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


    public function setIdle(string $id): bool {
        return $this->update(['state' => 'IDLE', 'session' => null], ['uid' => $id]);
    }
    public function setActiveSession(string $id, string|int|null $sessionShortId): bool {
        return $this->update(['state' => 'ACTIVE', 'session' => $sessionShortId], ['uid' => $id]);
    }







    public function insert(
        string $organisationId,
        string $name,
        string $location,
        string $status = "DRAFT",
    ): ?string {
        $params = [
            "uuid" => $organisationId,
            "name" => $name,
            "location" => $location,
            "status" => $status,
        ];

        if(!$this->create($params)) return null;
        return $this->recentUid;
    }






}