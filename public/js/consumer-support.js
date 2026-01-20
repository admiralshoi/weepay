/**
 * Consumer Support JavaScript
 */

// State
let currentFilter = 'open';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initSupportPage();
});

function initSupportPage() {
    // Initialize form submission
    const createForm = document.getElementById('createTicketForm');
    if (createForm) {
        createForm.addEventListener('submit', createTicket);
    }

    // Initialize select-v2 on any selects
    initializeSelectV2();
}

/**
 * Filter tickets by status
 */
function filterTickets(status) {
    currentFilter = status;

    // Update toggle buttons
    document.getElementById('toggleOpen').classList.toggle('active', status === 'open');
    document.getElementById('toggleClosed').classList.toggle('active', status === 'closed');

    // Filter ticket cards
    const tickets = document.querySelectorAll('.ticket-card[data-status]');
    let visibleCount = 0;

    tickets.forEach(ticket => {
        const ticketStatus = ticket.dataset.status;
        if (ticketStatus === status) {
            ticket.style.display = '';
            visibleCount++;
        } else {
            ticket.style.display = 'none';
            // Collapse if hidden
            ticket.classList.remove('expanded');
            const body = ticket.querySelector('.ticket-body');
            if (body) body.style.display = 'none';
        }
    });

    // Show/hide empty states
    const emptyOpenState = document.getElementById('emptyOpenState');
    const emptyClosedState = document.getElementById('emptyClosedState');

    if (emptyOpenState) {
        emptyOpenState.style.display = (status === 'open' && visibleCount === 0) ? 'flex' : 'none';
    }
    if (emptyClosedState) {
        emptyClosedState.style.display = (status === 'closed' && visibleCount === 0) ? 'flex' : 'none';
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
 * Toggle ticket card expand/collapse
 */
function toggleTicket(ticketUid) {
    const card = document.querySelector(`.ticket-card[data-ticket-uid="${ticketUid}"]`);
    if (!card) return;

    const body = card.querySelector('.ticket-body');
    const isExpanded = card.classList.contains('expanded');

    if (isExpanded) {
        card.classList.remove('expanded');
        body.style.display = 'none';
    } else {
        card.classList.add('expanded');
        body.style.display = 'block';

        // Scroll messages to bottom
        const messagesContainer = document.getElementById(`ticketMessages_${ticketUid}`);
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }
}

/**
 * Create a new support ticket
 */
async function createTicket(e) {
    e.preventDefault();

    const form = e.target;
    const btn = document.getElementById('createTicketBtn');
    const originalText = btn.innerHTML;

    const category = form.querySelector('[name="category"]').value;
    const subject = form.querySelector('[name="subject"]').value.trim();
    const message = form.querySelector('[name="message"]').value.trim();

    if (!category || !subject || !message) {
        showErrorNotification('Fejl', 'Udfyld venligst alle felter');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Sender...</span>';

    const result = await post(platformLinks.api.consumer.support.create, {
        category: category,
        subject: subject,
        message: message
    });

    btn.disabled = false;
    btn.innerHTML = originalText;

    if (result.status === 'success') {
        queueNotificationOnLoad('Sendt', 'Din henvendelse er oprettet', 'success');
        window.location.reload();
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Kunne ikke oprette henvendelse');
    }
}

/**
 * Add a reply to a ticket
 */
async function addReply(ticketUid) {
    const textarea = document.getElementById(`replyMessage_${ticketUid}`);
    const message = textarea.value.trim();

    if (!message) {
        showErrorNotification('Fejl', 'Skriv venligst en besked');
        return;
    }

    const result = await post(platformLinks.api.consumer.support.reply, {
        ticket_uid: ticketUid,
        message: message
    });

    if (result.status === 'success') {
        showSuccessNotification('Sendt', 'Dit svar er sendt');
        textarea.value = '';

        // Append the new message to the container
        const reply = result.data.reply;
        const messagesContainer = document.getElementById(`ticketMessages_${ticketUid}`);
        if (messagesContainer && reply) {
            const replyHtml = `
                <div class="ticket-message user-message">
                    <div class="message-header">
                        <span class="font-12 font-weight-medium">Dig</span>
                        <span class="font-11 color-gray">${escapeHtml(reply.created_at)}</span>
                    </div>
                    <div class="message-content">
                        ${escapeHtml(reply.message).replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
            messagesContainer.insertAdjacentHTML('beforeend', replyHtml);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    } else {
        showErrorNotification('Fejl', result.error?.message || 'Kunne ikke sende svar');
    }
}

/**
 * Close a ticket
 */
function closeTicket(ticketUid) {
    SweetPrompt.confirm('Luk henvendelse?', 'Er du sikker på, at du vil lukke denne henvendelse?', {
        confirmButtonText: 'Ja, luk sag',
        onConfirm: async () => {
            const result = await post(platformLinks.api.consumer.support.close, {
                ticket_uid: ticketUid
            });

            if (result.status === 'success') {
                queueNotificationOnLoad('Lukket', 'Henvendelsen er lukket', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke lukke henvendelse');
            }
        }
    });
}

/**
 * Reopen a ticket
 */
function reopenTicket(ticketUid) {
    SweetPrompt.confirm('Genåbn henvendelse?', 'Er du sikker på, at du vil genåbne denne henvendelse?', {
        confirmButtonText: 'Ja, genåbn',
        onConfirm: async () => {
            const result = await post(platformLinks.api.consumer.support.reopen, {
                ticket_uid: ticketUid
            });

            if (result.status === 'success') {
                queueNotificationOnLoad('Genåbnet', 'Henvendelsen er genåbnet', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke genåbne henvendelse');
            }
        }
    });
}

/**
 * Load replies for a ticket (no-op, replies are server-rendered)
 */
async function loadTicketReplies(ticketUid) {
    // Replies are now server-rendered, page reload will show new replies
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
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
