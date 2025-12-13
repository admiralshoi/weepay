<?php
namespace Database\model;

class AppMeta extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "name" => "string",
        "value" => "text",
        "type" => "text",
    ];
    protected static array $indexes = [

    ];
    protected static array $uniques = [
        "name"
    ];


    protected static array $requiredRows = [

        [
            "name" => "user_role_settings",
            "value" => '[]',
            "type" => "array",
        ],
        [
            "name" => "payment_providers",
            "value" => '["viva"]',
            "type" => "array",
        ],
        [
            "name" => "active_payment_providers",
            "value" => '["viva"]',
            "type" => "array",
        ],
        [
            "name" => "available_payment_methods",
            "value" => '{"viva": ["smart_checkout"]}',
            "type" => "array",
        ],
        [
            "name" => "default_payment_provider",
            "value" => 'viva',
            "type" => "string",
        ],
        [
            "name" => "default_currency",
            "value" => 'DKK',
            "type" => "string",
        ],
        [
            "name" => "default_country",
            "value" => 'DK',
            "type" => "string",
        ],
        [
            "name" => "currencies",
            "value" => '["DKK","EUR","GBP","RON","PLN","CZK","HUF","SEK","BGN"]',
            "type" => "array",
        ],
        [
            "name" => "organisation_roles",
            "value" => '["owner","admin","team_manager","analyst"]',
            "type" => "array",
        ],
        [
            "name" => "location_roles",
            "value" => '["store_manager","team_manager","cashier"]',
            "type" => "array",
        ],
        [
            "name" => "taskManager",
            "value" => '{"subscriptionRevert": {"ttl": 600}, "subscriptionCreateFailedCancel": {"ttl": 600}, "voidInvoice": {"ttl": 600}}',
            "type" => "array",
        ],
        [
            "name" => "paymentPlans",
            "value" => '{"direct": {"enabled": true, "title": "Betal Nu", "caption": "Fuld betaling med det samme", "installments": 1, "start": "now"}, "pushed": {"enabled": true, "title": "Betal d. 1. i Måneden", "caption": "Udskyd betalingen til næste måned", "installments": 1, "start": "first day of next month"}, "installments": {"enabled": true, "title": "Del i 4 Rater", "caption": "Betal over 90 dage", "installments": 4, "start": "now"}}',
            "type" => "array",
        ],
        [
            "name" => "resellerFee",
            "value" => '4.95',
            "type" => "float",
        ],
        [
            "name" => "oidc_session_lifetime",
            "value" => '300',
            "type" => "int",
        ],
    ];
    protected static array $requiredRowsTesting = [];


    public static array $encodeColumns = [
        //Should be fetched using the Meta class
    ];
    //Not for columns that uses encode columns (does not support array converting)
    public static array $encryptedColumns = [];
    public static function foreignkeys(): array {
        return [
        ];
    }
}