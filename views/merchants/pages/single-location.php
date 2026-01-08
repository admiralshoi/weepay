<?php
/**
 * @var object $args
 */

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;

$location = $args->location;
$pageTitle = $location->name . " - Lokation";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
    var locations = <?=json_encode([$location])?>;
    var locationOrdersApiUrl = <?=json_encode(__url(Links::$api->orders->locationOrders($args->slug)))?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions, $args->slug);?>

        <div class="flex-row-end">
            <button class="btn-v2 mute-btn font-13 font-weight-medium flex-row-center-center cg-075"
                    onclick="LocationActions.editLocationDetails('<?=$location->uid?>')" name="edit_location_details">
                <i class="mdi mdi-cog-outline"></i>
                <span>Indstillinger</span>
            </button>
        </div>
    </div>



    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Overblik</p>
            <p class="mb-0 font-16 font-weight-medium color-gray"><?=$location->name?></p>
        </div>
        <div class="flex-row-end-center cg-075 flex-nowrap">
            <?php LocationPermissions::__oReadProtectedContent($location,  'team_members'); ?>
            <a href="<?=__url(Links::$merchant->locations->members($args->slug))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-account-multiple-outline"></i>
                <span class="text-nowrap">Medarbejdere</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>
            <?php if($location->status === 'ACTIVE'): ?>
            <button class="btn-v2 green-btn text-nowrap" onclick="LocationActions.locationQrAction('<?=$args->slug?>')">
                <i class="mdi mdi-qrcode"></i>
                <span class="text-nowrap">Vis QR</span>
            </button>
            <button class="btn-v2 mute-btn" style="padding: 0; width: 38px; height: 38px;" onclick="copyToClipboard('<?=__url('merchant/' . $args->slug)?>')" title="Kopier link">
                <i class="mdi mdi-content-copy font-16"></i>
            </button>
            <?php endif; ?>
            <?php LocationPermissions::__oReadProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->pageBuilder($args->slug))?>" class="btn-v2 action-btn text-nowrap" >
                <i class="fa-regular fa-pen-to-square"></i>
                <span class="text-nowrap">Rediger side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>

    <?php LocationPermissions::__oReadProtectedContent($location, 'metrics'); ?>
    <div class="row flex-align-stretch rg-15 mt-4">
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Total omsætning</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->netSales, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->netSalesLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->netSalesLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->netSalesLflMonth > 0 ? '+' : ''?>
                                <?=round($args->netSalesLflMonth, 2)?>%
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
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Nye kunder</p>
                            <p class="font-22 font-weight-700"><?=$args->newCustomersCount?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->newCustomersLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->newCustomersLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->newCustomersLflMonth > 0 ? '+' : ''?>
                                <?=round($args->newCustomersLflMonth, 2)?>%
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
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Transaktioner i dag</p>
                            <p class="font-22 font-weight-700"><?=$args->ordersTodayCount?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->todayOrdersCountLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->todayOrdersCountLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->todayOrdersCountLflMonth > 0 ? '+' : ''?>
                                <?=round($args->todayOrdersCountLflMonth, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-credit-card-outline color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Kurvestørrelse</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->orderAverage, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->averageLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->averageLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->averageLflMonth > 0 ? '+' : ''?>
                                <?=round($args->averageLflMonth, 2)?>%
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
    </div>
    <?php LocationPermissions::__oEndContent(); ?>



    <?php LocationPermissions::__oReadProtectedContent($location, 'orders'); ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <i class="mdi mdi-cart-outline font-16 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle ordrer</p>
                    </div>

                    <div class="mt-3">
                        <!-- Filters and Search -->
                        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <input type="text" class="form-control-v2 form-field-v2" id="location-orders-search"
                                           placeholder="Søg ordre ID eller kunde..." style="min-width: 200px;">
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="location-orders-filter-status" data-selected="all" style="min-width: 140px;">
                                        <option value="all" selected>Alle statusser</option>
                                        <option value="COMPLETED">Gennemført</option>
                                        <option value="PENDING">Afventer</option>
                                        <option value="DRAFT">Kladde</option>
                                        <option value="CANCELLED">Annulleret</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0 position-relative">
                                    <input type="text" class="form-control-v2 form-field-v2" id="location-orders-daterange"
                                           placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                                    <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                                       id="location-orders-daterange-clear"
                                       style="right: 8px; top: 50%; transform: translateY(-50%);"
                                       title="Ryd datofilter"></i>
                                </div>
                            </div>
                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="location-orders-sort" data-selected="date-DESC" style="min-width: 150px;">
                                        <option value="date-DESC" selected>Nyeste først</option>
                                        <option value="date-ASC">Ældste først</option>
                                        <option value="amount-DESC">Beløb (høj-lav)</option>
                                        <option value="amount-ASC">Beløb (lav-høj)</option>
                                        <option value="status-ASC">Status A-Z</option>
                                        <option value="status-DESC">Status Z-A</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="location-orders-per-page" data-selected="10" style="min-width: 80px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="table-v2" id="location-orders-table">
                                <thead>
                                <tr>
                                    <th>Ordre ID</th>
                                    <th>Dato & Tid</th>
                                    <th>Kunde</th>
                                    <th>Beløb</th>
                                    <th>Betalt</th>
                                    <th>Udestående</th>
                                    <th>Status</th>
                                    <th class="text-right">Handlinger</th>
                                </tr>
                                </thead>
                                <tbody id="location-orders-tbody">
                                <!-- Loading state - will be replaced by JS -->
                                <tr id="location-orders-loading-row">
                                    <td colspan="8" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser ordrer...</p>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No results message -->
                        <div id="location-orders-no-results" class="d-none text-center py-4">
                            <i class="mdi mdi-cart-off font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen ordrer fundet</p>
                        </div>

                        <!-- Pagination -->
                        <div id="location-orders-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                            <div class="text-sm color-gray">
                                Viser <span id="location-orders-showing">0</span> af <span id="location-orders-total">0</span> ordrer
                                (Side <span id="location-orders-current-page">1</span> af <span id="location-orders-total-pages">1</span>)
                            </div>
                            <div class="pagination-nav" id="location-orders-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php LocationPermissions::__oEndContent(); ?>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        LocationActions.init();
    })
</script>
<?php scriptEnd(); ?>



