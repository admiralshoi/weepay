<?php
/**
 * @var array|null $scriptList
 * @var array|null $styleList
 * @var string|null $customScripts
 * @var string|null $head
 * @var string|null $pageContent
 */
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




<?=$pageContent?>



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