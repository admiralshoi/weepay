/**
 * Admin Panel Fees JS
 */

var defaultFeeModal = null;
var cardFeeModal = null;
var paymentProviderFeeModal = null;
var flatFeeModal = null;
var orgFeeModal = null;
var organisationsLoaded = false;

function initPanelFees() {
    defaultFeeModal = $('#editDefaultFeeModal');
    cardFeeModal = $('#editCardFeeModal');
    paymentProviderFeeModal = $('#editPaymentProviderFeeModal');
    flatFeeModal = $('#editFlatFeeModal');
    orgFeeModal = $('#orgFeeModal');
    loadOrganisations();

    // Event delegation for edit buttons
    $(document).on('click', '.edit-org-fee-btn', function() {
        var btn = $(this);
        editOrgFee(
            btn.data('uid'),
            btn.data('fee'),
            btn.data('org'),
            btn.data('start'),
            btn.data('end') || null,
            btn.data('reason') || ''
        );
    });
}

async function loadOrganisations() {
    var result = await post(platformLinks.api.admin.organisations.list, { per_page: 1000 });

    if (result.status === 'success' && result.data && result.data.organisations) {
        var select = $('#orgFeeOrgSelect');
        select.empty();
        result.data.organisations.forEach(function(org) {
            select.append('<option value="' + org.uid + '" data-sort="' + org.name + '_' + (org.cvr || '') + '">' + org.name + (org.cvr ? ' (' + org.cvr + ')' : '') + '</option>');
        });
        organisationsLoaded = true;

        // Re-initialize the select after options are added
        rebuildSelectV2UI(select.get(0));
    } else {
        console.error('Failed to load organisations:', result);
    }
}

function editDefaultFee(currentFee) {
    $('#defaultFeeInput').val(currentFee);
    defaultFeeModal.modal('show');
}

async function saveDefaultFee() {
    var fee = parseFloat($('#defaultFeeInput').val());
    if (isNaN(fee) || fee < 0 || fee > 100) {
        showErrorNotification('Fejl', 'Gebyret skal være mellem 0 og 100');
        return;
    }

    var result = await post(platformLinks.api.admin.panel.updateSetting, {
        key: 'resellerFee',
        value: fee
    });

    if (result.status === 'success') {
        defaultFeeModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Standardgebyret er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

// Card Fee functions
function editCardFee(currentFee) {
    $('#cardFeeInput').val(currentFee);
    cardFeeModal.modal('show');
}

async function saveCardFee() {
    var fee = parseFloat($('#cardFeeInput').val());
    if (isNaN(fee) || fee < 0 || fee > 100) {
        showErrorNotification('Fejl', 'Gebyret skal være mellem 0 og 100');
        return;
    }

    var result = await post(platformLinks.api.admin.panel.updateSetting, {
        key: 'cardFee',
        value: fee
    });

    if (result.status === 'success') {
        cardFeeModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Kortgebyret er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

// Payment Provider Fee functions
function editPaymentProviderFee(currentFee) {
    $('#paymentProviderFeeInput').val(currentFee);
    paymentProviderFeeModal.modal('show');
}

async function savePaymentProviderFee() {
    var fee = parseFloat($('#paymentProviderFeeInput').val());
    if (isNaN(fee) || fee < 0 || fee > 100) {
        showErrorNotification('Fejl', 'Gebyret skal være mellem 0 og 100');
        return;
    }

    var result = await post(platformLinks.api.admin.panel.updateSetting, {
        key: 'paymentProviderFee',
        value: fee
    });

    if (result.status === 'success') {
        paymentProviderFeeModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Betalingsudbyder gebyret er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

// Flat Fee functions
function editFlatFee(currentFee) {
    $('#flatFeeInput').val(currentFee);
    flatFeeModal.modal('show');
}

async function saveFlatFee() {
    var fee = parseFloat($('#flatFeeInput').val());
    if (isNaN(fee) || fee < 0 || fee > 100) {
        showErrorNotification('Fejl', 'Gebyret skal være mellem 0 og 100');
        return;
    }

    var result = await post(platformLinks.api.admin.panel.updateSetting, {
        key: 'paymentProviderFlatFee',
        value: fee
    });

    if (result.status === 'success') {
        flatFeeModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Fast gebyr er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

function addOrgFee() {
    $('#orgFeeModalTitle').text('Tilføj organisationsgebyr');
    $('#orgFeeUid').val('');
    $('#orgFeeOrgSelect').val([]).trigger('change');
    $('#orgSelectWrapper').show();
    $('#orgFeeInput').val('').prop('disabled', false);
    $('#orgFeeStartDate').val(new Date().toISOString().split('T')[0]).prop('disabled', false);
    $('#orgFeeEndDate').val('').prop('disabled', false);
    $('#orgFeeReason').val('');
    $('#orgFeeEditNotice').hide();
    orgFeeModal.modal('show');
}

function editOrgFee(uid, fee, orgUid, startTime, endTime, reason) {
    $('#orgFeeModalTitle').text('Rediger organisationsgebyr');
    $('#orgFeeUid').val(uid);
    $('#orgFeeOrgSelect').val([orgUid]).trigger('change');
    $('#orgSelectWrapper').hide();
    $('#orgFeeInput').val(fee).prop('disabled', true);
    $('#orgFeeStartDate').val(startTime ? new Date(startTime * 1000).toISOString().split('T')[0] : '').prop('disabled', true);
    $('#orgFeeEndDate').val(endTime ? new Date(endTime * 1000).toISOString().split('T')[0] : '').prop('disabled', true);
    $('#orgFeeReason').val(reason || '');
    $('#orgFeeEditNotice').show();
    orgFeeModal.modal('show');
}

async function saveOrgFee() {
    var uid = $('#orgFeeUid').val();
    var orgUids = $('#orgFeeOrgSelect').val() || [];
    var fee = parseFloat($('#orgFeeInput').val());
    var startDate = $('#orgFeeStartDate').val();
    var endDate = $('#orgFeeEndDate').val();
    var reason = $('#orgFeeReason').val().trim();

    if (!uid && orgUids.length === 0) {
        showErrorNotification('Fejl', 'Vælg mindst én organisation');
        return;
    }
    if (isNaN(fee) || fee < 0 || fee > 100) {
        showErrorNotification('Fejl', 'Gebyret skal være mellem 0 og 100');
        return;
    }
    if (fee < minOrgFee) {
        showErrorNotification('Fejl', 'Gebyret kan ikke være lavere end ' + minOrgFee.toFixed(2).replace('.', ',') + ' % (kortgebyr + betalingsudbyder gebyr)');
        return;
    }
    if (!startDate) {
        showErrorNotification('Fejl', 'Vælg en startdato');
        return;
    }

    // Validate dates are not in the past (except today)
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var startDateObj = new Date(startDate);
    startDateObj.setHours(0, 0, 0, 0);

    if (!uid && startDateObj < today) {
        showErrorNotification('Fejl', 'Startdato kan ikke være i fortiden');
        return;
    }

    if (endDate) {
        var endDateObj = new Date(endDate);
        endDateObj.setHours(0, 0, 0, 0);
        if (!uid && endDateObj < today) {
            showErrorNotification('Fejl', 'Slutdato kan ikke være i fortiden');
            return;
        }
        if (endDateObj < startDateObj) {
            showErrorNotification('Fejl', 'Slutdato kan ikke være før startdato');
            return;
        }
    }

    var result = await post(platformLinks.api.admin.panel.updateSetting, {
        key: 'org_fee_save',
        value: {
            uid: uid || null,
            organisations: uid ? [orgUids[0]] : orgUids, // Single org when editing, multiple when adding
            fee: fee,
            start_date: startDate,
            end_date: endDate || null,
            reason: reason || null
        }
    });

    if (result.status === 'success') {
        orgFeeModal.modal('hide');
        queueNotificationOnLoad('Gemt', 'Gebyret er gemt', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

function deleteOrgFee(uid) {
    SweetPrompt.confirm('Slet gebyr?', 'Er du sikker på at du vil slette dette gebyr?', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            var apiResult = await post(platformLinks.api.admin.panel.updateSetting, {
                key: 'org_fee_delete',
                value: { uid: uid }
            });
            if (apiResult.status === 'success') {
                queueNotificationOnLoad('Slettet', 'Gebyret er slettet', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', apiResult.message || 'Der opstod en fejl');
            }
            return apiResult;
        }
    });
}

// Rykker Settings
function editRykkerSettings() {
    $('#editRykkerModal').modal('show');
}

async function saveRykkerSettings() {
    var rykker1Days = parseInt($('#rykker1DaysInput').val()) || 7;
    var rykker2Days = parseInt($('#rykker2DaysInput').val()) || 14;
    var rykker3Days = parseInt($('#rykker3DaysInput').val()) || 21;
    var rykker1Fee = parseFloat($('#rykker1FeeInput').val()) || 0;
    var rykker2Fee = parseFloat($('#rykker2FeeInput').val()) || 0;
    var rykker3Fee = parseFloat($('#rykker3FeeInput').val()) || 0;

    // Validate days are in ascending order
    if (rykker1Days >= rykker2Days || rykker2Days >= rykker3Days) {
        showErrorNotification('Ugyldig rækkefølge', 'Dagene skal være i stigende rækkefølge (Rykker 1 < Rykker 2 < Rykker 3)');
        return;
    }

    // Validate minimum days
    if (rykker1Days < 1) {
        showErrorNotification('Ugyldigt antal dage', 'Rykker 1 skal være mindst 1 dag efter forfalden');
        return;
    }

    screenLoader.show('Gemmer rykker indstillinger...');
    var result = await post(platformLinks.api.admin.panel.rykkerSettings, {
        rykker_1_days: rykker1Days,
        rykker_2_days: rykker2Days,
        rykker_3_days: rykker3Days,
        rykker_1_fee: rykker1Fee,
        rykker_2_fee: rykker2Fee,
        rykker_3_fee: rykker3Fee
    });
    screenLoader.hide();

    if (result.status === 'success') {
        $('#editRykkerModal').modal('hide');
        queueNotificationOnLoad('Gemt', 'Rykker indstillinger er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.error?.message || result.message || 'Der opstod en fejl');
    }
}
