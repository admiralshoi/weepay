<?php

namespace Database\model;

class MarketingTemplates extends \Database\Model {

    public static ?string $uidPrefix = "mt";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "file_path" => "string",
        "type" => ["type" => "enum", "default" => "A4", "values" => ["A4", "A3", "A5", "roll-up", "poster", "flyer", "sticker"]],
        "status" => ["type" => "enum", "default" => "DRAFT", "values" => ["DRAFT", "ACTIVE", "INACTIVE"]],
        "preview_image" => ["type" => "string", "default" => null, "nullable" => true],
        "description" => ["type" => "text", "default" => null, "nullable" => true],
        "created_by" => ["type" => "string", "default" => null, "nullable" => true],
    ];

    public static array $indexes = ["status", "type"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
