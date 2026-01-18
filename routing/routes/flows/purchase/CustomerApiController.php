<?php

namespace routing\routes\flows\purchase;

use classes\enumerations\Links;
use classes\Methods;
use classes\payments\CardValidationService;
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

        // Get customer birthdate and ID for age restriction and BNPL limit
        $birthdate = $customer->user?->birthdate ?? null;
        $customerId = $customer->user?->uid ?? null;

        // Get organisation ID for BNPL limit check
        $org = $terminalSession->terminal->location->uuid;
        $organisationId = is_object($org) ? $org->uid : $org;

        $plan = $basketHandler->createCheckoutInfo($basket, $planName, $birthdate, $customerId, $organisationId);
        if(isEmpty($plan)) Response()->jsonError("Ugyldigetalingsplan.", [], 404);

        $slug = $location->slug;

        testLog($plan,'checkoutsessionplan');

        // Get merchant ID from organisation (handle both resolved and unresolved FK)
        $terminalOrg = $terminalSession->terminal->uuid;
        $merchantId = is_object($terminalOrg) ? $terminalOrg->merchant_prid : null;
        if(isEmpty($merchantId)) {
            // Fallback: fetch organisation directly if FK not resolved
            $organisation = \Database\model\Organisations::where('uid', is_object($terminalOrg) ? $terminalOrg->uid : $terminalOrg)->first();
            $merchantId = $organisation?->merchant_prid;
        }
        if(isEmpty($merchantId)) Response()->jsonError("Forhandlers ID er ikke gyldigt. Prøv igen senere", [$location], 404);
        $resellerFee = Methods::organisationFees()->resellerFee($organisationId);

        // For "pushed" plan: create 1 unit currency validation payment with allowRecurring
        // For other plans: create normal payment with to_pay_now amount
        $isPushedPlan = $planName === 'pushed';
        $paymentAmount = $isPushedPlan ? CardValidationService::getValidationAmount() : $plan->to_pay_now;
        $allowRecurring = $isPushedPlan || !($plan->start === 'now' && $plan->installments === 1);

        // Customize description for pushed plan
        $customerNote = $isPushedPlan
            ? "Kortvalidering - " . $location->name
            : $location->name . ": " . $basket->name;
        $merchantNote = $isPushedPlan
            ? "Kortvalidering - " . $basket->name
            : $location->name . " - " . $basket->name;

        $paymentSession = Methods::viva()->createPayment(
            $merchantId,
            $paymentAmount,
            $location->source_prid,
            $terminalSession->customer,
            $location->name,
            $customerNote,
            $merchantNote,
            $basket->currency,
            $allowRecurring,
            false,
            [$location->name, $basket->name, $customer->user->full_name, $customer->user->birthdate],
            null,
            $isPushedPlan ? 0 : $resellerFee  // No reseller fee on 1 unit validation
        );

        if(nestedArray($paymentSession, ['status']) === 'error')
            Response()->jsonError(nestedArray($paymentSession, ['errors', 0,'message'],'Noget gik galt'), $paymentSession, 500);

        if(isEmpty($paymentSession)) Response()->jsonError("Noget gik galt. Prøv igen senere.", [], 500);

        $orderCode = $paymentSession['orderCode'];
        $paymentSessionUrl = Methods::viva()->checkoutUrl($orderCode);

        $order = Methods::viva()->getOrder($merchantId, $orderCode);
        Methods::orders()->insert(
            $terminalSession->terminal->uuid->uid,
            $location->uid,
            $customer->user->uid,
            "ppr_fheioflje98f",
            $planName,
            $basket->currency,
            $basket->price,
//            $order['RequestAmount'],
            round($basket->price * $resellerFee / 100, 2),
            $resellerFee,
            $location->source_prid,
            $order['MerchantTrns'],
            $orderCode,
            $terminalSession->uid,
            $plan,
            .8
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
        $merchantId = nestedArray($order, ['location', 'uuid', 'merchant_prid']);
        if(isEmpty($merchantId)) Response()->jsonError("Ugyldigt forhandler id", toArray($order), 404);

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
            debugLog($status, 'checkorderstatus');
            $basket = Methods::checkoutBasket()->getActiveBasket($terminalSessionId, ['uid']);
            $orderHandler->update(['status' => $status], ['uid' => $order->uid]);
            if(in_array($status, ["EXPIRED", "CANCELLED"])) {
                Methods::terminalSessions()->setVoid($terminalSessionId);
                Methods::checkoutBasket()->setVoid($basket->uid);
                if($status === 'CANCELLED') $orderHandler->setCancelled($order->uid);
                if($status === 'EXPIRED') $orderHandler->setExpired($order->uid);
            }
            elseif($status === 'COMPLETED') {
                Methods::terminalSessions()->setCompleted($terminalSessionId);
                Methods::checkoutBasket()->setFulfilled($basket->uid);

                $currency = $order->currency ?? 'DKK';
                $isTestOrder = (bool)($order->test ?? false);

                // For pushed plan: process card validation (refund 1 unit and store transaction ID)
                if ($order->payment_plan === 'pushed') {
                    $validationResult = CardValidationService::processValidationPayment(
                        $merchantId,
                        $orderCode,
                        $currency,
                        $isTestOrder
                    );

                    if ($validationResult['success'] && !empty($validationResult['transaction_id'])) {
                        // Store the transaction ID on all payments for this order
                        Methods::payments()->storeInitialTransactionId(
                            $order->uid,
                            $validationResult['transaction_id']
                        );
                    } else {
                        // Log error but don't fail - order is created, we can retry later
                        errorLog([
                            'orderUid' => $order->uid,
                            'orderCode' => $orderCode,
                            'validationResult' => $validationResult,
                        ], 'pushed-card-validation-failed');
                    }
                }
                // For installments plan: get transaction ID from first payment and store on all payments
                elseif ($order->payment_plan === 'installments') {
                    $viva = Methods::viva();
                    if (!$isTestOrder) {
                        $viva->live();
                    }

                    $paymentDetails = $viva->getPaymentByOrderId($merchantId, $orderCode);
                    $transactions = $paymentDetails['Transactions'] ?? [];

                    // Find the completed transaction
                    $transactionId = null;
                    foreach ($transactions as $tx) {
                        if (($tx['StatusId'] ?? '') === 'F') { // F = Finished/Completed
                            $transactionId = $tx['TransactionId'] ?? null;
                            break;
                        }
                    }

                    if (!empty($transactionId)) {
                        // Store the transaction ID on all SCHEDULED payments for future recurring charges
                        Methods::payments()->storeInitialTransactionId(
                            $order->uid,
                            $transactionId
                        );

                        debugLog([
                            'orderUid' => $order->uid,
                            'transactionId' => $transactionId,
                        ], 'INSTALLMENTS_TRANSACTION_ID_STORED');
                    } else {
                        errorLog([
                            'orderUid' => $order->uid,
                            'orderCode' => $orderCode,
                            'paymentDetails' => $paymentDetails,
                        ], 'installments-transaction-id-not-found');
                    }
                }

                $orderHandler->setCompleted($order->uid);

                Response()
                    ->setRedirect(__url(Links::$checkout->createOrderConfirmation($order->uid)))
                    ->jsonSuccess("Betalingen er gennemført. Tak fordi du handlede hos {$terminalSession->terminal->location->name}",
                        compact(
                    'status', 'terminalSessionId','orderCode',
                        )
                    );
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

        // If session is completed, basket hash doesn't matter anymore - customer will be redirected by checkOrderStatus
        if($terminalSession->state === 'COMPLETED') {
            Response()->jsonSuccess("Session completed", ['hash' => '', 'goto' => '']);
        }

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

        // Get customer birthdate and ID for age restriction and BNPL limit
        $birthdate = $terminalSession->customer?->birthdate ?? null;
        $customerId = $terminalSession->customer?->uid ?? null;

        $paymentPlans = [];
        foreach (Settings::$app->paymentPlans as $name => $plan){
            $plan = $basketHandler->createCheckoutInfo($basket, $name, $birthdate, $customerId);
            if(isEmpty($plan)) continue;
            $paymentPlans[] = $plan;
        }

        $redirectIfDifferent = __url(Links::$merchant->terminals->getConsumerChoosePlan($terminalSession->terminal->location->slug, $terminalSessionId));
        $basketHash = hash("sha256", json_encode($basket) . "_" . json_encode($paymentPlans));
        response()->jsonSuccess("", ['hash' => $basketHash, 'goto' => $redirectIfDifferent]);
    }


    #[NoReturn] public static function evaluateOrder(array $args): void  {
        foreach (['ts_id', 'order_code'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);

        $terminalSessionId = $args["ts_id"];
        $orderCode = $args["order_code"];
        $orderHandler = Methods::orders();
        $paymentsHandler = Methods::payments();

        // Get the order
        $order = $orderHandler->getByPrid($orderCode);
        if(isEmpty($order)) {
            // Order doesn't exist, nothing to cleanup
            Response()->jsonSuccess("Ordre ikke fundet", []);
        }

        // Verify order belongs to this terminal session
        if($order->terminal_session->uid !== $terminalSessionId) {
            Response()->jsonError("Ugyldig session", [], 401);
        }

        // Get all payments for this order
        $payments = $paymentsHandler->getByOrder($order->uid);

        // Check if all payments are still in initial state (not processed)
        $allUnprocessed = true;
        foreach ($payments->list() as $payment) {
            if(!in_array($payment->status, ['PENDING', 'SCHEDULED', 'DRAFT'])) {
                $allUnprocessed = false;
                break;
            }
        }

        // If all payments are unprocessed, cleanup the order
        if($allUnprocessed && $payments->count() > 0) {
            // Delete all payment rows
            foreach ($payments->list() as $payment) {
                $paymentsHandler->delete(['uid' => $payment->uid]);
            }

            // Delete the order
            $orderHandler->delete(['uid' => $order->uid]);

            Response()->jsonSuccess("Ordre ryddet op", ['cleaned' => true]);
        }

        // Order was processed or no payments exist, just return success
        Response()->jsonSuccess("Ordre allerede behandlet", ['cleaned' => false]);
    }


}