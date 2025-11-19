<?php

use classes\enumerations\Links;
use features\Settings;
use routing\Routes;

require_once ROOT . "features/init.php";
require_once ROOT . "routing/middleware/middleware.php";









/**
 *  =========================================
 *  =========== USER VERIFY START ===========
 *  =========================================
 */
Routes::group([], function() {
    Routes::get("user/verify/{status}", "verification.MitIdController::callbackRouter");
});
/**
 * =========================================
 * ============ USER VERIFY END ============
 * =========================================
 */


/**
 *  =========================================
 *  ====== CUSTOMER PURCHASE FLOW START =====
 *  =========================================
 */
Routes::group([], function() {
    Routes::get("merchant/{slug}/checkout", "flows.purchase.CustomerPageController::start");
    Routes::get("merchant/{slug}/checkout/info", "flows.purchase.CustomerPageController::info");
    Routes::get("merchant/{slug}/checkout/choose-plan", "flows.purchase.CustomerPageController::choosePlan");
});

Routes::group(['api'], function() {
    Routes::post("api/checkout/terminal/basket", "flows.purchase.CustomerApiController::getBasket");
    Routes::post("api/checkout/payment/session", "flows.purchase.CustomerApiController::generateSession");
    Routes::post("api/checkout/order/status", "flows.purchase.CustomerApiController::checkOrderStatus");
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
    Routes::post(Links::$api->forms->createOrganisation, "merchants.ApiController::createOrganisation");
    Routes::get(Links::$merchant->organisation->switch, "merchants.ApiController::selectOrganisation");







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


//        Routes::get("team", "merchants.pages.PageController::team");
        Routes::get(Links::$merchant->organisation->home, "merchants.pages.PageController::organisation");
        Routes::get(Links::$merchant->organisation->add, "merchants.pages.PageController::add");
        Routes::get(Links::$merchant->orders, "merchants.pages.PageController::orders");
        Routes::get(Links::$merchant->terminals, "merchants.pages.PageController::terminals");
        Routes::get(Links::$merchant->locations, "merchants.pages.PageController::locations");
        Routes::get(Links::$merchant->dashboard, "merchants.pages.PageController::dashboard");


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

    Routes::get("", "LandingController::home");

    /**
     *  =========================================
     *  ========== MERCHANT AUTH START ==========
     *  =========================================
     */
    Routes::get(Links::$app->auth->consumerLogin, "auth.PageController::consumerDashboardLogin");
    Routes::get(Links::$app->auth->merchantLogin, "auth.PageController::merchantDashboardLogin");
    /**
     *  =========================================
     *  =========== MERCHANT AUTH END ===========
     *  =========================================
     */



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



//    Routes::get("merchant/{slug}/checkout", "LandingController::merchantCheckout");
//    Routes::get("merchant/{slug}/checkout/validation", "LandingController::merchantCheckoutUserValidation");
//    Routes::get("merchant/{slug}/checkout/payment", "LandingController::merchantCheckoutPayment");

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