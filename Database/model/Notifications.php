<?php
namespace Database\model;

class Notifications extends \Database\Model {
    public static ?string $uidPrefix = "ntf";
    protected static array $schema = [
        "uid" => "string",
        "recipient_id" => "string",
        "ref" => ["type" => "string","default" => ""],
        "type" => "string",
        "push_type" => ["type" => "tinyInteger","comment" => "0 = platform, 1 = email, 2 = both	"],
        "email_sent" => "tinyInteger",
        "is_read" => ["type" => "tinyInteger","default" => 0],
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "uid"
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