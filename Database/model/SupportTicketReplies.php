<?php

namespace Database\model;

class SupportTicketReplies extends \Database\Model {

    public static ?string $uidPrefix = "tktr";

    protected static array $schema = [
        "uid"      => "string",
        "ticket"   => "string",
        "user"     => "string",
        "message"  => ["type" => "text", "nullable" => false],
        "is_admin" => ["type" => "tinyInteger", "default" => 0],
    ];

    public static array $indexes = ["ticket", "user"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "ticket" => [SupportTickets::tableColumn("uid"), SupportTickets::newStatic()],
            "user" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }

}
