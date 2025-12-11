<?php
namespace routing\routes\auth;

use classes\enumerations\Links;
use classes\Methods;

class PageController {

    public static function merchantDashboardLogin(array $args): mixed  {
        return Views("MERCHANT_AUTH_DASHBOARD_LOGIN", $args);
    }
    public static function merchantDashboardSignup(array $args): mixed  {
        return Views("MERCHANT_AUTH_DASHBOARD_SIGNUP", $args);
    }
    public static function consumerDashboardLogin(array $args): mixed  {
        if(isLoggedIn())  {
            if(Methods::isAdmin()) $url = Links::$admin->dashboard;
            elseif(Methods::isConsumer()) $url = Links::$consumer->dashboard;
            elseif(Methods::isMerchant()) $url = Links::$merchant->dashboard;
            else return null;
            Response()->redirect(__url($url));
        }
        return Views("CONSUMER_AUTH_DASHBOARD_LOGIN", $args);
    }
}