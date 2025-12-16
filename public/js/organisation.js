





async function teamInviteModal() {
    let modal = new ModalHandler('organisationTeamInvite')
    modal.construct({roles: organisationRoles})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        invite: async (btn, modalHandler) => {
            let parent = btn.parents('#organisation-team-invite').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;
            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
            }
            const end = () => {
                btn.removeAttr('disabled')
            }
            const start = () => {
                btn.attr('disabled', 'disabled')
                clearError()
            }
            const setError = (txt) => {
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                end()
            }


            start();
            screenLoader.show("Adding member...")

            let formData = new FormData(form.get(0))
            let result = await post(platformLinks.api.organisation.team.invite, formData);

            if(result.status === "error") {
                setError(result.error.message)
                screenLoader.hide()
                return false;
            }

            notifyTopCorner(result.message, 1500, "bg-success")
            screenLoader.update('Team member invited. Refreshing the page...')

            handleStandardApiRedirect(result, 800)
        }
    })
    modal.open()
}


async function teamMemberAction(btn) {
    btn = $(btn);
    let row = btn.parents("tr").first()
    let role = row.find("select[name=role]").first().val()
    let action = btn.attr('data-team-action')
    let memberUuid = btn.attr('data-uuid')
    if(empty(role, action, memberUuid)) return;

    screenLoader.show("Updating member...")
    let result = await post(platformLinks.api.organisation.team.update, {action, role, member_uuid: memberUuid});

    if(result.status === "error") {
        screenLoader.hide()
        notifyTopCorner(result.error.message, 5000, "bg-red")
        return false;
    }

    screenLoader.hide()
    notifyTopCorner(result.message)
    handleStandardApiRedirect(result, 1000)
}


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

    let result = await post(platformLinks.api.organisation.team.respond, {action, organisation_id: organisationId});

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



async function organisationCreateRole() {
    let modal = new ModalHandler('organisationCreateRole')
    modal.construct()
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        create: async (btn, modalHandler) => {
            if('organisationCreateRole' in applicationProcessing && applicationProcessing.organisationCreateRole) return false;
            applicationProcessing.organisationCreateRole = true;
            let parent = btn.parents('.modal').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;

            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
                form.find('.error-shadow').each(function () { $(this).removeClass('error-shadow') })
            }
            const end = () => {
                btn.get(0).disabled = false;
                applicationProcessing.organisationCreateRole = false;
            }
            const start = () => {
                btn.get(0).disabled = true;
                clearError()
            }
            const setError = (error) => {
                let txt = error.message
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                if('blame_field' in error) {
                    let blameId = error.blame_field;
                    let blameElement = form.find(`[name=${blameId}]`).first();
                    if(blameElement.length) blameElement.addClass('error-shadow')
                }
                end()
            }
            start();

            let formData = new FormData(form.get(0))
            let result = await post(platformLinks.api.organisation.team.role.create, formData);

            if(result.status === "error") {
                setError(result.error)
                return false;
            }

            queueNotificationOnLoad("Handling fuldført", result.message, 'success')
            handleStandardApiRedirect(result, 1)

            setTimeout(function (){
                applicationProcessing.organisationCreateRole = false;
                removeQueuedNotification();
                showSuccessNotification("Handling fuldført", result.message)
            }, 100)

        }
    })
    modal.open()
}


async function organisationRenameRole(btn) {
    btn = $(btn);
    let role = btn.attr("data-role")
    if(empty(role)) return;
    let modal = new ModalHandler('organisationRenameRole')
    modal.construct({role: prepareProperNameString(role)})
    await modal.build()
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        rename: async (btn, modalHandler) => {
            if('organisationRenameRole' in applicationProcessing && applicationProcessing.organisationRenameRole) return false;
            applicationProcessing.organisationRenameRole = true;
            let parent = btn.parents('.modal').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;

            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
                form.find('.error-shadow').each(function () { $(this).removeClass('error-shadow') })
            }
            const end = () => {
                btn.get(0).disabled = false;
                applicationProcessing.organisationRenameRole = false;
            }
            const start = () => {
                btn.get(0).disabled = true;
                clearError()
            }
            const setError = (error) => {
                let txt = error.message
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                if('blame_field' in error) {
                    let blameId = error.blame_field;
                    let blameElement = form.find(`[name=${blameId}]`).first();
                    if(blameElement.length) blameElement.addClass('error-shadow')
                }
                end()
            }
            start();

            let formData = new FormData(form.get(0))
            formData.append("role", role);
            let result = await post(platformLinks.api.organisation.team.role.rename, formData);

            if(result.status === "error") {
                setError(result.error)
                return false;
            }

            queueNotificationOnLoad("Handling fuldført", result.message, 'success')
            handleStandardApiRedirect(result, 1)

            setTimeout(function (){
                applicationProcessing.organisationRenameRole = false;
                removeQueuedNotification();
                showSuccessNotification("Handling fuldført", result.message)
            }, 100)
        }
    })
    modal.open()
}




async function organisationDeleteRole(btn) {
    btn = $(btn);
    let role = btn.attr("data-role")
    if(empty(role)) return;
    if('organisationDeleteRole' in applicationProcessing && applicationProcessing.organisationDeleteRole) return false;
    applicationProcessing.organisationDeleteRole = true;
    btn.get(0).disabled = true;

    let result = await del(platformLinks.api.organisation.team.role.delete, {role});

    if(result.status === "error") {
        applicationProcessing.organisationDeleteRole = false;
        showErrorNotification("Der opstod en fejl",result.error.message)
        btn.get(0).disabled = false;
        return false;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result, 1)

    setTimeout(function (){
        applicationProcessing.organisationDeleteRole = false;
        btn.get(0).disabled = false;
        removeQueuedNotification();
        showSuccessNotification("Handling fuldført", result.message)
    }, 100)
}



async function editRolePermissions(btn) {
    if('editRolePermissions' in applicationProcessing && applicationProcessing.editRolePermissions) return false;
    let select = $(document).find("select#role_permissions").first();
    if(empty(select)) return;
    let role = select.val()
    if(role === "owner") return;
    let viewId = select.attr("name")
    let parent = select.parents(`[data-switchParent][data-switch-id=${viewId}]`).first();
    let form = parent.find(`form.switchViewObject[data-switch-id=${viewId}][data-switch-object-name=${role}]`).first()
    if(empty(form)) return;
    applicationProcessing.editRolePermissions = true;
    btn.disabled = true


    let formData = new FormData(form.get(0))
    formData.append("role", role);
    let result = await post(platformLinks.api.organisation.team.role.permissions, formData);
    if(result.status === "error") {
        applicationProcessing.editRolePermissions = false;
        showErrorNotification("Der opstod en fejl",result.error.message)
        btn.disabled = false
        return;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result, 1)

    setTimeout(function (){
        applicationProcessing.editRolePermissions = false;
        btn.disabled = false;
        removeQueuedNotification();
        showSuccessNotification("Handling fuldført", result.message)
    }, 100)
}



