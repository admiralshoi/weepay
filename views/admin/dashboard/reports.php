<?php
/**
 * Admin Dashboard - Reports
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;
use classes\utility\Titles;

$pageTitle = "Rapporter";

// Convert data to arrays for JS
$paymentsByStatus = toArray($args->paymentsByStatus);
$dailyData = toArray($args->dailyData);
$revenueByOrg = toArray($args->revenueByOrg);
$revenueByLocation = toArray($args->revenueByLocation);
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "reports";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">

        <!-- Header with export buttons -->
        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .75rem;">
            <div class="flex-col-start">
                <h1 class="mb-0 font-24 font-weight-bold">Platform Rapporter</h1>
                <p class="mb-0 font-14 color-gray">Overblik over platform omsætning, betalinger og <?=Translate::word("organisationer")?></p>
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

        <!-- Filters -->
        <div class="flex-row-end flex-align-center flex-wrap mb-4" style="column-gap: .5rem; row-gap: .5rem;">
            <!-- Organisation Filter -->
            <select class="form-select-v2" id="organisation-filter" style="min-width: 180px;">
                <option value="all" <?=empty($args->selectedOrganisation) ? 'selected' : ''?>>Alle <?=Translate::word("organisationer")?></option>
                <?php foreach ($args->organisationOptions as $org): ?>
                    <option value="<?=$org->uid?>" <?=$args->selectedOrganisation === $org->uid ? 'selected' : ''?>><?=htmlspecialchars($org->name)?></option>
                <?php endforeach; ?>
            </select>

            <!-- Location Filter -->
            <select class="form-select-v2" id="location-filter" style="min-width: 180px;">
                <option value="all" <?=empty($args->selectedLocation) ? 'selected' : ''?>>Alle lokationer</option>
                <?php foreach ($args->locationOptions as $loc): ?>
                    <option value="<?=$loc->uid?>" <?=$args->selectedLocation === $loc->uid ? 'selected' : ''?>><?=htmlspecialchars($loc->name)?></option>
                <?php endforeach; ?>
            </select>

            <!-- Date Range -->
            <div class="position-relative">
                <input type="text" class="form-field-v2" id="reports-daterange"
                       placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                   id="reports-daterange-clear"
                   style="right: 8px; top: 50%; transform: translateY(-50%);"
                   title="Ryd datofilter"></i>
            </div>
        </div>

        <!-- KPI Cards - Row 1: Payment stats -->
        <div class="row flex-align-stretch rg-15 mb-3">
            <!-- Payment Revenue (Total Payment Amounts) -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Realiseret omsætning</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-revenue"><?=number_format($args->grossRevenue, 2, ',', '.')?> kr</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment ISV (Net Sales for Admin) -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Realiseret profit</p>
                            <p class="font-18 font-weight-700 mb-0 color-success" id="kpi-isv"><?=number_format($args->isvAmount, 2, ',', '.')?> kr</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Revenue -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Ordre omsætning</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-order-revenue"><?=number_format($args->orderRevenue ?? 0, 2, ',', '.')?> kr</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order ISV -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Ordre profit</p>
                            <p class="font-18 font-weight-700 mb-0 color-success" id="kpi-order-isv"><?=number_format($args->orderIsv ?? 0, 2, ',', '.')?> kr</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards - Row 2: Counts and rates -->
        <div class="row flex-align-stretch rg-15 mb-4">
            <!-- Orders -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Ordrer</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-orders"><?=number_format($args->orderCount)?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Betalinger</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-payments"><?=number_format($args->paymentCount)?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Order -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Gns. ordre</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-average"><?=number_format($args->orderAverage, 2, ',', '.')?> kr</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collection Rate -->
            <div class="col-6 col-md-4 col-lg-3 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start">
                            <p class="color-gray font-12 font-weight-medium mb-1">Indsamling</p>
                            <p class="font-18 font-weight-700 mb-0" id="kpi-collection"><?=number_format($args->collectionRate, 1)?>%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Buttons -->
        <div class="flex-row-start flex-align-center mb-4" style="gap: .5rem;">
            <button class="btn-v2 action-btn report-tab-btn active" data-tab="sales">
                <i class="mdi mdi-chart-line"></i>
                <span>Salg</span>
            </button>
            <button class="btn-v2 mute-btn report-tab-btn" data-tab="payments">
                <i class="mdi mdi-cash"></i>
                <span>Betalinger</span>
            </button>
            <button class="btn-v2 mute-btn report-tab-btn" data-tab="organisations">
                <i class="mdi mdi-domain"></i>
                <span><?=ucfirst(Translate::word("Organisations"))?></span>
            </button>
            <button class="btn-v2 mute-btn report-tab-btn" data-tab="locations">
                <i class="mdi mdi-map-marker"></i>
                <span>Lokationer</span>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content-wrapper">

            <!-- Sales Tab -->
            <div class="report-tab-content" id="tab-sales">
                <div class="row rg-15">
                    <!-- Revenue Chart -->
                    <div class="col-12">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                    <i class="mdi mdi-chart-areaspline font-18 color-blue"></i>
                                    <p class="mb-0 font-18 font-weight-bold">Realiseret omsætning over tid</p>
                                </div>
                                <div id="revenueChart" style="height: 350px;"></div>
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
                                            <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['COMPLETED']['amount'] ?? 0, 2, ',', '.')?> kr</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                    <span class="bg-pee-yellow border-radius-50" style="width: 10px; height: 10px;"></span>
                                                    Planlagt
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold"><?=$paymentsByStatus['SCHEDULED']['count'] ?? 0?></td>
                                            <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['SCHEDULED']['amount'] ?? 0, 2, ',', '.')?> kr</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                    <span class="bg-info border-radius-50" style="width: 10px; height: 10px;"></span>
                                                    Afventende
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold"><?=$paymentsByStatus['PENDING']['count'] ?? 0?></td>
                                            <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['PENDING']['amount'] ?? 0, 2, ',', '.')?> kr</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                    <span class="bg-danger border-radius-50" style="width: 10px; height: 10px;"></span>
                                                    Forsinket
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold"><?=$paymentsByStatus['PAST_DUE']['count'] ?? 0?></td>
                                            <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['PAST_DUE']['amount'] ?? 0, 2, ',', '.')?> kr</td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <span class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                    <span class="bg-secondary border-radius-50" style="width: 10px; height: 10px;"></span>
                                                    Fejlet
                                                </span>
                                            </td>
                                            <td class="text-end font-weight-bold"><?=$paymentsByStatus['FAILED']['count'] ?? 0?></td>
                                            <td class="text-end font-weight-bold"><?=number_format($paymentsByStatus['FAILED']['amount'] ?? 0, 2, ',', '.')?> kr</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organisations Tab -->
            <div class="report-tab-content d-none" id="tab-organisations">
                <div class="row rg-15">
                    <!-- Organisation Revenue Chart -->
                    <div class="col-12 col-lg-6">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                    <i class="mdi mdi-chart-bar font-18 color-blue"></i>
                                    <p class="mb-0 font-18 font-weight-bold">Omsætning pr. <?=Translate::word("organisation")?></p>
                                </div>
                                <div id="orgRevenueChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Organisation Table -->
                    <div class="col-12 col-lg-6">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                    <i class="mdi mdi-domain font-18 color-blue"></i>
                                    <p class="mb-0 font-18 font-weight-bold">Top <?=Translate::word("organisationer")?></p>
                                </div>

                                <?php if(empty($args->revenueByOrg)): ?>
                                    <p class="text-center color-gray py-4">Ingen data for valgte periode</p>
                                <?php else: ?>
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="font-13 color-gray font-weight-medium"><?=ucfirst(Translate::word("Organisation"))?></th>
                                                <th class="font-13 color-gray font-weight-medium text-end">Betalinger</th>
                                                <th class="font-13 color-gray font-weight-medium text-end">Omsætning</th>
                                                <th class="font-13 color-gray font-weight-medium text-end">ISV</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($args->revenueByOrg as $org): ?>
                                            <tr>
                                                <td class="font-weight-medium">
                                                    <a href="<?=__url(Links::$admin->organisationDetail($org->uid))?>" class="color-blue text-decoration-none" title="<?=htmlspecialchars($org->name)?>"><?=htmlspecialchars(mb_substr($org->name, 0, 25, 'UTF-8'))?></a>
                                                </td>
                                                <td class="text-end"><?=$org->payments?></td>
                                                <td class="text-end font-weight-bold"><?=number_format($org->revenue, 2, ',', '.')?> kr</td>
                                                <td class="text-end color-success"><?=number_format($org->isv, 2, ',', '.')?> kr</td>
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

            <!-- Locations Tab -->
            <div class="report-tab-content d-none" id="tab-locations">
                <div class="row rg-15">
                    <!-- Location Revenue Chart -->
                    <div class="col-12 col-lg-6">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                                    <i class="mdi mdi-chart-bar font-18 color-blue"></i>
                                    <p class="mb-0 font-18 font-weight-bold">Omsætning pr. lokation</p>
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
                                    <i class="mdi mdi-map-marker font-18 color-blue"></i>
                                    <p class="mb-0 font-18 font-weight-bold">Top lokationer</p>
                                </div>

                                <?php if(empty($args->revenueByLocation)): ?>
                                    <p class="text-center color-gray py-4">Ingen data for valgte periode</p>
                                <?php else: ?>
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="font-13 color-gray font-weight-medium">Lokation</th>
                                                <th class="font-13 color-gray font-weight-medium text-end">Betalinger</th>
                                                <th class="font-13 color-gray font-weight-medium text-end">Omsætning</th>
                                                <th class="font-13 color-gray font-weight-medium text-end">ISV</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($args->revenueByLocation as $loc): ?>
                                            <tr>
                                                <td class="font-weight-medium">
                                                    <a href="<?=__url(Links::$admin->locationDetail($loc->uid))?>" class="color-blue text-decoration-none" title="<?=htmlspecialchars($loc->name)?>"><?=htmlspecialchars(mb_substr($loc->name, 0, 25, 'UTF-8'))?></a>
                                                </td>
                                                <td class="text-end"><?=$loc->payments?></td>
                                                <td class="text-end font-weight-bold"><?=number_format($loc->revenue, 2, ',', '.')?> kr</td>
                                                <td class="text-end color-success"><?=number_format($loc->isv, 2, ',', '.')?> kr</td>
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
    var startDate = '<?=$args->queryStart?>';
    var endDate = '<?=$args->queryEnd?>';
    var displayStart = '<?=$args->startDate?>';
    var displayEnd = '<?=$args->endDate?>';

    // Set initial value from controller dates
    $dateRange.val(moment(displayStart).format('DD/MM/YYYY') + ' - ' + moment(displayEnd).format('DD/MM/YYYY'));
    if (startDate && endDate) {
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

            applyFilters(newStart, newEnd);
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

    function applyFilters(start, end) {
        var url = new URL(window.location.href);
        if (start) url.searchParams.set('start', start);
        if (end) url.searchParams.set('end', end);

        var org = $('#organisation-filter').val();
        var loc = $('#location-filter').val();

        // Treat 'all' as empty
        if (org && org !== 'all') url.searchParams.set('organisation', org);
        else url.searchParams.delete('organisation');
        if (loc && loc !== 'all') url.searchParams.set('location', loc);
        else url.searchParams.delete('location');

        window.location.href = url.toString();
    }

    // Filter change handlers
    $('#organisation-filter, #location-filter').on('change', function() {
        applyFilters(startDate, endDate);
    });

    // Chart data from PHP
    const dailyData = <?=json_encode(array_values($dailyData))?>;
    const paymentsByStatus = <?=json_encode($paymentsByStatus)?>;
    const revenueByOrg = <?=json_encode(array_values($revenueByOrg))?>;
    const revenueByLocation = <?=json_encode(array_values($revenueByLocation))?>;

    // Calculate tick amount to limit x-axis labels
    function getTickAmount(dataLength) {
        if (dataLength <= 10) return dataLength - 1;
        if (dataLength <= 30) return 9;
        if (dataLength <= 60) return 11;
        return 14;
    }

    // Revenue over time chart
    var revenueChartOptions = {
        series: [{
            name: 'Realiseret omsætning (DKK)',
            type: 'area',
            data: dailyData.map(d => d.revenue)
        }, {
            name: 'Antal betalinger',
            type: 'line',
            data: dailyData.map(d => d.payments)
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
        labels: dailyData.map(d => d.date),
        xaxis: {
            type: 'category',
            tickAmount: getTickAmount(dailyData.length)
        },
        yaxis: [{
            title: { text: 'Realiseret omsætning (DKK)' },
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

    var revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueChartOptions);
    revenueChart.render();

    // Payment status donut chart
    var paymentStatusOptions = {
        series: [
            paymentsByStatus.COMPLETED ? paymentsByStatus.COMPLETED.amount : 0,
            paymentsByStatus.SCHEDULED ? paymentsByStatus.SCHEDULED.amount : 0,
            paymentsByStatus.PENDING ? paymentsByStatus.PENDING.amount : 0,
            paymentsByStatus.PAST_DUE ? paymentsByStatus.PAST_DUE.amount : 0,
            paymentsByStatus.FAILED ? paymentsByStatus.FAILED.amount : 0
        ],
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['Gennemført', 'Planlagt', 'Afventende', 'Forsinket', 'Fejlet'],
        colors: ['#28a745', '#ffc107', '#17a2b8', '#dc3545', '#6c757d'],
        legend: { position: 'bottom' },
        dataLabels: {
            enabled: true,
            formatter: function (val) { return val.toFixed(1) + '%'; }
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK' }).format(value);
                }
            }
        }
    };

    var paymentStatusChart = new ApexCharts(document.querySelector("#paymentStatusChart"), paymentStatusOptions);
    paymentStatusChart.render();

    // Organisation revenue bar chart
    if (revenueByOrg.length > 0) {
        var orgRevenueOptions = {
            series: [{
                name: 'Omsætning',
                data: revenueByOrg.map(o => Math.round(o.revenue * 100) / 100)
            }, {
                name: 'ISV',
                data: revenueByOrg.map(o => Math.round(o.isv * 100) / 100)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', { maximumFractionDigits: 2 }).format(value);
                }
            },
            colors: ['#4BC0C0', '#28a745'],
            xaxis: {
                categories: revenueByOrg.map(o => o.name),
                labels: {
                    formatter: function (value) {
                        return shortNumbByT(value, true, true);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK', maximumFractionDigits: 2 }).format(value);
                    }
                }
            },
            legend: { position: 'top' }
        };

        var orgRevenueChart = new ApexCharts(document.querySelector("#orgRevenueChart"), orgRevenueOptions);
        orgRevenueChart.render();
    } else {
        document.querySelector("#orgRevenueChart").innerHTML = '<p class="text-center color-gray py-5">Ingen data</p>';
    }

    // Location revenue bar chart
    if (revenueByLocation.length > 0) {
        var locationRevenueOptions = {
            series: [{
                name: 'Omsætning',
                data: revenueByLocation.map(l => Math.round(l.revenue * 100) / 100)
            }, {
                name: 'ISV',
                data: revenueByLocation.map(l => Math.round(l.isv * 100) / 100)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (value) {
                    return new Intl.NumberFormat('da-DK', { maximumFractionDigits: 2 }).format(value);
                }
            },
            colors: ['#4BC0C0', '#28a745'],
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
                        return new Intl.NumberFormat('da-DK', { style: 'currency', currency: 'DKK', maximumFractionDigits: 2 }).format(value);
                    }
                }
            },
            legend: { position: 'top' }
        };

        var locationRevenueChart = new ApexCharts(document.querySelector("#locationRevenueChart"), locationRevenueOptions);
        locationRevenueChart.render();
    } else {
        document.querySelector("#locationRevenueChart").innerHTML = '<p class="text-center color-gray py-5">Ingen data</p>';
    }

    // Export functionality
    var exportCsvUrl = <?=json_encode(__url(Links::$api->admin->reports->generateCsv))?>;
    var exportPdfUrl = <?=json_encode(__url(Links::$api->admin->reports->generatePdf))?>;

    function exportReport(type) {
        var $btn = type === 'csv' ? $('#export-csv-btn') : $('#export-pdf-btn');
        var originalHtml = $btn.html();
        var url = type === 'csv' ? exportCsvUrl : exportPdfUrl;

        // Disable button and show loading
        $btn.prop('disabled', true);
        $btn.html('<i class="mdi mdi-loading mdi-spin"></i> <span>Genererer...</span>');

        // Build payload with current filters (treat 'all' as empty)
        var orgVal = $('#organisation-filter').val();
        var locVal = $('#location-filter').val();
        var payload = {
            start: startDate || moment().subtract(29, 'days').format('YYYY-MM-DD'),
            end: endDate || moment().format('YYYY-MM-DD'),
            organisation: (orgVal && orgVal !== 'all') ? orgVal : '',
            location: (locVal && locVal !== 'all') ? locVal : '',
            group_by: 'none'
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
