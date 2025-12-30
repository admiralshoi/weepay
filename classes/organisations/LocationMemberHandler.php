<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use classes\utility\Titles;
use Database\Collection;
use Database\model\LocationMembers;
use features\Settings;
use JetBrains\PhpStorm\ArrayShape;

class LocationMemberHandler extends Crud {


    public bool $isError = true;


    function __construct() {
        parent::__construct(LocationMembers::newStatic(), "location_members");
    }




    public function getUserLocations(string|int $uuid = 0): Collection {
        if(empty($uuid)) $uuid = __uuid();
        return $this->getByX(["uuid" => $uuid]);
    }



    public function getMember(string|int $locationId, string|int $uuid = 0): ?object {
        if(empty($uuid)) $uuid = __uuid();
        return $this->first(["uuid" => $uuid, "location" => $locationId]);
    }



    public function memberHasPermission(?object $location, string $type, string $mainObject = "", string $subObject = ""): bool {
        if(isEmpty($location)) return false;
        if(!in_array($type, ["read", "modify", "delete"])) return false;
        if(isEmpty($mainObject) && empty($subObject)) return false;
        $memberRow = $this->getMember($location->uid);
        if(isEmpty($memberRow)) return false;

        // Check if member is suspended or deleted - no permissions if so
        if($memberRow->status === MemberEnum::MEMBER_SUSPENDED || $memberRow->status === MemberEnum::MEMBER_DELETED) return false;

        $role = $memberRow->role;
        $permissions = toArray($location->permissions->$role);

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
        if(empty($triggeredBy)) $triggeredBy = __uuid();
        return ["timestamp" => time(), "triggered_by" => $triggeredBy, "event" => $eventType, "extra" => $extra];
    }


    /**
     * Get paginated location members with search, filter, and sort
     *
     * @param string $locationUid Location UID
     * @param int $page Current page (1-indexed)
     * @param int $perPage Items per page
     * @param string $sortColumn Column to sort by
     * @param string $sortDirection Sort direction (ASC/DESC)
     * @param string|null $search Search term for name/email
     * @param string|null $filterRole Role filter
     * @param string|null $filterStatus Status filter
     * @return array{items: Collection, count: int, page: int, perPage: int, totalPages: int}
     */
    #[ArrayShape(["items" => "Collection", "count" => "int", "page" => "int", "perPage" => "int", "totalPages" => "int"])]
    public function getLocationMembersPagination(
        string $locationUid,
        int $page = 1,
        int $perPage = 10,
        string $sortColumn = "created_at",
        string $sortDirection = "DESC",
        ?string $search = null,
        ?string $filterRole = null,
        ?string $filterStatus = null,
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
            ->select(['id', 'uid', 'uuid', 'location', 'role', 'status', 'invitation_status', 'created_at'])
            ->where('location', $locationUid)
            ->where('status', [MemberEnum::MEMBER_SUSPENDED, MemberEnum::MEMBER_ACTIVE]);

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
        $items = $query->order($sortColumn, $sortDirection)
                       ->limit($perPage)
                       ->offset($offset)
                       ->all();

        return [
            "items" => $items,
            "count" => $totalCount,
            "page" => $page,
            "perPage" => $perPage,
            "totalPages" => $totalPages,
        ];
    }


}
