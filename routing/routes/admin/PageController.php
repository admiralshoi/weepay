<?php
namespace routing\routes\admin;

use classes\Methods;
use Database\model\Orders;
use Database\model\Organisations;
use Database\model\Users;
use Database\model\Locations;
use features\Settings;

/**
 * Admin Dashboard PageController
 * Handles all dashboard pages for admin users
 */
class PageController {

    // =====================================================
    // DASHBOARD PAGES
    // =====================================================

    public static function dashboard(array $args): mixed {
        $userHandler = Methods::users();
        $orderHandler = Methods::orders();

        // Time ranges
        $todayStart = strtotime('today midnight');
        $todayEnd = strtotime('tomorrow midnight') - 1;
        $last24Hours = time() - (24 * 60 * 60);
        $thisMonthStart = strtotime('first day of this month midnight');
        $lastMonthStart = strtotime('first day of last month midnight');
        $lastMonthEnd = strtotime('last day of last month 23:59:59');

        // KPI: Pending payments (for alerts card)
        $pendingPayments = Methods::payments()->queryBuilder()
            ->where('status', ['PENDING', 'SCHEDULED'])
            ->count();

        // KPI: Past due payments (for alerts card)
        $pastDuePayments = Methods::payments()->count(['status' => 'PAST_DUE']);

        // Chart data: Revenue from completed payments over last 30 days
        $chartData = [];
        for ($i = 29; $i >= 0; $i--) {
            $dayStart = strtotime("-$i days midnight");
            $dayEnd = strtotime("-$i days 23:59:59");
            $dayRevenue = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=')
                ->sum('amount') ?? 0;
            $dayPayments = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=')
                ->count();

            $chartData[] = [
                'date' => date('d/m', $dayStart),
                'revenue' => (float)$dayRevenue,
                'payments' => (int)$dayPayments
            ];
        }

        // User growth chart data
        $userGrowthData = [];
        for ($i = 29; $i >= 0; $i--) {
            $dayStart = strtotime("-$i days midnight");
            $dayEnd = strtotime("-$i days 23:59:59");
            $newConsumers = Methods::users()->queryBuilder()
                ->where('access_level', 1)
                ->whereTimeAfter('created_at', $dayStart, '>=')
                ->whereTimeBefore('created_at', $dayEnd, '<=')
                ->count();
            $newMerchants = Methods::users()->queryBuilder()
                ->where('access_level', 2)
                ->whereTimeAfter('created_at', $dayStart, '>=')
                ->whereTimeBefore('created_at', $dayEnd, '<=')
                ->count();

            $userGrowthData[] = [
                'date' => date('d/m', $dayStart),
                'consumers' => (int)$newConsumers,
                'merchants' => (int)$newMerchants
            ];
        }

        // KPIs for alerts card only (other KPIs loaded via JS API)
        $args['kpis'] = (object)[
            'pendingPayments' => $pendingPayments,
            'pastDuePayments' => $pastDuePayments,
        ];
        $args['chartData'] = $chartData;
        $args['userGrowthData'] = $userGrowthData;

        // System overview stats (all-time totals)
        $args['stats'] = (object)[
            'totalUsers' => Methods::users()->count(),
            'totalOrganisations' => Methods::organisations()->count(),
            'totalLocations' => Methods::locations()->count(),
            'totalOrders' => Methods::orders()->count(),
        ];

        return Views("ADMIN_DASHBOARD", $args);
    }

    public static function users(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $roleFilter = $args['GET']['role'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Date range for stats (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Build query
        $query = Users::queryBuilder()
            ->select(['uid', 'email', 'phone', 'full_name', 'access_level', 'deactivated', 'created_at']);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->whereLike('phone', $search)
                ->endGroup();
        }

        // Apply role filter
        if (!empty($roleFilter)) {
            $query->where('access_level', (int)$roleFilter);
        }

        // Apply status filter
        if ($statusFilter === 'active') {
            $query->where('deactivated', 0);
        } elseif ($statusFilter === 'deactivated') {
            $query->where('deactivated', 1);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $users = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Stats - Total counts (all time)
        $totalUsers = Users::count();
        $totalConsumers = Users::where('access_level', 1)->count();
        $totalMerchants = Users::where('access_level', 2)->count();
        $totalActive = Users::where('deactivated', 0)->count();
        $totalDeactivated = Users::where('deactivated', 1)->count();

        // Stats - New signups in date range
        $newSignups = Users::queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();
        $newConsumers = Users::queryBuilder()
            ->where('access_level', 1)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();
        $newMerchants = Users::queryBuilder()
            ->where('access_level', 2)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Daily signups for chart
        $dailySignups = [];
        $currentDate = new \DateTime($startDate);
        $endDateObj = new \DateTime($endDate);
        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayStart = strtotime($dateStr . ' 00:00:00');
            $dayEnd = strtotime($dateStr . ' 23:59:59');

            $dailySignups[] = [
                'date' => $currentDate->format('d/m'),
                'total' => Users::queryBuilder()
                    ->whereTimeAfter('created_at', $dayStart, '>=')
                    ->whereTimeBefore('created_at', $dayEnd, '<=')
                    ->count(),
                'consumers' => Users::queryBuilder()
                    ->where('access_level', 1)
                    ->whereTimeAfter('created_at', $dayStart, '>=')
                    ->whereTimeBefore('created_at', $dayEnd, '<=')
                    ->count(),
                'merchants' => Users::queryBuilder()
                    ->where('access_level', 2)
                    ->whereTimeAfter('created_at', $dayStart, '>=')
                    ->whereTimeBefore('created_at', $dayEnd, '<=')
                    ->count(),
            ];
            $currentDate->modify('+1 day');
        }

        $args['users'] = $users;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'role' => $roleFilter,
            'status' => $statusFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['stats'] = (object)[
            'totalUsers' => $totalUsers,
            'totalConsumers' => $totalConsumers,
            'totalMerchants' => $totalMerchants,
            'totalActive' => $totalActive,
            'totalDeactivated' => $totalDeactivated,
            'newSignups' => $newSignups,
            'newConsumers' => $newConsumers,
            'newMerchants' => $newMerchants,
        ];
        $args['dailySignups'] = $dailySignups;

        return Views("ADMIN_DASHBOARD_USERS", $args);
    }

    public static function userDetail(array $args): mixed {
        $userId = $args['id'] ?? '';
        if (empty($userId)) {
            return Views("404", $args);
        }

        $user = Methods::users()->get($userId);
        if (isEmpty($user)) {
            return Views("404", $args);
        }

        // Get user's organisations
        $organisationMemberships = Methods::organisationMembers()->getByX(['uuid' => $userId]);

        // Get user's orders
        $orders = Methods::orders()->queryBuilder()
            ->where('uuid', $userId)
            ->order('created_at', 'DESC')
            ->limit(10)
            ->all();

        // Get order statistics
        $totalOrders = Methods::orders()->count(['uuid' => $userId]);
        $totalSpent = Methods::orders()->queryBuilder()->where('uuid', $userId)->where('status', 'COMPLETED')->rawSelect('SUM(amount - amount_refunded) as total')->first()->total ?? 0;

        // Get top 3 locations for consumers (by order count)
        $topLocations = [];
        if ((int)$user->access_level === 1) { // Consumer
            $topLocationsRaw = Orders::queryBuilder()
                ->rawSelect('location, COUNT(*) as order_count, SUM(amount - amount_refunded) as total_amount')
                ->where('uuid', $userId)
                ->groupBy('location')
                ->order('order_count', 'DESC')
                ->limit(3)
                ->all();

            foreach ($topLocationsRaw->list() as $loc) {
                $location = Methods::locations()->get($loc->location);
                if ($location) {
                    $topLocations[] = (object)[
                        'location' => $location,
                        'orderCount' => $loc->order_count,
                        'totalAmount' => $loc->total_amount ?? 0
                    ];
                }
            }
        }

        $args['user'] = $user;
        $args['organisationMemberships'] = $organisationMemberships;
        $args['orders'] = $orders;
        $args['topLocations'] = $topLocations;
        $args['stats'] = (object)[
            'totalOrders' => $totalOrders,
            'totalSpent' => $totalSpent,
        ];

        return Views("ADMIN_DASHBOARD_USER_DETAIL", $args);
    }

    public static function consumers(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Date range for stats (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Build query - filter for consumers only (access_level = 1)
        $query = Users::queryBuilder()
            ->select(['uid', 'email', 'phone', 'full_name', 'access_level', 'deactivated', 'created_at'])
            ->where('access_level', 1);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->whereLike('phone', $search)
                ->endGroup();
        }

        // Apply status filter
        if ($statusFilter === 'active') {
            $query->where('deactivated', 0);
        } elseif ($statusFilter === 'deactivated') {
            $query->where('deactivated', 1);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $users = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Stats - Total counts (all time)
        $totalConsumers = Users::where('access_level', 1)->count();
        $totalActive = Users::where('access_level', 1)->where('deactivated', 0)->count();
        $totalDeactivated = Users::where('access_level', 1)->where('deactivated', 1)->count();

        // Stats - New signups in date range
        $newSignups = Users::queryBuilder()
            ->where('access_level', 1)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Daily signups for chart
        $dailySignups = [];
        $currentDate = new \DateTime($startDate);
        $endDateObj = new \DateTime($endDate);
        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayStart = strtotime($dateStr . ' 00:00:00');
            $dayEnd = strtotime($dateStr . ' 23:59:59');

            $dailySignups[] = [
                'date' => $currentDate->format('d/m'),
                'count' => Users::queryBuilder()
                    ->where('access_level', 1)
                    ->whereTimeAfter('created_at', $dayStart, '>=')
                    ->whereTimeBefore('created_at', $dayEnd, '<=')
                    ->count(),
            ];
            $currentDate->modify('+1 day');
        }

        $args['users'] = $users;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['stats'] = (object)[
            'totalConsumers' => $totalConsumers,
            'totalActive' => $totalActive,
            'totalDeactivated' => $totalDeactivated,
            'newSignups' => $newSignups,
        ];
        $args['dailySignups'] = $dailySignups;

        return Views("ADMIN_DASHBOARD_CONSUMERS", $args);
    }

    public static function merchants(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Date range for stats (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Build query - filter for merchants only (access_level = 2)
        $query = Users::queryBuilder()
            ->select(['uid', 'email', 'phone', 'full_name', 'access_level', 'deactivated', 'created_at'])
            ->where('access_level', 2);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->whereLike('phone', $search)
                ->endGroup();
        }

        // Apply status filter
        if ($statusFilter === 'active') {
            $query->where('deactivated', 0);
        } elseif ($statusFilter === 'deactivated') {
            $query->where('deactivated', 1);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $users = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Stats - Total counts (all time)
        $totalMerchants = Users::where('access_level', 2)->count();
        $totalActive = Users::where('access_level', 2)->where('deactivated', 0)->count();
        $totalDeactivated = Users::where('access_level', 2)->where('deactivated', 1)->count();

        // Stats - New signups in date range
        $newSignups = Users::queryBuilder()
            ->where('access_level', 2)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Daily signups for chart
        $dailySignups = [];
        $currentDate = new \DateTime($startDate);
        $endDateObj = new \DateTime($endDate);
        while ($currentDate <= $endDateObj) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayStart = strtotime($dateStr . ' 00:00:00');
            $dayEnd = strtotime($dateStr . ' 23:59:59');

            $dailySignups[] = [
                'date' => $currentDate->format('d/m'),
                'count' => Users::queryBuilder()
                    ->where('access_level', 2)
                    ->whereTimeAfter('created_at', $dayStart, '>=')
                    ->whereTimeBefore('created_at', $dayEnd, '<=')
                    ->count(),
            ];
            $currentDate->modify('+1 day');
        }

        $args['users'] = $users;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['stats'] = (object)[
            'totalMerchants' => $totalMerchants,
            'totalActive' => $totalActive,
            'totalDeactivated' => $totalDeactivated,
            'newSignups' => $newSignups,
        ];
        $args['dailySignups'] = $dailySignups;

        return Views("ADMIN_DASHBOARD_MERCHANTS", $args);
    }

    public static function organisations(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Build query
        $query = Organisations::queryBuilder()
            ->select(['uid', 'name', 'primary_email', 'cvr', 'company_name', 'status', 'created_at']);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('primary_email', $search)
                ->whereLike('cvr', $search)
                ->whereLike('company_name', $search)
                ->endGroup();
        }

        // Apply status filter
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $organisations = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $args['organisations'] = $organisations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_ORGANISATIONS", $args);
    }

    public static function organisationDetail(array $args): mixed {
        $orgId = $args['id'] ?? '';
        if (empty($orgId)) {
            return Views("404", $args);
        }

        $organisation = Methods::organisations()->get($orgId);
        if (isEmpty($organisation)) {
            return Views("404", $args);
        }

        // Date range (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Get organisation members
        $members = Methods::organisationMembers()->getByX(['organisation' => $orgId]);

        // Get organisation locations
        $locations = Methods::locations()->excludeForeignKeys()->getByX(['uuid' => $orgId]);

        // Order-based KPIs (within date range)
        $orderQuery = Methods::orders()->queryBuilder()
            ->where('organisation', $orgId)
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        $orderRevenue = (clone $orderQuery)->rawSelect('SUM(amount - amount_refunded) as total')->first()->total ?? 0;
        $orderIsv = (clone $orderQuery)->sum('fee_amount') ?? 0;
        $totalOrders = (clone $orderQuery)->count();

        // Payment-based KPIs (within date range)
        $paymentQuery = Methods::payments()->queryBuilder()
            ->where('organisation', $orgId)
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=');

        $paymentRevenue = (clone $paymentQuery)->sum('amount') ?? 0;
        $paymentIsv = (clone $paymentQuery)->sum('isv_amount') ?? 0;

        $args['organisation'] = $organisation;
        $args['members'] = $members;
        $args['locations'] = $locations;
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['stats'] = (object)[
            'totalMembers' => $members->count(),
            'totalLocations' => $locations->count(),
            'orderRevenue' => $orderRevenue,
            'orderIsv' => $orderIsv,
            'paymentRevenue' => $paymentRevenue,
            'paymentIsv' => $paymentIsv,
            'totalOrders' => $totalOrders,
        ];

        return Views("ADMIN_DASHBOARD_ORGANISATION_DETAIL", $args);
    }

    public static function locations(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $orgFilter = $args['GET']['org'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Build query
        $query = Locations::queryBuilder()
            ->select(['uid', 'uuid', 'name', 'slug', 'status', 'contact_email', 'address', 'created_at']);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('slug', $search)
                ->whereLike('contact_email', $search)
                ->endGroup();
        }

        // Apply status filter
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('uuid', $orgFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $locations = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Get organisation names for filter dropdown
        $organisations = Organisations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        $args['locations'] = $locations;
        $args['organisations'] = $organisations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'org' => $orgFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_LOCATIONS", $args);
    }

    public static function locationDetail(array $args): mixed {
        $locId = $args['id'] ?? '';
        if (empty($locId)) {
            return Views("404", $args);
        }

        $location = Methods::locations()->get($locId);
        if (isEmpty($location)) {
            return Views("404", $args);
        }

        // Date range (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');

        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Get location members
        $members = Methods::locationMembers()->getByX(['location' => $locId]);

        // Order-based KPIs (within date range)
        $orderQuery = Methods::orders()->queryBuilder()
            ->where('location', $locId)
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        $orderRevenue = (clone $orderQuery)->rawSelect('SUM(amount - amount_refunded) as total')->first()->total ?? 0;
        $orderIsv = (clone $orderQuery)->sum('fee_amount') ?? 0;
        $totalOrders = (clone $orderQuery)->count();

        // Payment-based KPIs (within date range)
        $paymentQuery = Methods::payments()->queryBuilder()
            ->where('location', $locId)
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=');

        $paymentRevenue = (clone $paymentQuery)->sum('amount') ?? 0;
        $paymentIsv = (clone $paymentQuery)->sum('isv_amount') ?? 0;

        $args['location'] = $location;
        $args['members'] = $members;
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['stats'] = (object)[
            'totalMembers' => $members->count(),
            'orderRevenue' => $orderRevenue,
            'orderIsv' => $orderIsv,
            'paymentRevenue' => $paymentRevenue,
            'paymentIsv' => $paymentIsv,
            'totalOrders' => $totalOrders,
        ];

        return Views("ADMIN_DASHBOARD_LOCATION_DETAIL", $args);
    }

    public static function orders(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $orgFilter = $args['GET']['org'] ?? '';
        $locFilter = $args['GET']['loc'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Build query
        $query = Orders::queryBuilder()
            ->select(['uid', 'uuid', 'location', 'organisation', 'provider', 'status', 'amount', 'currency', 'caption', 'created_at']);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('caption', $search)
                ->whereLike('prid', $search)
                ->endGroup();
        }

        // Apply status filter
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        // Apply location filter
        if (!empty($locFilter)) {
            $query->where('location', $locFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $orders = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Get organisations for filter dropdown
        $organisations = Organisations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        // Get locations for filter dropdown
        $locations = Locations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        $args['orders'] = $orders;
        $args['organisations'] = $organisations;
        $args['locations'] = $locations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'org' => $orgFilter,
            'loc' => $locFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_ORDERS", $args);
    }

    public static function orderDetail(array $args): mixed {
        $orderId = $args['id'] ?? '';
        if (empty($orderId)) {
            return Views("404", $args);
        }

        $order = Methods::orders()->get($orderId);
        if (isEmpty($order)) {
            return Views("404", $args);
        }

        // Ensure organisation is resolved
        if (!is_object($order->organisation) && !empty($order->organisation)) {
            $order->organisation = Methods::organisations()->get($order->organisation);
        }

        // Get order payments
        $payments = \Database\model\Payments::where('order', $orderId)
            ->order('installment_number', 'ASC')
            ->all();

        // Calculate payment stats
        $completedPayments = 0;
        $pendingPayments = 0;
        $pastDuePayments = 0;
        $totalPaid = 0;

        foreach ($payments->list() as $payment) {
            if ($payment->status === 'COMPLETED') {
                $completedPayments++;
                $totalPaid += $payment->amount;
            } elseif ($payment->status === 'PAST_DUE') {
                $pastDuePayments++;
            } elseif (in_array($payment->status, ['PENDING', 'SCHEDULED'])) {
                $pendingPayments++;
            }
        }

        $args['order'] = $order;
        $args['payments'] = $payments;
        $args['stats'] = (object)[
            'totalPayments' => $payments->count(),
            'completedPayments' => $completedPayments,
            'pendingPayments' => $pendingPayments,
            'pastDuePayments' => $pastDuePayments,
            'totalPaid' => $totalPaid,
        ];

        return Views("ADMIN_DASHBOARD_ORDER_DETAIL", $args);
    }

    public static function payments(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $statusFilter = $args['GET']['status'] ?? '';
        $orgFilter = $args['GET']['org'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'created_at';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'DESC');

        // Build query
        $query = \Database\model\Payments::queryBuilder()
            ->select(['uid', 'order', 'uuid', 'organisation', 'location', 'amount', 'currency', 'installment_number', 'due_date', 'paid_at', 'status', 'created_at']);

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('prid', $search)
                ->endGroup();
        }

        // Apply status filter
        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $payments = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Get organisations for filter dropdown
        $organisations = Organisations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        $args['payments'] = $payments;
        $args['organisations'] = $organisations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'status' => $statusFilter,
            'org' => $orgFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_PAYMENTS", $args);
    }

    public static function paymentDetail(array $args): mixed {
        $paymentId = $args['id'] ?? '';
        if (empty($paymentId)) {
            return Views("404", $args);
        }

        $payment = Methods::payments()->get($paymentId);
        if (isEmpty($payment)) {
            return Views("404", $args);
        }

        // Get related order
        $order = null;
        $orderUid = is_object($payment->order) ? $payment->order->uid : $payment->order;
        if (!isEmpty($orderUid)) {
            $order = is_object($payment->order) ? $payment->order : Methods::orders()->get($orderUid);
        }

        // Get user info
        $user = null;
        $userUid = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
        if (!isEmpty($userUid)) {
            $user = is_object($payment->uuid) ? $payment->uuid : Methods::users()->get($userUid);
        }

        // Get organisation info
        $organisation = null;
        $orgUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
        if (!isEmpty($orgUid)) {
            $organisation = is_object($payment->organisation) ? $payment->organisation : Methods::organisations()->get($orgUid);
        }

        // Get location info
        $location = null;
        $locUid = is_object($payment->location) ? $payment->location->uid : $payment->location;
        if (!isEmpty($locUid)) {
            $location = is_object($payment->location) ? $payment->location : Methods::locations()->get($locUid);
        }

        // Get provider info
        $provider = null;
        $providerUid = is_object($payment->provider) ? $payment->provider->uid : $payment->provider;
        if (!isEmpty($providerUid)) {
            $provider = is_object($payment->provider) ? $payment->provider : Methods::paymentProviders()->get($providerUid);
        }

        // Get all payments for this order (siblings)
        $allPayments = new \Database\Collection();
        if (!isEmpty($orderUid)) {
            $allPayments = Methods::payments()->getByXOrderBy('installment_number', 'ASC', ['order' => $orderUid]);
        }

        $args['payment'] = $payment;
        $args['order'] = $order;
        $args['user'] = $user;
        $args['organisation'] = $organisation;
        $args['location'] = $location;
        $args['provider'] = $provider;
        $args['allPayments'] = $allPayments;

        return Views("ADMIN_DASHBOARD_PAYMENT_DETAIL", $args);
    }

    public static function paymentsPending(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $orgFilter = $args['GET']['org'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'due_date';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'ASC');

        // Build query - only PENDING and SCHEDULED
        $query = \Database\model\Payments::queryBuilder()
            ->select(['uid', 'order', 'uuid', 'organisation', 'location', 'amount', 'currency', 'installment_number', 'due_date', 'status', 'created_at'])
            ->startGroup('OR')
            ->where('status', 'PENDING')
            ->where('status', 'SCHEDULED')
            ->endGroup();

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('prid', $search)
                ->endGroup();
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $payments = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Get organisations for filter dropdown
        $organisations = Organisations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        $args['payments'] = $payments;
        $args['organisations'] = $organisations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'org' => $orgFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_PAYMENTS_PENDING", $args);
    }

    public static function paymentsPastDue(array $args): mixed {
        $page = (int)($args['GET']['page'] ?? 1);
        $perPage = (int)($args['GET']['per_page'] ?? 25);
        $search = $args['GET']['search'] ?? '';
        $orgFilter = $args['GET']['org'] ?? '';
        $sortColumn = $args['GET']['sort'] ?? 'due_date';
        $sortDirection = strtoupper($args['GET']['dir'] ?? 'ASC');

        // Build query - only PAST_DUE
        $query = \Database\model\Payments::queryBuilder()
            ->select(['uid', 'order', 'uuid', 'organisation', 'location', 'amount', 'currency', 'installment_number', 'due_date', 'status', 'created_at'])
            ->where('status', 'PAST_DUE');

        // Apply search filter
        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('prid', $search)
                ->endGroup();
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $payments = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        // Get organisations for filter dropdown
        $organisations = Organisations::queryBuilder()
            ->select(['uid', 'name'])
            ->order('name', 'ASC')
            ->all();

        $args['payments'] = $payments;
        $args['organisations'] = $organisations;
        $args['pagination'] = (object)[
            'page' => $page,
            'perPage' => $perPage,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
        ];
        $args['filters'] = (object)[
            'search' => $search,
            'org' => $orgFilter,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return Views("ADMIN_DASHBOARD_PAYMENTS_PAST_DUE", $args);
    }

    public static function kpi(array $args): mixed {
        // Date range (default last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Debug logging
        debugLog([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startTimestamp' => $startTimestamp,
            'endTimestamp' => $endTimestamp,
            'startFormatted' => date('Y-m-d H:i:s', $startTimestamp),
            'endFormatted' => date('Y-m-d H:i:s', $endTimestamp),
            'args_start' => $args['start'] ?? 'not set',
            'args_end' => $args['end'] ?? 'not set'
        ], 'KPI_DATES');

        // ==========================================
        // REVENUE & PAYMENT KPIs
        // ==========================================

        // Total revenue (completed payments)
        $paymentQuery = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereColumnIsNotNull('paid_at')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=');

        $totalRevenue = (clone $paymentQuery)->sum('amount') ?? 0;
        $totalIsv = (clone $paymentQuery)->sum('isv_amount') ?? 0;
        $completedPaymentsCount = (clone $paymentQuery)->count();

        // Order revenue
        $orderQuery = Methods::orders()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        $totalOrderRevenue = (clone $orderQuery)->rawSelect('SUM(amount - amount_refunded) as total')->first()->total ?? 0;
        $totalOrderIsv = (clone $orderQuery)->sum('fee_amount') ?? 0;
        $totalOrdersCount = (clone $orderQuery)->count();
        $completedOrdersCount = (clone $orderQuery)->where('status', 'COMPLETED')->count();
        $pendingOrdersCount = Methods::orders()->queryBuilder()
            ->where('status', 'PENDING')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Average order value
        $avgOrderValue = $totalOrdersCount > 0 ? $totalOrderRevenue / $totalOrdersCount : 0;

        debugLog([
            'totalOrdersCount' => $totalOrdersCount,
            'completedOrdersCount' => $completedOrdersCount,
            'totalOrderRevenue' => $totalOrderRevenue,
        ], 'KPI_ORDERS');

        // Pending and past due payments
        $pendingPaymentsCount = Methods::payments()->queryBuilder()
            ->startGroup('OR')
            ->where('status', 'PENDING')
            ->where('status', 'SCHEDULED')
            ->endGroup()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $pendingPaymentsAmount = Methods::payments()->queryBuilder()
            ->startGroup('OR')
            ->where('status', 'PENDING')
            ->where('status', 'SCHEDULED')
            ->endGroup()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->sum('amount') ?? 0;

        $pastDuePaymentsCount = Methods::payments()->queryBuilder()
            ->where('status', 'PAST_DUE')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $pastDuePaymentsAmount = Methods::payments()->queryBuilder()
            ->where('status', 'PAST_DUE')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->sum('amount') ?? 0;

        // Failed payments
        $failedPaymentsCount = Methods::payments()->queryBuilder()
            ->where('status', 'FAILED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Collection rate (completed / total payments in period)
        $totalPaymentsInPeriod = Methods::payments()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();
        $collectionRate = $totalPaymentsInPeriod > 0 ? ($completedPaymentsCount / $totalPaymentsInPeriod) * 100 : 0;

        // ==========================================
        // USER KPIs
        // ==========================================

        $newUsersCount = Methods::users()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $newConsumersCount = Methods::users()->queryBuilder()
            ->where('access_level', 1)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $newMerchantsCount = Methods::users()->queryBuilder()
            ->where('access_level', 2)
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        debugLog([
            'newUsersCount' => $newUsersCount,
            'newConsumersCount' => $newConsumersCount,
            'newMerchantsCount' => $newMerchantsCount,
        ], 'KPI_USERS');

        // Active users (users who made a payment in period)
        $activeUsersPayments = Methods::payments()->queryGetAll(
            Methods::payments()->queryBuilder()
                ->select(['uuid'])
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $startTimestamp, '>=')
                ->whereTimeBefore('paid_at', $endTimestamp, '<=')
        );
        $activeUserIds = [];
        foreach ($activeUsersPayments->list() as $p) {
            $userId = is_object($p->uuid) ? $p->uuid->uid : $p->uuid;
            if (!isEmpty($userId)) $activeUserIds[$userId] = true;
        }
        $activeUsersCount = count($activeUserIds);

        // Total users (all time)
        $totalUsersCount = Methods::users()->count();
        $totalConsumersCount = Methods::users()->count(['access_level' => 1]);
        $totalMerchantsCount = Methods::users()->count(['access_level' => 2]);

        // ==========================================
        // ORGANISATION & LOCATION KPIs
        // ==========================================

        $newOrganisationsCount = Methods::organisations()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $newLocationsCount = Methods::locations()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        $totalOrganisationsCount = Methods::organisations()->count();
        $totalLocationsCount = Methods::locations()->count();

        // ==========================================
        // TOP ORGANISATIONS (by orders placed in period)
        // ==========================================
        $periodOrders = Methods::orders()->queryGetAll(
            Methods::orders()->queryBuilder()
                ->whereTimeAfter('created_at', $startTimestamp, '>=')
                ->whereTimeBefore('created_at', $endTimestamp, '<=')
        );

        $ordersByOrg = [];
        $orgCache = [];
        foreach ($periodOrders->list() as $order) {
            $orgId = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
            if (isEmpty($orgId)) continue;

            $orgName = is_object($order->organisation) && !isEmpty($order->organisation->name)
                ? $order->organisation->name
                : null;

            if (!$orgName) {
                if (!isset($orgCache[$orgId])) {
                    $org = Methods::organisations()->get($orgId);
                    $orgCache[$orgId] = $org ? $org->name : 'Ukendt';
                }
                $orgName = $orgCache[$orgId];
            }

            if (!isset($ordersByOrg[$orgId])) {
                $ordersByOrg[$orgId] = ['uid' => $orgId, 'name' => $orgName, 'revenue' => 0, 'orders' => 0];
            }
            $ordersByOrg[$orgId]['revenue'] += orderAmount($order);
            $ordersByOrg[$orgId]['orders']++;
        }
        usort($ordersByOrg, fn($a, $b) => $b['orders'] <=> $a['orders']);
        $topOrganisations = array_slice(array_values($ordersByOrg), 0, 5);

        // ==========================================
        // TOP LOCATIONS (by orders placed in period)
        // ==========================================
        $ordersByLoc = [];
        $locCache = [];
        foreach ($periodOrders->list() as $order) {
            $locId = is_object($order->location) ? $order->location->uid : $order->location;
            if (isEmpty($locId)) continue;

            $locName = is_object($order->location) && !isEmpty($order->location->name)
                ? $order->location->name
                : null;

            if (!$locName) {
                if (!isset($locCache[$locId])) {
                    $loc = Methods::locations()->get($locId);
                    $locCache[$locId] = $loc ? $loc->name : 'Ukendt';
                }
                $locName = $locCache[$locId];
            }

            if (!isset($ordersByLoc[$locId])) {
                $ordersByLoc[$locId] = ['uid' => $locId, 'name' => $locName, 'revenue' => 0, 'orders' => 0];
            }
            $ordersByLoc[$locId]['revenue'] += orderAmount($order);
            $ordersByLoc[$locId]['orders']++;
        }
        usort($ordersByLoc, fn($a, $b) => $b['orders'] <=> $a['orders']);
        $topLocations = array_slice(array_values($ordersByLoc), 0, 5);

        // ==========================================
        // TOP CUSTOMERS (by orders placed in period)
        // ==========================================
        $ordersByCustomer = [];
        $userCache = [];
        foreach ($periodOrders->list() as $order) {
            $userId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
            if (isEmpty($userId)) continue;

            $userName = is_object($order->uuid) && !isEmpty($order->uuid->full_name)
                ? $order->uuid->full_name
                : null;

            if (!$userName) {
                if (!isset($userCache[$userId])) {
                    $user = Methods::users()->get($userId);
                    $userCache[$userId] = $user ? ($user->full_name ?? $user->email ?? 'Ukendt') : 'Ukendt';
                }
                $userName = $userCache[$userId];
            }

            if (!isset($ordersByCustomer[$userId])) {
                $ordersByCustomer[$userId] = ['uid' => $userId, 'name' => $userName, 'spent' => 0, 'orders' => 0];
            }
            $ordersByCustomer[$userId]['spent'] += orderAmount($order);
            $ordersByCustomer[$userId]['orders']++;
        }
        usort($ordersByCustomer, fn($a, $b) => $b['orders'] <=> $a['orders']);
        $topCustomers = array_slice(array_values($ordersByCustomer), 0, 5);

        // ==========================================
        // COMPILE ALL KPIs
        // ==========================================
        $args['kpis'] = (object)[
            // Revenue KPIs
            'totalRevenue' => $totalRevenue,
            'totalIsv' => $totalIsv,
            'totalOrderRevenue' => $totalOrderRevenue,
            'totalOrderIsv' => $totalOrderIsv,
            'avgOrderValue' => $avgOrderValue,

            // Payment KPIs
            'completedPaymentsCount' => $completedPaymentsCount,
            'pendingPaymentsCount' => $pendingPaymentsCount,
            'pendingPaymentsAmount' => $pendingPaymentsAmount,
            'pastDuePaymentsCount' => $pastDuePaymentsCount,
            'pastDuePaymentsAmount' => $pastDuePaymentsAmount,
            'failedPaymentsCount' => $failedPaymentsCount,
            'collectionRate' => $collectionRate,

            // Order KPIs
            'totalOrdersCount' => $totalOrdersCount,
            'completedOrdersCount' => $completedOrdersCount,
            'pendingOrdersCount' => $pendingOrdersCount,

            // User KPIs
            'newUsersCount' => $newUsersCount,
            'newConsumersCount' => $newConsumersCount,
            'newMerchantsCount' => $newMerchantsCount,
            'activeUsersCount' => $activeUsersCount,
            'totalUsersCount' => $totalUsersCount,
            'totalConsumersCount' => $totalConsumersCount,
            'totalMerchantsCount' => $totalMerchantsCount,

            // Organisation & Location KPIs
            'newOrganisationsCount' => $newOrganisationsCount,
            'newLocationsCount' => $newLocationsCount,
            'totalOrganisationsCount' => $totalOrganisationsCount,
            'totalLocationsCount' => $totalLocationsCount,
        ];

        $args['topOrganisations'] = $topOrganisations;
        $args['topLocations'] = $topLocations;
        $args['topCustomers'] = $topCustomers;
        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;

        return Views("ADMIN_DASHBOARD_KPI", $args);
    }

    public static function reports(array $args): mixed {
        // Date range from query params (default: last 30 days)
        $startDate = $args['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $args['end'] ?? date('Y-m-d');
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Get filter options (treat 'all' as no filter)
        $organisationId = (!empty($args['organisation']) && $args['organisation'] !== 'all') ? $args['organisation'] : null;
        $locationId = (!empty($args['location']) && $args['location'] !== 'all') ? $args['location'] : null;

        // Build base queries
        $paymentQuery = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=');

        $orderQuery = Methods::orders()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');

        if ($organisationId) {
            $paymentQuery->where('organisation', $organisationId);
            $orderQuery->where('organisation', $organisationId);
        }
        if ($locationId) {
            $paymentQuery->where('location', $locationId);
            $orderQuery->where('location', $locationId);
        }

        // KPIs from payments (for admin: revenue = total amount, net sales = ISV)
        $grossRevenue = (clone $paymentQuery)->sum('amount') ?? 0;
        $isvAmount = (clone $paymentQuery)->sum('isv_amount') ?? 0;
        $paymentCount = (clone $paymentQuery)->count();
        $orderCount = (clone $orderQuery)->count();
        $orderAverage = $orderCount > 0 ? $grossRevenue / $orderCount : 0;

        // Order-specific KPIs (total order amounts and ISV from orders table)
        $orderRevenue = (clone $orderQuery)->rawSelect('SUM(amount - amount_refunded) as total')->first()->total ?? 0;
        $orderIsv = (clone $orderQuery)->sum('fee_amount') ?? 0;

        // Get unique customers
        $payments = Methods::payments()->queryGetAll($paymentQuery->select(['uuid']));
        $customerIds = [];
        foreach ($payments->list() as $p) {
            $customerId = is_object($p->uuid) ? $p->uuid->uid : $p->uuid;
            if ($customerId) $customerIds[$customerId] = true;
        }
        $customerCount = count($customerIds);

        // Calculate collection rate
        $allPaymentsQuery = Methods::payments()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=');
        if ($organisationId) $allPaymentsQuery->where('organisation', $organisationId);
        if ($locationId) $allPaymentsQuery->where('location', $locationId);

        $totalPaymentsAmount = (clone $allPaymentsQuery)->sum('amount') ?? 0;
        $completedPaymentsAmount = (clone $allPaymentsQuery)->where('status', 'COMPLETED')->sum('amount') ?? 0;
        $collectionRate = $totalPaymentsAmount > 0 ? ($completedPaymentsAmount / $totalPaymentsAmount) * 100 : 0;

        // Daily data for charts
        $dailyData = [];
        $currentDate = strtotime($startDate);
        $endDateTs = strtotime($endDate);

        while ($currentDate <= $endDateTs) {
            $dayStart = strtotime(date('Y-m-d', $currentDate) . ' 00:00:00');
            $dayEnd = strtotime(date('Y-m-d', $currentDate) . ' 23:59:59');

            $dayPaymentQuery = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=');

            if ($organisationId) $dayPaymentQuery->where('organisation', $organisationId);
            if ($locationId) $dayPaymentQuery->where('location', $locationId);

            $dayRevenue = (clone $dayPaymentQuery)->sum('amount') ?? 0;
            $dayPayments = (clone $dayPaymentQuery)->count();

            $dailyData[] = (object)[
                'date' => date('d/m', $currentDate),
                'revenue' => (float)$dayRevenue,
                'payments' => (int)$dayPayments
            ];

            $currentDate = strtotime('+1 day', $currentDate);
        }

        // Payment status breakdown
        $paymentsByStatus = [];
        $statuses = ['COMPLETED', 'SCHEDULED', 'PAST_DUE', 'PENDING', 'FAILED'];
        foreach ($statuses as $status) {
            $statusQuery = Methods::payments()->queryBuilder()
                ->where('status', $status)
                ->whereTimeAfter('created_at', $startTimestamp, '>=')
                ->whereTimeBefore('created_at', $endTimestamp, '<=');
            if ($organisationId) $statusQuery->where('organisation', $organisationId);
            if ($locationId) $statusQuery->where('location', $locationId);

            $paymentsByStatus[$status] = [
                'count' => (clone $statusQuery)->count(),
                'amount' => (clone $statusQuery)->sum('amount') ?? 0
            ];
        }

        // Revenue by organisation (top 10)
        $orgPayments = Methods::payments()->queryGetAll(
            Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $startTimestamp, '>=')
                ->whereTimeBefore('paid_at', $endTimestamp, '<=')
        );

        $revenueByOrg = [];
        $organisationCache = []; // Cache organisation names to avoid repeated lookups
        foreach ($orgPayments->list() as $payment) {
            $orgId = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;

            // Skip if no organisation
            if (empty($orgId)) continue;

            // Get organisation name
            if (is_object($payment->organisation) && !empty($payment->organisation->name)) {
                $orgName = $payment->organisation->name;
            } else {
                // Organisation wasn't resolved as object or name is empty, fetch from cache or DB
                if (!isset($organisationCache[$orgId])) {
                    $org = Methods::organisations()->get($orgId);
                    $organisationCache[$orgId] = $org ? $org->name : 'Ukendt';
                }
                $orgName = $organisationCache[$orgId];
            }

            if (!isset($revenueByOrg[$orgId])) {
                $revenueByOrg[$orgId] = (object)['uid' => $orgId, 'name' => $orgName, 'revenue' => 0, 'isv' => 0, 'payments' => 0];
            }
            $revenueByOrg[$orgId]->revenue += $payment->amount;
            $revenueByOrg[$orgId]->isv += $payment->isv_amount ?? 0;
            $revenueByOrg[$orgId]->payments++;
        }
        usort($revenueByOrg, fn($a, $b) => $b->revenue <=> $a->revenue);
        $revenueByOrg = array_slice(array_values($revenueByOrg), 0, 10);

        // Revenue by location (top 10)
        $revenueByLocation = [];
        $locationCache = []; // Cache location names to avoid repeated lookups
        foreach ($orgPayments->list() as $payment) {
            $locId = is_object($payment->location) ? $payment->location->uid : $payment->location;

            // Skip if no location
            if (empty($locId)) continue;

            // Get location name
            if (is_object($payment->location) && !empty($payment->location->name)) {
                $locName = $payment->location->name;
            } else {
                // Location wasn't resolved as object or name is empty, fetch from cache or DB
                if (!isset($locationCache[$locId])) {
                    $loc = Methods::locations()->get($locId);
                    $locationCache[$locId] = $loc ? $loc->name : 'Ukendt';
                }
                $locName = $locationCache[$locId];
            }

            if (!isset($revenueByLocation[$locId])) {
                $revenueByLocation[$locId] = (object)['uid' => $locId, 'name' => $locName, 'revenue' => 0, 'isv' => 0, 'payments' => 0];
            }
            $revenueByLocation[$locId]->revenue += $payment->amount;
            $revenueByLocation[$locId]->isv += $payment->isv_amount ?? 0;
            $revenueByLocation[$locId]->payments++;
        }
        usort($revenueByLocation, fn($a, $b) => $b->revenue <=> $a->revenue);
        $revenueByLocation = array_slice(array_values($revenueByLocation), 0, 10);

        // Get organisation options for filter
        $organisations = Methods::organisations()->getByX(['status' => 'ACTIVE'], ['uid', 'name']);
        $organisationOptions = [];
        foreach ($organisations->list() as $org) {
            $organisationOptions[] = (object)['uid' => $org->uid, 'name' => $org->name];
        }

        // Get location options for filter
        $locations = Methods::locations()->getByX([], ['uid', 'name']);
        $locationOptions = [];
        foreach ($locations->list() as $loc) {
            $locationOptions[] = (object)['uid' => $loc->uid, 'name' => $loc->name];
        }

        $args['startDate'] = $startDate;
        $args['endDate'] = $endDate;
        $args['queryStart'] = $args['start'] ?? '';
        $args['queryEnd'] = $args['end'] ?? '';
        $args['grossRevenue'] = $grossRevenue;
        $args['isvAmount'] = $isvAmount;
        $args['netRevenue'] = $grossRevenue - $isvAmount; // For merchants this is net, for admin ISV is net
        $args['orderRevenue'] = $orderRevenue;
        $args['orderIsv'] = $orderIsv;
        $args['paymentCount'] = $paymentCount;
        $args['orderCount'] = $orderCount;
        $args['orderAverage'] = $orderAverage;
        $args['customerCount'] = $customerCount;
        $args['collectionRate'] = $collectionRate;
        $args['dailyData'] = $dailyData;
        $args['paymentsByStatus'] = (object)$paymentsByStatus;
        $args['revenueByOrg'] = $revenueByOrg;
        $args['revenueByLocation'] = $revenueByLocation;
        $args['organisationOptions'] = $organisationOptions;
        $args['locationOptions'] = $locationOptions;
        $args['selectedOrganisation'] = $organisationId;
        $args['selectedLocation'] = $locationId;

        return Views("ADMIN_DASHBOARD_REPORTS", $args);
    }

    public static function support(array $args): mixed {
        // Placeholder for support ticket system
        // In a real implementation, this would fetch from a SupportTickets model
        $args['stats'] = (object)[
            'openTickets' => 0,
            'pendingTickets' => 0,
            'resolvedTickets' => 0,
            'totalTickets' => 0,
        ];
        $args['tickets'] = new \Database\Collection();

        return Views("ADMIN_DASHBOARD_SUPPORT", $args);
    }

}
