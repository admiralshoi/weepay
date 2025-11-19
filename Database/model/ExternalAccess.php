<?php
namespace Database\model;

class ExternalAccess extends \Database\Model {
    public static ?string $uidPrefix = "ext";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "user_agent" => ["type" => "string","default" => null, "nullable" => true, "comment" => ""],
        "origin" => ["type" => "string","default" => null, 'nullable' => true, "comment" => ""],
        "token" => ["type" => "text", "default" => null, 'nullable' => true, "comment" => ""],
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
    ];


    protected static array $requiredRows = [
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