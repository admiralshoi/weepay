<?php
/**
 * @var object $args
 */


$pageTitle = "Siden er udløbet";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div class="page-content">
    <div class="flex-row-center organisation-container mt-4">
        <div class="card border-radius-10px w-100 organisation-join-card">
            <div class="card-body">
                <a class="mb-2 cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
                   style="gap: .5rem;" href="<?=$args->prevUrl?>">
                    <i class="mdi mdi-arrow-left"></i>
                    <span><?=$args->prevUrlTitle?></span>
                </a>
                <p class="font-25 font-weight-700 text-center">Siden er udløbet.</p>
                <p class="font-14 font-weight-medium text-gray text-center">Siden du prøver at tilgå er udløbet. Gå tilbage og prøv igen.</p>
            </div>
        </div>
    </div>
</div>