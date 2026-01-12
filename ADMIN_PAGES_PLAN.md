# Admin Dashboard & Panel Pages - Detailed Implementation Plan

---

## CRITICAL DEVELOPMENT RULES

### Code Patterns
1. **NEVER use Model classes directly** - Always use Handler classes via `Methods::handlerName()`
2. **Check foreignkeys()** - Foreign key columns are resolved as objects, not strings. Use `->excludeForeignKeys()` when you only need raw IDs
3. **Use Crud methods** - Prefer `get()`, `getFirst()`, `getByX()`, `exists()`, `create()`, `update()`, `delete()` over raw QueryBuilder
4. **Translate sidebar titles** - Use `ucfirst(Translate::word("..."))` for all sidebar section headers

### UI Standards
1. **KPI Cards** - MAX 4 cards per row (divisible by 12: 3, 4, or 6 columns). Never 6 cards in a row - causes overflow
2. **Icon containers** - Use `square-40` class with proper colors (`bg-blue`, `bg-green`, `bg-pee-yellow`, etc.). NOT `bg-warning`, `bg-success`, etc.
3. **Form elements** - `form-field-v2` goes directly on `<input>`, `form-select-v2` goes directly on `<select>`
4. **Sidebar section headers** - Must hide when sidebar is collapsed (same behavior as link texts)

### Asset Loading
- **Charts**: `vendor.apexcharts.apexcharts.min.js`, `js.includes.charts.js`
- **DateRangePicker**: `vendor.moment.moment.min.js`, `js.includes.daterangepicker.js`, `css.includes.daterangepicker.css`
- **DataTables**: `vendor.datatables.dataTables.js`, `vendor.datatables.dataTablesBs.js`, `js.includes.dataTables.js`, `vendor.datatables.dataTablesBs.css`
- **SweetAlert/SweetPrompt**: `vendor.sweetalert.sweetalert2.min.css`, `js.includes.sweetAlert.js`, `vendor.sweetalert.sweetalert2.min.js`, `js.includes.sweetPrompt.js`
- **Handlebars**: `js.includes.handleBars.js`

### Reference Implementation
- See `views/merchants/pages/reports.php` for correct KPI card styling

---

## BUGS & ISSUES TO FIX BEFORE CONTINUING

These issues were identified during testing and must be fixed before building panel pages:

### Form Element Classes (WRONG USAGE)
- `form-field-v2` goes directly on the `<input>` element, NOT on a container div
- `form-select-v2` goes directly on the `<select>` element, NOT on a container div
- We do NOT use `form-control` or `form-control-v2` at all

### KPI Card Icons (WRONG STYLING)
- Icons have awful ratio and weird colors
- Should NOT use `bg-warning`, `bg-success`, `bg-info`, etc.
- Should use proper colors: `bg-pee-yellow`, `bg-green`, etc.
- Should use `square-40` class for icon containers
- **Reference**: Merchant reports page (`views/merchants/pages/reports.php`) for correct implementation

### Dashboard Charts
- No data is showing on the dashboard charts
- Need to investigate why chart data is not rendering

### Orders Page
- Missing pagination - needs to be added

### Payments Pages
- No content shows up anywhere except for the title
- Views are broken or data is not being passed correctly

### Sidebar Section Headers
- Section headers (e.g., "Oversigt", "Brugere", "Virksomheder") must disappear when sidebar is partially closed
- Should behave same as link texts that hide on collapse
- Add `sidebar-section-title` class behavior to match link text hiding

### Other Potential Issues
- More issues likely exist - need thorough testing of all pages

---

## Implementation Progress

### Dashboard Pages
- [x] Dashboard Home - KPIs, revenue chart, user growth chart, alerts, system stats
- [x] Users List - Paginated table with search, role/status filters
- [x] User Detail - User info, stats, organisation memberships, recent orders
- [x] Consumers - Filtered user list (access_level=1)
- [x] Merchants - Filtered user list (access_level=2)
- [x] Organisations - Paginated table with search, status filter
- [x] Organisation Detail - Info, stats, members table, locations list
- [x] Locations - Paginated table with search, status/org filters
- [x] Location Detail - Info, stats, members table
- [x] Orders - Paginated table with search, org/loc/status filters, links to order detail
- [x] Order Detail - Order info, customer link, stats cards, payments table
- [x] Payments (all) - Paginated table with search, org/status filters, quick links to pending/past due
- [x] Payments Pending - Filtered payments (PENDING/SCHEDULED), due soon warnings
- [x] Payments Past Due - Filtered payments (PAST_DUE), days overdue badge, alert banner
- [x] BNPL Overview - BNPL stats, payment status cards, recent BNPL orders table
- [x] Reports - Revenue/orders/users by time period, totals summary, quick links
- [x] Support - Ticket stats, ticket table (placeholder), help contact section

### Panel Pages

**Priority 1 - Build Now:**
- [x] Panel Home - Overview with quick links, system status
- [x] App Settings - Global settings via AppMeta (grouped by category, edit modals)
- [x] Fees - Transaction/monthly fee configuration (default + per-org overrides)
- [x] Payment Plans - BNPL, direct, pushed options (max BNPL amount, plan config)
- [x] Notifications - Email/SMS templates (placeholder)
- [x] User Roles - Admin user management (existing page at /panel/users)
- [x] API Settings - API keys, rate limits (placeholder)
- [x] Logs - System log viewer (existing page at /panel/logs/list)
- [x] Cron Jobs - Scheduled task monitoring (placeholder)
- [x] Maintenance - Maintenance mode toggle (full implementation with message config)
- [x] Marketing Materials - NEW PAGE (placeholder: "Content coming soon")

**Priority 2 - Skip for Now (placeholder only):**
- [x] Cache - No interesting content yet (placeholder)
- [x] Webhooks - No interesting content yet (placeholder)

**Priority 3 - Build Last:**
- [ ] Policies (Privacy, Terms, Cookies) - Rich text editors
- [ ] Contact Forms - Form destinations and submissions
- [ ] Flows - TBD

---

## Overview
This document outlines the detailed implementation plan for all admin dashboard and panel pages. All list pages will use the standard pagination pattern (server-side with 10/25/50 per page). KPI cards and graphs will be included where relevant.

---

## DASHBOARD PAGES (Daily Operations)

### 1. Dashboard Home (`/dashboard`)
**Purpose**: Main landing page after admin login - high-level system overview with actionable alerts

#### KPI Cards (Top Section)
| KPI | Description | Icon | Color |
|-----|-------------|------|-------|
| Total Omsætning (i dag) | Today's total revenue across all organisations | mdi-cash-multiple | Blue |
| Aktive Brugere | Users active in last 24 hours | mdi-account-check | Green |
| Nye Registreringer | New users registered today | mdi-account-plus | Cyan |
| Afventende Betalinger | Payments awaiting processing | mdi-clock-outline | Orange |
| Forfaldne Betalinger | Past due payments requiring action | mdi-alert-circle | Red |
| Aktive Organisationer | Organisations with activity today | mdi-domain | Blue |

#### Graphs Section
1. **Omsætning Over Tid** (Area + Line Chart)
   - X-axis: Last 30 days
   - Y-axis Left: Revenue (DKK)
   - Y-axis Right: Order count
   - Filterable by: 7d, 30d, 90d, 1y

2. **Bruger Vækst** (Line Chart)
   - New registrations over time
   - Consumers vs Merchants breakdown

#### Alerts & Actions Section (Card with list)
| Alert Type | Description | Action Button |
|------------|-------------|---------------|
| Forfaldne Betalinger | X payments are past due | Se alle |
| Afventende Verifikationer | X users awaiting verification | Gennemgå |
| Support Tickets | X unresolved support tickets | Se tickets |
| System Advarsler | Any system warnings/errors | Se logs |

#### Quick Stats Table
- Top 5 performing organisations (by revenue this month)
- Recent activity feed (last 10 actions)

---

### 2. Users Page (`/dashboard/users`)
**Purpose**: Overview of all users in the system

#### KPI Cards
| KPI | Description | Icon |
|-----|-------------|------|
| Total Brugere | All registered users | mdi-account-group |
| Forbrugere | Consumer count | mdi-account |
| Forhandlere | Merchant count | mdi-store |
| Admins | Admin count | mdi-shield-account |
| Nye (denne måned) | New users this month | mdi-account-plus |
| Verificerede | Verified users % | mdi-account-check |

#### Graph
- **Bruger Registreringer** (Bar chart): Daily/weekly registrations by type

#### Filters
- Search: Name, email, phone, UID
- User Type: All / Consumer / Merchant / Admin
- Status: All / Active / Inactive / Suspended
- Verification: All / Verified / Unverified
- Date Range: Registration date

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Bruger | Yes | Name + avatar |
| Email | Yes | Email address |
| Type | Yes | Consumer/Merchant/Admin badge |
| Status | Yes | Active/Inactive/Suspended |
| Verificeret | Yes | Yes/No with icon |
| Registreret | Yes | Registration date |
| Sidste Login | Yes | Last login timestamp |
| Handlinger | No | View / Edit / Suspend buttons |

---

### 3. User Detail (`/dashboard/users/{id}`)
**Purpose**: Detailed view of a single user

#### Header Section
- User avatar, name, email, phone
- User type badge
- Account status (with toggle)
- Quick actions: Impersonate, Reset Password, Suspend

#### KPI Cards (User-specific)
| KPI | Description |
|-----|-------------|
| Total Ordrer | Orders placed/received |
| Total Betalt | Total amount paid/received |
| Udestående | Outstanding balance |
| Medlemskaber | Organisation memberships |

#### Tabs
1. **Oversigt**: Basic info, verification status, login history
2. **Ordrer**: User's orders (paginated table)
3. **Betalinger**: User's payments (paginated table)
4. **Organisationer**: Organisations user belongs to
5. **Aktivitet**: Activity log / audit trail

---

### 4. Consumers Page (`/dashboard/consumers`)
**Purpose**: Consumer-specific user management

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Total Forbrugere | All consumers |
| Aktive (30 dage) | Active in last 30 days |
| Gennemsnit Køb | Average purchase value |
| BNPL Brugere | Using buy-now-pay-later |
| Forfaldne | With past due payments |

#### Graph
- **Forbruger Aktivitet** (Area chart): Purchase activity over time

#### Filters & Table
- Same structure as Users page, filtered to consumers only
- Additional column: Total Purchases, Outstanding Balance

---

### 5. Merchants Page (`/dashboard/merchants`)
**Purpose**: Merchant-specific user management

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Total Forhandlere | All merchants |
| Aktive Organisationer | With active org |
| Total Omsætning | Combined revenue |
| Gns. Månedlig Omsætning | Avg monthly revenue |
| Nye (denne måned) | New this month |

#### Graph
- **Forhandler Performance** (Bar chart): Top 10 by revenue

#### Filters & Table
- Same structure as Users page, filtered to merchants only
- Additional columns: Primary Organisation, Revenue, Locations

---

### 6. Organisations Page (`/dashboard/organisations`)
**Purpose**: All organisations in the system

#### KPI Cards
| KPI | Description | Icon |
|-----|-------------|------|
| Total Organisationer | All organisations | mdi-domain |
| Aktive | Active orgs | mdi-check-circle |
| Nye (denne måned) | New this month | mdi-plus-circle |
| Total Lokationer | All locations | mdi-map-marker |
| Total Omsætning | Combined revenue | mdi-cash |
| Gns. Omsætning | Avg revenue per org | mdi-chart-line |

#### Graph
- **Omsætning per Organisation** (Horizontal bar): Top 10 organisations

#### Filters
- Search: Name, CVR, UID
- Status: All / Active / Inactive / Suspended
- Type: (if applicable)
- Date Range: Created date

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Organisation | Yes | Name + logo |
| CVR | Yes | Company number |
| Ejere | No | Owner count |
| Lokationer | Yes | Location count |
| Omsætning | Yes | Total revenue |
| Status | Yes | Active/Inactive |
| Oprettet | Yes | Created date |
| Handlinger | No | View / Edit / Suspend |

---

### 7. Organisation Detail (`/dashboard/organisations/{id}`)
**Purpose**: Detailed view of single organisation

#### Header
- Logo, name, CVR, address
- Status badge with toggle
- Quick actions: Edit, Suspend, View as Merchant

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Total Omsætning | All-time revenue |
| Denne Måned | Current month revenue |
| Ordrer | Total orders |
| Lokationer | Location count |
| Medlemmer | Team member count |
| Terminaler | Terminal count |

#### Graph
- **Omsætning Over Tid** (Area chart): Organisation revenue

#### Tabs
1. **Oversigt**: Basic info, settings, verification
2. **Lokationer**: Locations list (paginated)
3. **Medlemmer**: Team members (paginated)
4. **Ordrer**: Orders (paginated)
5. **Betalinger**: Payments (paginated)
6. **Gebyrer**: Fee configuration
7. **Indstillinger**: Organisation settings

---

### 8. Locations Page (`/dashboard/locations`)
**Purpose**: All locations across all organisations

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Total Lokationer | All locations |
| Aktive | Active locations |
| Med Terminaler | Locations with terminals |
| Total Terminaler | All terminals |
| Gns. Omsætning | Avg revenue per location |

#### Graph
- **Lokation Performance** (Bar chart): Top 10 by revenue

#### Filters
- Search: Name, address, UID
- Organisation: Dropdown
- Status: All / Active / Inactive
- Has Terminals: Yes / No

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Lokation | Yes | Name + address |
| Organisation | Yes | Parent org |
| Terminaler | Yes | Terminal count |
| Omsætning | Yes | Location revenue |
| Status | Yes | Active/Inactive |
| Oprettet | Yes | Created date |
| Handlinger | No | View / Edit |

---

### 9. Location Detail (`/dashboard/locations/{id}`)
**Purpose**: Detailed view of single location

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Omsætning | Location revenue |
| Ordrer | Order count |
| Terminaler | Terminal count |
| Medlemmer | Staff count |

#### Tabs
1. **Oversigt**: Basic info, address, opening hours
2. **Terminaler**: Terminal list
3. **Ordrer**: Orders from this location
4. **Medlemmer**: Staff at this location

---

### 10. Orders Page (`/dashboard/orders`)
**Purpose**: All orders across all organisations

#### KPI Cards
| KPI | Description | Icon |
|-----|-------------|------|
| Total Ordrer | All-time orders | mdi-cart |
| I dag | Today's orders | mdi-calendar-today |
| Denne Uge | This week | mdi-calendar-week |
| Denne Måned | This month | mdi-calendar-month |
| Gennemsnitlig Værdi | Avg order value | mdi-cash |
| Annullerede | Cancelled rate % | mdi-cancel |

#### Graph
- **Ordrer Over Tid** (Area + Line): Order count + revenue dual axis

#### Filters
- Search: Order ID, customer name/email
- Organisation: Dropdown
- Location: Dropdown (dependent on org)
- Status: All / Completed / Pending / Cancelled / Refunded
- Payment Status: All / Paid / Unpaid / Partial
- Date Range: Order date

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Ordre ID | Yes | Clickable link |
| Dato | Yes | Order date/time |
| Kunde | Yes | Customer name |
| Organisation | Yes | Organisation name |
| Lokation | Yes | Location name |
| Beløb | Yes | Order total |
| Status | Yes | Order status badge |
| Betaling | Yes | Payment status badge |
| Handlinger | No | View / Refund |

---

### 11. Order Detail (`/dashboard/orders/{id}`)
**Purpose**: Detailed view of single order

#### Header
- Order ID, date, status badge
- Quick actions: Refund, Cancel, Print

#### Info Cards (Row)
| Card | Content |
|------|---------|
| Kunde | Name, email, phone |
| Organisation | Org name, location |
| Betaling | Method, status, transaction ID |

#### Order Items Table
- Product, quantity, unit price, total
- Subtotal, fees, discounts, total

#### Payment Timeline
- Payment events with timestamps

#### Activity Log
- Order status changes, admin actions

---

### 12. Payments Page (`/dashboard/payments`)
**Purpose**: All completed payments

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Total Betalt | All-time payments |
| I dag | Today's payments |
| Denne Måned | This month |
| Gns. Betaling | Avg payment amount |
| Gebyr Indtjening | Total fees collected |

#### Graph
- **Betalinger Over Tid** (Area chart): Payment volume

#### Filters
- Search: Payment ID, customer, order ID
- Organisation: Dropdown
- Method: All / Card / MobilePay / Invoice / BNPL
- Date Range

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Betaling ID | Yes | Payment reference |
| Dato | Yes | Payment date |
| Kunde | Yes | Customer name |
| Organisation | Yes | Organisation |
| Ordre | Yes | Linked order |
| Beløb | Yes | Amount |
| Metode | Yes | Payment method |
| Gebyr | Yes | Fee amount |
| Handlinger | No | View / Refund |

---

### 13. Payments Pending (`/dashboard/payments/pending`)
**Purpose**: Payments awaiting processing

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Afventende | Total pending |
| Total Værdi | Combined value |
| Ældste | Oldest pending age |
| Forfaldne Snart | Due within 3 days |

#### Same table structure as Payments, filtered to pending

---

### 14. Payments Past Due (`/dashboard/payments/past-due`)
**Purpose**: Overdue payments requiring action

#### KPI Cards
| KPI | Description | Color |
|-----|-------------|-------|
| Forfaldne | Total overdue | Red |
| Total Værdi | Combined value | Red |
| Gns. Dage Forfalden | Avg days overdue | Orange |
| Kritiske (>30 dage) | Over 30 days | Red |

#### Graph
- **Forfaldne Over Tid** (Bar chart): Aging buckets (1-7d, 8-14d, 15-30d, 30+d)

#### Same table structure with additional columns:
- Forfaldsdato (Due date)
- Dage Forfalden (Days overdue)
- Reminder count

---

### 15. BNPL Page (`/dashboard/bnpl`)
**Purpose**: Buy-now-pay-later overview

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Aktive BNPL | Active plans |
| Total Udestående | Outstanding balance |
| Gennemførte | Completed plans |
| Misligholdelse Rate | Default rate % |
| Kommende Betalinger | Due this week |

#### Graph
- **BNPL Performance** (Stacked bar): On-time vs late vs defaulted

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Plan ID | Yes | BNPL reference |
| Kunde | Yes | Customer |
| Original Beløb | Yes | Original amount |
| Udestående | Yes | Remaining balance |
| Næste Betaling | Yes | Next due date |
| Rater | No | X/Y completed |
| Status | Yes | On-track / Late / Defaulted |
| Handlinger | No | View / Contact |

---

### 16. Reports Page (`/dashboard/reports`)
**Purpose**: Generate and view system reports

#### Report Types (Cards/Buttons)
| Report | Description |
|--------|-------------|
| Omsætningsrapport | Revenue by period/org/location |
| Brugerrapport | User statistics and growth |
| Betalingsrapport | Payment method breakdown |
| Gebyrrapport | Fee collection summary |
| BNPL Rapport | BNPL performance |
| Organisationsrapport | Org comparison |

#### Report Generator
- Date range picker
- Organisation filter (or all)
- Export format: PDF / Excel / CSV
- Schedule option (daily/weekly/monthly email)

#### Recent Reports Table
- Previously generated reports with download links

---

### 17. Support Page (`/dashboard/support`)
**Purpose**: Support ticket management

#### KPI Cards
| KPI | Description |
|-----|-------------|
| Åbne Tickets | Open tickets |
| Ubesvaret | Awaiting response |
| Lukket (denne uge) | Closed this week |
| Gns. Svartid | Avg response time |

#### Filters
- Search: Ticket ID, subject, customer
- Status: All / Open / Pending / Resolved / Closed
- Priority: All / Low / Medium / High / Urgent
- Category: (dynamic)

#### Table Columns
| Column | Sortable | Description |
|--------|----------|-------------|
| Ticket ID | Yes | Reference |
| Emne | Yes | Subject line |
| Kunde | Yes | Customer name |
| Kategori | Yes | Category |
| Prioritet | Yes | Priority badge |
| Status | Yes | Status badge |
| Oprettet | Yes | Created date |
| Sidste Aktivitet | Yes | Last update |
| Handlinger | No | View / Assign / Close |

---

## PANEL PAGES (System Configuration)

### 18. Panel Home (`/panel`)
**Purpose**: System configuration overview

#### Quick Links Grid
Cards linking to all panel sections with icons

#### System Status
- Database status
- Cache status
- Queue/Job status
- Last deployment info

#### Recent Admin Activity
- Recent configuration changes by admins

---

### 19. App Settings (`/panel/settings`)
**Purpose**: Global application settings via AppMeta

#### Settings Sections (Accordion or Tabs)
1. **Generelt**: App name, logo, contact info
2. **Lokalisering**: Timezone, currency, language
3. **Email**: SMTP settings, from address
4. **Sikkerhed**: Session timeout, password policy
5. **Integration**: Third-party API keys

#### Each Setting
- Label + description
- Input field (text/select/toggle)
- Save button per section

---

### 20. Fees Configuration (`/panel/fees`)
**Purpose**: Configure OrganisationFees

#### Default Fees Section
- System-wide default fee structure
- Transaction fee %
- Fixed fee per transaction
- Monthly fees

#### Organisation Overrides Table
- List of organisations with custom fees
- Add/Edit override modal

#### Fee Structure
| Fee Type | Description | Default Value |
|----------|-------------|---------------|
| Transaction % | Per-transaction percentage | X% |
| Fixed Fee | Per-transaction fixed | X DKK |
| Monthly Fee | Monthly subscription | X DKK |
| BNPL Fee | BNPL additional fee | X% |

---

### 21. Payment Plans (`/panel/payment-plans`)
**Purpose**: Configure available payment plan options from AppMeta.paymentPlans

#### Payment Plan Cards
Each plan shown as a card with toggle and settings:

| Plan | Key | Description | Settings |
|------|-----|-------------|----------|
| **Betal Nu** | `direct` | Full payment immediately | Enable/Disable |
| **Betal d. 1. i Måneden** | `pushed` | Defer payment to 1st of next month | Enable/Disable |
| **Del i Rater (BNPL)** | `installments` | Split into installments | Enable/Disable, Number of installments (default 4) |

#### Global BNPL Settings
| Setting | Key | Description | Input Type |
|---------|-----|-------------|------------|
| Max BNPL Beløb | `platform_max_bnpl_amount` | Maximum amount allowed for BNPL | Number (DKK) |
| Antal Rater | `paymentPlans.installments.installments` | Default number of installments | Number (default 4) |

#### Plan Card Layout (per plan)
```
┌─────────────────────────────────────────┐
│ [Toggle] Betal Nu                       │
│ ─────────────────────────────────────── │
│ Fuld betaling med det samme             │
│                                         │
│ Installments: 1                         │
│ Start: Immediately                      │
│                                         │
│ [Edit Title] [Edit Caption]             │
└─────────────────────────────────────────┘
```

#### Per-Organisation Overrides (Future)
- Allow specific organisations to have different plan availability

---

### 22. Policies (`/panel/policies`)
**Purpose**: Manage legal documents

#### Policy List
| Policy | Last Updated | Actions |
|--------|--------------|---------|
| Privatlivspolitik | Date | Edit / Preview |
| Servicevilkår | Date | Edit / Preview |
| Cookiepolitik | Date | Edit / Preview |

#### Each Policy Editor (`/panel/policies/{type}`)
- Rich text editor (WYSIWYG)
- Version history
- Preview mode
- Publish button

---

### 23. Contact Forms (`/panel/contact-forms`)
**Purpose**: Configure contact form destinations and handling

#### Form Configurations
| Form | Recipient | Auto-reply | Status |
|------|-----------|------------|--------|
| Generel Kontakt | email@... | Yes/No | Active |
| Support | email@... | Yes/No | Active |
| Partnership | email@... | Yes/No | Active |

#### Form Editor
- Recipient emails
- Auto-reply template
- Form fields configuration
- Spam protection settings

#### Submissions Table
- Recent form submissions with view/respond options

---

### 24. Notifications (`/panel/notifications`)
**Purpose**: Configure notification flows and templates

#### Notification Types
| Type | Trigger | Channels | Status |
|------|---------|----------|--------|
| Ordre Bekræftelse | Order created | Email, SMS | Active |
| Betaling Modtaget | Payment completed | Email | Active |
| Betaling Forfalden | Payment past due | Email, SMS | Active |
| Velkommen | User registered | Email | Active |
| Password Reset | Password reset requested | Email | Active |

#### Template Editor (per notification)
- Subject line
- Email body (HTML editor)
- SMS text (if applicable)
- Variables: {customer_name}, {order_id}, {amount}, etc.
- Test send functionality

---

### 25. User Roles (`/panel/users`)
**Purpose**: Admin user management and roles

#### Role Definitions
| Role | Permissions |
|------|-------------|
| Super Admin | Full access |
| Admin | Most access, no system config |
| Support | Read + support tickets |
| Finance | Payments and reports |

#### Admin Users Table
- Name, email, role, last login
- Add/Edit/Remove admins

---

### 26. API Configuration (`/panel/api`)
**Purpose**: API keys and webhook configuration

#### API Keys Section
- Generate new keys
- List existing keys (masked)
- Revoke keys

#### Rate Limits
- Configure rate limiting per endpoint

#### Documentation Link
- Link to API documentation

---

### 27. Logs (`/panel/logs/list`)
**Purpose**: System log viewer

#### Log Types
- Application logs
- Error logs
- Access logs
- Audit logs

#### Log Viewer
- Date filter
- Log level filter (debug, info, warning, error)
- Search within logs
- Download logs

---

### 28. Webhooks (`/panel/webhooks`)
**Purpose**: Configure outgoing webhooks

#### Webhook Endpoints Table
| Endpoint | URL | Events | Status |
|----------|-----|--------|--------|
| Name | https://... | order.created, payment.completed | Active |

#### Add/Edit Webhook
- Endpoint URL
- Secret key (for signature verification)
- Event subscriptions (checkboxes)
- Retry policy

#### Webhook Logs
- Recent webhook deliveries with status

---

### 29. Cron Jobs (`/panel/jobs`)
**Purpose**: Monitor scheduled tasks

#### Job Status Table
| Job | Schedule | Last Run | Next Run | Status |
|-----|----------|----------|----------|--------|
| Payment Reminders | Daily 09:00 | Date/time | Date/time | OK / Failed |
| Report Generation | Weekly Mon | Date/time | Date/time | OK / Failed |
| Cleanup | Daily 03:00 | Date/time | Date/time | OK / Failed |

#### Job Logs
- Execution history with output/errors

#### Manual Trigger
- Button to manually run each job

---

### 30. Cache (`/panel/cache`)
**Purpose**: Cache management

#### Cache Statistics
| Cache | Size | Hit Rate | Last Cleared |
|-------|------|----------|--------------|
| Application | X MB | 95% | Date |
| Query | X MB | 87% | Date |
| Session | X MB | N/A | Date |

#### Actions
- Clear all cache
- Clear specific cache type
- Warm cache (regenerate)

---

### 31. Maintenance (`/panel/maintenance`)
**Purpose**: System maintenance mode

#### Maintenance Mode Toggle
- Enable/disable maintenance mode
- Custom maintenance message
- Whitelist IPs (admins can still access)

#### Scheduled Maintenance
- Schedule future maintenance window
- Auto-notify users before maintenance

#### System Health
- Database connection test
- External API connectivity tests
- Storage space check

---

## Implementation Notes

### Pagination Standard
All list pages use:
- Server-side pagination
- Per page options: 10, 25, 50
- Smooth loading states
- URL state persistence (page, filters in query params)

### KPI Card Standard
- 6 cards max per row (responsive grid)
- Icon in colored square badge
- Main value prominent
- Change indicator where applicable

### Graph Standard
- ApexCharts library
- Consistent color palette
- Danish locale formatting
- Responsive sizing
- Download/export option

### Table Standard
- Sortable columns
- Search with debounce
- Filter dropdowns
- Date range picker
- Loading skeleton
- Empty state with icon

### Modal Standard
- For add/edit forms
- Confirmation dialogs for destructive actions
- Loading state during submission

---

## Required Vendor Assets

### For Pages with Charts (KPIs & Graphs)
Add to path constant `vendor` array:
```php
"vendor" => [
    "vendor.apexcharts.apexcharts.min.js",
    "js.includes.charts.js",
],
```

**Pages requiring charts:**
- Dashboard Home (`ADMIN_DASHBOARD`)
- Users (`ADMIN_DASHBOARD_USERS`)
- Consumers (`ADMIN_DASHBOARD_CONSUMERS`)
- Merchants (`ADMIN_DASHBOARD_MERCHANTS`)
- Organisations (`ADMIN_DASHBOARD_ORGANISATIONS`)
- Organisation Detail (`ADMIN_DASHBOARD_ORGANISATION_DETAIL`)
- Locations (`ADMIN_DASHBOARD_LOCATIONS`)
- Orders (`ADMIN_DASHBOARD_ORDERS`)
- Payments (`ADMIN_DASHBOARD_PAYMENTS`)
- Payments Past Due (`ADMIN_DASHBOARD_PAYMENTS_PAST_DUE`)
- BNPL (`ADMIN_DASHBOARD_BNPL`)

### For Pages with Date Range Pickers
Add to path constant `vendor` array:
```php
"vendor" => [
    "vendor.moment.moment.min.js",
    "js.includes.daterangepicker.js",
    "css.includes.daterangepicker.css",
],
```

**Pages requiring daterangepicker:**
- All list pages with date filtering:
  - Users, Consumers, Merchants
  - Organisations, Locations
  - Orders, Order Detail
  - Payments, Payments Pending, Payments Past Due
  - BNPL
  - Support
  - Reports

### For Pages with DataTables (if using)
```php
"vendor" => [
    "vendor.datatables.dataTables.js",
    "vendor.datatables.dataTablesBs.js",
    "js.includes.dataTables.js",
    "vendor.datatables.dataTablesBs.css",
],
```

### For Pages with Handlebars Templates
```php
"vendor" => [
    "js.includes.handleBars.js",
],
```

### For Pages with SweetAlert Confirmations
```php
"vendor" => [
    "vendor.sweetalert.sweetalert2.min.css",
    "js.includes.sweetAlert.js",
    "vendor.sweetalert.sweetalert2.min.js",
],
```

---

## Form Element Standards

### Input Fields
Use class `form-field-v2` for all text inputs:
```html
<input type="text" class="form-control-v2 form-field-v2" placeholder="Søg...">
<input type="email" class="form-control-v2 form-field-v2" placeholder="Email">
<input type="number" class="form-control-v2 form-field-v2" placeholder="Beløb">
```

### Select Dropdowns
Use class `form-select-v2` for all select elements:
```html
<select class="form-select-v2">
    <option value="">Vælg...</option>
</select>
```

### Date Range Picker Input
```html
<input type="text" id="daterange-filter" class="form-control-v2 form-field-v2"
       placeholder="Vælg datointerval" readonly>
```

Initialize with JavaScript:
```javascript
$('#daterange-filter').daterangepicker({
    autoUpdateInput: false,
    locale: {
        format: 'DD/MM/YYYY',
        applyLabel: 'Anvend',
        cancelLabel: 'Ryd',
        fromLabel: 'Fra',
        toLabel: 'Til',
        customRangeLabel: 'Brugerdefineret',
        weekLabel: 'U',
        daysOfWeek: ['Sø', 'Ma', 'Ti', 'On', 'To', 'Fr', 'Lø'],
        monthNames: ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni',
                     'Juli', 'August', 'September', 'Oktober', 'November', 'December'],
        firstDay: 1
    },
    ranges: {
        'I dag': [moment(), moment()],
        'I går': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Sidste 7 dage': [moment().subtract(6, 'days'), moment()],
        'Sidste 30 dage': [moment().subtract(29, 'days'), moment()],
        'Denne måned': [moment().startOf('month'), moment().endOf('month')],
        'Sidste måned': [moment().subtract(1, 'month').startOf('month'),
                         moment().subtract(1, 'month').endOf('month')]
    }
});
```

### Checkboxes and Toggles
```html
<!-- Standard checkbox -->
<label class="form-check-v2">
    <input type="checkbox" class="form-check-input">
    <span class="form-check-label">Label text</span>
</label>

<!-- Toggle switch -->
<label class="toggle-switch">
    <input type="checkbox">
    <span class="toggle-slider"></span>
</label>
```

### Buttons
```html
<!-- Primary action -->
<button class="btn-v2 action-btn">Gem</button>

<!-- Secondary/muted -->
<button class="btn-v2 mute-btn">Annuller</button>

<!-- Transparent -->
<button class="btn-v2 trans-btn">Ryd</button>

<!-- Danger -->
<button class="btn-v2 danger-btn">Slet</button>
```

---

## Page-by-Page Asset Requirements

| Page | Charts | DatePicker | DataTables | SweetAlert | Handlebars |
|------|--------|------------|------------|------------|------------|
| Dashboard Home | Yes | No | No | No | No |
| Users | Yes | Yes | No | Yes | Yes |
| User Detail | No | No | No | Yes | No |
| Consumers | Yes | Yes | No | Yes | Yes |
| Merchants | Yes | Yes | No | Yes | Yes |
| Organisations | Yes | Yes | No | Yes | Yes |
| Organisation Detail | Yes | No | No | Yes | No |
| Locations | Yes | Yes | No | Yes | Yes |
| Location Detail | No | No | No | Yes | No |
| Orders | Yes | Yes | No | Yes | Yes |
| Order Detail | No | No | No | Yes | No |
| Payments | Yes | Yes | No | Yes | Yes |
| Payments Pending | No | Yes | No | Yes | Yes |
| Payments Past Due | Yes | Yes | No | Yes | Yes |
| BNPL | Yes | Yes | No | Yes | Yes |
| Reports | No | Yes | No | No | No |
| Support | No | Yes | No | Yes | Yes |
| Panel Home | No | No | No | No | No |
| App Settings | No | No | No | Yes | No |
| Fees | No | No | No | Yes | No |
| Payment Plans | No | No | No | Yes | No |
| Policies | No | No | No | Yes | No |
| Contact Forms | No | Yes | No | Yes | Yes |
| Notifications | No | No | No | Yes | No |
| User Roles | No | No | No | Yes | No |
| API | No | No | No | Yes | No |
| Logs | No | Yes | No | No | No |
| Webhooks | No | No | No | Yes | No |
| Cron Jobs | No | No | No | Yes | No |
| Cache | No | No | No | Yes | No |
| Maintenance | No | No | No | Yes | No |

---

## Admin Impersonation & Merchant Access Routes

### Overview
Admins need the ability to access merchant-specific pages and perform actions on behalf of merchants (e.g., creating terminals, managing locations, viewing orders). This requires:

1. **Permission bypass** (IMPLEMENTED):
   - `OrganisationMemberHandler::userIsMember()` - Returns `true` for admins
   - `OrganisationMemberHandler::memberHasPermission()` - Returns `true` for admins
   - `LocationMemberHandler::memberHasPermission()` - Returns `true` for admins

2. **Admin-specific routes** to access merchant functionality

### Admin Impersonation Routes

These routes allow admins to access merchant-specific pages by specifying the organisation/location in the URL:

| Route | Description | Equivalent Merchant Route |
|-------|-------------|---------------------------|
| `/admin/impersonate/org/{orgId}` | View organisation dashboard as admin | `/merchant/dashboard` |
| `/admin/impersonate/org/{orgId}/locations` | View organisation locations | `/merchant/locations` |
| `/admin/impersonate/org/{orgId}/locations/{locId}` | View specific location | `/merchant/locations/{id}` |
| `/admin/impersonate/org/{orgId}/terminals` | View organisation terminals | `/merchant/terminals` |
| `/admin/impersonate/org/{orgId}/terminals/new` | Create new terminal | `/merchant/terminals/new` |
| `/admin/impersonate/org/{orgId}/orders` | View organisation orders | `/merchant/orders` |
| `/admin/impersonate/org/{orgId}/payments` | View organisation payments | `/merchant/payments` |
| `/admin/impersonate/org/{orgId}/members` | Manage organisation members | `/merchant/members` |
| `/admin/impersonate/org/{orgId}/settings` | Organisation settings | `/merchant/settings` |

### Path Constants (to add)

```php
// In routing/paths/constants/Admin.php
public string $impersonateOrg = "admin/impersonate/org/{orgId}";
public string $impersonateOrgLocations = "admin/impersonate/org/{orgId}/locations";
public string $impersonateOrgLocation = "admin/impersonate/org/{orgId}/locations/{locId}";
public string $impersonateOrgTerminals = "admin/impersonate/org/{orgId}/terminals";
public string $impersonateOrgTerminalsNew = "admin/impersonate/org/{orgId}/terminals/new";
public string $impersonateOrgOrders = "admin/impersonate/org/{orgId}/orders";
public string $impersonateOrgPayments = "admin/impersonate/org/{orgId}/payments";
public string $impersonateOrgMembers = "admin/impersonate/org/{orgId}/members";
public string $impersonateOrgSettings = "admin/impersonate/org/{orgId}/settings";
```

### Controller Implementation

Create `routing/routes/admin/ImpersonateController.php`:

```php
namespace routing\routes\admin;

use classes\Methods;
use features\Settings;
use Database\model\Organisations;

class ImpersonateController {

    private function loadOrganisation(array $args): ?object {
        $orgId = $args['orgId'] ?? null;
        if(empty($orgId)) return null;

        // Admin check handled by middleware
        $org = Methods::organisations()->get($orgId);
        if(isEmpty($org)) return null;

        // Set the organisation context for the admin session
        Settings::$chosenOrganisation = $org;
        return $org;
    }

    public function dashboard(array $args): array {
        $org = $this->loadOrganisation($args);
        if(isEmpty($org)) return ["return_as" => 404];

        return [
            "return_as" => "view",
            "result" => [
                "view" => "admin.impersonate.dashboard",
                "data" => ["organisation" => $org, "isAdminView" => true]
            ]
        ];
    }

    // ... similar methods for other pages
}
```

### UI Indicators

When viewing as admin, display:
- Yellow banner at top: "Du ser som admin: [Organisation Name]"
- Admin badge next to actions: "Admin handling"
- Return to admin dashboard button

### Breadcrumb Pattern

```
Admin Dashboard > Organisationer > [Org Name] > [Current Page]
```

### Security Considerations

1. All impersonation routes require `admin` middleware
2. All actions are logged with admin user ID
3. Activity logs show "Udført af admin: [Admin Name]"
4. Critical actions (delete, suspend) require confirmation modal
5. Some actions may be restricted even for admins (configurable)

### Implementation Priority

1. **Phase 1**: Basic view access (dashboard, locations, orders, payments)
2. **Phase 2**: Create/modify access (terminals, members)
3. **Phase 3**: Settings and advanced configuration
