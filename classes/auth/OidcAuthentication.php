<?php

namespace classes\auth;

use classes\Methods;
use classes\utility\Crud;
use Database\model\AuthOidc;
use features\Settings;

class OidcAuthentication extends Crud {

    function __construct() {
        parent::__construct(AuthOidc::newStatic(), "authoidc");
    }

    public function getByUserId(string|int|null $uid = null, array $fields = []): ?object {
        if(empty($uid) && !isOidcAuthenticated()) return null;
        if(empty($uid)) $uid = __uuid();
        return $this->getFirst(['user' => $uid], $fields);
    }
    public function getByPrid(string|int|null $prid, array $fields = [], string $provider = 'mitid'): ?object {
        if(empty($prid)) return null;
        return $this->getFirst(['prid' => $prid,  'provider' => $provider], $fields);
    }
    public function getByNin(string|int|null $nin, array $fields = [], string $provider = 'mitid'): ?object {
        if(empty($nin)) return null;
        return $this->getFirst(['nin' => $nin, 'provider' => $provider], $fields);
    }
    public function getByProviderSession(array|object|null $providerSession, array $fields = []): ?object {
        $provider = nestedArray($providerSession, ["provider"]);
        $nin = nestedArray($providerSession, ["subject", "nin", "value"]);
        if(empty($nin) || empty($provider)) return null;
        return $this->getFirst(['nin' => $nin, 'provider' => $provider], $fields);
    }


    public function login(array|object|null $providerSession): ?string {
        $existing = $this->getByProviderSession($providerSession);
        if(isEmpty($existing)) {
            if(!$this->newOidcUser($providerSession)) return null;
            $uid = $this->recentUid;
        }
        else $uid = $existing->uid;

        $existing = $this->get($uid);
        if(isEmpty($existing)) return null;
        $user = toArray($existing->user);
        $keys = array_keys($user);
        $keys[] = "logged_in";
        $keys[] = "oidcAuth";
        setSessions($user,$keys);
        return $this->recentUid;
    }

    public function newOidcUser(
        array|object|null $providerSession,
        ?array $restrictedLogonTypes = null,
        ?int $enabled = null,
    ): bool {
        if(isEmpty($providerSession)) return false;
        if(is_object($providerSession)) $providerSession = toArray($providerSession);

        $provider = nestedArray($providerSession, ["provider"]);
        $prid = nestedArray($providerSession, ["subject", "id"]);
        $name = nestedArray($providerSession, ["subject", "name"], "Ukendt");
        $birthDate = nestedArray($providerSession, ["subject", "dateOfBirth"]);
        $nin = nestedArray($providerSession, ["subject", "nin", "value"]);
        $ninCountry = nestedArray($providerSession, ["subject", "nin", "issuingCountry"], Settings::$app->default_country);
        $ninUserType = nestedArray($providerSession, ["subject", "nin", "type"], "PERSON");
        if(empty($provider) || empty($prid)) {
            debugLog($providerSession, "newOidcUser-empty-error");
            return false;
        }

        if($ninUserType === 'PERSON') $accessLevel = 1;
        else {
            debugLog("Unknown ninUserType $ninUserType", "newOidcUser-usertype-error");
            return false;
        }

        $userHandler = Methods::users();
        $userParams = [
            "full_name" => $name,
            "access_level" => $accessLevel,
            "birthdate" => $birthDate,
            "lang" => strtolower($ninCountry)
        ];
        if(!$userHandler->create($userParams)) {
            debugLog($userParams,"newOidcUser-user-create-error");
            return false;
        }
        $uid = $userHandler->recentUid;
        if(empty($this->insert($uid, $prid, $nin, $ninCountry, $ninUserType, $provider, $restrictedLogonTypes, $enabled))) {
            debugLog($userParams,"newOidcUser-auth-create-error");
            return false;
        }
        return true;
    }

    public function insert(
        string $userUid,
        string $prid,
        string $nin,
        ?string $ninCountry = null,
        ?string $ninUserType = null,
        string $provider = 'mitId',
        ?array $restrictedLogonTypes = null,
        ?int $enabled = null,
    ): ?string {
        $params = [
            "user" => $userUid,
            "prid" => $prid,
            "nin" => $nin,
            "provider" => $provider,
            "nin_country" => $ninCountry,
            "nin_user_type" => $ninUserType,
            "restricted_logon_types" => $restrictedLogonTypes,
        ];
        if($enabled !== null) $params['enabled'] = $enabled;
        if($this->create($params)) return $this->recentUid;
        return null;
    }





}