<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Siden er ikke klar";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>






<div class="page-content">


    <section class="w-100 py-6 px-3">
        <div class="flex-row-center w-100">
            <div class="w-100 mxw-700px mx-auto">
                <div class="note-info-box w-100">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                        <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-wrench-clock"></i></div>
                        <p class="mb-0 info-title">Siden er ikke klar</p>
                    </div>
                    <div class="info-content">
                        <p class="mb-0">Siden er endnu ikke klar til brug.</p>
                        <a href="<?=__url(Links::$app->home)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til <?=BRAND_NAME?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </section>


</div>