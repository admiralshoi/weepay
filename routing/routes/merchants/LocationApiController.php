<?php

namespace routing\routes\merchants;

use classes\app\LocationPermissions;
use classes\app\OrganisationPermissions;
use classes\lang\Translate;
use classes\Methods;
use classes\notifications\NotificationTriggers;
use classes\organisations\LocationRolePermissions;
use classes\organisations\MemberEnum;
use classes\utility\Titles;
use features\Settings;
use JetBrains\PhpStorm\NoReturn;

class LocationApiController {

    #[NoReturn] public static function getLocationMembers(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");

        $locationUid = trim($args["location_uid"]);
        $page = (int)($args["page"] ?? 1);
        $perPage = (int)($args["per_page"] ?? 10);
        $search = isset($args["search"]) ? trim($args["search"]) : null;
        $filterRole = isset($args["filter_role"]) && !empty($args["filter_role"]) ? trim($args["filter_role"]) : null;
        $filterStatus = isset($args["filter_status"]) && !empty($args["filter_status"]) ? trim($args["filter_status"]) : null;
        $sortColumn = isset($args["sort_column"]) && !empty($args["sort_column"]) ? trim($args["sort_column"]) : "created_at";
        $sortDirection = isset($args["sort_direction"]) && in_array(strtoupper($args["sort_direction"]), ["ASC", "DESC"])
            ? strtoupper($args["sort_direction"])
            : "DESC";

        // Map frontend sort columns to database columns
        $sortColumnMap = [
            'name' => 'created_at',
            'role' => 'role',
            'status' => 'status',
        ];
        if(array_key_exists($sortColumn, $sortColumnMap)) {
            $sortColumn = $sortColumnMap[$sortColumn];
        }

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        // Check location permissions
        if(!LocationPermissions::__oRead($location, 'team_members'))
            Response()->jsonError(Translate::context("location.no_permission_view"));

        // Get paginated members
        $locationMemberHandler = Methods::locationMembers();
        $result = $locationMemberHandler->getLocationMembersPagination(
            $locationUid,
            $page,
            $perPage,
            $sortColumn,
            $sortDirection,
            $search,
            $filterRole,
            $filterStatus,
        );

        // Transform members for frontend
        $members = $result["items"]->map(function ($member) use ($location) {
            $status = $member["status"];
            $invitationStatus = $member["invitation_status"];
            $user = !is_string($member['uuid']) ? toObject($member['uuid']) : Methods::users()->get($member['uuid'], ['email', 'full_name', 'uid']);

            if($status === MemberEnum::MEMBER_SUSPENDED) {
                $showStatus = "Suspended";
                $statusBoxClass = "danger-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-power-off", 'title' => "Unsuspend", "action" => "unsuspend", 'risk' => "low"],
                    ["icon" => "fa-solid fa-user-xmark", 'title' => "Remove", "action" => "remove", 'risk' => "high"],
                ];
            }
            elseif($invitationStatus === MemberEnum::INVITATION_PENDING) {
                $showStatus = "Pending";
                $statusBoxClass = "warning-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-xmark", 'title' => "Cancel", "action" => "remove", 'risk' => "high"],
                ];
            }
            else {
                $showStatus = "Active";
                $statusBoxClass = "success-box";
                $actionMenu = [
                    ["icon" => "fa-solid fa-user-pen", 'title' => "Update Role", "action" => "update-role", 'risk' => "low"],
                    ["icon" => "fa-solid fa-ban", 'title' => "Suspend", "action" => "suspend", 'risk' => "high"],
                    ["icon" => "fa-solid fa-user-xmark", 'title' => "Remove", "action" => "remove", 'risk' => "high"],
                ];
            }

            $member["action_menu"] = $actionMenu;
            $member["show_status"] = $showStatus;
            $member["status_box"] = $statusBoxClass;
            $member["name"] = $user?->full_name ?? '';
            $member["email"] = $user?->email ?? '';
            $member["initials"] = __initials($member["name"]);
            $member["name_truncated"] = Titles::truncateStr(Titles::cleanUcAll($member["name"]), 16);
            $member["member_uuid"] = $user?->uid;
            $member["role_title"] = ucfirst(Translate::word(Titles::clean($member["role"])));

            return $member;
        });

        // Get location roles for role titles
        $permissions = $location->permissions;
        $locationRoles = [];
        foreach($permissions as $role => $roleData) {
            $locationRoles[$role] = ucfirst(Translate::word(Titles::clean($role)));
        }

        Response()->jsonSuccess("", [
            "members" => $members->toArray(),
            "pagination" => [
                "page" => $result["page"],
                "perPage" => $result["perPage"],
                "total" => $result["count"],
                "totalPages" => $result["totalPages"],
            ],
            "roles" => $locationRoles,
        ]);
    }

    #[NoReturn] public static function inviteLocationMember(array $args): void {
        // Validate required fields
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("user_type", $args) || empty(trim($args["user_type"])))
            Response()->jsonError("User type is required.");
        if(!array_key_exists("role", $args) || empty(trim($args["role"])))
            Response()->jsonError("Udfyld venligst feltet rolle.");

        $locationUid = trim($args["location_uid"]);
        $userType = trim($args["user_type"]);
        $role = trim($args["role"]);

        // Initialize handlers
        $locationMemberHandler = Methods::locationMembers();
        $orgMemberHandler = Methods::organisationMembers();

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        // Check location permissions
        if(!LocationPermissions::__oModify($location, 'team_invitations'))
            Response()->jsonError(Translate::context("location.no_permission_invite"));

        $organisationId = __oUuid();

        if($userType === 'existing') {
            // EXISTING ORGANISATION MEMBER PATH
            if(!array_key_exists("existing_member_uuid", $args) || empty(trim($args["existing_member_uuid"])))
                Response()->jsonError(Translate::context("location.select_member"));

            $userUuid = trim($args["existing_member_uuid"]);

            // Verify user is actually an org member
            $orgMember = $orgMemberHandler->getMember($organisationId, $userUuid);
            if(isEmpty($orgMember)) Response()->jsonError("Denne bruger er ikke medlem af organisationen.");

            // Check if already a location member
            $existingLocMember = $locationMemberHandler->first(['uuid' => $userUuid, 'location' => $locationUid]);
            if(!isEmpty($existingLocMember)) {
                // If member exists but was deleted/suspended, reactivate them
                if($existingLocMember->status === MemberEnum::MEMBER_DELETED || $existingLocMember->status === MemberEnum::MEMBER_SUSPENDED) {
                    $locationMemberHandler->update([
                        'role' => $role,
                        'status' => MemberEnum::MEMBER_ACTIVE,
                        'invitation_status' => MemberEnum::INVITATION_ACCEPTED,
                        'invitation_activity' => [
                            $locationMemberHandler->getEventDetails(MemberEnum::INVITATION_ACCEPTED)
                        ]
                    ], ['uuid' => $userUuid, 'location' => $locationUid]);

                    // If member has scoped locations, ensure this location is in their scope
                    $scopedLocations = !isEmpty($orgMember->scoped_locations) ? toArray($orgMember->scoped_locations) : null;
                    if($scopedLocations !== null && !in_array($locationUid, $scopedLocations)) {
                        $scopedLocations[] = $locationUid;
                        $orgMemberHandler->updateMemberDetails($organisationId, $userUuid, [
                            'scoped_locations' => $scopedLocations
                        ]);
                    }

                    Response()->setRedirect()->jsonSuccess(Translate::context("location.member_reactivated"));
                }
                Response()->jsonError(Translate::context("location.member_already_added"));
            }

            // If member has scoped locations (not null/empty), add this location to their scope
            $scopedLocations = !isEmpty($orgMember->scoped_locations) ? toArray($orgMember->scoped_locations) : null;
            if($scopedLocations !== null && !in_array($locationUid, $scopedLocations)) {
                $scopedLocations[] = $locationUid;
                $orgMemberHandler->updateMemberDetails($organisationId, $userUuid, [
                    'scoped_locations' => $scopedLocations
                ]);
            }

            // Add to location (auto-accepted for existing org members)
            $locationMemberHandler->create([
                "uuid" => $userUuid,
                "location" => $locationUid,
                "role" => $role,
                "invitation_status" => MemberEnum::INVITATION_ACCEPTED,
                "status" => MemberEnum::MEMBER_ACTIVE,
                "invitation_activity" => [
                    $locationMemberHandler->getEventDetails(MemberEnum::INVITATION_ACCEPTED)
                ],
            ]);

            Response()->setRedirect()->jsonSuccess(Translate::context("location.member_added"));
        }
        else {
            // NEW USER PATH
            if(!array_key_exists("full_name", $args) || empty(trim($args["full_name"])))
                Response()->jsonError("Udfyld venligst feltet fuldt navn.");

            $fullName = trim($args["full_name"]);
            $email = array_key_exists("email", $args) && !empty(trim($args["email"])) ? trim($args["email"]) : null;

            // Validate email format if provided
            if($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL))
                Response()->jsonError("Ugyldig email format.");

            // Check if user with this email already exists
            $userHandler = Methods::users();
            $existingUser = $email !== null ? $userHandler->getByEmail($email) : null;

            if(!isEmpty($existingUser)) {
                // User exists, treat as existing member path
                if($existingUser->access_level !== Methods::roles()->accessLevel('merchant'))
                    Response()->jsonError("Denne bruger er ikke registreret som en forhandler-konto.");

                // Check if they have any org membership record (including suspended/deleted)
                $orgMember = $orgMemberHandler->getMember($organisationId, $existingUser->uid);
                $isActiveOrgMember = !isEmpty($orgMember) && $orgMember->status === MemberEnum::MEMBER_ACTIVE;
                $wasOrgMember = !isEmpty($orgMember);

                if(!$wasOrgMember) {
                    // Never been an org member - create new membership
                    $orgMemberHandler->createNewMember(
                        $organisationId,
                        $existingUser->uid,
                        'LOCATION_EMPLOYEE',
                        MemberEnum::INVITATION_PENDING,
                        [$locationUid]
                    );

                    // Send invitation notification
                    Methods::notificationHandler()->teamInvitation([
                        'uid' => $existingUser->uid,
                        'organisation_uid' => $organisationId,
                        'invited_by' => __uuid()
                    ]);

                    // Trigger organisation member invited notification
                    $organisation = Methods::organisations()->get($organisationId);
                    $inviter = Methods::users()->get(__uuid());
                    NotificationTriggers::organisationMemberInvited(
                        $organisation,
                        $existingUser->email ?? '',
                        $inviter,
                        __url(ORGANISATION_PANEL_PATH . '/add')
                    );
                } elseif(!$isActiveOrgMember) {
                    // Was an org member but suspended/deleted - reactivate with pending invitation
                    $scopedLocations = !isEmpty($orgMember->scoped_locations) ? toArray($orgMember->scoped_locations) : [];
                    if(!in_array($locationUid, $scopedLocations)) {
                        $scopedLocations[] = $locationUid;
                    }
                    $orgMemberHandler->updateMemberDetails($organisationId, $existingUser->uid, [
                        'status' => MemberEnum::MEMBER_ACTIVE,
                        'invitation_status' => MemberEnum::INVITATION_PENDING,
                        'scoped_locations' => $scopedLocations,
                        'invitation_activity' => $orgMemberHandler->getEventDetails(MemberEnum::INVITATION_PENDING)
                    ]);

                    // Send invitation notification
                    Methods::notificationHandler()->teamInvitation([
                        'uid' => $existingUser->uid,
                        'organisation_uid' => $organisationId,
                        'invited_by' => __uuid()
                    ]);

                    // Trigger organisation member invited notification
                    $organisation = Methods::organisations()->get($organisationId);
                    $inviter = Methods::users()->get(__uuid());
                    NotificationTriggers::organisationMemberInvited(
                        $organisation,
                        $existingUser->email ?? '',
                        $inviter,
                        __url(ORGANISATION_PANEL_PATH . '/add')
                    );
                } else {
                    // Active org member - just ensure location is in their scope
                    $scopedLocations = !isEmpty($orgMember->scoped_locations) ? toArray($orgMember->scoped_locations) : null;
                    if($scopedLocations !== null && !in_array($locationUid, $scopedLocations)) {
                        $scopedLocations[] = $locationUid;
                        $orgMemberHandler->updateMemberDetails($organisationId, $existingUser->uid, [
                            'scoped_locations' => $scopedLocations
                        ]);
                    }
                }

                // Check for existing location membership
                $existingLocMember = $locationMemberHandler->first(['uuid' => $existingUser->uid, 'location' => $locationUid]);
                if(!isEmpty($existingLocMember)) {
                    // If location member exists but was deleted/suspended, reactivate
                    if($existingLocMember->status === MemberEnum::MEMBER_DELETED || $existingLocMember->status === MemberEnum::MEMBER_SUSPENDED) {
                        $locationMemberHandler->update([
                            'role' => $role,
                            'status' => MemberEnum::MEMBER_ACTIVE,
                            'invitation_status' => $isActiveOrgMember ? MemberEnum::INVITATION_ACCEPTED : MemberEnum::INVITATION_PENDING,
                            'invitation_activity' => [
                                $locationMemberHandler->getEventDetails($isActiveOrgMember ? MemberEnum::INVITATION_ACCEPTED : MemberEnum::INVITATION_PENDING)
                            ]
                        ], ['uuid' => $existingUser->uid, 'location' => $locationUid]);
                    }
                    // If already active, we just continue (no error, scoped_locations already updated above)
                } else {
                    // Create new location membership
                    $locationMemberHandler->create([
                        "uuid" => $existingUser->uid,
                        "location" => $locationUid,
                        "role" => $role,
                        "invitation_status" => $isActiveOrgMember ? MemberEnum::INVITATION_ACCEPTED : MemberEnum::INVITATION_PENDING,
                        "status" => MemberEnum::MEMBER_ACTIVE,
                        "invitation_activity" => [
                            $locationMemberHandler->getEventDetails($isActiveOrgMember ? MemberEnum::INVITATION_ACCEPTED : MemberEnum::INVITATION_PENDING)
                        ],
                    ]);
                }

                $message = $isActiveOrgMember
                    ? Translate::context("location.member_added")
                    : "En invitation er blevet sendt til brugeren.";
                Response()->setRedirect()->jsonSuccess($message);
            }
            else {
                // NEW USER PATH - Create user and add to team
                $organisationName = Settings::$organisation->organisation->name;

                // Generate unique username
                $username = $userHandler->generateUniqueUsername($organisationName, $fullName);
                $password = $organisationId; // Use org UID as temp password

                // Create user
                if(!$userHandler->create([
                    'full_name' => $fullName,
                    'access_level' => Methods::roles()->accessLevel('merchant'),
                    'lang' => 'DA',
                    'created_by' => __uuid()
                ])) Response()->jsonError("Kunne ikke oprette brugeren. Prøv igen senere.");
                $userUid = $userHandler->recentUid;

                // Create auth record
                Methods::localAuthentication()->create([
                    'username' => $username,
                    'email' => null, // Don't set email to avoid conflicts
                    'password' => passwordHashing($password),
                    'user' => $userUid,
                    'enabled' => 1,
                    'force_password_change' => 1
                ]);

                // Add to organisation with LOCATION_EMPLOYEE role
                $orgMemberHandler->createNewMember(
                    $organisationId,
                    $userUid,
                    'location_employee',
                    MemberEnum::INVITATION_PENDING,
                    [$locationUid]
                );

                // Trigger organisation member invited notification
                $organisation = Methods::organisations()->get($organisationId);
                $inviter = Methods::users()->get(__uuid());
                NotificationTriggers::organisationMemberInvited(
                    $organisation,
                    $email ?? '',
                    $inviter,
                    __url(ORGANISATION_PANEL_PATH . '/add')
                );

                // Add to location with PENDING status
                $locationMemberHandler->create([
                    "uuid" => $userUid,
                    "location" => $locationUid,
                    "role" => $role,
                    "invitation_status" => MemberEnum::INVITATION_PENDING,
                    "status" => MemberEnum::MEMBER_ACTIVE,
                    "invitation_activity" => [
                        $locationMemberHandler->getEventDetails(MemberEnum::INVITATION_PENDING)
                    ],
                ]);

                // Send notification
                $notificationHandler = Methods::notificationHandler();
                if($email) {
                    // If email provided, send email notification
                    $notificationHandler->userCreated([
                        'uid' => $userUid,
                        'organisation_name' => $organisationName,
                        'username' => $username,
                        'password' => $password,
                        'ref' => $organisationId,
                        'push_type' => 1 // Email
                    ]);
                    $emailSent = true;
                } else {
                    // No email, just platform notification
                    $notificationHandler->userCreated([
                        'uid' => $userUid,
                        'organisation_name' => $organisationName,
                        'username' => $username,
                        'password' => $password,
                        'ref' => $organisationId,
                        'push_type' => 0 // Platform only
                    ]);
                    $emailSent = false;
                }

                // Return success with credentials
                Response()->setRedirect()->jsonSuccess(
                    'Brugeren er blevet oprettet og tilføjet til lokationen.',
                    [
                        'user_created' => true,
                        'username' => $username,
                        'password' => $password,
                        'email_sent' => $emailSent,
                        'full_name' => $fullName
                    ]
                );
            }
        }
    }

    #[NoReturn] public static function updateLocationMember(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("member_uuid", $args) || empty(trim($args["member_uuid"])))
            Response()->jsonError("Member UUID is required.");
        if(!array_key_exists("action", $args) || empty(trim($args["action"])))
            Response()->jsonError("Action is required.");

        $locationUid = trim($args["location_uid"]);
        $memberUuid = trim($args["member_uuid"]);
        $action = trim($args["action"]);
        $role = array_key_exists("role", $args) ? trim($args["role"]) : null;
        $organisationId = __oUuid();

        if($memberUuid === __uuid()) Response()->jsonError("Du kan ikke lave ændringer til din egen konto.");

        // Initialize handler
        $locationMemberHandler = Methods::locationMembers();

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== $organisationId) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        // Get location member
        $member = $locationMemberHandler->first(['uuid' => $memberUuid, 'location' => $locationUid]);
        if(isEmpty($member)) Response()->jsonError(Translate::context("location.member_not_found"));

        switch($action) {
            case 'update-role':
                if(!LocationPermissions::__oModify($location, 'team_members'))
                    Response()->jsonPermissionError("redigerings", 'medarbejdere');
                if(isEmpty($role)) Response()->jsonError("Rolle er påkrævet.");

                $locationMemberHandler->update(['role' => $role], ['uuid' => $memberUuid, 'location' => $locationUid]);
                Response()->setRedirect()->jsonSuccess(Translate::context("location.member_role_updated"));
                break;

            case 'suspend':
                if(!LocationPermissions::__oModify($location, 'team_members'))
                    Response()->jsonPermissionError("redigerings", 'medarbejdere');

                // Suspend location membership only - location member status is the authority for location access
                $locationMemberHandler->update([
                    'status' => MemberEnum::MEMBER_SUSPENDED,
                    'change_activity' => [
                        $locationMemberHandler->getEventDetails(MemberEnum::MEMBER_SUSPENDED)
                    ]
                ], ['uuid' => $memberUuid, 'location' => $locationUid]);

                Response()->setRedirect()->jsonSuccess(Translate::context("location.member_suspended"));
                break;

            case 'unsuspend':
                if(!LocationPermissions::__oModify($location, 'team_members'))
                    Response()->jsonPermissionError("redigerings", 'medarbejdere');

                // Unsuspend location membership only
                $locationMemberHandler->update([
                    'status' => MemberEnum::MEMBER_ACTIVE,
                    'change_activity' => [
                        $locationMemberHandler->getEventDetails(MemberEnum::MEMBER_UNSUSPENDED)
                    ]
                ], ['uuid' => $memberUuid, 'location' => $locationUid]);

                Response()->setRedirect()->jsonSuccess(Translate::context("location.member_reactivated"));
                break;

            case 'remove':
                if(!LocationPermissions::__oDelete($location, 'team_members'))
                    Response()->jsonPermissionError("slette", 'medarbejdere');

                // Mark location membership as deleted
                $locationMemberHandler->update([
                    'status' => MemberEnum::MEMBER_DELETED,
                    'change_activity' => [
                        $locationMemberHandler->getEventDetails(MemberEnum::MEMBER_DELETED)
                    ]
                ], ['uuid' => $memberUuid, 'location' => $locationUid]);

                // Remove this location from user's scoped_locations if present
                $orgMemberHandler = Methods::organisationMembers();
                $orgMember = $orgMemberHandler->getMember($organisationId, $memberUuid);
                if(!isEmpty($orgMember)) {
                    $scopedLocations = !isEmpty($orgMember->scoped_locations) ? toArray($orgMember->scoped_locations) : null;
                    if($scopedLocations !== null) {
                        $scopedLocations = array_values(array_filter($scopedLocations, fn($loc) => $loc !== $locationUid));

                        // If this was their only scoped location, suspend org membership
                        if(empty($scopedLocations)) {
                            $orgMemberHandler->updateMemberDetails($organisationId, $memberUuid, [
                                'status' => MemberEnum::MEMBER_SUSPENDED,
                                'scoped_locations' => [],
                                'change_activity' => $orgMemberHandler->getEventDetails(MemberEnum::MEMBER_SUSPENDED)
                            ]);
                        } else {
                            $orgMemberHandler->updateMemberDetails($organisationId, $memberUuid, [
                                'scoped_locations' => $scopedLocations
                            ]);
                        }
                    }
                }

                Response()->setRedirect()->jsonSuccess(Translate::context("location.member_removed"));
                break;

            default:
                Response()->jsonError("Ugyldig handling.");
        }
    }

    #[NoReturn] public static function createLocationRole(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("role", $args) || empty(trim($args["role"])))
            Response()->jsonError("Venligst angiv et rollenavn.");

        $locationUid = trim($args["location_uid"]);
        $roleName = strtolower(trim($args["role"]));

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        if(!LocationPermissions::__oModify($location, 'team_roles'))
            Response()->jsonPermissionError("redigerings", 'roller');

        $permissions = toArray($location->permissions);
        if(array_key_exists($roleName, $permissions))
            Response()->jsonError("En rolle med dette navn eksisterer allerede.");


        $permissions[$roleName] = LocationRolePermissions::getForRole($roleName);
        Methods::locations()->update(['permissions' => $permissions], ['uid' => $locationUid]);

        Response()->setRedirect()->jsonSuccess("Rollen er blevet oprettet.");
    }

    #[NoReturn] public static function renameLocationRole(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("old_role", $args) || empty(trim($args["old_role"])))
            Response()->jsonError("Venligst angiv den gamle rolle.");
        if(!array_key_exists("new_role", $args) || empty(trim($args["new_role"])))
            Response()->jsonError("Venligst angiv et nyt rollenavn.");

        $locationUid = trim($args["location_uid"]);
        $oldRole = strtolower(trim($args["old_role"]));
        $newRole = strtolower(trim($args["new_role"]));

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        if(!LocationPermissions::__oModify($location, 'team_roles'))
            Response()->jsonPermissionError("redigerings", 'roller');

        if($oldRole === 'owner') Response()->jsonError("Owner rollen kan ikke omdøbes.");

        $permissions = toArray($location->permissions);
        if(!array_key_exists($oldRole, $permissions))
            Response()->jsonError("Den gamle rolle blev ikke fundet.");
        if(array_key_exists($newRole, $permissions))
            Response()->jsonError("En rolle med det nye navn eksisterer allerede.");

        // Rename role
        $permissions[$newRole] = $permissions[$oldRole];
        unset($permissions[$oldRole]);
        Methods::locations()->update(['permissions' => $permissions], ['uid' => $locationUid]);

        // Update all members with this role
        Methods::locationMembers()->queryBuilder()
            ->whereList(['location' => $locationUid, 'role' => $oldRole])
            ->update(['role' => $newRole]);

        Response()->setRedirect()->jsonSuccess('Rollen er blevet omdøbt.');
    }

    #[NoReturn] public static function deleteLocationRole(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("role", $args) || empty(trim($args["role"])))
            Response()->jsonError("Venligst angiv en gyldig rolle.", $args);

        $locationUid = trim($args["location_uid"]);
        $role = strtolower(trim($args["role"]));

        // Get and validate location
        $location = Methods::locations()->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        if(!LocationPermissions::__oDelete($location, 'team_roles'))
            Response()->jsonPermissionError("slette", 'roller');

        if($role === 'owner') Response()->jsonError("Owner rollen kan ikke slettes.");

        $permissions = toArray($location->permissions);
        if(!array_key_exists($role, $permissions))
            Response()->jsonError("Rollen blev ikke fundet.");

        // Check if any members have this role
        $membersWithRole = Methods::locationMembers()->queryBuilder()
            ->whereList(['location' => $locationUid, 'role' => $role])
            ->count();

        if($membersWithRole > 0)
            Response()->jsonError(Translate::context("location.role_has_members"));

        unset($permissions[$role]);
        Methods::locations()->update(['permissions' => $permissions], ['uid' => $locationUid]);

        Response()->setRedirect()->jsonSuccess('Rollen er blevet slettet.');
    }

    #[NoReturn] public static function updateLocationRolePermissions(array $args): void {
        if(!array_key_exists("location_uid", $args) || empty(trim($args["location_uid"])))
            Response()->jsonError("Location UID is required.");
        if(!array_key_exists("role", $args) || empty(trim($args["role"])))
            Response()->jsonError("Rolle er påkrævet.");

        $locationUid = trim($args["location_uid"]);
        $role = trim($args["role"]);
        unset($args["location_uid"], $args["role"]);

        // Get and validate location
        $locationHandler = Methods::locations();
        $location = $locationHandler->get($locationUid);
        if(isEmpty($location)) Response()->jsonError("Lokation ikke fundet.");
        if($location->uuid->uid !== __oUuid()) Response()->jsonError("Du har ikke tilladelse til denne handling.");

        if(!LocationPermissions::__oModify($location, 'role_permissions'))
            Response()->jsonPermissionError("redigerings", 'rolletilladelser');

        if($role === 'owner') Response()->jsonError("Ejer-rollens tilladelser kan ikke ændres.");

        $permissions = toArray($location->permissions);
        if(!array_key_exists($role, $permissions))
            Response()->jsonError("Rollen blev ikke fundet.");

        // Build permissions from form data using BASE_PERMISSIONS as template
        $basePermissions = $locationHandler::BASE_PERMISSIONS;

        foreach ($basePermissions as $mainObject => &$mainPermissions) {
            $newMain = array_key_exists($mainObject, $args) ? $args[$mainObject] : [];
            $mainPermissions["read"] = array_key_exists("read", $newMain) && $newMain["read"] === "on";
            $mainPermissions["modify"] = array_key_exists("modify", $newMain) && $newMain["modify"] === "on";
            $mainPermissions["delete"] = array_key_exists("delete", $newMain) && $newMain["delete"] === "on";

            if(!array_key_exists("permissions", $newMain)) $newMain["permissions"] = [];
            foreach ($mainPermissions["permissions"] as $subObject => &$subPermissions) {
                $newSub = array_key_exists($subObject, $newMain["permissions"]) ? $newMain["permissions"][$subObject] : [];
                $subPermissions["read"] = $mainPermissions["read"] !== false && array_key_exists("read", $newSub) && $newSub["read"] === "on";
                $subPermissions["modify"] = $mainPermissions["modify"] !== false && array_key_exists("modify", $newSub) && $newSub["modify"] === "on";
                $subPermissions["delete"] = $mainPermissions["delete"] !== false && array_key_exists("delete", $newSub) && $newSub["delete"] === "on";
            }
        }

        $permissions[$role] = $basePermissions;
        $locationHandler->update(['permissions' => $permissions], ['uid' => $locationUid]);

        Response()->setRedirect()->jsonSuccess("Rollen '" . Titles::cleanUcAll($role) . "'s tilladelser er blevet opdateret.");
    }
}
