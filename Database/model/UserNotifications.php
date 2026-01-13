<?php

namespace Database\model;

class UserNotifications extends \Database\Model {

    public static ?string $uidPrefix = "un";

    protected static array $schema = [
        "uid" => "string",
        "user" => "string",
        "title" => "string",
        "content" => "text",
        "type" => ["type" => "enum", "values" => ["info", "success", "warning", "error"], "default" => "info"],
        "icon" => ["type" => "string", "nullable" => true, "default" => null],
        "link" => ["type" => "string", "nullable" => true, "default" => null],
        "reference_type" => ["type" => "string", "nullable" => true, "default" => null],
        "reference_id" => ["type" => "string", "nullable" => true, "default" => null],
        "is_read" => ["type" => "tinyInteger", "default" => 0],
        "read_at" => ["type" => "timestamp", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["user", "type", "is_read", "reference_type"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }
}
