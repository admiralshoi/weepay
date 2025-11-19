<?php

namespace Database\model;

class Countries extends \Database\Model {

    public static ?string $uidPrefix = "cnt";
    protected static array $schema = [
        "uid" => "string",
        "code" => "string",
        "name" => "string",
        "enabled" => ["type" => "tinyInteger", "default" => 0],
    ];
    public static array $indexes = [

    ];
    public static array $uniques = [
        "code", "uid"
    ];


    protected static array $requiredRows = [
        [
            "uid" => "ctr_70zjtwdj7",
            "code" => "DE",
            "name" => "Germany",
            "enabled" => 1,
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