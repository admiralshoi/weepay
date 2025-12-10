<?php
namespace classes\enumerations;



use classes\enumerations\links\admin\Admin;
use classes\enumerations\links\checkout\Checkout;
use classes\enumerations\links\Consumer;
use classes\enumerations\links\Merchant;
use classes\enumerations\links\policies\Policies;
use classes\enumerations\links\Support;
use classes\enumerations\links\api\Api;
use classes\enumerations\links\app\App;

final class Links {
    public static Consumer $consumer;
    public static Merchant $merchant;
    public static Admin $admin;
    public static Policies $policies;
    public static Support $support;
    public static Api $api;
    public static App $app;
    public static Checkout $checkout;


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



    public static function toArray(string|array $exclude = []): array {
        $exclude = self::normalizeExclude($exclude);
        $ref = new \ReflectionClass(self::class);
        $result = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_STATIC) as $prop) {
            $name = $prop->getName();

            if ($name === 'initialized') {
                continue;
            }
            if (!$prop->isInitialized()) {
                continue;
            }
            if (self::isExcluded($name, $exclude)) {
                continue;
            }

            $value = $prop->getValue();
            $result[$name] = self::objectToArray($value, $exclude);
        }

        return $result;
    }

    private static function objectToArray($value, array $exclude) {
        if (!is_object($value)) {
            return $value;
        }

        $ref = new \ReflectionClass($value);
        $props = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            if (!$prop->isInitialized($value)) {
                continue;
            }

            $name = $prop->getName();

            if (self::isExcluded($name, $exclude)) {
                continue;
            }

            $prop->setAccessible(true);
            $v = $prop->getValue($value);

            $props[$name] = self::objectToArray($v, $exclude);
        }

        return $props;
    }

    private static function normalizeExclude(string|array $exclude): array {
        if (is_string($exclude)) {
            return [strtolower($exclude)];
        }
        return array_map('strtolower', $exclude);
    }

    private static function isExcluded(string $propName, array $exclude): bool {
        $name = strtolower($propName);
        foreach ($exclude as $word) {
            if ($word !== "" && str_contains($name, $word)) {
                return true;
            }
        }
        return false;
    }


}
