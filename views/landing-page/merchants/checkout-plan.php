<?php
/**
 * @var object $args
 */

?>



<div class="mt-3 flex-row-center">

    <div class="w-500px flex-col-start" style="gap: 1rem;">

        <div class="w-100 flex-col-start flex-align-center" style="gap: 0;">
            <p class="mb-0 font-22 font-weight-bold text-center">Vælg betalingsplan</p>
            <p class="mb-0 font-14 color-gray font-weight-medium text-center">Vælg den løsning der passer dig bedst</p>
        </div>


        <div class="w-500px flex-col-start mt-3" style="gap: 1rem;">
            <div class="row">
                <div class="col-12">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem;">
                                <p class="mb-0 font-16 font-weight-bold">Betalingsplan 1...</p>
                                <p class="mb-0 font-16 font-weight-medium"><?=number_format($args->product->price, 2)?> <?=currencySymbol($args->product->currency)?></p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-100 flex-col-start" style="gap: 1rem;">
            <a href="<?=$args->payLink?>" class="btn-v2 design-action-btn-lg">
                Fortsæt til bekræftelse
                <i class="mdi mdi-arrow-right"></i>
            </a>
        </div>
    </div>


</div>

