<?php

namespace env\api;

class Viva {


    public static function isSandbox(): bool { return self::$sandbox; }
    public static function sandbox(): void { self::$sandbox = true; }
    public static function live(): void { self::$sandbox = false; }


    public static function clientId(): string { return self::$sandbox ? self::SANDBOX_CLIENT_ID : self::CLIENT_ID; }
    public static function clientSecret(): string { return self::$sandbox ? self::SANDBOX_CLIENT_SECRET : self::CLIENT_SECRET; }
    public static function resellerId(): string { return self::$sandbox ? self::SANDBOX_RESELLER_ID : self::RESELLER_ID; }
    public static function resellerBasicAuthId(string $merchantId): string { return self::resellerId() . ":$merchantId"; }
    public static function resellerApiKey(): string { return self::$sandbox ? self::SANDBOX_RESELLER_API_KEY : self::RESELLER_API_KEY; }
    public static function oAuthUrl(): string { return self::$sandbox ? self::SANDBOX_OAUTH_URL : self::OAUTH_URL; }
    public static function merchantCreateUrl(): string { return self::$sandbox ? self::SANDBOX_MERCHANT_CREATE_URL : self::MERCHANT_CREATE_URL; }
    public static function merchantReadUrl(string $merchantId): string { return (self::$sandbox ? self::SANDBOX_MERCHANT_READ_URL : self::MERCHANT_READ_URL) . $merchantId; }
    public static function sourceCreateUrl(): string { return self::$sandbox ? self::SANDBOX_SOURCE_CREATE_URL : self::SOURCE_CREATE_URL; }
    public static function paymentCreateUrl(string $merchantId): string { return (self::$sandbox ? self::SANDBOX_PAYMENT_CREATE_URL : self::PAYMENT_CREATE_URL) . $merchantId; }
    public static function paymentReadUrl(string $orderId): string { return (self::$sandbox ? self::SANDBOX_PAYMENT_READ_URL : self::PAYMENT_READ_URL) . $orderId; }
    public static function orderReadUrl(string $orderId): string { return (self::$sandbox ? self::SANDBOX_ORDER_READ_URL : self::ORDER_READ_URL) . $orderId; }



    private static bool $sandbox = true;
    private const SANDBOX_CLIENT_ID = "067jg2zsb0g51m923ez87ic03n8isrqughtqpjldn5bu6.apps.vivapayments.com";
    private const SANDBOX_CLIENT_SECRET = "7nUK8kkFW442A3q9Od4r9FmdP6t31K";
    private const SANDBOX_RESELLER_ID = "7e416dfc-aa89-4335-9be5-4b3ce2dc359b";
    private const SANDBOX_RESELLER_API_KEY = "M3GMZ71Jyp4G1Ka2J6k4hMdH699ZUX";
    private const CLIENT_ID = "";
    private const CLIENT_SECRET = "";
    private const RESELLER_ID = "";
    private const RESELLER_API_KEY = "";
    private const SANDBOX_OAUTH_URL = "https://demo-accounts.vivapayments.com/connect/token";
    private const OAUTH_URL = "";
    private const SANDBOX_MERCHANT_CREATE_URL = "https://demo-api.vivapayments.com/isv/v1/accounts";
    private const MERCHANT_CREATE_URL = "";
    private const SANDBOX_MERCHANT_READ_URL = "https://demo-api.vivapayments.com/isv/v1/accounts/";
    private const MERCHANT_READ_URL = "";
    private const SANDBOX_SOURCE_CREATE_URL = "https://demo.vivapayments.com/api/sources";
    private const SOURCE_CREATE_URL = "";
    private const SANDBOX_PAYMENT_CREATE_URL = "https://demo-api.vivapayments.com/checkout/v2/isv/orders?merchantId=";
    private const PAYMENT_CREATE_URL = "";
    private const SANDBOX_PAYMENT_READ_URL = "https://demo.vivapayments.com/api/transactions/?ordercode=";
    private const PAYMENT_READ_URL = "";
    private const SANDBOX_ORDER_READ_URL = "https://demo.vivapayments.com/api/orders/";
    private const ORDER_READ_URL = "";





}