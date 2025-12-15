<?php
/**
 * @var object $args
 * Customer Location Home Page
 * Public-facing location landing page displaying published content
 */

use classes\Methods;

$location = $args->location;
$page = $args->publishedPage;
$slug = $args->slug;

// Prepare URLs
$heroImageUrl = __url($page->hero_image);
$logoUrl = __url($page->logo);

// Handle address with inheritance
$address = Methods::locations()->locationAddress($location);
// Format address string using helper method (no country, comma-separated)
$addressString = Methods::misc()::extractCompanyAddressString($address, false, false);
$hasAddress = !isEmpty($addressString);
?>

<!-- Hero Section -->
<div class="position-relative" style="min-height: 300px; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('<?=$heroImageUrl?>'); background-size: cover; background-position: center;">
    <div class="container py-4">
        <!-- Logo and Location Info -->
        <div class="flex-row-start-center mb-4" style="gap: 1rem;">
            <img src="<?=$logoUrl?>" alt="<?=$location->name?>" style="width: 60px; height: 60px; object-fit: contain; border-radius: 8px; background: white; padding: 8px;">
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
            <p class="color-white font-weight-bold font-40 text-center">
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
            <?php if(!isEmpty($page->caption)): ?>
                <div class="card-border border-radius-10px p-4 mb-4">
                    <p class="mb-0 font-16 line-height-relaxed">
                        <?=nl2br(htmlspecialchars($page->caption))?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- About Us Section -->
            <?php if(!isEmpty($page->about_us)): ?>
                <div class="card-border border-radius-10px p-4 mb-4">
                    <h3 class="font-weight-bold mb-3">Om os</h3>
                    <p class="mb-0 font-16 line-height-relaxed">
                        <?=nl2br(htmlspecialchars($page->about_us))?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Dynamic Sections -->
            <?php if(!isEmpty($page->sections)): ?>
                <?php foreach($page->sections as $section): ?>
                    <?php if(!isEmpty($section->title) || !isEmpty($section->content)): ?>
                        <div class="card-border border-radius-10px p-4 mb-4">
                            <?php if(!isEmpty($section->title)): ?>
                                <p class="font-18 font-weight-bold mb-3"><?=htmlspecialchars($section->title)?></p>
                            <?php endif; ?>
                            <?php if(!isEmpty($section->content)): ?>
                                <p class="mb-0 font-16 line-height-relaxed">
                                    <?=nl2br(htmlspecialchars($section->content))?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- Right Column - Sidebar -->
        <div class="col-lg-4">

            <!-- Credit Widget (if enabled) -->
            <?php if(!isEmpty($page->credit_widget_enabled)): ?>
                <div class="card border-radius-10px mb-4">
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
            <?php endif; ?>

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
.bg-info-light {
    background-color: rgba(23, 162, 184, 0.1);
}
.opacity-90 {
    opacity: 0.9;
}
</style>
