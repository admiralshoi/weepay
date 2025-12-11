<?php
namespace Database\model;

class VivaConnectedAccounts extends \Database\Model {
    public static ?string $uidPrefix = "viva";
    protected static array $schema = [
        "uid" => "string",
        "email" => "string",
        "organisation" => ["type" => "string", "default" => null, "nullable" => true],
        "prid" => ["type" => "string", "default" => null, "nullable" => true],
        "link" => ["type" => "text", "default" => null, "nullable" => true],
        "state" => ["type" => "enum","default" => "DRAFT", "values" => ["DRAFT", "VOID", "COMPLETED", "REMOVED"]],
    ];
    public static array $indexes = [
        "email", "organisation"
    ];
    public static array $uniques = [
        "uid"
    ];


    protected static array $requiredRows = [
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "organisation" => [Organisations::tableColumn("uid"), Organisations::newStatic()]
        ];
    }
}