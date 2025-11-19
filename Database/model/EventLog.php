<?php
namespace Database\model;

class EventLog extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "event_type" => "string",
        "event_value" => "string",
        "uid" => ["type" => "string", "default" => null, "nullable" => true],
        "_page" => ["type" => "string", "default" => null, "nullable" => true],
        "_object" => ["type" => "string", "default" => null, "nullable" => true],
        "_value" => ["type" => "string", "default" => null, "nullable" => true],
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