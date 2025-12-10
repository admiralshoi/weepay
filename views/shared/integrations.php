<?php
/**
 * @var object $args
 * @var string|null $pageHeaderTitle
 */

use classes\app\OrganisationPermissions;
use classes\Methods;
use classes\utility\Titles;
use features\Settings;


$pageTitle = "Social Integrations";

$upcomingSubscriptionDecrease = Methods::subscriptions()->upcomingDowngrade();
$hasSubscription = Methods::subscriptions()->hasActiveSubscription();

/*
 * More variables around here.
 */

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "integrations";
    thirdPartyAuth = <?=json_encode($args->auth)?>;



    var integrationUsageRemainingZero = <?=$args->integrationUsage - $args->currentUsage === 0 ? 1 : 0?>;
    var integrationsDisabledZero = <?=$args->allDisabled->count() === 0 ? 1 : 0?>;
    var integrationUsage = <?=$args->integrationUsage?>;
    var integrationCurrentUsage = <?=$args->currentUsage?>;
    var disabledInstagramAccounts = <?=json_encode($args->allDisabled->toArray())?>;
    var enabledAccounts = <?=json_encode($args->allEnabled->toArray())?>;
    var allAvailable = <?=json_encode($args->allAvailable->toArray())?>;
    var integrations = <?=json_encode($args->integrations->toArray())?>;


</script>
<div class="page-content position-relative" data-page="integrations">
    <div class="page-inner-content">

        <div class="flex-row-between flex-align-center flex-nowrap"  id="nav">
            <div class="">
                <div class="flex-row-start flex-align-center flex-nowrap">
                    <p class="text-xl font-weight-bold"><?=$pageHeaderTitle?></p>
                </div>
            </div>

            <div class="flex-row-end flex-align-center flex-nowrap">
                <div class="flex-row-end flex-align-center">
                </div>
            </div>
        </div>





        <?php if(!$args->allDisabled->empty() || !$args->allEnabled->empty()): ?>


        <?php OrganisationPermissions::__oReadProtectedContent('integrations'); ?>


        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-radius-10px">
                    <div class="card-body position-relative">
                        <div class="flex-row-between flex-align-start flex-wrap" style="gap: .5rem;">
                            <div class="flex-col-start" style="column-gap: .5rem;">
                                <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                    <p class="font-18 font-weight-bold mb-0">Enabled Instagram Accounts</p>
                                </div>
                                <p class="font-14 color-gray mb-0">
                                    From your social integrations, choose which Instagram accounts to use
                                </p>
                            </div>

                            <div class="flex-row-end">
                                <?php if($args->integrationUsage - $args->currentUsage > 5): ?>
                                <p class="success-box"><?=$args->currentUsage?> / <?=$args->integrationUsage?> Integrations available</p>
                                <?php elseif($args->integrationUsage - $args->currentUsage > 0): ?>
                                <p class="success-box"><?=$args->currentUsage?> / <?=$args->integrationUsage?> Integrations available</p>
                                <?php else: ?>
                                    <p class="danger-box"><?=$args->currentUsage?> / <?=$args->integrationUsage?> Integrations available</p>
                                <?php endif; ?>
                            </div>
                        </div>




                        <div class="row align-items-stretch mt-3" style="row-gap: 1rem;">
                            <?php foreach ($args->allEnabled->list() as $account): ?>

                            <div class="col-12 col-md-6 col-xl-4 d-flex">
                                <div class="card border-radius-10px w-100">
                                    <div class="top-inner-border bg-danger"></div>
                                    <div class="card-body position-relative">
                                        <div class="status-indicator success"></div>
                                        <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                                            <?php
                                            $icon = "";
                                            if($account->provider === 'facebook') $icon = "fa-brands fa-facebook";
                                            elseif($account->provider === 'instagram') $icon = "fa-brands fa-instagram";
                                            ?>
                                            <?php if(!empty($account->profile_picture)): ?>
                                            <img class="square-60 border-radius-50 border-info-danger" src="<?=resolveImportUrl($account->profile_picture)?>"
                                            <?php else: ?>
                                            <div class="square-60 flex-row-center flex-align-center border-radius-50 p-1 bg-danger-bg">
                                                <i class="<?=$icon?> color-danger-text font-32"></i>
                                            </div>
                                            <?php endif; ?>
                                            <p class="mb-0 font-16 font-weight-medium"><?=ucfirst($account->provider)?></p>
                                            <?php if(!empty($account->username)): ?>
                                            <p class="mb-0 mute-box text-center flex-row-center flex-align-center flex-nowrap" style="column-gap: 3px;">
                                                <i class="fa-solid fa-at"></i>
                                                <span>
                                                    <?=$account->username?>
                                                </span>
                                            </p>
                                            <?php endif; ?>

                                            <p class="mb-0 font-14">
                                                <?=!isEmpty($account->actor) ? \classes\utility\Numbers::shortenNumber($account->actor->followers_count) . " followers" :
                                                    $account->name?>
                                            </p>

                                            <button class="btn-v2 mute-danger-btn flex-row-center flex-align-center" style="column-gap: 1rem;"
                                                    onclick="disconnectInstagramAccount('<?=$account->uid?>')">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                                <span class="text-nowrap">Disconnect</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php endforeach; ?>

                            <div class="col-12 col-md-6 col-xl-4 d-flex">
                                <div class="card noBackground border-dashed border-radius-10px w-100">
                                    <div class="card-body position-relative">
                                        <div class="flex-col-center flex-align-center h-100" style="row-gap: .5rem;">
                                            <button onclick="enableInstagramAccounts()"
                                                    class="border-none square-60 flex-row-center flex-align-center border-radius-50 p-1 bg-wrapper-sibling">
                                                <i class="fa-solid fa-plus color-gray font-32"></i>
                                            </button>
                                            <p class="mb-0 font-18 font-weight-medium">Add Instagram Account</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <?php if($hasSubscription): ?>

            <?php if($upcomingSubscriptionDecrease): ?>

                <div class="row mt-4" style="row-gap: .75rem">
                    <div class="col-12">
                        <div class="danger-info-box">
                            <div class="info-title">
                                <p class="font-16 font-weight-bold mb-0">Your subscription will be degraded</p>
                            </div>
                            <div class="flex-col-start flex-align-start">
                                <p class="font-14  mb-0">Your subscription tier or quantity will decrease from the next billing cycle</p>
                                <p class="font-14  mb-0">
                                    Ensure that you have disabled any social accounts you're not planning to use, as any
                                    amount exceeding your new capacity will be forcibly disabled, potentially affecting active and upcoming campaigns.
                                </p>
                                <a href="<?=__url(ORGANISATION_PANEL_PATH . '/my-subscription')?>"
                                   class="btn-v2 danger-btn mt-2">View change</a>
                            </div>

                        </div>
                    </div>
                </div>

            <?php else: ?>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="action-info-box">
                            <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                                <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                    <div class="square-50 flex-row-center flex-align-center border-radius-50 p-1 " style="background: #e3ebf9">
                                        <i class="mdi mdi-help-circle-outline color-action-info-text font-28"></i>
                                    </div>
                                    <div class="flex-col-start flex-align-start">
                                        <p class="mb-0 font-weight-bold font-16">Need More Instagram Accounts?</p>
                                        <p class="mb-0 font-weight-medium color-gray font-14">
                                            Your current plan allows for <?=$args->integrationUsage?> Instagram
                                            <?=Titles::pluralS($args->integrationUsage, 'account')?>. Upgrade to connect more accounts.
                                        </p>
                                    </div>
                                </div>

                                <div class="flex-row-end">
                                    <a class="btn-v2 action-btn text-nowrap" href="<?=__url(ORGANISATION_PANEL_PATH . '/my-subscription')?>">
                                        Upgrade Plan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        <?php else: ?>

            <div class="row mt-4" style="row-gap: .75rem">
                <div class="col-12">
                    <div class="action-info-box">
                        <div class="info-title">
                            <p class="font-16 font-weight-bold mb-0">Subscribe to increase capacity</p>
                        </div>
                        <div class="flex-col-start flex-align-start">
                            <p class="font-14  mb-0">You current integration capacity is <?=$args->integrationUsage?></p>
                            <p class="font-14  mb-0">
                                To enable social accounts to be used in campaigns and other tracking, you might want to consider
                                increasing your capacity to track more of your brands and accounts.
                            </p>
                            <a href="<?=__url( 'premium')?>"
                               class="btn-v2 action-btn mt-2">Subscribe Now</a>
                        </div>

                    </div>
                </div>
            </div>

        <?php endif; ?>


        <?php OrganisationPermissions::__oEndContent(); ?>

        <?php endif; ?>

        <?php OrganisationPermissions::__oReadProtectedContent('integrations'); ?>


        <div class="row mt-4" style="row-gap: 2rem">

            <div class="col-12">
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 0">
                            <div class="flex-row-between flex-align-center flex-wrap" style="gap: .5rem;">
                                <p class="font-22 font-weight-bold mb-0">Connect Through Social Providers</p>

                                <?php OrganisationPermissions::__oModifyProtectedContent('integrations'); ?>
                                <div class="flex-row-end flex-align-center flex-wrap " style="gap: .5rem;">
                                    <select class="form-select-v2 mnw-200px" name="role">
                                        <option value="" selected>Connect Account</option>
                                        <option value="facebook" data-href="<?=$args->auth->facebook->link?>">
                                            <i class="mdi mdi-facebook"></i> Facebook
                                        </option>
                                    </select>
                                </div>
                                <?php OrganisationPermissions::__oEndContent(); ?>
                            </div>
                            <?php if(Methods::isBrand()): ?>
                                <p class="font-14 color-gray font-weight-medium mb-0">Login and integrate through social providers to connect your accounts</p>
                            <?php elseif(Methods::isCreator()): ?>
                                <p class="font-14 color-gray font-weight-medium  mb-0">
                                    Link your Instagram creator accounts to automatically track your campaign activity insights
                                </p>
                            <?php else: ?>

                                <!--            Eg. Admin-->

                            <?php endif; ?>

                        </div>

                        <div class="flex-col-start mt-2">
                            <?php foreach ($args->integrations->list() as $integration): ?>

                                <div class="pt-3 pb-3 border-bottom-card">
                                    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .5rem; row-gap: .75rem;">
                                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                            <?php
                                            $icon = "";
                                            if($integration->provider === 'facebook') $icon = "fa-brands fa-facebook";
                                            elseif($integration->provider === 'instagram') $icon = "fa-brands fa-instagram";
                                            ?>
                                            <i class="color-dark font-20 <?=$icon?>"></i>
                                            <div class="flex-col-start">
                                                <p class="font-16 mb-0">
                                                    <?=ucfirst($integration->provider)?>
                                                    <span class="font-14 color-gray">(<?=$integration->created_display_date?>)</span>
                                                </p>
                                                <p class="font-14 color-gray mb-0">Connected by <?=Titles::prettifiedUppercase($integration->integrated_by->full_name)?></p>
                                            </div>
                                        </div>
                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">

                                            <?php OrganisationPermissions::__oModifyProtectedContent('integrations'); ?>
                                            <button class="btn-v2 mute-btn square-30 flex-align-center flex-row-center"
                                                onclick="manageIntegration('<?=$integration->uid?>')" data-toggle="tooltip" title="Manage integration">
                                                <i class="mdi mdi-cog"></i>
                                            </button>
                                            <button class="btn-v2 danger-btn square-30 flex-align-center flex-row-center"
                                                    onclick="removeIntegration('<?=$integration->uid?>')" data-toggle="tooltip" title="Remove integration">
                                                <i class="mdi mdi-trash-can"></i>
                                            </button>
                                            <?php OrganisationPermissions::__oEndContent(); ?>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>


                        </div>
                    </div>
                </div>
            </div>

        </div>





        <?php OrganisationPermissions::__oEndContent(); ?>

    </div>
</div>

