<?php

namespace classes\payments;

use classes\Methods;
use classes\payments\objects\Customer;
use classes\payments\objects\InvoiceHandler;
use classes\payments\objects\OrdersHandler;
use classes\payments\stripe\StripeMethods;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;
use Stripe\Climate\Order;

class ProcessOrder {




    private function initiateOrder(
        OrdersHandler $orderHandler,
        InvoiceHandler $invoiceHandler,
        object $price,
        int $quantity,
        object $paymentMethod,
        ?object $order,
        ?Customer $customer,
        ?string $couponCode = null,
    ): array {
        debugLog($price, 'some-price');
        debugLog($quantity, 'some-quantity');
        debugLog($paymentMethod, 'some-payment-method');
        debugLog($order, 'some-order');
        debugLog($customer?->toArray(), 'some-customer');
        debugLog($couponCode, 'some-coupon-code');



        $productType = $price->product->type;
        $orderId = $order?->uid;
        if(isEmpty($customer)) $customer = Methods::customers()->get($paymentMethod->customer->uid);

        if(isEmpty($order)) {
            $paymentProviderId = Methods::paymentProvider()->getColumn(['name' => Settings::$app->default_payment_provider], 'uid');
            $orderHandler->createNewRow($paymentProviderId, $customer);
            $orderId = $orderHandler->recentUid;
            $order = $orderHandler->get($orderId);
        }

        $createNewRow = true;
        $taxRow = Methods::taxRates()->prepareTaxRow($customer->billingCountry);
        if($productType === 'sub') {
            $existingRow = Methods::subscriptions()->getByOrderId($orderId);
            $lineItems = Methods::lineItems()->prepareSubLineItems(
                $price,
                $quantity,
                $taxRow
            );
        }
        else {
            $existingRow = Methods::perPerUse()->getByOrderId($orderId);
            $lineItems = Methods::lineItems()->preparePpuLineItems(
                $customer->prid,
                $price,
                $quantity,
                $taxRow
            );
        }


        $coupon = $invoiceRow = null;
        if(!empty($couponCode)){
            $coupon = Methods::coupons()->getValidCodeRow($couponCode, $order->provider->uid, $price->product->uid, true);
            if(isEmpty($coupon)) return Response()->arrayError("Invalid coupon code. Please enter another one or leave the field blank.");
        }

        if(!isEmpty($existingRow)) {
            $invoiceRow = $invoiceHandler->getFirst(['order' => $orderId, 'type' => $productType === 'sub' ? 'initial' : 'one_time']);
            if(
                $invoiceRow->payment_method?->uid === $paymentMethod->uid &&
                $coupon?->uid === $invoiceRow?->coupon?->uid &&
                $existingRow->price->uid === $price->uid &&
                $existingRow->quantity === $quantity
            ) {
                $createNewRow = false;
                if($productType === 'sub' && $paymentMethod->uid !== $existingRow->payment_method?->uid) {
                    Methods::subscriptions()->update(['payment_method' => $paymentMethod->uid], ['uid' => $existingRow->uid]);
                }
            }
        }

        $lineItemId = null;
        if($createNewRow) {
            $lineItemsRows = Methods::lineItems()->createLineItems($order, $lineItems, $productType);
            foreach ($lineItemsRows as $lineItemRow) {
                if($lineItemRow['price'] === $price->uid) {
                    $lineItemId = $lineItemRow['uid'];
                    break;
                }
            }
        }
        else $lineItemId = $existingRow->line_item->uid;




        return [
            $createNewRow,
            $existingRow,
            $invoiceRow,
            $lineItems,
            $lineItemId,
            $coupon,
            $customer,
            $order,
            $taxRow,
        ];
    }


    #[NoReturn] public function createSubscription(
        object $price,
        int $quantity,
        object $paymentMethod,
        ?object $order,
        ?Customer $customer,
        ?string $couponCode = null,
        bool $allowSetupIntent = true,
        ?string $portal = null,
        ?string $redirectOnSuccess = null,
    ): array {
        $orderHandler = Methods::orderHandler();
        $subscriptionHandler = Methods::subscriptions();
        $invoiceHandler = Methods::invoices();
        $paymentMethodId = $paymentMethod->prid;

        if($order?->status === 'completed') return Response()->arrayError('This order has already been completed.');
        list($createNewRow, $createNewInvoice, $row, $lineItems, $lineItemId, $coupon, $customer, $order, $taxRow) =
            $this->initiateOrder(
                $orderHandler, $invoiceHandler, $price, $quantity, $paymentMethod, $order, $customer, $couponCode
            );
        $orderId = $order?->uid;



        debugLog($order, 'orderbeforecreatenewrow');
        debugLog($quantity, 'quantitybeforecreatenewrow');

        if($createNewRow) {
            if(!$subscriptionHandler->createNewRow($order, $quantity, $price->uid, $lineItemId, $paymentMethod->uid))
                return Response()->arrayError("Failed to create and insert subscription. Try again later.");
            $subscriptionId = $subscriptionHandler->recentUid;
            $metadata = [
                "type" => 'sub',
                "item_id" => $subscriptionId,
                "order_id" => $orderId,
                "line_item_id" => $lineItemId,
                "price" => $price->uid,
                "customer" => $customer->uid,
                "coupon" => $coupon?->uid,
                'portal' => $portal,
            ];

            testLog($lineItems, "stripe-create-invoice-items");
            $result = StripeMethods::createSubscription($customer->prid, $lineItems, $paymentMethodId, $metadata, $coupon?->prid);
            if($result["status"] === "error") {
                testLog($result, "stripe-sub-create");
                return $result;
            }
            $subscription = $result['subscription'];
            testLog($subscription->toArray(), "stripe-sub-create");

            Methods::taskManager()->newTask(
                'cancel_subscription',
                $subscriptionId,
                time() + Settings::$app->taskManager->subscriptionCreateFailedCancel->ttl,
            );

        }
        else {
            $subscriptionId = $row->uid;
            $subscription = StripeMethods::retrieveSubscription($row->prid);
            if($subscription["status"] === "error") {
                errorLog($subscription['error']['message'], "stripe-sub-create");
                return $subscription;
            }
            $subscription = $subscriptionId['subscription'];
            testLog($subscription->toArray(), "stripe-sub-create");
        }

        $invoiceRow = $invoiceHandler->getFirstOrderBy('created_at', 'DESC', ['order' => $orderId, 'type' => 'initial']);
        if($createNewInvoice || isEmpty($invoiceRow) || $invoiceRow->status === 'failed') {
            $result = $invoiceHandler->createStripeSubscriptionInvoice($subscription, $allowSetupIntent);
            if($result["status"] === "error") return Response()->arrayError($result["error"]["message"]);
            $invoice = $result["invoice"];
            $paymentIntent = $result["paymentIntent"];


            $invoiceHandler->createNewRow(
                $order,
                $invoice->id,
                $invoice->status,
                ($invoice->amount_due / 100),
                ($invoice->amount_paid / 100),
                $invoice->currency,
                $invoice->created,
                "initial",
                $paymentMethod->uid,
                $coupon?->uid,
                $taxRow->uid,
                $paymentIntent,
                $invoice->invoice_pdf,
                $invoice->status_transitions->paid_at,
            );
            $invoiceRow = $invoiceHandler->get($invoiceHandler->recentUid);
            Methods::taskManager()->newTask(
                'void_invoice',
                $invoiceRow->uid,
                time() + Settings::$app->taskManager->voidInvoice->ttl,
            );
        }
        else {
            $invoice = $invoiceHandler->getStripeInvoice($invoiceRow->prid);
            if(isEmpty($invoice)) return Response()->arrayError("Failed to retrieve stripe invoice.");
            if(!empty($invoiceRow->pi_prid)) {
                $paymentIntent = $invoiceHandler->getStripePaymentIntent(
                    $invoiceRow->pi_prid,
                    str_starts_with($invoiceRow->pi_prid, 'pi_') ? 'payment_intent' : 'setup_intent'
                );
                $paymentIntent = $paymentIntent?->id;
            }

            $invoiceHandler->updateRow($invoiceRow->uid,
                $invoice->status,
                ($invoice->amount_paid / 100),
                $invoice->invoice_pdf,
                $invoice->status_transitions->paid_at,
                $paymentMethod->uid,
            );
            $invoiceRow = $invoiceHandler->get($invoiceRow->uid);
        }


        $subscriptionHandler->updateItem(
            $subscriptionId,
            $subscription->status,
            $subscription->id,
            $price->uid,
            $subscription->current_period_start,
            $subscription->current_period_end,
            null,
            $subscription->cancel_at,
            null,
            $subscription->current_period_end,
        );

        $finalized = $invoiceHandler->finalizePaymentIntent(
            $invoiceRow,
            false
        );
        if($finalized->status === 'failed') return Response()->arrayError($finalized->message);


        Methods::subscriptionHistory()->createNewRow(
            $subscriptionId,
            $lineItemId,
            $price->uid,
            $subscription->current_period_start,
            'initial',
            $finalized->status === 'succeeded' ? 'settled' : 'draft'
        );
        Methods::orderHandler()->updateItem($orderId, $finalized->status, ($invoice->amount_due / 100));
        if($finalized->status === 'succeeded') {
            Methods::campaignDays()->insertNewRestricted($subscriptionHandler->get($subscriptionId), true);
            Methods::taskManager()->close($subscriptionId);
            Methods::taskManager()->close($invoiceRow?->uid);
        }
        $finalized->response = $this->paymentResponse(
            $finalized,
            $orderId,
            $redirectOnSuccess,
            [],
            true
        );
        return $finalized->response;
    }






    public function createPpuOrder(
        object $price,
        int $quantity,
        object $paymentMethod,
        ?object $order,
        ?Customer $customer = null,
        ?string $couponCode = null,
        bool $allowSetupIntent = true,
        ?string $portal = null,
        ?string $redirectOnSuccess = null,
    ): array {
        $orderHandler = Methods::orderHandler();
        $ppuHandler = Methods::perPerUse();
        $invoiceHandler = Methods::invoices();
        $paymentMethodId = $paymentMethod->prid;
        if($order?->status === 'completed') return Response()->arrayError('This order has already been completed.');


        list($createNewRow, $row, $invoiceRow, $lineItems, $lineItemId, $coupon, $customer, $order, $taxRow) =
            $this->initiateOrder($orderHandler, $invoiceHandler, $price, $quantity, $paymentMethod, $order, $customer, $couponCode);
        $orderId = $order->uid;


        if($createNewRow) {
            if(!$ppuHandler->createNewRow($order, $quantity, $price->uid, $lineItemId))
                return Response()->arrayError("Failed to create and insert subscription. Try again later.");
            $ppuId = $ppuHandler->recentUid;
            $metadata = [
                "type" => 'ppu',
                "item_id" => $ppuId,
                "order_id" => $orderId,
                "line_item_id" => $lineItemId,
                "price" => $price->uid,
                "customer" => $customer->uid,
                "coupon" => null,
                "portal" => $portal
            ];
            $result = Methods::invoices()->createNewStripePpuInvoice($customer, $lineItems, $paymentMethodId, $metadata, $coupon?->prid, $allowSetupIntent);
            if($result["status"] === "error") return  Response()->arrayError($result["error"]["message"]);
            $paymentIntent = $result["data"]["paymentIntent"];
            $invoice = $result["data"]["invoice"];

            $invoiceHandler->createNewRow(
                $order,
                $invoice->id,
                $invoice->status,
                ($invoice->amount_due / 100),
                ($invoice->amount_paid / 100),
                $invoice->currency,
                $invoice->created,
                "one_time",
                $paymentMethod->uid,
                null,
                $taxRow->uid,
                $paymentIntent,
                $invoice->invoice_pdf,
                $invoice->status_transitions->paid_at,
            );
            $invoiceRow = $invoiceHandler->get($invoiceHandler->recentUid);
            Methods::taskManager()->newTask(
                'void_invoice',
                $invoiceRow->uid,
                time() + Settings::$app->taskManager->voidInvoice->ttl,
            );
        }
        else {
            $ppuId = $row->uid;
        }


        $finalized = $invoiceHandler->finalizePaymentIntent(
            $invoiceRow,
            false
        );
        if($finalized->status === 'failed') return Response()->arrayError($finalized->message);

        Methods::perPerUse()->updateItem($ppuId, $finalized->status);
        Methods::orderHandler()->updateItem($orderId, $finalized->status, ($invoice->amount_due / 100));
        if($finalized->status === 'succeeded') {
            Methods::campaignDays()->insertNewUnrestricted($ppuHandler->get($ppuId), true);
            if(!isEmpty($invoiceRow)) Methods::taskManager()->close($invoiceRow->uid);
        }
        $finalized->response = $this->paymentResponse(
            $finalized,
            $orderId,
            $redirectOnSuccess,
            [],
            true
        );
        return $finalized->response;
    }




    public function paymentResponse(
        object $finalized,
        string $orderId = '',
        ?string $redirectOnSuccess = null,
        array $customMessages = [],
        bool $allowActionRequired = true,
        array $extraData = [],
    ): array {
        $status = isEmpty($finalized->paymentIntent) ? $finalized->status : $finalized->paymentIntent->status;
        $clientSecret = isEmpty($finalized->paymentIntent) ? null : $finalized->paymentIntent->client_secret;
        $object = isEmpty($finalized->paymentIntent) ? null : $finalized->paymentIntent->object;
        if(!$allowActionRequired && in_array($status, ['requires_action', 'requires_confirmation'])) $status = 'requires_payment_method';

        return match ($status) {
            'succeeded' =>
            Response()->arraySuccess(
                array_key_exists('succeeded', $customMessages) ? $customMessages['succeeded'] : "Payment succeeded",
                array_merge(
                    [
                        "action_required" => false,
                        "processing" => false,
                        "confirmation_type" => null,
                        "redirect_uri" => $redirectOnSuccess,
                        "redirect" => $redirectOnSuccess,
                        "refresh" => empty($redirectOnSuccess),
                        "dashboard_url" => __url(""),
                        "client_secret" => null,
                        "order" => $orderId,
                    ],
                    $extraData
                )
            ),


            'requires_action', 'requires_confirmation' =>
                // 🔹 3D Secure or other authentication is required
            Response()->arraySuccess(
                array_key_exists('requires_action', $customMessages) ? $customMessages['requires_action'] : "Action required.",
                array_merge(
                    [
                        "action_required" => true,
                        "processing" => false,
                        "confirmation_type" => $object,
                        "redirect_uri" => $redirectOnSuccess,
                        "refresh" => false,
                        "redirect" => false,
                        "dashboard_url" => __url(""),
                        "order" => $orderId,
                        "client_secret" => $clientSecret,
                    ],
                    $extraData
                )
            ),

            'processing' =>
                // 🕐 Payment is still processing
            Response()->arraySuccess(
                array_key_exists('processing', $customMessages) ? $customMessages['processing'] : "Payment is processing. You'll receive an email once it's finalized.",
                array_merge(
                    [
                        "action_required" => false,
                        "processing" => true,
                        "confirmation_type" => null,
                        "redirect_uri" => $redirectOnSuccess,
                        "redirect" => $redirectOnSuccess,
                        "refresh" => empty($redirectOnSuccess),
                        "dashboard_url" => __url(""),
                        "client_secret" => null,
                        "order" => $orderId,
                    ],
                    $extraData
                )
            ),

            'requires_payment_method' =>
                // ❌ Payment failed, user needs to provide a new payment method
            Response()->arrayError(
                array_key_exists('requires_payment_method', $customMessages) ? $customMessages['requires_payment_method'] : "Payment failed. Please use a different payment method.",
                array_merge(
                    [
                        "action_required" => false,
                        "processing" => false,
                        "confirmation_type" => null,
                        "redirect_uri" => $redirectOnSuccess,
                        "redirect" => false,
                        "refresh" => false,
                        "dashboard_url" => __url(""),
                        "client_secret" => null,
                        "order" => $orderId,
                    ],
                    $extraData
                )
            ),
            'canceled' =>
                // ❌ Payment was canceled
            Response()->arrayError(
                array_key_exists('canceled', $customMessages) ? $customMessages['canceled'] : "Payment was canceled.",
                array_merge(
                    [
                        "action_required" => false,
                        "processing" => false,
                        "confirmation_type" => null,
                        "redirect_uri" => $redirectOnSuccess,
                        "redirect" => false,
                        "refresh" => false,
                        "dashboard_url" => __url(""),
                        "client_secret" => null,
                        "order" => $orderId,
                    ],
                    $extraData
                )
            ),

            default =>
                // ❓ Unexpected case
            Response()->arrayError(
                array_key_exists('default', $customMessages) ? $customMessages['default'] : "Unknown payment status: " . $status,
                array_merge(
                    [
                        "action_required" => false,
                        "processing" => false,
                        "confirmation_type" => null,
                        "redirect_uri" => $redirectOnSuccess,
                        "redirect" => false,
                        "refresh" => false,
                        "dashboard_url" => __url(""),
                        "client_secret" => null,
                        "order" => $orderId,
                    ],
                    $extraData
                )
            )
        };
    }




}