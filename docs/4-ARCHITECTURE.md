# BookQu Architecture Specification

> **Document Status:** Current Architecture + Target Architecture
> **Version:** 1.0
> **Authority:** Authoritative technical architecture direction
> **Related Product Definition:** `docs/PRODUCT.md`
> **Related Requirements:** `docs/REQUIREMENTS.md`
> **Last Updated:** 2026-09-26
>
> This document defines how BookQu should be structured, how responsibilities should be separated, and how the current implementation should evolve toward a maintainable and scalable architecture.
>
> This document is a technical specification. It does not define product scope. Product behavior is defined in `docs/PRODUCT.md` and `docs/REQUIREMENTS.md`.

---

# 1. Purpose

The purpose of this document is to establish a consistent architecture for BookQu so that:

* all developers structure code consistently;
* AI agents follow the same technical boundaries;
* business logic does not become concentrated in controllers;
* UI logic does not become concentrated in large Blade files;
* tenant isolation remains a first-class security boundary;
* new modules can be introduced without destabilizing unrelated modules;
* refactoring can happen incrementally;
* technical debt can be reduced without changing intended product behavior.

The architecture must optimize for:

```text
Correctness
Maintainability
Testability
Tenant Isolation
Modularity
Scalability
Understandability
```

Performance optimization is important, but premature abstraction is discouraged.

---

# 2. Architectural Context

BookQu is a Laravel-based multi-tenant web application.

The current implementation uses:

```text
Backend
- PHP 8.3+
- Laravel 13

Frontend
- Blade
- Alpine.js
- Tailwind CSS

Build
- Vite

Database
- MySQL / compatible relational database

Payment
- Midtrans

Testing
- PHPUnit / Laravel test suite
```

The current repository already contains:

```text
app/Http/Controllers
app/Http/Middleware
app/Models
app/Services
app/Support
app/Traits
app/Mail
app/Notifications
database/migrations
database/seeders
resources/views
tests
```

These existing structures should be preserved where they are conceptually appropriate.

---

# 3. Architecture Goals

The target architecture has the following goals.

## 3.1 Clear Responsibility

Every layer should have one primary responsibility.

---

## 3.2 Domain-Oriented Structure

Code should be organized around meaningful BookQu domains rather than arbitrary technical groupings alone.

Primary domains include:

```text
Authentication
Tenant
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
Analytics
Notification
Asset
```

---

## 3.3 Thin HTTP Layer

Controllers should coordinate HTTP requests and delegate business operations.

Controllers should not become large business-logic containers.

---

## 3.4 Explicit Business Operations

Important business operations should exist as explicit application actions or services.

Examples:

```text
CreateBooking
CancelBooking
RescheduleBooking
CreateWalkInBooking
CreateSchedule
ProcessBookingPayment
ApplyVoucher
ChangeBookingStatus
```

---

## 3.5 Testability

Business rules must be testable without requiring the full browser request flow whenever practical.

---

## 3.6 Tenant Safety

Tenant isolation must be enforced consistently and must not rely on individual developer discipline.

---

## 3.7 Incremental Refactoring

The target architecture must be achievable gradually.

A working feature should not be rewritten solely for stylistic reasons if doing so introduces unnecessary product risk.

---

# 4. Current Architecture

The current BookQu implementation approximately follows this structure:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Customer/
│   │   ├── Owner/
│   │   └── Webhook/
│   └── Middleware/
│
├── Models/
├── Services/
├── Support/
├── Traits/
├── Mail/
└── Notifications/
```

The frontend is primarily organized as:

```text
resources/views/
├── admin/
├── auth/
├── components/
│   ├── customer/
│   ├── landing/
│   └── owner/
├── customer/
├── emails/
├── layouts/
└── owner/
```

The current structure is functional, but not all responsibilities are consistently separated.

---

# 5. Current Architecture Problems

The following issues are recognized architectural debt.

## 5.1 Oversized Controllers

Some controllers contain too many responsibilities.

The most significant example is the customer booking controller, which currently contains the complete flow for:

* service selection;
* date selection;
* time selection;
* checkout;
* voucher validation;
* payment;
* payment callback;
* invoice.

The resulting controller is too large to remain a good long-term architectural boundary.

---

## 5.2 Business Logic Inside Controllers

Controllers currently perform operations such as:

* database queries;
* validation;
* business-rule checks;
* transaction management;
* cache management;
* payment interaction;
* booking state transitions;
* notification-related operations.

This makes the logic harder to reuse and test.

---

## 5.3 Large Blade Files

Several owner and customer Blade files are very large.

Large page files may contain a mixture of:

* markup;
* UI state;
* modal markup;
* Alpine state;
* form logic;
* repeated presentation logic;
* JavaScript;
* business-specific conditions.

This makes changes risky and encourages duplication.

---

## 5.4 Inconsistent Naming

The current repository contains legacy naming from different development stages.

Examples include:

```text
Program
Service
Layanan
idlayanan
namalayanan
```

Product terminology has been standardized around `Service`.

Code refactoring may happen gradually.

---

## 5.5 Generic Utility Accumulation

The current repository includes traits and support classes.

Traits are useful for cross-cutting behavior, but they must not become a place where unrelated business logic accumulates.

---

## 5.6 Large Shared Controllers

Historically, `OwnerPortalController` hosted unrelated capabilities such as:

* calendar;
* schedule reporting;
* appearance;
* payment settings;
* balance;
* integrations.

Under RF-04, `OwnerPortalController` was decomposed into dedicated, cohesive controllers:
* `OwnerCalendarController`
* `OwnerScheduleReportController`
* `OwnerAppearanceController`
* `OwnerPaymentSettingsController`
* `OwnerBalanceController`
* `OwnerIntegrationController`

`OwnerPortalController` was preserved as a thin, delegating adapter ensuring 100% backwards compatibility for legacy callers.

---

## 5.7 Route File Growth

The main web route file contains a large number of routes across:

* authentication;
* owner operations;
* customer booking;
* booking management;
* admin;
* payment webhooks;
* tenant public routing.

Route definitions are valid, but the file should remain primarily declarative.

Business logic must not move into route definitions.

---

# 6. Target Architecture

The target architecture is a pragmatic layered/domain-oriented Laravel architecture.

It is intentionally not a fully isolated enterprise architecture.

The target is:

```text
HTTP
  ↓
Application
  ↓
Domain
  ↓
Infrastructure
```

with presentation views remaining above the application layer.

Conceptually:

```text
                 ┌─────────────────────┐
                 │   HTTP / Web / UI   │
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │    Application     │
                 │ Actions / Use Cases│
                 └──────────┬──────────┘
                            │
                            ▼
                 ┌─────────────────────┐
                 │       Domain       │
                 │ Business Rules     │
                 │ Domain Services    │
                 └──────────┬──────────┘
                            │
                ┌───────────┴───────────┐
                ▼                       ▼
       ┌────────────────┐      ┌────────────────┐
       │ Infrastructure │      │   Persistence  │
       │ Midtrans/Mail  │      │ Eloquent/DB    │
       └────────────────┘      └────────────────┘
```

Not every BookQu operation needs every layer explicitly.

The architecture should remain practical.

---

# 7. Target Folder Structure

The target structure is:

```text
app/
├── Actions/
│   ├── Booking/
│   ├── Customer/
│   ├── Payment/
│   ├── Schedule/
│   ├── Service/
│   ├── Subscription/
│   └── Tenant/
│
├── Domain/
│   ├── Booking/
│   ├── Customer/
│   ├── Payment/
│   ├── Schedule/
│   ├── Service/
│   ├── Subscription/
│   └── Tenant/
│
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Customer/
│   │   ├── Owner/
│   │   └── Webhook/
│   │
│   ├── Requests/
│   │   ├── Booking/
│   │   ├── Customer/
│   │   ├── Schedule/
│   │   └── Service/
│   │
│   └── Middleware/
│
├── Models/
│
├── Services/
│   ├── Payment/
│   ├── Notification/
│   ├── Storage/
│   └── External/
│
├── Infrastructure/
│   ├── Payments/
│   ├── Notifications/
│   └── Storage/
│
├── Support/
│
└── Traits/
```

This is the target direction, not a requirement to create every directory immediately.

Empty abstractions should not be created merely for the sake of architecture.

---

# 8. Layer Responsibilities

## 8.1 HTTP Layer

Responsible for:

* receiving HTTP requests;
* selecting the appropriate action;
* authentication context;
* authorization coordination;
* request validation;
* returning views or redirects.

Should not contain complex business rules.

---

## 8.2 Form Requests

Form Requests are responsible for:

* input validation;
* authorization checks that belong to request validation;
* normalization of request input where appropriate.

Example:

```text
StoreBookingRequest
UpdateBookingRequest
RescheduleBookingRequest
StoreServiceRequest
UpdateServiceRequest
```

Controllers should not contain large inline validation blocks when a dedicated Form Request improves clarity.

---

## 8.3 Controllers

Controllers should be thin.

Ideal controller flow:

```text
Request
   ↓
Authorize / Validate
   ↓
Action
   ↓
Response
```

Example:

```php
public function store(StoreBookingRequest $request)
{
    $booking = $this->createBooking->execute(
        $request->validated()
    );

    return redirect()->route(...);
}
```

The controller should not contain the entire booking algorithm.

---

# 9. Application Actions

Actions represent business use cases from the application's perspective.

Examples:

```text
CreateBooking
CancelBooking
RescheduleBooking
CreateWalkInBooking
UpdateBookingStatus
CreateSchedule
BulkCreateSchedules
CreateService
UpdateService
ProcessPayment
ApplyVoucher
SubmitReview
```

An Action should represent one meaningful operation.

Good:

```text
CreateWalkInBooking
```

Bad:

```text
BusinessManager
```

which performs unrelated operations.

---

# 10. Domain Layer

The Domain layer contains business concepts and rules that should remain understandable independently of the HTTP interface.

Examples:

```text
BookingStateTransition
BookingAvailability
ScheduleConflict
VoucherEligibility
SubscriptionEntitlement
TenantAccess
```

Domain logic should not depend directly on:

* Blade;
* route parameters;
* HTTP request objects;
* UI-specific strings.

---

# 11. Domain Services

Domain Services are appropriate when a business rule:

* spans multiple models;
* does not naturally belong to one model;
* is complex enough to justify explicit representation.

Examples:

```text
BookingAvailabilityService
BookingPricingService
SubscriptionEntitlementService
VoucherValidationService
```

A service should not become a generic container for all business logic.

---

# 12. Models

Eloquent Models are responsible for:

* persistence representation;
* relationships;
* attribute casting;
* simple domain behavior closely associated with the entity;
* query scopes where appropriate.

Models should not become enormous workflow controllers.

Avoid putting a complete multi-step payment workflow into a model.

---

# 13. Repositories

Repositories are not mandatory.

BookQu should use Eloquent directly by default when:

```text
query is straightforward
+
Eloquent expresses it clearly
+
no alternate data source exists
```

Introduce a Repository only when it creates meaningful architectural value, such as:

* multiple data sources;
* complex persistence abstraction;
* difficult query reuse;
* external data source boundary.

Do not create:

```text
BookingRepository
ServiceRepository
CustomerRepository
```

automatically for every model.

---

# 14. Services

The `Services` directory should contain integrations or reusable application-level services where appropriate.

Examples:

```text
MidtransPaymentService
NotificationService
MediaStorageService
```

Services should have a clear responsibility.

Avoid generic names such as:

```text
CommonService
HelperService
GeneralService
UtilityService
```

unless the responsibility is truly coherent.

---

# 15. Infrastructure

Infrastructure contains external system concerns.

Examples:

```text
Midtrans
Email
File storage
External APIs
Future WhatsApp integration
Future calendar integrations
```

The domain should not need to know the low-level details of Midtrans HTTP/API behavior.

Instead:

```text
Application
   ↓
Payment interface/service
   ↓
Midtrans adapter
```

---

# 16. Payment Architecture

Payment is an external integration boundary.

The conceptual architecture is:

```text
Booking / Subscription
        ↓
Payment Application Logic
        ↓
Payment Service
        ↓
Payment Provider Adapter
        ↓
Midtrans
```

Payment provider-specific code should not spread throughout unrelated controllers.

A controller should not directly call Midtrans APIs.

---

# 17. Notification Architecture

Notifications should originate from meaningful system events.

Conceptually:

```text
Domain Event
    ↓
Notification Handler
    ↓
Notification Channel
```

Potential channels:

```text
Email
Database notification
Future WhatsApp
Future external messaging
```

Adding another notification channel should not require rewriting booking business logic.

---

# 18. Multi-Tenancy Architecture

Multi-tenancy is a core security boundary.

Current BookQu already uses a tenant-context approach with mechanisms such as:

```text
TenantContext
TenantMiddleware
TenantScope
BelongsToTenant
ResolvesOwnerTenant
```

The target architecture retains this concept.

---

## 18.1 Tenant Resolution

Tenant context may be established through:

```text
Owner authenticated context
        OR
Public tenant slug
        OR
Configured custom domain
```

After resolution, downstream tenant-owned operations should use the established tenant context.

---

## 18.2 Tenant Isolation

Tenant isolation should occur at multiple layers:

```text
HTTP / Middleware
       ↓
Tenant Context
       ↓
Model / Query Scope
       ↓
Authorization
       ↓
Database constraints
```

No single layer should be treated as the only security mechanism.

---

## 18.3 Fail Closed

If a tenant-scoped query requires tenant context and no valid tenant context exists, the system should fail closed rather than accidentally returning records from all tenants.

---

## 18.4 Owner Authorization

Tenant context does not replace authorization.

The system must still verify that the authenticated owner is permitted to operate on the resolved tenant.

---

# 19. Tenant Context Rules

Any code using tenant-owned data should follow these rules:

1. Establish tenant context before tenant-scoped operations.
2. Do not trust a client-provided tenant ID as authorization.
3. Do not manually repeat tenant checks throughout every query when the established scope already provides the required isolation.
4. Use explicit `withoutGlobalScopes()` only when there is a documented reason.
5. Any intentional bypass must perform explicit authorization checks.
6. Never use tenant bypass methods as a normal convenience.

---

# 20. Booking Architecture

Booking is the central domain.

The target flow is:

```text
HTTP Request
      ↓
Booking Request Validation
      ↓
Booking Action
      ↓
Availability Validation
      ↓
Business Rules
      ↓
Database Transaction
      ↓
Booking Persistence
      ↓
Payment / Notification where applicable
```

---

# 21. Booking State Management

Booking state transitions should be centralized.

Avoid scattering logic such as:

```text
if ($booking->status === ...)
```

through dozens of controllers.

A state transition should have a clear source of truth.

Conceptually:

```text
Booking
 ├── pending
 ├── paid
 ├── completed
 └── cancelled
```

Transitions should be validated by the booking domain/application layer.

---

# 22. Schedule and Availability Architecture

Schedule and availability must remain separate concepts.

```text
Service
  ↓
Schedule
  ↓
Booking
  ↓
Availability State
```

Schedule defines a potential bookable period.

Booking consumption affects current availability.

Availability should not be duplicated independently across several controllers.

---

# 23. Double-Booking Protection

Double booking is a critical domain invariant.

Protection should exist at more than one level where necessary:

```text
Application validation
        +
Database constraints / transaction behavior
        +
Concurrency-safe operation
```

A UI-level availability check alone is insufficient.

---

# 24. Customer Booking Management

Customer management links must use an explicit secure authorization mechanism.

The architecture should separate:

```text
Booking identity
```

from:

```text
Booking management authorization
```

A booking code is not automatically sufficient authorization to perform protected operations.

---

# 25. Multi-Slot Booking Architecture

Multi-slot booking consists conceptually of:

```text
Customer Selection
        ↓
Slot Compatibility Validation
        ↓
Reservation Intent
        ↓
Payment Group
        ↓
Booking Records
```

The application layer should coordinate this operation.

It should not be implemented independently in several controllers.

---

# 26. Payment and Booking Boundary

Payment and booking must remain separate domains.

The relationship is:

```text
Booking
   │
   └── may have payment
```

not:

```text
Booking = Payment
```

A payment adapter should not own booking business rules.

A booking action may call payment services when required.

---

# 27. Subscription Architecture

Subscription is a platform capability.

Conceptually:

```text
Plan
  ↓
Subscription
  ↓
Tenant Entitlement
  ↓
Feature Authorization
```

Feature gating should be centralized enough to prevent different controllers from interpreting plan rules differently.

---

# 28. Analytics Architecture

Analytics should derive from authoritative operational records.

The architecture should avoid maintaining multiple conflicting "truth" systems.

Example:

```text
Bookings
Payments
Customers
Schedules
      ↓
Analytics Queries
      ↓
Dashboard / Reports
```

Analytics data should not independently redefine booking state.

---

# 29. Caching Architecture

Caching is allowed only where cache invalidation is well-defined.

Current booking-related caching already exists.

The target rules are:

1. Cache read-heavy data where useful.
2. Define cache keys consistently.
3. Include tenant identity in tenant-scoped keys.
4. Invalidate cache when underlying state changes.
5. Never use stale cache as the final authority for booking correctness.
6. Never sacrifice booking integrity for a cache optimization.

For availability:

```text
Database
    ↓
authoritative state

Cache
    ↓
performance optimization
```

not:

```text
Cache
    ↓
source of truth
```

---

# 30. Database Architecture

BookQu uses a relational database.

Primary domain entities include:

```text
users
tenants
services
schedules
bookings
payments
subscriptions
plans
reviews
customers / customer data
```

Supporting entities include:

```text
categories
staff
resources
additional_items
vouchers
assets
customer_notes
booking_logs
refunds
notifications
owner_payouts
usage_logs
```

Database relationships must follow domain ownership and tenant isolation.

---

# 31. Database Migration Rules

Migrations are the authoritative mechanism for schema changes.

Rules:

* Every schema change requires a migration.
* Never modify an already-applied production migration merely to fix current development convenience.
* Use descriptive migration names.
* Database constraints should enforce important invariants where practical.
* Add indexes based on actual query patterns.
* Avoid storing redundant data unless there is a documented reason.
* Avoid database enums when frequent state evolution would make migrations unnecessarily fragile, unless the existing model intentionally uses them.

---

# 32. Database Naming

New database schema should use consistent English naming.

Recommended convention:

```text
snake_case
```

Examples:

```text
tenant_id
service_id
booking_id
created_at
updated_at
```

Existing legacy columns such as:

```text
idtenant
idlayanan
namalayanan
tanggalbooking
```

should not be renamed casually.

Such migrations must be treated as explicit refactoring projects because they can affect:

* models;
* relationships;
* queries;
* migrations;
* seeders;
* tests;
* views;
* existing data.

---

# 33. PHP Naming Convention

New PHP code must follow standard Laravel/PHP conventions.

Classes:

```text
PascalCase
```

Examples:

```text
BookingController
CreateBooking
BookingAvailabilityService
TenantContext
```

Methods:

```text
camelCase
```

Examples:

```text
createBooking()
resolveTenant()
calculatePrice()
```

Variables:

```text
camelCase
```

Examples:

```text
$booking
$tenantId
$serviceId
```

Constants:

```text
UPPER_SNAKE_CASE
```

---

# 34. Legacy Indonesian Variable Names

The current codebase contains legacy Indonesian variable names.

Examples may include:

```text
$namabisnis
$nomorhp
$tanggalbooking
```

New code must not introduce additional Indonesian variable naming unless a clear compatibility reason exists.

Existing names may be migrated gradually when the affected area is already being refactored.

Avoid massive rename-only changes unrelated to the feature being developed.

---

# 35. Controller Naming

Use resource/domain-oriented controller names.

Examples:

```text
OwnerBookingController
OwnerScheduleController
OwnerServiceController
CustomerBookingController
AdminDashboardController
```

Avoid generic controllers such as:

```text
PortalController
ManagerController
SystemController
GeneralController
```

when the responsibility can be named more precisely.

Existing controllers such as `OwnerProgramController` should be treated as legacy terminology and may be renamed during the relevant refactor.

---

# 36. Controller Size Guideline

There is no absolute line-count limit.

However, a controller should be considered a refactoring candidate when:

* one method performs multiple major business operations;
* business rules dominate the method;
* the controller contains repeated query logic;
* payment logic is embedded directly in it;
* transaction orchestration is repeated;
* the controller becomes difficult to test independently.

The goal is responsibility clarity, not arbitrary line limits.

---

# 37. View Architecture

Blade is the presentation layer.

Views should primarily handle:

* rendering;
* presentation conditions;
* forms;
* user interaction state.

Views should not perform:

* database queries;
* major business decisions;
* payment-provider calls;
* booking mutation;
* authorization logic that should occur server-side.

---

# 38. Blade Component Rules

Repeated UI elements should be extracted into reusable Blade components.

Examples:

```text
components/owner/
components/customer/
components/shared/
```

Good candidates include:

```text
Modal
Sidebar
Topbar
StatCard
PageHeader
Table
Pagination
FormField
StatusBadge
EmptyState
ConfirmationDialog
```

A component should encapsulate reusable presentation behavior.

---

# 39. Page View Rules

A page should primarily compose sections and components.

Avoid very large files that contain:

```text
page
+
all modals
+
all forms
+
all repeated cards
+
all JavaScript
+
all business logic
```

Instead:

```text
Page
├── Header
├── Filters
├── Data Section
├── Modal Components
├── Form Components
└── Page-specific interaction
```

---

# 40. Alpine.js Rules

Alpine.js is appropriate for localized client-side interaction.

Good use cases:

```text
Modal visibility
Dropdown state
Tabs
Local form interaction
UI toggles
Small interactive components
```

Avoid using Alpine state as the authoritative source for business state.

The server remains authoritative for:

```text
booking availability
payment status
subscription state
authorization
tenant identity
```

---

# 41. JavaScript Rules

JavaScript should enhance the UI.

It must not bypass server-side business rules.

Any important validation performed in JavaScript must also be validated server-side.

---

# 42. Route Architecture

Routes should be declarative.

A route should primarily define:

```text
HTTP method
URI
Controller/action
Middleware
Route name
```

Avoid putting business logic directly into route closures for production functionality.

Route groups should be used for:

```text
authentication
role
tenant
owner
customer/public tenant
admin
webhook
```

---

# 43. Route Naming

Use consistent route naming.

Recommended pattern:

```text
owner.bookings.index
owner.bookings.store
owner.bookings.update
owner.bookings.cancel

owner.services.index
owner.services.store
owner.services.update

customer.booking.service
customer.booking.date
customer.booking.time
```

Legacy aliases may remain temporarily when required for compatibility.

New route aliases must not be added without a clear reason.

---

# 44. Request Validation

Validation should occur before business operations.

Preferred order:

```text
Request
  ↓
Form Request validation
  ↓
Authorization
  ↓
Action
```

Do not rely on client-side validation as the primary security mechanism.

---

# 45. Authorization

Authorization must happen on the server.

Use the appropriate mechanism:

```text
Middleware
Policies
Authorization checks
Tenant authorization
Subscription/entitlement checks
```

Do not rely on hidden UI elements as authorization.

---

# 46. Error Handling

Expected business errors should be handled predictably.

Examples:

```text
Invalid booking
Unavailable schedule
Unauthorized tenant
Expired payment
Invalid voucher
Expired subscription
Invalid state transition
```

Errors should return an appropriate:

* redirect;
* validation error;
* HTTP response;
* user-facing message.

Do not expose internal exception details to end users.

---

# 47. Transactions

Database transactions should be used when an operation changes multiple related records and partial completion would cause inconsistent state.

Examples:

```text
Create multi-slot booking
Process successful payment
Cancel booking and release related state
Create subscription payment result
Create walk-in booking with related records
```

Transactions should not be added mechanically to every query.

---

# 48. Events

Events should be used when a domain occurrence has multiple independent consequences.

Example:

```text
BookingCreated
    ├── notification
    ├── logging
    └── analytics side effect
```

Events should not be introduced merely to make simple code appear more sophisticated.

---

# 49. Notifications and Side Effects

Side effects such as:

* sending email;
* creating notifications;
* logging external integration events;

should be separated from the core transaction where possible.

A booking operation should first establish the correct business state.

Then asynchronous or secondary effects can occur safely.

---

# 50. Queues and Background Jobs

Background jobs are appropriate for work that:

* is slow;
* can run asynchronously;
* does not need to block the HTTP response;
* may require retries.

Examples:

```text
Email delivery
Large report generation
Non-critical notification
External synchronization
```

Do not move critical booking state changes to asynchronous jobs if doing so could expose inconsistent booking state to the user.

---

# 51. Testing Architecture

Testing is part of the architecture.

BookQu should have multiple levels of testing.

```text
Unit
 ↓
Domain/Application
 ↓
Feature
 ↓
Integration
 ↓
End-to-End where justified
```

---

# 52. Unit Tests

Use unit tests for:

* pure business rules;
* calculations;
* state transition rules;
* voucher calculation;
* pricing;
* utility logic.

---

# 53. Feature Tests

Use feature tests for:

* HTTP behavior;
* authorization;
* tenant isolation;
* booking flows;
* customer management;
* owner modules;
* payment callbacks.

---

# 54. Integration Tests

Integration tests should verify important boundaries.

Examples:

```text
Booking + Schedule + Database
Booking + Payment
Tenant + Authorization
Subscription + Feature Access
```

---

# 55. Security Tests

Critical security invariants must have explicit tests.

Examples:

```text
cross-tenant access
IDOR
unauthorized role access
double booking
malicious callback
payment manipulation
token leakage
```

---

# 56. Test Naming

Tests should describe behavior.

Good:

```text
test_owner_cannot_access_other_tenant_bookings
test_customer_cannot_book_an_unavailable_schedule
test_duplicate_payment_callback_does_not_duplicate_booking_state
```

Avoid vague names:

```text
test_booking
test_function
test_feature
test_it_works
```

---

# 57. Refactoring Strategy

Refactoring must preserve product behavior unless the refactoring is explicitly intended to change behavior.

The correct sequence is:

```text
Existing Behavior
      ↓
Characterization Tests
      ↓
Small Refactor
      ↓
Run Tests
      ↓
Small Refactor
      ↓
Run Tests
```

Do not combine:

```text
architecture refactor
+
product redesign
+
database redesign
+
UI redesign
```

into one uncontrolled change.

---

# 58. Refactoring Priority

Current refactoring should prioritize:

```text
1. Booking domain
2. Payment integration boundary
3. Schedule / availability logic
4. Owner controllers
5. Large Blade views
6. Route organization
7. Legacy terminology
8. Shared traits / utility cleanup
```

The order may change based on risk and active development.

---

# 59. Refactoring Rule: Behavior First

Before refactoring a legacy module:

1. Understand what it currently does.
2. Identify the requirements it implements.
3. Identify relevant tests.
4. Identify important edge cases.
5. Extract responsibility.
6. Keep behavior stable.
7. Run tests.
8. Document architectural changes if significant.

---

# 60. Refactoring Rule: Do Not Rewrite Everything

Do not replace working code simply because it is not architecturally ideal.

The target architecture should be reached incrementally.

Use a "refactor while touching" strategy when practical:

```text
Feature change
    ↓
Relevant old code touched
    ↓
Improve the affected boundary
    ↓
Keep unrelated legacy code stable
```

---

# 61. Architecture Decision Records

Significant technical decisions should be documented in:

```text
docs/adr/
```

Examples include:

```text
ADR-001 Multi-Tenancy Strategy
ADR-002 Public Tenant URL Strategy
ADR-003 Booking Domain Structure
ADR-004 Payment Provider Boundary
ADR-005 Subscription Entitlement Strategy
```

An ADR should answer:

```text
Context
Decision
Alternatives
Consequences
```

---

# 62. Architecture Change Rule

If a change affects:

* domain boundaries;
* folder architecture;
* data ownership;
* tenant strategy;
* payment integration;
* state management;
* external integrations;

the architecture documentation should be updated.

Do not silently introduce a new architectural pattern in one feature.

---

# 63. AI Agent Architecture Rules

AI agents working on BookQu must follow these rules.

## Rule 1 — Do Not Copy Legacy Structure Blindly

Existing code is evidence of current implementation, not automatically the correct architecture.

---

## Rule 2 — Follow Target Architecture for New Code

When adding new functionality, use the target architecture.

Do not reproduce an existing anti-pattern just because an older module uses it.

---

## Rule 3 — Prefer Existing Patterns When They Are Valid

Do not introduce a new abstraction if an existing appropriate pattern already exists.

---

## Rule 4 — Do Not Create Abstractions Without Need

Do not create:

```text
Repository
Service
Factory
Interface
DTO
Event
```

merely because they appear architecturally sophisticated.

Create them when they provide a real responsibility boundary.

---

## Rule 5 — Preserve Tenant Isolation

Every tenant-owned operation must respect tenant context and authorization.

---

## Rule 6 — Business Logic Must Not Live in Blade

Blade may display business state.

Blade must not define authoritative business behavior.

---

## Rule 7 — Business Logic Must Not Be Hidden in JavaScript

Client-side code is not a trusted source of business truth.

---

## Rule 8 — Do Not Trust IDs

A user-provided:

```text tenant_id
booking_id
service_id
schedule_id
payment_id
```

must never be treated as authorization by itself.

---

## Rule 9 — Reuse Domain Logic

Do not implement booking validation separately for:

```text customer booking
walk-in booking
reschedule
API-like endpoint
admin operation
```

when the same domain rule applies.

---

## Rule 10 — Update Tests

Behavioral changes require corresponding test updates.

---

# 64. Forbidden Architecture Patterns

The following patterns are discouraged or prohibited for new code.

## 64.1 Fat Controller

Do not place a complete business workflow into a controller method.

---

## 64.2 God Service

Do not create a single service responsible for unrelated modules.

---

## 64.3 God Model

Do not turn one model into the entire application.

---

## 64.4 Generic Helper

Do not create generic helper files that become dumping grounds.

---

## 64.5 View-Level Database Access

Do not perform database queries directly in Blade.

---

## 64.6 Client-Side Authorization

Do not rely on UI visibility to enforce access.

---

## 64.7 Duplicated Business Rules

Do not copy the same booking/schedule/payment rule across multiple controllers.

---

## 64.8 Silent Architectural Drift

Do not introduce a new structural pattern without documenting why it exists.

---

# 65. Code Organization Principle

The preferred dependency direction is:

```text
Presentation
    ↓
Application
    ↓
Domain
    ↓
Infrastructure / Persistence
```

Lower-level infrastructure must not force product-level concepts into the UI.

For example:

```text
Midtrans
```

must not define what a booking means.

BookQu's booking domain defines the business behavior.

Midtrans is an integration detail.

---

# 66. Dependency Rule

A module should depend on a more stable concept rather than a more volatile implementation detail.

For example:

```text
Booking Application
    ↓
Payment Capability
    ↓
Midtrans Adapter
```

instead of:

```text
Booking Controller
    ↓
Midtrans SDK directly
```

This makes provider replacement and testing easier.

---

# 67. Domain Boundary Rule

The most important BookQu boundaries are:

```text
Booking
Schedule
Service
Payment
Subscription
Tenant
Customer
```

Supporting modules may interact with these domains but should not redefine them.

---

# 68. Ownership Rule

Every piece of data should have an identifiable owner.

Example:

```text
Service
→ Tenant

Schedule
→ Service / Tenant

Booking
→ Tenant / Service / Schedule / Customer context

Payment
→ Tenant / Business purpose

Subscription
→ Tenant / Plan
```

If ownership is ambiguous, the model should be reviewed before implementing additional logic.

---

# 69. Shared Code Rule

Shared code must be genuinely shared.

Do not move unrelated logic into a shared class merely because two files appear similar.

Shared abstractions should emerge from repeated stable behavior.

---

# 70. Utility Rule

Utility code should remain small and focused.

If a utility begins making business decisions, it likely belongs in:

```text
Domain
or
Application
```

instead of:

```text
Support
or
Helper
```

---

# 71. View Data Rule

Controllers or application services should prepare the data required by views.

Views should not reconstruct domain data through complicated queries or business calculations.

---

# 72. API Boundary

BookQu currently operates primarily as a server-rendered web application.

An API should only be introduced when an actual product requirement requires it.

Do not build a complete REST API solely for architectural appearance.

If an API is introduced later, it must reuse the same application/domain operations rather than duplicating business logic.

---

# 73. External Integration Boundary

Every external provider should have a clear boundary.

Examples:

```text
Payment Provider
Email Provider
Storage Provider
Calendar Provider
Messaging Provider
```

The application should not become tightly coupled to provider-specific behavior.

---

# 74. Configuration Rule

Environment-specific configuration belongs in:

```text
.env
config/
```

Business behavior must not depend directly on environment variables scattered throughout controllers.

Preferred pattern:

```text
Environment
    ↓
Configuration
    ↓
Application / Service
```

---

# 75. Security Boundary Rule

The following are security-sensitive boundaries:

```text
Authentication
Authorization
Tenant Resolution
Tenant Scope
Booking Management Token
Payment Callback
Subscription Entitlement
File Upload
External Integrations
```

Changes to these areas require extra review and tests.

---

# 76. File Upload Architecture

User-provided assets must:

* be validated;
* be stored through the configured storage mechanism;
* belong to the correct tenant;
* not allow unauthorized path manipulation;
* use appropriate file type/size restrictions.

File paths should not be treated as authorization.

---

# 77. Logging Rule

Logs should help answer:

```text
What happened?
When?
For which tenant?
For which entity?
Why did it fail?
```

Logs must not expose sensitive credentials or payment secrets.

---

# 78. Performance Architecture Rule

Performance work should begin with evidence.

Do not add:

```text
cache
queue
repository
Redis
complex optimization
```

merely because the system may someday need it.

Preferred process:

```text
Measure
   ↓
Identify bottleneck
   ↓
Optimize
   ↓
Measure again
```

---

# 79. Scalability Architecture Rule

Scalability should be achieved primarily through clear boundaries.

The first priority is:

```text
correct domain boundaries
+
efficient queries
+
proper indexes
+
safe transactions
+
controlled external integrations
```

Infrastructure scaling can follow once application architecture supports it.

---

# 80. Current-to-Target Migration Strategy

The BookQu codebase should not be rewritten in one step.

The migration should occur incrementally.

---

## Phase 1 — Documentation Alignment

Create and stabilize:

```text
PRODUCT.md
REQUIREMENTS.md
ARCHITECTURE.md
DEVELOPMENT.md
TRACKER.md
AGENTS.md
```

---

## Phase 2 — Characterize Existing Behavior

Identify:

```text
Requirement
+
Current implementation
+
Current test
```

for critical modules.

---

## Phase 3 — Stabilize Critical Domains

Prioritize:

```text
Tenant
Service
Schedule
Booking
Payment
Subscription
```

---

## Phase 4 — Extract Application Actions

Start with the most complex operations.

Examples:

```text
CreateBooking
CreateWalkInBooking
CancelBooking
RescheduleBooking
ProcessBookingPayment
ProcessSubscriptionPayment
```

---

## Phase 5 — Refactor Controllers

Move business workflows out of oversized controllers.

Keep controllers focused on HTTP concerns.

---

## Phase 6 — Refactor Blade

Extract repeated components and large UI sections.

Do not change product behavior unnecessarily.

---

## Phase 7 — Normalize Terminology

Gradually migrate legacy naming such as:

```text
Program
→ Service
```

where doing so is safe and useful.

---

## Phase 8 — Improve Database Consistency

Only after the application layer is stable should major naming/schema refactors be considered.

---

# 81. Refactoring Safety Rule

Every substantial refactor must have one of the following:

```text
Existing tests
or
Characterization test
or
Explicit manual verification procedure
```

No large refactor should depend solely on visual inspection.

---

# 82. Architecture Completeness

The target architecture is considered sufficiently implemented when:

```text
HTTP responsibilities are clear
+
business operations are explicit
+
tenant isolation is centralized
+
payment integration is isolated
+
critical domain rules are testable
+
large controllers are reduced
+
large views are decomposed
+
new features follow the same architecture
```

It does not require the repository to be perfectly abstract.

---

# 83. Definition of Architectural Completion

A module is architecturally considered healthy when:

* responsibilities are clear;
* dependencies are understandable;
* business rules are not duplicated;
* tenant isolation is preserved;
* tests can exercise important behavior;
* external integrations are isolated;
* adding another feature does not require modifying unrelated modules;
* future contributors can understand the module without reconstructing the entire application.

---

# 84. Architecture Change Checklist

Before merging an architectural change, check:

```text
[ ] Product behavior remains aligned with PRODUCT.md
[ ] Requirement remains aligned with REQUIREMENTS.md
[ ] Tenant isolation is preserved
[ ] Authorization remains correct
[ ] Existing critical tests pass
[ ] New behavior has tests
[ ] No business logic was added to Blade
[ ] No unnecessary logic was added to controllers
[ ] No unnecessary abstraction was introduced
[ ] External integrations remain isolated
[ ] Database changes have migrations
[ ] Architecture documentation updated if needed
[ ] Relevant ADR created if the decision is significant
[ ] TRACKER.md updated
```

---

# 85. Final Architecture Principles

BookQu follows these architectural principles:

```text
1. Product behavior comes before implementation.
2. Controllers coordinate; they do not own the whole business.
3. Business rules should have identifiable owners.
4. Booking is the central operational domain.
5. Tenant isolation is a security boundary.
6. Payment is an external integration boundary.
7. UI is not a source of business truth.
8. Database is not a substitute for business architecture.
9. Do not introduce abstraction without a real need.
10. Prefer incremental refactoring over uncontrolled rewrites.
11. Existing code is evidence, not necessarily the target architecture.
12. New code follows the target architecture.
13. Important architectural decisions must be documented.
14. Tests are part of the architecture.
15. Correctness comes before optimization.
```

---

# 86. Architecture Decision Priority

When architectural concerns conflict, prioritize them in this order:

```text
1. Data correctness
2. Security / tenant isolation
3. Business-rule correctness
4. Maintainability
5. Testability
6. Performance
7. Convenience
```

Performance or coding convenience must not justify breaking tenant isolation or business correctness.

---

# 87. AI Agent Implementation Decision Flow

When an AI agent receives an implementation task, the agent should reason through:

```text
Task
 ↓
Which product capability?
 ↓
Which requirement ID?
 ↓
Which domain?
 ↓
Which application operation?
 ↓
Which HTTP entry point?
 ↓
Which persistence model?
 ↓
Which existing test?
 ↓
What new test is needed?
 ↓
Does the architecture need to change?
 ↓
Implement
```

The agent should not begin by creating a new controller or editing the nearest file without understanding the domain boundary.

---

# 88. When to Create a New Class

Create a new class when at least one of these is true:

* responsibility is independently meaningful;
* logic is reused;
* logic is complex enough to require isolation;
* logic needs independent testing;
* logic crosses multiple models;
* external integration should be isolated.

Do not create a new class solely to reduce a file by a few lines.

---

# 89. When to Refactor Existing Code

Refactor when:

```text
the feature is actively being changed
and
the existing structure blocks safe implementation
```

or when:

```text
the existing structure introduces meaningful security,
correctness, or maintainability risk.
```

Do not perform large architecture rewrites merely because a file is aesthetically unpleasant.

---

# 90. When Architecture Documentation Must Change

Update this document when introducing:

* a new major domain;
* a new application layer;
* a new external integration;
* a new tenant strategy;
* a major persistence strategy;
* a new authentication mechanism;
* a major frontend architecture;
* a new architectural pattern.

Small implementation details do not require architecture-document changes.

---

# 91. Relationship With Other Documentation

The documentation system is:

```text
AGENTS.md
    │
    ├── how agents work
    │
    ▼
docs/PRODUCT.md
    │
    ├── what BookQu is
    │
    ▼
docs/REQUIREMENTS.md
    │
    ├── what BookQu must do
    │
    ▼
docs/ARCHITECTURE.md
    │
    ├── how BookQu is structured
    │
    ▼
docs/DEVELOPMENT.md
    │
    ├── how contributors work
    │
    ▼
docs/TRACKER.md
    │
    └── implementation status
```

Architectural decisions with long-term consequences are stored in:

```text
docs/adr/
```

Historical documents are stored in:

```text
docs/archive/
```

---

# 92. Final Statement

The purpose of the BookQu architecture is not to make the codebase look sophisticated.

The purpose is to make the system:

```text
understandable
+
predictable
+
safe
+
testable
+
maintainable
+
scalable
```

BookQu should evolve from its current implementation into the target architecture incrementally while preserving the product behavior defined in:

```text
docs/PRODUCT.md
docs/REQUIREMENTS.md
```

The architecture is a means to support those requirements, not a replacement for them.
