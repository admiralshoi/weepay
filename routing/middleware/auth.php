<?php


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
    return Methods::isMerchant();
}
function consumer(): bool {
    return Methods::isConsumer();
}
function guest(): bool {
    return Methods::isGuest();
}

function cronJobAuth(array $args): bool {
    return isset($args["token"]) && $args["token"] === CRONJOB_TOKEN;
}
