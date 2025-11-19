<?php

namespace classes\organisations;

use classes\utility\Crud;
use Database\Collection;
use classes\Methods;
use Database\model\TerminalSession;
use features\Settings;

class TerminalSessionHandler extends Crud {


    function __construct() {
        parent::__construct(TerminalSession::newStatic(), "terminal");
    }


    public function getByTerminalAndSessionId(string $terminalId, string|int $sessionId, array $fields = []): ?object {
        return $this->getFirst(['uid' => $terminalId, 'session' => $sessionId], $fields);
    }

    public function getSession(string $terminalId): ?object {
        $params = ['terminal' => $terminalId, 'csrf' => __csrf()];
        $queryBuilder = $this->queryBuilder();
        $session = $queryBuilder->whereList($params)
            ->where('state', ['ACTIVE', 'PENDING'])
            ->whereTimeAfter('updated_at',time() - 600)
            ->first();

        if(!isEmpty($session)) return $session;
        if(!$this->setNew($terminalId)) return null;
        return $this->get($this->recentUid);
    }
    public function setNew(string $terminalId): bool {
        while(true) {
            $sessionId = generateUniqueId(4, 'INT');
            $params = ['terminal' => $terminalId, 'session' => $sessionId];
            if(!$this->exists(array_merge($params, ['state' => ['PENDING', 'ACTIVE']]))) break;
        }
        return $this->create(array_merge($params, ['csrf' => __csrf()]));
    }














}