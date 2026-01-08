<?php
namespace routing\routes\auth;

use classes\enumerations\Links;
use classes\Methods;

class PageController {

    public static function merchantDashboardLogin(array $args): mixed  {
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);
        return Views("MERCHANT_AUTH_DASHBOARD_LOGIN", compact('worldCountries'));
    }
    public static function merchantDashboardSignup(array $args): mixed  {
        return Views("MERCHANT_AUTH_DASHBOARD_SIGNUP", $args);
    }
    public static function consumerDashboardLogin(array $args): mixed  {
        // Store redirect URL if provided, otherwise clear any existing redirect
        if(!empty($args['redirect'])) {
            $_SESSION['redirect_after_profile_completion'] = $args['redirect'];
        } else {
            unset($_SESSION['redirect_after_profile_completion']);
        }

        if(isLoggedIn())  {
            // If logged in, check for redirect URL first
            if(!empty($_SESSION['redirect_after_profile_completion'])) {
                $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
                unset($_SESSION['redirect_after_profile_completion']);
                Response()->redirect($redirectUrl);
            }

            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else return null;
            Response()->redirect(__url($url));
        }

        // Create OIDC session for consumer login
        $query = ['next' => 'consumer_login'];
        $token = crc32(json_encode($query) . "_" . __csrf());
        $oidcSessionId = Methods::oidcSession()->setSession("authenticate", $query, $token);

        // If OIDC session creation fails, still show the page but OIDC button won't work
        if(isEmpty($oidcSessionId)) {
            debugLog("Failed to create OIDC session for consumer login", "oidc-session-error");
            $oidcSessionId = null;
        }

        // Get auth error from query params if redirected back from failed OIDC
        $authError = $args['auth_error'] ?? null;

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("CONSUMER_AUTH_DASHBOARD_LOGIN", compact('oidcSessionId', 'authError', 'worldCountries'));
    }

    public static function consumerDashboardSignup(array $args): mixed  {
        // Store redirect URL if provided, otherwise clear any existing redirect
        if(!empty($args['redirect'])) {
            $_SESSION['redirect_after_profile_completion'] = $args['redirect'];
        } else {
            unset($_SESSION['redirect_after_profile_completion']);
        }

        if(isLoggedIn())  {
            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else return null;
            Response()->redirect(__url($url));
        }

        // Create OIDC session for consumer signup
        $query = ['next' => 'consumer_signup'];
        $token = crc32(json_encode($query) . "_" . __csrf());
        $oidcSessionId = Methods::oidcSession()->setSession("authenticate", $query, $token);

        // If OIDC session creation fails, still show the page but OIDC button won't work
        if(isEmpty($oidcSessionId)) {
            debugLog("Failed to create OIDC session for consumer signup", "oidc-session-error");
            $oidcSessionId = null;
        }

        // Get auth error from query params if redirected back from failed OIDC
        $authError = $args['auth_error'] ?? null;

        return Views("CONSUMER_AUTH_DASHBOARD_SIGNUP", compact('oidcSessionId', 'authError'));
    }

    public static function consumerCompleteProfile(array $args): mixed  {
        if(!isLoggedIn()) Response()->redirect(__url(Links::$app->auth->consumerSignup));
        if(!Methods::isConsumer()) Response()->redirect(__url(Links::$app->home));

        $user = Methods::users()->get(__uuid());
        if(isEmpty($user)) Response()->redirect(__url(Links::$app->auth->consumerSignup));

        // If user already has full_name, email and phone, redirect to intended destination or dashboard
        if(!isEmpty($user->full_name) && !isEmpty($user->email) && !isEmpty($user->phone)) {
            if(!empty($_SESSION['redirect_after_profile_completion'])) {
                $redirectUrl = __url($_SESSION['redirect_after_profile_completion']);
                unset($_SESSION['redirect_after_profile_completion']);
                Response()->redirect($redirectUrl);
            } else {
                Response()->redirect(__url(Links::$consumer->dashboard));
            }
        }

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("CONSUMER_AUTH_COMPLETE_PROFILE", compact('user', 'worldCountries'));
    }
}