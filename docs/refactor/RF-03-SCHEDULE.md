# RF-03 — Schedule & Availability Refactor

> **Status:** Planned
> **Priority:** High
> **Phase:** Schedule & Availability Consolidation
> **Integration Branch:** `Refactor`
> **Baseline Commit:** `ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8`
> **Reference Implementation:** `mergeV2`
> **Depends On:** RF-00 Foundation, RF-01 Booking
> **Related Work:** RF-02 Payment where payment state affects availability
> **Purpose:** Establish a clear and reusable Schedule / Availability boundary without changing booking behavior.

---

# 1. Objective

Refactor Schedule and Availability logic so that the system has a clear separation between:

```text
Schedule Management
+
Availability Calculation
+
Booking Occupancy
+
Conflict Detection
+
Cache Invalidation
```

The target direction is:

```text id="x4pj6p"
HTTP
 ↓
Schedule Application Operation
 ↓
Schedule / Availability Rules
 ↓
Persistence
```

and for customer booking:

```text id="8e0y68"
Booking Application Operation
 ↓
Availability Operation
 ↓
Schedule / Booking Data
```

The goal is to establish one understandable source of business rules for schedule availability.

This is not a redesign of BookQu's scheduling product.

---

# 2. Branch and Documentation Baseline

This work order is based on:

```text id="3quf3z"
Branch:
Refactor

Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

The repository currently has transitional documentation filenames.

Canonical target after RF-00:

```text id="cytkxj"
AGENTS.md
docs/README.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
```

Do not create duplicate documentation files.

If RF-00 has not yet renamed the documentation, use the existing numbered files while preserving their authority.

---

# 3. Mandatory Reading

Before changing Schedule or Availability code, read:

```text id="xdm2yv"
AGENTS.md
docs/PRODUCT.md
docs/REQUIREMENTS.md
docs/ARCHITECTURE.md
docs/DEVELOPMENT.md
docs/TRACKER.md
docs/REFACTOR-PLAN.md
```

Pay particular attention to:

```text id="m4x4u7"
Schedule requirements
Availability requirements
Booking requirements
Double-booking rules
Multi-slot rules
Tenant isolation
Caching
Transactions
Database integrity
```

---

# 4. Relevant Requirements

Primary Schedule requirements:

```text id="qz9jc1"
FR-SCHEDULE-001
FR-SCHEDULE-002
FR-SCHEDULE-003
FR-SCHEDULE-004
FR-SCHEDULE-005
FR-SCHEDULE-006
FR-SCHEDULE-007
FR-SCHEDULE-008
FR-SCHEDULE-009
FR-SCHEDULE-010
```

Booking requirements directly affected:

```text id="e4wpb1"
FR-BOOKING-002
FR-BOOKING-003
FR-BOOKING-005
FR-BOOKING-011
FR-BOOKING-012
FR-BOOKING-017
FR-BOOKING-018
FR-BOOKING-019
```

Multi-slot requirements:

```text id="9dn2fm"
FR-MULTIBOOK-001
FR-MULTIBOOK-002
FR-MULTIBOOK-005
FR-MULTIBOOK-006
```

The actual requirement descriptions in `REQUIREMENTS.md` remain authoritative.

---

# 5. Current Implementation

Schedule-related implementation is currently distributed across:

```text id="18x8a4"
app/Http/Controllers/Owner/OwnerScheduleController.php
app/Http/Controllers/Owner/OwnerBookingController.php
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Customer/BookingManageController.php
app/Models/Schedule.php
app/Models/Booking.php
app/Models/OwnerBlockedDate.php
app/Traits/ClearsBookingCache.php
```

Important supporting models:

```text id="1p4qge"
app/Models/Service.php
app/Models/Tenant.php
```

---

# 6. Current Schedule Hotspots

Current `OwnerScheduleController` is approximately:

```text id="no5ck2"
332 lines
```

It contains:

```text id="8au2aw"
schedule listing
bulk slot creation
schedule deletion
default pricing
availability configuration
blocked date deletion
validation
transactions
cache invalidation
```

Customer booking availability is also calculated inside:

```text id="nxf32g"
BookingController
```

Customer rescheduling also contains availability logic:

```text id="o7txkr"
BookingManageController
```

Owner booking operations also calculate available slots:

```text id="x7wkch"
OwnerBookingController::getAvailableSlots()
```

The Schedule model itself contains:

```text id="r34m0p"
availability status logic
isAvailable()
```

Cache behavior is concentrated in:

```text id="3kqd8k"
ClearsBookingCache
```

---

# 7. Current Architectural Problem

Schedule data and schedule availability are currently treated as if they were the same concern.

They are not.

The system contains at least four separate concepts:

```text id="8xxcx1"
Schedule
=
a bookable time slot record

Availability
=
whether the slot can currently be booked

Booking Occupancy
=
whether an active booking occupies the slot

Schedule Configuration
=
how schedules are created/configured
```

These concepts should be coordinated without being mixed into every controller.

---

# 8. Target Architecture

The target direction is:

```text id="q3wn8w"
app/
├── Actions/
│   └── Schedule/
│
├── Domain/
│   └── Schedule/
│
├── Http/
│   ├── Controllers/
│   └── Requests/
│
└── Services/
```

A possible target:

```text id="u0p4q7"
app/Actions/Schedule/
├── CreateSchedule.php
├── BulkCreateSchedules.php
├── DeleteSchedule.php
├── UpdateScheduleAvailability.php
└── DeleteBlockedDate.php
```

Availability may become an explicit operation such as:

```text id="0l9x9u"
GetAvailableSchedules
```

or another bounded operation consistent with the architecture.

Domain rules may be represented by:

```text id="kw5n1l"
app/Domain/Schedule/
├── AvailabilityRules.php
├── ScheduleConflictRules.php
└── SlotCompatibilityRules.php
```

These exact class names are not mandatory.

Do not create every class merely to populate the folder tree.

---

# 9. Core Boundary

The important distinction is:

```text id="7yv1c3"
Schedule Management
        ↓
creates / changes schedule records
```

while:

```text id="s3b5qy"
Availability
        ↓
answers whether a schedule can currently be reserved
```

and:

```text id="02c6cl"
Booking
        ↓
consumes availability
```

A controller must not independently reconstruct all three concepts.

---

# 10. Refactor Sequence

RF-03 must follow this order:

```text id="kueh0k"
Step 1
Schedule behavior baseline

↓
Step 2
Availability characterization

↓
Step 3
Extract schedule write operations

↓
Step 4
Centralize conflict rules

↓
Step 5
Centralize availability calculation

↓
Step 6
Centralize blocked-date rules

↓
Step 7
Consolidate multi-slot compatibility

↓
Step 8
Establish cache boundary

↓
Step 9
Reduce controller logic

↓
Step 10
Schedule architecture verification
```

Do not begin with cache cleanup.

---

# 11. Step 1 — Schedule Behavior Baseline

Before modification:

```text id="uhq8my"
[ ] Run the full test suite
[ ] Run schedule-specific tests
[ ] Run booking availability tests
[ ] Run concurrency tests
[ ] Run tenant isolation tests
[ ] Run reschedule tests
```

Relevant existing tests include:

```text id="ddg2mo"
tests/Feature/Owner/ScheduleManagementTest.php
tests/Feature/Customer/BookingFlowTest.php
tests/Feature/Customer/BookingManageTest.php
tests/Feature/Customer/ProductionLogicSpecificationTest.php
tests/Feature/Owner/BookingManagementTest.php
tests/Feature/CoreFlowIntegrationTest.php
tests/Feature/P0SecurityTest.php
```

Record baseline results before changing schedule logic.

---

# 12. Step 2 — Availability Characterization

The most important behavior to protect is not schedule CRUD.

It is:

> whether BookQu correctly determines whether a slot can be booked.

Characterization coverage must include:

```text id="w2nrc8"
available slot
already booked slot
pending booking reservation
paid booking
completed booking
cancelled booking
past slot
inactive service
blocked date
wrong tenant
wrong service
wrong date
multi-slot selection
concurrent booking attempt
```

Use existing tests where possible.

Add only missing coverage.

---

# 13. Availability Definition

Before refactoring, explicitly trace how current code determines availability.

The agent must inspect all relevant conditions including:

```text id="vhgrz4"
schedule status
booking status
booking creation time
booking cancellation
schedule date
schedule time
service status
tenant ownership
blocked dates
```

Do not assume availability means:

```text schedule.status == tersedia
```

The current implementation contains additional booking-state logic.

That existing behavior must be preserved.

---

# 14. Pending Booking Behavior

The current customer availability calculation treats some recent pending bookings as occupying a slot.

This is important because a pending payment may temporarily reserve availability.

Do not remove or simplify this rule during extraction.

Trace exactly:

```text id="fe8s61"
pending booking
+
creation time
+
expiration window
```

before modifying availability logic.

If this behavior is unclear, stop and report it.

---

# 15. Step 3 — Extract Schedule Creation

Primary source:

```text id="r5d4ua"
OwnerScheduleController
```

Relevant operation:

```text id="3g2s5a"
bulkStore()
```

Potential target:

```text id="jcvk4n"
app/Actions/Schedule/BulkCreateSchedules.php
```

The Action should own:

```text id="04niwl"
input normalization
date/time generation
service ownership validation
tenant ownership validation
conflict detection
schedule creation
appropriate transaction
cache invalidation
```

The controller should no longer implement the complete schedule-generation workflow.

---

# 16. Bulk Schedule Safety

Bulk creation must preserve:

```text id="a1l8z9"
duplicate prevention
date validity
time validity
service ownership
tenant ownership
pricing rules
blocked-date behavior
schedule conflict rules
```

Do not change how many schedules are created.

Do not change pricing semantics.

Do not change existing date/time interpretation.

---

# 17. Step 4 — Centralize Schedule Conflict Rules

Current schedule conflict protection should have one identifiable owner.

Potential conceptual boundary:

```text id="pjv5a7"
ScheduleConflictRules
```

The purpose is to centralize rules such as:

```text id="ah6ql6"
same tenant
same service
same date
same time
duplicate schedule
invalid overlapping schedule
```

Do not invent new overlap semantics.

Use current requirements and current implementation behavior.

---

# 18. Database Protection

Inspect the current database indexes and constraints before changing conflict logic.

Important:

```text id="3ptw3q"
application validation
+
database constraints
```

should complement each other.

Do not remove a database constraint because application validation now exists.

Do not add a speculative constraint without verifying whether existing data can satisfy it.

---

# 19. Step 5 — Centralize Availability Calculation

This is the most important refactor in RF-03.

Current availability logic is duplicated or partially duplicated across:

```text id="6h6lm4"
BookingController
BookingManageController
OwnerBookingController
Schedule model
```

The target should establish one clear application/domain operation.

Conceptually:

```text id="v2k1b7"
GetAvailableSchedules
        ↓
Availability Rules
        ↓
Schedule + Booking state
```

The exact API can differ.

The important requirement is:

```text id="2dt9gd"
Do not implement availability independently in every controller.
```

---

# 20. Customer Availability

The customer flow should eventually request availability through the common operation.

Current customer flow includes:

```text id="hs9xy9"
date selection
time selection
```

The refactor must preserve:

```text id="pk0sm4"
date availability
time-slot availability
tenant filtering
service filtering
pending booking protection
past-date protection
```

---

# 21. Owner Availability

Owner booking flow includes:

```text id="3k0fe2"
OwnerBookingController::getAvailableSlots()
```

It must use the same underlying availability semantics.

Owner permissions may affect what the owner is allowed to do, but should not produce a different definition of "available slot" unless explicitly required.

---

# 22. Reschedule Availability

Customer rescheduling currently checks available schedules separately.

After refactoring:

```text id="9s8lmr"
RescheduleBooking
        ↓
Availability Operation
```

should be preferred over a second custom availability implementation.

Do not let rescheduling accidentally consider the current booking as a conflict with itself.

This behavior must be preserved.

---

# 23. Multi-Slot Compatibility

Availability is not only:

```text one slot available
```

For multi-slot booking, the system must also determine:

```text id="62adfg"
slot compatibility
contiguous sequence
same service
same date
valid ordering
availability of every slot
```

RF-03 should establish a reusable compatibility rule.

Conceptually:

```text id="lp8q3i"
Multi-slot selection
        ↓
Slot Compatibility Rules
        ↓
Availability Rules
```

Detailed booking orchestration remains in RF-01.

---

# 24. Step 6 — Blocked Dates

Current blocked-date behavior uses:

```text id="qpv6jp"
OwnerBlockedDate
OwnerScheduleController
```

Determine exactly how blocked dates affect:

```text id="m9b6z7"
schedule creation
availability display
booking creation
reschedule
```

Centralize the common rule where appropriate.

Do not make blocked-date logic a generic helper.

It belongs conceptually to Schedule/Availability.

---

# 25. Step 7 — Schedule Model

`Schedule.php` may retain:

```text id="9j4zvv"
relationships
casts
simple availability/status predicates
simple entity behavior
```

The model should not become responsible for:

```text id="jyc9jz"
complete booking availability queries
multi-slot orchestration
cross-module workflows
cache invalidation
notifications
```

If `isAvailable()` is retained, its responsibility must be clearly defined.

Do not create two competing definitions:

```text Schedule::isAvailable()
```

and:

```text AvailabilityService::isAvailable()
```

unless the distinction is explicit.

---

# 26. Step 8 — Cache Boundary

Current cache behavior is primarily in:

```text id="khysl1"
app/Traits/ClearsBookingCache.php
```

This trait contains:

```text id="19fppq"
cache-key generation
service cache clearing
availability cache clearing
schedule cache clearing
bulk cache clearing
booking availability invalidation
```

It should be reviewed after availability logic is centralized.

---

# 27. Cache Refactor Direction

A potential target is:

```text id="ks8o6u"
app/Services/...
ScheduleAvailabilityCache
```

or another clearly bounded cache component.

The purpose is to make cache behavior explicit:

```text id="7v8e34"
Availability changes
        ↓
Invalidate relevant cache
```

not:

```text id="6k8ohf"
Every controller knows random cache keys.
```

Do not build a general-purpose cache utility.

---

# 28. Cache Correctness

Availability cache must never become authoritative.

The rule remains:

```text id="4mo8id"
Database / Booking state
        ↓
Availability calculation
        ↓
Cache optimization
```

not:

```text id="pvxvc4"
Cache
        ↓
Truth
```

When uncertain whether cached data can be trusted, prefer the authoritative source.

---

# 29. Cache Invalidation Requirements

Verify invalidation after:

```text id="2fmmf7"
schedule creation
schedule deletion
booking creation
booking payment
booking cancellation
booking reschedule
blocked-date change
service activation/deactivation
availability configuration changes
```

Do not invalidate the entire cache globally unless required.

Prefer targeted invalidation when the behavior is already safely understood.

---

# 30. Step 9 — Reduce Controllers

After schedule operations are extracted:

`OwnerScheduleController` should primarily:

```text id="s0vvq4"
accept request
authorize
invoke action
return response
```

Customer controllers should:

```text id="7vq2q1"
request availability
display it
pass selection to booking operation
```

They should not reimplement schedule rules.

---

# 31. Files / Areas In Scope

Primary areas:

```text id="g6s5vk"
app/Http/Controllers/Owner/OwnerScheduleController.php
app/Http/Controllers/Customer/BookingController.php
app/Http/Controllers/Customer/BookingManageController.php
app/Http/Controllers/Owner/OwnerBookingController.php

app/Models/Schedule.php
app/Models/Booking.php
app/Models/OwnerBlockedDate.php

app/Traits/ClearsBookingCache.php
```

Potential target locations:

```text id="o3ldtt"
app/Actions/Schedule/*
app/Domain/Schedule/*
app/Http/Requests/Schedule/*
app/Services/*
```

Only create the target classes when they represent real responsibilities.

---

# 32. Restricted Areas

Do not use RF-03 to perform:

```text id="7vdy8c"
full booking architecture refactor
payment architecture refactor
subscription refactor
database-wide naming migration
Blade redesign
route redesign
provider replacement
```

If a dependency blocks progress:

> report it instead of expanding scope.

---

# 33. Forbidden Changes

Do not:

```text id="j5br7g"
change slot duration semantics
change pricing semantics
change availability meaning
change booking status meaning
remove concurrency protection
remove pending-booking reservation behavior
remove blocked-date rules
rename database columns
replace schedule IDs
rewrite the customer UI
replace caching technology
introduce Redis merely for this refactor
```

Do not add new scheduling features.

---

# 34. Tenant Isolation

Every schedule operation must verify:

```text id="4q9t9y"
tenant
service ownership
schedule ownership
booking ownership where relevant
```

Do not use an incoming:

```text id="1stg1x"
service_id
schedule_id
tenant_id
```

as authorization.

Existing tenant architecture must remain:

```text id="7rj9uh"
TenantContext
TenantMiddleware
TenantScope
BelongsToTenant
```

where applicable.

---

# 35. Concurrency

Availability calculation may be read-only.

Booking creation and schedule mutation may require stronger protection.

Do not assume:

```text id="e50f82"
availability check
=
reservation protection
```

They are different.

The actual booking operation must still protect against concurrent reservations.

RF-03 must not weaken the locking/constraint strategy already protecting booking creation.

---

# 36. Transaction Boundaries

Schedule writes that affect multiple records should preserve appropriate transactions.

Examples:

```text id="3i5g05"
bulk schedule creation
complex blocked-date update
critical schedule configuration change
```

Pure availability reads do not need transactions unless the existing operation explicitly requires one.

Do not add transactions everywhere.

---

# 37. Performance Considerations

Availability may be a high-frequency read.

Do not optimize prematurely.

First establish:

```text id="t9x4z1"
correctness
query shape
indexes
cache behavior
```

Then optimize.

Do not introduce:

```text id="h2uglw"
queue
Redis
repository
CQRS
database redesign
```

without evidence.

---

# 38. Testing Requirements

At minimum verify:

```text id="xjag5m"
schedule creation
bulk schedule creation
schedule deletion
schedule conflict
schedule pricing
blocked dates
past schedule protection
available schedule
unavailable schedule
pending booking occupancy
cancelled booking release
rescheduled booking availability
multi-slot compatibility
cross-tenant protection
double booking protection
```

Also verify the customer and owner flows that consume availability.

---

# 39. Required Regression Areas

Because Schedule is shared by multiple domains, run:

```text id="xbi4hd"
schedule tests
booking tests
customer booking tests
customer reschedule tests
owner booking tests
security tests
concurrency tests
```

Do not consider RF-03 verified using only `ScheduleManagementTest`.

---

# 40. Acceptance Criteria

RF-03 is complete only when:

```text id="l39kdi"
[ ] Schedule creation has an explicit application boundary
[ ] Bulk schedule creation has an explicit application boundary
[ ] Schedule conflict rules have one identifiable owner
[ ] Availability rules have one identifiable owner
[ ] Customer availability uses the common availability boundary
[ ] Owner availability uses the common availability boundary
[ ] Reschedule availability uses the common availability boundary
[ ] Multi-slot compatibility rules are reusable
[ ] Blocked-date rules remain intact
[ ] Pending-booking occupancy behavior remains intact
[ ] Past-slot protection remains intact
[ ] Tenant isolation remains intact
[ ] Double-booking protection remains intact
[ ] Cache invalidation remains correct
[ ] Cache is not treated as source of truth
[ ] Existing schedule tests pass
[ ] Relevant booking tests pass
[ ] Relevant security tests pass
[ ] Controllers are materially thinner
[ ] No unrelated product behavior was introduced
[ ] TRACKER.md is updated
[ ] ARCHITECTURE.md is updated where required
```

---

# 41. Quality Criteria

This is not sufficient:

```text id="y4rc4o"
"ScheduleController is smaller."
```

or:

```text id="b8t3gq"
"AvailabilityService was created."
```

The actual objective is:

```text id="wjmxj1"
One clear definition of availability
        ↓
Reusable across booking entry points
        ↓
Protected by tests
        ↓
Independent of UI
        ↓
Independent of cache
```

The architecture should make it difficult for a new feature to accidentally invent another availability algorithm.

---

# 42. Stop Conditions

Stop and report when:

```text id="ju2ke5"
availability semantics are ambiguous
pending-booking behavior is unclear
multi-slot compatibility is unclear
schedule conflict rules conflict with database constraints
tenant ownership cannot be established
cache invalidation cannot be safely understood
current tests contradict requirements
a schema change appears necessary
```

Do not invent new availability behavior.

---

# 43. Agent Decision Rule

When choosing an implementation:

Prefer:

```text id="h5anb4"
existing correct behavior
+
single clear responsibility
+
reusable schedule rule
+
explicit application operation
+
minimal extraction
```

Avoid:

```text id="x9ez93"
generic ScheduleService
generic AvailabilityService
repository-per-model
unnecessary interfaces
large domain framework
```

unless the specific need is demonstrated.

---

# 44. Completion Report

When RF-03 is complete, report:

```text id="6u2r9m"
RF:
RF-03-SCHEDULE

Branch:
Refactor

Baseline Commit:
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8

Current Commit:
<commit>

Schedule Actions:
<list>

Availability Components:
<list>

Conflict Rules:
<list>

Cache Components:
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

Remaining Schedule Debt:
<list>

Tracker Updated:
Yes/No

Architecture Documentation Updated:
Yes/No

Blockers:
<list>
```

---

# 45. Final Rule

RF-03 must move BookQu toward:

```text id="w2lpq9"
Schedule Records
        ↓
Schedule Rules
        ↓
Availability Rules
        ↓
Booking Consumption
        ↓
Controlled Cache Optimization
```

The implementation must remain aligned with:

```text id="m7jz9m"
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
REFACTOR-PLAN.md
```

and with the current `Refactor` branch baseline:

```text
ccbcef00ad8726c1cef4ee56e6a2345c5941fbf8
```

No availability, scheduling, concurrency, or tenant-security behavior may be silently redefined during this refactor.
