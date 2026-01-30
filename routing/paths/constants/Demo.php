<?php

namespace routing\paths\constants;

class Demo extends \routing\paths\Paths {

    const DEMO_LANDING = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.landing",
        "custom_scripts" => null,
        "title" => "Prøv WeePay Demo",
        "head" => null,
        "meta" => [
            "description" => "Prøv WeePay demo og oplev vores betalingsløsning fra både kasserer- og kundesiden. Ingen tilmelding krævet.",
            "og_title" => "Prøv WeePay Demo",
            "og_description" => "Oplev WeePays fleksible betalingsløsning. Test som kasserer eller kunde - helt gratis.",
        ],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
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

    const DEMO_MERCHANT_START = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.merchant.start",
        "custom_scripts" => null,
        "title" => "Demo - Kasserer",
        "head" => null,
        "meta" => [
            "description" => "Oplev WeePay fra kasserersiden. Se hvordan du nemt kan tilbyde fleksible betalingsplaner til dine kunder.",
            "og_title" => "WeePay Demo - Kasserer",
            "og_description" => "Oplev WeePay fra kasserersiden. Se hvordan du nemt kan tilbyde fleksible betalingsplaner til dine kunder.",
        ],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-merchant.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_MERCHANT_DETAILS = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.merchant.details",
        "custom_scripts" => null,
        "title" => "Demo - Opret kurv",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-merchant.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_MERCHANT_CHECKOUT = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.merchant.checkout",
        "custom_scripts" => null,
        "title" => "Demo - Afventer betaling",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-merchant.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_MERCHANT_FULFILLED = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.merchant.fulfilled",
        "custom_scripts" => null,
        "title" => "Demo - Ordre fuldført",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
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

    const DEMO_CONSUMER_START = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.consumer.start",
        "custom_scripts" => null,
        "title" => "Demo - Kunde",
        "head" => null,
        "meta" => [
            "description" => "Oplev WeePay fra kundesiden. Se hvordan du nemt kan vælge mellem fleksible betalingsplaner.",
            "og_title" => "WeePay Demo - Kunde",
            "og_description" => "Oplev WeePay fra kundesiden. Se hvordan du nemt kan vælge mellem fleksible betalingsplaner.",
        ],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-consumer.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_CONSUMER_INFO = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.consumer.info",
        "custom_scripts" => null,
        "title" => "Demo - Afventer kurv",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-consumer.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_CONSUMER_CHOOSE_PLAN = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.consumer.choose-plan",
        "custom_scripts" => null,
        "title" => "Demo - Vælg betalingsplan",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.demo.css",
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.demo-consumer.js",
                "js.initializer.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const DEMO_CONSUMER_CONFIRMATION = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "demo.consumer.order-confirmation",
        "custom_scripts" => null,
        "title" => "Demo - Ordrebekræftelse",
        "head" => null,
        "meta" => ["robots" => "noindex,nofollow"],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.order-confirmation.css",
                "css.responsiveness.css",
                "css.demo.css",
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

}
