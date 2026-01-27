<?php

namespace routing\paths\constants;

class Consumer extends \routing\paths\Paths {

    const CONSUMER_DASHBOARD = [
        "template" => "CONSUMER_INNER_HTML",
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
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
            ],
            "custom" => [],
        ],
    ];

    const CONSUMER_ORDERS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.orders",
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
                "js.includes.daterangepicker.js",
                "js.consumer-orders.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.includes.daterangepicker.css",
            ],
            "base" => null,
            "vendor" => [
                "moment/moment.min.js",
            ],
            "custom" => [],
        ],
    ];

    const CONSUMER_PAYMENTS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.payments",
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
                "js.includes.daterangepicker.js",
                "js.consumer-payments.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.includes.daterangepicker.css",
            ],
            "base" => null,
            "vendor" => [
                "moment/moment.min.js",
            ],
            "custom" => [],
        ],
    ];

    const CONSUMER_CHANGE_CARD = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.change-card",
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
                "js.consumer-change-card.js",
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

    const CONSUMER_RECEIPTS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.receipts",
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

    const CONSUMER_UPCOMING_PAYMENTS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.upcoming-payments",
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

    const CONSUMER_OUTSTANDING_PAYMENTS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.outstanding-payments",
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

    const CONSUMER_ORDER_DETAIL = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.order-detail",
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
                "js.consumer-payments.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const CONSUMER_PAYMENT_DETAIL = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.payment-detail",
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
                "js.consumer-payments.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const CONSUMER_SETTINGS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.settings",
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

    const CONSUMER_SUPPORT = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.support",
        "custom_scripts" => "templates.scripts",
        "title" => "Support",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.initializer.js",
                "js.includes.SweetPrompt.js",
                "js.consumer-support.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.support.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
            ],
            "custom" => [],
        ],
    ];

    const CONSUMER_LOCATION_DETAIL = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.location-detail",
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

    const CONSUMER_CARD_CHANGE_SUCCESS = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.card-change-success",
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

    const CONSUMER_CARD_CHANGE_FAILED = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.card-change-failed",
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

    const CONSUMER_HELP = [
        "template" => "CONSUMER_INNER_HTML",
        "view" => "consumer.help",
        "custom_scripts" => "templates.scripts",
        "title" => "Hjælp",
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
