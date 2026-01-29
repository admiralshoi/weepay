<?php
namespace classes\auth;

use JetBrains\PhpStorm\ArrayShape;
use classes\Methods;
use classes\notifications\NotificationTriggers;
use classes\enumerations\Links;

class PasswordHandler {

    /**
     * Cooldown period in seconds between password reset requests
     */
    public const RESET_COOLDOWN_SECONDS = 60;

    /**
     * Find AuthLocal by identifier (email, phone, or username)
     * @param string $identifier Email, phone number, or username
     * @param string|null $phoneCountryCode Country code if identifier is a phone number
     * @return object|null AuthLocal record with resolved user, or null if not found
     */
    public function findAuthByIdentifier(string $identifier, ?string $phoneCountryCode = null): ?object {
        $handler = Methods::localAuthentication()->excludeForeignKeys();

        // Check if it's a phone number (numeric and has country code)
        if (is_numeric($identifier) && !isEmpty($phoneCountryCode)) {
            $auth = $handler->getFirst([
                'phone' => $identifier,
                'phone_country_code' => $phoneCountryCode
            ]);
        } else {
            // Try email first
            $auth = $handler->getFirst(['email' => $identifier]);

            // Then try username if email not found
            if (isEmpty($auth)) {
                $auth = $handler->getFirst(['username' => $identifier]);
            }
        }

        if (isEmpty($auth)) return null;

        // Return full auth with user foreign key resolved
        return Methods::localAuthentication()->get($auth->uid);
    }

    /**
     * Request a password reset
     * @param array $args ['identifier' => email/phone, 'phone_country_code' => optional]
     * @return array Status response
     */
    public static function requestReset(array $args): array {
        debugLog(['args' => $args, 'time' => date('Y-m-d H:i:s')], "PWD-RESET-START");

        if (isset($_SESSION["pwd_reset"])) unset($_SESSION["pwd_reset"]);

        $identifier = $args["identifier"] ?? null;
        $phoneCountryCode = $args["phone_country_code"] ?? null;

        if (isEmpty($identifier)) {
            debugLog(['error' => 'empty identifier'], "PWD-RESET-ERROR");
            return ["status" => "error", "error" => "Indtast venligst din email eller telefonnummer"];
        }

        $handler = new self();
        $isPhone = is_numeric($identifier) && !isEmpty($phoneCountryCode);

        debugLog(['identifier' => $identifier, 'isPhone' => $isPhone], "PWD-RESET-IDENTIFIER");

        // Validate email format if not a phone number
        if (!$isPhone && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            debugLog(['error' => 'invalid email format'], "PWD-RESET-ERROR");
            return ["status" => "error", "error" => "Ugyldig email adresse"];
        }

        // Find AuthLocal by identifier
        $authLocal = $handler->findAuthByIdentifier($identifier, $phoneCountryCode);

        debugLog(['authLocal_found' => !isEmpty($authLocal)], "PWD-RESET-AUTH-LOOKUP");

        // Always return success to prevent user enumeration
        if (isEmpty($authLocal)) {
            debugLog(['exit' => 'no authLocal found'], "PWD-RESET-EXIT");
            return ["status" => "success", "message" => "Hvis kontoen findes, vil du modtage et link til at nulstille din adgangskode"];
        }

        // Get user from AuthLocal
        $user = $authLocal->user;
        if (isEmpty($user)) {
            debugLog(['exit' => 'no user on authLocal'], "PWD-RESET-EXIT");
            return ["status" => "success", "message" => "Hvis kontoen findes, vil du modtage et link til at nulstille din adgangskode"];
        }

        debugLog(['user_uid' => $user->uid, 'user_email' => $user->email ?? 'N/A'], "PWD-RESET-USER-FOUND");

        // Rate limiting: Check cooldown and get existing valid token if any
        $twoFa = Methods::twoFactorAuth();
        $cooldownCheck = $twoFa->checkPasswordResetCooldown($user->uid, self::RESET_COOLDOWN_SECONDS);

        if ($cooldownCheck['on_cooldown']) {
            debugLog([
                'blocked_by_cooldown' => true,
                'wait_seconds' => $cooldownCheck['wait_seconds'],
            ], "PWD-RESET-COOLDOWN-BLOCKED");

            return [
                "status" => "error",
                "error" => "Vent venligst før du anmoder om et nyt link",
                "cooldown" => true,
                "wait_seconds" => $cooldownCheck['wait_seconds']
            ];
        }

        debugLog(['cooldown_passed' => true], "PWD-RESET-COOLDOWN-PASSED");

        // Check if we have an existing valid token to reuse
        $existingToken = $cooldownCheck['existing_token'];
        $token = null;

        if (!isEmpty($existingToken)) {
            // Reuse existing token
            $token = $existingToken->code;
            debugLog(['reusing_existing_token' => true, 'token_prefix' => substr($token, 0, 8) . '...'], "PWD-RESET-TOKEN-REUSED");
        } else {
            // Create new token
            $tokenData = $twoFa->createPasswordResetToken($user->uid, $identifier, $isPhone ? $phoneCountryCode : null);

            if (isEmpty($tokenData)) {
                debugLog(['error' => 'failed to create token'], "PWD-RESET-ERROR");
                return ["status" => "error", "error" => "Der opstod en fejl. Prøv igen senere"];
            }

            $token = $tokenData['token'];
            debugLog(['token_created' => true, 'token_prefix' => substr($token, 0, 8) . '...'], "PWD-RESET-TOKEN-CREATED");
        }

        // Generate reset link
        $resetLink = __url(Links::$app->auth->resetPassword . "?token=" . $token);

        // Send notification via the notification system
        // Only send via the channel the user used (email or SMS, not both)
        // Use token + minute-based timestamp for reference_id (unique per minute, matches 60s cooldown)
        $notificationOptions = [
            'reset_token' => $token . '_' . date('YmdHi')
        ];
        if ($isPhone) {
            $notificationOptions['skip_email'] = true;
        } else {
            $notificationOptions['skip_sms'] = true;
        }

        debugLog(['sending_notification' => true, 'options' => $notificationOptions], "PWD-RESET-NOTIFICATION");
        NotificationTriggers::userPasswordReset($user, $resetLink, $notificationOptions);

        debugLog(['success' => true], "PWD-RESET-COMPLETE");
        return ["status" => "success", "message" => "Hvis kontoen findes, vil du modtage et link til at nulstille din adgangskode"];
    }

    /**
     * Check if a reset token is valid and not expired
     * @param string $token The reset token
     * @return bool True if token is valid
     */
    public function resetAvailable(string $token): bool {
        $verification = Methods::twoFactorAuth()->verifyPasswordResetToken($token);
        return !isEmpty($verification);
    }

    /**
     * Get the password reset record by token
     * @param string $token The reset token
     * @return object|null The password reset record or null
     */
    public function getResetByToken(string $token): ?object {
        return Methods::twoFactorAuth()->verifyPasswordResetToken($token);
    }

    /**
     * Mark a reset token as used
     * @param string $token The reset token
     * @return bool Success status
     */
    public function markTokenUsed(string $token): bool {
        $verification = Methods::twoFactorAuth()->verifyPasswordResetToken($token);
        if (isEmpty($verification)) return false;
        return Methods::twoFactorAuth()->markPasswordResetUsed($verification->uid);
    }

    /**
     * Get user from a valid reset token
     * @param string $token The reset token
     * @return object|null User object or null if invalid
     */
    public function getUserFromToken(string $token): ?object {
        $verification = Methods::twoFactorAuth()->verifyPasswordResetToken($token);
        if (isEmpty($verification)) return null;

        // Get the user (foreign key is resolved automatically if not excluded)
        // The verification record has user as foreign key
        return $verification->user;
    }

    /**
     * Legacy method - Create a new password from reset token
     * @deprecated Use the force_password_change flow instead
     */
    #[ArrayShape(["status" => "string", "message" => "string"])]
    public function createNewPassword(array $args): array {
        if (!array_key_exists("data", $args)) return ["status" => "error", "message" => "Missing fields"];
        $data = $args["data"];

        foreach (["password", "password_repeat", "token"] as $key) {
            if (!array_key_exists($key, $data)) {
                return ["status" => "error", "message" => "Missing field $key"];
            }
        }

        $password = $data["password"];
        $passwordRepeat = $data["password_repeat"];
        $token = $data["token"];

        if (!isset($_SESSION["pwd_reset"]) || $_SESSION["pwd_reset"] !== true) {
            return ["status" => "error", "message" => "Du har ikke tilladelse til at udføre denne handling"];
        }

        if (!$this->resetAvailable($token)) {
            return ["status" => "error", "message" => "Linket er udløbet eller allerede brugt"];
        }

        if ($password !== $passwordRepeat) {
            return ["status" => "error", "message" => "Adgangskoderne matcher ikke"];
        }

        if (strlen($password) < 8) {
            return ["status" => "error", "message" => "Adgangskoden skal være mindst 8 tegn"];
        }

        $user = $this->getUserFromToken($token);

        if (isEmpty($user)) {
            return ["status" => "error", "message" => "Bruger ikke fundet"];
        }

        // Get the user's AuthLocal record
        $authLocal = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['user' => $user->uid]);

        if (isEmpty($authLocal)) {
            return ["status" => "error", "message" => "Bruger har ikke lokal login"];
        }

        $newPassword = passwordHashing($password);

        // Update password on AuthLocal, not Users table
        $updated = Methods::localAuthentication()->update(
            ['password' => $newPassword, 'force_password_change' => 0],
            ['uid' => $authLocal->uid]
        );

        if ($updated) {
            $this->markTokenUsed($token);
            unset($_SESSION["pwd_reset"]);
            return ["status" => "success", "message" => "Din adgangskode er blevet opdateret"];
        }

        return ["status" => "error", "message" => "Kunne ikke opdatere adgangskoden. Prøv igen senere"];
    }
}
