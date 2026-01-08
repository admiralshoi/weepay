<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location;
$ordersList = $args->ordersList;
$totalSpent = $args->totalSpent;
$orderCount = $args->orderCount;
$publicPageUrl = $args->publicPageUrl;
$locationPage = $args->locationPage ?? null;

$address = toArray($location->address ?? []);
$pageTitle = $location->name ?? "Butik Detaljer";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "dashboard";
</script>

<div class="page-content">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$consumer->dashboard)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til oversigt</span>
        </a>
    </div>

    <!-- Hero Section -->
    <?php if(!isEmpty($locationPage) && !isEmpty($locationPage->hero_image)): ?>
    <div class="mb-4 border-radius-10px overflow-hidden" style="height: 200px; position: relative;">
        <img src="<?=__url($locationPage->hero_image)?>" alt="<?=$location->name?>"
             style="width: 100%; height: 100%; object-fit: cover;">
        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 1.5rem;">
            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 1rem;">
                <?php if(!isEmpty($locationPage->logo)): ?>
                <img src="<?=__url($locationPage->logo)?>" alt="Logo"
                     class="border-radius-10px bg-white" style="width: 60px; height: 60px; object-fit: contain; padding: 5px;">
                <?php endif; ?>
                <div>
                    <p class="mb-0 font-24 font-weight-bold color-white"><?=$location->name?></p>
                    <?php if(!isEmpty($location->caption)): ?>
                    <p class="mb-0 font-14 color-white" style="opacity: 0.9;"><?=$location->caption?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="flex-col-start mb-4">
        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: 1rem;">
            <?php if(!isEmpty($locationPage) && !isEmpty($locationPage->logo)): ?>
            <img src="<?=__url($locationPage->logo)?>" alt="Logo"
                 class="border-radius-10px bg-lighter-blue" style="width: 60px; height: 60px; object-fit: contain; padding: 5px;">
            <?php else: ?>
            <div class="square-60 bg-lighter-blue border-radius-10px flex-row-center-center">
                <i class="mdi mdi-store font-30 color-blue"></i>
            </div>
            <?php endif; ?>
            <div>
                <p class="mb-0 font-30 font-weight-bold"><?=$location->name?></p>
                <?php if(!isEmpty($location->caption)): ?>
                <p class="mb-0 font-16 color-gray"><?=$location->caption?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Location Info and Stats -->
        <div class="col-12 col-lg-4">
            <!-- Stats Card -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-chart-bar font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Dit forbrug her</p>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Total brugt</p>
                            <p class="mb-0 font-18 font-weight-bold"><?=number_format($totalSpent, 2)?> <?=currencySymbol($location->default_currency ?? 'DKK')?></p>
                        </div>
                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Antal ordrer</p>
                            <p class="mb-0 font-18 font-weight-bold"><?=$orderCount?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Info Card -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-information-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Butik Information</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: 1rem;">
                        <?php if(!isEmpty($address)): ?>
                        <div>
                            <p class="mb-1 font-13 color-gray font-weight-medium">Adresse</p>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <?php if(!isEmpty($address['line_1'])): ?>
                                <p class="mb-0 font-14"><?=htmlspecialchars($address['line_1'])?></p>
                                <?php endif; ?>
                                <?php if(!isEmpty($address['city']) || !isEmpty($address['postal_code'])): ?>
                                <p class="mb-0 font-14">
                                    <?=trim(htmlspecialchars(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? '')))?>
                                </p>
                                <?php endif; ?>
                                <?php if(!isEmpty($address['country'])): ?>
                                <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($address['country'])?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->contact_phone)): ?>
                        <div>
                            <p class="mb-1 font-13 color-gray font-weight-medium">Telefon</p>
                            <p class="mb-0 font-14">
                                <a href="tel:<?=htmlspecialchars($location->contact_phone)?>" class="color-blue hover-underline">
                                    <?=\classes\Methods::locations()->contactPhone($location)?>
                                </a>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->contact_email)): ?>
                        <div>
                            <p class="mb-1 font-13 color-gray font-weight-medium">Email</p>
                            <p class="mb-0 font-14">
                                <a href="mailto:<?=htmlspecialchars($location->contact_email)?>" class="color-blue hover-underline">
                                    <?=\classes\Methods::locations()->contactEmail($location)?>
                                </a>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <?php if(!isEmpty($publicPageUrl)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-link-variant font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Links</p>
                    </div>

                    <a href="<?=htmlspecialchars($publicPageUrl)?>" target="_blank" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-open-in-new font-16"></i>
                        <span class="font-14">Besøg butikkens side</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Orders List -->
        <div class="col-12 col-lg-8">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-cart-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Dine ordrer hos <?=htmlspecialchars($location->name)?></p>
                    </div>

                    <?php if($orderCount === 0): ?>
                        <div class="flex-col-center py-4">
                            <i class="mdi mdi-cart-off font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen ordrer hos denne butik endnu</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table-v2">
                                <thead>
                                <tr>
                                    <th>Ordre ID</th>
                                    <th>Dato</th>
                                    <th>Beløb</th>
                                    <th>Betalingsplan</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($ordersList->list() as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?=__url(Links::$consumer->orderDetail($order->uid))?>" class="color-blue hover-underline font-monospace">
                                                <?=substr($order->uid, 0, 8)?>
                                            </a>
                                        </td>
                                        <td>
                                            <p class="mb-0 text-sm"><?=date("d/m/Y H:i", strtotime($order->created_at))?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 text-sm font-weight-medium"><?=number_format($order->amount, 2)?> <?=currencySymbol($order->currency)?></p>
                                        </td>
                                        <td>
                                            <?php if($order->payment_plan === 'installments'): ?>
                                                <span class="action-box">Afdrag</span>
                                            <?php elseif($order->payment_plan === 'pushed'): ?>
                                                <span class="action-box">Udskudt</span>
                                            <?php else: ?>
                                                <span class="success-box">Fuld betaling</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($order->status === 'COMPLETED'): ?>
                                                <span class="success-box">Gennemført</span>
                                            <?php elseif($order->status === 'PENDING'): ?>
                                                <span class="action-box">Afvikles</span>
                                            <?php elseif($order->status === 'CANCELLED'): ?>
                                                <span class="danger-box">Annulleret</span>
                                            <?php else: ?>
                                                <span class="mute-box"><?=$order->status?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
