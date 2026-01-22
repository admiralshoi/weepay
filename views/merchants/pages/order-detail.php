<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$order = $args->order;
$location = $order->location;
$customer = $order->uuid;
$billingDetails = toArray($order->billing_details ?? []);
$payments = $args->payments ?? null;

$pageTitle = "Ordre Detaljer - {$order->uid}";

// Status mapping for payments
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

// Status mapping for orders
$orderStatusMap = [
    'COMPLETED' => ['label' => 'GENNEMFØRT', 'class' => 'success-box'],
    'PENDING' => ['label' => 'AFVENTER', 'class' => 'action-box'],
    'CANCELLED' => ['label' => 'ANNULLERET', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'REFUNDERET', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'OPHÆVET', 'class' => 'mute-box'],
];
$orderStatusInfo = $orderStatusMap[$order->status] ?? null;

// Check if order has pending/scheduled payments (future payments that will be voided on refund)
$hasPendingPayments = false;
if(!isEmpty($payments)) {
    foreach($payments->list() as $p) {
        if(in_array($p->status, ['PENDING', 'SCHEDULED'])) {
            $hasPendingPayments = true;
            break;
        }
    }
}
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap mb-4" id="nav" style="column-gap: .5rem;">
        <a href="<?=__url(Links::$merchant->orders)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til ordrer</span>
        </a>
    </div>

    <div class="flex-row-between-center mb-4">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Ordre Detaljer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Ordre ID: <?=$order->uid?></p>
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
                            <?php endif; ?>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Dato & Tid</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Lokation</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$location->name ?? 'N/A'?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Valuta</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$order->currency?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalingsplan</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=\classes\lang\Translate::context("order.$order->payment_plan")?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Provider</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=ucfirst($order->provider->name)?></p>
                        </div>

                        <?php if(!isEmpty($order->prid)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Provider Reference ID</p>
                            <p class="mb-0 font-14 font-weight-medium font-monospace"><?=$order->prid?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($order->caption)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Beskrivelse</p>
                            <p class="mb-0 font-14"><?=$order->caption?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <?php if(!isEmpty($customer)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-account-outline font-18 color-blue"></i>
                            <p class="mb-0 font-20 font-weight-bold">Kunde Information</p>
                        </div>

                        <?php if(\classes\app\OrganisationPermissions::__oRead('orders', 'customers')): ?>
                        <a href="<?=__url(Links::$merchant->customerDetail($customer->uid))?>" class="btn-v2 action-btn flex-row-start flex-align-center flex-nowrap" style="gap: .35rem; padding: .35rem .65rem;">
                            <i class="mdi mdi-account-details font-16"></i>
                            <span class="font-13">Se Kunde Profil</span>
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Fulde Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$customer->full_name ?? 'N/A'?></p>
                        </div>

                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Email</p>
                            <p class="mb-0 font-14"><?=$customer->email ?? 'N/A'?></p>
                        </div>

                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Telefon</p>
                            <p class="mb-0 font-14"><?=formatPhone($customer->phone, $customer->phone_country_code)?></p>
                        </div>

                        <div class="col-6 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Kunde ID</p>
                            <p class="mb-0 font-14 font-monospace"><?=$customer->uid?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Payments List -->
            <?php if(!isEmpty($payments) && $payments->count() > 0): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-credit-card-multiple-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Betalinger</p>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="color-gray">
                                <tr>
                                    <th class="font-12">Rate</th>
                                    <th class="font-12">Beløb</th>
                                    <th class="font-12">Forfald</th>
                                    <th class="font-12">Betalt</th>
                                    <th class="font-12">Status</th>
                                    <th class="font-12 text-right">Handlinger</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($payments->list() as $payment): ?>
                                    <?php
                                    $statusInfo = $paymentStatusMap[$payment->status] ?? ['label' => $payment->status, 'class' => 'mute-box'];
                                    ?>
                                    <tr>
                                        <td>
                                            <p class="mb-0 font-13 font-weight-medium"><?=$payment->installment_number?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-13 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success-text' : ($payment->status === 'PAST_DUE' ? 'color-red' : '')?>"><?=number_format($payment->amount, 2) . ' ' . currencySymbol($payment->currency)?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=date("d/m-Y", strtotime($payment->due_date))?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=!isEmpty($payment->paid_at) ? date("d/m-Y H:i", strtotime($payment->paid_at)) : '-'?></p>
                                        </td>
                                        <td>
                                            <span class="<?=$statusInfo['class']?> font-11"><?=$statusInfo['label']?></span>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?=__url(Links::$merchant->paymentDetail($payment->uid))?>" class="btn-v2 trans-btn flex-row-center-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-eye-outline font-14"></i>
                                                <span class="font-12">Detaljer</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                        <?php if(in_array($order->payment_plan, ['installments', 'pushed'])): ?>
                        <a href="<?=__url("api/merchant/orders/{$order->uid}/contract")?>" target="_blank" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-file-document-outline font-16"></i>
                            <span class="font-14">Download Kontrakt</span>
                        </a>
                        <?php endif; ?>

                        <?php
                        // Collect payments with rykkers
                        $paymentsWithRykkers = [];
                        if(!isEmpty($payments)) {
                            foreach($payments->list() as $p) {
                                $rykkerLevel = (int)($p->rykker_level ?? 0);
                                if($rykkerLevel > 0) {
                                    $paymentsWithRykkers[] = $p;
                                }
                            }
                        }
                        ?>
                        <?php if(!empty($paymentsWithRykkers)): ?>
                        <div class="flex-col-start" style="gap: .5rem;">
                            <p class="mb-0 font-13 color-gray">Rykker dokumenter:</p>
                            <?php foreach($paymentsWithRykkers as $rykkerPayment): ?>
                            <div class="flex-col-start" style="gap: .25rem;">
                                <span class="font-11 color-gray">Betaling #<?=$rykkerPayment->installment_number ?? $rykkerPayment->uid?></span>
                                <div class="flex-row-start flex-wrap" style="gap: .5rem;">
                                    <?php for($i = 1; $i <= (int)$rykkerPayment->rykker_level; $i++): ?>
                                    <a href="<?=__url("api/merchant/payments/{$rykkerPayment->uid}/rykker/{$i}")?>" target="_blank" class="btn-v2 mute-btn btn-sm flex-row-center flex-align-center" style="gap: .25rem;">
                                        <i class="mdi mdi-file-pdf-box font-14"></i>
                                        <span class="font-12">Rykker <?=$i?></span>
                                    </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($order->status === 'COMPLETED' || $order->status === 'PENDING'): ?>
                        <button type="button" class="btn-v2 danger-outline-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" data-refund-order="<?=$order->uid?>" data-has-pending-payments="<?=$hasPendingPayments ? 'true' : 'false'?>">
                            <i class="mdi mdi-cash-refund font-16"></i>
                            <span class="font-14">Annuller & Refunder</span>
                        </button>
                        <?php endif; ?>
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
                            <p class="mb-0 font-14 color-gray">Total Beløb</p>
                            <p class="mb-0 font-16 font-weight-bold"><?=number_format(orderAmount($order), 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>

                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Gebyr</p>
                            <p class="mb-0 font-14 color-danger"><?=number_format($order->fee_amount, 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>

                        <div class="flex-row-between-center <?=(float)$order->amount_refunded > 0 ? '' : 'pb-3 border-bottom-card'?>">
                            <p class="mb-0 font-14 color-gray">Gebyr (%)</p>
                            <p class="mb-0 font-14"><?=number_format($order->fee, 2)?>%</p>
                        </div>

                        <?php if((float)$order->amount_refunded > 0): ?>
                        <div class="flex-row-between-center pb-3 border-bottom-card">
                            <p class="mb-0 font-14 color-gray">Refunderet</p>
                            <p class="mb-0 font-14 font-weight-medium color-warning-text">-<?=number_format($order->amount_refunded, 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>
                        <?php endif; ?>

                        <div class="flex-row-between-center">
                            <p class="mb-0 font-16 font-weight-bold">Net Beløb</p>
                            <p class="mb-0 font-18 font-weight-bold color-success-text"><?=number_format($order->amount - $order->fee_amount - $order->amount_refunded, 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>

                        <?php if($order->test): ?>
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

            <!-- Billing Details -->
            <?php if(!isEmpty($billingDetails)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-receipt-text-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Faktureringsoplysninger</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: .5rem;">
                        <?php if(!isEmpty($billingDetails['customer_name'])): ?>
                        <div>
                            <p class="mb-1 font-12 color-gray font-weight-medium">Kunde Navn</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$billingDetails['customer_name']?></p>
                        </div>
                        <?php endif; ?>

                        <?php
                        $address = $billingDetails['address'] ?? [];
                        if(!isEmpty($address['line_1']) || !isEmpty($address['city']) || !isEmpty($address['postal_code'])):
                        ?>
                        <div class="mt-2">
                            <p class="mb-1 font-12 color-gray font-weight-medium">Adresse</p>
                            <div class="flex-col-start" style="row-gap: .15rem;">
                                <?php if(!isEmpty($address['line_1'])): ?>
                                <p class="mb-0 font-13"><?=$address['line_1']?></p>
                                <?php endif; ?>

                                <?php if(!isEmpty($address['city']) || !isEmpty($address['postal_code'])): ?>
                                <p class="mb-0 font-13">
                                    <?=trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? ''))?>
                                </p>
                                <?php endif; ?>

                                <?php if(!isEmpty($address['region'])): ?>
                                <p class="mb-0 font-13"><?=$address['region']?></p>
                                <?php endif; ?>

                                <?php if(!isEmpty($address['country'])): ?>
                                <p class="mb-0 font-13 font-weight-medium"><?=$address['country']?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php scriptStart(); ?>
<script>
    $(document).ready(function() {
        initMerchantRefunds();
    });
</script>
<?php scriptEnd(); ?>
