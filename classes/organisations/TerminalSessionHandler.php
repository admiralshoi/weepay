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

    /**
     * Clear the PHP session cache for a terminal.
     * Call this when a terminal session state changes to non-active.
     */
    public function clearSessionCache(string $terminalId): void {
        $phpSessionKey = "terminal_session_{$terminalId}";
        if(isset($_SESSION[$phpSessionKey])) {
            unset($_SESSION[$phpSessionKey]);
        }
    }

    public function setPending(string $id): bool {
        return $this->update(['state' => 'PENDING'], ['uid' => $id]);
    }
    public function setCompleted(string $id, ?string $terminalId = null): bool {
        $result = $this->update(['state' => 'COMPLETED'], ['uid' => $id]);
        if($result) {
            // Clear PHP session cache
            if(isEmpty($terminalId)) {
                $session = $this->get($id, ['terminal']);
                $terminalId = $session?->terminal?->uid ?? $session?->terminal;
            }
            if(!isEmpty($terminalId)) $this->clearSessionCache($terminalId);
        }
        return $result;
    }
    public function setVoid(string $id, ?string $terminalId = null): bool {
        $result = $this->update(['state' => 'VOID'], ['uid' => $id]);
        if($result) {
            // Clear PHP session cache
            if(isEmpty($terminalId)) {
                $session = $this->get($id, ['terminal']);
                $terminalId = $session?->terminal?->uid ?? $session?->terminal;
            }
            if(!isEmpty($terminalId)) $this->clearSessionCache($terminalId);
        }
        return $result;
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
            $terminalId = $session->terminal->uid ?? $session->terminal;
            $this->setVoid($session->uid, $terminalId);
            if($session->terminal->session === $session->session) {
                Methods::terminals()->setIdle($session->terminal->uid);
            }
        }
    }

    private const SESSION_CACHE_TTL = 600; // 10 minutes

    public function getSession(string $terminalId): ?object {
        // Check PHP session first to prevent duplicate sessions from race conditions
        $phpSessionKey = "terminal_session_{$terminalId}";
        if(isset($_SESSION[$phpSessionKey])) {
            $cached = $_SESSION[$phpSessionKey];
            $cachedSessionId = is_array($cached) ? ($cached['id'] ?? null) : $cached;
            $cachedAt = is_array($cached) ? ($cached['cached_at'] ?? 0) : 0;

            // Check if cache is expired
            if($cachedAt > 0 && (time() - $cachedAt) > self::SESSION_CACHE_TTL) {
                unset($_SESSION[$phpSessionKey]);
            } elseif(!isEmpty($cachedSessionId)) {
                $cachedSession = $this->get($cachedSessionId);
                if(!isEmpty($cachedSession) && in_array($cachedSession->state, ['ACTIVE', 'PENDING'])) {
                    // Touch record to refresh updated_at timestamp if stale
                    if(strtotime($cachedSession->updated_at) < time() - 600) {
                        $this->update(['updated_at' => date('Y-m-d H:i:s')], ['uid' => $cachedSession->uid]);
                    }
                    return $cachedSession;
                }
                // Cached session is no longer valid, clear it
                unset($_SESSION[$phpSessionKey]);
            }
        }

        $params = ['terminal' => $terminalId];
        if(isOidcVerified()) {
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
            // Store in PHP session with timestamp for expiry
            $_SESSION[$phpSessionKey] = ['id' => $session->uid, 'cached_at' => time()];
            return $session;
        }
        $this->update(['state' => 'VOID'], array_merge($params, ['state' => ['ACTIVE', 'PENDING']]));
        if(!$this->setNew($terminalId)) return null;

        // Store newly created session in PHP session with timestamp
        $_SESSION[$phpSessionKey] = ['id' => $this->recentUid, 'cached_at' => time()];
        return $this->get($this->recentUid);
    }
    public function setNew(string $terminalId): bool {
        while(true) {
            $sessionId = generateUniqueId(4, 'INT');
            $params = ['terminal' => $terminalId, 'session' => $sessionId];
            if(!$this->exists(array_merge($params, ['state' => ['PENDING', 'ACTIVE']]))) break;
        }

        $customer = null;
        if(isOidcVerified()) {
            $customer = Methods::oidcAuthentication()->getByUserId()?->user;
        }

        return $this->create(array_merge($params, ['csrf' => __csrf(), "customer" => $customer?->uid]));
    }














}