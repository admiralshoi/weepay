<?php
namespace routing\routes\consumer;

use classes\Methods;

class PageController {

    public static function dashboard(array $args): mixed  {
        $user = Methods::users()->get(__uuid());
        debugLog(['controller' => 'consumer.PageController::dashboard', 'user_id' => __uuid(), 'access_level' => $user->access_level ?? 'none'], 'dashboard-controller');
        return Views("CONSUMER_DASHBOARD", compact('user'));
    }
}
