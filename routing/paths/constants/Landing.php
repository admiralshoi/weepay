<?php

namespace routing\paths\constants;

use env\api\Google;

class Landing extends \routing\paths\Paths {


    const LANDING_HOME = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.home",
        "custom_scripts" => null,
        "title" => "Betal senere, del betalingen op",
        "head" => null,
        "meta" => [
            "description" => "WeePay gør det nemt at betale senere eller dele betalingen op. Fleksible betalingsløsninger for forbrugere og forhandlere i Danmark.",
            "og_title" => "WeePay - Betal senere, del betalingen op",
            "og_description" => "Fleksible betalingsløsninger der passer til dig. Betal nu, senere eller del op i rater.",
        ],
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
        "view" => "policies.policy-template",
        "custom_scripts" => null,
        "title" => 'Privatlivspolitik for forbrugere',
        "head" => null,
        "meta" => [
            "description" => "Læs WeePays privatlivspolitik for forbrugere. Vi beskytter dine personlige data og forklarer hvordan vi behandler dine oplysninger.",
        ],
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
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

    const CONSUMER_TERMS = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.policy-template",
        "custom_scripts" => null,
        "title" => 'Vilkår for forbrugere',
        "head" => null,
        "meta" => [
            "description" => "Læs WeePays handelsbetingelser og vilkår for forbrugere. Forstå dine rettigheder og forpligtelser når du bruger vores betalingsløsninger.",
        ],
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
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

    const MERCHANT_PRIVACY_POLICY = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.policy-template",
        "custom_scripts" => null,
        "title" => 'Privatlivspolitik for forhandlere',
        "head" => null,
        "meta" => [
            "description" => "Læs WeePays privatlivspolitik for forhandlere. Vi beskytter dine virksomhedsdata og forklarer hvordan vi behandler dine oplysninger.",
        ],
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
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

    const MERCHANT_TERMS = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.policy-template",
        "custom_scripts" => null,
        "title" => 'Vilkår for forhandlere',
        "head" => null,
        "meta" => [
            "description" => "Læs WeePays handelsbetingelser og vilkår for forhandlere. Forstå dine rettigheder og forpligtelser som partner.",
        ],
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
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

    const COOKIES_POLICY = [
        "template" => "LANDING_INNER_HTML",
        "view" => "policies.policy-template",
        "custom_scripts" => null,
        "title" => 'Cookiepolitik',
        "head" => null,
        "meta" => [
            "description" => "Læs om WeePays brug af cookies. Vi forklarer hvilke cookies vi bruger og hvordan de hjælper med at forbedre din oplevelse.",
        ],
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
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

    const FAQ_CONSUMER = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.faq.consumer",
        "custom_scripts" => null,
        "title" => 'FAQ - Forbrugere',
        "head" => null,
        "meta" => [
            "description" => "Få svar på de mest stillede spørgsmål om WeePay som forbruger. Lær om betalingsplaner, sikkerhed og hvordan du kommer i gang.",
            "schema_type" => "FAQPage",
        ],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.faq.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const FAQ_MERCHANT = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.faq.merchant",
        "custom_scripts" => null,
        "title" => 'FAQ - Forhandlere',
        "head" => null,
        "meta" => [
            "description" => "Få svar på de mest stillede spørgsmål om WeePay som forhandler. Lær om integration, gebyrer og hvordan du tilbyder fleksible betalinger.",
            "schema_type" => "FAQPage",
        ],
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.faq.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const GUIDE_MERCHANT_ONBOARDING = [
        "template" => "LANDING_INNER_HTML",
        "view" => "landing-page.guides.merchant-onboarding",
        "custom_scripts" => null,
        "title" => 'Kom godt i gang som forhandler',
        "head" => null,
        "meta" => [
            "description" => "Guide til at komme i gang som forhandler hos WeePay. Lær trin-for-trin hvordan du opsætter din butik og begynder at tilbyde fleksible betalinger.",
        ],
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