<?php

namespace classes\organisations;

use classes\utility\Crud;
use Database\Collection;
use Database\model\CheckoutBasket;
use classes\Methods;
use features\Settings;

class CheckoutBasketHandler extends Crud {


    function __construct() {
        parent::__construct(CheckoutBasket::newStatic(), "checkout_basket");
    }


    public function getActiveBasket(string $terminalId, array $fields = []): ?object {
        return $this->getFirst(['terminal' => $terminalId, 'status' => 'DRAFT'], $fields);
    }



    public function setNew(string $terminalId, string $name, string|int|float $price, string $currency, ?string $note = null): ?string {
        if($this->exists(['terminal' => $terminalId, 'status' => 'DRAFT']))
            $this->update(['status' => 'VOID'], ['terminal' => $terminalId, 'status' => 'DRAFT']);
        if(!$this->create(['terminal' => $terminalId, 'name' => $name, 'price' => $price, 'currency' => $currency, 'note' => $note])) return null;
        return $this->recentUid;
    }




    public function createCheckoutInfo(object $basket, string $planName, int $paymentTimeframe = 90): ?object {
        $plan = Settings::$app->paymentPlans->$planName;
        if(!$plan->enabled) return null;
        if($planName === 'installments' || !Settings::$app->paymentPlans->installments->enabled)  $plan->default = true;
        else $plan->default = false;
        $plan->name = $planName;
        $price = $basket->price;

        $installments = $plan->installments;
        $pricePerInstallment =  round($price / $installments, 2);
        if($installments === 1) $plan->price_title = number_format($pricePerInstallment, 2) . currencySymbol($basket->currency);
        else $plan->price_title = number_format($pricePerInstallment, 2) . currencySymbol($basket->currency) . " &times; " . $installments;

        if($plan->start === 'now' && $installments === 1) $plan->subtitle = 'Ingen gebyrer';
        elseif($installments > 1) $plan->subtitle = 'Første betaling nu &bullet; Ingen renter';
        else $plan->subtitle = 'Betales den ' . date("d. F", strtotime($plan->start));

        $payments = [];
        if($installments > 1) {
            $remainingRatesAfterFirstPayment = $installments -1;
            $daysBetween = max(1, floor($paymentTimeframe / $remainingRatesAfterFirstPayment) -1);
            for($i = 0; $i < $installments; $i++) {
                $paymentTime = strtotime($plan->start . " +" . ($i * $daysBetween) . " days");
                $payments[] = [
                    'price' => ($i <  $installments - 1) ? floor($price / $installments) : ceil($price / $installments),
                    'date' => strtolower(date("d. F", $paymentTime)),
                    'date_title' => $i === 0 ? "I dag" : strtolower(date("d. F", $paymentTime)),
                    "timestamp" => $paymentTime,
                    'installment' => $i + 1,
                ];
            }
        }
        $plan->next_payment_price = floor($price / $installments);
        $plan->to_pay_now = $planName === 'pushed' ? 0 : floor($price / $installments);
        $plan->payments = toObject($payments);

        return $plan;
    }







}