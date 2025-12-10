<?php

namespace classes\enumerations\links;

class MerchantPublic {
    public string $recovery = 'merchant/password-recovery';
    public string $signup = 'merchant/signup';
    public string $login = 'merchant/login';
    public string $home = '';
    public string $locationPage = 'merchant/{slug}';

    public function getLocationPage(string $slug): string { return str_replace('{slug}', $slug, $this->locationPage); }

//    public string $home = 'merchant';


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