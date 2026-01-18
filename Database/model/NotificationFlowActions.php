<?php

namespace Database\model;

class NotificationFlowActions extends \Database\Model {

    public static ?string $uidPrefix = "nfla";

    protected static array $schema = [
        "uid" => "string",
        "flow" => "string",
        "template" => "string",
        "channel" => ["type" => "enum", "values" => ["email", "sms", "bell"], "default" => "email"],
        "delay_minutes" => ["type" => "integer", "default" => 0],
        "status" => ["type" => "enum", "values" => ["active", "inactive"], "default" => "active"],
    ];

    public static array $indexes = ["flow", "template", "channel", "status"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [
        // =====================================================
        // CONSUMER WELCOME ACTIONS
        // =====================================================
        [
            "uid" => "nfla_consumer_welcome_email",
            "flow" => "nflw_consumer_welcome",
            "template" => "ntpl_consumer_welcome_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_consumer_welcome_sms",
            "flow" => "nflw_consumer_welcome",
            "template" => "ntpl_consumer_welcome_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_consumer_welcome_bell",
            "flow" => "nflw_consumer_welcome",
            "template" => "ntpl_consumer_welcome_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PASSWORD RESET ACTIONS
        // =====================================================
        [
            "uid" => "nfla_password_reset_email",
            "flow" => "nflw_password_reset",
            "template" => "ntpl_password_reset_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_password_reset_sms",
            "flow" => "nflw_password_reset",
            "template" => "ntpl_password_reset_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // CONSUMER ORDER CONFIRMATION ACTIONS
        // =====================================================
        [
            "uid" => "nfla_consumer_order_confirm_email",
            "flow" => "nflw_consumer_order_confirm",
            "template" => "ntpl_consumer_order_confirm_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_consumer_order_confirm_sms",
            "flow" => "nflw_consumer_order_confirm",
            "template" => "ntpl_consumer_order_confirm_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "inactive", // SMS disabled for order creation - only email + bell
        ],
        [
            "uid" => "nfla_consumer_order_confirm_bell",
            "flow" => "nflw_consumer_order_confirm",
            "template" => "ntpl_consumer_order_confirm_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // ORDER CONTRACT ACTIONS (BNPL)
        // =====================================================
        [
            "uid" => "nfla_order_contract_email",
            "flow" => "nflw_order_contract",
            "template" => "ntpl_order_contract_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_order_contract_sms",
            "flow" => "nflw_order_contract",
            "template" => "ntpl_order_contract_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_order_contract_bell",
            "flow" => "nflw_order_contract",
            "template" => "ntpl_order_contract_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // ORDER COMPLETED DIRECT (Pay Now) ACTIONS
        // =====================================================
        [
            "uid" => "nfla_order_completed_direct_email",
            "flow" => "nflw_order_completed_direct",
            "template" => "ntpl_consumer_order_confirm_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_order_completed_direct_sms",
            "flow" => "nflw_order_completed_direct",
            "template" => "ntpl_consumer_order_confirm_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // MERCHANT ORDER ACTIONS
        // =====================================================
        [
            "uid" => "nfla_merchant_order_email",
            "flow" => "nflw_merchant_order",
            "template" => "ntpl_merchant_order_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_order_sms",
            "flow" => "nflw_merchant_order",
            "template" => "ntpl_merchant_order_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_order_bell",
            "flow" => "nflw_merchant_order",
            "template" => "ntpl_merchant_order_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT SUCCESS ACTIONS
        // =====================================================
        [
            "uid" => "nfla_payment_success_email",
            "flow" => "nflw_payment_success",
            "template" => "ntpl_payment_success_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_success_sms",
            "flow" => "nflw_payment_success",
            "template" => "ntpl_payment_success_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_success_bell",
            "flow" => "nflw_payment_success",
            "template" => "ntpl_payment_success_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT FAILED ACTIONS
        // =====================================================
        [
            "uid" => "nfla_payment_failed_email",
            "flow" => "nflw_payment_failed",
            "template" => "ntpl_payment_failed_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_failed_sms",
            "flow" => "nflw_payment_failed",
            "template" => "ntpl_payment_failed_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_failed_bell",
            "flow" => "nflw_payment_failed",
            "template" => "ntpl_payment_failed_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT REMINDER 5 DAY ACTIONS
        // =====================================================
        [
            "uid" => "nfla_payment_reminder_5d_email",
            "flow" => "nflw_payment_reminder_5d",
            "template" => "ntpl_payment_reminder_5day_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_reminder_5d_sms",
            "flow" => "nflw_payment_reminder_5d",
            "template" => "ntpl_payment_reminder_5day_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_reminder_5d_bell",
            "flow" => "nflw_payment_reminder_5d",
            "template" => "ntpl_payment_reminder_5day_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT REMINDER 1 DAY ACTIONS
        // =====================================================
        [
            "uid" => "nfla_payment_reminder_1d_email",
            "flow" => "nflw_payment_reminder_1d",
            "template" => "ntpl_payment_reminder_1day_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_reminder_1d_sms",
            "flow" => "nflw_payment_reminder_1d",
            "template" => "ntpl_payment_reminder_1day_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_reminder_1d_bell",
            "flow" => "nflw_payment_reminder_1d",
            "template" => "ntpl_payment_reminder_1day_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT OVERDUE ACTIONS
        // =====================================================
        [
            "uid" => "nfla_payment_overdue_email",
            "flow" => "nflw_payment_overdue",
            "template" => "ntpl_payment_overdue_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_overdue_sms",
            "flow" => "nflw_payment_overdue",
            "template" => "ntpl_payment_overdue_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_overdue_bell",
            "flow" => "nflw_payment_overdue",
            "template" => "ntpl_payment_overdue_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // RYKKER 1 ACTIONS
        // =====================================================
        [
            "uid" => "nfla_rykker_1_email",
            "flow" => "nflw_rykker_1",
            "template" => "ntpl_rykker_1_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_1_sms",
            "flow" => "nflw_rykker_1",
            "template" => "ntpl_rykker_1_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_1_bell",
            "flow" => "nflw_rykker_1",
            "template" => "ntpl_rykker_1_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // RYKKER 2 ACTIONS
        // =====================================================
        [
            "uid" => "nfla_rykker_2_email",
            "flow" => "nflw_rykker_2",
            "template" => "ntpl_rykker_2_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_2_sms",
            "flow" => "nflw_rykker_2",
            "template" => "ntpl_rykker_2_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_2_bell",
            "flow" => "nflw_rykker_2",
            "template" => "ntpl_rykker_2_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // RYKKER FINAL ACTIONS
        // =====================================================
        [
            "uid" => "nfla_rykker_final_email",
            "flow" => "nflw_rykker_final",
            "template" => "ntpl_rykker_3_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_final_sms",
            "flow" => "nflw_rykker_final",
            "template" => "ntpl_rykker_3_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_rykker_final_bell",
            "flow" => "nflw_rykker_final",
            "template" => "ntpl_rykker_3_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // ORG INVITE ACTIONS
        // =====================================================
        [
            "uid" => "nfla_org_invite_email",
            "flow" => "nflw_org_invite",
            "template" => "ntpl_org_invite_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_org_invite_sms",
            "flow" => "nflw_org_invite",
            "template" => "ntpl_org_invite_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // MERCHANT JOINED ACTIONS
        // =====================================================
        [
            "uid" => "nfla_merchant_joined_email",
            "flow" => "nflw_merchant_joined",
            "template" => "ntpl_merchant_joined_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_joined_bell",
            "flow" => "nflw_merchant_joined",
            "template" => "ntpl_merchant_joined_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // MERCHANT ORG READY ACTIONS
        // =====================================================
        [
            "uid" => "nfla_merchant_org_ready_email",
            "flow" => "nflw_merchant_org_ready",
            "template" => "ntpl_merchant_org_ready_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_org_ready_sms",
            "flow" => "nflw_merchant_org_ready",
            "template" => "ntpl_merchant_org_ready_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_org_ready_bell",
            "flow" => "nflw_merchant_org_ready",
            "template" => "ntpl_merchant_org_ready_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // MERCHANT VIVA APPROVED ACTIONS
        // =====================================================
        [
            "uid" => "nfla_merchant_viva_approved_email",
            "flow" => "nflw_merchant_viva_approved",
            "template" => "ntpl_merchant_viva_approved_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_viva_approved_sms",
            "flow" => "nflw_merchant_viva_approved",
            "template" => "ntpl_merchant_viva_approved_sms",
            "channel" => "sms",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_merchant_viva_approved_bell",
            "flow" => "nflw_merchant_viva_approved",
            "template" => "ntpl_merchant_viva_approved_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // POLICY UPDATE ACTIONS
        // =====================================================
        [
            "uid" => "nfla_policy_update_email",
            "flow" => "nflw_policy_update",
            "template" => "ntpl_policy_update_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // WEEKLY REPORT ORG ACTIONS
        // =====================================================
        [
            "uid" => "nfla_weekly_report_org_email",
            "flow" => "nflw_weekly_report_org",
            "template" => "ntpl_weekly_report_org_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_weekly_report_org_bell",
            "flow" => "nflw_weekly_report_org",
            "template" => "ntpl_weekly_report_org_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // WEEKLY REPORT LOCATION ACTIONS
        // =====================================================
        [
            "uid" => "nfla_weekly_report_loc_email",
            "flow" => "nflw_weekly_report_loc",
            "template" => "ntpl_weekly_report_location_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_weekly_report_loc_bell",
            "flow" => "nflw_weekly_report_loc",
            "template" => "ntpl_weekly_report_location_bell",
            "channel" => "bell",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // PAYMENT REFUND ACTIONS (email only)
        // =====================================================
        [
            "uid" => "nfla_payment_refund_bnpl_email",
            "flow" => "nflw_payment_refund_bnpl",
            "template" => "ntpl_payment_refund_bnpl_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_payment_refund_direct_email",
            "flow" => "nflw_payment_refund_direct",
            "template" => "ntpl_payment_refund_direct_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],

        // =====================================================
        // ORDER REFUND ACTIONS (email only)
        // =====================================================
        [
            "uid" => "nfla_order_refund_bnpl_email",
            "flow" => "nflw_order_refund_bnpl",
            "template" => "ntpl_order_refund_bnpl_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
        [
            "uid" => "nfla_order_refund_direct_email",
            "flow" => "nflw_order_refund_direct",
            "template" => "ntpl_order_refund_direct_email",
            "channel" => "email",
            "delay_minutes" => 0,
            "status" => "active",
        ],
    ];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "flow" => [NotificationFlows::tableColumn('uid'), NotificationFlows::newStatic()],
            "template" => [NotificationTemplates::tableColumn('uid'), NotificationTemplates::newStatic()],
        ];
    }
}
