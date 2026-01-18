<?php
/**
 * Admin Panel - Notification Flow Detail
 * Create or edit a notification flow
 * @var object $args
 */

use classes\enumerations\Links;
$pageTitle = $args->isNew ? "Nyt flow" : "Rediger flow";
$flow = $args->flow ?? null;
$isNew = $args->isNew ?? true;
$breakpoints = $args->breakpoints ?? new \Database\Collection();
$templates = $args->templates ?? new \Database\Collection();
$flowActions = $args->flowActions ?? new \Database\Collection();


if (isEmpty($flow->conditions)) $conditions = [];
else $conditions = array_values(toArray($flow->conditions));
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "notifications";
    var flowUid = <?=json_encode($flow->uid ?? null)?>;
    var isNewFlow = <?=json_encode($isNew)?>;
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
                <a href="<?=__url(Links::$admin->panelNotificationFlows)?>" class="font-13 color-gray hover-color-blue">Flows</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=$isNew ? 'Nyt' : htmlspecialchars($flow->name ?? '')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold"><?=$pageTitle?></h1>
                    <p class="mb-0 font-14 color-gray"><?=$isNew ? 'Opret et nyt notifikationsflow' : 'Rediger flowets indstillinger og handlinger'?></p>
                </div>
            </div>

            <div class="row" style="row-gap: 1.5rem;">
                <!-- Main Content -->
                <div class="col-12 col-lg-8">
                    <!-- Flow Form -->
                    <form id="flow-form">
                        <div class="card border-radius-10px">
                            <div class="card-body">
                                <p class="font-16 font-weight-bold mb-3">Flow detaljer</p>

                                <!-- Name -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Navn <span class="color-red">*</span></label>
                                    <input type="text" name="name" class="form-field-v2 w-100" value="<?=htmlspecialchars($flow->name ?? '')?>" placeholder="F.eks. Velkomstserie" required>
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Beskrivelse</label>
                                    <textarea name="description" class="form-field-v2 w-100" rows="3" placeholder="Beskriv hvad dette flow gør..."><?=htmlspecialchars($flow->description ?? '')?></textarea>
                                </div>

                                <!-- Breakpoint -->
                                <?php
                                // Get breakpoint key - handle both object (foreign key) and string
                                $flowBreakpointKey = null;
                                if ($flow && $flow->breakpoint) {
                                    $flowBreakpointKey = is_object($flow->breakpoint) ? $flow->breakpoint->key : $flow->breakpoint;
                                }
                                ?>
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Breakpoint <span class="color-red">*</span></label>
                                    <select name="breakpoint" class="form-select-v2 w-100" data-search="true" required>
                                        <option value="">Vælg breakpoint...</option>
                                        <?php foreach ($breakpoints->list() as $bp): ?>
                                            <option value="<?=$bp->key?>" <?=$flowBreakpointKey === $bp->key ? 'selected' : ''?>>
                                                <?=htmlspecialchars($bp->name)?> (<?=$bp->key?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Hvornår skal dette flow aktiveres?</small>
                                </div>

                                <div class="row">
                                    <!-- Priority -->
                                    <div class="col-12 col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label font-13 font-weight-medium">Prioritet</label>
                                            <input type="number" name="priority" class="form-field-v2 w-100" value="<?=$flow->priority ?? 100?>" min="1" max="1000">
                                            <small class="form-text text-muted">Lavere = højere prioritet</small>
                                        </div>
                                    </div>

                                    <!-- Start Date -->
                                    <div class="col-12 col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label font-13 font-weight-medium">Startdato</label>
                                            <input type="date" name="starts_at" class="form-field-v2 w-100" value="<?=($flow->starts_at ?? null) ? date('Y-m-d', $flow->starts_at) : ''?>">
                                            <small class="form-text text-muted">Valgfrit</small>
                                        </div>
                                    </div>

                                    <!-- End Date -->
                                    <div class="col-12 col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label font-13 font-weight-medium">Slutdato</label>
                                            <input type="date" name="ends_at" class="form-field-v2 w-100" value="<?=($flow->ends_at ?? null) ? date('Y-m-d', $flow->ends_at) : ''?>">
                                            <small class="form-text text-muted">Valgfrit</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Status</label>
                                    <select name="status" class="form-select-v2 w-100">
                                        <option value="draft" <?=($flow->status ?? 'draft') === 'draft' ? 'selected' : ''?>>Kladde</option>
                                        <option value="active" <?=($flow->status ?? '') === 'active' ? 'selected' : ''?>>Aktiv</option>
                                        <option value="inactive" <?=($flow->status ?? '') === 'inactive' ? 'selected' : ''?>>Inaktiv</option>
                                    </select>
                                </div>

                                <hr class="my-4">

                                <p class="font-14 font-weight-bold mb-3">Betingelser</p>
                                <p class="font-13 color-gray mb-3">Tilføj betingelser for hvornår dette flow skal trigges. Alle betingelser skal være opfyldt.</p>

                                <!-- Conditions -->
                                <div class="mb-3" id="conditions-container">
                                    <div id="conditions-list">
                                        <!-- Conditions will be rendered here by JS -->
                                    </div>
                                    <button type="button" class="btn-v2 mute-btn btn-sm mt-2" onclick="addCondition()">
                                        <i class="mdi mdi-plus mr-1"></i> Tilføj betingelse
                                    </button>
                                </div>
                                <input type="hidden" name="conditions" id="conditions-input" value="<?=htmlspecialchars(json_encode($conditions))?>">

                                <hr class="my-4">

                                <p class="font-14 font-weight-bold mb-3">Modtager & Tidsplan</p>

                                <!-- Recipient Type -->
                                <div class="mb-3">
                                    <label class="form-label font-13 font-weight-medium">Modtager</label>
                                    <select name="recipient_type" id="recipient-type" class="form-select-v2 w-100">
                                        <option value="user" <?=($flow->recipient_type ?? 'user') === 'user' ? 'selected' : ''?>>Bruger (fra kontekst)</option>
                                        <option value="organisation" <?=($flow->recipient_type ?? '') === 'organisation' ? 'selected' : ''?>>Organisation e-mail</option>
                                        <option value="location" <?=($flow->recipient_type ?? '') === 'location' ? 'selected' : ''?>>Lokation e-mail</option>
                                        <option value="organisation_owner" <?=($flow->recipient_type ?? '') === 'organisation_owner' ? 'selected' : ''?>>Organisationsejer</option>
                                        <option value="custom" <?=($flow->recipient_type ?? '') === 'custom' ? 'selected' : ''?>>Brugerdefineret e-mail</option>
                                    </select>
                                    <small class="form-text text-muted">Hvem skal modtage notifikationen?</small>
                                </div>

                                <!-- Custom Email (shown when recipient_type is 'custom') -->
                                <div class="mb-3" id="custom-email-field" style="display: <?=($flow->recipient_type ?? '') === 'custom' ? 'block' : 'none'?>;">
                                    <label class="form-label font-13 font-weight-medium">Brugerdefineret e-mail</label>
                                    <input type="email" name="recipient_email" class="form-field-v2 w-100" value="<?=htmlspecialchars($flow->recipient_email ?? '')?>" placeholder="email@example.com">
                                </div>

                                <!-- Schedule Offset (for scheduled breakpoints) -->
                                <div class="mb-3" id="schedule-offset-field">
                                    <label class="form-label font-13 font-weight-medium">Tidsforskydning (dage)</label>
                                    <input type="number" name="schedule_offset_days" class="form-field-v2 w-100" value="<?=$flow->schedule_offset_days ?? 0?>" min="-365" max="365">
                                    <small class="form-text text-muted">For planlagte breakpoints: negative = før hændelse, positive = efter. F.eks. -1 = 1 dag før forfald.</small>
                                </div>

                                <!-- Submit -->
                                <div class="flex-row-start" style="gap: .5rem;">
                                    <button type="submit" class="btn-v2 action-btn">
                                        <i class="mdi mdi-content-save-outline mr-1"></i>
                                        <?=$isNew ? 'Opret flow' : 'Gem ændringer'?>
                                    </button>

                                    <?php if (!$isNew): ?>
                                        <button type="button" class="btn-v2 danger-btn" onclick="deleteFlow()">
                                            <i class="mdi mdi-delete-outline mr-1"></i> Slet flow
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Flow Actions -->
                    <div class="card border-radius-10px mt-3">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3">
                                <p class="font-16 font-weight-bold mb-0">Handlinger</p>
                                <button type="button" class="btn-v2 action-btn btn-sm" onclick="showAddActionModal()">
                                    <i class="mdi mdi-plus mr-1"></i> Tilføj handling
                                </button>
                            </div>

                            <div id="actions-container">
                                <?php if ($isNew): ?>
                                    <!-- For new flows, actions are added via JS -->
                                    <div id="no-actions-message" class="text-center py-4">
                                        <p class="font-14 color-gray mb-2">Ingen handlinger endnu</p>
                                        <p class="font-12 color-gray">Tilføj handlinger for at sende notifikationer når flowet aktiveres</p>
                                    </div>
                                    <div id="actions-table-container" style="display: none;">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="font-12 color-gray font-weight-normal border-0">Skabelon</th>
                                                        <th class="font-12 color-gray font-weight-normal border-0">Kanal</th>
                                                        <th class="font-12 color-gray font-weight-normal border-0"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="pending-actions-tbody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php elseif ($flowActions->empty()): ?>
                                    <div class="text-center py-4">
                                        <p class="font-14 color-gray mb-2">Ingen handlinger endnu</p>
                                        <p class="font-12 color-gray">Tilføj handlinger for at sende notifikationer når flowet aktiveres</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="font-12 color-gray font-weight-normal border-0">Skabelon</th>
                                                    <th class="font-12 color-gray font-weight-normal border-0">Kanal</th>
                                                    <th class="font-12 color-gray font-weight-normal border-0">Status</th>
                                                    <th class="font-12 color-gray font-weight-normal border-0"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($flowActions->list() as $action): ?>
                                                    <?php
                                                    // Template is a foreign key object
                                                    $templateObj = $action->template;
                                                    $templateName = is_object($templateObj) ? $templateObj->name : 'Ukendt skabelon';
                                                    $channelLabels = ['email' => 'E-mail', 'sms' => 'SMS', 'bell' => 'Push'];
                                                    $channelLabel = $channelLabels[$action->channel] ?? $action->channel;
                                                    ?>
                                                    <tr>
                                                        <td class="py-3">
                                                            <span class="font-14"><?=htmlspecialchars($templateName)?></span>
                                                        </td>
                                                        <td class="py-3">
                                                            <span class="font-13"><?=$channelLabel?></span>
                                                        </td>
                                                        <td class="py-3">
                                                            <?php if ($action->status === 'active'): ?>
                                                                <span class="success-box font-11">Aktiv</span>
                                                            <?php else: ?>
                                                                <span class="mute-box font-11">Inaktiv</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="py-3 text-end">
                                                            <button type="button" class="btn-v2 action-btn btn-sm" onclick="deleteAction('<?=$action->uid?>')">
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
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-12 col-lg-4">
                    <!-- Help Card -->
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <p class="font-14 font-weight-bold mb-3">Sådan fungerer flows</p>
                            <div class="flex-col-start" style="gap: .75rem;">
                                <div class="flex-row-start" style="gap: .5rem;">
                                    <div class="square-25 bg-blue border-radius-50 flex-row-center-center flex-shrink-0">
                                        <span class="font-11 color-white font-weight-bold">1</span>
                                    </div>
                                    <p class="mb-0 font-13">Udfyld navn, breakpoint og modtager</p>
                                </div>
                                <div class="flex-row-start" style="gap: .5rem;">
                                    <div class="square-25 bg-blue border-radius-50 flex-row-center-center flex-shrink-0">
                                        <span class="font-11 color-white font-weight-bold">2</span>
                                    </div>
                                    <p class="mb-0 font-13">Tilføj handlinger med skabeloner</p>
                                </div>
                                <div class="flex-row-start" style="gap: .5rem;">
                                    <div class="square-25 bg-blue border-radius-50 flex-row-center-center flex-shrink-0">
                                        <span class="font-11 color-white font-weight-bold">3</span>
                                    </div>
                                    <p class="mb-0 font-13">Aktiver flowet når du er klar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Action Modal -->
<div class="modal fade" id="addActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header">
                <h5 class="modal-title font-16 font-weight-bold">Tilføj handling</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="action-form">
                    <input type="hidden" name="flow" value="<?=$flow->uid ?? ''?>">

                    <div class="mb-3">
                        <label class="form-label font-13 font-weight-medium">Skabelon <span class="color-red">*</span></label>
                        <select name="template" class="form-select-v2 w-100" data-search="true" required>
                            <option value="">Vælg skabelon...</option>
                            <?php foreach ($templates->list() as $template): ?>
                                <option value="<?=$template->uid?>"><?=htmlspecialchars($template->name)?> (<?=$template->type?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($templates->empty()): ?>
                            <small class="form-text text-danger">Ingen aktive skabeloner fundet. Opret en skabelon først.</small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-13 font-weight-medium">Kanal</label>
                        <select name="channel" class="form-select-v2 w-100">
                            <option value="email">E-mail</option>
                            <option value="sms">SMS</option>
                            <option value="bell">Push (klokke)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 secondary-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="addAction()">Tilføj</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle custom email field based on recipient type
    document.getElementById('recipient-type')?.addEventListener('change', function() {
        var customEmailField = document.getElementById('custom-email-field');
        if (customEmailField) {
            customEmailField.style.display = this.value === 'custom' ? 'block' : 'none';
        }
    });

    // ========================================
    // CONDITIONS MANAGEMENT
    // ========================================

    // Payment plans from AppMeta (Settings::$app->paymentPlans)
    var paymentPlans = <?=json_encode(\features\Settings::$app->paymentPlans ?? new stdClass())?>;
    var paymentPlanOptions = [];
    if (paymentPlans) {
        Object.keys(paymentPlans).forEach(function(key) {
            var plan = paymentPlans[key];
            if (plan && plan.enabled) {
                paymentPlanOptions.push({ value: key, label: plan.title || key });
            }
        });
    }

    // Available fields for conditions (matches placeholders)
    // valueType: 'select' = dropdown with options, 'number' = numeric input, 'text' = free text
    var conditionFields = [
        { value: 'order.payment_plan', label: 'Betalingsplan', valueType: 'select', options: paymentPlanOptions },
        { value: 'payment_plan.total_installments', label: 'Betalingsplan: Antal rater', valueType: 'number' },
        { value: 'payment_plan.remaining_installments', label: 'Betalingsplan: Resterende rater', valueType: 'number' },
        { value: 'payment_plan.completed_installments', label: 'Betalingsplan: Gennemførte rater', valueType: 'number' },
        { value: 'payment.status', label: 'Betalingsstatus', valueType: 'select', options: [
            { value: 'PENDING', label: 'Afventer' },
            { value: 'SCHEDULED', label: 'Planlagt' },
            { value: 'COMPLETED', label: 'Betalt' },
            { value: 'PAST_DUE', label: 'Forsinket' },
            { value: 'FAILED', label: 'Fejlet' },
            { value: 'CANCELLED', label: 'Annulleret' },
            { value: 'REFUNDED', label: 'Refunderet' },
        ]},
        { value: 'payment.installment_number', label: 'Rate nummer', valueType: 'number' },
        { value: 'order.status', label: 'Ordrestatus', valueType: 'select', options: [
            { value: 'DRAFT', label: 'Kladde' },
            { value: 'PENDING', label: 'Afventer' },
            { value: 'COMPLETED', label: 'Gennemført' },
            { value: 'CANCELLED', label: 'Annulleret' },
            { value: 'EXPIRED', label: 'Udløbet' },
        ]},
        { value: 'order.amount', label: 'Ordrebeløb (øre)', valueType: 'number' },
        { value: 'order.currency', label: 'Valuta', valueType: 'select', options: [
            { value: 'DKK', label: 'DKK' },
            { value: 'EUR', label: 'EUR' },
            { value: 'USD', label: 'USD' },
            { value: 'SEK', label: 'SEK' },
            { value: 'NOK', label: 'NOK' },
        ]},
        { value: 'user.email', label: 'Bruger e-mail', valueType: 'text' },
        { value: 'organisation.name', label: 'Organisation navn', valueType: 'text' },
        { value: 'location.name', label: 'Lokation navn', valueType: 'text' },
        { value: 'days_until_due', label: 'Dage til forfald', valueType: 'number' },
        { value: 'days_overdue', label: 'Dage overskredet', valueType: 'number' },
        { value: 'fees.reminder_count', label: 'Antal rykkere', valueType: 'number' },
    ];

    // Helper to get field config by value
    function getFieldConfig(fieldValue) {
        return conditionFields.find(function(f) { return f.value === fieldValue; });
    }

    var conditionOperators = [
        { value: '=', label: 'er lig med' },
        { value: '!=', label: 'er ikke lig med' },
        { value: '>', label: 'større end' },
        { value: '>=', label: 'større end eller lig' },
        { value: '<', label: 'mindre end' },
        { value: '<=', label: 'mindre end eller lig' },
        { value: 'contains', label: 'indeholder' },
        { value: 'starts_with', label: 'starter med' },
        { value: 'ends_with', label: 'slutter med' },
    ];

    var conditions = <?=json_encode($conditions)?>;
    if (!Array.isArray(conditions)) conditions = [];

    function renderConditions() {
        var container = document.getElementById('conditions-list');
        if (!container) return;

        if (conditions.length === 0) {
            container.innerHTML = '<p class="font-13 color-gray mb-0"><i class="mdi mdi-information-outline mr-1"></i>Ingen betingelser - flowet trigges altid når breakpointet udløses.</p>';
            return;
        }

        var html = '';
        conditions.forEach(function(cond, index) {
            var fieldConfig = getFieldConfig(cond.field);

            html += '<div class="condition-row flex-row-start flex-align-center flex-wrap mb-2" style="gap: .5rem;" data-index="' + index + '">';

            // Field select
            html += '<select class="form-select-v2 condition-field-select" data-index="' + index + '" style="min-width: 160px;">';
            html += '<option value="">Vælg felt...</option>';
            conditionFields.forEach(function(f) {
                var selected = f.value === cond.field ? ' selected' : '';
                html += '<option value="' + f.value + '"' + selected + '>' + f.label + '</option>';
            });
            html += '</select>';

            // Operator select
            html += '<select class="form-select-v2 condition-operator-select" data-index="' + index + '" style="min-width: 140px;">';
            conditionOperators.forEach(function(op) {
                var selected = op.value === cond.operator ? ' selected' : '';
                html += '<option value="' + op.value + '"' + selected + '>' + op.label + '</option>';
            });
            html += '</select>';

            // Value input - type depends on field
            html += renderValueInput(cond, index, fieldConfig);

            // Remove button
            html += '<button type="button" class="btn-v2 danger-btn btn-sm" style="padding: 8px 10px;" onclick="removeCondition(' + index + ')" title="Fjern betingelse"><i class="mdi mdi-close"></i></button>';

            html += '</div>';
        });

        container.innerHTML = html;

        // Initialize select-v2 elements
        container.querySelectorAll('.form-select-v2').forEach(function(select) {
            selectV2(select);
        });

        // Bind change events for field selects (need to re-render when field changes)
        container.querySelectorAll('.condition-field-select').forEach(function(select) {
            select.addEventListener('change', function() {
                var idx = parseInt(this.dataset.index);
                conditions[idx].field = this.value;
                conditions[idx].value = ''; // Reset value when field changes
                updateConditionsInput();
                renderConditions(); // Re-render to show correct value input type
            });
        });
        container.querySelectorAll('.condition-operator-select').forEach(function(select) {
            select.addEventListener('change', function() {
                updateCondition(parseInt(this.dataset.index), 'operator', this.value);
            });
        });
        container.querySelectorAll('.condition-value-input').forEach(function(input) {
            input.addEventListener('change', function() {
                updateCondition(parseInt(this.dataset.index), 'value', this.value);
            });
        });
        container.querySelectorAll('.condition-value-select').forEach(function(select) {
            select.addEventListener('change', function() {
                updateCondition(parseInt(this.dataset.index), 'value', this.value);
            });
        });
    }

    function renderValueInput(cond, index, fieldConfig) {
        if (!fieldConfig || !cond.field) {
            // No field selected yet - show placeholder text input
            return '<input type="text" class="form-field-v2 condition-value-input" data-index="' + index + '" style="min-width: 140px;" value="" placeholder="Vælg felt først" disabled>';
        }

        var valueType = fieldConfig.valueType || 'text';

        if (valueType === 'select' && fieldConfig.options) {
            // Dropdown with predefined options
            var html = '<select class="form-select-v2 condition-value-select" data-index="' + index + '" style="min-width: 140px;">';
            html += '<option value="">Vælg værdi...</option>';
            fieldConfig.options.forEach(function(opt) {
                var selected = opt.value === cond.value ? ' selected' : '';
                html += '<option value="' + opt.value + '"' + selected + '>' + opt.label + '</option>';
            });
            html += '</select>';
            return html;
        } else if (valueType === 'number') {
            // Numeric input
            return '<input type="number" class="form-field-v2 condition-value-input" data-index="' + index + '" style="min-width: 100px; max-width: 120px;" value="' + escapeHtml(cond.value || '') + '" placeholder="0">';
        } else {
            // Text input (default)
            return '<input type="text" class="form-field-v2 condition-value-input" data-index="' + index + '" style="min-width: 140px;" value="' + escapeHtml(cond.value || '') + '" placeholder="Værdi">';
        }
    }

    function addCondition() {
        conditions.push({ field: '', operator: '=', value: '' });
        renderConditions();
        updateConditionsInput();
    }

    function removeCondition(index) {
        conditions.splice(index, 1);
        renderConditions();
        updateConditionsInput();
    }

    function updateCondition(index, key, value) {
        if (conditions[index]) {
            conditions[index][key] = value;
            updateConditionsInput();
        }
    }

    function updateConditionsInput() {
        // Filter out empty conditions
        var validConditions = conditions.filter(function(c) {
            return c.field && c.value !== '';
        });
        document.getElementById('conditions-input').value = JSON.stringify(validConditions);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize conditions on page load
    document.addEventListener('DOMContentLoaded', function() {
        renderConditions();
    });
</script>
