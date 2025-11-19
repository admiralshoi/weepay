<?php

namespace routing\routes;

class ErrorController {



    public static function e404(): ?array {
        http_response_code(404);
        if(!isLoggedIn()) return Views("LANDING_404");
        return Views("USER_404");
    }

}