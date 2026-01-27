<?php

namespace Database\model;

class ASignDesigns extends \Database\Model {

    public static ?string $uidPrefix = "asd";
    protected static array $schema = [
        "uid" => "string",
        "organisation" => "string",
        "location" => ["type" => "string", "default" => null, "nullable" => true],
        "name" => "string",
        "type" => ["type" => "enum", "default" => "design", "values" => ["design", "arbitrary"]],
        "size" => ["type" => "enum", "default" => "A1", "values" => ["A1", "B1", "A0", "50x70"]],
        "background_image" => ["type" => "string", "default" => null, "nullable" => true],
        "canvas_data" => ["type" => "longText", "default" => null, "nullable" => true],
        "elements" => ["type" => "longText", "default" => null, "nullable" => true],
        "bar_color" => ["type" => "string", "default" => "#8B4513"],
        "status" => ["type" => "enum", "default" => "DRAFT", "values" => ["DRAFT", "SAVED", "ARCHIVED"]],
        "preview_image" => ["type" => "string", "default" => null, "nullable" => true],
        "logo_image" => ["type" => "string", "default" => null, "nullable" => true],
        "created_by" => ["type" => "string", "default" => null, "nullable" => true],
    ];

    public static array $indexes = ["organisation", "location", "status", "type", "size"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["canvas_data", "elements"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "organisation" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
            "location" => [Locations::tableColumn("uid"), Locations::newStatic()],
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
