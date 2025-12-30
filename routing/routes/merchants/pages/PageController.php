<?php

namespace routing\routes\merchants\pages;

use classes\data\Calculate;
use classes\enumerations\Links;
use classes\lang\Translate;
use classes\Methods;
use classes\organisations\MemberEnum;
use classes\utility\Titles;
use Database\Collection;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class PageController {

    public static function add(array $args): mixed  {
        $invitations = Methods::organisationMembers()->getByX(['uuid' => __uuid(), 'invitation_status' => MemberEnum::INVITATION_PENDING])->map(function ($invitation) {
            $organisation = $invitation["organisation"];
            $invitation["name"] = $organisation['name'];
            $latestInvitationHistory = $invitation['invitation_activity'][count($invitation['invitation_activity']) - 1];
            $latestInvitationHistory = toArray($latestInvitationHistory);
            if(!empty($latestInvitationHistory) && $latestInvitationHistory['event'] === MemberEnum::INVITATION_PENDING)
                $invitation["timestamp"] = $latestInvitationHistory['timestamp'];
            else $invitation["timestamp"] = strtotime($organisation['created_at']);
            return $invitation;
        });

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        return Views("ORGANISATION_ADD", compact('invitations', 'worldCountries'));
    }

    public static function organisation(array $args): mixed  {
        // Check if user has ANY read permission for organisation pages
        if(!\classes\app\OrganisationPermissions::__oRead('billing', '') &&
           !\classes\app\OrganisationPermissions::__oRead('team', '') &&
           !\classes\app\OrganisationPermissions::__oRead('roles', '') &&
           !\classes\app\OrganisationPermissions::__oRead('locations', '') &&
           !\classes\app\OrganisationPermissions::__oRead('orders', '') &&
           !\classes\app\OrganisationPermissions::__oRead('organisation', '')) {
            return null;
        }

        $memberRows = Methods::organisationMembers()->getUserOrganisations();
        $memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations()->map(function ($location) {
            $orders = Methods::orders()->getByX(['location' => $location['uid'], 'status' => 'COMPLETED'], ['amount', 'uuid']);
            $location['order_count'] = $orders->count();
            $location['net_sales'] = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);

            // Calculate unique customer count
            $customerIds = [];
            foreach ($orders->list() as $order) {
                $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
                $customerIds[$customerId] = true;
            }
            $location['customer_count'] = count($customerIds);
            $location['lfl_month'] = 100;
            return $location;
        });

        // Get setup requirements
        $setupRequirements = Methods::organisations()->getSetupRequirements();

        return Views("ORGANISATION_OVERVIEW", compact('memberRows', 'worldCountries', 'locations', 'setupRequirements'));
    }

    public static function team(array $args): mixed {
        // Check if user has read permission for team members
        if(!\classes\app\OrganisationPermissions::__oRead('team', 'members')) {
            return null;
        }

        // Members are now loaded via AJAX for better pagination support
        $permissions = Settings::$organisation?->organisation->permissions;
        $memberRows = Methods::organisationMembers()->getUserOrganisations();
        $memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');

        // Get locations for scoped permissions
        $locations = Methods::locations()->getMyLocations(null, ['uid', 'name', 'slug']);

        return Views("MERCHANT_ORGANISATION_TEAM", compact('permissions', 'memberRows', 'locations'));
    }

    public static function orders(array $args): mixed  {
        // Check orders.payments permission
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) {
            return null;
        }

        $locationHandler = Methods::locations();
        $orderHandler = Methods::orders();

        // Get date filters from query params
        $startDate = $args['start'] ?? null;
        $endDate = $args['end'] ?? null;

        // Build where conditions
        $where = ['organisation' => __oUuid(), 'status' => ['DRAFT', 'PENDING', 'COMPLETED']];

        // Add date filters if provided
        if(!isEmpty($startDate)) {
            $where['created_at >='] = date('Y-m-d 00:00:00', strtotime($startDate));
        }
        if(!isEmpty($endDate)) {
            $where['created_at <='] = date('Y-m-d 23:59:59', strtotime($endDate));
        }

        // Use queryBuilder to conditionally add scoped location filter
        $locationIds = Methods::locations()->userLocationPredicate();
        $queryBuilder = $orderHandler->queryBuilder()->whereList($where);
        if(!empty($locationIds)) {
            $queryBuilder->where('location', $locationIds);
        }
        $orders = $orderHandler->queryGetAll($queryBuilder->order('created_at', 'DESC'));
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $customers = [];
        foreach ($orders->list() as $n => $order) {
            $customer = $order->uuid;
            if(is_string($customer) && array_key_exists($customer, $customers)) {
                $order->uuid = $customers[$customer];
                $orders->updateItem($n, $order);
            }
            else if(!is_string($customer)) $customers[$customer->uid] = $customer;
        }

        return Views("MERCHANT_ORDERS", compact('orders', 'locationOptions', 'startDate', 'endDate'));
    }

    public static function customers(array $args): mixed  {
        // Check orders.customers permission
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'customers')) {
            return null;
        }

        $orderHandler = Methods::orders();

        // Get all completed orders for the organisation (filtered by scoped locations)
        $locationIds = Methods::locations()->userLocationPredicate();
        $orders = $orderHandler->getByX([
            'organisation' => __oUuid(),
            'status' => 'COMPLETED'
        ], ['uuid', 'amount', 'created_at'], ['location' => $locationIds]);

        // Group orders by customer and calculate stats
        $customersMap = [];
        foreach ($orders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;

            if(!isset($customersMap[$customerId])) {
                $customersMap[$customerId] = [
                    'customer' => is_object($order->uuid) ? $order->uuid : null,
                    'total_spent' => 0,
                    'order_count' => 0,
                    'last_order_date' => $order->created_at,
                    'first_order_date' => $order->created_at,
                ];
            }

            $customersMap[$customerId]['total_spent'] += $order->amount;
            $customersMap[$customerId]['order_count']++;

            // Update last order date if this order is more recent
            if(strtotime($order->created_at) > strtotime($customersMap[$customerId]['last_order_date'])) {
                $customersMap[$customerId]['last_order_date'] = $order->created_at;
            }

            // Update first order date if this order is older
            if(strtotime($order->created_at) < strtotime($customersMap[$customerId]['first_order_date'])) {
                $customersMap[$customerId]['first_order_date'] = $order->created_at;
            }

            // Keep customer object reference
            if(is_object($order->uuid)) {
                $customersMap[$customerId]['customer'] = $order->uuid;
            }
        }

        // Convert to array and sort by last order date (most recent first)
        $customers = array_values($customersMap);
        usort($customers, function($a, $b) {
            return strtotime($b['last_order_date']) - strtotime($a['last_order_date']);
        });

        return Views("MERCHANT_CUSTOMERS", compact('customers'));
    }

    public static function payments(array $args): mixed  {
        // Check orders.payments permission
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) return null;

        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        // Get date filters from query params
        $startDate = $args['start'] ?? null;
        $endDate = $args['end'] ?? null;


        // Build where conditions with scoped locations
        $locationIds = Methods::locations()->userLocationPredicate();
        $where = ['organisation' => __oUuid(), 'status' => 'COMPLETED'];
        $queryBuilder = $paymentsHandler->queryBuilder()->whereList($where);

        if(!empty($locationIds)) {
            $queryBuilder->where('location', $locationIds);
        }

        if(!isEmpty($startDate)) {
            $queryBuilder->whereTimeAfter("paid_at", strtotime($startDate), ">=");
        }
        if(!isEmpty($endDate)) {
            $queryBuilder->whereTimeBefore("paid_at", strtotime($endDate . " +1 day"), "<=");
        }

        $queryBuilder->order("paid_at", 'DESC');
        $payments = $paymentsHandler->queryGetAll($queryBuilder);

        // Enrich payments with order and customer data
        foreach ($payments->list() as $n => $payment) {
            $order = $payment->order;
            if(!is_object($order)) {
                $order = $orderHandler->get($order);
                $payment->order = $order;
                $payments->updateItem($n, $payment);
            }
        }

        return Views("MERCHANT_PAYMENTS", compact('payments', 'startDate', 'endDate'));
    }

    public static function pendingPayments(array $args): mixed  {
        // Permission check: Need organisation permission for 'orders.payments'
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) return null;

        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        // Get date filters from query params
        $startDate = $args['start'] ?? null;
        $endDate = $args['end'] ?? null;

        // Build where conditions with scoped locations
        $locationIds = Methods::locations()->userLocationPredicate();
        $where = ['organisation' => __oUuid(), 'status' => 'SCHEDULED'];
        $queryBuilder = $paymentsHandler->queryBuilder()->whereList($where);

        if(!empty($locationIds)) {
            $queryBuilder->where('location', $locationIds);
        }

        if(!isEmpty($startDate)) {
            $queryBuilder->whereTimeAfter("due_date", strtotime($startDate), ">=");
        }
        if(!isEmpty($endDate)) {
            $queryBuilder->whereTimeBefore("due_date", strtotime($endDate . " +1 day"), "<=");
        }

        $queryBuilder->order("due_date", 'ASC');
        $payments = $paymentsHandler->queryGetAll($queryBuilder);

        // Enrich payments with order and customer data
        foreach ($payments->list() as $n => $payment) {
            $order = $payment->order;
            if(!is_object($order)) {
                $order = $orderHandler->get($order);
                $payment->order = $order;
                $payments->updateItem($n, $payment);
            }
        }

        return Views("MERCHANT_PENDING_PAYMENTS", compact('payments', 'startDate', 'endDate'));
    }

    public static function pastDuePayments(array $args): mixed  {
        // Permission check: Need organisation permission for 'orders.payments'
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) return null;

        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        // Get date filters from query params
        $startDate = $args['start'] ?? null;
        $endDate = $args['end'] ?? null;

        // Build where conditions with scoped locations
        $locationIds = Methods::locations()->userLocationPredicate();
        $where = ['organisation' => __oUuid(), 'status' => 'PAST_DUE'];
        $queryBuilder = $paymentsHandler->queryBuilder()->whereList($where);

        if(!empty($locationIds)) {
            $queryBuilder->where('location', $locationIds);
        }

        if(!isEmpty($startDate)) {
            $queryBuilder->whereTimeAfter("due_date", strtotime($startDate), ">=");
        }
        if(!isEmpty($endDate)) {
            $queryBuilder->whereTimeBefore("due_date", strtotime($endDate . " +1 day"), "<=");
        }

        $queryBuilder->order("due_date", 'DESC');
        $payments = $paymentsHandler->queryGetAll($queryBuilder);

        // Enrich payments with order and customer data
        foreach ($payments->list() as $n => $payment) {
            $order = $payment->order;
            if(!is_object($order)) {
                $order = $orderHandler->get($order);
                $payment->order = $order;
                $payments->updateItem($n, $payment);
            }
        }

        return Views("MERCHANT_PAST_DUE_PAYMENTS", compact('payments', 'startDate', 'endDate'));
    }

    public static function orderDetail(array $args): mixed  {
        $orderId = $args['id'];
        $orderHandler = Methods::orders();
        $order = $orderHandler->get($orderId);

        if(isEmpty($order)) {
            return null;
        }


        // Verify the order belongs to the current organisation
        if($order->organisation !== __oUuid()) {
            return null;
        }

        return Views("MERCHANT_ORDER_DETAIL", compact('order'));
    }

    public static function customerDetail(array $args): mixed  {
        $customerId = $args['id'];
        $userHandler = Methods::users();
        $customer = $userHandler->get($customerId);

        if(isEmpty($customer)) {
            return null;
        }

        // Permission check: Need organisation permission for 'orders.customers'
        // This allows viewing customers across the organisation
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'customers')) return null;

        // Get all orders for this customer with the current organisation
        $orderHandler = Methods::orders();
        $orders = $orderHandler->getByXOrderBy('created_at', 'DESC', [
            'uuid' => $customerId,
            'organisation' => __oUuid(),
            'status' => ['COMPLETED', 'PENDING', 'CANCELLED']
        ]);

        // Calculate statistics
        $completedOrders = $orders->filter(function($order) {
            return $order['status'] === 'COMPLETED';
        });

        $totalSpent = $completedOrders->reduce(function($carry, $order) {
            return $carry + $order['amount'];
        }, 0);

        $orderCount = $completedOrders->count();

        // Get first order date
        $firstOrder = $orders->sortByKey("created_at", true)->first();
        $firstOrderDate = !isEmpty($firstOrder) ? $firstOrder->created_at : null;

        return Views("MERCHANT_CUSTOMER_DETAIL", compact(
            'customer', 'orders', 'totalSpent', 'orderCount', 'firstOrderDate'
        ));
    }

    public static function terminals(array $args): mixed  {
        // Check locations.checkout permission
        if(!\classes\app\OrganisationPermissions::__oRead('locations', 'terminals')) return null;

        $locationHandler = Methods::locations();
        $terminalHandler = Methods::terminals();
        $terminals = $terminalHandler->getMyTerminals();
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name', 'slug', 'cvr']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');

        return Views("MERCHANT_TERMINALS", compact('terminals', 'locationOptions', 'locations'));
    }

    public static function locationMembers(array $args): mixed  {
        $slug = $args['slug'];
        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            return null; // User doesn't have access to this location
        }

        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);


        $orderHandler = Methods::orders();
        $orders = $orderHandler->getByX(['location' => $location->uid, 'status' => 'COMPLETED'], ['amount', 'uuid', 'created_at']);
        $orderCount = $orders->count();
        $netSales = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
        $ordersToday = $orders->filter(function ($item) { return date("Y-m-d", strtotime($item['created_at'])) === date('Y-m-d'); });
        $ordersTodayCount = $ordersToday->count();
        $orderAverage = Calculate::average($netSales, $orderCount);
        $newCustomersCount = $orderCount;
        $netSalesLflMonth = 100;
        $newCustomersLflMonth = 100;
        $todayOrdersCountLflMonth = min(100, $ordersTodayCount * 100);
        $averageLflMonth = 100;


        $orders = $orderHandler->getByX(['location' => $location->uid]);

        // Members are now loaded via AJAX for better pagination support
        $permissions = $location->permissions;

        // Get existing location member UUIDs for filtering org members in invite modal
        $existingLocationMemberUuids = Methods::locationMembers()
            ->queryBuilder()
            ->where('location', $location->uid)
            ->where('status', [MemberEnum::MEMBER_SUSPENDED, MemberEnum::MEMBER_ACTIVE])
            ->pluck('uuid');

        // Get organisation members who are not already location members for invite modal
        $organisationMembers = Methods::organisationMembers()
            ->getByX(['organisation' => __oUuid(), 'status' => MemberEnum::MEMBER_ACTIVE, 'invitation_status' => MemberEnum::INVITATION_ACCEPTED])
            ->filter(function($member) use ($existingLocationMemberUuids) {
                $uuid = is_string($member['uuid']) ? $member['uuid'] : $member['uuid']['uid'];
                return !in_array($uuid, $existingLocationMemberUuids) && $uuid !== __uuid();
            })
            ->map(function($member) {
                return [
                    'uuid' => $member['uuid'],
                    'name' => $member['uuid']['full_name'],
                    'email' => $member['uuid']['email'] ?? ''
                ];
            });


        // Get location roles for dropdown
        $locationRoles = [];
        foreach($permissions as $role => $roleData) {
            $locationRoles[$role] = ucfirst(Translate::word(Titles::clean($role)));
        }

        return Views("MERCHANT_LOCATION_MEMBERS", compact(
            'locations', 'locationOptions', 'permissions',
            'worldCountries', 'slug', 'location', 'orders', 'orderCount', 'netSales',
            'ordersTodayCount', 'orderAverage', 'ordersToday', 'newCustomersCount', 'todayOrdersCountLflMonth',
            'netSalesLflMonth', 'newCustomersLflMonth', 'averageLflMonth', 'organisationMembers', 'locationRoles'
        ));
    }



    public static function singleLocation(array $args): mixed  {
        $slug = $args['slug'];
        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            return null; // User doesn't have access to this location
        }

        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);


        $orderHandler = Methods::orders();
        $orders = $orderHandler->getByX(['location' => $location->uid, 'status' => 'COMPLETED'], ['amount', 'uuid', 'created_at']);
        $orderCount = $orders->count();
        $netSales = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
        $ordersToday = $orders->filter(function ($item) { return date("Y-m-d", strtotime($item['created_at'])) === date('Y-m-d'); });
        $ordersTodayCount = $ordersToday->count();
        $orderAverage = Calculate::average($netSales, $orderCount);
        $newCustomersCount = $orderCount;
        $netSalesLflMonth = 100;
        $newCustomersLflMonth = 100;
        $todayOrdersCountLflMonth = min(100, $ordersTodayCount * 100);
        $averageLflMonth = 100;


        $orders = $orderHandler->getByX(['location' => $location->uid]);


        return Views("MERCHANT_SINGLE_LOCATION", compact(
            'locations', 'locationOptions',
            'worldCountries', 'slug', 'location', 'orders', 'orderCount', 'netSales',
            'ordersTodayCount', 'orderAverage', 'ordersToday', 'newCustomersCount', 'todayOrdersCountLflMonth',
            'netSalesLflMonth', 'newCustomersLflMonth', 'averageLflMonth'
        ));
    }

    public static function locationPageBuilderPreview(array $args): mixed  {
        $slug = $args['slug'];
        $draftId = $args['id'];

        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            return null; // User doesn't have access to this location
        }

        $pagesHandler = Methods::locationPages();
        $pageDraft = $pagesHandler->get($draftId);

        if(isEmpty($pageDraft) || $pageDraft->location->uid !== $location->uid) {
            return null;
        }

        return Views("MERCHANT_LOCATION_PAGE_PREVIEW", compact('location', 'pageDraft', 'slug', 'draftId'));
    }

    public static function locationPageBuilderPreviewCheckout(array $args): mixed  {
        $slug = $args['slug'];
        $draftId = $args['id'];

        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            return null; // User doesn't have access to this location
        }

        $pagesHandler = Methods::locationPages();
        $pageDraft = $pagesHandler->get($draftId);

        if(isEmpty($pageDraft) || $pageDraft->location->uid !== $location->uid) {
            return null;
        }

        // Get contact info
        $address = $locationHandler->locationAddress($location);
        $addressString = Methods::misc()::extractCompanyAddressString($address, false, false);
        $contactEmail = $locationHandler->contactEmail($location);
        $contactPhone = $locationHandler->contactPhone($location);

        return Views("MERCHANT_LOCATION_PAGE_PREVIEW_CHECKOUT", compact('location', 'pageDraft', 'slug', 'draftId', 'addressString', 'contactEmail', 'contactPhone'));
    }

    public static function locationPageBuilder(array $args): mixed  {
        $slug = $args['slug'];
        $ref = $args['ref'] ?? null;

        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            return null; // User doesn't have access to this location
        }
        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        $pagesHandler = Methods::locationPages();

        // Get all page versions for dropdown
        $allPages = $pagesHandler->excludeForeignKeys()
            ->getByX(['location' => $location->uid], ['uid', 'state', 'created_at']);

        // Build page options for dropdown
        $pageOptions = [];
        foreach($allPages->list() as $page) {
            $label = $page->state;
            if($page->state === 'DRAFT') $label = "Draft (" . date('d. M H:i', strtotime($page->created_at)) . ")";
            elseif($page->state === 'ARCHIVED') $label = "Arkiveret (" . date('d. M H:i', strtotime($page->created_at)) . ")";
            elseif($page->state === 'PUBLISHED') $label = "Udgivet";

            $pageOptions[$page->uid] = $label;
        }
        arsort($pageOptions); //published, draft, archived

        // Determine which page to load
        if(!isEmpty($ref)) {
            // Load specific page by ref
            $pageDraft = $pagesHandler->get($ref);
            if(isEmpty($pageDraft) || $pageDraft->location !== $location->uid) {
                // Invalid ref, redirect to default
                Response()->redirect(Links::$merchant->locations->pageBuilder($slug));
            }
        } else {
            // No ref: load PUBLISHED or most recent draft
            $published = $pagesHandler->getPublished($location->uid);
            if(!isEmpty($published)) {
                $pageDraft = $published;
            } else {
                $pageDraft = $pagesHandler->getCurrentDraft($location->uid);
                if(isEmpty($pageDraft)) {
                    // Create new draft
                    $pageDraft = $pagesHandler->getOrCreateDraft($location->uid, __uuid());
                }
            }
        }

        if(isEmpty($pageDraft)) {
            // Fallback to location data if draft creation fails
            $pageDraft = (object) [
                'uid' => null,
                'state' => 'DRAFT',
                'logo' => DEFAULT_LOCATION_LOGO,
                'hero_image' => DEFAULT_LOCATION_HERO,
                'title' => $location->name,
                'caption' => $location->caption,
                'about_us' => $location->description,
                'credit_widget_enabled' => 1,
                'sections' => []
            ];
        }

        return Views("MERCHANT_LOCATION_PAGE_BUILDER", compact(
            'locations', 'locationOptions','worldCountries', 'slug', 'location', 'pageDraft', 'pageOptions'
        ));
    }

    public static function locations(array $args): mixed  {
        // Check locations.locations permission
        if(!\classes\app\OrganisationPermissions::__oRead('locations', 'locations')) return null;

        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("MERCHANT_LOCATIONS", compact('locations', 'locationOptions', 'worldCountries'));
    }


    public static function dashboard(array $args): mixed  {
        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');

        // Get date filters from query params, default to current month
        $startDate = $args['start'] ?? date('Y-m-01');
        $endDate = $args['end'] ?? date('Y-m-d');

        // Calculate previous period for comparison
        $periodLength = (strtotime($endDate) - strtotime($startDate)) / 86400;
        $previousStart = date('Y-m-d', strtotime($startDate . " -{$periodLength} days"));
        $previousEnd = date('Y-m-d', strtotime($startDate . " -1 day"));

        $orderHandler = Methods::orders();
        $paymentsHandler = Methods::payments();

        // Get scoped location IDs
        $locationIds = Methods::locations()->userLocationPredicate();

        // Get completed orders for current period (filtered by scoped locations)
        $ordersQuery = $orderHandler->queryBuilder()
            ->whereList(['organisation' => __oUuid(), 'status' => 'COMPLETED'])
            ->whereTimeAfter('created_at', strtotime($startDate), '>=')
            ->whereTimeBefore('created_at', strtotime($endDate . ' +1 day'), '<=');
        if(!empty($locationIds)) {
            $ordersQuery->where('location', $locationIds);
        }
        $orders = $orderHandler->queryGetAll($ordersQuery);

        // Get completed orders for previous period (filtered by scoped locations)
        $previousOrdersQuery = $orderHandler->queryBuilder()
            ->whereList(['organisation' => __oUuid(), 'status' => 'COMPLETED'])
            ->whereTimeAfter('created_at', strtotime($previousStart), '>=')
            ->whereTimeBefore('created_at', strtotime($previousEnd . ' +1 day'), '<=');
        if(!empty($locationIds)) {
            $previousOrdersQuery->where('location', $locationIds);
        }
        $previousOrders = $orderHandler->queryGetAll($previousOrdersQuery);

        // Calculate order metrics
        $orderCount = $orders->count();
        $grossRevenue = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
        $totalFees = $orders->reduce(function ($carry, $item) { return $carry + $item['fee_amount']; }, 0);
        $netRevenue = $grossRevenue - $totalFees;
        $orderAverage = Calculate::average($grossRevenue, $orderCount);

        // Calculate previous period metrics
        $previousOrderCount = $previousOrders->count();
        $previousGrossRevenue = $previousOrders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
        $previousOrderAverage = Calculate::average($previousGrossRevenue, $previousOrderCount);

        // Get unique customers count
        $customerIds = [];
        foreach ($orders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            $customerIds[$customerId] = true;
        }
        $customerCount = count($customerIds);

        // Get previous period customers
        $previousCustomerIds = [];
        foreach ($previousOrders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            $previousCustomerIds[$customerId] = true;
        }
        $previousCustomerCount = count($previousCustomerIds);

        // Get payment metrics (filtered by scoped locations)
        $completedPayments = $paymentsHandler->getByX([
            'organisation' => __oUuid(),
            'status' => 'COMPLETED'
        ], ['amount'], ['location' => $locationIds]);
        $totalPaid = $completedPayments->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);

        $outstandingPayments = $paymentsHandler->getByX([
            'organisation' => __oUuid(),
            'status' => ['SCHEDULED', 'PAST_DUE']
        ], ['amount'], ['location' => $locationIds]);
        $totalOutstanding = $outstandingPayments->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);

        $pastDuePayments = $paymentsHandler->getByX([
            'organisation' => __oUuid(),
            'status' => 'PAST_DUE'
        ], ['amount'], ['location' => $locationIds]);
        $totalPastDue = $pastDuePayments->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);

        // Calculate BNPL usage rate
        $bnplOrders = $orders->filter(function ($order) {
            return !empty($order['payment_plan']) && in_array($order['payment_plan'], ['installments', 'pushed']);
        });
        $bnplUsageRate = Calculate::average($bnplOrders->count(), $orderCount) * 100;

        // Calculate percentage changes
        $revenueChange = Calculate::percentageChange($previousGrossRevenue, $grossRevenue);
        $orderCountChange = Calculate::percentageChange($previousOrderCount, $orderCount);
        $customerCountChange = Calculate::percentageChange($previousCustomerCount, $customerCount);
        $averageChange = Calculate::percentageChange($previousOrderAverage, $orderAverage);

        // Prepare chart data (daily aggregation)
        $chartData = [];
        $currentDate = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        while ($currentDate <= $endTimestamp) {
            $dateKey = date('Y-m-d', $currentDate);
            $chartData[$dateKey] = [
                'date' => date('d/m', $currentDate),
                'revenue' => 0,
                'orders' => 0,
            ];
            $currentDate = strtotime('+1 day', $currentDate);
        }

        // Aggregate orders by day
        foreach ($orders->list() as $order) {
            $dateKey = date('Y-m-d', strtotime($order->created_at));
            if (isset($chartData[$dateKey])) {
                $chartData[$dateKey]['revenue'] += $order->amount;
                $chartData[$dateKey]['orders']++;
            }
        }

        // Get setup requirements
        $setupRequirements = Methods::organisations()->getSetupRequirements();

        // Get active terminals for quick access (filtered by user's accessible locations)
        $terminals = Methods::terminals()->getMyTerminals()
            ->filter(fn($t) => $t['status'] === 'ACTIVE');

        return Views("MERCHANT_DASHBOARD", compact(
            'locationOptions', 'grossRevenue', 'netRevenue', 'totalFees', 'orders', 'orderCount',
            'orderAverage', 'customerCount', 'totalPaid', 'totalOutstanding', 'totalPastDue',
            'bnplUsageRate', 'revenueChange', 'orderCountChange', 'customerCountChange',
            'averageChange', 'setupRequirements', 'chartData', 'startDate', 'endDate', 'terminals'
        ));
    }

    public static function settings(array $args): mixed {
        $user = Methods::users()->get(__uuid());
        $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);
        return Views("MERCHANT_SETTINGS", compact('user', 'authLocal'));
    }

    #[NoReturn] public static function getTerminalQrBytes(array $args): void {
        $terminalId = $args["id"];
        $terminal = Methods::terminals()->get($terminalId);
        if(isEmpty($terminal)) Response()->jsonError("Invalid terminal", [], 404);
        if($terminal->status !== 'ACTIVE') Response()->jsonError("The terminal is not active", [], 403);
        if($terminal->location->status !== 'ACTIVE') Response()->jsonError("The location is not active", [], 403);

        $link = __url(Links::$merchant->terminals->checkoutStart($terminal->location->slug, $terminal->uid));
        $qrGenerator = Methods::qr()->build($link)->get();


        Response()->mimeType($qrGenerator->getString(), $qrGenerator->getMimeType());
    }

}