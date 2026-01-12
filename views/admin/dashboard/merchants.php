<?php
/**
 * Admin Dashboard - Merchants
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forhandlere";
$stats = $args->stats ?? (object)['totalMerchants' => 0, 'totalActive' => 0, 'totalDeactivated' => 0, 'newSignups' => 0];
$startDate = $args->startDate ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $args->endDate ?? date('Y-m-d');
$dailySignups = $args->dailySignups ?? [];
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "merchants";
    var adminMerchantsApiUrl = <?=json_encode(__url(Links::$api->admin->merchants->list))?>;
    var adminUserDetailUrl = <?=json_encode(__url(Links::$admin->users) . '/')?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Forhandlere</h1>
                    <p class="mb-0 font-14 color-gray"><span id="merchants-total-count"><?=number_format($stats->totalMerchants)?></span> forhandlere i alt</p>
                </div>
                <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                    <a href="<?=__url(Links::$admin->dashboardUsers)?>" class="btn-v2 trans-btn">Alle brugere</a>
                    <a href="<?=__url(Links::$admin->dashboardConsumers)?>" class="btn-v2 trans-btn">Forbrugere</a>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row flex-align-stretch rg-15">
                <div class="col-6 col-md-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Total forhandlere</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalMerchants)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-orange border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-store color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Aktive</p>
                                    <p class="mb-0 font-18 font-weight-bold color-success"><?=number_format($stats->totalActive)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-account-check color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Deaktiverede</p>
                                    <p class="mb-0 font-18 font-weight-bold color-danger"><?=number_format($stats->totalDeactivated)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-red border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-account-off color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Nye tilmeldinger</p>
                                    <p class="mb-0 font-18 font-weight-bold color-blue"><?=number_format($stats->newSignups)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-account-plus color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signups Chart -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-chart-line font-18 color-orange"></i>
                            <p class="mb-0 font-16 font-weight-bold">Nye forhandlere over tid</p>
                        </div>
                        <input type="text" class="form-field-v2" id="date-range-picker" style="min-width: 220px;" readonly>
                    </div>
                    <div id="signupsChart" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <input type="text" class="form-field-v2" id="merchants-search" placeholder="Søg efter navn, email eller telefon..." style="min-width: 250px;">
                            <select class="form-select-v2" id="merchants-filter-status" style="min-width: 130px;">
                                <option value="all" selected>Alle status</option>
                                <option value="active">Aktive</option>
                                <option value="deactivated">Deaktiverede</option>
                            </select>
                        </div>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                            <select class="form-select-v2" id="merchants-sort" style="min-width: 150px;">
                                <option value="created_at-DESC" selected>Nyeste først</option>
                                <option value="created_at-ASC">Ældste først</option>
                                <option value="full_name-ASC">Navn (A-Z)</option>
                                <option value="full_name-DESC">Navn (Z-A)</option>
                            </select>
                            <select class="form-select-v2" id="merchants-per-page" style="min-width: 80px;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Merchants Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="merchants-table">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Forhandler</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Kontakt</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Status</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Oprettet</th>
                                </tr>
                            </thead>
                            <tbody id="merchants-tbody">
                                <!-- Loading state -->
                                <tr id="merchants-loading-row">
                                    <td colspan="4" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser forhandlere...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No results message -->
                    <div id="merchants-no-results" class="d-none flex-col-center flex-align-center py-5">
                        <i class="mdi mdi-store-search-outline font-50 color-gray"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen forhandlere fundet</p>
                    </div>
                </div>

                <div class="card-footer bg-white border-top" id="merchants-pagination-footer">
                    <div class="flex-row-between flex-align-center">
                        <p class="mb-0 font-13 color-gray">
                            Viser <span id="merchants-showing-start">0</span> - <span id="merchants-showing-end">0</span> af <span id="merchants-total">0</span> forhandlere
                        </p>
                        <nav>
                            <ul class="pagination mb-0" id="merchants-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    const dailySignupsRaw = <?=json_encode(toArray($dailySignups))?>;
    const dailySignups = Array.isArray(dailySignupsRaw) ? dailySignupsRaw : [];

    // Calculate tick amount
    function getTickAmount(dataLength) {
        if (dataLength <= 10) return dataLength - 1;
        if (dataLength <= 30) return 9;
        if (dataLength <= 60) return 11;
        return 14;
    }

    // Initialize daterangepicker
    $(document).ready(function() {
        const startDate = moment('<?=$startDate?>', 'YYYY-MM-DD');
        const endDate = moment('<?=$endDate?>', 'YYYY-MM-DD');

        $('#date-range-picker').daterangepicker({
            startDate: startDate,
            endDate: endDate,
            ranges: {
                'Sidste 7 dage': [moment().subtract(6, 'days'), moment()],
                'Sidste 30 dage': [moment().subtract(29, 'days'), moment()],
                'Denne måned': [moment().startOf('month'), moment().endOf('month')],
                'Sidste måned': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Sidste 3 måneder': [moment().subtract(3, 'months'), moment()],
                'År til dato': [moment().startOf('year'), moment()]
            },
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Anvend',
                cancelLabel: 'Annuller',
                customRangeLabel: 'Vælg periode',
                daysOfWeek: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
                monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December']
            }
        }, function(start, end) {
            const url = new URL(window.location.href);
            url.searchParams.set('start', start.format('YYYY-MM-DD'));
            url.searchParams.set('end', end.format('YYYY-MM-DD'));
            window.location.href = url.toString();
        });

        $('#date-range-picker').val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));
    });

    // Signups chart
    var signupsChartOptions = {
        series: [{
            name: 'Nye forhandlere',
            type: 'area',
            data: dailySignups.map(d => d.count)
        }],
        chart: {
            height: 300,
            type: 'area',
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
            width: 3
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100]
            }
        },
        colors: ['#fd7e14'],
        labels: dailySignups.map(d => d.date),
        xaxis: {
            type: 'category',
            tickAmount: getTickAmount(dailySignups.length)
        },
        yaxis: {
            title: { text: 'Antal tilmeldinger' },
            labels: { formatter: value => Math.round(value) }
        },
        tooltip: {
            shared: true,
            intersect: false
        }
    };

    var signupsChart = new ApexCharts(document.querySelector("#signupsChart"), signupsChartOptions);
    signupsChart.render();
</script>
<?php scriptEnd(); ?>
