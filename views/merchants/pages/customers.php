<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Kunder";
?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "customers";
</script>


<div class="page-content home">

    <div class="flex-col-start">
        <p class="mb-0 font-30 font-weight-bold">Kunder</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Oversigt over alle kunder</p>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-multiple-outline font-18 color-blue"></i>
                        <p class="mb-0 font-22 font-weight-bold">Alle kunder</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="color-gray">
                                <th>Kunde</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Antal Ordrer</th>
                                <th>Total Forbrug</th>
                                <th>Første Ordre</th>
                                <th>Seneste Ordre</th>
                                <th>Handlinger</th>
                            </thead>
                            <tbody>
                            <?php if(!empty($args->customers)): ?>
                                <?php foreach ($args->customers as $customerData): ?>
                                    <?php $customer = $customerData->customer; ?>
                                    <?php if(isEmpty($customer)) continue; ?>
                                    <tr>
                                        <td>
                                            <p class="mb-0 font-14 font-weight-medium"><?=$customer->full_name ?? 'N/A'?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=$customer->email ?? 'N/A'?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=!isEmpty($customer->phone) ? '+' . $customer->phone : 'N/A'?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-weight-medium"><?=$customerData->order_count?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12 font-weight-bold color-success-text"><?=number_format($customerData->total_spent, 2)?> DKK</p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=date("d/m-Y", strtotime($customerData->first_order_date))?></p>
                                        </td>
                                        <td>
                                            <p class="mb-0 font-12"><?=date("d/m-Y", strtotime($customerData->last_order_date))?></p>
                                        </td>
                                        <td>
                                            <a href="<?=__url(Links::$merchant->customerDetail($customer->uid))?>" class="btn-v2 trans-btn flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                                <i class="mdi mdi-eye-outline font-16"></i>
                                                <span class="font-14">Se detaljer</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <p class="mb-0 color-gray font-14 py-3">Ingen kunder fundet</p>
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

