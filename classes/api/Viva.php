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
//        "DKK" => "208",
        "DKK" => "978",
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



    public function createConnectedMerchant(
        string $returnUrl,
        string $email,
        string $name,
        ?string $color = null,
        ?string $logoUrl = null,
        ?string $token = null
    ): ?array {
        $requests =  Methods::requests();
        $branding = ['partnerName' => $name];
        if(!empty($color)) $branding['primaryColor'] = $color;
        if(!empty($logoUrl)) $branding['logoUrl'] = $logoUrl;
        $payload = [
            'email' => $email,
            'returnUrl' => $returnUrl,
            'branding' => $branding,
        ];
        if(empty($token)) $token = $this->fetchToken($requests);
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


    public function getConnectedMerchant(string $accountId, ?string $token = null): ?array {
        $requests =  Methods::requests();
        if(empty($token)) $token = $this->fetchToken($requests);
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

    public function getPayment(string $merchantId, string $orderId): ?array {
        $requests =  Methods::requests();
        $requests->basicAuth(API::resellerBasicAuthId($merchantId), API::resellerApiKey());
        $requests->get(API::paymentReadUrl($orderId));

        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }








}