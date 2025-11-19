

function toggleOrganisationCreateAndJoinCards(showCreate = true) {
    let showTargetSelector = showCreate ? '.organisation-create-card' : '.organisation-join-card';
    let hideTargetSelector = !showCreate ? '.organisation-create-card' : '.organisation-join-card';
    let showTarget = $(document).find(showTargetSelector).first().parents('.organisation-container').first(),
        hideTarget = $(document).find(hideTargetSelector).first().parents('.organisation-container').first();

    console.log(showTarget.length,  hideTarget.length)
    if(empty(showTarget, hideTarget)) return;
    hideTarget.hide();
    showTarget.show();
}
$(document).on("click", '.organisation-show-form, .organisation-hide-form', function () {
    let showCreate = $(this).hasClass('organisation-show-form');
    toggleOrganisationCreateAndJoinCards(showCreate);
})



async function invitationAction(btn) {
    if('invitationAction' in applicationProcessing && applicationProcessing.invitationAction) return false;
    applicationProcessing.invitationAction = true;
    btn.disabled = true
    btn = $(btn);
    let action = btn.attr('data-invitation-action')
    let organisationId = btn.attr('data-organisation-id')
    if(empty(action, organisationId)) {
        applicationProcessing.invitationAction = false;
        btn.get(0).disabled = false
        return;
    }

    let result = await post('api/organisations/team/respond', {action, organisation_id: organisationId});

    if(result.status === "error") {
        showErrorNotification("Der opstod en fejl",result.error.message)
        applicationProcessing.invitationAction = false;
        btn.get(0).disabled = false
        return false;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result)
    if(action === 'decline') {
        btn.parents('tr').first().remove()
    }
    removeQueuedNotification();
    showSuccessNotification("Handling fuldført", result.message)
    applicationProcessing.invitationAction = false;
    btn.get(0).disabled = false
}



async function createOrganisation(btn) {
    if('createOrganisation' in applicationProcessing && applicationProcessing.createOrganisation) return false;
    applicationProcessing.createOrganisation = true;
    btn.disabled = true
    let form = $(btn).parents('form').first();
    form.find('.error-shadow').each(function () { $(this).removeClass('error-shadow') })
    let formData = new FormData(form.get(0))
    let dest = form.attr("action")

    let result = await post(dest, formData);

    if(result.status === "error") {
        showErrorNotification("Der opstod en fejl",result.error.message)
        applicationProcessing.createOrganisation = false;
        btn.disabled = false

        if('blame_field' in result.error) {
            let blameId = result.error.blame_field;
            let blameElement = form.find(`[name=${blameId}]`).first();
            if(blameElement.length) blameElement.addClass('error-shadow')
        }

        return false;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result)
}
