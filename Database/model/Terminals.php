<?php

namespace Database\model;

class Terminals extends \Database\Model {

    public static ?string $uidPrefix = "ter";
    protected static array $schema = [
        "uid" => "string",
        "uuid" => "string",
        "location" => "string",
        "name" => "string",
        "state" => ["type" => "enum", "default" => 'IDLE', 'values' => ['IDLE', 'ACTIVE', 'AWAITING_CUSTOMER', 'AWAITING_MERCHANT']],
        "status" => ["type" => "enum", "default" => 'DRAFT', 'values' => ['DRAFT', 'ACTIVE', 'INACTIVE', 'DELETED']],
        "session" => ["type" => 'string', "default" => null, 'nullable' => true],
    ];

    public static array $indexes = ["location", "uuid"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "uuid" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
            "location" => [Locations::tableColumn("uid"), Locations::newStatic()],
        ];
    }
}
