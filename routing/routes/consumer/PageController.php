<?php
namespace routing\routes\consumer;

use classes\data\Calculate;
use classes\Methods;
use features\Settings;

class PageController {

    public static function dashboard(array $args): mixed  {
        $user = Methods::users()->get(__uuid());
        $paymentsHandler = Methods::payments();
        $orderHandler = Methods::orders();

        // Get all orders for the user
        $orders = $orderHandler->getByX([
            'uuid' => __uuid(),
            'status' => 'COMPLETED'
        ], ['uid', 'amount', 'created_at', 'location', 'payment_plan']);

        // Calculate total spent
        $totalSpent = $orders->reduce(function ($carry, $item) {
            return $carry + $item['amount'];
        }, 0);

        // Get order count
        $orderCount = $orders->count();

        // Get outstanding payments (SCHEDULED + PAST_DUE)
        $outstandingPayments = $paymentsHandler->getByX([
            'uuid' => __uuid(),
            'status' => ['SCHEDULED', 'PAST_DUE']
        ], ['amount', 'due_date', 'status']);

        $totalOutstanding = $outstandingPayments->reduce(function ($carry, $item) {
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
            $activities[] = [
                'type' => 'order',
                'date' => $order->created_at,
                'amount' => $order->amount,
                'data' => $order,
                'currency' => $order->currency,
            ];
        }
        foreach ($recentPaymentsList->list() as $payment) {
            $activities[] = [
                'type' => 'payment',
                'date' => $payment->paid_at,
                'amount' => $payment->amount,
                'data' => $payment,
                'currency' => $payment->currency,
            ];
        }

        // Sort by date descending and take top 3
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        $activities = array_slice($activities, 0, 3);

        return Views("CONSUMER_DASHBOARD", compact(
            'user',
            'totalSpent',
            'orderCount',
            'totalOutstanding',
            'availableCredit',
            'activities'
        ));
    }

    public static function orders(array $args): mixed  {
        $orderHandler = Methods::orders();

        $orders = $orderHandler->queryBuilder()
            ->whereList(['uuid' => __uuid(), 'status' => 'COMPLETED'])
            ->order('created_at', 'DESC');
        $ordersList = $orderHandler->queryGetAll($orders);

        return Views("CONSUMER_ORDERS", compact('ordersList'));
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

        return Views("CONSUMER_SETTINGS", compact('user', 'authLocal'));
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
        ], ['uid', 'amount', 'status', 'due_date', 'paid_at', 'created_at']);

        return Views("CONSUMER_ORDER_DETAIL", compact('order', 'payments'));
    }
}
