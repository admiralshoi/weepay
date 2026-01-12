/**
 * Admin Panel Payment Plans JS
 */

var maxBnplModal = null;
var maxDurationModal = null;
var editPlanModal = null;
var currentPaymentPlans = {};
var paymentPlansApiUrl = '';

function initPanelPaymentPlans(paymentPlans, apiUrl) {
    currentPaymentPlans = paymentPlans;
    paymentPlansApiUrl = apiUrl;
    maxBnplModal = $('#editMaxBnplModal');
    maxDurationModal = $('#editMaxDurationModal');
    editPlanModal = $('#editPlanModal');
}

function editMaxBnpl(currentAmount) {
    $('#maxBnplInput').val(currentAmount);
    maxBnplModal.modal('show');
}

async function saveMaxBnpl() {
    var amount = parseFloat($('#maxBnplInput').val());
    if (isNaN(amount) || amount < 0) {
        showErrorNotification('Fejl', 'Beløbet skal være positivt');
        return;
    }

    var result = await post(paymentPlansApiUrl, {
        key: 'platform_max_bnpl_amount',
        value: amount
    });

    if (result.status === 'success') {
        maxBnplModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Maksimalt BNPL beløb er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

function editMaxDuration(currentDays) {
    $('#maxDurationInput').val(currentDays);
    maxDurationModal.modal('show');
}

async function saveMaxDuration() {
    var days = parseInt($('#maxDurationInput').val());
    if (isNaN(days) || days < 1) {
        showErrorNotification('Fejl', 'Antal dage skal være mindst 1');
        return;
    }

    var result = await post(paymentPlansApiUrl, {
        key: 'bnplInstallmentMaxDuration',
        value: days
    });

    if (result.status === 'success') {
        maxDurationModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Maksimal BNPL varighed er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
    }
}

function editPlan(planKey, plan) {
    $('#editPlanKey').val(planKey);
    $('#editPlanTitle').val(plan.title || '');
    $('#editPlanCaption').val(plan.caption || '');
    $('#editPlanInstallments').val(plan.installments || 1);
    updateSelectV2Value($('#editPlanStart'), plan.start || 'now');
    $('#editPlanEnabled').prop('checked', plan.enabled === true);
    $('#editPlanModalTitle').text('Rediger: ' + (plan.title || planKey));
    editPlanModal.modal('show');
}

async function savePlan() {
    var planKey = $('#editPlanKey').val();
    var updatedPlan = {
        enabled: $('#editPlanEnabled').is(':checked'),
        title: $('#editPlanTitle').val(),
        caption: $('#editPlanCaption').val(),
        installments: parseInt($('#editPlanInstallments').val()) || 1,
        start: $('#editPlanStart').val()
    };

    // Update the plan in the full object
    currentPaymentPlans[planKey] = updatedPlan;

    var result = await post(paymentPlansApiUrl, {
        key: 'paymentPlans',
        value: currentPaymentPlans
    });

    if (result.status === 'success') {
        editPlanModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Betalingsplanen er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}
