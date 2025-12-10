<?php

namespace classes\enumerations\links;

class Locations {

    public string $main = "locations";
    public string $singleLocation = "locations/{slug}";
    public string $locationMembers = "locations/{slug}/members";
    public string $locationPageBuilder = "locations/{slug}/page-builder";
    public string $locationPreviewPage = "locations/{slug}/page-builder/preview/{id}/page"; //draft id
    public string $locationPreviewCheckout = "locations/{slug}/page-builder/preview/{id}/checkout"; //draft id
    public function previewPage(string $slug, string $id): string { return str_replace(["{slug}", "{id}"] , [$slug, $id], $this->locationPreviewPage); }
    public function previewCheckout(string $slug, string $id): string { return str_replace(["{slug}", "{id}"] , [$slug, $id], $this->locationPreviewCheckout); }
    public function members(string $slug): string { return str_replace("{slug}" , $slug, $this->locationMembers); }
    public function pageBuilder(string $slug): string { return str_replace("{slug}" , $slug, $this->locationPageBuilder); }
    public function setSingleLocation(string $slug): string { return str_replace("{slug}" , $slug, $this->singleLocation); }
    public function mangeTeamDynamic(?string $slug = null): string {
        if(empty($slug)) $slug = '{slug}';
        return "locations/$slug/team";
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