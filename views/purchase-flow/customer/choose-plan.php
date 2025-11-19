<?php
/**
 * @var object $args
 */

$customer = $args->customer;
$terminalSession = $args->terminalSession;
$terminal = $terminalSession->terminal;
$basket = $args->basket;
$paymentPlans = $args->paymentPlans;



?>

<script>
    var paymentPlans = <?=json_encode(toArray($paymentPlans))?>;
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

        <div class="flex-col-start flex-align-center mt-5" style="row-gap: .75rem;">

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="mb-0 font-25 font-weight-bold">Vælg Betalingsplan</p>
                <p class="mb-0 font-14 font-weight-medium color-gray">Vælg den løsning der passer dig bedst</p>
            </div>


            <div class="flex-col-start w-100" style="row-gap: 1rem;">

                <div class="payment-cards">
                    <?php foreach ($paymentPlans as $paymentPlan): ?>

                    <label class="payment-card <?=$paymentPlan->default ? 'payment-card--selected' : ''?>">
                        <input type="radio" name="payment" class="payment-card__radio" value="<?=$paymentPlan->name?>" <?=$paymentPlan->default ? 'checked' : ''?>>
                        <div class="payment-card__radio-label"></div>

                        <div class="payment-card__content">
                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <div class="flex-row-start flex-align-start flex-nowrap" style="gap: .75rem;">
                                    <div class="payment-card__icon">
                                        <?php
                                        if($paymentPlan->name === 'direct') $iconClass = "mdi mdi-credit-card-outline color-design-blue";
                                        elseif($paymentPlan->name === 'pushed') $iconClass = "mdi mdi-calendar-outline color-design-blue";
                                        else $iconClass = "mdi mdi-trending-up color-design-blue";
                                        ?>
                                        <i class="<?=$iconClass?>"></i>
                                    </div>
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


                <div class="action-mute-info-box">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                        <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-cart-outline"></i></div>
                        <p class="mb-0 info-title">Ordresammendrag</p>
                    </div>
                    <div class="info-content">
                        <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem">
                            <p class="mb-0 font-15"><?=$basket->name?></p>
                            <p class="mb-0 font-15 font-weight-bold"><?=number_format($basket->price, 2) . currencySymbol($basket->currency)?></p>
                        </div>
                    </div>
                </div>

                <div class="note-info-box">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                        <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-exclamation-thick"></i></div>
                        <p class="mb-0 info-title">Sammentykke</p>
                    </div>
                    <div class="info-content">
                        <div class="flex-row-start flex-align-center flex-nowrap mt-1" style="gap: .5rem">
                            <input type="checkbox" name="accept_terms" class="square-20">
                            Ved at bekræfte accepterer du handelsbetingelserne og privatlivspolitikken
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



