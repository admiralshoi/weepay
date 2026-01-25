/**
 * Admin Refunds JS
 * Handles refund functionality for orders and payments in admin dashboard
 */

function initAdminRefunds() {
    // Bind refund order buttons
    $('[data-refund-order]').on('click', function() {
        var orderId = $(this).attr('data-refund-order');
        var hasPendingPayments = $(this).attr('data-has-pending-payments') === '1';
        if (orderId) {
            adminRefundOrder(orderId, hasPendingPayments);
        }
    });

    // Bind refund payment buttons
    $('[data-refund-payment]').on('click', function() {
        var paymentId = $(this).attr('data-refund-payment');
        if (paymentId) {
            adminRefundPayment(paymentId);
        }
    });
}

/**
 * Refund an entire order (all completed payments)
 * @param {string} orderId - The order UID
 * @param {boolean} hasPendingPayments - Whether order has pending/scheduled payments that will be voided
 */
function adminRefundOrder(orderId, hasPendingPayments) {
    var warningText = 'Er du sikker på at du vil refundere hele denne ordre? Alle gennemførte betalinger vil blive refunderet.';

    if (hasPendingPayments) {
        warningText += '\n\nBemærk: Alle fremtidige/afventende betalinger vil blive annulleret (voided).';
    }

    SweetPrompt.confirm(
        'Refunder ordre?',
        warningText,
        {
            confirmButtonText: 'Ja, refunder',
            onConfirm: async function() {
                var response = await post('api/admin/orders/' + orderId + '/refund', {});

                if (response.status === 'success') {
                    queueNotificationOnLoad('Ordre refunderet', response.message || 'Ordren blev refunderet succesfuldt.', 'success', 5000);
                    handleStandardApiRedirect(response, 1);
                    return { status: 'success' };
                } else {
                    showErrorNotification('Refundering fejlede', response.error?.message || response.message || 'Der opstod en fejl under refunderingen.');
                    return { status: 'error' };
                }
            },
            refireAfter: true
        }
    );
}

/**
 * Refund a single payment
 * @param {string} paymentId - The payment UID
 */
function adminRefundPayment(paymentId) {
    SweetPrompt.confirm(
        'Refunder betaling?',
        'Er du sikker på at du vil refundere denne betaling?',
        {
            confirmButtonText: 'Ja, refunder',
            onConfirm: async function() {
                var response = await post('api/admin/payments/' + paymentId + '/refund', {});

                if (response.status === 'success') {
                    queueNotificationOnLoad('Betaling refunderet', response.message || 'Betalingen blev refunderet succesfuldt.', 'success', 5000);
                    handleStandardApiRedirect(response, 1);
                    return { status: 'success' };
                } else {
                    showErrorNotification('Refundering fejlede', response.error?.message || response.message || 'Der opstod en fejl under refunderingen.');
                    return { status: 'error' };
                }
            },
            refireAfter: true
        }
    );
}
