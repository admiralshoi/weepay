/**
 * Merchant Dashboard - Pending Validation Refunds
 */

let pendingRefundsExpanded = false;

document.addEventListener('DOMContentLoaded', function() {
    fetchPendingRefunds();
});

async function fetchPendingRefunds() {
    const result = await get(platformLinks.api.merchant.pendingValidationRefunds);

    if (result.status === 'success' && result.data && result.data.count > 0) {
        renderPendingRefunds(result.data.refunds, result.data.count);
        document.getElementById('pending-refunds-container').style.display = 'block';
    } else {
        document.getElementById('pending-refunds-container').style.display = 'none';
    }
}

function renderPendingRefunds(refunds, count) {
    document.getElementById('pending-refunds-count').textContent = count;

    const listContainer = document.getElementById('pending-refunds-list');
    listContainer.innerHTML = '';

    // Add explanation text
    const explanation = document.createElement('div');
    explanation.className = 'mb-3 p-3 bg-lighter-blue border-radius-8px';
    explanation.innerHTML = `
        <p class="mb-2 font-13 color-dark">
            <i class="mdi mdi-information-outline me-1 color-design-blue"></i>
            <strong>Hvad er dette?</strong>
        </p>
        <p class="mb-0 font-12 color-gray">
            Ved kortvalidering opkræves 1 ${refunds[0]?.currency || 'DKK'} midlertidigt, som straks refunderes automatisk.
            Disse refunderinger fejlede og skal håndteres manuelt via Viva Wallet dashboardet.
            Klik "Marker som refunderet" når du har gennemført refunderingen manuelt.
        </p>
    `;
    listContainer.appendChild(explanation);

    refunds.forEach(refund => {
        const testLabel = refund.test ? '<span class="mute-box font-11">Test</span>' : '';

        // Determine context: order checkout or card change
        let contextHtml = '';
        if (refund.order_uid) {
            const orderLink = `<a href="/orders/${refund.order_uid}" class="color-design-blue">${refund.order_name || refund.order_uid}</a>`;
            contextHtml = `
                <div class="flex-col-start">
                    <p class="mb-0 font-11 color-muted">Relateret ordre</p>
                    <p class="mb-0 font-14">${orderLink}</p>
                </div>
            `;
        } else {
            contextHtml = `
                <div class="flex-col-start">
                    <p class="mb-0 font-11 color-muted">Kontekst</p>
                    <p class="mb-0 font-14"><i class="mdi mdi-credit-card-refresh-outline me-1"></i>Kortskift</p>
                </div>
            `;
        }

        const card = document.createElement('div');
        card.className = 'border-radius-8px bg-white border p-3 mb-2';
        card.id = `pending-refund-${refund.uid}`;
        card.innerHTML = `
            <div class="flex-col-start">
                <div class="flex-row-between flex-align-center mb-2">
                    <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                        <span class="info-box font-11">Afventer handling</span>
                        ${testLabel}
                    </div>
                    <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                        <button onclick="attemptRefund('${refund.uid}')" class="btn-v2 action-btn btn-sm flex-row-center flex-align-center flex-shrink-0" style="gap: .25rem;">
                            <i class="mdi mdi-cash-refund"></i>
                            <span>Refunder</span>
                        </button>
                        <button onclick="markRefundAsRefunded('${refund.uid}')" class="btn-v2 mute-btn btn-sm flex-row-center flex-align-center flex-shrink-0" style="gap: .25rem;">
                            <i class="mdi mdi-check"></i>
                            <span>Marker som refunderet</span>
                        </button>
                    </div>
                </div>
                <div class="flex-row-start flex-align-center flex-wrap mb-2" style="gap: 1rem;">
                    <div class="flex-col-start">
                        <p class="mb-0 font-11 color-muted">Beløb</p>
                        <p class="mb-0 font-14 font-weight-bold">${refund.amount} ${refund.currency}</p>
                    </div>
                    ${contextHtml}
                    ${refund.location_name ? `
                        <div class="flex-col-start">
                            <p class="mb-0 font-11 color-muted">Lokation</p>
                            <p class="mb-0 font-14">${escapeHtml(refund.location_name)}</p>
                        </div>
                    ` : ''}
                </div>
                ${refund.failure_reason ? `
                    <p class="mb-2 font-12 color-danger">
                        <i class="mdi mdi-alert-circle-outline me-1"></i>
                        ${escapeHtml(refund.failure_reason)}
                    </p>
                ` : ''}
                <p class="mb-0 font-11 color-muted">
                    <i class="mdi mdi-clock-outline"></i> ${formatDatePendingRefunds(refund.created_at)}
                </p>
            </div>
        `;
        listContainer.appendChild(card);
    });
}

function togglePendingRefunds() {
    pendingRefundsExpanded = !pendingRefundsExpanded;
    const list = document.getElementById('pending-refunds-list');
    const icon = document.getElementById('pending-refunds-toggle-icon');

    if (pendingRefundsExpanded) {
        list.style.display = 'block';
        icon.classList.remove('mdi-chevron-down');
        icon.classList.add('mdi-chevron-up');
    } else {
        list.style.display = 'none';
        icon.classList.remove('mdi-chevron-up');
        icon.classList.add('mdi-chevron-down');
    }
}

function attemptRefund(uid) {
    SweetPrompt.confirm('Refunder via Viva?', 'Dette vil forsøge at refundere beløbet via Viva Wallet API. Fortsæt?', {
        confirmButtonText: 'Ja, refunder nu',
        refireAfter: true,
        onConfirm: async function() {
            const response = await post(platformLinks.api.merchant.pendingValidationRefundsAttemptRefund.replace('{uid}', uid), {});

            if (response.status === 'success') {
                queueNotificationOnLoad('Refunderet', 'Beløbet er blevet refunderet', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Refundering fejlede', response.error?.message || 'Kunne ikke gennemføre refundering');
            }
        }
    });
}

function markRefundAsRefunded(uid) {
    SweetPrompt.confirm('Marker som refunderet?', 'Bekræft at du allerede har gennemført refunderingen manuelt via Viva Wallet Dashboard. Denne handling kan ikke fortrydes.', {
        confirmButtonText: 'Ja, jeg har refunderet manuelt',
        cancelButtonText: 'Annuller',
        onConfirm: async function() {
            const response = await post(platformLinks.api.merchant.pendingValidationRefundsMarkRefunded.replace('{uid}', uid), {});

            if (response.status === 'success') {
                // Remove the refund from the list
                const refundEl = document.getElementById(`pending-refund-${uid}`);
                if (refundEl) {
                    refundEl.remove();
                }

                // Update count
                const countEl = document.getElementById('pending-refunds-count');
                const currentCount = parseInt(countEl.textContent);
                const newCount = currentCount - 1;
                countEl.textContent = newCount;

                // Hide container if no more pending refunds
                if (newCount <= 0) {
                    document.getElementById('pending-refunds-container').style.display = 'none';
                }

                showSuccessNotification('Markeret', 'Markeret som manuelt refunderet');
                return { status: 'success' };
            } else {
                showErrorNotification('Fejl', response.error?.message || 'Kunne ikke markere som refunderet');
                return { status: 'error', error: response.error?.message || 'Kunne ikke markere som refunderet' };
            }
        },
        refireAfter: false
    });
}

// Utility functions (in case not already defined)
if (typeof escapeHtml !== 'function') {
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

function formatDatePendingRefunds(dateString) {
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
