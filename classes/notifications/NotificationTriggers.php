<?php

namespace classes\notifications;

use classes\enumerations\Links;
use classes\Methods;
use Database\model\Users;
use Database\model\Orders;
use Database\model\Organisations;
use Database\model\Locations;

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
        $userData = self::normalizeUserData($user);

        return NotificationService::trigger('user.registered', [
            'user' => $userData,
            'app' => self::getAppContext(),
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'email_title' => 'Velkommen',
        ]);
    }

    /**
     * Trigger when user verifies their email
     */
    public static function userEmailVerified(object|array $user): bool {
        $userData = self::normalizeUserData($user);

        return NotificationService::trigger('user.email_verified', [
            'user' => $userData,
            'app' => self::getAppContext(),
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'email_title' => 'Email bekræftet',
        ]);
    }

    /**
     * Trigger when user requests password reset
     */
    public static function userPasswordReset(object|array $user, string $resetLink): bool {
        $userData = self::normalizeUserData($user);

        return NotificationService::trigger('user.password_reset', [
            'user' => $userData,
            'reset_link' => $resetLink,
            'app' => self::getAppContext(),
            'email_title' => 'Nulstil adgangskode',
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
        $orderUid = is_array($order) ? ($order['uid'] ?? 'unknown') : ($order->uid ?? 'unknown');
        $orderPlan = is_array($order) ? ($order['payment_plan'] ?? 'unknown') : ($order->payment_plan ?? 'unknown');

        debugLog([
            'order_uid' => $orderUid,
            'payment_plan' => $orderPlan,
            'timestamp' => date('Y-m-d H:i:s.u'),
            'user_provided' => $user !== null,
            'org_provided' => $organisation !== null,
        ], 'NOTIFICATION_ORDER_COMPLETED_ENTRY');

        $context = self::buildOrderContext($order, $user, $organisation);

        debugLog([
            'order_uid' => $orderUid,
            'context_order_uid' => $context['order']['uid'] ?? 'missing',
            'context_order_payment_plan' => $context['order']['payment_plan'] ?? 'missing',
            'context_user_email' => $context['user']['email'] ?? 'missing',
            'has_location' => isset($context['location']),
            'location_name' => $context['location']['name'] ?? null,
            'has_payment_plan' => isset($context['payment_plan']),
            'payment_plan_type' => $context['payment_plan']['type'] ?? null,
            'payment_plan_total' => $context['payment_plan']['total_amount_formatted'] ?? null,
            'payment_plan_first' => $context['payment_plan']['first_amount_formatted'] ?? null,
        ], 'NOTIFICATION_ORDER_COMPLETED_CONTEXT');

        debugLog([
            'order_uid' => $orderUid,
            'about_to_trigger' => 'order.completed',
        ], 'NOTIFICATION_ORDER_COMPLETED_BEFORE_TRIGGER');

        $result = NotificationService::trigger('order.completed', $context);

        debugLog([
            'order_uid' => $orderUid,
            'trigger_result' => $result,
        ], 'NOTIFICATION_ORDER_COMPLETED_AFTER_TRIGGER');

        return $result;
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
        $context = self::buildPaymentContext($payment, $user, $order);
        $context['email_title'] = 'Betaling modtaget';
        return NotificationService::trigger('payment.successful', $context);
    }

    /**
     * Trigger when a payment fails
     */
    public static function paymentFailed(object|array $payment, ?object $user = null, ?object $order = null, ?string $reason = null): bool {
        $context = self::buildPaymentContext($payment, $user, $order);
        $context['failure_reason'] = $reason ?? 'Betalingen kunne ikke gennemføres';
        $context['email_title'] = 'Betaling fejlet';
        return NotificationService::trigger('payment.failed', $context);
    }

    /**
     * Trigger when a payment becomes past due (after max retry attempts)
     *
     * @param object|array $payment The payment that is past due
     * @param object|null $user The customer
     * @param object|null $order The order
     * @param string|null $reason The reason for failure
     */
    public static function paymentPastDue(object|array $payment, ?object $user = null, ?object $order = null, ?string $reason = null): bool {
        $context = self::buildPaymentContext($payment, $user, $order);
        $context['failure_reason'] = $reason ?? 'Betalingen kunne ikke gennemføres';
        $context['email_title'] = 'Betaling forsinket';
        return NotificationService::trigger('payment.past_due', $context);
    }

    /**
     * Trigger when a payment is refunded
     *
     * @param object|array $payment The payment being refunded
     * @param object|null $user The customer (optional, resolved from payment/order if not provided)
     * @param object|null $order The order (optional, resolved from payment if not provided)
     * @param float|null $refundAmount The amount refunded (optional, defaults to payment amount)
     * @param string|null $refundReason Optional reason for the refund
     * @param object|null $organisation The organisation (optional, resolved from order if not provided)
     * @param object|null $location The location (optional, resolved from order if not provided)
     */
    public static function paymentRefunded(
        object|array $payment,
        ?object $user = null,
        ?object $order = null,
        ?float $refundAmount = null,
        ?string $refundReason = null,
        ?object $organisation = null,
        ?object $location = null
    ): bool {
        $context = self::buildPaymentContext($payment, $user, $order);
        $context['email_title'] = 'Betaling refunderet';

        // Get currency from payment or order
        $paymentData = self::normalizeData($payment);
        $currency = $paymentData['currency'] ?? $context['order']['currency'] ?? 'DKK';

        // Add refund-specific context
        $paymentAmount = (float)($paymentData['amount'] ?? 0);
        $actualRefundAmount = $refundAmount ?? $paymentAmount;

        $context['refund_amount'] = $actualRefundAmount;
        $context['refund_formatted_amount'] = number_format($actualRefundAmount, 2, ',', '.') . ' ' . $currency;
        $context['refund_reason'] = $refundReason ?? 'Refundering anmodet af forretningen';

        // Determine if partial or full refund
        $context['is_partial_refund'] = $actualRefundAmount < $paymentAmount;
        $context['is_full_refund'] = $actualRefundAmount >= $paymentAmount;

        // Add organisation if provided or resolve from order
        if ($organisation) {
            $context['organisation'] = self::normalizeData($organisation);
        } elseif (!isset($context['organisation']) && $order) {
            $orderData = self::normalizeData($order);
            if (!empty($orderData['organisation'])) {
                $orgValue = $orderData['organisation'];
                if (is_object($orgValue)) {
                    $context['organisation'] = self::normalizeData($orgValue);
                } else {
                    $org = Methods::organisations()->get($orgValue);
                    if ($org) $context['organisation'] = self::normalizeData($org);
                }
            }
        }

        // Add location if provided or resolve from order
        if ($location) {
            $context['location'] = self::normalizeData($location);
        } elseif (!isset($context['location']) && $order) {
            $orderData = self::normalizeData($order);
            if (!empty($orderData['location'])) {
                $locValue = $orderData['location'];
                if (is_object($locValue)) {
                    $context['location'] = self::normalizeData($locValue);
                } else {
                    $loc = Methods::locations()->get($locValue);
                    if ($loc) $context['location'] = self::normalizeData($loc);
                }
            }
        }

        // Add refund date/time
        $context['refund_date'] = date('d.m.Y');
        $context['refund_time'] = date('H:i');
        $context['refund_datetime'] = date('d.m.Y H:i');

        debugLog([
            'payment_uid' => $paymentData['uid'] ?? null,
            'refund_amount' => $actualRefundAmount,
            'user' => $context['user']['email'] ?? null,
            'organisation' => $context['organisation']['name'] ?? null,
            'location' => $context['location']['name'] ?? null,
        ], 'NotificationTriggers_paymentRefunded');

        return NotificationService::trigger('payment.refunded', $context);
    }

    /**
     * Trigger when an order is refunded (at least one payment refunded)
     * This is triggered for order-level refunds, not individual payment refunds
     *
     * @param object|array $order The order being refunded
     * @param object|null $user The customer
     * @param float $totalRefunded Total amount refunded in this operation
     * @param int $paymentsRefundedCount Number of payments refunded
     * @param int $paymentsVoidedCount Number of payments voided
     * @param string|null $refundReason Reason for refund
     * @param object|null $organisation The organisation
     * @param object|null $location The location
     * @param array|null $payments Array of payment objects for BNPL orders
     */
    public static function orderRefunded(
        object|array $order,
        ?object $user = null,
        float $totalRefunded = 0,
        int $paymentsRefundedCount = 0,
        int $paymentsVoidedCount = 0,
        ?string $refundReason = null,
        ?object $organisation = null,
        ?object $location = null,
        ?array $payments = null
    ): bool {
        $orderData = self::normalizeData($order);
        $currency = $orderData['currency'] ?? 'DKK';

        // Format order amount if present (amount is stored in DKK, not øre)
        if (isset($orderData['amount'])) {
            $orderData['formatted_amount'] = number_format((float)$orderData['amount'], 2, ',', '.') . ' ' . $currency;
        }

        // Add created_date formatting
        $createdAt = $orderData['created_at'] ?? null;
        if ($createdAt) {
            $timestamp = is_numeric($createdAt) ? (int)$createdAt : strtotime($createdAt);
            $orderData['created_date'] = date('d.m.Y', $timestamp);
            $orderData['created_time'] = date('H:i', $timestamp);
            $orderData['created_datetime'] = date('d.m.Y H:i', $timestamp);
        }

        $context = [
            'order' => $orderData,
            'total_refunded' => $totalRefunded,
            'total_refunded_formatted' => number_format($totalRefunded, 2, ',', '.') . ' ' . $currency,
            'payments_refunded_count' => $paymentsRefundedCount,
            'payments_voided_count' => $paymentsVoidedCount,
            'refund_reason' => $refundReason ?? 'Ordre refunderet',
            'app' => self::getAppContext(),
            'email_title' => 'Ordre refunderet',
        ];

        // Add user context if provided
        if ($user) {
            $userData = self::normalizeUserData($user);
            $context['user'] = $userData;
            $context['recipient_email'] = $userData['email'] ?? null;
            $context['recipient_phone'] = $userData['phone'] ?? $userData['phone_number'] ?? null;
            $context['recipient_user_uid'] = $userData['uid'] ?? null;
        }

        // Add organisation if provided or resolve from order
        if ($organisation) {
            $context['organisation'] = self::normalizeData($organisation);
        } elseif (!empty($orderData['organisation'])) {
            $orgValue = $orderData['organisation'];
            if (is_object($orgValue)) {
                $context['organisation'] = self::normalizeData($orgValue);
            } else {
                $org = Methods::organisations()->get($orgValue);
                if ($org) $context['organisation'] = self::normalizeData($org);
            }
        }

        // Add location if provided or resolve from order
        $locationData = null;
        if ($location) {
            $locationData = self::normalizeData($location);
        } elseif (!empty($orderData['location'])) {
            $locValue = $orderData['location'];
            if (is_object($locValue)) {
                $locationData = self::normalizeData($locValue);
            } else {
                $loc = Methods::locations()->get($locValue);
                if ($loc) $locationData = self::normalizeData($loc);
            }
        }
        if ($locationData) {
            $context['location'] = $locationData;
            // Add hero HTML for location-branded emails
            $context['location_hero_html'] = self::buildLocationHeroHtml($locationData);
        }

        // Add refund date/time
        $context['refund_date'] = date('d.m.Y');
        $context['refund_time'] = date('H:i');
        $context['refund_datetime'] = date('d.m.Y H:i');

        // Add links
        $context['order_link'] = __url(Links::$consumer->orderDetail($orderData['uid'] ?? ''));
        $context['receipt_link'] = __url(Links::$consumer->orderDetail($orderData['uid'] ?? ''));
        $context['dashboard_link'] = __url(Links::$consumer->dashboard);

        // Build payments list HTML for BNPL orders
        if ($payments && count($payments) > 0) {
            $paymentsHtmlRows = '';
            $paymentsTextRows = '';
            $paymentNumber = 1;
            $statusMap = [
                'COMPLETED' => ['label' => 'Betalt', 'color' => '#4caf50'],
                'REFUNDED' => ['label' => 'Refunderet', 'color' => '#2196f3'],
                'VOIDED' => ['label' => 'Ophævet', 'color' => '#9e9e9e'],
                'PENDING' => ['label' => 'Afventer', 'color' => '#ff9800'],
                'SCHEDULED' => ['label' => 'Planlagt', 'color' => '#ff9800'],
                'FAILED' => ['label' => 'Fejlet', 'color' => '#f44336'],
                'PAST_DUE' => ['label' => 'Forfalden', 'color' => '#f44336'],
            ];

            foreach ($payments as $payment) {
                $paymentData = self::normalizeData($payment);
                $amount = (float)($paymentData['amount'] ?? 0);
                $amountFormatted = number_format($amount, 2, ',', '.') . ' ' . $currency;
                $status = $paymentData['status'] ?? 'PENDING';
                $statusInfo = $statusMap[$status] ?? ['label' => $status, 'color' => '#666'];

                // Format due date
                $dueDate = $paymentData['due_date'] ?? null;
                $dueDateFormatted = $dueDate ? date('d.m.Y', strtotime($dueDate)) : '-';

                // HTML row
                $paymentsHtmlRows .= '<tr>';
                $paymentsHtmlRows .= '<td style="padding: 8px; border-bottom: 1px solid #eee;">Rate ' . $paymentNumber . '</td>';
                $paymentsHtmlRows .= '<td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">' . $amountFormatted . '</td>';
                $paymentsHtmlRows .= '<td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">' . $dueDateFormatted . '</td>';
                $paymentsHtmlRows .= '<td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;"><span style="color: ' . $statusInfo['color'] . '; font-weight: 500;">' . $statusInfo['label'] . '</span></td>';
                $paymentsHtmlRows .= '</tr>';

                // Text row for plain text email
                $paymentsTextRows .= "Rate {$paymentNumber}: {$amountFormatted} - {$dueDateFormatted} - {$statusInfo['label']}\n";

                $paymentNumber++;
            }

            // Build full HTML table
            $context['payments_list_html'] = '<table style="width: 100%; font-size: 14px; border-collapse: collapse;">'
                . '<tr style="background: #f5f5f5;">'
                . '<th style="padding: 10px 8px; text-align: left; font-weight: 600;">Rate</th>'
                . '<th style="padding: 10px 8px; text-align: right; font-weight: 600;">Beløb</th>'
                . '<th style="padding: 10px 8px; text-align: center; font-weight: 600;">Forfald</th>'
                . '<th style="padding: 10px 8px; text-align: right; font-weight: 600;">Status</th>'
                . '</tr>'
                . $paymentsHtmlRows
                . '</table>';

            $context['payments_list_text'] = $paymentsTextRows;
            $context['payments_count'] = count($payments);
        } else {
            $context['payments_list_html'] = '';
            $context['payments_list_text'] = '';
            $context['payments_count'] = 0;
        }

        // Reference for deduplication - IMPORTANT: must include order UID
        $context['reference_id'] = $orderData['uid'] ?? null;
        $context['reference_type'] = 'order_refund';

        debugLog([
            'order_uid' => $orderData['uid'] ?? null,
            'total_refunded' => $totalRefunded,
            'payments_refunded_count' => $paymentsRefundedCount,
            'payments_voided_count' => $paymentsVoidedCount,
            'user' => $context['user']['email'] ?? null,
            'organisation' => $context['organisation']['name'] ?? null,
            'location' => $context['location']['name'] ?? null,
            'reference_id' => $context['reference_id'],
            'reference_type' => $context['reference_type'],
        ], 'NotificationTriggers_orderRefunded');

        return NotificationService::trigger('order.refunded', $context);
    }

    /**
     * Trigger payment due reminder (X days before)
     */
    public static function paymentDueReminder(object|array $payment, ?object $user = null, int $daysUntilDue = 1): bool {
        $context = self::buildPaymentReminderContext($payment, $user, $daysUntilDue, 0);
        $context['email_title'] = 'Betalingspåmindelse';
        return NotificationService::trigger('payment.due_reminder', $context);
    }

    /**
     * Trigger payment overdue reminder
     */
    public static function paymentOverdueReminder(object|array $payment, ?object $user = null, int $daysOverdue = 1): bool {
        $context = self::buildPaymentReminderContext($payment, $user, 0, $daysOverdue);
        $context['email_title'] = 'Forfalden betaling';
        return NotificationService::trigger('payment.overdue_reminder', $context);
    }

    /**
     * Trigger rykker (dunning notice) for overdue payment
     *
     * @param object|array $payment Payment data
     * @param int $rykkerLevel Rykker level (1, 2, or 3)
     * @param float $fee Fee amount for this rykker
     * @param object|null $user User object (resolved from payment if not provided)
     * @return bool Success status
     */
    public static function paymentRykker(object|array $payment, int $rykkerLevel, float $fee = 0, ?object $user = null): bool {
        $paymentData = self::normalizeData($payment);

        debugLog([
            'payment_uid' => $paymentData['uid'] ?? null,
            'rykker_level' => $rykkerLevel,
            'fee' => $fee,
            'user_provided' => !isEmpty($user),
        ], 'NOTIFICATION_RYKKER_START');

        // Build context using existing helper
        $daysOverdue = 0;
        if (!empty($paymentData['due_date'])) {
            $dueTimestamp = is_numeric($paymentData['due_date']) ? $paymentData['due_date'] : strtotime($paymentData['due_date']);
            $daysOverdue = max(0, (int) floor((time() - $dueTimestamp) / 86400));
        }

        $context = self::buildPaymentReminderContext($payment, $user, 0, $daysOverdue);

        // Add rykker-specific context
        $context['rykker'] = [
            'level' => $rykkerLevel,
            'fee' => $fee,
            'formatted_fee' => number_format($fee, 2, ',', '.') . ' ' . ($paymentData['currency'] ?? 'DKK'),
            'total_fees' => (float)($paymentData['rykker_fee'] ?? 0) + $fee,
            'formatted_total_fees' => number_format((float)($paymentData['rykker_fee'] ?? 0) + $fee, 2, ',', '.') . ' ' . ($paymentData['currency'] ?? 'DKK'),
        ];

        // Calculate total due (payment amount + rykker fees)
        $paymentAmount = (float)($paymentData['amount'] ?? 0);
        $totalRykkerFees = (float)($paymentData['rykker_fee'] ?? 0) + $fee;
        $totalDue = $paymentAmount + $totalRykkerFees;
        $context['payment']['total_due'] = $totalDue;
        $context['payment']['formatted_total_due'] = number_format($totalDue, 2, ',', '.') . ' ' . ($paymentData['currency'] ?? 'DKK');

        // Override payment_link to point to consumer payments page
        $context['payment_link'] = __url(Links::$consumer->payments);

        // Set appropriate email title and breakpoint based on level
        $breakpoint = match($rykkerLevel) {
            1 => 'payment.rykker_1',
            2 => 'payment.rykker_2',
            default => 'payment.rykker_final',
        };

        $context['email_title'] = match($rykkerLevel) {
            1 => '1. rykker - Forfalden betaling',
            2 => '2. rykker - Forfalden betaling',
            default => 'Sidste rykker - Inkassovarsel',
        };

        debugLog([
            'payment_uid' => $paymentData['uid'] ?? null,
            'breakpoint' => $breakpoint,
            'email_title' => $context['email_title'],
            'days_overdue' => $daysOverdue,
            'payment_amount' => $paymentAmount,
            'total_rykker_fees' => $totalRykkerFees,
            'total_due' => $totalDue,
            'user_email' => $context['user']['email'] ?? null,
            'user_name' => $context['user']['full_name'] ?? null,
        ], 'NOTIFICATION_RYKKER_CONTEXT');

        $result = NotificationService::trigger($breakpoint, $context);

        debugLog([
            'payment_uid' => $paymentData['uid'] ?? null,
            'breakpoint' => $breakpoint,
            'trigger_result' => $result,
        ], 'NOTIFICATION_RYKKER_RESULT');

        return $result;
    }

    /**
     * Trigger notification when rykker is cancelled/reset by merchant or admin
     */
    public static function paymentRykkerCancelled(object|array $payment, ?object $user = null): bool {
        $paymentData = self::normalizeData($payment);

        // Build context using existing helper
        $context = self::buildPaymentReminderContext($payment, $user, 0, 0);

        $context['email_title'] = 'Rykker annulleret';

        // Override payment_link to point to consumer payments page
        $context['payment_link'] = __url(Links::$consumer->payments);

        return NotificationService::trigger('payment.rykker_cancelled', $context);
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
            'invite_link' => $inviteLink ?? __url(Links::$app->auth->merchantLogin),
            'app' => self::getAppContext(),
            'email_title' => 'Invitation',
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
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'email_title' => 'Velkommen til teamet',
        ]);
    }

    // =====================================================
    // RYKKER / DUNNING EVENTS
    // =====================================================

    /**
     * Trigger first dunning notice (1. rykker)
     */
    public static function rykker1(object|array $payment, ?object $user = null, int $daysOverdue = 7): bool {
        $context = self::buildPaymentReminderContext($payment, $user, 0, $daysOverdue);
        $context['email_title'] = '1. Betalingspåmindelse';
        return NotificationService::trigger('payment.rykker_1', $context);
    }

    /**
     * Trigger second dunning notice (2. rykker)
     */
    public static function rykker2(object|array $payment, ?object $user = null, int $daysOverdue = 14): bool {
        $context = self::buildPaymentReminderContext($payment, $user, 0, $daysOverdue);
        $context['email_title'] = '2. Betalingspåmindelse';
        return NotificationService::trigger('payment.rykker_2', $context);
    }

    /**
     * Trigger final dunning notice / inkasso warning (3. rykker)
     */
    public static function rykkerFinal(object|array $payment, ?object $user = null, int $daysOverdue = 21): bool {
        $context = self::buildPaymentReminderContext($payment, $user, 0, $daysOverdue);
        $context['email_title'] = 'Inkassovarsel';
        return NotificationService::trigger('payment.rykker_final', $context);
    }

    // =====================================================
    // MERCHANT EVENTS
    // =====================================================

    /**
     * Trigger when a merchant receives a new order
     */
    public static function merchantOrderReceived(object|array $order, ?object $organisation = null, ?object $location = null): bool {
        $context = self::buildOrderContext($order, null, $organisation);

        if ($location) {
            $context['location'] = self::normalizeData($location);
        }

        return NotificationService::trigger('merchant.order_received', $context);
    }

    /**
     * Trigger when merchant organisation is ready for use
     */
    public static function merchantOrgReady(object|array $organisation, ?object $owner = null): bool {
        $orgData = self::normalizeData($organisation);
        $context = [
            'organisation' => $orgData,
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'app' => self::getAppContext(),
            'email_title' => 'Din konto er klar',
        ];

        if ($owner) {
            $context['user'] = self::normalizeUserData($owner);
        }

        return NotificationService::trigger('merchant.org_ready', $context);
    }

    /**
     * Trigger when Viva approves merchant business
     */
    public static function merchantVivaApproved(object|array $organisation, ?object $owner = null): bool {
        $orgData = self::normalizeData($organisation);
        $context = [
            'organisation' => $orgData,
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'app' => self::getAppContext(),
            'email_title' => 'Viva godkendelse',
        ];

        if ($owner) {
            $context['user'] = self::normalizeUserData($owner);
        }

        return NotificationService::trigger('merchant.viva_approved', $context);
    }

    // =====================================================
    // SYSTEM EVENTS
    // =====================================================

    /**
     * Trigger when a policy is updated (single user)
     */
    public static function policyUpdated(
        object|array $user,
        string $policyType,
        string $policyName,
        string $updateSummary,
        string $policyLink,
        string $changelogUid,
        string $effectiveDate
    ): bool {
        $userData = self::normalizeUserData($user);

        return NotificationService::trigger('system.policy_updated', [
            'user' => $userData,
            'policy_type' => $policyType,
            'policy_name' => $policyName,
            'update_summary' => $updateSummary,
            'policy_link' => $policyLink,
            'effective_date' => $effectiveDate,
            'app' => self::getAppContext(),
            'email_title' => 'Opdatering af ' . $policyName,
            // Reference for deduplication - uses changelog UID so republishing creates new notification
            'reference_id' => $changelogUid,
            'reference_type' => 'policy_changelog',
        ]);
    }

    /**
     * Send policy update notifications to multiple recipient types
     *
     * @param object|array $policy The policy object
     * @param array $recipientTypes Array of recipient type strings
     * @param string $changelogUid The changelog UID for deduplication
     * @param string $effectiveDate When the policy becomes/became active (for display)
     */
    public static function policyUpdatedBatch(
        object|array $policy,
        array $recipientTypes,
        string $changelogUid,
        string $effectiveDate
    ): void {
        $policyData = self::normalizeData($policy);
        // Get type from policy_type (may be object or string like "pt_consumer_privacy")
        $policyTypeUid = $policyData['policy_type'] ?? $policyData['type'] ?? '';
        $policyType = is_object($policyTypeUid) ? $policyTypeUid->type : str_replace('pt_', '', $policyTypeUid);
        $policyName = Methods::policyTypes()->getDisplayName($policyType) ?? $policyData['title'] ?? 'Politik';
        // Get version number from policy data for direct link to specific version
        $versionNumber = isset($policyData['version']) ? (int) $policyData['version'] : null;
        $policyLink = self::getPolicyLink($policyType, $versionNumber);
        $updateSummary = 'Vores ' . $policyName . ' er blevet opdateret.';

        // Format effective date in Danish
        $formattedDate = self::formatDanishDate($effectiveDate);

        // Build recipient list based on types
        $recipientUids = self::buildPolicyRecipients($recipientTypes);

        debugLog([
            'policy_type' => $policyType,
            'policy_name' => $policyName,
            'recipient_types' => $recipientTypes,
            'recipient_count' => count($recipientUids),
            'changelog_uid' => $changelogUid,
            'effective_date' => $formattedDate,
        ], 'NotificationTriggers_policyUpdatedBatch');

        // Send notification to each recipient
        foreach ($recipientUids as $userUid) {
            $user = Methods::users()->get($userUid);
            if ($user) {
                self::policyUpdated($user, $policyType, $policyName, $updateSummary, $policyLink, $changelogUid, $formattedDate);
            }
        }
    }

    /**
     * Format a datetime string to Danish readable format
     */
    private static function formatDanishDate(string $datetime): string {
        $timestamp = strtotime($datetime);
        $formatted = date('d. F Y', $timestamp);

        // Map English month names to Danish
        $danishMonths = [
            'January' => 'januar', 'February' => 'februar', 'March' => 'marts',
            'April' => 'april', 'May' => 'maj', 'June' => 'juni',
            'July' => 'juli', 'August' => 'august', 'September' => 'september',
            'October' => 'oktober', 'November' => 'november', 'December' => 'december'
        ];

        return str_replace(array_keys($danishMonths), array_values($danishMonths), $formatted);
    }

    /**
     * Build list of recipient user UIDs based on recipient types
     */
    private static function buildPolicyRecipients(array $types): array {
        $userUids = [];

        // Handle 'all' type - sends to all active users
        if (in_array('all', $types)) {
            $users = Methods::users()->getByX(['deactivated' => 0]);
            foreach ($users->list() as $user) {
                $userUids[$user->uid] = true;
            }
            return array_keys($userUids);
        }

        // Consumers - access_level 1
        if (in_array('consumers', $types)) {
            $users = Methods::users()->getByX(['deactivated' => 0, 'access_level' => 1]);
            foreach ($users->list() as $user) {
                $userUids[$user->uid] = true;
            }
        }

        // Merchants - access_level 2
        if (in_array('merchants', $types)) {
            $users = Methods::users()->getByX(['deactivated' => 0, 'access_level' => 2]);
            foreach ($users->list() as $user) {
                $userUids[$user->uid] = true;
            }
        }

        // Organisation owners - members with role 'owner'
        if (in_array('org_owners', $types)) {
            $members = Methods::organisationMembers()->excludeForeignKeys()->getByX(['role' => 'owner', 'status' => 'active']);
            foreach ($members->list() as $member) {
                $userUids[$member->user] = true;
            }
        }

        // Organisation admins - members with role 'admin'
        if (in_array('org_admins', $types)) {
            $members = Methods::organisationMembers()->excludeForeignKeys()->getByX(['role' => 'admin', 'status' => 'active']);
            foreach ($members->list() as $member) {
                $userUids[$member->user] = true;
            }
        }

        // Location managers - members with location assignment
        if (in_array('location_managers', $types)) {
            $membersHandler = Methods::organisationMembers()->excludeForeignKeys();
            $query = $membersHandler->queryBuilder()
                ->where('status', 'active')
                ->whereNotNull('location');
            $members = $membersHandler->queryGetAll($query);
            foreach ($members->list() as $member) {
                $userUids[$member->user] = true;
            }
        }

        // WeePay admins - access_level 8 or 9
        if (in_array('weepay_admins', $types)) {
            $usersHandler = Methods::users();
            $query = $usersHandler->queryBuilder()
                ->where('deactivated', 0)
                ->where('access_level', '>=', 8);
            $users = $usersHandler->queryGetAll($query);
            foreach ($users->list() as $user) {
                $userUids[$user->uid] = true;
            }
        }

        return array_keys($userUids);
    }

    /**
     * Get public policy link based on type
     */
    private static function getPolicyLink(string $policyType, ?int $version = null): string {
        $baseUrl = match ($policyType) {
            'consumer_privacy' => __url(Links::$policies->consumer->privacy),
            'consumer_terms' => __url(Links::$policies->consumer->termsOfUse),
            'merchant_privacy' => __url(Links::$policies->merchant->privacy),
            'merchant_terms' => __url(Links::$policies->merchant->termsOfUse),
            'cookies' => __url(Links::$policies->cookies),
            default => __url('/'),
        };

        // Append version number if provided
        if ($version !== null) {
            return $baseUrl . '/' . $version;
        }

        return $baseUrl;
    }

    // =====================================================
    // REPORT EVENTS
    // =====================================================

    /**
     * Trigger weekly organisation report
     */
    public static function weeklyReportOrganisation(
        object|array $organisation,
        object|array $recipient,
        array $reportData
    ): bool {
        $orgData = self::normalizeData($organisation);
        $userData = self::normalizeData($recipient);

        return NotificationService::trigger('report.weekly_organisation', [
            'organisation' => $orgData,
            'user' => $userData,
            'report_period_start' => $reportData['period_start'] ?? '',
            'report_period_end' => $reportData['period_end'] ?? '',
            'total_orders' => $reportData['total_orders'] ?? 0,
            'total_revenue' => $reportData['total_revenue'] ?? 0,
            'total_revenue_formatted' => $reportData['total_revenue_formatted'] ?? '0,00 DKK',
            'pending_payments' => $reportData['pending_payments'] ?? 0,
            'completed_payments' => $reportData['completed_payments'] ?? 0,
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'app' => self::getAppContext(),
            'email_title' => 'Ugentlig rapport',
        ]);
    }

    /**
     * Trigger weekly location report
     */
    public static function weeklyReportLocation(
        object|array $location,
        object|array $organisation,
        object|array $recipient,
        array $reportData
    ): bool {
        $locationData = self::normalizeData($location);
        $orgData = self::normalizeData($organisation);
        $userData = self::normalizeData($recipient);

        return NotificationService::trigger('report.weekly_location', [
            'location' => $locationData,
            'organisation' => $orgData,
            'user' => $userData,
            'report_period_start' => $reportData['period_start'] ?? '',
            'report_period_end' => $reportData['period_end'] ?? '',
            'total_orders' => $reportData['total_orders'] ?? 0,
            'total_revenue' => $reportData['total_revenue'] ?? 0,
            'total_revenue_formatted' => $reportData['total_revenue_formatted'] ?? '0,00 DKK',
            'pending_payments' => $reportData['pending_payments'] ?? 0,
            'completed_payments' => $reportData['completed_payments'] ?? 0,
            'dashboard_link' => __url(Links::$consumer->dashboard),
            'app' => self::getAppContext(),
            'email_title' => 'Ugentlig rapport',
        ]);
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    /**
     * Build context for payment events (successful, failed, refunded)
     */
    private static function buildPaymentContext(
        object|array $payment,
        ?object $user = null,
        ?object $order = null
    ): array {
        $paymentData = self::normalizeData($payment);
        $currency = $paymentData['currency'] ?? 'DKK';

        // Format amount
        if (isset($paymentData['amount']) && !isset($paymentData['formatted_amount'])) {
            $amount = is_numeric($paymentData['amount']) ? (float)$paymentData['amount'] : 0;
            $paymentData['formatted_amount'] = number_format($amount, 2, ',', '.') . ' ' . $currency;
        }

        // Format due date
        if (isset($paymentData['due_date']) && !isset($paymentData['due_date_formatted'])) {
            $dueDate = $paymentData['due_date'];
            $paymentData['due_date_formatted'] = is_string($dueDate) ? date('d.m.Y', strtotime($dueDate)) : '-';
        }

        // Format paid date/time
        $paidAt = $paymentData['paid_at'] ?? $paymentData['completed_at'] ?? null;
        if ($paidAt) {
            $timestamp = is_numeric($paidAt) ? (int)$paidAt : strtotime($paidAt);
            $paymentData['paid_date'] = date('d.m.Y', $timestamp);
            $paymentData['paid_time'] = date('H:i', $timestamp);
        } else {
            $paymentData['paid_date'] = date('d.m.Y');
            $paymentData['paid_time'] = date('H:i');
        }

        $context = [
            'payment' => $paymentData,
            'app' => self::getAppContext(),
            'viva_note' => 'Opkrævning og afvikling af betalinger håndteres af VIVA på vegne af forretningen.',
        ];

        // Resolve user
        if (!$user && !empty($paymentData['user'])) {
            $userValue = $paymentData['user'];
            $user = is_object($userValue) ? $userValue : Methods::users()->get($userValue);
        }
        if ($user) {
            $context['user'] = self::normalizeUserData($user);
        }

        // Resolve order
        if (!$order && !empty($paymentData['order'])) {
            $orderValue = $paymentData['order'];
            $order = is_object($orderValue) ? $orderValue : Methods::orders()->get($orderValue);
        }
        if ($order) {
            $orderData = self::normalizeData($order);
            $context['order'] = $orderData;

            // Resolve organisation from order
            if (!empty($orderData['organisation'])) {
                $orgValue = $orderData['organisation'];
                if (is_object($orgValue)) {
                    $context['organisation'] = self::normalizeData($orgValue);
                } else {
                    $org = Methods::organisations()->get($orgValue);
                    if ($org) $context['organisation'] = self::normalizeData($org);
                }
            }

            // Resolve location from order
            if (!empty($orderData['location'])) {
                $locValue = $orderData['location'];
                $locationData = null;
                if (is_object($locValue)) {
                    $locationData = self::normalizeData($locValue);
                } else {
                    $loc = Methods::locations()->get($locValue);
                    if ($loc) $locationData = self::normalizeData($loc);
                }
                if ($locationData) {
                    $context['location'] = $locationData;
                    // Add hero HTML for location-branded emails
                    $context['location_hero_html'] = self::buildLocationHeroHtml($locationData);
                }
            }

            // Build payment plan context
            $context['payment_plan'] = self::buildPaymentPlanContext($order, $currency);

            // Add links
            $orderUid = $orderData['uid'] ?? null;
            if ($orderUid) {
                $context['order_link'] = __url(Links::$consumer->orderDetail($orderUid));
                $context['payment_link'] = __url(Links::$consumer->payments);
                $context['retry_link'] = __url(Links::$consumer->payments);
            }
        }

        // Payment detail link
        $paymentUid = $paymentData['uid'] ?? null;
        if ($paymentUid) {
            $context['receipt_link'] = __url(Links::$consumer->paymentDetail($paymentUid));
        }

        $context['dashboard_link'] = __url(Links::$consumer->dashboard);

        // Reference for deduplication
        $context['reference_id'] = $paymentData['uid'] ?? null;
        $context['reference_type'] = 'payment';

        return $context;
    }

    /**
     * Build context for payment reminder events (due reminders, overdue, rykker)
     */
    private static function buildPaymentReminderContext(
        object|array $payment,
        ?object $user = null,
        int $daysUntilDue = 0,
        int $daysOverdue = 0
    ): array {
        $paymentData = self::normalizeData($payment);
        $currency = $paymentData['currency'] ?? 'DKK';

        // Format amount if not already formatted
        if (isset($paymentData['amount']) && !isset($paymentData['formatted_amount'])) {
            $amount = is_numeric($paymentData['amount']) ? (float)$paymentData['amount'] : 0;
            $paymentData['formatted_amount'] = number_format($amount, 2, ',', '.') . ' ' . $currency;
        }

        // Format due date if available
        if (isset($paymentData['due_date']) && !isset($paymentData['due_date_formatted'])) {
            $dueDate = $paymentData['due_date'];
            if ($dueDate instanceof \DateTime) {
                $paymentData['due_date_formatted'] = $dueDate->format('d.m.Y');
            } elseif (is_string($dueDate)) {
                $paymentData['due_date_formatted'] = date('d.m.Y', strtotime($dueDate));
            }
        }

        $context = [
            'payment' => $paymentData,
            'days_until_due' => $daysUntilDue,
            'days_overdue' => $daysOverdue,
            'app' => self::getAppContext(),
            'viva_note' => 'Opkrævning og afvikling af betalinger håndteres af VIVA på vegne af forretningen.',
        ];

        // Resolve user if not provided
        // Payment model uses 'uuid' for user field, but other sources may use 'user'
        $userField = !empty($paymentData['uuid']) ? $paymentData['uuid'] : ($paymentData['user'] ?? null);
        if (!$user && !empty($userField)) {
            if (is_object($userField)) {
                $user = $userField;
            } else {
                $user = Methods::users()->get($userField);
            }
        }

        if ($user) {
            $context['user'] = self::normalizeUserData($user);
        }

        // Resolve order if available
        $order = null;
        if (!empty($paymentData['order'])) {
            $orderValue = $paymentData['order'];
            if (is_object($orderValue)) {
                $order = $orderValue;
            } else {
                $order = Methods::orders()->get($orderValue);
            }
        }

        if ($order) {
            $orderData = self::normalizeData($order);
            $context['order'] = $orderData;

            // Resolve organisation from order if not in payment
            if (!empty($orderData['organisation'])) {
                $orgValue = $orderData['organisation'];
                if (is_object($orgValue)) {
                    $context['organisation'] = self::normalizeData($orgValue);
                } else {
                    $org = Methods::organisations()->get($orgValue);
                    if ($org) {
                        $context['organisation'] = self::normalizeData($org);
                    }
                }
            }

            // Resolve location from order
            if (!empty($orderData['location'])) {
                $locValue = $orderData['location'];
                $locationData = null;
                if (is_object($locValue)) {
                    $locationData = self::normalizeData($locValue);
                } else {
                    $loc = Methods::locations()->get($locValue);
                    if ($loc) {
                        $locationData = self::normalizeData($loc);
                    }
                }
                if ($locationData) {
                    $context['location'] = $locationData;
                    // Add hero HTML for location-branded emails
                    $context['location_hero_html'] = self::buildLocationHeroHtml($locationData);
                }
            }

            // Build payment plan context
            $context['payment_plan'] = self::buildPaymentPlanContext($order, $currency);
        }

        // Add links
        if (!empty($paymentData['order'])) {
            $orderUid = is_object($paymentData['order']) ? $paymentData['order']->uid : $paymentData['order'];
            $context['payment_link'] = __url(Links::$consumer->payments);
            $context['retry_link'] = __url(Links::$consumer->payments);
            $context['order_link'] = __url(Links::$consumer->orderDetail($orderUid));
        }
        // Payment detail link
        if (!empty($paymentData['uid'])) {
            $context['receipt_link'] = __url(Links::$consumer->paymentDetail($paymentData['uid']));
        }
        $context['dashboard_link'] = __url(Links::$consumer->dashboard);

        // Reference for deduplication
        $context['reference_id'] = $paymentData['uid'] ?? null;
        $context['reference_type'] = 'payment';

        return $context;
    }

    /**
     * Build context for order-related events
     */
    private static function buildOrderContext(object|array $order, ?object $user = null, ?object $organisation = null): array {
        $orderData = self::normalizeData($order);
        $currency = $orderData['currency'] ?? 'DKK';

        // Format amount if present
        if (isset($orderData['amount'])) {
            $orderData['formatted_amount'] = number_format($orderData['amount'] / 100, 2, ',', '.') . ' ' . $currency;
        }

        // Add created_datetime formatting
        $createdAt = $orderData['created_at'] ?? null;
        if ($createdAt) {
            $timestamp = is_numeric($createdAt) ? (int)$createdAt : strtotime($createdAt);
            $orderData['created_date'] = date('d.m.Y', $timestamp);
            $orderData['created_time'] = date('H:i', $timestamp);
            $orderData['created_datetime'] = date('d.m.Y H:i', $timestamp);
        }

        $context = [
            'order' => $orderData,
            'app' => self::getAppContext(),
            'viva_note' => 'Opkrævning og afvikling af betalinger håndteres af VIVA på vegne af forretningen.',
            'email_title' => 'Ordrebekræftelse',
        ];

        // Resolve user if not provided
        if (!$user && !empty($orderData['uuid'])) {
            $userValue = $orderData['uuid'];
            // Handle foreign key objects
            if (is_object($userValue)) {
                $user = $userValue;
            } else {
                $user = Methods::users()->get($userValue);
            }
        }

        if ($user) {
            $context['user'] = self::normalizeUserData($user);
        }

        // Resolve organisation if not provided
        if (!$organisation && !empty($orderData['organisation'])) {
            $orgValue = $orderData['organisation'];
            // Handle foreign key objects
            if (is_object($orgValue)) {
                $organisation = $orgValue;
            } else {
                $organisation = Methods::organisations()->get($orgValue);
            }
        }

        if ($organisation) {
            $context['organisation'] = self::normalizeData($organisation);
        }

        // Resolve location if available
        $location = null;
        if (!empty($orderData['location'])) {
            $locValue = $orderData['location'];
            if (is_object($locValue)) {
                $location = $locValue;
            } else {
                $location = Methods::locations()->get($locValue);
            }
        }

        if ($location) {
            $locationData = self::normalizeData($location);
            $context['location'] = $locationData;
            // Add hero HTML for location-branded emails
            $context['location_hero_html'] = self::buildLocationHeroHtml($locationData);
        }

        // Build payment_plan context if order has payment plan data
        $context['payment_plan'] = self::buildPaymentPlanContext($order, $currency);

        // Add reference data for deduplication
        $context['reference_id'] = $orderData['uid'] ?? null;
        $context['reference_type'] = 'order';

        // Add links
        $orderUid = $orderData['uid'] ?? null;
        if ($orderUid) {
            $context['order_link'] = __url(Links::$consumer->orderDetail($orderUid));
            $context['payment_link'] = __url(Links::$consumer->payments);
            $context['receipt_link'] = __url(Links::$consumer->orderDetail($orderUid));
            $context['agreement_link'] = __url(Links::$consumer->orderDetail($orderUid));
            $context['retry_link'] = __url(Links::$consumer->payments);
        }
        $context['dashboard_link'] = __url(Links::$consumer->dashboard);

        return $context;
    }

    /**
     * Build payment plan context from order
     */
    private static function buildPaymentPlanContext(object|array $order, string $currency = 'DKK'): array {
        $orderData = is_array($order) ? $order : (array) $order;
        $orderUid = $orderData['uid'] ?? null;

        $paymentPlan = $orderData['payment_plan'] ?? null;
        $totalAmount = $orderData['amount'] ?? 0;

        // Get payment plan settings from AppMeta
        $planSettings = null;
        if ($paymentPlan && isset(\features\Settings::$app->paymentPlans->$paymentPlan)) {
            $planSettings = \features\Settings::$app->paymentPlans->$paymentPlan;
        }

        $planTitle = $planSettings->title ?? match($paymentPlan) {
            'direct' => 'Direkte betaling',
            'pushed' => 'Udskudt betaling',
            'installments' => 'Ratebetaling',
            default => $paymentPlan ?? 'Ukendt'
        };

        // Get actual payments from the order
        $firstAmount = 0;
        $installmentAmount = 0;
        $installmentCount = 0;
        $remainingAmount = 0;
        $scheduleLines = [];
        $firstDueDate = null;
        $nextDueDate = null;
        $lastDueDate = null;

        if ($orderUid) {
            $payments = Methods::payments()->getByXOrderBy('installment_number', 'ASC', ['order' => $orderUid]);
            $installmentCount = $payments ? $payments->count() : 0;

            $i = 0;
            foreach ($payments ? $payments->list() : [] as $payment) {
                $paymentAmount = (float) $payment->amount;
                $status = $payment->status ?? '';
                $isPaid = in_array($status, ['COMPLETED', 'PAID']);
                $isRefundedOrCancelled = in_array($status, ['REFUNDED', 'VOIDED', 'CANCELLED']);

                $dueDate = $payment->due_date ?? null;

                if ($payment->installment_number === 1) {
                    $firstAmount = $paymentAmount;
                    $firstDueDate = $dueDate;
                } else {
                    // Use amount from subsequent payments as installment amount
                    if ($installmentAmount === 0) {
                        $installmentAmount = $paymentAmount;
                    }
                }

                // Track next due date (first unpaid payment)
                if (!$isPaid && !$isRefundedOrCancelled && $nextDueDate === null && $dueDate) {
                    $nextDueDate = $dueDate;
                }

                // Track last due date
                if ($dueDate) {
                    $lastDueDate = $dueDate;
                }

                // Only add to remaining if not paid and not refunded/cancelled
                if (!$isPaid && !$isRefundedOrCancelled) {
                    $remainingAmount += $paymentAmount;
                }

                // Build schedule summary line
                $statusText = $isPaid ? '✓' : ($isRefundedOrCancelled ? '✗' : '');
                $dueDateFormatted = $dueDate ? date('d.m.Y', strtotime($dueDate)) : '-';
                $amountFormatted = number_format($paymentAmount, 2, ',', '.') . ' ' . $currency;
                $scheduleLines[] = "Rate " . ($i + 1) . ": " . $amountFormatted . " - " . $dueDateFormatted . " " . $statusText;
                $i++;
            }
        }

        // Convert to cents for formatting (amounts stored as decimal)
        $totalAmountCents = (int) ($totalAmount * 100);
        $firstAmountCents = (int) ($firstAmount * 100);
        $installmentAmountCents = (int) ($installmentAmount * 100);
        $remainingAmountCents = (int) ($remainingAmount * 100);

        return [
            'type' => $paymentPlan,
            'title' => $planTitle,
            'total_amount' => $totalAmountCents,
            'total_amount_formatted' => number_format($totalAmount, 2, ',', '.') . ' ' . $currency,
            'first_amount' => $firstAmountCents,
            'first_amount_formatted' => number_format($firstAmount, 2, ',', '.') . ' ' . $currency,
            'installment_amount' => $installmentAmountCents,
            'installment_amount_formatted' => number_format($installmentAmount, 2, ',', '.') . ' ' . $currency,
            'installment_count' => $installmentCount,
            'total_installments' => $installmentCount,
            'remaining_amount' => $remainingAmountCents,
            'remaining_amount_formatted' => number_format($remainingAmount, 2, ',', '.') . ' ' . $currency,
            'first_due_date' => $firstDueDate ? date('d.m.Y', strtotime($firstDueDate)) : null,
            'next_due_date' => $nextDueDate ? date('d.m.Y', strtotime($nextDueDate)) : null,
            'last_due_date' => $lastDueDate ? date('d.m.Y', strtotime($lastDueDate)) : null,
            'schedule_summary' => implode("\n", $scheduleLines),
        ];
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
     * Normalize user data with first_name derived from full_name
     */
    private static function normalizeUserData(object|array $user): array {
        $data = self::normalizeData($user);
        // Add first_name derived from full_name for SMS templates
        if (!empty($data['full_name']) && empty($data['first_name'])) {
            $nameParts = explode(' ', trim($data['full_name']));
            $data['first_name'] = $nameParts[0] ?? '';
        }
        return $data;
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

    /**
     * Build location hero HTML for email header
     * Returns hero image HTML if available, empty string otherwise (text-only fallback)
     */
    private static function buildLocationHeroHtml(?array $locationData): string {
        if (empty($locationData)) return '';

        $locationUid = $locationData['uid'] ?? null;
        if (!$locationUid) return '';

        // Get published page for hero image
        $publishedPage = Methods::locationPages()->getPublished($locationUid);
        $heroImage = $publishedPage->hero_image ?? null;

        // Skip if no hero image or if it's the default
        if ($heroImage && !str_contains($heroImage, 'default') && !str_contains($heroImage, 'merchant-beauty')) {
            $heroUrl = HOST . ltrim($heroImage, '/');
            return '<img src="' . htmlspecialchars($heroUrl) . '" alt="' . htmlspecialchars($locationData['name'] ?? '') . '" style="width: 100%; height: 150px; object-fit: cover;">';
        }

        return ''; // No hero = text only
    }

    // =====================================================
    // SUPPORT TICKET EVENTS
    // =====================================================

    /**
     * Trigger when a support ticket is created
     * Notifies admin via bell notification
     */
    public static function supportTicketCreated(object|array $ticket, object|array $user): bool {
        $ticketData = self::normalizeData($ticket);
        $userData = self::normalizeUserData($user);

        // Determine support link based on ticket type
        $supportLink = $ticketData['type'] === 'merchant'
            ? __url(Links::$merchant->support)
            : __url(Links::$consumer->support);

        return NotificationService::trigger('support.ticket_created', [
            'ticket' => $ticketData,
            'user' => $userData,
            'support_link' => $supportLink,
            'app' => self::getAppContext(),
            // Use ticket UID for deduplication - allows one notification per ticket
            'reference_id' => $ticketData['uid'] ?? null,
            'reference_type' => 'support_ticket',
        ]);
    }

    /**
     * Trigger when admin replies to a support ticket
     * Notifies user via email and bell notification
     */
    public static function supportTicketReplied(object|array $ticket, object|array $user, object|array $reply): bool {
        $ticketData = self::normalizeData($ticket);
        $userData = self::normalizeUserData($user);
        $replyData = self::normalizeData($reply);

        // Determine support link based on ticket type
        $supportLink = $ticketData['type'] === 'merchant'
            ? __url(Links::$merchant->support)
            : __url(Links::$consumer->support);

        debugLog([
            'trigger' => 'supportTicketReplied',
            'ticket_uid' => $ticketData['uid'] ?? null,
            'user_uid' => $userData['uid'] ?? null,
            'reply_uid' => $replyData['uid'] ?? null,
        ], 'notification-trigger');

        return NotificationService::trigger('support.ticket_replied', [
            'ticket' => $ticketData,
            'user' => $userData,
            'reply' => $replyData,
            'support_link' => $supportLink,
            'app' => self::getAppContext(),
            'email_title' => 'Svar på din henvendelse',
            // Use reply UID for deduplication - allows one notification per reply
            'reference_id' => $replyData['uid'] ?? null,
            'reference_type' => 'support_reply',
        ]);
    }
}
