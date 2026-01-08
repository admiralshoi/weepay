

async function teamInviteModal() {
    let modal = new ModalHandler('locationTeamInvite')
    modal.construct({roles: locationRoles, organisation_members: organisationMembers})
    await modal.build()
        .then(() => {
            selectV2();

            // Handle user type selection
            let userTypeSelect = $('#user-type-select');
            let newUserFields = $('#new-user-fields');
            let existingUserField = $('#existing-user-field');

            userTypeSelect.on('change', function() {
                if($(this).val() === 'new') {
                    newUserFields.removeClass('d-none');
                    existingUserField.addClass('d-none');
                } else {
                    newUserFields.addClass('d-none');
                    existingUserField.removeClass('d-none');
                }
            });
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        invite: async (btn, modalHandler) => {
            let parent = btn.parents('#location-team-invite').first()
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
            screenLoader.show("Tilføjer medarbejder...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.invite, formData);

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
                showSuccessNotification(result.message)
                modalHandler.close()

                // Refresh the members table instead of reloading the page
                if(typeof TeamMembersPagination !== 'undefined' && TeamMembersPagination.refresh) {
                    TeamMembersPagination.refresh();
                } else {
                    handleStandardApiRedirect(result, 1)
                }
            }
        }
    })
    modal.open()
}


function showCredentialsModal(data) {
    // Close the invite modal first
    $('#location-team-invite').modal('hide')

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
            notifyTopCorner("Kopieret!")
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
        notifyTopCorner("Kopieret!")
        setTimeout(() => {
            $btn.html(originalHtml)
        }, 2000)
    }
}

function closeCredentialsAndRefresh() {
    $('#team-credentials-modal').modal('hide')
    setTimeout(() => {
        // Refresh the members table instead of reloading the page
        if(typeof TeamMembersPagination !== 'undefined' && TeamMembersPagination.refresh) {
            TeamMembersPagination.refresh();
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

    screenLoader.show("Opdaterer medarbejder...")
    let result = await post(platformLinks.api.locations.team.update, {action, role, member_uuid: memberUuid, location_uid: currentLocation.uid});

    if(result.status === "error") {
        screenLoader.hide()
        showErrorNotification(result.error.message)
        return false;
    }

    screenLoader.hide()
    showSuccessNotification(result.message)

    // Refresh the members table instead of reloading the page
    if(typeof TeamMembersPagination !== 'undefined' && TeamMembersPagination.refresh) {
        TeamMembersPagination.refresh();
    } else {
        // Fallback to page reload if pagination module not available
        handleStandardApiRedirect(result)
    }
}

async function locationCreateRole() {
    let modal = new ModalHandler('locationCreateRole')
    modal.construct({})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        create: async (btn, modalHandler) => {
            let parent = btn.parents('#location-new-role').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();

            const clearError = () => {
                if(errorBox.length) {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
            }
            const setError = (txt) => {
                if(errorBox.length) {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                }
                btn.removeAttr('disabled')
            }

            btn.attr('disabled', 'disabled')
            clearError()
            screenLoader.show("Opretter rolle...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.role.create, formData);

            if(result.status === "error") {
                btn.removeAttr('disabled')
                screenLoader.hide()
                setError(result.error.message)
                return false;
            }

            screenLoader.hide()
            queueNotificationOnLoad("Udført", result.message, 'success')
            handleStandardApiRedirect(result)
        }
    })
    modal.open()
}

async function locationRenameRole(btn) {
    if('locationRenameRole' in applicationProcessing && applicationProcessing.locationRenameRole) return false;

    let role = $(btn).attr('data-role')
    let modal = new ModalHandler('locationRenameRole')
    modal.construct({role: role, role_name: Translate.word(role)})
    await modal.build()
        .then(() => {
            selectV2();
        })
    modal.bindEvents({
        onclose: (modalHandler) => {
            modalHandler.dispose();
        },
        rename: async (btn, modalHandler) => {
            if('locationRenameRole' in applicationProcessing && applicationProcessing.locationRenameRole) return false;

            let parent = btn.parents('#location-rename-role').first()
            let form = parent.find("form").first();
            if (empty(form)) return;
            let errorBox = parent.find(".modalErrorBox").first();

            const clearError = () => {
                if(errorBox.length) {
                    errorBox.text('')
                    errorBox.addClass('d-none')
                }
            }
            const setError = (txt) => {
                if(errorBox.length) {
                    errorBox.text(txt)
                    errorBox.removeClass('d-none')
                }
                applicationProcessing.locationRenameRole = false;
                btn.removeAttr('disabled')
            }

            applicationProcessing.locationRenameRole = true;
            btn.attr('disabled', 'disabled')
            clearError()
            screenLoader.show("Omdøber rolle...")

            let formData = new FormData(form.get(0))
            formData.append('location_uid', currentLocation.uid)

            let result = await post(platformLinks.api.locations.team.role.rename, formData);

            if(result.status === "error") {
                applicationProcessing.locationRenameRole = false;
                btn.removeAttr('disabled')
                screenLoader.hide()
                setError(result.error.message)
                return false;
            }

            screenLoader.hide()
            queueNotificationOnLoad("Udført", result.message, 'success')
            handleStandardApiRedirect(result)
        }
    })
    modal.open()
}

async function locationDeleteRole(btn) {
    let $btn = $(btn)
    let role = $btn.attr('data-role')

    SweetPrompt.confirm(
        'Slet rolle?',
        `Er du sikker på, at du vil slette rollen "${role}"?`,
        {
            confirmButtonText: 'Ja, slet',
            onConfirm: async () => {
                $btn.attr('disabled', 'disabled')

                let result = await del(platformLinks.api.locations.team.role.delete, {role: role, location_uid: currentLocation.uid});

                if(result.status === "error") {
                    $btn.removeAttr('disabled')
                    showErrorNotification("Kan ikke slette rollen", result.error.message)
                    return { status: 'error', error: result.error.message };
                }

                queueNotificationOnLoad("Udført", result.message, 'success')
                handleStandardApiRedirect(result)
                return { status: 'success', message: result.message };
            }
        }
    )
}

async function locationEditRolePermissions(btn) {
    let $btn = $(btn)
    let select = $(document).find("select#role_permissions").first();
    if(empty(select)) return;
    let role = select.val()
    if(role === "owner") return;
    let viewId = select.attr("name")
    let parent = select.parents(`[data-switchParent][data-switch-id=${viewId}]`).first();
    let form = parent.find(`form.switchViewObject[data-switch-id=${viewId}][data-switch-object-name=${role}]`).first()
    if(empty(form)) return;

    $btn.attr('disabled', 'disabled')
    screenLoader.show("Gemmer tilladelser...")

    let formData = new FormData(form.get(0))
    formData.append('location_uid', currentLocation.uid)
    formData.append('role', role)

    let result = await post(platformLinks.api.locations.team.role.permissions, formData);

    if(result.status === "error") {
        $btn.removeAttr('disabled')
        screenLoader.hide()
        showErrorNotification(result.error.message)
        return false;
    }

    $btn.removeAttr('disabled')
    screenLoader.hide()
    showSuccessNotification(result.message)
    // Don't redirect for permissions, just show success
}


/**
 * Team Members Table - Backend Pagination, Search, Filter, and Sort
 * Uses server-side pagination for scalability
 */
const TeamMembersPagination = (function() {
    // State
    let currentPage = 1;
    let perPage = 10;
    let searchTerm = '';
    let filterRole = '';
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
        $tbody = $('#team-members-tbody');
        $search = $('#team-search');
        $filterRole = $('#team-filter-role');
        $filterStatus = $('#team-filter-status');
        $sort = $('#team-sort');
        $perPage = $('#team-per-page');
        $showing = $('#team-showing');
        $total = $('#team-total');
        $currentPageEl = $('#team-current-page');
        $totalPages = $('#team-total-pages');
        $pagination = $('#team-pagination');
        $noResults = $('#team-no-results');
        $paginationContainer = $('#team-pagination-container');

        if (!$tbody.length || typeof currentLocation === 'undefined') return;

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
                location_uid: currentLocation.uid,
                page: currentPage,
                per_page: perPage,
                sort_column: sortColumn,
                sort_direction: sortDirection,
            };

            if (searchTerm) params.search = searchTerm;
            if (filterRole) params.filter_role = filterRole;
            if (filterStatus) params.filter_status = filterStatus;

            const result = await post(locationMembersApiUrl, params);

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
                locationRoles = data.roles;
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
            <tr id="team-loading-row">
                <td colspan="5" class="text-center py-4">
                    <div class="flex-col-center flex-align-center">
                        <span class="spinner-border color-primary-cta square-30" role="status" style="border-width: 3px;">
                            <span class="sr-only">Indlæser...</span>
                        </span>
                        <p class="color-gray mt-2 mb-0">Indlæser medarbejdere...</p>
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
                        <button class="btn-v2 mute-btn mt-2" onclick="TeamMembersPagination.refresh()">Prøv igen</button>
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
            <tr class="team-member-row"
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
        for (const [role, title] of Object.entries(locationRoles)) {
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
                    scrollTop: $('#team-members').offset().top - 100
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
    TeamMembersPagination.init();
});
