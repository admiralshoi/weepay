<?php
/**
 * Admin Dashboard - KPI Overview
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "KPI Oversigt";
$kpis = $args->kpis ?? (object)[];
$topOrganisations = $args->topOrganisations ?? [];
$topLocations = $args->topLocations ?? [];
$topCustomers = $args->topCustomers ?? [];
$startDate = $args->startDate ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $args->endDate ?? date('Y-m-d');
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "kpi";
</script>


<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">KPI Oversigt</h1>
                    <p class="mb-0 font-14 color-gray">Nøgletal og performance indikatorer</p>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <div class="position-relative">
                        <input type="text" class="form-field-v2" id="kpi-daterange"
                               placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                        <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                           id="kpi-daterange-clear"
                           style="right: 8px; top: 50%; transform: translateY(-50%);"
                           title="Ryd datofilter"></i>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- REVENUE & COMPLETED PAYMENTS KPIs -->
            <!-- ============================================ -->
            <div class="flex-col-start" style="gap: .5rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Omsætning & Betalinger</p>
                <div class="row flex-align-stretch rg-15">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Gennemførte betalinger</p>
                                    <p class="mb-0 font-22 font-weight-bold color-success"><?=number_format($kpis->completedPaymentsCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Realiseret omsætning</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->totalRevenue ?? 0, 0, ',', '.')?> kr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Realiseret profit</p>
                                    <p class="mb-0 font-22 font-weight-bold color-success"><?=number_format($kpis->totalIsv ?? 0, 0, ',', '.')?> kr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Gns. ordreværdi</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->avgOrderValue ?? 0, 0, ',', '.')?> kr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- ORDER KPIs -->
            <!-- ============================================ -->
            <div class="flex-col-start" style="gap: .5rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Ordrer</p>
                <div class="row flex-align-stretch rg-15">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Oprettede ordrer</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->totalOrdersCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Gennemførte ordrer</p>
                                    <p class="mb-0 font-22 font-weight-bold color-success"><?=number_format($kpis->completedOrdersCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Ordre omsætning</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->totalOrderRevenue ?? 0, 0, ',', '.')?> kr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Ordre profit</p>
                                    <p class="mb-0 font-22 font-weight-bold color-success"><?=number_format($kpis->totalOrderIsv ?? 0, 0, ',', '.')?> kr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- USER KPIs -->
            <!-- ============================================ -->
            <div class="flex-col-start" style="gap: .5rem;">
                <p class="mb-0 font-14 font-weight-bold color-gray text-uppercase">Nye brugere i perioden</p>
                <div class="row flex-align-stretch rg-15">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Nye brugere</p>
                                    <p class="mb-0 font-22 font-weight-bold color-blue"><?=number_format($kpis->newUsersCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Nye forbrugere</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->newConsumersCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Nye forhandlere</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->newMerchantsCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Nye <?=Translate::word("organisationer")?></p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->newOrganisationsCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
                        <div class="card border-radius-10px w-100">
                            <div class="card-body">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-11 color-gray text-wrap">Nye lokationer</p>
                                    <p class="mb-0 font-22 font-weight-bold"><?=number_format($kpis->newLocationsCount ?? 0)?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- TOP LISTS -->
            <!-- ============================================ -->
            <div class="row rg-15">
                <!-- Top Organisations -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-domain font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Top <?=Translate::word("Organisationer")?></p>
                            </div>
                            <?php if(isEmpty($topOrganisations)): ?>
                                <p class="mb-0 font-14 color-gray text-center py-3">Ingen data i perioden</p>
                            <?php else: ?>
                                <div class="flex-col-start" style="gap: 0;">
                                    <?php foreach ($topOrganisations as $index => $org): ?>
                                        <a href="<?=__url(Links::$admin->organisationDetail($org->uid))?>"
                                           class="flex-row-between flex-align-center py-2 <?=$index < count(toArray($topOrganisations)) - 1 ? 'border-bottom-card' : ''?>"
                                           style="text-decoration: none; color: inherit;">
                                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="font-12 font-weight-bold color-blue"><?=$index + 1?>.</span>
                                                <span class="font-13 text-truncate" style="max-width: 120px;" title="<?=htmlspecialchars($org->name)?>"><?=htmlspecialchars($org->name)?></span>
                                            </div>
                                            <div class="flex-row-end flex-align-center" style="gap: .75rem;">
                                                <span class="font-12 color-gray"><?=$org->orders?> ordrer</span>
                                                <span class="font-13 font-weight-bold"><?=number_format($org->revenue, 0, ',', '.')?> kr</span>
                                                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


                <!-- Top Locations -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-map-marker font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Top Lokationer</p>
                            </div>
                            <?php if(isEmpty($topLocations)): ?>
                                <p class="mb-0 font-14 color-gray text-center py-3">Ingen data i perioden</p>
                            <?php else: ?>
                                <div class="flex-col-start" style="gap: 0;">
                                    <?php foreach ($topLocations as $index => $loc): ?>
                                        <a href="<?=__url(Links::$admin->locationDetail($loc->uid))?>"
                                           class="flex-row-between flex-align-center py-2 <?=$index < count(toArray($topLocations)) - 1 ? 'border-bottom-card' : ''?>"
                                           style="text-decoration: none; color: inherit;">
                                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="font-12 font-weight-bold color-blue"><?=$index + 1?>.</span>
                                                <span class="font-13 text-truncate" style="max-width: 120px;" title="<?=htmlspecialchars($loc->name)?>"><?=htmlspecialchars($loc->name)?></span>
                                            </div>
                                            <div class="flex-row-end flex-align-center" style="gap: .75rem;">
                                                <span class="font-12 color-gray"><?=$loc->orders?> ordrer</span>
                                                <span class="font-13 font-weight-bold"><?=number_format($loc->revenue, 0, ',', '.')?> kr</span>
                                                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-account-star font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Top Kunder</p>
                            </div>
                            <?php if(isEmpty($topCustomers)): ?>
                                <p class="mb-0 font-14 color-gray text-center py-3">Ingen data i perioden</p>
                            <?php else: ?>
                                <div class="flex-col-start" style="gap: 0;">
                                    <?php foreach ($topCustomers as $index => $customer): ?>
                                        <a href="<?=__url(Links::$admin->userDetail($customer->uid))?>"
                                           class="flex-row-between flex-align-center py-2 <?=$index < count(toArray($topCustomers)) - 1 ? 'border-bottom-card' : ''?>"
                                           style="text-decoration: none; color: inherit;">
                                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                                <span class="font-12 font-weight-bold color-blue"><?=$index + 1?>.</span>
                                                <span class="font-13 text-truncate" style="max-width: 120px;" title="<?=htmlspecialchars($customer->name)?>"><?=htmlspecialchars($customer->name)?></span>
                                            </div>
                                            <div class="flex-row-end flex-align-center" style="gap: .75rem;">
                                                <span class="font-12 color-gray"><?=$customer->orders?> ordrer</span>
                                                <span class="font-13 font-weight-bold"><?=number_format($customer->spent, 0, ',', '.')?> kr</span>
                                                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
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
    var kpiStartDate = '<?=$startDate?>';
    var kpiEndDate = '<?=$endDate?>';

    $(document).ready(function() {
        var $dateRange = $('#kpi-daterange');
        var $dateRangeClear = $('#kpi-daterange-clear');

        // Set initial value if dates are set
        if (kpiStartDate && kpiEndDate) {
            $dateRange.val(moment(kpiStartDate).format('DD/MM/YYYY') + ' - ' + moment(kpiEndDate).format('DD/MM/YYYY'));
            $dateRangeClear.removeClass('d-none');
        }

        $('#kpi-daterange').daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            startDate: kpiStartDate ? moment(kpiStartDate) : moment().subtract(29, 'days'),
            endDate: kpiEndDate ? moment(kpiEndDate) : moment(),
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

        $('#kpi-daterange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            $dateRangeClear.removeClass('d-none');

            var url = new URL(window.location.href);
            url.searchParams.set('start', picker.startDate.format('YYYY-MM-DD'));
            url.searchParams.set('end', picker.endDate.format('YYYY-MM-DD'));
            window.location.href = url.toString();
        });

        $('#kpi-daterange').on('cancel.daterangepicker', function(ev, picker) {
            clearKpiDateRange();
        });

        $dateRangeClear.on('click', function(e) {
            e.stopPropagation();
            clearKpiDateRange();
        });
    });

    function clearKpiDateRange() {
        $('#kpi-daterange').val('');
        $('#kpi-daterange-clear').addClass('d-none');
        var url = new URL(window.location.href);
        url.searchParams.delete('start');
        url.searchParams.delete('end');
        window.location.href = url.toString();
    }
</script>
<?php scriptEnd(); ?>
