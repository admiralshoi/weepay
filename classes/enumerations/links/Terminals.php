<?php

namespace classes\enumerations\links;

class Terminals {


    public string $main = "terminals";
    public string $terminalCheckoutStart = "merchant/{slug}/checkout?tid={id}";
    public string $terminalPosStart = "merchant/{slug}/terminal/{id}/pos/start";
    public string $terminalPosDetails = "merchant/{slug}/terminal/{id}/pos/{tsid}/details";
    public string $terminalPosCheckout = "merchant/{slug}/terminal/{id}/pos/{tsid}/checkout";
    public string $terminalPosFulfilled = "merchant/{slug}/terminal/{id}/pos/{tsid}/fulfilled";
    public string $consumerChoosePlan = "merchant/{slug}/checkout/{tsid}/choose-plan";
    public string $terminalQr = "merchant/terminals/{id}/qr";
    public function qr(string $id): string { return str_replace("{id}" , $id, $this->terminalQr); }
    public function posDetails(string $slug, string $id, string $tsId): string {
        return str_replace(["{slug}", "{id}", "{tsid}"] , [$slug, $id, $tsId], $this->terminalPosDetails);
    }
    public function posCheckout(string $slug, string $id, string $tsId): string {
        return str_replace(["{slug}", "{id}", "{tsid}"] , [$slug, $id, $tsId], $this->terminalPosCheckout);
    }
    public function posFulfilled(string $slug, string $id, string $tsId): string {
        return str_replace(["{slug}", "{id}", "{tsid}"] , [$slug, $id, $tsId], $this->terminalPosFulfilled);
    }
    public function getConsumerChoosePlan(string $slug, string $tsId): string {
        return str_replace(["{slug}", "{tsid}"] , [$slug, $tsId], $this->consumerChoosePlan);
    }
    public function posStart(string $slug, string $id): string { return str_replace(["{slug}", "{id}"] , [$slug, $id], $this->terminalPosStart); }
    public function checkoutStart(string $slug, string $id): string { return str_replace(["{slug}", "{id}"] , [$slug, $id], $this->terminalCheckoutStart); }


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