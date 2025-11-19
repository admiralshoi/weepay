<?php

namespace routing\paths\constants;

class Admin extends \routing\paths\Paths {

    const ADMIN_HOME = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "dashboards.admin",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Welcome Back",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "js.includes.dataTables.js",
                "js.includes.handleBars.js",
                "vendor.datatables.dataTablesBs.css",
            ],
            "custom" => [],
        ],
    ];


    const ADMIN_USERS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.users",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "js.includes.dataTables.js",
                "vendor.datatables.dataTablesBs.css",
            ],
            "custom" => [],
        ],
    ];

    const APP_SETTINGS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.app",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "js.includes.dataTables.js",
                "vendor.datatables.dataTablesBs.css",
            ],
            "custom" => [],
        ],
    ];


    const LOG_VIEW = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.log-view",
        "custom_scripts" => null,
        "head" => null,
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const LOG_LIST = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.logs",
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