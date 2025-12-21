





async function teamInviteModal() {
    console.log(organisationLocations)
    let modal = new ModalHandler('organisationTeamInvite')
    modal.construct({roles: organisationRoles, locations: organisationLocations})
    await modal.build()
        .then(() => {
            selectV2();

            // Handle location scope type change
            let scopeTypeSelect = $('#location-scope-type');
            let scopedContainer = $('#scoped-locations-container');

            scopeTypeSelect.on('change', function() {
                if($(this).val() === 'scoped') {
                    scopedContainer.removeClass('d-none');
                } else {
                    scopedContainer.addClass('d-none');
                }
            });
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

            // Handle scoped locations
            let scopeType = formData.get('location_scope_type');
            if(scopeType === 'scoped') {
                let scopedLocationsSelect = $('#scoped-locations-select');
                let selectedLocations = scopedLocationsSelect.val();
                if(!selectedLocations || selectedLocations.length === 0) {
                    setError('Vælg venligst mindst én lokation for scoped tilladelser');
                    screenLoader.hide()
                    return false;
                }
                formData.set('scoped_locations', JSON.stringify(selectedLocations));
            } else {
                formData.delete('scoped_locations');
            }
            formData.delete('location_scope_type');

            let result = await post(platformLinks.api.organisation.team.invite, formData);

            if(result.status === "error") {
                setError(result.error.message)
                screenLoader.hide()
                return false;
            }

            screenLoader.hide()

            // Check if a new user was created
            if(result.data && result.data.user_created) {
                // Show credentials modal
                showCredentialsModal(result.data)
            } else {
                // Existing user invited
                notifyTopCorner(result.message, 1500, "bg-success")
                screenLoader.update('Team member invited. Refreshing the page...')
                handleStandardApiRedirect(result, 800)
            }
        }
    })
    modal.open()
}


function showCredentialsModal(data) {
    // Close the invite modal first
    $('#organisation-team-invite').modal('hide')

    // Create credentials modal
    let credentialsHtml = `
        <div class="modal fade" id="team-credentials-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog vertical-middle" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bruger Oprettet!</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="font-14 mb-3">
                            ${data.full_name} er blevet oprettet og tilføjet til dit team.
                            ${data.email_sent ? 'En email med loginoplysninger er sendt.' : 'Del følgende loginoplysninger med personen:'}
                        </p>
                        <div class="p-3 bg-light border-radius-5" style="background: #f8f9fa;">
                            <div class="flex-col-start" style="row-gap: 1rem;">
                                <div>
                                    <p class="font-12 font-weight-bold mb-1 color-gray">Brugernavn:</p>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="font-16 font-weight-bold mb-0">${data.username}</p>
                                        <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.username}', this)">
                                            <i class="fa fa-copy"></i> Kopier
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-12 font-weight-bold mb-1 color-gray">Midlertidigt Kodeord:</p>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="font-16 font-weight-bold mb-0">${data.password}</p>
                                        <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.password}', this)">
                                            <i class="fa fa-copy"></i> Kopier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="font-12 color-orange mt-3 mb-0">
                            <i class="fa fa-info-circle"></i> Brugeren vil blive bedt om at ændre kodeord ved første login.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-v2 action-btn" onclick="closeCredentialsAndRefresh()">
                            Forstået
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `

    // Remove existing credentials modal if any
    $('#team-credentials-modal').remove()

    // Add and show new modal
    $('body').append(credentialsHtml)
    $('#team-credentials-modal').modal('show')
}

function copyCredential(text, btn) {
    let $btn = $(btn)
    let originalHtml = $btn.html()

    // Use modern Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            $btn.html('<i class="fa fa-check"></i> Kopieret!')
            notifyTopCorner("Copied!")
            setTimeout(() => {
                $btn.html(originalHtml)
            }, 2000)
        }).catch(() => {
            // Fallback to old method
            fallbackCopy(text, $btn, originalHtml)
        })
    } else {
        // Fallback for older browsers or non-secure contexts
        fallbackCopy(text, $btn, originalHtml)
    }
}

function fallbackCopy(text, $btn, originalHtml) {
    const el = document.createElement('textarea')
    el.value = text
    el.setAttribute('readonly', '')
    el.style.position = 'absolute'
    el.style.left = '-9999px'
    document.body.appendChild(el)
    el.select()
    const success = document.execCommand('copy')
    document.body.removeChild(el)

    if (success) {
        $btn.html('<i class="fa fa-check"></i> Kopieret!')
        notifyTopCorner("Copied!")
        setTimeout(() => {
            $btn.html(originalHtml)
        }, 2000)
    }
}

function closeCredentialsAndRefresh() {
    $('#team-credentials-modal').modal('hide')
    setTimeout(() => {
        window.location.reload()
    }, 300)
}


async function teamMemberAction(btn) {
    btn = $(btn);
    let row = btn.parents("tr").first()
    let role = row.find("select[name=role]").first().val()
    let action = btn.attr('data-team-action')
    let memberUuid = btn.attr('data-uuid')
    if(empty(role, action, memberUuid)) return;

    // Handle edit-scoped-locations action separately
    if(action === 'edit-scoped-locations') {
        let memberName = row.find("td").first().text().trim()
        await editMemberScopedLocations(memberUuid, memberName)
        return;
    }

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


async function editMemberScopedLocations(memberUuid, memberName) {
    // First, fetch current member data
    screenLoader.show("Henter data...")
    let memberDataResult = await post(platformLinks.api.organisation.team.scopedLocations.get, {member_uuid: memberUuid});
    screenLoader.hide()


    if(memberDataResult.status === "error") {
        notifyTopCorner(memberDataResult.error.message, 5000, "bg-red")
        return false;
    }

    let memberData = memberDataResult.data
    let scopedLocations = memberData.scoped_locations || []
    let scopeType = scopedLocations.length > 0 ? 'scoped' : 'all'

    let modal = new ModalHandler('organisationMemberScopedLocations')
    modal.construct({
        member_name: memberName,
        member_uuid: memberUuid,
        locations: organisationLocations,
        selected_locations: scopedLocations,
        scope_type: scopeType
    })
    await modal.build()
        .then(() => {
            selectV2();

            // Handle location scope type change
            let scopeTypeSelect = $('#edit-location-scope-type');
            let scopedContainer = $('#edit-scoped-locations-container');

            scopeTypeSelect.on('change', function() {
                if($(this).val() === 'scoped') {
                    scopedContainer.removeClass('d-none');
                } else {
                    scopedContainer.addClass('d-none');
                }
            });
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        save: async (btn, modalHandler) => {
            let parent = btn.parents('#organisation-member-scoped-locations').first()
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
            screenLoader.show("Gemmer ændringer...")

            let formData = new FormData(form.get(0))
            formData.append('member_uuid', memberUuid)

            // Handle scoped locations
            let scopeType = formData.get('location_scope_type');
            if(scopeType === 'scoped') {
                let scopedLocationsSelect = $('#edit-scoped-locations-select');
                let selectedLocations = scopedLocationsSelect.val();
                if(!selectedLocations || selectedLocations.length === 0) {
                    setError('Vælg venligst mindst én lokation for scoped tilladelser');
                    screenLoader.hide()
                    return false;
                }
                formData.set('scoped_locations', JSON.stringify(selectedLocations));
            } else {
                formData.set('scoped_locations', JSON.stringify([]));
            }
            formData.delete('location_scope_type');

            let result = await post(platformLinks.api.organisation.team.scopedLocations.update, formData);

            if(result.status === "error") {
                setError(result.error.message)
                screenLoader.hide()
                return false;
            }

            screenLoader.hide()
            notifyTopCorner(result.message, 1500, "bg-success")
            screenLoader.update('Ændringer gemt. Genindlæser siden...')
            handleStandardApiRedirect(result, 800)
        }
    })
    modal.open()
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



