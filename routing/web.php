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
Routes::get(Links::$app->auth->merchantLogin, "auth.PageController::merchantDashboardLogin");
/**
 *  =========================================
 *  ============= OPEN PAGES END ============
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
 *  =========================================
 *  ====== CUSTOMER PURCHASE FLOW START =====
 *  =========================================
 */
Routes::group([], function() {
    Routes::get(Links::$merchant->public->locationPage, "flows.purchase.CustomerPageController::home");
    Routes::get("merchant/{slug}/checkout", "flows.purchase.CustomerPageController::start", ['notMerchant', 'notAdmin']);
    Routes::get("merchant/{slug}/checkout/info", "flows.purchase.CustomerPageController::info", ['notMerchant', 'notAdmin']);
    Routes::get(Links::$merchant->terminals->consumerChoosePlan, "flows.purchase.CustomerPageController::choosePlan", ['notMerchant', 'notAdmin']);
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
    Routes::post(Links::$api->auth->consumerLogin, "auth.ApiController::loginUser");
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
    Routes::group(['merchant'], function() {
        Routes::post(Links::$api->organisation->team->update, "merchants.ApiController::updateTeamMember");
        Routes::post(Links::$api->organisation->team->role->create, "merchants.ApiController::createNewRole");
        Routes::post(Links::$api->organisation->team->role->rename, "merchants.ApiController::renameRole");
        Routes::delete(Links::$api->organisation->team->role->delete, "merchants.ApiController::deleteRole");
        Routes::post(Links::$api->organisation->team->role->permissions, "merchants.ApiController::updateRolePermissions");

        Routes::post(Links::$api->forms->createOrganisation, "merchants.ApiController::createOrganisation");
        Routes::get(Links::$merchant->organisation->switch, "merchants.ApiController::selectOrganisation");
        Routes::post(Links::$api->forms->merchant->editOrganisationDetails, "merchants.ApiController::updateBasicDetails");
        Routes::post(Links::$api->forms->merchant->addNewLocation, "merchants.ApiController::createLocation");
        Routes::post(Links::$api->forms->merchant->addNewTerminal, "merchants.ApiController::createTerminal");


        Routes::get(Links::$api->checkout->merchantPosGetSessions, "flows.purchase.MerchantApiController::getTerminalSessions");
        Routes::delete(Links::$api->checkout->terminalSession, "flows.purchase.MerchantApiController::deleteTerminalSession");

        Routes::post(Links::$api->checkout->merchantPosBasket, "flows.purchase.MerchantApiController::createPosBasket");
        Routes::get(Links::$api->checkout->merchantPosBasket, "flows.purchase.MerchantApiController::getBasket");
        Routes::post(Links::$api->checkout->merchantVoidBasket, "flows.purchase.MerchantApiController::voidBasket");
        Routes::get(Links::$api->checkout->terminalSession, "flows.purchase.MerchantApiController::getTerminalSession");



    });

    Routes::group(['consumer'], function() {
        Routes::delete(Links::$api->checkout->terminalSession, "flows.purchase.CustomerApiController::voidTerminalSession");
        Routes::get(Links::$api->checkout->basketHash, "flows.purchase.CustomerApiController::getBasketHash");

    });

    Routes::group(['merchantOrConsumer'], function() {


        Routes::get(Links::$api->checkout->consumerBasket, "flows.purchase.CustomerApiController::getBasket");
        Routes::post("api/checkout/payment/session", "flows.purchase.CustomerApiController::generateSession");
        Routes::post("api/checkout/order/status", "flows.purchase.CustomerApiController::checkOrderStatus");

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
        Routes::get(Links::$merchant->orders, "merchants.pages.PageController::orders");
        Routes::get(Links::$merchant->terminals->main, "merchants.pages.PageController::terminals");
        Routes::get(Links::$merchant->locations->main, "merchants.pages.PageController::locations");
        Routes::get(Links::$merchant->locations->singleLocation, "merchants.pages.PageController::singleLocation");
        Routes::get(Links::$merchant->locations->locationMembers, "merchants.pages.PageController::locationMembers");
        Routes::get(Links::$merchant->locations->locationPageBuilder, "merchants.pages.PageController::locationPageBuilder");
        Routes::get(Links::$merchant->terminals->terminalQr, "merchants.pages.PageController::getTerminalQrBytes");

        Routes::get(Links::$merchant->terminals->terminalPosStart, "flows.purchase.MerchantPageController::posStart");
        Routes::get(Links::$merchant->terminals->terminalPosDetails, "flows.purchase.MerchantPageController::posDetails");
        Routes::get(Links::$merchant->terminals->terminalPosCheckout, "flows.purchase.MerchantPageController::posCheckout");




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




/**
 *  =========================================
 *  ============ LOGGED OUT START ===========
 *  =========================================
 */
Routes::group(['requiresLoggedOut'], function() {



    Routes::get(Links::$policies->consumer->privacy, "GeneralController::pageNotReady");
    Routes::get(Links::$policies->consumer->termsOfUse, "GeneralController::pageNotReady");
    Routes::get(Links::$policies->merchant->privacy, "GeneralController::pageNotReady");
    Routes::get(Links::$policies->merchant->termsOfUse, "GeneralController::pageNotReady");
    Routes::get(Links::$support->public, "GeneralController::pageNotReady");
    Routes::get(Links::$merchant->public->signup, "GeneralController::pageNotReady");
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
    Routes::get("", "admin.AdminController::home");




    /**
     *  =========================================
     *  ============= PANEL START ===============
     *  =========================================
     */
    Routes::get(ADMIN_PANEL_PATH, "admin.AdminController::home");
    Routes::get(ADMIN_PANEL_PATH . "/app", "admin.AdminController::appSettings");
    Routes::get(ADMIN_PANEL_PATH . "/users", "admin.AdminController::users");
    Routes::get(ADMIN_PANEL_PATH . "/users/{role}", "admin.AdminController::users");
    Routes::get(ADMIN_PANEL_PATH . "/logs/list", "admin.AdminController::logList");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}", "admin.AdminController::logView");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}/{month}", "admin.AdminController::logView");
    Routes::get(ADMIN_PANEL_PATH . "/logs/{type}/{month}/{day}", "admin.AdminController::logView");
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
Routes::group(['requiresApiLogin', "admin", "api"], function() {
    Routes::post("webhook/test", "admin.AdminController::testWebhook");
    Routes::post("api/admin/settings/app/{name}/{action}", "admin.ApiController::appMetaUpdate");
    Routes::post("api/create-user-on-behalf", "api.AuthController::createUserThirdParty");
    Routes::post("api/user/{id}/toggle", "api.ContentController::userToggleSuspension");
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
Routes::group(['requiresApiLogout', "cronJobAuth"], function() {

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