<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class OrdersApiController {

    #[NoReturn] public static function getOrders(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        // Validate organisation
        if(isEmpty(Settings::$organisation))
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        // Check permissions
        if(!OrganisationPermissions::__oRead('orders', 'payments'))
            Response()->jsonError("Du har ikke tilladelse til at se ordrer.");

        $organisationUid = Settings::$organisation->organisation->uid;

        // Build base query
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->where('organisation', $organisationUid)
            ->where('status', ['DRAFT', 'PENDING', 'COMPLETED', 'CANCELLED']);

        // Apply scoped location filter if applicable
        $locationIds = Methods::locations()->userLocationPredicate();
        if(!empty($locationIds)) {
            $query->where('location', $locationIds);
        }

        // Apply status filter
        if(!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        // Apply date range filter
        if(!empty($startDate)) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter - search in order UID or customer name/email
        if(!empty($search)) {
            // First check if search matches order uid pattern
            $searchLower = strtolower($search);

            // Get matching user UIDs
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            // Search in orders by uid or customer
            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingUserUids)) {
                $query->where('uuid', $matchingUserUids);
            }
            $query->endGroup();
        }

        // Get total count
        $totalCount = $query->count();

        if($totalCount === 0) {
            Response()->jsonSuccess("", [
                "orders" => [],
                "pagination" => [
                    "page" => 1,
                    "perPage" => $perPage,
                    "total" => 0,
                    "totalPages" => 0,
                ],
            ]);
        }

        // Calculate pagination
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Map sort columns
        $sortColumnMap = [
            'date' => 'created_at',
            'amount' => 'amount',
            'status' => 'status',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Fetch orders
        $orders = $orderHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Get order UIDs for paid amounts
        $orderUids = $orders->pluck('uid')->toArray();
        $paidAmounts = [];
        if(!empty($orderUids)) {
            $paymentHandler = Methods::payments()->excludeForeignKeys();
            $payments = $paymentHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $orderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach ($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }
        }

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
            'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
        ];

        // Transform orders for frontend
        $transformedOrders = [];
        foreach($orders->list() as $order) {
            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $outstanding = $order->amount - $paidAmount;

            // Get customer info - uuid is already resolved to object via foreign key
            $customerName = 'Ukendt';
            $customerEmail = '';
            $customerUid = null;
            if(!isEmpty($order->uuid) && is_object($order->uuid)) {
                $customerName = $order->uuid->full_name ?? 'Ukendt';
                $customerEmail = $order->uuid->email ?? '';
                $customerUid = $order->uuid->uid;
            }

            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m-Y H:i", strtotime($order->created_at)),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_uid' => $customerUid,
                'amount' => (float)$order->amount,
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'currency' => $order->currency,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$merchant->orderDetail($order->uid)),
                'customer_url' => $customerUid ? __url(Links::$merchant->customerDetail($customerUid)) : null,
            ];
        }

        Response()->jsonSuccess("", [
            "orders" => $transformedOrders,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }

    #[NoReturn] public static function getLocationOrders(array $args): void {
        $slug = $args["slug"] ?? null;
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        // Validate organisation
        if(isEmpty(Settings::$organisation))
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        // Get location by slug
        if(isEmpty($slug))
            Response()->jsonError("Lokation mangler.");

        $location = Methods::locations()->getFirst(['slug' => $slug, 'uuid' => Settings::$organisation->organisation->uid]);
        if(isEmpty($location))
            Response()->jsonError("Lokation ikke fundet.");

        // Check permissions for this location
        if(!\classes\app\LocationPermissions::__oRead($location, 'orders'))
            Response()->jsonError("Du har ikke tilladelse til at se ordrer for denne lokation.");

        // Build base query
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->where('location', $location->uid)
            ->where('status', ['DRAFT', 'PENDING', 'COMPLETED', 'CANCELLED']);

        // Apply status filter
        if(!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        // Apply date range filter
        if(!empty($startDate)) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter
        if(!empty($search)) {
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingUserUids)) {
                $query->where('uuid', $matchingUserUids);
            }
            $query->endGroup();
        }

        // Get total count
        $totalCount = $query->count();

        if($totalCount === 0) {
            Response()->jsonSuccess("", [
                "orders" => [],
                "pagination" => [
                    "page" => 1,
                    "perPage" => $perPage,
                    "total" => 0,
                    "totalPages" => 0,
                ],
            ]);
        }

        // Calculate pagination
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Map sort columns
        $sortColumnMap = [
            'date' => 'created_at',
            'amount' => 'amount',
            'status' => 'status',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Fetch orders
        $orders = $orderHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Get paid amounts
        $orderUids = $orders->pluck('uid')->toArray();
        $paidAmounts = [];
        if(!empty($orderUids)) {
            $paymentHandler = Methods::payments()->excludeForeignKeys();
            $payments = $paymentHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $orderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach ($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }
        }

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
            'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
        ];

        // Transform orders for frontend
        $transformedOrders = [];
        foreach($orders->list() as $order) {
            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $outstanding = $order->amount - $paidAmount;

            $customerName = 'Ukendt';
            $customerEmail = '';
            $customerUid = null;
            if(is_string($order->uuid)) $order->uuid = Methods::users()->get($order->uuid);
            if(!isEmpty($order->uuid) && is_object($order->uuid)) {
                $customerName = $order->uuid->full_name ?? 'Ukendt';
                $customerEmail = $order->uuid->email ?? '';
                $customerUid = $order->uuid->uid;
            }

            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m-Y H:i", strtotime($order->created_at)),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_uid' => $customerUid,
                'amount' => (float)$order->amount,
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'currency' => $order->currency,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$merchant->orderDetail($order->uid)),
                'customer_url' => $customerUid ? __url(Links::$merchant->customerDetail($customerUid)) : null,
            ];
        }

        Response()->jsonSuccess("", [
            "orders" => $transformedOrders,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }
}
