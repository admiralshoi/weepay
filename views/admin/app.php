<?php
/**
 * @var object $args
 */

use classes\api\functions\InstagramUtility;
use classes\data\regression\Estimator;
use classes\data\regression\ModelFitter;
use classes\http\cookies\Cookies;
use classes\http\enum\ScraperNestedLists;
use classes\Methods;
use features\Settings;


$pageTitle = "App settings";




if(isset($_GET["simplif"])) {


//    require_once __view("testing/simplif.php");
    require_once __view("testing/simplif-4.php");


}




if(isset($_GET["models"])) {
    require_once __view("testing/models");
}

if(isset($_GET["tester"])) {

    $media = \Database\model\ActorMedia::queryBuilder()->select(["id", "actor_id", "type"])
        ->where("actor_id", "!=", 0)->all();
    foreach ($media->list() as $item) {
        Methods::mediaEstimations()->createIfNotExist($item->id, $item->actor_id, $item->type === "story");
    }






}


if(isset($_GET["abcd"]))  {



    require_once __view("testing/runner.php");


//    $scraper = new \classes\http\scrapers\InstagramScraper();
//    $res = $scraper->queryUserTimelineMedia("anna.und.die.raeuberband");
//    $res = Methods::handler()->instagramLookupUser("anna.und.die.raeuberbande");
//    $mediaInfo = $scraper->mediaInfo("3533850329305308505");
//    prettyPrint($mediaInfo);




//    \routing\routes\api\CronjobController::campaignMediaUpdate();


    /**
     * AI TEST
     */
//    $metricsInitial = ["impressions", "replies_count","reach_count","shares_count","total_interactions", "like_count"];
//    $evaluatedMetrics = [];
//    $apiHandler = Methods::openAi();
//    $example = [
//        "impressions" => 313,
//        "replies_count" => 2,
//        "total_interactions" => 0,
//        "confidence" => [
//            "impressions" => .96,
//            "replies_count" => 1,
//            "total_interactions" => 0,
//        ]
//    ];
//
//    $txt = "From the image, please extract me: (" . implode(", ", $metricsInitial) . "). ";
//    $txt .= "If you cant find any of those then just set the value to 0 for the metric. ";
//    if(in_array("impressions", $metricsInitial)) {
//        $txt .= "Any 'navigation' metric does NOT count towards impressions. ";
//        $txt .= "Any 'sticker clicks' does NOT count towards impressions. ";
//    }
//    if(in_array("like_count", $metricsInitial)) {
//        $txt .= "If the text is in german, 'Gefällt-mir-Angaben' refers to 'like_count'. ";
//    }
//    $txt .= "Please return the response in a json format with no additional text outside of the json in the reply. ";
//    $txt .= "Foreach property in the response, please include your confidence in decimal from 0-1 indicating how sure you are that you extracted the correct metric. ";
//    $txt .= "Please use this format: " . json_encode($example) . ". Please be ware that we are comparing multiple images. So if a metric gets he value '0' keep your confidence relatively loll. ";
//    $txt .= "If you encounter the actually word of the metric name, that should increase your confidence a lot. ";
//    $txt .= "All keys/properties of the json response must be in english.";
//    prettyPrint($txt);
//
//
//    $uploadHandler = Methods::uploadsHandler();
//    $rows = $uploadHandler->getByX(["hash" => ["3f059f152405445ef60fa71d508c4d54", "f2c18c323301163d7a00497fa117e1d2"]]);
//    $rows = $rows->filter(function ($row){ return file_exists(ROOT . $row["path"]); });
//    $paths = $rows->map(function ($row){ return $row["path"]; });
//    foreach ($paths as $path) {
//        $publicUrl = resolveImportUrl($path);
//        prettyPrint($publicUrl);
//        $result = $apiHandler->imageAndTextPrompt($publicUrl, $txt);
//
//        $evaluatedMetrics[] = $result;
//    }
//    prettyPrint($evaluatedMetrics);



}



if(isset($_GET["customwebhooktest"])) {


//    $ids = Methods::actorMedia()->getMediaIdsByCampaignIds(28);
//    prettyPrint($ids);
//
//
//    foreach ($ids as $id) {
//        if(!Methods::mediaRelations()->exists(["campaign_id" => 28, "media_id" => $id])) continue;
//        Methods::mediaRelations()->delete(["campaign_id" => 28, "media_id" => $id]);
//    }




//    $dir = ROOT . "logs/test/";
//    $date = "2024-12-26";
//    $endTime = strtotime("2024-12-31");
//    $files = [];
//
//    while (true) {
//        $time = strtotime($date);
//        if($time > $endTime) break;
//        $logsDir = $dir . $date . "/";
//
//        $date = date("Y-m-d", strtotime($date . " +1 day"));
//        if(!is_dir($logsDir)) continue;
//        $contents = directoryContent($logsDir . "hook-*.json");
//        if(empty($contents)) continue;
//
//        foreach ($contents as $fn) {
//            $path = $logsDir . $fn;
//            $content = json_decode(file_get_contents($path), true);
//            if(array_key_exists("messaging", $content)) continue;
////            if(!empty(nestedArray($content, ["entry", 0, "messaging"]))) continue;
//            if(!empty(nestedArray($content, ["entry", 0, "changes", 0, "value", "comment_id"]))) continue;
//            if(str_contains(json_encode($content), "story_insights")) continue;
////            if(!str_contains(json_encode($content), "17841404642910865")) continue;
//
//            if(array_key_exists("forward_key", $content)) unset($content["forward_key"]);
//            prettyPrint([Methods::metaWebhook()->event($content)]);
//        }
//    }




}




if(isset($_GET["runner"])) {

    function reCalculateInteractions() {
        $content = Methods::actorMedia()->getByX();
        foreach ($content as $item) {
            if(Methods::metricHandler()->exists(["object_id" => $item->id])) continue;
            $media = new \classes\actors\Media($item);
            $media->runMetricCalculations();
            $totalInteractions = $media->totalInteractions();
            if(empty($totalInteractions)) continue;
            if($totalInteractions <= $item->total_interactions) continue;
            Methods::actorMedia()->update(["total_interactions" => $totalInteractions], ["id" => $item->id]);
        }
    }


    function ensureActorIdIfAvailable() {
        $query = \Database\model\ActorMedia::queryBuilder();
        $mediaRows = $query->raw("SELECT username, actor_id FROM $query->table WHERE actor_id = 0 OR actor_id IS NULL GROUP BY username")->all();
        $actorsNotFound = [];
        $userUpdatedCount = 0;
        foreach ($mediaRows as $row) {
            $actor = \Database\model\Actors::queryBuilder()->where("username", $row->username)->select(["id"])->first();
            if(isEmpty($actor)) {
                $actorsNotFound[] = $row->username;
                continue;
            }

            Methods::actorMedia()->update(["actor_id" => $actor->id], ["username" => $row->username]);
            $userUpdatedCount++;
        }
        prettyPrint("Username unique rows updated: $userUpdatedCount");
        prettyPrint($actorsNotFound);


//        $MANUALRUN = "UPDATE Pd_dIkQ_actormedia SET maturing = 0";
//        $MANUALRUN = "UPDATE Pd_dIkQ_actormedia SET maturing = 1 WHERE campaign = 1 AND actor_id IS NOT NULL AND actor_id != 0 AND type != 'story'";
    }



    function ensureScrapeUnknownCreators() {
        /**
         * Please change i as you go. Dont run everthing at once to prevent a million scrapes in too short a period.
         */
        $actorsNotFound = [];

        $campaigns = \Database\model\Campaigns::queryBuilder()->where("owned_by", "!=", "NULL")->all();
        $creatorRelations = Methods::actorRelations();
        $relationsCreatedCount = 0;
        $actorsFetchedCount = 0;

        $i = 0;
        foreach ($campaigns->list() as $campaign) {
            $i++;
            if($i < 0) continue;
            $campaignId = $campaign->id;
            $ownerUid = $campaign->owned_by;
            $campaignMediaIds = Methods::actorMedia()->getMediaIdsByCampaignIds($campaignId);
            $campaignCreatorsGrouped =  Methods::actorMedia()->uniqueCreatorMetrics($campaignId,  $campaignMediaIds);


            prettyPrint("======= Campaign $campaignId =======");

            prettyPrint($campaignCreatorsGrouped->count());
            foreach ($campaignCreatorsGrouped->list() as $item) {

                $username = $item->username;
                if(in_array($username, ["daisy_theafghanhound", "beatrizfaccio13"])) continue;
                $actor = \Database\model\Actors::queryBuilder()->where("username", $username)->select(["id", "username"])->first();
                //            prettyPrint([$username, isEmpty($actor)]);
                //            continue;

                if(isEmpty($actor)) {
                    $actor = Methods::actors()->fetchAndStoreNewUnknownCreator($username, "webhook_manual", ["id"]);
                    //                prettyPrint("$username fetched!");
                    //                prettyPrint([!isEmpty($actor)]);
                    //                break 2;
                    if(isEmpty($actor)) {
                        $actorsNotFound[] = $username;
                        continue;
                    }
                    $actorsFetchedCount++;


                }
                $actorId = $actor->id;


                $relationParam = ["actor_id" => $actorId, "user_id" => $ownerUid];
                if(!$creatorRelations->exists($relationParam)) {
                    $creatorRelations->create($relationParam);
                    $relationsCreatedCount++;
                }




            }


            if($i > 5) break;
        }

        prettyPrint("======== END ========");

        prettyPrint($actorsFetchedCount);
        prettyPrint($relationsCreatedCount);
        prettyPrint(count($actorsNotFound));
        prettyPrint($actorsNotFound);
    }

}







?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "app-settings";


</script>
<div class="page-content position-relative" data-page="app-settings">

    <div class="row">
        <div class="col-12">

            <p class="font-22 font-weight-bold">App settings</p>

            <div class="card border-radius-10px mt-4">
                <div class="card-body">
                    <div class="flex-row-between flex-wrap pb-4" style="gap: 5px; border-bottom:1px solid #efefef;">
                        <div class="flex-col-start">
                            <p class="font-18 font-weight-bold color-primary-cta mb-0">Migrate to production</p>
                            <p class="font-14 mb-0 text-wrap font-weight-bold mt-2">Details</p>
                            <ul>
                                <li>Backing up current LIVE version. This includes files and Database.</li>
                                <li>Storing a maximum of <?=MAX_BACKUPS?> backup versions. This will overwrite the oldest one if need be.</li>
                                <li>Moving all files from 'testing', except for a few exceptions, into production.</li>
                                <li>Refresh the page, migrates the database.</li>
                                <li>The database contents (rows) are not shared between testing and production, so you won't have to worry overwriting existing user data etc.</li>
                                <li>It is possible to revert a migration (within <?=MAX_BACKUPS?> versions), however you'll need a developer to help revert it.</li>
                            </ul>
                        </div>
                        <button class="btn-base btn-prim mt-1 noSelect" name="migrate_to_production">Migrate now</button>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-12 mt-5">

            <p class="font-22 font-weight-bold">Test webhook</p>

            <div class="card border-radius-10px mt-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center" style="gap: 5px;">
                        <input type="text" class="form-control" name="webhook_filename" placeholder="path-to-file" />
                        <button class="btn-base btn-prim mt-1 noSelect" name="test_webhook">Test</button>
                    </div>
                    <pre style="display: none; overflow-x: hidden; overflow-y: auto; white-space: normal" id="webhook_response"></pre>
                </div>
            </div>
        </div>

    </div>





    <div class="row align-items-stretch mt-5">
        <div class="col-12 mb-4">
            <p class="font-22 font-weight-bold">App settings</p>
        </div>
        <div class="col-12 col-lg-6 d-flex">
            <div class="card w-100">
                <form id="app_settings_general" class="card-body">

                    <div class="flex-row-between flex-align-center mb-3">
                        <p class="font-weight-bold font-16 mb-0">General settings</p>
                        <p class="font-16 mb-0 color-primary-cta cursor-pointer hover-underline noSelect" id="save_app_general_settings">
                            <i class="mdi mdi-content-save font-16"></i>
                            Save
                        </p>
                    </div>

                    <div style="display: none" class="error-field px-3 py-2 mb-3 alert-danger"></div>

                    <ul>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Allow unknown creator fetch</p>

                                <label class="form-switch">
                                    <input name="allow_auto_unknown_actor_fetch" type="checkbox" name="" <?=Settings::$app->allow_auto_unknown_actor_fetch ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Update unknown creator metrics</p>

                                <label class="form-switch">
                                    <input name="update_unknown_creator_media" type="checkbox" name="" <?=Settings::$app->update_unknown_creator_media ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">
                                    Live campaign testing
                                    <i class="mdi mdi-information" data-toggle="tooltip" data-placement="top"
                                       title="Relevant only in testing environment. If enabled, you may view and create campaigns that uses live data. All webhook that are forwarded to the testing app will ONLY interact with those such testing campaigns."></i>
                                </p>

                                <label class="form-switch">
                                    <input name="live_campaign_dev" type="checkbox" name="" <?=Settings::$app->live_campaign_dev ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">
                                    Open campaigns
                                    <i class="mdi mdi-information" data-toggle="tooltip" data-placement="top"
                                       title="Enable the 'Open Campaigns' feature, so that Creators may apply to open campaigns."></i>
                                </p>

                                <label class="form-switch">
                                    <input name="open_campaigns_enabled" type="checkbox" name="" <?=Settings::$app->open_campaigns_enabled ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">
                                    Creator analytic section
                                    <i class="mdi mdi-information" data-toggle="tooltip" data-placement="top"
                                       title="Show the section 'Analytics' (next to 'Recent Content') on the creators page, assuming you are not viewing through a campaign."></i>
                                </p>

                                <label class="form-switch">
                                    <input name="show_creator_account_analytics_section" type="checkbox" name="" <?=Settings::$app->show_creator_account_analytics_section ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>

                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Maturing period in days</p>

                                <input name="maturing_period" type="number" class="form-control h-30px mxw-100px ml-2"
                                       min="0" value="<?=(Settings::$app->maturing_period / 3600 / 24)?>">
                            </div>
                        </li>

                    </ul>

                </form>
            </div>
        </div>
    </div>






    <div class="row align-items-stretch mt-5">
        <div class="col-12 mb-4">
            <p class="font-22 font-weight-bold">Affiliate settings</p>
        </div>
        <div class="col-12 col-lg-6 d-flex">
            <div class="card w-100">
                <div class="card-body">

                    <div class="flex-row-between flex-align-center mb-3">
                        <p class="font-weight-bold font-16 mb-0">General settings</p>
                        <p class="font-16 mb-0 color-primary-cta cursor-pointer hover-underline noSelect" id="save_affiliate_general_settings">
                            <i class="mdi mdi-content-save font-16"></i>
                            Save
                        </p>
                    </div>

                    <ul>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Allow signup</p>

                                <label class="form-switch">
                                    <input name="aas" type="checkbox" name="" <?=Settings::$app->allow_affiliate_signup ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </li>

                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Payout pool in euros (EUR €)</p>

                                <input name="app" type="number" class="form-control h-30px mxw-100px ml-2" min="0" value="<?=Settings::$app->affiliate_payout_pool?>">
                            </div>
                        </li>

                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Payout interval</p>
                                <input name="apwi" type="text" class="form-control h-30px mxw-150px ml-2"
                                       value="Every <?=Settings::$app->affiliate_payout_week_interval?> weeks" disabled>
                            </div>
                        </li>
                    </ul>

                </div>
            </div>
        </div>


        <div class="col-12 col-lg-6 d-flex">
            <div class="card w-100">
                <div class="card-body">


                    <div class="flex-row-between flex-align-center mb-3">
                        <p class="font-weight-bold font-16 mb-0">Cookie settings</p>
                        <p class="font-16 mb-0 color-primary-cta cursor-pointer hover-underline noSelect" id="save_affiliate_cookie_settings">
                            <i class="mdi mdi-content-save font-16"></i>
                            Save
                        </p>
                    </div>

                    <ul>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Maximum total cookies</p>

                                <input name="actm" type="number" class="form-control h-30px mxw-100px ml-2" min="0" value="<?=Settings::$app->affiliate_cookie_total_max?>">
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Maximum user cookies</p>

                                <input name="acim" type="number" class="form-control h-30px mxw-100px ml-2" min="0" value="<?=Settings::$app->affiliate_cookie_individual_max?>">
                            </div>
                        </li>
                        <li class="mt-2">
                            <div class="flex-row-between flex-align-center" style="column-gap: .5rem">
                                <p class="mb-0 font-16">Max error streak</p>

                                <input name="aces" type="number" class="form-control h-30px mxw-100px ml-2" min="0" value="<?=Settings::$app->affiliate_cookie_error_streak?>">
                            </div>
                        </li>
                    </ul>

                </div>
            </div>
        </div>
    </div>








    <div class="row">
        <div class="col-12 mt-5">

            <p class="font-22 font-weight-bold">Other</p>

            <div class="row align-items-stretch" style="row-gap: 2rem;">

                <div class="col-12 col-lg-4  d-flex">
                    <div class="card border-radius-10px mt-4 w-100">
                        <div class="card-body">
                            <div class="flex-col-start" style="">
                                <p class="font-16 font-weight-bold">Testing: View Live data</p>
                                <p class="font-14 mb-0">When on the testing app, you'll see the data from the live app.</p>
                                <p class="font-14 text-gray mb-3">This is not a setting, and will not affect anyone but your own experience.</p>
                                <div class="flex-row-end">
                                    <a href="<?=forceLiveDb() ? __adjustUrl("", [], ["live_db"]) : __adjustUrl("", ["live_db" => LIVE_DB_FORCE_TOKEN])?>"
                                       class="font-18 btn btn-primary mxw-150px">Toggle</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>

</div>