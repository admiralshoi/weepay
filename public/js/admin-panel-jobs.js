/**
 * Admin Panel Cron Jobs JS
 */

var logsModal = null;

function initPanelJobs() {
    logsModal = $('#logsModal');
}

async function forceRunCronjob(type, button) {
    if (!confirm('Er du sikker på at du vil køre denne cronjob nu?')) return;

    var originalText = button.innerHTML;
    button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i>';
    button.disabled = true;

    try {
        var result = await post('api/admin/cronjobs/force-run', { type: type });
        if (result.status === 'success') {
            showSuccessNotification('Cronjob kørt', result.message || 'Cronjob blev kørt succesfuldt');
            // Reload page to show updated timestamps
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            showErrorNotification('Fejl', result.message || 'Kunne ikke køre cronjob');
        }
    } catch (error) {
        showErrorNotification('Fejl', 'Der opstod en fejl');
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

async function viewLogs(type, name) {
    try {
        var result = await get('api/admin/cronjobs/logs?type=' + encodeURIComponent(type));
        if (result.status === 'success') {
            showLogsModal(name, result.logs);
        } else {
            showErrorNotification('Fejl', result.message || 'Kunne ikke hente logs');
        }
    } catch (error) {
        showErrorNotification('Fejl', 'Der opstod en fejl');
    }
}

function showLogsModal(name, logs) {
    var modalTitle = document.getElementById('logsModalTitle');
    var logsContent = document.getElementById('logsContent');

    modalTitle.textContent = name + ' - Logs';

    // Format the log content
    var content = '';
    if (logs.log) {
        content = logs.log;
    } else {
        content = '<p class="color-gray">Ingen logs fundet</p>';
    }

    logsContent.innerHTML = content;
    logsModal.modal('show');
}

function formatTimeGap(seconds) {
    if (seconds < 60) return seconds + ' sek';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' timer';
    return Math.floor(seconds / 86400) + ' dage';
}
