<?php
/**
 * @var object $args
 */

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;


$location = $args->location;
$pageTitle = $location->name . " - Pagebuilder";


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
</script>


<div class="page-content home">

    <div class="flex-col-start" id="nav" style="gap: 0;">
        <a class="cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
           style="gap: .5rem;" href="<?=__url(Links::$merchant->locations->setSingleLocation($location->slug))?>">
            <i class="mdi mdi-arrow-left"></i>
            <span>Tilbage</span>
        </a>
        <div class="flex-row-between flex-align-start flex-nowrap">
            <?=\features\DomMethods::locationSelect($args->locationOptions, $location->slug);?>
            <div class="flex-row-end">
                <p class="mb-0 font-16 font-weight-medium color-gray"><?=$location->name?></p>
            </div>
        </div>
    </div>


    <div class="flex-row-between-start flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <div class="flex-row-start-center flex-nowrap cg-05">

                <p class="mb-0 font-30 font-weight-bold">Rediger butiksside</p>
            </div>
            <p class="mb-0 font-16 font-weight-medium color-gray text-wrap mxw-400px">
                Tilpas din butiksside – denne side fokuserer på din butik. WeePay faciliterer kun.
            </p>
        </div>
        <div class="flex-row-end-center flex-nowrap cg-075 flex-nowrap">
            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewPage($args->slug, 'draft'))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-eye-outline"></i>
                <span class="text-nowrap">Forhåndsvis side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewCheckout($args->slug, 'draft'))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-cart-outline"></i>
                <span class="text-nowrap">Forhåndsvis checkout</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <button name="create_page_draft" data-id="<?=$location->uid?>" onclick="" class="btn-v2 action-btn text-nowrap" >
                <i class="mdi mdi-plus"></i>
                <span class="text-nowrap">Opret udkast</span>
            </button>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>


</div>




