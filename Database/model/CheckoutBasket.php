<?php

namespace Database\model;

class CheckoutBasket extends \Database\Model {

    public static ?string $uidPrefix = "chkb";
    protected static array $schema = [
        "uid" => "string",
        "name" => "string",
        "price" => "decimal",
        "terminal_session" => "string",
        "currency" => "string",
        "note" => ["type" => 'string', "default" => null, 'nullable' => true],
        "status" => ["type" => "enum", "default" => 'DRAFT', 'values' => ['DRAFT', 'VOID', 'FULFILLED']],
        "created_by" => ["type" => 'string', "default" => null, 'nullable' => true],
    ];

    public static array $indexes = ["terminal_session", "status", "created_by"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "terminal_session" => [TerminalSession::tableColumn("uid"), TerminalSession::newStatic()],
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
