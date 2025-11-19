<?php

namespace Database\model;

class PaymentProviders extends \Database\Model {

    public static ?string $uidPrefix = "ppr";
    protected static array $schema = [
        "uid"      => "string",
        "name"      => "string",
        "enabled" => ["type" => "tinyInteger", "default" => 0],
        "pkl"      => ["type" => "string", "nullable" => true, "default" => null],
        "skl"      => ["type" => "string", "nullable" => true, "default" => null],
        "pkt"      => ["type" => "string", "nullable" => true, "default" => null],
        "skt"      => ["type" => "string", "nullable" => true, "default" => null],
    ];

    public static array $indexes = [];
    public static array $uniques = ["name", "uid"];

    protected static array $requiredRows = [
        [
            "uid" => "ppr_fheioflje98f",
            "name" => "viva",
            "pkl" => "",
            "skl" => "",
            "pkt" => "",
            "skt" => "",
        ]
    ];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = ["pkl", "skl", "pkt", "skt"];
    public static function foreignkeys(): array {
        return [
        ];
    }
}
