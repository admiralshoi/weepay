<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Udestående betalinger";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "outstanding-payments";
</script>

<div class="page-content">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Udestående betalinger</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Alle betalinger der er planlagt eller forsinkede</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <?php if($args->paymentsList->count() === 0): ?>
                <div class="flex-col-center py-5">
                    <i class="mdi mdi-check-circle-outline font-48 color-green mb-2"></i>
                    <p class="mb-0 font-16 color-gray">Ingen udestående betalinger</p>
                    <p class="mb-0 font-14 color-gray">Du er helt opdateret!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Betalings ID</th>
                                <th>Forfaldsdato</th>
                                <th>Beløb</th>
                                <th>Ordre</th>
                                <th>Butik</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($args->paymentsList->list() as $payment): ?>
                                <?php
                                    $dueDate = strtotime($payment->due_date);
                                    $today = time();
                                    $daysOverdue = floor(($today - $dueDate) / (60 * 60 * 24));
                                    $isPastDue = $payment->status === 'PAST_DUE';
                                ?>
                                <tr class="<?=$isPastDue ? 'table-danger' : ''?>">
                                    <td>
                                        <p class="mb-0 font-14 font-weight-medium"><?=substr($payment->uid, 0, 8)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14"><?=date('d/m/Y', strtotime($payment->due_date))?></p>
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
                                        <?php if($isPastDue): ?>
                                            <div class="flex-col-start">
                                                <span class="danger-box">Forsinket</span>
                                                <p class="mb-0 font-12 font-weight-bold color-red mt-1"><?=$daysOverdue?> dage</p>
                                            </div>
                                        <?php else: ?>
                                            <span class="action-box">Planlagt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                    // Check if there are any past due payments
                    $hasPastDue = false;
                    foreach($args->paymentsList->list() as $payment) {
                        if($payment->status === 'PAST_DUE') {
                            $hasPastDue = true;
                            break;
                        }
                    }
                ?>

                <?php if($hasPastDue): ?>
                    <div class="alert alert-danger mt-3" role="alert">
                        <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                            <i class="mdi mdi-alert-circle-outline font-20"></i>
                            <div>
                                <p class="mb-0 font-14 font-weight-bold">Du har forsinkede betalinger</p>
                                <p class="mb-0 font-12">Forsinkede betalinger kan påvirke din mulighed for at bruge BNPL fremadrettet.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
