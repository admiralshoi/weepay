<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\notifications\NotificationTriggers;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class OrdersApiController {

    #[NoReturn] public static function getOrders(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        // Validate organisation
        if(isEmpty(Settings::$organisation))
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        // Check permissions
        if(!OrganisationPermissions::__oRead('orders', 'payments'))
            Response()->jsonError("Du har ikke tilladelse til at se ordrer.");

        $organisationUid = Settings::$organisation->organisation->uid;

        // Build base query
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->where('organisation', $organisationUid);

        // Apply scoped location filter if applicable
        $locationIds = Methods::locations()->userLocationPredicate();
        if(!empty($locationIds)) {
            $query->where('location', $locationIds);
        }

        // Apply status filter
        if(!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        // Apply date range filter
        if(!empty($startDate)) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter - search in order UID or customer name/email
        if(!empty($search)) {
            // First check if search matches order uid pattern
            $searchLower = strtolower($search);

            // Get matching user UIDs
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            // Search in orders by uid or customer
            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingUserUids)) {
                $query->where('uuid', $matchingUserUids);
            }
            $query->endGroup();
        }

        // Get total count
        $totalCount = $query->count();

        if($totalCount === 0) {
            Response()->jsonSuccess("", [
                "orders" => [],
                "pagination" => [
                    "page" => 1,
                    "perPage" => $perPage,
                    "total" => 0,
                    "totalPages" => 0,
                ],
            ]);
        }

        // Calculate pagination
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Map sort columns
        $sortColumnMap = [
            'date' => 'created_at',
            'amount' => 'amount',
            'status' => 'status',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Fetch orders
        $orders = $orderHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Get order UIDs for paid amounts
        $orderUids = $orders->pluck('uid')->toArray();
        $paidAmounts = [];
        if(!empty($orderUids)) {
            $paymentHandler = Methods::payments()->excludeForeignKeys();
            $payments = $paymentHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $orderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach ($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }
        }

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
            'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
        ];

        // Transform orders for frontend
        $transformedOrders = [];
        foreach($orders->list() as $order) {
            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $outstanding = orderAmount($order) - $paidAmount;

            // Get customer info - uuid is already resolved to object via foreign key
            $customerName = 'Ukendt';
            $customerEmail = '';
            $customerUid = null;
            if(!isEmpty($order->uuid) && is_object($order->uuid)) {
                $customerName = $order->uuid->full_name ?? 'Ukendt';
                $customerEmail = $order->uuid->email ?? '';
                $customerUid = $order->uuid->uid;
            }

            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m-Y H:i", strtotime($order->created_at)),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_uid' => $customerUid,
                'amount' => orderAmount($order),
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'currency' => $order->currency,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$merchant->orderDetail($order->uid)),
                'customer_url' => $customerUid ? __url(Links::$merchant->customerDetail($customerUid)) : null,
            ];
        }

        Response()->jsonSuccess("", [
            "orders" => $transformedOrders,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }

    #[NoReturn] public static function getLocationOrders(array $args): void {
        $slug = $args["slug"] ?? null;
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        // Validate organisation
        if(isEmpty(Settings::$organisation))
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        // Get location by slug
        if(isEmpty($slug))
            Response()->jsonError("Lokation mangler.");

        $location = Methods::locations()->getFirst(['slug' => $slug, 'uuid' => Settings::$organisation->organisation->uid]);
        if(isEmpty($location))
            Response()->jsonError("Lokation ikke fundet.");

        // Check permissions for this location
        if(!\classes\app\LocationPermissions::__oRead($location, 'orders'))
            Response()->jsonError("Du har ikke tilladelse til at se ordrer for denne lokation.");

        // Build base query
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->where('location', $location->uid);

        // Apply status filter
        if(!empty($filterStatus)) {
            $query->where('status', $filterStatus);
        }

        // Apply date range filter
        if(!empty($startDate)) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter
        if(!empty($search)) {
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingUserUids)) {
                $query->where('uuid', $matchingUserUids);
            }
            $query->endGroup();
        }

        // Get total count
        $totalCount = $query->count();

        if($totalCount === 0) {
            Response()->jsonSuccess("", [
                "orders" => [],
                "pagination" => [
                    "page" => 1,
                    "perPage" => $perPage,
                    "total" => 0,
                    "totalPages" => 0,
                ],
            ]);
        }

        // Calculate pagination
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Map sort columns
        $sortColumnMap = [
            'date' => 'created_at',
            'amount' => 'amount',
            'status' => 'status',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Fetch orders
        $orders = $orderHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Get paid amounts
        $orderUids = $orders->pluck('uid')->toArray();
        $paidAmounts = [];
        if(!empty($orderUids)) {
            $paymentHandler = Methods::payments()->excludeForeignKeys();
            $payments = $paymentHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $orderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach ($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }
        }

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
            'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
        ];

        // Transform orders for frontend
        $transformedOrders = [];
        foreach($orders->list() as $order) {
            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $outstanding = orderAmount($order) - $paidAmount;

            $customerName = 'Ukendt';
            $customerEmail = '';
            $customerUid = null;
            if(is_string($order->uuid)) $order->uuid = Methods::users()->get($order->uuid);
            if(!isEmpty($order->uuid) && is_object($order->uuid)) {
                $customerName = $order->uuid->full_name ?? 'Ukendt';
                $customerEmail = $order->uuid->email ?? '';
                $customerUid = $order->uuid->uid;
            }

            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m-Y H:i", strtotime($order->created_at)),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_uid' => $customerUid,
                'amount' => orderAmount($order),
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'currency' => $order->currency,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$merchant->orderDetail($order->uid)),
                'customer_url' => $customerUid ? __url(Links::$merchant->customerDetail($customerUid)) : null,
            ];
        }

        Response()->jsonSuccess("", [
            "orders" => $transformedOrders,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }


    /**
     * Refund an entire order - refunds all completed payments
     * POST api/merchant/orders/{id}/refund
     */
    #[NoReturn] public static function refundOrder(array $args): void {
        $orderId = $args['id'] ?? null;

        debugLog(['action' => 'refundOrder', 'orderId' => $orderId, 'args' => $args], 'REFUND_ORDER_START');

        if(isEmpty($orderId)) {
            debugLog(['error' => 'Missing order ID'], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Ordre ID mangler.");
        }

        // Validate organisation
        if(isEmpty(Settings::$organisation)) {
            debugLog(['error' => 'No organisation'], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        // Check permissions
        if(!OrganisationPermissions::__oModify('orders', 'payments')) {
            debugLog(['error' => 'No permission'], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Du har ikke tilladelse til at refundere ordrer.");
        }

        $organisationUid = Settings::$organisation->organisation->uid;
        debugLog(['organisationUid' => $organisationUid], 'REFUND_ORDER_ORG');

        // Get the order
        $orderHandler = Methods::orders();
        $order = $orderHandler->get($orderId);

        if(isEmpty($order)) {
            debugLog(['error' => 'Order not found', 'orderId' => $orderId], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Ordre ikke fundet.");
        }

        // Get organisation UID from order (could be object or string depending on handler)
        $orderOrgUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;

        debugLog([
            'orderId' => $order->uid,
            'orderStatus' => $order->status,
            'orderAmount' => $order->amount,
            'orderOrganisation' => $orderOrgUid,
        ], 'REFUND_ORDER_FOUND');

        // Verify the order belongs to the current organisation
        if($orderOrgUid !== $organisationUid) {
            debugLog([
                'error' => 'Organisation mismatch',
                'orderOrg' => $orderOrgUid,
                'userOrg' => $organisationUid,
            ], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Du har ikke adgang til denne ordre.");
        }

        // Check order status - can only refund COMPLETED or PENDING orders
        if(!in_array($order->status, ['COMPLETED', 'PENDING'])) {
            debugLog(['error' => 'Invalid order status', 'status' => $order->status], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Denne ordre kan ikke refunderes (status: {$order->status}).");
        }

        // Get all payments for this order
        $paymentHandler = Methods::payments();
        $completedPayments = $paymentHandler->getByX(['order' => $orderId, 'status' => 'COMPLETED']);

        // Check for pending/scheduled payments that will be voided
        $pendingPayments = $paymentHandler->queryBuilder()
            ->where('order', $orderId)
            ->startGroup('OR')
                ->where('status', 'PENDING')
                ->where('status', 'SCHEDULED')
            ->endGroup()
            ->all();

        $hasCompletedPayments = !$completedPayments->empty();
        $hasPendingPayments = $pendingPayments && !$pendingPayments->empty();

        debugLog([
            'completedPaymentsCount' => $completedPayments->count(),
            'pendingPaymentsCount' => $hasPendingPayments ? $pendingPayments->count() : 0,
        ], 'REFUND_ORDER_PAYMENTS');

        // Must have either completed payments to refund OR pending payments to void
        if(!$hasCompletedPayments && !$hasPendingPayments) {
            debugLog(['error' => 'No payments to refund or void'], 'REFUND_ORDER_ERROR');
            Response()->jsonError("Der er ingen betalinger at refundere eller annullere.");
        }

        // Resolve organisation (could be object or string)
        $orderOrg = is_object($order->organisation) ? $order->organisation : Methods::organisations()->get($order->organisation);

        $totalRefunded = 0;
        $refundErrors = [];
        $paymentsRefundedCount = 0; // Count of payments refunded in this operation

        // Only process Viva refunds if there are completed payments
        if($hasCompletedPayments) {
            $merchantId = $orderOrg->merchant_prid ?? null;

            debugLog(['merchantId' => $merchantId, 'organisationUid' => $orderOrg->uid ?? null], 'REFUND_ORDER_MERCHANT');

            if(isEmpty($merchantId)) {
                debugLog(['error' => 'Missing merchant ID'], 'REFUND_ORDER_ERROR');
                Response()->jsonError("Organisation mangler Viva merchant ID.");
            }

            // Get the Viva API
            $viva = Methods::viva();

            // Refund each completed payment
            foreach($completedPayments->list() as $payment) {
            debugLog([
                'paymentId' => $payment->uid,
                'paymentStatus' => $payment->status,
                'paymentAmount' => $payment->amount,
                'paymentPrid' => $payment->prid,
            ], 'REFUND_ORDER_PAYMENT_LOOP');

            // Skip if already refunded
            if($payment->status === 'REFUNDED') {
                debugLog(['skipped' => 'Already refunded', 'paymentId' => $payment->uid], 'REFUND_ORDER_SKIP');
                continue;
            }

            // Get transaction ID (prid)
            $transactionId = $payment->prid;
            if(isEmpty($transactionId)) {
                debugLog(['error' => 'Missing prid', 'paymentId' => $payment->uid], 'REFUND_ORDER_ERROR');
                $refundErrors[] = "Betaling {$payment->uid} mangler transaktion ID.";
                continue;
            }

            debugLog([
                'calling' => 'viva->refundTransaction',
                'merchantId' => $merchantId,
                'transactionId' => $transactionId,
                'currency' => $payment->currency,
            ], 'REFUND_ORDER_VIVA_CALL');

            // Call Viva refund API (full refund)
            $result = $viva->refundTransaction(
                $merchantId,
                $transactionId,
                (float)$payment->amount, // Amount is required by Viva
                null,
                $payment->currency
            );

            debugLog(['vivaResult' => $result], 'REFUND_ORDER_VIVA_RESULT');

            if(!isEmpty($result) && isset($result['TransactionId'])) {
                debugLog([
                    'success' => true,
                    'paymentId' => $payment->uid,
                    'refundTransactionId' => $result['TransactionId'],
                ], 'REFUND_ORDER_PAYMENT_SUCCESS');

                // Update payment status to REFUNDED
                $paymentHandler->update(['status' => 'REFUNDED'], ['uid' => $payment->uid]);
                $totalRefunded += (float)$payment->amount;
                $paymentsRefundedCount++;
            } else {
                $errorMsg = $result['message'] ?? $result['Message'] ?? $result['ErrorText'] ?? 'Ukendt fejl';
                debugLog([
                    'error' => 'Viva refund failed',
                    'paymentId' => $payment->uid,
                    'errorMsg' => $errorMsg,
                    'fullResult' => $result,
                ], 'REFUND_ORDER_PAYMENT_ERROR');
                $refundErrors[] = "Betaling {$payment->uid}: {$errorMsg}";
            }
        }

        } // End if($hasCompletedPayments)

        debugLog([
            'totalRefunded' => $totalRefunded,
            'refundErrors' => $refundErrors,
        ], 'REFUND_ORDER_SUMMARY');

        // Void all future/pending payments (PENDING, SCHEDULED status)
        $voidedCount = 0;
        if($hasPendingPayments) {
            foreach($pendingPayments->list() as $futurePayment) {
                $paymentHandler->update(['status' => 'VOIDED'], ['uid' => $futurePayment->uid]);
                $voidedCount++;
                debugLog(['voided' => $futurePayment->uid, 'amount' => $futurePayment->amount], 'REFUND_ORDER_VOIDED_PAYMENT');
            }
        }

        debugLog(['voidedCount' => $voidedCount], 'REFUND_ORDER_VOIDED_SUMMARY');

        // Update order amount_refunded, fee_amount and status
        $orderAmount = (float)$order->amount;
        $newAmountRefunded = (float)$order->amount_refunded + $totalRefunded;
        $updateData = ['amount_refunded' => $newAmountRefunded];

        // Recalculate fee_amount based on remaining amount after refund
        $feePercentage = (float)($order->fee ?? 0);
        $remainingAmount = $orderAmount - $newAmountRefunded;

        if($remainingAmount <= 0) {
            // Full refund - no fee
            $updateData['fee_amount'] = 0;
        } else {
            // Partial refund - recalculate fee on remaining amount
            $newFeeAmount = $remainingAmount * ($feePercentage / 100);
            $updateData['fee_amount'] = round($newFeeAmount, 2);
        }

        debugLog([
            'orderAmount' => $orderAmount,
            'feePercentage' => $feePercentage,
            'remainingAmount' => $remainingAmount,
            'newFeeAmount' => $updateData['fee_amount'],
        ], 'REFUND_ORDER_FEE_RECALC');

        // Determine order status: REFUNDED if any payments have been refunded (now or previously), VOIDED if only voided
        // Check if there are any refunded payments on this order (including previously refunded ones)
        $refundedPaymentsCount = $paymentHandler->count(['order' => $orderId, 'status' => 'REFUNDED']);

        if($refundedPaymentsCount > 0 || $totalRefunded > 0) {
            $updateData['status'] = 'REFUNDED';
            $successMessage = "Ordre refunderet succesfuldt.";
        } else {
            $updateData['status'] = 'VOIDED';
            $successMessage = "Ordre annulleret succesfuldt.";
        }

        debugLog(['updateData' => $updateData, 'newStatus' => $updateData['status']], 'REFUND_ORDER_UPDATE');
        $orderHandler->update($updateData, ['uid' => $orderId]);

        // Trigger order.refunded notification if at least one payment was refunded (not just voided)
        if($paymentsRefundedCount > 0) {
            // Resolve user from order
            $user = null;
            if(!isEmpty($order->uuid)) {
                $user = is_object($order->uuid) ? $order->uuid : Methods::users()->get($order->uuid);
            }

            // Resolve location from order
            $location = null;
            if(!isEmpty($order->location)) {
                $location = is_object($order->location) ? $order->location : Methods::locations()->get($order->location);
            }

            NotificationTriggers::orderRefunded(
                $order,
                $user,
                $totalRefunded,
                $paymentsRefundedCount,
                $voidedCount,
                'Refundering anmodet af forretningen',
                $orderOrg,
                $location
            );

            debugLog(['order_refunded_notification_triggered' => true, 'orderId' => $order->uid, 'paymentsRefundedCount' => $paymentsRefundedCount], 'REFUND_ORDER_NOTIFICATION');
        }

        if(!empty($refundErrors)) {
            debugLog(['result' => 'partial_success'], 'REFUND_ORDER_COMPLETE');
            Response()->setRedirect()->jsonSuccess("Delvis refundering gennemført. " . implode(' ', $refundErrors), [
                'total_refunded' => $totalRefunded,
                'voided_payments' => $voidedCount,
                'currency' => $order->currency,
                'errors' => $refundErrors,
            ]);
        }

        debugLog(['result' => 'success', 'totalRefunded' => $totalRefunded, 'voidedCount' => $voidedCount], 'REFUND_ORDER_COMPLETE');
        Response()->setRedirect()->jsonSuccess($successMessage, [
            'total_refunded' => $totalRefunded,
            'voided_payments' => $voidedCount,
            'currency' => $order->currency,
        ]);
    }


    /**
     * Refund a single payment
     * POST api/merchant/payments/{id}/refund
     */
    #[NoReturn] public static function refundPayment(array $args): void {
        $paymentId = $args['id'] ?? null;

        debugLog(['action' => 'refundPayment', 'paymentId' => $paymentId, 'args' => $args], 'REFUND_PAYMENT_START');

        if(isEmpty($paymentId)) {
            debugLog(['error' => 'Missing payment ID'], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betalings ID mangler.");
        }

        // Validate organisation
        if(isEmpty(Settings::$organisation)) {
            debugLog(['error' => 'No organisation'], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        // Check permissions
        if(!OrganisationPermissions::__oModify('orders', 'payments')) {
            debugLog(['error' => 'No permission'], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Du har ikke tilladelse til at refundere betalinger.");
        }

        $organisationUid = Settings::$organisation->organisation->uid;
        debugLog(['organisationUid' => $organisationUid], 'REFUND_PAYMENT_ORG');

        // Get the payment
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if(isEmpty($payment)) {
            debugLog(['error' => 'Payment not found', 'paymentId' => $paymentId], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betaling ikke fundet.");
        }

        // Get organisation UID from payment (could be object or string depending on handler)
        $paymentOrgUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;

        debugLog([
            'paymentId' => $payment->uid,
            'paymentStatus' => $payment->status,
            'paymentAmount' => $payment->amount,
            'paymentCurrency' => $payment->currency,
            'paymentPrid' => $payment->prid,
            'paymentOrganisation' => $paymentOrgUid,
        ], 'REFUND_PAYMENT_FOUND');

        // Verify the payment belongs to the current organisation
        if($paymentOrgUid !== $organisationUid) {
            debugLog([
                'error' => 'Organisation mismatch',
                'paymentOrg' => $paymentOrgUid,
                'userOrg' => $organisationUid,
            ], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Du har ikke adgang til denne betaling.");
        }

        // Check payment status - can only refund COMPLETED payments
        if($payment->status !== 'COMPLETED') {
            debugLog(['error' => 'Invalid payment status', 'status' => $payment->status], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Kun gennemførte betalinger kan refunderes (status: {$payment->status}).");
        }

        // Get transaction ID (prid)
        $transactionId = $payment->prid;
        if(isEmpty($transactionId)) {
            debugLog(['error' => 'Missing prid', 'paymentId' => $payment->uid], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betaling mangler transaktion ID.");
        }

        debugLog(['transactionId' => $transactionId], 'REFUND_PAYMENT_TRANSACTION');

        // Get the order to access merchant ID (could be object or string)
        $orderValue = $payment->order;
        if(isEmpty($orderValue)) {
            debugLog(['error' => 'Order not found for payment'], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Tilhørende ordre ikke fundet.");
        }

        // Resolve order if it's a string UID
        $order = is_object($orderValue) ? $orderValue : Methods::orders()->get($orderValue);
        if(isEmpty($order)) {
            debugLog(['error' => 'Could not resolve order', 'orderValue' => $orderValue], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Tilhørende ordre ikke fundet.");
        }

        debugLog([
            'orderId' => $order->uid,
            'orderStatus' => $order->status,
            'orderAmount' => $order->amount,
            'orderAmountRefunded' => $order->amount_refunded,
        ], 'REFUND_PAYMENT_ORDER');

        // Get organisation from order (could be object or string)
        $orderOrg = is_object($order->organisation) ? $order->organisation : Methods::organisations()->get($order->organisation);
        $merchantId = $orderOrg->merchant_prid ?? null;
        if(isEmpty($merchantId)) {
            debugLog(['error' => 'Missing merchant ID', 'organisationId' => $orderOrg->uid ?? 'N/A'], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Organisation mangler Viva merchant ID.");
        }

        debugLog(['merchantId' => $merchantId], 'REFUND_PAYMENT_MERCHANT');

        // Call Viva refund API (full refund)
        $viva = Methods::viva();

        debugLog([
            'calling' => 'viva->refundTransaction',
            'merchantId' => $merchantId,
            'transactionId' => $transactionId,
            'amount' => (float)$payment->amount,
            'currency' => $payment->currency,
        ], 'REFUND_PAYMENT_VIVA_CALL');

        $result = $viva->refundTransaction(
            $merchantId,
            $transactionId,
            (float)$payment->amount, // Amount is required by Viva
            null,
            $payment->currency
        );

        debugLog(['vivaResult' => $result], 'REFUND_PAYMENT_VIVA_RESULT');

        if(isEmpty($result) || !isset($result['TransactionId'])) {
            $errorMsg = $result['message'] ?? $result['Message'] ?? $result['ErrorText'] ?? 'Ukendt fejl fra betalingsudbyder';
            debugLog([
                'error' => 'Viva refund failed',
                'errorMsg' => $errorMsg,
                'fullResult' => $result,
            ], 'REFUND_PAYMENT_ERROR');
            Response()->jsonError("Refundering fejlede: {$errorMsg}");
        }

        debugLog([
            'success' => true,
            'refundTransactionId' => $result['TransactionId'],
        ], 'REFUND_PAYMENT_VIVA_SUCCESS');

        // Update payment status to REFUNDED
        debugLog(['updating' => 'payment status to REFUNDED', 'paymentId' => $paymentId], 'REFUND_PAYMENT_UPDATE');
        $paymentHandler->update(['status' => 'REFUNDED'], ['uid' => $paymentId]);

        // Update order amount_refunded and recalculate fee_amount (but NOT status - single payment refund doesn't change order status)
        $orderHandler = Methods::orders()->excludeForeignKeys();
        $orderData = $orderHandler->get($order->uid);
        $newAmountRefunded = (float)$orderData->amount_refunded + (float)$payment->amount;

        // Recalculate fee_amount based on remaining amount after refund
        $orderAmount = (float)$orderData->amount;
        $feePercentage = (float)($orderData->fee ?? 0);
        $remainingAmount = $orderAmount - $newAmountRefunded;

        $updateData = ['amount_refunded' => $newAmountRefunded];

        if($remainingAmount <= 0) {
            // Full refund - no fee
            $updateData['fee_amount'] = 0;
        } else {
            // Partial refund - recalculate fee on remaining amount
            $newFeeAmount = $remainingAmount * ($feePercentage / 100);
            $updateData['fee_amount'] = round($newFeeAmount, 2);
        }

        // If all payments are now refunded (amount == amount_refunded), set order status to REFUNDED
        if($newAmountRefunded >= $orderAmount) {
            $updateData['status'] = 'REFUNDED';
            debugLog(['order_fully_refunded' => true, 'newAmountRefunded' => $newAmountRefunded, 'orderAmount' => $orderAmount], 'REFUND_PAYMENT_ORDER_FULLY_REFUNDED');
        }

        debugLog([
            'updating' => 'order amount_refunded and fee_amount',
            'orderId' => $order->uid,
            'previousAmountRefunded' => $orderData->amount_refunded,
            'paymentAmount' => $payment->amount,
            'newAmountRefunded' => $newAmountRefunded,
            'orderAmount' => $orderAmount,
            'feePercentage' => $feePercentage,
            'remainingAmount' => $remainingAmount,
            'newFeeAmount' => $updateData['fee_amount'],
            'newStatus' => $updateData['status'] ?? 'unchanged',
        ], 'REFUND_PAYMENT_ORDER_UPDATE');

        $orderHandler->update($updateData, ['uid' => $order->uid]);

        // Trigger refund notification
        // Resolve user from order
        $user = null;
        if(!isEmpty($order->uuid)) {
            $user = is_object($order->uuid) ? $order->uuid : Methods::users()->get($order->uuid);
        }

        // Resolve location from order
        $location = null;
        if(!isEmpty($order->location)) {
            $location = is_object($order->location) ? $order->location : Methods::locations()->get($order->location);
        }

        // Trigger notification (use $orderOrg which is already resolved)
        NotificationTriggers::paymentRefunded(
            $payment,
            $user,
            $order,
            (float)$payment->amount,
            'Refundering anmodet af forretningen',
            $orderOrg,
            $location
        );

        debugLog(['notification_triggered' => true, 'paymentId' => $payment->uid], 'REFUND_PAYMENT_NOTIFICATION');

        debugLog([
            'result' => 'success',
            'amountRefunded' => (float)$payment->amount,
            'currency' => $payment->currency,
            'transactionId' => $result['TransactionId'],
        ], 'REFUND_PAYMENT_COMPLETE');

        Response()->setRedirect()->jsonSuccess("Betaling refunderet succesfuldt.", [
            'amount_refunded' => (float)$payment->amount,
            'currency' => $payment->currency,
            'transaction_id' => $result['TransactionId'],
        ]);
    }

    /**
     * Download order contract PDF
     * Merchant version - verifies organisation ownership and location permissions
     */
    #[NoReturn] public static function downloadContract(array $args): void {
        $orderUid = $args['uid'] ?? null;

        // 1. Verify organisation membership
        if (isEmpty(Settings::$organisation)) {
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        if (isEmpty($orderUid)) {
            Response()->jsonError("Ordre ID mangler.");
        }

        // Get the order
        $orderHandler = Methods::orders();
        $order = $orderHandler->excludeForeignKeys()->get($orderUid);

        if (isEmpty($order)) {
            Response()->jsonError("Ordre ikke fundet.");
        }

        // 2. Verify order belongs to user's organisation
        if ($order->organisation !== Settings::$organisation->organisation->uid) {
            Response()->jsonError("Du har ikke adgang til denne ordre.");
        }

        // 3. Check location-level permissions
        $location = Methods::locations()->excludeForeignKeys()->get($order->location);
        if (!\classes\app\LocationPermissions::__oRead($location, 'orders')) {
            Response()->jsonError("Du har ikke tilladelse til at se ordrer for denne lokation.");
        }

        // Only allow contracts for BNPL orders
        if (!in_array($order->payment_plan, ['installments', 'pushed'])) {
            Response()->jsonError("Denne ordre har ingen kontrakt.");
        }

        // Get or generate the contract PDF
        $documentHandler = Methods::contractDocuments();
        $pdfContent = $documentHandler->getContract($order);

        if (!$pdfContent) {
            // Generate the contract PDF
            $pdf = new \classes\documents\OrderContractPdf($order);
            $pdfContent = $pdf->generatePdfString();

            // Save it for future use
            $documentHandler->saveContract($order, $pdfContent);
        }

        // Stream the PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="kontrakt_' . $order->uid . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
        exit;
    }

    /**
     * Download rykker PDF for an order's payment
     * Merchant version - verifies organisation ownership and location permissions
     */
    #[NoReturn] public static function downloadRykker(array $args): void {
        $orderUid = $args['uid'] ?? null;
        $level = (int)($args['level'] ?? 0);

        // 1. Verify organisation membership
        if (isEmpty(Settings::$organisation)) {
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        if (isEmpty($orderUid)) {
            Response()->jsonError("Ordre ID mangler.");
        }

        if ($level < 1 || $level > 3) {
            Response()->jsonError("Ugyldigt rykker niveau.");
        }

        // Get the order
        $orderHandler = Methods::orders();
        $order = $orderHandler->excludeForeignKeys()->get($orderUid);

        if (isEmpty($order)) {
            Response()->jsonError("Ordre ikke fundet.");
        }

        // 2. Verify order belongs to user's organisation
        if ($order->organisation !== Settings::$organisation->organisation->uid) {
            Response()->jsonError("Du har ikke adgang til denne ordre.");
        }

        // 3. Check location-level permissions
        $location = Methods::locations()->excludeForeignKeys()->get($order->location);
        if (!\classes\app\LocationPermissions::__oRead($location, 'orders')) {
            Response()->jsonError("Du har ikke tilladelse til at se ordrer for denne lokation.");
        }

        // Get payments for this order to find one with rykker at this level
        $paymentHandler = Methods::payments();
        $payments = $paymentHandler->excludeForeignKeys()->getByX(['order' => $orderUid]);

        // Find a payment that has this rykker level
        $targetPayment = null;
        foreach ($payments->list() as $payment) {
            if ((int)$payment->rykker_level >= $level) {
                $targetPayment = $payment;
                break;
            }
        }

        if (!$targetPayment) {
            Response()->jsonError("Ingen rykker fundet paa dette niveau for denne ordre.");
        }

        // Get or generate the rykker PDF
        $documentHandler = Methods::contractDocuments();
        $pdfContent = $documentHandler->getRykker($targetPayment, $level);

        if (!$pdfContent) {
            // Generate the rykker PDF
            $pdf = new \classes\documents\RykkerPdf($targetPayment, $level);
            $pdfContent = $pdf->generatePdfString();

            // Save it for future use
            $documentHandler->saveRykker($targetPayment, $level, $pdfContent);
        }

        // Stream the PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="rykker' . $level . '_' . $orderUid . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
        exit;
    }

    /**
     * Download rykker PDF by payment UID
     * Merchant version - verifies organisation ownership and location permissions
     */
    #[NoReturn] public static function downloadPaymentRykker(array $args): void {
        $paymentUid = $args['uid'] ?? null;
        $level = (int)($args['level'] ?? 0);

        // 1. Verify organisation membership
        if (isEmpty(Settings::$organisation)) {
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        if (isEmpty($paymentUid)) {
            Response()->jsonError("Betalings ID mangler.");
        }

        if ($level < 1 || $level > 3) {
            Response()->jsonError("Ugyldigt rykker niveau.");
        }

        // Get the payment
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->excludeForeignKeys()->get($paymentUid);

        if (isEmpty($payment)) {
            Response()->jsonError("Betaling ikke fundet.");
        }

        // Verify payment has this rykker level
        if ((int)$payment->rykker_level < $level) {
            Response()->jsonError("Denne betaling har ikke rykker niveau {$level}.");
        }

        // Get the order to check permissions
        $order = Methods::orders()->excludeForeignKeys()->get($payment->order);

        if (isEmpty($order)) {
            Response()->jsonError("Ordre ikke fundet.");
        }

        // 2. Verify order belongs to user's organisation
        if ($order->organisation !== Settings::$organisation->organisation->uid) {
            Response()->jsonError("Du har ikke adgang til denne betaling.");
        }

        // 3. Check location-level permissions
        $location = Methods::locations()->excludeForeignKeys()->get($order->location);
        if (!\classes\app\LocationPermissions::__oRead($location, 'orders')) {
            Response()->jsonError("Du har ikke tilladelse til at se ordrer for denne lokation.");
        }

        // Get or generate the rykker PDF
        $documentHandler = Methods::contractDocuments();
        $pdfContent = $documentHandler->getRykker($payment, $level);

        if (!$pdfContent) {
            // Generate the rykker PDF
            $pdf = new \classes\documents\RykkerPdf($payment, $level);
            $pdfContent = $pdf->generatePdfString();

            // Save it for future use
            $documentHandler->saveRykker($payment, $level, $pdfContent);
        }

        // Stream the PDF to browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="rykker' . $level . '_' . $paymentUid . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
        exit;
    }
}
