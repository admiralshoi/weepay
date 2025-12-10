<?php

namespace routing\routes\flows\purchase;

use classes\enumerations\Links;
use classes\Methods;
use Database\model\Locations;
use features\Settings;
use routing\routes\verification\OidcController;

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

        if(isOidcAuthenticated()) Response()->redirect(
            __url("merchant/{$terminal->location->slug}/checkout/info?tsid={$session->uid}")
        );

        $checkoutQuery = ['tsid' => $session->uid,'next' => 'cpf'];
        $token = crc32(json_encode($checkoutQuery) . "_" . __csrf());
        $existingOidcSession = Methods::oidcSession()->getByToken($token, ['uid', 'expires_at', 'status']);
        $oidcSessionId = null;
        if(!isEmpty($existingOidcSession)) {
            if($existingOidcSession->expires_at <= time() || $existingOidcSession->status !== "DRAFT") {
                Methods::oidcSession()->statusTimeout($existingOidcSession->uid);
                $existingOidcSession = null;
            }
            else $oidcSessionId = $existingOidcSession->uid;
        }

        if(isEmpty($existingOidcSession)) {
            $oidcSessionId = Methods::oidcSession()->setSession(
                "authenticate",
                $checkoutQuery,
                $token,
            );
        }
        if(isEmpty($oidcSessionId)) return null;

        return Views("CUSTOMER_PURCHASE_FLOW_START", compact('slug', 'terminal', 'session', 'oidcSessionId'));
    }

    public static function info(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $sessionId = array_key_exists('sid', $args) ? $args["sid"] : null;
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;
        $redirectUrl = __url(
            Links::$merchant->terminals->checkoutStart($terminalSession->terminal->location->slug, $terminalSession->terminal->uid)
        );

        if(!in_array($terminalSession->state, ['PENDING', 'ACTIVE'])) Response()->redirect($redirectUrl);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->redirect($redirectUrl);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->redirect($redirectUrl);
        $authHandler = Methods::oidcAuthentication();

        if(!isOidcAuthenticated()) {
            if(empty($sessionId)) Response()->redirect($redirectUrl);
            $session =  Methods::oidcSession()->get($sessionId);
            if($session->status !== 'SUCCESS') Response()->redirect($redirectUrl);
            $providerSession = Methods::oidcSession()->getProviderSession($session->prid);
            testLog($providerSession, 'oidc-session-info-page');
            if($providerSession->status === 'EXPIRED') Response()->redirect($redirectUrl);
            $authId = $authHandler->login($providerSession);
            if(empty($authId)) {
                debugLog("Unable to authenticate user with oidc", 'auth-error');
                Response()->redirect($redirectUrl);
            }
            $customer = $authHandler->get($authId);
        }
        else $customer = $authHandler->getByUserId();
        if(isEmpty($customer)) {
            Response()->redirect($redirectUrl);
        }

        $customer->name = $customer->user?->full_name;
        if(isEmpty($terminalSession->customer)) {
            $terminalSession->customer = $customer?->user;
            Methods::terminalSessions()->update(['customer' => $customer?->user?->uid], ['uid' => $terminalSession->uid]);
        }

        if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING'])) {
            $terminalSession = Methods::terminalSessions()->getSession($terminalSession->terminal->uid);
            $currentUrl = $_SERVER['REQUEST_URI'];
            $parsed = parse_url($currentUrl);
            $path = getUrlPath();
            $query = [];
            if (!empty($parsed['query'])) parse_str($parsed['query'], $query);
            $query['tsid'] = $terminalSession->uid;
            Response()->redirect($path . '?' . http_build_query($query));
        }


        $basket = null;
        $nextStepLink = __url(Links::$merchant->terminals->getConsumerChoosePlan($slug, $terminalSessionId));
        if($terminalSession->terminal->state === 'IDLE') Methods::terminals()->update(['state' => 'AWAITING_MERCHANT'], ['uid' => $terminalSessionId]);
        elseif($terminalSession->terminal->state === 'AWAITING_CUSTOMER'  && $terminalSession->terminal->session === $terminalSession->session) {
            Methods::terminals()->update(['state' => 'ACTIVE'], ['uid' => $terminalSessionId]);
            $basket = Methods::checkoutBasket()->excludeForeignKeys()->getActiveBasket($terminalSession->terminal->uid);
        }

        return Views("CUSTOMER_PURCHASE_FLOW_INFO", compact('slug', 'terminalSession', 'customer', 'basket', 'nextStepLink'));
    }

    public static function choosePlan(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;
        $customer = Methods::oidcAuthentication()->getByUserId();
        if(isEmpty($customer)) {
            return OidcController::expiredPage(null, $terminalSession);
        }
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        if($terminalSession->terminal->status !== 'ACTIVE') return null;
        if($terminalSession->terminal->location->status !== 'ACTIVE') return null;
        $basket = $basketHandler->getActiveBasket($terminalSession->uid);


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
        $basketHash = hash("sha256", json_encode($basket) . "_" . json_encode($paymentPlans));

        return Views("CUSTOMER_PURCHASE_FLOW_PLAN", compact(
            'slug', 'terminalSession', 'customer', 'basket', 'paymentPlans',
            'previousStepLink', 'defaultToPayNow', 'defaultPlanId', 'basketHash'
        ));
    }

}