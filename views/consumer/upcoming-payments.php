<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Kommende betalinger";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "upcoming-payments";
</script>

<div class="page-content">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Kommende betalinger</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Betalinger der er planlagt til fremtiden</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <?php if($args->paymentsList->count() === 0): ?>
                <div class="flex-col-center py-5">
                    <i class="mdi mdi-calendar-clock font-48 color-gray mb-2"></i>
                    <p class="mb-0 font-16 color-gray">Ingen kommende betalinger</p>
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
                                <th>Dage til forfalden</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($args->paymentsList->list() as $payment): ?>
                                <?php
                                    $dueDate = strtotime($payment->due_date);
                                    $today = time();
                                    $daysUntilDue = floor(($dueDate - $today) / (60 * 60 * 24));
                                ?>
                                <tr>
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
                                        <?php if($daysUntilDue < 0): ?>
                                            <span class="danger-box">Forfald i dag</span>
                                        <?php elseif((int)$daysUntilDue === 0): ?>
                                            <span class="warning-box">I dag</span>
                                        <?php elseif($daysUntilDue <= 7): ?>
                                            <p class="mb-0 font-12 font-weight-bold action-box"><?=$daysUntilDue?> dage</p>
                                        <?php else: ?>
                                            <p class="mb-0 font-12 success-box"><?=$daysUntilDue?> dage</p>
                                        <?php endif; ?>
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
