<?php

namespace Database\model;

class NotificationFlows extends \Database\Model {

    public static ?string $uidPrefix = "nflw";

    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "description" => ["type" => "text", "nullable" => true, "default" => null],
        "breakpoint" => "string",
        "status" => ["type" => "enum", "values" => ["active", "inactive", "draft"], "default" => "draft"],
        "priority" => ["type" => "integer", "default" => 100],
        "starts_at" => ["type" => "bigInteger", "nullable" => true, "default" => null],
        "ends_at" => ["type" => "bigInteger", "nullable" => true, "default" => null],
        "conditions" => ["type" => "text", "nullable" => true, "default" => null],
        "schedule_offset_days" => ["type" => "integer", "default" => 0],
        "recipient_type" => ["type" => "enum", "values" => ["user", "organisation", "location", "organisation_owner", "custom"], "default" => "user"],
        "recipient_email" => ["type" => "string", "nullable" => true, "default" => null],
        "created_by" => ["type" => "string", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["breakpoint", "status", "priority", "recipient_type", "created_by"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["conditions"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "breakpoint" => [NotificationBreakpoints::tableColumn('uid'), NotificationBreakpoints::newStatic()],
            "created_by" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }
}
