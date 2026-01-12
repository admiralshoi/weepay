<?php
/**
 * Admin Dashboard - Payment Detail
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$payment = $args->payment ?? null;
$order = $args->order ?? null;
$customer = $args->user ?? null;
$orderPayments = $args->allPayments ?? new \Database\Collection();
$organisation = $args->organisation ?? null;
$location = $args->location ?? null;
$provider = $args->provider ?? null;

$pageTitle = $payment ? ("Betaling: " . $payment->uid) : "Betaling detaljer";

// Status mapping
$paymentStatusMap = [
    'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
    'PENDING' => ['label' => 'Afventer', 'class' => 'warning-box'],
    'SCHEDULED' => ['label' => 'Planlagt', 'class' => 'info-box'],
    'PAST_DUE' => ['label' => 'Forsinket', 'class' => 'danger-box'],
    'FAILED' => ['label' => 'Fejlet', 'class' => 'danger-box'],
    'CANCELLED' => ['label' => 'Annulleret', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'Refunderet', 'class' => 'mute-box'],
];

$statusInfo = $paymentStatusMap[$payment->status ?? 'PENDING'] ?? ['label' => 'Ukendt', 'class' => 'mute-box'];

// Calculate total payments for this order
$totalPayments = 0;
$completedPayments = 0;
if(!$orderPayments->empty()) {
    $totalPayments = $orderPayments->count();
    $completedPayments = $orderPayments->filter(fn($p) => $p['status'] === 'COMPLETED')->count();
}

// Extract user info
$userName = is_object($customer) ? ($customer->full_name ?? $customer->email ?? '-') : '-';
$userUid = is_object($customer) ? $customer->uid : $customer;

// Extract organisation/location info
$orgName = is_object($organisation) ? ($organisation->name ?? '-') : '-';
$orgUid = is_object($organisation) ? $organisation->uid : ($payment->organisation ?? null);
$locName = is_object($location) ? ($location->name ?? '-') : '-';
$locUid = is_object($location) ? $location->uid : ($payment->location ?? null);

// Order info
$orderUid = is_object($order) ? $order->uid : ($payment->order ?? null);
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->dashboardPayments)?>" class="font-13 color-gray hover-color-blue">Betalinger</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=htmlspecialchars($payment->uid ?? 'Betaling')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-start w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                    <div class="square-70 bg-light-gray border-radius-8px flex-row-center-center">
                        <i class="mdi mdi-credit-card-outline font-40 color-info"></i>
                    </div>
                    <div class="flex-col-start">
                        <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($payment->uid ?? 'Unavngivet')?></h1>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="<?=$statusInfo['class']?> font-11"><?=$statusInfo['label']?></span>
                            <span class="font-12 color-gray"><?=number_format($payment->amount ?? 0, 2, ',', '.')?> <?=htmlspecialchars($payment->currency ?? 'DKK')?></span>
                        </div>
                    </div>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <a href="<?=__url(Links::$admin->dashboardPayments)?>" class="btn-v2 trans-btn">
                        <i class="mdi mdi-arrow-left mr-1"></i> Tilbage
                    </a>
                </div>
            </div>

            <div class="row rg-15">
                <!-- Left Column - Payment Info -->
                <div class="col-12 col-lg-8">
                    <!-- Payment Information -->
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-credit-card-outline font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Betaling Information</p>
                            </div>

                            <div class="row">
                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Status</p>
                                    <span class="<?=$statusInfo['class']?> font-11"><?=$statusInfo['label']?></span>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Beløb</p>
                                    <p class="mb-0 font-18 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success' : ($payment->status === 'PAST_DUE' ? 'color-danger' : '')?>"><?=number_format($payment->amount ?? 0, 2, ',', '.')?> <?=htmlspecialchars($payment->currency ?? 'DKK')?></p>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Valuta</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($payment->currency ?? 'DKK')?></p>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Forfaldsdato</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=$payment->due_date ? date("d/m/Y", strtotime($payment->due_date)) : '-'?></p>
                                </div>

                                <?php if(!empty($payment->paid_at)): ?>
                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Betalt</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=date("d/m/Y H:i", strtotime($payment->paid_at))?></p>
                                </div>
                                <?php endif; ?>

                                <?php if($totalPayments > 1): ?>
                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Rate</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=$payment->installment_number?> af <?=$totalPayments?></p>
                                </div>
                                <?php endif; ?>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray"><?=Translate::word("Organisation")?></p>
                                    <?php if($orgUid): ?>
                                    <a href="<?=__url(Links::$admin->dashboardOrganisationDetail($orgUid))?>" class="font-14 font-weight-medium color-blue hover-underline"><?=htmlspecialchars($orgName)?></a>
                                    <?php else: ?>
                                    <p class="mb-0 font-14 font-weight-medium">-</p>
                                    <?php endif; ?>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Lokation</p>
                                    <?php if($locUid): ?>
                                    <a href="<?=__url(Links::$admin->dashboardLocationDetail($locUid))?>" class="font-14 font-weight-medium color-blue hover-underline"><?=htmlspecialchars($locName)?></a>
                                    <?php else: ?>
                                    <p class="mb-0 font-14 font-weight-medium">-</p>
                                    <?php endif; ?>
                                </div>

                                <?php if(!empty($payment->prid)): ?>
                                <div class="col-12 mb-3">
                                    <p class="mb-1 font-12 color-gray">Provider Reference ID</p>
                                    <p class="mb-0 font-14 font-weight-medium font-monospace"><?=htmlspecialchars($payment->prid)?></p>
                                </div>
                                <?php endif; ?>

                                <?php if(!empty($payment->failure_reason)): ?>
                                <div class="col-12 mb-3">
                                    <p class="mb-1 font-12 color-gray">Fejlbeskrivelse</p>
                                    <p class="mb-0 font-14 color-danger"><?=htmlspecialchars($payment->failure_reason)?></p>
                                </div>
                                <?php endif; ?>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14"><?=$payment->created_at ? date("d/m/Y H:i", strtotime($payment->created_at)) : '-'?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <?php if($order): ?>
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3" style="gap: .5rem;">
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <i class="mdi mdi-cart-outline font-18 color-blue"></i>
                                    <p class="mb-0 font-16 font-weight-bold">Tilhørende Ordre</p>
                                </div>
                                <a href="<?=__url(Links::$admin->dashboardOrderDetail($orderUid))?>" class="btn-v2 action-btn font-12">
                                    <i class="mdi mdi-eye-outline mr-1"></i> Se Ordre
                                </a>
                            </div>

                            <div class="row">
                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Ordre ID</p>
                                    <p class="mb-0 font-14 font-monospace"><?=htmlspecialchars($order->uid)?></p>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Total Beløb</p>
                                    <p class="mb-0 font-14 font-weight-bold"><?=number_format($order->amount ?? 0, 2, ',', '.')?> <?=htmlspecialchars($order->currency ?? 'DKK')?></p>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Betalingsplan</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($order->payment_plan ?? '-')?></p>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Ordre Status</p>
                                    <?php if(($order->status ?? '') === 'COMPLETED'): ?>
                                        <span class="success-box font-11">Gennemført</span>
                                    <?php elseif(($order->status ?? '') === 'DRAFT'): ?>
                                        <span class="mute-box font-11">Kladde</span>
                                    <?php elseif(($order->status ?? '') === 'PENDING'): ?>
                                        <span class="warning-box font-11">Afventer</span>
                                    <?php elseif(($order->status ?? '') === 'CANCELLED'): ?>
                                        <span class="danger-box font-11">Annulleret</span>
                                    <?php else: ?>
                                        <span class="mute-box font-11"><?=htmlspecialchars($order->status ?? '-')?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14"><?=$order->created_at ? date("d/m/Y H:i", strtotime($order->created_at)) : '-'?></p>
                                </div>

                                <?php if($totalPayments > 1): ?>
                                <div class="col-6 col-md-4 mb-3">
                                    <p class="mb-1 font-12 color-gray">Betalingsfremdrift</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=$completedPayments?> / <?=$totalPayments?> betalinger</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Customer Information -->
                    <?php if($customer): ?>
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3" style="gap: .5rem;">
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <i class="mdi mdi-account-outline font-18 color-blue"></i>
                                    <p class="mb-0 font-16 font-weight-bold">Kunde Information</p>
                                </div>
                                <a href="<?=__url(Links::$admin->dashboardUserDetail($userUid))?>" class="btn-v2 action-btn font-12">
                                    <i class="mdi mdi-account-details mr-1"></i> Se Kunde
                                </a>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <p class="mb-1 font-12 color-gray">Fulde Navn</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($customer->full_name ?? '-')?></p>
                                </div>

                                <div class="col-6 mb-3">
                                    <p class="mb-1 font-12 color-gray">Email</p>
                                    <p class="mb-0 font-14"><?=htmlspecialchars($customer->email ?? '-')?></p>
                                </div>

                                <div class="col-6 mb-3">
                                    <p class="mb-1 font-12 color-gray">Telefon</p>
                                    <p class="mb-0 font-14">
                                        <?php if(!empty($customer->phone)): ?>
                                            <?=!empty($customer->phone_country_code) ? '+' . $customer->phone_country_code . ' ' : ''?><?=htmlspecialchars($customer->phone)?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="col-6 mb-3">
                                    <p class="mb-1 font-12 color-gray">Kunde ID</p>
                                    <p class="mb-0 font-14 font-monospace"><?=htmlspecialchars($customer->uid ?? '-')?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column - Actions & Summary -->
                <div class="col-12 col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-lightning-bolt font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Handlinger</p>
                            </div>

                            <div class="flex-col-start" style="gap: .75rem;">
                                <?php if($orderUid): ?>
                                <a href="<?=__url(Links::$admin->dashboardOrderDetail($orderUid))?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                                    <i class="mdi mdi-cart-outline font-16"></i>
                                    <span class="font-14">Se Ordre</span>
                                </a>
                                <?php endif; ?>

                                <?php if($userUid): ?>
                                <a href="<?=__url(Links::$admin->dashboardUserDetail($userUid))?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                                    <i class="mdi mdi-account font-16"></i>
                                    <span class="font-14">Se Kunde</span>
                                </a>
                                <?php endif; ?>

                                <?php if($payment->status === 'COMPLETED'): ?>
                                <button type="button" class="btn-v2 danger-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" data-refund-payment="<?=$payment->uid?>">
                                    <i class="mdi mdi-cash-refund font-16"></i>
                                    <span class="font-14">Refunder</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-cash-multiple font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Betalingsoversigt</p>
                            </div>

                            <div class="flex-col-start" style="gap: .75rem;">
                                <div class="flex-row-between-center pb-3 border-bottom">
                                    <p class="mb-0 font-14 color-gray">Beløb</p>
                                    <p class="mb-0 font-18 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success' : ''?>"><?=number_format($payment->amount ?? 0, 2, ',', '.')?> <?=htmlspecialchars($payment->currency ?? 'DKK')?></p>
                                </div>

                                <?php if(!empty($payment->fee_amount) && $payment->fee_amount > 0): ?>
                                <div class="flex-row-between-center">
                                    <p class="mb-0 font-14 color-gray">Gebyr</p>
                                    <p class="mb-0 font-14"><?=number_format($payment->fee_amount, 2, ',', '.')?> <?=htmlspecialchars($payment->currency ?? 'DKK')?></p>
                                </div>
                                <?php endif; ?>

                                <?php if(!isEmpty($provider)): ?>
                                <div class="flex-row-between-center">
                                    <p class="mb-0 font-14 color-gray">Provider</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($provider->name ?? '-')?></p>
                                </div>
                                <?php endif; ?>

                                <?php if(!empty($payment->test) && $payment->test): ?>
                                <div class="p-2 bg-warning-light border-radius-8px mt-2">
                                    <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                        <i class="mdi mdi-flask-outline font-16 color-warning"></i>
                                        <p class="mb-0 font-13 font-weight-medium">Test Transaktion</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Installment Progress (if multiple payments) -->
                    <?php if(!$orderPayments->empty() && $orderPayments->count() > 1): ?>
                    <div class="card border-radius-10px mb-3">
                        <div class="card-body">
                            <div class="flex-row-start flex-align-center mb-3" style="gap: .5rem;">
                                <i class="mdi mdi-format-list-numbered font-18 color-blue"></i>
                                <p class="mb-0 font-16 font-weight-bold">Alle Rater</p>
                            </div>

                            <div class="flex-col-start" style="gap: .5rem;">
                                <?php foreach($orderPayments->list() as $installment): ?>
                                    <?php
                                    $instStatusInfo = $paymentStatusMap[$installment->status] ?? ['label' => $installment->status, 'class' => 'mute-box'];
                                    $isCurrent = $installment->uid === $payment->uid;
                                    ?>
                                    <?php if($isCurrent): ?>
                                    <div class="flex-row-between flex-align-center p-2 border-radius-8px bg-info-light" style="border: 1px solid var(--color-info);">
                                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                            <span class="font-12 font-weight-bold"><?=$installment->installment_number?>.</span>
                                            <span class="font-12 font-weight-medium"><?=number_format($installment->amount, 2, ',', '.')?> <?=htmlspecialchars($installment->currency ?? 'DKK')?></span>
                                        </div>
                                        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                            <span class="font-11 color-gray"><?=$installment->due_date ? date("d/m", strtotime($installment->due_date)) : '-'?></span>
                                            <span class="<?=$instStatusInfo['class']?> font-10"><?=$instStatusInfo['label']?></span>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <a href="<?=__url(Links::$admin->dashboardPaymentDetail($installment->uid))?>"
                                       class="flex-row-between flex-align-center p-2 border-radius-8px bg-light-gray hover-bg-lighter cursor-pointer" style="text-decoration: none; color: inherit; border: 1px solid transparent; transition: border-color 0.2s;" onmouseover="this.style.borderColor='var(--color-blue)'" onmouseout="this.style.borderColor='transparent'">
                                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                            <span class="font-12 font-weight-bold color-blue"><?=$installment->installment_number?>.</span>
                                            <span class="font-12 font-weight-medium"><?=number_format($installment->amount, 2, ',', '.')?> <?=htmlspecialchars($installment->currency ?? 'DKK')?></span>
                                        </div>
                                        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                            <span class="font-11 color-gray"><?=$installment->due_date ? date("d/m", strtotime($installment->due_date)) : '-'?></span>
                                            <span class="<?=$instStatusInfo['class']?> font-10"><?=$instStatusInfo['label']?></span>
                                            <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                                        </div>
                                    </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
