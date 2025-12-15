<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Ordre";


?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "orders";
</script>

<div class="page-content">


    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Mine ordrer</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle dine gennemførte køb</p>
    </div>

    <div class="card border-radius-10px mt-4">
        <div class="card-body">
            <?php if($args->ordersList->count() === 0): ?>
                <div class="flex-col-center py-5">
                    <i class="mdi mdi-cart-outline font-48 color-gray mb-2"></i>
                    <p class="mb-0 font-16 color-gray">Ingen ordrer endnu</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ordre ID</th>
                                <th>Dato</th>
                                <th>Beløb</th>
                                <th>Butik</th>
                                <th>Betalingsplan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($args->ordersList->list() as $order): ?>
                                <tr>
                                    <td>
                                        <a href="<?=__url(Links::$consumer->orderDetail . '/' . $order->uid)?>" class="mb-0 font-14 font-weight-medium color-blue">
                                            <?=substr($order->uid, 0, 8)?>
                                        </a>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14"><?=date('d/m/Y H:i', strtotime($order->created_at))?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14 font-weight-medium"><?=number_format($order->amount, 2)?> <?=currencySymbol("DKK")?></p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14">
                                            <?php if(is_object($order->location)): ?>
                                                <?=htmlspecialchars($order->location->name ?? 'N/A')?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </p>
                                    </td>
                                    <td>
                                        <p class="mb-0 font-14">
                                            <?php if($order->payment_plan === 'installments'): ?>
                                                <span class="action-box">Afdrag</span>
                                            <?php elseif($order->payment_plan === 'pushed'): ?>
                                                <span class="action-box">Udskudt</span>
                                            <?php else: ?>
                                                <span class="success-box">Fuld betaling</span>
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
