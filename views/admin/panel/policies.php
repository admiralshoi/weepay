<?php
/**
 * Admin Panel - Policies Overview
 * @var object $args - Contains policies and typeNames
 */

use classes\enumerations\Links;

$policies = $args->policies ?? (object)[];
$pageTitle = "Politikker";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "policies";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Politikker</h1>
                    <p class="mb-0 font-14 color-gray">Administrer privatlivspolitik, vilkår og cookies</p>
                </div>
            </div>

            <!-- Consumer Policies Section -->
            <div class="flex-col-start" style="gap: 1rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Forbruger politikker</p>

                <div class="row rg-15">
                    <!-- Consumer Privacy Policy -->
                    <div class="col-12 col-md-6">
                        <?php
                        $policy = $policies->consumer_privacy ?? null;
                        $hasPublished = $policy && !empty($policy->current_version);
                        $hasDraft = $policy && $policy->has_draft;
                        ?>
                        <div class="card border-radius-10px h-100">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-shield-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start flex-grow-1">
                                        <div class="flex-row-between flex-align-center w-100">
                                            <p class="mb-0 font-16 font-weight-bold color-dark">Privatlivspolitik</p>
                                            <?php if ($hasDraft): ?>
                                                <span class="warning-box font-10">Kladde</span>
                                            <?php elseif ($hasPublished): ?>
                                                <span class="success-box font-10">Publiceret</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 font-12 color-gray">
                                            <?php if ($hasPublished): ?>
                                                Version <?=$policy->current_version->version?> · Publiceret <?=date('d/m/Y', strtotime($policy->current_version->published_at))?>
                                            <?php else: ?>
                                                Ingen publiceret version
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-row-start mt-3" style="gap: 0.5rem;">
                                    <a href="<?=__url(Links::$admin->panelPoliciesPrivacy)?>?type=consumer" class="btn-v2 action-btn"><i class="mdi mdi-pencil mr-1"></i> Rediger</a>
                                    <?php if ($hasPublished): ?>
                                        <a href="<?=__url(Links::$policies->consumer->privacy)?>" target="_blank" class="btn-v2 mute-btn"><i class="mdi mdi-open-in-new mr-1"></i> Se live</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Consumer Terms -->
                    <div class="col-12 col-md-6">
                        <?php
                        $policy = $policies->consumer_terms ?? null;
                        $hasPublished = $policy && !empty($policy->current_version);
                        $hasDraft = $policy && $policy->has_draft;
                        ?>
                        <div class="card border-radius-10px h-100">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-green border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-file-document-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start flex-grow-1">
                                        <div class="flex-row-between flex-align-center w-100">
                                            <p class="mb-0 font-16 font-weight-bold color-dark">Handelsbetingelser</p>
                                            <?php if ($hasDraft): ?>
                                                <span class="warning-box font-10">Kladde</span>
                                            <?php elseif ($hasPublished): ?>
                                                <span class="success-box font-10">Publiceret</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 font-12 color-gray">
                                            <?php if ($hasPublished): ?>
                                                Version <?=$policy->current_version->version?> · Publiceret <?=date('d/m/Y', strtotime($policy->current_version->published_at))?>
                                            <?php else: ?>
                                                Ingen publiceret version
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-row-start mt-3" style="gap: 0.5rem;">
                                    <a href="<?=__url(Links::$admin->panelPoliciesTerms)?>?type=consumer" class="btn-v2 action-btn"><i class="mdi mdi-pencil mr-1"></i> Rediger</a>
                                    <?php if ($hasPublished): ?>
                                        <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" target="_blank" class="btn-v2 mute-btn"><i class="mdi mdi-open-in-new mr-1"></i> Se live</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Merchant Policies Section -->
            <div class="flex-col-start" style="gap: 1rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Erhverv politikker</p>

                <div class="row rg-15">
                    <!-- Merchant Privacy Policy -->
                    <div class="col-12 col-md-6">
                        <?php
                        $policy = $policies->merchant_privacy ?? null;
                        $hasPublished = $policy && !empty($policy->current_version);
                        $hasDraft = $policy && $policy->has_draft;
                        ?>
                        <div class="card border-radius-10px h-100">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-purple border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-lock-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start flex-grow-1">
                                        <div class="flex-row-between flex-align-center w-100">
                                            <p class="mb-0 font-16 font-weight-bold color-dark">Privatlivspolitik</p>
                                            <?php if ($hasDraft): ?>
                                                <span class="warning-box font-10">Kladde</span>
                                            <?php elseif ($hasPublished): ?>
                                                <span class="success-box font-10">Publiceret</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 font-12 color-gray">
                                            <?php if ($hasPublished): ?>
                                                Version <?=$policy->current_version->version?> · Publiceret <?=date('d/m/Y', strtotime($policy->current_version->published_at))?>
                                            <?php else: ?>
                                                Ingen publiceret version
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-row-start mt-3" style="gap: 0.5rem;">
                                    <a href="<?=__url(Links::$admin->panelPoliciesPrivacy)?>?type=merchant" class="btn-v2 action-btn"><i class="mdi mdi-pencil mr-1"></i> Rediger</a>
                                    <?php if ($hasPublished): ?>
                                        <a href="<?=__url(Links::$policies->merchant->privacy)?>" target="_blank" class="btn-v2 mute-btn"><i class="mdi mdi-open-in-new mr-1"></i> Se live</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Merchant Terms -->
                    <div class="col-12 col-md-6">
                        <?php
                        $policy = $policies->merchant_terms ?? null;
                        $hasPublished = $policy && !empty($policy->current_version);
                        $hasDraft = $policy && $policy->has_draft;
                        ?>
                        <div class="card border-radius-10px h-100">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-cyan border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-file-document-outline color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start flex-grow-1">
                                        <div class="flex-row-between flex-align-center w-100">
                                            <p class="mb-0 font-16 font-weight-bold color-dark">Vilkår for forhandlere</p>
                                            <?php if ($hasDraft): ?>
                                                <span class="warning-box font-10">Kladde</span>
                                            <?php elseif ($hasPublished): ?>
                                                <span class="success-box font-10">Publiceret</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 font-12 color-gray">
                                            <?php if ($hasPublished): ?>
                                                Version <?=$policy->current_version->version?> · Publiceret <?=date('d/m/Y', strtotime($policy->current_version->published_at))?>
                                            <?php else: ?>
                                                Ingen publiceret version
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-row-start mt-3" style="gap: 0.5rem;">
                                    <a href="<?=__url(Links::$admin->panelPoliciesTerms)?>?type=merchant" class="btn-v2 action-btn"><i class="mdi mdi-pencil mr-1"></i> Rediger</a>
                                    <?php if ($hasPublished): ?>
                                        <a href="<?=__url(Links::$policies->merchant->termsOfUse)?>" target="_blank" class="btn-v2 mute-btn"><i class="mdi mdi-open-in-new mr-1"></i> Se live</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Policies Section -->
            <div class="flex-col-start" style="gap: 1rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Generelle politikker</p>

                <div class="row rg-15">
                    <!-- Cookies Policy -->
                    <div class="col-12 col-md-6">
                        <?php
                        $policy = $policies->cookies ?? null;
                        $hasPublished = $policy && !empty($policy->current_version);
                        $hasDraft = $policy && $policy->has_draft;
                        ?>
                        <div class="card border-radius-10px h-100">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                                    <div class="square-50 bg-orange border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-cookie color-white font-24"></i>
                                    </div>
                                    <div class="flex-col-start flex-grow-1">
                                        <div class="flex-row-between flex-align-center w-100">
                                            <p class="mb-0 font-16 font-weight-bold color-dark">Cookiepolitik</p>
                                            <?php if ($hasDraft): ?>
                                                <span class="warning-box font-10">Kladde</span>
                                            <?php elseif ($hasPublished): ?>
                                                <span class="success-box font-10">Publiceret</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-0 font-12 color-gray">
                                            <?php if ($hasPublished): ?>
                                                Version <?=$policy->current_version->version?> · Publiceret <?=date('d/m/Y', strtotime($policy->current_version->published_at))?>
                                            <?php else: ?>
                                                Ingen publiceret version
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex-row-start mt-3" style="gap: 0.5rem;">
                                    <a href="<?=__url(Links::$admin->panelPoliciesCookies)?>" class="btn-v2 action-btn"><i class="mdi mdi-pencil mr-1"></i> Rediger</a>
                                    <?php if ($hasPublished): ?>
                                        <a href="<?=__url(Links::$policies->cookies)?>" target="_blank" class="btn-v2 mute-btn"><i class="mdi mdi-open-in-new mr-1"></i> Se live</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
