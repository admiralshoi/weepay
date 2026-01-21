/**
 * Admin Marketing Templates Management
 */

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
