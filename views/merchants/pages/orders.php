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


    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Ordrer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle ordrer</p>
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
                            <th >Handlinger</th>
                            </thead>
                            <tbody>
                            <?php foreach ($args->orders->list() as $order): ?>
                                <tr>
                                    <td><?=$order->uid?></td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap"><?=date("d/m-Y H:i", strtotime($order->created_at))?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-12 text-wrap">Customer Name...</p>
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
                                        <a href="<?=__url()?>" target="_blank" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
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




