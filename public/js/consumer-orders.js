/**
 * Consumer Orders Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 * Supports two views: Fully Paid vs Not Fully Paid
 */
const ConsumerOrdersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let paymentFilter = 'fully_paid'; // Default to fully paid, will be overridden if not_fully_paid exists
    let filterStatus = 'all';
    let sortColumn = 'date';
    let sortDirection = 'DESC';
    let startDate = '';
    let endDate = '';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $sort, $perPage, $dateRange, $dateRangeClear, $filterStatus;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer, $table;
    let $typeButtons;

    function init() {
        // Get DOM elements
        $tbody = $('#consumer-orders-tbody');
        $table = $('#consumer-orders-table');
        $search = $('#consumer-orders-search');
        $sort = $('#consumer-orders-sort');
        $perPage = $('#consumer-orders-per-page');
        $filterStatus = $('#consumer-orders-filter-status');
        $dateRange = $('#consumer-orders-daterange');
        $dateRangeClear = $('#consumer-orders-daterange-clear');
        $showing = $('#consumer-orders-showing');
        $total = $('#consumer-orders-total');
        $currentPageEl = $('#consumer-orders-current-page');
        $totalPages = $('#consumer-orders-total-pages');
        $pagination = $('#consumer-orders-pagination');
        $noResults = $('#consumer-orders-no-results');
        $paginationContainer = $('#consumer-orders-pagination-container');
        $typeButtons = $('.consumer-order-type-btn');

        if (!$tbody.length || typeof consumerOrdersApiUrl === 'undefined') return;

        // Determine initial tab: not_fully_paid if has outstanding, otherwise fully_paid
        if (typeof consumerHasNotFullyPaid !== 'undefined' && consumerHasNotFullyPaid) {
            paymentFilter = 'not_fully_paid';
        } else {
            paymentFilter = 'fully_paid';
        }

        // Update button states to match initial filter type
        $typeButtons.removeClass('active action-btn').addClass('mute-btn');
        $typeButtons.filter(`[data-type="${paymentFilter}"]`).removeClass('mute-btn').addClass('active action-btn');

        // Initialize daterangepicker
        initDateRangePicker();

        // Bind events
        bindEvents();

        // Initial load
        fetchOrders();
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
                'Sidste måned': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        $dateRange.on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            startDate = picker.startDate.format('YYYY-MM-DD');
            endDate = picker.endDate.format('YYYY-MM-DD');
            $dateRangeClear.removeClass('d-none');
            currentPage = 1;
            fetchOrders();
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
        fetchOrders();
    }

    function bindEvents() {
        // Type toggle buttons
        $typeButtons.on('click', function() {
            const $btn = $(this);
            const type = $btn.data('type');

            if (type === paymentFilter) return;

            // Update button states
            $typeButtons.removeClass('active action-btn').addClass('mute-btn');
            $btn.removeClass('mute-btn').addClass('active action-btn');

            // Update type and reset
            paymentFilter = type;
            currentPage = 1;

            // Fetch new data
            fetchOrders();
        });

        // Search with debounce
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchOrders();
            }, 400);
        });

        // Sort - handle select-v2 change event
        $(document).on('change', '#consumer-orders-sort', function() {
            const val = $(this).val();
            if (val) {
                const parts = val.split('-');
                sortColumn = parts[0];
                sortDirection = parts[1].toUpperCase();
                fetchOrders();
            }
        });

        // Status filter - handle select-v2 change event
        $(document).on('change', '#consumer-orders-filter-status', function() {
            const val = $(this).val();
            filterStatus = val || 'all';
            currentPage = 1;
            fetchOrders();
        });

        // Per page - handle select-v2 change event
        $(document).on('change', '#consumer-orders-per-page', function() {
            const val = $(this).val();
            if (val) {
                perPage = parseInt(val);
                currentPage = 1;
                fetchOrders();
            }
        });
    }

    async function fetchOrders() {
        if (isLoading) return;
        isLoading = true;

        // Show loading state
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                payment_filter: paymentFilter,
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (filterStatus && filterStatus !== 'all') params.filter_status = filterStatus;
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;

            const result = await post(consumerOrdersApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            // Update state from response
            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            // Render orders
            renderOrders(data.orders);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching orders:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="consumer-orders-loading-row">
                <td colspan="6" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser ordrer...</p>
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
                        <p class="color-red mt-2 mb-0">${escapeHtml(message)}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="ConsumerOrdersPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderOrders(orders) {
        if (!orders || orders.length === 0) {
            $tbody.empty();
            $table.addClass('d-none');
            $noResults.removeClass('d-none');
            $paginationContainer.addClass('d-none');

            // Update no results message based on payment filter
            let icon, message;
            if (paymentFilter === 'fully_paid') {
                icon = 'mdi-cart-outline';
                message = 'Ingen fuldt betalte ordrer fundet';
            } else {
                icon = 'mdi-check-circle-outline';
                message = 'Ingen ubetalte ordrer - du er helt opdateret!';
                $noResults.find('i').removeClass('color-gray').addClass('color-green');
            }
            $noResults.find('i').attr('class', `mdi ${icon} font-40 ${paymentFilter === 'not_fully_paid' ? 'color-green' : 'color-gray'}`);
            $noResults.find('p').text(message);

            return;
        }

        $noResults.addClass('d-none');
        $table.removeClass('d-none');
        $paginationContainer.removeClass('d-none');

        let html = '';
        orders.forEach(function(order) {
            html += renderOrderRow(order);
        });

        $tbody.html(html);
    }

    function renderOrderRow(order) {
        const currencySymbol = getCurrencySymbol(order.currency);

        // Status badge
        let statusHtml;
        if (order.status === 'COMPLETED') {
            statusHtml = '<span class="success-box">Gennemført</span>';
        } else if (order.status === 'PENDING') {
            statusHtml = '<span class="action-box">Afvikles</span>';
        } else if (order.status === 'CANCELLED') {
            statusHtml = '<span class="danger-box">Annulleret</span>';
        } else if (order.status === 'DRAFT') {
            statusHtml = '<span class="mute-box">Kladde</span>';
        } else {
            statusHtml = `<span class="mute-box">${escapeHtml(order.status)}</span>`;
        }

        return `
            <tr>
                <td>
                    <a href="${order.detail_url}" class="color-blue hover-underline font-monospace">${escapeHtml(order.uid.substring(0, 8))}</a>
                </td>
                <td>
                    <p class="mb-0 text-sm">${escapeHtml(order.created_at)}</p>
                </td>
                <td>
                    <p class="mb-0 text-sm font-weight-medium">${formatNumber(order.amount, 2)}${currencySymbol}</p>
                </td>
                <td>
                    <p class="mb-0 text-sm">${escapeHtml(order.location_name)}</p>
                </td>
                <td>
                    <span class="${order.plan_class}">${escapeHtml(order.plan_label)}</span>
                </td>
                <td>
                    ${statusHtml}
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

        $showing.text(totalItems === 0 ? '0' : `${startItem}-${endItem}`);
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
                fetchOrders();
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $('#consumer-orders-table').offset().top - 100
                }, 200);
            }
        });
    }

    // Public API
    return {
        init: init,
        refresh: fetchOrders
    };
})();

// Initialize on document ready
$(document).ready(function() {
    ConsumerOrdersPagination.init();
});
