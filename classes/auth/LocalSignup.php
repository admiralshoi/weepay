<?php
namespace classes\auth;

use classes\Methods;
use classes\notifications\NotificationTriggers;
use classes\utility\Crud;
use Database\model\AuthLocal;
use Database\model\Users;

class LocalSignup extends Crud {
    private ?array $error = null;
    private ?object $user = null;
    private ?string $userId = null;
    private array $userData = [];
    private array $authData = [];

    /**
     * Usage:
     * 1. Call setUserData() with user information
     * 2. Call setAuthData() with authentication information
     * 3. Call validate() to check if data is valid
     * 4. Call signup() to create the user
     * 5. Optionally call autoLogin() to log the user in
     */

    function __construct() {
        parent::__construct(Users::newStatic(), "users");
    }

    public function setUserData(array $data): self {
        $this->userData = $data;
        return $this;
    }

    public function setAuthData(array $data): self {
        $this->authData = $data;
        return $this;
    }

    public function getUser(): ?object {
        return $this->user;
    }

    public function getUserId(): ?string {
        return $this->userId;
    }

    public function getError(): ?array {
        return $this->error;
    }

    public function validate(): bool {
        // Validate user data
        if (!array_key_exists('email', $this->userData) || empty($this->userData['email'])) {
            $this->error = Response()->arrayError("Email er påkrævet", [], 400);
            return false;
        }

        if (!array_key_exists('full_name', $this->userData) || empty($this->userData['full_name'])) {
            $this->error = Response()->arrayError("Fulde navn er påkrævet", [], 400);
            return false;
        }

        if (!array_key_exists('access_level', $this->userData)) {
            $this->error = Response()->arrayError("Access level er påkrævet", [], 400);
            return false;
        }

        // Validate email format
        if (!filter_var($this->userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error = Response()->arrayError("Ugyldig email-adresse", [], 400);
            return false;
        }

        // Check if email already exists
        $existingUser = Methods::users()->getByX(['email' => $this->userData['email']]);
        if (!isEmpty($existingUser) && $existingUser->count() > 0) {
            $this->error = Response()->arrayError("En bruger med denne email eksisterer allerede", [], 409);
            return false;
        }

        // Validate auth data
        if (!array_key_exists('password', $this->authData) || empty($this->authData['password'])) {
            $this->error = Response()->arrayError("Adgangskode er påkrævet", [], 400);
            return false;
        }

        // Validate password strength (min 8 chars)
        if (strlen($this->authData['password']) < 8) {
            $this->error = Response()->arrayError("Adgangskoden skal være mindst 8 tegn", [], 400);
            return false;
        }

        // Check if at least one auth identifier is provided (email, username, or phone)
        $hasIdentifier = false;
        foreach (['email', 'username', 'phone'] as $key) {
            if (array_key_exists($key, $this->authData) && !empty($this->authData[$key])) {
                $hasIdentifier = true;
                break;
            }
        }

        if (!$hasIdentifier) {
            $this->error = Response()->arrayError("Email, brugernavn eller telefonnummer er påkrævet til login", [], 400);
            return false;
        }

        // Check if auth identifiers already exist
        $authHandler = Methods::localAuthentication();
        foreach (['email', 'username', 'phone'] as $key) {
            if (array_key_exists($key, $this->authData) && !empty($this->authData[$key])) {
                $existing = $authHandler->getByX([$key => $this->authData[$key]]);
                if (!isEmpty($existing) && $existing->count() > 0) {
                    $this->error = Response()->arrayError("En bruger med denne $key eksisterer allerede", [], 409);
                    return false;
                }
            }
        }

        return true;
    }

    public function signup(): bool {
        if (!empty($this->error)) {
            return false;
        }

        // Create user
        $userHandler = Methods::users();
        $userCreateData = array_merge([
            'lang' => 'en',
            'registration_complete' => 0,
        ], $this->userData);

        if (!$userHandler->create($userCreateData)) {
            $this->error = Response()->arrayError("Kunne ikke oprette bruger. Prøv igen.", [], 500);
            return false;
        }

        $this->userId = $userHandler->recentUid;

        // Create auth record
        $authHandler = Methods::localAuthentication();
        $authCreateData = array_merge([
            'enabled' => 1,
        ], $this->authData);

        // Hash password
        $authCreateData['password'] = passwordHashing($authCreateData['password']);
        $authCreateData['user'] = $this->userId;

        if (!$authHandler->create($authCreateData)) {
            // Rollback: delete user if auth creation fails
            $userHandler->delete(['uid' => $this->userId]);
            $this->error = Response()->arrayError("Kunne ikke oprette autentificering. Prøv igen.", [], 500);
            return false;
        }

        // Load the created user
        $this->user = $userHandler->get($this->userId);

        // Trigger user registered notification
        NotificationTriggers::userRegistered($this->user);

        return true;
    }

    public function autoLogin(string $password): bool {
        if (isEmpty($this->user)) {
            $this->error = Response()->arrayError("Ingen bruger at logge ind", [], 500);
            return false;
        }

        // Use email as login identifier
        $authHandler = Methods::localAuthentication();
        $loginResult = $authHandler->validate([
            'username' => $this->user->email,
            'password' => $password
        ]);

        if (!$loginResult) {
            $this->error = $authHandler->getError();
            return false;
        }

        $authHandler->login();
        return true;
    }
}
