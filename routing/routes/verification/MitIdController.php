<?php

namespace routing\routes\verification;

use classes\Methods;
use Database\model\Locations;
use JetBrains\PhpStorm\NoReturn;

class MitIdController {


    #[NoReturn] public static function callbackRouter(array $args): void  {
        $status = $args["status"];
        if(!array_key_exists('next', $args)) Response()->jsonError('Unknown next action.', [], 400);
        if(!array_key_exists('sessionId', $args)) {
            $status = 'error';
            $sessionId = null;
        }
        else $sessionId = $args["sessionId"];
        $next = $args["next"];


        $session = Methods::signicact()->getSession($sessionId);
        if($session['status'] !== 'SUCCESS') Response()->jsonError('Unable to authenticate.', [], 401);

        switch ($next) {
            default: Response()->jsonError('Unknown next action.', [], 400);
            case 'cpf':
                if(!array_key_exists('tsid', $args)) Response()->jsonError('Missing tsid.', [], 400);

                $terminalSession = Methods::terminalSessions()->get($args['tsid']);
                if(isEmpty($terminalSession)) Response()->jsonError('Invalid tsid.', [], 400);
                if(!in_array($terminalSession->state, ['ACTIVE', 'PENDING'])) Response()->jsonError('The session has expired.', [], 410);

                Response()->redirect(
                    __url(
                        "merchant/" . $terminalSession->terminal->location->slug .
                        '/checkout/info?' .
                        http_build_query(['tsid' => $terminalSession->uid, 'sid' => $sessionId])
                    )
                );

        }
    }

}