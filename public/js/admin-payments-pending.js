/**
 * Admin Pending Payments Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const AdminPaymentsPendingPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let searchTerm = '';
    let filterOrg = '';
    let sortColumn = 'due_date';
    let sortDirection = 'ASC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterOrg, $sort, $perPage;
    let $showingStart, $showingEnd, $total, $totalCount, $pagination, $noResults, $paginationFooter, $table;

    function init() {
        $tbody = $('#payments-tbody');
        $table = $('#payments-table');
        $search = $('#payments-search');
        $filterOrg = $('#payments-filter-org');
        $sort = $('#payments-sort');
        $perPage = $('#payments-per-page');
        $showingStart = $('#payments-showing-start');
        $showingEnd = $('#payments-showing-end');
        $total = $('#payments-total');
        $totalCount = $('#payments-total-count');
        $pagination = $('#payments-pagination');
        $noResults = $('#payments-no-results');
        $paginationFooter = $('#payments-pagination-footer');

        if (!$tbody.length || typeof adminPaymentsApiUrl === 'undefined') return;

        bindEvents();
        fetchPayments();
    }

    function bindEvents() {
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchPayments();
            }, 400);
        });

        $filterOrg.on('change', function() {
            const val = $(this).val();
            filterOrg = val === 'all' ? '' : val;
            currentPage = 1;
            fetchPayments();
        });

        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchPayments();
        });

        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchPayments();
        });
    }

    async function fetchPayments() {
        if (isLoading) return;
        isLoading = true;
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_direction: sortDirection,
                status: 'PENDING,SCHEDULED', // Only pending/scheduled payments
            };

            if (searchTerm) params.search = searchTerm;
            if (filterOrg) params.organisation = filterOrg;

            const result = await post(adminPaymentsApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

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
            <tr id="payments-loading-row">
                <td colspan="7" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser afventende betalinger...</p>
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
                <td colspan="7" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="AdminPaymentsPendingPagination.refresh()">Prøv igen</button>
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
            $paginationFooter.addClass('d-none');
            return;
        }

        $noResults.addClass('d-none');
        $table.removeClass('d-none');
        $paginationFooter.removeClass('d-none');

        let html = '';
        payments.forEach(function(payment) {
            html += renderPaymentRow(payment);
        });

        $tbody.html(html);
    }

    function renderPaymentRow(payment) {
        const statusLabels = {
            'PENDING': { label: 'Afventer', class: 'warning-box' },
            'SCHEDULED': { label: 'Planlagt', class: 'info-box' }
        };

        const status = statusLabels[payment.status] || { label: 'Ukendt', class: 'mute-box' };

        // Check if due soon (within 7 days)
        let dueSoonClass = '';
        if (payment.due_date) {
            const dueDate = new Date(payment.due_date);
            const now = new Date();
            const diffDays = Math.ceil((dueDate - now) / (1000 * 60 * 60 * 24));
            if (diffDays <= 7 && diffDays > 0) {
                dueSoonClass = 'color-warning font-weight-medium';
            }
        }

        // Customer display
        let customerHtml;
        if (payment.user_uid) {
            customerHtml = `<a href="${adminUserDetailUrl}${payment.user_uid}" class="font-13 color-blue hover-underline">${escapeHtml(payment.user_name || payment.user_email || '-')}</a>`;
        } else {
            customerHtml = `<span class="font-13 color-gray">-</span>`;
        }

        // Order display
        let orderHtml;
        if (payment.order_uid) {
            const truncatedOrderId = payment.order_uid.substring(0, 10);
            orderHtml = `<a href="${adminOrderDetailUrl}${payment.order_uid}" class="font-13 color-blue hover-underline font-monospace">${escapeHtml(truncatedOrderId)}</a>`;
        } else {
            orderHtml = `<span class="font-13 color-gray">-</span>`;
        }

        const truncatedPaymentId = payment.uid.substring(0, 10);

        return `
            <tr>
                <td>
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="square-40 bg-light-gray border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-clock-outline font-20 color-warning"></i>
                        </div>
                        <div class="flex-col-start">
                            <a href="${adminPaymentDetailUrl}${payment.uid}" class="mb-0 font-14 font-weight-medium color-blue hover-underline font-monospace">${escapeHtml(truncatedPaymentId)}</a>
                            <p class="mb-0 font-12 color-gray">Rate ${payment.installment_number || 1}</p>
                        </div>
                    </div>
                </td>
                <td>${orderHtml}</td>
                <td>${customerHtml}</td>
                <td><span class="font-13">${escapeHtml(payment.organisation_name || '-')}</span></td>
                <td><p class="mb-0 font-14 font-weight-medium">${formatNumber(payment.amount, 2)} ${escapeHtml(payment.currency || 'DKK')}</p></td>
                <td><p class="mb-0 font-13 ${dueSoonClass}">${formatDate(payment.due_date)}${dueSoonClass ? ' <i class="mdi mdi-alert-circle color-warning"></i>' : ''}</p></td>
                <td><span class="${status.class} font-11">${status.label}</span></td>
            </tr>
        `;
    }

    function formatNumber(num, decimals = 0) {
        return new Intl.NumberFormat('da-DK', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
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
                fetchPayments();
                $('html, body').animate({
                    scrollTop: $('#payments-table').offset().top - 100
                }, 200);
            }
        });
    }

    return {
        init: init,
        refresh: fetchPayments
    };
})();

$(document).ready(function() {
    AdminPaymentsPendingPagination.init();
});
