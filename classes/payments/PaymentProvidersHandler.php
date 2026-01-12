<?php

namespace classes\payments;

use classes\utility\Crud;
use Database\model\PaymentProviders;

class PaymentProvidersHandler extends Crud {

    function __construct() {
        parent::__construct(PaymentProviders::newStatic(), "payment_providers");
    }

    /**
     * Get provider by name
     *
     * @param string $name Provider name (e.g. 'viva')
     * @return object|null
     */
    public function getByName(string $name): ?object {
        return $this->getFirst(['name' => $name]);
    }

    /**
     * Get all enabled providers
     *
     * @return \Database\Collection
     */
    public function getEnabled(): \Database\Collection {
        return $this->getByX(['enabled' => 1]);
    }

}
