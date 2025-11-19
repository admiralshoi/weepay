<?php

namespace routing\routes\merchants;

use classes\enumerations\Links;
use classes\Methods;
use classes\organisations\MemberEnum;
use classes\utility\Numbers;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class ApiController {


    public static function selectOrganisation(array $args): mixed {
        $uid = $args["uid"];
        if(isEmpty(Methods::organisations()->get($uid))) return ['return_as' => 404];
        if(!Methods::organisationMembers()->exists(["uuid" => __uuid(), "organisation" => $uid])) return ['return_as' => 404];
        Methods::organisations()->setChosenOrganisation($uid);
        Response()->redirect(Links::$merchant->organisation->home);
    }

    #[NoReturn] public static function createOrganisation(array $args): void {
        $requiredKeys = ["name", "company_name", "company_cvr", "company_line_1", "company_city", "company_postal_code", "company_country"];
        foreach ($requiredKeys as $key) if(!array_key_exists($key, $args) || empty(trim($args[$key])))
            Response()->jsonError("Venligst udfyld alle påkrævet felter", ['blame_field' => $key]);

        $name = Titles::cleanUcAll(trim($args["name"]));
        $companyName = Titles::cleanUcAll(trim($args["company_name"]));
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

        if(!empty($website) && !filter_var($args["website"], FILTER_VALIDATE_URL))
            Response()->jsonError("Forkert hjemmeside-format.", ['blame_field' => 'website']);
        if(!empty($contactEmail) && !filter_var($args["contact_email"], FILTER_VALIDATE_EMAIL))
            Response()->jsonError("Forkert email-format.", ['blame_field' => 'contact_email']);

        if(strlen($name) > 30) Response()->jsonError("Navnet er for langt. Maximum 30 tegn", ['blame_field' => 'name']);
        if(strlen($companyName) > 50) Response()->jsonError("Virksomhedsnavnet er for langt. Maximum 50 tegn", ['blame_field' => 'contact_email']);
        if(strlen($companyLine1) > 100) Response()->jsonError("Virksomhedens vejnavn er for lang. Maximum 100 tegn", ['blame_field' => 'company_line_1']);
        if(strlen($companyCity) > 50) Response()->jsonError("Virksomhedens bynavn er for lang. Maximum 50 tegn", ['blame_field' => 'company_city']);
        if(strlen($companyPostalCode) > 30) Response()->jsonError("Virksomhedens postnummer er for langt. Maximum 30 tegn", ['blame_field' => 'company_postal_code']);
        if(strlen($website ?? '') > 50) Response()->jsonError("Hjemmesiden er for lang. Maximum 50 tegn", ['blame_field' => 'website']);
        if(strlen($contactEmail ?? '') > 50) Response()->jsonError("Kontakt e-mailen er for lang. Maximum 50 tegn", ['blame_field' => 'company_name']);
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
            $contactPhone = Numbers::cleanPhoneNumber($contactPhone, true, $phoneLength, $callerCode);
            if(empty($contactPhone)) Response()->jsonError("Udgyldigt kontakt-telefonnummer angivet");
        }

        if(!Methods::countries()->exists(['uid' => $companyCountry, 'enabled' => 1]))
            Response()->jsonError("Ugyldigt land valgt.", ['blame_field' => 'company_country']);

        $organisationId = Methods::organisations()->createNewOrganisation(
            $name, $companyName, $companyCvr, $companyLine1, $companyCity, $companyPostalCode, $companyCountry,
            $website, $industry, $contactEmail, $contactPhone, $description,
        );
        if(empty($organisationId)) Response()->jsonError("Der opstod en fejl under oprettelsen. Prøv igen senere");

        Methods::organisationMembers()->createNewMember($organisationId, __uuid(), "owner", MemberEnum::INVITATION_ACCEPTED);
        Methods::organisations()->setChosenOrganisation($organisationId);

        Response()->setRedirect(__url(Links::$merchant->organisation->home))->jsonSuccess('Din organisation er blevet oprettet');
    }


}