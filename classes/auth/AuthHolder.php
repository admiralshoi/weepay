<?php

declare(strict_types=1);

namespace classes\auth;

use classes\auth\modals\Organisation;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use classes\auth\modals\User;

/**
 * Centralised static holder for request authentication context.
 *
 */
final class AuthHolder implements JsonSerializable {
    private static ?self $instance = null;

    /* ------------------------------------------------------------------ */
    /*  Public properties – your data goes here                           */
    /* ------------------------------------------------------------------ */
    public string $csrfToken;
    public bool $isLoggedIn = false;
    public ?User $user = null;
    public ?Organisation $organisation = null;
    public array $permissions = [];
    public bool $isApiRequest = false;
    public ?string $apiClientId = null;

    /* ------------------------------------------------------------------ */
    private function __construct() {
        $this->csrfToken = $this->resolveCsrfToken();
    }

    public static function get(): self {
        return self::$instance ??= new self();
    }

    public static function reset(): void {
        self::$instance = null;
    }

    private function resolveCsrfToken(): string {
        return $_SESSION['_csrf'] ??= __csrf();
    }

    public function refreshCsrfToken(): void {
        $this->csrfToken = $_SESSION['_csrf'] = __csrf();
    }

    /* ------------------------------------------------------------------ */
    /*  Setters                                                            */
    /* ------------------------------------------------------------------ */
    public function setUser(User $user): void {
        $this->user = $user;
        $this->isLoggedIn = true;
        $this->permissions = $user->getPermissions();
    }
    public function setOrganisation(Organisation $organisation): void {
        $this->organisation = $organisation;
        $this->permissions = $organisation->getPermissions();
    }

    public function setApiDetails(string $clientId, array $permissions): void {
        $this->isApiRequest = true;
        $this->apiClientId = $clientId;
        $this->permissions = $permissions;
    }

    public function logout(): void {
        removeSessions();
        $this->isLoggedIn = false;
        $this->user = null;
        $this->permissions = [];
        $this->isApiRequest = false;
        $this->apiClientId = null;
        $this->refreshCsrfToken();
    }

    /* ------------------------------------------------------------------ */
    /*  toArray() – deep traversal of ALL public properties                */
    /* ------------------------------------------------------------------ */

    /**
     * Convert the entire holder (and all nested public data) to a plain array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return $this->convertValueToArray($this);
    }

    /**
     * Recursively convert any value to a plain array.
     * Handles: objects, arrays, stdClass, primitives.
     */
    private function convertValueToArray(mixed $value, ?int $depth = 0): mixed {
        // Safety: prevent infinite recursion
        if ($depth > 20) {
            return '[MAX_DEPTH]';
        }

        // 1. Null
        if ($value === null) {
            return null;
        }

        // 2. Primitive (string, int, float, bool)
        if (is_scalar($value)) {
            return $value;
        }

        // 3. Array → recurse
        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->convertValueToArray($v, $depth + 1);
            }
            return $result;
        }

        // 4. stdClass or any object
        if (is_object($value)) {
            // If it has toArray(), use it (common pattern)
            if (method_exists($value, 'toArray')) {
                $array = $value->toArray();
                return is_array($array)
                    ? $this->convertValueToArray($array, $depth + 1)
                    : $array;
            }

            // If it implements JsonSerializable, use that
            if ($value instanceof JsonSerializable) {
                $array = $value->jsonSerialize();
                return is_array($array)
                    ? $this->convertValueToArray($array, $depth + 1)
                    : $array;
            }

            // Otherwise: reflect public properties
            $result = [];
            $reflection = new ReflectionClass($value);

            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                $name = $prop->getName();
                $val  = $prop->getValue($value);
                $result[$name] = $this->convertValueToArray($val, $depth + 1);
            }

            return $result;
        }

        // Fallback
        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  JsonSerializable – use toArray()                                   */
    /* ------------------------------------------------------------------ */
    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public function toJsonString(int $flags = 0): string {
        return json_encode($this, $flags | JSON_THROW_ON_ERROR);
    }
}