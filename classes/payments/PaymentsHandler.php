<?php

namespace classes\payments;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\Organisations;
use Database\model\Payments;
use features\Settings;

class PaymentsHandler extends Crud {

    function __construct() {
        parent::__construct(Payments::newStatic(), "payments");
    }


    public function addTransactionId(string $id, string|int $trxId): bool {
        return $this->update(['prid' => $trxId], ['uid' => $id]);
    }
    public function setCompleted(string $id): bool {
        return $this->update(['status' => 'COMPLETED'], ['uid' => $id]);
    }
    public function setPastDue(string $id): bool {
        return $this->update(['status' => 'PAST_DUE'], ['uid' => $id]);
    }
    public function setPending(string $id): bool {
        return $this->update(['status' => 'PENDING'], ['uid' => $id]);
    }
    public function setCancelled(string $id): bool {
        return $this->update(['status' => 'CANCELLED'], ['uid' => $id]);
    }
    public function setRefunded(string $id): bool {
        return $this->update(['status' => 'REFUNDED'], ['uid' => $id]);
    }
    public function setFailed(string $id): bool {
        return $this->update(['status' => 'FAILED'], ['uid' => $id]);
    }
    public function setScheduled(string $id): bool {
        return $this->update(['status' => 'SCHEDULED'], ['uid' => $id]);
    }

    /**
     * Create payment installments for an order using plan structure
     *
     * @param string $orderId Order UID
     * @param string $customerId Customer UID
     * @param string $organisation Organisation UID
     * @param string $location Location UID
     * @param string $provider Provider UID
     * @param string $currency Currency code
     * @param object|null $plan Plan object with payments array (from CheckoutBasketHandler)
     * @param float $resellerFeePercent Reseller fee percentage to calculate ISV amounts
     * @param string $paymentPlan Payment plan type (installments, pushed, etc)
     * @param bool $isTest Whether this is a test transaction
     * @return bool Success status
     */
    public function createInstallments(
        string $orderId,
        string $customerId,
        string $organisation,
        string $location,
        string $provider,
        string $currency,
        ?object $plan,
        float $resellerFeePercent,
        string $paymentPlan,
        bool $isTest = false
    ): bool {

        if (isEmpty($plan) || isEmpty($plan->payments)) {
            return false;
        }

        $payments = toArray($plan->payments);

        foreach ($payments as $paymentInfo) {
            $paymentInfo = toObject($paymentInfo);
            $customerAmount = $paymentInfo->price;
            $isvAmount = round($customerAmount * $resellerFeePercent / 100, 2);

            $dueDate = date('Y-m-d H:i:s', $paymentInfo->timestamp);
            $isFirstPayment = $paymentInfo->installment === 1;
            $status = ($isFirstPayment && $paymentPlan === 'installments') ? 'PENDING' : 'SCHEDULED';

            $params = [
                'order' => $orderId,
                'uuid' => $customerId,
                'organisation' => $organisation,
                'location' => $location,
                'provider' => $provider,
                'amount' => $customerAmount,
                'isv_amount' => $isvAmount,
                'currency' => $currency,
                'installment_number' => $paymentInfo->installment,
                'due_date' => $dueDate,
                'status' => $status,
                'test' => (int)$isTest,
            ];

            if (!$this->create($params)) {
                return false;
            }
        }

        return true;
    }


    /**
     * Mark a payment as completed
     *
     * @param string $paymentId Payment UID
     * @param string|null $prid Provider reference ID
     * @return bool Success status
     */
    public function markAsCompleted(string $paymentId, ?string $prid = null): bool {
        $updateData = [
            'status' => 'COMPLETED',
            'paid_at' => date('Y-m-d H:i:s'),
        ];

        if (!isEmpty($prid)) {
            $updateData['prid'] = $prid;
        }

        return $this->update($updateData, ['uid' => $paymentId]);
    }


    /**
     * Mark a payment as failed
     *
     * @param string $paymentId Payment UID
     * @param string|null $failureReason Reason for failure
     * @return bool Success status
     */
    public function markAsFailed(string $paymentId, ?string $failureReason = null): bool {
        return $this->update([
            'status' => 'FAILED',
            'failure_reason' => $failureReason,
        ], ['uid' => $paymentId]);
    }


    /**
     * Get all payments for an order
     *
     * @param string $orderId Order UID
     * @param array $fields Fields to retrieve
     * @return Collection
     */
    public function getByOrder(string $orderId, array $fields = []): Collection {
        return $this->getByXOrderBy('installment_number', 'ASC', ['order' => $orderId], $fields);
    }


    /**
     * Get all payments for a customer
     *
     * @param string $customerId Customer UID
     * @param array $status Filter by status
     * @param array $fields Fields to retrieve
     * @return Collection
     */
    public function getByCustomer(string $customerId, array $status = [], array $fields = []): Collection {
        $where = ['uuid' => $customerId];
        if (!empty($status)) {
            $where['status'] = $status;
        }
        return $this->getByXOrderBy('due_date', 'ASC', $where, $fields);
    }


    /**
     * Get due payments for an organisation
     *
     * @param string $organisationId Organisation UID
     * @param string|null $beforeDate Get payments due before this date
     * @param array $fields Fields to retrieve
     * @return Collection
     */
    public function getDuePayments(string $organisationId, ?string $beforeDate = null, array $fields = []): Collection {
        $where = [
            'organisation' => $organisationId,
            'status' => ['PENDING', 'SCHEDULED'],
        ];

        if (!isEmpty($beforeDate)) {
            $where['due_date <='] = $beforeDate;
        }

        return $this->getByXOrderBy('due_date', 'ASC', $where, $fields);
    }


    /**
     * Get the next pending payment for an order
     *
     * @param string $orderId Order UID
     * @return object|null
     */
    public function getNextPendingPayment(string $orderId): ?object {
        return $this->getFirst([
            'order' => $orderId,
            'status' => ['PENDING', 'SCHEDULED']
        ], [], [
            'order_by' => 'installment_number',
            'order_direction' => 'ASC'
        ]);
    }


    /**
     * Get payment statistics for a customer with an organisation
     *
     * @param string $customerId Customer UID
     * @param string $organisationId Organisation UID
     * @return object Statistics object
     */
    public function getCustomerStats(string $customerId, string $organisationId): object {
        $payments = $this->getByX([
            'uuid' => $customerId,
            'organisation' => $organisationId,
        ]);

        $totalPaid = 0;
        $totalPending = 0;
        $completedCount = 0;
        $pendingCount = 0;

        foreach ($payments->list() as $payment) {
            if ($payment->status === 'COMPLETED') {
                $totalPaid += $payment->amount;
                $completedCount++;
            } elseif (in_array($payment->status, ['PENDING', 'SCHEDULED'])) {
                $totalPending += $payment->amount;
                $pendingCount++;
            }
        }

        return (object)[
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
            'completed_count' => $completedCount,
            'pending_count' => $pendingCount,
            'total_payments' => $payments->count(),
        ];
    }


    /**
     * Calculate outstanding BNPL amount for a customer
     * Only counts payments from COMPLETED orders that are PENDING or SCHEDULED
     *
     * @param string $customerId Customer UID
     * @return float Outstanding amount across all completed orders
     */
    public function getOutstandingBnplAmount(string $customerId): float {
        // Get all payments for this customer from COMPLETED orders
        $orderHandler = Methods::orders();
        $completedOrders = $orderHandler->getByX([
            'uuid' => $customerId,
            'status' => 'COMPLETED'
        ], ['uid']);

        if($completedOrders->count() === 0) {
            return 0;
        }

        $orderIds = array_column($completedOrders->toArray(), 'uid');

        $outstanding = 0;
        foreach($orderIds as $orderId) {
            $outstanding += $this->getByX([
                'order' => $orderId,
                'status' => ['PENDING', 'SCHEDULED']
            ], ['amount'])
                ->reduce(function ($amount, $order) { return $amount + (float)$order['amount']; }, 0);
        }

        return $outstanding;
    }


    /**
     * Calculate available BNPL limit for a customer
     *
     * @param ?string $customerId Customer UID
     * @return array ['outstanding' => float, 'available' => float, 'max_amount' => float, 'is_org_specific' => bool]
     */
    public function getBnplLimit(?string $customerId, ?string $organisationId = null): array {
        $platformMaxAmount = Settings::$app->platform_max_bnpl_amount;
        $maxAmount = $platformMaxAmount;
        $isOrgSpecific = false;

        // If organisation is specified, check for org-specific max
        if(!empty($organisationId)) {
            $organisation = Organisations::where('uid', $organisationId)->first();
            if($organisation) {
                $generalSettings = $organisation->general_settings ?? (object)[];
                $orgMaxAmount = $generalSettings->max_bnpl_amount ?? null;
                if(!isEmpty($orgMaxAmount) && $orgMaxAmount > 0) {
                    $maxAmount = min($platformMaxAmount, (float)$orgMaxAmount);
                    $isOrgSpecific = true;
                }
            }
        }

        $outstanding = empty($customerId) ? 0 : $this->getOutstandingBnplAmount($customerId);
        $available = empty($customerId) ? 0 : max(0, $maxAmount - $outstanding);

        // If customer has any PAST_DUE payments, set available to 0
        if(!empty($customerId)) {
            $pastDuePayments = $this->getByX([
                'uuid' => $customerId,
                'status' => 'PAST_DUE'
            ], ['uid']);

            if($pastDuePayments->count() > 0) {
                $available = 0;
            }
        }

        return [
            'outstanding' => $outstanding,
            'available' => $available,
            'max_amount' => $maxAmount,
            'platform_max' => $platformMaxAmount,
            'is_org_specific' => $isOrgSpecific,
        ];
    }

}
