<?php

namespace routing\routes\verification;

use classes\enumerations\Links;
use classes\Methods;
use Database\model\Locations;
use JetBrains\PhpStorm\NoReturn;

class MitIdController {


    #[NoReturn] public static function callbackRouter(array $args): void  {
        if(!array_key_exists('ref', $args)) Response()->jsonError('Unknown ref.', [], 400);
        $ref = $args["ref"];
        $handler = Methods::oidcSession();
        $session = $handler->get($ref);
        testLog($session, 'oidc-cb-session');
        if(isEmpty($session)) Response()->jsonError("Session not found", [], 404);
        if(!in_array($session->status, ['DRAFT', 'PENDING'])) Response()->jsonError("Denied.", [], 401);

        $response = $handler->getProviderSession($session->prid);
        testLog($response, 'oidc-cb-response-' . time());

        switch ($response?->status) {
            default:
                $handler->statusSuccess($ref);
                break;
            case "ABORT":
                $handler->statusCancelled($ref);
                Response()->jsonError('Verificeringen blev stoppet', [], 400);
            case "CREATED":
                $handler->statusVoid($ref);
                Response()->jsonError('Verificeringen blev ikke fuldført', [], 400);
            case "ERROR":
                $handler->statusError($ref);
                Response()->jsonError('Der opstod en fejl. Prøv igen senere.', [], 400);
        }

        $info = $session->info;
        $next = property_exists($info, "next") ? $info->next : null;
        $tsId = property_exists($info, "tsid") ? $info->tsid : null;

        switch ($next) {
            default: Response()->jsonError('Unknown handling.', [], 400);
            case 'cpf':
                if(empty($tsId)) Response()->jsonError('Missing tsid.', [], 400);

                $terminalSession = Methods::terminalSessions()->get($tsId);
                if(isEmpty($terminalSession)) Response()->jsonError('Invalid tsid.', [], 400);
                if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING'])) Response()->jsonError('The session has expired.', [], 410);

                sleep(2);
                Response()->redirect(
                    __url(
                        "merchant/" . $terminalSession->terminal->location->slug .
                        '/checkout/info?' .
                        http_build_query(['tsid' => $terminalSession->uid, 'sid' => $ref])
                    )
                );

            case 'consumer_login':
            case 'consumer_signup':
                // Handle consumer authentication (failsafe if popup didn't work)
                $authHandler = Methods::oidcAuthentication();

                $authId = $authHandler->login($response);
                if(empty($authId)) Response()->jsonError('Verificeringen fejlede.', [], 500);

                // Get user to check if profile completion is needed
                $user = Methods::users()->get(__uuid());
                if(isEmpty($user)) Response()->jsonError('Brugeren kunne ikke findes.', [], 500);
                Response()->redirect(__url(Links::$consumer->dashboard));
        }
    }

}