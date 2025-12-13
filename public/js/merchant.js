

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
            let result = await post('api/organisations/team/invite', formData);

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
                setError(result.error.message)
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
                setError(result.error.message)
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
        applicationProcessing.createOrganisation = false;
        showErrorNotification("Der opstod en fejl",result.error.message)
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
        applicationProcessing.organisationDeleteRole = false;
        btn.disabled = false;
        removeQueuedNotification();
        showSuccessNotification("Handling fuldført", result.message)
    }, 100)
}



async function editOrganisationDetails() {
    let modal = new ModalHandler('organisationDetails')
    modal.construct({organisation, allowedCountries, worldCountries, defaultCountry, currencies})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        update: async (btn, modalHandler) => {
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
            let result = await post(platformLinks.api.forms.merchant.editOrganisationDetails, formData);

            if(result.status === "error") {
                setError(result.error)
                return false;
            }

            queueNotificationOnLoad('Success', result.message, 'success')
            handleStandardApiRedirect(result)
            modalHandler.dispose()
        }
    })
    modal.open()
}




const LocationActions = {
    isInit: false,
    isOpen: false,
    locationId: null,

    init() {
        if(this.isInit) return;
        this.isInit = true;
        this.sidebar = document.getElementById('locationAction');
        this.closeRightSidebarBtn = this.sidebar.querySelector('.closeRightSidebarBtn');



        if (!document.getElementById('pageBackdrop')) {
            this.backdrop = document.createElement('div');
            this.backdrop.id = 'pageBackdrop';
            document.body.appendChild(this.backdrop);
        } else {
            this.backdrop = document.getElementById('pageBackdrop');
        }
        this.backdrop.addEventListener('click', () => this.closeCampaignSidebar());

        // Bind events
        if (this.closeRightSidebarBtn) {
            this.closeRightSidebarBtn.addEventListener('click', () => this.closeCampaignSidebar());
        }

        $(this.sidebar).on('click', '.edit-section .edit-header', function () { LocationActions.toggleEditSections($(this)) })
    },
    toggleEditSections (btn) {
        let parent = $(btn).parents('.edit-section').first()
        if(!parent.length) return;
        parent.toggleClass('open')
    },
    open(btn) {
        if(this.isOpen) return;
        let locationId = btn.dataset.locationId;
        if(empty(locationId)) return;
        this.locationId = locationId;
        this.init();
        this.sidebar.classList.add('open');
        if (this.backdrop) this.backdrop.classList.add('active');
        this.isOpen = true;
    },
    closeCampaignSidebar() {
        if(!this.isOpen) return;
        this.sidebar.classList.remove('open');
        if (this.backdrop) this.backdrop.classList.remove('active');
        this.isOpen = false;
    },

    async addNewLocation() {
        let modal = new ModalHandler('locationAddNew')
        modal.construct({SITE_NAME, allowedCountries, worldCountries, defaultCountry, currencies})
        await modal.build()
            .then(() => {
                selectV2();
                setTooltips();
            })
        modal.bindEvents({
            onclose: (modalHandler) => {
                modalHandler.dispose();
            },
            create: async (btn, modalHandler) => {
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
                let result = await post(platformLinks.api.forms.merchant.addNewLocation, formData);

                if(result.status === "error") {
                    setError(result.error)
                    return false;
                }

                queueNotificationOnLoad('Success', result.message, 'success')
                handleStandardApiRedirect(result)
                modalHandler.dispose()
            }
        })
        modal.open()
    },


    async editLocationDetails() {
        if(empty(this.locationId)) return;
        let location = locations.find(l => l.uid === this.locationId)
        console.log(location)


        let modal = new ModalHandler('locationDetails')
        modal.construct({location, organisation, allowedCountries, worldCountries, defaultCountry, SITE_NAME, currencies})
        await modal.build()
            .then(() => {
                selectV2();
            })
        modal.bindEvents({
            onclose: (modalHandler) => {
                modalHandler.dispose();
            },
            update: async (btn, modalHandler) => {
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
                let result = await post(platformLinks.api.forms.merchant.editLocationDetails, formData);

                if(result.status === "error") {
                    setError(result.error)
                    return false;
                }

                queueNotificationOnLoad('Success', result.message, 'success')
                handleStandardApiRedirect(result)
                modalHandler.dispose()
            }
        })
        modal.open()
    },

    async addNewTerminal() {
        let modal = new ModalHandler('terminalAddNew')
        modal.construct({locations, selectedLocation})
        await modal.build()
            .then(() => {
                selectV2();
                setTooltips();
            })
        modal.bindEvents({
            onclose: (modalHandler) => {
                modalHandler.dispose();
            },
            create: async (btn, modalHandler) => {
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
                let result = await post(platformLinks.api.forms.merchant.addNewTerminal, formData);

                if(result.status === "error") {
                    setError(result.error)
                    return false;
                }

                queueNotificationOnLoad('Success', result.message, 'success')
                handleStandardApiRedirect(result)
                modalHandler.dispose()
            }
        })
        modal.open()
    },

    async editTerminal(terminalId) {
        if(empty(terminalId)) return;
        let terminal = terminals.find(t => t.uid === terminalId)
        if(empty(terminal)) return;

        let modal = new ModalHandler('terminalDetails')
        modal.construct({terminal})
        await modal.build()
            .then(() => {
                selectV2();
            })
        modal.bindEvents({
            onclose: (modalHandler) => {
                modalHandler.dispose();
            },
            update: async (btn, modalHandler) => {
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
                let result = await post(platformLinks.api.forms.merchant.editTerminalDetails, formData);

                if(result.status === "error") {
                    setError(result.error)
                    return false;
                }

                queueNotificationOnLoad('Success', result.message, 'success')
                handleStandardApiRedirect(result)
                modalHandler.dispose()
            }
        })
        modal.open()
    },

    qrAction(terminalId) {
        let link = HOST + platformLinks.merchant.terminals.terminalQr.replace("{id}", terminalId);
        const windowName = 'TerminalQrWindow';
        const windowFeatures = 'width=600,height=750,scrollbars=yes';
        const popupWindow = window.open(link, windowName, windowFeatures);
    }
};




const VivaWallet = {
    onboardingWindow: null,
    onboardingInterval: null,
    accountStatus: "DRAFT",
    init(accountStatus) {
        if(!empty(accountStatus)) this.accountStatus = accountStatus;
        this.bindEvents();
    },
    bindEvents() {
        if(this.accountStatus !== 'COMPLETED') {
            this.onboardingInterval = setInterval(this.checkAccountStatus.bind(this), 3500);
        }
    },
    async checkAccountStatus() {
        let result = await get(platformLinks.api.organisation.vivaConnectedAccount);
        if(result.status === "error") {
            console.warn("Der opstod en fejl", result.error.message)
            clearInterval(this.onboardingInterval)
            this.onboardingInterval = null;
            return;
        }

        if(this.onboardingWindow !== null && result?.data?.state !== 'DRAFT') {
            if(!this.onboardingWindow.closed) {
                this.onboardingWindow.close();
                this.onboardingWindow = null;
            }
        }

        if(result?.data?.state === 'COMPLETED') {
            clearInterval(this.onboardingInterval)
            this.onboardingInterval = null;
            if(this.accountStatus !== 'COMPLETED') {
                queueNotificationOnLoad(
                    'Din Viva wallet er opsat',
                    "Du kan nu begynde at oprette lokationer og tage imod betalinger",
                    'success'
                )
                window.location.reload();
                return;
            }
        }
    },
    async setupVivaWallet(btn) {
        if(this.accountStatus !== 'DRAFT' && this.accountStatus !== null) return;
        btn.disabled = true;

        let result = await post(platformLinks.api.organisation.vivaConnectedAccount);
        if(result.status === "error") {
            btn.disabled = false;
            showErrorNotification("Der opstod en fejl", result.error.message)
            return;
        }

        if(!('onboarding' in result.data)) {
            showErrorNotification("Der opstod en fejl", 'Kunne ikke finde onoarding linket');
            btn.disabled = false;
            return;
        }

        showNeutralNotification("Klargøre opsættelse...", result.message)

        this.onboardingWindow = window.open(
            result.data.onboarding,
            'onboardingWindow',
            'width="100%",height="100%"'
        )
        if (!this.onboardingWindow) {
            window.location.href = result.data.onboarding;
        }

        btn.disabled = false;
    }
};
















