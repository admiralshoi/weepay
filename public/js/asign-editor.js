/**
 * A-Sign Editor - Fabric.js based canvas editor
 */

// Global variables
let canvas;
let cropper = null;
let currentDesignUid = null;
let hasUnsavedChanges = false;
let isDesignType = true;
let currentQrBase64 = null;
let currentSize = 'A1'; // Current selected size
let isInitializing = false; // Flag to ignore changes during initial load

// Display canvas dimensions (scaled for screen, maintains aspect ratio)
const DISPLAY_MAX_WIDTH = 400;

// Initialize editor when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEditor();
});

/**
 * Get current size configuration
 */
function getCurrentSizeConfig() {
    const config = window.editorConfig;
    return config.sizes[currentSize] || config.sizes[config.defaultSize];
}

/**
 * Get display dimensions for canvas (scaled for screen)
 */
function getDisplayDimensions() {
    const sizeConfig = getCurrentSizeConfig();
    const aspectRatio = sizeConfig.heightMm / sizeConfig.widthMm;
    return {
        width: DISPLAY_MAX_WIDTH,
        height: Math.round(DISPLAY_MAX_WIDTH * aspectRatio),
    };
}

/**
 * Initialize the Fabric.js canvas and load existing design if any
 */
function initializeEditor() {
    const config = window.editorConfig;

    // Check if config is available
    if (!config) {
        console.error('editorConfig not found. Make sure it is defined before the script runs.');
        return;
    }

    // Set initial size
    currentSize = config.defaultSize || 'A1';
    const displayDims = getDisplayDimensions();

    // Initialize canvas with display dimensions
    canvas = new fabric.Canvas('asignCanvas', {
        width: displayDims.width,
        height: displayDims.height,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true,
    });

    // Set custom styling for controls
    fabric.Object.prototype.set({
        transparentCorners: false,
        cornerColor: '#2196F3',
        cornerStrokeColor: '#1976D2',
        cornerSize: 10,
        borderColor: '#2196F3',
        rotatingPointOffset: 30,
    });

    // Custom cursors for different control actions
    canvas.defaultCursor = 'default';
    canvas.hoverCursor = 'move';
    canvas.moveCursor = 'move';
    canvas.rotationCursor = 'grab';

    // Set control cursors (Fabric applies these as inline styles)
    fabric.Object.prototype.controls.tl.cursorStyle = 'nwse-resize';
    fabric.Object.prototype.controls.tr.cursorStyle = 'nesw-resize';
    fabric.Object.prototype.controls.bl.cursorStyle = 'nesw-resize';
    fabric.Object.prototype.controls.br.cursorStyle = 'nwse-resize';
    fabric.Object.prototype.controls.ml.cursorStyle = 'ew-resize';
    fabric.Object.prototype.controls.mr.cursorStyle = 'ew-resize';
    fabric.Object.prototype.controls.mt.cursorStyle = 'ns-resize';
    fabric.Object.prototype.controls.mb.cursorStyle = 'ns-resize';
    fabric.Object.prototype.controls.mtr.cursorStyle = 'grab';

    // Set current design UID if editing
    if (!config.isNew && config.design) {
        currentDesignUid = config.design.uid;
        isDesignType = config.design.type === 'design';
        // Load saved size if available
        if (config.design.size && config.sizes[config.design.size]) {
            currentSize = config.design.size;
            document.getElementById('designSize').value = currentSize;
            // Re-initialize canvas with correct dimensions
            const newDims = getDisplayDimensions();
            canvas.setWidth(newDims.width);
            canvas.setHeight(newDims.height);
        }
    } else {
        isDesignType = document.getElementById('designType').value === 'design';
    }

    // Update UI based on type
    updateTypeUI();
    updateSizeInfo();

    // Load existing design or initialize new
    if (!config.isNew && config.design) {
        loadExistingDesign(config.design);
    } else {
        initializeNewDesign();
    }

    // Load inspiration based on type
    loadInspiration();

    // Setup event listeners
    setupEventListeners();

    // Track changes
    canvas.on('object:modified', markUnsavedChanges);
    canvas.on('object:added', function() {
        markUnsavedChanges();
        updateElementList();
    });
    canvas.on('object:removed', function() {
        markUnsavedChanges();
        updateElementList();
    });

    // Update properties panel when object is scaled/modified
    canvas.on('object:modified', function(e) {
        if (e.target && document.getElementById('propertiesPanel').style.display !== 'none') {
            showPropertiesPanel();
        }
    });

    // Selection change for properties panel and element list
    canvas.on('selection:created', function() {
        showPropertiesPanel();
        updateElementListSelection();
    });
    canvas.on('selection:updated', function() {
        showPropertiesPanel();
        updateElementListSelection();
    });
    canvas.on('selection:cleared', function() {
        hidePropertiesPanel();
        updateElementListSelection();
    });

    // Handle responsive scaling
    handleResponsiveCanvas();
    window.addEventListener('resize', debounce(handleResponsiveCanvas, 150));
}

/**
 * Handle size change from dropdown
 */
let ignoreSizeChange = false;
function onSizeChange() {
    if (ignoreSizeChange) return;

    const selectEl = document.getElementById('designSize');
    const newSize = selectEl.value;
    if (newSize === currentSize) return;

    // Warn about losing content
    if (canvas.getObjects().length > 1) { // More than just bottom bar
        const previousSize = currentSize;
        SweetPrompt.confirm(
            'Skift størrelse?',
            'Ændring af størrelse vil nulstille designet. Vil du fortsætte?',
            {
                confirmButtonText: 'Ja, skift størrelse',
                onConfirm: () => {
                    currentSize = newSize;
                    updateCanvasSize();
                    updateSizeInfo();
                    markUnsavedChanges();
                },
                callbackEnd: (result) => {
                    // Only reset if dismissed (cancelled)
                    if (result && result.dismiss) {
                        ignoreSizeChange = true;
                        updateSelectV2Value(selectEl, previousSize);
                        ignoreSizeChange = false;
                    }
                }
            }
        );
        return;
    }

    currentSize = newSize;
    updateCanvasSize();
    updateSizeInfo();
    markUnsavedChanges();
}

/**
 * Update canvas dimensions based on current size
 */
function updateCanvasSize() {
    const displayDims = getDisplayDimensions();

    // Clear canvas
    canvas.clear();
    canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));

    // Resize canvas
    canvas.setWidth(displayDims.width);
    canvas.setHeight(displayDims.height);

    // Re-initialize design elements
    if (isDesignType) {
        addBottomBar();
    }

    // Reset bottom bar section to defaults
    const bottomBarSection = document.getElementById('bottomBarSection');
    if (bottomBarSection) {
        bottomBarSection.style.display = '';
    }

    canvas.renderAll();
    handleResponsiveCanvas();
    updateElementList();
}

/**
 * Update size info display
 */
function updateSizeInfo() {
    const sizeConfig = getCurrentSizeConfig();
    const infoEl = document.getElementById('canvasSizeInfo');
    if (infoEl) {
        infoEl.textContent = `${sizeConfig.widthMm} × ${sizeConfig.heightMm} mm (${currentSize})`;
    }
}

/**
 * Handle responsive canvas scaling for smaller screens
 */
function handleResponsiveCanvas() {
    const frame = document.querySelector('.asign-frame');
    const container = document.querySelector('.canvas-container');

    if (!frame || !container) return;

    const displayDims = getDisplayDimensions();
    const containerWidth = container.clientWidth - 40; // Account for padding
    const frameWidth = displayDims.width + 32; // Canvas + frame border/padding

    if (containerWidth < frameWidth) {
        const scale = containerWidth / frameWidth;
        frame.style.transform = `scale(${scale})`;
        frame.style.transformOrigin = 'center top';
        // Adjust container height to prevent overflow
        const scaledHeight = (displayDims.height + 32) * scale;
        frame.style.marginBottom = `-${(displayDims.height + 32) - scaledHeight}px`;
    } else {
        frame.style.transform = 'none';
        frame.style.marginBottom = '0';
    }
}

/**
 * Sanitize design name for use as filename
 * Replaces spaces with -, keeps a-z, A-Z, 0-9, æøåÆØÅ, and -_
 */
function sanitizeFilename() {
    const name = document.getElementById('designName').value.trim() || 'design';
    return name
        .replace(/\s+/g, '-')
        .replace(/[^a-zA-Z0-9æøåÆØÅ\-_]/g, '')
        .toLowerCase() || 'design';
}

/**
 * Debounce utility function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Initialize a new design with default elements
 */
function initializeNewDesign() {
    if (isDesignType) {
        addBottomBar();
    }
}

/**
 * Load an existing design from saved data
 */
function loadExistingDesign(design) {
    // Set flag to ignore changes during load
    isInitializing = true;

    // Load canvas data if available
    if (design.canvas_data) {
        canvas.loadFromJSON(design.canvas_data, function() {
            canvas.renderAll();
            // Re-identify special elements after load
            identifySpecialElements();
            // Update element list
            updateElementList();
        });
    } else if (design.elements) {
        // Reconstruct from elements configuration
        reconstructFromElements(design.elements, design);
    }

    // Set background image if available
    if (design.background_image) {
        setBackgroundFromUrl(design.background_image);
    }

    // Set logo image if available
    if (design.logo_image) {
        setLogoFromUrl(design.logo_image, !!design.background_image);
    }

    // Initialize bottom bar for design type
    if (isDesignType && !hasBottomBar()) {
        addBottomBar();
        if (design.bar_color) {
            updateBarColor(design.bar_color);
        }
    }

    // Sync location dropdown if design has a location
    if (design.location && design.location.uid) {
        const locationSelect = document.getElementById('designLocation');
        if (locationSelect) {
            locationSelect.value = design.location.uid;
        }
    }

    // Update element list after loading, then clear initializing flag
    setTimeout(() => {
        updateElementList();
        isInitializing = false;
    }, 500);
}


/**
 * Reconstruct canvas from elements configuration
 */
function reconstructFromElements(elements, design) {
    // Add bottom bar first for design type
    if (isDesignType) {
        addBottomBar();
        if (design.bar_color) {
            updateBarColor(design.bar_color);
        }
        if (elements.bar && elements.bar.text) {
            updateBarText(elements.bar.text);
        }
    }

    // Add text elements
    if (elements.text_elements) {
        elements.text_elements.forEach(function(textEl) {
            const text = new fabric.IText(textEl.text || 'Tekst', {
                left: textEl.x || 50,
                top: textEl.y || 50,
                fontSize: textEl.fontSize || 24,
                fontFamily: textEl.fontFamily || 'Helvetica',
                fill: textEl.fill || '#333333',
                fontStyle: textEl.fontStyle || 'normal',
                fontWeight: textEl.fontWeight || 'normal',
                elementType: 'text',
                elementId: textEl.id,
            });
            canvas.add(text);
        });
    }

    // Add QR code
    if (elements.qr_code) {
        addQrCodeAtPosition(elements.qr_code.x, elements.qr_code.y, elements.qr_code.size);
    }

    // Add badge
    if (elements.badge && elements.badge.visible) {
        addBadgeAtPosition(elements.badge);
    }

    canvas.renderAll();
}

/**
 * Check if canvas has bottom bar
 */
function hasBottomBar() {
    return canvas.getObjects().some(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barBackground');
}

/**
 * Bring bottom bar elements to front
 */
function bringBottomBarToFront() {
    const barObjects = canvas.getObjects().filter(obj => obj.elementType === 'bottomBar');
    barObjects.forEach(obj => canvas.bringToFront(obj));
}

/**
 * Identify special elements after loading from JSON
 */
function identifySpecialElements() {
    // Elements retain their custom properties when loaded from JSON
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Delete key
        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                deleteSelected();
                e.preventDefault();
            }
        }

        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveDesign(true);
        }
    });

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

/**
 * Mark that there are unsaved changes
 */
function markUnsavedChanges() {
    // Ignore during initial load
    if (isInitializing) return;

    hasUnsavedChanges = true;
    document.getElementById('saveDraftBtn').classList.add('has-changes');
    // Disable export button when there are unsaved changes
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.disabled = true;
    }
}

/**
 * Clear unsaved changes flag
 */
function clearUnsavedChanges() {
    hasUnsavedChanges = false;
    document.getElementById('saveDraftBtn').classList.remove('has-changes');
    // Enable export button after save (only if design has been saved at least once)
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn && currentDesignUid) {
        exportBtn.disabled = false;
    }
}

// ==================== TYPE HANDLING ====================

/**
 * Handle design type change
 */
function onTypeChange() {
    const newType = document.getElementById('designType').value;
    const wasDesignType = isDesignType;
    isDesignType = newType === 'design';

    updateTypeUI();

    // Add/remove bottom bar based on type
    if (isDesignType && !wasDesignType) {
        addBottomBar();
    } else if (!isDesignType && wasDesignType) {
        removeBottomBar();
    }

    // Update inspiration
    loadInspiration();

    markUnsavedChanges();
}

/**
 * Update UI visibility based on design type
 */
function updateTypeUI() {
    const designOnlyElements = document.querySelectorAll('.design-type-only');
    const arbitraryOnlyElements = document.querySelectorAll('.arbitrary-type-only');

    designOnlyElements.forEach(el => {
        el.style.display = isDesignType ? '' : 'none';
    });

    arbitraryOnlyElements.forEach(el => {
        el.style.display = isDesignType ? 'none' : '';
    });
}

// ==================== BOTTOM BAR (Design Type) ====================

/**
 * Add the bottom bar group for design type
 */
function addBottomBar() {
    const config = window.editorConfig;
    const displayDims = getDisplayDimensions();
    const barHeight = displayDims.height * (config.barHeightPercent / 100);
    const barY = displayDims.height - barHeight;
    const barColor = document.getElementById('barColor')?.value || '#8B4513';

    // Remove existing bar first
    removeBottomBar();

    // Create bar background as a separate object (not grouped)
    const barBg = new fabric.Rect({
        left: 0,
        top: barY,
        width: displayDims.width,
        height: barHeight,
        fill: barColor,
        selectable: false,
        evented: false,
        elementType: 'bottomBar',
        elementId: 'barBackground',
    });

    // Create bar text as a separate object
    // Defaults: Verdana, 60pt, 60 char spacing
    const barFontSize = printToDisplayFontSize(60);
    const barText = new fabric.IText(' ', {
        left: displayDims.width / 2,
        top: barY + barHeight / 2,
        fontSize: barFontSize,
        fontFamily: 'Verdana',
        fontWeight: 'bold',
        fill: '#ffffff',
        originX: 'center',
        originY: 'center',
        textAlign: 'center',
        selectable: true,
        evented: true,
        elementType: 'bottomBar',
        elementId: 'barText',
        charSpacing: 60,
    });

    canvas.add(barBg);
    canvas.add(barText);

    // Bring to front so it's always visible above background
    canvas.bringToFront(barBg);
    canvas.bringToFront(barText);
    canvas.renderAll();

    console.log('Bottom bar added:', {barY, barHeight, barColor, displayDims});
}

/**
 * Remove the bottom bar
 */
function removeBottomBar() {
    const barObjects = canvas.getObjects().filter(obj => obj.elementType === 'bottomBar');
    barObjects.forEach(obj => canvas.remove(obj));
    if (barObjects.length > 0) {
        canvas.renderAll();
    }
}

/**
 * Update bottom bar color
 */
function updateBarColor(color) {
    // Validate hex color
    if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
        color = '#8B4513';
    }

    // Sync inputs
    document.getElementById('barColor').value = color;
    document.getElementById('barColorHex').value = color;

    // Find and update the bar background color
    const barBg = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barBackground');
    if (barBg) {
        barBg.set('fill', color);
        canvas.renderAll();
    }

    markUnsavedChanges();
}

/**
 * Update bottom bar text
 */
function updateBarText(text, syncInput = true) {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        barText.set('text', text);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

/**
 * Update bottom bar text from properties panel
 */
function updateBottomBarText(text) {
    updateBarText(text);
}

/**
 * Update bottom bar font size from properties panel
 */
function updateBottomBarFontSize(printPt) {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        const displaySize = printToDisplayFontSize(parseFloat(printPt));
        // Reset scale and set new font size
        barText.set({
            fontSize: displaySize,
            scaleX: 1,
            scaleY: 1
        });
        canvas.renderAll();
        markUnsavedChanges();
    }
}

/**
 * Update bottom bar font size and sync both inputs
 */
function updateBottomBarFontSizeWithSync(printPt) {
    updateBottomBarFontSize(printPt);
    // Sync both inputs
    const rangeEl = document.getElementById('barFontSizeRange');
    const numberEl = document.getElementById('barFontSizeNumber');
    if (rangeEl) rangeEl.value = printPt;
    if (numberEl) numberEl.value = printPt;
}

/**
 * Update bottom bar char spacing and sync both inputs
 */
function updateBottomBarCharSpacingWithSync(value) {
    updateBottomBarProperty('charSpacing', value);
    // Sync both inputs
    const rangeEl = document.getElementById('barCharSpacingRange');
    const numberEl = document.getElementById('barCharSpacingNumber');
    if (rangeEl) rangeEl.value = value;
    if (numberEl) numberEl.value = value;
}

/**
 * Update bottom bar property
 */
function updateBottomBarProperty(prop, value) {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        barText.set(prop, value);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

/**
 * Toggle bottom bar font weight
 */
function toggleBottomBarFontWeight() {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        const newWeight = barText.fontWeight === 'bold' ? 'normal' : 'bold';
        barText.set('fontWeight', newWeight);
        canvas.renderAll();
        markUnsavedChanges();
        showPropertiesPanel();
    }
}

/**
 * Toggle bottom bar font style
 */
function toggleBottomBarFontStyle() {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        const newStyle = barText.fontStyle === 'italic' ? 'normal' : 'italic';
        barText.set('fontStyle', newStyle);
        canvas.renderAll();
        markUnsavedChanges();
        showPropertiesPanel();
    }
}

/**
 * Delete bottom bar text element
 */
function deleteBottomBarText() {
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');
    if (barText) {
        canvas.remove(barText);
        canvas.discardActiveObject();
        canvas.renderAll();
        markUnsavedChanges();

        // Hide the bottom bar section
        const bottomBarSection = document.getElementById('bottomBarSection');
        if (bottomBarSection) {
            bottomBarSection.style.display = 'none';
        }

        // Hide properties panel since element is gone
        hidePropertiesPanel();

        // Update element list
        updateElementList();
    }
}

// ==================== BACKGROUND ====================

/**
 * Trigger file input for background upload
 */
function triggerBackgroundUpload() {
    document.getElementById('backgroundUpload').click();
}

/**
 * Handle background image upload
 */
function handleBackgroundUpload(input) {
    if (!input.files || !input.files[0]) return;
    screenLoader.show();

    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        screenLoader.hide();
        document.getElementById('cropImage').src = e.target.result;
        $('#cropModal').modal('show');
        $('#cropModal').one('shown.bs.modal', function() {
            initCropper();
        });
    };

    reader.onerror = function() {
        screenLoader.hide();
        showErrorNotification('Kunne ikke læse billedet. Prøv igen.');
    };

    reader.readAsDataURL(file);
}

/**
 * Initialize Cropper.js
 */
function initCropper() {
    if (cropper) {
        cropper.destroy();
    }

    const sizeConfig = getCurrentSizeConfig();

    // Calculate background area (85% of height for design type with bottom bar)
    const bgHeightPercent = isDesignType ? 0.85 : 1;
    const aspectRatio = sizeConfig.widthMm / (sizeConfig.heightMm * bgHeightPercent);

    // Required minimum dimensions at 300 DPI for print quality
    const minWidth = sizeConfig.widthPx;
    const minHeight = Math.round(sizeConfig.heightPx * bgHeightPercent);

    const image = document.getElementById('cropImage');

    // Update recommended size display
    const recommendedEl = document.querySelector('#cropModal .color-muted:last-child');
    if (recommendedEl) {
        recommendedEl.textContent = `Anbefalet: min. ${minWidth}×${minHeight}px`;
    }

    cropper = new Cropper(image, {
        aspectRatio: aspectRatio,
        viewMode: 2,           // Restrict crop box to not exceed image bounds
        dragMode: 'move',      // Move the image by default
        movable: true,         // Allow moving the image
        zoomable: true,        // Allow zooming
        scalable: false,       // Don't allow scaling (use zoom instead)
        autoCropArea: 1,       // Start with full crop area
        responsive: true,
        background: true,
        guides: true,
        center: true,
        highlight: true,
        minCropBoxWidth: 50,   // Minimum visual crop box size
        minCropBoxHeight: 50,
        ready: function() {
            // Check if image is large enough
            const imageData = cropper.getImageData();

            // If image is too small, show warning
            if (imageData.naturalWidth < minWidth || imageData.naturalHeight < minHeight) {
                showWarningNotification(
                    `Billedet er mindre end anbefalet (${minWidth}×${minHeight}px). Kvaliteten kan blive reduceret ved print.`
                );
            }
        },
        crop: function(event) {
            // Update info display
            const cropData = event.detail;
            updateCropInfo(cropData, minWidth, minHeight);
        }
    });
}

/**
 * Update crop info display
 */
function updateCropInfo(cropData, minWidth, minHeight) {
    const infoEl = document.getElementById('cropInfo');
    if (infoEl && cropData) {
        const width = Math.round(cropData.width);
        const height = Math.round(cropData.height);
        const isOk = width >= minWidth && height >= minHeight;
        const statusIcon = isOk ? '✓' : '⚠';
        const statusClass = isOk ? 'color-success' : 'color-warning';
        infoEl.innerHTML = `<span class="${statusClass}">${statusIcon}</span> Udsnit: ${width} × ${height}px`;
    }
}

/**
 * Show warning notification
 */
function showWarningNotification(message) {
    if (typeof showNotification === 'function') {
        showNotification(message, 'warning');
    } else {
        console.warn(message);
    }
}

/**
 * Apply cropped image as background
 */
function applyCrop() {
    if (!cropper) return;

    const btn = document.querySelector('#cropModal .btn-v2.action-btn');
    const originalText = btn.innerHTML;
    const spinButton = async () => {
        // Spin the button
        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Behandler...';
    }
    const stopSpin = () => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }

    function processCrop() {
        const sizeConfig = getCurrentSizeConfig();
        const displayDims = getDisplayDimensions();

        const bgHeightPercent = isDesignType ? 0.85 : 1;
        const targetWidth = sizeConfig.widthPx;
        const targetHeight = Math.round(sizeConfig.heightPx * bgHeightPercent);

        const croppedCanvas = cropper.getCroppedCanvas({
            width: targetWidth,
            height: targetHeight,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!croppedCanvas) {
            stopSpin();
            showErrorNotification('Kunne ikke beskære billedet. Prøv igen.');
            return;
        }

        const dataUrl = croppedCanvas.toDataURL('image/jpeg', 0.92);

        fabric.Image.fromURL(dataUrl, function(img) {
            img.scaleToWidth(displayDims.width);
            img.set({
                left: 0,
                top: 0,
                selectable: false,
                evented: false,
                elementType: 'background',
            });

            removeBackground();
            canvas.add(img);
            canvas.sendToBack(img);

            // Bring all content elements to front of background
            canvas.getObjects().forEach(obj => {
                if (obj.elementType && obj.elementType !== 'background') {
                    canvas.bringToFront(obj);
                }
            });

            if (isDesignType) {
                bringBottomBarToFront();
            }

            canvas.renderAll();
            markUnsavedChanges();

            stopSpin();
            $('#cropModal').modal('hide');
            cropper.destroy();
            cropper = null;

            if (currentDesignUid) {
                uploadBackgroundToServer(dataUrl);
            }
        });
    }

    spinButton()
        .then(() => {
            setTimeout(() => {

                const sizeConfig = getCurrentSizeConfig();

                const bgHeightPercent = isDesignType ? 0.85 : 1;
                const targetWidth = sizeConfig.widthPx;
                const targetHeight = Math.round(sizeConfig.heightPx * bgHeightPercent);

                const cropData = cropper.getData();
                const croppedWidth = cropData.width;
                const croppedHeight = cropData.height;

                if (croppedWidth < targetWidth * 0.5 || croppedHeight < targetHeight * 0.5) {
                    stopSpin();
                    SweetPrompt.confirm(
                        'Lille udsnit',
                        'Det valgte udsnit er meget lille og kan blive utydeligt ved print. Vil du fortsætte?',
                        {
                            confirmButtonText: 'Ja, fortsæt',
                            onConfirm: () => {
                                spinButton().then(() => {
                                    setTimeout(processCrop, 100);
                                });
                            },
                            onCancel: () => {
                                // Do nothing, user cancelled
                            }
                        }
                    );
                    return;
                }

                processCrop();
            }, 500)

        })

}

/**
 * Set background from URL
 */
function setBackgroundFromUrl(url) {
    const displayDims = getDisplayDimensions();

    fabric.Image.fromURL(url, function(img) {
        img.scaleToWidth(displayDims.width);
        img.set({
            left: 0,
            top: 0,
            selectable: false,
            evented: false,
            elementType: 'background',
        });

        // Remove existing background first
        removeBackground();

        canvas.add(img);
        canvas.sendToBack(img);

        // Bring all content elements to front (in correct order)
        bringAllElementsToFront();

        canvas.renderAll();

        // Load pending logo after background is done
        if (pendingLogoUrl) {
            loadLogoImage(pendingLogoUrl);
            pendingLogoUrl = null;
        }
    }, { crossOrigin: 'anonymous' });
}

/**
 * Bring all content elements to front in correct z-order
 */
function bringAllElementsToFront() {
    // Order: background (back) -> bottomBar -> shapes -> text -> qrCode -> badge -> logo (front)
    // Logo should be on TOP of bottom bar since it sits in that area

    // Bottom bar first (above background but below content)
    if (isDesignType) {
        bringBottomBarToFront();
    }

    // Then content elements
    const order = ['shape', 'text', 'qrCode', 'badge', 'logo'];

    order.forEach(type => {
        canvas.getObjects().forEach(obj => {
            if (obj.elementType === type) {
                canvas.bringToFront(obj);
            }
        });
    });
}

/**
 * Remove background image
 */
function removeBackground() {
    const bgImage = canvas.getObjects().find(obj => obj.elementType === 'background');
    if (bgImage) {
        canvas.remove(bgImage);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

/**
 * Upload background to server
 */
function uploadBackgroundToServer(dataUrl) {
    // Convert data URL to blob
    fetch(dataUrl)
        .then(res => res.blob())
        .then(blob => {
            const formData = new FormData();
            formData.append('image', blob, 'background.jpg');
            formData.append('uid', currentDesignUid);

            post('api/merchant/asign/upload-background', formData)
            .then(result => {
                if (result.status === 'error') {
                    console.error('Failed to upload background:', result.error?.message);
                }
            })
            .catch(err => console.error('Background upload error:', err));
        });
}

/**
 * Upload logo to server
 */
function uploadLogoToServer(dataUrl) {
    if (!currentDesignUid) return;

    // Convert data URL to blob
    fetch(dataUrl)
        .then(res => res.blob())
        .then(blob => {
            const formData = new FormData();
            formData.append('image', blob, 'logo.png');
            formData.append('uid', currentDesignUid);

            post('api/merchant/asign/upload-logo', formData)
            .then(result => {
                if (result.status === 'error') {
                    console.error('Failed to upload logo:', result.error?.message);
                }
            })
            .catch(err => console.error('Logo upload error:', err));
        });
}

// ==================== ELEMENTS ====================

/**
 * Get scale factor from display to print resolution
 */
function getScaleFactor() {
    const sizeConfig = getCurrentSizeConfig();
    const displayDims = getDisplayDimensions();
    return sizeConfig.widthPx / displayDims.width;
}

/**
 * Convert print font size (pt) to display font size (px)
 * Print sizes are in points at 300 DPI
 */
function printToDisplayFontSize(printPt) {
    const scaleFactor = getScaleFactor();
    // At 300 DPI, 1pt = 4.17px, but we scale down for display
    return Math.round((printPt * 4.17) / scaleFactor);
}

/**
 * Convert display font size to approximate print font size
 */
function displayToPrintFontSize(displayPx) {
    const scaleFactor = getScaleFactor();
    return Math.round((displayPx * scaleFactor) / 4.17);
}

/**
 * Add a text element
 */
function addTextElement() {
    const displayDims = getDisplayDimensions();

    // Default to 75pt print size, Verdana, normal weight/style
    const defaultDisplayFontSize = printToDisplayFontSize(75);

    const text = new fabric.IText('', {
        left: displayDims.width / 2,
        top: 100,
        fontSize: defaultDisplayFontSize,
        fontFamily: 'Verdana',
        fontWeight: 'normal',
        fontStyle: 'normal',
        fill: '#333333',
        originX: 'center',
        elementType: 'text',
        lockUniScaling: true, // Prevent stretching text
        charSpacing: 0, // Letter spacing (in 1/1000 em)
    });

    canvas.add(text);
    canvas.bringToFront(text);
    canvas.setActiveObject(text);
    canvas.renderAll();

    // Scroll properties panel to top and focus text input
    setTimeout(() => {
        const propertiesPanel = document.getElementById('propertiesPanel');
        if (propertiesPanel) {
            propertiesPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        const textInput = document.querySelector('#propertiesContent input[type="text"]');
        if (textInput) {
            textInput.focus();
            textInput.select();
        }
    }, 50);
}

/**
 * Add QR code element
 */
function addQrCode() {
    const locationSelect = document.getElementById('designLocation');
    const locationUid = locationSelect.value;

    if (!locationUid) {
        showErrorNotification('Vælg en lokation først for at generere QR-kode');
        return;
    }

    screenLoader.show();

    get('api/merchant/asign/generate-qr', { location_uid: locationUid })
        .then(result => {
            screenLoader.hide();
            if (result.status === 'error') {
                showErrorNotification(result.error?.message || 'Kunne ikke generere QR-kode');
                return;
            }
            currentQrBase64 = result.data?.qr_base64 || result.result?.qr_base64;
            addQrCodeAtPosition(50, 380, 100);
        })
        .catch(err => {
            screenLoader.hide();
            console.error('QR generation error:', err);
            showErrorNotification('Fejl ved generering af QR-kode');
        });
}

/**
 * Add QR code at specific position
 */
function addQrCodeAtPosition(x, y, size) {
    if (!currentQrBase64) return;

    const qrUrl = currentQrBase64.startsWith('data:') ? currentQrBase64 : `data:image/png;base64,${currentQrBase64}`;

    fabric.Image.fromURL(qrUrl, function(img) {
        img.scaleToWidth(size || 100);
        img.set({
            left: x || 50,
            top: y || 380,
            elementType: 'qrCode',
        });

        canvas.add(img);
        canvas.setActiveObject(img);
        canvas.renderAll();
    });
}

/**
 * Handle location change
 */
function onLocationChange() {
    // Reset QR if location changes
    currentQrBase64 = null;

    // Remove existing QR codes
    const existingQr = canvas.getObjects().find(obj => obj.elementType === 'qrCode');
    if (existingQr) {
        canvas.remove(existingQr);
        canvas.renderAll();
    }

    markUnsavedChanges();
}

/**
 * Add logo element
 */
function addLogo() {
    $('#logoModal').modal('show');
}

/**
 * Handle logo upload
 */
function handleLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    screenLoader.show();

    const file = input.files[0];
    $('#logoModal').modal('hide');
    input.value = '';

    const reader = new FileReader();

    reader.onload = function(e) {
        fabric.Image.fromURL(e.target.result, function(img) {
            const displayDims = getDisplayDimensions();
            const maxWidth = 100;
            if (img.width > maxWidth) {
                img.scaleToWidth(maxWidth);
            }

            img.set({
                left: 20,
                top: isDesignType ? displayDims.height * 0.85 + 10 : displayDims.height - 60,
                elementType: 'logo',
            });

            canvas.add(img);
            canvas.setActiveObject(img);
            canvas.renderAll();
            markUnsavedChanges();
            screenLoader.hide();
        });
    };

    reader.onerror = function() {
        screenLoader.hide();
        showErrorNotification('Kunne ikke læse billedet. Prøv igen.');
    };

    reader.readAsDataURL(file);
}

/**
 * Set logo from URL (when loading existing design)
 */
function setLogoFromUrl(url, hasBackground) {
    if (hasBackground) {
        // Store the URL, logo will be loaded after background
        pendingLogoUrl = url;
    } else {
        // No background, load logo directly
        loadLogoImage(url);
    }
}

/**
 * Actually load the logo image
 */
function loadLogoImage(url) {
    const displayDims = getDisplayDimensions();

    fabric.Image.fromURL(url, function(img) {
        const maxWidth = 100;
        if (img.width > maxWidth) {
            img.scaleToWidth(maxWidth);
        }

        img.set({
            left: 20,
            top: isDesignType ? displayDims.height * 0.85 + 10 : displayDims.height - 60,
            elementType: 'logo',
        });

        // Remove existing logo first
        const existingLogo = canvas.getObjects().find(obj => obj.elementType === 'logo');
        if (existingLogo) {
            canvas.remove(existingLogo);
        }

        canvas.add(img);
        canvas.bringToFront(img);

        if (isDesignType) {
            bringBottomBarToFront();
        }

        canvas.renderAll();
    }, { crossOrigin: 'anonymous' });
}

// Store pending logo URL to load after background
let pendingLogoUrl = null;

/**
 * Add badge element (Design type only)
 */
function addBadge() {
    const displayDims = getDisplayDimensions();

    // Use 25pt print size for badge text, Verdana, normal weight/style
    const badgeFontSize = printToDisplayFontSize(25);
    const badgeWidth = badgeFontSize * 10;
    const badgeHeight = badgeFontSize * 3;

    // Create badge background (rounded rect)
    const badgeBg = new fabric.Rect({
        width: badgeWidth,
        height: badgeHeight,
        rx: badgeHeight / 4,
        ry: badgeHeight / 4,
        fill: '#D2691E',
        originX: 'center',
        originY: 'center',
    });

    // Create badge text
    const badgeText = new fabric.Text('', {
        fontSize: badgeFontSize,
        fontFamily: 'Verdana',
        fontWeight: 'normal',
        fontStyle: 'normal',
        fill: '#ffffff',
        originX: 'center',
        originY: 'center',
        charSpacing: 0,
    });

    // Group them
    const badge = new fabric.Group([badgeBg, badgeText], {
        left: displayDims.width - badgeWidth / 2 - 20,
        top: 80,
        elementType: 'badge',
        lockUniScaling: true,
    });

    canvas.add(badge);
    canvas.bringToFront(badge);
    canvas.setActiveObject(badge);
    canvas.renderAll();
}

/**
 * Add badge at specific position
 */
function addBadgeAtPosition(badgeConfig) {
    // Use saved font size or default to ~36pt print size
    const badgeFontSize = badgeConfig.fontSize || printToDisplayFontSize(36);
    const badgeWidth = badgeConfig.width || badgeFontSize * 8;
    const badgeHeight = badgeConfig.height || badgeFontSize * 2.5;
    const borderRadius = badgeConfig.rx || badgeHeight / 4;

    const badgeBg = new fabric.Rect({
        width: badgeWidth,
        height: badgeHeight,
        rx: borderRadius,
        ry: borderRadius,
        fill: badgeConfig.fill || '#D2691E',
        originX: 'center',
        originY: 'center',
    });

    const badgeText = new fabric.Text(badgeConfig.text || 'INGEN RENTER', {
        fontSize: badgeFontSize,
        fontFamily: 'Helvetica',
        fontWeight: 'bold',
        fill: badgeConfig.textColor || '#ffffff',
        originX: 'center',
        originY: 'center',
    });

    const badge = new fabric.Group([badgeBg, badgeText], {
        left: badgeConfig.x || 280,
        top: badgeConfig.y || 80,
        elementType: 'badge',
        lockUniScaling: true,
    });

    canvas.add(badge);
    canvas.bringToFront(badge);
    canvas.renderAll();
}

/**
 * Add shape element (Arbitrary type only)
 */
function addShape(type) {
    const config = window.editorConfig;
    let shape;

    if (type === 'rect') {
        shape = new fabric.Rect({
            left: config.canvasWidth / 2 - 50,
            top: config.canvasHeight / 2 - 30,
            width: 100,
            height: 60,
            fill: 'rgba(100, 100, 100, 0.5)',
            stroke: '#333333',
            strokeWidth: 1,
            rx: 4,
            ry: 4,
            elementType: 'shape',
            shapeType: 'rect',
        });
    } else if (type === 'circle') {
        shape = new fabric.Circle({
            left: config.canvasWidth / 2 - 40,
            top: config.canvasHeight / 2 - 40,
            radius: 40,
            fill: 'rgba(100, 100, 100, 0.5)',
            stroke: '#333333',
            strokeWidth: 1,
            elementType: 'shape',
            shapeType: 'circle',
        });
    }

    if (shape) {
        canvas.add(shape);
        canvas.setActiveObject(shape);
        canvas.renderAll();
    }
}

// ==================== LAYER CONTROLS ====================

function bringForward() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType !== 'bottomBar') {
        canvas.bringForward(activeObject);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function sendBackward() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType !== 'bottomBar') {
        canvas.sendBackwards(activeObject);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function bringToFront() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType !== 'bottomBar') {
        canvas.bringToFront(activeObject);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function sendToBack() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType !== 'bottomBar' && activeObject.elementType !== 'background') {
        canvas.sendToBack(activeObject);
        // Keep background at the very back
        const bg = canvas.getObjects().find(obj => obj.elementType === 'background');
        if (bg) canvas.sendToBack(bg);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function deleteSelected() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType !== 'bottomBar') {
        canvas.remove(activeObject);
        canvas.discardActiveObject();
        canvas.renderAll();
        markUnsavedChanges();
    }
}

// ==================== PROPERTIES PANEL ====================

function showPropertiesPanel() {
    const activeObject = canvas.getActiveObject();
    if (!activeObject) return;

    const panel = document.getElementById('propertiesPanel');
    const content = document.getElementById('propertiesContent');

    panel.style.display = 'block';
    document.getElementById('inspirationPanel').style.display = 'none';

    // Build properties based on element type
    let html = '';

    if (activeObject.elementType === 'bottomBar' && activeObject.elementId === 'barText') {
        html = buildBottomBarTextProperties(activeObject);
    } else if (activeObject.type === 'i-text' || activeObject.type === 'text') {
        html = buildTextProperties(activeObject);
    } else if (activeObject.elementType === 'shape') {
        html = buildShapeProperties(activeObject);
    } else if (activeObject.elementType === 'badge') {
        html = buildBadgeProperties(activeObject);
    } else if (activeObject.elementType === 'qrCode' || activeObject.elementType === 'logo') {
        html = buildImageProperties(activeObject);
    }

    content.innerHTML = html;

    // Initialize selectV2 for any dynamically added selects
    content.querySelectorAll('.form-select-v2').forEach(select => {
        selectV2(select);
    });
}

function hidePropertiesPanel() {
    document.getElementById('propertiesPanel').style.display = 'none';
    document.getElementById('inspirationPanel').style.display = 'block';
}

/**
 * Store elements for reliable reference
 */
let elementListItems = [];

/**
 * Update the element list in sidebar
 */
function updateElementList() {
    const listContainer = document.getElementById('elementList');
    if (!listContainer) return;

    // Get all user-added elements (exclude bottom bar background)
    elementListItems = canvas.getObjects().filter(obj => {
        if (!obj.elementType) return false;
        if (obj.elementType === 'bottomBar' && obj.elementId === 'barBackground') return false;
        return true;
    });

    if (elementListItems.length === 0) {
        listContainer.innerHTML = '<p class="font-13 color-gray mb-0">Ingen elementer tilføjet</p>';
        return;
    }

    const activeObject = canvas.getActiveObject();
    let html = '';

    elementListItems.forEach((obj, index) => {
        const isSelected = activeObject === obj;
        const typeInfo = getElementTypeInfo(obj);
        const isBackground = obj.elementType === 'background';
        const canDelete = !isBackground;

        html += `
            <div class="element-list-item ${isSelected ? 'selected' : ''} ${!obj.selectable ? 'non-selectable' : ''}"
                 onclick="${obj.selectable ? `selectElementFromList(${index})` : ''}"
                 data-element-index="${index}">
                <span class="element-name">
                    <i class="mdi ${typeInfo.icon}"></i>
                    ${typeInfo.name}
                </span>
                ${canDelete ? `
                <button type="button" class="element-delete" onclick="event.stopPropagation(); deleteElementFromList(${index})" title="Slet">
                    <i class="mdi mdi-close"></i>
                </button>
                ` : ''}
            </div>
        `;
    });

    listContainer.innerHTML = html;
}

/**
 * Get element type info (icon and display name)
 */
function getElementTypeInfo(obj) {
    const typeMap = {
        'text': { icon: 'mdi-format-text', name: 'Tekst' },
        'qrCode': { icon: 'mdi-qrcode', name: 'QR-kode' },
        'logo': { icon: 'mdi-image-area', name: 'Logo' },
        'badge': { icon: 'mdi-tag', name: 'Badge' },
        'shape': { icon: 'mdi-shape-outline', name: 'Form' },
        'bottomBar': { icon: 'mdi-dock-bottom', name: 'Bundbjælke tekst' },
        'background': { icon: 'mdi-image', name: 'Baggrund' },
    };

    const type = obj.elementType || 'unknown';
    return typeMap[type] || { icon: 'mdi-help-circle-outline', name: 'Element' };
}

/**
 * Select element from the stored list
 */
function selectElementFromList(index) {
    const obj = elementListItems[index];
    if (obj && canvas.getObjects().includes(obj)) {
        canvas.setActiveObject(obj);
        canvas.renderAll();
    }
}

/**
 * Delete element from the stored list
 */
function deleteElementFromList(index) {
    const obj = elementListItems[index];
    if (obj && canvas.getObjects().includes(obj)) {
        // If deleting bottom bar text, reset the input field and hide the section
        // If deleting bottom bar text, hide the section
        if (obj.elementType === 'bottomBar' && obj.elementId === 'barText') {
            const bottomBarSection = document.getElementById('bottomBarSection');
            if (bottomBarSection) {
                bottomBarSection.style.display = 'none';
            }
        }
        canvas.remove(obj);
        canvas.renderAll();
    }
}

/**
 * Update selection state in element list
 */
function updateElementListSelection() {
    const listContainer = document.getElementById('elementList');
    if (!listContainer) return;

    const activeObject = canvas.getActiveObject();
    const items = listContainer.querySelectorAll('.element-list-item');

    items.forEach((item, index) => {
        const isSelected = elementListItems[index] === activeObject;
        item.classList.toggle('selected', isSelected);
    });
}

function buildBottomBarTextProperties(obj) {
    // Account for object scale when showing font size
    const visualFontSize = obj.fontSize * (obj.scaleY || 1);
    const printSize = displayToPrintFontSize(visualFontSize);

    return `
        <div class="mb-2">
            <span class="font-13 font-weight-bold">Bundbjælke tekst</span>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Tekst</label>
            <input type="text" class="form-field-v2 w-100" value="${obj.text?.trim() || ''}"
                   onchange="updateBottomBarText(this.value)"
                   oninput="updateBottomBarText(this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skriftstørrelse (pt)</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" id="barFontSizeRange" class="form-range" style="flex: 1;" min="12" max="200" value="${printSize}"
                       oninput="updateBottomBarFontSizeWithSync(this.value)">
                <input type="number" id="barFontSizeNumber" class="form-field-v2" style="width: 70px;" value="${printSize}" min="8" max="300"
                       onchange="updateBottomBarFontSizeWithSync(this.value)">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skrifttype</label>
            <select class="form-select-v2 w-100 h-40px" onchange="updateBottomBarProperty('fontFamily', this.value)">
                <optgroup label="Sans-serif">
                    <option value="Helvetica" ${obj.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option>
                    <option value="Arial" ${obj.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option>
                    <option value="Verdana" ${obj.fontFamily === 'Verdana' ? 'selected' : ''}>Verdana</option>
                    <option value="Trebuchet MS" ${obj.fontFamily === 'Trebuchet MS' ? 'selected' : ''}>Trebuchet MS</option>
                    <option value="Impact" ${obj.fontFamily === 'Impact' ? 'selected' : ''}>Impact</option>
                </optgroup>
                <optgroup label="Serif">
                    <option value="Georgia" ${obj.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
                    <option value="Times New Roman" ${obj.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option>
                    <option value="Palatino" ${obj.fontFamily === 'Palatino' ? 'selected' : ''}>Palatino</option>
                </optgroup>
                <optgroup label="Display">
                    <option value="Comic Sans MS" ${obj.fontFamily === 'Comic Sans MS' ? 'selected' : ''}>Comic Sans MS</option>
                    <option value="Brush Script MT" ${obj.fontFamily === 'Brush Script MT' ? 'selected' : ''}>Brush Script MT</option>
                </optgroup>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Stil</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <button type="button" class="btn-v2 mute-btn ${obj.fontWeight === 'bold' ? 'active' : ''}"
                        onclick="toggleBottomBarFontWeight()" title="Fed"><b>B</b></button>
                <button type="button" class="btn-v2 mute-btn ${obj.fontStyle === 'italic' ? 'active' : ''}"
                        onclick="toggleBottomBarFontStyle()" title="Kursiv"><i>I</i></button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Bogstavafstand</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" id="barCharSpacingRange" class="form-range" style="flex: 1;" min="-200" max="800" value="${obj.charSpacing || 0}"
                       oninput="updateBottomBarCharSpacingWithSync(parseInt(this.value))">
                <input type="number" id="barCharSpacingNumber" class="form-field-v2" style="width: 70px;" value="${obj.charSpacing || 0}" min="-500" max="2000"
                       onchange="updateBottomBarCharSpacingWithSync(parseInt(this.value))">
            </div>
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-v2 danger-btn w-100" onclick="deleteBottomBarText()">
                <i class="mdi mdi-delete me-1"></i> Slet bundbjælke tekst
            </button>
        </div>
    `;
}

function buildTextProperties(obj) {
    // Account for object scale when showing font size
    // The visual font size = actual fontSize * scaleY
    const visualFontSize = obj.fontSize * (obj.scaleY || 1);
    const printSize = displayToPrintFontSize(visualFontSize);

    return `
        <div class="mb-3">
            <label class="form-label font-13">Tekst</label>
            <input type="text" class="form-field-v2 w-100" value="${obj.text || ''}"
                   onchange="updateActiveProperty('text', this.value)"
                   oninput="updateActiveProperty('text', this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skriftstørrelse (pt)</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" class="form-range" style="flex: 1;" min="24" max="600" value="${printSize}"
                       oninput="updateTextFontSizePrint(this.value)">
                <input type="number" class="form-field-v2" style="width: 70px;" value="${printSize}" min="12" max="1000"
                       onchange="updateTextFontSizePrint(this.value)">
            </div>
            <small class="text-muted font-11">72pt = ~1 tomme høj, 300pt = overskrift</small>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Forudindstillet størrelse</label>
            <div class="flex-row-center flex-wrap" style="gap: .25rem;">
                <button type="button" class="btn-v2 mute-btn font-11" onclick="setTextPresetPrint('headline')">Overskrift (300pt)</button>
                <button type="button" class="btn-v2 mute-btn font-11" onclick="setTextPresetPrint('subheadline')">Underoverskrift (150pt)</button>
                <button type="button" class="btn-v2 mute-btn font-11" onclick="setTextPresetPrint('body')">Brødtekst (72pt)</button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skrifttype</label>
            <select class="form-select-v2 w-100 h-40px" onchange="updateActiveProperty('fontFamily', this.value)">
                <optgroup label="Sans-serif">
                    <option value="Helvetica" ${obj.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option>
                    <option value="Arial" ${obj.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option>
                    <option value="Verdana" ${obj.fontFamily === 'Verdana' ? 'selected' : ''}>Verdana</option>
                    <option value="Trebuchet MS" ${obj.fontFamily === 'Trebuchet MS' ? 'selected' : ''}>Trebuchet MS</option>
                    <option value="Impact" ${obj.fontFamily === 'Impact' ? 'selected' : ''}>Impact</option>
                </optgroup>
                <optgroup label="Serif">
                    <option value="Georgia" ${obj.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
                    <option value="Times New Roman" ${obj.fontFamily === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option>
                    <option value="Palatino" ${obj.fontFamily === 'Palatino' ? 'selected' : ''}>Palatino</option>
                </optgroup>
                <optgroup label="Display">
                    <option value="Comic Sans MS" ${obj.fontFamily === 'Comic Sans MS' ? 'selected' : ''}>Comic Sans MS</option>
                    <option value="Brush Script MT" ${obj.fontFamily === 'Brush Script MT' ? 'selected' : ''}>Brush Script MT</option>
                </optgroup>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Stil</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <button type="button" class="btn-v2 mute-btn ${obj.fontWeight === 'bold' ? 'active' : ''}"
                        onclick="toggleFontWeight()" title="Fed"><b>B</b></button>
                <button type="button" class="btn-v2 mute-btn ${obj.fontStyle === 'italic' ? 'active' : ''}"
                        onclick="toggleFontStyle()" title="Kursiv"><i>I</i></button>
                <button type="button" class="btn-v2 mute-btn ${obj.underline ? 'active' : ''}"
                        onclick="toggleUnderline()" title="Understreget"><u>U</u></button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Farve</label>
            <input type="color" value="${obj.fill}" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 6px;"
                   onchange="updateActiveProperty('fill', this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Bogstavafstand</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" class="form-range" style="flex: 1;" min="-200" max="800" value="${obj.charSpacing || 0}"
                       oninput="updateActiveProperty('charSpacing', parseInt(this.value))">
                <input type="number" class="form-field-v2" style="width: 70px;" value="${obj.charSpacing || 0}" min="-500" max="2000"
                       onchange="updateActiveProperty('charSpacing', parseInt(this.value))">
            </div>
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-v2 danger-btn w-100" onclick="deleteSelected()">
                <i class="mdi mdi-delete me-1"></i> Slet tekst
            </button>
        </div>
    `;
}

function updateTextFontSizePrint(printPt) {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        // Convert print pt to display px, accounting for object scale
        const displayPx = printToDisplayFontSize(parseInt(printPt));
        // Divide by object scale to get the actual fontSize needed
        const objectScale = activeObject.scaleY || 1;
        activeObject.set('fontSize', displayPx / objectScale);
        canvas.renderAll();
        // Sync slider and number input (both show print pt)
        const inputs = document.querySelectorAll('#propertiesContent .mb-3:nth-child(2) input');
        inputs.forEach(input => input.value = printPt);
        markUnsavedChanges();
    }
}

function setTextPresetPrint(preset) {
    const activeObject = canvas.getActiveObject();
    if (!activeObject) return;

    let printPt, fontWeight, fontFamily;

    switch (preset) {
        case 'headline':
            printPt = 300;
            fontWeight = 'bold';
            fontFamily = 'Helvetica';
            break;
        case 'subheadline':
            printPt = 150;
            fontWeight = 'normal';
            fontFamily = 'Helvetica';
            break;
        case 'body':
            printPt = 72;
            fontWeight = 'normal';
            fontFamily = 'Helvetica';
            break;
        default:
            return;
    }

    const displayPx = printToDisplayFontSize(printPt);
    // Account for object scale - divide by scale to get correct visual size
    const objectScale = activeObject.scaleY || 1;
    activeObject.set({
        fontSize: displayPx / objectScale,
        fontWeight: fontWeight,
        fontFamily: fontFamily
    });
    canvas.renderAll();
    markUnsavedChanges();

    // Refresh properties panel
    showPropertiesPanel(activeObject);
}

function toggleUnderline() {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        activeObject.set('underline', !activeObject.underline);
        canvas.renderAll();
        markUnsavedChanges();
        showPropertiesPanel(activeObject);
    }
}

function buildShapeProperties(obj) {
    return `
        <div class="mb-3">
            <label class="form-label font-13">Fyldfarve</label>
            <input type="color" value="${rgbaToHex(obj.fill)}" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 6px;"
                   onchange="updateActiveProperty('fill', this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Gennemsigtighed</label>
            <input type="range" class="form-range w-100" min="0" max="1" step="0.1" value="${obj.opacity || 1}"
                   onchange="updateActiveProperty('opacity', parseFloat(this.value))">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Kantfarve</label>
            <input type="color" value="${obj.stroke || '#333333'}" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 6px;"
                   onchange="updateActiveProperty('stroke', this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Kanttykkelse</label>
            <input type="number" class="form-field-v2 w-100" value="${obj.strokeWidth || 1}" min="0" max="10"
                   onchange="updateActiveProperty('strokeWidth', parseInt(this.value))">
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-v2 danger-btn w-100" onclick="deleteSelected()">
                <i class="mdi mdi-delete me-1"></i> Slet form
            </button>
        </div>
    `;
}

function buildBadgeProperties(obj) {
    const bgRect = obj.getObjects()[0];
    const textObj = obj.getObjects()[1];
    // Account for group scale when showing font size
    // The visual font size = actual fontSize * group scaleY
    const visualFontSize = textObj.fontSize * (obj.scaleY || 1);
    // Show print size in UI (what user understands)
    const printSize = displayToPrintFontSize(visualFontSize);

    return `
        <div class="mb-3">
            <label class="form-label font-13">Badge tekst</label>
            <input type="text" class="form-field-v2 w-100" value="${textObj.text}"
                   onchange="updateBadgeText(this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skriftstørrelse (pt)</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" class="form-range" style="flex: 1;" min="24" max="400" value="${printSize}"
                       oninput="updateBadgeFontSizePrint(this.value)">
                <input type="number" class="form-field-v2" style="width: 70px;" value="${printSize}" min="12" max="600"
                       onchange="updateBadgeFontSizePrint(this.value)">
            </div>
            <small class="text-muted font-11">72pt = ~1 tomme høj</small>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Skrifttype</label>
            <select class="form-select-v2 w-100 h-40px" onchange="updateBadgeFontFamily(this.value)">
                <option value="Helvetica" ${textObj.fontFamily === 'Helvetica' ? 'selected' : ''}>Helvetica</option>
                <option value="Arial" ${textObj.fontFamily === 'Arial' ? 'selected' : ''}>Arial</option>
                <option value="Verdana" ${textObj.fontFamily === 'Verdana' ? 'selected' : ''}>Verdana</option>
                <option value="Impact" ${textObj.fontFamily === 'Impact' ? 'selected' : ''}>Impact</option>
                <option value="Georgia" ${textObj.fontFamily === 'Georgia' ? 'selected' : ''}>Georgia</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Stil</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <button type="button" class="btn-v2 mute-btn ${textObj.fontWeight === 'bold' ? 'active' : ''}"
                        onclick="toggleBadgeFontWeight()" title="Fed"><b>B</b></button>
                <button type="button" class="btn-v2 mute-btn ${textObj.fontStyle === 'italic' ? 'active' : ''}"
                        onclick="toggleBadgeFontStyle()" title="Kursiv"><i>I</i></button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Bogstavafstand</label>
            <div class="flex-row-center" style="gap: .5rem;">
                <input type="range" class="form-range" style="flex: 1;" min="-200" max="800" value="${textObj.charSpacing || 0}"
                       oninput="updateBadgeCharSpacing(parseInt(this.value))">
                <input type="number" class="form-field-v2" style="width: 70px;" value="${textObj.charSpacing || 0}" min="-500" max="2000"
                       onchange="updateBadgeCharSpacing(parseInt(this.value))">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Baggrundsfarve</label>
            <input type="color" value="${bgRect.fill}" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 6px;"
                   onchange="updateBadgeColor(this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Tekstfarve</label>
            <input type="color" value="${textObj.fill}" style="width: 100%; height: 36px; border: 1px solid #ddd; border-radius: 6px;"
                   onchange="updateBadgeTextColor(this.value)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Hjørneradius</label>
            <input type="range" class="form-range w-100" min="0" max="50" value="${bgRect.rx || 8}"
                   onchange="updateBadgeBorderRadius(parseInt(this.value))">
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-v2 danger-btn w-100" onclick="deleteSelected()">
                <i class="mdi mdi-delete me-1"></i> Slet badge
            </button>
        </div>
    `;
}

function updateBadgeBorderRadius(value) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const bgRect = activeObject.getObjects()[0];
        bgRect.set({ rx: value, ry: value });
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function updateBadgeFontSizePrint(printPt) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        // Convert print pt to display px, accounting for group scale
        const displayPx = printToDisplayFontSize(parseInt(printPt));
        // Divide by group scale to get the actual fontSize needed
        const groupScale = activeObject.scaleY || 1;
        textObj.set('fontSize', displayPx / groupScale);
        // Sync slider and number input (both show print pt)
        const inputs = document.querySelectorAll('#propertiesContent .mb-3:nth-child(2) input');
        inputs.forEach(input => input.value = printPt);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function updateBadgeFontFamily(value) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        textObj.set('fontFamily', value);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function toggleBadgeFontWeight() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        const newWeight = textObj.fontWeight === 'bold' ? 'normal' : 'bold';
        textObj.set('fontWeight', newWeight);
        canvas.renderAll();
        markUnsavedChanges();
        showPropertiesPanel(activeObject);
    }
}

function toggleBadgeFontStyle() {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        const newStyle = textObj.fontStyle === 'italic' ? 'normal' : 'italic';
        textObj.set('fontStyle', newStyle);
        canvas.renderAll();
        markUnsavedChanges();
        showPropertiesPanel(activeObject);
    }
}

function updateBadgeCharSpacing(value) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        textObj.set('charSpacing', value);
        // Sync slider and number input
        const charSpacingInputs = document.querySelectorAll('#propertiesContent input[type="range"][max="800"], #propertiesContent input[type="number"][max="2000"]');
        charSpacingInputs.forEach(input => input.value = value);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function buildImageProperties(obj) {
    const elementName = obj.elementType === 'qrCode' ? 'QR-kode' : (obj.elementType === 'logo' ? 'logo' : 'billede');
    return `
        <div class="mb-3">
            <label class="form-label font-13">Bredde</label>
            <input type="number" class="form-field-v2 w-100" value="${Math.round(obj.getScaledWidth())}"
                   onchange="resizeImage(parseInt(this.value), null)">
        </div>
        <div class="mb-3">
            <label class="form-label font-13">Højde</label>
            <input type="number" class="form-field-v2 w-100" value="${Math.round(obj.getScaledHeight())}"
                   onchange="resizeImage(null, parseInt(this.value))">
        </div>
        <div class="mt-4 pt-3 border-top">
            <button type="button" class="btn-v2 danger-btn w-100" onclick="deleteSelected()">
                <i class="mdi mdi-delete me-1"></i> Slet ${elementName}
            </button>
        </div>
    `;
}

function updateActiveProperty(property, value) {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        activeObject.set(property, value);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function toggleFontWeight() {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        const newWeight = activeObject.fontWeight === 'bold' ? 'normal' : 'bold';
        activeObject.set('fontWeight', newWeight);
        canvas.renderAll();
        showPropertiesPanel();
        markUnsavedChanges();
    }
}

function toggleFontStyle() {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        const newStyle = activeObject.fontStyle === 'italic' ? 'normal' : 'italic';
        activeObject.set('fontStyle', newStyle);
        canvas.renderAll();
        showPropertiesPanel();
        markUnsavedChanges();
    }
}

function updateBadgeText(text) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        textObj.set('text', text);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function updateBadgeColor(color) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const bgRect = activeObject.getObjects()[0];
        bgRect.set('fill', color);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function updateBadgeTextColor(color) {
    const activeObject = canvas.getActiveObject();
    if (activeObject && activeObject.elementType === 'badge') {
        const textObj = activeObject.getObjects()[1];
        textObj.set('fill', color);
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function resizeImage(width, height) {
    const activeObject = canvas.getActiveObject();
    if (activeObject) {
        if (width) {
            activeObject.scaleToWidth(width);
        } else if (height) {
            activeObject.scaleToHeight(height);
        }
        canvas.renderAll();
        markUnsavedChanges();
    }
}

function rgbaToHex(rgba) {
    if (!rgba || rgba.startsWith('#')) return rgba || '#333333';
    const match = rgba.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    if (match) {
        return '#' + [match[1], match[2], match[3]].map(x => {
            const hex = parseInt(x).toString(16);
            return hex.length === 1 ? '0' + hex : hex;
        }).join('');
    }
    return '#333333';
}

// ==================== INSPIRATION ====================

function loadInspiration() {
    const grid = document.getElementById('inspirationGrid');
    const noMessage = document.getElementById('noInspirationMessage');
    const config = window.editorConfig;

    // Get inspirations based on type
    let inspirations = [];
    if (isDesignType) {
        inspirations = [...config.inspirations.design, ...config.inspirations.legacy];
    } else {
        inspirations = [...config.inspirations.arbitrary, ...config.inspirations.legacy];
    }

    if (inspirations.length === 0) {
        grid.innerHTML = '';
        noMessage.style.display = 'block';
        return;
    }

    noMessage.style.display = 'none';

    let html = '';
    inspirations.forEach(function(item) {
        html += `
            <div class="inspiration-item mb-2 cursor-pointer" onclick="viewInspirationImage('${item.image}', '${item.title}')">
                <img src="${item.image}" alt="${item.title}" class="w-100 border-radius-6px" style="height: 80px; object-fit: cover;">
            </div>
        `;
    });

    grid.innerHTML = html;
}

function viewInspirationImage(url, title) {
    // Simple image view - could expand to modal
    window.open(url, '_blank');
}

// ==================== SAVE & EXPORT ====================

/**
 * Save design to server
 */
function saveDesign(showNotification = true, btn = null) {
    screenLoader.show();

    const name = document.getElementById('designName').value.trim();
    const type = document.getElementById('designType').value;
    const size = document.getElementById('designSize').value;
    const locationUid = document.getElementById('designLocation').value;
    const barColor = document.getElementById('barColor')?.value || '#8B4513';

    if (!name) {
        screenLoader.hide();
        showErrorNotification('Indtast et navn til dit design');
        return Promise.reject('Missing name');
    }

    // Get canvas data but exclude background image (stored separately via upload)
    const canvasData = canvas.toJSON(['elementType', 'elementId', 'shapeType']);
    canvasData.objects = canvasData.objects.filter(obj => obj.elementType !== 'background');
    const elements = buildElementsConfig();

    // Check if background/logo exist on canvas
    const hasBackground = canvas.getObjects().some(obj => obj.elementType === 'background');
    const hasLogo = canvas.getObjects().some(obj => obj.elementType === 'logo');

    const data = {
        name: name,
        type: type,
        size: size,
        location_uid: locationUid || null,
        canvas_data: canvasData,
        elements: elements,
        bar_color: barColor,
        status: 'SAVED',
        clear_background: !hasBackground,
        clear_logo: !hasLogo,
    };

    let apiPath;
    if (currentDesignUid) {
        apiPath = 'api/merchant/asign/designs/update';
        data.uid = currentDesignUid;
    } else {
        apiPath = 'api/merchant/asign/designs/create';
    }

    const isNewDesign = !currentDesignUid;

    return post(apiPath, data)
    .then(result => {
        screenLoader.hide();
        if (result.status === 'error') {
            showErrorNotification(result.error?.message || 'Kunne ikke gemme design');
            return Promise.reject(result.error?.message);
        }

        const newUid = result.data?.uid || result.result?.uid;
        if (!currentDesignUid && newUid) {
            currentDesignUid = newUid;
            window.history.pushState({}, '', HOST + `asign-editor/${currentDesignUid}`);
        }

        // Upload background if this was a new design and we have a background
        if (isNewDesign && currentDesignUid) {
            const bgObject = canvas.getObjects().find(obj => obj.elementType === 'background');
            if (bgObject && bgObject._element && bgObject._element.src) {
                uploadBackgroundToServer(bgObject._element.src);
            }
        }

        // Upload logo if we have one
        const logoObject = canvas.getObjects().find(obj => obj.elementType === 'logo');
        if (logoObject && logoObject._element && logoObject._element.src) {
            uploadLogoToServer(logoObject._element.src);
        }

        clearUnsavedChanges();

        if (showNotification) {
            showSuccessNotification('Design gemt');
        }

        // Enable export button now that design is saved
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.disabled = false;
        }

        uploadPreview();
        return result;
    })
    .catch(err => {
        screenLoader.hide();
        console.error('Save error:', err);
        showErrorNotification('Fejl ved gemning af design');
        return Promise.reject(err);
    });
}

/**
 * Build elements configuration from canvas
 */
function buildElementsConfig() {
    const elements = {
        text_elements: [],
        qr_code: null,
        logo: null,
        badge: null,
        bar: null,
    };

    // Get bottom bar elements (now separate objects, not grouped)
    const barBg = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barBackground');
    const barText = canvas.getObjects().find(obj => obj.elementType === 'bottomBar' && obj.elementId === 'barText');

    if (barBg || barText) {
        elements.bar = {
            color: barBg?.fill || '#8B4513',
            text: barText?.text || '',
        };
    }

    canvas.getObjects().forEach(function(obj) {
        // Skip bottom bar elements (handled above)
        if (obj.elementType === 'bottomBar') {
            return;
        }

        if (obj.elementType === 'text' || (obj.type === 'i-text' && !obj.elementType)) {
            elements.text_elements.push({
                id: obj.elementId || null,
                text: obj.text,
                x: obj.left,
                y: obj.top,
                fontSize: obj.fontSize,
                fontFamily: obj.fontFamily,
                fill: obj.fill,
                fontStyle: obj.fontStyle,
                fontWeight: obj.fontWeight,
            });
        } else if (obj.elementType === 'qrCode') {
            elements.qr_code = {
                x: obj.left,
                y: obj.top,
                size: obj.getScaledWidth(),
            };
        } else if (obj.elementType === 'logo') {
            elements.logo = {
                x: obj.left,
                y: obj.top,
                width: obj.getScaledWidth(),
                height: obj.getScaledHeight(),
            };
        } else if (obj.elementType === 'badge' && obj._objects) {
            // Badge is still a group
            const bgRect = obj._objects[0];
            const textObj = obj._objects[1];
            elements.badge = {
                visible: true,
                x: obj.left,
                y: obj.top,
                text: textObj?.text || '',
                fill: bgRect?.fill || '#D2691E',
                textColor: textObj?.fill || '#FFFFFF',
                fontSize: textObj?.fontSize || 12,
                width: bgRect?.width || 100,
                height: bgRect?.height || 40,
                rx: bgRect?.rx || 8,
            };
        }
    });

    return elements;
}

/**
 * Upload preview thumbnail
 */
function uploadPreview() {
    if (!currentDesignUid) return;

    const dataUrl = canvas.toDataURL({
        format: 'png',
        quality: 0.8,
        multiplier: 0.5, // Smaller preview
    });

    post('api/merchant/asign/upload-preview', {
        uid: currentDesignUid,
        image_data: dataUrl,
    }).catch(err => console.error('Preview upload error:', err));
}

/**
 * Save and show export modal
 */
function saveAndExport() {
    screenLoader.show();

    saveDesign(false).then(() => {
        screenLoader.hide();
        $('#exportModal').modal('show');
    }).catch(() => {
        screenLoader.hide();
    });
}

/**
 * Execute export
 */
function doExport() {
    const btn = document.querySelector('#exportModal .btn-v2.action-btn');
    const originalText = btn.innerHTML;

    const spinButton = async () => {
        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin me-1"></i> Eksporterer...';
    };

    const stopSpin = () => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        $('#exportModal').modal('hide');
    };

    spinButton().then(() => {
        setTimeout(() => {
            const format = document.getElementById('exportFormat').value;
            const sizeConfig = getCurrentSizeConfig();
            const displayDims = getDisplayDimensions();
            const printMultiplier = sizeConfig.widthPx / displayDims.width;

            if (format === 'png') {
                const dataUrl = canvas.toDataURL({
                    format: 'png',
                    quality: 1,
                    multiplier: printMultiplier,
                });

                const link = document.createElement('a');
                link.download = `${sanitizeFilename()}.png`;
                link.href = dataUrl;
                link.click();

                stopSpin();
            } else if (format === 'pdf') {
                // Generate high-res image from canvas
                const dataUrl = canvas.toDataURL({
                    format: 'png',
                    quality: 1,
                    multiplier: printMultiplier,
                });

                // Create PDF with exact page dimensions in mm
                const jsPDF = window.jspdf?.jsPDF || window.jsPDF;
                if (!jsPDF) {
                    showErrorNotification('PDF bibliotek kunne ikke indlæses');
                    stopSpin();
                    return;
                }

                const pdf = new jsPDF({
                    orientation: sizeConfig.widthMm > sizeConfig.heightMm ? 'landscape' : 'portrait',
                    unit: 'mm',
                    format: [sizeConfig.widthMm, sizeConfig.heightMm],
                });

                // Add image to fill entire page (0,0 to full width/height)
                pdf.addImage(dataUrl, 'PNG', 0, 0, sizeConfig.widthMm, sizeConfig.heightMm, undefined, 'FAST');

                // Download the PDF
                pdf.save(`${sanitizeFilename()}.pdf`);

                stopSpin();
            } else {
                showErrorNotification('Ukendt eksportformat');
                stopSpin();
            }
        }, 100);
    });
}
