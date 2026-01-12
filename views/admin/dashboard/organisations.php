<?php
/**
 * Admin Dashboard - Organisations
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = Translate::word("Organisationer");
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "organisations";
    var adminOrganisationsApiUrl = <?=json_encode(__url(Links::$api->admin->organisations->list))?>;
    var adminOrgDetailUrl = <?=json_encode(__url(Links::$admin->organisations) . '/')?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold"><?=ucfirst(Translate::word("Organisationer"))?></h1>
                    <p class="mb-0 font-14 color-gray"><span id="organisations-total-count">0</span> <?=Translate::word("organisations")?> i alt</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <input type="text" class="form-field-v2" id="organisations-search" placeholder="Søg efter navn, email, CVR..." style="min-width: 250px;">
                            <select class="form-select-v2" id="organisations-filter-status" style="min-width: 130px;">
                                <option value="all" selected>Alle status</option>
                                <option value="ACTIVE">Aktiv</option>
                                <option value="INACTIVE">Inaktiv</option>
                                <option value="DRAFT">Kladde</option>
                                <option value="DELETED">Slettet</option>
                            </select>
                        </div>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                            <select class="form-select-v2" id="organisations-sort" style="min-width: 150px;">
                                <option value="created_at-DESC" selected>Nyeste først</option>
                                <option value="created_at-ASC">Ældste først</option>
                                <option value="name-ASC">Navn (A-Z)</option>
                                <option value="name-DESC">Navn (Z-A)</option>
                            </select>
                            <select class="form-select-v2" id="organisations-per-page" style="min-width: 80px;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organisations Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="organisations-table">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase"><?=Translate::word("Organisation")?></th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">CVR / Firma</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Kontakt</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Status</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Oprettet</th>
                                </tr>
                            </thead>
                            <tbody id="organisations-tbody">
                                <!-- Loading state -->
                                <tr id="organisations-loading-row">
                                    <td colspan="5" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser organisationer...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No results message -->
                    <div id="organisations-no-results" class="d-none flex-col-center flex-align-center py-5">
                        <i class="mdi mdi-domain font-50 color-gray"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen organisationer fundet</p>
                    </div>
                </div>

                <div class="card-footer bg-white border-top" id="organisations-pagination-footer">
                    <div class="flex-row-between flex-align-center">
                        <p class="mb-0 font-13 color-gray">
                            Viser <span id="organisations-showing-start">0</span> - <span id="organisations-showing-end">0</span> af <span id="organisations-total">0</span> organisationer
                        </p>
                        <nav>
                            <ul class="pagination mb-0" id="organisations-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
