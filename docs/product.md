# Product

What BookQu is, who it serves, which capabilities belong to it, and which are released. Required behaviour is in `docs/requirements.md`; terms are defined in `docs/glossary.md`.

**Principle:** code implements the product; code never silently redefines it. A route, screen, table or half-built feature does not make something part of the product (and absence from an older spec does not remove an accepted capability). New capabilities go through a product change (`docs/workflow.md` §2).

## 1. What BookQu is

A multi-tenant web platform where schedule-based businesses (sports courts, studios, consultants, …) publish bookable services, open schedules, take bookings and payments, and manage customers — each business isolated as its own **tenant**.

The problem it solves: businesses that take reservations through chat, phone, spreadsheets and paper struggle to keep availability consistent, avoid double bookings, keep history and see how the business is doing. BookQu puts the whole booking operation in one system.

BookQu is an **operational booking platform**. It is not an accounting system, ERP, full CRM, marketing-automation tool, inventory system or social-media manager.

## 2. Users

| User | Goal | Account |
|---|---|---|
| **Owner** | Run the business: services, schedules, bookings, customers, subscription | Authenticated user, role `owner` |
| **Customer** | Find a service, pick a time, pay, manage the booking, leave a review | No account; access through tokenized management links |
| **Platform admin** | Oversee the platform | Authenticated user, role `admin` |

## 3. Core loop

1. Business setup → 2. Service configuration → 3. Schedule configuration → 4. Customer selects service → 5. date → 6. time → 7. completes booking → 8. payment / confirmation → 9. booking record → 10. owner manages the booking → 11. customer and booking history → 12. business insight.

Supporting capabilities must strengthen this loop, never compete with it.

## 4. Product principles

1. **Booking is the central transaction.** Supporting modules extend it; none introduces a competing reservation concept.
2. **Service ≠ Schedule.** A service is *what* can be booked; a schedule is *when*.
3. **Booking ≠ Payment.** A booking is the reservation; a payment is the money transaction. Separate lifecycles.
4. **Tenant isolation is part of the product**, not an implementation detail.
5. **Customer ≠ User.** Customers never need an account to book or manage a booking.
6. **Supporting features do not redefine core concepts** (service, schedule, booking, customer, payment, tenant).

## 5. Product areas

### Owner portal (`/owner`)

| Capability | Area |
|---|---|
| Dashboard and live notifications | Core |
| Calendar (schedules and bookings by time) | Core |
| Services and categories | Core |
| Schedules: bulk creation, slot pricing (weekend rule, per-slot override), availability, blocked dates | Core |
| Bookings: list, details, status changes, walk-in bookings, rescheduling, cancellation | Core |
| Customers: directory, booking history, internal notes | Core (operational view, not a full CRM) |
| Reviews: moderation, reply, visibility | Supporting |
| Business profile and account settings | Core |
| Subscription and checkout | Platform |
| Analytics, schedule utilization report | Supporting |
| Staff & resources, additional items, vouchers, appearance, media assets, landing-page builder, integrations, platform balance | Supporting (see release tiers) |

### Customer portal (`/{slug}` or custom domain)

Public business page → select service → select date → select time (one or several contiguous slots) → checkout (contact details, price) → payment → confirmation and invoice. Afterwards, through the management link: view booking and payment, cancel or reschedule an eligible booking, view the invoice, submit a review.

- **Walk-in booking:** the owner records a booking for an on-site customer; same booking rules, created as paid.
- **Multi-slot booking:** several contiguous slots form one reservation with one payment and one invoice; it is cancelled or rescheduled as a whole or not at all.

### Admin portal (`/admin`)

Platform-level dashboard (tenants, subscriptions, subscription revenue). Admin permissions are separate from owner permissions.

### Public site

BookQu landing page with plan pricing, privacy policy, terms and conditions, sitemap.

## 6. Payment model

- Two payment purposes: **booking payment** (customer pays the tenant) and **subscription payment** (tenant pays BookQu). They are different operations even when they use the same provider.
- **Platform escrow:** all payments run through BookQu's own gateway account — SingaPay by default, Midtrans as alternative, switchable by configuration (ADR-0001). Tenant-owned gateway credentials are not offered (Tier 3, hidden).
- Free services skip the gateway.

## 7. Subscription model

Plan (`small`, `medium`, `pro`) → subscription (trial / active / expired / cancelled) → entitlement (features and limits such as services, staff and monthly bookings). New tenants get a 7-day trial with Pro-level access. Analytics require `medium`; the landing-page builder requires `pro`.

## 8. Release tiers

BookQu releases capabilities in stages to keep booking and payment stable (ADR-0002). `tests/Feature/Owner/OwnerSidebarNavigationTest.php` enforces the navigation.

| Tier | Meaning | Capabilities |
|---|---|---|
| **1 — Live** | Released, in navigation | Dashboard & notifications, calendar & schedules, services & categories, bookings (status, walk-in, reschedule, cancel), customers & notes, reviews, business profile & account, unified payment gateway, customer self-service portal, subscription & checkout |
| **2 — Locked** | Visible with a "Fitur Sedang Dikembangkan" / "Coming Soon" notice | Analytics (`/owner/analytics`), schedule report (`/owner/schedule-report`), integrations (WhatsApp, Google Calendar), platform balance (withdrawal on hold) |
| **3 — Hidden** | Backend exists, no navigation entry | Vouchers (checkout endpoint returns 422), additional items, staff & resources, appearance, direct payment-gateway settings, media assets, landing-page builder |

**Rules:** do not unhide Tier 3 or remove Tier 2 locks without lead approval and a release plan. Promoting a module whose requirements already exist (e.g. vouchers, FR-VOUCHER-*) is a **release decision** by the lead; changing its behaviour beyond those requirements is a **product change**. Both start as a `proposed` work item. Hiding a link is not access control — server-side gating of Tier 3 is tracked in BQ-008. Promoting a module: finish its end-to-end flow, update `OwnerSidebarNavigationTest`, then this table, `requirements.md` and the domain file.

## 9. Scope

**Out of scope** unless accepted as a product change: accounting / financial management, ERP, general-purpose CRM, inventory, social-media management, marketing automation, AI-made business decisions, unspecified integrations, multi-location management, advanced payment automation.

**Possible future directions** (not requirements): Google Calendar sync, WhatsApp automation, additional payment providers, multi-location, advanced analytics and segmentation, AI-assisted insights.
