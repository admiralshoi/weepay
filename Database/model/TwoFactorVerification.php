<?php
namespace Database\model;

class TwoFactorVerification extends \Database\Model {
    public static ?string $uidPrefix = "2fa";
    protected static array $schema = [
        "uid" => "string",
        "user" => ["type" => "string"],
        "type" => ["type" => "string"], // 'sms' or 'email'
        "purpose" => ["type" => "string"], // 'phone_verification', 'email_verification', '2fa_auth', 'device_verification'
        "code" => ["type" => "string"], // 6-digit code
        "identifier" => ["type" => "string"], // phone number or email being verified
        "message" => ["type" => "text", "default" => null, "nullable" => true], // The actual message sent (encrypted)
        "verified" => ["type" => "tinyInteger", "default" => 0], // 0 = pending, 1 = verified
        "expires_at" => ["type" => "integer"], // Unix timestamp
        "verified_at" => ["type" => "integer", "default" => null, "nullable" => true],
        "phone_country_code" => ["type" => "string", "default" => null, "nullable" => true],
    ];

    public static array $indexes = [
        "user", "code", "identifier"
    ];

    public static array $uniques = [
        "uid"
    ];

    protected static array $requiredRows = [];
    protected static array $requiredRowsTesting = [];
    public static array $encodeColumns = [];
    public static array $encryptedColumns = [
        "message", "identifier"
    ];

    public static function foreignkeys(): array {
        return [
            "user" => [Users::tableColumn("uid"), Users::newStatic()]
        ];
    }
}
