<?php

namespace routing\routes\auth;
use classes\enumerations\Links;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class ApiController {

    #[NoReturn] public static function loginUser(array $args): void  {
        foreach (['username', 'password'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Missing required parameter $key");

        if(!Methods::userLogin($args)) Response()->jsonError(Methods::userLoginError());

        $role = Methods::roles()->name($_SESSION['access_level'] ?? 0);
        $redirectUrl = match ($role) {
            default => "",
            "consumer"=> __url(Links::$consumer->dashboard),
            "merchant" => __url(Links::$merchant->dashboard),
        };


        Response()->setRedirect($redirectUrl)->jsonSuccess("You are now logged in");
    }


}