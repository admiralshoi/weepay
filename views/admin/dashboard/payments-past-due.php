<?php
/**
 * Admin Dashboard - Past Due Payments
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "Forfaldne betalinger";
$organisations = $args->organisations ?? new \Database\Collection();
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments-past-due";
    var adminPaymentsApiUrl = <?=json_encode(__url(Links::$api->admin->payments->list))?>;
    var adminOrderDetailUrl = <?=json_encode(__url(Links::$admin->orders) . '/')?>;
    var adminUserDetailUrl = <?=json_encode(__url(Links::$admin->users) . '/')?>;
    var adminPaymentDetailUrl = <?=json_encode(__url(Links::$admin->payments) . '/')?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-col-start">
                <h1 class="mb-0 font-24 font-weight-bold color-danger">Forfaldne betalinger</h1>
                <p class="mb-0 font-14 color-gray"><span id="payments-total-count">0</span> forfaldne betalinger kræver handling</p>
            </div>

            <!-- Filters Card -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <input type="text" class="form-field-v2" id="payments-search" placeholder="Søg betaling, ordre, kunde eller org..." style="min-width: 250px;">
                            <select class="form-select-v2 h-45px" data-search="true" id="payments-filter-org" style="min-width: 180px;">
                                <option value="all" selected>Alle <?=Translate::word("organisationer")?></option>
                                <?php foreach ($organisations->list() as $org): ?>
                                <option value="<?=$org->uid?>"><?=htmlspecialchars($org->name)?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                            <select class="form-select-v2 h-45px" id="payments-sort" style="min-width: 150px;">
                                <option value="due_date-ASC" selected>Forfaldsdato (tidligst)</option>
                                <option value="due_date-DESC">Forfaldsdato (senest)</option>
                                <option value="amount-DESC">Beløb (høj-lav)</option>
                                <option value="amount-ASC">Beløb (lav-høj)</option>
                            </select>
                            <select class="form-select-v2 h-45px" id="payments-per-page" style="min-width: 80px;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="payments-table">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Betaling</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Ordre</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Kunde</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase"><?=Translate::word("Organisation")?></th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Beløb</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Forfaldsdato</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Dage forsinket</th>
                                </tr>
                            </thead>
                            <tbody id="payments-tbody">
                                <!-- Loading state -->
                                <tr id="payments-loading-row">
                                    <td colspan="7" class="text-center py-4">
                                        <div class="flex-col-center flex-align-center">
                                            <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                                <span class="sr-only">Indlæser...</span>
                                            </span>
                                            <p class="color-gray mt-2 mb-0">Indlæser forfaldne betalinger...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- No results message -->
                    <div id="payments-no-results" class="d-none flex-col-center flex-align-center py-5">
                        <i class="mdi mdi-check-circle-outline font-50 color-success"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen forfaldne betalinger</p>
                        <p class="mb-0 font-14 color-gray">Alle betalinger er opdaterede.</p>
                    </div>
                </div>

                <div class="card-footer bg-white border-top" id="payments-pagination-footer">
                    <div class="flex-row-between flex-align-center">
                        <p class="mb-0 font-13 color-gray">
                            Viser <span id="payments-showing-start">0</span> - <span id="payments-showing-end">0</span> af <span id="payments-total">0</span> betalinger
                        </p>
                        <nav>
                            <ul class="pagination mb-0" id="payments-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
