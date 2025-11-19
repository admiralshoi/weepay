<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;
use features\Settings;

$pageTitle = "Organisation";
if(!isEmpty(Settings::$organisation?->organisation)) $pageTitle .= " - " . Settings::$organisation->organisation->name;


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "organisation";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::organisationSelect($args->memberRows, Settings::$organisation?->organisation?->uid);?>
        <div class="flex-row-end">
            <a href="<?=__url(Links::$merchant->organisation->add)?>"
               class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap color-white" style="gap: .5rem;">
                <i class="mdi mdi-plus"></i>
                <span>Tilføj ny organisation</span>
            </a>
        </div>
    </div>




    <?php  if(!isEmpty(Settings::$organisation?->organisation)): ?>

    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Oversigt</p>
            <p class="design-box mb-0 px-2 py-1">
                <i class="mdi mdi-store-outline font-16"></i>
                <span class=" font-16"><?=Settings::$organisation->organisation->name?></span>
            </p>
        </div>

    </div>

    <?php endif; ?>
</div>




