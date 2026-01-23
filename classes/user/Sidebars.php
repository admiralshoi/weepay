<?php
namespace classes\user;
use classes\enumerations\Links;
use classes\lang\Translate;
use classes\Methods;
use features\Settings;


class Sidebars {


    public static function sideBarMenuAccess(): array {
        $bars = self::sideBarAccess();
        if(empty($bars)) return $bars;

        $filter = array_filter($bars,function ($bar) {
            if(empty($bar["access_level"])) return true;
            return in_array(__accessLevel(),$bar["access_level"]);
        });


        $response = array();
        foreach ($filter as $name => $value) $response[$name] = $value;
        return $response;
    }

    public static function sideBarAccess(): array {
        $loggedIn = (int)loggedIn();
        $menus = array(
            0 => array(), //Not logged in
            1 => array( // Logged in
                "admin" => array(
                    "access_level" => [8,9],
                    "pathName" => "admin",
                    "show_title" => false
                ),
                "system_admin" => array(
                    "access_level" => [9],
                    "pathName" => "developer",
                    "show_title" => false
                ),
                "consumer" => array(
                    "access_level" => [1],
                    "pathName" => "consumer",
                    "show_title" => false
                ),
                "merchant" => array(
                    "access_level" => [2],
                    "pathName" => "merchant",
                    "show_title" => false
                ),
            ),
            2 => [
                "admin-dashboard" => [
                    "access_level" => [8,9],
                    "pathName" => "admin-dashboard",
                    "show_title" => false
                ]
            ],
            3 => [
                "merchant-dashboard" => [
                    "access_level" => [2,8,9],
                    "pathName" => "merchant-dashboard",
                    "show_title" => false
                ]
            ]
        );

        if(Settings::$viewingAdminDashboard) return $menus[2];
        if(Settings::$viewingOrganisationDashboard) return $menus[3];
        return array_key_exists($loggedIn,$menus) ? $menus[$loggedIn] : array();
    }


    /**
     * Check if user has permission to see a sidebar item based on organisation permissions
     */
    private static function hasOrganisationPermission(string $item): bool {
        // If not in organisation context, allow all
        if(isEmpty(Settings::$organisation)) return true;

        // Map sidebar items to permission checks
        $permissionMap = [
            'locations' => ['locations', 'locations'],
            'terminals' => ['locations', 'terminals'],
            'team' => ['team', 'members'],
            'organisation' => null, // Special handling below
            'orders' => ['orders', 'payments'],
            'payments' => ['orders', 'payments'],
            'pending-payments' => ['orders', 'payments'],
            'past-due-payments' => ['orders', 'payments'],
            'customers' => ['orders', 'customers'],
            'reports' => ['organisation', 'reports'],
            'materials' => ['organisation', 'advertisement'],
        ];

        // Always show these items
        if(!array_key_exists($item, $permissionMap)) return true;

        // Special handling for organisation - show if user has ANY permission
        if($item === 'organisation') {
            return \classes\app\OrganisationPermissions::__oRead('billing', '') ||
                   \classes\app\OrganisationPermissions::__oRead('team', '') ||
                   \classes\app\OrganisationPermissions::__oRead('roles', '') ||
                   \classes\app\OrganisationPermissions::__oRead('locations', '') ||
                   \classes\app\OrganisationPermissions::__oRead('orders', '') ||
                   \classes\app\OrganisationPermissions::__oRead('organisation', '');
        }

        // Check specific permission
        [$main, $sub] = $permissionMap[$item];
        return \classes\app\OrganisationPermissions::__oRead($main, $sub);
    }

    public static function sideBarLinks($barName){
        $sideBarLinks = array(
            "developer" => array(
            ),

            "consumer" => array(
                "dashboard" => array(
                    "link" => Links::$consumer->dashboard,
                    "title" => "Oversigt",
                    "data-value" => "dashboard",
                    "icon-class" => "mdi mdi-view-grid-outline",
                    "access_level" => []
                ),
                "orders" => array(
                    "link" => Links::$consumer->orders,
                    "title" => "Ordre",
                    "data-value" => "orders",
                    "icon-class" => "mdi mdi-cart-outline",
                    "access_level" => []
                ),
                "payments" => array(
                    "link" => Links::$consumer->payments,
                    "title" => "Betalinger",
                    "data-value" => "payments",
                    "icon-class" => "mdi mdi-cash",
                    "access_level" => []
                ),
                "help" => array(
                    "link" => Links::$consumer->help,
                    "title" => "Hjælp",
                    "data-value" => "help",
                    "icon-class" => "mdi mdi-help-circle-outline",
                    "access_level" => []
                ),
            ),
            "merchant" => array(
                "dashboard" => array(
                    "link" => Links::$merchant->dashboard,
                    "title" => "Oversigt",
                    "data-value" => "dashboard",
                    "icon-class" => "mdi mdi-view-grid-outline",
                    "access_level" => []
                ),
                "orders" => array(
                    "link" => Links::$merchant->orders,
                    "title" => "Ordrer",
                    "data-value" => "orders",
                    "icon-class" => "mdi mdi-cart-outline",
                    "access_level" => []
                ),
                "payments" => array(
                    "link" => Links::$merchant->payments,
                    "title" => "Betalinger",
                    "data-value" => "payments",
                    "icon-class" => "mdi mdi-cash",
                    "access_level" => []
                ),
                "locations" => array(
                    "link" => Links::$merchant->locations->main,
                    "title" => "Butikker",
                    "data-value" => "locations",
                    "icon-class" => "mdi mdi-store-outline",
                    "access_level" => []
                ),
                "terminals" => array(
                    "link" => Links::$merchant->terminals->main,
                    "title" => "Terminaler",
                    "data-value" => "terminals",
                    "icon-class" => "mdi mdi-monitor",
                    "access_level" => []
                ),
                "customers" => array(
                    "link" => Links::$merchant->customers,
                    "title" => "Kunder",
                    "data-value" => "customers",
                    "icon-class" => "mdi mdi-account-heart-outline",
                    "access_level" => []
                ),
                "team" => array(
                    "link" => Links::$merchant->organisation->team,
                    "title" => "Medlemmer",
                    "data-value" => "team",
                    "icon-class" => "mdi mdi-account-multiple-outline",
                    "access_level" => []
                ),
                "organisation" => array(
                    "link" => Links::$merchant->organisation->home,
                    "title" => ucfirst(Translate::word("Organisation")),
                    "data-value" => "organisation",
                    "icon-class" => "fa-regular fa-building",
                    "access_level" => []
                ),
                "reports" => array(
                    "link" => Links::$merchant->reports,
                    "title" => "Rapporter",
                    "data-value" => "reports",
                    "icon-class" => "mdi mdi-file-document-outline",
                    "access_level" => []
                ),
                "materials" => array(
                    "link" => Links::$merchant->materials,
                    "title" => "Markedsføring",
                    "data-value" => "materials",
                    "icon-class" => "mdi mdi-image-multiple-outline",
                    "access_level" => []
                ),
                "help" => array(
                    "link" => Links::$merchant->help,
                    "title" => "Hjælp",
                    "data-value" => "help",
                    "icon-class" => "mdi mdi-help-circle-outline",
                    "access_level" => []
                ),
            ),

            "admin" => array(
                "home" => array(
                    "link" => "",
                    "title" => "Home",
                    "data-value" => "home",
                    "icon-class" => "mdi mdi-home",
                    "access_level" => []
                ),
                "creators" => array(
                    "link" => "creators",
                    "title" => "Creators",
                    "data-value" => "creators",
                    "icon-class" => "mdi mdi-account-star",
                    "access_level" => []
                ),
                "campaigns" => array(
                    "link" => "campaigns",
                    "title" => "Campaigns",
                    "data-value" => "campaigns",
                    "icon-class" => "mdi mdi-bullhorn",
                    "access_level" => []
                ),
                "integrations" => array(
                    "link" => "integrations",
                    "title" => "Integrations",
                    "data-value" => "integrations",
                    "icon-class" => "fa-solid fa-link",
                    "access_level" => []
                ),
                "cookie-manager" => array(
                    "link" => "cookie-manager",
                    "title" => "Cookies",
                    "data-value" => "cookie-manager",
                    "icon-class" => "mdi mdi-cookie",
                    "access_level" => []
                ),
            ),
            "admin-dashboard" => array(
                "home" => array(
                    "link" => ADMIN_PANEL_PATH,
                    "title" => "Home",
                    "data-value" => "home",
                    "icon-class" => "mdi mdi-home",
                    "access_level" => []
                ),
                "users" => array(
                    "link" => ADMIN_PANEL_PATH ."/users",
                    "title" => "Users",
                    "data-value" => "users",
                    "icon-class" => "mdi mdi-account-multiple",
                    "access_level" => []
                ),
                "cron-logs" => array(
                    "link" => ADMIN_PANEL_PATH . "/logs/list",
                    "title" => "Logs",
                    "data-value" => "logs",
                    "icon-class" => "mdi mdi-code-parentheses",
                    "access_level" => []
                ),
                "scraper-log" => array(
                    "link" => ADMIN_PANEL_PATH . "/scraper-logs",
                    "title" => "Scraper log",
                    "data-value" => "scraper-logs",
                    "icon-class" => "mdi mdi-code-parentheses-box",
                    "access_level" => []
                ),
                "settings" => array(
                    "link" => ADMIN_PANEL_PATH . "/app",
                    "title" => "App",
                    "data-value" => "app-settings",
                    "icon-class" => "mdi mdi-apple-keyboard-command",
                    "access_level" => []
                ),
                "payment-settings" => array(
                    "link" => ADMIN_PANEL_PATH . "/payment-settings",
                    "title" => "Payment settings",
                    "data-value" => "payment-settings",
                    "icon-class" => "mdi mdi-cogs",
                    "access_level" => []
                ),
                "transactions" => array(
                    "link" => ADMIN_PANEL_PATH . "/transactions",
                    "title" => "Transactions",
                    "data-value" => "transactions",
                    "icon-class" => "fa-solid fa-credit-card",
                    "access_level" => []
                ),
                "user-subscriptions" => array(
                    "link" => ADMIN_PANEL_PATH . "/user-subscriptions",
                    "title" => "Subscriptions",
                    "data-value" => "user-subscriptions",
                    "icon-class" => "fa-solid fa-money-bill-transfer",
                    "access_level" => []
                ),
                "ppu" => array(
                    "link" => ADMIN_PANEL_PATH . "/pay-per-use",
                    "title" => "Ppu",
                    "data-value" => "pay-per-use",
                    "icon-class" => "fa-solid fa-money-bill",
                    "access_level" => []
                ),
                "affiliates" => array(
                    "link" => ADMIN_PANEL_PATH . "/affiliates",
                    "title" => "Affiliates",
                    "data-value" => "affiliates",
                    "icon-class" => "mdi mdi-account-hard-hat",
                    "access_level" => []
                ),
            ),
            "merchant-dashboard" => array(
                "dashboard" => array(
                    "link" => Links::$merchant->dashboard,
                    "title" => "Oversigt",
                    "data-value" => "dashboard",
                    "icon-class" => "mdi mdi-view-grid-outline",
                    "access_level" => []
                ),
                "orders" => array(
                    "link" => Links::$merchant->orders,
                    "title" => "Ordrer",
                    "data-value" => "orders",
                    "icon-class" => "mdi mdi-cart-outline",
                    "access_level" => []
                ),
                "payments" => array(
                    "link" => Links::$merchant->payments,
                    "title" => "Betalinger",
                    "data-value" => "payments",
                    "icon-class" => "mdi mdi-cash",
                    "access_level" => []
                ),
                "locations" => array(
                    "link" => Links::$merchant->locations->main,
                    "title" => "Butikker",
                    "data-value" => "locations",
                    "icon-class" => "mdi mdi-store-outline",
                    "access_level" => []
                ),
                "terminals" => array(
                    "link" => Links::$merchant->terminals->main,
                    "title" => "Terminaler",
                    "data-value" => "terminals",
                    "icon-class" => "mdi mdi-monitor",
                    "access_level" => []
                ),
                "customers" => array(
                    "link" => Links::$merchant->customers,
                    "title" => "Kunder",
                    "data-value" => "customers",
                    "icon-class" => "mdi mdi-account-heart-outline",
                    "access_level" => []
                ),
                "team" => array(
                    "link" => Links::$merchant->organisation->team,
                    "title" => "Medlemmer",
                    "data-value" => "team",
                    "icon-class" => "mdi mdi-account-multiple-outline",
                    "access_level" => []
                ),
                "organisation" => array(
                    "link" => Links::$merchant->organisation->home,
                    "title" => ucfirst(Translate::word("Organisation")),
                    "data-value" => "organisation",
                    "icon-class" => "fa-regular fa-building",
                    "access_level" => []
                ),
                "reports" => array(
                    "link" => Links::$merchant->reports,
                    "title" => "Rapporter",
                    "data-value" => "reports",
                    "icon-class" => "mdi mdi-file-document-outline",
                    "access_level" => []
                ),
                "materials" => array(
                    "link" => Links::$merchant->materials,
                    "title" => "Markedsføring",
                    "data-value" => "materials",
                    "icon-class" => "mdi mdi-image-multiple-outline",
                    "access_level" => []
                ),
                "help" => array(
                    "link" => Links::$merchant->help,
                    "title" => "Hjælp",
                    "data-value" => "help",
                    "icon-class" => "mdi mdi-help-circle-outline",
                    "access_level" => []
                ),
            ),
        );

        // Get links for the requested bar
        $links = array_key_exists($barName,$sideBarLinks) ? $sideBarLinks[$barName] : array();

        // Filter based on organisation permissions (only for merchant dashboards)
        if(in_array($barName, ['merchant', 'merchant-dashboard'])) {
            $links = array_filter($links, function($link, $key) {
                return self::hasOrganisationPermission($key);
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $links;
    }

}