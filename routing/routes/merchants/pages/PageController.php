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
            $orders = Methods::orders()->getByX(['location' => $location['uid'], 'status' => 'COMPLETED'], ['amount', 'amount_refunded', 'uuid']);
            $location['order_count'] = $orders->count();
            $location['net_sales'] = $orders->reduce(function ($carry, $item) { return $carry + ($item['amount'] - $item['amount_refunded']); }, 0);

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
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');

        return Views("MERCHANT_ORDERS", compact('locationOptions'));
    }

    public static function customers(array $args): mixed  {
        // Check orders.customers permission
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'customers')) {
            return null;
        }

        return Views("MERCHANT_CUSTOMERS");
    }

    public static function payments(array $args): mixed  {
        // Check orders.payments permission
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) return null;

        return Views("MERCHANT_PAYMENTS");
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

        // Fetch payments for this order
        $paymentHandler = Methods::payments();
        $payments = $paymentHandler->getByXOrderBy('installment_number', 'ASC', ['order' => $orderId]);

        return Views("MERCHANT_ORDER_DETAIL", compact('order', 'payments'));
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
            return $carry + ($order['amount'] - $order['amount_refunded']);
        }, 0);

        $orderCount = $completedOrders->count();

        // Get first order date
        $firstOrder = $orders->sortByKey("created_at", true)->first();
        $firstOrderDate = !isEmpty($firstOrder) ? $firstOrder->created_at : null;

        return Views("MERCHANT_CUSTOMER_DETAIL", compact(
            'customer', 'orders', 'totalSpent', 'orderCount', 'firstOrderDate'
        ));
    }

    public static function paymentDetail(array $args): mixed  {
        $paymentId = $args['id'];
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if(isEmpty($payment)) {
            return null;
        }

        // Verify the payment belongs to the current organisation
        if($payment->organisation !== __oUuid()) {
            return null;
        }

        // Check permissions
        if(!\classes\app\OrganisationPermissions::__oRead('orders', 'payments')) return null;

        // Get the order associated with this payment
        $order = $payment->order;

        // Get the customer (ensure it's a valid user object, not just a string uid)
        $customer = !isEmpty($payment->uuid) && is_object($payment->uuid) ? $payment->uuid : null;

        // Get all payments for the same order (for installment context)
        $orderPayments = null;
        if(!isEmpty($order)) {
            $orderPayments = $paymentHandler->getByXOrderBy('installment_number', 'ASC', ['order' => $order->uid]);
        }

        return Views("MERCHANT_PAYMENT_DETAIL", compact('payment', 'order', 'customer', 'orderPayments'));
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
        $orders = $orderHandler->getByX(['location' => $location->uid, 'status' => 'COMPLETED'], ['amount', 'amount_refunded', 'uuid', 'created_at']);
        $orderCount = $orders->count();
        $netSales = $orders->reduce(function ($carry, $item) { return $carry + ($item['amount'] - $item['amount_refunded']); }, 0);
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
        $orders = $orderHandler->getByX(['location' => $location->uid, 'status' => 'COMPLETED'], ['amount', 'amount_refunded', 'uuid', 'created_at']);
        $orderCount = $orders->count();
        $netSales = $orders->reduce(function ($carry, $item) { return $carry + ($item['amount'] - $item['amount_refunded']); }, 0);
        $ordersToday = $orders->filter(function ($item) { return date("Y-m-d", strtotime($item['created_at'])) === date('Y-m-d'); });
        $ordersTodayCount = $ordersToday->count();
        $orderAverage = Calculate::average($netSales, $orderCount);
        $newCustomersCount = $orderCount;
        $netSalesLflMonth = 100;
        $newCustomersLflMonth = 100;
        $todayOrdersCountLflMonth = min(100, $ordersTodayCount * 100);
        $averageLflMonth = 100;

        return Views("MERCHANT_SINGLE_LOCATION", compact(
            'locations', 'locationOptions',
            'worldCountries', 'slug', 'location', 'orderCount', 'netSales',
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
        $grossRevenue = $orders->reduce(function ($carry, $item) { return $carry + ($item['amount'] - $item['amount_refunded']); }, 0);
        $totalFees = $orders->reduce(function ($carry, $item) { return $carry + $item['fee_amount']; }, 0);
        $netRevenue = $grossRevenue - $totalFees;
        $orderAverage = Calculate::average($grossRevenue, $orderCount);

        // Calculate previous period metrics
        $previousOrderCount = $previousOrders->count();
        $previousGrossRevenue = $previousOrders->reduce(function ($carry, $item) { return $carry + ($item['amount'] - $item['amount_refunded']); }, 0);
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
                $chartData[$dateKey]['revenue'] += orderAmount($order);
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
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        return Views("MERCHANT_SETTINGS", compact('user', 'authLocal', 'worldCountries'));
    }

    public static function accessDenied(array $args): mixed {
        return Views("MERCHANT_ACCESS_DENIED");
    }

    public static function materials(array $args): mixed {
        // Get active marketing templates (only category=template, not a_sign_base)
        $templates = Methods::marketingTemplates()->getActiveTemplates();

        // Get user's accessible locations that have a published public page
        $allLocations = Methods::locations()->getMyLocations(null, ['uid', 'name', 'slug']);
        $locations = $allLocations->filter(function($location) {
            $publishedPage = Methods::locationPages()->excludeForeignKeys()->getFirst([
                'location' => $location['uid'],
                'state' => 'PUBLISHED'
            ]);
            return !isEmpty($publishedPage);
        });

        // Get active inspiration items
        $inspirations = Methods::marketingInspiration()->getActive();

        // Group inspirations by category
        $inspirationCategories = Methods::marketingInspiration()->getCategoryOptions();

        // Size options for download
        $sizeOptions = [
            'original' => 'Original',
            'A3' => 'A3 (297 × 420 mm)',
            'A4' => 'A4 (210 × 297 mm)',
            'A5' => 'A5 (148 × 210 mm)',
        ];

        return Views("MERCHANT_MATERIALS", compact(
            'templates', 'locations', 'inspirations', 'inspirationCategories', 'sizeOptions'
        ));
    }

    public static function reports(array $args): mixed {
        // Check permission
        if(!\classes\app\OrganisationPermissions::__oRead('organisation', 'reports')) {
            return null;
        }

        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name', 'slug']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');

        // Get date filters from query params, default to last 30 days
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');

        $orderHandler = Methods::orders();
        $paymentsHandler = Methods::payments();

        // Get scoped location IDs
        $locationIds = Methods::locations()->userLocationPredicate();

        // Get all completed orders for period
        $ordersQuery = $orderHandler->queryBuilder()
            ->whereList(['organisation' => __oUuid(), 'status' => 'COMPLETED'])
            ->whereTimeAfter('created_at', strtotime($startDate), '>=')
            ->whereTimeBefore('created_at', strtotime($endDate . ' +1 day'), '<=');
        if(!empty($locationIds)) {
            $ordersQuery->where('location', $locationIds);
        }
        $orders = $orderHandler->queryGetAll($ordersQuery);

        // Calculate KPIs
        $orderCount = $orders->count();
        $grossRevenue = $orders->reduce(fn($c, $i) => $c + ($i['amount'] - $i['amount_refunded']), 0);
        $totalFees = $orders->reduce(fn($c, $i) => $c + $i['fee_amount'], 0);
        $netRevenue = $grossRevenue - $totalFees;
        $orderAverage = $orderCount > 0 ? $grossRevenue / $orderCount : 0;

        // Unique customers
        $customerIds = [];
        foreach ($orders->list() as $order) {
            $customerId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            $customerIds[$customerId] = true;
        }
        $customerCount = count($customerIds);

        // BNPL vs Full payment breakdown
        $bnplOrders = $orders->filter(fn($o) => !empty($o['payment_plan']) && in_array($o['payment_plan'], ['installments', 'pushed']));
        $fullPaymentOrders = $orders->filter(fn($o) => empty($o['payment_plan']) || $o['payment_plan'] === 'full');
        $bnplCount = $bnplOrders->count();
        $fullPaymentCount = $fullPaymentOrders->count();
        $bnplRevenue = $bnplOrders->reduce(fn($c, $i) => $c + ($i['amount'] - $i['amount_refunded']), 0);
        $fullPaymentRevenue = $fullPaymentOrders->reduce(fn($c, $i) => $c + ($i['amount'] - $i['amount_refunded']), 0);

        // Payment status breakdown
        $allPayments = $paymentsHandler->getByX(['organisation' => __oUuid()], ['amount', 'status'], ['location' => $locationIds]);
        $paymentsByStatus = [
            'COMPLETED' => ['count' => 0, 'amount' => 0],
            'SCHEDULED' => ['count' => 0, 'amount' => 0],
            'PAST_DUE' => ['count' => 0, 'amount' => 0],
            'FAILED' => ['count' => 0, 'amount' => 0],
        ];
        foreach ($allPayments->list() as $payment) {
            $status = $payment->status;
            if (isset($paymentsByStatus[$status])) {
                $paymentsByStatus[$status]['count']++;
                $paymentsByStatus[$status]['amount'] += $payment->amount;
            }
        }

        // Revenue by location
        $revenueByLocation = [];
        foreach ($orders->list() as $order) {
            $locUid = is_object($order->location) ? $order->location->uid : $order->location;
            $locName = is_object($order->location) ? $order->location->name : 'Ukendt';
            if (!isset($revenueByLocation[$locUid])) {
                $revenueByLocation[$locUid] = ['name' => $locName, 'revenue' => 0, 'orders' => 0];
            }
            $revenueByLocation[$locUid]['revenue'] += orderAmount($order);
            $revenueByLocation[$locUid]['orders']++;
        }
        usort($revenueByLocation, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        // Daily revenue chart data
        $dailyData = [];
        $currentDate = strtotime($startDate);
        $endTimestamp = strtotime($endDate);
        while ($currentDate <= $endTimestamp) {
            $dateKey = date('Y-m-d', $currentDate);
            $dailyData[$dateKey] = ['date' => date('d/m', $currentDate), 'revenue' => 0, 'orders' => 0];
            $currentDate = strtotime('+1 day', $currentDate);
        }
        foreach ($orders->list() as $order) {
            $dateKey = date('Y-m-d', strtotime($order->created_at));
            if (isset($dailyData[$dateKey])) {
                $dailyData[$dateKey]['revenue'] += orderAmount($order);
                $dailyData[$dateKey]['orders']++;
            }
        }

        // Weekly aggregation for longer periods
        $weeklyData = [];
        foreach ($orders->list() as $order) {
            $weekKey = date('W-Y', strtotime($order->created_at));
            $weekLabel = 'Uge ' . date('W', strtotime($order->created_at));
            if (!isset($weeklyData[$weekKey])) {
                $weeklyData[$weekKey] = ['date' => $weekLabel, 'revenue' => 0, 'orders' => 0];
            }
            $weeklyData[$weekKey]['revenue'] += orderAmount($order);
            $weeklyData[$weekKey]['orders']++;
        }
        ksort($weeklyData);

        // Collection rate (completed vs total scheduled+completed)
        $totalScheduledAndCompleted = $paymentsByStatus['COMPLETED']['count'] + $paymentsByStatus['SCHEDULED']['count'] + $paymentsByStatus['PAST_DUE']['count'];
        $collectionRate = $totalScheduledAndCompleted > 0
            ? ($paymentsByStatus['COMPLETED']['count'] / $totalScheduledAndCompleted) * 100
            : 0;

        return Views("MERCHANT_REPORTS", compact(
            'locationOptions', 'startDate', 'endDate',
            'grossRevenue', 'netRevenue', 'totalFees', 'orderCount', 'orderAverage', 'customerCount',
            'bnplCount', 'fullPaymentCount', 'bnplRevenue', 'fullPaymentRevenue',
            'paymentsByStatus', 'revenueByLocation', 'dailyData', 'weeklyData', 'collectionRate'
        ));
    }

    #[NoReturn] public static function getTerminalQrBytes(array $args): void {
        $terminalId = $args["id"];
        $terminal = Methods::terminals()->get($terminalId);
        if(isEmpty($terminal)) Response()->jsonError("Invalid terminal", [], 404);
        if($terminal->status !== 'ACTIVE') Response()->jsonError("The terminal is not active", [], 403);
        if($terminal->location->status !== 'ACTIVE') Response()->jsonError("The location is not active", [], 403);

        $link = __url(Links::$merchant->terminals->checkoutStart($terminal->location->slug, $terminal->uid));
        $qrWithLogo = Methods::qr()->buildWithLogo($link);

        Response()->mimeType($qrWithLogo['image'], $qrWithLogo['mimeType']);
    }

    #[NoReturn] public static function getLocationQrBytes(array $args): void {
        $slug = $args["slug"];
        $location = Methods::locations()->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) Response()->jsonError("Invalid location", [], 404);
        if($location->status !== 'ACTIVE') Response()->jsonError("The location is not active", [], 403);

        $link = __url(Links::$merchant->public->getLocationPage($slug));
        $qrGenerator = Methods::qr()->build($link)->get();

        Response()->mimeType($qrGenerator->getString(), $qrGenerator->getMimeType());
    }

    public static function asignEditor(array $args): mixed {
        $designId = $args['id'] ?? null;

        // Get user's accessible locations with published pages
        $allLocations = Methods::locations()->getMyLocations(null, ['uid', 'name', 'slug']);
        $locations = $allLocations->filter(function($location) {
            $publishedPage = Methods::locationPages()->excludeForeignKeys()->getFirst([
                'location' => $location['uid'],
                'state' => 'PUBLISHED'
            ]);
            return !isEmpty($publishedPage);
        });

        $design = null;
        $isNew = true;

        if (!isEmpty($designId)) {
            // Loading existing design
            $handler = Methods::asignDesigns();
            $design = $handler->getWithAccess($designId);

            if (isEmpty($design)) {
                // Design not found or no access, redirect to new editor
                Response()->redirect(Links::$merchant->asignEditor);
            }

            // Fetch location if design has one
            if (!isEmpty($design->location)) {
                $design->location = Methods::locations()->get($design->location);
            }
            $isNew = false;
        }

        // Get type options
        $typeOptions = Methods::asignDesigns()->getTypeOptions();

        // Get inspiration images for sidebar
        $inspirationHandler = Methods::marketingInspiration();
        $designInspirations = $inspirationHandler->getActive('a_sign_design');
        $arbitraryInspirations = $inspirationHandler->getActive('a_sign_arbitrary');
        // Also get legacy a_sign category
        $legacyInspirations = $inspirationHandler->getActive('a_sign');

        return Views("MERCHANT_ASIGN_EDITOR", compact(
            'locations', 'design', 'isNew', 'typeOptions',
            'designInspirations', 'arbitraryInspirations', 'legacyInspirations'
        ));
    }

    public static function support(array $args): mixed {
        $ticketHandler = Methods::supportTickets();
        $replyHandler = Methods::supportTicketReplies();

        // Get user's tickets
        $tickets = $ticketHandler->getByUser(__uuid());

        // Get counts
        $openCount = $tickets->filter(fn($t) => $t['status'] === 'open')->count();
        $closedCount = $tickets->filter(fn($t) => $t['status'] === 'closed')->count();

        // Get replies for each ticket
        $ticketReplies = new \stdClass();
        foreach ($tickets->list() as $ticket) {
            $ticketUid = $ticket->uid;
            $ticketReplies->$ticketUid = $replyHandler->getByTicket($ticketUid);
        }

        // Merchant categories
        $categories = [
            'Betalinger & Afregning',
            'Terminaler & QR',
            'Team & Adgang',
            'Viva Wallet',
            'Teknisk problem',
            'Andet'
        ];

        return Views("MERCHANT_SUPPORT", compact('tickets', 'openCount', 'closedCount', 'categories', 'ticketReplies'));
    }

}