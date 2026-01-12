<?php

namespace classes\organisations;

/**
 * Preset role permissions for location roles.
 * Each role has a tailored set of permissions based on their responsibilities.
 */
class LocationRolePermissions {

    /**
     * Store Manager - Full access to their location
     */
    private const STORE_MANAGER = [
        'general' => [
            'icon' => 'mdi mdi-view-grid-outline',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'locations' => ['read' => true, 'modify' => true, 'delete' => true],
                'checkout' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_members' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_roles' => ['read' => true, 'modify' => true, 'delete' => true],
                'role_permissions' => ['read' => true, 'modify' => true, 'delete' => true],
                'metrics' => ['read' => true, 'modify' => true, 'delete' => true],
                'orders' => ['read' => true, 'modify' => true, 'delete' => true],
                'payments' => ['read' => true, 'modify' => true, 'delete' => true],
                'customers' => ['read' => true, 'modify' => true, 'delete' => true],
                'settings' => ['read' => true, 'modify' => true, 'delete' => true],
                'terminals' => ['read' => true, 'modify' => true, 'delete' => true],
                'pages' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
    ];

    /**
     * Team Manager - Can manage team but limited settings/role access
     */
    private const TEAM_MANAGER = [
        'general' => [
            'icon' => 'mdi mdi-view-grid-outline',
            'read' => true,
            'modify' => true,
            'delete' => false,
            'permissions' => [
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
                'checkout' => ['read' => true, 'modify' => true, 'delete' => false],
                'team_members' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_roles' => ['read' => true, 'modify' => false, 'delete' => false],
                'role_permissions' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'orders' => ['read' => true, 'modify' => true, 'delete' => false],
                'payments' => ['read' => true, 'modify' => true, 'delete' => false],
                'customers' => ['read' => true, 'modify' => true, 'delete' => false],
                'settings' => ['read' => true, 'modify' => false, 'delete' => false],
                'terminals' => ['read' => true, 'modify' => false, 'delete' => false],
                'pages' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
    ];

    /**
     * Cashier - Basic operational access for day-to-day sales
     */
    private const CASHIER = [
        'general' => [
            'icon' => 'mdi mdi-view-grid-outline',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
                'checkout' => ['read' => true, 'modify' => true, 'delete' => false],
                'team_members' => ['read' => false, 'modify' => false, 'delete' => false],
                'team_invitations' => ['read' => false, 'modify' => false, 'delete' => false],
                'team_roles' => ['read' => false, 'modify' => false, 'delete' => false],
                'role_permissions' => ['read' => false, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'orders' => ['read' => true, 'modify' => true, 'delete' => false],
                'payments' => ['read' => true, 'modify' => true, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'terminals' => ['read' => true, 'modify' => false, 'delete' => false],
                'pages' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
    ];

    /**
     * Get permissions for a specific role
     */
    public static function getForRole(string $role): array {
        return match($role) {
            'store_manager' => self::STORE_MANAGER,
            'team_manager' => self::TEAM_MANAGER,
            'cashier' => self::CASHIER,
            default => LocationHandler::BASE_PERMISSIONS,
        };
    }

    /**
     * Get all role permissions as an associative array
     */
    public static function getAllRolePermissions(): array {
        return [
            'store_manager' => self::STORE_MANAGER,
            'team_manager' => self::TEAM_MANAGER,
            'cashier' => self::CASHIER,
        ];
    }

    /**
     * Get list of fixed/system role names that cannot be removed
     */
    public static function getFixedRoles(): array {
        return ['store_manager', 'team_manager', 'cashier'];
    }
}
