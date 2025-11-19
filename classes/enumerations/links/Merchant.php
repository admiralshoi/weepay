<?php

namespace classes\enumerations\links;

class Merchant {

    public MerchantPublic $public;
    public Organisation $organisation;
    public string $dashboard = "dashboard";
    public string $orders = "orders";
    public string $terminals = "terminals";
    public string $team = "team";
    public string $locations = "locations";
    public string $payouts = "payouts";
    public string $pendingPayments = "pending-payments";
    public string $locationPages = "location-pages";
    public string $customers = "customers";
    public string $settings = "settings";
    public string $reports = "reports";
    public string $support = "support";

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