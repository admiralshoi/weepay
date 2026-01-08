<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$customer = $args->customer;
$firstOrderDate = $args->firstOrderDate;
$totalSpent = $args->totalSpent;
$orderCount = $args->orderCount;
$orders = $args->orders;

$pageTitle = "Kunde Detaljer - {$customer->full_name}";

?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "customers";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$merchant->customers)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til kunder</span>
        </a>
    </div>

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Kunde Detaljer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray"><?=$customer->full_name?></p>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-12 col-lg-8">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Kunde Information</p>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Fulde Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$customer->full_name ?? 'N/A'?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Email</p>
                            <p class="mb-0 font-14"><?=$customer->email ?? 'N/A'?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Telefon</p>
                            <p class="mb-0 font-14"><?=formatPhone($customer->phone, $customer->phone_country_code)?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Kunde ID</p>
                            <p class="mb-0 font-14 font-monospace"><?=$customer->uid?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Kunde Siden</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=!isEmpty($firstOrderDate) ? date("d/m-Y", strtotime($firstOrderDate)) : 'N/A'?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Account Status</p>
                            <?php if(!$customer->deactivated): ?>
                                <span class="success-box">Aktiv</span>
                            <?php else: ?>
                                <span class="danger-box">Suspenderet</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if(!isEmpty($customer->address_street) || !isEmpty($customer->address_city)): ?>
                    <div class="row mt-3 pt-3 border-top-card">
                        <div class="col-12">
                            <p class="mb-2 font-14 color-gray font-weight-medium">Adresse</p>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <?php if(!isEmpty($customer->address_street)): ?>
                                <p class="mb-0 font-14"><?=$customer->address_street?></p>
                                <?php endif; ?>

                                <?php if(!isEmpty($customer->address_city) || !isEmpty($customer->address_zip)): ?>
                                <p class="mb-0 font-14">
                                    <?=trim(($customer->address_zip ?? '') . ' ' . ($customer->address_city ?? ''))?>
                                </p>
                                <?php endif; ?>

                                <?php if(!isEmpty($customer->address_region)): ?>
                                <p class="mb-0 font-14"><?=$customer->address_region?></p>
                                <?php endif; ?>

                                <?php if(!isEmpty($customer->address_country)): ?>
                                <p class="mb-0 font-14 font-weight-medium"><?=$customer->address_country?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card border-radius-10px sticky-top mb-4" style="top: 1rem;">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-chart-line font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Statistik</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: 1rem;">
                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Total Forbrug</p>
                            <p class="mb-0 font-22 font-weight-bold color-success-text"><?=number_format($totalSpent, 2)?> DKK</p>
                        </div>

                        <div class="flex-col-start pb-3 border-bottom-card">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Antal Ordrer</p>
                            <p class="mb-0 font-18 font-weight-bold"><?=$orderCount?></p>
                        </div>

                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Gennemsnitlig Ordre</p>
                            <p class="mb-0 font-16 font-weight-medium">
                                <?=$orderCount > 0 ? number_format($totalSpent / $orderCount, 2) : '0.00'?> DKK
                            </p>
                        </div>

                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Første Ordre</p>
                            <p class="mb-0 font-14">
                                <?=!isEmpty($firstOrderDate) ? date("d/m-Y", strtotime($firstOrderDate)) : 'N/A'?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Sidebar -->
        <div class="col-12">
            <!-- Order History -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-history font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Ordre Historik</p>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table plainDataTable table-hover" id="orderHistoryTable">
                            <thead class="color-gray">
                            <tr>
                                <th>Ordre ID</th>
                                <th>Dato</th>
                                <th>Lokation</th>
                                <th>Beløb</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if($orders->count() > 0): ?>
                                <?php foreach ($orders->list() as $order): ?>
                                    <tr>
                                        <td>
                                            <p class="mb-0 font-12 font-monospace"><?=$order->uid?></p>
                                        </td>
                                        <td data-order="<?=strtotime($order->created_at)?>">
                                            <p class="mb-0 font-12"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=$order->location->name ?? 'N/A'?></p>
                                        </td>
                                        <td data-order="<?=$order->amount?>">
                                            <p class="mb-0 font-12 font-weight-medium"><?=number_format($order->amount, 2) . ' ' . currencySymbol($order->currency)?></p>
                                        </td>
                                        <td>
                                            <?php if($order->status === 'COMPLETED'): ?>
                                                <span class="success-box font-12">Gennemført</span>
                                            <?php elseif($order->status === 'DRAFT'): ?>
                                                <span class="mute-box font-12">Draft</span>
                                            <?php elseif($order->status === 'PENDING'): ?>
                                                <span class="action-box font-12">Afvikles</span>
                                            <?php elseif($order->status === 'CANCELLED'): ?>
                                                <span class="danger-box font-12">Annulleret</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?=__url(Links::$merchant->orderDetail($order->uid))?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem; ">
                                                <i class="mdi mdi-eye-outline font-16"></i>
                                                <span class="text-sm">Se detaljer</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <p class="mb-0 color-gray font-14 py-3">Ingen ordrer fundet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>
