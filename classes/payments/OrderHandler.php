<?php

namespace classes\payments;

use classes\Methods;
use classes\notifications\NotificationTriggers;
use classes\utility\Crud;
use Database\Collection;
use Database\model\Orders;
use env\api\Viva;
use features\Settings;

class OrderHandler extends Crud {



    function __construct() {
        parent::__construct(Orders::newStatic(), "orders");
    }



    public function getByOrganisation(?string $organisationId = null, array $status = ['DRAFT', 'PENDING', 'COMPLETED'], array $fields = []): Collection {
        return $this->getByX(['organisation' => $organisationId, 'status' => $status], $fields);
    }
    public function getByPrid(int|string $prid = null, array $fields = []): ?object {
        return $this->getFirst(['prid' => $prid], $fields);
    }



    public function setCompleted(string $id): bool {
        // DEEP DEBUG: Capture stack trace to find duplicate callers
        $stackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $callers = [];
        foreach ($stackTrace as $i => $trace) {
            $callers[] = ($trace['class'] ?? '') . ($trace['type'] ?? '') . ($trace['function'] ?? 'unknown') . ' @ ' . ($trace['file'] ?? 'unknown') . ':' . ($trace['line'] ?? '?');
        }

        debugLog([
            'order_id' => $id,
            'timestamp' => date('Y-m-d H:i:s.u'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'callers' => $callers,
        ], 'ORDER_SETCOMPLETED_ENTRY');

        $order = $this->get($id);

        debugLog([
            'order_id' => $id,
            'order_found' => !isEmpty($order),
            'current_status' => $order->status ?? 'N/A',
            'will_skip' => isEmpty($order) || $order->status === 'COMPLETED',
        ], 'ORDER_SETCOMPLETED_CHECK');

        if (isEmpty($order) || $order->status === 'COMPLETED') {
            debugLog([
                'order_id' => $id,
                'reason' => isEmpty($order) ? 'order_not_found' : 'already_completed',
            ], 'ORDER_SETCOMPLETED_SKIPPED');
            return false;
        }

        $result = $this->update(['status' => 'COMPLETED'], ['uid' => $id]);

        debugLog([
            'order_id' => $id,
            'update_result' => $result,
            'will_trigger_notification' => $result,
        ], 'ORDER_SETCOMPLETED_UPDATE');

        if ($result) {
            $order->status = 'COMPLETED';
            debugLog([
                'order_id' => $id,
                'order_payment_plan' => $order->payment_plan ?? 'N/A',
                'about_to_call' => 'NotificationTriggers::orderCompleted',
            ], 'ORDER_SETCOMPLETED_BEFORE_NOTIFICATION');

            NotificationTriggers::orderCompleted($order);

            // Also notify the merchant about the new order
            NotificationTriggers::merchantOrderReceived($order);

            debugLog([
                'order_id' => $id,
                'notification_triggered' => true,
            ], 'ORDER_SETCOMPLETED_AFTER_NOTIFICATION');
        }

        return $result;
    }
    public function setPending(string $id): bool {
        return $this->update(['status' => 'PENDING'], ['uid' => $id]);
    }
    public function setCancelled(string $id): bool {
        return $this->update(['status' => 'CANCELLED'], ['uid' => $id]);
    }
    public function setExpired(string $id): bool {
        return $this->update(['status' => 'EXPIRED'], ['uid' => $id]);
    }




    public function insert(
        string $organisation,
        string $location,
        ?string $customerId,
        string $provider,
        string $plan,
        string $currency,
        float|int $amount,
        float|int $isvAmount,
        float|int $isvFee,
        string $sourceCode,
        string $caption,
        ?string $prid,
        ?string $terminalSessionId,
        ?object $planObject = null,
        float|int $creditScore = 0,
        ?string $idempotencyKey = null
    ): bool {
        $user = Methods::users()->get($customerId);
        $isTest = (int)Viva::isSandbox();

        $success = $this->create([
            "organisation" => $organisation,
            "location" => $location,
            "uuid" => $customerId,
            "provider" => $provider,
            "payment_plan" => $plan,
            "currency" => $currency,
            "amount" => $amount,
            "fee_amount" => $isvAmount,
            "fee" => $isvFee,
            'cardFee' => (float)Settings::$app->cardFee,
            'paymentProviderFee' => (float)Settings::$app->paymentProviderFee,
            "source_code" => $sourceCode,
            "caption" => $caption,
            "prid" => $prid,
            "credit_score" => $creditScore,
            "terminal_session" => $terminalSessionId,
            "idempotency_key" => $idempotencyKey,
            "billing_details" => [
                "customer_name" => $user?->full_name,
                "address" => [
                    "line_1" => $user?->address_street,
                    "city" => $user?->address_city,
                    "postal_code" => $user?->address_zip,
                    "region" => $user?->address_region,
                    "country" => $user?->address_country,
                ]
            ],
            "test" => $isTest
        ]);

        if (!$success) {
            return false;
        }

        $orderId = $this->recentUid;
        $paymentsHandler = Methods::payments();

        // Create payment installments for installments or pushed plans
        if (in_array($plan, ['installments', 'pushed']) && !isEmpty($planObject)) {
            $success = $paymentsHandler->createInstallments(
                orderId: $orderId,
                customerId: $customerId,
                organisation: $organisation,
                location: $location,
                provider: $provider,
                currency: $currency,
                plan: $planObject,
                resellerFeePercent: $isvFee,
                paymentPlan: $plan,
                isTest: (bool)$isTest
            );

            if (!$success) {
                // Rollback order creation if payment creation fails
                $this->delete(['uid' => $orderId]);
                return false;
            }
        } elseif ($plan === 'direct') {
            // Create a single payment for direct plans
            $success = $paymentsHandler->create([
                'order' => $orderId,
                'uuid' => $customerId,
                'organisation' => $organisation,
                'location' => $location,
                'provider' => $provider,
                'currency' => $currency,
                'amount' => $amount,
                'status' => 'PENDING',
                'due_date' => date('Y-m-d'),
                'test' => $isTest,
            ]);

            if (!$success) {
                // Rollback order creation if payment creation fails
                $this->delete(['uid' => $orderId]);
                return false;
            }
        }

        return true;
    }

    /**
     * Create a temporary card change order for 1 unit validation
     *
     * @param string $organisation Organisation UID
     * @param string $location Location UID
     * @param string $customerId Customer UID
     * @param string $provider Provider UID
     * @param string $currency Currency code
     * @param string $prid Viva order code
     * @param bool $isTest Whether this is a test transaction
     * @param array $metadata Additional metadata to store
     * @return string|null Order UID if created, null on failure
     */
    public function createCardChangeOrder(
        string $organisation,
        string $location,
        string $customerId,
        string $provider,
        string $currency,
        string $prid,
        bool $isTest = false,
        array $metadata = []
    ): ?string {
        $user = Methods::users()->get($customerId);

        $success = $this->create([
            "organisation" => $organisation,
            "location" => $location,
            "uuid" => $customerId,
            "provider" => $provider,
            "type" => "card_change",
            "payment_plan" => null,
            "currency" => $currency,
            "amount" => 1, // 1 unit validation
            "fee_amount" => 0,
            "fee" => 0,
            "cardFee" => 0,
            "paymentProviderFee" => 0,
            "source_code" => null,
            "caption" => "Kortskift",
            "prid" => $prid,
            "credit_score" => 0,
            "terminal_session" => null,
            "billing_details" => array_merge([
                "customer_name" => $user?->full_name,
            ], $metadata),
            "test" => (int)$isTest
        ]);

        return $success ? $this->recentUid : null;
    }

    /**
     * Delete a card change order by UID
     *
     * @param string $orderUid Order UID
     * @return bool Success
     */
    public function deleteCardChangeOrder(string $orderUid): bool {
        $order = $this->get($orderUid);
        if (isEmpty($order) || $order->type !== 'card_change') {
            return false;
        }
        return $this->delete(['uid' => $orderUid]);
    }


}