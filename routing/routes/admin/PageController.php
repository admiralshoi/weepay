<?php
namespace routing\routes\admin;

use classes\Methods;
use features\Settings;

class PageController {

    public static function dashboard(array $args): mixed  {
        $user = Methods::users()->get(__uuid());

        return Views("ADMIN_DASHBOARD", compact('user'));
    }

}
