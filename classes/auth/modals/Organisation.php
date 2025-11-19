<?php

namespace classes\auth\modals;

class Organisation {
    public int $id;
    public string $name;
    public ?Address $address;

    public function __construct() {
        $this->address = new Address();
    }

    public function getPermissions(): array {


    }
}