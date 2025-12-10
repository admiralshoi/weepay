<?php

namespace Database\model;

class OidcSessions extends \Database\Model {

    public static ?string $uidPrefix = "oidc";
    protected static array $schema = [
        "uid" => "string",
        "provider" => "string",
        "prid" => ['type' => 'string', 'default' => null, 'nullable' => true],
        "uuid" => ['type' => 'string', 'default' => null, 'nullable' => true],
        "info" => ['type' => 'text', 'default' => null, 'nullable' => true],
        "reason" => ['type' => 'enum', 'default' => 'authenticate', 'values' => ['authenticate', 'signature', 'other']],
        "status" => ['type' => 'enum', 'default' => 'DRAFT', 'values' => ['DRAFT', 'TIMEOUT', 'PENDING', 'CANCELLED', 'ERROR', 'SUCCESS', 'VOID']],
        "expires_at" => ["type" => "bigInteger", "default" => 0],
        "token" => ["type" => 'string', "default" => null, 'nullable' => true],
    ];
    public static array $indexes = [
        "uuid",  "token"
    ];
    public static array $uniques = [
        "prid", "uid"
    ];


    protected static array $requiredRows = [
    ];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = ["info"];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
        ];
    }
}