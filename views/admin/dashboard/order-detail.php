<?php
/**
 * Admin Dashboard - Order Detail
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$order = $args->order ?? null;
$payments = $args->payments ?? new \Database\Collection();
$stats = $args->stats ?? (object)['totalPayments' => 0, 'completedPayments' => 0, 'pendingPayments' => 0, 'pastDuePayments' => 0, 'totalPaid' => 0];

$pageTitle = $order ? ('Ordre: ' . $order->uid) : 'Ordre detaljer';

$statusLabels = [
    'DRAFT' => ['label' => 'Kladde', 'class' => 'mute-box'],
    'PENDING' => ['label' => 'Afventer', 'class' => 'warning-box'],
    'COMPLETED' => ['label' => 'Gennemført', 'class' => 'success-box'],
    'CANCELLED' => ['label' => 'Annulleret', 'class' => 'danger-box'],
    'EXPIRED' => ['label' => 'Udløbet', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'Refunderet', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'Ophævet', 'class' => 'mute-box'],
];
$statusInfo = $statusLabels[$order->status ?? 'DRAFT'] ?? ['label' => 'Ukendt', 'class' => 'mute-box'];

// Big label for special statuses
$bigLabelStatusMap = [
    'REFUNDED' => ['label' => 'REFUNDERET', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'OPHÆVET', 'class' => 'mute-box'],
];
$bigLabelInfo = $bigLabelStatusMap[$order->status ?? ''] ?? null;

// Check for pending/scheduled payments
$hasPendingPayments = false;
if (!$payments->empty()) {
    foreach ($payments->list() as $p) {
        if (in_array($p->status, ['PENDING', 'SCHEDULED'])) {
            $hasPendingPayments = true;
            break;
        }
    }
}

// Check for completed payments (for refund button)
$hasCompletedPayments = false;
if (!$payments->empty()) {
    foreach ($payments->list() as $p) {
        if ($p->status === 'COMPLETED') {
            $hasCompletedPayments = true;
            break;
        }
    }
}

// Can refund if order is COMPLETED or PENDING and has payments to refund/void
$canRefund = in_array($order->status, ['COMPLETED', 'PENDING']) && ($hasCompletedPayments || $hasPendingPayments);

$paymentStatusLabels = [
    'PENDING' => ['label' => 'Afventer', 'class' => 'warning-box'],
    'PAST_DUE' => ['label' => 'Forsinket', 'class' => 'danger-box'],
    'SCHEDULED' => ['label' => 'Planlagt', 'class' => 'info-box'],
    'COMPLETED' => ['label' => 'Betalt', 'class' => 'success-box'],
    'FAILED' => ['label' => 'Fejlet', 'class' => 'danger-box'],
    'CANCELLED' => ['label' => 'Annulleret', 'class' => 'mute-box'],
    'REFUNDED' => ['label' => 'Refunderet', 'class' => 'warning-box'],
    'VOIDED' => ['label' => 'Ophævet', 'class' => 'mute-box'],
];

// Extract foreign key data
$userName = is_object($order->uuid) ? ($order->uuid->full_name ?? $order->uuid->email ?? '-') : '-';
$userUid = is_object($order->uuid) ? $order->uuid->uid : $order->uuid;
$orgName = is_object($order->organisation) ? ($order->organisation->name ?? '-') : '-';
$orgUid = is_object($order->organisation) ? $order->organisation->uid : $order->organisation;
$locName = is_object($order->location) ? ($order->location->name ?? '-') : '-';
$locUid = is_object($order->location) ? $order->location->uid : $order->location;
$providerName = is_object($order->provider) ? ($order->provider->name ?? '-') : '-';
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->dashboardOrders)?>" class="font-13 color-gray hover-color-blue">Ordrer</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=htmlspecialchars($order->uid ?? 'Ordre')?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-start w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                    <div class="square-70 bg-light-gray border-radius-8px flex-row-center-center">
                        <i class="mdi mdi-cart-outline font-40 color-blue"></i>
                    </div>
                    <div class="flex-col-start">
                        <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($order->uid ?? 'Unavngivet')?></h1>
                        <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                            <span class="<?=$statusInfo['class']?> font-11"><?=$statusInfo['label']?></span>
                            <span class="font-12 color-gray"><?=number_format(orderAmount($order), 2, ',', '.')?> <?=htmlspecialchars($order->currency ?? 'DKK')?></span>
                        </div>
                    </div>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <?php if($bigLabelInfo): ?>
                        <div class="<?=$bigLabelInfo['class']?> font-24 font-weight-bold px-4 py-2">
                            <?=$bigLabelInfo['label']?>
                        </div>
                    <?php endif; ?>
                    <?php if($canRefund): ?>
                        <button class="btn-v2 danger-btn" data-refund-order="<?=$order->uid?>" data-has-pending-payments="<?=$hasPendingPayments ? '1' : '0'?>">
                            <i class="mdi mdi-cash-refund mr-1"></i> Annuller & Refunder
                        </button>
                    <?php elseif($order->status === 'PENDING'): ?>
                        <button class="btn-v2 warning-btn" onclick="cancelOrder()">
                            <i class="mdi mdi-close mr-1"></i> Annuller
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row flex-align-stretch rg-15">
                <div class="col-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Total beløb</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format(orderAmount($order), 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-blue border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-cash-multiple color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Betalt</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=number_format($stats->totalPaid, 2, ',', '.')?> kr</p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-green border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-check color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Afventer</p>
                                    <p class="mb-0 font-18 font-weight-bold"><?=$stats->pendingPayments?> / <?=$stats->totalPayments?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-pee-yellow border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-clock-outline color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3 d-flex">
                    <div class="card border-radius-10px w-100">
                        <div class="card-body">
                            <div class="flex-row-between-center flex-nowrap g-075">
                                <div class="flex-col-start rg-025 flex-1 min-width-0">
                                    <p class="mb-0 font-12 color-gray text-wrap">Forsinkede</p>
                                    <p class="mb-0 font-18 font-weight-bold <?=$stats->pastDuePayments > 0 ? 'color-danger' : ''?>"><?=$stats->pastDuePayments?></p>
                                </div>
                                <div style="width: 40px; height: 40px; min-width: 40px;" class="bg-red border-radius-10px flex-row-center-center">
                                    <i class="mdi mdi-alert color-white font-22"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row rg-15">
                <!-- Order Info -->
                <div class="col-12 col-lg-4">
                    <div class="card border-radius-10px h-100">
                        <div class="card-body">
                            <p class="font-16 font-weight-bold mb-3">Ordre information</p>

                            <div class="flex-col-start" style="gap: 1rem;">
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Ordre ID</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($order->uid ?? '-')?></p>
                                </div>
                                <?php if(!empty($order->caption)): ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Beskrivelse</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($order->caption)?></p>
                                </div>
                                <?php endif; ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Kunde</p>
                                    <?php if($userUid): ?>
                                    <a href="<?=__url(Links::$admin->dashboardUserDetail($userUid))?>" class="font-14 font-weight-medium color-blue hover-underline">
                                        <?=htmlspecialchars($userName)?>
                                    </a>
                                    <?php else: ?>
                                    <p class="mb-0 font-14 font-weight-medium color-gray">Gæst</p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray"><?=Translate::word("Organisation")?></p>
                                    <a href="<?=__url(Links::$admin->dashboardOrganisationDetail($orgUid))?>" class="font-14 font-weight-medium color-blue hover-underline">
                                        <?=htmlspecialchars($orgName)?>
                                    </a>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Lokation</p>
                                    <a href="<?=__url(Links::$admin->dashboardLocationDetail($locUid))?>" class="font-14 font-weight-medium color-blue hover-underline">
                                        <?=htmlspecialchars($locName)?>
                                    </a>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Betalingsudbyder</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=htmlspecialchars($providerName)?></p>
                                </div>
                                <?php if(!empty($order->payment_plan)): ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Betalingsplan</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=ucfirst(Translate::context("order.".$order->payment_plan))?></p>
                                </div>
                                <?php endif; ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Gebyr</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=number_format($order->fee_amount, 2, ',', '.')?> kr (<?=number_format($order->fee * 100, 1)?>%)</p>
                                </div>
                                <?php if((float)($order->amount_refunded ?? 0) > 0): ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Refunderet</p>
                                    <p class="mb-0 font-14 font-weight-medium color-warning"><?=number_format($order->amount_refunded, 2, ',', '.')?> kr</p>
                                </div>
                                <?php endif; ?>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-12 color-gray">Oprettet</p>
                                    <p class="mb-0 font-14 font-weight-medium"><?=date('d/m/Y H:i', strtotime($order->created_at))?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments -->
                <div class="col-12 col-lg-8">
                    <div class="card border-radius-10px">
                        <div class="card-body">
                            <div class="flex-row-between flex-align-center mb-3">
                                <p class="mb-0 font-16 font-weight-bold">Betalinger</p>
                            </div>

                            <?php if($payments->empty()): ?>
                                <div class="flex-col-center flex-align-center py-4">
                                    <i class="mdi mdi-credit-card-off font-40 color-gray"></i>
                                    <p class="mb-0 font-14 color-gray mt-2">Ingen betalinger</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="font-12 font-weight-medium color-gray">#</th>
                                                <th class="font-12 font-weight-medium color-gray">Beløb</th>
                                                <th class="font-12 font-weight-medium color-gray">Forfaldsdato</th>
                                                <th class="font-12 font-weight-medium color-gray">Betalt</th>
                                                <th class="font-12 font-weight-medium color-gray">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments->list() as $payment): ?>
                                            <?php
                                                $paymentStatusInfo = $paymentStatusLabels[$payment->status ?? 'PENDING'] ?? ['label' => 'Ukendt', 'class' => 'bg-secondary'];
                                            ?>
                                            <tr class="cursor-pointer hover-bg-light-gray" onclick="window.location='<?=__url(Links::$admin->dashboardPaymentDetail($payment->uid))?>'">
                                                <td>
                                                    <span class="font-13 font-weight-medium color-blue"><?=$payment->installment_number?></span>
                                                </td>
                                                <td>
                                                    <span class="font-13"><?=number_format($payment->amount, 2, ',', '.')?> <?=htmlspecialchars($payment->currency ?? 'DKK')?></span>
                                                </td>
                                                <td>
                                                    <span class="font-13"><?=$payment->due_date ? date('d/m/Y', strtotime($payment->due_date)) : '-'?></span>
                                                </td>
                                                <td>
                                                    <span class="font-13"><?=$payment->paid_at ? date('d/m/Y H:i', strtotime($payment->paid_at)) : '-'?></span>
                                                </td>
                                                <td>
                                                    <span class="<?=$paymentStatusInfo['class']?> font-10"><?=$paymentStatusInfo['label']?></span>
                                                </td>
                                                <td class="text-right">
                                                    <i class="mdi mdi-chevron-right font-16 color-gray"></i>
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
    </div>
</div>

<?php scriptStart(); ?>
<script>
    $(document).ready(function() {
        initAdminRefunds();
    });

    function cancelOrder() {
        const orderId = '<?=$order->uid?>';
        if (confirm('Er du sikker på at du vil annullere denne ordre?')) {
            // TODO: Implement API call to cancel order
            console.log('Cancel order:', orderId);
        }
    }
</script>
<?php scriptEnd(); ?>
