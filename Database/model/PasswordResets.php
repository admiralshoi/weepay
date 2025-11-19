<?php
namespace Database\model;

class PasswordResets extends \Database\Model {
    public static ?string $uidPrefix = "pwr";
    protected static array $schema = [
        "uid" => "string",
        "email" => "string",
        "token" => "string",
        "is_used" => ["type" => "tinyInteger","default" => 0],
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "token"
    ];


    protected static array $requiredRows = [
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
            "email" => [Users::tableColumn("email"), Users::newStatic()]
        ];
    }
}