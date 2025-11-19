<?php



function __oRead(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
    if(!$strictRole && !\classes\Methods::isMerchant()) return true;
    return \classes\Methods::organisationMembers()->memberHasPermission("read", $mainObject, $subObject);
}
function __oModify(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
    if(!$strictRole && !\classes\Methods::isMerchant()) return true;
    if(!__oRead($mainObject, $subObject)) return false;
    return \classes\Methods::organisationMembers()->memberHasPermission("modify", $mainObject, $subObject);
}
function __oDelete(string $mainObject = "", string $subObject = "", bool $strictRole = false): bool {
    if(!$strictRole && !\classes\Methods::isMerchant()) return true;
    if(!__oModify($mainObject, $subObject)) return false;
    return \classes\Methods::organisationMembers()->memberHasPermission("delete", $mainObject, $subObject);
}

function __oReadProtectedContent(string $mainObject = "", string $subObject = ""):void {
    startProtectedContent($mainObject, $subObject, "read");
}
function __oModifyProtectedContent(string $mainObject = "", string $subObject = ""):void {
    startProtectedContent($mainObject, $subObject, "modify");
}
function __oDeleteProtectedContent(string $mainObject = "", string $subObject = ""):void {
    startProtectedContent($mainObject, $subObject, "delete");
}
function startProtectedContent(string $mainObject = "", string $subObject = "", string $type = ""): void {
    ob_start();
    if(!isset($GLOBALS['protected_content']) || empty($GLOBALS['protected_content'])) $GLOBALS['protected_content'] = [];
    $GLOBALS['protected_content'][] = [
        'main' => $mainObject,
        'sub' => $subObject,
        'type' => $type,
    ];
}

function __oEndContent(): void {
    $content = ob_get_clean();
    $item = array_pop($GLOBALS['protected_content']);
    $mainObject = $item['main'] ?? "";
    $subObject = $item['sub'] ?? "";
    $type = $item['type'] ?? "";

    if(empty($GLOBALS['protected_content'])) unset($GLOBALS['protected_content']); // Clean up

    $status = match ($type) {
        default => false,
        "read" => __oRead($mainObject, $subObject),
        "modify" => __oModify($mainObject, $subObject),
        "delete" => __oDelete($mainObject, $subObject),
    };


    if ($status) echo $content;
    else echo '<p class="mt-2 color-red font-12">You lack the permissions necessary to ' . $type . ' the content</p>';
}