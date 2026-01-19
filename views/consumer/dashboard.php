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

    <!-- BNPL Credit Widget -->
    <div class="mt-4">
        <?=\features\DomMethods::bnplCreditCard($args->bnplLimit ?? [], $args->pastDueCount > 0)?>
    </div>

    <?php

    //prettyPrint(\classes\Methods::paymentMethods()->backfillAllPayments());
    ?>

    <?php if($args->pastDueCount > 0): ?>
    <!-- Past Due Alert -->
    <div class="mt-4">
        <div class="alert alert-danger border-radius-10px" role="alert">
            <div class="flex-row-between flex-align-center flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 1rem;">
                    <i class="mdi mdi-alert-circle font-28"></i>
                    <div class="flex-col-start">
                        <p class="mb-0 font-16 font-weight-bold">
                            <?=$args->pastDueCount?> <?=$args->pastDueCount === 1 ? 'forfalden betaling' : 'forfaldne betalinger'?>
                        </p>
                        <p class="mb-0 font-14">
                            Du skylder i alt <strong><?=number_format($args->pastDueTotal, 2)?> kr.</strong> som er overskredet forfaldsdato
                        </p>
                    </div>
                </div>
                <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 danger-btn flex-row-center flex-align-center" style="gap: .5rem;">
                    <span>Betal nu</span>
                    <i class="mdi mdi-arrow-right font-16"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row flex-align-stretch rg-15 mt-4">
        <!-- Upcoming Payments -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Kommende betalinger</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->totalUpcoming, 2) . currencySymbol("DKK")?></p>
                            <?php if($args->totalUpcoming > 0): ?>
                                <a href="<?=__url(Links::$consumer->payments)?>" class="color-blue font-12">Se detaljer</a>
                            <?php else: ?>
                                <p class="color-gray font-12">Ingen kommende</p>
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
                    <p class="font-12 color-gray mt-2 mb-0"><i class="mdi mdi-information-outline"></i> Hver butik kan have forskellige maksimumbeløb</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 1: Activity Feed + Upcoming Payments -->
    <div class="row mt-4">
        <!-- Activity Feed -->
        <div class="col-12 col-lg-6">
            <div class="card border-radius-10px h-100">
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
                            <?php foreach($args->activities as $activity):
                                $activity = (object)$activity;
                                $detailUrl = $activity->type === 'order'
                                    ? __url(Links::$consumer->orderDetail($activity->uid))
                                    : __url(Links::$consumer->paymentDetail($activity->uid));
                            ?>
                                <a href="<?=$detailUrl?>" class="flex-row-between flex-align-center flex-wrap border-bottom-card pb-3 hover-bg-light" style="column-gap: 1rem; row-gap: .5rem; text-decoration: none; color: inherit; margin: -0.5rem; padding: 0.5rem; border-radius: 8px;">
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 1rem;">
                                        <?php if($activity->type === 'order'): ?>
                                            <div class="square-40 bg-lighter-blue border-radius-10px flex-row-center-center">
                                                <i class="mdi mdi-cart-outline color-blue font-20"></i>
                                            </div>
                                            <div class="flex-col-start">
                                                <p class="mb-0 font-14 font-weight-medium">Ny ordre</p>
                                                <p class="mb-0 font-12 color-gray"><?=htmlspecialchars($activity->location_name)?></p>
                                                <p class="mb-0 font-11 color-gray"><?=date('d/m/Y H:i', strtotime($activity->date))?></p>
                                            </div>
                                        <?php else: ?>
                                            <div class="square-40 bg-lighter-green border-radius-10px flex-row-center-center">
                                                <i class="mdi mdi-check-circle-outline color-green font-20"></i>
                                            </div>
                                            <div class="flex-col-start">
                                                <p class="mb-0 font-14 font-weight-medium">Betaling gennemført</p>
                                                <p class="mb-0 font-12 color-gray"><?=htmlspecialchars($activity->location_name)?></p>
                                                <p class="mb-0 font-11 color-gray"><?=date('d/m/Y H:i', strtotime($activity->date))?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($activity->amount, 2)?> <?=currencySymbol($activity->currency)?></p>
                                        <i class="mdi mdi-chevron-right font-20 color-gray"></i>
                                    </div>
                                </a>
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

        <!-- Upcoming Payments Card -->
        <div class="col-12 col-lg-6 mt-4 mt-lg-0">
            <div class="card border-radius-10px h-100">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-calendar-clock font-18 color-blue"></i>
                            <p class="mb-0 font-22 font-weight-bold">Kommende betalinger</p>
                        </div>
                    </div>

                    <?php if(empty($args->upcomingPayments)): ?>
                        <div class="flex-col-center py-5">
                            <i class="mdi mdi-calendar-check font-48 color-green mb-2"></i>
                            <p class="mb-0 font-16 font-weight-medium color-gray">Ingen kommende betalinger</p>
                            <p class="mb-0 font-13 color-gray mt-1">Du er helt ajour!</p>
                        </div>
                    <?php else: ?>
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <?php foreach($args->upcomingPayments as $payment): $payment = (object)$payment; ?>
                                <a href="<?=__url(Links::$consumer->paymentDetail($payment->uid))?>" class="flex-row-between flex-align-center flex-wrap border-bottom-card pb-3 hover-bg-light" style="column-gap: 1rem; row-gap: .5rem; text-decoration: none; color: inherit; margin: -0.5rem; padding: 0.5rem; border-radius: 8px;">
                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 1rem;">
                                        <div class="square-40 bg-warning border-radius-10px flex-row-center-center">
                                            <i class="mdi mdi-credit-card-clock-outline color-acoustic-yellow font-20"></i>
                                        </div>
                                        <div class="flex-col-start">
                                            <p class="mb-0 font-14 font-weight-medium">
                                                <?php if($payment->installment_number && $payment->total_installments): ?>
                                                    Rate <?=$payment->installment_number?>/<?=$payment->total_installments?>
                                                <?php else: ?>
                                                    Betaling
                                                <?php endif; ?>
                                            </p>
                                            <p class="mb-0 font-12 color-gray"><?=htmlspecialchars($payment->location_name)?></p>
                                            <p class="mb-0 font-11 color-gray">Forfald: <?=date('d/m/Y', strtotime($payment->due_date))?></p>
                                        </div>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($payment->amount, 2)?> <?=currencySymbol($payment->currency)?></p>
                                        <i class="mdi mdi-chevron-right font-20 color-gray"></i>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <?php if(count(toArray($args->upcomingPayments)) >= 3): ?>
                            <div class="flex-row-center mt-3">
                                <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 action-btn">Se alle betalinger</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Orders Chart + Locations -->
    <?php $locationPurchasesArray = toArray($args->locationPurchases ?? []); ?>
    <?php if(!empty($locationPurchasesArray)): ?>
    <div class="row mt-4">
        <!-- Orders by Location Chart -->
        <?php if(count($locationPurchasesArray) > 1): ?>
        <div class="col-12 col-lg-6">
            <div class="card border-radius-10px h-100">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-chart-pie font-18 color-blue"></i>
                            <p class="mb-0 font-22 font-weight-bold">Ordrer pr. butik</p>
                        </div>
                    </div>

                    <div id="locationPurchasesChart" style="max-height: 250px;"></div>

                    <div class="flex-col-start mt-3" style="row-gap: .5rem;">
                        <?php
                        $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];
                        $totalOrders = array_sum(array_map(fn($l) => $l['order_count'], $locationPurchasesArray));
                        foreach($locationPurchasesArray as $i => $loc):
                            $loc = (object)$loc;
                            $color = $colors[$i % count($colors)];
                            $percentage = $totalOrders > 0 ? round(($loc->order_count / $totalOrders) * 100, 1) : 0;
                        ?>
                        <div class="flex-row-between flex-align-center">
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <div style="width: 12px; height: 12px; border-radius: 3px; background-color: <?=$color?>;"></div>
                                <p class="mb-0 font-14"><?=htmlspecialchars($loc->name)?></p>
                            </div>
                            <p class="mb-0 font-14 font-weight-medium"><?=$percentage?>%</p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Locations Card -->
        <div class="col-12 col-lg-6 <?=count($locationPurchasesArray) > 1 ? 'mt-4 mt-lg-0' : ''?>">
            <div class="card border-radius-10px h-100">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-store font-18 color-blue"></i>
                            <p class="mb-0 font-22 font-weight-bold">Dine butikker</p>
                        </div>
                    </div>

                    <div class="flex-col-start" style="row-gap: 1rem;">
                        <?php foreach($args->locationPurchases as $loc): $loc = (object)$loc; ?>
                            <a href="<?=__url(Links::$consumer->locationDetail($loc->uid))?>" class="flex-row-between flex-align-center flex-wrap border-bottom-card pb-3 hover-bg-light" style="column-gap: 1rem; row-gap: .5rem; text-decoration: none; color: inherit; margin: -0.5rem; padding: 0.5rem; border-radius: 8px;">
                                <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 1rem;">
                                    <div class="square-40 bg-lighter-blue border-radius-10px flex-row-center-center">
                                        <i class="mdi mdi-store color-blue font-20"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($loc->name)?></p>
                                        <p class="mb-0 font-12 color-gray"><?=$loc->order_count?> <?=$loc->order_count === 1 ? 'ordre' : 'ordrer'?></p>
                                    </div>
                                </div>
                                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                    <p class="mb-0 font-16 font-weight-bold"><?=number_format($loc->total_spent, 2)?> kr.</p>
                                    <i class="mdi mdi-chevron-right font-20 color-gray"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(count($locationPurchasesArray) > 1): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if(typeof ApexCharts !== 'undefined') {
                var options = {
                    series: <?=json_encode(array_map(fn($l) => $l['order_count'], $locationPurchasesArray))?>,
                    chart: {
                        type: 'donut',
                        height: 250
                    },
                    labels: <?=json_encode(array_map(fn($l) => $l['name'], $locationPurchasesArray))?>,
                    colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'],
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    tooltip: {
                        y: {
                            formatter: function(value) {
                                return value + (value === 1 ? ' ordre' : ' ordrer');
                            }
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '65%'
                            }
                        }
                    }
                };

                var chart = new ApexCharts(document.querySelector("#locationPurchasesChart"), options);
                chart.render();
            }
        });
    </script>
    <?php endif; ?>
    <?php endif; ?>

</div>
