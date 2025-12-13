<?php
/**
 * @var object $args
 * @var object $user
 * @var array $worldCountries
 */

use classes\enumerations\Links;
use features\Settings;

$user = $args->user;
$worldCountries = $args->worldCountries;

?>




<div class="page-content mt-3">
    <div class="page-inner-content auth">

        <div class="flex-col-start" style="row-gap: 1.25rem;">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <form action="<?=Links::$api->auth->consumerUpdateProfile?>" class="flex-col-start" style="row-gap: 1.5rem;">
                        <div class="flex-col-start flex-align-center" style="row-gap: .25rem;">
                            <div class="flex-row-center flex-align-center square-60 bg-wrapper-hover border-radius-50 " >
                                <i class="font-35 color-design-blue mdi mdi-account-check-outline"></i>
                            </div>
                            <p class="mb-0 font-22 font-weight-700">Velkommen<?=!isEmpty($user->full_name) ? ', ' . htmlspecialchars($user->full_name) : ''?>!</p>
                            <p class="mb-0 font-14 color-gray font-weight-medium">Udfyld dine kontaktoplysninger for at fortsætte</p>
                        </div>

                        <div class="flex-row-start flex-align-start w-100" style="gap: .5rem; padding: .75rem; background: #e8f5e9; border-radius: 8px; border-left: 3px solid #4caf50;">
                            <i class="mdi mdi-check-circle-outline color-green font-18" style="margin-top: 2px;"></i>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <p class="mb-0 font-13 font-weight-bold color-green">Din identitet er verificeret</p>
                                <p class="mb-0 font-12 color-gray">
                                    Du er nu logget ind med MitID. For at kunne kontakte dig, beder vi dig om at udfylde dine kontaktoplysninger.
                                </p>
                            </div>
                        </div>

                        <?php if(isEmpty($user->full_name)): ?>
                        <div class="flex-col-start w-100" style="row-gap: .25rem;">
                            <p class="mb-0 font-14 font-weight-bold">Fulde navn <span class="color-red">*</span></p>
                            <input type="text" class="form-field-v2 h-45px" name="full_name" id="full_name"
                                   placeholder="Indtast dit fulde navn" required maxlength="100">
                        </div>
                        <?php endif; ?>

                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Telefonnummer <span class="color-red">*</span></p>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                    <select class="form-select-v2 h-45px w-70px dropdown-no-arrow border-radius-tr-br-0-5rem "
                                            data-search="true" name="phone_country_code" id="phone_country_code">
                                        <?php foreach ($worldCountries as $country): ?>
                                            <option data-sort="<?=$country->countryNameEn?>_<?=$country->countryCode?>_<?=$country->countryNameLocal?>_<?=$country->countryCallingCode?>"
                                                    value="<?=$country->countryCode?>" <?=$country->countryCode === Settings::$app->default_country ? 'selected' : ''?>>
                                                <div class="flex-row-center flex-align-center flex-nowrap" style="gap: .25rem;">
                                                    <span class=""><?=$country->flag?></span>
                                                    <span class="">+<?=$country->countryCallingCode?></span>
                                                </div>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="flex-1-current form-field-v2 h-45px" name="phone" id="phone" placeholder="12 34 56 78"
                                           style="border-radius: 0;" required value="<?=htmlspecialchars($user->phone ?? '')?>">
                                    <button type="button" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap h-45px"
                                            style="gap: .5rem; white-space: nowrap; border-radius: 0 10px 10px 0;" name="send-code-button" id="send-code-button">
                                        <span>Send kode</span>
                                        <span class="ml-2 flex-align-center flex-row-start button-disabled-spinner">
                                            <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                              <span class="sr-only">Loading...</span>
                                            </span>
                                        </span>
                                    </button>
                                </div>
                                <p class="mb-0 font-12 color-gray">Bruges til kontoadgendannelse og vigtige beskeder</p>
                            </div>

                            <div class="flex-col-start w-100 d-none" style="row-gap: .25rem;" id="verification-code-section">
                                <p class="mb-0 font-14 font-weight-bold">Verifikationskode <span class="color-red">*</span></p>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100">
                                    <input type="text" class="flex-1-current form-field-v2 h-45px" name="verification_code"
                                           style="border-radius: 10px 0 0 10px;" id="verification_code" placeholder="123456" maxlength="6">
                                    <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap h-45px"
                                            style="gap: .5rem; white-space: nowrap; border-radius: 0 10px 10px 0;" name="verify-code-button" id="verify-code-button">
                                        <span>Verificer</span>
                                        <span class="ml-2 flex-align-center flex-row-start button-disabled-spinner">
                                            <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                              <span class="sr-only">Loading...</span>
                                            </span>
                                        </span>
                                    </button>
                                </div>
                                <p class="mb-0 font-12 color-gray">Indtast den 6-cifrede kode sendt til dit telefonnummer</p>
                            </div>

                            <div class="alert alert-success d-none" id="verification-success">
                                <i class="mdi mdi-check-circle"></i> Telefonnummer verificeret!
                            </div>

                            <div class="flex-col-start w-100" style="row-gap: .25rem;">
                                <p class="mb-0 font-14 font-weight-bold">Email</p>
                                <input type="email" class="w-100 form-field-v2" name="email" id="email" placeholder="din@email.dk" value="<?=htmlspecialchars($user->email ?? '')?>">
                                <p class="mb-0 font-12 color-gray">Valgfrit - bruges til vigtige meddelelser og kvitteringer</p>
                            </div>
                        </div>


                        <div class="flex-col-start w-100" style="row-gap: .75rem;">
                            <button type="button" class="btn-v2 green-btn flex-row-center flex-align-center flex-nowrap" style="gap: .5rem;" name="complete-profile-button" id="complete-profile-button">
                                <span>Fortsæt til dashboard</span>

                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                      <span class="sr-only">Loading...</span>
                                    </span>
                                </span>
                            </button>

                            <p class="mb-0 font-12 color-gray text-center">
                                Du kan altid ændre disse oplysninger senere i dine kontoindstillinger
                            </p>
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
                                <i class="mdi mdi-shield-check-outline color-green font-16"></i>
                                <span>MitID verificeret</span>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

    </div>
</div>




