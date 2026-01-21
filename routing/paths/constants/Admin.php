<?php

namespace routing\paths\constants;

class Admin extends \routing\paths\Paths {

    // =====================================================
    // DASHBOARD PAGES (New admin dashboard - daily ops)
    // =====================================================

    const ADMIN_DASHBOARD = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.index",
        "custom_scripts" => "templates.scripts",
        "title" => "Dashboard",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_USERS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.users",
        "custom_scripts" => "templates.scripts",
        "title" => "Alle brugere",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-users.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_USER_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.user-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Bruger detaljer",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
                "js.includes.dataTables.js",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_CONSUMERS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.consumers",
        "custom_scripts" => "templates.scripts",
        "title" => "Forbrugere",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-consumers.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_MERCHANTS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.merchants",
        "custom_scripts" => "templates.scripts",
        "title" => "Forhandlere",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-merchants.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_ORGANISATIONS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.organisations",
        "custom_scripts" => "templates.scripts",
        "title" => "Organisationer",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-organisations.js",
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

    const ADMIN_DASHBOARD_ORGANISATION_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.organisation-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Organisation detaljer",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
                "js.includes.dataTables.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_LOCATIONS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.locations",
        "custom_scripts" => "templates.scripts",
        "title" => "Lokationer",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-locations.js",
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

    const ADMIN_DASHBOARD_LOCATION_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.location-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Lokation detaljer",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
                "js.includes.dataTables.js",
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_ORDERS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.orders",
        "custom_scripts" => "templates.scripts",
        "title" => "Ordrer",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-orders.js",
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

    const ADMIN_DASHBOARD_ORDER_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.order-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Ordre detaljer",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-refunds.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_PAYMENTS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.payments",
        "custom_scripts" => "templates.scripts",
        "title" => "Betalinger",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-payments.js",
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

    const ADMIN_DASHBOARD_PAYMENT_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.payment-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Betaling detaljer",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-refunds.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
                "js.rykker.js",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_PAYMENTS_PENDING = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.payments-pending",
        "custom_scripts" => "templates.scripts",
        "title" => "Afventende betalinger",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-payments-pending.js",
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

    const ADMIN_DASHBOARD_PAYMENTS_PAST_DUE = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.payments-past-due",
        "custom_scripts" => "templates.scripts",
        "title" => "Forfaldne betalinger",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-payments-past-due.js",
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

    const ADMIN_DASHBOARD_KPI = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.kpi",
        "custom_scripts" => "templates.scripts",
        "title" => "KPI Oversigt",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_REPORTS = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.reports",
        "custom_scripts" => "templates.scripts",
        "title" => "Rapporter",
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
                "css.responsiveness.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.apexcharts.apexcharts.min.js",
                "vendor.moment.moment.min.js",
            ],
            "custom" => [
                "js.includes.daterangepicker.js",
                "css.includes.daterangepicker.css",
            ],
        ],
    ];

    const ADMIN_DASHBOARD_SUPPORT = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.support",
        "custom_scripts" => "templates.scripts",
        "title" => "Support",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-support.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.support.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [],
        ],
    ];

    const ADMIN_DASHBOARD_SUPPORT_DETAIL = [
        "template" => "ADMIN_INNER_HTML",
        "view" => "admin.dashboard.support-detail",
        "custom_scripts" => "templates.scripts",
        "title" => "Support Ticket",
        "head" => "templates.head",
        "assets" => [
            "main" => [
                "js.server.js",
                "js.main.js",
                "js.utility.js",
                "js.features.js",
                "js.admin-support.js",
                "js.initializer.js",
                "css.main.css",
                "css.styles2.css",
                "css.styles3.css",
                "css.responsiveness.css",
                "css.support.css",
            ],
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [],
        ],
    ];


    // =====================================================
    // PANEL PAGES (System configuration - existing + new)
    // =====================================================

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

    const ADMIN_PANEL_HOME = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.home",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Panel",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_SETTINGS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.settings",
        "custom_scripts" => null,
        "head" => null,
        "title" => "App Indstillinger",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.sweetPrompt.js",
            ],
            "custom" => [
                "js.admin-panel-settings.js",
            ],
        ],
    ];

    const ADMIN_PANEL_MARKETING = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.marketing",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Marketing Materialer",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
            ],
            "custom" => [
                "js.admin-marketing.js",
            ],
        ],
    ];

    const ADMIN_PANEL_MARKETING_TEMPLATE_EDITOR = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.marketing-template-editor",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Template Editor",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.SweetPrompt.js",
                "vendor.pdfjs.pdf.min.js",
            ],
            "custom" => [
                "js.admin-marketing-editor.js",
                "css.marketing-editor.css",
            ],
        ],
    ];

    const ADMIN_PANEL_FEES = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.fees",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Gebyrer",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
                "js.includes.sweetPrompt.js",
                "vendor.datatables.dataTables.js",
                "vendor.datatables.dataTablesBs.js",
                "vendor.datatables.dataTablesBs.css",
                "js.includes.dataTables.js",
            ],
            "custom" => [
                "js.admin-panel-fees.js",
            ],
        ],
    ];

    const ADMIN_PANEL_WEBHOOKS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.webhooks",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Webhooks",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_API = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.api",
        "custom_scripts" => null,
        "head" => null,
        "title" => "API",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_PAYMENT_PLANS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.payment-plans",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Betalingsplaner",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
            ],
            "custom" => [
                "js.admin-panel-payment-plans.js",
            ],
        ],
    ];

    const ADMIN_PANEL_MAINTENANCE = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.maintenance",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Maintenance",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.css",
                "js.includes.sweetAlert.js",
                "vendor.sweetalert.sweetalert2.min.js",
            ],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_CACHE = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.cache",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Cache",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_JOBS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.jobs",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Cron Jobs",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [
                "js.admin-panel-jobs.js",
            ],
        ],
    ];

    const ADMIN_PANEL_POLICIES = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.policies",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Politikker",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_POLICIES_PRIVACY = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.policies-privacy",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Privatlivspolitik",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const ADMIN_PANEL_POLICIES_TERMS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.policies-terms",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Servicevilkår",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const ADMIN_PANEL_POLICIES_COOKIES = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.policies-cookies",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Cookiepolitik",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const ADMIN_PANEL_CONTACT_FORMS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.contact-forms",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Kontaktformularer",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [],
            "custom" => [],
        ],
    ];

    const ADMIN_PANEL_NOTIFICATIONS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.notifications",
        "custom_scripts" => null,
        "head" => null,
        "title" => "Notifikationer",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
            ],
        ],
    ];

    const ADMIN_PANEL_FAQS = [
        "template" => "ADMIN_HOME_INNER_HTML",
        "view" => "admin.panel.faqs",
        "custom_scripts" => null,
        "head" => null,
        "title" => "FAQ Administration",
        "assets" => [
            "main" => null,
            "base" => null,
            "vendor" => [
                "vendor.sweetalert.sweetalert2.min.js",
                "vendor.sweetalert.sweetalert2.min.css",
            ],
            "custom" => [
                "js.includes.SweetPrompt.js",
                "js.admin-panel-faqs.js",
                "css.faq.css",
            ],
        ],
    ];


    // =====================================================
    // EXISTING PANEL PAGES (Keep as-is)
    // =====================================================

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
