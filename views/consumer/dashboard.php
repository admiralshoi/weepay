<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Oversigt";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
</script>

<div class="page-content home">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Oversigt</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Velkommen til dit WeePay dashboard</p>
    </div>

    <!-- KPI Cards -->
    <div class="row flex-align-stretch rg-15 mt-4">
        <!-- Total Outstanding -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Udestående betalinger</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->totalOutstanding, 2) . currencySymbol("DKK")?></p>
                            <?php if($args->totalOutstanding > 0): ?>
                                <a href="<?=__url(Links::$consumer->outstandingPayments)?>" class="color-blue font-12">Se detaljer</a>
                            <?php else: ?>
                                <p class="color-gray font-12">Ingen udestående</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-warning border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-credit-card-clock-outline color-acoustic-yellow font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Spent -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Total brugt</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->totalSpent, 2) . currencySymbol("DKK")?></p>
                            <p class="color-gray font-12">Alle køb</p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-currency-usd color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Count -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Antal ordrer</p>
                            <p class="font-22 font-weight-700"><?=$args->orderCount?></p>
                            <a href="<?=__url(Links::$consumer->orders)?>" class="color-blue font-12">Se ordrer</a>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-cart-outline color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Credit -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Tilgængelig kredit</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->availableCredit, 2) . currencySymbol("DKK")?></p>
                            <p class="color-gray font-12">Til BNPL køb</p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-success border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-wallet-outline color-green font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Feed -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-timeline-clock font-18 color-blue"></i>
                            <p class="mb-0 font-22 font-weight-bold">Seneste aktivitet</p>
                        </div>
                    </div>

                    <?php if(empty($args->activities)): ?>
                        <div class="flex-col-center py-5">
                            <i class="mdi mdi-information-outline font-48 color-gray mb-2"></i>
                            <p class="mb-0 font-16 color-gray">Ingen aktivitet endnu</p>
                        </div>
                    <?php else: ?>
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <?php foreach($args->activities as $activity): ?>
                                <div class="flex-row-between flex-align-center flex-wrap border-bottom-card pb-3" style="column-gap: 1rem; row-gap: .5rem;">
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 1rem;">
                                        <?php if($activity->type === 'order'): ?>
                                            <div class="square-40 bg-lighter-blue border-radius-10px flex-row-center-center">
                                                <i class="mdi mdi-cart-outline color-blue font-20"></i>
                                            </div>
                                            <div class="flex-col-start">
                                                <p class="mb-0 font-14 font-weight-medium">Ny ordre</p>
                                                <p class="mb-0 font-12 color-gray"><?=date('d/m/Y H:i', strtotime($activity->date))?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="square-40 bg-lighter-green border-radius-10px flex-row-center-center">
                                                <i class="mdi mdi-check-circle-outline color-green font-20"></i>
                                            </div>
                                            <div class="flex-col-start">
                                                <p class="mb-0 font-14 font-weight-medium">Betaling gennemført</p>
                                                <p class="mb-0 font-12 color-gray"><?=date('d/m/Y H:i', strtotime($activity->date))?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-row-end">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($activity->amount, 2)?> <?=currencySymbol($activity->currency)?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if(count(toArray($args->activities)) >= 3): ?>
                            <div class="flex-row-center mt-3">
                                <a href="<?=__url(Links::$consumer->orders)?>" class="btn-v2 action-btn">Se alle ordrer</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
