<?php

namespace classes\api;
use classes\Methods;
use env\api\Gateway as API;

class GatewayApi {

    /**
     * Send an SMS using GatewayAPI
     * @param string $recipient Phone number with country code (e.g., +4512345678)
     * @param string $message Message content (max 160 chars for single SMS)
     * @param string|null $sender Sender name (max 11 chars) or null for default
     * @return array Response from GatewayAPI
     */
    public function sendSms(string $recipient, string $message, ?string $sender = null): array {
        $requests = Methods::requests();

        $normalizedPhone = $this->normalizePhoneNumber($recipient);

        // Build payload
        $payload = [
            'recipients' => [
                ['msisdn' => $normalizedPhone]
            ],
            'message' => $message,
        ];

        // Add sender if provided
        if (!empty($sender)) {
            $payload['sender'] = substr($sender, 0, 11);
        }

        debugLog([
            'recipient_input' => $recipient,
            'normalized_phone' => $normalizedPhone,
            'message_length' => strlen($message),
            'sender' => $sender,
            'payload' => $payload,
        ], 'GatewayApi_sendSms');

        // Set up request
        $requests->basicAuth(API::TOKEN, ''); //Intentional. no pwd to use.
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        $requests->post('https://gatewayapi.eu/rest/mtsms');

        $response = $requests->getResponse();

        debugLog([
            'response' => $response,
        ], 'GatewayApi_sendSms_response');

        return $response;
    }

    /**
     * Send bulk SMS to multiple recipients
     * @param array $recipients Array of phone numbers
     * @param string $message Message content
     * @param string|null $sender Sender name or null for default
     * @return array Response from GatewayAPI
     */
    public function sendBulkSms(array $recipients, string $message, ?string $sender = null): array {
        $requests = Methods::requests();

        // Build recipients array
        $recipientsData = [];
        foreach ($recipients as $recipient) {
            $recipientsData[] = ['msisdn' => $this->normalizePhoneNumber($recipient)];
        }

        // Build payload
        $payload = [
            'recipients' => $recipientsData,
            'message' => $message,
        ];

        // Add sender if provided
        if (!empty($sender)) {
            $payload['sender'] = substr($sender, 0, 11);
        }

        // Set up request
        $requests->setBearerToken(API::TOKEN);
        $requests->setHeaderContentTypeJson();
        $requests->setBody($payload);
        $requests->post('https://gatewayapi.com/rest/mtsms');

        return $requests->getResponse();
    }

    /**
     * Get delivery status for an SMS
     * @param int $messageId Message ID from send response
     * @return array Response from GatewayAPI
     */
    public function getStatus(int $messageId): array {
        $requests = Methods::requests();

        $requests->setBearerToken(API::TOKEN);
        $requests->get("https://gatewayapi.com/rest/mtsms/{$messageId}");

        return $requests->getResponse();
    }

    /**
     * Normalize phone number to international format without spaces or special chars
     * @param string $phone Phone number (e.g., +45 12 34 56 78 or 12345678)
     * @return int Phone number as integer (e.g., 4512345678)
     */
    private function normalizePhoneNumber(string $phone): int {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // If phone doesn't start with country code, assume Danish (+45)
        if (strlen($phone) === 8) {
            $phone = '45' . $phone;
        }

        return (int)$phone;
    }

    /**
     * Generate a random 6-digit verification code
     * @return string 6-digit code
     */
    public static function generateVerificationCode(): string {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
}
