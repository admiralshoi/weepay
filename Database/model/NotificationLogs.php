<?php

namespace Database\model;

class NotificationLogs extends \Database\Model {

    public static ?string $uidPrefix = "nlog";

    protected static array $schema = [
        "uid" => "string",
        "flow" => ["type" => "string", "nullable" => true, "default" => null],
        "template" => ["type" => "string", "nullable" => true, "default" => null],
        "breakpoint_key" => ["type" => "string", "nullable" => true, "default" => null],
        "recipient" => ["type" => "string", "nullable" => true, "default" => null],
        "recipient_identifier" => ["type" => "string", "nullable" => true, "default" => null],
        "channel" => ["type" => "enum", "values" => ["email", "sms", "bell"], "default" => "email"],
        "subject" => ["type" => "string", "nullable" => true, "default" => null],
        "content" => "text",
        "status" => ["type" => "enum", "values" => ["sent", "delivered", "failed", "bounced"], "default" => "sent"],
        "reference_id" => ["type" => "string", "nullable" => true, "default" => null],
        "reference_type" => ["type" => "string", "nullable" => true, "default" => null],
        "schedule_offset" => ["type" => "integer", "nullable" => true, "default" => null],
        "metadata" => ["type" => "text", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["flow", "template", "breakpoint_key", "recipient", "channel", "status", "reference_id"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["metadata"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "flow" => [NotificationFlows::tableColumn('uid'), NotificationFlows::newStatic()],
            "template" => [NotificationTemplates::tableColumn('uid'), NotificationTemplates::newStatic()],
            "recipient" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }
}
