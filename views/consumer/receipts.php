<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Kvitteringer";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "receipts";
</script>

<div class="page-content">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Kvitteringer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Alle dine gennemførte betalinger</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <?php if($args->paymentsList->count() === 0): ?>
                <div class="flex-col-center py-5">
                    <i class="mdi mdi-receipt font-48 color-gray mb-2"></i>
                    <p class="mb-0 font-16 color-gray">Ingen betalinger endnu</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Betalings ID</th>
                                <th>Betalt dato</th>
                                <th>Beløb</th>
                                <th>Ordre</th>
                                <th>Butik</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($args->paymentsList->list() as $payment): ?>
                                <tr>
                                    <td>
                                        <p class="mb-0 font-14 font-weight-medium"><?=substr($payment->uid, 0, 8)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14"><?=date('d/m/Y H:i', strtotime($payment->paid_at))?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14 font-weight-medium"><?=number_format($payment->amount, 2)?> <?=currencySymbol("DKK")?></p>
                                    </td>
                                    <td>
                                        <a href="<?=__url(Links::$consumer->orderDetail . '/' . (is_object($payment->order) ? $payment->order->uid : $payment->order))?>" class="mb-0 font-14 color-blue">
                                            <?=substr((is_object($payment->order) ? $payment->order->uid : $payment->order), 0, 8)?>
                                        </a>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14">
                                            <?php if(is_object($payment->order) && is_object($payment->order->location)): ?>
                                                <?=htmlspecialchars($payment->order->location->name ?? 'N/A')?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td>
                                        <span class="success-box">Gennemført</span>
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
