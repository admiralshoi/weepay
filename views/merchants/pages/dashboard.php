<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forhandler Dashboard";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>


        <div class="flex-row-end">

        </div>
    </div>


    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Oversigt</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Velkommen til dit WeePay forhandler dashboard</p>
    </div>


</div>




