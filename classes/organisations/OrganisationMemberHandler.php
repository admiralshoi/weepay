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
            ->where('status', MemberEnum::MEMBER_ACTIVE)
            ->exists();
    }
    public function firstValidOrganisation(string|int $uuid = 0): ?object {
        if(empty($uuid)) $uuid = __uuid();
        return $this->withForeignValues(OrganisationMembers::queryBuilder()
            ->where('uuid', $uuid)
            ->where('invitation_status', "!=", MemberEnum::INVITATION_PENDING)
            ->where('status', MemberEnum::MEMBER_ACTIVE)
            ->first());
    }

    public function userIsMember(?string $organisationId, string $uuid = ''): bool {
        if(empty($organisationId)) return false;
        // Admins have access to all organisations
        if(Methods::isAdmin()) return true;
        if(empty($uuid)) $uuid = __uuid();
        return OrganisationMembers::queryBuilder()
            ->where('uuid', $uuid)
            ->where('organisation', $organisationId)
            ->where('invitation_status', MemberEnum::INVITATION_ACCEPTED)
            ->where('status', MemberEnum::MEMBER_ACTIVE)
            ->exists();
    }



    public function getUserOrganisations(string|int $uuid = 0): Collection {
        if(empty($uuid)) $uuid = __uuid();
        return $this->getByX(["uuid" => $uuid]);
    }



    public function getMember(string|int $organisationId, string|int $uuid = 0, array $fields =  []): ?object {
        if(empty($uuid)) $uuid = __uuid();
        return $this->first(["uuid" => $uuid, "organisation" => $organisationId], $fields);
    }



    public function memberHasPermission(string $type, string $mainObject = "", string $subObject = ""): bool {
        // Admins have full permissions on all organisations
        if(Methods::isAdmin()) return true;

        if(!in_array($type, ["read", "modify", "delete"])) return false;
        if(isEmpty($mainObject) && empty($subObject)) return false;
        $organisation = \features\Settings::$organisation;
        if(isEmpty($organisation)) return false;

        // Check if member is suspended or deleted - no permissions if so
        if($organisation->status === MemberEnum::MEMBER_SUSPENDED || $organisation->status === MemberEnum::MEMBER_DELETED) return false;

        $role = $organisation->role;
        $orgPermissions = $organisation->organisation->permissions ?? new \stdClass();
        $permissions = isset($orgPermissions->$role) ? toArray($orgPermissions->$role) : [];

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






    public function createNewMember(
        string|int $organisationId,
        string|int $uuid,
        string $role,
        string $invitationStatus = MemberEnum::INVITATION_PENDING,
        ?array $scopedLocations = null
    ): bool {
        // Check if member already exists (including deleted/suspended)
        $existingMember = $this->getMember($organisationId, $uuid);
        if(!isEmpty($existingMember)) {
            // Update existing member instead of creating duplicate
            $updateParams = [
                "role" => $role,
                "invitation_status" => $invitationStatus,
                "status" => MemberEnum::MEMBER_ACTIVE,
                "invitation_activity" => $this->getEventDetails($invitationStatus),
            ];
            if($scopedLocations !== null) {
                $updateParams["scoped_locations"] = $scopedLocations;
            }
            return $this->updateMemberDetails($organisationId, $uuid, $updateParams);
        }

        $params = [
            "organisation" => $organisationId,
            "uuid" => $uuid,
            "role" => $role,
            "invitation_status" => $invitationStatus,
            "invitation_activity" => [
                $this->getEventDetails($invitationStatus)
            ],
        ];
        if($scopedLocations !== null) {
            $params["scoped_locations"] = $scopedLocations;
        }
        return $this->create($params);
    }


    public function updateMemberDetails(string|int $organisationId, string|int $uuid, array $args): bool {
        $params = [];
        foreach ([
             "role", "invitation_status", "status",
             "invitation_activity", "change_activity", "scoped_locations",
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


    /**
     * Get paginated organisation members with search, filter, and sort
     *
     * @param string $organisationUid Organisation UID
     * @param int $page Current page (1-indexed)
     * @param int $perPage Items per page
     * @param string $sortColumn Column to sort by
     * @param string $sortDirection Sort direction (ASC/DESC)
     * @param string|null $search Search term for name/email
     * @param string|null $filterRole Role filter
     * @param string|null $filterStatus Status filter
     * @param bool $excludeLocationEmployees Whether to exclude location_employee role
     * @return array{items: Collection, count: int, page: int, perPage: int, totalPages: int}
     */
    #[ArrayShape(["items" => "Collection", "count" => "int", "page" => "int", "perPage" => "int", "totalPages" => "int"])]
    public function getOrganisationMembersPagination(
        string $organisationUid,
        int $page = 1,
        int $perPage = 10,
        string $sortColumn = "created_at",
        string $sortDirection = "DESC",
        ?string $search = null,
        ?string $filterRole = null,
        ?string $filterStatus = null,
        bool $excludeLocationEmployees = true,
    ): array {
        $response = [
            "items" => new Collection(),
            "count" => 0,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => 0,
        ];

        // Build base query
        $query = $this->queryBuilder()
            ->select(['id', 'uid', 'uuid', 'organisation', 'role', 'status', 'invitation_status', 'scoped_locations', 'created_at'])
            ->where('organisation', $organisationUid);

        // Exclude location_employee by default
        if($excludeLocationEmployees) {
            $query->where('role', '!=', 'location_employee');
        }

        // Apply role filter
        if(!empty($filterRole)) {
            $query->where('role', $filterRole);
        }

        // Apply status filter (mapped from display status to actual status/invitation_status)
        if(!empty($filterStatus)) {
            switch ($filterStatus) {
                case 'Active':
                    $query->where('status', MemberEnum::MEMBER_ACTIVE)
                          ->where('invitation_status', MemberEnum::INVITATION_ACCEPTED);
                    break;
                case 'Suspended':
                    $query->where('status', MemberEnum::MEMBER_SUSPENDED);
                    break;
                case 'Pending':
                    $query->where('invitation_status', MemberEnum::INVITATION_PENDING);
                    break;
                case 'Declined':
                    $query->where('invitation_status', MemberEnum::INVITATION_DECLINED);
                    break;
                case 'Retracted':
                    $query->where('invitation_status', MemberEnum::INVITATION_RETRACTED);
                    break;
                case 'Active_Pending':
                    // Filter for Active OR Pending members
                    $query->where('status', MemberEnum::MEMBER_ACTIVE)
                          ->where('invitation_status', [MemberEnum::INVITATION_ACCEPTED, MemberEnum::INVITATION_PENDING]);
                    break;
            }
        }

        // Apply search filter - search in related user table
        if(!empty($search)) {
            $userHandler = Methods::users();
            $matchingUserUids = $userHandler->queryBuilder()
                ->startGroup("OR")
                ->whereLike('full_name', $search)
                ->whereLike('email', $search)
                ->endGroup()
                ->pluck('uid');

            if(empty($matchingUserUids)) {
                return $response;
            }
            $query->where('uuid', $matchingUserUids);
        }

        // Get total count before pagination
        $totalCount = $query->count();
        if($totalCount === 0) {
            return $response;
        }

        // Calculate pagination
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        // Apply sorting and pagination
        $items = $this->queryGetAll(
            $query->order($sortColumn, $sortDirection)
           ->limit($perPage)
           ->offset($offset)
        );

        return [
            "items" => $items,
            "count" => $totalCount,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
        ];
    }


}