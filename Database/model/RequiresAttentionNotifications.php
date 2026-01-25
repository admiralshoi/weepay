<?php

namespace Database\model;

class RequiresAttentionNotifications extends \Database\Model {

    public static ?string $uidPrefix = "ran";
    protected static array $schema = [
        "uid"                   => "string",
        "target_audience"       => ["type" => "enum", "default" => "merchant", "values" => ["admin", "merchant"]],
        "organisation"          => ["type" => "string", "nullable" => true, "default" => null],
        "source"                => ["type" => "enum", "default" => "payment", "values" => ["payment", "php_error", "cronjob", "api", "webhook", "other"]],
        "type"                  => ["type" => "enum", "default" => "other", "values" => [
            // Payment-related (merchant)
            "recurring_not_enabled",
            "refund_not_enabled",
            "invalid_merchant_config",
            "sca_required",
            "account_blocked",
            // System/Admin types
            "php_error",
            "php_fatal",
            "cronjob_failure",
            "api_error",
            "webhook_failure",
            "gateway_error",
            "database_error",
            "security_alert",
            // Generic
            "other"
        ]],
        "severity"              => ["type" => "enum", "default" => "warning", "values" => ["info", "warning", "critical"]],
        "title"                 => "string",
        "message"               => "text",
        "related_entity_type"   => ["type" => "string", "nullable" => true, "default" => null],
        "related_entity_uid"    => ["type" => "string", "nullable" => true, "default" => null],
        "error_context"         => ["type" => "text", "nullable" => true, "default" => null],
        "viva_event_id"         => ["type" => "integer", "nullable" => true, "default" => null],
        "fault_type"            => ["type" => "enum", "default" => "system", "values" => ["merchant", "consumer", "system", "platform"]],
        "resolved"              => ["type" => "tinyInteger", "default" => 0],
        "resolved_by"           => ["type" => "string", "nullable" => true, "default" => null],
        "resolved_at"           => ["type" => "timestamp", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["target_audience", "organisation", "source", "type", "severity", "resolved", "created_at"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["error_context"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "organisation" => [Organisations::tableColumn('uid'), Organisations::newStatic()],
            "resolved_by" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }

}
