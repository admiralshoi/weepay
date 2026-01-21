<?php

namespace Database\model;

class MarketingTemplatePlaceholders extends \Database\Model {

    public static ?string $uidPrefix = "mtp";
    protected static array $schema = [
        "uid" => "string",
        "template" => "string",
        "type" => ["type" => "enum", "default" => "qr_code", "values" => ["qr_code", "location_name", "location_logo"]],
        "x" => ["type" => "decimal", "precision" => 10, "scale" => 4],
        "y" => ["type" => "decimal", "precision" => 10, "scale" => 4],
        "width" => ["type" => "decimal", "precision" => 10, "scale" => 4],
        "height" => ["type" => "decimal", "precision" => 10, "scale" => 4],
        "page_number" => ["type" => "integer", "default" => 1],
        "font_size" => ["type" => "integer", "default" => 12, "nullable" => true],
        "font_color" => ["type" => "string", "default" => "#000000", "nullable" => true],
        "sort_order" => ["type" => "integer", "default" => 0],
    ];

    public static array $indexes = ["template", "page_number"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "template" => [MarketingTemplates::tableColumn("uid"), MarketingTemplates::newStatic()],
        ];
    }
}
