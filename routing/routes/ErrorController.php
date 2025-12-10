<?php

namespace routing\routes;

class ErrorController {



    public static function e404(): ?array {
        http_response_code(404);
        if(!isLoggedIn()) return Views("LANDING_404");
        return Views("USER_404");
    }
    public static function expired(array $args): ?array {
        http_response_code(419);
        if(!array_key_exists("prevUrl", $args) || empty($args["prevUrl"])) $args["prevUrl"] = __url();
        if(!array_key_exists("prevUrlTitle", $args) || empty($args["prevUrlTitle"])) $args["prevUrlTitle"] = "Tilbage";
        return Views("EXPIRED_419", $args);
    }

}