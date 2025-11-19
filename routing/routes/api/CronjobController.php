<?php

namespace routing\routes\api;

use classes\app\CronWorker;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;
use classes\Methods;
use JetBrains\PhpStorm\NoReturn;

class CronjobController {





    #[NoReturn] public static function manualExtra(): array {
        require_once __view("testing/simplif-3.php");
        Response()->jsonSuccess("Finished running cronjob");
    }





    #[NoReturn] public static function affiliatePayPeriod(): array {
        $worker = self::init("affiliate_pay_period");
        if($worker === null) Response()->jsonError("Cronjob may not be initiated.", [], 202);

        Methods::cronRequestHandler()->affiliatePayPeriod($worker);

        self::end($worker);
        Response()->jsonSuccess("Finished running cronjob");
    }




    #[ArrayShape(["result" => "\array|null|string", "response_code" => "int"])]
    public static function accountAnalytics(array $args): array {
        $worker = self::init("account_insights");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $creatorItems = $requestHandler->findCreatorsToQueryAccountAnalytics($worker);
        $worker->log("Found " . $creatorItems->count() . " creators to fetch account insights and analytics from.");

        if (!$creatorItems->empty()) {
            $worker->log("Proceeding...");
            $requestHandler->queryAccountAnalytics($creatorItems, $worker);
        }

        return self::end($worker);
    }









    #[ArrayShape(["result" => "\array|null|string", "response_code" => "int"])]
    public static function campaignMediaUpdate(): array {
        $worker = self::init("media_update");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $mediasToUpdate = $requestHandler->getCreatorMediaToUpdate($worker);
        $worker->log("Found " . $mediasToUpdate->count() . " media to update.");

        if (!$mediasToUpdate->empty()) {
            $worker->log("Proceeding...");
            $requestHandler->queryCampaignMedias($mediasToUpdate, $worker);
        }

        return self::end($worker);
    }





    #[NoReturn] public static function hashtagTracking(): array {
        $worker = self::init("hashtag_tracking");
        if($worker === null) Response()->jsonError("Cronjob may not be initiated.", [], 202);

        $requestHandler = Methods::cronRequestHandler();
        $hashtagsToTrack = Methods::cronRequestHandler()->findHashtagsToTrack($worker);
        $worker->log("Found " . $hashtagsToTrack->count() . " hashtag(s) to track.");

        if (!$hashtagsToTrack->empty()) {
            $worker->log("Proceeding...");
            $requestHandler->hashtagTracking($hashtagsToTrack, $worker);
        }

        self::end($worker);
        Response()->jsonSuccess("Finished running cronjob");
    }






    #[NoReturn] public static function contentTagMention(): void {
        $worker = self::init("tag_mentions");
        if($worker === null) Response()->jsonError("Cronjob may not be initiated.", [], 202);

        $requestHandler = Methods::cronRequestHandler();
        $uniqueItems = $requestHandler->tagMentionFlowFindPosts($worker);
        $worker->log("Found " . $uniqueItems->count() . " integrations to Tag-track");

        if (!$uniqueItems->empty()) {
            $worker->log("Proceeding...");
            $requestHandler->runTagMentionQueries($uniqueItems, $worker);

            $campaignIds = [];
            foreach ($uniqueItems->toArray() as $item) {
                $campaignIds = array_merge($campaignIds, array_map(function ($campaign) { return $campaign["campaign"]["id"]; }, $item["items"]));
            }
            $worker?->log("Finished processing media queries. " . count($campaignIds) . " campaign's will be updated.");
            Methods::campaigns()->update(["last_tag_discovery" => time()], ["id" => $campaignIds]);
        }

        self::end($worker);
        Response()->jsonSuccess("Finished running cronjob");
    }




    #[ArrayShape(["result" => "\array|null|string", "response_code" => "int"])]
    public static function eventMode(): array {
        $worker = self::init("event_mode");
        if($worker === null) return self::returnJsonResponse("Cronjob may not be initiated.", 202);

        $requestHandler = Methods::cronRequestHandler();
        $campaignHandler = Methods::campaigns();
        $response = Methods::cronRequestHandler()->getEventModeCampaignsToUpdate($worker);

        if (!$response["actors"]->empty()) {
            $worker->log("Proceeding...");
            $requestHandler->runEventMode($response["actors"], $worker);
        }

        if(!empty($response["campaign_ids"])) {
            $worker->log("Updating campaign ids...");
            foreach ($response["campaign_ids"] as $campaignId) {
                $campaign = $campaignHandler->get($campaignId, ["end"]);
                if(isEmpty($campaign)) continue;
                $params = ["last_event_discovery" => time()];
                if($campaign->end <= time()) $params["event_discovery_active"] = 0;
                $campaignHandler->update($params, ["id" => $campaignId]);
            }
        }

        return self::end($worker);
    }





















    #[ArrayShape(["result" => "\array|null|string", "response_code" => "int"])]
    private static function end(CronWorker $worker): array {
        $worker->log("Finished running cronjob");
        $worker->end();
        return self::returnJsonResponse("Finished running cronjob");
    }

    private static function init(string $type, bool $force = false): ?CronWorker {
        Settings::$omnipotent = true;
        $worker = Methods::cronWorker($type);
        $timeOfInit = time();
        return !$worker->init($timeOfInit, $force) ? null : $worker;
    }





    #[ArrayShape(["result" => "array|null|string", "response_code" => "int"])]
    private static function returnJsonResponse(string|array|null $res, int $responseCode = 200): array {
        return [
            "result" => $res,
            "response_code" => $responseCode
        ];
    }

}