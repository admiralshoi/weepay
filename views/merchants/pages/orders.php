<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Ordrer";


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions);?>
        <div class="flex-row-end">

        </div>
    </div>



    <?php  //prettyPrint(\classes\Methods::viva()->getPayment('ee6d19b2-8b9e-41ed-874e-044680beeae7', 'f3781870-0105-4122-ae21-551560022e27')); ?>


    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Ordrer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle ordrer</p>
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
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <i class="mdi mdi-cart-outline font-16 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle ordrer</p>
                    </div>

                    <div class="mt-2">
                        <table class="table table-hover">
                            <thead class="color-gray">
                            <th>Ordre ID</th>
                            <th>Dato & Tid</th>
                            <th>Kunde</th>
                            <th>Total</th>
                            <th>Net Total</th>
                            <th>Udestående</th>
                            <th>Risikoscore</th>
                            <th>Status</th>
                            <th>Handlinger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->orders->list() as $order): ?>
                                <tr>
                                    <td><?=$order->uid?></td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                                    </td>
                                    <td>
                                            <?php if(!isEmpty($order->uuid)): ?>
                                            <a href="<?=__url(Links::$merchant->customerDetail($order->uuid->uid))?>"
                                               class="color-blue hover-underline"><?=$order->uuid->full_name?></a>
                                            <?php else: ?>
                                            <p class="mb-0 font-12 text-wrap">Ukendt</p>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format($order->amount) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format($order->amount - $order->fee_amount) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=number_format(0) . currencySymbol($order->currency)?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">NaN</p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">
                                            <?php if($order->status === 'COMPLETED'): ?>
                                                <span class="success-box">Gennemført</span>
                                            <?php elseif($order->status === 'DRAFT'): ?>
                                                <span class="mute-box">Draft</span>
                                            <?php elseif($order->status === 'PENDING'): ?>
                                                <span class="action-box">Afvikles</span>
                                            <?php elseif($order->status === 'CANCELLED'): ?>
                                                <span class="action-box">Cancelled</span>
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td>
                                        <a href="<?=__url(Links::$merchant->orderDetail($order->uid))?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                            <i class="mdi mdi-eye-outline font-16"></i>
                                            <span class="font-14">Detaljer</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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


