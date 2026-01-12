# WeePay Notifications System - Implementation Plan

## Overview

A comprehensive notification system enabling customizable notification flows with support for **Email**, **SMS**, and **Bell (in-app)** notifications. The system consists of three core components:

1. **Notification Templates** - Reusable message templates with dynamic placeholders
2. **Breakpoints** - Code-defined trigger points where notifications can be sent
3. **Notification Flows** - Configurable rules connecting breakpoints to templates with conditions

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        NOTIFICATION FLOW                                 │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────────────┐ │
│  │  Breakpoint │───▶│    Flow     │───▶│  Template (Email/SMS/Bell)  │ │
│  │  (Trigger)  │    │ (Conditions)│    │  with Dynamic Placeholders  │ │
│  └─────────────┘    └─────────────┘    └─────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      EXECUTION METHODS                                   │
│                                                                          │
│   1. INSTANT (Code-triggered)          2. SCHEDULED (Cron-triggered)    │
│      - Order completed                    - Payment due reminders        │
│      - Payment successful                 - Subscription renewals        │
│      - User registration                  - Scheduled campaigns          │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Database Models

### 1. NotificationTemplates
Stores reusable notification content templates.

```php
Schema:
- uid (string, PK) - Prefix: 'ntpl'
- name (string) - Internal template name
- type (string) - 'email' | 'sms' | 'bell'
- subject (string, nullable) - Email subject line (supports placeholders)
- content (text) - Message body (supports placeholders)
- html_content (text, nullable) - HTML version for emails
- placeholders (encoded array) - List of available placeholders for this template
- status (string) - 'active' | 'inactive' | 'draft'
- created_by (FK: Users)
- created_at (timestamp)
- updated_at (timestamp)
```

**Reasoning**: Separating templates allows reuse across multiple flows. A "Payment Reminder" template can be used in multiple flows with different conditions.

---

### 2. NotificationBreakpoints
Registry of all available trigger points in the system.

```php
Schema:
- uid (string, PK) - Prefix: 'nbp'
- key (string, unique) - Code identifier e.g., 'order.completed', 'payment.due_reminder'
- name (string) - Human-readable name
- description (text) - What this breakpoint does
- category (string) - 'order' | 'payment' | 'user' | 'subscription' | 'system'
- available_placeholders (encoded array) - Placeholders available at this breakpoint
- trigger_type (string) - 'instant' | 'scheduled'
- is_system (boolean) - True if core system breakpoint (cannot be deleted)
- status (string) - 'active' | 'inactive'
- created_at (timestamp)
```

**Reasoning**: While breakpoints are code-defined, storing them in DB allows:
- Admin UI to display available breakpoints
- Enable/disable breakpoints without code changes
- Document available placeholders per breakpoint
- Track which breakpoints are in use

---

### 3. NotificationFlows
Connects breakpoints to templates with conditions and scheduling.

```php
Schema:
- uid (string, PK) - Prefix: 'nflw'
- name (string) - Flow name
- description (text, nullable)
- breakpoint (FK: NotificationBreakpoints) - Which trigger
- status (string) - 'active' | 'inactive' | 'draft'
- priority (integer) - Execution order (lower = first)
- starts_at (timestamp, nullable) - When flow becomes active
- ends_at (timestamp, nullable) - When flow deactivates
- conditions (encoded array) - JSON conditions for when to trigger
- created_by (FK: Users)
- created_at (timestamp)
- updated_at (timestamp)
```

**Conditions Structure Example**:
```json
{
  "user_type": ["consumer", "merchant"],
  "organisation": ["org_123", "org_456"],
  "min_amount": 100,
  "max_amount": 5000,
  "days_before": 1,
  "custom": {
    "field": "payment.installment_number",
    "operator": ">",
    "value": 1
  }
}
```

**Reasoning**: Flows are the "glue" - they define WHEN and UNDER WHAT CONDITIONS a notification fires. Time-based activation (starts_at/ends_at) enables promotional campaigns.

---

### 4. NotificationFlowActions
Links flows to templates (a flow can trigger multiple notifications).

```php
Schema:
- uid (string, PK) - Prefix: 'nfla'
- flow (FK: NotificationFlows)
- template (FK: NotificationTemplates)
- channel (string) - 'email' | 'sms' | 'bell' (redundant but useful for quick filtering)
- delay_minutes (integer, default: 0) - Delay after breakpoint triggers
- status (string) - 'active' | 'inactive'
- created_at (timestamp)
```

**Reasoning**: A single flow might send both an email AND a bell notification. Or send an email immediately and SMS 30 minutes later. This table enables that flexibility.

---

### 5. NotificationQueue
Queue for scheduled/delayed notifications.

```php
Schema:
- uid (string, PK) - Prefix: 'nq'
- flow_action (FK: NotificationFlowActions)
- recipient (FK: Users)
- recipient_email (string, nullable) - For non-user recipients
- recipient_phone (string, nullable) - For SMS to non-users
- channel (string) - 'email' | 'sms' | 'bell'
- subject (string, nullable) - Resolved subject
- content (text) - Resolved content (placeholders replaced)
- context_data (encoded array) - Original context for debugging
- status (string) - 'pending' | 'processing' | 'sent' | 'failed' | 'cancelled'
- scheduled_at (timestamp) - When to send
- sent_at (timestamp, nullable)
- attempts (integer, default: 0)
- last_error (text, nullable)
- created_at (timestamp)
```

**Reasoning**: Queue enables:
- Delayed notifications (send 1 day before payment)
- Retry logic for failed sends
- Audit trail of all notifications
- Cancellation of pending notifications

---

### 6. NotificationLogs
Permanent log of sent notifications (NotificationQueue gets cleaned up).

```php
Schema:
- uid (string, PK) - Prefix: 'nlog'
- flow (FK: NotificationFlows, nullable) - Can be null for manual sends
- template (FK: NotificationTemplates, nullable)
- breakpoint_key (string) - Which breakpoint triggered this
- recipient (FK: Users, nullable)
- recipient_identifier (string) - Email or phone used
- channel (string) - 'email' | 'sms' | 'bell'
- subject (string, nullable)
- content (text)
- status (string) - 'sent' | 'delivered' | 'failed' | 'bounced'
- metadata (encoded array) - Additional data (provider response, etc.)
- created_at (timestamp)
```

**Reasoning**: Separate from queue for:
- Long-term audit/compliance
- Analytics (how many emails sent per flow)
- Debugging delivery issues
- Queue cleanup without losing history

---

### 7. UserNotifications (Bell Notifications)
In-app notifications for users.

```php
Schema:
- uid (string, PK) - Prefix: 'un'
- user (FK: Users)
- title (string)
- content (text)
- type (string) - 'info' | 'success' | 'warning' | 'error'
- icon (string, nullable) - MDI icon class
- link (string, nullable) - Click action URL
- reference_type (string, nullable) - 'order' | 'payment' | 'flow' etc.
- reference_id (string, nullable) - Related object UID
- is_read (boolean, default: false)
- read_at (timestamp, nullable)
- created_at (timestamp)
```

**Reasoning**: Bell notifications need their own table because:
- They persist until read/dismissed
- Need read/unread state
- Can link to related objects
- Displayed in real-time via polling/websockets

---

## Breakpoints (Code-Defined Triggers)

### Instant Breakpoints (triggered immediately in code)

| Key | Category | Description | Available Placeholders |
|-----|----------|-------------|----------------------|
| `user.registered` | user | New user registration | user.*, |
| `user.email_verified` | user | Email verified | user.* |
| `user.password_reset` | user | Password reset requested | user.*, reset_link |
| `order.created` | order | New order created | user.*, order.*, organisation.*, location.* |
| `order.completed` | order | Order fully paid | user.*, order.*, organisation.*, location.* |
| `order.cancelled` | order | Order cancelled | user.*, order.*, organisation.*, location.*, reason |
| `payment.successful` | payment | Payment completed | user.*, payment.*, order.*, organisation.* |
| `payment.failed` | payment | Payment failed | user.*, payment.*, order.*, failure_reason |
| `payment.refunded` | payment | Payment refunded | user.*, payment.*, order.*, refund_amount |
| `subscription.created` | subscription | New subscription | user.*, subscription.*, organisation.* |
| `subscription.cancelled` | subscription | Subscription cancelled | user.*, subscription.*, reason |
| `organisation.member_invited` | organisation | Team member invited | inviter.*, invitee.*, organisation.*, invite_link |
| `organisation.member_joined` | organisation | Member accepted invite | user.*, organisation.* |

### Scheduled Breakpoints (triggered by cron)

| Key | Category | Description | Available Placeholders |
|-----|----------|-------------|----------------------|
| `payment.due_reminder` | payment | X days before payment due | user.*, payment.*, order.*, days_until_due |
| `payment.overdue_reminder` | payment | X days after payment overdue | user.*, payment.*, order.*, days_overdue |
| `subscription.renewal_reminder` | subscription | X days before renewal | user.*, subscription.*, days_until_renewal |
| `subscription.expiring` | subscription | Subscription about to expire | user.*, subscription.*, days_until_expiry |

---

## Dynamic Placeholders

Placeholders use dot notation and are replaced at send time.

### User Placeholders
```
{{user.full_name}}
{{user.first_name}}
{{user.email}}
{{user.phone}}
```

### Order Placeholders
```
{{order.uid}}
{{order.amount}}
{{order.currency}}
{{order.status}}
{{order.created_at}}
{{order.caption}}
```

### Payment Placeholders
```
{{payment.uid}}
{{payment.amount}}
{{payment.due_date}}
{{payment.installment_number}}
{{payment.total_installments}}
```

### Organisation Placeholders
```
{{organisation.name}}
{{organisation.email}}
```

### Location Placeholders
```
{{location.name}}
{{location.address}}
```

### System Placeholders
```
{{app.name}}          -> WeePay
{{app.url}}           -> https://wee-pay.dk
{{current.date}}      -> 12/01/2026
{{current.year}}      -> 2026
```

---

## Admin UI Pages

### 1. Notifications Overview (`/dashboard/notifications`)
- Summary cards: Active flows, Templates count, Queued notifications, Sent today
- Quick links to create templates/flows
- Recent notification activity

### 2. Notification Templates (`/dashboard/notifications/templates`)
- Paginated list of all templates
- Filter by type (email/sms/bell), status
- Create/Edit template with:
  - WYSIWYG editor for email HTML
  - Placeholder insertion tool
  - Preview with sample data
  - Test send functionality

### 3. Notification Flows (`/dashboard/notifications/flows`)
- List of all flows with status indicators
- Filter by breakpoint, status, date range
- Create/Edit flow:
  - Select breakpoint
  - Configure conditions
  - Add actions (templates to send)
  - Set time-based activation
  - Priority ordering

### 4. Notification Logs (`/dashboard/notifications/logs`)
- Paginated history of all sent notifications
- Filter by channel, status, date range, recipient
- View full notification content
- Resend failed notifications

### 5. Notification Queue (`/dashboard/notifications/queue`)
- View pending/scheduled notifications
- Cancel pending notifications
- Retry failed notifications
- Process queue manually (admin action)

---

## Implementation Phases

### Phase 1: Database & Models
- [ ] Create all 7 model files
- [ ] Create handler classes for each model
- [ ] Register handlers in Methods.php
- [ ] Run migrations to create tables
- [ ] Seed initial breakpoints

### Phase 2: Core Notification Engine
- [ ] Create `NotificationEngine` class
  - Placeholder resolution
  - Template rendering
  - Channel dispatchers (email, SMS, bell)
- [ ] Create `NotificationDispatcher` for each channel
- [ ] Create `BreakpointRegistry` class
- [ ] Implement queue processing

### Phase 3: Breakpoint Integration
- [ ] Add breakpoint triggers to existing code:
  - Order creation/completion
  - Payment success/failure
  - User registration
- [ ] Create cron job for scheduled breakpoints

### Phase 4: Admin UI - Templates
- [ ] Templates list page
- [ ] Template create/edit page
- [ ] Placeholder insertion component
- [ ] Preview functionality

### Phase 5: Admin UI - Flows
- [ ] Flows list page
- [ ] Flow create/edit page
- [ ] Condition builder component
- [ ] Action configuration

### Phase 6: Admin UI - Logs & Queue
- [ ] Notification logs page (paginated)
- [ ] Queue management page
- [ ] Analytics/stats

### Phase 7: Testing & Polish
- [ ] Test all breakpoints
- [ ] Test scheduled notifications
- [ ] Test condition evaluation
- [ ] Performance optimization

---

## Technical Considerations

### Why Separate Queue and Logs?
- **Queue** is operational - constantly changing, needs fast writes
- **Logs** are historical - append-only, needs efficient reads
- Queue can be purged regularly; logs kept for compliance

### Why Store Breakpoints in DB?
- Admin can see all available breakpoints without code knowledge
- Can disable breakpoints without deployment
- Documents available placeholders
- Future: Allow custom breakpoints via webhooks

### Condition Evaluation
Conditions are evaluated as AND by default. Complex OR logic can be achieved with multiple flows at same breakpoint.

```php
// Example condition evaluation
$conditions = [
    'user_type' => ['consumer'],
    'min_amount' => 100
];

// Passes if: user is consumer AND order amount >= 100
```

### Cron Job Design
Single cron job (`crn_notifications`) that:
1. Processes notification queue (pending items where scheduled_at <= now)
2. Checks scheduled breakpoints (payment due reminders, etc.)
3. Runs every 5-15 minutes

### SMS Integration
SMS requires external provider. Design is provider-agnostic:
- SMS dispatcher interface
- Implement for Twilio/MessageBird/etc.
- Store provider config in app settings

---

## File Structure

```
Database/model/
├── NotificationTemplates.php
├── NotificationBreakpoints.php
├── NotificationFlows.php
├── NotificationFlowActions.php
├── NotificationQueue.php
├── NotificationLogs.php
└── UserNotifications.php

classes/notifications/
├── NotificationEngine.php          # Core orchestrator
├── PlaceholderResolver.php         # Resolves {{placeholders}}
├── ConditionEvaluator.php          # Evaluates flow conditions
├── BreakpointRegistry.php          # Registry of breakpoints
├── dispatchers/
│   ├── EmailDispatcher.php
│   ├── SmsDispatcher.php
│   └── BellDispatcher.php
└── handlers/
    ├── NotificationTemplateHandler.php
    ├── NotificationFlowHandler.php
    ├── NotificationQueueHandler.php
    └── UserNotificationHandler.php

views/admin/dashboard/notifications/
├── index.php                       # Overview
├── templates.php                   # Template list
├── template-edit.php               # Create/edit template
├── flows.php                       # Flow list
├── flow-edit.php                   # Create/edit flow
├── logs.php                        # Notification logs
└── queue.php                       # Queue management

routing/routes/admin/
└── NotificationController.php      # Admin page controllers

routing/routes/api/admin/
└── NotificationApiController.php   # API endpoints
```

---

## API Endpoints

### Templates
- `GET /api/admin/notifications/templates` - List templates (paginated)
- `POST /api/admin/notifications/templates` - Create template
- `PUT /api/admin/notifications/templates/{uid}` - Update template
- `DELETE /api/admin/notifications/templates/{uid}` - Delete template
- `POST /api/admin/notifications/templates/{uid}/test` - Send test

### Flows
- `GET /api/admin/notifications/flows` - List flows
- `POST /api/admin/notifications/flows` - Create flow
- `PUT /api/admin/notifications/flows/{uid}` - Update flow
- `DELETE /api/admin/notifications/flows/{uid}` - Delete flow
- `PUT /api/admin/notifications/flows/{uid}/status` - Toggle status

### Queue & Logs
- `GET /api/admin/notifications/queue` - List queued (paginated)
- `DELETE /api/admin/notifications/queue/{uid}` - Cancel queued
- `POST /api/admin/notifications/queue/{uid}/retry` - Retry failed
- `GET /api/admin/notifications/logs` - List logs (paginated)

### Breakpoints
- `GET /api/admin/notifications/breakpoints` - List all breakpoints

---

## Questions to Resolve

1. **SMS Provider**: Which SMS provider to integrate? (Twilio, MessageBird, local provider?)
2. **Email Provider**: Continue using PHP mail() or integrate SendGrid/Mailgun?
3. **Bell Notification Delivery**: Polling or WebSocket for real-time?
4. **Retention Policy**: How long to keep notification logs?
5. **Rate Limiting**: Max notifications per user per hour/day?

---

## Progress Tracking

| Phase | Status | Notes |
|-------|--------|-------|
| Phase 1: Database & Models | Not Started | |
| Phase 2: Core Engine | Not Started | |
| Phase 3: Breakpoint Integration | Not Started | |
| Phase 4: Admin UI - Templates | Not Started | |
| Phase 5: Admin UI - Flows | Not Started | |
| Phase 6: Admin UI - Logs | Not Started | |
| Phase 7: Testing | Not Started | |

---

*Last Updated: 2026-01-12*
