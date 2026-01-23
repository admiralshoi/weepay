




const MerchantPOS = {
    basket: null,
    interval: null,
    tId: null,
    tsId: null,
    basketId: null,
    locationId: null,
    basketInterval: null,
    checkSessionInterval: null,
    sentToCustomer: false,
    allSalesLoaded: false,
    elements: {
        awaitingContainer: null,
        sessionContainer: null,
        sessionTableBody: null,
        cancelBasketBtn: null,
        createBasketBtn: null,
        voidBasketBtn: null,
        voidSessionBtn: null,
        backToStartBtn: null,
        sessionStatus: null,
        pageTitleVoid: null,
        pageTitlePending: null,
        salesListContainer: null,
        mySalesCount: null,
        allSalesCount: null,
    },
    init() {
        if('terminalId' in window) this.tId = terminalId;
        if('terminalSessionId' in window) this.tsId = terminalSessionId;
        if('terminalSessionBasket' in window) this.basketId = terminalSessionBasket;
        if('locationId' in window) this.locationId = locationId;
        this.elements.awaitingContainer = document.getElementById('awaiting_customers');
        this.elements.sessionContainer = document.getElementById('session_container');
        this.elements.sessionTableBody = document.getElementById('session_body');
        this.elements.cancelBasketBtn = document.getElementById('cancelBasket');
        this.elements.createBasketBtn = document.getElementById('createBasket');
        this.elements.voidBasketBtn = document.getElementById('edit-basket');
        this.elements.voidSessionBtn = document.getElementById('void-session');
        this.elements.backToStartBtn = document.getElementById('back-to-start');
        this.elements.sessionStatus = document.getElementById('session-status');
        this.elements.pageTitleVoid = document.getElementById('page-title-void');
        this.elements.pageTitlePending = document.getElementById('page-title-pending');
        this.elements.salesListContainer = document.getElementById('sales-list-container');
        this.elements.mySalesCount = document.getElementById('my-sales-count');
        this.elements.allSalesCount = document.getElementById('all-sales-count');


        this.bindEvents();
    },

    bindEvents() {
        if(!empty(this.tId)) {
            this.interval = window.setInterval(this.fetchSessions.bind(this), 1200);
            this.fetchSessions.bind(this)();
        }
        if(!empty(this.tsId) && empty(this.basketId)) {
            this.basketInterval = window.setInterval(this.findBasket.bind(this), 1200);
            this.findBasket.bind(this)();
        }
        if(!empty(this.tsId) && !empty(this.basketId)) {
            this.checkSessionInterval = window.setInterval(this.checkSession.bind(this), 1200);
            this.checkSession.bind(this)();
        }
        if(!empty(this.tsId) && this.elements.voidBasketBtn) {
            this.elements.voidBasketBtn.addEventListener('click', this.voidBasket.bind(this));
        }
        if(!empty(this.tsId) && this.elements.voidSessionBtn) {
            this.elements.voidSessionBtn.addEventListener('click', this.voidSession.bind(this));
        }
        // Sales toggle buttons (only on start page)
        if(this.elements.salesListContainer) {
            document.querySelectorAll('.sales-toggle-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.sales-toggle-btn').forEach(b => b.classList.remove('active'));
                    e.currentTarget.classList.add('active');
                    const showAll = e.currentTarget.dataset.showAll === '1';
                    this.loadSales(showAll);
                });
            });
            // Load initial sales
            this.loadSales(false);
        }
    },


    async fetchSessions() {
        let link = platformLinks.api.checkout.merchantPosGetSessions.replace("{id}", this.tId);
        const result = await get(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            window.clearInterval(this.interval)
            this.interval = null;
            return
        }

        if(empty(result.data.sessions)) {
            this.elements.sessionContainer.style.display = 'none';
            this.elements.awaitingContainer.style.display = 'flex';
            return;
        }

        this.elements.sessionContainer.style.display = 'flex';
        this.elements.awaitingContainer.style.display = 'none';

        let sessionIds = {};
        for(let i in result.data.sessions) {
            let session = result.data.sessions[i];
            sessionIds[session.uid] = session.hash
            let html = `<tr data-id='${session.uid}' data-hash='${session.hash}'>`;
                html += `<td>`;
                    html += `<p class="design-box font-14 ">${session.session}</p>`;
                html += `</td>`;
                html += `<td>${session.customer.name}</td>`;
                html += `<td>${session.dateFormat}</td>`;
                html += `<td>`;
                if(session.state === 'ACTIVE') html += `<p class="color-green  font-weight-bold">${ucFirst(session.state.toLowerCase())}</p>`;
                else html += `<p class="color-design-blue">${ucFirst(session.state.toLowerCase())}</p>`;
                html += `</td>`;
                html += `<td>`;
                    html += `<div class="flex-row-center-center flex-nowrap cg-05">`;
                        html += `<a href="${session.link}" class="btn-v2 trans-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">`;
                            html += `<i class="mdi mdi-play-outline font-18"></i>`;
                            html += `<span class="font-14">Start</span>`;
                        html += `</a>`;
                        html += `<button onclick="MerchantPOS.removeSession('${session.uid}')" class="btn-v2 danger-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;">`;
                            html += `<i class="mdi mdi-trash-can-outline font-18"></i>`;
                        html += `</button>`;
                    html += `</div>`;
                html += `</td>`;
            html += `</tr>`;

            let existingTr = this.elements.sessionTableBody.querySelector(`tr[data-id='${session.uid}']`);
            if(existingTr) {
                if(existingTr.dataset.hash !== session.hash) {
                    console.log('tr hash', existingTr.dataset.hash, 'new hash', session.hash);
                    $(existingTr).replaceWith(html)
                }
            }
            else {
                $(this.elements.sessionTableBody).append(html)
            }
        }

        this.elements.sessionTableBody.querySelectorAll("tr").forEach(el => {
            if(!Object.keys(sessionIds).includes(el.dataset.id)) {
                $(el).slideUp(500, function () {
                    $(this).remove();
                });
            }
        })

    },
    async removeSession(sessionId) {
        let link = platformLinks.api.checkout.terminalSession.replace("{id}", sessionId);
        const result = await del(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            return
        }

        let existingTr = this.elements.sessionTableBody.querySelector(`tr[data-id='${sessionId}']`);
        if(existingTr) {
            $(existingTr).slideUp(500, function () {
                $(this).remove();
            });
        }
    },
    async createBasket(btn) {
        btn.disabled = true;
        let form = $(btn).parents('form').first();
        let formData = new FormData(form.get(0))

        let link = platformLinks.api.checkout.merchantPosBasket.replace("{id}", this.tsId);
        const result = await post(link, formData)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            btn.disabled = false;
            return
        }

        showSuccessNotification("Kurven blev oprettet", "Afventer nu kunde handling")
        // this.elements.cancelBasketBtn.style.display =  "flex";
        // this.basketId = result.data.id
        // this.sentToCustomer = true;

    },
    async cancelBasket(btn) {
        btn.disabled = true;
        let link = platformLinks.api.checkout.merchantPosBasket.replace("{id}", this.tsId);
        const result = await del(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            return
        }

        btn.style.display =  "none";
        this.basketId = null;
        btn.disabled = false;
        this.sentToCustomer = false;
        this.elements.createBasketBtn.disabled = false;
        if(!empty(this.basketInterval)) {
            window.clearInterval(this.basketInterval)
            this.basketInterval = null;
        }
    },
    async voidSession() {
        this.elements.voidBasketBtn.disabled = true;
        this.elements.voidSessionBtn.disabled = true;
        let link = platformLinks.api.checkout.terminalSession.replace("{id}", this.tsId);
        const result = await del(link,  {restart: 1})
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            this.elements.voidBasketBtn.disabled = false;
            this.elements.voidSessionBtn.disabled = false;
            return
        }
        handleStandardApiRedirect(result)
        this.elements.voidBasketBtn.disabled = false;
        this.elements.voidSessionBtn.disabled = false;
    },
    async voidBasket() {
        this.elements.voidBasketBtn.disabled = true;
        let link = platformLinks.api.checkout.merchantVoidBasket.replace("{id}", this.tsId);
        const result = await post(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            this.elements.voidBasketBtn.disabled = false;
            return
        }
        handleStandardApiRedirect(result)
        this.elements.voidBasketBtn.disabled = false;
    },
    async findBasket() {
        let result = await this.fetchBasket();
        // return result.data.basket;
        if(!('basket' in result.data)) return;
        let basket = result.data.basket;
        if(empty(basket) || basket.status !== 'DRAFT') return;
        handleStandardApiRedirect(result)
    },
    async basketStatus() {
        let basket = await this.fetchBasket();
        // if(empty(basket) || basket.uid !== this.basketId || basket.status === 'VOID') {
        //     window.clearInterval(this.basketInterval)
        //     this.basketInterval = null;
        //     this.sentToCustomer = false;
        //     this.elements.createBasketBtn.disabled = false;
        //     this.elements.cancelBasketBtn.style.display =  "none";
        // }
        //
        // if(basket.status === 'DRAFT') return;
        //
        // //fulfilled!!!
        //
        //
        // console.log('fulfilled!', basket)
        // // window.location = platformLinks.merchant.terminals.terminalPosFulfilled
        // //     .replace("{slug}", basket.slug)
        // //     .replace("{tsid}", basket.terminal_session)
        // //     .replace("{id}", basket.terminal)
        //
        //
        // this.basketInterval = null;
        // this.elements.cancelBasketBtn.style.display =  "none";
    },
    async fetchBasket() {
        let link = platformLinks.api.checkout.merchantPosBasket.replace("{id}", this.tsId);
        const result = await get(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            window.clearInterval(this.basketInterval)
            this.basketInterval = null;
            return
        }

        return result;
    },
    async checkSession() {
        let link = platformLinks.api.checkout.terminalSession.replace("{id}", this.tsId);
        const result = await get(link)
        if(result.status === 'error') {
            showErrorNotification("Der opstod en fejl", result.error.message)
            window.clearInterval(this.checkSessionInterval)
            this.checkSessionInterval = null;
            handleStandardApiRedirect(result)
            return
        }

        this.elements.sessionStatus.innerText = result.data.statusTitle;
        if(result.data.state === 'VOID') {
            this.elements.voidSessionBtn.style.display = 'none';
            this.elements.voidBasketBtn.style.display = 'none';
            this.elements.backToStartBtn.style.display = 'flex';
            this.elements.pageTitlePending.style.display = 'none';
            this.elements.pageTitleVoid.style.display = 'block';
        }
        if(result.data.state === 'COMPLETED') {
            queueNotificationOnLoad("Købet er fuldført", result.message, 'success')
            window.clearInterval(this.checkSessionInterval)
            this.checkSessionInterval = null;
            handleStandardApiRedirect(result)
        }


        return result;
    },
    async loadSales(showAll) {
        if(!this.locationId || !this.elements.salesListContainer) return;

        let link = platformLinks.api.checkout.todaysSales.replace("{location}", this.locationId);
        if(showAll) link += '?all=1';

        this.elements.salesListContainer.innerHTML = '<div class="flex-col-center flex-align-center py-4"><span class="spinner-border color-blue square-30" style="border-width: 3px;"></span></div>';

        const result = await get(link);
        if(result.status === 'error') {
            this.elements.salesListContainer.innerHTML = '<div class="flex-col-center flex-align-center py-4"><p class="color-red">Kunne ikke hente salg</p></div>';
            return;
        }

        const sales = result.data.sales || [];
        const count = result.data.count || 0;

        if(showAll) {
            this.allSalesLoaded = true;
            if(this.elements.allSalesCount) this.elements.allSalesCount.innerText = count;
        } else {
            if(this.elements.mySalesCount) this.elements.mySalesCount.innerText = count;
        }

        if(sales.length === 0) {
            this.elements.salesListContainer.innerHTML =
                '<div class="flex-col-center flex-align-center py-4" style="row-gap: .5rem;">' +
                '<i class="mdi mdi-cart-outline font-30 color-gray"></i>' +
                '<p class="mb-0 font-14 color-gray text-center">Ingen gennemførte salg endnu i dag</p>' +
                '</div>';
            return;
        }

        let html = '<div class="flex-col-start" style="row-gap: .5rem; max-height: 350px; overflow-y: auto;">';
        for(let i in sales) {
            let sale = sales[i];
            html += '<a href="' + sale.link + '" target="_blank" class="card-border p-3 border-radius-8px text-decoration-none d-block">';
            html += '<div class="flex-row-between flex-align-start">';
            html += '<div class="flex-col-start" style="row-gap: .25rem;">';
            html += '<p class="mb-0 font-14 font-weight-bold color-dark">' + sale.basket_name + '</p>';
            html += '<p class="mb-0 font-12 color-gray">' + sale.customer_name + '</p>';
            if(showAll) html += '<p class="mb-0 font-11 color-gray"><i class="mdi mdi-account-outline"></i> ' + sale.cashier_name + '</p>';
            html += '</div>';
            html += '<div class="flex-col-end" style="row-gap: .25rem;">';
            html += '<p class="mb-0 font-14 font-weight-bold color-green">' + sale.price + ' ' + sale.currency + '</p>';
            html += '<p class="mb-0 font-11 color-gray">' + sale.time + '</p>';
            html += '</div>';
            html += '</div>';
            html += '</a>';
        }
        html += '</div>';
        this.elements.salesListContainer.innerHTML = html;
    },
}



