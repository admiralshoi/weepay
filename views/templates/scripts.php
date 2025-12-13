<?php
use features\Settings;

$linksToExclude = [];
if(!\classes\Methods::isAdmin()) {
    $linksToExclude = ['admin'];
    if(!\classes\Methods::isMerchant()) $linksToExclude[] = 'merchant';
    if(!\classes\Methods::isConsumer()) $linksToExclude[] = 'consumer';
}
?>
<script>
    var applicationProcessing = {}
    var searchQueries = null, error = null;
    var activeMenuItems = [];
    var activePage = <?=json_encode(trimPath(getUrlPath()))?>;
    const serverHost = <?=json_encode(HOST)?>;
    const HOST = <?=json_encode(HOST)?>;
    const SITE_NAME = <?=json_encode(SITE_NAME)?>;
    const userSession = <?=json_encode(isLoggedIn())?>;
    const UID = <?=json_encode(isset($_SESSION["uid"]) ? $_SESSION["uid"] : 0)?>;
    const SUMMERTIME = 2;
    const testingCredAuth = <?=json_encode(Settings::$testing ? Settings::$testerAuth : null)?>;
    const _csrf = <?=json_encode(__csrf())?>;
    const ORGANISATION_PANEL = <?=json_encode(ORGANISATION_PANEL_PATH)?>;
    const RECAPTCHA_PK = <?=json_encode(\classes\Methods::reCaptcha()->siteKey())?>;




    const errorIcon = <?=json_encode(__asset("media/icons/error_icon.png"))?>;
    const approveIcon = <?=json_encode(__asset("media/icons/approve_white_icon.png"))?>;
    const emailApproveIcon = <?=json_encode(__asset("media/icons/email.png"))?>;
    const closeWhiteIcon = <?=json_encode(__asset("media/icons/close-window-white.png"));?>


    const organisation = <?=json_encode(__unsetKey(toArray(Settings::$organisation?->organisation), ['permissions']))?>;
    var allowedCountries = <?=json_encode(\classes\Methods::countries()->mappedValues())?>;
    const defaultCountry = <?=json_encode(Settings::$app->default_country)?>;
    const currencies = <?=json_encode(toArray(Settings::$app->currencies))?>;
    const platformLinks = <?=json_encode(\classes\enumerations\Links::toArray($linksToExclude))?>;
    var thirdPartyAuth = {}
</script>