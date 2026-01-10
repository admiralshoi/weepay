<?php
/**
 * Marketing Materials Page
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Markedsføring";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "materials";
</script>

<div class="page-content">

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Markedsføringsmaterialer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Download marketingmaterialer til din butik</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body py-5">
                    <div class="flex-col-center flex-align-center text-center">
                        <div class="square-80 flex-row-center flex-align-center bg-blue-light border-radius-50 mb-4">
                            <i class="mdi mdi-image-multiple-outline font-40 color-blue"></i>
                        </div>
                        <h3 class="font-20 font-weight-bold mb-2">Kommer snart</h3>
                        <p class="font-14 color-gray mb-0" style="max-width: 400px;">
                            Her vil du kunne downloade markedsføringsmaterialer som plakater, skilte og andet materiale til din butik.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
