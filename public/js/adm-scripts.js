



async function updateAffiliateSettings(btn) {
    let btnId = btn.attr("id"), elementIds;
    if(btnId === "save_affiliate_cookie_settings") elementIds = {
        affiliate_cookie_total_max: "actm",
        affiliate_cookie_individual_max: "acim",
        affiliate_cookie_error_streak: "aces",
    };
    else if(btnId === "save_affiliate_general_settings") elementIds = {
        allow_affiliate_signup: "aas",
        affiliate_payout_pool: "app",
        // affiliate_payout_week_interval: "apwi",
    };
    else return;



    for(let name in elementIds) {
        let id = elementIds[name];
        let element = $(document).find(`[name=${id}]`).first();
        if(!element) return false;
        let val;
        if(element.attr("type") === "checkbox") val = element.is(":checked");
        else if(empty(element.val())) return false;
        else val = element.val();
        elementIds[name] = val;
    }


    await post(`api/admin/settings/app/affiliate/update`, {data: elementIds})
        .then((res) => {
            res = ensureObject(res);
            if(res.status === "error") {
                notifyTopCorner(res.error.message, 10000, "bg-red")
                return;
            }

            notifyTopCorner("Settings updated!", 5000)
        })
}







async function markUnpaidAsPaid(elements) {
    if(applicationResponding) return;
    applicationResponding = true;
    let rowIds = [];
    elements.each(function () { rowIds.push($(this).attr("data-row-id")) })

    console.log(rowIds)

    let cbError = () => {
        applicationResponding = false;
        screenLoader.hide();
    }
    let cb = async (data) => {
        screenLoader.show("Updating rows...")
        console.log(data)
        let body = {ids: rowIds}
        console.log(body)
        cbError();
        let result = await post(`api/affiliate/payments/paid`, body);
        if(result.status === "error") result.error = result.error.message
        cbError();
        return result;
    }


    let swalData = {
        callback: cb,
        callbackError: cbError,
        refreshTimeout: 1,
        visualText: {
            preFireText: {
                title: `Mark ${rowIds.length > 1 ? 'these ' + rowIds.length + ' items' : 'this item'} as paid?`,
                text: "This is a permanent action. After marking them as paid, the user will be able to see it on their dashboard as well.",
                confirmButtonText: "Mark as paid",
                icon: "warning"
            },
            successText: {
                title: "Success",
                text: 'Marked ' + (rowIds.length > 1 ? 'all ' + rowIds.length + ' items' : '1 item') + " as paid.",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "<_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };

    swalConfirmCancel(swalData);
}





async function toggleUserSuspension(btn) {
    let id = btn.attr("data-uid");
    if(typeof id === "undefined") return;

    await post(`api/user/${id}/toggle`)
        .then((res) => {
            res = ensureObject(res);
            if(res.status === "error") {
                ePopupTimeout("Error", res.error.message)
                return;
            }
            window.location = window.location.href;
        })
}



async function createUserThirdParty(btn) {
    let parent = btn.parents('#user-creation-third-party').first();
    let fields = {
        username: "input[name=username]",
        full_name: "input[name=full_name]",
        email: "input[name=email]",
        access_level: "select[name=access_level]",
    };


    for(let fieldName in fields) {
        let el = parent.find(fields[fieldName]).first();

        if(empty(el.val())) {
            ePopup(prepareProperNameString(fieldName) + " error", prepareProperNameString(fieldName) + " must not be empty");
            return false;
        }

        fields[fieldName] = el.val().trim();
    }


    let result = await post("api/create-user-on-behalf", {data: fields});

    if(typeof result !== "object" || (!("error" in result) && !("message" in result))) {
        ePopup("Server error","Something went wrong");
        return false;
    }
    if("error" in result) {
        ePopup("Account creation error", result.error);
        return false;
    }

    ePopup("Success", result.message, 0, "success", "approve")
    window.setTimeout(function (){window.location = window.location.href;}, 1500)
}





async function updateAppGeneralSettings(btn) {
    screenLoader.show("Updating...");
    let form = btn.parents('form').first();
    let errorField = form.find(".error-field").first();
    const setError = (msg) => {
        errorField.text(msg);
        errorField.show();
        btn.removeClass("pointer-event-none");
        screenLoader.hide();
    }
    const unsetError = () => {
        btn.addClass("pointer-event-none")
        errorField.text("");
        errorField.hide();
    }
    unsetError();

    let data = {}
    form.find("input[name], select[name]").each(function () {
        let name = $(this).attr("name");
        data[name] = $(this).attr("type") === "checkbox" ? $(this).is(":checked") : $(this).val();
    })

    let result = await post("api/admin/settings/app/general/update", {data});

    if(typeof result !== "object" || (!("error" in result) && !("message" in result))) {
        setError("Something went wrong. Try again later.");
        return false;
    }
    if("error" in result) {
        setError(result.error.message);
        return false;
    }

    screenLoader.hide();
    btn.removeClass("pointer-event-none");
    notifyTopCorner(result.message, 5000)
}





var isMigrating = false;
async function migrateToProduction(){
    if(isMigrating) return;
    isMigrating = true;
    let cb = async () => {
        screenLoader.show("Migrating. Performing backups...")
        let result = await get("migration/init");
        console.log(result)
        if(result.status !== "success") {
            screenLoader.hide();
            return result;
        }
        let next = result.next;

        screenLoader.update("Migrating. Moving files...")
        result = await get(next);
        console.log(result)
        if(result.status !== "success") {
            screenLoader.hide()
            return result;
        }
        next = result.next;

        screenLoader.update("Migrating. Migrating database...")
        result = await get(next);
        result.resultCallback = () => { window.location = result.redirect };
        screenLoader.hide()
        return result;
    }

    swalConfirmCancel({
        callback: cb,
        visualText: {
            preFireText: {
                title: "Migrate to production?",
                text: "Migrating to production moves the testing environment into production. This includes both database and files. " +
                    "A backup of the current production will be taken",
                icon: "warning",
                confirmBtnText: "Resolve"
            },
            successText: {
                title: "Migration complete.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to migrate. Error message: <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    });
}





async function testWebhook() {
    let inputField = $(document).find("input[name=webhook_filename]").first(),
    responseField = $(document).find("#webhook_response").first();
    if(empty(inputField, responseField)) return;
    responseField.hide();
    responseField.text("");

    let filepath = inputField.val().trim();
    if(empty(filepath)) return;


    await post(`webhook/test`, {file: filepath})
        .then(res => {
            responseField.text(JSON.stringify(res, null, 4))
            responseField.show();
        })
        .catch(res => {
            if(typeof res === "object") res = JSON.stringify(res, null, 4);
            responseField.text(res)
            responseField.show();
        })

}












/**
 * =========================
 * ===== SCRAPER LOGS ======
 * =========================
 */

async function scraperLogs(btn) {
    let fn = btn.attr("data-filename")
    let success = btn.data("success")
    let datetime = btn.attr("data-datetime")
    let name = btn.attr("data-name")

    screenLoader.show("")

    if(typeof success !== "number") success = !(empty(success) || success === "0")
    else success = !!success;

    const fileType = fn.split('.').pop().toLowerCase();
    const filename = fn.split('.').slice(0, -1).join('.').replaceAll("/", ".")

    let content = await get(`api/scraper-logs/${filename}/${fileType}`)
    let formattedContent = "";

    if (fileType === "json") {
        formattedContent = displayJson(content);
    } else if (fileType === "log" || fileType === "txt") {
        formattedContent = displayLogOrTxt(content);
    } else if (fileType === "html") {
        formattedContent = displayHtml(content);
    } else {
        formattedContent = `<p style='color: red;'>Unsupported file type: ${fileType}</p>`;
    }



    let finalObj = {
        success,
        title: name + ` (${datetime})`,
        content: formattedContent
    }

    const modalOnClose = (modalHandler) => {
        modalHandler.dispose();
    }

    let modal = new ModalHandler('filePrint')
    modal.construct(finalObj)
    await modal.build()
    modal.bindEvents({onclose: modalOnClose})
    screenLoader.hide()
    modal.open();
}



/**
 * =========================
 * ===== PAYMENT INFO  ======
 * =========================
 */

async function affiliatePaymentInfo(btn) {
    let paymentInfo = btn.data("payment-info")
    if(empty(paymentInfo)) paymentInfo = {
        iban: null,
        swift: null,
        bank_name: null,
        bank_country_name: null,
        bank_country: null,
    };
    else if (!('bank_country_name' in paymentInfo)) paymentInfo.bank_country_name = null;
    screenLoader.show("Loading payout info...")


    let finalObj = {
        addressCountry: btn.attr("data-address-country"),
        addressCountryName: btn.attr("data-address-country-name"),
        addressCity: btn.attr("data-address-city"),
        addressStreet: btn.attr("data-address-street"),
        addressZip: btn.attr("data-address-zip"),
        addressRegion: btn.attr("data-address-region"),
        recipientName: btn.attr("data-recipient-name"),
        recipientEmail: btn.attr("data-recipient-email"),
        ...paymentInfo
    }

    const modalOnClose = (modalHandler) => {
        modalHandler.dispose();
    }

    let modal = new ModalHandler('paymentInfo')
    modal.construct(finalObj)
    await modal.build()
    modal.bindEvents({onclose: modalOnClose})
    screenLoader.hide()
    modal.open();
}







async function updatePaymentSettings(btn) {
    screenLoader.show("Updating...");
    let form = btn.parents('form#product_settings').first();
    let errorField = form.find(".error-field").first();
    const setError = (msg) => {
        errorField.text(msg);
        errorField.show();
        btn.removeClass("pointer-event-none");
        screenLoader.hide();
    }
    const unsetError = () => {
        btn.addClass("pointer-event-none")
        errorField.text("");
        errorField.hide();
    }
    unsetError();

    let formData = new FormData(form.get(0))


    let result = await post(`api/admin/settings/payment/update`, formData);
    console.log(result);

    if(result.status === "error") {
        setError(result.error.message)
        return;
    }



    notifyTopCorner(result.message, 2500)
    if(result.data.refresh) {
        screenLoader.update(result.message);
        setTimeout(function (){ window.location = window.location.href; }, 1500)
        return;
    }
    screenLoader.hide()
    btn.removeClass("pointer-event-none");
}




async function createCoupon(btn) {
    screenLoader.show("Updating...");
    let form = btn.parents('form#coupon_form').first();
    let errorField = form.find(".error-field").first();
    let productLimitFields = form.find("select[name=select_product_ids]").first();
    const setError = (msg) => {
        errorField.text(msg);
        errorField.show();
        btn.removeClass("pointer-event-none");
        screenLoader.hide();
    }
    const unsetError = () => {
        btn.addClass("pointer-event-none")
        errorField.text("");
        errorField.hide();
    }
    unsetError();

    let formData = new FormData(form.get(0))
    formData.append("product_ids", productLimitFields.val())


    let result = await post(`api/admin/settings/payment/coupon/create`, formData);
    console.log(result);

    if(result.status === "error") {
        setError(result.error.message)
        return;
    }



    notifyTopCorner(result.message, 2500)
    if(result.data.refresh) {
        screenLoader.update(result.message);
        setTimeout(function (){ window.location = window.location.href; }, 1500)
        return;
    }
    screenLoader.hide()
    btn.removeClass("pointer-event-none");
}

const couponUtility = (form) => {
    let usageSelect = form.find("select[name=usages_select]").first();
    let usageContainer = form.find("#usages_container").first();
    let usageNumber = usageContainer.find("input[name=usages]").first();
    let durationSelect = form.find("select[name=duration]").first();
    let cyclesContainer = form.find("#cycles_container").first();
    let cyclesNumber = cyclesContainer.find("input[name=cycles]").first();

    usageSelect.on("change", function () {
        let select = $(this);
        let value = select.val();
        if(value === "custom") {
            usageContainer.show();
            value = "";
        }
        else usageContainer.hide();
        usageNumber.val(value);
    })
    durationSelect.on("change", function () {
        if($(this).val() === "repeating") cyclesContainer.show();
        else cyclesContainer.hide();
    })

    $(document).on("change", "select[name=coupon_action]", function () {
        couponAction($(this));
    })
}





if(!(applicationResponding in window)) var applicationResponding = false;

function couponAction(element) {
    let action = element.val();
    if(empty(action)) return;

    if(!["removeCouponCode", "toggleCouponCode"].includes(action) || !(action in window)) return;
    window[action](element)
        .then(() => { element.val("") })
        .catch(() => { element.val("") })
}

async function toggleCouponCode(element) {
    if(applicationResponding) return;
    let rowId = element.data("row-id")
    if(empty(rowId)) return;
    applicationResponding = true;

    screenLoader.show("Toggling...")
    let result = await post(`api/admin/settings/payment/coupon/toggle`, {
        id: rowId,
    });

    if(result.status === "error") {
        notifyTopCorner(result.error.message, 10000, "bg-danger")
        applicationResponding = false;
        screenLoader.hide()
        return;
    }

    screenLoader.update(result.message);
    setTimeout(function (){ window.location = window.location.href; }, 750)
    applicationResponding = false;
}

async function removeCouponCode(element) {
    if(applicationResponding) return;
    let rowId = element.data("row-id")
    if(empty(rowId)) return;
    applicationResponding = true;

    let cbError = () => {
        applicationResponding = false;
        screenLoader.hide()
    }

    let cb = async (data) => {
        screenLoader.show("Removing...")
        let result = await post(`api/admin/settings/payment/coupon/delete`, {
            id: rowId,
        });
        if(result.status === "error") result.swalOptions = {errorText: result.error.message}
        cbError();
        return result;
    }

    let swalData = {
        callback: cb,
        callbackError: cbError,
        refreshTimeout: 1,
        visualText: {
            preFireText: {
                title: `Delete and remove coupon code?`,
                text: "This is is required to re-create a new coupon with the same Promotional code.",
                confirmButtonText: "Yes, delete",
                icon: "warning"
            },
            successText: {
                title: "Coupon deleted.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "<_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };


    swalConfirmCancel(swalData);
}







/**
 * INIT TRIGGERS
 */

if($(document).find("form#coupon_form").length) {
    couponUtility($(document).find("form#coupon_form").first());
}


/**
 * ADMIN TRIGGERS
 */

$(document).on("click", "#create_new_coupon", function () { createCoupon($(this)); })
$(document).on("click", "#save_product_settings", function () { updatePaymentSettings($(this)); })
$(document).on("click", "#save_app_general_settings", function () { updateAppGeneralSettings($(this)); })
$(document).on("click", "#save_affiliate_cookie_settings, #save_affiliate_general_settings", function () { updateAffiliateSettings($(this)); })
$(document).on("click", ".view-payment-info[data-payment-info]", function () { affiliatePaymentInfo($(this)); })
$(document).on("click", ".view-scraper-log[data-filename]", function () { scraperLogs($(this)); })
$(document).on("click", "button[name=test_webhook]", function () { testWebhook(); })
$(document).on("click", "button[name=migrate_to_production]", function () { migrateToProduction(); })















