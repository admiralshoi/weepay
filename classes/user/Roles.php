<?php

namespace classes\user;
use Database\Collection;
use Database\model\UserRoles;
use classes\Methods;
use features\Settings;

class Roles {

    private int $accessLevel = 0;
    private bool $validRole = false;
    private int $requestingUsersAccessLevel = 0;

    function __construct(string|int $accessLevel = 0){
        if(isset($_SESSION["access_level"])) $this->requestingUsersAccessLevel = $_SESSION["access_level"];
        $this->identifier($accessLevel);
    }


    public function name(string|int $accessLevel = 0): string {
        $field = "name";
        $role = $this->get($accessLevel, array($field));
        return empty($role) ? "" : $role->$field;
    }

    public function description(string|int $accessLevel = 0): string {
        $field = "description";
        $role = $this->get($accessLevel, array($field));
        return empty($role) ? "" : $role->$field;
    }

    public function depth(string|int $accessLevel = 0): string {
        $field = "depth";
        $role = $this->get($accessLevel, array($field));
        return empty($role) ? "" : $role->$field;
    }

    public function isDefined(string|int $accessLevel = 0): int {
        $field = "defined";
        $role = $this->get($accessLevel, array($field));
        return empty($role) ? 0 : (int)$role->$field;
    }

    public function getRoleByName(string $name): ?object {
        if(empty($name)) return null;
        return UserRoles::where("name", $name)->first();
    }

    public function get(string|int $accessLevel = 0,  $fields = array()): ?object {
        if($accessLevel === 0 && $this->accessLevel === 0) $accessLevel = $this->requestingUsersAccessLevel;
        $this->identifier($accessLevel);
        if (!$this->status()) return null;
        return UserRoles::where("access_level", $this->accessLevel)->select($fields)->first();
    }

    public function getByX(array $params = array(), array $fields = array()): ?object {
        return UserRoles::whereList($params)->select($fields)->all()->list();
    }
    public function getFirst(string|int $accessLevel = 0,  $fields = array()): ?object {
        return UserRoles::where("access_level", $accessLevel)->select($fields)->first();
    }

    public function exists(string|int $accessLevel): bool {
        return UserRoles::where("access_level", $accessLevel)->exists();
    }

    private function identifier(string|int $accessLevel): void {
        if ($accessLevel === 0) return;
        if ($accessLevel === $this->accessLevel) return;
        if ($this->exists($accessLevel)) {
            $this->accessLevel = $accessLevel;
            $this->validRole = true;
        };
    }

    private function status(): bool { return $this->validRole; }


    public function update(array $params, array $identifier): bool {
        if (empty($params)) return false;
        return UserRoles::whereList($identifier)->update($params);
    }

    public function create(array $params): bool {
        if (empty($params)) return false;
        return UserRoles::insert($params);
    }


    private function getKnownRole(string $role, string|int $accessLevel): ?bool {
        if((int)$accessLevel !== $this->requestingUsersAccessLevel) return null;
        if(!Settings::$knownRole) return null;
        return match ($role) {
            default => null,
            "admin" => Settings::$isAdmin,
            "merchant" => Settings::$isMerchant,
            "consumer" => Settings::$isConsumer,
            "guest" => Settings::$isGuest,
        };
    }
    private function setKnownRole(string $role, string|int $accessLevel, bool $match): void {
        if((int)$accessLevel !== $this->requestingUsersAccessLevel) return;
        if(Settings::$knownRole) return;
        if(!$match) return;
        switch ($role) {
            default:  break;
            case "admin": Settings::$isAdmin = $match; break;
            case "merchant": Settings::$isMerchant = $match; break;
            case "consumer": Settings::$isConsumer = $match; break;
            case "guest": Settings::$isGuest = $match; break;
        }
        Settings::$knownRole = true;
    }

    public function isAdmin(string|int $accessLevel = 0): bool {
        if(isset($_SESSION["guest"])) return false;
        if($accessLevel === 0 && $this->accessLevel === 0) $accessLevel = $this->requestingUsersAccessLevel;
        elseif($accessLevel === 0 && $this->accessLevel !== 0) $accessLevel = $this->accessLevel;
        $knownRole = $this->getKnownRole("admin", $accessLevel);
        if($knownRole !== null) return $knownRole;

        $match = in_array($this->name($accessLevel), array("admin", "system_admin"));
        $this->setKnownRole("admin", $accessLevel, $match);
        return $match;
    }
    public function isMerchant(string|int $accessLevel = 0): bool {
        if($accessLevel === 0 && $this->accessLevel === 0) $accessLevel = $this->requestingUsersAccessLevel;
        elseif($accessLevel === 0 && $this->accessLevel !== 0) $accessLevel = $this->accessLevel;
        $knownRole = $this->getKnownRole("merchant", $accessLevel);
        if($knownRole !== null) return $knownRole;
        $match = $this->name($accessLevel) === 'merchant';
        $this->setKnownRole("merchant", $accessLevel, $match);
        return $match;
    }
    public function isConsumer(string|int $accessLevel = 0): bool {
        if($accessLevel === 0 && $this->accessLevel === 0) $accessLevel = $this->requestingUsersAccessLevel;
        elseif($accessLevel === 0 && $this->accessLevel !== 0) $accessLevel = $this->accessLevel;
        $knownRole = $this->getKnownRole("consumer", $accessLevel);
        if($knownRole !== null) return $knownRole;
        $match = $this->name($accessLevel) === 'consumer';
        $this->setKnownRole("consumer", $accessLevel, $match);
        return $match;
    }
    public function isGuest(string|int $accessLevel = 0): bool {
        $knownRole = $this->getKnownRole("guest", $this->requestingUsersAccessLevel);
        if($knownRole !== null) return $knownRole;
        $match = !isLoggedIn() && isset($_SESSION["share_token"]);
        $this->setKnownRole("guest", $this->requestingUsersAccessLevel, $match);
        return $match;
    }

    public function accessLevel(string $name): int {
        $role = $this->getRoleByName($name);
        return empty($role) ? 0 : (int)$role->access_level;
    }


    public function hasRoleOfX(string|int $accessLevel, string $roleName): bool {
        if(empty($accessLevel) || empty($roleName)) return false;
        if($accessLevel === 0) return false;

        return $this->accessLevel($roleName) === (int)$accessLevel;
    }


    public function userRoleSettings(object $data): array {
        $roleName = $this->name();
        if(empty($roleName)) return [];

        if(isEmpty($data->user_role_settings)) $data = Methods::appMeta()->get("user_role_settings");
        else $data = toArray($data->user_role_settings);

        if(empty($data) || !array_key_exists($roleName, $data)) return [];
        return $data[$roleName];
    }
}






























