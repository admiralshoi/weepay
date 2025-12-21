

async function teamInviteModal() {
    let modal = new ModalHandler('locationTeamInvite')
    modal.construct({roles: locationRoles, organisation_members: organisationMembers})
    await modal.build()
        .then(() => {
            selectV2();

            // Handle user type selection
            let userTypeSelect = $('#user-type-select');
            let newUserFields = $('#new-user-fields');
            let existingUserField = $('#existing-user-field');

            userTypeSelect.on('change', function() {
                if($(this).val() === 'new') {
                    newUserFields.removeClass('d-none');
                    existingUserField.addClass('d-none');
                } else {
                    newUserFields.addClass('d-none');
                    existingUserField.removeClass('d-none');
                }
            });
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        invite: async (btn, modalHandler) => {
            let parent = btn.parents('#location-team-invite').first()
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
            screenLoader.show("Tilføjer medlem...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.invite, formData);

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
                queueNotificationOnLoad("Udført", result.message, 'success')
                screenLoader.hide()
                handleStandardApiRedirect(result, 1)
            }
        }
    })
    modal.open()
}


function showCredentialsModal(data) {
    // Close the invite modal first
    $('#location-team-invite').modal('hide')

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
            notifyTopCorner("Kopieret!")
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
        notifyTopCorner("Kopieret!")
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

    screenLoader.show("Opdaterer medlem...")
    let result = await post(platformLinks.api.locations.team.update, {action, role, member_uuid: memberUuid, location_uid: currentLocation.uid});

    if(result.status === "error") {
        screenLoader.hide()
        showErrorNotification(result.error.message)
        return false;
    }

    screenLoader.hide()
    queueNotificationOnLoad("Udført", result.message, 'success')
    handleStandardApiRedirect(result)
}

async function locationCreateRole() {
    let modal = new ModalHandler('locationCreateRole')
    modal.construct({})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        create: async (btn, modalHandler) => {
            let parent = btn.parents('#location-new-role').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();

            const clearError = () => {
                if(errorBox.length) {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
            }
            const setError = (txt) => {
                if(errorBox.length) {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                }
                btn.removeAttr('disabled')
            }

            btn.attr('disabled', 'disabled')
            clearError()
            screenLoader.show("Opretter rolle...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.role.create, formData);

            if(result.status === "error") {
                btn.removeAttr('disabled')
                screenLoader.hide()
                setError(result.error.message)
                return false;
            }

            screenLoader.hide()
            queueNotificationOnLoad("Udført", result.message, 'success')
            handleStandardApiRedirect(result)
        }
    })
    modal.open()
}

async function locationRenameRole(btn) {
    let role = $(btn).attr('data-role')
    let modal = new ModalHandler('locationRenameRole')
    modal.construct({role: role})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        rename: async (btn, modalHandler) => {
            let parent = btn.parents('#location-rename-role').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();

            const clearError = () => {
                if(errorBox.length) {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
            }
            const setError = (txt) => {
                if(errorBox.length) {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                }
                btn.removeAttr('disabled')
            }

            btn.attr('disabled', 'disabled')
            clearError()
            screenLoader.show("Omdøber rolle...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.role.rename, formData);

            if(result.status === "error") {
                btn.removeAttr('disabled')
                screenLoader.hide()
                setError(result.error.message)
                return false;
            }

            screenLoader.hide()
            queueNotificationOnLoad("Udført", result.message, 'success')
            handleStandardApiRedirect(result)
        }
    })
    modal.open()
}

async function locationDeleteRole(btn) {
    let $btn = $(btn)
    let role = $btn.attr('data-role')

    if(!await confirmAction(`Er du sikker på, at du vil slette rollen "${role}"?`)) {
        return false;
    }

    $btn.attr('disabled', 'disabled')
    screenLoader.show("Sletter rolle...")

    let result = await deleteRequest(platformLinks.api.locations.team.role.delete, {role: role, location_uid: currentLocation.uid});

    if(result.status === "error") {
        $btn.removeAttr('disabled')
        screenLoader.hide()
        showErrorNotification(result.error.message)
        return false;
    }

    screenLoader.hide()
    queueNotificationOnLoad("Udført", result.message, 'success')
    handleStandardApiRedirect(result)
}

async function locationEditRolePermissions(btn) {
    let $btn = $(btn)
    let select = $(document).find("select#role_permissions").first();
    if(empty(select)) return;
    let role = select.val()
    if(role === "owner") return;
    let viewId = select.attr("name")
    let parent = select.parents(`[data-switchParent][data-switch-id=${viewId}]`).first();
    let form = parent.find(`form.switchViewObject[data-switch-id=${viewId}][data-switch-object-name=${role}]`).first()
    if(empty(form)) return;

    $btn.attr('disabled', 'disabled')
    screenLoader.show("Gemmer tilladelser...")

    let formData = new FormData(form.get(0))
    formData.append('location_uid', currentLocation.uid)
    formData.append('role', role)

    let result = await post(platformLinks.api.locations.team.role.permissions, formData);

    if(result.status === "error") {
        $btn.removeAttr('disabled')
        screenLoader.hide()
        showErrorNotification(result.error.message)
        return false;
    }

    $btn.removeAttr('disabled')
    screenLoader.hide()
    showSuccessNotification(result.message)
    // Don't redirect for permissions, just show success
}
