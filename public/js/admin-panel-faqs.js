/**
 * Admin Panel - FAQ Management JavaScript
 * Inline editing - no modals
 */

var currentTab = 'consumer';

/**
 * Switch between consumer and merchant tabs
 * @param {string} tab - 'consumer' or 'merchant'
 */
function switchTab(tab) {
    currentTab = tab;

    // Update toggle buttons
    document.getElementById('toggleConsumer').classList.toggle('active', tab === 'consumer');
    document.getElementById('toggleMerchant').classList.toggle('active', tab === 'merchant');

    // Update sections
    document.getElementById('consumerSection').classList.toggle('active', tab === 'consumer');
    document.getElementById('merchantSection').classList.toggle('active', tab === 'merchant');
}

/**
 * Update the FAQ counts in the toggle buttons
 */
function updateCounts() {
    var consumerCount = document.querySelectorAll('#consumerFaqList .faq-item').length;
    var merchantCount = document.querySelectorAll('#merchantFaqList .faq-item').length;
    document.getElementById('consumerCount').textContent = consumerCount;
    document.getElementById('merchantCount').textContent = merchantCount;
}

/**
 * Add new FAQ - clones template and inserts at top
 */
function addNewFaq() {
    // Determine type based on active section
    const isConsumer = document.getElementById('consumerSection').classList.contains('active');
    const type = isConsumer ? 'consumer' : 'merchant';
    const listId = isConsumer ? 'consumerFaqList' : 'merchantFaqList';

    const template = document.getElementById('faq-template');
    const clone = template.content.cloneNode(true);

    // Set the type
    clone.querySelector('.faq-type').value = type;

    const list = document.getElementById(listId);

    // Remove empty state if present
    const emptyState = list.querySelector('.text-center.py-5');
    if (emptyState) {
        emptyState.remove();
    }

    list.insertBefore(clone, list.firstChild);

    // Get the newly inserted item
    const newItem = list.firstElementChild;
    if (newItem) {
        // Initialize selectV2 on the new select element
        selectV2(newItem.querySelector('.faq-active'));

        // Focus on the category field
        const categoryInput = newItem.querySelector('.faq-category');
        if (categoryInput) categoryInput.focus();
    }
}

/**
 * Save FAQ - creates or updates based on uid presence
 * @param {HTMLElement} btn - The save button clicked
 */
async function saveFaq(btn) {
    const item = btn.closest('.faq-item');
    const uid = item.dataset.uid || '';

    // Sync contenteditable to hidden input before reading
    const editor = item.querySelector('.faq-content-editor');
    const hiddenContent = item.querySelector('.faq-content');
    if (editor && hiddenContent) {
        hiddenContent.value = editor.innerHTML;
    }

    const type = item.querySelector('.faq-type').value;
    const category = item.querySelector('.faq-category').value.trim();
    const sortOrder = parseInt(item.querySelector('.faq-sort').value) || 0;
    const isActive = item.querySelector('.faq-active').value;
    const title = item.querySelector('.faq-title').value.trim();
    const content = hiddenContent ? hiddenContent.value : '';

    // Validation
    if (!category || !title || !content) {
        showErrorNotification('Fejl', 'Udfyld alle felter (kategori, titel, svar)');
        return;
    }

    // Disable button during save
    btn.disabled = true;
    btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Gemmer...';

    const endpoint = uid ? platformLinks.api.admin.panel.faqsUpdate : platformLinks.api.admin.panel.faqsCreate;
    const data = {
        type: type,
        category: category,
        title: title,
        content: content,
        sort_order: sortOrder,
        is_active: parseInt(isActive)
    };

    if (uid) {
        data.uid = uid;
    }

    const response = await post(endpoint, data);

    btn.disabled = false;
    btn.innerHTML = '<i class="mdi mdi-content-save"></i> Gem';

    if (response.status === 'success') {
        // If new FAQ, update the data-uid so subsequent saves/deletes work
        if (!uid && response.data.faq) {
            item.dataset.uid = response.data.faq.uid;
            updateCounts();
        }
        showSuccessNotification('Gemt', uid ? 'FAQ opdateret' : 'FAQ oprettet');
    } else {
        showErrorNotification('Fejl', response.message || 'Der opstod en fejl');
    }
}

/**
 * Delete FAQ - confirms and removes
 * @param {HTMLElement} btn - The delete button clicked
 */
function deleteFaq(btn) {
    const item = btn.closest('.faq-item');
    const uid = item.dataset.uid;
    const title = item.querySelector('.faq-title').value || 'Denne FAQ';

    // If no uid, it's a new unsaved FAQ - just remove from DOM
    if (!uid || uid === '') {
        item.remove();
        updateCounts();
        return;
    }

    // Confirm deletion
    SweetPrompt.confirm('Slet FAQ?', 'Er du sikker på at du vil slette "' + title + '"?', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async function() {
            await performDelete(item, uid);
        }
    });
}

/**
 * Perform the actual deletion
 * @param {HTMLElement} item - The FAQ item element
 * @param {string} uid - The FAQ uid
 */
async function performDelete(item, uid) {
    const response = await post(platformLinks.api.admin.panel.faqsDelete, { uid: uid });

    if (response.status === 'success') {
        item.remove();
        updateCounts();
        showSuccessNotification('Slettet', 'FAQ blev slettet');
    } else {
        showErrorNotification('Fejl', response.message || 'Der opstod en fejl');
    }
}

/**
 * Execute formatting command on contenteditable
 * @param {string} command - The execCommand to run
 */
function execCmd(command) {
    document.execCommand(command, false, null);
}

/**
 * Insert a link
 * @param {HTMLElement} btn - The toolbar button clicked
 */
function execLink(btn) {
    var url = prompt('Indtast URL:', 'https://');
    if (url) {
        document.execCommand('createLink', false, url);
    }
}

/**
 * Sync contenteditable content to hidden input
 * @param {HTMLElement} editor - The contenteditable div
 */
function syncContent(editor) {
    var container = editor.closest('.html-editor-container');
    var hiddenInput = container.querySelector('.faq-content');
    hiddenInput.value = editor.innerHTML;
}
