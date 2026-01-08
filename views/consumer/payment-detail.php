<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$payment = $args->payment;
$order = $args->order;
$orderPayments = $args->orderPayments;
$location = $payment->location;

$pageTitle = "Betaling Detaljer";

// Status mapping
$paymentStatusMap = [
    'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
    'PENDING' => ['label' => 'Afventer', 'class' => 'action-box'],
    'SCHEDULED' => ['label' => 'Planlagt', 'class' => 'mute-box'],
    'PAST_DUE' => ['label' => 'Forsinket', 'class' => 'danger-box'],
    'FAILED' => ['label' => 'Fejlet', 'class' => 'danger-box'],
    'CANCELLED' => ['label' => 'Annulleret', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'Refunderet', 'class' => 'warning-box'],
];

$statusInfo = $paymentStatusMap[$payment->status] ?? ['label' => $payment->status, 'class' => 'mute-box'];

// Calculate total payments for this order
$totalPayments = 0;
$completedPayments = 0;
if(!isEmpty($orderPayments)) {
    $totalPayments = $orderPayments->count();
    $completedPayments = $orderPayments->filter(fn($p) => $p['status'] === 'COMPLETED')->count();
}
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til betalinger</span>
        </a>
    </div>

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Betaling Detaljer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Betaling ID: <?=$payment->uid?></p>
    </div>

    <div class="row">
        <!-- Payment Information -->
        <div class="col-12 col-lg-8">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-credit-card-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Betaling Information</p>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Status</p>
                            <span class="<?=$statusInfo['class']?>"><?=$statusInfo['label']?></span>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Beløb</p>
                            <p class="mb-0 font-18 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success-text' : ($payment->status === 'PAST_DUE' ? 'color-red' : '')?>"><?=number_format($payment->amount, 2) . ' ' . currencySymbol($payment->currency)?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Valuta</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$payment->currency?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Forfaldsdato</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=date("d/m-Y", strtotime($payment->due_date))?></p>
                        </div>

                        <?php if(!isEmpty($payment->paid_at)): ?>
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalt</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=date("d/m-Y H:i", strtotime($payment->paid_at))?></p>
                        </div>
                        <?php endif; ?>

                        <?php if($totalPayments > 1): ?>
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Rate</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$payment->installment_number?> af <?=$totalPayments?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Store/Location Information -->
            <?php if(!isEmpty($location)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-store-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Butik Information</p>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Butiksnavn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$location->name ?? 'N/A'?></p>
                        </div>

                        <?php if(!isEmpty($location->cvr)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">CVR</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$location->cvr?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->address)): ?>
                        <?php $addressString = \classes\utility\Misc::extractCompanyAddressString($location->address); ?>
                        <?php if(!isEmpty($addressString)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Adresse</p>
                            <p class="mb-0 font-14"><?=$addressString?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->phone ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Telefon</p>
                            <p class="mb-0 font-14"><?=$location->phone?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->email ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Email</p>
                            <p class="mb-0 font-14"><?=$location->email?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Order Information -->
            <?php if(!isEmpty($order)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-package-variant-closed font-18 color-blue"></i>
                            <p class="mb-0 font-20 font-weight-bold">Tilhørende Ordre</p>
                        </div>

                        <a href="<?=__url(Links::$consumer->orderDetail($order->uid))?>" class="btn-v2 action-btn flex-row-start flex-align-center flex-nowrap" style="gap: .35rem; padding: .35rem .65rem;">
                            <i class="mdi mdi-eye-outline font-16"></i>
                            <span class="font-13">Se Ordre</span>
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Ordre ID</p>
                            <p class="mb-0 font-14 font-monospace"><?=$order->uid?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Total Beløb</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=number_format($order->amount, 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalingsplan</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=\classes\lang\Translate::context("order.$order->payment_plan")?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Oprettet</p>
                            <p class="mb-0 font-14"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                        </div>

                        <?php if($totalPayments > 1): ?>
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalingsfremdrift</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$completedPayments?> / <?=$totalPayments?> betalinger</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Customer Billing Details -->
            <?php if(!isEmpty($order) && !isEmpty($order->billing_details ?? null)): ?>
            <?php $billing = $order->billing_details; ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Faktureringsoplysninger</p>
                    </div>

                    <div class="row">
                        <?php if(!isEmpty($billing->customer_name ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$billing->customer_name?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($billing->address ?? null)): ?>
                        <?php $billingAddress = $billing->address; ?>

                        <?php if(!isEmpty($billingAddress->line_1 ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Adresse</p>
                            <p class="mb-0 font-14"><?=$billingAddress->line_1?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($billingAddress->postal_code ?? null) || !isEmpty($billingAddress->city ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">By</p>
                            <p class="mb-0 font-14"><?=trim(($billingAddress->postal_code ?? '') . ' ' . ($billingAddress->city ?? ''))?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($billingAddress->region ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Region</p>
                            <p class="mb-0 font-14"><?=$billingAddress->region?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($billingAddress->country ?? null)): ?>
                        <div class="col-12 col-md-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Land</p>
                            <p class="mb-0 font-14"><?=$billingAddress->country?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Quick Actions -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-lightning-bolt font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Handlinger</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .75rem;">
                        <?php if($payment->status === 'COMPLETED'): ?>
                        <a href="<?=__url(Links::$api->orders->payments->receipt($payment->uid))?>" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem; text-decoration: none;">
                            <i class="mdi mdi-download font-16"></i>
                            <span class="font-14">Download Kvittering</span>
                        </a>
                        <?php endif; ?>

                        <?php if(in_array($payment->status, ['PAST_DUE', 'SCHEDULED', 'PENDING'])): ?>
                        <button type="button" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" id="update-payment-method-btn">
                            <i class="mdi mdi-credit-card-refresh-outline font-16"></i>
                            <span class="font-14">Opdater betalingsmetode</span>
                        </button>
                        <?php endif; ?>

                        <?php if(!isEmpty($order)): ?>
                        <a href="<?=__url(Links::$consumer->orderDetail($order->uid))?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-package-variant font-16"></i>
                            <span class="font-14">Se Ordre</span>
                        </a>
                        <?php endif; ?>

                        <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 trans-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left font-16"></i>
                            <span class="font-14">Tilbage til betalinger</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payment Summary -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-cash-multiple font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Betalingsoversigt</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .75rem;">
                        <div class="flex-row-between-center pb-3 border-bottom-card">
                            <p class="mb-0 font-14 color-gray">Beløb</p>
                            <p class="mb-0 font-18 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success-text' : ''?>"><?=number_format($payment->amount, 2) . ' ' . currencySymbol($payment->currency)?></p>
                        </div>

                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Status</p>
                            <span class="<?=$statusInfo['class']?>"><?=$statusInfo['label']?></span>
                        </div>

                        <?php if($payment->test): ?>
                        <div class="warning-info-box px-3 py-2 mt-2">
                            <div class="flex-row-start flex-align-center" style="column-gap: .5rem">
                                <i class="mdi mdi-flask-outline font-16"></i>
                                <p class="mb-0 font-13 font-weight-medium">Test Transaktion</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Installment Progress (if multiple payments) -->
            <?php if(!isEmpty($orderPayments) && $orderPayments->count() > 1): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-format-list-numbered font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Alle Rater</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .5rem;">
                        <?php foreach($orderPayments->list() as $installment): ?>
                            <?php
                            $instStatusInfo = $paymentStatusMap[$installment->status] ?? ['label' => $installment->status, 'class' => 'mute-box'];
                            $isCurrent = $installment->uid === $payment->uid;
                            ?>
                            <?php if($isCurrent): ?>
                            <div class="flex-row-between flex-align-center p-2 border-radius-5px bg-lighter-blue" style="border: 1px solid var(--design-blue);">
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <span class="font-12 font-weight-bold"><?=$installment->installment_number?>.</span>
                                    <span class="font-12 font-weight-medium"><?=number_format($installment->amount, 2)?> <?=currencySymbol($installment->currency)?></span>
                                </div>
                                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                    <span class="font-11 color-gray"><?=date("d/m", strtotime($installment->due_date))?></span>
                                    <span class="<?=$instStatusInfo['class']?> font-10"><?=$instStatusInfo['label']?></span>
                                </div>
                            </div>
                            <?php else: ?>
                            <a href="<?=__url(Links::$consumer->paymentDetail($installment->uid))?>"
                               class="flex-row-between flex-align-center p-2 border-radius-5px hover-bg-light card-border hover-bg-lightest-blue" style="text-decoration: none; color: inherit;">
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <span class="font-12 font-weight-bold"><?=$installment->installment_number?>.</span>
                                    <span class="font-12 font-weight-medium"><?=number_format($installment->amount, 2)?> <?=currencySymbol($installment->currency)?></span>
                                </div>
                                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                                    <span class="font-11 color-gray"><?=date("d/m", strtotime($installment->due_date))?></span>
                                    <span class="<?=$instStatusInfo['class']?> font-10"><?=$instStatusInfo['label']?></span>
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

