<?php

namespace routing\paths\constants;

class Wrappers extends \routing\paths\Paths {




    const CONSUMER_INNER_HTML = [
        "template" => "CONSUMER_OUTER_HTML",
        "view" => "templates.consumer-inner-html",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const CONSUMER_OUTER_HTML = [
        "template" => null,
        "view" => "templates.consumer-outer-html",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.bell-notifications.js",
                "css.main.css",
                "css.styles2.css",
                "css.responsiveness.css",
                "css.bell-notifications.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const MERCHANT_INNER_HTML = [
        "template" => "MERCHANT_OUTER_HTML",
        "view" => "templates.merchant-inner-html",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const MERCHANT_OUTER_HTML = [
        "template" => null,
        "view" => "templates.merchant-outer-html",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.bell-notifications.js",
                "css.main.css",
                "css.styles2.css",
                "css.responsiveness.css",
                "css.bell-notifications.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];



    const ADMIN_INNER_HTML = [
        "template" => "ADMIN_OUTER_HTML",
        "view" => "templates.admin-inner-html",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const ADMIN_OUTER_HTML = [
        "template" => null,
        "view" => "templates.admin-outer-html",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.adm-scripts.js",
                "js.initializer.js",
                "js.bell-notifications.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.bell-notifications.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const ADMIN_HOME_INNER_HTML = [
        "template" => "ADMIN_HOME_OUTER_HTML",
        "view" => "templates.admin-panel-inner-html",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const ADMIN_HOME_OUTER_HTML = [
        "template" => null,
        "view" => "templates.admin-panel-outer-html",
        "custom_scripts" => "templates.scripts",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.adm-scripts.js",
                "js.initializer.js",
                "js.bell-notifications.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.bell-notifications.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const LANDING_INNER_HTML = [
        "template" => "LANDING_OUTER_HTML",
        "view" => "templates.landing.inner",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const LANDING_OUTER_HTML = [
        "template" => null,
        "view" => "templates.landing.outer",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const AUTH_INNER_HTML = [
        "template" => "AUTH_OUTER_HTML",
        "view" => "templates.purchase-flow.customer-inner",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const AUTH_OUTER_HTML = [
        "template" => null,
        "view" => "templates.purchase-flow.customer-outer",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];
}