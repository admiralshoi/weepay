<?php
/**
 * Demo Consumer Order Confirmation Page
 * @var object $args
 */

use classes\enumerations\Links;

$order = $args->order;
$customer = $args->customer;
$payments = $args->payments;

$isSuccess = $order->status === 'COMPLETED';
$statusColor = 'success';
$statusText = 'Ordre bekræftet';
$statusIcon = 'check-circle';

$pageTitle = "Demo - Ordrebekræftelse";
?>

<style>
    .confirmation-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<!-- Demo Badge -->
<div class="demo-badge">
    <i class="mdi mdi-test-tube"></i>
    Demo Mode
</div>

<!-- Demo Reset Link -->
<a href="<?=__url(Links::$demo->landing)?>" class="demo-reset-link">
    <i class="mdi mdi-refresh"></i>
    Nulstil Demo
</a>

<div class="page-content mt-3">
    <div class="page-inner-content">

        <div class="confirmation-container mx-auto">
            <!-- Header -->
            <div class="confirmation-header">
                <div class="status-icon">
                    <i class="mdi mdi-<?=$statusIcon?>"></i>
                </div>
                <h1>Tak for din ordre!</h1>
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
                            <span class="info-value"><?=number_format($order->amount, 2, ',', '.')?> <?=$order->currency?></span>
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
                                <?=number_format($payment->amount, 2, ',', '.')?> <?=$payment->currency?>
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
                            <span class="info-value"><?=$customer->full_name ?? $customer->name?></span>
                        </div>
                        <div class="info-item full-width">
                            <span class="info-label">E-mail</span>
                            <span class="info-value"><?=$customer->email?></span>
                        </div>
                    </div>
                </div>

                <!-- Demo Notice -->
                <div class="demo-info-box mt-4">
                    <i class="mdi mdi-information-outline"></i>
                    <div class="info-content">
                        <p class="info-title">Demo fuldført!</p>
                        <p class="info-text">
                            Dette var en simuleret ordre. I en rigtig transaktion ville du modtage en bekræftelsesmail,
                            og betalingerne ville blive trukket automatisk på de planlagte datoer.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="<?=__url(Links::$demo->landing)?>" class="btn btn-primary">
                        <i class="mdi mdi-home"></i>
                        Tilbage til demo
                    </a>
                    <a href="<?=__url(Links::$demo->consumer)?>" class="btn btn-secondary">
                        <i class="mdi mdi-refresh"></i>
                        Prøv igen
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
