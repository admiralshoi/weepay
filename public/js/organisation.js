





async function teamInviteModal() {
    console.log(organisationLocations)
    let modal = new ModalHandler('organisationTeamInvite')
    modal.construct({roles: organisationRoles, locations: organisationLocations})
    await modal.build()
        .then(() => {
            selectV2();

            // Handle location scope type change
            let scopeTypeSelect = $('#location-scope-type');
            let scopedContainer = $('#scoped-locations-container');

            scopeTypeSelect.on('change', function() {
                if($(this).val() === 'scoped') {
                    scopedContainer.removeClass('d-none');
                } else {
                    scopedContainer.addClass('d-none');
                }
            });
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        invite: async (btn, modalHandler) => {
            let parent = btn.parents('#organisation-team-invite').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;
            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
            }
            const end = () => {
                btn.removeAttr('disabled')
            }
            const start = () => {
                btn.attr('disabled', 'disabled')
                clearError()
            }
            const setError = (txt) => {
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                end()
            }


            start();
            screenLoader.show("Adding member...")

            let formData = new FormData(form.get(0))

            // Handle scoped locations
            let scopeType = formData.get('location_scope_type');
            if(scopeType === 'scoped') {
                let scopedLocationsSelect = $('#scoped-locations-select');
                let selectedLocations = scopedLocationsSelect.val();
                if(!selectedLocations || selectedLocations.length === 0) {
                    setError('Vælg venligst mindst én lokation for scoped tilladelser');
                    screenLoader.hide()
                    return false;
                }
                formData.set('scoped_locations', JSON.stringify(selectedLocations));
            } else {
                formData.delete('scoped_locations');
            }
            formData.delete('location_scope_type');

            let result = await post(platformLinks.api.organisation.team.invite, formData);

            if(result.status === "error") {
                setError(result.error.message)
                screenLoader.hide()
                return false;
            }

            screenLoader.hide()

            // Check if a new user was created
            if(result.data && result.data.user_created) {
                // Show credentials modal
                showCredentialsModal(result.data)
            } else {
                // Existing user invited
                notifyTopCorner(result.message, 1500, "bg-success")
                modalHandler.close()

                // Refresh the members table instead of reloading the page
                if(typeof OrganisationMembersPagination !== 'undefined' && OrganisationMembersPagination.refresh) {
                    OrganisationMembersPagination.refresh();
                } else {
                    handleStandardApiRedirect(result, 800)
                }
            }
        }
    })
    modal.open()
}


function showCredentialsModal(data) {
    // Close the invite modal first
    $('#organisation-team-invite').modal('hide')

    // Create credentials modal
    let credentialsHtml = `
        <div class="modal fade" id="team-credentials-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog vertical-middle" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bruger Oprettet!</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="font-14 mb-3">
                            ${data.full_name} er blevet oprettet og tilføjet til dit team.
                            ${data.email_sent ? 'En email med loginoplysninger er sendt.' : 'Del følgende loginoplysninger med personen:'}
                        </p>
                        <div class="p-3 bg-light border-radius-5" style="background: #f8f9fa;">
                            <div class="flex-col-start" style="row-gap: 1rem;">
                                <div>
                                    <p class="font-12 font-weight-bold mb-1 color-gray">Brugernavn:</p>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="font-16 font-weight-bold mb-0">${data.username}</p>
                                        <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.username}', this)">
                                            <i class="fa fa-copy"></i> Kopier
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="font-12 font-weight-bold mb-1 color-gray">Midlertidigt Kodeord:</p>
                                    <div class="flex-row-between flex-align-center">
                                        <p class="font-16 font-weight-bold mb-0">${data.password}</p>
                                        <button class="btn-v2 mute-btn btn-sm" onclick="copyCredential('${data.password}', this)">
                                            <i class="fa fa-copy"></i> Kopier
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="font-12 color-orange mt-3 mb-0">
                            <i class="fa fa-info-circle"></i> Brugeren vil blive bedt om at ændre kodeord ved første login.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-v2 action-btn" onclick="closeCredentialsAndRefresh()">
                            Forstået
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `

    // Remove existing credentials modal if any
    $('#team-credentials-modal').remove()

    // Add and show new modal
    $('body').append(credentialsHtml)
    $('#team-credentials-modal').modal('show')
}

function copyCredential(text, btn) {
    let $btn = $(btn)
    let originalHtml = $btn.html()

    // Use modern Clipboard API
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            $btn.html('<i class="fa fa-check"></i> Kopieret!')
            notifyTopCorner("Copied!")
            setTimeout(() => {
                $btn.html(originalHtml)
            }, 2000)
        }).catch(() => {
            // Fallback to old method
            fallbackCopy(text, $btn, originalHtml)
        })
    } else {
        // Fallback for older browsers or non-secure contexts
        fallbackCopy(text, $btn, originalHtml)
    }
}

function fallbackCopy(text, $btn, originalHtml) {
    const el = document.createElement('textarea')
    el.value = text
    el.setAttribute('readonly', '')
    el.style.position = 'absolute'
    el.style.left = '-9999px'
    document.body.appendChild(el)
    el.select()
    const success = document.execCommand('copy')
    document.body.removeChild(el)

    if (success) {
        $btn.html('<i class="fa fa-check"></i> Kopieret!')
        notifyTopCorner("Copied!")
        setTimeout(() => {
            $btn.html(originalHtml)
        }, 2000)
    }
}

function closeCredentialsAndRefresh() {
    $('#team-credentials-modal').modal('hide')
    setTimeout(() => {
        // Refresh the members table instead of reloading the page
        if(typeof OrganisationMembersPagination !== 'undefined' && OrganisationMembersPagination.refresh) {
            OrganisationMembersPagination.refresh();
        } else {
            window.location.reload()
        }
    }, 300)
}


async function teamMemberAction(btn) {
    btn = $(btn);
    let row = btn.parents("tr").first()
    let role = row.find("select[name=role]").first().val()
    let action = btn.attr('data-team-action')
    let memberUuid = btn.attr('data-uuid')
    if(empty(role, action, memberUuid)) return;

    // Handle edit-scoped-locations action separately
    if(action === 'edit-scoped-locations') {
        let memberName = row.find("td").first().text().trim()
        await editMemberScopedLocations(memberUuid, memberName)
        return;
    }

    screenLoader.show("Updating member...")
    let result = await post(platformLinks.api.organisation.team.update, {action, role, member_uuid: memberUuid});

    if(result.status === "error") {
        screenLoader.hide()
        notifyTopCorner(result.error.message, 5000, "bg-red")
        return false;
    }

    screenLoader.hide()
    notifyTopCorner(result.message)

    // Refresh the members table instead of reloading the page
    if(typeof OrganisationMembersPagination !== 'undefined' && OrganisationMembersPagination.refresh) {
        OrganisationMembersPagination.refresh();
    } else {
        handleStandardApiRedirect(result, 1000)
    }
}


async function editMemberScopedLocations(memberUuid, memberName) {
    // First, fetch current member data
    screenLoader.show("Henter data...")
    let memberDataResult = await post(platformLinks.api.organisation.team.scopedLocations.get, {member_uuid: memberUuid});
    screenLoader.hide()


    if(memberDataResult.status === "error") {
        notifyTopCorner(memberDataResult.error.message, 5000, "bg-red")
        return false;
    }

    let memberData = memberDataResult.data
    let scopedLocations = memberData.scoped_locations || []
    let scopeType = scopedLocations.length > 0 ? 'scoped' : 'all'

    let modal = new ModalHandler('organisationMemberScopedLocations')
    modal.construct({
        member_name: memberName,
        member_uuid: memberUuid,
        locations: organisationLocations,
        selected_locations: scopedLocations,
        scope_type: scopeType
    })
    await modal.build()
        .then(() => {
            selectV2();

            // Handle location scope type change
            let scopeTypeSelect = $('#edit-location-scope-type');
            let scopedContainer = $('#edit-scoped-locations-container');

            scopeTypeSelect.on('change', function() {
                if($(this).val() === 'scoped') {
                    scopedContainer.removeClass('d-none');
                } else {
                    scopedContainer.addClass('d-none');
                }
            });
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        save: async (btn, modalHandler) => {
            let parent = btn.parents('#organisation-member-scoped-locations').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;

            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
            }
            const end = () => {
                btn.removeAttr('disabled')
            }
            const start = () => {
                btn.attr('disabled', 'disabled')
                clearError()
            }
            const setError = (txt) => {
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                end()
            }

            start();
            screenLoader.show("Gemmer ændringer...")

            let formData = new FormData(form.get(0))
            formData.append('member_uuid', memberUuid)

            // Handle scoped locations
            let scopeType = formData.get('location_scope_type');
            if(scopeType === 'scoped') {
                let scopedLocationsSelect = $('#edit-scoped-locations-select');
                let selectedLocations = scopedLocationsSelect.val();
                if(!selectedLocations || selectedLocations.length === 0) {
                    setError('Vælg venligst mindst én lokation for scoped tilladelser');
                    screenLoader.hide()
                    return false;
                }
                formData.set('scoped_locations', JSON.stringify(selectedLocations));
            } else {
                formData.set('scoped_locations', JSON.stringify([]));
            }
            formData.delete('location_scope_type');

            let result = await post(platformLinks.api.organisation.team.scopedLocations.update, formData);

            if(result.status === "error") {
                setError(result.error.message)
                screenLoader.hide()
                return false;
            }

            screenLoader.hide()
            notifyTopCorner(result.message, 1500, "bg-success")
            screenLoader.update('Ændringer gemt. Genindlæser siden...')
            handleStandardApiRedirect(result, 800)
        }
    })
    modal.open()
}


async function invitationAction(btn) {
    if('invitationAction' in applicationProcessing && applicationProcessing.invitationAction) return false;
    applicationProcessing.invitationAction = true;
    btn.disabled = true
    btn = $(btn);
    let action = btn.attr('data-invitation-action')
    let organisationId = btn.attr('data-organisation-id')
    if(empty(action, organisationId)) {
        applicationProcessing.invitationAction = false;
        btn.get(0).disabled = false
        return;
    }

    let result = await post(platformLinks.api.organisation.team.respond, {action, organisation_id: organisationId});

    if(result.status === "error") {
        showErrorNotification("Der opstod en fejl",result.error.message)
        applicationProcessing.invitationAction = false;
        btn.get(0).disabled = false
        return false;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result)
    if(action === 'decline') {
        btn.parents('tr').first().remove()
    }
    removeQueuedNotification();
    showSuccessNotification("Handling fuldført", result.message)
    applicationProcessing.invitationAction = false;
    btn.get(0).disabled = false
}



async function organisationCreateRole() {
    let modal = new ModalHandler('organisationCreateRole')
    modal.construct()
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        create: async (btn, modalHandler) => {
            if('organisationCreateRole' in applicationProcessing && applicationProcessing.organisationCreateRole) return false;
            applicationProcessing.organisationCreateRole = true;
            let parent = btn.parents('.modal').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;

            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
                form.find('.error-shadow').each(function () { $(this).removeClass('error-shadow') })
            }
            const end = () => {
                btn.get(0).disabled = false;
                applicationProcessing.organisationCreateRole = false;
            }
            const start = () => {
                btn.get(0).disabled = true;
                clearError()
            }
            const setError = (error) => {
                let txt = error.message
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                if('blame_field' in error) {
                    let blameId = error.blame_field;
                    let blameElement = form.find(`[name=${blameId}]`).first();
                    if(blameElement.length) blameElement.addClass('error-shadow')
                }
                end()
            }
            start();

            let formData = new FormData(form.get(0))
            let result = await post(platformLinks.api.organisation.team.role.create, formData);

            if(result.status === "error") {
                setError(result.error)
                return false;
            }

            queueNotificationOnLoad("Handling fuldført", result.message, 'success')
            handleStandardApiRedirect(result, 1)

            setTimeout(function (){
                applicationProcessing.organisationCreateRole = false;
                removeQueuedNotification();
                showSuccessNotification("Handling fuldført", result.message)
            }, 100)

        }
    })
    modal.open()
}


async function organisationRenameRole(btn) {
    btn = $(btn);
    let role = btn.attr("data-role")
    if(empty(role)) return;
    let modal = new ModalHandler('organisationRenameRole')
    modal.construct({role: role, role_name: Translate.word(role)})
    await modal.build()
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        rename: async (btn, modalHandler) => {
            if('organisationRenameRole' in applicationProcessing && applicationProcessing.organisationRenameRole) return false;
            applicationProcessing.organisationRenameRole = true;
            let parent = btn.parents('.modal').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();
            if(empty(errorBox)) return;

            const clearError = () => {
                errorBox.text('')
                errorBox.addClass('d-none')
                form.find('.error-shadow').each(function () { $(this).removeClass('error-shadow') })
            }
            const end = () => {
                btn.get(0).disabled = false;
                applicationProcessing.organisationRenameRole = false;
            }
            const start = () => {
                btn.get(0).disabled = true;
                clearError()
            }
            const setError = (error) => {
                let txt = error.message
                errorBox.text(txt)
                errorBox.removeClass('d-none')
                if('blame_field' in error) {
                    let blameId = error.blame_field;
                    let blameElement = form.find(`[name=${blameId}]`).first();
                    if(blameElement.length) blameElement.addClass('error-shadow')
                }
                end()
            }
            start();

            let formData = new FormData(form.get(0))
            formData.append("role", role);
            let result = await post(platformLinks.api.organisation.team.role.rename, formData);

            if(result.status === "error") {
                setError(result.error)
                return false;
            }

            queueNotificationOnLoad("Handling fuldført", result.message, 'success')
            handleStandardApiRedirect(result, 1)

            setTimeout(function (){
                applicationProcessing.organisationRenameRole = false;
                removeQueuedNotification();
                showSuccessNotification("Handling fuldført", result.message)
            }, 100)
        }
    })
    modal.open()
}




async function organisationDeleteRole(btn) {
    btn = $(btn);
    let role = btn.attr("data-role")
    if(empty(role)) return;
    if('organisationDeleteRole' in applicationProcessing && applicationProcessing.organisationDeleteRole) return false;

    SweetPrompt.confirm(
        'Slet rolle?',
        `Er du sikker på, at du vil slette rollen "${role}"?`,
        {
            confirmButtonText: 'Ja, slet',
            onConfirm: async () => {
                applicationProcessing.organisationDeleteRole = true;
                btn.get(0).disabled = true;

                let result = await del(platformLinks.api.organisation.team.role.delete, {role});

                if(result.status === "error") {
                    applicationProcessing.organisationDeleteRole = false;
                    btn.get(0).disabled = false;
                    showErrorNotification("Kan ikke slette rollen", result.error.message)
                    return { status: 'error', error: result.error.message };
                }

                queueNotificationOnLoad("Handling fuldført", result.message, 'success')
                handleStandardApiRedirect(result, 1)

                setTimeout(function (){
                    applicationProcessing.organisationDeleteRole = false;
                    btn.get(0).disabled = false;
                    removeQueuedNotification();
                    showSuccessNotification("Handling fuldført", result.message)
                }, 100)

                return { status: 'success', message: result.message };
            }
        }
    )
}



async function editRolePermissions(btn) {
    if('editRolePermissions' in applicationProcessing && applicationProcessing.editRolePermissions) return false;
    let select = $(document).find("select#role_permissions").first();
    if(empty(select)) return;
    let role = select.val()
    if(role === "owner") return;
    let viewId = select.attr("name")
    let parent = select.parents(`[data-switchParent][data-switch-id=${viewId}]`).first();
    let form = parent.find(`form.switchViewObject[data-switch-id=${viewId}][data-switch-object-name=${role}]`).first()
    if(empty(form)) return;
    applicationProcessing.editRolePermissions = true;
    btn.disabled = true


    let formData = new FormData(form.get(0))
    formData.append("role", role);
    let result = await post(platformLinks.api.organisation.team.role.permissions, formData);
    if(result.status === "error") {
        applicationProcessing.editRolePermissions = false;
        showErrorNotification("Der opstod en fejl",result.error.message)
        btn.disabled = false
        return;
    }

    queueNotificationOnLoad("Handling fuldført", result.message, 'success')
    handleStandardApiRedirect(result, 1)

    setTimeout(function (){
        applicationProcessing.editRolePermissions = false;
        btn.disabled = false;
        removeQueuedNotification();
        showSuccessNotification("Handling fuldført", result.message)
    }, 100)
}


/**
 * Organisation Members Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const OrganisationMembersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let filterRole = 'hide_location_employees';
    let filterStatus = 'Active_Pending';
    let sortColumn = 'created_at';
    let sortDirection = 'DESC';
    let isLoading = false;
    let totalItems = 0;
    let totalPagesCount = 1;

    // DOM elements
    let $tbody, $search, $filterRole, $filterStatus, $sort, $perPage;
    let $showing, $total, $currentPageEl, $totalPages, $pagination, $noResults, $paginationContainer;

    function init() {
        // Get DOM elements
        $tbody = $('#org-team-members-tbody');
        $search = $('#org-team-search');
        $filterRole = $('#org-team-filter-role');
        $filterStatus = $('#org-team-filter-status');
        $sort = $('#org-team-sort');
        $perPage = $('#org-team-per-page');
        $showing = $('#org-team-showing');
        $total = $('#org-team-total');
        $currentPageEl = $('#org-team-current-page');
        $totalPages = $('#org-team-total-pages');
        $pagination = $('#org-team-pagination');
        $noResults = $('#org-team-no-results');
        $paginationContainer = $('#org-team-pagination-container');

        if (!$tbody.length || typeof organisationMembersApiUrl === 'undefined') return;

        // Bind events
        bindEvents();

        // Initial load
        fetchMembers();
    }

    function bindEvents() {
        // Search with debounce
        let searchTimeout;
        $search.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                searchTerm = $search.val().trim();
                currentPage = 1;
                fetchMembers();
            }, 400);
        });

        // Filter by role
        $filterRole.on('change', function() {
            const val = $(this).val();
            filterRole = val === 'all' ? '' : val;
            currentPage = 1;
            fetchMembers();
        });

        // Filter by status
        $filterStatus.on('change', function() {
            const val = $(this).val();
            filterStatus = val === 'all' ? '' : val;
            currentPage = 1;
            fetchMembers();
        });

        // Sort
        $sort.on('change', function() {
            const val = $(this).val().split('-');
            sortColumn = val[0];
            sortDirection = val[1].toUpperCase();
            fetchMembers();
        });

        // Per page
        $perPage.on('change', function() {
            perPage = parseInt($(this).val());
            currentPage = 1;
            fetchMembers();
        });
    }

    async function fetchMembers() {
        if (isLoading) return;
        isLoading = true;

        // Show loading state
        showLoading();

        try {
            const params = {
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (filterRole) params.filter_role = filterRole;
            if (filterStatus) params.filter_status = filterStatus;

            const result = await post(organisationMembersApiUrl, params);

            if (result.status === 'error') {
                showError(result.error?.message || 'Der opstod en fejl');
                return;
            }

            // Update state from response
            const data = result.data;
            totalItems = data.pagination.total;
            totalPagesCount = data.pagination.totalPages;
            currentPage = data.pagination.page;

            // Update roles if provided
            if (data.roles) {
                organisationRoles = data.roles;
            }

            // Render members
            renderMembers(data.members);
            renderPaginationInfo();
            renderPagination();

        } catch (error) {
            console.error('Error fetching members:', error);
            showError('Der opstod en netværksfejl');
        } finally {
            isLoading = false;
        }
    }

    function showLoading() {
        $tbody.html(`
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
        `);
        $noResults.addClass('d-none');
    }

    function showError(message) {
        $tbody.html(`
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <i class="mdi mdi-alert-circle font-40 color-red"></i>
                        <p class="color-red mt-2 mb-0">${message}</p>
                        <button class="btn-v2 mute-btn mt-2" onclick="OrganisationMembersPagination.refresh()">Prøv igen</button>
                    </div>
                </td>
            </tr>
        `);
    }

    function renderMembers(members) {
        if (!members || members.length === 0) {
            $tbody.empty();
            $noResults.removeClass('d-none');
            $paginationContainer.addClass('d-none');
            return;
        }

        $noResults.addClass('d-none');
        $paginationContainer.removeClass('d-none');

        let html = '';
        members.forEach(function(member) {
            html += renderMemberRow(member);
        });

        $tbody.html(html);
        selectV2();
    }

    function renderMemberRow(member) {
        const actionMenuHtml = renderActionMenu(member);

        return `
            <tr class="org-team-member-row"
                data-name="${(member.name || '').toLowerCase()}"
                data-email="${(member.email || '').toLowerCase()}"
                data-role="${member.role}"
                data-status="${member.show_status}">
                <td class="font-weight-bold">
                    <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                        <div class="flex-row-center flex-align-center square-30 bg-primary-cta border-radius-50">
                            <span class="text-sm text-uppercase color-white">
                                ${member.initials || ''}
                            </span>
                        </div>
                        <p class="font-weight-medium mb-0 text-sm">${member.name_truncated || ''}</p>
                    </div>
                </td>
                <td class="text-sm color-gray">${member.email || ''}</td>
                <td>
                    <select class="form-select-v2 w-100" name="role" data-member-uuid="${member.member_uuid}">
                        ${renderRoleOptions(member.role)}
                    </select>
                </td>
                <td><span class="${member.status_box}-lg">${member.show_status}</span></td>
                <td class="text-right">
                    <div class="flex-row-end">
                        ${actionMenuHtml}
                    </div>
                </td>
            </tr>
        `;
    }

    function renderRoleOptions(selectedRole) {
        let html = '';
        for (const [role, title] of Object.entries(organisationRoles)) {
            const selected = role === selectedRole ? 'selected' : '';
            html += `<option value="${role}" ${selected}>${title}</option>`;
        }
        return html;
    }

    function renderActionMenu(member) {
        if (!member.action_menu || member.action_menu.length === 0) {
            return '';
        }

        const lowRiskItems = member.action_menu.filter(item => item.risk === 'low');
        const highRiskItems = member.action_menu.filter(item => item.risk === 'high');

        let lowRiskHtml = '';
        lowRiskItems.forEach(item => {
            lowRiskHtml += `
                <a href="javascript:void(0);" class="list-item" onclick="teamMemberAction(this)"
                   data-uuid="${member.member_uuid}" data-team-action="${item.action}">
                    <i class="${item.icon}"></i>
                    <span>${item.title}</span>
                </a>
            `;
        });

        let highRiskHtml = '';
        highRiskItems.forEach(item => {
            highRiskHtml += `
                <a href="javascript:void(0);" class="list-item color-red" onclick="teamMemberAction(this)"
                   data-uuid="${member.member_uuid}" data-team-action="${item.action}">
                    <i class="${item.icon}"></i>
                    <span>${item.title}</span>
                </a>
            `;
        });

        return `
            <div class="dropdown nav-item-v2 p-0 pr-2">
                <a class="color-primary-dark dropdown-no-arrow dropdown-toggle nav-button font-20 font-weight-bold noSelect"
                   href="javascript:void(0);" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis font-20 font-weight-bold noSelect"></i>
                </a>
                <div class="dropdown-menu section-dropdown">
                    <div class="account-body">
                        <p class="list-title"><span>Handlinger</span></p>
                        ${lowRiskHtml}
                    </div>
                    ${highRiskHtml ? `<div class="account-footer">${highRiskHtml}</div>` : ''}
                </div>
            </div>
        `;
    }

    function renderPaginationInfo() {
        const startItem = totalItems === 0 ? 0 : (currentPage - 1) * perPage + 1;
        const endItem = Math.min(currentPage * perPage, totalItems);

        $showing.text(totalItems === 0 ? 0 : `${startItem}-${endItem}`);
        $total.text(totalItems);
        $currentPageEl.text(currentPage);
        $totalPages.text(totalPagesCount);
    }

    function renderPagination() {
        $pagination.empty();

        if (totalPagesCount <= 1) {
            return;
        }

        // Previous button
        const prevDisabled = currentPage === 1;
        $pagination.append(`
            <button class="pagination-btn ${prevDisabled ? 'disabled' : ''}"
                    ${prevDisabled ? 'disabled' : ''}
                    data-page="${currentPage - 1}">
                <i class="mdi mdi-chevron-left"></i>
            </button>
        `);

        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPagesCount, startPage + maxVisiblePages - 1);

        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // First page + ellipsis
        if (startPage > 1) {
            $pagination.append(`<button class="pagination-btn" data-page="1">1</button>`);
            if (startPage > 2) {
                $pagination.append(`<span class="pagination-ellipsis">...</span>`);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === currentPage;
            $pagination.append(`
                <button class="pagination-btn ${isActive ? 'active' : ''}" data-page="${i}">${i}</button>
            `);
        }

        // Last page + ellipsis
        if (endPage < totalPagesCount) {
            if (endPage < totalPagesCount - 1) {
                $pagination.append(`<span class="pagination-ellipsis">...</span>`);
            }
            $pagination.append(`<button class="pagination-btn" data-page="${totalPagesCount}">${totalPagesCount}</button>`);
        }

        // Next button
        const nextDisabled = currentPage === totalPagesCount;
        $pagination.append(`
            <button class="pagination-btn ${nextDisabled ? 'disabled' : ''}"
                    ${nextDisabled ? 'disabled' : ''}
                    data-page="${currentPage + 1}">
                <i class="mdi mdi-chevron-right"></i>
            </button>
        `);

        // Bind click events
        $pagination.find('.pagination-btn:not(.disabled)').on('click', function() {
            const page = parseInt($(this).data('page'));
            if (page !== currentPage && !isLoading) {
                currentPage = page;
                fetchMembers();
                // Scroll to top of table
                $('html, body').animate({
                    scrollTop: $('#org-team-members').offset().top - 100
                }, 200);
            }
        });
    }

    // Public API
    return {
        init: init,
        refresh: fetchMembers
    };
})();

// Initialize on document ready
$(document).ready(function() {
    OrganisationMembersPagination.init();
});

