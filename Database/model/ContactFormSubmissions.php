<?php
namespace Database\model;

class ContactFormSubmissions extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "name" => "string",
        "email" => "string",
        "subject" => "string",
        "content" => "text",
        "newsletter_consent" => ["type" => "tinyInteger", 'default' => 0],
        "uuid" => ["type" => "string", 'default' => null, "nullable" => true],
        "_csrf" => ["type" => "string", 'default' => null, "nullable" => true],
    ];
    protected static array $indexes = [
        "email"
    ];
    protected static array $uniques = [];


    protected static array $requiredRows = [
    ];
    protected static array $requiredRowsTesting = [];


    public static array $encodeColumns = [
        //Should be fetched using the Meta class
    ];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = ["subject", "content"];
    public static function foreignkeys(): array {
        return [
            "uuid" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}