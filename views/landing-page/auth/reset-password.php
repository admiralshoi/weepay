<?php
/**
 * Reset Password Page (after clicking link in email/SMS)
 * User is already logged in at this point, just needs to set new password
 *
 * @var object $args
 * @var object|null $user
 * @var bool $tokenValid
 * @var string|null $error
 */

use classes\enumerations\Links;

$tokenValid = $args->tokenValid ?? false;
$error = $args->error ?? null;
$user = $args->user ?? null;

$pageTitle = "Nulstil adgangskode";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var changePasswordApiUrl = <?=json_encode(__url(Links::$api->auth->changePassword))?>;
</script>

<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <?php if($tokenValid && !isEmpty($user)): ?>
                    <!-- Valid token - show password reset form -->
                    <form action="<?=Links::$api->auth->changePassword?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                <i class="font-35 color-design-blue mdi mdi-lock-reset"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Opret ny adgangskode</p>
                            <p class="mb-0 font-14 color-gray font-weight-medium text-center">Velkommen tilbage, <?=htmlspecialchars($user->full_name ?? 'bruger')?></p>
                        </div>

                        <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #e3f2fd; border-radius: 8px; border-left: 3px solid #2196f3;">
                            <i class="mdi mdi-information-outline color-blue font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold color-blue">Opret din nye adgangskode</p>
                                <p class="mb-0 font-12 color-gray">
                                    Vælg en sikker adgangskode på mindst 8 tegn. Du vil automatisk blive logget ind efter opdatering.
                                </p>
                            </div>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                            <p class="mb-0 font-14 font-weight-bold">Ny adgangskode <span class="color-red">*</span></p>
                            <div class="position-relative w-100">
                                <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="new_password" id="new_password"
                                       placeholder="Indtast ny adgangskode" required minlength="8">
                            </div>
                            <p class="mb-0 font-12 color-gray">Mindst 8 tegn</p>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                            <p class="mb-0 font-14 font-weight-bold">Bekræft adgangskode <span class="color-red">*</span></p>
                            <div class="position-relative w-100">
                                <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="confirm_password" id="confirm_password"
                                       placeholder="Bekræft ny adgangskode" required minlength="8">
                            </div>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="change-password-button" id="change-password-button">
                                <span>Gem ny adgangskode</span>

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

                    <?php else: ?>
                    <!-- Invalid or expired token - show error -->
                    <div class="flex-col-start" style="row-gap: 1.5rem;">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                <i class="font-35 color-red mdi mdi-alert-circle-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Link udløbet</p>
                            <p class="mb-0 font-14 color-gray font-weight-medium text-center"><?=htmlspecialchars($error ?? 'Linket er ugyldigt eller udløbet')?></p>
                        </div>

                        <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #ffebee; border-radius: 8px; border-left: 3px solid #f44336;">
                            <i class="mdi mdi-information-outline color-red font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold" style="color: #c62828;">Hvad kan du gøre?</p>
                                <p class="mb-0 font-12 color-gray">
                                    Links til nulstilling af adgangskode udløber efter 24 timer. Anmod om et nyt link for at fortsætte.
                                </p>
                            </div>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <a href="<?=__url(Links::$app->auth->passwordRecovery)?>" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap text-decoration-none" style="gap: .5rem;">
                                <i class="mdi mdi-refresh"></i>
                                <span>Anmod om nyt link</span>
                            </a>

                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium" style="gap: .25rem;">
                                <span class="color-gray font-13">Eller</span>
                                <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="color-blue font-13 hover-underline">gå til login</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>


            <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" class="color-gray font-12 hover-underline hover-color-blue">Vilkår & Betingelser</a>
                &bullet;
                <a href="<?=__url(Links::$policies->consumer->privacy)?>" class="color-gray font-12 hover-underline hover-color-blue">Privatlivspolitik</a>
                &bullet;
                <a href="<?=__url(Links::$support->public)?>" class="color-gray font-12 hover-underline hover-color-blue">Support</a>
            </div>
        </div>

    </div>
</div>
