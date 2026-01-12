<?php


use classes\enumerations\Links;
use JetBrains\PhpStorm\Pure;
use Database\model\UserRoles;
use classes\Methods;

function checkSession(): bool {
    return isset($_SESSION['uid']);
}
function hasCsrf(): bool {
    return isset($_SESSION['_csrf']);
}

function checkToken(): bool {
    return isset($_COOKIE['token']) && $_COOKIE['token'] === 'expected_token_value';
}

#[Pure] function api(): bool {
    return hasCsrf() || checkToken();
}


#[Pure] function loggedOut(): bool {
    return !isLoggedIn();
}

#[Pure] function loggedIn(): bool {
    return isLoggedIn();
}
#[Pure] function requiresLoggedOut(): bool {
    return !isLoggedIn();
}
#[Pure] function requiresApiLogout(): bool {
    return !isLoggedIn();
}
function requiresLogin(): bool {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = realUrlPath();
        return false;
    }
    return true;
}
function requiresApiLogin(): bool {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = realUrlPath();
        return false;
    }
    return true;
}


function user(): bool {
    return isLoggedIn() || guest();
}
function admin(): bool {
    return Methods::isAdmin();
}
function merchant(): bool {
    $result = Methods::isMerchant();
    $user = Methods::users()->get(__uuid());
    $accessLevel = $user->access_level ?? 'none';
    debugLog(['isMerchant' => $result, 'access_level' => $accessLevel, 'user_id' => __uuid()], 'middleware-merchant');
    return $result;
}
function merchantOrConsumer(): bool {
    return merchant()  || consumer();
}
function consumer(): bool {
    $result = Methods::isConsumer();
    $user = Methods::users()->get(__uuid());
    $accessLevel = $user->access_level ?? 'none';
    debugLog(['isConsumer' => $result, 'access_level' => $accessLevel, 'user_id' => __uuid()], 'middleware-consumer');
    return $result;
}
function guest(): bool {
    return Methods::isGuest();
}
function notMerchant(): bool { return !merchant(); }
function notConsumer(): bool { return !consumer(); }
function notAdmin(): bool { return !admin(); }

function cronJobAuth(array $args): bool {
    return isset($args["token"]) && $args["token"] === CRONJOB_TOKEN;
}

function consumerProfileComplete(): bool {
    if(!Methods::isConsumer()) return true;
    return !isEmpty(\features\Settings::$user?->phone) && !isEmpty(\features\Settings::$user?->full_name);
}

function isImpersonating(): bool {
    return !empty($_SESSION["admin_impersonating_uid"]) &&
           (!empty($_SESSION["admin_impersonating_org"]) || !empty($_SESSION["admin_impersonating_user"]));
}
