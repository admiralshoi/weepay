<?php

namespace classes\organisations;

use classes\lang\Translate;
use classes\utility\Crud;
use Database\Collection;
use Database\model\CheckoutBasket;
use classes\Methods;
use features\Settings;

class CheckoutBasketHandler extends Crud {


    function __construct() {
        parent::__construct(CheckoutBasket::newStatic(), "checkout_basket");
    }



    public function setFulfilled(string $id): bool {
        return $this->update(['status' => 'FULFILLED'], ['uid' => $id]);
    }
    public function setVoid(string $id): bool {
        return $this->update(['status' => 'VOID'], ['uid' => $id]);
    }


    public function getActiveBasket(string $terminalSessionId, array $fields = []): ?object {
        return $this->getFirst(['terminal_session' => $terminalSessionId, 'status' => 'DRAFT'], $fields);
    }


    public function setNew(string $terminalSessionId, string $name, string|int|float $price, string $currency, ?string $note = null): ?string {
        if($this->exists(['terminal_session' => $terminalSessionId, 'status' => 'DRAFT']))
            $this->update(['status' => 'VOID'], ['terminal_session' => $terminalSessionId, 'status' => 'DRAFT']);
        if(!$this->create(['terminal_session' => $terminalSessionId, 'name' => $name, 'price' => $price, 'currency' => $currency, 'note' => $note])) return null;
        return $this->recentUid;
    }




    public function createCheckoutInfo(object $basket, string $planName, int $paymentTimeframe = 90, ?string $birthdate = null, ?string $customerId = null): ?object {
        $plan = Settings::$app->paymentPlans->$planName;
        if(!$plan->enabled) return null;

        // Check age restriction for BNPL plans (installments and pushed)
        if(in_array($planName, ['installments', 'pushed'])) {
            $age = $this->calculateAge($birthdate);
            if($age < 18) {
                return null; // User must be 18+ for BNPL
            }

            // Check BNPL limit
            if(!isEmpty($customerId)) {
                $bnplLimit = Methods::payments()->getBnplLimit($customerId);

                // If basket amount exceeds available BNPL limit, don't show this plan
                if($basket->price > $bnplLimit['available']) {
                    return null;
                }
            }
        }

        if($planName === 'installments' || !Settings::$app->paymentPlans->installments->enabled)  $plan->default = true;
        else $plan->default = false;
        $plan->name = $planName;
        $price = $basket->price;

        $installments = $plan->installments;
        $pricePerInstallment =  round($price / $installments, 2);
        if($installments === 1) $plan->price_title = number_format($pricePerInstallment, 2) . currencySymbol($basket->currency);
        else {
            if(floor($pricePerInstallment) === $pricePerInstallment)
                $plan->price_title = number_format($pricePerInstallment, 2) . currencySymbol($basket->currency) . " &times; " . $installments;
            else $plan->price_title = "";
        }

        if($plan->start === 'now' && $installments === 1) $plan->subtitle = 'Ingen gebyrer';
        elseif($installments > 1) $plan->subtitle = 'Første betaling nu &bullet; Ingen renter';
        else $plan->subtitle = Translate::sentence('Betales den ' . date("d. F", strtotime($plan->start)). ". Ingen renter");

        $payments = [];
        $averagePrice = floor($price / $installments);
        $remainingPrice = $price - $averagePrice * ($installments - 1);
        if($installments > 1) {
            $remainingRatesAfterFirstPayment = $installments -1;
            $daysBetween = max(1, floor($paymentTimeframe / $remainingRatesAfterFirstPayment) -1);
            for($i = 0; $i < $installments; $i++) {
                $paymentTime = strtotime($plan->start . " +" . ($i * $daysBetween) . " days");
                $payments[] = [
                    'price' => ($i === 0) ? $remainingPrice : $averagePrice,
                    'date' => Translate::sentence(strtolower(date("d. F", $paymentTime))),
                    'date_title' => $i === 0 ? "I dag" : Translate::sentence(strtolower(date("d. F", $paymentTime))),
                    "timestamp" => strtotime(date("Y-m-d", $paymentTime)),
                    'installment' => $i + 1,
                ];
            }
        }

        $plan->next_payment_price = floor($price / $installments);
        $plan->to_pay_now = $planName === 'pushed' ? 0 : $remainingPrice;
        $plan->payments = toObject($payments);

        return $plan;
    }


    /**
     * Calculate age from birthdate
     *
     * @param string|null $birthdate Birthdate in Y-m-d format
     * @return int Age in years, returns 0 if birthdate is empty (treated as child)
     */
    private function calculateAge(?string $birthdate): int {
        // If birthdate is empty, treat as child (under 18)
        if(isEmpty($birthdate)) {
            return 0;
        }

        try {
            $birthDate = new \DateTime($birthdate);
            $today = new \DateTime('today');
            $age = $birthDate->diff($today)->y;
            return $age;
        } catch (\Exception $e) {
            // If invalid date format, treat as child for safety
            return 0;
        }
    }






}