<?php
/**
 * Demo Merchant Details Page - Enter basket info
 * @var object $args
 */

use classes\enumerations\Links;

$terminal = $args->terminal;
$location = $args->location;
$session = $args->session;
$customer = $args->customer;
$pageTitle = "Demo - Opret kurv";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<!-- Demo Badge -->
<div class="demo-badge">
    <i class="mdi mdi-test-tube"></i>
    Demo Mode
</div>

<!-- Demo Reset Link -->
<a href="<?=__url(Links::$demo->landing)?>" class="demo-reset-link">
    <i class="mdi mdi-refresh"></i>
    Nulstil Demo
</a>

<div class="page-content mt-5">
    <div class="page-inner-content">

        <!-- Stepper -->
        <div class="stepper-progress demo-stepper">
            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">1</div>
                <div class="stepper-label">Kunde</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <div class="stepper-circle">2</div>
                <div class="stepper-label">Kurv</div>
            </div>
            <div class="stepper-line"></div>
            <div class="stepper-item">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Betaling</div>
            </div>
        </div>

        <!-- Store Header -->
        <div class="flex-col-start flex-align-center" style="row-gap: .5rem;">
            <p class="mb-0 font-20 font-weight-bold"><?=$location->name?></p>
            <span class="demo-status-badge active">
                <i class="mdi mdi-check-circle"></i>
                Session <?=$session->session_id?>
            </span>
        </div>

        <!-- Customer Card -->
        <div class="demo-customer-card mt-4">
            <div class="customer-header">
                <div class="customer-avatar">
                    <?=strtoupper(substr($customer->name ?? 'U', 0, 1))?>
                </div>
                <div class="customer-info">
                    <h4><?=$customer->name ?? 'Ukendt kunde'?></h4>
                    <p><?=$customer->email ?? ''?></p>
                </div>
            </div>

            <!-- Basket Form -->
            <form id="demo-basket-form" class="flex-col-start" style="row-gap: 1rem;">
                <div class="flex-col-start" style="row-gap: .25rem;">
                    <label class="font-14 font-weight-bold">Varenavn / Beskrivelse</label>
                    <input type="text" name="name" class="form-field-v2 w-100" placeholder="f.eks. iPhone 15 Pro" required>
                </div>

                <div class="flex-col-start" style="row-gap: .25rem;">
                    <label class="font-14 font-weight-bold">Pris (DKK)</label>
                    <input type="number" name="price" class="form-field-v2 w-100" placeholder="1000" min="1" step="0.01" required>
                </div>

                <div class="flex-col-start" style="row-gap: .25rem;">
                    <label class="font-14 font-weight-bold">Note (valgfri)</label>
                    <textarea name="note" class="form-field-v2 w-100" rows="2" placeholder="Eventuelle noter til ordren..."></textarea>
                </div>

                <div class="demo-action-group">
                    <button type="submit" class="btn-v2 action-btn flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-cart-plus font-18"></i>
                        <span>Opret kurv og send til kunde</span>
                    </button>
                    <a href="<?=__url(Links::$demo->cashier)?>" class="btn-v2 trans-btn flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-arrow-left font-18"></i>
                        <span>Tilbage</span>
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>
