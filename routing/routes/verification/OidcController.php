<?php

namespace routing\routes\verification;

use classes\enumerations\Links;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;
use routing\routes\ErrorController;

class OidcController {

    #[NoReturn] public static function sessionPolling(array $args): void {
        $id = $args['id'];
        $handler = Methods::oidcSession();
        $session = $handler->get($id);
        if(isEmpty($session)) Response()->jsonError("Session not found");
        $providerSession = $handler->getProviderSession($session->prid);
        testLog($providerSession, 'polling-oidc-provider-session');

        if($session->status === 'SUCCESS') {
            $info = $session->info;
            $next = property_exists($info, "next") ? $info->next : null;
            $tsId = property_exists($info, "tsid") ? $info->tsid : null;

            if($next === 'cpf' && !empty($tsId)) {
                $terminalSession = Methods::terminalSessions()->get($tsId);
                if(isEmpty($terminalSession)) Response()->jsonError('Invalid tsid.', [], 400);
//                if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING'])) Response()->jsonError('The session has expired.', [], 410);

                Response()->setRedirect(
                    __url(
                        "merchant/" . $terminalSession->terminal->location->slug .
                        '/checkout/info?' .
                        http_build_query(['tsid' => $terminalSession->uid, 'sid' => $id])
                    )
                )->jsonSuccess("", toArray($session));
            }

            if(in_array($next, ['consumer_login', 'consumer_signup'])) {
                if(!isOidcAuthenticated()) Response()->jsonError('Kunne ikke verificere dig. Prøv igen', [], 500);
                $user = Methods::users()->get(__uuid());
                if(isEmpty($user)) Response()->jsonError('User not found.', [], 500);

                $redirectUrl = __url(Links::$consumer->dashboard);
                Response()->setRedirect($redirectUrl)->jsonSuccess("", toArray($session));
            }
        }


        Response()->jsonSuccess("", toArray($session));
    }

    public static function preAuthPage(array $args) {
        $id = $args['id'];
        $handler = Methods::oidcSession();
        $session = $handler->get($id);
        if(isEmpty($session)) return ErrorController::e404();
        if($session->expires_at < time()) {
            $handler->statusTimeout($id);
            return self::expiredPage($session);
        }
        if(!in_array($session->status, ['DRAFT', 'PENDING'])) return self::expiredPage($session);

        $providerSession = $handler->getProviderSession($session->prid);
        testLog($providerSession, 'preauth-oidc-provider-session');
        if(!property_exists($providerSession, 'id')) {
            debugLog($providerSession, 'oidc-session-issue');
            return ErrorController::e404();
        }
        debugLog($providerSession, 'oidc-session');
        if($session->status === 'DRAFT') $handler->statusPending($id);
        Response()->redirect('', $providerSession->authenticationUrl);
    }

    public static function expiredPage(
        ?object $session = null,
        ?object $terminalSession = null,
        ?string $prevUrl = null,
        ?string $prevUrlTitle = null
    ): ?array {
        if(isEmpty($session) && isEmpty($terminalSession) && empty($prevUrl)) return null;
        if(!isEmpty($terminalSession)) {
            if(empty($prevUrl))
                $prevUrl = __url("merchant/{$terminalSession->terminal->location->slug}/checkout?tid={$terminalSession->terminal->uid}");

            if(empty($prevUrlTitle))
                $prevUrlTitle = "Tilbage til " . $terminalSession->terminal->location->name;
        }
        else {
            if(empty($prevUrl)) {
                $query = $session->info;
                $next = property_exists($query, "next") ? $query->next : null;
                $prevUrl = __url();
                $prevUrlTitle = "Tilbage";
                switch ($next) {
                    default:
                        $prevUrl = __url();
                        break;
                    case "cpf":
                        $tsId = property_exists($query, "tsid") ? $query->tsid : null;
                        if(!empty($tsId)) {
                            $terminalSession = Methods::terminalSessions()->get($tsId);
                            if(!isEmpty($terminalSession)) {
                                $prevUrl = __url("merchant/{$terminalSession->terminal->location->slug}/checkout?tid=$tsId");
                                $prevUrlTitle = "Tilbage til " . $terminalSession->terminal->location->name;
                            }
                        }
                        break;
                }
            }

        }






        return ErrorController::expired(compact('prevUrl', 'prevUrlTitle'));
    }



}