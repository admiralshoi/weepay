<?php
/**
 * Demo Consumer Choose Plan Page
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location;
$session = $args->session;
$basket = $args->basket;
$customer = $args->customer;
$paymentPlans = $args->paymentPlans ?? [];
$defaultToPayNow = $args->defaultToPayNow ?? 0;
$pageTitle = "Demo - Vælg betalingsplan";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var paymentPlans = <?=json_encode($paymentPlans)?>;
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

<div class="page-content mt-3">
    <div class="page-inner-content">

        <!-- Stepper -->
        <div class="stepper-progress demo-stepper">
            <div class="stepper-item">
                <div class="stepper-circle"><i class="mdi mdi-check"></i></div>
                <div class="stepper-label">Login</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <div class="stepper-circle"><i class="mdi mdi-check"></i></div>
                <div class="stepper-label">Info</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Vælg og Bekræft</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <div class="stepper-circle">4</div>
                <div class="stepper-label">Betal</div>
            </div>
        </div>

        <div class="flex-col-start flex-align-center mt-4" style="row-gap: 1rem;">

            <!-- Store & Basket Info -->
            <div class="checkout-store-info w-100">
                <div class="checkout-store-info__left">
                    <?php if(!empty($location->logo)): ?>
                        <img src="<?=resolveImportUrl($location->logo)?>" alt="<?=$location->name?>" class="checkout-store-info__logo">
                    <?php else: ?>
                        <div class="checkout-store-info__logo-placeholder">
                            <?=strtoupper(substr($location->name, 0, 2))?>
                        </div>
                    <?php endif; ?>
                    <div class="checkout-store-info__text">
                        <p class="checkout-store-info__name"><?=$location->name?></p>
                        <p class="checkout-store-info__basket"><?=$basket->name?></p>
                    </div>
                </div>
                <p class="checkout-store-info__price"><?=number_format($basket->price, 0, ',', '.')?> kr.</p>
            </div>

            <div class="flex-col-start w-100" style="row-gap: 1rem;">

                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Vælg Betaling</p>

                <div class="payment-cards">
                    <?php foreach ($paymentPlans as $index => $paymentPlan): ?>
                    <label class="payment-card <?=$paymentPlan->default ? 'payment-card--selected' : ''?>" data-to-pay-now="<?=$paymentPlan->to_pay_now?>">
                        <input type="radio" name="payment" class="payment-card__radio" value="<?=$paymentPlan->name?>" <?=$paymentPlan->default ? 'checked' : ''?>>
                        <div class="payment-card__radio-label"></div>

                        <div class="payment-card__content">
                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <div class="flex-row-start flex-align-start flex-nowrap" style="gap: .75rem;">
                                    <div class="payment-card__text">
                                        <h3 class="payment-card__title mb-0"><?=$paymentPlan->title?></h3>
                                        <p class="payment-card__subtitle mb-0"><?=$paymentPlan->caption?></p>
                                    </div>
                                </div>
                                <p class="payment-card__extra"><?=$paymentPlan->subtitle?></p>
                            </div>
                            <div class="payment-card__price"><?=$paymentPlan->price_title?></div>
                        </div>

                        <?php if(!empty($paymentPlan->payments)): ?>
                        <div class="payment-card__details">
                            <p class="payment-card__details-title">Betalingsplan:</p>
                            <ul class="payment-card__details-list">
                                <?php foreach ($paymentPlan->payments as $payment): ?>
                                <li class="payment-card__details-item">
                                    <span>Rate <?=$payment->installment?> (<?=$payment->date_title?>)</span>
                                    <span><?=$payment->price?> kr.</span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="payment-card__details-total">
                                <span>Total</span>
                                <span><?=number_format($basket->price, 2, ',', '.')?> kr.</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Terms Consent -->
                <div class="note-info-box">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                        <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-exclamation-thick"></i></div>
                        <p class="mb-0 info-title">Samtykke</p>
                    </div>
                    <div class="info-content">
                        <div class="flex-row-start flex-align-start flex-nowrap mt-1" style="gap: .5rem">
                            <input type="checkbox" name="accept_terms" class="square-20 mt-1">
                            <span>
                                <span id="consent-bnpl" style="display: none;">
                                    Jeg er indforstået med, at jeg indgår en betalingsaftale direkte med <strong><?=$location->name ?? 'butikken'?></strong>, og ikke <?=BRAND_NAME?>, og jeg bekræfter, at jeg har læst og accepterer
                                </span>
                                <span id="consent-direct">
                                    Jeg bekræfter, at jeg har læst og accepterer
                                </span>
                                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" target="_blank">handelsbetingelserne</a> og
                                <a href="<?=__url(Links::$policies->consumer->privacy)?>" target="_blank">privatlivspolitikken.</a>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pay Button -->
                <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                    <button id="demo-pay-button" class="mt-3 btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" disabled>
                        <i class="mdi mdi-credit-card-outline font-20"></i>
                        <span class="font-18">
                            <span>Betal nu</span>
                            (<span id="demo-to-pay-now"><?=number_format($defaultToPayNow, 2, ',', '.')?></span> kr.)
                        </span>
                        <span id="demo-payment-loader" class="ml-3 flex-align-center flex-row-start" style="display: none;">
                            <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                <span class="sr-only">Loading...</span>
                            </span>
                        </span>
                    </button>

                    <a href="<?=__url(Links::$demo->consumerInfo)?>" class="mt-2 flex-row-center color-gray hover-color-design-blue flex-align-center flex-nowrap" style="gap: .5rem;">
                        <i class="mdi mdi-arrow-left"></i>
                        <span class="font-16">Tilbage</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
