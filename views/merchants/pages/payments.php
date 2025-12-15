<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Betalinger";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Betalinger</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle gennemførte betalinger</p>
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
                        <i class="mdi mdi-cash-check font-18 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Gennemførte Betalinger</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="color-gray">
                                <th>Betaling ID</th>
                                <th>Ordre ID</th>
                                <th>Kunde</th>
                                <th>Beløb</th>
                                <th>Rate</th>
                                <th>Betalt Dato</th>
                                <th>Forfald Dato</th>
                                <th>Handlinger</th>
                            </thead>
                            <tbody>
                            <?php if($args->payments->count() > 0): ?>
                                <?php foreach ($args->payments->list() as $payment): ?>
                                    <?php $order = $payment->order; ?>
                                    <?php $customer = $order->uuid ?? null; ?>
                                    <tr>
                                        <td>
                                            <p class="mb-0 font-12 font-monospace"><?=$payment->uid?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-monospace"><?=$order->uid ?? 'N/A'?></p>
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
                                            <p class="mb-0 font-12 font-weight-bold color-success-text"><?=number_format($payment->amount, 2)?> <?=currencySymbol($payment->currency)?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=$payment->installment_number?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=!isEmpty($payment->paid_at) ? date("d/m-Y H:i", strtotime($payment->paid_at)) : 'N/A'?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=date("d/m-Y", strtotime($payment->due_date))?></p>
                                        </td>
                                        <td>
                                            <a href="<?=__url(Links::$merchant->orderDetail($order->uid))?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-eye-outline font-16"></i>
                                                <span class="font-14">Se ordre</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <p class="mb-0 color-gray font-14 py-3">Ingen gennemførte betalinger fundet</p>
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

