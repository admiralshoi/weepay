$(document).ready(function (){

    if(typeof pageTitle !== "undefined") document.querySelector("title").innerText = pageTitle;

    if(typeof setDateRangePicker == "function") {
        setDateRangePicker().then(() => {

        })
    }


    if(typeof hasUserSession == "function") hasUserSession();
    setActiveNavItemLabels();


    if($(document).find(".upload-cover-thumb").length) {
        $(document).find(".upload-cover-thumb").each(function () {
            initFilepondElement($(this));
        })
    }


    $(document).on("click", ".application-action a[data-action]",function () {brandRespondToApplication($(this)) });
    $(document).on("click", "button[name=campaign-apply-now]",function () {applyToCampaign($(this)) });
    $(document).on("click", "input[name=toggle-open-campaign]",function () {toggleOpenCampaign($(this)) });
    $(document).on("click", "button[name=save_campaign_details]",function () {setOpenCampaignDetails($(this)) });
    $(document).on("click", "button[name=edit-brand-profile]",function () {editBrandProfile($(this)) });
    $(document).on("click", "button[name=user-settings-update-btn]",function (e) { e.preventDefault(); updateUserSettings($(this)) });
    $(document).on("click", "button[name=set_new_cookie]",function (e) { e.preventDefault(); setNewCookie($(this)); });

    $(document).on("click", "button[name=user_reset_pwd]",function (e) { e.preventDefault(); resetPwd($(this)); });
    $(document).on("click", "button[name=user_create_new_password]",function (e) { e.preventDefault(); createNewPwdFromReset($(this)); });
    $(document).on("click", ".toggleUserSuspension",function () { toggleUserSuspension($(this)); });
    $(document).on("click", ".close-popup",function () { closePopup($(this)); });
    $(document).on("click", ".selectable-el",function () { selectElements($(this), false, true); });


    $(document).on("change", "form#view_users select[name=view]",function () { window.location = $(this).val() });
    $(document).on("click", "button[name=create_new_user_third_party]",function () { createUserThirdParty($(this)) });
    $(document).on("click", "button[name=signup_user]",function () { createUser($(this)) });
    $(document).on("click", "button[name=signup_affiliate]",function () { createAffiliateUser($(this)) });

    $(document).on("change", "select[name=bulk_action]",function () { bulkAction($(this)); });
    $(document).on("click", "input[type=checkbox].masterBox",function () { multiCheckBoxes($(this)); });
    $(document).on("click", "[data-href]",function () { window.location = $(this).attr("data-href"); });


    $(document).on("click", "#save_billing_address",function () {updateBillingAddress($(this)); });
    $(document).on("click", "#save_payout_info",function () {updatePayoutInformation($(this)); });
    $(document).on("click", ".toggleIntegrationActive",function () {toggleIntegrationDefault($(this)); });
    $(document).on("click", ".removeIntegration",function () {removeIntegration($(this)); });



    //Goodbrandslove

    $(document).on("click", ".campaign-csv-export",function () { exportCampaignToCsv() });
    if($(document).find(".drawChart").length) {
        $(document).find(".drawChart").each(function () { initChartDraw($(this)); })
    }

    if($(document).find("#campaign_edit_container").length) {
        populateCampaignDetails();
    }

    $(document).on("click", ".mass-action-btn",function () { massCheckAction($(this)) });
    $(document).on("click", "[name=creatorless_campaign_edit], [name=event_mode_edit],[name=creatorless_campaign], [name=event_mode]",function () { campaignSettingContradictions($(this)) });


    $(document).on("click", ".accordion .card-header",function (e) {
        if(e.target.tagName.toLowerCase() === "button") return;
        let btn = $(this).find('button').first()
        if(btn.length) btn.trigger('click')
    });

    if($(document).find(".object-action-expand-btn").length) objectActionContentToggle();
    if($(document).find("#summary-container").length) loadCampaignSummary();
    $(document).on("click", "#generate-campaign-summary",function () { generateCampaignSummary() });
    $(document).on("click", "#edit_campaign_btn, #campaign-public-settings",function () { openCampaignSettings($(this)) });
    $(document).on("click", "#request_third_party_metrics",function () { requestThirdPartyCampaignMetrics($(this)) });
    $(document).on("click", "button[name=campaign_creator_media_btn]",function () { loadCampaignCreatorMedia($(this)) });
    $(document).on("click", "button[name=request_third_party_integration]",function () { requestThirdPartyIntegration($(this)) });
    $(document).on("click", "[data-toggle-creator]",function () { toggleCreator($(this)) });
    $(document).on("click", "button[name=update_campaign_btn]",function () { campaignUpdate($(this)) });
    // $(document).on("click", "button[name=create_campaign]",function () { createCampaign($(this)) });
    // $(document).on("click", "button[name=toggle_campaign_creation_view]",function () { toggleCampaignCreationContainer() });
    $(document).on("change", "select[name=creator_campaign_action]",function () { campaignCreatorAction($(this)) });
    $(document).on("change", "select[name=table_actions]",function () { tableActions($(this)) });
    if($(document).find("table#live_mention_table").length)
        mentionLiveTableTracking($(document).find("table#live_mention_table").first())
            .then(() => { window.setInterval(function () { mentionLiveTableTracking($(document).find("table#live_mention_table").first()) }, 15000); })


    if($(document).find("[data-page=data-collection-campaign-metrics]").length) campaignMediaAttachment();

    if($(document).find("select#campaignSummarySortingMethod").length) {
        $(document).find("select#campaignSummarySortingMethod").first().on("change", function () {
            let value = $(this).val().trim();
            if(!empty(value)) window.location = sortNavigation + value;
        })
    }












    if($(document).find(".select2Multi").length) { select2MultiInit(); }
    if($(document).find(".select2-single").length) { select2SingleInit(); }


    if($(document).find(".plainDataTable").length) {
        if(typeof setDataTable == "function") {
            $(document).find(".plainDataTable").each(function () {
                let table = $(this), paginationLimit = table.data("pagination-limit"), sortingColumn = table.data("sorting-col"), sortingOrder = table.data("sorting-order");

                if(paginationLimit === undefined || empty(paginationLimit)) paginationLimit = 100;
                if(sortingColumn === undefined || empty(sortingColumn)) sortingColumn = 0;
                if(sortingOrder === undefined || empty(sortingOrder)) sortingOrder = "desc";

                setDataTable(table, [sortingColumn, sortingOrder], false,[], paginationLimit);
            });
        }
    }



    $(document).on("click", ".title-box .title-box-header",function () {
        let header = $(this), titleBoxContent = header.parent().find(".title-box-content").first();
        if(header.find(".expand-title-box").length) return true;

        if(titleBoxContent.length === 0) return false;
        let open = header.hasClass("open");
        if(open) titleBoxContent.slideUp( 250, function() { header.removeClass("open"); });
        else titleBoxContent.css('opacity', 0).slideDown(250).animate({ opacity: 1 },{ queue: false, duration: 250,
            complete: function () { header.addClass("open"); }});
    });


    $(document).on("click", ".title-box .title-box-header .expand-title-box",function () {
        let expandLink = $(this), header = expandLink.parents(".title-box-header").first(),
            titleBoxContent = header.parent().find(".title-box-content").first();
        if(titleBoxContent.length === 0) return false;
        let open = header.hasClass("open");
        if(open) {
            titleBoxContent.slideUp(250, function () {
                header.removeClass("open");
            });
            expandLink.text("Expand");
        }
        else {
            titleBoxContent.css('opacity', 0).slideDown(250).animate({opacity: 1}, {
                queue: false, duration: 250,
                complete: function () {
                    header.addClass("open");
                }
            });
            expandLink.text("Close");
        }
    });



    $(document).on("click",".copyBtn",function () {
        let targetElement = $(this).parents(".copyContainer").first().find(".copyElement");
        let copyString;
        if(!targetElement.length) return;
        if(targetElement.length > 1) {
            let targetSelector = $(this).attr("data-copy-target");
            if(empty(targetSelector)) return;
            targetElement = $(document).find(targetSelector);
            if(targetElement.length !== 1) return;
        }

        copyString = targetElement.first().text()
        copyString = copyString.replaceAll("&amp;","&");
        copyString = copyString.replaceAll("&lt;","<");
        copyString = copyString.replaceAll("&gt;",">");
        copyString = copyString.replaceAll("&quot;",'"');
        copyString = copyString.replaceAll("&apos;","'");
        copyString = copyString.toString();

        copyToClipboard(copyString);
    });





    $(document).on("click", ".collapse-btn", function () {
        let parent = $(this).parents(".collapse-parent").first();
        let container = parent.find(".collapse-content").first();
        if(empty(parent, container)) return;
        if(parent.hasClass("collapse-show")) parent.removeClass("collapse-show")
        else parent.addClass("collapse-show")
    })




});



docReady(function() {
    //activePage is set in index.php
    if(activePage.length) trackEvent("page_view", activePage)

    let eventTypes = {
        click: {dataAttribute: "data-clickAction", selector: "[data-clickAction]"},
        change: {dataAttribute: "data-changeAction", selector: "[data-changeAction]"}
    }

    for(let event in eventTypes) {
        let eventOpt = eventTypes[event]
        $(document).on(event,eventOpt.selector, function () {
            trackEvent(event, $(this).attr(eventOpt.dataAttribute))
        })
    }


    if($(document).find("button[name=login_user]").length) {
        document.addEventListener("keypress", function (event) {
            if (event.key === "Enter") $(document).find("button[name=login_user]").first().trigger("click");
        });
    }


    $(document).off("dblclick");



    /**
     * Features for responsiveness
     */
    if('doAuthenticate' in window) doAuthenticate();
    togglePasswordVisibility();

    $(document).on("click", "#leftSidebarOpenBtn", function () {
        if($(document).find("#sidebar").length) {
            $(document).find("#sidebar").addClass("mb-open");
            $(document).find(".page-wrapper").first().addClass("overlay-blur-dark-small-screen");
        }
        else if($(document).find("#sidebar-admin-panel").length) {
            $(document).find("#sidebar-admin-panel").addClass("mb-open");
            $(document).find(".page-wrapper").first().addClass("overlay-blur-dark-small-screen");
        }
    })
    $(document).on("click", "#leftSidebarCloseBtn", function () {
        if($(document).find("#sidebar").length) {
            $(document).find("#sidebar").removeClass("mb-open");
            $(document).find(".page-wrapper").first().removeClass("overlay-blur-dark-small-screen");
        }
        else if($(document).find("#sidebar-admin-panel").length) {
            $(document).find("#sidebar-admin-panel").removeClass("mb-open");
            $(document).find(".page-wrapper").first().removeClass("overlay-blur-dark-small-screen");
        }
    })

    selectV2();
    customTableSearch();


})



window.addEventListener("load", function() {
    for (let i = 0; i < document.images.length; i++) {
        const img = document.images[i];

        img.addEventListener('error', () => replaceBadImage(img));
        img.addEventListener('load', () => {

            if (!IsImageOk(img)) {
                replaceBadImage(img);
            }
        });

        if (!IsImageOk(img)) {
            replaceBadImage(img);
        }
    }
});







document.addEventListener('input', function (event) {
    if (event.target.classList.contains('amount-input')) {
        formatCurrencyInput(event.target);
    }
});







