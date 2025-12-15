<?php

namespace routing\paths\constants;

use env\api\Google;

class Landing extends \routing\paths\Paths {


    const LANDING_HOME = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.home",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.contactForm.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
            ],
            "custom" => [
                ["https://www.google.com/recaptcha/api.js?render=" . Google::RECAPTCHA_PK, 'js']
            ],
        ],
    ];






    const LANDING_MITID_TEST_SUCCESS = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.mitid-test-success",
        "custom_scripts" => null,
        "title" => '',
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];
    const LANDING_MITID_TEST = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.mitid-test",
        "custom_scripts" => null,
        "title" => '',
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];
    const LANDING_VIVA_TEST = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.viva-test",
        "custom_scripts" => null,
        "title" => '',
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const LANDING_VIVA_TEST_RETURN = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.viva-test-return-url",
        "custom_scripts" => null,
        "title" => '',
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const CONSUMER_PRIVACY_POLICY = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.consumer-privacy",
        "custom_scripts" => null,
        "title" => 'Privatlivspolitik for forbrugere',
        "head" => null,
        "assets" => [
            "main" => [
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

    const CONSUMER_TERMS = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.consumer-terms",
        "custom_scripts" => null,
        "title" => 'Vilkår for forbrugere',
        "head" => null,
        "assets" => [
            "main" => [
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

    const MERCHANT_PRIVACY_POLICY = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.merchant-privacy",
        "custom_scripts" => null,
        "title" => 'Privatlivspolitik for forhandlere',
        "head" => null,
        "assets" => [
            "main" => [
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

    const MERCHANT_TERMS = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.merchant-terms",
        "custom_scripts" => null,
        "title" => 'Vilkår for forhandlere',
        "head" => null,
        "assets" => [
            "main" => [
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