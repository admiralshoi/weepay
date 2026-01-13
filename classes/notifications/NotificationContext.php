<?php

namespace classes\notifications;

use classes\Methods;

/**
 * NotificationContext - Helper for building rich notification context
 *
 * Use this class to easily build context arrays for NotificationService::trigger()
 *
 * Example usage:
 * ```php
 * $context = NotificationContext::forOrder($order)
 *     ->withUser($user)
 *     ->withPaymentPlan()
 *     ->withLinks()
 *     ->build();
 *
 * NotificationService::trigger('order.completed', $context);
 * ```
 */
class NotificationContext {

    private array $context = [];
    private ?object $order = null;

    /**
     * Create context for an order
     */
    public static function forOrder(object $order): self {
        $instance = new self();
        $instance->order = $order;

        $currency = $order->currency ?? 'DKK';

        $instance->context['order'] = [
            'uid' => $order->uid,
            'amount' => $order->amount,
            'formatted_amount' => self::formatAmount($order->amount, $currency),
            'currency' => $currency,
            'caption' => $order->caption ?? null,
            'status' => $order->status,
            'completed_at' => isset($order->completed_at) ? date('d/m/Y H:i', $order->completed_at) : null,
        ];

        return $instance;
    }

    /**
     * Create context for a payment
     */
    public static function forPayment(object $payment): self {
        $instance = new self();
        $currency = 'DKK';

        $instance->context['payment'] = [
            'uid' => $payment->uid,
            'amount' => $payment->amount,
            'formatted_amount' => self::formatAmount($payment->amount, $currency),
            'currency' => $currency,
            'due_date' => $payment->due_date,
            'due_date_formatted' => $payment->due_date ? date('d/m/Y', strtotime($payment->due_date)) : null,
            'installment_number' => $payment->installment_number ?? null,
            'status' => $payment->status,
            'paid_at' => isset($payment->paid_at) ? date('d/m/Y H:i', $payment->paid_at) : null,
        ];

        // If payment has order reference, load it
        if (!empty($payment->order)) {
            $orderUid = is_object($payment->order) ? $payment->order->uid : $payment->order;
            $order = Methods::orders()->get($orderUid);
            if ($order) {
                $instance->order = $order;
                $instance->context['order'] = [
                    'uid' => $order->uid,
                    'amount' => $order->amount,
                    'formatted_amount' => self::formatAmount($order->amount, $order->currency ?? 'DKK'),
                    'currency' => $order->currency ?? 'DKK',
                    'caption' => $order->caption ?? null,
                    'status' => $order->status,
                ];
            }
        }

        return $instance;
    }

    /**
     * Create empty context for custom use
     */
    public static function create(): self {
        return new self();
    }

    /**
     * Add user to context
     */
    public function withUser($user): self {
        if (is_string($user)) {
            $user = Methods::users()->get($user);
        }

        if ($user) {
            $this->context['user'] = [
                'uid' => $user->uid,
                'full_name' => $user->full_name ?? null,
                'email' => $user->email ?? null,
                'phone' => $user->phone ?? null,
            ];
        }

        return $this;
    }

    /**
     * Add organisation to context
     */
    public function withOrganisation($organisation): self {
        if (is_string($organisation)) {
            $organisation = Methods::organisations()->get($organisation);
        }

        if ($organisation) {
            $this->context['organisation'] = [
                'uid' => $organisation->uid,
                'name' => $organisation->name ?? null,
                'email' => $organisation->email ?? null,
                'phone' => $organisation->phone ?? null,
                'cvr' => $organisation->cvr ?? null,
            ];
        }

        return $this;
    }

    /**
     * Add location to context
     */
    public function withLocation($location): self {
        if (is_string($location)) {
            $location = Methods::locations()->get($location);
        }

        if ($location) {
            $this->context['location'] = [
                'uid' => $location->uid,
                'name' => $location->name ?? null,
                'email' => $location->email ?? null,
                'address' => $location->address ?? null,
                'city' => $location->city ?? null,
                'postal_code' => $location->postal_code ?? null,
            ];
        }

        return $this;
    }

    /**
     * Add payment plan details (requires order to be set)
     */
    public function withPaymentPlan(): self {
        if ($this->order) {
            $this->context['payment_plan'] = NotificationService::buildPaymentPlanContext($this->order);
        }

        return $this;
    }

    /**
     * Add standard links (requires order to be set)
     */
    public function withLinks(): self {
        if ($this->order) {
            $links = NotificationService::buildLinksContext($this->order);
            foreach ($links as $key => $value) {
                $this->context[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Add custom data to context
     */
    public function with(string $key, $value): self {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Add multiple custom values
     */
    public function withMany(array $data): self {
        foreach ($data as $key => $value) {
            $this->context[$key] = $value;
        }
        return $this;
    }

    /**
     * Add failure reason (for failed payment notifications)
     */
    public function withFailureReason(string $reason): self {
        $this->context['failure_reason'] = $reason;
        return $this;
    }

    /**
     * Add refund data
     */
    public function withRefund(int $amount, ?string $reason = null, string $currency = 'DKK'): self {
        $this->context['refund_amount'] = $amount;
        $this->context['refund_formatted_amount'] = self::formatAmount($amount, $currency);
        if ($reason) {
            $this->context['refund_reason'] = $reason;
        }
        return $this;
    }

    /**
     * Add days until due / days overdue
     */
    public function withDueDays(string $dueDate): self {
        $dueTimestamp = strtotime($dueDate);
        $today = strtotime('today');
        $daysDiff = floor(($dueTimestamp - $today) / 86400);

        $this->context['days_until_due'] = max(0, $daysDiff);
        $this->context['days_overdue'] = max(0, -$daysDiff);

        return $this;
    }

    /**
     * Add reference data for deduplication
     */
    public function withReference(string $referenceId, string $referenceType): self {
        $this->context['reference_id'] = $referenceId;
        $this->context['reference_type'] = $referenceType;
        return $this;
    }

    /**
     * Add inviter for invitation notifications
     */
    public function withInviter($user): self {
        if (is_string($user)) {
            $user = Methods::users()->get($user);
        }

        if ($user) {
            $this->context['inviter'] = [
                'uid' => $user->uid,
                'full_name' => $user->full_name ?? null,
                'email' => $user->email ?? null,
            ];
        }

        return $this;
    }

    /**
     * Add invitee for invitation notifications
     */
    public function withInvitee(string $email, ?string $name = null): self {
        $this->context['invitee'] = [
            'email' => $email,
            'name' => $name,
        ];
        return $this;
    }

    /**
     * Add reset link for password reset notifications
     */
    public function withResetLink(string $link): self {
        $this->context['reset_link'] = $link;
        return $this;
    }

    /**
     * Add invite link for invitation notifications
     */
    public function withInviteLink(string $link): self {
        $this->context['invite_link'] = $link;
        return $this;
    }

    /**
     * Build the final context array
     */
    public function build(): array {
        return $this->context;
    }

    /**
     * Format amount from øre to readable string
     */
    private static function formatAmount(int $amountInOre, string $currency = 'DKK'): string {
        return number_format($amountInOre / 100, 2, ',', '.') . ' ' . $currency;
    }
}
