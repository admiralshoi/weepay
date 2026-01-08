<?php

namespace routing\paths\constants;

class CustomerPurchaseFlow extends \routing\paths\Paths {

    const CUSTOMER_LOCATION_HOME = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.customer.home",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const CUSTOMER_PURCHASE_FLOW_PLAN = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.customer.choose-plan",
        "custom_scripts" => null,
        "title" => "Login to continue",
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.customer-checkout.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];
    const CUSTOMER_PURCHASE_FLOW_INFO = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.customer.info",
        "custom_scripts" => null,
        "title" => "Login to continue",
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.customer-checkout.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];


    const CUSTOMER_PURCHASE_FLOW_START = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.customer.start",
        "custom_scripts" => null,
        "title" => "Login to continue",
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.auth.auth.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.oidc.js",
                "js.features.js",
                "js.auth.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];


    const CUSTOMER_ORDER_CONFIRMATION = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.customer.order-confirmation",
        "custom_scripts" => null,
        "title" => "Ordrebekræftelse",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.order-confirmation.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.customer-checkout.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];



    const MERCHANT_POS_START = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.merchant.start",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.merchant-pos.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];



    const MERCHANT_POS_DETAILS = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.merchant.details",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.merchant-pos.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];




    const MERCHANT_POS_CHECKOUT = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.merchant.checkout",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.async_search.js",
                "js.payments.merchant-pos.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];


    const MERCHANT_POS_FULFILLED = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.merchant.fulfilled",
        "custom_scripts" => null,
        "title" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const CHECKOUT_UNAVAILABLE = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "purchase-flow.unavailable",
        "custom_scripts" => null,
        "title" => "Betalinger ikke tilgængelige",
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];






    const CUSTOMER_PURCHASE_FLOW_INNER_HTML = [
        "template" => "CUSTOMER_PURCHASE_FLOW_OUTER_HTML",
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


    const CUSTOMER_PURCHASE_FLOW_OUTER_HTML = [
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