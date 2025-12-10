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
    public function getAvailableSessions(string $terminalId, array $fields = []): Collection {
        return $this->getByX(['terminal' => $terminalId, 'state' => ['ACTIVE', 'PENDING']], $fields);
    }


    public function setPending(string $id): bool {
        return $this->update(['state' => 'PENDING'], ['uid' => $id]);
    }
    public function setCompleted(string $id): bool {
        return $this->update(['state' => 'COMPLETED'], ['uid' => $id]);
    }
    public function setVoid(string $id): bool {
        return $this->update(['state' => 'VOID'], ['uid' => $id]);
    }
    public function setActive(string $id): bool {
        return $this->update(['state' => 'ACTIVE'], ['uid' => $id]);
    }
    public function unsetAllActiveByTerminalId(string $terminalId): bool {
        return $this->update(['state' => 'PENDING'], ['state' => "ACTIVE", 'terminal' => $terminalId]);
    }

    public function voidCustomerSessions(?string $customerId): void {
        if(isEmpty($customerId)) return;
        $sessions = $this->getByX(['customer' => $customerId, 'state' => ['ACTIVE', 'PENDING']]);
        foreach ($sessions->list() as $session) {
            $this->setVoid($session->uid);
            if($session->terminal->session === $session->session) {
                Methods::terminals()->setIdle($session->terminal->uid);
            }
        }
    }

    public function getSession(string $terminalId): ?object {
        $params = ['terminal' => $terminalId];
        if(isOidcAuthenticated()) {
            $customer = Methods::oidcAuthentication()->getByUserId()?->user;
            $params['customer'] = $customer?->uid;
        }
        else $params['csrf'] = __csrf();
        $queryBuilder = $this->queryBuilder();
        $session = $queryBuilder->whereList($params)
            ->where('state', ['ACTIVE', 'PENDING'])
//            ->whereTimeAfter('updated_at',time() - 600)
            ->first();

        if(!isEmpty($session)) {
            if(strtotime($session->updated_at) < time() - 600) {
                $this->update(['updated_at' => time()], ['uid' => $session->uid]);
            }
            return $session;
        }
        $this->update(['state' => 'VOID'], $params);
        if(!$this->setNew($terminalId)) return null;
        return $this->get($this->recentUid);
    }
    public function setNew(string $terminalId): bool {
        while(true) {
            $sessionId = generateUniqueId(4, 'INT');
            $params = ['terminal' => $terminalId, 'session' => $sessionId];
            if(!$this->exists(array_merge($params, ['state' => ['PENDING', 'ACTIVE']]))) break;
        }

        $customer = null;
        if(isOidcAuthenticated()) {
            $customer = Methods::oidcAuthentication()->getByUserId()?->user;
        }

        return $this->create(array_merge($params, ['csrf' => __csrf(), "customer" => $customer?->uid]));
    }














}