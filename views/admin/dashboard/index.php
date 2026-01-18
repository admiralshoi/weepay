<?php
/**
 * Admin Dashboard - Home
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;


$pageTitle = "Dashboard";
$kpis = $args->kpis ?? (object)[];
$chartData = $args->chartData ?? [];
$userGrowthData = $args->userGrowthData ?? [];
$stats = $args->stats ?? (object)[];
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
    var adminDashboardStatsUrl = "api/admin/dashboard/stats";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Dashboard</h1>
                    <p class="mb-0 font-14 color-gray">Oversigt over hele systemet</p>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <input type="text" class="form-field-v2" id="dashboard-daterange" style="min-width: 200px; cursor: pointer;" readonly>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row flex-align-stretch rg-15">
                <!-- Realised Revenue (from payments) -->
                <div class="col-12 col-md-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="color-gray font-12 font-weight-medium mb-0 text-wrap">Realiseret omsætning</p>
                                    <p class="font-18 font-weight-700 mb-0" id="kpi-revenue">0,00 kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cash-multiple color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Realised Profit (ISV from payments) -->
                <div class="col-12 col-md-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="color-gray font-12 font-weight-medium mb-0 text-wrap">Realiseret profit</p>
                                    <p class="font-18 font-weight-700 mb-0 color-success" id="kpi-isv">0,00 kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-chart-line color-white font-22"></i>
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
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="color-gray font-12 font-weight-medium mb-0 text-wrap">Ordrer</p>
                                    <p class="font-18 font-weight-700 mb-0" id="kpi-orders">0</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cart-outline color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Users -->
                <div class="col-12 col-md-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="color-gray font-12 font-weight-medium mb-0 text-wrap">Nye brugere</p>
                                    <p class="font-18 font-weight-700 mb-0" id="kpi-users">0</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-account-plus color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Alerts & Quick Stats Row -->
            <div class="row rg-15 align-items-stretch">
                <!-- Alerts Section -->
                <div class="col-12 col-lg-6 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-bell-alert font-18 color-warning"></i>
                                <p class="mb-0 font-16 font-weight-bold">Handlinger påkrævet</p>
                            </div>

                            <div class="flex-col-start" style="gap: .75rem;">
                                <?php if(($kpis->pastDuePayments ?? 0) > 0): ?>
                                <div class="flex-row-between flex-align-center p-3 bg-light-gray border-radius-8px">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-danger border-radius-50 flex-row-center-center">
                                            <i class="mdi mdi-alert color-white font-18"></i>
                                        </div>
                                        <div class="flex-col-start">
                                            <p class="mb-0 font-14 font-weight-medium">Forfaldne betalinger</p>
                                            <p class="mb-0 font-12 color-gray"><?=$kpis->pastDuePayments?> betalinger er forfaldne</p>
                                        </div>
                                    </div>
                                    <a href="<?=__url(Links::$admin->dashboardPaymentsPastDue)?>" class="btn-v2 action-btn font-12">Se alle</a>
                                </div>
                                <?php endif; ?>

                                <?php if(($kpis->pendingPayments ?? 0) > 0): ?>
                                <div class="flex-row-between flex-align-center p-3 bg-light-gray border-radius-8px">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-pee-yellow border-radius-50 flex-row-center-center">
                                            <i class="mdi mdi-clock-outline color-white font-18"></i>
                                        </div>
                                        <div class="flex-col-start">
                                            <p class="mb-0 font-14 font-weight-medium">Afventende betalinger</p>
                                            <p class="mb-0 font-12 color-gray"><?=$kpis->pendingPayments?> betalinger afventer</p>
                                        </div>
                                    </div>
                                    <a href="<?=__url(Links::$admin->dashboardPaymentsPending)?>" class="btn-v2 action-btn font-12">Se alle</a>
                                </div>
                                <?php endif; ?>

                                <?php if(($kpis->pastDuePayments ?? 0) === 0 && ($kpis->pendingPayments ?? 0) === 0): ?>
                                <div class="flex-col-center flex-align-center py-4">
                                    <i class="mdi mdi-check-circle-outline font-40 color-success"></i>
                                    <p class="mb-0 font-14 color-gray mt-2">Ingen aktive advarsler</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="col-12 col-lg-6 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3">
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <i class="mdi mdi-chart-box font-18 color-blue"></i>
                                    <p class="mb-0 font-16 font-weight-bold">System oversigt</p>
                                </div>
                                <a href="<?=__url(Links::$admin->dashboardReports)?>" class="btn-v2 trans-btn font-12">
                                    <i class="mdi mdi-file-chart mr-1"></i> Rapporter
                                </a>
                            </div>

                            <div class="flex-col-start" style="gap: 0;">
                                <a href="<?=__url(Links::$admin->dashboardUsers)?>" class="flex-row-between flex-align-center py-3 border-bottom-card cursor-pointer" style="text-decoration: none; color: inherit;">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                                            <i class="mdi mdi-account-group color-white font-18"></i>
                                        </div>
                                        <p class="mb-0 font-14 color-dark">Total brugere</p>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($stats->totalUsers ?? 0)?></p>
                                        <i class="mdi mdi-chevron-right font-16 color-gray"></i>
                                    </div>
                                </a>
                                <a href="<?=__url(Links::$admin->dashboardOrganisations)?>" class="flex-row-between flex-align-center py-3 border-bottom-card cursor-pointer" style="text-decoration: none; color: inherit;">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-green border-radius-8px flex-row-center-center">
                                            <i class="mdi mdi-domain color-white font-18"></i>
                                        </div>
                                        <p class="mb-0 font-14 color-dark"><?=ucfirst(Translate::word("Organisationer"))?></p>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($stats->totalOrganisations ?? 0)?></p>
                                        <i class="mdi mdi-chevron-right font-16 color-gray"></i>
                                    </div>
                                </a>
                                <a href="<?=__url(Links::$admin->dashboardLocations)?>" class="flex-row-between flex-align-center py-3 border-bottom-card cursor-pointer" style="text-decoration: none; color: inherit;">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-pee-yellow border-radius-8px flex-row-center-center">
                                            <i class="mdi mdi-map-marker color-white font-18"></i>
                                        </div>
                                        <p class="mb-0 font-14 color-dark">Lokationer</p>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($stats->totalLocations ?? 0)?></p>
                                        <i class="mdi mdi-chevron-right font-16 color-gray"></i>
                                    </div>
                                </a>
                                <a href="<?=__url(Links::$admin->dashboardOrders)?>" class="flex-row-between flex-align-center py-3 cursor-pointer" style="text-decoration: none; color: inherit;">
                                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                        <div class="square-40 bg-info border-radius-8px flex-row-center-center">
                                            <i class="mdi mdi-cart-outline color-white font-18"></i>
                                        </div>
                                        <p class="mb-0 font-14 color-dark">Total ordrer</p>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                        <p class="mb-0 font-16 font-weight-bold"><?=number_format($stats->totalOrders ?? 0)?></p>
                                        <i class="mdi mdi-chevron-right font-16 color-gray"></i>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Charts Row - Moved to end -->
            <div class="row rg-15">
                <!-- Realised Revenue Chart (from payments) -->
                <div class="col-12 col-lg-8">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-chart-areaspline font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Realiseret omsætning over tid</p>
                            </div>
                            <div id="revenueChart" style="height: 350px;"></div>
                        </div>
                    </div>
                </div>

                <!-- User Growth Chart -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-account-group font-18 color-success"></i>
                                <p class="mb-0 font-16 font-weight-bold">Bruger vækst</p>
                            </div>
                            <div id="userGrowthChart" style="height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    const chartDataRaw = <?=json_encode($chartData)?>;
    const userGrowthDataRaw = <?=json_encode($userGrowthData)?>;
    const chartData = Array.isArray(chartDataRaw) ? chartDataRaw : [];
    const userGrowthData = Array.isArray(userGrowthDataRaw) ? userGrowthDataRaw : [];

    // Format number as Danish currency
    function formatDKK(num) {
        return new Intl.NumberFormat('da-DK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num) + ' kr';
    }

    // Calculate tick amount to limit x-axis labels (max ~10-15 labels)
    function getTickAmount(dataLength) {
        if (dataLength <= 10) return dataLength - 1;
        if (dataLength <= 30) return 9;
        if (dataLength <= 60) return 11;
        return 14;
    }

    // Fetch and update KPIs and charts based on date range
    async function fetchDashboardStats(startDate, endDate) {
        try {
            const result = await post(adminDashboardStatsUrl, {
                start_date: startDate,
                end_date: endDate
            });

            if (result.status === 'success') {
                const data = result.data;
                $('#kpi-revenue').text(formatDKK(data.revenue));
                $('#kpi-isv').text(formatDKK(data.isv_amount));
                $('#kpi-orders').text(data.orders_count.toLocaleString('da-DK'));
                $('#kpi-users').text(data.new_users.toLocaleString('da-DK'));

                // Update revenue chart
                if (data.revenue_chart && data.revenue_chart.length > 0) {
                    revenueChart.updateOptions({
                        labels: data.revenue_chart.map(d => d.date),
                        xaxis: {
                            type: 'category',
                            tickAmount: getTickAmount(data.revenue_chart.length)
                        }
                    });
                    revenueChart.updateSeries([{
                        name: 'Realiseret omsætning (DKK)',
                        type: 'area',
                        data: data.revenue_chart.map(d => d.revenue)
                    }, {
                        name: 'Antal betalinger',
                        type: 'line',
                        data: data.revenue_chart.map(d => d.payments)
                    }]);
                }

                // Update user growth chart
                if (data.user_growth_chart && data.user_growth_chart.length > 0) {
                    userGrowthChart.updateOptions({
                        xaxis: {
                            categories: data.user_growth_chart.map(d => d.date),
                            tickAmount: getTickAmount(data.user_growth_chart.length)
                        }
                    });
                    userGrowthChart.updateSeries([{
                        name: 'Forbrugere',
                        data: data.user_growth_chart.map(d => d.consumers)
                    }, {
                        name: 'Forhandlere',
                        data: data.user_growth_chart.map(d => d.merchants)
                    }]);
                }
            }
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
        }
    }

    // Initialize daterangepicker
    $(document).ready(function() {
        const today = moment().startOf('day');

        $('#dashboard-daterange').daterangepicker({
            opens: 'left',
            startDate: today,
            endDate: today,
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Anvend',
                cancelLabel: 'Annuller',
                fromLabel: 'Fra',
                toLabel: 'Til',
                customRangeLabel: 'Vælg periode',
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
        }, function(start, end) {
            fetchDashboardStats(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
        });

        // Fetch initial stats for today
        fetchDashboardStats(today.format('YYYY-MM-DD'), today.format('YYYY-MM-DD'));
    });

    // Revenue Chart
    var revenueOptions = {
        series: [{
            name: 'Realiseret omsætning (DKK)',
            type: 'area',
            data: chartData.map(d => d.revenue)
        }, {
            name: 'Antal betalinger',
            type: 'line',
            data: chartData.map(d => d.payments)
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
        dataLabels: { enabled: false },
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
            type: 'category',
            tickAmount: getTickAmount(chartData.length)
        },
        yaxis: [{
            title: { text: 'Realiseret omsætning (DKK)' },
            labels: {
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK', minimumFractionDigits: 0 }).format(value);
                }
            }
        }, {
            opposite: true,
            title: { text: 'Antal betalinger' },
            labels: { formatter: value => Math.round(value) }
        }],
        tooltip: {
            shared: true,
            intersect: false,
            y: [{
                formatter: value => new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK' }).format(value)
            }, {
                formatter: value => Math.round(value) + ' betalinger'
            }]
        },
        legend: { position: 'top', horizontalAlign: 'left' }
    };

    var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
    revenueChart.render();

    // User Growth Chart
    var userGrowthOptions = {
        series: [{
            name: 'Forbrugere',
            data: userGrowthData.map(d => d.consumers)
        }, {
            name: 'Forhandlere',
            data: userGrowthData.map(d => d.merchants)
        }],
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: { show: false }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 4
            }
        },
        dataLabels: { enabled: false },
        colors: ['#4BC0C0', '#FF6384'],
        xaxis: {
            categories: userGrowthData.map(d => d.date),
            tickAmount: getTickAmount(userGrowthData.length)
        },
        yaxis: {
            title: { text: 'Nye brugere' }
        },
        legend: { position: 'top' },
        fill: { opacity: 1 }
    };

    var userGrowthChart = new ApexCharts(document.querySelector("#userGrowthChart"), userGrowthOptions);
    userGrowthChart.render();
</script>
<?php scriptEnd(); ?>
