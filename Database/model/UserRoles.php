<?php
namespace Database\model;

class UserRoles extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "access_level" => "integer",
        "name" => "string",
        "description" => "string",
        "depth" => ["type" => "string", "default" => ""],
        "defined" => "tinyInteger",
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "access_level"
    ];


    protected static array $requiredRows = [
        [
            "access_level" => 0,
            "name" => "guest",
            "description" => "Guest user",
            "depth" => "",
            "defined" => 1,
        ],
        [
            "access_level" => 1,
            "name" => "consumer",
            "description" => "End consumer. People who pay with WeePay",
            "depth" => "",
            "defined" => 1,
        ],
        [
            "access_level" => 2,
            "name" => "merchant",
            "description" => "Merchant and employees",
            "depth" => "",
            "defined" => 1,
        ],
        [
            "access_level" =>8,
            "name" => "admin",
            "description" => "Platform admin. Second highest authority. The user has access to everything. Can read and write to every object",
            "depth" => "all",
            "defined" => 1,
        ],
        [
            "access_level" => 9,
            "name" => "system_admin",
            "description" => "System admin. Highest authority. The user has access to developer-menus as well as anything the platform offers",
            "depth" => "all",
            "defined" => 1,
        ]
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
        ];
    }
}