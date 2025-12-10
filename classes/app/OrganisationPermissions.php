<?php

namespace classes\app;

class OrganisationPermissions {


    public static function __oRead(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        return \classes\Methods::organisationMembers()->memberHasPermission("read", $mainObject, $subObject);
    }
    public static function __oModify(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        if(!self::__oRead($mainObject, $subObject)) return false;
        return \classes\Methods::organisationMembers()->memberHasPermission("modify", $mainObject, $subObject);
    }
    public static function __oDelete(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
        if(!$strictRole && !\classes\Methods::isMerchant()) return true;
        if(!self::__oModify($mainObject, $subObject)) return false;
        return \classes\Methods::organisationMembers()->memberHasPermission("delete", $mainObject, $subObject);
    }

    public static function __oReadProtectedContent(string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($mainObject, $subObject, "read");
    }
    public static function __oModifyProtectedContent(string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($mainObject, $subObject, "modify");
    }
    public static function __oDeleteProtectedContent(string $mainObject = "", string $subObject = ""):void {
        self::startProtectedContent($mainObject, $subObject, "delete");
    }
    public static function startProtectedContent(string $mainObject = "", string $subObject = "", string $type = ""): void {
        ob_start();
        if(!isset($GLOBALS['protected_organisation_content']) || empty($GLOBALS['protected_organisation_content'])) $GLOBALS['protected_organisation_content'] = [];
        $GLOBALS['protected_organisation_content'][] = [
            'main' => $mainObject,
            'sub' => $subObject,
            'type' => $type,
        ];
    }

    public static function __oEndContent(): void {
        $content = ob_get_clean();
        $item = array_pop($GLOBALS['protected_organisation_content']);
        $mainObject = $item['main'] ?? "";
        $subObject = $item['sub'] ?? "";
        $type = $item['type'] ?? "";

        if(empty($GLOBALS['protected_organisation_content'])) unset($GLOBALS['protected_organisation_content']); // Clean up

        $status = match ($type) {
            default => false,
            "read" => self::__oRead($mainObject, $subObject),
            "modify" => self::__oModify($mainObject, $subObject),
            "delete" => self::__oDelete($mainObject, $subObject),
        };


        if ($status) echo $content;
        else echo '<p class="mt-2 color-red font-12">You lack the permissions necessary to ' . $type . ' the content</p>';
    }

}