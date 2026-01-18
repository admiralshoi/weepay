/**
 * Admin Notification Logs Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const AdminNotificationLogsPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 25;
    let filterStatus = '';
    let filterChannel = '';
    let searchRecipient = '';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $filterStatus, $filterChannel, $searchRecipient, $perPage;
    let $showingStart, $showingEnd, $total, $pagination, $noResults, $paginationFooter, $table, $loadingRow;

    function init() {
        $tbody = $('#logs-tbody');
        $table = $('#logs-table');
        $filterStatus = $('#logs-filter-status');
        $filterChannel = $('#logs-filter-channel');
        $searchRecipient = $('#logs-search-recipient');
        $perPage = $('#logs-per-page');
        $showingStart = $('#logs-showing-start');
        $showingEnd = $('#logs-showing-end');
        $total = $('#logs-total');
        $pagination = $('#logs-pagination');
        $noResults = $('#logs-no-results');
        $paginationFooter = $('#logs-pagination-footer');
        $loadingRow = $('#logs-loading');

        if (!$tbody.length) return;

        applyQueryParams();
        bindEvents();
        fetchLogs();
    }

    function applyQueryParams() {
        const urlParams = new URLSearchParams(window.location.search);

        const statusParam = urlParams.get('status');
        if (statusParam && $filterStatus.find(`option[value="${statusParam}"]`).length) {
            filterStatus = statusParam;
            updateSelectV2Value($filterStatus.get(0), statusParam);
        }

        const channelParam = urlParams.get('channel');
        if (channelParam && $filterChannel.find(`option[value="${channelParam}"]`).length) {
            filterChannel = channelParam;
            updateSelectV2Value($filterChannel.get(0), channelParam);
        }

        const searchParam = urlParams.get('search');
        if (searchParam) {
            $searchRecipient.val(searchParam);
            searchRecipient = searchParam;
        }
    }

    function bindEvents() {
        let searchTimeout;
        $searchRecipient.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchRecipient = $searchRecipient.val().trim();
                currentPage = 1;
                fetchLogs();
            }, 400);
        });

        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchLogs();
        });

        $filterChannel.on('change', function() {
            const val = $(this).val();
            filterChannel = val === 'all' ? '' : val;
            currentPage = 1;
            fetchLogs();
        });

        if ($perPage.length) {
            $perPage.on('change', function() {
                perPage = parseInt($(this).val());
                currentPage = 1;
                fetchLogs();
            });
        }
    }

    async function fetchLogs() {
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

            if (searchRecipient) params.recipient = searchRecipient;
            if (filterStatus) params.status = filterStatus;
            if (filterChannel) params.channel = filterChannel;

            const result = await post('api/admin/notifications/logs/list', params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            renderLogs(data.logs);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching logs:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $loadingRow.removeClass('d-none');
        $noResults.addClass('d-none');
        $table.addClass('d-none');
        $paginationFooter.addClass('d-none');
    }

    function showError(message) {
        $loadingRow.addClass('d-none');
        $table.addClass('d-none');
        $noResults.removeClass('d-none').html(`
            <div class="card-body text-center py-5">
                <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3 mx-auto">
                    <i class="mdi mdi-alert-circle font-28 color-red"></i>
                </div>
                <p class="mb-0 font-16 font-weight-bold color-red">${message}</p>
                <button class="btn-v2 mute-btn mt-3" onclick="AdminNotificationLogsPagination.refresh()">Prøv igen</button>
            </div>
        `);
    }

    function renderLogs(logs) {
        $loadingRow.addClass('d-none');

        if (!logs || logs.length === 0) {
            $tbody.empty();
            $table.addClass('d-none');
            $noResults.removeClass('d-none').html(`
                <div class="card-body text-center py-5">
                    <div class="square-60 bg-light-gray border-radius-50 flex-row-center-center mb-3 mx-auto">
                        <i class="mdi mdi-history font-28 color-gray"></i>
                    </div>
                    <p class="mb-0 font-16 font-weight-bold color-dark">Ingen logs</p>
                    <p class="mb-0 font-13 color-gray mt-1">Ingen notifikationer fundet med de valgte filtre</p>
                </div>
            `);
            $paginationFooter.addClass('d-none');
            return;
        }

        $noResults.addClass('d-none');
        $table.removeClass('d-none');
        $paginationFooter.removeClass('d-none');

        let html = '';
        logs.forEach(function(log) {
            html += renderLogRow(log);
        });

        $tbody.html(html);
    }

    function renderLogRow(log) {
        const channelLabels = {
            'email': { label: 'E-mail', icon: 'mdi-email-outline', class: 'primary-box' },
            'sms': { label: 'SMS', icon: 'mdi-message-text-outline', class: 'info-box' },
            'bell': { label: 'Push', icon: 'mdi-bell-outline', class: 'warning-box' }
        };

        const statusLabels = {
            'sent': { label: 'Sendt', class: 'success-box' },
            'delivered': { label: 'Leveret', class: 'success-box' },
            'failed': { label: 'Fejlet', class: 'danger-box' },
            'bounced': { label: 'Afvist', class: 'danger-box' }
        };

        const channel = channelLabels[log.channel] || { label: log.channel || '-', icon: 'mdi-help-circle-outline', class: 'mute-box' };
        const status = statusLabels[log.status] || { label: log.status || '-', class: 'mute-box' };

        // Recipient display
        let recipientHtml;
        if (log.recipient_name) {
            recipientHtml = `
                <div class="flex-col-start">
                    <span class="font-13 font-weight-medium">${escapeHtml(log.recipient_name)}</span>
                    <span class="font-11 color-gray">${escapeHtml(log.recipient_identifier || '')}</span>
                </div>
            `;
        } else {
            recipientHtml = `<span class="font-13">${escapeHtml(log.recipient_identifier || '-')}</span>`;
        }

        // Format date
        const createdDate = formatDateTime(log.created_at);

        // Breakpoint display
        let breakpointHtml = '-';
        if (log.breakpoint_key) {
            breakpointHtml = `<code class="font-11 bg-light-gray px-2 py-1 border-radius-4px">${escapeHtml(log.breakpoint_key)}</code>`;
        }

        return `
            <tr data-uid="${escapeHtml(log.uid)}">
                <td class="ps-3 py-3">
                    <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                        <div class="square-36 bg-light-gray border-radius-8px flex-row-center-center">
                            <i class="mdi ${channel.icon} font-18 color-blue"></i>
                        </div>
                        ${recipientHtml}
                    </div>
                </td>
                <td class="py-3"><span class="${channel.class} font-11">${channel.label}</span></td>
                <td class="py-3"><span class="font-13 color-gray text-truncate" style="max-width: 200px; display: inline-block;">${escapeHtml(log.subject || '-')}</span></td>
                <td class="py-3">${breakpointHtml}</td>
                <td class="py-3"><span class="${status.class} font-11">${status.label}</span></td>
                <td class="py-3"><span class="font-12 color-gray">${createdDate}</span></td>
                <td class="pe-3 py-3 text-end">
                    <button type="button" class="btn-v2 action-btn btn-sm" onclick="AdminNotificationLogsPagination.confirmResend('${escapeHtml(log.uid)}', '${escapeHtml(log.recipient_name || log.recipient_identifier || '')}', '${escapeHtml(log.channel)}')" title="Gensend">
                        <i class="mdi mdi-send"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '-';
        return d.toLocaleDateString('da-DK', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
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

        if ($showingStart.length) $showingStart.text(startItem);
        if ($showingEnd.length) $showingEnd.text(endItem);
        if ($total.length) $total.text(formatNumber(totalItems));
    }

    function renderPagination() {
        if (!$pagination.length) return;
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
                fetchLogs();
                $('html, body').animate({
                    scrollTop: $('#logs-table').offset().top - 100
                }, 200);
            }
        });
    }

    function confirmResend(uid, recipientName, channel) {
        const channelLabels = { email: 'e-mail', sms: 'SMS', bell: 'push-notifikation' };
        const channelLabel = channelLabels[channel] || 'notifikation';

        SweetPrompt.confirm(
            'Gensend notifikation?',
            `Er du sikker på at du vil gensende denne ${channelLabel} til ${recipientName || 'modtageren'}?`,
            {
                confirmButtonText: 'Ja, gensend',
                onConfirm: async function() {
                    await resendNotification(uid);
                }
            }
        );
    }

    async function resendNotification(uid) {
        try {
            const result = await post('api/admin/notifications/logs/resend', { uid: uid });

            if (result.status === 'success') {
                showSuccessNotification('Notifikation gensendt', result.message || 'Notifikationen blev gensendt');

                // Insert new log row at the top if returned
                if (result.data && result.data.log) {
                    const newRowHtml = renderLogRow(result.data.log);
                    $tbody.prepend(newRowHtml);

                    // Highlight the new row briefly
                    $tbody.find('tr').first().css('background-color', '#e8f5e9');
                    setTimeout(function() {
                        $tbody.find('tr').first().css('background-color', '');
                    }, 2000);
                }
            } else {
                showErrorNotification('Fejl', result.message || result.error?.message || 'Kunne ikke gensende notifikationen');
            }
        } catch (error) {
            console.error('Error resending notification:', error);
            showErrorNotification('Fejl', 'Der opstod en netværksfejl');
        }
    }

    return {
        init: init,
        refresh: fetchLogs,
        confirmResend: confirmResend
    };
})();

$(document).ready(function() {
    AdminNotificationLogsPagination.init();
});
