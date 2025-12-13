<?php

namespace classes\app;

use classes\Methods;

class LocationPermissions {

    private static function checkParent(string $type, ?object $location, string $mainObject = "", bool $strictRole = false): bool {

        //If they didn't have access  to the thing and are actual members, we then return false since membership access supersedes the org
        if(!isEmpty(Methods::locationMembers()->exists(['location' => $location->uid, 'uuid' => __uuid()]))) return false;
        $organisationId = is_string($location->uuid) ? $location->uuid : $location->uuid->uid;

        //Ensure they  are indeed members of the organisation
        $memberRole = Methods::organisationMembers()->getMember($organisationId, __uuid(), ['scoped_locations']);
        if(isEmpty($memberRole)) return false;


        //Check first if they even have the general permission needed on org level
        //All location permissions are equivalent to the organisations: locations.$mainObject
        if($type === 'read' && !OrganisationPermissions::__oRead("locations", $mainObject, $strictRole)) return false;
        elseif($type === 'modify' && !OrganisationPermissions::__oModify("locations", $mainObject, $strictRole)) return false;
        elseif($type === 'delete' && !OrganisationPermissions::__oDelete("locations", $mainObject, $strictRole)) return false;

        //Finally, we see if the org member are restricted to certain locations only.
        //scoped_locations = null mean they have full access to all available locations.
        $scopedLocations = toArray($memberRole->scoped_locations);
        return isEmpty($scopedLocations) || in_array($location->uid, $scopedLocations);
    }

    public static function __oRead(?object $location, string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
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
        else echo '<p class="mt-2 color-red font-12">You lack the permissions necessary to ' . $type . ' the content</p>';
    }

}