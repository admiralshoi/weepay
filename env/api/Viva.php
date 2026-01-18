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
    public static function orderReadUrl(string $orderId): string { return (self::$sandbox ? self::SANDBOX_ORDER_READ_URL : self::ORDER_READ_URL) . $orderId; }
    public static function checkoutUrl(string $orderId): string { return (self::$sandbox ? self::SANDBOX_CHECKOUT_URL : self::CHECKOUT_URL) . $orderId; }
    public static function paymentByOrderIdReadUrl(string $orderId): string { return (self::$sandbox ? self::SANDBOX_PAYMENT_BY_ORDER_ID_READ_URL : self::PAYMENT_BY_ORDER_ID_READ_URL) . $orderId; }
    public static function paymentReadUrl(string $id, string $merchantId): string {
        return str_replace(
            ['{transactionId}', '{merchantId}'],
            [$id,$merchantId],
            self::$sandbox ? self::SANDBOX_PAYMENT_READ_URL : self::PAYMENT_READ_URL
        );
    }

    /**
     * Recurring payment URL - POST /api/transactions/{transactionId}
     */
    public static function recurringPaymentUrl(string $transactionId): string {
        $baseUrl = self::$sandbox ? self::SANDBOX_RECURRING_PAYMENT_URL : self::RECURRING_PAYMENT_URL;
        return $baseUrl . '/' . $transactionId;
    }

    /**
     * Cancel order URL - DELETE /api/orders/{orderCode}
     */
    public static function cancelOrderUrl(string $orderCode): string {
        return (self::$sandbox ? self::SANDBOX_CANCEL_ORDER_URL : self::CANCEL_ORDER_URL) . $orderCode;
    }

    /**
     * Refund/cancel transaction URL - DELETE /api/transactions/{transactionId}
     */
    public static function refundTransactionUrl(string $transactionId): string {
        return (self::$sandbox ? self::SANDBOX_REFUND_TRANSACTION_URL : self::REFUND_TRANSACTION_URL) . $transactionId;
    }


    private static bool $sandbox = true;
    private const SANDBOX_CLIENT_ID = "067jg2zsb0g51m923ez87ic03n8isrqughtqpjldn5bu6.apps.vivapayments.com";
    private const SANDBOX_CLIENT_SECRET = "7nUK8kkFW442A3q9Od4r9FmdP6t31K";
    private const SANDBOX_RESELLER_ID = "7e416dfc-aa89-4335-9be5-4b3ce2dc359b";
    private const SANDBOX_RESELLER_API_KEY = "M3GMZ71Jyp4G1Ka2J6k4hMdH699ZUX";
    private const CLIENT_ID = "e60a1kur6qjy1rt82hmm9lwyqshvmumb56cenyedv66j7.apps.vivapayments.com";
    private const CLIENT_SECRET = "0nQT56vfkk3t9CC1X9ZzfjS1yj4G4U";
    private const RESELLER_ID = "0fb66aa8-ba5b-49f0-877f-64e8649a3da9";
    private const RESELLER_API_KEY = "tH8KWe94dTa7k9ta6h6o700Rfc2y3D";
    private const SANDBOX_OAUTH_URL = "https://demo-accounts.vivapayments.com/connect/token";
    private const OAUTH_URL = "https://accounts.vivapayments.com/connect/token";
    private const SANDBOX_MERCHANT_CREATE_URL = "https://demo-api.vivapayments.com/isv/v1/accounts";
    private const MERCHANT_CREATE_URL = "https://api.vivapayments.com/isv/v1/accounts";
    private const SANDBOX_MERCHANT_READ_URL = "https://demo-api.vivapayments.com/isv/v1/accounts/";
    private const MERCHANT_READ_URL = "https://api.vivapayments.com/isv/v1/accounts/";
    private const SANDBOX_SOURCE_CREATE_URL = "https://demo.vivapayments.com/api/sources";
    private const SOURCE_CREATE_URL = "https://www.vivapayments.com/api/sources";
    private const SANDBOX_PAYMENT_CREATE_URL = "https://demo-api.vivapayments.com/checkout/v2/isv/orders?merchantId=";
    private const PAYMENT_CREATE_URL = "https://api.vivapayments.com/checkout/v2/isv/orders?merchantId=";
    private const SANDBOX_PAYMENT_READ_URL = "https://demo-api.vivapayments.com/checkout/v2/isv/transactions/{transactionId}?merchantId={merchantId}";
    private const PAYMENT_READ_URL = "https://api.vivapayments.com/checkout/v2/isv/transactions/{transactionId}?merchantId={merchantId}";
    private const SANDBOX_PAYMENT_BY_ORDER_ID_READ_URL = "https://demo.vivapayments.com/api/transactions/?ordercode=";
    private const PAYMENT_BY_ORDER_ID_READ_URL = "https://www.vivapayments.com/api/transactions/?ordercode=";
    private const SANDBOX_ORDER_READ_URL = "https://demo.vivapayments.com/api/orders/";
    private const ORDER_READ_URL = "https://www.vivapayments.com/api/orders/";
    private const SANDBOX_CHECKOUT_URL = "https://demo.vivapayments.com/web/checkout?ref=";
    private const CHECKOUT_URL = "https://www.vivapayments.com/web/checkout?ref=";
    private const SANDBOX_RECURRING_PAYMENT_URL = "https://demo.vivapayments.com/api/transactions";
    private const RECURRING_PAYMENT_URL = "https://www.vivapayments.com/api/transactions";
    private const SANDBOX_CANCEL_ORDER_URL = "https://demo.vivapayments.com/api/orders/";
    private const CANCEL_ORDER_URL = "https://www.vivapayments.com/api/orders/";
    private const SANDBOX_REFUND_TRANSACTION_URL = "https://demo.vivapayments.com/api/transactions/";
    private const REFUND_TRANSACTION_URL = "https://www.vivapayments.com/api/transactions/";

}