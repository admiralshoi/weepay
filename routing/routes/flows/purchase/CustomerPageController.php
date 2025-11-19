<?php

namespace routing\routes\flows\purchase;

use classes\Methods;
use Database\model\Locations;
use features\Settings;

class CustomerPageController {


    public static function start(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tid', $args)) return null;
        $terminalId = $args["tid"];
        $locationId = Methods::locations()->getColumn(['slug' => $slug], 'uid');
        if(isEmpty($locationId)) return null;
        $terminal = Methods::terminals()->getByTerminalAndLocationId($terminalId, $locationId);
        if(isEmpty($terminal)) return null;

        if($terminal->status !== 'ACTIVE') return null;
        if($terminal->location->status !== 'ACTIVE') return null;
        $session = Methods::terminalSessions()->getSession($terminalId);
        if(isEmpty($session)) return null;

        $verifySession = Methods::signicact()->createSession(
            'user/verify',
            ['tsid' => $session->uid,'next' => 'cpf'],
        );
        if(isset($_SESSION['customer'])) unset($_SESSION['customer']);

        return Views("CUSTOMER_PURCHASE_FLOW_START", compact('slug', 'terminal', 'session', 'verifySession'));
    }

    public static function info(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalId = $args["tsid"];
        $sessionId = array_key_exists('sid', $args) ? $args["sid"] : null;

        if(isset($_SESSION['customer'])) $customer = $_SESSION['customer'];
        else {
            if(!empty($sessionId)) {
                $session = Methods::signicact()->getSession($sessionId);
                if($session['status'] !== 'SUCCESS') Response()->jsonError('Unable to authenticate.', [], 401);

                $customer = [
                    'auth_provider_id' => $session['subject']['id'],
                    'name' => $session['subject']['name'],
                    'birthdate' => $session['subject']['dateOfBirth'],
                    'nin_id' => $session['subject']['nin']['value'],
                    'nin_country' => $session['subject']['nin']['issuingCountry'],
                    'nin_user_type' => $session['subject']['nin']['type'],
                ];
            }
            else {
                $customer = [
                    'auth_provider_id' => "",
                    'name' => "",
                    'birthdate' => "",
                    'nin_id' => "",
                    'nin_country' => "",
                    'nin_user_type' => "",
                ];
            }
            $_SESSION['customer'] = $customer;
        }


        $terminalSession = Methods::terminalSessions()->get($terminalId);
        if(isEmpty($terminalSession)) return null;

        if($terminalSession->terminal->status !== 'ACTIVE') return null;
        if($terminalSession->terminal->location->status !== 'ACTIVE') return null;

        $basket = null;
        if($terminalSession->terminal->state === 'IDLE') Methods::terminals()->update(['state' => 'AWAITING_MERCHANT'], ['uid' => $terminalId]);
        elseif($terminalSession->terminal->state === 'AWAITING_CUSTOMER'  && $terminalSession->terminal->session === $terminalSession->session) {
            Methods::terminals()->update(['state' => 'ACTIVE'], ['uid' => $terminalId]);
            $basket = Methods::checkoutBasket()->excludeForeignKeys()->getActiveBasket($terminalSession->terminal->uid);
        }


        $nextStepLink = __url("merchant/{$slug}/checkout/choose-plan?tsid={$terminalId}");

        return Views("CUSTOMER_PURCHASE_FLOW_INFO", compact('slug', 'terminalSession', 'customer', 'basket', 'nextStepLink'));
    }

    public static function choosePlan(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $customer = $_SESSION['customer'];
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        if($terminalSession->terminal->status !== 'ACTIVE') return null;
        if($terminalSession->terminal->location->status !== 'ACTIVE') return null;
        $basket = $basketHandler->getActiveBasket($terminalSession->terminal->uid);
        if(isEmpty($basket)) return null;


        $defaultToPayNow = 0;
        $defaultPlanId = null;
        $paymentPlans = [];
        foreach (Settings::$app->paymentPlans as $name => $plan){
            $plan = $basketHandler->createCheckoutInfo($basket, $name);
            if(isEmpty($plan)) continue;

            $paymentPlans[] = $plan;
            if($plan->default) {
                $defaultToPayNow = $plan->to_pay_now;
                $defaultPlanId = $name;
            }
        }

        $previousStepLink = __url("merchant/{$slug}/checkout/info?tsid={$terminalSessionId}");

        return Views("CUSTOMER_PURCHASE_FLOW_PLAN", compact(
            'slug', 'terminalSession', 'customer', 'basket', 'paymentPlans',
            'previousStepLink', 'defaultToPayNow', 'defaultPlanId'
        ));
    }

}