<?php
/**
 * @var object $args
 * @var string|null $pageHeaderTitle
 */

use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Titles;
use features\Settings;
use classes\lang\Translate;

$pageTitle = ucfirst(Translate::word("Organisationsmedlemmer"));

$organisationRoles = [];
foreach ($args->permissions as $role => $items) $organisationRoles[$role] = ucfirst(Translate::word(Titles::clean($role)));

$organisation = Settings::$organisation?->organisation;

/*
 * More variables around here.
 */

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "team";
    var organisationRoles = <?=json_encode($organisationRoles)?>;
    var organisationLocations = <?=json_encode($args->locations->toArray())?>;
    var organisationMembersApiUrl = <?=json_encode(Links::$api->organisation->team->list)?>;
</script>
<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <div class="flex-row-start"></div>
        <div class="flex-row-end">
            <?=\features\DomMethods::organisationSelect($args->memberRows, $organisation?->uid);?>
        </div>
    </div>


    <div class="mb-4">
        <p class="font-25 font-weight-bold"><?=ucfirst(Translate::word("Organisationsmedlemmer"))?></p>
        <p class="font-14 color-gray font-weight-medium">Administrer din <?=Translate::word("organisations")?> medlemmer og deres tilladelser</p>
        <p class="font-14 color-gray font-weight-medium">Du kan tilføje medarbejdere til dine lokationer direkte på butikssiden</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-start " style="column-gap: .75rem; row-gap: .5rem;">
                        <div class="flex-col-start">
                            <p class="font-22 font-weight-bold">Tilføj eller administrer</p>
                            <p class="font-14 color-gray font-weight-medium text-wrap">
                                Ved at tilføje medlemmer behøver I ikke dele den samme konto
                            </p>
                            <p class="font-14 color-gray font-weight-medium text-wrap">
                                Når du inviterer en ny person, sender vi dem en email, hvor de kan registrere og tilknytte sig din <?=Translate::word("organisation")?>.
                            </p>
                        </div>
                        <div class="flex-row-end">
                            <?php OrganisationPermissions::__oModifyProtectedContent('team', 'invitations'); ?>
                            <button class="btn-v2 action-btn text-nowrap" name="invite_team_member" onclick="teamInviteModal()">
                                <i class="fa-solid fa-user-plus"></i>
                                <span class="text-nowrap">Inviter Medlem</span>
                            </button>
                            <?php OrganisationPermissions::__oEndContent(); ?>
                        </div>
                    </div>

                    <div class="mt-3">

                        <?php OrganisationPermissions::__oReadProtectedContent('team', 'members'); ?>

                        <!-- Filters and Search -->
                        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <input type="text" class="form-control-v2 form-field-v2" id="org-team-search"
                                           placeholder="Søg efter navn eller email..." style="min-width: 200px;">
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2 w-250px" id="org-team-filter-role" data-selected="hide_location_employees">
                                        <option value="all">Alle roller</option>
                                        <option value="hide_location_employees" selected>Skjul butiksmedarbejdere</option>
                                        <?php foreach ($organisationRoles as $role => $title):
                                            if($role === 'location_employee') continue; ?>
                                            <option value="<?=$role?>"><?=$title?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="org-team-filter-status" data-selected="Active_Pending" style="min-width: 120px;">
                                        <option value="all">Alle status</option>
                                        <option value="Active_Pending" selected>Aktiv og Afventer</option>
                                        <option value="Active">Aktiv</option>
                                        <option value="Pending">Afventer</option>
                                        <option value="Suspended">Suspenderet</option>
                                        <option value="Declined">Afvist</option>
                                        <option value="Retracted">Tilbagetrukket</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .5rem;">
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="org-team-sort" data-selected="created_at-DESC" style="min-width: 150px;">
                                        <option value="created_at-DESC" selected>Nyeste først</option>
                                        <option value="created_at-ASC">Ældste først</option>
                                        <option value="name-ASC">Navn A-Z</option>
                                        <option value="name-DESC">Navn Z-A</option>
                                        <option value="role-ASC">Rolle A-Z</option>
                                        <option value="role-DESC">Rolle Z-A</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <select class="form-select-v2" id="org-team-per-page" data-selected="10" style="min-width: 80px;">
                                        <option value="10" selected>10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <table class="table-v2" id="org-team-members">
                            <thead>
                            <tr>
                                <th>Bruger</th>
                                <th>Email / Brugernavn</th>
                                <th>Rolle</th>
                                <th>Status</th>
                                <th class="text-right">Handling</th>
                            </tr>
                            </thead>
                            <tbody id="org-team-members-tbody">
                            <!-- Loading state - will be replaced by JS -->
                            <tr id="org-team-loading-row">
                                <td colspan="5" class="text-center py-4">
                                    <div class="flex-col-center flex-align-center">
                                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                                            <span class="sr-only">Indlæser...</span>
                                        </span>
                                        <p class="color-gray mt-2 mb-0">Indlæser medlemmer...</p>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- No results message -->
                        <div id="org-team-no-results" class="d-none text-center py-4">
                            <i class="mdi mdi-account-search font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen medlemmer fundet</p>
                        </div>

                        <!-- Pagination -->
                        <div id="org-team-pagination-container" class="flex-row-between flex-align-center flex-wrap mt-3" style="gap: .75rem;">
                            <div class="text-sm color-gray">
                                Viser <span id="org-team-showing">0</span> af <span id="org-team-total">0</span> medlemmer
                                (Side <span id="org-team-current-page">1</span> af <span id="org-team-total-pages">1</span>)
                            </div>
                            <div class="pagination-nav" id="org-team-pagination"></div>
                        </div>

                        <?php OrganisationPermissions::__oEndContent(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4"/>

    <div class="mb-4">
        <p class="font-25 font-weight-bold">Rolle administrering</p>
        <p class="font-14 color-gray font-weight-medium">Konfigurer tilladelser for medlemsroller i <?=Translate::word("organisationen")?></p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-radius-10px">
                <div class="card-body" data-switchParent data-switch-id="role_permissions">
                    <div class="flex-row-between flex-align-start flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
                        <div class="flex-col-start">
                            <div class="font-22 flex-align-center flex-row-start flex-nowrap" style="column-gap: .5rem;">
                                <i class="mdi mdi-shield-outline"></i>
                                <p class="font-weight-medium">Administrer rolle</p>
                            </div>
                            <p class="font-14 color-gray font-weight-medium text-wrap">
                                Konfigurer tilladelser for de medlemsroller i <?=Translate::word("organisationen")?>
                            </p>
                        </div>
                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <select class="form-select-v2 mnw-150px switchViewSelect" name="role_permissions" id="role_permissions">
                                <?php foreach ($args->permissions as $role => $permissions): ?>
                                    <option value="<?=$role?>"><?=Titles::cleanUcAll(Translate::word($role))?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(OrganisationPermissions::__oModify('roles', 'roles')): ?>
                                <button class="btn-v2 mute-btn text-nowrap" name="create_role" onclick="organisationCreateRole()">
                                    <i class="mdi mdi-plus-circle-outline"></i>
                                    <span class="text-nowrap">Opret ny rolle</span>
                                </button>
                            <?php endif; ?>
                            <?php if(OrganisationPermissions::__oModify('roles', 'permissions')): ?>
                                <button class="btn-v2 action-btn text-nowrap flex-row-center-center flex-nowrap g-05" name="save_role_permissions"  onclick="editRolePermissions(this)">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    <span class="text-nowrap">Gem ændringer</span>
                                    <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                                          <span class="sr-only">Indlæser...</span>
                                        </span>
                                    </span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-3"/>

                    <div class="">
                        <?php
                        $n = 0;
                        foreach ($args->permissions as $role => $permissions):
                            $n++;
                            ?>
                            <form method="post" class="switchViewObject" data-switch-id="role_permissions" data-switch-object-name="<?=$role?>"
                                  data-is-shown="<?=$n === 1 ? 'true' : 'false'?>" <?=$n === 1 ? '' : 'style="display:none;"'?>>

                                <div class="flex-row-between flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
                                    <div class="flex-col-start">
                                        <p class="font-16 font-weight-bold"><?=ucfirst(Translate::word(Titles::clean($role)))?>-rolle</p>
                                        <p class="text-sm color-gray font-weight-medium text-wrap">
                                            Du kan kun gemme ændringerne for den rolle, der er synlig (<?=Translate::word(Titles::cleanUcAll($role))?>)
                                        </p>
                                        <?php if($role === "owner"): ?>
                                            <div class="warning-box w-fit mt-1">
                                                <span>Ejer-rollen vil altid have alle tilladelser aktiveret og kan ikke ændres.</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="column-gap: .5rem;">
                                        <?php if($role !== "owner" && OrganisationPermissions::__oModify('roles', 'roles')): ?>
                                            <div class="btn-v2 mute-btn h-fit noSelect cursor-pointer" data-role="<?=$role?>" onclick="organisationRenameRole(this)">
                                                <i class="fa-solid fa-pencil"></i>
                                                <span>Omdøb rolle</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($role !== "owner" && OrganisationPermissions::__oDelete('roles', 'roles')): ?>
                                            <div class="btn-v2 danger-btn h-fit noSelect cursor-pointer flex-row-center-center flex-nowrap g-05" data-role="<?=$role?>" onclick="organisationDeleteRole(this)">
                                                <i class="fa-solid fa-trash"></i>
                                                <span>Slet rolle</span>
                                                <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                                    <span class="spinner-border color-white square-15" role="status" style="border-width: 2px;">
                                                      <span class="sr-only">Indlæser...</span>
                                                    </span>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <?php OrganisationPermissions::__oReadProtectedContent('roles', 'permissions'); ?>


                                <?php foreach ($permissions as $mainObject => $mainPermissions): ?>

                                    <div class="mt-4">
                                        <table class="table-v2" id="table-<?=$mainObject?>-permissions">
                                            <thead>
                                            <tr>
                                                <th colspan="3">
                                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                                        <i class="color-primary-cta <?=$mainPermissions->icon?>"></i>
                                                        <span class="font-weight-bold"><?=ucfirst(Translate::context("team.".Titles::clean($mainObject)))?></span>
                                                    </div>
                                                </th>
                                                <th colspan="1">
                                                    <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .75rem;">
                                                        <?php if(property_exists($mainPermissions, 'read')): ?>
                                                            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                       name="<?=$mainObject?>[read]" <?=$mainPermissions->read ? 'checked' : ''?>>
                                                                <span>Læse</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($mainPermissions, 'modify')): ?>
                                                            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                       name="<?=$mainObject?>[modify]" <?=$mainPermissions->modify ? 'checked' : ''?>>
                                                                <span>Redigere</span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($mainPermissions, 'delete')): ?>
                                                            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                       name="<?=$mainObject?>[delete]" <?=$mainPermissions->delete ? 'checked' : ''?>>
                                                                <span>Slette</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </th>

                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($mainPermissions->permissions as $permission => $permissionItem): ?>
                                                <tr>
                                                    <td colspan="3">
                                                        <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                                            <i class="color-primary-cta <?=$mainPermissions->icon?>" style="visibility: hidden"></i>
                                                            <span class=""><?=ucfirst(Translate::context("team.".Titles::clean($permission)))?></span>
                                                        </div>
                                                    </td>
                                                    <td class="font-14" colspan="1">
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .75rem;">
                                                            <?php if(property_exists($permissionItem, 'read')): ?>
                                                                <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                    <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                           name="<?=$mainObject?>[permissions][<?=$permission?>][read]" <?=$permissionItem->read ? 'checked' : ''?>>
                                                                    <span>Læse</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if(property_exists($permissionItem, 'modify')): ?>
                                                                <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                    <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                           name="<?=$mainObject?>[permissions][<?=$permission?>][modify]" <?=$permissionItem->modify ? 'checked' : ''?>>
                                                                    <span>Redigere</span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if(property_exists($permissionItem, 'delete')): ?>
                                                                <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                                    <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                           name="<?=$mainObject?>[permissions][<?=$permission?>][delete]" <?=$permissionItem->delete ? 'checked' : ''?>>
                                                                    <span>Slette</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>

                                <?php endforeach; ?>

                                <?php OrganisationPermissions::__oEndContent() ?>

                            </form>

                        <?php endforeach; ?>


                        <?php if(OrganisationPermissions::__oModify('roles', 'permissions')): ?>
                            <div class="mt-4 flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                <button class="btn-v2 action-btn text-nowrap flex-row-center-center flex-nowrap g-05" name="save_role_permissions"  onclick="editRolePermissions(this)">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    <span class="text-nowrap">Gem ændringer</span>
                                    <span class="ml-3 flex-align-center flex-row-start button-disabled-spinner">
                                        <span class="spinner-border color-blue square-15" role="status" style="border-width: 2px;">
                                          <span class="sr-only">Indlæser...</span>
                                        </span>
                                    </span>
                                </button>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>







</div>

