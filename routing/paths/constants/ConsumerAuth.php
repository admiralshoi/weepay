<?php

namespace routing\paths\constants;

use env\api\Google;

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
                "js.oidc.js",
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

    const CONSUMER_AUTH_DASHBOARD_SIGNUP = [
        "template" => "AUTH_INNER_HTML",
        "view" => "landing-page.consumer.auth.dashboard-signup",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.oidc.js",
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

    const CONSUMER_AUTH_COMPLETE_PROFILE = [
        "template" => "AUTH_INNER_HTML",
        "view" => "landing-page.consumer.auth.complete-profile",
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

    const AUTH_CHANGE_PASSWORD = [
        "template" => "AUTH_INNER_HTML",
        "view" => "auth.change-password",
        "custom_scripts" => "templates.scripts",
        "title" => "Skift adgangskode",
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

    const AUTH_INVITATION_ERROR = [
        "template" => "AUTH_INNER_HTML",
        "view" => "auth.invitation-error",
        "custom_scripts" => "templates.scripts",
        "title" => "Invitation",
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
                "css.auth.auth.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const AUTH_PASSWORD_RECOVERY = [
        "template" => "AUTH_INNER_HTML",
        "view" => "landing-page.auth.password-recovery",
        "custom_scripts" => "templates.scripts",
        "title" => "Nulstil adgangskode",
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
            "custom" => [
                "js.auth-password-recovery.js",
                ["https://www.google.com/recaptcha/api.js?render=" . Google::RECAPTCHA_PK, 'js']
            ],
        ],
    ];

    const AUTH_RESET_PASSWORD = [
        "template" => "AUTH_INNER_HTML",
        "view" => "landing-page.auth.reset-password",
        "custom_scripts" => "templates.scripts",
        "title" => "Nulstil adgangskode",
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