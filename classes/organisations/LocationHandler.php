<?php

namespace classes\organisations;

use classes\Methods;
use classes\utility\Crud;
use Database\Collection;
use Database\model\Locations;
use features\Settings;
use stdClass;

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
        $ids = $this->userLocationPredicate();
        return $this->getByX(['uuid' => $uuid, 'status' => ['DRAFT', 'ACTIVE', 'INACTIVE']], $fields, ['uid' => $ids]);
    }

    public function userLocationPredicate(): array {
        if(isEmpty(Settings::$organisation)) return [];
        $scope = toArray(Settings::$organisation->scoped_locations);
        if(isEmpty($scope)) return [];

        // Get location memberships, but exclude suspended/deleted ones
        $locMemberHandler = Methods::locationMembers();
        $activeLocMemberIds = $locMemberHandler->queryBuilder()
            ->where('uuid', __uuid())
            ->where('status', MemberEnum::MEMBER_ACTIVE)
            ->pluck("location");

        // Get suspended/deleted location memberships to exclude from scope
        $inactiveLocMemberIds = $locMemberHandler->queryBuilder()
            ->where('uuid', __uuid())
            ->where('status', [MemberEnum::MEMBER_SUSPENDED, MemberEnum::MEMBER_DELETED])
            ->pluck("location");

        // Start with scoped locations, remove any that are suspended/deleted
        $allowedLocations = array_diff($scope, $inactiveLocMemberIds);

        // Add active location memberships
        if(!empty($activeLocMemberIds)) {
            $allowedLocations = array_merge($allowedLocations, $activeLocMemberIds);
        }

        return array_values(array_unique($allowedLocations));
    }



    public function locationAddress(?object $location): object {
        if(isEmpty($location)) return new StdClass();
        $address = $location->address ?? new StdClass();
        if($location->inherit_details ?? false) {
            if(is_string($location->uuid)) $location->uuid = Methods::organisations()->get($location->uuid, ['company_address']);
            $orgAddress = $location->uuid->company_address ?? new StdClass();
            // Inherit missing fields from organisation
            if(isEmpty($address?->line_1) && !isEmpty($orgAddress?->line_1 ?? null)) $address->line_1 = $orgAddress?->line_1;
            if(isEmpty($address?->city) && !isEmpty($orgAddress?->city ?? null)) $address->city = $orgAddress?->city;
            if(isEmpty($address?->postal_code) && !isEmpty($orgAddress?->postal_code ?? null)) $address->postal_code = $orgAddress?->postal_code;
            if(isEmpty($address?->country) && !isEmpty($orgAddress?->country ?? null)) $address->country = $orgAddress?->country;
        }
        return $address;
    }

    public function contactPhone(?object $location): ?string {
        if(!isEmpty($location->contact_phone)) {
            $phone = $location->contact_phone;
            $callerCode = $location->contact_phone_country_code;
        }
        else {
            if(!$location->inherit_details) return null;
            if(is_string($location->uuid)) $location->uuid = Methods::organisations()->get($location->uuid, ['contact_phone']);
            $phone = $location->uuid->contact_phone;
            $callerCode = $location->uuid->contact_phone_country_code;
        }

        if(empty($phone)) return null;
        return "+" . Methods::misc()::callerCode($callerCode) . " " . $phone;
    }
    public function contactEmail(?object $location): ?string {
        if(!isEmpty($location->contact_email)) $email = $location->contact_email;
        else {
            if(!$location->inherit_details) return null;
            if(is_string($location->uuid)) $location->uuid = Methods::organisations()->get($location->uuid, ['contact_email']);
            $email = $location->uuid->contact_email;
        }

        return $email;
    }
    public function tradingCurrency(?object $location): ?string {
        if(!isEmpty($location->default_currency)) $currency = $location->default_currency;
        else {
            if(!$location->inherit_details) return null;
            if(is_string($location->uuid)) $location->uuid = Methods::organisations()->get($location->uuid, ['default_currency']);
            $currency = $location->uuid->default_currency;
        }

        return $currency;
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
        ?string $defaultCurrency = null,
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
            "default_currency" => $defaultCurrency,
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


    /**
     * Check if a location can accept payments
     *
     * @param object|string $location Location object or UID
     * @return array ['canAccept' => bool, 'reason' => string|null]
     */
    public function canAcceptPayments(object|string $location): array {
        // Get location object if UID was passed
        if(is_string($location)) {
            $location = $this->get($location);
            if(isEmpty($location)) {
                return ['canAccept' => false, 'reason' => 'location_not_found'];
            }
        }

        // Check location status
        if($location->status !== 'ACTIVE') {
            return ['canAccept' => false, 'reason' => 'location_inactive'];
        }

        if(!Methods::locationPages()->exists(['location' => $location->uid, 'state' => 'PUBLISHED'])) {
            return ['canAccept' => false, 'reason' => 'no_published_page'];
        }
        // Check location has payment source
        if(isEmpty($location->source_prid)) {
            return ['canAccept' => false, 'reason' => 'no_payment_source'];
        }

        // Check organisation status
        $organisation = is_object($location->uuid) ? $location->uuid : Methods::organisations()->get($location->uuid);
        if(isEmpty($organisation)) {
            return ['canAccept' => false, 'reason' => 'organisation_not_found'];
        }

        if($organisation->status !== 'ACTIVE') {
            return ['canAccept' => false, 'reason' => 'organisation_inactive'];
        }

        // Check organisation has merchant ID
        if(isEmpty($organisation->merchant_prid)) {
            return ['canAccept' => false, 'reason' => 'no_merchant_id'];
        }

        return ['canAccept' => true, 'reason' => null];
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