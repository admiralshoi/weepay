<?php
/**
 * Demo Merchant Fulfilled Page - Order completed
 * @var object $args
 */

use classes\enumerations\Links;

$terminal = $args->terminal;
$location = $args->location;
$session = $args->session;
$customer = $args->customer;
$basket = $args->basket;
$order = $args->order;
$payments = $args->payments;
$pageTitle = "Demo - Ordre fuldført";
?>

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

<div class="page-content mt-5">
    <div class="page-inner-content">

        <!-- Success Animation -->
        <div class="flex-col-start flex-align-center" style="row-gap: 1rem;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); display: flex; align-items: center; justify-content: center;">
                <i class="mdi mdi-check font-50 color-white"></i>
            </div>
            <h2 class="mb-0 font-weight-bold">Ordre Fuldført!</h2>
            <p class="mb-0 color-gray">Betalingen er gennemført</p>
        </div>

        <!-- Order Summary Card -->
        <div class="card mt-4">
            <div class="card-header bg-green text-white">
                <h5 class="mb-0 font-weight-bold">
                    <i class="mdi mdi-receipt mr-2"></i>
                    Ordrekvittering
                </h5>
            </div>
            <div class="card-body">
                <!-- Order ID -->
                <div class="flex-row-between flex-align-center pb-3 border-bottom-card">
                    <span class="color-gray">Ordre ID</span>
                    <span class="font-weight-bold"><?=$order->prid?></span>
                </div>

                <!-- Customer -->
                <div class="flex-row-between flex-align-center py-3 border-bottom-card">
                    <span class="color-gray">Kunde</span>
                    <span class="font-weight-bold"><?=$customer->name ?? 'Ukendt'?></span>
                </div>

                <!-- Item -->
                <div class="flex-row-between flex-align-center py-3 border-bottom-card">
                    <span class="color-gray">Vare</span>
                    <span class="font-weight-bold"><?=$basket->name?></span>
                </div>

                <!-- Payment Plan -->
                <div class="flex-row-between flex-align-center py-3 border-bottom-card">
                    <span class="color-gray">Betalingsplan</span>
                    <span class="font-weight-bold">
                        <?php
                        $planName = match($order->payment_plan) {
                            'direct' => 'Betal Nu',
                            'pushed' => 'Betal d. 1. i Måneden',
                            'installments' => 'Del i 4 Rater',
                            default => 'Fuld betaling',
                        };
                        echo $planName;
                        ?>
                    </span>
                </div>

                <!-- Total -->
                <div class="flex-row-between flex-align-center pt-3">
                    <span class="font-18 font-weight-bold">Total</span>
                    <span class="font-25 font-weight-bold color-green">
                        <?=number_format($basket->price, 2, ',', '.')?> kr.
                    </span>
                </div>
            </div>
        </div>

        <!-- Payment Schedule -->
        <?php if(is_array($payments) && count($payments) > 1): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="mb-0 font-weight-bold">
                    <i class="mdi mdi-calendar-clock mr-2"></i>
                    Betalingsplan
                </h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach($payments as $payment): ?>
                    <?php $payment = (object) $payment; ?>
                    <li class="list-group-item flex-row-between flex-align-center">
                        <div>
                            <span class="font-weight-bold">Rate <?=$payment->installment_number?></span>
                            <span class="color-gray ml-2">
                                <?php if($payment->status === 'COMPLETED'): ?>
                                    (Betalt <?=date('d/m/Y', strtotime($payment->paid_at))?>)
                                <?php else: ?>
                                    (Forfald: <?=date('d/m/Y', strtotime($payment->due_date))?>)
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                            <span class="font-weight-bold"><?=number_format($payment->amount, 2, ',', '.')?> kr.</span>
                            <?php if($payment->status === 'COMPLETED'): ?>
                                <i class="mdi mdi-check-circle color-green font-18"></i>
                            <?php else: ?>
                                <i class="mdi mdi-clock-outline color-gray font-18"></i>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="demo-action-group mt-4">
            <a href="<?=__url(Links::$demo->cashier)?>" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-plus font-18"></i>
                <span>Start ny ordre</span>
            </a>
            <a href="<?=__url(Links::$demo->landing)?>" class="btn-v2 trans-btn flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-home font-18"></i>
                <span>Tilbage til demo forside</span>
            </a>
        </div>

    </div>
</div>
