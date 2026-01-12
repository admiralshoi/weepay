/**
 * Admin Consumers Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const AdminConsumersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let searchTerm = '';
    let filterStatus = '';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterStatus, $sort, $perPage;
    let $showingStart, $showingEnd, $total, $totalCount, $pagination, $noResults, $paginationFooter, $table;

    function init() {
        $tbody = $('#consumers-tbody');
        $table = $('#consumers-table');
        $search = $('#consumers-search');
        $filterStatus = $('#consumers-filter-status');
        $sort = $('#consumers-sort');
        $perPage = $('#consumers-per-page');
        $showingStart = $('#consumers-showing-start');
        $showingEnd = $('#consumers-showing-end');
        $total = $('#consumers-total');
        $totalCount = $('#consumers-total-count');
        $pagination = $('#consumers-pagination');
        $noResults = $('#consumers-no-results');
        $paginationFooter = $('#consumers-pagination-footer');

        if (!$tbody.length || typeof adminConsumersApiUrl === 'undefined') return;

        bindEvents();
        fetchConsumers();
    }

    function bindEvents() {
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchConsumers();
            }, 400);
        });

        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchConsumers();
        });

        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchConsumers();
        });

        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchConsumers();
        });
    }

    async function fetchConsumers() {
        if (isLoading) return;
        isLoading = true;
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_direction: sortDirection,
                role: '1', // Consumer access level
            };

            if (searchTerm) params.search = searchTerm;
            if (filterStatus) params.status = filterStatus;

            const result = await post(adminConsumersApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            renderConsumers(data.users);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching consumers:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="consumers-loading-row">
                <td colspan="4" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser forbrugere...</p>
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
                <td colspan="4" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="AdminConsumersPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderConsumers(users) {
        if (!users || users.length === 0) {
            $tbody.empty();
            $table.addClass('d-none');
            $noResults.removeClass('d-none');
            $paginationFooter.addClass('d-none');
            return;
        }

        $noResults.addClass('d-none');
        $table.removeClass('d-none');
        $paginationFooter.removeClass('d-none');

        let html = '';
        users.forEach(function(user) {
            html += renderConsumerRow(user);
        });

        $tbody.html(html);
    }

    function renderConsumerRow(user) {
        const statusHtml = user.deactivated
            ? '<span class="danger-box font-11">Deaktiveret</span>'
            : '<span class="success-box font-11">Aktiv</span>';

        return `
            <tr>
                <td>
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="square-40 bg-light-gray border-radius-50 flex-row-center-center">
                            <i class="mdi mdi-account font-20 color-info"></i>
                        </div>
                        <div class="flex-col-start">
                            <a href="${adminUserDetailUrl}${user.uid}" class="mb-0 font-14 font-weight-medium color-blue hover-underline">${escapeHtml(user.full_name || 'Unavngivet')}</a>
                            <p class="mb-0 font-12 color-gray">${user.uid.substring(0, 10)}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-col-start">
                        <p class="mb-0 font-13">${escapeHtml(user.email || '-')}</p>
                        <p class="mb-0 font-12 color-gray">${escapeHtml(user.phone || '-')}</p>
                    </div>
                </td>
                <td>${statusHtml}</td>
                <td><p class="mb-0 font-13">${formatDate(user.created_at)}</p></td>
            </tr>
        `;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('da-DK', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatNumber(num, decimals = 0) {
        return new Intl.NumberFormat('da-DK', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    }

    function renderPaginationInfo() {
        const startItem = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
        const endItem = Math.min(currentPage * perPage, totalItems);

        $showingStart.text(startItem);
        $showingEnd.text(endItem);
        $total.text(formatNumber(totalItems));
        $totalCount.text(formatNumber(totalItems));
    }

    function renderPagination() {
        $pagination.empty();

        if (totalPagesCount <= 1) {
            $paginationFooter.addClass('d-none');
            return;
        }

        $paginationFooter.removeClass('d-none');

        if (currentPage > 1) {
            $pagination.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage - 1}"><i class="mdi mdi-chevron-left"></i></a>
                </li>
            `);
        }

        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPagesCount, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === currentPage;
            $pagination.append(`
                <li class="page-item ${isActive ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (currentPage < totalPagesCount) {
            $pagination.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage + 1}"><i class="mdi mdi-chevron-right"></i></a>
                </li>
            `);
        }

        $pagination.find('a[data-page]').on('click', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page !== currentPage && !isLoading) {
                currentPage = page;
                fetchConsumers();
                $('html, body').animate({
                    scrollTop: $('#consumers-table').offset().top - 100
                }, 200);
            }
        });
    }

    return {
        init: init,
        refresh: fetchConsumers
    };
})();

$(document).ready(function() {
    AdminConsumersPagination.init();
});
