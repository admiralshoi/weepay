<?php

namespace classes\cookies;

use classes\utility\Crud;
use Database\model\CookieConsents;

class CookieConsentHandler extends Crud {

    public function __construct() {
        parent::__construct(CookieConsents::newStatic(), 'cookie_consents');
    }

    /**
     * Check if an IP address has consented
     */
    public function existsByIp(string $ipAddress): bool {
        return $this->exists(['ip_address' => $ipAddress]);
    }

    /**
     * Check if a user has consented
     */
    public function existsByUser(string $userUid): bool {
        return $this->exists(['user' => $userUid]);
    }

    /**
     * Get consent record by IP address
     */
    public function getByIp(string $ipAddress): ?object {
        return $this->getFirst(['ip_address' => $ipAddress]);
    }

    /**
     * Get consent record by user UID
     */
    public function getByUser(string $userUid): ?object {
        return $this->getFirst(['user' => $userUid]);
    }

    /**
     * Record a new consent
     */
    public function recordConsent(?string $userUid, string $ipAddress, ?string $userAgent): bool {
        // Check if already consented by IP to avoid duplicates
        if ($this->existsByIp($ipAddress)) {
            // Update existing record with user if now logged in
            if ($userUid) {
                $existing = $this->excludeForeignKeys()->getByIp($ipAddress);
                if ($existing && isEmpty($existing->user)) {
                    return $this->update(['user' => $userUid], ['uid' => $existing->uid]);
                }
            }
            return true; // Already consented
        }

        // Check if user already consented from different IP
        if ($userUid && $this->existsByUser($userUid)) {
            return true; // Already consented
        }

        // Create new consent record
        return $this->create([
            'user' => $userUid,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'consented_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
