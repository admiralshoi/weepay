<?php

namespace classes\api;
use env\api\Signicat as API;
use classes\http\Requests;
use classes\Methods;

class Signicat {

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


    private function fetchToken(?Requests $requests = null): ?string {
        if($requests === null) $requests =  Methods::requests();
        $authBody = [
            'grant_type' => 'client_credentials',
            'scope' => 'signicat-api',
        ];
        $requests->basicAuth(API::clientId(), API::clientSecret());
        $requests->setHeaderContentTypeFormEncoded();
        $requests->setBody($authBody);
        $requests->post(API::oAuthUrl());
        $response = $requests->getResponse();

        $token = nestedArray($requests->getResponse(), ['access_token']);
        if(empty($token)) {
            errorLog($response, 'signicat-failed-token-authentication');
        }
        return $token;
    }



    public function createSession(
        string $callbackPath,
        ?array $callbackQuery =  null,
        ?string $token = null
    ): ?array {

        $requests =  Methods::requests();
        $payload = API::sessionCreationBody(
            "$callbackPath/success" . (empty($callbackQuery) ? '' : '?' . http_build_query($callbackQuery)),
            "$callbackPath/error" . (empty($callbackQuery) ? '' : '?' . http_build_query($callbackQuery)),
            "$callbackPath/abort" . (empty($callbackQuery) ? '' : '?' . http_build_query($callbackQuery)),
        );
        if(empty($token)) $token = $this->fetchToken();
        if(empty($token)) {
            //set some error somewhere
            return null;
        }
        $requests->setBearerToken($token);
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        $requests->post(API::sessionCreateUrl());


        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }


    public function getSession(
        string $sessionId,
        ?string $token = null
    ): ?array {
        if(empty($token)) $token = $this->fetchToken();
        if(empty($token)) {
            //set some error somewhere
            return null;
        }
        $requests =  Methods::requests();
        $requests->setBearerToken($token);
        $requests->setHeaderContentTypeJson();
        $requests->get(API::sessionReadUrl($sessionId));

        $response = $requests->getResponse();
        return $response;
        //Find the error response and at right...
//        if(empty($token)) {
//            errorLog($response, 'viva-failed-token-authentication');
//        }
//        return $token;
    }




}