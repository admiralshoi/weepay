<?php

namespace Database\model;

class CookieConsents extends \Database\Model {
    public static ?string $uidPrefix = "cc";
    protected static array $schema = [
        "uid" => "string",
        "user" => ["type" => "string", "nullable" => true],
        "ip_address" => "string",
        "user_agent" => ["type" => "string", "nullable" => true],
        "consented_at" => "datetime",
    ];

    public static array $indexes = [
        "ip_address",
        "user",
    ];

    public static array $uniques = [
        "uid",
    ];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
