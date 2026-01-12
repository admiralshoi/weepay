/**
 * Admin Panel Settings JS
 */

var addItemModal = null;
var addCountryModal = null;
var listData = {};
var updateSettingUrl = '';

function initPanelSettings(data, apiUrl) {
    listData = data;
    updateSettingUrl = apiUrl;
    addItemModal = $('#addItemModal');
    addCountryModal = $('#addCountryModal');
}

async function saveSingleSetting(key, value) {
    var parsedValue = value;
    if (!isNaN(value) && value !== '') {
        parsedValue = parseFloat(value);
    }

    var result = await post(updateSettingUrl, {
        key: key,
        value: parsedValue
    });

    if (result.status === 'success') {
        showSuccessNotification('Gemt', 'Indstillingen er opdateret');
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

function openAddItemModal(key, title, placeholder) {
    $('#addItemKey').val(key);
    $('#addItemModalTitle').text(title);
    $('#addItemInputLabel').text(placeholder);
    $('#addItemValue').val('').attr('placeholder', placeholder);
    // Show hint for role inputs
    if (key === 'organisation_roles' || key === 'location_roles') {
        $('#addItemRoleHint').show();
    } else {
        $('#addItemRoleHint').hide();
    }
    addItemModal.modal('show');
}

async function saveNewItem() {
    var key = $('#addItemKey').val();
    var value = $('#addItemValue').val().trim();

    if (!value) {
        showErrorNotification('Fejl', 'Værdien kan ikke være tom');
        return;
    }

    if (!listData[key].includes(value)) {
        listData[key].push(value);
    }

    var result = await post(updateSettingUrl, {
        key: key,
        value: listData[key]
    });

    if (result.status === 'success') {
        addItemModal.modal('hide');

        // For roles, use the sanitized value returned from backend
        if (result.data?.value && (key === 'organisation_roles' || key === 'location_roles')) {
            listData[key] = result.data.value;
            // Get the newly added sanitized value (last one that wasn't in old list)
            var sanitizedValue = result.data.value[result.data.value.length - 1];
            var displayValue = sanitizedValue.charAt(0).toUpperCase() + sanitizedValue.slice(1).replace(/_/g, ' ');
            $('#' + key + '-list').append(
                '<span class="tag-chip" data-key="' + key + '" data-value="' + sanitizedValue + '">' +
                    displayValue +
                    '<i class="mdi mdi-close ml-1 cursor-pointer" onclick="removeItem(\'' + key + '\', \'' + sanitizedValue + '\')"></i>' +
                '</span>'
            );
        } else {
            var displayValue = value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ');
            $('#' + key + '-list').append(
                '<span class="tag-chip" data-key="' + key + '" data-value="' + value + '">' +
                    displayValue +
                    '<i class="mdi mdi-close ml-1 cursor-pointer" onclick="removeItem(\'' + key + '\', \'' + value + '\')"></i>' +
                '</span>'
            );
        }
        showSuccessNotification('Tilføjet', 'Elementet er tilføjet');
    } else {
        // Revert the local change since save failed
        listData[key] = listData[key].filter(function(item) { return item !== value; });
        showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
    }
}

function removeItem(key, value) {
    SweetPrompt.confirm('Fjern element?', 'Er du sikker på at du vil fjerne "' + value + '"?', {
        confirmButtonText: 'Ja, fjern',
        onConfirm: async function() {
            listData[key] = listData[key].filter(function(item) { return item !== value; });

            var apiResult = await post(updateSettingUrl, {
                key: key,
                value: listData[key]
            });

            if (apiResult.status === 'success') {
                $('.tag-chip[data-key="' + key + '"][data-value="' + value + '"]').remove();
                showSuccessNotification('Fjernet', 'Elementet er fjernet');
            } else {
                listData[key].push(value);
                showErrorNotification('Fejl', apiResult.error?.message || 'Der opstod en fejl');
            }
            return apiResult;
        }
    });
}

function togglePaymentProvider(provider, isNowChecked) {
    // isNowChecked = the NEW state after clicking (onclick fires after state change)

    if (!isNowChecked) {
        // Was active, now unchecked - trying to deactivate - show warning
        // Revert checkbox immediately
        $('input[data-provider="' + provider + '"]').prop('checked', true);

        SweetPrompt.confirm(
            'Deaktiver betalingsudbyder?',
            'Er du sikker på at du vil deaktivere "' + provider + '"? Dette kan påvirke aktive betalingsflows.',
            {
                confirmButtonText: 'Ja, deaktiver',
                onConfirm: function() {
                    doTogglePaymentProvider(provider, false);
                }
            }
        );
        return false;
    } else {
        // Was inactive, now checked - trying to activate - no warning needed, proceed
        doTogglePaymentProvider(provider, true);
    }
}

async function doTogglePaymentProvider(provider, isActive) {
    if (isActive) {
        if (!listData.active_payment_providers.includes(provider)) {
            listData.active_payment_providers.push(provider);
        }
    } else {
        listData.active_payment_providers = listData.active_payment_providers.filter(function(p) { return p !== provider; });
    }

    var result = await post(updateSettingUrl, {
        key: 'active_payment_providers',
        value: listData.active_payment_providers
    });

    if (result.status === 'success') {
        queueNotificationOnLoad(isActive ? 'Aktiveret' : 'Deaktiveret', 'Betalingsudbyder er opdateret', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
    }
}

// Country management functions
function openAddCountryModal() {
    $('#addCountrySelect').val('');
    addCountryModal.modal('show');
}

async function saveNewCountry() {
    var select = $('#addCountrySelect');
    var code = select.val();
    var name = select.find('option:selected').data('name');

    if (!code) {
        showErrorNotification('Fejl', 'Vælg et land');
        return;
    }

    var result = await post(updateSettingUrl, {
        key: 'country_add',
        value: { code: code, name: name }
    });

    if (result.status === 'success') {
        addCountryModal.modal('hide');
        queueNotificationOnLoad('Tilføjet', 'Landet er tilføjet', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}

function removeCountry(code) {
    SweetPrompt.confirm('Fjern land?', 'Er du sikker på at du vil fjerne dette land?', {
        confirmButtonText: 'Ja, fjern',
        onConfirm: async function() {
            var apiResult = await post(updateSettingUrl, {
                key: 'country_remove',
                value: { code: code }
            });

            if (apiResult.status === 'success') {
                queueNotificationOnLoad('Fjernet', 'Landet er fjernet', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', apiResult.message || 'Der opstod en fejl');
            }
            return apiResult;
        }
    });
}

// Currency management
async function saveCurrencies() {
    var selectedCurrencies = $('#currenciesSelect').val() || [];

    var result = await post(updateSettingUrl, {
        key: 'currencies',
        value: selectedCurrencies
    });

    if (result.status === 'success') {
        listData.currencies = selectedCurrencies;
        showSuccessNotification('Gemt', 'Valutaer er opdateret');
    } else {
        showErrorNotification('Fejl', result.message || 'Der opstod en fejl');
    }
}
