# BookQu Product Specification

> **Document Status:** Current Product Definition
> **Version:** 1.0
> **Authority:** Current Product Source of Truth
> **Applies To:** Current BookQu implementation and future development
> **Last Updated:** 2026-09-26
>
> This document defines what BookQu is, who it serves, what capabilities are part of the current product, and how the product domain should be understood.
>
> This document is not a technical architecture document and does not define implementation details. Technical decisions are defined in `docs/ARCHITECTURE.md`.

---

# 1. Purpose of This Document

This document establishes a single, shared definition of the BookQu product.

BookQu has evolved significantly during development. Some product capabilities were introduced or expanded after the original SRS was created. Those changes became part of the current implementation but were not consistently reflected in the original requirements documentation.

This document therefore defines the product from the perspective of its current intended state.

The purpose is to ensure that:

* all developers understand the same product;
* all AI agents interpret the same product scope;
* features are described using consistent terminology;
* product concepts are not inferred differently by different contributors;
* future requirements can be derived consistently from this document;
* technical implementation can be refactored without changing the intended product behavior;
* historical requirements do not accidentally override the current product definition.

The original SRS and previous development documents remain historical references only. They are not authoritative when they conflict with the current product definition.

---

# 2. Product Overview

## 2.1 What Is BookQu?

BookQu is a multi-tenant web-based booking and reservation management platform for businesses that provide time-based or schedule-based services.

BookQu allows a business owner to:

* establish a public business presence;
* define bookable services;
* configure available schedules;
* receive and manage customer bookings;
* manage booking operations;
* maintain customer information and booking history;
* manage supporting business capabilities such as categories, additional items, vouchers, reviews, staff, resources, and analytics;
* receive or manage booking-related payments;
* manage the business's BookQu subscription;
* customize the public presentation of the business.

Customers can access a business's public BookQu page, select a service, select a date and available time, complete a booking, make the required payment, and manage eligible bookings afterward.

BookQu is therefore primarily an **operational booking platform**, not a general-purpose e-commerce platform, CRM, accounting platform, or marketing automation platform.

---

# 3. Product Problem

Many small and medium service businesses manage reservations using a combination of:

* chat applications;
* spreadsheets;
* manual calendars;
* phone calls;
* social media messages;
* handwritten records;
* manually maintained schedules.

These approaches make it difficult to maintain consistent availability, prevent scheduling conflicts, track booking history, and provide customers with a structured booking experience.

BookQu centralizes the operational booking process into a single system.

The central problem BookQu solves is:

> **How can a schedule-based business provide customers with a structured booking process while allowing the business owner to manage availability, reservations, customers, and business activity from one system?**

---

# 4. Product Goal

The primary goal of BookQu is to provide a reliable and structured booking workflow from service configuration through reservation management.

The product should make the following process straightforward:

```text
Business Setup
      ↓
Service Configuration
      ↓
Schedule Configuration
      ↓
Customer Booking
      ↓
Booking Record
      ↓
Owner Management
      ↓
Customer History
      ↓
Business Insight
```

Supporting capabilities should strengthen this flow rather than replace it as the primary purpose of the platform.

---

# 5. Target Users

BookQu currently serves three principal user groups.

## 5.1 Business Owner

The business owner operates a business through BookQu.

The owner is responsible for:

* configuring business information;
* managing services;
* managing availability;
* managing bookings;
* managing customers;
* monitoring business activity;
* managing optional supporting capabilities;
* managing the business's BookQu subscription.

The owner is the primary authenticated operational user.

---

## 5.2 Customer

A customer is a person who wants to book a service provided by a business.

The customer can:

* access a business's public booking page;
* view available services;
* select a booking date;
* select an available time;
* provide booking information;
* complete payment when required;
* receive booking/payment information;
* access booking management through a secure management link;
* perform permitted post-booking actions such as cancellation or rescheduling;
* submit a review where eligible.

A customer does not currently need a full BookQu account to perform the public booking process.

Customer identity for a booking is primarily represented through booking information such as name, phone number, and email.

---

## 5.3 Platform Admin

The platform admin operates at the BookQu platform level rather than at the individual business level.

The platform admin is separate from a business owner.

The platform admin is responsible for platform-level oversight and administration capabilities.

The current implementation provides a platform administration area, but BookQu should not assume that every future platform management capability already exists.

---

# 6. Core Product Concept

BookQu is based on the following primary domain relationship:

```text
User
  │
  └── owns
       │
       ▼
     Tenant
       │
       ├── Services
       │      │
       │      └── Schedules
       │
       ├── Bookings
       │      │
       │      ├── Customer information
       │      └── Payment information
       │
       ├── Customers
       ├── Staff / Resources
       ├── Categories
       ├── Additional Items
       ├── Vouchers
       ├── Reviews
       ├── Assets / Appearance
       └── Subscription
```

This relationship is the conceptual foundation of BookQu.

---

# 7. Canonical Domain Terminology

Terminology must remain consistent throughout product documentation, requirements, code, UI copy, tests, and agent instructions.

## 7.1 User

`User` represents an authenticated identity in the BookQu platform.

A user may have a platform role such as:

* owner;
* admin.

A user is not the same concept as a business.

---

## 7.2 Tenant

`Tenant` is the canonical technical/domain term for a business entity operating inside BookQu.

A tenant represents one business account and its isolated operational data.

For user-facing language, the term **Business** may be used.

Therefore:

```text
Technical/domain term:
Tenant

User-facing term:
Business
```

Contributors should not introduce a separate `Business` domain entity unless a future requirement explicitly requires one.

---

## 7.3 Owner

`Owner` is the person who operates and manages a tenant/business through the BookQu owner portal.

The owner is represented by a `User`.

The current product model assumes a business is associated with its owner.

---

## 7.4 Customer

`Customer` represents the person making or receiving a booking.

A customer is distinct from the authenticated BookQu user.

A customer can interact with the booking system without maintaining a normal BookQu owner account.

---

## 7.5 Service

`Service` is the canonical term for something that customers can book.

Examples include:

* badminton court rental;
* photography studio session;
* music studio session;
* consultation session.

The following terms should not be treated as separate domain concepts unless explicitly required:

```text
Program
Layanan
Business Service
Bookable Program
```

These are legacy or presentation terms.

The canonical product/domain term is:

> **Service**

The existing `/programs` naming in the implementation may remain temporarily for compatibility, but new requirements and architecture documentation should use `Service`.

---

## 7.6 Schedule

`Schedule` represents a bookable time slot associated with a service.

A schedule has at minimum:

* service;
* date;
* start time;
* end time;
* availability state.

A schedule is not the same as a business's general operating hours.

---

## 7.7 Availability

`Availability` represents whether a service can currently be booked for a particular date/time.

Availability is a business state derived from scheduling and booking conditions.

Availability should not be treated as a separate primary domain entity unless future requirements require persistent availability rules.

---

## 7.8 Booking

`Booking` represents a reservation made for a service at one or more eligible schedules.

A booking is the central transaction of the BookQu operational domain.

A booking may be created through:

* customer booking;
* owner walk-in booking.

---

## 7.9 Payment

`Payment` represents a financial transaction associated with BookQu operations.

Current product usage includes payment flows related to:

* customer booking payments;
* business subscription payments.

Payment state and booking state are related but should remain conceptually separate.

A payment being successful does not mean every booking-related state should be inferred without applying the relevant booking business rules.

---

## 7.10 Plan

`Plan` represents a BookQu subscription package definition.

A plan defines the capability and/or usage entitlement associated with a subscription level.

---

## 7.11 Subscription

`Subscription` represents the subscription state of a tenant/business.

A subscription is an instance of a plan for a tenant.

Therefore:

```text
Plan
=
available package definition

Subscription
=
a tenant's active/trial/expired/cancelled entitlement
```

These terms must not be used interchangeably.

---

## 7.12 Staff

`Staff` represents a person/resource participant associated with a business's operation.

Staff management is a supporting operational capability.

Customer-facing staff selection is not assumed to be part of the core booking flow unless explicitly defined by a future requirement.

---

## 7.13 Resource

`Resource` represents a physical or operational asset that may be associated with a service.

Examples may include:

* studio room;
* court;
* room;
* equipment/resource unit.

Staff and resources are related concepts but should not be treated as the same entity.

---

## 7.14 Additional Item

`Additional Item` represents an optional add-on that may be selected together with a service where the feature is enabled.

Examples may include:

* equipment rental;
* additional facilities;
* optional extras.

Additional items are supporting booking capabilities, not standalone primary bookings.

---

## 7.15 Voucher

`Voucher` represents a promotional discount rule that may be applied to an eligible booking.

Voucher functionality is part of the supporting commercial layer of BookQu.

---

## 7.16 Review

`Review` represents customer feedback associated with an eligible booking.

A review is a post-booking customer experience capability.

It is not part of the primary booking creation transaction.

---

## 7.17 Tenant Page / Public Booking Page

The public page of a tenant is the customer-facing entry point for that business.

It allows customers to discover available services and begin a booking.

The default access mechanism is based on the tenant's slug.

BookQu also supports the concept of a custom domain for eligible businesses where configured.

---

# 8. Product Architecture at the Conceptual Level

The product is divided into three major user-facing areas.

```text
                    BOOKQU
                      │
       ┌──────────────┼──────────────┐
       │              │              │
       ▼              ▼              ▼
   Owner Portal   Customer Portal  Admin Portal
       │              │              │
       ▼              ▼              ▼
 Business Ops     Booking Flow    Platform Ops
```

These areas must remain conceptually separated.

---

# 9. Owner Portal

The Owner Portal is the primary operational workspace for a business.

## 9.1 Dashboard

The dashboard provides a concise overview of business activity.

Current dashboard concepts include information related to:

* bookings;
* revenue;
* customers;
* services;
* trends;
* upcoming schedules;
* recent booking activity;
* operational statistics.

The dashboard is an aggregation and monitoring surface.

It should not become the primary location for unrelated business operations.

---

## 9.2 Calendar

The calendar provides a time-oriented operational view of schedules and bookings.

It allows the owner to understand:

* schedule availability;
* occupied schedules;
* booking activity;
* dates;
* operational periods.

Calendar is a view of booking and scheduling data.

It is not a replacement for the underlying schedule domain.

---

## 9.3 Schedule Management

Schedule Management defines when services are available for booking.

Current capabilities include concepts such as:

* creating available slots;
* bulk slot generation;
* default pricing;
* availability configuration;
* blocked dates;
* removing schedule slots;
* preventing conflicts.

Schedule configuration is one of the core operational capabilities of BookQu.

---

## 9.4 Booking Management

Booking Management allows the owner to operate reservations after they are created.

Current capabilities include:

* viewing bookings;
* viewing booking details;
* changing booking status;
* creating walk-in bookings;
* checking available reschedule slots;
* rescheduling eligible bookings;
* viewing booking/payment information;
* completing operational actions.

Booking Management is part of the core BookQu product.

---

## 9.5 Service Management

Service Management allows a business to define what customers can book.

Current service concepts include:

* service name;
* description;
* price;
* duration;
* capacity;
* active/inactive state;
* service image;
* category association where applicable.

Service Management defines the inventory of bookable offerings.

---

## 9.6 Customer Management

Customer Management provides the business with access to customer information and booking history.

Current capabilities include:

* customer listing;
* customer details;
* booking history;
* customer notes where supported.

The customer module is operational rather than a full CRM system.

---

## 9.7 Categories

Categories organize services into meaningful groups.

Categories improve service organization and discovery.

Categories do not represent a replacement for services.

---

## 9.8 Staff and Resources

Staff and Resources provide additional operational structure for businesses that need to manage people or physical resources.

This module should remain optional.

A business does not need staff/resource management to operate the core booking workflow.

---

## 9.9 Additional Items

Additional Items allow businesses to offer optional extras together with services.

This capability extends a basic booking rather than replacing it.

---

## 9.10 Vouchers

Vouchers provide promotional discount capabilities.

Voucher rules may include:

* discount type;
* discount amount;
* validity period;
* minimum transaction;
* usage limitation.

Exact business rules belong in `REQUIREMENTS.md`.

---

## 9.11 Reviews

Reviews provide customer feedback after an eligible booking.

The owner can view feedback and manage supported review presentation behaviors.

Reviews are a supporting customer-experience capability.

---

## 9.12 Analytics and Reports

Analytics and reports summarize operational data.

Current capabilities include concepts such as:

* booking metrics;
* revenue-related metrics;
* customer metrics;
* service performance;
* schedule utilization;
* schedule-related reports;
* report export.

Analytics should consume operational data.

Analytics must not become a separate source of truth for booking or financial records.

---

## 9.13 Appearance and Landing Page

BookQu allows a business to customize its public presence.

Current appearance concepts include:

* logo;
* brand identity;
* colors;
* banner/cover assets;
* public landing page configuration.

Appearance affects presentation.

It must not change the underlying operational rules of booking.

---

## 9.14 Assets

Assets provide a media management layer for business presentation.

Potential assets include:

* business logo;
* service images;
* banners;
* public page media.

Assets are presentation resources rather than core booking records.

---

## 9.15 Notifications

Notifications communicate operational events to the owner.

Examples include:

* new booking;
* booking status change;
* payment-related events;
* subscription-related events.

Notification behavior should be event-driven from the relevant domain state.

---

## 9.16 Subscription

Subscription management controls the tenant's relationship with the BookQu service.

Current product concepts include:

* subscription plans;
* subscription status;
* trial state;
* package-based access;
* subscription payment;
* subscription lifecycle.

Exact entitlement and lifecycle rules are defined separately in `REQUIREMENTS.md`.

---

# 10. Customer Portal

The Customer Portal is the public-facing booking experience.

The primary customer flow is:

```text
Business Page
     ↓
Select Service
     ↓
Select Date
     ↓
Select Time
     ↓
Review Booking
     ↓
Payment
     ↓
Booking Confirmation / Invoice
```

---

## 10.1 Service Selection

The customer chooses a service from the tenant's active bookable services.

Inactive services must not be treated as normally bookable.

---

## 10.2 Date Selection

The customer chooses a valid booking date.

Only dates that are relevant to the service's availability should be presented as available.

Past or invalid booking periods must not become valid booking targets.

---

## 10.3 Time Selection

The customer selects an available time slot.

The system must prevent selection of unavailable or already occupied schedules.

---

## 10.4 Checkout

Checkout summarizes the reservation before payment or final booking completion.

The checkout process may include:

* customer information;
* selected service;
* selected schedule;
* pricing;
* eligible additional items;
* applicable voucher;
* total amount;
* applicable booking policy.

---

## 10.5 Payment

When payment is required, the customer proceeds through the supported payment process.

BookQu currently integrates with Midtrans for online payment processing.

Payment state must remain distinct from booking state.

---

## 10.6 Invoice

Successful booking/payment flows provide customer-facing financial and reservation information.

Invoice presentation should represent recorded transaction data and must not independently redefine financial truth.

---

# 11. Customer Booking Management

Customers can access booking management through secure, tokenized links.

This supports customers who do not have a full authenticated BookQu account.

The management flow may include:

* viewing booking information;
* viewing payment information;
* cancellation where eligible;
* rescheduling where eligible;
* viewing invoice;
* submitting a review where eligible.

Access to management functions must be controlled by the booking's management authorization mechanism.

---

# 12. Walk-In Booking

BookQu supports bookings created directly by the owner for customers who arrive or reserve directly through the business.

The conceptual distinction is:

```text
Online Booking
Customer → BookQu → Booking

Walk-In Booking
Owner → BookQu → Booking
```

Both produce a booking record inside the same operational booking system.

They should not become two separate booking domains.

---

# 13. Multi-Slot Booking

BookQu supports booking scenarios involving more than one contiguous schedule slot where the business rules allow it.

Multiple selected schedules that belong to the same reservation should be treated as one customer booking operation rather than unrelated independent customer transactions.

The implementation may represent the resulting schedule reservations as multiple booking records where necessary, but the product concept remains one reservation transaction.

This distinction is important for:

* payment;
* cancellation;
* invoice;
* availability;
* booking management.

---

# 14. Payment Domain

Payment is a supporting but important part of the BookQu product.

The product currently has two major payment contexts:

```text
Booking Payment
      ↓
Customer pays for a reservation

Subscription Payment
      ↓
Owner pays for BookQu subscription
```

These contexts must not be conflated.

A payment record should always have a clear business purpose.

Midtrans is the currently supported external payment gateway.

External payment-provider integration details belong in `docs/ARCHITECTURE.md`.

---

# 15. Subscription Domain

BookQu operates as a subscription-based platform.

The subscription domain contains:

```text
Plan
   ↓
Subscription
   ↓
Tenant Entitlement
   ↓
Feature / Usage Access
```

The product may distinguish between:

* trial;
* active subscription;
* expired subscription;
* cancelled subscription.

Exact lifecycle transitions, limits, entitlements, and upgrade/downgrade behavior are requirement-level rules and must be defined in `REQUIREMENTS.md`.

---

# 16. Multi-Tenancy

BookQu is a multi-tenant system.

Each business operates within an isolated tenant context.

Conceptually:

```text
BookQu
│
├── Tenant A
│    ├── Services
│    ├── Schedules
│    ├── Bookings
│    └── Customers
│
├── Tenant B
│    ├── Services
│    ├── Schedules
│    ├── Bookings
│    └── Customers
│
└── Tenant C
     ├── Services
     ├── Schedules
     ├── Bookings
     └── Customers
```

Data belonging to one tenant must not become accessible to another tenant.

Tenant isolation is a fundamental product property, not an optional technical enhancement.

---

# 17. Tenant Public Access

A tenant/business has a public customer-facing booking page.

The canonical concept is:

```text
BookQu Platform
      ↓
Tenant
      ↓
Public Booking Page
```

The current implementation supports:

```text
/{tenant-slug}
```

as the primary public access pattern.

Custom domain support may also be used where configured.

The concept is therefore:

> **Tenant public identity = slug-based public URL, with optional custom-domain support.**

The term "subdomain" should not be used to describe the default `/tenant-slug` URL because a path-based URL and a subdomain are different concepts.

---

# 18. Core Product Loop

The core BookQu product loop is:

```text
1. Business Setup
        ↓
2. Configure Services
        ↓
3. Configure Schedule
        ↓
4. Customer Selects Service
        ↓
5. Customer Selects Date
        ↓
6. Customer Selects Time
        ↓
7. Customer Completes Booking
        ↓
8. Payment / Booking Confirmation
        ↓
9. Booking Stored
        ↓
10. Owner Manages Booking
        ↓
11. Customer and Booking History Retained
        ↓
12. Dashboard / Reporting Provides Operational Insight
```

A feature should be considered part of the core product when it directly strengthens this loop.

Features that do not directly strengthen the loop should be treated as supporting or future capabilities.

---

# 19. Product Scope Classification

To prevent future scope confusion, current BookQu capabilities are divided into four conceptual groups.

## 19.1 Core Operations

These capabilities form the main purpose of BookQu.

```text
- Business setup
- Business profile
- Service management
- Schedule management
- Availability
- Customer booking
- Booking management
- Customer records
- Walk-in booking
- Booking payment
- Dashboard
- Calendar
- Public booking page
```

---

## 19.2 Supporting Operations

These capabilities strengthen the core booking product but are not the fundamental booking transaction itself.

```text
- Categories
- Staff
- Resources
- Additional items
- Vouchers
- Reviews
- Analytics
- Schedule reports
- Assets
- Appearance
- Notifications
- Customer notes
```

---

## 19.3 Platform Capabilities

These capabilities operate at the BookQu platform level.

```text
- Authentication
- Subscription
- Plans
- Trial lifecycle
- Feature entitlement
- Subscription payments
- Platform administration
- Tenant isolation
```

---

## 19.4 Future / Expansion Capabilities

The following concepts may be part of BookQu's future roadmap but are not automatically part of the current product definition unless implemented and explicitly accepted:

```text
- Google Calendar synchronization
- Advanced external integrations
- Advanced AI insights
- Automated marketing
- Advanced CRM
- Customer segmentation
- Multi-location management
- Advanced payment automation
- WhatsApp automation
- Advanced analytics beyond current reporting
```

The existence of a route, placeholder page, database field, or unfinished code does not by itself make a future capability part of the current product.

---

# 20. Product Boundaries

BookQu should not be interpreted as a general-purpose platform for every business operation.

BookQu's primary responsibility is:

```text
Booking
+
Scheduling
+
Customer Reservation Management
+
Booking-related Operations
```

BookQu may provide supporting features around those responsibilities, but it does not automatically become:

* a full accounting system;
* a complete ERP;
* a general CRM;
* a full marketing automation platform;
* a general inventory management platform;
* a social media management platform.

Future expansion into those areas must be explicitly documented.

---

# 21. Important Product Rules

The following principles apply to the current product definition.

## 21.1 Booking Is the Central Transaction

Booking is the central operational object of BookQu.

Supporting modules must not create conflicting booking concepts.

---

## 21.2 Service and Schedule Are Different Concepts

A service defines:

> What the customer can book.

A schedule defines:

> When the customer can book it.

These concepts must not be merged.

---

## 21.3 Booking and Payment Are Different States

A booking has its own business lifecycle.

A payment has its own financial lifecycle.

The system must not assume that one state is always identical to the other.

---

## 21.4 Tenant Isolation Is Fundamental

All tenant-owned operational data belongs to a specific tenant.

A feature is incomplete if it can operate correctly for one tenant but improperly expose another tenant's data.

---

## 21.5 Customer Does Not Equal User

An owner/admin is an authenticated BookQu user.

A booking customer is a customer entity represented by booking information.

The product should not assume every customer has a BookQu account.

---

## 21.6 Supporting Features Must Not Redefine the Core Product

Analytics, vouchers, reviews, add-ons, assets, staff, and similar features should consume and extend the core booking domain.

They should not duplicate or redefine booking, schedule, customer, or payment concepts.

---

# 22. Current Product Terminology Rules

The following terminology is canonical for new work:

| Concept                   | Canonical Term  | Avoid as a New Domain Term                 |
| ------------------------- | --------------- | ------------------------------------------ |
| Business entity           | Tenant          | Business entity, Merchant entity           |
| Business operator         | Owner           | Seller                                     |
| Bookable offering         | Service         | Program                                    |
| Available time            | Schedule        | Slot entity, Time program                  |
| Person making reservation | Customer        | User                                       |
| Reservation               | Booking         | Order, Reservation record                  |
| Financial transaction     | Payment         | Transaction, unless used generically       |
| Subscription package      | Plan            | Package, if ambiguous                      |
| Tenant entitlement        | Subscription    | Membership                                 |
| Optional booking extra    | Additional Item | Add-on entity, unless explicitly specified |
| Promotional discount      | Voucher         | Coupon, unless explicitly specified        |
| Customer feedback         | Review          | Rating entity                              |

User-facing copy may use natural Indonesian terminology where appropriate.

However, the underlying domain concept should remain aligned with the canonical terminology.

---

# 23. Legacy Terminology Compatibility

The current codebase contains terminology from earlier development stages.

Examples may include:

```text
Program
namalayanan
idlayanan
services
Business Services
```

These should not be interpreted as separate product concepts.

The product definition standardizes them under:

> **Service**

Existing code should not necessarily be renamed immediately.

Refactoring terminology is an architectural task and should be handled separately from product definition.

---

# 24. Current-State Interpretation Rule

When an existing implementation contains behavior that is consistent with the current product definition, it should be treated as an implementation of the current product even if the behavior did not exist in the original SRS.

The absence of a feature from the historical SRS does not automatically mean the feature is invalid.

However:

> A feature existing in source code does not automatically mean it is a permanent product requirement.

A feature becomes part of the authoritative product when it is explicitly represented in the current product and requirements documentation.

This distinction prevents accidental scope expansion.

---

# 25. Product vs Implementation

The following distinction must be maintained.

## Product Definition

Describes:

* what BookQu does;
* who uses it;
* what concepts exist;
* what business problem it solves;
* what belongs to the product.

## Requirements

Describes:

* exactly what the system must do;
* business rules;
* acceptance criteria;
* constraints;
* measurable behavior.

## Architecture

Describes:

* how the software is structured;
* how responsibilities are separated;
* how data flows through the system;
* how the application should be implemented.

## Development Tracker

Describes:

* implementation status;
* ownership;
* progress;
* testing state;
* refactoring state.

These documents must not replace one another.

---

# 26. Product Completeness Rule

A feature should not be considered fully defined simply because a screen exists.

A product capability should conceptually answer:

```text
Who uses it?
Why does it exist?
What business problem does it solve?
Which domain concept does it operate on?
How does it affect the core product?
What boundaries does it have?
```

Detailed functional behavior belongs in `REQUIREMENTS.md`.

---

# 27. Current Product Definition Summary

BookQu is:

> **A multi-tenant booking and reservation management platform that enables schedule-based businesses to publish bookable services, manage availability and reservations, handle customers and booking-related payments, and monitor their business operations from a centralized platform.**

The primary operational flow is:

```text
Tenant
  ↓
Services
  ↓
Schedules
  ↓
Customer Booking
  ↓
Payment / Confirmation
  ↓
Booking Management
  ↓
Customer History
  ↓
Business Insight
```

The Owner Portal is the operational center.

The Customer Portal is the reservation interface.

The Admin Portal is the platform-level management interface.

The booking domain is the center of the product.

All supporting capabilities must remain aligned with that model.

---

# 28. Authority and Change Rule

This document represents the current product definition.

When a new feature or behavior is proposed:

1. Determine whether it belongs to the BookQu product.
2. Determine which existing domain concept it affects.
3. Determine whether it changes the core product flow.
4. Update the appropriate requirement before treating the behavior as an official requirement.
5. Update architecture documentation when the change affects technical structure.
6. Update the development tracker.
7. Implement and test the change.

No contributor or AI agent should silently redefine the product through code.

If the implementation and this document disagree, the disagreement must be treated as a product/documentation conflict rather than silently resolved through code.

---

# 29. Related Documents

The current documentation hierarchy is:

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

Additional architectural decisions are stored in:

```text
docs/adr/
```

Historical project documents are stored in:

```text
docs/archive/
```

Historical documents must not be treated as current product authority.

---

# 30. Document Status

This document defines the current conceptual identity and scope of BookQu.

It intentionally does not contain detailed technical implementation rules.

The next authoritative document to define is:

```text
docs/REQUIREMENTS.md
```

That document will convert the product concepts defined here into explicit, testable functional and non-functional requirements.
