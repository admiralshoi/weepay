<?php

namespace routing\paths\constants;

class Policies extends \routing\paths\Paths {

    const PRIVACY_POLICY = [
        "template" => "LANDING_HTML",
        "view" => "policies.privacy",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.landing_design.landing_style",
                "css.landing_design.policy_styles",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const TERMS_OF_USE = [
        "template" => "LANDING_HTML",
        "view" => "policies.terms-of-use",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => [
                "css.landing_design.landing_style",
                "css.landing_design.policy_styles",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


}