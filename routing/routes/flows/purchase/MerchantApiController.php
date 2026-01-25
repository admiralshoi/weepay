<?php

namespace routing\routes\flows\purchase;

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\lang\Translate;
use classes\Methods;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class MerchantApiController {







    #[NoReturn] public static function getTerminalSessions(array $args): void {
        $terminalId = $args['id'];
        $handler = Methods::terminals();
        $sessionHandler = Methods::terminalSessions();
        $terminal = $handler->get($terminalId);
        if(isEmpty($terminal)) Response()->jsonError("Kunne ikke finde terminalen", [$args], 404);
        if($terminal->status !== 'ACTIVE') Response()->jsonError("Ugyldig terminal", [], 403);
        if(is_string($terminal->location->uuid)) $terminal->location->uuid = $terminal->uuid;
        if($terminal->location->status !== 'ACTIVE') Response()->jsonError("Ugyldig lokation", [], 403);
        if(!LocationPermissions::__oRead($terminal->location, "checkout"))
            Response()->jsonError("Du har ikke tilladelse til denne handling", [], 401);



        $sessions = $sessionHandler->getAvailableSessions($terminalId);
        if($sessions->empty()) Response()->jsonSuccess("No sessions are pending", ['sessions' => $sessions->toArray()]);
        $sessions = $sessions->map(function($session) use ($terminal) {
            $session['dateFormat'] = date("H:i", strtotime($session['created_at']));
            $session['link'] = __url(Links::$merchant->terminals->posDetails($terminal->location->slug, $terminal->uid, $session['uid']));
            if(isEmpty($session['customer'])) $session['customer'] = ['name' => "Ukendt"];
            elseif(isset($session['customer']['full_name'])) $session['customer']['name'] = $session['customer']['full_name'];
            elseif(isEmpty($session['customer']['name'])) $session['customer']['name'] = "Ukendt";
            $session['hash'] = hash("sha256", json_encode($session));
            return $session;
        });

        Response()->jsonSuccess("No sessions are pending", ['sessions' => $sessions->toArray()]);
    }
    #[NoReturn] public static function deleteTerminalSession(array $args): void {
        $sessionId = $args['id'];
        $sessionHandler = Methods::terminalSessions();
        $session = $sessionHandler->get($sessionId);
        if(isEmpty($session)) Response()->jsonError("Kunne ikke finde sessionen", [$args], 404);
        if(!in_array($session->state, ['ACTIVE', 'PENDING'])) Response()->jsonSuccess("Sessionen er blevet fjernet");
        if(is_string($session->terminal->location->uuid)) $session->terminal->location->uuid = $session->terminal->uuid;
        if(!LocationPermissions::__oDelete($session->terminal->location, "checkout"))
            Response()->jsonError("Du har ikke tilladelse til denne handling", [], 401);

        $basketHandler = Methods::checkoutBasket();
        $currentActiveBasket = $basketHandler->getActiveBasket($sessionId);
        if(!isEmpty($currentActiveBasket)) $basketHandler->setVoid($currentActiveBasket->uid);
        $sessionHandler->setVoid($sessionId);

        if(array_key_exists("restart", $args) && $args['restart']) {
            Response()
                ->setRedirect(
                    __url(Links::$merchant->terminals->posStart(
                        $session->terminal->location->slug,
                        $session->terminal->uid
                    ))
                )
                ->jsonSuccess("Købet er blevet annulleret");
        }
        Response()->jsonSuccess("Sessionen er blevet fjernet");



    }


    #[NoReturn] public static function createPosBasket(array $args): void {
        foreach (['name', 'price', 'id'] as $key) if(!array_key_exists($key, $args) || empty($args[$key]))
            Response()->jsonError("Venlgist udfyld alle påkrævet felter", [], 400);
        $sessionId = $args['id'];
        $name = trim($args['name']);
        $price = (float)$args['price'];
        $note = array_key_exists("note", $args) && !empty($args['note']) ? trim($args['note']) : null;

        $sessionHandler = Methods::terminalSessions();
        $session = $sessionHandler->get($sessionId);
        if(isEmpty($session)) Response()->jsonError("Kunne ikke finde sessionen", [$args], 404);
        if($session->state !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv.");
        if($session->terminal->state !== 'ACTIVE') Response()->jsonError("Terminalen er ikke aktiv.");
        if($session->session !== $session->terminal->session) Response()->jsonError("Sessionen matcher ikke terminalens aktive session.");
        if(is_string($session->terminal->location->uuid)) $session->terminal->location->uuid = $session->terminal->uuid;
        if(!LocationPermissions::__oModify($session->terminal->location, "checkout"))
            Response()->jsonError("Du har ikke tilladelse til denne handling", [], 401);

        if(strlen($name) > 100) Response()->jsonError("Beskrivelsen kan ikke være længere end 100 tegn", [], 400);
        if($note !== null && strlen($note) > 1000) Response()->jsonError("Noten kan ikke være længere end 1000 tegn", [], 400);
        if($price <= 0) Response()->jsonError("Prisen bør være større end 0kr", [], 400);

        $basketHandler = Methods::checkoutBasket();
        $currentActiveBasket = $basketHandler->getActiveBasket($sessionId);
        if(!isEmpty($currentActiveBasket)) $basketHandler->setVoid($currentActiveBasket->uid);
        $basketId = $basketHandler->setNew($sessionId, $name, $price, Methods::locations()->tradingCurrency($session->terminal->location), $note, __uuid());
        if(isEmpty($basketId)) Response()->jsonError("Noget gik galt.", [], 500);
        Response()->jsonSuccess("Kurven er blevet oprettet", ['id' => $basketId]);
    }



    #[NoReturn] public static function voidBasket(array $args): void {
        foreach (['id'] as $key) if(!array_key_exists($key, $args) || empty($args[$key]))
            Response()->jsonError("Mangler parametre", [], 400);
        $sessionId = $args['id'];

        $sessionHandler = Methods::terminalSessions();
        $session = $sessionHandler->get($sessionId);
        if(isEmpty($session)) Response()->jsonError("Kunne ikke finde sessionen", [$args], 404);
        if($session->state !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv.");
        if($session->terminal->state !== 'ACTIVE') Response()->jsonError("Terminalen er ikke aktiv.");
        if($session->session !== $session->terminal->session) Response()->jsonError("Sessionen matcher ikke terminalens aktive session.");
        if(is_string($session->terminal->location->uuid)) $session->terminal->location->uuid = $session->terminal->uuid;
        if(!LocationPermissions::__oModify($session->terminal->location, "checkout"))
            Response()->jsonError("Du har ikke tilladelse til denne handling", [], 401);

        $basketHandler = Methods::checkoutBasket();
        $currentActiveBasket = $basketHandler->getActiveBasket($sessionId);
        if(!isEmpty($currentActiveBasket)) $basketHandler->setVoid($currentActiveBasket->uid);

        Response()
            ->setRedirect(
                __url(Links::$merchant->terminals->posDetails(
                    $session->terminal->location->slug,
                    $session->terminal->uid,
                    $sessionId
                )) . "?ref=$currentActiveBasket->uid"
            )
            ->jsonSuccess("Kurven er blevet nulstillet");
    }






    #[NoReturn] public static function getBasket(array $args): void  {
        foreach (['id'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["id"];
        $basketHandler = Methods::checkoutBasket()->excludeForeignKeys();


        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);

        $basket = null;
        if($terminalSession->terminal->session === $terminalSession->session) {
            $basket = $basketHandler->getActiveBasket($terminalSession->uid);
            if(!isEmpty($basket)) {
                response()->setRedirect(__url(
                    Links::$merchant->terminals->posCheckout(
                        $terminalSession->terminal->location->slug,
                        $terminalSession->terminal->uid,
                        $terminalSessionId
                    )
                ))->jsonSuccess("", compact('basket'));
            }
        }

        response()->jsonSuccess("", compact('basket'));
    }



    #[NoReturn] public static function getTerminalSession(array $args): void  {
        foreach (['id'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);
        $terminalSessionId = $args["id"];

        $terminalSession = Methods::terminalSessions()->get($terminalSessionId);
        if(isEmpty($terminalSession)) Response()->jsonError("Ugyldig session", [], 404);
        if($terminalSession->terminal->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);
        if($terminalSession->terminal->location->status !== 'ACTIVE') Response()->jsonError("Sessionen er ikke aktiv", [], 404);

        $terminalSession->statusTitle = Translate::context("checkout.status.$terminalSession->state");

        // Only handle completion redirect for merchant users (not customers)
        // Customers have their own polling via checkOrderStatus endpoint

        if(Methods::isMerchant()) {
            if($terminalSession->state === 'ACTIVE') {
                $basket = Methods::checkoutBasket()->getFirst(['terminal_session' => $terminalSessionId, 'status' => 'FULFILLED'], ['uid']);
                if(!isEmpty($basket)) {
                    debugLog("SETTING SESSION COMPLETE");
                    Methods::terminalSessions()->setCompleted($terminalSessionId);

                    response()
                        ->setRedirect(__url(Links::$merchant->terminals->posFulfilled(
                            $terminalSession->terminal->location->slug, $terminalSession->terminal->uid, $terminalSessionId
                        )))
                        ->jsonSuccess("Købet blev gennemført uden problemer.", toArray($terminalSession));
                }
            }
            elseif($terminalSession->state === 'COMPLETED') {
                response()
                    ->setRedirect(__url(Links::$merchant->terminals->posFulfilled(
                        $terminalSession->terminal->location->slug, $terminalSession->terminal->uid, $terminalSessionId
                    )))
                    ->jsonSuccess("Købet blev gennemført uden problemer.", toArray($terminalSession));
            }
        }

        response()->jsonSuccess("", toArray($terminalSession));
    }


    #[NoReturn] public static function getTodaysSales(array $args): void {
        foreach (['location'] as $key) if(!array_key_exists($key, $args))
            Response()->jsonError("Mangler parametre", [], 400);

        $locationId = $args['location'];
        $showAll = ($args['all'] ?? '0') === '1';

        $location = Methods::locations()->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Ugyldig lokation", [], 404);
        if(is_string($location->uuid)) $location->uuid = Methods::organisations()->get($location->uuid);
        if(!LocationPermissions::__oRead($location, "checkout"))
            Response()->jsonError("Du har ikke tilladelse til denne handling", [], 401);

        $todayStart = date('Y-m-d 00:00:00');
        $ordersHandler = Methods::orders();
        $basketHandler = Methods::checkoutBasket();
        $slug = $location->slug;

        $query = $ordersHandler->queryBuilder()
            ->where('location', $locationId)
            ->where('status', 'COMPLETED')
            ->where('created_at', '>=', $todayStart)
            ->order('created_at', 'DESC')
            ->limit(20);

        $orders = $ordersHandler->queryGetAll($query);

        $sales = [];
        foreach ($orders->list() as $order) {
            $tsUid = is_object($order->terminal_session) ? $order->terminal_session->uid : $order->terminal_session;
            $basket = $basketHandler->getFirst(['terminal_session' => $tsUid, 'status' => 'FULFILLED']);
            if (isEmpty($basket)) continue;

            // If not showing all, filter to only this user's baskets
            $createdByUid = is_object($basket->created_by) ? $basket->created_by->uid : $basket->created_by;
            if (!$showAll && $createdByUid !== __uuid()) continue;

            $customerName = is_object($order->uuid) ? ($order->uuid->full_name ?? 'Kunde') : 'Kunde';

            // Get cashier name - created_by could be object (foreign key resolved) or string (UID)
            $cashierName = 'Ukendt';
            if (is_object($basket->created_by)) {
                $cashierName = $basket->created_by->full_name ?? 'Ukendt';
            } elseif (!isEmpty($basket->created_by)) {
                $cashierUser = Methods::users()->get($basket->created_by);
                $cashierName = $cashierUser->full_name ?? 'Ukendt';
            }

            $sales[] = [
                'order_uid' => $order->uid,
                'basket_name' => $basket->name,
                'customer_name' => $customerName,
                'cashier_name' => $cashierName,
                'price' => number_format($basket->price, 2, ',', '.'),
                'currency' => currencySymbol($basket->currency),
                'time' => date('H:i', strtotime($basket->created_at)),
                'link' => __url(Links::$merchant->orderDetail($order->uid)),
            ];
        }

        Response()->jsonSuccess("", ['sales' => $sales, 'count' => count($sales)]);
    }

}