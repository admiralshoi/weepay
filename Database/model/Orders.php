<?php

namespace Database\model;


use JetBrains\PhpStorm\ArrayShape;

class Orders extends \Database\Model {

    public static ?string $uidPrefix = "ord";
    protected static array $schema = [
        "uid"      => "string",
        "uuid"      => ["type" => "string", "nullable" => true, "default" => null],
        "location"      => "string",
        "organisation"      => "string",
        "provider"    => "string",
        "status"            => ["type" => "enum", "default" => "DRAFT", "values" => ["DRAFT", "PENDING", "COMPLETED", "CANCELLED", "EXPIRED", "REFUNDED", "VOIDED"]],
        "amount"      => ["type" => "decimal", "nullable" => false, "default" => 0, "precision" => 10, "scale" => 2],
        "amount_refunded"      => ["type" => "decimal", "nullable" => false, "default" => 0, "precision" => 10, "scale" => 2],
        "currency" => "string",
        "billing_details"       => ["type" => "text", "nullable" => true, "default" => null],
        "source_code"       => ["type" => "string", "nullable" => true, "default" => null],
        "payment_plan"       => ["type" => "string", "nullable" => true, "default" => null],
        "caption"       => ["type" => "string", "nullable" => true, "default" => null],
        "fee"       => ["type" => "float", "default" => 0],
        "fee_amount"       => ["type" => "decimal", "default" => 0],
        "cardFee"       => ["type" => "decimal", "default" => 0],
        "paymentProviderFee"       => ["type" => "decimal", "default" => 0],
        "credit_score"       => ["type" => "float", "default" => 0],
        "prid"       => ["type" => "string", "nullable" => true, "default" => null],
        "terminal_session"       => ["type" => "string", "nullable" => true, "default" => null],
        "test" => "tinyInteger",
    ];

    public static array $indexes = ["uuid", "location", "organisation"];
    public static array $uniques = ["uid", "prid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = ["billing_details"];
    public static array $encryptedColumns = [];


    public static function foreignkeys(): array {
        return [
            "location" => [Locations::tableColumn('uid'), Locations::newStatic()],
            "organisation" => [Organisations::tableColumn('uid'), Organisations::newStatic()],
            "provider" => [PaymentProviders::tableColumn('uid'), PaymentProviders::newStatic()],
            "uuid" => [Users::tableColumn('uid'), Users::newStatic()],
            "terminal_session" => [TerminalSession::tableColumn('uid'), TerminalSession::newStatic()],
        ];
    }

}
