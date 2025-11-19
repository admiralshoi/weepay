<?php
/**
 * @var object $args
 */

$customer = $args->customer;
$terminalSession = $args->terminalSession;
$terminal = $terminalSession->terminal;
$basket = $args->basket;



?>




<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="stepper-progress">
            <div class="stepper-item">
                <div class="stepper-circle">1</div>
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

        <div class="flex-col-start flex-align-center mt-5" style="row-gap: .75rem;">
            <p class="design-box mb-0 px-2">
                <i class="mdi mdi-store"></i>
                <span class="font-weight-bold">Du Handler hos</span>
            </p>

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="mb-0 font-25 font-weight-bold"><?=$terminal->location->name?></p>
                <p class="mb-0 font-14 font-weight-medium color-gray"><?=$terminal->location->caption?></p>
            </div>


            <div class="card border-radius-10px w-100">
                <div class="w-100 h-200px overflow-hidden">
                    <div
                        class="w-100 h-100 overflow-hidden bg-cover"
                        style="
                            border-radius: 10px 10px 0 0;
                            aspect-ratio: 16/9;
                            background-image: url('<?=resolveImportUrl($terminal->location->hero_image)?>');
                            "
                    ></div>
                    <div style="position: absolute; top: 5px; right: 8px;">
                        <p class="design-box mb-0 font-18 font-weight-bold px-3  py-2">
                            ID: <?=$terminalSession->session?>
                        </p>
                    </div>
                </div>

                <div class="py-3 px-4 w-100 flex-col-start" style="row-gap: .5rem;">
                    <div class="flex-col-start border-bottom-card pb-3" style="row-gap: .5rem;">
                        <?php if(!empty($terminal->location->contact_email)): ?>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                <i class="mdi mdi-email-outline color-design-blue font-16"></i>
                                <p class="mb-0 font-14"><?=$terminal->location->contact_email?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="line_items" class="flex-col-start border-bottom-card pb-3" style="row-gap: .5rem; <?=isEmpty($basket) ? 'display: none;' : ''?>">
                        <p class="mb-2 font-16 font-weight-bold">Ordredetaljer</p>
                        <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem">
                            <p class="mb-0 font-15" data-show="basket_name"></p>
                            <p class="mb-0 font-15 font-weight-bold">
                                <span data-show="basket_price"></span>
                                <span data-show="basket_currency"></span>
                            </p>
                        </div>
                    </div>
                    <div id="total_price_container" class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem; <?=isEmpty($basket) ? 'display: none;' : ''?>">
                        <p class="mb-0 font-16 font-weight-bold">I alt</p>
                        <p class="mb-0 font-22 color-design-blue font-weight-bold">
                            <span data-show="basket_price"></span>
                            <span data-show="basket_currency"></span>
                        </p>
                    </div>


                    <div class="flex-col-start pt-3" style="row-gap: 1.5rem;">
                        <div class="vision-card px-4 py-3 border-radius-10px w-100 " style="gap: .5rem;">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 1.5rem">
                                <div class="flex-row-center flex-align-center square-75 bg-wrapper-hover border-radius-50 " >
                                    <i class="font-40 color-design-blue mdi mdi-account"></i>
                                </div>
                                <div class="flex-col-start mt-2" style="row-gap: .25rem;">
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                        <p class="mb-0 font-14">Navn</p>
                                        <p class="mb-0 font-14 font-weight-bold"><?=$customer->name?></p>
                                    </div>
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                        <p class="mb-0 font-14">Fødselsdag</p>
                                        <p class="mb-0 font-14 font-weight-bold"><?=$customer->birthdate?></p>
                                    </div>
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                        <p class="mb-0 font-14 ">CPR</p>
                                        <p class="mb-0 font-14 font-weight-bold"><?=$customer->nin_id?></p>
                                    </div>
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                        <p class="mb-0 font-14">Nationalitet</p>
                                        <p class="mb-0 font-14 font-weight-bold"><?=\classes\Methods::countries()->name($customer->nin_country)?></p>
                                    </div>
                                </div>
                            </div>



                            <a href="<?=$args->nextStepLink?>" id="next-step" style="gap: .55rem; <?=isEmpty($basket) ? 'display: none;' : ''?>"
                               class="mt-3  btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap" >
                                <i class="mdi mdi-contactless-payment font-18"></i>
                                <span class="font-18">Vælg Betalingsplan</span>
                            </a>

                            <div class="flex-row-center flex-align-center flex-nowrap mt-3" id="loader-container" style="gap: 1rem; <?=!isEmpty($basket) ? 'display: none;' : ''?>">
                                <div class="ml-3 flex-align-center flex-row-start"  id="paymentButtonLoader">
                                    <span class="spinner-border color-dark square-30" role="status" style="border-width: 3px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </div>

                                <p class="mb-0 font-18 font-weight-medium">Waiting for cashier...</p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>



        </div>
    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        CustomerCheckoutInfo.init(
            <?=json_encode($terminalSession->uid)?>
        );
    })
</script>
<?php scriptEnd(); ?>

