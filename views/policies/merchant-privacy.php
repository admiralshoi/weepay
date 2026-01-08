<?php
/**
 * @var object $args
 */

$pageTitle = "Forhandler privatlivspolitik";
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
    .policy-container table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
    }
    .policy-container th, .policy-container td {
        border: 1px solid #ddd;
        padding: 0.75rem;
        text-align: left;
    }
    .policy-container th {
        background-color: #f8f9fa;
        font-weight: 600;
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
        <h1>Privatlivspolitik for forhandlere</h1>

        <div class="policy-meta">
            <p><strong>Gældende for erhvervskunder (forhandlere)</strong><br>
            Senest opdateret: 14. december 2025</p>
        </div>

        <p>Denne privatlivspolitik ("Privatlivspolitikken") beskriver, hvordan <?=COMPANY_NAME?>, CVR-nr. <?=COMPANY_CVR?>, med adresse <?=COMPANY_ADDRESS_STRING?> ("vi", "os" eller "<?=BRAND_NAME?>") behandler personoplysninger og virksomhedsoplysninger i forbindelse med forhandlerens brug af betalingsplatformen <?=BRAND_NAME?> ("Platformen").</p>

        <p>Vi behandler oplysninger i overensstemmelse med <strong>EU's databeskyttelsesforordning (GDPR)</strong> og den danske databeskyttelseslov og følger princippet om <strong>dataminimering</strong>, dvs. at vi kun indsamler og opbevarer oplysninger, der er nødvendige for drift af Platformen.</p>

        <hr>

        <h2>1. Dataansvarlig</h2>

        <p><?=COMPANY_NAME?> er dataansvarlig for behandlingen af de oplysninger, der behandles via Platformen.</p>

        <p>Kontaktoplysninger:</p>
        <ul>
            <li>Adresse: <?=COMPANY_ADDRESS_STRING?></li>
            <li>E-mail: <?=CONTACT_EMAIL?></li>
            <li>Telefon: <?=CONTACT_PHONE?></li>
        </ul>

        <hr>

        <h2>2. Roller og ansvarsfordeling</h2>

        <p>2.1 <?=BRAND_NAME?> er dataansvarlig for behandling af oplysninger relateret til:</p>
        <ul>
            <li>Forhandler-konti på Platformen</li>
            <li>Platformens drift, sikkerhed og administration</li>
        </ul>

        <p>2.2 Vores eksterne betalingspartner <strong>Viva Bank / viva.com</strong> er selvstændig dataansvarlig for behandling af:</p>
        <ul>
            <li>bank- og kontooplysninger,</li>
            <li>udbetalinger og afregning,</li>
            <li>betalingsrelaterede compliance-krav.</li>
        </ul>

        <p>2.3 <?=BRAND_NAME?> har <strong>ikke adgang til og kan ikke se</strong> forhandlerens bank-, konto- eller betalingsoplysninger, som udelukkende behandles af Viva Bank.</p>

        <hr>

        <h2>3. Hvilke oplysninger behandler vi om forhandleren?</h2>

        <h3>3.1 Virksomhedsoplysninger</h3>

        <p>Vi behandler følgende virksomhedsoplysninger:</p>
        <ul>
            <li>Virksomhedens navn</li>
            <li>CVR-nummer</li>
            <li>Forretningsadresse</li>
            <li>Oplysning om hvorvidt virksomheden er verificeret på Platformen</li>
        </ul>

        <h3>3.2 Konto- og brugeroplysninger</h3>

        <p>For den eller de personer, der opretter og administrerer forhandler-kontoen, behandler vi:</p>
        <ul>
            <li>Navn</li>
            <li>E-mailadresse</li>
            <li>Telefonnummer (hvis oplyst)</li>
            <li>Fødselsdato (kun hvis frivilligt oplyst og relevant)</li>
        </ul>

        <p>Vi behandler <strong>ingen følsomme personoplysninger</strong>.</p>

        <h3>3.3 Tekniske og metadata</h3>
        <ul>
            <li>IP-adresse</li>
            <li>Log- og sikkerhedsdata</li>
            <li>Brugs- og fejllogs</li>
        </ul>

        <hr>

        <h2>4. Oplysninger vi ikke behandler</h2>

        <p><?=BRAND_NAME?> behandler eller opbevarer <strong>ikke</strong>:</p>
        <ul>
            <li>bank- eller kontooplysninger,</li>
            <li>kortoplysninger,</li>
            <li>adgangsoplysninger til eksterne betalingstjenester,</li>
            <li>oplysninger om forhandlerens kunder ud over transaktionsmetadata.</li>
        </ul>

        <hr>

        <h2>5. Formål og retsgrundlag</h2>

        <p>Vi behandler oplysninger til følgende formål:</p>

        <table>
            <thead>
                <tr>
                    <th>Formål</th>
                    <th>Retsgrundlag</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Oprettelse og administration af forhandler-konto</td>
                    <td>GDPR art. 6, stk. 1, litra b</td>
                </tr>
                <tr>
                    <td>Drift og levering af Platformen</td>
                    <td>GDPR art. 6, stk. 1, litra b</td>
                </tr>
                <tr>
                    <td>Verifikation af forhandler</td>
                    <td>GDPR art. 6, stk. 1, litra c</td>
                </tr>
                <tr>
                    <td>Sikkerhed, logning og fejlsøgning</td>
                    <td>GDPR art. 6, stk. 1, litra f</td>
                </tr>
                <tr>
                    <td>Overholdelse af lovkrav</td>
                    <td>GDPR art. 6, stk. 1, litra c</td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h2>6. Videregivelse af oplysninger</h2>

        <p>Vi videregiver kun oplysninger, når det er nødvendigt:</p>

        <h3>6.1 Betalingsudbyder</h3>
        <p><strong>Viva Bank / viva.com</strong> (i det omfang teknisk nødvendigt for integration)</p>

        <h3>6.2 Myndigheder</h3>
        <p>Når videregivelse er påkrævet i henhold til lovgivning</p>

        <p>Vi sælger aldrig oplysninger til tredjepart.</p>

        <hr>

        <h2>7. Opbevaring og sletning</h2>

        <p>Vi opbevarer oplysninger, så længe det er nødvendigt for:</p>
        <ul>
            <li>at opretholde forhandler-forholdet,</li>
            <li>at overholde lovpligtige opbevaringskrav.</li>
        </ul>

        <p>Når oplysninger ikke længere er nødvendige, slettes eller anonymiseres de.</p>

        <hr>

        <h2>8. Forhandlerens rettigheder</h2>

        <p>I det omfang behandlingen vedrører personoplysninger, har de registrerede følgende rettigheder:</p>
        <ul>
            <li>Ret til indsigt</li>
            <li>Ret til berigtigelse</li>
            <li>Ret til sletning</li>
            <li>Ret til begrænsning af behandling</li>
            <li>Ret til indsigelse</li>
            <li>Ret til dataportabilitet</li>
        </ul>

        <p>Anmodninger kan rettes til: <?=CONTACT_EMAIL?></p>

        <hr>

        <h2>9. Datasikkerhed</h2>

        <p>Vi anvender passende tekniske og organisatoriske sikkerhedsforanstaltninger for at beskytte oplysninger mod uautoriseret adgang, tab eller misbrug.</p>

        <hr>

        <h2>10. Ændringer af privatlivspolitikken</h2>

        <p>Vi forbeholder os retten til at opdatere denne Privatlivspolitik.</p>

        <p>Væsentlige ændringer vil blive kommunikeret til forhandleren via Platformen.</p>

        <hr>

        <h2>11. Klage</h2>

        <p>Hvis du ønsker at klage over vores behandling af personoplysninger, kan du kontakte os.</p>

        <p>Du kan også indgive klage til:</p>

        <p><strong>Datatilsynet</strong><br>
        Borgergade 28, 5. sal<br>
        1300 København K<br>
        www.datatilsynet.dk</p>

        <hr>

        <p>Ved brug af <?=BRAND_NAME?> accepterer forhandleren denne Privatlivspolitik.</p>
    </div>
</div>
