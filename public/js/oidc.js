






const OidcAuth = {
    id: null,
    status: null,
    link: null,
    expiresAt: null,
    isInit: false,
    isRunning: false,
    popup: null,
    pollingInterval: false,
    elements: {
        button: null,
    },
    selectors: {
        button: ".oidc-auth",
    },
    init() {
        this.elements.button = document.querySelector(this.selectors.button);
        if(!this.elements.button) return;
        let id = this.elements.button.dataset.id;
        if(empty(id)) return;
        this.id = id;
        this.link = HOST + platformLinks.app.auth.oicd.preAuthUrl.replace("{id}", this.id);

        this.isInit = true;
        this.bindEvents();
    },

    bindEvents() {
        this.elements.button.addEventListener("click", this.run.bind(this))
    },

    run() {
        if(this.isRunning) return;
        if(empty(this.link)) return;
        this.elements.button.disabled = true;
        this.isRunning = true;
        screenLoader.show("Afventer verificering...")

        // Use redirect on mobile instead of popup
        if (isMobileDevice()) {
            window.location.href = this.link;
            return; // Don't start polling - callback will handle redirect
        }

        const windowName = 'OIDCAuthWindow';
        const windowFeatures = 'width=600,height=750,scrollbars=yes';

        const popupWindow = window.open(this.link, windowName, windowFeatures);

        if (!popupWindow) {
            console.warn("Pop-up window was blocked. Falling back to full page redirect.");
            window.location.href = this.link;
            this.isRunning = false;
            this.elements.button.disabled = false;
            screenLoader.hide();
            return;
        }

        // If the window is still open after a very short delay (e.g., 250ms), we assume the flow has successfully started.
        setTimeout(() => {
            if (popupWindow.closed) {
                // The pop-up opened and immediately closed itself (another sign of a block/failure).
                console.warn("Pop-up window closed immediately. Falling back to full page redirect.");
                this.isRunning = false;
                this.elements.button.disabled = false;
                screenLoader.hide();
                return;
            }

            popupWindow.focus();
            this.pollingInterval = true
            this.startPolling.bind(this)(popupWindow);
        }, 250);
    },

    async startPolling(popupWindow) {
        if(!this.pollingInterval || !popupWindow || popupWindow.closed) {
            console.log(popupWindow)
            this.pollingInterval = false;
            this.isRunning = false;
            this.elements.button.disabled = false;
            screenLoader.hide();

            // If popup was closed by user, reload page to get fresh OIDC session
            // This prevents "session expired" errors when user tries to open popup again
            if(popupWindow.closed) {
                console.log("OIDC popup closed by user - reloading page to refresh session");
                setTimeout(() => {
                    window.location.reload();
                }, 500); // Small delay so user sees the popup close
            }
            return;
        }
        let endpoint = platformLinks.api.oidc.sessionPolling.replace("{id}", this.id);
        const result = await get(endpoint)
        if(result.status === 'error') {
            showErrorNotification("An error occurred", result.error.message)
            this.pollingInterval = false;
            screenLoader.hide();
            return
        }

        let status = result.data.status;
        if(!['PENDING', 'DRAFT'].includes(status)) {
            if(status === 'VOID') {
                queueNotificationOnLoad("Fejl.", "Linket udløb. Prøv igen", 'error')
                window.location.reload()
            }
            else if(status === 'TIMEOUT') {
                queueNotificationOnLoad("Udløbet.", "Linket udløb. Prøv igen", 'error')
                if(!popupWindow.closed) {
                    popupWindow.close();
                }
                window.location.reload()
            }
            else if(status === 'CANCELLED') {
                queueNotificationOnLoad("Verificering stoppet", "Linket blev efterladt. Prøv igen.", 'error')
                window.location.reload()
            }
            else if(status === 'ERROR') {
                queueNotificationOnLoad("Verificeringsfejl.", "Der opstod en fejl under under verificeringen. Prøv igen", 'error')
                window.location.reload()
            }
            else if(status === 'SUCCESS') {
                queueNotificationOnLoad("Verificeret!", "Verificeringen blev gennemført og godkendt", "success")
                console.log(popupWindow)
                if(!popupWindow.closed) {
                    popupWindow.close();
                }
                handleStandardApiRedirect(result)
            }
            this.pollingInterval = false;
            this.isRunning = false;
            return;
        }

        setTimeout(async () => await this.startPolling(popupWindow), 500)
    }
}

$(document).ready(function () {
    OidcAuth.init();
})




