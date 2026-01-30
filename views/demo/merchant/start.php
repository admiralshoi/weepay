<?php
/**
 * Demo Merchant Start Page - Waiting for customers
 * @var object $args
 */

use classes\enumerations\Links;

$terminal = $args->terminal;
$location = $args->location;
$pageTitle = "Demo - Afventer kunder";
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

        <!-- Store Header -->
        <div class="flex-col-start flex-align-center" style="row-gap: .75rem;">
            <p class="design-box mb-0 px-2">
                <i class="mdi mdi-store"></i>
                <span class="font-weight-bold">Kasserer Terminal</span>
            </p>

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="mb-0 font-25 font-weight-bold"><?=$location->name?></p>
                <p class="mb-0 font-14 font-weight-medium color-gray"><?=$terminal->name?></p>
            </div>
        </div>

        <!-- Info Box -->
        <div class="demo-info-box mt-4">
            <i class="mdi mdi-lightbulb-outline"></i>
            <div class="info-content">
                <p class="info-title">Demo Instruktion</p>
                <p class="info-text">
                    En simuleret kunde vil automatisk dukke op om 2 sekunder.
                    Klik pa "Start" for at oprette en kurv til kunden.
                </p>
            </div>
        </div>

        <!-- Waiting for customers -->
        <div id="demo-awaiting-customers" class="demo-waiting mt-4">
            <div class="waiting-icon">
                <i class="mdi mdi-account-clock-outline"></i>
            </div>
            <h3>Afventer kunder...</h3>
            <p>Kunder vil dukke op her, nar de scanner QR-koden</p>
        </div>

        <!-- Session table (hidden initially) -->
        <div id="demo-session-container" class="card mt-4" style="display: none;">
            <div class="card-header">
                <h5 class="mb-0 font-weight-bold">
                    <i class="mdi mdi-account-multiple-outline mr-2"></i>
                    Aktive Kunder
                </h5>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Kunde</th>
                            <th>Tidspunkt</th>
                            <th>Status</th>
                            <th>Handling</th>
                        </tr>
                    </thead>
                    <tbody id="demo-session-body">
                        <!-- Sessions will be populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
