<?php

namespace classes\organisations;

/**
 * Preset role permissions for organisation roles.
 * Each role has a tailored set of permissions based on their responsibilities.
 */
class OrganisationRolePermissions {

    /**
     * Owner - Full access to everything
     */
    private const OWNER = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => true, 'delete' => true],
                'wallet' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'members' => ['read' => true, 'modify' => true, 'delete' => true],
                'invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'roles' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'permissions' => ['read' => true, 'modify' => true, 'delete' => true],
                'roles' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'locations' => [
            'icon' => 'mdi mdi-store-outline',
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
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'orders' => ['read' => true, 'modify' => true, 'delete' => true],
                'payments' => ['read' => true, 'modify' => true, 'delete' => true],
                'metrics' => ['read' => true, 'modify' => true, 'delete' => true],
                'customers' => ['read' => true, 'modify' => true, 'delete' => true],
                'settings' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => true, 'delete' => true],
                'reports' => ['read' => true, 'modify' => true, 'delete' => true],
                'customers' => ['read' => true, 'modify' => true, 'delete' => true],
                'metrics' => ['read' => true, 'modify' => true, 'delete' => true],
                'locations' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
    ];

    /**
     * Admin - Full access except billing deletion and some sensitive operations
     */
    private const ADMIN = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => true,
            'modify' => true,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => true, 'delete' => false],
                'wallet' => ['read' => true, 'modify' => true, 'delete' => false],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'members' => ['read' => true, 'modify' => true, 'delete' => true],
                'invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'roles' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'permissions' => ['read' => true, 'modify' => true, 'delete' => true],
                'roles' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'locations' => [
            'icon' => 'mdi mdi-store-outline',
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
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'orders' => ['read' => true, 'modify' => true, 'delete' => true],
                'payments' => ['read' => true, 'modify' => true, 'delete' => true],
                'metrics' => ['read' => true, 'modify' => true, 'delete' => true],
                'customers' => ['read' => true, 'modify' => true, 'delete' => true],
                'settings' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => true,
            'modify' => true,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => true, 'delete' => false],
                'reports' => ['read' => true, 'modify' => true, 'delete' => true],
                'customers' => ['read' => true, 'modify' => true, 'delete' => true],
                'metrics' => ['read' => true, 'modify' => true, 'delete' => true],
                'locations' => ['read' => true, 'modify' => true, 'delete' => true],
            ]
        ],
    ];

    /**
     * Team Manager - Manages team members but no billing or role permission changes
     */
    private const TEAM_MANAGER = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'wallet' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => true,
            'modify' => true,
            'delete' => true,
            'permissions' => [
                'members' => ['read' => true, 'modify' => true, 'delete' => true],
                'invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'roles' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'permissions' => ['read' => true, 'modify' => false, 'delete' => false],
                'roles' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
        'locations' => [
            'icon' => 'mdi mdi-store-outline',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
                'checkout' => ['read' => true, 'modify' => false, 'delete' => false],
                'team_members' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_invitations' => ['read' => true, 'modify' => true, 'delete' => true],
                'team_roles' => ['read' => true, 'modify' => false, 'delete' => false],
                'role_permissions' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'orders' => ['read' => true, 'modify' => false, 'delete' => false],
                'payments' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => true, 'modify' => false, 'delete' => false],
                'terminals' => ['read' => true, 'modify' => false, 'delete' => false],
                'pages' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'orders' => ['read' => true, 'modify' => false, 'delete' => false],
                'payments' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => false, 'delete' => false],
                'reports' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
    ];

    /**
     * Analyst - Read-only access to metrics, orders, customers for reporting purposes
     */
    private const ANALYST = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => true, 'modify' => false, 'delete' => false],
                'wallet' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'members' => ['read' => false, 'modify' => false, 'delete' => false],
                'invitations' => ['read' => false, 'modify' => false, 'delete' => false],
                'roles' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'permissions' => ['read' => false, 'modify' => false, 'delete' => false],
                'roles' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'locations' => [
            'icon' => 'mdi mdi-store-outline',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
                'checkout' => ['read' => false, 'modify' => false, 'delete' => false],
                'team_members' => ['read' => false, 'modify' => false, 'delete' => false],
                'team_invitations' => ['read' => false, 'modify' => false, 'delete' => false],
                'team_roles' => ['read' => false, 'modify' => false, 'delete' => false],
                'role_permissions' => ['read' => false, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'orders' => ['read' => true, 'modify' => false, 'delete' => false],
                'payments' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'terminals' => ['read' => false, 'modify' => false, 'delete' => false],
                'pages' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'orders' => ['read' => true, 'modify' => false, 'delete' => false],
                'payments' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => true,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'reports' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'locations' => ['read' => true, 'modify' => false, 'delete' => false],
            ]
        ],
    ];

    /**
     * Location Employee - Limited to location-specific operations only
     * No access to organisation-level settings, billing, or team management
     */
    private const LOCATION_EMPLOYEE = [
        'billing' => [
            'icon' => 'mdi mdi-wallet',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'wallet' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'team' => [
            'icon' => 'fa-solid fa-users',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'members' => ['read' => false, 'modify' => false, 'delete' => false],
                'invitations' => ['read' => false, 'modify' => false, 'delete' => false],
                'roles' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'roles' => [
            'icon' => 'mdi mdi-shield',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'permissions' => ['read' => false, 'modify' => false, 'delete' => false],
                'roles' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'locations' => [
            'icon' => 'mdi mdi-store-outline',
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
        'orders' => [
            'icon' => 'mdi mdi-cart-outline',
            'read' => true,
            'modify' => true,
            'delete' => false,
            'permissions' => [
                'orders' => ['read' => true, 'modify' => true, 'delete' => false],
                'payments' => ['read' => true, 'modify' => true, 'delete' => false],
                'metrics' => ['read' => true, 'modify' => false, 'delete' => false],
                'customers' => ['read' => true, 'modify' => false, 'delete' => false],
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
        'organisation' => [
            'icon' => 'fa-solid fa-building',
            'read' => false,
            'modify' => false,
            'delete' => false,
            'permissions' => [
                'settings' => ['read' => false, 'modify' => false, 'delete' => false],
                'reports' => ['read' => false, 'modify' => false, 'delete' => false],
                'customers' => ['read' => false, 'modify' => false, 'delete' => false],
                'metrics' => ['read' => false, 'modify' => false, 'delete' => false],
                'locations' => ['read' => false, 'modify' => false, 'delete' => false],
            ]
        ],
    ];

    /**
     * Get permissions for a specific role
     */
    public static function getForRole(string $role): array {
        return match($role) {
            'owner' => self::OWNER,
            'admin' => self::ADMIN,
            'team_manager' => self::TEAM_MANAGER,
            'analyst' => self::ANALYST,
            'location_employee' => self::LOCATION_EMPLOYEE,
            default => OrganisationHandler::BASE_PERMISSIONS,
        };
    }

    /**
     * Get all role permissions as an associative array
     */
    public static function getAllRolePermissions(): array {
        return [
            'owner' => self::OWNER,
            'admin' => self::ADMIN,
            'team_manager' => self::TEAM_MANAGER,
            'analyst' => self::ANALYST,
            'location_employee' => self::LOCATION_EMPLOYEE,
        ];
    }
}
