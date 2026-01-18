<?php
namespace routing\routes\consumer;

use classes\enumerations\Links;
use classes\Methods;
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
            $isFullyPaid = $paidAmount >= (float)$order->amount;

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
            $outstanding = (float)$order->amount - $paidAmount;
            $statusInfo = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'mute-box'];

            $transformedOrders[] = [
                'uid' => $order->uid,
                'created_at' => date("d/m/Y H:i", strtotime($order->created_at)),
                'location_name' => $locationName,
                'amount' => (float)$order->amount,
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
}
