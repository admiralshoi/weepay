<?php

namespace classes\payments;

use classes\Methods;

/**
 * Card Validation Service
 *
 * Provides reusable methods for:
 * 1. Validating cards via micro-charge + instant refund
 * 2. Charging using stored transaction IDs (recurring payments)
 *
 * Used for:
 * - "Pushed" payment plan (deferred payment to 1st of next month)
 * - Future "change card" functionality
 */
class CardValidationService
{
    /**
     * Process a card validation after the customer has completed the 1 [currency] payment.
     * This should be called after the Viva checkout completes.
     *
     * Steps:
     * 1. Verify the payment completed successfully
     * 2. Get the transaction ID from the completed payment
     * 3. Instantly refund the validation charge
     * 4. Return the transaction ID for future recurring charges
     *
     * @param string $merchantId The merchant's Viva account ID
     * @param string $orderCode The Viva order code from the validation payment
     * @param string $currency The currency used (for refund)
     * @param bool $isTest Whether this is a test/sandbox transaction
     * @param string|null $organisationUid Organisation UID for error notifications
     * @param string|null $orderUid Order UID for pending refund tracking
     * @param string|null $customerUid Customer UID for pending refund tracking
     * @param string|null $locationUid Location UID for pending refund tracking
     * @param string|null $providerUid Provider UID for pending refund tracking
     * @return array{success: bool, transaction_id?: string, error?: string}
     */
    public static function processValidationPayment(
        string $merchantId,
        string $orderCode,
        string $currency = 'DKK',
        bool $isTest = false,
        ?string $organisationUid = null,
        ?string $orderUid = null,
        ?string $customerUid = null,
        ?string $locationUid = null,
        ?string $providerUid = null
    ): array {
        $viva = Methods::viva();
        if (!$isTest) {
            $viva->live();
        }

        debugLog([
            'action' => 'processValidationPayment',
            'merchantId' => $merchantId,
            'orderCode' => $orderCode,
            'currency' => $currency,
            'isTest' => $isTest,
        ], 'CARD_VALIDATION_START');

        // Get the payment details to retrieve the transaction ID
        $paymentDetails = $viva->getPaymentByOrderId($merchantId, $orderCode);

        if (empty($paymentDetails) || !isset($paymentDetails['Transactions'])) {
            debugLog(['error' => 'No payment found', 'response' => $paymentDetails], 'CARD_VALIDATION_ERROR');
            return [
                'success' => false,
                'error' => 'Kunne ikke finde betalingen',
            ];
        }

        // Get the transaction ID from the completed payment
        $transactions = $paymentDetails['Transactions'] ?? [];
        $completedTransaction = null;
        foreach ($transactions as $tx) {
            if (($tx['StatusId'] ?? '') === 'F') { // F = Finished/Completed
                $completedTransaction = $tx;
                break;
            }
        }

        if (!$completedTransaction) {
            debugLog(['error' => 'No completed transaction', 'transactions' => $transactions], 'CARD_VALIDATION_ERROR');
            return [
                'success' => false,
                'error' => 'Betalingen blev ikke gennemført',
            ];
        }

        $transactionId = $completedTransaction['TransactionId'] ?? null;
        if (empty($transactionId)) {
            debugLog(['error' => 'Missing transaction ID', 'transaction' => $completedTransaction], 'CARD_VALIDATION_ERROR');
            return [
                'success' => false,
                'error' => 'Mangler transaktions-ID',
            ];
        }

        debugLog([
            'transactionId' => $transactionId,
            'amount' => $completedTransaction['Amount'] ?? 0,
        ], 'CARD_VALIDATION_TRANSACTION_FOUND');

        // Instantly refund the 1 [currency] validation charge
        $refundResult = $viva->refundTransaction(
            $merchantId,
            $transactionId,
            1, // Always 1 unit of currency
            null,
            $currency
        );

        $refundFailed = false;
        if (empty($refundResult) || !isset($refundResult['TransactionId'])) {
            debugLog(['error' => 'Refund failed', 'result' => $refundResult], 'CARD_VALIDATION_REFUND_ERROR');
            // Even if refund fails, we still have a valid transaction ID
            // The 1 [currency] is a small loss, but we can still use the card
            errorLog([
                'transactionId' => $transactionId,
                'refundResult' => $refundResult,
            ], 'card-validation-refund-failed');

            $refundFailed = true;

            // Create pending validation refund record for tracking
            // orderUid can be null for card change scenarios, others required
            if (!isEmpty($customerUid) && !isEmpty($organisationUid) && !isEmpty($locationUid) && !isEmpty($providerUid)) {
                Methods::pendingValidationRefunds()->createFromFailedRefund(
                    $orderUid,       // Can be null for card change
                    $customerUid,
                    $organisationUid,
                    $locationUid,
                    $providerUid,
                    $transactionId,
                    1, // Always 1 unit of currency
                    $currency,
                    $isTest,
                    $refundResult['ErrorText'] ?? $refundResult['message'] ?? null,
                    $refundResult['EventId'] ?? $refundResult['ErrorCode'] ?? null
                );
            }

            // Create attention notification if this is a merchant config issue
            if (!isEmpty($organisationUid) && !empty($refundResult)) {
                Methods::requiresAttentionNotifications()->createFromVivaError(
                    'refund',
                    $refundResult,
                    $organisationUid,
                    [
                        'transaction_id' => $transactionId,
                        'order_code' => $orderCode,
                        'amount' => 1,
                        'currency' => $currency,
                        'context' => 'card_validation_refund',
                    ]
                );
            }
        } else {
            debugLog([
                'refundTransactionId' => $refundResult['TransactionId'],
            ], 'CARD_VALIDATION_REFUND_SUCCESS');
        }

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'refund_transaction_id' => $refundResult['TransactionId'] ?? null,
            'refund_failed' => $refundFailed,
            'refund_amount' => 1, // Always 1 unit of currency for validation
        ];
    }

    /**
     * Charge a customer using a stored transaction ID from a previous payment.
     * The original payment must have been made with allowRecurring=true.
     *
     * @param string $merchantId The merchant's Viva account ID
     * @param string $initialTransactionId The transaction ID from the initial/validation payment
     * @param float $amount Amount to charge in major currency units (e.g., 100.00)
     * @param string $currency 3-letter currency code (e.g., 'DKK')
     * @param string|null $description Optional description for the charge
     * @param bool $isTest Whether this is a test/sandbox transaction
     * @param float|null $isvAmount ISV fee amount from payment record (in major currency units)
     * @return array{success: bool, transaction_id?: string, error?: string}
     */
    public static function chargeWithStoredCard(
        string $merchantId,
        string $initialTransactionId,
        float $amount,
        string $currency = 'DKK',
        ?string $description = null,
        bool $isTest = false,
        ?float $isvAmount = null
    ): array {
        $viva = Methods::viva();
        if (!$isTest) {
            $viva->live();
        }

        debugLog([
            'action' => 'chargeWithStoredCard',
            'merchantId' => $merchantId,
            'initialTransactionId' => $initialTransactionId,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'isTest' => $isTest,
            'isvAmount' => $isvAmount,
        ], 'RECURRING_CHARGE_START');

        debugLog([
            'action' => 'BEFORE_chargeRecurring',
            'merchantId' => $merchantId,
            'initialTransactionId' => $initialTransactionId,
            'amount' => $amount,
            'currency' => $currency,
            'isvAmount' => $isvAmount,
            'isTest' => $isTest,
            'viva_mode' => $isTest ? 'SANDBOX' : 'LIVE',
        ], 'RECURRING_CHARGE_CALLING_VIVA');

        $result = $viva->chargeRecurring(
            $merchantId,
            $initialTransactionId,
            $amount,
            null, // sourceCode
            $description, // merchantTrns
            $description, // customerTrns
            $currency,
            $isvAmount // Use stored ISV amount from payment record
        );

        debugLog([
            'raw_result' => $result,
            'result_type' => gettype($result),
            'has_error_code' => isset($result['ErrorCode']),
            'error_code_value' => $result['ErrorCode'] ?? 'NOT_SET',
            'has_event_id' => isset($result['EventId']),
            'event_id_value' => $result['EventId'] ?? 'NOT_SET',
            'has_transaction_id' => isset($result['TransactionId']),
        ], 'RECURRING_CHARGE_RAW_RESPONSE');

        testLog($result, 'VIVA_CHARGE_RECURRING_RESPONSE');

        if (empty($result)) {
            debugLog(['error' => 'Empty response'], 'RECURRING_CHARGE_ERROR');
            return [
                'success' => false,
                'error' => 'Kunne ikke gennemføre betalingen',
            ];
        }

        // Check for Viva error codes
        if (isset($result['ErrorCode']) && $result['ErrorCode'] !== 0) {
            debugLog([
                'error' => 'Viva error',
                'errorCode' => $result['ErrorCode'],
                'errorText' => $result['ErrorText'] ?? null,
                'eventId' => $result['EventId'] ?? null,
            ], 'RECURRING_CHARGE_ERROR');
            return [
                'success' => false,
                'error' => $result['ErrorText'] ?? 'Betalingen fejlede',
                'error_code' => $result['ErrorCode'],
                'event_id' => $result['EventId'] ?? null,
            ];
        }

        $transactionId = $result['TransactionId'] ?? null;
        if (empty($transactionId)) {
            debugLog(['error' => 'Missing transaction ID', 'result' => $result], 'RECURRING_CHARGE_ERROR');
            return [
                'success' => false,
                'error' => 'Mangler transaktions-ID i svar',
            ];
        }

        debugLog([
            'success' => true,
            'transactionId' => $transactionId,
            'eventId' => $result['EventId'] ?? null,
        ], 'RECURRING_CHARGE_SUCCESS');

        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'event_id' => $result['EventId'] ?? null,
            'response' => $result,
        ];
    }

    /**
     * Get the validation amount (always 1 unit of currency)
     *
     * @return int Always returns 1
     */
    public static function getValidationAmount(): int
    {
        return 1;
    }
}
