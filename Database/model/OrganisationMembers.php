<?php

namespace Database\model;

class OrganisationMembers extends \Database\Model {

    public static ?string $uidPrefix = "orm";
    protected static array $schema = [
        "uid" => "string",
        "uuid" => "string",
        "organisation" => "string",
        "role" => "string",
        "invitation_status" => ["type" => "string", "default" => 'PENDING'], //pending, declined, accepted, retracted
        "status" => ["type" => "string", "default" => 'ACTIVE'], //removed, suspended, active
        "invitation_activity" => ["type" => "text", "default" => null, "nullable" => true],
        "change_activity" => ["type" => "text", "default" => null, "nullable" => true],
    ];

    public static array $indexes = ["organisation", "uuid"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["change_activity", "invitation_activity"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "uuid" => [Users::tableColumn("uid"), Users::newStatic()],
            "organisation" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
        ];
    }
}
