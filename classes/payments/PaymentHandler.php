<?php

namespace classes\payments;


use features\Settings;
use stringEncode\Exception;
use classes\payments\stripe\StripeHandler;

class PaymentHandler {

    private ?string $provider = null;
    private ?string $paymentMethod = null;

    public function setPaymentProvider(string $provider): void {
        if(!in_array($provider, Settings::$app->active_payment_providers))
            throw new Exception("Unavailable payment provider: $provider");
        $this->provider = $provider;
    }

    public function setPaymentMethod(string $method): void {
        if(!in_array($method, Settings::$app->available_payment_methods[$this->provider]))
            throw new Exception("Unavailable payment method: $method for provider: " . $this->provider);
        $this->paymentMethod = $method;
    }


    public function handler(string $provider = null): null|StripeHandler {
        if(empty($this->provider)) $this->provider = $provider;
        if(empty($this->provider)) return null;
        return match ($this->provider) {
            default => null,
            "stripe" => new StripeHandler()
        };
    }

}