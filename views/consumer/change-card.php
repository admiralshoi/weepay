<?php
/**
 * Change Card Page - Payments grouped by payment method
 * Allows users to change cards for specific payment method groups
 *
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Skift Betalingskort";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
    var paymentsByCardApiUrl = platformLinks.api.consumer.payments + '-by-card';
    var changeCardForPaymentMethodUrl = platformLinks.api.consumer.changeCard + '/payment-method';
</script>

<div class="page-content">

    <div class="flex-row-between flex-align-start flex-wrap mb-4" style="gap: 1rem;">
        <div class="flex-col-start">
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 mute-btn btn-sm">
                    <i class="mdi mdi-arrow-left"></i>
                </a>
                <p class="mb-0 font-30 font-weight-bold">Skift Betalingskort</p>
            </div>
            <p class="mb-0 font-16 font-weight-medium color-gray mt-2">
                Vælg hvilket kort du vil skifte til et nyt
            </p>
        </div>
    </div>

    <!-- Loading State -->
    <div id="change-card-loading" class="card border-radius-10px">
        <div class="card-body p-5">
            <div class="flex-col-center flex-align-center">
                <span class="spinner-border color-primary-cta square-40" role="status" style="border-width: 3px;">
                    <span class="sr-only">Indlæser...</span>
                </span>
                <p class="color-gray mt-3 mb-0">Indlæser dine betalingskort...</p>
            </div>
        </div>
    </div>

    <!-- No Cards State -->
    <div id="change-card-empty" class="card border-radius-10px d-none">
        <div class="card-body p-5 text-center">
            <i class="mdi mdi-credit-card-off-outline font-60 color-gray"></i>
            <p class="font-18 font-weight-medium mt-3 mb-2">Ingen kort at skifte</p>
            <p class="color-gray mb-4">Du har ingen kommende betalinger der kan få kort skiftet.</p>
            <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 action-btn">
                <i class="mdi mdi-arrow-left mr-1"></i>
                Tilbage til betalinger
            </a>
        </div>
    </div>

    <!-- Card Groups Container -->
    <div id="change-card-groups" class="d-none">
        <!-- Summary -->
        <div class="alert alert-info mb-4" role="alert">
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <i class="mdi mdi-information-outline font-20"></i>
                <p class="mb-0 font-14">
                    <span id="total-payments-count">0</span> betalinger fordelt på <span id="total-cards-count">0</span> kort
                </p>
            </div>
        </div>

        <!-- Card Groups will be inserted here -->
        <div id="card-groups-list"></div>
    </div>

</div>

<!-- Card Group Template -->
<template id="card-group-template">
    <div class="card border-radius-10px mb-3 card-group-item" data-payment-method="">
        <div class="card-body p-4">
            <!-- Card Header -->
            <div class="flex-row-between flex-align-start flex-wrap" style="gap: 1rem;">
                <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                    <!-- Card Icon -->
                    <div class="card-icon-container" style="width: 48px; height: 48px; background: var(--primary-cta-faded, #f0f4ff); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="mdi mdi-credit-card font-24 color-primary-cta"></i>
                    </div>
                    <!-- Card Info -->
                    <div>
                        <p class="mb-0 font-18 font-weight-bold card-title-text">Kort</p>
                        <p class="mb-0 font-14 color-gray card-expiry-text"></p>
                        <p class="mb-0 font-12 color-gray card-organisation-text"></p>
                    </div>
                </div>
                <!-- Change Card Button -->
                <button type="button" class="btn-v2 action-btn change-card-btn flex-row-center flex-align-center" style="gap: .5rem; white-space: nowrap;">
                    <i class="mdi mdi-credit-card-refresh-outline font-16"></i>
                    <span>Skift kort</span>
                </button>
            </div>

            <!-- Summary Row -->
            <div class="flex-row-start flex-align-center flex-wrap mt-3 mb-3" style="gap: 1.5rem;">
                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-counter font-16 color-gray"></i>
                    <span class="font-14 color-gray"><span class="payment-count">0</span> betalinger</span>
                </div>
                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-cash font-16 color-gray"></i>
                    <span class="font-14 color-gray">I alt: <span class="total-amount">0,00 kr</span></span>
                </div>
            </div>

            <!-- Collapsible Payments List -->
            <div class="payments-toggle-container">
                <button type="button" class="btn-v2 mute-btn btn-sm toggle-payments-btn flex-row-center flex-align-center" style="gap: .35rem;">
                    <i class="mdi mdi-chevron-down font-16 toggle-icon"></i>
                    <span class="toggle-text">Vis betalinger</span>
                </button>
            </div>

            <div class="payments-list-container d-none mt-3">
                <div class="table-responsive">
                    <table class="table-v2 table-sm">
                        <thead>
                            <tr>
                                <th>Betalings ID</th>
                                <th>Butik</th>
                                <th>Beløb</th>
                                <th>Forfaldsdato</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="payments-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Payment Row Template -->
<template id="payment-row-template">
    <tr>
        <td class="payment-uid font-12"></td>
        <td class="payment-location"></td>
        <td class="payment-amount"></td>
        <td class="payment-due-date"></td>
        <td class="payment-status"></td>
    </tr>
</template>
