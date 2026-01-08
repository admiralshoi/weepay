

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



const LocationActions = {
    isInit: false,
    isOpen: false,
    locationId: null,
    location: null,
    buttons: {
        manageTeam: null,
        pageBuilder: null,
    },
    elements: {
      name: null
    },

    init() {
        if(this.isInit) return;
        this.isInit = true;
        this.sidebar = document.getElementById('locationAction');
        this.closeRightSidebarBtn = this.sidebar.querySelector('.closeRightSidebarBtn');
        this.elements.name = this.sidebar.querySelector(`[data-preview=location-name]`);
        this.buttons.pageBuilder = document.getElementById('page-builder-link');
        this.buttons.manageTeam = document.getElementById('manage-team');



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
    setValues () {
        let slug = this.location.slug;
        this.buttons.pageBuilder.href = platformLinks.merchant.locations.locationPageBuilder.replace("{slug}", slug);
        this.buttons.manageTeam.href = platformLinks.merchant.locations.locationMembers.replace("{slug}", slug);
        this.elements.name.innerText = this.location.name;
    },
    setLocation () {
        this.location = locations.find(l => l.uid === this.locationId)
        console.log(this.location)
    },
    open(btn) {
        if(this.isOpen) return;
        let locationId = btn.dataset.locationId;
        if(empty(locationId)) return;
        this.locationId = locationId;
        this.setLocation();
        this.init();
        this.setValues();
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


    async editLocationDetails(locationId) {
        if(!empty(locationId)) this.locationId = locationId;
        if(empty(this.locationId)) return;
        if(!this.isInit) this.init();
        this.setLocation();


        let modal = new ModalHandler('locationDetails')
        modal.construct({location: this.location, organisation, allowedCountries, worldCountries, defaultCountry, SITE_NAME, currencies})
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
    },

    locationQrAction(slug) {
        let link = HOST + platformLinks.merchant.locations.locationQr.replace("{slug}", slug);
        const windowName = 'LocationQrWindow';
        const windowFeatures = 'width=600,height=750,scrollbars=yes';
        window.open(link, windowName, windowFeatures);
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
















