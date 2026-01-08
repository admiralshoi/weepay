<?php
/**
 * @var object $args
 */

$pageTitle = "Betaling";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>



<div class="mt-3 flex-row-center">

    <div class="w-500px flex-col-start" style="gap: 1rem;">
        <div class="card border-radius-10px w-100">
            <div class="card-body">
                <div class="w-100 flex-col-start" style="gap: 1rem;">
                    <p class="mb-0 font-22 font-weight-bold"><?=$args->merchant->name?></p>

                    <div class="w-100 flex-col-start" style="gap: .5rem;">
                        <p class="mb-0 font-16 font-weight-bold">Order Detaljer</p>
                        <div class="flex-row-between flex-align-center flex-nowrap" style="gap: .5rem;">
                            <p class="mb-0 font-14"><?=$args->product->name?></p>
                            <p class="mb-0 font-16 font-weight-medium"><?=number_format($args->product->price, 2)?> <?=currencySymbol($args->product->currency)?></p>

                        </div>
                    </div>

                    <a href="<?=$args->userAuthenticationUrl?>" class="btn-v2 design-action-btn-lg">
                        Login for at fortsætte
                        <i class="mdi mdi-shield-outline"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>


</div>

