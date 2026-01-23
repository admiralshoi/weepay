<?php

namespace classes\enumerations\links\api;

class Checkout {
    public string $merchantPosGetSessions = "api/terminals/{id}/sessions";
    public string $terminalSession = "api/terminal-sessions/{id}";
    public string $consumerBasket = "api/terminal-sessions/{id}/basket/consumer";
    public string $merchantPosBasket = "api/terminal-sessions/{id}/basket/merchant";

    public string $merchantVoidBasket = "api/terminal-sessions/{id}/basket/void";
    public string $basketHash = "api/terminal-sessions/{id}/basket/hash";
    public string $todaysSales = "api/locations/{location}/todays-sales";






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