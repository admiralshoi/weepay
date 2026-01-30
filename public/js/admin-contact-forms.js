/**
 * Admin Contact Forms - Backend Pagination, Search, and Delete
 * Uses server-side pagination for scalability
 */
const AdminContactForms = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let searchTerm = '';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $sort, $perPage;
    let $showingStart, $showingEnd, $total, $totalCount, $pagination, $noResults, $paginationFooter, $table;

    function init() {
        $tbody = $('#contact-forms-tbody');
        $table = $('#contact-forms-table');
        $search = $('#contact-forms-search');
        $sort = $('#contact-forms-sort');
        $perPage = $('#contact-forms-per-page');
        $showingStart = $('#contact-forms-showing-start');
        $showingEnd = $('#contact-forms-showing-end');
        $total = $('#contact-forms-total');
        $totalCount = $('#contact-forms-total-count');
        $pagination = $('#contact-forms-pagination');
        $noResults = $('#contact-forms-no-results');
        $paginationFooter = $('#contact-forms-pagination-footer');

        if (!$tbody.length || typeof adminContactFormsListUrl === 'undefined') return;

        bindEvents();
        fetchSubmissions();
    }

    function bindEvents() {
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchSubmissions();
            }, 400);
        });

        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchSubmissions();
        });

        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchSubmissions();
        });
    }

    async function fetchSubmissions() {
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

            const result = await post(adminContactFormsListUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            renderSubmissions(data.submissions);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching submissions:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
            <tr id="contact-forms-loading-row">
                <td colspan="4" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser kontaktformularer...</p>
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
                        <button class="btn-v2 mute-btn mt-2" onclick="AdminContactForms.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderSubmissions(submissions) {
        if (!submissions || submissions.length === 0) {
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
        submissions.forEach(function(submission) {
            html += renderSubmissionRow(submission);
        });

        $tbody.html(html);
    }

    function renderSubmissionRow(submission) {
        const truncatedSubject = submission.subject && submission.subject.length > 50
            ? submission.subject.substring(0, 50) + '...'
            : submission.subject || '-';

        return `
            <tr data-uid="${submission.uid}">
                <td>
                    <div class="flex-col-start">
                        <p class="mb-0 font-14 font-weight-medium">${escapeHtml(submission.name || 'Unavngivet')}</p>
                        <p class="mb-0 font-12 color-gray">${escapeHtml(submission.email || '-')}</p>
                    </div>
                </td>
                <td>
                    <p class="mb-0 font-13">${escapeHtml(truncatedSubject)}</p>
                </td>
                <td>
                    <p class="mb-0 font-13">${formatDate(submission.created_at)}</p>
                </td>
                <td>
                    <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                        <button class="btn-v2 action-btn font-12 px-2" onclick="AdminContactForms.viewSubmission('${submission.uid}', '${escapeHtmlAttr(submission.name)}', '${escapeHtmlAttr(submission.email)}', '${escapeHtmlAttr(submission.subject)}', '${escapeHtmlAttr(submission.content)}', '${submission.created_at}')">
                            <i class="mdi mdi-eye"></i>
                        </button>
                        <button class="btn-v2 danger-btn font-12 px-2" onclick="AdminContactForms.deleteSubmission('${submission.uid}')">
                            <i class="mdi mdi-delete"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('da-DK', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function escapeHtmlAttr(text) {
        if (!text) return '';
        return text.replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '');
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
                fetchSubmissions();
                $('html, body').animate({
                    scrollTop: $('#contact-forms-table').offset().top - 100
                }, 200);
            }
        });
    }

    function viewSubmission(uid, name, email, subject, content, createdAt) {
        // Unescape the content for display
        const decodedContent = content.replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\"/g, '"');

        Swal.fire({
            title: `Besked fra ${escapeHtml(name)}`,
            html: `
                <div class="text-left">
                    <div class="mb-3">
                        <label class="font-12 color-gray mb-1 d-block">Email</label>
                        <p class="mb-0 font-14">${escapeHtml(email)}</p>
                    </div>
                    <div class="mb-3">
                        <label class="font-12 color-gray mb-1 d-block">Emne</label>
                        <p class="mb-0 font-14">${escapeHtml(subject)}</p>
                    </div>
                    <div class="mb-3">
                        <label class="font-12 color-gray mb-1 d-block">Dato</label>
                        <p class="mb-0 font-14">${formatDate(createdAt)}</p>
                    </div>
                    <div>
                        <label class="font-12 color-gray mb-1 d-block">Besked</label>
                        <div class="p-3 bg-light border-radius-10px" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;">
                            ${escapeHtml(decodedContent)}
                        </div>
                    </div>
                </div>
            `,
            width: 600,
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonText: '<i class="mdi mdi-delete"></i> Slet',
            cancelButtonText: 'Luk',
            customClass: {
                confirmButton: 'btn-v2 danger-btn mx-1',
                cancelButton: 'btn-v2 mute-btn mx-1',
            },
            buttonsStyling: false,
        }).then(async (result) => {
            if (result.value) {
                await performDelete(uid);
            }
        });
    }

    async function performDelete(uid) {
        const response = await post(adminContactFormsDeleteUrl, { uid: uid });
        if (response.status === 'success') {
            showSuccessNotification('Slettet', 'Indsendelsen er blevet slettet');
            fetchSubmissions();
        } else {
            showErrorNotification('Fejl', response.error?.message || 'Kunne ikke slette indsendelsen');
        }
    }

    function deleteSubmission(uid) {
        SweetPrompt.confirm('Slet indsendelse?', 'Er du sikker på at du vil slette denne kontaktformular?', {
            confirmButtonText: 'Ja, slet',
            onConfirm: async function() {
                const response = await post(adminContactFormsDeleteUrl, { uid: uid });
                if (response.status === 'success') {
                    showSuccessNotification('Slettet', 'Indsendelsen er blevet slettet');
                    fetchSubmissions();
                } else {
                    showErrorNotification('Fejl', response.error?.message || 'Kunne ikke slette indsendelsen');
                }
                return { status: 'silent' };
            },
            refireAfter: false
        });
    }

    return {
        init: init,
        refresh: fetchSubmissions,
        viewSubmission: viewSubmission,
        deleteSubmission: deleteSubmission
    };
})();

$(document).ready(function() {
    AdminContactForms.init();
});
