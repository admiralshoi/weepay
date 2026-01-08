<?php

namespace classes\enumerations\links;

class Merchant {

    public MerchantPublic $public;
    public Organisation $organisation;
    public Locations $locations;
    public Terminals $terminals;
    public string $dashboard = "dashboard";
    public string $orders = "orders";
    public string $payments = "payments";
    public string $payouts = "payouts";
    public string $pendingPayments = "pending-payments";
    public string $pastDuePayments = "past-due-payments";
    public string $locationPages = "location-pages";
    public string $customers = "customers";
    public string $settings = "settings";
    public string $reports = "reports";
    public string $support = "support";
    public string $accessDenied = "access-denied";

    public function orderDetail(string $orderId): string {
        return "orders/{$orderId}";
    }

    public function customerDetail(string $customerId): string {
        return "customers/{$customerId}";
    }

    public function paymentDetail(string $paymentId): string {
        return "payments/{$paymentId}";
    }

    function __construct() {
        $ref = new \ReflectionClass(self::class);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue; // skip static
            }


            $type = $prop->getType();
            if (!$type) {
                continue; // skip untyped
            }

            // Skip if already initialized (PHP 8)
            if ($prop->isInitialized($this)) {
                continue;
            }

            $className = $type->getName();

            // We only auto-init class types, not scalar types
            if (class_exists($className)) {
                $prop->setAccessible(true);
                $prop->setValue($this, new $className());
            }
        }
    }

}