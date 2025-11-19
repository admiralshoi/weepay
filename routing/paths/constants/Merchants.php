<?php

namespace routing\paths\constants;

class Merchants extends \routing\paths\Paths {

    const ORGANISATION_OVERVIEW = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.organisation.overview",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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
    const ORGANISATION_ADD = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.organisation.add",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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

    const MERCHANT_TEAM = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.team",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];
    const MERCHANT_ORDERS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.orders",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];
    const MERCHANT_TERMINALS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.terminals",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_LOCATIONS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.locations",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_DASHBOARD = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.dashboard",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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