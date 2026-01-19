<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forsinkede Betalinger";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "past-due-payments";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Forsinkede Betalinger</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle forsinkede betalinger</p>
        </div>

        <!-- Date Filter -->
        <div class="flex-row-start flex-align-center flex-wrap" style="column-gap: .5rem; row-gap: .5rem;">
            <input type="date" id="start-date" class="form-control" style="max-width: 160px;"
                   value="<?=$args->startDate ?? ''?>" placeholder="Start dato">
            <input type="date" id="end-date" class="form-control" style="max-width: 160px;"
                   value="<?=$args->endDate ?? ''?>" placeholder="Slut dato">
            <button onclick="applyDateFilter()" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-filter"></i>
                <span>Filtrer</span>
            </button>
            <?php if(!isEmpty($args->startDate) || !isEmpty($args->endDate)): ?>
                <button onclick="clearDateFilter()" class="btn-v2 mute-btn flex-row-center flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-close"></i>
                    <span>Ryd</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-alert-circle-outline font-18 color-red"></i>
                        <p class="mb-0 font-22 font-weight-bold">Forsinkede Betalinger</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="color-gray">
                                <th>Betaling ID</th>
                                <th>Ordre ID</th>
                                <th>Kunde</th>
                                <th>Beløb</th>
                                <th>Rykkergebyr</th>
                                <th>Rate</th>
                                <th>Forfald Dato</th>
                                <th>Rykker</th>
                                <th>Dage Forsinket</th>
                                <th>Handlinger</th>
                            </thead>
                            <tbody>
                            <?php if($args->payments->count() > 0): ?>
                                <?php foreach ($args->payments->list() as $payment): ?>
                                    <?php $order = $payment->order; ?>
                                    <?php $customer = $order->uuid ?? null; ?>
                                    <?php
                                        $dueDate = strtotime($payment->due_date);
                                        $today = time();
                                        $daysOverdue = floor(($today - $dueDate) / (60 * 60 * 24));

                                        $rykkerLevel = (int)($payment->rykker_level ?? 0);
                                        $rykkerFee = (float)($payment->rykker_fee ?? 0);
                                        $sentToCollection = (bool)($payment->sent_to_collection ?? false);
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?=__url(Links::$merchant->paymentDetail($payment->uid))?>" class="mb-0 font-12 font-monospace color-blue hover-underline"><?=substr($payment->uid, 0, 10)?></a>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-monospace"><?=substr($order->uid ?? 'N/A', 0, 10)?></p>
                                        </td>
                                        <td>
                                            <?php if(!isEmpty($customer)): ?>
                                            <a href="<?=__url(Links::$merchant->customerDetail($customer->uid))?>"
                                               class="color-blue hover-underline font-12"><?=$customer->full_name?></a>
                                            <?php else: ?>
                                            <p class="mb-0 font-12">N/A</p>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-weight-bold color-red"><?=number_format($payment->amount, 2)?> <?=currencySymbol($payment->currency)?></p>
                                        </td>
                                        <td>
                                            <?php if($rykkerFee > 0): ?>
                                                <p class="mb-0 font-12 color-red"><?=number_format($rykkerFee, 2)?> <?=currencySymbol($payment->currency)?></p>
                                            <?php else: ?>
                                                <p class="mb-0 font-12 color-gray">-</p>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=$payment->installment_number?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-weight-medium"><?=date("d/m-Y", strtotime($payment->due_date))?></p>
                                        </td>
                                        <td>
                                            <?php if($sentToCollection): ?>
                                                <span class="danger-box font-11">Inkasso</span>
                                            <?php elseif($rykkerLevel === 0): ?>
                                                <span class="mute-box font-11">-</span>
                                            <?php elseif($rykkerLevel === 1): ?>
                                                <span class="warning-box font-11">Rykker 1</span>
                                            <?php elseif($rykkerLevel === 2): ?>
                                                <span class="warning-box font-11">Rykker 2</span>
                                            <?php else: ?>
                                                <span class="danger-box font-11">Rykker 3</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-weight-bold color-red"><?=$daysOverdue?> dage</p>
                                        </td>
                                        <td>
                                            <a href="<?=__url(Links::$merchant->paymentDetail($payment->uid))?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-eye-outline font-16"></i>
                                                <span class="font-14">Se</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">
                                        <p class="mb-0 color-gray font-14 py-3">Ingen forsinkede betalinger fundet</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>


<?php scriptStart(); ?>
<script>
    function applyDateFilter() {
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;

        const url = new URL(window.location.href);

        if (startDate) {
            url.searchParams.set('start', startDate);
        } else {
            url.searchParams.delete('start');
        }

        if (endDate) {
            url.searchParams.set('end', endDate);
        } else {
            url.searchParams.delete('end');
        }

        window.location.href = url.toString();
    }

    function clearDateFilter() {
        const url = new URL(window.location.href);
        url.searchParams.delete('start');
        url.searchParams.delete('end');
        window.location.href = url.toString();
    }
</script>
<?php scriptEnd(); ?>

