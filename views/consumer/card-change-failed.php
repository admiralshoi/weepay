<?php
/**
 * Card Change Failed Page
 * Displayed when card change validation fails
 *
 * @var object $args
 */

use classes\enumerations\Links;

$error = $args->error ?? 'Der opstod en fejl under kortskift';
$retryUrl = $args->retryUrl ?? __url(Links::$consumer->payments);

$pageTitle = "Kortskift Fejlet";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
</script>

<div class="page-content">

    <div class="flex-col-center flex-align-center" style="min-height: 60vh;">
        <div class="card border-radius-10px" style="max-width: 500px; width: 100%;">
            <div class="card-body p-5 text-center">
                <!-- Error Icon -->
                <div class="mb-4">
                    <div class="error-icon-container">
                        <i class="mdi mdi-alert-circle-outline font-80 color-red"></i>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="font-24 font-weight-bold mb-3">Kortskift Fejlet</h2>

                <!-- Error Message -->
                <div class="danger-info-box p-3 mb-4">
                    <p class="mb-0 font-14 color-red"><?=htmlspecialchars($error)?></p>
                </div>

                <!-- Helpful Info -->
                <p class="font-14 color-gray mb-4">
                    Kontroller venligst at dit kort er gyldigt og har tilstrækkelig dækning.
                    Du kan forsøge igen eller kontakte butikken for hjælp.
                </p>

                <!-- Action Buttons -->
                <div class="flex-col-start" style="gap: .75rem;">
                    <a href="<?=htmlspecialchars($retryUrl)?>" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-refresh font-16"></i>
                        <span class="font-14">Prøv igen</span>
                    </a>

                    <a href="<?=__url(Links::$consumer->payments)?>" class="btn-v2 mute-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-arrow-left font-16"></i>
                        <span class="font-14">Tilbage til betalinger</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
