<?php

namespace routing\routes\api;

use JetBrains\PhpStorm\NoReturn;

class GeneralController {

    /**
     * Simple connection test endpoint
     * Used by server.js to check if the server is reachable
     */
    #[NoReturn] public static function connectionTest(array $args): void {
        Response()->jsonSuccess("Connection OK");
    }

}
