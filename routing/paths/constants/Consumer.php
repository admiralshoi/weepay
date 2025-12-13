<?php

namespace routing\paths\constants;

class Consumer extends \routing\paths\Paths {

    const CONSUMER_DASHBOARD = [
        "template" => "MAIN_INNER_HTML",
        "view" => "consumer.dashboard",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

}
