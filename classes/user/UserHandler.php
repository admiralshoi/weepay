<?php

namespace classes\user;

use classes\Methods;
use classes\utility\Crud;
use classes\utility\Titles;
use Database\Collection;
use Database\model\Users;
use Database\model\Integrations;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;


class UserHandler extends Crud {
    private string $requestingUsersAccessLevel = "";
    private string $requestingUsersId = "";


    function __construct() {
        parent::__construct(Users::newStatic(), "user");
        $this->requestingUsersAccessLevel = __accessLevel();
        $this->requestingUsersId = __uuid();
    }

    public function setDifferentRequestingUser(string|int $userId, string|int $accessLevel): void {
        $this->requestingUsersAccessLevel = $accessLevel;
        $this->requestingUsersId = $userId;
    }




    public function setCookie(string|int $key, mixed $value): void {
        if(isEmpty(Settings::$user)) return;
        $currentCookies = Settings::$user->cookies;
        if($currentCookies === null) $currentCookies = [];
        else $currentCookies = toArray($currentCookies);
        $currentCookies[$key] = $value;
        $this->update(["cookies" => $currentCookies], ["uid" => __uuid()]);
        Settings::$user->cookies = toObject($currentCookies);
        debugLog(Settings::$user->cookies, 'current cookies');
        debugLog(Methods::users()->get(__uuid())->cookies, 'current-cookies');
    }


    public function username(string|int $userId = 0): ?string {
        return $this->getColumn(['uid' => $userId], "username");
    }
    public function name(?string $userId = null): ?string {
        return $this->getColumn(['uid' => $userId], "full_name");
    }
    public function email(string|int $userId = 0): ?string {
        return $this->getColumn(['uid' => $userId], "email");
    }
    public function accessLevel(string|int $userId = 0): ?int {
        return $this->getColumn(['uid' => $userId], "access_level");
    }
    public function timeOfCreation(string|int $userId = 0): ?string {
        return $this->getColumn(['uid' => $userId], "created_at");
    }
    public function creator(string|int $userId = 0): ?string {
        return $this->getColumn(['uid' => $userId], "created_by");
    }
    public function lang(string|int $userId = 0): ?string {
        return $this->getColumn(['uid' => $userId], "lang");
    }

    public function getXByCreatedAtMax(int $createdAtMax): Collection {
        return Users::queryBuilder()->whereTimeBefore("created_at", $createdAtMax, "<=")->all();
    }


    public function getByEmail(string $email, array $fields = []): ?object {
        return $this->getFirst(["email" => $email], $fields);
    }



    public function toggleUserSuspension(array $args): array {
        if(!array_key_exists("id", $args)) return ["status" => "error", "error" => ["message" => "Missing user id"]];

        $user = $this->get($args["id"]);
        if($user->uid === $this->requestingUsersId) return ["status" => "error", "error" => ["message" => "You cannot toggle yourself"]];
        if(empty($user) || (int)$user->access_level > $this->requestingUsersAccessLevel)
            return ["status" => "error", "error" => ["message" => "Failed to identify user"]];

        $this->update(["deactivated" => (int)(!((int)$user->deactivated))], ["id" => $user->id]);
        return array("status" => "success", "success" => true);
    }



    public function registrationIsComplete(): bool {return (int)($this->get($this->requestingUsersId)->registration_complete) === 1; }
    public function setCompleteRegistrationIfComplete(): bool {
        $user = $this->get($this->requestingUsersId);
        if((int)$user->registration_complete === 1 ) {
            if(Methods::isCreator() && Methods::integrations()->getByX(["user_id" => $user->uid])->empty()) {
                $this->update(["registration_complete" => 0], ["uid" => $user->uid]);
                return false;
            }
            return true;
        }

        if(!Methods::integrations()->getByX(["user_id" => $user->uid])->empty()) {
            $this->update(["registration_complete" => 1], ["uid" => $user->uid]);
            return true;
        }
        return false;
    }
    public function integrationUnderway(): bool {
        if(!Methods::isCreator()) return false;
        $integration = Integrations::whereList(["user_id" => $this->requestingUsersId, "provider" => "instagram"])->first();
        if(isEmpty($integration)) return false;

        $username = $integration->item_name;

        return isEmpty(Methods::actors()->getByUsername($username));
    }

    public function creatorId(string|int $uuid = ""): string {
        if(empty($uuid)) $uuid = __oUuid();
        if(!Methods::isCreator($this->accessLevel($uuid))) return "";

        $username = Methods::socialAccounts()->getColumn(['uuid' => $uuid, 'provider' => 'instagram'], 'username');
        if(empty($username)) return "";
        return nestedArray(Methods::actors()->getByUsername($username, 0, ['uid']), ["uid"], "");
    }





    #[ArrayShape(["status" => "string", "message" => "string"])]
    public function updateUserFields(array $args): array {
        if(!$this->access(MODIFY_ACTION)) return ["status" => "error", "message" => "Unauthorized"];
        if(!array_key_exists("data", $args) || !is_array($args["data"]) || empty($args["data"])) return ["status" => "error", "message" => "Missing data"];
        $data = $args["data"];
        $uuid = array_key_exists("uid", $data) ? $data["uid"] : $this->requestingUsersId;
        $updateParams = $keysToUpdate = [];
        $user = $this->get($uuid);
        if(isEmpty($user)) return ["status" => "error", "message" => "Unknown user"];
        if($uuid !== $this->requestingUsersId) return ["status" => "error", "message" => "Unauthorized"];

        foreach ($data as $key => $value) {

            switch ($key) {
                default: continue 2;
                case "email":
                    if(!filter_var($value, FILTER_VALIDATE_EMAIL)) return ["status" => "error", "message" => "Incorrectly formatted email"];
                    if($user->email === $value) continue 2;
                    if(!isEmpty($this->getByEmail($value))) return ["status" => "error", "message" => "This email is already in use"];
                    $updateParams[$key] = trim($value);
                    break;

                case "full_name":
                    if(empty($value)) return ["status" => "error", "message" => "The Full Name cannot be empty"];
                    if($user->full_name === Titles::cleanUcAll($value)) continue 2;;
                    $updateParams[$key] = Titles::cleanUcAll(trim($value));
                    break;
                case "password":
                    if(empty($value)) return ["status" => "error", "message" => "The Password cannot be empty"];
                    if(strlen($value) < 6) return ["status" => "error", "message" => "The password must be at least 6 characters long"];
                    if($user->password === passwordHashing($value)) continue 2;;
                    $updateParams[$key] = passwordHashing($value);
                    break;
            }

            $keysToUpdate[] = $key;
        }

        if(!empty($updateParams)) $this->update($updateParams, ["uid" => $uuid]);
        if($uuid === $this->requestingUsersId) setSessions($updateParams, $keysToUpdate);
        return ["status" => "success", "message" => "Successfully updated the user's fields"];
    }


    /**
     * Generate a unique username for organisation team member
     * Format: {org_short}_{initials}{numbers}
     * Example: acme_jd42
     */
    public function generateUniqueUsername(string $organisationName, string $fullName): string {
        // Clean and shorten organisation name (max 6 chars, alphanumeric only)
        $orgShort = strtolower(preg_replace('/[^a-z0-9]/i', '', $organisationName));
        $orgShort = substr($orgShort, 0, min(6, strlen($orgShort)));

        // Extract initials from full name
        $nameParts = explode(' ', trim($fullName));
        $initials = '';
        if (count($nameParts) >= 2) {
            // First and last name
            $initials = strtolower(substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts) - 1], 0, 1));
        } else {
            // Just first name, use first two chars
            $initials = strtolower(substr($nameParts[0], 0, 2));
        }

        // Try to generate unique username with increasing numbers
        $maxAttempts = 100;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $randomNum = str_pad(rand(10, 9999), 2, '0', STR_PAD_LEFT);
            $username = "{$orgShort}_{$initials}{$randomNum}";

            // Check if username exists in AuthLocal
            $exists = \Database\model\AuthLocal::where('username', $username)->exists();
            if (!$exists) {
                return $username;
            }
        }

        // Fallback: use timestamp if all attempts failed
        return "{$orgShort}_{$initials}" . substr(time(), -4);
    }


}