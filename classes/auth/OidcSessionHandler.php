<?php

namespace classes\auth;

use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\OidcSessions;
use features\Settings;

class OidcSessionHandler extends Crud {


    function __construct() {
        parent::__construct(OidcSessions::newStatic(), "oidc");
    }


    public function getByPrid(string|int|null $prid, array $fields = []): ?object {
        if(empty($prid)) return null;
        return $this->getFirst(['prid' => $prid], $fields);
    }
    public function getByToken(string $token,  array $fields = []): ?object {
        return $this->queryGetFirst(
            $this->queryBuilder()->select($fields)
            ->where("expires_at", ">", time())
            ->where("token", $token)
            ->where("status", ["DRAFT", "PENDING"])
        );
    }


    public function getProviderSession(string|int $prid): ?object {
        return toObject(Methods::signicact()->getSession($prid));
    }
    public function statusTimeout(string $id): bool {
        return $this->update(['status' => 'TIMEOUT'], ['uid' => $id]);
    }
    public function statusPending(string $id): bool {
        return $this->update(['status' => 'PENDING'], ['uid' => $id]);
    }
    public function statusCancelled(string $id): bool {
        return $this->update(['status' => 'CANCELLED'], ['uid' => $id]);
    }
    public function statusVoid(string $id): bool {
        return $this->update(['status' => 'VOID'], ['uid' => $id]);
    }
    public function statusError(string $id): bool {
        return $this->update(['status' => 'ERROR'], ['uid' => $id]);
    }
    public function statusSuccess(string $id): bool {
        return $this->update(['status' => 'SUCCESS'], ['uid' => $id]);
    }

    public function getSession(string|int $prid): ?object {
        $row = $this->getByPrid($prid);
        if(isEmpty($row)) return null;
        $row->session = $this->getSession($prid);
        return $row;
    }


    public function setSession(
        string $reason,
        ?array $queryParams,
        int|string $token,
        ?int $expiresAt = null,
        string $status = 'DRAFT',
        ?string $provider = null,
    ): ?string {
        $oidcSessionId = $this->insert($reason, null, $token, $queryParams, $expiresAt, $status, $provider);
        if(empty($oidcSessionId)) {
            debugLog("Failed to insert own oidc session", 'oidc-session-error');
            return null;
        }
        $verifySession = Methods::signicact()->createSession(
            Links::$app->auth->oicd->callbackAuthenticate,
            ['ref' => $oidcSessionId],
        );
        if(empty($verifySession)) {
            debugLog("Failed to create  oidc session", 'oidc-session-error');
            $this->statusVoid($oidcSessionId);
            return null;
        }
        if(!array_key_exists("id", $verifySession)) {
            debugLog($verifySession, 'oidc-session-error');
            $this->statusVoid($oidcSessionId);
            return null;
        }
        $this->update(['prid' => $verifySession['id']], ['uid' => $oidcSessionId]);
        return $oidcSessionId;
    }




    public function insert(
        string $reason,
        null|string|int $prid,
        int|string $token,
        ?array $info = null,
        ?int $expiresAt = null,
        string $status = 'DRAFT',
        ?string $provider = null,
    ): ?string {
        $this->recentUid = null;
        if($provider == null) $provider = "mitid";
        if($expiresAt == null) $expiresAt = time() + Settings::$app->oidc_session_lifetime;
        if(!$this->create([
            "prid" => $prid,
            "reason" => $reason,
            "expires_at" => $expiresAt,
            "status" => $status,
            "provider" => $provider,
            "info" => $info,
            "token" => $token
        ])) return null;
        return $this->recentUid;
    }














}