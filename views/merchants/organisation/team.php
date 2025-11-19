<?php
/**
 * @var object $args
 * @var string|null $pageHeaderTitle
 */
use classes\Methods;
use classes\utility\Titles;
use features\Settings;

$pageTitle = "Team";

$organisationRoles = [];
foreach ($args->permissions as $role => $items) $organisationRoles[$role] = \classes\utility\Titles::cleanUcAll($role);


/*
 * More variables around here.
 */

?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "team";
    var organisationRoles = <?=json_encode($organisationRoles)?>;
</script>
<div class="page-content position-relative" data-page="team">
    <div class="page-inner-content">

        <div class="flex-row-between flex-align-center flex-wrap"  style="gap: .5rem;" id="nav">
            <div class="">
                <div class="flex-row-start flex-align-center flex-nowrap">
                    <p class="text-xl font-weight-bold"><?=$pageHeaderTitle?></p>
                </div>
            </div>

            <div class="flex-row-end flex-align-center flex-nowrap">
                <div class="flex-row-end flex-align-center">
                    <?php if(Methods::subscriptions()->userSubscriptionIsPastDueActive()): ?>
                        <div class="mb-0 danger-box-lg flex-row-start flex-align-center flex-wrap" style="gap: .5rem;">
                            <span class="">Your subscription payment is past due.</span>
                            <button class="btn-v2 danger-btn " onclick="">Process Payment</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <p class="font-25 font-weight-bold">Team Members</p>
            <p class="font-14 color-gray font-weight-medium">Manage your organization's team and their permissions</p>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-row-between flex-align-start " style="column-gap: .75rem; row-gap: .5rem;">
                            <div class="flex-col-start">
                                <p class="font-22 font-weight-bold">Add or Manage Members</p>
                                <p class="font-14 color-gray font-weight-medium text-wrap">
                                    Adding members brings the advantage of not sharing a single account in your organisation.
                                </p><p class="font-14 color-gray font-weight-medium text-wrap">
                                    When inviting you should use the email that the user, that you wish to add, used during their signup.
                                </p>
                            </div>
                            <div class="flex-row-end">
                                <?php __oModifyProtectedContent('team', 'invitations'); ?>
                                <button class="btn-v2 action-btn text-nowrap" name="invite_team_member" onclick="teamInviteModal()">
                                    <i class="fa-solid fa-user-plus"></i>
                                    <span class="text-nowrap">Invite Member</span>
                                </button>
                                <?php __oEndContent(); ?>
                            </div>
                        </div>

                        <div class="mt-3">

                            <?php __oReadProtectedContent('team', 'members'); ?>
                            <table class="table-v2 custom-sorting-table" id="team-members">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th class="sort-active" data-sortDirection="asc">Role</th>
                                        <th>Status</th>
                                        <th class="text-right unsortable">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($args->members->list() as $member): ?>
                                    <tr>
                                        <td class="font-weight-bold" data-sort="<?=$member->name?>">
                                            <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                                <div class="flex-row-center flex-align-center square-30 bg-primary-cta  border-radius-50 ">
                                                    <span class="text-sm text-uppercase color-white">
                                                        <?=__initials($member->name)?>
                                                    </span>
                                                </div>
                                                <p class="font-weight-medium mb-0 text-sm"><?=Titles::truncateStr(Titles::cleanUcAll($member->name), 16)?></p>
                                            </div>
                                        </td>
                                        <td class="text-sm color-gray"><?=$member->email?></td>
                                        <td class="" data-sort="<?=$member->role?>">
                                            <select class="form-select-v2 w-100" name="role">
                                                <?php foreach ($organisationRoles as $role => $title): ?>
                                                <option value="<?=$role?>" <?=$member->role === $role ? 'selected' : ''?>><?=$title?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="" data-sort="<?=$member->show_status?>"><span class="<?=$member->status_box?>-lg"><?=$member->show_status?></span></td>
                                        <td class="text-right">

                                            <div class="flex-row-end">

                                                <div class="dropdown nav-item-v2 p-0 pr-2">
                                                    <a class="color-primary-dark dropdown-no-arrow dropdown-toggle nav-button font-20 font-weight-bold noSelect"
                                                       href="javascript:void(0);" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis font-20 font-weight-bold noSelect"></i>
                                                    </a>
                                                    <div class="dropdown-menu section-dropdown" id="">


                                                        <div class="account-body">
                                                            <p  class="list-title">
                                                                <span>Actions</span>
                                                            </p>
                                                            <?php foreach ($member->action_menu as $menuItem):
                                                                if($menuItem->risk !== "low") continue; ?>
                                                                <a href="javascript:void(0);" class="list-item" onclick="teamMemberAction(this)"
                                                                   data-uuid="<?=$member->uuid->uid?>" data-team-action="<?=$menuItem->action?>">
                                                                    <i class="<?=$menuItem->icon?>"></i>
                                                                    <span><?=$menuItem->title?></span>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>

                                                        <div class="account-footer">
                                                            <?php foreach ($member->action_menu as $menuItem):
                                                                if($menuItem->risk !== "high") continue; ?>
                                                                <a href="javascript:void(0);" class="list-item color-red" onclick="teamMemberAction(this)"
                                                                   data-uuid="<?=$member->uuid->uid?>" data-team-action="<?=$menuItem->action?>">
                                                                    <i class="<?=$menuItem->icon?>"></i>
                                                                    <span><?=$menuItem->title?></span>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>



                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php __oEndContent(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-4"/>

        <div class="mb-4">
            <p class="font-25 font-weight-bold">Role Management</p>
            <p class="font-14 color-gray font-weight-medium">Configure permissions for different roles in your organization</p>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-radius-10px">
                    <div class="card-body" data-switchParent data-switch-id="role_permissions">
                        <div class="flex-row-between flex-align-start flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
                            <div class="flex-col-start">
                                <div class="font-22 flex-align-center flex-row-start flex-nowrap" style="column-gap: .5rem;">
                                    <i class="mdi mdi-shield-outline"></i>
                                    <p class="font-weight-medium">Manage Roles</p>
                                </div>
                                <p class="font-14 color-gray font-weight-medium text-wrap">
                                    Configure permissions for different roles in your organization
                                </p>
                            </div>
                            <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                <select class="form-select-v2 mnw-150px switchViewSelect" name="role_permissions" id="role_permissions">
                                    <?php foreach ($args->permissions as $role => $permissions): ?>
                                        <option value="<?=$role?>"><?=Titles::cleanUcAll($role)?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if(__oModify('roles', 'roles')): ?>
                                <button class="btn-v2 mute-btn text-nowrap" name="create_role" onclick="organisationCreateRole()">
                                    <i class="mdi mdi-plus-circle-outline"></i>
                                    <span class="text-nowrap">Create Role</span>
                                </button>
                                <?php endif; ?>
                                <?php if(__oModify('roles', 'permissions')): ?>
                                <button class="btn-v2 action-btn text-nowrap" name="save_role_permissions" onclick="editRolePermissions()">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    <span class="text-nowrap">Save Changes</span>
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
                                        <p class="font-16 font-weight-bold"><?=Titles::cleanUcAll($role)?> Role</p>
                                        <p class="text-sm color-gray font-weight-medium text-wrap">
                                            Changing and saving the permissions will only save the permissions of the <?=Titles::cleanUcAll($role)?> Role.
                                        </p>
                                        <?php if($role === "owner"): ?>
                                            <div class="warning-box w-fit mt-1">
                                                <span>The 'Owner' role will always have all permissions enabled and cannot be modified.</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-row-end flex-align-center" style="column-gap: .5rem;">
                                        <?php if($role !== "owner" && __oModify('roles', 'roles')): ?>
                                        <div class="btn-v2 mute-btn h-fit noSelect cursor-pointer" data-role="<?=$role?>" onclick="organisationRenameRole(this)">
                                            <i class="fa-solid fa-pencil"></i>
                                            <span>Rename role</span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if($role !== "owner" && __oDelete('roles', 'roles')): ?>
                                        <div class="btn-v2 danger-btn h-fit noSelect cursor-pointer" data-role="<?=$role?>" onclick="organisationDeleteRole(this)">
                                            <i class="fa-solid fa-trash"></i>
                                            <span>Delete role</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <?php __oReadProtectedContent('roles', 'permissions'); ?>


                                <?php foreach ($permissions as $mainObject => $mainPermissions): ?>

                                <div class="mt-4">
                                    <table class="table-v2" id="table-<?=$mainObject?>-permissions">
                                        <thead>
                                            <tr>
                                                <th colspan="3">
                                                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem">
                                                        <i class="color-primary-cta <?=$mainPermissions->icon?>"></i>
                                                        <span class="font-weight-bold"><?=Titles::cleanUcAll($mainObject)?></span>
                                                    </div>
                                                </th>
                                                <th colspan="1">
                                                    <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .75rem;">
                                                        <?php if(property_exists($mainPermissions, 'read')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[read]" <?=$mainPermissions->read ? 'checked' : ''?>>
                                                            <span>Read</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($mainPermissions, 'modify')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[modify]" <?=$mainPermissions->modify ? 'checked' : ''?>>
                                                            <span>Modify</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($mainPermissions, 'delete')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[delete]" <?=$mainPermissions->delete ? 'checked' : ''?>>
                                                            <span>Delete</span>
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
                                                        <span class=""><?=Titles::cleanUcAll($permission)?></span>
                                                    </div>
                                                </td>
                                                <td class="font-14" colspan="1">
                                                    <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .75rem;">
                                                        <?php if(property_exists($permissionItem, 'read')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[permissions][<?=$permission?>][read]" <?=$permissionItem->read ? 'checked' : ''?>>
                                                            <span>Read</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($permissionItem, 'modify')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[permissions][<?=$permission?>][modify]" <?=$permissionItem->modify ? 'checked' : ''?>>
                                                            <span>Modify</span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if(property_exists($permissionItem, 'delete')): ?>
                                                        <div class="flex-row-end flex-align-center flex-nowrap" style="column-gap: .25rem;">
                                                            <input type="checkbox" <?=$role === "owner" ? 'disabled' : ''?>
                                                                   name="<?=$mainObject?>[permissions][<?=$permission?>][delete]" <?=$permissionItem->delete ? 'checked' : ''?>>
                                                            <span>Delete</span>
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

                                <?php __oEndContent() ?>

                            </form>

                            <?php endforeach; ?>


                            <?php if(__oModify('roles', 'permissions')): ?>
                            <div class="mt-4 flex-row-end flex-align-center flex-nowrap" style="column-gap: .5rem;">
                                <button class="btn-v2 action-btn text-nowrap" name="save_role_permissions"  onclick="editRolePermissions()">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    <span class="text-nowrap">Save Changes</span>
                                </button>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>







    </div>
</div>

