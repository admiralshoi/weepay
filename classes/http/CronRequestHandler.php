<?php
namespace classes\http;

use classes\api\functions\InstagramUtility;
use classes\http\cookies\Cookies;
use classes\http\enum\ExternalItems;
use classes\http\enum\ScraperNestedLists;
use classes\api\Instagram;
use classes\Methods;
use classes\campaigns\CampaignHandler;
use classes\app\CronWorker;
use Database\Collection;
use Database\model\AccountAnalytics;
use Database\model\ActorMedia;
use Database\model\Actors;
use Database\model\CampaignRelations;
use Database\model\Campaigns;
use Database\model\HttpCookies;
use Database\model\ScraperTracking;
use features\Settings;


class CronRequestHandler {


    public function getEventModeCampaignsToUpdate(?CronWorker $worker = null): array {
        $response = ["actors" => new Collection(), "campaign_ids" => []];
        $minTrackingTimeBetween = time() - (3600 * 4) + (60 * 15);

        $campaigns = Campaigns::select([])
            ->where("event", 1)
            ->where("last_event_discovery", "<=", $minTrackingTimeBetween)
            ->where("event_discovery_active", 1)->all();

        $worker?->log($campaigns->count() . " event-campaign(s) were found.");

        if($campaigns->empty()) {
            $worker?->log("No event-campaigns are currently available to use. Terminating...");
            return $response;
        }

        if($campaigns->count() > 5) {
            $worker?->log("Creating a chunk of 10 campaigns to use.");
            $campaigns = $campaigns->grabChunk(10);
        }


        $response["campaign_ids"] = $campaigns->map(function ($campaign) { return $campaign["id"]; })->toArray();
        $campaignRelations = CampaignRelations::select(["creator_id", "campaign_id"])->where("campaign_id", $response["campaign_ids"])->all();

        if($campaignRelations->empty()) {
            $worker?->log("None of the acceptable campaigns have any creators connected. Terminating...");
            return $response;
        }


        $actorIds = $campaignRelations->map(function ($item) { return $item["creator_id"]; })->toArray();
        $response["actors"] = Actors::select(["username", "id", "api", "ig_id"])->where("id", $actorIds)->all();

        if($response["actors"]->empty()) {
            $worker?->log("Failed to fetch any of the associated creators. Terminating...");
            return $response;
        }


        $worker?->log("Returning " . $response["actors"]->count() . " creators whose analytics must be updated");
        return $response;
    }


    public function runEventMode(Collection $actors, ?CronWorker $worker = null): void {
        $integrationHandler = Methods::integrations();
        $mediaHandler = Methods::actorMedia();
        $campaignHandler = Methods::campaigns();
        $api = new Instagram();
        $worker?->log("Running Event mode...");

        foreach ($actors->list() as $actor) {
            if($actor->username === "mariaekena") continue;
            if($actor->api) {
                $worker?->log("Creator '$actor->username': Use Api");
                $integration = $integrationHandler->getByUsername($actor->username, ["item_name", "item_id", "item_token"]);
                if(isEmpty($integration)) {
                    $worker?->log("Creator '$actor->username': Could not find existing integration.");
                    continue;
                }

                $storyData = $api->getStoryMedia($integration->item_id, $integration->item_token);
                if(empty($storyData)) {
                    $worker?->log("Creator '$actor->username': Has no available story-media.");
                    continue;
                }

            }
            else {
                $worker?->log("Creator '$actor->username': Use Scraper");
                $storyData = Methods::actors()->scrapeUserStories($actor->username, $actor->ig_id);
                if(empty($storyData) || array_key_exists("error", $storyData)) continue;
                $storyData = $storyData["data"];
            }

            $worker?->log("Creator '$actor->username':  Has  " . count($storyData) . " active stories");

            $mappedMedia = array_map(function ($media) use ($actor) {
                $media["username"] = $actor->username;
                $media["actor_id"] = $actor->id;
                $media["api"] = $actor->api;
                $media["campaign"] = 1;
                $media["type"] = "story";
                $media["origin"] = $actor->api ? "event_api" : "event_scrape";
                return $media;
            }, $storyData);



            $mappedMedia = (new InstagramUtility())->removeStoryDuplicates($mappedMedia, $actor->username, $actor->api);
            if(empty($mappedMedia)) {
                $worker?->log("Creator '$actor->username': After removing duplicates, no story media are left.");
                continue;
            }
            $worker?->log("Creator '$actor->username':  After removing direct duplicates, the creator has  " . count($mappedMedia) . " stories to insert/update");



            $mappedMedia = $mediaHandler->processMedia($mappedMedia);
            foreach ($mappedMedia as $media) {
                $mediaHandler->insertNewMedia(
                    $mappedMedia,
                    $actor->id,
                    $actor->username,
                    $campaignHandler->getMediaActiveCreatorCampaignIds($media, "story", $actor->id, $actor->username, ["event" => 1]),
                    true
                );
            }
        }
    }


    public function findCreatorsToQueryAccountAnalytics(?CronWorker $worker = null, array $extraParams = []): Collection {
        $minTrackingTimeBetween = time() - (3600 * 24);
        $actorHandler = Methods::actors();
        $creatorIntegrations = Methods::integrations()->getByX(array_merge(["is_creator" => 1, "provider" => "instagram"], $extraParams));
        $worker?->log("A total of " . $creatorIntegrations->count() . " creator integrations were pulled");
        debugLog("A total of " . $creatorIntegrations->count() . " creator integrations were pulled");
        $collection = new Collection();
        if($creatorIntegrations->empty()) return $collection;

        $collector = [];
        foreach ($creatorIntegrations->list() as $integration) {
            $creator = $actorHandler->getByUsername($integration->username, 0, ["uid"]);
            debugLog($creator, "toquery-creator");
            if(isEmpty($creator)) continue;

            $pullCount = AccountAnalytics::select(["created_at"])
            ->where("actor_id", $creator->uid)
            ->whereTimeBefore("created_at", $minTrackingTimeBetween, "<=")
            ->count();
            debugLog($pullCount, "toquery-latestpull-count");

            if($pullCount > 0) continue;

            $collector[] = [
                "actor_id" => $creator->uid,
                "integration_id" => $integration->uid,
                "access_token" => $integration->token,
                "account_id" => $integration->prid,
            ];
        }
        $worker?->log("Returning " . count($collector) . " creators whose analytics must be updated");
        debugLog("Returning " . count($collector) . " creators whose analytics must be updated");
        return empty($collector) ? $collection : $collection->setItems($collector)->grabChunk(20);
    }


    public function queryAccountAnalytics(Collection $creatorItems, ?CronWorker $worker = null): void {
        $actorHandler = Methods::actors();
        $dataHandler = Methods::dataHandler();
        $analyticsHandler = Methods::analytics();

        $api = new Instagram();

        $worker?->log("Looping " . $creatorItems->count() . " creators whose analytics must be updated");
        debugLog("Looping " . $creatorItems->count() . " creators whose analytics must be updated");
        foreach ($creatorItems->list() as $item) {
            $actorId = $item->actor_id;
            $accountId = $item->account_id;
            $accessToken = $item->access_token;

            $creatorData = $api->accountInsight($accountId, $accessToken);
            if(empty($creatorData) || array_key_exists("error", $creatorData)) {
                $worker?->log("Empty or error thrown: " . json_encode($creatorData));
                debugLog("Empty or error thrown: " . json_encode($creatorData));
                continue;
            }
            testLog($creatorData, "creatordata");
            $worker?->log("Pulled account basic insights");
            debugLog("Pulled account basic insights");

            $creatorData = $dataHandler->downloadMediasAndUpdateUrl($creatorData, false, "profile-picture", "username")[0];
            $actorHandler->update($creatorData, ["uid" => $actorId]);
            $worker?->log("Account basic insights updated");
            debugLog("Account basic insights updated");

            $analyticInsights = $api->accountReachInsight($accountId, $accessToken);
            $demoInsights = $api->accountDemographicInsight($accountId, $accessToken);
            debugLog($demoInsights, "demo-insight-returned");
            if(!array_key_exists("error", $demoInsights)) $analyticInsights = array_merge($analyticInsights, $demoInsights);
            $worker?->log("Pulled account demographic -and reach -insights");
            debugLog("Pulled account demographic -and reach -insights");
            if(empty($analyticInsights) || array_key_exists("error", $analyticInsights)) continue;

            $analyticInsights["actor"] = $actorId;

            testLog($analyticInsights, "insightsAccount");

            $analyticsHandler->create($analyticInsights);
            $worker?->log("Advanced account insights created");
            debugLog("Advanced account insights created");
        }
    }


    public function findCreatorsToCompleteIntegration(?CronWorker $worker = null, array $extraParams = []): Collection {
        $integrationHandler = Methods::integrations();
        $creatorIntegrations = $integrationHandler->getByX(array_merge(["is_creator" => 1, "provider" => "instagram"], $extraParams));
        $worker?->log("A total of " . $creatorIntegrations->count() . " creator integrations were pulled");
        debugLog("A total of " . $creatorIntegrations->count() . " creator integrations were pulled");

        $collection = new Collection();
        if($creatorIntegrations->empty()) return $collection;

        $collector = [];
        foreach ($creatorIntegrations->list() as $integration) {
            testLog($integration, "int-proc");
            if(Actors::where("username", $integration->username)->where("deactivated", 0)->where("api", 1)->exists()) continue;
            $collector[] = $integration;
        }
        $worker?->log("Returning " . $creatorIntegrations->count() . " integrations to complete");
        debugLog("Returning " . $creatorIntegrations->count() . " integrations to complete");
        return empty($collector) ? $collection : $collection->setItems(array_chunk($collector, 5)[0]);
    }


    public function finishCreatorIntegration(Collection $integrations, ?CronWorker $worker = null): void {
        $worker?->log("Looping " . $integrations->count() . " integrations to complete");
        debugLog("Looping " . $integrations->count() . " integrations to complete");
        $actorHandler = Methods::actors();
        $handler = Methods::handler();

        foreach ($integrations->list() as $integration) {
            $data = $handler->instagramUserLookupApi($integration, "", $worker);
            if(isEmpty($data)) continue;

            $actorHandler->setMeta([
                "data_level" => 1,
                "init_type" => ExternalItems::INITIALIZED_DIRECT,
                "init_by" => $integration->user_id,
                "init_origin" => ExternalItems::ORIGIN_AUTOMATED,
                "api" => 1
            ]);
            $actorHandler->setUserAndMedia($data);
        }
    }


    public function getCreatorMediaToUpdate(?CronWorker $worker = null): Collection {
        $chunkSize = 15;
        $currentTime = time();
        $storyLife = 24 * 3600;
//        $storyUpdateFrequency = $currentTime - Settings::$app->campaign_media_update_frequency;
        $storyUpdateFrequency = $currentTime - 20 * 60;
        $minTrackingTimeBetween = $currentTime - Settings::$app->campaign_media_update_frequency;
        $campaignHandler = Methods::campaigns();
        $mediaHandler = Methods::actorMedia();

        $collection = new Collection();
        $collector = [];
        $campaignMedia = ActorMedia::where("campaign", 1)
            ->where("metric_update_active", 1)
            ->startGroup('OR') // Starting nested group for OR condition
                ->startGroup()
                    ->where('api', 0)
                    ->where('type', '!=', 'story')
                ->endGroup()
                ->orWhere('api', 1)
            ->endGroup()
            ->startGroup('OR') // Starting the second group of conditions
                ->startGroup("AND")
                    ->where('type', '!=', 'story')
                    ->where('last_metric_update',  "<=",  $minTrackingTimeBetween)
                ->endGroup()
                ->startGroup("AND")
                    ->where('type','story')
//                    ->whereColumn("timestamp + $storyLife", '>', $currentTime)
                    ->where('last_metric_update', "<=",  $storyUpdateFrequency)
                ->endGroup()
            ->endGroup()
            ->order("last_metric_update", "ASC")
            ->limit($chunkSize)
            ->all();

        $mediaCount = $campaignMedia->count();
        $worker?->log("campaign-media-update-initial : $mediaCount");
        if($campaignMedia->empty()) return $collection;
        $metricHandler = Methods::metricHandler();

        foreach ($campaignMedia->list() as $media) {
            if($metricHandler->exists(["object_id" => $media->id])) {
                $mediaHandler->update(["metric_update_active" => 0, "maturing" => 0], ["id" => $media->id]);
                $worker?->log("Media $media->id has attached media-metrics. Therefor, it will be skipped and not updated again.");
                continue;
            }


            if($media->type === "story" && $media->timestamp + $storyLife <= $currentTime) {
                $mediaHandler->update(["metric_update_active" => 0, "maturing" => 0], ["id" => $media->id]);
                $worker?->log("Media $media->id is a story and has expired. It will not be updated anymore except for any incoming story_insight webhooks.");
                continue;
            }

            $mediaCampaignIds = $mediaHandler->getCampaignIdsByMediaId($media->id);
            foreach ($mediaCampaignIds as $i => $campaignId) {
                if($campaignHandler->isCampaignActive($campaignId)) break;
                if($mediaHandler->mediaIsMaturing($media)) break;
                if($i === (count($mediaCampaignIds) - 1)) {
                    $mediaHandler->update(["metric_update_active" => 0, "maturing" => 0], ["id" => $media->id]);
                    $worker?->log("$media->id will no longer be updated going forward");
                }
            }

            $creator = Actors::where("id", $media->actor_id)->select(["id", "followers_count"])->first();
            $creatorId = isEmpty($creator) ? 0 : $creator->id;
            $worker?->log("Added creator $media->username for post " . $media->id . " (" . $media->type . ") on shortcode " . $media->shortcode);
            $collector[] = [
                "actor_id" => $creatorId,
                "mid" => $media->mid,
                "_sid" => $media->_sid,
                "shortcode" => $media->shortcode,
                "row_id" => $media->id,
                "type" => $media->type,
                "mime" => $media->media_type,
                "api" => !isEmpty($creator) && (int)$media->api === 1 && $media->origin !== "profile_scrape",
                "followers_count" => empty($creatorId) ? 0 : (int)$creator->followers_count,
                "unknown" => isEmpty($creator)
            ];
            if(count($collector) >= $chunkSize) break;
        }

        return empty($collector) ? $collection : $collection->setItems($collector);
    }


    public function queryCampaignMedias(Collection $mediaCollection, ?CronWorker $worker = null): void {
        $worker?->log("Attempting to update " . $mediaCollection->count());
        if($mediaCollection->empty()) return;
        $this->mediaLookupHandler($mediaCollection, $worker);
    }


    public function mediaLookupHandler(Collection $mediaCollection, ?CronWorker $worker = null): void {
        $actorHandler = Methods::actors();
        $mediaHandler = Methods::actorMedia();
        $dataHandler = Methods::dataHandler();
        $api = new Instagram();

        debugLog("Starting media lookup", "mediaLookupHandler");

        foreach ($mediaCollection->list() as $media) {
            $actorId = $media->actor_id;
            $shortCode = $media->shortcode;
            $mid = $media->mid;
            $rowId = $media->row_id;
            $useApi = $media->api;
            $followersCount = $media->followers_count;
            $unknown = $media->unknown;
            debugLog("Actor id: $actorId; Shortcode: $shortCode; Api: $useApi; Unknown: $unknown", "mediaLookupHandler");

            $worker?->log("Media type: " . ($media->type));
            if($useApi) { //Query through the api
                $integration = $actorHandler->getRelatedIntegration($actorId);
                $worker?->log("Media " . $media->mid . " belongs to the API. Querying from api");

                $accessToken = $integration->item_token;
                $mediaType = $media->type === "post" && $media->mime === "VIDEO" ? "reel" : $media->type;
                $insights = $api->mediaInsight($mid, $accessToken, $mediaType);
                testLog($insights, "mediainsight-$media->type");
                if(empty($insights)) {
                    $mediaHandler->update(["last_metric_update" => time()], ["id" => $rowId]);
                    $worker?->log("Insights turned out to be empty...");
                    continue;
                }
                if(array_key_exists("error", $insights)) {
                    $worker?->log(json_encode($insights));
                    continue;
                }

                $insights["engagement_rate"] = $media->type === "story" ?
                    $dataHandler->storyEngagement(array_merge($insights, ["followers_count" => $followersCount])):
                    $dataHandler->engagementRate(array_merge($insights, ["followers_count" => $followersCount]));

                $insights["last_metric_update"] = time();
                $mediaHandler->update($insights, ["id" => $rowId]);
                $worker?->log("Updated media: " . $media->mid . " through the API");
            }



            else { //Query through scraper
                $scraper = Methods::igScraper();

                $mid = $media->_sid;
                if(str_contains((string)$mid, "_")) $mid = explode("_", $mid)[0];
                debugLog("Initial scraper id: $mid", "mediaLookupHandler");

                if(empty($mid)) {
                    debugLog("Attempting to fetch new scraper id", "mediaLookupHandler");
                    $mid = $scraper->getPostPageMediaId($shortCode);
                    debugLog("New media id: $mid", "mediaLookupHandler");

                    if(empty($mid)) {
                        debugLog("Failed to grab new media id.", "mediaLookupHandler");
                        $worker?->log("Failed to scrape media id off of shortcode " . $media->shortcode . " as media failed initially by row-id: " . $media->row_id . ". Trying next time");
                        continue;
                    }

                    debugLog("Successfully grabbed new media id: $mid. Updating row...", "mediaLookupHandler");
                    $mediaHandler->update(["_sid" => $mid], ["id" => $media->row_id]);
                }


                Cookies::$initializerInfo = "Scraper-id; $mid";
                $mediaInfo = $scraper->mediaInfo($mid);
                testLog($mediaInfo, "mediainfo-1");
                if(empty($mediaInfo)) {
                    debugLog("Media info empty...", "mediaLookupHandler");
                    $worker?->log("Failed to scrape media with row-id: ".$media->row_id.". Trying next time");
                    $mediaHandler->update(["last_metric_update" => time()], ["id" => $rowId]);
                    continue;
                }
                if(array_key_exists("error", $mediaInfo)) {
                    debugLog("Scraper error on media id: $mid: " . json_encode($mediaInfo), "mediaLookupHandler");
                    $worker?->log("Scraper error on media id: $mid: " . json_encode($mediaInfo));
                    continue;
                }
                debugLog("Media info fetched successfully", "mediaLookupHandler");

                testLog($mediaInfo, "mediainfo");
                $generalEdgeData = $dataHandler->getEdgeData($mediaInfo, ScraperNestedLists::POST_PAGE_MEDIA_INFO);
                testLog($generalEdgeData, "generaledge");

                switch ($generalEdgeData["media_type"]) {
                    default: $generalEdgeData["media_type"] = "IMAGE"; break;
                    case 2: $generalEdgeData["media_type"] = "VIDEO"; break;
//            case 8: $generalEdgeData["media_type"] = "CAROUSEL"; break;
                }
                debugLog("Media type: " . $generalEdgeData["media_type"], "mediaLookupHandler");

                $mediaEdge = [$dataHandler->getEdgeData($generalEdgeData, ScraperNestedLists::POST_PAGE_MEDIA_TO_MEDIA)];
                testLog($mediaEdge, "mediaEdge");

                $mediaEdge = $dataHandler->exchangeMediaFields($mediaEdge, [], Settings::$app->download_media)[0];
                testLog($mediaEdge, "media_exchanged");


                $mediaEdge["permalink"] = "https://instagram.com/p/" . $mediaEdge["shortcode"];
                if(!$unknown) {
                    $mediaEdge = array_merge(
                        $mediaEdge,
                        [
                            "engagement_rate" => $dataHandler->engagementRate(
                                array_merge(
                                    $mediaEdge,
                                    ["followers_count" => $followersCount]
                                )
                            )
                        ]
                    );
                }
                debugLog($mediaEdge, "media_extracted");

                $mediaEdge["last_metric_update"] = time();
                $mediaEdge["type"] = $media->type;
                $worker?->log("Inserting / updating media: " . $media->mid);
                $mediaEdge = $mediaHandler->processMedia($mediaEdge);
                testLog($mediaEdge, "processed-scraper");
                $mediaHandler->insertNewMedia($mediaEdge, $actorId);
            }
        }
    }


    public function findHashtagsToTrack(?CronWorker $worker = null): Collection {
        $minTrackingTimeBetween = time() - (3600 * 12);
        $collection = new Collection();

        $campaigns = Campaigns::where("tracking", [1,2])
            ->where("discovery_active", 1)
            ->where("last_discovery", "<=", $minTrackingTimeBetween)
            ->where("start", "<=", time())
            ->all();


        $worker?->log("Initial campaign count fitting criteria: " . $campaigns->count());
        if($campaigns->empty()) return $collection;

        $trackingList = [];
        $campaignHandler = Methods::campaigns();
        foreach ($campaigns->list() as $campaign) {
            $hashtag = trim(str_replace("#", "", $campaign->hashtag));
            if(empty($hashtag)) {
                $worker?->log("Hashtag is empty from campaign " . $campaign->id);
                continue;
            }


            $item = $campaign;
            $item->creators = $campaignHandler->getCampaignCreators($campaign->id, ["id", "username"]);
            if(!$campaign->creatorless_campaign && $item->creators->empty()) continue;
            $item->creators = $item->creators->list();

            $worker?->log("Adding new hashtag: $hashtag");
            if(!array_key_exists($hashtag, $trackingList)) $trackingList[$hashtag] = [];
            $trackingList[$hashtag][] = $item;
        }
        return empty($trackingList) ? $collection : $collection->setItems($trackingList)->grabChunk(5, 0, true);
    }


    public function hashtagTracking(Collection $trackingList, ?CronWorker $worker = null): void {
        if($trackingList->empty()) return;

        $campaignHandler = Methods::campaigns();
        $mediaHandler = Methods::actorMedia();
        $dataHandler = Methods::dataHandler();
        $handler = Methods::handler();
        $scraper = Methods::igScraper();
        $campaignUpdateParams = [];
        $counter = 0;

        foreach ($trackingList->list() as $hashtag => $campaigns) {
            $mostRecentDiscovery = (new Collection($campaigns))->reduce(function ($current, $campaign) {
                if(!isset($current)) return $campaign["last_discovery"];
                return min($campaign["last_discovery"], $current);
            });
            $worker?->log("Last #$hashtag discover at: $mostRecentDiscovery");
            foreach ($campaigns as $campaign) {
                $campaignId = $campaign->id;
                $campaignUpdateParams[$campaignId] = ["last_discovery" => time()];
                if(!$campaignHandler->isCampaignActive($campaign)) {
                    $campaignUpdateParams[$campaignId]["discovery_active"] = 0;
                    $worker?->log("As the campaign $campaignId has ended, this is the last tracking for this campaign");
                }
            }

            $medias = $handler->hashtagExplore($hashtag, $scraper, $mostRecentDiscovery);
            testLog($medias, "hashtag-$hashtag");
            if(empty($medias) || array_key_exists("error", $medias)) {
                if(array_key_exists("error", $medias)) $worker?->log("Hashtag error: " . json_encode($medias));
                $worker?->log("Found no data for hashtag $hashtag");
                continue;
            }


            foreach ($medias as $i => $media) {
                $n = $i + 1;
                $campaignIds = [];
                $ownerUsername = $media["username"];
                $timestamp = $media["timestamp"];
                $mid = $media["mid"];
                $worker?->log("Looking at media $mid ($ownerUsername)");
                $actorId = 0;
                $followersCount = 0;


                foreach ($campaigns as $campaign) {
                    $brand = $campaignHandler->brandAccount($campaign->id);
                    if($brand === $ownerUsername) {
                        $worker?->log("The media is published by the brand itself ($ownerUsername). Skipping.");
                        continue;
                    }
                    $creatorless = $campaign->creatorless_campaign === 1;
                    $creators = $campaign->creators;
                    $worker?->log("Matching media to campaign $campaign->id...");

                    if((int)$campaign->end < $timestamp || (int)$campaign->start > $timestamp) {
                        $worker?->log("Media was not published within the campaign period.");
                        continue;
                    }
                    $worker?->log("Media ($n) owner: $ownerUsername, and time: $timestamp, (" . date("Y-m-d H:i:s", $timestamp) . ").");

                    if($creatorless) $campaignIds[] = $campaign->id;
                    if(!empty($actorId)) continue; //Once the creator is found, lets skip downwards.
                    $creator = array_values(array_filter(toArray($creators), function ($creator) use ($ownerUsername) { return $ownerUsername === $creator["username"]; }));
                    if(!$creatorless && empty($creator)) {
                        $worker?->log("Media is not creatorless and did not match any campaign-creators of campaign $campaign->id");
                        continue;
                    }

                    if(!empty($creator)) {
                        $creator = $creator[0];
                        $actorId = $creator["id"];
                        $followersCount = $creator["followers_count"];
                        $worker?->log("Actor ($actorId) owns the current media.");
                    }
                    else $worker?->log("Campaign is creatorless and the creator is unknown.");
                    if(!$creatorless) $campaignIds[] = $campaign->id;
                }

                $data = array_merge(
                    $media,
                    array(
                        "hashtags" => $dataHandler->setHashtags($media),
                        "location" => $dataHandler->setLocation($media),
                        "engagement_rate" => $dataHandler->engagementRate(array_merge($media, ["followers_count" => $followersCount])),
                        "total_interactions" => $dataHandler->totalInteractions($media),
                    )
                );
                $data["actor_id"] = $actorId;
                $data["origin"] = "hashtag_scrape";
                $data["type"] = "post";

                if(Methods::actors()->belongsToApi($actorId)) {
                    $integration = Methods::actors()->getRelatedIntegration($actorId);
                    if(!isEmpty($integration)) {
                        $apiMedia = Methods::instagram()->fetchByIdentifier($integration->item_id, $integration->item_token, ["shortcode" => $data["shortcode"]]);
                        if(!empty($apiMedia)) {
                            $data["_sid"] = $mid;
                            $data["mid"] = $apiMedia["id"];
                        }
                    }
                }

                testLog($data, "hashtagrunnin");

                $worker?->log("Media $mid relates to a total of " . count($campaignIds) . " campaigns.");
                if(empty($campaignIds)) continue;
                $worker?->log("Processing media...");
                $data = $mediaHandler->processMedia($data);
                $worker?->log("Inserting media if it doesn't exist.");
                testLog($data, "hashtagmediaprocseddeds");
                $mediaHandler->insertIfNotExist($data, 0, "", $campaignIds, $worker);
                $counter += 1;
            }
        }

        $worker?->log("Finished handling media. Updating last discovery for " . count(array_keys($campaignUpdateParams)) . " campaigns.");
        foreach ($campaignUpdateParams as $campaignId => $params) $campaignHandler->update($params,["id" => $campaignId]);
        $worker?->log("Finished hashtag tracking. In total we found $counter new posts");
    }


    public function runTagMentionQueries(Collection $items, ?CronWorker $worker = null): void {
        if($items->empty()) return;
        $api = new Instagram();
        foreach ($items->list() as $item) {
            $integration = $item->owner_integration;
            $campaigns = new Collection($item->items);
            $worker?->log("The brand (" . $item->username . ") has " . $campaigns->count() . " active campaigns to check for.");
            $earliestTimestamp = $this->tagMentionComparableTimestamp($campaigns);
            $worker?->log("Running media time comparing $earliestTimestamp (" . date("Y-m-d H:i:s", $earliestTimestamp) . ").");
            $taggedMedia = $api->taggedPage($integration->item_id, $integration->item_token, $earliestTimestamp);
            $worker?->log("The tagged media query yielded " . count($taggedMedia) . " posts of interest.");
            $this->tagMentionMediaHandle($taggedMedia, $campaigns, $worker);
        }

    }


    private function tagMentionMediaHandle(array $taggedMedia, Collection $campaignItems, ?CronWorker $worker = null): void {
        if(empty($taggedMedia) || $campaignItems->empty()) return;
        $dataHandler = Methods::dataHandler();
        $mediaHandler = Methods::actorMedia();
        $finishedCampaigns = [];

        testLog($campaignItems->toArray(), "medaicreaotooo");
        foreach ($taggedMedia as $i => $media) {
            $campaignIds = [];
            $n = $i + 1;
            $ownerUsername = $media["username"];
            $timestamp = $media["timestamp"];
            $mid = $media["id"];
            $worker?->log("Looking at media $mid ($ownerUsername)");
            $actorId = 0;
            $followersCount = 0;

            foreach ($campaignItems->list() as $campaignItem) {
                $campaign = $campaignItem->campaign;
                $creatorless = $campaignItem->creatorless;
                $creators = $campaignItem->creators; //list of id, username, followers_count
                $worker?->log("Matching media to campaign $campaign->id...");

                if((int)$campaign->end < time() && !in_array($campaign->id, $finishedCampaigns)) $finishedCampaigns[] = $campaign->id;
                if((int)$campaign->end < $timestamp || (int)$campaign->start > $timestamp) {
                    $worker?->log("Media was not published within the campaign period.");
                    continue;
                }
                $worker?->log("Media ($n) owner: $ownerUsername, and time: $timestamp, (" . date("Y-m-d H:i:s", $timestamp) . ").");

                if($creatorless) $campaignIds[] = $campaign->id;
                if(!empty($actorId)) continue; //Once the creator is found, lets skip downwards.
                $creator = array_values(array_filter(toArray($creators), function ($creator) use ($ownerUsername) { return $ownerUsername === $creator["username"]; }));
                if(!$creatorless && empty($creator)) {
                    $worker?->log("Media is not creatorless and did not match any campaign-creators of campaign $campaign->id");
                    continue;
                }

                if(!empty($creator)) {
                    $creator = $creator[0];
                    $actorId = $creator["id"];
                    $followersCount = $creator["followers_count"];
                    $worker?->log("Actor ($actorId) owns the current media.");
                }
                else $worker?->log("Campaign is creatorless and the creator is unknown.");
                if(!$creatorless) $campaignIds[] = $campaign->id;
            }

            $data = array_merge(
                $media,
                array(
                    "hashtags" => $dataHandler->setHashtags($media),
                    "location" => $dataHandler->setLocation($media),
                    "engagement_rate" => $dataHandler->engagementRate(array_merge($media, ["followers_count" => $followersCount])),
                    "total_interactions" => $dataHandler->totalInteractions($media),
                )
            );
            $data["actor_id"] = $actorId;
            $data["origin"] = "tag_mention";
            $data["shortcode"] = $dataHandler->instagramUrlShortCode($data["permalink"]);
            testLog($data, "mediarunnin");


            if(Methods::actors()->belongsToApi($actorId)) {
                $integration = Methods::actors()->getRelatedIntegration($actorId);
                if(!isEmpty($integration)) {
                    $apiMedia = Methods::instagram()->fetchByIdentifier($integration->item_id, $integration->item_token, ["shortcode" => $data["shortcode"]]);
                    if(!empty($apiMedia)) {
                        if((string)$mid !== (string)$apiMedia["id"]) {
                            $data["_sid"] = $mid;
                            $data["mid"] = $apiMedia["id"];
                        }
                    }
                }
            }

            $worker?->log("Media $mid relates to a total of " . count($campaignIds) . " campaigns.");
            if(empty($campaignIds)) continue;
            $worker?->log("Processing media...");
            $data = $mediaHandler->processMedia($data);
            $worker?->log("Inserting media if it doesn't exist.");
            testLog($data, "mediaprocseddeds");
            $mediaHandler->insertIfNotExist($data, 0, "", $campaignIds, $worker);
        }

        if(!empty($finishedCampaigns)) {
            $worker?->log("The following campaigns have finished. Setting discovery to 0. " . json_encode($finishedCampaigns));
            Methods::campaigns()->update(["discovery_active" => 0], ["id" => $finishedCampaigns]);
        }

        $worker?->log("Finished media handle.");
    }


    private function tagMentionComparableTimestamp(Collection $campaignItems): int {
        $earliestTime = null;
        foreach ($campaignItems as $campaignItem) {
            $campaign = $campaignItem->campaign;
            $current = min((int)$campaign->last_tag_discovery, (int)$campaign->start);
            if($earliestTime === null || $current < $earliestTime) $earliestTime = $current;
        }
        return $earliestTime === null ? 0 : $earliestTime;
    }


    public function tagMentionFlowFindPosts(?CronWorker $worker = null): Collection {
        $campaignHandler = Methods::campaigns();
        $minTrackingTimeBetween = time() - (3600);
        $campaigns = Campaigns::where("tracking", [0,2])
            ->where("discovery_active", 1)
            ->where("last_tag_discovery", "<=", $minTrackingTimeBetween)
            ->where("start", "<=", time())
            ->whereColumn("last_tag_discovery", "<", "end")
//            ->orWhere("id", [11,12,13,14,15])
            ->all();

        $collection = new Collection();
        $worker?->log("Initial campaign-count fitting query-criteria: " . $campaigns->count());
        if($campaigns->empty()) return $collection;

        $collector = [];
        foreach ($campaigns->list() as $campaign) {
            $creators = $campaignHandler->getCampaignCreators($campaign->id, ["id", "username", "followers_count"]);
            $ownerIntegration = $campaignHandler->ownerIntegration($campaign);
            if(empty($ownerIntegration) || (!$campaign->creatorless_campaign && $creators->empty())) {
                $campaignHandler->update(["last_tag_discovery" => time()], ["id" => $campaign->id]);
                $worker?->log((empty($ownerIntegration) ? "Owner integration was not found" : "The campaign has no creators"));
                continue;
            }
            if(!array_key_exists($ownerIntegration->id, $collector))  $collector[$ownerIntegration->id] = [
                "username" => $ownerIntegration->item_name,
                "owner_integration" => $ownerIntegration,
                "items" => []
            ];
            $collector[$ownerIntegration->id]["items"][] = [
                "creatorless" => (bool)$campaign->creatorless_campaign,
                "creators" => $creators->list(),
                "campaign" => $campaign
            ];
        }

        $worker?->log("Found " . count($collector) . " integrations to Tag-track");
        $worker?->log("Trimming to max 5.");
        return empty($collector) ? $collection : $collection->setItems($collector)->grabChunk(5, 0, true);
    }


    public function affiliatePayPeriod(?CronWorker $worker = null): void {
        $worker?->log("Running affiliatePayPeriod...");
        $weeklyInterval = Settings::$app->affiliate_payout_week_interval;
        $intervalTotalPay = Settings::$app->affiliate_payout_pool;

        $periodHandler = Methods::affiliatePaymentPeriod();
        $resultsHandler = Methods::affiliatePayoutResults();

        if(!isEmpty($periodHandler->getCurrentPeriod())) {
            $worker?->log("The current period has not yet ended.");
            return;
        }

        $startTime = strtotime("today");
        $startDate = date("Y-m-d", $startTime);
        $endTime = strtotime($startDate . " +$weeklyInterval weeks");
        $endDate = date("Y-m-d", $endTime -1); //Minus one so that the end date is written as ending before midnight.
        $worker?->log("No current period. Creating a new one for the dates $startDate - $endDate.");
        $created = $periodHandler->create([
            "start_date" => $startDate, "end_date" => $endDate,
            "start_time" => $startTime, "end_time" => $endTime,
            "total_payout" => $intervalTotalPay //Fixed amount every time.
        ]);
        if(!$created) $worker?->log("Failed to create a new payment period.");
        else $worker?->log("Successfully created a new payment period.");




        $latestPeriod = $periodHandler->getLatestNonCurrentPeriod();
        if(isEmpty($latestPeriod) || $latestPeriod->status === "closed") {
            $worker?->log("The latest ended period does not exist or is already closed.");
            return;
        }

        $worker?->log("The most recent period has ended and is not yet closed.");
        $worker?->log("Starting process to calculate contributions...");
        $periodHandler->update(["status" => "closed"], ["id" => $latestPeriod->id]);


        $contributions = Methods::affiliates()->getTotalPeriodSuccessfulContribution($latestPeriod);
        if(empty($contributions)) {
            $periodHandler->update(["total_payout" => 0], ["id" => $latestPeriod->id]);
            $worker?->log("No affiliates currently exists or has contributed successfully to the period.");
            return;
        }

        $totalSuccessCount = array_sum(array_values($contributions));

        foreach ($contributions as $uid => $successCount) {
            if($successCount === 0) $worker?->log("Affiliate ($uid) has not had any successful cookie usages.");
            else $worker?->log("Affiliate ($uid) contributed with $successCount cookie usages.");


            if($resultsHandler->exists(["affiliate_id" => $uid, "pay_period_id" => $latestPeriod->id])) {
                $worker?->log("Affiliate $uid already has a results row for this period id $latestPeriod->id");
                continue;
            }


            $user = Methods::affiliates()->get($uid);
            $params = [
                "affiliate_id" => $uid,
                "pay_period_id" => $latestPeriod->id,
                "total_contributions" => $successCount,
                "payout" => $totalSuccessCount === 0 ? 0 : round($successCount / $totalSuccessCount * $intervalTotalPay, 2),
                "payment_to_name" => $user->full_name,
                "payment_to_email" => $user->email,
            ];
            if($resultsHandler->create($params)) $worker?->log("Inserted a results row for affiliate $uid on period $latestPeriod->id");
            else $worker?->log("Failed to insert a results row for affiliate $uid on period $latestPeriod->id");
        }
    }




}


















