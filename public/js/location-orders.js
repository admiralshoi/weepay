/**
 * Location Orders Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 * Location-specific version of the orders pagination
 */
const LocationOrdersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let filterStatus = '';
    let sortColumn = 'date';
    let sortDirection = 'DESC';
    let startDate = '';
    let endDate = '';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterStatus, $sort, $perPage, $dateRange, $dateRangeClear;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer, $table;

    function init() {
        // Get DOM elements
        $tbody = $('#location-orders-tbody');
        $table = $('#location-orders-table');
        $search = $('#location-orders-search');
        $filterStatus = $('#location-orders-filter-status');
        $sort = $('#location-orders-sort');
        $perPage = $('#location-orders-per-page');
        $dateRange = $('#location-orders-daterange');
        $dateRangeClear = $('#location-orders-daterange-clear');
        $showing = $('#location-orders-showing');
        $total = $('#location-orders-total');
        $currentPageEl = $('#location-orders-current-page');
        $totalPages = $('#location-orders-total-pages');
        $pagination = $('#location-orders-pagination');
        $noResults = $('#location-orders-no-results');
        $paginationContainer = $('#location-orders-pagination-container');

        if (!$tbody.length || typeof locationOrdersApiUrl === 'undefined') return;

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

        // Filter by status
        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchOrders();
        });

        // Sort
        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchOrders();
        });

        // Per page
        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchOrders();
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
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (filterStatus) params.filter_status = filterStatus;
            if (startDate) params.start_date = startDate;
            if (endDate) params.end_date = endDate;

            const result = await post(locationOrdersApiUrl, params);

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
            console.error('Error fetching location orders:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="location-orders-loading-row">
                <td colspan="8" class="text-center py-4">
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
                <td colspan="8" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="LocationOrdersPagination.refresh()">Prøv igen</button>
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

        // Customer display
        let customerHtml;
        if (order.customer_uid) {
            customerHtml = `<a href="${order.customer_url}" class="color-blue hover-underline">${escapeHtml(order.customer_name)}</a>`;
        } else {
            customerHtml = `<span class="color-gray">${escapeHtml(order.customer_name)}</span>`;
        }

        return `
            <tr>
                <td class="font-weight-medium">${escapeHtml(order.uid)}</td>
                <td>
                    <p class="mb-0 text-sm text-wrap">${order.created_at}</p>
                </td>
                <td>${customerHtml}</td>
                <td>
                    <p class="mb-0 text-sm">${formatNumber(order.amount)}${currencySymbol}</p>
                </td>
                <td>
                    <p class="mb-0 text-sm">${formatNumber(order.paid_amount)}${currencySymbol}</p>
                </td>
                <td>
                    <p class="mb-0 text-sm ${order.outstanding > 0 ? 'color-orange' : ''}">${formatNumber(order.outstanding)}${currencySymbol}</p>
                </td>
                <td>
                    <span class="${order.status_class}">${order.status_label}</span>
                </td>
                <td class="text-right">
                    <a href="${order.detail_url}" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
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

    function formatNumber(num) {
        return new Intl.NumberFormat('da-DK').format(num);
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
                fetchOrders();
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $('#location-orders-table').offset().top - 100
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
    LocationOrdersPagination.init();
});
