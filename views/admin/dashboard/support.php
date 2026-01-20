<?php
/**
 * Admin Dashboard - Support
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Support";
$stats = $args->stats ?? (object)['openTickets' => 0, 'closedTickets' => 0, 'totalTickets' => 0];
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "support";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Support</h1>
                    <p class="mb-0 font-14 color-gray">Håndter support henvendelser</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row flex-align-stretch rg-15">
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100 cursor-pointer stat-card" data-filter="open">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Åbne sager</p>
                                    <p class="mb-0 font-18 font-weight-bold" id="statOpenTickets"><?=number_format($stats->openTickets)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-ticket-outline color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100 cursor-pointer stat-card" data-filter="closed">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Lukkede sager</p>
                                    <p class="mb-0 font-18 font-weight-bold" id="statClosedTickets"><?=number_format($stats->closedTickets)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-check-circle color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4 d-flex">
                    <div class="card border-radius-10px w-100 cursor-pointer stat-card" data-filter="all">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Totalt antal</p>
                                    <p class="mb-0 font-18 font-weight-bold" id="statTotalTickets"><?=number_format($stats->totalTickets)?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-ticket-confirmation color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tickets Table -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <!-- Filters -->
                    <div class="flex-row-between flex-align-center flex-wrap mb-4" style="gap: 1rem;">
                        <p class="mb-0 font-16 font-weight-bold">Support sager</p>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <select class="form-select-v2 h-40px" id="filterStatus" style="min-width: 130px;">
                                    <option value="all">Alle status</option>
                                    <option value="open" selected>Åben</option>
                                    <option value="closed">Lukket</option>
                                </select>
                                <select class="form-select-v2 h-40px" id="filterType" style="min-width: 130px;">
                                    <option value="all" selected>Alle typer</option>
                                    <option value="consumer">Forbruger</option>
                                    <option value="merchant">Forhandler</option>
                                </select>
                            </div>
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <input type="text" class="form-field-v2 h-40px" id="searchInput" placeholder="Søg..." style="width: 200px;">
                                <button type="button" class="btn-v2 action-btn h-40px" onclick="loadTickets()">
                                    <i class="mdi mdi-magnify"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div id="ticketsLoading" class="flex-col-center flex-align-center py-5">
                        <div class="spinner-border color-blue" role="status">
                            <span class="sr-only">Indlæser...</span>
                        </div>
                        <p class="mb-0 font-14 color-gray mt-2">Indlæser sager...</p>
                    </div>

                    <!-- Empty state -->
                    <div id="ticketsEmpty" class="flex-col-center flex-align-center py-5" style="display: none;">
                        <i class="mdi mdi-ticket-outline font-50 color-gray"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen support sager fundet</p>
                        <p class="mb-0 font-14 color-gray">Prøv at justere dine filtre.</p>
                    </div>

                    <!-- Table -->
                    <div id="ticketsTableContainer" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Emne</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Bruger</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Type</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Kategori</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Status</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase">Oprettet</th>
                                        <th class="font-12 font-weight-medium color-gray text-uppercase text-right">Handling</th>
                                    </tr>
                                </thead>
                                <tbody id="ticketsTableBody">
                                    <!-- Rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="flex-row-between flex-align-center mt-3 pt-3 border-top" id="paginationContainer">
                            <p class="mb-0 font-13 color-gray" id="paginationInfo">Viser 0-0 af 0</p>
                            <div class="flex-row-end flex-align-center" style="gap: .5rem;" id="paginationButtons">
                                <!-- Pagination buttons will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <p class="font-16 font-weight-bold mb-3">Hurtig hjælp</p>
                    <div class="row rg-15">
                        <div class="col-12 col-lg-6">
                            <div class="p-3 bg-light-gray border-radius-8px">
                                <div class="flex-row-start flex-align-start" style="gap: .75rem;">
                                    <div class="square-40 bg-blue border-radius-8px flex-row-center-center flex-shrink-0">
                                        <i class="mdi mdi-email-outline color-white font-20"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-14 font-weight-medium">Email support</p>
                                        <p class="mb-0 font-13 color-gray">Kontakt vores support team via email</p>
                                        <a href="mailto:support@wee-pay.dk" class="font-13 color-blue">support@wee-pay.dk</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="p-3 bg-light-gray border-radius-8px">
                                <div class="flex-row-start flex-align-start" style="gap: .75rem;">
                                    <div class="square-40 bg-green border-radius-8px flex-row-center-center flex-shrink-0">
                                        <i class="mdi mdi-phone-outline color-white font-20"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-14 font-weight-medium">Telefon support</p>
                                        <p class="mb-0 font-13 color-gray">Ring til os i åbningstiden</p>
                                        <span class="font-13 color-dark">+45 12 34 56 78</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
