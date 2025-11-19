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
                    <form action="<?=Links::$api->auth->consumerLogin?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <a href="<?=__url(Links::$consumer->public->home)?>" class="transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark" style="gap: .5rem;">
                            <i class="mdi mdi-arrow-left"></i>
                            <span>Tilbage til <?=BRAND_NAME?></span>
                        </a>

                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50 " >
                                <i class="font-35 color-design-blue mdi mdi-lock-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Login - <?=BRAND_NAME?> Kunde </p>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Få adgang til din konto</p>
                        </div>


                        <a href="javascript:void(0);" class="mt-3  btn-v2 design-action-btn-lg flex-row-center flex-align-center flex-nowrap" style="gap: .55rem;">
                            <i class="mdi mdi-shield-outline font-16"></i>
                            <span class="font-16">Log ind med MitId</span>
                        </a>

                        <div class="alternative-box color-gray">
                            <span class="alternative-line"></span>
                            <span class="alternative-text text-uppercase">Eller</span>
                            <span class="alternative-line"></span>
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Brugernavn, email eller telefonnummer</p>
                                <input type="text" class="w-100 form-field-v2" name="username" id="username" placeholder="kontakt@dinbutik.dk">
                            </div>
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Adgangskode</p>

                                <div class="position-relative w-100">
                                    <input type="password" class="w-100 form-field-v2 togglePwdVisibilityField" name="password" id="password" placeholder="******">
                                </div>
                            </div>
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-row-end">
                                <a href="<?=__url(Links::$consumer->public->recovery)?>" class="color-blue hover-underline font-13">Glemt adgangskode?</a>
                            </div>
                            <button class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="login-button" id="login-button">
                                <span>Log ind</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>
                            <div class="flex-row-center flex-align-center flex-nowrap font-weight-medium" style="gap: .25rem;">
                                <span class="color-gray font-13">Har du ikke en konto?</span>
                                <a href="<?=__url(Links::$consumer->public->signup)?>" class="color-blue font-13 hover-underline">Opret konto her</a>
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
                <a href="<?=__url(Links::$policies->consumer->termsOfUse)?>" class="color-gray font-12 hover-underline hover-color-blue">Vilkår & Betingelser</a>
                &bullet;
                <a href="<?=__url(Links::$policies->consumer->privacy)?>" class="color-gray font-12 hover-underline hover-color-blue">Privatlivspolitik</a>
                &bullet;
                <a href="<?=__url(Links::$support->public)?>" class="color-gray font-12 hover-underline hover-color-blue">Support</a>
            </div>
        </div>

    </div>
</div>




