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
    'VOIDED' => ['label' => 'Ophævet', 'class' => 'mute-box'],
];

// Big label status mapping
$bigLabelStatusMap = [
    'REFUNDED' => ['label' => 'REFUNDERET', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'OPHÆVET', 'class' => 'mute-box'],
];
$bigLabelInfo = $bigLabelStatusMap[$payment->status] ?? null;

$statusInfo = $paymentStatusMap[$payment->status] ?? ['label' => $payment->status, 'class' => 'mute-box'];

// Calculate total payments for this order
$totalPayments = 0;
$completedPayments = 0;
if(!isEmpty($orderPayments)) {
    $totalPayments = $orderPayments->count();
    $completedPayments = $orderPayments->filter(fn($p) => $p['status'] === 'COMPLETED')->count();
}
?>




<?php
// Get order UID for card change
$orderUid = is_object($order) ? $order->uid : ($order ?? null);
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
    var paymentUid = <?=json_encode($payment->uid)?>;
    var paymentStatus = <?=json_encode($payment->status)?>;
    var paymentOrderUid = <?=json_encode($orderUid)?>;
    var consumerPayNowUrl = <?=json_encode(__url(str_replace('{uid}', $payment->uid, Links::$api->consumer->payNow)))?>;
    var consumerReceiptUrl = <?=json_encode(__url(Links::$api->consumer->paymentReceipt($payment->uid)))?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til betalinger</span>
        </a>
    </div>

    <div class="flex-row-between-center mb-4">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Betaling Detaljer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Betaling ID: <?=$payment->uid?></p>
        </div>
        <?php if($bigLabelInfo): ?>
            <div class="<?=$bigLabelInfo['class']?> font-24 font-weight-bold px-4 py-2">
                <?=$bigLabelInfo['label']?>
            </div>
        <?php endif; ?>
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
                        <button type="button" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" id="download-receipt-btn" data-uid="<?=$payment->uid?>">
                            <i class="mdi mdi-download font-16"></i>
                            <span class="font-14">Download Kvittering</span>
                        </button>
                        <?php endif; ?>

                        <?php if($payment->status === 'PAST_DUE'): ?>
                        <button type="button" class="btn-v2 danger-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" id="pay-now-btn" data-uid="<?=$payment->uid?>">
                            <i class="mdi mdi-cash-fast font-16"></i>
                            <span class="font-14">Betal nu</span>
                        </button>
                        <?php endif; ?>

                        <?php if(in_array($payment->status, ['PAST_DUE', 'SCHEDULED', 'PENDING', 'FAILED', 'DRAFT'])): ?>
                        <a href="<?=__url(Links::$consumer->changeCard)?>" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-credit-card-refresh-outline font-16"></i>
                            <span class="font-14">Skift betalingskort</span>
                        </a>
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

            <!-- Rykker Information (only for PAST_DUE or payments with rykker_level > 0) -->
            <?php
            $rykkerLevel = (int)($payment->rykker_level ?? 0);
            $rykkerFee = (float)($payment->rykker_fee ?? 0);
            $sentToCollection = (bool)($payment->sent_to_collection ?? false);
            $isCompleted = in_array($payment->status, ['COMPLETED', 'PAID']);
            $showRykkerSection = $payment->status === 'PAST_DUE' || $rykkerLevel > 0 || $sentToCollection;

            // For completed payments, the rykker_fee is already included in amount
            // So we calculate original amount by subtracting the fee
            $originalAmount = $isCompleted && $rykkerFee > 0 ? (float)$payment->amount - $rykkerFee : (float)$payment->amount;
            ?>
            <?php if($showRykkerSection): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <?php if($isCompleted): ?>
                        <i class="mdi mdi-information-outline font-18 color-gray"></i>
                        <p class="mb-0 font-20 font-weight-bold">Rykkerhistorik</p>
                        <?php else: ?>
                        <i class="mdi mdi-alert-circle-outline font-18 color-danger"></i>
                        <p class="mb-0 font-20 font-weight-bold">Betalingspåmindelse</p>
                        <?php endif; ?>
                    </div>

                    <div class="flex-col-start" style="row-gap: .75rem;">
                        <?php if($sentToCollection && !$isCompleted): ?>
                        <!-- Collection Notice (only show if not yet paid) -->
                        <div class="danger-info-box px-3 py-3">
                            <div class="flex-col-start" style="gap: .5rem;">
                                <p class="mb-0 font-14 font-weight-bold color-danger">Sendt til inkasso</p>
                                <p class="mb-0 font-13 color-dark">Denne betaling er blevet overdraget til inkasso. Kontakt venligst butikken for at løse sagen.</p>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Rykker Level Info -->
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Påmindelsesstatus</p>
                            <?php if($rykkerLevel === 0): ?>
                                <span class="mute-box font-12">Ingen påmindelse sendt</span>
                            <?php elseif($rykkerLevel === 1): ?>
                                <span class="<?=$isCompleted ? 'mute-box' : 'warning-box'?> font-12">Påmindelse 1</span>
                            <?php elseif($rykkerLevel === 2): ?>
                                <span class="<?=$isCompleted ? 'mute-box' : 'warning-box'?> font-12">Påmindelse 2</span>
                            <?php else: ?>
                                <span class="<?=$isCompleted ? 'mute-box' : 'danger-box'?> font-12">Sidste påmindelse</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Rykker Fee -->
                        <?php if($rykkerFee > 0): ?>
                        <?php if($isCompleted): ?>
                        <!-- Completed payment: show breakdown (fee already included in amount) -->
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Oprindeligt beløb</p>
                            <p class="mb-0 font-14"><?=number_format($originalAmount, 2, ',', '.')?> <?=currencySymbol($payment->currency)?></p>
                        </div>
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Rykkergebyr</p>
                            <p class="mb-0 font-14"><?=number_format($rykkerFee, 2, ',', '.')?> <?=currencySymbol($payment->currency)?></p>
                        </div>
                        <div class="flex-row-between-center pb-3 border-bottom-card">
                            <p class="mb-0 font-14 font-weight-bold">Total betalt</p>
                            <p class="mb-0 font-18 font-weight-bold color-dark"><?=number_format($payment->amount, 2, ',', '.')?> <?=currencySymbol($payment->currency)?></p>
                        </div>
                        <?php else: ?>
                        <!-- Past due payment: show amount + fee = total due -->
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Rykkergebyr</p>
                            <p class="mb-0 font-14 font-weight-bold color-danger"><?=number_format($rykkerFee, 2, ',', '.')?> <?=currencySymbol($payment->currency)?></p>
                        </div>
                        <div class="flex-row-between-center pb-3 border-bottom-card">
                            <p class="mb-0 font-14 font-weight-bold">Total skyldig beløb</p>
                            <p class="mb-0 font-18 font-weight-bold color-danger"><?=number_format(paymentTotalDue($payment), 2, ',', '.')?> <?=currencySymbol($payment->currency)?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- Info message (only for unpaid) -->
                        <?php if(!$sentToCollection && !$isCompleted): ?>
                        <div class="p-2 bg-light-gray border-radius-5px">
                            <p class="mb-0 font-12 color-dark">
                                <i class="mdi mdi-information-outline mr-1"></i>
                                Betal venligst snarest for at undgå yderligere gebyrer.
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

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

