<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;
use classes\Methods;

$terminal = $args->terminal;
$worldCountries = $args->worldCountries ?? [];
$isUserLoggedIn = $args->isUserLoggedIn ?? false;
$isUserOidcVerified = $args->isUserOidcVerified ?? false;

$pageTitle = "{$terminal->location->name} - Start Køb";

$locationHandler = Methods::locations();
$location = $locationHandler->get($terminal->location->uid);
$address = $locationHandler->locationAddress($location);
$addressString = Methods::misc()::extractCompanyAddressString($address, false, false);
$contactEmail = $locationHandler->contactEmail($location);
$contactPhone = $locationHandler->contactPhone($location);

// Determine which auth options to show:
// - If logged in but NOT OIDC verified → only MitID
// - If NOT logged in → both MitID and local auth
$showLocalAuth = !$isUserLoggedIn;
$showMitId = true; // Always show MitID
?>


<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>


<div class="page-content mt-5">
    <div class="page-inner-content">
        <div class="stepper-progress">
            <div class="stepper-item stepper-item--active">
                <div class="stepper-circle">1</div>
                <div class="stepper-label">Login</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">2</div>
                <div class="stepper-label">Info</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">3</div>
                <div class="stepper-label">Vælg og Bekræft</div>
            </div>

            <div class="stepper-line"></div>

            <div class="stepper-item">
                <div class="stepper-circle">4</div>
                <div class="stepper-label">Betal</div>
            </div>
        </div>

        <div class="flex-col-start flex-align-center mt-5" style="row-gap: .75rem;">
            <p class="design-box mb-0 px-2">
                <i class="mdi mdi-store"></i>
                <span class="font-weight-bold">Du Handler hos</span>
            </p>

            <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                <p class="mb-0 font-25 font-weight-bold"><?=$terminal->location->name?></p>
                <p class="mb-0 font-14 font-weight-medium color-gray"><?=$args->page->caption?></p>
            </div>




            <div class="card border-radius-10px w-100">
                <div class="w-100 h-200px overflow-hidden">
                    <div
                            class="w-100 h-100 overflow-hidden bg-cover"
                            style="
                                border-radius: 10px 10px 0 0;
                                aspect-ratio: 16/9;
                                background-image: url('<?=resolveImportUrl($args->page->hero_image)?>');
                            "
                    ></div>
                </div>

                <div class="py-3 px-4 w-100 flex-col-start" style="row-gap: .5rem;">
                    <div class="flex-col-start border-bottom-card pb-3" style="row-gap: .5rem;">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                            <i class="mdi mdi-email-outline color-design-blue font-16"></i>
                            <p class="mb-0 font-14"><?=$contactEmail?></p>
                        </div>
                        <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                            <i class="mdi mdi-phone-outline color-design-blue font-16"></i>
                            <p class="mb-0 font-14"><?=$contactPhone?></p>
                        </div>
                        <?php if(!isEmpty($addressString)): ?>
                            <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem">
                                <i class="mdi mdi-map-marker-outline color-design-blue font-16"></i>
                                <p class="mb-0 font-14"><?=$addressString?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-col-start pt-3" style="row-gap: 1.5rem;">
                        <div class="action-mute-info-box">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                                <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-shield-outline"></i></div>
                                <p class="mb-0 info-title color-dark">For at fortsætte skal du:</p>
                            </div>
                            <div class="info-content mt-2">
                                <ul class="pl-1 line-spacing">
                                    <li>Verificere din identitet med MitID</li>
                                    <li>Godkende en hurtig kreditvurdering (10 sek)</li>
                                    <li>Vælge din betalingsplan</li>
                                </ul>
                            </div>
                        </div>


                        <?php if(!isEmpty($args->authError)): ?>
                        <div class="alert alert-danger w-100 flex-row-start flex-align-center" style="gap: .5rem;">
                            <i class="mdi mdi-alert-circle-outline font-20"></i>
                            <span><?=htmlspecialchars($args->authError)?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Login Options Card -->
                        <div class="card border-radius-10px w-100">
                            <div class="card-body flex-col-start" style="row-gap: 1.5rem;">
                                <!-- MitID Login Option -->
                                <div class="flex-col-start flex-align-center w-100" style="gap: .5rem;">
                                    <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50">
                                        <i class="font-35 color-design-blue mdi mdi-shield-outline"></i>
                                    </div>

                                    <p class="mb-0 font-20 font-weight-bold text-center">Log ind eller opret konto</p>
                                    <p class="mb-0 font-13 color-gray font-weight-medium text-center">Vi bruger MitID for at verificere din identitet sikkert</p>

                                    <button data-id="<?=$args->oidcSessionId?>"
                                            class="mt-2 btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap oidc-auth w-100" style="gap: .55rem;">
                                        <i class="mdi mdi-shield-outline font-18"></i>
                                        <span class="font-16">Fortsæt med MitID</span>
                                    </button>
                                </div>

                                <?php if($showLocalAuth): ?>
                                <!-- Divider -->
                                <div class="alternative-box color-gray">
                                    <span class="alternative-line"></span>
                                    <span class="alternative-text text-uppercase">Eller</span>
                                    <span class="alternative-line"></span>
                                </div>

                                <!-- Local Auth Login Option -->
                                <form action="<?=Links::$api->auth->consumerLogin?>" class="flex-col-start w-100" style="row-gap: 1rem;" data-reload-on-success="true">
                                    <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                                        <div class="flex-row-center flex-align-center square-50 bg-wrapper-hover border-radius-50">
                                            <i class="font-28 color-design-blue mdi mdi-lock-outline"></i>
                                        </div>
                                        <p class="mb-0 font-18 font-weight-700 text-center">Allerede MitID verificeret?</p>
                                        <p class="mb-0 font-13 color-gray font-weight-medium text-center">Log ind med adgangskode</p>
                                    </div>

                                    <!-- Login credentials section -->
                                    <div class="flex-col-start w-100 login-credentials-section" style="row-gap: .75rem;">
                                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                            <p class="mb-0 font-14 font-weight-bold">Brugernavn, email eller telefonnummer</p>
                                            <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                                <div class="login-country-code-container d-none">
                                                    <select class="form-select-v2 h-45px w-70px dropdown-no-arrow border-radius-tr-br-0-5rem"
                                                            data-search="true" name="phone_country_code" id="login_phone_country_code">
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

                                    <div class="flex-col-start w-100" style="row-gap: .5rem;">
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
                                    </div>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

    </div>
</div>
