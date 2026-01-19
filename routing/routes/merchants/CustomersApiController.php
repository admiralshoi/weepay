<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class CustomersApiController {

    #[NoReturn] public static function getCustomers(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "total_spent";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";

        // Validate organisation
        if(isEmpty(Settings::$organisation))
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        // Check permissions
        if(!OrganisationPermissions::__oRead('orders', 'customers'))
            Response()->jsonError("Du har ikke tilladelse til at se kunder.");

        $organisationUid = Settings::$organisation->organisation->uid;
        $orderHandler = Methods::orders()->excludeForeignKeys();

        // Get scoped location filter
        $locationIds = Methods::locations()->userLocationPredicate();

        // Build query to get customer stats from completed orders
        $query = $orderHandler->queryBuilder()
            ->rawSelect('uuid, COUNT(*) as order_count, SUM(amount - amount_refunded) as total_spent, MIN(created_at) as first_order_date, MAX(created_at) as last_order_date')
            ->where('organisation', $organisationUid)
            ->where('status', 'COMPLETED')
            ->whereColumnIsNotNull('uuid');

        if(!empty($locationIds)) {
            $query->where('location', $locationIds);
        }

        $query->groupBy('uuid');

        // Get all customer aggregates first (we need to filter/sort in PHP due to joined user data)
        $customerAggregates = $query->all()->toArray();

        // Get all unique customer UIDs
        $customerUids = array_map(fn($agg) => $agg['uuid'], $customerAggregates);

        if(empty($customerUids)) {
            Response()->jsonSuccess("", [
                "customers" => [],
                "pagination" => [
                    "page" => 1,
                    "perPage" => $perPage,
                    "total" => 0,
                    "totalPages" => 0,
                ],
            ]);
        }

        // Fetch all customer details
        $userHandler = Methods::users();
        $usersQuery = $userHandler->queryBuilder()->where('uid', $customerUids);

        // Apply search filter on users
        if(!empty($search)) {
            $usersQuery->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->whereLike('phone', $search)
                ->endGroup();
        }

        $users = $usersQuery->all();
        $usersMap = [];
        foreach($users->list() as $user) {
            $usersMap[$user->uid] = $user;
        }

        // Combine customer data with aggregates
        $customers = [];
        foreach($customerAggregates as $agg) {
            if(!isset($usersMap[$agg['uuid']])) continue; // Skip if user doesn't match search

            $user = $usersMap[$agg['uuid']];
            $customers[] = [
                'uid' => $user->uid,
                'full_name' => $user->full_name ?? 'N/A',
                'email' => $user->email ?? 'N/A',
                'phone' => !isEmpty($user->phone) ? '+' . $user->phone : 'N/A',
                'order_count' => (int)$agg['order_count'],
                'total_spent' => (float)$agg['total_spent'],
                'first_order_date' => date("d/m-Y", strtotime($agg['first_order_date'])),
                'last_order_date' => date("d/m-Y", strtotime($agg['last_order_date'])),
                'first_order_timestamp' => strtotime($agg['first_order_date']),
                'last_order_timestamp' => strtotime($agg['last_order_date']),
                'detail_url' => __url(Links::$merchant->customerDetail($user->uid)),
            ];
        }

        // Sort customers
        usort($customers, function($a, $b) use ($sortColumn, $sortDirection) {
            $sortMap = [
                'name' => 'full_name',
                'orders' => 'order_count',
                'total_spent' => 'total_spent',
                'first_order' => 'first_order_timestamp',
                'last_order' => 'last_order_timestamp',
            ];

            $col = $sortMap[$sortColumn] ?? 'total_spent';
            $aVal = $a[$col] ?? 0;
            $bVal = $b[$col] ?? 0;

            if(is_string($aVal)) {
                $cmp = strcasecmp($aVal, $bVal);
            } else {
                $cmp = $aVal <=> $bVal;
            }

            return $sortDirection === 'DESC' ? -$cmp : $cmp;
        });

        // Calculate pagination
        $totalCount = count($customers);
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Slice for current page
        $pagedCustomers = array_slice($customers, $offset, $perPage);

        // Remove timestamp fields from output
        $pagedCustomers = array_map(function($c) {
            unset($c['first_order_timestamp'], $c['last_order_timestamp']);
            return $c;
        }, $pagedCustomers);

        Response()->jsonSuccess("", [
            "customers" => $pagedCustomers,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }
}
