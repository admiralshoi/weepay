<?php

namespace routing\routes\merchants;

use classes\app\LocationPermissions;
use classes\app\OrganisationPermissions;
use classes\enumerations\Links;
use classes\lang\Translate;
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
        $qrWithLogo = Methods::qr()->buildWithLogo($link);

        Response()->mimeType($qrWithLogo['image'], $qrWithLogo['mimeType']);
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
        Response()->setRedirect()->jsonSuccess(Translate::word("Organisationsdetaljerne") . " er blevet opdateret");
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

        Response()->setRedirect(__url(Links::$merchant->organisation->home))->jsonSuccess('Din ' . Translate::word('organisation') . ' er blevet oprettet');
    }


    #[NoReturn] public static function createVivaConnectedAccount(array $args): void {
        $organisationId = __oUuid();
        if(empty($organisationId))
            Response()->jsonError("Ugyldig " . Translate::word("organisation") . ". Venligst vælg en " . Translate::word("organisation") . ".", [], 400);
        if(!OrganisationPermissions::__oModify("billing", "wallet"))
            Response()->jsonError("Du har ikke tilladelse til denne handling.", [], 401);

        $handler = Methods::organisations();
        $organisation = $handler->get($organisationId);
        if(isEmpty($organisation))
            Response()->jsonError("Ugyldig " . Translate::word("organisation") . ". Venligst vælg en " . Translate::word("organisation") . ".", [], 400);
        if(isEmpty($organisation->primary_email))
            Response()->jsonError(ucfirst(Translate::word("Organisationen")) . " har ingen primær email. Venligst tilføj en først.", [], 400);

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
            Response()->jsonError("Ugyldig " . Translate::word("organisation") . ". Venligst vælg en " . Translate::word("organisation") . ".", [], 400);
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
            Response()->jsonError("Ugyldig " . Translate::word("organisation") . ". Venligst vælg en " . Translate::word("organisation") . " for at tilføje lokationen til");
        if(empty(Settings::$organisation->organisation->merchant_prid))
            Response()->jsonError(ucfirst(Translate::word("Organisationen")) . " er endnu ikke tilknyttet en VIVA wallet. Tilknyt en wallet før du opretter lokationer");

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
                    Response()->jsonError("Venligst udfyld pågældende felt, hvis du har slået brug af " . Translate::word("organisations") . "-detaljerne fra", ['blame_field' => $key]);
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

        // Check if slug is reserved
        $reservedNames = toArray(Settings::$app->reserved_names ?? []);
        if(in_array(strtolower($slug), $reservedNames)) {
            Response()->jsonError("Kaldenavnet '$slug' er reserveret", ['blame_field' => 'slug']);
        }

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
            Response()->jsonError("Det var ikke muligt at oprette forbindelse til " . Translate::word("organisations") . " VIVA wallet. Prøv igen senere.");
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

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            Response()->jsonError("Du har ikke adgang til denne lokation", [], 403);
        }

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

        // Check if slug is reserved (only if changing)
        if($slug !== $location->slug) {
            $reservedNames = toArray(Settings::$app->reserved_names ?? []);
            if(in_array(strtolower($slug), $reservedNames)) {
                Response()->jsonError("Kaldenavnet '$slug' er reserveret", ['blame_field' => 'slug']);
            }
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

        // Redirect to potentially updated slug URL
        $redirectUrl = __url(Links::$merchant->locations->setSingleLocation($slug));
        Response()->setRedirect($redirectUrl)->jsonSuccess("Lokationsdetaljerne er blevet opdateret");
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

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            Response()->jsonError("Du har ikke adgang til denne lokation", [], 403);
        }

        $organisationId = $location->uuid->uid;
        if(!OrganisationPermissions::__oModify("locations", "terminals"))
            Response()->jsonError("Unauthorized action", [], 401);

        if($handler->exists(['name' => $name, "location" => $locationId])) Response()->jsonError("Terminalnavnet '$name' er allerede i brug.", ['blame_field' => 'name']);

        $terminalId = $handler->insert($organisationId, $name, $locationId, $status);
        if(empty($terminalId)) Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere");

        //create qr

        Response()->setRedirect()->jsonSuccess('Terminalen er blevet oprettet');
    }


    #[NoReturn] public static function updateWhitelistEnabled(array $args): void {
        if(!OrganisationPermissions::__oModify("organisation", "settings"))
            Response()->jsonPermissionError("modify", 'organisationsindstillinger');

        $enabled = (int)($args['enabled'] ?? 0);
        $organisation = Settings::$organisation->organisation;

        $generalSettings = toArray($organisation->general_settings ?? []);
        $generalSettings['whitelist_enabled'] = $enabled === 1;

        Methods::organisations()->update(['general_settings' => $generalSettings], ['uid' => $organisation->uid]);
        Response()->jsonSuccess($enabled ? 'IP whitelist aktiveret' : 'IP whitelist deaktiveret');
    }

    #[NoReturn] public static function addWhitelistIp(array $args): void {
        if(!OrganisationPermissions::__oModify("organisation", "settings"))
            Response()->jsonPermissionError("modify", 'organisationsindstillinger');

        $ip = trim($args['ip'] ?? '');
        if(empty($ip)) Response()->jsonError("Indtast en IP-adresse", [], 400);

        // Validate IP address (IPv4 or IPv6)
        if(!filter_var($ip, FILTER_VALIDATE_IP)) {
            Response()->jsonError("Ugyldig IP-adresse format", [], 400);
        }

        $organisation = Settings::$organisation->organisation;
        $generalSettings = toArray($organisation->general_settings ?? []);
        $whitelistIps = $generalSettings['whitelist_ips'] ?? [];

        // Check if IP already exists
        if(in_array($ip, $whitelistIps)) {
            Response()->jsonError("IP-adressen er allerede tilføjet", [], 400);
        }

        $whitelistIps[] = $ip;
        $generalSettings['whitelist_ips'] = $whitelistIps;

        Methods::organisations()->update(['general_settings' => $generalSettings], ['uid' => $organisation->uid]);
        Response()->jsonSuccess('IP-adresse tilføjet til whitelist');
    }

    #[NoReturn] public static function removeWhitelistIp(array $args): void {
        if(!OrganisationPermissions::__oModify("organisation", "settings"))
            Response()->jsonPermissionError("modify", 'organisationsindstillinger');

        $ip = trim($args['ip'] ?? '');
        if(empty($ip)) Response()->jsonError("Ugyldig IP-adresse", [], 400);

        $organisation = Settings::$organisation->organisation;
        $generalSettings = toArray($organisation->general_settings ?? []);
        $whitelistIps = $generalSettings['whitelist_ips'] ?? [];

        // Remove IP from array
        $whitelistIps = array_values(array_filter($whitelistIps, fn($item) => $item !== $ip));
        $generalSettings['whitelist_ips'] = $whitelistIps;

        Methods::organisations()->update(['general_settings' => $generalSettings], ['uid' => $organisation->uid]);
        Response()->jsonSuccess('IP-adresse fjernet fra whitelist');
    }

    #[NoReturn] public static function updateOrgSettings(array $args): void {
        if(!OrganisationPermissions::__oModify("organisation", "settings"))
            Response()->jsonPermissionError("modify", 'organisationsindstillinger');

        $organisation = Settings::$organisation->organisation;
        $generalSettings = toArray($organisation->general_settings ?? []);

        // Handle max BNPL amount
        $maxBnpl = $args['max_bnpl_amount'] ?? null;
        if(!isEmpty($maxBnpl)) {
            $maxBnpl = (float)$maxBnpl;
            $platformMax = Settings::$app->platform_max_bnpl_amount ?? 50000;

            if($maxBnpl < 0) {
                Response()->jsonError("Beløbet kan ikke være negativt", [], 400);
            }
            if($maxBnpl > $platformMax) {
                Response()->jsonError("Beløbet kan ikke overstige platform maximum: " . number_format($platformMax, 2, ',', '.') . " DKK", [], 400);
            }
            $generalSettings['max_bnpl_amount'] = $maxBnpl;
        } else {
            // Remove the setting to use platform default
            unset($generalSettings['max_bnpl_amount']);
        }

        Methods::organisations()->update(['general_settings' => $generalSettings], ['uid' => $organisation->uid]);
        Response()->jsonSuccess('Indstillinger gemt');
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

        // Check if user has access to this location based on scoped permissions
        $allowedLocationIds = Methods::locations()->userLocationPredicate();
        if(!empty($allowedLocationIds) && !in_array($location->uid, $allowedLocationIds)) {
            Response()->jsonError("Du har ikke adgang til denne lokation", [], 403);
        }
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


    // =====================================================
    // SUPPORT TICKET METHODS
    // =====================================================

    /**
     * Create a new support ticket
     */
    #[NoReturn] public static function supportCreate(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        // Validate required fields
        $onBehalfOf = trim($args['on_behalf_of'] ?? 'personal');
        $category = trim($args['category'] ?? '');
        $subject = trim($args['subject'] ?? '');
        $message = trim($args['message'] ?? '');

        if (!in_array($onBehalfOf, ['personal', 'organisation'])) {
            Response()->jsonError('Ugyldigt valg for "på vegne af"', [], 400);
        }

        if (isEmpty($category)) {
            Response()->jsonError('Vælg venligst en kategori', [], 400);
        }

        if (isEmpty($subject)) {
            Response()->jsonError('Emne er påkrævet', [], 400);
        }

        if (isEmpty($message)) {
            Response()->jsonError('Besked er påkrævet', [], 400);
        }

        if (strlen($subject) > 200) {
            Response()->jsonError('Emne må max være 200 tegn', [], 400);
        }

        if (strlen($message) > 5000) {
            Response()->jsonError('Besked må max være 5000 tegn', [], 400);
        }

        // Get organisation if on behalf of organisation
        $organisationUid = null;
        if ($onBehalfOf === 'organisation') {
            // Check if user has permission to act on behalf of organisation
            if (!\classes\app\OrganisationPermissions::__oRead('organisation', 'settings')) {
                Response()->jsonError('Du har ikke tilladelse til at oprette henvendelser på vegne af virksomheden', [], 403);
            }

            $chosenOrg = \features\Settings::$organisation;
            if ($chosenOrg && !isEmpty($chosenOrg->organisation->uid ?? null)) {
                $organisationUid = $chosenOrg->organisation->uid;
            } else {
                Response()->jsonError('Ingen aktiv virksomhed valgt', [], 400);
            }
        }

        $ticketHandler = Methods::supportTickets();

        $ticketUid = $ticketHandler->createTicket([
            'user' => $userId,
            'type' => 'merchant',
            'on_behalf_of' => $onBehalfOf,
            'organisation' => $organisationUid,
            'category' => $category,
            'subject' => $subject,
            'message' => $message,
            'status' => 'open',
        ]);

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Kunne ikke oprette henvendelse', [], 500);
        }

        // Trigger notification to admin
        try {
            $ticket = $ticketHandler->get($ticketUid);
            $user = Methods::users()->get($userId);
            \classes\notifications\NotificationTriggers::supportTicketCreated($ticket, $user);
        } catch (\Throwable $e) {
            errorLog(['error' => $e->getMessage()], 'support-ticket-notification-error');
        }

        Response()->jsonSuccess('Din henvendelse er oprettet', ['ticket_uid' => $ticketUid]);
    }

    /**
     * Add a reply to an existing ticket
     */
    #[NoReturn] public static function supportReply(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');
        $message = trim($args['message'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        if (isEmpty($message)) {
            Response()->jsonError('Besked er påkrævet', [], 400);
        }

        if (strlen($message) > 5000) {
            Response()->jsonError('Besked må max være 5000 tegn', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->excludeForeignKeys()->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        // Verify ticket belongs to user
        if ($ticket->user !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne henvendelse', [], 403);
        }

        // Can only reply to open tickets
        if ($ticket->status !== 'open') {
            Response()->jsonError('Du kan ikke svare på en lukket henvendelse', [], 400);
        }

        $replyHandler = Methods::supportTicketReplies();
        $replyUid = $replyHandler->addReply($ticketUid, $userId, $message, false);

        if (isEmpty($replyUid)) {
            Response()->jsonError('Kunne ikke tilføje svar', [], 500);
        }

        Response()->jsonSuccess('Svar tilføjet', [
            'reply' => [
                'uid' => $replyUid,
                'message' => $message,
                'is_admin' => false,
                'created_at' => date('d/m/Y H:i'),
            ]
        ]);
    }

    /**
     * Close a support ticket
     */
    #[NoReturn] public static function supportClose(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->excludeForeignKeys()->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        // Verify ticket belongs to user
        if ($ticket->user !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne henvendelse', [], 403);
        }

        $success = $ticketHandler->closeTicket($ticketUid, $userId);

        if (!$success) {
            Response()->jsonError('Kunne ikke lukke henvendelse', [], 500);
        }

        Response()->jsonSuccess('Henvendelse lukket');
    }

    /**
     * Reopen a closed support ticket
     */
    #[NoReturn] public static function supportReopen(array $args): void {
        $userId = __uuid();

        if (isEmpty($userId)) {
            Response()->jsonError('Du skal være logget ind', [], 401);
        }

        $ticketUid = trim($args['ticket_uid'] ?? '');

        if (isEmpty($ticketUid)) {
            Response()->jsonError('Ticket ID mangler', [], 400);
        }

        $ticketHandler = Methods::supportTickets();
        $ticket = $ticketHandler->excludeForeignKeys()->get($ticketUid);

        if (isEmpty($ticket)) {
            Response()->jsonError('Henvendelse ikke fundet', [], 404);
        }

        // Verify ticket belongs to user
        if ($ticket->user !== $userId) {
            Response()->jsonError('Du har ikke adgang til denne henvendelse', [], 403);
        }

        $success = $ticketHandler->reopenTicket($ticketUid);

        if (!$success) {
            Response()->jsonError('Kunne ikke genåbne henvendelse', [], 500);
        }

        Response()->jsonSuccess('Henvendelse genåbnet');
    }


    // =====================================================
    // REQUIRES ATTENTION NOTIFICATIONS
    // =====================================================

    /**
     * Get unresolved notifications for the current organisation
     */
    #[NoReturn] public static function getAttentionNotifications(array $args): void {
        $organisationId = __oid();
        if (isEmpty($organisationId)) {
            Response()->jsonError('Ingen organisation valgt', [], 400);
        }

        $notifications = Methods::requiresAttentionNotifications()->getUnresolved('merchant', $organisationId);

        Response()->jsonSuccess('', [
            'notifications' => $notifications->toArray(),
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Mark a notification as resolved
     */
    #[NoReturn] public static function resolveAttentionNotification(array $args): void {
        $notificationUid = $args['uid'] ?? null;
        if (isEmpty($notificationUid)) {
            Response()->jsonError('Mangler notification ID', [], 400);
        }

        $organisationId = __oid();
        if (isEmpty($organisationId)) {
            Response()->jsonError('Ingen organisation valgt', [], 400);
        }

        $handler = Methods::requiresAttentionNotifications();
        $notification = $handler->get($notificationUid);

        if (isEmpty($notification)) {
            Response()->jsonError('Notification ikke fundet', [], 404);
        }

        // Verify notification belongs to current organisation
        $notificationOrgId = is_object($notification->organisation) ? $notification->organisation->uid : $notification->organisation;
        if ($notificationOrgId !== $organisationId) {
            Response()->jsonError('Du har ikke adgang til denne notification', [], 403);
        }

        // Mark as resolved
        $success = $handler->markResolved($notificationUid, __uuid());

        if (!$success) {
            Response()->jsonError('Kunne ikke markere som løst', [], 500);
        }

        Response()->jsonSuccess('Notification markeret som løst');
    }


    // =====================================================
    // PENDING VALIDATION REFUNDS
    // =====================================================

    /**
     * Get pending validation refunds for the current organisation
     */
    #[NoReturn] public static function getPendingValidationRefunds(array $args): void {
        $organisationId = __oid();
        if (isEmpty($organisationId)) {
            Response()->jsonError('Ingen organisation valgt', [], 400);
        }

        // Get location scope if user has limited location access
        $locationIds = Methods::locations()->userLocationPredicate();

        $pendingRefunds = Methods::pendingValidationRefunds()->getPendingForOrganisation($organisationId, $locationIds);

        // Format for response
        $refunds = [];
        foreach ($pendingRefunds as $refund) {
            $locationName = is_object($refund->location) ? $refund->location->name : null;
            $orderObj = is_object($refund->order) ? $refund->order : null;

            $refunds[] = [
                'uid' => $refund->uid,
                'order_uid' => is_object($refund->order) ? $refund->order->uid : $refund->order,
                'order_name' => $orderObj?->name ?? null,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'location_name' => $locationName,
                'failure_reason' => $refund->failure_reason,
                'created_at' => $refund->created_at,
                'test' => (bool)$refund->test,
            ];
        }

        Response()->jsonSuccess('', [
            'refunds' => $refunds,
            'count' => count($refunds),
        ]);
    }

    /**
     * Attempt to refund a pending validation refund via Viva API
     */
    #[NoReturn] public static function attemptPendingRefund(array $args): void {
        $refundUid = $args['uid'] ?? null;
        if (isEmpty($refundUid)) {
            Response()->jsonError('Mangler refund ID', [], 400);
        }

        $organisationId = __oid();
        if (isEmpty($organisationId)) {
            Response()->jsonError('Ingen organisation valgt', [], 400);
        }

        $handler = Methods::pendingValidationRefunds();
        $refund = $handler->get($refundUid);

        if (isEmpty($refund)) {
            Response()->jsonError('Refund ikke fundet', [], 404);
        }

        // Verify refund belongs to current organisation
        $refundOrgId = is_object($refund->organisation) ? $refund->organisation->uid : $refund->organisation;
        if ($refundOrgId !== $organisationId) {
            Response()->jsonError('Du har ikke adgang til denne refund', [], 403);
        }

        // Check location permissions if user has limited location access
        $locationIds = Methods::locations()->userLocationPredicate();
        if (!empty($locationIds)) {
            $refundLocationId = is_object($refund->location) ? $refund->location->uid : $refund->location;
            if (!in_array($refundLocationId, $locationIds)) {
                Response()->jsonError('Du har ikke adgang til denne lokation', [], 403);
            }
        }

        // Check if already refunded
        if ($refund->status === 'REFUNDED') {
            Response()->jsonError('Denne refund er allerede markeret som refunderet', [], 400);
        }

        // Get merchant ID from organisation
        $organisation = Settings::$organisation->organisation ?? Methods::organisations()->get($organisationId);
        if (isEmpty($organisation) || isEmpty($organisation->merchant_prid)) {
            Response()->jsonError('Organisation mangler Viva merchant ID', [], 400);
        }

        // Attempt the refund via Viva
        $viva = Methods::viva();
        if (!$refund->test) {
            $viva->live();
        }

        $transactionId = $refund->prid;
        if (isEmpty($transactionId)) {
            Response()->jsonError('Mangler transaktions-ID for refund', [], 400);
        }

        $refundResult = $viva->refundTransaction(
            $organisation->merchant_prid,
            $transactionId,
            $refund->amount,
            null,
            $refund->currency
        );

        if (empty($refundResult) || !isset($refundResult['TransactionId'])) {
            $errorMsg = $refundResult['ErrorText'] ?? $refundResult['message'] ?? 'Refundering fejlede';
            Response()->jsonError($errorMsg, ['viva_response' => $refundResult], 400);
        }

        // Mark as refunded
        $handler->markAsRefunded($refundUid, __uuid());

        Response()->jsonSuccess('Refundering gennemført', [
            'refund_transaction_id' => $refundResult['TransactionId'],
        ]);
    }

    /**
     * Mark a pending validation refund as manually refunded
     */
    #[NoReturn] public static function markPendingRefundAsRefunded(array $args): void {
        $refundUid = $args['uid'] ?? null;
        if (isEmpty($refundUid)) {
            Response()->jsonError('Mangler refund ID', [], 400);
        }

        $organisationId = __oid();
        if (isEmpty($organisationId)) {
            Response()->jsonError('Ingen organisation valgt', [], 400);
        }

        $handler = Methods::pendingValidationRefunds();
        $refund = $handler->get($refundUid);

        if (isEmpty($refund)) {
            Response()->jsonError('Refund ikke fundet', [], 404);
        }

        // Verify refund belongs to current organisation
        $refundOrgId = is_object($refund->organisation) ? $refund->organisation->uid : $refund->organisation;
        if ($refundOrgId !== $organisationId) {
            Response()->jsonError('Du har ikke adgang til denne refund', [], 403);
        }

        // Check location permissions if user has limited location access
        $locationIds = Methods::locations()->userLocationPredicate();
        if (!empty($locationIds)) {
            $refundLocationId = is_object($refund->location) ? $refund->location->uid : $refund->location;
            if (!in_array($refundLocationId, $locationIds)) {
                Response()->jsonError('Du har ikke adgang til denne lokation', [], 403);
            }
        }

        // Check if already refunded
        if ($refund->status === 'REFUNDED') {
            Response()->jsonError('Denne refund er allerede markeret som refunderet', [], 400);
        }

        // Mark as refunded
        $success = $handler->markAsRefunded($refundUid, __uuid());

        if (!$success) {
            Response()->jsonError('Kunne ikke markere som refunderet', [], 500);
        }

        Response()->jsonSuccess('Refund markeret som refunderet');
    }

}