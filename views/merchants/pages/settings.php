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
                                <input type="text" class="form-control" name="full_name" value="<?=htmlspecialchars($user->full_name ?? '')?>" required>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label font-14 font-weight-medium">Email</label>
                                <input type="email" class="form-control" name="email" value="<?=htmlspecialchars($user->email ?? '')?>" required>
                                <small class="form-text text-muted">Din email bruges til login og kommunikation</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Telefon</label>
                                <input type="tel" class="form-control" name="phone" value="<?=htmlspecialchars($user->phone ?? '')?>" placeholder="+45 12345678">
                                <small class="form-text text-muted">Inkluder landekode (f.eks. +45)</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Landekode</label>
                                <input type="text" class="form-control" name="phone_country_code" value="<?=htmlspecialchars($user->phone_country_code ?? '')?>" placeholder="DK">
                            </div>
                        </div>

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
                                <input type="text" class="form-control" name="address_street" value="<?=htmlspecialchars($user->address_street ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">By</label>
                                <input type="text" class="form-control" name="address_city" value="<?=htmlspecialchars($user->address_city ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Postnummer</label>
                                <input type="text" class="form-control" name="address_zip" value="<?=htmlspecialchars($user->address_zip ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Region</label>
                                <input type="text" class="form-control" name="address_region" value="<?=htmlspecialchars($user->address_region ?? '')?>">
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Land</label>
                                <input type="text" class="form-control" name="address_country" value="<?=htmlspecialchars($user->address_country ?? '')?>" placeholder="Denmark">
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
                            Du har allerede sat en adgangskode. Du kan logge ind med email og adgangskode i stedet for MitID.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="mdi mdi-alert-outline"></i>
                            Du bruger kun MitID til login. Tilføj en adgangskode for at kunne logge ind med email og adgangskode.
                        </div>
                    <?php endif; ?>

                    <form id="passwordForm">
                        <?php if($hasPassword): ?>
<!--                            <div class="row">-->
<!--                                <div class="col-12 mb-3">-->
<!--                                    <label class="form-label font-14 font-weight-medium">Nuværende Adgangskode</label>-->
<!--                                    <input type="password" class="form-control" name="current_password" required>-->
<!--                                </div>-->
<!--                            </div>-->
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium"><?=$hasPassword ? 'Ny' : ''?> Adgangskode</label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                                <small class="form-text text-muted">Minimum 8 tegn</small>
                            </div>

                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label font-14 font-weight-medium">Bekræft Adgangskode</label>
                                <input type="password" class="form-control" name="password_confirm" required minlength="8">
                            </div>
                        </div>

                        <button type="submit" class="btn-v2 action-btn" id="savePasswordBtn">
                            <i class="mdi mdi-shield-lock"></i>
                            <span><?=$hasPassword ? 'Opdater' : 'Tilføj'?> Adgangskode</span>
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
                                    <span class="action-box">Email & Adgangskode</span>
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

                    <a href="<?=__url(Links::$merchant->support)?>" class="btn-v2 trans-btn w-100">
                        <i class="mdi mdi-face-agent"></i>
                        <span>Kontakt Support</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php scriptStart(); ?>
<script>
    // Profile Form Handler
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveProfileBtn');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        const result = await post('<?=__url(Links::$api->user->updateProfile)?>', data);

        btn.disabled = false;
        btn.innerHTML = originalText;

        if(result.status === 'success') {
            showSuccessNotification('Udført', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showErrorNotification('Der opstod en fejl', result.error.message);
        }
    });

    // Address Form Handler
    document.getElementById('addressForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('saveAddressBtn');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        const result = await post('<?=__url(Links::$api->user->updateAddress)?>', data);

        btn.disabled = false;
        btn.innerHTML = originalText;

        if(result.status === 'success') {
            showSuccessNotification('Udført', result.message);
        } else {
            showErrorNotification('Der opstod en fejl', result.error.message);
        }
    });

    // Password Form Handler
    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('savePasswordBtn');
        const originalText = btn.innerHTML;

        const password = this.querySelector('[name="password"]').value;
        const passwordConfirm = this.querySelector('[name="password_confirm"]').value;

        if(password !== passwordConfirm) {
            showErrorNotification('Fejl', 'Adgangskoder matcher ikke');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i><span>Gemmer...</span>';

        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        const result = await post('<?=__url(Links::$api->user->updatePassword)?>', data);

        btn.disabled = false;
        btn.innerHTML = originalText;

        if(result.status === 'success') {
            showSuccessNotification('Udført', result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showErrorNotification('Der opstod en fejl', result.error.message);
        }
    });

</script>
<?php scriptEnd(); ?>
