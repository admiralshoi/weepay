<?php

namespace classes\enumerations\links\api\orders;

class Orders {

    public string $list = "api/orders/list";
    public string $locationList = "api/orders/location/{slug}";
    public Payments $payments;
    public Customers $customers;

    function __construct() {
        $this->payments = new Payments();
        $this->customers = new Customers();
    }

    public function locationOrders(string $slug): string {
        return str_replace("{slug}", $slug, $this->locationList);
    }

}

class Payments {
    public string $list = "api/payments/list";

    public function receipt(string $paymentId): string {
        return "api/payments/{$paymentId}/receipt";
    }
}

class Customers {
    public string $list = "api/customers/list";
}
