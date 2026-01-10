<?php
/**
 * Reports Page
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Rapporter";

// Convert paymentsByStatus to array for easier access
$paymentsByStatus = toArray($args->paymentsByStatus);
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "reports";
</script>

<div class="page-content">

    <!-- Header with filters -->
    <div class="flex-row-between flex-align-center flex-wrap" id="nav" style="column-gap: .5rem; row-gap: .75rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>

        <div class="flex-row-end flex-align-center flex-wrap" style="column-gap: .5rem; row-gap: .5rem;">
            <div class="form-group mb-0 position-relative">
                <input type="text" class="form-control-v2 form-field-v2" id="reports-daterange"
                       placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                   id="reports-daterange-clear"
                   style="right: 8px; top: 50%; transform: translateY(-50%);"
                   title="Ryd datofilter"></i>
            </div>
        </div>
    </div>

    <!-- Page Title -->
    <div class="flex-row-between flex-align-center flex-wrap mb-4" style="row-gap: .75rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Rapporter</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Overblik over salg, betalinger og kunder</p>
        </div>
        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
            <button class="btn-v2 mute-btn" id="export-csv-btn" onclick="exportReport('csv')">
                <i class="mdi mdi-file-delimited-outline"></i>
                <span>Download CSV</span>
            </button>
            <button class="btn-v2 action-btn" id="export-pdf-btn" onclick="exportReport('pdf')">
                <i class="mdi mdi-file-pdf-box"></i>
                <span>Download PDF</span>
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row flex-align-stretch rg-15">
        <!-- Gross Revenue -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Omsætning</p>
                            <p class="font-18 font-weight-700"><?=number_format($args->grossRevenue, 0, ',', '.')?> kr</p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-currency-usd color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Revenue -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Netto</p>
                            <p class="font-18 font-weight-700"><?=number_format($args->netRevenue, 0, ',', '.')?> kr</p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-chart-line color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Count -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Ordrer</p>
                            <p class="font-18 font-weight-700"><?=$args->orderCount?></p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-cart-outline color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Average Order -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Gns. ordre</p>
                            <p class="font-18 font-weight-700"><?=number_format($args->orderAverage, 0, ',', '.')?> kr</p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-pee-yellow border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-trending-up color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Kunder</p>
                            <p class="font-18 font-weight-700"><?=$args->customerCount?></p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-account-heart-outline color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="col-12 col-md-6 col-lg-4 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025 flex-1 min-width-0">
                            <p class="color-gray font-12 font-weight-medium text-truncate">Indsamling</p>
                            <p class="font-18 font-weight-700"><?=number_format($args->collectionRate, 1)?>%</p>
                        </div>
                        <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                            <i class="mdi mdi-check-circle-outline color-white font-22"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="flex-row-start flex-align-center mt-4" style="gap: .5rem;">
        <button class="btn-v2 action-btn report-tab-btn active" data-tab="sales">
            <i class="mdi mdi-chart-line"></i>
            <span>Salg</span>
        </button>
        <button class="btn-v2 mute-btn report-tab-btn" data-tab="payments">
            <i class="mdi mdi-cash"></i>
            <span>Betalinger</span>
        </button>
        <button class="btn-v2 mute-btn report-tab-btn" data-tab="locations">
            <i class="mdi mdi-store-outline"></i>
            <span>Butikker</span>
        </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-4">

        <!-- Sales Tab -->
        <div class="report-tab-content" id="tab-sales">
            <div class="row rg-15">
                <!-- Revenue Chart -->
                <div class="col-12 col-lg-8">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-chart-areaspline font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Omsætning over tid</p>
                            </div>
                            <div id="revenueChart" style="height: 350px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Payment Type Breakdown -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-chart-pie font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Betalingstype</p>
                            </div>
                            <div id="paymentTypeChart" style="height: 250px;"></div>

                            <div class="mt-3">
                                <div class="flex-row-between flex-align-center py-2" style="border-bottom: 1px solid var(--card-border-color);">
                                    <span class="font-14">BNPL (Delbetaling)</span>
                                    <span class="font-14 font-weight-bold"><?=$args->bnplCount?> ordrer</span>
                                </div>
                                <div class="flex-row-between flex-align-center py-2">
                                    <span class="font-14">Fuld betaling</span>
                                    <span class="font-14 font-weight-bold"><?=$args->fullPaymentCount?> ordrer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <div class="report-tab-content d-none" id="tab-payments">
            <div class="row rg-15">
                <!-- Payment Status Chart -->
                <div class="col-12 col-lg-6">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-chart-donut font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Betalingsstatus</p>
                            </div>
                            <div id="paymentStatusChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Table -->
                <div class="col-12 col-lg-6">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-table font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Betalingsoversigt</p>
                            </div>

                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-13 color-gray font-weight-medium">Status</th>
                                        <th class="font-13 color-gray font-weight-medium text-end">Antal</th>
                                        <th class="font-13 color-gray font-weight-medium text-end">Beløb</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="bg-green border-radius-50" style="width: 10px; height: 10px;"></span>
                                                Gennemført
                                            </span>
                                        </td>
                                        <td class="text-end font-weight-bold"><?=$paymentsByStatus['COMPLETED']['count'] ?? 0?></td>
                                        <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['COMPLETED']['amount'] ?? 0, 0, ',', '.')?> kr</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="bg-pee-yellow border-radius-50" style="width: 10px; height: 10px;"></span>
                                                Planlagt
                                            </span>
                                        </td>
                                        <td class="text-end font-weight-bold"><?=$paymentsByStatus['SCHEDULED']['count'] ?? 0?></td>
                                        <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['SCHEDULED']['amount'] ?? 0, 0, ',', '.')?> kr</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="bg-danger border-radius-50" style="width: 10px; height: 10px;"></span>
                                                Forsinket
                                            </span>
                                        </td>
                                        <td class="text-end font-weight-bold"><?=$paymentsByStatus['PAST_DUE']['count'] ?? 0?></td>
                                        <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['PAST_DUE']['amount'] ?? 0, 0, ',', '.')?> kr</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="bg-secondary border-radius-50" style="width: 10px; height: 10px;"></span>
                                                Fejlet
                                            </span>
                                        </td>
                                        <td class="text-end font-weight-bold"><?=$paymentsByStatus['FAILED']['count'] ?? 0?></td>
                                        <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['FAILED']['amount'] ?? 0, 0, ',', '.')?> kr</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locations Tab -->
        <div class="report-tab-content d-none" id="tab-locations">
            <div class="row rg-15">
                <!-- Location Revenue Chart -->
                <div class="col-12 col-lg-6">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-chart-bar font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Omsætning pr. butik</p>
                            </div>
                            <div id="locationRevenueChart" style="height: 300px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Location Table -->
                <div class="col-12 col-lg-6">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                <i class="mdi mdi-store-outline font-18 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Butiksoversigt</p>
                            </div>

                            <?php if(empty($args->revenueByLocation)): ?>
                                <p class="text-center color-gray py-4">Ingen data for valgte periode</p>
                            <?php else: ?>
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th class="font-13 color-gray font-weight-medium">Butik</th>
                                            <th class="font-13 color-gray font-weight-medium text-end">Ordrer</th>
                                            <th class="font-13 color-gray font-weight-medium text-end">Omsætning</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($args->revenueByLocation as $location): ?>
                                        <tr>
                                            <td class="font-weight-medium"><?=htmlspecialchars($location->name)?></td>
                                            <td class="text-end"><?=$location->orders?></td>
                                            <td class="text-end font-weight-bold"><?=number_format($location->revenue, 0, ',', '.')?> kr</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php scriptStart(); ?>
<script>
    // Tab switching
    document.querySelectorAll('.report-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;

            // Update button states
            document.querySelectorAll('.report-tab-btn').forEach(b => {
                b.classList.remove('active', 'action-btn');
                b.classList.add('mute-btn');
            });
            this.classList.remove('mute-btn');
            this.classList.add('active', 'action-btn');

            // Show/hide tab content
            document.querySelectorAll('.report-tab-content').forEach(content => {
                content.classList.add('d-none');
            });
            document.getElementById('tab-' + tabId).classList.remove('d-none');
        });
    });

    // Date range picker
    var $dateRange = $('#reports-daterange');
    var $dateRangeClear = $('#reports-daterange-clear');
    var startDate = '<?=$args->startDate?>';
    var endDate = '<?=$args->endDate?>';

    // Set initial value if dates are set
    if (startDate && endDate) {
        $dateRange.val(moment(startDate).format('DD/MM/YYYY') + ' - ' + moment(endDate).format('DD/MM/YYYY'));
        $dateRangeClear.removeClass('d-none');
    }

    if ($dateRange.length && typeof $dateRange.daterangepicker === 'function') {
        $dateRange.daterangepicker({
            autoUpdateInput: false,
            startDate: startDate ? moment(startDate) : moment().subtract(29, 'days'),
            endDate: endDate ? moment(endDate) : moment(),
            locale: {
                format: 'DD/MM/YYYY',
                separator: ' - ',
                applyLabel: 'Anvend',
                cancelLabel: 'Ryd',
                fromLabel: 'Fra',
                toLabel: 'Til',
                customRangeLabel: 'Brugerdefineret',
                weekLabel: 'U',
                daysOfWeek: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
                monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'],
                firstDay: 1
            },
            ranges: {
                'I dag': [moment(), moment()],
                'I går': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Sidste 7 dage': [moment().subtract(6, 'days'), moment()],
                'Sidste 30 dage': [moment().subtract(29, 'days'), moment()],
                'Denne måned': [moment().startOf('month'), moment().endOf('month')],
                'Sidste måned': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        $dateRange.on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            var newStart = picker.startDate.format('YYYY-MM-DD');
            var newEnd = picker.endDate.format('YYYY-MM-DD');
            $dateRangeClear.removeClass('d-none');

            // Reload page with new dates
            var url = new URL(window.location.href);
            url.searchParams.set('start', newStart);
            url.searchParams.set('end', newEnd);
            window.location.href = url.toString();
        });

        $dateRange.on('cancel.daterangepicker', function(ev, picker) {
            clearDateRange();
        });

        $dateRangeClear.on('click', function(e) {
            e.stopPropagation();
            clearDateRange();
        });
    }

    function clearDateRange() {
        $dateRange.val('');
        $dateRangeClear.addClass('d-none');
        var url = new URL(window.location.href);
        url.searchParams.delete('start');
        url.searchParams.delete('end');
        window.location.href = url.toString();
    }

    // Chart data from PHP
    const dailyData = <?=json_encode(array_values(toArray($args->dailyData)))?>;
    const revenueByLocation = <?=json_encode(array_values(toArray($args->revenueByLocation)))?>;
    const paymentsByStatus = <?=json_encode(toArray($args->paymentsByStatus))?>;
    const bnplRevenue = <?=$args->bnplRevenue ?: 0?>;
    const fullPaymentRevenue = <?=$args->fullPaymentRevenue ?: 0?>;

    // Revenue over time chart
    var revenueChartOptions = {
        series: [{
            name: 'Omsætning (DKK)',
            type: 'area',
            data: dailyData.map(d => d.revenue)
        }, {
            name: 'Antal ordrer',
            type: 'line',
            data: dailyData.map(d => d.orders)
        }],
        chart: {
            height: 350,
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
        labels: dailyData.map(d => d.date),
        xaxis: {
            type: 'category',
            tickAmount: Math.min(dailyData.length, 10),
            labels: {
                rotate: -45,
                rotateAlways: false,
                hideOverlappingLabels: true
            }
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

    var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueChartOptions);
    revenueChart.render();

    // Payment type donut chart
    var paymentTypeOptions = {
        series: [bnplRevenue, fullPaymentRevenue],
        chart: {
            type: 'donut',
            height: 250
        },
        labels: ['BNPL (Delbetaling)', 'Fuld betaling'],
        colors: ['#4BC0C0', '#36A2EB'],
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', {
                        style: 'currency',
                        currency: 'DKK'
                    }).format(value);
                }
            }
        }
    };

    var paymentTypeChart = new ApexCharts(document.querySelector("#paymentTypeChart"), paymentTypeOptions);
    paymentTypeChart.render();

    // Payment status donut chart
    var paymentStatusOptions = {
        series: [
            paymentsByStatus.COMPLETED ? paymentsByStatus.COMPLETED.amount : 0,
            paymentsByStatus.SCHEDULED ? paymentsByStatus.SCHEDULED.amount : 0,
            paymentsByStatus.PAST_DUE ? paymentsByStatus.PAST_DUE.amount : 0,
            paymentsByStatus.FAILED ? paymentsByStatus.FAILED.amount : 0
        ],
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['Gennemført', 'Planlagt', 'Forsinket', 'Fejlet'],
        colors: ['#28a745', '#ffc107', '#dc3545', '#6c757d'],
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', {
                        style: 'currency',
                        currency: 'DKK'
                    }).format(value);
                }
            }
        }
    };

    var paymentStatusChart = new ApexCharts(document.querySelector("#paymentStatusChart"), paymentStatusOptions);
    paymentStatusChart.render();

    // Location revenue bar chart
    if (revenueByLocation.length > 0) {
        var locationRevenueOptions = {
            series: [{
                name: 'Omsætning',
                data: revenueByLocation.map(l => l.revenue)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            colors: ['#4BC0C0'],
            xaxis: {
                categories: revenueByLocation.map(l => l.name),
                labels: {
                    formatter: function (value) {
                        return shortNumbByT(value, true, true);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('da-DK', {
                            style: 'currency',
                            currency: 'DKK'
                        }).format(value);
                    }
                }
            }
        };

        var locationRevenueChart = new ApexCharts(document.querySelector("#locationRevenueChart"), locationRevenueOptions);
        locationRevenueChart.render();
    } else {
        document.querySelector("#locationRevenueChart").innerHTML = '<p class="text-center color-gray py-5">Ingen data</p>';
    }

    // Export functionality
    var exportCsvUrl = <?=json_encode(__url(Links::$api->organisation->reports->generateCsv))?>;
    var exportPdfUrl = <?=json_encode(__url(Links::$api->organisation->reports->generatePdf))?>;

    function exportReport(type) {
        var $btn = type === 'csv' ? $('#export-csv-btn') : $('#export-pdf-btn');
        var originalHtml = $btn.html();
        var url = type === 'csv' ? exportCsvUrl : exportPdfUrl;

        // Disable button and show loading
        $btn.prop('disabled', true);
        $btn.html('<i class="mdi mdi-loading mdi-spin"></i> <span>Genererer...</span>');

        // Build payload with current date filters
        var payload = {
            start: startDate || moment().subtract(29, 'days').format('YYYY-MM-DD'),
            end: endDate || moment().format('YYYY-MM-DD')
        };

        $.ajax({
            url: url,
            method: 'POST',
            data: payload,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success' && response.data && response.data.download_url) {
                    // Trigger download
                    window.location.href = response.data.download_url;
                    showSuccessNotification(type.toUpperCase() + '-rapport genereret');
                } else {
                    showErrorNotification(response.message || 'Kunne ikke generere rapport');
                }
            },
            error: function(xhr) {
                var message = 'Kunne ikke generere rapport';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showErrorNotification(message);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    }

</script>
<?php scriptEnd(); ?>
