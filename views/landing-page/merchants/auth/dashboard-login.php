<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Forhandler login";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>






<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <form action="<?=Links::$api->auth->merchantLogin?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <a href="<?=__url(Links::$merchant->public->home)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til <?=BRAND_NAME?></span>
                        </a>

                        <!-- User type toggle -->
                        <div class="w-100" style="display: grid; grid-template-columns: 1fr 1fr; border: 1px solid var(--card-border-color); border-radius: 8px; overflow: hidden;">
                            <a href="<?=__url(Links::$app->auth->consumerLogin)?>" class="flex-row-center-center py-2 color-dark font-weight-medium font-14 transition-all" style="gap: .4rem; text-decoration: none; background: var(--card-bg-hover);">
                                <i class="mdi mdi-account-outline"></i>
                                <span>Forbruger</span>
                            </a>
                            <div class="flex-row-center-center py-2 bg-blue color-white font-weight-bold font-14" style="gap: .4rem;">
                                <i class="mdi mdi-store-outline"></i>
                                <span>Forhandler</span>
                            </div>
                        </div>

                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50 " >
                                <i class="font-35 color-design-blue mdi mdi-lock-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Login - <?=BRAND_NAME?> Forhandler </p>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Få adgang til din konto</p>
                        </div>


                        <!-- Login credentials section -->
                        <div class="flex-col-start w-100 login-credentials-section" style="row-gap: .75rem;">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Brugernavn, email eller telefonnummer</p>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                    <div class="login-country-code-container d-none">
                                        <select class="form-select-v2 h-45px w-70px dropdown-no-arrow border-radius-tr-br-0-5rem"
                                                data-search="true" name="phone_country_code" id="login_phone_country_code">
                                            <?php foreach ($args->worldCountries as $country): ?>
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
                                    <input type="text" class="w-100 form-field-v2 login-username-field" name="username" id="username" placeholder="kontakt@dinbutik.dk">
                                </div>
                            </div>
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Adgangskode</p>

                                <div class="position-relative w-100">
                                    <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="password" id="password" placeholder="******">
                                </div>
                            </div>
                        </div>

                        <!-- 2FA verification section - hidden by default -->
                        <div class="flex-col-start w-100 d-none login-2fa-section" style="row-gap: .75rem;">
                            <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #e3f2fd; border-radius: 8px; border-left: 3px solid #2196f3;">
                                <i class="mdi mdi-shield-check-outline color-blue font-18" style="margin-top: 2px;"></i>
                                <div class="flex-col-start" style="row-gap: .25rem;">
                                    <p class="mb-0 font-13 font-weight-bold color-blue">To-faktor godkendelse</p>
                                    <p class="mb-0 font-12 color-gray">
                                        Vi har sendt en verifikationskode til dit telefonnummer <span id="login-2fa-phone-hint" class="font-weight-bold"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Verifikationskode</p>
                                <input type="text" class="w-100 form-field-v2" name="2fa_code" id="login_2fa_code" placeholder="123456" maxlength="6">
                                <p class="mb-0 font-12 color-gray">Indtast den 6-cifrede kode sendt til dit telefonnummer</p>
                            </div>

                            <div class="flex-row-start flex-align-center" style="gap: .5rem;" id="login-2fa-timer-display">
                                <i class="mdi mdi-timer-sand color-gray font-16"></i>
                                <p class="mb-0 font-12 color-gray">Du kan anmode om en ny kode om <span id="login-2fa-timer-countdown" class="font-weight-bold">60</span> sekunder</p>
                            </div>

                            <a href="#" class="font-12 color-blue text-decoration-underline d-none" id="login-2fa-resend-link" style="cursor: pointer;">Send ny kode</a>
                            <a href="#" class="font-12 color-gray text-decoration-underline" id="login-2fa-back-link" style="cursor: pointer;">Tilbage til login</a>
                        </div>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-row-end login-credentials-section">
                                <a href="<?=__url(Links::$merchant->public->recovery)?>" class="color-blue hover-underline font-13">Glemt adgangskode?</a>
                            </div>
                            <button class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap login-credentials-section" style="gap: .5rem;" name="login-button" id="login-button">
                                <span>Log ind</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>
                            <button class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap d-none login-2fa-section" style="gap: .5rem;" name="verify-2fa-button" id="verify-2fa-button">
                                <span>Verificer</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>
                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium login-credentials-section" style="gap: .25rem;">
                                <span class="color-gray font-13">Har du ikke en konto?</span>
                                <a href="<?=__url(Links::$merchant->public->signup)?>" class="color-blue font-13 hover-underline">Tilmeld dig her</a>
                            </div>
                        </div>


                        <div class="alternative-box color-gray">
                            <span class="alternative-line"></span>
                        </div>

                        <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                            <div class="trans-info-badge">
                                <i class="mdi mdi-lock-outline color-blue font-16"></i>
                                <span>Sikker via VIVA</span>
                            </div>
                            <div class="trans-info-badge">
                                <i class="mdi mdi-check-circle-outline color-green font-16"></i>
                                <span>GDPR-kompatibel</span>
                            </div>
                            <div class="trans-info-badge">
                                <i class="mdi mdi-shield-outline color-blue font-16"></i>
                                <span>MitId verificeret</span>
                            </div>
                        </div>

                    </form>
                </div>
            </div>


            <div class="flex-row-center flex-align-center flex-wrap" style="gap: .5rem;">
                <a href="<?=__url(Links::$policies->merchant->termsOfUse)?>" class="color-gray font-12 hover-underline hover-color-blue">Vilkår & Betingelser</a>
                &bullet;
                <a href="<?=__url(Links::$policies->merchant->privacy)?>" class="color-gray font-12 hover-underline hover-color-blue">Privatlivspolitik</a>
                &bullet;
                <a href="<?=__url(Links::$support->public)?>" class="color-gray font-12 hover-underline hover-color-blue">Support</a>
            </div>
        </div>

    </div>
</div>




