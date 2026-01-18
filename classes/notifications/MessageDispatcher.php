<?php

namespace classes\notifications;

use classes\api\GatewayApi;
use classes\Methods;
use classes\utility\Numbers;
use Database\model\Users;

/**
 * MessageDispatcher - Central class for sending messages via different channels
 *
 * Provides simple static methods for sending:
 * - Email (text + HTML via PHP mail())
 * - SMS (via GatewayAPI)
 * - Bell notifications (in-app, stored in database)
 *
 * Usage:
 *   MessageDispatcher::email('user@example.com', 'Subject', 'Plain text', '<p>HTML</p>');
 *   MessageDispatcher::sms('+4512345678', 'Your message here');
 *   MessageDispatcher::bell('user_uid', 'Title', 'Content');
 */
class MessageDispatcher {

    private const DEFAULT_FROM_EMAIL = "no-reply@wee-pay.dk";
    private const DEFAULT_SMS_SENDER = "WeePay";

    // =====================================================
    // EMAIL
    // =====================================================

    /**
     * Send an email with plain text and optional HTML content
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $textContent Plain text content
     * @param string|null $htmlContent Optional HTML content (if null, text is converted to basic HTML)
     * @param string|null $fromEmail Optional sender email
     * @param string|null $fromName Optional sender name
     * @return bool True on success
     */
    public static function email(
        string $to,
        string $subject,
        string $textContent,
        ?string $htmlContent = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): bool {
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            debugLog(['error' => 'Invalid email address', 'to' => $to], 'MESSAGE_DISPATCHER_EMAIL');
            return false;
        }

        // Don't send emails in non-live environment, but log them
        if (!LIVE) {
            debugLog([
                'to' => $to,
                'subject' => $subject,
                'text' => substr($textContent, 0, 200),
                'html' => $htmlContent ? 'yes' : 'no',
            ], 'MESSAGE_DISPATCHER_EMAIL_DEBUG');
            return true; // Return true in dev to not break flows
        }

        $fromEmail = $fromEmail ?? self::DEFAULT_FROM_EMAIL;
        $fromName = $fromName ?? strtoupper(BRAND_NAME);

        // Encode from name for UTF-8 support (RFC 2047)
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';

        debugLog(compact('fromEmail', 'fromName', 'encodedFromName'), 'MESSAGE_DISPATCHER_EMAIL_FROM');

        // Generate boundary for multipart
        $boundary = md5(uniqid('', true));

        // Build headers
        $headers = "From: $encodedFromName <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";

        // Build message body
        $message = "";

        // Plain text part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($textContent));

        // HTML part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";

        if ($htmlContent) {
            $message .= chunk_split(base64_encode($htmlContent));
        } else {
            // Convert plain text to basic HTML
            $basicHtml = self::textToHtml($textContent);
            $message .= chunk_split(base64_encode($basicHtml));
        }

        $message .= "--$boundary--";

        // Send the email with envelope sender for better deliverability
        $result = @mail($to, $subject, $message, $headers, "-f$fromEmail");

        if (!$result) {
            debugLog([
                'error' => 'mail() failed',
                'to' => $to,
                'subject' => $subject,
            ], 'MESSAGE_DISPATCHER_EMAIL_FAILED');
        }

        return $result;
    }

    /**
     * Send email to a user by their UID
     *
     * @param string $userUid User's UID
     * @param string $subject Email subject
     * @param string $textContent Plain text content
     * @param string|null $htmlContent Optional HTML content
     * @return bool True on success
     */
    public static function emailToUser(
        string $userUid,
        string $subject,
        string $textContent,
        ?string $htmlContent = null
    ): bool {
        $user = Users::where('uid', $userUid)->first();
        if (!$user || empty($user->email)) {
            debugLog(['error' => 'User not found or no email', 'uid' => $userUid], 'MESSAGE_DISPATCHER_EMAIL');
            return false;
        }

        return self::email($user->email, $subject, $textContent, $htmlContent);
    }

    // =====================================================
    // SMS
    // =====================================================

    /**
     * Send an SMS message
     *
     * @param string $phone Phone number (with or without country code)
     * @param string $message Message content (max ~160 chars for single SMS)
     * @param string|null $sender Sender name (max 11 chars)
     * @param string|null $countryCode Country code if phone doesn't include it (e.g., '45' for Denmark)
     * @return bool True on success
     */
    public static function sms(
        string $phone,
        string $message,
        ?string $sender = null,
        ?string $countryCode = null
    ): bool {
        // Clean the phone number
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If no + and no country code in phone, add default country code
        if (!str_starts_with($phone, '+') && strlen($phone) <= 10) {
            $countryCode = $countryCode ?? '45'; // Default to Denmark
            $phone = '+' . $countryCode . ltrim($phone, '0');
        }

        // Don't send SMS in non-live environment, but log them
        if (!LIVE) {
            debugLog([
                'to' => $phone,
                'message' => $message,
                'sender' => $sender ?? self::DEFAULT_SMS_SENDER,
            ], 'MESSAGE_DISPATCHER_SMS_DEBUG');
            return true; // Return true in dev to not break flows
        }

        try {
            $gateway = new GatewayApi();
            $response = $gateway->sendSms($phone, $message, $sender ?? self::DEFAULT_SMS_SENDER);

            // Check for success (GatewayAPI returns 'ids' array on success)
            if (!empty($response['ids']) && is_array($response['ids'])) {
                debugLog([
                    'success' => true,
                    'to' => $phone,
                    'message_id' => $response['ids'][0] ?? null,
                ], 'MESSAGE_DISPATCHER_SMS_SENT');
                return true;
            }

            debugLog([
                'success' => false,
                'to' => $phone,
                'response' => $response,
            ], 'MESSAGE_DISPATCHER_SMS_FAILED');
            return false;

        } catch (\Exception $e) {
            debugLog([
                'error' => $e->getMessage(),
                'to' => $phone,
            ], 'MESSAGE_DISPATCHER_SMS_EXCEPTION');
            return false;
        }
    }

    /**
     * Send SMS to a user by their UID
     *
     * @param string $userUid User's UID
     * @param string $message Message content
     * @param string|null $sender Sender name
     * @return bool True on success
     */
    public static function smsToUser(
        string $userUid,
        string $message,
        ?string $sender = null
    ): bool {
        $user = Users::where('uid', $userUid)->first();
        if (!$user || empty($user->phone)) {
            debugLog(['error' => 'User not found or no phone', 'uid' => $userUid], 'MESSAGE_DISPATCHER_SMS');
            return false;
        }

        // Get country code from user if available and convert to dialer code
        $dialerCode = null;
        if (!empty($user->phone_country_code)) {
            $dialerCode = \classes\utility\Misc::callerCode($user->phone_country_code);
        }

        return self::sms($user->phone, $message, $sender, $dialerCode);
    }

    // =====================================================
    // BELL (In-App Notifications)
    // =====================================================

    /**
     * Send a bell notification (in-app notification)
     *
     * @param string $userUid Recipient user's UID
     * @param string $title Notification title
     * @param string $content Notification content
     * @param string $type Notification type: 'info', 'success', 'warning', 'error'
     * @param string|null $icon MDI icon class (e.g., 'mdi-bell-outline')
     * @param string|null $link Optional link to navigate to when clicked
     * @param string|null $referenceType Optional reference type (e.g., 'order', 'payment')
     * @param string|null $referenceId Optional reference ID (e.g., order UID)
     * @return bool True on success
     */
    public static function bell(
        string $userUid,
        string $title,
        string $content,
        string $type = 'info',
        ?string $icon = null,
        ?string $link = null,
        ?string $referenceType = null,
        ?string $referenceId = null
    ): bool {
        // Validate user exists
        if (!Users::where('uid', $userUid)->exists()) {
            debugLog(['error' => 'User not found', 'uid' => $userUid], 'MESSAGE_DISPATCHER_BELL');
            return false;
        }

        // Default icon based on type
        if (!$icon) {
            $icon = match ($type) {
                'success' => 'mdi-check-circle-outline',
                'warning' => 'mdi-alert-outline',
                'error' => 'mdi-alert-circle-outline',
                default => 'mdi-bell-outline',
            };
        }

        return Methods::userNotifications()->insert(
            $userUid,
            $title,
            $content,
            $type,
            $icon,
            $link,
            $referenceType,
            $referenceId
        );
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Convert plain text to basic HTML
     * Preserves line breaks and escapes special characters
     */
    private static function textToHtml(string $text): string {
        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $withBreaks = nl2br($escaped);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #333; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto;">
        $withBreaks
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get user's contact info by UID
     * Returns array with 'email' and 'phone' keys
     */
    public static function getUserContactInfo(string $userUid): ?array {
        $user = Users::where('uid', $userUid)->first();
        if (!$user) {
            return null;
        }

        return [
            'email' => $user->email ?? null,
            'phone' => $user->phone ?? null,
            'phone_country_code' => $user->phone_country_code ?? null,
            'full_name' => $user->full_name ?? null,
        ];
    }
}
