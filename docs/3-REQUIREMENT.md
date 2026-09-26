# BookQu System Requirements

> **Document Status:** Current Requirement Baseline
> **Version:** 1.0
> **Authority:** Authoritative for current product behavior
> **Related Product Definition:** `docs/PRODUCT.md`
> **Related Architecture Definition:** `docs/ARCHITECTURE.md`
> **Last Updated:** 2026-09-26
>
> This document defines what the BookQu system is required to do.
>
> It is based on the current BookQu product definition and the current intended behavior represented by the `mergeV2` implementation.
>
> Historical SRS documents are not authoritative when they conflict with this document.

---

# 1. Purpose

This document translates the current BookQu product definition into explicit system requirements.

The objectives are:

* establish a single functional baseline;
* eliminate ambiguity between historical requirements and current behavior;
* provide a common implementation target for developers;
* provide a reliable context source for AI agents;
* provide a basis for acceptance testing;
* provide traceability between requirements, implementation, and tests;
* prevent undocumented product behavior from being introduced through code.

---

# 2. Requirement Authority

The current requirement hierarchy is:

```text
PRODUCT.md
    ↓
REQUIREMENTS.md
    ↓
ARCHITECTURE.md
    ↓
IMPLEMENTATION
    ↓
TESTS
```

`PRODUCT.md` defines what BookQu is.

`REQUIREMENTS.md` defines what the system must do.

`ARCHITECTURE.md` defines how the system should be built.

`DEVELOPMENT.md` defines how work should be performed.

`TRACKER.md` defines current implementation status.

Historical documents are reference material only.

---

# 3. Requirement Status

Each requirement may have one of the following statuses.

| Status               | Meaning                                                                       |
| -------------------- | ----------------------------------------------------------------------------- |
| `Baseline`           | Accepted as part of the current product requirement                           |
| `Implemented`        | Implemented in the current codebase                                           |
| `Verified`           | Implemented and verified by an appropriate test or explicit evidence          |
| `Needs Verification` | Intended requirement exists, but complete verification is not yet established |
| `Planned`            | Accepted requirement that has not yet been implemented                        |
| `Deprecated`         | No longer part of the current product                                         |
| `Proposed`           | Suggested change that has not yet been accepted                               |

A feature must not be marked `Verified` merely because a UI screen exists.

---

# 4. Requirement ID Convention

Every functional requirement must have a stable identifier.

The format is:

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

A platform-level administrator responsible for managing BookQu platform activity.

The admin is separate from a tenant owner.

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

Future capabilities that have not been accepted into the current baseline are documented separately in the Product Definition.

---

# 7. Authentication Requirements

## FR-AUTH-001 — Owner Registration

The system shall allow a prospective owner to create a BookQu account.

The registration process shall collect the information required by the current product onboarding flow.

At minimum, the system shall associate the account with the owner identity.

---

## FR-AUTH-002 — Owner Login

The system shall allow a registered owner to authenticate using the supported credentials.

Authentication failure shall not grant access to authenticated owner functionality.

---

## FR-AUTH-003 — Owner Logout

The system shall allow an authenticated owner to terminate the current authenticated session.

---

## FR-AUTH-004 — Email Verification

The system shall support email verification for owner accounts where email verification is required by the authentication flow.

Unverified accounts shall not be treated as fully verified owners where a verified identity is required.

---

## FR-AUTH-005 — Role-Based Access

The system shall distinguish at least:

```text
owner
admin
```

and shall prevent a user from accessing functionality outside the user's role.

---

## FR-AUTH-006 — Password Security

Passwords shall never be stored as plaintext.

Passwords shall be stored using a secure password hashing mechanism supported by Laravel.

---

# 8. Tenant and Business Requirements

## FR-TENANT-001 — Tenant Creation

The system shall associate an owner with a tenant/business.

Each owner account shall operate within the correct tenant context.

---

## FR-TENANT-002 — Tenant Isolation

The system shall isolate tenant-owned operational data.

A tenant must not be able to access another tenant's:

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

The system shall establish the appropriate tenant context for tenant-specific operations.

Tenant-specific business logic shall not rely on arbitrary client-provided tenant identifiers without authorization validation.

---

## FR-TENANT-004 — Business Profile

The owner shall be able to manage business information used by the public booking page and owner portal.

The business profile may include:

* business name;
* business type;
* phone number;
* address;
* description;
* contact information;
* operational information.

---

## FR-TENANT-005 — Business Slug

The system shall provide a unique public slug for each tenant where the public slug is used as the default public access identifier.

---

## FR-TENANT-006 — Public Tenant Access

The system shall provide a public customer-facing page based on the tenant's public identity.

The default URL pattern is:

```text
/{tenant-slug}
```

---

## FR-TENANT-007 — Custom Domain

Where custom-domain capability is enabled and configured, the system may expose the tenant's public booking page through the configured custom domain.

Custom domain functionality must resolve the correct tenant context.

---

# 9. Public Business Page Requirements

## FR-PUBLIC-001 — Public Business Page

The system shall provide a customer-facing page for an active tenant.

The page shall provide sufficient business information to allow the customer to understand the available booking offering.

---

## FR-PUBLIC-002 — Public Services

The public page shall display eligible active services for the tenant.

Inactive services shall not normally appear as bookable services.

---

## FR-PUBLIC-003 — Public Branding

Where configured, the public page shall use the tenant's approved branding information.

Branding may include:

* logo;
* brand color;
* cover/banner;
* business imagery.

---

## FR-PUBLIC-004 — Booking Entry Point

The public page shall provide a clear path into the customer booking flow.

---

# 10. Service Requirements

## FR-SERVICE-001 — Create Service

The owner shall be able to create a service.

A service shall contain the information necessary to make it bookable.

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

The system shall support the schedule pricing model accepted by the current product.

Schedule-specific pricing may override a service's default price where configured.

---

## FR-SCHEDULE-004 — Availability Configuration

The owner shall be able to configure scheduling availability.

---

## FR-SCHEDULE-005 — Blocked Dates

The owner shall be able to define dates that should not be available for normal booking.

---

## FR-SCHEDULE-006 — Delete Schedule

The owner shall be able to remove an eligible schedule that has not violated an existing reservation constraint.

---

## FR-SCHEDULE-007 — Schedule Conflict Prevention

The system shall prevent invalid overlapping or conflicting schedule records where such conflicts would make the booking model ambiguous.

---

## FR-SCHEDULE-008 — Tenant Ownership

A schedule must belong to the correct tenant and service.

A tenant must not manipulate another tenant's schedules.

---

## FR-SCHEDULE-009 — Booking Availability

The system shall calculate whether a schedule is currently available for booking.

Availability shall take into account relevant booking state and business rules.

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

A customer shall be able to select an available time schedule.

---

## FR-BOOKING-004 — Customer Information

The customer shall provide the customer information required to create a booking.

This may include:

* name;
* phone number;
* email;
* booking notes where applicable.

---

## FR-BOOKING-005 — Booking Creation

The system shall create a structured booking record after the customer completes the applicable booking process.

---

## FR-BOOKING-006 — Booking Code

Each booking shall have a unique booking identifier or booking code that can be used for customer-facing management.

---

## FR-BOOKING-007 — Booking Status

The system shall maintain the booking lifecycle state.

The current implementation includes states such as:

```text
pending
paid
cancelled
completed
```

Any future state addition must be explicitly documented.

---

## FR-BOOKING-008 — Booking Detail

The owner shall be able to view booking details.

Booking details shall provide the relevant customer, service, schedule, payment, and operational information.

---

## FR-BOOKING-009 — Booking Status Management

The owner shall be able to perform supported booking status transitions.

Invalid state transitions shall be rejected.

---

## FR-BOOKING-010 — Walk-In Booking

The owner shall be able to create a booking on behalf of a customer who books directly through the business.

A walk-in booking shall remain part of the same booking domain as online customer bookings.

---

## FR-BOOKING-011 — Booking Reschedule

The owner shall be able to reschedule an eligible booking to another available schedule.

---

## FR-BOOKING-012 — Customer Reschedule

The customer shall be able to reschedule an eligible booking through the secure booking management mechanism.

Eligibility shall be determined by booking policy and current booking state.

---

## FR-BOOKING-013 — Customer Cancellation

The customer shall be able to cancel an eligible booking through the secure booking management mechanism.

---

## FR-BOOKING-014 — Owner Cancellation Handling

The owner shall be able to manage booking cancellation state according to the supported operational flow.

---

## FR-BOOKING-015 — Booking Security

A customer shall not be able to manage another customer's booking merely by changing an exposed booking identifier.

---

## FR-BOOKING-016 — Booking Token

Where tokenized customer management is used, the system shall generate a secure management mechanism that cannot be easily guessed.

---

## FR-BOOKING-017 — Booking Availability Protection

A booking shall not be created against a schedule that is no longer available.

---

## FR-BOOKING-018 — Double Booking Protection

The system shall protect against two valid booking operations successfully occupying the same mutually exclusive schedule.

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

Selected slots within one multi-slot reservation must satisfy the supported compatibility rules.

Non-contiguous or otherwise incompatible schedules must be rejected where the current booking policy requires contiguous slots.

---

## FR-MULTIBOOK-003 — Unified Payment

Eligible multi-slot bookings shall be processed as one customer payment transaction where the current payment flow treats them as a single reservation payment group.

---

## FR-MULTIBOOK-004 — Unified Invoice

The customer-facing invoice shall represent all booking slots belonging to the same payment group.

---

## FR-MULTIBOOK-005 — Multi-Slot Cancellation Rules

The system shall apply the defined cancellation policy to multi-slot bookings.

A multi-slot booking must not accidentally behave like unrelated independent bookings.

---

## FR-MULTIBOOK-006 — Multi-Slot Reschedule Rules

The system shall apply the defined rescheduling policy to multi-slot bookings.

Unsupported multi-slot customer rescheduling must be explicitly rejected rather than partially applied.

---

# 15. Customer Management Requirements

## FR-CUSTOMER-001 — Customer Record

The system shall maintain customer information associated with bookings.

---

## FR-CUSTOMER-002 — Customer Directory

The owner shall be able to view relevant customers belonging to the owner’s tenant.

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

The system shall maintain the financial status of a payment independently from the booking status.

The current implementation supports states such as:

```text
pending
sukses
gagal
```

The canonical technical and user-facing wording may be standardized during architecture and UI refinement, but the distinction between payment state and booking state must remain.

---

## FR-PAYMENT-004 — Payment Reference

Each external payment transaction shall maintain the relevant external reference required for reconciliation.

---

## FR-PAYMENT-005 — Payment Verification

The system shall not mark a payment as successful solely because a client-side request claims that payment succeeded.

Payment success shall be based on a trusted verification mechanism.

---

## FR-PAYMENT-006 — Webhook Handling

The system shall support payment-provider callback/webhook processing where required.

---

## FR-PAYMENT-007 — Webhook Idempotency

Repeated delivery of the same payment-provider event shall not create duplicate financial side effects.

---

## FR-PAYMENT-008 — Payment Lifecycle

The system shall correctly process supported payment states including successful, failed, pending, cancelled, and expired cases where applicable.

---

## FR-PAYMENT-009 — Booking Synchronization

Where a booking payment determines booking eligibility, the system shall synchronize the booking state according to the defined business rules.

---

## FR-PAYMENT-010 — Failed Payment Protection

A failed or expired payment shall not leave the booking in an incorrect confirmed state.

---

# 17. Booking Management Without Account

## FR-MANAGE-001 — Secure Booking Access

A customer shall be able to access eligible booking management functionality without requiring a normal BookQu account.

---

## FR-MANAGE-002 — Secure Access Token

The system shall use an appropriate tokenized mechanism to authorize access to the customer's booking management page.

---

## FR-MANAGE-003 — Booking Details

The customer shall be able to view eligible booking details through the management link.

---

## FR-MANAGE-004 — Payment Information

The customer shall be able to access applicable payment and invoice information.

---

## FR-MANAGE-005 — Cancellation

The customer shall be able to cancel an eligible booking through the management interface.

---

## FR-MANAGE-006 — Rescheduling

The customer shall be able to access the supported rescheduling flow for eligible bookings.

---

## FR-MANAGE-007 — Review Submission

A customer shall be able to submit a review for an eligible completed booking.

---

# 18. Additional Item Requirements

## FR-ADDON-001 — Create Additional Item

The owner shall be able to create an additional item.

---

## FR-ADDON-002 — Update Additional Item

The owner shall be able to update an additional item.

---

## FR-ADDON-003 — Activate or Deactivate Additional Item

The owner shall be able to control whether the additional item is currently offered.

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

The system shall calculate the resulting booking price according to the defined voucher rule.

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

Staff/resource management does not automatically imply that customers may select staff or resources during booking.

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

The owner shall be able to manage supported review visibility or reply behavior.

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

The owner shall be able to access a report concerning schedule and booking performance.

---

## FR-REPORT-002 — Report Filtering

Where supported, the owner shall be able to filter reports by relevant time period or operational dimension.

---

## FR-REPORT-003 — Report Export

The system shall support export of supported report data.

The output format should match the current supported export mechanism.

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

The system shall support notifications when relevant booking state changes occur.

---

## FR-NOTIFICATION-003 — Payment Notification

The system may notify the relevant actor about important payment events.

---

## FR-NOTIFICATION-004 — Subscription Notification

The system shall support relevant subscription lifecycle notifications where applicable.

---

## FR-NOTIFICATION-005 — Notification Isolation

Owner notifications must only represent events belonging to the owner's tenant or account.

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

Current supported conceptual states include:

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

Feature access must be determined using the tenant's current subscription state and plan configuration.

---

## FR-SUB-007 — Subscription Payment

The owner shall be able to make a supported payment for the BookQu subscription.

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

Platform administration must not be implemented as an extension of ordinary owner permissions.

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

Critical multi-record operations must be performed atomically where partial completion would create invalid business state.

---

## FR-DATA-006 — Double Booking Prevention

The system shall provide a database/application-level mechanism preventing conflicting confirmed reservations from occupying the same exclusive schedule.

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

The following values originate from the historical system requirements and remain performance targets rather than verified claims.

## NFR-PERF-001 — General Response Time

Normal operations should target a response time of no more than approximately 2 seconds under expected operating conditions.

This requirement requires formal performance testing before it may be marked verified.

---

## NFR-PERF-002 — Heavy Operations

Heavy operations such as booking/payment processing should target a response time of no more than approximately 5 seconds where practical.

External payment-provider latency may be outside direct application control.

---

## NFR-PERF-003 — Availability Data

Availability checks should provide sufficiently fresh data for the booking process to prevent stale availability from creating an invalid booking.

---

## NFR-PERF-004 — Query Efficiency

The system should avoid unnecessary repeated database queries, unbounded dataset retrieval, and redundant computation in frequently accessed owner and customer flows.

---

## NFR-PERF-005 — Caching

Caching may be used where it improves performance without compromising booking correctness or data freshness.

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

Expected external-service and application failure conditions should result in predictable recovery behavior.

---

## NFR-REL-004 — Backup

Production data should be backed up using an appropriate operational backup mechanism.

---

## NFR-REL-005 — Recovery

The production environment should have a documented recovery procedure.

Backup and recovery requirements require operational verification.

---

# 36. Availability Requirement

## NFR-AVAIL-001 — Service Availability

The production service should target high availability.

The historical target was approximately 99% uptime.

This target must be validated through actual production monitoring rather than assumed from code.

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

## NFR-UX-004 — Booking Step Count

The main booking operation should remain reasonably concise.

The historical target was no more than approximately five major user steps, excluding externally controlled payment interactions where applicable.

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

The system shall support current mainstream browsers, including modern:

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

Controllers, views, models, services, and domain/application logic shall have clear responsibilities.

Business logic shall not be unnecessarily concentrated in controllers.

---

## NFR-MAINT-002 — Modular Design

The system shall be organized so that new features can be added without unnecessary modification of unrelated modules.

---

## NFR-MAINT-003 — Reusable Components

Repeated UI and application behavior should be extracted into reusable components or services when appropriate.

---

## NFR-MAINT-004 — Consistent Naming

New code must follow the naming conventions defined in `ARCHITECTURE.md`.

Historical naming inconsistencies may remain temporarily but should not be propagated into new code.

---

## NFR-MAINT-005 — Documentation

Important domain and architectural decisions shall be documented.

---

## NFR-MAINT-006 — Technical Debt Visibility

Known implementation defects that do not invalidate product behavior shall be recorded explicitly rather than silently ignored.

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

Production application errors should be observable through the configured monitoring/logging mechanism.

---

## NFR-OBS-005 — Operational Monitoring

Production performance and availability should be observable.

---

# 41. Extensibility Requirements

## NFR-EXT-001 — Payment Provider Integration

Payment integration shall be isolated sufficiently to allow future provider changes or additional providers.

---

## NFR-EXT-002 — Notification Integration

Notification delivery should be sufficiently modular to allow future channels such as email or messaging integrations.

---

## NFR-EXT-003 — Feature Expansion

Future modules should be addable without unnecessarily rewriting the booking domain.

---

## NFR-EXT-004 — Multi-Tenant Expansion

New tenant-level modules must respect the established tenant isolation mechanism.

---

# 42. Core Business Rules

The following business rules are fundamental to BookQu.

## BR-CORE-001 — Every Operational Record Belongs to a Tenant

Tenant-owned operational data must have a valid tenant association.

---

## BR-CORE-002 — A Service Belongs to One Tenant

A service cannot be shared across unrelated tenants.

---

## BR-CORE-003 — A Schedule Belongs to One Service

A schedule must reference the service for which it is available.

---

## BR-CORE-004 — A Booking Belongs to One Tenant

A booking must remain associated with the correct tenant throughout its lifecycle.

---

## BR-CORE-005 — A Booking References a Service

The booking must reference the service being reserved.

---

## BR-CORE-006 — A Booking References an Eligible Schedule

The schedule used for a booking must belong to the correct service and tenant.

---

## BR-CORE-007 — Unavailable Schedules Cannot Be Booked

The system shall reject attempts to book schedules that are no longer eligible.

---

## BR-CORE-008 — Double Booking Must Be Prevented

Two incompatible reservations must not successfully occupy the same exclusive schedule.

---

## BR-CORE-009 — Payment and Booking State Are Distinct

Booking status must not be inferred solely from generic payment status without applying the accepted booking rules.

---

## BR-CORE-010 — Customer Management Requires Authorization

A customer must only be able to manage bookings to which the customer has valid access.

---

## BR-CORE-011 — Owner Access Requires Tenant Authorization

An authenticated owner may only operate on the tenant/business they are authorized to manage.

---

## BR-CORE-012 — Deactivated Services Are Not New Booking Targets

Deactivation affects new booking eligibility but must not destroy historical booking data.

---

## BR-CORE-013 — Historical Records Must Remain Traceable

Booking and payment history must remain usable for reporting and operational traceability after normal lifecycle changes.

---

# 43. Business Rule: Booking State

The booking lifecycle currently includes concepts such as:

```text
pending
paid
cancelled
completed
```

Allowed transitions must be explicitly defined and tested.

An arbitrary status assignment from a client request must not bypass business rules.

---

# 44. Business Rule: Payment State

Payment state is separate from booking state.

Example conceptual flow:

```text
Payment
pending
   ↓
success
   ↓
booking may become confirmed/paid
```

or:

```text
Payment
pending
   ↓
failed / expired / cancelled
   ↓
booking must not remain falsely confirmed
```

The exact transition implementation belongs to the payment and booking architecture.

---

# 45. Business Rule: Tenant Isolation

All tenant-scoped queries and mutations must use the correct tenant context.

A request must never rely solely on a client-provided tenant identifier to establish authorization.

---

# 46. Business Rule: Subscription Entitlement

A tenant's access to restricted functionality must be derived from its current subscription state and plan.

A feature gate must not be duplicated inconsistently across unrelated controllers.

---

# 47. Business Rule: Customer Management Token

Customer booking-management access must use a secure authorization mechanism.

Management URLs must not expose secrets unnecessarily.

---

# 48. Business Rule: Multi-Slot Booking

Where a customer books multiple schedules in one transaction:

```text
selected slots
      ↓
one reservation intent
      ↓
one payment group where applicable
      ↓
corresponding booking records
```

The implementation may use multiple records internally, but the customer experience must remain coherent.

---

# 49. Business Rule: Walk-In Booking

Walk-in bookings are bookings created by an authorized owner on behalf of a customer.

They share the same core booking domain as online bookings.

They must still obey:

* schedule availability;
* tenant isolation;
* booking constraints;
* financial recording requirements where applicable.

---

# 50. Authorization Matrix

The baseline authorization model is:

| Capability             | Owner |              Customer |                                     Admin |
| ---------------------- | ----: | --------------------: | ----------------------------------------: |
| Manage own business    |   Yes |                    No |           Platform-level where authorized |
| Manage services        |   Yes |                    No |                Not normal owner operation |
| Manage schedules       |   Yes |                    No | Platform-level oversight where authorized |
| View own bookings      |   Yes | Own eligible bookings |           Platform-level where authorized |
| Create walk-in booking |   Yes |                    No |           Not a normal customer operation |
| Create public booking  |    No |                   Yes |                                        No |
| Manage own booking     |    No |      Yes, if eligible |           Platform-level where authorized |
| Manage customers       |   Yes |                    No |           Platform-level where authorized |
| Manage categories      |   Yes |                    No |           Platform-level where authorized |
| Manage staff/resources |   Yes |                    No |           Platform-level where authorized |
| Manage vouchers        |   Yes |                    No |           Platform-level where authorized |
| Submit review          |    No |      Yes, if eligible |                                        No |
| Manage subscription    |   Yes |                    No |           Platform-level where authorized |
| Access admin dashboard |    No |                    No |                                       Yes |

This matrix is intentionally high-level.

Detailed authorization policy belongs in the architecture and security implementation.

---

# 51. Acceptance Criteria Rules

Every implemented requirement must eventually have acceptance criteria.

Acceptance criteria must describe observable behavior.

Bad example:

```text
The controller should be clean.
```

Good example:

```text
Given an owner authenticated for Tenant A,
when the owner opens the booking list,
then only bookings belonging to Tenant A are returned.
```

Acceptance criteria must focus on behavior rather than implementation details.

---

# 52. Global Acceptance Criteria

The following criteria apply to all relevant tenant-scoped features.

## AC-GLOBAL-001 — Tenant Isolation

Given an owner of Tenant A, the owner must not be able to retrieve or modify Tenant B data.

---

## AC-GLOBAL-002 — Authorization

A user without the required permission must receive an authorization failure rather than the requested protected data.

---

## AC-GLOBAL-003 — Invalid Resource

A request for a nonexistent or inaccessible resource must not reveal data belonging to another tenant.

---

## AC-GLOBAL-004 — Validation

Invalid input must be rejected before destructive or invalid business operations occur.

---

## AC-GLOBAL-005 — Transaction Safety

Critical multi-record operations must either complete successfully according to business rules or leave the system in a valid previous state.

---

# 53. Traceability Model

Every accepted requirement should eventually be traceable to implementation and verification.

The intended relationship is:

```text
Requirement
    ↓
Feature
    ↓
Module
    ↓
Implementation
    ↓
Automated Test
    ↓
Acceptance
```

Example:

```text
FR-BOOKING-010
    ↓
Walk-In Booking
    ↓
Booking Module
    ↓
Owner booking implementation
    ↓
Owner booking tests
    ↓
PASS
```

---

# 54. Requirement Traceability Table

This table will be expanded as the implementation audit progresses.

| Requirement      | Product Area      | Implementation                  | Test                   | Status             |
| ---------------- | ----------------- | ------------------------------- | ---------------------- | ------------------ |
| FR-AUTH-001      | Authentication    | Existing auth flow              | Auth tests             | Needs Verification |
| FR-AUTH-002      | Authentication    | Existing auth flow              | Auth tests             | Implemented        |
| FR-TENANT-002    | Multi-Tenancy     | Tenant context/scope            | Isolation tests        | Verified           |
| FR-SERVICE-001   | Services          | Service management              | Service tests          | Implemented        |
| FR-SCHEDULE-001  | Schedule          | Schedule management             | Schedule tests         | Implemented        |
| FR-BOOKING-005   | Booking           | Customer booking flow           | Booking tests          | Implemented        |
| FR-BOOKING-010   | Walk-In           | Owner booking flow              | Owner booking tests    | Implemented        |
| FR-BOOKING-018   | Booking Integrity | Database/application protection | Concurrency tests      | Verified           |
| FR-PAYMENT-001   | Payment           | Midtrans integration            | Payment tests          | Implemented        |
| FR-PAYMENT-007   | Payment           | Webhook handling                | Payment tests          | Verified           |
| FR-MULTIBOOK-001 | Multi-Slot        | Booking/payment group flow      | Production logic tests | Implemented        |
| FR-REVIEW-001    | Reviews           | Customer review flow            | Review tests           | Implemented        |
| FR-ANALYTICS-001 | Analytics         | Owner analytics                 | Analytics tests        | Implemented        |
| FR-SUB-001       | Subscription      | Subscription module             | Subscription tests     | Implemented        |

This table is a baseline and must be synchronized with `TRACKER.md`.

---

# 55. Current Implementation Evidence

The current repository contains automated tests covering important areas of the product, including:

* tenant isolation;
* schedule ownership;
* duplicate and overlapping schedule protection;
* double-booking concurrency;
* booking ownership;
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

The existence of a test does not automatically prove that all possible acceptance criteria are satisfied.

A test must be mapped to the appropriate requirement.

---

# 56. Non-Functional Verification Policy

A non-functional requirement must not be marked `Verified` without evidence.

Examples:

```text
NFR-PERF-001
requires performance measurement.

NFR-SCALE-004
requires deployment/load architecture evidence.

NFR-REL-004
requires backup evidence.

NFR-AVAIL-001
requires operational monitoring evidence.
```

Code inspection alone is insufficient for these requirements.

---

# 57. Historical Requirement Mapping

The original SRS contains requirements that are now represented differently.

Examples include:

* subdomain terminology;
* subscription and trial requirements;
* package-based access;
* payment;
* custom landing page;
* notification behavior;
* usage limits;
* scheduling automation.

The current requirement baseline retains accepted product concepts while standardizing terminology and separating product behavior from implementation details.

The historical SRS must not be copied directly into new implementation tasks.

---

# 58. Deprecated or Superseded Concepts

The following concepts should not be introduced as new independent domain concepts without an explicit requirement change:

```text
Program as a separate domain from Service
Business as a separate domain from Tenant
Customer as a BookQu User
Schedule and Service as one entity
Payment and Booking as one lifecycle
Subdomain as the name for /tenant-slug
```

Legacy code may still contain these names.

Refactoring them is an architecture task, not a requirement change by itself.

---

# 59. Out of Scope

The following are not part of the current baseline unless explicitly accepted later:

```text
- Full accounting/financial management
- Full ERP capabilities
- Full general-purpose CRM
- General inventory management
- Social media management
- Full marketing automation
- AI-generated business decisions
- Unspecified external integrations
- Unspecified multi-location management
- Unspecified advanced payment automation
```

Future functionality must be added through an explicit requirement change.

---

# 60. Change Control

A change to BookQu behavior must not be introduced only through source code.

When a proposed change modifies product behavior:

```text
Proposal
   ↓
Determine affected requirement
   ↓
Accept / reject product change
   ↓
Update REQUIREMENTS.md
   ↓
Update affected architecture documentation
   ↓
Update TRACKER.md
   ↓
Implement
   ↓
Test
```

---

# 61. Requirement Conflict Rule

When a conflict is found between:

* source code;
* tests;
* tracker;
* historical SRS;
* product definition;
* current requirements;

the conflict must be explicitly identified.

A developer or AI agent must not silently select whichever interpretation is easiest to implement.

The current product and requirement documents are the authority for intended behavior.

Source code represents current implementation.

Tests represent verified behavior.

Historical documents represent past decisions.

---

# 62. Rule for AI Agents

AI agents working on BookQu must:

1. read `AGENTS.md`;
2. read the relevant section of `PRODUCT.md`;
3. identify the applicable requirement ID;
4. inspect the current implementation;
5. inspect relevant tests;
6. determine whether the requested task changes product behavior;
7. implement only within the accepted scope;
8. update tests where behavior changes;
9. update documentation when the accepted behavior changes.

An AI agent must not create a new product capability merely because it appears technically useful.

An AI agent must not remove an existing capability merely because it does not exist in the historical SRS.

When uncertain, the agent must identify the requirement conflict instead of inventing product behavior.

---

# 63. Definition of Requirement Completeness

A requirement is considered sufficiently defined when it specifies:

```text
Who
↓
What
↓
Under what conditions
↓
Expected behavior
↓
Important business rules
↓
Expected result
↓
Relevant constraints
```

For example:

```text
FR-BOOKING-010

Who:
Owner

What:
Create a walk-in booking

Condition:
Customer arrives directly at the business

Expected behavior:
The owner can create a booking on behalf of that customer

Constraints:
The selected schedule must be available

Result:
A valid booking record is created
```

---

# 64. Definition of Done for Functional Requirements

A functional requirement is considered `Done` when:

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
UI/UX flow verified
        ↓
Tracker updated
```

A feature that works but violates the target architecture may be marked:

```text
Implemented
Architecture: Needs Refactor
```

It should not be misrepresented as architecturally complete.

---

# 65. Final Requirement Principle

BookQu development follows one fundamental rule:

> **Code implements requirements; code does not define requirements.**

The intended product behavior must be established first.

The implementation must then realize that behavior.

Tests must verify it.

Documentation must remain synchronized with it.

Therefore:

```text
PRODUCT
   ↓
REQUIREMENT
   ↓
ARCHITECTURE
   ↓
IMPLEMENTATION
   ↓
TEST
```

This chain is the foundation for future BookQu development.

---

# 66. Document Status

This document is the current functional and non-functional requirement baseline for BookQu.

It supersedes conflicting portions of historical requirement documents.

It intentionally does not define detailed code structure.

Technical structure, folder organization, layer responsibilities, coding conventions, and architectural constraints are defined in:

```text
docs/ARCHITECTURE.md
```

Development procedures are defined in:

```text
docs/DEVELOPMENT.md
```

Current implementation status is defined in:

```text
docs/TRACKER.md
```

Agent-specific rules are defined in:

```text
AGENTS.md
```
