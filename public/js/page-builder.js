/**
 * Page Builder JavaScript
 * Handles media upload (hero image and logo) and page builder functionality
 */

(function() {
    'use strict';

    // State
    let currentLocation = null;
    let currentPageId = null;
    let currentPageState = null;
    let originalStateHash = null;
    let isPublished = false;
    const mediaState = {
        hero: { isDefault: false, input: null },
        logo: { isDefault: false, input: null }
    };

    // Cache for image URLs to prevent unnecessary reloads
    let imageCache = {
        hero: '',
        logo: ''
    };

    /**
     * Initialize page builder
     */
    function init() {
        // Get location ID and page info from page
        const locationIdElement = document.querySelector('[data-location-id]');
        if (locationIdElement) {
            currentLocation = locationIdElement.dataset.locationId;
        }

        // Get page state from global variables
        currentPageId = typeof window.currentPageId !== 'undefined' ? window.currentPageId : null;
        currentPageState = typeof window.currentPageState !== 'undefined' ? window.currentPageState : null;
        isPublished = currentPageState === 'PUBLISHED';

        // Setup page version selector
        setupPageVersionSelector();

        // Show info message if viewing published page
        if (isPublished) {
            showPublishedPageInfo();
        }

        // Setup hero image
        mediaState.hero.isDefault = typeof isDefaultHeroImage !== 'undefined' ? isDefaultHeroImage : false;
        setupMediaUpload('hero', {
            minWidth: 0,
            minHeight: 300,
            recommendedRatio: 19 / 6,
            minRatio: 19 / 12,
            maxRatio: 20 / 5,
            ratioDescription: 'et billedformat mellem 20:5 og 19:12 (19:6 anbefalet, f.eks. 1920×600px)',
            maxFileSize: 10 * 1024 * 1024,
            label: 'Hero-billede',
            apiEndpoint: platformLinks.api.locations.merchantHeroImage
        });

        // Setup logo upload
        mediaState.logo.isDefault = typeof isDefaultLocationLogo !== 'undefined' ? isDefaultLocationLogo : false;
        setupMediaUpload('logo', {
            minWidth: 250,
            minHeight: 250,
            recommendedRatio: 1.0,
            minRatio: 0.8,
            maxRatio: 1.2,
            ratioDescription: 'et kvadratisk format (1:1 anbefalet, f.eks. 500×500px)',
            maxFileSize: 5 * 1024 * 1024,
            label: 'Logo',
            apiEndpoint: platformLinks.api.locations.merchantLogo
        });

        // Setup save button
        setupSaveButton();

        // Setup sections
        setupSections();

        // Setup change tracking and publish button
        setupChangeTracking();
        setupPublishButton();

        // Initialize image cache with current values
        initializeImageCache();
    }

    /**
     * Initialize image cache with current values
     */
    function initializeImageCache() {
        // Cache hero image
        const heroPreview = document.getElementById('hero-preview-image');
        if (heroPreview) {
            const bgImage = heroPreview.style.backgroundImage;
            const urlMatch = bgImage.match(/url\(['"]?([^'"]+)['"]?\)/);
            if (urlMatch) {
                imageCache.hero = urlMatch[1];
            }
        }

        // Cache logo
        const logoPreview = document.getElementById('logo-preview-image');
        if (logoPreview && logoPreview.src) {
            imageCache.logo = logoPreview.src;
        }
    }

    /**
     * Generic setup for media upload (hero/logo)
     */
    function setupMediaUpload(type, config) {
        const uploadArea = document.getElementById(`${type}-upload-area`);
        const fileInput = document.getElementById(`${type}-image-input`);
        const removeButton = document.getElementById(`${type}-remove-button`);

        if (!uploadArea || !fileInput) return;

        mediaState[type].input = fileInput;

        // Hide remove button if showing default
        if (mediaState[type].isDefault && removeButton) {
            removeButton.style.display = 'none';
        }

        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length > 0) {
                handleFileSelect(type, e.dataTransfer.files[0], config);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(type, e.target.files[0], config);
            }
        });

        // Remove button
        if (removeButton) {
            removeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                removeMedia(type, config);
            });
        }
    }

    /**
     * Handle file selection
     */
    function handleFileSelect(type, file, config) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!validTypes.includes(file.type)) {
            showErrorNotification('Ugyldig filtype', 'Kun billeder er tilladt (JPG, PNG, GIF)');
            return;
        }

        // Validate file size
        if (file.size > config.maxFileSize) {
            const maxMB = (config.maxFileSize / (1024 * 1024)).toFixed(0);
            showErrorNotification('Filen er for stor', `Billedet må maksimalt være ${maxMB}MB`);
            return;
        }

        // Validate image dimensions before upload
        validateAndUploadImage(type, file, config);
    }

    /**
     * Validate image dimensions and upload
     */
    function validateAndUploadImage(type, file, config) {
        const reader = new FileReader();

        reader.onload = function(e) {
            const img = new Image();

            img.onload = function() {
                const width = img.width;
                const height = img.height;

                // Check minimum dimensions
                if (width < config.minWidth || height < config.minHeight) {
                    showErrorNotification(
                        'Billedet er for lille',
                        `Billedet skal være mindst ${config.minWidth}×${config.minHeight}px. Dit billede er ${width}×${height}px`
                    );
                    return;
                }

                // Check aspect ratio
                const actualRatio = width / height;
                if (actualRatio < config.minRatio || actualRatio > config.maxRatio) {
                    const recommendedWidth = Math.round(height * config.recommendedRatio);
                    showErrorNotification(
                        'Forkert billedformat',
                        `Billedet skal have ${config.ratioDescription}. Dit billede er ${width}×${height}px. Prøv ${recommendedWidth}×${height}px`
                    );
                    return;
                }

                // All validations passed, upload the image
                uploadMedia(type, file, config);
            };

            img.onerror = function() {
                showErrorNotification('Fejl', 'Kunne ikke læse billedfilen');
            };

            img.src = e.target.result;
        };

        reader.onerror = function() {
            showErrorNotification('Fejl', 'Kunne ikke læse filen');
        };

        reader.readAsDataURL(file);
    }

    /**
     * Upload media to server
     */
    function uploadMedia(type, file, config) {
        if (!currentLocation) {
            showErrorNotification('Fejl', 'Kunne ikke finde location ID');
            return;
        }

        // Show loading state
        const uploadArea = document.getElementById(`${type}-upload-area`);
        if (uploadArea) {
            uploadArea.classList.add('uploading');
            uploadArea.innerHTML = `
                <div class="flex-col-start flex-align-center" style="gap: .5rem;">
                    <div class="spinner-border color-design-blue square-40" role="status" style="border-width: 3px;">
                        <span class="sr-only">Uploader...</span>
                    </div>
                    <p class="mb-0 font-14 font-weight-medium color-design-blue">Uploader billede...</p>
                </div>
            `;
        }

        // Prepare form data
        const formData = new FormData();
        formData.append('file', file);
        if (currentPageId) {
            formData.append('page_id', currentPageId);
        }

        // Upload via AJAX
        let link = serverHost + config.apiEndpoint.replace("{location_id}", currentLocation);
        post(link, formData)
            .then(response => {
                if (response.status === 'success') {
                    showSuccessNotification('Succes', `${config.label} uploadet!`);
                    updateMediaPreview(type, response.data.url);
                    resetUploadArea(type, config);

                    // Update default state and button visibility
                    mediaState[type].isDefault = response.data.default || false;
                    const removeButton = document.getElementById(`${type}-remove-button`);
                    if (removeButton) {
                        removeButton.style.display = mediaState[type].isDefault ? 'none' : 'block';
                    }

                    // Broadcast update to preview (cache will be updated in updateInlinePreview)
                    broadcastPreviewUpdate();

                    // If new draft was created, redirect
                    if (response.data.created_new_draft && response.data.draft_uid) {
                        setTimeout(() => {
                            window.location.href = window.location.pathname + '?ref=' + response.data.draft_uid;
                        }, 1000);
                    }
                } else {
                    showErrorNotification('Upload fejlede', response.message || 'Kunne ikke uploade billede');
                    resetUploadArea(type, config);
                }
            })
            .catch(response => {
                showErrorNotification('Netværksfejl', 'Kunne ikke forbinde til serveren');
                resetUploadArea(type, config);
            });
    }

    /**
     * Update media preview
     */
    function updateMediaPreview(type, imageUrl) {
        const previewImage = document.getElementById(`${type}-preview-image`);
        const previewContainer = document.getElementById(`${type}-preview-container`);

        if (previewImage) {
            if(previewImage.tagName === 'IMG') previewImage.src = imageUrl;
            else previewImage.style.backgroundImage = `url('${imageUrl}')`;
            if (previewContainer) {
                previewContainer.classList.remove('d-none');
            }
        }
    }

    /**
     * Reset upload area to initial state
     */
    function resetUploadArea(type, config) {
        const uploadArea = document.getElementById(`${type}-upload-area`);
        if (uploadArea) {
            uploadArea.classList.remove('uploading');
            const sizeText = type === 'hero' ? '10MB (1920×600px anbefalet)' : '5MB (500×500px anbefalet)';
            uploadArea.innerHTML = `
                <i class="mdi mdi-upload font-48 color-gray mb-2"></i>
                <p class="mb-0 font-14 font-weight-medium">Upload ${type === 'hero' ? 'hero-billede' : 'logo'}</p>
                <p class="mb-0 font-12 color-gray">PNG, JPG op til ${sizeText}</p>
            `;
        }
    }

    /**
     * Remove media
     */
    function removeMedia(type, config) {
        if (mediaState[type].isDefault) return;

        SweetPrompt.confirm(
            `Fjern ${type === 'hero' ? 'hero-billede' : 'logo'}?`,
            `Er du sikker på, at du vil fjerne ${type === 'hero' ? 'hero-billedet' : 'logoet'}?`,
            {
                confirmButtonText: 'Ja, fjern',
                onConfirm: async () => {
                    if (!currentLocation) {
                        return { status: 'error', error: 'Kunne ikke finde location ID' };
                    }

                    try {
                        let link = serverHost + config.apiEndpoint.replace("{location_id}", currentLocation);
                        const payload = currentPageId ? { page_id: currentPageId } : {};
                        const response = await del(link, payload);

                        if (response.status === 'success') {
                            // Update preview to show default image
                            const previewImage = document.getElementById(`${type}-preview-image`);
                            const previewContainer = document.getElementById(`${type}-preview-container`);

                            if (previewImage && response.data.default_url) {
                                if(previewImage.tagName === 'IMG') previewImage.src = response.data.default_url;
                                else previewImage.style.backgroundImage = `url('${response.data.default_url}')`;
                                if (previewContainer) {
                                    previewContainer.classList.remove('d-none');
                                }
                            }

                            // Clear file input
                            if (mediaState[type].input) {
                                mediaState[type].input.value = '';
                            }

                            // Update state and hide remove button
                            mediaState[type].isDefault = true;
                            const removeButton = document.getElementById(`${type}-remove-button`);
                            if (removeButton) {
                                removeButton.style.display = 'none';
                            }

                            showSuccessNotification("Billede fjernet", `${config.label} er blevet nulstillet til standard billede.`);

                            // Broadcast update to preview (cache will be updated in updateInlinePreview)
                            broadcastPreviewUpdate();

                            // If new draft was created, redirect
                            if (response.data.created_new_draft && response.data.draft_uid) {
                                setTimeout(() => {
                                    window.location.href = window.location.pathname + '?ref=' + response.data.draft_uid;
                                }, 1000);
                            }
                        } else {
                            showErrorNotification("Der opstod en fejl", response.error.message);
                        }
                    } catch (error) {
                        showErrorNotification("Der opstod en fejl", error.message);
                    }
                }
            }
        );
    }

    /**
     * Setup save button
     */
    function setupSaveButton() {
        const saveButton = document.querySelector('button[name="save_page_changes"]');
        if (!saveButton) return;

        saveButton.addEventListener('click', async (e) => {
            e.preventDefault();
            saveDraft();
        });
    }

    /**
     * Collect form data from all fields
     */
    function collectFormData() {
        const formData = {};

        // Collect standard fields
        const titleInput = document.querySelector('input[name="page_title"]');
        const captionTextarea = document.querySelector('textarea[name="page_caption"]');
        const aboutUsTextarea = document.querySelector('textarea[name="about_us"]');
        const creditWidgetCheckbox = document.querySelector('input[name="credit_widget_enabled"]');

        if (titleInput) formData.title = titleInput.value;
        if (captionTextarea) formData.caption = captionTextarea.value;
        if (aboutUsTextarea) formData.about_us = aboutUsTextarea.value;
        if (creditWidgetCheckbox) formData.credit_widget_enabled = creditWidgetCheckbox.checked ? 1 : 0;

        // Collect dynamic sections
        const sectionElements = document.querySelectorAll('[data-section-index]');
        sectionElements.forEach((sectionEl) => {
            const index = sectionEl.dataset.sectionIndex;
            const titleInput = sectionEl.querySelector(`input[name="section_title_${index}"]`);
            const contentTextarea = sectionEl.querySelector(`textarea[name="section_content_${index}"]`);

            if (titleInput) formData[`section_title_${index}`] = titleInput.value;
            if (contentTextarea) formData[`section_content_${index}`] = contentTextarea.value;
        });

        return formData;
    }

    /**
     * Save draft
     */
    async function saveDraft() {
        if (!currentLocation) {
            showErrorNotification('Fejl', 'Kunne ikke finde location ID');
            return;
        }

        // Collect form data
        const formData = collectFormData();

        // Add page ID if we have one
        if (currentPageId) {
            formData.page_id = currentPageId;
        }

        // Show loading state on button
        const saveButton = document.querySelector('button[name="save_page_changes"]');
        if (saveButton) {
            saveButton.disabled = true;
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div>';

            try {
                const link = serverHost + platformLinks.api.locations.savePageDraft.replace("{location_id}", currentLocation);
                const response = await post(link, formData);

                if (response.status === 'success') {
                    showSuccessNotification('Gemt', 'Dine ændringer er blevet gemt');

                    // Update original state hash since we just saved
                    originalStateHash = getCurrentStateHash();
                    checkForChanges();

                    // If we saved to a different draft (created from published), redirect
                    if (response.data.draft_uid && response.data.draft_uid !== currentPageId) {
                        setTimeout(() => {
                            window.location.href = window.location.pathname + '?ref=' + response.data.draft_uid;
                        }, 1000);
                    }
                } else {
                    showErrorNotification('Fejl', response.message || 'Kunne ikke gemme ændringer');
                }
            } catch (error) {
                showErrorNotification('Netværksfejl', 'Kunne ikke forbinde til serveren');
            } finally {
                // Restore button
                saveButton.disabled = false;
                saveButton.innerHTML = originalText;
            }
        }
    }

    /**
     * Setup sections functionality
     */
    function setupSections() {
        const addSectionBtn = document.getElementById('add-section-btn');
        const sectionsContainer = document.getElementById('sections-container');

        if (!addSectionBtn || !sectionsContainer) return;

        // Add section button click
        addSectionBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addNewSection();
        });

        // Setup delete buttons for existing sections
        setupSectionDeleteButtons();

        // Setup about us delete button
        const aboutUsDeleteBtn = document.querySelector('[data-delete-about-us]');
        if (aboutUsDeleteBtn) {
            aboutUsDeleteBtn.addEventListener('click', (e) => {
                e.preventDefault();
                deleteAboutUsSection();
            });
        }
    }

    /**
     * Get next section index
     */
    function getNextSectionIndex() {
        const sections = document.querySelectorAll('.section-item');
        if (sections.length === 0) return 0;

        let maxIndex = -1;
        sections.forEach(section => {
            const index = parseInt(section.dataset.sectionIndex);
            if (index > maxIndex) maxIndex = index;
        });

        return maxIndex + 1;
    }

    /**
     * Add new section
     */
    function addNewSection() {
        const sectionsContainer = document.getElementById('sections-container');
        if (!sectionsContainer) return;

        const index = getNextSectionIndex();
        const sectionHtml = `
            <div class="flex-col-start card-border border-radius-10px p-3 section-item" style="row-gap: 1rem;" data-section-index="${index}">
                <div class="flex-row-between flex-align-center">
                    <input type="text" class="flex-1-current form-field-v2 mb-0 font-14 font-weight-medium" name="section_title_${index}" value="" style="border-radius: 10px 0 0 10px;" placeholder="Sektion titel">
                    <button class="btn-v2 danger-btn transition-all section-delete-btn h-45px" style="border-radius: 0 10px 10px 0;">
                        <i class="mdi mdi-delete-outline"></i>
                    </button>
                </div>
                <textarea class="form-field-v2 mnh-80px" name="section_content_${index}" placeholder="Skriv indhold..."></textarea>
            </div>
        `;

        sectionsContainer.insertAdjacentHTML('beforeend', sectionHtml);

        // Setup delete button for the new section
        setupSectionDeleteButtons();

        // Focus on title input
        const newSection = sectionsContainer.lastElementChild;
        const titleInput = newSection.querySelector('input[type="text"]');
        if (titleInput) titleInput.focus();

        // Broadcast update to preview
        broadcastPreviewUpdate();
    }

    /**
     * Setup delete buttons for sections
     */
    function setupSectionDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.section-delete-btn');
        deleteButtons.forEach(btn => {
            // Remove old listeners by cloning
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const section = newBtn.closest('.section-item');
                if (section) deleteSection(section);
            });
        });
    }

    /**
     * Delete a section
     */
    function deleteSection(sectionElement) {
        sectionElement.remove();
        // Broadcast update to preview
        broadcastPreviewUpdate();
    }

    /**
     * Delete about us section
     */
    function deleteAboutUsSection() {
        const aboutUsTextarea = document.querySelector('textarea[name="about_us"]');
        if (aboutUsTextarea) {
            aboutUsTextarea.value = '';
            const aboutUsSection = aboutUsTextarea.closest('.card-border');
            if (aboutUsSection) {
                aboutUsSection.remove();
            }
        }
    }

    /**
     * Setup page version selector
     */
    function setupPageVersionSelector() {
        const pageVersionSelect = document.getElementById('page-version-select');
        if (!pageVersionSelect) return;

        pageVersionSelect.addEventListener('change', (e) => {
            const selectedOption = e.target.options[e.target.selectedIndex];
            const href = selectedOption.dataset.href;
            if (href) {
                window.location.href = href;
            }
        });
    }

    /**
     * Hash a string using simple hash function
     */
    function hashString(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        return hash.toString();
    }

    /**
     * Get current form state as hash
     */
    function getCurrentStateHash() {
        const formData = collectFormData();
        const stateString = JSON.stringify(formData);
        return hashString(stateString);
    }

    /**
     * Setup change tracking
     */
    function setupChangeTracking() {
        // Store original state hash
        originalStateHash = getCurrentStateHash();

        // Monitor all form changes
        const formElements = document.querySelectorAll('input, textarea, select');
        formElements.forEach(element => {
            element.addEventListener('input', checkForChanges);
            element.addEventListener('change', checkForChanges);
        });

        // Initial check and broadcast
        checkForChanges();

        // Broadcast initial state to preview
        broadcastPreviewUpdate();
    }

    /**
     * Check if form has changed from original state
     */
    function checkForChanges() {
        const currentHash = getCurrentStateHash();
        const hasChanges = currentHash !== originalStateHash;

        const publishBtn = document.getElementById('publish-page-btn');
        if (publishBtn) {
            if (hasChanges || isPublished) {
                publishBtn.style.display = 'none';
            } else {
                publishBtn.style.display = 'inline-flex';
            }
        }

        // Broadcast changes to preview
        broadcastPreviewUpdate();
    }

    /**
     * Broadcast preview update to preview window
     */
    function broadcastPreviewUpdate() {
        if (!currentPageId) return;

        try {
            const formData = collectFormData();

            // Build preview data
            const previewData = {
                title: formData.title || '',
                caption: formData.caption || '',
                about_us: formData.about_us || '',
                credit_widget_enabled: formData.credit_widget_enabled ? true : false,
                sections: [],
                hero_image: '',
                logo: ''
            };

            // Collect sections
            const sectionElements = document.querySelectorAll('[data-section-index]');
            sectionElements.forEach((sectionEl) => {
                const index = sectionEl.dataset.sectionIndex;
                const title = formData[`section_title_${index}`] || '';
                const content = formData[`section_content_${index}`] || '';

                if (title || content) {
                    previewData.sections.push({ title, content });
                }
            });

            // Get current hero image URL
            const heroPreview = document.getElementById('hero-preview-image');
            if (heroPreview) {
                const bgImage = heroPreview.style.backgroundImage;
                const urlMatch = bgImage.match(/url\(['"]?([^'"]+)['"]?\)/);
                if (urlMatch) {
                    previewData.hero_image = urlMatch[1];
                }
            }

            // Get current logo URL
            const logoPreview = document.getElementById('logo-preview-image');
            if (logoPreview && logoPreview.src) {
                previewData.logo = logoPreview.src;
            }

            // Store in localStorage for external preview window to pick up
            localStorage.setItem(`pagebuilder_preview_${currentPageId}`, JSON.stringify(previewData));

            // Update inline preview
            updateInlinePreview(previewData);
        } catch (error) {
            console.error('Error broadcasting preview update:', error);
        }
    }

    /**
     * Update inline preview
     */
    function updateInlinePreview(data) {
        // Update title
        if (data.title !== undefined) {
            const titleEl = document.getElementById('inline-preview-title');
            if (titleEl) {
                titleEl.textContent = data.title || '';
            }
        }

        // Update caption
        if (data.caption !== undefined) {
            const captionEl = document.getElementById('inline-preview-caption');
            const captionSection = document.getElementById('inline-preview-caption-section');

            if (captionEl && captionSection) {
                if (data.caption) {
                    captionEl.innerHTML = escapeHtml(data.caption).replace(/\n/g, '<br>');
                    captionSection.style.display = 'block';
                } else {
                    captionSection.style.display = 'none';
                }
            }
        }

        // Update about us
        if (data.about_us !== undefined) {
            const aboutEl = document.getElementById('inline-preview-about');
            const aboutSection = document.getElementById('inline-preview-about-section');

            if (aboutEl && aboutSection) {
                if (data.about_us) {
                    aboutEl.innerHTML = escapeHtml(data.about_us).replace(/\n/g, '<br>');
                    aboutSection.style.display = 'block';
                } else {
                    aboutSection.style.display = 'none';
                }
            }
        }

        // Update credit widget
        if (data.credit_widget_enabled !== undefined) {
            const creditWidget = document.getElementById('inline-preview-credit-widget');
            if (creditWidget) {
                creditWidget.style.display = data.credit_widget_enabled ? 'block' : 'none';
            }
        }

        // Update sections
        if (data.sections !== undefined) {
            updateInlineSections(data.sections);
        }

        // Update hero image (only if URL changed)
        if (data.hero_image !== undefined && data.hero_image && imageCache.hero !== data.hero_image) {
            const heroEl = document.getElementById('inline-preview-hero');
            if (heroEl) {
                heroEl.style.backgroundImage = `linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('${data.hero_image}')`;
                imageCache.hero = data.hero_image;
            }
        }

        // Update logo (only if URL changed)
        if (data.logo !== undefined && data.logo && imageCache.logo !== data.logo) {
            const logoEl = document.getElementById('inline-preview-logo');
            if (logoEl) {
                logoEl.src = data.logo;
                imageCache.logo = data.logo;
            }
        }
    }

    /**
     * Update inline sections
     */
    function updateInlineSections(sections) {
        const container = document.getElementById('inline-preview-sections-container');
        if (!container) return;

        // Clear existing sections
        container.innerHTML = '';

        // Add new sections
        sections.forEach((section, index) => {
            if (!section.title && !section.content) return;

            const sectionEl = document.createElement('div');
            sectionEl.className = 'mb-3 p-3 border-radius-8px inline-preview-section';
            sectionEl.dataset.sectionIndex = index;
            sectionEl.style.background = '#f8f9fa';

            let html = '';

            if (section.title) {
                html += `<p class="font-14 font-weight-bold mb-2 inline-preview-section-title">${escapeHtml(section.title)}</p>`;
            }

            if (section.content) {
                html += `<p class="mb-0 font-13 line-height-relaxed inline-preview-section-content">${escapeHtml(section.content).replace(/\n/g, '<br>')}</p>`;
            }

            sectionEl.innerHTML = html;
            container.appendChild(sectionEl);
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Setup publish button
     */
    function setupPublishButton() {
        const publishBtn = document.getElementById('publish-page-btn');
        if (!publishBtn) return;

        publishBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            await publishPage();
        });
    }

    /**
     * Publish the current page
     */
    async function publishPage() {
        SweetPrompt.confirm(
            'Udgiv side?',
            'Er du sikker på, at du vil udgive denne side? Den vil blive synlig for alle besøgende.',
            {
                confirmButtonText: 'Ja, udgiv',
                onConfirm: async () => {
                    if (!currentLocation || !currentPageId) {
                        return { status: 'error', error: 'Kunne ikke finde page ID' };
                    }

                    try {
                        const link = serverHost + platformLinks.api.locations.publishPageDraft
                            .replace("{location_id}", currentLocation);
                        const response = await post(link, { page_id: currentPageId });

                        if (response.status === 'success') {
                            showSuccessNotification('Udgivet', 'Siden er blevet udgivet');
                            // Reload page to show published state
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            showErrorNotification('Fejl', response.message || 'Kunne ikke udgive siden');
                        }
                    } catch (error) {
                        showErrorNotification('Netværksfejl', 'Kunne ikke forbinde til serveren');
                    }
                }
            }
        );
    }

    /**
     * Show info message for published page
     */
    function showPublishedPageInfo() {
        const saveButton = document.querySelector('button[name="save_page_changes"]');
        if (saveButton) {
            const parent = saveButton.parentElement;
            const notice = document.createElement('div');
            notice.className = 'flex-row-start-center p-3 bg-info-light border-radius-10px';
            notice.style.gap = '0.5rem';
            notice.innerHTML = `
                <i class="mdi mdi-information-outline color-info font-20"></i>
                <p class="mb-0 font-13 color-info">
                    Du redigerer den udgivne side. Når du gemmer ændringer, oprettes en ny draft automatisk.
                </p>
            `;
            parent.parentElement.insertBefore(notice, parent.parentElement.firstChild);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
