<?php
/**
 * Admin Panel - Fees Configuration
 * Manage platform default fee and organisation-specific overrides
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "Gebyrer";
$defaultFee = $args->defaultFee ?? 5.95;
$cardFee = $args->cardFee ?? 0.39;
$paymentProviderFee = $args->paymentProviderFee ?? 0.39;
$minOrgFee = $args->minOrgFee ?? 0.78;
$orgFees = $args->orgFees ?? new \Database\Collection();

?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "fees";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Gebyrer</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Gebyrer</h1>
                    <p class="mb-0 font-14 color-gray">Administrer platform og organisationsgebyrer</p>
                </div>
            </div>

            <!-- Platform Fees Card -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                        <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-percent color-white font-20"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-16 font-weight-bold">Platform Gebyrer</p>
                            <p class="mb-0 font-12 color-gray">Gebyrer der indgår i det samlede platformgebyr</p>
                        </div>
                    </div>

                    <div class="row" style="row-gap: 1rem;">
                        <!-- Total Platform Fee -->
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light-gray border-radius-8px h-100">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Samlet platformgebyr</p>
                                    <p class="mb-0 font-24 font-weight-bold color-blue"><?=number_format($defaultFee, 2, ',', '.')?> %</p>
                                    <p class="mb-0 font-11 color-gray mt-1">Standard for alle organisationer</p>
                                </div>
                                <button class="btn-v2 action-btn btn-sm mt-2" onclick="editDefaultFee(<?=$defaultFee?>)">
                                    <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                </button>
                            </div>
                        </div>

                        <!-- Card Fee -->
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light-gray border-radius-8px h-100">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Kortgebyr (Visa/MC)</p>
                                    <p class="mb-0 font-24 font-weight-bold color-pee-yellow"><?=number_format($cardFee, 2, ',', '.')?> %</p>
                                    <p class="mb-0 font-11 color-gray mt-1">Forventet kortudsteders gebyr</p>
                                </div>
                                <button class="btn-v2 action-btn btn-sm mt-2" onclick="editCardFee(<?=$cardFee?>)">
                                    <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                </button>
                            </div>
                        </div>

                        <!-- Payment Provider Fee -->
                        <div class="col-12 col-md-4">
                            <div class="p-3 bg-light-gray border-radius-8px h-100">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Betalingsudbyder gebyr</p>
                                    <p class="mb-0 font-24 font-weight-bold color-purple"><?=number_format($paymentProviderFee, 2, ',', '.')?> %</p>
                                    <p class="mb-0 font-11 color-gray mt-1">Viva/stripe osv.</p>
                                </div>
                                <button class="btn-v2 action-btn btn-sm mt-2" onclick="editPaymentProviderFee(<?=$paymentProviderFee?>)">
                                    <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Minimum Fee Info -->
                    <div class="flex-row-start flex-align-center mt-3 p-2 bg-lightest-blue border-radius-8px">
                        <i class="mdi mdi-information-outline font-16 color-blue mr-2"></i>
                        <p class="mb-0 font-12 color-dark">
                            Minimum organisationsgebyr: <strong><?=number_format($minOrgFee, 2, ',', '.')?> %</strong> (kortgebyr + betalingsudbyder gebyr)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Organisation Overrides -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-3">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-green border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-domain color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold"><?=ucfirst(Translate::word("Organisationer"))?> med tilpasset gebyr</p>
                                <p class="mb-0 font-12 color-gray">Specifikke gebyrer der overskriver standardgebyret</p>
                            </div>
                        </div>
                        <button class="btn-v2 action-btn" onclick="addOrgFee()">
                            <i class="mdi mdi-plus mr-1"></i> Tilføj gebyr
                        </button>
                    </div>

                    <?php if($orgFees->empty()): ?>
                        <div class="flex-col-center flex-align-center py-4">
                            <i class="mdi mdi-check-circle-outline font-40 color-gray"></i>
                            <p class="mb-0 font-14 color-gray mt-2">Ingen tilpassede gebyrer</p>
                            <p class="mb-0 font-12 color-gray">Alle organisationer bruger standardgebyret</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 plainDataTable" data-pagination-limit="10" data-sorting-col="0" data-sorting-order="asc">
                                <thead>
                                    <tr>
                                        <th class="font-12 font-weight-medium color-gray" style="display:none;">Sort</th>
                                        <th class="font-12 font-weight-medium color-gray"><?=ucfirst(Translate::word("Organisation"))?></th>
                                        <th class="font-12 font-weight-medium color-gray">Gebyr</th>
                                        <th class="font-12 font-weight-medium color-gray">Startdato</th>
                                        <th class="font-12 font-weight-medium color-gray">Slutdato</th>
                                        <th class="font-12 font-weight-medium color-gray">Årsag</th>
                                        <th class="font-12 font-weight-medium color-gray">Oprettet af</th>
                                        <th class="font-12 font-weight-medium color-gray text-right">Handlinger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $now = time();
                                    foreach ($orgFees->list() as $fee):
                                        // Determine status
                                        $isEnded = $fee->end_time && $fee->end_time < $now;
                                        $isUpcoming = $fee->start_time > $now;
                                        $isActive = !$isEnded && !$isUpcoming;

                                        if ($isEnded) {
                                            $statusClass = 'bg-light-gray';
                                            $statusBadge = '<span class="mute-box font-10">Afsluttet</span>';
                                            $sortOrder = 3;
                                        } elseif ($isUpcoming) {
                                            $statusClass = '';
                                            $statusBadge = '<span class="warning-box font-10">Kommende</span>';
                                            $sortOrder = 2;
                                        } else {
                                            $statusClass = '';
                                            $statusBadge = '<span class="success-box font-10">Aktiv</span>';
                                            $sortOrder = 1;
                                        }
                                    ?>
                                    <tr class="<?=$statusClass?>">
                                        <td style="display:none;"><?=$sortOrder?></td>
                                        <td class="font-13">
                                            <div class="flex-col-start" style="gap: 4px;">
                                                <?php if($fee->organisation): ?>
                                                    <a href="<?=__url(Links::$admin->dashboardOrganisationDetail($fee->organisation->uid ?? ''))?>" class="color-dark hover-color-blue">
                                                        <?=htmlspecialchars($fee->organisation->name ?? 'Ukendt')?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="color-gray">Ukendt</span>
                                                <?php endif; ?>
                                                <?=$statusBadge?>
                                            </div>
                                        </td>
                                        <td class="font-13 font-weight-bold color-blue"><?=number_format($fee->fee, 2, ',', '.')?> %</td>
                                        <td class="font-13"><?=date('d/m/Y', $fee->start_time)?></td>
                                        <td class="font-13"><?=$fee->end_time ? date('d/m/Y', $fee->end_time) : '<span class="info-box font-11">Ingen</span>'?></td>
                                        <td class="font-13"><?=$fee->reason ? htmlspecialchars($fee->reason) : '<span class="color-gray">-</span>'?></td>
                                        <td class="font-13">
                                            <?php if($fee->created_by): ?>
                                                <?=htmlspecialchars($fee->created_by->full_name ?? 'Admin')?>
                                            <?php else: ?>
                                                <span class="color-gray">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <button class="btn-v2 trans-btn font-12 mr-1 edit-org-fee-btn"
                                                    data-uid="<?=htmlspecialchars($fee->uid)?>"
                                                    data-fee="<?=$fee->fee?>"
                                                    data-org="<?=htmlspecialchars($fee->organisation->uid ?? '')?>"
                                                    data-start="<?=$fee->start_time?>"
                                                    data-end="<?=$fee->end_time ?? ''?>"
                                                    data-reason="<?=htmlspecialchars($fee->reason ?? '')?>">
                                                <i class="mdi mdi-pencil-outline"></i>
                                            </button>
                                            <button class="btn-v2 trans-btn font-12 color-danger" onclick="deleteOrgFee('<?=htmlspecialchars($fee->uid)?>')">
                                                <i class="mdi mdi-delete-outline"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card border-radius-10px bg-lightest-blue">
                <div class="card-body">
                    <div class="flex-row-start" style="gap: .75rem;">
                        <i class="mdi mdi-information-outline font-20 color-blue"></i>
                        <div class="flex-col-start">
                            <p class="mb-0 font-14 font-weight-medium color-dark">Om gebyrer</p>
                            <p class="mb-0 font-13 color-gray mt-1">
                                Gebyret fratrækkes fra hver transaktion som WeePay's ISV-andel. Hvis en organisation har et tilpasset gebyr, bruges dette i stedet for standardgebyret.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Default Fee Modal -->
<div class="modal fade" id="editDefaultFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger standardgebyr</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Samlet platformgebyr (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-field-v2" id="defaultFeeInput" placeholder="5.95">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveDefaultFee()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Card Fee Modal -->
<div class="modal fade" id="editCardFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger kortgebyr</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Kortgebyr - Visa/MC (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-field-v2" id="cardFeeInput" placeholder="0.39">
                    <p class="mb-0 font-11 color-gray mt-2">Dette gebyr bruges til at beregne minimum organisationsgebyr.</p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveCardFee()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Provider Fee Modal -->
<div class="modal fade" id="editPaymentProviderFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger betalingsudbyder gebyr</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Betalingsudbyder gebyr (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-field-v2" id="paymentProviderFeeInput" placeholder="0.39">
                    <p class="mb-0 font-11 color-gray mt-2">Dette gebyr bruges til at beregne minimum organisationsgebyr.</p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="savePaymentProviderFee()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Org Fee Modal -->
<div class="modal fade" id="orgFeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold" id="orgFeeModalTitle">Tilføj organisationsgebyr</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start" style="gap: 1rem;">
                    <input type="hidden" id="orgFeeUid">

                    <!-- Edit mode notice -->
                    <div class="flex-row-start flex-align-center p-2 bg-lightest-blue border-radius-8px" id="orgFeeEditNotice" style="display: none;">
                        <i class="mdi mdi-information-outline font-14 color-blue mr-2"></i>
                        <p class="mb-0 font-11 color-dark">Gebyr og datoer kan ikke ændres. Opret et nyt gebyr i stedet.</p>
                    </div>

                    <div class="flex-col-start w-100" id="orgSelectWrapper">
                        <label class="font-12 color-gray mb-1"><?=ucfirst(Translate::word("Organisationer"))?></label>
                        <select class="form-select-v2 w-100" data-search="true" multiple id="orgFeeOrgSelect">
                        </select>
                    </div>
                    <div class="flex-col-start">
                        <label class="font-12 color-gray mb-1">Gebyr (%)</label>
                        <input type="number" step="0.01" min="<?=$minOrgFee?>" max="100" class="form-field-v2" id="orgFeeInput" placeholder="5.95">
                        <p class="mb-0 font-11 color-gray mt-1">Minimum: <?=number_format($minOrgFee, 2, ',', '.')?> % (kortgebyr + betalingsudbyder gebyr)</p>
                    </div>
                    <div class="row" style="row-gap: 1rem;">
                        <div class="col-12 col-md-6">
                            <div class="flex-col-start">
                                <label class="font-12 color-gray mb-1">Startdato</label>
                                <input type="date" class="form-field-v2" id="orgFeeStartDate" min="<?=date('Y-m-d')?>">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="flex-col-start">
                                <label class="font-12 color-gray mb-1">Slutdato (valgfri)</label>
                                <input type="date" class="form-field-v2" id="orgFeeEndDate" min="<?=date('Y-m-d')?>">
                            </div>
                        </div>
                    </div>
                    <div class="flex-col-start">
                        <label class="font-12 color-gray mb-1">Årsag (valgfri)</label>
                        <textarea class="form-field-v2" id="orgFeeReason" rows="2" placeholder="Angiv evt. årsag til det tilpassede gebyr"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveOrgFee()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    var minOrgFee = <?=$minOrgFee?>;
    var currentCardFee = <?=$cardFee?>;
    var currentPaymentProviderFee = <?=$paymentProviderFee?>;

    $(document).ready(function() {
        initPanelFees();
    });
</script>
<?php scriptEnd(); ?>
