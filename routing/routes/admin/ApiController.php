<?php

namespace routing\routes\admin;

use classes\enumerations\Links;
use classes\Methods;
use classes\payments\stripe\StripeHandler;
use classes\payments\stripe\StripeMethods;
use Database\model\Organisations;
use Database\model\Payments;
use Database\model\Users;
use JetBrains\PhpStorm\NoReturn;

class ApiController {

    /**
     * Start impersonating an organisation or user as admin
     * Logs admin in as the organisation owner or directly as a consumer
     */
    #[NoReturn] public static function startImpersonation(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $orgId = $args['organisation'] ?? null;
        $userId = $args['user'] ?? null;

        // Store admin's UID and access level so we can restore later
        $adminUid = __uuid();
        $adminAccessLevel = $_SESSION["access_level"] ?? 9;
        $adminEmail = \features\Settings::$user->email ?? 'unknown';

        // Impersonate a user directly (consumer or merchant)
        if (!empty($userId)) {
            $targetUser = Methods::users()->get($userId);

            if (isEmpty($targetUser)) {
                Response()->jsonError('Bruger ikke fundet');
            }

            $accessLevel = (int)$targetUser->access_level;
            if (!in_array($accessLevel, [1, 2])) {
                Response()->jsonError('Kan kun impersonere forbrugere eller forhandlere.');
            }

            $userType = $accessLevel === 1 ? 'consumer' : 'merchant';

            // Log the impersonation for audit
            debugLog([
                'admin_uid' => $adminUid,
                'admin_email' => $adminEmail,
                'target_uid' => $userId,
                'target_name' => $targetUser->full_name ?? 'unknown',
                'type' => $userType,
                'action' => 'start',
                'timestamp' => date('Y-m-d H:i:s')
            ], 'ADMIN_IMPERSONATION');

            // Log in as the user
            $userData = toArray($targetUser);
            $sessionKeys = array_keys($userData);
            $sessionKeys[] = "logged_in";
            setSessions($userData, $sessionKeys);

            // Store impersonation state AFTER setSessions
            $_SESSION["admin_impersonating_uid"] = $adminUid;
            $_SESSION["admin_impersonating_access_level"] = $adminAccessLevel;
            $_SESSION["admin_impersonating_user"] = $userId;

            // Redirect to appropriate dashboard
            $redirectUrl = $accessLevel === 1
                ? __url(Links::$consumer->dashboard)
                : __url(Links::$merchant->dashboard);

            Response()->jsonSuccess('Du er nu logget ind som ' . ($targetUser->full_name ?? 'bruger'), [
                'redirect' => $redirectUrl
            ]);
        }

        // Impersonate an organisation (via owner)
        if (empty($orgId)) {
            Response()->jsonError('Organisation eller bruger ID mangler');
        }

        $org = Methods::organisations()->get($orgId);
        if (isEmpty($org)) {
            Response()->jsonError('Organisation ikke fundet');
        }

        // Find the owner of this organisation
        $ownerMember = Methods::organisationMembers()->getFirst([
            'organisation' => $orgId,
            'role' => 'owner',
            'status' => 'ACTIVE'
        ]);

        if (isEmpty($ownerMember)) {
            Response()->jsonError('Ingen aktiv ejer fundet for denne organisation');
        }

        // Get the owner's user ID and user object
        $ownerUid = is_object($ownerMember->uuid) ? $ownerMember->uuid->uid : $ownerMember->uuid;
        $ownerUser = Methods::users()->get($ownerUid);

        if (isEmpty($ownerUser)) {
            Response()->jsonError('Ejerens brugerkonto blev ikke fundet');
        }

        // Log the impersonation for audit
        debugLog([
            'admin_uid' => $adminUid,
            'admin_email' => $adminEmail,
            'owner_uid' => $ownerUid,
            'organisation' => $orgId,
            'organisation_name' => $org->name,
            'type' => 'organisation',
            'action' => 'start',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'ADMIN_IMPERSONATION');

        // Log in as the owner - use setSessions like normal login does
        $ownerData = toArray($ownerUser);
        $sessionKeys = array_keys($ownerData);
        $sessionKeys[] = "logged_in";
        setSessions($ownerData, $sessionKeys);

        // Store impersonation state AFTER setSessions (so it doesn't get overwritten)
        $_SESSION["admin_impersonating_uid"] = $adminUid;
        $_SESSION["admin_impersonating_access_level"] = $adminAccessLevel;
        $_SESSION["admin_impersonating_org"] = $orgId;

        Response()->jsonSuccess('Du er nu logget ind som ejer af ' . $org->name, [
            'redirect' => __url(Links::$merchant->dashboard)
        ]);
    }

    /**
     * Stop impersonating - restore admin session
     */
    #[NoReturn] public static function stopImpersonation(array $args): void {
        $adminUid = $_SESSION["admin_impersonating_uid"] ?? null;
        $adminAccessLevel = $_SESSION["admin_impersonating_access_level"] ?? 9;
        $orgId = $_SESSION["admin_impersonating_org"] ?? null;
        $userId = $_SESSION["admin_impersonating_user"] ?? null;

        if (empty($adminUid)) {
            Response()->jsonError('Ingen aktiv impersonering fundet');
        }

        // Determine redirect URL based on impersonation type
        $redirectUrl = __url(Links::$admin->dashboard);
        $targetName = 'Ukendt';

        if (!empty($orgId)) {
            $org = Methods::organisations()->get($orgId);
            if ($org) {
                $targetName = $org->name;
            }
            $redirectUrl = __url(Links::$admin->organisations) . '/' . $orgId;
        } elseif (!empty($userId)) {
            $user = Methods::users()->get($userId);
            if ($user) {
                $targetName = $user->full_name ?? 'Bruger';
            }
            $redirectUrl = __url(Links::$admin->users) . '/' . $userId;
        }

        // Get admin user to restore full session
        $adminUser = Methods::users()->get($adminUid);

        // Log the impersonation end for audit
        debugLog([
            'admin_uid' => $adminUid,
            'organisation' => $orgId,
            'user' => $userId,
            'target_name' => $targetName,
            'action' => 'stop',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'ADMIN_IMPERSONATION');

        // Restore admin session - use setSessions like normal login does
        if (!isEmpty($adminUser)) {
            $adminData = toArray($adminUser);
            $sessionKeys = array_keys($adminData);
            $sessionKeys[] = "logged_in";
            setSessions($adminData, $sessionKeys);
        } else {
            // Fallback if admin user not found
            $_SESSION["uid"] = $adminUid;
            $_SESSION["access_level"] = $adminAccessLevel;
        }

        // Clear impersonation state
        unset($_SESSION["admin_impersonating_uid"]);
        unset($_SESSION["admin_impersonating_access_level"]);
        unset($_SESSION["admin_impersonating_org"]);
        unset($_SESSION["admin_impersonating_user"]);

        Response()->jsonSuccess('Du er nu logget tilbage som admin', [
            'redirect' => $redirectUrl
        ]);
    }



    #[NoReturn] public static function couponActions(array $args): void {
        switch ($args["action"]) {
            default: Response()->jsonError("Unknown action.");
            case "create": self::createCoupon($args);
            case "toggle": self::toggleCoupon($args);
            case "delete": self::deleteCoupon($args);
        }
    }

    #[NoReturn] public static function deleteCoupon(array $args): void {
        Response()->json(Methods::coupons()->deleteCoupon($args["id"]));
    }

    #[NoReturn] public static function toggleCoupon(array $args): void {
        $rowId = $args["id"];
        $row = Methods::coupons()->get($rowId);
        if(isEmpty($row)) Response()->jsonError("Invalid id $rowId");
        $row->enabled = (int)(!($row->enabled === 1));
        $status = Methods::coupons()->update(["enabled" => $row->enabled], ["id" => $rowId]);
        if(!$status) Response()->jsonError("Failed up update row. Try again later.");
        Response()->jsonSuccess("Row updated.", toArray($row));
    }


    #[NoReturn] public static function createCoupon(array $args): void {
//        Response()->jsonSuccess("", $args);
        $requiredKeys = ["amount", "cycles", "duration", "is_fixed", "promotional_code", "date_range", "usages", "product_ids"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Missing required key: $key");
        $amount = (float)$args["amount"];
        $isFixed = (int)$args["is_fixed"] === 1;
        $cycles = (int)$args["cycles"];
        $duration = $args["duration"];
        $code = $args["promotional_code"];
        $dateRange = $args["date_range"];
        $usages = (int)$args["usages"];
        $productIds = empty($args["product_ids"]) ? null : explode(",", $args["product_ids"]);
        if($duration !== "repeating") $cycles = 0;
        $dates = explode(" - ", $dateRange);
        $start = strtotime($dates[0]);
        $end = strtotime($dates[1]);

        if(empty($code)) Response()->jsonError("Please put a Promotional code");
        if(!in_array($duration, ["repeating", "once", "forever"])) Response()->jsonError("Invalid duration $duration.");
        if($cycles < 0) Response()->jsonError("The cycles cannot be less than 0.");
        if($usages < 0) Response()->jsonError("The allowed usages cannot be less than 0");
        if($amount <= 0) Response()->jsonError("The amount should at least be larger than 0.");
        if(!$isFixed && $amount > 100) Response()->jsonError("You cannot set a 'percent off' greater than 100.");

        if(!$isFixed) $amount = (int)$amount;
        else $amount = round($amount, 2);
        $result = Methods::coupons()->createCoupon($code, $amount, $isFixed, $duration, $usages, $start, $end, $cycles, $productIds);
        if($result["status"] === "error") Response()->jsonError($result["error"]["message"]);
        $result["data"]['refresh'] = true;
        Response()->jsonSuccess($result["message"], $result["data"]);
    }





    #[NoReturn] public static function updatePaymentSettings(array $args): void {
        $action = $args["action"];
        unset($args["action"]);

//        Response()->jsonSuccess("Data updated.", $args);

        $productHandler = Methods::products();
        $featureHandler = Methods::features();
        $priceHandler = Methods::pricing();
//        valid_campaign_currencies

        switch ($action) {
            default: Response()->jsonError("Invalid action: $action");
            case "update":

                $productUpdates = [];
                $featureUpdates = [];
                $pricesToUpdate = [];
                $pricesToCreate = [];

                foreach ($args as $productItemId => $data) {
                    $itemsToUpdate = [];
                    $row = $productHandler->getByItemId($productItemId);
                    if(isEmpty($row)) Response()->jsonError("Invalid product id given.");
                    $enabled = (int)(array_key_exists("enabled", $data) && $data["enabled"] === "on");
                    $name = array_key_exists("name", $data) ? ucfirst($data["name"]) : "";
                    $description = array_key_exists("description", $data) ? ucfirst($data["description"]) : "";
                    if(empty($name)) Response()->jsonError("Please put a name for product $productItemId");
                    if(strlen($name) > 25) Response()->jsonError("Please keep the product name under 25 characters: '$name'");

                    if($description !== $row->description) $itemsToUpdate["description"] = $description;
                    if($name !== $row->name) $itemsToUpdate["name"] = $name;
                    if($enabled !== $row->enabled) $itemsToUpdate["enabled"] = $enabled;


                    if(!array_key_exists("features", $data)) {
                        $featureHandler->update(["value" => 0], ["product_id" => $productItemId]);
                    }
                    else {
                        $existingFeatures = $featureHandler->getByProductId($productItemId);
                        foreach ($data["features"] as $rowId => $val) {
                            $feature = $existingFeatures->filter(function ($row) use ($rowId) { return (int)$rowId === (int)$row["id"]; })->first();
                            if(isEmpty($feature)) Response()->jsonError("Invalid feature with id $rowId");

                            $value = is_numeric($val) ? (int)$val : (int)($val === "on");
                            if($value !== $feature->value) $featureUpdates[$rowId] = ["value" => $value];
                        }

                        if($existingFeatures->count() !== count($data["features"])) {
                            foreach ($existingFeatures->list() as $feature) {
                                if(array_key_exists($feature->id, $data["features"])) continue;
                                if($feature->value > 0) $featureUpdates[$feature->id] = ["value" => 0];
                            }
                        }
                    }


                    if(array_key_exists("pricing", $data) && !empty($data["pricing"])) {
                        $toUpdate = $toCreate = [];
                        foreach ($data["pricing"] as $state => $items) {
                            if($state === "existing") {
                                foreach ($items as $rowId => $item) {
                                    $priceRow = $priceHandler->get($rowId);
                                    $amount = (float)max((float)$item["amount"], 0);
                                    if((float)$priceRow->amount !== $amount) $toUpdate[$rowId] = ["amount" => $amount, "currency" => $item["currency"]];
                                    elseif ($amount > 0 && $priceRow->enabled === 0) $toUpdate[$rowId] = ["enabled" => true];
                                }
                            }
                            else {
                                foreach ($items as $interval => $item) {
                                    if(!in_array($interval,  ["monthly", "quarterly", "biannual", "yearly"])) continue;
                                    $amount = max((float)$item["amount"], 0);
                                    if($amount <= 0) continue;
                                    $toCreate[$interval] = [
                                        "amount" => $amount,
                                        "currency" => $item["currency"],
                                        "price_only" => false,
                                    ];
                                }
                            }
                        }
                        if(!empty($toUpdate)) $pricesToUpdate[$productItemId] = $toUpdate;
                        if(!empty($toCreate)) $pricesToCreate[$productItemId] = $toCreate;
                    }
                    if(!empty($itemsToUpdate)) $productUpdates[$productItemId] = $itemsToUpdate;
                }


//                Response()->jsonSuccess("", compact('pricesToCreate', 'pricesToUpdate', 'productUpdates', 'featureUpdates'));

                if(empty($productUpdates) && empty($featureUpdates) && empty($pricesToCreate) && empty($pricesToUpdate)) {
                    Response()->jsonSuccess("No data to update.", ["refresh" => false]);
                }

                foreach ($productUpdates as $productItemKey => $params) {
                    $productHandler->update($params, ["item_id" => $productItemKey]);
                    $priceRow = $priceHandler->getFirst(["product_id" => $productItemKey], ["stripe_product_id"]);
                    $productRow = $productHandler->getFirst(["item_id" => $productItemKey], ["enabled", "name", "type"]);

                    if(array_key_exists("enabled", $params) || array_key_exists("name", $params)) {
                        if(array_key_exists("enabled", $params)) $enabled = $params["enabled"] === 1;
                        else $enabled = $productRow->enabled === 1;
                        if(array_key_exists("name", $params)) $stripeName = $params["name"];
                        else $stripeName = $productRow->name;
                        $stripeName = ($productRow->type === "sub" ? "(Sub) - " : "(Ppu) - ") . $stripeName;
                        if(!isEmpty($priceRow)) StripeMethods::updateProduct($priceRow->stripe_product_id, $enabled, $stripeName);
                    }
                }
                foreach ($featureUpdates as $rowId => $params) {
                    $featureHandler->update($params, ["id" => $rowId]);
                }

                $paymentHandler = Methods::paymentHandler()->handler("stripe");
                $paymentHandler->handlePriceAndProductUpdate($pricesToUpdate, $pricesToCreate, $productUpdates);



                Response()->jsonSuccess("Data updated.",
                    ["refresh" => true, "items" => compact('pricesToCreate', 'pricesToUpdate', 'productUpdates', 'featureUpdates')]);
        }


        Response()->jsonSuccess("", $args);




    }






    #[NoReturn] public static function appMetaUpdate(array $args): void {
        $metaName = array_key_exists("name", $args) ? $args["name"] : null;
        $action = $args["action"];

        $data = array_key_exists("data", $args) ? $args["data"] : [];
        $handler = Methods::appMeta();
        if(empty($data) && !$handler->exists($metaName)) Response()->jsonError("Invalid app setting: $metaName");


        switch ($action) {
            default:  Response()->jsonError("Invalid action: $action");
            case "toggle":
                $row = $handler->getRow($metaName);
                if(isEmpty($row)) Response()->jsonError("The setting does not exist or you do not have sufficient access.");

                $value = Methods::misc()::enforceDataType($row->type, $row->value);
                switch ($row->type) {
                    default: Response()->jsonError("The data type $row->type does not support the 'toggle' action.");
                    case "bool": $value = (!!!$value) ? 'true' : 'false'; break;
                    case "int": $value = (int)(!!!($value > 0)); break;
                }

                $updated = $handler->update($value, $metaName);
                if(!$updated) Response()->jsonError("Failed to toggle and update the setting. Try again later.");
                Response()->jsonSuccess("$metaName updated to $value ($row->type)");

            case "update":
                $listToUpdate = [];
                foreach ($data as $name => $val) {
                    $row = $handler->getRow($name);
                    if(isEmpty($row)) Response()->jsonError("The setting does not exist or you do not have sufficient access.");
                    $value = Methods::misc()::enforceDataType($row->type, $val);
                    if(in_array($name, ["maturing_period"])) { //Convert days to seconds.
                        $value *= (3600 * 24);
                    }
                    if(!Methods::misc()::isValidType($row->type, $value)) Response()->jsonError("Failed to convert the value of $name to $row->type.");
                    if($row->type === "bool") $value = $value ? 'true' : 'false';
                    $listToUpdate[$name] = $value;
                }

                foreach ($listToUpdate as $name => $value) {
                    $handler->update($value, $name);
                }
                Response()->jsonSuccess("App settings updated successfully.");
        }



    }





    #[NoReturn] public static function markAffiliatePaymentsAsPaid(array $args): void {
        $rowIds = $args["ids"];
        $resultsHandler = Methods::affiliatePayoutResults();
        $updates = [];
        foreach ($rowIds as $rowId) {
            $row = $resultsHandler->get($rowId);
            if(isEmpty($row)) Response()->jsonError("Failed to find some or all the associated rows. Try again later.");
            $uid = $row->affiliate_id;
            $user = Methods::affiliates()->get($uid);
            if(isEmpty($row)) Response()->jsonError("Failed to fetch the user row. Try again later.");

            $updates[$rowId] = [
                "payout_status" => $resultsHandler->statusClosed,
                "address_country" => $user->address_country,
                "address_region" => $user->address_region,
                "address_city" => $user->address_city,
                "address_zip" => $user->address_zip,
                "address_street" => $user->address_street,
                "payout_bank_info" => $user->payout_bank_info,
            ];
        }

        foreach ($updates as $rowId => $params) $resultsHandler->update($params, ["id" => $rowId]);
        Response()->jsonSuccess("Rows updated.");
    }


    #[NoReturn] public static function paymentsList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $orgFilter = $args['organisation'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $rykkerLevelFilter = $args['rykker_level'] ?? '';
        $sentToCollectionFilter = $args['sent_to_collection'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        // Validate sort column
        $allowedSortColumns = ['created_at', 'due_date', 'amount', 'status', 'rykker_level'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        // Build query - exclude foreign keys to get raw UIDs
        $paymentsHandler = Methods::payments()->excludeForeignKeys();
        $query = $paymentsHandler->queryBuilder();

        // Apply search filter
        if (!empty($search)) {
            // Get organisation UIDs matching the search term by name
            $orgHandler = Methods::organisations();
            $matchingOrgs = $orgHandler->queryBuilder()
                ->select(['uid'])
                ->whereLike('name', $search)
                ->all();

            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('prid', $search)
                ->whereLike('order', $search)
                ->whereLike('organisation', $search);

            foreach ($matchingOrgs->list() as $org) {
                $query->where('organisation', $org->uid);
            }

            $query->endGroup();
        }

        // Apply organisation filter
        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        // Apply status filter (supports comma-separated values like "PENDING,SCHEDULED")
        if (!empty($statusFilter)) {
            $statuses = array_map('trim', explode(',', $statusFilter));
            $query->where('status', $statuses);
        }

        // Apply rykker level filter
        if ($rykkerLevelFilter !== '' && $rykkerLevelFilter !== null) {
            $query->where('rykker_level', (int)$rykkerLevelFilter);
        }

        // Apply sent to collection filter
        if ($sentToCollectionFilter !== '' && $sentToCollectionFilter !== null) {
            $query->where('sent_to_collection', (int)$sentToCollectionFilter);
        }

        // Get total count
        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $payments = $paymentsHandler->queryGetAll(
            $query->order($sortColumn, $sortDirection)
                ->limit($perPage)
                ->offset($offset)
        );

        // Format payments data with related info
        $formattedPayments = [];
        foreach ($payments->list() as $payment) {
            // Get organisation name
            $orgName = null;
            if (!empty($payment->organisation)) {
                $org = Methods::organisations()->get($payment->organisation);
                $orgName = $org ? $org->name : null;
            }

            // Get user info from payment
            $userName = null;
            $userEmail = null;
            $userUid = null;
            if (!empty($payment->uuid)) {
                $user = Methods::users()->get($payment->uuid);
                if ($user) {
                    $userName = $user->full_name;
                    $userEmail = $user->email;
                    $userUid = $user->uid;
                }
            }

            $formattedPayments[] = [
                'uid' => $payment->uid,
                'order_uid' => $payment->order,
                'user_uid' => $userUid,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'organisation_name' => $orgName,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'installment_number' => $payment->installment_number,
                'due_date' => $payment->due_date,
                'paid_at' => $payment->paid_at,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
                'rykker_level' => $payment->rykker_level ?? 0,
                'rykker_fee' => $payment->rykker_fee ?? 0,
                'sent_to_collection' => $payment->sent_to_collection ?? 0,
            ];
        }

        Response()->jsonSuccess('', [
            'payments' => $formattedPayments,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }


    #[NoReturn] public static function usersList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $roleFilter = $args['role'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'full_name', 'email'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = Users::queryBuilder()
            ->select(['uid', 'full_name', 'email', 'phone', 'access_level', 'deactivated', 'created_at']);

        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->whereLike('phone', $search)
                ->whereLike('uid', $search)
                ->endGroup();
        }

        if (!empty($roleFilter)) {
            $query->where('access_level', $roleFilter);
        }

        if (!empty($statusFilter)) {
            if ($statusFilter === 'active') {
                $query->where('deactivated', 0);
            } elseif ($statusFilter === 'deactivated') {
                $query->where('deactivated', 1);
            }
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $users = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedUsers = [];
        foreach ($users->list() as $user) {
            $formattedUsers[] = [
                'uid' => $user->uid,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'access_level' => $user->access_level,
                'deactivated' => (bool)$user->deactivated,
                'created_at' => $user->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'users' => $formattedUsers,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }


    #[NoReturn] public static function ordersList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $orgFilter = $args['organisation'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'amount', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = \Database\model\Orders::queryBuilder()
            ->select(['uid', 'uuid', 'organisation', 'location', 'caption', 'amount', 'currency', 'status', 'created_at']);

        if (!empty($search)) {
            $matchingOrgs = Organisations::queryBuilder()
                ->select(['uid'])
                ->whereLike('name', $search)
                ->all();

            $query->startGroup('OR')
                ->whereLike('uid', $search)
                ->whereLike('caption', $search);

            foreach ($matchingOrgs->list() as $org) {
                $query->where('organisation', $org->uid);
            }

            $query->endGroup();
        }

        if (!empty($orgFilter)) {
            $query->where('organisation', $orgFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $orders = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedOrders = [];
        foreach ($orders->list() as $order) {
            $orgName = null;
            if (!empty($order->organisation)) {
                $org = Organisations::where('uid', $order->organisation)->first();
                $orgName = $org ? $org->name : null;
            }

            $userName = null;
            $userEmail = null;
            $userUid = null;
            if (!empty($order->uuid)) {
                $user = Users::where('uid', $order->uuid)->first();
                if ($user) {
                    $userName = $user->full_name;
                    $userEmail = $user->email;
                    $userUid = $user->uid;
                }
            }

            $formattedOrders[] = [
                'uid' => $order->uid,
                'user_uid' => $userUid,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'organisation_uid' => $order->organisation,
                'organisation_name' => $orgName,
                'caption' => $order->caption,
                'amount' => orderAmount($order),
                'currency' => $order->currency,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'orders' => $formattedOrders,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }


    #[NoReturn] public static function organisationsList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'name', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = Organisations::queryBuilder()
            ->select(['uid', 'name', 'primary_email', 'cvr', 'company_name', 'status', 'created_at']);

        if (!empty($search)) {
            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('primary_email', $search)
                ->whereLike('cvr', $search)
                ->whereLike('company_name', $search)
                ->whereLike('uid', $search)
                ->endGroup();
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $organisations = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedOrgs = [];
        foreach ($organisations->list() as $org) {
            $formattedOrgs[] = [
                'uid' => $org->uid,
                'name' => $org->name,
                'primary_email' => $org->primary_email,
                'cvr' => $org->cvr,
                'company_name' => $org->company_name,
                'status' => $org->status,
                'created_at' => $org->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'organisations' => $formattedOrgs,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }


    #[NoReturn] public static function locationsList(array $args): void {
        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 25);
        $search = $args['search'] ?? '';
        $orgFilter = $args['organisation'] ?? '';
        $statusFilter = $args['status'] ?? '';
        $sortColumn = $args['sort_column'] ?? 'created_at';
        $sortDirection = strtoupper($args['sort_direction'] ?? 'DESC');

        $allowedSortColumns = ['created_at', 'name', 'status'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'created_at';
        }

        $query = \Database\model\Locations::queryBuilder()
            ->select(['uid', 'uuid', 'name', 'slug', 'contact_email', 'status', 'created_at']);

        if (!empty($search)) {
            $matchingOrgs = Organisations::queryBuilder()
                ->select(['uid'])
                ->whereLike('name', $search)
                ->all();

            $query->startGroup('OR')
                ->whereLike('name', $search)
                ->whereLike('slug', $search)
                ->whereLike('contact_email', $search)
                ->whereLike('uid', $search);

            foreach ($matchingOrgs->list() as $org) {
                $query->where('uuid', $org->uid);
            }

            $query->endGroup();
        }

        if (!empty($orgFilter)) {
            $query->where('uuid', $orgFilter);
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        $totalCount = (clone $query)->count();
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $locations = $query->order($sortColumn, $sortDirection)
            ->limit($perPage)
            ->offset($offset)
            ->all();

        $formattedLocs = [];
        foreach ($locations->list() as $loc) {
            $orgName = null;
            $orgUid = null;
            if (!empty($loc->uuid)) {
                $org = Organisations::where('uid', $loc->uuid)->first();
                if ($org) {
                    $orgName = $org->name;
                    $orgUid = $org->uid;
                }
            }

            $formattedLocs[] = [
                'uid' => $loc->uid,
                'name' => $loc->name,
                'slug' => $loc->slug,
                'contact_email' => $loc->contact_email,
                'organisation_uid' => $orgUid,
                'organisation_name' => $orgName,
                'status' => $loc->status,
                'created_at' => $loc->created_at,
            ];
        }

        Response()->jsonSuccess('', [
            'locations' => $formattedLocs,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCount,
                'totalPages' => $totalPages,
            ],
        ]);
    }


    #[NoReturn] public static function organisationStatusUpdate(array $args): void {
        $orgId = $args['id'] ?? '';
        $status = $args['status'] ?? '';

        if (empty($orgId)) {
            Response()->jsonError('Organisation ID mangler');
        }

        $validStatuses = ['ACTIVE', 'INACTIVE', 'DRAFT'];
        if (!in_array($status, $validStatuses)) {
            Response()->jsonError('Ugyldig status');
        }

        $organisation = Methods::organisations()->get($orgId);
        if (isEmpty($organisation)) {
            Response()->jsonError('Organisation ikke fundet');
        }

        $updated = Methods::organisations()->update(['status' => $status], ['uid' => $orgId]);

        if (!$updated) {
            Response()->jsonError('Kunne ikke opdatere organisation status');
        }

        Response()->jsonSuccess('Organisation status opdateret');
    }

    #[NoReturn] public static function dashboardStats(array $args): void {
        $startDate = $args['start_date'] ?? date('Y-m-d');
        $endDate = $args['end_date'] ?? date('Y-m-d');

        // Convert to timestamps
        $startTimestamp = strtotime($startDate . ' 00:00:00');
        $endTimestamp = strtotime($endDate . ' 23:59:59');

        // Revenue from completed payments in the date range
        $revenue = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=')
            ->sum('amount') ?? 0;

        // ISV (net sales) from completed payments in the date range
        $isvAmount = Methods::payments()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('paid_at', $startTimestamp, '>=')
            ->whereTimeBefore('paid_at', $endTimestamp, '<=')
            ->sum('isv_amount') ?? 0;

        // Orders count in the date range
        $ordersCount = Methods::orders()->queryBuilder()
            ->where('status', 'COMPLETED')
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // New users in the date range
        $newUsers = Methods::users()->queryBuilder()
            ->whereTimeAfter('created_at', $startTimestamp, '>=')
            ->whereTimeBefore('created_at', $endTimestamp, '<=')
            ->count();

        // Chart data: daily breakdown for the selected date range
        $revenueChartData = [];
        $userGrowthChartData = [];
        $currentDate = strtotime($startDate);
        $endDateTs = strtotime($endDate);

        while ($currentDate <= $endDateTs) {
            $dayStart = strtotime(date('Y-m-d', $currentDate) . ' 00:00:00');
            $dayEnd = strtotime(date('Y-m-d', $currentDate) . ' 23:59:59');

            // Revenue chart data
            $dayRevenue = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=')
                ->sum('amount') ?? 0;
            $dayPayments = Methods::payments()->queryBuilder()
                ->where('status', 'COMPLETED')
                ->whereTimeAfter('paid_at', $dayStart, '>=')
                ->whereTimeBefore('paid_at', $dayEnd, '<=')
                ->count();

            $revenueChartData[] = [
                'date' => date('d/m', $currentDate),
                'revenue' => (float)$dayRevenue,
                'payments' => (int)$dayPayments
            ];

            // User growth chart data
            $newConsumers = Methods::users()->queryBuilder()
                ->where('access_level', 1)
                ->whereTimeAfter('created_at', $dayStart, '>=')
                ->whereTimeBefore('created_at', $dayEnd, '<=')
                ->count();
            $newMerchants = Methods::users()->queryBuilder()
                ->where('access_level', 2)
                ->whereTimeAfter('created_at', $dayStart, '>=')
                ->whereTimeBefore('created_at', $dayEnd, '<=')
                ->count();

            $userGrowthChartData[] = [
                'date' => date('d/m', $currentDate),
                'consumers' => (int)$newConsumers,
                'merchants' => (int)$newMerchants
            ];

            $currentDate = strtotime('+1 day', $currentDate);
        }

        Response()->jsonSuccess('', [
            'revenue' => (float)$revenue,
            'isv_amount' => (float)$isvAmount,
            'orders_count' => (int)$ordersCount,
            'new_users' => (int)$newUsers,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'revenue_chart' => $revenueChartData,
            'user_growth_chart' => $userGrowthChartData,
        ]);
    }


    /**
     * Update a single AppMeta setting or handle special panel operations
     */
    #[NoReturn] public static function panelUpdateSetting(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $key = $args['key'] ?? null;
        $value = $args['value'] ?? null;

        if (empty($key)) {
            Response()->jsonError('Nøgle mangler');
        }

        // Handle special org fee operations
        if ($key === 'org_fee_save') {
            self::handleOrgFeeSave((array)$value);
            return;
        }

        if ($key === 'org_fee_delete') {
            self::handleOrgFeeDelete((array)$value);
            return;
        }

        // Handle country operations
        if ($key === 'country_add') {
            self::handleCountryAdd((array)$value);
            return;
        }

        if ($key === 'country_remove') {
            self::handleCountryRemove((array)$value);
            return;
        }

        // Validate organisation_roles - ensure fixed roles are not removed and clean new roles
        if ($key === 'organisation_roles' && is_array($value)) {
            $fixedOrgRoles = \classes\organisations\OrganisationRolePermissions::getFixedRoles();
            // Clean all role names and validate
            $cleanedValue = [];
            foreach ($value as $role) {
                $sanitized = \classes\utility\Titles::sanitizeKey($role);
                if (empty($sanitized)) {
                    Response()->jsonError('Ugyldigt rollenavn: "' . htmlspecialchars($role) . '". Brug kun bogstaver (a-z, æ, ø, å) og mellemrum.');
                }
                $cleanedValue[] = $sanitized;
            }
            $value = array_unique($cleanedValue);
            $value = array_values($value);
            foreach ($fixedOrgRoles as $fixedRole) {
                if (!in_array($fixedRole, $value)) {
                    Response()->jsonError('Systemrollen "' . $fixedRole . '" kan ikke fjernes');
                }
            }
        }

        // Validate location_roles - ensure fixed roles are not removed and clean new roles
        if ($key === 'location_roles' && is_array($value)) {
            $fixedLocationRoles = \classes\organisations\LocationRolePermissions::getFixedRoles();
            // Clean all role names and validate
            $cleanedValue = [];
            foreach ($value as $role) {
                $sanitized = \classes\utility\Titles::sanitizeKey($role);
                if (empty($sanitized)) {
                    Response()->jsonError('Ugyldigt rollenavn: "' . htmlspecialchars($role) . '". Brug kun bogstaver (a-z, æ, ø, å) og mellemrum.');
                }
                $cleanedValue[] = $sanitized;
            }
            $value = array_unique($cleanedValue);
            $value = array_values($value);
            foreach ($fixedLocationRoles as $fixedRole) {
                if (!in_array($fixedRole, $value)) {
                    Response()->jsonError('Systemrollen "' . $fixedRole . '" kan ikke fjernes');
                }
            }
        }

        // Check if the setting exists, create if it doesn't
        $success = false;
        if (!Methods::appMeta()->exists($key)) {
            // Determine type based on value
            $type = 'string';
            if (is_bool($value)) $type = 'bool';
            elseif (is_int($value)) $type = 'int';
            elseif (is_float($value)) $type = 'float';
            elseif (is_array($value) || is_object($value)) $type = 'array';

            $success = Methods::appMeta()->create([
                'name' => $key,
                'value' => is_array($value) || is_object($value) ? json_encode($value) : (string)$value,
                'type' => $type
            ]);
        } else {
            // Update the setting
            $success = Methods::appMeta()->update($value, $key);
        }

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'key' => $key,
                'action' => 'update_setting'
            ], 'ADMIN_PANEL_SETTING');

            // Return the sanitized value for roles so frontend can display correctly
            $responseData = [];
            if ($key === 'organisation_roles' || $key === 'location_roles') {
                $responseData['value'] = $value;
            }
            Response()->jsonSuccess('Indstillingen er opdateret', $responseData);
        } else {
            Response()->jsonError('Kunne ikke opdatere indstillingen');
        }
    }

    /**
     * Create a new user from admin panel
     */
    #[NoReturn] public static function panelCreateUser(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $fullName = trim($args['full_name'] ?? '');
        $accessLevel = (int)($args['access_level'] ?? 0);
        $email = trim($args['email'] ?? '');
        $username = trim($args['username'] ?? '');

        // Validate required fields
        if (empty($fullName)) {
            Response()->jsonError('Navn er påkrævet');
        }

        if (empty($accessLevel)) {
            Response()->jsonError('Rolle er påkrævet');
        }

        // Validate email if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response()->jsonError('Ugyldigt email format');
        }

        // Check if email already exists
        if (!empty($email)) {
            $existingEmail = Methods::users()->getFirst(['email' => $email]);
            if (!isEmpty($existingEmail)) {
                Response()->jsonError('Denne email er allerede registreret');
            }
        }

        // Check if username already exists
        if (!empty($username)) {
            $existingUsername = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['username' => $username]);
            if (!isEmpty($existingUsername)) {
                Response()->jsonError('Dette brugernavn er allerede taget');
            }
        }

        $userHandler = Methods::users();

        // Generate username if not provided
        if (empty($username)) {
            // Use "weepay" as org prefix for admin-created users
            $username = $userHandler->generateUniqueUsername('weepay', $fullName);
        }

        // Generate a temporary password
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);

        // Create user
        if (!$userHandler->create([
            'full_name' => $fullName,
            'email' => !empty($email) ? $email : null,
            'access_level' => $accessLevel,
            'lang' => 'DA',
            'created_by' => __uuid()
        ])) {
            Response()->jsonError('Kunne ikke oprette brugeren. Prøv igen senere.');
        }

        $userUid = $userHandler->recentUid;

        // Create auth record
        Methods::localAuthentication()->create([
            'username' => $username,
            'email' => !empty($email) ? $email : null,
            'password' => passwordHashing($password),
            'user' => $userUid,
            'enabled' => 1,
            'force_password_change' => 1
        ]);

        // Log notification if email is provided
        $emailSent = false;
        if (!empty($email)) {
            Methods::notificationHandler()->userCreated([
                'uid' => $userUid,
                'organisation_name' => BRAND_NAME,
                'username' => $username,
                'password' => $password,
                'ref' => null,
                'push_type' => 1 // Email
            ]);
            $emailSent = true;
        }

        // Log admin action
        debugLog([
            'admin' => __uuid(),
            'created_user' => $userUid,
            'username' => $username,
            'access_level' => $accessLevel,
            'action' => 'create_user'
        ], 'ADMIN_PANEL_CREATE_USER');

        // Return success with credentials
        Response()->jsonSuccess(
            'Brugeren er blevet oprettet.',
            [
                'user_created' => true,
                'username' => $username,
                'password' => $password,
                'email_sent' => $emailSent,
                'full_name' => $fullName
            ]
        );
    }

    /**
     * Handle organisation fee save
     */
    #[NoReturn] private static function handleOrgFeeSave(array $data): void {
        $uid = $data['uid'] ?? null;
        $organisations = $data['organisations'] ?? [];
        $fee = (float)($data['fee'] ?? 0);
        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        $reason = $data['reason'] ?? null;

        if (empty($uid) && empty($organisations)) {
            Response()->jsonError('Mindst én organisation er påkrævet');
        }

        if ($fee < 0 || $fee > 100) {
            Response()->jsonError('Gebyret skal være mellem 0 og 100');
        }

        // Validate minimum fee (card fee + payment provider fee)
        $cardFee = Methods::appMeta()->get('cardFee') ?? 0.39;
        $paymentProviderFee = Methods::appMeta()->get('paymentProviderFee') ?? 0.39;
        $minFee = $cardFee + $paymentProviderFee;

        if ($fee < $minFee) {
            Response()->jsonError('Gebyret kan ikke være lavere end ' . number_format($minFee, 2, ',', '.') . ' % (kortgebyr + betalingsudbyder gebyr)');
        }

        $handler = Methods::organisationFees();

        if (!empty($uid)) {
            // Update existing fee - only reason can be changed
            $success = $handler->update([
                'reason' => $reason
            ], ['uid' => $uid]);
        } else {
            // Calculate proper timestamps for new fees
            $now = time();
            $todayStart = strtotime('today 00:00:00');
            $todayEnd = strtotime('today 23:59:59');

            // Parse start date
            if (!empty($startDate)) {
                $startDateObj = strtotime($startDate);
                $startDateStart = strtotime(date('Y-m-d', $startDateObj) . ' 00:00:00');

                // Validate not in the past
                if ($startDateStart < $todayStart) {
                    Response()->jsonError('Startdato kan ikke være i fortiden');
                }

                // If today, use current time. Otherwise use start of day
                if ($startDateStart === $todayStart) {
                    $startTime = $now;
                } else {
                    $startTime = $startDateStart;
                }
            } else {
                $startTime = $now;
            }

            // Parse end date - always end of day
            $endTime = null;
            if (!empty($endDate)) {
                $endDateObj = strtotime($endDate);
                $endDateEnd = strtotime(date('Y-m-d', $endDateObj) . ' 23:59:59');

                // Validate not in the past
                if ($endDateEnd < $todayEnd) {
                    Response()->jsonError('Slutdato kan ikke være i fortiden');
                }

                // Validate end is not before start
                if ($endDateEnd < $startTime) {
                    Response()->jsonError('Slutdato kan ikke være før startdato');
                }

                $endTime = $endDateEnd;
            }

            // Insert new fee(s) for each organisation with overlap handling
            $success = true;
            foreach ($organisations as $organisation) {
                // Handle overlapping fees for this organisation
                self::handleOrgFeeOverlaps($handler, $organisation, $startTime, $endTime);

                // Insert the new fee
                $feeUid = $handler->insertFee(
                    $organisation,
                    $fee,
                    $startTime,
                    $endTime,
                    __uuid(),
                    $reason
                );
                if (empty($feeUid)) {
                    $success = false;
                }
            }
        }

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'uid' => $uid,
                'organisations' => $organisations,
                'fee' => $fee,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'action' => $uid ? 'update_org_fee' : 'create_org_fee'
            ], 'ADMIN_PANEL_ORG_FEE');

            Response()->jsonSuccess('Gebyret er gemt');
        } else {
            Response()->jsonError('Kunne ikke gemme gebyret');
        }
    }

    /**
     * Handle overlapping fee periods for an organisation
     * New fee supersedes existing ones - splits existing fees if needed
     */
    private static function handleOrgFeeOverlaps($handler, string $organisation, int $newStartTime, ?int $newEndTime): void {
        // Get all active fees for this organisation
        $existingFees = $handler->getByX(['organisation' => $organisation, 'enabled' => 1]);

        if (empty($existingFees)) {
            return;
        }

        foreach ($existingFees as $existingFee) {
            $existingStart = (int)$existingFee->start_time;
            $existingEnd = $existingFee->end_time ? (int)$existingFee->end_time : null;

            // Check if there's an overlap
            $overlaps = false;

            // Case: new fee has no end (infinite)
            if ($newEndTime === null) {
                // Overlaps if existing starts at or after new start, OR existing ends at or after new start
                if ($existingEnd === null || $existingEnd >= $newStartTime) {
                    $overlaps = true;
                }
            }
            // Case: new fee has an end
            else {
                // Check for any overlap
                if ($existingEnd === null) {
                    // Existing is infinite - overlaps if existing starts before new ends
                    if ($existingStart <= $newEndTime) {
                        $overlaps = true;
                    }
                } else {
                    // Both have ends - standard overlap check
                    if ($existingStart <= $newEndTime && $existingEnd >= $newStartTime) {
                        $overlaps = true;
                    }
                }
            }

            if (!$overlaps) {
                continue;
            }

            // Handle the overlap - end existing fee just before new one starts
            $newExistingEnd = $newStartTime - 1;

            // If the new fee would end before the existing fee, we need to create a continuation
            $needsContinuation = false;
            $continuationStart = null;

            if ($newEndTime !== null && ($existingEnd === null || $existingEnd > $newEndTime)) {
                $needsContinuation = true;
                $continuationStart = $newEndTime + 1;
            }

            // If existing fee hasn't started yet and starts after new one starts, disable it entirely
            if ($existingStart >= $newStartTime) {
                if ($needsContinuation && ($existingEnd === null || $existingEnd > $newEndTime)) {
                    // Existing fee starts within new period but extends beyond - update start time
                    $handler->update([
                        'start_time' => $continuationStart
                    ], ['uid' => $existingFee->uid]);
                } else {
                    // Existing fee is completely covered - disable it
                    $handler->update([
                        'enabled' => 0
                    ], ['uid' => $existingFee->uid]);
                }
            } else {
                // Existing fee started before new one - end it before new one starts
                $handler->update([
                    'end_time' => $newExistingEnd
                ], ['uid' => $existingFee->uid]);

                // If we need a continuation after the new fee ends
                if ($needsContinuation) {
                    $handler->insertFee(
                        $organisation,
                        $existingFee->fee,
                        $continuationStart,
                        $existingEnd,
                        __uuid(),
                        $existingFee->reason ? $existingFee->reason . ' (fortsat)' : null
                    );
                }
            }
        }
    }

    /**
     * Handle organisation fee delete
     */
    #[NoReturn] private static function handleOrgFeeDelete(array $data): void {
        $uid = $data['uid'] ?? null;

        if (empty($uid)) {
            Response()->jsonError('Gebyr ID mangler');
        }

        $handler = Methods::organisationFees();
        $success = $handler->update(['enabled' => 0], ['uid' => $uid]);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'uid' => $uid,
                'action' => 'delete_org_fee'
            ], 'ADMIN_PANEL_ORG_FEE');

            Response()->jsonSuccess('Gebyret er slettet');
        } else {
            Response()->jsonError('Kunne ikke slette gebyret');
        }
    }

    /**
     * Handle adding a country
     */
    #[NoReturn] private static function handleCountryAdd(array $data): void {
        debugLog(['data' => $data], 'COUNTRY_ADD_DEBUG');

        $code = $data['code'] ?? null;
        $name = $data['name'] ?? null;

        debugLog(['code' => $code, 'name' => $name], 'COUNTRY_ADD_DEBUG');

        if (empty($code) || empty($name)) {
            debugLog(['error' => 'missing code or name'], 'COUNTRY_ADD_DEBUG');
            Response()->jsonError('Landekode og navn er påkrævet');
        }

        // Check if country already exists
        $existing = \Database\model\Countries::where('code', $code)->first();

        if ($existing) {
            // Enable if disabled
            if ($existing->enabled == 0) {
                $success = \Database\model\Countries::where('code', $code)->update(['enabled' => 1]);
            } else {
                Response()->jsonError('Landet findes allerede');
            }
        } else {
            // Insert new country
            $success = \Database\model\Countries::insert([
                'code' => strtoupper($code),
                'name' => $name,
                'enabled' => 1
            ]);
        }

        if ($success ?? false) {
            debugLog([
                'admin' => __uuid(),
                'code' => $code,
                'name' => $name,
                'action' => 'add_country'
            ], 'ADMIN_PANEL_COUNTRY');

            Response()->jsonSuccess('Landet er tilføjet');
        } else {
            Response()->jsonError('Kunne ikke tilføje landet');
        }
    }

    /**
     * Handle removing a country (disabling it)
     */
    #[NoReturn] private static function handleCountryRemove(array $data): void {
        $code = $data['code'] ?? null;

        if (empty($code)) {
            Response()->jsonError('Landekode mangler');
        }

        // Check if this is the default country
        $defaultCountry = Methods::appMeta()->get('default_country');
        if ($defaultCountry === $code) {
            Response()->jsonError('Kan ikke fjerne standardlandet. Skift standardland først.');
        }

        $success = \Database\model\Countries::where('code', $code)->update(['enabled' => 0]);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'code' => $code,
                'action' => 'remove_country'
            ], 'ADMIN_PANEL_COUNTRY');

            Response()->jsonSuccess('Landet er fjernet');
        } else {
            Response()->jsonError('Kunne ikke fjerne landet');
        }
    }

    /**
     * Create a new user role
     */
    #[NoReturn] public static function panelCreateRole(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $accessLevel = isset($args['access_level']) ? (int)$args['access_level'] : null;
        $name = trim($args['name'] ?? '');
        $description = trim($args['description'] ?? '');

        if ($accessLevel === null) {
            Response()->jsonError('Adgangsniveau er påkrævet');
        }

        if (empty($name)) {
            Response()->jsonError('Rollenavn er påkrævet');
        }

        // Validate access level range (3-7 allowed, 0, 1, 2, 8, 9 are reserved)
        if ($accessLevel < 3 || $accessLevel > 7) {
            Response()->jsonError('Adgangsniveau skal være mellem 3 og 7 (0, 1, 2, 8, 9 er reserveret)');
        }

        // Check if access level already exists
        $existing = \Database\model\UserRoles::where('access_level', $accessLevel)->first();
        if ($existing) {
            Response()->jsonError('Adgangsniveau ' . $accessLevel . ' findes allerede');
        }

        // Clean role name (lowercase, no special chars)
        $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name));

        // Check if name already exists
        $existingName = \Database\model\UserRoles::where('name', $cleanName)->first();
        if ($existingName) {
            Response()->jsonError('Rollenavn "' . $name . '" findes allerede');
        }

        // Insert new role
        $success = \Database\model\UserRoles::insert([
            'access_level' => $accessLevel,
            'name' => $cleanName,
            'description' => $description,
            'depth' => '',
            'defined' => 0
        ]);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'access_level' => $accessLevel,
                'name' => $cleanName,
                'action' => 'create_role'
            ], 'ADMIN_PANEL_CREATE_ROLE');

            Response()->jsonSuccess('Rollen er blevet oprettet');
        } else {
            Response()->jsonError('Kunne ikke oprette rollen');
        }
    }

    /**
     * Update an existing user role
     */
    #[NoReturn] public static function panelUpdateRole(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $accessLevel = isset($args['access_level']) ? (int)$args['access_level'] : null;
        $description = trim($args['description'] ?? '');
        $defined = (int)($args['defined'] ?? 0);

        if ($accessLevel === null) {
            Response()->jsonError('Adgangsniveau er påkrævet');
        }

        // Check if role exists
        $existing = \Database\model\UserRoles::where('access_level', $accessLevel)->first();
        if (!$existing) {
            Response()->jsonError('Rollen findes ikke');
        }

        // Update role
        $success = \Database\model\UserRoles::where('access_level', $accessLevel)->update([
            'description' => $description,
            'defined' => $defined
        ]);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'access_level' => $accessLevel,
                'description' => $description,
                'defined' => $defined,
                'action' => 'update_role'
            ], 'ADMIN_PANEL_UPDATE_ROLE');

            Response()->jsonSuccess('Rollen er blevet opdateret');
        } else {
            Response()->jsonError('Kunne ikke opdatere rollen');
        }
    }


    /**
     * Update rykker (dunning) settings
     * POST api/admin/panel/rykker-settings
     */
    #[NoReturn] public static function panelRykkerSettings(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $rykker1Days = isset($args['rykker_1_days']) ? (int)$args['rykker_1_days'] : 7;
        $rykker2Days = isset($args['rykker_2_days']) ? (int)$args['rykker_2_days'] : 14;
        $rykker3Days = isset($args['rykker_3_days']) ? (int)$args['rykker_3_days'] : 21;
        $rykker1Fee = isset($args['rykker_1_fee']) ? (float)$args['rykker_1_fee'] : 0;
        $rykker2Fee = isset($args['rykker_2_fee']) ? (float)$args['rykker_2_fee'] : 100;
        $rykker3Fee = isset($args['rykker_3_fee']) ? (float)$args['rykker_3_fee'] : 100;

        // Validate days are in ascending order
        if ($rykker1Days >= $rykker2Days || $rykker2Days >= $rykker3Days) {
            Response()->jsonError('Dagene skal være i stigende rækkefølge (Rykker 1 < Rykker 2 < Rykker 3)');
        }

        // Validate minimum days
        if ($rykker1Days < 1) {
            Response()->jsonError('Rykker 1 skal være mindst 1 dag efter forfalden');
        }

        // Update all rykker settings
        $appMeta = Methods::appMeta();
        $appMeta->update($rykker1Days, 'rykker_1_days');
        $appMeta->update($rykker2Days, 'rykker_2_days');
        $appMeta->update($rykker3Days, 'rykker_3_days');
        $appMeta->update($rykker1Fee, 'rykker_1_fee');
        $appMeta->update($rykker2Fee, 'rykker_2_fee');
        $appMeta->update($rykker3Fee, 'rykker_3_fee');

        debugLog([
            'admin' => __uuid(),
            'rykker_1_days' => $rykker1Days,
            'rykker_2_days' => $rykker2Days,
            'rykker_3_days' => $rykker3Days,
            'rykker_1_fee' => $rykker1Fee,
            'rykker_2_fee' => $rykker2Fee,
            'rykker_3_fee' => $rykker3Fee,
            'action' => 'update_rykker_settings'
        ], 'ADMIN_PANEL_RYKKER_SETTINGS');

        Response()->jsonSuccess('Rykker indstillinger er opdateret');
    }


    /**
     * Refund an entire order - refunds all completed payments and voids pending ones
     * POST api/admin/orders/{id}/refund
     */
    #[NoReturn] public static function refundOrder(array $args): void {
        $orderId = $args['id'] ?? null;

        debugLog(['action' => 'refundOrder', 'orderId' => $orderId, 'args' => $args, 'admin' => __uuid()], 'ADMIN_REFUND_ORDER_START');

        if (!Methods::isAdmin()) {
            debugLog(['error' => 'Not admin'], 'ADMIN_REFUND_ORDER_ERROR');
            Response()->jsonError('Adgang nægtet', 403);
        }

        if (isEmpty($orderId)) {
            debugLog(['error' => 'Missing order ID'], 'ADMIN_REFUND_ORDER_ERROR');
            Response()->jsonError("Ordre ID mangler.");
        }

        // Get the order
        $orderHandler = Methods::orders();
        $order = $orderHandler->get($orderId);

        if (isEmpty($order)) {
            debugLog(['error' => 'Order not found', 'orderId' => $orderId], 'ADMIN_REFUND_ORDER_ERROR');
            Response()->jsonError("Ordre ikke fundet.");
        }

        debugLog([
            'orderId' => $order->uid,
            'orderStatus' => $order->status,
            'orderAmount' => $order->amount,
            'orderOrganisation' => is_object($order->organisation) ? $order->organisation->uid : $order->organisation,
        ], 'ADMIN_REFUND_ORDER_FOUND');

        // Check order status - can only refund COMPLETED or PENDING orders
        if (!in_array($order->status, ['COMPLETED', 'PENDING'])) {
            debugLog(['error' => 'Invalid order status', 'status' => $order->status], 'ADMIN_REFUND_ORDER_ERROR');
            Response()->jsonError("Denne ordre kan ikke refunderes (status: {$order->status}).");
        }

        // Resolve organisation
        $orderOrg = is_object($order->organisation) ? $order->organisation : Methods::organisations()->get($order->organisation);

        debugLog(['organisationUid' => $orderOrg->uid ?? null, 'organisationName' => $orderOrg->name ?? null], 'ADMIN_REFUND_ORDER_ORG');

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
        ], 'ADMIN_REFUND_ORDER_PAYMENTS');

        // Must have either completed payments to refund OR pending payments to void
        if (!$hasCompletedPayments && !$hasPendingPayments) {
            debugLog(['error' => 'No payments to refund or void'], 'ADMIN_REFUND_ORDER_ERROR');
            Response()->jsonError("Der er ingen betalinger at refundere eller annullere.");
        }

        $totalRefunded = 0;
        $refundErrors = [];
        $paymentsRefundedCount = 0; // Count of payments refunded in this operation

        // Only process Viva refunds if there are completed payments
        if ($hasCompletedPayments) {
            $merchantId = $orderOrg->merchant_prid ?? null;

            debugLog(['merchantId' => $merchantId, 'organisationUid' => $orderOrg->uid ?? null], 'ADMIN_REFUND_ORDER_MERCHANT');

            if (isEmpty($merchantId)) {
                debugLog(['error' => 'Missing merchant ID'], 'ADMIN_REFUND_ORDER_ERROR');
                Response()->jsonError("Organisation mangler Viva merchant ID.");
            }

            // Get the Viva API - use the same mode the order was created in
            // The order's `test` field indicates if it was created in sandbox/test mode
            $isTestOrder = (bool)($order->test ?? false);
            $viva = Methods::viva();
            if (!$isTestOrder) {
                $viva->live();
            }

            debugLog([
                'isTestOrder' => $isTestOrder,
                'orderTestField' => $order->test ?? null,
                'vivaMode' => $isTestOrder ? 'sandbox' : 'live',
            ], 'ADMIN_REFUND_ORDER_VIVA_MODE');

            // Refund each completed payment
            foreach ($completedPayments->list() as $payment) {
                debugLog([
                    'paymentId' => $payment->uid,
                    'paymentStatus' => $payment->status,
                    'paymentAmount' => $payment->amount,
                    'paymentPrid' => $payment->prid,
                ], 'ADMIN_REFUND_ORDER_PAYMENT_LOOP');

                // Skip if already refunded
                if ($payment->status === 'REFUNDED') {
                    debugLog(['skipped' => 'Already refunded', 'paymentId' => $payment->uid], 'ADMIN_REFUND_ORDER_SKIP');
                    continue;
                }

                // Get transaction ID (prid)
                $transactionId = $payment->prid;
                if (isEmpty($transactionId)) {
                    debugLog(['error' => 'Missing prid', 'paymentId' => $payment->uid], 'ADMIN_REFUND_ORDER_ERROR');
                    $refundErrors[] = "Betaling {$payment->uid} mangler transaktion ID.";
                    continue;
                }

                debugLog([
                    'calling' => 'viva->refundTransaction',
                    'merchantId' => $merchantId,
                    'transactionId' => $transactionId,
                    'amount' => (float)$payment->amount,
                    'currency' => $payment->currency,
                ], 'ADMIN_REFUND_ORDER_VIVA_CALL');

                // Call Viva refund API (full refund)
                $result = $viva->refundTransaction(
                    $merchantId,
                    $transactionId,
                    (float)$payment->amount,
                    null,
                    $payment->currency
                );

                debugLog(['vivaResult' => $result], 'ADMIN_REFUND_ORDER_VIVA_RESULT');

                if (!isEmpty($result) && isset($result['TransactionId'])) {
                    debugLog([
                        'success' => true,
                        'paymentId' => $payment->uid,
                        'refundTransactionId' => $result['TransactionId'],
                    ], 'ADMIN_REFUND_ORDER_PAYMENT_SUCCESS');

                    // Update payment status to REFUNDED (clears failure_reason etc.)
                    $paymentHandler->setRefunded($payment->uid);
                    $totalRefunded += (float)$payment->amount;
                    $paymentsRefundedCount++;
                } else {
                    $errorMsg = $result['message'] ?? $result['Message'] ?? $result['ErrorText'] ?? 'Ukendt fejl';
                    debugLog([
                        'error' => 'Viva refund failed',
                        'paymentId' => $payment->uid,
                        'errorMsg' => $errorMsg,
                        'fullResult' => $result,
                    ], 'ADMIN_REFUND_ORDER_PAYMENT_ERROR');
                    $refundErrors[] = "Betaling {$payment->uid}: {$errorMsg}";

                    // Create attention notification if this is a merchant config issue
                    $orgUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
                    Methods::requiresAttentionNotifications()->createFromVivaError(
                        'refund',
                        $result ?? [],
                        $orgUid,
                        [
                            'payment_uid' => $payment->uid,
                            'order_uid' => $order->uid,
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                        ]
                    );
                }
            }
        }

        debugLog([
            'totalRefunded' => $totalRefunded,
            'refundErrors' => $refundErrors,
        ], 'ADMIN_REFUND_ORDER_SUMMARY');

        // Void all future/pending payments
        $voidedCount = 0;
        if ($hasPendingPayments) {
            foreach ($pendingPayments->list() as $futurePayment) {
                $paymentHandler->update(['status' => 'VOIDED'], ['uid' => $futurePayment->uid]);
                $voidedCount++;
                debugLog(['voided' => $futurePayment->uid, 'amount' => $futurePayment->amount], 'ADMIN_REFUND_ORDER_VOIDED_PAYMENT');
            }
        }

        debugLog(['voidedCount' => $voidedCount], 'ADMIN_REFUND_ORDER_VOIDED_SUMMARY');

        // Update order amount_refunded, fee_amount and status
        $orderAmount = (float)$order->amount;
        $newAmountRefunded = (float)$order->amount_refunded + $totalRefunded;
        $updateData = ['amount_refunded' => $newAmountRefunded];

        // Recalculate fee_amount based on remaining amount after refund
        $feePercentage = (float)($order->fee ?? 0);
        $remainingAmount = $orderAmount - $newAmountRefunded;

        if ($remainingAmount <= 0) {
            $updateData['fee_amount'] = 0;
        } else {
            $newFeeAmount = $remainingAmount * ($feePercentage / 100);
            $updateData['fee_amount'] = round($newFeeAmount, 2);
        }

        debugLog([
            'orderAmount' => $orderAmount,
            'feePercentage' => $feePercentage,
            'remainingAmount' => $remainingAmount,
            'newFeeAmount' => $updateData['fee_amount'],
        ], 'ADMIN_REFUND_ORDER_FEE_RECALC');

        // Determine order status: REFUNDED if any payments have been refunded (now or previously), VOIDED if only voided
        $refundedPaymentsCount = $paymentHandler->count(['order' => $orderId, 'status' => 'REFUNDED']);

        if ($refundedPaymentsCount > 0 || $totalRefunded > 0) {
            $updateData['status'] = 'REFUNDED';
            $successMessage = "Ordre refunderet succesfuldt.";
        } else {
            $updateData['status'] = 'VOIDED';
            $successMessage = "Ordre annulleret succesfuldt.";
        }

        debugLog(['updateData' => $updateData, 'newStatus' => $updateData['status']], 'ADMIN_REFUND_ORDER_UPDATE');
        $orderHandler->update($updateData, ['uid' => $orderId]);

        // Trigger order.refunded notification if at least one payment was refunded (not just voided)
        if ($paymentsRefundedCount > 0) {
            // Resolve user from order
            $user = null;
            if (!isEmpty($order->uuid)) {
                $user = is_object($order->uuid) ? $order->uuid : Methods::users()->get($order->uuid);
            }

            // Resolve location from order
            $location = null;
            if (!isEmpty($order->location)) {
                $location = is_object($order->location) ? $order->location : Methods::locations()->get($order->location);
            }

            // Fetch all payments for BNPL orders to include in notification
            $allPayments = null;
            if ($order->payment_plan !== 'direct') {
                $allPaymentsQuery = $paymentHandler->queryBuilder()
                    ->where('order', $orderId)
                    ->order('due_date', 'ASC');
                $allPayments = $paymentHandler->queryGetAll($allPaymentsQuery);
            }

            \classes\notifications\NotificationTriggers::orderRefunded(
                $order,
                $user,
                $totalRefunded,
                $paymentsRefundedCount,
                $voidedCount,
                'Refundering af administrator',
                $orderOrg,
                $location,
                $allPayments
            );

            debugLog(['order_refunded_notification_triggered' => true, 'orderId' => $order->uid, 'paymentsRefundedCount' => $paymentsRefundedCount], 'ADMIN_REFUND_ORDER_NOTIFICATION');
        }

        if (!empty($refundErrors)) {
            debugLog(['result' => 'partial_success', 'errors' => $refundErrors], 'ADMIN_REFUND_ORDER_COMPLETE');
            Response()->setRedirect()->jsonSuccess("Delvis refundering gennemført. " . implode(' ', $refundErrors), [
                'total_refunded' => $totalRefunded,
                'voided_payments' => $voidedCount,
                'currency' => $order->currency,
                'errors' => $refundErrors,
            ]);
        }

        debugLog(['result' => 'success', 'totalRefunded' => $totalRefunded, 'voidedCount' => $voidedCount], 'ADMIN_REFUND_ORDER_COMPLETE');
        Response()->setRedirect()->jsonSuccess($successMessage, [
            'total_refunded' => $totalRefunded,
            'voided_payments' => $voidedCount,
            'currency' => $order->currency,
        ]);
    }


    /**
     * Refund a single payment
     * POST api/admin/payments/{id}/refund
     */
    #[NoReturn] public static function refundPayment(array $args): void {
        $paymentId = $args['id'] ?? null;

        debugLog(['action' => 'refundPayment', 'paymentId' => $paymentId, 'args' => $args, 'admin' => __uuid()], 'ADMIN_REFUND_PAYMENT_START');

        if (!Methods::isAdmin()) {
            debugLog(['error' => 'Not admin'], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError('Adgang nægtet', 403);
        }

        if (isEmpty($paymentId)) {
            debugLog(['error' => 'Missing payment ID'], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betalings ID mangler.");
        }

        // Get the payment
        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if (isEmpty($payment)) {
            debugLog(['error' => 'Payment not found', 'paymentId' => $paymentId], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betaling ikke fundet.");
        }

        debugLog([
            'paymentId' => $payment->uid,
            'paymentStatus' => $payment->status,
            'paymentAmount' => $payment->amount,
            'paymentPrid' => $payment->prid,
            'paymentCurrency' => $payment->currency,
            'paymentOrganisation' => is_object($payment->organisation) ? $payment->organisation->uid : $payment->organisation,
        ], 'ADMIN_REFUND_PAYMENT_FOUND');

        // Check payment status
        if ($payment->status !== 'COMPLETED') {
            debugLog(['error' => 'Invalid payment status', 'status' => $payment->status], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError("Kun gennemførte betalinger kan refunderes (status: {$payment->status}).");
        }

        // Resolve organisation
        $organisation = is_object($payment->organisation) ? $payment->organisation : Methods::organisations()->get($payment->organisation);

        debugLog(['organisationUid' => $organisation->uid ?? null, 'organisationName' => $organisation->name ?? null], 'ADMIN_REFUND_PAYMENT_ORG');

        $merchantId = $organisation->merchant_prid ?? null;
        if (isEmpty($merchantId)) {
            debugLog(['error' => 'Missing merchant ID', 'organisationUid' => $organisation->uid ?? null], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError("Organisation mangler Viva merchant ID.");
        }

        // Get transaction ID
        $transactionId = $payment->prid;
        if (isEmpty($transactionId)) {
            debugLog(['error' => 'Missing prid', 'paymentId' => $payment->uid], 'ADMIN_REFUND_PAYMENT_ERROR');
            Response()->jsonError("Betaling mangler transaktion ID.");
        }

        debugLog([
            'calling' => 'viva->refundTransaction',
            'merchantId' => $merchantId,
            'transactionId' => $transactionId,
            'amount' => (float)$payment->amount,
            'currency' => $payment->currency,
        ], 'ADMIN_REFUND_PAYMENT_VIVA_CALL');

        // Get the Viva API and refund
        $viva = Methods::viva();
        $result = $viva->refundTransaction(
            $merchantId,
            $transactionId,
            (float)$payment->amount,
            null,
            $payment->currency
        );

        debugLog(['vivaResult' => $result], 'ADMIN_REFUND_PAYMENT_VIVA_RESULT');

        if (isEmpty($result) || !isset($result['TransactionId'])) {
            $errorMsg = $result['message'] ?? $result['Message'] ?? $result['ErrorText'] ?? 'Ukendt fejl fra Viva';
            debugLog(['error' => 'Viva refund failed', 'errorMsg' => $errorMsg, 'fullResult' => $result], 'ADMIN_REFUND_PAYMENT_ERROR');

            // Create attention notification if this is a merchant config issue
            $orderId = is_object($payment->order) ? $payment->order->uid : $payment->order;
            Methods::requiresAttentionNotifications()->createFromVivaError(
                'refund',
                $result ?? [],
                $organisation->uid,
                [
                    'payment_uid' => $payment->uid,
                    'order_uid' => $orderId,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                ]
            );

            Response()->jsonError("Refundering fejlede: {$errorMsg}");
        }

        debugLog(['success' => true, 'refundTransactionId' => $result['TransactionId']], 'ADMIN_REFUND_PAYMENT_VIVA_SUCCESS');

        // Update payment status (clears failure_reason etc.)
        $paymentHandler->setRefunded($paymentId);
        debugLog(['paymentStatusUpdated' => 'REFUNDED', 'paymentId' => $paymentId], 'ADMIN_REFUND_PAYMENT_UPDATED');

        // Update order if exists
        $orderId = is_object($payment->order) ? $payment->order->uid : $payment->order;
        if (!isEmpty($orderId)) {
            $orderHandler = Methods::orders();
            $order = $orderHandler->get($orderId);

            if (!isEmpty($order)) {
                $orderAmount = (float)$order->amount;
                $newAmountRefunded = (float)$order->amount_refunded + (float)$payment->amount;
                $updateData = ['amount_refunded' => $newAmountRefunded];

                // Recalculate fee
                $feePercentage = (float)($order->fee ?? 0);
                $remainingAmount = $orderAmount - $newAmountRefunded;

                if ($remainingAmount <= 0) {
                    $updateData['fee_amount'] = 0;
                } else {
                    $updateData['fee_amount'] = round($remainingAmount * ($feePercentage / 100), 2);
                }

                // If all payments are now refunded (amount == amount_refunded), set order status to REFUNDED
                if ($newAmountRefunded >= $orderAmount) {
                    $updateData['status'] = 'REFUNDED';
                    debugLog(['order_fully_refunded' => true, 'newAmountRefunded' => $newAmountRefunded, 'orderAmount' => $orderAmount], 'ADMIN_REFUND_PAYMENT_ORDER_FULLY_REFUNDED');
                }

                debugLog([
                    'orderId' => $orderId,
                    'orderAmount' => $orderAmount,
                    'newAmountRefunded' => $newAmountRefunded,
                    'feePercentage' => $feePercentage,
                    'remainingAmount' => $remainingAmount,
                    'newFeeAmount' => $updateData['fee_amount'],
                    'newStatus' => $updateData['status'] ?? 'unchanged',
                ], 'ADMIN_REFUND_PAYMENT_ORDER_UPDATE');

                $orderHandler->update($updateData, ['uid' => $orderId]);
            }
        }

        // Trigger notification
        $user = null;
        $order = null;
        $location = null;

        if (!isEmpty($payment->uuid)) {
            $user = is_object($payment->uuid) ? $payment->uuid : Methods::users()->get($payment->uuid);
        }
        if (!isEmpty($orderId)) {
            $order = is_object($payment->order) ? $payment->order : Methods::orders()->get($orderId);
        }
        if (!isEmpty($payment->location)) {
            $location = is_object($payment->location) ? $payment->location : Methods::locations()->get($payment->location);
        }

        debugLog([
            'triggering_notification' => true,
            'userId' => $user->uid ?? null,
            'orderId' => $order->uid ?? null,
            'locationId' => $location->uid ?? null,
        ], 'ADMIN_REFUND_PAYMENT_NOTIFICATION');

        \classes\notifications\NotificationTriggers::paymentRefunded(
            $payment,
            $user,
            $order,
            (float)$payment->amount,
            'Refundering af administrator',
            $organisation,
            $location
        );

        debugLog(['result' => 'success', 'refundedAmount' => (float)$payment->amount, 'currency' => $payment->currency], 'ADMIN_REFUND_PAYMENT_COMPLETE');

        Response()->setRedirect()->jsonSuccess("Betaling refunderet succesfuldt.", [
            'refunded_amount' => (float)$payment->amount,
            'currency' => $payment->currency,
        ]);
    }


    /**
     * Reset rykker status for a payment
     * POST api/admin/payments/{id}/reset-rykker
     */
    #[NoReturn] public static function resetPaymentRykker(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $paymentId = $args['id'] ?? null;
        if (isEmpty($paymentId)) {
            Response()->jsonError("Betalings ID mangler.");
        }

        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if (isEmpty($payment)) {
            Response()->jsonError("Betaling ikke fundet.");
        }

        // Reset rykker (clear fees by default)
        $success = $paymentHandler->resetRykker($paymentId, true);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'paymentId' => $paymentId,
                'action' => 'reset_rykker'
            ], 'ADMIN_RESET_RYKKER');

            Response()->jsonSuccess('Rykker status er blevet nulstillet');
        } else {
            Response()->jsonError('Kunne ikke nulstille rykker status');
        }
    }


    /**
     * Mark a payment for collection
     * POST api/admin/payments/{id}/mark-collection
     */
    #[NoReturn] public static function markPaymentForCollection(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $paymentId = $args['id'] ?? null;
        if (isEmpty($paymentId)) {
            Response()->jsonError("Betalings ID mangler.");
        }

        $paymentHandler = Methods::payments();
        $payment = $paymentHandler->get($paymentId);

        if (isEmpty($payment)) {
            Response()->jsonError("Betaling ikke fundet.");
        }

        // Mark for collection
        $success = $paymentHandler->markForCollection($paymentId);

        if ($success) {
            debugLog([
                'admin' => __uuid(),
                'paymentId' => $paymentId,
                'action' => 'mark_for_collection'
            ], 'ADMIN_MARK_COLLECTION');

            Response()->jsonSuccess('Betalingen er markeret til inkasso');
        } else {
            Response()->jsonError('Kunne ikke markere betalingen til inkasso');
        }
    }


    // =====================================================
    // FAQ MANAGEMENT API
    // =====================================================

    /**
     * Get all FAQs
     */
    #[NoReturn] public static function faqsList(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $type = $args['type'] ?? null;
        $faqHandler = Methods::faqs();

        if ($type) {
            $faqs = $faqHandler->getGroupedByCategory($type, false);
        } else {
            $faqs = [
                'consumer' => $faqHandler->getGroupedByCategory('consumer', false),
                'merchant' => $faqHandler->getGroupedByCategory('merchant', false)
            ];
        }

        Response()->jsonSuccess('FAQs hentet', ['faqs' => $faqs]);
    }

    /**
     * Create a new FAQ
     */
    #[NoReturn] public static function faqCreate(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $type = $args['type'] ?? null;
        $category = $args['category'] ?? null;
        $title = $args['title'] ?? null;
        $content = $args['content'] ?? null;

        if (isEmpty($type) || isEmpty($category) || isEmpty($title) || isEmpty($content)) {
            Response()->jsonError('Alle felter er påkrævede');
        }

        if (!in_array($type, ['consumer', 'merchant'])) {
            Response()->jsonError('Ugyldig type. Skal være consumer eller merchant');
        }

        $faqHandler = Methods::faqs();
        $uid = $faqHandler->createFaq([
            'type' => $type,
            'category' => trim($category),
            'title' => trim($title),
            'content' => $content,
            'is_active' => 1
        ]);

        if ($uid) {
            $faq = $faqHandler->get($uid);
            Response()->jsonSuccess('FAQ oprettet', ['faq' => $faq]);
        } else {
            Response()->jsonError('Kunne ikke oprette FAQ');
        }
    }

    /**
     * Update an existing FAQ
     */
    #[NoReturn] public static function faqUpdate(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $uid = $args['uid'] ?? null;
        if (isEmpty($uid)) {
            Response()->jsonError('FAQ ID er påkrævet');
        }

        $faqHandler = Methods::faqs();
        $existingFaq = $faqHandler->get($uid);

        if (!$existingFaq) {
            Response()->jsonError('FAQ ikke fundet');
        }

        $updateData = [];

        if (isset($args['category'])) {
            $updateData['category'] = trim($args['category']);
        }
        if (isset($args['title'])) {
            $updateData['title'] = trim($args['title']);
        }
        if (isset($args['content'])) {
            $updateData['content'] = $args['content'];
        }
        if (isset($args['sort_order'])) {
            $updateData['sort_order'] = (int)$args['sort_order'];
        }
        if (isset($args['is_active'])) {
            $updateData['is_active'] = (int)$args['is_active'];
        }

        if (empty($updateData)) {
            Response()->jsonError('Ingen data at opdatere');
        }

        $success = $faqHandler->updateFaq($uid, $updateData);

        if ($success) {
            $updatedFaq = $faqHandler->get($uid);
            Response()->jsonSuccess('FAQ opdateret', ['faq' => $updatedFaq]);
        } else {
            Response()->jsonError('Kunne ikke opdatere FAQ');
        }
    }

    /**
     * Delete a FAQ
     */
    #[NoReturn] public static function faqDelete(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $uid = $args['uid'] ?? null;
        if (isEmpty($uid)) {
            Response()->jsonError('FAQ ID er påkrævet');
        }

        $faqHandler = Methods::faqs();
        $success = $faqHandler->deleteFaq($uid);

        if ($success) {
            Response()->jsonSuccess('FAQ slettet');
        } else {
            Response()->jsonError('Kunne ikke slette FAQ');
        }
    }

    /**
     * Toggle FAQ active status
     */
    #[NoReturn] public static function faqToggleActive(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $uid = $args['uid'] ?? null;
        if (isEmpty($uid)) {
            Response()->jsonError('FAQ ID er påkrævet');
        }

        $faqHandler = Methods::faqs();
        $success = $faqHandler->toggleActive($uid);

        if ($success) {
            $faq = $faqHandler->get($uid);
            Response()->jsonSuccess('FAQ status opdateret', ['faq' => $faq]);
        } else {
            Response()->jsonError('Kunne ikke opdatere FAQ status');
        }
    }

    /**
     * Reorder FAQs within a category
     */
    #[NoReturn] public static function faqReorder(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', 403);
        }

        $type = $args['type'] ?? null;
        $category = $args['category'] ?? null;
        $order = $args['order'] ?? null;

        if (isEmpty($type) || isEmpty($category) || !is_array($order)) {
            Response()->jsonError('Type, kategori og rækkefølge er påkrævet');
        }

        $faqHandler = Methods::faqs();
        $success = $faqHandler->reorder($type, $category, $order);

        if ($success) {
            Response()->jsonSuccess('Rækkefølge opdateret');
        } else {
            Response()->jsonError('Kunne ikke opdatere rækkefølge');
        }
    }


    // =====================================================
    // SUPPORT TICKET METHODS
    // =====================================================

    /**
     * List support tickets with filtering and pagination
     */
    #[NoReturn] public static function supportList(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $page = (int)($args['page'] ?? 1);
        $perPage = (int)($args['per_page'] ?? 20);
        $status = $args['status'] ?? null;
        $type = $args['type'] ?? null;
        $search = trim($args['search'] ?? '');

        $ticketHandler = Methods::supportTickets();

        // Build filters
        $filters = [];
        if (!isEmpty($status) && $status !== 'all') {
            $filters['status'] = $status;
        }
        if (!isEmpty($type) && $type !== 'all') {
            $filters['type'] = $type;
        }

        // Get filtered tickets with pagination
        $result = $ticketHandler->getFiltered($filters, $page, $perPage, $search);

        // Transform for frontend
        $transformedTickets = [];
        foreach ($result['tickets']->list() as $ticket) {
            $userName = 'Ukendt';
            $userEmail = '';
            if (is_object($ticket->user)) {
                $userName = $ticket->user->full_name ?? 'Ukendt';
                $userEmail = $ticket->user->email ?? '';
            }

            // Get reply count
            $replyCount = Methods::supportTicketReplies()->countByTicket($ticket->uid);

            // Get organisation name if ticket is on behalf of organisation
            $orgName = null;
            $onBehalfOf = $ticket->on_behalf_of ?? 'personal';
            if ($onBehalfOf === 'organisation' && is_object($ticket->organisation)) {
                $orgName = $ticket->organisation->name ?? null;
            }

            $transformedTickets[] = [
                'uid' => $ticket->uid,
                'user_name' => $userName,
                'user_email' => $userEmail,
                'type' => $ticket->type,
                'type_label' => $ticket->type === 'consumer' ? 'Forbruger' : 'Forhandler',
                'on_behalf_of' => $onBehalfOf,
                'organisation_name' => $orgName,
                'category' => $ticket->category,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'status_label' => $ticket->status === 'open' ? 'Åben' : 'Lukket',
                'reply_count' => $replyCount,
                'created_at' => date('d/m/Y H:i', strtotime($ticket->created_at)),
                'detail_url' => __url(Links::$admin->supportDetail($ticket->uid)),
            ];
        }

        Response()->jsonSuccess('', [
            'tickets' => $transformedTickets,
            'pagination' => $result['pagination'],
            'counts' => $ticketHandler->getCounts(),
        ]);
    }

    /**
     * Admin reply to a support ticket
     */
    #[NoReturn] public static function supportReply(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');
        $message = trim($args['message'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        if (isEmpty($message)) {
            Response()->jsonError('Besked er påkrævet', [], 400);
        }

        if (strlen($message) > 5000) {
            Response()->jsonError('Besked må max være 5000 tegn', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        $replyHandler = Methods::supportTicketReplies();
        $replyUid = $replyHandler->addReply($ticketUid, __uuid(), $message, true);

        if (isEmpty($replyUid)) {
            Response()->jsonError('Kunne ikke tilføje svar', [], 500);
        }

        // Trigger notification to user
        try {
            $reply = $replyHandler->get($replyUid);
            $user = is_object($ticket->user) ? $ticket->user : Methods::users()->get($ticket->user);
            \classes\notifications\NotificationTriggers::supportTicketReplied($ticket, $user, $reply);
        } catch (\Throwable $e) {
            errorLog(['error' => $e->getMessage()], 'support-reply-notification-error');
        }

        Response()->jsonSuccess('Svar sendt');
    }

    /**
     * Close a support ticket
     */
    #[NoReturn] public static function supportClose(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        $success = $ticketHandler->closeTicket($ticketUid, __uuid());

        if (!$success) {
            Response()->jsonError('Kunne ikke lukke henvendelse', [], 500);
        }

        Response()->jsonSuccess('Henvendelse lukket');
    }

    /**
     * Reopen a closed support ticket
     */
    #[NoReturn] public static function supportReopen(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        $success = $ticketHandler->reopenTicket($ticketUid);

        if (!$success) {
            Response()->jsonError('Kunne ikke genåbne henvendelse', [], 500);
        }

        Response()->jsonSuccess('Henvendelse genåbnet');
    }

    /**
     * Delete a support ticket
     */
    #[NoReturn] public static function supportDelete(array $args): void {
        debugLog("supportDelete called with args: " . json_encode($args), "ADMIN_SUPPORT_DELETE");

        if (!Methods::isAdmin()) {
            debugLog("Access denied - not admin", "ADMIN_SUPPORT_DELETE");
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');
        debugLog("Ticket UID: $ticketUid", "ADMIN_SUPPORT_DELETE");

        if (isEmpty($ticketUid)) {
            debugLog("Ticket ID is empty", "ADMIN_SUPPORT_DELETE");
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->get($ticketUid);
        debugLog("Ticket found: " . json_encode($ticket ? true : false), "ADMIN_SUPPORT_DELETE");

        if (isEmpty($ticket)) {
            debugLog("Ticket not found", "ADMIN_SUPPORT_DELETE");
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        debugLog("Calling deleteTicket...", "ADMIN_SUPPORT_DELETE");
        $success = $ticketHandler->deleteTicket($ticketUid);
        debugLog("Delete result: " . json_encode($success), "ADMIN_SUPPORT_DELETE");

        if (!$success) {
            debugLog("Delete failed", "ADMIN_SUPPORT_DELETE");
            Response()->jsonError('Kunne ikke slette henvendelse', [], 500);
        }

        debugLog("Delete successful", "ADMIN_SUPPORT_DELETE");
        Response()->jsonSuccess('Henvendelse slettet');
    }

    // =====================================================
    // POLICY MANAGEMENT API
    // =====================================================

    /**
     * Get all policy summaries for admin overview
     */
    #[NoReturn] public static function policiesList(array $args): void {
        $summaries = Methods::policyTypes()->getAllWithStatus();
        Response()->jsonSuccess('', ['policies' => $summaries]);
    }

    /**
     * Get a specific policy or create draft for editing
     */
    #[NoReturn] public static function policiesGet(array $args): void {
        $type = $args['type'] ?? null;
        $uid = $args['uid'] ?? null;

        // If UID provided, get that specific policy version
        if (!isEmpty($uid)) {
            $policy = Methods::policyVersions()->get($uid);
            if (isEmpty($policy)) {
                Response()->jsonError('Politik ikke fundet', [], 404);
            }

            // Get type from policy_type FK
            $policyType = is_object($policy->policy_type) ? $policy->policy_type->type : str_replace('pt_', '', $policy->policy_type);

            Response()->jsonSuccess('', [
                'policy' => [
                    'uid' => $policy->uid,
                    'type' => $policyType,
                    'title' => $policy->title,
                    'content' => $policy->content,
                    'status' => $policy->status,
                    'version' => $policy->version,
                    'active_from' => $policy->active_from,
                    'published_at' => $policy->published_at,
                ]
            ]);
        }

        // If type provided, get or create draft for editing
        if (!isEmpty($type)) {
            $draft = Methods::policyVersions()->getOrCreateDraft($type, __uuid());
            if (isEmpty($draft)) {
                Response()->jsonError('Kunne ikke oprette kladde', [], 500);
            }

            // Check if this is a scheduled version
            $typeUid = Methods::policyTypes()->getUidForType($type);
            $policyType = Methods::policyTypes()->excludeForeignKeys()->get($typeUid);
            $isScheduled = !isEmpty($policyType) && $policyType->scheduled_version === $draft->uid;
            $scheduledAt = $isScheduled ? $policyType->scheduled_at : null;

            Response()->jsonSuccess('', [
                'policy' => [
                    'uid' => $draft->uid,
                    'type' => $type,
                    'title' => $draft->title,
                    'content' => $draft->content,
                    'status' => $draft->status,
                    'version' => $draft->version,
                    'active_from' => $draft->active_from,
                    'published_at' => $draft->published_at,
                    'is_scheduled' => $isScheduled,
                    'scheduled_at' => $scheduledAt,
                ]
            ]);
        }

        Response()->jsonError('Angiv type eller uid', [], 400);
    }

    /**
     * Save/update a draft policy
     */
    #[NoReturn] public static function policiesSave(array $args): void {
        $uid = $args['uid'] ?? null;
        $title = trim($args['title'] ?? '');
        $content = $args['content'] ?? '';

        if (isEmpty($uid)) {
            Response()->jsonError('Politik UID mangler', [], 400);
        }

        if (isEmpty($title)) {
            Response()->jsonError('Titel er påkrævet', [], 400);
        }

        $updated = Methods::policyVersions()->updateDraft($uid, [
            'title' => $title,
            'content' => $content,
        ], __uuid());

        if (!$updated) {
            Response()->jsonError('Kunne ikke gemme kladde. Kun kladder kan redigeres.', [], 400);
        }

        // Return updated policy
        $policy = Methods::policyVersions()->get($uid);
        Response()->jsonSuccess('Kladde gemt', [
            'policy' => [
                'uid' => $policy->uid,
                'title' => $policy->title,
                'content' => $policy->content,
                'status' => $policy->status,
                'version' => $policy->version,
                'updated_at' => $policy->updated_at,
            ]
        ]);
    }

    /**
     * Publish a policy (immediate or scheduled)
     */
    #[NoReturn] public static function policiesPublish(array $args): void {
        $uid = $args['uid'] ?? null;
        $startsAt = $args['starts_at'] ?? null;
        $notify = (bool)($args['notify'] ?? false);
        $recipientTypes = $args['recipient_types'] ?? [];

        if (isEmpty($uid)) {
            Response()->jsonError('Politik UID mangler', [], 400);
        }

        // Validate starts_at if provided
        if (!isEmpty($startsAt)) {
            $timestamp = strtotime($startsAt);
            if ($timestamp === false) {
                Response()->jsonError('Ugyldig startdato', [], 400);
            }
        }

        // Determine if immediate or scheduled publish
        $isScheduled = !isEmpty($startsAt) && strtotime($startsAt) > time();

        if ($isScheduled) {
            // Schedule for future - notifications sent immediately with scheduled date
            $success = Methods::policyVersions()->schedulePublish($uid, $startsAt, __uuid(), $notify, $recipientTypes);
            $message = 'Politik planlagt til publicering';
        } else {
            // Publish immediately
            $success = Methods::policyVersions()->publishImmediate($uid, __uuid(), $notify, $recipientTypes);
            $message = 'Politik publiceret';
        }

        if (!$success) {
            Response()->jsonError('Kunne ikke publicere politik. Kun kladder kan publiceres.', [], 400);
        }

        // Return the updated policy
        $policy = Methods::policyVersions()->get($uid);
        Response()->jsonSuccess($message, [
            'policy' => [
                'uid' => $policy->uid,
                'title' => $policy->title,
                'content' => $policy->content,
                'status' => $policy->status,
                'version' => $policy->version,
                'active_from' => $policy->active_from,
                'published_at' => $policy->published_at,
                'is_scheduled' => false,
                'scheduled_at' => null,
            ]
        ]);
    }

    /**
     * Delete a draft policy
     */
    #[NoReturn] public static function policiesDelete(array $args): void {
        $uid = $args['uid'] ?? null;

        if (isEmpty($uid)) {
            Response()->jsonError('Politik UID mangler', [], 400);
        }

        $success = Methods::policyVersions()->deleteDraft($uid);

        if (!$success) {
            Response()->jsonError('Kunne ikke slette politik. Kun kladder kan slettes.', [], 400);
        }

        Response()->jsonSuccess('Kladde slettet');
    }

    /**
     * Get version history for a policy type
     */
    #[NoReturn] public static function policiesVersions(array $args): void {
        $type = $args['type'] ?? null;
        $changelogUid = $args['changelog_uid'] ?? null;

        if (isEmpty($type) && isEmpty($changelogUid)) {
            Response()->jsonError('Politik type eller changelog_uid mangler', [], 400);
        }

        // If changelog_uid provided, get that specific change log entry with snapshot
        if (!isEmpty($changelogUid)) {
            $changelog = Methods::policyChangeLogs()->get($changelogUid);
            if (isEmpty($changelog)) {
                Response()->jsonError('Changelog ikke fundet', [], 404);
            }

            Response()->jsonSuccess('', [
                'changelog' => [
                    'uid' => $changelog->uid,
                    'title_snapshot' => $changelog->title_snapshot,
                    'content_snapshot' => $changelog->content_snapshot,
                    'version_snapshot' => $changelog->version_snapshot,
                    'change_type' => $changelog->change_type,
                    'created_at' => $changelog->created_at,
                ]
            ]);
        }

        // Get change log history for the type
        $typeUid = Methods::policyTypes()->getUidForType($type);
        $history = Methods::policyChangeLogs()->getTypeHistory($typeUid);
        Response()->jsonSuccess('', ['versions' => $history]);
    }


    // =====================================================
    // REQUIRES ATTENTION NOTIFICATIONS
    // =====================================================

    /**
     * Get all unresolved admin notifications
     */
    #[NoReturn] public static function getAttentionNotifications(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $handler = Methods::requiresAttentionNotifications();

        // Get filters from request
        // Admin can see all notifications (admin + merchant) unless filtered
        $filters = [
            'resolved' => (int)($args['resolved'] ?? 0),
        ];

        // Only filter by target_audience if explicitly provided
        if (!isEmpty($args['target_audience'] ?? null)) {
            $filters['target_audience'] = $args['target_audience'];
        }

        if (!isEmpty($args['source'] ?? null)) {
            $filters['source'] = $args['source'];
        }
        if (!isEmpty($args['severity'] ?? null)) {
            $filters['severity'] = $args['severity'];
        }
        if (!isEmpty($args['type'] ?? null)) {
            $filters['type'] = $args['type'];
        }

        $notifications = $handler->getFiltered($filters);
        $stats = $handler->getAdminStats();

        Response()->jsonSuccess('', [
            'notifications' => $notifications->toArray(),
            'count' => $notifications->count(),
            'stats' => $stats,
        ]);
    }

    /**
     * Mark an admin notification as resolved
     */
    #[NoReturn] public static function resolveAttentionNotification(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $notificationUid = $args['uid'] ?? null;
        if (isEmpty($notificationUid)) {
            Response()->jsonError('Mangler notification ID', [], 400);
        }

        $handler = Methods::requiresAttentionNotifications();
        $notification = $handler->get($notificationUid);

        if (isEmpty($notification)) {
            Response()->jsonError('Notification ikke fundet', [], 404);
        }

        // Mark as resolved
        $success = $handler->markResolved($notificationUid, __uuid());

        if (!$success) {
            Response()->jsonError('Kunne ikke markere som løst', [], 500);
        }

        Response()->jsonSuccess('Notification markeret som løst');
    }

    /**
     * Get admin notification stats
     */
    #[NoReturn] public static function getAttentionNotificationStats(array $args): void {
        if (!Methods::isAdmin()) {
            Response()->jsonError('Adgang nægtet', [], 403);
        }

        $stats = Methods::requiresAttentionNotifications()->getAdminStats();
        Response()->jsonSuccess('', ['stats' => $stats]);
    }

}