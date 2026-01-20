/**
 * Admin Panel - Policy Editor JavaScript
 * Rich HTML editor for policy management
 */

var currentPolicy = null;
var hasUnsavedChanges = false;

/**
 * Initialize the editor on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Load current policy data
    loadPolicy();

    // Set up change tracking
    var titleInput = document.getElementById('policy-title');
    var contentEditor = document.getElementById('policy-content');

    if (titleInput) {
        titleInput.addEventListener('input', function() {
            setUnsavedChanges(true);
        });
    }

    if (contentEditor) {
        contentEditor.addEventListener('input', function() {
            setUnsavedChanges(true);
        });

        // Handle paste - clean HTML
        contentEditor.addEventListener('paste', function(e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/html');
            if (!text) {
                text = (e.clipboardData || window.clipboardData).getData('text/plain');
            }
            // Clean the pasted content
            text = cleanPastedContent(text);
            document.execCommand('insertHTML', false, text);
        });
    }

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

/**
 * Clean pasted content - remove unwanted styles but keep structure
 */
function cleanPastedContent(html) {
    // Create a temporary element
    var temp = document.createElement('div');
    temp.innerHTML = html;

    // Remove style attributes
    var elements = temp.querySelectorAll('[style]');
    elements.forEach(function(el) {
        el.removeAttribute('style');
    });

    // Remove class attributes
    elements = temp.querySelectorAll('[class]');
    elements.forEach(function(el) {
        el.removeAttribute('class');
    });

    return temp.innerHTML;
}

/**
 * Load policy data from server
 */
async function loadPolicy() {
    var response = await post(platformLinks.api.admin.policies.get, {
        type: policyType
    });

    if (response.status === 'success') {
        currentPolicy = response.data.policy;
        populateEditor(currentPolicy);
        updateStatusUI(currentPolicy);
    } else {
        showErrorNotification('Fejl', response.message || 'Kunne ikke indlæse politik');
    }
}

/**
 * Populate editor with policy data
 */
function populateEditor(policy) {
    var titleInput = document.getElementById('policy-title');
    var contentEditor = document.getElementById('policy-content');

    if (titleInput && policy) {
        titleInput.value = policy.title || '';
    }

    if (contentEditor && policy) {
        contentEditor.innerHTML = policy.content || '';
    }

    setUnsavedChanges(false);
}

/**
 * Set unsaved changes state and update UI accordingly
 */
function setUnsavedChanges(hasChanges) {
    hasUnsavedChanges = hasChanges;

    var saveBtn = document.getElementById('save-btn');
    var publishBtn = document.getElementById('publish-btn');

    if (saveBtn) {
        saveBtn.style.display = hasChanges ? 'inline-block' : 'none';
    }

    if (publishBtn) {
        // Disable publish if there are unsaved changes
        if (hasChanges) {
            publishBtn.disabled = true;
            publishBtn.title = 'Gem kladde før publicering';
        } else {
            publishBtn.disabled = false;
            publishBtn.title = '';
        }
    }
}

/**
 * Update UI based on policy status
 */
function updateStatusUI(policy) {
    var statusBadge = document.getElementById('policy-status-badge');
    var versionInfo = document.getElementById('policy-version-info');
    var deleteBtn = document.getElementById('delete-btn');
    var viewLiveBtn = document.getElementById('view-live-btn');
    var scheduledWarning = document.getElementById('scheduled-warning');

    // Set up view live button (always show since URL exists)
    if (viewLiveBtn && typeof policyLiveUrls !== 'undefined' && policyLiveUrls[policyType]) {
        viewLiveBtn.href = policyLiveUrls[policyType];
        viewLiveBtn.style.display = 'inline-block';
    }

    // Hide scheduled warning by default
    if (scheduledWarning) {
        scheduledWarning.style.display = 'none';
    }

    if (!policy) {
        statusBadge.className = 'mute-box font-10 ml-2';
        statusBadge.textContent = 'Ny';
        versionInfo.textContent = 'Ingen tidligere versioner';
        if (deleteBtn) deleteBtn.style.display = 'none';
        return;
    }

    // Delete button - show for drafts only (including scheduled)
    if (deleteBtn) {
        deleteBtn.style.display = (policy.status === 'draft') ? 'inline-block' : 'none';
    }

    // Check if scheduled
    var isScheduled = policy.is_scheduled && policy.scheduled_at;

    // Status badge
    if (isScheduled) {
        statusBadge.className = 'action-box font-10 ml-2';
        statusBadge.textContent = 'Planlagt';
    } else {
        switch (policy.status) {
            case 'draft':
                statusBadge.className = 'warning-box font-10 ml-2';
                statusBadge.textContent = 'Kladde';
                break;
            case 'published':
                statusBadge.className = 'success-box font-10 ml-2';
                statusBadge.textContent = 'Publiceret';
                break;
            case 'archived':
                statusBadge.className = 'mute-box font-10 ml-2';
                statusBadge.textContent = 'Arkiveret';
                break;
            default:
                statusBadge.className = 'mute-box font-10 ml-2';
                statusBadge.textContent = policy.status;
        }
    }

    // Version info
    var versionText = 'Version ' + policy.version;
    if (isScheduled) {
        versionText += ' · Planlagt til ' + formatDate(policy.scheduled_at);
    } else if (policy.status === 'published' && policy.published_at) {
        versionText += ' · Publiceret ' + formatDate(policy.published_at);
    } else if (policy.status === 'draft') {
        versionText += ' · Kladde';
        if (policy.updated_at) {
            versionText += ' · Sidst gemt ' + formatDate(policy.updated_at);
        }
    }
    versionInfo.textContent = versionText;

    // Show scheduled warning if applicable
    if (scheduledWarning && isScheduled) {
        var scheduledDate = new Date(policy.scheduled_at);
        var formattedDate = scheduledDate.toLocaleDateString('da-DK', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        scheduledWarning.innerHTML = '<i class="mdi mdi-clock-outline mr-1"></i> Denne version er planlagt til publicering <strong>' + formattedDate + '</strong>. Hvis du gemmer ændringer, vil planlægningen blive annulleret.';
        scheduledWarning.style.display = 'block';
    }
}

/**
 * Format date for display
 */
function formatDate(dateString) {
    var date = new Date(dateString);
    var day = date.getDate();
    var month = date.getMonth() + 1;
    var year = date.getFullYear();
    var hours = String(date.getHours()).padStart(2, '0');
    var minutes = String(date.getMinutes()).padStart(2, '0');
    return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
}

/**
 * Execute formatting command
 */
function execCmd(command, value) {
    document.execCommand(command, false, value || null);
    document.getElementById('policy-content').focus();
}

/**
 * Insert a link
 */
function insertLink() {
    var url = prompt('Indtast URL:', 'https://');
    if (url) {
        document.execCommand('createLink', false, url);
    }
}

/**
 * Insert a table
 */
function insertTable() {
    var rows = prompt('Antal rækker:', '3');
    var cols = prompt('Antal kolonner:', '3');

    if (rows && cols) {
        rows = parseInt(rows);
        cols = parseInt(cols);

        var table = '<table><thead><tr>';
        for (var c = 0; c < cols; c++) {
            table += '<th>Overskrift ' + (c + 1) + '</th>';
        }
        table += '</tr></thead><tbody>';
        for (var r = 0; r < rows - 1; r++) {
            table += '<tr>';
            for (var c = 0; c < cols; c++) {
                table += '<td>Celle</td>';
            }
            table += '</tr>';
        }
        table += '</tbody></table>';

        document.execCommand('insertHTML', false, table);
    }
}

/**
 * Save as draft
 */
async function saveDraft() {
    var title = document.getElementById('policy-title').value.trim();
    var content = document.getElementById('policy-content').innerHTML;

    if (!title) {
        showErrorNotification('Fejl', 'Indtast en titel');
        return;
    }

    if (!content || content === '<br>' || content === '<p><br></p>') {
        showErrorNotification('Fejl', 'Indtast indhold');
        return;
    }

    var data = {
        type: policyType,
        title: title,
        content: content
    };

    if (currentPolicy && currentPolicy.uid) {
        data.uid = currentPolicy.uid;
    }

    var response = await post(platformLinks.api.admin.policies.save, data);

    if (response.status === 'success') {
        currentPolicy = response.data.policy;
        setUnsavedChanges(false);
        updateStatusUI(currentPolicy);
        showSuccessNotification('Gemt', 'Kladde gemt');
    } else {
        showErrorNotification('Fejl', response.message || 'Kunne ikke gemme');
    }
}

/**
 * Open publish modal
 */
function openPublishModal() {
    // Check for unsaved changes
    if (hasUnsavedChanges) {
        showErrorNotification('Fejl', 'Gem kladden før du publicerer');
        return;
    }

    // Reset form
    document.getElementById('publish-starts-at').value = '';
    document.getElementById('notify-users').checked = false;
    document.querySelectorAll('.recipient-type').forEach(function(cb) {
        cb.checked = false;
    });
    document.getElementById('recipient-types-container').style.display = 'none';

    $('#publishModal').modal('show');
}

/**
 * Toggle recipient type checkboxes
 */
function toggleRecipientTypes() {
    var container = document.getElementById('recipient-types-container');
    var checkbox = document.getElementById('notify-users');
    container.style.display = checkbox.checked ? 'block' : 'none';
}

/**
 * Publish policy
 */
async function publishPolicy() {
    // Double-check we have a saved policy
    if (!currentPolicy || !currentPolicy.uid) {
        showErrorNotification('Fejl', 'Gem kladden først');
        return;
    }

    // Gather publish options
    var startsAt = document.getElementById('publish-starts-at').value;
    var notifyUsers = document.getElementById('notify-users').checked;

    var recipientTypes = [];
    if (notifyUsers) {
        document.querySelectorAll('.recipient-type:checked').forEach(function(cb) {
            recipientTypes.push(cb.value);
        });
    }

    // Publish
    var publishData = {
        uid: currentPolicy.uid,
        notify: notifyUsers,
        recipient_types: recipientTypes
    };

    if (startsAt) {
        publishData.starts_at = startsAt;
    }

    // Show loading spinner on button
    var publishBtn = document.getElementById('modal-publish-btn');
    var originalText = publishBtn.innerHTML;
    publishBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status"></span> Publicerer...';
    publishBtn.disabled = true;

    var response = await post(platformLinks.api.admin.policies.publish, publishData);

    // Reset button state
    publishBtn.innerHTML = originalText;
    publishBtn.disabled = false;

    if (response.status === 'success') {
        $('#publishModal').modal('hide');

        if (startsAt) {
            showSuccessNotification('Planlagt', 'Politik planlagt til publicering');
        } else {
            showSuccessNotification('Publiceret', 'Politik publiceret');
        }

        // Refresh page after short delay to show success message
        setTimeout(function() {
            window.location.reload();
        }, 1000);
    } else {
        showErrorNotification('Fejl', response.message || 'Kunne ikke publicere');
    }
}

/**
 * Delete draft
 */
function deletePolicy() {
    if (!currentPolicy || !currentPolicy.uid) {
        showErrorNotification('Fejl', 'Ingen kladde at slette');
        return;
    }

    if (currentPolicy.status !== 'draft') {
        showErrorNotification('Fejl', 'Kun kladder kan slettes');
        return;
    }

    SweetPrompt.confirm('Slet kladde?', 'Er du sikker på at du vil slette denne kladde? Handlingen kan ikke fortrydes.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            var response = await post(platformLinks.api.admin.policies.delete, {
                uid: currentPolicy.uid
            });

            if (response.status === 'success') {
                setUnsavedChanges(false);
                showSuccessNotification('Slettet', 'Kladde slettet');

                // Reload to get the published version or blank state
                loadPolicy();
            } else {
                showErrorNotification('Fejl', response.message || 'Kunne ikke slette');
            }
        }
    });
}

/**
 * Load version history
 */
async function loadVersionHistory() {
    var historyContent = document.getElementById('history-content');
    historyContent.innerHTML = '<div class="flex-col-center flex-align-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 mb-0 color-gray">Indlæser historik...</p></div>';

    $('#historyModal').modal('show');

    var response = await post(platformLinks.api.admin.policies.versions, {
        type: policyType
    });

    if (response.status === 'success') {
        renderVersionHistory(response.data.versions || []);
    } else {
        historyContent.innerHTML = '<div class="text-center py-4"><p class="color-gray">Kunne ikke indlæse historik</p></div>';
    }
}

/**
 * Render version history list
 */
function renderVersionHistory(versions) {
    var historyContent = document.getElementById('history-content');

    if (versions.length === 0) {
        historyContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-history font-40 color-gray"></i><p class="mt-2 color-gray">Ingen versionshistorik endnu</p></div>';
        return;
    }

    var html = '<div class="flex-col-start" style="gap: 0.75rem;">';
    versions.forEach(function(version) {
        var changeTypeLabel = getChangeTypeLabel(version.change_type);
        var date = formatDate(version.created_at);
        var changedBy = version.changed_by_name || 'System';

        html += '<div class="card">';
        html += '<div class="card-body py-3">';
        html += '<div class="flex-row-between flex-align-center">';
        html += '<div>';
        html += '<span class="' + changeTypeLabel.class + ' mr-2">' + changeTypeLabel.text + '</span>';
        html += '<strong>Version ' + version.version_snapshot + '</strong>';
        html += '<span class="color-gray font-14 ml-2">' + version.title_snapshot + '</span>';
        html += '</div>';
        html += '<div class="text-right">';
        html += '<span class="font-12 color-gray">' + date + '</span>';
        html += '<span class="font-12 color-gray d-block">' + changedBy + '</span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="mt-2">';
        html += '<button type="button" class="btn-v2 mute-btn" onclick="viewChangeLog(\'' + version.uid + '\')"><i class="mdi mdi-eye mr-1"></i> Se indhold</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
    });
    html += '</div>';

    historyContent.innerHTML = html;
}

/**
 * Get change type label
 */
function getChangeTypeLabel(type) {
    switch (type) {
        case 'created':
            return { text: 'Oprettet', class: 'action-box' };
        case 'updated':
            return { text: 'Opdateret', class: 'mute-box' };
        case 'published':
            return { text: 'Publiceret', class: 'success-box' };
        case 'archived':
            return { text: 'Arkiveret', class: 'warning-box' };
        case 'scheduled':
            return { text: 'Planlagt', class: 'action-box' };
        case 'unscheduled':
            return { text: 'Afplanlagt', class: 'warning-box' };
        default:
            return { text: type, class: 'mute-box' };
    }
}

/**
 * View a specific changelog entry (snapshot)
 */
async function viewChangeLog(changeLogUid) {
    var response = await post(platformLinks.api.admin.policies.versions, {
        type: policyType,
        changelog_uid: changeLogUid
    });

    if (response.status === 'success' && response.data.changelog) {
        var log = response.data.changelog;
        var resolvedTitle = resolvePlaceholders(log.title_snapshot);
        var resolvedContent = resolvePlaceholders(log.content_snapshot);

        document.getElementById('view-version-title').textContent = 'Version ' + log.version_snapshot + ' - ' + resolvedTitle;
        document.getElementById('view-version-content').innerHTML = resolvedContent;

        $('#historyModal').modal('hide');
        $('#viewVersionModal').modal('show');
    } else {
        showErrorNotification('Fejl', 'Kunne ikke indlæse version');
    }
}

/**
 * Preview current editor content with placeholders resolved (shows in modal)
 */
function previewPolicy() {
    var title = document.getElementById('policy-title').value;
    var content = document.getElementById('policy-content').innerHTML;

    if (!title && !content) {
        showErrorNotification('Fejl', 'Der er intet at forhåndsvise');
        return;
    }

    // Resolve placeholders client-side
    var renderedContent = resolvePlaceholders(content);
    var renderedTitle = resolvePlaceholders(title);

    var previewHtml = '';
    if (renderedTitle) {
        previewHtml += '<h1>' + escapeHtml(renderedTitle) + '</h1>';
    }
    previewHtml += renderedContent;

    document.getElementById('preview-content').innerHTML = previewHtml;
    $('#previewModal').modal('show');
}

/**
 * Resolve placeholders in content
 */
function resolvePlaceholders(content) {
    if (!content || typeof policyPlaceholders === 'undefined') {
        return content;
    }

    for (var placeholder in policyPlaceholders) {
        content = content.split(placeholder).join(policyPlaceholders[placeholder]);
    }

    return content;
}

/**
 * Escape HTML for safe display
 */
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
