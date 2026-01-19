/**
 * Rykker (Dunning) Management Functions
 */

function resetRykker(paymentId, apiPrefix) {
    var url = apiPrefix === 'admin'
        ? platformLinks.api.admin.payments.resetRykker.replace('{id}', paymentId)
        : platformLinks.api.orders.payments.resetRykker.replace('{id}', paymentId);

    SweetPrompt.confirm('Nulstil rykker?', 'Dette nulstiller rykker niveau og fjerner rykkergebyrer. Vil du fortsætte?', {
        confirmButtonText: 'Ja, nulstil',
        onConfirm: async function() {
            screenLoader.show('Nulstiller rykker...');
            var response = await post(url, {});
            screenLoader.hide();

            if (response.status === 'success') {
                queueNotificationOnLoad('Nulstillet', 'Rykker status er blevet nulstillet', 'success');
                location.reload();
            } else {
                showErrorNotification('Fejl', response.error?.message || response.message || 'Der opstod en fejl');
            }
        }
    });
}

function markForCollection(paymentId) {
    var url = platformLinks.api.admin.payments.markCollection.replace('{id}', paymentId);

    SweetPrompt.confirm('Send til inkasso?', 'Dette markerer betalingen til inkasso. Denne handling kan ikke fortrydes. Vil du fortsætte?', {
        confirmButtonText: 'Ja, send til inkasso',
        onConfirm: async function() {
            screenLoader.show('Sender til inkasso...');
            var response = await post(url, {});
            screenLoader.hide();

            if (response.status === 'success') {
                queueNotificationOnLoad('Sendt til inkasso', 'Betalingen er markeret til inkasso', 'success');
                location.reload();
            } else {
                showErrorNotification('Fejl', response.error?.message || response.message || 'Der opstod en fejl');
            }
        }
    });
}
