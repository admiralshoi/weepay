<?php
/**
 * Admin Panel - Maintenance Mode
 * Toggle maintenance mode on/off
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;
use features\Settings;

$pageTitle = "Maintenance";

// Check if maintenance mode setting exists, default to false
$maintenanceMode = Settings::$app->maintenance_mode ?? false;
$maintenanceMessage = Settings::$app->maintenance_message ?? 'Systemet er under vedligeholdelse. Prøv igen senere.';






?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "maintenance";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <?php

            $pmId = "7d389cfe-aa78-4ec7-a6f9-d87e5e80d5df";

//            prettyPrint(\classes\Methods::viva()->getPayment("cd0ca7f2-456e-44e3-a983-b71053f67745", $pmId));

            ?>

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Maintenance</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Maintenance Mode</h1>
                    <p class="mb-0 font-14 color-gray">Sæt systemet i vedligeholdelsestilstand</p>
                </div>
            </div>

            <!-- Status Card -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center">
                        <div class="flex-row-start flex-align-center" style="gap: 1rem;">
                            <?php if($maintenanceMode): ?>
                                <div class="square-60 bg-warning border-radius-50 flex-row-center-center">
                                    <i class="mdi mdi-alert-circle color-white font-30"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-18 font-weight-bold color-warning">Maintenance Mode AKTIV</p>
                                    <p class="mb-0 font-14 color-gray">Brugere kan ikke tilgå systemet</p>
                                </div>
                            <?php else: ?>
                                <div class="square-60 bg-success border-radius-50 flex-row-center-center">
                                    <i class="mdi mdi-check-circle color-white font-30"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-18 font-weight-bold color-success">System Online</p>
                                    <p class="mb-0 font-14 color-gray">Alt fungerer normalt</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if($maintenanceMode): ?>
                            <button class="btn-v2 success-btn" onclick="toggleMaintenance(false)">
                                <i class="mdi mdi-power mr-1"></i> Deaktiver Maintenance
                            </button>
                        <?php else: ?>
                            <button class="btn-v2 warning-btn" onclick="toggleMaintenance(true)">
                                <i class="mdi mdi-tools mr-1"></i> Aktiver Maintenance
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Message Configuration -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                        <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-message-text-outline color-white font-20"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-16 font-weight-bold">Vedligeholdelsesbesked</p>
                            <p class="mb-0 font-12 color-gray">Denne besked vises til brugere under vedligeholdelse</p>
                        </div>
                    </div>

                    <div class="flex-col-start" style="gap: 1rem;">
                        <textarea class="form-field-v2" id="maintenanceMessage" rows="3" placeholder="Systemet er under vedligeholdelse..."><?=htmlspecialchars($maintenanceMessage)?></textarea>
                        <div class="flex-row-end">
                            <button class="btn-v2 action-btn" onclick="saveMessage()">
                                <i class="mdi mdi-content-save-outline mr-1"></i> Gem besked
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card border-radius-10px bg-lightest-blue">
                <div class="card-body">
                    <div class="flex-row-start" style="gap: .75rem;">
                        <i class="mdi mdi-information-outline font-20 color-blue"></i>
                        <div class="flex-col-start">
                            <p class="mb-0 font-14 font-weight-medium color-dark">Om Maintenance Mode</p>
                            <p class="mb-0 font-13 color-gray mt-1">
                                Når maintenance mode er aktiv, vil alle brugere (undtagen administratorer) se vedligeholdelsesbeskeden i stedet for det normale system. Administratorer kan stadig tilgå systemet normalt.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    async function toggleMaintenance(enable) {
        const action = enable ? 'aktivere' : 'deaktivere';
        const result = await SweetAlert.fire({
            icon: enable ? 'warning' : 'question',
            title: enable ? 'Aktiver Maintenance Mode?' : 'Deaktiver Maintenance Mode?',
            text: enable
                ? 'Alle brugere vil blive låst ude af systemet indtil du deaktiverer maintenance mode.'
                : 'Systemet vil være tilgængeligt for alle brugere igen.',
            showCancelButton: true,
            confirmButtonText: 'Ja, ' + action,
            cancelButtonText: 'Annuller',
            confirmButtonColor: enable ? '#f59e0b' : '#22c55e'
        });

        if (result.isConfirmed) {
            const apiResult = await post('<?=__url(Links::$api->admin->panel->updateSetting)?>', {
                key: 'maintenance_mode',
                value: enable
            });

            if (apiResult.status === 'success') {
                SweetAlert.fire({
                    icon: 'success',
                    title: enable ? 'Maintenance Mode Aktiveret' : 'Maintenance Mode Deaktiveret',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            } else {
                SweetAlert.fire({ icon: 'error', title: 'Fejl', text: apiResult.message || 'Der opstod en fejl' });
            }
        }
    }

    async function saveMessage() {
        const message = $('#maintenanceMessage').val().trim();
        if (!message) {
            SweetAlert.fire({ icon: 'error', title: 'Fejl', text: 'Beskeden kan ikke være tom' });
            return;
        }

        const result = await post('<?=__url(Links::$api->admin->panel->updateSetting)?>', {
            key: 'maintenance_message',
            value: message
        });

        if (result.status === 'success') {
            SweetAlert.fire({
                icon: 'success',
                title: 'Besked gemt',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            SweetAlert.fire({ icon: 'error', title: 'Fejl', text: result.message || 'Der opstod en fejl' });
        }
    }
</script>
<?php scriptEnd(); ?>
