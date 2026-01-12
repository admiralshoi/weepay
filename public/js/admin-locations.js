/**
 * Admin Locations Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const AdminLocationsPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let searchTerm = '';
    let filterOrg = '';
    let filterStatus = '';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterOrg, $filterStatus, $sort, $perPage;
    let $showingStart, $showingEnd, $total, $totalCount, $pagination, $noResults, $paginationFooter, $table;

    function init() {
        $tbody = $('#locations-tbody');
        $table = $('#locations-table');
        $search = $('#locations-search');
        $filterOrg = $('#locations-filter-org');
        $filterStatus = $('#locations-filter-status');
        $sort = $('#locations-sort');
        $perPage = $('#locations-per-page');
        $showingStart = $('#locations-showing-start');
        $showingEnd = $('#locations-showing-end');
        $total = $('#locations-total');
        $totalCount = $('#locations-total-count');
        $pagination = $('#locations-pagination');
        $noResults = $('#locations-no-results');
        $paginationFooter = $('#locations-pagination-footer');

        if (!$tbody.length || typeof adminLocationsApiUrl === 'undefined') return;

        bindEvents();
        fetchLocations();
    }

    function bindEvents() {
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchLocations();
            }, 400);
        });

        $filterOrg.on('change', function() {
            const val = $(this).val();
            filterOrg = val === 'all' ? '' : val;
            currentPage = 1;
            fetchLocations();
        });

        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchLocations();
        });

        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchLocations();
        });

        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchLocations();
        });
    }

    async function fetchLocations() {
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
            if (filterOrg) params.organisation = filterOrg;
            if (filterStatus) params.status = filterStatus;

            const result = await post(adminLocationsApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            renderLocations(data.locations);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching locations:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="locations-loading-row">
                <td colspan="5" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser lokationer...</p>
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
                        <button class="btn-v2 mute-btn mt-2" onclick="AdminLocationsPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderLocations(locations) {
        if (!locations || locations.length === 0) {
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
        locations.forEach(function(loc) {
            html += renderLocationRow(loc);
        });

        $tbody.html(html);
    }

    function renderLocationRow(loc) {
        const statusLabels = {
            'DRAFT': { label: 'Kladde', class: 'mute-box' },
            'ACTIVE': { label: 'Aktiv', class: 'success-box' },
            'INACTIVE': { label: 'Inaktiv', class: 'warning-box' },
            'DELETED': { label: 'Slettet', class: 'danger-box' }
        };

        const status = statusLabels[loc.status] || { label: 'Ukendt', class: 'mute-box' };

        // Organisation display
        let orgHtml;
        if (loc.organisation_uid) {
            orgHtml = `<a href="${adminOrgDetailUrl}${loc.organisation_uid}" class="font-13 color-blue hover-underline">${escapeHtml(loc.organisation_name || '-')}</a>`;
        } else {
            orgHtml = `<span class="font-13 color-gray">-</span>`;
        }

        return `
            <tr>
                <td>
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="square-40 bg-light-gray border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-map-marker font-20 color-warning"></i>
                        </div>
                        <div class="flex-col-start">
                            <a href="${adminLocationDetailUrl}${loc.uid}" class="mb-0 font-14 font-weight-medium color-blue hover-underline">${escapeHtml(loc.name || 'Unavngivet')}</a>
                            <p class="mb-0 font-12 color-gray">${escapeHtml(loc.slug || '-')}</p>
                        </div>
                    </div>
                </td>
                <td>${orgHtml}</td>
                <td><p class="mb-0 font-13">${escapeHtml(loc.contact_email || '-')}</p></td>
                <td><span class="${status.class} font-11">${status.label}</span></td>
                <td><p class="mb-0 font-13">${formatDate(loc.created_at)}</p></td>
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
                fetchLocations();
                $('html, body').animate({
                    scrollTop: $('#locations-table').offset().top - 100
                }, 200);
            }
        });
    }

    return {
        init: init,
        refresh: fetchLocations
    };
})();

$(document).ready(function() {
    AdminLocationsPagination.init();
});
