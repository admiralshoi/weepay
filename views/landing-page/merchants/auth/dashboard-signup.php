<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;
?>




<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <form action="<?=Links::$api->auth->merchantSignup?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <a href="<?=__url(Links::$merchant->public->home)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til <?=BRAND_NAME?></span>
                        </a>

                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50 " >
                                <i class="font-35 color-design-blue mdi mdi-account-plus-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Tilmeld dig - <?=BRAND_NAME?> Forhandler </p>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Opret din konto og kom i gang</p>
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Fulde navn <span class="color-red">*</span></p>
                                <input type="text" class="w-100 form-field-v2" name="full_name" id="full_name" placeholder="Anders Andersen" required>
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Email <span class="color-red">*</span></p>
                                <input type="email" class="w-100 form-field-v2" name="email" id="email" placeholder="kontakt@dinbutik.dk" required>
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Telefonnummer</p>
                                <input type="tel" class="w-100 form-field-v2" name="phone" id="phone" placeholder="+45 12 34 56 78">
                                <p class="mb-0 font-12 color-gray">Valgfrit - bruges til kontoadgendannelse</p>
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Adgangskode <span class="color-red">*</span></p>
                                <div class="position-relative w-100">
                                    <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="password" id="password" placeholder="Mindst 8 tegn" required>
                                </div>
                                <p class="mb-0 font-12 color-gray">Minimum 8 tegn</p>
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Bekræft adgangskode <span class="color-red">*</span></p>
                                <div class="position-relative w-100">
                                    <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="password_confirm" id="password_confirm" placeholder="Gentag adgangskode" required>
                                </div>
                            </div>
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-row-start flex-align-start" style="gap: .5rem;">
                                <input type="checkbox" name="accept_terms" id="accept_terms" required style="margin-top: 2px;">
                                <label for="accept_terms" class="font-13 color-gray cursor-pointer">
                                    Jeg accepterer
                                    <a href="<?=__url(Links::$policies->merchant->termsOfUse)?>" target="_blank" class="color-blue hover-underline">Vilkår & Betingelser</a>
                                    og
                                    <a href="<?=__url(Links::$policies->merchant->privacy)?>" target="_blank" class="color-blue hover-underline">Privatlivspolitik</a>
                                </label>
                            </div>

                            <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="signup-button" id="signup-button">
                                <span>Opret konto</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>

                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium" style="gap: .25rem;">
                                <span class="color-gray font-13">Har du allerede en konto?</span>
                                <a href="<?=__url(Links::$app->auth->merchantLogin)?>" class="color-blue font-13 hover-underline">Log ind her</a>
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




