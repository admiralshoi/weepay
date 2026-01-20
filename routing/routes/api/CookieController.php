<?php

namespace routing\routes\api;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class CookieController {

    /**
     * Accept cookie consent
     */
    #[NoReturn] public static function accept(array $args): void {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $userUid = isLoggedIn() ? __uuid() : null;

        $success = Methods::cookieConsents()->recordConsent($userUid, $ipAddress, $userAgent);

        if ($success) {
            Response()->jsonSuccess('Cookies accepted');
        } else {
            Response()->jsonError('Could not record consent', [], 500);
        }
    }
}
