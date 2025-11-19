<?php
namespace routing\routes\auth;

class PageController {

    public static function merchantDashboardLogin(array $args): mixed  {
        return Views("MERCHANT_AUTH_DASHBOARD_LOGIN", $args);
    }
    public static function consumerDashboardLogin(array $args): mixed  {
        return Views("CONSUMER_AUTH_DASHBOARD_LOGIN", $args);
    }
}