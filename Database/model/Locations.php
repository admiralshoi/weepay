<?php

namespace Database\model;

class Locations extends \Database\Model {

    public static ?string $uidPrefix = "loc";
    protected static array $schema = [
        "uid" => "string",
        "uuid" => "string",
        "slug" => "string",
        "name" => "string",
        "description" => ['type' => "string", 'default' => null, 'nullable' => true],
        "caption" => ['type' => "string", 'default' => null, 'nullable' => true],
        "status" => ["type" => "enum", "default" => 'DRAFT', 'values' => ['DRAFT', 'ACTIVE', 'INACTIVE', 'DELETED']],
        "source_prid" => ['type' => "string", 'default' => null, 'nullable' => true],
        "opening_hours" => ['type' => "text", 'default' => null, 'nullable' => true],
        "inherit_details" => ['type' => "tinyInteger", 'default' => 1],
        "industry" => ['type' => "string", 'default' => null, 'nullable' => true],
        "contact_email" => ['type' => "string", 'default' => null, 'nullable' => true],
        "contact_phone" => ['type' => "string", 'default' => null, 'nullable' => true],
        "contact_phone_country_code" => ["type" => "string", "default" => null, "nullable" => true],
        "cvr" => ['type' => "string", 'default' => null, 'nullable' => true],
        "address" => ['type' => "text", 'default' => null, 'nullable' => true],
        "country" => ['type' => "string", 'default' => null, 'nullable' => true],
        "permissions" => ['type' => "text", 'default' => null, 'nullable' => true],
        "default_currency" => ['type' => "string", 'default' => null, 'nullable' => true],
    ];

    public static array $indexes = ["uuid", "source_prid"];
    public static array $uniques = ["uid", "slug"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["address", "opening_hours", "permissions"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "uuid" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
        ];
    }
}
