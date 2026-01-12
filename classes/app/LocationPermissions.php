<?php

namespace classes\app;

use classes\lang\Translate;
use classes\Methods;

class LocationPermissions {

    private static function checkParent(string $type, ?object $location, string $mainObject = "", bool $strictRole = false): bool {
        $organisationId = is_string($location->uuid) ? $location->uuid : $location->uuid->uid;
        $locationMemberHandler = Methods::locationMembers();

        // Check if user is a location member
        $locationMember = $locationMemberHandler->first(['location' => $location->uid, 'uuid' => __uuid()]);

        // If they are a location member but suspended/deleted, they have no access via this location
        if(!isEmpty($locationMember)) {
            if($locationMember->status === \classes\organisations\MemberEnum::MEMBER_SUSPENDED ||
               $locationMember->status === \classes\organisations\MemberEnum::MEMBER_DELETED) {
                return false;
            }
            // If they are an active location member but didn't have permission via memberHasPermission,
            // we return false since location membership access supersedes org-level access
            return false;
        }

        // User is not a location member, check org-level access
        $orgMemberHandler = Methods::organisationMembers();
        $memberRole = $orgMemberHandler->getMember($organisationId, __uuid());
        if(isEmpty($memberRole)) return false;

        // Check org member status - suspended/deleted means no access
        if($memberRole->status === \classes\organisations\MemberEnum::MEMBER_SUSPENDED ||
           $memberRole->status === \classes\organisations\MemberEnum::MEMBER_DELETED) {
            return false;
        }

        // Check first if they even have the general permission needed on org level
        // All location permissions are equivalent to the organisations: locations.$mainObject
        if($type === 'read' && !OrganisationPermissions::__oRead("locations", $mainObject, $strictRole)) return false;
        elseif($type === 'modify' && !OrganisationPermissions::__oModify("locations", $mainObject, $strictRole)) return false;
        elseif($type === 'delete' && !OrganisationPermissions::__oDelete("locations", $mainObject, $strictRole)) return false;

        // Finally, we see if the org member are restricted to certain locations only.
        // scoped_locations = null means they have full access to all available locations.
        $scopedLocations = toArray($memberRole->scoped_locations);
        return isEmpty($scopedLocations) || in_array($location->uid, $scopedLocations);
    }

    public static function __oRead(?object $location, string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(\classes\Methods::isAdmin()) return true;
        $type = 'read';
        if(empty($subObject)) {
            $subObject = $mainObject;
            $mainObject = "general";
        }
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        if(\classes\Methods::locationMembers()->memberHasPermission($location, $type, $mainObject, $subObject))  return true;
        return self::checkParent($type, $location, $subObject, $strictRole);
    }
    public static function __oModify(?object $location, string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(\classes\Methods::isAdmin()) return true;
        $type = 'modify';
        if(empty($subObject)) {
            $subObject = $mainObject;
            $mainObject = "general";
        }
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        if(\classes\Methods::locationMembers()->memberHasPermission($location, $type, $mainObject, $subObject))  return true;
        return self::checkParent($type, $location, $subObject, $strictRole);
    }
    public static function __oDelete(?object $location, string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(\classes\Methods::isAdmin()) return true;
        $type = 'delete';
        if(empty($subObject)) {
            $subObject = $mainObject;
            $mainObject = "general";
        }
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        if(\classes\Methods::locationMembers()->memberHasPermission($location, $type, $mainObject, $subObject))  return true;
        return self::checkParent($type, $location, $subObject, $strictRole);
    }

    public static function __oReadProtectedContent(?object $location, string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($location, $mainObject, $subObject, "read");
    }
    public static function __oModifyProtectedContent(?object $location, string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($location, $mainObject, $subObject, "modify");
    }
    public static function __oDeleteProtectedContent(?object $location, string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($location, $mainObject, $subObject, "delete");
    }
    public static function startProtectedContent(?object $location, string $mainObject = "", string $subObject = "", string $type = ""): void {
        ob_start();
        if(!isset($GLOBALS['protected_location_content']) || empty($GLOBALS['protected_location_content'])) $GLOBALS['protected_location_content'] = [];
        $GLOBALS['protected_location_content'][] = [
            'main' => $mainObject,
            'sub' => $subObject,
            'type' => $type,
            'location' => $location,
        ];
    }

    public static function __oEndContent(): void {
        $content = ob_get_clean();
        $item = array_pop($GLOBALS['protected_location_content']);
        $mainObject = $item['main'] ?? "";
        $subObject = $item['sub'] ?? "";
        $type = $item['type'] ?? "";
        $location = $item['location'] ?? null;

        if(empty($GLOBALS['protected_location_content'])) unset($GLOBALS['protected_location_content']); // Clean up

        $status = match ($type) {
            default => false,
            "read" => self::__oRead($location, $mainObject, $subObject),
            "modify" => self::__oModify($location, $mainObject, $subObject),
            "delete" => self::__oDelete($location, $mainObject, $subObject),
        };


        if ($status) echo $content;
        else echo '<p class="mt-2 text-wrap color-red font-12">'.Translate::context("permissions.std_$type").'</p>';
    }

}