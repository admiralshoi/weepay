<?php

namespace Database\model;

class Customers extends \Database\Model {
    public static ?string $uidPrefix = "cus";
    protected static array $schema = [
        "uid" => "string",
        "uuid" => "string",
        "provider" => "string",
        "default_pm" => ["type" => "string", "nullable" => true, "default" => null],
        "prid" => ["type" => "string", "nullable" => true, "default" => null], //Payment provider's id for this object
        "billing_email" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_name" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_country" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_region" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_city" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_zip" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_street" => ["type" => "string", "nullable" => true, "default" => null],
        "billing_vat" => ["type" => "string", "nullable" => true, "default" => null],
        "test" => "tinyInteger",
    ];
    public static array $indexes = [];
    public static array $uniques = ["prid", "uid", "uuid"];


    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];  //Not for columns that uses encode columns (does not support array converting)
    public static function foreignkeys(): array {
        return [
            "provider" => [PaymentProviders::tableColumn('uid'), PaymentProviders::newStatic()],
        ];
    }
}