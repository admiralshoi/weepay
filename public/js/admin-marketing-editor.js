/**
 * Admin Marketing Template Placeholder Editor
 * Visual editor for placing elements on PDF templates
 */

// PDF.js worker setup
pdfjsLib.GlobalWorkerOptions.workerSrc = HOST + 'public/vendor/pdfjs/pdf.worker.min.js';

// State
let pdfDoc = null;
let currentPage = 1;
let totalPages = 1;
let scale = 1.0;
let placeholders = [];
let selectedPlaceholder = null;
let placeholderIdCounter = 0;
let isDragging = false;
let isResizing = false;
let dragOffset = { x: 0, y: 0 };
let resizeHandle = null;
let justSelected = false; // Flag to prevent immediate deselection after selection
let currentRenderTask = null; // Track current render to prevent conflicts

// Canvas and container
let canvas = null;
let ctx = null;
let overlay = null;
let canvasRect = null;

/**
 * Initialize the editor
 */
document.addEventListener('DOMContentLoaded', function() {
    canvas = document.getElementById('pdf-canvas');
    ctx = canvas.getContext('2d');
    overlay = document.getElementById('placeholders-overlay');

    // Load existing placeholders
    if (typeof existingPlaceholders !== 'undefined' && existingPlaceholders.length > 0) {
        existingPlaceholders.forEach(function(p) {
            placeholders.push({
                id: ++placeholderIdCounter,
                type: p.type,
                x: parseFloat(p.x),
                y: parseFloat(p.y),
                width: parseFloat(p.width),
                height: parseFloat(p.height),
                page_number: parseInt(p.page_number) || 1,
                font_size: parseInt(p.font_size) || 12,
                font_color: p.font_color || '#000000'
            });
        });
    }

    // Load PDF
    loadPdf();

    // Setup event listeners
    setupEventListeners();
});

/**
 * Load and render the PDF
 */
function loadPdf() {
    if (typeof templateFilePath === 'undefined') return;

    pdfjsLib.getDocument(templateFilePath).promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        document.getElementById('totalPages').textContent = totalPages;

        // Update pagination buttons
        updatePaginationButtons();

        // Render first page
        renderPage(currentPage);
    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        showErrorNotification('Fejl', 'Kunne ikke indlaese PDF');
    });
}

/**
 * Render a specific page
 */
function renderPage(pageNum) {
    // Cancel any existing render task
    if (currentRenderTask) {
        currentRenderTask.cancel();
        currentRenderTask = null;
    }

    pdfDoc.getPage(pageNum).then(function(page) {
        var viewport = page.getViewport({ scale: scale });

        canvas.width = viewport.width;
        canvas.height = viewport.height;

        // Update overlay size
        overlay.style.width = viewport.width + 'px';
        overlay.style.height = viewport.height + 'px';

        var renderContext = {
            canvasContext: ctx,
            viewport: viewport
        };

        currentRenderTask = page.render(renderContext);
        currentRenderTask.promise.then(function() {
            currentRenderTask = null;
            // Store canvas rect for coordinate calculations
            canvasRect = canvas.getBoundingClientRect();

            // Render placeholders for this page
            renderPlaceholders();
        }).catch(function(error) {
            // Ignore cancelled render errors
            if (error.name !== 'RenderingCancelledException') {
                console.error('Render error:', error);
            }
            currentRenderTask = null;
        });
    });

    document.getElementById('currentPage').textContent = pageNum;
    updatePaginationButtons();
}

/**
 * Render all placeholders for the current page
 */
function renderPlaceholders() {
    overlay.innerHTML = '';

    var pageplaceholders = placeholders.filter(function(p) {
        return p.page_number === currentPage;
    });

    if (pageplaceholders.length === 0) {
        document.getElementById('no-placeholders-msg').style.display = 'block';
    } else {
        document.getElementById('no-placeholders-msg').style.display = 'none';
    }

    pageplaceholders.forEach(function(placeholder) {
        createPlaceholderElement(placeholder);
    });

    updatePlaceholderList();
}

/**
 * Create a placeholder DOM element
 */
function createPlaceholderElement(placeholder) {
    var el = document.createElement('div');
    el.className = 'placeholder-box';
    el.dataset.id = placeholder.id;

    // Convert percentage to pixels
    var left = (placeholder.x / 100) * canvas.width;
    var top = (placeholder.y / 100) * canvas.height;
    var width = (placeholder.width / 100) * canvas.width;
    var height = (placeholder.height / 100) * canvas.height;

    el.style.left = left + 'px';
    el.style.top = top + 'px';
    el.style.width = width + 'px';
    el.style.height = height + 'px';

    // Color based on type
    var colors = {
        'qr_code': '#4CAF50',
        'location_name': '#2196F3',
        'location_logo': '#FF9800'
    };
    el.style.borderColor = colors[placeholder.type] || '#666';
    el.style.backgroundColor = (colors[placeholder.type] || '#666') + '20';

    // Label
    var label = document.createElement('div');
    label.className = 'placeholder-label';
    label.textContent = placeholderTypeOptions[placeholder.type] || placeholder.type;
    label.style.backgroundColor = colors[placeholder.type] || '#666';
    el.appendChild(label);

    // Resize handles
    var handles = ['nw', 'ne', 'sw', 'se'];
    handles.forEach(function(pos) {
        var handle = document.createElement('div');
        handle.className = 'resize-handle resize-' + pos;
        handle.dataset.handle = pos;
        el.appendChild(handle);
    });

    // Event listeners
    el.addEventListener('mousedown', function(e) {
        e.stopPropagation(); // Prevent event from bubbling to overlay
        if (e.target.classList.contains('resize-handle')) {
            startResize(e, placeholder);
        } else {
            startDrag(e, placeholder);
        }
    });

    el.addEventListener('click', function(e) {
        e.stopPropagation();
        selectPlaceholder(placeholder);
    });

    overlay.appendChild(el);

    // Select if it was selected before
    if (selectedPlaceholder && selectedPlaceholder.id === placeholder.id) {
        el.classList.add('selected');
    }
}

/**
 * Add a new placeholder
 */
function addPlaceholder(type) {
    var placeholder = {
        id: ++placeholderIdCounter,
        type: type,
        x: 10,
        y: 10,
        width: 15,
        height: type === 'location_name' ? 5 : 15,
        page_number: currentPage,
        font_size: 30,
        font_color: '#000000'
    };

    placeholders.push(placeholder);
    renderPlaceholders();
    selectPlaceholder(placeholder);
}

/**
 * Select a placeholder
 */
function selectPlaceholder(placeholder) {
    selectedPlaceholder = placeholder;

    // Set flag to prevent immediate deselection (reset after a short delay)
    justSelected = true;
    setTimeout(function() { justSelected = false; }, 100);

    // Update visual selection
    var boxes = overlay.querySelectorAll('.placeholder-box');
    boxes.forEach(function(box) {
        box.classList.remove('selected');
        if (parseInt(box.dataset.id) === placeholder.id) {
            box.classList.add('selected');
        }
    });

    // Show properties panel
    document.getElementById('placeholder-properties').style.display = 'block';

    // Update property values
    var typeSelect = document.getElementById('prop-type');
    typeSelect.value = placeholder.type;
    // Refresh the custom select UI to show the correct value
    if (typeof refreshSelectV2UI === 'function') {
        refreshSelectV2UI(typeSelect);
    }

    document.getElementById('prop-font-size').value = placeholder.font_size;
    document.getElementById('prop-font-color').value = placeholder.font_color;

    // Show/hide text properties based on type
    var textProps = document.getElementById('text-properties');
    textProps.style.display = placeholder.type === 'location_name' ? 'block' : 'none';

    // Update list selection
    updatePlaceholderList();
}

/**
 * Update selected placeholder from properties
 */
function updateSelectedPlaceholder() {
    if (!selectedPlaceholder) return;

    selectedPlaceholder.type = document.getElementById('prop-type').value;
    selectedPlaceholder.font_size = parseInt(document.getElementById('prop-font-size').value) || 12;
    selectedPlaceholder.font_color = document.getElementById('prop-font-color').value;

    // Show/hide text properties based on type
    var textProps = document.getElementById('text-properties');
    textProps.style.display = selectedPlaceholder.type === 'location_name' ? 'block' : 'none';

    renderPlaceholders();
}

/**
 * Delete selected placeholder
 */
function deleteSelectedPlaceholder() {
    if (!selectedPlaceholder) return;

    placeholders = placeholders.filter(function(p) {
        return p.id !== selectedPlaceholder.id;
    });

    selectedPlaceholder = null;
    document.getElementById('placeholder-properties').style.display = 'none';

    renderPlaceholders();
}

/**
 * Update placeholder list in sidebar
 */
function updatePlaceholderList() {
    var list = document.getElementById('placeholder-list');
    var noMsg = document.getElementById('no-placeholders-msg');

    // Clear list except the no-placeholders message
    var items = list.querySelectorAll('.placeholder-list-item');
    items.forEach(function(item) {
        item.remove();
    });

    if (placeholders.length === 0) {
        noMsg.style.display = 'block';
        return;
    }

    noMsg.style.display = 'none';

    placeholders.forEach(function(p) {
        var item = document.createElement('div');
        item.className = 'placeholder-list-item cursor-pointer flex-row-between flex-align-center';
        if (selectedPlaceholder && selectedPlaceholder.id === p.id) {
            item.classList.add('active');
        }

        var label = placeholderTypeOptions[p.type] || p.type;
        var pageInfo = p.page_number !== currentPage ? ' (Side ' + p.page_number + ')' : '';

        item.innerHTML = '<span class="font-13">' + label + pageInfo + '</span>' +
            '<button type="button" class="btn-v2 danger-btn btn-sm p-0" onclick="deletePlaceholderById(' + p.id + '); event.stopPropagation();">' +
            '<i class="mdi mdi-delete"></i></button>';

        item.addEventListener('click', function() {
            if (p.page_number !== currentPage) {
                currentPage = p.page_number;
                renderPage(currentPage);
            }
            selectPlaceholder(p);
        });

        list.appendChild(item);
    });
}

/**
 * Delete placeholder by ID
 */
function deletePlaceholderById(id) {
    placeholders = placeholders.filter(function(p) {
        return p.id !== id;
    });

    if (selectedPlaceholder && selectedPlaceholder.id === id) {
        selectedPlaceholder = null;
        document.getElementById('placeholder-properties').style.display = 'none';
    }

    renderPlaceholders();
}

/**
 * Start dragging a placeholder
 */
function startDrag(e, placeholder) {
    isDragging = true;
    selectedPlaceholder = placeholder;

    var el = e.target.closest('.placeholder-box');
    var rect = el.getBoundingClientRect();
    var containerRect = overlay.getBoundingClientRect();

    dragOffset.x = e.clientX - rect.left;
    dragOffset.y = e.clientY - rect.top;

    selectPlaceholder(placeholder);
}

/**
 * Start resizing a placeholder
 */
function startResize(e, placeholder) {
    isResizing = true;
    selectedPlaceholder = placeholder;
    resizeHandle = e.target.dataset.handle;

    selectPlaceholder(placeholder);
    e.stopPropagation();
}

/**
 * Setup global event listeners
 */
function setupEventListeners() {
    document.addEventListener('mousemove', function(e) {
        if (!isDragging && !isResizing) return;
        if (!selectedPlaceholder) return;

        var containerRect = overlay.getBoundingClientRect();

        if (isDragging) {
            var newX = e.clientX - containerRect.left - dragOffset.x;
            var newY = e.clientY - containerRect.top - dragOffset.y;

            // Convert to percentage
            selectedPlaceholder.x = (newX / canvas.width) * 100;
            selectedPlaceholder.y = (newY / canvas.height) * 100;

            // Clamp values
            selectedPlaceholder.x = Math.max(0, Math.min(100 - selectedPlaceholder.width, selectedPlaceholder.x));
            selectedPlaceholder.y = Math.max(0, Math.min(100 - selectedPlaceholder.height, selectedPlaceholder.y));

            renderPlaceholders();
        }

        if (isResizing) {
            var el = overlay.querySelector('[data-id="' + selectedPlaceholder.id + '"]');
            if (!el) return;

            var elRect = el.getBoundingClientRect();
            var mouseX = e.clientX - containerRect.left;
            var mouseY = e.clientY - containerRect.top;

            // Current position in pixels
            var currentX = (selectedPlaceholder.x / 100) * canvas.width;
            var currentY = (selectedPlaceholder.y / 100) * canvas.height;
            var currentW = (selectedPlaceholder.width / 100) * canvas.width;
            var currentH = (selectedPlaceholder.height / 100) * canvas.height;

            var newX = currentX, newY = currentY, newW = currentW, newH = currentH;
            var minSize = 20;

            switch(resizeHandle) {
                case 'se':
                    newW = Math.max(minSize, mouseX - currentX);
                    newH = Math.max(minSize, mouseY - currentY);
                    break;
                case 'sw':
                    newW = Math.max(minSize, currentX + currentW - mouseX);
                    newH = Math.max(minSize, mouseY - currentY);
                    newX = mouseX;
                    break;
                case 'ne':
                    newW = Math.max(minSize, mouseX - currentX);
                    newH = Math.max(minSize, currentY + currentH - mouseY);
                    newY = mouseY;
                    break;
                case 'nw':
                    newW = Math.max(minSize, currentX + currentW - mouseX);
                    newH = Math.max(minSize, currentY + currentH - mouseY);
                    newX = mouseX;
                    newY = mouseY;
                    break;
            }

            // Convert back to percentage
            selectedPlaceholder.x = (newX / canvas.width) * 100;
            selectedPlaceholder.y = (newY / canvas.height) * 100;
            selectedPlaceholder.width = (newW / canvas.width) * 100;
            selectedPlaceholder.height = (newH / canvas.height) * 100;

            // Clamp values
            selectedPlaceholder.x = Math.max(0, selectedPlaceholder.x);
            selectedPlaceholder.y = Math.max(0, selectedPlaceholder.y);
            selectedPlaceholder.width = Math.min(100 - selectedPlaceholder.x, selectedPlaceholder.width);
            selectedPlaceholder.height = Math.min(100 - selectedPlaceholder.y, selectedPlaceholder.height);

            renderPlaceholders();
        }
    });

    document.addEventListener('mouseup', function() {
        isDragging = false;
        isResizing = false;
        resizeHandle = null;
    });

    // Click outside to deselect
    overlay.addEventListener('click', function(e) {
        // Don't deselect if we just selected something (prevents race condition with re-rendered DOM)
        if (justSelected) return;

        // Only deselect when clicking directly on the overlay background
        if (e.target === overlay) {
            selectedPlaceholder = null;
            document.getElementById('placeholder-properties').style.display = 'none';
            var boxes = overlay.querySelectorAll('.placeholder-box');
            boxes.forEach(function(box) {
                box.classList.remove('selected');
            });
            updatePlaceholderList();
        }
    });
}

/**
 * Pagination
 */
function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        renderPage(currentPage);
    }
}

function updatePaginationButtons() {
    document.getElementById('prevPageBtn').disabled = currentPage <= 1;
    document.getElementById('nextPageBtn').disabled = currentPage >= totalPages;
}

/**
 * Zoom controls
 */
function zoomIn() {
    if (scale < 2.0) {
        // Use smaller steps at lower zoom levels
        var step = scale >= 0.5 ? 0.25 : 0.1;
        scale += step;
        if (scale > 2.0) scale = 2.0;
        document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    }
}

function zoomOut() {
    if (scale > 0.1) {
        // Use smaller steps at lower zoom levels
        var step = scale > 0.5 ? 0.25 : 0.1;
        scale -= step;
        if (scale < 0.1) scale = 0.1;
        document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
        renderPage(currentPage);
    }
}

/**
 * Save placeholders to server
 */
function savePlaceholders() {
    var btn = document.getElementById('saveBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    var data = placeholders.map(function(p) {
        return {
            type: p.type,
            x: p.x,
            y: p.y,
            width: p.width,
            height: p.height,
            page_number: p.page_number,
            font_size: p.font_size,
            font_color: p.font_color
        };
    });

    post('api/admin/marketing/templates/placeholders/save', {
        template_uid: templateUid,
        placeholders: data
    }).then(function(response) {
        if (response.status === 'success' || response.success) {
            showSuccessNotification('Succes', 'Placeholders gemt');
        } else {
            showErrorNotification('Fejl', response.error?.message || 'Kunne ikke gemme placeholders');
        }

        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    }).catch(function(error) {
        console.error('Save error:', error);
        showErrorNotification('Fejl', 'Der opstod en fejl');

        btn.querySelector('.btn-text').classList.remove('d-none');
        btn.querySelector('.spinner-border').classList.add('d-none');
        btn.disabled = false;
    });
}
