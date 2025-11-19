<?php
use features\Settings;
?>
<script>
    var applicationProcessing = {}
    var searchQueries = null, error = null;
    var activeMenuItems = [];
    var activePage = <?=json_encode(trimPath(getUrlPath()))?>;
    const serverHost = <?=json_encode(HOST)?>;
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

    var thirdPartyAuth = {}
</script>