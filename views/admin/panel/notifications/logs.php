<?php
/**
 * Admin Panel - Notification Logs
 * View history of sent notifications
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Notifikationslogs";
$sentLast24h = $args->sentLast24h ?? 0;
$sentLast7d = $args->sentLast7d ?? 0;
$failedLast24h = $args->failedLast24h ?? 0;
$failedLast7d = $args->failedLast7d ?? 0;
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "notifications";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <a href="<?=__url(Links::$admin->panelNotifications)?>" class="font-13 color-gray hover-color-blue">Notifikationer</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Logs</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Notifikationslogs</h1>
                    <p class="mb-0 font-14 color-gray">Historik over sendte notifikationer</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="row-gap: 1rem;">
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Sendt (24t)</p>
                            <p class="mb-0 font-24 font-weight-bold color-green"><?=$sentLast24h?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Sendt (7d)</p>
                            <p class="mb-0 font-24 font-weight-bold color-green"><?=$sentLast7d?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Fejlet (24t)</p>
                            <p class="mb-0 font-24 font-weight-bold color-red"><?=$failedLast24h?></p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="mb-0 font-12 color-gray">Fejlet (7d)</p>
                            <p class="mb-0 font-24 font-weight-bold color-red"><?=$failedLast7d?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: 1rem;">
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <label class="font-12 color-gray mb-0">Status:</label>
                                <select id="logs-filter-status" class="form-select-v2" style="min-width: 120px;">
                                    <option value="all">Alle</option>
                                    <option value="sent">Sendt</option>
                                    <option value="delivered">Leveret</option>
                                    <option value="failed">Fejlet</option>
                                    <option value="bounced">Afvist</option>
                                </select>
                            </div>
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <label class="font-12 color-gray mb-0">Kanal:</label>
                                <select id="logs-filter-channel" class="form-select-v2" style="min-width: 120px;">
                                    <option value="all">Alle</option>
                                    <option value="email">E-mail</option>
                                    <option value="sms">SMS</option>
                                    <option value="bell">Push</option>
                                </select>
                            </div>
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <label class="font-12 color-gray mb-0">Modtager:</label>
                                <input type="text" id="logs-search-recipient" class="form-field-v2" placeholder="Søg..." style="width: 180px;">
                            </div>
                        </div>
                        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                            <label class="font-12 color-gray mb-0">Vis:</label>
                            <select id="logs-per-page" class="form-select-v2" style="min-width: 80px;">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="logs-loading" class="card border-radius-10px">
                <div class="card-body text-center py-5">
                    <div class="spinner-border spinner-border-sm color-blue" role="status"></div>
                    <p class="mb-0 font-13 color-gray mt-2">Indlæser logs...</p>
                </div>
            </div>

            <!-- No Results State -->
            <div id="logs-no-results" class="card border-radius-10px d-none">
                <div class="card-body text-center py-5">
                    <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3 mx-auto">
                        <i class="mdi mdi-history font-28 color-gray"></i>
                    </div>
                    <p class="mb-0 font-16 font-weight-bold color-dark">Ingen logs</p>
                    <p class="mb-0 font-13 color-gray mt-1">Ingen notifikationer fundet med de valgte filtre</p>
                </div>
            </div>

            <!-- Logs Table -->
            <div id="logs-table" class="card border-radius-10px d-none">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="font-12 color-gray font-weight-normal border-0 ps-3">Modtager</th>
                                    <th class="font-12 color-gray font-weight-normal border-0">Kanal</th>
                                    <th class="font-12 color-gray font-weight-normal border-0">Emne</th>
                                    <th class="font-12 color-gray font-weight-normal border-0">Breakpoint</th>
                                    <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                    <th class="font-12 color-gray font-weight-normal border-0">Tidspunkt</th>
                                    <th class="font-12 color-gray font-weight-normal border-0 pe-3 text-end">Handling</th>
                                </tr>
                            </thead>
                            <tbody id="logs-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination Footer -->
            <div id="logs-pagination-footer" class="card border-radius-10px d-none">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: 1rem;">
                        <div class="font-13 color-gray">
                            Viser <span id="logs-showing-start">0</span> - <span id="logs-showing-end">0</span> af <span id="logs-total">0</span>
                        </div>
                        <nav>
                            <ul id="logs-pagination" class="pagination pagination-sm mb-0"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
