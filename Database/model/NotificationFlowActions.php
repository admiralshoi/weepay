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

    protected static array $requiredRows = [];
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
