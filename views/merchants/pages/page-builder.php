<?php
/**
 * @var object $args
 */

use classes\app\LocationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use features\Settings;


$location = $args->location;
$pageTitle = $location->name . " - Pagebuilder";
$pageDraft = $args->pageDraft;


?>




<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
    activePage = "locations";
    var worldCountries = <?=json_encode(toArray($args->worldCountries))?>;
    var isDefaultHeroImage = <?=json_encode(isEmpty($pageDraft->hero_image) || $pageDraft->hero_image === DEFAULT_LOCATION_HERO)?>;
    var isDefaultLocationLogo = <?=json_encode(isEmpty($pageDraft->logo) || $pageDraft->logo === DEFAULT_LOCATION_LOGO)?>;
    var currentPageId = <?=json_encode($pageDraft->uid)?>;
    var currentPageState = <?=json_encode($pageDraft->state)?>;
</script>


<div class="page-content home">

    <div class="flex-col-start" id="nav" style="gap: 0;">
        <div class="flex-row-start">
            <a class="cursor-pointer transition-color font-14 flex-row-start flex-align-center flex-nowrap color-gray hover-color-dark"
               style="gap: .5rem;" href="<?=__url(Links::$merchant->locations->setSingleLocation($location->slug))?>">
                <i class="mdi mdi-arrow-left"></i>
                <span>Tilbage</span>
            </a>
        </div>
        <div class="flex-row-between flex-align-start flex-nowrap">
            <?=\features\DomMethods::locationSelect($args->locationOptions, $location->slug);?>
            <div class="flex-row-end flex-align-center" style="gap: .5rem;">
                <!-- Page Version Selector -->
                <select class="form-select-v2" id="page-version-select" style="width: 220px;">
                    <?php foreach($args->pageOptions as $uid => $label): ?>
                        <option value="<?=$uid?>" <?=$uid === $pageDraft->uid ? 'selected' : ''?> data-href="<?=__url(Links::$merchant->locations->pageBuilder($location->slug) . '?ref=' . $uid)?>"><?=$label?></option>
                    <?php endforeach; ?>
                </select>

                <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
                    <?php if($pageDraft->state !== 'PUBLISHED'): ?>
                        <button id="publish-page-btn" class="btn-v2 green-btn text-nowrap" style="display: none;">
                            <i class="mdi mdi-publish"></i>
                            <span class="text-nowrap">Udgiv side</span>
                        </button>
                    <?php endif; ?>
                <?php LocationPermissions::__oEndContent(); ?>
            </div>
        </div>
    </div>


    <div class="flex-row-between-start flex-wrap" style="column-gap: .75rem; row-gap: .5rem;">
        <div class="flex-col-start">
            <div class="flex-row-start-center flex-nowrap cg-05">
                <p class="mb-0 font-30 font-weight-bold text-wrap">Rediger butiksside</p>
            </div>
            <p class="mb-0 font-16 font-weight-medium color-gray text-wrap mxw-400px">
                Tilpas din butiksside – denne side fokuserer på din butik. WeePay faciliterer kun.
            </p>
        </div>
        <div class="flex-row-end-center flex-nowrap cg-075 flex-nowrap">
            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewPage($args->slug, $pageDraft->uid))?>" target="_blank" class="btn-v2 mute-btn text-nowrap" id="preview-page-btn">
                <i class="mdi mdi-eye-outline"></i>
                <span class="text-nowrap">Forhåndsvis side</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <a href="<?=__url(Links::$merchant->locations->previewCheckout($args->slug, $pageDraft->uid))?>" target="_blank" class="btn-v2 mute-btn text-nowrap" id="preview-checkout-btn">
                <i class="mdi mdi-cart-outline"></i>
                <span class="text-nowrap">Forhåndsvis checkout</span>
            </a>
            <?php LocationPermissions::__oEndContent(); ?>

            <?php LocationPermissions::__oModifyProtectedContent($location,  'pages'); ?>
            <button name="save_page_changes" data-id="<?=$location->uid?>" onclick="" class="btn-v2 action-btn text-nowrap" >
                <i class="mdi mdi-content-save"></i>
                <span class="text-nowrap">Gem ændringer</span>
            </button>
            <?php LocationPermissions::__oEndContent(); ?>
        </div>
    </div>

    <!-- Page Builder Content -->
    <div class="row mt-4" style="row-gap: 1.5rem;">
        <!-- Left Panel: Editor -->
        <div class="col-12 col-lg-6">
            <div class="flex-col-start" style="row-gap: 2rem;">

                <!-- Logo Section -->
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Logo</h5>

                            <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                            <!-- Upload Area -->
                            <div id="logo-upload-area" class="border-dashed border-color-card flex-col-start flex-align-center flex-justify-center border-dashed-gray border-radius-05rem p-5 cursor-pointer hover-bg-light transition-all" style="min-height: 150px;">
                                <i class="mdi mdi-upload font-48 color-gray mb-2"></i>
                                <p class="mb-0 font-14 font-weight-medium">Upload logo</p>
                                <p class="mb-0 font-12 color-gray">PNG, JPG op til 5MB (500×500px anbefalet)</p>
                            </div>

                            <!-- Hidden File Input -->
                            <input type="file" id="logo-image-input" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                            <?php LocationPermissions::__oEndContent(); ?>

                            <!-- Preview Container -->
                            <div id="logo-preview-container" class="bg-light border-radius-10px w-100 p-2 <?=isEmpty($pageDraft->logo) ? 'd-none' : ''?>" style="min-height: 150px;">
                                <div class="w-100 h-100 position-relative flex-row-center flex-align-center">
                                    <img src="<?=resolveImportUrl($pageDraft->logo ?? DEFAULT_LOCATION_LOGO)?>" id="logo-preview-image"
                                         class="square-150 " />
                                    <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                                    <button id="logo-remove-button" type="button" class="btn-v2 danger-btn position-absolute" style="top: 10px; right: 10px;">
                                        <i class="mdi mdi-close-circle-outline"></i>
                                    </button>
                                    <?php LocationPermissions::__oEndContent(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Hero Image Section -->
                <div class="card border-radius-10px" data-location-id="<?=$location->uid?>">
                    <div class="card-body">
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Hero-billede</h5>

                            <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                            <!-- Upload Area -->
                            <div id="hero-upload-area" class="border-dashed border-color-card flex-col-start flex-align-center flex-justify-center border-dashed-gray border-radius-05rem p-5 cursor-pointer hover-bg-light transition-all" style="min-height: 180px;">
                                <i class="mdi mdi-upload font-48 color-gray mb-2"></i>
                                <p class="mb-0 font-14 font-weight-medium">Upload hero-billede</p>
                                <p class="mb-0 font-12 color-gray">PNG, JPG op til 10MB (1920×600px anbefalet)</p>
                            </div>

                            <!-- Hidden File Input -->
                            <input type="file" id="hero-image-input" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">
                            <?php LocationPermissions::__oEndContent(); ?>

                            <!-- Preview Container -->
                            <div id="hero-preview-container" class="bg-light border-radius-10px w-100 flex-col-start flex-align-center flex-justify-center p-2 <?=isEmpty($pageDraft->hero_image) ? 'd-none' : ''?>"
                                 style="aspect-ratio: 19/6">
                                <div class="w-100 h-100 position-relative">
                                    <div id="hero-preview-image"
                                         class="w-100 h-100 overflow-hidden bg-cover bg-center"
                                         style="
                                                border-radius: 10px;
                                                background-image: url('<?=resolveImportUrl($pageDraft->hero_image ?? DEFAULT_LOCATION_HERO)?>');
                                                background-size: cover;
                                                background-position: center;
                                         "></div>
                                    <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                                    <button id="hero-remove-button" type="button" class="btn-v2 danger-btn position-absolute" style="top: 10px; right: 10px;">
                                        <i class="mdi mdi-close-circle-outline"></i>
                                    </button>
                                    <?php LocationPermissions::__oEndContent(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1.5rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Indhold</h5>

                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <label class="font-13 font-weight-bold mb-0">Overskrift</label>
                                <input type="text" class="form-field-v2" name="page_title" placeholder="Velkommen til København Centrum" value="<?=htmlspecialchars($pageDraft->title ?? '')?>">
                            </div>

                            <div class="flex-col-start" style="row-gap: .5rem;">
                                <label class="font-13 font-weight-bold mb-0">Beskrivelse</label>
                                <textarea class="form-field-v2 mnh-100px" name="page_caption" placeholder="Vi tilbyder fleksible betalingsløsninger via WeePay - betal med det samme eller fordel betalingen over tid."><?=htmlspecialchars($pageDraft->caption ?? '')?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Sections -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1.5rem;">
                            <div class="flex-row-between flex-align-center">
                                <h5 class="mb-0 font-18 font-weight-bold">Tilpassede sektioner</h5>
                                <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                                <button id="add-section-btn" class="btn-v2 mute-btn flex-row-center-center flex-nowrap" style="gap: .25rem;">
                                    <i class="mdi mdi-plus font-16"></i>
                                    <span>Tilføj sektion</span>
                                </button>
                                <?php LocationPermissions::__oEndContent(); ?>
                            </div>

                            <!-- Om os Section (if exists) -->
                            <?php if(!isEmpty($pageDraft->about_us)): ?>
                            <div class="flex-col-start card-border border-radius-10px p-3" style="row-gap: 1rem;">
                                <div class="flex-row-between flex-align-center">
                                    <p class="mb-0 font-14 font-weight-medium">Om os</p>
                                    <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                                    <button class="btn-v2 mute-danger-outline-btn transition-all" data-delete-about-us>
                                        <i class="mdi mdi-delete-outline"></i>
                                    </button>
                                    <?php LocationPermissions::__oEndContent(); ?>
                                </div>
                                <textarea class="form-field-v2 mnh-80px" name="about_us" placeholder="Skriv om din butik..."><?=htmlspecialchars($pageDraft->about_us)?></textarea>
                            </div>
                            <?php endif; ?>

                            <!-- Dynamic Custom Sections -->
                            <div id="sections-container">
                                <?php
                                if(!isEmpty($pageDraft->sections)):
                                foreach($pageDraft->sections as $index => $section):
                                ?>
                                <div class="flex-col-start card-border border-radius-10px p-3 section-item" style="row-gap: 1rem;" data-section-index="<?=$index?>">
                                    <div class="flex-row-between flex-align-center">
                                        <input type="text" class="flex-1-current form-field-v2 mb-0 font-14 font-weight-medium " style="border-radius: 10px 0 0 10px;" name="section_title_<?=$index?>" value="<?=htmlspecialchars($section->title ?? '')?>" placeholder="Sektion titel">
                                        <?php LocationPermissions::__oModifyProtectedContent($location, 'pages'); ?>
                                        <button class="h-45px btn-v2 danger-btn transition-all section-delete-btn" style="border-radius: 0 10px 10px 0;">
                                            <i class="mdi mdi-delete-outline"></i>
                                        </button>
                                        <?php LocationPermissions::__oEndContent(); ?>
                                    </div>
                                    <textarea class="form-field-v2 mnh-80px" name="section_content_<?=$index?>" placeholder="Skriv indhold..."><?=htmlspecialchars($section->content ?? '')?></textarea>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credit Widget Section -->
                <div  class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start " style="row-gap: 1rem;">
                            <h5 class="mb-0 font-18 font-weight-bold">Kredit widget</h5>
                            <div class="flex-row-between flex-align-start" style="column-gap: 1rem;">
                                <div class="flex-col-start flex-1" style="row-gap: .25rem;">
                                    <p class="mb-0 font-14 font-weight-medium">Inkluder "Tjek din kredit" widget</p>
                                    <p class="mb-0 font-12 color-gray">Lad kunder tjekke deres tilgængelige kredit direkte på din side</p>
                                </div>
                                <label class="form-switch">
                                    <input type="checkbox" name="credit_widget_enabled" <?=$pageDraft->credit_widget_enabled ? 'checked' : ''?>>
                                    <i></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Panel: Live Preview -->
        <div class="col-12 col-lg-6">
            <div class="position-sticky" style="top: 20px;">
                <div class="card border-radius-10px">
                    <div class="card-body">
                        <div class="flex-col-start" style="row-gap: 1rem;">
                            <div class="flex-row-between-center">
                                <h5 class="mb-0 font-18 font-weight-bold">Live forhåndsvisning</h5>
                                <div class="flex-row-center-center" style="gap: 0.25rem;">
                                    <div class="square-8 border-radius-circle bg-mute"></div>
                                    <div class="square-8 border-radius-circle bg-mute"></div>
                                    <div class="square-8 border-radius-circle bg-mute"></div>
                                </div>
                            </div>

                            <!-- Mobile Preview Container -->
                            <div class="card-border border-radius-10px overflow-hidden" style="max-width: 100%; background: #f8f9fa;">
                                <div id="inline-preview-container" style="transform-origin: top center; overflow-y: auto; max-height: 700px; background: white;">

                                    <!-- Hero Section -->
                                    <div id="inline-preview-hero" style="min-height: 200px; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('<?=resolveImportUrl($pageDraft->hero_image ?? DEFAULT_LOCATION_HERO)?>'); background-size: cover; background-position: center; padding: 1.5rem;">
                                        <div class="flex-row-start-center mb-3" style="gap: 0.5rem;">
                                            <img id="inline-preview-logo" src="<?=resolveImportUrl($pageDraft->logo ?? DEFAULT_LOCATION_LOGO)?>" alt="<?=$location->name?>" style="width: 40px; height: 40px; object-fit: contain; border-radius: 6px; background: white; padding: 4px;">
                                            <div>
                                                <p class="mb-0 font-13 color-white font-weight-bold"><?=$location->name?></p>
                                            </div>
                                        </div>
                                        <div class="text-center py-3">
                                            <p id="inline-preview-title" class="color-white font-weight-bold mb-2 font-20">
                                                <?=$pageDraft->title?>
                                            </p>
                                            <button class="btn-v2 green-btn btn-sm" style="pointer-events: none;">
                                                <i class="mdi mdi-qrcode font-14"></i>
                                                <span class="font-12">Køb nu</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Content Area -->
                                    <div class="p-3">
                                        <!-- Caption -->
                                        <div id="inline-preview-caption-section" class="mb-3 p-3 border-radius-8px" style="<?=isEmpty($pageDraft->caption) ? 'display: none;' : ''?> background: #f8f9fa;">
                                            <p id="inline-preview-caption" class="mb-0 font-13 line-height-relaxed">
                                                <?=nl2br(htmlspecialchars($pageDraft->caption))?>
                                            </p>
                                        </div>

                                        <!-- About Us -->
                                        <div id="inline-preview-about-section" class="mb-3 p-3 border-radius-8px" style="<?=isEmpty($pageDraft->about_us) ? 'display: none;' : ''?> background: #f8f9fa;">
                                            <p class="font-14 font-weight-bold mb-2">Om os</p>
                                            <p id="inline-preview-about" class="mb-0 font-13 line-height-relaxed">
                                                <?=nl2br(htmlspecialchars($pageDraft->about_us))?>
                                            </p>
                                        </div>

                                        <!-- Dynamic Sections -->
                                        <div id="inline-preview-sections-container">
                                            <?php if(!isEmpty($pageDraft->sections)): ?>
                                                <?php foreach($pageDraft->sections as $index => $section): ?>
                                                    <?php if(!isEmpty($section->title) || !isEmpty($section->content)): ?>
                                                        <div class="mb-3 p-3 border-radius-8px inline-preview-section" data-section-index="<?=$index?>" style="background: #f8f9fa;">
                                                            <?php if(!isEmpty($section->title)): ?>
                                                                <p class="font-14 font-weight-bold mb-2 inline-preview-section-title"><?=htmlspecialchars($section->title)?></p>
                                                            <?php endif; ?>
                                                            <?php if(!isEmpty($section->content)): ?>
                                                                <p class="mb-0 font-13 line-height-relaxed inline-preview-section-content">
                                                                    <?=nl2br(htmlspecialchars($section->content))?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Credit Widget -->
                                        <div id="inline-preview-credit-widget" class="p-3 border-radius-8px" style="<?=isEmpty($pageDraft->credit_widget_enabled) ? 'display: none;' : ''?> background: rgba(40, 199, 111, 0.1);">
                                            <div class="flex-row-start-center mb-2" style="gap: 0.5rem;">
                                                <i class="mdi mdi-shield-outline font-16 color-design-blue"></i>
                                                <p class="font-13 font-weight-bold mb-0">Tjek din kredit</p>
                                            </div>
                                            <p class="mb-2 font-12 color-gray">
                                                Se hvor meget du kan handle for
                                            </p>
                                            <button class="btn-v2 green-btn btn-sm w-100 font-12" style="pointer-events: none;">
                                                Tjek min kredit nu
                                            </button>
                                        </div>

                                        <!-- Security Badges -->
                                        <div class="mt-3 p-3 border-radius-8px" style="background: #f8f9fa;">
                                            <p class="font-13 font-weight-bold mb-3">Sikker betaling</p>

                                            <div class="flex-row-start-center mb-2 pb-2" style="gap: 0.5rem;">
                                                <div class="square-30 border-radius-8px flex-row-center-center" style="background: rgba(40, 199, 111, 0.1);">
                                                    <i class="mdi mdi-shield-outline color-green font-16"></i>
                                                </div>
                                                <div class="flex-1-current">
                                                    <p class="mb-0 font-12 font-weight-medium">Beskyttet af VIVA</p>
                                                    <p class="mb-0 font-11 color-gray">Sikker betalingsgateway</p>
                                                </div>
                                            </div>

                                            <div class="flex-row-start-center mb-2 pb-2" style="gap: 0.5rem;">
                                                <div class="square-30 border-radius-8px flex-row-center-center" style="background: rgba(40, 199, 111, 0.1);">
                                                    <i class="mdi mdi-lock-outline color-green font-16"></i>
                                                </div>
                                                <div class="flex-1-current">
                                                    <p class="mb-0 font-12 font-weight-medium">GDPR-kompatibel</p>
                                                    <p class="mb-0 font-11 color-gray">Dine data er sikre</p>
                                                </div>
                                            </div>

                                            <div class="flex-row-start-center" style="gap: 0.5rem;">
                                                <div class="square-30 border-radius-8px flex-row-center-center" style="background: rgba(40, 199, 111, 0.1);">
                                                    <i class="mdi mdi-check-circle-outline color-green font-16"></i>
                                                </div>
                                                <div class="flex-1-current">
                                                    <p class="mb-0 font-12 font-weight-medium">MitID verificeret</p>
                                                    <p class="mb-0 font-11 color-gray">Sikker identifikation</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Info Message -->
                                    <div class="p-3 bg-info-light border-top">
                                        <div class="flex-row-start-center" style="gap: 0.5rem;">
                                            <i class="mdi mdi-information-outline color-info font-16"></i>
                                            <p class="mb-0 font-12 color-info">Live forhåndsvisning opdateres automatisk. Klik "Forhåndsvis side" for fuld størrelse.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>




