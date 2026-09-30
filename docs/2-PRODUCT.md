# BookQu Product Definition

> **Document Status:** Current Product Definition
> **Version:** 1.0
> **Authority:** Current Product Source of Truth
> **Applies To:** Current BookQu product and future product development
> **Last Updated:** 2026-09-30
>
> This document defines what BookQu is, who it serves, what capabilities belong to the product, and how the product domain should be understood.
>
> This document defines product meaning and scope. It does not define detailed technical architecture, implementation structure, operational procedures, or architectural rationale.

---

# 1. Purpose

This document establishes a shared and stable definition of the BookQu product.

Its purpose is to ensure that:

* developers understand the same product scope;
* AI agents interpret product behavior consistently;
* business concepts use consistent terminology;
* future requirements can be derived from a common product definition;
* technical implementation can evolve without silently changing the meaning of the product;
* new capabilities are evaluated against the existing product boundary.

This document is the authority for determining:

> **What BookQu is as a product.**

Detailed system behavior belongs in `docs/3-REQUIREMENT.md`.

Technical structure belongs in `docs/4-ARCHITECTURE.md` and `docs/7-SYSTEM-DESIGN.md`.

Development procedures belong in `docs/5-DEVELOPMENT.md`.

---

# 2. Product Overview

## 2.1 What Is BookQu?

BookQu is a multi-tenant web-based booking and reservation management platform for businesses that provide time-based or schedule-based services.

BookQu enables a business to:

* establish a public business presence;
* define bookable services;
* configure available schedules;
* receive and manage customer bookings;
* manage booking operations;
* maintain customer information and booking history;
* manage supporting business capabilities;
* handle booking-related payments;
* manage its BookQu subscription;
* monitor business activity and operational performance;
* customize the presentation of its public business page.

Customers can use BookQu to:

* access a business's public booking page;
* discover available services;
* select a valid date and time;
* provide booking information;
* complete payment where required;
* receive booking information;
* manage eligible bookings after creation;
* provide reviews where eligible.

BookQu is primarily an **operational booking platform**.

It is not intended to be interpreted as a general-purpose:

* accounting system;
* ERP;
* full CRM;
* marketing automation platform;
* social media management platform;
* general inventory management platform.

Supporting capabilities may exist around the booking workflow, but booking and scheduling remain the central purpose of the product.

---

# 3. Product Problem

Many schedule-based businesses manage reservations through combinations of:

* chat applications;
* spreadsheets;
* manual calendars;
* phone calls;
* social media messages;
* handwritten records;
* manually maintained schedules.

These approaches can make it difficult to:

* maintain consistent availability;
* prevent scheduling conflicts;
* maintain booking history;
* organize customer information;
* provide a structured customer booking experience;
* monitor business activity.

BookQu centralizes the booking operation into one system.

The central problem BookQu addresses is:

> **How can a schedule-based business provide customers with a structured booking experience while allowing the business owner to manage services, schedules, reservations, customers, and business activity from one system?**

---

# 4. Product Goal

The primary goal of BookQu is to provide a reliable and structured booking workflow from business configuration through reservation management.

The core product loop is:

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

Supporting capabilities should strengthen this workflow rather than replace it as the primary purpose of the platform.

---

# 5. Target Users

BookQu currently serves three principal user groups.

## 5.1 Business Owner

The business owner operates a business through BookQu.

The owner is responsible for activities such as:

* configuring business information;
* managing services;
* managing schedules;
* managing bookings;
* managing customer information;
* monitoring business activity;
* managing supporting business capabilities;
* managing the business's BookQu subscription.

The owner is the primary authenticated operational user.

---

## 5.2 Customer

A customer is a person who wants to book a service provided by a business.

A customer can:

* access a business's public booking page;
* view available services;
* select a booking date;
* select an available time;
* provide booking information;
* complete payment when required;
* receive booking and payment information;
* access eligible booking management functions;
* cancel or reschedule an eligible booking;
* submit a review where eligible.

A customer does not need to maintain a normal BookQu owner account to use the public booking flow.

Customer identity is represented through customer and booking information associated with the reservation.

---

## 5.3 Platform Admin

The platform admin operates at the BookQu platform level rather than within an individual business.

The platform admin is separate from a business owner.

The platform admin is responsible for platform-level administration and oversight capabilities supported by BookQu.

The existence of an administrative role does not imply that every possible platform management capability is part of the product.

---

# 6. Core Product Model

The conceptual BookQu model is:

```text
User
  │
  └── operates
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
        ├── Categories
        ├── Staff
        ├── Resources
        ├── Additional Items
        ├── Vouchers
        ├── Reviews
        ├── Assets / Appearance
        └── Subscription
```

This model describes the product domain conceptually.

It does not prescribe the technical database structure.

---

# 7. Canonical Product Terminology

Terminology must remain consistent across product documentation, requirements, UI language, implementation discussions, tests, and AI-agent instructions.

## 7.1 User

`User` represents an authenticated identity in the BookQu platform.

A user may operate with a platform role such as:

* owner;
* admin.

A user is not the same concept as a business or tenant.

---

## 7.2 Tenant

`Tenant` is the canonical domain term for a business operating inside BookQu.

A tenant represents one business account and its isolated operational context.

For user-facing language, the term **Business** may be used where natural.

Therefore:

```text
Domain term:
Tenant

User-facing term:
Business
```

A separate `Business` domain entity should not be introduced unless a future requirement explicitly requires such a distinction.

---

## 7.3 Owner

`Owner` is the person who operates and manages a tenant/business through BookQu.

The owner is represented by an authenticated BookQu user.

---

## 7.4 Customer

`Customer` represents the person making or receiving a booking.

A customer is distinct from an authenticated BookQu owner/admin user.

A customer can use the public booking flow without maintaining a normal BookQu owner account.

---

## 7.5 Service

`Service` is the canonical product term for something a customer can book.

Examples include:

* badminton court rental;
* photography studio session;
* music studio session;
* consultation session.

The following terms should not be introduced as separate product concepts unless a future requirement explicitly defines them:

```text
Program
Layanan
Business Service
Bookable Program
```

The canonical product concept is:

> **Service**

---

## 7.6 Schedule

`Schedule` represents a bookable time period associated with a service.

Conceptually, a schedule defines:

* date;
* start time;
* end time;
* booking availability.

A schedule is not the same as general business operating hours.

---

## 7.7 Availability

`Availability` represents whether a service can currently be booked for a particular date and time.

Availability is a business concept derived from schedule and booking conditions.

It should not automatically be treated as a separate primary product entity.

---

## 7.8 Booking

`Booking` represents a reservation made for a service at one or more eligible schedules.

Booking is the central operational transaction of BookQu.

A booking may originate from:

* a customer booking;
* an owner-created walk-in booking.

---

## 7.9 Payment

`Payment` represents a financial transaction associated with a BookQu business operation.

BookQu currently includes payment contexts such as:

* booking payment;
* subscription payment.

Payment and booking remain separate product concepts.

---

## 7.10 Plan

`Plan` represents a BookQu subscription package definition.

A plan defines the package-level capabilities or entitlements associated with a subscription level.

---

## 7.11 Subscription

`Subscription` represents a tenant's relationship with a BookQu plan.

Conceptually:

```text
Plan
=
Available subscription package

Subscription
=
A tenant's subscription state and entitlement
```

Plan and Subscription must not be treated as interchangeable concepts.

---

## 7.12 Staff

`Staff` represents a person participating in a business's operational activities.

Staff management is a supporting capability.

Staff does not automatically imply that customers can select a staff member during booking unless such behavior is explicitly defined by product requirements.

---

## 7.13 Resource

`Resource` represents a physical or operational asset associated with a business or service.

Examples may include:

* court;
* room;
* studio;
* equipment;
* facility.

Staff and Resource are related operational concepts but are not the same concept.

---

## 7.14 Additional Item

`Additional Item` represents an optional extra associated with a service or booking.

Examples may include:

* equipment rental;
* additional facilities;
* optional extras.

Additional Items extend a booking; they do not replace the service being booked.

---

## 7.15 Voucher

`Voucher` represents a promotional discount mechanism that may be applied to an eligible booking.

The exact discount rules belong to the requirements authority.

---

## 7.16 Review

`Review` represents customer feedback associated with an eligible booking.

Reviews belong to the post-booking customer experience rather than the core booking creation process.

---

## 7.17 Public Business Page

The public business page is the customer-facing entry point for a tenant.

It allows customers to:

* discover the business;
* view available services;
* begin the booking flow;
* access customer-facing business information.

The canonical public identity is associated with the tenant's public slug and may support custom-domain access where configured.

---

# 8. Product Areas

BookQu is conceptually divided into three major user-facing areas:

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

These areas have different responsibilities.

---

# 9. Owner Portal

The Owner Portal is the primary operational workspace for a business.

## 9.1 Dashboard

The dashboard provides an overview of business activity.

Current dashboard concepts include:

* bookings;
* revenue-related information;
* customers;
* services;
* trends;
* upcoming schedules;
* recent activity;
* operational statistics.

The dashboard is an aggregation and monitoring surface.

It should not become the conceptual source of truth for unrelated business domains.

---

## 9.2 Calendar

The calendar provides a time-oriented operational view of schedules and bookings.

It helps owners understand:

* available schedules;
* occupied schedules;
* booking activity;
* dates;
* operating periods.

The calendar is a view of scheduling and booking information rather than a separate scheduling domain.

---

## 9.3 Service Management

Service Management defines what customers can book.

A service may contain concepts such as:

* name;
* description;
* price;
* duration;
* capacity;
* active/inactive state;
* presentation information;
* category association.

Service Management forms one of the core product capabilities.

---

## 9.4 Schedule Management

Schedule Management defines when services are available for booking.

The product supports concepts such as:

* creating schedules;
* generating schedules in bulk;
* configuring availability;
* defining pricing-related schedule information;
* blocking unavailable periods;
* removing schedules;
* preventing scheduling conflicts.

Schedule Management is a core product capability.

---

## 9.5 Booking Management

Booking Management allows an owner to operate reservations after they are created.

It includes capabilities such as:

* viewing bookings;
* viewing booking details;
* managing booking status;
* creating walk-in bookings;
* rescheduling eligible bookings;
* viewing booking and payment information;
* performing supported operational actions.

---

## 9.6 Customer Management

Customer Management allows the business to access customer information and booking history.

It provides an operational view of:

* customers;
* customer details;
* booking history;
* customer-related information supported by the product.

Customer Management is an operational capability rather than a full CRM system.

---

## 9.7 Categories

Categories organize services into meaningful groups.

Categories support organization and discovery.

They do not replace services as the core bookable offering.

---

## 9.8 Staff and Resources

Staff and Resources provide additional operational structure for businesses that need to manage people or physical resources.

These capabilities are supporting features and are not required for every booking scenario.

---

## 9.9 Additional Items

Additional Items allow a business to provide optional extras together with a service.

They extend the booking experience without replacing the primary service.

---

## 9.10 Vouchers

Vouchers provide promotional discount functionality.

The product may support concepts such as:

* discount type;
* discount amount;
* validity;
* minimum transaction;
* usage limits.

Detailed voucher behavior is defined in `docs/3-REQUIREMENT.md`.

---

## 9.11 Reviews

Reviews provide a mechanism for customers to submit feedback after eligible bookings.

Reviews support the customer experience and business feedback loop.

---

## 9.12 Analytics and Reports

Analytics and reports provide summarized business information.

Current product concepts include:

* booking metrics;
* revenue-related metrics;
* customer metrics;
* service performance;
* schedule utilization;
* report generation;
* reporting/export capabilities where supported.

Analytics and reports consume operational information.

They should not become an alternative source of truth for booking records.

---

## 9.13 Appearance and Public Presentation

BookQu provides capabilities for customizing the business's public presentation.

These may include:

* logo;
* branding;
* colors;
* banners;
* cover imagery;
* public page presentation.

Appearance affects presentation rather than the fundamental booking rules.

---

## 9.14 Assets

Assets support the business's public presentation and media needs.

Examples include:

* logos;
* service images;
* banners;
* public page media.

Assets are supporting presentation resources rather than core booking records.

---

## 9.15 Notifications

Notifications communicate important business events.

Examples include:

* new booking;
* booking status changes;
* payment-related events;
* subscription-related events.

Notifications support operational awareness rather than defining the underlying business state.

---

## 9.16 Subscription Management

Subscription Management controls the tenant's relationship with the BookQu platform.

Product concepts include:

* plans;
* subscriptions;
* trial;
* package-based access;
* feature entitlements;
* subscription payment;
* subscription lifecycle.

Detailed subscription rules belong in `docs/3-REQUIREMENT.md`.

---

# 10. Customer Portal

The Customer Portal provides the public booking experience.

The primary customer flow is:

```text
Public Business Page
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
Booking Confirmation
```

---

## 10.1 Service Selection

The customer selects an active service that is available for booking.

---

## 10.2 Date Selection

The customer selects a valid booking date.

The available dates should correspond to the service's scheduling and availability rules.

---

## 10.3 Time Selection

The customer selects an available time.

Unavailable or occupied schedules must not be represented as normally bookable.

---

## 10.4 Checkout

Checkout presents the reservation before completion.

Relevant concepts may include:

* customer information;
* selected service;
* selected schedule;
* pricing;
* additional items;
* voucher;
* total amount;
* applicable booking information.

---

## 10.5 Payment

Where payment is required, the customer proceeds through the supported payment flow.

BookQu currently supports online payment through its payment integration.

Payment remains a separate product concept from booking status.

---

## 10.6 Booking Confirmation and Invoice

After the booking/payment flow succeeds, the customer can receive booking and financial information associated with the transaction.

The confirmation and invoice represent recorded transaction information.

---

# 11. Customer Booking Management

Customers can manage eligible bookings without requiring a normal owner account.

The product supports a secure management flow that can provide capabilities such as:

* viewing booking information;
* viewing payment information;
* cancelling eligible bookings;
* rescheduling eligible bookings;
* viewing invoice information;
* submitting reviews where eligible.

Access to management capabilities is restricted according to the booking's management authorization rules.

---

# 12. Walk-In Booking

BookQu supports bookings created by an owner on behalf of a customer.

Conceptually:

```text
Online Booking

Customer
   ↓
BookQu
   ↓
Booking


Walk-In Booking

Owner
   ↓
BookQu
   ↓
Booking
```

Both flows produce bookings within the same core booking system.

Walk-in booking is therefore a booking-entry method, not a separate product domain.

---

# 13. Multi-Slot Booking

BookQu supports booking scenarios involving multiple eligible schedules where the product rules allow it.

Conceptually:

```text
Customer
   ↓
Select multiple compatible schedules
   ↓
One booking operation
   ↓
One reservation context
```

A multi-slot reservation remains one customer booking concept even when the implementation internally represents multiple schedule reservations.

This distinction is important for:

* payment;
* booking management;
* cancellation;
* rescheduling;
* invoice;
* availability.

---

# 14. Payment Product Model

BookQu currently has two major payment contexts:

```text
Booking Payment
      ↓
Payment for a customer reservation

Subscription Payment
      ↓
Payment for the business's BookQu subscription
```

These are different business purposes.

A booking payment and a subscription payment must not be treated as the same product operation merely because they use a common payment provider.

Payment state is also conceptually separate from booking state.

Detailed payment behavior is defined in `docs/3-REQUIREMENT.md`.

Technical payment integration belongs in `docs/4-ARCHITECTURE.md` and `docs/7-SYSTEM-DESIGN.md`.

---

# 15. Subscription Product Model

BookQu operates as a subscription-based platform.

The conceptual model is:

```text
Plan
   ↓
Subscription
   ↓
Tenant Entitlement
   ↓
Feature / Usage Access
```

The product may support lifecycle concepts such as:

* trial;
* active subscription;
* expired subscription;
* cancelled subscription.

The exact transition rules and entitlement behavior are requirements-level concerns.

---

# 16. Multi-Tenancy

BookQu is fundamentally multi-tenant.

Each business operates inside an isolated tenant context.

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

Tenant isolation is a fundamental product property.

Operational data belonging to one tenant must not become accessible to another tenant.

---

# 17. Public Tenant Access

Each tenant has a public-facing booking presence.

The conceptual model is:

```text
BookQu
   ↓
Tenant
   ↓
Public Business Page
   ↓
Customer Booking
```

The product supports a tenant-specific public identity and may support custom-domain access where configured.

The public business page is the primary entry point into the customer booking experience.

---

# 18. Core Product Loop

The core BookQu loop is:

```text
1. Business Setup
        ↓
2. Service Configuration
        ↓
3. Schedule Configuration
        ↓
4. Customer Selects Service
        ↓
5. Customer Selects Date
        ↓
6. Customer Selects Time
        ↓
7. Customer Completes Booking
        ↓
8. Payment / Confirmation
        ↓
9. Booking Record
        ↓
10. Owner Manages Booking
        ↓
11. Customer and Booking History
        ↓
12. Business Insight
```

This loop defines the primary purpose of the product.

New capabilities should be evaluated according to whether they:

* strengthen this loop;
* support an existing domain;
* introduce a new product capability that requires explicit acceptance.

---

# 19. Product Scope Classification

Current BookQu capabilities can be grouped into four conceptual categories.

## 19.1 Core Operations

These capabilities form the central purpose of BookQu:

```text
Business Setup
Business Profile
Service Management
Schedule Management
Availability
Customer Booking
Booking Management
Customer Records
Walk-In Booking
Booking Payment
Dashboard
Calendar
Public Booking Page
```

---

## 19.2 Supporting Operations

These capabilities strengthen the core booking product:

```text
Categories
Staff
Resources
Additional Items
Vouchers
Reviews
Analytics
Reports
Assets
Appearance
Notifications
```

---

## 19.3 Platform Capabilities

These capabilities operate across the BookQu platform:

```text
Authentication
Multi-Tenancy
Subscription
Plans
Trial
Feature Entitlements
Subscription Payments
Platform Administration
```

---

## 19.4 Expansion Capabilities

Potential future capabilities must not automatically be treated as current product requirements.

Examples of possible future expansion areas include:

```text
Google Calendar synchronization
Advanced external integrations
Advanced CRM
Automated marketing
Advanced customer segmentation
Multi-location management
Advanced analytics
WhatsApp automation
Additional payment integrations
AI-assisted business insights
```

These become part of the current product only when they are explicitly accepted and documented.

---

# 20. Product Boundaries

BookQu's primary responsibility is:

```text
Booking
+
Scheduling
+
Reservation Management
+
Customer Booking Operations
+
Booking-related Business Operations
```

Supporting capabilities may exist around these responsibilities.

However, BookQu should not automatically be interpreted as:

```text
Full Accounting System
Full ERP
Full CRM
Full Marketing Automation Platform
General Inventory System
Social Media Management Platform
```

Expansion into these areas requires explicit product definition and requirements.

---

# 21. Important Product Principles

## 21.1 Booking Is the Central Transaction

Booking is the central operational transaction of BookQu.

Supporting modules should strengthen the booking domain rather than create competing reservation concepts.

---

## 21.2 Service and Schedule Are Different Concepts

A service defines:

> What the customer can book.

A schedule defines:

> When the customer can book it.

These concepts must remain distinct.

---

## 21.3 Booking and Payment Are Different Concepts

A booking describes the reservation.

A payment describes the financial transaction.

The product must not treat them as the same lifecycle.

---

## 21.4 Tenant Isolation Is Fundamental

Tenant isolation is part of BookQu's product model, not merely an implementation optimization.

---

## 21.5 Customer Does Not Equal User

An authenticated BookQu user and a booking customer are different product concepts.

A customer does not need to become an owner/admin user merely to make or manage a reservation.

---

## 21.6 Supporting Features Must Not Redefine Core Concepts

Analytics, vouchers, reviews, additional items, staff, resources, assets, and similar capabilities should extend existing product concepts rather than introduce competing definitions of:

```text
Service
Schedule
Booking
Customer
Payment
Tenant
```

---

# 22. Product Terminology Rules

The following terms are canonical for new product and domain work.

| Concept                     | Canonical Term  | Do Not Introduce as a Separate Concept Without Explicit Requirement |
| --------------------------- | --------------- | ------------------------------------------------------------------- |
| Business entity             | Tenant          | Business entity                                                     |
| Business operator           | Owner           | Seller                                                              |
| Bookable offering           | Service         | Program                                                             |
| Bookable time               | Schedule        | Program slot                                                        |
| Person making a reservation | Customer        | User                                                                |
| Reservation                 | Booking         | Order                                                               |
| Financial transaction       | Payment         | Generic transaction as a domain replacement                         |
| Subscription package        | Plan            | Package where ambiguous                                             |
| Tenant subscription state   | Subscription    | Membership                                                          |
| Optional booking extra      | Additional Item | Separate add-on domain                                              |
| Promotional discount        | Voucher         | Separate coupon domain                                              |
| Customer feedback           | Review          | Separate rating domain                                              |

User-facing language may use natural Indonesian terms.

The underlying product concepts should remain aligned with the canonical terminology.

---

# 23. Legacy Terminology

Existing implementation may contain terminology inherited from earlier development stages.

Examples may include:

```text
Program
Layanan
Business Service
```

These terms do not automatically represent separate product concepts.

For new product, requirement, or architecture work, use:

> **Service**

Existing implementation terminology does not need to be renamed solely because the product terminology has been standardized.

Terminology migration is a technical change and should be handled through the appropriate development and architecture process.

---

# 24. Product Definition vs Implementation

The distinction between product and implementation must remain explicit.

## Product Definition

Answers:

```text
What is BookQu?
Who uses it?
What problem does it solve?
What concepts exist?
What capabilities belong to the product?
What boundaries does the product have?
```

## Requirements

Answers:

```text
What exact behavior is required?
What rules must be satisfied?
What are the acceptance conditions?
```

## Architecture

Answers:

```text
How should the software be structured?
Where should responsibilities belong?
What technical boundaries must be preserved?
```

## System Design

Answers:

```text
How does the current implementation actually work?
How do current components interact?
What current invariants must be preserved?
```

## Development

Answers:

```text
How should changes be performed safely?
```

## Tracker

Answers:

```text
What is currently implemented?
What remains?
What requires verification?
```

## Operations

Answers:

```text
How is the current system operated and verified?
```

These documents complement one another and must not become interchangeable.

---

# 25. Product Scope Rule

A capability should be treated as part of the current product only when its product meaning is explicitly established.

The presence of:

* a route;
* a UI screen;
* a database field;
* an unfinished implementation;
* a placeholder;
* a temporary experiment;

does not automatically establish a permanent product capability.

Likewise, the absence of a capability from an older product specification does not automatically make a currently accepted capability invalid.

The authoritative current product definition is this document.

---

# 26. Product Change Rule

When a new capability is proposed:

```text
1. Determine whether it belongs to BookQu.
2. Identify the product concept it affects.
3. Determine whether it changes the core product loop.
4. Update the product definition when the product scope changes.
5. Define the required behavior in docs/3-REQUIREMENT.md.
6. Update architectural documentation when the technical structure changes.
7. Implement and verify the change.
8. Update docs/6-TRACKER.md.
```

No contributor or AI agent should silently redefine BookQu through implementation.

A code change that introduces a new product capability without corresponding product and requirement decisions should be treated as a scope-control problem.

---

# 27. Future Product Evolution

The product definition is designed to remain stable while allowing BookQu to evolve.

Future product changes should preserve the distinction between:

```text
Existing Product
        +
Accepted New Capability
        +
Supporting Technical Change
```

A technical refactor does not automatically change the product.

A new product capability does not automatically require a new domain concept if an existing concept can represent it correctly.

New concepts should be introduced only when the product meaning genuinely requires them.

---

# 28. Current Product Summary

BookQu is:

> **A multi-tenant booking and reservation management platform that enables schedule-based businesses to publish bookable services, configure availability, receive and manage reservations, handle customer information and booking-related payments, and monitor business operations from a centralized platform.**

The core flow is:

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

The three principal interaction areas are:

```text
Owner Portal
→ Business Operations

Customer Portal
→ Booking Experience

Admin Portal
→ Platform Operations
```

The booking domain remains the center of the product.

Supporting capabilities should extend the booking experience without creating conflicting definitions of the core product.

---

# 29. Related Documents

The BookQu documentation system is organized as follows:

```text
AGENT.md
    ↓
AI agent behavior and working rules

docs/1-README.md
    ↓
Documentation map and source-of-truth guide

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
Current implementation and project status

docs/7-SYSTEM-DESIGN.md
    ↓
How the current system actually works

docs/8-OPERATIONS.md
    ↓
How the system is operated and verified

docs/adr/
    ↓
Why important architectural decisions were made
```

---

# 30. Document Status

This document is the current product definition for BookQu.

It defines:

```text
Product Identity
+
Product Scope
+
Product Concepts
+
Canonical Terminology
+
Core Product Flow
+
Product Boundaries
+
Product-Level Change Rules
```

It does not define:

```text
Detailed Requirements
Technical Architecture
Current Implementation Mapping
Operational Procedures
Architectural Decision Rationale
```

Those responsibilities belong to their respective documents.

The central principle is:

> **Code may implement the BookQu product, but code must not silently redefine what the BookQu product is.**
