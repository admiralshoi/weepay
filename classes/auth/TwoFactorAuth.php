<?php

namespace classes\auth;

use classes\utility\Crud;
use Database\model\TwoFactorVerification;

class TwoFactorAuth extends Crud {
    private const FIXED_TEST_CODE = '123456'; // Fixed code for testing
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
    public function sendSmsCode(string $userId, string $phone, string $phoneCountryCode, string $purpose = 'phone_verification'): ?array {
        // Invalidate any existing pending codes for this user/identifier/purpose
        $this->update(
            ['verified' => -1], // -1 means invalidated
            ['user' => $userId, 'identifier' => $phone, 'purpose' => $purpose, 'verified' => 0]
        );

        // For now, use fixed test code
        $code = self::FIXED_TEST_CODE;

        // Create the message (will be encrypted in database)
        $message = "Your verification code is: $code. This code will expire in 10 minutes.";

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

        return [
            'code_id' => $this->recentUid,
            'expires_at' => $data['expires_at'],
        ];

        // TODO: When SMS provider is ready, send actual SMS here
        // $this->sendActualSms($phone, $message);
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

        // For now, use fixed test code
        $code = self::FIXED_TEST_CODE;

        // Create the message (will be encrypted in database)
        $message = "Your verification code is: $code. This code will expire in 10 minutes.";

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
}
