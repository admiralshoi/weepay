<?php
/**
 * Demo Landing Page - Role Selection
 * @var object $args
 */

use classes\enumerations\Links;

$location = $args->location;



?>


<!-- Demo Badge -->
<div class="demo-badge">
    <i class="mdi mdi-test-tube"></i>
    Demo Mode
</div>

<div class="page-content">
    <div class="page-inner-content">

        <div class="demo-landing">
            <!-- Hero Section -->
            <div class="demo-landing-hero">
                <div class="flex-row-center flex-align-center mb-4">
                    <div class="demo-store-avatar">
                        <i class="mdi mdi-store"></i>
                    </div>
                </div>
                <h1>Prøv WeePay Demo</h1>
                <p>
                    Oplev hvordan WeePay fungerer fra både kassererens og kundens perspektiv.
                    Vælg en rolle nedenfor for at starte demo'en.
                </p>
            </div>

            <!-- Role Selection Cards -->
            <div class="demo-role-cards">
                <!-- Merchant/Cashier Card -->
                <a href="<?=__url(Links::$demo->cashier)?>" class="demo-role-card merchant">
                    <div class="role-icon">
                        <i class="mdi mdi-store"></i>
                    </div>
                    <h3>Test som Kasserer</h3>
                    <p>Se hvordan butikken opretter en ordre og sender den til kunden.</p>
                    <span class="role-btn">Start som Kasserer</span>
                </a>

                <!-- Consumer Card -->
                <a href="<?=__url(Links::$demo->consumer)?>" class="demo-role-card consumer">
                    <div class="role-icon">
                        <i class="mdi mdi-account"></i>
                    </div>
                    <h3>Test som Kunde</h3>
                    <p>Prøv kundens oplevelse med MitID login og betalingsvalg.</p>
                    <span class="role-btn">Start som Kunde</span>
                </a>
            </div>

            <!-- Info Box -->
            <div class="demo-info-box mt-5" style="max-width: 600px;">
                <i class="mdi mdi-information-outline"></i>
                <div class="info-content">
                    <p class="info-title">Om denne demo</p>
                    <p class="info-text">
                        Denne demo bruger simulerede data og udfører ingen rigtige transaktioner.
                        Åbn to browservinduer for at teste begge roller samtidig.
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
