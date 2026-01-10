<?php

namespace routing\paths\constants;

class Merchants extends \routing\paths\Paths {

    const ORGANISATION_OVERVIEW = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.organisation.overview",
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
                "js.merchant.js",
                "js.organisation.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];
    const ORGANISATION_ADD = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.organisation.add",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
                "js.organisation.js",
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

    const MERCHANT_ORGANISATION_TEAM = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.organisation.team",
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
                "js.merchant.js",
                "js.organisation.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [],
        ],
    ];
    const MERCHANT_ORDERS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.orders",
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
                "js.merchant.js",
                "js.orders.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "js.includes.moment.min.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_ORDER_DETAIL = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.order-detail",
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
                "js.merchant.js",
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

    const MERCHANT_CUSTOMER_DETAIL = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.customer-detail",
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
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "js.includes.dataTables.js",
                "vendor.datatables.dataTablesBs.css",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_PAYMENT_DETAIL = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.payment-detail",
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
                "js.merchant.js",
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

    const MERCHANT_CUSTOMERS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.customers",
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
                "js.merchant.js",
                "js.customers.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_PAYMENTS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.payments",
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
                "js.merchant.js",
                "js.payments.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "js.includes.moment.min.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_PENDING_PAYMENTS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.pending-payments",
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
                "js.merchant.js",
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

    const MERCHANT_PAST_DUE_PAYMENTS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.past-due-payments",
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
                "js.merchant.js",
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

    const MERCHANT_TERMINALS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.terminals",
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
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_LOCATIONS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.locations",
        "custom_scripts" => ["templates.scripts", 'templates.right-sidebars.location-actions'],
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_LOCATION_PAGE_BUILDER = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.page-builder",
        "custom_scripts" => ["templates.scripts", 'templates.right-sidebars.location-actions'],
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.page-builder.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_LOCATION_PAGE_PREVIEW = [
        "template" => "CUSTOMER_PURCHASE_FLOW_OUTER_HTML",
        "view" => "merchants.pages.page-preview",
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
                "js.page-preview.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const MERCHANT_LOCATION_PAGE_PREVIEW_CHECKOUT = [
        "template" => "CUSTOMER_PURCHASE_FLOW_INNER_HTML",
        "view" => "merchants.pages.page-preview-checkout",
        "custom_scripts" => null,
        "title" => "Checkout Preview",
        "head" => null,
        "assets" => [
            "main" => [
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "js.server.js",
                "js.page-preview.js",
            ],
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const MERCHANT_SINGLE_LOCATION = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.single-location",
        "custom_scripts" => ["templates.scripts", 'templates.right-sidebars.location-actions'],
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.location-orders.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "js.includes.moment.min.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
            "custom" => [],
        ],
    ];
    const MERCHANT_LOCATION_MEMBERS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.team",
        "custom_scripts" => ["templates.scripts", 'templates.right-sidebars.location-actions'],
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.modalHandler.js",
                "js.features.js",
                "js.merchant.js",
                "js.location-members.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "js.includes.handleBars.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_DASHBOARD = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.dashboard",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "js.includes.charts.js",
            ],
            "custom" => [],
        ],
    ];

    const MERCHANT_SETTINGS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.settings",
        "custom_scripts" => "templates.scripts",
        "title" => null,
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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

    const MERCHANT_ACCESS_DENIED = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.access-denied",
        "custom_scripts" => "templates.scripts",
        "title" => "Adgang Nægtet",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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

    const MERCHANT_MATERIALS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.materials",
        "custom_scripts" => "templates.scripts",
        "title" => "Markedsføring",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
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

    const MERCHANT_REPORTS = [
        "template" => "MERCHANT_INNER_HTML",
        "view" => "merchants.pages.reports",
        "custom_scripts" => "templates.scripts",
        "title" => "Rapporter",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.merchant.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "js.includes.charts.js",
                "vendor.moment.moment.min.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
            "custom" => [],
        ],
    ];
}