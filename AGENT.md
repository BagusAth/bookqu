# BookQu — AI Agent Instructions

> **Document Status:** Active
> **Authority:** Primary AI Agent Instruction
> **Repository:** BookQu
> **Last Updated:** 2026-09-26
>
> This file defines how AI agents must operate when analyzing, modifying, testing, or documenting the BookQu repository.
>
> This file does not replace product, requirements, architecture, or development documentation. It defines how an AI agent must use those documents.

---

# 1. Mission

BookQu is a multi-tenant booking and reservation management platform.

The purpose of an AI agent working on this repository is to:

```text
Understand the current product
        ↓
Understand the requirements
        ↓
Understand the architecture
        ↓
Understand the existing implementation
        ↓
Make the smallest correct change
        ↓
Verify the result
        ↓
Keep documentation and implementation aligned
```

The agent must optimize for:

```text
Correctness
Security
Maintainability
Consistency
Testability
Traceability
```

Do not optimize only for speed of implementation.

---

# 2. Mandatory Documentation Reading Order

Before making a non-trivial change, read the relevant documentation in this order:

```text
1. AGENTS.md
2. docs/PRODUCT.md
3. docs/REQUIREMENTS.md
4. docs/ARCHITECTURE.md
5. docs/DEVELOPMENT.md
6. docs/TRACKER.md
```

For architectural decisions, also inspect:

```text
docs/adr/
```

Historical documentation under:

```text
docs/archive/
```

is reference-only and must not be treated as current authority.

---

# 3. Documentation Authority

Use this hierarchy:

```text
PRODUCT.md
    ↓
Defines what BookQu is

REQUIREMENTS.md
    ↓
Defines what BookQu must do

ARCHITECTURE.md
    ↓
Defines how BookQu should be built

DEVELOPMENT.md
    ↓
Defines how work should be performed

TRACKER.md
    ↓
Defines current implementation status

Source Code
    ↓
Represents current implementation

Tests
    ↓
Provide evidence of verified behavior

docs/archive/
    ↓
Historical reference only
```

When two sources disagree, do not silently choose one.

Determine whether the conflict is:

```text
Product conflict
Requirement conflict
Architecture conflict
Implementation drift
Test drift
Documentation drift
Legacy behavior
```

Then handle the conflict explicitly.

---

# 4. Core Rule

> **Code must implement the product. Code must not silently redefine the product.**

An agent must not introduce new product behavior merely because:

* it seems useful;
* it is technically easy;
* a UI component could support it;
* another application commonly has it;
* the agent considers it a "best practice";
* the existing code already contains an unfinished version.

A new product capability requires an explicit requirement.

---

# 5. Existing Code Is Not Automatically the Standard

The repository contains legacy implementation.

Some existing code may:

* be correct;
* be partially correct;
* be outdated;
* be duplicated;
* violate the target architecture;
* exist for backward compatibility;
* represent an older product concept.

Therefore:

> **Existing code is evidence of current implementation, not automatically the model for new code.**

When adding new code:

```text
Follow target architecture
+
Reuse valid existing patterns
+
Do not reproduce known legacy anti-patterns
```

---

# 6. Product Scope Rule

Before implementing a new feature, determine:

```text
Does this capability already exist in PRODUCT.md?
Does a requirement exist in REQUIREMENTS.md?
```

If yes:

```text
Proceed according to the existing requirement.
```

If no:

```text
Treat it as a possible product change.
Do not silently implement it as normal feature work.
```

---

# 7. Requirement Identification

Every meaningful behavior change must be associated with a requirement ID.

Examples:

```text
FR-BOOKING-005
FR-SCHEDULE-009
FR-PAYMENT-007
FR-SUB-006
```

Before changing business behavior:

```text
Identify requirement
        ↓
Read its context
        ↓
Check related requirements
        ↓
Inspect implementation
        ↓
Implement
```

If no appropriate requirement exists, stop treating the task as an ordinary implementation task.

---

# 8. Smallest Correct Change

Prefer the smallest change that satisfies:

```text
Requirement
+
Security
+
Architecture
+
Testing
```

Do not make broad unrelated changes.

Avoid:

```text
Feature task
+
UI redesign
+
database rename
+
large controller rewrite
+
unrelated cleanup
```

unless those changes are actually required to complete the task safely.

---

# 9. Investigate Before Editing

Before modifying code, inspect:

```text
Relevant routes
Relevant controllers
Relevant requests
Relevant models
Relevant services/actions
Relevant middleware
Relevant views/components
Relevant migrations
Relevant tests
```

Search the repository before creating:

```text
new controller
new service
new action
new helper
new component
new query
new model method
```

The goal is to avoid creating duplicate solutions.

---

# 10. Domain Terminology

Use the canonical terminology from `docs/PRODUCT.md`.

Core terms:

```text
Tenant
Owner
Customer
Service
Schedule
Booking
Payment
Plan
Subscription
Review
Voucher
Staff
Resource
Additional Item
```

Do not introduce new domain concepts that merely duplicate these.

For example:

```text
Service
```

is the canonical product term.

Do not introduce `Program` as a separate domain concept unless a new requirement explicitly defines a different concept.

---

# 11. Legacy Terminology

The current codebase may contain legacy terminology such as:

```text
Program
namalayanan
idlayanan
namabisnis
idtenant
```

Do not perform broad terminology migrations automatically.

When working in an affected area:

```text
Understand compatibility impact
+
Preserve behavior
+
Move new code toward canonical terminology where practical
```

Large naming migrations require a separate refactoring task.

---

# 12. Tenant Isolation Is Mandatory

BookQu is multi-tenant.

Tenant isolation is a security boundary.

Every tenant-scoped operation must be evaluated for:

```text
Tenant resolution
Tenant authorization
Tenant query scope
Tenant ownership
Cross-tenant access
Cross-tenant mutation
```

The agent must never assume that a user-provided tenant ID is sufficient authorization.

---

# 13. Tenant Security Rule

Never do this conceptually:

```php
Tenant::find($request->tenant_id);
```

and assume the result is authorized.

The agent must establish tenant authorization through the application's existing tenant context and authorization mechanisms.

---

# 14. Tenant Scope Rule

When working with tenant-owned models:

Prefer the established tenant-scoping architecture.

Use:

```text
TenantContext
TenantMiddleware
TenantScope
BelongsToTenant
ResolvesOwnerTenant
```

where applicable.

Avoid bypassing tenant scopes unless there is a documented reason.

---

# 15. Global Scope Bypass

Any intentional use of:

```text
withoutGlobalScopes()
withoutGlobalScope()
```

must have a clear reason.

When bypassing tenant scope:

```text
1. Explain why the bypass is needed.
2. Explicitly verify authorization.
3. Restrict the query to the intended tenant/data.
4. Add or verify a regression test where security-relevant.
```

Never use a scope bypass as a shortcut to make a query work.

---

# 16. Authentication and Authorization

The agent must never treat UI visibility as authorization.

These are not security mechanisms:

```text
hidden button
hidden menu
disabled input
frontend condition
Alpine state
JavaScript check
```

Authorization must happen server-side.

---

# 17. User-Provided IDs Are Untrusted

Treat the following as untrusted input:

```text
tenant_id
booking_id
service_id
schedule_id
payment_id
customer_id
staff_id
resource_id
voucher_id
subscription_id
```

Always verify:

```text
existence
ownership
tenant association
authorization
business eligibility
```

before performing a protected operation.

---

# 18. Booking Is a Critical Domain

Booking is the central operational domain of BookQu.

Any booking-related change must consider:

```text
Service
Schedule
Availability
Customer
Tenant
Booking State
Payment State
Concurrency
Cancellation
Rescheduling
Multi-slot behavior
```

Do not implement booking changes as isolated CRUD operations without checking the surrounding domain behavior.

---

# 19. Booking Availability

A client-side or cached availability result is never the final authority for creating a booking.

Before creating or changing a reservation:

```text
Validate availability server-side
+
Validate tenant/service/schedule relationship
+
Apply booking business rules
+
Protect against concurrency
```

---

# 20. Double Booking

Double booking is a critical invariant.

Any change affecting:

```text
schedule
availability
booking creation
booking cancellation
booking payment
booking reschedule
```

must consider its effect on double-booking protection.

Do not weaken database/application protections merely to simplify a feature.

---

# 21. Booking State

Do not arbitrarily assign booking status.

Current conceptual booking states include:

```text
pending
paid
cancelled
completed
```

Before changing booking status:

```text
Check valid state transition
Check payment implications
Check availability implications
Check notification implications
Check related booking behavior
```

---

# 22. Payment Is a Separate Domain

Do not treat:

```text
Booking
=
Payment
```

They are separate concepts.

Payment changes must consider:

```text
Payment state
Booking state
External provider state
Webhook behavior
Idempotency
Failure
Expiration
Cancellation
```

---

# 23. Payment Provider Rule

Midtrans is an external integration.

Do not spread Midtrans SDK calls throughout unrelated controllers.

Prefer:

```text
Application operation
        ↓
Payment service/interface
        ↓
Midtrans implementation
```

A payment-provider-specific implementation detail must not redefine BookQu's payment domain.

---

# 24. Webhook Security

A payment webhook is untrusted until verified.

Never mark a payment successful merely because:

```text
the browser returned
the frontend reported success
a client request claimed success
```

The system must verify the trusted payment-provider information.

Repeated webhook delivery must be handled idempotently.

---

# 25. Subscription Rule

Subscription state controls platform capability access where applicable.

When changing subscription behavior, check:

```text
Plan
Subscription
Trial
Status
Entitlement
Feature restriction
Usage limit
Payment
Expiration
```

Do not implement subscription logic independently in multiple controllers.

---

# 26. Controller Rule

Controllers must remain focused on HTTP responsibilities.

Preferred flow:

```text
Request
  ↓
Validation / Authorization
  ↓
Action / Application Service
  ↓
Response
```

Avoid putting large business workflows directly inside controllers.

---

# 27. Large Controller Rule

When modifying a controller that already contains substantial business logic:

Do not automatically add more logic to the same method.

Ask:

```text
Can this operation be extracted?
Does this represent a distinct use case?
Can the logic be tested independently?
Is this business logic or HTTP logic?
```

If the existing code is heavily legacy, incremental extraction is preferred.

---

# 28. Action Rule

Use Actions for meaningful application operations.

Good examples:

```text
CreateBooking
CreateWalkInBooking
CancelBooking
RescheduleBooking
UpdateBookingStatus
CreateService
CreateSchedule
BulkCreateSchedules
ProcessBookingPayment
ProcessSubscriptionPayment
SubmitReview
ApplyVoucher
```

Do not create a generic action that owns unrelated business operations.

---

# 29. Service Rule

Use services for coherent reusable operations, particularly when:

* logic crosses multiple models;
* an external integration is involved;
* the logic is reused;
* independent testing is useful.

Do not create generic containers such as:

```text
GeneralService
CommonService
UtilityService
ManagerService
```

without a clear responsibility.

---

# 30. Repository Rule

Repositories are optional.

Do not create repositories automatically for every model.

Use Eloquent directly when it is sufficient.

Create a repository only when there is a meaningful persistence abstraction need.

---

# 31. Model Rule

Models should primarily contain:

```text
relationships
casts
scopes
simple entity behavior
persistence-related behavior
```

Do not turn a model into a complete application workflow.

---

# 32. Blade Rule

Blade is presentation.

Blade must not contain authoritative business logic.

Never perform:

```text
database queries
booking mutation
payment mutation
authorization decisions
complex business calculations
```

inside Blade.

---

# 33. JavaScript Rule

JavaScript and Alpine.js are for interaction and presentation.

They may handle:

```text
modal state
tabs
dropdowns
local interaction
UI filtering
previews
```

They must not be the authoritative source for:

```text
booking availability
payment status
authorization
tenant identity
subscription entitlement
```

---

# 34. Validation Rule

Client-side validation is for UX.

Server-side validation is mandatory.

Important business rules must also be enforced in the appropriate application/domain layer.

Think in layers:

```text
Frontend validation
        ↓
Request validation
        ↓
Business rule validation
        ↓
Database constraints
```

---

# 35. Database Rules

Every schema change must use a migration.

Do not modify an already-applied migration simply because it is convenient.

Before modifying a database field:

```text
Search all references.
Inspect model.
Inspect relationships.
Inspect tests.
Inspect seeders.
Inspect views.
Inspect queries.
```

---

# 36. Database Integrity

Important invariants should be protected at the database level when practical.

Examples:

```text
unique identifiers
foreign keys
unique schedule constraints
indexes
non-null requirements
```

Application checks alone are not always sufficient for concurrency-sensitive operations.

---

# 37. Transactions

Use database transactions when an operation modifies multiple related records and partial completion would produce invalid state.

Examples:

```text
multi-slot booking
payment success synchronization
complex booking cancellation
subscription payment processing
```

Do not wrap every query in a transaction unnecessarily.

---

# 38. Cache Rule

Cache is an optimization, not the source of truth.

For booking-related state:

```text
Database / authoritative domain state
        ↓
Cache
```

not:

```text
Cache
        ↓
authoritative booking state
```

Any cache used for availability must have explicit invalidation behavior.

---

# 39. UI Component Rule

Before creating a new UI component:

```text
Search existing components.
```

If a component already exists and fits:

```text
Reuse it.
```

If the component is nearly reusable:

```text
Refactor it carefully.
```

Do not create many visually identical components with slightly different names.

---

# 40. View Decomposition Rule

When modifying a large Blade file:

```text
Identify repeated UI
        ↓
Extract reusable component
        ↓
Keep page orchestration in the page
```

Do not perform a giant visual rewrite unless the task requires it.

---

# 41. Naming Rule

New PHP classes:

```text
PascalCase
```

New methods and variables:

```text
camelCase
```

New database fields:

```text
snake_case
```

New route names:

```text
domain.resource.action
```

Use the canonical terminology from `PRODUCT.md`.

---

# 42. Legacy Naming Rule

Do not create additional legacy-style naming.

Do not introduce new variables such as:

```text
$namabisnis
$tanggalbooking
$jumlahbooking
```

unless required for compatibility with existing code.

Prefer:

```text
$businessName
$bookingDate
$bookingCount
```

for new code.

---

# 43. Route Rule

Routes should remain declarative.

A route should primarily define:

```text
HTTP method
URI
Controller
Middleware
Name
```

Do not put complex application logic into route closures.

---

# 44. Test Rule

Every meaningful behavior change should have appropriate verification.

At minimum, consider:

```text
happy path
validation failure
authorization failure
tenant isolation
edge case
state transition
concurrency
```

Not every task requires every category, but critical domains require broader coverage.

---

# 45. Test Preservation Rule

When a test fails after a code change:

Do not immediately change the test.

First determine:

```text
Was the implementation wrong?
Was the requirement changed?
Is the test obsolete?
Is the test asserting legacy behavior?
```

A failing test is evidence of a discrepancy.

---

# 46. Test Naming

Prefer behavior-oriented tests:

```text
test_owner_cannot_access_other_tenant_bookings
test_customer_cannot_book_unavailable_schedule
test_duplicate_payment_webhook_does_not_duplicate_state
```

Avoid vague tests such as:

```text
test_booking
test_feature
test_function
```

---

# 47. Refactoring Rule

Refactoring should preserve intended product behavior.

Before significant refactoring:

```text
Read requirement
Inspect current behavior
Inspect tests
Identify target architecture
Refactor incrementally
Run tests
```

Do not combine a major architecture rewrite with unrelated product changes unless explicitly required.

---

# 48. Characterization Test Rule

When legacy behavior is poorly tested, add characterization tests before refactoring critical behavior.

The goal is to capture:

> What the existing system currently does.

Then compare that behavior against:

> What the system is supposed to do.

---

# 49. Product Change Rule

If a requested change modifies user-visible or business behavior:

```text
Do not silently implement it.
```

Instead:

```text
Identify product impact
        ↓
Update PRODUCT.md if necessary
        ↓
Update REQUIREMENTS.md
        ↓
Update ARCHITECTURE.md if necessary
        ↓
Update TRACKER.md
        ↓
Implement
        ↓
Test
```

---

# 50. Architecture Change Rule

If a change introduces or modifies:

```text
domain boundaries
application layers
tenant strategy
payment boundary
database strategy
external integration
authentication architecture
```

evaluate whether an ADR is required.

ADR directory:

```text
docs/adr/
```

---

# 51. Scope Discipline

Do not expand the task because an unrelated improvement is discovered.

Example:

```text
Requested:
Fix reschedule validation.

Discovered:
Dashboard UI is outdated.
```

Do not automatically redesign the dashboard.

Record the unrelated improvement separately.

---

# 52. Opportunistic Refactoring

Small related refactoring is allowed when it directly reduces risk or duplication for the current task.

Example:

```text
Current task:
Fix booking cancellation.

Relevant improvement:
Extract duplicated cancellation rule.
```

This is acceptable.

Unrelated architecture rewrites are not.

---

# 53. Minimal Diff Principle

Prefer the smallest coherent change.

However:

> Minimal change does not mean preserving obviously dangerous or incorrect behavior merely to keep the diff small.

Security and data-integrity problems override minimal-diff preference.

---

# 54. Security Priority

When choosing between alternatives, prioritize:

```text
1. Security
2. Data Integrity
3. Business Correctness
4. Maintainability
5. Performance
6. Convenience
```

Never sacrifice tenant isolation or payment correctness for implementation convenience.

---

# 55. Performance Rule

Do not add optimization without understanding the bottleneck.

Preferred workflow:

```text
Measure
  ↓
Find bottleneck
  ↓
Optimize
  ↓
Measure again
```

Do not add caching, queues, abstractions, or infrastructure solely because they sound scalable.

---

# 56. Search Before Create Rule

Before creating a class or feature implementation:

Search for:

```text
existing controller
existing method
existing query
existing service
existing action
existing component
existing test
existing route
existing model relationship
```

The repository is large enough that blind creation can easily produce duplicate functionality.

---

# 57. Do Not Duplicate Domain Rules

A business rule must have an identifiable source of truth.

Examples:

```text
booking availability
voucher eligibility
booking state transitions
subscription entitlement
tenant authorization
```

Do not implement the same rule independently across multiple controllers, views, and JavaScript files.

---

# 58. Legacy Compatibility

When refactoring existing behavior, consider:

```text
route compatibility
database compatibility
existing links
existing tests
seeders
user-facing URLs
```

Do not break public booking URLs or customer management links casually.

---

# 59. External Integration Safety

Treat external systems as unreliable.

Examples:

```text
Midtrans
Email
Storage
Future WhatsApp
Future Google Calendar
```

External requests may:

```text
fail
timeout
retry
return duplicates
return partial information
```

Application logic must handle these conditions appropriately.

---

# 60. Error Handling

Do not expose internal implementation details to end users.

User-facing errors should be understandable.

Internal logs may contain technical context, but must not expose:

```text
passwords
API secrets
payment credentials
private tokens
```

---

# 61. Logging

Log important operational events where appropriate.

Useful context may include:

```text
tenant
user
booking
payment
event
timestamp
failure reason
```

Avoid logging sensitive credentials or security tokens.

---

# 62. File Uploads

When handling uploaded assets:

```text
Validate file
Validate size
Validate type
Store through configured storage
Associate with tenant
Do not trust client path
```

Do not make file path visibility equal authorization.

---

# 63. Background Jobs

Use queued jobs when an operation:

* is slow;
* can safely run asynchronously;
* does not need to block the request;
* can be retried.

Do not move critical booking state changes into asynchronous processing if doing so could create temporary invalid booking state.

---

# 64. When the Agent Should Stop and Ask for Clarification

An agent should request clarification when:

```text
Two valid product interpretations exist
AND
the repository does not provide enough evidence
AND
choosing one would materially change product behavior.
```

Do not ask for clarification merely because implementation is difficult.

If the intended behavior can be established from the existing documentation and code, proceed.

---

# 65. When the Agent Should Not Ask for Clarification

Do not ask unnecessarily when:

* the requirement is explicit;
* the architecture already defines the pattern;
* the task is a straightforward bug fix;
* existing code establishes the behavior;
* the necessary implementation detail can be safely inferred from established project conventions.

Make a reasonable, documented implementation decision.

---

# 66. Implementation Plan Format

For non-trivial tasks, the agent should formulate an internal plan using:

```text
Requirement:
<requirement ID>

Domain:
<domain>

Current Implementation:
<relevant files>

Target Change:
<what will change>

Architecture:
<where the change belongs>

Tests:
<tests to add/update>

Documentation:
<documents affected>
```

The plan should remain proportional to the task.

---

# 67. Completion Report Format

After a non-trivial task, the agent should summarize:

```text
Implemented:
<what changed>

Requirement:
<requirement IDs>

Files:
<important changed files>

Tests:
<tests run>

Verification:
<what was verified>

Notes:
<any limitations or follow-up>
```

---

# 68. Tracker Rule

After a meaningful implementation task, update `docs/TRACKER.md`.

At minimum record:

```text
Requirement
Status
Test status
Architecture status
Important notes
```

Do not leave the tracker permanently behind the implementation.

---

# 69. Documentation Change Rule

Update documentation when a change affects:

```text
Product behavior
Requirements
Business rules
Architecture
Canonical terminology
Development workflow
```

Do not update documentation for every minor code edit.

The goal is meaningful synchronization, not documentation noise.

---

# 70. Architecture Decision Rule

Create an ADR when a decision:

* affects multiple modules;
* introduces a new architectural pattern;
* changes a major integration boundary;
* establishes a long-term technical constraint;
* would otherwise force future contributors to guess why the design exists.

---

# 71. Never Use Historical Documentation as Current Authority

Files under:

```text
docs/archive/
```

may explain why BookQu previously behaved differently.

They must not be used as the current implementation target when they conflict with:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
```

---

# 72. Never Use the Tracker as a Product Definition

`TRACKER.md` tells the agent:

```text
what has been implemented
```

It does not tell the agent:

```text
what the product should become
```

Product decisions belong in:

```text
PRODUCT.md
REQUIREMENTS.md
```

---

# 73. Never Use Existing UI as the Requirement

A button or page existing in the code does not automatically mean the feature is part of the permanent product.

The agent must distinguish:

```text
Implemented
```

from:

```text
Required
```

---

# 74. Feature Completion vs Architecture Completion

A feature can be:

```text
Functional Status:
Done

Architecture Status:
Needs Refactor
```

Do not artificially rewrite working behavior simply to make the tracker say:

```text
Everything Done
```

Technical debt must remain visible.

---

# 75. Current BookQu Refactoring Priorities

When multiple technical problems exist, prioritize approximately:

```text
1. Tenant / security integrity
2. Booking domain
3. Payment domain
4. Schedule / availability
5. Owner controller decomposition
6. Large Blade decomposition
7. Subscription entitlement
8. Terminology normalization
9. Route cleanup
10. General cleanup
```

This order can change if a security or production issue requires immediate action.

---

# 76. No Uncontrolled Rewrites

Do not rewrite the application from scratch.

Do not replace the entire architecture in one task unless explicitly requested and sufficiently justified.

Preferred strategy:

```text
Understand
→ Characterize
→ Extract
→ Test
→ Replace
→ Verify
```

---

# 77. New Feature Implementation Standard

For a new approved feature:

```text
1. Requirement exists
2. Domain identified
3. Existing implementation inspected
4. Architecture boundary identified
5. Request validation defined
6. Application operation defined
7. Persistence impact identified
8. Authorization considered
9. Tenant isolation considered
10. Tests defined
11. Implementation completed
12. Tests passed
13. Tracker updated
```

---

# 78. Bug Fix Standard

For a bug:

```text
1. Identify affected requirement
2. Reproduce behavior
3. Identify root cause
4. Write/update regression test
5. Implement smallest correct fix
6. Run relevant tests
7. Check related flows
8. Update tracker
```

---

# 79. Refactor Standard

For refactoring:

```text
1. Identify target architecture
2. Identify preserved behavior
3. Check test coverage
4. Add characterization tests if necessary
5. Refactor incrementally
6. Run tests after each major step
7. Check security boundaries
8. Update architecture documentation if needed
9. Update tracker
```

---

# 80. Product Change Standard

For product changes:

```text
1. Identify affected product concept
2. Decide whether the change is accepted
3. Update PRODUCT.md
4. Update REQUIREMENTS.md
5. Update ARCHITECTURE.md if necessary
6. Update TRACKER.md
7. Implement
8. Test
9. Verify UI and business behavior
```

---

# 81. Definition of Done

An AI agent must not declare a task complete merely because code was written.

A task is complete only when:

```text
[ ] Requirement understood
[ ] Scope respected
[ ] Architecture respected
[ ] Implementation completed
[ ] Validation completed
[ ] Authorization checked
[ ] Tenant isolation checked where applicable
[ ] Relevant tests added/updated
[ ] Tests pass
[ ] UI verified where applicable
[ ] No unintended behavior introduced
[ ] Documentation updated if required
[ ] Tracker updated
```

---

# 82. Final Agent Principles

Always follow these principles:

```text
1. Read before changing.
2. Search before creating.
3. Requirements before implementation.
4. Architecture before abstraction.
5. Security before convenience.
6. Tenant isolation is mandatory.
7. Booking correctness is critical.
8. Payment must be verified.
9. Tests are evidence.
10. Existing code is not automatically the target architecture.
11. Do not silently expand scope.
12. Do not duplicate business rules.
13. Refactor incrementally.
14. Keep documentation synchronized.
15. Make changes traceable.
```

---

# 83. Golden Rule for AI Agents

> **Understand first. Change second. Verify third. Document fourth.**

An AI agent must never use source code generation as a substitute for understanding the BookQu product and architecture.

---

# 84. Final Development Chain

Every meaningful BookQu change should fit into this chain:

```text
PRODUCT
   ↓
REQUIREMENT
   ↓
DOMAIN
   ↓
ARCHITECTURE
   ↓
IMPLEMENTATION
   ↓
TEST
   ↓
TRACKER
```

When a link is missing, identify the missing link before continuing.

---

# 85. Repository Documentation Map

The active documentation set is:

```text
AGENTS.md
    ↓
docs/PRODUCT.md
    ↓
docs/REQUIREMENTS.md
    ↓
docs/ARCHITECTURE.md
    ↓
docs/DEVELOPMENT.md
    ↓
docs/TRACKER.md
```

Architecture decisions:

```text
docs/adr/
```

Historical documents:

```text
docs/archive/
```

All AI agents working on BookQu must respect this documentation structure.
