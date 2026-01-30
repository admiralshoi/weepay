<?php
/**
 * Demo Consumer Start Page - MitID simulation
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location;
$pageTitle = "Demo - Kunde Login";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<!-- Demo Badge -->
<div class="demo-badge">
    <i class="mdi mdi-test-tube"></i>
    Demo Mode
</div>

<!-- Demo Reset Link -->
<a href="<?=__url(Links::$demo->landing)?>" class="demo-reset-link">
    <i class="mdi mdi-refresh"></i>
    Nulstil Demo
</a>

<div class="page-content mt-5">
    <div class="page-inner-content">

        <!-- Stepper -->
        <div class="stepper-progress demo-stepper">
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

        <!-- Store Header -->
        <div class="flex-col-start flex-align-center" style="row-gap: .75rem;">
            <p class="design-box mb-0 px-2">
                <i class="mdi mdi-store"></i>
                <span class="font-weight-bold">Du Handler hos</span>
            </p>

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="mb-0 font-25 font-weight-bold"><?=$location->name?></p>
                <p class="mb-0 font-14 font-weight-medium color-gray"><?=$location->caption?></p>
            </div>
        </div>

        <!-- Location Card -->
        <div class="card border-radius-10px w-100 mt-4">
            <div class="w-100 h-200px overflow-hidden position-relative">
                <div
                    class="w-100 h-100 overflow-hidden bg-cover"
                    style="
                        border-radius: 10px 10px 0 0;
                        aspect-ratio: 16/9;
                        background-image: url('<?=resolveImportUrl($location->hero_image)?>');
                    "
                ></div>
                <!-- Store credit clarification -->
                <div class="position-absolute" style="bottom: 10px; left: 10px; right: 10px;">
                    <div class="d-inline-flex flex-row-start-center" style="gap: 6px; background: rgba(255,255,255,0.95); padding: 6px 12px; border-radius: 6px; backdrop-filter: blur(4px);">
                        <i class="mdi mdi-store-outline color-design-blue font-14"></i>
                        <span class="font-12 color-dark">Kredit tilbydes af <strong><?=$location->name?></strong></span>
                    </div>
                </div>
            </div>

            <div class="py-3 px-4 w-100 flex-col-start" style="row-gap: 1rem;">

                <!-- Info Box -->
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

                <!-- Demo Info -->
                <div class="demo-info-box">
                    <i class="mdi mdi-information-outline"></i>
                    <div class="info-content">
                        <p class="info-title">Demo Mode</p>
                        <p class="info-text">
                            I denne demo springer vi MitID-login over.
                            Klik pa knappen nedenfor for at simulere et succesfuldt login.
                        </p>
                    </div>
                </div>

                <!-- MitID Simulation Button -->
                <div class="flex-col-start flex-align-center w-100" style="gap: 1rem;">
                    <button id="demo-mitid-login" class="demo-mitid-btn">
                        <i class="mdi mdi-shield-check-outline"></i>
                        <span>Simuler MitID Login</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>
