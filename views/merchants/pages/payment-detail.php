<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$payment = $args->payment;
$order = $args->order;
$customer = $args->customer;
$orderPayments = $args->orderPayments;
$location = $payment->location;

$pageTitle = "Betaling Detaljer - {$payment->uid}";

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
        <a href="<?=__url(Links::$merchant->payments)?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
            <i class="mdi mdi-arrow-left font-16"></i>
            <span class="font-14">Tilbage til betalinger</span>
        </a>
    </div>

    <div class="flex-row-between-center mb-4">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Betaling Detaljer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Betaling ID: <?=$payment->uid?></p>
        </div>
        <?php if($payment->status === 'REFUNDED'): ?>
            <div class="refunded-badge warning-box font-24 font-weight-bold px-4 py-2">
                REFUNDERET
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

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Lokation</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=$location->name ?? 'N/A'?></p>
                        </div>

                        <?php if(!isEmpty($payment->prid)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Provider Reference ID</p>
                            <p class="mb-0 font-14 font-weight-medium font-monospace"><?=$payment->prid?></p>
                        </div>
                        <?php endif; ?>

                        <?php if(!isEmpty($payment->failure_reason)): ?>
                        <div class="col-12 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Fejlbeskrivelse</p>
                            <p class="mb-0 font-14 color-red"><?=$payment->failure_reason?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Information -->
            <?php if(!isEmpty($order)): ?>
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-package-variant-closed font-18 color-blue"></i>
                            <p class="mb-0 font-20 font-weight-bold">Tilhørende Ordre</p>
                        </div>

                        <a href="<?=__url(Links::$merchant->orderDetail($order->uid))?>" class="btn-v2 action-btn flex-row-start flex-align-center flex-nowrap" style="gap: .35rem; padding: .35rem .65rem;">
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
                            <p class="mb-0 font-14 font-weight-bold"><?=number_format(orderAmount($order), 2) . ' ' . currencySymbol($order->currency)?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Betalingsplan</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=\classes\lang\Translate::context("order.$order->payment_plan")?></p>
                        </div>

                        <div class="col-6 col-md-4 mb-3">
                            <p class="mb-1 font-13 color-gray font-weight-medium">Ordre Status</p>
                            <?php if($order->status === 'COMPLETED'): ?>
                                <span class="success-box font-12">Gennemført</span>
                            <?php elseif($order->status === 'DRAFT'): ?>
                                <span class="mute-box font-12">Draft</span>
                            <?php elseif($order->status === 'PENDING'): ?>
                                <span class="action-box font-12">Afvikles</span>
                            <?php elseif($order->status === 'CANCELLED'): ?>
                                <span class="danger-box font-12">Annulleret</span>
                            <?php endif; ?>
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

                        <?php if(!isEmpty($order)): ?>
                        <a href="<?=__url(Links::$merchant->orderDetail($order->uid))?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-package-variant font-16"></i>
                            <span class="font-14">Se Ordre</span>
                        </a>
                        <?php endif; ?>

                        <?php if(!isEmpty($customer) && \classes\app\OrganisationPermissions::__oRead('orders', 'customers')): ?>
                        <a href="<?=__url(Links::$merchant->customerDetail($customer->uid))?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-account font-16"></i>
                            <span class="font-14">Se Kunde</span>
                        </a>
                        <?php endif; ?>

                        <?php if($payment->status === 'COMPLETED'): ?>
                        <button type="button" class="btn-v2 danger-outline-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;" data-refund-payment="<?=$payment->uid?>">
                            <i class="mdi mdi-cash-refund font-16"></i>
                            <span class="font-14">Refunder</span>
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
                            <p class="mb-0 font-14 color-gray">Beløb</p>
                            <p class="mb-0 font-18 font-weight-bold <?=$payment->status === 'COMPLETED' ? 'color-success-text' : ''?>"><?=number_format($payment->amount, 2) . ' ' . currencySymbol($payment->currency)?></p>
                        </div>

                        <?php if(!isEmpty($payment->isv_amount) && $payment->isv_amount > 0): ?>
                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">ISV Beløb</p>
                            <p class="mb-0 font-14"><?=number_format($payment->isv_amount, 2) . ' ' . currencySymbol($payment->currency)?></p>
                        </div>
                        <?php endif; ?>

                        <div class="flex-row-between-center">
                            <p class="mb-0 font-14 color-gray">Provider</p>
                            <p class="mb-0 font-14 font-weight-medium"><?=ucfirst($payment->provider->name ?? 'N/A')?></p>
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
                            <a href="<?=__url(Links::$merchant->paymentDetail($installment->uid))?>"
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

<?php scriptStart(); ?>
<script>
    $(document).ready(function() {
        initMerchantRefunds();
    });
</script>
<?php scriptEnd(); ?>
