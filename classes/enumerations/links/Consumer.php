<?php

namespace classes\enumerations\links;

class Consumer {

    public ConsumerPublic $public;
    public string $dashboard = "dashboard";
    public string $orders = "orders";
    public string $orderDetail = "order";
    public string $payments = "payments";
    public string $receipts = "receipts";
    public string $upcomingPayments = "upcoming-payments";
    public string $outstandingPayments = "outstanding-payments";
    public string $settings = "settings";
    public string $support = "support";

    public function orderDetail(string $orderId): string {
        return "order/{$orderId}";
    }

    public function paymentDetail(string $paymentId): string {
        return "payments/{$paymentId}";
    }

    public function locationDetail(string $locationId): string {
        return "location/{$locationId}";
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