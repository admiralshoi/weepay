<?php
/**
 * Admin Panel - Users Management
 * Create users and manage user roles
 * @var object $args
 */

use classes\enumerations\Links;
use classes\utility\Titles;

$pageTitle = "Brugere";
$userRoles = $args->userRoles ?? new \Database\Collection();

?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "users";
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Brugere</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Brugere</h1>
                    <p class="mb-0 font-14 color-gray">Opret brugere og administrer brugerroller</p>
                </div>
                <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                    <a href="<?=__url(Links::$admin->dashboardUsers)?>" class="btn-v2 trans-btn">
                        <i class="mdi mdi-account-group-outline mr-1"></i> Se alle brugere
                    </a>
                </div>
            </div>

            <!-- Create User Card -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-start flex-align-center mb-3" style="gap: .75rem;">
                        <div class="square-40 bg-blue border-radius-8px flex-row-center-center">
                            <i class="mdi mdi-account-plus color-white font-20"></i>
                        </div>
                        <div class="flex-col-start">
                            <p class="mb-0 font-16 font-weight-bold">Opret ny bruger</p>
                            <p class="mb-0 font-12 color-gray">Tilføj en ny bruger til platformen</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="flex-col-start" style="gap: 1rem;">
                                <!-- Name (required) -->
                                <div class="flex-col-start w-100">
                                    <label class="font-12 color-gray mb-1">Navn <span class="color-danger">*</span></label>
                                    <input type="text" class="form-field-v2" id="createUserName" placeholder="Fulde navn">
                                </div>

                                <!-- Role (required) -->
                                <div class="flex-col-start w-100">
                                    <label class="font-12 color-gray mb-1">Rolle <span class="color-danger">*</span></label>
                                    <select class="form-select-v2 w-100" id="createUserRole">
                                        <?php foreach ($userRoles->list() as $role):
                                            if($role->access_level === 0) continue;
                                            ?>
                                            <option value="<?=$role->access_level?>"><?=Titles::clean($role->name)?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Email (optional) -->
                                <div class="flex-col-start w-100">
                                    <label class="font-12 color-gray mb-1">Email <span class="font-11 color-gray">(valgfri)</span></label>
                                    <input type="email" class="form-field-v2" id="createUserEmail" placeholder="email@eksempel.dk">
                                </div>

                                <!-- Username (optional) -->
                                <div class="flex-col-start w-100">
                                    <label class="font-12 color-gray mb-1">Brugernavn <span class="font-11 color-gray">(valgfri - autogenereres hvis tomt)</span></label>
                                    <input type="text" class="form-field-v2" id="createUserUsername" placeholder="brugernavn">
                                </div>

                                <button type="button" class="btn-v2 action-btn" onclick="createUser()">
                                    <i class="mdi mdi-account-plus-outline mr-1"></i> Opret bruger
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Info box -->
                            <div class="p-3 bg-lightest-blue border-radius-8px h-100">
                                <div class="flex-col-start" style="gap: 1rem;">
                                    <div class="flex-row-start" style="gap: .5rem;">
                                        <i class="mdi mdi-information-outline font-16 color-blue"></i>
                                        <p class="mb-0 font-13 font-weight-bold color-blue">Information</p>
                                    </div>
                                    <ul class="mb-0 pl-3 font-13 color-gray" style="line-height: 1.8;">
                                        <li>Hvis intet brugernavn angives, genereres et automatisk</li>
                                        <li>Loginoplysninger vises efter oprettelsen</li>
                                        <li>Brugeren vil blive bedt om at ændre adgangskoden ved første login</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Roles Table -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <div class="flex-row-between flex-align-center mb-3">
                        <div class="flex-row-start flex-align-center" style="gap: .75rem;">
                            <div class="square-40 bg-green border-radius-8px flex-row-center-center">
                                <i class="mdi mdi-account-key color-white font-20"></i>
                            </div>
                            <div class="flex-col-start">
                                <p class="mb-0 font-16 font-weight-bold">Brugerroller</p>
                                <p class="mb-0 font-12 color-gray"><?=$userRoles->count()?> roller defineret</p>
                            </div>
                        </div>
                        <button class="btn-v2 action-btn" onclick="openAddRoleModal()">
                            <i class="mdi mdi-plus mr-1"></i> Tilføj rolle
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table-v2 plainDataTable" data-pagination-limit="15" data-sorting-col="0" data-sorting-order="asc">
                            <thead>
                                <tr>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Adgangsniveau</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Navn</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Beskrivelse</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase">Status</th>
                                    <th class="font-12 font-weight-medium color-gray text-uppercase text-right">Handling</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRoles->list() as $role): ?>
                                    <tr data-access-level="<?=$role->access_level?>">
                                        <td class="font-13 font-weight-bold"><?=$role->access_level?></td>
                                        <td class="font-13"><?=Titles::clean($role->name)?></td>
                                        <td class="font-13 color-gray" style="max-width: 300px;">
                                            <span class="text-truncate d-inline-block" style="max-width: 100%;">
                                                <?=htmlspecialchars($role->description ?? '-')?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if((int)($role->defined ?? 0) === 1): ?>
                                                <span class="success-box font-11">Aktiv</span>
                                            <?php else: ?>
                                                <span class="mute-box font-11">Inaktiv</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <button class="btn-v2 trans-btn btn-sm"
                                                    onclick="openEditRoleModal(<?=$role->access_level?>, '<?=htmlspecialchars($role->name)?>', '<?=htmlspecialchars($role->description ?? '')?>', <?=(int)$role->defined?>)">
                                                <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                                            </button>
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
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Tilføj ny rolle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start" style="gap: 1rem;">
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Adgangsniveau <span class="color-danger">*</span></label>
                        <input type="number" class="form-field-v2" id="addRoleAccessLevel" placeholder="F.eks. 3, 4, 5..." min="1" max="7">
                        <p class="mb-0 font-11 color-gray mt-1">Vælg et nummer mellem 1-7 (0 er gæst, 8-9 er admin)</p>
                    </div>
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Navn <span class="color-danger">*</span></label>
                        <input type="text" class="form-field-v2" id="addRoleName" placeholder="Rollenavn (f.eks. manager)">
                    </div>
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Beskrivelse</label>
                        <textarea class="form-field-v2" id="addRoleDescription" rows="3" placeholder="Beskrivelse af rollen..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="addRole()">
                    <i class="mdi mdi-plus mr-1"></i> Tilføj rolle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Rediger rolle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="flex-col-start" style="gap: 1rem;">
                    <input type="hidden" id="editRoleAccessLevel">
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Adgangsniveau</label>
                        <input type="text" class="form-field-v2 bg-light-gray" id="editRoleAccessLevelDisplay" readonly>
                    </div>
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Navn</label>
                        <input type="text" class="form-field-v2 bg-light-gray" id="editRoleNameDisplay" readonly>
                        <p class="mb-0 font-11 color-gray mt-1">Rollenavn kan ikke ændres</p>
                    </div>
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Beskrivelse</label>
                        <textarea class="form-field-v2" id="editRoleDescription" rows="3" placeholder="Beskrivelse af rollen..."></textarea>
                    </div>
                    <div class="flex-col-start w-100">
                        <label class="font-12 color-gray mb-1">Status</label>
                        <select class="form-select-v2 w-100" id="editRoleDefined">
                            <option value="1">Aktiv</option>
                            <option value="0">Inaktiv</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="updateRole()">
                    <i class="mdi mdi-content-save-outline mr-1"></i> Gem ændringer
                </button>
            </div>
        </div>
    </div>
</div>

<?php scriptStart(); ?>
<script>
    var addRoleModal = null;
    var editRoleModal = null;

    $(document).ready(function() {
        addRoleModal = $('#addRoleModal');
        editRoleModal = $('#editRoleModal');
        selectV2();
    });

    function openCreateUserModal() {
        // This page has inline form, but keeping for compatibility
    }

    async function createUser() {
        var name = $('#createUserName').val().trim();
        var role = $('#createUserRole').val();
        var email = $('#createUserEmail').val().trim();
        var username = $('#createUserUsername').val().trim();

        if (!name) {
            showErrorNotification('Fejl', 'Navn er påkrævet');
            return;
        }

        if (!role) {
            showErrorNotification('Fejl', 'Rolle er påkrævet');
            return;
        }

        var result = await post(platformLinks.api.admin.panel.createUser, {
            full_name: name,
            access_level: role,
            email: email || null,
            username: username || null
        });

        if (result.status === 'success') {
            // Clear form
            $('#createUserName').val('');
            $('#createUserEmail').val('');
            $('#createUserUsername').val('');

            if (result.data && result.data.user_created) {
                showCredentialsModal(result.data);
            } else {
                showSuccessNotification('Oprettet', 'Brugeren er blevet oprettet');
            }
        } else {
            showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
        }
    }

    function openAddRoleModal() {
        $('#addRoleAccessLevel').val('');
        $('#addRoleName').val('');
        $('#addRoleDescription').val('');
        addRoleModal.modal('show');
    }

    async function addRole() {
        var accessLevel = $('#addRoleAccessLevel').val().trim();
        var name = $('#addRoleName').val().trim();
        var description = $('#addRoleDescription').val().trim();

        if (!accessLevel) {
            showErrorNotification('Fejl', 'Adgangsniveau er påkrævet');
            return;
        }

        if (!name) {
            showErrorNotification('Fejl', 'Rollenavn er påkrævet');
            return;
        }

        var result = await post(platformLinks.api.admin.panel.createRole, {
            access_level: parseInt(accessLevel),
            name: name,
            description: description || null
        });

        if (result.status === 'success') {
            addRoleModal.modal('hide');
            showSuccessNotification('Rolle tilføjet', 'Den nye rolle er blevet oprettet');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
        }
    }

    function openEditRoleModal(accessLevel, name, description, defined) {
        $('#editRoleAccessLevel').val(accessLevel);
        $('#editRoleAccessLevelDisplay').val(accessLevel);
        $('#editRoleNameDisplay').val(name);
        $('#editRoleDescription').val(description);
        updateSelectV2Value($('#editRoleDefined'), defined.toString());
        editRoleModal.modal('show');
    }

    async function updateRole() {
        var accessLevel = $('#editRoleAccessLevel').val();
        var description = $('#editRoleDescription').val().trim();
        var defined = $('#editRoleDefined').val();

        var result = await post(platformLinks.api.admin.panel.updateRole, {
            access_level: parseInt(accessLevel),
            description: description,
            defined: parseInt(defined)
        });

        if (result.status === 'success') {
            editRoleModal.modal('hide');
            showSuccessNotification('Rolle opdateret', 'Rollen er blevet opdateret');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showErrorNotification('Fejl', result.error?.message || 'Der opstod en fejl');
        }
    }

    function showCredentialsModal(data) {
        var credentialsHtml = `
            <div class="modal fade" id="user-credentials-modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content border-radius-10px">
                        <div class="modal-header border-0">
                            <h5 class="modal-title font-weight-bold">
                                <i class="mdi mdi-check-circle color-success mr-1"></i>
                                Bruger oprettet!
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p class="font-14 mb-3">
                                <strong>${data.full_name}</strong> er blevet oprettet.
                                ${data.email_sent ? 'En email med loginoplysninger er sendt.' : 'Del følgende loginoplysninger med personen:'}
                            </p>
                            <div class="p-3 bg-light-gray border-radius-8px">
                                <div class="flex-col-start" style="row-gap: 1rem;">
                                    <div>
                                        <p class="font-12 font-weight-medium mb-1 color-gray">Brugernavn:</p>
                                        <div class="flex-row-between flex-align-center">
                                            <p class="font-16 font-weight-bold mb-0">${data.username}</p>
                                            <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.username}', this)">
                                                <i class="mdi mdi-content-copy"></i> Kopier
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-12 font-weight-medium mb-1 color-gray">Adgangskode:</p>
                                        <div class="flex-row-between flex-align-center">
                                            <p class="font-16 font-weight-bold mb-0">${data.password}</p>
                                            <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.password}', this)">
                                                <i class="mdi mdi-content-copy"></i> Kopier
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-lightest-yellow border-radius-8px mt-3">
                                <div class="flex-row-start" style="gap: .5rem;">
                                    <i class="mdi mdi-alert-outline font-16 color-warning"></i>
                                    <p class="mb-0 font-12 color-gray">
                                        Brugeren vil blive bedt om at ændre adgangskoden ved første login.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn-v2 action-btn" data-dismiss="modal">
                                <i class="mdi mdi-check mr-1"></i> Færdig
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#user-credentials-modal').remove();
        $('body').append(credentialsHtml);
        $('#user-credentials-modal').modal('show');
    }

    function copyCredential(text, btn) {
        var $btn = $(btn);
        var originalHtml = $btn.html();

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                $btn.html('<i class="mdi mdi-check"></i> Kopieret!');
                setTimeout(() => $btn.html(originalHtml), 2000);
            }).catch(() => {
                fallbackCopy(text, $btn, originalHtml);
            });
        } else {
            fallbackCopy(text, $btn, originalHtml);
        }
    }

    function fallbackCopy(text, $btn, originalHtml) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            $btn.html('<i class="mdi mdi-check"></i> Kopieret!');
            setTimeout(() => $btn.html(originalHtml), 2000);
        } catch (err) {
            console.error('Failed to copy:', err);
        }
        document.body.removeChild(textArea);
    }
</script>
<?php scriptEnd(); ?>
