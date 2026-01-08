<?php
namespace Database\model;

class AuthOidc extends \Database\Model {
    public static ?string $uidPrefix = "authoidc";
    protected static array $schema = [
        "uid" => "string",
        "provider" => "string",
        "prid" => "string",
        "nin" => "string",
        "nin_country" => ["type" => "string", "default" => null, "nullable" => true],
        "nin_user_type" => ["type" => "string", "default" => null, "nullable" => true],
        "enabled" => ["type" => "tinyInteger", "default" => 1],
        "restricted_logon_types" => ["type" => "text", "default" => null, "nullable" => true],
        "user" => "string",
    ];
    public static array $indexes = [
        "nin"
    ];
    public static array $uniques = [
        "uid","prid"
    ];

    protected static array $requiredRows = [];

    protected static array $requiredRowsTesting = [

    ];


    public static array $encodeColumns = [
        "restricted_logon_types"
    ];

    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [
        "nin", "prid"
    ];
    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn("uid"), Users::newStatic()]
        ];
    }

}