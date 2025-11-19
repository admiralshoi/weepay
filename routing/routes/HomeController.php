<?php

namespace routing\routes;
use classes\data\Calculate;
use classes\Methods;
use classes\utility\Numbers;
use Database\Collection;
use Database\model\ActorMedia;
use Database\model\ActorMediaRelations;
use Database\model\Actors;
use Database\model\CampaignApplications;
use Database\model\CampaignRelations;
use Database\model\Campaigns;
use Database\model\ObjectMetaData;
use Database\model\Organisations;
use Database\model\PublicCampaigns;
use routing\routes\GeneralController;

class HomeController {

    public static function integrations(array $args): mixed  {
        $params = Methods::isAdmin() ? ['active' => 1, 'deleted' => 0] : ["uuid" =>__oUuid(), 'active' => 1, 'deleted' => 0];
        $socialHandler = Methods::socialAccounts();
        $integrations = Methods::integrations()->getByX($params);
        $integrationUsage = Methods::app()->getIntegrationUsage();
        $currentUsage = $socialHandler->currentUsage();

        $integrations = $integrations->map(function ($integration) {
            $integration['created_display_date'] = date('M d, Y', strtotime($integration['created_at']));
            return $integration;
        });

        $actorHandler = Methods::actors();

        $allAvailable = $socialHandler->getAllAvailable();
        $allAvailable = $allAvailable->map(function ($account) use ($socialHandler) {
            $account['created_display_date'] = date('M d, Y', strtotime($account['created_at']));
            if($account['parent'] !== null) $account['parent'] = $socialHandler->get($account['parent'], ['name']);
            if(empty($account['profile_picture'])) $account['profile_picture'] = __asset(DEFAULT_USER_PICTURE);
            $account['profile_picture'] = resolveImportUrl($account['profile_picture']);
            return toArray($socialHandler->accountCampaigns($account, ['upcoming', 'active']));
        });


        $allEnabled = $allAvailable->filter(function ($account) { return $account['active'] === 1; });
        $allEnabled = $allEnabled->map(function ($account)  use ($actorHandler, $socialHandler) {
            $account['actor'] = isEmpty($account['username']) ? null : $actorHandler->getByUsername($account['username']);
            return $account;
        });

        $allDisabled = $allAvailable->filter(function ($account) { return $account['active'] === 0; });


        $providers = ['facebook'];
        $auth = [];
        $code = $link = null;
        $authenticate = false;
        $endpoint = $storageEndpoint = null;
        $page = str_starts_with(__oUuid(), Organisations::$uidPrefix) ? __url(ORGANISATION_PANEL_PATH .'/integrations') : __url('integrations');

        if(array_key_exists('provider', $args) && array_key_exists('state', $args) && array_key_exists('code', $args)) {
            $state = $args['state'];
            $provider = $args['provider'];
            $code = $args['code'];
            if(in_array($provider, $providers)) {
                if(isset($_SESSION['state']) && $_SESSION['state'] === $state) $authenticate = true;
            }

            if($state !== $_SESSION['state']) Response()->redirect('', $page);
        }
        else {
            $state = $_SESSION['state'] = 'auth-' . generateUniqueId();
        }

        foreach ($providers as $authProvider) {
            if($authProvider === 'facebook') {
                $link = Methods::oauthLink('', $state);
                $endpoint = 'api/integration/create';
                $storageEndpoint = 'api/integration/create/store';
            }
            $auth[$authProvider] = compact('link', 'state', 'code', 'authenticate', 'endpoint', 'page', 'storageEndpoint');
        }




        return Views("INTEGRATIONS", compact(
            'auth',
            'integrations',
            'integrationUsage',
            'currentUsage',
            'allEnabled',
            'allDisabled',
            'allAvailable',
        ));
    }




    public static function viewApplicant(array $args): mixed {
        $campaignId = $args["campaign_id"];
        $actorId = $args["actor_id"];
        if(!CampaignApplications::where("actor_id", $actorId)->where("campaign_id", $campaignId)->where("status", "!=", 4)->exists())
            return null;
        return self::viewCreator(["creator_id" => $actorId, "force" => true]);
    }


    public static function viewCreator(array $args): mixed  {
        $force = array_key_exists("force", $args) && $args["force"];
        $actorHandler = Methods::actors();
        $actorMediaHandler = Methods::actorMedia();
        $analyticHandler = Methods::analytics();
        $relationHandler = Methods::campaignRelations();
        $isCreator = Methods::isCreator();
        $isBrand = Methods::isBrand(0, false);
        $actorId = $args["actor"];
        if($force) $actorHandler->disableCheck();


        if($isCreator && $actorId !== Methods::creatorId()) return null;
        $campaignId = array_key_exists("campaign", $args) ? $args["campaign"] : null;
        $campaign = Methods::campaigns()->get($campaignId);
        if(!empty($campaignId) && isEmpty($campaign)) return null;
        if(!empty($campaignId) && $isCreator && !$relationHandler->exists(["campaign" => $campaignId, "actor" => $actorId])) return null;
        if(!$force && $isBrand && !in_array($actorId, Methods::actorRelations()->getRelationActorIds())) return null;
        if($isBrand && !isEmpty($campaign) && __oUuid() !== $campaign->uuid) return null;


        $actor = $actorHandler->get($actorId);
        if(isEmpty($actor)) return null;


        $mediaIds = !empty($campaignId) ? $actorMediaHandler->getMediaIdsByCampaignAndActorIds($campaignId, $actorId) :
            $actorMediaHandler->getMediaIds($actorId);
        $actorMetrics = empty($campaignId) ? $actorMediaHandler->uniqueCreatorMetrics(null, $mediaIds) :
            Methods::campaigns()->uniqueTrueCampaignCreatorMetrics($campaignId, [$actorId], $mediaIds);
        $performance = Methods::campaigns()->campaignPerformance($campaign, $mediaIds, $actorMetrics);


        $mediaTimestamps = $actorMediaHandler->getByX(['uid' => $mediaIds], ['timestamp'])->toArray();
        $mediaTimestamps = array_column($mediaTimestamps, 'timestamp');
        $postFrequency = Calculate::frequency($mediaTimestamps);

        $activeCampaigns = empty($campaignId) ? Methods::toCollection() : Methods::campaigns()->getActiveCampaigns([], 0, $actorId, [], ['uid', 'start', 'end', 'name'])->sortByKey('start');
        $upcomingCampaigns = empty($campaignId) ? Methods::toCollection() : Methods::campaigns()->getUpcomingCampaigns([],  $actorId, ['uid', 'start', 'end', 'name'])->sortByKey('start');
        $previousCampaigns = empty($campaignId) ? Methods::toCollection() : Methods::campaigns()->getPastCampaigns([], $actorId,['uid', 'start', 'end', 'name'])->sortByKey('start');


        $latestAnalytics = $analyticHandler->getCreatorLatest($actorId);
        $latestAnalytics = $analyticHandler->formatChartData($latestAnalytics);

        $list = compact(
            "latestAnalytics", "actor", "actorId",
            "campaignId", "campaign", 'performance', 'postFrequency',
            'activeCampaigns', 'upcomingCampaigns', 'previousCampaigns',
        );
        return Views("VIEW_CREATOR", $list);
    }



    public static function creators(): mixed  {
        $actorHandler = Methods::actors();
        $mediaHandler = Methods::actorMedia();
        $estimator = Methods::mediaEstimations();
        $actorRelationHandler = Methods::actorRelations();


        $actorIds = $actorRelationHandler->getRelationActorIds(!Methods::isAdmin() ? __oUuid() : null);
        $mediaIds = $mediaHandler->queryBuilder()->select(['uid'])->where('uid', $actorIds)
            ->all()->map(function ($row) { return $row['uid']; })->toArray();
        $mediaIdsThisMonth = $mediaHandler->queryBuilder()->select(['uid'])->where('uid', $actorIds)
            ->where('timestamp', '>=', strtotime('first day of this month 00:00:00'))
            ->all()->map(function ($row) { return $row['uid']; })->toArray();

        $creatorMetrics = $mediaHandler->uniqueCreatorMetrics(0, $mediaIds);
        $creatorMetricsThisMonth = $mediaHandler->uniqueCreatorMetrics(0, $mediaIdsThisMonth);


        $totalReach = $totalReachThisMonth = $totalInteractions = $totalFollowers = $totalInteractionsThisMonth = 0;
        foreach ($creatorMetrics->list() as $item) {
            $actor = $item->actor;
            if(isEmpty($actor)) continue;
            $totalReach += (int)$item->total_reach;
            $totalInteractions += (int)$actor->total_interactions;
            $totalFollowers += (int)$actor->total_interactions;
            $zeroImpressionsRowIds = $estimator->zeroImpressionsRowIds($actor->uid, $mediaIds);
            if(!empty($zeroImpressionsRowIds)) {
                $estimationTotals = $estimator->getTotalsByActorId($actor->uid, $zeroImpressionsRowIds);
                if(!isEmpty($estimationTotals)) {
                    $totalReach += $estimationTotals->reach;
                }
            }
        }
        foreach ($creatorMetricsThisMonth->list() as $item) {
            $actor = $item->actor;
            if(isEmpty($actor)) continue;
            $totalInteractionsThisMonth += (int)$actor->total_interactions;
            $zeroImpressionsRowIds = $estimator->zeroImpressionsRowIds($actor->uid, $mediaIdsThisMonth);
            if(!empty($zeroImpressionsRowIds)) {
                $estimationTotals = $estimator->getTotalsByActorId($actor->uid, $zeroImpressionsRowIds);
                if(!isEmpty($estimationTotals)) {
                    $totalReachThisMonth += $estimationTotals->reach;
                }
            }
        }

        $totalInteractionsBeforeThisMonth = $totalInteractions - $totalInteractionsThisMonth;
        $engagement = Methods::dataHandler()->calculateEngagement($totalInteractions, $totalFollowers);
        $engagementLastMonth = Methods::dataHandler()->calculateEngagement($totalInteractionsBeforeThisMonth, $totalFollowers);
        $engagementDifference = $engagement - $engagementLastMonth;

        $list = [
            "engagementIncrease" => $engagementDifference,
            "engagement" => $engagement,
            "totalReach" => $totalReach,
            "totalReachThisMonth" => $totalReachThisMonth,
            "maximumCreators" => Methods::app()->getCreatorUsage(),
            "currentCreators" => Methods::actorRelations()->currentUsage()
        ];
        return Views("CREATORS", $list);
    }




}