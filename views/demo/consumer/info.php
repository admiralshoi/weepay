<?php
/**
 * Demo Consumer Info Page - Waiting for basket
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location;
$session = $args->session;
$basket = $args->basket;
$customer = $args->customer;
$pageTitle = "Demo - Afventer kurv";
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
            <div class="stepper-item">
                <div class="stepper-circle"><i class="mdi mdi-check"></i></div>
                <div class="stepper-label">Login</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item stepper-item--active">
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
        <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
            <p class="mb-0 font-20 font-weight-bold"><?=$location->name?></p>
            <?php if($customer): ?>
            <p class="mb-0 font-14 color-gray">Velkommen, <?=$customer->name ?? 'Kunde'?></p>
            <?php endif; ?>
        </div>

        <!-- Waiting for basket -->
        <div id="demo-waiting-basket" class="demo-waiting mt-4" style="<?=$basket ? 'display: none;' : ''?>">
            <div class="waiting-icon">
                <i class="mdi mdi-cart-outline"></i>
            </div>
            <h3>Afventer kurv fra kasserer...</h3>
            <p>Kassereren er ved at oprette din kurv</p>

            <!-- Demo Tip -->
            <div class="demo-info-box mt-4" style="max-width: 400px;">
                <i class="mdi mdi-lightbulb-outline"></i>
                <div class="info-content">
                    <p class="info-title">Demo Tip</p>
                    <p class="info-text">
                        Åbn kasserer-demoen i et andet vindue og opret en kurv.
                        Denne side opdateres automatisk.
                    </p>
                </div>
            </div>

            <a href="<?=__url(Links::$demo->cashierDetails)?>" target="_blank" class="btn-v2 action-btn mt-3 flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-open-in-new font-18"></i>
                <span>Åbn kasserer-visning</span>
            </a>
        </div>

        <!-- Basket info (shown when basket exists) -->
        <div id="demo-basket-info" class="mt-4" style="<?=$basket ? '' : 'display: none;'?>">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 font-weight-bold">
                        <i class="mdi mdi-cart-outline mr-2"></i>
                        Din kurv
                    </h5>
                </div>
                <div class="card-body">
                    <div class="flex-row-between flex-align-center">
                        <div>
                            <p id="demo-basket-name" class="mb-0 font-18 font-weight-bold"><?=$basket?->name ?? ''?></p>
                            <p class="mb-0 font-12 color-gray"><?=$location->name?></p>
                        </div>
                        <p id="demo-basket-price" class="mb-0 font-25 font-weight-bold color-green">
                            <?=$basket ? number_format($basket->price, 2, ',', '.') . ' kr.' : ''?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="demo-action-group mt-4">
                <a href="<?=__url(Links::$demo->consumerChoosePlan)?>" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-arrow-right font-18"></i>
                    <span>Fortsæt til betalingsvalg</span>
                </a>
            </div>
        </div>

    </div>
</div>
