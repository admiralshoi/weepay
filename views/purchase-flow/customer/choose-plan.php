<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$customer = $args->customer;
$terminalSession = $args->terminalSession;
$terminal = $terminalSession->terminal;
$basket = $args->basket;
$paymentPlans = $args->paymentPlans;
$page = $args->page ?? null;
$logoUrl = $page ? __url($page->logo) : null;

// Calculate BNPL progress percentage
$bnplPercentage = 0;
if (!isEmpty($args->bnplLimit) && $args->bnplLimit->platform_max > 0) {
    $bnplPercentage = ($args->bnplLimit->available / $args->bnplLimit->platform_max) * 100;
}

?>

<script>
    var paymentPlans = <?=json_encode(toArray($paymentPlans))?>;
    var basketHash = '<?=$args->basketHash?>';
</script>



<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="stepper-progress">
            <div class="stepper-item">
                <div class="stepper-circle">1</div>
                <div class="stepper-label">Login</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">2</div>
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

            <?php if(!isEmpty($args->bnplLimit)): ?>
            <!-- Dark Credit Box -->
            <div class="bnpl-credit-card w-100">
                <p class="bnpl-credit-card__label">WEEPAY SALDO</p>
                <p class="bnpl-credit-card__amount"><?=number_format($args->bnplLimit->available, 0, ',', '.')?> kr.</p>
                <div class="bnpl-credit-card__progress-row">
                    <span class="bnpl-credit-card__progress-label">Tilgængelig</span>
                    <span class="bnpl-credit-card__progress-max">Max <?=number_format($args->bnplLimit->platform_max, 0, ',', '.')?> kr.</span>
                </div>
                <div class="bnpl-credit-card__progress-container">
                    <div class="bnpl-credit-card__progress-bar" style="width: <?=$bnplPercentage?>%;"></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Store & Basket Info -->
            <div class="checkout-store-info w-100">
                <div class="checkout-store-info__left">
                    <?php if($logoUrl): ?>
                        <img src="<?=$logoUrl?>" alt="<?=$terminal->location->name?>" class="checkout-store-info__logo">
                    <?php else: ?>
                        <div class="checkout-store-info__logo-placeholder">
                            <?=strtoupper(substr($terminal->location->name, 0, 2))?>
                        </div>
                    <?php endif; ?>
                    <div class="checkout-store-info__text">
                        <p class="checkout-store-info__name"><?=$terminal->location->name?></p>
                        <p class="checkout-store-info__basket"><?=$basket->name?></p>
                    </div>
                </div>
                <p class="checkout-store-info__price"><?=number_format($basket->price, 0, ',', '.')?> kr.</p>
            </div>

            <div class="flex-col-start w-100" style="row-gap: 1rem;">

                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Vælg Betaling</p>

                <div class="payment-cards">
                    <?php foreach ($paymentPlans as $paymentPlan): ?>

                    <label class="payment-card <?=$paymentPlan->default ? 'payment-card--selected' : ''?>">
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

                        <?php if(!isEmpty($paymentPlan->payments)): ?>
                            <div class="payment-card__details">
                                <p class="payment-card__details-title">Betalingsplan:</p>
                                <ul class="payment-card__details-list">
                                    <?php foreach ($paymentPlan->payments as $payment): ?>
                                    <li class="payment-card__details-item">
                                        <span>Rate <?=$payment->installment?> (<?=$payment->date_title?>)</span><span><?=$payment->price . currencySymbol($basket->currency)?></span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="payment-card__details-total">
                                    <span>Total</span><span><?=$basket->price . currencySymbol($basket->currency)?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="note-info-box">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                        <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-exclamation-thick"></i></div>
                        <p class="mb-0 info-title">Samtykke</p>
                    </div>
                    <div class="info-content">
                        <div class="flex-row-start flex-align-center flex-nowrap mt-1" style="gap: .5rem">
                            <input type="checkbox" name="accept_terms" class="square-20">
                            <span>
                                Jeg bekræfter at jeg har læst og accepterer
                                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" target="_blank">handelsbetingelserne</a> og
                                <a href="<?=__url(Links::$policies->consumer->privacy)?>" target="_blank">privatlivspolitikken.</a>
                            </span>
                        </div>
                    </div>
                </div>


                <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
                    <button id="payButton" class="mt-3  btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">
                        <i class="mdi mdi-credit-card-outline font-20"></i>
                        <span class="font-18">
                            Bekræft og Betal
                            (<span id="to-pay-now"><?=number_format($args->defaultToPayNow, 2)?></span>
                            <?=currencySymbol($basket->currency)?>)
                        </span>

                        <span class="ml-3 flex-align-center flex-row-start" style="display: none;" id="paymentButtonLoader">
                            <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                              <span class="sr-only">Loading...</span>
                            </span>
                        </span>
                    </button>
                    <a href="<?=$args->previousStepLink?>" class="mt-2 flex-row-center color-gray hover-color-design-blue flex-align-center flex-nowrap" style="gap: .5rem;">
                        <i class="mdi mdi-arrow-left"></i>
                        <span class="font-16">Tilbage</span>
                    </a>

                </div>
            </div>



        </div>
    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        CustomerCheckout.init(
            <?=json_encode($args->defaultPlanId)?>,
            <?=json_encode($terminalSession->uid)?>
        );
    })
</script>
<?php scriptEnd(); ?>



