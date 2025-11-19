<?php

namespace classes\api;
use classes\Methods;
use env\api\Google as API;

class ReCaptcha {

    public function siteKey(): ?string { return API::RECAPTCHA_PK; }

    public function getTokenData(string $token): array {
        $requests =  Methods::requests();
        $queryParams = [
            "secret" => API::RECAPTCHA_SK,
            "response" => $token,
        ];
        $requests->get(API::RECAPTCHA_FETCH_TOKEN_URL . "?" . http_build_query($queryParams));
        return $requests->getResponse();
    }

    public function validate(?array $tokenData, int|float $passingScore = .5, ?string $requiredAction = null): bool {
        if(!is_array($tokenData)) return false;
        if($requiredAction !== null && (!array_key_exists("action", $tokenData) || $tokenData['action'] !== $requiredAction)) return false;
        if(!array_key_exists("score", $tokenData)) return false;
        return $tokenData['score'] >= $passingScore;
    }



}