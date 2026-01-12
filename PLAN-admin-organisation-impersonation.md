# Plan: Admin Organisation Impersonation

> **STATUS: IMPLEMENTED** - Completed on <?=date('Y-m-d')?>


## Goal
Allow system admins to fully manage any organisation as if they were the owner, enabling them to help with onboarding, troubleshooting, and support.

---

## Current Architecture Summary

### Identity Resolution
- `__uuid()` → Returns `$_SESSION["uid"]` (current user's ID)
- `__oid()` → Returns `Settings::$organisation->organisation->uid` (current organisation ID)
- `__oUuid()` → Returns either `__oid()` (merchants) or `__uuid()` (consumers) based on role

### Organisation Context Flow
1. User logs in → `$_SESSION["uid"]` set
2. `features/init.php` loads `Settings::$user`
3. Organisation ID read from user's cookies
4. `OrganisationMemberHandler::setChosenOrganisation()` sets `Settings::$organisation`
5. All merchant pages use `Settings::$organisation` for context

### Permission System
- `memberHasPermission()` already returns `true` for admins (line 83 in OrganisationMemberHandler.php)
- Roles: owner, admin, team_manager, analyst, location_employee
- Permissions are hierarchical: billing, team, locations, etc.

### Middleware
- `merchant()` middleware checks `Methods::isMerchant()` (access_level = 2)
- Routes are grouped under this middleware

---

## Options Analysis

### Option A: "View As Organisation" Session Override (Recommended)

**Concept**: Admin clicks "View as Organisation" button on admin panel, which sets a session variable that overrides organisation context.

**Implementation**:
1. Add `$_SESSION["admin_impersonating_org"]` variable
2. Modify `features/init.php` to check this session variable for admins
3. When set, load that organisation's context into `Settings::$organisation`
4. Add new middleware `adminOrMerchant` that allows both roles
5. Create admin UI button to enter/exit impersonation mode
6. Show visual indicator when impersonating

**Pros**:
- Minimal code changes to existing merchant controllers
- Uses existing permission system (already bypasses for admins)
- Clear audit trail (can log impersonation sessions)
- Easy to exit impersonation mode
- No database changes required

**Cons**:
- Session-based (lost on logout)
- Need to add `adminOrMerchant` middleware to merchant routes

**Files to modify**:
- `features/init.php` - Check for impersonation session
- `features/Settings.php` - Add `$impersonatingOrganisation` flag
- `routing/middleware/auth.php` - Add `adminOrMerchant()` middleware
- `routing/web.php` - Update merchant route groups to use new middleware
- `views/admin/organisations/detail.php` - Add "View as Organisation" button
- `views/templates/nav/merchant/` - Show impersonation indicator
- New API endpoint for starting/stopping impersonation

---

### Option B: Invisible Admin Member Role

**Concept**: Create a hidden "system_admin" role on each organisation that grants full permissions, automatically assigned to platform admins.

**Implementation**:
1. Add new role type "system_admin" to OrganisationRolePermissions
2. Auto-create OrganisationMembers record for admins when they access an org
3. Modify role display to hide "system_admin" from organisation team views
4. Admin becomes a "member" of the organisation with invisible full access

**Pros**:
- Works with existing middleware (admin becomes a merchant member)
- No session override needed
- Persistent across sessions

**Cons**:
- Creates database records for every admin-org interaction
- Pollutes OrganisationMembers table
- Complex cleanup if admin is removed
- Could cause confusion in member counts/reports
- Need to filter out system_admin from all team views
- `__uuid()` still returns admin's ID, not a merchant's

---

### Option C: Dual Middleware Pattern

**Concept**: Add `adminOrMerchant` middleware to all merchant routes, and have admins select an organisation context from admin panel.

**Implementation**:
1. Create `adminOrMerchant()` middleware
2. Store selected organisation in admin's session
3. Modify `__oid()` and `__oUuid()` to check for admin override
4. Update all merchant route groups to use new middleware

**Pros**:
- Explicit route-level control
- Clear separation of concerns

**Cons**:
- Requires updating every merchant route group
- Still need session-based organisation selection
- Essentially same as Option A but more work

---

## Recommended Approach: Option A

Option A is cleanest because:
1. **Minimal database changes** - No new tables or polluted member records
2. **Leverages existing permission system** - `memberHasPermission()` already returns true for admins
3. **Clear UX** - Button to enter/exit, visual indicator when impersonating
4. **Audit capability** - Can log when admins enter impersonation mode
5. **Safe** - Easy to exit, session-based so auto-clears on logout

---

## Detailed Implementation Plan (Option A)

### Phase 1: Core Infrastructure

#### 1.1 Add Settings Flag
**File**: `features/Settings.php`
```php
public static bool $impersonatingOrganisation = false;
public static ?string $impersonatedOrganisationId = null;
```

#### 1.2 Modify Organisation Context Loading
**File**: `features/init.php`

After loading the user, check if admin is impersonating:
```php
// After Settings::$user is loaded
if (Methods::isAdmin() && !empty($_SESSION["admin_impersonating_org"])) {
    $orgId = $_SESSION["admin_impersonating_org"];
    $org = Methods::organisations()->get($orgId);
    if ($org) {
        // Create a synthetic member object with owner permissions
        Settings::$organisation = (object)[
            'uid' => 'admin_impersonation',
            'uuid' => Settings::$user,
            'organisation' => $org,
            'role' => 'owner',
            'status' => 'ACTIVE',
            'scoped_locations' => null,
            'invitation_status' => 'ACCEPTED'
        ];
        Settings::$impersonatingOrganisation = true;
        Settings::$impersonatedOrganisationId = $orgId;
    }
}
```

#### 1.3 Add New Middleware
**File**: `routing/middleware/auth.php`
```php
function adminOrMerchant(): bool {
    return Methods::isAdmin() || Methods::isMerchant();
}
```

### Phase 2: Route Updates

#### 2.1 Update Merchant Route Groups
**File**: `routing/web.php`

Change merchant route groups from:
```php
Routes::group(['requiresLogin', 'merchant'], function() { ... });
```
To:
```php
Routes::group(['requiresLogin', 'adminOrMerchant'], function() { ... });
```

**Note**: Only update routes that make sense for admin access. Skip consumer-specific routes.

### Phase 3: Admin UI

#### 3.1 Add Impersonation Button
**File**: `views/admin/organisations/detail.php`

Add button:
```php
<form action="<?=__url(Links::$api->admin->impersonateOrganisation)?>" method="POST">
    <input type="hidden" name="organisation" value="<?=$organisation->uid?>">
    <button type="submit" class="btn btn-primary">
        <i class="mdi mdi-account-switch"></i> Se som organisation
    </button>
</form>
```

#### 3.2 Add Impersonation API Endpoints
**File**: `routing/routes/api/AdminController.php`

```php
public static function startImpersonation(array $args): array {
    if (!Methods::isAdmin()) return ["return_as" => 403];

    $orgId = $args['organisation'] ?? null;
    if (!$orgId) return ["return_as" => "json", "result" => ["error" => "Missing organisation"], "response_code" => 400];

    $org = Methods::organisations()->get($orgId);
    if (!$org) return ["return_as" => "json", "result" => ["error" => "Organisation not found"], "response_code" => 404];

    $_SESSION["admin_impersonating_org"] = $orgId;

    // Log the impersonation for audit
    debugLog(['admin' => __uuid(), 'organisation' => $orgId, 'action' => 'start'], 'ADMIN_IMPERSONATION');

    return ["return_as" => "json", "result" => ["success" => true, "redirect" => Links::$merchant->dashboard]];
}

public static function stopImpersonation(array $args): array {
    if (!Methods::isAdmin()) return ["return_as" => 403];

    $orgId = $_SESSION["admin_impersonating_org"] ?? null;
    unset($_SESSION["admin_impersonating_org"]);

    debugLog(['admin' => __uuid(), 'organisation' => $orgId, 'action' => 'stop'], 'ADMIN_IMPERSONATION');

    return ["return_as" => "json", "result" => ["success" => true, "redirect" => Links::$admin->organisations]];
}
```

#### 3.3 Add Visual Indicator
**File**: `views/templates/nav/merchant/top-nav.php` (or equivalent)

```php
<?php if (Settings::$impersonatingOrganisation): ?>
    <div class="impersonation-banner bg-warning text-dark p-2 text-center">
        <i class="mdi mdi-alert"></i>
        Du ser som: <strong><?=Settings::$organisation->organisation->name?></strong>
        <a href="<?=__url(Links::$api->admin->stopImpersonation)?>" class="btn btn-sm btn-dark ml-2">
            <i class="mdi mdi-exit-to-app"></i> Afslut
        </a>
    </div>
<?php endif; ?>
```

### Phase 4: Safety & Audit

#### 4.1 Add Audit Logging
Create dedicated log for impersonation events in `logs/admin/impersonation.log`

#### 4.2 Add Timeout (Optional)
Auto-clear impersonation session after X minutes of inactivity

#### 4.3 Restrict Dangerous Operations (Optional)
Even in impersonation mode, prevent:
- Deleting the organisation
- Changing billing/payment details
- Modifying owner account

---

## Files Summary

| File | Change |
|------|--------|
| `features/Settings.php` | Add `$impersonatingOrganisation` and `$impersonatedOrganisationId` |
| `features/init.php` | Check for impersonation session, set synthetic member |
| `routing/middleware/auth.php` | Add `adminOrMerchant()` middleware |
| `routing/web.php` | Update merchant route groups |
| `routing/routes/api/AdminController.php` | Add start/stop impersonation endpoints |
| `routing/paths/constants/Api.php` | Add new API routes for impersonation |
| `classes/enumerations/Links.php` | Add impersonation links |
| `views/admin/organisations/detail.php` | Add "View as Organisation" button |
| `views/templates/nav/merchant/top-nav.php` | Add impersonation indicator banner |

---

## Questions to Resolve

1. **Should impersonation timeout?** After X minutes of inactivity, auto-exit impersonation mode?

2. **Which routes to open?** All merchant routes, or a subset? Some routes may not make sense for admin access.

3. **Restrict any operations?** Should admins be prevented from certain actions even when impersonating (e.g., deleting the org, changing payment methods)?

4. **Audit requirements?** How detailed should impersonation logging be? Just start/stop, or every action taken while impersonating?

5. **Multiple admins?** Can multiple admins impersonate the same organisation simultaneously?

---

## Next Steps

1. Review this plan and answer the questions above
2. Implement Phase 1 (core infrastructure)
3. Implement Phase 2 (route updates)
4. Implement Phase 3 (admin UI)
5. Test thoroughly with a test organisation
6. Add Phase 4 safety measures as needed
