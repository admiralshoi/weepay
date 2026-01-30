<?php
/**
 * Admin Panel - Contact Forms
 * Lists and manages contact form submissions from the landing page
 */
use classes\enumerations\Links;

$pageTitle = "Kontaktformularer";
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "contact-forms";
    var adminContactFormsListUrl = <?=json_encode(__url(Links::$api->admin->contactForms->list))?>;
    var adminContactFormsDeleteUrl = <?=json_encode(__url(Links::$api->admin->contactForms->delete))?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Kontaktformularer</h1>
                    <p class="mb-0 font-14 color-gray"><span id="contact-forms-total-count">0</span> indsendelser i alt</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <input type="text" class="form-field-v2" id="contact-forms-search" placeholder="Søg efter navn, email eller emne..." style="min-width: 280px;">
                        </div>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                            <select class="form-select-v2" id="contact-forms-sort" style="min-width: 150px;">
                                <option value="created_at-DESC" selected>Nyeste først</option>
                                <option value="created_at-ASC">Ældste først</option>
                                <option value="name-ASC">Navn (A-Z)</option>
                                <option value="name-DESC">Navn (Z-A)</option>
                            </select>
                            <select class="form-select-v2" id="contact-forms-per-page" style="min-width: 80px;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Forms Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="contact-forms-table">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Navn</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Emne</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase" style="width: 140px;">Dato</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase" style="width: 100px;">Handlinger</th>
                                </tr>
                            </thead>
                            <tbody id="contact-forms-tbody">
                                <!-- Loading state -->
                                <tr id="contact-forms-loading-row">
                                    <td colspan="4" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser kontaktformularer...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No results message -->
                    <div id="contact-forms-no-results" class="d-none flex-col-center flex-align-center py-5">
                        <i class="mdi mdi-email-outline font-50 color-gray"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen kontaktformularer</p>
                        <p class="mb-0 font-13 color-gray">Der er ingen indsendte kontaktformularer endnu</p>
                    </div>
                </div>

                <div class="card-footer bg-white border-top" id="contact-forms-pagination-footer">
                    <div class="flex-row-between flex-align-center">
                        <p class="mb-0 font-13 color-gray">
                            Viser <span id="contact-forms-showing-start">0</span> - <span id="contact-forms-showing-end">0</span> af <span id="contact-forms-total">0</span> indsendelser
                        </p>
                        <nav>
                            <ul class="pagination mb-0" id="contact-forms-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
