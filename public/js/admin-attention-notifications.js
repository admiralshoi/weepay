/**
 * Admin Panel - Requires Attention Notifications
 */

let adminAttentionNotificationsExpanded = false;

document.addEventListener('DOMContentLoaded', function() {
    fetchAdminAttentionNotifications();
});

async function fetchAdminAttentionNotifications() {
    const result = await get(platformLinks.api.admin.panel.attentionNotifications);

    if (result.status === 'success' && result.data && result.data.count > 0) {
        renderAdminAttentionNotifications(result.data.notifications, result.data.count, result.data.stats);
        document.getElementById('admin-attention-notifications-container').style.display = 'block';
    } else {
        document.getElementById('admin-attention-notifications-container').style.display = 'none';
    }
}

function renderAdminAttentionNotifications(notifications, count, stats) {
    document.getElementById('admin-attention-count').textContent = count;

    const listContainer = document.getElementById('admin-attention-notifications-list');
    listContainer.innerHTML = '';

    // Stats summary
    if (stats) {
        const statsSummary = document.createElement('div');
        statsSummary.className = 'flex-row-start flex-wrap mb-3';
        statsSummary.style.gap = '.5rem';
        statsSummary.innerHTML = `
            <span class="danger-box font-12">${stats.critical_count} Kritiske</span>
            <span class="warning-box font-12">${stats.warning_count} Advarsler</span>
            <span class="info-box font-12">${stats.unresolved_admin} Admin</span>
            <span class="action-box font-12">${stats.unresolved_merchant} Forhandler</span>
        `;
        listContainer.appendChild(statsSummary);
    }

    notifications.forEach(notification => {
        const severityBox = notification.severity === 'critical' ? 'danger-box' : (notification.severity === 'warning' ? 'warning-box' : 'info-box');
        const sourceLabel = getSourceLabel(notification.source);
        const audienceBox = notification.target_audience === 'admin' ? 'info-box' : 'action-box';
        const audienceLabel = notification.target_audience === 'admin' ? 'Admin' : 'Forhandler';

        // Get organisation info for merchant notifications
        const orgName = notification.organisation?.name || notification.organisation || null;
        const orgUid = notification.organisation?.uid || (typeof notification.organisation === 'string' ? notification.organisation : null);
        const orgLink = orgUid ? `${HOST}${platformLinks.admin.organisationDetail.replace('{uid}', orgUid)}` : null;

        const card = document.createElement('div');
        card.className = 'border-radius-8px bg-white border p-3 mb-2';
        card.id = `admin-notification-${notification.uid}`;
        card.innerHTML = `
            <div class="flex-col-start">
                <div class="flex-row-between flex-align-center mb-2">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                        <span class="${severityBox} font-11">${notification.severity === 'critical' ? 'Kritisk' : (notification.severity === 'warning' ? 'Advarsel' : 'Info')}</span>
                        <span class="info-box font-11">${sourceLabel}</span>
                        <span class="${audienceBox} font-11">${audienceLabel}</span>
                    </div>
                    <button onclick="resolveAdminAttentionNotification('${notification.uid}')" class="btn-v2 action-btn btn-sm flex-row-center flex-align-center flex-shrink-0" style="gap: .25rem;">
                        <i class="mdi mdi-check"></i>
                        <span>Marker løst</span>
                    </button>
                </div>
                ${notification.target_audience === 'merchant' && orgName ? `
                    <p class="mb-1 font-12 color-muted">
                        <i class="mdi mdi-domain"></i> Forhandler:
                        ${orgLink ? `<a href="${orgLink}" class="color-action font-weight-bold">${escapeHtml(orgName)}</a>` : escapeHtml(orgName)}
                    </p>
                ` : ''}
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

function toggleAdminAttentionNotifications() {
    adminAttentionNotificationsExpanded = !adminAttentionNotificationsExpanded;
    const list = document.getElementById('admin-attention-notifications-list');
    const icon = document.getElementById('admin-attention-toggle-icon');

    if (adminAttentionNotificationsExpanded) {
        list.style.display = 'block';
        icon.classList.remove('mdi-chevron-down');
        icon.classList.add('mdi-chevron-up');
    } else {
        list.style.display = 'none';
        icon.classList.remove('mdi-chevron-up');
        icon.classList.add('mdi-chevron-down');
    }
}

function resolveAdminAttentionNotification(uid) {
    SweetPrompt.confirm('Marker som løst?', 'Er du sikker på at du vil markere denne som løst?', {
        confirmButtonText: 'Ja, marker som løst',
        onConfirm: async function() {
            const response = await post(platformLinks.api.admin.panel.attentionNotificationsResolve.replace('{uid}', uid), {});

            if (response.status === 'success') {
                // Remove the notification from the list
                const notificationEl = document.getElementById(`admin-notification-${uid}`);
                if (notificationEl) {
                    notificationEl.remove();
                }

                // Update count
                const countEl = document.getElementById('admin-attention-count');
                const currentCount = parseInt(countEl.textContent);
                const newCount = currentCount - 1;
                countEl.textContent = newCount;

                // Hide container if no more notifications
                if (newCount <= 0) {
                    document.getElementById('admin-attention-notifications-container').style.display = 'none';
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
