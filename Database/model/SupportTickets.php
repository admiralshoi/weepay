<?php

namespace Database\model;

class SupportTickets extends \Database\Model {

    public static ?string $uidPrefix = "tkt";

    protected static array $schema = [
        "uid"          => "string",
        "user"         => "string",
        "type"         => ["type" => "enum", "default" => "consumer", "values" => ["consumer", "merchant"]],
        "on_behalf_of" => ["type" => "enum", "default" => "personal", "values" => ["personal", "organisation"]],
        "organisation" => ["type" => "string", "nullable" => true, "default" => null],
        "category"     => "string",
        "subject"      => "string",
        "message"      => ["type" => "text", "nullable" => false],
        "status"       => ["type" => "enum", "default" => "open", "values" => ["open", "closed"]],
        "closed_at"    => ["type" => "datetime", "nullable" => true, "default" => null],
        "closed_by"    => ["type" => "string", "nullable" => true, "default" => null],
    ];

    public static array $indexes = ["user", "type", "status", "category"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn("uid"), Users::newStatic()],
            "closed_by" => [Users::tableColumn("uid"), Users::newStatic()],
            "organisation" => [Organisations::tableColumn("uid"), Organisations::newStatic()],
        ];
    }

}
