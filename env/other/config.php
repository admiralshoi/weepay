<?php
if(!defined("IN_VIEW")) exit;
date_default_timezone_set('Europe/Copenhagen');
function getUrlPath(): string {
    $url = parse_url($_SERVER["REQUEST_URI"]);
    $url = !is_array($url) || !array_key_exists("path", $url) ? "" : $url["path"];
    if(str_starts_with($url, "/")) $url = substr($url, 1);
    return $url;
}

define("LIVE", !str_contains($_SERVER["HTTP_HOST"], "localhost"));



/**
 * ENV files
 */
const PLATFORM_VERSION = "v1.0014";
const HTACCESS_PWD_FILE = "env/other/htaccessPwd.txt";
const DB_LIVE_FILE = "env/db/live.php";
const DB_LOCAL_FILE = "env/db/local.php";
const DB_ENABLE_PREFIX = true;
const FORWARD_KEY = "1b9fb9e6ca4379142b55469e5492220d70b21917c63a79f685e8bf540d699e6b";
const MIGRATION_TOKEN = "fa6fe44b34b7f76f66a2f1252cb0a8b7a7af70ae3a9713bc5a01029ded35cf89";
const CRONJOB_TOKEN = "91fcd71769087fb0fdad6faf7bc599260965c3481bdda73c9d850e5425ceadd4";
const LIVE_DB_FORCE_TOKEN = "583359b03515cd03a19de12e2039c7f524654babd314f029ecd65b8e3d703992";

if(LIVE) {
    define("ROOT_DIR", "");
    define("TESTING", ((explode("/", str_replace(ROOT_DIR, "", getUrlPath()))[0]) === "testing"));
    if(TESTING) {
        define("ROOT", $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR . "testing/");
        define("HOST", "https://wee-pay.dk/" . ROOT_DIR . "testing/");
    }
    else {
        define("ROOT", $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR);
        define("HOST", "https://wee-pay.dk/" . ROOT_DIR);
    }
}
else {
    define("ROOT_DIR", "weepay/");
    define("TESTING", ((explode("/", str_replace(ROOT_DIR, "", getUrlPath()))[0]) === "testing"));
    if(TESTING) {
        define("ROOT", $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR . "testing/");
        define("HOST", "https://localhost/" . ROOT_DIR . "testing/");
    }
    else {
        define("ROOT", $_SERVER["DOCUMENT_ROOT"] . "/" . ROOT_DIR);
        define("HOST", "https://localhost/" . ROOT_DIR);
    }
}


const MAX_BACKUPS = 4;
const MIGRATION_EXCLUDES = [
    ".htaccess", ".htpasswd", "logs\\DIR_ONLY", "public\\media\\dynamic\\DIR_ONLY"
];
const BACKUP_EXCLUDES = [
    "testing", "versions", "public\\media\\dynamic\\DIR_ONLY", "bup"
];














define("SITE_NAME","wee-pay.dk"); //Site name
define("BRAND_NAME","WeePay"); //Brand

define("LOGO_WIDE_HEADER", "media/logos/weepay_pos.svg");
define("PARTNER_BANK_LOGO", "media/images/viva-first-tech-bank.png");
define("LOGO_ICON", "media/logos/icon.png");
define("FAVICON", "media/icons/icon.ico");

define("DEFAULT_LOCATION_HERO", "public/media/images/merchant-beauty-DuNYPCOQ.jpg");
define("DEFAULT_LOCATION_LOGO", "public/media/images/nopp.png");


define("LOGO_HEADER", "media/images/logo-goodbrands-04.svg"); //Transparent version of the logo
define("LOGO_HEADER_WHITE", "media/images/logo_white.png"); //Transparent version of the logo
define("LOGO_ICON_WHITE", "media/logos/icon.png"); //Transparent version of the logo
define("LOGO_SQUARE", "media/images/logo.jpg"); //Transparent version of the logo
define("GMT_TIME", "GMT+1 (CET)"); //Timezone that is visually displayed on graphs on most pages.

define("VIVA_LOGIN_URL", "https://accounts.vivapayments.com/Account/Login");




/**
 * Company legal
 */
DEFINE("COMPANY_NAME", "In Via ApS");
DEFINE("COMPANY_ALIAS", implode(" and ", ['WeePay']));
DEFINE("COMPANY_STREET_ADDRESS", "Hindegade 6");
DEFINE("COMPANY_CITY", "København K");
DEFINE("COMPANY_POSTAL", "1303");
DEFINE("COMPANY_COUNTRY", "Danmark");
DEFINE("COMPANY_ADDRESS_STRING", implode(" ", [COMPANY_STREET_ADDRESS, COMPANY_CITY, COMPANY_POSTAL]));
DEFINE("COMPANY_EMAIL", "mail@" . SITE_NAME);
DEFINE("COMPANY_WEBSITE", "www.wee-pay.dk");
DEFINE("CONTACT_PHONE", "+45 81 98 18 86");
DEFINE("CONTACT_EMAIL",  "mail@" . SITE_NAME);
DEFINE("COMPANY_CVR", "39148196");




/**
 * Other variables
 */
const MODIFY_ACTION = 2; //Defining action-level for access-points as MODIFY ONLY
const READ_ACTION = 1; //Defining action-level for access-points as READ ONLY
const KNOWN_CONTENT_TYPES = [
    "image/jpeg" => "jpeg",
    "image/jpg" => "jpg",
    "image/heif" => "heic",
    "image/png" => "png",
    "image/svn" => "svg",
    "image/webp" => "png",
    "image/gif" => "gif",
    "video/avi" => "avi",
    "video/mp4" => "mp4",
    "video/mov" => "mov",
    "video/wmv" => "wmv",
    "video/webm" => "mp4",
];
const DEFAULT_USER_PICTURE = "public/media/images/nopp.png";
const ADMIN_PANEL_PATH = "panel";
const ORGANISATION_PANEL_PATH = "organisation";




/**
 * Libraries
 */
const MATH_AI = "vendor/math-ai/vendor/autoload.php";
const PARSER = "vendor/html_parser/autoload.php";
const HTML5_PARSER = "vendor/html5_parser/autoload.php";
const HASHTAG_LIST = "hashtags/hashtags.json";
const GENDER_LIB = "names/gender_and_origin.json";
const COUNTRY_SEARCH_LIB = "countries/countries_and_cities.json";
const COUNTRY_NAME_BY_CODE = "countries/countrycode_to_country.json";
const WORLD_COUNTRIES = "countries/worldCountries.json";
const DIALER_CODES = "countries/dialer_codes.json";
const CURRENCIES = "lib/countries/currencies.json";


/**
 * Cronjob
 */
const CRON_LOGS = ROOT . "logs/cron/";
const CRON_LOG_MAX_ENTRIES = 200;





require_once ROOT . "features/functions.php";