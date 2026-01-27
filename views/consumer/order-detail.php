<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$order = $args->order;
$location = $order->location;
$payments = $args->payments;
$billingDetails = toArray($order->billing_details ?? []);

$pageTitle = "Ordre Detaljer - " . substr($order->uid, 0, 8);

// Order status mapping for big label
$orderStatusMap = [
    'COMPLETED' => ['label' => 'GENNEMFØRT', 'class' => 'success-box'],
    'PENDING' => ['label' => 'AFVENTER', 'class' => 'action-box'],
    'CANCELLED' => ['label' => 'ANNULLERET', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'REFUNDERET', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'OPHÆVET', 'class' => 'mute-box'],
];
$orderStatusInfo = $orderStatusMap[$order->status] ?? null;
?>

<?php
// Count unpaid payments (SCHEDULED, PAST_DUE, FAILED) for this order
$unpaidStatuses = ['SCHEDULED', 'PAST_DUE', 'FAILED'];
$unpaidPayments = $payments->filter(fn($p) => in_array($p['status'], $unpaidStatuses));
$unpaidCount = $unpaidPayments->count();
$unpaidTotal = 0;
$unpaidFees = 0;
$hasPastDue = false;

// Also track PAST_DUE specifically for warning box
$pastDueCount = 0;
$pastDueTotal = 0;
$pastDueFees = 0;

foreach ($unpaidPayments->list() as $p) {
    $unpaidTotal += (float)$p->amount;
    $unpaidFees += (float)($p->rykker_fee ?? 0);
    if($p->status === 'PAST_DUE') {
        $hasPastDue = true;
        $pastDueCount++;
        $pastDueTotal += (float)$p->amount;
        $pastDueFees += (float)($p->rykker_fee ?? 0);
    }
}
$unpaidTotalWithFees = $unpaidTotal + $unpaidFees;
$orderCurrencySymbol = currencySymbol($order->currency);

// Get card info from first unpaid payment
$cardBrand = null;
$cardLast4 = null;
foreach ($payments->list() as $p) {
    $pm = $p->payment_method ?? null;
    if(!isEmpty($pm) && !isEmpty($pm->brand) && !isEmpty($pm->last4)) {
        $cardBrand = $pm->brand;
        $cardLast4 = $pm->last4;
        break;
    }
}
$hasCardInfo = !isEmpty($cardBrand) && !isEmpty($cardLast4);
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
    var orderUid = <?=json_encode($order->uid)?>;
    var payOrderOutstandingUrl = <?=json_encode(__url(str_replace('{uid}', $order->uid, Links::$api->consumer->payOrderOutstanding)))?>;
    var payOutstandingData = {
        count: <?=json_encode($unpaidCount)?>,
        amount: <?=json_encode($unpaidTotalWithFees)?>,
        baseAmount: <?=json_encode($unpaidTotal)?>,
        rykkerFees: <?=json_encode($unpaidFees)?>,
        currency: <?=json_encode($order->currency)?>,
        currencySymbol: <?=json_encode($orderCurrencySymbol)?>,
        cardBrand: <?=json_encode($cardBrand)?>,
        cardLast4: <?=json_encode($cardLast4)?>,
        hasCardInfo: <?=json_encode($hasCardInfo)?>,
        hasPastDue: <?=json_encode($hasPastDue)?>
    };
</script>

<div class="page-content">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$consumer->orders)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til ordrer</span>
        </a>
    </div>

    <div class="flex-row-between-center mb-4">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Ordre Detaljer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Ordre ID: <?=substr($order->uid, 0, 8)?></p>
        </div>
        <?php if($orderStatusInfo): ?>
            <div class="<?=$orderStatusInfo['class']?> font-24 font-weight-bold px-4 py-2">
                <?=$orderStatusInfo['label']?>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-12 col-lg-8">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-information-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Ordre Information</p>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Status</p>
                            <?php if($order->status === 'COMPLETED'): ?>
                                <span class="success-box">Gennemført</span>
                            <?php elseif($order->status === 'DRAFT'): ?>
                                <span class="mute-box">Draft</span>
                            <?php elseif($order->status === 'PENDING'): ?>
                                <span class="action-box">Afvikles</span>
                            <?php elseif($order->status === 'CANCELLED'): ?>
                                <span class="danger-box">Annulleret</span>
                            <?php elseif($order->status === 'VOIDED'): ?>
                                <span class="mute-box">Ophævet</span>
                            <?php elseif($order->status === 'REFUNDED'): ?>
                                <span class="warning-box">Refunderet</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Dato & Tid</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Beløb</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=number_format($order->amount, 2)?> <?=currencySymbol($order->currency)?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Valuta</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$order->currency?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalingsplan</p>
                            <p class="mb-0 font-14 font-weight-medium">
                                <?php if($order->payment_plan === 'installments'): ?>
                                    Afdrag
                                <?php elseif($order->payment_plan === 'pushed'): ?>
                                    Udskudt
                                <?php else: ?>
                                    Fuld betaling
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if(!isEmpty($order->caption)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Beskrivelse</p>
                            <p class="mb-0 font-14"><?=$order->caption?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <?php if(!isEmpty($location)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-store-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Butik Information</p>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Butik Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($location->name ?? 'N/A')?></p>
                        </div>

                        <?php if(!isEmpty($location->address)): ?>
                        <?php $address = toArray($location->address); ?>
                        <div class="col-12 mb-3">
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
                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Telefon</p>
                            <p class="mb-0 font-14"><?=\classes\Methods::locations()->contactPhone($location)?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($location->contact_email)): ?>
                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Email</p>
                            <p class="mb-0 font-14"><?=\classes\Methods::locations()->contactEmail($location)?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Billing Details -->
            <?php if(!isEmpty($billingDetails)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-receipt-text-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Faktureringsoplysninger</p>
                    </div>

                    <div class="row">
                        <?php if(!isEmpty($billingDetails['customer_name'])): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Kunde Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($billingDetails['customer_name'])?></p>
                        </div>
                        <?php endif; ?>

                        <?php
                        $billingAddress = $billingDetails['address'] ?? [];
                        if(!isEmpty($billingAddress['line_1']) || !isEmpty($billingAddress['city']) || !isEmpty($billingAddress['postal_code'])):
                        ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Adresse</p>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <?php if(!isEmpty($billingAddress['line_1'])): ?>
                                <p class="mb-0 font-14"><?=htmlspecialchars($billingAddress['line_1'])?></p>
                                <?php endif; ?>

                                <?php if(!isEmpty($billingAddress['city']) || !isEmpty($billingAddress['postal_code'])): ?>
                                <p class="mb-0 font-14">
                                    <?=trim(htmlspecialchars(($billingAddress['postal_code'] ?? '') . ' ' . ($billingAddress['city'] ?? '')))?>
                                </p>
                                <?php endif; ?>

                                <?php if(!isEmpty($billingAddress['country'])): ?>
                                <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($billingAddress['country'])?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <?php
            // Check if there are any payments that can have their card changed
            $hasUnpaidPayments = false;
            $changeableStatuses = ['PAST_DUE', 'SCHEDULED', 'PENDING', 'FAILED', 'DRAFT'];
            foreach($payments->list() as $p) {
                if(in_array($p->status, $changeableStatuses)) {
                    $hasUnpaidPayments = true;
                    break;
                }
            }
            ?>

            <!-- Quick Actions -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-lightning-bolt font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Handlinger</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .75rem;">
                        <?php if($unpaidCount > 0): ?>
                        <?php
                        // Build button text with amount and card info
                        $btnOutstandingText = number_format($unpaidTotalWithFees, 2, ',', '.') . ' ' . $orderCurrencySymbol;
                        $btnCardText = $hasCardInfo ? strtoupper($cardBrand) . ' (**' . $cardLast4 . ')' : '';
                        // Use "udestående" for past due, "resterende" for scheduled only
                        $btnLabel = $hasPastDue ? 'Betal udestående' : 'Betal resterende';
                        $btnClass = $hasPastDue ? 'danger-btn' : 'green-btn';
                        ?>
                        <button type="button" id="pay-all-outstanding-btn" class="btn-v2 <?=$btnClass?> w-100 flex-col-center flex-align-center py-3" style="gap: .25rem;">
                            <div class="flex-row-center flex-align-center" style="gap: .5rem;">
                                <i class="mdi mdi-cash-fast font-16"></i>
                                <span class="font-14 font-weight-bold"><?=$btnLabel?> <?=$btnOutstandingText?></span>
                            </div>
                            <?php if($hasCardInfo): ?>
                            <span class="font-12 opacity-80">med <?=$btnCardText?></span>
                            <?php endif; ?>
                        </button>
                        <?php endif; ?>

                        <?php if($hasUnpaidPayments): ?>
                        <a href="<?=__url(Links::$consumer->changeCard)?>" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-credit-card-refresh-outline font-16"></i>
                            <span class="font-14">Skift betalingskort</span>
                        </a>
                        <?php endif; ?>

                        <?php if(in_array($order->payment_plan, ['installments', 'pushed'])): ?>
                        <a href="<?=__url("api/consumer/orders/{$order->uid}/contract")?>" target="_blank" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-file-document-outline font-16"></i>
                            <span class="font-14">Download Kontrakt</span>
                        </a>
                        <?php endif; ?>

                        <a href="<?=__url(Links::$consumer->orders)?>" class="btn-v2 <?=$hasUnpaidPayments ? 'mute-btn' : 'action-btn'?> w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left font-16"></i>
                            <span class="font-14">Tilbage til ordrer</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Outstanding Payments Warning -->
            <?php if($pastDueCount > 0): ?>
            <div class="card border-radius-10px mb-4 border-danger">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-alert-circle-outline font-18 color-danger"></i>
                        <p class="mb-0 font-20 font-weight-bold color-danger">Udestående</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .5rem;">
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Antal betalinger</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=$pastDueCount?></p>
                        </div>
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Beløb</p>
                            <p class="mb-0 font-14 font-weight-bold"><?=number_format($pastDueTotal, 2)?> <?=currencySymbol($order->currency)?></p>
                        </div>
                        <?php if($pastDueFees > 0): ?>
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Rykkergebyrer</p>
                            <p class="mb-0 font-14 font-weight-bold color-danger"><?=number_format($pastDueFees, 2)?> <?=currencySymbol($order->currency)?></p>
                        </div>
                        <div class="flex-row-between-center pt-2 border-top-card">
                            <p class="mb-0 font-16 font-weight-bold">Total skyldig</p>
                            <p class="mb-0 font-18 font-weight-bold color-danger"><?=number_format($pastDueTotal + $pastDueFees, 2)?> <?=currencySymbol($order->currency)?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payment Summary -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-cash-multiple font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Betalingsoversigt</p>
                    </div>

                    <?php if($payments->count() === 0): ?>
                        <p class="mb-0 font-14 color-gray">Ingen betalinger endnu</p>
                    <?php else: ?>
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <?php foreach($payments->list() as $payment): ?>
                            <a href="<?=__url(Links::$consumer->paymentDetail($payment->uid))?>" class="flex-row-between flex-align-center border-bottom-card pb-3 hover-bg-light" style="text-decoration: none; color: inherit; margin: -0.5rem; padding: 0.5rem; border-radius: 8px; gap: .5rem;">
                                <div class="flex-col-start flex-grow-1" style="row-gap: .5rem;">
                                    <div class="flex-row-between flex-align-center">
                                        <p class="mb-0 font-13 color-gray">Betaling</p>
                                        <p class="mb-0 font-14 font-weight-bold"><?=number_format($payment->amount, 2)?> <?=currencySymbol($order->currency)?></p>
                                    </div>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="mb-0 font-13 color-gray">Status</p>
                                        <?php if($payment->status === 'COMPLETED'): ?>
                                        <span class="success-box">Gennemført</span>
                                        <?php elseif($payment->status === 'SCHEDULED'): ?>
                                        <span class="action-box">Planlagt</span>
                                        <?php elseif($payment->status === 'PAST_DUE'): ?>
                                        <span class="danger-box">Forsinket</span>
                                        <?php elseif($payment->status === 'PENDING'): ?>
                                        <span class="action-box">Afventer</span>
                                        <?php else: ?>
                                        <span class="mute-box"><?=$payment->status?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($payment->status === 'COMPLETED' && !isEmpty($payment->paid_at)): ?>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="mb-0 font-13 color-gray">Betalt dato</p>
                                        <p class="mb-0 font-13"><?=date('d/m/Y', strtotime($payment->paid_at))?></p>
                                    </div>
                                    <?php elseif(!isEmpty($payment->due_date)): ?>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="mb-0 font-13 color-gray">Forfaldsdato</p>
                                        <p class="mb-0 font-13 <?=$payment->status === 'PAST_DUE' ? 'color-red font-weight-bold' : ''?>">
                                            <?=date('d/m/Y', strtotime($payment->due_date))?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <i class="mdi mdi-chevron-right font-20 color-gray"></i>
                            </a>
                            <?php endforeach; ?>

                            <div class="flex-col-start pt-2" style="row-gap: .5rem;">
                                <div class="flex-row-between flex-align-center">
                                    <p class="mb-0 font-16 font-weight-bold">Total</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($order->amount, 2)?> <?=currencySymbol($order->currency)?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
