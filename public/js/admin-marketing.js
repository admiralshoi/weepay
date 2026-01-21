/**
 * Admin Marketing Templates & Inspiration Management
 */

// Tab switching
function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        btn.classList.remove('active');
        if (btn.dataset.tab === tabName) {
            btn.classList.add('active');
        }
    });

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(function(content) {
        content.style.display = 'none';
    });
    var targetTab = document.getElementById('tab-' + tabName);
    if (targetTab) {
        targetTab.style.display = 'block';
    }
}

// Template functions
function openUploadModal() {
    $('#uploadForm')[0].reset();
    $('#uploadModal').modal('show');
}

function uploadTemplate() {
    var fileInput = document.getElementById('templateFile');
    var name = document.getElementById('templateName').value.trim();
    var type = document.getElementById('templateType').value;
    var description = document.getElementById('templateDescription').value.trim();

    if (!fileInput.files.length) {
        showErrorNotification('Fejl', 'Vælg venligst en PDF fil');
        return;
    }

    if (!name) {
        showErrorNotification('Fejl', 'Indtast venligst et navn');
        return;
    }

    var formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('name', name);
    formData.append('type', type);
    formData.append('description', description);
    formData.append('_csrf', _csrf);

    // Show loading state
    var btn = document.getElementById('uploadBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    fetch(HOST + 'api/admin/marketing/templates/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Template uploadet', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke uploade template');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl under upload');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function editTemplate(uid, name, type, description, status) {
    document.getElementById('editUid').value = uid;
    document.getElementById('editName').value = name;
    document.getElementById('editType').value = type;
    document.getElementById('editDescription').value = description;
    document.getElementById('editStatus').value = status;
    $('#editModal').modal('show');
}

function saveTemplateChanges() {
    var uid = document.getElementById('editUid').value;
    var name = document.getElementById('editName').value.trim();
    var type = document.getElementById('editType').value;
    var description = document.getElementById('editDescription').value.trim();
    var status = document.getElementById('editStatus').value;

    if (!name) {
        showErrorNotification('Fejl', 'Indtast venligst et navn');
        return;
    }

    var btn = document.getElementById('saveBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    post('api/admin/marketing/templates/update', {
        uid: uid,
        name: name,
        type: type,
        description: description,
        status: status
    }).then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Template opdateret', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke opdatere template');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    }).catch(error => {
        console.error('Update error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function updateTemplateStatus(uid, status) {
    var actionText = status === 'ACTIVE' ? 'aktivere' : (status === 'DRAFT' ? 'flytte til kladde' : 'deaktivere');

    SweetPrompt.confirm('Bekræft', 'Er du sikker på du vil ' + actionText + ' denne template?', {
        confirmButtonText: 'Ja, ' + actionText,
        onConfirm: async () => {
            const data = await post('api/admin/marketing/templates/update', {
                uid: uid,
                status: status
            });
            if (data.status === 'success' || data.success) {
                queueNotificationOnLoad('Succes', 'Status opdateret', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', data.error?.message || 'Kunne ikke opdatere status');
            }
            return {
                status: (data.status === 'success' || data.success) ? 'success' : 'error',
                error: data.error?.message || 'Kunne ikke opdatere status'
            };
        }
    });
}

function deleteTemplate(uid, name) {
    SweetPrompt.confirm('Slet template', 'Er du sikker på du vil slette "' + name + '"? Dette kan ikke fortrydes.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async () => {
            const data = await post('api/admin/marketing/templates/delete', {
                uid: uid
            });
            if (data.status === 'success' || data.success) {
                queueNotificationOnLoad('Succes', 'Template slettet', 'success');
            } else {
                showErrorNotification('Fejl', data.error?.message || 'Kunne ikke slette template');
            }
            return {
                status: (data.status === 'success' || data.success) ? 'success' : 'error',
                error: data.error?.message || 'Kunne ikke slette template'
            };
        },
        refreshTimeout: 1000
    });
}

// =====================================================
// INSPIRATION FUNCTIONS
// =====================================================

function openInspirationUploadModal() {
    $('#inspirationUploadForm')[0].reset();
    $('#inspirationUploadModal').modal('show');
}

function uploadInspiration() {
    var fileInput = document.getElementById('inspirationFile');
    var title = document.getElementById('inspirationTitle').value.trim();
    var category = document.getElementById('inspirationCategory').value;
    var description = document.getElementById('inspirationDescription').value.trim();

    if (!fileInput.files.length) {
        showErrorNotification('Fejl', 'Vælg venligst et billede');
        return;
    }

    if (!title) {
        showErrorNotification('Fejl', 'Indtast venligst en titel');
        return;
    }

    var formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('title', title);
    formData.append('category', category);
    formData.append('description', description);
    formData.append('_csrf', _csrf);

    // Show loading state
    var btn = document.getElementById('inspirationUploadBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    fetch(HOST + 'api/admin/marketing/inspiration/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Inspiration uploadet', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke uploade inspiration');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl under upload');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function editInspiration(uid, title, category, description, status) {
    document.getElementById('inspirationEditUid').value = uid;
    document.getElementById('inspirationEditTitle').value = title;
    document.getElementById('inspirationEditCategory').value = category;
    document.getElementById('inspirationEditDescription').value = description || '';
    document.getElementById('inspirationEditStatus').value = status;
    $('#inspirationEditModal').modal('show');
}

function saveInspirationChanges() {
    var uid = document.getElementById('inspirationEditUid').value;
    var title = document.getElementById('inspirationEditTitle').value.trim();
    var category = document.getElementById('inspirationEditCategory').value;
    var description = document.getElementById('inspirationEditDescription').value.trim();
    var status = document.getElementById('inspirationEditStatus').value;

    if (!title) {
        showErrorNotification('Fejl', 'Indtast venligst en titel');
        return;
    }

    var btn = document.getElementById('inspirationSaveBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    post('api/admin/marketing/inspiration/update', {
        uid: uid,
        title: title,
        category: category,
        description: description,
        status: status
    }).then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Inspiration opdateret', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke opdatere inspiration');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    }).catch(error => {
        console.error('Update error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function updateInspirationStatus(uid, status) {
    var actionText = status === 'ACTIVE' ? 'aktivere' : (status === 'DRAFT' ? 'flytte til kladde' : 'deaktivere');

    SweetPrompt.confirm('Bekræft', 'Er du sikker på du vil ' + actionText + ' denne inspiration?', {
        confirmButtonText: 'Ja, ' + actionText,
        onConfirm: async () => {
            const data = await post('api/admin/marketing/inspiration/update', {
                uid: uid,
                status: status
            });
            if (data.status === 'success' || data.success) {
                queueNotificationOnLoad('Succes', 'Status opdateret', 'success');
                window.location.reload();
            } else {
                showErrorNotification('Fejl', data.error?.message || 'Kunne ikke opdatere status');
            }
            return {
                status: (data.status === 'success' || data.success) ? 'success' : 'error',
                error: data.error?.message || 'Kunne ikke opdatere status'
            };
        }
    });
}

function deleteInspiration(uid, title) {
    SweetPrompt.confirm('Slet inspiration', 'Er du sikker på du vil slette "' + title + '"? Dette kan ikke fortrydes.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async () => {
            const data = await post('api/admin/marketing/inspiration/delete', {
                uid: uid
            });
            if (data.status === 'success' || data.success) {
                queueNotificationOnLoad('Succes', 'Inspiration slettet', 'success');
            } else {
                showErrorNotification('Fejl', data.error?.message || 'Kunne ikke slette inspiration');
            }
            return {
                status: (data.status === 'success' || data.success) ? 'success' : 'error',
                error: data.error?.message || 'Kunne ikke slette inspiration'
            };
        },
        refreshTimeout: 1000
    });
}

// =====================================================
// A-SIGN PRELOAD BACKGROUND FUNCTIONS
// =====================================================

function openAsignPreloadUploadModal() {
    $('#asignPreloadUploadForm')[0].reset();
    $('#asignPreloadUploadModal').modal('show');
}

function uploadAsignPreload() {
    var fileInput = document.getElementById('asignPreloadFile');
    var title = document.getElementById('asignPreloadTitle').value.trim();
    var description = document.getElementById('asignPreloadDescription').value.trim();

    if (!fileInput.files.length) {
        showErrorNotification('Fejl', 'Vælg venligst et billede');
        return;
    }

    if (!title) {
        showErrorNotification('Fejl', 'Indtast venligst en titel');
        return;
    }

    var formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('title', title);
    formData.append('category', 'a_sign_preload'); // Fixed category for preloads
    formData.append('description', description);
    formData.append('_csrf', _csrf);

    // Show loading state
    var btn = document.getElementById('asignPreloadUploadBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    fetch(HOST + 'api/admin/marketing/inspiration/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Baggrund uploadet', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke uploade baggrund');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl under upload');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function editAsignPreload(uid, title, description, status) {
    document.getElementById('asignPreloadEditUid').value = uid;
    document.getElementById('asignPreloadEditTitle').value = title;
    document.getElementById('asignPreloadEditDescription').value = description || '';
    document.getElementById('asignPreloadEditStatus').value = status;
    $('#asignPreloadEditModal').modal('show');
}

function saveAsignPreloadChanges() {
    var uid = document.getElementById('asignPreloadEditUid').value;
    var title = document.getElementById('asignPreloadEditTitle').value.trim();
    var description = document.getElementById('asignPreloadEditDescription').value.trim();
    var status = document.getElementById('asignPreloadEditStatus').value;

    if (!title) {
        showErrorNotification('Fejl', 'Indtast venligst en titel');
        return;
    }

    var btn = document.getElementById('asignPreloadSaveBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    post('api/admin/marketing/inspiration/update', {
        uid: uid,
        title: title,
        category: 'a_sign_preload', // Keep category fixed
        description: description,
        status: status
    }).then(data => {
        if (data.status === 'success' || data.success) {
            queueNotificationOnLoad('Succes', data.message || 'Baggrund opdateret', 'success');
            window.location.reload();
        } else {
            showErrorNotification('Fejl', data.error?.message || 'Kunne ikke opdatere baggrund');
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        }
    }).catch(error => {
        console.error('Update error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl');
        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}

function deleteAsignPreload(uid, title) {
    SweetPrompt.confirm('Slet baggrund', 'Er du sikker på du vil slette "' + title + '"? Dette kan ikke fortrydes.', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async () => {
            const data = await post('api/admin/marketing/inspiration/delete', {
                uid: uid
            });
            if (data.status === 'success' || data.success) {
                queueNotificationOnLoad('Succes', 'Baggrund slettet', 'success');
            } else {
                showErrorNotification('Fejl', data.error?.message || 'Kunne ikke slette baggrund');
            }
            return {
                status: (data.status === 'success' || data.success) ? 'success' : 'error',
                error: data.error?.message || 'Kunne ikke slette baggrund'
            };
        },
        refreshTimeout: 1000
    });
}
