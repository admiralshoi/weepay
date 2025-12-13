<?php
namespace Database\model;

class AuthLocal extends \Database\Model {
    public static ?string $uidPrefix = "authloc";
    protected static array $schema = [
        "uid" => "string",
        "email" => ["type" => "string", "default" => null, "nullable" => true],
        "username" => ["type" => "string", "default" => null, "nullable" => true],
        "phone" => ["type" => "string", "default" => null, "nullable" => true],
        "password" => "text",
        "2fa" => ["type" => "tinyInteger", "default" => 0],
        "2fa_method" => ["type" => "string", "default" => null, "nullable" => true],
        "enabled" => ["type" => "tinyInteger", "default" => 0],
        "restricted_logon_types" => ["type" => "text", "default" => null, "nullable" => true],
        "user" => ["type" => "string"],
        "phone_country_code" => ["type" => "string", "default" => null, "nullable" => true],
    ];
    public static array $indexes = [
        "user"
    ];
    public static array $uniques = [
        "uid","email","phone", "username"
    ];

    protected static array $requiredRows = [
        [
            "uid" => "authloc_fddfslfj39r43",
            "username" => "admin",
            "email" => "admin@system.com",
            "phone" => null,
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "user" => "29852698833154",
        ],
        [
            "uid" => "authloc_da4jugr730bnfs",
            "username" => "consumer",
            "email" => "consumer@example.com",
            "phone" => null,
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "user" => "31883198833154",
        ],
        [
            "uid" => "authloc_908da83kj130n422",
            "username" => "merchant",
            "email" => "merchant@example.com",
            "phone" => null,
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "user" => "6124499677766",
        ],
    ];

    protected static array $requiredRowsTesting = [

    ];


    public static array $encodeColumns = [
        "restricted_logon_types"
    ];

    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [
        "email", "username", "phone"
    ];
    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn("uid"), Users::newStatic()]
        ];
    }

}