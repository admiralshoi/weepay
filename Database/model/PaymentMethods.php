<?php

namespace Database\model;

class PaymentMethods extends \Database\Model {

    public static ?string $uidPrefix = "pmt";
    protected static array $schema = [
        "uid" => "string",
        "type" => ["type" => "string", "nullable" => true, "default" => null],
        "brand" => ["type" => "string", "nullable" => true, "default" => null],
        "last4" => ["type" => "integer", "nullable" => true, "default" => null],
        "exp_month" => ["type" => "string", "nullable" => true, "default" => null],
        "exp_year" => ["type" => "string", "nullable" => true, "default" => null],
        "is_default" => ["type" => "tinyInteger", "default" => 0],
        "prid" => ["type" => "string", "nullable" => true, "default" => null],
        "customer" => ["type" => "string", "nullable" => true, "default" => null],
        "test" => "tinyInteger",
        "deleted" => ["type" => "tinyInteger", "default" => 0],
    ];
    public static array $indexes = ["customer"];
    public static array $uniques = ["prid", "uid"];


    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];  //Not for columns that uses encode columns (does not support array converting)

    public static function foreignkeys(): array {
        return [
            "customer" => [Customers::tableColumn('uid'), Customers::newStatic()],
        ];
    }
}