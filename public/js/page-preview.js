/**
 * Page Preview JavaScript
 * Listens for changes from page builder and updates preview in real-time
 */

(function() {
    'use strict';

    // State
    let previewData = {
        title: '',
        caption: '',
        about_us: '',
        credit_widget_enabled: false,
        offer_enabled: false,
        offer_title: '',
        offer_text: '',
        offer_image: '',
        sections: [],
        hero_image: '',
        logo: ''
    };

    // Cache for image URLs to prevent unnecessary reloads
    let imageCache = {
        hero: '',
        logo: '',
        offer: ''
    };

    /**
     * Initialize preview listener
     */
    function init() {
        // Initialize image cache with current values
        initializeImageCache();

        // Listen for storage events (cross-tab communication)
        window.addEventListener('storage', handleStorageChange);

        // Check for initial data
        loadPreviewData();

        // Poll for changes every 500ms as fallback
        setInterval(loadPreviewData, 500);
    }

    /**
     * Initialize image cache with current values
     */
    function initializeImageCache() {
        // Cache hero image
        const heroEl = document.getElementById('preview-hero');
        if (heroEl) {
            const bgImage = heroEl.style.backgroundImage;
            const urlMatch = bgImage.match(/url\(['"]?([^'"]+)['"]?\)/);
            if (urlMatch) {
                imageCache.hero = urlMatch[1];
            }
        }

        // Cache logo
        const logoEl = document.getElementById('preview-logo');
        if (logoEl && logoEl.src) {
            imageCache.logo = logoEl.src;
        }

        // Cache offer image
        const offerImageEl = document.getElementById('preview-offer-image');
        if (offerImageEl && offerImageEl.src) {
            imageCache.offer = offerImageEl.src;
        }
    }

    /**
     * Handle storage change event
     */
    function handleStorageChange(e) {
        if (e.key === `pagebuilder_preview_${draftId}`) {
            try {
                const data = JSON.parse(e.newValue);
                updatePreview(data);
            } catch (error) {
                console.error('Error parsing preview data:', error);
            }
        }
    }

    /**
     * Load preview data from localStorage
     */
    function loadPreviewData() {
        try {
            const storedData = localStorage.getItem(`pagebuilder_preview_${draftId}`);
            if (storedData) {
                const data = JSON.parse(storedData);

                // Only update if data has changed
                if (JSON.stringify(data) !== JSON.stringify(previewData)) {
                    previewData = data;
                    updatePreview(data);
                }
            }
        } catch (error) {
            console.error('Error loading preview data:', error);
        }
    }

    /**
     * Update preview with new data
     */
    function updatePreview(data) {
        // Update title (main page)
        if (data.title !== undefined) {
            const titleEl = document.getElementById('preview-title');
            if (titleEl) {
                titleEl.textContent = data.title || '';
            }
        }

        // Update caption (main page)
        if (data.caption !== undefined) {
            const captionEl = document.getElementById('preview-caption');
            const captionSection = document.getElementById('preview-caption-section');

            if (captionEl && captionSection) {
                if (data.caption) {
                    captionEl.innerHTML = escapeHtml(data.caption).replace(/\n/g, '<br>');
                    captionSection.style.display = 'block';
                } else {
                    captionSection.style.display = 'none';
                }
            }

            // Update caption (checkout page)
            const checkoutCaptionEl = document.getElementById('preview-checkout-caption');
            if (checkoutCaptionEl) {
                checkoutCaptionEl.textContent = data.caption || '';
            }
        }

        // Update about us
        if (data.about_us !== undefined) {
            const aboutEl = document.getElementById('preview-about');
            const aboutSection = document.getElementById('preview-about-section');

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
            const creditWidget = document.getElementById('preview-credit-widget');
            if (creditWidget) {
                creditWidget.style.display = data.credit_widget_enabled ? 'block' : 'none';
            }
        }

        // Update offer section
        const offerSection = document.getElementById('preview-offer-section');
        if (offerSection) {
            const offerVisible = data.offer_enabled && data.offer_title && (data.offer_text || data.offer_image);
            offerSection.style.display = offerVisible ? 'block' : 'none';

            if (offerVisible) {
                const offerTitleEl = document.getElementById('preview-offer-title');
                const offerContentDiv = offerSection.querySelector('.flex-1-current');

                if (offerTitleEl) offerTitleEl.textContent = data.offer_title || '';

                // Handle offer text - create element if needed
                let offerTextEl = document.getElementById('preview-offer-text');
                if (data.offer_text) {
                    if (!offerTextEl && offerContentDiv) {
                        offerTextEl = document.createElement('p');
                        offerTextEl.id = 'preview-offer-text';
                        offerTextEl.className = 'mb-0 font-15 line-height-relaxed';
                        offerContentDiv.appendChild(offerTextEl);
                    }
                    if (offerTextEl) {
                        offerTextEl.innerHTML = escapeHtml(data.offer_text).replace(/\n/g, '<br>');
                        offerTextEl.style.display = 'block';
                    }
                } else if (offerTextEl) {
                    offerTextEl.style.display = 'none';
                }

                // Handle offer image - create container and image if needed
                let offerImageContainer = document.getElementById('preview-offer-image-container');
                let offerImageEl = document.getElementById('preview-offer-image');
                const flexContainer = offerSection.querySelector('.flex-row-start');

                if (data.offer_image) {
                    if (!offerImageContainer && flexContainer) {
                        offerImageContainer = document.createElement('div');
                        offerImageContainer.id = 'preview-offer-image-container';
                        offerImageContainer.className = 'offer-image-container';
                        offerImageEl = document.createElement('img');
                        offerImageEl.id = 'preview-offer-image';
                        offerImageEl.style.cssText = 'max-width: 200px; max-height: 200px; border-radius: 10px; object-fit: cover;';
                        offerImageContainer.appendChild(offerImageEl);
                        flexContainer.appendChild(offerImageContainer);
                    }
                    if (offerImageEl && imageCache.offer !== data.offer_image) {
                        offerImageEl.src = data.offer_image;
                        imageCache.offer = data.offer_image;
                    }
                    if (offerImageContainer) offerImageContainer.style.display = 'block';
                } else if (offerImageContainer) {
                    offerImageContainer.style.display = 'none';
                }
            }
        }

        // Update sections
        if (data.sections !== undefined) {
            updateSections(data.sections);
        }

        // Update hero image (only if URL changed)
        if (data.hero_image !== undefined && data.hero_image && imageCache.hero !== data.hero_image) {
            // Main page hero
            const heroEl = document.getElementById('preview-hero');
            if (heroEl) {
                heroEl.style.backgroundImage = `linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('${data.hero_image}')`;
            }

            // Checkout page hero
            const checkoutHeroEl = document.getElementById('preview-checkout-hero');
            if (checkoutHeroEl) {
                checkoutHeroEl.style.backgroundImage = `url('${data.hero_image}')`;
            }

            imageCache.hero = data.hero_image;
        }

        // Update logo (only if URL changed)
        if (data.logo !== undefined && data.logo && imageCache.logo !== data.logo) {
            const logoEl = document.getElementById('preview-logo');
            if (logoEl) {
                logoEl.src = data.logo;
                imageCache.logo = data.logo;
            }
        }
    }

    /**
     * Update sections
     */
    function updateSections(sections) {
        const container = document.getElementById('preview-sections-container');
        if (!container) return;

        // Clear existing sections
        container.innerHTML = '';

        // Add new sections
        sections.forEach((section, index) => {
            if (!section.title && !section.content) return;

            const sectionEl = document.createElement('div');
            sectionEl.className = 'card-border border-radius-10px p-4 mb-4 preview-section';
            sectionEl.dataset.sectionIndex = index;

            let html = '';

            if (section.title) {
                html += `<p class="font-18 font-weight-bold mb-3 preview-section-title">${escapeHtml(section.title)}</p>`;
            }

            if (section.content) {
                html += `<p class="mb-0 font-16 line-height-relaxed preview-section-content">${escapeHtml(section.content).replace(/\n/g, '<br>')}</p>`;
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

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
