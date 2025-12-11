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

    #[NoReturn] public static function signupUser(array $args): void {
        // Validate required fields for merchant signup
        foreach (['email', 'password', 'full_name'] as $key) {
            if(!array_key_exists($key, $args) || empty($args[$key]))
                Response()->jsonError("Mangler obligatorisk felt: $key", [], 400);
        }

        // Check if password confirmation matches (if provided)
        if (array_key_exists('password_confirm', $args) && $args['password'] !== $args['password_confirm']) {
            Response()->jsonError("Adgangskoderne matcher ikke", [], 400);
        }

        // Prepare user data for merchant
        $userData = [
            'email' => $args['email'],
            'full_name' => $args['full_name'],
            'phone' => !empty($args['phone']) ? $args['phone'] : null,
            'access_level' => 2, // Merchant
        ];

        // Prepare auth data
        $authData = [
            'email' => $args['email'],
            'username' => !empty($args['username']) ? $args['username'] : null,
            'phone' => !empty($args['phone']) ? $args['phone'] : null,
            'password' => $args['password'], // Will be hashed in LocalSignup
        ];

        // Create signup handler
        $signupHandler = Methods::localSignup();
        $signupHandler->setUserData($userData)->setAuthData($authData);

        // Validate
        if (!$signupHandler->validate()) {
            $error = $signupHandler->getError();
            Response()->jsonError($error['error']['message'], [], $error['code']);
        }

        // Create user and auth records
        if (!$signupHandler->signup()) {
            $error = $signupHandler->getError();
            Response()->jsonError($error['error']['message'], [], $error['code']);
        }

        // Auto-login
        if (!$signupHandler->autoLogin($args['password'])) {
            // If auto-login fails, still consider signup successful but redirect to login
            $redirectUrl = __url(Links::$app->auth->merchantLogin);
            Response()->setRedirect($redirectUrl)->jsonSuccess("Konto oprettet! Log venligst ind.");
        }

        $user = $signupHandler->getUser();
        $redirectUrl = __url(Links::$merchant->organisation->add); // Redirect to create first organisation
        Response()->setRedirect($redirectUrl)->jsonSuccess("Velkommen til " . BRAND_NAME . ", " . $user->full_name);
    }


}