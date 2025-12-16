<?php

namespace routing\routes\merchants;

use classes\app\OrganisationPermissions;
use classes\lang\Translate;
use classes\Methods;
use classes\organisations\MemberEnum;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class OrganisationApiController {



    #[NoReturn] public static function respondToInvitation(array $args): void {
        foreach (["organisation_id", "action"] as $key)
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) Response()->jsonError("Manglende påkrævet felt. Prøv igen senere.");

        $organisationId = $args["organisation_id"];
        $action = trim($args["action"]);
        $member = Methods::organisationMembers()->getMember($organisationId, __uuid());
        if(isEmpty($member)) Response()->jsonError("Ugyldig anmodning", [], 400);
        if($member->invitation_status !== MemberEnum::INVITATION_PENDING) Response()->jsonError("Ugyldig anmodning", [], 400);


        switch ($action) {
            default: Response()->jsonError("Ugyldig anmodning", ['reason' => "Ukendt handling"], 400);
            case "decline":
                Methods::organisationMembers()->updateMemberDetails($organisationId, __uuid(), [
                    "invitation_status" => MemberEnum::INVITATION_DECLINED,
                    "invitation_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::INVITATION_DECLINED)
                ]);
                Response()->jsonSuccess("Invitationen er blevet afvist.");
            case "accept":
                $accepted = Methods::organisationMembers()->updateMemberDetails($organisationId, __uuid(), [
                    "invitation_status" => MemberEnum::INVITATION_ACCEPTED,
                    "invitation_activity" => Methods::organisationMembers()->getEventDetails(MemberEnum::INVITATION_ACCEPTED)
                ]);

                if(!$accepted) Response()->jsonError("Operationen mislykkedes. Prøv igen senere.");
                Methods::organisations()->setChosenOrganisation($organisationId);
                Response()->setRedirect(__url(ORGANISATION_PANEL_PATH))->jsonSuccess("Invitationen er blevet accepteret.");
        }
    }

    #[NoReturn] public static function inviteTeamMember(array $args): void {
        foreach (["role", "email"] as $key)
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) Response()->jsonError("Udfyld venligst feltet $key.");

        $role = trim($args["role"]);
        $email = trim($args["email"]);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) Response()->jsonError("Ugyldig email format.");

        if(isEmpty(Settings::$organisation)) Response()->jsonError("Du er ikke medlem af nogen aktiv " . Translate::word("organisation") . ".");
        if(!__oModify('team', 'members')) Response()->jsonError("Du har ikke tilladelse til at udføre denne handling.");
        if(!property_exists(Settings::$organisation->organisation->permissions, $role)) Response()->jsonError("Ugyldig rolle.");

        $user = Methods::users()->getByEmail($email, ['uid', "access_level"]);
        if(isEmpty($user)) Response()->jsonError("Der findes ingen bruger med denne email.");
        if($user->access_level !== Methods::roles()->accessLevel('merchant'))
            Response()->jsonError("Denne bruger er ikke registreret som en forhandler-konto. Bed brugeren om at oprette en korrekt forhandler-konto og inviter dem igen.");
        if(Methods::organisationMembers()->userIsMember(Settings::$organisation->organisation->uid, $user->uid))
            Response()->jsonError("Denne bruger er allerede en del af dit team.");

        Methods::organisationMembers()->createNewMember(Settings::$organisation->organisation->uid, $user->uid, $role, MemberEnum::INVITATION_PENDING);
        Response()->setRedirect()->jsonSuccess('Brugeren er blevet inviteret, og en email er blevet sendt.');
    }





    #[NoReturn] public static function updateTeamMember(array $args): void {
        foreach (["action", "role", "member_uuid"] as $key)
            if(!array_key_exists($key, $args) || empty(trim($args[$key]))) Response()->jsonError("Der mangler påkrævede felter.");

        $role = trim($args["role"]);
        $action = trim($args["action"]);
        $uuid = trim($args["member_uuid"]);

        if($uuid === __uuid()) Response()->jsonError("Du kan ikke lave ændringer til din egen konto.");
        if(isEmpty(Settings::$organisation)) Response()->jsonError("Du er ikke medlem af nogen aktiv " . Translate::word("organisation") . ".");

        $organisationId = Settings::$organisation->organisation->uid;
        $member = Methods::organisationMembers()->getMember($organisationId, $uuid);
        if(isEmpty($member)) Response()->jsonError("Denne bruger er ikke medlem af denne " . Translate::word("organisation") . ".");

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
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig " . Translate::word("organisation")], 400);
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
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig " . Translate::word("organisation")], 400);
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
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig " . Translate::word("organisation")], 400);
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
        if(isEmpty($organisation)) Response()->jsonError("Bad request", ["reason" => "Ugyldig " . Translate::word("organisation")], 400);
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