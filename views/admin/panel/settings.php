<?php
/**
 * Admin Panel - App Settings
 * Manage global application settings via AppMeta
 * @var object $args
 */

use classes\enumerations\Links;
use classes\lang\Translate;
use classes\organisations\OrganisationRolePermissions;
use classes\organisations\LocationRolePermissions;

$pageTitle = "App Indstillinger";
$settings = $args->settings ?? (object)[];

// Convert settings object to array for easier access
$settingsArray = (array)$settings;

// Helper to get setting value
function getSetting($arr, $key, $default = '') {
    return isset($arr[$key]) ? $arr[$key] : $default;
}

// Helper to ensure value is array (defined early for use below)
function ensureArray($value) {
    if (is_string($value)) {
        return json_decode($value, true) ?? [];
    }
    if (is_object($value)) {
        return (array)$value;
    }
    if (is_array($value)) {
        return $value;
    }
    return [];
}

// Get currencies and payment providers from settings for dropdowns
$currencies = ensureArray(getSetting($settingsArray, 'currencies', []));
$paymentProviders = ensureArray(getSetting($settingsArray, 'payment_providers', []));

// Get countries data
$enabledCountries = $args->enabledCountries ?? [];
$worldCountries = $args->worldCountries ?? [];

// Get currencies library
$currenciesLibrary = $args->currenciesLibrary ?? [];

// Simple settings that can be edited directly (excluding default_country which needs special handling)
$simpleSettings = [
    'default_currency' => ['label' => 'Standard valuta', 'type' => 'select', 'options' => $currencies],
    'default_payment_provider' => ['label' => 'Standard betalingsudbyder', 'type' => 'select', 'options' => $paymentProviders],
    'oidc_session_lifetime' => ['label' => 'OIDC session levetid (sekunder)', 'type' => 'number', 'min' => 60, 'max' => 3600],
];

// Get active payment providers
$activePaymentProviders = ensureArray(getSetting($settingsArray, 'active_payment_providers', []));

// Get roles
$organisationRoles = ensureArray(getSetting($settingsArray, 'organisation_roles', []));
$locationRoles = ensureArray(getSetting($settingsArray, 'location_roles', []));

// Get fixed roles that cannot be removed
$fixedOrgRoles = OrganisationRolePermissions::getFixedRoles();
$fixedLocationRoles = LocationRolePermissions::getFixedRoles();
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "settings";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">App Indstillinger</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">App Indstillinger</h1>
                    <p class="mb-0 font-14 color-gray">Administrer globale systemindstillinger</p>
                </div>
            </div>

            <!-- General Settings -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center mb-4" style="gap: .75rem;">
                        <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-cog-outline color-white font-20"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-16 font-weight-bold">Generelle Indstillinger</p>
                            <p class="mb-0 font-12 color-gray">Grundlæggende systemkonfiguration</p>
                        </div>
                    </div>

                    <div class="row" style="row-gap: 1.5rem;">
                        <!-- Default Country (special handling with searchable select) -->
                        <div class="col-12 col-md-6">
                            <div class="flex-col-start">
                                <label class="font-13 color-dark mb-2">Standard land</label>
                                <select class="form-select-v2" data-search="true" id="setting_default_country" onchange="saveSingleSetting('default_country', this.value)">
                                    <?php foreach ($enabledCountries as $country): ?>
                                    <option value="<?=$country->code?>" <?=getSetting($settingsArray, 'default_country') === $country->code ? 'selected' : ''?>><?=$country->name?> (<?=$country->code?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php foreach ($simpleSettings as $key => $config): ?>
                        <div class="col-12 col-md-6">
                            <div class="flex-col-start">
                                <label class="font-13 color-dark mb-2"><?=$config['label']?></label>
                                <?php if ($config['type'] === 'select'): ?>
                                <select class="form-select-v2" id="setting_<?=$key?>" onchange="saveSingleSetting('<?=$key?>', this.value)">
                                    <?php foreach ($config['options'] as $option): ?>
                                    <option value="<?=$option?>" <?=getSetting($settingsArray, $key) === $option ? 'selected' : ''?>><?=$option?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php elseif ($config['type'] === 'number'): ?>
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <input type="number"
                                           class="form-field-v2"
                                           id="setting_<?=$key?>"
                                           value="<?=getSetting($settingsArray, $key)?>"
                                           min="<?=$config['min'] ?? 0?>"
                                           max="<?=$config['max'] ?? 999999?>"
                                           style="max-width: 150px;">
                                    <button class="btn-v2 action-btn" onclick="saveSingleSetting('<?=$key?>', document.getElementById('setting_<?=$key?>').value)">
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                    <input type="text"
                                           class="form-field-v2"
                                           id="setting_<?=$key?>"
                                           value="<?=htmlspecialchars(getSetting($settingsArray, $key))?>">
                                    <button class="btn-v2 action-btn" onclick="saveSingleSetting('<?=$key?>', document.getElementById('setting_<?=$key?>').value)">
                                        <i class="mdi mdi-check"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Countries -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-4">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-cyan border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-file-document-outline color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold">Understøttede Lande</p>
                                <p class="mb-0 font-12 color-gray">Lande der kan vælges i systemet</p>
                            </div>
                        </div>
                        <button class="btn-v2 action-btn" onclick="openAddCountryModal()">
                            <i class="mdi mdi-plus mr-1"></i> Tilføj
                        </button>
                    </div>

                    <div class="flex-row-start flex-wrap" style="gap: .5rem;" id="countries-list">
                        <?php foreach ($enabledCountries as $country): ?>
                        <span class="tag-chip" data-key="countries" data-value="<?=htmlspecialchars($country->code)?>">
                            <?=htmlspecialchars($country->name)?> (<?=htmlspecialchars($country->code)?>)
                            <i class="mdi mdi-close ml-1 cursor-pointer" onclick="removeCountry('<?=htmlspecialchars($country->code)?>')"></i>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Currencies -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center mb-4" style="gap: .75rem;">
                        <div class="square-40 bg-green border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-currency-usd color-white font-20"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-16 font-weight-bold">Understøttede Valutaer</p>
                            <p class="mb-0 font-12 color-gray">Vælg hvilke valutaer der kan bruges i systemet</p>
                        </div>
                    </div>

                    <div class="flex-col-start w-100" style="gap: 1rem;">
                        <select class="form-select-v2 w-100" data-search="true" multiple id="currenciesSelect" onchange="saveCurrencies()">
                            <?php foreach ($currenciesLibrary as $code => $currency): ?>
                            <option value="<?=htmlspecialchars($code)?>"
                                    data-sort="<?=htmlspecialchars($code)?>_<?=htmlspecialchars($currency->name)?>"
                                    <?=in_array($code, $currencies) ? 'selected' : ''?>>
                                <?=htmlspecialchars($code)?> - <?=htmlspecialchars($currency->name)?> (<?=htmlspecialchars($currency->symbol)?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payment Providers -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-4">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-purple border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-credit-card-clock-outline color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold">Betalingsudbydere</p>
                                <p class="mb-0 font-12 color-gray">Konfigurer hvilke betalingsudbydere der er aktive</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex-col-start" style="gap: 1rem;">
                        <?php
                        foreach ($paymentProviders as $provider):
                            $isActive = in_array($provider, $activePaymentProviders);
                        ?>
                        <div class="flex-row-between flex-align-center p-3 bg-light-gray border-radius-8px">
                            <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                                <div class="square-40 <?=$isActive ? 'bg-green' : 'bg-gray'?> border-radius-8px flex-row-center-center">
                                    <i class="mdi mdi-credit-card color-white font-18"></i>
                                </div>
                                <div class="flex-col-start">
                                    <p class="mb-0 font-14 font-weight-bold"><?=ucfirst($provider)?></p>
                                    <p class="mb-0 font-12 color-gray"><?=$isActive ? 'Aktiv' : 'Inaktiv'?></p>
                                </div>
                            </div>
                            <label class="toggle-switch mb-0">
                                <input type="checkbox"
                                       data-provider="<?=$provider?>"
                                       <?=$isActive ? 'checked' : ''?>
                                       onclick="return togglePaymentProvider('<?=$provider?>', this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Organisation Roles -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-4">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-pee-yellow border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-account-group-outline color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold">Organisationsroller</p>
                                <p class="mb-0 font-12 color-gray">Roller der kan tildeles til organisationsmedlemmer</p>
                            </div>
                        </div>
                        <button class="btn-v2 action-btn" onclick="openAddItemModal('organisation_roles', 'Tilføj rolle', 'Rollenavn (f.eks. manager)')">
                            <i class="mdi mdi-plus mr-1"></i> Tilføj
                        </button>
                    </div>

                    <div class="flex-row-start flex-wrap" style="gap: .5rem;" id="organisation_roles-list">
                        <?php foreach ($organisationRoles as $role):
                            $isFixed = in_array($role, $fixedOrgRoles);
                        ?>
                        <span class="tag-chip <?=$isFixed ? 'tag-chip-fixed' : ''?>" data-key="organisation_roles" data-value="<?=htmlspecialchars($role)?>" <?=$isFixed ? 'title="Systemrolle - kan ikke fjernes"' : ''?>>
                            <?php if($isFixed): ?><i class="mdi mdi-lock-outline font-10 mr-1"></i><?php endif; ?>
                            <?=htmlspecialchars(ucfirst(str_replace('_', ' ', $role)))?>
                            <?php if(!$isFixed): ?>
                            <i class="mdi mdi-close ml-1 cursor-pointer" onclick="removeItem('organisation_roles', '<?=htmlspecialchars($role)?>')"></i>
                            <?php endif; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Location Roles -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-4">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-cyan border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-shield-account-outline color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold">Lokationsroller</p>
                                <p class="mb-0 font-12 color-gray">Roller der kan tildeles til lokationsmedlemmer</p>
                            </div>
                        </div>
                        <button class="btn-v2 action-btn" onclick="openAddItemModal('location_roles', 'Tilføj rolle', 'Rollenavn (f.eks. cashier)')">
                            <i class="mdi mdi-plus mr-1"></i> Tilføj
                        </button>
                    </div>

                    <div class="flex-row-start flex-wrap" style="gap: .5rem;" id="location_roles-list">
                        <?php foreach ($locationRoles as $role):
                            $isFixed = in_array($role, $fixedLocationRoles);
                        ?>
                        <span class="tag-chip <?=$isFixed ? 'tag-chip-fixed' : ''?>" data-key="location_roles" data-value="<?=htmlspecialchars($role)?>" <?=$isFixed ? 'title="Systemrolle - kan ikke fjernes"' : ''?>>
                            <?php if($isFixed): ?><i class="mdi mdi-lock-outline font-10 mr-1"></i><?php endif; ?>
                            <?=htmlspecialchars(ucfirst(str_replace('_', ' ', $role)))?>
                            <?php if(!$isFixed): ?>
                            <i class="mdi mdi-close ml-1 cursor-pointer" onclick="removeItem('location_roles', '<?=htmlspecialchars($role)?>')"></i>
                            <?php endif; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links to Related Pages -->
            <div class="card border-radius-10px bg-lightest-blue">
                <div class="card-body">
                    <div class="flex-row-start" style="gap: .75rem;">
                        <i class="mdi mdi-information-outline font-20 color-blue"></i>
                        <div class="flex-col-start">
                            <p class="mb-0 font-14 font-weight-medium color-dark">Flere indstillinger</p>
                            <p class="mb-0 font-13 color-gray mt-1">
                                For at konfigurere gebyrer, betalingsplaner og BNPL grænser, besøg de dedikerede sider:
                            </p>
                            <div class="flex-row-start flex-wrap mt-2" style="gap: .5rem;">
                                <a href="<?=__url(Links::$admin->panelFees)?>" class="btn-v2 trans-btn font-12">
                                    <i class="mdi mdi-cash-multiple mr-1"></i> Gebyrer
                                </a>
                                <a href="<?=__url(Links::$admin->panelPaymentPlans)?>" class="btn-v2 trans-btn font-12">
                                    <i class="mdi mdi-credit-card-settings-outline mr-1"></i> Betalingsplaner
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold" id="addItemModalTitle">Tilføj</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="addItemKey">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1" id="addItemInputLabel">Værdi</label>
                    <input type="text" class="form-field-v2" id="addItemValue" placeholder="">
                </div>
                <div class="flex-row-start flex-align-center mt-3 p-2 bg-lightest-blue border-radius-8px" id="addItemRoleHint" style="display: none;">
                    <i class="mdi mdi-information-outline font-14 color-blue mr-2"></i>
                    <p class="mb-0 font-11 color-dark">Bogstaver (a-z, æ, ø, å) og mellemrum er tilladt.</p>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveNewItem()">
                    <i class="mdi mdi-plus mr-1"></i> Tilføj
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Country Modal -->
<div class="modal fade" id="addCountryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Tilføj land</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start">
                    <label class="font-12 color-gray mb-1">Vælg land</label>
                    <select class="form-select-v2" data-search="true" id="addCountrySelect">
                        <option value="">Vælg et land...</option>
                        <?php
                        // Get codes of already enabled countries
                        $enabledCodes = array_map(function($c) { return $c->code; }, toArray($enabledCountries));
                        foreach ($worldCountries as $country):
                            if (in_array($country->countryCode, $enabledCodes)) continue;
                        ?>
                        <option value="<?=htmlspecialchars($country->countryCode)?>"
                                data-name="<?=htmlspecialchars($country->countryNameEn)?>"
                                data-sort="<?=htmlspecialchars($country->countryNameEn)?>_<?=htmlspecialchars($country->countryCode)?>_<?=htmlspecialchars($country->countryNameLocal ?? '')?>">
                            <?=htmlspecialchars($country->flag ?? '')?> <?=htmlspecialchars($country->countryNameEn)?> (<?=htmlspecialchars($country->countryCode)?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveNewCountry()">
                    <i class="mdi mdi-plus mr-1"></i> Tilføj
                </button>
            </div>
        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    var panelSettingsData = {
        currencies: <?=json_encode($currencies)?>,
        organisation_roles: <?=json_encode($organisationRoles)?>,
        location_roles: <?=json_encode($locationRoles)?>,
        active_payment_providers: <?=json_encode($activePaymentProviders)?>
    };
    var fixedRoles = {
        organisation_roles: <?=json_encode($fixedOrgRoles)?>,
        location_roles: <?=json_encode($fixedLocationRoles)?>
    };
    var panelSettingsApiUrl = '<?=__url(Links::$api->admin->panel->updateSetting)?>';

    $(document).ready(function() {
        initPanelSettings(panelSettingsData, panelSettingsApiUrl);
    });
</script>
<?php scriptEnd(); ?>
