/**
 * Admin Panel Cron Jobs JS
 */

var logsModal = null;
var currentLogType = null;
var currentLogName = null;

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
    currentLogType = type;
    currentLogName = name;

    // Update modal title
    document.getElementById('logsModalTitle').textContent = name + ' - Logs';
    document.getElementById('logsContent').innerHTML = 'Henter logs...';
    document.getElementById('logDateInfo').textContent = '';

    // Show modal first
    logsModal.modal('show');

    // Load available dates
    await loadLogDates(type);

    // Load logs for today (default)
    await loadLogsForDate();
}

async function loadLogDates(type) {
    var selector = document.getElementById('logDateSelector');
    selector.innerHTML = '<option value="">Henter datoer...</option>';

    try {
        var result = await get('api/admin/cronjobs/log-dates?type=' + encodeURIComponent(type));
        if (result.status === 'success') {
            var dates = result.dates || [];
            var today = new Date().toISOString().split('T')[0];

            // If no dates available, add today as option
            if (dates.length === 0) {
                dates = [today];
            }

            // Build options
            var options = '';
            dates.forEach(function(date, index) {
                var isToday = date === today;
                var label = formatDateLabel(date) + (isToday ? ' (i dag)' : '');
                var selected = index === 0 ? 'selected' : '';
                options += '<option value="' + date + '" ' + selected + '>' + label + '</option>';
            });

            selector.innerHTML = options;
            rebuildSelectV2UI(selector);
        } else {
            selector.innerHTML = '<option value="">Ingen datoer fundet</option>';
        }
    } catch (error) {
        selector.innerHTML = '<option value="">Fejl ved hentning</option>';
    }
}

async function loadLogsForDate() {
    var selector = document.getElementById('logDateSelector');
    var date = selector.value;
    var logsContent = document.getElementById('logsContent');
    var dateInfo = document.getElementById('logDateInfo');

    if (!currentLogType) return;

    logsContent.innerHTML = 'Henter logs...';

    try {
        var url = 'api/admin/cronjobs/logs?type=' + encodeURIComponent(currentLogType);
        if (date) {
            url += '&date=' + encodeURIComponent(date);
        }

        var result = await get(url);
        if (result.status === 'success') {
            showLogsContent(result.logs);
            dateInfo.textContent = 'Viser logs for ' + formatDateLabel(result.selectedDate || date);
        } else {
            logsContent.innerHTML = '<p class="color-gray">Kunne ikke hente logs: ' + (result.message || 'Ukendt fejl') + '</p>';
        }
    } catch (error) {
        logsContent.innerHTML = '<p class="color-gray">Der opstod en fejl ved hentning af logs</p>';
    }
}

function showLogsContent(logs) {
    var logsContent = document.getElementById('logsContent');

    // Format the log content
    var content = '';
    if (logs && logs.log) {
        content = logs.log;
    } else {
        content = '<p class="color-gray">Ingen logs fundet for denne dato</p>';
    }

    logsContent.innerHTML = content;
}

function formatDateLabel(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return parts[2] + '/' + parts[1] + '/' + parts[0]; // DD/MM/YYYY
}

function formatTimeGap(seconds) {
    if (seconds < 60) return seconds + ' sek';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' min';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' timer';
    return Math.floor(seconds / 86400) + ' dage';
}
