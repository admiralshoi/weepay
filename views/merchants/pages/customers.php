<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Kunder";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "customers";
    var customersApiUrl = <?=json_encode(Links::$api->orders->customers->list)?>;
</script>


<div class="page-content home">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Kunder</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle kunder</p>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-multiple-outline font-18 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle kunder</p>
                    </div>

                    <div class="mt-3">
                        <!-- Filters and Search -->
                        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <input type="text" class="form-control-v2 form-field-v2" id="customers-search"
                                           placeholder="Søg navn, email eller telefon..." style="min-width: 250px;">
                                </div>
                            </div>
                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="customers-sort" data-selected="total_spent-DESC" style="min-width: 170px;">
                                        <option value="total_spent-DESC" selected>Forbrug (høj-lav)</option>
                                        <option value="total_spent-ASC">Forbrug (lav-høj)</option>
                                        <option value="orders-DESC">Ordrer (høj-lav)</option>
                                        <option value="orders-ASC">Ordrer (lav-høj)</option>
                                        <option value="last_order-DESC">Seneste ordre først</option>
                                        <option value="last_order-ASC">Ældste ordre først</option>
                                        <option value="name-ASC">Navn A-Z</option>
                                        <option value="name-DESC">Navn Z-A</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="customers-per-page" data-selected="10" style="min-width: 80px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div style="overflow-x: auto;">
                            <table class="table-v2" id="customers-table">
                                <thead>
                                <tr>
                                    <th>Kunde</th>
                                    <th>Email</th>
                                    <th>Telefon</th>
                                    <th>Antal Ordrer</th>
                                    <th>Total Forbrug</th>
                                    <th>Første Ordre</th>
                                    <th>Seneste Ordre</th>
                                    <th class="text-right">Handlinger</th>
                                </tr>
                                </thead>
                                <tbody id="customers-tbody">
                                <!-- Loading state - will be replaced by JS -->
                                <tr id="customers-loading-row">
                                    <td colspan="8" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser kunder...</p>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- No results message -->
                        <div id="customers-no-results" class="d-none text-center py-4">
                            <i class="mdi mdi-account-off-outline font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen kunder fundet</p>
                        </div>

                        <!-- Pagination -->
                        <div id="customers-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                            <div class="text-sm color-gray">
                                Viser <span id="customers-showing">0</span> af <span id="customers-total">0</span> kunder
                                (Side <span id="customers-current-page">1</span> af <span id="customers-total-pages">1</span>)
                            </div>
                            <div class="pagination-nav" id="customers-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
