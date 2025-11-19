<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use classes\utility\Titles;
use Database\Collection;
use Database\model\Organisations;
use features\Settings;

class OrganisationHandler extends Crud {


    const BASE_PERMISSIONS = [
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
                'employees' => [
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






    public function setChosenOrganisation(string|int $uid): void {
        $memberRow = Methods::organisationMembers()->first(['uuid' => __uuid(), 'organisation' => $uid]);
        Methods::organisationMembers()->setChosenOrganisation($memberRow);
    }


    public function createNewOrganisation(
        string $name,
        string $companyName,
        string|int $companyCvr,
        string $companyLine1,
        string $companyCity,
        string|int $companyPostalCode,
        string $companyCountry,
        ?string $website = null,
        ?string $industry = null,
        ?string $contactEmail = null,
        null|int|string $contactPhone = null,
        ?string $description = null,
    ): ?string {
        $params = [
            "name" => $name,
            "company_name" => $companyName,
            "cvr" => $companyCvr,
            "company_address" => [
                "line_1" => $companyLine1,
                "city" => $companyCity,
                "postal_code" => $companyPostalCode,
                "country" => $companyCountry,
            ],
            "country" => $companyCountry,
            "website" => $website,
            "industry" => $industry,
            "contact_email" => $contactEmail,
            "contact_phone" => $contactPhone,
            "description" => $description,
            "permissions" => [],
            "status" => "ACTIVE"
        ];
        foreach (Settings::$app->organisation_roles as $role) $params["permissions"][$role] = self::BASE_PERMISSIONS;

        if(!$this->create($params)) return null;
        return $this->recentUid;
    }



    public function updateOrganisationDetails(string|int $uid, array $args): bool {
        $params = [];
        foreach ([
            "name", "industry", "description", "website", "contact_email",
            "team_settings", "general_settings", "pictures", "permissions"
        ] as $key) if(array_key_exists($key, $args)) $params[$key] = $args[$key];

        return $this->update($params, ["uid" => $uid]);
    }



    public function hasModifyPermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!__oModify($mainObject, $subObject)) return false;
        }
        return true;
    }
    public function hasReadPermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!__oRead($mainObject, $subObject)) return false;
        }
        return true;
    }
    public function hasDeletePermissions(string $organisationId, string $mainObject, string $subObject = ''): bool {
        if(!Methods::organisationMembers()->userIsMember($organisationId)) return false;
        if($organisationId !== __oUuid()) Methods::organisations()->setChosenOrganisation($organisationId);
        if(str_starts_with($organisationId, Organisations::$uidPrefix)) {
            if(!__oDelete($mainObject, $subObject)) return false;
        }
        return true;
    }

}