<?php
/**
 * @var object $args
 * @var string $oidcSessionId
 * @var string|null $authError
 */

use classes\enumerations\Links;

$oidcSessionId = $args->oidcSessionId;
$authError = $args->authError ?? null;

$pageTitle = "Opret konto";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>




<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-col-start" style="row-gap: 1.5rem;">
                        <a href="<?=__url(Links::$consumer->public->home)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til <?=BRAND_NAME?></span>
                        </a>

                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50 " >
                                <i class="font-35 color-design-blue mdi mdi-account-plus-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Tilmeld dig - <?=BRAND_NAME?></p>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Opret din konto med MitID</p>
                        </div>

                        <?php if(!isEmpty($authError)): ?>
                        <div class="alert alert-danger flex-row-start flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-alert-circle-outline font-20"></i>
                            <span><?=htmlspecialchars($authError)?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <p class="mb-0 font-14 color-gray text-center">
                                For at sikre din identitet og beskytte dine data, bruger vi MitID til sikker login.
                            </p>

                            <?php if(!isEmpty($oidcSessionId)): ?>
                            <button type="button" class="mt-3  btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap oidc-auth" style="gap: .75rem;" data-id="<?=$oidcSessionId?>">
                                <i class="mdi mdi-shield-outline font-16"></i>
                                <span class="font-16">Fortsæt med MitId</span>
                            </button>
                            <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="mdi mdi-alert"></i> MitID tilmelding er midlertidigt utilgængelig. Prøv igen senere.
                            </div>
                            <?php endif; ?>

                            <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #f8f9fa; border-radius: 8px;">
                                <i class="mdi mdi-information-outline color-blue font-18" style="margin-top: 2px;"></i>
                                <div class="flex-col-start" style="row-gap: .25rem;">
                                    <p class="mb-0 font-13 font-weight-bold">Hvorfor MitID?</p>
                                    <p class="mb-0 font-12 color-gray">
                                        MitID sikrer, at kun du kan tilgå din konto. Din identitet verificeres automatisk,
                                        og du behøver ikke at huske en adgangskode.
                                    </p>
                                </div>
                            </div>

                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium" style="gap: .25rem;">
                                <span class="color-gray font-13">Har du allerede en konto?</span>
                                <a href="<?=__url(Links::$consumer->public->login)?>" class="color-blue font-13 hover-underline">Log ind her</a>
                            </div>
                        </div>


                        <div class="alternative-box color-gray">
                            <span class="alternative-line"></span>
                        </div>

                        <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                            <div class="trans-info-badge">
                                <i class="mdi mdi-shield-check-outline color-green font-16"></i>
                                <span>MitID verificeret</span>
                            </div>
                            <div class="trans-info-badge">
                                <i class="mdi mdi-lock-outline color-blue font-16"></i>
                                <span>Sikker login</span>
                            </div>
                            <div class="trans-info-badge">
                                <i class="mdi mdi-check-circle-outline color-green font-16"></i>
                                <span>GDPR-kompatibel</span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" class="color-gray font-12 hover-underline hover-color-blue">Vilkår & Betingelser</a>
                &bullet;
                <a href="<?=__url(Links::$policies->consumer->privacy)?>" class="color-gray font-12 hover-underline hover-color-blue">Privatlivspolitik</a>
                &bullet;
                <a href="<?=__url(Links::$support->public)?>" class="color-gray font-12 hover-underline hover-color-blue">Support</a>
            </div>
        </div>

    </div>
</div>




