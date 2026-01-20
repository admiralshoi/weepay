<?php

namespace classes\enumerations\links\api;

class Consumer {

    public ConsumerSupport $support;

    public string $orders = "api/consumer/orders";
    public string $payments = "api/consumer/payments";
    public string $updateProfile = "api/consumer/update-profile";
    public string $updateAddress = "api/consumer/update-address";
    public string $updatePassword = "api/consumer/update-password";
    public string $verifyPhone = "api/consumer/verify-phone";

    // Payment actions
    public string $changeCard = "api/consumer/change-card";
    public string $paymentsByCard = "api/consumer/payments-by-card";
    public string $payNow = "api/consumer/payments/{uid}/pay-now";
    public string $payOrderOutstanding = "api/consumer/orders/{uid}/pay-outstanding";

    function __construct() {
        $this->support = new ConsumerSupport();
    }

    public function changeCardForOrder(string $orderUid): string {
        return "api/consumer/change-card/order/{$orderUid}";
    }

    public function changeCardForPaymentMethod(string $paymentMethodUid): string {
        return "api/consumer/change-card/payment-method/{$paymentMethodUid}";
    }

    public function paymentReceipt(string $paymentUid): string {
        return "api/consumer/payments/{$paymentUid}/receipt";
    }

}

class ConsumerSupport {
    public string $create = "api/consumer/support/create";
    public string $reply = "api/consumer/support/reply";
    public string $close = "api/consumer/support/close";
    public string $reopen = "api/consumer/support/reopen";
}
