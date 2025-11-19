<?php

namespace classes\organisations;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Locations;

class LocationHandler extends Crud {


    function __construct() {
        parent::__construct(Locations::newStatic(), "location");
    }


    public function getBySlug(string $slug, array $fields = []): ?object {
        return $this->getFirst(['slug' => $slug], $fields);
    }

    public function getMyLocations(?string $uuid = null, array $fields = []): ?object {
        if($uuid === null) $uuid = __oUuid();
        return $this->getByX(['uuid' => $uuid, 'status' => ['DRAFT', 'ACTIVE', 'INACTIVE']], $fields);
    }














}