<?php
/**
 * @var object $args
 */

use classes\enumerations\Links;
use classes\Methods;
use features\Settings;
use classes\lang\Translate;

$pageTitle = ucfirst(Translate::word("Organisation"));
if(!isEmpty(Settings::$organisation?->organisation)) $pageTitle .= " - " . Settings::$organisation->organisation->name;

$organisation = Settings::$organisation?->organisation;
$merchantActionRequired = empty($organisation?->merchant_prid);
$connectedAccount = Methods::vivaConnectedAccounts()->myConnection();



?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "organisation";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
</script>


<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::organisationSelect($args->memberRows, $organisation?->uid);?>
        <div class="flex-row-end">
            <a href="<?=__url(Links::$merchant->organisation->add)?>"
               class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap color-white" style="gap: .5rem;">
                <i class="mdi mdi-plus"></i>
                <span>Tilføj ny <?=Translate::word("organisation")?></span>
            </a>
        </div>
    </div>


    <?php  if(!isEmpty($organisation)): ?>

    <?php if($args->setupRequirements->has_incomplete): ?>
    <!-- Setup Requirements Notice -->
    <div class="danger-info-box px-4 py-3 mb-4">
        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .75rem">
            <div class="square-40 flex-row-center flex-align-center bg-danger-bread border-radius-50">
                <i class="font-20 mdi mdi-alert-outline color-white"></i>
            </div>
            <div class="flex-col-start flex-1">
                <p class="mb-2 font-18 font-weight-bold color-dark">Handlinger påkrævet</p>
                <p class="mb-2 font-14 color-gray">For at kunne modtage betalinger skal du færdiggøre følgende opsætning:</p>

                <div class="flex-col-start mt-2" style="row-gap: .75rem;">
                    <!-- Viva Wallet -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->viva_wallet->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Viva Wallet tilsluttet</span>
                        <?php elseif($args->setupRequirements->viva_wallet->status === 'in_progress'): ?>
                            <i class="mdi mdi-clock-outline color-warning font-18"></i>
                            <span class="font-14 font-weight-medium">Viva Wallet afventer godkendelse</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <span class="font-14 font-weight-medium">Tilslut Viva Wallet nedenfor</span>
                        <?php endif; ?>
                    </div>

                    <!-- Location -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->locations->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Lokation oprettet</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->locations->main)?>" class="font-14 font-weight-medium color-design-blue">Opret lokation</a>
                        <?php endif; ?>
                    </div>

                    <!-- Terminal -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->terminals->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Terminal oprettet</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->terminals->main)?>" class="font-14 font-weight-medium color-design-blue">Opret terminal</a>
                        <?php endif; ?>
                    </div>

                    <!-- Published Page -->
                    <div class="flex-row-start flex-align-center" style="column-gap: .5rem;">
                        <?php if($args->setupRequirements->published_page->completed): ?>
                            <i class="mdi mdi-check-circle color-success-text font-18"></i>
                            <span class="font-14 color-gray">Lokationsside publiceret</span>
                        <?php else: ?>
                            <i class="mdi mdi-close-circle color-danger-bread font-18"></i>
                            <a href="<?=__url(Links::$merchant->locations->main)?>" class="font-14 font-weight-medium color-design-blue">Publicer lokationsside</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold"><?=ucfirst(Translate::word("Organisation"))?></p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Administrer din <?=Translate::word("organisation")?>, branding og betalingsintegration</p>
        </div>
    </div>



    <div class="row flex-align-stretch rg-15 mt-4">
        <div class="col-12 col-md-6 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center g-1">
                        <div class="flex-row-start-center flex-nowrap g-075">
                            <i class="fa-regular fa-building font-18"></i>
                            <p class="font-22 font-weight-bold">Oplysninger</p>
                        </div>
                        <div  class="flex-row-end">
                            <button class="btn-v2 mute-btn font-13 font-weight-medium flex-row-center-center cg-075"
                                    onclick="editOrganisationDetails()" name="edit_organisation_details">
                                <i class="mdi mdi-cog-outline"></i>
                                <span>Rediger</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-row-between-center g-1 mt-4 pb-4 border-bottom-card">
                        <div class="flex-row-start-start flex-nowrap g-075">
                            <div class="square-80 border-radius-10px position-relative bg-card-border">
                                <div class="w-100 h-100 flex-row-center-center" id="no-profile-picture-container">
                                    <i class="fa-regular fa-building color-cta-inactive font-40"></i>
                                </div>
                            </div>

                            <div class="flex-col-start">
                                <p class="font-14 color-gray font-weight-medium"><?=ucfirst(Translate::word("Organisationsnavn"))?></p>
                                <p class="font-15 mb-2 font-weight-medium"><?=$organisation->name?></p>
                                <?php if($organisation->status === 'ACTIVE'): ?>
                                    <p class="success-box px-2">
                                        <i class="mdi mdi-check-circle-outline"></i> Aktiv
                                    </p>
                                <?php elseif($organisation->status === 'DRAFT'): ?>
                                    <p class="mute-box px-2">
                                        <i class="fa-regular fa-envelope-open"></i> Udkast
                                    </p>
                                <?php elseif($organisation->status === 'INACTIVE'): ?>
                                    <p class="danger-box px-2">
                                        <i class="fa-solid fa-xmark"></i> Inaktiv
                                    </p>
                                <?php elseif($organisation->status === 'DELETED'): ?>
                                    <p class="danger-box px-2">
                                        <i class="fa-regular fa-trash-can"></i> Slettet
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4 rg-1">
                        <div class="col-6">
                            <div class="flex-col-start">
                                <p class="font-14 color-gray font-weight-medium">Virksomhed</p>
                                <p class="font-15 mb-2 font-weight-medium"><?=$organisation->company_name ?? ''?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="flex-col-start">
                                <p class="font-14 color-gray font-weight-medium">CVR-nummer</p>
                                <p class="font-15 mb-2 font-weight-medium"><?=$organisation->cvr ?? ''?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="flex-col-start">
                                <p class="font-14 color-gray font-weight-medium">Adresse</p>
                                <p class="font-15 mb-2 font-weight-medium"><?=Methods::misc()::extractCompanyAddressString($organisation->company_address, false, true)?></p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="flex-col-start">
                                <p class="font-14 color-gray font-weight-medium">Kontakt</p>
                                <div class="flex-row-start-center cg-05">
                                    <i class="mdi mdi-phone font-15"></i>
                                    <p class="font-15 font-weight-medium">
                                        <?php if(!empty($organisation->contact_phone)): ?>
                                        +<?=$organisation->contact_phone?>
                                        <?php else: ?>
                                        <i class="color-gray font-weight-normal">Not set</i>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="flex-row-start-center cg-05">
                                    <i class="mdi mdi-email font-15"></i>
                                    <p class="font-15 font-weight-medium">

                                        <?php if(!empty($organisation->contact_email)): ?>
                                            <?=$organisation->contact_email?>
                                        <?php else: ?>
                                            <i class="color-gray font-weight-normal">Not set</i>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>



        <div class="col-12 col-md-6 d-flex">
            <div class="card border-radius-10px w-100">
                <div class="card-body">
                    <div class="flex-row-between-center g-1">
                        <div class="flex-row-start-center flex-nowrap g-075">
                            <i class="mdi mdi-wallet-outline color-blue font-18"></i>
                            <p class="font-22 font-weight-bold">Viva Wallet</p>
                        </div>
                    </div>

                    <div class="flex-col-start rg-1 mt-3">
                        <?php if($merchantActionRequired): ?>
                        <div class="danger-info-box px-3 py-2">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                                <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-exclamation-thick color-danger-bread"></i></div>
                                <p class="mb-0 info-title color-dark">Handling påkrævet</p>
                            </div>
                            <div class="info-content">
                                <p class="color-gray font-14">Du skal færdiggøre din Viva wallet opsætning før du kan begynde at bruge <?=BRAND_NAME?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="success-info-box px-3 py-2">
                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: 5px">
                                <div class="square-25 flex-row-center flex-align-center"><i class="font-16 mdi mdi-check-circle-outline color-success-text"></i></div>
                                <p class="mb-0 info-title color-dark">Tilsluttet</p>
                            </div>
                            <div class="info-content">
                                <p class="color-gray font-14">Din Viva Wallet konto er aktiv og modtager betalinger</p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if(isEmpty($connectedAccount) || in_array($connectedAccount->state, ['VOID', 'REMOVED'])): ?>
                        <button onclick="VivaWallet.setupVivaWallet(this)" class="btn-v2 mute-hover-design-action-btn-lg flex-row-between-center cg-1">
                            <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                <span class="spinner-border color-gray square-15" role="status" style="border-width: 2px;">
                                  <span class="sr-only">Loading...</span>
                                </span>
                            </span>
                            <span class="font-14">Opsæt min wallet</span>
                            <i class="mdi mdi-cog-outline"></i>
                        </button>
                        <?php elseif($connectedAccount->state === 'DRAFT'): ?>
                        <button onclick="VivaWallet.setupVivaWallet(this)" class="btn-v2 mute-hover-design-action-btn-lg flex-row-between-center cg-1">
                            <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                <span class="spinner-border color-gray square-15" role="status" style="border-width: 2px;">
                                  <span class="sr-only">Loading...</span>
                                </span>
                            </span>
                            <span class="font-14">Færdiggør opsætning</span>
                            <i class="mdi mdi-open-in-new"></i>
                        </button>
                        <?php else: ?>
                        <a href="<?=VIVA_LOGIN_URL?>" class="btn-v2 mute-hover-design-action-btn-lg flex-row-between-center cg-1">
                            <span class="font-14">Åbn Viva wallet</span>
                            <i class="mdi mdi-open-in-new"></i>
                        </a>
                        <?php endif; ?>

                        <p class="font-14 font-weight-medium">Din wallet håndterer:</p>
                        <ul class="pl-3 line-spacing">
                            <li>Din omsætning</li>
                            <li>Alle udbetalinger</li>
                            <li>Logo til specifikke lokationer</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>



        <?php if(\classes\app\OrganisationPermissions::__oRead("organisation", "settings")): ?>
        <?php
            $generalSettings = $organisation->general_settings ?? (object)[];
            $whitelistEnabled = $generalSettings->whitelist_enabled ?? false;
            $whitelistIps = $generalSettings->whitelist_ips ?? [];
            $maxBnplAmount = $generalSettings->max_bnpl_amount ?? null;
            $platformMaxBnpl = Settings::$app->platform_max_bnpl_amount ?? 50000;
        ?>
        <!-- Organisation Settings Row -->
        <div class="row flex-align-stretch rg-15 mt-4">
            <!-- IP Whitelist Card -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-row-between-center g-1 mb-3">
                            <div class="flex-row-start-center flex-nowrap g-075">
                                <i class="mdi mdi-shield-lock-outline font-18 color-blue"></i>
                                <p class="font-20 font-weight-bold">IP Whitelist</p>
                            </div>
                            <?php if(\classes\app\OrganisationPermissions::__oModify("organisation", "settings")): ?>
                            <div class="flex-row-end-center g-075">
                                <span class="font-12 color-gray"><?=$whitelistEnabled ? 'Aktiveret' : 'Deaktiveret'?></span>
                                <label class="form-switch">
                                    <input type="checkbox" id="whitelistEnabledToggle" <?=$whitelistEnabled ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <p class="font-13 color-gray mb-3">Begræns adgang til din organisation til kun godkendte IP-adresser. Ejere er undtaget.</p>

                        <div class="alert alert-info mb-3" style="border-radius: 8px; padding: .5rem .75rem;">
                            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                                <i class="mdi mdi-ip-network font-16"></i>
                                <span class="font-13">Din nuværende IP: <strong class="font-monospace"><?=getUserIp()?></strong></span>
                            </div>
                        </div>

                        <?php if(\classes\app\OrganisationPermissions::__oModify("organisation", "settings")): ?>
                        <div class="flex-row-start flex-align-end flex-nowrap w-100 mb-3" style="gap: .5rem;">
                            <div class="flex-col-start" style="flex: 1;">
                                <label class="form-label font-13 font-weight-medium">Tilføj IP-adresse</label>
                                <input type="text" class="form-field-v2 w-100 h-40px" id="newWhitelistIp" placeholder="192.168.1.1">
                            </div>
                            <button class="btn-v2 action-btn h-40px" id="addWhitelistIpBtn" style="white-space: nowrap;">
                                <i class="mdi mdi-plus"></i>
                                <span>Tilføj</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-sm mb-0 plainDataTable" data-pagination-limit="10" data-sorting-col="0" data-sorting-order="asc">
                                <thead class="color-gray">
                                    <tr>
                                        <th class="font-12">IP-adresse</th>
                                        <?php if(\classes\app\OrganisationPermissions::__oModify("organisation", "settings")): ?>
                                        <th class="font-12 text-right">Handling</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody id="whitelistIpsTable">
                                    <?php if(empty($whitelistIps)): ?>
                                    <tr class="no-ips-row">
                                        <td colspan="2" class="text-center color-gray font-13 py-3">
                                            <i class="mdi mdi-information-outline"></i> Ingen IP-adresser tilføjet
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach($whitelistIps as $ip): ?>
                                    <tr data-ip="<?=htmlspecialchars($ip)?>">
                                        <td class="font-13 font-monospace"><?=htmlspecialchars($ip)?></td>
                                        <?php if(\classes\app\OrganisationPermissions::__oModify("organisation", "settings")): ?>
                                        <td class="text-right">
                                            <button class="btn-v2 danger-btn remove-whitelist-ip" data-ip="<?=htmlspecialchars($ip)?>">
                                                <i class="mdi mdi-delete-outline"></i>
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Small Settings Card -->
            <div class="col-12 col-lg-6 d-flex">
                <div class="card border-radius-10px w-100">
                    <div class="card-body">
                        <div class="flex-row-start-center flex-nowrap g-075 mb-3">
                            <i class="mdi mdi-cog-outline font-18 color-blue"></i>
                            <p class="font-20 font-weight-bold">Indstillinger</p>
                        </div>

                        <form id="orgSettingsForm">
                            <div class="flex-col-start mb-3">
                                <label class="form-label font-14 font-weight-medium">Maks BNPL beløb</label>
                                <div class="flex-row-start flex-align-center flex-nowrap" style="gap: .5rem;">
                                    <input type="number" class="form-field-v2 h-40px" name="max_bnpl_amount" id="maxBnplAmount"
                                           value="<?=htmlspecialchars($maxBnplAmount ?? '')?>"
                                           placeholder="<?=number_format($platformMaxBnpl, 2, ',', '.')?>"
                                           step="0.01" min="0" max="<?=$platformMaxBnpl?>"
                                           style="width: 150px;"
                                           <?=!\classes\app\OrganisationPermissions::__oModify("organisation", "settings") ? 'disabled' : ''?>>
                                    <span class="font-14 color-gray">DKK</span>
                                </div>
                                <small class="form-text text-muted">
                                    Platform standard: <?=number_format($platformMaxBnpl, 2, ',', '.')?> DKK.
                                    Lad feltet være tomt for at bruge standard.
                                </small>
                            </div>

                            <?php if(\classes\app\OrganisationPermissions::__oModify("organisation", "settings")): ?>
                            <button type="submit" class="btn-v2 action-btn" id="saveOrgSettingsBtn">
                                <i class="mdi mdi-content-save"></i>
                                <span>Gem Indstillinger</span>
                            </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <i class="mdi mdi-store-outline font-22 color-blue"></i>
                            <p class="mb-0 font-22 font-weight-bold">Butiksoversigt (Lokationer)</p>
                        </div>

                        <div class="mt-2">
                            <table class="table table-hover">
                                <thead class="color-gray">
                                <th>Butik</th>
                                <th>Omsætning</th>
                                <th>Ordrer</th>
                                <th>Kunder</th>
                                <th>Udvikling (Lfl. måned)</th>
                                <th>Status</th>
                                </thead>
                                <tbody>
                                <?php foreach ($args->locations->list() as $location): ?>
                                    <tr>
                                        <td><?=$location->name?></td>
                                        <td><?=number_format($location->net_sales, 2) . currencySymbol('DKK')?></td>
                                        <td><?=$location->order_count?></td>
                                        <td><?=$location->customer_count?></td>
                                        <td>
                                            <?php $colorClass = 'color-gray';
                                            if($location->lfl_month > 0) $colorClass = 'color-green';
                                            elseif($location->lfl_month < 0) $colorClass = 'color-danger'; ?>
                                            <p class="<?=$colorClass?> font-weight-bold">
                                                <?=$location->lfl_month > 0 ? '+' : ''?>
                                                <?=round($location->lfl_month, 2)?>%
                                            </p>
                                        </td>
                                        <td>
                                            <?php if($location->status === 'ACTIVE'): ?>
                                                <p class="success-box px-2">
                                                    <i class="mdi mdi-check-circle-outline"></i> Aktiv
                                                </p>
                                            <?php elseif($location->status === 'DRAFT'): ?>
                                                <p class="mute-box px-2">
                                                    <i class="fa-regular fa-envelope-open"></i> Udkast
                                                </p>
                                            <?php elseif($location->status === 'INACTIVE'): ?>
                                                <p class="danger-box px-2">
                                                    <i class="fa-solid fa-xmark"></i> Inaktiv
                                                </p>
                                            <?php elseif($location->status === 'DELETED'): ?>
                                                <p class="danger-box px-2">
                                                    <i class="fa-regular fa-trash-can"></i> Slettet
                                                </p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>




    <?php endif; ?>
</div>


<?php scriptStart(); ?>
<script>
    $(document).ready(function () {
        VivaWallet.init('<?=$connectedAccount?->state?>', '<?=$organisation?->uid?>');

        // Whitelist toggle handler
        $('#whitelistEnabledToggle').on('change', async function() {
            const enabled = $(this).is(':checked');
            const result = await post('<?=__url(Links::$api->organisation->updateWhitelistEnabled)?>', { enabled: enabled ? 1 : 0 });
            if(result.status === 'success') {
                showSuccessNotification('Udført', result.message);
                $(this).closest('.flex-row-end-center').find('span').text(enabled ? 'Aktiveret' : 'Deaktiveret');
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke opdatere');
                $(this).prop('checked', !enabled);
            }
        });

        // Add IP to whitelist
        $('#addWhitelistIpBtn').on('click', async function() {
            const ip = $('#newWhitelistIp').val().trim();
            if(!ip) {
                showErrorNotification('Fejl', 'Indtast en IP-adresse');
                return;
            }

            const btn = $(this);
            btn.prop('disabled', true);

            const result = await post('<?=__url(Links::$api->organisation->addWhitelistIp)?>', { ip: ip });

            btn.prop('disabled', false);

            if(result.status === 'success') {
                showSuccessNotification('Udført', result.message);
                $('#newWhitelistIp').val('');

                // Add row to table
                $('.no-ips-row').remove();
                $('#whitelistIpsTable').append(`
                    <tr data-ip="${ip}">
                        <td class="font-13 font-monospace">${ip}</td>
                        <td class="text-right">
                            <button class="btn-v2 danger-btn remove-whitelist-ip" data-ip="${ip}">
                                <i class="mdi mdi-delete-outline"></i>
                            </button>
                        </td>
                    </tr>
                `);
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke tilføje IP');
            }
        });

        // Remove IP from whitelist
        $(document).on('click', '.remove-whitelist-ip', async function() {
            const ip = $(this).data('ip');
            const row = $(this).closest('tr');

            const result = await post('<?=__url(Links::$api->organisation->removeWhitelistIp)?>', { ip: ip });

            if(result.status === 'success') {
                showSuccessNotification('Udført', result.message);
                row.remove();

                // Show empty message if no IPs left
                if($('#whitelistIpsTable tr').length === 0) {
                    $('#whitelistIpsTable').append(`
                        <tr class="no-ips-row">
                            <td colspan="2" class="text-center color-gray font-13 py-3">
                                <i class="mdi mdi-information-outline"></i> Ingen IP-adresser tilføjet
                            </td>
                        </tr>
                    `);
                }
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke fjerne IP');
            }
        });

        // Organisation settings form
        $('#orgSettingsForm').on('submit', async function(e) {
            e.preventDefault();
            const btn = $('#saveOrgSettingsBtn');
            const originalText = btn.html();

            btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Gemmer...');

            const maxBnpl = $('#maxBnplAmount').val();

            const result = await post('<?=__url(Links::$api->organisation->updateSettings)?>', {
                max_bnpl_amount: maxBnpl || null
            });

            btn.prop('disabled', false).html(originalText);

            if(result.status === 'success') {
                showSuccessNotification('Udført', result.message);
            } else {
                showErrorNotification('Fejl', result.error?.message || 'Kunne ikke gemme indstillinger');
            }
        });
    })
</script>
<?php scriptEnd(); ?>



