# BookQu Architecture Specification

> **Document Status:** Active Architectural Specification
> **Version:** 1.1
> **Authority:** Architectural direction and stable structural rules
> **Related Product Definition:** `docs/2-PRODUCT.md`
> **Related Requirements:** `docs/3-REQUIREMENT.md`
> **Related System Design:** `docs/7-SYSTEM-DESIGN.md`
> **Development Workflow:** `docs/5-DEVELOPMENT.md`
> **Architectural Decisions:** `docs/adr/`
> **Last Updated:** 2026-09-30
>
> This document defines how BookQu should be structured, how responsibilities should be separated, and which architectural rules must be preserved as the system evolves.
>
> It is not a historical refactor record and does not define every detail of the current implementation.

---

# 1. Purpose

The purpose of this document is to establish a stable architectural foundation for BookQu.

The architecture must allow BookQu to:

* preserve correct business behavior;
* maintain strong tenant isolation;
* prevent booking and payment inconsistencies;
* keep HTTP concerns separate from business logic;
* keep important business operations explicit;
* isolate external integrations;
* support automated testing;
* evolve without repeatedly restructuring the entire application;
* allow future features to be added without unnecessary coupling;
* make the system understandable to developers and AI agents.

The architecture should optimize for:

```text
Correctness
Maintainability
Testability
Security
Tenant Isolation
Modularity
Understandability
Extensibility
```

Performance and scalability remain important, but abstraction must have a clear responsibility and should not be introduced merely for stylistic reasons.

---

# 2. Architectural Scope

This document defines architectural rules for:

```text
Application Structure
Domain Boundaries
Layer Responsibilities
Request Handling
Application Actions
Domain Logic
Infrastructure
Persistence
Tenant Isolation
Authorization
Booking
Payment
Scheduling
Subscription
Notifications
Testing
External Integrations
Future Extension
```

This document does not define:

```text
Product Scope
    → docs/2-PRODUCT.md

Required Behavior
    → docs/3-REQUIREMENT.md

Detailed Current Implementation
    → docs/7-SYSTEM-DESIGN.md

Development Workflow
    → docs/5-DEVELOPMENT.md

Operational Procedures
    → docs/8-OPERATIONS.md

Project Status
    → docs/6-TRACKER.md

Architectural Decision Rationale
    → docs/adr/
```

---

# 3. Architectural Principles

## 3.1 Requirement-Driven Architecture

Architecture exists to support accepted product requirements.

The architecture must not introduce product behavior merely because a particular technical design makes it possible.

The relationship is:

```text
Product
    ↓
Requirement
    ↓
Architecture
    ↓
Implementation
    ↓
Tests
```

---

## 3.2 Clear Responsibility

Every component should have a clearly defined primary responsibility.

A component should not become a dumping ground for unrelated behavior.

When a class performs multiple unrelated responsibilities, the design should be reconsidered.

---

## 3.3 Domain-Oriented Organization

The application should be understood in terms of meaningful BookQu domains.

Important domains include:

```text
Tenant
Authentication
Service
Schedule
Booking
Customer
Payment
Subscription
Review
Voucher
Staff
Resource
Notification
Analytics
```

Not every domain requires an independent architectural layer or package.

The purpose of domain-oriented organization is to make responsibilities and dependencies understandable.

---

## 3.4 Thin HTTP Layer

HTTP controllers should primarily coordinate:

```text
Request
    ↓
Validation / Authorization
    ↓
Application Operation
    ↓
Response
```

Controllers should not become the primary location of complex business rules.

Business rules involving booking, payment, availability, subscription, or other critical domains should be delegated to the appropriate application/domain boundary.

---

## 3.5 Explicit Application Operations

Important business operations should be explicit.

Examples include:

```text
CreateBooking
CreateWalkInBooking
CancelBooking
RescheduleBooking
CreateSchedule
ProcessBookingPayment
ExpirePayment
CreateSubscription
ProcessRefund
```

An application operation should represent a meaningful action rather than exist merely to satisfy a folder structure.

---

## 3.6 Domain Rules Must Be Centralized

Rules that determine the correctness of a domain operation should not be duplicated across controllers, views, commands, and unrelated services.

Examples include:

```text
Booking availability
Occupied-slot determination
Booking state transitions
Tenant ownership
Payment state handling
Subscription entitlement
Customer token authorization
```

A rule that affects correctness should have a clear authoritative implementation.

---

## 3.7 Infrastructure Must Remain Replaceable

External systems should not become deeply embedded in core business logic.

Examples include:

```text
Midtrans
Email delivery
External messaging
Storage providers
Caching infrastructure
Other external APIs
```

The application should depend on clear internal responsibilities rather than scattering provider-specific logic throughout business operations.

---

## 3.8 Tenant Isolation Is a First-Class Boundary

Tenant isolation is not optional.

Every tenant-scoped operation must execute within the correct tenant context.

A feature is architecturally incomplete if it works functionally but can violate tenant boundaries.

---

## 3.9 Transactional Integrity Over Convenience

Critical business operations must prioritize consistency over implementation convenience.

This is particularly important for:

```text
Booking
Availability
Payment
Refund
Subscription
```

A simpler implementation must not be chosen if it can create inconsistent business state.

---

## 3.10 Tests Must Follow Business Risk

Testing effort should be strongest around operations where incorrect behavior can damage:

```text
Bookings
Availability
Payments
Refunds
Tenant Isolation
Authorization
Subscription Entitlement
```

The architecture should make these areas testable without requiring unnecessarily large end-to-end flows.

---

# 4. Architectural Context

BookQu is a Laravel-based multi-tenant web application.

The current technology baseline is:

```text
Backend
PHP 8.3+
Laravel 13

Frontend
Blade
Alpine.js
Tailwind CSS

Build
Vite

Database
MySQL / compatible relational database

Payment
Midtrans

Testing
Laravel/PHPUnit test suite
```

The architecture should remain compatible with this technology baseline while avoiding unnecessary coupling to individual framework implementation details.

---

# 5. Architectural Model

The conceptual BookQu architecture is:

```text
                 Presentation
                      │
                      ▼
             HTTP / UI Interface
                      │
                      ▼
                Application
                      │
          ┌───────────┴───────────┐
          ▼                       ▼
       Domain              Supporting Services
          │                       │
          └───────────┬───────────┘
                      ▼
               Infrastructure
                      │
          ┌───────────┼───────────┐
          ▼           ▼           ▼
       Database    Payments    External Systems
```

The exact implementation may contain additional framework-level components.

The architectural responsibilities should remain understandable even when individual files move.

---

# 6. Architectural Layers

## 6.1 Presentation Layer

The Presentation layer is responsible for interaction with users and external requests.

Typical responsibilities include:

```text
HTTP Controllers
Form Requests
Middleware
Blade Views
UI Components
Authentication Entry Points
Webhook Entry Points
```

Presentation code should:

* receive input;
* invoke the appropriate application operation;
* return a response;
* handle presentation-specific concerns.

Presentation code should not contain large amounts of reusable business logic.

---

## 6.2 Application Layer

The Application layer coordinates business operations.

Typical responsibilities include:

```text
Create Booking
Cancel Booking
Reschedule Booking
Create Walk-In Booking
Create Schedule
Process Payment
Process Refund
Expire Payment
Manage Subscription
```

Application operations may:

* validate application preconditions;
* coordinate multiple domain concepts;
* open transactions;
* acquire required locks;
* invoke domain rules;
* persist changes;
* trigger appropriate side effects.

The Application layer is responsible for orchestration.

It should not become a replacement for the Domain layer.

---

## 6.3 Domain Layer

The Domain layer contains business concepts and rules that should remain independent from HTTP presentation.

Important domain concepts include:

```text
Booking
Schedule
Payment
Availability
Subscription
Tenant
```

Domain responsibilities include:

* business invariants;
* state transitions;
* domain rules;
* reusable business calculations;
* domain-specific validation.

Domain code should not depend unnecessarily on HTTP request details or presentation concerns.

---

## 6.4 Infrastructure Layer

Infrastructure provides implementations for external or technical concerns.

Examples include:

```text
Payment Providers
External APIs
Storage
Framework Integration
Infrastructure-specific Services
```

Infrastructure may depend on external providers.

Core business rules should not depend directly on provider-specific implementation details where a stable boundary is appropriate.

---

## 6.5 Persistence

Persistence is responsible for storing and retrieving system state.

BookQu uses a relational database.

Persistence concerns include:

```text
Models
Relationships
Migrations
Indexes
Constraints
Transactions
Queries
```

Database constraints should be used where they provide a meaningful final integrity boundary.

Application logic must not assume that application-level validation alone is sufficient for critical concurrency rules.

---

# 7. Current Structural Organization

The current architecture follows a domain-oriented Laravel structure similar to:

```text
app/
├── Actions/
│   ├── Booking/
│   ├── Payment/
│   └── Schedule/
│
├── Domain/
│   ├── Booking/
│   ├── Payment/
│   └── Schedule/
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Auth/
│   │   ├── Customer/
│   │   ├── Owner/
│   │   └── Webhook/
│   │
│   ├── Middleware/
│   │
│   └── Requests/
│       ├── Booking/
│       ├── Customer/
│       ├── Owner/
│       └── Schedule/
│
├── Infrastructure/
│   └── Midtrans/
│
├── Models/
├── Policies/
├── Services/
├── Support/
├── Traits/
├── Mail/
└── Notifications/
```

This structure is an implementation representation of the architectural responsibilities described by this document.

The exact directory layout may evolve.

Future changes should preserve the underlying responsibilities even if files move between directories.

Detailed current mappings belong in `docs/7-SYSTEM-DESIGN.md`.

---

# 8. HTTP and Controller Rules

Controllers should remain focused on HTTP concerns.

A controller should generally perform:

```text
Receive Request
      ↓
Authorize / Validate
      ↓
Invoke Application Operation
      ↓
Prepare Response
```

Controllers should avoid directly implementing complex rules for:

```text
Availability
Double-Booking
Payment State
Refund Logic
Subscription Entitlement
Tenant Isolation
Complex Booking Transitions
```

These concerns belong in appropriate application/domain boundaries.

A controller may coordinate multiple operations when the use case genuinely requires it.

---

# 9. Form Request Rules

Form Requests should be used for request-level validation and authorization concerns where appropriate.

They may validate:

```text
Required fields
Format
Input type
Basic constraints
Request authorization
```

They should not become the authoritative location for reusable domain business rules.

For example:

```text
"email is required"
```

is request validation.

Whereas:

```text
"this schedule cannot be booked because it is already occupied"
```

is a domain/application rule.

---

# 10. Application Action Rules

Application Actions should represent meaningful application operations.

An Action should have:

```text
One clear purpose
Defined inputs
Defined outputs/effects
Predictable error behavior
Relevant transaction boundary
```

Good:

```text
CreateBooking
CancelBooking
RescheduleBooking
CreateWalkInBooking
ExpirePayment
```

Poor:

```text
DoBookingStuff
HandleEverything
CommonAction
UtilityAction
```

Actions should not exist solely because a controller became large.

They should represent meaningful application responsibilities.

---

# 11. Domain Service Rules

A domain service is justified when business logic:

* does not naturally belong to one entity;
* requires coordination of domain concepts;
* represents a reusable domain operation.

Do not create a domain service merely to move arbitrary code out of a controller.

A domain service should have a clear domain responsibility.

---

# 12. Model Rules

Models represent persistent business data and its persistence relationships.

Models may contain:

* relationships;
* casts;
* persistence-oriented behavior;
* simple domain-adjacent helpers;
* query scopes where appropriate.

Models should not become large containers for unrelated application workflows.

Complex workflows should be delegated to appropriate application/domain components.

---

# 13. Policy and Authorization Rules

Authorization must be enforced at the appropriate boundary.

Policies or equivalent authorization mechanisms should protect tenant-owned resources and privileged operations.

Authorization must answer:

```text
Who is acting?
        ↓
What resource is being accessed?
        ↓
Does the actor have permission?
        ↓
Does the resource belong to the actor's authorized tenant/context?
```

A resource identifier must never be treated as proof of authorization.

---

# 14. Multi-Tenant Architecture

Tenant isolation is a mandatory architectural boundary.

The conceptual model is:

```text
Authenticated Owner
        ↓
Authorized Tenant
        ↓
Tenant-Owned Resource
```

Tenant-scoped resources include concepts such as:

```text
Services
Schedules
Bookings
Customers
Vouchers
Reviews
Assets
Staff
Resources
Subscription Data
```

A tenant identifier supplied by a client must not independently establish access rights.

The correct tenant context must come from trusted application context and authorization rules.

---

# 15. Tenant Boundary Rules

Every new tenant-scoped feature must answer:

```text
What tenant owns this resource?
How is tenant context established?
How is authorization enforced?
Can the resource be requested across tenants?
What happens to cross-tenant identifiers?
What tests prove isolation?
```

A feature without clear answers to these questions is not ready for production use.

---

# 16. Booking Architecture

Booking is a critical domain.

The booking architecture must protect:

```text
Availability
Concurrency
State Integrity
Tenant Isolation
Payment Relationship
Customer Authorization
Cancellation
Rescheduling
```

The conceptual booking flow is:

```text
Request
   ↓
Validate
   ↓
Authorize
   ↓
Resolve Tenant
   ↓
Resolve Service / Schedule
   ↓
Check Availability
   ↓
Acquire Required Locks
   ↓
Create / Modify Booking
   ↓
Persist State
   ↓
Invalidate / Update Relevant Derived Data
   ↓
Return Result
```

The detailed current implementation of this flow belongs in `docs/7-SYSTEM-DESIGN.md`.

---

# 17. Availability Architecture

Availability is a critical domain rule rather than merely a UI concept.

Availability must account for:

```text
Schedule State
Booking State
Pending Grace Period
Payment State
Cancellation
Concurrency
Tenant Context
```

The authoritative availability calculation must be consistent across:

```text
Customer Booking
Owner Booking
Rescheduling
Calendar
Schedule Management
Background Expiration
```

Different entry points must not implement conflicting definitions of "available".

---

# 18. Booking State Architecture

The current conceptual booking lifecycle is:

```text
pending
    ↓
paid
    ↓
completed
```

with:

```text
pending → cancelled
paid    → cancelled
paid    → completed
```

where supported by business rules.

Booking state must be changed through controlled transitions.

The architecture must not allow raw client input to arbitrarily set internal booking state.

---

# 19. Pending Booking Architecture

Pending bookings are treated differently from confirmed bookings.

The architecture distinguishes:

```text
Pending within grace period
        ↓
temporarily occupies schedule
```

from:

```text
Pending beyond grace period
        ↓
stale pending state
        ↓
must no longer block normal availability
```

The current pending grace period is:

```text
15 minutes
```

This rule must remain centralized.

It must not be independently reimplemented in:

```text
Controller
Blade
JavaScript
Command
Calendar
Booking Query
```

without using the authoritative booking/availability rules.

---

# 20. Stale Pending Handling

Stale pending bookings must be reconciled safely.

The architecture should support:

```text
Scheduled expiration
+
Authoritative booking transaction handling
+
Concurrency protection
```

A stale pending booking must not permanently occupy a schedule.

A new booking must not be blocked indefinitely merely because an old pending record still exists.

---

# 21. Double-Booking Protection

Double-booking prevention is a critical architectural invariant.

Protection must exist at more than one conceptual level where appropriate:

```text
Application Validation
        +
Transaction / Locking
        +
Database Integrity
```

The exact implementation may change.

The invariant must not:

> allow two conflicting confirmed reservations to occupy the same exclusive schedule.

Database constraints should be used where appropriate as a final integrity boundary.

Application validation must not be treated as sufficient by itself for high-risk concurrent operations.

---

# 22. Multi-Slot Booking Architecture

A multi-slot booking represents one reservation intent that may span multiple schedules.

The architecture should preserve:

```text
One reservation intent
        ↓
Compatible schedule set
        ↓
Coordinated availability validation
        ↓
Coordinated persistence
        ↓
Unified payment relationship where applicable
```

Partial booking must not leave the system in an invalid state.

---

# 23. Cancellation Architecture

Cancellation is a business operation rather than a direct status update.

It may involve:

```text
Authorization
Availability restoration
Booking state transition
Payment relationship
Refund processing
Notifications
Cache invalidation
```

Customer and owner cancellation flows may have different business effects.

The cancellation operation must preserve booking and financial consistency.

---

# 24. Rescheduling Architecture

Rescheduling is also a controlled business operation.

The architecture should treat rescheduling as:

```text
Authorize
   ↓
Validate Current Booking
   ↓
Determine New Schedule
   ↓
Check Availability
   ↓
Lock Relevant Resources
   ↓
Update Booking
   ↓
Persist Atomically
   ↓
Update Derived State
```

The old schedule must become available only when the transaction has safely moved the booking.

---

# 25. Payment Architecture

Payment is a separate domain from booking.

The conceptual relationship is:

```text
Booking
    │
    └── may require
           ↓
        Payment
```

but:

```text
Booking State
≠
Payment State
```

The architecture must preserve this separation.

Payment status currently supports:

```text
pending
sukses
gagal
```

Payment expiration is a business condition handled within the existing payment lifecycle rather than a separate persistent status.

---

# 26. Payment Provider Boundary

The current supported payment provider is Midtrans.

Provider-specific communication belongs behind an infrastructure boundary.

The core booking domain should not depend directly on raw Midtrans-specific details.

The architecture should make it possible to:

```text
Replace Provider
Add Provider
Mock Provider
Test Provider Failures
```

without rewriting core booking rules.

---

# 27. Payment Verification

Payment success must be based on trusted evidence.

The architecture must distinguish:

```text
Customer / Client Request
```

from:

```text
Trusted Payment Provider Result
```

A client-side claim must not by itself establish successful payment state.

Webhook or equivalent trusted verification must be validated appropriately before financial state is changed.

---

# 28. Payment Idempotency

External payment providers may repeat callbacks.

Payment processing must therefore be idempotent.

Repeated delivery of the same external event must not produce:

```text
Duplicate Payment State Changes
Duplicate Refunds
Duplicate Booking Side Effects
Duplicate Notifications
```

where those side effects are intended to occur only once.

---

# 29. Refund Architecture

Refund is separate from payment and booking state.

The conceptual model is:

```text
Booking
    ↓
Cancellation / Eligible Refund Event
    ↓
Refund
    ↓
Refund Processing
```

Refund state:

```text
pending
processed
failed
```

Refund processing must be idempotent and traceable.

---

# 30. Customer Management Token Architecture

Customer-facing booking management may operate without a normal authenticated BookQu account.

Therefore token security is an architectural boundary.

Tokenized capabilities must be scoped appropriately.

Examples include:

```text
View Booking
View Invoice
Submit Review
Cancel Booking
Reschedule Booking
```

A token valid for one capability must not automatically imply authority for another capability.

A token for one booking must never grant access to another booking.

Cross-booking and cross-tenant token use must be rejected.

---

# 31. Subscription Architecture

Subscription controls the tenant's access to BookQu capabilities.

The conceptual structure is:

```text
Plan
   ↓
Subscription
   ↓
Entitlement
   ↓
Feature Access
```

Feature access rules should have a centralized source of truth.

The architecture should prevent the same entitlement logic from being independently implemented across many controllers.

---

# 32. Notification Architecture

Notifications communicate domain events or operational state changes.

Notification generation should remain separated from the core operation that creates the underlying state.

Conceptually:

```text
Business Event
      ↓
Notification Decision
      ↓
Delivery Channel
```

Examples:

```text
Booking Created
Booking Status Changed
Payment Updated
Subscription Changed
```

A notification failure should not automatically corrupt the primary booking or payment transaction unless the product requirement explicitly requires transactional coupling.

---

# 33. Cache Architecture

Caching is a performance mechanism, not a source of business truth.

Cached data must never override authoritative booking state.

Caching may be used for:

```text
Availability
Public Business Data
Read-Heavy Views
Derived Analytics
```

where safe.

Critical booking correctness must ultimately depend on authoritative persistent state and transactional rules.

Cache keys must include all dimensions required to prevent cross-tenant or cross-service contamination.

---

# 34. Scheduler Architecture

Scheduled processing is used for background reconciliation and time-based business behavior.

Examples include:

```text
Payment Expiration
Subscription Expiration
Other Time-Based Maintenance
```

Scheduler responsibilities should invoke application operations rather than duplicating business rules in the scheduler itself.

The conceptual model is:

```text
Scheduler
   ↓
Application Command / Action
   ↓
Business Rules
   ↓
Persistent State
```

Operational execution details belong in `docs/8-OPERATIONS.md`.

---

# 35. Database Architecture

The relational database is an authoritative persistence layer.

Database design should provide:

```text
Referential Integrity
Unique Constraints
Foreign Keys
Indexes
Atomic Transactions
Critical Integrity Boundaries
```

Application code remains responsible for business behavior.

Database constraints remain responsible for integrity that must hold even under concurrent requests.

---

# 36. Transaction Boundaries

Transactions should be used whenever a business operation requires multiple changes to remain consistent.

Examples include:

```text
Create Booking
Reschedule Booking
Cancel Booking
Expire Payment
Process Refund
Subscription State Change
Multi-Slot Booking
```

A transaction should define a clear consistency boundary.

The architecture should avoid long-running transactions around unrelated external I/O where possible.

---

# 37. External Integration Rules

External integrations should be isolated from the core domain.

An integration should have clear boundaries for:

```text
Request
Response
Failure
Timeout
Retry
Idempotency
Logging
Security
```

External provider failures must not be allowed to leave internal state in an ambiguous condition.

The integration boundary should make provider-specific behavior testable.

---

# 38. Frontend Architecture

The BookQu frontend is primarily server-rendered through Blade with interactive behavior provided by Alpine.js and supporting frontend tooling.

The frontend should be organized around:

```text
Page Responsibility
Component Responsibility
Presentation State
Reusable UI
```

Blade views should not become repositories for large application workflows.

Business decisions should remain in the application/domain layers.

Frontend JavaScript may:

```text
Display State
Collect Input
Perform UI Interaction
Request Server Operations
```

but must not become the authoritative source for security-sensitive or business-critical rules.

---

# 39. UI Component Rules

Reusable UI components should be used where repeated presentation responsibility exists.

Examples include:

```text
Owner Navigation
Topbar
Alerts
Modals
Booking Components
Form Components
Public Page Components
```

A component should remain focused on presentation.

It should not silently introduce business behavior.

---

# 40. Validation Boundary

Validation exists at multiple levels.

```text
Request Validation
        ↓
Application Preconditions
        ↓
Domain Invariants
        ↓
Database Integrity
```

These layers are complementary.

Request validation cannot replace domain validation.

Domain validation cannot replace database constraints when concurrent integrity requires database enforcement.

---

# 41. Error Handling Architecture

Errors should be classified according to responsibility.

Examples:

```text
Validation Error
Authorization Error
Not Found
Business Rule Violation
Concurrency Conflict
External Provider Failure
Infrastructure Failure
Unexpected Application Error
```

Business operations should return or raise meaningful failure conditions that can be translated appropriately by the presentation layer.

Sensitive infrastructure details must not be exposed directly to users.

---

# 42. Logging Architecture

Logging should support operational investigation without becoming a substitute for structured domain state.

High-value events include:

```text
Authentication
Booking Creation
Booking Cancellation
Booking Reschedule
Payment State Change
Refund State Change
Webhook Processing
Subscription State Change
Security Failures
Unexpected Errors
```

Logs must avoid exposing secrets or unnecessary sensitive customer data.

---

# 43. Security Architecture

Security is cross-cutting.

The architecture must preserve:

```text
Authentication
Authorization
Tenant Isolation
Input Validation
CSRF Protection
XSS Protection
SQL Injection Protection
Secret Protection
Webhook Verification
IDOR Protection
Token Scope
```

Security controls should be enforced at appropriate architectural boundaries rather than relying solely on frontend behavior.

---

# 44. Testing Architecture

Testing should exist at multiple levels.

```text
Domain / Rule Tests
        ↓
Application Operation Tests
        ↓
Integration Tests
        ↓
HTTP / Feature Tests
        ↓
UI / Browser Verification where required
```

The exact distribution may change.

Critical invariants should have targeted automated tests.

Especially important are:

```text
Tenant Isolation
Booking Concurrency
Availability
Pending Grace Handling
Payment Idempotency
Refund Idempotency
Authorization
Token Scope
Subscription Entitlement
```

---

# 45. Architecture and Testing Relationship

Every architectural boundary should make the relevant behavior easier to test.

For example:

```text
Application Action
    ↓
Can be tested independently

Domain Rule
    ↓
Can be tested directly

Infrastructure Provider
    ↓
Can be mocked or integration-tested
```

Architecture should not introduce unnecessary indirection that makes testing harder without providing meaningful value.

---

# 46. Dependency Direction

Dependencies should generally flow toward stable business responsibilities.

Conceptually:

```text
Presentation
      ↓
Application
      ↓
Domain
      ↓
Infrastructure / Persistence
```

Framework-specific and provider-specific details should not become the foundation of core business rules.

Where practical:

```text
Core Business Logic
        ↓
Stable Interface / Responsibility
        ↓
Concrete Infrastructure
```

The exact mechanism may be interface-based or another appropriate Laravel design.

Abstraction must be justified by a real dependency boundary.

---

# 47. Domain Dependency Rules

The following rules should be preserved.

### Booking

May depend on:

```text
Service
Schedule
Customer
Payment relationship
Tenant context
```

but must not depend on:

```text
Blade
HTTP Request
Controller
Browser JavaScript
```

---

### Payment

May depend on:

```text
Booking/payment context
External provider boundary
Transaction state
```

but core payment rules should not depend directly on UI implementation.

---

### Schedule

May depend on:

```text
Service
Tenant
Booking availability rules
```

but should not depend on controller-specific input structures.

---

# 48. Feature Boundary Rule

A new feature should be introduced through the existing architectural boundaries before introducing a new architecture layer.

Before creating a new directory, service, interface, or framework pattern, ask:

```text
What responsibility requires this?
Which existing boundary cannot represent it?
What invariant does it protect?
What tests justify it?
```

Do not create abstractions merely because the codebase is growing.

---

# 49. Extension Points

BookQu should remain extensible in the following areas:

```text
Payment Providers
Notification Channels
Storage Providers
External Integrations
Subscription Features
Reporting
Analytics
Customer Capabilities
```

Future extension should preferably occur behind stable responsibilities.

For example:

```text
Payment
   ↓
Payment Provider Boundary
   ├── Midtrans
   ├── Future Provider A
   └── Future Provider B
```

rather than:

```text
Booking
   ↓
Direct Provider-Specific Calls
```

---

# 50. Architecture Evolution Rules

Architecture is expected to evolve.

Future changes should follow these rules:

1. Preserve accepted product behavior unless behavior change is intentional.
2. Preserve tenant isolation.
3. Preserve critical booking invariants.
4. Preserve payment/booking separation.
5. Preserve authorization boundaries.
6. Avoid unnecessary new abstractions.
7. Prefer incremental changes.
8. Update System Design when current implementation changes materially.
9. Create or update ADRs for significant long-lived architectural decisions.
10. Keep Tracker status synchronized with actual implementation.

---

# 51. Current Implementation vs Architectural Authority

The architecture document defines:

```text
What boundaries should exist.
What responsibilities belong where.
What invariants must be protected.
What direction the system should evolve.
```

The System Design document defines:

```text
How those responsibilities are implemented now.
```

Therefore:

```text
ARCHITECTURE
→ Normative

SYSTEM DESIGN
→ Descriptive + Current
```

A current implementation detail may be temporarily different from the architectural target.

Such a difference should be visible and intentional rather than silently redefining the architecture.

---

# 52. Architecture Decision Records

Important architectural decisions should be recorded in:

```text
docs/adr/
```

An ADR is justified when a decision:

* has long-term consequences;
* establishes a system-wide boundary;
* affects multiple domains;
* constrains future implementation;
* would be difficult to reconstruct later;
* resolves a significant architectural trade-off.

Examples include:

```text
Multi-Tenancy Strategy
Booking Concurrency Model
Payment State Separation
Customer Token Scope
External Payment Provider Boundary
```

Minor implementation decisions do not require ADRs.

---

# 53. Operational Boundary

Architecture defines what operational capabilities must exist.

Detailed procedures belong to:

```text
docs/8-OPERATIONS.md
```

For example, architecture may require:

```text
Scheduled payment expiration
Cache consistency
Background processing
External payment reconciliation
Production observability
```

Operations defines:

```text
How the scheduler is configured
How it is verified
How failures are diagnosed
How production is recovered
```

This separation keeps architecture stable while operational procedures can evolve.

---

# 54. Documentation Boundary

Architecture documentation should not duplicate:

```text
Product requirements
Current implementation details
Operational runbooks
Historical refactor plans
```

Instead:

```text
Product
→ docs/2-PRODUCT.md

Requirements
→ docs/3-REQUIREMENT.md

Architecture
→ docs/4-ARCHITECTURE.md

Current System
→ docs/7-SYSTEM-DESIGN.md

Operations
→ docs/8-OPERATIONS.md

Development
→ docs/5-DEVELOPMENT.md

Status
→ docs/6-TRACKER.md

Rationale
→ docs/adr/
```

---

# 55. Architecture Change Process

An architectural change should normally follow:

```text
Identify Problem
       ↓
Identify Affected Requirement
       ↓
Inspect Current System Design
       ↓
Define Architectural Change
       ↓
Evaluate Consequences
       ↓
Create / Update ADR if Significant
       ↓
Implement Incrementally
       ↓
Test
       ↓
Update System Design
       ↓
Update Tracker
```

A significant architectural decision should not be introduced only through code without documenting the resulting architectural rule.

---

# 56. Definition of Architectural Compliance

A feature is architecturally compliant when:

```text
Responsibilities are clear
        ↓
Tenant boundaries are preserved
        ↓
Business rules have appropriate ownership
        ↓
Controllers remain appropriately thin
        ↓
External integrations remain isolated
        ↓
Critical operations preserve transaction safety
        ↓
Important invariants are testable
        ↓
The implementation follows the intended architectural direction
```

Architectural compliance does not mean every file must perfectly match a theoretical structure.

The goal is meaningful responsibility separation and safe evolution.

---

# 57. Anti-Patterns to Avoid

The following patterns should be treated as architectural warnings.

## God Controller

A controller containing:

```text
Validation
Business Rules
Database Orchestration
Payment Logic
Notification Logic
Availability Logic
```

without clear delegation.

---

## God Service

A generic service responsible for unrelated domains.

Example:

```text
BookQuService
```

containing booking, payment, subscription, analytics, and customer logic without clear boundaries.

---

## Generic Utility Dump

A helper or utility class containing unrelated business rules simply because they are shared.

---

## Direct Provider Coupling

Core business operations directly calling provider-specific APIs throughout the application.

---

## Business Logic in Blade

Critical rules implemented inside:

```text
Blade
JavaScript
UI Component
```

instead of the appropriate backend/domain boundary.

---

## Client-Side Security

Relying on:

```text
Hidden Input
Frontend Condition
Disabled Button
JavaScript Check
```

as authorization.

---

## Duplicate Business Rules

Implementing the same booking/payment rule differently in multiple controllers, commands, and services.

---

## Architecture by Folder

Creating directories such as:

```text
Domain
Service
Repository
Manager
Helper
Utility
```

without a clear responsibility.

Folders are organizational tools, not architecture by themselves.

---

# 58. Future-Ready Architecture

The architecture must support future capabilities without predicting them as requirements.

Potential expansion areas include:

```text
Additional Payment Providers
Additional Notification Channels
External Calendar Integrations
Additional Customer Capabilities
Multi-Location Support
Advanced Analytics
Additional Tenant Features
```

Future capabilities should be introduced through existing boundaries whenever possible.

A new architectural layer should be created only when the new requirement genuinely introduces a new responsibility.

---

# 59. Architecture Success Criteria

The architecture is considered healthy when:

```text
Product behavior has a clear requirement
        ↓
Requirement has an architectural home
        ↓
Current implementation has a clear system-design mapping
        ↓
Critical behavior has tests
        ↓
Tenant isolation is enforced
        ↓
Booking invariants are protected
        ↓
Payment boundaries are clear
        ↓
External integrations are isolated
        ↓
Future changes can be introduced incrementally
```

---

# 60. Final Architectural Model

The BookQu architecture can be summarized as:

```text
                    PRODUCT
                       ↓
                  REQUIREMENTS
                       ↓
                 ARCHITECTURAL
                   PRINCIPLES
                       ↓
        ┌──────────────┼──────────────┐
        ↓              ↓              ↓
   PRESENTATION    APPLICATION      DOMAIN
        │              │              │
        └──────────────┼──────────────┘
                       ↓
                 INFRASTRUCTURE
                       ↓
              DATABASE / PROVIDERS
```

with cross-cutting concerns:

```text
Tenant Isolation
Authorization
Security
Transactions
Caching
Logging
Testing
```

and supporting documentation:

```text
SYSTEM DESIGN
→ Current implementation model

OPERATIONS
→ Runtime and operational procedures

ADR
→ Architectural rationale
```

---

# 61. Final Principle

The BookQu architecture must evolve without losing the boundaries that protect its critical behavior.

The fundamental principles are:

```text
Clear Responsibilities
        +
Strong Tenant Isolation
        +
Explicit Business Operations
        +
Centralized Critical Rules
        +
Safe Transactions
        +
Separated External Integrations
        +
Testable Design
        +
Incremental Evolution
```

The architecture exists to make correct product behavior easier to maintain, verify, and extend.

> **Architecture should provide stable boundaries while allowing implementation details to evolve.**

The source of truth for product meaning remains:

```text
docs/2-PRODUCT.md
```

The source of truth for required behavior remains:

```text
docs/3-REQUIREMENT.md
```

The source of truth for current implementation design is:

```text
docs/7-SYSTEM-DESIGN.md
```

The source of truth for operational procedures is:

```text
docs/8-OPERATIONS.md
```

The rationale for significant architectural decisions is maintained in:

```text
docs/adr/
```
