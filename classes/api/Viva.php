<?php

namespace classes\api;
use classes\enumerations\Links;
use classes\http\Requests;
use classes\Methods;
use env\api\Viva as API;
use features\Settings;

class Viva {


    function __construct(?bool $sandbox = null) {
        if($sandbox !== null) {
            if($sandbox) $this->sandbox();
            else $this->live();
        }
    }

    public function sandbox(): static {
        API::sandbox();
        return $this;
    }
    public function live(): static {
        API::live();
        return $this;
    }

    const ISO_CURRENCIES = [
        "DKK" => "208",
        "EUR" => "978",
        "GBP" => "826",
        "RON" => "946",
        "PLN" => "985",
        "CZK" => "203",
        "HUF" => "348",
        "SEK" => "752",
        "BGN" => "975",
    ];


    private function fetchToken(?Requests $requests = null): ?string {
        if($requests === null) $requests =  Methods::requests();
        $authBody = ['grant_type' => 'client_credentials'];
        $requests->basicAuth(API::clientId(), API::clientSecret());
        $requests->setHeaderContentTypeFormEncoded();
        $requests->setBody($authBody);
        $requests->post(API::oAuthUrl());

        $response = $requests->getResponse();
        $token = nestedArray($requests->getResponse(), ['access_token']);
        if(empty($token)) {
            errorLog($response, 'viva-failed-token-authentication');
        }
        return $token;
    }



    public function createConnectedMerchantAccount(
        string $email,
        ?string $returnUrl = null,
        ?string $name = null,
        ?string $color = null,
        ?string $logoUrl = null,
    ): ?array {
        $requests =  Methods::requests();
        $branding = ["partnerName" => !empty($name) ? $name : "WeePay"];
        $branding['primaryColor'] = !empty($color) ? $color : "#f6f9fc";
        $branding['logoUrl'] = !empty($logoUrl) ? $logoUrl : "https://wee-pay.dk/public/media/logos/weepay_pos.svg";
        $payload = [
            'email' => $email,
            'returnUrl' => !empty($returnUrl) ? $returnUrl : __url(Links::$merchant->organisation->home),
            "branding" => $branding,
        ];

        if(empty($token)) $token = $this->fetchToken();
        if(empty($token)) {
            //set some error somewhere
            return null;
        }
        $requests->setBearerToken($token);
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        $requests->post(API::merchantCreateUrl());

        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }


    public function getConnectedMerchant(string $accountId): ?array {
        $requests =  Methods::requests();
        $token = $this->fetchToken();
        if(empty($token)) {
            //set some error somewhere
            return null;
        }
        $requests->setBearerToken($token);
        $requests->get(API::merchantReadUrl($accountId));

        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }




    public function createSource(
        string $merchantId,
        string $sourceCode,
        string $slug,
//        string $domain,
//        string $pathSuccess,
//        string $pathFailure,
    ): bool {
        $requests =  Methods::requests();
        $payload = [
            'sourceCode' => $sourceCode,
            'name' => $slug,
            'domain' => SITE_NAME,
            'pathSuccess' => Links::$checkout->createMerchantCallbackPath($slug),
            'pathFail' => Links::$checkout->createMerchantCallbackPath($slug),
            "isSecure" => true
        ];
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        $requests->post(API::sourceCreateUrl());

        return $requests->getResponseCode() < 300;
    }

    public function checkoutUrl(string $orderCode): string {
        return API::checkoutUrl($orderCode);
    }




    public function createPayment(
        string $merchantId,
        string|int|float $amount,
        string $sourceCode,
        object $user,
        string $dynamicDescriptor,
        string $customerTrnsNote,
        string $merchantTrnsNote,
        ?string $currency = null,
        bool $allowRecurring = false,
        bool $preAuth = false,
        ?array $tags = null,
        ?string $resellerSourceCode = null,
        null|string|int|float $resellerFee = null,
        ?string $token = null
    ): ?array {
        $payload = [
            'sourceCode' =>$sourceCode,
            'amount' => (float)$amount * 100,
            'dynamicDescriptor' => $dynamicDescriptor,
            'customerTrns' => $customerTrnsNote,
            'merchantTrns' => $merchantTrnsNote,
            'allowRecurring' => $allowRecurring,
            'preauth' => $preAuth,
            'disableCash' => true,
            'disableWallet' => true,
            "customer" => [
                "email" => $user->email,
                "phone" => $user->phone,
                "fullName" => $user->full_name,
                "countryCode" => strtoupper($user->lang),
            ]
        ];
        if(!empty($currency) && array_key_exists($currency, self::ISO_CURRENCIES)) $payload['currencyCode'] = self::ISO_CURRENCIES[$currency];
        if(!empty($resellerSourceCode)) $payload['resellerSourceCode'] = $resellerSourceCode;
        if(!empty($tags)) $payload['tags'] = $tags;
        if(empty($resellerFee)) $resellerFee = Settings::$app->resellerFee;
        if(!empty($resellerFee)) {
            $resellerFee = (float)$resellerFee;
            if($resellerFee > 0) $resellerFee /= 100;
            $payload['isvAmount'] = ceil($payload['amount'] * $resellerFee);
        }


        if(empty($token)) $token = $this->fetchToken();
        if(empty($token)) return null;
        $requests =  Methods::requests();
        $requests->setBearerToken($token);
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        testLog($payload, 'create-payment-payload');

        $requests->post(API::paymentCreateUrl($merchantId));

        testLog([
            "url" => API::paymentCreateUrl($merchantId),
            "token" => $token,
            "headers" => $requests->getRequestHeaders(),
            "response_headers" => $requests->getHeaders(),
            "response_body" => $requests->getResponse(),
        ], 'create-payment-response');

        $response = $requests->getResponse();
        if(nestedArray($response, ['status']) === 'error') {
            errorLog($response, 'viva-failed-payment-creation');
        }
        if(is_null($response)) {
            errorLog($requests->getHeaders(), 'viva-failed-payment-creation-header');
        }
        return $response;
    }


    public function getOrder(string $merchantId, string $orderId): ?array {
        $requests =  Methods::requests();
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->get(API::orderReadUrl($orderId));

        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }

    public function getPaymentByOrderId(string $merchantId, string $orderId): ?array {
        $requests =  Methods::requests();
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->get(API::paymentByOrderIdReadUrl($orderId));

        return $requests->getResponse();
    }

    public function getPayment(string $merchantId, string $trxId): ?array {
        $requests =  Methods::requests();
        $token = $this->fetchToken();
        $requests->setBearerToken($token);
        $requests->get(API::paymentReadUrl($trxId, $merchantId));


        return $requests->getResponse();
    }


    /**
     * Charge a recurring payment using a previous transaction ID
     * The initial transaction must have been created with allowRecurring=true
     *
     * @param string $merchantId The merchant's Viva account ID
     * @param string $initialTransactionId The transaction ID from the initial payment (used as orderCode)
     * @param int|float|string $amount Amount in major currency units (e.g., 100.00 for 100 DKK)
     * @param string|null $sourceCode Optional source code (defaults to initial transaction's source)
     * @param string|null $merchantTrns Optional merchant reference
     * @param string|null $customerTrns Optional customer-facing description
     * @param string|null $currency Optional 3-letter currency code (e.g., 'DKK')
     * @param int|float|string|null $resellerFee Optional ISV fee amount
     * @return array|null Response array or null on failure
     */
    public function chargeRecurring(
        string $merchantId,
        string $initialTransactionId,
        int|float|string $amount,
        ?string $sourceCode = null,
        ?string $merchantTrns = null,
        ?string $customerTrns = null,
        ?string $currency = null,
        int|float|string|null $resellerFee = null
    ): ?array {
        $requests = Methods::requests();
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->setHeaderContentTypeJson();

        // Build payload - transaction ID goes in URL, not body
        $payload = [
            'amount' => (int)((float)$amount * 100),
        ];

        if (!empty($sourceCode)) $payload['sourceCode'] = $sourceCode;
        if (!empty($merchantTrns)) $payload['merchantTrns'] = $merchantTrns;
        if (!empty($customerTrns)) $payload['customerTrns'] = $customerTrns;
        if (!empty($currency) && array_key_exists($currency, self::ISO_CURRENCIES)) {
            $payload['CurrencyCode'] = self::ISO_CURRENCIES[$currency];
        }

        // Use passed ISV amount directly (from payment record, already in major currency units)
        // Convert to cents for Viva API
        if (!empty($resellerFee)) {
            $payload['isvAmount'] = (int)((float)$resellerFee * 100);
        }

        $requests->setBody($payload);
        // POST /api/transactions/{initialTransactionId}
        $requests->post(API::recurringPaymentUrl($initialTransactionId));

        $response = $requests->getResponse();
        if (is_null($response) || (isset($response['ErrorCode']) && $response['ErrorCode'] !== 0)) {
            errorLog([
                'payload' => $payload,
                'response' => $response,
                'headers' => $requests->getHeaders(),
            ], 'viva-recurring-payment-failed');
        }

        return $response;
    }


    /**
     * Cancel an open payment order (before it has been paid)
     *
     * @param string $merchantId The merchant's Viva account ID
     * @param string $orderCode The order code to cancel
     * @return array|null Response array or null on failure
     */
    public function cancelOrder(string $merchantId, string $orderCode): ?array {
        $requests = Methods::requests();
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->delete(API::cancelOrderUrl($orderCode));

        $response = $requests->getResponse();
        if (is_null($response) || $requests->getResponseCode() >= 400) {
            errorLog([
                'orderCode' => $orderCode,
                'response' => $response,
                'responseCode' => $requests->getResponseCode(),
                'headers' => $requests->getHeaders(),
            ], 'viva-cancel-order-failed');
        }

        return $response;
    }


    /**
     * Refund or cancel a transaction (partial or full)
     * - Same day: performs a cancel/reversal
     * - Previous day: performs a refund
     *
     * @param string $merchantId The merchant's Viva account ID
     * @param string $transactionId The transaction ID to refund
     * @param int|float|string|null $amount Optional amount for partial refund (in major currency units). Omit for full refund.
     * @param string|null $sourceCode Optional source code
     * @param string|null $currency Optional 3-letter currency code
     * @return array|null Response array or null on failure
     */
    public function refundTransaction(
        string $merchantId,
        string $transactionId,
        int|float|string|null $amount = null,
        ?string $sourceCode = null,
        ?string $currency = null
    ): ?array {
        debugLog([
            'method' => 'refundTransaction',
            'merchantId' => $merchantId,
            'transactionId' => $transactionId,
            'amount' => $amount,
            'sourceCode' => $sourceCode,
            'currency' => $currency,
        ], 'VIVA_REFUND_START');

        $requests = Methods::requests();
        $basicAuthId = API::resellerBasicAuthId($merchantId);
        $basicAuthKey = API::resellerApiKey();

        debugLog([
            'basicAuthId' => $basicAuthId,
            'basicAuthKeyLength' => strlen($basicAuthKey),
        ], 'VIVA_REFUND_AUTH');

        $requests->basicAuth($basicAuthId, $basicAuthKey);

        $queryParams = [];
        if (!is_null($amount)) {
            $queryParams['amount'] = (int)((float)$amount * 100);
        }
        if (!empty($sourceCode)) {
            $queryParams['sourceCode'] = $sourceCode;
        }
        if (!empty($currency) && array_key_exists($currency, self::ISO_CURRENCIES)) {
            $queryParams['currencyCode'] = self::ISO_CURRENCIES[$currency];
        }

        debugLog(['queryParams' => $queryParams], 'VIVA_REFUND_PARAMS');

        $url = API::refundTransactionUrl($transactionId);
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        debugLog(['url' => $url], 'VIVA_REFUND_URL');

        $requests->delete($url);

        $responseCode = $requests->getResponseCode();
        $response = $requests->getResponse();
        $responseHeaders = $requests->getHeaders();

        debugLog([
            'responseCode' => $responseCode,
            'response' => $response,
            'responseHeaders' => $responseHeaders,
        ], 'VIVA_REFUND_RESPONSE');

        if (is_null($response) || $responseCode >= 400) {
            errorLog([
                'transactionId' => $transactionId,
                'amount' => $amount,
                'url' => $url,
                'response' => $response,
                'responseCode' => $responseCode,
                'headers' => $responseHeaders,
            ], 'viva-refund-transaction-failed');

            debugLog(['error' => 'Request failed', 'responseCode' => $responseCode], 'VIVA_REFUND_ERROR');
        } else {
            debugLog([
                'success' => true,
                'transactionId' => $response['TransactionId'] ?? null,
            ], 'VIVA_REFUND_SUCCESS');
        }

        return $response;
    }

}