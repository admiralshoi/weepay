/**
 * Admin Support JavaScript
 */

// State
let currentPage = 1;
let currentFilters = {
    status: 'open',
    type: 'all',
    search: ''
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initAdminSupportPage();
});

function initAdminSupportPage() {
    // Check if we're on the list page or detail page
    const ticketsTableBody = document.getElementById('ticketsTableBody');

    if (ticketsTableBody) {
        // List page
        initializeListPage();
    } else {
        // Detail page
        initializeDetailPage();
    }

    // Initialize select-v2 on any selects
    initializeSelectV2();
}

function initializeListPage() {
    // Setup filter listeners
    const filterStatus = document.getElementById('filterStatus');
    const filterType = document.getElementById('filterType');
    const searchInput = document.getElementById('searchInput');

    if (filterStatus) {
        filterStatus.addEventListener('change', () => {
            currentFilters.status = filterStatus.value;
            currentPage = 1;
            loadTickets();
        });
    }

    if (filterType) {
        filterType.addEventListener('change', () => {
            currentFilters.type = filterType.value;
            currentPage = 1;
            loadTickets();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                currentFilters.search = searchInput.value.trim();
                currentPage = 1;
                loadTickets();
            }
        });
    }

    // Setup stat card click filters
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', function() {
            const filter = this.dataset.filter;
            filterStatus.value = filter;
            currentFilters.status = filter;
            currentPage = 1;
            loadTickets();

            // Update active state
            document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Load tickets on page load
    loadTickets();
}

function initializeDetailPage() {
    // Scroll chat to bottom to show latest messages
    const messagesContainer = document.getElementById('ticketMessages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

function initializeSelectV2() {
    if (typeof selectV2 === 'function') {
        document.querySelectorAll('select.form-select-v2').forEach(select => {
            if (!select.classList.contains('select-v2-initialized')) {
                selectV2(select);
                select.classList.add('select-v2-initialized');
            }
        });
    }
}

/**
 * Load tickets with current filters
 */
async function loadTickets() {
    const loadingEl = document.getElementById('ticketsLoading');
    const emptyEl = document.getElementById('ticketsEmpty');
    const tableContainer = document.getElementById('ticketsTableContainer');
    const tableBody = document.getElementById('ticketsTableBody');

    // Show loading
    loadingEl.style.display = 'flex';
    emptyEl.style.display = 'none';
    tableContainer.style.display = 'none';

    const result = await post(platformLinks.api.admin.support.list, {
        page: currentPage,
        per_page: 15,
        status: currentFilters.status,
        type: currentFilters.type,
        search: currentFilters.search
    });

    loadingEl.style.display = 'none';

    if (result.status === 'success' && result.data.tickets.length > 0) {
        tableContainer.style.display = 'block';
        renderTicketsTable(result.data.tickets);
        renderPagination(result.data.pagination);
    } else {
        emptyEl.style.display = 'flex';
    }
}

/**
 * Render tickets table rows
 */
function renderTicketsTable(tickets) {
    const tableBody = document.getElementById('ticketsTableBody');
    tableBody.innerHTML = '';

    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.className = 'ticket-row';
        row.onclick = () => viewTicket(ticket.uid, ticket.detail_url);

        const statusClass = ticket.status === 'open' ? 'warning-box' : 'success-box';
        const statusText = ticket.status === 'open' ? 'Åben' : 'Lukket';
        const typeClass = ticket.type === 'merchant' ? 'action-box' : 'info-box';
        const typeText = ticket.type === 'merchant' ? 'Forhandler' : 'Forbruger';

        // Build on behalf of info for merchants
        let onBehalfInfo = '';
        if (ticket.type === 'merchant') {
            if (ticket.on_behalf_of === 'organisation' && ticket.organisation_name) {
                onBehalfInfo = `<span class="font-11 color-gray"><i class="mdi mdi-domain"></i> ${escapeHtml(ticket.organisation_name)}</span>`;
            } else {
                onBehalfInfo = `<span class="font-11 color-gray"><i class="mdi mdi-account"></i> Personlig</span>`;
            }
        }

        row.innerHTML = `
            <td>
                <div class="flex-col-start">
                    <span class="font-14 font-weight-medium ticket-subject" title="${escapeHtml(ticket.subject)}">${escapeHtml(ticket.subject)}</span>
                    <span class="font-12 color-gray">#${ticket.uid.substring(0, 8)}</span>
                </div>
            </td>
            <td>
                <div class="flex-col-start">
                    <span class="font-14">${escapeHtml(ticket.user_name || 'Ukendt')}</span>
                    <span class="font-12 color-gray">${escapeHtml(ticket.user_email || '')}</span>
                    ${onBehalfInfo}
                </div>
            </td>
            <td><span class="${typeClass}">${typeText}</span></td>
            <td><span class="font-13">${escapeHtml(ticket.category)}</span></td>
            <td><span class="${statusClass}">${statusText}</span></td>
            <td class="font-13 color-gray">${escapeHtml(ticket.created_at)}</td>
            <td class="text-right">
                <button class="btn-v2 action-btn h-35px" onclick="event.stopPropagation(); viewTicket('${ticket.uid}', '${ticket.detail_url}')">
                    <i class="mdi mdi-eye"></i>
                </button>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

/**
 * Render pagination controls
 */
function renderPagination(pagination) {
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');

    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    paginationInfo.textContent = `Viser ${start}-${end} af ${pagination.total}`;

    paginationButtons.innerHTML = '';

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="mdi mdi-chevron-left"></i>';
    prevBtn.disabled = pagination.current_page === 1;
    prevBtn.onclick = () => goToPage(pagination.current_page - 1);
    paginationButtons.appendChild(prevBtn);

    // Page numbers
    const totalPages = pagination.total_pages;
    const current = pagination.current_page;

    let pages = [];
    if (totalPages <= 7) {
        pages = Array.from({length: totalPages}, (_, i) => i + 1);
    } else {
        if (current <= 4) {
            pages = [1, 2, 3, 4, 5, '...', totalPages];
        } else if (current >= totalPages - 3) {
            pages = [1, '...', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
        } else {
            pages = [1, '...', current - 1, current, current + 1, '...', totalPages];
        }
    }

    pages.forEach(page => {
        if (page === '...') {
            const dots = document.createElement('span');
            dots.className = 'px-2 color-gray';
            dots.textContent = '...';
            paginationButtons.appendChild(dots);
        } else {
            const pageBtn = document.createElement('button');
            pageBtn.className = 'pagination-btn' + (page === current ? ' active' : '');
            pageBtn.textContent = page;
            pageBtn.onclick = () => goToPage(page);
            paginationButtons.appendChild(pageBtn);
        }
    });

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = '<i class="mdi mdi-chevron-right"></i>';
    nextBtn.disabled = pagination.current_page === totalPages;
    nextBtn.onclick = () => goToPage(pagination.current_page + 1);
    paginationButtons.appendChild(nextBtn);
}

/**
 * Go to specific page
 */
function goToPage(page) {
    currentPage = page;
    loadTickets();
}

/**
 * View ticket detail
 */
function viewTicket(ticketUid, detailUrl) {
    if (detailUrl) {
        window.location.href = detailUrl;
    } else {
        window.location.href = platformLinks.admin.supportDetail.replace('{id}', ticketUid);
    }
}

/**
 * Send admin reply (for detail page)
 */
async function sendAdminReply() {
    const textarea = document.getElementById('adminReplyMessage');
    const sendBtn = document.getElementById('sendReplyBtn');
    const message = textarea.value.trim();

    if (!message) {
        showErrorNotification('Fejl', 'Skriv venligst en besked');
        return;
    }

    const ticketUid = typeof currentTicketUid !== 'undefined' ? currentTicketUid : null;
    if (!ticketUid) {
        showErrorNotification('Fejl', 'Kunne ikke finde sag ID');
        return;
    }

    // Show loading state
    if (sendBtn) {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Sender...</span>';
    }

    const result = await post(platformLinks.api.admin.support.reply, {
        ticket_uid: ticketUid,
        message: message
    });

    if (result.status === 'success') {
        queueNotificationOnLoad('Sendt', 'Dit svar er sendt til brugeren', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Kunne ikke sende svar');
        // Reset button on error
        if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="mdi mdi-send"></i><span>Send svar</span>';
        }
    }
}

/**
 * Close a ticket
 */
async function closeTicket(ticketUid) {
    SweetPrompt.confirm('Luk sag?', 'Er du sikker på, at du vil lukke denne sag?', {
        confirmButtonText: 'Ja, luk sag',
        onConfirm: async () => {
            const result = await post(platformLinks.api.admin.support.close, {
                ticket_uid: ticketUid
            });

            if (result.status === 'success') {
                queueNotificationOnLoad('Lukket', 'Sagen er lukket', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke lukke sag');
            }
        }
    });
}

/**
 * Reopen a ticket
 */
function reopenTicket(ticketUid) {
    SweetPrompt.confirm('Genåbn sag?', 'Er du sikker på, at du vil genåbne denne sag?', {
        confirmButtonText: 'Ja, genåbn',
        onConfirm: async () => {
            const result = await post(platformLinks.api.admin.support.reopen, {
                ticket_uid: ticketUid
            });

            if (result.status === 'success') {
                queueNotificationOnLoad('Genåbnet', 'Sagen er genåbnet', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke genåbne sag');
            }
        }
    });
}

/**
 * Delete a ticket
 */
function deleteTicket(ticketUid) {
    SweetPrompt.confirm('Slet sag?', 'Er du sikker på, at du vil slette denne sag? Dette kan ikke fortrydes.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async () => {
            const result = await post(platformLinks.api.admin.support.delete, {
                ticket_uid: ticketUid
            });

            if (result.status === 'success') {
                queueNotificationOnLoad('Slettet', 'Sagen er slettet', 'success');
                window.location.href = HOST + platformLinks.admin.support;
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke slette sag');
            }
        }
    });
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
