# BookQu Development Tracker

> **Document Status:** Current Development Tracking Baseline
> **Version:** 1.0
> **Authority:** Current implementation tracking document
> **Product Definition:** `docs/PRODUCT.md`
> **Requirements:** `docs/REQUIREMENTS.md`
> **Architecture:** `docs/ARCHITECTURE.md`
> **Development Workflow:** `docs/DEVELOPMENT.md`
> **Last Updated:** 2026-09-26
>
> This document tracks the implementation state of BookQu against the current requirements baseline.
>
> This tracker replaces the previous feature tracker as the primary implementation tracking reference.

---

# 1. Purpose

The purpose of this tracker is to answer:

> **What has been implemented, what is currently being worked on, what remains, what has been tested, and what still requires architectural improvement?**

The tracker exists to maintain traceability between:

```text
Requirement
    ↓
Feature
    ↓
Implementation
    ↓
Test
    ↓
Architecture State
    ↓
Development Status
```

The tracker is not a replacement for `REQUIREMENTS.md`.

Requirements define what BookQu must do.

This document defines implementation status.

---

# 2. Important Status Distinction

BookQu separates **functional completion** from **architectural completion**.

A feature can be:

```text
Functional Status: Done
Architecture Status: Needs Refactor
```

This means the current behavior is considered implemented, but the implementation does not yet fully follow the target architecture.

This distinction is required because the current BookQu codebase contains functionality that was implemented before the architecture was formally reorganized.

---

# 3. Status Definitions

## 3.1 Planned

The requirement has been accepted but implementation has not started.

```text
Implementation: 0%
```

---

## 3.2 In Progress

Implementation is actively being developed.

```text
Implementation: Active
```

---

## 3.3 Testing

Implementation is substantially complete and currently being verified.

---

## 3.4 Done

The intended functionality has been implemented and the relevant verification is considered sufficient for the current scope.

`Done` does not automatically mean architecturally perfect.

---

## 3.5 Needs Refactor

The feature works or is substantially implemented, but the implementation has significant architectural or maintainability debt.

---

## 3.6 Blocked

Implementation cannot continue because of a known dependency or external blocker.

---

## 3.7 Deprecated

The requirement or implementation has been intentionally removed from the current product baseline.

---

## 3.8 Needs Verification

The feature appears to be implemented, but the available evidence is not sufficient to mark it verified.

---

# 4. Architecture Status

Each major feature should also have an architecture state.

| Status           | Meaning                                       |
| ---------------- | --------------------------------------------- |
| `Target`         | Follows target architecture                   |
| `Legacy`         | Works but follows older architecture          |
| `Needs Refactor` | Working but should be structurally improved   |
| `Blocked`        | Refactor cannot yet proceed                   |
| `Not Evaluated`  | Architecture audit has not yet been completed |

---

# 5. Test Status

| Status    | Meaning                                     |
| --------- | ------------------------------------------- |
| `PASS`    | Relevant automated tests pass               |
| `PARTIAL` | Some behavior is tested                     |
| `MANUAL`  | Verified manually                           |
| `MISSING` | Relevant automated test not yet established |
| `FAIL`    | Current verification fails                  |
| `N/A`     | Automated test not applicable               |
| `UNKNOWN` | Verification has not yet been established   |

---

# 6. Priority Definitions

| Priority | Meaning                           |
| -------- | --------------------------------- |
| `P0`     | Core product capability           |
| `P1`     | Supporting operational capability |
| `P2`     | Future / expansion capability     |

Priority represents product importance, not implementation difficulty.

---

# 7. Current High-Level Status

The current BookQu implementation already contains a substantial portion of the core product and several supporting modules.

The current repository includes implemented areas such as:

```text
Authentication
Multi-Tenancy
Business Setup
Service Management
Schedule Management
Booking
Walk-In Booking
Customer Management
Calendar
Dashboard
Payment
Reviews
Categories
Staff & Resources
Additional Items
Vouchers
Analytics
Reports
Assets
Appearance
Subscription
Notifications
Admin Dashboard
```

However, implementation completeness and architecture completeness are not identical.

The next development phase should therefore focus on:

```text
Behavior stabilization
+
Requirement traceability
+
Architecture refactoring
+
Test strengthening
```

rather than uncontrolled feature expansion.

---

# 8. Current Core Product Tracker

## 8.1 Authentication

| ID          | Requirement             | Status      | Test           | Architecture | Notes                              |
| ----------- | ----------------------- | ----------- | -------------- | ------------ | ---------------------------------- |
| FR-AUTH-001 | Owner registration      | Implemented | PASS / PARTIAL | Legacy       | Existing auth flow                 |
| FR-AUTH-002 | Owner login             | Implemented | PASS           | Legacy       | Existing auth flow                 |
| FR-AUTH-003 | Owner logout            | Implemented | PASS           | Legacy       | Existing auth flow                 |
| FR-AUTH-004 | Email verification      | Implemented | PASS / PARTIAL | Legacy       | Existing Laravel verification flow |
| FR-AUTH-005 | Role-based access       | Implemented | PASS           | Legacy       | Owner/Admin separation exists      |
| FR-AUTH-006 | Secure password hashing | Implemented | PASS           | Target       | Laravel hashing                    |

### Authentication Follow-up

```text
Priority: Medium

[ ] Audit authentication architecture
[ ] Consolidate authorization strategy
[ ] Verify all protected owner routes
[ ] Add missing authorization regression tests
```

---

# 9. Tenant and Business Management

| ID            | Requirement          | Status      | Test           | Architecture       | Notes                            |
| ------------- | -------------------- | ----------- | -------------- | ------------------ | -------------------------------- |
| FR-TENANT-001 | Tenant creation      | Implemented | PARTIAL        | Needs Refactor     | Tenant linked to owner           |
| FR-TENANT-002 | Tenant isolation     | Done        | PASS           | Target             | Tenant context/scope implemented |
| FR-TENANT-003 | Tenant context       | Done        | PASS           | Target             | TenantContext exists             |
| FR-TENANT-004 | Business profile     | Done        | PASS / PARTIAL | Needs Refactor     | Owner settings/profile           |
| FR-TENANT-005 | Business slug        | Done        | PASS           | Needs Refactor     | Slug-based public access         |
| FR-TENANT-006 | Public tenant access | Done        | PASS           | Needs Refactor     | `/{slug}` route                  |
| FR-TENANT-007 | Custom domain        | Implemented | PARTIAL        | Needs Verification | Custom-domain routing exists     |

### Tenant Follow-up

```text
Priority: High

[ ] Complete tenant isolation audit
[ ] Verify every tenant-owned model
[ ] Verify every intentional `withoutGlobalScopes()` usage
[ ] Verify custom domain behavior
[ ] Document tenant resolution flow
[ ] Strengthen cross-tenant security tests
```

---

# 10. Public Business Page

| ID            | Requirement          | Status      | Test    | Architecture   | Notes                                  |
| ------------- | -------------------- | ----------- | ------- | -------------- | -------------------------------------- |
| FR-PUBLIC-001 | Public business page | Done        | PASS    | Needs Refactor | Public booking page exists             |
| FR-PUBLIC-002 | Public services      | Done        | PASS    | Needs Refactor | Active services shown                  |
| FR-PUBLIC-003 | Public branding      | Implemented | PARTIAL | Needs Refactor | Appearance/assets                      |
| FR-PUBLIC-004 | Booking entry point  | Done        | PASS    | Needs Refactor | Public booking starts from tenant page |

---

# 11. Service Management

| ID             | Requirement                 | Status | Test | Architecture | Notes                                              |
| -------------- | --------------------------- | ------ | ---- | ------------ | -------------------------------------------------- |
| FR-SERVICE-001 | Create service              | Done   | PASS | Target       | CreateService action & StoreServiceRequest (RF-04) |
| FR-SERVICE-002 | Service information         | Done   | PASS | Target       | Handled by Service Actions & Requests              |
| FR-SERVICE-003 | Update service              | Done   | PASS | Target       | UpdateService action & UpdateServiceRequest (RF-04)|
| FR-SERVICE-004 | Activate/deactivate         | Done   | PASS | Target       | ToggleServiceStatus action (RF-04)                 |
| FR-SERVICE-005 | Inactive service protection | Done   | PASS | Target       | Public booking & availability protection           |
| FR-SERVICE-006 | Deletion protection         | Done   | PASS | Target       | DeleteService action with active booking guard     |
| FR-SERVICE-007 | Service tenant ownership    | Done   | PASS | Target       | Tenant isolation enforced                          |

### Service Refactor

```text
Status: Completed (RF-04)

[x] CreateService Application Action
[x] UpdateService Application Action
[x] DeleteService Application Action
[x] ToggleServiceStatus Application Action
[x] StoreServiceRequest & UpdateServiceRequest Form Requests
[x] OwnerProgramController refactored into thin HTTP adapter
```

---

# 12. Categories

| ID              | Requirement         | Status      | Test    | Architecture   | Notes                         |
| --------------- | ------------------- | ----------- | ------- | -------------- | ----------------------------- |
| FR-CATEGORY-001 | Create category     | Implemented | PASS    | Needs Refactor | Existing CRUD                 |
| FR-CATEGORY-002 | Update category     | Implemented | PASS    | Needs Refactor | Existing CRUD                 |
| FR-CATEGORY-003 | Delete category     | Implemented | PASS    | Needs Refactor | Existing CRUD                 |
| FR-CATEGORY-004 | Category status     | Implemented | PARTIAL | Needs Refactor | Existing toggle               |
| FR-CATEGORY-005 | Service association | Implemented | PASS    | Needs Refactor | Category/service relationship |

Source implementation is already covered by owner module tests.

---

# 13. Schedule Management

| ID              | Requirement                | Status | Test | Architecture | Notes                                                  |
| --------------- | -------------------------- | ------ | ---- | ------------ | ------------------------------------------------------ |
| FR-SCHEDULE-001 | Create schedule            | Done   | PASS | Target       | BulkCreateSchedules action                             |
| FR-SCHEDULE-002 | Bulk schedule creation     | Done   | PASS | Target       | Bulk slot generator action                             |
| FR-SCHEDULE-003 | Schedule pricing           | Done   | PASS | Target       | ScheduleConflictRules pricing calculation              |
| FR-SCHEDULE-004 | Availability configuration | Done   | PASS | Target       | UpdateScheduleAvailability action                      |
| FR-SCHEDULE-005 | Blocked dates              | Done   | PASS | Target       | Owner blocked dates & DeleteBlockedDate action         |
| FR-SCHEDULE-006 | Delete schedule            | Done   | PASS | Target       | DeleteSchedule action with deletion safety             |
| FR-SCHEDULE-007 | Conflict prevention        | Done   | PASS | Target       | ScheduleConflictRules & SlotCompatibilityRules         |
| FR-SCHEDULE-008 | Tenant ownership           | Done   | PASS | Target       | Tenant isolation enforced                              |
| FR-SCHEDULE-009 | Booking availability       | Done   | PASS | Target       | AvailabilityRules & GetAvailableSchedules operation   |
| FR-SCHEDULE-010 | Past schedule protection   | Done   | PASS | Target       | AvailabilityRules past slot protection                 |

### Schedule Refactor Priority

```text
Status: Completed (RF-03)

[x] Extract availability rules
[x] Consolidate conflict validation
[x] Separate schedule generation from HTTP controller
[x] Create reusable booking availability operation
```

---

# 14. Booking

Booking is currently the most important implementation domain.

| ID             | Requirement               | Status   | Test | Architecture   | Notes                                           |
| -------------- | ------------------------- | -------- | ---- | -------------- | ----------------------------------------------- |
| FR-BOOKING-001 | Service selection         | Done     | PASS | Needs Refactor | Customer flow                                   |
| FR-BOOKING-002 | Date selection            | Done     | PASS | Needs Refactor | Customer flow                                   |
| FR-BOOKING-003 | Time selection            | Done     | PASS | Needs Refactor | Customer flow                                   |
| FR-BOOKING-004 | Customer information      | Done     | PASS | Target         | Validated via CreateBookingRequest              |
| FR-BOOKING-005 | Booking creation          | Done     | PASS | Target         | Refactored to CreateBooking Action              |
| FR-BOOKING-006 | Booking code              | Done     | PASS | Target         | Booking model                                   |
| FR-BOOKING-007 | Booking status            | Done     | PASS | Target         | Centralized in BookingState domain              |
| FR-BOOKING-008 | Booking detail            | Done     | PASS | Target         | Tokenized management / owner detail             |
| FR-BOOKING-009 | Owner status management   | Done     | PASS | Target         | Refactored to UpdateBookingStatus Action        |
| FR-BOOKING-010 | Walk-in booking           | Done     | PASS | Target         | Refactored to CreateWalkInBooking Action        |
| FR-BOOKING-011 | Owner reschedule          | Done     | PASS | Target         | Shared RescheduleBooking Action                 |
| FR-BOOKING-012 | Customer reschedule       | Done     | PASS | Target         | Shared RescheduleBooking Action                 |
| FR-BOOKING-013 | Customer cancellation     | Done     | PASS | Target         | Shared CancelBooking Action                     |
| FR-BOOKING-014 | Owner cancellation        | Done     | PASS | Target         | Shared CancelBooking Action                     |
| FR-BOOKING-015 | Booking security          | Done     | PASS | Target         | Secure tokens + constant-time hash_equals       |
| FR-BOOKING-016 | Management token          | Done     | PASS | Target         | Secure token flow                               |
| FR-BOOKING-017 | Availability protection   | Done     | PASS | Target         | Centralized in BookingRules                     |
| FR-BOOKING-018 | Double booking protection | Verified | PASS | Target         | Concurrency tests                               |
| FR-BOOKING-019 | Tenant validation         | Verified | PASS | Target         | Integration/security tests                      |

### Booking Architecture Assessment

RF-01 (Booking Refactor) has been implemented:
- Extracted heavy business workflows out of `BookingController`, `OwnerBookingController`, and `BookingManageController`.
- Established `app/Actions/Booking/`:
  - `CreateBooking`
  - `CreateWalkInBooking`
  - `CancelBooking`
  - `RescheduleBooking`
  - `UpdateBookingStatus`
- Established `app/Domain/Booking/`:
  - `BookingRules`
  - `BookingState`
- Established `app/Http/Requests/Booking/`:
  - `CreateBookingRequest`
  - `RescheduleBookingRequest`
  - `UpdateBookingStatusRequest`
- All regression and specification tests pass without changes to product behavior.

---

# 15. Multi-Slot Booking

| ID               | Requirement             | Status   | Test | Architecture   | Notes                      |
| ---------------- | ----------------------- | -------- | ---- | -------------- | -------------------------- |
| FR-MULTIBOOK-001 | Multi-slot selection    | Done     | PASS | Needs Refactor | Production logic tests     |
| FR-MULTIBOOK-002 | Slot compatibility      | Verified | PASS | Needs Refactor | Contiguous-slot validation |
| FR-MULTIBOOK-003 | Unified payment         | Verified | PASS | Needs Refactor | Payment group              |
| FR-MULTIBOOK-004 | Unified invoice         | Verified | PASS | Needs Refactor | Group invoice              |
| FR-MULTIBOOK-005 | Multi-slot cancellation | Verified | PASS | Needs Refactor | Explicit restrictions      |
| FR-MULTIBOOK-006 | Multi-slot reschedule   | Verified | PASS | Needs Refactor | Explicit restrictions      |

Existing tests include multi-slot checkout, payment grouping, invoice behavior, cancellation, and rescheduling constraints.

---

# 16. Customer Management

| ID              | Requirement        | Status      | Test           | Architecture       | Notes                        |
| --------------- | ------------------ | ----------- | -------------- | ------------------ | ---------------------------- |
| FR-CUSTOMER-001 | Customer record    | Done        | PASS           | Needs Refactor     | Booking-linked customer data |
| FR-CUSTOMER-002 | Customer directory | Done        | PASS           | Needs Refactor     | Owner module                 |
| FR-CUSTOMER-003 | Booking history    | Done        | PASS           | PARTIAL            | Owner/customer flows         |
| FR-CUSTOMER-004 | Customer notes     | Implemented | PASS           | Needs Refactor     | CustomerNote model           |
| FR-CUSTOMER-005 | Customer privacy   | Implemented | PASS / PARTIAL | Needs Verification | Requires ongoing audit       |

---

# 17. Payment

| ID             | Requirement                | Status   | Test | Architecture | Notes                           |
| -------------- | -------------------------- | -------- | ---- | ------------ | ------------------------------- |
| FR-PAYMENT-001 | Booking payment            | Done     | PASS | Target       | CreateBookingPayment Action     |
| FR-PAYMENT-002 | Midtrans integration       | Done     | PASS | Target       | MidtransPaymentGateway boundary |
| FR-PAYMENT-003 | Payment status             | Done     | PASS | Target       | Domain PaymentState mapping     |
| FR-PAYMENT-004 | External payment reference | Done     | PASS | Target       | PaymentRules order ID generator |
| FR-PAYMENT-005 | Trusted verification       | Verified | PASS | Target       | CheckPaymentStatus Action       |
| FR-PAYMENT-006 | Webhook                    | Done     | PASS | Target       | ProcessPaymentWebhook Action    |
| FR-PAYMENT-007 | Webhook idempotency        | Verified | PASS | Target       | SynchronizePaymentStatus Action |
| FR-PAYMENT-008 | Payment lifecycle          | Done     | PASS | Target       | SynchronizePaymentStatus/Expire |
| FR-PAYMENT-009 | Booking synchronization    | Done     | PASS | Target       | SynchronizePaymentStatus Action |
| FR-PAYMENT-010 | Failed payment protection  | Verified | PASS | Target       | Production logic & domain rules |

### Payment Architecture Assessment

RF-02 (Payment Refactor) has been implemented:
- Isolated Midtrans provider implementation into `app/Infrastructure/Payments/Midtrans/MidtransPaymentGateway.php`.
- Established Payment Domain in `app/Domain/Payment/`:
  - `PaymentState.php` (constants, status mapping, terminal & transition rules)
  - `PaymentRules.php` (expiry, eligibility, signature verification, order ID generation, subscription price calculation)
- Established Payment Application Actions in `app/Actions/Payment/`:
  - `CreateBookingPayment.php` (creation of paid and free booking payments, snap token generation)
  - `CreateSubscriptionPayment.php` (owner subscription checkout and snap token generation)
  - `CheckPaymentStatus.php` (fast-path check and gateway query synchronization)
  - `SynchronizePaymentStatus.php` (atomic DB transactions, row-level locking, booking/subscription state sync, notifications, emails, cache invalidation)
  - `ExpirePayment.php` (atomic payment expiration and booking slot release)
  - `ProcessPaymentWebhook.php` (signature verification, tenant isolation context, and status sync)
- Decomposed `MidtransPaymentService.php` from a 491-line monolithic service into an 80-line coordinator delegating to the new actions and gateway.
- Decomposed `MidtransWebhookController` into a clean HTTP adapter over `ProcessPaymentWebhook`.
- Decomposed `OwnerCheckoutController` and `CreateBooking` to delegate payment workflows to dedicated Actions.
- Added comprehensive unit and feature tests covering payment domain rules, actions, and webhook flow (`PaymentStateTest`, `PaymentRulesTest`, `PaymentActionTest`).
- Preserved 100% backward compatibility for existing callers and tests.

---

# 18. Booking Management Without Account

| ID            | Requirement           | Status | Test | Architecture   | Notes               |
| ------------- | --------------------- | ------ | ---- | -------------- | ------------------- |
| FR-MANAGE-001 | Secure booking access | Done   | PASS | Needs Refactor | `/manage`           |
| FR-MANAGE-002 | Secure token          | Done   | PASS | Needs Refactor | Token-based access  |
| FR-MANAGE-003 | Booking details       | Done   | PASS | Needs Refactor | Customer management |
| FR-MANAGE-004 | Payment information   | Done   | PASS | Needs Refactor | Payment group       |
| FR-MANAGE-005 | Cancellation          | Done   | PASS | Needs Refactor | Customer manage     |
| FR-MANAGE-006 | Rescheduling          | Done   | PASS | Needs Refactor | Customer manage     |
| FR-MANAGE-007 | Review submission     | Done   | PASS | Needs Refactor | Customer review     |

---

# 19. Additional Items

| ID           | Requirement            | Status      | Test | Architecture   | Notes                       |
| ------------ | ---------------------- | ----------- | ---- | -------------- | --------------------------- |
| FR-ADDON-001 | Create additional item | Implemented | PASS | Needs Refactor | Existing module             |
| FR-ADDON-002 | Update additional item | Implemented | PASS | Needs Refactor | Existing module             |
| FR-ADDON-003 | Activation             | Implemented | PASS | Needs Refactor | Toggle                      |
| FR-ADDON-004 | Service association    | Implemented | PASS | Needs Refactor | Relationship                |
| FR-ADDON-005 | Checkout integration   | Implemented | PASS | Needs Refactor | Public checkout integration |

---

# 20. Vouchers

| ID             | Requirement        | Status      | Test | Architecture   | Notes                     |
| -------------- | ------------------ | ----------- | ---- | -------------- | ------------------------- |
| FR-VOUCHER-001 | Create voucher     | Implemented | PASS | Needs Refactor | Existing CRUD             |
| FR-VOUCHER-002 | Configure rules    | Implemented | PASS | Needs Refactor | Existing model            |
| FR-VOUCHER-003 | Validate voucher   | Implemented | PASS | Needs Refactor | Customer validation       |
| FR-VOUCHER-004 | Tenant isolation   | Verified    | PASS | Needs Refactor | Isolation tests           |
| FR-VOUCHER-005 | Calculate discount | Implemented | PASS | Needs Refactor | Needs central domain rule |

### Voucher Refactor

```text
Priority: Medium

[ ] Extract voucher eligibility
[ ] Extract pricing calculation
[ ] Remove duplicated validation from booking controller
```

---

# 21. Staff and Resources

| ID                   | Requirement                 | Status      | Test | Architecture   | Notes                               |
| -------------------- | --------------------------- | ----------- | ---- | -------------- | ----------------------------------- |
| FR-STAFF-001         | Staff CRUD                  | Implemented | PASS | Needs Refactor | Existing module                     |
| FR-RESOURCE-001      | Resource CRUD               | Implemented | PASS | Needs Refactor | Existing module                     |
| FR-STAFFRESOURCE-001 | Service association         | Implemented | PASS | Needs Refactor | Pivot relationships                 |
| FR-STAFFRESOURCE-002 | Active resource constraint  | Verified    | PASS | Needs Refactor | Integration tests                   |
| FR-STAFFRESOURCE-003 | Customer selection boundary | Verified    | PASS | Needs Refactor | Customer flow explicitly restricted |

Important:

```text
Staff/resource availability exists,
but customer selection is not automatically part of the booking flow.
```

---

# 22. Reviews

| ID            | Requirement             | Status      | Test           | Architecture   | Notes                 |
| ------------- | ----------------------- | ----------- | -------------- | -------------- | --------------------- |
| FR-REVIEW-001 | Review eligibility      | Done        | PASS           | Needs Refactor | Booking-linked        |
| FR-REVIEW-002 | Review submission       | Done        | PASS           | Needs Refactor | Customer flow         |
| FR-REVIEW-003 | Rating                  | Done        | PASS           | Needs Refactor | Existing              |
| FR-REVIEW-004 | Comment                 | Done        | PASS           | Needs Refactor | Existing              |
| FR-REVIEW-005 | Review ownership        | Verified    | PASS           | Needs Refactor | Tenant isolation      |
| FR-REVIEW-006 | Owner review management | Implemented | PASS           | Needs Refactor | Reply/visibility      |
| FR-REVIEW-007 | One review per booking  | Implemented | PASS / PARTIAL | Needs Refactor | Verify all edge cases |

---

# 23. Dashboard

| ID          | Requirement              | Status             | Test           | Architecture | Notes                                                |
| ----------- | ------------------------ | ------------------ | -------------- | ------------ | ---------------------------------------------------- |
| FR-DASH-001 | Business overview        | Done               | PASS           | Target       | GetOwnerDashboardOverview action (RF-04)             |
| FR-DASH-002 | Booking metrics          | Done               | PASS           | Target       | Dashboard metrics via GetOwnerDashboardOverview      |
| FR-DASH-003 | Revenue metrics          | Done               | PASS           | Target       | Dashboard metrics via GetOwnerDashboardOverview      |
| FR-DASH-004 | Customer metrics         | Done               | PASS           | Target       | Dashboard metrics via GetOwnerDashboardOverview      |
| FR-DASH-005 | Service metrics          | Implemented        | PASS / PARTIAL | Target       | Dashboard metrics via GetOwnerDashboardOverview      |
| FR-DASH-006 | Recent activity          | Done               | PASS           | Target       | Dashboard metrics via GetOwnerDashboardOverview      |
| FR-DASH-007 | Dashboard data integrity | Needs Verification | PARTIAL        | Target       | Tenant isolated queries via GetOwnerDashboardOverview|

### Dashboard Refactor Priority

```text
Status: Completed (RF-04)

[x] Extract dashboard queries from controller into GetOwnerDashboardOverview
[x] Centralize dashboard metrics
[x] Separate dashboard presentation data
[x] Verify metric definitions against requirements
[x] Reduce dashboard query complexity
```

---

# 24. Calendar

| ID              | Requirement         | Status | Test | Architecture | Notes                                                |
| --------------- | ------------------- | ------ | ---- | ------------ | ---------------------------------------------------- |
| FR-CALENDAR-001 | Calendar view       | Done   | PASS | Target       | OwnerCalendarController & GetOwnerCalendarData(RF-04)|
| FR-CALENDAR-002 | Date navigation     | Done   | PASS | Target       | Handled by GetOwnerCalendarData action               |
| FR-CALENDAR-003 | Booking visibility  | Done   | PASS | Target       | Handled by GetOwnerCalendarData action               |
| FR-CALENDAR-004 | Schedule visibility | Done   | PASS | Target       | Handled by GetOwnerCalendarData action               |
| FR-CALENDAR-005 | Filtering           | Done   | PASS | Target       | Handled by GetOwnerCalendarData action               |
| FR-CALENDAR-006 | Walk-in operation   | Done   | PASS | Target       | Delegated to CreateWalkInBooking Action              |

The current calendar view Blade file will be refined under RF-05 Presentation layer.

---

# 25. Analytics

| ID               | Requirement              | Status             | Test           | Architecture   | Notes                       |
| ---------------- | ------------------------ | ------------------ | -------------- | -------------- | --------------------------- |
| FR-ANALYTICS-001 | Operational analytics    | Done               | PASS           | Needs Refactor | Existing analytics          |
| FR-ANALYTICS-002 | Booking analysis         | Done               | PASS           | Needs Refactor | Existing analytics          |
| FR-ANALYTICS-003 | Revenue analysis         | Implemented        | PASS / PARTIAL | Needs Refactor | Needs metric audit          |
| FR-ANALYTICS-004 | Service analysis         | Implemented        | PARTIAL        | Needs Refactor | Needs coverage              |
| FR-ANALYTICS-005 | Schedule utilization     | Implemented        | PASS           | Needs Refactor | Schedule report/tests       |
| FR-ANALYTICS-006 | Analytics data integrity | Needs Verification | PARTIAL        | Needs Refactor | Needs source-of-truth audit |

---

# 26. Reporting

| ID            | Requirement      | Status      | Test | Architecture | Notes                                                |
| ------------- | ---------------- | ----------- | ---- | ------------ | ---------------------------------------------------- |
| FR-REPORT-001 | Schedule report  | Implemented | PASS | Target       | OwnerScheduleReportController & GenerateScheduleReport (RF-04) |
| FR-REPORT-002 | Report filtering | Implemented | PASS | Target       | Handled by GenerateScheduleReport action             |
| FR-REPORT-003 | Report export    | Implemented | PASS | Target       | Handled by ExportScheduleReport action (CSV stream)  |

---

# 27. Appearance

| ID                | Requirement         | Status      | Test             | Architecture   | Notes               |
| ----------------- | ------------------- | ----------- | ---------------- | -------------- | ------------------- |
| FR-APPEARANCE-001 | Logo                | Implemented | MANUAL / PARTIAL | Needs Refactor | Public presentation |
| FR-APPEARANCE-002 | Brand color         | Implemented | MANUAL           | Needs Refactor | Current appearance  |
| FR-APPEARANCE-003 | Banner / cover      | Implemented | MANUAL           | Needs Refactor | Asset integration   |
| FR-APPEARANCE-004 | Public presentation | Implemented | MANUAL / PARTIAL | Needs Refactor | Needs visual QA     |

---

# 28. Assets

| ID           | Requirement      | Status      | Test           | Architecture   | Notes               |
| ------------ | ---------------- | ----------- | -------------- | -------------- | ------------------- |
| FR-ASSET-001 | Asset storage    | Implemented | PASS / PARTIAL | Needs Refactor | Storage integration |
| FR-ASSET-002 | Asset ownership  | Implemented | PASS           | Needs Refactor | Tenant-specific     |
| FR-ASSET-003 | Asset management | Implemented | PASS           | Needs Refactor | CRUD                |
| FR-ASSET-004 | Asset usage      | Implemented | MANUAL         | Needs Refactor | Public page         |

---

# 29. Notifications

| ID                  | Requirement                 | Status             | Test              | Architecture   | Notes                     |
| ------------------- | --------------------------- | ------------------ | ----------------- | -------------- | ------------------------- |
| FR-NOTIFICATION-001 | Booking notification        | Implemented        | PARTIAL           | Needs Refactor | Existing notifications    |
| FR-NOTIFICATION-002 | Booking status notification | Implemented        | PARTIAL           | Needs Refactor | Existing                  |
| FR-NOTIFICATION-003 | Payment notification        | Implemented        | PARTIAL           | Needs Refactor | Existing                  |
| FR-NOTIFICATION-004 | Subscription notification   | Implemented        | PARTIAL           | Needs Refactor | Trial notification exists |
| FR-NOTIFICATION-005 | Notification isolation      | Needs Verification | MISSING / PARTIAL | Needs Refactor | Requires security audit   |

---

# 30. Subscription

| ID         | Requirement            | Status      | Test           | Architecture   | Notes                   |
| ---------- | ---------------------- | ----------- | -------------- | -------------- | ----------------------- |
| FR-SUB-001 | Subscription plans     | Done        | PASS           | Needs Refactor | Plan model              |
| FR-SUB-002 | Tenant subscription    | Done        | PASS           | Needs Refactor | Subscription model      |
| FR-SUB-003 | Trial                  | Implemented | PASS           | Needs Refactor | Existing lifecycle      |
| FR-SUB-004 | Subscription status    | Done        | PASS           | Needs Refactor | Lifecycle states        |
| FR-SUB-005 | Feature access         | Implemented | PASS           | Needs Refactor | Middleware              |
| FR-SUB-006 | Feature entitlement    | Implemented | PARTIAL        | Needs Refactor | Centralization required |
| FR-SUB-007 | Subscription payment   | Implemented | PASS           | Needs Refactor | Owner checkout          |
| FR-SUB-008 | Subscription callback  | Implemented | PASS           | Needs Refactor | Payment lifecycle       |
| FR-SUB-009 | Subscription lifecycle | Implemented | PASS           | Needs Refactor | Scheduler/console       |
| FR-SUB-010 | Usage limits           | Implemented | PASS / PARTIAL | Needs Refactor | Requires central rule   |
| FR-SUB-011 | Usage tracking         | Implemented | PARTIAL        | Needs Refactor | UsageLog                |

---

# 31. Platform Admin

| ID           | Requirement          | Status             | Test    | Architecture       | Notes                 |
| ------------ | -------------------- | ------------------ | ------- | ------------------ | --------------------- |
| FR-ADMIN-001 | Admin authentication | Implemented        | PASS    | Legacy             | Role middleware       |
| FR-ADMIN-002 | Admin dashboard      | Implemented        | PASS    | Needs Refactor     | Limited current scope |
| FR-ADMIN-003 | Tenant oversight     | Needs Verification | PARTIAL | Needs Verification | Current scope limited |
| FR-ADMIN-004 | Platform boundary    | Implemented        | PASS    | Target             | Role separation       |

---

# 32. Data Integrity

| ID          | Requirement                | Status      | Test           | Architecture   | Notes                      |
| ----------- | -------------------------- | ----------- | -------------- | -------------- | -------------------------- |
| FR-DATA-001 | Referential integrity      | Implemented | PASS / PARTIAL | Target         | DB relationships           |
| FR-DATA-002 | Tenant integrity           | Verified    | PASS           | Target         | Tenant isolation           |
| FR-DATA-003 | Booking integrity          | Verified    | PASS           | Needs Refactor | Production logic tests     |
| FR-DATA-004 | Payment integrity          | Verified    | PASS           | Needs Refactor | Payment tests              |
| FR-DATA-005 | Atomic critical operations | Implemented | PASS / PARTIAL | Needs Refactor | Transaction audit required |
| FR-DATA-006 | Double booking prevention  | Verified    | PASS           | Target         | Concurrency protection     |

---

# 33. Security

| ID          | Requirement               | Status             | Test           | Architecture       | Notes                         |
| ----------- | ------------------------- | ------------------ | -------------- | ------------------ | ----------------------------- |
| NFR-SEC-001 | HTTPS                     | Needs Verification | N/A            | Deployment         | Environment concern           |
| NFR-SEC-002 | Password hashing          | Verified           | PASS           | Target             | Laravel hashing               |
| NFR-SEC-003 | SQL injection protection  | Implemented        | PASS / PARTIAL | Target             | ORM/query usage               |
| NFR-SEC-004 | XSS protection            | Implemented        | PARTIAL        | Needs Verification | Requires broader audit        |
| NFR-SEC-005 | CSRF protection           | Implemented        | PASS / PARTIAL | Target             | Web framework                 |
| NFR-SEC-006 | Authorization             | Implemented        | PASS / PARTIAL | Needs Refactor     | Requires route/action audit   |
| NFR-SEC-007 | Tenant isolation          | Verified           | PASS           | Target             | Critical                      |
| NFR-SEC-008 | IDOR protection           | Verified           | PASS           | Needs Refactor     | Security tests exist          |
| NFR-SEC-009 | Payment callback security | Verified           | PASS           | Needs Refactor     | Callback hardening            |
| NFR-SEC-010 | Secret protection         | Needs Verification | PARTIAL        | Deployment         | Requires repository/env audit |

---

# 34. Performance

| ID           | Requirement                 | Status             | Test    | Architecture       | Notes                    |
| ------------ | --------------------------- | ------------------ | ------- | ------------------ | ------------------------ |
| NFR-PERF-001 | Normal requests target ≤2s  | Needs Verification | MISSING | Needs Refactor     | Requires measurement     |
| NFR-PERF-002 | Heavy operations target ≤5s | Needs Verification | MISSING | Needs Verification | Requires measurement     |
| NFR-PERF-003 | Fresh availability          | Implemented        | PARTIAL | Needs Refactor     | Cache invalidation audit |
| NFR-PERF-004 | Query efficiency            | Needs Verification | MISSING | Needs Refactor     | Query audit required     |
| NFR-PERF-005 | Appropriate caching         | Implemented        | PARTIAL | Needs Refactor     | Existing cache strategy  |

---

# 35. Scalability

| ID            | Requirement                  | Status             | Test    | Architecture   | Notes                     |
| ------------- | ---------------------------- | ------------------ | ------- | -------------- | ------------------------- |
| NFR-SCALE-001 | Tenant growth                | Needs Verification | MISSING | Target         | Architecture target       |
| NFR-SCALE-002 | User growth                  | Needs Verification | MISSING | Target         | Requires load testing     |
| NFR-SCALE-003 | Booking growth               | Needs Verification | MISSING | Needs Refactor | Booking domain priority   |
| NFR-SCALE-004 | Horizontal scaling readiness | Needs Verification | MISSING | Target         | Infrastructure validation |

---

# 36. Reliability

| ID          | Requirement                  | Status             | Test           | Architecture   | Notes                       |
| ----------- | ---------------------------- | ------------------ | -------------- | -------------- | --------------------------- |
| NFR-REL-001 | Transaction consistency      | Implemented        | PASS / PARTIAL | Needs Refactor | Critical-flow audit         |
| NFR-REL-002 | External callback resilience | Verified           | PASS           | Needs Refactor | Webhook idempotency         |
| NFR-REL-003 | Error recovery               | Needs Verification | PARTIAL        | Needs Refactor | Requires failure-path audit |
| NFR-REL-004 | Production backup            | Needs Verification | N/A            | Deployment     | Operational requirement     |
| NFR-REL-005 | Recovery procedure           | Needs Verification | N/A            | Deployment     | Operational requirement     |

---

# 37. Availability

| ID            | Requirement                            | Status             | Test | Architecture | Notes                          |
| ------------- | -------------------------------------- | ------------------ | ---- | ------------ | ------------------------------ |
| NFR-AVAIL-001 | Target high availability / ~99% uptime | Needs Verification | N/A  | Deployment   | Requires production monitoring |

---

# 38. Usability

| ID         | Requirement             | Status      | Test             | Architecture   | Notes                    |
| ---------- | ----------------------- | ----------- | ---------------- | -------------- | ------------------------ |
| NFR-UX-001 | Responsive interface    | Implemented | MANUAL           | Needs Refactor | Visual QA required       |
| NFR-UX-002 | Consistent navigation   | Implemented | PASS / MANUAL    | Needs Refactor | Sidebar/navigation tests |
| NFR-UX-003 | Clear booking flow      | Implemented | PASS / MANUAL    | Needs Refactor | Customer frontend tests  |
| NFR-UX-004 | Concise booking process | Implemented | PASS / MANUAL    | Needs Refactor | Customer flow            |
| NFR-UX-005 | Clear state             | Implemented | MANUAL / PARTIAL | Needs Refactor | UX audit required        |

---

# 39. Compatibility

| ID           | Requirement            | Status             | Test    | Architecture   | Notes                   |
| ------------ | ---------------------- | ------------------ | ------- | -------------- | ----------------------- |
| NFR-COMP-001 | Modern browser support | Needs Verification | MISSING | Target         | Browser matrix needed   |
| NFR-COMP-002 | Responsive layout      | Implemented        | MANUAL  | Needs Refactor | Responsive verification |

---

# 40. Maintainability

| ID            | Requirement                    | Status         | Test | Architecture   | Notes                             |
| ------------- | ------------------------------ | -------------- | ---- | -------------- | --------------------------------- |
| NFR-MAINT-001 | Separation of responsibilities | Needs Refactor | N/A  | Needs Refactor | Major current concern             |
| NFR-MAINT-002 | Modular design                 | Needs Refactor | N/A  | Needs Refactor | Target architecture               |
| NFR-MAINT-003 | Reusable components            | Needs Refactor | N/A  | Needs Refactor | Large Blade files                 |
| NFR-MAINT-004 | Consistent naming              | Needs Refactor | N/A  | Needs Refactor | Legacy terminology                |
| NFR-MAINT-005 | Technical documentation        | In Progress    | N/A  | Target         | Current documentation rebuild     |
| NFR-MAINT-006 | Technical debt visibility      | In Progress    | N/A  | Target         | This tracker establishes baseline |

---

# 41. Observability

| ID          | Requirement            | Status             | Test           | Architecture       | Notes                  |
| ----------- | ---------------------- | ------------------ | -------------- | ------------------ | ---------------------- |
| NFR-OBS-001 | Authentication events  | Implemented        | PARTIAL        | Needs Verification | Logging audit required |
| NFR-OBS-002 | Booking events         | Implemented        | PASS / PARTIAL | Needs Refactor     | Booking logs exist     |
| NFR-OBS-003 | Payment events         | Implemented        | PASS / PARTIAL | Needs Refactor     | Payment logs/events    |
| NFR-OBS-004 | Application errors     | Implemented        | MANUAL         | Needs Verification | Laravel logging        |
| NFR-OBS-005 | Operational monitoring | Needs Verification | MISSING        | Deployment         | Requires environment   |

---

# 42. Extensibility

| ID          | Requirement                | Status         | Test           | Architecture   | Notes                       |
| ----------- | -------------------------- | -------------- | -------------- | -------------- | --------------------------- |
| NFR-EXT-001 | Payment provider boundary  | Needs Refactor | PASS / PARTIAL | Needs Refactor | Midtrans currently coupled  |
| NFR-EXT-002 | Notification extensibility | Needs Refactor | PARTIAL        | Needs Refactor | Future channels             |
| NFR-EXT-003 | Feature expansion          | Needs Refactor | N/A            | Target         | Main architecture objective |
| NFR-EXT-004 | Tenant-safe expansion      | Implemented    | PASS           | Target         | Existing tenant mechanism   |

---

# 43. Architectural Refactoring Tracker

This section tracks architectural problems independently from feature completion.

## A. Booking Architecture

Priority: Critical

```text
Status: Needs Refactor
```

Tasks:

```text
[ ] Audit BookingController
[ ] Extract booking creation action
[ ] Extract availability logic
[ ] Extract checkout orchestration
[ ] Extract cancellation logic
[ ] Extract reschedule logic
[ ] Centralize booking state transitions
[ ] Centralize booking pricing
[ ] Reduce controller dependency count
[ ] Add characterization tests
```

---

## B. Payment Architecture

Priority: High

```text
Status: Needs Refactor
```

Tasks:

```text
[ ] Separate booking payment from subscription payment
[ ] Isolate Midtrans-specific implementation
[ ] Centralize payment state transitions
[ ] Extract payment operations
[ ] Audit webhook handling
[ ] Audit transaction boundaries
```

---

## C. Schedule Architecture

Priority: High

```text
Status: Needs Refactor
```

Tasks:

```text
[ ] Extract availability calculation
[ ] Extract conflict checking
[ ] Extract bulk slot generation
[ ] Centralize blocked-date rules
[ ] Consolidate cache invalidation
```

---

## D. Owner Controller Architecture

Priority: High

```text
Status: Needs Refactor
```

Current candidates:

```text
OwnerDashboardController
OwnerPortalController
OwnerBookingController
OwnerSettingController
OwnerCustomerController
OwnerProgramController
```

Tasks:

```text
[ ] Identify responsibility boundaries
[ ] Extract Actions
[ ] Extract Form Requests
[ ] Remove duplicated tenant resolution
[ ] Remove repeated query logic
[ ] Reduce controller size
```

---

## E. Blade Architecture

Priority: High

Current large views include:

```text
owner/calendar.blade.php
owner/staff-resources.blade.php
owner/bookings.blade.php
customer/manage/show.blade.php
customer/booking/payment.blade.php
```

Tasks:

```text
[ ] Identify reusable components
[ ] Extract repeated modal structures
[ ] Extract repeated form fields
[ ] Separate page sections
[ ] Reduce embedded business logic
[ ] Reduce oversized Alpine state
```

---

## F. Terminology Refactor

Priority: Medium

Legacy:

```text
Program
Layanan
idlayanan
namalayanan
```

Canonical:

```text
Service
```

Tasks:

```text
[ ] Identify legacy terminology
[ ] Identify database compatibility impact
[ ] Identify route compatibility impact
[ ] Identify test impact
[ ] Perform incremental migration
```

---

## G. Route Architecture

Priority: Medium

Tasks:

```text
[ ] Audit route organization
[ ] Remove unnecessary duplicate route aliases
[ ] Standardize route naming
[ ] Extract route groups where useful
[ ] Keep business logic out of routes
```

---

## H. Shared Logic

Priority: Medium

Tasks:

```text
[ ] Audit Traits
[ ] Audit Support classes
[ ] Identify duplicated booking logic
[ ] Identify duplicated tenant resolution
[ ] Identify generic utility accumulation
```

---

# 44. Current High-Priority Refactor Queue

The current recommended refactor order is:

```text id="tqijv7"
R-001
Booking domain extraction

R-002
Payment boundary extraction

R-003
Schedule / availability extraction

R-004
Owner controller decomposition

R-005
Large Blade decomposition

R-006
Subscription entitlement centralization

R-007
Terminology normalization

R-008
Route cleanup

R-009
Shared utility / trait cleanup
```

This queue is a technical roadmap, not a product feature roadmap.

---

# 45. Verification Queue

The following areas require stronger verification before being considered fully verified.

```text
[ ] Production performance targets
[ ] Concurrent-user scalability
[ ] Production uptime
[ ] Backup/recovery
[ ] Browser compatibility matrix
[ ] Full authorization audit
[ ] Notification isolation
[ ] Custom domain behavior
[ ] Analytics metric correctness
[ ] Dashboard metric correctness
[ ] Asset security
[ ] Production secret management
```

---

# 46. Documentation Work Queue

Current documentation state:

```text
[✓] PRODUCT.md
[✓] REQUIREMENTS.md
[✓] ARCHITECTURE.md
[✓] DEVELOPMENT.md
[ ] TRACKER.md
[ ] AGENTS.md
[ ] docs/README.md
```

This file becomes complete once the current implementation audit has been synchronized with the final requirement mapping.

---

# 47. Feature vs Architecture Matrix

This matrix is important for the current BookQu state.

| Area                | Product State | Functional State | Architecture State   |
| ------------------- | ------------- | ---------------- | -------------------- |
| Authentication      | Core          | Implemented      | Legacy               |
| Tenant              | Core          | Implemented      | Target / Needs Audit |
| Service             | Core          | Done             | Needs Refactor       |
| Schedule            | Core          | Done             | Needs Refactor       |
| Booking             | Core          | Done             | Needs Major Refactor |
| Customer            | Core          | Done             | Needs Refactor       |
| Payment             | Core          | Done             | Needs Refactor       |
| Calendar            | Core          | Done             | Needs Refactor       |
| Dashboard           | Core          | Done             | Needs Refactor       |
| Categories          | Supporting    | Implemented      | Needs Refactor       |
| Staff/Resources     | Supporting    | Implemented      | Needs Refactor       |
| Additional Items    | Supporting    | Implemented      | Needs Refactor       |
| Vouchers            | Supporting    | Implemented      | Needs Refactor       |
| Reviews             | Supporting    | Implemented      | Needs Refactor       |
| Analytics           | Supporting    | Implemented      | Needs Refactor       |
| Reports             | Supporting    | Implemented      | Needs Refactor       |
| Assets              | Supporting    | Implemented      | Needs Refactor       |
| Appearance          | Supporting    | Implemented      | Needs Refactor       |
| Notifications       | Supporting    | Implemented      | Needs Refactor       |
| Subscription        | Platform      | Implemented      | Needs Refactor       |
| Admin               | Platform      | Implemented      | Needs Verification   |
| Future Integrations | Future        | Not Baseline     | Not Started          |
| AI Insights         | Future        | Not Baseline     | Not Started          |
| Advanced CRM        | Future        | Not Baseline     | Not Started          |
| Multi-location      | Future        | Not Baseline     | Not Started          |

---

# 48. Current Development Phase

BookQu should currently be considered in:

```text
PHASE: Stabilization + Architecture Consolidation
```

The project is not currently in:

```text
Feature Expansion Phase
```

The immediate goal is to:

```text
1. Stabilize product definition
2. Stabilize requirements
3. Stabilize architecture direction
4. Map current implementation
5. Protect existing behavior
6. Refactor critical architecture
7. Strengthen testing
```

Only after these are sufficiently stable should large new product capabilities be added.

---

# 49. Rule for New Work

Before adding any new feature:

```text
[ ] Does the capability exist in PRODUCT.md?
[ ] Does a requirement exist in REQUIREMENTS.md?
[ ] Is the requirement ID known?
[ ] Does the current architecture have an appropriate home?
[ ] Does an existing implementation already solve it?
[ ] Does the task require a new domain?
[ ] Are tests defined?
[ ] Is the tracker entry present?
```

If the answer to the requirement question is `No`, stop and classify the task as a product change.

---

# 50. Rule for "Done"

Do not mark a feature as `Done` only because:

* a page exists;
* a button works;
* the happy path works;
* a controller was written.

A feature should be considered `Done` when its required behavior has been implemented and sufficiently verified.

---

# 51. Rule for "Needs Refactor"

Mark a feature `Needs Refactor` when:

* behavior is considered correct;
* tests indicate expected behavior;
* but implementation violates significant target architecture principles.

This status is not a failure.

It is a deliberate representation of technical debt.

---

# 52. Rule for Blockers

A blocker must describe why work cannot proceed.

Bad:

```text
Blocked: not finished.
```

Good:

```text
Blocked:
Waiting for payment-provider credentials required to verify production callback behavior.
```

Every blocker should include:

```text
Reason
Impact
Required resolution
Last updated
```

---

# 53. Tracker Update Rules

Update the tracker when:

* a task starts;
* a task changes status;
* testing begins;
* testing fails;
* implementation is completed;
* an architecture issue is discovered;
* a blocker appears;
* a requirement changes.

Do not wait until the end of an entire sprint or milestone.

---

# 54. Change Log

Track major tracker-level changes here.

| Date       | Change                                                 | Reason                                                                  |
| ---------- | ------------------------------------------------------ | ----------------------------------------------------------------------- |
| 2026-09-26 | Replaced legacy tracker with requirement-based tracker | Establish single implementation source                                  |
| 2026-09-26 | Added functional vs architecture state                 | Current code is functionally developed but architecturally inconsistent |

---

# 55. Final Development Tracking Principle

The BookQu tracker follows:

```text id="ypj8hi"
Requirement
    ↓
Implementation
    ↓
Verification
    ↓
Architecture
    ↓
Status
```

The tracker must answer five questions:

```text
What should exist?
What exists?
Does it work?
Is it verified?
Does it follow the target architecture?
```

A healthy BookQu development process must be able to answer all five.

---

# 56. Final Status Principle

BookQu is currently treated as:

> **Functionally mature in several core and supporting areas, but in need of architectural consolidation and systematic verification.**

Therefore the immediate roadmap is:

```text
Current Implementation
        ↓
Requirement Traceability
        ↓
Architecture Refactor
        ↓
Verification
        ↓
Stable Development Baseline
        ↓
Future Feature Expansion
```

New functionality should not be allowed to recreate the architectural drift that this documentation effort is intended to eliminate.
