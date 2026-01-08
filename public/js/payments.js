/**
 * Payments Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 * Supports three views: Completed (gennemførte), Upcoming (kommende), and Past Due (forfaldne)
 */
const PaymentsPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let filterType = 'completed'; // 'completed', 'upcoming', or 'past_due'
    let filterStatus = '';
    let sortColumn = 'date';
    let sortDirection = 'DESC';
    let startDate = '';
    let endDate = '';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterStatus, $filterStatusContainer, $sort, $perPage, $dateRange, $dateRangeClear;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer, $table;
    let $typeButtons, $dateHeader, $statusHeader;

    function init() {
        // Get DOM elements
        $tbody = $('#payments-tbody');
        $table = $('#payments-table');
        $search = $('#payments-search');
        $filterStatus = $('#payments-filter-status');
        $filterStatusContainer = $('#payments-status-filter-container');
        $sort = $('#payments-sort');
        $perPage = $('#payments-per-page');
        $dateRange = $('#payments-daterange');
        $dateRangeClear = $('#payments-daterange-clear');
        $showing = $('#payments-showing');
        $total = $('#payments-total');
        $currentPageEl = $('#payments-current-page');
        $totalPages = $('#payments-total-pages');
        $pagination = $('#payments-pagination');
        $noResults = $('#payments-no-results');
        $paginationContainer = $('#payments-pagination-container');
        $typeButtons = $('.payment-type-btn');
        $dateHeader = $('#payments-date-header');
        $statusHeader = $('#payments-status-header');

        if (!$tbody.length || typeof paymentsApiUrl === 'undefined') return;

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
            filterStatus = '';
            $filterStatus.val('all');
            currentPage = 1;

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

        // Filter by status
        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchPayments();
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
        if (filterType === 'completed') {
            // Hide status filter for completed (all are COMPLETED)
            $filterStatusContainer.addClass('d-none');
            // Show "Betalt" column, hide "Status" column
            $dateHeader.text('Betalt');
            $statusHeader.addClass('d-none');
        } else if (filterType === 'past_due') {
            // Hide status filter for past_due (all are PAST_DUE)
            $filterStatusContainer.addClass('d-none');
            // Change date header, hide status column (all have same status)
            $dateHeader.text('Betalt');
            $statusHeader.addClass('d-none');
        } else {
            // Show status filter for upcoming
            $filterStatusContainer.removeClass('d-none');
            // Change date header, show status column
            $dateHeader.text('Betalt');
            $statusHeader.removeClass('d-none');
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
            if (filterStatus) params.filter_status = filterStatus;
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;

            const result = await post(paymentsApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            // Update state from response
            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

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
        const colspan = filterType === 'upcoming' ? 9 : 8;
        $tbody.html(`
            <tr id="payments-loading-row">
                <td colspan="${colspan}" class="text-center py-4">
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
        const colspan = filterType === 'upcoming' ? 9 : 8;
        $tbody.html(`
            <tr>
                <td colspan="${colspan}" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="PaymentsPagination.refresh()">Prøv igen</button>
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

        // Customer display
        let customerHtml;
        if (payment.customer_uid) {
            customerHtml = `<a href="${payment.customer_url}" class="color-blue hover-underline">${escapeHtml(payment.customer_name)}</a>`;
        } else {
            customerHtml = `<span class="color-gray">${escapeHtml(payment.customer_name)}</span>`;
        }

        // Order display
        let orderHtml;
        if (payment.order_uid) {
            orderHtml = `<a href="${payment.order_url}" class="color-blue hover-underline font-monospace">${escapeHtml(payment.order_uid)}</a>`;
        } else {
            orderHtml = `<span class="color-gray">N/A</span>`;
        }

        // Status column (only for upcoming)
        const statusColumn = filterType === 'upcoming'
            ? `<td><span class="${payment.status_class}">${payment.status_label}</span></td>`
            : '';

        // Amount color based on type
        let amountClass = '';
        if (filterType === 'completed') amountClass = 'color-success-text';
        else if (filterType === 'past_due') amountClass = 'color-red';

        return `
            <tr>
                <td class="font-monospace text-sm">${escapeHtml(payment.uid)}</td>
                <td class="text-sm">${orderHtml}</td>
                <td class="text-sm">${customerHtml}</td>
                <td>
                    <p class="mb-0 text-sm font-weight-bold ${amountClass}">${formatNumber(payment.amount, 2)}${currencySymbol}</p>
                </td>
                <td class="text-sm">${payment.installment_number}</td>
                <td class="text-sm">${payment.paid_at || '-'}</td>
                <td class="text-sm font-weight-medium">${payment.due_date}</td>
                ${statusColumn}
                <td class="text-right">
                    <a href="${payment.detail_url}" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                        <i class="mdi mdi-eye-outline font-16"></i>
                        <span class="text-sm">Detaljer</span>
                    </a>
                </td>
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
                    scrollTop: $('#payments-table').offset().top - 100
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
    PaymentsPagination.init();
});
