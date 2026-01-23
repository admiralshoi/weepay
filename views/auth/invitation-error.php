<?php
/**
 * @var object $args
 * @var string $error
 * @var string $error_type
 */

use classes\enumerations\Links;

$error = $args->error ?? 'Der opstod en fejl.';
$errorType = $args->error_type ?? 'unknown';

$pageTitle = "Invitation";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-col-start" style="row-gap: 1.5rem;">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                <?php if($errorType === 'expired'): ?>
                                <i class="font-35 color-orange mdi mdi-clock-alert-outline"></i>
                                <?php else: ?>
                                <i class="font-35 color-red mdi mdi-alert-circle-outline"></i>
                                <?php endif; ?>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">
                                <?php if($errorType === 'expired'): ?>
                                Invitation udløbet
                                <?php else: ?>
                                Ugyldig invitation
                                <?php endif; ?>
                            </p>
                            <p class="mb-0 font-14 color-gray font-weight-medium text-center" style="max-width: 300px;">
                                <?= htmlspecialchars($error) ?>
                            </p>
                        </div>

                        <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #fff3e0; border-radius: 8px; border-left: 3px solid #ff9800;">
                            <i class="mdi mdi-information-outline color-orange font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold" style="color: #e65100;">Hvad kan du gøre?</p>
                                <p class="mb-0 font-12 color-gray">
                                    <?php if($errorType === 'expired'): ?>
                                    Bed den person, der inviterede dig, om at sende en ny invitation.
                                    <?php else: ?>
                                    Kontakt administratoren for at få et nyt invitationslink.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <a href="<?= __url(Links::$app->auth->merchantLogin) ?>" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap text-decoration-none" style="gap: .5rem;">
                                <span>Gå til login</span>
                            </a>
                        </div>

                        <div class="alternative-box color-gray">
                            <span class="alternative-line"></span>
                        </div>

                        <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                            <div class="trans-info-badge">
                                <i class="mdi mdi-lock-outline color-blue font-16"></i>
                                <span>Sikker forbindelse</span>
                            </div>
                            <div class="trans-info-badge">
                                <i class="mdi mdi-shield-check-outline color-green font-16"></i>
                                <span>Krypteret</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
