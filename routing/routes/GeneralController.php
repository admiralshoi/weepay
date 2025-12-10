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
}