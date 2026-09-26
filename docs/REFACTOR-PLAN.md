# BookQu Refactor & Project Alignment Plan

**Branch:** `Refactor`
**Baseline:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
**Reference Implementation:** `mergeV2`
**Phase:** Stabilization + Architecture Consolidation

---

# 1. Refactoring Objective

Refactor BookQu from the current controller-centric implementation into the documented target architecture without changing intended product behavior.

The primary objective is:

```text
Current Implementation
        ↓
Behavior Protection
        ↓
Responsibility Extraction
        ↓
Application Actions
        ↓
Domain Boundaries
        ↓
Thin Controllers
        ↓
Stable Architecture
```

The refactor must preserve:

```text
Tenant isolation
Booking behavior
Payment behavior
Schedule availability
Multi-slot booking
Cancellation
Rescheduling
Subscription behavior
Existing public/customer flows
```

This is an architectural refactor, not a product redesign.

---

# 2. Current Baseline

The current repository has:

```text
24 Controllers
No app/Actions
No app/Domain
No app/Infrastructure
No app/Http/Requests
```

Major implementation hotspots:

```text
BookingController
≈ 1,506 lines

BookingController::processCheckout()
≈ 438 lines

BookingManageController
≈ 692 lines

OwnerPortalController
≈ 514 lines

OwnerBookingController
≈ 503 lines

MidtransPaymentService
≈ 491 lines
```

Large views:

```text
owner/calendar.blade.php
≈ 96 KB

customer/manage/show.blade.php
≈ 61 KB

owner/staff-resources.blade.php
≈ 60 KB

owner/bookings.blade.php
≈ 51 KB
```

Large shared components also exist:

```text
components/owner/sidebar.blade.php
≈ 35 KB

components/owner/topbar.blade.php
≈ 31 KB
```

Routes:

```text
routes/web.php
≈ 415 lines
```

---

# 3. Refactoring Rules

The following rules apply to the entire refactor.

```text
1. Do not redesign product behavior.
2. Do not rename the database schema during early phases.
3. Do not rewrite the entire repository.
4. Do not create abstractions without a responsibility.
5. Preserve tenant isolation.
6. Preserve existing payment invariants.
7. Preserve booking concurrency protection.
8. Preserve existing tests.
9. Add characterization tests before risky extraction.
10. Run verification after every major extraction.
11. Update TRACKER.md after each phase.
12. Update ARCHITECTURE.md when the actual architecture changes.
13. Use canonical terminology for new code.
14. Keep legacy compatibility where required.
```

---

# 4. Phase 0 — Documentation Alignment

Priority: Critical

Before code refactoring starts, make the documentation references internally consistent.

Current repository:

```text
AGENT.md

docs/1-README.md
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
docs/6-TRACKER.md
```

Target:

```text
AGENTS.md

docs/README.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
```

Tasks:

```text
[ ] Rename AGENT.md → AGENTS.md
[ ] Rename 1-README.md → README.md
[ ] Rename 2-PRODUCT.md → PRODUCT.md
[ ] Rename 3-REQUIREMENT.md → REQUIREMENTS.md
[ ] Rename 4-ARCHITECTURE.md → ARCHITECTURE.md
[ ] Rename 5-DEVELOPMENT.md → DEVELOPMENT.md
[ ] Rename 6-TRACKER.md → TRACKER.md
[ ] Verify every documentation reference
[ ] Ensure AGENTS.md references existing files
[ ] Ensure DEVELOPMENT.md references existing files
[ ] Ensure TRACKER.md references existing files
```

Do not create additional specification documents at this stage.

---

# 5. Phase 1 — Baseline & Characterization

Priority: Critical

Before extracting critical business logic, establish a behavior baseline.

Critical domains:

```text
Tenant
Service
Schedule
Booking
Payment
Subscription
```

Critical flows:

```text
Customer booking
Walk-in booking
Multi-slot booking
Payment
Webhook
Cancellation
Reschedule
Availability
Tenant isolation
Subscription entitlement
```

Tasks:

```text
[ ] Run complete test suite
[ ] Record current test result
[ ] Identify failed/unstable tests
[ ] Identify tests covering booking creation
[ ] Identify tests covering availability
[ ] Identify tests covering concurrency
[ ] Identify tests covering payment
[ ] Identify tests covering webhook
[ ] Identify tests covering cancellation
[ ] Identify tests covering reschedule
[ ] Identify tests covering tenant isolation
[ ] Add characterization tests where coverage is weak
```

Important rule:

```text
No major booking refactor before the critical behavior baseline is understood.
```

---

# 6. Phase 2 — Booking Refactor

Priority: Critical

This is the first major code refactor.

Current problem:

```text
BookingController
BookingManageController
OwnerBookingController
MidtransPaymentService
```

all contain pieces of the booking domain.

Target:

```text
Booking
├── Actions
│   ├── CreateBooking
│   ├── CreateWalkInBooking
│   ├── CancelBooking
│   ├── RescheduleBooking
│   └── UpdateBookingStatus
│
├── Domain
│   ├── BookingRules
│   ├── BookingState
│   └── Availability coordination
│
└── Requests
    ├── CreateBookingRequest
    ├── RescheduleBookingRequest
    └── UpdateBookingRequest
```

## 6.1 First Extraction — Create Booking

Do not refactor the entire controller immediately.

Start with:

```text
BookingController::processCheckout()
```

Extract its transactional booking workflow into:

```text
app/Actions/Booking/CreateBooking.php
```

The Action should initially own the complete existing booking creation operation.

Do not immediately split every internal rule.

Goal:

```text
Controller
    ↓
CreateBooking
    ↓
Existing booking behavior
```

This creates the first real application boundary.

---

## 6.2 Extract Customer Booking Request Validation

Create:

```text
app/Http/Requests/Booking/CreateBookingRequest.php
```

Move HTTP/input validation out of `processCheckout()`.

Do not move domain rules that depend on database state into the request.

Request:

```text
Input validation
Authorization where appropriate
```

Action/domain:

```text
Business validation
Availability
Booking rules
Pricing
```

---

## 6.3 Extract Walk-In Booking

Current:

```text
OwnerBookingController::walkinStore()
```

Target:

```text
app/Actions/Booking/CreateWalkInBooking.php
```

The customer and owner booking flows should eventually reuse the same domain rules.

Conceptually:

```text
Customer
    ↓
CreateBooking

Owner
    ↓
CreateWalkInBooking
        ↓
Shared Booking Rules
```

Do not duplicate booking validation.

---

## 6.4 Extract Booking Cancellation

Current cancellation logic exists in:

```text
BookingManageController
OwnerBookingController
```

Create:

```text
app/Actions/Booking/CancelBooking.php
```

Responsibilities:

```text
Validate cancellation
Update booking state
Create refund record when required
Create booking log
Invalidate availability
Trigger required side effects
```

Customer and owner cancellation should eventually use the same application operation.

---

## 6.5 Extract Rescheduling

Current:

```text
BookingManageController::reschedule()
OwnerBookingController::reschedule()
```

Create:

```text
app/Actions/Booking/RescheduleBooking.php
```

Centralize:

```text
old slot validation
new slot validation
tenant validation
availability
concurrency
booking update
history
cache invalidation
notifications
```

---

## 6.6 Extract Booking Status Transition

Current state changes are distributed.

Create a clear application/domain boundary:

```text
app/Domain/Booking/BookingState.php
```

or another focused state-transition mechanism.

Valid states must remain aligned with the current product:

```text
pending
paid
cancelled
completed
```

Do not change state semantics during this phase.

---

## 6.7 Booking Refactor Completion Criteria

Booking phase is complete when:

```text
[ ] Booking creation is outside controller
[ ] Walk-in booking uses application action
[ ] Cancellation uses shared action
[ ] Rescheduling uses shared action
[ ] State transitions have one identifiable owner
[ ] Critical booking rules are not duplicated
[ ] Controllers are significantly smaller
[ ] Existing booking tests pass
[ ] Security tests pass
[ ] Concurrency tests pass
```

---

# 7. Phase 3 — Payment Boundary

Priority: High

Current payment logic is spread across:

```text
BookingController
OwnerCheckoutController
OwnerSettingController
MidtransPaymentService
MidtransWebhookController
```

The problem is not only file size.

The primary problem is:

```text
BookQu business logic
        +
Midtrans-specific logic
```

are tightly coupled.

Target:

```text
BookQu Payment Operation
        ↓
Payment Gateway Boundary
        ↓
Midtrans Adapter
```

Example conceptual structure:

```text
app/
├── Actions/Payment/
│   ├── CreatePayment.php
│   ├── CheckPaymentStatus.php
│   └── ProcessPaymentCallback.php
│
├── Domain/Payment/
│   └── PaymentState.php
│
├── Services/Payment/
│   └── PaymentGateway.php
│
└── Infrastructure/Payments/
    └── MidtransPaymentGateway.php
```

Do not create every class immediately.

Extract only when the responsibility is real.

---

## 7.1 Booking Payment vs Subscription Payment

Separate:

```text
Booking Payment
```

from:

```text
Subscription Payment
```

They may use the same provider but are different business operations.

Target:

```text
Booking Payment
    ↓
Payment Gateway

Subscription Payment
    ↓
Payment Gateway
```

not:

```text
Huge Midtrans Service
    ↓
Everything
```

---

## 7.2 Webhook

Webhook flow should become:

```text
Midtrans Webhook
        ↓
Verify payload
        ↓
Resolve Payment
        ↓
Payment Application Operation
        ↓
Payment State Transition
        ↓
Booking / Subscription Synchronization
```

Idempotency must remain intact.

---

# 8. Phase 4 — Schedule & Availability

Priority: High

Current availability logic is spread across:

```text
BookingController
OwnerScheduleController
OwnerBookingController
BookingManageController
Schedule model
ClearsBookingCache
```

Target:

```text
Schedule
├── CreateSchedules
├── GenerateBulkSchedules
├── UpdateAvailability
└── Availability rules
```

Important operations:

```text
[ ] available schedule query
[ ] conflict detection
[ ] blocked date validation
[ ] active booking detection
[ ] past schedule protection
[ ] multi-slot compatibility
[ ] cache invalidation
```

Centralize business rules before optimizing the implementation.

---

# 9. Phase 5 — Cache Boundary

`ClearsBookingCache` currently contains multiple cache key and invalidation mechanisms.

It should not become a new business-logic dumping ground.

After availability is consolidated, evaluate replacing the trait with a focused component such as:

```text
ScheduleAvailabilityCache
```

or another clearly bounded cache service.

Do not perform this extraction before understanding all current cache consumers.

---

# 10. Phase 6 — Owner Controller Refactor

Priority: High

Only after Actions and major domain rules exist should owner controllers be decomposed.

Current candidates:

```text
OwnerBookingController
OwnerScheduleController
OwnerProgramController
OwnerCustomerController
OwnerDashboardController
OwnerPortalController
OwnerSettingController
OwnerCheckoutController
```

---

## 10.1 Owner Booking

Target:

```text
OwnerBookingController
        ↓
Actions
```

The controller becomes responsible mainly for:

```text
request
authorization
action invocation
response
```

---

## 10.2 Owner Portal

`OwnerPortalController` currently contains:

```text
calendar
schedule report
schedule report export
appearance
payment settings
assets
balance
integrations
```

This should be split into bounded controllers.

Potential structure:

```text
OwnerCalendarController
OwnerScheduleReportController
OwnerAppearanceController
OwnerPaymentSettingsController
OwnerBalanceController
OwnerIntegrationController
```

This is not the first controller to refactor because its logic depends on several already-existing modules.

---

## 10.3 Owner Dashboard

Current dashboard code contains substantial query and metric construction.

Target:

```text
OwnerDashboardController
        ↓
Dashboard query/application service
        ↓
Prepared metrics
        ↓
Blade
```

Do not blindly create a generic `DashboardService`.

First identify each metric's source and business definition.

---

# 11. Phase 7 — Form Request Consolidation

Priority: Medium-High

Current controllers directly use `Request` and inline validation.

Gradually introduce focused Requests:

```text
Booking
Schedule
Service
Customer
Payment
Subscription
```

Example:

```text
CreateBookingRequest
RescheduleBookingRequest
UpdateBookingStatusRequest
CreateScheduleRequest
CreateServiceRequest
```

The purpose is:

```text
HTTP validation
+
clear input contract
```

not moving business rules into validation classes.

---

# 12. Phase 8 — Blade Refactor

Priority: High, but after domain extraction

Target order:

```text
1. owner/calendar.blade.php
2. customer/manage/show.blade.php
3. owner/staff-resources.blade.php
4. owner/bookings.blade.php
5. owner/customers.blade.php
6. owner/schedule.blade.php
7. shared sidebar/topbar
```

Decomposition pattern:

```text
Page
├── Page Header
├── Filters
├── Main Content
├── Modal
├── Table/List
└── Supporting Components
```

Move repeated UI into:

```text
resources/views/components/
```

Move large page JavaScript into dedicated JS modules where appropriate.

Do not redesign the visual interface during this architectural refactor.

---

# 13. Phase 9 — Subscription & Entitlement

Priority: Medium-High

Current feature gating is partly handled directly by middleware.

The subscription model should evolve toward:

```text
Plan
   ↓
Subscription
   ↓
Entitlement
   ↓
Feature access
```

The middleware should ask an entitlement capability rather than knowing the plan-level implementation itself.

Conceptually:

```text
CheckSubscription
        ↓
SubscriptionEntitlement
        ↓
Can access feature?
```

Centralize:

```text
trial
plan level
feature access
usage limits
expiration
subscription state
```

---

# 14. Phase 10 — Terminology Alignment

Priority: Medium

Canonical product term:

```text
Service
```

Current legacy terminology:

```text
Program
Layanan
idlayanan
namalayanan
```

The database should NOT be renamed during the early architecture refactor.

Instead:

```text
New PHP code
    ↓
Canonical terminology

Legacy database
    ↓
Compatibility layer / existing mapping
```

Gradually migrate:

```text
OwnerProgramController
        ↓
OwnerServiceController
```

while preserving existing routes where necessary.

Do not break:

```text
/programs
/services
```

during the initial migration.

Later evaluate which route should remain canonical.

---

# 15. Phase 11 — Route Organization

Priority: Medium

`routes/web.php` currently contains approximately 415 lines.

First clean:

```text
duplicate route aliases
legacy route names
inconsistent parameters
route organization
```

Examples:

```text
/programs
/services
```

currently map to the same implementation.

Do not remove compatibility routes until all internal references and external assumptions have been checked.

The main rule:

```text
Route
    ↓
Middleware
    ↓
Controller
```

No business workflow inside routes.

---

# 16. Phase 12 — Shared Logic Cleanup

Priority: Medium

After the core refactor, audit:

```text
Traits
Support classes
Helpers
Repeated controller queries
Repeated tenant resolution
Repeated cache invalidation
```

Especially:

```text
ClearsBookingCache
ResolvesOwnerTenant
CustomerBookingRoutes
```

A shared class should remain only when the behavior is genuinely shared and stable.

---

# 17. Branch Strategy

Keep `Refactor` as the integration branch.

Use short-lived branches:

```text
Refactor
│
├── refactor/foundation
├── refactor/booking
├── refactor/payment
├── refactor/schedule
├── refactor/owner
├── refactor/views
├── refactor/subscription
└── refactor/cleanup
```

Each branch should have one architectural objective.

Avoid:

```text
refactor/everything
```

---

# 18. Commit Strategy

Use small coherent commits.

Examples:

```text
docs: align documentation filenames

test: add booking characterization coverage

refactor(booking): extract create booking action

refactor(booking): centralize cancellation workflow

refactor(booking): centralize reschedule workflow

refactor(payment): introduce payment gateway boundary

refactor(schedule): extract availability rules

refactor(owner): decompose owner portal controller

refactor(view): decompose owner calendar view
```

Each commit should ideally remain understandable on its own.

---

# 19. Definition of Done for Every Refactor Step

A refactor step is complete only when:

```text
[ ] Intended behavior is preserved
[ ] Requirement IDs remain satisfied
[ ] Tenant isolation remains intact
[ ] Authorization remains intact
[ ] Existing critical tests pass
[ ] New tests exist where needed
[ ] Legacy duplication is reduced
[ ] Target responsibility is clearer
[ ] Controller/view complexity decreases where intended
[ ] No unrelated product behavior was introduced
[ ] TRACKER.md updated
[ ] ARCHITECTURE.md updated when required
```

---

# 20. Things We Must Not Do Yet

Do not perform these during the first refactor cycle:

```text
[ ] Rename all database columns
[ ] Rewrite all controllers simultaneously
[ ] Rewrite all Blade files simultaneously
[ ] Introduce a REST API
[ ] Replace Laravel/Eloquent architecture entirely
[ ] Replace Midtrans before establishing a boundary
[ ] Redesign the product UI
[ ] Add major new product features
[ ] Rewrite all models into domain entities
[ ] Create repositories for every model
[ ] Create interfaces for every class
```

These changes create unnecessary risk before the core boundaries are stable.

---

# 21. Target End State

The desired architecture is approximately:

```text
HTTP
 │
 ├── Controllers
 └── Requests
       │
       ▼
Application
 │
 └── Actions
       │
       ▼
Domain
 │
 ├── Booking
 ├── Schedule
 ├── Service
 ├── Payment
 ├── Subscription
 ├── Tenant
 └── Customer
       │
       ▼
Persistence / Infrastructure
 │
 ├── Eloquent Models
 ├── Midtrans
 ├── Notifications
 └── Storage
```

The UI becomes:

```text
Blade
 └── Presentation only

Alpine / JS
 └── Interaction only
```

---

# 22. Refactoring Order

The complete order is:

```text
PHASE 0
Documentation Alignment
        ↓
PHASE 1
Behavior Baseline / Characterization
        ↓
PHASE 2
Booking
        ↓
PHASE 3
Payment Boundary
        ↓
PHASE 4
Schedule / Availability
        ↓
PHASE 5
Cache Boundary
        ↓
PHASE 6
Owner Controllers
        ↓
PHASE 7
Form Requests
        ↓
PHASE 8
Blade Decomposition
        ↓
PHASE 9
Subscription / Entitlement
        ↓
PHASE 10
Terminology
        ↓
PHASE 11
Route Cleanup
        ↓
PHASE 12
Shared Logic Cleanup
        ↓
FINAL
Regression + Architecture Audit
```

---

# 23. First Concrete Work Item

The first actual code-related milestone should therefore be:

```text
BOOKQU-REFactor-001
Documentation + Baseline Alignment
```

Deliverables:

```text
1. Normalize documentation filenames.
2. Verify all internal documentation links.
3. Establish Refactor as integration branch.
4. Establish current test baseline.
5. Verify critical booking tests.
6. Update TRACKER.md with the real baseline.
```

After this baseline is stable, the first real architectural extraction is:

```text
CreateBooking Action
```

from:

```text
BookingController::processCheckout()
```

That is the safest first major extraction because it removes the single largest concentration of booking business logic while allowing the existing UI and database structure to remain unchanged.

# 24. Final Principle

The refactor must move BookQu from:

```text
Many Controllers
        ↓
Many Business Rules
        ↓
Duplicated Domain Logic
        ↓
Fragile Dependencies
```

toward:

```text
Clear Domain
        ↓
Explicit Actions
        ↓
Centralized Business Rules
        ↓
Thin Controllers
        ↓
Testable Architecture
```

The goal is not to make every file small.

The goal is to make every responsibility have a clear owner.

---

# 25. Post-Refactor Stabilization: RF-09 — Booking Flow Stability & Production Hardening

After completing RF-00 to RF-08, RF-09 resolves residual production hardening tasks:
- **P0-01 Timezone**: Set `Asia/Jakarta` uniformly across Laravel and Carbon.
- **P0-02 Payment Expiry**: Harmonize DB `payments.status` (`gagal`) and trigger cancellation + slot release via `ExpirePayment`.
- **P0-03 Production Scheduler**: Document crontab `* * * * * cd /path/to/bookqu && php artisan schedule:run >> /dev/null 2>&1` in development documentation.
- **P0-04 Active Booking Definition**: Centralized semantic definition via `BookingState::occupiesSlot()`.
- **P0-05 Cache & Pending Expiry**: Shortened cache TTL and verified authoritative DB transactions with row locking.
- **P1-01 Scoped Token Isolation**: Differentiate `cancellation_token` (cancel only) and `reschedule_token` (reschedule only).
- **P1-02 Refunded State**: Removed non-existent `STATUS_REFUNDED` from `BookingState` (refunds tracked in `refunds` table).
- **P1-03 Owner Cancellation Policy**: Owner cancellations bypass automatic customer refund records and owner self-notifications.
- **P1-04 Checkout Validation**: Authoritative date-slot matching during checkout.
- **P1-05 JS Escaping**: Replaced manual interpolation with `Js::from(...)`.
- **P1-06 Indonesian Locale**: Configured `id` locale and Carbon translation.
- **P2-01 Duplicate Booking Rules**: Delegated model methods to `BookingRules`.
- **P2-02 Availability Centralization**: Centralized availability query and status evaluation.
- **P2-03 Cache Key Audit**: Verified tenant/service isolated cache keys.
- **P2-04 CI Regression**: Added `.github/workflows/tests.yml`.

