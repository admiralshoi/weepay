<?php

namespace routing\routes\flows\purchase;

use classes\enumerations\Links;
use classes\Methods;
use classes\payments\CardValidationService;
use Database\model\Locations;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;
use routing\routes\verification\OidcController;

class CustomerPageController {

    public static function home(array $args): mixed  {
        $slug = $args['slug'];
        $location = Methods::locations()->getFirst(['slug' => $slug]);
        if(isEmpty($location)) return null;

        // Get published page for this location
        $publishedPage = Methods::locationPages()->getPublished($location->uid);

        // If no published page, use location defaults
        if(isEmpty($publishedPage)) {
            $publishedPage = (object) [
                'uid' => null,
                'state' => 'DRAFT',
                'logo' => $location->logo ?? DEFAULT_LOCATION_LOGO,
                'hero_image' => $location->hero_image ?? DEFAULT_LOCATION_HERO,
                'title' => $location->name,
                'caption' => $location->caption,
                'about_us' => $location->description,
                'credit_widget_enabled' => 1,
                'sections' => []
            ];
        }

        // Check if consumer is logged in and get their BNPL info
        $isConsumerLoggedIn = isLoggedIn() && Methods::isConsumer();
        $bnplLimit = null;
        $hasPastDue = false;

        if($isConsumerLoggedIn) {
            $organisationId = is_object($location->uuid) ? $location->uuid->uid : $location->uuid;
            $bnplLimit = Methods::payments()->getBnplLimit(__uuid(), $organisationId);
            $hasPastDue = Methods::payments()->exists(['uuid' => __uuid(), 'status' => 'PAST_DUE']);
        }

        // Build login URL with redirect back to this page
        $currentPageUrl = "merchant/{$slug}";
        $loginUrl = __url(\classes\enumerations\Links::$app->auth->consumerLogin . '?' . http_build_query(['redirect' => $currentPageUrl]));

        return Views("CUSTOMER_LOCATION_HOME", compact('location', 'publishedPage', 'slug', 'isConsumerLoggedIn', 'bnplLimit', 'hasPastDue', 'loginUrl'));
    }

    public static function start(array $args): mixed  {
        // Detect browser prefetch requests - don't create sessions for these
        $isPrefetch = (
            (isset($_SERVER['HTTP_SEC_PURPOSE']) && $_SERVER['HTTP_SEC_PURPOSE'] === 'prefetch') ||
            (isset($_SERVER['HTTP_PURPOSE']) && $_SERVER['HTTP_PURPOSE'] === 'prefetch') ||
            (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] === 'prefetch') ||
            (isset($_SERVER['HTTP_X_PURPOSE']) && $_SERVER['HTTP_X_PURPOSE'] === 'preview')
        );
        if($isPrefetch) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
            return null;
        }

        $slug = $args["slug"];
        if(!array_key_exists('tid', $args)) return null;
        $terminalId = $args["tid"];
        $locationId = Methods::locations()->getColumn(['slug' => $slug], 'uid');
        if(isEmpty($locationId)) return null;
        $terminal = Methods::terminals()->getByTerminalAndLocationId($terminalId, $locationId);
        if(isEmpty($terminal)) return null;

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminal->location, 'reason' => $paymentCheck['reason']]);
        }

        if($terminal->status !== 'ACTIVE') return null;
        if($terminal->location->status !== 'ACTIVE') return null;
        $session = Methods::terminalSessions()->getSession($terminalId);
        if(isEmpty($session)) return null;

        if(isOidcVerified()) Response()->redirect(
            __url("merchant/{$terminal->location->slug}/checkout/info?tsid={$session->uid}")
        );

        $checkoutQuery = ['tsid' => $session->uid,'next' => 'cpf'];
        $token = crc32(json_encode($checkoutQuery) . "_" . __csrf());
        $existingOidcSession = Methods::oidcSession()->getByToken($token, ['uid', 'expires_at', 'status']);
        $oidcSessionId = null;
        if(!isEmpty($existingOidcSession)) {
            if($existingOidcSession->expires_at <= time() || $existingOidcSession->status !== "DRAFT") {
                Methods::oidcSession()->statusTimeout($existingOidcSession->uid);
                $existingOidcSession = null;
            }
            else $oidcSessionId = $existingOidcSession->uid;
        }

        if(isEmpty($existingOidcSession)) {
            $oidcSessionId = Methods::oidcSession()->setSession(
                "authenticate",
                $checkoutQuery,
                $token,
            );
        }
        if(isEmpty($oidcSessionId)) return null;
        $page = Methods::locationPages()->getFirst(['location' => $locationId, 'state' => 'PUBLISHED']);

        // Get auth error from query params if redirected back from failed OIDC
        $authError = $args['auth_error'] ?? null;

        // Get world countries for phone country code dropdown (for local auth login)
        $worldCountries = Methods::misc()::getCountriesLib(WORLD_COUNTRIES);

        // Check login state for showing appropriate auth options
        $isUserLoggedIn = isLoggedIn();
        $isUserOidcVerified = isOidcVerified();

        return Views("CUSTOMER_PURCHASE_FLOW_START", compact('slug', 'terminal', 'session', 'oidcSessionId', 'page', 'authError', 'worldCountries', 'isUserLoggedIn', 'isUserOidcVerified'));
    }

    public static function info(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $sessionId = array_key_exists('sid', $args) ? $args["sid"] : null;
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;
        $redirectUrl = __url(
            Links::$merchant->terminals->checkoutStart($terminalSession->terminal->location->slug, $terminalSession->terminal->uid)
        );

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminalSession->terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminalSession->terminal->location, 'reason' => $paymentCheck['reason']]);
        }

        if(!in_array($terminalSession->state, ['PENDING', 'ACTIVE'])) Response()->redirect($redirectUrl);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->redirect($redirectUrl);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->redirect($redirectUrl);
        $authHandler = Methods::oidcAuthentication();

        if(!isOidcVerified()) {
            if(empty($sessionId)) Response()->redirect($redirectUrl);
            $session =  Methods::oidcSession()->get($sessionId);
            if($session->status !== 'SUCCESS') Response()->redirect($redirectUrl);
            $providerSession = Methods::oidcSession()->getProviderSession($session->prid);
            testLog($providerSession, 'oidc-session-info-page');
            if($providerSession->status === 'EXPIRED') Response()->redirect($redirectUrl);
            $authId = $authHandler->login($providerSession);
            if(empty($authId)) {
                debugLog("Unable to authenticate user with oidc", 'auth-error');
                Response()->redirect($redirectUrl);
            }
            $customer = $authHandler->get($authId);
        }
        else $customer = $authHandler->getByUserId();
        if(isEmpty($customer)) {
            Response()->redirect($redirectUrl);
        }

        $customer->name = $customer->user?->full_name;
        if(isEmpty($terminalSession->customer)) {
            $terminalSession->customer = $customer?->user;
            Methods::terminalSessions()->update(['customer' => $customer?->user?->uid], ['uid' => $terminalSession->uid]);
        }

        if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING'])) {
            $terminalSession = Methods::terminalSessions()->getSession($terminalSession->terminal->uid);
            $currentUrl = $_SERVER['REQUEST_URI'];
            $parsed = parse_url($currentUrl);
            $path = getUrlPath();
            $query = [];
            if (!empty($parsed['query'])) parse_str($parsed['query'], $query);
            $query['tsid'] = $terminalSession->uid;
            Response()->redirect($path . '?' . http_build_query($query));
        }


        $basket = null;
        $nextStepLink = __url(Links::$merchant->terminals->getConsumerChoosePlan($slug, $terminalSessionId));
        if($terminalSession->terminal->state === 'IDLE') Methods::terminals()->update(['state' => 'AWAITING_MERCHANT'], ['uid' => $terminalSessionId]);
        elseif($terminalSession->terminal->state === 'AWAITING_CUSTOMER'  && $terminalSession->terminal->session === $terminalSession->session) {
            Methods::terminals()->update(['state' => 'ACTIVE'], ['uid' => $terminalSessionId]);
            $basket = Methods::checkoutBasket()->excludeForeignKeys()->getActiveBasket($terminalSession->terminal->uid);
        }

        // Get BNPL limit info
        $customerId = $customer->user?->uid ?? null;
        $location = $terminalSession->terminal->location;
        $organisationId = is_object($location->uuid) ? $location->uuid->uid : $location->uuid;
        $bnplLimit = !isEmpty($customerId) ? Methods::payments()->getBnplLimit($customerId, $organisationId) : null;

        // Check for past due payments
        $hasPastDue = !isEmpty($customerId) && Methods::payments()->exists(['uuid' => $customerId, 'status' => 'PAST_DUE']);

        // Get payment error from query params if redirected back from failed payment
        $paymentError = $args['payment_error'] ?? null;

        $page = Methods::locationPages()->getFirst(['location' => $location->uid, 'state' => 'PUBLISHED']);
        return Views("CUSTOMER_PURCHASE_FLOW_INFO", compact(
            'slug', 'terminalSession', 'customer', 'basket', 'nextStepLink',
            'page', 'bnplLimit', 'paymentError', 'hasPastDue'
        ));
    }

    public static function choosePlan(array $args): mixed  {
        $slug = $args["slug"];
        if(!array_key_exists('tsid', $args)) return null;
        $terminalSessionId = $args["tsid"];
        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) return null;

        // Check if location can accept payments
        $paymentCheck = Methods::locations()->canAcceptPayments($terminalSession->terminal->location);
        if(!$paymentCheck['canAccept']) {
            return Views("CHECKOUT_UNAVAILABLE", ['location' => $terminalSession->terminal->location, 'reason' => $paymentCheck['reason']]);
        }

        if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING']))
            Response()->redirect(__url(Links::$merchant->terminals->checkoutStart(
                $slug, $terminalSession->terminal->uid
            )));

        $customer = Methods::oidcAuthentication()->getByUserId();
        if(isEmpty($customer)) {
            return OidcController::expiredPage(null, $terminalSession);
        }

        if(__uuid() !== $terminalSession->customer->uid)
            Response()->redirect(__url(Links::$merchant->terminals->checkoutStart(
                $slug, $terminalSession->terminal->uid
            )));

        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();

        if($terminalSession->terminal->status !== 'ACTIVE') return null;
        if($terminalSession->terminal->location->status !== 'ACTIVE') return null;
        $basket = $basketHandler->getActiveBasket($terminalSession->uid);


        $defaultToPayNow = 0;
        $defaultPlanId = null;
        $paymentPlans = [];

        // Get customer birthdate and ID for age restriction and BNPL limit
        $birthdate = $customer->user?->birthdate ?? null;
        $customerId = $customer->user?->uid ?? null;

        // Get organisation ID for BNPL limit check
        $location = $terminalSession->terminal->location;
        $organisationId = is_object($location->uuid) ? $location->uuid->uid : $location->uuid;

        // Get BNPL limit info
        $bnplLimit = !isEmpty($customerId) ? Methods::payments()->getBnplLimit($customerId, $organisationId) : null;

        // Check for past due payments
        $hasPastDue = !isEmpty($customerId) && Methods::payments()->exists(['uuid' => $customerId, 'status' => 'PAST_DUE']);

        foreach (Settings::$app->paymentPlans as $name => $plan){
            $plan = $basketHandler->createCheckoutInfo($basket, $name, $birthdate, $customerId, $organisationId);
            if(isEmpty($plan)) continue;

            $paymentPlans[] = $plan;
            if($plan->default) {
                $defaultToPayNow = $plan->to_pay_now;
                $defaultPlanId = $name;
            }
        }

        $previousStepLink = __url("merchant/{$slug}/checkout/info?tsid={$terminalSessionId}");
        $basketHash = hash("sha256", json_encode($basket) . "_" . json_encode($paymentPlans));
        $page = Methods::locationPages()->getFirst(['location' => $terminalSession->terminal->location->uid, 'state' => 'PUBLISHED']);

        return Views("CUSTOMER_PURCHASE_FLOW_PLAN", compact(
            'slug', 'terminalSession', 'customer', 'basket', 'paymentPlans',
            'previousStepLink', 'defaultToPayNow', 'defaultPlanId', 'basketHash', 'bnplLimit', 'page', 'hasPastDue'
        ));
    }




    #[NoReturn] public static function handlePaymentCallback(array $args): void {
        // Get transaction ID from query param
        $transactionId = $args['t'] ?? null;
        $slug = $args['slug'] ?? null;

        // Get order code from query param 's'
        $orderCode = $args['s'] ?? null;

        debugLog([
            'transaction_id' => $transactionId,
            'order_code' => $orderCode,
            'slug' => $slug,
            'all_params' => $args,
            'timestamp' => date('Y-m-d H:i:s.u'),
        ], 'PAYMENT_CALLBACK_ENTRY');

        // For any errors, we need to redirect back to the checkout flow
        // First try to get basic order info for redirect context
        $order = null;
        $terminalSession = null;

        if(!isEmpty($orderCode)) {
            $orderHandler = Methods::orders();
            $order = $orderHandler->getByPrid($orderCode);

            // Check if this is a card_change order - redirect to consumer card change handler
            if(!isEmpty($order) && $order->type === 'card_change') {
                $queryParams = http_build_query($args);
                Response()->redirect(__url("consumer/card-change/callback?{$queryParams}"));
            }

            if(!isEmpty($order)) {
                $terminalSession = $order->terminal_session;
            }
        }

        // Helper function to redirect with error
        $redirectWithError = function($errorMessage) use ($slug, $terminalSession, $order) {
            // Try to redirect to checkout info page if we have terminal session
            if(!isEmpty($terminalSession) && !isEmpty($slug)) {
                Response()->redirect(
                    __url(
                        "merchant/{$slug}/checkout/info?" .
                        http_build_query(['tsid' => $terminalSession->uid, 'payment_error' => $errorMessage])
                    )
                );
            }
            // Fallback: redirect to checkout start if we have terminal
            if(!isEmpty($terminalSession) && !isEmpty($terminalSession->terminal)) {
                Response()->redirect(
                    __url(
                        "merchant/{$slug}/checkout?" .
                        http_build_query(['tid' => $terminalSession->terminal->uid, 'payment_error' => $errorMessage])
                    )
                );
            }
            // Last resort: JSON error
            Response()->jsonError($errorMessage, [], 400);
        };

        if(isEmpty($transactionId)) {
            $redirectWithError("Mangler transaktions ID. Betalingen kunne ikke behandles.");
        }

        if(isEmpty($orderCode)) {
            $redirectWithError("Mangler ordre kode. Betalingen kunne ikke bekræftes.");
        }

        if(isEmpty($order)) {
            $redirectWithError("Ugyldig ordre. Betalingen kunne ikke findes.");
        }

        $merchantId = nestedArray($order, ['location', 'uuid', 'merchant_prid']);
        if(isEmpty($merchantId)) {
            $redirectWithError("Ugyldigt forhandler id. Betalingen kunne ikke behandles.");
        }

        // Get payment details from Viva
        $vivaPayment = Methods::viva()->getPayment($merchantId, $transactionId);
        if(isEmpty($vivaPayment)) {
            $redirectWithError("Kunne ikke hente betalingsoplysninger. Prøv venligst igen.");
        }

        // Map Viva status to our payment status
        $vivaStatus = $vivaPayment['statusId'] ?? null;
        $paymentStatus = match($vivaStatus) {
            'F', 'C' => 'COMPLETED',    // Payment successful
            'E', 'M', 'MA', 'MI', 'ML', 'MW', 'MS' => 'FAILED',       // Unsuccessful
            'R' => 'REFUNDED',     // Refunded
            'X' => 'CANCELLED',    // Cancelled by merchant
            default => 'PENDING'
        };

        // Find the pending payment for this order
        $paymentsHandler = Methods::payments();
        $currentPayment = $paymentsHandler->getFirst(['order' => $order->uid, 'status' => 'PENDING']);


        testLog($vivaPayment, 'viva-payment-pmcb');
        testLog($currentPayment, 'current-payment-pmcb');

        if(!isEmpty($currentPayment)) {
            // Update payment status
            $updateData = [
                'status' => $paymentStatus,
                'prid' => $transactionId,
            ];

            if($paymentStatus === 'COMPLETED') {
                $updateData['paid_at'] = date('Y-m-d H:i:s');
            } elseif(in_array($paymentStatus, ['FAILED', 'CANCELLED'])) {
                $updateData['failure_reason'] = "Viva status: {$vivaStatus}";
            }

            $paymentsHandler->update($updateData, ['uid' => $currentPayment->uid]);
        }

        // Update order status if completed
        if($paymentStatus === 'COMPLETED') {
            Methods::terminalSessions()->setCompleted($order->terminal_session?->uid);
            $basket = Methods::checkoutBasket()->getFirst(['terminal_session' => $order->terminal_session->uid, 'status' => 'FULFILLED']);
            if(isEmpty($basket)) Methods::checkoutBasket()->update(['status' => 'FULFILLED'], ['terminal_session' => $order->terminal_session->uid]);

            // Store initial_transaction_id for BNPL orders (for future recurring charges)
            $isTestOrder = (bool)($order->test ?? false);
            $currency = $order->currency ?? 'DKK';

            if ($order->payment_plan === 'pushed') {
                // For pushed plan: refund the 1 unit validation and store transaction ID
                $validationResult = CardValidationService::processValidationPayment(
                    $merchantId,
                    $orderCode,
                    $currency,
                    $isTestOrder
                );

                if ($validationResult['success'] && !empty($validationResult['transaction_id'])) {
                    $storedTransactionId = $validationResult['transaction_id'];

                    // Get card details from Viva and store payment method
                    $viva = Methods::viva();
                    if ($isTestOrder) {
                        $viva->sandbox();
                    } else {
                        $viva->live();
                    }

                    $paymentInfo = $viva->getPayment($merchantId, $storedTransactionId);
                    $paymentMethodUid = null;

                    if (!isEmpty($paymentInfo)) {
                        $customerUid = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
                        $paymentMethod = Methods::paymentMethods()->createFromVivaTransaction(
                            $customerUid,
                            $paymentInfo,
                            $isTestOrder
                        );
                        $paymentMethodUid = $paymentMethod?->uid;
                    }

                    // Store the transaction ID and payment method on all payments for this order
                    $paymentsHandler->excludeForeignKeys()->update(
                        [
                            'initial_transaction_id' => $storedTransactionId,
                            'payment_method' => $paymentMethodUid,
                        ],
                        ['order' => $order->uid]
                    );

                    debugLog([
                        'orderUid' => $order->uid,
                        'transactionId' => $storedTransactionId,
                        'paymentMethodUid' => $paymentMethodUid,
                    ], 'PUSHED_PAYMENT_METHOD_STORED_CALLBACK');
                } else {
                    errorLog([
                        'orderUid' => $order->uid,
                        'orderCode' => $orderCode,
                        'validationResult' => $validationResult,
                    ], 'pushed-card-validation-failed-callback');
                }
            } elseif ($order->payment_plan === 'installments') {
                // For installments: store the transaction ID from first payment
                if (!empty($transactionId)) {
                    // Get card details from Viva and store payment method
                    $viva = Methods::viva();
                    if ($isTestOrder) {
                        $viva->sandbox();
                    } else {
                        $viva->live();
                    }

                    $paymentInfo = $viva->getPayment($merchantId, $transactionId);
                    $paymentMethodUid = null;

                    if (!isEmpty($paymentInfo)) {
                        $customerUid = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
                        $paymentMethod = Methods::paymentMethods()->createFromVivaTransaction(
                            $customerUid,
                            $paymentInfo,
                            $isTestOrder
                        );
                        $paymentMethodUid = $paymentMethod?->uid;
                    }

                    // Store the transaction ID and payment method on all payments for this order
                    $paymentsHandler->excludeForeignKeys()->update(
                        [
                            'initial_transaction_id' => $transactionId,
                            'payment_method' => $paymentMethodUid,
                        ],
                        ['order' => $order->uid]
                    );

                    debugLog([
                        'orderUid' => $order->uid,
                        'transactionId' => $transactionId,
                        'paymentMethodUid' => $paymentMethodUid,
                    ], 'INSTALLMENTS_PAYMENT_METHOD_STORED_CALLBACK');
                }
            } elseif ($order->payment_plan === 'direct') {
                // For direct plan: store the transaction ID and payment method
                if (!empty($transactionId)) {
                    $viva = Methods::viva();
                    if ($isTestOrder) {
                        $viva->sandbox();
                    } else {
                        $viva->live();
                    }

                    $paymentInfo = $viva->getPayment($merchantId, $transactionId);
                    $paymentMethodUid = null;

                    if (!isEmpty($paymentInfo)) {
                        $customerUid = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
                        $paymentMethod = Methods::paymentMethods()->createFromVivaTransaction(
                            $customerUid,
                            $paymentInfo,
                            $isTestOrder
                        );
                        $paymentMethodUid = $paymentMethod?->uid;
                    }

                    // Store the transaction ID and payment method on the payment for this order
                    $paymentsHandler->excludeForeignKeys()->update(
                        [
                            'initial_transaction_id' => $transactionId,
                            'payment_method' => $paymentMethodUid,
                        ],
                        ['order' => $order->uid]
                    );

                    debugLog([
                        'orderUid' => $order->uid,
                        'transactionId' => $transactionId,
                        'paymentMethodUid' => $paymentMethodUid,
                    ], 'DIRECT_PAYMENT_METHOD_STORED_CALLBACK');
                }
            }

            $orderHandler->setCompleted($order->uid);

            debugLog([
                'order_uid' => $order->uid,
                'order_code' => $orderCode,
                'transaction_id' => $transactionId,
                'payment_plan' => $order->payment_plan,
                'final_status' => 'COMPLETED',
            ], 'PAYMENT_CALLBACK_COMPLETED');

            // Success: redirect to confirmation page
            $confirmationUrl = __url(Links::$checkout->createOrderConfirmation($order->uid));
            response()->redirect('', $confirmationUrl);
        } elseif(in_array($paymentStatus, ['FAILED', 'CANCELLED'])) {
            $orderHandler->setCancelled($order->uid);

            // Failed/Cancelled: redirect back to checkout with error message
            $errorMessage = match($paymentStatus) {
                'FAILED' => 'Betalingen fejlede. Prøv venligst igen eller vælg en anden betalingsmetode.',
                'CANCELLED' => 'Betalingen blev annulleret. Du kan prøve igen hvis du ønsker.',
                default => 'Betalingen kunne ikke gennemføres. Prøv venligst igen.'
            };

            Response()->redirect(
                __url(
                    "merchant/{$slug}/checkout/info?" .
                    http_build_query(['tsid' => $terminalSession->uid, 'payment_error' => $errorMessage])
                )
            );
        } else {
            // Pending/Refunded or other status: redirect back with generic message
            $errorMessage = 'Betalingsstatus kunne ikke bekræftes. Kontakt venligst support hvis beløbet er trukket.';
            Response()->redirect(
                __url(
                    "merchant/{$slug}/checkout/info?" .
                    http_build_query(['tsid' => $terminalSession->uid, 'payment_error' => $errorMessage])
                )
            );
        }
    }


    public static function orderConfirmation(array $args): mixed {
        $orderId = $args['orderid'] ?? null;
        if(isEmpty($orderId)) return null;

        $orderHandler = Methods::orders();
        $order = $orderHandler->get($orderId);
        if(isEmpty($order)) return null;
        if($order->uuid->uid !== __uuid()) return null;

        $payments = Methods::payments()->getByOrder($order->uid);
        $customer = $order->uuid;

        return Views("CUSTOMER_ORDER_CONFIRMATION", compact('order', 'payments', 'customer'));
    }


}