<?php
namespace routing\routes\api;

use JetBrains\PhpStorm\ArrayShape;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class WebhookController {





    #[NoReturn] public static function meta(array $args, $hookLog = true): void {
        if($hookLog) testLog($args, "hook-" . time(), "json");
        if(array_key_exists("hub_challenge", $args)) $type = "challenge";
        elseif(array_key_exists("entry", $args)) $type = "event";
        else Response()->jsonError("Invalid webhook request");

        if($type === "challenge") Response()->json(Methods::metaWebhook()->challenge($args));

        $expectedRawKeys = ["entry", "id"];
        foreach ($expectedRawKeys as $expectedRawKey) {
            if(!str_contains(json_encode($args),$expectedRawKey)) {
                $error = "Expected the raw entry to contain key $expectedRawKey, but it did not.";
                hookLog($error);
                Response()->jsonError($error);
            }
        }

        /*
         * Sometimes we'll see entries not containing a comment_id. Perhaps because it's a caption. For now- we filter that away
         */
        Response()->jsonSuccess(Methods::metaWebhook()->event($args));
    }






    #[NoReturn] public static function metaForward(array $args, $hookLog = true): array {
        if($hookLog) testLog($args, "hook-" . time(), "json");
        if(!array_key_exists("forward_key", $args) || $args["forward_key"] !== strtolower(FORWARD_KEY))
            Response()->jsonError("Invalid forward key", $args, 400);
        unset($args["forward_key"]);
        Response()->jsonSuccess(Methods::metaWebhook()->event($args));
    }















    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    private static function returnJsonResponse(string|array|null $res, int $responseCode = 200): array {
        return [
            "result" => $res,
            "response_code" => $responseCode
        ];
    }
}