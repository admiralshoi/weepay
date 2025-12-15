<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;


$payments= $args->payments;
$order= $args->order;
$customer= $args->customer;
$orderStatus = $order->status ?? 'DRAFT';
$firstPayment = $payments->first();
$paymentStatus = $firstPayment?->status ?? 'PENDING';

// Determine if order is successful
$isSuccess = in_array($orderStatus, ['COMPLETED']) &&
             in_array($paymentStatus, ['COMPLETED']);

$statusColor = match($paymentStatus) {
    'COMPLETED' => 'success',
    'PENDING' => 'warning',
    'FAILED', 'CANCELLED' => 'danger',
    default => 'secondary'
};

$statusText = match($paymentStatus) {
    'COMPLETED' => 'Betaling gennemført',
    'PENDING' => 'Afventer betaling',
    'FAILED' => 'Betaling mislykkedes',
    'CANCELLED' => 'Betaling annulleret',
    'REFUNDED' => 'Refunderet',
    'SCHEDULED' => 'Planlagt',
    default => 'Ukendt status'
};

$statusIcon = match($paymentStatus) {
    'COMPLETED' => 'check-circle',
    'PENDING' => 'clock-outline',
    'FAILED', 'CANCELLED' => 'close-circle',
    'REFUNDED' => 'cash-refund',
    default => 'help-circle'
};


$pageTitle = "Ordrebekræftelse - " . $order->location->name;
?>

<style>
    .confirmation-header {
        background: <?=$isSuccess ? 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' : 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'?>;
    }
</style>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>


<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="confirmation-container mx-auto">
            <!-- Header -->
            <div class="confirmation-header">
                <div class="status-icon">
                    <i class="mdi mdi-<?=$statusIcon?>"></i>
                </div>
                <h1><?=$isSuccess ? 'Tak for din ordre!' : 'Ordre status'?></h1>
                <p><?=$statusText?></p>
            </div>

            <!-- Body -->
            <div class="confirmation-body">
                <!-- Order Information -->
                <div class="info-section">
                    <h2>
                        <i class="mdi mdi-file-document-outline"></i>
                        Ordreoplysninger
                    </h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Ordre ID</span>
                            <span class="info-value"><?=$order->prid?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Status</span>
                            <span class="status-badge <?=$statusColor?>">
                                <i class="mdi mdi-<?=$statusIcon?>"></i>
                                <?=$statusText?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Beløb</span>
                            <span class="info-value"><?=number_format($order->amount, 2)?> <?=$order->currency?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Dato</span>
                            <span class="info-value"><?=date('d/m/Y H:i', strtotime($order->created_at))?></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">Forretning</span>
                            <span class="info-value"><?=$order->location->name?></span>
                        </div>
                    </div>
                </div>

                <?php if($payments->count() > 0): ?>
                <div class="divider"></div>

                <!-- Payment Schedule -->
                <div class="info-section">
                    <h2>
                        <i class="mdi mdi-calendar-clock"></i>
                        Betalingsplan
                    </h2>
                    <ul class="payment-list">
                        <?php foreach($payments->list() as $payment): ?>
                        <li class="payment-item">
                            <div class="payment-item-info">
                                <span class="payment-installment">
                                    Rate <?=$payment->installment_number?> af <?=$payments->count()?>
                                </span>
                                <span class="payment-due">
                                    <?php if($payment->status === 'COMPLETED'): ?>
                                        Betalt <?=date('d/m/Y', strtotime($payment->paid_at))?>
                                    <?php else: ?>
                                        Forfald: <?=date('d/m/Y', strtotime($payment->due_date))?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="payment-amount">
                                <?=number_format($payment->amount, 2)?> <?=$payment->currency?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="divider"></div>

                <!-- Customer Information -->
                <div class="info-section">
                    <h2>
                        <i class="mdi mdi-account-outline"></i>
                        Kundeoplysninger
                    </h2>
                    <div class="info-grid">
                        <div class="info-item full-width">
                            <span class="info-label">Navn</span>
                            <span class="info-value"><?=$customer->full_name?></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">E-mail</span>
                            <span class="info-value"><?=$customer->email?></span>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="<?=__url(Links::$consumer->dashboard)?>" class="btn btn-primary">
                        <i class="mdi mdi-home"></i>
                        Tilbage min side
                    </a>
                    <?php if($isSuccess && $paymentStatus === 'COMPLETED'): ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="mdi mdi-printer"></i>
                        Print kvittering
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

