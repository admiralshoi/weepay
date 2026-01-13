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
        $result = $this->update(['status' => 'COMPLETED'], ['uid' => $id]);

        // Trigger order completed notification
        if ($result) {
            $order = $this->get($id);
            if (!isEmpty($order)) {
                NotificationTriggers::orderCompleted($order);
            }
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
        float|int $creditScore = 0
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

        // Create payment installments for installments or pushed plans
        if (in_array($plan, ['installments', 'pushed']) && !isEmpty($planObject)) {
            $orderId = $this->recentUid;

            $paymentsHandler = Methods::payments();
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
        }

        return true;
    }


}