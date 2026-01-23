<?php

namespace classes\payments;

use classes\Methods;
use classes\utility\Crud;
use classes\notifications\NotificationTriggers;
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
     * Store initial transaction ID on all payments for an order
     * Used for recurring charges with pushed payment plan
     *
     * @param string $orderId Order UID
     * @param string $transactionId The initial transaction ID from card validation
     * @return bool Success status
     */
    public function storeInitialTransactionId(string $orderId, string $transactionId): bool {
        return $this->update(
            ['initial_transaction_id' => $transactionId],
            ['order' => $orderId]
        );
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
                'cardFee' => (float)Settings::$app->cardFee,
                'paymentProviderFee' => (float)Settings::$app->paymentProviderFee,
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


    // ==========================================
    // RYKKER (DUNNING) METHODS
    // ==========================================

    /**
     * Get all PAST_DUE payments that are scheduled for rykker check
     * Only returns payments where scheduled_at <= now (ready for next rykker)
     *
     * @return Collection
     */
    public function getPastDueForRykker(): Collection {
        return $this->queryGetAll(
            $this->queryBuilder()
                ->where('status', 'PAST_DUE')
                ->where('sent_to_collection', 0)
                ->where('scheduled_at', '<=', date('Y-m-d H:i:s'))
        );
    }

    /**
     * Calculate days overdue for a payment
     *
     * @param object $payment Payment object with due_date
     * @return int Days overdue (0 if not overdue)
     */
    public function getDaysOverdue(object $payment): int {
        if (isEmpty($payment->due_date)) return 0;

        $dueTimestamp = is_numeric($payment->due_date) ? $payment->due_date : strtotime($payment->due_date);
        $now = time();

        if ($now <= $dueTimestamp) return 0;

        return (int) floor(($now - $dueTimestamp) / 86400);
    }

    /**
     * Send a rykker for a payment and update its status
     * Sets scheduled_at for the next rykker check
     *
     * @param string $paymentId Payment UID
     * @param int $rykkerLevel Rykker level (1, 2, or 3)
     * @param float $fee Fee amount for this rykker
     * @return bool Success status
     */
    public function sendRykker(string $paymentId, int $rykkerLevel, float $fee = 0): bool {
        debugLog([
            'payment_id' => $paymentId,
            'rykker_level' => $rykkerLevel,
            'fee' => $fee,
        ], 'SEND_RYKKER_START');

        $payment = $this->get($paymentId);
        if (isEmpty($payment)) {
            debugLog([
                'payment_id' => $paymentId,
                'error' => 'Payment not found',
            ], 'SEND_RYKKER_PAYMENT_NOT_FOUND');
            return false;
        }

        $currentFee = (float)($payment->rykker_fee ?? 0);
        $newFee = $currentFee + $fee;

        $updateData = [
            'rykker_level' => $rykkerLevel,
            'rykker_fee' => $newFee,
            "rykker_{$rykkerLevel}_sent_at" => date('Y-m-d H:i:s'),
        ];

        if ($rykkerLevel >= 3) {
            // Rykker 3: Schedule for collection check in 7 days (grace period)
            // Don't mark sent_to_collection yet - rykkerChecks will do that after 7 days
            $collectionSchedule = date('Y-m-d H:i:s', strtotime('+7 days'));
            $updateData['scheduled_at'] = $collectionSchedule;
            debugLog([
                'payment_id' => $paymentId,
                'action' => 'scheduled_for_collection',
                'collection_date' => $collectionSchedule,
            ], 'SEND_RYKKER_COLLECTION_SCHEDULED');
        } else {
            // Schedule next rykker check
            $nextScheduledAt = $this->calculateNextRykkerDate($rykkerLevel);
            $updateData['scheduled_at'] = $nextScheduledAt;
            debugLog([
                'payment_id' => $paymentId,
                'current_level' => $rykkerLevel,
                'next_scheduled_at' => $nextScheduledAt,
            ], 'SEND_RYKKER_NEXT_SCHEDULED');
        }

        debugLog([
            'payment_id' => $paymentId,
            'update_data' => $updateData,
        ], 'SEND_RYKKER_UPDATE_DATA');

        $result = $this->update($updateData, ['uid' => $paymentId]);

        debugLog([
            'payment_id' => $paymentId,
            'update_success' => $result,
        ], 'SEND_RYKKER_RESULT');

        return $result;
    }

    /**
     * Calculate the next rykker scheduled date based on current level
     *
     * @param int $currentRykkerLevel The rykker level just sent (1 or 2)
     * @return string Next scheduled date
     */
    private function calculateNextRykkerDate(int $currentRykkerLevel): string {
        $rykker1Days = (int)(Settings::$app->rykker_1_days ?? 7);
        $rykker2Days = (int)(Settings::$app->rykker_2_days ?? 14);
        $rykker3Days = (int)(Settings::$app->rykker_3_days ?? 21);

        // Calculate interval to next rykker
        if ($currentRykkerLevel === 1) {
            $daysToNext = $rykker2Days - $rykker1Days;
        } else {
            $daysToNext = $rykker3Days - $rykker2Days;
        }

        // Minimum 1 day interval
        $daysToNext = max(1, $daysToNext);

        debugLog([
            'current_level' => $currentRykkerLevel,
            'rykker_1_days' => $rykker1Days,
            'rykker_2_days' => $rykker2Days,
            'rykker_3_days' => $rykker3Days,
            'days_to_next' => $daysToNext,
        ], 'CALCULATE_NEXT_RYKKER_DATE');

        return date('Y-m-d H:i:s', strtotime("+{$daysToNext} days"));
    }

    /**
     * Reset rykker status for a payment (for disputes/goodwill)
     * Sets a fresh grace period before the next rykker
     *
     * @param string $paymentId Payment UID
     * @param bool $clearFees Whether to also clear accumulated fees
     * @return bool Success status
     */
    public function resetRykker(string $paymentId, bool $clearFees = true, bool $sendNotification = true): bool {
        // Fetch payment before reset (for notification and PDF deletion)
        $payment = $this->get($paymentId);

        // Get grace period from settings (days until first rykker)
        $rykker1Days = (int)(Settings::$app->rykker_1_days ?? 7);

        $updateData = [
            'rykker_level' => 0,
            'rykker_1_sent_at' => null,
            'rykker_2_sent_at' => null,
            'rykker_3_sent_at' => null,
            'sent_to_collection' => 0,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime("+{$rykker1Days} days")),
        ];

        if ($clearFees) {
            $updateData['rykker_fee'] = 0;
        }

        $result = $this->update($updateData, ['uid' => $paymentId]);

        // Delete rykker PDFs if reset was successful
        if ($result && $payment) {
            try {
                Methods::contractDocuments()->deleteRykkerPdfs($payment);
            } catch (\Exception $e) {
                // Log error but don't fail the reset
                debugLog(['error' => $e->getMessage(), 'payment_id' => $paymentId], 'DELETE_RYKKER_PDF_ERROR');
            }
        }

        // Trigger notification if successful and payment had a rykker
        if ($result && $sendNotification && $payment && (int)($payment->rykker_level ?? 0) > 0) {
            NotificationTriggers::paymentRykkerCancelled($payment);
        }

        return $result;
    }

    /**
     * Clear rykker fees when payment is refunded/voided
     *
     * @param string $paymentId Payment UID
     * @return bool Success status
     */
    public function clearRykkerOnRefund(string $paymentId): bool {
        // Get payment for PDF deletion
        $payment = $this->get($paymentId);

        $result = $this->update([
            'rykker_fee' => 0,
        ], ['uid' => $paymentId]);

        // Delete rykker PDFs
        if ($result && $payment) {
            try {
                Methods::contractDocuments()->deleteRykkerPdfs($payment);
            } catch (\Exception $e) {
                debugLog(['error' => $e->getMessage(), 'payment_id' => $paymentId], 'DELETE_RYKKER_PDF_ERROR');
            }
        }

        return $result;
    }

    /**
     * Mark a payment for collection manually
     *
     * @param string $paymentId Payment UID
     * @return bool Success status
     */
    public function markForCollection(string $paymentId): bool {
        return $this->update([
            'sent_to_collection' => 1,
        ], ['uid' => $paymentId]);
    }

    /**
     * Get payments marked for collection
     *
     * @param string|null $organisationId Optional organisation filter
     * @return Collection
     */
    public function getCollectionPayments(?string $organisationId = null): Collection {
        $query = $this->queryBuilder()
            ->where('sent_to_collection', 1);

        if (!isEmpty($organisationId)) {
            $query->where('organisation', $organisationId);
        }

        return $this->queryGetAll($query);
    }

    /**
     * Get rykker statistics for admin/reports
     *
     * @return object Statistics object
     */
    public function getRykkerStats(): object {
        $rykker1 = $this->queryBuilder()->where('rykker_level', 1)->where('sent_to_collection', 0)->count();
        $rykker2 = $this->queryBuilder()->where('rykker_level', 2)->where('sent_to_collection', 0)->count();
        $rykker3 = $this->queryBuilder()->where('rykker_level', 3)->where('sent_to_collection', 0)->count();
        $collection = $this->queryBuilder()->where('sent_to_collection', 1)->count();

        $totalFees = $this->queryBuilder()
            ->where('rykker_fee', '>', 0)
            ->rawSelect('SUM(rykker_fee) as total')
            ->first()->total ?? 0;

        return (object)[
            'rykker_1_count' => $rykker1,
            'rykker_2_count' => $rykker2,
            'rykker_3_count' => $rykker3,
            'collection_count' => $collection,
            'total_fees' => (float)$totalFees,
        ];
    }

}
