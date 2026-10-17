# BookQu System Requirements

> **Document Status:** Current Requirement Baseline
> **Version:** 1.1
> **Authority:** Current behavioral and system requirement source of truth
> **Related Product Definition:** `docs/2-PRODUCT.md`
> **Related Architecture:** `docs/4-ARCHITECTURE.md`
> **Related System Design:** `docs/7-SYSTEM-DESIGN.md`
> **Related Development Workflow:** `docs/5-DEVELOPMENT.md`
> **Current Status:** `docs/6-TRACKER.md`
> **Last Updated:** 2026-09-30
>
> This document defines what BookQu is required to do.
>
> It represents the current accepted requirement baseline and is intended to remain stable while implementation evolves.
>
> Historical specifications do not override this document.

---

# 1. Purpose

This document translates the BookQu product definition into explicit system requirements.

The objectives are:

* establish a single functional baseline;
* establish explicit business rules;
* prevent undocumented behavior from being introduced through code;
* provide a reliable source of context for developers and AI agents;
* provide a basis for acceptance testing;
* provide requirement traceability;
* distinguish intended behavior from implementation details;
* provide a stable baseline for future development.

The requirement document answers:

> **What must BookQu do?**

It does not define detailed implementation structure.

Technical structure belongs to:

```text
docs/4-ARCHITECTURE.md
docs/7-SYSTEM-DESIGN.md
```

Development workflow belongs to:

```text
docs/5-DEVELOPMENT.md
```

Current implementation status belongs to:

```text
docs/6-TRACKER.md
```

---

# 2. Requirement Authority

The BookQu documentation authority model is responsibility-based.

```text
docs/2-PRODUCT.md
    ↓
Defines what BookQu is

docs/3-REQUIREMENT.md
    ↓
Defines what BookQu must do

docs/4-ARCHITECTURE.md
    ↓
Defines how BookQu should be structured

docs/7-SYSTEM-DESIGN.md
    ↓
Defines how the current implementation actually works

docs/5-DEVELOPMENT.md
    ↓
Defines how changes should be performed

docs/6-TRACKER.md
    ↓
Defines current implementation status
```

Requirements take precedence when the question is:

> What behavior is required?

Implementation is evidence of what currently exists.

Tests are evidence of verified behavior.

Neither source code nor historical documentation should silently redefine accepted product behavior.

---

# 3. Requirement Status

Each requirement may have one of the following statuses.

| Status               | Meaning                                                                         |
| -------------------- | ------------------------------------------------------------------------------- |
| `Baseline`           | Accepted as part of the current product requirement                             |
| `Implemented`        | Implemented in the current codebase                                             |
| `Verified`           | Implemented and verified by appropriate evidence                                |
| `Needs Verification` | Intended requirement exists, but sufficient verification is not yet established |
| `Planned`            | Accepted requirement not yet implemented                                        |
| `Deprecated`         | No longer part of the current product                                           |
| `Proposed`           | Suggested change that has not yet been accepted                                 |

A requirement must not be marked `Verified` merely because a UI screen or code path exists.

Verification requires evidence appropriate to the requirement.

---

# 4. Requirement ID Convention

Every functional requirement must have a stable identifier.

Functional requirements use:

```text
FR-{DOMAIN}-{NUMBER}
```

Examples:

```text
FR-AUTH-001
FR-TENANT-001
FR-SERVICE-001
FR-SCHEDULE-001
FR-BOOKING-001
FR-PAYMENT-001
```

Business rules use:

```text
BR-{DOMAIN}-{NUMBER}
```

Non-functional requirements use:

```text
NFR-{DOMAIN}-{NUMBER}
```

Acceptance criteria use:

```text
AC-{REQUIREMENT-ID}-{NUMBER}
```

Example:

```text
FR-BOOKING-003
AC-FR-BOOKING-003-01
AC-FR-BOOKING-003-02
```

Existing requirement IDs should remain stable.

Renumbering requires a deliberate documentation change because other documents, tests, and implementation references may depend on them.

---

# 5. Actors

BookQu has three primary actors.

## 5.1 Owner

The business owner who operates a tenant.

The owner can manage business operations and access the Owner Portal.

---

## 5.2 Customer

A person making or managing a booking.

A customer does not require a normal BookQu owner account for the public booking flow.

---

## 5.3 Platform Admin

A platform-level administrator responsible for permitted BookQu platform operations.

The admin is separate from a tenant owner.

Platform-level permissions must not be treated as an extension of normal owner permissions.

---

# 6. Product Scope

The current requirement baseline covers:

```text
Core Operations
├── Authentication
├── Tenant / Business Setup
├── Public Business Page
├── Service Management
├── Schedule Management
├── Booking
├── Customer Management
├── Booking Payment
├── Dashboard
└── Calendar

Supporting Operations
├── Categories
├── Staff & Resources
├── Additional Items
├── Vouchers
├── Reviews
├── Analytics
├── Schedule Reports
├── Assets
├── Appearance
└── Notifications

Platform Capabilities
├── Subscription
├── Plans
├── Trial
├── Feature Entitlements
├── Subscription Payment
├── Multi-Tenancy
└── Platform Administration
```

A future capability is not automatically part of the requirement baseline merely because implementation or experimentation exists.

New product capabilities require explicit requirement acceptance.

---

# 7. Authentication Requirements

## FR-AUTH-001 — Owner Registration

The system shall allow a prospective owner to create a BookQu account.

The registration process shall collect the information required by the current onboarding flow.

---

## FR-AUTH-002 — Owner Login

The system shall allow a registered owner to authenticate using supported credentials.

Authentication failure shall not grant access to protected owner functionality.

---

## FR-AUTH-003 — Owner Logout

The system shall allow an authenticated owner to terminate the current authenticated session.

---

## FR-AUTH-004 — Email Verification

The system shall support email verification where required by the authentication flow.

An account requiring verification shall not be treated as fully verified before the verification requirement is satisfied.

---

## FR-AUTH-005 — Role-Based Access

The system shall distinguish at least:

```text
owner
admin
```

A user must not access functionality outside the permissions associated with the user's role.

---

## FR-AUTH-006 — Password Security

Passwords shall never be stored as plaintext.

Passwords shall use a secure password hashing mechanism.

---

# 8. Tenant and Business Requirements

## FR-TENANT-001 — Tenant Association

The system shall associate an owner with the correct tenant/business.

---

## FR-TENANT-002 — Tenant Isolation

The system shall isolate tenant-owned operational data.

A tenant must not access another tenant's:

* services;
* schedules;
* bookings;
* customers;
* payments;
* reviews;
* assets;
* subscription data;
* other tenant-owned records.

---

## FR-TENANT-003 — Tenant Context

Tenant-specific operations shall execute within the correct tenant context.

Client-provided tenant identifiers must not independently establish authorization.

---

## FR-TENANT-004 — Business Profile

The owner shall be able to manage business information used by the public booking page and owner portal.

Business information may include:

* business name;
* business type;
* phone number;
* address;
* description;
* contact information;
* operational information.

---

## FR-TENANT-005 — Business Slug

The system shall provide a unique public slug for each tenant where the slug is used as the default public identity.

---

## FR-TENANT-006 — Public Tenant Access

The system shall provide a public customer-facing page based on the tenant's public identity.

The default public access pattern is:

```text
/{tenant-slug}
```

---

## FR-TENANT-007 — Custom Domain

Where supported and configured, a tenant may expose its public page through a custom domain.

The custom domain must resolve to the correct tenant.

---

# 9. Public Business Page Requirements

## FR-PUBLIC-001 — Public Business Page

The system shall provide a public customer-facing page for an eligible tenant.

The page shall provide sufficient information for customers to understand the available booking offering.

---

## FR-PUBLIC-002 — Public Services

The public page shall display eligible active services for the tenant.

Inactive services shall not normally appear as new bookable services.

---

## FR-PUBLIC-003 — Public Branding

Where configured, the public page shall use the tenant's supported branding information.

Branding may include:

* logo;
* brand color;
* banner;
* business imagery.

---

## FR-PUBLIC-004 — Booking Entry Point

The public page shall provide a clear entry point into the customer booking flow.

---

# 10. Service Requirements

## FR-SERVICE-001 — Create Service

The owner shall be able to create a service.

A service shall contain information sufficient to make it bookable.

---

## FR-SERVICE-002 — Service Information

A service may contain:

* name;
* description;
* price;
* duration;
* capacity;
* status;
* image;
* category association where applicable.

---

## FR-SERVICE-003 — Update Service

The owner shall be able to update an existing service.

---

## FR-SERVICE-004 — Service Activation

The owner shall be able to activate or deactivate a service.

---

## FR-SERVICE-005 — Service Deactivation

A deactivated service shall not become a new publicly bookable service.

Existing historical booking records must remain valid.

---

## FR-SERVICE-006 — Service Deletion Protection

The system shall prevent destructive service deletion when doing so would violate existing booking or data-integrity constraints.

---

## FR-SERVICE-007 — Service Ownership

A service must belong to exactly one tenant.

---

# 11. Category Requirements

## FR-CATEGORY-001 — Create Category

The owner shall be able to create a service category.

---

## FR-CATEGORY-002 — Update Category

The owner shall be able to update a category.

---

## FR-CATEGORY-003 — Delete Category

The owner shall be able to delete a category where doing so does not violate relevant data constraints.

---

## FR-CATEGORY-004 — Category Status

The system shall support the relevant category active/inactive state where applicable.

---

## FR-CATEGORY-005 — Service Association

A service may be associated with an appropriate category.

Category management shall not change the fundamental identity of the service.

---

# 12. Schedule Requirements

## FR-SCHEDULE-001 — Create Schedule

The owner shall be able to create available schedules for a service.

A schedule shall identify at minimum:

* service;
* date;
* start time;
* end time;
* availability state.

---

## FR-SCHEDULE-002 — Bulk Schedule Creation

The owner shall be able to generate multiple schedules through a supported bulk operation.

---

## FR-SCHEDULE-003 — Schedule Pricing

The system shall support the accepted schedule pricing model.

Schedule-specific pricing may override a service's default price where configured.

---

## FR-SCHEDULE-004 — Availability Configuration

The owner shall be able to configure schedule availability.

---

## FR-SCHEDULE-005 — Blocked Dates

The owner shall be able to define dates that should not be available for normal booking.

---

## FR-SCHEDULE-006 — Delete Schedule

The owner shall be able to remove an eligible schedule where doing so does not violate existing reservation constraints.

---

## FR-SCHEDULE-007 — Schedule Conflict Prevention

The system shall prevent invalid overlapping or conflicting schedules where such conflicts would make the booking model ambiguous.

---

## FR-SCHEDULE-008 — Tenant Ownership

A schedule must belong to the correct tenant and service.

A tenant must not manipulate another tenant's schedules.

---

## FR-SCHEDULE-009 — Booking Availability

The system shall determine whether a schedule is currently eligible for booking.

Availability shall take relevant booking state and business rules into account.

---

## FR-SCHEDULE-010 — Past Schedule Protection

Schedules that are no longer valid for a new booking must not be presented as normally selectable customer booking slots.

---

# 13. Customer Booking Requirements

## FR-BOOKING-001 — Service Selection

A customer shall be able to select an eligible service from the tenant's public booking page.

---

## FR-BOOKING-002 — Date Selection

A customer shall be able to select an eligible booking date.

---

## FR-BOOKING-003 — Time Selection

A customer shall be able to select an available schedule.

---

## FR-BOOKING-004 — Customer Information

The customer shall provide the information required to create the booking.

This may include:

* name;
* phone number;
* email;
* booking notes where applicable.

---

## FR-BOOKING-005 — Booking Creation

The system shall create a structured booking record after the applicable booking process is completed.

---

## FR-BOOKING-006 — Booking Code

Each booking shall have a unique booking identifier or booking code suitable for customer-facing management.

---

## FR-BOOKING-007 — Booking Status

The system shall maintain the booking lifecycle.

The current booking status model is:

```text
pending
paid
cancelled
completed
```

Future status additions require an explicit requirement change.

---

## FR-BOOKING-008 — Booking Detail

The owner shall be able to view booking details.

Booking details shall provide relevant customer, service, schedule, payment, and operational information.

---

## FR-BOOKING-009 — Booking Status Management

The owner shall be able to perform supported booking status transitions.

Invalid state transitions shall be rejected.

---

## FR-BOOKING-010 — Walk-In Booking

The owner shall be able to create a booking on behalf of a customer who books directly through the business.

Walk-in booking remains part of the same booking domain as online customer booking.

---

## FR-BOOKING-011 — Owner Reschedule

The owner shall be able to reschedule an eligible booking to another available schedule.

---

## FR-BOOKING-012 — Customer Reschedule

The customer shall be able to reschedule an eligible booking through the secure booking management mechanism.

Eligibility is determined by the booking policy and current state.

---

## FR-BOOKING-013 — Customer Cancellation

The customer shall be able to cancel an eligible booking through the secure booking management mechanism.

---

## FR-BOOKING-014 — Owner Cancellation

The owner shall be able to cancel an eligible booking through the supported operational flow.

---

## FR-BOOKING-015 — Booking Security

A customer shall not be able to manage another customer's booking merely by changing a public booking identifier.

---

## FR-BOOKING-016 — Booking Management Authorization

Customer booking management shall require an appropriate valid authorization mechanism.

---

## FR-BOOKING-017 — Booking Availability Protection

A booking shall not be created against a schedule that is no longer available.

---

## FR-BOOKING-018 — Double Booking Protection

The system shall prevent two incompatible booking operations from successfully occupying the same exclusive schedule.

---

## FR-BOOKING-019 — Tenant Validation

A booking must be associated with the correct tenant, service, and schedule.

Cross-tenant references must be rejected.

---

# 14. Multi-Slot Booking Requirements

## FR-MULTIBOOK-001 — Multi-Slot Selection

The system shall support eligible reservations consisting of multiple compatible schedule slots.

---

## FR-MULTIBOOK-002 — Slot Compatibility

Selected slots within one multi-slot reservation must satisfy the applicable compatibility rules.

Where the booking policy requires contiguous slots, non-contiguous slots shall be rejected.

---

## FR-MULTIBOOK-003 — Unified Payment

Eligible multi-slot bookings shall use one customer payment transaction when the booking flow treats them as one reservation payment group.

---

## FR-MULTIBOOK-004 — Unified Invoice

The customer-facing invoice shall represent all schedules belonging to the same payment group.

---

## FR-MULTIBOOK-005 — Multi-Slot Cancellation

The system shall apply the applicable cancellation policy consistently to all schedules belonging to the reservation.

---

## FR-MULTIBOOK-006 — Multi-Slot Reschedule

The system shall apply the applicable rescheduling policy to a multi-slot reservation.

Unsupported operations must be rejected rather than partially applied.

---

# 15. Customer Management Requirements

## FR-CUSTOMER-001 — Customer Record

The system shall maintain customer information associated with bookings.

---

## FR-CUSTOMER-002 — Customer Directory

The owner shall be able to view customers belonging to the owner's tenant.

---

## FR-CUSTOMER-003 — Booking History

The owner shall be able to access relevant customer booking history.

---

## FR-CUSTOMER-004 — Customer Notes

Where supported, the owner shall be able to maintain internal notes associated with a customer.

Customer notes must remain tenant-isolated.

---

## FR-CUSTOMER-005 — Customer Privacy

Customer information shall only be accessible to authorized users and permitted customer-facing flows.

---

# 16. Payment Requirements

## FR-PAYMENT-001 — Booking Payment

The system shall support payment for eligible customer bookings.

---

## FR-PAYMENT-002 — Payment Provider

The current supported online payment provider is Midtrans.

---

## FR-PAYMENT-003 — Payment Status

The system shall maintain payment state independently from booking state.

The current payment status model is:

```text
pending
sukses
gagal
```

Payment expiration is a business condition and shall not be represented as an additional payment status unless explicitly introduced by a future requirement.

---

## FR-PAYMENT-004 — Payment Reference

Each external payment transaction shall maintain the relevant external reference required for reconciliation.

---

## FR-PAYMENT-005 — Payment Verification

The system shall not mark a payment as successful solely because a client-side request claims that payment succeeded.

Payment success shall rely on a trusted verification mechanism.

---

## FR-PAYMENT-006 — Payment Webhook

The system shall support payment-provider callback/webhook processing where required.

---

## FR-PAYMENT-007 — Webhook Idempotency

Repeated delivery of the same payment-provider event shall not create duplicate financial side effects.

---

## FR-PAYMENT-008 — Payment Expiration

An expired payment shall be handled as a failed payment outcome for payment state purposes.

Expiration may additionally affect the associated booking and schedule according to the booking expiration rules.

---

## FR-PAYMENT-009 — Booking Synchronization

Where payment determines booking eligibility, the system shall synchronize the booking state according to the defined booking and payment rules.

---

## FR-PAYMENT-010 — Failed Payment Protection

A failed or expired payment shall not leave a booking in an incorrectly confirmed payment state.

---

## FR-PAYMENT-011 — Refund Record

Where a customer cancellation or other supported operation requires a refund, the system shall maintain a refund record.

Supported refund states are:

```text
pending
processed
failed
```

Refund state is separate from both booking state and payment state.

---

# 17. Booking Management Without Account

## FR-MANAGE-001 — Secure Booking Access

A customer shall be able to access eligible booking management functionality without requiring a normal BookQu account.

---

## FR-MANAGE-002 — Secure Access Token

The system shall use secure tokenized authorization for customer booking management.

---

## FR-MANAGE-003 — Booking Details

The customer shall be able to view eligible booking details through an authorized management link.

---

## FR-MANAGE-004 — Payment Information

The customer shall be able to access applicable payment and invoice information through valid authorization.

---

## FR-MANAGE-005 — Cancellation Authorization

Customer cancellation shall require the valid cancellation authorization associated with the booking.

---

## FR-MANAGE-006 — Reschedule Authorization

Customer rescheduling shall require the valid rescheduling authorization associated with the booking.

---

## FR-MANAGE-007 — Review Authorization

Review submission shall require valid authorization for the eligible booking.

---

# 18. Additional Item Requirements

## FR-ADDON-001 — Create Additional Item

The owner shall be able to create an additional item.

---

## FR-ADDON-002 — Update Additional Item

The owner shall be able to update an additional item.

---

## FR-ADDON-003 — Activate or Deactivate Additional Item

The owner shall be able to control whether an additional item is currently offered.

---

## FR-ADDON-004 — Service Association

An additional item may be associated with eligible services.

---

## FR-ADDON-005 — Booking Integration

Where enabled, a customer shall be able to select eligible additional items during checkout.

---

# 19. Voucher Requirements

## FR-VOUCHER-001 — Create Voucher

The owner shall be able to create promotional vouchers.

---

## FR-VOUCHER-002 — Voucher Rule Configuration

The owner shall be able to configure supported voucher rules such as:

* code;
* discount type;
* discount value;
* active period;
* minimum transaction;
* usage limitation.

---

## FR-VOUCHER-003 — Voucher Validation

The system shall validate voucher eligibility before applying the discount.

---

## FR-VOUCHER-004 — Voucher Tenant Isolation

A voucher belonging to one tenant shall not be usable by another tenant.

---

## FR-VOUCHER-005 — Voucher Calculation

The system shall calculate the resulting booking price according to the accepted voucher rules.

---

# 20. Staff and Resource Requirements

## FR-STAFF-001 — Staff Management

The owner shall be able to create, update, deactivate, and delete eligible staff records.

---

## FR-RESOURCE-001 — Resource Management

The owner shall be able to create, update, deactivate, and delete eligible resource records.

---

## FR-STAFFRESOURCE-001 — Association

The system shall support association between services and eligible staff/resources where configured.

---

## FR-STAFFRESOURCE-002 — Operational Constraint

Inactive staff or resources must not be used by booking flows that require active operational resources.

---

## FR-STAFFRESOURCE-003 — Customer Selection Boundary

Staff/resource management does not automatically imply customer selection during booking.

Customer-facing staff/resource selection requires an explicit product requirement.

---

# 21. Review Requirements

## FR-REVIEW-001 — Review Eligibility

Only eligible bookings may generate a customer review.

---

## FR-REVIEW-002 — Review Submission

The customer shall be able to submit a review for an eligible booking.

---

## FR-REVIEW-003 — Review Rating

The system shall support a rating value within the accepted rating range.

---

## FR-REVIEW-004 — Review Comment

The customer may provide a textual comment where supported.

---

## FR-REVIEW-005 — Review Ownership

A review must remain associated with the correct tenant and booking.

---

## FR-REVIEW-006 — Review Management

The owner shall be able to manage supported review visibility or response behavior.

---

## FR-REVIEW-007 — One Review Per Eligible Booking

The system should prevent duplicate review creation for the same eligible booking unless a future requirement explicitly permits multiple reviews.

---

# 22. Dashboard Requirements

## FR-DASH-001 — Business Overview

The owner shall be able to view a business overview dashboard.

---

## FR-DASH-002 — Booking Metrics

The dashboard shall present relevant booking metrics.

---

## FR-DASH-003 — Revenue Metrics

The dashboard shall present relevant revenue or payment-related metrics based on recorded data.

---

## FR-DASH-004 — Customer Metrics

The dashboard shall present relevant customer activity metrics.

---

## FR-DASH-005 — Service Metrics

The dashboard may present service-related performance information.

---

## FR-DASH-006 — Recent Activity

The dashboard shall provide access to recent or upcoming operational activity where applicable.

---

## FR-DASH-007 — Dashboard Integrity

Dashboard figures shall derive from authoritative operational data.

Dashboard aggregation must not become an independent source of truth.

---

# 23. Calendar Requirements

## FR-CALENDAR-001 — Calendar View

The owner shall be able to view booking and schedule information through a calendar interface.

---

## FR-CALENDAR-002 — Date Navigation

The calendar shall support navigation across relevant dates.

---

## FR-CALENDAR-003 — Booking Visibility

The calendar shall show relevant booking information.

---

## FR-CALENDAR-004 — Schedule Visibility

The calendar shall show relevant schedule availability.

---

## FR-CALENDAR-005 — Calendar Filtering

Where supported, the calendar shall allow filtering by relevant operational dimensions.

---

## FR-CALENDAR-006 — Walk-In Operation

The owner shall be able to initiate supported walk-in booking or operational actions from the calendar where provided by the current product flow.

---

# 24. Analytics Requirements

## FR-ANALYTICS-001 — Operational Analytics

The system shall provide owner-facing operational analytics based on booking and business activity data.

---

## FR-ANALYTICS-002 — Booking Analysis

The system shall provide relevant booking metrics.

---

## FR-ANALYTICS-003 — Revenue Analysis

The system shall provide relevant revenue-related metrics where payment data is available.

---

## FR-ANALYTICS-004 — Service Analysis

The system may provide service performance metrics.

---

## FR-ANALYTICS-005 — Schedule Utilization

The system shall support schedule utilization analysis where sufficient data is available.

---

## FR-ANALYTICS-006 — Analytics Data Integrity

Analytics must derive from the same authoritative operational records used by the booking system.

---

# 25. Reporting Requirements

## FR-REPORT-001 — Schedule Report

The owner shall be able to access reports concerning schedule and booking performance.

---

## FR-REPORT-002 — Report Filtering

Where supported, the owner shall be able to filter reports by relevant time periods or operational dimensions.

---

## FR-REPORT-003 — Report Export

The system shall support export of supported report data.

---

# 26. Appearance Requirements

## FR-APPEARANCE-001 — Logo

The owner shall be able to configure the business logo where supported.

---

## FR-APPEARANCE-002 — Brand Color

The owner shall be able to configure supported brand colors.

---

## FR-APPEARANCE-003 — Banner / Cover

The owner shall be able to configure supported cover or banner imagery.

---

## FR-APPEARANCE-004 — Public Presentation

Appearance configuration shall affect public presentation without modifying core booking behavior.

---

# 27. Asset Requirements

## FR-ASSET-001 — Asset Storage

The system shall provide a mechanism for storing supported business media assets.

---

## FR-ASSET-002 — Asset Ownership

Assets shall belong to the correct tenant.

---

## FR-ASSET-003 — Asset Management

Authorized owners shall be able to add and remove supported assets.

---

## FR-ASSET-004 — Asset Usage

Eligible assets may be used by the tenant's public-facing pages.

---

# 28. Notification Requirements

## FR-NOTIFICATION-001 — Booking Notification

The system shall support owner notification for relevant booking events.

---

## FR-NOTIFICATION-002 — Booking Status Notification

The system shall support notifications for relevant booking state changes.

---

## FR-NOTIFICATION-003 — Payment Notification

The system may notify the relevant actor about important payment events.

---

## FR-NOTIFICATION-004 — Subscription Notification

The system shall support relevant subscription lifecycle notifications where applicable.

---

## FR-NOTIFICATION-005 — Notification Isolation

Owner notifications must only represent events belonging to the owner's tenant or authorized account.

---

# 29. Subscription Requirements

## FR-SUB-001 — Subscription Plans

The platform shall support subscription plan definitions.

---

## FR-SUB-002 — Tenant Subscription

A tenant shall have a subscription state associated with a plan.

---

## FR-SUB-003 — Trial

The platform may provide a trial period for eligible new tenants according to the accepted subscription policy.

---

## FR-SUB-004 — Subscription Status

The system shall maintain subscription status.

Current conceptual subscription states include:

```text
trial
active
expired
cancelled
```

---

## FR-SUB-005 — Subscription Access Control

The platform shall be able to restrict access to features based on subscription entitlement.

---

## FR-SUB-006 — Feature Entitlement

Feature access shall be determined using the tenant's current subscription state and plan configuration.

---

## FR-SUB-007 — Subscription Payment

The owner shall be able to make supported payments for the BookQu subscription.

---

## FR-SUB-008 — Payment Callback

The system shall process trusted subscription payment status updates.

---

## FR-SUB-009 — Subscription Lifecycle

The system shall handle supported subscription lifecycle transitions.

---

## FR-SUB-010 — Usage Limits

Where a plan includes usage limits, the system shall prevent the tenant from exceeding the accepted limit.

---

## FR-SUB-011 — Usage Tracking

The system shall maintain the information necessary to evaluate supported usage limits.

---

# 30. Platform Administration Requirements

## FR-ADMIN-001 — Admin Authentication

The platform admin shall authenticate using an authorized admin account.

---

## FR-ADMIN-002 — Admin Dashboard

The system shall provide a platform-level dashboard for supported administration metrics.

---

## FR-ADMIN-003 — Tenant Oversight

The admin may access permitted platform-level tenant information.

The admin view must respect the platform security model.

---

## FR-ADMIN-004 — Platform Boundary

Platform administration must remain separate from ordinary owner permissions.

---

# 31. Data Integrity Requirements

## FR-DATA-001 — Referential Integrity

The system shall maintain valid relationships between related records.

---

## FR-DATA-002 — Tenant Integrity

Tenant-owned records must reference the correct tenant.

---

## FR-DATA-003 — Booking Integrity

A booking must reference a valid tenant, service, and schedule combination.

---

## FR-DATA-004 — Payment Integrity

Payment records must remain associated with their intended business purpose and relevant tenant.

---

## FR-DATA-005 — Atomic Critical Operations

Critical multi-record operations shall be performed atomically where partial completion would create invalid business state.

---

## FR-DATA-006 — Double Booking Prevention

The system shall provide application and/or database-level protection preventing conflicting reservations from occupying the same exclusive schedule.

---

# 32. Security Requirements

## NFR-SEC-001 — HTTPS

Production communication shall use HTTPS/TLS.

---

## NFR-SEC-002 — Password Hashing

Passwords shall be securely hashed.

---

## NFR-SEC-003 — SQL Injection Protection

The system shall protect database operations against SQL injection.

---

## NFR-SEC-004 — XSS Protection

The system shall provide appropriate protection against cross-site scripting.

---

## NFR-SEC-005 — CSRF Protection

State-changing authenticated web operations shall use CSRF protection unless explicitly exempted for a trusted external callback mechanism.

---

## NFR-SEC-006 — Authorization

Every protected operation shall enforce authorization at the appropriate layer.

---

## NFR-SEC-007 — Tenant Isolation

Tenant isolation shall be treated as a security boundary.

---

## NFR-SEC-008 — IDOR Protection

User-controlled identifiers shall not be sufficient to access unauthorized tenant or booking data.

---

## NFR-SEC-009 — Payment Callback Security

External payment callbacks shall be verified before trusted financial state is updated.

---

## NFR-SEC-010 — Secret Protection

API keys, payment credentials, and application secrets must not be committed into source control.

---

# 33. Performance Requirements

The following are performance targets rather than automatically verified claims.

## NFR-PERF-001 — General Response Time

Normal operations should target a response time of approximately 2 seconds or less under expected operating conditions.

This requirement requires formal performance measurement before it can be marked verified.

---

## NFR-PERF-002 — Heavy Operations

Heavy operations such as booking and payment processing should target approximately 5 seconds or less where practical.

External payment-provider latency may be outside direct application control.

---

## NFR-PERF-003 — Availability Data Freshness

Availability information should be sufficiently fresh to prevent stale availability from producing an invalid booking.

---

## NFR-PERF-004 — Query Efficiency

The system should avoid unnecessary repeated queries, unbounded retrieval, and redundant computation in frequently accessed flows.

---

## NFR-PERF-005 — Caching

Caching may be used where it improves performance without compromising booking correctness or required data freshness.

---

# 34. Scalability Requirements

## NFR-SCALE-001 — Tenant Growth

The architecture shall support increasing numbers of tenants without requiring tenant-specific application code.

---

## NFR-SCALE-002 — User Growth

The system shall support increasing numbers of owner and customer interactions.

---

## NFR-SCALE-003 — Booking Growth

The system shall support increasing booking volume without requiring redesign of the core booking domain.

---

## NFR-SCALE-004 — Horizontal Scaling Readiness

The application architecture should remain compatible with future horizontal scaling.

This is an architectural target and requires infrastructure validation before being considered verified.

---

# 35. Reliability Requirements

## NFR-REL-001 — Transaction Consistency

Critical booking and payment operations shall not leave the system in a partially updated state.

---

## NFR-REL-002 — External Callback Resilience

External payment callbacks may be delivered more than once and must be handled safely.

---

## NFR-REL-003 — Error Recovery

Expected external-service and application failures should produce predictable recovery behavior.

---

## NFR-REL-004 — Backup

Production data should be backed up using an appropriate operational backup mechanism.

---

## NFR-REL-005 — Recovery

The production environment should have a documented recovery procedure.

Backup and recovery require operational verification.

---

# 36. Availability Requirement

## NFR-AVAIL-001 — Service Availability

The production service should target high availability.

A target of approximately 99% uptime may be used as an operational baseline.

This target must be validated through actual monitoring.

---

# 37. Usability Requirements

## NFR-UX-001 — Responsive Interface

The customer and owner interfaces shall support:

* desktop;
* tablet;
* mobile.

---

## NFR-UX-002 — Consistent Navigation

Navigation within each portal should be consistent.

---

## NFR-UX-003 — Clear Booking Flow

The customer booking process should remain understandable and predictable.

---

## NFR-UX-004 — Concise Booking Flow

The primary booking operation should remain reasonably concise.

The approximate five-step historical target should be treated as a usability target rather than a rigid technical constraint.

---

## NFR-UX-005 — Clear State

Users should be able to understand:

* what they selected;
* what is available;
* what has been booked;
* whether payment succeeded;
* what action they can take next.

---

# 38. Compatibility Requirements

## NFR-COMP-001 — Browser Support

The system shall support current mainstream browsers including:

* Chrome;
* Firefox;
* Edge;
* Safari.

---

## NFR-COMP-002 — Responsive Layout

Critical functionality shall remain usable across supported screen sizes.

---

# 39. Maintainability Requirements

## NFR-MAINT-001 — Separation of Responsibilities

Controllers, views, models, services, domain/application logic, and infrastructure shall have clear responsibilities.

Business logic shall not be unnecessarily concentrated in controllers.

---

## NFR-MAINT-002 — Modular Design

The system shall be organized so that new features can be added without unnecessary modification of unrelated modules.

---

## NFR-MAINT-003 — Reusable Components

Repeated UI and application behavior should be extracted into reusable components where appropriate.

---

## NFR-MAINT-004 — Consistent Naming

New code must follow naming conventions defined by `docs/4-ARCHITECTURE.md`.

Historical naming inconsistencies may remain temporarily but should not be propagated into new work.

---

## NFR-MAINT-005 — Documentation

Important domain, system, and architectural decisions shall be documented in their appropriate authority.

---

## NFR-MAINT-006 — Technical Debt Visibility

Known technical debt shall be recorded explicitly rather than silently treated as intended behavior.

---

# 40. Logging and Monitoring Requirements

## NFR-OBS-001 — Authentication Events

Important authentication events should be logged appropriately.

---

## NFR-OBS-002 — Booking Events

Important booking lifecycle events should be traceable.

---

## NFR-OBS-003 — Payment Events

Important payment state transitions should be traceable.

---

## NFR-OBS-004 — Application Errors

Production application errors should be observable through the configured logging or monitoring mechanism.

---

## NFR-OBS-005 — Operational Monitoring

Production performance and availability should be observable.

---

# 41. Extensibility Requirements

## NFR-EXT-001 — Payment Provider Integration

Payment integration shall be isolated sufficiently to allow future provider changes or additional providers.

---

## NFR-EXT-002 — Notification Integration

Notification delivery should be modular enough to allow future channels.

---

## NFR-EXT-003 — Feature Expansion

Future modules should be addable without unnecessarily rewriting the booking domain.

---

## NFR-EXT-004 — Multi-Tenant Expansion

New tenant-level modules must respect the established tenant isolation model.

---

# 42. Core Business Rules

The following business rules are fundamental to BookQu.

## BR-CORE-001 — Tenant Ownership

Every tenant-owned operational record must belong to a valid tenant.

---

## BR-CORE-002 — Service Ownership

A service belongs to one tenant.

---

## BR-CORE-003 — Schedule Ownership

A schedule belongs to one service and the service's tenant.

---

## BR-CORE-004 — Booking Ownership

A booking remains associated with the correct tenant throughout its lifecycle.

---

## BR-CORE-005 — Booking References

A booking must reference a valid service and eligible schedule belonging to the correct tenant.

---

## BR-CORE-006 — Availability Protection

Unavailable schedules cannot be booked.

---

## BR-CORE-007 — Double Booking Prevention

Two incompatible reservations cannot successfully occupy the same exclusive schedule.

---

## BR-CORE-008 — Payment and Booking Separation

Payment state and booking state are separate concepts.

---

## BR-CORE-009 — Customer Authorization

A customer may only manage bookings for which valid management authorization exists.

---

## BR-CORE-010 — Owner Authorization

An owner may only operate on the tenant they are authorized to manage.

---

## BR-CORE-011 — Historical Data Preservation

Deactivation or normal lifecycle transitions must not unnecessarily destroy historical booking or payment information required for traceability.

---

# 43. Booking State Rules

The current booking lifecycle is:

```text
pending
paid
cancelled
completed
```

A booking state must not be changed arbitrarily through client-provided input.

Supported transitions must be enforced by business rules.

---

# 44. Active Booking and Occupied-Slot Rules

A schedule is considered occupied according to the current booking rules.

The current occupied states are:

```text
paid
completed
```

A `pending` booking occupies a schedule only while it remains within the configured pending-payment grace period.

The current grace period is:

```text
15 minutes
```

Therefore:

```text
pending + within grace period
        ↓
occupies schedule

pending + beyond grace period
        ↓
does not remain an active occupied reservation

cancelled
        ↓
available
```

The application must not treat all historical `pending` records as permanently occupying a schedule.

---

# 45. Stale Pending Booking Rules

When a pending booking exceeds the payment grace period, it must be treated as expired for availability purposes.

Stale pending handling must not produce a false double-booking condition.

Where a new booking competes with a stale pending booking, the system must resolve the stale state within the authoritative booking operation before allowing the new reservation to proceed.

The implementation must preserve transactional safety and concurrency protection.

---

# 46. Payment State Rules

The current payment status model is:

```text
pending
sukses
gagal
```

Payment expiration is not a separate persistent payment status.

An expired payment is handled as a failed payment outcome and must trigger the applicable booking expiration behavior.

Payment state must remain distinct from booking state.

---

# 47. Payment Expiration Rules

When a payment expires:

```text
Payment
    ↓
gagal
```

and the associated pending booking must no longer remain an active confirmed reservation.

The affected schedule must become eligible according to the booking availability rules.

Payment expiration must not result in a permanently occupied schedule.

---

# 48. Refund Rules

Refunds are separate from payment status and booking status.

Supported refund states are:

```text
pending
processed
failed
```

Where an eligible paid booking is cancelled by a customer and a refund is required:

```text
Booking cancellation
        ↓
Refund record
        ↓
Refund processing
```

A duplicate refund record must not be created for the same cancellation event.

Owner cancellation must follow the defined owner-cancellation policy and must not automatically imply a refund unless the accepted requirement explicitly requires one.

---

# 49. Token Authorization Rules

Customer booking management uses scoped token authorization.

The system must distinguish the purpose of a tokenized operation.

Examples include:

```text
show / management access
invoice access
review submission
cancellation
rescheduling
```

A valid token for one purpose must not automatically authorize unrelated operations.

A token belonging to one booking must not authorize operations on another booking.

Cross-booking token use must be rejected.

---

# 50. Multi-Slot Booking Rules

Where multiple schedules form one reservation:

```text
Selected schedules
        ↓
Compatibility validation
        ↓
One reservation intent
        ↓
Payment group where applicable
        ↓
Corresponding booking records
```

The customer-facing behavior must remain coherent.

Partial success must not leave an invalid reservation state.

---

# 51. Walk-In Booking Rules

Walk-in bookings are created by authorized owners on behalf of customers.

They use the same core booking rules as online bookings.

They must respect:

* tenant isolation;
* schedule availability;
* booking constraints;
* applicable payment requirements;
* booking lifecycle rules.

---

# 52. Subscription Entitlement Rules

A tenant's restricted feature access shall be derived from:

```text
Plan
+
Subscription state
+
Applicable entitlement rules
```

Feature gating should not be implemented through inconsistent independent checks across unrelated modules.

---

# 53. Scheduler and Expiration Rules

The system has scheduled processing for payment expiration and related stale booking handling.

The payment-expiration command is expected to run periodically so that expired payment states do not remain indefinitely.

The operational scheduler must execute the registered expiration command according to the current runtime configuration.

Exact production scheduler configuration belongs in:

```text
docs/8-OPERATIONS.md
```

The requirement is behavioral:

> Expired payment and stale pending booking states must eventually be reconciled without requiring manual customer intervention.

---

# 54. Authorization Matrix

The baseline authorization model is:

| Capability               | Owner |              Customer |                           Admin |
| ------------------------ | ----: | --------------------: | ------------------------------: |
| Manage own business      |   Yes |                    No | Platform-level where authorized |
| Manage services          |   Yes |                    No | Platform-level where authorized |
| Manage schedules         |   Yes |                    No | Platform-level where authorized |
| View own tenant bookings |   Yes | Own eligible bookings | Platform-level where authorized |
| Create walk-in booking   |   Yes |                    No | Not a normal customer operation |
| Create public booking    |    No |                   Yes |                              No |
| Manage own booking       |    No |      Yes, if eligible | Platform-level where authorized |
| Manage customers         |   Yes |                    No | Platform-level where authorized |
| Manage categories        |   Yes |                    No | Platform-level where authorized |
| Manage staff/resources   |   Yes |                    No | Platform-level where authorized |
| Manage vouchers          |   Yes |                    No | Platform-level where authorized |
| Submit review            |    No |      Yes, if eligible |                              No |
| Manage subscription      |   Yes |                    No | Platform-level where authorized |
| Access admin dashboard   |    No |                    No |                             Yes |

This matrix is intentionally high-level.

Detailed authorization policy belongs to the architecture and implementation.

---

# 55. Acceptance Criteria Rules

Every implemented functional requirement should have acceptance criteria appropriate to its risk.

Acceptance criteria must describe observable behavior rather than code structure.

Bad:

```text
The controller should be clean.
```

Good:

```text
Given an owner authenticated for Tenant A,
when the owner opens the booking list,
then only bookings belonging to Tenant A are returned.
```

Architecture quality is verified through architecture criteria, tests, code review, or architectural inspection rather than pretending it is a functional requirement.

---

# 56. Global Acceptance Criteria

The following criteria apply to relevant tenant-scoped functionality.

## AC-GLOBAL-001 — Tenant Isolation

Given an owner of Tenant A, the owner must not retrieve or modify Tenant B data.

---

## AC-GLOBAL-002 — Authorization

A user without the required permission must receive an authorization failure instead of the protected operation or data.

---

## AC-GLOBAL-003 — Invalid Resource

A request for a nonexistent or inaccessible resource must not reveal another tenant's data.

---

## AC-GLOBAL-004 — Validation

Invalid input must be rejected before an invalid or destructive operation occurs.

---

## AC-GLOBAL-005 — Transaction Safety

Critical multi-record operations must either complete according to business rules or leave the system in a valid state.

---

# 57. Traceability Model

Every accepted requirement should eventually be traceable to implementation and verification.

The intended relationship is:

```text
Requirement
    ↓
Product Area
    ↓
Implementation
    ↓
Test
    ↓
Verification
```

Example:

```text
FR-BOOKING-010
    ↓
Walk-In Booking
    ↓
Booking Application Flow
    ↓
Owner Booking Tests
    ↓
Verified
```

---

# 58. Requirement Traceability

The current traceability baseline should be maintained against `docs/6-TRACKER.md`.

Representative entries include:

| Requirement      | Product Area      | Evidence                     | Status                              |
| ---------------- | ----------------- | ---------------------------- | ----------------------------------- |
| FR-AUTH-001      | Authentication    | Auth flow/tests              | Needs Verification / current status |
| FR-AUTH-002      | Authentication    | Auth tests                   | Verified where covered              |
| FR-TENANT-002    | Multi-Tenancy     | Tenant isolation tests       | Verified                            |
| FR-SERVICE-001   | Services          | Service management/tests     | Implemented                         |
| FR-SCHEDULE-001  | Schedule          | Schedule management/tests    | Implemented                         |
| FR-BOOKING-005   | Booking           | Customer booking flow/tests  | Implemented                         |
| FR-BOOKING-010   | Walk-In           | Owner booking flow/tests     | Implemented                         |
| FR-BOOKING-018   | Booking Integrity | Concurrency protection/tests | Verified                            |
| FR-PAYMENT-001   | Payment           | Midtrans payment flow/tests  | Implemented                         |
| FR-PAYMENT-007   | Payment           | Webhook tests                | Verified                            |
| FR-MULTIBOOK-001 | Multi-Slot        | Multi-slot flow/tests        | Implemented                         |
| FR-REVIEW-001    | Reviews           | Review tests                 | Implemented                         |
| FR-ANALYTICS-001 | Analytics         | Analytics flow/tests         | Implemented                         |
| FR-SUB-001       | Subscription      | Subscription module/tests    | Implemented                         |

The tracker remains the authority for current implementation status.

This requirement document remains the authority for what the requirement means.

---

# 59. Current Verification Evidence

The repository contains automated tests covering important requirement areas including:

* tenant isolation;
* schedule ownership;
* duplicate and overlapping schedule protection;
* booking ownership;
* double-booking concurrency;
* payment/webhook idempotency;
* customer isolation;
* voucher isolation;
* review isolation;
* staff/resource behavior;
* calendar;
* schedule reporting;
* booking flow;
* multi-slot payment behavior;
* booking management;
* owner modules;
* subscription behavior.

Tests provide evidence of covered behavior.

The existence of a passing test does not automatically mean every acceptance criterion for a requirement has been verified.

---

# 60. Non-Functional Verification Policy

A non-functional requirement must not be marked `Verified` without appropriate evidence.

Examples:

```text
NFR-PERF-001
→ performance measurement

NFR-SCALE-004
→ architecture/infrastructure validation

NFR-REL-004
→ backup evidence

NFR-REL-005
→ recovery exercise/documentation

NFR-AVAIL-001
→ production monitoring
```

Code inspection alone is insufficient for these claims.

---

# 61. Deprecated or Superseded Concepts

The following concepts should not be introduced as new independent domain concepts without an explicit product and requirement decision:

```text
Program as a separate domain from Service
Business as a separate domain from Tenant
Customer as a BookQu User
Schedule and Service as one entity
Payment and Booking as one lifecycle
Subdomain as the name for /tenant-slug
```

Legacy implementation may still contain these terms.

Changing legacy terminology is an architecture or implementation task, not a requirement change by itself.

---

# 62. Out of Scope

Unless explicitly accepted through product and requirement change control, the following are outside the current baseline:

```text
Full accounting / financial management
Full ERP capabilities
Full general-purpose CRM
General inventory management
Social media management
Full marketing automation
AI-generated business decisions
Unspecified external integrations
Unspecified multi-location management
Unspecified advanced payment automation
```

A new capability must not be treated as accepted simply because it is technically possible.

---

# 63. Requirement Change Control

A change to BookQu behavior must not be introduced only through source code.

When a proposed change modifies product behavior:

```text
Proposal
   ↓
Determine affected product concept
   ↓
Determine affected requirement
   ↓
Accept / reject product change
   ↓
Update docs/2-PRODUCT.md when scope changes
   ↓
Update docs/3-REQUIREMENT.md
   ↓
Update docs/4-ARCHITECTURE.md when architecture changes
   ↓
Update docs/7-SYSTEM-DESIGN.md when current implementation changes
   ↓
Update implementation
   ↓
Update tests
   ↓
Update docs/6-TRACKER.md
```

Operational changes should additionally update:

```text
docs/8-OPERATIONS.md
```

Important architectural decisions should additionally be recorded through:

```text
docs/adr/
```

where appropriate.

---

# 64. Requirement Conflict Rule

When a conflict is found between:

* source code;
* tests;
* tracker;
* product definition;
* requirements;
* architecture;
* system design;

the conflict must be explicitly identified.

An agent must not silently select whichever interpretation is easiest to implement.

The following distinction must be maintained:

```text
Product
→ What BookQu should provide.

Requirement
→ What behavior is required.

Architecture
→ How the system should be structured.

System Design
→ How the current system actually works.

Source Code
→ Current implementation.

Tests
→ Verified behavior.

Tracker
→ Current implementation status.
```

If current code conflicts with an accepted requirement, that is an implementation/documentation conflict and must be resolved intentionally.

---

# 65. Rule for AI Agents

AI agents working on BookQu must:

1. read `AGENT.md`;
2. read the relevant section of `docs/2-PRODUCT.md`;
3. identify the applicable requirement ID;
4. inspect the current system design where relevant;
5. inspect the implementation;
6. inspect relevant tests;
7. determine whether the task changes product behavior;
8. implement only within accepted scope;
9. update tests when behavior changes;
10. update documentation when the accepted behavior changes;
11. update the tracker when implementation status changes.

An AI agent must not create a new product capability merely because it appears technically useful.

An AI agent must not remove an existing capability merely because it is absent from an older specification.

When uncertain, the agent should identify the requirement conflict instead of inventing product behavior.

---

# 66. Definition of Requirement Completeness

A requirement is sufficiently defined when it specifies, where applicable:

```text
Who
↓
What
↓
Under what conditions
↓
Expected behavior
↓
Business rules
↓
Expected result
↓
Constraints
```

Example:

```text
FR-BOOKING-010

Who:
Owner

What:
Create a walk-in booking

Condition:
A customer books directly through the business

Expected behavior:
The owner creates a booking on behalf of the customer

Constraints:
The selected schedule must be available
Tenant authorization must be valid

Result:
A valid booking is created
```

---

# 67. Definition of Done for Functional Requirements

A functional requirement is considered complete when:

```text
Requirement defined
        ↓
Implementation complete
        ↓
Validation complete
        ↓
Relevant tests updated/passed
        ↓
Authorization verified
        ↓
Tenant isolation verified where applicable
        ↓
User-facing behavior verified
        ↓
Documentation synchronized
        ↓
Tracker updated
```

A functional requirement being implemented does not automatically mean its architecture is optimal.

Architectural status is tracked separately.

---

# 68. Requirement Maintenance Principle

Requirements should remain stable as long as the intended product behavior remains stable.

Implementation changes should not require requirement changes merely because:

* a class moved;
* a controller became thinner;
* an Action was introduced;
* a service was renamed;
* a cache implementation changed;
* a database optimization was introduced.

Requirement changes are justified when the intended product/system behavior changes.

---

# 69. Requirement-to-Architecture Boundary

The requirement document may state constraints that affect architecture, for example:

```text
Tenant data must remain isolated.
Booking operations must prevent double booking.
Payment state must remain distinct from booking state.
```

However, it should not prescribe the exact implementation.

For example, this is a requirement:

```text
The system must prevent double booking.
```

This is architecture/system design:

```text
The authoritative booking transaction uses
the designated availability and concurrency mechanism.
```

The distinction allows implementation to evolve without invalidating the requirement baseline.

---

# 70. Requirement-to-System-Design Boundary

`3-REQUIREMENT.md` states what behavior must hold.

`7-SYSTEM-DESIGN.md` will explain how the current implementation achieves that behavior.

Example:

```text
Requirement:

A stale pending booking must not permanently occupy a schedule.
```

System Design will explain:

```text
Current occupancy rule
Current pending grace handling
Current stale-booking eviction path
Current transactional boundary
Current cache invalidation
```

This keeps detailed implementation knowledge out of the requirement document.

---

# 71. Requirement-to-Operations Boundary

Operational requirements may exist here when they affect required system behavior.

For example:

```text
Expired payment must eventually be reconciled.
```

The detailed operational mechanism belongs in:

```text
docs/8-OPERATIONS.md
```

This distinction prevents the requirement document from becoming a production runbook.

---

# 72. Final Requirement Model

The BookQu requirement model follows:

```text
PRODUCT
   ↓
WHAT THE PRODUCT IS

REQUIREMENT
   ↓
WHAT THE SYSTEM MUST DO

ARCHITECTURE
   ↓
HOW THE SYSTEM SHOULD BE STRUCTURED

SYSTEM DESIGN
   ↓
HOW THE CURRENT SYSTEM ACTUALLY WORKS

IMPLEMENTATION
   ↓
CODE THAT REALIZES THE REQUIREMENTS

TESTS
   ↓
EVIDENCE OF VERIFIED BEHAVIOR

TRACKER
   ↓
CURRENT IMPLEMENTATION STATUS
```

Operations and ADR provide supporting dimensions:

```text
OPERATIONS
→ How the running system is operated and verified.

ADR
→ Why significant architectural decisions were made.
```

---

# 73. Final Requirement Principle

The fundamental rule of BookQu development is:

> **Code implements requirements; code does not silently define requirements.**

The intended behavior must be established first.

The implementation must realize that behavior.

Tests must provide evidence.

Documentation must remain synchronized.

The resulting development chain is:

```text
PRODUCT
   ↓
REQUIREMENT
   ↓
ARCHITECTURE
   ↓
SYSTEM DESIGN
   ↓
IMPLEMENTATION
   ↓
TEST
   ↓
TRACKER
```

---

# 74. Document Status

This document is the current functional and non-functional requirement baseline for BookQu.

It is authoritative for accepted system behavior.

It intentionally does not serve as:

```text
Product specification
        → docs/2-PRODUCT.md

Architectural specification
        → docs/4-ARCHITECTURE.md

Current implementation design
        → docs/7-SYSTEM-DESIGN.md

Development workflow
        → docs/5-DEVELOPMENT.md

Operational procedures
        → docs/8-OPERATIONS.md

Architectural rationale
        → docs/adr/

Current project status
        → docs/6-TRACKER.md
```

The requirement baseline should be changed when accepted product or behavioral requirements change, not merely when the implementation structure changes.
