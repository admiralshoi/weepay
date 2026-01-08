<?php
/**
 * @var object $args
 */

$pageTitle = "Privatlivspolitik";
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
        <h1>Privatlivspolitik for <?=BRAND_NAME?></h1>

        <div class="policy-meta">
            <p><strong>Gældende for forbrugere</strong><br>
            Senest opdateret: 14. december 2025</p>
        </div>

        <p>Denne privatlivspolitik ("Privatlivspolitikken") beskriver, hvordan <?=COMPANY_NAME?>, CVR-nr. <?=COMPANY_CVR?>, med adresse <?=COMPANY_ADDRESS_STRING?> ("vi", "os" eller "<?=BRAND_NAME?>") behandler dine personoplysninger, når du anvender betalingsplatformen <?=BRAND_NAME?> ("Platformen").</p>

        <p>Vi behandler personoplysninger i overensstemmelse med <strong>EU's databeskyttelsesforordning (GDPR)</strong> og den danske databeskyttelseslov og indsamler <strong>kun de oplysninger, der er nødvendige</strong> for at levere Platformens funktioner.</p>

        <hr>

        <h2>1. Dataansvarlig</h2>

        <p><?=COMPANY_NAME?> er dataansvarlig for behandlingen af dine personoplysninger.</p>

        <p>Kontaktoplysninger:</p>
        <ul>
            <li>Adresse: <?=COMPANY_ADDRESS_STRING?></li>
            <li>E-mail: <?=CONTACT_EMAIL?></li>
            <li>Telefon: <?=CONTACT_PHONE?></li>
        </ul>

        <hr>

        <h2>2. Hvilke personoplysninger behandler vi?</h2>

        <p>Vi behandler kun personoplysninger, som er nødvendige for at levere Platformen, herunder:</p>

        <h3>2.1 Identitetsoplysninger</h3>
        <ul>
            <li>Navn</li>
            <li>CPR-relaterede identifikationsdata (via MitID – vi modtager ikke dit CPR-nummer)</li>
        </ul>

        <h3>2.2 Kontaktoplysninger</h3>
        <ul>
            <li>E-mailadresse</li>
            <li>Telefonnummer (hvis relevant)</li>
        </ul>

        <h3>2.3 Transaktionsoplysninger</h3>
        <ul>
            <li>Oplysninger om gennemførte betalinger (beløb, dato, forhandler-id)</li>
            <li>Valgt betalingsmetode (fx kort, BNPL)</li>
        </ul>

        <h3>2.4 Tekniske oplysninger</h3>
        <ul>
            <li>Enheds- og browseroplysninger</li>
            <li>Log- og sikkerhedsdata</li>
        </ul>

        <p>Vi opbevarer <strong>ikke</strong>:</p>
        <ul>
            <li>fulde betalingskortoplysninger,</li>
            <li>kontooplysninger,</li>
            <li>adgangsoplysninger til MitID.</li>
        </ul>

        <hr>

        <h2>3. Formål og retsgrundlag</h2>

        <p>Vi behandler dine personoplysninger til følgende formål:</p>

        <table>
            <thead>
                <tr>
                    <th>Formål</th>
                    <th>Retsgrundlag</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Levering af Platformens funktioner</td>
                    <td>GDPR art. 6, stk. 1, litra b</td>
                </tr>
                <tr>
                    <td>Gennemførelse og registrering af betalinger</td>
                    <td>GDPR art. 6, stk. 1, litra b</td>
                </tr>
                <tr>
                    <td>Overholdelse af lovkrav (fx hvidvask)</td>
                    <td>GDPR art. 6, stk. 1, litra c</td>
                </tr>
                <tr>
                    <td>Forebyggelse af svig og misbrug</td>
                    <td>GDPR art. 6, stk. 1, litra f</td>
                </tr>
                <tr>
                    <td>Drift, sikkerhed og fejlsøgning</td>
                    <td>GDPR art. 6, stk. 1, litra f</td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h2>4. Videregivelse af personoplysninger</h2>

        <p>Vi videregiver kun personoplysninger, når det er nødvendigt:</p>

        <h3>4.1 Betalingsudbydere</h3>
        <p><strong>Viva Bank</strong> – håndtering og gennemførelse af betalinger</p>

        <h3>4.2 Identifikationsudbydere</h3>
        <p><strong>MitID</strong> – identitetsbekræftelse før transaktioner</p>

        <h3>4.3 Forhandlere</h3>
        <p>Oplysninger om transaktionen i forbindelse med dit køb</p>

        <h3>4.4 Myndigheder</h3>
        <p>Når det er påkrævet ved lov (fx Finanstilsynet eller andre relevante myndigheder)</p>

        <p>Vi sælger aldrig dine personoplysninger til tredjepart.</p>

        <hr>

        <h2>5. BNPL og databehandling</h2>

        <p>Ved brug af <strong>Køb Nu, Betal Senere (BNPL)</strong>:</p>
        <ul>
            <li>indgås aftalen direkte mellem dig og forhandleren,</li>
            <li>er forhandleren dataansvarlig for kredit- og betalingsoplysninger relateret til BNPL-aftalen,</li>
            <li>fungerer <?=BRAND_NAME?> alene som teknisk formidler af relevante oplysninger.</li>
        </ul>

        <p><?=BRAND_NAME?> foretager ikke kreditvurdering og opbevarer ikke data ud over det, der er nødvendigt for at facilitere betalingen.</p>

        <hr>

        <h2>6. Opbevaring og sletning</h2>

        <p>Vi opbevarer dine personoplysninger <strong>kun så længe, det er nødvendigt</strong> for de formål, de er indsamlet til, herunder:</p>
        <ul>
            <li>Transaktionsdata: i henhold til bogførings- og hvidvasklovgivning</li>
            <li>Tekniske logdata: i en begrænset periode af hensyn til sikkerhed</li>
        </ul>

        <p>Når oplysningerne ikke længere er nødvendige, slettes eller anonymiseres de.</p>

        <hr>

        <h2>7. Dine rettigheder</h2>

        <p>Du har følgende rettigheder efter GDPR:</p>
        <ul>
            <li>Ret til indsigt</li>
            <li>Ret til berigtigelse</li>
            <li>Ret til sletning ("retten til at blive glemt")</li>
            <li>Ret til begrænsning af behandling</li>
            <li>Ret til dataportabilitet</li>
            <li>Ret til indsigelse mod behandling</li>
        </ul>

        <p>Hvis behandlingen er baseret på samtykke, kan dette til enhver tid trækkes tilbage.</p>

        <p>Anmodninger kan rettes til: <?=CONTACT_EMAIL?></p>

        <hr>

        <h2>8. Klage</h2>

        <p>Hvis du ønsker at klage over vores behandling af dine personoplysninger, kan du kontakte os.</p>

        <p>Du har også ret til at indgive klage til:</p>

        <p><strong>Datatilsynet</strong><br>
        Borgergade 28, 5. sal<br>
        1300 København K<br>
        www.datatilsynet.dk</p>

        <hr>

        <h2>9. Datasikkerhed</h2>

        <p>Vi anvender passende tekniske og organisatoriske sikkerhedsforanstaltninger for at beskytte dine personoplysninger mod uautoriseret adgang, tab eller misbrug.</p>

        <hr>

        <h2>10. Ændringer af privatlivspolitikken</h2>

        <p>Vi forbeholder os retten til at opdatere denne Privatlivspolitik.</p>

        <p>Væsentlige ændringer vil blive kommunikeret via Platformen.</p>

        <hr>

        <p>Ved brug af <?=BRAND_NAME?> accepterer du denne Privatlivspolitik.</p>
</div>
</div>
