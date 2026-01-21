<?php
/**
 * Admin Panel - Marketing Template Placeholder Editor
 * Visual editor for placing QR codes, logos, and text on PDF templates
 */

use classes\enumerations\Links;

$pageTitle = "Template Editor";
$template = $args->template ?? null;
$placeholders = $args->placeholders ?? null;
$placeholderTypes = $args->placeholderTypes ?? [];

if (!$template) {
    return;
}
?>
<script>
    var pageTitle = <?=json_encode($pageTitle . ' - ' . ($template->name ?? ''))?>;
    activePage = "marketing";
    var templateUid = <?=json_encode($template->uid)?>;
    var templateFilePath = <?=json_encode(__url('api/admin/marketing/templates/' . $template->uid . '/pdf'))?>;
    var existingPlaceholders = <?=json_encode($placeholders ? $placeholders->toArray() : [])?>;
    var placeholderTypeOptions = <?=json_encode($placeholderTypes)?>;
</script>

<div class="page-content py-3">
    <div class="page-inner-content">
        <div class="flex-col-start" style="row-gap: 1rem;">

            <!-- Breadcrumb -->
            <div class="flex-row-start flex-align-center" style="gap: .5rem;">
                <a href="<?=__url(Links::$admin->panel)?>" class="font-13 color-gray hover-color-blue">Panel</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <a href="<?=__url(Links::$admin->panelMarketing)?>" class="font-13 color-gray hover-color-blue">Marketing</a>
                <i class="mdi mdi-chevron-right font-14 color-gray"></i>
                <span class="font-13 color-dark"><?=htmlspecialchars($template->name)?></span>
            </div>

            <!-- Page Header -->
            <div class="flex-row-between flex-align-center w-100 flex-wrap" style="gap: 1rem;">
                <div class="flex-col-start">
                    <h1 class="mb-0 font-24 font-weight-bold"><?=htmlspecialchars($template->name)?></h1>
                    <p class="mb-0 font-14 color-gray">Placer QR-koder, logoer og tekst paa PDF'en</p>
                </div>
                <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                    <a href="<?=__url(Links::$admin->panelMarketing)?>" class="btn-v2 mute-btn">
                        <i class="mdi mdi-arrow-left mr-1"></i> Tilbage
                    </a>
                    <button type="button" class="btn-v2 action-btn" onclick="savePlaceholders()" id="saveBtn">
                        <span class="btn-text"><i class="mdi mdi-content-save mr-1"></i> Gem placeholders</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>

            <!-- Main Editor Area -->
            <div class="row">
                <!-- PDF Viewer -->
                <div class="col-lg-9">
                    <div class="card border-radius-10px">
                        <div class="card-header bg-white flex-row-between flex-align-center">
                            <span class="font-14 font-weight-bold">PDF Forhaandsvisning</span>
                            <div class="flex-row-start flex-align-center" style="gap: 0.5rem;">
                                <button type="button" class="btn-v2 mute-btn btn-sm" onclick="previousPage()" id="prevPageBtn" disabled>
                                    <i class="mdi mdi-chevron-left"></i>
                                </button>
                                <span class="font-13">Side <span id="currentPage">1</span> af <span id="totalPages">1</span></span>
                                <button type="button" class="btn-v2 mute-btn btn-sm" onclick="nextPage()" id="nextPageBtn" disabled>
                                    <i class="mdi mdi-chevron-right"></i>
                                </button>
                                <span class="mx-2">|</span>
                                <button type="button" class="btn-v2 mute-btn btn-sm" onclick="zoomOut()">
                                    <i class="mdi mdi-minus"></i>
                                </button>
                                <span class="font-13" id="zoomLevel">100%</span>
                                <button type="button" class="btn-v2 mute-btn btn-sm" onclick="zoomIn()">
                                    <i class="mdi mdi-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0" style="overflow: auto; max-height: calc(100vh - 250px); background: #e9ecef;">
                            <div id="pdf-container" style="position: relative; display: inline-block; margin: 20px;">
                                <canvas id="pdf-canvas"></canvas>
                                <div id="placeholders-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Placeholder Controls -->
                <div class="col-lg-3">
                    <div class="card border-radius-10px">
                        <div class="card-header bg-white">
                            <span class="font-14 font-weight-bold">Tilfoej placeholder</span>
                        </div>
                        <div class="card-body">
                            <div class="flex-col-start" style="gap: 0.75rem;">
                                <button type="button" class="btn-v2 action-btn w-100" onclick="addPlaceholder('qr_code')">
                                    <i class="mdi mdi-qrcode mr-2"></i> QR-kode
                                </button>
                                <button type="button" class="btn-v2 action-btn w-100" onclick="addPlaceholder('location_name')">
                                    <i class="mdi mdi-text mr-2"></i> Lokationsnavn
                                </button>
                                <button type="button" class="btn-v2 action-btn w-100" onclick="addPlaceholder('location_logo')">
                                    <i class="mdi mdi-image mr-2"></i> Lokationslogo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder List -->
                    <div class="card border-radius-10px mt-3">
                        <div class="card-header bg-white">
                            <span class="font-14 font-weight-bold">Placeholders</span>
                        </div>
                        <div class="card-body p-0">
                            <div id="placeholder-list">
                                <div class="text-center text-muted font-13 py-4" id="no-placeholders-msg">
                                    Ingen placeholders tilfojet endnu
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Placeholder Properties -->
                    <div class="card border-radius-10px mt-3" id="placeholder-properties" style="display: none;">
                        <div class="card-header bg-white">
                            <span class="font-14 font-weight-bold">Egenskaber</span>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="font-13 font-weight-bold d-block mb-2">Type</label>
                                <select class="form-select-v2 w-100" id="prop-type" onchange="updateSelectedPlaceholder()">
                                    <?php foreach($placeholderTypes as $value => $label): ?>
                                        <option value="<?=$value?>"><?=$label?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Text properties (shown for location_name) -->
                            <div id="text-properties">
                                <div class="form-group">
                                    <label class="font-13 font-weight-bold d-block mb-2">Skriftstoerrelse</label>
                                    <input type="number" class="form-field-v2 w-100" id="prop-font-size" value="12" min="6" max="72" onchange="updateSelectedPlaceholder()">
                                </div>
                                <div class="form-group">
                                    <label class="font-13 font-weight-bold d-block mb-2">Farve</label>
                                    <input type="color" class="form-field-v2 w-100" id="prop-font-color" value="#000000" onchange="updateSelectedPlaceholder()" style="height: 35px;">
                                </div>
                            </div>

                            <button type="button" class="btn-v2 danger-btn w-100 mt-3" onclick="deleteSelectedPlaceholder()">
                                <i class="mdi mdi-delete mr-1"></i> Slet placeholder
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
