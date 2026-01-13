<?php

namespace classes\notifications;

use classes\Methods;
use Database\model\Users;
use Database\model\Orders;
use Database\model\Organisations;

/**
 * NotificationTriggers - Helper class for triggering notifications
 *
 * Usage:
 *   NotificationTriggers::orderCompleted($order, $user);
 *   NotificationTriggers::userRegistered($user);
 *   NotificationTriggers::paymentSuccessful($payment, $user, $order);
 */
class NotificationTriggers {

    // =====================================================
    // USER EVENTS
    // =====================================================

    /**
     * Trigger when a new user registers
     */
    public static function userRegistered(object|array $user): bool {
        $userData = self::normalizeData($user);

        return NotificationService::trigger('user.registered', [
            'user' => $userData,
            'app' => self::getAppContext(),
        ]);
    }

    /**
     * Trigger when user verifies their email
     */
    public static function userEmailVerified(object|array $user): bool {
        $userData = self::normalizeData($user);

        return NotificationService::trigger('user.email_verified', [
            'user' => $userData,
            'app' => self::getAppContext(),
        ]);
    }

    /**
     * Trigger when user requests password reset
     */
    public static function userPasswordReset(object|array $user, string $resetLink): bool {
        $userData = self::normalizeData($user);

        return NotificationService::trigger('user.password_reset', [
            'user' => $userData,
            'reset_link' => $resetLink,
            'app' => self::getAppContext(),
        ]);
    }

    // =====================================================
    // ORDER EVENTS
    // =====================================================

    /**
     * Trigger when an order is created
     */
    public static function orderCreated(object|array $order, ?object $user = null, ?object $organisation = null): bool {
        $context = self::buildOrderContext($order, $user, $organisation);
        return NotificationService::trigger('order.created', $context);
    }

    /**
     * Trigger when an order is completed
     */
    public static function orderCompleted(object|array $order, ?object $user = null, ?object $organisation = null): bool {
        $context = self::buildOrderContext($order, $user, $organisation);
        return NotificationService::trigger('order.completed', $context);
    }

    /**
     * Trigger when an order is cancelled
     */
    public static function orderCancelled(object|array $order, ?object $user = null, ?object $organisation = null): bool {
        $context = self::buildOrderContext($order, $user, $organisation);
        return NotificationService::trigger('order.cancelled', $context);
    }

    // =====================================================
    // PAYMENT EVENTS
    // =====================================================

    /**
     * Trigger when a payment is successful
     */
    public static function paymentSuccessful(object|array $payment, ?object $user = null, ?object $order = null): bool {
        $paymentData = self::normalizeData($payment);
        $context = [
            'payment' => $paymentData,
            'app' => self::getAppContext(),
        ];

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }
        if ($order) {
            $context['order'] = self::normalizeData($order);
        }

        return NotificationService::trigger('payment.successful', $context);
    }

    /**
     * Trigger when a payment fails
     */
    public static function paymentFailed(object|array $payment, ?object $user = null, ?object $order = null, ?string $reason = null): bool {
        $paymentData = self::normalizeData($payment);
        $context = [
            'payment' => $paymentData,
            'failure_reason' => $reason,
            'app' => self::getAppContext(),
        ];

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }
        if ($order) {
            $context['order'] = self::normalizeData($order);
        }

        return NotificationService::trigger('payment.failed', $context);
    }

    /**
     * Trigger when a payment is refunded
     */
    public static function paymentRefunded(object|array $payment, ?object $user = null, ?object $order = null): bool {
        $paymentData = self::normalizeData($payment);
        $context = [
            'payment' => $paymentData,
            'app' => self::getAppContext(),
        ];

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }
        if ($order) {
            $context['order'] = self::normalizeData($order);
        }

        return NotificationService::trigger('payment.refunded', $context);
    }

    /**
     * Trigger payment due reminder (X days before)
     */
    public static function paymentDueReminder(object|array $payment, ?object $user = null, int $daysUntilDue = 1): bool {
        $paymentData = self::normalizeData($payment);
        $context = [
            'payment' => $paymentData,
            'days_until_due' => $daysUntilDue,
            'app' => self::getAppContext(),
        ];

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }

        return NotificationService::trigger('payment.due_reminder', $context);
    }

    /**
     * Trigger payment overdue reminder
     */
    public static function paymentOverdueReminder(object|array $payment, ?object $user = null, int $daysOverdue = 1): bool {
        $paymentData = self::normalizeData($payment);
        $context = [
            'payment' => $paymentData,
            'days_overdue' => $daysOverdue,
            'app' => self::getAppContext(),
        ];

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }

        return NotificationService::trigger('payment.overdue_reminder', $context);
    }

    // =====================================================
    // ORGANISATION EVENTS
    // =====================================================

    /**
     * Trigger when a member is invited to an organisation
     */
    public static function organisationMemberInvited(
        object|array $organisation,
        string $inviteeEmail,
        ?object $inviter = null,
        ?string $inviteLink = null
    ): bool {
        $orgData = self::normalizeData($organisation);
        $context = [
            'organisation' => $orgData,
            'invitee_email' => $inviteeEmail,
            'recipient_email' => $inviteeEmail,
            'invite_link' => $inviteLink,
            'app' => self::getAppContext(),
        ];

        if ($inviter) {
            $context['inviter'] = self::normalizeData($inviter);
        }

        return NotificationService::trigger('organisation.member_invited', $context);
    }

    /**
     * Trigger when a member joins an organisation
     */
    public static function organisationMemberJoined(object|array $organisation, object|array $member): bool {
        $orgData = self::normalizeData($organisation);
        $memberData = self::normalizeData($member);

        return NotificationService::trigger('organisation.member_joined', [
            'organisation' => $orgData,
            'user' => $memberData,
            'member' => $memberData,
            'app' => self::getAppContext(),
        ]);
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Build context for order-related events
     */
    private static function buildOrderContext(object|array $order, ?object $user = null, ?object $organisation = null): array {
        $orderData = self::normalizeData($order);

        // Format amount if present
        if (isset($orderData['amount']) && isset($orderData['currency'])) {
            $orderData['formatted_amount'] = number_format($orderData['amount'] / 100, 2, ',', '.') . ' ' . $orderData['currency'];
        }

        $context = [
            'order' => $orderData,
            'app' => self::getAppContext(),
        ];

        // Resolve user if not provided
        if (!$user && !empty($orderData['uuid'])) {
            $user = Users::where('uid', $orderData['uuid'])->first();
        }

        if ($user) {
            $context['user'] = self::normalizeData($user);
        }

        // Resolve organisation if not provided
        if (!$organisation && !empty($orderData['organisation'])) {
            $organisation = Organisations::where('uid', $orderData['organisation'])->first();
        }

        if ($organisation) {
            $context['organisation'] = self::normalizeData($organisation);
        }

        return $context;
    }

    /**
     * Normalize data to array format
     */
    private static function normalizeData(object|array $data): array {
        if (is_array($data)) {
            return $data;
        }

        return (array) $data;
    }

    /**
     * Get app context for placeholders
     */
    private static function getAppContext(): array {
        return [
            'name' => BRAND_NAME,
            'url' => HOST,
            'support_email' => 'support@' . SITE_NAME,
        ];
    }
}
