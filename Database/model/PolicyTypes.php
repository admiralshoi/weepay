<?php

namespace Database\model;

class PolicyTypes extends \Database\Model {

    public static ?string $uidPrefix = "pt";
    protected static array $schema = [
        "uid" => "string",
        "type" => ["type" => "enum", "values" => ["consumer_privacy", "consumer_terms", "merchant_privacy", "merchant_terms", "cookies"]],
        "display_name" => "string",
        "current_version" => ["type" => "string", "nullable" => true],
        "scheduled_version" => ["type" => "string", "nullable" => true],
        "scheduled_at" => ["type" => "datetime", "nullable" => true],
    ];

    public static array $indexes = ["type"];
    public static array $uniques = ["uid", "type"];

    protected static array $requiredRows = [
        [
            "uid" => "pt_consumer_privacy",
            "type" => "consumer_privacy",
            "display_name" => "Privatlivspolitik (Forbruger)",
        ],
        [
            "uid" => "pt_consumer_terms",
            "type" => "consumer_terms",
            "display_name" => "Handelsbetingelser (Forbruger)",
        ],
        [
            "uid" => "pt_merchant_privacy",
            "type" => "merchant_privacy",
            "display_name" => "Privatlivspolitik (Erhverv)",
        ],
        [
            "uid" => "pt_merchant_terms",
            "type" => "merchant_terms",
            "display_name" => "Handelsbetingelser (Erhverv)",
        ],
        [
            "uid" => "pt_cookies",
            "type" => "cookies",
            "display_name" => "Cookiepolitik",
        ],
    ];

    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [];
    }
}
