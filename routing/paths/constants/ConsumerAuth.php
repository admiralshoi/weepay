<?php

namespace routing\paths\constants;

class ConsumerAuth extends \routing\paths\Paths {




    const CONSUMER_AUTH_DASHBOARD_LOGIN = [
        "template" => "AUTH_INNER_HTML",
        "view" => "landing-page.consumer.auth.dashboard-login",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.auth.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.auth.auth.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];




}