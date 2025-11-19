<?php

namespace classes\app;
use classes\Methods;
use classes\payments\stripe\StripeMethods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\Countries;
use Database\model\TaxRates;
use features\Settings;

class CountryHandler extends Crud {


    function __construct() {
        parent::__construct(Countries::newStatic(), "countries");
    }

    /*
     * Utility methods START
     */
    public function getByCountryCode(string $code, array $fields = []): ?object {
        return $this->getFirst(['code' => strtoupper($code)], $fields);
    }
    public function name(string $code): ?string {
        return $this->getColumn(['code' => strtoupper($code)], 'name');
    }

    /*
     * Utility methods END
     */






}