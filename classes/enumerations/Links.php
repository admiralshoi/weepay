<?php
namespace classes\enumerations;



use classes\enumerations\links\Consumer;
use classes\enumerations\links\Merchant;
use classes\enumerations\links\policies\Policies;
use classes\enumerations\links\Support;
use classes\enumerations\links\api\Api;
use classes\enumerations\links\app\App;

final class Links {
    public static Consumer $consumer;
    public static Merchant $merchant;
    public static Policies $policies;
    public static Support $support;
    public static Api $api;
    public static App $app;


    public static function init(): void {

        $ref = new \ReflectionClass(self::class);

        foreach ($ref->getProperties(\ReflectionProperty::IS_STATIC) as $prop) {
            $name = $prop->getName();

            // Skip the internal flag
            if ($name === 'initialized') {
                continue;
            }

            // Only initialize typed properties
            $type = $prop->getType();
            if ($type && !$prop->isInitialized()) {

                $className = $type->getName();

                // Only auto-init if the type is a class, not scalar
                if (class_exists($className)) {
                    self::${$name} = new $className();
                }
            }
        }

    }

}
