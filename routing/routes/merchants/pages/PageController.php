<?php

namespace routing\routes\merchants\pages;

use classes\data\Calculate;
use classes\enumerations\Links;
use classes\Methods;
use classes\organisations\MemberEnum;
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
        $memberRows = Methods::organisationMembers()->getUserOrganisations();
        $memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations()->map(function ($location) {
            $orders = Methods::orders()->getByX(['location' => $location['uid'], 'status' => 'COMPLETED'], ['amount', 'uuid']);
            $location['order_count'] = $orders->count();
            $location['net_sales'] = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
            $location['customer_count'] = 1;
            $location['lfl_month'] = 100;
            return $location;
        });


        return Views("ORGANISATION_OVERVIEW", compact('memberRows', 'worldCountries', 'locations'));
    }

    public static function team(array $args): mixed {
        $members = Methods::organisationMembers()->getByX(['organisation' => __oid()])->map(function ($member) {
            $status = $member["status"];
            $invitationStatus = $member["invitation_status"];

            if($status === MemberEnum::MEMBER_SUSPENDED) {
                $showStatus = "Suspended";
                $statusBoxClass = "danger-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-power-off", 'title' => "Unsuspend", "action" => "unsuspend", 'risk' => "low"],
                ];
            }
            elseif($invitationStatus === MemberEnum::INVITATION_DECLINED) {
                $showStatus = "Declined";
                $statusBoxClass = "danger-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-envelope", 'title' => "Resend Invitation", "action" => "resend-invitation", 'risk' => "low"],
                ];
            }
            elseif($invitationStatus === MemberEnum::INVITATION_RETRACTED) {
                $showStatus = "Retracted";
                $statusBoxClass = "mute-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-envelope", 'title' => "Resend Invitation", "action" => "resend-invitation", 'risk' => "low"],
                ];
            }
            elseif($invitationStatus === MemberEnum::INVITATION_PENDING) {
                $showStatus = "Pending";
                $statusBoxClass = "warning-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-envelope", 'title' => "Resend Invitation", "action" => "resend-invitation", 'risk' => "low"],
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-xmark", 'title' => "Retract Invitation", "action" => "retract-invitation", 'risk' => "high"],
                ];
            }
            else {
                $showStatus = "Active";
                $statusBoxClass = "success-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-trash", 'title' => "Suspend", "action" => "suspend", 'risk' => "high"],
                ];
            }
            $member["action_menu"] = $actionMenu;
            $member["show_status"] = $showStatus;
            $member["status_box"] = $statusBoxClass;
            $member["name"] = $member["uuid"]['full_name'];
            $member["email"] = $member["uuid"]['email'];
            return $member;
        });
        $permissions = Settings::$organisation?->organisation->permissions;
        $memberRows = Methods::organisationMembers()->getUserOrganisations();
        $memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');


        return Views("MERCHANT_ORGANISATION_TEAM", compact('members', 'permissions',  'memberRows'));
    }

    public static function orders(array $args): mixed  {
        $locationHandler = Methods::locations();
        $orderHandler = Methods::orders();
        $orders = $orderHandler->getByOrganisation(__oUuid());
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');

        return Views("MERCHANT_ORDERS", compact('orders', 'locationOptions'));
    }

    public static function terminals(array $args): mixed  {
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

        $members = Methods::locationMembers()
        ->getByX(['location' => $location->uid, "status" => [MemberEnum::MEMBER_SUSPENDED, MemberEnum::MEMBER_ACTIVE]])
        ->map(function ($member) {
            $status = $member["status"];

            if($status === MemberEnum::MEMBER_SUSPENDED) {
                $showStatus = "Suspended";
                $statusBoxClass = "danger-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-power-off", 'title' => "Unsuspend", "action" => "unsuspend", 'risk' => "low"],
                ];
            }
            else {
                $showStatus = "Active";
                $statusBoxClass = "success-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-trash", 'title' => "Suspend", "action" => "suspend", 'risk' => "high"],
                ];
            }
            $member["action_menu"] = $actionMenu;
            $member["show_status"] = $showStatus;
            $member["status_box"] = $statusBoxClass;
            $member["name"] = $member["uuid"]['full_name'];
            $member["email"] = $member["uuid"]['email'];
            return $member;
        });
        $permissions = $location->permissions;




        return Views("MERCHANT_LOCATION_MEMBERS", compact(
            'locations', 'locationOptions', 'permissions', 'members',
            'worldCountries', 'slug', 'location', 'orders', 'orderCount', 'netSales',
            'ordersTodayCount', 'orderAverage', 'ordersToday', 'newCustomersCount', 'todayOrdersCountLflMonth',
            'netSalesLflMonth', 'newCustomersLflMonth', 'averageLflMonth'
        ));
    }



    public static function singleLocation(array $args): mixed  {
        $slug = $args['slug'];
        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;
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


    public static function locationPageBuilder(array $args): mixed  {
        $slug = $args['slug'];
        $locationHandler = Methods::locations();
        $location = $locationHandler->getFirst(['slug' => $slug, 'uuid' => __oUuid()]);
        if(isEmpty($location)) return null;
        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'slug', 'name');
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);



        return Views("MERCHANT_LOCATION_PAGE_BUILDER", compact(
            'locations', 'locationOptions','worldCountries', 'slug', 'location'
        ));
    }

    public static function locations(array $args): mixed  {
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

        $orders = Methods::orders()->getByX(['organisation' => __oUuid(), 'status' => 'COMPLETED'], ['amount', 'uuid', 'created_at']);
        $orderCount = $orders->count();
        $netSales = $orders->reduce(function ($carry, $item) { return $carry + $item['amount']; }, 0);
        $ordersToday = $orders->filter(function ($item) { return date("Y-m-d", strtotime($item['created_at'])) === date('Y-m-d'); });
        $ordersTodayCount = $ordersToday->count();
        $orderAverage = Calculate::average($netSales, $orderCount);
        $newCustomersCount = 1;
        $netSalesLflMonth = 100;
        $newCustomersLflMonth = 100;
        $todayOrdersCountLflMonth = min(100, $ordersTodayCount * 100);
        $averageLflMonth = 100;


        return Views("MERCHANT_DASHBOARD", compact(
            'locationOptions', 'netSales', 'orders', 'orderCount',
            'ordersTodayCount', 'orderAverage', 'ordersToday', 'newCustomersCount', 'todayOrdersCountLflMonth',
            'netSalesLflMonth', 'newCustomersLflMonth', 'averageLflMonth'
        ));
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