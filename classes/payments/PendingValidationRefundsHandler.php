<?php

namespace classes\payments;

use classes\utility\Crud;
use Database\Collection;
use Database\model\PendingValidationRefunds;

class PendingValidationRefundsHandler extends Crud {

    public function __construct() {
        parent::__construct(PendingValidationRefunds::newStatic(), "pending_validation_refunds");
    }

    /**
     * Create pending refund record when card validation refund fails
     *
     * @param string|null $orderUid Order UID (null for card change)
     * @param string $customerUid Customer UID
     * @param string $organisationUid Organisation UID
     * @param string $locationUid Location UID
     * @param string $providerUid Payment provider UID
     * @param string $transactionId Viva transaction ID (prid)
     * @param float $amount Amount to refund (usually 1)
     * @param string $currency Currency code
     * @param bool $isTest Whether this is a test transaction
     * @param string|null $failureReason Error message from Viva
     * @param int|null $vivaEventId Viva EventId code
     * @return string|false The created record UID or false on failure
     */
    public function createFromFailedRefund(
        ?string $orderUid,
        string $customerUid,
        string $organisationUid,
        string $locationUid,
        string $providerUid,
        string $transactionId,
        float $amount,
        string $currency,
        bool $isTest,
        ?string $failureReason = null,
        ?int $vivaEventId = null
    ): string|false {
        $data = [
            'uuid' => $customerUid,
            'organisation' => $organisationUid,
            'location' => $locationUid,
            'provider' => $providerUid,
            'prid' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'test' => $isTest ? 1 : 0,
            'status' => 'PENDING',
            'failure_reason' => $failureReason,
            'viva_event_id' => $vivaEventId,
        ];

        // Only add order if not null (null for card change)
        if (!isEmpty($orderUid)) {
            $data['order'] = $orderUid;
        }

        return $this->create($data);
    }

    /**
     * Get pending refunds for an organisation (for merchant dashboard)
     * Only returns PENDING status records
     *
     * @param string $organisationId Organisation UID
     * @param array|null $locationIds Optional array of location UIDs to filter by
     * @return Collection
     */
    public function getPendingForOrganisation(string $organisationId, ?array $locationIds = null): Collection {
        $query = $this->queryBuilder()
            ->where('organisation', $organisationId)
            ->where('status', 'PENDING')
            ->order('created_at', 'DESC');

        if (!empty($locationIds)) {
            $query->whereIn('location', $locationIds);
        }

        return $this->queryGetAll($query);
    }

    /**
     * Get pending refund for an order (for fulfillment page check)
     * Returns the first PENDING record for this order
     *
     * @param string $orderUid Order UID
     * @return object|null
     */
    public function getPendingForOrder(string $orderUid): ?object {
        return $this->queryGetFirst(
            $this->queryBuilder()
                ->where('order', $orderUid)
                ->where('status', 'PENDING')
        );
    }

    /**
     * Mark a pending refund as manually refunded
     *
     * @param string $uid Pending refund UID
     * @param string $refundedByUserUid User who marked it as refunded
     * @return bool Success status
     */
    public function markAsRefunded(string $uid, string $refundedByUserUid): bool {
        return $this->update([
            'status' => 'REFUNDED',
            'refunded_at' => date('Y-m-d H:i:s'),
            'refunded_by' => $refundedByUserUid,
        ], ['uid' => $uid]);
    }

    /**
     * Get count of pending refunds for an organisation
     *
     * @param string $organisationId Organisation UID
     * @param array|null $locationIds Optional array of location UIDs
     * @return int
     */
    public function getPendingCount(string $organisationId, ?array $locationIds = null): int {
        $filters = [
            'organisation' => $organisationId,
            'status' => 'PENDING',
        ];

        if (!empty($locationIds)) {
            return $this->count($filters, ['location' => $locationIds]);
        }

        return $this->count($filters);
    }

}
