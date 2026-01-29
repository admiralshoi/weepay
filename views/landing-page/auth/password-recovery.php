<?php
/**
 * Password Recovery Request Page
 * @var object $args
 * @var array $worldCountries
 */

use classes\enumerations\Links;

$worldCountries = $args->worldCountries ?? [];

$pageTitle = "Nulstil adgangskode";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    var passwordRecoveryApiUrl = <?=json_encode(__url(Links::$api->auth->passwordRecovery))?>;
</script>

<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <form id="password-recovery-form" class="flex-col-start recaptcha" style="row-gap: 1.5rem;">
                        <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til login</span>
                        </a>

                        <!-- User type toggle -->
                        <div class="w-100" style="display: grid; grid-template-columns: 1fr 1fr; border: 1px solid var(--card-border-color); border-radius: 8px; overflow: hidden;">
                            <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="flex-row-center-center py-2 color-dark font-weight-medium font-14 transition-all" style="gap: .4rem; text-decoration: none; background: var(--card-bg-hover);">
                                <i class="mdi mdi-account-outline"></i>
                                <span>Forbruger</span>
                            </a>
                            <a href="<?=__url(Links::$app->auth->merchantLogin)?>" class="flex-row-center-center py-2 color-dark font-weight-medium font-14 transition-all" style="gap: .4rem; text-decoration: none; background: var(--card-bg-hover);">
                                <i class="mdi mdi-store-outline"></i>
                                <span>Forhandler</span>
                            </a>
                        </div>

                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                <i class="font-35 color-design-blue mdi mdi-lock-reset"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Glemt adgangskode?</p>
                            <p class="mb-0 font-14 color-gray font-weight-medium text-center">Indtast din email eller telefonnummer for at modtage et link til at nulstille din adgangskode</p>
                        </div>

                        <!-- Success message (hidden by default) -->
                        <div class="flex-row-start flex-align-start w-100 d-none" id="recovery-success-message" style="gap: .5rem; padding: .75rem; background: #e8f5e9; border-radius: 8px; border-left: 3px solid #4caf50;">
                            <i class="mdi mdi-check-circle-outline color-green font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold color-green">Link sendt!</p>
                                <p class="mb-0 font-12 color-gray">
                                    Hvis kontoen findes, vil du modtage et link til at nulstille din adgangskode. Tjek din email eller SMS.
                                </p>
                            </div>
                        </div>

                        <!-- Resend section with timer (hidden by default) -->
                        <div class="flex-col-start w-100 d-none" id="recovery-resend-section" style="row-gap: .5rem;">
                            <!-- Timer display -->
                            <div class="flex-row-center flex-align-center w-100 d-none" id="recovery-timer-display" style="gap: .5rem; padding: .5rem; background: var(--card-bg-hover); border-radius: 6px;">
                                <i class="mdi mdi-timer-outline color-gray font-16"></i>
                                <span class="font-13 color-gray">Du kan sende et nyt link om <span id="recovery-timer-countdown" class="font-weight-bold">60</span> sekunder</span>
                            </div>

                            <!-- Resend link (shown after timer expires) -->
                            <div class="flex-row-center flex-align-center w-100 d-none" id="recovery-resend-link">
                                <a href="#" class="color-blue font-13 hover-underline flex-row-center flex-align-center" style="gap: .35rem;">
                                    <i class="mdi mdi-refresh font-16"></i>
                                    <span>Modtog du ikke linket? Send igen</span>
                                </a>
                            </div>
                        </div>

                        <!-- Form fields (shown by default) -->
                        <div class="flex-col-start w-100 recovery-form-fields" style="row-gap: .75rem;">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Email eller telefonnummer</p>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                    <div class="recovery-country-code-container d-none">
                                        <select class="form-select-v2 h-45px w-70px dropdown-no-arrow border-radius-tr-br-0-5rem"
                                                data-search="true" name="phone_country_code" id="recovery_phone_country_code">
                                            <?php foreach ($worldCountries as $country): ?>
                                                <option data-sort="<?=$country->countryNameEn?>_<?=$country->countryCode?>_<?=$country->countryNameLocal?>_<?=$country->countryCallingCode?>"
                                                        value="<?=$country->countryCode?>" <?=$country->countryCode === \features\Settings::$app->default_country ? 'selected' : ''?>>
                                                    <div class="flex-row-center flex-align-center flex-nowrap" style="gap: .25rem;">
                                                        <span class=""><?=$country->flag?></span>
                                                        <span class="">+<?=$country->countryCallingCode?></span>
                                                    </div>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="text" class="w-100 form-field-v2 recovery-identifier-field" name="identifier" id="identifier" placeholder="kontakt@dinbutik.dk eller 12345678">
                                </div>
                                <p class="mb-0 font-12 color-gray">Indtast den email eller telefonnummer der er tilknyttet din konto</p>
                            </div>
                        </div>

                        <div class="flex-col-start w-100 recovery-form-fields" style="row-gap: .75rem;">
                            <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="recovery-button" id="recovery-button">
                                <span>Send nulstillingslink</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>

                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium" style="gap: .25rem;">
                                <span class="color-gray font-13">Husker du din adgangskode?</span>
                                <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="color-blue font-13 hover-underline">Log ind her</a>
                            </div>
                        </div>

                        <!-- Back to login link (shown after success) -->
                        <div class="flex-col-start w-100 d-none" id="recovery-success-actions" style="row-gap: .75rem;">
                            <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap text-decoration-none" style="gap: .5rem;">
                                <i class="mdi mdi-arrow-left"></i>
                                <span>Tilbage til login</span>
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

                    </form>
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
