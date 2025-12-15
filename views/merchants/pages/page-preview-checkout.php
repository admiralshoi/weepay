<?php
/**
 * @var object $args
 * Location Checkout Preview
 * Live preview of checkout flow with draft page content
 */

use classes\Methods;

$location = $args->location;
$page = $args->pageDraft;
$slug = $args->slug;
$draftId = $args->draftId;
$addressString = $args->addressString;
$contactEmail = $args->contactEmail;
$contactPhone = $args->contactPhone;

$pageTitle = "{$location->name} - Checkout Forhåndsvisning";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var draftId = <?=json_encode($draftId)?>;
    var locationSlug = <?=json_encode($slug)?>;
</script>

<!-- Preview Header -->
<div class="bg-warning text-center py-2 sticky-top" style="z-index: 1000;">
    <p class="mb-0 font-14 font-weight-medium">
        <i class="mdi mdi-eye-outline mr-1"></i>
        Checkout Forhåndsvisning - Denne side opdateres live mens du redigerer
    </p>
</div>

<div class="page-content mt-5">
    <div class="page-inner-content">
        <div class="stepper-progress">
            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">1</div>
                <div class="stepper-label">Login</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">2</div>
                <div class="stepper-label">Info</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Vælg og Bekræft</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">4</div>
                <div class="stepper-label">Betal</div>
            </div>
        </div>

        <div class="flex-col-start flex-align-center mt-5" style="row-gap: .75rem;">
            <p class="design-box mb-0 px-2">
                <i class="mdi mdi-store"></i>
                <span class="font-weight-bold">Du Handler hos</span>
            </p>

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p id="preview-checkout-location-name" class="mb-0 font-25 font-weight-bold"><?=$location->name?></p>
                <p id="preview-checkout-caption" class="mb-0 font-14 font-weight-medium color-gray"><?=$page->caption?></p>
            </div>

            <div class="card border-radius-10px w-100">
                <div class="w-100 h-200px overflow-hidden">
                    <div
                        id="preview-checkout-hero"
                        class="w-100 h-100 overflow-hidden bg-cover"
                        style="
                            border-radius: 10px 10px 0 0;
                            aspect-ratio: 16/9;
                            background-image: url('<?=resolveImportUrl($page->hero_image)?>');
                        "
                    ></div>
                </div>

                <div class="py-3 px-4 w-100 flex-col-start" style="row-gap: .5rem;">
                    <div class="flex-col-start border-bottom-card pb-3" style="row-gap: .5rem;">
                        <?php if(!isEmpty($contactEmail)): ?>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                <i class="mdi mdi-email-outline color-design-blue font-16"></i>
                                <p class="mb-0 font-14"><?=$contactEmail?></p>
                            </div>
                        <?php endif; ?>
                        <?php if(!isEmpty($contactPhone)): ?>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                <i class="mdi mdi-phone-outline color-design-blue font-16"></i>
                                <p class="mb-0 font-14"><?=$contactPhone?></p>
                            </div>
                        <?php endif; ?>
                        <?php if(!isEmpty($addressString)): ?>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                <i class="mdi mdi-map-marker-outline color-design-blue font-16"></i>
                                <p class="mb-0 font-14"><?=$addressString?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-col-start pt-3" style="row-gap: 1.5rem;">
                        <div class="action-mute-info-box">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                                <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-shield-outline"></i></div>
                                <p class="mb-0 info-title color-dark">For at fortsætte skal du:</p>
                            </div>
                            <div class="info-content mt-2">
                                <ul class="pl-1 line-spacing">
                                    <li>Verificere din identitet med MitID</li>
                                    <li>Godkende en hurtig kreditvurdering (10 sek)</li>
                                    <li>Vælge din betalingsplan</li>
                                </ul>
                            </div>
                        </div>

                        <div class="vision-card px-4 py-3 border-radius-10px w-100 flex-align-center" style="gap: .5rem;">
                            <div class="flex-row-center flex-align-center square-75 bg-wrapper-hover border-radius-50 " >
                                <i class="font-40 color-design-blue mdi mdi-shield-outline"></i>
                            </div>

                            <p class="mb-0 font-22 font-weight-bold text-center">Log ind med MitID</p>
                            <p class="mb-0 font-14 color-gray font-weight-medium text-center">Vi bruger MitID for at verificere din identitet sikkert</p>

                            <button class="mt-3 btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap" style="gap: .55rem; pointer-events: none; opacity: 0.7;">
                                <i class="mdi mdi-shield-outline font-18"></i>
                                <span class="font-18">Bekræft med MitID</span>
                            </button>
                            <p class="mb-0 font-12 color-mute text-center mt-2">
                                <i class="mdi mdi-information-outline mr-1"></i>
                                Dette er en forhåndsvisning - MitID login er deaktiveret
                            </p>
                        </div>
                    </div>
                </div>

            </div>

    </div>
</div>
