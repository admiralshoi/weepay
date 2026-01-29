<?php

namespace classes\auth;

use classes\api\GatewayApi;
use classes\Methods;
use classes\utility\Crud;
use classes\utility\Numbers;
use Database\model\TwoFactorVerification;

class TwoFactorAuth extends Crud {
    private const CODE_EXPIRY = 600; // 10 minutes

    function __construct() {
        parent::__construct(TwoFactorVerification::newStatic(), "2fa");
    }

    /**
     * Send a verification code via SMS
     * @param string $userId User UID
     * @param string $phone Phone number to verify
     * @param string $phoneCountryCode Country code for the phone number
     * @param string $purpose Purpose of verification (e.g., 'phone_verification', '2fa_auth')
     * @return array|null Returns array with code_id on success, null on failure
     */
    public function sendSmsCode(string $userId, string $phone, string $phoneCountryCode, array $callerInfo, string $purpose = 'phone_verification'): ?array {
        // Invalidate any existing pending codes for this user/identifier/purpose
        $this->update(
            ['verified' => -1], // -1 means invalidated
            ['user' => $userId, 'identifier' => $phone, 'purpose' => $purpose, 'verified' => 0]
        );

        // Generate random 6-digit code
        $code = GatewayApi::generateVerificationCode();

        // Create the message (will be encrypted in database)
        $message = "Din verificeringskode: $code. Koden udløber om 10 minutter.";

        // Create verification record
        $data = [
            'user' => $userId,
            'type' => 'sms',
            'purpose' => $purpose,
            'code' => $code,
            'identifier' => $phone,
            'phone_country_code' => $phoneCountryCode,
            'message' => $message,
            'verified' => 0,
            'expires_at' => time() + self::CODE_EXPIRY,
        ];

        if (!$this->create($data)) {
            return null;
        }

        // Send actual SMS via GatewayAPI
        try {
            $callerCode = $callerInfo['phone'];
            $phoneLength = $callerInfo['phoneLength'];
            $phoneNumber =  Numbers::cleanPhoneNumber($phone,  true, $phoneLength, $callerCode);
            $gateway = new GatewayApi();
            $smsResponse = $gateway->sendSms($phoneNumber, $message, 'WeePay');

            // Log SMS response for debugging
            if (!empty($smsResponse['ids']) && is_array($smsResponse['ids'])) {
                debugLog([
                    'success' => true,
                    'phone' => $phone,
                    'message_id' => $smsResponse['ids'][0] ?? null
                ], '2fa-sms-sent');
            } else {
                debugLog([
                    'success' => false,
                    'phone' => $phone,
                    'response' => $smsResponse
                ], '2fa-sms-failed');
            }
        } catch (\Exception $e) {
            errorLog([
                'error' => $e->getMessage(),
                'phone' => $phone
            ], '2fa-sms-exception');
        }

        return [
            'code_id' => $this->recentUid,
            'expires_at' => $data['expires_at'],
        ];
    }

    /**
     * Send a verification code via Email
     * @param string $userId User UID
     * @param string $email Email to verify
     * @param string $purpose Purpose of verification
     * @return array|null Returns array with code_id on success, null on failure
     */
    public function sendEmailCode(string $userId, string $email, string $purpose = 'email_verification'): ?array {
        // Invalidate any existing pending codes for this user/identifier/purpose
        $this->update(
            ['verified' => -1],
            ['user' => $userId, 'identifier' => $email, 'purpose' => $purpose, 'verified' => 0]
        );

        // Generate random 6-digit code
        $code = GatewayApi::generateVerificationCode();

        // Create the message (will be encrypted in database)
        $message = "Your WeePay verification code is: $code. This code will expire in 10 minutes.";

        // Create verification record
        $data = [
            'user' => $userId,
            'type' => 'email',
            'purpose' => $purpose,
            'code' => $code,
            'identifier' => $email,
            'message' => $message,
            'verified' => 0,
            'expires_at' => time() + self::CODE_EXPIRY,
        ];

        if (!$this->create($data)) {
            return null;
        }

        return [
            'code_id' => $this->recentUid,
            'expires_at' => $data['expires_at'],
        ];

        // TODO: When email provider is ready, send actual email here
        // $this->sendActualEmail($email, $message);
    }

    /**
     * Verify a code
     * @param string $userId User UID
     * @param string $code 6-digit code
     * @param string $identifier Phone or email being verified
     * @param string|null $phoneCountryCode Country code for phone verification (null for email)
     * @param string $purpose Purpose of verification
     * @return bool True if code is valid and not expired
     */
    public function verifyCode(string $userId, string $code, string $identifier, ?string $phoneCountryCode, string $purpose): bool {
        // Build the verification query
        $query = [
            'user' => $userId,
            'code' => $code,
            'identifier' => $identifier,
            'purpose' => $purpose,
            'verified' => 0
        ];

        // Add phone_country_code to query if provided (for phone verification)
        if (!empty($phoneCountryCode)) {
            $query['phone_country_code'] = $phoneCountryCode;
        }

        // Find the verification record
        $verification = $this->getFirst($query);

        if (isEmpty($verification)) {
            return false;
        }

        // Check if expired
        if ($verification->expires_at < time()) {
            return false;
        }

        // Mark as verified
        $this->update(
            ['verified' => 1, 'verified_at' => time()],
            ['uid' => $verification->uid]
        );

        return true;
    }

    /**
     * Check if a code has been verified
     * @param string $userId User UID
     * @param string $identifier Phone or email
     * @param string|null $phoneCountryCode Country code for phone verification (null for email)
     * @param string $purpose Purpose of verification
     * @return bool True if verified
     */
    public function isVerified(string $userId, string $identifier, ?string $phoneCountryCode, string $purpose): bool {
        // Build the verification query
        $query = [
            'user' => $userId,
            'identifier' => $identifier,
            'purpose' => $purpose,
            'verified' => 1
        ];

        // Add phone_country_code to query if provided (for phone verification)
        if (!empty($phoneCountryCode)) {
            $query['phone_country_code'] = $phoneCountryCode;
        }

        $verification = $this->getFirst($query);

        return !isEmpty($verification);
    }

    /**
     * Get the most recent pending verification for a user
     * @param string $userId User UID
     * @param string $purpose Purpose of verification
     * @return object|null Verification record or null
     */
    public function getPendingVerification(string $userId, string $purpose): ?object {
        return $this->queryGetFirst(
            $this->queryBuilder()
                ->where('user', $userId)
                ->where('purpose', $purpose)
                ->where('verified', 0)
                ->where('expires_at', '>', time())
                ->order('created_at', 'DESC')
        );
    }

    /**
     * Clean up expired codes (can be called via cron)
     */
    public function cleanupExpired(): bool {
        return $this->update(
            ['verified' => -1],
            ['verified' => 0, 'expires_at' => ['<', time()]]
        );
    }

    /**
     * Create an invitation verification code (30 char string, 24hr TTL)
     * Used for team member invitations where the user doesn't know their password
     *
     * @param string $userId User UID being invited
     * @param string $organisationId Organisation UID
     * @param string $email Email of the invited user
     * @return array|null Returns array with code on success, null on failure
     */
    public function createInvitationCode(string $userId, string $organisationId, string $email): ?array {
        // Invalidate any existing pending invitation codes for this user/org
        $this->update(
            ['verified' => -1],
            ['user' => $userId, 'identifier' => $organisationId, 'purpose' => 'team_invitation', 'verified' => 0]
        );

        // Generate random 30-character code
        $code = bin2hex(random_bytes(15)); // 30 hex characters

        // 24 hour expiry
        $expiresAt = time() + (24 * 60 * 60);

        // Create verification record
        $data = [
            'user' => $userId,
            'type' => 'link',
            'purpose' => 'team_invitation',
            'code' => $code,
            'identifier' => $organisationId, // Store org UID as identifier
            'message' => $email, // Store email in message field
            'verified' => 0,
            'expires_at' => $expiresAt,
        ];

        if (!$this->create($data)) {
            return null;
        }

        return [
            'code' => $code,
            'code_id' => $this->recentUid,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verify an invitation code and return the user/org info
     *
     * @param string $organisationId Organisation UID from URL
     * @param string $code 30-character code from URL
     * @return object|null Returns verification record on success, null on failure
     */
    public function verifyInvitationCode(string $organisationId, string $code): ?object {
        // Find the verification record - use excludeForeignKeys to get raw user UID
        $verification = $this->excludeForeignKeys()->getFirst([
            'identifier' => $organisationId,
            'code' => $code,
            'purpose' => 'team_invitation',
            'verified' => 0
        ]);

        if (isEmpty($verification)) {
            return null;
        }

        // Check if expired
        if ($verification->expires_at < time()) {
            return null;
        }

        return $verification;
    }

    /**
     * Mark an invitation code as used
     *
     * @param string $codeUid The UID of the verification record
     * @return bool
     */
    public function markInvitationUsed(string $codeUid): bool {
        return $this->update(
            ['verified' => 1, 'verified_at' => time()],
            ['uid' => $codeUid]
        );
    }

    /**
     * Clear phone verification records for a specific user
     * @param string $userId User UID
     * @param string $purpose Purpose of verification (default: phone_verification)
     * @return bool
     */
    public function clearUserPhoneVerification(string $userId, string $purpose = 'phone_verification'): bool {
        return $this->delete([
            'user' => $userId,
            'purpose' => $purpose
        ]);
    }

    /**
     * Clear phone verification records for other users with a specific phone number
     * Used when a user claims a phone number that was previously used by another user
     * @param string $identifier Phone number
     * @param string $phoneCountryCode Country code
     * @param string $excludeUserId User UID to exclude (the one claiming the number)
     * @param string $purpose Purpose of verification (default: phone_verification)
     * @return bool
     */
    public function clearOtherUsersPhoneVerification(string $identifier, string $phoneCountryCode, string $excludeUserId, string $purpose = 'phone_verification'): bool {
        // Using queryBuilder here because Crud::delete() doesn't support != conditions
        return $this->queryBuilder()
            ->where('identifier', $identifier)
            ->where('phone_country_code', $phoneCountryCode)
            ->where('purpose', $purpose)
            ->where('user', '!=', $excludeUserId)
            ->delete();
    }

    /**
     * Create a password reset token (32 char string, 24hr TTL)
     *
     * @param string $userId User UID
     * @param string $identifier Email or phone used for reset
     * @param string|null $phoneCountryCode Country code if phone
     * @return array|null Returns array with token on success, null on failure
     */
    public function createPasswordResetToken(string $userId, string $identifier, ?string $phoneCountryCode = null): ?array {
        $purpose = 'password_reset';

        // Invalidate any existing pending password reset tokens for this user
        $this->update(
            ['verified' => -1],
            ['user' => $userId, 'purpose' => $purpose, 'verified' => 0]
        );

        // Generate random 32-character token
        $token = bin2hex(random_bytes(16));

        // 24 hour expiry
        $expiresAt = time() + (24 * 60 * 60);

        // Create verification record
        $data = [
            'user' => $userId,
            'type' => 'link',
            'purpose' => $purpose,
            'code' => $token,
            'identifier' => $identifier,
            'verified' => 0,
            'expires_at' => $expiresAt,
        ];

        if ($phoneCountryCode) {
            $data['phone_country_code'] = $phoneCountryCode;
        }

        if (!$this->create($data)) {
            return null;
        }

        return [
            'token' => $token,
            'token_id' => $this->recentUid,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verify a password reset token
     *
     * @param string $token The reset token from URL
     * @return object|null Returns verification record on success, null on failure
     */
    public function verifyPasswordResetToken(string $token): ?object {
        $verification = $this->getFirst([
            'code' => $token,
            'purpose' => 'password_reset',
            'verified' => 0
        ]);

        if (isEmpty($verification)) {
            return null;
        }

        // Check if expired
        if ($verification->expires_at < time()) {
            return null;
        }

        return $verification;
    }

    /**
     * Mark a password reset token as used
     *
     * @param string $tokenUid The UID of the verification record
     * @return bool
     */
    public function markPasswordResetUsed(string $tokenUid): bool {
        return $this->update(
            ['verified' => 1, 'verified_at' => time()],
            ['uid' => $tokenUid]
        );
    }

    /**
     * Get a valid (not expired, not used) password reset token for the user
     * Used to reuse existing token instead of creating new ones
     *
     * @param string $userId User UID
     * @return object|null Valid reset record or null
     */
    public function getValidPasswordReset(string $userId): ?object {
        $now = time();

        $result = $this->queryGetFirst(
            $this->queryBuilder()
                ->where('user', $userId)
                ->where('purpose', 'password_reset')
                ->where('verified', 0)
                ->where('expires_at', '>', $now)
                ->order('expires_at', 'DESC')
        );

        debugLog([
            'method' => 'getValidPasswordReset',
            'user_uid' => $userId,
            'now' => $now,
            'result_found' => !isEmpty($result),
            'result_uid' => $result->uid ?? null,
            'result_expires_at' => $result->expires_at ?? null,
        ], "PWD-RESET-2FA-GET-VALID");

        return $result;
    }

    /**
     * Check if user is on cooldown based on expires_at timestamp
     * expires_at is 24h from creation, so: created_at = expires_at - 24h
     *
     * @param string $userId User UID
     * @param int $cooldownSeconds Cooldown period in seconds
     * @return array ['on_cooldown' => bool, 'wait_seconds' => int, 'existing_token' => object|null]
     */
    public function checkPasswordResetCooldown(string $userId, int $cooldownSeconds): array {
        $now = time();
        $tokenTtl = 24 * 60 * 60; // 24 hours - matches createPasswordResetToken

        // Get valid (not expired, not used) token
        $validToken = $this->getValidPasswordReset($userId);

        if (isEmpty($validToken)) {
            debugLog([
                'on_cooldown' => false,
                'reason' => 'no valid token exists',
            ], "PWD-RESET-COOLDOWN-CHECK");
            return ['on_cooldown' => false, 'wait_seconds' => 0, 'existing_token' => null];
        }

        // Calculate when token was created from expires_at
        $expiresAt = (int)$validToken->expires_at;
        $createdAt = $expiresAt - $tokenTtl;
        $secondsSinceCreated = $now - $createdAt;
        $cooldownEndsAt = $createdAt + $cooldownSeconds;
        $waitSeconds = max(0, $cooldownEndsAt - $now);

        debugLog([
            'expires_at' => $expiresAt,
            'calculated_created_at' => $createdAt,
            'now' => $now,
            'seconds_since_created' => $secondsSinceCreated,
            'cooldown_seconds' => $cooldownSeconds,
            'cooldown_ends_at' => $cooldownEndsAt,
            'wait_seconds' => $waitSeconds,
            'on_cooldown' => $waitSeconds > 0,
        ], "PWD-RESET-COOLDOWN-CHECK");

        return [
            'on_cooldown' => $waitSeconds > 0,
            'wait_seconds' => $waitSeconds,
            'existing_token' => $validToken
        ];
    }
}
