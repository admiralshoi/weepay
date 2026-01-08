<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\payments\PaymentReceipt;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class PaymentsApiController {

    #[NoReturn] public static function getPayments(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterType = isset($args["filter_type"]) && !empty($args["filter_type"]) ? trim($args["filter_type"]) : 'completed'; // 'completed' or 'upcoming'
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "paid_at";
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
            Response()->jsonError("Du har ikke tilladelse til at se betalinger.");

        $organisationUid = Settings::$organisation->organisation->uid;

        // Build base query
        $paymentHandler = Methods::payments();
        $query = $paymentHandler->queryBuilder()
            ->where('organisation', $organisationUid);

        // Apply scoped location filter if applicable
        $locationIds = Methods::locations()->userLocationPredicate();
        if(!empty($locationIds)) {
            $query->where('location', $locationIds);
        }

        // Apply type filter (completed vs upcoming vs past_due)
        if($filterType === 'completed') {
            $query->where('status', 'COMPLETED');
            // Default sort by paid_at for completed
            if($sortColumn === 'due_date') $sortColumn = 'paid_at';
        } elseif($filterType === 'past_due') {
            $query->where('status', 'PAST_DUE');
            // Default sort by due_date for past_due
            if($sortColumn === 'paid_at') $sortColumn = 'due_date';
        } else {
            // Upcoming: PENDING, SCHEDULED (exclude PAST_DUE as it has its own tab)
            $upcomingStatuses = ['PENDING', 'SCHEDULED'];
            if(!empty($filterStatus) && in_array($filterStatus, $upcomingStatuses)) {
                $query->where('status', $filterStatus);
            } else {
                $query->where('status', $upcomingStatuses);
            }
            // Default sort by due_date for upcoming
            if($sortColumn === 'paid_at') $sortColumn = 'due_date';
        }

        // Apply date range filter
        $dateColumn = $filterType === 'completed' ? 'paid_at' : 'due_date';
        if(!empty($startDate)) {
            $query->where($dateColumn, '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where($dateColumn, '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter - search in payment UID, order UID, or customer name/email
        if(!empty($search)) {
            // Get matching user UIDs
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            // Get matching order UIDs
            $orderHandler = Methods::orders();
            $matchingOrderUids = $orderHandler->queryBuilder()
                ->whereLike('uid', $search)
                ->pluck('uid');

            // Search in payments
            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingUserUids)) {
                $query->where('uuid', $matchingUserUids);
            }
            if(!empty($matchingOrderUids)) {
                $query->where('order', $matchingOrderUids);
            }
            $query->endGroup();
        }

        // Get total count
        $totalCount = $query->count();

        if($totalCount === 0) {
            Response()->jsonSuccess("", [
                "payments" => [],
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
            'date' => $filterType === 'completed' ? 'paid_at' : 'due_date',
            'paid_at' => 'paid_at',
            'due_date' => 'due_date',
            'amount' => 'amount',
            'status' => 'status',
            'installment' => 'installment_number',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Fetch payments
        $payments = $paymentHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
            'SCHEDULED' => ['label' => 'Planlagt', 'class' => 'mute-box'],
            'PAST_DUE' => ['label' => 'Forsinket', 'class' => 'danger-box'],
            'FAILED' => ['label' => 'Fejlet', 'class' => 'danger-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'mute-box'],
            'REFUNDED' => ['label' => 'Refunderet', 'class' => 'warning-box'],
        ];

        // Transform payments for frontend
        $transformedPayments = [];
        foreach($payments->list() as $payment) {
            // Get order and customer info - resolved via foreign keys
            $order = $payment->order;
            $orderUid = is_object($order) ? $order->uid : null;

            if(is_string($payment->uuid)) $payment->uuid = Methods::users()->get($payment->uuid);

            $customerName = 'Ukendt';
            $customerEmail = '';
            $customerUid = null;
            if(!isEmpty($payment->uuid) && is_object($payment->uuid)) {
                $customerName = $payment->uuid->full_name ?? 'Ukendt';
                $customerEmail = $payment->uuid->email ?? '';
                $customerUid = $payment->uuid->uid;
            }

            $statusInfo = $statusMap[$payment->status] ?? ['label' => $payment->status, 'class' => 'mute-box'];

            $transformedPayments[] = [
                'uid' => $payment->uid,
                'order_uid' => $orderUid,
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_uid' => $customerUid,
                'amount' => (float)$payment->amount,
                'currency' => $payment->currency,
                'installment_number' => $payment->installment_number,
                'due_date' => date("d/m-Y", strtotime($payment->due_date)),
                'paid_at' => !isEmpty($payment->paid_at) ? date("d/m-Y H:i", strtotime($payment->paid_at)) : null,
                'status' => $payment->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$merchant->paymentDetail($payment->uid)),
                'order_url' => $orderUid ? __url(Links::$merchant->orderDetail($orderUid)) : null,
                'customer_url' => $customerUid ? __url(Links::$merchant->customerDetail($customerUid)) : null,
            ];
        }

        Response()->jsonSuccess("", [
            "payments" => $transformedPayments,
            "pagination" => [
                "page" => $page,
                "perPage" => $perPage,
                "total" => $totalCount,
                "totalPages" => $totalPages,
            ],
        ]);
    }

    /**
     * Download payment receipt as PDF
     */
    #[NoReturn] public static function downloadReceipt(array $args): void {
        $paymentId = $args['id'] ?? null;

        if(isEmpty($paymentId)) {
            Response()->jsonError("Betalings ID mangler.");
        }

        // Validate organisation
        if(isEmpty(Settings::$organisation)) {
            Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");
        }

        // Check permissions
        if(!OrganisationPermissions::__oRead('orders', 'payments')) {
            Response()->jsonError("Du har ikke tilladelse til at se betalinger.");
        }

        // Get the payment
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if(isEmpty($payment)) {
            Response()->jsonError("Betaling ikke fundet.");
        }

        // Verify the payment belongs to the current organisation
        if($payment->organisation !== Settings::$organisation->organisation->uid) {
            Response()->jsonError("Du har ikke adgang til denne betaling.");
        }

        // Only allow receipts for completed payments
        if($payment->status !== 'COMPLETED') {
            Response()->jsonError("Kvittering kan kun downloades for gennemførte betalinger.");
        }

        // Generate and download the receipt
        $receipt = new PaymentReceipt($payment);
        $receipt->download();
        exit;
    }
}
