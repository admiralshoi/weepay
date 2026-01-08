<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Ordrer";


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
    var ordersApiUrl = <?=json_encode(Links::$api->orders->list)?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>
        <div class="flex-row-end">

        </div>
    </div>


    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Ordrer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle ordrer</p>
        </div>
    </div>

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
                                    <input type="text" class="form-control-v2 form-field-v2" id="orders-search"
                                           placeholder="Søg ordre ID eller kunde..." style="min-width: 200px;">
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="orders-filter-status" data-selected="all" style="min-width: 140px;">
                                        <option value="all" selected>Alle statusser</option>
                                        <option value="COMPLETED">Gennemført</option>
                                        <option value="PENDING">Afventer</option>
                                        <option value="DRAFT">Kladde</option>
                                        <option value="CANCELLED">Annulleret</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0 position-relative">
                                    <input type="text" class="form-control-v2 form-field-v2" id="orders-daterange"
                                           placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                                    <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                                       id="orders-daterange-clear"
                                       style="right: 8px; top: 50%; transform: translateY(-50%);"
                                       title="Ryd datofilter"></i>
                                </div>
                            </div>
                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="orders-sort" data-selected="date-DESC" style="min-width: 150px;">
                                        <option value="date-DESC" selected>Nyeste først</option>
                                        <option value="date-ASC">Ældste først</option>
                                        <option value="amount-DESC">Beløb (høj-lav)</option>
                                        <option value="amount-ASC">Beløb (lav-høj)</option>
                                        <option value="status-ASC">Status A-Z</option>
                                        <option value="status-DESC">Status Z-A</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="orders-per-page" data-selected="10" style="min-width: 80px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="table-v2" id="orders-table">
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
                                <tbody id="orders-tbody">
                                <!-- Loading state - will be replaced by JS -->
                                <tr id="orders-loading-row">
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
                        <div id="orders-no-results" class="d-none text-center py-4">
                            <i class="mdi mdi-cart-off font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen ordrer fundet</p>
                        </div>

                        <!-- Pagination -->
                        <div id="orders-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                            <div class="text-sm color-gray">
                                Viser <span id="orders-showing">0</span> af <span id="orders-total">0</span> ordrer
                                (Side <span id="orders-current-page">1</span> af <span id="orders-total-pages">1</span>)
                            </div>
                            <div class="pagination-nav" id="orders-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
