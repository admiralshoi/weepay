<?php
/**
 * @var object $args
 * @var object $user
 * @var bool $isForced
 */

use classes\enumerations\Links;

$user = $args->user;
$isForced = $args->isForced ?? false;

$pageTitle = "Skift adgangskode";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <form action="<?=Links::$api->auth->changePassword?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                <i class="font-35 color-design-blue mdi mdi-lock-reset"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Skift adgangskode</p>
                            <?php if($isForced): ?>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Du skal oprette en ny adgangskode for at fortsætte</p>
                            <?php else: ?>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Opret en ny sikker adgangskode til din konto</p>
                            <?php endif; ?>
                        </div>

                        <?php if($isForced): ?>
                        <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #fff3e0; border-radius: 8px; border-left: 3px solid #ff9800;">
                            <i class="mdi mdi-alert-outline color-orange font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold" style="color: #e65100;">Påkrævet handling</p>
                                <p class="mb-0 font-12 color-gray">
                                    Din konto kræver en adgangskodeændring. Du kan ikke fortsætte, før du har oprettet en ny adgangskode.
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                            <p class="mb-0 font-14 font-weight-bold">Ny adgangskode <span class="color-red">*</span></p>
                            <input type="password" class="form-field-v2 h-45px" name="new_password" id="new_password"
                                   placeholder="Indtast ny adgangskode" required minlength="8">
                            <p class="mb-0 font-12 color-gray">Mindst 8 tegn</p>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                            <p class="mb-0 font-14 font-weight-bold">Bekræft adgangskode <span class="color-red">*</span></p>
                            <input type="password" class="form-field-v2 h-45px" name="confirm_password" id="confirm_password"
                                   placeholder="Bekræft ny adgangskode" required minlength="8">
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="change-password-button" id="change-password-button">
                                <span>Gem adgangskode</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>
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

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
