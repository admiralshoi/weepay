<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$session = $args->session;
$terminal = $args->terminal;
$customer = $args->customer;
$basket = $args->basket;
$order = $args->order;
$pendingValidationRefund = $args->pendingValidationRefund ?? null;

$pageTitle = "Ordre gennemført - {$terminal->location->name}";
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">


        <div class="flex-row-center-center mx-auto w-100 mxw-700px mt-3">
            <div class="flex-col-start rg-15 w-100">

                <!-- Success Header -->
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-row-between-center g-1 mb-3">
                            <p class="design-box font-16 py-1 px-2">Terminal: <?=$terminal->name?></p>
                        </div>

                        <div class="flex-col-start flex-align-center" style="row-gap: 1.5rem;">
                            <div class="square-100 border-radius-50 flex-row-center-center bg-success-light">
                                <i class="mdi mdi-check-circle font-50 color-success-text"></i>
                            </div>
                            <div class="flex-col-start flex-align-center" style="row-gap: 0.5rem;">
                                <p class="font-weight-bold font-25">Købet blev gennemført!</p>
                                <p class="font-weight-medium color-gray font-16">Betalingen er gennemført og ordren er bekræftet</p>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(!isEmpty($pendingValidationRefund)): ?>
                <!-- Pending Validation Refund Warning (Cashier Only) -->
                <div class="card border-radius-10px w-100 border-danger" style="border-width: 2px;">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: 0.75rem;">
                                <div class="square-40 border-radius-50 flex-row-center-center bg-danger flex-shrink-0">
                                    <i class="mdi mdi-alert-circle font-20 color-white"></i>
                                </div>
                                <p class="font-weight-bold font-18 mb-0 color-danger">Refundering af kortvalidering mislykkedes</p>
                            </div>
                            <p class="font-14 color-gray mb-3">
                                Ved kortvalidering blev der trukket <strong><?=number_format($pendingValidationRefund->amount, 2)?> <?=currencySymbol($pendingValidationRefund->currency)?></strong>,
                                men den automatiske refundering fejlede fordi refunderingsfunktionen ikke er aktiveret på Viva-kontoen.
                            </p>
                            <div class="bg-danger-light border-radius-8px p-3">
                                <p class="font-14 color-dark mb-0">
                                    <i class="mdi mdi-information-outline mr-1"></i>
                                    <strong>Handling påkrævet:</strong> Bed din leder om at aktivere refunderinger på Viva Dashboard,
                                    og herefter manuelt refundere <?=number_format($pendingValidationRefund->amount, 2)?> <?=currencySymbol($pendingValidationRefund->currency)?> til kunden.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Customer Information -->
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start rg-075">
                            <p class="font-weight-bold font-18 mb-2">Kundeoplysninger</p>
                            <div class="row bg-lighter-blue border-radius-10px p-3">
                                <div class="col-12 col-md-6 mb-3 mb-md-0">
                                    <div class="flex-col-start">
                                        <p class="color-gray font-14 font-weight-medium">Navn</p>
                                        <p class="font-16 font-weight-bold">
                                            <?=$customer->full_name ?? 'N/A'?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start">
                                        <p class="color-gray font-14 font-weight-medium">E-mail</p>
                                        <p class="font-16 font-weight-bold">
                                            <?=$customer->email ?? 'N/A'?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basket Information -->
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start rg-075">
                            <p class="font-weight-bold font-18 mb-2">Købsdetaljer</p>

                            <div class="bg-lighter-blue border-radius-10px p-3 mb-2">
                                <div class="flex-col-start">
                                    <p class="color-gray font-14 font-weight-medium">Beskrivelse</p>
                                    <p class="font-16 font-weight-bold">
                                        <?=$basket->name?>
                                    </p>
                                </div>
                            </div>

                            <?php if(!isEmpty($basket->note)): ?>
                            <div class="bg-lighter-blue border-radius-10px p-3 mb-2">
                                <div class="flex-col-start">
                                    <p class="color-gray font-14 font-weight-medium">Noter</p>
                                    <p class="font-14 font-weight-medium">
                                        <?=$basket->note?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="row bg-lighter-blue border-radius-10px p-3">
                                <div class="col-12 col-md-6 mb-3 mb-md-0">
                                    <div class="flex-col-start">
                                        <p class="color-gray font-14 font-weight-medium">Valuta</p>
                                        <p class="font-16 font-weight-bold">
                                            <?=$basket->currency?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="flex-col-start">
                                        <p class="color-gray font-14 font-weight-medium">Status</p>
                                        <p class="font-16 font-weight-bold color-success-text">
                                            Gennemført
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Totals -->
                <?php if(!isEmpty($order)): ?>
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start rg-075">
                            <p class="font-weight-bold font-18 mb-2">Ordre totaler</p>

                            <div class="flex-row-between-center p-3 bg-lighter-blue border-radius-10px mb-2">
                                <p class="font-16 font-weight-medium">Ordre ID</p>
                                <p class="font-16 font-weight-bold"><?=$order->prid?></p>
                            </div>

                            <div class="flex-row-between-center p-3 bg-lighter-blue border-radius-10px mb-2">
                                <p class="font-16 font-weight-medium">Betalingsplan</p>
                                <p class="font-16 font-weight-bold">
                                    <?php
                                    $planLabels = [
                                        'installments' => 'Rater',
                                        'pushed' => 'Udskudt',
                                        'full' => 'Fuld betaling',
                                    ];
                                    echo $planLabels[$order->payment_plan] ?? $order->payment_plan;
                                    ?>
                                </p>
                            </div>

                            <div class="flex-row-between-center p-3 bg-success-light border-radius-10px">
                                <p class="font-18 font-weight-bold">Total beløb</p>
                                <p class="font-22 font-weight-bold color-success-text">
                                    <?=number_format($basket->price, 2)?> <?=currencySymbol($basket->currency)?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Button -->
                <div class="flex-row-center-center flex-1 mt-2">
                    <a href="<?=__url(Links::$merchant->terminals->posStart($terminal->location->slug, $terminal->uid))?>"
                       class="btn-v2 h-45px font-weight-bold font-16 action-btn flex-row-center-center cg-1 flex-nowrap">
                        <i class="mdi mdi-plus-circle"></i>
                        <span>Ny transaktion</span>
                    </a>
                </div>
            </div>
        </div>



    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        // Optional: Auto-redirect after a delay
        // setTimeout(function() {
        //     window.location.href = '<?=__url(Links::$merchant->terminals->posStart($terminal->location->slug, $terminal->uid))?>';
        // }, 5000);
    })
</script>
<?php scriptEnd(); ?>
