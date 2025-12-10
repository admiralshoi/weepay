<?php
/**
 * @var object $args
 */

$terminal = $args->terminal;

$pageTitle = "POS Start - {$terminal->location->name}";
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var terminalId = <?=json_encode($terminal->uid)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">


        <div class="flex-row-center-center mx-auto w-100 mxw-700px mt-3">
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



    </div>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        MerchantPOS.init();
    })
</script>
<?php scriptEnd(); ?>

