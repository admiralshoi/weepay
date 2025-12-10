<?php

namespace routing\paths\constants;

class ErrorPages extends \routing\paths\Paths {




    const PAGE_NOT_READY = [
        "template" => "LANDING_INNER_HTML",
        "view" => "error-pages.page-not-ready",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const EXPIRED_419 = [
        "template" => "LANDING_INNER_HTML",
        "view" => "error-pages.page-expired",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const USER_404 = [
        "template" => "HOME_INNER_HTML",
        "view" => "error-pages.404-logged-in",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];


    const LANDING_404 = [
        "template" => "AUTH_OUTER_HTML",
        "view" => "error-pages.404-landing",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

}