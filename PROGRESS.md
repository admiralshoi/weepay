# WeePay Development Progress

## Date: 2026-01-10

---

## Merchant Features

### 1. Reports Page
- [x] Create reports page for sales data
- [x] Date filtering with date pickers
- [x] Location filtering
- [x] KPI cards (revenue, orders, customers, collection rate)
- [x] Sales tab with revenue chart and payment type breakdown
- [x] Payments tab with status breakdown chart and table
- [x] Locations tab with revenue by location chart and table
- [x] CSV export with orders, payments, customers, and ISV fees
- [x] PDF export with KPIs and summary
- **Status**: Complete
- **Notes**:
  - Uses ApexCharts for visualizations
  - Three tabs: Salg, Betalinger, Butikker
  - Permission check: organisation.reports

### 2. Marketing Materials Page
- [x] Create marketing materials page (placeholder)
- [ ] Display existing downloadable images
- [ ] Pre-generate customized materials for download
- **Status**: Page structure complete, content pending (will be populated from admin)
- **Notes**:
  - Route: `marketing-materials`
  - Link added to sidebar as "Markedsføring"
  - Admin will populate content later

### 3. High Risk Button (Checkout Flow)
- [ ] Add 'High Risk' button on cashier screen
- [ ] Flag customer for stricter risk score requirements
- **Status**: Not started
- **Notes**:

### 4. Refund Options
- [ ] Implement refund functionality
- **Status**: Not started
- **Notes**:

### 5. Support Page (Merchant)
- [ ] Create merchant support page
- [ ] Ticket submission system
- **Status**: Not started
- **Notes**:

### 6. FAQ Section (Merchant)
- [ ] Create FAQ page for merchants
- **Status**: Not started
- **Notes**:

---

## Consumer Features

### 7. Card Management / Past Due Payments
- [ ] Ability to change card
- [ ] Pay past due payments
- [ ] Investigate card pre-verification options
- **Status**: Not started
- **Notes**:

### 8. Support Page (Consumer)
- [ ] Create consumer support page
- [ ] Ticket submission system
- **Status**: Not started
- **Notes**:

### 9. FAQ Section (Consumer)
- [ ] Create FAQ page for consumers
- **Status**: Not started
- **Notes**:

---

## Admin Dashboard

### 10. Admin Dashboard Core
- [ ] Create admin dashboard to govern all existing features
- **Status**: Not started
- **Notes**:

### 11. Late Payment Fee System
- [ ] System to charge fees for late payments
- [ ] Configure fee amounts/rules
- **Status**: Not started
- **Notes**:

### 12. Email Templates
- [ ] Set up email template system
- [ ] Make templates editable via admin
- [ ] Implement actual email sending
- **Status**: Not started
- **Notes**:

### 13. Support Tickets Management
- [ ] View incoming tickets from support
- [ ] Ticket management interface
- **Status**: Not started
- **Notes**:

### 14. Form Submissions
- [ ] View filled out forms
- [ ] Form management interface
- **Status**: Not started
- **Notes**:

### 15. Policies Management
- [ ] Expand policies content
- [ ] Make policies editable through admin
- **Status**: Not started
- **Notes**:

---

## Global/Legal

### 16. Cookie Consent Popup
- [ ] EU-compliant cookie consent popup
- [ ] Cookie preferences management
- **Status**: Not started
- **Notes**:

---

## Summary

| Category | Tasks | Completed |
|----------|-------|-----------|
| Merchant | 6 | 2 |
| Consumer | 3 | 0 |
| Admin | 6 | 0 |
| Global | 1 | 0 |
| **Total** | **16** | **2** |

---

## Session Notes

### Context & Decisions
- Marketing materials content will be managed via admin dashboard

### Completed Today
- Marketing Materials page created with placeholder UI
  - Added link `Links::$merchant->materials` = "marketing-materials"
  - Added to both merchant and merchant-dashboard sidebars
  - Route, controller method, view constant, and view file created
  - Danish translations used ("Markedsføring", "Markedsføringsmaterialer")
  - Added `advertisement` permission under `organisation` in BASE_PERMISSIONS
  - Added to all roles in OrganisationRolePermissions (location_employee: read-only)
  - Added Danish translation for "advertisement" = "Markedsføring"

- Reports page created with full functionality
  - Route: `/reports`
  - Three tabs: Salg (Sales), Betalinger (Payments), Butikker (Locations)
  - KPI cards: Revenue, Net, Orders, Avg Order, Customers, Collection Rate
  - Charts: Revenue over time, Payment type breakdown, Payment status, Revenue by location
  - Date range filtering with start/end date pickers
  - Location filtering via existing location selector
  - Permission check: `organisation.reports`

- Reports export functionality added
  - CSV export with grouped sections: Orders, Completed Payments, Customers, Summary
  - All amounts include ISV fee aggregation
  - PDF export with KPIs, payment breakdown, and revenue by location
  - Files stored in `reports/{org_uid}/csv/` and `reports/{org_uid}/pdf/`
  - Download route validates organisation access before serving files
  - New files:
    - `classes/reports/ReportExporter.php` - handles CSV/PDF generation
    - `classes/enumerations/links/api/organisation/Reports.php` - API link constants
    - `routing/routes/merchants/ReportsApiController.php` - API controller
  - API routes:
    - POST `api/organisation/reports/generate-csv`
    - POST `api/organisation/reports/generate-pdf`
    - GET `api/organisation/reports/download/{filename}`

### Blockers / Questions
- (Note any issues encountered)
