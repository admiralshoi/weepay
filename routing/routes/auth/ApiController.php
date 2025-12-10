<?php

namespace routing\routes\auth;
use classes\enumerations\Links;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class ApiController {

    #[NoReturn] public static function loginUser(array $args): void  {
        foreach (['username', 'password'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Missing required parameter $key");

        $authHandler = Methods::localAuthentication();
        if(!$authHandler->validate($args)) {
            $error = $authHandler->getError();
            Response()->jsonError($error["error"]['message'], [], $error['code']);
        }

        $authHandler->login();
        $user = $authHandler->getUser();

        $role = Methods::roles()->name($user?->access_level ?? 0);
        $redirectUrl = match ($role) {
            default => "",
            "consumer"=> __url(Links::$consumer->dashboard),
            "merchant" => __url(Links::$merchant->dashboard),
        };

        Response()->setRedirect($redirectUrl)->jsonSuccess("Velkommen tilbage, " . $user->full_name);
    }


}