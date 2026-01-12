<?php
/**
 * Admin Panel - Payment Plans
 * Configure BNPL, direct, and pushed payment options
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "Betalingsplaner";
$paymentPlans = $args->paymentPlans ?? [];
$maxBnplAmount = $args->maxBnplAmount ?? 1000;
$bnplInstallmentMaxDuration = $args->bnplInstallmentMaxDuration ?? 90;

// Convert to array if object
$paymentPlans = (array)$paymentPlans;
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payment-plans";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Betalingsplaner</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Betalingsplaner</h1>
                    <p class="mb-0 font-14 color-gray">Konfigurer BNPL, direkte og udskudt betaling</p>
                </div>
            </div>

            <!-- BNPL Settings -->
            <div class="row rg-15">
                <!-- Max BNPL Amount -->
                <div class="col-12 col-md-6">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-40 bg-pee-yellow border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-cash-multiple color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold">Maksimalt BNPL Beløb</p>
                                    <p class="mb-0 font-12 color-gray">Det maksimale beløb en forbruger kan købe på BNPL</p>
                                </div>
                            </div>

                            <div class="p-3 bg-light-gray border-radius-8px">
                                <div class="flex-row-between flex-align-center">
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-12 color-gray">Nuværende grænse</p>
                                        <p class="mb-0 font-24 font-weight-bold color-pee-yellow"><?=number_format($maxBnplAmount, 2, ',', '.')?> kr</p>
                                    </div>
                                    <button class="btn-v2 action-btn" onclick="editMaxBnpl(<?=$maxBnplAmount?>)">
                                        <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Max BNPL Duration -->
                <div class="col-12 col-md-6">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                                <div class="square-40 bg-purple border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-calendar-range color-white font-20"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-16 font-weight-bold">Maksimal BNPL Varighed</p>
                                    <p class="mb-0 font-12 color-gray">Maksimalt antal dage for afdragsordninger</p>
                                </div>
                            </div>

                            <div class="p-3 bg-light-gray border-radius-8px">
                                <div class="flex-row-between flex-align-center">
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-12 color-gray">Nuværende grænse</p>
                                        <p class="mb-0 font-24 font-weight-bold color-purple"><?=$bnplInstallmentMaxDuration?> dage</p>
                                    </div>
                                    <button class="btn-v2 action-btn" onclick="editMaxDuration(<?=$bnplInstallmentMaxDuration?>)">
                                        <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Plans -->
            <div class="row rg-15">
                <?php
                $planIcons = [
                    'direct' => ['icon' => 'mdi-cash', 'color' => 'bg-green'],
                    'pushed' => ['icon' => 'mdi-calendar-clock', 'color' => 'bg-blue'],
                    'installments' => ['icon' => 'mdi-view-split-vertical', 'color' => 'bg-purple'],
                ];
                foreach ($paymentPlans as $planKey => $plan):
                    $plan = (array)$plan;
                    $iconInfo = $planIcons[$planKey] ?? ['icon' => 'mdi-credit-card', 'color' => 'bg-gray'];
                ?>
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-start mb-3">
                                <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                    <div class="square-40 <?=$iconInfo['color']?> border-radius-8px flex-row-center-center">
                                        <i class="mdi <?=$iconInfo['icon']?> color-white font-20"></i>
                                    </div>
                                    <div class="flex-col-start">
                                        <p class="mb-0 font-16 font-weight-bold"><?=htmlspecialchars($plan['title'] ?? ucfirst($planKey))?></p>
                                        <p class="mb-0 font-12 color-gray"><?=htmlspecialchars($plan['caption'] ?? '')?></p>
                                    </div>
                                </div>
                                <?php if($plan['enabled'] ?? false): ?>
                                    <span class="success-box font-11">Aktiv</span>
                                <?php else: ?>
                                    <span class="mute-box font-11">Inaktiv</span>
                                <?php endif; ?>
                            </div>

                            <div class="flex-col-start" style="gap: .5rem;">
                                <div class="flex-row-between flex-align-center py-2 border-bottom-card">
                                    <p class="mb-0 font-13 color-gray">Rater</p>
                                    <p class="mb-0 font-13 font-weight-medium"><?=$plan['installments'] ?? 1?></p>
                                </div>
                                <div class="flex-row-between flex-align-center py-2">
                                    <p class="mb-0 font-13 color-gray">Start</p>
                                    <p class="mb-0 font-13 font-weight-medium"><?=ucfirst(Translate::context('payment_start.' . ($plan['start'] ?? 'now')))?></p>
                                </div>
                            </div>

                            <div class="flex-row-end mt-3">
                                <button class="btn-v2 trans-btn font-12" onclick="editPlan('<?=$planKey?>', <?=htmlspecialchars(json_encode($plan))?>)">
                                    <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Info Card -->
            <div class="card border-radius-10px bg-lightest-blue">
                <div class="card-body">
                    <div class="flex-row-start" style="gap: .75rem;">
                        <i class="mdi mdi-information-outline font-20 color-blue"></i>
                        <div class="flex-col-start">
                            <p class="mb-0 font-14 font-weight-medium color-dark">Om betalingsplaner</p>
                            <p class="mb-0 font-13 color-gray mt-1">
                                <strong>Direct:</strong> Betaling med det samme.<br>
                                <strong>Pushed:</strong> Udskudt betaling til en specifik dato.<br>
                                <strong>Installments:</strong> Delt betaling over flere rater (BNPL).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Edit Max BNPL Modal -->
<div class="modal fade" id="editMaxBnplModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger maksimalt BNPL beløb</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Beløb (DKK)</label>
                    <input type="number" step="0.01" min="0" class="form-field-v2" id="maxBnplInput" placeholder="1000">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveMaxBnpl()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Max Duration Modal -->
<div class="modal fade" id="editMaxDurationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger maksimal BNPL varighed</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Antal dage</label>
                    <input type="number" step="1" min="1" class="form-field-v2" id="maxDurationInput" placeholder="90">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveMaxDuration()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Plan Modal -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold" id="editPlanModalTitle">Rediger betalingsplan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start" style="gap: 1rem;">
                    <input type="hidden" id="editPlanKey">
                    <div class="flex-col-start">
                        <label class="font-12 color-gray mb-1">Titel</label>
                        <input type="text" class="form-field-v2" id="editPlanTitle">
                    </div>
                    <div class="flex-col-start">
                        <label class="font-12 color-gray mb-1">Beskrivelse</label>
                        <input type="text" class="form-field-v2" id="editPlanCaption">
                    </div>
                    <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                        <div class="flex-col-start flex-1">
                            <label class="font-12 color-gray mb-1">Rater</label>
                            <input type="number" min="1" max="12" class="form-field-v2" id="editPlanInstallments">
                        </div>
                        <div class="flex-col-start flex-1">
                            <label class="font-12 color-gray mb-1">Start</label>
                            <select class="form-select-v2 h-45px" id="editPlanStart">
                                <option value="now">Nu (now)</option>
                                <option value="first day of next month">1. næste måned</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <label class="toggle-switch mb-0">
                            <input type="checkbox" id="editPlanEnabled">
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="editPlanEnabled" class="font-14 mb-0 cursor-pointer">Aktiv</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="savePlan()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem
                </button>
            </div>
        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    var panelPaymentPlans = <?=json_encode($paymentPlans)?>;
    var panelPaymentPlansApiUrl = '<?=__url(Links::$api->admin->panel->updateSetting)?>';

    $(document).ready(function() {
        initPanelPaymentPlans(panelPaymentPlans, panelPaymentPlansApiUrl);
    });
</script>
<?php scriptEnd(); ?>
