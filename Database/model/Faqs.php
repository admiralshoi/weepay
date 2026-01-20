<?php

namespace Database\model;

class Faqs extends \Database\Model {

    public static ?string $uidPrefix = "faq";
    protected static array $schema = [
        "uid"           => "string",
        "type"          => ["type" => "enum", "default" => "consumer", "values" => ["consumer", "merchant"]],
        "category"      => "string",
        "title"         => "string",
        "content"       => ["type" => "text", "nullable" => false],
        "sort_order"    => ["type" => "integer", "default" => 0],
        "is_active"     => ["type" => "tinyInteger", "default" => 1],
    ];

    public static array $indexes = ["type", "category", "is_active"];
    public static array $uniques = ["uid"];

    protected static array $requiredRows = [
        // =====================================================
        // CONSUMER FAQs
        // =====================================================

        // Generelt om WeePay
        ["uid" => "faq_c_g1", "type" => "consumer", "category" => "Generelt om WeePay", "sort_order" => 1,
            "title" => "Hvad er WeePay?",
            "content" => "WeePay er Danmarks førende Køb nu, Betal senere-løsning til fysiske butikker. Med WeePay kan du betale med det samme, udskyde betalingen til næste måned, eller dele betalingen i 4 rater over 90 dage."],

        ["uid" => "faq_c_g2", "type" => "consumer", "category" => "Generelt om WeePay", "sort_order" => 2,
            "title" => "Hvem yder kreditten ved udskudt betaling eller ratebetaling?",
            "content" => "<strong>Vigtigt:</strong> WeePay yder ikke selv kredit. Når du vælger at udskyde betalingen eller betale i rater, er det <strong>butikken/forhandleren</strong>, der giver dig henstand med betalingen. WeePay er udelukkende en teknisk platform, der faciliterer betalingsløsningen for butikkerne. Vi stiller systemet til rådighed, men kreditten ydes af den butik, du handler hos."],

        ["uid" => "faq_c_g3", "type" => "consumer", "category" => "Generelt om WeePay", "sort_order" => 3,
            "title" => "Hvordan virker WeePay?",
            "content" => "<ol><li>Scan QR-koden i butikken</li><li>Log ind med MitID eller opret en konto</li><li>Vælg din foretrukne betalingsplan</li><li>Godkend købet - færdig!</li></ol>"],

        ["uid" => "faq_c_g4", "type" => "consumer", "category" => "Generelt om WeePay", "sort_order" => 4,
            "title" => "Er WeePay sikkert?",
            "content" => "Ja. WeePay samarbejder med Viva Bank, en EU-reguleret bank med indskudsgaranti op til 100.000 EUR. Vi gemmer aldrig dine fulde kortoplysninger - kun de sidste 4 cifre til reference. Al data er krypteret og beskyttet."],

        ["uid" => "faq_c_g5", "type" => "consumer", "category" => "Generelt om WeePay", "sort_order" => 5,
            "title" => "Hvilke butikker bruger WeePay?",
            "content" => "WeePay bruges af et voksende antal fysiske butikker i Danmark. Du kan genkende WeePay-butikker på vores QR-kode ved kassen. Spørg gerne i din favoritbutik, om de tilbyder WeePay."],

        // Betaling & Betalingsplaner
        ["uid" => "faq_c_b1", "type" => "consumer", "category" => "Betaling og Betalingsplaner", "sort_order" => 1,
            "title" => "Hvilke betalingsmuligheder har jeg?",
            "content" => "<ul><li><strong>Betal Nu:</strong> Fuld betaling med det samme</li><li><strong>Betal d. 1. i Måneden:</strong> Udskyd betalingen til den 1. i næste måned</li><li><strong>Del i 4 Rater:</strong> Opdel betalingen i 4 lige store rater over 90 dage (maks. 1.000 kr)</li></ul>"],

        ["uid" => "faq_c_b2", "type" => "consumer", "category" => "Betaling og Betalingsplaner", "sort_order" => 2,
            "title" => "Hvad er Betal Nu?",
            "content" => "Betal Nu er den simpleste mulighed - du betaler det fulde beløb med det samme fra dit betalingskort. Betalingen gennemføres øjeblikkeligt."],

        ["uid" => "faq_c_b3", "type" => "consumer", "category" => "Betaling og Betalingsplaner", "sort_order" => 3,
            "title" => "Hvad er Betal d. 1. i Måneden?",
            "content" => "Med udskudt betaling udskydes betalingen automatisk til den 1. i den følgende måned. Dette giver dig tid til at få løn, før betalingen trækkes. Der er ingen ekstra gebyrer ved denne mulighed."],

        ["uid" => "faq_c_b4", "type" => "consumer", "category" => "Betaling og Betalingsplaner", "sort_order" => 4,
            "title" => "Hvad er Del i 4 Rater (BNPL)?",
            "content" => "Del i 4 Rater giver dig mulighed for at opdele dit køb i 4 lige store betalinger over 90 dage. Første rate betales ved køb, og de resterende 3 rater trækkes automatisk med ca. 30 dages mellemrum. Denne mulighed er tilgængelig for køb op til 1.000 kr."],

        ["uid" => "faq_c_b5", "type" => "consumer", "category" => "Betaling og Betalingsplaner", "sort_order" => 5,
            "title" => "Hvordan fungerer automatiske betalinger?",
            "content" => "Når du vælger udskudt betaling eller ratebetaling, gemmer vi dit kort sikkert og trækker automatisk på de aftalte datoer. Du modtager en påmindelse før hver betaling."],

        // Min Konto & Dashboard
        ["uid" => "faq_c_k1", "type" => "consumer", "category" => "Min Konto og Dashboard", "sort_order" => 1,
            "title" => "Hvordan opretter jeg en konto?",
            "content" => "Du opretter en konto ved første køb. Scan QR-koden i butikken, og følg vejledningen for at verificere dig med MitID. Din konto oprettes automatisk, og du kan med det samme begynde at handle."],

        ["uid" => "faq_c_k2", "type" => "consumer", "category" => "Min Konto og Dashboard", "sort_order" => 2,
            "title" => "Hvad kan jeg se på mit dashboard?",
            "content" => "På dit dashboard kan du se: <ul><li>Kommende betalinger og forfaldsdatoer</li><li>Tidligere ordrer og kvitteringer</li><li>Dine gemte betalingskort</li><li>Din betalingshistorik</li></ul>"],

        ["uid" => "faq_c_k3", "type" => "consumer", "category" => "Min Konto og Dashboard", "sort_order" => 3,
            "title" => "Hvordan ændrer jeg mit betalingskort?",
            "content" => "Gå til dit dashboard, vælg Betalinger, og klik på \"Skift kort\". Vælg de betalinger, du vil opdatere, og tilføj et nyt kort. Det nye kort bruges til fremtidige automatiske betalinger."],

        // Problemer & Support
        ["uid" => "faq_c_p1", "type" => "consumer", "category" => "Problemer og Support", "sort_order" => 1,
            "title" => "Hvad sker der, hvis jeg ikke kan betale til tiden?",
            "content" => "Hvis en betaling ikke gennemføres til tiden, sender vi påmindelser (rykkere):<ul><li>1. rykker efter 7 dage (gratis)</li><li>2. rykker efter 14 dage (100 kr gebyr)</li><li>3. rykker efter 21 dage (100 kr gebyr)</li></ul>Du kan altid betale via \"Betal nu\" på dit dashboard for at undgå yderligere gebyrer."],

        ["uid" => "faq_c_p2", "type" => "consumer", "category" => "Problemer og Support", "sort_order" => 2,
            "title" => "Hvordan betaler jeg en forfalden betaling?",
            "content" => "Log ind på dit dashboard, find den forfaldne betaling, og klik \"Betal nu\". Du kan også opdatere dit betalingskort, hvis det gamle er udløbet eller spærret."],

        ["uid" => "faq_c_p3", "type" => "consumer", "category" => "Problemer og Support", "sort_order" => 3,
            "title" => "Hvordan kontakter jeg support?",
            "content" => "Du kan kontakte os via: <ul><li>Email: support@wee-pay.dk</li><li>Telefon: Se kontaktinfo på hjemmesiden</li><li>Kontaktformular på wee-pay.dk</li></ul>Vi besvarer henvendelser inden for 1-2 hverdage."],

        // Sikkerhed & Privatliv
        ["uid" => "faq_c_s1", "type" => "consumer", "category" => "Sikkerhed og Privatliv", "sort_order" => 1,
            "title" => "Gemmer I mine kortoplysninger?",
            "content" => "Vi gemmer aldrig dine fulde kortoplysninger. Kortdata håndteres sikkert af vores betalingspartner Viva Wallet, som er PCI DSS-certificeret. Vi gemmer kun de sidste 4 cifre til reference."],

        ["uid" => "faq_c_s2", "type" => "consumer", "category" => "Sikkerhed og Privatliv", "sort_order" => 2,
            "title" => "Hvad er MitID-verificering?",
            "content" => "MitID-verificering sikrer, at det virkelig er dig, der opretter kontoen og foretager køb. Dette beskytter dig mod identitetstyveri og sikrer, at kun du kan bruge din WeePay-konto."],

        ["uid" => "faq_c_s3", "type" => "consumer", "category" => "Sikkerhed og Privatliv", "sort_order" => 3,
            "title" => "Hvordan beskytter I mine data?",
            "content" => "Al data krypteres både under transport og opbevaring. Vi følger GDPR og danske databeskyttelsesregler. Du kan læse mere i vores privatlivspolitik."],

        // =====================================================
        // MERCHANT FAQs
        // =====================================================

        // Kom i Gang
        ["uid" => "faq_m_k1", "type" => "merchant", "category" => "Kom i Gang", "sort_order" => 1,
            "title" => "Hvad er WeePay for forhandlere?",
            "content" => "WeePay giver dine kunder fleksible betalingsmuligheder direkte i din fysiske butik. Studier viser, at BNPL kan øge din gennemsnitlige kurv med op til 50%."],

        ["uid" => "faq_m_k2", "type" => "merchant", "category" => "Kom i Gang", "sort_order" => 2,
            "title" => "Hvordan tilmelder jeg mig?",
            "content" => "Tilmelding tager kun 5 minutter: <ol><li>Opret en konto på wee-pay.dk</li><li>Verificer din virksomhed med CVR</li><li>Forbind din Viva Wallet-konto</li><li>Tilføj din første lokation og terminal</li></ol>"],

        ["uid" => "faq_m_k3", "type" => "merchant", "category" => "Kom i Gang", "sort_order" => 3,
            "title" => "Hvor lang tid tager opsætningen?",
            "content" => "Kun 5 minutter! Opret din konto, forbind Viva Wallet, tilføj din første lokation og terminal, og du er klar."],

        ["uid" => "faq_m_k4", "type" => "merchant", "category" => "Kom i Gang", "sort_order" => 4,
            "title" => "Hvad kræves for at komme i gang?",
            "content" => "For at bruge WeePay skal du:<ul><li>Have en registreret virksomhed (CVR-nummer)</li><li>Oprette en Viva Wallet-konto (vores betalingspartner)</li><li>Have mindst én fysisk butikslokation</li><li>Have en PC, tablet eller smartphone til at håndtere betalinger</li></ul>"],

        // Organisation & Lokationer
        ["uid" => "faq_m_o1", "type" => "merchant", "category" => "Organisation og Lokationer", "sort_order" => 1,
            "title" => "Hvad er en organisation?",
            "content" => "En organisation repræsenterer din virksomhed i WeePay. Under organisationen kan du have flere lokationer (butikker) og teammedlemmer med forskellige roller."],

        ["uid" => "faq_m_o2", "type" => "merchant", "category" => "Organisation og Lokationer", "sort_order" => 2,
            "title" => "Hvordan opretter jeg en butik/lokation?",
            "content" => "Gå til Lokationer i dit dashboard og klik \"Tilføj lokation\". Udfyld butikkens navn, adresse og kontaktinfo. Du kan oprette så mange lokationer, som du har brug for."],

        ["uid" => "faq_m_o3", "type" => "merchant", "category" => "Organisation og Lokationer", "sort_order" => 3,
            "title" => "Kan jeg have flere lokationer?",
            "content" => "Ja! Du kan tilføje ubegrænset antal lokationer under din organisation. Hver lokation kan have sine egne terminaler og medarbejdere."],

        // Terminaler & QR-Koder
        ["uid" => "faq_m_t1", "type" => "merchant", "category" => "Terminaler og QR-Koder", "sort_order" => 1,
            "title" => "Hvad er en WeePay-terminal?",
            "content" => "En WeePay-terminal er dit virtuelle kassepunkt. Hver terminal har en unik QR-kode, som kunderne scanner for at betale. Du kan have flere terminaler pr. lokation - f.eks. \"Kasse 1\", \"Kasse 2\" osv. Du skal bruge en PC, tablet eller smartphone til at indtaste beløb og følge betalingen - ingen speciel betalingsterminal er nødvendig."],

        ["uid" => "faq_m_t2", "type" => "merchant", "category" => "Terminaler og QR-Koder", "sort_order" => 2,
            "title" => "Hvordan opretter jeg en terminal?",
            "content" => "<ol><li>Gå til din lokation i dashboardet</li><li>Klik \"Tilføj Terminal\"</li><li>Giv terminalen et navn (f.eks. \"Kasse 1\")</li><li>Print eller vis QR-koden for kunderne</li></ol>"],

        ["uid" => "faq_m_t3", "type" => "merchant", "category" => "Terminaler og QR-Koder", "sort_order" => 3,
            "title" => "Hvordan fungerer QR-koden?",
            "content" => "Når kunden scanner QR-koden med deres telefon, åbnes WeePays betalingsside. Du indtaster beløbet på din enhed (PC, tablet eller telefon), og kunden kan se beløbet, logge ind, vælge betalingsplan og godkende købet på deres telefon. Du kan følge betalingen live på din skærm. Hele processen tager typisk under et minut."],

        // Betalinger & Afregning
        ["uid" => "faq_m_b1", "type" => "merchant", "category" => "Betalinger og Afregning", "sort_order" => 1,
            "title" => "Hvem yder kreditten til kunderne?",
            "content" => "<strong>Vigtigt at forstå:</strong> WeePay yder ikke selv kredit til dine kunder. Når en kunde vælger at udskyde betalingen eller betale i rater, er det <strong>din butik</strong>, der giver kunden henstand med betalingen. WeePay er udelukkende en teknisk platform, der faciliterer betalingsløsningen. Vi stiller systemet til rådighed - inklusiv håndtering af betalinger, påmindelser og rykkere - men kreditten ydes af dig som forhandler til dine kunder."],

        ["uid" => "faq_m_b2", "type" => "merchant", "category" => "Betalinger og Afregning", "sort_order" => 2,
            "title" => "Hvilke betalingsplaner kan mine kunder vælge?",
            "content" => "Dine kunder kan vælge mellem:<ul><li><strong>Betal Nu:</strong> Fuld betaling med det samme</li><li><strong>Betal d. 1.:</strong> Udskudt betaling til næste måned</li><li><strong>Del i 4:</strong> Ratebetaling over 90 dage (maks 1.000 kr)</li></ul>"],

        ["uid" => "faq_m_b3", "type" => "merchant", "category" => "Betalinger og Afregning", "sort_order" => 3,
            "title" => "Hvornår modtager jeg mine penge?",
            "content" => "Penge afregnes direkte til din Viva Wallet-konto. Ved ratebetaling modtager du hver rate, når kunden betaler."],

        ["uid" => "faq_m_b4", "type" => "merchant", "category" => "Betalinger og Afregning", "sort_order" => 4,
            "title" => "Hvad sker der, hvis en kunde ikke betaler?",
            "content" => "WeePay håndterer automatisk rykkere og betalingspåmindelser. Du har adgang til at se status på alle betalinger i dit dashboard."],

        // Gebyrer & Priser
        ["uid" => "faq_m_g1", "type" => "merchant", "category" => "Gebyrer og Priser", "sort_order" => 1,
            "title" => "Hvad koster det at bruge WeePay?",
            "content" => "WeePay opkræver et transaktionsgebyr på hver betaling. Der er ingen skjulte gebyrer, ingen månedlige abonnementer, og ingen opsætningsgebyrer. Du betaler kun, når du sælger."],

        ["uid" => "faq_m_g2", "type" => "merchant", "category" => "Gebyrer og Priser", "sort_order" => 2,
            "title" => "Er der skjulte gebyrer?",
            "content" => "Nej. Vi tror på fuld gennemsigtighed. Du ser altid det præcise gebyr for hver transaktion. Ingen overraskelser."],

        // Team & Roller
        ["uid" => "faq_m_r1", "type" => "merchant", "category" => "Team og Roller", "sort_order" => 1,
            "title" => "Hvordan tilføjer jeg medarbejdere?",
            "content" => "Gå til Team i dit dashboard og klik \"Inviter medlem\". Du kan tildele roller som kassemedarbejder, butikschef, eller administrator med forskellige adgangsniveauer."],

        ["uid" => "faq_m_r2", "type" => "merchant", "category" => "Team og Roller", "sort_order" => 2,
            "title" => "Hvilke roller findes der?",
            "content" => "WeePay tilbyder flere roller:<ul><li><strong>Administrator:</strong> Fuld adgang til alt</li><li><strong>Butikschef:</strong> Kan administrere lokationer og se rapporter</li><li><strong>Kassemedarbejder:</strong> Kan tage betalinger</li></ul>"],

        ["uid" => "faq_m_r3", "type" => "merchant", "category" => "Team og Roller", "sort_order" => 3,
            "title" => "Kan jeg begrænse adgang til specifikke lokationer?",
            "content" => "Ja. Du kan tildele medarbejdere til specifikke lokationer, så de kun ser og kan arbejde med de relevante butikker."],

        // Viva Wallet Integration
        ["uid" => "faq_m_v1", "type" => "merchant", "category" => "Viva Wallet Integration", "sort_order" => 1,
            "title" => "Hvad er Viva Wallet?",
            "content" => "Viva Wallet er WeePays betalingspartner - en EU-reguleret bank der håndterer alle transaktioner sikkert. Forbindelsen tager kun et par minutter."],

        ["uid" => "faq_m_v2", "type" => "merchant", "category" => "Viva Wallet Integration", "sort_order" => 2,
            "title" => "Hvordan forbinder jeg min Viva-konto?",
            "content" => "Under opsætningen guides du til at oprette eller forbinde en Viva Wallet-konto. Følg trinene, og din konto er forbundet på få minutter."],

        ["uid" => "faq_m_v3", "type" => "merchant", "category" => "Viva Wallet Integration", "sort_order" => 3,
            "title" => "Hvorfor kræves Viva Wallet?",
            "content" => "Viva Wallet sikrer, at alle betalinger håndteres sikkert og i overensstemmelse med EU-regler. Det er også her dine penge indsættes."],

        // Rapporter & Analytics
        ["uid" => "faq_m_a1", "type" => "merchant", "category" => "Rapporter og Analytics", "sort_order" => 1,
            "title" => "Hvilke rapporter kan jeg se?",
            "content" => "Dit dashboard viser:<ul><li>Daglige, ugentlige og månedlige salgstal</li><li>Antal transaktioner og gennemsnitlig ordreværdi</li><li>Betalingsplan-fordeling</li><li>Afventende og forfaldne betalinger</li></ul>"],

        ["uid" => "faq_m_a2", "type" => "merchant", "category" => "Rapporter og Analytics", "sort_order" => 2,
            "title" => "Kan jeg eksportere data?",
            "content" => "Ja. Du kan eksportere transaktionsdata til CSV for brug i regnskabsprogrammer eller egen analyse."],
    ];

    protected static array $requiredRowsTesting = [];

    public static array $encodeColumns = [];
    public static array $encryptedColumns = [];

    public static function foreignkeys(): array {
        return [];
    }

}
