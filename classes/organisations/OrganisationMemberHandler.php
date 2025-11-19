<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use classes\utility\Titles;
use Database\Collection;
use Database\model\OrganisationMembers;
use Database\model\Organisations;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;

class OrganisationMemberHandler extends Crud {


    private string $requestingUsersId;
    public bool $isError = true;


    function __construct() {
        parent::__construct(OrganisationMembers::newStatic(), "organisation_members");
        $this->requestingUsersId = __uuid();
    }


    public function setChosenOrganisation(?object $memberRow): void {
        if(isEmpty(Settings::$user)) return;
        Settings::$organisation = $memberRow;
        debugLog([$memberRow?->organisation, $memberRow?->organisation->uid],'set-org-default');
        Methods::users()->setCookie("organisation", $memberRow?->organisation->uid);
    }

    public function hasOrganisation(string|int $uuid = 0): bool {
        if(empty($uuid)) $uuid = __uuid();
        return OrganisationMembers::queryBuilder()
            ->where('uuid', $uuid)
            ->where('invitation_status', "!=", MemberEnum::INVITATION_PENDING)
            ->where('status', "!=", MemberEnum::MEMBER_SUSPENDED)
            ->exists();
    }
    public function firstValidOrganisation(string|int $uuid = 0): ?object {
        if(empty($uuid)) $uuid = __uuid();
        return $this->withForeignValues(OrganisationMembers::queryBuilder()
            ->where('uuid', $uuid)
            ->where('invitation_status', "!=", MemberEnum::INVITATION_PENDING)
            ->where('status', "!=", MemberEnum::MEMBER_SUSPENDED)
            ->first());
    }

    public function userIsMember(?string $organisationId, string $uuid = ''): bool {
        if(empty($organisationId)) return false;
        if(empty($uuid)) $uuid = __uuid();
        return OrganisationMembers::queryBuilder()
            ->where('uuid', $uuid)
            ->where('organisation', $organisationId)
            ->where('invitation_status', "!=", MemberEnum::INVITATION_PENDING)
            ->where('status', "!=", MemberEnum::MEMBER_SUSPENDED)
            ->exists();
    }



    public function getUserOrganisations(string|int $uuid = 0): Collection {
        if(empty($uuid)) $uuid = __uuid();
        return $this->getByX(["uuid" => $uuid]);
    }



    public function getMember(string|int $organisationId, string|int $uuid = 0): ?object {
        if(empty($uuid)) $uuid = __uuid();
        return $this->first(["uuid" => $uuid, "organisation" => $organisationId]);
    }



    public function memberHasPermission(string $type, string $mainObject = "", string $subObject = ""): bool {
        if(!in_array($type, ["read", "modify", "delete"])) return false;
        if(isEmpty($mainObject) && empty($subObject)) return false;
        $organisation = \features\Settings::$organisation;
        if(isEmpty($organisation)) return false;
        $role = $organisation->role;
        $permissions = toArray($organisation->organisation->permissions->$role);

        $mainPermission = [];
        $subPermission = [];
        if(!empty($mainObject)) {
            $mainPermission = array_key_exists($mainObject, $permissions) ? $permissions[$mainObject] : [];
        }

        if(empty($subObject)) return array_key_exists($type, $mainPermission) && $mainPermission[$type];
        if(empty($mainPermission)) {
            foreach ($permissions as $main => $items) {
                if(!array_key_exists("permissions", $items)) continue;
                if(array_key_exists($subObject, $items["permissions"])) {
                    $subPermission = $items["permissions"][$subObject];
                    break;
                }
            }
        }
        else {
            if(!array_key_exists("permissions", $mainPermission)) return false;
            if(array_key_exists($subObject, $mainPermission["permissions"])) {
                $subPermission = $mainPermission["permissions"][$subObject];
            }
        }
        if(empty($subPermission)) return false;
        return array_key_exists($type, $subPermission) && $subPermission[$type];
    }






    public function createNewMember(string|int $organisationId, string|int $uuid, string $role, string $invitationStatus = MemberEnum::INVITATION_PENDING): bool {
        $params = [
            "organisation" => $organisationId,
            "uuid" => $uuid,
            "role" => $role,
            "invitation_status" => $invitationStatus,
            "invitation_activity" => [
                $this->getEventDetails($invitationStatus)
            ],
        ];
        return $this->create($params);
    }


    public function updateMemberDetails(string|int $organisationId, string|int $uuid, array $args): bool {
        $params = [];
        foreach ([
             "role", "invitation_status", "status",
             "invitation_activity", "change_activity",
         ] as $key) if(array_key_exists($key, $args)) $params[$key] = $args[$key];


        $identifier = ["organisation" => $organisationId, "uuid" => $uuid];
        $row = $this->first($identifier);
        if(isEmpty($row)) return false;

        if(!isEmpty($row->invitation_activity) && isset($params["invitation_activity"])) {
            $params["invitation_activity"] = array_merge(
                toArray($row->invitation_activity),
                (isAssoc($params["invitation_activity"]) ? [$params["invitation_activity"]] : $params["invitation_activity"])
            );
        }
        if(!isEmpty($row->change_activity) && isset($params["change_activity"])) {
            $params["change_activity"] = array_merge(
                toArray($row->change_activity),
                (isAssoc($params["change_activity"]) ? [$params["change_activity"]] : $params["change_activity"])
            );
        }

        return $this->update($params, $identifier);
    }

    #[ArrayShape(["timestamp" => "int", "triggered_by" => "int|mixed|string", "event" => "string", "extra" => "array"])]
    public function getEventDetails(string $eventType, int|string $triggeredBy = "", array $extra = []): array {
        if(empty($triggeredBy)) $triggeredBy = $this->requestingUsersId;
        return ["timestamp" => time(), "triggered_by" => $triggeredBy, "event" => $eventType, "extra" => $extra];
    }





}