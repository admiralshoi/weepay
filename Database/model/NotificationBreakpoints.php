<?php

namespace Database\model;

class NotificationBreakpoints extends \Database\Model {

    public static ?string $uidPrefix = "nbp";

    protected static array $schema = [
        "uid" => "string",
        "key" => "string",
        "name" => "string",
        "description" => ["type" => "text", "nullable" => true, "default" => null],
        "category" => ["type" => "enum", "values" => ["order", "payment", "user", "subscription", "organisation", "system", "support"], "default" => "system"],
        "available_placeholders" => ["type" => "text", "nullable" => true, "default" => null],
        "trigger_type" => ["type" => "enum", "values" => ["instant", "scheduled"], "default" => "instant"],
        "is_system" => ["type" => "tinyInteger", "default" => 0],
        "status" => ["type" => "enum", "values" => ["active", "inactive"], "default" => "active"],
    ];

    public static array $indexes = ["category", "trigger_type", "status"];
    public static array $uniques = ["uid", "key"];

    protected static array $requiredRows = [
        // User breakpoints
        [
            "uid" => "nbp_user_registered",
            "key" => "user.registered",
            "name" => "Bruger registreret",
            "description" => "Udløses når en ny bruger opretter sig",
            "category" => "user",
            "available_placeholders" => '["user.full_name","user.email","user.phone","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_user_email_verified",
            "key" => "user.email_verified",
            "name" => "Email verificeret",
            "description" => "Udløses når bruger verificerer sin email",
            "category" => "user",
            "available_placeholders" => '["user.full_name","user.email","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_user_password_reset",
            "key" => "user.password_reset",
            "name" => "Nulstilling af adgangskode",
            "description" => "Udløses når bruger anmoder om nulstilling af adgangskode",
            "category" => "user",
            "available_placeholders" => '["user.full_name","user.email","reset_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // Order breakpoints
        [
            "uid" => "nbp_order_created",
            "key" => "order.created",
            "name" => "Ordre oprettet",
            "description" => "Udløses når en ny ordre oprettes",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","order.status","organisation.name","organisation.email","location.name","location.address","payment_plan.total_installments","payment_plan.first_amount","payment_plan.installment_amount","payment_plan.start_date","order_link","receipt_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_order_completed",
            "key" => "order.completed",
            "name" => "Ordre gennemført",
            "description" => "Udløses når en ordre er fuldt betalt",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","order.status","order.completed_at","organisation.name","organisation.email","location.name","location.address","payment_plan.total_installments","payment_plan.total_paid","receipt_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_agreement_created",
            "key" => "order.payment_agreement_created",
            "name" => "Betalingsaftale oprettet",
            "description" => "Udløses når en forbruger indgår en betalingsaftale (delbetaling)",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","organisation.name","organisation.email","location.name","payment_plan.total_installments","payment_plan.first_amount","payment_plan.first_amount_formatted","payment_plan.installment_amount","payment_plan.installment_amount_formatted","payment_plan.first_due_date","payment_plan.last_due_date","payment_plan.schedule_summary","agreement_link","payment_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_order_cancelled",
            "key" => "order.cancelled",
            "name" => "Ordre annulleret",
            "description" => "Udløses når en ordre annulleres",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","order.uid","order.amount","order.currency","reason","organisation.name","location.name","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_order_refunded",
            "key" => "order.refunded",
            "name" => "Ordre refunderet",
            "description" => "Udløses når en ordre refunderes (mindst én betaling refunderet)",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","user.phone","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","order.created_date","total_refunded","total_refunded_formatted","payments_refunded_count","payments_voided_count","refund_reason","refund_date","refund_time","refund_datetime","organisation.uid","organisation.name","organisation.email","organisation.phone","location.uid","location.name","location.email","location.address","location.city","location.zip","order_link","receipt_link","dashboard_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // Payment breakpoints
        [
            "uid" => "nbp_payment_successful",
            "key" => "payment.successful",
            "name" => "Betaling gennemført",
            "description" => "Udløses når en betaling gennemføres",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.currency","payment.installment_number","payment.paid_at","order.uid","order.amount","order.formatted_amount","payment_plan.total_installments","payment_plan.remaining_installments","payment_plan.next_due_date","payment_plan.remaining_amount","organisation.name","receipt_link","payment_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_failed",
            "key" => "payment.failed",
            "name" => "Betaling fejlet",
            "description" => "Udløses når en betaling fejler",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.currency","payment.due_date","failure_reason","order.uid","organisation.name","payment_link","retry_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_refunded",
            "key" => "payment.refunded",
            "name" => "Betaling refunderet",
            "description" => "Udløses når en betaling refunderes",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","user.phone","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","payment.installment_number","refund_amount","refund_formatted_amount","refund_reason","refund_date","refund_time","refund_datetime","is_partial_refund","is_full_refund","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","organisation.uid","organisation.name","organisation.email","organisation.phone","organisation.cvr","location.uid","location.name","location.email","location.address","location.city","location.zip","payment_link","receipt_link","order_link","dashboard_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_due_reminder",
            "key" => "payment.due_reminder",
            "name" => "Betalingspåmindelse",
            "description" => "Udløses X dage før betalingsfrist",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","payment.installment_number","days_until_due","order.uid","order.caption","payment_plan.total_installments","payment_plan.remaining_installments","organisation.name","payment_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_overdue",
            "key" => "payment.overdue_reminder",
            "name" => "Forfalden betaling",
            "description" => "Udløses X dage efter betalingsfrist er overskredet",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","payment.installment_number","days_overdue","order.uid","order.caption","payment_plan.total_installments","payment_plan.remaining_installments","organisation.name","payment_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        // Organisation breakpoints
        [
            "uid" => "nbp_org_member_invited",
            "key" => "organisation.member_invited",
            "name" => "Medlem inviteret",
            "description" => "Udløses når et teammedlem inviteres",
            "category" => "organisation",
            "available_placeholders" => '["inviter.full_name","invitee.email","organisation.name","invite_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_org_member_joined",
            "key" => "organisation.member_joined",
            "name" => "Medlem tilsluttet",
            "description" => "Udløses når et medlem accepterer invitation",
            "category" => "organisation",
            "available_placeholders" => '["user.full_name","user.email","organisation.name","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // Rykker/Dunning breakpoints
        [
            "uid" => "nbp_payment_rykker_1",
            "key" => "payment.rykker_1",
            "name" => "1. rykker",
            "description" => "Udløses ved første rykker (f.eks. 7 dage efter forfald)",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","days_overdue","order.uid","order.caption","organisation.name","payment_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_rykker_2",
            "key" => "payment.rykker_2",
            "name" => "2. rykker",
            "description" => "Udløses ved anden rykker (f.eks. 14 dage efter forfald)",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","days_overdue","order.uid","order.caption","organisation.name","payment_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_rykker_final",
            "key" => "payment.rykker_final",
            "name" => "Sidste rykker / Inkassovarsel",
            "description" => "Udløses ved sidste rykker før inkasso (f.eks. 21 dage efter forfald)",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","days_overdue","order.uid","order.caption","organisation.name","payment_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_payment_rykker_cancelled",
            "key" => "payment.rykker_cancelled",
            "name" => "Rykker annulleret",
            "description" => "Udløses når en rykker annulleres/nulstilles",
            "category" => "payment",
            "available_placeholders" => '["user.full_name","user.email","payment.uid","payment.amount","payment.formatted_amount","payment.due_date","payment.due_date_formatted","order.uid","order.caption","organisation.name","location.name","payment_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // Merchant breakpoints
        [
            "uid" => "nbp_merchant_order_received",
            "key" => "merchant.order_received",
            "name" => "Ny ordre modtaget (forretning)",
            "description" => "Udløses når en forretning modtager en ny ordre",
            "category" => "order",
            "available_placeholders" => '["user.full_name","user.email","user.phone","order.uid","order.amount","order.formatted_amount","order.currency","order.caption","order.created_date","order.created_time","organisation.name","location.name","payment_plan.total_installments","payment_plan.first_amount_formatted","payment_plan.total_amount_formatted","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_merchant_org_ready",
            "key" => "merchant.org_ready",
            "name" => "Forretningskonto klar",
            "description" => "Udløses når en forretningskonto er klar til brug",
            "category" => "organisation",
            "available_placeholders" => '["user.full_name","user.email","organisation.name","organisation.cvr","dashboard_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_merchant_viva_approved",
            "key" => "merchant.viva_approved",
            "name" => "Viva godkendelse",
            "description" => "Udløses når Viva godkender forretningens betalingsaftale",
            "category" => "organisation",
            "available_placeholders" => '["user.full_name","user.email","organisation.name","organisation.cvr","dashboard_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // System breakpoints
        [
            "uid" => "nbp_system_policy_updated",
            "key" => "system.policy_updated",
            "name" => "Politikopdatering",
            "description" => "Udløses når vilkår eller privatlivspolitik opdateres",
            "category" => "system",
            "available_placeholders" => '["user.full_name","user.email","policy_type","policy_name","update_summary","policy_link","app.name","app.url"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        // Report breakpoints
        [
            "uid" => "nbp_report_weekly_org",
            "key" => "report.weekly_organisation",
            "name" => "Ugentlig rapport (organisation)",
            "description" => "Ugentlig opsummering for organisationsejere",
            "category" => "organisation",
            "available_placeholders" => '["user.full_name","user.email","organisation.name","report_period_start","report_period_end","total_orders","total_revenue","total_revenue_formatted","pending_payments","completed_payments","dashboard_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_report_weekly_location",
            "key" => "report.weekly_location",
            "name" => "Ugentlig rapport (lokation)",
            "description" => "Ugentlig opsummering for lokationsejere",
            "category" => "organisation",
            "available_placeholders" => '["user.full_name","user.email","organisation.name","location.name","report_period_start","report_period_end","total_orders","total_revenue","total_revenue_formatted","pending_payments","completed_payments","dashboard_link","app.name"]',
            "trigger_type" => "scheduled",
            "is_system" => 1,
            "status" => "active"
        ],
        // Support breakpoints
        [
            "uid" => "nbp_support_ticket_created",
            "key" => "support.ticket_created",
            "name" => "Support henvendelse oprettet",
            "description" => "Udløses når en bruger opretter en ny support henvendelse",
            "category" => "support",
            "available_placeholders" => '["user.full_name","user.email","ticket.uid","ticket.subject","ticket.category","ticket.message","ticket.type","support_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
        [
            "uid" => "nbp_support_ticket_replied",
            "key" => "support.ticket_replied",
            "name" => "Support svar modtaget",
            "description" => "Udløses når admin svarer på en support henvendelse",
            "category" => "support",
            "available_placeholders" => '["user.full_name","user.email","ticket.uid","ticket.subject","ticket.category","reply.message","support_link","app.name"]',
            "trigger_type" => "instant",
            "is_system" => 1,
            "status" => "active"
        ],
    ];

    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["available_placeholders"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [];
    }
}
