<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forhandler Dashboard";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>


        <div class="flex-row-end">

        </div>
    </div>


    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Oversigt</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Velkommen til dit WeePay forhandler dashboard</p>
    </div>




    <div class="row flex-align-stretch rg-15 mt-4">
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Total omsætning</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->netSales, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->netSalesLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->netSalesLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->netSalesLflMonth > 0 ? '+' : ''?>
                                <?=round($args->netSalesLflMonth, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-currency-usd color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Nye kunder</p>
                            <p class="font-22 font-weight-700"><?=$args->newCustomersCount?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->newCustomersLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->newCustomersLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->newCustomersLflMonth > 0 ? '+' : ''?>
                                <?=round($args->newCustomersLflMonth, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-account-heart-outline color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Transaktioner i dag</p>
                            <p class="font-22 font-weight-700"><?=$args->ordersTodayCount?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->todayOrdersCountLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->todayOrdersCountLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->todayOrdersCountLflMonth > 0 ? '+' : ''?>
                                <?=round($args->todayOrdersCountLflMonth, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-credit-card-outline color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center flex-nowrap g-075">
                        <div class="flex-col-start rg-025">
                            <p class="color-gray font-13 font-weight-medium">Kurvestørrelse</p>
                            <p class="font-22 font-weight-700"><?=number_format($args->orderAverage, 2) . currencySymbol("DKK")?></p>
                            <?php $colorClass = 'color-gray';
                            if($args->averageLflMonth > 0) $colorClass = 'color-green';
                            elseif($args->averageLflMonth < 0) $colorClass = 'color-danger'; ?>
                            <p class="<?=$colorClass?>">
                                <?=$args->averageLflMonth > 0 ? '+' : ''?>
                                <?=round($args->averageLflMonth, 2)?>%
                            </p>
                        </div>

                        <div class="flex-row-end">
                            <div class="square-50 bg-blue border-radius-10px flex-row-center-center">
                                <i class="mdi mdi-trending-up color-white font-30"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>




