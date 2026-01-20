<?php
namespace Database\model;

class Cronjob extends \Database\Model {
    public static ?string $uidPrefix = "cro";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "started_at" => "integer",
        "finished_at" => "integer",
        "can_run" => "tinyInteger",
        "is_running" => "tinyInteger",
        "access_level" => "tinyInteger",
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "uid"
    ];


    protected static array $requiredRows = [
        [
            "uid" => "crn_take_payments",
            "name" => "Take Payments",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_retry_payments",
            "name" => "Retry Payments",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_cleanup_logs",
            "name" => "Cleanup Logs",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_payment_notifications",
            "name" => "Payment Notifications",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_notification_queue",
            "name" => "Notification Queue",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_rykker_checks",
            "name" => "Rykker Checks",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_weekly_reports",
            "name" => "Weekly Reports",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
        [
            "uid" => "crn_policy_publish",
            "name" => "Policy Publish",
            "started_at" => 0,
            "finished_at" => 0,
            "can_run" => 1,
            "is_running" => 0,
            "access_level" => 8
        ],
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
        ];
    }
}
