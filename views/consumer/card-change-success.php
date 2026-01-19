<?php
/**
 * Card Change Success Page
 * Displayed after successfully updating the payment card
 *
 * @var object $args
 */

use classes\enumerations\Links;

$message = $args->message ?? 'Dit kort er blevet opdateret';
$updateCount = $args->updateCount ?? 0;
$returnUrl = $args->returnUrl ?? __url(Links::$consumer->payments);

$pageTitle = "Kort Opdateret";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "payments";
</script>

<div class="page-content">

    <div class="flex-col-center flex-align-center" style="min-height: 60vh;">
        <div class="card border-radius-10px" style="max-width: 500px; width: 100%;">
            <div class="card-body p-5 text-center">
                <!-- Success Icon -->
                <div class="mb-4">
                    <div class="success-check-container">
                        <i class="mdi mdi-check-circle font-80 color-success-text"></i>
                    </div>
                </div>

                <!-- Title -->
                <h2 class="font-24 font-weight-bold mb-3">Kort Opdateret</h2>

                <!-- Message -->
                <p class="font-16 color-gray mb-4"><?=htmlspecialchars($message)?></p>

                <?php if($updateCount > 0): ?>
                <div class="success-info-box p-3 mb-4">
                    <div class="flex-row-center flex-align-center" style="gap: .5rem;">
                        <i class="mdi mdi-credit-card-check-outline font-20 color-success-text"></i>
                        <p class="mb-0 font-14 color-success-text font-weight-medium">
                            <?=$updateCount?> betaling<?=$updateCount !== 1 ? 'er' : ''?> opdateret med nyt kort
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Return Button -->
                <a href="<?=htmlspecialchars($returnUrl)?>" class="btn-v2 action-btn w-100 flex-row-center flex-align-center" style="gap: .5rem;">
                    <i class="mdi mdi-arrow-left font-16"></i>
                    <span class="font-14">Tilbage</span>
                </a>
            </div>
        </div>
    </div>

</div>
