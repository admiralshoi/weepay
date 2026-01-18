<?php

namespace Database\model;

class Payments extends \Database\Model {

    public static ?string $uidPrefix = "pmt";
    protected static array $schema = [
        "uid"               => "string",
        "order"             => "string",
        "uuid"              => "string",
        "organisation"      => "string",
        "location"          => "string",
        "provider"          => "string",
        "amount"            => ["type" => "decimal", "nullable" => false, "default" => 0, "precision" => 10, "scale" => 2],
        "isv_amount"        => ["type" => "decimal", "nullable" => false, "default" => 0, "precision" => 10, "scale" => 2],
        "cardFee"       => ["type" => "decimal", "default" => 0],
        "paymentProviderFee"       => ["type" => "decimal", "default" => 0],
        "currency"          => "string",
        "installment_number" => ["type" => "integer", "default" => 1],
        "due_date"          => ["type" => "timestamp", "nullable" => true, "default" => null],
        "paid_at"           => ["type" => "timestamp", "nullable" => true, "default" => null],
        "status"            => ["type" => "enum", "default" => "PENDING", "values" => ["PENDING", "PAST_DUE", "SCHEDULED", "COMPLETED", "FAILED", "CANCELLED", "REFUNDED", "VOIDED"]],
        "prid"              => ["type" => "string", "nullable" => true, "default" => null],
        "initial_transaction_id" => ["type" => "string", "nullable" => true, "default" => null],
        "failure_reason"    => ["type" => "string", "nullable" => true, "default" => null],
        "metadata"          => ["type" => "text", "nullable" => true, "default" => null],
        "test"              => "tinyInteger",
        "processing_at"     => ["type" => "timestamp", "nullable" => true, "default" => null],
        "scheduled_at"      => ["type" => "timestamp", "nullable" => true, "default" => null],
        "attempts"          => ["type" => "integer", "default" => 0],
    ];

    public static array $indexes = ["order", "uuid", "organisation", "location", "status", "due_date"];
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
        ];
    }

}
