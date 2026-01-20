<?php

namespace Database\model;

class PolicyVersions extends \Database\Model {

    public static ?string $uidPrefix = "pv";
    protected static array $schema = [
        "uid" => "string",
        "policy_type" => "string",
        "version" => "integer",
        "title" => "string",
        "content" => "text",
        "status" => ["type" => "enum", "default" => "draft", "values" => ["draft", "published", "archived"]],
        "created_by" => ["type" => "string", "nullable" => true],
        "updated_by" => ["type" => "string", "nullable" => true],
        "published_by" => ["type" => "string", "nullable" => true],
        "published_at" => ["type" => "datetime", "nullable" => true],
        "active_from" => ["type" => "datetime", "nullable" => true],
        "active_until" => ["type" => "datetime", "nullable" => true],
    ];

    public static array $indexes = ["policy_type", "status", "version"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [
        [
            "uid" => "pv_consumer_privacy_v1",
            "policy_type" => "pt_consumer_privacy",
            "version" => 1,
            "title" => "Privatlivspolitik",
            "content" => '<h1>Privatlivspolitik for {{BRAND_NAME}}</h1>

<div class="policy-meta">
    <p><strong>Gældende for forbrugere</strong><br>
    Senest opdateret: 14. december 2025</p>
</div>

<p>Denne privatlivspolitik ("Privatlivspolitikken") beskriver, hvordan {{COMPANY_NAME}}, CVR-nr. {{COMPANY_CVR}}, med adresse {{COMPANY_ADDRESS_STRING}} ("vi", "os" eller "{{BRAND_NAME}}") behandler dine personoplysninger, når du anvender betalingsplatformen {{BRAND_NAME}} ("Platformen").</p>

<p>Vi behandler personoplysninger i overensstemmelse med <strong>EU\'s databeskyttelsesforordning (GDPR)</strong> og den danske databeskyttelseslov og indsamler <strong>kun de oplysninger, der er nødvendige</strong> for at levere Platformens funktioner.</p>

<hr>

<h2>1. Dataansvarlig</h2>

<p>{{COMPANY_NAME}} er dataansvarlig for behandlingen af dine personoplysninger.</p>

<p>Kontaktoplysninger:</p>
<ul>
    <li>Adresse: {{COMPANY_ADDRESS_STRING}}</li>
    <li>E-mail: {{CONTACT_EMAIL}}</li>
    <li>Telefon: {{CONTACT_PHONE}}</li>
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
    <li>fungerer {{BRAND_NAME}} alene som teknisk formidler af relevante oplysninger.</li>
</ul>

<p>{{BRAND_NAME}} foretager ikke kreditvurdering og opbevarer ikke data ud over det, der er nødvendigt for at facilitere betalingen.</p>

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

<p>Anmodninger kan rettes til: {{CONTACT_EMAIL}}</p>

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

<p>Ved brug af {{BRAND_NAME}} accepterer du denne Privatlivspolitik.</p>',
            "status" => "published",
            "published_at" => "2024-01-01 00:00:00",
            "active_from" => "2024-01-01 00:00:00",
        ],
        [
            "uid" => "pv_consumer_terms_v1",
            "policy_type" => "pt_consumer_terms",
            "version" => 1,
            "title" => "Handelsbetingelser",
            "content" => '<h1>Vilkår for brug af {{BRAND_NAME}}</h1>

<div class="policy-meta">
    <p><strong>Gældende for forbrugere</strong><br>
    Senest opdateret: 14. december 2025</p>
</div>

<p>Disse vilkår ("Vilkårene") gælder for forbrugeres brug af betalingsplatformen {{BRAND_NAME}} ("Platformen"), der drives af {{COMPANY_NAME}}, CVR-nr. {{COMPANY_CVR}}, med adresse {{COMPANY_ADDRESS_STRING}} ("vi", "os" eller "{{BRAND_NAME}}").</p>

<p>Ved at oprette en bruger, anvende Platformen eller gennemføre en betaling accepterer du disse Vilkår.</p>

<hr>

<h2>1. Platformens rolle</h2>

<p>1.1 {{BRAND_NAME}} er en teknisk betalings- og formidlingsplatform, der gør det muligt for forbrugere at gennemføre betalinger, herunder <strong>Køb Nu, Betal Senere (BNPL)</strong>, hos fysiske forretninger ("forhandlere").</p>

<p>1.2 <strong>{{BRAND_NAME}} er ikke mellemmand, kreditgiver, långiver eller part i købsaftalen</strong> mellem dig og forhandleren. Købsaftalen, herunder levering, reklamation, returnering og eventuel kredit, indgås <strong>udelukkende mellem dig og forhandleren</strong>.</p>

<p>1.3 {{BRAND_NAME}} påtager sig intet ansvar for forhandlerens varer, ydelser eller opfyldelse af købsaftalen.</p>

<hr>

<h2>2. Betalingstjenester</h2>

<p>2.1 Alle betalinger på Platformen håndteres af vores eksterne betalingspartner <strong>Viva Bank</strong> ("Betalingsudbyderen").</p>

<p>2.2 {{BRAND_NAME}} opbevarer ikke dine betalingsoplysninger og har ikke adgang til dine kort- eller kontodata.</p>

<p>2.3 Gennemførelse af betalinger er underlagt Betalingsudbyderens egne vilkår, som du accepterer ved brug af betalingstjenesten.</p>

<hr>

<h2>3. Identifikation og sikkerhed</h2>

<p>3.1 For at anvende Platformen og gennemføre betalinger skal du identificere dig ved brug af <strong>MitID</strong>.</p>

<p>3.2 Du er ansvarlig for at beskytte dine MitID-oplysninger og for enhver brug, der sker via din identitet.</p>

<p>3.3 {{BRAND_NAME}} hæfter ikke for tab som følge af misbrug af MitID, medmindre andet følger af ufravigelig dansk lovgivning.</p>

<hr>

<h2>4. BNPL – Køb Nu, Betal Senere</h2>

<p>4.1 BNPL er en betalingsmulighed, hvor du kan udskyde betalingen af dit køb i op til <strong>maksimalt 90 dage</strong> fra købsdatoen.</p>

<p>4.2 <strong>BNPL-aftalen indgås direkte mellem dig og forhandleren</strong>. Det er forhandleren, der:</p>
<ul>
    <li>yder kreditten,</li>
    <li>fastsætter vilkår for tilbagebetaling,</li>
    <li>bærer kreditrisikoen.</li>
</ul>

<p>4.3 {{BRAND_NAME}} er ikke kreditgiver, foretager ikke kreditvurdering og har ingen rettigheder eller forpligtelser i relation til BNPL-aftalen.</p>

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

<p>5.2 {{BRAND_NAME}} er ikke ansvarlig for opfyldelsen af disse rettigheder, men kan i visse tilfælde fungere som teknisk kontaktpunkt.</p>

<hr>

<h2>6. Gebyrer</h2>

<p>6.1 {{BRAND_NAME}} opkræver som udgangspunkt ingen gebyrer direkte fra forbrugeren, medmindre andet tydeligt er oplyst forud for transaktionen.</p>

<p>6.2 Eventuelle gebyrer i forbindelse med BNPL aftales mellem dig og forhandleren.</p>

<hr>

<h2>7. Ansvarsbegrænsning</h2>

<p>7.1 {{BRAND_NAME}} er alene ansvarlig for den tekniske drift af Platformen.</p>

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

<p>{{COMPANY_NAME}}<br>
E-mail: {{CONTACT_EMAIL}}<br>
Telefon: {{CONTACT_PHONE}}</p>

<hr>

<p>Ved brug af {{BRAND_NAME}} bekræfter du, at du har læst, forstået og accepteret disse Vilkår.</p>',
            "status" => "published",
            "published_at" => "2024-01-01 00:00:00",
            "active_from" => "2024-01-01 00:00:00",
        ],
        [
            "uid" => "pv_merchant_privacy_v1",
            "policy_type" => "pt_merchant_privacy",
            "version" => 1,
            "title" => "Privatlivspolitik for forhandlere",
            "content" => '<h1>Privatlivspolitik for forhandlere</h1>

<div class="policy-meta">
    <p><strong>Gældende for erhvervskunder (forhandlere)</strong><br>
    Senest opdateret: 14. december 2025</p>
</div>

<p>Denne privatlivspolitik ("Privatlivspolitikken") beskriver, hvordan {{COMPANY_NAME}}, CVR-nr. {{COMPANY_CVR}}, med adresse {{COMPANY_ADDRESS_STRING}} ("vi", "os" eller "{{BRAND_NAME}}") behandler personoplysninger og virksomhedsoplysninger i forbindelse med forhandlerens brug af betalingsplatformen {{BRAND_NAME}} ("Platformen").</p>

<p>Vi behandler oplysninger i overensstemmelse med <strong>EU\'s databeskyttelsesforordning (GDPR)</strong> og den danske databeskyttelseslov og følger princippet om <strong>dataminimering</strong>, dvs. at vi kun indsamler og opbevarer oplysninger, der er nødvendige for drift af Platformen.</p>

<hr>

<h2>1. Dataansvarlig</h2>

<p>{{COMPANY_NAME}} er dataansvarlig for behandlingen af de oplysninger, der behandles via Platformen.</p>

<p>Kontaktoplysninger:</p>
<ul>
    <li>Adresse: {{COMPANY_ADDRESS_STRING}}</li>
    <li>E-mail: {{CONTACT_EMAIL}}</li>
    <li>Telefon: {{CONTACT_PHONE}}</li>
</ul>

<hr>

<h2>2. Roller og ansvarsfordeling</h2>

<p>2.1 {{BRAND_NAME}} er dataansvarlig for behandling af oplysninger relateret til:</p>
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

<p>2.3 {{BRAND_NAME}} har <strong>ikke adgang til og kan ikke se</strong> forhandlerens bank-, konto- eller betalingsoplysninger, som udelukkende behandles af Viva Bank.</p>

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

<p>{{BRAND_NAME}} behandler eller opbevarer <strong>ikke</strong>:</p>
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

<p>Anmodninger kan rettes til: {{CONTACT_EMAIL}}</p>

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

<p>Ved brug af {{BRAND_NAME}} accepterer forhandleren denne Privatlivspolitik.</p>',
            "status" => "published",
            "published_at" => "2024-01-01 00:00:00",
            "active_from" => "2024-01-01 00:00:00",
        ],
        [
            "uid" => "pv_merchant_terms_v1",
            "policy_type" => "pt_merchant_terms",
            "version" => 1,
            "title" => "Vilkår for forhandlere",
            "content" => '<h1>Vilkår for brug af {{BRAND_NAME}}</h1>

<div class="policy-meta">
    <p><strong>Gældende for forhandlere (fysiske forretninger)</strong><br>
    Senest opdateret: 14. december 2025</p>
</div>

<p>Disse vilkår ("forhandler-vilkårene") gælder for brugen af betalingsplatformen {{BRAND_NAME}} ("Platformen") for erhvervsdrivende forhandlere, der indgår aftale med {{COMPANY_NAME}}, CVR-nr. {{COMPANY_CVR}}, med adresse {{COMPANY_ADDRESS_STRING}} ("vi", "os" eller "{{BRAND_NAME}}").</p>

<p>Ved at indgå aftale om brug af Platformen accepterer forhandleren disse forhandler-vilkår.</p>

<hr>

<h2>1. Platformens karakter og rolle</h2>

<p>1.1 {{BRAND_NAME}} er en teknisk platform, der stiller betalingsinfrastruktur til rådighed for forhandlere, herunder mulighed for at tilbyde betaling og <strong>Køb Nu, Betal Senere (BNPL)</strong> til forbrugere.</p>

<p>1.2 {{BRAND_NAME}} er <strong>ikke</strong>:</p>
<ul>
    <li>kreditgiver,</li>
    <li>mellemmand i købsaftalen,</li>
    <li>betalingsinstitut,</li>
    <li>garant for forbrugeres betaling.</li>
</ul>

<p>1.3 Alle købsaftaler, herunder levering, reklamation, fortrydelse og kreditvilkår, indgås direkte mellem forhandleren og forbrugeren.</p>

<hr>

<h2>2. Betalinger og håndtering af midler</h2>

<p>2.1 {{BRAND_NAME}} håndterer <strong>ikke</strong> forhandlerens eller forbrugerens midler.</p>

<p>2.2 Alle betalinger behandles og afvikles af vores eksterne betalingspartner <strong>Viva Bank / viva.com</strong> ("Betalingsudbyderen").</p>

<p>2.3 Afregning, udbetalinger, chargebacks og tilbageførsler er underlagt Betalingsudbyderens vilkår og processer.</p>

<p>2.4 {{BRAND_NAME}} har ingen rådighed over, kontrol med eller ansvar for midler, der behandles af Betalingsudbyderen.</p>

<hr>

<h2>3. BNPL – Køb Nu, Betal Senere</h2>

<p>3.1 BNPL er en betalingsmulighed, hvor forhandleren giver forbrugeren mulighed for at udskyde betaling af et køb i op til <strong>maksimalt 90 dage</strong>.</p>

<p>3.2 <strong>Forhandleren er den eneste kreditgiver</strong> i BNPL-aftalen og bærer den fulde kreditrisiko.</p>

<p>3.3 {{BRAND_NAME}} er ikke ansvarlig, hvis en forbruger:</p>
<ul>
    <li>undlader at betale helt eller delvist,</li>
    <li>betaler for sent,</li>
    <li>misligholder BNPL-aftalen.</li>
</ul>

<p>3.4 Manglende betaling fra forbrugeren giver ikke forhandleren ret til regres, kompensation eller erstatning fra {{BRAND_NAME}}.</p>

<hr>

<h2>4. Kreditvurdering og risikoscore</h2>

<p>4.1 {{BRAND_NAME}} kan, som en del af Platformens funktionalitet, foretage en <strong>automatisk kredit- eller risikoscore</strong> af forbrugere forud for godkendelse af BNPL.</p>

<p>4.2 En eventuel godkendelse af BNPL:</p>
<ul>
    <li>er alene vejledende,</li>
    <li>udgør <strong>ingen garanti</strong> for betaling,</li>
    <li>ændrer ikke på, at kreditrisikoen fuldt ud bæres af forhandleren.</li>
</ul>

<p>4.3 {{BRAND_NAME}} påtager sig intet ansvar for tab, der måtte opstå som følge af en forbrugers manglende betaling, uanset om BNPL er godkendt via Platformen.</p>

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

<h2>6. Gebyrer og betaling til {{BRAND_NAME}}</h2>

<p>6.1 Eventuelle gebyrer for forhandlerens brug af Platformen fremgår af den særskilte kommercielle aftale mellem parterne.</p>

<p>6.2 Manglende betaling fra forbrugere påvirker ikke forhandlerens betalingsforpligtelser over for {{BRAND_NAME}}, medmindre andet er aftalt skriftligt.</p>

<hr>

<h2>7. Ansvarsbegrænsning</h2>

<p>7.1 {{BRAND_NAME}} er alene ansvarlig for Platformens tekniske funktionalitet.</p>

<p>7.2 {{BRAND_NAME}} er ikke ansvarlig for:</p>
<ul>
    <li>forbrugeres betalingsevne eller betalingsvilje,</li>
    <li>forhandlerens tab som følge af BNPL,</li>
    <li>indirekte tab, herunder driftstab eller tabt fortjeneste.</li>
</ul>

<p>7.3 {{BRAND_NAME}}s samlede ansvar kan aldrig overstige, hvad der følger af ufravigelig dansk ret.</p>

<hr>

<h2>8. Databeskyttelse</h2>

<p>8.1 Parterne er selvstændige dataansvarlige i relation til deres respektive behandling af personoplysninger.</p>

<p>8.2 Behandling af personoplysninger via Platformen sker i overensstemmelse med {{BRAND_NAME}}s Privatlivspolitik og gældende databeskyttelseslovgivning.</p>

<hr>

<h2>9. Opsigelse og suspension</h2>

<p>9.1 {{BRAND_NAME}} kan suspendere eller opsige adgangen til Platformen ved væsentlig misligholdelse af disse forhandler-vilkår.</p>

<p>9.2 Opsigelse fritager ikke forhandleren for forpligtelser, der er opstået før opsigelsestidspunktet.</p>

<hr>

<h2>10. Ændringer af vilkår</h2>

<p>10.1 {{BRAND_NAME}} forbeholder sig retten til at ændre disse forhandler-vilkår.</p>

<p>10.2 Væsentlige ændringer vil blive varslet med rimeligt varsel.</p>

<hr>

<h2>11. Lovvalg og værneting</h2>

<p>11.1 Disse forhandler-vilkår er underlagt <strong>dansk ret</strong>.</p>

<p>11.2 Eventuelle tvister afgøres ved de danske domstole med værneting ved København, medmindre andet følger af ufravigelig lovgivning.</p>

<hr>

<h2>12. Kontakt</h2>

<p>{{COMPANY_NAME}}<br>
E-mail: {{CONTACT_EMAIL}}<br>
Telefon: {{CONTACT_PHONE}}</p>

<hr>

<p>Ved indgåelse af aftale med {{BRAND_NAME}} bekræfter forhandleren at have læst, forstået og accepteret disse forhandler-vilkår.</p>',
            "status" => "published",
            "published_at" => "2024-01-01 00:00:00",
            "active_from" => "2024-01-01 00:00:00",
        ],
        [
            "uid" => "pv_cookies_v1",
            "policy_type" => "pt_cookies",
            "version" => 1,
            "title" => "Cookiepolitik",
            "content" => '<h1>Cookiepolitik for {{BRAND_NAME}}</h1>

<div class="policy-meta">
    <p><strong>Gældende for alle brugere</strong><br>
    Senest opdateret: 14. december 2025</p>
</div>

<p>Denne cookiepolitik beskriver, hvordan {{COMPANY_NAME}}, CVR-nr. {{COMPANY_CVR}}, med adresse {{COMPANY_ADDRESS_STRING}} ("vi", "os" eller "{{BRAND_NAME}}") anvender cookies og lignende teknologier på betalingsplatformen {{BRAND_NAME}} ("Platformen").</p>

<hr>

<h2>1. Hvad er cookies?</h2>

<p>Cookies er små tekstfiler, der gemmes på din enhed (computer, tablet eller mobiltelefon), når du besøger en hjemmeside. Cookies hjælper hjemmesiden med at huske dine præferencer og gøre din oplevelse bedre.</p>

<hr>

<h2>2. Hvilke cookies bruger vi?</h2>

<h3>2.1 Nødvendige cookies</h3>
<p>Disse cookies er essentielle for, at Platformen kan fungere korrekt. De bruges til:</p>
<ul>
    <li>At holde dig logget ind</li>
    <li>At huske dine sikkerhedsindstillinger</li>
    <li>At sikre, at betalinger gennemføres sikkert</li>
</ul>
<p>Disse cookies kan ikke fravælges, da Platformen ikke kan fungere uden dem.</p>

<h3>2.2 Funktionelle cookies</h3>
<p>Disse cookies husker dine valg og præferencer for at give dig en bedre brugeroplevelse, såsom:</p>
<ul>
    <li>Sprogindstillinger</li>
    <li>Visningsindstillinger</li>
</ul>

<h3>2.3 Analytiske cookies</h3>
<p>Vi bruger analytiske cookies til at forstå, hvordan besøgende bruger Platformen. Dette hjælper os med at forbedre funktionalitet og brugeroplevelse. Disse cookies indsamler anonymiserede data.</p>

<hr>

<h2>3. Tredjepartscookies</h2>

<p>Vores betalingspartner <strong>Viva Bank</strong> kan sætte egne cookies i forbindelse med betalingsprocessen. Disse cookies er underlagt Viva Banks egen cookiepolitik.</p>

<hr>

<h2>4. Sådan administrerer du cookies</h2>

<p>Du kan til enhver tid ændre dine cookieindstillinger i din browser. De fleste browsere giver dig mulighed for at:</p>
<ul>
    <li>Se hvilke cookies der er gemt</li>
    <li>Slette cookies enkeltvis eller alle på én gang</li>
    <li>Blokere cookies fra tredjeparter</li>
    <li>Blokere alle cookies</li>
</ul>

<p><strong>Bemærk:</strong> Hvis du blokerer nødvendige cookies, vil dele af Platformen muligvis ikke fungere korrekt.</p>

<hr>

<h2>5. Opbevaringsperiode</h2>

<p>Cookies opbevares i forskellige perioder afhængigt af deres formål:</p>
<ul>
    <li><strong>Sessionscookies:</strong> Slettes, når du lukker browseren</li>
    <li><strong>Vedvarende cookies:</strong> Opbevares i op til 12 måneder</li>
</ul>

<hr>

<h2>6. Ændringer af cookiepolitikken</h2>

<p>Vi forbeholder os retten til at opdatere denne cookiepolitik. Væsentlige ændringer vil blive meddelt via Platformen.</p>

<hr>

<h2>7. Kontakt</h2>

<p>Har du spørgsmål om vores brug af cookies, kan du kontakte os på:</p>

<p>{{COMPANY_NAME}}<br>
E-mail: {{CONTACT_EMAIL}}<br>
Telefon: {{CONTACT_PHONE}}</p>

<hr>

<p>Ved at fortsætte med at bruge {{BRAND_NAME}} accepterer du vores brug af cookies som beskrevet i denne politik.</p>',
            "status" => "published",
            "published_at" => "2024-01-01 00:00:00",
            "active_from" => "2024-01-01 00:00:00",
        ],
    ];

    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [
            "policy_type" => [PolicyTypes::tableColumn("uid"), PolicyTypes::newStatic()],
            "created_by" => [Users::tableColumn("uid"), Users::newStatic()],
            "updated_by" => [Users::tableColumn("uid"), Users::newStatic()],
            "published_by" => [Users::tableColumn("uid"), Users::newStatic()],
        ];
    }
}
