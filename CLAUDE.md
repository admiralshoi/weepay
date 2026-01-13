# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Core Principle - Use Existing Patterns

**CRITICAL: This codebase has established patterns for all core functionality. NEVER invent custom solutions when a system already exists.**

Before implementing anything, check if there's an existing pattern:

| Task | Use This | NOT This |
|------|----------|----------|
| Database queries | Models (`Database/model/`) | Raw PDO/SQL |
| AJAX requests | `post()`, `get()`, `del()` from `server.js` | Native `fetch()` |
| URL generation | `Links` class (`classes/enumerations/Links`) | Hardcoded strings |
| Page routing | `routing/web.php` with Controllers | Custom routing |
| Asset loading | Path Constants (`routing/paths/constants/`) | Inline `<script>`/`<link>` |
| Business logic | Handler classes via `Methods::` | Direct Model calls in controllers |
| Response handling | `Response()->json...()` methods | Manual `echo json_encode()` |

**When building new features:**
1. Find a similar existing feature in the codebase
2. Follow its exact patterns for structure, naming, and organization
3. Use the same classes/methods it uses
4. If unsure, search for how existing code handles the same type of task

## Project Overview

WeePay is a PHP-based payment platform with POS (Point of Sale) functionality. It's a multi-tenant system supporting merchants and consumers, with features including checkout flows, terminal management, location management, and order processing. The application uses OIDC authentication (MitID) and integrates with payment providers.

## Development Environment

### Local Development
- **URL**: `https://localhost/weepay/`
- **Document Root**: `/Users/admiralshoi/Sites/weepay/`
- **Testing Environment**: Add `/testing` to URL path (e.g., `https://localhost/weepay/testing/`)

### Live Environment
- **URL**: `https://wee-pay.dk/`
- **Testing Environment**: `https://wee-pay.dk/testing/`

### Configuration
- **Database Config**: `env/db/local.php` (local) or `env/db/live.php` (production)
- **Encryption Details**: `env/encryption/details.php`
- **Application Config**: `env/other/config.php`
- **Timezone**: Europe/Copenhagen (GMT+1)

## Architecture

### Request Flow
1. All requests route through `index.php` (via `.htaccess` rewrite rules)
2. `index.php` loads: config → autoload → vendor autoload → routing/web.php
3. Routes are defined in `routing/web.php` using the custom `Routes` class
4. Middleware executes before controllers
5. Controllers return arrays with `return_as` key determining output format

### Directory Structure
- **`Database/`** - Custom ORM with Model, QueryBuilder, Schema, Connection classes
  - **`model/`** - Model classes (Users, Organisations, Orders, etc.)
- **`routing/`** - Routing system and controllers
  - **`routes/`** - Controller classes organized by feature (auth, merchants, flows, etc.)
  - **`middleware/`** - Authentication and authorization middleware
- **`classes/`** - Utility classes, enumerations, HTTP helpers
  - **`enumerations/`** - Links class for URL management
  - **`Methods.php`** - Primary utility class for business logic
- **`features/`** - Business logic and feature implementations
  - **`init.php`** - Application initialization (user sessions, settings)
  - **`Settings.php`** - Global settings class
  - **`functions.php`** - Global helper functions
- **`views/`** - PHP templates organized by feature
- **`public/`** - Static assets (accessible directly via URL)
- **`env/`** - Configuration files (db, encryption, other)
- **`logs/`** - Application logs

## Database System

### Custom ORM
The application uses a custom ORM built on PDO (MySQL).

**Model Definition Example**:
```php
namespace Database\model;

class Users extends \Database\Model {
    public static ?string $uidPrefix = null;
    protected static array $schema = [
        "uid" => "string",
        "email" => ["type" => "string", "default" => null, "nullable" => true],
        "access_level" => "integer",
    ];
    public static array $uniques = ["uid", "email"];
    public static array $encodeColumns = ["cookies"];
    public static array $encryptedColumns = ["address_country"];
}
```

**Query Examples**:
```php
// Get all users
Users::all()->list();

// Where clause
Users::where("email", "user@example.com")->first();

// Complex queries
Users::where("access_level", ">", 5)->order("created_at", "DESC")->limit(10)->all()->list();

// Insert
Users::insert(["uid" => "123", "email" => "user@example.com"]);

// Update
Users::where("uid", "123")->update(["email" => "newemail@example.com"]);

// Delete
Users::where("uid", "123")->delete();
```

### Model Features
- **Schema Definition**: Define columns with types, defaults, nullable
- **Unique Constraints**: Defined in `$uniques` array
- **Encoded Columns**: JSON-serialized columns (defined in `$encodeColumns`)
- **Encrypted Columns**: Encrypted storage (defined in `$encryptedColumns`)
- **Required Rows**: Seed data in `$requiredRows` array

### Foreign Keys - CRITICAL
**IMPORTANT**: Before fetching data from any Model, ALWAYS check the Model file for `foreignkeys()` method. Foreign key columns are **automatically resolved as full objects**, not raw string values.

**Example - AuthLocal Model**:
```php
// In Database/model/AuthLocal.php
public static function foreignkeys(): array {
    return [
        "user" => [Users::tableColumn("uid"), Users::newStatic()]
    ];
}
```

This means when you fetch an AuthLocal record, `$authLocal->user` is a **Users object**, NOT a string UID!

**Common Mistake**:
```php
// WRONG - $existingAuth->user is an object, not a string!
$existingAuth = Methods::localAuthentication()->getFirst(['username' => $username]);
if($existingAuth && $existingAuth->user !== __uuid()) {  // BROKEN - comparing object to string
    // This will never match correctly
}

// CORRECT Option 1 - Access the resolved object's UID
if($existingAuth && $existingAuth->user->uid !== __uuid()) {
    // Works correctly
}

// CORRECT Option 2 - Exclude foreign keys to get raw values (more efficient)
$existingAuth = Methods::localAuthentication()->excludeForeignKeys()->getFirst(['username' => $username]);
if($existingAuth && $existingAuth->user !== __uuid()) {  // Now $existingAuth->user is a string
    // Works correctly
}
```

**Best Practices**:
1. Always check the Model's `foreignkeys()` method before accessing properties
2. Use `->excludeForeignKeys()` on the handler when you only need raw IDs (avoids unnecessary JOINs)
3. When you need the related object's data, let it resolve and access via `->foreignKeyColumn->property`
4. WHERE clauses work with raw values - `getFirst(['user' => __uuid()])` is fine

### Database Migrations
- Schema changes are managed through `Database/Schema.php` and `Database/SchemaManager.php`
- Migration routes available at `/migration/init`, `/migration/db` (admin only)

### Handler Classes and Crud Pattern
**IMPORTANT**: Always use Handler classes over Model classes directly in controllers and business logic.

Each Model typically has a corresponding Handler class in `classes/` that extends `Crud`:
- Access handlers via `Methods::handlerName()` (e.g., `Methods::users()`, `Methods::orders()`, `Methods::twoFactorAuth()`)
- Handlers provide business logic, access control, and consistent data operations

**Crud Class Methods** (`classes/utility/Crud.php`):
Use Crud methods instead of QueryBuilder where possible:
```php
// PREFERRED - Using Crud methods
$handler->get($uid);                           // Get by UID
$handler->getFirst(['column' => 'value']);     // Get first matching record
$handler->getByX(['column' => 'value']);       // Get all matching records
$handler->exists(['column' => 'value']);       // Check if exists
$handler->create(['column' => 'value']);       // Insert new record
$handler->update($data, $identifier);          // Update records
$handler->delete(['column' => 'value']);       // Delete records
$handler->count(['column' => 'value']);        // Count records

// For complex queries with ordering
$handler->getByXOrderBy('column', 'DESC', ['filter' => 'value']);
$handler->getFirstOrderBy('column', 'DESC', ['filter' => 'value']);

// When QueryBuilder is needed (complex conditions like !=, >, <, OR groups)
$query = $handler->queryBuilder()
    ->where('column', '!=', 'value')
    ->where('other', '>', 5);
$result = $handler->queryGetFirst($query);  // Use queryGetFirst for single result
$results = $handler->queryGetAll($query);   // Use queryGetAll for collections
```

**When to use QueryBuilder directly**:
- Complex conditions (`!=`, `>`, `<`, `LIKE`, etc.)
- OR groups with `startGroup('OR')` / `endGroup()`
- Joins or subqueries
- Always use `queryGetFirst()` or `queryGetAll()` for fetching to include foreign key resolution

**Example - Correct Pattern**:
```php
// In a controller - use handler via Methods
$user = Methods::users()->get($userId);
$orders = Methods::orders()->getByX(['user' => $userId, 'status' => 'COMPLETED']);
Methods::twoFactorAuth()->delete(['user' => $userId, 'purpose' => 'phone_verification']);

// DON'T do this in controllers:
$user = \Database\model\Users::where('uid', $userId)->first();  // Wrong - use handler
```

## Routing System

### Defining Routes
Routes are defined in `routing/web.php`:

```php
// Basic route
Routes::get("/path", "ControllerName::methodName");

// Route with middleware
Routes::get("/path", "ControllerName::methodName", ['requiresLogin']);

// Route with parameters
Routes::get("/user/{id}/profile", "UserController::profile");

// Grouped routes with shared middleware
Routes::group(['requiresLogin', 'merchant'], function() {
    Routes::get("/dashboard", "merchants.pages.PageController::dashboard");
});

// API routes
Routes::group(['api', 'requiresApiLogin'], function() {
    Routes::post("/api/user/update", "api.UserController::update");
});
```

### Route Controller Pattern
Controllers are located in `routing/routes/` and organized by feature:
- Use dot notation in route definition: `"auth.PageController::login"`
- This maps to: `routing\routes\auth\PageController::login()`

### Controller Response Format
Controllers must return arrays with specific keys:

```php
// Return view
return ["return_as" => "view", "result" => ["view" => "viewname", "data" => [...]]];

// Return JSON (API)
return ["return_as" => "json", "result" => [...], "response_code" => 200];

// Return HTML
return ["return_as" => "html", "result" => "<html>...</html>"];

// Return 404
return ["return_as" => 404];
```

### Middleware
Common middleware groups:
- `requiresLogin` - User must be authenticated
- `requiresLoggedOut` - User must NOT be authenticated
- `requiresApiLogin` - API authentication required
- `requiresApiLogout` - API route for logged-out users
- `merchant` - Requires merchant role (access_level 2)
- `consumer` - Requires consumer role (access_level 1)
- `admin` - Requires admin role (access_level 8 or 9)
- `merchantOrConsumer` - Either merchant or consumer

## Authentication

### User Access Levels
- **9**: System Admin (superuser)
- **8**: Admin
- **2**: Merchant
- **1**: Consumer

### Session Management
- Sessions handled via PHP sessions (started in `index.php`)
- User data stored in `Settings::$user` (populated in `features/init.php`)
- OIDC authentication via MitID for identity verification
- Helper functions: `isLoggedIn()`, `__uuid()` (current user ID)

### Organisation System
- Multi-tenant: users belong to organisations
- Organisation selection stored in user cookies
- `Settings::$chosenOrganisation` contains current organisation context
- Helper: `Methods::organisationMembers()->setChosenOrganisation($memberRow)`

## Helper Functions

### Common Helpers (from `features/functions.php`)
- `__url($path)` - Generate absolute URL
- `__uuid()` - Get current user's UID
- `isLoggedIn()` - Check if user is authenticated
- `isEmpty($value)` - Check if value is empty/null
- `toArray($value)` - Convert to array
- `debugLog($data, $tag)` - Log debug information
- `printView($data)` - Render view
- `printJson($data, $code)` - Output JSON response
- `Response()` - Get response helper instance

### URL Generation
Use the `Links` enumeration for consistent URL management:
```php
Links::$merchant->dashboard
Links::$api->auth->merchantLogin
Links::$policies->consumer->privacy
```

## Key Classes

### Settings (features/Settings.php)
Global application state:
- `Settings::$user` - Current logged-in user
- `Settings::$postData` - POST request data (including JSON)
- `Settings::$app` - Application metadata
- `Settings::$chosenOrganisation` - Selected organisation
- `Settings::$testing` - Whether in testing environment

### Methods (classes/Methods.php)
Primary utility class for business logic:
- `Methods::users()` - User management
- `Methods::organisationMembers()` - Organisation membership
- `Methods::appMeta()` - Application metadata
- `Methods::isAdmin()` - Check if current user is admin

## Views

Views are PHP templates in `views/` directory:
- Organized by feature (e.g., `views/merchants/`, `views/purchase-flow/`)
- Use `printView()` to render
- Data passed via `$data` variable in view context

### View File Rules - CRITICAL
**View files are sacred - keep them clean!**

**ALLOWED in view files:**
- HTML markup
- PHP for outputting data: `<?=$variable?>`, `<?php foreach(...): ?>`
- Variable declarations for passing PHP data to JS: `var apiUrl = <?=json_encode($url)?>;`
- Chart building JS (ApexCharts initialization)

**NOT ALLOWED in view files:**
- Inline CSS - use CSS files
- Complex JavaScript logic - use external JS files
- Business logic - belongs in controllers
- Direct `<script src="...">` includes - use Path Constants

### Asset Imports - Path Constants System
**NEVER include JS/CSS files directly in views with `<script src="">` or `<link href="">`.**

Assets are defined in `routing/paths/constants/` files. Each page has a constant defining its assets:

```php
// In routing/paths/constants/Admin.php
const ADMIN_DASHBOARD_PAYMENTS = [
    "template" => "ADMIN_INNER_HTML",
    "view" => "admin.dashboard.payments",
    "assets" => [
        "main" => [
            "js.server.js",        // public/js/server.js
            "js.main.js",          // public/js/main.js
            "js.admin-payments.js", // public/js/admin-payments.js (custom page script)
            "css.main.css",        // public/css/main.css
        ],
        "vendor" => [
            "vendor.apexcharts.apexcharts.min.js",
        ],
    ],
];
```

**Dot notation mapping:**
- `js.filename.js` → `public/js/filename.js`
- `css.filename.css` → `public/css/filename.css`
- `vendor.folder.file.js` → `public/vendor/folder/file.js`

**To add a new JS file for a page:**
1. Create the file in `public/js/`
2. Add it to the page's constant in `routing/paths/constants/`

### JavaScript API Methods
**NEVER use native `fetch()` in JavaScript.** Always use the project's methods from `server.js`:
- `post(url, data)` - POST request
- `get(url)` - GET request
- `del(url)` - DELETE request

```javascript
// CORRECT
const result = await post(apiUrl, { page: 1, search: term });
if (result.status === 'error') { ... }
const data = result.data;

// WRONG - never use fetch directly
const response = await fetch(url, { method: 'POST', body: JSON.stringify(data) });
```

### JavaScript Notifications
Use the built-in notification functions from `utility.js` (NOT SweetAlert/swal):

```javascript
// Show error notification (red)
showErrorNotification('Fejl', 'Der opstod en fejl');

// Show success notification (green)
showSuccessNotification('Success', 'Handlingen blev udført');

// Show neutral notification
showNeutralNotification('Info', 'Her er noget information');

// All accept optional timeout (default 5000ms)
showErrorNotification('Fejl', 'Beskrivelse', 3000);

// Queue notification to show after page load/redirect
queueNotificationOnLoad('Titel', 'Beskrivelse', 'success'); // types: 'success', 'error', 'neutral'
```

**For confirmation dialogs:** Use `SweetPrompt.confirm()` (NOT native `confirm()`):

```javascript
// CORRECT - Use SweetPrompt for confirmations
SweetPrompt.confirm('Slet element?', 'Er du sikker på at du vil slette dette?', {
    confirmButtonText: 'Ja, slet',
    onConfirm: async function() {
        // Do the delete action
        var response = await post('api/delete', {uid: uid});
        if (response.status === 'success') {
            location.reload();
        }
    }
});

// WRONG - Never use native confirm()
if (!confirm('Are you sure?')) return;
```

## Testing

- Testing environment accessible via `/testing` URL path
- Separate testing database configuration
- Test credentials defined in model `$requiredRows` arrays

## Dependencies

### Composer Packages
- `endroid/qr-code` - QR code generation for terminals

### Custom Vendors
- `vendor/html_parser/` - HTML parsing utilities
- `vendor/html5_parser/` - HTML5 parsing
- `vendor/math-ai/` - ML/AI functionality

## Common Development Tasks

### Adding a New Route
1. Define route in `routing/web.php`
2. Create controller in `routing/routes/` (organized by feature)
3. Controller method receives `$args` array with GET, POST, and route params
4. Return appropriate response format

### Creating a New Model
1. Create class in `Database/model/` extending `\Database\Model`
2. Define `$schema` array with column definitions
3. Define `$uniques` for unique constraints
4. Optionally define `$encodeColumns` or `$encryptedColumns`
5. Run migration to create table

### Adding Middleware
1. Create middleware function in `routing/middleware/`
2. Function receives `$params` and returns boolean
3. Add to middleware array in route definition
4. Middleware runs before controller execution

### Working with Organisations
- Always check current organisation via `Settings::$chosenOrganisation`
- Filter queries by organisation ID for multi-tenant data
- Use `Methods::organisationMembers()->userIsMember($orgId)` to verify access

## Security Notes

- Database columns can be encrypted (use `$encryptedColumns` in models)
- CSRF protection via `__csrf()` function (called at end of routing)
- SQL injection protected via PDO prepared statements
- Access control via middleware and access_level checks
- Sensitive configuration in `env/` directory (excluded from version control)

## Logging

- Logs stored in `logs/` directory
- Use `debugLog($data, $tag)` for debugging
- Cron job logs in `logs/cron/`
- Request paths logged automatically

## Constants

Key constants defined in `env/other/config.php`:
- `ROOT` - Absolute file system path
- `HOST` - Base URL
- `LIVE` - Boolean for production environment
- `TESTING` - Boolean for testing environment
- `BRAND_NAME` - "WeePay"
- `ADMIN_PANEL_PATH` - "panel"
