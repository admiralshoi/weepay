<?php
namespace Database\model;

class Users extends \Database\Model {
    public static ?string $uidPrefix = "";
    protected static array $schema = [
        "uid" => "string",
        "username" => "string",
        "password" => "text",
        "email" => "string",
        "full_name" => "string",
        "access_level" => "integer",
        "lang" => ["type" => "string", "default" => "en"],
        "deactivated" => ["type" => "tinyInteger", "default" => 0],
        "registration_complete" => ["type" => "tinyInteger", "default" => 1],
        "created_by" => ["type" => "string", "default" => null, "nullable" => true],
        "cookies" => ["type" => "text", "default" => null, "nullable" => true],
        "address_country" => ["type" => "string", "default" => null, "nullable" => true],
        "address_region" => ["type" => "string", "default" => null, "nullable" => true],
        "address_city" => ["type" => "string", "default" => null, "nullable" => true],
        "address_zip" => ["type" => "string", "default" => null, "nullable" => true],
        "address_street" => ["type" => "string", "default" => null, "nullable" => true],
        "payout_bank_info" => ["type" => "string", "default" => null, "nullable" => true],
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "uid","email"
    ];

    protected static array $requiredRows = [
        [
            "uid" => "29852698833154",
            "username" => "admin",
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "email" => "admin@system.com",
            "full_name" => "System Admin",
            "access_level" => 9,
        ],
        [
            "uid" => "31883198833154",
            "username" => "consumer",
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "email" => "consumer@example.com",
            "full_name" => "Consumer Test",
            "access_level" => 1,
        ],
        [
            "uid" => "6124499677766",
            "username" => "merchant",
            "password" => "e128ef07f3be8ceac5e357196cd34c5cfab18ff79a13e92a5c475d11e0788969",
            "email" => "merchant@example.com",
            "full_name" => "Merchant User",
            "access_level" => 2,
        ],
    ];

    protected static array $requiredRowsTesting = [

    ];


    public static array $encodeColumns = [
        "cookies", "payout_bank_info"
    ];

    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [
        "address_country", "address_region", "address_city", "address_zip", "address_street"
    ];
    public static function foreignkeys(): array {
        return [
        ];
    }

}