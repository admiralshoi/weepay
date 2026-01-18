<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Ordre";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
    var consumerOrdersApiUrl = <?=json_encode(Links::$api->consumer->orders)?>;
    var consumerHasNotFullyPaid = <?=json_encode($args->hasNotFullyPaid ?? false)?>;
</script>

<div class="page-content">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Mine ordrer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle dine køb</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <!-- Type Toggle - Fully Paid vs Not Fully Paid -->
            <div class="flex-row-start flex-align-center flex-wrap mb-3" style="gap: .5rem;">
                <button class="btn-v2 action-btn consumer-order-type-btn active" data-type="fully_paid">
                    <i class="mdi mdi-check-circle-outline mr-1"></i>
                    Fuldt betalt
                </button>
                <button class="btn-v2 mute-btn consumer-order-type-btn" data-type="not_fully_paid">
                    <i class="mdi mdi-clock-outline mr-1"></i>
                    Ikke fuldt betalt
                </button>
            </div>

            <div class="mt-3">
                <!-- Filters and Search -->
                <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control-v2 form-field-v2" id="consumer-orders-search"
                                   placeholder="Søg ordre eller butik..." style="min-width: 200px;">
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-orders-filter-status" data-selected="all" style="min-width: 140px;">
                                <option value="all" selected>Alle statusser</option>
                                <option value="COMPLETED">Gennemført</option>
                                <option value="PENDING">Afventer</option>
                                <option value="CANCELLED">Annulleret</option>
                                <option value="REFUNDED">Refunderet</option>
                                <option value="VOIDED">Ophævet</option>
                            </select>
                        </div>
                        <div class="form-group mb-0 position-relative">
                            <input type="text" class="form-control-v2 form-field-v2" id="consumer-orders-daterange"
                                   placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                            <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                               id="consumer-orders-daterange-clear"
                               style="right: 8px; top: 50%; transform: translateY(-50%);"
                               title="Ryd datofilter"></i>
                        </div>
                    </div>
                    <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-orders-sort" data-selected="date-DESC" style="min-width: 150px;">
                                <option value="date-DESC" selected>Nyeste først</option>
                                <option value="date-ASC">Ældste først</option>
                                <option value="amount-DESC">Beløb (høj-lav)</option>
                                <option value="amount-ASC">Beløb (lav-høj)</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-orders-per-page" data-selected="10" style="min-width: 80px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table-v2" id="consumer-orders-table">
                        <thead>
                        <tr>
                            <th>Ordre ID</th>
                            <th>Dato</th>
                            <th>Beløb</th>
                            <th>Butik</th>
                            <th>Betalingsplan</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody id="consumer-orders-tbody">
                        <!-- Loading state - will be replaced by JS -->
                        <tr id="consumer-orders-loading-row">
                            <td colspan="6" class="text-center py-4">
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
                <div id="consumer-orders-no-results" class="d-none text-center py-4">
                    <i class="mdi mdi-cart-outline font-40 color-gray"></i>
                    <p class="color-gray mt-2 mb-0">Ingen ordrer fundet</p>
                </div>

                <!-- Pagination -->
                <div id="consumer-orders-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                    <div class="text-sm color-gray">
                        Viser <span id="consumer-orders-showing">0</span> af <span id="consumer-orders-total">0</span> ordrer
                        (Side <span id="consumer-orders-current-page">1</span> af <span id="consumer-orders-total-pages">1</span>)
                    </div>
                    <div class="pagination-nav" id="consumer-orders-pagination"></div>
                </div>
            </div>
        </div>
    </div>

</div>
