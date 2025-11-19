<?php

namespace routing\routes\flows\purchase;

use classes\Methods;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class CustomerApiController {



    #[NoReturn] public static function generateSession(array $args): void  {
        foreach (['ts_id', 'plan'] as $key) if(!array_key_exists($key, $args)) Response()->jsonError("Missing required parameter", [], 400);
        $terminalSessionId = $args["ts_id"];
        $planName = $args["plan"];
        $customer = $_SESSION['customer'];
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();


        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Session not found", [], 404);

        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);
        $basket = $basketHandler->getActiveBasket($terminalSession->terminal->uid);
        if(isEmpty($basket)) Response()->jsonError("Basket not found", [], 404);

        $plan = $basketHandler->createCheckoutInfo($basket, $planName);
        if(isEmpty($plan)) Response()->jsonError("Payment plan not found.", [], 404);

        $slug = $terminalSession->terminal->location->slug;


        $merchantId = "ee6d19b2-8b9e-41ed-874e-044680beeae7";
        $paymentSession = Methods::viva()->createPayment(
            $merchantId,
            $plan->to_pay_now,
            '3387',
            Titles::truncateStr($terminalSession->terminal->location->name . " - " . $basket->name, 13),
            $terminalSession->terminal->location->name . " - " . $basket->name,
            $terminalSession->terminal->location->name . " - " . $basket->name,
//            $basket->currency,
            null,
            !($plan->start === 'now' && $plan->installments === 1),
            false,
            [$terminalSession->terminal->location->name, $basket->name, $customer['name'], $customer['birthdate']],
            null
        );

        if(nestedArray($paymentSession, ['status']) === 'error')
            Response()->jsonError(nestedArray($paymentSession, ['errors', 0,'message'],'Something went wrong'), $paymentSession, 500);

        if(isEmpty($paymentSession)) Response()->jsonError("Something went wrong. Try again later.", [], 500);

        $orderCode = $paymentSession['orderCode'];
        $paymentSessionUrl = "https://demo.vivapayments.com/web/checkout?ref=$orderCode";

        $order = Methods::viva()->getOrder($merchantId, $orderCode);
        $resellerFee = (float)Settings::$app->resellerFee;
        Methods::orders()->insert(
            $terminalSession->terminal->uuid->uid,
            $terminalSession->terminal->location->uid,
            null,
            "ppr_fheioflje98f",
            $planName,
            "EUR",
            $order['RequestAmount'],
            ceil($order['RequestAmount'] * $resellerFee / 100),
            $resellerFee,
            '3387',
            $order['MerchantTrns'],
            $orderCode
        );


        Response()->jsonSuccess('Checkout Session', compact(
            'slug', 'terminalSessionId', 'customer', 'paymentSessionUrl', 'orderCode',
        ));
    }

    #[NoReturn] public static function checkOrderStatus(array $args): void  {
        foreach (['ts_id','order_code'] as $key) if(!array_key_exists($key, $args)) Response()->jsonError("Missing required parameter", [], 400);
        $terminalSessionId = $args["ts_id"];
        $orderCode = $args["order_code"];
        $orderHandler = Methods::orders();

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Session not found", [], 404);

        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);

        $order = $orderHandler->getByPrid($orderCode);
        if(isEmpty($order)) Response()->jsonError("Order not found.", [], 404);
        $merchantId = "ee6d19b2-8b9e-41ed-874e-044680beeae7";
        $providerOrder = Methods::viva()->getOrder($merchantId, $orderCode);
        if(isEmpty($providerOrder)) Response()->jsonError("Provider order not found.", [], 404);
        if(!array_key_exists("OrderCode", $providerOrder)) Response()->jsonError("Provider order error.", [], 404);

        $status = match ($providerOrder['StateId']) {
            default => "DRAFT",
            0 => "PENDING",
            1 => "EXPIRED",
            2 => "CANCELLED",
            3 => "COMPLETED",
        };

        if($order->status !== $status) {
            $orderHandler->update(['status' => $status], ['uid' => $order->uid]);
        }

        Response()->jsonSuccess('Order status', compact(
            'status', 'terminalSessionId','orderCode',
        ));
    }




    #[NoReturn] public static function getBasket(array $args): void  {
        foreach (['ts_id'] as $key) if(!array_key_exists($key, $args)) Response()->jsonError("Missing required parameter", [], 400);
        $terminalSessionId = $args["ts_id"];
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();


        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Session not found", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Session not active", [], 404);


        $basket = [];
        if($terminalSession->terminal->session !== $terminalSession->session) {
            $basket = $basketHandler->getActiveBasket($terminalSession->terminal->uid);
            if(!isEmpty($basket)) $basket->currency_symbol = currencySymbol($basket->currency);
        }

        response()->jsonSuccess("", ['basket' => $basket]);
    }

}