<?php

namespace Database\model;

class Tasks extends \Database\Model {

    public static ?string $uidPrefix = "tsk";
    protected static array $schema = [
        "uid" => "string",
        "status"  => ["type" => "enum", "default" => "open", "values" => ["open", "closed"]],
        "type"  => ["type" => "enum", "default" => null, 'nullable' => true, "values" => ["revert_subscription", "void_invoice"]],
        "object_id" => "string",
        "execute_at" => "bigInteger",
        "executed_at" => ['type' => "bigInteger", 'default' => null, 'nullable' => true],
        "data" => ["type" => "text", "default" => null, 'nullable' => true],
        "result" => ["type" => "text", "default" => null, 'nullable' => true],
    ];
    public static array $indexes = ['object_id'];
    public static array $uniques = [
        "uid"
    ];


    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = ['data', 'result'];
    public static array $encryptedColumns = [];  //Not for columns that uses encode columns (does not support array converting)

    public static function foreignkeys(): array {
        return [
        ];
    }
}