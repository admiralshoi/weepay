/**
 * Merchant Dashboard - Requires Attention Notifications
 */

let attentionNotificationsExpanded = false;

document.addEventListener('DOMContentLoaded', function() {
    fetchAttentionNotifications();
});

async function fetchAttentionNotifications() {
    const result = await get(platformLinks.api.merchant.attentionNotifications);

    if (result.status === 'success' && result.data && result.data.count > 0) {
        renderAttentionNotifications(result.data.notifications, result.data.count);
        document.getElementById('attention-notifications-container').style.display = 'block';
    } else {
        document.getElementById('attention-notifications-container').style.display = 'none';
    }
}

function renderAttentionNotifications(notifications, count) {
    document.getElementById('attention-count').textContent = count;

    const listContainer = document.getElementById('attention-notifications-list');
    listContainer.innerHTML = '';

    notifications.forEach(notification => {
        const severityBox = notification.severity === 'critical' ? 'danger-box' : (notification.severity === 'warning' ? 'warning-box' : 'info-box');
        const severityLabel = notification.severity === 'critical' ? 'Kritisk' : (notification.severity === 'warning' ? 'Advarsel' : 'Info');
        const sourceLabel = getSourceLabel(notification.source);

        const card = document.createElement('div');
        card.className = 'border-radius-8px bg-white border p-3 mb-2';
        card.id = `notification-${notification.uid}`;
        card.innerHTML = `
            <div class="flex-col-start">
                <div class="flex-row-between flex-align-center mb-2">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                        <span class="${severityBox} font-11">${severityLabel}</span>
                        <span class="info-box font-11">${sourceLabel}</span>
                    </div>
                    <button onclick="resolveAttentionNotification('${notification.uid}')" class="btn-v2 action-btn btn-sm flex-row-center flex-align-center flex-shrink-0" style="gap: .25rem;">
                        <i class="mdi mdi-check"></i>
                        <span>Marker løst</span>
                    </button>
                </div>
                <p class="mb-1 font-14 font-weight-bold color-dark">${escapeHtml(notification.title)}</p>
                <p class="mb-2 font-13 color-gray">${escapeHtml(notification.message).replace(/\\n/g, '<br>')}</p>
                ${notification.error_context ? `
                    <details class="mb-2">
                        <summary class="font-12 color-muted cursor-pointer">Tekniske detaljer</summary>
                        <pre class="mt-2 p-2 bg-light border-radius-5px font-11 overflow-auto" style="max-height: 200px;">${escapeHtml(JSON.stringify(notification.error_context, null, 2))}</pre>
                    </details>
                ` : ''}
                <p class="mb-0 font-11 color-muted">
                    <i class="mdi mdi-clock-outline"></i> ${formatDate(notification.created_at)}
                </p>
            </div>
        `;
        listContainer.appendChild(card);
    });
}

function getSourceLabel(source) {
    const labels = {
        'payment': 'Betaling',
        'php_error': 'PHP Fejl',
        'cronjob': 'Cronjob',
        'api': 'API',
        'webhook': 'Webhook',
        'other': 'Andet'
    };
    return labels[source] || source;
}

function toggleAttentionNotifications() {
    attentionNotificationsExpanded = !attentionNotificationsExpanded;
    const list = document.getElementById('attention-notifications-list');
    const icon = document.getElementById('attention-toggle-icon');

    if (attentionNotificationsExpanded) {
        list.style.display = 'block';
        icon.classList.remove('mdi-chevron-down');
        icon.classList.add('mdi-chevron-up');
    } else {
        list.style.display = 'none';
        icon.classList.remove('mdi-chevron-up');
        icon.classList.add('mdi-chevron-down');
    }
}

function resolveAttentionNotification(uid) {
    SweetPrompt.confirm('Marker som løst?', 'Er du sikker på at du vil markere denne som løst?', {
        confirmButtonText: 'Ja, marker som løst',
        onConfirm: async function() {
            const response = await post(platformLinks.api.merchant.attentionNotificationsResolve.replace('{uid}', uid), {});

            if (response.status === 'success') {
                // Remove the notification from the list
                const notificationEl = document.getElementById(`notification-${uid}`);
                if (notificationEl) {
                    notificationEl.remove();
                }

                // Update count
                const countEl = document.getElementById('attention-count');
                const currentCount = parseInt(countEl.textContent);
                const newCount = currentCount - 1;
                countEl.textContent = newCount;

                // Hide container if no more notifications
                if (newCount <= 0) {
                    document.getElementById('attention-notifications-container').style.display = 'none';
                }

                return { status: 'success' };
            } else {
                return { status: 'error', error: response.error?.message || 'Kunne ikke markere som løst' };
            }
        },
        success: { title: 'Løst', text: 'Notification markeret som løst' },
        error: { title: 'Fejl', text: '<_ERROR_MSG_>' },
        refireAfter: false
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('da-DK', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
