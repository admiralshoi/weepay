<?php

namespace classes\enumerations\links\api\organisation;

class Team {

    public Role $role;
    public ScopedLocations $scopedLocations;
    public string $update = "api/organisations/team/update";
    public string $invite = "api/organisations/team/invite";
    public string $respond = "api/organisations/team/respond";






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