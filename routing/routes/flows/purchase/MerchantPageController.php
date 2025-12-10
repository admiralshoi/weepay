<?php

namespace routing\routes\flows\purchase;

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use routing\routes\ErrorController;
use routing\routes\verification\OidcController;

class MerchantPageController {

    public static function posStart(array $args): mixed {
        $terminalId = $args['id'];
        $handler = Methods::terminals();
        $terminal = $handler->get($terminalId);
        if(isEmpty($terminal)) return null;
        if($terminal->status !== 'ACTIVE') return null;
        if(is_string($terminal->location->uuid)) $terminal->location->uuid = $terminal->uuid;
        if(!LocationPermissions::__oRead($terminal->location, "checkout")) return null;
        if(!empty($terminal->session) || $terminal->state !== 'IDLE') $handler->setIdle($terminalId);

        Methods::terminalSessions()->unsetAllActiveByTerminalId($terminalId);

        return Views("MERCHANT_POS_START", compact('terminal', 'terminalId'));
    }

    public static function posDetails(array $args): mixed {
        $terminalId = $args['id'];
        $slug = $args['slug'];
        $sessionId = $args['tsid'];
        $handler = Methods::terminals();
        $sessionHandler = Methods::terminalSessions();


        $session = $sessionHandler->get($sessionId);
        if(isEmpty($session)) return null;
        $terminal = $session->terminal;
        if($terminal?->status !== 'ACTIVE') return null;
        if($session->state === 'VOID') {
            $prevUrl = __url(
                Links::$merchant->terminals->posStart($terminal->location->slug, $terminal->uid)
            );
            $prevUrlTitle = "Tilbage til POS start";
            return OidcController::expiredPage(null, null, $prevUrl, $prevUrlTitle);
        }
        if(is_string($terminal->location->uuid)) $terminal->location->uuid = $terminal->uuid;
        if(!LocationPermissions::__oRead($terminal->location, "checkout")) return null;

        $sessionHandler->unsetAllActiveByTerminalId($terminalId);
        $sessionHandler->setActive($sessionId);


        if($terminal->state !== 'ACTIVE') {
            $handler->setActiveSession($terminalId, $session->session);
            $terminal = $handler->get($terminalId);
        }

        if($terminal->session === $session->session) {
            $basket = Methods::checkoutBasket()->getActiveBasket($sessionId);
            if(!isEmpty($basket)) {
                Response()->redirect(Links::$merchant->terminals->posCheckout($slug, $terminal->uid, $sessionId));
            }
        }

        $referenceBasket = null;
        if(array_key_exists("ref", $args)) {
            $basket = Methods::checkoutBasket()->excludeForeignKeys()->get($args['ref']);
            if(!isEmpty($basket) && $basket->terminal_session === $sessionId) $referenceBasket = $basket;
        }

        return Views("MERCHANT_POS_DETAILS", compact('session', 'slug', 'referenceBasket'));
    }


    public static function posCheckout(array $args): mixed {
        $slug = $args['slug'];
        $sessionId = $args['tsid'];
        $sessionHandler = Methods::terminalSessions();

        $session = $sessionHandler->get($sessionId);
        if(isEmpty($session)) return null;
        $terminal = $session->terminal;
        if($terminal?->status !== 'ACTIVE') return null;
        if($session?->state !== 'ACTIVE') {
            $prevUrl = __url(
                Links::$merchant->terminals->posStart($terminal->location->slug, $terminal->uid)
            );
            $prevUrlTitle = "Tilbage til POS start";
            return OidcController::expiredPage(null, null, $prevUrl, $prevUrlTitle);
        }
        if(is_string($terminal->location->uuid)) $terminal->location->uuid = $terminal->uuid;
        if(!LocationPermissions::__oRead($terminal->location, "checkout")) return null;

        $basket = Methods::checkoutBasket()->getActiveBasket($sessionId);
        if(isEmpty($basket)) return null;
        $customer = $session->customer;

        return Views("MERCHANT_POS_CHECKOUT", compact('session', 'slug', 'terminal', 'customer', 'basket'));
    }

}