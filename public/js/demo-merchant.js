/**
 * Demo Merchant POS JavaScript
 * Handles the merchant/cashier side of the demo flow
 */

const DemoMerchant = {
    sessionInterval: null,
    statusInterval: null,
    customerAppeared: false,
    elements: {
        awaitingContainer: null,
        sessionContainer: null,
        sessionTableBody: null,
        statusText: null,
    },

    /**
     * Initialize on start page
     */
    initStart() {
        this.elements.awaitingContainer = document.getElementById('demo-awaiting-customers');
        this.elements.sessionContainer = document.getElementById('demo-session-container');
        this.elements.sessionTableBody = document.getElementById('demo-session-body');

        // Simulate customer appearance after 2 seconds
        setTimeout(() => {
            this.simulateCustomerAppearance();
        }, 2000);

        // Start polling for sessions
        this.sessionInterval = setInterval(() => this.fetchSessions(), 1500);
        this.fetchSessions();
    },

    /**
     * Simulate customer appearance
     */
    async simulateCustomerAppearance() {
        if (this.customerAppeared) return;

        const result = await post(platformLinks.api.demo.posCustomerAppear, {});
        if (result.status === 'success') {
            this.customerAppeared = true;
            this.fetchSessions();
        }
    },

    /**
     * Fetch sessions for the table
     */
    async fetchSessions() {
        const result = await get(platformLinks.api.demo.posSessions);
        if (result.status === 'error') {
            console.error('Failed to fetch sessions:', result);
            return;
        }

        const sessions = result.data.sessions || [];

        if (sessions.length === 0) {
            if (this.elements.sessionContainer) this.elements.sessionContainer.style.display = 'none';
            if (this.elements.awaitingContainer) this.elements.awaitingContainer.style.display = 'flex';
            return;
        }

        if (this.elements.sessionContainer) this.elements.sessionContainer.style.display = 'block';
        if (this.elements.awaitingContainer) this.elements.awaitingContainer.style.display = 'none';

        // Render sessions
        if (this.elements.sessionTableBody) {
            let html = '';
            sessions.forEach(session => {
                html += `<tr data-id="${session.uid}">`;
                html += `<td><span class="design-box font-14">${session.session}</span></td>`;
                html += `<td>${session.customer.name}</td>`;
                html += `<td>${session.dateFormat}</td>`;
                html += `<td>`;
                if (session.state === 'ACTIVE') {
                    html += `<span class="color-green font-weight-bold">${session.state}</span>`;
                } else {
                    html += `<span class="color-design-blue">${session.state}</span>`;
                }
                html += `</td>`;
                html += `<td>`;
                html += `<a href="${session.link}" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">`;
                html += `<i class="mdi mdi-play-outline font-18"></i>`;
                html += `<span class="font-14">Start</span>`;
                html += `</a>`;
                html += `</td>`;
                html += `</tr>`;
            });
            this.elements.sessionTableBody.innerHTML = html;
        }
    },

    /**
     * Initialize on details page
     */
    initDetails() {
        const form = document.getElementById('demo-basket-form');
        if (form) {
            form.addEventListener('submit', (e) => this.createBasket(e));
        }
    },

    /**
     * Create basket from form
     */
    async createBasket(e) {
        e.preventDefault();
        const form = e.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData(form);
        const data = {
            name: formData.get('name'),
            price: formData.get('price'),
            note: formData.get('note') || '',
        };

        const result = await post(platformLinks.api.demo.posBasket, data);
        if (result.status === 'error') {
            showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
            btn.disabled = false;
            return;
        }

        showSuccessNotification('Kurv oprettet', 'Afventer nu kunde handling');

        if (result.data.redirect) {
            window.location.href = result.data.redirect;
        }
    },

    /**
     * Initialize on checkout page
     */
    initCheckout() {
        this.elements.statusText = document.getElementById('demo-session-status');

        // Start polling for session status
        this.statusInterval = setInterval(() => this.checkSessionStatus(), 1500);
        this.checkSessionStatus();
    },

    /**
     * Check session status for completion
     */
    async checkSessionStatus() {
        const result = await get(platformLinks.api.demo.posSessionStatus);
        if (result.status === 'error') {
            console.error('Failed to check status:', result);
            return;
        }

        if (this.elements.statusText) {
            this.elements.statusText.textContent = result.data.statusTitle;
        }

        if (result.data.state === 'COMPLETED') {
            clearInterval(this.statusInterval);
            this.statusInterval = null;

            if (result.redirect) {
                queueNotificationOnLoad('Ordre fulfort', result.message || 'Kunden har betalt!', 'success');
                window.location.href = result.redirect;
            }
        }
    },

    /**
     * Cleanup intervals
     */
    destroy() {
        if (this.sessionInterval) {
            clearInterval(this.sessionInterval);
            this.sessionInterval = null;
        }
        if (this.statusInterval) {
            clearInterval(this.statusInterval);
            this.statusInterval = null;
        }
    }
};

// Auto-initialize based on page
document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path.includes('/demo/cashier/details')) {
        DemoMerchant.initDetails();
    } else if (path.includes('/demo/cashier/checkout')) {
        DemoMerchant.initCheckout();
    } else if (path.includes('/demo/cashier') && !path.includes('/details') && !path.includes('/checkout') && !path.includes('/fulfilled')) {
        DemoMerchant.initStart();
    }
});

// Cleanup on page leave
window.addEventListener('beforeunload', () => {
    DemoMerchant.destroy();
});
