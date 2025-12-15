<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forhandler Dashboard";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-wrap" id="nav" style="column-gap: .5rem; row-gap: .75rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>

        <div class="flex-row-end flex-align-center flex-wrap" style="column-gap: .5rem; row-gap: .5rem;">
            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem; row-gap: .5rem;">
                <input type="date" id="start-date" class="form-control" style="max-width: 160px;"
                       value="<?=$args->startDate ?? ''?>" placeholder="Start dato">
                <input type="date" id="end-date" class="form-control" style="max-width: 160px;"
                       value="<?=$args->endDate ?? ''?>" placeholder="Slut dato">
            </div>
            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem; row-gap: .5rem;">
                <button onclick="applyDateFilter()" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-filter"></i>
                    <span>Filtrer</span>
                </button>
                <?php if(!isEmpty($args->startDate) || !isEmpty($args->endDate)): ?>
                    <button onclick="clearDateFilter()" class="btn-v2 mute-btn flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-close"></i>
                        <span>Ryd</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($args->setupRequirements->has_incomplete): ?>
    <!-- Setup Requirements Notice -->
    <div class="danger-info-box px-4 py-3 mb-4">
        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .75rem">
            <div class="square-40 flex-row-center flex-align-center bg-danger-bread border-radius-50">
                <i class="font-20 mdi mdi-alert-outline color-white"></i>
            </div>
            <div class="flex-col-start flex-1">
                <p class="mb-2 font-18 font-weight-bold color-dark">Handlinger påkrævet</p>
                <p class="mb-2 font-14 color-gray">For at kunne modtage betalinger skal du færdiggøre følgende opsætning:</p>

                <div class="flex-col-start mt-2" style="row-gap: .75rem;">
                    <!-- Viva Wallet -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->viva_wallet->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Viva Wallet tilsluttet</span>
                        <?php elseif($args->setupRequirements->viva_wallet->status === 'in_progress'): ?>
                            <i class="mdi mdi-clock-outline color-warning font-18"></i>
                            <span class="font-14 font-weight-medium">Viva Wallet afventer godkendelse</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->organisation->home)?>" class="font-14 font-weight-medium color-design-blue">Tilslut Viva Wallet</a>
                        <?php endif; ?>
                    </div>

                    <!-- Location -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->locations->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Lokation oprettet</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->locations->main)?>" class="font-14 font-weight-medium color-design-blue">Opret lokation</a>
                        <?php endif; ?>
                    </div>

                    <!-- Terminal -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->terminals->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Terminal oprettet</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->terminals->main)?>" class="font-14 font-weight-medium color-design-blue">Opret terminal</a>
                        <?php endif; ?>
                    </div>

                    <!-- Published Page -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->published_page->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Lokationsside publiceret</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->locations->main)?>" class="font-14 font-weight-medium color-design-blue">Publicer lokationsside</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Oversigt</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Velkommen til dit WeePay forhandler dashboard</p>
    </div>




    <div class="row flex-align-stretch rg-15 mt-4">
        <!-- Gross Revenue -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Total omsætning</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->grossRevenue, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = $args->revenueChange > 0 ? 'color-green' : ($args->revenueChange < 0 ? 'color-danger' : 'color-gray'); ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->revenueChange > 0 ? '+' : ''?>
                                <?=round($args->revenueChange, 2)?>%
                            </p>
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

        <!-- Net Revenue -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Nettoomsætning</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->netRevenue, 2) . currencySymbol("DKK")?></p>
                            <p class="color-gray font-12">Efter gebyrer</p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-success border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-chart-line color-green font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Outstanding -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Udestående</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->totalOutstanding, 2) . currencySymbol("DKK")?></p>
                            <?php if($args->totalPastDue > 0): ?>
                                <p class="color-danger font-12"><?=number_format($args->totalPastDue, 2)?> DKK forsinket</p>
                            <?php else: ?>
                                <p class="color-gray font-12">Ingen forsinkede</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-warning border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-clock-outline color-acoustic-yellow font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Paid -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Gennemført betalinger</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->totalPaid, 2) . currencySymbol("DKK")?></p>
                            <p class="color-gray font-12">Alle transaktioner</p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-success border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-check-circle-outline color-green font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Kunder</p>
                            <p class="font-22 font-weight-700"><?=$args->customerCount?></p>
                            <?php $colorClass = $args->customerCountChange > 0 ? 'color-green' : ($args->customerCountChange < 0 ? 'color-danger' : 'color-gray'); ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->customerCountChange > 0 ? '+' : ''?>
                                <?=round($args->customerCountChange, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-account-heart-outline color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Count -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Antal ordrer</p>
                            <p class="font-22 font-weight-700"><?=$args->orderCount?></p>
                            <?php $colorClass = $args->orderCountChange > 0 ? 'color-green' : ($args->orderCountChange < 0 ? 'color-danger' : 'color-gray'); ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->orderCountChange > 0 ? '+' : ''?>
                                <?=round($args->orderCountChange, 2)?>%
                            </p>
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

        <!-- Average Order -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Gennemsnitlig ordre</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->orderAverage, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = $args->averageChange > 0 ? 'color-green' : ($args->averageChange < 0 ? 'color-danger' : 'color-gray'); ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->averageChange > 0 ? '+' : ''?>
                                <?=round($args->averageChange, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-trending-up color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BNPL Usage Rate -->
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">BNPL brug</p>
                            <p class="font-22 font-weight-700"><?=round($args->bnplUsageRate, 1)?>%</p>
                            <p class="color-gray font-12">Af alle ordrer</p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-lighter-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-calendar-clock color-blue font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <i class="mdi mdi-chart-areaspline font-18 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Omsætning over tid</p>
                    </div>
                    <div id="revenueChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php scriptStart(); ?>
<script>
    console.log('dadmsjdhsaih')
    function applyDateFilter() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;

        const url = new URL(window.location.href);

        if (startDate) {
            url.searchParams.set('start', startDate);
        } else {
            url.searchParams.delete('start');
        }

        if (endDate) {
            url.searchParams.set('end', endDate);
        } else {
            url.searchParams.delete('end');
        }

        window.location.href = url.toString();
    }

    function clearDateFilter() {
        const url = new URL(window.location.href);
        url.searchParams.delete('start');
        url.searchParams.delete('end');
        window.location.href = url.toString();
    }


    // ApexCharts implementation
    const chartData = <?=json_encode(array_values(toArray($args->chartData)))?>;

    var options = {
        series: [{
            name: 'Omsætning (DKK)',
            type: 'area',
            data: chartData.map(d => d.revenue)
        }, {
            name: 'Antal ordrer',
            type: 'line',
            data: chartData.map(d => d.orders)
        }],
        chart: {
            height: 400,
            type: 'line',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: [0, 3]
        },
        fill: {
            type: ['gradient', 'solid'],
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100]
            }
        },
        colors: ['#4BC0C0', '#9966FF'],
        labels: chartData.map(d => d.date),
        xaxis: {
            type: 'category'
        },
        yaxis: [{
            title: {
                text: 'Omsætning (DKK)'
            },
            labels: {
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', {
                        style: 'currency',
                        currency: 'DKK',
                        minimumFractionDigits: 0
                    }).format(value);
                }
            }
        }, {
            opposite: true,
            title: {
                text: 'Antal ordrer'
            },
            labels: {
                formatter: function (value) {
                    return Math.round(value);
                }
            }
        }],
        tooltip: {
            shared: true,
            intersect: false,
            y: [{
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', {
                        style: 'currency',
                        currency: 'DKK'
                    }).format(value);
                }
            }, {
                formatter: function (value) {
                    return Math.round(value) + ' ordrer';
                }
            }]
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        }
    };

    var chart = new ApexCharts(document.querySelector("#revenueChart"), options);
    chart.render();

</script>
<?php scriptEnd(); ?>




