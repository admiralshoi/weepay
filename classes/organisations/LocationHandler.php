<?php

namespace classes\organisations;

use classes\utility\Crud;
use Database\Collection;
use Database\model\Locations;
use features\Settings;

class LocationHandler extends Crud {


    function __construct() {
        parent::__construct(Locations::newStatic(), "location");
    }


    public function setSource(string $uid, string $sourceCode): bool {
        return $this->update(['source_prid' => $sourceCode], ['uid' => $uid]);
    }

    public function getBySlug(string $slug, array $fields = []): ?object {
        return $this->getFirst(['slug' => $slug], $fields);
    }

    public function getMyLocations(?string $uuid = null, array $fields = []): Collection {
        if($uuid === null) $uuid = __oUuid();
        return $this->getByX(['uuid' => $uuid, 'status' => ['DRAFT', 'ACTIVE', 'INACTIVE']], $fields);
    }





    public function createNewLocation(
        string $organisationId,
        string $name,
        string $slug,
        string $caption,
        int $inheritParent,
        string|int $cvr,
        string $line1,
        string $city,
        string|int $postalCode,
        ?object $country,
        ?string $industry = null,
        ?string $description = null,
        ?string $contactEmail = null,
        null|int|string $contactPhone = null,
        ?string $contactPhoneCountryCode = null,
        ?array $openingHours = null,
        string $status = "DRAFT",
    ): ?string {
        $params = [
            "uuid" => $organisationId,
            "name" => $name,
            "slug" => $slug,
            "cvr" => $cvr,
            "address" => [
                "line_1" => $line1,
                "city" => $city,
                "postal_code" => $postalCode,
                "country" => $country?->code,
            ],
            "country" => $country?->uid,
            "inherit_details" => $inheritParent,
            "industry" => $industry,
            "contact_email" => $contactEmail,
            "contact_phone" => $contactPhone,
            "contact_phone_country_code" => $contactPhoneCountryCode,
            "description" => $description,
            "caption" => $caption,
            "status" => $status,
            "opening_hours" => $openingHours,
        ];


        foreach (Settings::$app->location_roles as $role) $params["permissions"][$role] = self::BASE_PERMISSIONS;

        if(!$this->create($params)) return null;
        return $this->recentUid;
    }



    public function generateUniqueSourceCode(): string {
        while(true) {
            $code = generateUniqueId(rand(5,6), "STRING_INT");
            if($this->exists(['source_prid' => $code]) !== true) return $code;
        }
    }







    const BASE_PERMISSIONS = [
        'general' => [
            'icon' => 'mdi mdi-view-grid-outline',
            'read' => true,
            'modify' => true,
            'delete' => true,
            "permissions" => [
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
    ];

}