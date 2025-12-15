<?php
/**
 * @var object $args
 */
?>

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
            <p><strong>Gældende for forhandlere (fysiske forretninger)</strong><br>
            Senest opdateret: 14. december 2025</p>
        </div>

        <p>Disse vilkår ("forhandler-vilkårene") gælder for brugen af betalingsplatformen <?=BRAND_NAME?> ("Platformen") for erhvervsdrivende forhandlere, der indgår aftale med <?=COMPANY_NAME?>, CVR-nr. <?=COMPANY_CVR?>, med adresse <?=COMPANY_ADDRESS_STRING?> ("vi", "os" eller "<?=BRAND_NAME?>").</p>

        <p>Ved at indgå aftale om brug af Platformen accepterer forhandleren disse forhandler-vilkår.</p>

        <hr>

        <h2>1. Platformens karakter og rolle</h2>

        <p>1.1 <?=BRAND_NAME?> er en teknisk platform, der stiller betalingsinfrastruktur til rådighed for forhandlere, herunder mulighed for at tilbyde betaling og <strong>Køb Nu, Betal Senere (BNPL)</strong> til forbrugere.</p>

        <p>1.2 <?=BRAND_NAME?> er <strong>ikke</strong>:</p>
        <ul>
            <li>kreditgiver,</li>
            <li>mellemmand i købsaftalen,</li>
            <li>betalingsinstitut,</li>
            <li>garant for forbrugeres betaling.</li>
        </ul>

        <p>1.3 Alle købsaftaler, herunder levering, reklamation, fortrydelse og kreditvilkår, indgås direkte mellem forhandleren og forbrugeren.</p>

        <hr>

        <h2>2. Betalinger og håndtering af midler</h2>

        <p>2.1 <?=BRAND_NAME?> håndterer <strong>ikke</strong> forhandlerens eller forbrugerens midler.</p>

        <p>2.2 Alle betalinger behandles og afvikles af vores eksterne betalingspartner <strong>Viva Bank / viva.com</strong> ("Betalingsudbyderen").</p>

        <p>2.3 Afregning, udbetalinger, chargebacks og tilbageførsler er underlagt Betalingsudbyderens vilkår og processer.</p>

        <p>2.4 <?=BRAND_NAME?> har ingen rådighed over, kontrol med eller ansvar for midler, der behandles af Betalingsudbyderen.</p>

        <hr>

        <h2>3. BNPL – Køb Nu, Betal Senere</h2>

        <p>3.1 BNPL er en betalingsmulighed, hvor forhandleren giver forbrugeren mulighed for at udskyde betaling af et køb i op til <strong>maksimalt 90 dage</strong>.</p>

        <p>3.2 <strong>Forhandleren er den eneste kreditgiver</strong> i BNPL-aftalen og bærer den fulde kreditrisiko.</p>

        <p>3.3 <?=BRAND_NAME?> er ikke ansvarlig, hvis en forbruger:</p>
        <ul>
            <li>undlader at betale helt eller delvist,</li>
            <li>betaler for sent,</li>
            <li>misligholder BNPL-aftalen.</li>
        </ul>

        <p>3.4 Manglende betaling fra forbrugeren giver ikke forhandleren ret til regres, kompensation eller erstatning fra <?=BRAND_NAME?>.</p>

        <hr>

        <h2>4. Kreditvurdering og risikoscore</h2>

        <p>4.1 <?=BRAND_NAME?> kan, som en del af Platformens funktionalitet, foretage en <strong>automatisk kredit- eller risikoscore</strong> af forbrugere forud for godkendelse af BNPL.</p>

        <p>4.2 En eventuel godkendelse af BNPL:</p>
        <ul>
            <li>er alene vejledende,</li>
            <li>udgør <strong>ingen garanti</strong> for betaling,</li>
            <li>ændrer ikke på, at kreditrisikoen fuldt ud bæres af forhandleren.</li>
        </ul>

        <p>4.3 <?=BRAND_NAME?> påtager sig intet ansvar for tab, der måtte opstå som følge af en forbrugers manglende betaling, uanset om BNPL er godkendt via Platformen.</p>

        <hr>

        <h2>5. Forhandlerens forpligtelser</h2>

        <p>5.1 Forhandleren er ansvarlig for:</p>
        <ul>
            <li>at overholde gældende lovgivning, herunder købeloven, forbrugeraftaleloven og kreditaftaleloven,</li>
            <li>korrekt og tydelig information til forbrugeren om BNPL-vilkår,</li>
            <li>håndtering af rykkere, inkasso og eventuel inddrivelse.</li>
        </ul>

        <p>5.2 Forhandleren skal sikre, at anvendelsen af Platformen sker i overensstemmelse med disse forhandler-vilkår og gældende ret.</p>

        <hr>

        <h2>6. Gebyrer og betaling til <?=BRAND_NAME?></h2>

        <p>6.1 Eventuelle gebyrer for forhandlerens brug af Platformen fremgår af den særskilte kommercielle aftale mellem parterne.</p>

        <p>6.2 Manglende betaling fra forbrugere påvirker ikke forhandlerens betalingsforpligtelser over for <?=BRAND_NAME?>, medmindre andet er aftalt skriftligt.</p>

        <hr>

        <h2>7. Ansvarsbegrænsning</h2>

        <p>7.1 <?=BRAND_NAME?> er alene ansvarlig for Platformens tekniske funktionalitet.</p>

        <p>7.2 <?=BRAND_NAME?> er ikke ansvarlig for:</p>
        <ul>
            <li>forbrugeres betalingsevne eller betalingsvilje,</li>
            <li>forhandlerens tab som følge af BNPL,</li>
            <li>indirekte tab, herunder driftstab eller tabt fortjeneste.</li>
        </ul>

        <p>7.3 <?=BRAND_NAME?>s samlede ansvar kan aldrig overstige, hvad der følger af ufravigelig dansk ret.</p>

        <hr>

        <h2>8. Databeskyttelse</h2>

        <p>8.1 Parterne er selvstændige dataansvarlige i relation til deres respektive behandling af personoplysninger.</p>

        <p>8.2 Behandling af personoplysninger via Platformen sker i overensstemmelse med <?=BRAND_NAME?>s Privatlivspolitik og gældende databeskyttelseslovgivning.</p>

        <hr>

        <h2>9. Opsigelse og suspension</h2>

        <p>9.1 <?=BRAND_NAME?> kan suspendere eller opsige adgangen til Platformen ved væsentlig misligholdelse af disse forhandler-vilkår.</p>

        <p>9.2 Opsigelse fritager ikke forhandleren for forpligtelser, der er opstået før opsigelsestidspunktet.</p>

        <hr>

        <h2>10. Ændringer af vilkår</h2>

        <p>10.1 <?=BRAND_NAME?> forbeholder sig retten til at ændre disse forhandler-vilkår.</p>

        <p>10.2 Væsentlige ændringer vil blive varslet med rimeligt varsel.</p>

        <hr>

        <h2>11. Lovvalg og værneting</h2>

        <p>11.1 Disse forhandler-vilkår er underlagt <strong>dansk ret</strong>.</p>

        <p>11.2 Eventuelle tvister afgøres ved de danske domstole med værneting ved København, medmindre andet følger af ufravigelig lovgivning.</p>

        <hr>

        <h2>12. Kontakt</h2>

        <p><?=COMPANY_NAME?><br>
        E-mail: <?=CONTACT_EMAIL?><br>
        Telefon: <?=CONTACT_PHONE?></p>

        <hr>

        <p>Ved indgåelse af aftale med <?=BRAND_NAME?> bekræfter forhandleren at have læst, forstået og accepteret disse forhandler-vilkår.</p>
    </div>

</div>