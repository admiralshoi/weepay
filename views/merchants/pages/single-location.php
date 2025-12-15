<?php
/**
 * @var object $args
 */

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;

$location = $args->location;
$pageTitle = $location->name . " - Lokation";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
    var locations = <?=json_encode([$location])?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions, $args->slug);?>

        <div class="flex-row-end">
            <button class="btn-v2 mute-btn font-13 font-weight-medium flex-row-center-center cg-075"
                    onclick="LocationActions.editLocationDetails('<?=$location->uid?>')" name="edit_location_details">
                <i class="mdi mdi-cog-outline"></i>
                <span>Indstillinger</span>
            </button>
        </div>
    </div>



    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Overblik</p>
            <p class="mb-0 font-16 font-weight-medium color-gray"><?=$location->name?></p>
        </div>
        <div class="flex-row-end-center cg-075 flex-nowrap">
            <?php LocationPermissions::__oReadProtectedContent($location,  'team_members'); ?>
            <a href="<?=__url(Links::$merchant->locations->members($args->slug))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-account-multiple-outline"></i>
                <span class="text-nowrap">Medlemmer</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>
            <?php LocationPermissions::__oReadProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->pageBuilder($args->slug))?>" class="btn-v2 action-btn text-nowrap" >
                <i class="fa-regular fa-pen-to-square"></i>
                <span class="text-nowrap">Rediger side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>

    <?php LocationPermissions::__oReadProtectedContent($location, 'metrics'); ?>
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
    <?php LocationPermissions::__oEndContent(); ?>



    <?php LocationPermissions::__oReadProtectedContent($location, 'orders'); ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <i class="mdi mdi-cart-outline font-16 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle ordrer</p>
                    </div>

                    <div class="mt-2">
                        <table class="table table-hover">
                            <thead class="color-gray">
                            <th>Ordre ID</th>
                            <th>Dato & Tid</th>
                            <th>Kunde</th>
                            <th>Total</th>
                            <th>Net Total</th>
                            <th>Udestående</th>
                            <th>Risikoscore</th>
                            <th>Status</th>
                            <th>Handlinger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->orders->list() as $order): ?>
                                <tr>
                                    <td><?=$order->uid?></td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">Customer Name...</p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format($order->amount) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format($order->amount - $order->fee_amount) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format(0) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">NaN</p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">
                                            <?php if($order->status === 'COMPLETED'): ?>
                                                <span class="success-box">Gennemført</span>
                                            <?php elseif($order->status === 'DRAFT'): ?>
                                                <span class="mute-box">Draft</span>
                                            <?php elseif($order->status === 'PENDING'): ?>
                                                <span class="action-box">Afvikles</span>
                                            <?php elseif($order->status === 'CANCELLED'): ?>
                                                <span class="action-box">Cancelled</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td>
                                        <a href="<?=__url()?>" target="_blank" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                            <i class="mdi mdi-eye-outline font-16"></i>
                                            <span class="font-14">Detaljer</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php LocationPermissions::__oEndContent(); ?>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        LocationActions.init();
    })
</script>
<?php scriptEnd(); ?>



