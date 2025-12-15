<?php
/**
 * @var array|null $scriptList
 * @var array|null $styleList
 * @var string|null $customScripts
 * @var string|null $head
 * @var string|null $pageContent
 * @var array $viewList
 */

use features\Settings;


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php if(!is_null($head)) include_once __view($head); ?>
</head>
<body>

<?php
if(!empty($customScripts)) {
    if(is_string($customScripts)) $customScripts = [$customScripts];
    foreach ($customScripts as $customScript) {
        include_once __view($customScript);
    }
}
?>


<div class="main-wrapper">

    <?php

    if(Settings::$viewingAdminDashboard) include_once __view("templates.nav.admin-panel");
    elseif(\classes\Methods::isConsumer()) include_once __view("templates.nav.consumer.left-nav");
    else include_once __view("templates.nav.main");

    ?>

    <?php printView($viewList); ?>

</div>




<?php
if(!is_null($scriptList)) {
    foreach ($scriptList as $asset) {
        echo assets($asset, "js");
    }
}

loadRegisteredScripts();
?>

<div id="notification-pop-container"></div>
</body>
</html>