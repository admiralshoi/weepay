<?php

namespace Database\model;

class Organisations extends \Database\Model {
    public static ?string $uidPrefix = "org";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "industry" => ["type" => "string", "default" => null, "nullable" => true],
        "description" => ["type" => "string", "default" => null, "nullable" => true],
        "website" => ["type" => "string", "default" => null, "nullable" => true],
        "primary_email" => ["type" => "string", "default" => null, "nullable" => true],
        "contact_email" => ["type" => "string", "default" => null, "nullable" => true],
        "contact_phone" => ["type" => "string", "default" => null, "nullable" => true],
        "contact_phone_country_code" => ["type" => "string", "default" => null, "nullable" => true],
        "cvr" => ['type' => "string", 'default' => null, 'nullable' => true],
        "company_name" => ['type' => "string", 'default' => null, 'nullable' => true],
        "company_address" => ['type' => "text", 'default' => null, 'nullable' => true],
        "country" => ['type' => "string", 'default' => null, 'nullable' => true],
        "team_settings" => ["type" => "text", "default" => null, "nullable" => true],
        "general_settings" => ["type" => "text", "default" => null, "nullable" => true],
        "pictures" => ["type" => "text", "default" => null, "nullable" => true],
        "permissions" => ["type" => "text", "default" => null, "nullable" => true],
        "status" => ["type" => "enum", "default" => 'DRAFT', 'values' => ['DRAFT', 'ACTIVE', 'INACTIVE', 'DELETED']],
        "merchant_prid" => ["type" => "string", "default" => null, "nullable" => true],
        "default_currency" => ['type' => "string", 'default' => null, 'nullable' => true],
    ];

    public static array $indexes = ['cvr'];
    public static array $uniques = ["uid", "merchant_prid", "primary_email"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["team_settings", "general_settings", "pictures", "permissions", "company_address"];
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
            "country" => [Countries::tableColumn("uid"), Countries::newStatic()]
        ];
    }
}
