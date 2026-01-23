<?php

use classes\enumerations\Links;
use features\Settings;
use classes\Methods;
function setPostData() {
    if(strtolower($_SERVER["REQUEST_METHOD"]) === "post") {
        $input = json_decode(file_get_contents("php://input"), true);
        if(empty($_POST) && !empty($input)) Settings::$postData = $input;
        else Settings::$postData = $_POST;
        if(isset($_FILES) && !empty($_FILES)) {
            Settings::$postData["__FILES"] = $_FILES;
        }
    }
}

setPostData();
Settings::$encryptionDetails = _env("encryption/details.php");
Settings::$testing = ((explode("/", getUrlPath())[0]) === "testing");
Settings::$viewingAdminDashboard = str_starts_with(realUrlPath(), ADMIN_PANEL_PATH);
Settings::$viewingOrganisationDashboard = str_starts_with(realUrlPath(), ORGANISATION_PANEL_PATH);
Settings::$app = Methods::appMeta()->mergeSettingsWithRole(
    Methods::appMeta()->getAllAsKeyPairs()
);

Settings::$testerAuth = file_get_contents(ROOT . HTACCESS_PWD_FILE);

Links::init();


foreach (\Database\model\UserRoles::where("defined", 1)->order("access_level", "DESC")->all()->list() as $role) {
    $roleMatch = match ($role->access_level) {
        default => false,
        8, 9 => Methods::isAdmin(),
    };
    if($roleMatch) break;
}


// Check cookie consent (GDPR) - valid if EITHER IP or user UID has consented
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$hasConsentByIp = Methods::cookieConsents()->existsByIp($ipAddress);
$hasConsentByUser = isLoggedIn() && Methods::cookieConsents()->existsByUser(__uuid());
Settings::$cookiesAccepted = $hasConsentByIp || $hasConsentByUser;

// Check for test mode - forces sandbox for APIs
// Either: query param with secret token, session flag, OR logged in user with test=1
if (defined('SANDBOX_MODE_TOKEN') && isset($_GET['sandbox']) && hash_equals(SANDBOX_MODE_TOKEN, $_GET['sandbox'])) {
    $_SESSION['sandbox_mode'] = true;
}
$hasValidSandboxSession = !empty($_SESSION['sandbox_mode']);

if(isLoggedIn()) {
    Settings::$user = Methods::users()->get(__uuid());

    // Check if user has test mode enabled
    if (!isEmpty(Settings::$user) && (Settings::$user->test ?? 0) == 1) {
        Settings::$userTestMode = true;
        \env\api\Viva::sandbox();
        \env\api\Signicat::sandbox();
    }

    // Check if we're impersonating (admin logged in as merchant owner or consumer)
    if (!empty($_SESSION["admin_impersonating_uid"])) {
        if (!empty($_SESSION["admin_impersonating_org"])) {
            Settings::$impersonatingOrganisation = true;
            Settings::$impersonatedOrganisationId = $_SESSION["admin_impersonating_org"];
        } elseif (!empty($_SESSION["admin_impersonating_user"])) {
            Settings::$impersonatingOrganisation = true; // Reuse flag for both types
            Settings::$impersonatedOrganisationId = $_SESSION["admin_impersonating_user"]; // Store user ID here
        }
    }

    // Normal organisation context loading
    if(!is_null(Settings::$user->cookies) && array_key_exists("organisation", toArray(Settings::$user->cookies))) {
        $organisationId = Settings::$user->cookies->organisation;
        $memberRow = Methods::organisationMembers()->getMember($organisationId);
        if(isEmpty($memberRow) || !Methods::organisationMembers()->userIsMember($organisationId)) {
            $memberRow = Methods::organisationMembers()->firstValidOrganisation();
            debugLog($memberRow, 'member-row-2');
        }
        Methods::organisationMembers()->setChosenOrganisation($memberRow);
    }
    else debugLog(Settings::$user->cookies, 'user-no-organisation-cookies');
} elseif ($hasValidSandboxSession) {
    // Not logged in but has valid sandbox session
    Settings::$userTestMode = true;
    \env\api\Viva::sandbox();
    \env\api\Signicat::sandbox();
}


