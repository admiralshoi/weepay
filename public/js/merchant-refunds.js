/**
 * Merchant Refunds JS
 * Handles refund functionality for orders and payments
 */

function initMerchantRefunds() {
    // Bind refund order buttons
    $('[data-refund-order]').on('click', function() {
        var orderId = $(this).attr('data-refund-order');
        var hasPendingPayments = $(this).attr('data-has-pending-payments') === 'true';
        if (orderId) {
            refundOrder(orderId, hasPendingPayments);
        }
    });

    // Bind refund payment buttons
    $('[data-refund-payment]').on('click', function() {
        var paymentId = $(this).attr('data-refund-payment');
        if (paymentId) {
            refundPayment(paymentId);
        }
    });
}

/**
 * Refund an entire order (all completed payments)
 * @param {string} orderId - The order UID
 * @param {boolean} hasPendingPayments - Whether order has pending/scheduled payments that will be voided
 */
function refundOrder(orderId, hasPendingPayments) {
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
                var response = await post('api/merchant/orders/' + orderId + '/refund', {});

                if (response.status === 'success') {
                    queueNotificationOnLoad('Ordre refunderet', response.message || 'Ordren blev refunderet succesfuldt.', 'success', 5000);
                    handleStandardApiRedirect(response, 1);
                    return {
                        status: 'success',
                        title: 'Ordre refunderet',
                        message: response.message || 'Ordren blev refunderet succesfuldt.'
                    };
                } else {
                    return {
                        status: 'error',
                        title: 'Refundering fejlede',
                        message: response.error?.message || response.message || 'Der opstod en fejl under refunderingen.'
                    };
                }
            }
        }
    );
}

/**
 * Refund a single payment
 */
function refundPayment(paymentId) {
    SweetPrompt.confirm(
        'Refunder betaling?',
        'Er du sikker på at du vil refundere denne betaling?',
        {
            confirmButtonText: 'Ja, refunder',
            onConfirm: async function() {
                var response = await post('api/merchant/payments/' + paymentId + '/refund', {});

                if (response.status === 'success') {
                    queueNotificationOnLoad('Betaling refunderet', response.message || 'Betalingen blev refunderet succesfuldt.', 'success', 5000);
                    handleStandardApiRedirect(response, 1);
                    return {
                        status: 'success',
                        title: 'Betaling refunderet',
                        message: response.message || 'Betalingen blev refunderet succesfuldt.'
                    };
                } else {
                    return {
                        status: 'error',
                        title: 'Refundering fejlede',
                        message: response.error?.message || response.message || 'Der opstod en fejl under refunderingen.'
                    };
                }
            }
        }
    );
}
