/**
 * Consumer Payments Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 * Supports three views: Completed (kvitteringer), Upcoming (kommende), and Past Due (udestående)
 */
const ConsumerPaymentsPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let filterType = 'upcoming'; // Default to upcoming, will be overridden if past_due exists
    let sortColumn = 'date';
    let sortDirection = 'ASC'; // Default ascending for upcoming (soonest first)
    let startDate = '';
    let endDate = '';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $sort, $perPage, $dateRange, $dateRangeClear;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer, $table;
    let $typeButtons, $dateHeader, $statusHeader, $pastDueWarning;

    function init() {
        // Get DOM elements
        $tbody = $('#consumer-payments-tbody');
        $table = $('#consumer-payments-table');
        $search = $('#consumer-payments-search');
        $sort = $('#consumer-payments-sort');
        $perPage = $('#consumer-payments-per-page');
        $dateRange = $('#consumer-payments-daterange');
        $dateRangeClear = $('#consumer-payments-daterange-clear');
        $showing = $('#consumer-payments-showing');
        $total = $('#consumer-payments-total');
        $currentPageEl = $('#consumer-payments-current-page');
        $totalPages = $('#consumer-payments-total-pages');
        $pagination = $('#consumer-payments-pagination');
        $noResults = $('#consumer-payments-no-results');
        $paginationContainer = $('#consumer-payments-pagination-container');
        $typeButtons = $('.consumer-payment-type-btn');
        $dateHeader = $('#consumer-payments-date-header');
        $statusHeader = $('#consumer-payments-status-header');
        $pastDueWarning = $('#consumer-payments-past-due-warning');

        if (!$tbody.length || typeof consumerPaymentsApiUrl === 'undefined') return;

        // Determine initial tab: past_due if has outstanding, otherwise upcoming
        if (typeof consumerHasPastDue !== 'undefined' && consumerHasPastDue) {
            filterType = 'past_due';
            sortDirection = 'ASC'; // Oldest overdue first
        } else {
            filterType = 'upcoming';
            sortDirection = 'ASC'; // Soonest upcoming first
        }

        // Update button states to match initial filter type
        $typeButtons.removeClass('active action-btn').addClass('mute-btn');
        $typeButtons.filter(`[data-type="${filterType}"]`).removeClass('mute-btn').addClass('active action-btn');

        // Update sort dropdown to match
        $sort.val('date-ASC');

        // Initialize daterangepicker
        initDateRangePicker();

        // Bind events
        bindEvents();

        // Set initial UI state
        updateUIForType();

        // Initial load
        fetchPayments();
    }

    function initDateRangePicker() {
        if (!$dateRange.length || typeof $dateRange.daterangepicker !== 'function') return;

        $dateRange.daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY',
                separator: ' - ',
                applyLabel: 'Anvend',
                cancelLabel: 'Ryd',
                fromLabel: 'Fra',
                toLabel: 'Til',
                customRangeLabel: 'Brugerdefineret',
                weekLabel: 'U',
                daysOfWeek: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
                monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'],
                firstDay: 1
            },
            ranges: {
                'I dag': [moment(), moment()],
                'I går': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Sidste 7 dage': [moment().subtract(6, 'days'), moment()],
                'Sidste 30 dage': [moment().subtract(29, 'days'), moment()],
                'Denne måned': [moment().startOf('month'), moment().endOf('month')],
                'Sidste måned': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Næste 7 dage': [moment(), moment().add(6, 'days')],
                'Næste 30 dage': [moment(), moment().add(29, 'days')]
            }
        });

        $dateRange.on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            startDate = picker.startDate.format('YYYY-MM-DD');
            endDate = picker.endDate.format('YYYY-MM-DD');
            $dateRangeClear.removeClass('d-none');
            currentPage = 1;
            fetchPayments();
        });

        $dateRange.on('cancel.daterangepicker', function(ev, picker) {
            clearDateRange();
        });

        // Clear button click
        $dateRangeClear.on('click', function(e) {
            e.stopPropagation();
            clearDateRange();
        });
    }

    function clearDateRange() {
        $dateRange.val('');
        startDate = '';
        endDate = '';
        $dateRangeClear.addClass('d-none');
        currentPage = 1;
        fetchPayments();
    }

    function bindEvents() {
        // Type toggle buttons
        $typeButtons.on('click', function() {
            const $btn = $(this);
            const type = $btn.data('type');

            if (type === filterType) return;

            // Update button states
            $typeButtons.removeClass('active action-btn').addClass('mute-btn');
            $btn.removeClass('mute-btn').addClass('active action-btn');

            // Update type and reset
            filterType = type;
            currentPage = 1;

            // Set appropriate sort direction based on type
            if (type === 'completed') {
                // Completed: newest first
                sortDirection = 'DESC';
                $sort.val('date-DESC');
            } else {
                // Upcoming/Past Due: soonest/oldest first
                sortDirection = 'ASC';
                $sort.val('date-ASC');
            }

            // Update UI
            updateUIForType();

            // Fetch new data
            fetchPayments();
        });

        // Search with debounce
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchPayments();
            }, 400);
        });

        // Sort
        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchPayments();
        });

        // Per page
        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchPayments();
        });
    }

    function updateUIForType() {
        // Hide past due warning by default
        $pastDueWarning.addClass('d-none');

        if (filterType === 'completed') {
            $dateHeader.text('Betalt');
            $statusHeader.text('Status');
        } else if (filterType === 'past_due') {
            $dateHeader.text('Forfald');
            $statusHeader.text('Dage forsinket');
        } else {
            // Upcoming
            $dateHeader.text('Forfald');
            $statusHeader.text('Dage til');
        }
    }

    async function fetchPayments() {
        if (isLoading) return;
        isLoading = true;

        // Show loading state
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                filter_type: filterType,
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;

            const result = await post(consumerPaymentsApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            // Update state from response
            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            // Show past due warning if applicable
            if (data.hasPastDue) {
                $pastDueWarning.removeClass('d-none');
            } else {
                $pastDueWarning.addClass('d-none');
            }

            // Render payments
            renderPayments(data.payments);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching payments:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="consumer-payments-loading-row">
                <td colspan="6" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser betalinger...</p>
                    </div>
                </td>
            </tr>
        `);
        $noResults.addClass('d-none');
        $table.removeClass('d-none');
    }

    function showError(message) {
        $tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="ConsumerPaymentsPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderPayments(payments) {
        if (!payments || payments.length === 0) {
            $tbody.empty();
            $table.addClass('d-none');
            $noResults.removeClass('d-none');
            $paginationContainer.addClass('d-none');

            // Update no results message based on type
            let icon, message;
            if (filterType === 'completed') {
                icon = 'mdi-receipt';
                message = 'Ingen kvitteringer endnu';
            } else if (filterType === 'past_due') {
                icon = 'mdi-check-circle-outline';
                message = 'Ingen udestående betalinger - du er helt opdateret!';
                $noResults.find('i').removeClass('color-gray').addClass('color-green');
            } else {
                icon = 'mdi-calendar-clock';
                message = 'Ingen kommende betalinger';
            }
            $noResults.find('i').attr('class', `mdi ${icon} font-40 ${filterType === 'past_due' ? 'color-green' : 'color-gray'}`);
            $noResults.find('p').text(message);

            return;
        }

        $noResults.addClass('d-none');
        $table.removeClass('d-none');
        $paginationContainer.removeClass('d-none');

        let html = '';
        payments.forEach(function(payment) {
            html += renderPaymentRow(payment);
        });

        $tbody.html(html);
    }

    function renderPaymentRow(payment) {
        const currencySymbol = getCurrencySymbol(payment.currency);

        // Order link
        let orderHtml;
        if (payment.order_uid && payment.order_url) {
            orderHtml = `<a href="${payment.order_url}" class="color-blue hover-underline font-monospace">${escapeHtml(payment.order_uid.substring(0, 8))}</a>`;
        } else {
            orderHtml = `<span class="color-gray">N/A</span>`;
        }

        // Date column
        let dateHtml;
        if (filterType === 'completed') {
            dateHtml = payment.paid_at || '-';
        } else {
            dateHtml = payment.due_date;
        }

        // Status column
        let statusHtml;
        if (filterType === 'completed') {
            statusHtml = `<span class="${payment.status_class}">${payment.status_label}</span>`;
        } else if (filterType === 'past_due' && payment.days_info) {
            statusHtml = `
                <div class="flex-col-start">
                    <span class="danger-box">Forsinket</span>
                    <p class="mb-0 font-12 font-weight-bold color-red mt-1">${payment.days_info.days} dage</p>
                </div>
            `;
        } else if (payment.days_info) {
            // Upcoming
            if (payment.days_info.type === 'today') {
                statusHtml = `<span class="warning-box">I dag</span>`;
            } else if (payment.days_info.type === 'soon') {
                statusHtml = `<span class="action-box">${payment.days_info.days} dage</span>`;
            } else {
                statusHtml = `<span class="success-box">${payment.days_info.days} dage</span>`;
            }
        } else {
            statusHtml = `<span class="${payment.status_class}">${payment.status_label}</span>`;
        }

        // Amount color based on type
        let amountClass = '';
        if (filterType === 'completed') amountClass = 'color-success-text';
        else if (filterType === 'past_due') amountClass = 'color-red';

        // Row class for past due
        const rowClass = filterType === 'past_due' ? 'table-danger' : '';

        // Payment ID link
        let paymentIdHtml;
        if (payment.detail_url) {
            paymentIdHtml = `<a href="${payment.detail_url}" class="color-blue hover-underline font-monospace">${escapeHtml(payment.uid.substring(0, 8))}</a>`;
        } else {
            paymentIdHtml = `<span class="font-monospace">${escapeHtml(payment.uid.substring(0, 8))}</span>`;
        }

        return `
            <tr class="${rowClass}">
                <td class="text-sm">${paymentIdHtml}</td>
                <td class="text-sm">${orderHtml}</td>
                <td class="text-sm">${escapeHtml(payment.location_name)}</td>
                <td>
                    <p class="mb-0 text-sm font-weight-bold ${amountClass}">${formatNumber(payment.amount, 2)}${currencySymbol}</p>
                </td>
                <td class="text-sm">${dateHtml}</td>
                <td class="text-sm">${statusHtml}</td>
            </tr>
        `;
    }

    function getCurrencySymbol(currency) {
        const symbols = {
            'DKK': ' kr.',
            'EUR': ' €',
            'USD': ' $',
            'GBP': ' £',
            'SEK': ' kr.',
            'NOK': ' kr.'
        };
        return symbols[currency] || ` ${currency}`;
    }

    function formatNumber(num, decimals = 0) {
        return new Intl.NumberFormat('da-DK', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function renderPaginationInfo() {
        const startItem = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
        const endItem = Math.min(currentPage * perPage, totalItems);

        $showing.text(totalItems === 0 ? 0 : `${startItem}-${endItem}`);
        $total.text(totalItems);
        $currentPageEl.text(currentPage);
        $totalPages.text(totalPagesCount);
    }

    function renderPagination() {
        $pagination.empty();

        if (totalPagesCount <= 1) {
            return;
        }

        // Previous button
        const prevDisabled = currentPage === 1;
        $pagination.append(`
            <button class="pagination-btn ${prevDisabled ? 'disabled' : ''}"
                    ${prevDisabled ? 'disabled' : ''}
                    data-page="${currentPage - 1}">
                <i class="mdi mdi-chevron-left"></i>
            </button>
        `);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPagesCount, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // First page + ellipsis
        if (startPage > 1) {
            $pagination.append(`<button class="pagination-btn" data-page="1">1</button>`);
            if (startPage > 2) {
                $pagination.append(`<span class="pagination-ellipsis">...</span>`);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === currentPage;
            $pagination.append(`
                <button class="pagination-btn ${isActive ? 'active' : ''}" data-page="${i}">${i}</button>
            `);
        }

        // Last page + ellipsis
        if (endPage < totalPagesCount) {
            if (endPage < totalPagesCount - 1) {
                $pagination.append(`<span class="pagination-ellipsis">...</span>`);
            }
            $pagination.append(`<button class="pagination-btn" data-page="${totalPagesCount}">${totalPagesCount}</button>`);
        }

        // Next button
        const nextDisabled = currentPage === totalPagesCount;
        $pagination.append(`
            <button class="pagination-btn ${nextDisabled ? 'disabled' : ''}"
                    ${nextDisabled ? 'disabled' : ''}
                    data-page="${currentPage + 1}">
                <i class="mdi mdi-chevron-right"></i>
            </button>
        `);

        // Bind click events
        $pagination.find('.pagination-btn:not(.disabled)').on('click', function() {
            const page = parseInt($(this).data('page'));
            if (page !== currentPage && !isLoading) {
                currentPage = page;
                fetchPayments();
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $('#consumer-payments-table').offset().top - 100
                }, 200);
            }
        });
    }

    // Public API
    return {
        init: init,
        refresh: fetchPayments
    };
})();

// Initialize on document ready
$(document).ready(function() {
    ConsumerPaymentsPagination.init();
    ConsumerPaymentActions.init();
});


/**
 * Consumer Payment Actions - Pay Now & Change Card
 */
const ConsumerPaymentActions = (function() {

    function init() {
        bindPayNowButton();
        bindPayAllOutstandingButton();
        bindChangeCardButton();
        bindGlobalChangeCardButton();
        bindDownloadReceiptButton();
    }

    /**
     * Pay Now button handler (for PAST_DUE payments)
     * Located on payment-detail.php
     */
    function bindPayNowButton() {
        const $btn = $('#pay-now-btn');
        if (!$btn.length) return;

        $btn.on('click', async function() {
            const paymentUid = $(this).data('uid');
            if (!paymentUid) return;

            // URL is set via JS variable from view (using Links class)
            const url = typeof consumerPayNowUrl !== 'undefined'
                ? consumerPayNowUrl
                : `api/consumer/payments/${paymentUid}/pay-now`;

            screenLoader.show('Behandler betaling...');

            try {
                const result = await post(url);
                screenLoader.hide();

                if (result.status === 'success') {
                    queueNotificationOnLoad('Gennemført', 'Betaling gennemført!', 'success');
                    location.reload();
                } else {
                    const errorMsg = result.error?.message || result.message || 'Betaling fejlede';
                    showErrorNotification('Fejl', errorMsg);
                }
            } catch (error) {
                screenLoader.hide();
                console.error('Pay now error:', error);
                showErrorNotification('Fejl', 'Der opstod en netværksfejl');
            }
        });
    }

    /**
     * Pay All Outstanding button handler (for orders with PAST_DUE payments)
     * Located on order-detail.php
     */
    function bindPayAllOutstandingButton() {
        const $btn = $('#pay-all-outstanding-btn');
        if (!$btn.length) return;

        $btn.on('click', async function() {
            // URL is set via JS variable from view (using Links class)
            const url = typeof payOrderOutstandingUrl !== 'undefined'
                ? payOrderOutstandingUrl
                : null;

            if (!url) {
                showErrorNotification('Fejl', 'Mangler API URL');
                return;
            }

            screenLoader.show('Behandler betalinger...');

            try {
                const result = await post(url);
                screenLoader.hide();

                if (result.status === 'success') {
                    queueNotificationOnLoad('Gennemført', result.message || 'Betalinger gennemført!', 'success');
                    location.reload();
                } else {
                    const errorMsg = result.error?.message || result.message || 'Betaling fejlede';
                    showErrorNotification('Fejl', errorMsg);
                }
            } catch (error) {
                screenLoader.hide();
                console.error('Pay all outstanding error:', error);
                showErrorNotification('Fejl', 'Der opstod en netværksfejl');
            }
        });
    }

    /**
     * Change Card button handler (order-specific)
     * Located on payment-detail.php and order-detail.php
     */
    function bindChangeCardButton() {
        const $btn = $('#change-card-btn');
        if (!$btn.length) return;

        $btn.on('click', async function() {
            const orderUid = $(this).data('order');
            if (!orderUid) return;

            // URL is set via JS variable from view (using Links class)
            const url = typeof consumerChangeCardOrderUrl !== 'undefined'
                ? consumerChangeCardOrderUrl
                : `api/consumer/change-card/order/${orderUid}`;

            screenLoader.show('Starter kortskift...');

            try {
                const result = await post(url);

                if (result.status === 'success' && result.data?.checkoutUrl) {
                    // Redirect to Viva checkout
                    window.location.href = result.data.checkoutUrl;
                } else {
                    screenLoader.hide();
                    const errorMsg = result.error?.message || result.message || 'Kunne ikke starte kortskift';
                    showErrorNotification('Fejl', errorMsg);
                }
            } catch (error) {
                screenLoader.hide();
                console.error('Change card error:', error);
                showErrorNotification('Fejl', 'Der opstod en netværksfejl');
            }
        });
    }

    /**
     * Global Change Card button handler
     * Located on payments.php (list page)
     */
    function bindGlobalChangeCardButton() {
        const $btn = $('#global-change-card-btn');
        if (!$btn.length) return;

        $btn.on('click', async function() {
            // URL is set via JS variable from view (using Links class)
            const url = typeof consumerChangeCardUrl !== 'undefined'
                ? consumerChangeCardUrl
                : 'api/consumer/change-card';

            screenLoader.show('Starter kortskift...');

            try {
                const result = await post(url);

                if (result.status === 'success' && result.data?.checkoutUrl) {
                    // Redirect to Viva checkout
                    window.location.href = result.data.checkoutUrl;
                } else {
                    screenLoader.hide();
                    const errorMsg = result.error?.message || result.message || 'Kunne ikke starte kortskift';
                    showErrorNotification('Fejl', errorMsg);
                }
            } catch (error) {
                screenLoader.hide();
                console.error('Global change card error:', error);
                showErrorNotification('Fejl', 'Der opstod en netværksfejl');
            }
        });
    }

    /**
     * Download Receipt button handler
     * Located on payment-detail.php for completed payments
     */
    function bindDownloadReceiptButton() {
        const $btn = $('#download-receipt-btn');
        if (!$btn.length) return;

        $btn.on('click', async function() {
            const paymentUid = $(this).data('uid');
            if (!paymentUid) return;

            // Build URL for receipt download (consumerReceiptUrl is set via __url() so it's absolute)
            let url = typeof consumerReceiptUrl !== 'undefined'
                ? consumerReceiptUrl
                : `api/consumer/payments/${paymentUid}/receipt`;

            // Ensure absolute URL
            if (!url.includes('https://')) url = serverHost + url;

            screenLoader.show('Henter kvittering...');

            try {
                const response = await fetch(url, { credentials: 'same-origin' });
                const contentType = response.headers.get('content-type');

                // Check if response is JSON (error) or PDF (success)
                if (contentType && contentType.includes('application/json')) {
                    const result = await response.json();
                    screenLoader.hide();
                    const errorMsg = result.error?.message || result.message || 'Kunne ikke hente kvittering';
                    showErrorNotification('Fejl', errorMsg);
                    return;
                }

                // Success - download the PDF
                const blob = await response.blob();
                const downloadUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = `kvittering-${paymentUid}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(downloadUrl);

                screenLoader.hide();
            } catch (error) {
                screenLoader.hide();
                console.error('Download receipt error:', error);
                showErrorNotification('Fejl', 'Der opstod en netværksfejl');
            }
        });
    }

    return {
        init: init
    };
})();
