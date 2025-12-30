<?php
/**
 * @var object $args
 * @var string|null $pageHeaderTitle
 */

use classes\app\LocationPermissions;
use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\utility\Titles;
use features\Settings;
use classes\lang\Translate;

$location = $args->location;
$pageTitle = $location->name . " - Medlemmer";

$locationRoles = $args->locationRoles;

$organisation = $location->uuid;

/*
 * More variables around here.
 */

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var locationRoles = <?=json_encode($locationRoles)?>;
    var currentLocation = <?=json_encode(['uid' => $location->uid, 'slug' => $location->slug, 'name' => $location->name])?>;
    var organisationMembers = <?=json_encode($args->organisationMembers->toArray())?>;
    var locationMembersApiUrl = <?=json_encode(Links::$api->locations->team->list)?>;
</script>
<div class="page-content home">

    <div class="flex-row-between flex-align-center flex-nowrap" id="nav" style="column-gap: .5rem;">
        <?=\features\DomMethods::locationSelect($args->locationOptions, $args->slug);?>

        <div class="flex-row-end">
            <?=$location->name?>
        </div>
    </div>




    <div class="flex-row-between flex-align-center flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Medlemmer</p>
            <p class="font-14 color-gray font-weight-medium">Administrer lokationsmedlemmer og deres tilladelser</p>
        </div>
        <div class="flex-row-end-center cg-075 flex-nowrap">
            <a href="<?=__url(Links::$merchant->locations->setSingleLocation($args->slug))?>" class="btn-v2 mute-btn text-nowrap" >
                <i class="mdi mdi-arrow-left"></i>
                <span class="text-nowrap">Oversigt</span>
            </a>
            <?php LocationPermissions::__oReadProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->pageBuilder($args->slug))?>" class="btn-v2 action-btn text-nowrap" >
                <i class="fa-regular fa-pen-to-square"></i>
                <span class="text-nowrap">Rediger side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>


    <div class="row mt-4">
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
                            <?php LocationPermissions::__oModifyProtectedContent($location, 'team_invitations'); ?>
                            <button class="btn-v2 action-btn text-nowrap" name="invite_team_member" onclick="teamInviteModal()">
                                <i class="fa-solid fa-user-plus"></i>
                                <span class="text-nowrap">Inviter Medlem</span>
                            </button>
                            <?php LocationPermissions::__oEndContent(); ?>
                        </div>
                    </div>

                    <div class="mt-3">

                        <?php LocationPermissions::__oReadProtectedContent($location, 'team_members'); ?>

                        <!-- Search and Filter Controls -->
                        <div class="flex-row-between flex-align-center flex-wrap mb-3" style="gap: .75rem;">
                            <div class="flex-row-start flex-align-center flex-wrap" style="gap: .75rem;">
                                <!-- Search -->
                                <div class="position-relative">
                                    <input type="text" id="team-search" class="form-field-v2" style="padding-left: calc(1.5rem + 12px); min-width: 200px;"
                                           placeholder="Søg efter navn eller email..." />
                                    <i class="mdi mdi-magnify font-16 position-absolute color-gray" style="top: 11px; left: .75rem;"></i>
                                </div>

                                <!-- Filter by Role -->
                                <select class="form-select-v2" id="team-filter-role" data-selected="all" style="min-width: 140px;">
                                    <option value="all" selected>Alle roller</option>
                                    <?php foreach ($locationRoles as $role => $title): ?>
                                        <option value="<?=$role?>"><?=$title?></option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Filter by Status -->
                                <select class="form-select-v2" id="team-filter-status" data-selected="Active_Pending" style="min-width: 140px;">
                                    <option value="all">Alle statusser</option>
                                    <option value="Active_Pending" selected>Aktiv og Afventer</option>
                                    <option value="Active">Aktiv</option>
                                    <option value="Pending">Afventer</option>
                                    <option value="Suspended">Suspenderet</option>
                                </select>
                            </div>

                            <div class="flex-row-end flex-align-center flex-wrap" style="gap: .75rem;">
                                <!-- Sort -->
                                <select class="form-select-v2" id="team-sort" style="min-width: 160px;">
                                    <option value="name-asc">Navn (A-Å)</option>
                                    <option value="name-desc">Navn (Å-A)</option>
                                    <option value="role-asc">Rolle (A-Å)</option>
                                    <option value="role-desc">Rolle (Å-A)</option>
                                    <option value="status-asc">Status (A-Å)</option>
                                    <option value="status-desc">Status (Å-A)</option>
                                </select>

                                <!-- Items per page -->
                                <select class="form-select-v2" id="team-per-page" style="min-width: 100px;">
                                    <option value="10">10 pr. side</option>
                                    <option value="25">25 pr. side</option>
                                    <option value="50">50 pr. side</option>
                                </select>
                            </div>
                        </div>

                        <!-- Results info -->
                        <div class="flex-row-between flex-align-center mb-2">
                            <p class="mb-0 text-sm color-gray" id="team-results-info">Viser <span id="team-showing">0</span> af <span id="team-total">0</span> medlemmer</p>
                        </div>

                        <table class="table-v2" id="team-members">
                            <thead>
                            <tr>
                                <th>Bruger</th>
                                <th>Email</th>
                                <th>Rolle</th>
                                <th>Status</th>
                                <th class="text-right">Handling</th>
                            </tr>
                            </thead>
                            <tbody id="team-members-tbody">
                            <!-- Loading state - will be replaced by JS -->
                            <tr id="team-loading-row">
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
                        <div id="team-no-results" class="text-center py-4 d-none">
                            <i class="mdi mdi-account-search font-40 color-gray"></i>
                            <p class="color-gray mt-2 mb-0">Ingen medlemmer matcher dine søgekriterier</p>
                        </div>

                        <!-- Pagination -->
                        <div class="flex-row-between flex-align-center mt-3 flex-wrap" style="gap: .75rem;" id="team-pagination-container">
                            <p class="mb-0 text-sm color-gray">Side <span id="team-current-page">1</span> af <span id="team-total-pages">1</span></p>
                            <nav class="pagination-nav" id="team-pagination">
                                <!-- Pagination buttons will be generated by JS -->
                            </nav>
                        </div>

                        <?php LocationPermissions::__oEndContent(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4"/>

    <div class="mb-4">
        <p class="font-25 font-weight-bold">Rolle administrering</p>
        <p class="font-14 color-gray font-weight-medium">Konfigurer tilladelser for medlemsroller hos lokationen</p>
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
                                Konfigurer tilladelser for medlemsrollerne hos lokationen
                            </p>
                        </div>
                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                            <select class="form-select-v2 mnw-150px switchViewSelect" name="role_permissions" id="role_permissions">
                                <?php foreach ($args->permissions as $role => $permissions): ?>
                                    <option value="<?=$role?>"><?=Titles::cleanUcAll(Translate::word($role))?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(LocationPermissions::__oModify($location, 'team_roles')): ?>
                                <button class="btn-v2 mute-btn text-nowrap" name="create_role" onclick="locationCreateRole()">
                                    <i class="mdi mdi-plus-circle-outline"></i>
                                    <span class="text-nowrap">Opret ny rolle</span>
                                </button>
                            <?php endif; ?>
                            <?php if(LocationPermissions::__oModify($location, 'role_permissions')): ?>
                                <button class="btn-v2 action-btn text-nowrap flex-row-center-center flex-nowrap g-05" name="save_role_permissions"  onclick="locationEditRolePermissions(this)">
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
                                        <p class="font-16 font-weight-bold"><?=ucfirst(Translate::word(Titles::cleanUcAll($role)))?>-rolle</p>
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
                                        <?php if($role !== "owner" && LocationPermissions::__oModify($location, 'team_roles')): ?>
                                            <div class="btn-v2 mute-btn h-fit noSelect cursor-pointer" data-role="<?=$role?>" onclick="locationRenameRole(this)">
                                                <i class="fa-solid fa-pencil"></i>
                                                <span>Omdøb rolle</span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if($role !== "owner" && LocationPermissions::__oDelete($location, 'team_roles')): ?>
                                            <div class="btn-v2 danger-btn h-fit noSelect cursor-pointer flex-row-center-center flex-nowrap g-05" data-role="<?=$role?>" onclick="locationDeleteRole(this)">
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


                                <?php LocationPermissions::__oReadProtectedContent($location, 'role_permissions'); ?>


                                <?php foreach ($permissions as $mainObject => $mainPermissions): ?>

                                    <div class="mt-4">
                                        <table class="table-v2" id="table-<?=$mainObject?>-permissions">
                                            <thead>
                                            <tr>
                                                <th colspan="3">
                                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                                        <i class="color-primary-cta <?=$mainPermissions->icon?>"></i>
                                                        <span class="font-weight-bold">
                                                            <?=ucfirst(Translate::context("team.".Titles::clean($mainObject)))?>
                                                        </span>
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
                                                            <span class="">
                                                                <?=ucfirst(Translate::context("team.".Titles::clean($permission)))?>
                                                            </span>
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

                                <?php LocationPermissions::__oEndContent() ?>

                            </form>

                        <?php endforeach; ?>


                        <?php if(LocationPermissions::__oModify($location, 'role_permissions')): ?>
                            <div class="mt-4 flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                <button class="btn-v2 action-btn text-nowrap flex-row-center-center flex-nowrap g-05" name="save_role_permissions"  onclick="locationEditRolePermissions(this)">
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

