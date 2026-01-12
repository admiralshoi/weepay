/**
 * Admin Users Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const AdminUsersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let searchTerm = '';
    let filterRole = '';
    let filterStatus = '';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterRole, $filterStatus, $sort, $perPage;
    let $showingStart, $showingEnd, $total, $totalCount, $pagination, $noResults, $paginationFooter, $table;

    function init() {
        $tbody = $('#users-tbody');
        $table = $('#users-table');
        $search = $('#users-search');
        $filterRole = $('#users-filter-role');
        $filterStatus = $('#users-filter-status');
        $sort = $('#users-sort');
        $perPage = $('#users-per-page');
        $showingStart = $('#users-showing-start');
        $showingEnd = $('#users-showing-end');
        $total = $('#users-total');
        $totalCount = $('#users-total-count');
        $pagination = $('#users-pagination');
        $noResults = $('#users-no-results');
        $paginationFooter = $('#users-pagination-footer');

        if (!$tbody.length || typeof adminUsersApiUrl === 'undefined') return;

        bindEvents();
        fetchUsers();
    }

    function bindEvents() {
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchUsers();
            }, 400);
        });

        $filterRole.on('change', function() {
            const val = $(this).val();
            filterRole = val === 'all' ? '' : val;
            currentPage = 1;
            fetchUsers();
        });

        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchUsers();
        });

        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchUsers();
        });

        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchUsers();
        });
    }

    async function fetchUsers() {
        if (isLoading) return;
        isLoading = true;
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (filterRole) params.role = filterRole;
            if (filterStatus) params.status = filterStatus;

            const result = await post(adminUsersApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            renderUsers(data.users);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching users:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="users-loading-row">
                <td colspan="5" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser brugere...</p>
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
                <td colspan="5" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="AdminUsersPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderUsers(users) {
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
            html += renderUserRow(user);
        });

        $tbody.html(html);
    }

    function renderUserRow(user) {
        const roleLabels = {
            1: { label: 'Forbruger', class: 'info-box' },
            2: { label: 'Forhandler', class: 'success-box' },
            8: { label: 'Admin', class: 'warning-box' },
            9: { label: 'Superadmin', class: 'danger-box' }
        };

        const role = roleLabels[user.access_level] || { label: 'Ukendt', class: 'mute-box' };
        const statusClass = user.deactivated ? 'danger-box' : 'success-box';
        const statusLabel = user.deactivated ? 'Deaktiveret' : 'Aktiv';
        const truncatedUid = user.uid.substring(0, 10);

        return `
            <tr>
                <td>
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="square-40 bg-light-gray border-radius-50 flex-row-center-center">
                            <i class="mdi mdi-account font-20 color-gray"></i>
                        </div>
                        <div class="flex-col-start">
                            <a href="${adminUserDetailUrl}${user.uid}" class="mb-0 font-14 font-weight-medium color-blue hover-underline">${escapeHtml(user.full_name || 'Unavngivet')}</a>
                            <p class="mb-0 font-12 color-gray font-monospace">${escapeHtml(truncatedUid)}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-col-start">
                        <p class="mb-0 font-13">${escapeHtml(user.email || '-')}</p>
                        <p class="mb-0 font-12 color-gray">${escapeHtml(user.phone || '-')}</p>
                    </div>
                </td>
                <td><span class="${role.class} font-11">${role.label}</span></td>
                <td><span class="${statusClass} font-11">${statusLabel}</span></td>
                <td>
                    <p class="mb-0 font-13">${formatDate(user.created_at)}</p>
                </td>
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
                fetchUsers();
                $('html, body').animate({
                    scrollTop: $('#users-table').offset().top - 100
                }, 200);
            }
        });
    }

    return {
        init: init,
        refresh: fetchUsers
    };
})();

$(document).ready(function() {
    AdminUsersPagination.init();
});
