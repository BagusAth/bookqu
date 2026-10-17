# BookQu Development Guide

> **Document Status:** Active Development Standard
> **Version:** 1.1
> **Authority:** Development workflow and implementation rules
> **Product Definition:** `docs/2-PRODUCT.md`
> **Requirements:** `docs/3-REQUIREMENT.md`
> **Architecture:** `docs/4-ARCHITECTURE.md`
> **System Design:** `docs/7-SYSTEM-DESIGN.md`
> **Operations:** `docs/8-OPERATIONS.md`
> **Tracker:** `docs/6-TRACKER.md`
> **ADR:** `docs/adr/`
> **Agent Rules:** `AGENT.md`
> **Last Updated:** 2026-09-30
>
> This document defines how BookQu changes are analyzed, implemented, tested, reviewed, and documented.
>
> It applies to feature development, bug fixes, refactoring, technical improvements, documentation changes, and work performed by AI agents.

---

# 1. Purpose

BookQu development follows a requirement-driven and evidence-driven workflow.

The development process must prevent:

* implementation based on guesswork;
* undocumented product behavior;
* silent scope expansion;
* duplicated business rules;
* tenant-isolation regressions;
* booking and payment inconsistencies;
* uncontrolled architectural drift;
* unnecessary abstractions;
* changes that pass locally but are not properly verified;
* documentation becoming inconsistent with the system.

The fundamental development principle is:

> **Understand the intended behavior before changing the implementation.**

The implementation must follow the current product, requirement, and architectural definitions.

---

# 2. Development Source of Truth

The active documentation responsibilities are:

```text
AGENT.md
    ↓
How AI agents should operate

docs/1-README.md
    ↓
Documentation map and authority guide

docs/2-PRODUCT.md
    ↓
What BookQu is

docs/3-REQUIREMENT.md
    ↓
What BookQu must do

docs/4-ARCHITECTURE.md
    ↓
How BookQu should be structured

docs/5-DEVELOPMENT.md
    ↓
How changes should be performed

docs/6-TRACKER.md
    ↓
What is currently implemented / in progress / pending

docs/7-SYSTEM-DESIGN.md
    ↓
How the current system actually works

docs/8-OPERATIONS.md
    ↓
How the system is run and verified operationally

docs/adr/
    ↓
Why significant architectural decisions exist
```

These documents have different responsibilities.

A document must not become the authority for another document's responsibility simply because it contains related information.

---

# 3. Development Information Hierarchy

For a normal implementation task, use this reading order:

```text
1. AGENT.md
2. docs/1-README.md
3. docs/2-PRODUCT.md
4. docs/3-REQUIREMENT.md
5. docs/4-ARCHITECTURE.md
6. docs/7-SYSTEM-DESIGN.md
7. docs/5-DEVELOPMENT.md
8. docs/6-TRACKER.md
9. docs/8-OPERATIONS.md when operational behavior is involved
10. docs/adr/ when an architectural decision is relevant
```

The order is not a rigid requirement for every task.

For a small bug fix, reading the entire repository documentation is unnecessary.

The principle is:

> Read the minimum authoritative material necessary to understand the task correctly.

---

# 4. Current System vs Target Structure

BookQu documentation distinguishes between:

```text
Architecture
→ How the system should be structured

System Design
→ How the current system is actually implemented

Development
→ How changes should be made
```

Do not confuse these concepts.

Existing implementation may contain details that are not the preferred architectural pattern.

When implementing new code:

> Follow the current architectural direction unless compatibility or existing constraints require another approach.

When modifying existing code:

> Understand its actual behavior before restructuring it.

---

# 5. Task Classification

Every meaningful task should first be classified.

The primary categories are:

```text
Feature
Bug Fix
Refactor
Technical Improvement
Documentation
Product Change
Operational Change
```

## 5.1 Feature

Adds an approved product capability.

Example:

```text
Add walk-in booking capability for owners.
```

## 5.2 Bug Fix

Corrects behavior that violates an existing requirement.

Example:

```text
A customer can currently book a schedule that should be unavailable.
```

## 5.3 Refactor

Changes implementation structure without intentionally changing accepted product behavior.

Example:

```text
Extract booking orchestration from a large controller.
```

## 5.4 Technical Improvement

Improves security, performance, testing, maintainability, observability, or developer experience.

Example:

```text
Add a missing tenant-isolation regression test.
```

## 5.5 Documentation

Updates an authoritative document without changing application behavior.

## 5.6 Product Change

Changes what BookQu is intended to do.

Examples:

```text
Allow customers to create accounts.
Add Google Calendar synchronization.
Allow customer staff selection.
Change cancellation policy.
```

A product change must go through requirement and product-definition review before implementation.

## 5.7 Operational Change

Changes how the deployed system is operated without necessarily changing product behavior.

Examples:

```text
Change scheduler configuration.
Change queue worker configuration.
Add production monitoring.
Change operational recovery procedure.
```

Operational changes must be coordinated with `docs/8-OPERATIONS.md`.

---

# 6. Step 1 — Understand the Task

Before changing code, identify the intended outcome.

Answer:

```text
Who is acting?

What are they trying to do?

Under what conditions?

What should happen?

What must not happen?

What data is affected?

What other domains are affected?

What security boundaries apply?
```

A task such as:

```text
Fix booking page
```

is insufficiently precise.

A better task definition is:

```text
A customer must not be able to select a schedule that is unavailable under the current booking rules.
```

---

# 7. Step 2 — Identify the Requirement

Every meaningful behavioral change should map to an existing requirement.

For example:

```text
Requirement:
FR-BOOKING-003

Customer can select an available schedule.
```

For a bug:

```text
Requirement:
FR-BOOKING-017

A booking must not be created against an unavailable schedule.

Current defect:
One booking path bypasses the authoritative availability check.
```

For a refactor:

```text
Requirement:
Existing booking behavior must remain unchanged.

Change:
Move orchestration into the appropriate application boundary.
```

If there is no requirement covering the desired behavior, determine whether the task is actually a product change.

---

# 8. Step 3 — Inspect the Current System

Before creating or changing an implementation, inspect the relevant current system.

Depending on the task, inspect:

```text
Routes
Controllers
Form Requests
Models
Actions
Domain classes
Services
Policies
Middleware
Views
JavaScript
Migrations
Jobs
Commands
Notifications
Mail
Tests
Configuration
Related documentation
```

The goal is to answer:

> Where does this behavior currently live?

and:

> Does BookQu already solve part of this problem?

Never assume that something does not exist merely because its name is unfamiliar.

---

# 9. Search Before Create

Before creating a new:

```text
Controller
Action
Service
Model method
Policy
Query
Component
Helper
Job
Command
Notification
Test
```

search the repository.

Use existing implementations where they are appropriate.

If an existing implementation is structurally poor but already owns the relevant behavior, prefer improving or extracting it rather than creating a duplicate implementation.

---

# 10. Identify the Domain

Every implementation task should have one or more identifiable domains.

Common BookQu domains include:

```text
Authentication
Tenant
Service
Category
Schedule
Booking
Customer
Payment
Subscription
Voucher
Staff
Resource
Review
Notification
Analytics
Asset
```

If a task touches several domains, identify each boundary explicitly.

For example:

```text
Booking creation
    ↓
Booking
    ├── Service
    ├── Schedule / Availability
    ├── Customer
    ├── Payment
    └── Notification
```

This does not mean all logic belongs in one component.

It means the dependency boundaries must be understood before implementation.

---

# 11. Determine Whether Behavior Changes

One of the most important development questions is:

> Does this task change user-visible behavior or business rules?

If no:

```text
Bug Fix
Refactor
Technical Improvement
Internal Documentation
```

may be appropriate.

If yes:

```text
Product / Requirement Change
```

must be considered.

Examples:

```text
Changing cancellation rules
Adding customer accounts
Changing booking limits
Adding a payment method
Changing subscription entitlements
Changing who can access a booking
```

These must not be silently introduced under the label of "refactor".

---

# 12. Product Change Workflow

For an accepted product change:

```text
Product decision
      ↓
Update docs/2-PRODUCT.md
      ↓
Update docs/3-REQUIREMENT.md
      ↓
Evaluate docs/4-ARCHITECTURE.md
      ↓
Evaluate docs/7-SYSTEM-DESIGN.md
      ↓
Update docs/6-TRACKER.md
      ↓
Implement
      ↓
Test
      ↓
Verify
```

If the change establishes a significant architectural decision, create or update an ADR.

---

# 13. Requirement Conflict Handling

Code, tests, documentation, and tracker state may occasionally disagree.

Do not silently choose whichever source is easiest to implement.

Use:

```text
Identify conflict
      ↓
Determine intended current behavior
      ↓
Check Product and Requirement authority
      ↓
Check current System Design
      ↓
Resolve discrepancy explicitly
      ↓
Update affected source of truth
      ↓
Implement / test
```

Important distinction:

```text
Product
→ Defines what BookQu is

Requirement
→ Defines what BookQu must do

System Design
→ Describes how it currently works

Tracker
→ Describes implementation status
```

The tracker must not silently redefine product behavior.

---

# 14. Implementation Planning

For small tasks, a short plan is enough:

```text
1. Identify affected action
2. Fix rule
3. Add regression test
4. Verify
```

For larger tasks:

```text
Requirement:
<requirement>

Domain:
<domain>

Current implementation:
<relevant files>

Architectural boundary:
<where responsibility belongs>

Behavior to preserve:
<existing behavior>

Behavior to add/change:
<intended behavior>

Tests:
<tests to add/update>

Documentation:
<documents affected>
```

Planning should be proportional to task complexity.

Do not produce a large speculative plan for a trivial change.

---

# 15. Architecture Before Abstraction

New code must follow `docs/4-ARCHITECTURE.md`.

Before creating a new abstraction, ask:

```text
What responsibility requires this?

Does an existing component already own it?

Is the new abstraction actually reusable?

Does it protect a meaningful boundary?

Will it make testing or maintenance easier?
```

Do not create abstractions merely because a project contains:

```text
Service
Repository
Manager
Helper
Utility
```

directories.

Architecture is based on responsibility, not folder count.

---

# 16. Controller Development

Controllers should primarily coordinate HTTP interaction:

```text
Request
    ↓
Validation / Authorization
    ↓
Application Operation
    ↓
Response
```

Controllers should not become the authoritative home for:

```text
Booking availability
Payment state transitions
Refund processing
Subscription entitlement
Complex pricing rules
Tenant isolation rules
Multi-step business workflows
```

These responsibilities should live at the appropriate application/domain boundary.

---

# 17. Form Request Development

Form Requests should handle request-level concerns such as:

```text
Required fields
Input format
Type constraints
Field-level validation
Request authorization
```

They should not become the permanent home for reusable domain rules.

For example:

```text
email is required
```

is request validation.

Whereas:

```text
schedule is unavailable because another active booking occupies it
```

is a business rule.

---

# 18. Application Action Development

Application Actions should represent meaningful business operations.

Examples:

```text
CreateBooking
CreateWalkInBooking
CancelBooking
RescheduleBooking
CreateSchedule
ProcessBookingPayment
ExpirePayment
ProcessRefund
CreateSubscription
```

An Action should have:

```text
Clear purpose
Defined input
Predictable behavior
Meaningful transaction boundary where necessary
```

Avoid generic actions such as:

```text
DoBookingStuff
HandleEverything
CommonAction
GenericManager
```

---

# 19. Domain Logic Development

A business rule should have a clear owner.

Examples:

```text
Booking availability
→ Booking / Schedule domain

Booking state transition
→ Booking domain

Payment state handling
→ Payment domain

Refund lifecycle
→ Refund / Payment domain

Subscription entitlement
→ Subscription domain

Tenant ownership
→ Tenant / authorization boundary
```

Do not independently implement the same rule in:

```text
Controller A
Controller B
Blade
JavaScript
Command
Job
```

unless one of those components is deliberately delegating to the authoritative rule.

---

# 20. Model Development

Models should primarily represent:

```text
Persistent state
Relationships
Casts
Scopes
Simple domain-adjacent behavior
```

A model should not become a complete application workflow.

Avoid putting large processes such as:

```text
Create booking
Charge payment
Send notifications
Process refund
```

into a single model method.

Use appropriate application/domain boundaries.

---

# 21. Service Development

A service is appropriate when a coherent responsibility needs reusable coordination.

Good reasons include:

```text
Logic crosses several models
External integration is involved
The operation is reused
Independent testing is useful
```

Avoid generic containers such as:

```text
GeneralService
CommonService
UtilityService
EverythingService
```

---

# 22. Repository Development

Repositories are optional.

Do not create repositories automatically for every model.

Use Eloquent when it is sufficient.

Introduce a repository only when it provides a meaningful persistence abstraction or isolates a real complexity.

The presence of a repository layer is not itself an architectural goal.

---

# 23. Booking Development Rules

Any change touching booking must consider:

```text
Tenant ownership
Service ownership
Schedule ownership
Availability
Concurrency
Booking state
Pending grace period
Payment relationship
Cancellation
Rescheduling
Multi-slot booking
Walk-in booking
```

A booking implementation must use the authoritative business rules rather than creating a second availability definition.

---

# 24. Booking State Rules

The current booking states are:

```text
pending
paid
cancelled
completed
```

Changes to booking state must occur through controlled application/domain behavior.

Do not let arbitrary client input directly determine internal booking state.

The implementation must preserve valid state transitions.

---

# 25. Pending Booking Rules

BookQu currently uses a 15-minute pending grace period.

The development implementation must preserve the distinction between:

```text
Pending within grace
→ still occupies the relevant schedule

Pending beyond grace
→ stale and should no longer block normal availability
```

The grace period is a centralized business rule.

Do not duplicate `15 minutes` independently throughout:

```text
Controllers
Blade
JavaScript
Commands
Queries
Tests
```

Use the authoritative booking rule instead.

---

# 26. Stale Pending and Concurrency

A stale pending booking must not permanently prevent a later booking.

When an authoritative booking operation encounters stale pending state, it must handle that state safely within the relevant transaction/concurrency boundary.

The development implementation must not rely solely on:

```text
UI availability
```

or:

```text
A background scheduler eventually cleaning it up
```

to preserve correctness.

---

# 27. Double-Booking Protection

Availability validation and database persistence must be treated as separate concerns.

The booking architecture should use an appropriate combination of:

```text
Business rule validation
+
Transaction
+
Locking where required
+
Database integrity constraint
```

Do not assume that:

```text
"the slot was available a moment ago"
```

is sufficient protection against concurrent requests.

---

# 28. Multi-Slot Booking

Multi-slot booking must preserve the integrity of the reservation as a whole.

Implementation must consider:

```text
All requested schedules
Compatibility
Availability
Concurrency
Atomic persistence
Payment relationship
Cancellation / rescheduling behavior
```

A partial reservation must not leave the system in an invalid state.

---

# 29. Walk-In Booking

Walk-in booking is an owner-facing booking operation.

Its implementation must still respect:

```text
Tenant boundary
Service ownership
Schedule availability
Booking state rules
Payment behavior where applicable
Customer information rules
```

The fact that an owner initiated the booking does not remove core domain invariants.

---

# 30. Payment Development Rules

Payment changes must consider:

```text
Payment state
Booking state
Provider response
Webhook
Duplicate callbacks
Expiration
Failure
Cancellation
Refund
```

Current payment statuses are:

```text
pending
sukses
gagal
```

Payment expiration is not a separate persistent payment status.

The payment lifecycle must be consistent with the requirements in `docs/3-REQUIREMENT.md`.

---

# 31. Payment Verification

Do not trust browser state or client-side claims as the final authority for financial state.

Payment success must be established through the trusted payment flow.

Development work around payment should explicitly consider:

```text
Customer redirect
Provider response
Webhook
Verification
Idempotency
Failure handling
```

---

# 32. Payment Idempotency

External payment callbacks may be delivered more than once.

Repeated processing of the same payment event must not create unintended duplicate effects.

The implementation must consider:

```text
Duplicate payment callback
Duplicate booking state transition
Duplicate refund
Duplicate notification
```

where each effect should occur only once.

---

# 33. Refund Development Rules

Refund is a separate stateful process.

Current refund states are:

```text
pending
processed
failed
```

Do not encode refund lifecycle by overloading payment or booking status.

Any refund change must consider:

```text
Eligibility
Authorization
Idempotency
Provider interaction
Booking relationship
Notification
Failure recovery
```

---

# 34. Customer Token Rules

Customer management without an authenticated BookQu account depends on scoped tokens.

Token-protected capabilities must be treated independently.

Examples:

```text
View booking
View invoice
Submit review
Cancel booking
Reschedule booking
```

A token must:

```text
Belong to the expected booking
Be valid for the requested capability
Not grant access to another booking
Not bypass tenant or resource boundaries
```

Cross-booking or cross-scope token use must fail authorization.

---

# 35. Subscription Development Rules

Subscription behavior must preserve:

```text
Plan
Subscription
Status
Entitlement
Usage limit
Expiration
Feature access
```

Feature entitlement must have a centralized source of truth.

Do not implement subscription checks independently and inconsistently across many controllers.

---

# 36. Tenant Development Rules

Every tenant-scoped implementation must answer:

```text
How is tenant context established?

Who owns the resource?

How is authorization checked?

Can another tenant reference the resource?

Can another tenant mutate it?

What test proves isolation?
```

At minimum, relevant tests should establish:

```text
Tenant A can access Tenant A data.
Tenant A cannot access Tenant B data.
```

A user-provided tenant identifier must never be treated as sufficient authorization evidence.

---

# 37. Authorization Development

Authorization should be checked at the backend boundary.

Never rely on:

```text
Hidden input
Disabled button
Frontend route hiding
JavaScript condition
Unlinked menu item
```

for security.

The backend must independently verify access.

---

# 38. Validation Development

Validation may exist at several layers:

```text
UI validation
      ↓
Form Request validation
      ↓
Application / domain rule validation
      ↓
Database integrity
```

These layers are complementary.

Client-side validation improves UX.

Server-side validation preserves application correctness.

Database constraints protect final persistence integrity.

---

# 39. Database Change Workflow

For schema changes:

```text
Requirement
    ↓
Identify data-model impact
    ↓
Create migration
    ↓
Update relationships / models
    ↓
Update fixtures / seeders where needed
    ↓
Update application code
    ↓
Update tests
    ↓
Verify
```

Never modify an already-applied migration merely to alter production history.

Create a new migration.

---

# 40. Database Safety

Before changing a database field or relationship, search for:

```text
Model references
Queries
Scopes
Controllers
Actions
Services
Tests
Seeders
Views
Commands
Jobs
Notifications
Reports
```

A schema change can have a much larger impact than the migration file itself suggests.

---

# 41. Transactions

Use a transaction when multiple related changes must succeed or fail together.

Examples include:

```text
Create booking
Reschedule booking
Cancel booking
Multi-slot booking
Payment state synchronization
Refund state changes
Subscription state changes
```

Transaction boundaries should be meaningful.

Do not place unrelated external network calls inside transactions merely because they happen during the same request.

---

# 42. Cache Rules

Cache is an optimization, not the source of business truth.

For booking-related behavior:

```text
Authoritative persistent state
        ↓
Derived / cached state
```

not:

```text
Cache
        ↓
Authoritative booking state
```

Any cache affecting availability or tenant data must have:

```text
Correct key scope
Tenant isolation
Invalidation strategy
Stale-data tolerance
```

A cache key must include every dimension required to prevent incorrect data sharing.

---

# 43. Scheduler and Background Work

Time-based business behavior should be implemented in application/domain operations, not duplicated in scheduler code.

Conceptually:

```text
Scheduler
    ↓
Command / Action
    ↓
Business Rule
    ↓
Persistent State
```

For example:

```text
bookings:expire-payments
```

should invoke the appropriate application behavior rather than contain an independent copy of payment-expiration rules.

Operational configuration belongs in:

```text
docs/8-OPERATIONS.md
```

---

# 44. Queue and Job Rules

Use background jobs when work:

```text
is slow;
can safely run asynchronously;
does not need to block the request;
can be retried safely.
```

Do not move critical state changes into asynchronous processing if doing so would allow the system to temporarily violate a critical invariant.

Jobs must be designed with:

```text
Idempotency
Retry behavior
Failure handling
Logging
```

where appropriate.

---

# 45. External Integration Development

Treat external systems as unreliable.

Examples:

```text
Midtrans
Email
Storage
Future messaging integrations
Future calendar integrations
```

External systems can:

```text
timeout
fail
retry
return duplicates
return invalid data
be temporarily unavailable
```

Integration code must have explicit behavior for these conditions.

Provider-specific code should stay behind the intended infrastructure boundary.

---

# 46. Frontend Development

The frontend should handle presentation and interaction.

Appropriate frontend responsibilities include:

```text
Modal state
Tabs
Dropdowns
Filtering
Preview
Client-side UX validation
Loading state
Local interaction
```

The frontend must not become the authoritative source of:

```text
Availability
Authorization
Tenant identity
Payment status
Subscription entitlement
Booking state
```

---

# 47. Blade Development

Blade is presentation.

Do not put authoritative business behavior into Blade.

Avoid:

```text
Database mutations
Complex booking rules
Payment mutation
Authorization implementation
Large business calculations
```

Before creating repeated markup, search for existing components.

Prefer:

```text
Page
    ↓
Reusable components
```

instead of repeated copies of the same UI.

---

# 48. JavaScript / Alpine Rules

JavaScript and Alpine.js should support interaction rather than duplicate server business logic.

Acceptable:

```text
Open modal
Toggle state
Filter visible records
Preview image
Animate UI
Prepare request payload
```

Not authoritative:

```text
Decide who may cancel
Decide whether a schedule is truly available
Decide whether payment succeeded
Decide tenant identity
Determine final subscription entitlement
```

Those decisions must be enforced server-side.

---

# 49. Route Development

Routes should remain declarative.

A route should primarily define:

```text
HTTP method
URI
Controller / handler
Middleware
Route name
```

Do not put complex business logic into route closures.

Route naming should follow existing project conventions and canonical terminology.

---

# 50. Naming Rules

New PHP classes:

```text
PascalCase
```

Methods and variables:

```text
camelCase
```

Database fields:

```text
snake_case
```

Use the canonical terminology defined by `docs/2-PRODUCT.md`.

Do not casually introduce new names for an existing domain concept.

---

# 51. Legacy Compatibility

When modifying existing functionality, check compatibility with:

```text
Existing routes
Public booking URLs
Customer management links
Database relationships
Existing tests
Seeders
External integrations
Existing tenant data
```

Do not casually break public customer-facing URLs or management links.

When a breaking change is required, treat it explicitly as such.

---

# 52. Refactoring Workflow

Refactoring should normally follow:

```text
Understand current behavior
        ↓
Identify requirement
        ↓
Inspect tests
        ↓
Identify target boundary
        ↓
Characterize missing behavior if needed
        ↓
Make one coherent structural change
        ↓
Run tests
        ↓
Continue
```

Do not rewrite large portions of the application without intermediate verification.

---

# 53. Characterization Tests

When existing behavior is poorly tested, add characterization tests before risky restructuring.

The purpose is to record:

> What does the current implementation actually do?

Then compare it against:

> What should the product do?

This distinction prevents accidental preservation of incorrect legacy behavior.

A characterization test is evidence of current implementation, not automatically a product requirement.

---

# 54. Refactor vs Product Change

A refactor should preserve intended behavior unless a behavioral change is explicitly part of the task.

A refactor should generally preserve:

```text
Inputs
Outputs
Authorization
Tenant isolation
Business invariants
User-visible behavior
Data integrity
```

If the implementation must change behavior, classify the task accordingly.

Do not hide a feature change inside a refactor.

---

# 55. Testing Workflow

Testing should happen during implementation, not only after all work is finished.

Preferred sequence:

```text
Implement small change
      ↓
Run focused test
      ↓
Inspect result
      ↓
Fix
      ↓
Run relevant suite
      ↓
Run full suite when appropriate
```

A large implementation should not wait until the end for its first verification.

---

# 56. Test Scope

Use the smallest useful test scope first:

```text
Specific test
    ↓
Related test class
    ↓
Related feature/module suite
    ↓
Full test suite
```

This provides fast feedback during development while preserving final confidence.

---

# 57. Test Requirements

Meaningful behavior changes should normally add or update tests.

Especially when changing:

```text
Booking
Availability
Concurrency
Payment
Refund
Authorization
Tenant isolation
Customer token access
Subscription entitlement
Data integrity
```

Tests should focus on observable behavior and critical invariants.

---

# 58. Critical Booking Test Coverage

Booking changes should consider, where relevant:

```text
Available schedule
Unavailable schedule
Expired pending booking
Pending within grace period
Concurrent booking
Cancellation
Rescheduling
Multi-slot booking
Cross-tenant access
Cross-booking token access
Payment-dependent booking state
```

The exact test matrix depends on the feature.

---

# 59. Critical Payment Test Coverage

Payment changes should consider:

```text
Pending payment
Successful payment
Failed payment
Expired payment
Duplicate webhook
Invalid webhook
Booking synchronization
Refund interaction
```

The goal is to prevent inconsistent financial and booking state.

---

# 60. Failed Test Handling

A failed test does not automatically mean the test should be changed.

First determine:

```text
Is the implementation wrong?

Is the requirement wrong?

Is the test outdated?

Is the test asserting legacy behavior?

Is test setup/data incorrect?
```

Then change the appropriate source.

Never modify a test merely to make the suite green.

---

# 61. Manual Verification

Manual verification is appropriate for:

```text
Visual UI changes
Responsive layout
Browser interaction
Complex customer booking flow
Payment sandbox flow
External integration behavior
Operational procedures
```

When manual verification is important and cannot reasonably be automated, record what was verified.

---

# 62. Security Verification

Any change involving authentication, authorization, tenant access, customer tokens, files, payments, or webhooks must include security consideration.

Check:

```text
Unauthorized access
Cross-tenant access
IDOR
Token scope
Input tampering
CSRF
XSS
SQL injection
Sensitive data exposure
Webhook authenticity
```

Security issues take precedence over minimal diff preference.

---

# 63. Performance Development

Do not optimize without evidence.

Preferred process:

```text
Measure
    ↓
Identify bottleneck
    ↓
Change
    ↓
Measure again
```

Do not introduce:

```text
Cache
Queue
Repository
Complex abstraction
Denormalization
```

merely because it appears more scalable.

Optimization should solve an identified problem.

---

# 64. Scope Discipline

Do not expand a task simply because unrelated issues are discovered.

Example:

```text
Task:
Fix reschedule validation.

Discovered:
Dashboard visual design is outdated.
```

Do not redesign the dashboard within the same task unless explicitly required.

Record unrelated improvements separately through the project tracker or issue workflow.

---

# 65. Opportunistic Refactoring

Small refactoring is acceptable when directly related to the task and it reduces implementation risk.

Example:

```text
Task:
Fix duplicated cancellation logic.

Related refactor:
Extract the duplicated cancellation rule into the authoritative application/domain boundary.
```

This is appropriate.

Unrelated rewrites are not.

---

# 66. Minimal Coherent Diff

Prefer the smallest coherent implementation.

However:

> Minimal diff is not more important than security, data integrity, or business correctness.

A small patch that leaves a critical race condition is not a good implementation merely because its diff is small.

---

# 67. Documentation Synchronization

Update documentation when a change affects:

```text
Product behavior
Requirement
Business rule
Architecture
Current system behavior
Operational procedure
Canonical terminology
```

Relevant documents include:

```text
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/6-TRACKER.md
docs/7-SYSTEM-DESIGN.md
docs/8-OPERATIONS.md
docs/adr/
```

Do not update every document for every code change.

Only the sources whose responsibility actually changed need updating.

---

# 68. System Design Synchronization

Update `docs/7-SYSTEM-DESIGN.md` when a change materially affects how the current system works.

Examples:

```text
New booking flow
Changed payment flow
Changed tenant-resolution mechanism
Changed authorization path
Changed scheduler behavior
Changed important state transition
Changed critical database invariant
```

Do not turn System Design into a chronological development log.

It should describe the current system.

---

# 69. Operations Synchronization

Update `docs/8-OPERATIONS.md` when a change affects:

```text
Scheduler
Queue
Cache
Environment configuration
Deployment prerequisites
Production verification
Monitoring
Recovery
External provider configuration
Operational troubleshooting
```

Do not put operational procedures into `5-DEVELOPMENT.md` merely because the developer implemented them.

---

# 70. ADR Synchronization

Create or update an ADR when a decision:

```text
Affects multiple domains
Creates a long-lived architectural constraint
Changes a major integration boundary
Changes tenant strategy
Changes booking concurrency strategy
Changes payment architecture
Introduces a significant new technical pattern
```

Minor implementation details do not require ADRs.

---

# 71. Tracker Synchronization

After a meaningful task, update:

```text
docs/6-TRACKER.md
```

The tracker should reflect actual implementation status.

At minimum, status should identify:

```text
What changed
Current status
Verification state
Relevant requirement
Important notes
```

Do not mark something `Done` merely because code has been written.

---

# 72. Definition of Done

A meaningful implementation task should satisfy the relevant items below:

```text
[ ] Requirement identified
[ ] Product scope confirmed
[ ] Current implementation inspected
[ ] Architecture boundary identified
[ ] Implementation completed
[ ] Validation handled
[ ] Authorization handled
[ ] Tenant isolation considered
[ ] Relevant business invariants preserved
[ ] Tests added/updated when appropriate
[ ] Focused tests pass
[ ] Relevant broader tests pass
[ ] UI manually verified when appropriate
[ ] No unintended scope expansion
[ ] Documentation updated when required
[ ] Tracker updated when required
```

Not every task requires every item, but skipping an item should be deliberate.

---

# 73. Definition of Done for Refactoring

A refactor is complete when:

```text
[ ] Existing behavior understood
[ ] Intended behavior mapped to requirement
[ ] Target architecture identified
[ ] Responsibilities become clearer
[ ] Business logic is not duplicated
[ ] Tests pass
[ ] Security boundaries preserved
[ ] Tenant isolation preserved
[ ] Data integrity preserved
[ ] Documentation updated if architecture/current design changed
```

---

# 74. Definition of Done for Bug Fixes

A bug fix is complete when:

```text
[ ] Affected requirement identified
[ ] Root cause understood
[ ] Bug reproduced where practical
[ ] Correct fix implemented
[ ] Regression test added/updated
[ ] Relevant tests pass
[ ] Related flow checked
[ ] No unrelated behavior changed
[ ] Tracker updated when appropriate
```

---

# 75. Definition of Done for New Features

A new feature is complete when:

```text
[ ] Product behavior is accepted
[ ] Requirement exists
[ ] Domain identified
[ ] Architecture approach identified
[ ] Existing implementation inspected
[ ] Authorization considered
[ ] Tenant isolation considered
[ ] Persistence impact considered
[ ] Implementation complete
[ ] Relevant tests complete
[ ] UI verified where applicable
[ ] Documentation synchronized
[ ] Tracker synchronized
```

---

# 76. Definition of Done for Product Changes

A product change is complete when:

```text
[ ] Product definition updated
[ ] Requirements updated
[ ] Architecture evaluated
[ ] Current system design updated if necessary
[ ] Tracker updated
[ ] Implementation complete
[ ] Tests updated
[ ] User-visible behavior verified
```

---

# 77. Agent Working Standard

AI agents working on BookQu must:

```text
Read before changing.
Search before creating.
Use requirements before assumptions.
Use architecture before abstractions.
Inspect current system before restructuring.
Treat existing code as evidence, not automatically the target.
Protect tenant isolation.
Protect booking correctness.
Protect payment correctness.
Test meaningful changes.
Keep scope controlled.
Synchronize documentation.
```

An AI agent must not generate code merely because a task description sounds plausible.

It must first establish enough repository evidence to implement the requested change safely.

---

# 78. Agent Decision Rule

When the intended behavior is clear from:

```text
Product
Requirements
Architecture
System Design
Existing tests
Current implementation
```

the agent should proceed.

An agent should only require clarification when:

```text
Two or more materially different product behaviors
are both plausible
AND
the available repository evidence cannot distinguish them.
```

Implementation difficulty alone is not a reason to stop.

---

# 79. Agent Conflict Rule

When sources conflict, the agent must not silently invent a resolution.

The agent should determine whether the discrepancy is:

```text
Documentation drift
Implementation drift
Test drift
Tracker drift
Product ambiguity
```

Then update the correct source of truth.

Historical implementation should not automatically override current product or requirements.

---

# 80. Agent Completion Report

For non-trivial work, the agent should produce a completion summary containing:

```text
Implemented:
<what changed>

Requirement:
<affected requirement IDs>

Files:
<important changed files>

Tests:
<tests run>

Verification:
<what was verified>

Documentation:
<documents updated>

Notes:
<important limitations or follow-up>
```

This makes implementation traceable for future contributors and agents.

---

# 81. No Uncontrolled Rewrite

Do not rewrite BookQu from scratch unless explicitly authorized.

Do not replace a stable architectural boundary simply because another pattern is more fashionable.

Prefer:

```text
Understand
    ↓
Characterize
    ↓
Extract
    ↓
Test
    ↓
Replace
    ↓
Verify
```

over:

```text
Delete everything
    ↓
Rewrite everything
    ↓
Hope the behavior remains equivalent
```

---

# 82. Historical Material

Historical refactor documentation, earlier implementation plans, old branches, and previous architecture states may remain available in Git history.

They are not current development authority.

Current development must follow:

```text
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
docs/4-ARCHITECTURE.md
docs/5-DEVELOPMENT.md
docs/7-SYSTEM-DESIGN.md
```

as applicable.

Git history explains how BookQu arrived at its present structure.

It does not automatically define what BookQu should do next.

---

# 83. Development Safety Priorities

When trade-offs exist, development should prioritize:

```text
1. Security
2. Data Integrity
3. Business Correctness
4. Tenant Isolation
5. Maintainability
6. Testability
7. Performance
8. Convenience
```

Convenience must not override security or correctness.

---

# 84. Core Development Chain

Every meaningful BookQu change should fit this model:

```text
PRODUCT
   ↓
REQUIREMENT
   ↓
DOMAIN
   ↓
ARCHITECTURE
   ↓
CURRENT SYSTEM
   ↓
IMPLEMENTATION
   ↓
TEST
   ↓
DOCUMENTATION
   ↓
TRACKER
```

For operational work:

```text
IMPLEMENTATION
   ↓
OPERATIONS
   ↓
VERIFICATION
```

For significant architecture changes:

```text
ARCHITECTURE
   ↓
ADR
```

---

# 85. Final Development Principles

BookQu development follows these permanent principles:

```text
1. Understand before changing.
2. Search before creating.
3. Requirements before implementation.
4. Architecture before abstraction.
5. Existing code is evidence, not automatically the target.
6. Critical business rules must have a clear owner.
7. Tenant isolation is mandatory.
8. Booking correctness is critical.
9. Payment state must be verified.
10. External integrations must be isolated appropriately.
11. Client-side logic is not a security boundary.
12. Tests are part of implementation, not an afterthought.
13. Refactor incrementally.
14. Do not hide product changes inside technical tasks.
15. Keep documentation synchronized.
16. Keep scope controlled.
17. Prefer evidence over assumption.
18. Protect data integrity over convenience.
19. Make important decisions traceable.
20. Leave the repository clearer than you found it.
```

---

# 86. Golden Rule

> **Understand first. Change second. Verify third. Document fourth.**

BookQu development is successful when the resulting code, tests, documentation, and operational behavior all describe the same system.
