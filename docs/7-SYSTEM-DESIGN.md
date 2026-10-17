# BookQu System Design

> **Document Status:** Active
> **Version:** 1.1
> **Authority:** Current system behavior and implementation model
> **Product Definition:** `docs/2-PRODUCT.md`
> **Requirements:** `docs/3-REQUIREMENT.md`
> **Architecture:** `docs/4-ARCHITECTURE.md`
> **Development Workflow:** `docs/5-DEVELOPMENT.md`
> **Tracker:** `docs/6-TRACKER.md`
> **Operations:** `docs/8-OPERATIONS.md`
> **ADR:** `docs/adr/`
> **Last Updated:** 2026-09-30
>
> This document describes how the current BookQu system is actually structured and how its important flows currently operate.
>
> It is descriptive rather than normative.
>
> `docs/4-ARCHITECTURE.md` defines how the system should be structured.
>
> This document explains how the current implementation works.

---

# 1. Purpose

The purpose of this document is to provide a technical model of the current BookQu system.

It answers questions such as:

```text
How does a booking move through the system?

How is tenant context established?

How is schedule availability determined?

How are booking states changed?

How is payment connected to booking state?

How does customer access work without a normal account?

How are subscriptions and entitlements enforced?

Which component owns an important business rule?

Where are transaction and concurrency boundaries?
```

This document is intended for:

```text
Developers
AI agents
System maintainers
Reviewers
Future contributors
```

---

# 2. System Model

At a high level, BookQu can be represented as:

```text
                     ┌──────────────────┐
                     │  Public Customer │
                     │      Portal      │
                     └────────┬─────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │  Booking System  │
                    └────────┬─────────┘
                              │
             ┌────────────────┼────────────────┐
             ▼                ▼                ▼
       Availability        Booking          Payment
             │                │                │
             └────────────────┼────────────────┘
                              │
                              ▼
                     Customer / Owner
                              │
                              ▼
                     Notification Layer
```

The owner side operates primarily through:

```text
Owner
  ↓
Authentication
  ↓
Tenant Context
  ↓
Owner Portal
  ├── Services
  ├── Schedules
  ├── Bookings
  ├── Customers
  ├── Staff / Resources
  ├── Vouchers
  ├── Reviews
  ├── Analytics
  ├── Reports
  ├── Appearance
  ├── Assets
  ├── Notifications
  └── Subscription
```

The customer side operates through:

```text
Public Tenant Page
        ↓
Service
        ↓
Date
        ↓
Available Schedule
        ↓
Customer Information
        ↓
Booking
        ↓
Payment
        ↓
Confirmation
```

---

# 3. Architectural Relationship

The current system should be understood through three complementary documents:

```text
4-ARCHITECTURE.md
        ↓
Target structural model

7-SYSTEM-DESIGN.md
        ↓
Current implementation model

6-TRACKER.md
        ↓
Current implementation status
```

A system component may therefore be:

```text
Implemented
but
architecturally imperfect.
```

The purpose of this document is not to hide such differences.

---

# 4. Main System Boundaries

BookQu currently contains the following major logical areas:

```text
Authentication
Tenant / Business
Public Business
Service
Category
Schedule
Availability
Booking
Customer
Payment
Refund
Subscription
Voucher
Staff
Resource
Additional Item
Review
Notification
Analytics
Report
Appearance
Asset
Admin
```

These areas do not necessarily correspond one-to-one with directories.

They represent the logical responsibilities that participate in the system.

---

# 5. Request Processing Model

A typical HTTP request follows this pattern:

```text
HTTP Request
     ↓
Route
     ↓
Middleware
     ↓
Authentication / Tenant Resolution
     ↓
Form Request / Validation
     ↓
Controller
     ↓
Application Action / Service
     ↓
Domain Rules
     ↓
Models / Database / External Integration
     ↓
Response
```

For customer-facing requests, authentication may not exist in the normal owner sense.

Instead:

```text
Public Request
     ↓
Tenant Resolution
     ↓
Booking / Token Validation
     ↓
Application Operation
```

depending on the capability.

---

# 6. Tenant Model

BookQu is multi-tenant.

The basic conceptual model is:

```text
Owner
  ↓
Tenant / Business
  ↓
Business Data
```

Tenant-owned resources may include:

```text
Services
Categories
Schedules
Bookings
Customers
Staff
Resources
Vouchers
Additional Items
Reviews
Assets
Appearance
Reports
Analytics
Subscription-related data
```

The tenant is therefore a primary authorization boundary.

---

# 7. Tenant Resolution

Tenant context may be established through the appropriate public or authenticated business access mechanism.

Once resolved, downstream operations must operate within the tenant boundary.

Conceptually:

```text
Request
  ↓
Resolve Tenant
  ↓
Set Tenant Context
  ↓
Resolve Resource
  ↓
Verify Resource Ownership
  ↓
Execute Operation
```

Tenant resolution must happen before business operations that depend on tenant ownership.

---

# 8. Tenant Isolation

Tenant isolation is a system invariant.

The system must prevent:

```text
Tenant A
    ↓
Reading Tenant B data
```

or:

```text
Tenant A
    ↓
Modifying Tenant B data
```

even when an attacker knows:

```text
ID
UUID
Booking code
Slug
Public identifier
Token
```

The application must therefore not rely solely on identifiers for authorization.

Ownership must be established within the current tenant context.

---

# 9. Owner Authentication Flow

The owner-side request model is:

```text
Owner
  ↓
Login
  ↓
Authenticated Session
  ↓
Protected Route
  ↓
Authorization
  ↓
Tenant Context
  ↓
Owner Operation
```

Owner authentication and tenant ownership are related but distinct concerns.

Authentication answers:

> Who is the authenticated actor?

Tenant authorization answers:

> Which tenant may this actor operate on?

---

# 10. Public Tenant Flow

A public tenant page provides an entry point for customer interaction.

Conceptually:

```text
Tenant Public Identifier
        ↓
Tenant Lookup
        ↓
Public Business Page
        ↓
Available Services
        ↓
Booking Flow
```

Public access must expose only data intended for the public.

Private owner/customer data must remain inaccessible.

---

# 11. Service Model

A service belongs to a tenant.

Conceptually:

```text
Tenant
  └── Service
       ├── Name
       ├── Description
       ├── Price
       ├── Duration
       └── Active State
```

A customer should only be able to book a service that:

```text
belongs to the current tenant
and
is available for public booking
```

Inactive services must not become valid customer booking targets.

---

# 12. Schedule Model

A schedule represents a bookable time associated with a tenant/service context.

The schedule system is responsible for concepts such as:

```text
Date
Time
Availability
Pricing
Blocked Dates
Schedule Conflicts
Past Schedule Protection
```

Schedule generation and schedule availability are separate concerns.

---

# 13. Availability Model

Availability is derived from several conditions.

Conceptually:

```text
Schedule exists
    +
Schedule belongs to tenant/service
    +
Schedule is not blocked
    +
Schedule is not in the past
    +
Schedule is compatible with requested booking
    +
No active booking occupies it
```

Only after these conditions are satisfied should the schedule be considered available.

---

# 14. Active Booking Definition

BookQu does not treat every booking row as permanently occupying a schedule.

Current occupancy behavior is:

```text
paid
    → occupies schedule

completed
    → occupies schedule

pending within 15-minute grace
    → occupies schedule

pending beyond grace
    → stale; should no longer block normal availability

cancelled
    → does not occupy schedule
```

This distinction is critical to the booking system.

---

# 15. Pending Booking Grace

The current grace period is:

```text
15 minutes
```

The authoritative rule must not be duplicated independently across multiple implementation layers.

The system uses a centralized booking rule for pending-state evaluation.

---

# 16. Stale Pending Handling

A pending booking may become stale before any scheduled cleanup process runs.

Therefore the booking system cannot depend solely on a scheduler for correctness.

Conceptually:

```text
New Booking Request
        ↓
Acquire Relevant Booking/Schedule Protection
        ↓
Evaluate Existing Booking
        ↓
Identify Stale Pending
        ↓
Evict / Release Stale State
        ↓
Re-check Availability
        ↓
Create New Booking
```

This prevents an expired pending record from permanently blocking a schedule.

---

# 17. Booking Creation Flow

The primary customer booking flow is:

```text
Customer
   ↓
Select Tenant
   ↓
Select Service
   ↓
Select Date
   ↓
Load Available Schedules
   ↓
Select Schedule(s)
   ↓
Enter Customer Information
   ↓
Validate Request
   ↓
Check Ownership
   ↓
Check Availability
   ↓
Check Booking Rules
   ↓
Create Booking
   ↓
Create Payment Requirement
   ↓
Return Booking / Payment Information
```

The exact UI sequence may vary, but the server-side invariants remain authoritative.

---

# 18. Booking Transaction Boundary

Booking creation is concurrency-sensitive.

The authoritative operation must protect the sequence:

```text
Read Availability
     ↓
Resolve Existing Booking State
     ↓
Evict Stale Pending Where Applicable
     ↓
Create Booking
```

within an appropriate transaction and locking strategy.

The goal is to prevent two concurrent requests from both successfully claiming the same schedule.

---

# 19. Booking State

The current booking states are:

```text
pending
paid
cancelled
completed
```

Conceptually:

```text
                 ┌─────────────┐
                 │   pending   │
                 └──────┬──────┘
                        │
                payment success
                        │
                        ▼
                 ┌─────────────┐
                 │    paid     │
                 └──────┬──────┘
                        │
                   service done
                        │
                        ▼
                 ┌─────────────┐
                 │  completed  │
                 └─────────────┘

pending / paid
      │
   cancel
      │
      ▼
cancelled
```

Not every transition is valid from every state.

State changes must be controlled through the application's booking state rules.

---

# 20. Customer Cancellation

Customer cancellation requires the appropriate management authorization.

Conceptually:

```text
Customer
   ↓
Cancellation Request
   ↓
Validate cancellation token
   ↓
Load booking
   ↓
Verify token belongs to booking
   ↓
Check cancellation rule
   ↓
Change booking state
   ↓
Handle refund if applicable
   ↓
Notify relevant party
```

A paid booking may require refund processing.

The booking cancellation itself and the refund lifecycle are separate concepts.

---

# 21. Owner Cancellation

Owner cancellation is initiated through the authenticated owner context.

Conceptually:

```text
Owner
   ↓
Tenant Context
   ↓
Booking
   ↓
Ownership Verification
   ↓
Cancel Booking
```

Current business behavior does not automatically create the same refund behavior as customer-paid cancellation.

This distinction must remain explicit in implementation.

---

# 22. Rescheduling

Rescheduling is an availability-sensitive operation.

Conceptually:

```text
Existing Booking
      ↓
Authorize Actor
      ↓
Validate Reschedule Rules
      ↓
Select New Schedule
      ↓
Check Availability
      ↓
Protect Transaction
      ↓
Move Booking
      ↓
Update Related State
      ↓
Notify
```

Customer and owner rescheduling use the appropriate authorization path but share the core booking-domain operation.

---

# 23. Multi-Slot Booking

A multi-slot booking represents multiple schedules belonging to the same logical reservation.

The system must preserve:

```text
All requested slots compatible
        +
All slots available
        +
One coherent booking operation
        +
Consistent payment relationship
```

A partial success is not acceptable where atomicity is required.

Conceptually:

```text
Requested Slot A
Requested Slot B
Requested Slot C
       ↓
Compatibility Check
       ↓
Availability Check
       ↓
Atomic Booking
       ↓
Unified Payment
```

---

# 24. Customer Management Without Normal Account

BookQu supports customer-facing booking management without requiring the customer to have a normal authenticated account.

This is implemented through scoped management credentials/tokens.

The token is associated with a specific booking context.

The system must not treat a valid token as a universal customer credential.

---

# 25. Token Scope

Different customer capabilities use appropriate token scopes.

Examples:

```text
View / manage booking
Cancel booking
Reschedule booking
Review booking
View invoice/payment information
```

A token for one purpose must not silently become authorization for unrelated operations.

---

# 26. Token Verification Flow

A protected customer-management request follows approximately:

```text
Customer Request
      ↓
Extract Token
      ↓
Resolve Booking Context
      ↓
Verify Token
      ↓
Verify Token Scope
      ↓
Verify Booking Relationship
      ↓
Execute Operation
```

Invalid, mismatched, or cross-booking token use must result in authorization failure.

---

# 27. Payment Model

Payment is a separate stateful domain from booking.

Conceptually:

```text
Booking
   │
   └── Payment
          ├── Provider Reference
          ├── Amount
          ├── Status
          └── Payment Metadata
```

Booking state and payment state are related but must not be collapsed into one field.

---

# 28. Payment States

Current persistent payment states are:

```text
pending
sukses
gagal
```

Payment expiration is treated as a business condition.

The expiration process results in the appropriate persistent state changes rather than introducing a separate persistent `kadaluarsa` state.

---

# 29. Payment Creation Flow

Conceptually:

```text
Booking
   ↓
Create Payment
   ↓
Generate Provider Reference
   ↓
Create Provider Transaction
   ↓
Store Payment State
   ↓
Return Payment Information
```

External provider operations must remain behind the payment integration boundary.

---

# 30. Payment Verification

The browser should not be considered the ultimate source of payment truth.

The system should verify payment through the trusted provider interaction.

Conceptually:

```text
Payment Provider
      ↓
Provider Response / Webhook
      ↓
Verify Event
      ↓
Resolve Payment
      ↓
Update Payment State
      ↓
Update Related Booking State
      ↓
Trigger Relevant Effects
```

---

# 31. Payment Webhook

Webhook processing must be safe against duplicate delivery.

Conceptually:

```text
Webhook
   ↓
Verify Authenticity
   ↓
Resolve Payment
   ↓
Check Current State
   ↓
Apply State Transition if Required
   ↓
Persist
```

Repeated webhook delivery must not produce duplicate state changes or duplicate side effects.

---

# 32. Payment Expiration

Expired pending payments are handled through an application operation.

Conceptually:

```text
Scheduler
   ↓
Expire Pending Payments
   ↓
Find Eligible Payment
   ↓
Mark Payment gagal
   ↓
Cancel Related Booking
   ↓
Release Slot
   ↓
Invalidate Relevant Cache
```

The scheduler invokes the business process.

The scheduler itself must not become a second implementation of the payment rules.

---

# 33. Refund Model

Refund is a separate lifecycle.

Current refund statuses are:

```text
pending
processed
failed
```

Conceptually:

```text
Eligible Cancellation
       ↓
Refund Request
       ↓
Refund State = pending
       ↓
External / Internal Processing
       ↓
processed / failed
```

Refund state must remain distinguishable from both payment and booking state.

---

# 34. Notification Flow

Notifications are side effects of important business events.

Typical events include:

```text
Booking created
Payment completed
Booking cancelled
Booking rescheduled
Refund state changed
```

Conceptually:

```text
Business Operation
      ↓
Persistent State Change
      ↓
Notification Trigger
      ↓
Recipient Resolution
      ↓
Notification Delivery
```

Notifications must use the correct tenant, booking, owner, or customer context.

---

# 35. Subscription Model

Subscription determines access to plan-controlled capabilities.

Conceptually:

```text
Plan
  ↓
Subscription
  ↓
Subscription State
  ↓
Entitlement
  ↓
Feature Access
```

The system should evaluate entitlement through centralized rules rather than duplicating plan conditions throughout controllers.

---

# 36. Subscription State

Subscription behavior includes conditions such as:

```text
Active
Expired
Trial / transitional states where applicable
```

The exact persisted states must follow the implementation and requirements.

Feature access must use the authoritative entitlement mechanism.

---

# 37. Voucher Flow

Voucher processing occurs during applicable booking/payment operations.

Conceptually:

```text
Voucher Input
    ↓
Resolve Voucher
    ↓
Verify Tenant Ownership
    ↓
Check Status / Validity
    ↓
Check Eligibility
    ↓
Calculate Discount
    ↓
Apply Result
```

Frontend calculation must not become the final pricing authority.

---

# 38. Staff and Resource Model

Staff and resources belong to the tenant and may be associated with relevant business capabilities.

Ownership must remain tenant-scoped.

Any future capability that allows a customer to directly select staff/resources must use the existing ownership and availability boundaries rather than introducing a parallel booking model.

---

# 39. Review Flow

A review is associated with a booking/customer context.

Conceptually:

```text
Customer
   ↓
Review Request
   ↓
Validate Management Authorization
   ↓
Validate Booking / Review Eligibility
   ↓
Create Review
   ↓
Display / Aggregate Review
```

Review access must not permit arbitrary modification of another customer's booking context.

---

# 40. Dashboard Data Flow

The owner dashboard consumes operational data such as:

```text
Bookings
Revenue / Payment data
Schedules
Customers
Service activity
Subscription state
```

Dashboard metrics should be derived from authoritative domain state.

The dashboard should not define booking or payment rules itself.

---

# 41. Analytics and Reports

Analytics and reports are read-oriented consumers of domain data.

Conceptually:

```text
Authoritative Domain Data
        ↓
Queries / Aggregation
        ↓
Analytics / Reports
        ↓
Owner Presentation
```

Analytics must not mutate booking or payment state as part of normal reporting.

---

# 42. Asset Flow

Business assets such as images and branding information are associated with the owning tenant.

The general model is:

```text
Upload Request
      ↓
Validate File
      ↓
Verify Tenant Context
      ↓
Store Asset
      ↓
Associate With Tenant
      ↓
Expose Through Authorized/Public Context
```

A file path alone must never become equivalent to authorization.

---

# 43. Cache Architecture

Cache is used to optimize data retrieval.

The cache must never become the authoritative source of booking correctness.

Conceptually:

```text
Database / Domain State
          ↓
        Cache
          ↓
       Response
```

Relevant cache dimensions may include:

```text
Tenant
Service
Date / availability context
```

Cache keys must prevent cross-tenant and cross-service contamination.

Important booking mutations must invalidate or refresh affected cached availability.

---

# 44. Scheduler Model

Scheduled tasks are system automation, not an independent business layer.

Conceptually:

```text
Laravel Scheduler
      ↓
Command
      ↓
Application Operation
      ↓
Domain Rules
      ↓
Persistent State
```

Current time-sensitive operations include payment expiration and subscription-related expiration behavior.

Scheduler frequency and production cron configuration are operational concerns and are documented in:

```text
docs/8-OPERATIONS.md
```

---

# 45. Database Integrity

The database is the final persistence boundary.

Important invariants may be protected through:

```text
Foreign Keys
Unique Constraints
Indexes
Transactions
Application Rules
```

Booking concurrency is particularly dependent on combining application checks with database-level integrity.

---

# 46. Booking Uniqueness

BookQu uses database-level protection against conflicting active booking combinations.

The application handles stale pending bookings before the final booking operation.

The model is therefore conceptually:

```text
Application
    ↓
Resolve stale state
    ↓
Attempt authoritative booking
    ↓
Database integrity protection
```

The database must not be expected to evaluate time-dependent business state such as the current wall-clock time inside a static generated uniqueness expression.

---

# 47. Transaction Boundaries

Transactions are required where several related state changes must remain consistent.

Important examples include:

```text
Booking creation
Multi-slot booking
Booking cancellation
Booking rescheduling
Payment state synchronization
Refund-related state changes
Subscription state transitions
```

The exact transaction boundary belongs to the application operation responsible for the state transition.

---

# 48. Concurrency Model

Concurrency-sensitive operations must assume that multiple requests can execute simultaneously.

Example:

```text
Request A:
Check slot availability

Request B:
Check slot availability

Both requests:
Attempt to reserve same slot
```

The system must prevent both requests from independently succeeding.

Therefore:

```text
Availability Check
+
Transaction / Locking
+
Database Integrity
```

must be considered together.

---

# 49. Frontend Architecture

The frontend is primarily:

```text
Blade
+
Alpine.js / JavaScript
+
Tailwind CSS
+
Vite
```

The frontend consumes server-authoritative state and provides interaction.

The server remains authoritative for:

```text
Booking
Payment
Authorization
Tenant access
Subscription entitlement
Pricing rules
Availability
```

---

# 50. Error Handling Model

Application errors should be separated conceptually into:

```text
Validation Error
Authorization Error
Business Rule Error
Not Found
External Integration Error
Unexpected System Error
```

The user-facing response should not reveal sensitive internal information.

Internal diagnostics may contain technical context where appropriate.

---

# 51. Security Model

Security is enforced at multiple layers:

```text
Authentication
      ↓
Authorization
      ↓
Tenant Isolation
      ↓
Resource Ownership
      ↓
Business Rule Validation
      ↓
Database Integrity
```

No single layer should be assumed sufficient for all security requirements.

---

# 52. Sensitive Data Rules

The system must not expose sensitive information through:

```text
Logs
Error pages
Browser responses
URLs where avoidable
Public views
Client-side state
```

Sensitive examples include:

```text
Passwords
API secrets
Private tokens
Payment credentials
Internal security information
```

Customer management tokens must be handled as security-sensitive credentials.

---

# 53. Current Important Invariants

The following invariants are particularly important to the current system:

```text id="nm8bqg"
1. A tenant must not access another tenant's data.

2. A customer must not book an unavailable schedule.

3. A stale pending booking must not permanently block availability.

4. Paid and completed bookings occupy their schedules.

5. Cancelled bookings do not occupy schedules.

6. Pending bookings occupy schedules only during the defined grace period.

7. Payment state and booking state are related but separate.

8. Duplicate payment callbacks must not create duplicate effects.

9. Customer tokens must not cross booking boundaries.

10. Refund lifecycle must remain distinct from booking/payment state.

11. Multi-slot booking must remain consistent as one logical operation.

12. Cache must not override authoritative persistent state.
```

These invariants should be protected by the appropriate combination of:

```text
Application Rules
Transactions
Authorization
Database Constraints
Tests
```

---

# 54. Current High-Level Booking Sequence

A complete normal booking can be represented as:

```text
Customer
   ↓
Public Tenant
   ↓
Service
   ↓
Date
   ↓
Available Schedule Query
   ↓
Customer Information
   ↓
CreateBooking
   ↓
Transaction
   ├── Verify Tenant
   ├── Verify Service
   ├── Verify Schedule
   ├── Resolve Pending State
   ├── Check Availability
   └── Persist Booking
   ↓
Payment
   ↓
Payment Provider
   ↓
Webhook / Verification
   ↓
Payment State
   ↓
Booking State
   ↓
Notification
```

---

# 55. Cancellation Sequence

Customer cancellation:

```text
Customer
   ↓
Cancellation Token
   ↓
Validate Token
   ↓
Resolve Booking
   ↓
Check Cancellation Rules
   ↓
Cancel Booking
   ↓
Queue / Process Refund if applicable
   ↓
Notify
   ↓
Release Availability
```

Owner cancellation:

```text
Owner
   ↓
Authenticated Tenant Context
   ↓
Resolve Booking
   ↓
Verify Ownership
   ↓
Cancel Booking
   ↓
Apply Owner Cancellation Rules
   ↓
Notify
   ↓
Release Availability
```

---

# 56. Rescheduling Sequence

```text
Actor
   ↓
Authorization
   ↓
Existing Booking
   ↓
Reschedule Rules
   ↓
New Schedule Selection
   ↓
Availability Validation
   ↓
Transaction
   ↓
Update Booking
   ↓
Invalidate Affected Availability Cache
   ↓
Notify
```

---

# 57. Payment Expiration Sequence

```text
Scheduler
   ↓
Expire Payment Command
   ↓
Find Expired Pending Payment
   ↓
Payment → gagal
   ↓
Booking → cancelled
   ↓
Schedule becomes available
   ↓
Invalidate Relevant Cache
```

This process must remain idempotent.

Running it more than once must not create additional unintended state changes.

---

# 58. Current State Ownership

Important state should have a clear owner:

| State / Rule                 | Primary Authority                     |
| ---------------------------- | ------------------------------------- |
| Tenant ownership             | Tenant / authorization boundary       |
| Service active state         | Service domain/application            |
| Schedule availability        | Schedule / Booking availability rules |
| Booking state                | Booking domain                        |
| Pending grace period         | Booking rules                         |
| Payment state                | Payment domain                        |
| Refund state                 | Refund/payment process                |
| Subscription entitlement     | Subscription domain                   |
| Customer token authorization | Customer management authorization     |
| Notification delivery        | Notification layer                    |
| Cached availability          | Cache layer, derived only             |

This table is a conceptual ownership map, not a replacement for implementation details.

---

# 59. Extension Model

New functionality should connect to existing boundaries instead of introducing parallel systems.

Examples:

```text
New payment provider
    → Payment integration boundary

New booking source
    → Booking application boundary

New subscription capability
    → Subscription entitlement boundary

New notification channel
    → Notification boundary

New availability rule
    → Availability / Booking domain
```

A new feature should not bypass existing authoritative rules merely because it originates from a different UI.

---

# 60. Current System vs Future System

This document should describe the current system.

The following belong elsewhere:

```text
Target architectural improvements
→ docs/4-ARCHITECTURE.md

Development procedure
→ docs/5-DEVELOPMENT.md

Current status
→ docs/6-TRACKER.md

Operational procedures
→ docs/8-OPERATIONS.md

Architectural rationale
→ docs/adr/
```

Do not turn this document into a roadmap.

---

# 61. System Design Change Rule

Update this document when a meaningful change alters the current system model.

Examples:

```text
New booking flow
Changed booking state
Changed payment flow
Changed tenant resolution
Changed authorization path
Changed schedule availability logic
Changed transaction boundary
Changed external integration boundary
Changed scheduler behavior
Changed important database invariant
```

A purely internal implementation detail that does not alter the system model does not necessarily require an update.

---

# 62. System Design Verification

When this document is changed, verify it against:

```text
Source Code
Tests
Database Schema
Routes
Configuration
Jobs / Commands
Current Documentation
```

The goal is:

```text
Documentation
    ≈
Current System
```

not:

```text
Documentation
    ≠
Current System
```

---

# 63. Final System Model

The current BookQu system can be summarized as:

```text
                         BOOKQU
                           │
              ┌────────────┴────────────┐
              │                         │
          OWNER SIDE              CUSTOMER SIDE
              │                         │
      Authentication              Public Tenant
              │                         │
        Tenant Context             Service
              │                         │
        Owner Portal             Availability
              │                         │
      ┌───────┼────────┐         Booking
      │       │        │            │
  Services  Schedules  Bookings     │
      │       │        │            │
      └───────┼────────┘            │
              │                     │
              └──────────┬──────────┘
                         │
                    Booking Domain
                         │
                ┌────────┴────────┐
                │                 │
             Payment          Customer
                │                 │
             Midtrans        Token Access
                │                 │
                └────────┬────────┘
                         │
                    Notifications
                         │
                    Persistence
```

The critical system path is:

```text
Tenant
  ↓
Service
  ↓
Schedule
  ↓
Availability
  ↓
Booking
  ↓
Payment
  ↓
Booking State
  ↓
Notification
```

The critical security path is:

```text
Actor
  ↓
Authentication / Token
  ↓
Tenant Context
  ↓
Resource Ownership
  ↓
Authorization
  ↓
Business Rule
  ↓
Persistence
```

The critical integrity path is:

```text
Request
  ↓
Validation
  ↓
Availability
  ↓
Transaction
  ↓
Database Constraint
  ↓
Consistent State
```

---

# 64. Final Principle

`docs/7-SYSTEM-DESIGN.md` exists to ensure that a contributor can understand:

> **How BookQu actually works today.**

It should remain:

```text
Current
Descriptive
Evidence-based
Implementation-aware
Invariant-focused
```

It should not become:

```text
A product specification
A target architecture document
A task tracker
A refactor history
A deployment manual
```

The system design should evolve whenever the current implementation meaningfully changes, while keeping the responsibility boundaries of the other documentation intact.
