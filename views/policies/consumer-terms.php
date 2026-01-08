<?php
/**
 * @var object $args
 */

$pageTitle = "Handelsbetingelser";
?>

<script>
    var pageTitle = <?=json_encode($pageTitle)?>;
</script>

<style>
    .policy-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }
    .policy-container h1 {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #1a1a1a;
    }
    .policy-container h2 {
        font-size: 1.75rem;
        font-weight: bold;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        color: #2c3e50;
    }
    .policy-container h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        color: #34495e;
    }
    .policy-container p {
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 1rem;
        color: #4a4a4a;
    }
    .policy-container ul, .policy-container ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }
    .policy-container li {
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 0.5rem;
        color: #4a4a4a;
    }
    .policy-container hr {
        border: none;
        border-top: 2px solid #e9ecef;
        margin: 2rem 0;
    }
    .policy-meta {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 2rem;
    }
    .policy-meta strong {
        font-weight: 600;
    }
</style>



<div class="page-content">
    <div class="policy-container my-5">
        <h1>Vilkår for brug af <?=BRAND_NAME?></h1>

        <div class="policy-meta">
            <p><strong>Gældende for forbrugere</strong><br>
            Senest opdateret: 14. december 2025</p>
        </div>

        <p>Disse vilkår ("Vilkårene") gælder for forbrugeres brug af betalingsplatformen <?=BRAND_NAME?> ("Platformen"), der drives af <?=COMPANY_NAME?>, CVR-nr. <?=COMPANY_CVR?>, med adresse <?=COMPANY_ADDRESS_STRING?> ("vi", "os" eller "<?=BRAND_NAME?>").</p>

        <p>Ved at oprette en bruger, anvende Platformen eller gennemføre en betaling accepterer du disse Vilkår.</p>

        <hr>

        <h2>1. Platformens rolle</h2>

        <p>1.1 <?=BRAND_NAME?> er en teknisk betalings- og formidlingsplatform, der gør det muligt for forbrugere at gennemføre betalinger, herunder <strong>Køb Nu, Betal Senere (BNPL)</strong>, hos fysiske forretninger ("forhandlere").</p>

        <p>1.2 <strong><?=BRAND_NAME?> er ikke mellemmand, kreditgiver, långiver eller part i købsaftalen</strong> mellem dig og forhandleren. Købsaftalen, herunder levering, reklamation, returnering og eventuel kredit, indgås <strong>udelukkende mellem dig og forhandleren</strong>.</p>

        <p>1.3 <?=BRAND_NAME?> påtager sig intet ansvar for forhandlerens varer, ydelser eller opfyldelse af købsaftalen.</p>

        <hr>

        <h2>2. Betalingstjenester</h2>

        <p>2.1 Alle betalinger på Platformen håndteres af vores eksterne betalingspartner <strong>Viva Bank</strong> ("Betalingsudbyderen").</p>

        <p>2.2 <?=BRAND_NAME?> opbevarer ikke dine betalingsoplysninger og har ikke adgang til dine kort- eller kontodata.</p>

        <p>2.3 Gennemførelse af betalinger er underlagt Betalingsudbyderens egne vilkår, som du accepterer ved brug af betalingstjenesten.</p>

        <hr>

        <h2>3. Identifikation og sikkerhed</h2>

        <p>3.1 For at anvende Platformen og gennemføre betalinger skal du identificere dig ved brug af <strong>MitID</strong>.</p>

        <p>3.2 Du er ansvarlig for at beskytte dine MitID-oplysninger og for enhver brug, der sker via din identitet.</p>

        <p>3.3 <?=BRAND_NAME?> hæfter ikke for tab som følge af misbrug af MitID, medmindre andet følger af ufravigelig dansk lovgivning.</p>

        <hr>

        <h2>4. BNPL – Køb Nu, Betal Senere</h2>

        <p>4.1 BNPL er en betalingsmulighed, hvor du kan udskyde betalingen af dit køb i op til <strong>maksimalt 90 dage</strong> fra købsdatoen.</p>

        <p>4.2 <strong>BNPL-aftalen indgås direkte mellem dig og forhandleren</strong>. Det er forhandleren, der:</p>
        <ul>
            <li>yder kreditten,</li>
            <li>fastsætter vilkår for tilbagebetaling,</li>
            <li>bærer kreditrisikoen.</li>
        </ul>

        <p>4.3 <?=BRAND_NAME?> er ikke kreditgiver, foretager ikke kreditvurdering og har ingen rettigheder eller forpligtelser i relation til BNPL-aftalen.</p>

        <p>4.4 Eventuelle renter, gebyrer eller konsekvenser ved manglende betaling fastsættes af forhandleren og fremgår af aftalen mellem dig og forhandleren.</p>

        <hr>

        <h2>5. Forbrugerrettigheder</h2>

        <p>5.1 Dine forbrugerrettigheder, herunder fortrydelsesret, reklamationsret og mangelsbeføjelser, følger af:</p>
        <ul>
            <li>købeloven,</li>
            <li>forbrugeraftaleloven,</li>
            <li>kreditaftaleloven (hvor relevant),</li>
            <li>samt aftalen mellem dig og forhandleren.</li>
        </ul>

        <p>5.2 <?=BRAND_NAME?> er ikke ansvarlig for opfyldelsen af disse rettigheder, men kan i visse tilfælde fungere som teknisk kontaktpunkt.</p>

        <hr>

        <h2>6. Gebyrer</h2>

        <p>6.1 <?=BRAND_NAME?> opkræver som udgangspunkt ingen gebyrer direkte fra forbrugeren, medmindre andet tydeligt er oplyst forud for transaktionen.</p>

        <p>6.2 Eventuelle gebyrer i forbindelse med BNPL aftales mellem dig og forhandleren.</p>

        <hr>

        <h2>7. Ansvarsbegrænsning</h2>

        <p>7.1 <?=BRAND_NAME?> er alene ansvarlig for den tekniske drift af Platformen.</p>

        <p>7.2 Vi er ikke ansvarlige for:</p>
        <ul>
            <li>forhandlerens handlinger eller undladelser,</li>
            <li>afslag på betaling eller BNPL fra forhandlerens side,</li>
            <li>tab som følge af forkert eller mangelfuld levering af varer eller ydelser.</li>
        </ul>

        <p>7.3 Vores ansvar kan aldrig overstige, hvad der følger af ufravigelig dansk lovgivning.</p>

        <hr>

        <h2>8. Drift og tilgængelighed</h2>

        <p>8.1 Vi tilstræber høj oppetid, men garanterer ikke uafbrudt adgang til Platformen.</p>

        <p>8.2 Vi kan midlertidigt lukke for adgang af hensyn til vedligeholdelse, sikkerhed eller myndighedskrav.</p>

        <hr>

        <h2>9. Databeskyttelse</h2>

        <p>9.1 Behandling af personoplysninger sker i overensstemmelse med vores <strong>Privatlivspolitik</strong>, som er en integreret del af disse Vilkår.</p>

        <p>9.2 Identitets- og transaktionsdata kan deles med Viva Bank og relevante myndigheder i det omfang, det er lovpligtigt.</p>

        <hr>

        <h2>10. Ændringer af vilkår</h2>

        <p>10.1 Vi forbeholder os retten til at ændre disse Vilkår.</p>

        <p>10.2 Væsentlige ændringer vil blive meddelt via Platformen eller e-mail.</p>

        <hr>

        <h2>11. Lovvalg og værneting</h2>

        <p>11.1 Disse Vilkår er underlagt <strong>dansk ret</strong>.</p>

        <p>11.2 Eventuelle tvister skal anlægges ved de danske domstole, med dit værneting som forbruger.</p>

        <hr>

        <h2>12. Kontakt</h2>

        <p>Spørgsmål til disse Vilkår kan rettes til:</p>

        <p><?=COMPANY_NAME?><br>
        E-mail: <?=CONTACT_EMAIL?><br>
        Telefon: <?=CONTACT_PHONE?></p>

        <hr>

        <p>Ved brug af <?=BRAND_NAME?> bekræfter du, at du har læst, forstået og accepteret disse Vilkår.</p>
    </div>
</div>
