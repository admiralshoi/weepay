<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use Database\model\OrganisationFees;
use features\Settings;

class OrganisationFeesHandler extends Crud {

    public function __construct() {
        parent::__construct(OrganisationFees::newStatic(), 'organisation');
    }

    /**
     * Get the reseller fee for an organisation
     * Returns active fee if exists, otherwise platform default
     * The returned fee is the total fee minus cardFee and paymentProviderFee
     */
    public function resellerFee(string $organisationId): float {
        $now = time();

        $activeFee = $this->queryBuilder()
            ->where('organisation', $organisationId)
            ->where('enabled', 1)
            ->where('start_time', '<=', $now)
            ->startGroup("OR")
                ->where('end_time', '>=', $now)
                ->whereColumnIsNull('end_time')
            ->endGroup()
            ->first();

        $totalFee = !isEmpty($activeFee) ? (float) $activeFee->fee : (float) Settings::$app->resellerFee;

        // Subtract card fee and payment provider fee
        $cardFee = (float) (Settings::$app->cardFee ?? 0.39);
        $paymentProviderFee = (float) (Settings::$app->paymentProviderFee ?? 0.39);

        return max(0, $totalFee - $cardFee - $paymentProviderFee);
    }

    /**
     * Insert new organisation fee with overlap handling
     */
    public function insertFee(
        string $organisationId,
        float|int|string $fee,
        int $startTime,
        ?int $endTime = null,
        ?string $createdBy = null,
        ?string $reason = null
    ): ?string {
        // Ensure start time is not in the past
        $now = time();
        if($startTime < $now) {
            $startTime = $now;
        }

        $existingFees = $this->excludeForeignKeys()->getByX(['organisation' => $organisationId, 'enabled' => 1]);

        foreach($existingFees->list() as $existing) {
            $existingStart = $existing->start_time;
            $existingEnd = $existing->end_time;

            // Check for overlap
            $hasOverlap = false;

            if($endTime === null) {
                // New fee has no end - overlaps if existing starts before new fee ends (or has no end)
                if($existingEnd === null || $existingEnd > $startTime) {
                    $hasOverlap = true;
                }
            } else {
                // New fee has end - check if ranges overlap
                if($existingEnd === null) {
                    // Existing has no end - overlaps if it starts before new fee ends
                    if($existingStart < $endTime) {
                        $hasOverlap = true;
                    }
                } else {
                    // Both have ends - check if they overlap
                    if(!($existingEnd <= $startTime || $existingStart >= $endTime)) {
                        $hasOverlap = true;
                    }
                }
            }

            if($hasOverlap) {
                // Check if existing is completely overlapped
                if($existingStart >= $startTime && ($existingEnd === null || ($endTime !== null && $existingEnd <= $endTime))) {
                    // Completely overlapped - disable it
                    $this->update(['enabled' => 0], ['uid' => $existing->uid]);
                }
                // Check if new fee starts during existing fee
                elseif($existingStart < $startTime && ($existingEnd === null || $existingEnd > $startTime)) {
                    // Existing fee should end when new one starts
                    $this->update(['end_time' => $startTime], ['uid' => $existing->uid]);
                }
                // Check if new fee ends during existing fee
                elseif($endTime !== null && $existingStart < $endTime && ($existingEnd === null || $existingEnd > $endTime)) {
                    // Existing fee should start when new one ends
                    $this->update(['start_time' => $endTime], ['uid' => $existing->uid]);
                }
            }
        }

        // Insert new fee
        if(!$this->create([
            'organisation' => $organisationId,
            'fee' => $fee,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'enabled' => 1,
            'created_by' => $createdBy,
            'reason' => $reason
        ])) return null;
        return $this->recentUid;
    }
}
