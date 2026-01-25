<?php

namespace classes\payments;

/**
 * Categorizes Viva payment errors by fault type
 * Based on Viva API documentation EventId codes
 */
class PaymentErrorCategorizer {

    // Merchant configuration issues - require merchant action
    const MERCHANT_FAULT_CODES = [
        10003,  // Invalid merchant number
        10057,  // Function not permitted to cardholder (recurring not enabled)
        10058,  // Function not permitted to terminal
        10065,  // Soft decline - SCA required
        10301,  // Soft decline - SCA required
    ];

    // HTTP error codes that indicate merchant configuration issues
    // These are returned when Viva's ErrorCode indicates account/API issues
    const MERCHANT_FAULT_HTTP_CODES = [
        403,    // API action disabled for merchant (recurring not enabled)
    ];

    // Consumer/Card issues - consumer needs to take action
    const CONSUMER_FAULT_CODES = [
        10014,  // Invalid card number
        10043,  // Stolen card
        10054,  // Expired card
        10051,  // Insufficient funds
        10041,  // Lost card
        10222,  // Lost card (Visa)
        10223,  // Stolen card (Visa)
        10059,  // Suspected fraud
        10062,  // Restricted card
        10061,  // Withdrawal limit exceeded
        10213,  // Revocation of authorization order
        10004,  // Pick up card
        10007,  // Pick up card (fraud)
        10046,  // Closed account
        10078,  // Card blocked or not activated
        10212,  // Blocked card
        10224,  // Closed account (Visa)
        10226,  // Card blocked by issuer (Visa)
        10227,  // Card blocked by cardholder (Visa)
        2061,   // 3DS flow incomplete
        2062,   // 3DS validation failed
        10075,  // PIN entry tries exceeded
        10210,  // Invalid CVV
    ];

    // Issuer declined - consumer should contact bank
    const ISSUER_FAULT_CODES = [
        10001,  // Refer to card issuer
        10005,  // Do not honor
        10006,  // General error
        10012,  // Invalid transaction
        10039,  // No credit account
        10052,  // No checking account
        10053,  // No savings account
        10055,  // Incorrect PIN
        10063,  // Security violation
        10070,  // Call issuer
        10077,  // Invalid "from account"
        10079,  // Life cycle
        10080,  // No financial impact
        10081,  // Domestic debit not allowed
        10083,  // Fraud/Security
        10084,  // Invalid authorization life cycle
        10086,  // PIN validation not possible
        10088,  // Cryptographic failure
        10089,  // Unacceptable PIN
        10091,  // Issuer unavailable
        10093,  // Violation of law
        10094,  // Duplicate transmission
        10199,  // Empty issuer response
        10201,  // Force reversal
        10211,  // Negative CAM/CVV results
        10214,  // Verification data failed
        10215,  // Policy
        10216,  // Invalid account
        10221,  // No such issuer (Visa)
        10300,  // SCA requested - online PIN
        10302,  // SCA requested - second tap
        10400,  // Bancontact - use chip
        10401,  // Issuer unavailable
        18202,  // Cartes Bancaires void validation failed
    ];

    // System/Gateway issues - retry later
    const SYSTEM_FAULT_CODES = [
        0,      // Undefined
        7001,   // Transaction failed (generic)
        10096,  // System malfunction
        10200,  // Generic error
        6614,   // Transport channel timeout
        6624,   // Transport channel write failed
        6650,   // Transport proxy send failed
        10013,  // Invalid amount
        10015,  // Invalid issuer
        10019,  // Re-enter transaction
        10021,  // No action taken
        10030,  // Format error
        10076,  // Invalid "to account"
        317,    // Acquiring offline decline
        2108,   // Payments policy acquiring restriction
        3500,   // Device not found
        101003, // Payment method parse response failed
    ];

    // Maps event IDs to notification types
    const EVENT_TO_TYPE = [
        10003 => 'invalid_merchant_config',
        10057 => 'recurring_not_enabled',
        10058 => 'recurring_not_enabled',
        10065 => 'sca_required',
        10301 => 'sca_required',
    ];

    // Maps HTTP error codes to notification types (default - for charge actions)
    const HTTP_TO_TYPE = [
        403 => 'recurring_not_enabled',
    ];

    // Maps HTTP error codes to notification types for refund actions
    const HTTP_TO_TYPE_REFUND = [
        403 => 'refund_not_enabled',
    ];

    // Maps event IDs to human-readable titles (Danish)
    const EVENT_TITLES = [
        10003 => 'Ugyldigt forhandlernummer',
        10057 => 'Recurring betalinger ikke aktiveret',
        10058 => 'Terminal ikke konfigureret til denne type betaling',
        10065 => '3D Secure påkrævet',
        10301 => '3D Secure påkrævet',
        // Generic fallbacks
        'merchant' => 'Forhandlerkonfiguration påkrævet',
        'consumer' => 'Kortproblem',
        'system' => 'Systemfejl',
        'issuer' => 'Bank afvisning',
    ];

    // Maps HTTP error codes to titles (Danish) - default for charge actions
    const HTTP_TITLES = [
        403 => 'Recurring betalinger ikke aktiveret',
    ];

    // Maps HTTP error codes to titles (Danish) - for refund actions
    const HTTP_TITLES_REFUND = [
        403 => 'Refunderinger ikke aktiveret',
    ];

    // Maps event IDs to detailed messages (Danish)
    const EVENT_MESSAGES = [
        10003 => 'Betalingen fejlede fordi dit forhandlernummer er ugyldigt eller ikke genkendt. Kontakt venligst support for at verificere din kontokonfiguration.',
        10057 => 'Betalingen fejlede fordi recurring/abonnementsbetalinger ikke er aktiveret på din Viva-konto. Kontakt venligst Viva support for at aktivere denne funktion.',
        10058 => 'Betalingen fejlede fordi din terminal eller konto ikke er konfigureret til denne type transaktion. Kontakt venligst support for at aktivere de nødvendige funktioner.',
        10065 => 'Betalingen kræver 3D Secure authentication, men dette er ikke korrekt konfigureret for recurring betalinger. Kontakt support for at løse dette.',
        10301 => 'Betalingen kræver 3D Secure authentication, men dette er ikke korrekt konfigureret for recurring betalinger. Kontakt support for at løse dette.',
    ];

    // Maps HTTP error codes to detailed messages (Danish) - default for charge actions
    const HTTP_MESSAGES = [
        403 => 'Betalingen fejlede fordi recurring/abonnementsbetalinger ikke er aktiveret på din Viva-konto. Kontakt venligst Viva support for at aktivere denne funktion.',
    ];

    // Maps HTTP error codes to detailed messages (Danish) - for refund actions
    const HTTP_MESSAGES_REFUND = [
        403 => 'Refunderingen fejlede fordi refunderinger ikke er aktiveret på din Viva-konto. Kontakt venligst Viva support for at aktivere denne funktion.',
    ];

    /**
     * Categorize an event ID by fault type
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @return string 'merchant', 'consumer', 'system', or 'platform'
     */
    public function categorize(int $eventId, ?int $httpErrorCode = null): string {
        // Check HTTP error code first (e.g., 403 = API disabled = merchant config issue)
        if ($httpErrorCode !== null && in_array($httpErrorCode, self::MERCHANT_FAULT_HTTP_CODES)) {
            return 'merchant';
        }

        if (in_array($eventId, self::MERCHANT_FAULT_CODES)) {
            return 'merchant';
        }

        if (in_array($eventId, self::CONSUMER_FAULT_CODES)) {
            return 'consumer';
        }

        if (in_array($eventId, self::ISSUER_FAULT_CODES)) {
            return 'consumer'; // Issuer issues are still consumer's responsibility
        }

        if (in_array($eventId, self::SYSTEM_FAULT_CODES)) {
            return 'system';
        }

        // Unknown codes default to system
        return 'system';
    }

    /**
     * Check if an event ID requires merchant attention
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @return bool
     */
    public function requiresMerchantAttention(int $eventId, ?int $httpErrorCode = null): bool {
        // Check HTTP error code first (e.g., 403 = API disabled = merchant config issue)
        if ($httpErrorCode !== null && in_array($httpErrorCode, self::MERCHANT_FAULT_HTTP_CODES)) {
            return true;
        }

        return in_array($eventId, self::MERCHANT_FAULT_CODES);
    }

    /**
     * Get notification type for an event ID
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @param string|null $action The action type (e.g., 'charge', 'refund', 'createPayment')
     * @return string Notification type enum value
     */
    public function getNotificationType(int $eventId, ?int $httpErrorCode = null, ?string $action = null): string {
        // Check HTTP error code first - use action-specific mapping if available
        if ($httpErrorCode !== null) {
            if ($action === 'refund' && isset(self::HTTP_TO_TYPE_REFUND[$httpErrorCode])) {
                return self::HTTP_TO_TYPE_REFUND[$httpErrorCode];
            }
            if (isset(self::HTTP_TO_TYPE[$httpErrorCode])) {
                return self::HTTP_TO_TYPE[$httpErrorCode];
            }
        }

        return self::EVENT_TO_TYPE[$eventId] ?? 'other';
    }

    /**
     * Get severity for an event ID
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @return string 'critical', 'warning', or 'info'
     */
    public function getSeverity(int $eventId, ?int $httpErrorCode = null): string {
        // HTTP merchant fault codes are critical
        if ($httpErrorCode !== null && in_array($httpErrorCode, self::MERCHANT_FAULT_HTTP_CODES)) {
            return 'critical';
        }

        // Merchant fault codes are critical - they need immediate action
        if (in_array($eventId, self::MERCHANT_FAULT_CODES)) {
            return 'critical';
        }

        // System errors are warnings - may resolve themselves
        if (in_array($eventId, self::SYSTEM_FAULT_CODES)) {
            return 'warning';
        }

        return 'info';
    }

    /**
     * Get human-readable title for an event ID
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @param string|null $action The action type (e.g., 'charge', 'refund', 'createPayment')
     * @return string
     */
    public function getTitle(int $eventId, ?int $httpErrorCode = null, ?string $action = null): string {
        // Check HTTP error code first - use action-specific mapping if available
        if ($httpErrorCode !== null) {
            if ($action === 'refund' && isset(self::HTTP_TITLES_REFUND[$httpErrorCode])) {
                return self::HTTP_TITLES_REFUND[$httpErrorCode];
            }
            if (isset(self::HTTP_TITLES[$httpErrorCode])) {
                return self::HTTP_TITLES[$httpErrorCode];
            }
        }

        if (isset(self::EVENT_TITLES[$eventId])) {
            return self::EVENT_TITLES[$eventId];
        }

        // Fallback based on category
        $category = $this->categorize($eventId, $httpErrorCode);
        return self::EVENT_TITLES[$category] ?? 'Betalingsfejl';
    }

    /**
     * Get detailed message for an event ID
     *
     * @param int $eventId Viva EventId
     * @param object|null $payment Payment object for context
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @param string|null $action The action type (e.g., 'charge', 'refund', 'createPayment')
     * @return string
     */
    public function getMessage(int $eventId, ?object $payment = null, ?int $httpErrorCode = null, ?string $action = null): string {
        // Check HTTP error code first - use action-specific mapping if available
        if ($httpErrorCode !== null) {
            if ($action === 'refund' && isset(self::HTTP_MESSAGES_REFUND[$httpErrorCode])) {
                $baseMessage = self::HTTP_MESSAGES_REFUND[$httpErrorCode];
            } elseif (isset(self::HTTP_MESSAGES[$httpErrorCode])) {
                $baseMessage = self::HTTP_MESSAGES[$httpErrorCode];
            } else {
                $baseMessage = self::EVENT_MESSAGES[$eventId] ?? $this->getGenericMessage($eventId, $httpErrorCode);
            }
        } else {
            $baseMessage = self::EVENT_MESSAGES[$eventId] ?? $this->getGenericMessage($eventId, $httpErrorCode);
        }

        // Add payment context if available
        if (!isEmpty($payment)) {
            $context = [];

            if (!isEmpty($payment->failure_reason)) {
                $context[] = "Fejlbesked: {$payment->failure_reason}";
            }

            if (!isEmpty($payment->amount)) {
                $context[] = "Beløb: " . number_format($payment->amount, 2, ',', '.') . " kr";
            }

            if (!isEmpty($payment->installment_number)) {
                $context[] = "Betaling nr. {$payment->installment_number}";
            }

            if (!empty($context)) {
                $baseMessage .= "\n\n" . implode("\n", $context);
            }
        }

        return $baseMessage;
    }

    /**
     * Get generic message based on category
     *
     * @param int $eventId Viva EventId
     * @param int|null $httpErrorCode HTTP error code from Viva (e.g., 403)
     * @return string
     */
    private function getGenericMessage(int $eventId, ?int $httpErrorCode = null): string {
        $category = $this->categorize($eventId, $httpErrorCode);

        return match ($category) {
            'merchant' => 'Der er opstået en konfigurationsfejl med din betalingskonto. Kontakt venligst support for assistance.',
            'consumer' => 'Betalingen blev afvist af kundens bank eller kort. Kunden bør kontakte deres bank eller bruge et andet betalingskort.',
            'system' => 'Der opstod en midlertidig systemfejl. Betalingen vil automatisk blive forsøgt igen.',
            default => 'Der opstod en fejl under betalingsbehandlingen.',
        };
    }

    /**
     * Check if error should trigger retry
     * Consumer and system errors can be retried, merchant errors should not
     *
     * @param int $eventId Viva EventId
     * @return bool
     */
    public function shouldRetry(int $eventId): bool {
        // Don't retry merchant configuration issues - they won't resolve themselves
        if (in_array($eventId, self::MERCHANT_FAULT_CODES)) {
            return false;
        }

        // System errors can be retried
        if (in_array($eventId, self::SYSTEM_FAULT_CODES)) {
            return true;
        }

        // Consumer/issuer errors can be retried (card may be topped up, etc.)
        return true;
    }

    /**
     * Get all known event IDs with their categories
     *
     * @return array
     */
    public static function getAllCategories(): array {
        $categories = [];

        foreach (self::MERCHANT_FAULT_CODES as $code) {
            $categories[$code] = 'merchant';
        }

        foreach (self::CONSUMER_FAULT_CODES as $code) {
            $categories[$code] = 'consumer';
        }

        foreach (self::ISSUER_FAULT_CODES as $code) {
            $categories[$code] = 'issuer';
        }

        foreach (self::SYSTEM_FAULT_CODES as $code) {
            $categories[$code] = 'system';
        }

        return $categories;
    }

}
