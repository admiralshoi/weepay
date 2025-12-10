<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$session = $args->session;
$terminal = $args->terminal;
$customer = $args->customer;
$basket = $args->basket;

$pageTitle = "POS Checkout - {$terminal->location->name}";
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var terminalSessionId = <?=json_encode($session->uid)?>;
    var terminalSessionBasket = <?=json_encode($basket->uid)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">


        <div class="flex-row-center-center mx-auto w-100 mxw-700px mt-3">
            <div class="flex-col-start rg-15 w-100">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-row-between-center g-1">
                            <p class="design-box font-16 py-1 px-2">Terminal: <?=$terminal->name?></p>
                        </div>

                        <div class="flex-col-start flex-align-center" style="row-gap: 1.5rem;">
                            <div class="square-100 border-radius-50 flex-row-center-center card-border bg-lighter-blue">
                                <i class="mdi mdi-clock-outline font-50 color-blue"></i>
                            </div>
                            <div class="flex-col-start flex-align-center" id="page-title-pending" style="row-gap: 1.5rem;">
                                <p class="font-weight-bold font-25">Venter på kundens godkendelse...</p>
                                <p class="font-weight-medium color-gray font-18">Kunden gennemgår betalingsflowet på deres enhed</p>
                            </div>
                            <p class="font-weight-bold font-25 color-danger-text" id="page-title-void" style="display: none">Købet er blevet afbrud</p>
                        </div>
                    </div>
                </div>

                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-col-start rg-075">
                            <p class="font-weight-bold font-18">Købsdetaljer</p>
                            <div class="flex-col-start rg-075">
                                <div class="row bg-lighter-blue border-radius-10px p-3">
                                    <div class="col-12 col-md-6">
                                        <div class="flex-col-start">
                                            <p class="color-gray font-16 font-weight-medium">Beløb</p>
                                            <p class="font-18 font-weight-bold">
                                                <?=number_format($basket->price, 2) . currencySymbol($basket->currency)?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="flex-col-start">
                                            <p class="color-gray font-16 font-weight-medium">Status</p>
                                            <p class="font-18 font-weight-bold color-blue" id="session-status">Afventer</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row bg-lighter-blue border-radius-10px p-3">
                                    <div class="col-12">
                                        <div class="flex-col-start">
                                            <p class="color-gray font-16 font-weight-medium">Noter</p>
                                            <p class="font-14 font-weight-medium">
                                                <?=$basket->note?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex-row-center-center flex-1 flex-wrap cg-1 rg-075">
                    <button class="btn-v2 h-45px font-weight-bold font-16 mute-btn flex-row-center-center cg-1 flex-nowrap" id="edit-basket">
                        <i class="mdi mdi-square-edit-outline"></i>
                        <span>Rediger køb</span>
                        <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                            <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                              <span class="sr-only">Loading...</span>
                            </span>
                        </span>
                    </button>
                    <button class="btn-v2 h-45px font-weight-bold font-16 danger-btn-outline flex-row-center-center cg-1 flex-nowrap" id="void-session">
                        <i class="mdi mdi-square-edit-outline"></i>
                        <span>Annuller køb</span>
                        <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                            <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                              <span class="sr-only">Loading...</span>
                            </span>
                        </span>
                    </button>

                    <a href="<?=__url(Links::$merchant->terminals->posStart($terminal->location->slug, $terminal->uid))?>" style="display: none;"
                       class="btn-v2 h-45px font-weight-bold font-16 action-btn flex-row-center-center cg-1 flex-nowrap" id="back-to-start">
                        <i class="mdi mdi-arrow-left"></i>
                        <span>Tilbage til start</span>
                    </a>
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

