<?php

namespace routing\routes\merchants;

use classes\app\LocationPermissions;
use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\Methods;
use classes\organisations\MemberEnum;
use classes\utility\Numbers;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class ApiController {


    #[NoReturn] public static function getTerminalQrBytes(array $args): void {
        $terminalId = $args["id"];
        $terminal = Methods::terminals()->get($terminalId);
        if(isEmpty($terminal)) Response()->jsonError("Invalid terminal", [], 404);
        if($terminal->status !== 'ACTIVE') Response()->jsonError("The terminal is not active", [], 403);
        if($terminal->location->status !== 'ACTIVE') Response()->jsonError("The location is not active", [], 403);

        $link = __url(Links::$merchant->terminals->checkoutStart($terminal->location->slug, $terminal->uid));
        $qrGenerator = Methods::qr()->build($link)->get();


        Response()->mimeType($qrGenerator->getString(), $qrGenerator->getMimeType());
    }


    public static function selectOrganisation(array $args): mixed {
        $uid = $args["uid"];
        if(isEmpty(Methods::organisations()->get($uid))) return ['return_as' => 404];
        if(!Methods::organisationMembers()->exists(["uuid" => __uuid(), "organisation" => $uid])) return ['return_as' => 404];
        Methods::organisations()->setChosenOrganisation($uid);
        Response()->redirect(Links::$merchant->organisation->home);
    }



    #[NoReturn] public static function updateBasicDetails(array $args): void {
        $handler = Methods::organisations();
        $organisationId = __oid();
        if(!$handler->hasModifyPermissions($organisationId, 'organisation', 'settings'))
            Response()->jsonPermissionError("modify", 'organisationsdetaljer');
        $organisation = Methods::organisations()->get(__oid());

        $requiredKeys = ["name", "email", "company_name", "company_cvr", "company_line_1", "company_city", "company_postal_code", "company_country"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        $name = Titles::cleanUcAll(trim($args["name"]));
        $companyName = Titles::cleanUcAll(trim($args["company_name"]));
        $primaryEmail = trim($args["email"]);
        $companyCvr = trim($args["company_cvr"]);
        $companyLine1 = trim($args["company_line_1"]);
        $companyCity = trim($args["company_city"]);
        $companyPostalCode = trim($args["company_postal_code"]);
        $companyCountry = trim($args["company_country"]);
        $defaultCurrency = trim($args["default_currency"]);
        $website = array_key_exists('website', $args) ? trim($args["website"]) : null;
        $industry = array_key_exists('industry', $args) ? trim($args["industry"]) : null;
        $contactEmail = array_key_exists('contact_email', $args) ? trim($args["contact_email"]) : null;
        $contactPhone = array_key_exists('contact_phone', $args) ? trim($args["contact_phone"]) : null;
        $contactPhoneCountry = array_key_exists('contact_phone_country', $args) ? trim($args["contact_phone_country"]) : null;
        $description = array_key_exists('description', $args) ? ucfirst(trim($args["description"])) : null;

//        if(!empty($website) && !filter_var($website, FILTER_VALIDATE_URL))
//            Response()->jsonError("Forkert hjemmeside-format.", ['blame_field' => 'website']);
        if(!filter_var($primaryEmail, FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Den primære email har et forkert format.", ['blame_field' => 'email']);
        if(!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Forkert email-format.", ['blame_field' => 'contact_email']);
        if(!in_array($defaultCurrency, toArray(Settings::$app->currencies)))
            Response()->jsonError("Ugyldig valuta.", ['blame_field' => 'default_currency']);

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        if(strlen($companyName) > 50) Response()->jsonError("Virksomhedsnavnet er for langt. Maximum 50 tegn", ['blame_field' => 'company_name']);
        if(strlen($companyLine1) > 100) Response()->jsonError("Virksomhedens vejnavn er for lang. Maximum 100 tegn", ['blame_field' => 'company_line_1']);
        if(strlen($companyCity) > 50) Response()->jsonError("Virksomhedens bynavn er for lang. Maximum 50 tegn", ['blame_field' => 'company_city']);
        if(strlen($companyPostalCode) > 30) Response()->jsonError("Virksomhedens postnummer er for langt. Maximum 30 tegn", ['blame_field' => 'company_postal_code']);
        if(strlen($website ?? '') > 50) Response()->jsonError("Hjemmesiden er for lang. Maximum 50 tegn", ['blame_field' => 'website']);
        if(strlen($contactEmail ?? '') > 50) Response()->jsonError("Kontakt e-mailen er for lang. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($primaryEmail) > 50) Response()->jsonError("Den primære e-mail er for lang. Maximum 50 tegn", ['blame_field' => 'email']);
        if(strlen($contactPhone ?? '') > 15) Response()->jsonError("Kontaktnummeret er for langt. Maximum 15 tegn", ['blame_field' => 'contact_phone']);
        if(strlen($industry ?? '') > 30) Response()->jsonError("Industrinavnet er for langt. Maximum 30 tegn", ['blame_field' => 'industry']);
        if(strlen($companyCvr) > 20) Response()->jsonError("Cvr nummeret er for langt. Maximum 20 tegn", ['blame_field' => 'company_cvr']);
        if(strlen($description ?? '') > 1000) Response()->jsonError("Beskrivelsen er for lang. Maximum 1000 tegn", ['blame_field' => 'description']);

        if(!empty($contactPhone)) {
            if(empty($contactPhoneCountry)) $contactPhoneCountry = Settings::$app->default_country;
            $calleInfo = Methods::misc()::callerCode($contactPhoneCountry,  false);
            if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'contact_phone_country']);

            $callerCode = $calleInfo['phone'];
            $phoneLength = $calleInfo['phoneLength'];
            $contactPhone = Numbers::cleanPhoneNumber($contactPhone, false, $phoneLength, $callerCode);
            if(empty($contactPhone)) Response()->jsonError("Udgyldigt kontakt-telefonnummer angivet");
        }

        $country = Methods::countries()->getByCountryCode($companyCountry, ['uid', 'enabled']);
        if(isEmpty($country) || !$country->enabled) Response()->jsonError("Ugyldigt land valgt.", ['blame_field' => 'company_country']);

        $params = [];
        $address = isEmpty($organisation->company_address) ? [] : toArray($organisation->company_address);
        if($name !== $organisation->name) $params['name'] = $name;
        if($companyName !== $organisation->company_name) $params['company_name'] = $companyName;
        if($companyCvr !== $organisation->cvr) $params['cvr'] = $companyCvr;
        if($website !== $organisation->website) $params['website'] = $website;
        if($contactEmail !== $organisation->contact_email) $params['contact_email'] = $contactEmail;
        if($primaryEmail !== $organisation->primary_email) $params['primary_email'] = $primaryEmail;

        if($contactPhone !== $organisation->contact_phone) $params['contact_phone'] = $contactPhone;
        if($industry !== $organisation->industry) $params['industry'] = $industry;
        if($description !== $organisation->description) $params['description'] = $description;
        if($country->uid !== $organisation->country) $params['country'] = $country->uid;
        if($contactPhoneCountry !== $organisation->contact_phone_country_code) $params['contact_phone_country_code'] = $contactPhoneCountry;
        if($defaultCurrency !== $organisation->default_currency) $params['default_currency'] = $defaultCurrency;
        if($companyLine1 !== $organisation->company_address?->line_1) $address['line_1'] = $companyLine1;
        if($companyCity !== $organisation->company_address?->city) $address['city'] = $companyCity;
        if($companyPostalCode !== $organisation->company_address?->postal_code) $address['postal_code'] = $companyPostalCode;
        if($companyCountry !== $organisation->company_address?->country) $address['country'] = $companyCountry;
        $params['company_address'] = $address;

        Methods::organisations()->updateOrganisationDetails($organisationId, $params);
        Response()->setRedirect()->jsonSuccess("Organisationsdetaljerne er blevet opdateret");
    }

    #[NoReturn] public static function createOrganisation(array $args): void {
        $requiredKeys = ["name", "email", "company_name", "company_cvr", "company_line_1", "company_city", "company_postal_code", "company_country"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        $name = Titles::cleanUcAll(trim($args["name"]));
        $companyName = Titles::cleanUcAll(trim($args["company_name"]));
        $primaryEmail = trim($args["email"]);
        $companyCvr = trim($args["company_cvr"]);
        $companyLine1 = trim($args["company_line_1"]);
        $companyCity = trim($args["company_city"]);
        $companyPostalCode = trim($args["company_postal_code"]);
        $companyCountry = trim($args["company_country"]);
        $website = array_key_exists('website', $args) ? trim($args["website"]) : null;
        $industry = array_key_exists('industry', $args) ? trim($args["industry"]) : null;
        $contactEmail = array_key_exists('contact_email', $args) ? trim($args["contact_email"]) : null;
        $contactPhone = array_key_exists('contact_phone', $args) ? trim($args["contact_phone"]) : null;
        $contactPhoneCountry = array_key_exists('contact_phone_country', $args) ? trim($args["contact_phone_country"]) : null;
        $description = array_key_exists('description', $args) ? ucfirst(trim($args["description"])) : null;
        $defaultCurrency = array_key_exists('default_currency', $args) ? trim($args["default_currency"]) : null;

        if(!empty($defaultCurrency) && !in_array($defaultCurrency, toArray(Settings::$app->currencies)))
            Response()->jsonError("Ugyldig valuta valgt.", ['blame_field' => 'default_currency']);
//        if(!empty($website) && !filter_var($website, FILTER_VALIDATE_URL))
//            Response()->jsonError("Forkert hjemmeside-format.", ['blame_field' => 'website']);
        if(!filter_var($primaryEmail, FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Den primære email har et forkert format.", ['blame_field' => 'email']);
        if(!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Forkert email-format.", ['blame_field' => 'contact_email']);

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        if(strlen($companyName) > 50) Response()->jsonError("Virksomhedsnavnet er for langt. Maximum 50 tegn", ['blame_field' => 'company_name']);
        if(strlen($companyLine1) > 100) Response()->jsonError("Virksomhedens vejnavn er for lang. Maximum 100 tegn", ['blame_field' => 'company_line_1']);
        if(strlen($companyCity) > 50) Response()->jsonError("Virksomhedens bynavn er for lang. Maximum 50 tegn", ['blame_field' => 'company_city']);
        if(strlen($companyPostalCode) > 30) Response()->jsonError("Virksomhedens postnummer er for langt. Maximum 30 tegn", ['blame_field' => 'company_postal_code']);
        if(strlen($website ?? '') > 50) Response()->jsonError("Hjemmesiden er for lang. Maximum 50 tegn", ['blame_field' => 'website']);
        if(strlen($contactEmail ?? '') > 50) Response()->jsonError("Kontakt e-mailen er for lang. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($primaryEmail) > 50) Response()->jsonError("Den primære e-mail er for lang. Maximum 50 tegn", ['blame_field' => 'email']);
        if(strlen($contactPhone ?? '') > 15) Response()->jsonError("Kontaktnummeret er for langt. Maximum 15 tegn", ['blame_field' => 'contact_phone']);
        if(strlen($industry ?? '') > 30) Response()->jsonError("Industrinavnet er for langt. Maximum 30 tegn", ['blame_field' => 'industry']);
        if(strlen($companyCvr) > 20) Response()->jsonError("Cvr nummeret er for langt. Maximum 20 tegn", ['blame_field' => 'company_cvr']);
        if(strlen($description ?? '') > 1000) Response()->jsonError("Beskrivelsen er for lang. Maximum 1000 tegn", ['blame_field' => 'description']);

        if(!empty($contactPhone)) {
            if(empty($contactPhoneCountry)) $contactPhoneCountry = Settings::$app->default_country;
            $calleInfo = Methods::misc()::callerCode($contactPhoneCountry,  false);
            if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'contact_phone_country']);

            $callerCode = $calleInfo['phone'];
            $phoneLength = $calleInfo['phoneLength'];
            $contactPhone = Numbers::cleanPhoneNumber($contactPhone, false, $phoneLength, $callerCode);
            if(empty($contactPhone)) Response()->jsonError("Udgyldigt kontakt-telefonnummer angivet");
        }

        $country = Methods::countries()->getByCountryCode($companyCountry, ['uid', 'enabled', 'code']);
        if(isEmpty($country) || !$country->enabled) Response()->jsonError("Ugyldigt land valgt.", ['blame_field' => 'company_country']);

        $organisationId = Methods::organisations()->createNewOrganisation(
            $name, $companyName, $primaryEmail, $companyCvr, $companyLine1, $companyCity, $companyPostalCode, $country,
            $website, $industry, $description, $contactEmail, $contactPhone, $contactPhoneCountry, $defaultCurrency
        );
        if(empty($organisationId)) Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere");

        Methods::organisationMembers()->createNewMember($organisationId, __uuid(), "owner", MemberEnum::INVITATION_ACCEPTED);
        Methods::organisations()->setChosenOrganisation($organisationId);

        Response()->setRedirect(__url(Links::$merchant->organisation->home))->jsonSuccess('Din organisation er blevet oprettet');
    }


    #[NoReturn] public static function createVivaConnectedAccount(array $args): void {
        $organisationId = __oUuid();
        if(empty($organisationId))
            Response()->jsonError("Ugyldig organisation. Venligst vælg en organisation.", [], 400);
        if(!OrganisationPermissions::__oModify("billing", "wallet"))
            Response()->jsonError("Du har ikke tilladelse til denne handling.", [], 401);

        $handler = Methods::organisations();
        $organisation = $handler->get($organisationId);
        if(isEmpty($organisation))
            Response()->jsonError("Ugyldig organisation. Venligst vælg en organisation.", [], 400);
        if(isEmpty($organisation->primary_email))
            Response()->jsonError("Organisationen har ingen primær email. Venligst tilføj en først.", [], 400);

        $connectedAccountsHandler = Methods::vivaConnectedAccounts();
        $existingAccount = $connectedAccountsHandler->myConnection();
        if(!isEmpty($existingAccount) && $existingAccount->state === 'COMPLETED') {
            if($existingAccount->state === 'COMPLETED')
                Response()->jsonError("Du har allerede en opsat Viva wallet. Hvis du ønsker at ændre den bør du først fjerne den eksisterende.");
        }

        if($existingAccount?->state === 'DRAFT' && $existingAccount->email === $organisation->primary_email) {
            $vivaAccount = Methods::viva()->getConnectedMerchant($existingAccount->prid);
            if(!empty(nestedArray($vivaAccount, ['merchantId']))) $link = VIVA_LOGIN_URL;
            else $link = $existingAccount->link;

            Response()->jsonSuccess('Fortsæt oprettelsen direkte hos Viva.', ['onboarding' => $link]);
        }

        $response = Methods::viva()->createConnectedMerchantAccount($organisation->primary_email);
        $accountId = nestedArray($response, ['accountId']);
        $redirectUrl = nestedArray($response, ['invitation', 'redirectUrl']);
        if(empty($accountId) || empty($redirectUrl)) {
            debugLog($response, 'viva-connected-account-error');
            Response()->jsonError("Der opstod en fejl. Prøv igen senere", [], 500);
        }

        $connectedAccountsHandler->update(['state' => "VOID"], ['organisation' => $organisationId, 'state' => 'DRAFT']);
        if(!$connectedAccountsHandler->insert($organisation->primary_email, $organisationId, $accountId, $redirectUrl))
            Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere", [], 500);

        Response()->jsonSuccess('Fortsæt oprettelsen direkte hos Viva.', ['onboarding' => $redirectUrl]);
    }

    #[NoReturn] public static function vivaWalletStatus(array $args): void {
        $organisationId = __oUuid();
        if(empty($organisationId))
            Response()->jsonError("Ugyldig organisation. Venligst vælg en organisation.", [], 400);
        if(!OrganisationPermissions::__oRead("billing", "wallet"))
            Response()->jsonError("Du har ikke tilladelse til denne handling.", [], 401);


        $connectedAccountsHandler = Methods::vivaConnectedAccounts();
        $existingAccount = $connectedAccountsHandler->excludeForeignKeys()->myConnection();
        if(isEmpty($existingAccount)) Response()->jsonSuccess("Ingen tilknytter Viva  Wallet blev fundet");
        if($existingAccount->state === 'COMPLETED')
            Response()->jsonSuccess("Din Viva Wallet er opsat korrekt og er klar til brug.", $existingAccount);

        $response = Methods::viva()->getConnectedMerchant($existingAccount->prid);
        if(!nestedArray($response, ['verified'], false))
            Response()->jsonSuccess("Din Viva Wallet er under opsætning men endnu ikke færdiggjort", $existingAccount);

        $merchantId = nestedArray($response, ['merchantId']);
        $vat = nestedArray($response, ['vatNumber']);
        $taxNumber = nestedArray($response, ['taxNumber']);
        $legalName = nestedArray($response, ['legalName']);
        $cvr = nestedArray($response, ['registrationNumber']);
        if(empty($cvr) && !empty($vat)) $cvr = $vat;
        if(empty($cvr) && !empty($taxNumber)) $cvr = $taxNumber;



        Methods::organisations()->update(
            ['cvr' => $cvr, "company_name" => $legalName, "merchant_prid" => $merchantId],
            ['uid' => $organisationId]
        );
        $connectedAccountsHandler->update(['state' => 'COMPLETED'], ['uid' => $existingAccount->uid]);

        Response()->jsonSuccess(
            "Din Viva Wallet er blevet opsat korrekt og er nu klar til brug.",
            $connectedAccountsHandler->get($existingAccount->uid)
        );
    }


    #[NoReturn] public static function createLocation(array $args): void {
        $requiredKeys = ["name", "slug", "caption"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);
        $organisationId = __oUuid();
        if(empty($organisationId))
            Response()->jsonError("Ugyldig organisation. Venligst vælg en organisation for at tilføje lokationen til");
        if(empty(Settings::$organisation->organisation->merchant_prid))
            Response()->jsonError("Organisationen er endnu ikke tilknyttet en VIVA wallet. Tilknyt en wallet før du opretter lokationer");

        $name = Titles::cleanUcAll(trim($args["name"]));
        $slug = strtolower(trim($args["slug"]));
        $slug = str_replace([' ', 'ø', 'å', 'æ'], ['-', 'o', 'aa', 'ae'], $slug);
        $slug = preg_replace('/[^a-zA-Z\- ]/', '', $slug);
        $caption = ucfirst(trim($args["caption"]));
        $inheritParent = array_key_exists('inherit_details', $args) ? $args["inherit_details"] : null;
        $inheritParent = $inheritParent === "on" ? 1 : 0;
        $description = array_key_exists('description', $args) ? ucfirst(trim($args["description"])) : null;
        $industry = array_key_exists('industry', $args) ? trim($args["industry"]) : null;
        $contactEmail = array_key_exists('contact_email', $args) ? trim($args["contact_email"]) : null;
        $contactPhone = array_key_exists('contact_phone', $args) ? trim($args["contact_phone"]) : null;
        $contactPhoneCountry = array_key_exists('contact_phone_country', $args) ? trim($args["contact_phone_country"]) : null;
        $cvr = trim($args["cvr"]);
        $line1 = trim($args["line_1"]);
        $city = trim($args["city"]);
        $postalCode = trim($args["postal_code"]);
        $country = trim($args["country"]);
        $defaultCurrency = array_key_exists('default_currency', $args) ? trim($args["default_currency"]) : null;

        if(!empty($defaultCurrency) && !in_array($defaultCurrency, toArray(Settings::$app->currencies)))
            Response()->jsonError("Ugyldig valuta valgt.", ['blame_field' => 'default_currency']);
        if(!empty($contactEmail) && !filter_var($args["contact_email"], FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Forkert email-format.", ['blame_field' => 'contact_email']);

        if(!$inheritParent) {
            foreach (["industry", "contact_email", "contact_phone", "cvr", "line_1", "city", "postal_code", "country"] as $key) {
                if(!array_key_exists($key, $args) || empty(trim($args[$key])))
                    Response()->jsonError("Venligst udfyld pågældende felt, hvis du har slået brug af organisations-detaljerne fra", ['blame_field' => $key]);
            }
        }

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        if(strlen($slug) > 50) Response()->jsonError("Lokationsnavnet er for langt. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($line1) > 100) Response()->jsonError("Lokationens vejnavn er for lang. Maximum 100 tegn", ['blame_field' => 'line_1']);
        if(strlen($city) > 50) Response()->jsonError("Lokationens bynavn er for lang. Maximum 50 tegn", ['blame_field' => 'city']);
        if(strlen($postalCode) > 30) Response()->jsonError("Lokationens postnummer er for langt. Maximum 30 tegn", ['blame_field' => 'postal_code']);
        if(strlen($contactEmail ?? '') > 50) Response()->jsonError("Kontakt e-mailen er for lang. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($contactPhone ?? '') > 15) Response()->jsonError("Kontaktnummeret er for langt. Maximum 15 tegn", ['blame_field' => 'contact_phone']);
        if(strlen($industry ?? '') > 30) Response()->jsonError("Industrinavnet er for langt. Maximum 30 tegn", ['blame_field' => 'industry']);
        if(strlen($cvr) > 20) Response()->jsonError("Cvr nummeret er for langt. Maximum 20 tegn", ['blame_field' => 'cvr']);
        if(strlen($description ?? '') > 1000) Response()->jsonError("Beskrivelsen er for lang. Maximum 1000 tegn", ['blame_field' => 'description']);
        if(strlen($caption) > 250) Response()->jsonError("Underteksten er for lang. Maximum 250 tegn", ['blame_field' => 'caption']);

        if(!empty($contactPhone)) {
            if(empty($contactPhoneCountry)) $contactPhoneCountry = Settings::$app->default_country;
            $calleInfo = Methods::misc()::callerCode($contactPhoneCountry,  false);
            if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'contact_phone_country']);

            $callerCode = $calleInfo['phone'];
            $phoneLength = $calleInfo['phoneLength'];
            $contactPhone = Numbers::cleanPhoneNumber($contactPhone, false, $phoneLength, $callerCode);
            if(empty($contactPhone)) Response()->jsonError("Udgyldigt kontakt-telefonnummer angivet");
        }

        $country = Methods::countries()->getByCountryCode($country, ['uid', 'enabled', 'code']);
        if(isEmpty($country) || !$country->enabled) Response()->jsonError("Ugyldigt land valgt.", ['blame_field' => 'country']);

        $handler = Methods::locations();
        if($handler->exists(['slug' => $slug])) Response()->jsonError("Kaldenavnet '$slug' er allerede i brug", ['blame_field' => 'slug']);

        $locationId = $handler->createNewLocation(
            $organisationId, $name, $slug, $caption, $inheritParent,
            $cvr, $line1, $city, $postalCode, $country, $industry, $description,
            $contactEmail, $contactPhone, $contactPhoneCountry, null, 'ACTIVE', $defaultCurrency
        );
        if(empty($locationId)) Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere");

        $sourceCode = $handler->generateUniqueSourceCode();
        if(!Methods::viva()->createSource(
            Settings::$organisation->organisation->merchant_prid,
            $sourceCode,
            $slug
        )){
            $handler->delete(['uid' => $locationId]);
            Response()->jsonError("Det var ikke muligt at oprette forbindelse til organisations VIVA wallet. Prøv igen senere.");
        }

        $handler->setSource($locationId, $sourceCode);
        Response()->setRedirect(__url(Links::$merchant->locations->main))->jsonSuccess('Lokationen er blevet oprettet');
    }


    #[NoReturn] public static function updateLocationDetails(array $args): void {
        $requiredKeys = ["name", "slug", "caption"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        if(!array_key_exists('location_id', $args) || empty($args['location_id']))
            Response()->jsonError("Ugyldig lokation", [], 400);

        $locationId = $args['location_id'];
        $handler = Methods::locations();
        $location = $handler->get($locationId);

        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet", [], 404);
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling", [], 403);

        $name = Titles::cleanUcAll(trim($args["name"]));
        $slug = strtolower(trim($args["slug"]));
        $slug = str_replace([' ', 'ø', 'å', 'æ'], ['-', 'o', 'aa', 'ae'], $slug);
        $slug = preg_replace('/[^a-zA-Z\- ]/', '', $slug);
        $caption = ucfirst(trim($args["caption"]));
        $inheritParent = array_key_exists('inherit_details', $args) ? $args["inherit_details"] : null;
        $inheritParent = $inheritParent === "on" ? 1 : 0;
        $description = array_key_exists('description', $args) ? ucfirst(trim($args["description"])) : null;
        $industry = array_key_exists('industry', $args) ? trim($args["industry"]) : null;
        $contactEmail = array_key_exists('contact_email', $args) ? trim($args["contact_email"]) : null;
        $contactPhone = array_key_exists('contact_phone', $args) ? trim($args["contact_phone"]) : null;
        $contactPhoneCountry = array_key_exists('contact_phone_country', $args) ? trim($args["contact_phone_country"]) : null;
        $cvr = array_key_exists('cvr', $args) ? trim($args["cvr"]) : null;
        $line1 = array_key_exists('line_1', $args) ? trim($args["line_1"]) : null;
        $city = array_key_exists('city', $args) ? trim($args["city"]) : null;
        $postalCode = array_key_exists('postal_code', $args) ? trim($args["postal_code"]) : null;
        $country = array_key_exists('country', $args) ? trim($args["country"]) : null;
        $defaultCurrency = array_key_exists('default_currency', $args) ? trim($args["default_currency"]) : null;

        if(!empty($defaultCurrency) && !in_array($defaultCurrency, toArray(Settings::$app->currencies)))
            Response()->jsonError("Ugyldig valuta valgt.", ['blame_field' => 'default_currency']);
        if(!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Forkert email-format.", ['blame_field' => 'contact_email']);

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        if(strlen($slug) > 50) Response()->jsonError("Lokationsnavnet er for langt. Maximum 50 tegn", ['blame_field' => 'slug']);
        if(strlen($line1 ?? '') > 100) Response()->jsonError("Lokationens vejnavn er for lang. Maximum 100 tegn", ['blame_field' => 'line_1']);
        if(strlen($city ?? '') > 50) Response()->jsonError("Lokationens bynavn er for lang. Maximum 50 tegn", ['blame_field' => 'city']);
        if(strlen($postalCode ?? '') > 30) Response()->jsonError("Lokationens postnummer er for langt. Maximum 30 tegn", ['blame_field' => 'postal_code']);
        if(strlen($contactEmail ?? '') > 50) Response()->jsonError("Kontakt e-mailen er for lang. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($contactPhone ?? '') > 15) Response()->jsonError("Kontaktnummeret er for langt. Maximum 15 tegn", ['blame_field' => 'contact_phone']);
        if(strlen($industry ?? '') > 30) Response()->jsonError("Industrinavnet er for langt. Maximum 30 tegn", ['blame_field' => 'industry']);
        if(strlen($cvr ?? '') > 20) Response()->jsonError("Cvr nummeret er for langt. Maximum 20 tegn", ['blame_field' => 'cvr']);
        if(strlen($description ?? '') > 1000) Response()->jsonError("Beskrivelsen er for lang. Maximum 1000 tegn", ['blame_field' => 'description']);
        if(strlen($caption) > 250) Response()->jsonError("Underteksten er for lang. Maximum 250 tegn", ['blame_field' => 'caption']);

        if(!empty($contactPhone)) {
            if(empty($contactPhoneCountry)) $contactPhoneCountry = Settings::$app->default_country;
            $calleInfo = Methods::misc()::callerCode($contactPhoneCountry, false);
            if(empty($calleInfo)) Response()->jsonError("Forkert landekode angivet til telefonnummeret", ['blame_field' => 'contact_phone_country']);

            $callerCode = $calleInfo['phone'];
            $phoneLength = $calleInfo['phoneLength'];
            $contactPhone = Numbers::cleanPhoneNumber($contactPhone, false, $phoneLength, $callerCode);
            if(empty($contactPhone)) Response()->jsonError("Udgyldigt kontakt-telefonnummer angivet");
        }

        if(!empty($country)) {
            $countryObj = Methods::countries()->getByCountryCode($country, ['uid', 'enabled', 'code']);
            if(isEmpty($countryObj) || !$countryObj->enabled) Response()->jsonError("Ugyldigt land valgt.", ['blame_field' => 'country']);
        } else {
            $countryObj = null;
        }

        // Check slug uniqueness (excluding current location)
        if($slug !== $location->slug && $handler->exists(['slug' => $slug]))
            Response()->jsonError("Kaldenavnet '$slug' er allerede i brug", ['blame_field' => 'slug']);

        $params = [];
        $address = isEmpty($location->address) ? [] : toArray($location->address);

        if($name !== $location->name) $params['name'] = $name;
        if($slug !== $location->slug) $params['slug'] = $slug;
        if($caption !== $location->caption) $params['caption'] = $caption;
        if($inheritParent !== $location->inherit_details) $params['inherit_details'] = $inheritParent;
        if($description !== $location->description) $params['description'] = $description;
        if($industry !== $location->industry) $params['industry'] = $industry;
        if($contactEmail !== $location->contact_email) $params['contact_email'] = $contactEmail;
        if($contactPhone !== $location->contact_phone) $params['contact_phone'] = $contactPhone;
        if($contactPhoneCountry !== $location->contact_phone_country_code) $params['contact_phone_country_code'] = $contactPhoneCountry;
        if($cvr !== $location->cvr) $params['cvr'] = $cvr;
        if($defaultCurrency !== $location->default_currency) $params['default_currency'] = $defaultCurrency;
        if(!empty($countryObj) && $countryObj->uid !== $location->country) $params['country'] = $countryObj->uid;

        if($line1 !== $location->address?->line_1) $address['line_1'] = $line1;
        if($city !== $location->address?->city) $address['city'] = $city;
        if($postalCode !== $location->address?->postal_code) $address['postal_code'] = $postalCode;
        if(!empty($countryObj) && $countryObj->code !== $location->address?->country) $address['country'] = $countryObj->code;
        if(!isEmpty($address)) $params['address'] = $address;

        if(!isEmpty($params)) {
            $handler->update($params, ['uid' => $locationId]);
        }

        Response()->setRedirect()->jsonSuccess("Lokationsdetaljerne er blevet opdateret");
    }


    #[NoReturn] public static function createTerminal(array $args): void {
        $requiredKeys = ["name", "location"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        $locationHandler = Methods::locations();
        $handler = Methods::terminals();

        $name = Titles::cleanUcAll(trim($args["name"]));
        $locationId = trim($args["location"]);
        $status = array_key_exists('status', $args) ? $args["status"] : null;
        $status = $status === "on" ? "ACTIVE" : "DRAFT";

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        $location  = $locationHandler->get($locationId);
        if(isEmpty($location)) Response()->jsonError("Ugyldig lokationr", ['blame_field' => 'location'], 400);

        $organisationId = $location->uuid->uid;
        if(!OrganisationPermissions::__oModify("locations", "terminals"))
            Response()->jsonError("Unauthorized action", [], 401);

        if($handler->exists(['name' => $name, "location" => $locationId])) Response()->jsonError("Terminalnavnet '$name' er allerede i brug.", ['blame_field' => 'name']);

        $terminalId = $handler->insert($organisationId, $name, $locationId, $status);
        if(empty($terminalId)) Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere");

        //create qr

        Response()->setRedirect()->jsonSuccess('Terminalen er blevet oprettet');
    }


    #[NoReturn] public static function updateTerminalDetails(array $args): void {
        $requiredKeys = ["name", "status"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        if(!array_key_exists('terminal_id', $args) || empty($args['terminal_id']))
            Response()->jsonError("Ugyldig terminal", [], 400);

        $terminalId = $args['terminal_id'];
        $handler = Methods::terminals();
        $terminal = $handler->get($terminalId);

        if(isEmpty($terminal)) Response()->jsonError("Terminal ikke fundet", [], 404);
        if($terminal->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling", [], 403);

        // Check location permissions
        $location = $terminal->location;
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet", [], 404);
        if(!LocationPermissions::__oModify($location, 'terminals'))
            Response()->jsonError("Du har ikke tilladelse til at redigere denne terminal", [], 403);

        $name = Titles::cleanUcAll(trim($args["name"]));
        $status = trim($args["status"]);

        // Validate status
        $allowedStatuses = ['DRAFT', 'ACTIVE', 'INACTIVE', 'DELETED'];
        if(!in_array($status, $allowedStatuses))
            Response()->jsonError("Ugyldig status valgt.", ['blame_field' => 'status']);

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);

        // Check name uniqueness (excluding current terminal)
        if($name !== $terminal->name && $handler->exists(['name' => $name, 'location' => $location->uid]))
            Response()->jsonError("Terminalnavnet '$name' er allerede i brug.", ['blame_field' => 'name']);

        $params = [];
        if($name !== $terminal->name) $params['name'] = $name;
        if($status !== $terminal->status) $params['status'] = $status;

        if(!isEmpty($params)) {
            $handler->update($params, ['uid' => $terminalId]);
        }

        Response()->setRedirect()->jsonSuccess("Terminaldetaljerne er blevet opdateret");
    }





    #[NoReturn] public static function updateTeamMember(array $args): void {
        foreach (["action", "role", "member_uuid"] as $key)
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) Response()->jsonError("Der mangler påkrævede felter.");

        $role = trim($args["role"]);
        $action = trim($args["action"]);
        $uuid = trim($args["member_uuid"]);

        if($uuid === __uuid()) Response()->jsonError("Du kan ikke lave ændringer til din egen konto.");
        if(isEmpty(Settings::$organisation)) Response()->jsonError("Du er ikke medlem af nogen aktiv organisation.");

        $organisationId = Settings::$organisation->organisation->uid;
        $member = Methods::organisationMembers()->getMember($organisationId, $uuid);
        if(isEmpty($member)) Response()->jsonError("Denne bruger er ikke medlem af denne organisation.");

        $user = Methods::users()->get($uuid, ['uid', "access_level"]);
        if(isEmpty($user)) Response()->jsonError("Denne bruger eksisterer ikke, eller så har du ikke tilladelse til at se den.");
        if(!property_exists(Settings::$organisation->organisation->permissions, $role)) Response()->jsonError("Ugyldig rolle.");


        switch ($action) {
            default: Response()->jsonError("Ugyldig handling.");
            case "unsuspend":
                if(!OrganisationPermissions::__oModify('team', 'members')) Response()->jsonPermissionError("redigerings", 'medlemmer');
                if($member->status !== MemberEnum::MEMBER_SUSPENDED) Response()->jsonSuccess("Ingen ændringer at foretage.");
                Methods::organisationMembers()->updateMemberDetails($organisationId, $uuid, [
                    "status" => MemberEnum::MEMBER_ACTIVE,
                    "change_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::MEMBER_UNSUSPENDED)
                ]);
                $responseMessage = "Suspenderingen fra dette medlem er blevet fjernet.";
                break;
            case "suspend":
                if(!OrganisationPermissions::__oDelete('team', 'members')) Response()->jsonPermissionError("slette", 'medlemsinvitationer');
                if($member->status === MemberEnum::MEMBER_SUSPENDED) Response()->jsonSuccess("Ingen ændringer at foretage.");
                Methods::organisationMembers()->updateMemberDetails($organisationId, $uuid, [
                    "status" => MemberEnum::MEMBER_SUSPENDED,
                    "change_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::MEMBER_SUSPENDED)
                ]);
                $responseMessage = "Medlemmet er blevet suspenderet.";
                break;
            case "resend-invitation":
                if(!OrganisationPermissions::__oModify('team', 'invitations')) Response()->jsonPermissionError("redigerings", 'medlemsinvitationer');
                if($member->invitation_status === MemberEnum::INVITATION_ACCEPTED)
                    Response()->jsonSuccess("Brugeren har allerede accepteret invitationen.");
                $params = [
                    "invitation_status" => MemberEnum::INVITATION_PENDING,
                    "status" => MemberEnum::MEMBER_ACTIVE,
                    "invitation_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::INVITATION_RESEND)
                ];
                if($member->status !== MemberEnum::MEMBER_ACTIVE)
                    $params['change_activity'] = Methods::organisationMembers()->getEventDetails(MemberEnum::ROLE_CHANGE, "", ["role" => $member->role]);
                Methods::organisationMembers()->updateMemberDetails($organisationId, $uuid, $params);
                $responseMessage = "Invitationen er blevet gensendt og medlemmets status er blevet sat til 'aktiv'";

                //Send some notification?
                break;
            case "update-role":
                if(!OrganisationPermissions::__oModify('team', 'roles')) Response()->jsonPermissionError("redigerings", 'medlemmer');
                if($member->role === $role) Response()->jsonSuccess("Medlemmet har allerede denne rolle.");
                Methods::organisationMembers()->updateMemberDetails($organisationId, $uuid, [
                    "role" => $role,
                    "change_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::ROLE_CHANGE, "", ["role" => $member->role])
                ]);
                $responseMessage = "Medlemmets rolle er blevet opdateret til " . Titles::cleanUcAll($role) . ".";

                break;
            case "retract-invitation":
                if(!OrganisationPermissions::__oModify('team', 'invitations')) Response()->jsonPermissionError("redigerings", 'medlemsinvitationer');
                if($member->invitation_status !== MemberEnum::INVITATION_PENDING)
                    Response()->jsonSuccess("Du kan ikke trække en invitation tilbage som ikke afventer godkendelse.", ["refresh" => false]);
                Methods::organisationMembers()->updateMemberDetails($organisationId, $uuid, [
                    "invitation_status" => MemberEnum::INVITATION_RETRACTED,
                    "invitation_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::INVITATION_RETRACTED)
                ]);
                $responseMessage = "Medlemsinvitationen er blevet trukket tilbage.";

                break;
        }

        Response()->setRedirect()->jsonSuccess($responseMessage);
    }




    #[NoReturn] public static function updateRolePermissions(array $args): void {
        $basePermissions = Methods::organisations()::BASE_PERMISSIONS;
        if(!array_key_exists("role", $args)) Response()->jsonError("Missing role.");
        $role = $args["role"];
        unset($args["role"]);
        $organisation = Methods::organisations()->get(__oid());
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig organisation"], 400);
        if(!OrganisationPermissions::__oModify("roles", "permissions")) Response()->jsonPermissionError("redigerings", 'rolletilladelser');

        foreach ($basePermissions as $mainObject => &$mainPermissions) {
            $newMain = array_key_exists($mainObject, $args) ? $args[$mainObject] : [];
            $mainPermissions["read"] = array_key_exists("read", $newMain) && $newMain["read"] === "on";
            $mainPermissions["modify"] = array_key_exists("modify", $newMain) && $newMain["modify"] === "on";
            $mainPermissions["delete"] = array_key_exists("delete", $newMain) && $newMain["delete"] === "on";

            if(!array_key_exists("permissions", $newMain)) $newMain["permissions"] = [];
            foreach ($mainPermissions["permissions"] as $subObject => &$permissions) {
                $newSub = array_key_exists($subObject, $newMain["permissions"]) ? $newMain["permissions"][$subObject] : [];
                $permissions["read"] = $mainPermissions["read"] !== false && array_key_exists("read", $newSub) && $newSub["read"] === "on";
                $permissions["modify"] = $mainPermissions["modify"] !== false && array_key_exists("modify", $newSub) && $newSub["modify"] === "on";
                $permissions["delete"] = $mainPermissions["delete"] !== false && array_key_exists("delete", $newSub) && $newSub["delete"] === "on";
            }
        }

        $organisation->permissions->$role = $basePermissions;
        $params = ["permissions" => toArray($organisation->permissions)];
        Methods::organisations()->updateOrganisationDetails(__oid(), $params);
        Response()->setRedirect()->jsonSuccess("Rollen '" . Titles::cleanUcAll($role) . "'s tilladelser er blevet opdateret.");
    }


    #[NoReturn] public static function createNewRole(array $args): void {
        if(!array_key_exists("role_name", $args) || empty(trim($args["role_name"])))
            Response()->jsonError("Venligst angiv et gyldigt rollenavn");

        $organisation = Methods::organisations()->get(__oid());
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig organisation"], 400);
        if(!OrganisationPermissions::__oModify('roles', 'roles')) Response()->jsonPermissionError("redigere", "organisationsroller");

        $name = Titles::reverseClean(trim($args["role_name"]));
        $permissions = toArray($organisation->permissions);
        if(array_key_exists($name,$permissions)) Response()->jsonError("En rolle med dette navn eksisterer allerede. Prøv et andet navn.");

        $permissions[$name] = Methods::organisations()::BASE_PERMISSIONS;
        $status = Methods::organisations()->updateOrganisationDetails(__oid(), ["permissions" => $permissions]);
        if(!$status) Response()->jsonError("Var ikke i stand til at oprette den nye rolle. Prøv igen senere.");
        Response()->setRedirect()->jsonSuccess('Den nye rolle er blevet oprettet.');
    }




    #[NoReturn] public static function renameRole(array $args): void {
        if(!array_key_exists("role", $args) || empty(trim($args["role"]))) Response()->jsonError("Venligst angiv et gyldigt rollenavn");
        if(!array_key_exists("new_role_name", $args) || empty(trim($args["new_role_name"])))
            Response()->jsonError("Venligst angiv et nyt gyldigt rollenavn");

        $organisation = Methods::organisations()->get(__oid());
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig organisation"], 400);
        if(!OrganisationPermissions::__oModify('roles', 'roles')) Response()->jsonPermissionError("redigerings", "organisationsroller");

        $newName = Titles::reverseClean(trim($args["new_role_name"]));
        $role = trim($args["role"]);
        if($role === "owner") Response()->jsonError("Ejerrollen kan ikke omdøbes.");
        $permissions = toArray($organisation->permissions);
        if(!array_key_exists($role,$permissions)) Response()->jsonError("Bad request", ["reason" => "Ugyldig role"], 400);
        if(array_key_exists($newName,$permissions)) Response()->jsonError("En rolle med dette navn eksisterer allerede. Prøv et andet navn.");

        $permissions = array_combine(
            array_map(function ($k) use($newName, $role) {
                return $k === $role ? $newName : $k;
            }, array_keys($permissions)),
            array_values($permissions)
        );

        $status = Methods::organisations()->updateOrganisationDetails(__oid(), ["permissions" => $permissions]);
        if(!$status) Response()->jsonError("Var ikke i stand til at omdøbe rollen. Prøv igen senere.");
        Methods::organisationMembers()->update(["role" => $newName], ["role" => $role]);

        Response()->setRedirect()->jsonSuccess('Rollen er blevet omdøbt.');
    }

    #[NoReturn] public static function deleteRole(array $args): void {
        if(!array_key_exists("role", $args) || empty(trim($args["role"])))
            Response()->jsonError("Venligst angiv en gyldig rolle", $args);

        $organisation = Methods::organisations()->get(__oid());
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig organisation"], 400);
        if(!OrganisationPermissions::__oDelete('roles', 'roles')) Response()->jsonPermissionError("slette", "organisationsroller");

        $role = trim($args["role"]);
        if($role === "owner") Response()->jsonError("Ejerrollen kan ikke slettes.");
        $permissions = toArray($organisation->permissions);
        if(!array_key_exists($role,$permissions)) Response()->jsonError("Bad request", ["reason" => "Ugyldig role"], 400);

        if(Methods::organisationMembers()->exists(["organisation" => __oid(), "role" => $role]))
            Response()->jsonError("En eller flere medlemmer er tildelt denne rolle. Venligst fjern rollen for alle medlemmer før du sletter den.");

        unset($permissions[$role]);

        $status = Methods::organisations()->updateOrganisationDetails(__oid(), ["permissions" => $permissions]);
        if(!$status) Response()->jsonError("Var ikke i stand til at slette rollen. Prøv igen senere.");
        Response()->setRedirect()->jsonSuccess('Rollen er blevet slettet.');
    }

}