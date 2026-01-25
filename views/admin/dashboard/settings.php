<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;

$user = $args->user;
$authLocal = $args->authLocal;
$hasPassword = !empty($authLocal);

$pageTitle = "Indstillinger";

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "settings";
    var originalPhone = <?=json_encode($user->phone ?? '')?>;
    var originalPhoneCountryCode = <?=json_encode($authLocal->phone_country_code ?? $user->phone_country_code ?? '')?>;
</script>

<div class="page-content">

    <div class="flex-col-start mb-4">
        <p class="mb-0 font-30 font-weight-bold">Indstillinger</p>
        <p class="mb-0 font-16 font-weight-medium color-gray">Administrer din profil og kontoindstillinger</p>
    </div>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-12 col-lg-8">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Profil Information</p>
                    </div>

                    <form id="profileForm">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label font-14 font-weight-medium">Fulde Navn</label>
                                <input type="text" class="form-field-v2 w-100" name="full_name" value="<?=htmlspecialchars($user->full_name ?? '')?>" required>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label font-14 font-weight-medium">Email</label>
                                <input type="email" class="form-field-v2 w-100" name="email" value="<?=htmlspecialchars($user->email ?? '')?>" required>
                                <small class="form-text text-muted">Din email bruges til login og kommunikation</small>
                            </div>

                            <div class="col-12 mb-3" id="phone-input-section">
                                <label class="form-label font-14 font-weight-medium">Telefon</label>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                    <select class="form-select-v2 h-45px dropdown-no-arrow border-radius-tr-br-0-5rem"
                                            data-search="true" name="phone_country_code" id="settings_phone_country_code" style="width: 85px;">
                                        <?php foreach ($args->worldCountries as $country): ?>
                                            <option data-sort="<?=$country->countryNameEn?>_<?=$country->countryCode?>_<?=$country->countryNameLocal?>_<?=$country->countryCallingCode?>"
                                                    value="<?=$country->countryCode?>" <?=$country->countryCode === ($authLocal->phone_country_code ?? $user->phone_country_code ?? \features\Settings::$app->default_country) ? 'selected' : ''?>>
                                                <div class="flex-row-center flex-align-center flex-nowrap" style="gap: .25rem;">
                                                    <span><?=$country->flag?></span>
                                                    <span>+<?=$country->countryCallingCode?></span>
                                                </div>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="tel" class="form-field-v2 h-45px" name="phone" id="settings_phone" value="<?=htmlspecialchars($user->phone ?? '')?>" placeholder="12 34 56 78" style="flex: 1;">
                                </div>
                                <small class="form-text text-muted">Vælg landekode og indtast telefonnummer</small>

                                <!-- Send verification code button - shown when phone changes -->
                                <button type="button" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap h-45px mt-2 d-none"
                                        style="gap: .5rem; white-space: nowrap;" id="settings-send-code-button">
                                    <span>Send bekræftelseskode</span>
                                    <span class="ml-2 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                            <span class="sr-only">Loading...</span>
                                        </span>
                                    </span>
                                </button>
                            </div>

                            <!-- Verification code section - hidden by default -->
                            <div class="col-12 mb-3 d-none" id="settings-verification-code-section">
                                <label class="form-label font-14 font-weight-medium">Bekræftelseskode</label>
                                <div class="flex-row-start flex-align-start flex-nowrap w-100" style="gap: 2px;">
                                    <input type="text" class="form-field-v2 h-45px" name="verification_code" id="settings_verification_code"
                                           placeholder="123456" maxlength="6" style="flex: 1;">
                                </div>
                                <button type="button" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap h-45px mt-2"
                                        style="gap: .5rem; white-space: nowrap;" id="settings-verify-code-button">
                                    <span>Bekræft kode</span>
                                    <span class="ml-2 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                            <span class="sr-only">Loading...</span>
                                        </span>
                                    </span>
                                </button>
                                <small class="form-text text-muted">Indtast den 6-cifrede kode sendt til dit telefonnummer</small>

                                <div class="flex-row-start flex-align-center mt-2" style="gap: .5rem;" id="settings-send-code-timer-display">
                                    <i class="mdi mdi-timer-sand color-gray font-16"></i>
                                    <p class="mb-0 font-12 color-gray">Du kan anmode om en ny kode om <span id="settings-timer-countdown" class="font-weight-bold">60</span> sekunder</p>
                                </div>

                                <a href="#" class="font-12 color-blue text-decoration-underline d-none mt-2" id="settings-try-new-number-link" style="cursor: pointer; display: block;">Prøv et andet nummer</a>
                            </div>

                            <!-- Verification success message -->
                            <div class="col-12 mb-3 d-none" id="settings-verification-success">
                                <div class="alert alert-success mb-0">
                                    <i class="mdi mdi-check-circle"></i> Telefonnummer bekræftet! Du kan nu gemme dine ændringer.
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="phone_verified" id="settings_phone_verified" value="0">

                        <button type="submit" class="btn-v2 action-btn" id="saveProfileBtn">
                            <i class="mdi mdi-content-save"></i>
                            <span>Gem Ændringer</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Address Information -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <i class="mdi mdi-map-marker-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Adresse</p>
                    </div>

                    <form id="addressForm">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label font-14 font-weight-medium">Vej/Gade</label>
                                <input type="text" class="form-field-v2 w-100" name="address_street" value="<?=htmlspecialchars($user->address_street ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">By</label>
                                <input type="text" class="form-field-v2 w-100" name="address_city" value="<?=htmlspecialchars($user->address_city ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Postnummer</label>
                                <input type="text" class="form-field-v2 w-100" name="address_zip" value="<?=htmlspecialchars($user->address_zip ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Region</label>
                                <input type="text" class="form-field-v2 w-100" name="address_region" value="<?=htmlspecialchars($user->address_region ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Land</label>
                                <select class="form-select-v2 w-100 h-45px" name="address_country" data-search="true">
                                    <option value="">Vælg land</option>
                                    <?php foreach ($args->worldCountries as $country): ?>
                                        <option value="<?=$country->countryCode?>"
                                                data-sort="<?=$country->countryNameEn?>_<?=$country->countryCode?>_<?=$country->countryNameLocal?>"
                                            <?=strtoupper($user->address_country ?? '') === strtoupper($country->countryCode) ? 'selected' : ''?>>
                                            <?=$country->flag?> <?=$country->countryNameLocal?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-v2 action-btn" id="saveAddressBtn">
                            <i class="mdi mdi-content-save"></i>
                            <span>Gem Adresse</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Password/Local Auth -->
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-4" style="column-gap: .5rem;">
                        <i class="mdi mdi-lock-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Login Adgangskode</p>
                    </div>

                    <?php if($hasPassword): ?>
                        <div class="alert alert-info mb-3">
                            <i class="mdi mdi-information-outline"></i>
                            Du har allerede sat en adgangskode. Du kan logge ind med adgangskode i stedet for MitID.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="mdi mdi-alert-outline"></i>
                            Du bruger kun MitID til login. Tilføj en adgangskode for at kunne logge ind med adgangskode.
                        </div>
                    <?php endif; ?>

                    <!-- Username Section -->
                    <form id="usernameForm" class="mb-4">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label font-14 font-weight-medium">Brugernavn</label>
                                <input type="text" class="form-field-v2 w-100" name="username" value="<?=htmlspecialchars($authLocal->username ?? '')?>" placeholder="valgfrit_brugernavn">
                                <small class="form-text text-muted">Valgfrit. Du kan bruge dette til at logge ind i stedet for email/telefon. Skal være unikt.</small>
                            </div>
                        </div>

                        <button type="submit" class="btn-v2 action-btn" id="saveUsernameBtn">
                            <i class="mdi mdi-account-edit"></i>
                            <span>Gem Brugernavn</span>
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- Password Section -->
                    <form id="passwordForm" class="mb-4">
                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium"><?=$hasPassword ? 'Ny' : ''?> Adgangskode</label>
                                <input type="password" class="form-field-v2 w-100" name="password" required minlength="8">
                                <small class="form-text text-muted">Minimum 8 tegn</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Bekræft Adgangskode</label>
                                <input type="password" class="form-field-v2 w-100" name="password_confirm" required minlength="8">
                            </div>
                        </div>

                        <button type="submit" class="btn-v2 action-btn" id="savePasswordBtn">
                            <i class="mdi mdi-shield-lock"></i>
                            <span><?=$hasPassword ? 'Opdater' : 'Tilføj'?> Adgangskode</span>
                        </button>
                    </form>

                    <hr class="my-4">

                    <!-- 2FA Section -->
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-shield-check-outline font-18 color-blue"></i>
                        <p class="mb-0 font-16 font-weight-bold">To-faktor Godkendelse (2FA)</p>
                    </div>

                    <form id="twoFactorForm">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="flex-row-start flex-align-center" style="column-gap: .75rem;">
                                    <label class="form-switch">
                                        <input type="checkbox" id="twoFactorEnabled" name="two_factor_enabled"
                                            <?=($authLocal && $authLocal->{'2fa'} == 1) ? 'checked' : ''?>>
                                        <i></i>
                                    </label>
                                    <span class="font-14">Aktiver 2FA</span>
                                </div>
                                <small class="form-text text-muted">Beskyt din konto med et ekstra sikkerhedslag</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">2FA Metode</label>
                                <select class="form-select-v2" name="two_factor_method" id="twoFactorMethod">
                                    <option value="SMS" <?=($authLocal && $authLocal->{'2fa_method'} === 'SMS') ? 'selected' : ''?>>SMS</option>
                                    <option value="EMAIL" <?=($authLocal && $authLocal->{'2fa_method'} === 'EMAIL') ? 'selected' : ''?> disabled>Email (kommer snart)</option>
                                </select>
                                <small class="form-text text-muted">Vælg hvordan du vil modtage din bekræftelseskode</small>
                            </div>
                        </div>

                        <button type="submit" class="btn-v2 action-btn" id="saveTwoFactorBtn" <?=!$authLocal ? 'disabled' : ''?>>
                            <i class="mdi mdi-shield-check"></i>
                            <span>Gem 2FA Indstillinger</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Info Sidebar -->
        <div class="col-12 col-lg-4">
            <div class="card border-radius-10px mb-4">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-account-circle font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Konto Oversigt</p>
                    </div>

                    <div class="flex-col-start" style="row-gap: 1rem;">
                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray">Konto ID</p>
                            <p class="mb-0 font-14 font-monospace"><?=substr($user->uid, 0, 12)?></p>
                        </div>

                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray">Medlem siden</p>
                            <p class="mb-0 font-14"><?=date('d/m/Y', strtotime($user->created_at))?></p>
                        </div>

                        <div class="flex-col-start">
                            <p class="mb-1 font-13 color-gray">Login metoder</p>
                            <div class="flex-col-start" style="row-gap: .25rem;">
                                <span class="success-box">MitID</span>
                                <?php if($hasPassword): ?>
                                    <span class="action-box">Adgangskode</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                        <i class="mdi mdi-help-circle-outline font-18 color-blue"></i>
                        <p class="mb-0 font-20 font-weight-bold">Hjælp</p>
                    </div>

                    <p class="mb-3 font-14 color-gray">Har du brug for hjælp med dine indstillinger?</p>

                    <a href="<?=__url(Links::$admin->support)?>" class="btn-v2 trans-btn w-100">
                        <i class="mdi mdi-face-agent"></i>
                        <span>Kontakt Support</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
