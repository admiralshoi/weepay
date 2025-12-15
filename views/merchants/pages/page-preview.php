<?php
/**
 * @var object $args
 * Location Page Preview
 * Live preview of draft page content
 */

use classes\Methods;

$location = $args->location;
$page = $args->pageDraft;
$slug = $args->slug;
$draftId = $args->draftId;

// Prepare URLs
$heroImageUrl = __url($page->hero_image);
$logoUrl = __url($page->logo);

// Handle address with inheritance
$address = $location->address ?? new StdClass();
if($location->inherit_details ?? false) {
    $orgAddress = $location->uuid->company_address ?? new StdClass();
    // Inherit missing fields from organisation
    if(isEmpty($address?->line_1) && !isEmpty($orgAddress?->line_1 ?? null)) $address->line_1 = $orgAddress?->line_1;
    if(isEmpty($address?->city) && !isEmpty($orgAddress?->city ?? null)) $address->city = $orgAddress?->city;
    if(isEmpty($address?->postal_code) && !isEmpty($orgAddress?->postal_code ?? null)) $address->postal_code = $orgAddress?->postal_code;
    if(isEmpty($address?->country) && !isEmpty($orgAddress?->country ?? null)) $address->country = $orgAddress?->country;
}

// Format address string using helper method (no country, comma-separated)
$addressString = Methods::misc()::extractCompanyAddressString($address, false, false);
$hasAddress = !isEmpty($addressString);
?>

<!-- Preview Header -->
<div class="bg-warning text-center py-2 sticky-top" style="z-index: 1000;">
    <p class="mb-0 font-14 font-weight-medium">
        <i class="mdi mdi-eye-outline mr-1"></i>
        Forhåndsvisning - Denne side opdateres live mens du redigerer
    </p>
</div>

<!-- Hero Section -->
<div class="position-relative" id="preview-hero" style="min-height: 300px; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('<?=$heroImageUrl?>'); background-size: cover; background-position: center;">
    <div class="container py-4">
        <!-- Logo and Location Info -->
        <div class="flex-row-start-center mb-4" style="gap: 1rem;">
            <img id="preview-logo" src="<?=$logoUrl?>" alt="<?=$location->name?>" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: white; padding: 8px;">
            <div>
                <h5 class="mb-0 color-white font-weight-bold"><?=$location->name?></h5>
                <?php if($hasAddress): ?>
                    <p class="mb-0 font-13 color-white opacity-90">
                        <?=$addressString?>
                    </p>
                <?php endif; ?>
            </div>
        </div>


        <!-- Hero Title -->
        <div class="flex-col-start flex-align-center rg-1 py-5">
            <p id="preview-title" class="color-white font-weight-bold font-40 text-center">
                <?=$page->title?>
            </p>
            <button class="btn-v2 green-btn flex-row-center-start flex-nowrap cg-075 font-20" >
                <i class="mdi mdi-qrcode"></i>
                Scan QR-koden &bullet; Køb nu &bullet; Betal senere
            </button>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="container my-5">
    <div class="row">
        <!-- Left Column - Main Content -->
        <div class="col-lg-8">

            <!-- Caption Section -->
            <div id="preview-caption-section" class="card-border border-radius-10px p-4 mb-4" style="<?=isEmpty($page->caption) ? 'display: none;' : ''?>">
                <p id="preview-caption" class="mb-0 font-16 line-height-relaxed">
                    <?=nl2br(htmlspecialchars($page->caption))?>
                </p>
            </div>

            <!-- About Us Section -->
            <div id="preview-about-section" class="card-border border-radius-10px p-4 mb-4" style="<?=isEmpty($page->about_us) ? 'display: none;' : ''?>">
                <h3 class="font-weight-bold mb-3">Om os</h3>
                <p id="preview-about" class="mb-0 font-16 line-height-relaxed">
                    <?=nl2br(htmlspecialchars($page->about_us))?>
                </p>
            </div>

            <!-- Dynamic Sections -->
            <div id="preview-sections-container">
                <?php if(!isEmpty($page->sections)): ?>
                    <?php foreach($page->sections as $index => $section): ?>
                        <?php if(!isEmpty($section->title) || !isEmpty($section->content)): ?>
                            <div class="card-border border-radius-10px p-4 mb-4 preview-section" data-section-index="<?=$index?>">
                                <?php if(!isEmpty($section->title)): ?>
                                    <p class="font-18 font-weight-bold mb-3 preview-section-title"><?=htmlspecialchars($section->title)?></p>
                                <?php endif; ?>
                                <?php if(!isEmpty($section->content)): ?>
                                    <p class="mb-0 font-16 line-height-relaxed preview-section-content">
                                        <?=nl2br(htmlspecialchars($section->content))?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">

            <!-- Credit Widget (if enabled) -->
            <div id="preview-credit-widget" class="card border-radius-10px mb-4" style="<?=isEmpty($page->credit_widget_enabled) ? 'display: none;' : ''?>">
                <div class="card-body">

                    <div class="flex-row-start-center mb-3" style="gap: 0.5rem;">
                        <i class="mdi mdi-shield-outline font-18 color-design-blue"></i>
                        <p class="font-weight-bold font-18">Sikker betaling</p>
                    </div>
                    <p class="mb-3 font-14 color-gray">
                        Se hvor meget du kan handle for hos <?=$location->name?>
                    </p>
                    <button class="btn-v2 green-btn w-100" onclick="alert('Credit check functionality coming soon')">
                        Tjek min kredit nu
                    </button>

                    <!-- Trust Badges -->
                    <div class="flex-row-start-center mt-3 pt-3 border-top" style="gap: 1rem;">
                        <p class="font-14">Se hvor meget du kan bruge for hos <?=$location->name?></p>
                    </div>
                </div>
            </div>

            <!-- Secure Payment Section -->
            <div class="card border-radius-10px">
                <div class="card-body">
                    <p class="font-weight-bold mb-4 font-18">Sikker betaling</p>

                    <!-- Security Item 1: VIVA Protected -->
                    <div class="flex-row-start-center mb-3 pb-3" style="gap: 1rem;">
                        <div class="square-40 border-radius-10px bg-success-light flex-row-center-center">
                            <i class="mdi mdi-shield-outline color-green font-20"></i>
                        </div>
                        <div class="flex-1-current">
                            <p class="mb-0 font-15 font-weight-medium">Beskyttet af VIVA</p>
                            <p class="mb-0 font-13 color-gray">Sikker betalingsgateway</p>
                        </div>
                    </div>

                    <!-- Security Item 2: GDPR Compliant -->
                    <div class="flex-row-start-center mb-3 pb-3" style="gap: 1rem;">
                        <div class="square-40 border-radius-10px bg-success-light flex-row-center-center">
                            <i class="mdi mdi-lock-outline color-green font-20"></i>
                        </div>
                        <div class="flex-1-current">
                            <p class="mb-0 font-15 font-weight-medium">GDPR-kompatibel</p>
                            <p class="mb-0 font-13 color-gray">Dine data er sikre</p>
                        </div>
                    </div>

                    <!-- Security Item 3: MitID Verified -->
                    <div class="flex-row-start-center" style="gap: 1rem;">
                        <div class="square-40 border-radius-10px bg-success-light flex-row-center-center">
                            <i class="mdi mdi-check-circle-outline color-green font-20"></i>
                        </div>
                        <div class="flex-1-current">
                            <p class="mb-0 font-15 font-weight-medium">MitID verificeret</p>
                            <p class="mb-0 font-13 color-gray">Sikker identifikation</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.line-height-relaxed {
    line-height: 1.7;
}
.bg-success-light {
    background-color: rgba(40, 199, 111, 0.1);
}
.opacity-90 {
    opacity: 0.9;
}
.sticky-top {
    position: sticky;
    top: 0;
}
</style>

<script>
    var draftId = <?=json_encode($draftId)?>;
    var locationSlug = <?=json_encode($slug)?>;
</script>
