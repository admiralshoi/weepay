<?php
/**
 * Merchant Onboarding Guide
 * Comprehensive step-by-step guide for merchants to get started with WeePay
 */

use classes\enumerations\Links;
use classes\lang\Translate;

$pageTitle = "Kom godt i gang som forhandler";
$screenshotPath = __asset("media/guides/merchant/onboarding/");
$orgWord = Translate::word("Organisation", "DA");
$orgWordLower = strtolower($orgWord);
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<style>
    .guide-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .guide-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .guide-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #1a1a2e;
    }
    .guide-header .subtitle {
        font-size: 1.1rem;
        color: #6b7280;
        max-width: 600px;
        margin: 0 auto;
    }
    .guide-toc {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 3rem;
    }
    .guide-toc h3 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #374151;
    }
    .guide-toc ol {
        margin: 0;
        padding-left: 1.25rem;
    }
    .guide-toc li {
        margin-bottom: 0.5rem;
    }
    .guide-toc a {
        color: var(--action-color, #3b82f6);
        text-decoration: none;
    }
    .guide-toc a:hover {
        text-decoration: underline;
    }
    .guide-toc-section {
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }
    .guide-toc-section-title {
        font-size: 0.8rem;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }
    .guide-prereq {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin-bottom: 3rem;
        border-left: 4px solid #f59e0b;
    }
    .guide-prereq h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .guide-prereq ul {
        margin: 0;
        padding-left: 1.25rem;
        color: #78350f;
    }
    .guide-prereq li {
        margin-bottom: 0.5rem;
    }
    .guide-step {
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e5e7eb;
    }
    .guide-step:last-of-type {
        border-bottom: none;
    }
    .step-header {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .step-number {
        width: 40px;
        height: 40px;
        background: var(--action-color, #3b82f6);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .step-number.critical {
        background: #dc2626;
    }
    .step-number.optional {
        background: #9ca3af;
    }
    .step-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1a1a2e;
        margin: 0;
    }
    .step-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-left: 0.75rem;
        vertical-align: middle;
    }
    .step-badge.critical {
        background: #fee2e2;
        color: #dc2626;
    }
    .step-badge.optional {
        background: #f3f4f6;
        color: #6b7280;
    }
    .step-content {
        margin-left: 56px;
    }
    .step-content p {
        font-size: 1rem;
        line-height: 1.7;
        color: #4b5563;
        margin-bottom: 1rem;
    }
    .step-content ul, .step-content ol {
        margin-bottom: 1rem;
        padding-left: 1.25rem;
        color: #4b5563;
    }
    .step-content li {
        margin-bottom: 0.5rem;
        line-height: 1.6;
    }
    .guide-screenshot {
        margin: 1.5rem 0;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    .guide-screenshot img {
        width: 100%;
        height: auto;
        display: block;
    }
    .guide-screenshot .placeholder {
        padding: 3rem 2rem;
        text-align: center;
        color: #9ca3af;
    }
    .guide-screenshot .placeholder i {
        font-size: 3rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .screenshot-caption {
        padding: 0.75rem 1rem;
        background: #f3f4f6;
        font-size: 0.875rem;
        color: #6b7280;
        border-top: 1px solid #e5e7eb;
    }
    .guide-tip {
        background: #eff6ff;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        border-left: 4px solid #3b82f6;
    }
    .guide-tip p {
        margin: 0;
        color: #1e40af;
    }
    .guide-tip strong {
        color: #1e3a8a;
    }
    .guide-warning {
        background: #fef2f2;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin: 1rem 0;
        border-left: 4px solid #dc2626;
    }
    .guide-warning p {
        margin: 0;
        color: #991b1b;
    }
    .guide-warning strong {
        color: #7f1d1d;
    }
    .guide-checklist {
        background: #f0fdf4;
        border-radius: 12px;
        padding: 1.5rem 2rem;
        margin: 2rem 0;
    }
    .guide-checklist h3 {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #166534;
    }
    .guide-checklist ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .guide-checklist li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        color: #166534;
    }
    .guide-checklist li i {
        font-size: 1.25rem;
    }
    .guide-checklist li.optional {
        color: #6b7280;
    }
    .guide-section-divider {
        text-align: center;
        margin: 3rem 0;
        position: relative;
    }
    .guide-section-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e5e7eb;
    }
    .guide-section-divider span {
        background: white;
        padding: 0 1.5rem;
        position: relative;
        color: #9ca3af;
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .guide-cta {
        text-align: center;
        padding: 3rem 2rem;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 16px;
        margin-top: 3rem;
    }
    .guide-cta h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
        color: #1e3a8a;
    }
    .guide-cta p {
        color: #3730a3;
        margin-bottom: 1.5rem;
    }
    .guide-cta-buttons {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    @media (max-width: 640px) {
        .guide-container {
            padding: 1rem;
        }
        .guide-header h1 {
            font-size: 1.75rem;
        }
        .step-content {
            margin-left: 0;
            margin-top: 1rem;
        }
        .step-header {
            flex-direction: column;
        }
    }
    .guide-video-tabs {
        margin: 1.5rem 0;
    }
    .video-tab-buttons {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .video-tab-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        color: #4b5563;
        transition: all 0.2s;
    }
    .video-tab-btn:hover {
        background: #f3f4f6;
    }
    .video-tab-btn.active {
        background: var(--action-color, #3b82f6);
        color: white;
        border-color: var(--action-color, #3b82f6);
    }
    .video-tab-content {
        display: none;
    }
    .video-tab-content.active {
        display: block;
    }
</style>

<div class="page-content">
    <div class="guide-container">

        <!-- Header -->
        <div class="guide-header">
            <h1>Kom godt i gang som forhandler</h1>
            <p class="subtitle">Denne guide hjælper dig med at opsætte din WeePay-konto, så du kan begynde at modtage betalinger fra dine kunder på under 10 minutter.</p>
        </div>

        <!-- Table of Contents -->
        <div class="guide-toc">
            <h3>Indhold</h3>
            <ol>
                <li><a href="#step-1">Opret din <?=$orgWordLower?></a></li>
                <li><a href="#step-2">Forbind Viva Wallet</a> <span class="step-badge critical">Vigtigt</span></li>
                <li><a href="#step-3">Aktivér gentagende betalinger</a> <span class="step-badge critical">Vigtigt</span></li>
                <li><a href="#step-4">Opret din første butik</a></li>
                <li><a href="#step-5">Opret en terminal</a></li>
                <li><a href="#step-6">Opsæt din butikside</a></li>
                <li><a href="#step-7">Begynd at modtage betalinger</a></li>
            </ol>
            <div class="guide-toc-section">
                <div class="guide-toc-section-title">Yderligere opsætning</div>
                <ol start="8">
                    <li><a href="#step-8">Markedsføringsmaterialer</a> <span class="step-badge optional">Valgfrit</span></li>
                    <li><a href="#step-9">Tilføj teammedlemmer</a> <span class="step-badge optional">Valgfrit</span></li>
                </ol>
            </div>
        </div>

        <!-- Prerequisites -->
        <div class="guide-prereq">
            <h3><i class="mdi mdi-clipboard-check-outline"></i> Før du starter</h3>
            <p>Sørg for at have følgende klar:</p>
            <ul>
                <li><strong>CVR-nummer</strong> - Din <?=$orgWordLower?>s registreringsnummer</li>
                <li><strong><?=$orgWord?>sadresse</strong> - Officiel adresse på din <?=$orgWordLower?></li>
                <li><strong>Telefonnummer</strong> - Til verificering hos Viva Wallet</li>
                <li><strong>Gyldigt ID</strong> - Pas eller kørekort til identitetsbekræftelse</li>
                <li><strong>Bankkonto</strong> - Til udbetaling af dine indtægter</li>
            </ul>
        </div>

        <!-- Step 1: Create Organisation -->
        <div class="guide-step" id="step-1">
            <div class="step-header">
                <div class="step-number">1</div>
                <h2 class="step-title">Opret din <?=$orgWordLower?></h2>
            </div>
            <div class="step-content">
                <p>Det første skridt er at oprette din <?=$orgWordLower?> i WeePay. En <?=$orgWordLower?> repræsenterer din virksomhed og kan indeholde flere butikker (lokationer).</p>

                <ol>
                    <li>Log ind på din WeePay-konto eller <a href="<?=__url(Links::$app->auth->merchantLogin)?>">opret en ny konto</a></li>
                    <li>Klik på <strong>"Opret <?=$orgWordLower?>"</strong></li>
                    <li>Udfyld formularen med dine virksomhedsoplysninger:
                        <ul>
                            <li><?=$orgWord?>snavn (det navn dine kunder vil se)</li>
                            <li>Officielt firmanavn</li>
                            <li>CVR-nummer</li>
                            <li>Adresse</li>
                            <li>Kontakt-email</li>
                        </ul>
                    </li>
                    <li>Klik <strong>"Opret"</strong></li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-org-create-form.png" alt="<?=$orgWord?> oprettelsesformular" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-org-create-form.png</span></div>'">
                    <div class="screenshot-caption">Formularen til oprettelse af <?=$orgWordLower?></div>
                </div>

                <div class="guide-tip">
                    <p><strong>Tip:</strong> Brug et <?=$orgWordLower?>snavn, som dine kunder nemt kan genkende - det vises på deres betalingsoversigt.</p>
                </div>
            </div>
        </div>

        <!-- Step 2: Connect Viva Wallet -->
        <div class="guide-step" id="step-2">
            <div class="step-header">
                <div class="step-number critical">2</div>
                <h2 class="step-title">Forbind Viva Wallet <span class="step-badge critical">Vigtigt</span></h2>
            </div>
            <div class="step-content">
                <p>Viva Wallet er vores betalingspartner, som håndterer alle korttransaktioner og udbetalinger. Dette trin er <strong>obligatorisk</strong> før du kan modtage betalinger.</p>

                <div class="guide-warning">
                    <p><strong>Vigtigt:</strong> Du kan ikke modtage betalinger før din Viva Wallet-konto er verificeret. Verificeringen tager typisk samme dag, men kan i nogle tilfælde tage op til 48 timer.</p>
                </div>

                <ol>
                    <li>Gå til din <strong><?=$orgWord?>sside</strong></li>
                    <li>Find kortet <strong>"Viva Wallet"</strong> og klik på <strong>"Opsæt min wallet"</strong></li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-viva-setup-button.png" alt="Viva Wallet opsætningsknap" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-viva-setup-button.png</span></div>'">
                    <div class="screenshot-caption">Klik på "Opsæt min wallet" for at starte</div>
                </div>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Se hvordan det fungerer</h4>
                <p>Her kan du se hele processen afhængigt af, om du allerede har en Viva Wallet-konto eller ej:</p>

                <div class="guide-video-tabs">
                    <div class="video-tab-buttons">
                        <button class="video-tab-btn active" data-tab="new-merchant">Ny hos Viva</button>
                        <button class="video-tab-btn" data-tab="existing-merchant">Har allerede Viva-konto</button>
                    </div>
                    <div class="video-tab-content active" id="new-merchant">
                        <div class="guide-screenshot">
                            <video loop muted playsinline controls preload="auto" style="width: 100%; display: block;">
                                <source src="<?=$screenshotPath?>isv_flow_new_vendor.mp4" type="video/mp4">
                            </video>
                            <div class="screenshot-caption">Oprettelse af ny Viva Wallet-konto gennem WeePay</div>
                        </div>
                    </div>
                    <div class="video-tab-content" id="existing-merchant">
                        <div class="guide-screenshot">
                            <video loop muted playsinline controls preload="auto" style="width: 100%; display: block;">
                                <source src="<?=$screenshotPath?>isv_flow_existing_vendor.mp4" type="video/mp4">
                            </video>
                            <div class="screenshot-caption">Tilknytning af eksisterende Viva Wallet-konto</div>
                        </div>
                    </div>
                </div>

                <ol start="3">
                    <li>Du bliver nu sendt videre til Viva Wallet's onboarding-proces</li>
                    <li>Udfyld alle påkrævede oplysninger:
                        <ul>
                            <li><?=$orgWord?>soplysninger (CVR, adresse, etc.)</li>
                            <li>Personlig verificering (upload af ID)</li>
                            <li>Bankkontooplysninger til udbetalinger</li>
                        </ul>
                    </li>
                    <li>Vent på verificering fra Viva Wallet</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-viva-onboarding.png" alt="Viva Wallet onboarding" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-viva-onboarding.png</span></div>'">
                    <div class="screenshot-caption">Viva Wallet's onboarding-proces</div>
                </div>

                <p>Når din konto er verificeret, vil du se en grøn bekræftelse på din <?=$orgWordLower?>sside:</p>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-viva-connected.png" alt="Viva Wallet forbundet" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-viva-connected.png</span></div>'">
                    <div class="screenshot-caption">Bekræftelse på at Viva Wallet er forbundet</div>
                </div>
            </div>
        </div>

        <!-- Step 3: Enable Recurring Payments -->
        <div class="guide-step" id="step-3">
            <div class="step-header">
                <div class="step-number critical">3</div>
                <h2 class="step-title">Aktivér gentagende betalinger <span class="step-badge critical">Vigtigt</span></h2>
            </div>
            <div class="step-content">
                <p>For at dine kunder kan benytte WeePay's fleksible betalingsmuligheder (delbetaling, betal d. 1. i måneden, etc.), skal du aktivere gentagende betalinger i din Viva Wallet-konto.</p>

                <div class="guide-warning">
                    <p><strong>Vigtigt:</strong> Uden dette trin vil dine kunder kun kunne betale med det samme. Delbetaling og "betal senere" funktionerne kræver denne indstilling.</p>
                </div>

                <ol>
                    <li>Log ind på dit <a href="https://demo.vivapayments.com" target="_blank">Viva Wallet dashboard</a></li>
                    <li>Gå til <strong>Settings</strong> (Indstillinger)</li>
                    <li>Vælg <strong>API Access</strong></li>
                    <li>Find indstillingen for <strong>"Recurring Payments"</strong> eller <strong>"Card-on-file"</strong></li>
                    <li>Aktivér denne funktion</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-viva-api-settings.png" alt="Viva API indstillinger" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-viva-api-settings.png</span></div>'">
                    <div class="screenshot-caption">API-indstillinger i Viva Wallet dashboard</div>
                </div>

                <div class="guide-tip">
                    <p><strong>Brug for hjælp?</strong> Se <a href="https://developer.viva.com/isv-partner-program/#onboarding-flows" target="_blank">Viva's dokumentation</a> for detaljerede instruktioner, eller kontakt vores support.</p>
                </div>
            </div>
        </div>

        <!-- Step 4: Create Location -->
        <div class="guide-step" id="step-4">
            <div class="step-header">
                <div class="step-number">4</div>
                <h2 class="step-title">Opret din første butik</h2>
            </div>
            <div class="step-content">
                <p>En butik (lokation) repræsenterer et fysisk eller virtuelt salgssted. Du kan have flere butikker under samme <?=$orgWordLower?>.</p>

                <ol>
                    <li>Gå til <strong>Butikker</strong> i menuen</li>
                    <li>Klik på <strong>"Tilføj ny butik"</strong></li>
                    <li>Udfyld oplysningerne:
                        <ul>
                            <li>Butiksnavn</li>
                            <li>URL-slug (bruges i betalingslinks)</li>
                            <li>Kort beskrivelse</li>
                            <li>Adresse (du kan vælge at arve fra <?=$orgWordLower?>en)</li>
                        </ul>
                    </li>
                    <li>Klik <strong>"Opret"</strong></li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-location-create.png" alt="Opret butik" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-location-create.png</span></div>'">
                    <div class="screenshot-caption">Formularen til oprettelse af butik</div>
                </div>

                <div class="guide-tip">
                    <p><strong>Tip:</strong> Hvis du kun har én fysisk butik, kan du navngive den det samme som din <?=$orgWordLower?>. Du kan altid tilføje flere butikker senere.</p>
                </div>
            </div>
        </div>

        <!-- Step 5: Create Terminal -->
        <div class="guide-step" id="step-5">
            <div class="step-header">
                <div class="step-number">5</div>
                <h2 class="step-title">Opret en terminal</h2>
            </div>
            <div class="step-content">
                <p>En terminal er det punkt, hvor dine kunder betaler - typisk via QR-kode eller et betalingslink. Hver terminal er tilknyttet en butik.</p>

                <ol>
                    <li>Gå til <strong>Terminaler</strong> i menuen</li>
                    <li>Klik på <strong>"Tilføj ny terminal"</strong></li>
                    <li>Vælg hvilken butik terminalen skal tilknyttes</li>
                    <li>Giv terminalen et navn (f.eks. "Kasse 1" eller "Hovedterminal")</li>
                    <li>Sæt status til <strong>"Aktiv"</strong></li>
                    <li>Klik <strong>"Opret"</strong></li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-terminal-create.png" alt="Opret terminal" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-terminal-create.png</span></div>'">
                    <div class="screenshot-caption">Formularen til oprettelse af terminal</div>
                </div>
            </div>
        </div>

        <!-- Step 6: Create Store Page (Required) -->
        <div class="guide-step" id="step-6">
            <div class="step-header">
                <div class="step-number">6</div>
                <h2 class="step-title">Opsæt din butikside</h2>
            </div>
            <div class="step-content">
                <p>Din butikside er den offentlige side, som dine kunder ser. Den er vigtig af flere grunde:</p>

                <ul>
                    <li><strong>Markedsføring:</strong> Alle QR-koder på plakater og materialer linker til denne side</li>
                    <li><strong>Fallback:</strong> Hvis en kunde afbryder betalingen, sendes de hertil</li>
                    <li><strong>Kreditcheck:</strong> Kunder kan se, om de har kredit tilgængelig hos netop <em>din</em> butik (da hver <?=$orgWordLower?> kan have forskellige kreditgrænser)</li>
                    <li><strong>Troværdighed:</strong> En professionel side med logo og beskrivelse skaber tillid</li>
                </ul>

                <ol>
                    <li>Gå til din butik og klik på <strong>"Rediger Side"</strong></li>
                    <li>Tilpas siden med:
                        <ul>
                            <li>Dit logo</li>
                            <li>Coverbillede</li>
                            <li>Beskrivelse af din butik</li>
                            <li>Eventuelle tilbud eller kampagner</li>
                        </ul>
                    </li>
                    <li>Klik <strong>"Publicer"</strong> når du er tilfreds</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-pagebuilder.png" alt="Rediger Side" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-pagebuilder.png</span></div>'">
                    <div class="screenshot-caption">Pagebuilderen til tilpasning af din butikside</div>
                </div>

                <div class="guide-tip">
                    <p><strong>Tip:</strong> Din butikside er det første indtryk mange kunder får. Tag dig tid til at uploade et godt logo og skriv en indbydende beskrivelse.</p>
                </div>
            </div>
        </div>

        <!-- Step 7: Start Accepting Payments -->
        <div class="guide-step" id="step-7">
            <div class="step-header">
                <div class="step-number">7</div>
                <h2 class="step-title">Begynd at modtage betalinger</h2>
            </div>
            <div class="step-content">
                <p>Tillykke! Du er nu klar til at modtage betalinger. Her er hvordan dine kunder kan betale:</p>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">QR-kode</h4>
                <ol>
                    <li>Gå til <strong>Terminaler</strong></li>
                    <li>Klik på <strong>"Vis QR"</strong> ud for din terminal</li>
                    <li>Print eller vis QR-koden til dine kunder</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-terminal-qr.png" alt="Terminal QR-kode" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-terminal-qr.png</span></div>'">
                    <div class="screenshot-caption">QR-koden til din terminal</div>
                </div>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Betalingslink</h4>
                <p>Du kan også kopiere betalingslinket og sende det direkte til kunder via SMS, email eller messenger.</p>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Test din opsætning</h4>
                <p>Vi anbefaler at lave en testbetaling for at sikre, at alt virker korrekt:</p>
                <ol>
                    <li>Scan din egen QR-kode</li>
                    <li>Gennemfør en lille betaling</li>
                    <li>Bekræft at ordren vises i dit dashboard</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-first-payment.png" alt="Første betaling" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-first-payment.png</span></div>'">
                    <div class="screenshot-caption">Din første gennemførte betaling</div>
                </div>
            </div>
        </div>

        <!-- Checklist Summary -->
        <div class="guide-checklist">
            <h3><i class="mdi mdi-check-circle"></i> Tjekliste - Grundlæggende opsætning</h3>
            <ul>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> <?=$orgWord?> oprettet</li>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> Viva Wallet forbundet og verificeret</li>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> Gentagende betalinger aktiveret i Viva</li>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> Butik oprettet</li>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> Terminal oprettet og aktiv</li>
                <li><i class="mdi mdi-checkbox-marked-circle color-green"></i> Butikside opsat og publiceret</li>
            </ul>
        </div>

        <!-- Section Divider -->
        <div class="guide-section-divider">
            <span>Yderligere opsætning</span>
        </div>

        <!-- Step 8: Marketing Materials -->
        <div class="guide-step" id="step-8">
            <div class="step-header">
                <div class="step-number optional">8</div>
                <h2 class="step-title">Markedsføringsmaterialer <span class="step-badge optional">Valgfrit</span></h2>
            </div>
            <div class="step-content">
                <p>WeePay giver dig adgang til færdige markedsføringsmaterialer, så du ikke selv skal designe plakater og skilte. Alt indeholder automatisk din butiks QR-kode.</p>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Tilgængelige materialer</h4>
                <ul>
                    <li><strong>Bordopstillere:</strong> Perfekt til kassen eller bordet</li>
                    <li><strong>Plakater:</strong> I forskellige størrelser (A3, A4, A5)</li>
                    <li><strong>Vindueskilte:</strong> Til butiksvinduer</li>
                    <li><strong>Digitale bannere:</strong> Til sociale medier og hjemmeside</li>
                </ul>

                <ol>
                    <li>Gå til <strong>Markedsføring</strong> i menuen</li>
                    <li>Vælg den skabelon, du ønsker</li>
                    <li>Vælg butik og størrelse</li>
                    <li>Download som PDF eller billede</li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-marketing-materials.png" alt="Markedsføringsmaterialer" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-marketing-materials.png</span></div>'">
                    <div class="screenshot-caption">Vælg mellem forskellige markedsføringsmaterialer</div>
                </div>

                <div class="guide-tip">
                    <p><strong>Tip:</strong> Print materialerne i høj kvalitet og placer dem synligt i butikken. Jo lettere kunderne kan finde QR-koden, jo flere vil bruge WeePay.</p>
                </div>
            </div>
        </div>

        <!-- Step 9: Team Management -->
        <div class="guide-step" id="step-9">
            <div class="step-header">
                <div class="step-number optional">9</div>
                <h2 class="step-title">Tilføj teammedlemmer <span class="step-badge optional">Valgfrit</span></h2>
            </div>
            <div class="step-content">
                <p>Hvis du har medarbejdere eller samarbejdspartnere, der skal have adgang til WeePay, kan du invitere dem til din <?=$orgWordLower?> eller direkte til specifikke butikker.</p>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Rollebaseret adgang</h4>
                <p>WeePay understøtter et fleksibelt rollesystem, hvor du kan styre præcis, hvad hver bruger har adgang til:</p>

                <ul>
                    <li><strong><?=$orgWord?>sniveau:</strong> Inviter brugere til hele <?=$orgWordLower?>en med adgang på tværs af alle butikker</li>
                    <li><strong>Butiksniveau:</strong> Inviter brugere til specifikke butikker, hvis de kun skal arbejde ét sted</li>
                    <li><strong>Tilpassede roller:</strong> Opret roller med præcis de tilladelser, der er nødvendige (f.eks. "Kasserer" som kun kan se ordrer, eller "Butikschef" med fuld adgang til én butik)</li>
                </ul>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Sådan inviterer du et teammedlem</h4>
                <ol>
                    <li>Gå til <strong>Medlemmer</strong> i menuen (for <?=$orgWordLower?>sniveau) eller til butikkens <strong>Team</strong>-sektion</li>
                    <li>Klik på <strong>"Inviter medlem"</strong></li>
                    <li>Indtast personens email-adresse</li>
                    <li>Vælg hvilken rolle de skal have</li>
                    <li>Klik <strong>"Send invitation"</strong></li>
                </ol>

                <div class="guide-screenshot">
                    <img src="<?=$screenshotPath?>guide-team-invite.png" alt="Inviter teammedlem" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\'placeholder\'><i class=\'mdi mdi-image-outline\'></i><span>guide-team-invite.png</span></div>'">
                    <div class="screenshot-caption">Inviter teammedlemmer med forskellige roller</div>
                </div>

                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">Eksempler på roller</h4>
                <ul>
                    <li><strong>Ejer:</strong> Fuld adgang til alt</li>
                    <li><strong>Administrator:</strong> Kan administrere butikker, terminaler og team</li>
                    <li><strong>Butikschef:</strong> Fuld adgang til én eller flere specifikke butikker</li>
                    <li><strong>Kasserer:</strong> Kan kun se og håndtere ordrer</li>
                    <li><strong>Revisor:</strong> Kun læseadgang til rapporter og betalinger</li>
                </ul>

                <div class="guide-tip">
                    <p><strong>Tip:</strong> Start med de foruddefinerede roller og tilpas dem efter behov. Du kan altid ændre en brugers rolle eller fjerne adgang senere.</p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="guide-cta">
            <h3>Har du brug for hjælp?</h3>
            <p>Vores supportteam er klar til at hjælpe dig</p>
            <div class="guide-cta-buttons">
                <a href="<?=__url(Links::$faq->merchant)?>" class="btn-v2 trans-hover-design-action-btn card-border px-4 py-2 border-radius-10px font-weight-medium">
                    Se FAQ
                </a>
                <a href="<?=__url('support')?>" class="btn-v2 action-btn px-4 py-2 border-radius-10px font-weight-bold">
                    Kontakt support
                </a>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.video-tab-btn');
    const tabContents = document.querySelectorAll('.video-tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Remove active from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');

            // Play the video in the active tab
            const activeVideo = document.getElementById(tabId).querySelector('video');
            if (activeVideo) {
                activeVideo.play();
            }
        });
    });
});
</script>
