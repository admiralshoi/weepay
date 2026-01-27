<?php
/**
 * A-Sign Designer Editor
 * @var object $args
 */

use classes\enumerations\Links;

$pageTitle = "A-Skilt Designer";
$design = $args->design ?? null;
$isNew = $args->isNew ?? true;
$locations = $args->locations ?? [];
$typeOptions = $args->typeOptions ?? [];

// Prepare inspiration data (legacy, keep for now)
$designInspirations = $args->designInspirations ?? [];
$arbitraryInspirations = $args->arbitraryInspirations ?? [];
$legacyInspirations = $args->legacyInspirations ?? [];

// Preload backgrounds from admin
$preloads = $args->preloads ?? [];
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "materials";

    // Editor configuration
    window.editorConfig = {
        isNew: <?=json_encode($isNew)?>,
        design: <?=json_encode($design ? [
            'uid' => $design->uid,
            'name' => $design->name,
            'type' => $design->type,
            'size' => $design->size ?? 'A1',
            'status' => $design->status,
            'background_image' => $design->background_image ? __url($design->background_image) : null,
            'logo_image' => isset($design->logo_image) && $design->logo_image ? __url($design->logo_image) : null,
            'canvas_data' => $design->canvas_data,
            'elements' => $design->elements,
            'bar_color' => $design->bar_color,
            'location' => (is_object($design->location) && $design->location) ? [
                'uid' => $design->location->uid,
                'name' => $design->location->name,
                'slug' => $design->location->slug,
            ] : null,
        ] : null)?>,
        locations: <?=json_encode(toArray($locations->list()))?>,
        typeOptions: <?=json_encode($typeOptions)?>,
        inspirations: {
            design: <?=json_encode($designInspirations->map(function($item) {
                return [
                    'uid' => $item['uid'],
                    'title' => $item['title'],
                    'image' => __url($item['image_path']),
                ];
            })->toArray())?>,
            arbitrary: <?=json_encode($arbitraryInspirations->map(function($item) {
                return [
                    'uid' => $item['uid'],
                    'title' => $item['title'],
                    'image' => __url($item['image_path']),
                ];
            })->toArray())?>,
            legacy: <?=json_encode($legacyInspirations->map(function($item) {
                return [
                    'uid' => $item['uid'],
                    'title' => $item['title'],
                    'image' => __url($item['image_path']),
                ];
            })->toArray())?>
        },
        // Preload backgrounds from admin
        preloads: <?=json_encode($preloads->map(function($item) {
            return [
                'uid' => $item['uid'],
                'title' => $item['title'],
                'description' => $item['description'] ?? '',
                'image' => __url($item['image_path']),
                'thumbnail' => !empty($item['thumbnail_path']) ? __url($item['thumbnail_path']) : __url($item['image_path']),
            ];
        })->toArray())?>,
        // A-Sign size options (real-world mm and 300 DPI pixels for print)
        // Display canvas is scaled down for screen (max ~400px width)
        sizes: {
            'A1': {
                name: 'A1 (594 × 841 mm)',
                widthMm: 594,
                heightMm: 841,
                widthPx: 7016,   // 594mm at 300 DPI
                heightPx: 9933,  // 841mm at 300 DPI
                displayScale: 0.057, // Scale to ~400px width for screen
            },
            'B1': {
                name: 'B1 (700 × 1000 mm)',
                widthMm: 700,
                heightMm: 1000,
                widthPx: 8268,   // 700mm at 300 DPI
                heightPx: 11811, // 1000mm at 300 DPI
                displayScale: 0.048,
            },
            'A0': {
                name: 'A0 (841 × 1189 mm)',
                widthMm: 841,
                heightMm: 1189,
                widthPx: 9933,   // 841mm at 300 DPI
                heightPx: 14043, // 1189mm at 300 DPI
                displayScale: 0.040,
            },
            '50x70': {
                name: '50 × 70 cm',
                widthMm: 500,
                heightMm: 700,
                widthPx: 5906,   // 500mm at 300 DPI
                heightPx: 8268,  // 700mm at 300 DPI
                displayScale: 0.068,
            },
        },
        defaultSize: 'A1',
        barHeightPercent: 15, // Bottom bar is 15% of total height
    };
</script>

<div class="page-content">

<!-- Page Header -->
<div class="flex-row-between flex-align-center flex-wrap mb-4" style="gap: .75rem;">
    <div class="flex-row-center flex-align-center" style="gap: 1rem;">
        <a href="<?=__url(Links::$merchant->materials)?>" class="btn-v2 mute-btn" style="padding: 8px 12px;">
            <i class="mdi mdi-arrow-left"></i>
        </a>
        <div>
            <h4 class="mb-0 font-weight-bold"><?=$pageTitle?></h4>
            <p class="mb-0 font-13 color-gray">
                <?=$isNew ? 'Opret nyt A-skilt design' : 'Rediger: ' . htmlspecialchars($design->name)?>
            </p>
        </div>
    </div>
    <div class="flex-row-center" style="gap: .5rem;">
        <button type="button" class="btn-v2 mute-btn" onclick="saveDesign(true)" id="saveDraftBtn">
            <i class="mdi mdi-content-save-outline me-1"></i>
            Gem kladde
        </button>
        <button type="button" class="btn-v2 action-btn" onclick="saveAndExport()" id="exportBtn" <?=$isNew ? 'disabled' : ''?>>
            <i class="mdi mdi-download me-1"></i>
            Eksporter PDF
        </button>
    </div>
</div>

<!-- Editor Layout -->
<div class="editor-container">
    <div class="row g-0">
        <!-- Left Sidebar: Tools & Settings -->
        <div class="col-lg-3 col-md-4">
            <div class="editor-sidebar bg-white border-radius-10px p-3 shadow-sm">
                <!-- Design Info -->
                <div class="sidebar-section mb-4">
                    <h6 class="font-weight-bold mb-3 d-none d-lg-flex"><i class="mdi mdi-information-outline me-2"></i>Design Info</h6>
                    <div class="sidebar-fields">
                        <div class="mb-3 mb-lg-3">
                            <label class="form-label font-13 d-none d-lg-block">Navn</label>
                            <input type="text" id="designName" class="form-field-v2 w-100"
                                   placeholder="Design navn"
                                   value="<?=$design ? htmlspecialchars($design->name) : ''?>">
                        </div>
                        <div class="mb-3 mb-lg-3">
                            <label class="form-label font-13 d-none d-lg-block">Type</label>
                            <select id="designType" class="form-select-v2 w-100 h-45px" onchange="onTypeChange()">
                                <?php foreach ($typeOptions as $value => $label): ?>
                                    <option value="<?=$value?>" <?=($design && $design->type === $value) ? 'selected' : ''?>>
                                        <?=$label?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3 mb-lg-3">
                            <label class="form-label font-13 d-none d-lg-block">Størrelse</label>
                            <select id="designSize" class="form-select-v2 w-100 h-45px" onchange="onSizeChange()">
                                <option value="A1">A1 (594 × 841 mm)</option>
                                <option value="B1">B1 (700 × 1000 mm)</option>
                                <option value="A0">A0 (841 × 1189 mm)</option>
                                <option value="50x70">50 × 70 cm</option>
                            </select>
                        </div>
                        <div class="mb-3 mb-lg-3">
                            <label class="form-label font-13 d-none d-lg-block">Lokation (til QR)</label>
                            <select id="designLocation" class="form-select-v2 w-100 h-45px" onchange="onLocationChange()">
                                <option value="">Vælg lokation...</option>
                                <?php foreach ($locations->list() as $loc): ?>
                                    <option value="<?=$loc->uid?>"
                                            data-name="<?=htmlspecialchars($loc->name)?>"
                                            data-slug="<?=htmlspecialchars($loc->slug)?>"
                                            <?=($design && is_object($design->location) && $design->location->uid === $loc->uid) ? 'selected' : ''?>>
                                        <?=htmlspecialchars($loc->name)?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Background -->
                <div class="sidebar-section mb-4">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-image me-2"></i>Baggrund</h6>
                    <div class="upload-area border-dashed p-3 text-center cursor-pointer mb-3" onclick="triggerBackgroundUpload()">
                        <i class="mdi mdi-cloud-upload font-24 color-gray"></i>
                        <p class="font-13 mb-0 color-gray">Klik for at uploade billede</p>
                        <p class="font-11 color-muted mb-0">JPG, PNG, WebP, SVG (max 30MB)</p>
                    </div>
                    <input type="file" id="backgroundUpload" accept="image/*" style="display: none;" onchange="handleBackgroundUpload(this)">
                    <button type="button" class="btn-v2 mute-btn w-100 font-13" onclick="removeBackground()">
                        <i class="mdi mdi-trash-can-outline me-1"></i>
                        Fjern baggrund
                    </button>
                </div>

                <!-- Elements -->
                <div class="sidebar-section mb-4">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-shape-plus me-2"></i>Elementer</h6>
                    <div class="element-buttons">
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left" onclick="addTextElement()">
                            <i class="mdi mdi-format-text me-2"></i>Tilf&oslash;j tekst
                        </button>
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left" onclick="addQrCode()">
                            <i class="mdi mdi-qrcode me-2"></i>Tilf&oslash;j QR-kode
                        </button>
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left" onclick="addLogo()">
                            <i class="mdi mdi-image-area me-2"></i>Tilf&oslash;j logo
                        </button>
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left design-type-only" onclick="addBadge()">
                            <i class="mdi mdi-tag me-2"></i>Tilf&oslash;j badge
                        </button>
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left arbitrary-type-only" onclick="addShape('rect')">
                            <i class="mdi mdi-rectangle-outline me-2"></i>Tilf&oslash;j rektangel
                        </button>
                        <button type="button" class="btn-v2 mute-btn w-100 mb-2 text-left arbitrary-type-only" onclick="addShape('circle')">
                            <i class="mdi mdi-circle-outline me-2"></i>Tilf&oslash;j cirkel
                        </button>
                    </div>
                </div>

                <!-- Bottom Bar (Design type only) -->
                <div class="sidebar-section mb-4 design-type-only" id="bottomBarSection">
                    <h6 class="font-weight-bold mb-3 d-none d-lg-flex"><i class="mdi mdi-dock-bottom me-2"></i>Bundbjælke</h6>
                    <div class="sidebar-fields">
                        <div class="mb-3">
                            <label class="form-label font-13 d-none d-lg-block">Farve</label>
                            <div class="flex-row-center" style="gap: .5rem;">
                                <input type="color" id="barColor"
                                       style="width: 50px; height: 36px; padding: 2px; border-radius: 6px; border: 1px solid #ddd;"
                                       value="<?=$design ? $design->bar_color : '#173c90'?>"
                                       onchange="updateBarColor(this.value)">
                                <input type="text" id="barColorHex" class="form-control"
                                       style="flex: 1; height: 36px; font-size: 13px;"
                                       value="<?=$design ? $design->bar_color : '#173c90'?>"
                                       onchange="updateBarColor(this.value)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layer Controls -->
                <div class="sidebar-section mb-4">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-layers me-2"></i>Lag</h6>
                    <div class="layer-buttons flex-row-center" style="gap: .5rem; flex-wrap: wrap;">
                        <button type="button" class="btn-v2 mute-btn font-12" onclick="bringForward()" data-toggle="tooltip" data-placement="top" title="Flyt ét lag op">
                            <i class="mdi mdi-arrange-bring-forward"></i>
                        </button>
                        <button type="button" class="btn-v2 mute-btn font-12" onclick="bringToFront()" data-toggle="tooltip" data-placement="top" title="Flyt øverst">
                            <i class="mdi mdi-arrange-bring-to-front"></i>
                        </button>
                        <button type="button" class="btn-v2 mute-btn font-12" onclick="sendBackward()" data-toggle="tooltip" data-placement="top" title="Flyt ét lag ned">
                            <i class="mdi mdi-arrange-send-backward"></i>
                        </button>
                        <button type="button" class="btn-v2 mute-btn font-12" onclick="sendToBack()" data-toggle="tooltip" data-placement="top" title="Flyt bagerst">
                            <i class="mdi mdi-arrange-send-to-back"></i>
                        </button>
                        <button type="button" class="btn-v2 mute-btn font-12 text-danger" onclick="deleteSelected()" data-toggle="tooltip" data-placement="top" title="Slet valgte element">
                            <i class="mdi mdi-trash-can-outline"></i>
                        </button>
                    </div>
                </div>

                <!-- Element List -->
                <div class="sidebar-section">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-format-list-bulleted me-2"></i>Alle elementer</h6>
                    <div id="elementList" class="element-list">
                        <p class="font-13 color-gray mb-0">Ingen elementer tilføjet</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Center: Canvas -->
        <div class="col-lg-6 col-md-4">
            <div class="canvas-container">
                <div class="canvas-wrapper">
                    <div class="asign-frame" id="canvasFrame">
                        <canvas id="asignCanvas"></canvas>
                    </div>
                </div>
                <div class="canvas-info text-center mt-2">
                    <span id="canvasSizeInfo" class="font-12 color-gray">594 × 841 mm (A1)</span>
                </div>
            </div>
        </div>

        <!-- Right Sidebar: Inspiration & Properties -->
        <div class="col-lg-3 col-md-4">
            <div class="editor-sidebar bg-white border-radius-10px shadow-sm">
                <!-- Properties Panel (shown when element selected) -->
                <div class="properties-panel p-3" id="propertiesPanel" style="display: none;">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-tune me-2"></i>Egenskaber</h6>
                    <div id="propertiesContent">
                        <!-- Dynamic properties based on selected element -->
                    </div>
                </div>

                <!-- Preload Backgrounds Panel -->
                <div class="preloads-panel p-3" id="preloadsPanel">
                    <h6 class="font-weight-bold mb-3"><i class="mdi mdi-image-multiple me-2"></i>Baggrunde</h6>
                    <p class="font-12 color-gray mb-3">Klik på et billede for at bruge det som baggrund</p>
                    <div class="preloads-grid" id="preloadsGrid">
                        <!-- Populated by JS with preload backgrounds -->
                    </div>
                    <div class="text-center mt-3" id="noPreloadsMessage" style="display: none;">
                        <p class="font-13 color-gray mb-0">Ingen baggrundsbilleder tilgængelige endnu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div><!-- /.page-content -->

<!-- Background Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Beskær billede</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="font-13 color-gray mb-2">
                    <i class="mdi mdi-information-outline me-1"></i>
                    Træk i billedet for at flytte det. Brug hjørnerne til at justere udsnittet.
                </p>
                <div class="crop-container" style="max-height: 450px; overflow: hidden;">
                    <img id="cropImage" src="" style="max-width: 100%;">
                </div>
                <div class="mt-2 flex-row-between flex-align-center">
                    <span id="cropInfo" class="font-12 color-muted"></span>
                    <span class="font-12 color-muted">Anbefalet: min. 800×952px</span>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="applyCrop()">Anvend</button>
            </div>
        </div>
    </div>
</div>

<!-- Logo Upload Modal -->
<div class="modal fade" id="logoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Tilføj logo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="upload-area border-dashed p-4 text-center cursor-pointer mb-3" onclick="document.getElementById('logoUpload').click()">
                    <i class="mdi mdi-cloud-upload font-32 color-gray"></i>
                    <p class="font-14 mb-0 color-gray">Klik for at uploade logo</p>
                    <p class="font-12 color-muted mb-0">JPG, PNG, SVG</p>
                </div>
                <input type="file" id="logoUpload" accept="image/*" style="display: none;" onchange="handleLogoUpload(this)">
            </div>
        </div>
    </div>
</div>

<!-- Export Options Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">Eksporter A-skilt</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="font-14 color-gray mb-3">Vælg eksportformat og kvalitet</p>
                <div class="mb-3">
                    <label class="form-label font-13">Format</label>
                    <select id="exportFormat" class="form-select-v2 w-100 h-45px">
                        <option value="pdf">PDF (Print kvalitet)</option>
                        <option value="png">PNG (Høj opløsning)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="doExport()">
                    <i class="mdi mdi-download me-1"></i>
                    Eksporter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preload Preview Modal -->
<div class="modal fade" id="preloadPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-radius-10px">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold" id="preloadPreviewTitle">Baggrund</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="preloadPreviewImage" src="" alt="" style="max-width: 100%; max-height: 60vh; object-fit: contain; border-radius: 8px;">
                <p id="preloadPreviewDescription" class="font-13 color-gray mt-3 mb-0"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn-v2 mute-btn" data-dismiss="modal">Annuller</button>
                <button type="button" class="btn-v2 action-btn" onclick="usePreloadAsBackground()">
                    <i class="mdi mdi-check me-1"></i>
                    Brug som baggrund
                </button>
            </div>
        </div>
    </div>
</div>
