<?php
/**
 * Admin Panel - Marketing Materials
 * Manage PDF templates and inspiration for merchant marketing materials
 */

use classes\enumerations\Links;

$pageTitle = "Marketing Materialer";
$baseTemplates = $args->baseTemplates ?? null;
$templates = $args->templates ?? null;
$typeOptions = $args->typeOptions ?? [];
$statusOptions = $args->statusOptions ?? [];
$categoryOptions = $args->categoryOptions ?? [];
$inspirations = $args->inspirations ?? null;
$inspirationCategoryOptions = $args->inspirationCategoryOptions ?? [];
$inspirationStatusOptions = $args->inspirationStatusOptions ?? [];
$asignPreloads = $args->asignPreloads ?? null;

/**
 * Render a base template card
 */
function renderBaseTemplateCard($baseTemplate, $typeOptions): string {
    $previewHtml = !isEmpty($baseTemplate->preview_image)
        ? '<div class="template-preview mb-3" style="height: 180px; overflow: hidden; border-radius: 8px; background: #f8f9fa;">
               <img src="' . __url($baseTemplate->preview_image) . '" alt="' . htmlspecialchars($baseTemplate->name) . '" class="img-fluid w-100 h-100" style="object-fit: contain;">
           </div>'
        : '<div class="template-preview mb-3 flex-col-center flex-align-center" style="height: 180px; background: #f8f9fa; border-radius: 8px;">
               <i class="mdi mdi-file-pdf-outline font-50 color-gray"></i>
           </div>';

    $descriptionHtml = !isEmpty($baseTemplate->description)
        ? '<p class="font-13 color-gray mb-3" style="min-height: 40px;">' . htmlspecialchars(substr($baseTemplate->description, 0, 80)) . (strlen($baseTemplate->description) > 80 ? '...' : '') . '</p>'
        : '<p class="font-13 color-gray mb-3" style="min-height: 40px;"><em>Ingen beskrivelse</em></p>';

    $versionCount = $baseTemplate->version_count ?? 0;
    $versionBadge = $versionCount > 0
        ? '<span class="mute-box">' . $versionCount . ' version' . ($versionCount > 1 ? 'er' : '') . '</span>'
        : '<span class="mute-box">Ingen versioner</span>';

    return '
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card border-radius-10px h-100">
            <div class="card-body position-relative">
                ' . $previewHtml . '
                <h5 class="font-16 font-weight-bold mb-2">' . htmlspecialchars($baseTemplate->name) . '</h5>
                <div class="flex-row-start flex-align-center flex-wrap mb-2" style="gap: 0.5rem;">
                    <span class="mute-box">' . ($baseTemplate->type ?? 'A4') . '</span>
                    ' . $versionBadge . '
                </div>
                ' . $descriptionHtml . '
                <div class="flex-row-between flex-align-center mt-auto">
                    <button type="button" class="btn-v2 action-btn btn-sm" onclick="openCreateVersionModal(\'' . $baseTemplate->uid . '\', \'' . htmlspecialchars($baseTemplate->name) . '\', \'' . ($baseTemplate->type ?? 'A4') . '\')">
                        <i class="mdi mdi-plus mr-1"></i> Opret version
                    </button>
                    <div class="dropdown">
                        <button class="btn-v2 mute-btn btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="mdi mdi-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" onclick="editBaseTemplate(\'' . $baseTemplate->uid . '\', \'' . htmlspecialchars($baseTemplate->name) . '\', \'' . ($baseTemplate->type ?? 'A4') . '\', \'' . htmlspecialchars($baseTemplate->description ?? '') . '\'); return false;">
                                <i class="mdi mdi-pencil mr-2"></i> Rediger detaljer
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteBaseTemplate(\'' . $baseTemplate->uid . '\', \'' . htmlspecialchars($baseTemplate->name) . '\', ' . $versionCount . '); return false;">
                                <i class="mdi mdi-delete mr-2"></i> Slet
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Render a template card
 */
function renderTemplateCard($template, $statusOptions, bool $isActive = false): string {
    $cardBorderClass = $isActive ? 'border-success' : '';
    $cardStyle = $isActive ? 'border-width: 2px;' : '';

    $previewHtml = !isEmpty($template->preview_image)
        ? '<div class="template-preview mb-3" style="height: 180px; overflow: hidden; border-radius: 8px; background: #f8f9fa;">
               <img src="' . __url($template->preview_image) . '" alt="' . htmlspecialchars($template->name) . '" class="img-fluid w-100 h-100" style="object-fit: contain;">
           </div>'
        : '<div class="template-preview mb-3 flex-col-center flex-align-center" style="height: 180px; background: #f8f9fa; border-radius: 8px;">
               <i class="mdi mdi-file-pdf-outline font-50 color-gray"></i>
           </div>';

    $descriptionHtml = !isEmpty($template->description)
        ? '<p class="font-13 color-gray mb-3" style="min-height: 40px;">' . htmlspecialchars(substr($template->description, 0, 80)) . (strlen($template->description) > 80 ? '...' : '') . '</p>'
        : '<p class="font-13 color-gray mb-3" style="min-height: 40px;"><em>Ingen beskrivelse</em></p>';

    $statusOptionsArr = [];
    if ($template->status !== 'ACTIVE') {
        $statusOptionsArr[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'ACTIVE\'); return false;"><i class="mdi mdi-check-circle mr-2"></i> Aktiver</a>';
    }
    if ($template->status !== 'DRAFT') {
        $statusOptionsArr[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'DRAFT\'); return false;"><i class="mdi mdi-pencil-outline mr-2"></i> Flyt til kladde</a>';
    }
    if ($template->status !== 'INACTIVE') {
        $statusOptionsArr[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'INACTIVE\'); return false;"><i class="mdi mdi-close-circle mr-2"></i> Deaktiver</a>';
    }
    $statusOptionsHtml = implode('', $statusOptionsArr);

    $activeIndicator = $isActive
        ? '<div class="position-absolute" style="top: 10px; right: 10px;"><span class="success-box"><i class="mdi mdi-check-circle mr-1"></i>Aktiv</span></div>'
        : '';

    return '
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card border-radius-10px h-100 ' . $cardBorderClass . '" style="' . $cardStyle . '">
            <div class="card-body position-relative">
                ' . $activeIndicator . '
                ' . $previewHtml . '
                <h5 class="font-16 font-weight-bold mb-2">' . htmlspecialchars($template->name) . '</h5>
                <div class="flex-row-start flex-align-center flex-wrap mb-2" style="gap: 0.5rem;">
                    <span class="mute-box">' . $template->type . '</span>
                </div>
                ' . $descriptionHtml . '
                <div class="flex-row-between flex-align-center mt-auto">
                    <a href="' . __url(Links::$admin->panelMarketing . '/' . $template->uid . '/editor') . '" class="btn-v2 action-btn btn-sm">
                        <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                    </a>
                    <div class="dropdown">
                        <button class="btn-v2 mute-btn btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                            <i class="mdi mdi-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="#" onclick="editTemplate(\'' . $template->uid . '\', \'' . htmlspecialchars($template->name) . '\', \'' . $template->type . '\', \'' . htmlspecialchars($template->description ?? '') . '\', \'' . $template->status . '\'); return false;">
                                <i class="mdi mdi-pencil mr-2"></i> Rediger detaljer
                            </a>
                            ' . $statusOptionsHtml . '
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteTemplate(\'' . $template->uid . '\', \'' . htmlspecialchars($template->name) . '\'); return false;">
                                <i class="mdi mdi-delete mr-2"></i> Slet
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Render an A-Sign preload background card
 */
function renderAsignPreloadCard($item, $statusOptions): string {
    $statusClass = match($item->status) {
        'ACTIVE' => 'success-box',
        'INACTIVE' => 'danger-box',
        default => 'warning-box'
    };
    $statusText = $statusOptions->{$item->status} ?? $item->status;
    // Use thumbnail if available, fallback to full image
    $previewImage = !empty($item->thumbnail_path) ? $item->thumbnail_path : $item->image_path;

    return '
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card border-radius-10px h-100">
            <div class="card-body position-relative">
                <div class="template-preview mb-3" style="height: 180px; overflow: hidden; border-radius: 8px; background: #f8f9fa;">
                    <img src="' . __url($previewImage) . '" alt="' . htmlspecialchars($item->title) . '" class="img-fluid w-100 h-100" style="object-fit: cover;">
                </div>
                <h5 class="font-16 font-weight-bold mb-2">' . htmlspecialchars($item->title) . '</h5>
                <div class="flex-row-start flex-align-center flex-wrap mb-2" style="gap: 0.5rem;">
                    <span class="' . $statusClass . '">' . $statusText . '</span>
                </div>
                <div class="flex-row-between flex-align-center mt-auto">
                    <button type="button" class="btn-v2 action-btn btn-sm" onclick="editAsignPreload(\'' . $item->uid . '\', \'' . htmlspecialchars($item->title) . '\', \'' . htmlspecialchars($item->description ?? '') . '\', \'' . $item->status . '\')">
                        <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                    </button>
                    <button type="button" class="btn-v2 danger-btn btn-sm" onclick="deleteAsignPreload(\'' . $item->uid . '\', \'' . htmlspecialchars($item->title) . '\')">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>';
}

/**
 * Render an inspiration card
 */
function renderInspirationCard($item, $categoryOptions, $statusOptions): string {
    $statusClass = match($item->status) {
        'ACTIVE' => 'success-box',
        'INACTIVE' => 'danger-box',
        default => 'warning-box'
    };
    $statusText = $statusOptions->{$item->status} ?? $item->status;
    $categoryText = $categoryOptions->{$item->category} ?? $item->category;
    // Use thumbnail if available, fallback to full image
    $previewImage = !empty($item->thumbnail_path) ? $item->thumbnail_path : $item->image_path;

    return '
    <div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card border-radius-10px h-100">
            <div class="card-body position-relative">
                <div class="template-preview mb-3" style="height: 180px; overflow: hidden; border-radius: 8px; background: #f8f9fa;">
                    <img src="' . __url($previewImage) . '" alt="' . htmlspecialchars($item->title) . '" class="img-fluid w-100 h-100" style="object-fit: cover;">
                </div>
                <h5 class="font-16 font-weight-bold mb-2">' . htmlspecialchars($item->title) . '</h5>
                <div class="flex-row-start flex-align-center flex-wrap mb-2" style="gap: 0.5rem;">
                    <span class="mute-box">' . $categoryText . '</span>
                    <span class="' . $statusClass . '">' . $statusText . '</span>
                </div>
                <div class="flex-row-between flex-align-center mt-auto">
                    <button type="button" class="btn-v2 action-btn btn-sm" onclick="editInspiration(\'' . $item->uid . '\', \'' . htmlspecialchars($item->title) . '\', \'' . $item->category . '\', \'' . htmlspecialchars($item->description ?? '') . '\', \'' . $item->status . '\')">
                        <i class="mdi mdi-pencil-outline mr-1"></i> Rediger
                    </button>
                    <button type="button" class="btn-v2 danger-btn btn-sm" onclick="deleteInspiration(\'' . $item->uid . '\', \'' . htmlspecialchars($item->title) . '\')">
                        <i class="mdi mdi-delete"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>';
}
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "marketing";
    var typeOptions = <?=json_encode($typeOptions)?>;
    var statusOptions = <?=json_encode($statusOptions)?>;
    var inspirationCategoryOptions = <?=json_encode($inspirationCategoryOptions)?>;
    var inspirationStatusOptions = <?=json_encode($inspirationStatusOptions)?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1.5rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark">Marketing Materialer</span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold">Marketing Materialer</h1>
                    <p class="mb-0 font-14 color-gray">Administrer templates og inspiration til forhandlere</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex-row-start flex-align-center" style="gap: 0; border-bottom: 2px solid #e9ecef;">
                <button type="button" class="tab-btn active" data-tab="base-templates" onclick="switchTab('base-templates')">
                    <i class="mdi mdi-file-document-outline mr-2"></i> Base Templates
                </button>
                <button type="button" class="tab-btn" data-tab="templates" onclick="switchTab('templates')">
                    <i class="mdi mdi-file-pdf-outline mr-2"></i> Publicerede Versioner
                </button>
                <button type="button" class="tab-btn" data-tab="inspiration" onclick="switchTab('inspiration')">
                    <i class="mdi mdi-image-multiple mr-2"></i> Inspiration
                </button>
                <button type="button" class="tab-btn" data-tab="asign" onclick="switchTab('asign')">
                    <i class="mdi mdi-sign-real-estate mr-2"></i> A-Skilt Generator
                </button>
            </div>

            <!-- Tab Content: Base Templates -->
            <div id="tab-base-templates" class="tab-content">
                <div class="flex-row-between flex-align-center mb-3">
                    <div>
                        <p class="mb-0 font-14 color-gray">Upload PDF-filer som kan genbruges til flere versioner med forskellige QR-placeringer.</p>
                    </div>
                    <button type="button" class="btn-v2 action-btn" onclick="openBaseTemplateUploadModal()">
                        <i class="mdi mdi-plus mr-2"></i> Upload Base Template
                    </button>
                </div>

                <?php if(!isEmpty($baseTemplates)): ?>
                    <div class="row rg-1">
                        <?php foreach($baseTemplates as $baseTemplate): ?>
                            <?php echo renderBaseTemplateCard((object)$baseTemplate, $typeOptions); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-radius-10px">
                        <div class="card-body flex-col-center flex-align-center py-5">
                            <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-file-document-outline font-40 color-gray"></i>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold color-dark">Ingen base templates</p>
                            <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                                Upload en PDF-fil som base template. Du kan derefter oprette flere versioner med forskellige QR-placeringer uden at uploade filen igen.
                            </p>
                            <button type="button" class="btn-v2 action-btn mt-3" onclick="openBaseTemplateUploadModal()">
                                <i class="mdi mdi-plus mr-2"></i> Upload Base Template
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Templates (Published Versions) -->
            <div id="tab-templates" class="tab-content" style="display: none;">
                <div class="flex-row-between flex-align-center mb-3">
                    <div>
                        <p class="mb-0 font-14 color-gray">Versioner af templates med konfigurerede QR-placeringer. Disse kan downloades af forhandlere.</p>
                    </div>
                    <button type="button" class="btn-v2 action-btn" onclick="openUploadModal()">
                        <i class="mdi mdi-plus mr-2"></i> Upload Standalone Template
                    </button>
                </div>

                <?php if($templates && !$templates->empty()):
                    // Group templates by status
                    $activeTemplates = [];
                    $draftTemplates = [];
                    $inactiveTemplates = [];
                    foreach($templates->list() as $template) {
                        match($template->status) {
                            'ACTIVE' => $activeTemplates[] = $template,
                            'DRAFT' => $draftTemplates[] = $template,
                            'INACTIVE' => $inactiveTemplates[] = $template,
                            default => $draftTemplates[] = $template
                        };
                    }
                ?>

                    <?php if(!empty($activeTemplates)): ?>
                    <div class="flex-col-start mb-4" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                            <span class="square-10 bg-green border-radius-50"></span>
                            <h2 class="mb-0 font-18 font-weight-bold">Aktive Templates</h2>
                            <span class="success-box"><?=count($activeTemplates)?></span>
                        </div>
                        <div class="row rg-1">
                            <?php foreach($activeTemplates as $template): ?>
                                <?php echo renderTemplateCard($template, $statusOptions, true); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($draftTemplates)): ?>
                    <div class="flex-col-start mb-4" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                            <span class="square-10 bg-pee-yellow border-radius-50"></span>
                            <h2 class="mb-0 font-18 font-weight-bold">Kladder</h2>
                            <span class="warning-box"><?=count($draftTemplates)?></span>
                        </div>
                        <div class="row rg-1">
                            <?php foreach($draftTemplates as $template): ?>
                                <?php echo renderTemplateCard($template, $statusOptions, false); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($inactiveTemplates)): ?>
                    <div class="flex-col-start mb-4" style="gap: 1rem;">
                        <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                            <span class="square-10 bg-red border-radius-50"></span>
                            <h2 class="mb-0 font-18 font-weight-bold">Inaktive Templates</h2>
                            <span class="danger-box"><?=count($inactiveTemplates)?></span>
                        </div>
                        <div class="row rg-1">
                            <?php foreach($inactiveTemplates as $template): ?>
                                <?php echo renderTemplateCard($template, $statusOptions, false); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="card border-radius-10px">
                        <div class="card-body flex-col-center flex-align-center py-5">
                            <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-file-pdf-outline font-40 color-gray"></i>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold color-dark">Ingen templates</p>
                            <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                                Upload din første marketing template for at komme i gang.
                            </p>
                            <button type="button" class="btn-v2 action-btn mt-3" onclick="openUploadModal()">
                                <i class="mdi mdi-plus mr-2"></i> Upload Template
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Content: Inspiration -->
            <div id="tab-inspiration" class="tab-content" style="display: none;">
                <div class="flex-row-between flex-align-center mb-3">
                    <div></div>
                    <button type="button" class="btn-v2 action-btn" onclick="openInspirationUploadModal()">
                        <i class="mdi mdi-plus mr-2"></i> Upload Inspiration
                    </button>
                </div>

                <?php if($inspirations && !$inspirations->empty()): ?>
                    <div class="row rg-1">
                        <?php foreach($inspirations->list() as $item): ?>
                            <?php echo renderInspirationCard($item, (object)$inspirationCategoryOptions, (object)$inspirationStatusOptions); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-radius-10px">
                        <div class="card-body flex-col-center flex-align-center py-5">
                            <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-image-multiple font-40 color-gray"></i>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold color-dark">Ingen inspiration</p>
                            <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                                Upload billeder af Instagram posts, A-skilte og andre eksempler som forhandlere kan bruge som inspiration.
                            </p>
                            <button type="button" class="btn-v2 action-btn mt-3" onclick="openInspirationUploadModal()">
                                <i class="mdi mdi-plus mr-2"></i> Upload Inspiration
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab Content: A-Sign Generator (Preload Backgrounds) -->
            <div id="tab-asign" class="tab-content" style="display: none;">
                <div class="flex-row-between flex-align-center mb-3">
                    <div>
                        <p class="mb-0 font-14 color-gray">Upload baggrundsbilleder som forhandlere kan bruge i A-Skilt Generatoren.</p>
                    </div>
                    <button type="button" class="btn-v2 action-btn" onclick="openAsignPreloadUploadModal()">
                        <i class="mdi mdi-plus mr-2"></i> Upload baggrund
                    </button>
                </div>

                <?php if($asignPreloads && !$asignPreloads->empty()): ?>
                    <div class="row rg-1">
                        <?php foreach($asignPreloads->list() as $item): ?>
                            <?php echo renderAsignPreloadCard($item, (object)$inspirationStatusOptions); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-radius-10px">
                        <div class="card-body flex-col-center flex-align-center py-5">
                            <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                                <i class="mdi mdi-image-multiple font-40 color-gray"></i>
                            </div>
                            <p class="mb-0 font-18 font-weight-bold color-dark">Ingen baggrundsbilleder</p>
                            <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                                Upload billeder som forhandlere kan bruge som baggrund i A-Skilt Generatoren.
                            </p>
                            <button type="button" class="btn-v2 action-btn mt-3" onclick="openAsignPreloadUploadModal()">
                                <i class="mdi mdi-plus mr-2"></i> Upload baggrund
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<style>
    .tab-btn {
        background: none;
        border: none;
        padding: 0.75rem 1.25rem;
        font-size: 14px;
        font-weight: 500;
        color: #6c757d;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
    }
    .tab-btn:hover {
        color: #495057;
    }
    .tab-btn.active {
        color: var(--primary-cta);
        border-bottom-color: var(--primary-cta);
    }
</style>

<!-- Upload Template Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload ny template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">PDF Fil *</label>
                        <input type="file" name="file" id="templateFile" accept=".pdf" required>
                        <small class="form-text text-muted">Maks 20MB. Kun PDF filer.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Navn *</label>
                        <input type="text" class="form-field-v2 w-100" name="name" id="templateName" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Type</label>
                        <select class="form-select-v2 w-100" name="type" id="templateType">
                            <?php foreach($typeOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="templateDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="uploadTemplate()" id="uploadBtn">
                    <span class="btn-text">Upload</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" name="uid" id="editUid">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Navn *</label>
                        <input type="text" class="form-field-v2 w-100" name="name" id="editName" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Type</label>
                        <select class="form-select-v2 w-100" name="type" id="editType">
                            <?php foreach($typeOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Status</label>
                        <select class="form-select-v2 w-100" name="status" id="editStatus">
                            <?php foreach($statusOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="editDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveTemplateChanges()" id="saveBtn">
                    <span class="btn-text">Gem</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Inspiration Modal -->
<div class="modal fade" id="inspirationUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload inspiration</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="inspirationUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Billede *</label>
                        <input type="file" name="file" id="inspirationFile" accept="image/*" required>
                        <small class="form-text text-muted">Maks 30MB. JPG, PNG, GIF eller WebP.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Titel *</label>
                        <input type="text" class="form-field-v2 w-100" name="title" id="inspirationTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Kategori</label>
                        <select class="form-select-v2 w-100" name="category" id="inspirationCategory">
                            <?php foreach($inspirationCategoryOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="inspirationDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="uploadInspiration()" id="inspirationUploadBtn">
                    <span class="btn-text">Upload</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Inspiration Modal -->
<div class="modal fade" id="inspirationEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger inspiration</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="inspirationEditForm">
                    <input type="hidden" name="uid" id="inspirationEditUid">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Titel *</label>
                        <input type="text" class="form-field-v2 w-100" name="title" id="inspirationEditTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Kategori</label>
                        <select class="form-select-v2 w-100" name="category" id="inspirationEditCategory">
                            <?php foreach($inspirationCategoryOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Status</label>
                        <select class="form-select-v2 w-100" name="status" id="inspirationEditStatus">
                            <?php foreach($inspirationStatusOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="inspirationEditDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveInspirationChanges()" id="inspirationSaveBtn">
                    <span class="btn-text">Gem</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload A-Sign Preload Modal -->
<div class="modal fade" id="asignPreloadUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload baggrundsbillede</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="asignPreloadUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Billede *</label>
                        <input type="file" name="file" id="asignPreloadFile" accept="image/*" required>
                        <small class="form-text text-muted">Maks 30MB. JPG, PNG, GIF eller WebP. Anbefalet format: Portrætorienteret.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Titel *</label>
                        <input type="text" class="form-field-v2 w-100" name="title" id="asignPreloadTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="asignPreloadDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="uploadAsignPreload()" id="asignPreloadUploadBtn">
                    <span class="btn-text">Upload</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit A-Sign Preload Modal -->
<div class="modal fade" id="asignPreloadEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger baggrundsbillede</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="asignPreloadEditForm">
                    <input type="hidden" name="uid" id="asignPreloadEditUid">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Titel *</label>
                        <input type="text" class="form-field-v2 w-100" name="title" id="asignPreloadEditTitle" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Status</label>
                        <select class="form-select-v2 w-100" name="status" id="asignPreloadEditStatus">
                            <?php foreach($inspirationStatusOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="asignPreloadEditDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveAsignPreloadChanges()" id="asignPreloadSaveBtn">
                    <span class="btn-text">Gem</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Base Template Modal -->
<div class="modal fade" id="baseTemplateUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload base template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="baseTemplateUploadForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">PDF Fil *</label>
                        <input type="file" name="file" id="baseTemplateFile" accept=".pdf" required>
                        <small class="form-text text-muted">Maks 20MB. Kun PDF filer. Denne fil kan genbruges til flere versioner.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Navn *</label>
                        <input type="text" class="form-field-v2 w-100" name="name" id="baseTemplateName" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Type</label>
                        <select class="form-select-v2 w-100" name="type" id="baseTemplateType">
                            <?php foreach($typeOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="baseTemplateDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="uploadBaseTemplate()" id="baseTemplateUploadBtn">
                    <span class="btn-text">Upload</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Base Template Modal -->
<div class="modal fade" id="baseTemplateEditModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger base template</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="baseTemplateEditForm">
                    <input type="hidden" name="uid" id="baseTemplateEditUid">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Navn *</label>
                        <input type="text" class="form-field-v2 w-100" name="name" id="baseTemplateEditName" required>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Type</label>
                        <select class="form-select-v2 w-100" name="type" id="baseTemplateEditType">
                            <?php foreach($typeOptions as $value => $label): ?>
                                <option value="<?=$value?>"><?=$label?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="baseTemplateEditDescription" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="saveBaseTemplateChanges()" id="baseTemplateSaveBtn">
                    <span class="btn-text">Gem</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Version from Base Template Modal -->
<div class="modal fade" id="createVersionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Opret ny version</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i class="mdi mdi-information mr-2"></i>
                    <span id="createVersionBaseInfo"></span>
                </div>
                <form id="createVersionForm">
                    <input type="hidden" name="base_template_uid" id="createVersionBaseUid">
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Versionsnavn *</label>
                        <input type="text" class="form-field-v2 w-100" name="name" id="createVersionName" required placeholder="f.eks. A4 Plakat - QR nederst">
                        <small class="form-text text-muted">Giv versionen et beskrivende navn, så du kan skelne mellem forskellige placeringer.</small>
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Intern note (valgfrit)</label>
                        <input type="text" class="form-field-v2 w-100" name="version_name" id="createVersionNote" placeholder="f.eks. QR i bunden, logo øverst">
                    </div>
                    <div class="form-group">
                        <label class="font-14 font-weight-bold d-block mb-2">Beskrivelse</label>
                        <textarea class="form-field-v2 w-100" name="description" id="createVersionDescription" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="createVersionFromBase()" id="createVersionBtn">
                    <span class="btn-text">Opret & Rediger</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>
