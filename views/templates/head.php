<?php
/**
 * @var array|null $styleList
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<link rel="icon" href="<?=__asset(FAVICON)?>" type="image/x-icon" />
<title><?=BRAND_NAME?></title>

<?php
if(!is_null($styleList)) {
    foreach ($styleList as $asset) {
        echo assets($asset, "css");
    }
}
?>