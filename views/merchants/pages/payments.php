<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Betalinger";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
    var paymentsApiUrl = <?=json_encode(Links::$api->orders->payments->list)?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Betalinger</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle betalinger</p>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <!-- Type Toggle - Completed vs Upcoming vs Past Due -->
                    <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                        <button class="btn-v2 action-btn payment-type-btn active" data-type="completed">
                            <i class="mdi mdi-cash-check mr-1"></i>
                            Gennemførte
                        </button>
                        <button class="btn-v2 mute-btn payment-type-btn" data-type="upcoming">
                            <i class="mdi mdi-calendar-clock mr-1"></i>
                            Kommende
                        </button>
                        <button class="btn-v2 mute-btn payment-type-btn" data-type="past_due">
                            <i class="mdi mdi-alert-circle-outline mr-1"></i>
                            Forfaldne
                        </button>
                    </div>

                    <div class="mt-3">
                        <!-- Filters and Search -->
                        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <input type="text" class="form-control-v2 form-field-v2" id="payments-search"
                                           placeholder="Søg betaling, ordre eller kunde..." style="min-width: 220px;">
                                </div>
                                <div class="form-group mb-0" id="payments-status-filter-container">
                                    <select class="form-select-v2" id="payments-filter-status" data-selected="all" style="min-width: 140px;">
                                        <option value="all" selected>Alle statusser</option>
                                        <option value="PENDING">Afventer</option>
                                        <option value="SCHEDULED">Planlagt</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0 position-relative">
                                    <input type="text" class="form-control-v2 form-field-v2" id="payments-daterange"
                                           placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                                    <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                                       id="payments-daterange-clear"
                                       style="right: 8px; top: 50%; transform: translateY(-50%);"
                                       title="Ryd datofilter"></i>
                                </div>
                            </div>
                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="payments-sort" data-selected="date-DESC" style="min-width: 150px;">
                                        <option value="date-DESC" selected>Nyeste først</option>
                                        <option value="date-ASC">Ældste først</option>
                                        <option value="amount-DESC">Beløb (høj-lav)</option>
                                        <option value="amount-ASC">Beløb (lav-høj)</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="payments-per-page" data-selected="10" style="min-width: 80px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="table-v2" id="payments-table">
                                <thead>
                                <tr>
                                    <th>Betaling ID</th>
                                    <th>Ordre ID</th>
                                    <th>Kunde</th>
                                    <th>Beløb</th>
                                    <th>Rate</th>
                                    <th id="payments-date-header">Betalt</th>
                                    <th>Forfald</th>
                                    <th id="payments-status-header" class="d-none">Status</th>
                                    <th class="text-right">Handlinger</th>
                                </tr>
                                </thead>
                                <tbody id="payments-tbody">
                                <!-- Loading state - will be replaced by JS -->
                                <tr id="payments-loading-row">
                                    <td colspan="9" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser betalinger...</p>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No results message -->
                        <div id="payments-no-results" class="d-none text-center py-4">
                            <i class="mdi mdi-credit-card-off-outline font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen betalinger fundet</p>
                        </div>

                        <!-- Pagination -->
                        <div id="payments-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                            <div class="text-sm color-gray">
                                Viser <span id="payments-showing">0</span> af <span id="payments-total">0</span> betalinger
                                (Side <span id="payments-current-page">1</span> af <span id="payments-total-pages">1</span>)
                            </div>
                            <div class="pagination-nav" id="payments-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
