<?php

namespace classes\enumerations\links\api\organisation;

class Organisation {
    public string $vivaConnectedAccount = "api/organisation/connected-account";
    public string $updateWhitelistEnabled = "api/organisation/whitelist/enabled";
    public string $addWhitelistIp = "api/organisation/whitelist/add";
    public string $removeWhitelistIp = "api/organisation/whitelist/remove";
    public string $updateSettings = "api/organisation/settings";

    public Team $team;
    public Reports $reports;





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