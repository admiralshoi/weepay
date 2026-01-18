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
    var consumerPaymentsApiUrl = <?=json_encode(Links::$api->consumer->payments)?>;
    var consumerHasPastDue = <?=json_encode($args->hasPastDue ?? false)?>;
</script>


<div class="page-content">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Betalinger</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle dine betalinger</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <!-- Type Toggle - Completed vs Upcoming vs Past Due -->
            <div class="flex-row-start flex-align-center flex-wrap mb-3" style="gap: .5rem;">
                <button class="btn-v2 action-btn consumer-payment-type-btn active" data-type="completed">
                    <i class="mdi mdi-cash-check mr-1"></i>
                    Kvitteringer
                </button>
                <button class="btn-v2 mute-btn consumer-payment-type-btn" data-type="upcoming">
                    <i class="mdi mdi-calendar-clock mr-1"></i>
                    Kommende
                </button>
                <button class="btn-v2 mute-btn consumer-payment-type-btn" data-type="past_due">
                    <i class="mdi mdi-alert-circle-outline mr-1"></i>
                    Udestående
                </button>
            </div>

            <div class="mt-3">
                <!-- Filters and Search -->
                <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control-v2 form-field-v2" id="consumer-payments-search"
                                   placeholder="Søg betaling eller ordre..." style="min-width: 200px;">
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-payments-filter-status" data-selected="all" style="min-width: 140px;">
                                <option value="all" selected>Alle statusser</option>
                                <option value="COMPLETED">Gennemført</option>
                                <option value="PENDING">Afventer</option>
                                <option value="SCHEDULED">Planlagt</option>
                                <option value="PAST_DUE">Forsinket</option>
                                <option value="REFUNDED">Refunderet</option>
                                <option value="VOIDED">Ophævet</option>
                            </select>
                        </div>
                        <div class="form-group mb-0 position-relative">
                            <input type="text" class="form-control-v2 form-field-v2" id="consumer-payments-daterange"
                                   placeholder="Vælg datointerval" style="min-width: 220px; padding-right: 30px;" readonly>
                            <i class="mdi mdi-close-circle font-16 color-red position-absolute cursor-pointer d-none"
                               id="consumer-payments-daterange-clear"
                               style="right: 8px; top: 50%; transform: translateY(-50%);"
                               title="Ryd datofilter"></i>
                        </div>
                    </div>
                    <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-payments-sort" data-selected="date-ASC" style="min-width: 150px;">
                                <option value="date-ASC" selected>Ældste først</option>
                                <option value="date-DESC">Nyeste først</option>
                                <option value="amount-DESC">Beløb (høj-lav)</option>
                                <option value="amount-ASC">Beløb (lav-høj)</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-select-v2" id="consumer-payments-per-page" data-selected="10" style="min-width: 80px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table-v2" id="consumer-payments-table">
                        <thead>
                        <tr>
                            <th>Betalings ID</th>
                            <th>Ordre</th>
                            <th>Butik</th>
                            <th>Beløb</th>
                            <th id="consumer-payments-date-header">Betalt</th>
                            <th id="consumer-payments-status-header">Status</th>
                        </tr>
                        </thead>
                        <tbody id="consumer-payments-tbody">
                        <!-- Loading state - will be replaced by JS -->
                        <tr id="consumer-payments-loading-row">
                            <td colspan="6" class="text-center py-4">
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
                <div id="consumer-payments-no-results" class="d-none text-center py-4">
                    <i class="mdi mdi-credit-card-off-outline font-40 color-gray"></i>
                    <p class="color-gray mt-2 mb-0">Ingen betalinger fundet</p>
                </div>

                <!-- Past due warning -->
                <div id="consumer-payments-past-due-warning" class="d-none">
                    <div class="alert alert-danger mt-3" role="alert">
                        <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                            <i class="mdi mdi-alert-circle-outline font-20"></i>
                            <div>
                                <p class="mb-0 font-14 font-weight-bold">Du har forsinkede betalinger</p>
                                <p class="mb-0 font-12">Forsinkede betalinger kan påvirke din mulighed for at bruge BNPL fremadrettet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="consumer-payments-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                    <div class="text-sm color-gray">
                        Viser <span id="consumer-payments-showing">0</span> af <span id="consumer-payments-total">0</span> betalinger
                        (Side <span id="consumer-payments-current-page">1</span> af <span id="consumer-payments-total-pages">1</span>)
                    </div>
                    <div class="pagination-nav" id="consumer-payments-pagination"></div>
                </div>
            </div>
        </div>
    </div>

</div>
