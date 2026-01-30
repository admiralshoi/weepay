<?php
/**
 * Demo Merchant Checkout Page - Awaiting customer payment
 * @var object $args
 */

use classes\enumerations\Links;

$terminal = $args->terminal;
$location = $args->location;
$session = $args->session;
$customer = $args->customer;
$basket = $args->basket;
$pageTitle = "Demo - Afventer betaling";
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
                <div class="stepper-label">Kunde</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <div class="stepper-circle"><i class="mdi mdi-check"></i></div>
                <div class="stepper-label">Kurv</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Betaling</div>
            </div>
        </div>

        <!-- Store Header -->
        <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
            <p class="mb-0 font-20 font-weight-bold"><?=$location->name?></p>
            <span class="demo-status-badge pending">
                <i class="mdi mdi-clock-outline"></i>
                <span id="demo-session-status">Afventer kunde betaling</span>
            </span>
        </div>

        <!-- Order Summary Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0 font-weight-bold">
                    <i class="mdi mdi-cart-outline mr-2"></i>
                    Ordre Detaljer
                </h5>
            </div>
            <div class="card-body">
                <!-- Customer Info -->
                <div class="flex-row-between flex-align-center pb-3 border-bottom-card">
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="customer-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700;">
                            <?=strtoupper(substr($customer->name ?? 'U', 0, 1))?>
                        </div>
                        <div>
                            <p class="mb-0 font-weight-bold"><?=$customer->name ?? 'Ukendt'?></p>
                            <p class="mb-0 font-12 color-gray"><?=$customer->email ?? ''?></p>
                        </div>
                    </div>
                    <span class="design-box font-14">Session <?=$session->session_id?></span>
                </div>

                <!-- Basket Info -->
                <div class="pt-3">
                    <div class="flex-row-between flex-align-start mb-2">
                        <div>
                            <p class="mb-0 font-weight-bold"><?=$basket->name?></p>
                            <?php if(!empty($basket->note)): ?>
                            <p class="mb-0 font-12 color-gray"><?=$basket->note?></p>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 font-20 font-weight-bold color-green">
                            <?=number_format($basket->price, 2, ',', '.')?> kr.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Waiting Animation -->
        <div class="demo-waiting mt-4">
            <div class="waiting-icon">
                <i class="mdi mdi-credit-card-clock-outline"></i>
            </div>
            <h3>Afventer kundens betaling</h3>
            <p>Kunden vælger nu betalingsplan og gennemfører betalingen</p>
        </div>

        <!-- Info Box -->
        <div class="demo-info-box mt-4">
            <i class="mdi mdi-lightbulb-outline"></i>
            <div class="info-content">
                <p class="info-title">Demo Tip</p>
                <p class="info-text">
                    Åbn kunde-demoen i et andet vindue og gennemfør betalingen der.
                    Denne side opdateres automatisk når betalingen er gennemført.
                </p>
            </div>
        </div>

        <!-- Actions -->
        <div class="demo-action-group mt-4">
            <a href="<?=__url(Links::$demo->consumer)?>" target="_blank" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-open-in-new font-18"></i>
                <span>Åbn kunde-visning</span>
            </a>
            <a href="<?=__url(Links::$demo->cashier)?>" class="btn-v2 danger-btn flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-close font-18"></i>
                <span>Annuller ordre</span>
            </a>
        </div>

    </div>
</div>
