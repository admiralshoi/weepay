<?php

namespace classes\enumerations\links\api\locations;

class Locations {

    public Team $team;


    public string $merchantHeroImage = "api/merchant/location/{location_id}/hero-image";
    public string $merchantLogo = "api/merchant/location/{location_id}/logo";
    public string $savePageDraft = "api/merchant/location/{location_id}/page-draft";
    public string $publishPageDraft = "api/merchant/location/{location_id}/page-draft/publish";






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
