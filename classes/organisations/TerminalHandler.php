<?php

namespace classes\organisations;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Terminals;
use classes\Methods;

class TerminalHandler extends Crud {


    function __construct() {
        parent::__construct(Terminals::newStatic(), "terminal");
    }


    public function getByTerminalAndLocationId(string $terminalId, string $locationId, array $fields = []): ?object {
        return $this->getFirst(['uid' => $terminalId, 'location' => $locationId], $fields);
    }

    public function getMyTerminals(?string $uuid = null, array $fields = []): ?object {
        if($uuid === null) $uuid = __oUuid();
        return $this->getByX(['uuid' => $uuid, 'status' => ['DRAFT', 'ACTIVE', 'INACTIVE']], $fields);
    }












}