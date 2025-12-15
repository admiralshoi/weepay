<?php

namespace routing\routes\flows\purchase;

use classes\enumerations\Links;
use classes\Methods;
use Database\model\Locations;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;
use routing\routes\verification\OidcController;

class CustomerPageController {

    public static function home(array $args): mixed  {
        $slug = $args['slug'];
        $location = Methods::locations()->getFirst(['slug' => $slug]);
        if(isEmpty($location)) return null;

        // Get published page for this location
        $publishedPage = Methods::locationPages()->getPublished($location->uid);

        // If no published page, use location defaults
        if(isEmpty($publishedPage)) {
            $publishedPage = (object) [
                'uid' => null,
                'state' => 'DRAFT',
                'logo' => $location->logo ?? DEFAULT_LOCATION_LOGO,
                'hero_image' => $location->hero_image ?? DEFAULT_LOCATION_HERO,
                'title' => $location->name,
                'caption' => $location->caption,
                'about_us' => $location->description,
                'credit_widget_enabled' => 1,
                'sections' => []
            ];
        }

        return Views("CUSTOMER_LOCATION_HOME", compact('location', 'publishedPage', 'slug'));
    }

    public static function start(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tid', $args)) return null;
        $terminalId = $args["tid"];
        $locationId = Methods::locations()->getColumn(['slug' => $slug], 'uid');
        if(isEmpty($locationId)) return null;
        $terminal = Methods::terminals()->getByTerminalAndLocationId($terminalId, $locationId);
        if(isEmpty($terminal)) return null;

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminal->location, 'reason' => $paymentCheck['reason']]);
        }

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
        $page = Methods::locationPages()->getFirst(['location' => $locationId, 'state' => 'PUBLISHED']);

        return Views("CUSTOMER_PURCHASE_FLOW_START", compact('slug', 'terminal', 'session', 'oidcSessionId', 'page'));
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

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminalSession->terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminalSession->terminal->location, 'reason' => $paymentCheck['reason']]);
        }

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

        // Get BNPL limit info
        $customerId = $customer->user?->uid ?? null;
        $bnplLimit = !isEmpty($customerId) ? Methods::payments()->getBnplLimit($customerId) : null;

        $page = Methods::locationPages()->getFirst(['location' => $terminalSession->terminal->location->uid, 'state' => 'PUBLISHED']);
        return Views("CUSTOMER_PURCHASE_FLOW_INFO", compact(
            'slug', 'terminalSession', 'customer', 'basket', 'nextStepLink',
            'page', 'bnplLimit'
        ));
    }

    public static function choosePlan(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminalSession->terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminalSession->terminal->location, 'reason' => $paymentCheck['reason']]);
        }

        if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING']))
            Response()->redirect(__url(Links::$merchant->terminals->checkoutStart(
                $slug, $terminalSession->terminal->uid
            )));

        $customer = Methods::oidcAuthentication()->getByUserId();
        if(isEmpty($customer)) {
            return OidcController::expiredPage(null, $terminalSession);
        }

        if(__uuid() !== $terminalSession->customer->uid)
            Response()->redirect(__url(Links::$merchant->terminals->checkoutStart(
                $slug, $terminalSession->terminal->uid
            )));

        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        if($terminalSession->terminal->status !== 'ACTIVE') return null;
        if($terminalSession->terminal->location->status !== 'ACTIVE') return null;
        $basket = $basketHandler->getActiveBasket($terminalSession->uid);


        $defaultToPayNow = 0;
        $defaultPlanId = null;
        $paymentPlans = [];

        // Get customer birthdate and ID for age restriction and BNPL limit
        $birthdate = $customer->user?->birthdate ?? null;
        $customerId = $customer->user?->uid ?? null;

        // Get BNPL limit info
        $bnplLimit = !isEmpty($customerId) ? Methods::payments()->getBnplLimit($customerId) : null;

        foreach (Settings::$app->paymentPlans as $name => $plan){
            $plan = $basketHandler->createCheckoutInfo($basket, $name, 90, $birthdate, $customerId);
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
            'previousStepLink', 'defaultToPayNow', 'defaultPlanId', 'basketHash', 'bnplLimit'
        ));
    }




    #[NoReturn] public static function handlePaymentCallback(array $args): void {
        // Get transaction ID from query param
        $transactionId = $args['t'] ?? null;
        $slug = $args['slug'] ?? null;

        if(isEmpty($transactionId)) {
            Response()->jsonError("Mangler transaktions ID", [], 400);
        }

        // First get order code from query param 's'
        $orderCode = $args['s'] ?? null;
        if(isEmpty($orderCode)) {
            Response()->jsonError("Mangler ordre kode", [], 400);
        }

        // Get the order to find merchant ID
        $orderHandler = Methods::orders();
        $order = $orderHandler->getByPrid($orderCode);
        if(isEmpty($order)) {
            Response()->jsonError("Ugyldig ordre", [], 404);
        }

        $merchantId = nestedArray($order, ['location', 'uuid', 'merchant_prid']);
        if(isEmpty($merchantId)) {
            Response()->jsonError("Ugyldigt forhandler id", [], 404);
        }





        // Get payment details from Viva
        $vivaPayment = Methods::viva()->getPayment($merchantId, $transactionId);
        if(isEmpty($vivaPayment)) {
            Response()->jsonError("Kunne ikke hente betalingsoplysninger", [], 404);
        }

        // Map Viva status to our payment status
        $vivaStatus = $vivaPayment['statusId'] ?? null;
        $paymentStatus = match($vivaStatus) {
            'F', 'C' => 'COMPLETED',    // Payment successful
            'E', 'M', 'MA', 'MI', 'ML', 'MW', 'MS' => 'FAILED',       // Unsuccessful
            'R' => 'REFUNDED',     // Refunded
            'X' => 'CANCELLED',    // Cancelled by merchant
            default => 'PENDING'
        };

        // Find the pending payment for this order
        $paymentsHandler = Methods::payments();
        $currentPayment = $paymentsHandler->getFirst(['order' => $order->uid, 'status' => 'PENDING']);

        if(!isEmpty($currentPayment)) {
            // Update payment status
            $updateData = [
                'status' => $paymentStatus,
                'prid' => $transactionId,
            ];

            if($paymentStatus === 'COMPLETED') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            } elseif(in_array($paymentStatus, ['FAILED', 'CANCELLED'])) {
                $updateData['failure_reason'] = "Viva status: {$vivaStatus}";
            }

            $paymentsHandler->update($updateData, ['uid' => $currentPayment->uid]);
        }

        // Update order status if completed
        if($paymentStatus === 'COMPLETED') {
            $orderHandler->setCompleted($order->uid);
            Methods::terminalSessions()->setCompleted($order->terminal_session?->uid);
            $basket = Methods::checkoutBasket()->getFirst(['terminal_session' => $order->terminal_session->uid, 'status' => 'FULFILLED']);
            if(isEmpty($basket)) Methods::checkoutBasket()->update(['status' => 'FULFILLED'], ['terminal_session' => $order->terminal_session->uid]);
        } elseif(in_array($paymentStatus, ['FAILED', 'CANCELLED'])) {
            $orderHandler->setCancelled($order->uid);
        }

        // Redirect to confirmation page
        $confirmationUrl = __url(Links::$checkout->createOrderConfirmation($order->uid));
        response()->redirect('', $confirmationUrl);
    }


    public static function orderConfirmation(array $args): mixed {
        $orderId = $args['orderid'] ?? null;
        if(isEmpty($orderId)) return null;

        $orderHandler = Methods::orders();
        $order = $orderHandler->get($orderId);
        if(isEmpty($order)) return null;
        if($order->uuid->uid !== __uuid()) return null;

        $payments = Methods::payments()->getByOrder($order->uid);
        $customer = $order->uuid;

        return Views("CUSTOMER_ORDER_CONFIRMATION", compact('order', 'payments', 'customer'));
    }


}