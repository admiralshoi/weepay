
async function fetchMediaInfo(url) {
    let type = "";
    try {

        const response = await get("api/proxy", {
            url,
            method: "HEAD"
        })

        if (response.status === "success") {
            if(('Content-Type' in response.headers)) {
                const contentType = response.headers['Content-Type'];
                if (contentType.startsWith('image/')) type = "image";
                if (contentType.startsWith('video/')) type = "video";
            }
        } else {
            // console.log(response);
        }
    } catch (error) {
        console.log(error)
    }
    return type;
}

async function fetchMediaType(url) {
    let type = "";
    try {
        const response = await post(`api/media-type`, {url})
        if(response.status === "error") return type;
        return empty(response.data.media_type) ? type : response.data.media_type;
    } catch (error) {
        console.log(error)
    }
    return type;
}


async function resetPwd(btn) {
    let form = btn.parents("form").first();
    let emailField = form.find("input[name=email]").first();

    if(!emailField.length) return false;
    let email = emailField.val();

    if(empty(email)) return false;
    let result = await post("api/password-recovery", {email})
    console.error(result);
    result = ensureObject(result);

    if(typeof result !== "object" || !("status" in result)) {
        ePopup("Something went wrong", "Please try again later");
        return false;
    }

    if(result.status === "error") {
        ePopup("Something went wrong", result.error);
        return false;
    }

    ePopup(result.message, "Check your email", 0, "success", "email_approve");
    window.setTimeout(function () {window.location = window.location.href;}, 2500);
}


async function createNewPwdFromReset(btn) {
    let form = btn.parents("form").first();
    let passwordField = form.find("input[name=password]").first();
    let passwordRepeatField = form.find("input[name=password_repeat]").first();
    let token = findGetParameter("token");

    if(empty(token)) return false;
    if(!passwordField.length || !passwordRepeatField.length) return false;
    let password = passwordField.val();
    let passwordRepeat = passwordRepeatField.val();

    if(empty(password) || empty(passwordRepeat)) {
        ePopup("Empty fields", "Please fill out both password fields");
        return false;
    }
    let data = {
        password,
        password_repeat: passwordRepeat,
        token
    };

    let result = await post("api/password-recovery/reset", {data})

    if(typeof result !== "object" || !("status" in result)) {
        ePopup("Something went wrong", "Please try again later");
        return false;
    }

    if(result.status === "error") {
        ePopup("Something went wrong", result.message);
        return false;
    }

    ePopup("Password reset successful", result.message, 0, "success", "approve");
    window.setTimeout(function () {window.location = serverHost + "login";}, 1500);
}


async function baseRequest(parent,request) {
    let result = ensureObject(await requestServer(request));

    if(result === true) {
        if(parent !== null) eNotice("","HIDE");
        return true;
    }

    if(typeof result === "object" && !empty(result) && "error" in result) {
        if(parent !== null) eNotice(result.error,parent);
        return false;
    }

    if(parent !== null) eNotice("","HIDE");
    return true;
}








async function setDateRangePicker() {


    if($(document).find(".DP_RANGE").length) {
        $(document).find(".DP_RANGE").each(async function (){
            let el = $(this), startDate = 0, endDate = 0, ranges = {};
            if(el.data("no-pick") === true) return; //Used for displaying period-lists mainly

            startDate = moment().startOf('week').add(1, 'weeks').add(1, "days");
            endDate = moment().endOf('week').add(1, "weeks").add(1, "days");
            ranges = {
                'Today': [moment().startOf('day'), moment().endOf('day')],
                'Tomorrow': [moment().startOf('day').add(1, 'days'), moment().endOf('day').add(1, 'days')],
                'Next week': [startDate, endDate]
            };





            el.daterangepicker({
                opens: 'left',
                timePicker: true,
                timePicker24Hour: true,
                startDate: new Date(startDate),
                endDate: new Date(endDate),
                locale: {
                    format: 'MMMM DD YYYY, HH:mm'
                },
                ranges
            }, function(start, end) {

            });

        });
    }
}

async function hasUserSession() {
    if(!userSession) return false;

    // var checkUserActiveSession = setInterval(async function (){
    //     await get("api/has-session")
    //         .then(res => {
    //             let refresh = false;
    //             if(typeof res !== "object" || empty(res)) {
    //                 console.log("Failed to check session");
    //                 refresh = true;
    //             }
    //             else if(!("session" in res) || !res.session) {
    //                 console.log("Session expired");
    //                 refresh = true;
    //             }
    //
    //             // if(refresh) window.location = serverHost + "logout";
    //         })
    //         .catch(res => {
    //             console.log(res);
    //             // clearInterval(checkUserActiveSession);
    //             // setTimeout(function (){ window.location = serverHost; }, (2 * 1000))
    //         })
    // }, (1000 * 60));
}



async function trackEvent(action, name) {
    return; // Disabled - backend not implemented
    let _object = null, _value = null;
    await post("api/track",{request: "userLogging", event_type: action, event_value: name, _object, _value, _page: activePage});
}



async function bulkAction(selectElement) {
    let targetObjectsSelector = selectElement.data("target-bulk-items"), fetchParams = selectElement.data("bulk-info-fields"),
        bulkParent = selectElement.parents(".dataParentContainer").first(), action = selectElement.val().trim();

    if(targetObjectsSelector === undefined || fetchParams === undefined || bulkParent === undefined) return false;
    if(empty(action)) return false;

    let list = [], targetObjects = bulkParent.find("input[type=checkbox]" + targetObjectsSelector);
    if(targetObjects.length === 0) return false;

    if(empty(fetchParams)) return false;
    fetchParams = fetchParams.split(",");

    targetObjects.each(function () {
        let checkbox = $(this), values = {};
        if(!(checkbox.is(":checked"))) return;

        for(let param of fetchParams) {
            let paramValue = checkbox.data("bulk-" + param);
            if(paramValue === undefined) return;

            values[param] = paramValue;
        }

        list.push(values);
    });

    await window[action](list)
        .then(() => { selectElement.val("") })
        .catch(() => { selectElement.val("") })
}



function selectElements(el, canUnselect = true, exclusive = false) {
    if(canUnselect && !exclusive) {
        if(el.hasClass("selected")) el.removeClass("selected");
        else el.addClass("selected");
        return;
    }

    if(!canUnselect) if(el.hasClass("selected")) return;
    let parentSelector = ".dataParentContainer", elSelector = ".selectable-el";

    if(el.hasClass("selected")) {
        el.removeClass("selected");
        return;
    }

    let parent = el.parents(parentSelector).first(), elements = parent.find(elSelector);
    if(!elements.length) return;

    elements.each(function (){ if($(this).hasClass("selected")) $(this).removeClass("selected"); })

    if(el.hasClass("selected")) el.removeClass("selected");
    else el.addClass("selected");
}





// async function createUser(btn) {
//     let parent = btn.parents('#user_signup_form').first();
//     let fields = {
//         password: "input[name=password]",
//         password_repeat: "input[name=password_repeat]",
//         full_name: "input[name=full_name]",
//         email: "input[name=email]",
//         access_level: "select[name=access_level]"
//     };
//
//     if(!parent.find("input[name=policy_accept]").first().is(":checked")) {
//         ePopup("Field missing.","Please read and accept the privacy policy");
//         return false;
//     }
//     if(!parent.find("input[name=terms_accept]").first().is(":checked")) {
//         ePopup("Field missing.","Please read and accept the Terms of Use");
//         return false;
//     }
//
//     for(let fieldName in fields) {
//         let el = parent.find(fields[fieldName]).first();
//
//         if(empty(el.val())) {
//             ePopup(prepareProperNameString(fieldName) + " error", prepareProperNameString(fieldName) + " must not be empty");
//             return false;
//         }
//
//         fields[fieldName] = el.val().trim();
//     }
//
//     let result = await post("api/create-user/normal", {data: fields});
//     console.log(result);
//
//     if(typeof result !== "object" || (!("error" in result) && !("message" in result))) {
//         ePopup("Server error","Something went wrong");
//         return false;
//     }
//     if("error" in result) {
//         ePopup("Account creation error", result.error);
//         return false;
//     }
//
//     ePopup("Success", result.message, 0, "success", "approve")
//     window.setTimeout(function (){
//         window.location = serverHost;
//     }, 2000)
// }





async function createAffiliateUser(btn) {
    screenLoader.show("Validating...");
    let parent = btn.parents('#affiliate_signup_form').first();
    let errorField = parent.find("#signup-error-field").first();
    const setError = (msg) => {
        errorField.text(msg);
        errorField.show();
        btn.removeAttr("disabled");
        screenLoader.hide();
    }
    const unsetError = () => {
        btn.attr("disabled", "disabled")
        errorField.text("");
        errorField.hide();
    }

    unsetError();
    let fields = {
        password: "input[name=password]",
        password_repeat: "input[name=password_repeat]",
        full_name: "input[name=full_name]",
        email: "input[name=email]",
    };

    if(!parent.find("input[name=policy_accept]").first().is(":checked")) {
        setError("Please read and accept the Privacy Policy")
        return false;
    }
    if(!parent.find("input[name=terms_accept]").first().is(":checked")) {
        setError("Please read and accept the Terms of Use")
        return false;
    }
    if(!parent.find("input[name=affiliate_terms_accept]").first().is(":checked")) {
        setError("Please read and accept the Affiliate Terms and Usage Policy")
        return false;
    }

    for(let fieldName in fields) {
        let el = parent.find(fields[fieldName]).first();

        if(empty(el.val())) {
            setError(prepareProperNameString(fieldName) + " must not be empty")
            return false;
        }

        fields[fieldName] = el.val().trim();
    }

    screenLoader.update("Creating user...")
    let result = await post("api/create-user/affiliate", {data: fields});
    console.log(result);

    if(typeof result !== "object" || (!("error" in result) && !("message" in result))) {
        setError("Something went wrong. Try again later.");
        return false;
    }
    if("error" in result) {
        setError(result.error);
        return false;
    }

    screenLoader.update(result.message);
    window.setTimeout(function (){
        window.location = serverHost;
    }, 1500)
}










function getTimepickerTime(timeRangeElement) {
    return {
        start: Math.round(((new Date((timeRangeElement.data('daterangepicker').startDate))).valueOf()) / 1000),
        end: Math.round(((new Date((timeRangeElement.data('daterangepicker').endDate))).valueOf()) / 1000)
    };
}





function initChartDraw(element) {
    let id = element.attr("id");
    switch(id) {
        default: return;
        case "cities": return citiesChart(element);
        case "gender_count": return genderCountChart(element);
        case "age_range": return ageRangeChart(element);
        case "countries":
            google.charts.load("current", {packages:["geochart"]});
            google.charts.setOnLoadCallback(function () { countriesChart(element) });
    }
}
function countriesChart(element) {
    let data = ("creatorAnalytics" in window) && ("countries" in window.creatorAnalytics) ? window.creatorAnalytics.countries : [];
    if(empty(data)) return;

    // data.height = "500px";
    // data.title = "Top 8 Cities";
    setGoogleGeoChart(element, data);
}
function citiesChart(element) {
    let data = ("creatorAnalytics" in window) && ("cities" in window.creatorAnalytics) ? window.creatorAnalytics.cities : [];
    if(empty(data)) return;

    data.height = "500px";
    data.title = "Top 8 Cities";
    renderCharts(element.get(0), data, element.data("chart-type"));
}
function genderCountChart(element) {
    let data = ("creatorAnalytics" in window) && ("gender_count" in window.creatorAnalytics) ? window.creatorAnalytics.gender_count : [];
    if(empty(data)) return;

    data.height = "500px";
    data.title = "Gender distribution";
    renderCharts(element.get(0), data, element.data("chart-type"));

}
function ageRangeChart(element) {
    let data = ("creatorAnalytics" in window) && ("gender_age_range" in window.creatorAnalytics) ? window.creatorAnalytics.gender_age_range : [];
    if(empty(data)) return;


    let labels = data[ (Object.keys(data)[0]) ].labels;
    let series = [];
    for(let key in data) {
        let item = data[key];
        series.push({
            name: prepareProperNameString(key),
            data: Object.values(item.series)
        });
    }

    renderCharts(
        element.get(0),
        {
            labels,
            series,
            height: "750px",
            orientation: "horizontal",
            title: "Age & Gender distribution"
        },
        element.data("chart-type")
    );

}
function setLineChartNoData(chartElement) {
    let data = {
        series: {
            name: "",
            data: []
        },
        labels: [],
        title: "No data available"
    };

    renderCharts(chartElement.get(0), data, "line");
}
async function multiChart(element, data, title) {
    let chartType = element.attr("data-chart-type");
    if(chartType === undefined) {
        setLineChartNoData(element);
        return false;
    }
    renderCharts(element.get(0), {...data, title}, chartType);
}





var newCreatorData = null;
var currentCreatorUsernameSearch = null;
async function toggleCreator(btn) {
    let id = btn.attr("data-toggle-creator");
    if(typeof id === "undefined") return;

    await post(`api/creator/${id}/toggle`)
        .then(() => { window.location = window.location.href; })
}
async function createCampaignOld(btn) {
    let parent = btn.parents("#campaign_creation_container").first(), nameField = parent.find("input[name=campaign_name]").first(),
        dateField = parent.find("input[name=campaign_dates]").first(),ppcField = parent.find("input[name=ppc]").first(),
        contentTypeField = parent.find("select[name=post_types]").first(), creatorsField = parent.find("select[name=campaign_creators]").first(),
        assignedToField = parent.find("select[name=campaign_owner]").first(), trackingField = parent.find("select[name=tracking]").first(),
        trackingTagField = parent.find("input[name=tracking_hashtag]").first(), creatorlessField = parent.find("input[name=creatorless_campaign]").first(),
        eventModeField = parent.find("input[name=event_mode]").first();
    if(empty(nameField, dateField, ppcField, contentTypeField, creatorsField, trackingTagField, trackingField, creatorlessField, eventModeField)) return;

    ePopup("Creating campaign...", "Hold on a moment", 0, "warning")


    let result = await post("api/campaign/create", {data: {
        name: nameField.val(),
        date_range: getTimepickerTime(dateField),
        ppc: ppcField.val(),
        content_type: contentTypeField.val(),
        creators: creatorsField.val(),
        creatorless: creatorlessField.is(":checked"),
        event_mode: eventModeField.is(":checked"),
        tracking: trackingField.val(),
        tracking_hashtag: trackingTagField.val(),
        owner: empty(assignedToField.val()) ? null : assignedToField.val()
    }});
    console.log(result)

    if("error" in result) {
        ePopupTimeout("Failed to create campaign", result.error.message, "error", "error_triangle", 5000)
        return;
    }


    ePopupTimeout("Done!", "Successfully created campaign: " + nameField.val(), "success", "approve")
    window.setTimeout(function (){ window.location = window.location.href; }, 2000)
}
function toggleCampaignCreationContainer() {
    let container = $(document).find("#campaign_creation_container").first();
    if(!container.length) return;

    if(container.hasClass("container-open")) {
        container.removeClass("container-open");
        container.slideUp("slow");
    }
    else {
        container.addClass("container-open");
        container.slideDown("slow");
    }
}

function campaignSettingContradictions(btn) {
    let mappedContradictions = {
        event_mode_edit: "creatorless_campaign_edit",
        creatorless_campaign_edit: "event_mode_edit",
        event_mode: "creatorless_campaign",
        creatorless_campaign: "event_mode",
    }
    let btnName = btn.attr("name");
    let parentId = btnName.includes("_edit") ? "campaign_edit_container" : "campaign_creation_container";
    if(!(btnName in mappedContradictions)) return;
    let parent = btn.parents(`#${parentId}`).first();
    let targetElement = parent.find(`[name=${mappedContradictions[btnName]}]`).first();
    if(empty(parent,targetElement)) return;

    if(btn.is(":checked")) targetElement.attr("disabled", "disabled")
    else targetElement.removeAttr("disabled");
}



async function campaignUpdate(btn) {
    let parent = $(document).find("#campaign_edit_container").first(), nameField = parent.find("input[name=campaign_name_edit]").first(),
        dateField = parent.find("input[name=campaign_dates_edit]").first(),ppcField = parent.find("input[name=ppc_edit]").first(),
        contentTypeField = parent.find("select[name=post_types_edit]").first(), creatorsField = parent.find("select[name=campaign_creators_edit]").first(),
        creatorsBulkField = parent.find("input[name=creators_bulk_edit]").first(), assignedToField = parent.find("select[name=campaign_owner_edit]").first(),
        trackingField = parent.find("select[name=tracking_edit]").first(), trackingTagField = parent.find("input[name=tracking_hashtag_edit]").first(),
        creatorlessField = parent.find("input[name=creatorless_campaign_edit]").first(), eventModeField = parent.find("input[name=event_mode_edit]").first();
    if(empty(nameField, dateField, ppcField, contentTypeField, creatorsBulkField, creatorsField, trackingTagField, trackingField, creatorlessField, eventModeField)) return;


    ePopup("Updating campaign...", "Hold on a moment", 0, "warning")

    let data = {
        campaign_id: campaignId,
        name: nameField.val(),
        date_range: getTimepickerTime(dateField),
        ppc: ppcField.val(),
        content_type: contentTypeField.val(),
        creators: creatorsField.val(),
        tracking: trackingField.val(),
        tracking_hashtag: trackingTagField.val(),
        creators_bulk: creatorsBulkField.val(),
        creatorless: creatorlessField.is(":checked"),
        event_mode: eventModeField.is(":checked"),
        owner: empty(assignedToField.val()) ? null : assignedToField.val()
    };

    console.log(data)
    let result = await post("api/campaign/create", {data})

    if("error" in result) {
        ePopupTimeout("Failed to update campaign", result.error.message, "error", "error_triangle", 5000)
        return;
    }

    ePopupTimeout("Done!", "Successfully updated campaign: " + nameField.val(), "success", "approve")
    window.setTimeout(function (){ window.location = window.location.href; }, 2000)
}

async function populateCampaignDetails() {
    let parent = $(document).find("#campaign_edit_container").first(), nameField = parent.find("input[name=campaign_name_edit]").first(),
        dateField = parent.find("input[name=campaign_dates_edit]").first(),ppcField = parent.find("input[name=ppc_edit]").first(),
        contentTypeField = parent.find("select[name=post_types_edit]").first();
    if(empty(nameField, dateField, ppcField, contentTypeField)) return;

    let result = await get(`api/campaign/${campaignId}/details`)
    console.log(result);

    if(empty(result)) {
        ePopupTimeout("Campaign not found", "Could not find the campaign details. Try again later")
        return;
    }

    nameField.val(result.name);
    ppcField.val(result.ppc);
    contentTypeField.val(result.content_type);
    dateField.data('daterangepicker').setStartDate(new Date((parseInt(result.start) * 1000)));
    dateField.data('daterangepicker').setEndDate(new Date((parseInt(result.end) * 1000)));
}

function openCampaignSettings(triggerBtn) {
    let settingsBtn = $(document).find("[data-toggle-switch-object=settings][data-switch-id=campaign-content]").first();
    if(settingsBtn.length) settingsBtn.trigger("click");

    let scrollToSelector = triggerBtn.attr("data-scroll-to");
    if(empty(scrollToSelector)) return;

    let element = $(document).find(scrollToSelector).first();
    if(!element.length) return;

    $('html, body').animate({
        scrollTop: element.offset().top
    }, 1000);
}






async function removeCreatorFromCampaign(btn) {
    let creatorId = btn.attr("data-creator-id");
    if(empty(creatorId)) return;
    if(applicationResponding) return;
    applicationResponding = true;

    let cbError = () => {
        applicationResponding = false;
        screenLoader.hide()
    }
    let cb = async (data) => {
        screenLoader.show("Removing creator...")
        let result = await post(`api/campaign/remove/creator`,{data: {
            campaign_id: campaignId,
            creator_id: creatorId,
        }});
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
                title: `Remove campaign creator?`,
                text: "This will remove the creator from the campaign and ALL their campaign-associated content",
                confirmButtonText: "Yes, remove",
                icon: "warning"
            },
            successText: {
                title: "Creator removed from the campaign",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to remove creator from the campaign. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };


    swalConfirmCancel(swalData);
}





async function addCreatorCampaignCost(element) {
    if(applicationResponding) return;
    applicationResponding = true;
    let creatorId = element.attr("data-creator-id")
    let currentCurrency = element.attr("data-creator-currency")
    if(empty(creatorId)) return;


    let cbError = () => {
        applicationResponding = false;
    }
    let cb = async (data) => {
        console.log(data)
        let body = {creator_id: creatorId, cost_data: data.value}
        cbError();
        let result = await post(`api/campaigns/${campaignId}/creator-cost`, body);
        console.log(result)
        if(result.status === "error") result.swalOptions = {errorText: result.error.message}
        else {
            element.parents("tr").first().find(".campaign-creator-cost").first().text(result.data.price_title + " " + validCurrencies[result.data.currency])
            element.parents("tr").first().find(".campaign-creator-cpm-currency").first().text(validCurrencies[result.data.currency])
            element.attr("data-creator-currency", result.data.currency)
        }
        cbError();
        return result;
    }


    let currencyHtml = '';
    for(let currency of Object.keys(validCurrencies)) {
        let currencySelected = !empty(currentCurrency) && currentCurrency === currency ? "selected" : "";
        currencyHtml +=  `<option value="${currency}" ${currencySelected}>${currency}</option>`
    }


    swalConfirmCancel({
        callback: cb,
        callbackError: cbError,
        // refreshTimeout: 1000,
        visualText: {
            preFireText: {
                icon: "warning",
                title: "Add creator cost",
                text: "Add the cost of this creator on this specific campaign. This will allow for cost metrics, such as CPM, to be calculated." +
                    " Add the costs in 100s, so that 1$ = 100 and not 1.00. ",
                confirmButtonText: "Add cost",
                // input: "text",
                // inputPlaceholder: "Cost in 100s. No decimals.",
                // inputOptional: false,
                html: `
                    <div class="swal2-input-group">
                    <input type="text" id="amountInput" class="sweet-alert-amount-input amount-input form-control" placeholder="0.00" />
                    <select id="currencySelect" class="currency-select form-control noSelect">${currencyHtml}</select>
                    </div>
                `,
                focusConfirm: false,
                preConfirm: () => {
                    const amount = document.getElementById('amountInput').value;
                    const currency = document.getElementById('currencySelect').value;

                    // Validate that an amount has been entered
                    if (!amount || isNaN(parseFloat(amount))) {
                        Swal.showValidationMessage('Please enter a valid amount');
                        return false;
                    }

                    return { amount, currency };  // Return values for further processing
                }
            },
            successText: {
                title: "Cost added",
                text: "The cost has been added / updated accordingly.",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "",
                icon: "error",
                html: ""
            }
        }
    });

}




async function invalidateCookie(btn) {
    let cookieId = btn.attr("data-cookie-id");
    if(empty(cookieId)) return;
    if(applicationResponding) return;
    applicationResponding = true;

    let cbError = () => {
        applicationResponding = false;
        screenLoader.hide()
    }
    let cb = async (data) => {
        screenLoader.show("Invalidating cookie...")
        let result = await post(`api/cookie/invalidate/${cookieId}`);
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
                title: `Invalidate cookie?`,
                text: "Invalidating a cookie is permanent. It will not affect any contributions made by the cookie. Invalidating a cookie will free up room for other cookies.",
                confirmButtonText: "Yes, invalidate",
                icon: "warning"
            },
            successText: {
                title: "Cookie invalidated",
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



function tableActions(element) {
    let action = element.val();
    if(empty(action)) return;

    if(!["invalidateCookie", "toggleUserSuspension"].includes(action) || !(action in window)) return;

    window[action](element)
        .then(() => { element.val("") })
        .catch(() => { element.val("") })
}



function campaignCreatorAction(element) {
    let action = element.val();
    if(empty(action)) return;

    if(!["removeCreatorFromCampaign", "addCreatorCampaignCost"].includes(action) || !(action in window)) return;
    window[action](element)
        .then(() => { element.val("") })
        .catch(() => { element.val("") })
}



async function exportCampaignToCsv() {
    let result = await post(`api/campaign/${campaignId}/export/csv`)
    if(result.status === "error") {
        ePopupTimeout("Failed to create export: ", result.error.message);
        return false;
    }
    ePopupTimeout("Default toggled", result.message, "success", "approve", 2500)
    window.open(result.data.link);
    return true;
}



var liveMentionTable = null;
async function mentionLiveTableTracking(table) {
    let offset = table.attr("data-row-offset"), pageLength = 100, blink = true;
    if(typeof offset === "undefined") {
        offset = 0;
        blink = false;
    }
    else offset = parseInt(offset);

    let result = await get("api/mention/live", { offset,page_size: pageLength })
    if(empty(result)) return;

    let newOffset = result[0].timestamp;
    if(!blink) liveMentionTable = table.DataTable({
        order: [4, "desc"],
        columnDefs: [
            {
                targets: 4,
                render: function(data, type, row) {
                    if (!data) return '';
                    if (type === 'display') return data.display;
                    if (type === 'sort') return data.sort;
                    return data.display;
                },
                data: function(row, type, set, meta) {
                    return row[4];
                },
            }
        ]
    });

    for(let item of result) {
        let rowNode = liveMentionTable
            .row.add( [
                item.id,
                !('creator_link' in item) || empty(item.creator_link) ? item.username : `<a href="${item.creator_link}">${item.username}</a>`,
                !('permalink' in item) || empty(item.permalink) ? prepareProperNameString(item.type) :
                    `<a href="${item.permalink}" target="_blank">${prepareProperNameString(item.type)}<i class="mdi mdi-open-in-new ml-1"></i></a>`,
                item.campaign === 0 ? "No" : '<a href="' + item.campaign_link + '">' + item.campaign_count + ' ' + pluralS(item.campaign_count, "campaign") + '</a>',
                {
                    display: item.display_date, // Display value (what you see in the table)
                    sort: item.timestamp        // Sort value (what's used for sorting)
                }
            ] )
            .draw()
            .node();

        if(blink) {
            $(rowNode).addClass("blink-bg")
            setTimeout(function () {
                $(rowNode).removeClass("blink-bg")
            }, 4000);
        }
    }

    table.attr("data-row-offset", newOffset);
}





async function removeIntegration(el) {
    let rowId = el.attr("data-id"), parentRow = el.parents("tr").first();
    let result = await post(`api/integration/${rowId}/remove`)
    if(result.status === "error") {
        ePopupTimeout("Failed to remove integration", result.error.message);
        return false;
    }
    ePopupTimeout("Integration removed", result.message, "success", "approve")
    parentRow.slideUp("fast", function() { $(this).remove(); } );
    return true;
}
async function toggleIntegrationDefault(el) {
    let rowId = el.attr("data-id");
    let result = await post(`api/integration/${rowId}/default/toggle`)

    if(result.status === "error") {
        ePopupTimeout("Failed...", result.error.message);
        return false;
    }
    ePopup("Enabled", result.message, 0, "success", "approve")
    window.setTimeout(function () { window.location = window.location.href; }, 2000)
    return true;
}






async function updateUserSettings(btn) {
    let parent = btn.parents('form').first();
    let fields = ["email", "full_name", "password"], collector = {};


    for(let field of fields) {
        let element, value, passwordConfirmElement, passwordConfirmValue
        switch (field) {
            default: break;
            case "email":
            case "full_name":
                element = parent.find(`[name=${field}]`).first();
                if(!element.length) continue;
                value = element.val().trim();
                if(empty(value)) continue;
                collector[field] = value;
                break;

            case "password":
                element = parent.find(`[name=${field}]`).first();
                if(!element.length) continue;
                value = element.val().trim();
                if(empty(value)) continue;

                passwordConfirmElement = parent.find(`[name=password_repeat]`).first();
                if(!passwordConfirmElement.length) continue;
                passwordConfirmValue = passwordConfirmElement.val().trim();

                if(empty(passwordConfirmValue) || value !== passwordConfirmValue) {
                    ePopupTimeout("Passwords do not match", "The password and the password confirmation must match. Please correct the indifference to continue")
                    return;
                }
                collector[field] = value;
                break;
        }
    }

    if(empty(collector)) return;
    collector.uid = parent.attr("data-user-id");
    let result = await post("account-settings",{data: collector});

    if(typeof result !== "object" || !(("status" in result) && ("message" in result))) {
        ePopup("Server error","Something went wrong");
        return false;
    }
    if(result.status === "error") {
        ePopup("Failed to update fields", result.message);
        return false;
    }

    ePopup("Fields updated!", result.message, 0, "success", "approve")
    window.setTimeout(function (){window.location = window.location.href;}, 1500)
}




function editBrandProfile(btnOpen) {
    let btnSave = $(document).find("button[name=edit-brand-profile-save]").first(),
        nameField = $(document).find("input[name=brand_name_edit]").first(),
        descField = $(document).find("textarea[name=brand_description_edit]").first(),
        nameEl = $(document).find("#brand_name").first(),
        descEl = $(document).find("#brand_description").first();
    if(empty(btnOpen, btnSave, nameField, descField, nameEl, descEl)) return;

    btnOpen.hide();
    btnSave.show();
    nameEl.hide();
    nameField.show();
    descEl.hide();
    descField.show();

    const closeUp = () => {
        btnSave.hide();
        btnOpen.show();
        nameField.hide();
        nameEl.show();
        descField.hide();
        descEl.show();
    }


    const editData = async () => {
        let data = {};
        if(empty(nameField.val().trim())) {
            notifyTopCorner("The brand-name should not be empty",5000, "bg-red")
            closeUp();
            return;
        }
        if(empty(descField.val().trim())) {
            notifyTopCorner("The brand-description should not be empty", 5000,"bg-red")
            closeUp();
            return;
        }
        data.name = nameField.val().trim()
        data.description = descField.val().trim()

        let result = await post("profile/brand-details", {data});
        console.log(result)
        if(typeof result !== "object") {
            notifyTopCorner("Try again later", 5000,"bg-red");
            closeUp();
            return;
        }

        if(result.status === "error") {
            notifyTopCorner(result.error.message, 5000,"bg-red");
            closeUp();
            return;
        }
        notifyTopCorner("Fields update");
        nameEl.text(data.name)
        descEl.html((data.description).replace(/\n/g, '<br>'))
        closeUp()
    }
    btnSave.off("click").on("click", function () { editData() })
}


async function toggleOpenCampaign(btn) {
    let enabled = btn.is(":checked");
    btn.attr("disabled", "disabled")


    let result = await post("campaigns/" + campaignId + "/open/toggle", );
    console.log(result)
    if(typeof result !== "object") {
        notifyTopCorner("Try again later", 5000,"bg-red");
        btn.removeAttr("disabled")
        btn.prop("checked", !enabled)
        return;
    }
    if(result.status === "error") {
        notifyTopCorner(result.error.message, 7000,"bg-red");
        btn.removeAttr("disabled")
        btn.prop("checked", !enabled)
        return;
    }

    notifyTopCorner(result.message);
    btn.removeAttr("disabled")
}


async function setOpenCampaignDetails(btn) {
    btn.attr("disabled", "disabled")
    let fields = {
        picture: "input[name=media_paths]",
        details: "textarea[name=campaign_details]",
        due: "input[name=campaign_applications_due_at]",
        max_applicants: "input[name=campaign_max_applicants]",
        rewards: "textarea[name=campaign_rewards]",
    }, data = {}
    let optional = ["rewards"]

    for(let field in fields) {
        let selector = fields[field]
        let el = $(document).find(selector).first();
        if(!el.length || empty(el.val())) {
            if(optional.includes(field)) continue;
            notifyTopCorner("Please make sure to fill out required fields (" + field + ")", 5000,"bg-red");
            btn.removeAttr("disabled")
            return
        }

        let value = el.val();
        if(el.attr("type") !== "number") value = value.trim();
        data[field] = value
    }


    let result = await post("campaigns/" + campaignId + "/open/set", {data});
    console.log(result)
    if(typeof result !== "object") {
        notifyTopCorner("Try again later", 5000,"bg-red");
        btn.removeAttr("disabled")
        return;
    }
    if(result.status === "error") {
        notifyTopCorner(result.error.message, 7000,"bg-red");
        btn.removeAttr("disabled")
        return;
    }

    notifyTopCorner(result.message);
    btn.removeAttr("disabled")
}


function initFilepondElement(thumbParent) {
    let thumbFileElement = thumbParent.parents(".dataParentContainer").first().find("input[type=file]").first();
    thumbParent.on("click", function () {
        thumbFileElement.trigger("click");
    })

    let loader = null;
    let divChild = thumbParent.find("div").first();

    let isMultiple = thumbFileElement.attr("multiple") !== undefined;

    thumbFileElement.fileuploader({
        captions: "en",
        dialogs: {
            // alert dialog
            alert: function(text) {
                return Swal.fire({
                    title: "Error",
                    text: text,
                    type: "error",
                    confirmButtonText: "Got it"
                });
            },

            // confirm dialog
            confirm: function(text, callback) {
                confirm(text) ? callback() : null;
            }
        },

        changeInput: ' ',
        enableApi: true,
        addMore: true,
        maxFiles: 10,


        dragDrop: {
            container: '.upload-cover-thumb'
        },
        afterRender: function(listEl, parentEl, newInputEl, inputEl) {
            var plusInput = listEl.find('.upload-cover-thumb'),
                api = $.fileuploader.getInstance(inputEl.get(0));

            plusInput.on('click', function() {
                api.open();
            });

            api.getOptions().dragDrop.container = plusInput;
        },


        upload: {
            url: serverHost+'api/media/image/upload',
            data: null,
            type: 'POST',
            enctype: 'multipart/form-data',
            start: true,
            synchron: true,
            chunk: 50,
            beforeSend: function(xhr) {
                if (('testingCredAuth' in window) && testingCredAuth !== null)
                    xhr.setRequestHeader('Authorization', 'Basic ' + btoa(testingCredAuth));
                $('.btn-blocked').show();


                if(loader === null) {
                    loader = progressLoader.set(thumbParent.get(0))
                    divChild.addClass("d-none")
                    // if(children === null) children = thumbParent.children();
                    // children.each(function () {$(this).addClass("d-none");})
                }
                return true;
            },


            onSuccess: function(result, item) {
                console.log([result, item])
                let fileName = item.file.name;
                let format = item.format;

                get("api/media/upload/" + fileName)
                    .then(res => {
                        if(empty(res.path)) return;

                        let coverMedia;
                        if(format === "image") {
                            coverMedia = thumbParent.find("img").first();

                        }
                        else coverMedia = thumbParent.find("video").first().find("source");

                        let currentPathsElement = thumbParent.find("input[type=hidden][name=media_paths]").first();
                        if(!isMultiple) {
                            coverMedia.attr("src", serverHost + res.path);
                            currentPathsElement.val(res.path);
                        }
                        else {
                            let value = currentPathsElement.val();
                            if(value === "") value = "[]";
                            value = ensureObject(value);
                            value.push({url: res.path, is_image: format === "image" ? 1 : 0});
                            currentPathsElement.val(ensureString(value));
                            let currentCount = Object.keys(value).length;
                            if(currentCount === 1) coverMedia.attr("src", serverHost + res.path);

                            let counterElement = thumbParent.parents(".file-upload-parent").first().find(".file-count").first();
                            if(counterElement.length) {
                                counterElement.text(currentCount);
                                if(counterElement.parents().first().hasClass("d-none")) counterElement.parents().first().removeClass("d-none");
                            }
                        }

                        thumbParent.css("opacity", 1);
                        let hiddenItem = format === "image" ? coverMedia : coverMedia.parents('video').first();
                        if(hiddenItem.hasClass("d-none")) {
                            hiddenItem.removeClass("d-none");
                            hiddenItem.parents().first().find("div").addClass("d-none");
                        }
                        if(format !== "image") hiddenItem.get(0).load();
                    })



                if(loader !== null) {
                    loader = progressLoader.delete(loader);
                }

                $('.btn-blocked').hide();
            },
            onError: function(item) {
                if(loader !== null) {
                    loader = progressLoader.delete(loader);
                    divChild.removeClass("d-none")
                }
                item.html.find('.progress-holder, .fileuploader-action-popup, .fileuploader-item-image').hide();

                $('.btn-blocked').hide();

            },
            onProgress: function(data, item) {
                if(loader !== null) loader = progressLoader.update(loader, data.percentage);
                var progressBar = item.html.find('.progress-holder');

                if(progressBar.length > 0) {
                    progressBar.show();
                    progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                }

                item.html.find('.fileuploader-action-popup, .fileuploader-item-image').hide();
            }
        },
        onRemove: function(item) {
            $.post(serverHost+'requests.php?tmp_remove_file', {
                file: item.name,
                _token: $('meta[name="csrf-token"]').attr('content')
            });
        }

    }); // End fileuploader()
}





async function requestThirdPartyCampaignMetrics(btn){
    btn.attr("disabled", "disabled")

    let cb = async (data) => {
        let result = await post(`request-data/campaign-metrics`, {data: {campaign_id: campaignId}});
        let swalOptionsResponse = {}
        if(result.status === "error") swalOptionsResponse.errorText = result.error.message
        else {
            swalOptionsResponse.successHtml = '<div style="padding: 2rem; font-size: 16px; row-gap: 10px;" class="flex-col-start copyContainer">';
            swalOptionsResponse.successHtml += `<p>Please forward the link below to the creators who should upload their media-metrics.</p>`;
            swalOptionsResponse.successHtml += '<p>The link is valid for the next <u class="font-weight-bold">' + result.expires_in + '</u></p>';
            swalOptionsResponse.successHtml += '<div class="flex-col-start" style="row-gap: 1px;">';
            swalOptionsResponse.successHtml += '<p class="font-italic font-12">Click the link to copy it</p>';
            swalOptionsResponse.successHtml += '<p class="copyBtn copyElement cursor-pointer" style="color: #0579f5; white-space: normal">' + result.link + '</p>';
            swalOptionsResponse.successHtml += '</div>';
            swalOptionsResponse.successHtml += '</div>';
        }

        btn.removeAttr("disabled")
        result.swalOptions = swalOptionsResponse;
        return result;
    }

    let cbError = () => {
        btn.removeAttr("disabled")
    }

    swalConfirmCancel({
        callback: cb,
        callbackError: cbError,
        // refreshTimeout: 1000,
        visualText: {
            preFireText: {
                icon: "warning",
                title: "Request media metrics?",
                text: "Create a link where creators can manually upload their campaign-media metrics.",
                confirmButtonText: "Create request",
            },
            successText: {
                title: "Request created",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "",
                icon: "error",
                html: ""
            }
        }
    });
}



async function requestThirdPartyIntegration(btn){
    btn.attr("disabled", "disabled")

    let cb = async (data) => {
        console.log(data)
        let result = await post(`request-data/integration`, {data});
        let swalOptionsResponse = {}
        if(result.status === "error") swalOptionsResponse.errorText = result.error.message
        else {
            swalOptionsResponse.successHtml = '<div style="padding: 2rem; font-size: 16px; row-gap: 10px;" class="flex-col-start copyContainer">';
            if(!empty(result.username)) swalOptionsResponse.successHtml += '<p>You have requested an integration for the user: ' +
                '<span class="font-italic font-weight-bold">@' + result.username + '</span></p>';
            swalOptionsResponse.successHtml += `<p>Please forward the link below to the person that should integrate.</p>`;
            swalOptionsResponse.successHtml += '<p>The link is valid for the next <u class="font-weight-bold">' + result.expires_in + '</u></p>';
            swalOptionsResponse.successHtml += '<div class="flex-col-start" style="row-gap: 1px;">';
            swalOptionsResponse.successHtml += '<p class="font-italic font-12">Click the link to copy it</p>';
            swalOptionsResponse.successHtml += '<p class="copyBtn copyElement cursor-pointer" style="color: #0579f5; white-space: normal">' + result.link + '</p>';
            swalOptionsResponse.successHtml += '</div>';
            swalOptionsResponse.successHtml += '</div>';
        }

        btn.removeAttr("disabled")
        result.swalOptions = swalOptionsResponse;
        return result;
    }

    let cbError = () => {
        btn.removeAttr("disabled")
    }

    swalConfirmCancel({
        callback: cb,
        callbackError: cbError,
        // refreshTimeout: 1000,
        visualText: {
            preFireText: {
                icon: "warning",
                title: "Request integration?",
                text: "Request a brand, or creator to integrate their account to enable tracking. You can specify the username of the account required to avoid confusion.",
                confirmButtonText: "Create request",
                input: "text",
                inputPlaceholder: "Username (Optional)",
                inputOptional: true
            },
            successText: {
                title: "Request created",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "",
                icon: "error",
                html: ""
            }
        }
    });
}



async function applyToCampaign(btn){
    btn.attr("disabled", "disabled")

    let cb = async () => {
        let result = await post(`campaigns/${campaignId}/apply`);
        if(result.status === "error") result.error = result.error.message
        btn.removeAttr("disabled")
        return result;
    }

    let cbError = async () => {
        btn.removeAttr("disabled")
    }

    swalConfirmCancel({
        callback: cb,
        callbackError: cbError,
        refreshTimeout: 1000,
        visualText: {
            preFireText: {
                title: "Apply to campaign?",
                text: "After applying the brand will be notified. You can retract or view your application within your 'applications' menu.",
                icon: "warning",
                confirmButtonText: "Apply now"
            },
            successText: {
                title: "Applied to campaign.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to apply. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    });
}



var applicationResponding = false;
async function brandRespondToApplication(btn){
    if(applicationResponding) return;
    applicationResponding = true;
    let action = btn.attr("data-action")
    let applicationId = btn.parents(".application-action").first().attr("data-application");
    if(empty(applicationId)) return;

    let cbError = () => {
        applicationResponding = false;
    }
    let cb = async (data) => {
        console.log(data)
        let body = {}
        if(action === "comment") body.comment = data.value;
        let result = await post(`application/${applicationId}/${action}`, body);
        if(result.status === "error") result.error = result.error.message
        cbError();
        return result;
    }



    let swalData = {
        callback: cb,
        callbackError: cbError,
        refreshTimeout: 1,
        visualText: {
            successText: {
                title: "Applied to campaign.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to apply. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };

    let preFireText, successText, errorText;
    if(action === "comment") {
        preFireText = {
            title: "Add a comment",
            text: "This will also mark the application as 'seen'",
            confirmButtonText: "Add comment",
            input: "text",
            inputPlaceholder: "Type a comment..."
        }
        swalData.visualText.successText.title = "Comment added"
        swalData.visualText.errorText.text = "Failed to add comment. <_ERROR_MSG_>"
    }
    else if(action === "approve") {
        preFireText = {
            title: "Approve application?",
            text: "You can remove the creator from the campaign directly later on.",
            icon: "warning",
            confirmButtonText: "Approve",
        }
        swalData.visualText.successText.title = "Application approved."
        swalData.visualText.errorText.text = "Failed to approve application. <_ERROR_MSG_>"
    }
    else if(action === "reject") {
        preFireText = {
            title: "Reject application?",
            text: "This is permanent. The creator will not be allowed to re-apply either.",
            icon: "warning",
            confirmButtonText: "Reject",
        }
        swalData.visualText.successText.title = "Application rejected."
        swalData.visualText.errorText.text = "Failed to reject application. <_ERROR_MSG_>"
    }
    else if(action === "retract") {
        preFireText = {
            title: "Retract application?",
            text: "This is permanent. You'll not be allowed to re-apply to this campaign.",
            icon: "warning",
            confirmButtonText: "Retract",
        }
        swalData.visualText.successText.title = "Application retracted."
        swalData.visualText.errorText.text = "Failed to retract application. <_ERROR_MSG_>"
    }
    else if(action === "accept") {
        preFireText = {
            title: "Accept application approval?",
            text: "The brand has approved your application. You'll have to accept it to join the campaign.",
            confirmButtonText: "Accept",
        }
        swalData.visualText.successText.title = "Application approval accepted."
        swalData.visualText.errorText.text = "Failed to accept application approval. <_ERROR_MSG_>"
    }
    else if(action === "unset") {
        preFireText = {
            title: "Unset application?",
            text: "This will simply re-mark the application as 'received'.",
            icon: "warning",
            confirmButtonText: "Unset",
        }
        swalData.visualText.successText.title = "Application unset."
        swalData.visualText.errorText.text = "Failed to unset application. <_ERROR_MSG_>"
    }
    swalData.visualText.preFireText = preFireText;


    swalConfirmCancel(swalData);
}





function massCheckAction(btn) {
    let id = btn.attr("name");
    let select = $(document).find("#" + id).first();
    if(!select.length) return;
    let action = select.val();
    if(empty(action)) return;

    let selectedElements = $(document).find("." + id + ":checked");
    if(!selectedElements.length) return;

    switch (action) {
        default: return;
        case "mark_shadow_media":
            markShadowMedia(selectedElements)
            break;
        case "remove_from_campaign":
            removeCampaignMedia(selectedElements)
            break;
        case "recover_from_bin":
            recoverCampaignMedia(selectedElements)
            break;
        case "mark_unpaid_as_paid":
            markUnpaidAsPaid(selectedElements)
            break;
    }


}






async function recoverCampaignMedia(elements) {
    if(applicationResponding) return;
    applicationResponding = true;
    let rowIds = [];
    elements.each(function () { rowIds.push($(this).attr("data-row-id")) })

    console.log(rowIds)

    let cbError = () => {
        applicationResponding = false;
    }
    let cb = async (data) => {
        console.log(data)
        let body = {ids: rowIds}
        console.log(body)
        cbError();
        let result = await post(`api/campaigns/${campaignId}/recover-media-associations`, body);
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
                title: `Recover ${rowIds.length} media from the bin?`,
                text: "These items will have an impact on the metrics once outside the bin.",
                confirmButtonText: "Recover",
                icon: "warning"
            },
            successText: {
                title: "Associations recovered from the bin.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to recover associations from the bin. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };

    swalConfirmCancel(swalData);
}


async function markShadowMedia(elements) {
    if(applicationResponding) return;
    applicationResponding = true;
    let mediaIds = [];
    elements.each(function () { mediaIds.push($(this).attr("data-media-id")) })

    console.log(mediaIds)

    let cbError = () => {
        applicationResponding = false;
    }
    let cb = async (data) => {
        console.log(data)
        let body = {media_ids: mediaIds}
        console.log(body)
        cbError();
        let result = await post(`api/campaigns/${campaignId}/mark-shadow-media`, body);
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
                title: `Toggle relevancy for ${mediaIds.length} medias?`,
                text: "Marking media as irrelevant means their metrics wont be updated and they wont count towards any metrics. You may however still view them.",
                confirmButtonText: "Yes, go",
                icon: "warning"
            },
            successText: {
                title: "Relevancy toggled.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to to toggle relevancy. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };


    swalConfirmCancel(swalData);
}


async function removeCampaignMedia(elements) {
    if(applicationResponding) return;
    applicationResponding = true;
    let mediaIds = [];
    elements.each(function () { mediaIds.push($(this).attr("data-media-id")) })

    console.log(mediaIds)

    let cbError = () => {
        applicationResponding = false;
    }
    let cb = async (data) => {
        console.log(data)
        let body = {media_ids: mediaIds}
        console.log(body)
        cbError();
        let result = await post(`api/campaigns/${campaignId}/remove-media-associations`, body);
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
                title: `Move ${mediaIds.length} media to the bin?`,
                text: "Items from the bin may be recovered within a certain time.",
                confirmButtonText: "Remove",
                icon: "warning"
            },
            successText: {
                title: "Associations moved to the bin.",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to move associations to the bin. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };


    swalConfirmCancel(swalData);
}


async function loadCampaignCreatorMedia(btn) {
    let currentText = buttonInProcess(btn);
    let target = btn.attr("data-target-field");
    let field = $(document).find(target);
    let outputContainer = $(document).find("#media-dom-grid").first();
    let errorElement = $(document).find("#error-field").first();
    if(empty(field, outputContainer)) return buttonIdle(btn, currentText);
    if(!errorElement.length) return buttonIdle(btn, currentText);
    let username = field.val().trim().replace("@", "");

    const clearError = () => {
        errorElement.text("")
        errorElement.hide()
    }
    const setError = (txt) => {
        errorElement.text(txt)
        errorElement.show()
    }

    clearError();
    if(empty(username)) return buttonIdle(btn, currentText);
    screenLoader.show("Loading media...")

    let params = {
        token: "requestToken" in window ? requestToken : null,
        media_fields: ["id", "display_url", "type", "media_type", "permalink", "timestamp"],
        with_metrics: true
    }

    let result = await get(`api/campaigns/${campaignId}/creator-media/${username}`, params)
    console.log(result)
    if(result.status === "error") {
        setError(result.error.message)
        screenLoader.hide();
        return buttonIdle(btn, currentText);
    }

    if(empty(result.data)) {
        setError("Unable to load user-media.")
        screenLoader.hide();
        return buttonIdle(btn, currentText);
    }


    let gridTemplate = "";
    outputContainer.html("")

    await get(`api/template/element/media-dom-grid-item`)
        .then(responseText => {
            gridTemplate = responseText
        })
        .catch(error => {
            let code = error.status, errorText = error.statusText;
            console.error(`Build error while fetching template:: (${code}) ${errorText}`)
            setError(`Build error while fetching template:: (${code}) ${errorText}`)
        })
    if(empty(gridTemplate)) {
        screenLoader.hide();
        return buttonIdle(btn, currentText);
    }

    let usernameElement = $(`<div class="col-12"><p class="font-22">@${username}</p></div>`)
    gridTemplate = $(gridTemplate);
    outputContainer.append(usernameElement)

    for(let item of result.data) {
        let template = gridTemplate.clone();
        let linkElement;
        let statusElement
        let mediaElement;
        let type = item.type;
        let mediaType = await fetchMediaType(item.display_url);
        if(empty(mediaType)) {
            if(item.display_url.slice(-4) === ".mp4")  mediaType = "video";
            else if([".jpg", ".png", "jpeg","webp", ".gif"].includes(item.display_url.slice(-4)))  mediaType = "image";
        }


        if(mediaType !== "video") {
            mediaElement = $(`<img src="${resolveAssetPath(item.display_url)}" class="w-100 media-item-media" />`)
        }
        else {
            mediaElement = $(`<video class="w-100 media-item-media" disabled=""><source type="video/mp4" src="${resolveAssetPath(item.display_url)}"></video>`)
        }

        if(type !== "story") {
            linkElement = $(`<a href="${item.permalink}" target="_blank" class="font-16 font-weight link-secondary hover-underline">View post - <span class="">${item.datetime}</span></a>`)
        }
        else {
            linkElement = $(`<p class="font-16 font-weight mb-0">Story post - <span class="">${item.datetime}</span></p>`)
        }

        statusElement = $(`<p class="mb-0 font-16"><span class="font-weight-bold">Status:</span><span class="ml-2 color-red attachment-status">No attachments</span></p>`)

        template.find(".media-media-element-container").first().html(mediaElement)
        template.find(".media-link-element-container").first().html(linkElement)
        template.find(".media-link-element-container").first().append(statusElement)

        if(item.has_metrics) {
            let waterMark = $(
                '<div class="media-action-overlay noSelect">' +
                    '<div class="bg-success border-radius-50 square-30 flex-row-around flex-align-center">' +
                        '<i class="mdi mdi-check color-white font-20"></i>' +
                    '</div>' +
                '</div>'
            );
            template.find(".media-item-inner-container").first().append(waterMark)
            let statusElement = template.find(".attachment-status").first();
            if(statusElement.length) {
                statusElement.removeClass("color-red")
                statusElement.addClass("color-green")
                statusElement.text("Completed")
            }

            template.find(".modal-trigger").each(function (){
                $(this).removeClass("modal-trigger")
                $(this).removeClass("cursor-pointer")
            })
            template.find(".media-stat-overlay").first().find("p").first().text("Attachment complete")
        }

        let tmp = (Handlebars.compile(template.get(0).outerHTML))({
            mediaId: item.id,
        });
        outputContainer.append(tmp)
    }
    scrollToElement(outputContainer)

    screenLoader.hide();
    return buttonIdle(btn, currentText);

}



async function campaignMediaAttachment() {
    const triggerModal = async (domElement) => {
        let mediaId = domElement.attr("data-media-id");
        if(empty(mediaId)) return;

        let domParent;
        if(domElement.hasClass("media-item-container")) domParent = domElement
        else domParent = domElement.parents(".media-item-container").first();

        let modal = new ModalHandler('mediaMetricsAttachment')
        modal.construct({media_id: mediaId})
        await modal.build()
        modal.bindEvents({
            imagePreview: async (btn, modalHandler) => {
                const MAX_FILES = "maxMediaAttachments" in window ? maxMediaAttachments : 4;
                const setError = (errorBox,txt) => {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                    end()
                }
                const clearError = (errorBox) => {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
                const end = () => {
                    btn.removeAttr('disabled')
                }
                const start = () => {
                    btn.attr('disabled', 'disabled')
                }
                start();

                let files = btn.get(0).files;
                if(empty(files)) {
                    end();
                    return;
                }

                let modalBody = null;
                btn.parents().each(function () {
                    if($(this).find(".modal-body").length) {
                        modalBody = $(this).find(".modal-body").first();
                        return true;
                    }
                })
                if (empty(modalBody)) {
                    end();
                    return;
                }

                modalBody.find("#form-nav-back").first().hide();
                modalBody.find("#upload-images").first().show();

                let previewContainer = modalBody.find(".vertical-file-preview-container"), errorBox = modalBody.find(".modalErrorBox").first();
                if(empty(previewContainer, errorBox)) {
                    end();
                    return;
                }
                errorBox = errorBox.clone()
                previewContainer.html(errorBox)



                clearError(errorBox);

                let imageTemplate = "";
                await get(`api/template/element/image-upload-preview`)
                    .then(responseText => {
                        imageTemplate = responseText
                    })
                    .catch(error => {
                        let code = error.status, errorText = error.statusText;
                        console.error(`Build error while fetching template:: (${code}) ${errorText}`)
                        setError(errorBox, `Build error while fetching template:: (${code}) ${errorText}`)
                    })
                if(empty(imageTemplate)) {
                    end();
                    return;
                }

                let removes = [];
                for (let n = 0; n < files.length; n++) {
                    if(n +1 > MAX_FILES) {
                        removes.push(n)
                        continue;
                    }
                    let file = files[n]
                    let tmp = (Handlebars.compile(imageTemplate))({
                        fileId: n,
                        blob: URL.createObjectURL(file)
                    });
                    previewContainer.append(tmp)
                }


                const removeFileIndex = (fileElement, index) => {
                    let filesList = Array.from(fileElement.get(0).files);

                    const dataTransfer = new DataTransfer();
                    if (filesList.length > 0) {
                        if (index >= 0 && index < filesList.length) {
                            filesList.splice(index, 1); // Remove the element at the specified index
                        }
                        filesList.forEach(file => {
                            dataTransfer.items.add(file);
                        });
                    }
                    return dataTransfer.files;
                }

                const removeImagePreview = (btn) => {
                    let parent = btn.parents(".img-modal-container").first();
                    if(!parent.length) return false;
                    let index = parent.attr("data-file-n")
                    if(index === undefined) return false;
                    if(typeof index !== "number") index = parseInt(index);
                    let fileElement = parent.parents("#fileHandleContainer").first().find("input[type=file]");
                    if(!fileElement.length) return false;

                    fileElement.get(0).files = removeFileIndex(fileElement, index);
                    parent.slideUp(400, function() { $(this).remove(); });
                    clearError(errorBox)
                    return true;
                }
                $(".remove-image-preview").off("click").on("click", function (){
                    removeImagePreview($(this));
                })

                if(!empty(removes)) {
                    let fileElement = $(document).find("#fileHandleContainer").first().find("input[type=file]");
                    for(let index of removes) {
                        removeFileIndex(fileElement, index)
                    }

                    setError(errorBox, "Truncated the amount of files. Max: " + MAX_FILES);
                }
                end();
            },

            sendForm: async (btn, modalHandler) => {
                const MAX_FILES = "maxMediaAttachments" in window ? maxMediaAttachments : 4;
                const end = () => {
                    btn.removeAttr('disabled')
                }
                const start = () => {
                    btn.attr('disabled', 'disabled')
                }
                const setError = (errorBox,txt) => {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                    end()
                }
                const clearError = (errorBox) => {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
                start();

                let modalBody = null;
                let modalFooter = null;
                btn.parents().each(function () {
                    if($(this).find(".modal-body").length) {
                        modalBody = $(this).find(".modal-body").first();
                        modalFooter = $(this).find(".modal-footer").first();
                        return true;
                    }
                })
                if (empty(modalBody)) {
                    end();
                    return;
                }

                let errorBoxInitial = modalBody.find(".modalErrorBox").first(), errorBox = modalFooter.find(".modalSendErrorBox").first();
                let form = modalBody.find("form#fileHandleContainer").first(), fileElement = form.find("input[type=file]").first();
                let uploadContainer = modalBody.find("#upload-list").first(), metricListContainer = modalBody.find("#metric-list").first();
                let metricContent = modalBody.find("#metric-content").first();
                let navBackButton = modalBody.find("#form-nav-back").first();
                let storeMetricsBtn = modalBody.find("#confirm-save-metrics").first();
                if(empty(errorBox, modalFooter, form, fileElement, uploadContainer, metricListContainer, metricContent, navBackButton, storeMetricsBtn)) {
                    end();
                    return;
                }

                clearError(errorBoxInitial);
                clearError(errorBox);

                let files = fileElement.get(0).files;
                if(empty(files)) {
                    setError(errorBox, "No files selected.")
                    return;
                }
                if(files.length > MAX_FILES) {
                    setError(errorBox, "You may only upload a total maximum of " + MAX_FILES + " images.")
                    return;
                }


                const storeEvaluatedMetrics = async (callbackToken) => {
                    console.log(callbackToken)
                    let elements = metricContent.find("input[type=number]");
                    if(!elements.length) {
                        setError(errorBox, "No elements present")
                        return false;
                    }

                    let metrics = {};
                    elements.each(function () {
                        let name = $(this).attr("name").trim();
                        metrics[name] = $(this).val()
                    })

                    console.log(metrics)

                    console.log(metrics)
                    let data = {
                        metrics,
                        media_id: mediaId,
                        callback_token: callbackToken
                    };
                    screenLoader.show("Storing metrics...")
                    let result = await post(`request-data/campaign/${campaignId}/${requestToken}/store-metrics`, {data});
                    console.log(result)
                    if(result.status === "error") {
                        setError(errorBox, result.error.message);
                        return false;
                    }

                    ePopup("Metrics saved", result.message, 0, "success", "approve")
                    setTimeout(function (){
                        ePopup("", "", 1);
                        let domElement = $(document).find(`.media-item-container[data-media-id=${mediaId}]`).first();
                        if(domElement.length) {
                            let waterMark = $(
                                '<div class="media-action-overlay noSelect">' +
                                    '<div class="bg-success border-radius-50 square-30 flex-row-around flex-align-center">' +
                                        '<i class="mdi mdi-check color-white font-20"></i>' +
                                    '</div>' +
                                '</div>'
                            );
                            domElement.find(".media-item-inner-container").first().append(waterMark)
                            let statusElement = domElement.find(".attachment-status").first();
                            if(statusElement.length) {
                                statusElement.removeClass("color-red")
                                statusElement.addClass("color-green")
                                statusElement.text("Completed")
                            }
                        }
                        screenLoader.hide();
                        modalHandler.dispose();
                        domParent.find(".modal-trigger").each(function (){
                            $(this).removeClass("modal-trigger")
                            $(this).removeClass("cursor-pointer")
                        })
                        domParent.find(".media-stat-overlay").first().find("p").first().text("Attachment complete")
                    }, 2000);
                    return true;
                }


                const displayEvaluatedMetrics = async (data) => {
                    let metrics = data.metrics;
                    let metricTemplate = "";
                    metricContent.html("")

                    await get(`api/template/element/media-metric-element`)
                        .then(responseText => {
                            metricTemplate = responseText
                        })
                        .catch(error => {
                            let code = error.status, errorText = error.statusText;
                            console.error(`Build error while fetching template:: (${code}) ${errorText}`)
                            setError(errorBox, `Build error while fetching template:: (${code}) ${errorText}`)
                        })
                    if(empty(metricTemplate)) {
                        end();
                        return false;
                    }

                    for(let metric in metrics) {
                        let value = metrics[metric];
                        let tmp = (Handlebars.compile(metricTemplate))({
                            metricName: metric,
                            metricValue: value
                        });
                        metricContent.append(tmp)
                    }
                    screenLoader.hide();
                    fadeContainers(uploadContainer, metricListContainer)

                    btn.hide();
                    navBackButton.show();

                    navBackButton.off("click").on("click", function () {
                        fadeContainers(metricListContainer, uploadContainer)
                        navBackButton.hide();
                        btn.show();
                        metricContent.html("")
                        clearError(errorBox)
                    })
                    console.log(storeMetricsBtn.length)

                    storeMetricsBtn.off("click").on("click", function () {
                        storeEvaluatedMetrics(data.callbackToken)
                    })

                }



                const evaluateMetrics = async (items) => {
                    console.log(items)
                    let data = {
                        items: items.data,
                        media_id: mediaId,
                        callback_token: items['callback_token']
                    };
                    screenLoader.show("Evaluating metrics...")
                    let result = await post(`request-data/campaign/${campaignId}/${requestToken}/evaluate-metrics`, {data});
                    console.log(result)
                    if(result.status === "error") {
                        setError(errorBox, result.error.message);
                        return false;
                    }
                    await displayEvaluatedMetrics(result.data);
                }


                const handle = async (form) => {
                    if(applicationResponding) return;
                    applicationResponding = true;

                    let cbError = (result) => {
                        let msg;
                        applicationResponding = false;
                        if(typeof result === "object" && (("error" in result) && typeof result.error === "object")) msg = result.error.message
                        else if(typeof result === "object" && (("error" in result) && typeof result.error === "string")) msg = result.error
                        else if(typeof result === "string") msg = result;
                        else {
                            end()
                            return;
                        }
                        setError(errorBox, msg)
                    }
                    let cb = async () => {
                        cbError();
                        let data = new FormData(form.get(0))
                        data.append("media_id", mediaId)
                        let result = await post(`request-data/campaign/${campaignId}/${requestToken}/media`, data);
                        cbError(result);
                        if(result.status === "error") result.error = result.error.message
                        result.resultCallback = evaluateMetrics;
                        return result;
                    }


                    let swalData = {
                        callback: cb,
                        callbackError: cbError,
                        visualText: {
                            preFireText: {
                                title: `Attach screenshots?`,
                                text: "Please be absolutely sure that the screenshots contain the required metrics if they are available.",
                                confirmButtonText: "Yes, attach!",
                                icon: "warning"
                            },
                            successText: {
                                title: "Screenshots attached.",
                                text: "",
                                icon: "success",
                                html: ""
                            },
                            errorText: {
                                title: "Failed",
                                text: "Failed to attach screenshots. <_ERROR_MSG_>",
                                icon: "error",
                                html: ""
                            }
                        }
                    };

                    swalConfirmCancel(swalData);
                }

                await handle(form)
                end();
            }
        })


        let filesElement = modal.template.find("input[type=file]#customFiles").first();
        let metricContainer = modal.template.find("#metric-content").first();
        let previewContainer = modal.template.find(".vertical-file-preview-container > .img-modal-container");
        let modalPreErrorBox = modal.template.find(".modalErrorBox")
        metricContainer.html("")
        filesElement.val("");
        if(previewContainer.length) {
            previewContainer.each(function (){ $(this).remove(); })
        }
        if(modalPreErrorBox.length) {
            modalPreErrorBox.each(function (){
                $(this).addClass("d-none")
                $(this).text("")
            })
        }


        let imageContainerElement = modal.template.find(".main-media-modal-container").first();

        let isImage = domParent.find("img.media-item-media").length;
        let src = isImage ? domParent.find("img.media-item-media").first().attr("src") : domParent.find("video.media-item-media").first().find("source").first().attr("src")
        let mediaElement;
        if(isImage) {
            mediaElement = $(`<img src="${src}" class="rounded w-100 noSelect" />`)
        }
        else {
            mediaElement = $(`<video class="rounded w-100 noSelect" controls><source type="video/mp4" src="${src}"></video>`)
        }


        if(imageContainerElement.find("video, img").length) {
            imageContainerElement.find("video, img").each(function (){ $(this).remove() })
        }
        imageContainerElement.append(mediaElement)
        modal.open()





    }

    $(document).on("click", ".modal-trigger", function (){ triggerModal($(this)); });

}













window.mediaObservers = {};
// Function to create an observer for a specific container
async function createObserver(containerId, sentinelSelector, endpoint, templateName) {
    const mediaContainer = $(`#${containerId}`);
    const innerContent = mediaContainer.find(".inner-content").first();
    const loaderContainer = mediaContainer.find(".content-loader-container").first();
    if(empty(mediaContainer, innerContent, loaderContainer)) return;


    const sortContainerSelector = mediaContainer.attr('data-sort-target');
    const filterContainerSelector = mediaContainer.attr('data-filter-target');
    const searchContainerSelector = mediaContainer.attr('data-search-target');
    const currentViewSelector = mediaContainer.attr('data-current-view-target');
    const totalViewSelector = mediaContainer.attr('data-total-view-target');

    const sortOrderElement = empty(sortContainerSelector) ? null : $(document).find(sortContainerSelector).first();
    const filteringElement = empty(filterContainerSelector) ? null : $(document).find(filterContainerSelector);
    const searchElement = empty(searchContainerSelector) ? null : $(document).find(searchContainerSelector).first();
    const currentViewCountElement = empty(currentViewSelector) ? null : $(document).find(currentViewSelector).first();
    const totalViewCountElement = empty(totalViewSelector) ? null : $(document).find(totalViewSelector).first();

    let currentViewCount = 0;
    let next = true;
    let after = "";

    let isLoading = false;
    let containerVisible = false;
    let queryParams = {};

    const setQueryVariables = () => {
        let searchQ = !empty(searchElement) ? searchElement.val().trim() : null;
        let filter = [], orderBy = null, direction = null;

        if(!empty(sortOrderElement)) [orderBy, direction] = sortOrderElement.val().trim().split('|||', 2);
        if(!empty(filteringElement)) filteringElement.each(function () {
            filter.push($(this).val().trim())
        })

        queryParams = {
            sort_column: orderBy,
            sort_direction: direction,
            filtering: filter,
            q: searchQ,
            actor_id: ("actorId" in window) ? actorId : null,
            campaign_id: ("campaignId" in window) ? campaignId : null,
            after
        }
    }
    const clearContent = async () => {
        innerContent.empty();
        after = "";
        currentViewCount = 0;
        next = true;
        return await fetchMediaItems();
    }


    let templateSource = "";
    await get(`api/template/element/${templateName}`)
        .then(responseText => {
            templateSource = responseText
        })
        .catch(error => {
            let code = error.status, errorText = error.statusText;
            console.error(`Build error while fetching template:: (${code}) ${errorText}`)
        })
    if(empty(templateSource)) {
        screenLoader.hide();
        return;
    }
    let mediaTemplate = Handlebars.compile(templateSource);

    const setLoading = () => {
        isLoading = true;
        contentLoader.show(loaderContainer, "Loading items...")
    }
    const clearLoading = () => {
        isLoading = false;
        contentLoader.hide(loaderContainer)
    }

    function reattachSentinelObserver() {
        const sentinel = document.getElementById(sentinelSelector);
        if (sentinel) {
            sentinelObserver.observe(sentinel);
        }
    }

    // Function to fetch media items for this container
    async function fetchMediaItems() {
        if(!next) return;
        if (isLoading || !containerVisible) return;
        setLoading()

        try {
            setQueryVariables()
            const response = await get(`api/${endpoint}`, queryParams);
            if(response.status === "error") {
                next = false;
                clearLoading()
                return;
            }

            let pagination = response.data.pagination;
            let data = response.data.data;
            let metadata =('meta' in response.data) ?  response.data.meta : {};
            next = !empty(response.data.pagination) ? pagination.next : false
            after = !empty(response.data.pagination) ? pagination.after : ""

            currentViewCount += Object.keys(data).length
            if(currentViewCountElement.length) currentViewCountElement.text(currentViewCount)
            if(('total_count' in metadata) && totalViewCountElement.length) totalViewCountElement.text(metadata.total_count)

            if(empty(data)){
                next = false;
                clearLoading()
                return;
            }

            data.forEach(mediaItem => {
                if(isAssoc(mediaItem)) {
                    let item = {};
                    for(let key in mediaItem) item[key] = typeof mediaItem[key] === "object" ? JSON.stringify(mediaItem[key]) : mediaItem[key];
                    const html = mediaTemplate(item); // Compile Handlebars template with data
                    innerContent.get(0).insertAdjacentHTML("beforeend", html);
                }
                else { //Table groups
                    mediaItem.forEach(obj => {
                        let item = {};
                        for(let key in obj) item[key] = typeof obj[key] === "object" ? JSON.stringify(obj[key]) : obj[key];
                        const html = mediaTemplate(item); // Compile Handlebars template with data
                        innerContent.get(0).insertAdjacentHTML("beforeend", html);
                    })
                    innerContent.get(0).insertAdjacentHTML("beforeend", "<tr style='height: 75px;'></tr>");
                }

            });

            setTooltips();
            clearLoading()
            // reattachSentinelObserver();
        } catch (error) {
            console.error(`Error loading media for ${containerId}:`, error);
            clearLoading()
        }
    }

    // Observer to check if the media container is visible
    const containerObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            containerVisible = entry.isIntersecting;
            if (containerVisible) {
                fetchMediaItems(); // Start fetching when container is visible
            }
        });
    }, {
        root: null,
        threshold: 0.000000001 // Trigger when 10% of the container is visible
    });

    // Observer to load more content when near the bottom of the container
    const sentinelObserver = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && containerVisible) {
            fetchMediaItems(); // Trigger loading of next batch of media when near bottom and container is visible
        }
    }, {
        root: null,
        threshold: 0.1 // Trigger when 10% of the last element (sentinel) is visible
    });

    // Observe the media container and sentinel element
    containerObserver.observe(mediaContainer.get(0));
    const sentinel = document.getElementById(sentinelSelector);
    sentinelObserver.observe(sentinel);

    // Initial load of media
    fetchMediaItems();



    if(!empty(sortOrderElement)) {
        sortOrderElement.parents().first().on('click', '.list-item[data-input]', function () {
            let firstChild = $(this).find('div').first();
            if(firstChild.hasClass('selected')) return true;
            let input = $(this).attr('data-input');
            if(typeof input === 'undefined') return false;
            $(this).parents().first().find('.list-item[data-input]').each(function () {
                let firstElement = $(this).find('div').first();
                firstElement.removeClass('selected');
                firstElement.removeClass('list-style-selected');
            })
            firstChild.addClass('selected');
            firstChild.addClass('list-style-selected');
            sortOrderElement.val(input);
            clearContent()
        })
    }
    if(!empty(filteringElement)) {
        filteringElement.each(function () {
            let filterElement = $(this)
            let group = filterElement.attr('data-group');
            $(this).parents().first().on('click', `.list-item[data-input][data-group="${group}"]`, function () {
                let firstChild = $(this).find('div').first();
                if(firstChild.hasClass('selected')) return true;
                let input = $(this).attr('data-input');
                if(typeof input === 'undefined') return false;
                $(this).parents().first().find(`.list-item[data-input][data-group="${group}"]`).each(function () {
                    let firstElement = $(this).find('div').first();
                    firstElement.removeClass('selected');
                    firstElement.removeClass('list-style-selected');
                })
                firstChild.addClass('selected');
                firstChild.addClass('list-style-selected');
                filterElement.val(input);
                clearContent()
            })
        })
    }


    if(!empty(searchElement)) {
        let debounceTimer;
        searchElement.get(0).addEventListener('input', () => {
            clearTimeout(debounceTimer); // reset debounce timer

            if (empty(searchElement.get(0).value.trim()) && empty(queryParams.q)) return; // don't run for empty input

            debounceTimer = setTimeout(() => {
                // disable input while processing
                searchElement.get(0).disabled = true;

                clearContent()
                    .finally(() => {
                        // re-enable after both methods finish
                        searchElement.get(0).disabled = false;
                    });
            }, 500); // 0.5 seconds delay
        });
    }

    return {
        id: containerId,
        async reset() {
            return await clearContent(); // you already defined this
        },
        disconnect() {
            containerObserver.disconnect();
            sentinelObserver.disconnect();
        },
        async fetchNext() {
            return await fetchMediaItems();
        }
    };


} //end createObserver


async function initMediaObserver(id, sentinel, endpoint, tpl) {
    window.mediaObservers[id] =  await createObserver(id, sentinel, endpoint, tpl);
}


document.addEventListener("DOMContentLoaded", function() {
    let paginationContainers = $(document).find(".pagination-container");
    if(paginationContainers.length) {
        paginationContainers.each(async function () {
            let paginationContainer = $(this);
            let id = paginationContainer.attr("id");
            let sentinel = paginationContainer.attr("data-sentinel");
            let endpoint = paginationContainer.attr("data-endpoint");
            let templateName = paginationContainer.attr("data-template");

            await initMediaObserver(id, sentinel, endpoint,templateName);
        })
    }
});




const buildSummaryContent = (result) => {
    let wrapper = $(document).find("#summary-wrapper").first(), container = wrapper.find("#summary-container").first();
    if(empty(wrapper, container)) return;

    console.log(result)
    container.html("");
    if(result.status === "error") {
        container.hide();
        return;
    }


    const formatSummaryItem = (item) => {
        if (!item || typeof item !== 'object') return '';

        let res = '<p>';

        if ('title' in item) {
            res += "<span class='section-title'>" + cleanTitle(item.title) + "</span>";
        }

        if ('text' in item) {
            res += ucFirst(item.text);
        }

        res += '</p>';

        const pointerKeys = ['strengths', 'areas_for_improvements'];

        pointerKeys.forEach(function(pointerKey) {
            if (!(pointerKey in item)) return;

            res += "<p class='mt-2'>";
            res += "<span class='section-title'>" + cleanTitle(pointerKey) + "</span>";
            res += '</p>';
            res += '<ul>';

            item[pointerKey].forEach(function(subItem) {
                if ('text' in subItem) {
                    res += '<li>' + subItem.text + '</li>';
                }
            });

            res += '</ul>';
        });

        return res;
    }

    const processContent = (content) => {
        if(typeof content !== "object") return;
        if(Object.keys(content).length === 1 && ("content" in content)) content = content.content;
        if(typeof content === "string") {
            container.html(content)
            return;
        }
        const pointerKeys = ['strengths', 'areas_for_improvements'];

        Object.keys(content).forEach(function(headline) {
            if (pointerKeys.includes(headline)) return;

            let data = content[headline];
            let output = `<div class="summary-card">`;
            output += '<h4>' + cleanUcAll(headline) + '</h4>';

            if (isAssoc(data)) {
                if (headline === 'campaign_analysis') {
                    pointerKeys.forEach(function(pointerKey) {
                        if (!(pointerKey in data) && (pointerKey in content)) {
                            data[pointerKey] = content[pointerKey];
                        }
                    });
                }
                output += formatSummaryItem(data);
            } else {
                data.forEach(function(item) {
                    if (isAssoc(item)) {
                        output += formatSummaryItem(item);
                    }
                });
            }
            output += `</div>`;

            container.append(output); // Append to a div with id 'summary'
        });
    }


    processContent(result.data)
    container.show();
}

async function loadCampaignSummary() {
    let result = await get(`api/campaign/${campaignId}/summary`);
    if(result.status === "error") return;
    buildSummaryContent(result);
}


async function generateCampaignSummary() {
    if(applicationResponding) return;
    applicationResponding = true;

    let cbError = () => {
        applicationResponding = false;
        screenLoader.hide()
    }
    let cb = async () => {
        screenLoader.show("Generating summary...")
        let result = await post(`api/campaign/${campaignId}/summary`);
        if(result.status === "error") result.swalOptions = {errorText: result.error.message}
        buildSummaryContent(result);
        cbError();
        return result;
    }





    let swalData = {
        callback: cb,
        callbackError: cbError,
        visualText: {
            preFireText: {
                title: `Generate new campaign summary?`,
                text: "A new summary will overwrite the existing one. The summary is generated by AI and is therefor not necessarily always identical.",
                confirmButtonText: "Generate",
                icon: "warning"
            },
            successText: {
                title: "Summary generated",
                text: "",
                icon: "success",
                html: ""
            },
            errorText: {
                title: "Failed",
                text: "Failed to generate summary. <_ERROR_MSG_>",
                icon: "error",
                html: ""
            }
        }
    };


    swalConfirmCancel(swalData);
}










function customSortingTable(tableId) {
    function sortTable(columnIndex, direction) {
        const table = document.getElementById(tableId);
        const rows = Array.from(table.querySelectorAll('tbody tr'));

        // Determine the sort type (numeric or alphabetic) based on "data-sort"
        const isNumeric = (cell) => !isNaN(parseFloat(cell.dataset.sort));

        rows.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIndex];
            const cellB = rowB.cells[columnIndex];

            const valueA = cellA.dataset.sort || cellA.textContent.trim();
            const valueB = cellB.dataset.sort || cellB.textContent.trim();

            if (isNumeric(cellA) && isNumeric(cellB)) {
                // Numeric sort
                return direction === 'asc' ? valueA - valueB : valueB - valueA;
            } else {
                // Alphabetic sort
                return direction === 'asc'
                    ? valueA.localeCompare(valueB)
                    : valueB.localeCompare(valueA);
            }
        });

        // Re-append sorted rows to the table body
        const tbody = table.querySelector('tbody');
        rows.forEach(row => tbody.appendChild(row));
    }

    const tableHeaders = document.querySelectorAll(`#${tableId} thead th:not(th.unsortable)`);
    let headerToInit = null;
    let indexToInit = null;


    tableHeaders.forEach((header, index) => {
        let jqHeader = $(header);
        if(!jqHeader.find(".mdi:not(.mdi-information)").length) {
            let mdi = $('<i class="mdi mdi-sort"></i>');
            if(jqHeader.hasClass("sort-active")) {
                let currentDirection = jqHeader.attr("data-sortDirection") === 'asc' ? 'desc' : 'asc';
                mdi.removeClass("mdi-sort")
                mdi.addClass(currentDirection === "asc" ? "mdi-sort-ascending" : "mdi-sort-descending")
            }

            if(jqHeader.find('.mdi.mdi-information').length)
                mdi.insertBefore(jqHeader.find('.mdi.mdi-information').first())
            else jqHeader.append(mdi);
        }

        if(jqHeader.hasClass("sort-active")) {
            headerToInit = jqHeader;
            indexToInit = index;
        }
    });



    tableHeaders.forEach((header, index) => {
        let jqHeader = $(header);
        header.querySelector(".mdi:not(.mdi-information)").addEventListener('click', () => {
            const currentDirection = header.dataset.sortDirection === 'asc' ? 'desc' : 'asc';
            header.dataset.sortDirection = currentDirection;

            let mdi = jqHeader.find(".mdi:not(.mdi-information)").first();
            $(tableHeaders).each(function () {
                $(this).find(".mdi:not(.mdi-information)").first();
                mdi.removeClass("mdi-sort-descending")
                mdi.removeClass("mdi-sort-ascending")
                mdi.addClass("mdi-sort")
                $(this).removeClass("sort-active")
            })

            jqHeader.addClass("sort-active");
            mdi.removeClass("mdi-sort")
            mdi.addClass(currentDirection === "asc" ? "mdi-sort-ascending" : "mdi-sort-descending")

            sortTable(index, currentDirection);
        });
    });

    if(headerToInit !== null) sortTable(indexToInit, headerToInit.attr("data-sortDirection"));

}




function campaignCostEfficiencyChart(chartElement) {
    if(!('campaignCreators') in window || empty(campaignCreators)) {
        setLineChartNoData(chartElement)
        return;
    }
    const series = [];
    let maxY = 0, maxX = 0;
    let estimated = false;
    for(let n in campaignCreators) {
        let creator = campaignCreators[n];
        if(creator.realised_impressions === 0) continue;
        series.push({
            name: creator.username,
            data: [{ x: creator.cpm, y: creator.realised_reach, z: creator.realised_impressions }]
        })
        if(creator.cpm > maxX) maxX = creator.cpm;
        if(creator.realised_reach > maxY) maxY = creator.realised_reach;
    }

    if(empty(series)) {
        for(let n in campaignCreators) {
            let creator = campaignCreators[n];
            if(creator.total_estimated_impressions === 0) continue;
            series.push({
                name: creator.username,
                data: [{ x: creator.cpm, y: creator.total_estimated_reach, z: creator.total_estimated_impressions }]
            })
            if(creator.estimated_cpm > maxX) maxX = creator.estimated_cpm;
            if(creator.total_estimated_reach > maxY) maxY = creator.total_estimated_reach;
        }
        estimated = true;
    }


    let curr = 'campaignCurrencySymbol' in window ? campaignCurrencySymbol : '€';
    let opt = {
        series,maxY,maxX,
        x_title: (estimated ? 'Est. ' : 'Realized ') + `CPM (${curr})`,
        y_title: (estimated ? 'Est. ' : '') + 'Reach',
        title: (estimated ? 'Est. ' : '') + "Cost efficiency",
        y_tooltip: { formatter: (val) => `${shortNumbByT(val, true, true)}${estimated ? ' Estimated' : ''} reach` },
        z_tooltip: { formatter: (val) => `${shortNumbByT(val, true, true)}${estimated ? ' Estimated' : ''} impressions` },
        x_tooltip: { formatter: (val) => `${curr}${shortNumbByT(val, true, true)}${estimated ? ' Estimated ' : ''} CPM` },
    }
    renderCharts(chartElement.get(0), opt, "bubble");
}


function campaignImpressionsByContentType(chartElement) {
    if(!('averageImpressionsByContent') in window || empty(averageImpressionsByContent)) return;
    const series = Object.values(averageImpressionsByContent);
    const labels = Object.keys(averageImpressionsByContent);
    if(series.reduce(function (initial, val) { return initial + val; }, 0) === 0) {
        setLineChartNoData(chartElement)
        return;
    }

    let opt = {
        series,
        labels,
        height: 350,
        title: "Avg. impressions by content",
        y_tooltip: { formatter: (val) => `${shortNumbByT(val, true, true)} Avg. impressions` },
    }
    renderCharts(chartElement.get(0), opt, "pie");
}


function creatorImpressionDistribution(chartElement) {
    if(!('creatorImpressionsDistribution') in window || empty(creatorImpressionsDistribution)) return;
    const series = Object.values(creatorImpressionsDistribution);
    const labels = Object.keys(creatorImpressionsDistribution);
    if(series.reduce(function (initial, val) { return initial + val; }, 0) === 0) {
        setLineChartNoData(chartElement)
        return;
    }

    let opt = {
        series,
        labels,
        height: 350,
        title: "Creator impression - Known/Unknown (Estimated)",
        y_tooltip: { formatter: (val) => `${shortNumbByT(val, true, true)} Est. impressions` },
    }
    renderCharts(chartElement.get(0), opt, "pie");
}



if($(document).find("#cost_efficiency_chart").length)
    campaignCostEfficiencyChart($(document).find("#cost_efficiency_chart"));
if($(document).find("#impressions_by_content_type").length)
    campaignImpressionsByContentType($(document).find("#impressions_by_content_type"));
if($(document).find("#creator_impressions_distribution").length)
    creatorImpressionDistribution($(document).find("#creator_impressions_distribution"));
$(document).find("#switch-chart-view").each(function () {
    let selectEl = $(this);
    let chartIds = [];
    selectEl.find("option").each(function () { chartIds.push($(this).val()) })
    if(empty(chartIds)) return;

    selectEl.on("change", function () {
        let showId = $(this).val()
        for(let chartId of chartIds) {
            let el = $(document).find(`#${chartId}`).first()
            if(!el.length) return;

            if(chartId === showId) el.show();
            else el.hide();
        }
    })
})








async function updatePayoutInformation(btn) {
    screenLoader.show("Validating...");
    let form = btn.parents('#payout_info_form').first();
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


    let data = new FormData(form.get(0))
    screenLoader.update("Updating payout information...")
    let result = await post("api/user/settings/payout_bank_info", data);
    console.log(result);

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



async function updateBillingAddress(btn) {
    screenLoader.show("Validating...");
    let form = btn.parents('#billing_address_form').first();
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


    let data = new FormData(form.get(0))
    screenLoader.update("Updating billing information...")
    let result = await post("api/user/settings", data);
    console.log(result);

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





async function arbitraryFormSubmit(form) {
    let errorField = form.find(".error-field").first();

    const clearLoading = () => {
        applicationResponding = false;
        screenLoader.hide()
    }
    const setError = (msg, blameField = null) => {
        if(errorField.length) {
            errorField.text(msg);
            errorField.show();
        }
        if(blameField) {
            let field = form.find(`[name=${blameField}]`).first();
            if(field.length) {
                field.addClass('error-shadow');
            }
        }
        clearLoading()
    }
    const clearError = () => {
        let blameFields = form.find('.error-shadow');
        if(blameFields.length) {
            blameFields.each(function () { $(this).removeClass('error-shadow'); })
        }
        if(errorField.length) {
            errorField.text('')
            errorField.hide();
        }
    }
    const setLoading = () => {
        applicationResponding = true;
        clearError();
        screenLoader.show("")
    }


    setLoading();
    let data = new FormData(form.get(0));
    let path = form.attr('action');
    let result = await post(`api/forms/${path}`, data);

    if(result.status === "error") {
        let bf = ('blame_field' in result.error) ? result.error.blame_field : null;
        setError(result.error.message, bf)
        return;
    }


    //set cool new msg!


    clearLoading();
    if(('redirect' in result.data) && result.data.redirect) {
        let redirectUrl = ('redirect_uri' in result.data) ? result.data.redirect_uri : window.location.href;
        setTimeout(function () { window.location = redirectUrl; }, 1500)
    }
}

$(document).on("submit", '.arbitrary-form', function (e) {
    e.preventDefault();
    arbitraryFormSubmit($(this));
})


$(document).find("table.custom-sorting-table").each(function (){ customSortingTable($(this).attr("id")) })



function switchPricingIntervalView(btn) {
    let interval = btn.attr("data-price-interval");
    if(!['monthly', 'quarterly', 'biannual', 'yearly'].includes(interval)) return;
    if(!'pricingList' in window) return;

    let activeBtnClass = "bg-wrapper-hover";
    let inactiveBtnClass = "bg-wrapper-sibling";
    if(btn.hasClass(activeBtnClass)) return;


    for(let productId in pricingList) {
        let intervals = pricingList[productId];
        if(!(interval in intervals)) continue;
        let item = intervals[interval];

        let container = $(document).find(`.pricing-card#tier_${productId}`).first();
        if(!container.length) continue;

        let currencyElement = container.find(`[data-id=interval]`).first();
        if(currencyElement.length) currencyElement.text(item.currency_symbol)
        let amountElement = container.find(`[data-id=amount]`).first();
        if(amountElement.length) amountElement.text(item.amount)
        let intervalElement = container.find(`[data-id=interval]`).first();
        if(intervalElement.length) intervalElement.text(item.interval)
        let saveElement = container.find(`[data-id=save]`).first();
        if(saveElement.length) {
            saveElement.text(item.save_percentage)
            if(item.save_percentage > 0) saveElement.parents().first().show()
            else saveElement.parents().first().hide()
        }
    }

    $(document).find(".switch-interval").each(function () {
        let btnInterval = $(this).attr("data-price-interval");
        if(btnInterval === interval) $(this).addClass(activeBtnClass).removeClass(inactiveBtnClass)
        else $(this).addClass(inactiveBtnClass).removeClass(activeBtnClass)
    })
}
$(document).on("click", ".switch-interval", function () { switchPricingIntervalView($(this)) })



















