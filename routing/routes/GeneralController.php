<?php

namespace routing\routes;

use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class GeneralController {


    #[NoReturn] public static function logout(): void  {
        Methods::terminalSessions()->voidCustomerSessions(__uuid());
        removeSessions();
        Response()->redirect("");
    }


    public static function pageNotReady(): mixed {
        return Views("PAGE_NOT_READY");
    }

    /**
     * Generate a QR code image for any URL
     * Usage: /qr?dest=<url-encoded-destination>
     */
    #[NoReturn] public static function generateQr(array $args): void {
        $destination = $args['dest'] ?? null;

        if (isEmpty($destination)) {
            Response()->jsonError("Missing 'dest' parameter", [], 400);
        }

        // URL decode the destination
        $url = urldecode($destination);

        // Basic validation - must be a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Response()->jsonError("Invalid URL format", [], 400);
        }

        // Generate QR code
        $qrGenerator = Methods::qr()->build($url)->get();

        Response()->mimeType($qrGenerator->getString(), $qrGenerator->getMimeType());
    }
}