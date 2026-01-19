/**
 * Consumer Change Card Page JavaScript
 * Handles loading payment groups by card and initiating card changes
 */

(function() {
    'use strict';

    // Elements
    const loadingEl = document.getElementById('change-card-loading');
    const emptyEl = document.getElementById('change-card-empty');
    const groupsEl = document.getElementById('change-card-groups');
    const groupsListEl = document.getElementById('card-groups-list');
    const totalPaymentsCountEl = document.getElementById('total-payments-count');
    const totalCardsCountEl = document.getElementById('total-cards-count');

    // Templates
    const cardGroupTemplate = document.getElementById('card-group-template');
    const paymentRowTemplate = document.getElementById('payment-row-template');

    // Status labels mapping (using project's box classes)
    const statusLabels = {
        'PENDING': { text: 'Afventer', class: 'warning-box' },
        'SCHEDULED': { text: 'Planlagt', class: 'mute-box' },
        'PAST_DUE': { text: 'Forsinket', class: 'danger-box' },
        'FAILED': { text: 'Fejlet', class: 'danger-box' },
        'DRAFT': { text: 'Kladde', class: 'mute-box' }
    };

    // Card brand icons
    const brandIcons = {
        'Visa': 'mdi-credit-card',
        'Mastercard': 'mdi-credit-card',
        'American Express': 'mdi-credit-card',
        'Diners Club': 'mdi-credit-card',
        'Discover': 'mdi-credit-card',
        'JCB': 'mdi-credit-card'
    };

    /**
     * Initialize the page
     */
    function init() {
        loadPaymentsByCard();
    }

    /**
     * Load payments grouped by card from API
     */
    async function loadPaymentsByCard() {
        showLoading();

        try {
            const data = await post(platformLinks.api.consumer.paymentsByCard, {});

            if (data.status === 'success' && data.data) {
                renderCardGroups(data.data.groups, data.data.totalPayments);
            } else {
                showError(data.message || 'Kunne ikke indlæse betalinger');
            }
        } catch (error) {
            console.error('Error loading payments by card:', error);
            showError('Der opstod en fejl. Prøv igen senere.');
        }
    }

    /**
     * Render card groups
     */
    function renderCardGroups(groups, totalPayments) {
        if (!groups || groups.length === 0) {
            showEmpty();
            return;
        }

        // Update summary
        totalPaymentsCountEl.textContent = totalPayments;
        totalCardsCountEl.textContent = groups.length;

        // Clear existing groups
        groupsListEl.innerHTML = '';

        // Render each group
        groups.forEach(group => {
            const groupEl = renderCardGroup(group);
            groupsListEl.appendChild(groupEl);
        });

        showGroups();
    }

    /**
     * Render a single card group
     */
    function renderCardGroup(group) {
        const template = cardGroupTemplate.content.cloneNode(true);
        const container = template.querySelector('.card-group-item');

        // Set payment method ID
        container.dataset.paymentMethod = group.payment_method || 'null';

        // Card title
        const titleEl = container.querySelector('.card-title-text');
        titleEl.textContent = group.title || 'Ukendt kort';

        // Card expiry
        const expiryEl = container.querySelector('.card-expiry-text');
        if (group.exp_month && group.exp_year) {
            expiryEl.textContent = `Udløber ${group.exp_month}/${group.exp_year}`;
        } else if (group.payment_method === null) {
            expiryEl.textContent = 'Betalinger uden tilknyttet kort';
        } else {
            expiryEl.textContent = '';
        }

        // Organisation name
        const orgEl = container.querySelector('.card-organisation-text');
        if (group.organisation) {
            orgEl.textContent = group.organisation;
        } else {
            orgEl.style.display = 'none';
        }

        // Card icon
        const iconEl = container.querySelector('.card-icon-container i');
        if (group.payment_method === null) {
            iconEl.className = 'mdi mdi-credit-card-off-outline font-30 color-gray';
        } else {
            iconEl.className = 'mdi mdi-credit-card font-30 color-primary-cta';
        }

        // Payment count
        container.querySelector('.payment-count').textContent = group.payment_count;

        // Total amount
        const totalAmount = formatCurrency(group.total_amount, group.currency);
        container.querySelector('.total-amount').textContent = totalAmount;

        // Render payments
        const tbody = container.querySelector('.payments-tbody');
        group.payments.forEach(payment => {
            const row = renderPaymentRow(payment);
            tbody.appendChild(row);
        });

        // Toggle payments visibility
        const toggleBtn = container.querySelector('.toggle-payments-btn');
        const paymentsList = container.querySelector('.payments-list-container');
        const toggleIcon = container.querySelector('.toggle-icon');
        const toggleText = container.querySelector('.toggle-text');

        toggleBtn.addEventListener('click', () => {
            const isHidden = paymentsList.classList.contains('d-none');
            paymentsList.classList.toggle('d-none');
            toggleIcon.classList.toggle('mdi-chevron-down', !isHidden);
            toggleIcon.classList.toggle('mdi-chevron-up', isHidden);
            toggleText.textContent = isHidden ? 'Skjul betalinger' : 'Vis betalinger';
        });

        // Change card button
        const changeCardBtn = container.querySelector('.change-card-btn');
        changeCardBtn.addEventListener('click', () => {
            initiateCardChange(group.payment_method, group.organisation_uid);
        });

        return container;
    }

    /**
     * Render a payment row
     */
    function renderPaymentRow(payment) {
        const template = paymentRowTemplate.content.cloneNode(true);
        const row = template.querySelector('tr');

        // Make payment UID a clickable link
        const paymentLink = `${HOST}payments/${payment.uid}`;
        row.querySelector('.payment-uid').innerHTML = `<a href="${paymentLink}" class="color-primary-cta">${payment.uid}</a>`;

        row.querySelector('.payment-location').textContent = payment.location_name || '-';
        row.querySelector('.payment-amount').textContent = formatCurrency(payment.amount, payment.currency);
        row.querySelector('.payment-due-date').textContent = formatDate(payment.due_date);

        // Status box
        const statusInfo = statusLabels[payment.status] || { text: payment.status, class: 'mute-box' };
        row.querySelector('.payment-status').innerHTML = `<span class="${statusInfo.class}">${statusInfo.text}</span>`;

        return row;
    }

    /**
     * Initiate card change for a payment method
     */
    async function initiateCardChange(paymentMethodUid, organisationUid) {
        const url = `${platformLinks.api.consumer.changeCard}/payment-method/${paymentMethodUid || 'null'}`;

        // Show loading state on button
        const container = document.querySelector(`[data-payment-method="${paymentMethodUid || 'null'}"]`);
        const btn = container?.querySelector('.change-card-btn');
        const originalBtnHtml = btn?.innerHTML;

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>Indlæser...';
        }

        try {
            // Pass organisation_uid for no-card groups
            const body = {};
            if (!paymentMethodUid && organisationUid) {
                body.organisation_uid = organisationUid;
            }

            const data = await post(url, body);

            if (data.status === 'success' && data.data?.redirectUrl) {
                // Redirect to Viva checkout
                window.location.href = data.data.redirectUrl;
            } else {
                showToast(data.message || 'Kunne ikke starte kortskift', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            }
        } catch (error) {
            console.error('Error initiating card change:', error);
            showToast('Der opstod en fejl. Prøv igen senere.', 'error');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    }

    /**
     * Format currency amount
     */
    function formatCurrency(amount, currency) {
        const currencySymbols = {
            'DKK': 'kr',
            'EUR': '€',
            'USD': '$',
            'NOK': 'kr',
            'SEK': 'kr'
        };
        const symbol = currencySymbols[currency] || currency || 'kr';
        const formatted = parseFloat(amount).toLocaleString('da-DK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        return `${formatted} ${symbol}`;
    }

    /**
     * Format date
     */
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString('da-DK', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    /**
     * Show toast notification
     */
    function showToast(message, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type === 'error' ? 'error' : 'success'](message);
        } else {
            alert(message);
        }
    }

    /**
     * Show loading state
     */
    function showLoading() {
        loadingEl.classList.remove('d-none');
        emptyEl.classList.add('d-none');
        groupsEl.classList.add('d-none');
    }

    /**
     * Show empty state
     */
    function showEmpty() {
        loadingEl.classList.add('d-none');
        emptyEl.classList.remove('d-none');
        groupsEl.classList.add('d-none');
    }

    /**
     * Show groups
     */
    function showGroups() {
        loadingEl.classList.add('d-none');
        emptyEl.classList.add('d-none');
        groupsEl.classList.remove('d-none');
    }

    /**
     * Show error
     */
    function showError(message) {
        loadingEl.classList.add('d-none');
        emptyEl.classList.remove('d-none');
        emptyEl.querySelector('p.font-18').textContent = 'Fejl';
        emptyEl.querySelector('p.color-gray').textContent = message;
        groupsEl.classList.add('d-none');
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
