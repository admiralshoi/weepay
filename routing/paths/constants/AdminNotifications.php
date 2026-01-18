<?php

namespace routing\paths\constants;

class AdminNotifications extends \routing\paths\Paths {

    // =====================================================
    // NOTIFICATION SYSTEM PAGES
    // =====================================================

    const NOTIFICATION_TEMPLATES = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.templates",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikationsskabeloner",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "js.includes.dataTables.js",
            ],
            "custom" => [
                "js.admin-notifications.js",
            ],
        ],
    ];

    const NOTIFICATION_TEMPLATE_DETAIL = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.template-detail",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Rediger skabelon",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [
                "js.admin-notifications.js",
            ],
        ],
    ];

    const NOTIFICATION_BREAKPOINTS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.breakpoints",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikations breakpoints",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "js.includes.dataTables.js",
            ],
            "custom" => [],
        ],
    ];

    const NOTIFICATION_FLOWS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.flows",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikationsflows",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "js.includes.dataTables.js",
            ],
            "custom" => [
                "js.admin-notifications.js",
            ],
        ],
    ];

    const NOTIFICATION_FLOW_DETAIL = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.flow-detail",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Rediger flow",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [
                "js.admin-notifications.js",
            ],
        ],
    ];

    const NOTIFICATION_QUEUE = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.queue",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikationskø",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "js.includes.dataTables.js",
            ],
            "custom" => [
                "js.admin-notifications.js",
            ],
        ],
    ];

    const NOTIFICATION_LOGS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications.logs",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikationslogs",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [
                "js.admin-notification-logs.js",
            ],
        ],
    ];

}
