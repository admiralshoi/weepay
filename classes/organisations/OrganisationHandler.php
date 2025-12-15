<?php

namespace classes\organisations;

use classes\app\OrganisationPermissions;
use classes\Methods;
use classes\utility\Crud;
use classes\utility\Titles;
use Database\Collection;
use Database\model\Organisations;
use features\Settings;

class OrganisationHandler extends Crud {


    const BASE_PERMISSIONS = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'settings' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'wallet' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'members' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'invitations' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'roles' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'permissions' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'roles' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true,
                ],
            ]
        ],
        'locations' => [ //Relates to an individual store that a user is a member of
            'icon' => "mdi mdi-store-outline",
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'locations' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'checkout' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'team_members' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'team_invitations' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'team_roles' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'role_permissions' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'metrics' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'orders' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'customers' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'settings' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'terminals' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'pages' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
            ]
        ],
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'orders' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'metrics' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'customers' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'settings' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
                'settings' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'reports' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'customers' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'metrics' => [
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
                'locations' => [ //all locations of the organisation
                    'read' => true,
                    'modify' => true,
                    'delete' => true
                ],
            ]
        ],
    ];


    public bool $isError = true;


    function __construct() {
        parent::__construct(Organisations::newStatic(), "organisations");
    }


    public function updateNewBasePermissions(): void {
        $organisations = $this->getByX([], ['uid', 'permissions']);
        foreach ($organisations->list() as $org) {
            $rolePermissions = toArray($org->permissions);

            foreach ($rolePermissions as  &$permissions) {
                foreach (self::BASE_PERMISSIONS as $main => $item) {
                    if(!array_key_exists($main, $permissions)) {
                        $permissions[$main] = $item;
                        continue;
                    }
                    foreach ($item['permissions'] as $sub => $subPermissions) {
                        if(!array_key_exists($sub, $permissions[$main]['permissions'])) {
                            $permissions[$main]['permissions'][$sub] = $subPermissions;
                        }
                    }
                }

            }
            $this->update(['permissions' => $rolePermissions], ['uid' => $org->uid]);
        }
    }




    public function setChosenOrganisation(string|int $uid): void {
        $memberRow = Methods::organisationMembers()->first(['uuid' => __uuid(), 'organisation' => $uid]);
        Methods::organisationMembers()->setChosenOrganisation($memberRow);
    }


    public function createNewOrganisation(
        string $name,
        string $companyName,
        string $primaryEmail,
        string|int $companyCvr,
        string $companyLine1,
        string $companyCity,
        string|int $companyPostalCode,
        ?object $companyCountry,
        ?string $website = null,
        ?string $industry = null,
        ?string $description = null,
        ?string $contactEmail = null,
        null|int|string $contactPhone = null,
        ?string $contactPhoneCountryCode = null,
        ?string $defaultCurrency = null,
    ): ?string {
        $params = [
            "name" => $name,
            "primary_email" => $primaryEmail,
            "company_name" => $companyName,
            "cvr" => $companyCvr,
            "company_address" => [
                "line_1" => $companyLine1,
                "city" => $companyCity,
                "postal_code" => $companyPostalCode,
                "country" => $companyCountry?->code,
            ],
            "country" => $companyCountry?->uid,
            "website" => $website,
            "industry" => $industry,
            "contact_email" => $contactEmail,
            "contact_phone" => $contactPhone,
            "contact_phone_country_code" => $contactPhoneCountryCode,
            "description" => $description,
            "default_currency" => $defaultCurrency,
            "permissions" => [],
            "status" => "ACTIVE"
        ];
        foreach (Settings::$app->organisation_roles as $role) $params["permissions"][$role] = self::BASE_PERMISSIONS;

        if(!$this->create($params)) return null;
        return $this->recentUid;
    }



    public function updateOrganisationDetails(string|int $uid, array $params): bool {
        return $this->update($params, ["uid" => $uid]);
    }



    public function hasModifyPermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!OrganisationPermissions::__oModify($mainObject, $subObject)) return false;
        }
        return true;
    }
    public function hasReadPermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!OrganisationPermissions::__oRead($mainObject, $subObject)) return false;
        }
        return true;
    }
    public function hasDeletePermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!OrganisationPermissions::__oDelete($mainObject, $subObject)) return false;
        }
        return true;
    }


    /**
     * Get setup requirements status for an organisation
     * Returns object with status of Viva Wallet, locations, terminals, and published pages
     */
    public function getSetupRequirements(?string $organisationUid = null): object {
        if(isEmpty($organisationUid)) $organisationUid = __oUuid();

        $organisation = $this->first(['uid' => $organisationUid]);
        if(isEmpty($organisation)) return (object)[];

        // Check Viva Wallet status
        $hasMerchantId = !isEmpty($organisation->merchant_prid);
        $connectedAccount = Methods::vivaConnectedAccounts()->first(['organisation' => $organisationUid]);
        $vivaStatus = 'not_started'; // not_started, in_progress, completed

        if($hasMerchantId) {
            $vivaStatus = 'completed';
        } elseif(!isEmpty($connectedAccount) && !in_array($connectedAccount->state ?? '', ['VOID', 'REMOVED', ''])) {
            $vivaStatus = 'in_progress';
        }

        // Check if locations exist
        $locations = Methods::locations()->getByX(['uuid' => $organisationUid]);
        $hasLocations = $locations->count() > 0;

        // Check if any location has terminals
        $hasTerminals = false;
        foreach($locations->list() as $location) {
            if(Methods::terminals()->count(['location' => $location->uid]) > 0) {
                $hasTerminals = true;
                break;
            }
        }

        // Check if any location has a published page
        $hasPublishedPage = false;
        foreach($locations->list() as $location) {
            if(Methods::locationPages()->exists(['location' => $location->uid, 'state' => 'PUBLISHED'])) {
                $hasPublishedPage = true;
                break;
            }
        }

        return (object)[
            'viva_wallet' => (object)[
                'status' => $vivaStatus,
                'completed' => $vivaStatus === 'completed',
            ],
            'locations' => (object)[
                'status' => $hasLocations ? 'completed' : 'not_started',
                'completed' => $hasLocations,
            ],
            'terminals' => (object)[
                'status' => $hasTerminals ? 'completed' : 'not_started',
                'completed' => $hasTerminals,
            ],
            'published_page' => (object)[
                'status' => $hasPublishedPage ? 'completed' : 'not_started',
                'completed' => $hasPublishedPage,
            ],
            'all_completed' => $vivaStatus === 'completed' && $hasLocations && $hasTerminals && $hasPublishedPage,
            'has_incomplete' => $vivaStatus !== 'completed' || !$hasLocations || !$hasTerminals || !$hasPublishedPage,
        ];
    }

}