<?php

namespace Database\model;

class LocationPages extends \Database\Model {

    public static ?string $uidPrefix = "locpage";
    protected static array $schema = [
        "uid" => "string",
        "location" => "string",
        "state" => ["type" => "enum", "default" => 'DRAFT', 'values' => ['DRAFT', 'PUBLISHED', 'ARCHIVED']],
        "logo" => ['type' => "text", 'default' => null, 'nullable' => true],
        "hero_image" => ['type' => "text", 'default' => null, 'nullable' => true],
        "title" => ['type' => "string", 'default' => null, 'nullable' => true],
        "caption" => ['type' => "text", 'default' => null, 'nullable' => true],
        "about_us" => ['type' => "text", 'default' => null, 'nullable' => true],
        "credit_widget_enabled" => ['type' => "tinyInteger", 'default' => 1],
        "sections" => ['type' => "text", 'default' => null, 'nullable' => true],
        "created_by" => ['type' => "string", 'default' => null, 'nullable' => true],
    ];

    public static array $indexes = ["location", "state"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["sections"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "location" => [Locations::tableColumn("uid"), Locations::newStatic()],
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
