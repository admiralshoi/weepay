/**
 * Demo Consumer JavaScript
 * Handles the consumer/customer side of the demo flow
 */

const DemoConsumer = {
    basketInterval: null,
    selectedPlan: 'full',
    elements: {
        basketContainer: null,
        waitingContainer: null,
        payButton: null,
        toPayNow: null,
    },

    /**
     * Initialize on start page (MitID simulation)
     */
    initStart() {
        const loginBtn = document.getElementById('demo-mitid-login');
        if (loginBtn) {
            loginBtn.addEventListener('click', () => this.simulateLogin());
        }
    },

    /**
     * Simulate MitID login
     */
    async simulateLogin() {
        const btn = document.getElementById('demo-mitid-login');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Logger ind...';
        }

        // Simulate a brief delay for realism
        await new Promise(resolve => setTimeout(resolve, 1500));

        const result = await post(platformLinks.api.demo.customerSimulateLogin, {});
        if (result.status === 'error') {
            showErrorNotification('Fejl', result.error?.message || 'Login fejlede');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="mdi mdi-shield-outline font-18"></i> Simuler MitID Login';
            }
            return;
        }

        showSuccessNotification('Logget ind', 'Du er nu logget ind som ' + (result.data.customer?.name || 'Demo bruger'));

        if (result.data.redirect) {
            window.location.href = result.data.redirect;
        }
    },

    /**
     * Initialize on info page (waiting for basket)
     */
    initInfo() {
        this.elements.basketContainer = document.getElementById('demo-basket-info');
        this.elements.waitingContainer = document.getElementById('demo-waiting-basket');

        // Start polling for basket
        this.basketInterval = setInterval(() => this.checkBasket(), 1500);
        this.checkBasket();
    },

    /**
     * Check for basket availability
     */
    async checkBasket() {
        const result = await get(platformLinks.api.demo.customerBasket);
        if (result.status === 'error') {
            console.error('Failed to check basket:', result);
            return;
        }

        if (result.data.hasBasket && result.data.basket) {
            // Show basket info
            if (this.elements.waitingContainer) {
                this.elements.waitingContainer.style.display = 'none';
            }
            if (this.elements.basketContainer) {
                this.elements.basketContainer.style.display = 'block';

                // Update basket details
                const nameEl = document.getElementById('demo-basket-name');
                const priceEl = document.getElementById('demo-basket-price');
                if (nameEl) nameEl.textContent = result.data.basket.name;
                if (priceEl) priceEl.textContent = parseFloat(result.data.basket.price).toLocaleString('da-DK', { minimumFractionDigits: 2 }) + ' kr.';
            }

            // Stop polling
            clearInterval(this.basketInterval);
            this.basketInterval = null;
        }
    },

    /**
     * Initialize on choose plan page
     */
    initChoosePlan() {
        this.elements.payButton = document.getElementById('demo-pay-button');
        this.elements.toPayNow = document.getElementById('demo-to-pay-now');

        // Bind payment card selection
        const paymentCards = document.querySelectorAll('.payment-card');
        paymentCards.forEach(card => {
            card.addEventListener('click', () => this.selectPlan(card));
        });

        // Bind pay button
        if (this.elements.payButton) {
            this.elements.payButton.addEventListener('click', () => this.pay());
        }

        // Bind terms checkbox
        const termsCheckbox = document.querySelector('input[name="accept_terms"]');
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', (e) => {
                if (this.elements.payButton) {
                    this.elements.payButton.disabled = !e.target.checked;
                }
            });
        }
    },

    /**
     * Select a payment plan
     */
    selectPlan(card) {
        // Update visual selection
        document.querySelectorAll('.payment-card').forEach(c => {
            c.classList.remove('payment-card--selected');
        });
        card.classList.add('payment-card--selected');

        // Update radio
        const radio = card.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            this.selectedPlan = radio.value;
        }

        // Update to pay now amount
        const toPayNow = card.dataset.toPayNow;
        if (toPayNow && this.elements.toPayNow) {
            this.elements.toPayNow.textContent = parseFloat(toPayNow).toLocaleString('da-DK', { minimumFractionDigits: 2 });
        }

        // Update consent text based on plan type
        const consentBnpl = document.getElementById('consent-bnpl');
        const consentDirect = document.getElementById('consent-direct');
        if (consentBnpl && consentDirect) {
            if (this.selectedPlan === 'direct') {
                consentBnpl.style.display = 'none';
                consentDirect.style.display = 'inline';
            } else {
                consentBnpl.style.display = 'inline';
                consentDirect.style.display = 'none';
            }
        }
    },

    /**
     * Process payment
     */
    async pay() {
        const termsCheckbox = document.querySelector('input[name="accept_terms"]');
        if (!termsCheckbox || !termsCheckbox.checked) {
            showErrorNotification('Accepter betingelser', 'Du skal acceptere handelsbetingelserne for at fortsaette');
            return;
        }

        if (this.elements.payButton) {
            this.elements.payButton.disabled = true;
            const loader = document.getElementById('demo-payment-loader');
            if (loader) loader.style.display = 'inline-flex';
        }

        // Simulate payment processing delay
        await new Promise(resolve => setTimeout(resolve, 2000));

        const result = await post(platformLinks.api.demo.customerPay, {
            plan: this.selectedPlan,
        });

        if (result.status === 'error') {
            showErrorNotification('Betaling fejlede', result.error?.message || 'Der opstod en fejl');
            if (this.elements.payButton) {
                this.elements.payButton.disabled = false;
                const loader = document.getElementById('demo-payment-loader');
                if (loader) loader.style.display = 'none';
            }
            return;
        }

        queueNotificationOnLoad('Betaling gennemfort', 'Din ordre er nu bekraeftet!', 'success');

        if (result.data.redirect) {
            window.location.href = result.data.redirect;
        }
    },

    /**
     * Cleanup intervals
     */
    destroy() {
        if (this.basketInterval) {
            clearInterval(this.basketInterval);
            this.basketInterval = null;
        }
    }
};

// Auto-initialize based on page
document.addEventListener('DOMContentLoaded', () => {
    const path = window.location.pathname;

    if (path.includes('/demo/consumer/info')) {
        DemoConsumer.initInfo();
    } else if (path.includes('/demo/consumer/choose-plan')) {
        DemoConsumer.initChoosePlan();
    } else if (path.endsWith('/demo/consumer') || path.endsWith('/demo/consumer/')) {
        DemoConsumer.initStart();
    }
});

// Cleanup on page leave
window.addEventListener('beforeunload', () => {
    DemoConsumer.destroy();
});
