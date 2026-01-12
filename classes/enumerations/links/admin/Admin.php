<?php

namespace classes\enumerations\links\admin;

class Admin {

    // Dashboard routes (daily operations)
    public string $dashboard = "dashboard";
    public string $users = "dashboard/users";
    public string $consumers = "dashboard/consumers";
    public string $merchants = "dashboard/merchants";
    public string $organisations = "dashboard/organisations";
    public string $locations = "dashboard/locations";
    public string $orders = "dashboard/orders";
    public string $payments = "dashboard/payments";
    public string $paymentsPending = "dashboard/payments/pending";
    public string $paymentsPastDue = "dashboard/payments/past-due";
    public string $kpi = "dashboard/kpi";
    public string $reports = "dashboard/reports";
    public string $support = "dashboard/support";

    // Dashboard route aliases (for views using dashboardX naming)
    public string $dashboardUsers = "dashboard/users";
    public string $dashboardConsumers = "dashboard/consumers";
    public string $dashboardMerchants = "dashboard/merchants";
    public string $dashboardOrganisations = "dashboard/organisations";
    public string $dashboardLocations = "dashboard/locations";
    public string $dashboardOrders = "dashboard/orders";
    public string $dashboardPayments = "dashboard/payments";
    public string $dashboardPaymentsPending = "dashboard/payments/pending";
    public string $dashboardPaymentsPastDue = "dashboard/payments/past-due";
    public string $dashboardKpi = "dashboard/kpi";
    public string $dashboardReports = "dashboard/reports";
    public string $dashboardSupport = "dashboard/support";

    // Panel routes (system configuration)
    public string $panel = "panel";
    public string $panelSettings = "panel/settings";
    public string $panelMarketing = "panel/marketing";
    public string $panelFees = "panel/fees";
    public string $panelUsers = "panel/users";
    public string $panelLogs = "panel/logs/list";
    public string $panelWebhooks = "panel/webhooks";
    public string $panelApi = "panel/api";
    public string $panelPaymentPlans = "panel/payment-plans";
    public string $panelMaintenance = "panel/maintenance";
    public string $panelCache = "panel/cache";
    public string $panelJobs = "panel/jobs";

    // Content & Policies
    public string $panelPolicies = "panel/policies";
    public string $panelPoliciesPrivacy = "panel/policies/privacy";
    public string $panelPoliciesTerms = "panel/policies/terms";
    public string $panelPoliciesCookies = "panel/policies/cookies";

    // Communication
    public string $panelContactForms = "panel/contact-forms";
    public string $panelNotifications = "panel/notifications";

    // Dynamic route helpers
    public function userDetail(string $userId): string {
        return "dashboard/users/{$userId}";
    }

    public function organisationDetail(string $orgId): string {
        return "dashboard/organisations/{$orgId}";
    }

    public function locationDetail(string $locationId): string {
        return "dashboard/locations/{$locationId}";
    }

    public function orderDetail(string $orderId): string {
        return "dashboard/orders/{$orderId}";
    }

    // Dynamic route helpers with dashboard prefix (aliases)
    public function dashboardUserDetail(string $userId): string {
        return "dashboard/users/{$userId}";
    }

    public function dashboardOrganisationDetail(string $orgId): string {
        return "dashboard/organisations/{$orgId}";
    }

    public function dashboardLocationDetail(string $locationId): string {
        return "dashboard/locations/{$locationId}";
    }

    public function dashboardOrderDetail(string $orderId): string {
        return "dashboard/orders/{$orderId}";
    }

    public function paymentDetail(string $paymentId): string {
        return "dashboard/payments/{$paymentId}";
    }

    public function dashboardPaymentDetail(string $paymentId): string {
        return "dashboard/payments/{$paymentId}";
    }


    function __construct() {
        $ref = new \ReflectionClass(self::class);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue; // skip static
            }


            $type = $prop->getType();
            if (!$type) {
                continue; // skip untyped
            }

            // Skip if already initialized (PHP 8)
            if ($prop->isInitialized($this)) {
                continue;
            }

            $className = $type->getName();

            // We only auto-init class types, not scalar types
            if (class_exists($className)) {
                $prop->setAccessible(true);
                $prop->setValue($this, new $className());
            }
        }
    }
}
