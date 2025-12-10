var MODAL_SESSIONS = {
    active: null,
    sessions: {},
    stack: [] // <- keep a stack of modal handlers
};
class ModalHandler {
    modalTemplates = {
        integrationSelection: {
            file: 'integrations.facebook-integration-selection',
            // file: 'integration-selection',
            keys: ["items"]
        },
        mediaMetricsAttachment: {
            file: 'campaign-media-attachment',
            keys: ["media_id"]
        },
        filePrint: {
            file: 'file-print',
            keys: ["title","success", "content"]
        },
        paymentSuccess: {
            file: 'payment-success',
            keys: ["redirect_uri", "dashboard_url"]
        },
        authenticate: {
            file: 'authenticate',
            keys: ['redirect_uri']
        },
        organisationTeamInvite: {
            file: 'organisation-team-invite',
            keys: ['roles']
        },
        organisationCreateRole: {
            file: 'organisation-new-role',
            keys: []
        },
        organisationRenameRole: {
            file: 'organisation-rename-role',
            keys: ['role']
        },
        paymentInfo: {
            file: 'payment-info',
            keys: [
                "addressCountry",
                "addressCountryName",
                "addressCity",
                "addressStreet",
                "addressZip",
                "addressRegion",
                "recipientName",
                "recipientEmail",
                "iban",
                "swift",
                "bank_name",
                "bank_country_name",
                "bank_country"
            ]
        },
        billingInfo: {
            file: 'billing-info',
            keys: [
                "billing_city",
                "billing_country",
                "billing_email",
                "billing_name",
                "billing_region",
                "billing_street",
                "billing_zip",
                "billing_vat",
                "countries",
            ]
        },
        addPaymentMethod: {
            file: 'add-payment-method',
            keys: []
        },
        subscriptionChangePaymentMethod: {
            file: 'change-payment-method',
            keys: [
                'payment_methods',
                'current_payment_method',
                'customerId',
                'currentSubscriptionPaymentMethodObj',
            ]
        },
        reactivateExpiringSubscription1: {
            file: 'reactivate-expiring-subscription',
            keys: [
                'paymentMethod',
                'currentSubscription',
                'customerId',
            ]
        },
        getMoreCampaignDays: {
            file: 'get-more-campaign-days',
            keys: [
                'payment_methods',
                'info',
                'customerId',
                'default_payment_method',
                'upcomingSubscriptionDecrease',
                'canModifySubscription',
            ]
        },
        manageCurrentSubscription: {
            file: 'manage-current-subscription',
            keys: [
                'info',
                'payment_methods',
                'customerId',
                'default_payment_method',
                'upcomingSubscriptionDecrease',
                'canModifySubscription',
            ]
        },
        enableInstagramAccounts: {
            file: 'integrations.enable-instagram-account',
            keys: [
                'integrationUsageRemainingZero',
                'items',
                'integrationsDisabledZero',
                'integrationUsage',
                'integrationCurrentUsage',
            ]
        },
        disableInstagramAccount: {
            file: 'integrations.disable-instagram-account',
            keys: [
                'accountUsername',
                'accountName',
                'accountId',
                'affectedCampaigns',
            ]
        },
        deleteInstagramAccount: {
            file: 'integrations.delete-instagram-account',
            keys: [
                'accountUsername',
                'accountName',
                'accountId',
                'affectedCampaigns',
                'account',
            ]
        },
        manageIntegration: {
            file: 'integrations.manage-integration',
            keys: [
                'integration',
            ]
        },
        removeIntegration: {
            file: 'integrations.remove-facebook-integration',
            keys: [
                'integration',
            ]
        },
        addMoreCreators: {
            file: 'creators.add-more-creators',
            keys: []
        },
        organisationDetails: {
            file: 'organisation.edit-details',
            keys: ['organisation', 'allowedCountries', 'worldCountries', 'defaultCountry']
        },
        locationAddNew: {
            file: 'locations.add-new-location',
            keys: ['allowedCountries', 'worldCountries', 'defaultCountry', 'SITE_NAME']
        },
        terminalAddNew: {
            file: 'terminals.add-new-terminal',
            keys: ['locations', 'selectedLocation']
        },
    };

    constructor(template) {
        if(!Object.keys(this.modalTemplates).includes(template)) {
            this.init = false;
            return;
        }

        if (MODAL_SESSIONS.active !== null) {
            const activeModal = MODAL_SESSIONS.sessions[MODAL_SESSIONS.active];
            if (activeModal) {
                MODAL_SESSIONS.stack.push(activeModal.buildOptions);
                activeModal.close(); // close but don’t dispose
            }
        }


        this.reset()
        this.templateName = template;
        this.templateSelected = this.modalTemplates[template];
    }
    reset () {
        this.templateName = null;
        this.templateSelected = null;
        this.templateFile = null;
        this.templateRaw = null;
        this.template = null;
        this.init = true;
        this.error = null;
        this.data = null;
        this.templateId = null;
        this.modal = null;
        this.isOpen = false;
        this.isBuild = false;
        this.options = {};
        this.buildOptions = {bind: true, template: null, eventOptions: {}, data: null}
        MODAL_SESSIONS.active = null;
    }
    construct(data = {}, bind = true) {
        if(!this.init) return false;
        for(let key of this.templateSelected.keys) {
            if(!Object.keys(data).includes(key)) {
                this.error = `Construction failed for template ${this.templateName}. Missing data key: ${key}`
                return false;
            }
            if(empty(this.data)) this.data = {}
            this.data[key] = data[key]
        }
        this.buildOptions.data = data;
        this.buildOptions.bind = bind;
        this.buildOptions.template = this.templateName;
        if(bind) this.bindEvents()
    }
    async build() {
        if(!this.init) return false;
        if(!empty(this.error)) {
            console.error(`Build error: ${this.error}`)
            return null;
        }

        await get(`api/template/modal/${this.templateSelected.file}`)
            .then(responseText => {
                this.templateRaw = responseText
            })
            .catch(error => {
                let code = error.status, errorText = error.statusText;
                console.error(`Build error while fetching template:: (${code}) ${errorText}`)
            })

        if(empty(this.templateRaw)) return null;
        this.templateId = $(this.templateRaw).attr("id");
        this.template = (Handlebars.compile(this.templateRaw))(this.data);

        $("body").append(this.template)
        this.template = $("body").find(`#${this.templateId}`).first()
        this.modal = this.template.modal()


        MODAL_SESSIONS.active = this.templateName
        MODAL_SESSIONS.sessions[this.templateName] = this;
        this.isBuild = true;
    }
    open() {
        if(!this.isBuild) return false;
        if(this.isOpen) return true;
        this.modal.modal('show')
        this.isOpen = true;
    }
    close() {
        if(!this.isBuild) return false;
        if(!this.isOpen) return true;
        this.modal.modal('hide')
        this.isOpen = false;
    }
    toggle() {
        if(!this.isBuild) return false;
        this.modal.modal('toggle')
        this.isOpen = !this.isOpen;
    }
    dispose() {
        if(!this.isBuild) return false;
        this.close()
        this.modal.modal('dispose')
        this.template.remove()
        this.reset()
    }
    redirectPage() {
        console.log("data red: ",this.data)
        if(!this.isBuild) return false;
        if(!('redirect_uri' in this.data)) return false;
        console.log(this.data)
        window.location = this.data.redirect_uri
    }
    bindEvents(opt = {}) {
        if(!this.isBuild) return false;
        this.buildOptions.eventOptions = opt;
        let buttons = this.template.find("[data-run]")
        // let buttons = this.template.find("button")
        let cl = this;
        this.setOptions(opt)

        if(buttons.length) {
            buttons.each(function () {
                let fn = $(this).attr("data-run");
                if(empty(fn) || (!(fn in window) && !Object.keys(opt).includes(fn))) return;

                let eventType = $(this).attr("data-event");
                if(empty(eventType)) eventType = "click";

                $(this).on(eventType, function () {
                    if(Object.keys(opt).includes(fn)) opt[fn]($(this), cl)
                    else window[fn]($(this), cl)
                })
            })
        }

        this.modal.on('hidden.bs.modal', function () {
            if ('onclose' in cl.options) cl.options.onclose(cl)
            cl.isOpen = false;
        })

        MODAL_SESSIONS.sessions[this.templateName] = this;
    }

    setOptions(options = {}, name = null) {
        if(empty(name)) this.options = options;
        else this.options[name] = options;
    }


    queueModalAfterReload(template = null, options = {}) {
        if(template === null) {
            const previousModal = MODAL_SESSIONS.stack.pop();
            console.log(previousModal)
        }
        else {
            localStorage.setItem("queued_modal", JSON.stringify({ template, options }));
        }
    }
}



