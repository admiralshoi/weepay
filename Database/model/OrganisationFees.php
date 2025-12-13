<?php

namespace Database\model;

class OrganisationFees extends \Database\Model {

    public static ?string $uidPrefix = "orgfee";
    protected static array $schema = [
        "uid" => "string",
        "organisation" => "string",
        "fee" => ["type" => "float", "default" => 0],
        "start_time" => ["type" => "bigInteger", "default" => 0],
        "end_time" => ["type" => "bigInteger", "default" => null, "nullable" => true],
        "enabled" => ["type" => "tinyInteger", "default" => 1],
        "created_by" => ["type" => "string", "default" => null, "nullable" => true],
        "reason" => ["type" => "string", "default" => null, "nullable" => true],
    ];

    public static array $indexes = ["organisation", "enabled"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "organisation" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
