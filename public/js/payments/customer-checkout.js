




const CustomerCheckoutInfo = {
    basket: null,
    interval: null,
    tsId: null,
    elements: {
        showCurrencies: null,
        showNames: null,
        showPrices: null,
        nextStepButton: null,
        cancelCheckoutButton: null,
        loader: null,
        lineItems: null,
        total: null,
        storeBasketInfo: null,
    },
    init(tsId) {
        this.tsId = tsId;

        this.elements.lineItems = document.getElementById('line_items');
        this.elements.total = document.getElementById('total_price_container');
        this.elements.nextStepButton = document.getElementById('next-step');
        this.elements.cancelCheckoutButton = document.getElementById('cancel-checkout');
        this.elements.loader = document.getElementById('loader-container');
        this.elements.storeBasketInfo = document.getElementById('store-basket-info');
        this.elements.showNames = document.querySelectorAll('[data-show=basket_name]');
        this.elements.showCurrencies = document.querySelectorAll('[data-show=basket_currency]');
        this.elements.showPrices = document.querySelectorAll('[data-show=basket_price]');

        this.bindEvents();
    },

    bindEvents() {
        this.interval = window.setInterval(this.fetchBasket.bind(this), 1200);

        if(!empty(this.tsId) && this.elements.cancelCheckoutButton) {
            this.elements.cancelCheckoutButton.addEventListener('click', this.voidCheckout.bind(this));
        }
    },

    setBasketInfo() {
        this.elements.paymentCards.forEach(c => {
            if (c.querySelector('.payment-card__radio').checked) {
                c.classList.add('payment-card--selected');
            } else {
                c.classList.remove('payment-card--selected');
            }
        });
    },

    async voidCheckout() {
        this.elements.cancelCheckoutButton.disabled = true;
        this.elements.nextStepButton.disabled = true;
        let link = platformLinks.api.checkout.terminalSession.replace("{id}", this.tsId);
        const result = await del(link,  {restart: 1})
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            this.elements.cancelCheckoutButton.disabled = false;
            this.elements.nextStepButton.disabled = false;
            return
        }
        handleStandardApiRedirect(result)
        this.elements.cancelCheckoutButton.disabled = false;
        this.elements.nextStepButton.disabled = false;
    },
    async fetchBasket() {
        let link = platformLinks.api.checkout.consumerBasket.replace("{id}", this.tsId);
        const result = await get(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            window.clearInterval(this.interval)
            this.interval = null;
            handleStandardApiRedirect(result.error)
            return
        }
        if(empty(result.data.basket)) this.hideBasketInfo()
        else this.showBasketInfo(result.data.basket);
    },
    showBasketInfo(basket) {
        if(this.basket?.uid === basket.uid) return;
        this.basket = basket;
        let price = basket.price;
        let currency = basket.currency;
        let currencySymbol = basket.currency_symbol;
        let name = basket.name;
        let id = basket.uid;
        this.elements.showNames.forEach(el => el.innerText = name)
        this.elements.showCurrencies.forEach(el => el.innerText = currencySymbol)
        this.elements.showPrices.forEach(el => el.innerText = phpNumberFormat(price))
        if(this.elements.loader) this.elements.loader.style.display = 'none';
        if(this.elements.nextStepButton) this.elements.nextStepButton.style.display = 'flex';
        if(this.elements.lineItems) this.elements.lineItems.style.display = 'flex';
        if(this.elements.total) this.elements.total.style.display = 'flex';
        if(this.elements.storeBasketInfo) this.elements.storeBasketInfo.style.display = 'flex';
    },
    hideBasketInfo() {
        this.basket = null;
        this.elements.showNames.forEach(el => el.innerText = '')
        this.elements.showCurrencies.forEach(el => el.innerText = '')
        this.elements.showPrices.forEach(el => el.innerText = '')
        if(this.elements.loader) this.elements.loader.style.display = 'flex';
        if(this.elements.nextStepButton) this.elements.nextStepButton.style.display = 'none';
        if(this.elements.lineItems) this.elements.lineItems.style.display = 'none';
        if(this.elements.total) this.elements.total.style.display = 'none';
        if(this.elements.storeBasketInfo) this.elements.storeBasketInfo.style.display = 'none';
    },
}







const CustomerCheckout = {
    listenStatus: false,
    selectedPlan: null,
    plans: null,
    interval: null,
    tsId: null,
    basketHash: null,
    sessionPopup: null,
    elements: {
        payButton: null,
        toPayNow: null,
        paymentCards: null,
        paymentButtonLoader: null,
        acceptTerms: null,
    },
    init(selectedPlanName, tsId) {
        this.plans = paymentPlans;
        this.tsId = tsId;
        if('basketHash' in window) this.basketHash = basketHash;

        this.elements.acceptTerms = document.querySelector('[name=accept_terms]');
        this.elements.payButton = document.getElementById('payButton');
        this.elements.toPayNow = document.getElementById('to-pay-now');
        this.elements.paymentButtonLoader = document.getElementById('paymentButtonLoader');
        this.elements.paymentCards = document.querySelectorAll('.payment-card');

        this.updateSelectedPlan(selectedPlanName);
        this.bindEvents();
    },

    bindEvents() {
        this.elements.payButton.addEventListener('click', this.generateCheckoutLink.bind(this));
        this.elements.paymentCards.forEach(card => {
            const radio = card.querySelector('.payment-card__radio');
            card.addEventListener('click', (e) => {
                if (e.target === radio) return;
                radio.checked = true;
                this.updateSelectedPlan(radio.value)
                this.updateSelection();
            });
            radio.addEventListener('change', this.updateSelection);
        });


        if(this.basketHash) {
            this.interval = window.setInterval(this.fetchBasketHash.bind(this), 1200);
        }
    },
    async fetchBasketHash() {
        let link = platformLinks.api.checkout.basketHash.replace("{id}", this.tsId);
        const result = await get(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            window.clearInterval(this.interval)
            this.interval = null;
            handleStandardApiRedirect(result.error)
        }

        let hash = result.data.hash;
        if(hash !== this.basketHash) {
            if(!empty(result.data.goto)) {
                // Close popup if it's open before redirecting
                if(this.sessionPopup && !this.sessionPopup.closed) {
                    this.sessionPopup.close();
                }
                showNeutralNotification("Kurven er blevet opdateret", "Omredigerer...")
                setTimeout(()=> window.location =  result.data.goto, 1000);
            }
        }
    },
    updateSelectedPlan(planName) {
        for(let plan of this.plans) {
            if(plan.name === planName) {
                this.selectedPlan = plan;
                this.elements.toPayNow.innerText = phpNumberFormat(this.selectedPlan.to_pay_now)
                break;
            }
        }
    },
    updateSelection() {
        this.elements.paymentCards.forEach(c => {
            if (c.querySelector('.payment-card__radio').checked) {
                c.classList.add('payment-card--selected');
            } else {
                c.classList.remove('payment-card--selected');
            }
        });
    },

    async listenOrderStatus(orderCode) {
        if(!this.listenStatus) return;
        const result = await post(`api/checkout/order/status`, {ts_id: this.tsId, order_code: orderCode})
        if(result.status === 'error') {
            // showErrorNotification("Unable to fetch order", result.error.message)
            this.elements.payButton.disabled = false;
            this.elements.paymentButtonLoader.style.display = 'none';
            this.listenStatus = false;
            return false
        }

        if(result.data.status === 'COMPLETED') {
            queueNotificationOnLoad("Ordre fuldført", result.message, 'success')
            this.elements.paymentButtonLoader.style.display = 'none';
            this.listenStatus = false;

            if (this.sessionPopup && !this.sessionPopup.closed) {
                this.sessionPopup.close();
                this.sessionPopup = null;
            }

            handleStandardApiRedirect(result)
            return true
        }

        if(result.data.status === 'CANCELED') {
            showSuccessNotification("Order Cancelled")
            this.elements.payButton.disabled = false;
            this.elements.paymentButtonLoader.style.display = 'none';
            this.listenStatus = false;

            if (this.sessionPopup && !this.sessionPopup.closed) {
                this.sessionPopup.close();
                this.sessionPopup = null;
            }
            return true
        }

        if(result.data.status === 'EXPIRED') {
            showSuccessNotification("Checkout expired")
            this.elements.payButton.disabled = false;
            this.elements.paymentButtonLoader.style.display = 'none';
            this.listenStatus = false;

            if (this.sessionPopup && !this.sessionPopup.closed) {
                this.sessionPopup.close();
                this.sessionPopup = null;
            }
            return true
        }

        this.listenOrderStatus.bind(this)(orderCode)
    },

    async generateCheckoutLink() {
        if(!this.elements.acceptTerms.checked) {
            showErrorNotification("Please agree to the terms", "You must check the box and agree to the terms stated before proceeding.")
            return;
        }
        this.elements.payButton.disabled = true;
        this.elements.paymentButtonLoader.style.display = 'flex';

        const result = await post(`api/checkout/payment/session`, {ts_id: this.tsId, plan: this.selectedPlan.name})
        if(result.status === 'error') {
            showErrorNotification("Unable to proceed", result.error.message)
            this.elements.payButton.disabled = false;
            this.elements.paymentButtonLoader.style.display = 'none';
            return
        }

        let paymentSessionUrl = result.data.paymentSessionUrl;
        let orderCode = result.data.orderCode

        // Use redirect on mobile instead of popup
        if (isMobileDevice()) {
            // Stop basket hash checking before redirect
            if(this.interval) {
                window.clearInterval(this.interval);
                this.interval = null;
            }

            // Full redirect on mobile - callback will handle return
            window.location.href = paymentSessionUrl;
            return;
        }

        // Desktop: use popup
        this.sessionPopup = window.open(
            paymentSessionUrl,
            'paymentSessionPopup',
            'width="100%",height="100%"'
        )
        if (!this.sessionPopup) {
            window.location.href = paymentSessionUrl;
        }
        else {
            // Stop basket hash checking while popup is open
            if(this.interval) {
                window.clearInterval(this.interval);
                this.interval = null;
            }

            let popupIntervalCheck = setInterval(() => {
                if (this.sessionPopup.closed) {
                    console.warn("Payment window closed.");
                    this.elements.payButton.disabled = false;
                    this.elements.paymentButtonLoader.style.display = 'none';
                    this.listenStatus = false;
                    window.clearInterval(popupIntervalCheck);

                    // Restart basket hash checking when popup closes
                    if(this.basketHash) {
                        this.interval = window.setInterval(this.fetchBasketHash.bind(this), 1200);
                    }

                    // Cleanup abandoned order if payment wasn't completed
                    this.evaluateAbandonedOrder(orderCode);
                }
            }, 500);
        }



        this.listenStatus = true;
        this.listenOrderStatus.bind(this)(orderCode);
    },

    async evaluateAbandonedOrder(orderCode) {
        // Silently send request to cleanup abandoned order if not processed
        try {
            await post(`api/checkout/order/evaluate`, {
                ts_id: this.tsId,
                order_code: orderCode
            });
        } catch (error) {
            // Silent cleanup - don't show errors to user
            console.log('Order evaluation completed');
        }
    }
}