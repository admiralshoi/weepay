<?php

namespace classes\app;

use classes\Methods;
use classes\utility\Numbers;
use Database\Collection;
use features\Settings;

class App {

    public function getIntegrationUsage(): int {
        $minimum = 1;
        $subscriptionHandler = Methods::subscriptions();
        $subscription = $subscriptionHandler->getActiveSubscription();
        if(isEmpty($subscription)) return $minimum;

        $usage = $subscriptionHandler->getSubscriptionUsage($subscription);
        if(isEmpty($usage)) return $minimum;
        $integrationUsage = $usage->integrations->value;
        return max($minimum, ($integrationUsage * $subscription->quantity));
    }

    public function getCreatorUsage(): int {
        $minimum = 5;
        $subscriptionHandler = Methods::subscriptions();
        $subscription = $subscriptionHandler->getActiveSubscription();
        if(isEmpty($subscription)) return $minimum;

        $usage = $subscriptionHandler->getSubscriptionUsage($subscription);
        if(isEmpty($usage)) return $minimum;
        $creatorUsage = $usage->creators->value;
        return max($minimum, ($creatorUsage * $subscription->quantity));
    }

    public function campaignDaysInfo(): object {
        $subscription = Methods::subscriptions()->getActiveSubscription();
        $subscriptionDaysAvailable = $unrestrictedDaysAvailable = $subscriptionDaysUsed = $unrestrictedDaysUsed = 0;
        $subscriptionDaysRemaining = $unrestrictedDaysRemaining = $recurringBalance = 0;
        $nextCutoff = 0;
        $subscriptionPastDue = false;

        if(!isEmpty($subscription)) {
            $subscriptionDays = Methods::campaignDays()->getFromPeriod(
                $subscription?->current_period_start,
                $subscription?->current_period_end,
                ['item' => $subscription?->uid],
            );

            $subscriptionDaysAvailable = $subscriptionDays->empty() ? 0 : $subscriptionDays->reduce(function ($initial, $row) {
                return (!isset($initial) ? 0 : $initial) + $row['amount'];
            });
            $subscriptionDaysUsed = Methods::campaignDaysUsage()->getUsedAmount($subscriptionDays);
            $subscriptionDaysRemaining = $subscriptionDaysAvailable - $subscriptionDaysUsed;
        }

        $unrestrictedDays = Methods::campaignDays()->getByX(['uuid' => __oUuid(), 'restricted' => 0], ['uid', 'amount']);
        if(!isEmpty($unrestrictedDays)) {
            $unrestrictedDaysAvailable = $unrestrictedDays->reduce(function ($initial, $row) {
                return (!isset($initial) ? 0 : $initial) + $row['amount'];
            });


            $unrestrictedDaysUsed = Methods::campaignDaysUsage()->getUsedAmount($unrestrictedDays);
            $unrestrictedDaysRemaining = $unrestrictedDaysAvailable - $unrestrictedDaysUsed;
        }

        $totalDaysAvailable = $subscriptionDaysAvailable + $unrestrictedDaysAvailable;
        $totalDaysUsed = $subscriptionDaysUsed + $unrestrictedDaysUsed;
        $totalDaysRemaining = $subscriptionDaysRemaining + $unrestrictedDaysRemaining;


        if(!isEmpty($subscription) && $subscription->status !== 'canceled') {
            $subscriptionPastDue = $subscription->status === 'past_due';
            $nextCutoff = strtotime(date('Y-m-d', $subscription->current_period_end + (3600 * 24))); //Start of next day.
            $history = Methods::subscriptionHistory()->getLatest($subscription->uid);
            $usage = Methods::subscriptions()->getSubscriptionUsage($subscription, true);
            $recurringBalance = $history->line_item->quantity * $usage->days->value;
        }


        return toObject(compact(
            'totalDaysAvailable',
            'totalDaysUsed',
            'totalDaysRemaining',
            'subscriptionDaysAvailable',
            'unrestrictedDaysAvailable',
            'subscriptionDaysUsed',
            'unrestrictedDaysUsed',
            'subscriptionDaysRemaining',
            'unrestrictedDaysRemaining',
            'nextCutoff',
            'recurringBalance',
            'subscriptionPastDue',
        ));
    }


    public function allowedFeatures(?object $campaignDaysInfo = null):object {
        $campaignTypes = Settings::$app->campaignTypes;
        $campaignTrackingMethods = Settings::$app->campaignTrackingMethods;

        $activeSubscription = Methods::subscriptions()->getActiveSubscription();
        $subscriptionProductId = isEmpty($activeSubscription) ? null : $activeSubscription->price->product->uid;
        $collection = ['trackingMethods' => [], 'campaignTypes' => []];

        if($campaignDaysInfo === null) $campaignDaysInfo = $this->campaignDaysInfo();
        $unrestrictedAvailable = $campaignDaysInfo->unrestrictedDaysRemaining > 0;

        $featureHandler = Methods::features();
        foreach($campaignTypes as $campaignType => $info) {
            if(!$info->enabled) continue;
            $subEnabled = false;
            if(!empty($subscriptionProductId)) {
                $subEnabled = $featureHandler->getColumn(['product' => $subscriptionProductId, 'name' => $campaignType, 'type' => 'campaign'], 'value') === 1;
            }

            $collection['campaignTypes'][$campaignType] = [
                'title' => $info->title,
                'name' => $campaignType,
                'description' => $info->description,
                'subEnabled' => $subEnabled,
                'unrestrictedEnabled' => $unrestrictedAvailable,
                'enabled' => $unrestrictedAvailable || $subEnabled,
                'mostCommon' => $campaignType === 'tracking'
            ];
        }

        foreach($campaignTrackingMethods as $method => $info) {
            if(!$info->enabled) continue;
            $subEnabled = false;
            if(!empty($subscriptionProductId)) {
                $subEnabled = $featureHandler->getColumn(['product' => $subscriptionProductId, 'name' => $method, 'type' => 'tracking'], 'value') === 1;
            }

            $collection['trackingMethods'][$method] = [
                'title' => $info->title,
                'name' => $method,
                'description' => $info->description,
                'subEnabled' => $subEnabled,
                'unrestrictedEnabled' => $unrestrictedAvailable,
                'enabled' => $unrestrictedAvailable || $subEnabled,
                'mostCommon' => $method === 'mention'
            ];
        }

        return toObject($collection);
    }


    public function campaignRunDetails(
        ?Collection $activeCampaigns = null,
        ?string $focusCampaignId = null,
        ?object $features = null,
        ?object $campaignDaysInfo = null,
    ): ?object{
        if($campaignDaysInfo === null) $campaignDaysInfo = $this->campaignDaysInfo();
        if($features === null) $features = $this->allowedFeatures($campaignDaysInfo);

        $info = [
            'totalDaysRemaining' => $campaignDaysInfo->totalDaysRemaining,
            'subscriptionDaysRemaining' => $campaignDaysInfo->subscriptionDaysRemaining,
            'unrestrictedDaysRemaining' => $campaignDaysInfo->unrestrictedDaysRemaining,
            'recurringBalance' => $campaignDaysInfo->recurringBalance,
            'nextRebalance' => $campaignDaysInfo->nextCutoff,
            'subscriptionPastDue' => $campaignDaysInfo->subscriptionPastDue,
            'dailySubscriptionDaysConsumption' => 0,
            'requiredSubscriptionDaysUsageNext10Days' => 0,
            'requiredSubscriptionDaysUsageUntilCutoff' => 0,
            'dailyUnrestrictedDaysConsumption' => 0,
            'requiredUnrestrictedDaysUsageNext10Days' => 0,
            'canRun' => $focusCampaignId === null,
            'canRunNext10Days' => $focusCampaignId === null,
            'unRestricted' => false
        ];

        $daysHandler = Methods::campaignDaysUsage();
        $focusedCampaignUsingUnrestrictedDays = false;
        $daysUntilCutoff = Numbers::daysBetweenTimestamps(time(), $info['nextRebalance']);

        foreach ($activeCampaigns->list() as $campaign) {
            $campaignType = $campaign->type;
            $typeItem = $features->campaignTypes->$campaignType;

            $usingUnrestricted = false;

            if(!$typeItem->enabled) continue;
            if(!$typeItem->subEnabled) $usingUnrestricted = true;
            else {
                $enabledTracking = [];
                if($campaign->track_tag) $enabledTracking[] = $features->trackingMethods->tag;
                if($campaign->track_hashtag) $enabledTracking[] = $features->trackingMethods->hashtag;
                if($campaign->track_mention) $enabledTracking[] = $features->trackingMethods->mention;

                foreach ($enabledTracking as $tracking) {
                    if (!$tracking->enabled) continue;
                    if (!$tracking->subEnabled) $usingUnrestricted = true;
                }
            }

            $minimumRequiredToRun = (int)(!$daysHandler->exists(['campaign' => $campaign->uid, 'day' => date('Y-m-d')]));
            $requiredToRun10Days = min((10 - $minimumRequiredToRun), Numbers::daysBetweenTimestamps(time(), $campaign->end) - $minimumRequiredToRun);


            if(!$usingUnrestricted) {
                $info['dailySubscriptionDaysConsumption'] += 1;
                $info['requiredSubscriptionDaysUsageNext10Days'] += $requiredToRun10Days;
                $info['requiredSubscriptionDaysUsageUntilCutoff'] += min(Numbers::daysBetweenTimestamps(time(), $campaign->end - ($minimumRequiredToRun ? 1 : 0)), $daysUntilCutoff - $minimumRequiredToRun);
            }
            else {
                $info['dailyUnrestrictedDaysConsumption'] += 1;
                $info['requiredUnrestrictedDaysUsageNext10Days'] += $requiredToRun10Days;
            }

            if($focusCampaignId === $campaign->uid) {
                $info['unRestricted'] = $focusedCampaignUsingUnrestrictedDays = $usingUnrestricted;
                if($usingUnrestricted) {
                    $info['canRun'] = $info['unrestrictedDaysRemaining'] >= $minimumRequiredToRun;
                }
                else {
                    $info['canRun'] = $info['subscriptionDaysRemaining'] >= $minimumRequiredToRun;
                }
            }
        }

        $daysAvailable = $focusedCampaignUsingUnrestrictedDays ? $info['unrestrictedDaysRemaining'] : $info['subscriptionDaysRemaining'];
        $requiredUsage = $focusedCampaignUsingUnrestrictedDays ? $info['requiredUnrestrictedDaysUsageNext10Days'] : $info['requiredSubscriptionDaysUsageNext10Days'];
        $requiredUntilCutoff = $focusedCampaignUsingUnrestrictedDays ? $requiredUsage : $info['requiredSubscriptionDaysUsageUntilCutoff'];
        $unrestrictedUsageDiff = ($info['unrestrictedDaysRemaining'] - $info['requiredUnrestrictedDaysUsageNext10Days']);

        if(!$info['canRun'] && !$focusedCampaignUsingUnrestrictedDays) {
            $info['canRun'] = $unrestrictedUsageDiff > 0;
            $info['canRunNext10Days'] = $info['requiredUnrestrictedDaysUsageNext10Days'] < $info['unrestrictedDaysRemaining'];
            $info['unRestricted'] = true;
        }
        elseif(!$info['canRun']) $info['canRunNext10Days'] = false;
        else {
            if(!$focusedCampaignUsingUnrestrictedDays && $daysUntilCutoff >= 0 && $daysUntilCutoff < 10) {
                if($info['subscriptionDaysRemaining'] >= $requiredUntilCutoff) {
                    $remainingInNewPeriod = 10 - $daysUntilCutoff;
                    if($remainingInNewPeriod < $info['recurringBalance']) $info['canRunNext10Days'] = true;
                    if($remainingInNewPeriod < $unrestrictedUsageDiff) $info['canRunNext10Days'] = true;
                }
            }
            else {
                $info['canRunNext10Days'] = $requiredUsage < $daysAvailable;
            }
        }

        return toObject($info);
    }






}