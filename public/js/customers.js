/**
 * Customers Table - Backend Pagination, Search, and Sort
 * Uses server-side pagination for scalability
 */
const CustomersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let sortColumn = 'total_spent';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $sort, $perPage;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer, $table;

    function init() {
        // Get DOM elements
        $tbody = $('#customers-tbody');
        $table = $('#customers-table');
        $search = $('#customers-search');
        $sort = $('#customers-sort');
        $perPage = $('#customers-per-page');
        $showing = $('#customers-showing');
        $total = $('#customers-total');
        $currentPageEl = $('#customers-current-page');
        $totalPages = $('#customers-total-pages');
        $pagination = $('#customers-pagination');
        $noResults = $('#customers-no-results');
        $paginationContainer = $('#customers-pagination-container');

        if (!$tbody.length || typeof customersApiUrl === 'undefined') return;

        // Bind events
        bindEvents();

        // Initial load
        fetchCustomers();
    }

    function bindEvents() {
        // Search with debounce
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchCustomers();
            }, 400);
        });

        // Sort
        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchCustomers();
        });

        // Per page
        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchCustomers();
        });
    }

    async function fetchCustomers() {
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

            const result = await post(customersApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            // Update state from response
            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            // Render customers
            renderCustomers(data.customers);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching customers:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="customers-loading-row">
                <td colspan="8" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser kunder...</p>
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
                        <button class="btn-v2 mute-btn mt-2" onclick="CustomersPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderCustomers(customers) {
        if (!customers || customers.length === 0) {
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
        customers.forEach(function(customer) {
            html += renderCustomerRow(customer);
        });

        $tbody.html(html);
    }

    function renderCustomerRow(customer) {
        return `
            <tr>
                <td>
                    <p class="mb-0 font-weight-medium">${escapeHtml(customer.full_name)}</p>
                </td>
                <td class="text-sm">${escapeHtml(customer.email)}</td>
                <td class="text-sm">${escapeHtml(customer.phone)}</td>
                <td>
                    <p class="mb-0 text-sm font-weight-medium">${customer.order_count}</p>
                </td>
                <td>
                    <p class="mb-0 text-sm font-weight-bold color-success-text">${formatNumber(customer.total_spent, 2)} kr.</p>
                </td>
                <td class="text-sm">${customer.first_order_date}</td>
                <td class="text-sm">${customer.last_order_date}</td>
                <td class="text-right">
                    <a href="${customer.detail_url}" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                        <i class="mdi mdi-eye-outline font-16"></i>
                        <span class="text-sm">Se detaljer</span>
                    </a>
                </td>
            </tr>
        `;
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
                fetchCustomers();
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $('#customers-table').offset().top - 100
                }, 200);
            }
        });
    }

    // Public API
    return {
        init: init,
        refresh: fetchCustomers
    };
})();

// Initialize on document ready
$(document).ready(function() {
    CustomersPagination.init();
});
