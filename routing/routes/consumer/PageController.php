<?php
namespace routing\routes\consumer;

use classes\data\Calculate;
use classes\enumerations\Links;
use classes\Methods;
use classes\payments\CardValidationService;
use features\Settings;

class PageController {

    public static function dashboard(array $args): mixed  {
        $user = Methods::users()->get(__uuid());
        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();
        $locationHandler = Methods::locations();

        // Get all orders for the user
        $orders = $orderHandler->getByX([
            'uuid' => __uuid(),
            'status' => 'COMPLETED'
        ]);

        // Calculate total spent (subtract refunds)
        $totalSpent = $orders->reduce(function ($carry, $item) {
            return $carry + orderAmount((object)$item);
        }, 0);

        // Get order count
        $orderCount = $orders->count();

        // Get upcoming payments (all non-completed statuses)
        $upcomingStatuses = ['PENDING', 'SCHEDULED', 'PAST_DUE', 'FAILED', 'DRAFT'];
        $upcomingPaymentsAll = $paymentsHandler->getByX([
            'uuid' => __uuid(),
            'status' => $upcomingStatuses
        ], ['amount', 'due_date', 'status']);

        $totalUpcoming = $upcomingPaymentsAll->reduce(function ($carry, $item) {
            return $carry + $item['amount'];
        }, 0);

        // Get past due payments specifically
        $pastDuePayments = $paymentsHandler->getByX([
            'uuid' => __uuid(),
            'status' => 'PAST_DUE'
        ], ['amount', 'due_date']);

        $pastDueCount = $pastDuePayments->count();
        $pastDueTotal = $pastDuePayments->reduce(function ($carry, $item) {
            return $carry + $item['amount'];
        }, 0);

        // Get BNPL available credit
        $bnplLimit = $paymentsHandler->getBnplLimit(__uuid());
        $availableCredit = $bnplLimit['available'];

        // Get recent activity (last 3 orders and payments)
        $recentOrders = $orderHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'COMPLETED'])
            ->order('created_at', 'DESC')
            ->limit(3);
        $recentOrdersList = $orderHandler->queryGetAll($recentOrders);

        $recentPayments = $paymentsHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'COMPLETED'])
            ->order('paid_at', 'DESC')
            ->limit(3);
        $recentPaymentsList = $paymentsHandler->queryGetAll($recentPayments);

        // Merge and sort activities by date
        $activities = [];
        foreach ($recentOrdersList->list() as $order) {
            // Get location name
            $locationName = 'Ukendt butik';
            $locationUid = null;
            if(is_object($order->location)) {
                $locationName = $order->location->name ?? $locationName;
                $locationUid = $order->location->uid;
            } elseif(!isEmpty($order->location)) {
                $location = $locationHandler->get($order->location);
                $locationName = $location->name ?? $locationName;
                $locationUid = $order->location;
            }

            $activities[] = [
                'type' => 'order',
                'date' => $order->created_at,
                'amount' => orderAmount($order),
                'data' => $order,
                'currency' => $order->currency,
                'location_name' => $locationName,
                'location_uid' => $locationUid,
                'uid' => $order->uid,
            ];
        }
        foreach ($recentPaymentsList->list() as $payment) {
            // Get location name from payment's order
            $locationName = 'Ukendt butik';
            $locationUid = null;
            $order = $payment->order;
            if(is_object($order) && is_object($order->location)) {
                $locationName = $order->location->name ?? $locationName;
                $locationUid = $order->location->uid;
            } elseif(is_object($order) && !isEmpty($order->location)) {
                $location = $locationHandler->get($order->location);
                $locationName = $location->name ?? $locationName;
                $locationUid = $order->location;
            }

            $activities[] = [
                'type' => 'payment',
                'date' => $payment->paid_at,
                'amount' => $payment->amount,
                'data' => $payment,
                'currency' => $payment->currency,
                'location_name' => $locationName,
                'location_uid' => $locationUid,
                'uid' => $payment->uid,
            ];
        }

        // Sort by date descending and take top 3
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        $activities = array_slice($activities, 0, 3);

        // Build purchases by location data for chart and locations list
        $locationPurchases = [];
        foreach($orders->list() as $order) {
            $locationUid = is_object($order->location) ? $order->location->uid : $order->location;
            $locationName = is_object($order->location) ? ($order->location->name ?? 'Ukendt') : null;

            if(!isset($locationPurchases[$locationUid])) {
                // Fetch location if we don't have the name yet
                if($locationName === null && !isEmpty($locationUid)) {
                    $loc = $locationHandler->get($locationUid);
                    $locationName = $loc->name ?? 'Ukendt butik';
                }
                $locationPurchases[$locationUid] = [
                    'uid' => $locationUid,
                    'name' => $locationName ?? 'Ukendt butik',
                    'total_spent' => 0,
                    'order_count' => 0,
                ];
            }
            $locationPurchases[$locationUid]['total_spent'] += orderAmount($order);
            $locationPurchases[$locationUid]['order_count']++;
        }

        // Sort locations by total spent descending
        usort($locationPurchases, function($a, $b) {
            return $b['total_spent'] <=> $a['total_spent'];
        });

        // Get upcoming payments (next 3 scheduled)
        $upcomingPaymentsQuery = $paymentsHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'SCHEDULED'])
            ->order('due_date', 'ASC')
            ->limit(3);
        $upcomingPaymentsList = $paymentsHandler->queryGetAll($upcomingPaymentsQuery);

        // Enrich upcoming payments with location name
        $upcomingPayments = [];
        foreach($upcomingPaymentsList->list() as $payment) {
            $locationName = 'Ukendt butik';
            $order = $payment->order;
            if(is_object($order) && is_object($order->location)) {
                $locationName = $order->location->name ?? $locationName;
            } elseif(is_object($order) && !isEmpty($order->location)) {
                $loc = $locationHandler->get($order->location);
                $locationName = $loc->name ?? $locationName;
            }

            $upcomingPayments[] = [
                'uid' => $payment->uid,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'due_date' => $payment->due_date,
                'location_name' => $locationName,
                'installment_number' => $payment->installment_number ?? null,
                'total_installments' => $payment->total_installments ?? null,
            ];
        }

        return Views("CONSUMER_DASHBOARD", compact(
            'user',
            'totalSpent',
            'orderCount',
            'totalUpcoming',
            'availableCredit',
            'bnplLimit',
            'activities',
            'locationPurchases',
            'upcomingPayments',
            'pastDueCount',
            'pastDueTotal'
        ));
    }

    public static function orders(array $args): mixed  {
        // Check if user has any orders that are not fully paid
        $orderHandler = Methods::orders();
        $paymentsHandler = Methods::payments();

        // Get all user's orders
        $allOrders = $orderHandler->getByX(['uuid' => __uuid()]);
        $orderUids = $allOrders->map(fn($o) => $o['uid'])->toArray();

        $hasNotFullyPaid = false;

        if(!empty($orderUids)) {
            // Get paid amounts for each order
            $paidAmounts = [];
            $payments = $paymentsHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $orderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }

            // Check if any order is not fully paid (subtract refunds)
            foreach($allOrders->list() as $order) {
                $paidAmount = $paidAmounts[$order->uid] ?? 0;
                $netAmount = orderAmount($order);
                if($paidAmount < $netAmount) {
                    $hasNotFullyPaid = true;
                    break;
                }
            }
        }

        return Views("CONSUMER_ORDERS", compact('hasNotFullyPaid'));
    }

    public static function payments(array $args): mixed  {
        // Check if user has any past due payments to determine default tab
        $paymentsHandler = Methods::payments();
        $hasPastDue = $paymentsHandler->exists(['uuid' => __uuid(), 'status' => 'PAST_DUE']);

        // Check if user has any scheduled payments for card change button
        $hasScheduledPayments = $paymentsHandler->exists([
            'uuid' => __uuid(),
            'status' => ['PENDING', 'SCHEDULED', 'PAST_DUE']
        ]);

        return Views("CONSUMER_PAYMENTS", compact('hasPastDue', 'hasScheduledPayments'));
    }

    public static function changeCard(array $args): mixed  {
        // Check if user has any changeable payments
        $paymentsHandler = Methods::payments();
        $changeableStatuses = ['PENDING', 'SCHEDULED', 'PAST_DUE', 'FAILED', 'DRAFT'];

        $hasChangeablePayments = $paymentsHandler->queryBuilder()
            ->where('uuid', __uuid())
            ->where('status', $changeableStatuses)
            ->count() > 0;

        if (!$hasChangeablePayments) {
            // Redirect to payments page if no changeable payments
            Response()->redirect(__url(Links::$consumer->payments));
        }

        return Views("CONSUMER_CHANGE_CARD", []);
    }

    public static function receipts(array $args): mixed  {
        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        $payments = $paymentsHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'COMPLETED'])
            ->order('paid_at', 'DESC');
        $paymentsList = $paymentsHandler->queryGetAll($payments);

        // Enrich with order data
        foreach ($paymentsList->list() as $n => $payment) {
            if(!is_object($payment->order)) {
                $order = $orderHandler->get($payment->order);
                $payment->order = $order;
                $paymentsList->updateItem($n, $payment);
            }
        }

        return Views("CONSUMER_RECEIPTS", compact('paymentsList'));
    }

    public static function upcomingPayments(array $args): mixed  {
        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        $payments = $paymentsHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'SCHEDULED'])
            ->order('due_date', 'ASC');
        $paymentsList = $paymentsHandler->queryGetAll($payments);

        // Enrich with order data
        foreach ($paymentsList->list() as $n => $payment) {
            if(!is_object($payment->order)) {
                $order = $orderHandler->get($payment->order);
                $payment->order = $order;
                $paymentsList->updateItem($n, $payment);
            }
        }

        return Views("CONSUMER_UPCOMING_PAYMENTS", compact('paymentsList'));
    }

    public static function outstandingPayments(array $args): mixed  {
        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        $payments = $paymentsHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'PAST_DUE'])
            ->order('due_date', 'ASC');
        $paymentsList = $paymentsHandler->queryGetAll($payments);

        // Enrich with order data
        foreach ($paymentsList->list() as $n => $payment) {
            if(!is_object($payment->order)) {
                $order = $orderHandler->get($payment->order);
                $payment->order = $order;
                $paymentsList->updateItem($n, $payment);
            }
        }

        return Views("CONSUMER_OUTSTANDING_PAYMENTS", compact('paymentsList'));
    }

    public static function settings(array $args): mixed  {
        $user = Methods::users()->get(__uuid());

        // Get local auth if exists
        $authLocal = \Database\model\AuthLocal::where('user', __uuid())->first();

        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        return Views("CONSUMER_SETTINGS", compact('user', 'authLocal', 'worldCountries'));
    }

    public static function orderDetail(array $args): mixed  {
        $orderHandler = Methods::orders();
        $paymentsHandler = Methods::payments();
        $locationHandler = Methods::locations();


        $orderId = $args['id'] ?? null;
        if(empty($orderId)) return null;

        $order = $orderHandler->get($orderId);

        if(empty($order) || $order->uuid->uid !== __uuid()) return null;

        // Get location details
        if(!is_object($order->location)) {
            $location = $locationHandler->get($order->location);
            $order->location = $location;
        }

        // Get all payments for this order
        $payments = $paymentsHandler->getByX([
            'order' => $orderId
        ], ['uid', 'amount', 'status', 'due_date', 'paid_at', 'created_at', 'rykker_fee']);

        return Views("CONSUMER_ORDER_DETAIL", compact('order', 'payments'));
    }

    public static function paymentDetail(array $args): mixed {
        $paymentId = $args['id'] ?? null;
        if(empty($paymentId)) return null;

        $paymentHandler = Methods::payments();
        $locationHandler = Methods::locations();

        $payment = $paymentHandler->get($paymentId);

        if(isEmpty($payment)) return null;

        // Verify the payment belongs to the current user
        $paymentUserUid = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
        if($paymentUserUid !== __uuid()) {
            return null;
        }

        // Get the order associated with this payment
        $order = $payment->order;
        if(!isEmpty($order) && !is_object($order)) {
            $orderHandler = Methods::orders();
            $order = $orderHandler->get($order);
        }

        // Get location details
        if(!isEmpty($payment->location) && !is_object($payment->location)) {
            $location = $locationHandler->get($payment->location);
            $payment->location = $location;
        }

        // Get all payments for the same order (for installment context)
        $orderPayments = null;
        if(!isEmpty($order) && is_object($order)) {
            $orderPayments = $paymentHandler->getByXOrderBy('installment_number', 'ASC', ['order' => $order->uid]);
        }

        return Views("CONSUMER_PAYMENT_DETAIL", compact('payment', 'order', 'orderPayments'));
    }

    public static function locationDetail(array $args): mixed {
        $locationId = $args['id'] ?? null;
        if(empty($locationId)) return null;

        $locationHandler = Methods::locations();
        $orderHandler = Methods::orders();

        $location = $locationHandler->get($locationId);

        if(isEmpty($location)) return null;

        // Get all orders from this customer at this location
        $orders = $orderHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'location' => $locationId, 'status' => 'COMPLETED'])
            ->order('created_at', 'DESC');
        $ordersList = $orderHandler->queryGetAll($orders);

        // Calculate totals (subtract refunds)
        $totalSpent = 0;
        $orderCount = $ordersList->count();
        foreach($ordersList->list() as $order) {
            $totalSpent += orderAmount($order);
        }

        // Get location's public page URL
        $publicPageUrl = null;
        if(!isEmpty($location->slug)) {
            $publicPageUrl = __url("merchant/{$location->slug}");
        }

        // Get published location page for hero image and logo
        $locationPage = \Database\model\LocationPages::where('location', $locationId)
            ->where('state', 'PUBLISHED')
            ->first();

        return Views("CONSUMER_LOCATION_DETAIL", compact('location', 'ordersList', 'totalSpent', 'orderCount', 'publicPageUrl', 'locationPage'));
    }


    public static function support(array $args): mixed {
        $ticketHandler = Methods::supportTickets();
        $replyHandler = Methods::supportTicketReplies();

        // Get user's tickets
        $tickets = $ticketHandler->getByUser(__uuid());

        // Get counts
        $openCount = $tickets->filter(fn($t) => $t['status'] === 'open')->count();
        $closedCount = $tickets->filter(fn($t) => $t['status'] === 'closed')->count();

        // Get replies for each ticket
        $ticketReplies = new \stdClass();
        foreach ($tickets->list() as $ticket) {
            $ticketUid = $ticket->uid;
            $ticketReplies->$ticketUid = $replyHandler->getByTicket($ticketUid);
        }

        // Consumer categories
        $categories = [
            'Betaling & Rater',
            'Ordrer',
            'Min konto',
            'Teknisk problem',
            'Andet'
        ];

        return Views("CONSUMER_SUPPORT", compact('tickets', 'openCount', 'closedCount', 'categories', 'ticketReplies'));
    }

    /**
     * Handle Viva callback after card validation for card change
     *
     * GET /consumer/card-change/callback?s={orderCode}&t={transactionId}&...
     *
     * Uses the card_change order stored in DB instead of session for reliability
     */
    public static function cardChangeCallback(array $args): mixed {
        $orderCode = $args['s'] ?? null;

        $retryUrl = __url(Links::$consumer->changeCard);

        if (isEmpty($orderCode)) {
            errorLog(['args' => $args], 'card-change-callback-no-order-code');
            return Views("CONSUMER_CARD_CHANGE_FAILED", [
                'error' => 'Mangler ordre kode',
                'retryUrl' => $retryUrl,
            ]);
        }

        // Find the card_change order by prid (orderCode)
        $orderHandler = Methods::orders();
        $order = $orderHandler->getFirst(['prid' => $orderCode, 'type' => 'card_change']);

        if (isEmpty($order)) {
            errorLog(['orderCode' => $orderCode], 'card-change-callback-order-not-found');
            return Views("CONSUMER_CARD_CHANGE_FAILED", [
                'error' => 'Kortskift ordre ikke fundet',
                'retryUrl' => $retryUrl,
            ]);
        }

        // Verify order belongs to current user
        $userId = __uuid();
        $orderUserId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
        if ($orderUserId !== $userId) {
            errorLog(['orderUserId' => $orderUserId, 'currentUserId' => $userId], 'card-change-callback-user-mismatch');
            return Views("CONSUMER_CARD_CHANGE_FAILED", [
                'error' => 'Denne ordre tilhører ikke dig',
                'retryUrl' => $retryUrl,
            ]);
        }

        // Get metadata from billing_details (stored as object)
        $billingDetails = $order->billing_details ?? (object)[];
        $scope = $billingDetails->scope ?? 'payment_method';
        $oldPaymentMethodUid = $billingDetails->payment_method_uid ?? null;
        $organisationUidForNoCard = $billingDetails->organisation_uid ?? null;

        // Get organisation for merchant_prid
        $organisationUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
        $organisation = Methods::organisations()->get($organisationUid);

        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            return Views("CONSUMER_CARD_CHANGE_FAILED", [
                'error' => 'Butik ikke fundet',
                'retryUrl' => $retryUrl,
            ]);
        }

        $isTest = (bool)($order->test ?? false);
        $currency = $order->currency ?? 'DKK';

        // Process the validation payment (verify + refund + get transaction ID)
        $validationResult = CardValidationService::processValidationPayment(
            $organisation->merchant_prid,
            $orderCode,
            $currency,
            $isTest
        );

        debugLog([
            'orderUid' => $order->uid,
            'orderCode' => $orderCode,
            'scope' => $scope,
            'oldPaymentMethodUid' => $oldPaymentMethodUid,
            'validationResult' => $validationResult,
        ], 'CARD_CHANGE_CALLBACK_VALIDATION');

        if (!$validationResult['success']) {
            // Delete the failed card_change order
            $orderHandler->deleteCardChangeOrder($order->uid);

            return Views("CONSUMER_CARD_CHANGE_FAILED", [
                'error' => $validationResult['error'] ?? 'Kortvalidering fejlede',
                'retryUrl' => $retryUrl,
            ]);
        }

        $newTransactionId = $validationResult['transaction_id'];

        // Get card details from Viva and create/find payment method
        $viva = Methods::viva();
        if ($isTest) {
            $viva->sandbox();
        } else {
            $viva->live();
        }

        $paymentInfo = $viva->getPayment($organisation->merchant_prid, $newTransactionId);
        $newPaymentMethodUid = null;

        if (!isEmpty($paymentInfo)) {
            $paymentMethod = Methods::paymentMethods()->createFromVivaTransaction(
                $userId,
                $paymentInfo,
                $isTest
            );
            $newPaymentMethodUid = $paymentMethod?->uid;
        }

        // Update payments with new transaction ID and payment method
        $paymentsHandler = Methods::payments();
        $changeableStatuses = ['PENDING', 'SCHEDULED', 'PAST_DUE', 'FAILED', 'DRAFT'];
        $updateCount = 0;

        $query = $paymentsHandler->queryBuilder()
            ->where('uuid', $userId)
            ->where('status', $changeableStatuses);

        if (isEmpty($oldPaymentMethodUid)) {
            // No-card group - filter by null payment_method and organisation
            $query->whereNull('payment_method');
            if (!isEmpty($organisationUidForNoCard)) {
                $query->where('organisation', $organisationUidForNoCard);
            } elseif (!isEmpty($organisationUid)) {
                $query->where('organisation', $organisationUid);
            }
        } else {
            // Specific payment method
            $query->where('payment_method', $oldPaymentMethodUid);
        }

        $paymentsToUpdate = $query->all();

        foreach ($paymentsToUpdate->list() as $payment) {
            $paymentsHandler->excludeForeignKeys()->update(
                [
                    'initial_transaction_id' => $newTransactionId,
                    'payment_method' => $newPaymentMethodUid,
                ],
                ['uid' => $payment->uid]
            );
            $updateCount++;
        }

        debugLog([
            'scope' => $scope,
            'old_payment_method_uid' => $oldPaymentMethodUid,
            'new_payment_method_uid' => $newPaymentMethodUid,
            'user_uid' => $userId,
            'updateCount' => $updateCount,
            'newTransactionId' => $newTransactionId,
        ], 'CARD_CHANGE_SUCCESS');

        // Delete the temporary card_change order
        $orderHandler->deleteCardChangeOrder($order->uid);

        return Views("CONSUMER_CARD_CHANGE_SUCCESS", [
            'message' => "Dit kort er opdateret for {$updateCount} betalinger",
            'updateCount' => $updateCount,
            'returnUrl' => $retryUrl,
        ]);
    }
}
