<?php

namespace Database\model;

class MarketingInspiration extends \Database\Model {

    public static ?string $uidPrefix = "mi";
    protected static array $schema = [
        "uid" => "string",
        "title" => "string",
        "category" => ["type" => "enum", "default" => "other", "values" => ["instagram", "a_sign", "a_sign_design", "a_sign_arbitrary", "poster", "other"]],
        "image_path" => "string",
        "description" => ["type" => "text", "default" => null, "nullable" => true],
        "status" => ["type" => "enum", "default" => "DRAFT", "values" => ["DRAFT", "ACTIVE", "INACTIVE"]],
        "sort_order" => ["type" => "integer", "default" => 0],
        "created_by" => ["type" => "string", "default" => null, "nullable" => true],
    ];

    public static array $indexes = ["status", "category", "sort_order"];
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
