<?php

namespace routing\routes\merchants\pages;

use classes\Methods;
use classes\organisations\MemberEnum;
use features\Settings;

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

        return Views("ORGANISATION_ADD", compact('invitations'));
    }

    public static function organisation(array $args): mixed  {
        $memberRows = Methods::organisationMembers()->getUserOrganisations();
        $memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');

        return Views("ORGANISATION_OVERVIEW", compact('memberRows'));
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


        return Views("MERCHANT_TEAM", compact('members', 'permissions'));
    }

    public static function orders(array $args): mixed  {
        $locationHandler = Methods::locations();
        $orderHandler = Methods::orders();
        $orders = $orderHandler->getByOrganisation(__oUuid());
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'uid', 'name');

        return Views("MERCHANT_ORDERS", compact('orders', 'locationOptions'));
    }

    public static function terminals(array $args): mixed  {
        $locationHandler = Methods::locations();
        $terminalHandler = Methods::terminals();
        $terminals = $terminalHandler->getMyTerminals();
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'uid', 'name');

        return Views("MERCHANT_TERMINALS", compact('terminals', 'locationOptions'));
    }

    public static function locations(array $args): mixed  {
        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations();
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'uid', 'name');

        return Views("MERCHANT_LOCATIONS", compact('locations', 'locationOptions'));
    }


    public static function dashboard(array $args): mixed  {
        $locationHandler = Methods::locations();
        $locations = $locationHandler->getMyLocations(null, ['uid', 'name']);
        $locationOptions = mapItemToKeyValuePairs($locations->list(), 'uid', 'name');
        return Views("MERCHANT_DASHBOARD", compact('locationOptions'));
    }

}