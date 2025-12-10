<?php

namespace routing\routes\flows\purchase;

use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;
use routing\routes\verification\OidcController;

class CustomerApiController {



    #[NoReturn] public static function generateSession(array $args): void  {
        foreach (['ts_id', 'plan'] as $key) if(!array_key_exists($key, $args)) Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["ts_id"];
        $planName = $args["plan"];

        $customer = Methods::oidcAuthentication()->getByUserId();
        if(isEmpty($customer)) Response()->jsonError("Ugyldig kunde", [], 400);
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();


        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);
        $location = $terminalSession->terminal->location;

        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        if($location->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        $basket = $basketHandler->getActiveBasket($terminalSession->uid);
        if(isEmpty($basket)) Response()->jsonError("Kurven blev ikke fundet", [], 404);

        $plan = $basketHandler->createCheckoutInfo($basket, $planName);
        if(isEmpty($plan)) Response()->jsonError("Ugyldigetalingsplan.", [], 404);

        $slug = $location->slug;

        testLog($plan,'checkoutsessionplan');

        $merchantId = "ee6d19b2-8b9e-41ed-874e-044680beeae7";
        $paymentSession = Methods::viva()->createPayment(
            $merchantId,
            $plan->to_pay_now,
            $location->source_prid,
            $terminalSession->customer,
            $location->name,
            $location->name . ": " . $basket->name,
            $location->name . " - " . $basket->name,
            $basket->currency,
            !($plan->start === 'now' && $plan->installments === 1),
            false,
            [$location->name, $basket->name, $customer->user->full_name, $customer->user->birthdate],
            null
        );

        if(nestedArray($paymentSession, ['status']) === 'error')
            Response()->jsonError(nestedArray($paymentSession, ['errors', 0,'message'],'Noget gik galt'), $paymentSession, 500);

        if(isEmpty($paymentSession)) Response()->jsonError("Noget gik galt. Prøv igen senere.", [], 500);

        $orderCode = $paymentSession['orderCode'];
        $paymentSessionUrl = "https://demo.vivapayments.com/web/checkout?ref=$orderCode";

        $order = Methods::viva()->getOrder($merchantId, $orderCode);
        $resellerFee = (float)Settings::$app->resellerFee;
        Methods::orders()->insert(
            $terminalSession->terminal->uuid->uid,
            $location->uid,
            $customer->user->uid,
            "ppr_fheioflje98f",
            $planName,
            "EUR",
            $order['RequestAmount'],
            ceil($order['RequestAmount'] * $resellerFee / 100),
            $resellerFee,
            $location->source_prid,
            $order['MerchantTrns'],
            $orderCode,
            $terminalSession->uid
        );


        Response()->jsonSuccess('Checkout Session', compact(
            'slug', 'terminalSessionId', 'customer', 'paymentSessionUrl', 'orderCode',
        ));
    }

    #[NoReturn] public static function checkOrderStatus(array $args): void  {
        foreach (['ts_id','order_code'] as $key) if(!array_key_exists($key, $args)) Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["ts_id"];
        $orderCode = $args["order_code"];
        $orderHandler = Methods::orders();

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);

        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);

        $order = $orderHandler->getByPrid($orderCode);
        if(isEmpty($order)) Response()->jsonError("Ugyldig ordre.", [], 404);
        $merchantId = "ee6d19b2-8b9e-41ed-874e-044680beeae7";
        $providerOrder = Methods::viva()->getOrder($merchantId, $orderCode);
        if(isEmpty($providerOrder)) Response()->jsonError("Ugyldig betalingsudbyder.", [], 404);
        if(!array_key_exists("OrderCode", $providerOrder)) Response()->jsonError("Der opstod  en fejl hos betalingsudbyderen", [], 404);

        $status = match ($providerOrder['StateId']) {
            default => "DRAFT",
            0 => "PENDING",
            1 => "EXPIRED",
            2 => "CANCELLED",
            3 => "COMPLETED",
        };

        if($order->status !== $status) {
            $basket = Methods::checkoutBasket()->getActiveBasket($terminalSessionId, ['uid']);
            $orderHandler->update(['status' => $status], ['uid' => $order->uid]);
            if(in_array($status, ["EXPIRED", "CANCELLED"])) {
                Methods::terminalSessions()->setVoid($terminalSessionId);
                Methods::checkoutBasket()->setFulfilled($basket->uid);
            }
            elseif($status === 'COMPLETED') {
                Methods::terminalSessions()->setCompleted($terminalSessionId);
                Methods::checkoutBasket()->setVoid($basket->uid);
            }
        }

        Response()->jsonSuccess('Ordre status', compact(
            'status', 'terminalSessionId','orderCode',
        ));
    }




    #[NoReturn] public static function voidTerminalSession(array $args): void  {
        foreach (['id'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["id"];

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);


        if($terminalSession->customer !== __uuid() && $terminalSession->csrf !== __csrf())
            Response()->jsonError("Ugyldig session", [], 401);

        Methods::terminalSessions()->setVoid($terminalSessionId);
        if($terminalSession->terminal->session === $terminalSession->session)
            Methods::terminals()->setIdle($terminalSession->terminal->uid);


        $slug = $terminalSession->terminal->location->slug;
        response()
            ->setRedirect(__url(Links::$merchant->public->getLocationPage($slug)))
            ->jsonSuccess("Købet er blevet annulleret");
    }




    #[NoReturn] public static function getBasket(array $args): void  {
        foreach (['id'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["id"];
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);

        $redirect = __url(Links::$merchant->terminals->checkoutStart($terminalSession->terminal->location->slug, $terminalSession->terminal->uid));

        if(!in_array($terminalSession->state, ['PENDING', 'ACTIVE']))
            Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv. Opretter ny", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv", [], 404);
        if($terminalSession->customer->uid !== __uuid())
            Response()
                ->setRedirect($redirect)
                ->jsonError("Ugyldig session", [], 401);


        $basket = [];
        if($terminalSession->terminal->session === $terminalSession->session) {
            $basket = $basketHandler->getActiveBasket($terminalSession->uid);
            if(!isEmpty($basket)) {
                $basket->currency_symbol = currencySymbol($basket->currency);
                $basket->slug = $terminalSession->terminal->location->slug;
                $basket->terminal = $terminalSession->terminal->uid;
            }
        }

        response()->jsonSuccess("", ['basket' => $basket]);
    }


    #[NoReturn] public static function getBasketHash(array $args): void  {
        foreach (['id'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["id"];
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);

        $redirect = __url(Links::$merchant->terminals->checkoutStart($terminalSession->terminal->location->slug, $terminalSession->terminal->uid));

        if(!in_array($terminalSession->state, ['PENDING', 'ACTIVE']))
            Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv. Opretter ny", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->setRedirect($redirect)->jsonError("Sessionen er ikke længere aktiv", [], 404);
        if($terminalSession->customer->uid !== __uuid())
            Response()
                ->setRedirect($redirect)
                ->jsonError("Ugyldig session", [], 401);

        $basket = $basketHandler->getActiveBasket($terminalSession->uid);
        if(isEmpty($basket))
            Response()
                ->setRedirect($redirect)
                ->jsonError("Kurven kunne ikke findes", [], 404);

        $paymentPlans = [];
        foreach (Settings::$app->paymentPlans as $name => $plan){
            $plan = $basketHandler->createCheckoutInfo($basket, $name);
            if(isEmpty($plan)) continue;
            $paymentPlans[] = $plan;
        }

        $redirectIfDifferent = __url(Links::$merchant->terminals->getConsumerChoosePlan($terminalSession->terminal->location->slug, $terminalSessionId));
        $basketHash = hash("sha256", json_encode($basket) . "_" . json_encode($paymentPlans));
        response()->jsonSuccess("", ['hash' => $basketHash, 'goto' => $redirectIfDifferent]);
    }

}