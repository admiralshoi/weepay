<?php

namespace classes\payments;

use classes\utility\Crud;
use Database\model\PaymentMethods;

class PaymentMethodsHandler extends Crud {

    /**
     * Mapping of Viva bankId to display brand name
     */
    private const BRAND_MAP = [
        'NET_MASTER' => 'Mastercard',
        'NET_VISA' => 'Visa',
        'NET_AMEX' => 'American Express',
        'NET_DINERS' => 'Diners Club',
        'NET_DISCOVER' => 'Discover',
        'NET_JCB' => 'JCB',
    ];

    public function __construct() {
        parent::__construct(PaymentMethods::newStatic(), "payment_methods");
    }

    /**
     * Create or find a payment method from Viva transaction data
     *
     * @param string $userId User UID
     * @param array $vivaData Data from Viva getPayment() response
     * @param bool $isTest Whether this is a test/sandbox transaction
     * @return object|null The PaymentMethod record
     */
    public function createFromVivaTransaction(
        string $userId,
        array $vivaData,
        bool $isTest = false
    ): ?object {
        // Extract cardUniqueReference for deduplication
        $cardUniqueReference = $vivaData['cardUniqueReference'] ?? null;

        if (isEmpty($cardUniqueReference)) {
            errorLog(['vivaData' => $vivaData], 'payment-method-missing-card-reference');
            return null;
        }

        // Check if this card already exists for this user
        $existing = $this->findByPrid($userId, $cardUniqueReference);
        if ($existing) {
            return $existing;
        }

        // Map bankId to brand
        $bankId = $vivaData['bankId'] ?? null;
        $brand = self::BRAND_MAP[$bankId] ?? 'Unknown';

        // Extract last 4 digits
        $last4 = $vivaData['primaryAccountNumberLast4Digits'] ?? null;
        if ($last4 !== null) {
            $last4 = (int)$last4;
        }

        // Parse expiry date (format: "2033-11-30T00:00:00")
        $expMonth = null;
        $expYear = null;
        if (!isEmpty($vivaData['cardExpirationDate'])) {
            $expDate = strtotime($vivaData['cardExpirationDate']);
            if ($expDate) {
                $expMonth = date('m', $expDate);
                $expYear = date('Y', $expDate);
            }
        }

        // Determine type (card is default for now)
        $type = 'card';

        // Create title like "Mastercard **** 0101"
        $title = $brand;
        if ($last4) {
            $title .= ' **** ' . str_pad((string)$last4, 4, '0', STR_PAD_LEFT);
        }

        // Create new payment method
        $data = [
            'type' => $type,
            'brand' => $brand,
            'last4' => $last4,
            'exp_month' => $expMonth,
            'exp_year' => $expYear,
            'prid' => $cardUniqueReference,
            'uuid' => $userId,
            'title' => $title,
            'test' => (int)$isTest,
            'deleted' => 0,
        ];

        $created = $this->create($data);

        if ($created) {
            return $this->get($this->recentUid);
        }

        return null;
    }

    /**
     * Find existing payment method by Viva card unique reference (stored in prid)
     *
     * @param string $userId User UID
     * @param string $cardUniqueReference The cardUniqueReference from Viva
     * @return object|null The PaymentMethod record if found
     */
    public function findByPrid(string $userId, string $cardUniqueReference): ?object {
        return $this->getFirst([
            'uuid' => $userId,
            'prid' => $cardUniqueReference,
            'deleted' => 0,
        ]);
    }

    /**
     * Get all active payment methods for a user
     *
     * @param string $userId User UID
     * @return array Array of PaymentMethod records
     */
    public function getForUser(string $userId): array {
        $results = $this->getByX([
            'uuid' => $userId,
            'deleted' => 0,
        ]);

        return $results ?: [];
    }

    /**
     * Soft delete a payment method
     *
     * @param string $uid PaymentMethod UID
     * @return bool Success status
     */
    public function softDelete(string $uid): bool {
        return $this->update(['deleted' => 1], ['uid' => $uid]);
    }

    /**
     * Get brand display name from Viva bankId
     *
     * @param string|null $bankId Viva bankId
     * @return string Brand display name
     */
    public static function getBrandFromBankId(?string $bankId): string {
        return self::BRAND_MAP[$bankId] ?? 'Unknown';
    }

    /**
     * Backfill payment methods for all existing payments that have an initial_transaction_id
     * but no payment_method set. Fetches card details from Viva and creates/links PaymentMethod.
     *
     * Call this manually to populate payment methods for historical payments.
     *
     * @return array Summary of results: ['processed' => int, 'success' => int, 'failed' => int, 'skipped' => int, 'errors' => array]
     */
    public function backfillAllPayments(): array {
        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $paymentsHandler = \classes\Methods::payments();

        // Get all payments that have initial_transaction_id but no payment_method
        // Use queryGetAll to properly resolve foreign keys
        $query = $paymentsHandler->queryBuilder()
            ->whereNotNull('initial_transaction_id')
            ->whereNull('payment_method');

        $payments = $paymentsHandler->queryGetAll($query);

        debugLog(['total_payments' => $payments->count()], 'BACKFILL_START');
        prettyPrint(['total_payments' => $payments->count()]);

        foreach ($payments->list() as $payment) {
            $results['processed']++;


            // Get merchant_prid through order -> organisation
            $merchantPrid = null;
            if (is_object($payment->order) && is_object($payment->order->location->uuid)) {
                $merchantPrid = $payment->order->location->uuid->merchant_prid ?? null;
            }
            prettyPrint($merchantPrid);

            if (isEmpty($merchantPrid)) {
                $results['skipped']++;
                $results['errors'][] = [
                    'payment_uid' => $payment->uid,
                    'error' => 'Missing merchant_prid',
                ];
                continue;
            }

            // Get customer UID
            $customerUid = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
            if (isEmpty($customerUid)) {
                $results['skipped']++;
                $results['errors'][] = [
                    'payment_uid' => $payment->uid,
                    'error' => 'Missing customer uid',
                ];
                continue;
            }

            // Determine if sandbox or live based on payment test flag
            $isTest = (bool)($payment->test ?? false);
            $viva = \classes\Methods::viva();
            if ($isTest) {
                $viva->sandbox();
            } else {
                $viva->live();
            }

            // Fetch payment info from Viva
            try {
                $paymentInfo = $viva->getPayment($merchantPrid, $payment->initial_transaction_id);

                if (isEmpty($paymentInfo) || isEmpty($paymentInfo['cardUniqueReference'] ?? null)) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'payment_uid' => $payment->uid,
                        'transaction_id' => $payment->initial_transaction_id,
                        'error' => 'No card info returned from Viva',
                    ];
                    continue;
                }

                // Create or find payment method
                $paymentMethod = $this->createFromVivaTransaction(
                    $customerUid,
                    $paymentInfo,
                    $isTest
                );

                if ($paymentMethod) {
                    // Update the payment with the payment_method reference
                    $paymentsHandler->excludeForeignKeys()->update(
                        ['payment_method' => $paymentMethod->uid],
                        ['uid' => $payment->uid]
                    );
                    $results['success']++;

                    debugLog([
                        'payment_uid' => $payment->uid,
                        'payment_method_uid' => $paymentMethod->uid,
                        'brand' => $paymentMethod->brand,
                        'last4' => $paymentMethod->last4,
                    ], 'BACKFILL_PAYMENT_SUCCESS');
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'payment_uid' => $payment->uid,
                        'error' => 'Failed to create payment method',
                    ];
                }

            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'payment_uid' => $payment->uid,
                    'error' => $e->getMessage(),
                ];
            }
        }

        debugLog($results, 'BACKFILL_COMPLETE');

        return $results;
    }
}
