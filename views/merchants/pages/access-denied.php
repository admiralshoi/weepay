<?php
/**
 * Access Denied Page - IP Not Whitelisted
 * @var object $args
 */

use classes\enumerations\Links;
use classes\Methods;

$userIp = getUserIp();
$organisationName = \features\Settings::$organisation?->organisation?->name ?? 'Organisationen';
$currentOrgUid = \features\Settings::$organisation?->organisation?->uid;

// Get user's organisations for the dropdown
$memberRows = Methods::organisationMembers()->getUserOrganisations();
$memberRows = mapItemToKeyValuePairs(array_column($memberRows->toArray(), "organisation"), 'uid', 'name');
?>

<div class="page-content mt-5">
    <div class="page-inner-content">
        <div class="flex-col-center flex-align-center py-5" style="min-height: 60vh;">
            <div class="card border-radius-10px" style="max-width: 500px; width: 100%;">
                <div class="card-body text-center py-5 px-4">
                    <div class="flex-row-center flex-align-center mb-4">
                        <div class="square-80 flex-row-center flex-align-center bg-danger-light border-radius-50">
                            <i class="mdi mdi-shield-lock-outline font-40 color-danger"></i>
                        </div>
                    </div>

                    <h2 class="font-24 font-weight-bold mb-2">Adgang Nægtet</h2>
                    <p class="font-14 color-gray mb-4">
                        Din IP-adresse er ikke godkendt til at tilgå <strong><?=htmlspecialchars($organisationName)?></strong>.
                    </p>

                    <div class="alert alert-warning text-left mb-4" style="border-radius: 8px;">
                        <div class="flex-row-start flex-align-start" style="gap: .5rem;">
                            <i class="mdi mdi-information-outline font-18 mt-1"></i>
                            <div>
                                <p class="mb-1 font-14 font-weight-bold">Din IP-adresse</p>
                                <p class="mb-0 font-monospace font-13"><?=htmlspecialchars($userIp)?></p>
                            </div>
                        </div>
                    </div>

                    <p class="font-13 color-gray mb-4">
                        Din organisation har aktiveret IP-begrænsning. Kontakt din administrator eller ejer
                        for at få din IP-adresse tilføjet til whitelist.
                    </p>

                    <?php if(count($memberRows) > 1): ?>
                    <div class="mb-4">
                        <?=\features\DomMethods::organisationSelect($memberRows, $currentOrgUid)?>
                    </div>
                    <?php endif; ?>

                    <div class="flex-col-start" style="gap: .75rem;">
                        <a href="<?=__url(Links::$merchant->organisation->add)?>" class="btn-v2 action-btn w-100">
                            <i class="mdi mdi-plus"></i>
                            <span>Opret ny Organisation</span>
                        </a>
                        <a href="<?=__url(Links::$app->logout)?>" class="btn-v2 gray-btn w-100">
                            <i class="mdi mdi-logout"></i>
                            <span>Log Ud</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
