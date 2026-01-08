<?php
/**
 * @var object $args
 */

$pageTitle = "Bekræft og betal";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>



<div class="mt-3 flex-row-center">

    <div class="w-500px flex-col-start" style="gap: 1rem;">

        <div class="w-100 flex-col-start flex-align-center" style="gap: 0;">
            <p class="mb-0 font-22 font-weight-bold text-center">Bekræft og Betal</p>
            <p class="mb-0 font-14 color-gray font-weight-medium text-center">Gennemgå og bekræft din betaling</p>
        </div>


        <div class="w-500px flex-col-start mt-3" style="gap: 1rem;">
            <div class="row">
                <div class="col-12">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem;">
                                <p class="mb-0 font-16"><?=$args->product->name?></p>
                                <p class="mb-0 font-16 font-weight-medium"><?=number_format($args->product->price, 2)?> <?=currencySymbol($args->product->currency)?></p>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem;">
                                <p class="mb-0 font-16 font-weight-bold">Betalingsmetode 1...</p>
                                <p class="mb-0 font-16 font-weight-medium">Apple Pay!</p>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-100 flex-col-start" style="gap: 1rem;">
            <a href="<?=$args->payNowLink?>" class="btn-v2 green-btn">
                <i class="mdi mdi-shield-outline"></i>
                Betal med Apple Pay
            </a>
        </div>
    </div>


</div>

