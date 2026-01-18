/**
 * Admin Notifications JS
 * Handles notification templates and flows management
 */

// Template form handling
document.addEventListener('DOMContentLoaded', function() {
    var templateForm = document.getElementById('template-form');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveTemplate();
        });
    }

    var flowForm = document.getElementById('flow-form');
    if (flowForm) {
        flowForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveFlow();
        });
    }
});

// Template functions
function wrapHtmlContent(html) {
    if (!html || html.trim() === '') return '';

    var trimmed = html.trim();
    // Check if already has html/body tags
    if (trimmed.toLowerCase().includes('<html') || trimmed.toLowerCase().includes('<!doctype')) {
        return html;
    }

    // Wrap with standard email HTML frame
    return '<!DOCTYPE html>\n<html>\n<head>\n    <meta charset="UTF-8">\n    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n</head>\n<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #333; padding: 20px;">\n    <div style="max-width: 600px; margin: 0 auto;">\n' + html + '\n    </div>\n</body>\n</html>';
}

async function saveTemplate() {
    var form = document.getElementById('template-form');
    var formData = new FormData(form);
    var data = Object.fromEntries(formData.entries());

    // Auto-wrap HTML content if present
    if (data.html_content) {
        data.html_content = wrapHtmlContent(data.html_content);
    }

    var endpoint = isNewTemplate ? 'api/admin/notifications/templates/create' : 'api/admin/notifications/templates/update';
    if (!isNewTemplate) {
        data.uid = templateUid;
    }

    var response = await post(endpoint, data);
    if (response.status === 'success') {
        if (isNewTemplate && response.data && response.data.uid) {
            queueNotificationOnLoad('Skabelon oprettet', response.message || 'Skabelonen blev oprettet', 'success');
            window.location.href = serverHost + platformLinks.admin.panelNotificationTemplates + '/' + response.data.uid;
        } else {
            showSuccessToast(response.message || 'Skabelon gemt');
        }
    } else {
        showErrorToast(response.message || response.error?.message || 'Fejl ved gemning');
    }
}

function deleteTemplate() {
    SweetPrompt.confirm('Slet skabelon?', 'Er du sikker på at du vil slette denne skabelon?', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            var response = await post('api/admin/notifications/templates/delete', {uid: templateUid});
            if (response.status === 'success') {
                queueNotificationOnLoad('Skabelon slettet', 'Skabelonen blev slettet', 'success');
                window.location.href = serverHost + platformLinks.admin.panelNotificationTemplates;
            } else {
                showErrorToast(response.message || response.error?.message || 'Fejl ved sletning');
            }
        }
    });
}

// Pending actions for new flows
var pendingActions = [];

// Flow functions
async function saveFlow() {
    var form = document.getElementById('flow-form');
    var formData = new FormData(form);
    var data = Object.fromEntries(formData.entries());

    var endpoint = isNewFlow ? 'api/admin/notifications/flows/create' : 'api/admin/notifications/flows/update';
    if (!isNewFlow) {
        data.uid = flowUid;
    }

    // Include pending actions for new flows
    if (isNewFlow && pendingActions.length > 0) {
        data.actions = pendingActions;
    }

    var response = await post(endpoint, data);
    if (response.status === 'success') {
        if (isNewFlow && response.data && response.data.uid) {
            queueNotificationOnLoad('Flow oprettet', response.message || 'Flowet blev oprettet', 'success');
            window.location.href = serverHost + platformLinks.admin.panelNotificationFlows + '/' + response.data.uid;
        } else {
            showSuccessToast(response.message || 'Flow gemt');
        }
    } else {
        showErrorToast(response.message || response.error?.message || 'Fejl ved gemning');
    }
}

function deleteFlow() {
    SweetPrompt.confirm('Slet flow?', 'Er du sikker på at du vil slette dette flow? Alle tilknyttede handlinger slettes også.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            var response = await post('api/admin/notifications/flows/delete', {uid: flowUid});
            if (response.status === 'success') {
                queueNotificationOnLoad('Flow slettet', 'Flowet blev slettet', 'success');
                window.location.href = serverHost + platformLinks.admin.panelNotificationFlows;
            } else {
                showErrorToast(response.message || response.error?.message || 'Fejl ved sletning');
            }
        }
    });
}

// Flow action functions
function showAddActionModal() {
    $('#addActionModal').modal('show');
}

async function addAction() {
    var form = document.getElementById('action-form');
    var formData = new FormData(form);
    var data = Object.fromEntries(formData.entries());

    if (!data.template) {
        showErrorToast('Vælg en skabelon');
        return;
    }

    if (isNewFlow) {
        // For new flows, store action locally
        var templateSelect = form.querySelector('select[name="template"]');
        var templateName = templateSelect.options[templateSelect.selectedIndex].text;
        var channelLabels = {'email': 'E-mail', 'sms': 'SMS', 'bell': 'Push'};

        pendingActions.push({
            template: data.template,
            templateName: templateName,
            channel: data.channel,
            channelLabel: channelLabels[data.channel] || data.channel
        });

        renderPendingActions();
        $('#addActionModal').modal('hide');
        form.reset();
    } else {
        // For existing flows, save to API
        var response = await post('api/admin/notifications/flows/actions/create', data);
        if (response.status === 'success') {
            location.reload();
        } else {
            showErrorToast(response.message || response.error?.message || 'Fejl ved oprettelse');
        }
    }
}

function renderPendingActions() {
    var tbody = document.getElementById('pending-actions-tbody');
    var noActionsMsg = document.getElementById('no-actions-message');
    var tableContainer = document.getElementById('actions-table-container');

    if (!tbody) return;

    if (pendingActions.length === 0) {
        noActionsMsg.style.display = 'block';
        tableContainer.style.display = 'none';
        return;
    }

    noActionsMsg.style.display = 'none';
    tableContainer.style.display = 'block';

    tbody.innerHTML = '';
    pendingActions.forEach(function(action, index) {
        var row = document.createElement('tr');
        row.innerHTML = `
            <td class="py-3"><span class="font-14">${action.templateName}</span></td>
            <td class="py-3"><span class="font-13">${action.channelLabel}</span></td>
            <td class="py-3 text-end">
                <button type="button" class="btn-v2 action-btn btn-sm" onclick="removePendingAction(${index})">
                    <i class="mdi mdi-delete-outline"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function removePendingAction(index) {
    pendingActions.splice(index, 1);
    renderPendingActions();
}

function deleteAction(uid) {
    SweetPrompt.confirm('Slet handling?', 'Er du sikker på at du vil slette denne handling?', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            var response = await post('api/admin/notifications/flows/actions/delete', {uid: uid});
            if (response.status === 'success') {
                queueNotificationOnLoad('Handling slettet', 'Handlingen blev slettet', 'success');
                location.reload();
            } else {
                showErrorToast(response.message || response.error?.message || 'Fejl ved sletning');
            }
        }
    });
}

// Toast helpers - use built-in notification system
function showSuccessToast(message) {
    showSuccessNotification('Success', message);
}

function showErrorToast(message) {
    showErrorNotification('Fejl', message);
}
