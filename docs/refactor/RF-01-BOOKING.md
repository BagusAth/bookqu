# RF-01 — Booking Refactor

> **Status:** Completed
> **Priority:** Critical
> **Phase:** Booking Stabilization
> **Integration Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Reference Implementation:** `mergeV2`
> **Purpose:** Refactor the Booking domain incrementally without changing intended product behavior.

---

# 1. Objective

Refactor the current BookQu booking implementation from a controller-heavy structure into explicit application operations and clearer domain boundaries.

The target is:

```text
HTTP
  ↓
Booking Controller
  ↓
Booking Request
  ↓
Booking Action
  ↓
Booking Domain Rules
  ↓
Models / Persistence
```

The refactor must preserve current intended behavior.

This work order is an architectural refactor, not a booking feature redesign.

---

# 2. Branch and Documentation Baseline

This work order is written against:

```text
Branch:
Refactor

Baseline:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

The repository currently contains the documentation under these paths:

```text
AGENT.md

docs/1-README.md
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
docs/6-TRACKER.md
```

`RF-00-FOUNDATION.md` is responsible for normalizing those names to:

```text
AGENTS.md

docs/README.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
```

Therefore:

> If RF-00 has already been completed, use the canonical names.

> If RF-00 has not been completed, use the existing numbered filenames while preserving the same document authority.

Do not create duplicate copies of the documentation.

---

# 3. Mandatory Reading

Before modifying booking code, read:

```text
AGENTS.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
```

If RF-00 has not yet normalized filenames, map them as:

```text
AGENT.md                    → AGENTS.md
docs/2-PRODUCT.md           → docs/PRODUCT.md
docs/3-REQUIREMENT.md       → docs/REQUIREMENTS.md
docs/4-ARCHITECTURE.md      → docs/ARCHITECTURE.md
docs/5-DEVELOPMENT.md       → docs/DEVELOPMENT.md
docs/6-TRACKER.md           → docs/TRACKER.md
```

The product and requirement documents are authoritative for behavior.

The architecture document is authoritative for target technical structure.

This work order is authoritative only for the scope and sequence of this booking refactor.

---

# 4. Relevant Requirements

The primary requirements for this refactor are:

```text
FR-BOOKING-001
FR-BOOKING-002
FR-BOOKING-003
FR-BOOKING-004
FR-BOOKING-005
FR-BOOKING-006
FR-BOOKING-007
FR-BOOKING-008
FR-BOOKING-009
FR-BOOKING-010
FR-BOOKING-011
FR-BOOKING-012
FR-BOOKING-013
FR-BOOKING-014
FR-BOOKING-015
FR-BOOKING-016
FR-BOOKING-017
FR-BOOKING-018
FR-BOOKING-019
```

Multi-slot behavior:

```text
FR-MULTIBOOK-001
FR-MULTIBOOK-002
FR-MULTIBOOK-003
FR-MULTIBOOK-004
FR-MULTIBOOK-005
FR-MULTIBOOK-006
```

Related requirements may also be involved:

```text
FR-SERVICE-007
FR-SCHEDULE-007
FR-SCHEDULE-009
FR-SCHEDULE-010
FR-PAYMENT-001
FR-PAYMENT-003
FR-PAYMENT-008
FR-PAYMENT-009
FR-PAYMENT-010
```

Do not change the meaning of these requirements during this refactor.

---

# 5. Current Implementation

The current booking implementation is distributed across several components.

Primary controllers:

```text
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Customer/BookingManageController.php
app/Http/Controllers/Owner/OwnerBookingController.php
```

Payment integration currently involved in booking:

```text
app/Services/MidtransPaymentService.php
app/Http/Controllers/Webhook/MidtransWebhookController.php
```

Booking model:

```text
app/Models/Booking.php
```

Schedule model:

```text
app/Models/Schedule.php
```

Service model:

```text
app/Models/Service.php
```

Tenant support:

```text
app/Support/TenantContext.php
app/Models/Scopes/TenantScope.php
app/Traits/BelongsToTenant.php
app/Traits/ResolvesOwnerTenant.php
```

Cache support:

```text
app/Traits/ClearsBookingCache.php
```

---

# 6. Current Booking Hotspots

The largest current hotspot is:

```text
BookingController.php
≈ 1,506 lines
```

The largest workflow inside it is:

```text
processCheckout()
≈ 438 lines
```

Other important booking workflows:

```text
BookingManageController.php
≈ 692 lines

OwnerBookingController.php
≈ 503 lines
```

The problem is not simply file size.

The current implementation combines multiple responsibilities:

```text
HTTP handling
validation
tenant resolution
service resolution
schedule resolution
availability
pricing
voucher handling
multi-slot handling
booking creation
payment creation
payment integration
cache invalidation
notifications
invoice preparation
cancellation
rescheduling
state changes
```

---

# 7. Current Architectural Problem

The same conceptual booking rules are currently reachable through different entry points.

For example:

```text
Customer Booking
        ↓
BookingController

Walk-In
        ↓
OwnerBookingController

Customer Cancellation
        ↓
BookingManageController

Owner Reschedule
        ↓
OwnerBookingController

Customer Reschedule
        ↓
BookingManageController

Payment Synchronization
        ↓
MidtransPaymentService
```

This creates a risk of:

```text
duplicated rules
inconsistent behavior
difficulty testing
large controllers
high coupling
```

The refactor must establish clear ownership of booking operations.

---

# 8. Target Architecture

The target is:

```text
app/
├── Actions/
│   └── Booking/
│
├── Domain/
│   └── Booking/
│
└── Http/
    ├── Controllers/
    │   ├── Customer/
    │   └── Owner/
    │
    └── Requests/
        └── Booking/
```

The initial target structure may become:

```text
app/Actions/Booking/
├── CreateBooking.php
├── CreateWalkInBooking.php
├── CancelBooking.php
├── RescheduleBooking.php
└── UpdateBookingStatus.php

app/Domain/Booking/
├── BookingRules.php
└── BookingState.php

app/Http/Requests/Booking/
├── CreateBookingRequest.php
├── RescheduleBookingRequest.php
└── UpdateBookingStatusRequest.php
```

These are target responsibilities, not mandatory class names.

Do not create a class unless it has a real responsibility.

---

# 9. Core Architectural Principle

Customer and owner entry points may be different.

The business operation must not be duplicated merely because the entry point is different.

Preferred structure:

```text
Customer HTTP
      ↓
CreateBooking
```

and:

```text
Owner HTTP
      ↓
CreateWalkInBooking
      ↓
Shared Booking Rules
```

Likewise:

```text
Customer Cancellation
        ↓
CancelBooking
```

and:

```text
Owner Cancellation
        ↓
CancelBooking
```

when the underlying business operation is equivalent.

Authorization may differ at the HTTP boundary.

Business rules should not be duplicated unnecessarily.

---

# 10. Refactor Sequence

The booking refactor must happen in this order.

```text
Step 1
Behavior baseline

↓
Step 2
Characterization tests

↓
Step 3
CreateBooking extraction

↓
Step 4
CreateWalkInBooking extraction

↓
Step 5
Cancellation extraction

↓
Step 6
Reschedule extraction

↓
Step 7
Booking state boundary

↓
Step 8
Multi-slot consolidation

↓
Step 9
Controller cleanup

↓
Step 10
Booking architecture verification
```

Do not skip directly to Step 8.

---

# 11. Step 1 — Behavior Baseline

Before changing booking logic:

```text
[ ] Run the existing full test suite
[ ] Run booking-related tests
[ ] Run tenant isolation tests
[ ] Run concurrency/double-booking tests
[ ] Run payment-related booking tests
[ ] Record current result
```

Important tests currently present include:

```text
tests/Feature/CoreFlowIntegrationTest.php

tests/Feature/Customer/BookingFlowTest.php
tests/Feature/Customer/BookingManageTest.php
tests/Feature/Customer/PaymentGroupManagementTest.php
tests/Feature/Customer/ProductionLogicSpecificationTest.php

tests/Feature/Owner/BookingManagementTest.php
tests/Feature/Owner/CheckoutMidtransTest.php
tests/Feature/Owner/FiveModulesFullIntegrationTest.php

tests/Feature/P0SecurityTest.php
```

Do not modify failing tests just to make the refactor appear successful.

Determine whether a failure is:

```text
existing failure
regression
obsolete test
environment problem
```

---

# 12. Step 2 — Characterization Tests

Before extracting high-risk logic, ensure that the current intended behavior is protected.

Characterization coverage should protect at least:

```text
normal booking
unavailable schedule
cross-tenant booking
duplicate booking
double booking
multi-slot booking
voucher behavior
free booking
payment group creation
booking token
```

Where current tests already provide sufficient protection:

> Do not create redundant tests just for the sake of increasing test count.

The goal is behavior protection, not test quantity.

---

# 13. Step 3 — Extract CreateBooking

Primary source:

```text
BookingController::processCheckout()
```

Create:

```text
app/Actions/Booking/CreateBooking.php
```

and, if appropriate:

```text
app/Http/Requests/Booking/CreateBookingRequest.php
```

The first extraction should move the existing workflow without changing its meaning.

Target:

```text
POST /booking/checkout
        ↓
CreateBookingRequest
        ↓
BookingController
        ↓
CreateBooking
```

The controller should eventually become responsible for:

```text
receive request
call validation
invoke action
build response
```

The Action may initially still call existing models and existing services.

Do not prematurely create a complete domain framework.

---

# 14. CreateBooking Responsibilities

The extracted operation may own:

```text
service validation
schedule validation
tenant consistency validation
customer data handling
pricing coordination
voucher application
multi-slot coordination
booking record creation
payment record creation where currently required
transaction boundaries
booking token assignment
required booking logs
required cache invalidation
required post-booking side effects
```

However, responsibilities should be extracted only when they have a clear boundary.

Do not simply copy 438 lines into a new file and call that architectural completion.

The extraction is successful only when the workflow itself has a clearer ownership boundary.

---

# 15. Request Validation Boundary

`CreateBookingRequest` should handle HTTP/input concerns such as:

```text
required fields
format
basic type validation
basic input constraints
```

It must not become the home of complex database-dependent booking rules.

For example:

```text
"schedule exists and belongs to this tenant"
```

is a business/data ownership rule.

Do not blindly move such logic into the Request just because it is technically validation.

---

# 16. CreateBooking and Payment

Do not perform the payment architecture refactor as part of this task.

During RF-01:

```text
Preserve current payment behavior.
```

The Action may continue to call existing payment functionality where required.

The separation of:

```text
Booking Payment
vs
Payment Provider
```

belongs primarily to:

```text
RF-02-PAYMENT.md
```

Do not redesign Midtrans integration during RF-01.

---

# 17. Step 4 — Extract CreateWalkInBooking

Primary source:

```text
OwnerBookingController::walkinStore()
```

Create:

```text
app/Actions/Booking/CreateWalkInBooking.php
```

Target:

```text
Owner HTTP request
      ↓
CreateWalkInBooking
      ↓
Booking rules
```

Walk-in booking must remain inside the same booking domain.

Do not create a second booking architecture for walk-ins.

---

# 18. Walk-In Rules

Preserve current requirements relating to:

```text
tenant ownership
schedule ownership
availability
booking creation
customer information
payment creation
status
booking logs
cache invalidation
```

Do not change walk-in product behavior during this refactor.

---

# 19. Step 5 — Extract Cancellation

Current cancellation logic exists primarily in:

```text
BookingManageController::cancel()
OwnerBookingController::updateStatus()
```

Create:

```text
app/Actions/Booking/CancelBooking.php
```

The Action should own the common cancellation operation.

Potential responsibilities:

```text
validate booking cancellability
update booking state
create refund where required
record booking log
invalidate availability
trigger required side effects
```

Authorization remains outside or at the application boundary according to the architecture.

Do not let an owner bypass customer cancellation business rules merely because the owner has greater permissions.

Different authorization does not automatically mean different domain behavior.

---

# 20. Step 6 — Extract Reschedule

Current implementations:

```text
BookingManageController::reschedule()
OwnerBookingController::reschedule()
```

Create:

```text
app/Actions/Booking/RescheduleBooking.php
```

This operation must preserve:

```text
old booking validation
new schedule validation
tenant validation
service consistency
availability checking
concurrency protection
booking update
history/logging
cache invalidation
required notifications
```

The existing concurrency protection must not be weakened.

Where the current implementation uses:

```text
lockForUpdate()
```

do not remove it merely to simplify extraction.

---

# 21. Step 7 — Booking State Boundary

Current booking state behavior is distributed across controllers/services.

Introduce one explicit place for state-transition rules.

Possible implementation:

```text
app/Domain/Booking/BookingState.php
```

or another focused mechanism consistent with `ARCHITECTURE.md`.

The important requirement is:

```text
One identifiable owner for valid booking state transitions.
```

Current states must not be changed merely for architectural cleanup.

Preserve current semantics:

```text
pending
paid
cancelled
completed
```

Any discovery that current implementation and requirements disagree must be reported rather than silently corrected.

---

# 22. Step 8 — Multi-Slot Consolidation

Multi-slot booking must remain a single business operation.

Current requirements include:

```text
slot selection
slot compatibility
unified payment
unified invoice
multi-slot cancellation behavior
multi-slot reschedule behavior
```

Do not split multi-slot logic into unrelated duplicated workflows.

Target concept:

```text
CreateBooking
      ↓
Reservation Intent
      ↓
One or more compatible schedules
      ↓
Booking group / payment group
```

The exact internal structure should preserve current data and behavior unless a separate architecture decision explicitly changes it.

---

# 23. Availability Boundary

Availability is tightly coupled to Schedule.

RF-01 must identify where booking relies on availability, but should not fully redesign the Schedule domain.

The responsibility split should become:

```text
Booking
→ asks whether a reservation is possible

Schedule / Availability
→ owns availability rules
```

Detailed Schedule/Availability refactor belongs to:

```text
RF-03-SCHEDULE.md
```

During RF-01, extract only enough to create a stable booking boundary.

---

# 24. Tenant Security

Every extracted booking operation must preserve:

```text
tenant context
tenant ownership
service tenant association
schedule tenant association
booking tenant association
```

Do not trust:

```text
tenant_id
service_id
schedule_id
booking_id
```

without verifying ownership and relationships.

Do not bypass `TenantScope` without a documented reason and explicit tenant verification.

Any existing:

```text
withoutGlobalScope()
withoutGlobalScopes()
```

must be reviewed carefully before being moved.

Do not remove them blindly.

Do not add more bypasses merely because extraction is difficult.

---

# 25. Database and Concurrency

Do not change the booking schema during RF-01.

Preserve existing:

```text
transactions
row locking
unique constraints
foreign key relationships
booking indexes
active booking protection
```

The current repository contains an active-booking uniqueness mechanism.

Do not weaken or remove it.

Booking creation must remain safe against concurrent requests.

---

# 26. Model Responsibilities

`Booking` may retain:

```text
relationships
casts
simple state checks
simple entity behavior
```

Do not convert `Booking.php` into a God Model.

Complex workflows belong in Actions/domain/application boundaries.

Similarly:

```text
Schedule
Service
Payment
Tenant
```

must not become containers for the complete booking workflow.

---

# 27. Controller End State

After RF-01, controllers should be significantly thinner.

Customer:

```text
BookingController
```

should primarily coordinate:

```text
HTTP request
validation
tenant/public context
Action invocation
response/view
```

Owner:

```text
OwnerBookingController
```

should primarily coordinate:

```text
authorization
request validation
Action invocation
response
```

Booking management:

```text
BookingManageController
```

should not independently implement a separate booking domain.

---

# 28. Allowed Files / Areas

The agent may modify:

```text
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Customer/BookingManageController.php
app/Http/Controllers/Owner/OwnerBookingController.php
app/Models/Booking.php
app/Models/Schedule.php
app/Models/Service.php

app/Actions/Booking/*
app/Domain/Booking/*
app/Http/Requests/Booking/*

relevant booking tests
```

Additional files may be modified when directly required by the extraction.

Every additional file should have a clear reason.

---

# 29. Restricted Areas

Do not perform unrelated refactors in:

```text
Midtrans architecture
Subscription architecture
OwnerPortalController decomposition
large Blade decomposition
database schema renaming
route redesign
global terminology migration
```

Those belong to later work orders.

---

# 30. Forbidden Changes

Do not:

```text
change booking states
change pricing semantics
change voucher rules
change payment semantics
change payment-provider behavior
remove concurrency protection
remove tenant checks
remove security checks
rename database columns
rewrite customer booking UI
rewrite owner booking UI
replace Eloquent with another persistence system
introduce repositories everywhere
introduce unnecessary interfaces
rewrite all controllers
```

Do not "improve" behavior unless a requirement explicitly requires it.

---

# 31. Testing Requirements

At minimum, verify:

```text
customer booking creation
walk-in booking
booking availability
double booking prevention
cross-tenant isolation
multi-slot booking
voucher behavior
free booking behavior
booking cancellation
booking reschedule
booking token security
payment-group association
booking state transitions
```

Also run related:

```text
tenant/security tests
payment tests
schedule tests
```

because booking crosses these boundaries.

---

# 32. Regression Priority

If tests fail after an extraction, investigate in this order:

```text
1. Tenant isolation
2. Booking creation
3. Availability
4. Concurrency
5. Payment association
6. Cancellation
7. Reschedule
8. Multi-slot
9. Notifications/cache side effects
```

Do not immediately modify the test.

Determine whether the refactor changed behavior.

---

# 33. Acceptance Criteria

RF-01 is complete only when:

```text
[ ] Booking creation has an explicit application operation
[ ] Walk-in booking has an explicit application operation
[ ] Cancellation has a shared booking operation
[ ] Reschedule has a shared booking operation
[ ] Booking state transition ownership is explicit
[ ] Multi-slot logic remains behaviorally intact
[ ] Tenant isolation remains intact
[ ] Double-booking protection remains intact
[ ] Existing payment behavior remains intact
[ ] Relevant tests pass
[ ] Controllers are materially thinner
[ ] Business workflow is no longer concentrated in a single 400+ line controller method
[ ] No unrelated product behavior was introduced
[ ] TRACKER.md is updated
[ ] ARCHITECTURE.md is updated if the actual target structure has materially changed
```

---

# 34. Quality Criteria

Do not consider the following sufficient:

```text
"The code was moved into an Action."

"The controller is shorter."

"Tests are green."
```

The refactor should also improve:

```text
responsibility ownership
testability
reusability
dependency clarity
tenant safety
future change safety
```

A 1,500-line controller becoming a 1,400-line controller is not a successful architecture refactor.

A 438-line method copied into a 438-line Action without improving ownership is also not sufficient by itself.

---

# 35. Stop Conditions

Stop implementation and report a blocker when:

```text
requirements contradict existing behavior
critical booking behavior is not covered enough to refactor safely
a state transition is ambiguous
payment behavior must change to complete the extraction
tenant authorization is unclear
database meaning must change
multi-slot semantics are unclear
a proposed solution changes user-visible behavior
```

Do not invent a product or business-rule decision.

---

# 36. Agent Decision Rule

When uncertain about implementation structure:

Prefer:

```text
smallest change
+
existing valid pattern
+
documented target architecture
+
preserved behavior
```

Do not choose the most sophisticated architecture by default.

Do not introduce:

```text
Repository
DTO
Interface
Factory
Event
Domain Entity
Value Object
```

unless the specific responsibility justifies it.

---

# 37. Completion Report

When RF-01 is complete, report:

```text
RF:
RF-01-BOOKING

Branch:
Refactor

Baseline Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8

Current Commit:
<commit>

Implemented:
<summary>

Actions Created:
<list>

Requests Created:
<list>

Domain Components Created:
<list>

Controllers Changed:
<list>

Models Changed:
<list>

Tests Added:
<list>

Tests Executed:
<list>

Test Result:
<result>

Requirements Covered:
<IDs>

Behavior Changes:
None, unless explicitly documented.

Remaining Booking Debt:
<list>

Tracker Updated:
Yes/No

Architecture Documentation Updated:
Yes/No

Blockers:
<list>
```

---

# 38. Final Rule

The purpose of RF-01 is not to make BookQu's booking code look different.

The purpose is to establish:

```text
One booking domain
        ↓
Clear application operations
        ↓
Explicit business-rule ownership
        ↓
Thin HTTP controllers
        ↓
Preserved security and behavior
```

The implementation must remain aligned with:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
```

and with the refactor roadmap defined by:

```text
REFACTOR-PLAN.md
```

No architectural or product decision made during RF-01 may silently override those documents.
