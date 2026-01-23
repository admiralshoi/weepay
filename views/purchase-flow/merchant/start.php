<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$terminal = $args->terminal;
$slug = $args->slug ?? $terminal->location->slug;
$locationId = $args->locationId ?? $terminal->location->uid;

$pageTitle = "POS Start - {$terminal->location->name}";
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var terminalId = <?=json_encode($terminal->uid)?>;
    var locationId = <?=json_encode($locationId)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content" style="max-width: 1200px;">

        <div class="row" style="row-gap: 2rem;">
            <!-- Main POS Card -->
            <div class="col-12 col-xl-8">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-row-end">
                            <p class="design-box font-16 py-1 px-2">Terminal: <?=$terminal->name?></p>
                        </div>
                        <div class="flex-col-start flex-align-center" style="row-gap: 1.5rem;" id="awaiting_customers">
                            <div class="square-100 border-radius-50 flex-row-center-center card-border">
                                <i class="mdi mdi-qrcode font-50 color-blue"></i>
                            </div>
                            <p class="font-weight-bold font-25">Afventer kunder</p>
                            <p class="font-weight-medium color-gray font-18">Venter på QR-scanning af kunde</p>
                        </div>


                        <div class="flex-col-start flex-align-center" style="row-gap: 1.5rem; display: none;" id="session_container">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                <p class="mb-0 font-22 font-weight-bold">Afventende kunder</p>
                            </div>

                            <div class="mt-2 w-100">
                                <table class="table table-hover">
                                    <thead class="color-gray">
                                    <th>ID</th>
                                    <th>Kunde</th>
                                    <th>Tid</th>
                                    <th>Status</th>
                                    <th>Handling</th>
                                    </thead>
                                    <tbody id="session_body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Checkouts Sidebar -->
            <div class="col-12 col-xl-4">
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-row-between flex-align-center mb-2">
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <i class="mdi mdi-receipt font-20 color-blue"></i>
                                <p class="mb-0 font-18 font-weight-bold">Dagens salg</p>
                            </div>
                            <a href="<?=__url(Links::$merchant->orders)?>" target="_blank" class="font-12 color-blue">
                                <i class="mdi mdi-open-in-new"></i> Alle ordrer
                            </a>
                        </div>

                        <!-- Toggle -->
                        <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                            <button class="btn-v2 action-btn sales-toggle-btn active" data-show-all="0">Mine (<span id="my-sales-count">0</span>)</button>
                            <button class="btn-v2 action-btn sales-toggle-btn" data-show-all="1">Alle (<span id="all-sales-count">-</span>)</button>
                        </div>

                        <!-- Sales List Container -->
                        <div id="sales-list-container">
                            <div class="flex-col-center flex-align-center py-4" style="row-gap: .5rem;">
                                <span class="spinner-border color-blue square-30" style="border-width: 3px;"></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        MerchantPOS.init();
    })
</script>
<?php scriptEnd(); ?>

