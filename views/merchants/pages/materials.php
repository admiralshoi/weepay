<?php
/**
 * Marketing Materials Page
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "Markedsføring";

// Helper function to render template card
function renderTemplateCard($template) {
    $previewUrl = !isEmpty($template->preview_image) ? __url($template->preview_image) : __url('public/media/default/pdf-placeholder.svg');
    $typeLabels = [
        'A4' => 'A4 Plakat',
        'A3' => 'A3 Plakat',
        'A5' => 'A5 Flyer',
        'roll-up' => 'Roll-up',
        'poster' => 'Plakat',
        'flyer' => 'Flyer',
        'sticker' => 'Klistermærke',
    ];
    $typeLabel = $typeLabels[$template->type] ?? $template->type;
    ?>
    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
        <div class="card h-100 border-radius-10px template-card" data-uid="<?=$template->uid?>">
            <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                <img src="<?=$previewUrl?>" alt="<?=$template->name?>"
                     style="width: 100%; height: 100%; object-fit: contain; background: #f8f9fa;">
                <span class="success-box position-absolute" style="top: 10px; right: 10px; font-size: 11px;">
                    <?=$typeLabel?>
                </span>
            </div>
            <div class="card-body">
                <h5 class="font-16 font-weight-bold mb-1"><?=$template->name?></h5>
                <?php if (!isEmpty($template->description)): ?>
                    <p class="font-13 color-gray mb-3"><?=$template->description?></p>
                <?php else: ?>
                    <p class="font-13 color-gray mb-3">Download med dit logo og QR-kode</p>
                <?php endif; ?>
                <button class="btn-v2 action-btn w-100 flex-row-center flex-align-center flex-nowrap"
                        onclick="openDownloadModal('<?=$template->uid?>', '<?=addslashes($template->name)?>', '<?=$template->type?>')"
                        style="gap: .5rem;">
                    <i class="mdi mdi-download"></i>
                    <span>Download</span>
                </button>
            </div>
        </div>
    </div>
    <?php
}

// Helper function to render inspiration card
function renderInspirationCard($item, $categoryLabels) {
    $imageUrl = __url($item->image_path);
    $categoryLabel = $categoryLabels->{$item->category} ?? $item->category;
    ?>
    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4 inspiration-item" data-category="<?=$item->category?>">
        <div class="card h-100 border-radius-10px overflow-hidden cursor-pointer"
             onclick="viewInspiration('<?=$imageUrl?>', '<?=addslashes($item->title)?>', '<?=addslashes($item->description ?? '')?>')">
            <div class="card-img-top" style="height: 200px; overflow: hidden;">
                <img src="<?=$imageUrl?>" alt="<?=$item->title?>"
                     style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="card-body p-3">
                <span class="font-11 color-gray"><?=$categoryLabel?></span>
                <h5 class="font-14 font-weight-bold mb-0 mt-1"><?=$item->title?></h5>
            </div>
        </div>
    </div>
    <?php
}
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "materials";

    var locations = <?=json_encode(toArray($args->locations->list()))?>;
    var templates = <?=json_encode(toArray($args->templates->list()))?>;
    var sizeOptions = <?=json_encode($args->sizeOptions)?>;
</script>

<div class="page-content">

    <div class="flex-row-between flex-align-center flex-wrap mb-4" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <p class="mb-0 font-30 font-weight-bold">Markedsføringsmaterialer</p>
            <p class="mb-0 font-16 font-weight-medium color-gray">Download materialer til markedsføring af din butik</p>
        </div>
    </div>

    <!-- Section 1: Downloadable Templates -->
    <div class="card border-radius-10px mb-4">
        <div class="card-body">
            <div class="flex-row-start flex-align-center flex-nowrap mb-3" style="column-gap: .5rem;">
                <i class="mdi mdi-file-pdf-box font-20 color-blue"></i>
                <p class="mb-0 font-20 font-weight-bold">Download Materialer</p>
            </div>
            <p class="font-14 color-gray mb-4">
                Vælg et materiale og download det med dit logo, butiksnavn og QR-kode til betaling.
            </p>

            <?php if ($args->templates->count() > 0): ?>
                <div class="row">
                    <?php foreach ($args->templates->list() as $template): ?>
                        <?php renderTemplateCard($template); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex-col-center flex-align-center text-center py-5">
                    <div class="square-60 flex-row-center flex-align-center bg-blue-light border-radius-50 mb-3">
                        <i class="mdi mdi-file-document-outline font-28 color-blue"></i>
                    </div>
                    <p class="font-14 color-gray mb-0">Ingen materialer tilgængelige endnu.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 2: A-Sign Generator -->
    <div class="card border-radius-10px mb-4">
        <div class="card-body">
            <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .5rem;">
                <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                    <i class="mdi mdi-sign-real-estate font-20 color-orange"></i>
                    <p class="mb-0 font-20 font-weight-bold">A-Skilt Designer</p>
                </div>
                <a href="<?=__url(Links::$merchant->asignEditor)?>" class="btn-v2 action-btn">
                    <i class="mdi mdi-plus me-1"></i>
                    Opret nyt design
                </a>
            </div>
            <p class="font-14 color-gray mb-4">
                Design dit eget A-skilt med baggrundsbillede, tekst, QR-kode og logo.
            </p>

            <div class="row" id="asignDesignsGrid">
                <!-- A-Sign designs will be loaded here -->
            </div>

            <div id="noAsignDesigns" class="flex-col-center flex-align-center text-center py-4" style="display: none;">
                <div class="square-60 flex-row-center flex-align-center bg-orange-light border-radius-50 mb-3">
                    <i class="mdi mdi-sign-real-estate font-28 color-orange"></i>
                </div>
                <p class="font-14 color-gray mb-2">Du har ikke oprettet nogen A-skilt designs endnu.</p>
                <a href="<?=__url(Links::$merchant->asignEditor)?>" class="btn-v2 action-btn">
                    <i class="mdi mdi-plus me-1"></i>
                    Opret dit f&oslash;rste design
                </a>
            </div>
        </div>
    </div>

    <!-- Section 3: Inspiration Gallery -->
    <div class="card border-radius-10px">
        <div class="card-body">
            <div class="flex-row-between flex-align-center flex-wrap mb-3" style="column-gap: .5rem; row-gap: .5rem;">
                <div class="flex-row-start flex-align-center flex-nowrap" style="column-gap: .5rem;">
                    <i class="mdi mdi-lightbulb-on-outline font-20 color-purple"></i>
                    <p class="mb-0 font-20 font-weight-bold">Inspiration</p>
                </div>

                <?php if ($args->inspirations->count() > 0): ?>
                    <div class="flex-row-end flex-wrap" style="gap: .5rem;">
                        <button class="btn-v2 action-btn inspiration-filter active" data-category="all">
                            Alle
                        </button>
                        <?php foreach ($args->inspirationCategories as $key => $label): ?>
                            <button class="btn-v2 mute-btn inspiration-filter" data-category="<?=$key?>">
                                <?=$label?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <p class="font-14 color-gray mb-4">
                Se eksempler på hvordan andre bruger WeePay materialer til markedsføring.
            </p>

            <?php if ($args->inspirations->count() > 0): ?>
                <div class="row" id="inspirationGrid">
                    <?php foreach ($args->inspirations->list() as $item): ?>
                        <?php renderInspirationCard($item, $args->inspirationCategories); ?>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="flex-col-center flex-align-center text-center py-5">
                    <div class="square-60 flex-row-center flex-align-center bg-purple-light border-radius-50 mb-3">
                        <i class="mdi mdi-lightbulb-outline font-28 color-purple"></i>
                    </div>
                    <p class="font-14 color-gray mb-0">Ingen inspiration tilgængelig endnu.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Download Modal -->
<div class="modal fade" id="downloadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-18 font-weight-bold">Download Materiale</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="font-14 color-gray mb-4" id="downloadTemplateTitle">Vælg lokation og størrelse</p>

                <input type="hidden" id="downloadTemplateUid">

                <div class="form-group mb-3">
                    <label class="font-14 font-weight-medium mb-2">Lokation</label>
                    <select class="form-select-v2" id="downloadLocation">
                        <?php foreach ($args->locations->list() as $location): ?>
                            <option value="<?=$location->uid?>"><?=$location->name?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text color-gray">Dit logo og QR-kode til denne lokation vil blive indsat</small>
                </div>

                <div class="form-group mb-4" id="downloadSizeGroup">
                    <label class="font-14 font-weight-medium mb-2">Størrelse</label>
                    <select class="form-select-v2" id="downloadSize">
                        <?php foreach ($args->sizeOptions as $key => $label): ?>
                            <option value="<?=$key?>"><?=$label?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn flex-row-center flex-align-center flex-nowrap"
                        onclick="downloadTemplate()" id="downloadBtn" style="gap: .5rem;">
                    <span class="btn-text">Download PDF</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Inspiration View Modal -->
<div class="modal fade" id="inspirationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-18 font-weight-bold" id="inspirationModalTitle">Inspiration</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img src="" alt="" id="inspirationModalImage" class="img-fluid border-radius-10px" style="max-height: 70vh;">
                <p class="font-14 color-gray mt-3 mb-0" id="inspirationModalDescription"></p>
            </div>
        </div>
    </div>
</div>

<script>
function openDownloadModal(templateUid, templateName, templateType) {
    document.getElementById('downloadTemplateUid').value = templateUid;
    document.getElementById('downloadTemplateTitle').textContent = templateName;

    // Hide size selector for stickers (must be original size)
    var sizeGroup = document.getElementById('downloadSizeGroup');
    var sizeSelect = document.getElementById('downloadSize');
    if (templateType === 'sticker') {
        sizeGroup.style.display = 'none';
        sizeSelect.value = 'original';
    } else {
        sizeGroup.style.display = 'block';
    }

    $('#downloadModal').modal('show');
}

function downloadTemplate() {
    var templateUid = document.getElementById('downloadTemplateUid').value;
    var locationUid = document.getElementById('downloadLocation').value;
    var size = document.getElementById('downloadSize').value;

    if (!locationUid) {
        showErrorNotification('Fejl', 'Vælg venligst en lokation');
        return;
    }

    // Show loading state
    var btn = document.getElementById('downloadBtn');
    btn.querySelector('.btn-text').classList.add('d-none');
    btn.querySelector('.spinner-border').classList.remove('d-none');
    btn.disabled = true;

    // Build download URL
    var downloadUrl = HOST + 'api/merchant/materials/download' +
        '?template_uid=' + encodeURIComponent(templateUid) +
        '&location_uid=' + encodeURIComponent(locationUid) +
        '&size=' + encodeURIComponent(size);

    // Use fetch to check for errors first
    fetch(downloadUrl)
        .then(function(response) {
            // Check content type - if JSON, it's an error
            var contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json().then(function(result) {
                    throw new Error(result.error?.message || 'Kunne ikke downloade PDF');
                });
            }
            if (!response.ok) {
                throw new Error('Kunne ikke downloade PDF');
            }
            return response.blob();
        })
        .then(function(blob) {
            // Create download link
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;

            // Get location and template names for filename
            var locationSelect = document.getElementById('downloadLocation');
            var locationName = locationSelect.options[locationSelect.selectedIndex].text;
            var templateName = document.getElementById('downloadTemplateTitle').textContent;
            var filename = (locationName + '_' + templateName + '_' + size)
                .replace(/\s+/g, '_')
                .replace(/[^a-zA-Z0-9_æøåÆØÅ-]/g, '') + '.pdf';

            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            // Close modal
            $('#downloadModal').modal('hide');
        })
        .catch(function(error) {
            showErrorNotification('Fejl', error.message);
        })
        .finally(function() {
            // Reset button state
            btn.querySelector('.btn-text').classList.remove('d-none');
            btn.querySelector('.spinner-border').classList.add('d-none');
            btn.disabled = false;
        });
}

function viewInspiration(imageUrl, title, description) {
    document.getElementById('inspirationModalTitle').textContent = title;
    document.getElementById('inspirationModalImage').src = imageUrl;
    document.getElementById('inspirationModalDescription').textContent = description || '';
    document.getElementById('inspirationModalDescription').style.display = description ? 'block' : 'none';
    $('#inspirationModal').modal('show');
}

// Inspiration category filter
document.addEventListener('DOMContentLoaded', function() {
    var filterButtons = document.querySelectorAll('.inspiration-filter');

    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var category = this.dataset.category;

            // Update active button
            filterButtons.forEach(function(b) {
                b.classList.remove('active');
                b.classList.remove('action-btn');
                b.classList.add('mute-btn');
            });
            this.classList.add('active');
            this.classList.remove('mute-btn');
            this.classList.add('action-btn');

            // Filter items
            var items = document.querySelectorAll('.inspiration-item');
            items.forEach(function(item) {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Load A-Sign designs
    loadAsignDesigns();
});

// A-Sign Designs
function loadAsignDesigns() {
    fetch(HOST + 'api/merchant/asign/designs')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var grid = document.getElementById('asignDesignsGrid');
            var noDesigns = document.getElementById('noAsignDesigns');

            if (data.success || data.status === 'success') {
                var designs = data.data?.designs || data.result?.designs || [];
                if (designs.length === 0) {
                    grid.innerHTML = '';
                    noDesigns.style.display = 'flex';
                } else {
                    noDesigns.style.display = 'none';
                    grid.innerHTML = designs.map(renderAsignDesignCard).join('');
                }
            }
        })
        .catch(function(err) {
            console.error('Failed to load A-Sign designs:', err);
        });
}

function renderAsignDesignCard(design) {
    var previewUrl = design.preview_image || HOST + 'public/media/default/pdf-placeholder.svg';
    var typeLabel = design.type === 'design' ? 'Design' : 'Vilkårligt';
    var statusBadge = design.status === 'SAVED'
        ? '<span class="success-box position-absolute" style="top: 10px; right: 10px; font-size: 11px;">Gemt</span>'
        : '<span class="warning-box position-absolute" style="top: 10px; right: 10px; font-size: 11px;">Kladde</span>';

    return '<div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">' +
        '<div class="card h-100 border-radius-10px template-card">' +
        '<div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">' +
        '<img src="' + previewUrl + '" alt="' + design.name + '" ' +
        'style="width: 100%; height: 100%; object-fit: contain; background: #f8f9fa;" onerror="replaceBadImage(this)">' +
        statusBadge +
        '</div>' +
        '<div class="card-body">' +
        '<span class="font-11 color-gray">' + typeLabel + '</span>' +
        '<h5 class="font-16 font-weight-bold mb-1">' + design.name + '</h5>' +
        (design.location_name ? '<p class="font-13 color-gray mb-3">' + design.location_name + '</p>' : '<p class="font-13 color-gray mb-3">Ingen lokation</p>') +
        '<div class="flex-row-center" style="gap: .5rem;">' +
        '<a href="' + HOST + 'asign-editor/' + design.uid + '" class="btn-v2 action-btn flex-1 flex-row-center flex-align-center" style="gap: .25rem;">' +
        '<i class="mdi mdi-pencil"></i><span>Rediger</span>' +
        '</a>' +
        '<button type="button" class="btn-v2 mute-btn" onclick="deleteAsignDesign(\'' + design.uid + '\', \'' + design.name.replace(/'/g, "\\'") + '\')">' +
        '<i class="mdi mdi-trash-can-outline"></i>' +
        '</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '</div>';
}

function deleteAsignDesign(uid, name) {
    SweetPrompt.confirm('Slet design', 'Er du sikker på at du vil slette "' + name + '"?', {
        confirmButtonText: 'Ja, slet',
        onConfirm: async () => {
            const response = await post('api/merchant/asign/designs/delete', { uid: uid });
            if (response.success || response.status === 'success') {
                showSuccessNotification('Slettet', 'Design er blevet slettet');
                loadAsignDesigns();
            } else {
                showErrorNotification('Fejl', response.error?.message || 'Kunne ikke slette design');
            }
        }
    });
}
</script>

<style>
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.bg-orange-light {
    background-color: rgba(255, 152, 0, 0.1);
}

.color-orange {
    color: #FF9800;
}

.bg-purple-light {
    background-color: rgba(156, 39, 176, 0.1);
}

.color-purple {
    color: #9C27B0;
}

.inspiration-filter.active {
    background-color: var(--blue);
    color: white;
}
</style>
