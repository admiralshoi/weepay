<?php

namespace Database\model;

class TerminalSession extends \Database\Model {

    public static ?string $uidPrefix = "tses";
    protected static array $schema = [
        "uid" => "string",
        "session" => "string",
        "terminal" => "string",
        "state" => ["type" => "enum", "default" => 'PENDING', 'values' => ['PENDING', 'ACTIVE', 'VOID', 'COMPLETED']],
        "customer" => ["type" => 'string', "default" => null, 'nullable' => true],
        "csrf" => ["type" => 'string', "default" => null, 'nullable' => true],
    ];

    public static array $indexes = ["terminal"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "terminal" => [Terminals::tableColumn("uid"), Terminals::newStatic()],
        ];
    }
}
