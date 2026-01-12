<?php
/**
 * Admin Dashboard - Orders
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "Ordrer";
$organisations = $args->organisations ?? new \Database\Collection();
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
    var adminOrdersApiUrl = <?=json_encode(__url(Links::$api->admin->orders->list))?>;
    var adminOrderDetailUrl = <?=json_encode(__url(Links::$admin->orders) . '/')?>;
    var adminUserDetailUrl = <?=json_encode(__url(Links::$admin->users) . '/')?>;
    var adminOrgDetailUrl = <?=json_encode(__url(Links::$admin->organisations) . '/')?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Ordrer</h1>
                    <p class="mb-0 font-14 color-gray"><span id="orders-total-count">0</span> ordrer i alt</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card border-radius-10px">
                <div class="card-body py-3">
                    <div class="flex-row-between flex-align-center flex-wrap" style="gap: .75rem;">
                        <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <input type="text" class="form-field-v2" id="orders-search" placeholder="Søg efter ordre ID, caption eller org..." style="min-width: 250px;">
                            <select class="form-select-v2" id="orders-filter-org" style="min-width: 180px;">
                                <option value="all" selected>Alle <?=Translate::word("organisationer")?></option>
                                <?php foreach ($organisations->list() as $org): ?>
                                <option value="<?=$org->uid?>"><?=htmlspecialchars($org->name)?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-select-v2" id="orders-filter-status" style="min-width: 130px;">
                                <option value="all" selected>Alle status</option>
                                <option value="DRAFT">Kladde</option>
                                <option value="PENDING">Afventer</option>
                                <option value="COMPLETED">Gennemført</option>
                                <option value="CANCELLED">Annulleret</option>
                                <option value="EXPIRED">Udløbet</option>
                            </select>
                        </div>
                        <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                            <select class="form-select-v2" id="orders-sort" style="min-width: 150px;">
                                <option value="created_at-DESC" selected>Nyeste først</option>
                                <option value="created_at-ASC">Ældste først</option>
                                <option value="amount-DESC">Beløb (høj-lav)</option>
                                <option value="amount-ASC">Beløb (lav-høj)</option>
                            </select>
                            <select class="form-select-v2" id="orders-per-page" style="min-width: 80px;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="card border-radius-10px">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="orders-table">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Ordre</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Kunde</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase"><?=Translate::word("Organisation")?></th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Beløb</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Status</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Oprettet</th>
                                </tr>
                            </thead>
                            <tbody id="orders-tbody">
                                <!-- Loading state -->
                                <tr id="orders-loading-row">
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
                    <div id="orders-no-results" class="d-none flex-col-center flex-align-center py-5">
                        <i class="mdi mdi-cart-off font-50 color-gray"></i>
                        <p class="mb-0 font-16 color-gray mt-2">Ingen ordrer fundet</p>
                    </div>
                </div>

                <div class="card-footer bg-white border-top" id="orders-pagination-footer">
                    <div class="flex-row-between flex-align-center">
                        <p class="mb-0 font-13 color-gray">
                            Viser <span id="orders-showing-start">0</span> - <span id="orders-showing-end">0</span> af <span id="orders-total">0</span> ordrer
                        </p>
                        <nav>
                            <ul class="pagination mb-0" id="orders-pagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
