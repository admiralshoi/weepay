<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$session = $args->session;
$referenceBasket = $args->referenceBasket;
$terminal = $session->terminal;

$pageTitle = "POS købsdetaljer - {$terminal->location->name}";
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var terminalSessionId = <?=json_encode($session->uid)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">


        <div class="flex-row-center-center mx-auto w-100 mxw-700px mt-3">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center g-1">
                        <a class="cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
                           style="gap: .5rem;" href="<?=__url(Links::$merchant->terminals->posStart($args->slug, $terminal->uid))?>">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage</span>
                        </a>
                        <p class="design-box font-16 py-1 px-2">Terminal: <?=$terminal->name?></p>
                    </div>


                    <div class="flex-col-start flex-align-start mt-4" style="row-gap: 1rem;" id="basket_details">
                        <p class="font-weight-bold font-25">Indtast købsdetaljer</p>

                        <form class="flex-col-start rg-15 w-100" method="post">
                            <div class="flex-col-start rg-05">
                                <p class="font-weight-bold font-16">Kunde</p>
                                <input type="text" class="w-100 form-field-v2" disabled name="customer" value="<?=$session->customer?->full_name ?? "Ukendt"?>"/>
                            </div>
                            <div class="flex-col-start rg-05">
                                <p class="font-weight-bold font-16">Købsbeskrivelse</p>
                                <input type="text" class="w-100 form-field-v2" name="name"
                                       placeholder="Frisørbehandling, skægtrimning..." value="<?=$referenceBasket?->name ?? ''?>"/>
                            </div>
                            <div class="flex-col-start rg-05">
                                <p class="font-weight-bold font-16">Total Beløb (DKK)</p>
                                <input type="number" class="w-100 form-field-v2" name="price"
                                       placeholder="500.00" value="<?=$referenceBasket?->price ?? ''?>"/>
                            </div>
                            <div class="flex-col-start rg-05">
                                <p class="font-weight-bold font-16">Noter (Valgfri)</p>
                                <textarea class="w-100 form-field-v2" name="note" placeholder="Intern note der ikke er synlig for kunden"><?=$referenceBasket?->note ?? ''?></textarea
                            </div>

                            <div class="flex-row-center-center g-1 flex-1 mt-4">
                                <button class="btn-v2 danger-btn flex-row-center-center flex-nowrap" id="cancelBasket"
                                   style="gap: .5rem; display: none;"  onclick="MerchantPOS.cancelBasket(this)">
                                    <i class="mdi mdi-close"></i>
                                    <span>Annuller</span>
                                </button>
                                <button role="tab" class="btn-v2 action-btn flex-row-center-center flex-nowrap"
                                   style="gap: .5rem;" onclick="MerchantPOS.createBasket(this)" id="createBasket">
                                    <i class="mdi mdi-send"></i>
                                    <span>Send til kunde</span>
                                    <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                                          <span class="sr-only">Loading...</span>
                                        </span>
                                    </span>
                                </button>
                            </div>
                        </form>
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

