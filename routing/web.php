<?php

use classes\enumerations\Links;
use features\Settings;
use routing\Routes;

require_once ROOT . "features/init.php";
require_once ROOT . "routing/middleware/middleware.php";







/**
 *  =========================================
 *  =========== OPEN PAGES START ============
 *  =========================================
 */
Routes::get(Links::$app->home, "LandingController::home");
Routes::get(Links::$app->auth->consumerLogin, "auth.PageController::consumerDashboardLogin");
Routes::get(Links::$app->auth->consumerSignup, "auth.PageController::consumerDashboardSignup");
Routes::get(Links::$app->auth->merchantLogin, "auth.PageController::merchantDashboardLogin");
Routes::get(Links::$app->auth->merchantSignup, "auth.PageController::merchantDashboardSignup");
Routes::get(Links::$app->auth->adminLogin, "auth.PageController::adminDashboardLogin"); // Unlisted - not linked anywhere
Routes::get("qr", "GeneralController::generateQr");
/**
 *  =========================================
 *  ============= OPEN PAGES END ============
 *  =========================================
 */


/**
 *  =========================================
 *  ========= CONSUMER AUTH START ===========
 *  =========================================
 */
Routes::group(['requiresLogin', 'consumer'], function() {
    Routes::get(Links::$app->auth->consumerSignup . '/complete-profile', "auth.PageController::consumerCompleteProfile");
    Routes::get(Links::$consumer->dashboard, "consumer.PageController::dashboard", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->orders, "consumer.PageController::orders", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->orderDetail . '/{id}', "consumer.PageController::orderDetail", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->payments, "consumer.PageController::payments", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->changeCard, "consumer.PageController::changeCard", ['consumerProfileComplete']);
    Routes::get("payments/{id}", "consumer.PageController::paymentDetail", ['consumerProfileComplete']);
    Routes::get("location/{id}", "consumer.PageController::locationDetail", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->settings, "consumer.PageController::settings", ['consumerProfileComplete']);
    Routes::get(Links::$consumer->support, "consumer.PageController::support", ['consumerProfileComplete']);

    // Consumer API routes
    Routes::post(Links::$api->consumer->orders, "consumer.ApiController::getOrders", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->payments, "consumer.ApiController::getPayments", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->paymentsByCard, "consumer.ApiController::getPaymentsByCard", ['consumerProfileComplete']);

    // Consumer payment actions
    Routes::post(Links::$api->consumer->payNow, "consumer.ApiController::payNow", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->payOrderOutstanding, "consumer.ApiController::payOrderOutstanding", ['consumerProfileComplete']);
    Routes::get("api/consumer/payments/{id}/receipt", "consumer.ApiController::downloadReceipt", ['consumerProfileComplete']);
    // More specific routes must come before less specific ones
    Routes::post("api/consumer/change-card/payment-method/{paymentMethodUid}", "consumer.ApiController::initiatePaymentMethodCardChange", ['consumerProfileComplete']);
    Routes::post("api/consumer/change-card/order/{orderUid}", "consumer.ApiController::initiateOrderCardChange", ['consumerProfileComplete']);
    Routes::post("api/consumer/change-card", "consumer.ApiController::initiateCardChange", ['consumerProfileComplete']);

    // Card change callback (page route, not API)
    Routes::get("consumer/card-change/callback", "consumer.PageController::cardChangeCallback", ['consumerProfileComplete']);

    // Consumer support routes
    Routes::post(Links::$api->consumer->support->create, "consumer.ApiController::supportCreate", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->support->reply, "consumer.ApiController::supportReply", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->support->close, "consumer.ApiController::supportClose", ['consumerProfileComplete']);
    Routes::post(Links::$api->consumer->support->reopen, "consumer.ApiController::supportReopen", ['consumerProfileComplete']);
});
/**
 *  =========================================
 *  ========== CONSUMER AUTH END ============
 *  =========================================
 */



/**
 *  =========================================
 *  =========== OIDC PAGES START ============
 *  =========================================
 */
Routes::group([], function() {
    Routes::get("auth/user/verify/{status}", "verification.MitIdController::callbackRouter");
    Routes::get(Links::$app->auth->oicd->preAuthUrl, "verification.OidcController::preAuthPage");
});
/**
 * =========================================
 * ============ OIDC PAGES END =============
 * =========================================
 */


/**
 *  =========================================
 *  ============ OIDC API START =============
 *  =========================================
 */

Routes::group(['api'], function() {
    Routes::get(Links::$api->oidc->sessionPolling, "verification.OidcController::sessionPolling");
});
/**
 * =========================================
 * ============= OIDC API END ==============
 * =========================================
 */

/**
 * =========================================
 * ========= COOKIE CONSENT API ===========
 * =========================================
 */
Routes::group(['api'], function() {
    Routes::post("api/cookies/accept", "api.CookieController::accept");
});


/**
 *  =========================================
 *  ====== CUSTOMER PURCHASE FLOW START =====
 *  =========================================
 */
Routes::group([], function() {
    Routes::get(Links::$merchant->public->locationPage, "flows.purchase.CustomerPageController::home");
    Routes::get("merchant/{slug}/checkout", "flows.purchase.CustomerPageController::start", ['notMerchant', 'notAdmin']);
    Routes::get("merchant/{slug}/checkout/info", "flows.purchase.CustomerPageController::info", ['notMerchant', 'notAdmin']);
    Routes::get(Links::$merchant->terminals->consumerChoosePlan, "flows.purchase.CustomerPageController::choosePlan", ['notMerchant', 'notAdmin']);
    Routes::get(Links::$checkout->merchantCallbackPathSuccess, "flows.purchase.CustomerPageController::handlePaymentCallback");
    Routes::get(Links::$checkout->orderConfirmation, "flows.purchase.CustomerPageController::orderConfirmation", ['consumer']);
});


/**
 * =========================================
 * ======= CUSTOMER PURCHASE FLOW END ======
 * =========================================
 */


/**
 *  =========================================
 *  ========= API LOGGED OUT START ==========
 *  =========================================
 */
Routes::group(['api', "requiresApiLogout"], function() {
    Routes::post(Links::$api->forms->contactForm, "api.FormController::publicContactForm");


    /**
     *  =========================================
     *  ============ AUTH API START =============
     *  =========================================
     */
    Routes::post("api/password-recovery", "api.AuthController::passwordRecovery");
    Routes::post("api/password-recovery/reset", "api.AuthController::passwordRecoveryResetPassword");
    Routes::post("api/create-user/{account_type}", "api.AuthController::createUser");
    Routes::post(Links::$api->auth->merchantLogin, "auth.ApiController::loginUser");
    Routes::post(Links::$api->auth->merchantSignup, "auth.ApiController::signupUser");
    Routes::post(Links::$api->auth->consumerLogin, "auth.ApiController::loginUser");
    Routes::post(Links::$api->auth->adminLogin, "auth.ApiController::loginUser");
    Routes::post(Links::$api->auth->verify2faLogin, "auth.ApiController::verify2faLogin");
    Routes::post(Links::$api->auth->resend2faLoginCode, "auth.ApiController::resend2faLoginCode");
    /**
     *  =========================================
     *  ============= AUTH API END ==============
     *  =========================================
     */

});
/**
 *  =========================================
 *  ========== API LOGGED OUT END ===========
 *  =========================================
 */



/**
 *  =========================================
 *  ========= REQUIRES LOGIN START ==========
 *  =========================================
 */
Routes::group(['api','requiresApiLogin'], function() {
    // Password change API - available to all logged-in users
    Routes::post(Links::$api->auth->changePassword, "auth.ApiController::changePassword");

    // Bell notifications API - available to all logged-in users (admin, merchant, consumer)
    Routes::post(Links::$api->user->notificationsList, "UserApiController::notificationsList");
    Routes::post(Links::$api->user->notificationsMarkRead, "UserApiController::notificationsMarkRead");
    Routes::post(Links::$api->user->notificationsMarkAllRead, "UserApiController::notificationsMarkAllRead");
    Routes::get(Links::$api->user->notificationsUnreadCount, "UserApiController::notificationsUnreadCount");

    // Common user settings API (for both merchants and consumers)
    Routes::group(['merchantOrConsumer'], function() {
        Routes::post(Links::$api->user->updateProfile, "UserApiController::updateProfile");
        Routes::post(Links::$api->user->updateAddress, "UserApiController::updateAddress");
        Routes::post(Links::$api->user->updatePassword, "UserApiController::updatePassword");
        Routes::post(Links::$api->user->updateUsername, "UserApiController::updateUsername");
        Routes::post(Links::$api->user->updateTwoFactor, "UserApiController::updateTwoFactor");
        Routes::post(Links::$api->user->verifyPhone, "UserApiController::verifyPhone");
    });

    Routes::group(['consumer'], function() {
        Routes::post(Links::$api->auth->consumerSendVerificationCode, "auth.ApiController::sendVerificationCode");
        Routes::post(Links::$api->auth->consumerVerifyCode, "auth.ApiController::verifyCode");
        Routes::post(Links::$api->auth->consumerCheckPhoneVerification, "auth.ApiController::checkPhoneVerification");
        Routes::post(Links::$api->auth->consumerUpdateProfile, "auth.ApiController::updateConsumerProfile");
    });

    Routes::group(['merchant'], function() {
        Routes::get(Links::$api->organisation->vivaConnectedAccount, "merchants.ApiController::vivaWalletStatus");
        Routes::post(Links::$api->organisation->vivaConnectedAccount, "merchants.ApiController::createVivaConnectedAccount");
        Routes::post(Links::$api->organisation->updateWhitelistEnabled, "merchants.ApiController::updateWhitelistEnabled");
        Routes::post(Links::$api->organisation->addWhitelistIp, "merchants.ApiController::addWhitelistIp");
        Routes::post(Links::$api->organisation->removeWhitelistIp, "merchants.ApiController::removeWhitelistIp");
        Routes::post(Links::$api->organisation->updateSettings, "merchants.ApiController::updateOrgSettings");
        Routes::post(Links::$api->organisation->team->update, "merchants.OrganisationApiController::updateTeamMember");
        Routes::post(Links::$api->organisation->team->invite, "merchants.OrganisationApiController::inviteTeamMember");
        Routes::post(Links::$api->organisation->team->respond, "merchants.OrganisationApiController::respondToInvitation");
        Routes::post(Links::$api->organisation->team->list, "merchants.OrganisationApiController::getOrganisationMembers");
        Routes::post(Links::$api->organisation->team->role->create, "merchants.OrganisationApiController::createNewRole");
        Routes::post(Links::$api->organisation->team->role->rename, "merchants.OrganisationApiController::renameRole");
        Routes::delete(Links::$api->organisation->team->role->delete, "merchants.OrganisationApiController::deleteRole");
        Routes::post(Links::$api->organisation->team->role->permissions, "merchants.OrganisationApiController::updateRolePermissions");
        Routes::post(Links::$api->organisation->team->scopedLocations->get, "merchants.OrganisationApiController::getMemberScopedLocations");
        Routes::post(Links::$api->organisation->team->scopedLocations->update, "merchants.OrganisationApiController::updateMemberScopedLocations");

        // Reports API
        Routes::post(Links::$api->organisation->reports->generateCsv, "merchants.ReportsApiController::generateCsv");
        Routes::post(Links::$api->organisation->reports->generatePdf, "merchants.ReportsApiController::generatePdf");
        Routes::get("api/organisation/reports/download/{filename}", "merchants.ReportsApiController::downloadReport");

        Routes::post(Links::$api->locations->team->update, "merchants.LocationApiController::updateLocationMember");
        Routes::post(Links::$api->locations->team->invite, "merchants.LocationApiController::inviteLocationMember");
        Routes::post(Links::$api->locations->team->list, "merchants.LocationApiController::getLocationMembers");
        Routes::post(Links::$api->locations->team->role->create, "merchants.LocationApiController::createLocationRole");
        Routes::post(Links::$api->locations->team->role->rename, "merchants.LocationApiController::renameLocationRole");
        Routes::delete(Links::$api->locations->team->role->delete, "merchants.LocationApiController::deleteLocationRole");
        Routes::post(Links::$api->locations->team->role->permissions, "merchants.LocationApiController::updateLocationRolePermissions");

        Routes::post(Links::$api->orders->list, "merchants.OrdersApiController::getOrders");
        Routes::post(Links::$api->orders->locationList, "merchants.OrdersApiController::getLocationOrders");
        Routes::post(Links::$api->orders->payments->list, "merchants.PaymentsApiController::getPayments");
        Routes::get("api/payments/{id}/receipt", "merchants.PaymentsApiController::downloadReceipt");
        Routes::post("api/merchant/orders/{id}/refund", "merchants.OrdersApiController::refundOrder");
        Routes::post("api/merchant/payments/{id}/refund", "merchants.OrdersApiController::refundPayment");
        Routes::post("api/merchant/payments/{id}/reset-rykker", "merchants.PaymentsApiController::resetPaymentRykker");
        Routes::post(Links::$api->orders->customers->list, "merchants.CustomersApiController::getCustomers");

        Routes::post(Links::$api->forms->createOrganisation, "merchants.ApiController::createOrganisation");
        Routes::get(Links::$merchant->organisation->switch, "merchants.ApiController::selectOrganisation");
        Routes::post(Links::$api->forms->merchant->editOrganisationDetails, "merchants.ApiController::updateBasicDetails");
        Routes::post(Links::$api->forms->merchant->editLocationDetails, "merchants.ApiController::updateLocationDetails");
        Routes::post(Links::$api->forms->merchant->editTerminalDetails, "merchants.ApiController::updateTerminalDetails");
        Routes::post(Links::$api->forms->merchant->addNewLocation, "merchants.ApiController::createLocation");
        Routes::post(Links::$api->locations->merchantHeroImage, "merchants.PageBuilderApiController::uploadLocationHeroImage");
        Routes::delete(Links::$api->locations->merchantHeroImage, "merchants.PageBuilderApiController::removeLocationHeroImage");
        Routes::post(Links::$api->locations->merchantLogo, "merchants.PageBuilderApiController::uploadLocationLogo");
        Routes::delete(Links::$api->locations->merchantLogo, "merchants.PageBuilderApiController::removeLocationLogo");
        Routes::post(Links::$api->locations->merchantOfferImage, "merchants.PageBuilderApiController::uploadLocationOfferImage");
        Routes::delete(Links::$api->locations->merchantOfferImage, "merchants.PageBuilderApiController::removeLocationOfferImage");
        Routes::post(Links::$api->locations->savePageDraft, "merchants.PageBuilderApiController::saveLocationPageDraft");
        Routes::post(Links::$api->locations->publishPageDraft, "merchants.PageBuilderApiController::publishPageDraft");
        Routes::post(Links::$api->forms->merchant->addNewTerminal, "merchants.ApiController::createTerminal");


        Routes::get(Links::$api->checkout->merchantPosGetSessions, "flows.purchase.MerchantApiController::getTerminalSessions");
        Routes::delete(Links::$api->checkout->terminalSession, "flows.purchase.MerchantApiController::deleteTerminalSession");

        Routes::post(Links::$api->checkout->merchantPosBasket, "flows.purchase.MerchantApiController::createPosBasket");
        Routes::get(Links::$api->checkout->merchantPosBasket, "flows.purchase.MerchantApiController::getBasket");
        Routes::post(Links::$api->checkout->merchantVoidBasket, "flows.purchase.MerchantApiController::voidBasket");
        Routes::get(Links::$api->checkout->terminalSession, "flows.purchase.MerchantApiController::getTerminalSession");

        // Merchant support routes
        Routes::post(Links::$api->merchant->support->create, "merchants.ApiController::supportCreate");
        Routes::post(Links::$api->merchant->support->reply, "merchants.ApiController::supportReply");
        Routes::post(Links::$api->merchant->support->close, "merchants.ApiController::supportClose");
        Routes::post(Links::$api->merchant->support->reopen, "merchants.ApiController::supportReopen");

    });

    Routes::group(['consumer'], function() {
        Routes::delete(Links::$api->checkout->terminalSession, "flows.purchase.CustomerApiController::voidTerminalSession");
        Routes::get(Links::$api->checkout->basketHash, "flows.purchase.CustomerApiController::getBasketHash");

    });

    Routes::group(['merchantOrConsumer'], function() {


        Routes::get(Links::$api->checkout->consumerBasket, "flows.purchase.CustomerApiController::getBasket");
        Routes::post("api/checkout/payment/session", "flows.purchase.CustomerApiController::generateSession");
        Routes::post("api/checkout/order/status", "flows.purchase.CustomerApiController::checkOrderStatus");
        Routes::post("api/checkout/order/evaluate", "flows.purchase.CustomerApiController::evaluateOrder");

    });




});
/**
 *  =========================================
 *  ========== REQUIRES LOGIN END ===========
 *  =========================================
 */






/**
 *  =========================================
 *  =========== AUTH VIEWS START ============
 *  =========================================
 */
Routes::group(["requiresLogin"], function () {

    // Password change page - available to all logged-in users
    Routes::get(Links::$app->auth->changePassword, "auth.PageController::changePassword");


    /**
     *  =========================================
     *  ============ MERCHANT START =============
     *  =========================================
     */
    Routes::group(["merchant"], function () {
        Routes::get(Links::$merchant->organisation->team, "merchants.pages.PageController::team");
        Routes::get(Links::$merchant->organisation->home, "merchants.pages.PageController::organisation");
        Routes::get(Links::$merchant->organisation->add, "merchants.pages.PageController::add");
        Routes::get(Links::$merchant->dashboard, "merchants.pages.PageController::dashboard");
        Routes::get(Links::$merchant->settings, "merchants.pages.PageController::settings");
        Routes::get(Links::$merchant->accessDenied, "merchants.pages.PageController::accessDenied");
        Routes::get(Links::$merchant->orders, "merchants.pages.PageController::orders");
        Routes::get("orders/{id}", "merchants.pages.PageController::orderDetail");
        Routes::get(Links::$merchant->payments, "merchants.pages.PageController::payments");
        Routes::get("payments/{id}", "merchants.pages.PageController::paymentDetail");
        Routes::get(Links::$merchant->pendingPayments, "merchants.pages.PageController::pendingPayments");
        Routes::get(Links::$merchant->pastDuePayments, "merchants.pages.PageController::pastDuePayments");
        Routes::get(Links::$merchant->customers, "merchants.pages.PageController::customers");
        Routes::get("customers/{id}", "merchants.pages.PageController::customerDetail");
        Routes::get(Links::$merchant->terminals->main, "merchants.pages.PageController::terminals");
        Routes::get(Links::$merchant->locations->main, "merchants.pages.PageController::locations");
        Routes::get(Links::$merchant->locations->singleLocation, "merchants.pages.PageController::singleLocation");
        Routes::get(Links::$merchant->locations->locationMembers, "merchants.pages.PageController::locationMembers");
        Routes::get(Links::$merchant->locations->locationPageBuilder, "merchants.pages.PageController::locationPageBuilder");
        Routes::get(Links::$merchant->locations->locationPreviewPage, "merchants.pages.PageController::locationPageBuilderPreview");
        Routes::get(Links::$merchant->locations->locationPreviewCheckout, "merchants.pages.PageController::locationPageBuilderPreviewCheckout");
        Routes::get(Links::$merchant->terminals->terminalQr, "merchants.pages.PageController::getTerminalQrBytes");
        Routes::get(Links::$merchant->locations->locationQr, "merchants.pages.PageController::getLocationQrBytes");

        Routes::get(Links::$merchant->materials, "merchants.pages.PageController::materials");
        Routes::get(Links::$merchant->reports, "merchants.pages.PageController::reports");
        Routes::get(Links::$merchant->support, "merchants.pages.PageController::support");

        Routes::get(Links::$merchant->terminals->terminalPosStart, "flows.purchase.MerchantPageController::posStart");
        Routes::get(Links::$merchant->terminals->terminalPosDetails, "flows.purchase.MerchantPageController::posDetails");
        Routes::get(Links::$merchant->terminals->terminalPosCheckout, "flows.purchase.MerchantPageController::posCheckout");
        Routes::get(Links::$merchant->terminals->terminalPosFulfilled, "flows.purchase.MerchantPageController::posFulfilled");




    });
    /**
     * =========================================
     * ============= MERCHANT END ==============
     * =========================================
     */







});
/**
 *  =========================================
 *  ============ AUTH VIEWS END =============
 *  =========================================
 */



// Policy pages (with optional version parameter)
Routes::get(Links::$policies->consumer->privacy . '/{version}', "LandingController::consumerPrivacyPolicy");
Routes::get(Links::$policies->consumer->privacy, "LandingController::consumerPrivacyPolicy");
Routes::get(Links::$policies->consumer->termsOfUse . '/{version}', "LandingController::consumerTerms");
Routes::get(Links::$policies->consumer->termsOfUse, "LandingController::consumerTerms");
Routes::get(Links::$policies->merchant->privacy . '/{version}', "LandingController::merchantPrivacyPolicy");
Routes::get(Links::$policies->merchant->privacy, "LandingController::merchantPrivacyPolicy");
Routes::get(Links::$policies->merchant->termsOfUse . '/{version}', "LandingController::merchantTerms");
Routes::get(Links::$policies->merchant->termsOfUse, "LandingController::merchantTerms");
Routes::get(Links::$policies->cookies . '/{version}', "LandingController::cookiesPolicy");
Routes::get(Links::$policies->cookies, "LandingController::cookiesPolicy");
Routes::get("policies/cookies/{version}", "LandingController::cookiesPolicy");
Routes::get("policies/cookies", "LandingController::cookiesPolicy");

// FAQ pages
Routes::get(Links::$faq->consumer, "LandingController::faqConsumer");
Routes::get(Links::$faq->merchant, "LandingController::faqMerchant");

/**
 *  =========================================
 *  ============ LOGGED OUT START ===========
 *  =========================================
 */
Routes::group(['requiresLoggedOut'], function() {



//    Routes::get(Links::$policies->consumer->privacy, "GeneralController::pageNotReady");
//    Routes::get(Links::$policies->consumer->termsOfUse, "GeneralController::pageNotReady");
//    Routes::get(Links::$policies->merchant->privacy, "GeneralController::pageNotReady");
//    Routes::get(Links::$policies->merchant->termsOfUse, "GeneralController::pageNotReady");
    Routes::get(Links::$support->public, "GeneralController::pageNotReady");
//    Routes::get(Links::$merchant->public->signup, "GeneralController::pageNotReady");
    Routes::get(Links::$merchant->public->recovery, "GeneralController::pageNotReady");
    Routes::get(Links::$consumer->public->signup, "GeneralController::pageNotReady");
    Routes::get(Links::$consumer->public->recovery, "GeneralController::pageNotReady");




    Routes::get("mitid-test/success", "LandingController::mitIdTestSuccess");
    Routes::get("mitid-test", "LandingController::mitIdTest");
    Routes::get("viva-test", "LandingController::vivaTest");
    Routes::get("viva-test/return-url", "LandingController::vivaTestReturnUrl");


});
/**
 *  =========================================
 *  ============= LOGGED OUT END ============
 *  =========================================
 */




/**
 *  =========================================
 *  =========== AUTH VIEWS START ============
 *  =========================================
 */
Routes::group(["requiresLogin"], function () {








});
/**
 *  =========================================
 *  ============ AUTH VIEWS END =============
 *  =========================================
 */





/**
 *  =========================================
 *  =========== API ROUTES START ============
 *  =========================================
 */
Routes::group(["api"], function() {
    Routes::get("api/connection/test", "api.GeneralController::connectionTest");
    Routes::post("api/track", "api.GeneralController::trackEvent");
    Routes::get("api/template/modal/{name}", "api.ContentController::getTemplateModal");
    Routes::get("api/template/element/{name}", "api.ContentController::getTemplateElement");
    Routes::any("api/proxy", "api.ContentController::proxy");








    /**
     *  =========================================
     *  ========= REQUIRES LOGIN START ==========
     *  =========================================
     */
    Routes::group(['requiresApiLogin'], function() {
        Routes::post("api/user/settings/{property_name}", "api.UserController::updateSettings");
        Routes::post("api/user/settings", "api.UserController::updateSettings");
        Routes::get("api/has-session", "api.AuthController::hasSession");







    });
    /**
     *  =========================================
     *  ========== REQUIRES LOGIN END ===========
     *  =========================================
     */
});
/**
 *  =========================================
 *  ============ API ROUTES END =============
 *  =========================================
 */





/**
 *  =========================================
 *  ====== REQUIRES LOGIN ADMIN START =======
 *  =========================================
 */
Routes::group(['requiresLogin', "admin"], function() {

    /**
     *  =========================================
     *  =========== DASHBOARD START =============
     *  =========================================
     */
    Routes::get("", "admin.PageController::dashboard");
    Routes::get(Links::$admin->dashboard, "admin.PageController::dashboard");
    Routes::get(Links::$admin->users, "admin.PageController::users");
    Routes::get(Links::$admin->users . "/{id}", "admin.PageController::userDetail");
    Routes::get(Links::$admin->consumers, "admin.PageController::consumers");
    Routes::get(Links::$admin->merchants, "admin.PageController::merchants");
    Routes::get(Links::$admin->organisations, "admin.PageController::organisations");
    Routes::get(Links::$admin->organisations . "/{id}", "admin.PageController::organisationDetail");
    Routes::get(Links::$admin->locations, "admin.PageController::locations");
    Routes::get(Links::$admin->locations . "/{id}", "admin.PageController::locationDetail");
    Routes::get(Links::$admin->orders, "admin.PageController::orders");
    Routes::get(Links::$admin->orders . "/{id}", "admin.PageController::orderDetail");
    Routes::get(Links::$admin->payments, "admin.PageController::payments");
    Routes::get(Links::$admin->payments . "/{id}", "admin.PageController::paymentDetail");
    Routes::get(Links::$admin->paymentsPending, "admin.PageController::paymentsPending");
    Routes::get(Links::$admin->paymentsPastDue, "admin.PageController::paymentsPastDue");
    Routes::get(Links::$admin->kpi, "admin.PageController::kpi");
    Routes::get(Links::$admin->reports, "admin.PageController::reports");
    Routes::get(Links::$admin->support, "admin.PageController::support");
    Routes::get(Links::$admin->support . "/{id}", "admin.PageController::supportDetail");
    /**
     *  =========================================
     *  ============ DASHBOARD END ==============
     *  =========================================
     */


    /**
     *  =========================================
     *  ============= PANEL START ===============
     *  =========================================
     */
    Routes::get(ADMIN_PANEL_PATH, "admin.PanelController::home");
    Routes::get(Links::$admin->panelSettings, "admin.PanelController::settings");
    Routes::get(Links::$admin->panelMarketing, "admin.PanelController::marketing");
    Routes::get(Links::$admin->panelFees, "admin.PanelController::fees");
    Routes::get(Links::$admin->panelUsers, "admin.AdminController::users");
    Routes::get(Links::$admin->panelUsers . "/{role}", "admin.AdminController::users");
    Routes::get(Links::$admin->panelLogs, "admin.AdminController::logList");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}", "admin.AdminController::logView");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}/{month}", "admin.AdminController::logView");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}/{month}/{day}", "admin.AdminController::logView");
    Routes::get(Links::$admin->panelWebhooks, "admin.PanelController::webhooks");
    Routes::get(Links::$admin->panelApi, "admin.PanelController::api");
    Routes::get(Links::$admin->panelPaymentPlans, "admin.PanelController::paymentPlans");
    Routes::get(Links::$admin->panelMaintenance, "admin.PanelController::maintenance");
    Routes::get(Links::$admin->panelCache, "admin.PanelController::cache");
    Routes::get(Links::$admin->panelJobs, "admin.PanelController::jobs");

    // Content & Policies
    Routes::get(Links::$admin->panelPolicies, "admin.PanelController::policies");
    Routes::get(Links::$admin->panelPoliciesPrivacy, "admin.PanelController::policiesPrivacy");
    Routes::get(Links::$admin->panelPoliciesTerms, "admin.PanelController::policiesTerms");
    Routes::get(Links::$admin->panelPoliciesCookies, "admin.PanelController::policiesCookies");
    Routes::get(Links::$admin->panelContactForms, "admin.PanelController::contactForms");
    Routes::get(Links::$admin->panelNotifications, "admin.PanelController::notifications");
    Routes::get(Links::$admin->panelFaqs, "admin.PanelController::faqs");

    // Notification System
    Routes::get(Links::$admin->panelNotificationTemplates, "admin.NotificationController::templates");
    Routes::get(Links::$admin->panelNotificationTemplates . "/{id}", "admin.NotificationController::templateDetail");
    Routes::get(Links::$admin->panelNotificationBreakpoints, "admin.NotificationController::breakpoints");
    Routes::get(Links::$admin->panelNotificationFlows, "admin.NotificationController::flows");
    Routes::get(Links::$admin->panelNotificationFlows . "/{id}", "admin.NotificationController::flowDetail");
    Routes::get(Links::$admin->panelNotificationQueue, "admin.NotificationController::queue");
    Routes::get(Links::$admin->panelNotificationLogs, "admin.NotificationController::logs");
    /**
     *  =========================================
     *  ============== PANEL END ================
     *  =========================================
     */



    /**
     *  =========================================
     *  =========== MIGRATION START =============
     *  =========================================
     */
    Routes::get("migration/init", "admin.AdminController::migrationInit");
    Routes::get("migration/move", "admin.AdminController::migrationMoveFiles", ["migrating"]);
    Routes::get("migration/db", "admin.AdminController::migrationDb", ["migrating"]);
    /**
     *  =========================================
     *  ============ MIGRATION END ==============
     *  =========================================
     */
});
/**
 *  =========================================
 *  ======= REQUIRES LOGIN ADMIN END ========
 *  =========================================
 */




/**
 *  =========================================
 *  ==== REQUIRES API LOGIN ADMIN START =====
 *  =========================================
 */
// Impersonation stop - requires active impersonation session
Routes::group(['requiresApiLogin', "isImpersonating", "api"], function() {
    Routes::post("api/admin/impersonate/stop", "admin.ApiController::stopImpersonation");
});

Routes::group(['requiresApiLogin', "admin", "api"], function() {
    // Admin impersonation start (requires admin)
    Routes::post("api/admin/impersonate/start", "admin.ApiController::startImpersonation");

    Routes::post("webhook/test", "admin.AdminController::testWebhook");
    Routes::post("api/admin/settings/app/{name}/{action}", "admin.ApiController::appMetaUpdate");
    Routes::post("api/admin/panel/update-setting", "admin.ApiController::panelUpdateSetting");
    Routes::post("api/admin/panel/create-user", "admin.ApiController::panelCreateUser");
    Routes::post("api/admin/panel/create-role", "admin.ApiController::panelCreateRole");
    Routes::post("api/admin/panel/update-role", "admin.ApiController::panelUpdateRole");
    Routes::post(Links::$api->admin->panel->rykkerSettings, "admin.ApiController::panelRykkerSettings");
    Routes::post("api/admin/payments/list", "admin.ApiController::paymentsList");
    Routes::post("api/admin/users/list", "admin.ApiController::usersList");
    Routes::post("api/admin/orders/list", "admin.ApiController::ordersList");
    Routes::post("api/admin/orders/{id}/refund", "admin.ApiController::refundOrder");
    Routes::post("api/admin/payments/{id}/refund", "admin.ApiController::refundPayment");
    Routes::post("api/admin/payments/{id}/reset-rykker", "admin.ApiController::resetPaymentRykker");
    Routes::post("api/admin/payments/{id}/mark-collection", "admin.ApiController::markPaymentForCollection");
    Routes::post("api/admin/organisations/list", "admin.ApiController::organisationsList");
    Routes::post("api/admin/locations/list", "admin.ApiController::locationsList");
    Routes::post("api/admin/dashboard/stats", "admin.ApiController::dashboardStats");
    Routes::post("api/admin/organisation/{id}/status", "admin.ApiController::organisationStatusUpdate");
    // Admin Reports API
    Routes::post("api/admin/reports/stats", "admin.ReportsApiController::getStats");
    Routes::post("api/admin/reports/generate-csv", "admin.ReportsApiController::generateCsv");
    Routes::post("api/admin/reports/generate-pdf", "admin.ReportsApiController::generatePdf");
    Routes::get("api/admin/reports/download/{filename}", "admin.ReportsApiController::downloadReport");
    Routes::post("api/create-user-on-behalf", "api.AuthController::createUserThirdParty");
    Routes::post("api/user/{id}/toggle", "api.ContentController::userToggleSuspension");

    // Notification System API
    Routes::post("api/admin/notifications/templates/list", "admin.NotificationApiController::templatesList");
    Routes::post("api/admin/notifications/templates/create", "admin.NotificationApiController::templateCreate");
    Routes::post("api/admin/notifications/templates/update", "admin.NotificationApiController::templateUpdate");
    Routes::post("api/admin/notifications/templates/delete", "admin.NotificationApiController::templateDelete");
    Routes::post("api/admin/notifications/templates/preview", "admin.NotificationApiController::templatePreview");
    Routes::post("api/admin/notifications/flows/list", "admin.NotificationApiController::flowsList");
    Routes::post("api/admin/notifications/flows/create", "admin.NotificationApiController::flowCreate");
    Routes::post("api/admin/notifications/flows/update", "admin.NotificationApiController::flowUpdate");
    Routes::post("api/admin/notifications/flows/delete", "admin.NotificationApiController::flowDelete");
    Routes::post("api/admin/notifications/flows/clone", "admin.NotificationApiController::flowClone");
    Routes::post("api/admin/notifications/flows/actions/create", "admin.NotificationApiController::flowActionCreate");
    Routes::post("api/admin/notifications/flows/actions/update", "admin.NotificationApiController::flowActionUpdate");
    Routes::post("api/admin/notifications/flows/actions/delete", "admin.NotificationApiController::flowActionDelete");
    Routes::post("api/admin/notifications/queue/list", "admin.NotificationApiController::queueList");
    Routes::post("api/admin/notifications/queue/cancel", "admin.NotificationApiController::queueCancel");
    Routes::post("api/admin/notifications/logs/list", "admin.NotificationApiController::logsList");
    Routes::post("api/admin/notifications/logs/resend", "admin.NotificationApiController::logsResend");

    // Notification System Debug/Test Endpoints
    Routes::post("api/admin/notifications/test/trigger", "admin.NotificationApiController::testTrigger");
    Routes::post("api/admin/notifications/test/process-queue", "admin.NotificationApiController::processQueue");
    Routes::post("api/admin/notifications/test/process-scheduled", "admin.NotificationApiController::processScheduled");

    // FAQ System API
    Routes::post("api/admin/faqs/list", "admin.ApiController::faqsList");
    Routes::post("api/admin/faqs/create", "admin.ApiController::faqCreate");
    Routes::post("api/admin/faqs/update", "admin.ApiController::faqUpdate");
    Routes::post("api/admin/faqs/delete", "admin.ApiController::faqDelete");
    Routes::post("api/admin/faqs/toggle-active", "admin.ApiController::faqToggleActive");
    Routes::post("api/admin/faqs/reorder", "admin.ApiController::faqReorder");

    // Support Ticket System API
    Routes::post(Links::$api->admin->support->list, "admin.ApiController::supportList");
    Routes::post(Links::$api->admin->support->reply, "admin.ApiController::supportReply");
    Routes::post(Links::$api->admin->support->close, "admin.ApiController::supportClose");
    Routes::post(Links::$api->admin->support->reopen, "admin.ApiController::supportReopen");
    Routes::post(Links::$api->admin->support->delete, "admin.ApiController::supportDelete");

    // Policy Management API
    Routes::post(Links::$api->admin->policies->list, "admin.ApiController::policiesList");
    Routes::post(Links::$api->admin->policies->get, "admin.ApiController::policiesGet");
    Routes::post(Links::$api->admin->policies->save, "admin.ApiController::policiesSave");
    Routes::post(Links::$api->admin->policies->publish, "admin.ApiController::policiesPublish");
    Routes::post(Links::$api->admin->policies->delete, "admin.ApiController::policiesDelete");
    Routes::post(Links::$api->admin->policies->versions, "admin.ApiController::policiesVersions");

    // Cronjob Admin API
    Routes::post("api/admin/cronjobs/force-run", "api.CronjobController::forceRun");
    Routes::get("api/admin/cronjobs/logs", "api.CronjobController::getLogs");
});
/**
 *  =========================================
 *  ===== REQUIRES API LOGIN ADMIN END ======
 *  =========================================
 */










/**
 *  =========================================
 *  ========== WEBHOOK ROUTES START =========
 *  =========================================
 */
Routes::group(['requiresApiLogout'], function() {

});
/**
 *  =========================================
 *  =========== WEBHOOK ROUTES END ==========
 *  =========================================
 */





/**
 *  =========================================
 *  ============= CRON JOB START ============
 *  =========================================
 */
Routes::group(['requiresApiLogout'], function() {
    Routes::post("cron/{token}/take-payments", "api.CronjobController::takePayments");
    Routes::post("cron/{token}/retry-payments", "api.CronjobController::retryPayments");
    Routes::post("cron/{token}/cleanup-logs", "api.CronjobController::cleanupLogs");
    Routes::post("cron/{token}/payment-notifications", "api.CronjobController::paymentNotifications");
    Routes::post("cron/{token}/notification-queue", "api.CronjobController::notificationQueue");
    Routes::post("cron/{token}/rykker-checks", "api.CronjobController::rykkerChecks");
    Routes::post("cron/{token}/weekly-reports", "api.CronjobController::weeklyReports");
    Routes::post("cron/{token}/policy-publish", "api.CronjobController::policyPublish");
});
/**
 *  =========================================
 *  ============== CRON JOB END =============
 *  =========================================
 */








/*  --------------------------------------------------------- UNGROUPED ROUTES BELOW --------------------------------------------------------- */







/**
 *  =========================================
 *  ======== ARBITRARY PAGES START ==========
 *  =========================================
 */
Routes::get("logout", "GeneralController::logout");
/**
 *  =========================================
 *  ========= ARBITRARY PAGES END ===========
 *  =========================================
 */






/**
 *  =========================================
 *  =========== POLICY PAGES START ==========
 *  =========================================
 */
Routes::get("privacy-policy", "PolicyController::privacy");
Routes::get("terms-of-use", "PolicyController::termsOfUse");
/**
 *  =========================================
 *  ============ POLICY PAGES END ===========
 *  =========================================
 */










Routes::dispatch();
__csrf();