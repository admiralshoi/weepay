<?php
/**
 * Admin Panel - Marketing Materials
 * Manage PDF templates for merchant marketing materials
 */

use classes\enumerations\Links;

$pageTitle = "Marketing Materialer";
$templates = $args->templates ?? null;
$typeOptions = $args->typeOptions ?? [];
$statusOptions = $args->statusOptions ?? [];

/**
 * Render a template card
 */
function renderTemplateCard($template, $statusOptions, bool $isActive = false): string {
    $cardBorderClass = $isActive ? 'border-success' : '';
    $cardStyle = $isActive ? 'border-width: 2px;' : '';

    $statusClass = match($template->status) {
        'ACTIVE' => 'badge-soft-success',
        'INACTIVE' => 'badge-soft-danger',
        default => 'badge-soft-warning'
    };
    $statusText = $statusOptions->{$template->status} ?? $template->status;

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

    $statusOptions = [];
    if ($template->status !== 'ACTIVE') {
        $statusOptions[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'ACTIVE\'); return false;"><i class="mdi mdi-check-circle mr-2"></i> Aktiver</a>';
    }
    if ($template->status !== 'DRAFT') {
        $statusOptions[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'DRAFT\'); return false;"><i class="mdi mdi-pencil-outline mr-2"></i> Flyt til kladde</a>';
    }
    if ($template->status !== 'INACTIVE') {
        $statusOptions[] = '<a class="dropdown-item" href="#" onclick="updateTemplateStatus(\'' . $template->uid . '\', \'INACTIVE\'); return false;"><i class="mdi mdi-close-circle mr-2"></i> Deaktiver</a>';
    }
    $statusOptionsHtml = implode('', $statusOptions);

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
?>
<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "marketing";
    var typeOptions = <?=json_encode($typeOptions)?>;
    var statusOptions = <?=json_encode($statusOptions)?>;
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
                    <p class="mb-0 font-14 color-gray">Upload og administrer PDF templates til forhandlere</p>
                </div>
                <button type="button" class="btn-v2 action-btn" onclick="openUploadModal()">
                    <i class="mdi mdi-plus mr-2"></i> Upload Template
                </button>
            </div>

            <!-- Templates List -->
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
                <!-- Active Templates -->
                <div class="flex-col-start" style="gap: 1rem;">
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
                <!-- Draft Templates -->
                <div class="flex-col-start" style="gap: 1rem;">
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
                <!-- Inactive Templates -->
                <div class="flex-col-start" style="gap: 1rem;">
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
                <!-- Empty State -->
                <div class="card border-radius-10px">
                    <div class="card-body flex-col-center flex-align-center py-5">
                        <div class="square-80 bg-light-gray border-radius-50 flex-row-center-center mb-3">
                            <i class="mdi mdi-file-pdf-outline font-40 color-gray"></i>
                        </div>
                        <p class="mb-0 font-18 font-weight-bold color-dark">Ingen templates</p>
                        <p class="mb-0 font-14 color-gray mt-2 text-center" style="max-width: 400px;">
                            Upload din foerste marketing template for at komme i gang.
                        </p>
                        <button type="button" class="btn-v2 action-btn mt-3" onclick="openUploadModal()">
                            <i class="mdi mdi-plus mr-2"></i> Upload Template
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Upload Modal -->
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

<!-- Edit Modal -->
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
