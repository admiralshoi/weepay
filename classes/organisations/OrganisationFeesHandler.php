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
     * Get the reseller fee for an organisation (legacy method - does not account for flat fees)
     * Returns active fee if exists, otherwise platform default
     * The returned fee is the total fee minus cardFee and paymentProviderFee
     *
     * @deprecated Use calculateResellerFee() for transaction-aware fee calculation
     */
    public function resellerFee(string $organisationId): float {
        $totalFee = $this->getTotalFeeRate($organisationId);

        // Subtract card fee and payment provider fee
        $cardFee = (float)(Settings::$app->cardFee ?? 0.39);
        $paymentProviderFee = (float)(Settings::$app->paymentProviderFee ?? 0.39);

        return max(0, $totalFee - $cardFee - $paymentProviderFee);
    }

    /**
     * Get total fee rate for organisation (from org-specific or AppMeta default)
     */
    public function getTotalFeeRate(string $organisationId): float {
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

        $hasOrgFee = !isEmpty($activeFee);
        $totalFee = $hasOrgFee ? (float)$activeFee->fee : (float)Settings::$app->resellerFee;

        debugLog([
            'organisationId' => $organisationId,
            'hasOrgSpecificFee' => $hasOrgFee,
            'orgFee' => $hasOrgFee ? (float)$activeFee->fee : null,
            'defaultFee' => (float)Settings::$app->resellerFee,
            'resultFee' => $totalFee,
        ], 'FEE-CALC-GET-TOTAL-RATE');

        return $totalFee;
    }

    /**
     * Convert flat fee from DKK to target currency
     */
    public function convertFlatFee(float $flatFeeDkk, string $currency): float {
        if ($currency === 'DKK') {
            debugLog([
                'flatFeeDkk' => $flatFeeDkk,
                'currency' => $currency,
                'result' => $flatFeeDkk,
                'note' => 'No conversion needed (DKK)',
            ], 'FEE-CALC-CONVERT-FLAT');
            return $flatFeeDkk;
        }

        $rates = Settings::$app->currencyConversionRates ?? ['DKK' => 1];
        $rate = $rates[$currency] ?? 1;
        $result = $flatFeeDkk * $rate;

        debugLog([
            'flatFeeDkk' => $flatFeeDkk,
            'currency' => $currency,
            'conversionRate' => $rate,
            'allRates' => $rates,
            'result' => $result,
        ], 'FEE-CALC-CONVERT-FLAT');

        return $result;
    }

    /**
     * Calculate dynamic reseller fee percentage accounting for flat fees
     * Formula: r(x) = (T - p) - f/x, capped at 0
     *
     * @param string $organisationId
     * @param float $transactionAmount
     * @param string $currency
     * @return float Reseller fee as percentage (e.g., 4.42 for 4.42%)
     */
    public function calculateResellerFee(string $organisationId, float $transactionAmount, string $currency = 'DKK'): float {
        debugLog([
            'organisationId' => $organisationId,
            'transactionAmount' => $transactionAmount,
            'currency' => $currency,
        ], 'FEE-CALC-RESELLER-START');

        if ($transactionAmount <= 0) {
            debugLog(['error' => 'Transaction amount <= 0', 'result' => 0], 'FEE-CALC-RESELLER-ERROR');
            return 0;
        }

        // Get total desired fee (T)
        $totalFee = $this->getTotalFeeRate($organisationId);

        // Get underlying percentage fees (p)
        $cardFee = (float)(Settings::$app->cardFee ?? 0.39);
        $paymentProviderFee = (float)(Settings::$app->paymentProviderFee ?? 0.39);
        $underlyingPercentage = ($cardFee + $paymentProviderFee) / 100;

        // Get flat fee in transaction currency (f)
        $flatFeeDkk = (float)(Settings::$app->paymentProviderFlatFee ?? 0.75);
        $flatFee = $this->convertFlatFee($flatFeeDkk, $currency);

        // Calculate r(x) = (T - p) - f/x
        $T = $totalFee / 100; // Convert percentage to decimal
        $p = $underlyingPercentage;
        $f = $flatFee;
        $x = $transactionAmount;

        $T_minus_p = $T - $p;
        $f_div_x = $f / $x;
        $resellerRateDecimal = $T_minus_p - $f_div_x;
        $resellerRatePercent = $resellerRateDecimal * 100;
        $result = max(0, $resellerRatePercent);

        debugLog([
            'variables' => [
                'T (totalFee decimal)' => $T,
                'p (cardFee + ppFee decimal)' => $p,
                'f (flatFee in currency)' => $f,
                'x (transactionAmount)' => $x,
            ],
            'calculation' => [
                'T - p' => $T_minus_p,
                'f / x' => $f_div_x,
                'r(x) = (T - p) - f/x' => $resellerRateDecimal,
                'r(x) as percent' => $resellerRatePercent,
                'capped at 0' => $result,
            ],
            'inputs' => [
                'totalFee%' => $totalFee,
                'cardFee%' => $cardFee,
                'paymentProviderFee%' => $paymentProviderFee,
                'flatFeeDkk' => $flatFeeDkk,
                'flatFeeConverted' => $flatFee,
            ],
            'result' => $result,
        ], 'FEE-CALC-RESELLER-RESULT');

        return $result;
    }

    /**
     * Calculate minimum transaction amount for full margin
     * Formula: x_min = f / (T - p)
     *
     * @param string $organisationId
     * @param string $currency
     * @return float Minimum amount in given currency
     */
    public function minimumTransactionForFullMargin(string $organisationId, string $currency = 'DKK'): float {
        $totalFee = $this->getTotalFeeRate($organisationId);
        $cardFee = (float)(Settings::$app->cardFee ?? 0.39);
        $paymentProviderFee = (float)(Settings::$app->paymentProviderFee ?? 0.39);

        $T = $totalFee / 100;
        $p = ($cardFee + $paymentProviderFee) / 100;

        $flatFeeDkk = (float)(Settings::$app->paymentProviderFlatFee ?? 0.75);
        $flatFee = $this->convertFlatFee($flatFeeDkk, $currency);

        $denominator = $T - $p;

        if ($denominator <= 0) {
            debugLog([
                'organisationId' => $organisationId,
                'currency' => $currency,
                'T' => $T,
                'p' => $p,
                'T - p' => $denominator,
                'error' => 'Denominator <= 0, impossible to reach margin',
                'result' => 'PHP_FLOAT_MAX',
            ], 'FEE-CALC-MIN-TRANS-ERROR');
            return PHP_FLOAT_MAX;
        }

        $result = $flatFee / $denominator;

        debugLog([
            'organisationId' => $organisationId,
            'currency' => $currency,
            'variables' => [
                'T (totalFee decimal)' => $T,
                'p (cardFee + ppFee decimal)' => $p,
                'f (flatFee in currency)' => $flatFee,
            ],
            'calculation' => [
                'T - p' => $denominator,
                'x_min = f / (T - p)' => $result,
            ],
            'inputs' => [
                'totalFee%' => $totalFee,
                'cardFee%' => $cardFee,
                'paymentProviderFee%' => $paymentProviderFee,
                'flatFeeDkk' => $flatFeeDkk,
                'flatFeeConverted' => $flatFee,
            ],
            'result' => $result,
        ], 'FEE-CALC-MIN-TRANS-RESULT');

        return $result;
    }

    /**
     * Get fee breakdown for display purposes
     */
    public function getFeeBreakdown(string $organisationId, string $currency = 'DKK'): array {
        debugLog([
            'organisationId' => $organisationId,
            'currency' => $currency,
        ], 'FEE-CALC-BREAKDOWN-START');

        $totalFee = $this->getTotalFeeRate($organisationId);
        $cardFee = (float)(Settings::$app->cardFee ?? 0.39);
        $paymentProviderFee = (float)(Settings::$app->paymentProviderFee ?? 0.39);
        $flatFeeDkk = (float)(Settings::$app->paymentProviderFlatFee ?? 0.75);
        $flatFee = $this->convertFlatFee($flatFeeDkk, $currency);
        $minimumTransaction = $this->minimumTransactionForFullMargin($organisationId, $currency);
        $netFee = max(0, $totalFee - $cardFee - $paymentProviderFee);

        $breakdown = [
            'totalFee' => $totalFee,
            'cardFee' => $cardFee,
            'paymentProviderFee' => $paymentProviderFee,
            'paymentProviderFlatFee' => $flatFee,
            'paymentProviderFlatFeeDkk' => $flatFeeDkk,
            'currency' => $currency,
            'minimumTransaction' => $minimumTransaction,
            'netFee' => $netFee,
        ];

        debugLog([
            'organisationId' => $organisationId,
            'breakdown' => $breakdown,
            'appMetaValues' => [
                'resellerFee' => Settings::$app->resellerFee ?? 'NOT SET',
                'cardFee' => Settings::$app->cardFee ?? 'NOT SET',
                'paymentProviderFee' => Settings::$app->paymentProviderFee ?? 'NOT SET',
                'paymentProviderFlatFee' => Settings::$app->paymentProviderFlatFee ?? 'NOT SET',
                'currencyConversionRates' => Settings::$app->currencyConversionRates ?? 'NOT SET',
            ],
        ], 'FEE-CALC-BREAKDOWN-RESULT');

        return $breakdown;
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
