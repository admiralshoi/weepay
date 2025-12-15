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

        // Extract context info for error handling
        $info = $session->info;
        $next = property_exists($info, "next") ? $info->next : null;
        $tsId = property_exists($info, "tsid") ? $info->tsid : null;

        // Check for errors and redirect appropriately
        $errorMessage = null;
        switch ($response?->status) {
            default:
                $handler->statusSuccess($ref);
                break;
            case "ABORT":
                $handler->statusCancelled($ref);
                $errorMessage = 'Verificeringen blev annulleret. Prøv venligst igen.';
                break;
            case "CREATED":
                $handler->statusVoid($ref);
                $errorMessage = 'Verificeringen blev ikke fuldført. Prøv venligst igen.';
                break;
            case "ERROR":
                $handler->statusError($ref);
                $errorMessage = 'Der opstod en fejl under verificeringen. Prøv igen senere.';
                break;
        }

        // If there's an error, redirect to appropriate error page
        if(!isEmpty($errorMessage)) {
            switch ($next) {
                case 'cpf':
                    // Checkout flow - redirect back to start page with error
                    if(!empty($tsId)) {
                        $terminalSession = Methods::terminalSessions()->get($tsId);
                        if(!isEmpty($terminalSession)) {
                            Response()->redirect(
                                __url(
                                    "merchant/" . $terminalSession->terminal->location->slug .
                                    '/checkout?' .
                                    http_build_query(['tid' => $terminalSession->terminal->uid, 'auth_error' => $errorMessage])
                                )
                            );
                        }
                    }
                    // Fallback if we can't determine checkout context
                    Response()->jsonError($errorMessage, [], 400);

                case 'consumer_login':
                    // Redirect back to login page with error
                    Response()->redirect(
                        __url(Links::$app->auth->consumerLogin . '?' . http_build_query(['auth_error' => $errorMessage]))
                    );

                case 'consumer_signup':
                    // Redirect back to signup page with error
                    Response()->redirect(
                        __url(Links::$app->auth->consumerSignup . '?' . http_build_query(['auth_error' => $errorMessage]))
                    );

                default:
                    Response()->jsonError($errorMessage, [], 400);
            }
        }

        // Success flow - continue with authentication
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
                if(empty($authId)) {
                    // Authentication failed - redirect back with error
                    $redirectUrl = $next === 'consumer_login'
                        ? Links::$app->auth->consumerLogin
                        : Links::$app->auth->consumerSignup;
                    Response()->redirect(
                        __url($redirectUrl . '?' . http_build_query(['auth_error' => 'Verificeringen fejlede. Prøv venligst igen.']))
                    );
                }

                // Get user to check if profile completion is needed
                $user = Methods::users()->get(__uuid());
                if(isEmpty($user)) {
                    $redirectUrl = $next === 'consumer_login'
                        ? Links::$app->auth->consumerLogin
                        : Links::$app->auth->consumerSignup;
                    Response()->redirect(
                        __url($redirectUrl . '?' . http_build_query(['auth_error' => 'Brugeren kunne ikke findes. Prøv venligst igen.']))
                    );
                }
                Response()->redirect(__url(Links::$consumer->dashboard));
        }
    }

}