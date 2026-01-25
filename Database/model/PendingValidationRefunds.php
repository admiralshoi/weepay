<?php

namespace Database\model;

class PendingValidationRefunds extends \Database\Model {

    public static ?string $uidPrefix = "pvr";
    protected static array $schema = [
        "uid"               => "string",
        "order"             => ["type" => "string", "nullable" => true, "default" => null],  // Null for card change
        "uuid"              => "string",           // Customer
        "organisation"      => "string",
        "location"          => "string",
        "provider"          => "string",
        "amount"            => ["type" => "decimal", "nullable" => false, "default" => 1, "precision" => 10, "scale" => 2],
        "currency"          => "string",
        "prid"              => ["type" => "string", "nullable" => true, "default" => null], // Viva transaction ID
        "failure_reason"    => ["type" => "string", "nullable" => true, "default" => null],
        "viva_event_id"     => ["type" => "integer", "nullable" => true, "default" => null],
        "test"              => "tinyInteger",
        "status"            => ["type" => "enum", "default" => "PENDING", "values" => ["PENDING", "REFUNDED", "FAILED"]],
        "refunded_at"       => ["type" => "timestamp", "nullable" => true, "default" => null],
        "refunded_by"       => ["type" => "string", "nullable" => true, "default" => null], // User who marked as refunded
        "metadata"          => ["type" => "text", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["order", "uuid", "organisation", "location", "status"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["metadata"];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "order" => [Orders::tableColumn('uid'), Orders::newStatic()],
            "uuid" => [Users::tableColumn('uid'), Users::newStatic()],
            "organisation" => [Organisations::tableColumn('uid'), Organisations::newStatic()],
            "location" => [Locations::tableColumn('uid'), Locations::newStatic()],
            "provider" => [PaymentProviders::tableColumn('uid'), PaymentProviders::newStatic()],
            "refunded_by" => [Users::tableColumn('uid'), Users::newStatic()],
        ];
    }

}
