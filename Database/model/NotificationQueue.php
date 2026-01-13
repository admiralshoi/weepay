<?php

namespace Database\model;

class NotificationQueue extends \Database\Model {

    public static ?string $uidPrefix = "nq";

    protected static array $schema = [
        "uid" => "string",
        "flow_action" => ["type" => "string", "nullable" => true, "default" => null],
        "recipient" => ["type" => "string", "nullable" => true, "default" => null],
        "recipient_email" => ["type" => "string", "nullable" => true, "default" => null],
        "recipient_phone" => ["type" => "string", "nullable" => true, "default" => null],
        "channel" => ["type" => "enum", "values" => ["email", "sms", "bell"], "default" => "email"],
        "subject" => ["type" => "string", "nullable" => true, "default" => null],
        "content" => "text",
        "context_data" => ["type" => "text", "nullable" => true, "default" => null],
        "status" => ["type" => "enum", "values" => ["pending", "processing", "sent", "failed", "cancelled"], "default" => "pending"],
        "scheduled_at" => "timestamp",
        "sent_at" => ["type" => "timestamp", "nullable" => true, "default" => null],
        "attempts" => ["type" => "integer", "default" => 0],
        "last_error" => ["type" => "text", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["flow_action", "recipient", "channel", "status", "scheduled_at"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["context_data"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "flow_action" => [NotificationFlowActions::tableColumn('uid'), NotificationFlowActions::newStatic()],
            "recipient" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }
}
