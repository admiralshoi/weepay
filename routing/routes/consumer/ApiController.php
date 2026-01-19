<?php
namespace routing\routes\consumer;

use classes\enumerations\Links;
use classes\Methods;
use classes\payments\CardValidationService;
use classes\payments\PaymentReceipt;
use JetBrains\PhpStorm\NoReturn;

class ApiController {

    #[NoReturn] public static function getOrders(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $paymentFilter = isset($args["payment_filter"]) && !empty($args["payment_filter"]) ? trim($args["payment_filter"]) : 'fully_paid';
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        $userId = __uuid();
        if(isEmpty($userId)) {
            Response()->jsonError("Du skal være logget ind.");
        }

        // Build base query - only for current user
        $orderHandler = Methods::orders();
        $query = $orderHandler->queryBuilder()
            ->where('uuid', $userId);

        // Apply status filter
        if(!empty($filterStatus) && $filterStatus !== 'all') {
            $query->where('status', $filterStatus);
        } else {
            // Default: show all relevant orders (not DRAFT)
            $query->where('status', ['PENDING', 'COMPLETED', 'CANCELLED', 'EXPIRED', 'REFUNDED', 'VOIDED']);
        }

        // Apply date range filter
        if(!empty($startDate)) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($startDate)));
        }
        if(!empty($endDate)) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($endDate)));
        }

        // Apply search filter - search in order UID or location name
        if(!empty($search)) {
            $locationHandler = Methods::locations();
            $matchingLocationUids = $locationHandler->queryBuilder()
                ->whereLike('name', $search)
                ->pluck('uid');

            $query->startGroup("OR");
            $query->whereLike('uid', $search);
            if(!empty($matchingLocationUids)) {
                $query->where('location', $matchingLocationUids);
            }
            $query->endGroup();
        }

        // Get all matching order UIDs first for payment calculation
        $allOrderUids = (clone $query)->pluck('uid');

        // Get paid amounts for all orders
        $paidAmounts = [];
        if(!empty($allOrderUids)) {
            $paymentHandler = Methods::payments();
            $payments = $paymentHandler->queryBuilder()
                ->rawSelect('`order`, SUM(amount) as total_paid')
                ->where('order', $allOrderUids)
                ->where('status', 'COMPLETED')
                ->groupBy('order')
                ->all();
            foreach($payments->list() as $payment) {
                $paidAmounts[$payment->order] = (float)$payment->total_paid;
            }
        }

        // Get all orders to filter by payment status
        $allOrders = $orderHandler->queryGetAll(clone $query);

        // Filter orders by payment status
        $filteredOrderUids = [];
        foreach($allOrders->list() as $order) {
            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $netAmount = orderAmount($order);
            $isFullyPaid = $paidAmount >= $netAmount;

            if($paymentFilter === 'fully_paid' && $isFullyPaid) {
                $filteredOrderUids[] = $order->uid;
            } elseif($paymentFilter === 'not_fully_paid' && !$isFullyPaid) {
                $filteredOrderUids[] = $order->uid;
            }
        }

        // Get total count after payment filter
        $totalCount = count($filteredOrderUids);

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

        // Fetch paginated orders
        $orders = $orderHandler->queryGetAll(
            $orderHandler->queryBuilder()
                ->where('uid', $filteredOrderUids)
                ->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Status display mapping
        $statusMap = [
            'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
            'PENDING' => ['label' => 'Afvikles', 'class' => 'action-box'],
            'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
            'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
        ];

        // Transform orders for frontend
        $transformedOrders = [];
        foreach($orders->list() as $order) {
            // Get location name
            $locationName = 'N/A';
            if(is_object($order->location)) {
                $locationName = $order->location->name ?? 'N/A';
            } elseif(!isEmpty($order->location)) {
                $location = Methods::locations()->get($order->location);
                $locationName = $location->name ?? 'N/A';
            }

            // Payment plan label
            $planLabel = match($order->payment_plan) {
                'installments' => 'Afdrag',
                'pushed' => 'Udskudt',
                default => 'Fuld betaling'
            };
            $planClass = match($order->payment_plan) {
                'installments', 'pushed' => 'action-box',
                default => 'success-box'
            };

            $paidAmount = $paidAmounts[$order->uid] ?? 0;
            $netAmount = orderAmount($order);
            $outstanding = $netAmount - $paidAmount;
            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m/Y H:i", strtotime($order->created_at)),
                'location_name' => $locationName,
                'amount' => $netAmount,
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'currency' => $order->currency,
                'payment_plan' => $order->payment_plan,
                'plan_label' => $planLabel,
                'plan_class' => $planClass,
                'status' => $order->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$consumer->orderDetail . '/' . $order->uid),
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

    #[NoReturn] public static function getPayments(array $args): void {
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) && !empty(trim($args["search"])) ? trim($args["search"]) : null;
        $filterType = isset($args["filter_type"]) && !empty($args["filter_type"]) ? trim($args["filter_type"]) : 'completed';
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "paid_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";
        $startDate = isset($args["start_date"]) && !empty($args["start_date"]) ? trim($args["start_date"]) : null;
        $endDate = isset($args["end_date"]) && !empty($args["end_date"]) ? trim($args["end_date"]) : null;

        $userId = __uuid();
        if(isEmpty($userId)) {
            Response()->jsonError("Du skal være logget ind.");
        }

        // Build base query - only for current user
        $paymentHandler = Methods::payments();
        $query = $paymentHandler->queryBuilder()
            ->where('uuid', $userId);

        // Apply type filter (completed vs upcoming vs past_due)
        if($filterType === 'completed') {
            $query->where('status', 'COMPLETED');
            if($sortColumn === 'due_date') $sortColumn = 'paid_at';
        } elseif($filterType === 'past_due') {
            $query->where('status', 'PAST_DUE');
            if($sortColumn === 'paid_at') $sortColumn = 'due_date';
        } else {
            // Upcoming: PENDING, SCHEDULED
            $query->where('status', ['PENDING', 'SCHEDULED']);
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

        // Apply search filter
        if(!empty($search)) {
            $orderHandler = Methods::orders();
            $matchingOrderUids = $orderHandler->queryBuilder()
                ->whereLike('uid', $search)
                ->pluck('uid');

            $query->startGroup("OR");
            $query->whereLike('uid', $search);
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
                "hasPastDue" => false,
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

        // Check if there are any past due payments (for warning)
        $hasPastDue = false;
        if($filterType === 'past_due') {
            $hasPastDue = $totalCount > 0;
        }

        // Transform payments for frontend
        $transformedPayments = [];
        foreach($payments->list() as $payment) {
            $order = $payment->order;
            $orderUid = is_object($order) ? $order->uid : $order;

            // Get location name from order
            $locationName = 'N/A';
            if(is_object($order) && is_object($order->location)) {
                $locationName = $order->location->name ?? 'N/A';
            } elseif(is_object($order) && is_string($order->location)) {
                $location = Methods::locations()->get($order->location);
                $locationName = $location->name ?? 'N/A';
            }

            $statusInfo = $statusMap[$payment->status] ?? ['label' => $payment->status, 'class' => 'mute-box'];

            // Calculate days info for upcoming/past_due
            $daysInfo = null;
            if($filterType === 'upcoming' || $filterType === 'past_due') {
                $dueDate = strtotime($payment->due_date);
                $today = time();
                $daysDiff = floor(($dueDate - $today) / (60 * 60 * 24));

                if($filterType === 'past_due') {
                    $daysInfo = ['days' => abs($daysDiff), 'type' => 'overdue'];
                } else {
                    if($daysDiff < 0) {
                        $daysInfo = ['days' => 0, 'type' => 'today'];
                    } elseif($daysDiff === 0) {
                        $daysInfo = ['days' => 0, 'type' => 'today'];
                    } elseif($daysDiff <= 7) {
                        $daysInfo = ['days' => $daysDiff, 'type' => 'soon'];
                    } else {
                        $daysInfo = ['days' => $daysDiff, 'type' => 'normal'];
                    }
                }
            }

            $transformedPayments[] = [
                'uid' => $payment->uid,
                'order_uid' => $orderUid,
                'location_name' => $locationName,
                'amount' => (float)$payment->amount,
                'currency' => $payment->currency,
                'due_date' => date("d/m/Y", strtotime($payment->due_date)),
                'paid_at' => !isEmpty($payment->paid_at) ? date("d/m/Y H:i", strtotime($payment->paid_at)) : null,
                'status' => $payment->status,
                'status_label' => $statusInfo['label'],
                'status_class' => $statusInfo['class'],
                'detail_url' => __url(Links::$consumer->paymentDetail($payment->uid)),
                'order_url' => $orderUid ? __url(Links::$consumer->orderDetail . '/' . $orderUid) : null,
                'days_info' => $daysInfo,
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
            "hasPastDue" => $hasPastDue,
        ]);
    }

    #[NoReturn] public static function updateProfile(array $args): void {
        // Validation
        foreach (['full_name', 'email'] as $key) {
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) {
                Response()->jsonError("Fulde navn og email er påkrævet", [], 400);
            }
        }

        $fullName = trim($args['full_name']);
        $email = trim($args['email']);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response()->jsonError("Ugyldig email format", [], 400);
        }

        // Check if email is already taken by another user
        $existingUser = Methods::users()->getFirst(['email' => $email]);
        if($existingUser && $existingUser->uid !== __uuid()) {
            Response()->jsonError("Denne email er allerede i brug", [], 400);
        }

        // Phone validation
        $phone = null;
        $phoneCountryCode = null;

        if(!isEmpty($args['phone'])) {
            // Clean phone number
            $phone = preg_replace('/[^0-9+]/', '', $args['phone']);

            if(strlen($phone) < 8) {
                Response()->jsonError("Telefonnummer skal være mindst 8 cifre", [], 400);
            }

            // Check if phone is already taken by another user
            $existingUser = Methods::users()->getFirst(['phone' => $phone]);
            if($existingUser && $existingUser->uid !== __uuid()) {
                Response()->jsonError("Dette telefonnummer er allerede i brug", [], 400);
            }
        }

        if(!isEmpty($args['phone_country_code'])) {
            $phoneCountryCode = strtoupper(trim($args['phone_country_code']));
        }

        // Update user
        $updateData = [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'phone_country_code' => $phoneCountryCode,
        ];

        $updated = Methods::users()->update($updateData, ['uid' => __uuid()]);

        if($updated) {
            // Also update AuthLocal if exists
            $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);
            if($authLocal) {
                $authUpdateData = [
                    'email' => $email,
                    'phone' => $phone,
                    'phone_country_code' => $phoneCountryCode,
                ];

                // Enable/disable 2FA based on phone number
                if(!isEmpty($phone)) {
                    $authUpdateData['2fa'] = 1;
                    $authUpdateData['2fa_method'] = 'SMS';
                } else {
                    $authUpdateData['2fa'] = 0;
                    $authUpdateData['2fa_method'] = null;
                }

                Methods::localAuthentication()->update($authUpdateData, ['uid' => $authLocal->uid]);
            }

            Response()->jsonSuccess('Profil opdateret');
        }

        Response()->jsonError('Kunne ikke opdatere profil', [], 500);
    }

    #[NoReturn] public static function updateAddress(array $args): void {
        // Update address fields
        $updateData = [
            'address_street' => $args['address_street'] ?? null,
            'address_city' => $args['address_city'] ?? null,
            'address_zip' => $args['address_zip'] ?? null,
            'address_region' => $args['address_region'] ?? null,
            'address_country' => $args['address_country'] ?? null,
        ];

        $updated = Methods::users()->update($updateData, ['uid' => __uuid()]);

        if($updated) {
            Response()->jsonSuccess('Adresse opdateret');
        }

        Response()->jsonError('Kunne ikke opdatere adresse', [], 500);
    }

    #[NoReturn] public static function updatePassword(array $args): void {
        foreach (['password', 'password_confirm'] as $key) {
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) {
                Response()->jsonError("Udfyld begge felter", [], 400);
            }
        }

        $password = trim($args['password']);
        $passwordConfirm = trim($args['password_confirm']);

        if(strlen($password) < 8) {
            Response()->jsonError("Adgangskode skal være mindst 8 tegn", [], 400);
        }

        if($password !== $passwordConfirm) {
            Response()->jsonError("Adgangskoder matcher ikke", [], 400);
        }

        // Check if user already has a password
        $authLocal = Methods::localAuthentication()->getFirst(['user' => __uuid()]);
        $newPwd = passwordHashing($password);

        // Get user info
        $user = Methods::users()->get(__uuid());

        if($authLocal) {
            // Update existing password
            $updated = Methods::localAuthentication()->update([
                'password' => $newPwd,
                'enabled' => 1
            ], ['uid' => $authLocal->uid]);
        } else {
            // Create new AuthLocal entry with 2FA enabled if phone exists
            $authData = [
                'email' => $user->email,
                'phone' => $user->phone,
                'password' => $newPwd,
                'user' => __uuid(),
                'enabled' => 1,
                'phone_country_code' => $user->phone_country_code ?? null,
            ];

            // Enable 2FA if user has phone number
            if(!isEmpty($user->phone)) {
                $authData['2fa'] = 1;
                $authData['2fa_method'] = 'SMS';
            }

            $created = Methods::localAuthentication()->create($authData);
            $updated = !empty($created);
        }

        if($updated) {
            Response()->jsonSuccess($authLocal ? 'Adgangskode opdateret' : 'Adgangskode oprettet');
        }

        Response()->jsonError($authLocal ? 'Kunne ikke opdatere adgangskoden' : 'Kunne ikke oprette adgangskoden', [], 500);
    }

    #[NoReturn] public static function verifyPhone(array $args): void {
        // TODO: Implement phone verification logic
        // This would typically involve:
        // 1. Sending SMS verification code
        // 2. Storing verification code temporarily
        // 3. Verifying the code when user submits it
        // 4. Marking phone as verified in database

        Response()->jsonError('Telefon verifikation er ikke implementeret endnu', [], 501);
    }


    /**
     * Pay Now - Attempt to charge a PAST_DUE payment using the stored card
     *
     * POST /api/consumer/payments/{uid}/pay-now
     */
    #[NoReturn] public static function payNow(array $args): void {
        $paymentUid = $args['uid'] ?? null;
        $userId = __uuid();

        if (isEmpty($paymentUid)) {
            Response()->jsonError('Betalings-ID mangler', [], 400);
        }

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $paymentsHandler = Methods::payments();
        $ordersHandler = Methods::orders();

        // Get payment with FKs resolved for organisation access
        $payment = $paymentsHandler->includeForeignKeys()->get($paymentUid);

        if (isEmpty($payment)) {
            Response()->jsonError('Betaling ikke fundet', [], 404);
        }

        // Verify payment belongs to current user
        $paymentUserId = is_object($payment->uuid) ? $payment->uuid->uid : $payment->uuid;
        if ($paymentUserId !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne betaling', [], 403);
        }

        // Verify payment is PAST_DUE
        if ($payment->status !== 'PAST_DUE') {
            Response()->jsonError('Kun forsinkede betalinger kan betales nu', [], 400);
        }

        // Get organisation for merchant_prid
        $organisationUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
        $organisation = Methods::organisations()->get($organisationUid);

        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            errorLog([
                'payment_uid' => $paymentUid,
                'organisation_uid' => $organisationUid,
            ], 'pay-now-missing-merchant-prid');
            Response()->jsonError('Kan ikke behandle betaling - butikken mangler opsætning', [], 500);
        }

        // Check for initial_transaction_id
        if (isEmpty($payment->initial_transaction_id)) {
            errorLog([
                'payment_uid' => $paymentUid,
            ], 'pay-now-missing-initial-transaction-id');
            Response()->jsonError('Kan ikke betale - mangler kortoplysninger. Kontakt butikken.', [], 400);
        }

        // Get order for fee percentage
        $order = is_object($payment->order) ? $payment->order : $ordersHandler->get($payment->order);
        $orderUid = is_object($payment->order) ? $payment->order->uid : $payment->order;

        // Calculate total charge including rykker fees
        $originalAmount = (float)$payment->amount;
        $rykkerFee = (float)($payment->rykker_fee ?? 0);
        $totalChargeAmount = $originalAmount + $rykkerFee;

        // Recalculate ISV amount based on total charge and order fee percentage
        $feePercent = (float)($order->fee ?? 0);
        $originalIsvAmount = (float)($payment->isv_amount ?? 0);
        $newIsvAmount = round($totalChargeAmount * $feePercent / 100, 2);

        debugLog([
            'payment_uid' => $paymentUid,
            'original_amount' => $originalAmount,
            'rykker_fee' => $rykkerFee,
            'total_charge' => $totalChargeAmount,
            'fee_percent' => $feePercent,
            'original_isv' => $originalIsvAmount,
            'new_isv' => $newIsvAmount,
            'currency' => $payment->currency,
            'merchant_prid' => $organisation->merchant_prid,
        ], 'CONSUMER_PAY_NOW_START');

        // Attempt to charge using stored card
        $isTestPayment = (bool)($payment->test ?? false);

        $chargeResult = CardValidationService::chargeWithStoredCard(
            $organisation->merchant_prid,
            $payment->initial_transaction_id,
            $totalChargeAmount,
            $payment->currency,
            "Betaling af forsinket rate" . ($rykkerFee > 0 ? " inkl. rykkergebyr" : ""),
            $isTestPayment,
            $newIsvAmount > 0 ? $newIsvAmount : null
        );

        debugLog([
            'payment_uid' => $paymentUid,
            'result' => $chargeResult,
        ], 'CONSUMER_PAY_NOW_RESULT');

        if ($chargeResult['success']) {
            // Update payment: include rykker fee in amount, update ISV
            // Keep rykker history (level, sent_at dates, fee) for record-keeping
            $paymentsHandler->update([
                'amount' => $totalChargeAmount,
                'isv_amount' => $newIsvAmount,
                'sent_to_collection' => 0,
                'scheduled_at' => null,
            ], ['uid' => $paymentUid]);

            // Mark payment as completed
            $paymentsHandler->markAsCompleted($paymentUid, $chargeResult['transaction_id'] ?? null);

            // Update order: add rykker fee to amount and update fee_amount
            if ($rykkerFee > 0) {
                $newOrderAmount = (float)$order->amount + $rykkerFee;
                $isvDifference = $newIsvAmount - $originalIsvAmount;
                $newOrderFeeAmount = (float)($order->fee_amount ?? 0) + $isvDifference;

                $ordersHandler->update([
                    'amount' => $newOrderAmount,
                    'fee_amount' => $newOrderFeeAmount,
                ], ['uid' => $orderUid]);
            }

            // Trigger notification with updated payment data
            try {
                // Re-fetch payment to get updated values for notification
                $updatedPayment = $paymentsHandler->get($paymentUid);
                $user = Methods::users()->get($userId);
                \classes\notifications\NotificationTriggers::paymentSuccessful($updatedPayment, $user, $order);
            } catch (\Throwable $e) {
                errorLog(['error' => $e->getMessage()], 'pay-now-notification-error');
            }

            Response()->jsonSuccess('Betaling gennemført');
        }

        // Payment failed
        $errorMessage = $chargeResult['error'] ?? 'Betaling fejlede';
        Response()->jsonError($errorMessage, [], 400);
    }


    /**
     * Pay all outstanding (PAST_DUE) payments for an order
     *
     * POST /api/consumer/orders/{uid}/pay-outstanding
     * Charges all PAST_DUE payments for the order using stored card
     * Includes rykker fees in the charge and updates amounts accordingly
     */
    #[NoReturn] public static function payOrderOutstanding(array $args): void {
        $orderUid = $args['uid'] ?? null;
        $userId = __uuid();

        if (isEmpty($orderUid)) {
            Response()->jsonError('Ordre-ID mangler', [], 400);
        }

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $ordersHandler = Methods::orders();
        $paymentsHandler = Methods::payments();

        // Get order and verify ownership
        $order = $ordersHandler->includeForeignKeys()->get($orderUid);

        if (isEmpty($order)) {
            Response()->jsonError('Ordre ikke fundet', [], 404);
        }

        // Verify order belongs to current user
        $orderUserId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
        if ($orderUserId !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne ordre', [], 403);
        }

        // Get all PAST_DUE payments for this order
        $outstandingPayments = $paymentsHandler->includeForeignKeys()->getByX([
            'order' => $orderUid,
            'status' => 'PAST_DUE',
        ]);

        if ($outstandingPayments->count() === 0) {
            Response()->jsonError('Ingen udestående betalinger på denne ordre', [], 400);
        }

        // Get organisation for merchant_prid
        $organisationUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
        $organisation = Methods::organisations()->get($organisationUid);

        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            errorLog([
                'order_uid' => $orderUid,
                'organisation_uid' => $organisationUid,
            ], 'pay-order-outstanding-missing-merchant-prid');
            Response()->jsonError('Kan ikke behandle betaling - butikken mangler opsætning', [], 500);
        }

        // Get order fee percentage for ISV calculation
        $feePercent = (float)($order->fee ?? 0);

        debugLog([
            'order_uid' => $orderUid,
            'outstanding_count' => $outstandingPayments->count(),
            'fee_percent' => $feePercent,
            'merchant_prid' => $organisation->merchant_prid,
        ], 'CONSUMER_PAY_ORDER_OUTSTANDING_START');

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Track totals for order update
        $totalRykkerFeesCharged = 0;
        $totalIsvDifference = 0;

        // Process each outstanding payment
        foreach ($outstandingPayments->list() as $payment) {
            // Check for initial_transaction_id
            if (isEmpty($payment->initial_transaction_id)) {
                $results['failed']++;
                $results['errors'][] = "Betaling {$payment->uid}: mangler kortoplysninger";
                continue;
            }

            // Calculate total charge including rykker fees
            $originalAmount = (float)$payment->amount;
            $rykkerFee = (float)($payment->rykker_fee ?? 0);
            $totalChargeAmount = $originalAmount + $rykkerFee;

            // Recalculate ISV amount based on total charge
            $originalIsvAmount = (float)($payment->isv_amount ?? 0);
            $newIsvAmount = round($totalChargeAmount * $feePercent / 100, 2);

            // Attempt to charge using stored card
            $isTestPayment = (bool)($payment->test ?? false);

            $chargeResult = CardValidationService::chargeWithStoredCard(
                $organisation->merchant_prid,
                $payment->initial_transaction_id,
                $totalChargeAmount,
                $payment->currency,
                "Betaling af forsinket rate" . ($rykkerFee > 0 ? " inkl. rykkergebyr" : ""),
                $isTestPayment,
                $newIsvAmount > 0 ? $newIsvAmount : null
            );

            if ($chargeResult['success']) {
                // Update payment: include rykker fee in amount, update ISV
                // Keep rykker history (level, sent_at dates, fee) for record-keeping
                $paymentsHandler->update([
                    'amount' => $totalChargeAmount,
                    'isv_amount' => $newIsvAmount,
                    'sent_to_collection' => 0,
                    'scheduled_at' => null,
                ], ['uid' => $payment->uid]);

                // Mark payment as completed
                $paymentsHandler->markAsCompleted($payment->uid, $chargeResult['transaction_id'] ?? null);
                $results['success']++;

                // Track totals for order update
                $totalRykkerFeesCharged += $rykkerFee;
                $totalIsvDifference += ($newIsvAmount - $originalIsvAmount);

                // Trigger notification with updated payment data
                try {
                    $updatedPayment = $paymentsHandler->get($payment->uid);
                    $user = Methods::users()->get($userId);
                    \classes\notifications\NotificationTriggers::paymentSuccessful($updatedPayment, $user, $order);
                } catch (\Throwable $e) {
                    errorLog(['error' => $e->getMessage()], 'pay-order-outstanding-notification-error');
                }
            } else {
                $results['failed']++;
                $results['errors'][] = $chargeResult['error'] ?? 'Betaling fejlede';
            }
        }

        // Update order totals if any rykker fees were charged
        if ($totalRykkerFeesCharged > 0) {
            $newOrderAmount = (float)$order->amount + $totalRykkerFeesCharged;
            $newOrderFeeAmount = (float)($order->fee_amount ?? 0) + $totalIsvDifference;

            $ordersHandler->update([
                'amount' => $newOrderAmount,
                'fee_amount' => $newOrderFeeAmount,
            ], ['uid' => $orderUid]);
        }

        debugLog([
            'order_uid' => $orderUid,
            'results' => $results,
            'total_rykker_fees' => $totalRykkerFeesCharged,
            'total_isv_difference' => $totalIsvDifference,
        ], 'CONSUMER_PAY_ORDER_OUTSTANDING_RESULT');

        // Return appropriate response
        if ($results['failed'] === 0) {
            Response()->jsonSuccess("Alle {$results['success']} betalinger gennemført");
        } elseif ($results['success'] > 0) {
            Response()->jsonSuccess(
                "{$results['success']} betaling(er) gennemført, {$results['failed']} fejlede",
                ['errors' => $results['errors']]
            );
        } else {
            Response()->jsonError(
                'Ingen betalinger kunne gennemføres',
                ['errors' => $results['errors']],
                400
            );
        }
    }


    /**
     * Initiate card change for ALL customer's scheduled payments
     *
     * POST /api/consumer/change-card
     * Returns Viva checkout URL for 1-unit validation
     */
    #[NoReturn] public static function initiateCardChange(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        // Find customer's scheduled payments and determine organisation from them
        $paymentsHandler = Methods::payments();
        $scheduledPayment = $paymentsHandler->excludeForeignKeys()->getFirst([
            'uuid' => $userId,
            'status' => ['PENDING', 'SCHEDULED', 'PAST_DUE']
        ]);

        if (isEmpty($scheduledPayment)) {
            Response()->jsonError('Du har ingen kommende betalinger', [], 400);
        }

        $orgUid = $scheduledPayment->organisation;

        // Get organisation for merchant_prid
        $organisation = Methods::organisations()->get($orgUid);
        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            Response()->jsonError('Butikken mangler betalingsopsætning', [], 400);
        }

        // Get user info for Viva payment
        $user = Methods::users()->get($userId);

        // Get a location for source code (use first location with source)
        $location = Methods::locations()->excludeForeignKeys()->getFirst(['organisation' => $orgUid]);
        if (isEmpty($location) || isEmpty($location->source_prid)) {
            Response()->jsonError('Butikken mangler opsætning - kontakt butikken', [], 400);
        }

        // Create 1-unit validation payment
        $viva = Methods::viva()->live();

        // Check if we should use sandbox based on any test payments
        $testPayment = $paymentsHandler->excludeForeignKeys()->getFirst([
            'uuid' => $userId,
            'test' => 1
        ]);
        if ($testPayment) {
            $viva = Methods::viva()->sandbox();
        }

        $paymentResult = $viva->createPayment(
            $organisation->merchant_prid,
            1, // 1 unit of currency
            $location->source_prid,
            $user,
            "Kortskift - " . $organisation->name,
            "Kortvalidering",
            "Kortskift",
            'DKK', // TODO: Get from settings
            true, // allowRecurring - REQUIRED for card change
            false, // preAuth
            ['type' => 'card_change', 'scope' => 'global', 'user_uid' => $userId],
            null, // resellerSourceCode
            0 // No ISV fee for validation
        );

        if (isEmpty($paymentResult) || isEmpty($paymentResult['orderCode'])) {
            errorLog(['result' => $paymentResult], 'card-change-create-payment-failed');
            Response()->jsonError('Kunne ikke starte kortskift', [], 500);
        }

        // Store pending card change info in session for callback
        $_SESSION['pending_card_change'] = [
            'type' => 'global',
            'user_uid' => $userId,
            'org_uid' => $orgUid,
            'order_code' => $paymentResult['orderCode'],
            'is_test' => !isEmpty($testPayment),
            'created_at' => time(),
        ];

        $checkoutUrl = $viva->checkoutUrl($paymentResult['orderCode']);

        Response()->jsonSuccess('', ['checkoutUrl' => $checkoutUrl]);
    }


    /**
     * Initiate card change for a single order's remaining payments
     *
     * POST /api/consumer/change-card/order/{orderUid}
     * Returns Viva checkout URL for 1-unit validation
     */
    #[NoReturn] public static function initiateOrderCardChange(array $args): void {
        $orderUid = $args['orderUid'] ?? null;
        $userId = __uuid();

        if (isEmpty($orderUid)) {
            Response()->jsonError('Ordre-ID mangler', [], 400);
        }

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        // Get order with FKs
        $order = Methods::orders()->includeForeignKeys()->get($orderUid);
        if (isEmpty($order)) {
            Response()->jsonError('Ordre ikke fundet', [], 404);
        }

        // Verify order belongs to current user
        $orderUserId = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
        if ($orderUserId !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne ordre', [], 403);
        }

        // Get organisation
        $organisationUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
        $organisation = Methods::organisations()->get($organisationUid);

        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            Response()->jsonError('Butikken mangler betalingsopsætning', [], 400);
        }

        // Verify order has unpaid payments
        $paymentsHandler = Methods::payments();
        $unpaidPayments = $paymentsHandler->queryBuilder()
            ->where('order', $orderUid)
            ->where('status', ['PENDING', 'SCHEDULED', 'PAST_DUE'])
            ->count();

        if ($unpaidPayments === 0) {
            Response()->jsonError('Denne ordre har ingen kommende betalinger', [], 400);
        }

        // Get user info for Viva payment
        $user = Methods::users()->get($userId);

        // Get location
        $locationUid = is_object($order->location) ? $order->location->uid : $order->location;
        $location = Methods::locations()->get($locationUid);
        if (isEmpty($location) || isEmpty($location->source_prid)) {
            Response()->jsonError('Butikken mangler opsætning - kontakt butikken', [], 400);
        }

        // Check if test transaction
        $isTest = (bool)($order->test ?? false);

        // Create 1-unit validation payment
        $viva = $isTest ? Methods::viva()->sandbox() : Methods::viva()->live();

        $paymentResult = $viva->createPayment(
            $organisation->merchant_prid,
            1, // 1 unit of currency
            $location->source_prid,
            $user,
            "Kortskift - " . ($location->name ?? $organisation->name),
            "Kortvalidering",
            "Kortskift ordre " . $orderUid,
            $order->currency ?? 'DKK',
            true, // allowRecurring - REQUIRED for card change
            false, // preAuth
            ['type' => 'card_change', 'scope' => 'order', 'order_uid' => $orderUid, 'user_uid' => $userId],
            null, // resellerSourceCode
            0 // No ISV fee for validation
        );

        if (isEmpty($paymentResult) || isEmpty($paymentResult['orderCode'])) {
            errorLog(['result' => $paymentResult], 'card-change-order-create-payment-failed');
            Response()->jsonError('Kunne ikke starte kortskift', [], 500);
        }

        // Store pending card change info in session for callback
        $_SESSION['pending_card_change'] = [
            'type' => 'order',
            'order_uid' => $orderUid,
            'org_uid' => $organisationUid,
            'user_uid' => $userId,
            'order_code' => $paymentResult['orderCode'],
            'is_test' => $isTest,
            'created_at' => time(),
        ];

        $checkoutUrl = $viva->checkoutUrl($paymentResult['orderCode']);

        Response()->jsonSuccess('', ['checkoutUrl' => $checkoutUrl]);
    }


    /**
     * Get payments grouped by payment method (card) for card change page
     *
     * POST /api/consumer/payments-by-card
     * Returns payments grouped by their payment_method, filtered to changeable statuses only
     */
    #[NoReturn] public static function getPaymentsByCard(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $paymentsHandler = Methods::payments();

        // Only get payments with statuses that can have their card changed
        // Exclude: COMPLETED, CANCELLED, REFUNDED, VOIDED
        $changeableStatuses = ['PENDING', 'SCHEDULED', 'PAST_DUE', 'FAILED', 'DRAFT'];

        $payments = $paymentsHandler->queryGetAll(
            $paymentsHandler->queryBuilder()
                ->where('uuid', $userId)
                ->where('status', $changeableStatuses)
                ->order('due_date', 'ASC')
        );

        if ($payments->count() === 0) {
            Response()->jsonSuccess('', [
                'groups' => [],
                'totalPayments' => 0,
            ]);
        }

        // Group payments by payment_method (each payment_method is unique per card per organisation)
        $groups = [];

        foreach ($payments->list() as $payment) {
            $paymentMethodUid = is_object($payment->payment_method)
                ? $payment->payment_method->uid
                : $payment->payment_method;

            // Get location name - handle both resolved and unresolved FK
            $locationName = null;
            if (is_object($payment->location)) {
                $locationName = $payment->location->name;
            } elseif (!isEmpty($payment->location)) {
                $loc = Methods::locations()->get($payment->location);
                $locationName = $loc->name ?? null;
            }

            // Build payment data
            $paymentData = [
                'uid' => $payment->uid,
                'amount' => (float)$payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'due_date' => $payment->due_date,
                'order_uid' => is_object($payment->order) ? $payment->order->uid : $payment->order,
                'location_name' => $locationName,
            ];

            // Get organisation info for display
            $orgUid = is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation;
            $organisationName = is_object($payment->organisation)
                ? $payment->organisation->name
                : null;

            if (isEmpty($paymentMethodUid)) {
                // Group no-card payments by organisation
                $noCardKey = 'no_card_' . $orgUid;

                if (!isset($groups[$noCardKey])) {
                    $groups[$noCardKey] = [
                        'payment_method' => null,
                        'organisation_uid' => $orgUid,
                        'title' => 'Intet kort tilknyttet',
                        'brand' => null,
                        'last4' => null,
                        'exp_month' => null,
                        'exp_year' => null,
                        'organisation' => $organisationName,
                        'payments' => [],
                    ];
                }
                $groups[$noCardKey]['payments'][] = $paymentData;
            } else {
                if (!isset($groups[$paymentMethodUid])) {
                    $paymentMethod = is_object($payment->payment_method)
                        ? $payment->payment_method
                        : Methods::paymentMethods()->get($paymentMethodUid);

                    $groups[$paymentMethodUid] = [
                        'payment_method' => $paymentMethodUid,
                        'title' => $paymentMethod->title ?? 'Ukendt kort',
                        'brand' => $paymentMethod->brand ?? null,
                        'last4' => $paymentMethod->last4 ?? null,
                        'exp_month' => $paymentMethod->exp_month ?? null,
                        'exp_year' => $paymentMethod->exp_year ?? null,
                        'organisation' => $organisationName,
                        'payments' => [],
                    ];
                }
                $groups[$paymentMethodUid]['payments'][] = $paymentData;
            }
        }

        // Convert to indexed array
        $result = array_values($groups);

        // Add summary to each group
        foreach ($result as &$group) {
            $group['payment_count'] = count($group['payments']);
            $group['total_amount'] = array_sum(array_column($group['payments'], 'amount'));
            $group['currency'] = $group['payments'][0]['currency'] ?? 'DKK';
        }

        Response()->jsonSuccess('', [
            'groups' => $result,
            'totalPayments' => $payments->count(),
        ]);
    }


    /**
     * Initiate card change for all payments using a specific payment method
     *
     * POST /api/consumer/change-card/payment-method/{paymentMethodUid}
     * Creates a temporary card_change order, returns Viva checkout URL for redirect
     */
    #[NoReturn] public static function initiatePaymentMethodCardChange(array $args): void {
        // Route params are lowercased by the router
        $paymentMethodUid = $args['paymentmethoduid'] ?? $args['paymentMethodUid'] ?? null;
        $organisationUidParam = $args['organisation_uid'] ?? null; // For no-card groups
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        // Payment method can be empty string or 'null' for payments without a card
        $isNoCardGroup = isEmpty($paymentMethodUid) || $paymentMethodUid === 'null';

        $paymentsHandler = Methods::payments();
        $changeableStatuses = ['PENDING', 'SCHEDULED', 'PAST_DUE', 'FAILED', 'DRAFT'];

        // Build query for payments to update
        $query = $paymentsHandler->queryBuilder()
            ->where('uuid', $userId)
            ->where('status', $changeableStatuses);

        if ($isNoCardGroup) {
            $query->whereNull('payment_method');
            // For no-card groups, also filter by organisation if provided
            if (!isEmpty($organisationUidParam)) {
                $query->where('organisation', $organisationUidParam);
            }
        } else {
            $query->where('payment_method', $paymentMethodUid);
        }

        // Get first payment to determine organisation
        $firstPayment = $paymentsHandler->queryGetFirst($query);

        if (isEmpty($firstPayment)) {
            Response()->jsonError('Ingen betalinger fundet for dette kort', [], 404);
        }

        // Get organisation from payment
        $organisationUid = is_object($firstPayment->organisation)
            ? $firstPayment->organisation->uid
            : $firstPayment->organisation;

        $organisation = Methods::organisations()->get($organisationUid);
        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            Response()->jsonError('Butikken mangler betalingsopsætning', [], 400);
        }

        // Get location for source code
        $locationUid = is_object($firstPayment->location)
            ? $firstPayment->location->uid
            : $firstPayment->location;

        $location = Methods::locations()->get($locationUid);
        if (isEmpty($location) || isEmpty($location->source_prid)) {
            Response()->jsonError('Butikken mangler opsætning - kontakt butikken', [], 400);
        }

        // Get user info for Viva payment
        $user = Methods::users()->get($userId);

        // Check if test transaction
        $isTest = (bool)($firstPayment->test ?? false);

        // Create 1-unit validation payment via Viva
        $viva = $isTest ? Methods::viva()->sandbox() : Methods::viva()->live();

        $currency = $firstPayment->currency ?? 'DKK';

        $paymentResult = $viva->createPayment(
            $organisation->merchant_prid,
            1, // 1 unit of currency
            $location->source_prid,
            $user,
            "Kortskift - " . ($location->name ?? $organisation->name),
            "Kortvalidering",
            "Kortskift",
            $currency,
            true, // allowRecurring - REQUIRED for card change
            false, // preAuth
            [$location->name ?? $organisation->name, 'Kortskift', $user->full_name],
            null, // resellerSourceCode
            0 // No ISV fee for validation
        );

        if (isEmpty($paymentResult) || isEmpty($paymentResult['orderCode'])) {
            errorLog(['result' => $paymentResult], 'card-change-payment-method-create-failed');
            Response()->jsonError('Kunne ikke starte kortskift', [], 500);
        }

        $orderCode = $paymentResult['orderCode'];

        // Create temporary card_change order in database
        $orderHandler = Methods::orders();
        $orderUid = $orderHandler->createCardChangeOrder(
            organisation: $organisationUid,
            location: $locationUid,
            customerId: $userId,
            provider: "ppr_fheioflje98f", // Default provider
            currency: $currency,
            prid: $orderCode,
            isTest: $isTest,
            metadata: [
                'scope' => 'payment_method',
                'payment_method_uid' => $isNoCardGroup ? null : $paymentMethodUid,
                'organisation_uid' => $isNoCardGroup ? $organisationUidParam : null,
            ]
        );

        if (isEmpty($orderUid)) {
            errorLog(['orderCode' => $orderCode], 'card-change-order-create-failed');
            Response()->jsonError('Kunne ikke oprette kortskift ordre', [], 500);
        }

        $checkoutUrl = $viva->checkoutUrl($orderCode);

        Response()->jsonSuccess('', ['redirectUrl' => $checkoutUrl]);
    }

    /**
     * Download payment receipt as PDF
     * Consumer version - verifies the payment belongs to the current user
     */
    #[NoReturn] public static function downloadReceipt(array $args): void {
        $paymentId = $args['id'] ?? null;
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError("Du skal være logget ind.");
        }

        if (isEmpty($paymentId)) {
            Response()->jsonError("Betalings ID mangler.");
        }

        // Get the payment (order is auto-resolved via foreign key)
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if (isEmpty($payment)) {
            Response()->jsonError("Betaling ikke fundet.");
        }

        // $payment->order is the resolved Order object via foreign key
        if (isEmpty($payment->order)) {
            Response()->jsonError("Ordre ikke fundet.");
        }

        // Verify the order belongs to the current user
        // Note: $payment->order->uuid is a User object (foreign key), so access ->uid
        if ($payment->order->uuid->uid !== $userId) {
            Response()->jsonError("Du har ikke adgang til denne betaling.");
        }

        // Only allow receipts for completed payments
        if ($payment->status !== 'COMPLETED') {
            Response()->jsonError("Kvittering kan kun downloades for gennemførte betalinger.");
        }

        // Generate and download the receipt
        $receipt = new PaymentReceipt($payment);
        $receipt->download();
    }
}
