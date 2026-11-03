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
| **Owner** (Pemilik) | Run the business: services, schedules, bookings, customers, money, subscription, team | Authenticated user, role `owner` |
| **Team member** (Manajer, Kasir) | Help run one business with a limited role (D-011, FR-TEAM) | Authenticated user invited by the owner; belongs to one tenant |
| **Customer** | Find a service, pick a time, pay, manage the booking, leave a review | No account; access through e-mailed access links (customer access grants, ADR-0006) |
| **Platform admin** | Oversee the platform | Authenticated user, role `admin` |

## 3. Core loop

1. Business setup → 2. Service configuration → 3. Schedule configuration → 4. Customer selects service → 5. date and time (one schedule step) → 6. completes booking → 7. payment / confirmation → 8. booking record → 9. owner manages the booking → 10. customer and booking history → 11. business insight.

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

Fifteen menus in five groups. The contents of each page, who may use it and its acceptance criteria are in [`owner-portal.md`](owner-portal.md); required behaviour is in `requirements.md`.

| Group | Menus | Area |
|---|---|---|
| — | Beranda (today and needs action), Kalender, Booking (with the shared booking panel: complete, no-show, reschedule, cancel, refund decisions), Pelanggan | Core |
| Bisnis saya | Layanan (services, categories, add-ons), Unit, Jam Buka & Harga (opening hours, price rules, closed dates, slots) | Core; Unit is Pro; add-ons Medium and up |
| Pemasaran | Halaman Booking (link, QR, promotion materials, appearance, media gallery, landing page and custom domain for Pro), Promo, Ulasan | Core; Promo Medium and up |
| Keuangan | Pembayaran (transactions, balance and payouts, payout accounts), Laporan | Platform; Laporan Medium and up |
| Lainnya | Pengaturan (business profile, booking policy, notifications, integrations, account, team), Paket & Tagihan, Bantuan | Core; team Medium and up; API Pro |

Every page has the notification bell, a "+ Booking" walk-in action and a search; phones use a bottom navigation bar.

### Customer portal (`/{slug}` or custom domain)

Three steps: **Layanan** (select service) → **Jadwal** (pick a date, then one or several contiguous slots, on one page) → **Data & Pembayaran** (contact details, participants, add-ons and promo code from Medium, price, payment) → confirmation and invoice. The page shows the business's own branding (D-009) and its booking policy before payment. A customer who leaves an unpaid checkout can resume it until the payment deadline. Afterwards, through the access link: view booking and payment, request a refund when the business allows it (D-001), reschedule to a slot of the same or a lower price (D-006), view the invoice, submit a review.

- **Walk-in booking:** the owner or a team member records a booking for an on-site customer; same booking rules, created as paid.
- **Multi-slot booking:** several contiguous slots form one reservation with one payment and one invoice; it is cancelled or rescheduled as a whole or not at all.

### Admin portal (`/admin`)

Platform-level dashboard (tenants, subscriptions, subscription revenue, waiting payouts and refunds), withdrawal review (`/admin/payouts`: approve, record the transfer and proof, or reject with a reason) and refund processing (D-001, FR-ADMIN-005). Admin permissions are separate from owner permissions. Further admin capabilities wait for D-003.

### Public site

BookQu landing page with plan pricing, privacy policy, terms and conditions, sitemap.

## 6. Payment model

- Two payment purposes: **booking payment** (customer pays the tenant) and **subscription payment** (tenant pays BookQu). They are different operations even when they use the same provider.
- **Platform escrow:** all payments run through BookQu's own gateway account — SingaPay by default, Midtrans as alternative, switchable by configuration (ADR-0001). Tenant-owned gateways are not offered, and stored tenant gateway credentials are removed (D-016).
- Successful booking payments become the tenant's platform balance (fee 0%), withdrawn to a saved payout account after admin review (D-004).
- **Refunds:** optional per tenant. Customers request; the owner decides 100%, 50% or rejects. A business cancellation of a booking paid online is always a full refund. The admin transfers the money and the balance is debited (D-001).
- Free services skip the gateway.

## 7. Subscription model

Plan (`small`, `medium`, `pro`) → subscription (trial / active / expired / cancelled) → entitlement (features and limits). New tenants get a 7-day trial with Pro-level access, once per owner account. Entitlement is decided only by `EntitlementRules` (INV-SUB-01).

This table is the home of the plan contents (D-017). Prices stay Small Rp149.000, Medium Rp299.000, Pro Rp499.000 per month.

| Capability | Small | Medium | Pro |
|---|---|---|---|
| Booking page, calendar and slots, bookings and walk-ins, customers, reviews, payments, balance and payouts, email notifications, invoices | ✓ | ✓ | ✓ |
| Logo and appearance, link, QR and promotion materials, media gallery | ✓ | ✓ | ✓ |
| Capacity per slot, no-show, booking policy, Google Calendar, Google Analytics, Meta Pixel | ✓ | ✓ | ✓ |
| Services | 5 | unlimited | unlimited |
| Bookings per month | 300 | 500 | unlimited |
| Reports and exports | blurred preview | ✓ | ✓ |
| Promos and add-ons | — | ✓ | ✓ |
| Team accounts (in addition to the owner) | 0 | 2 | 10 |
| Units | — | — | ✓ |
| Landing page and custom domain | — | — | ✓ |
| API and webhooks | — | — | ✓ |

When the trial or subscription ends, the business keeps managing what exists but creates nothing new, and the public page takes no new bookings; renewing the same plan extends from the current end date (D-002, FR-SUB-012, FR-SUB-013).

## 8. Release tiers

BookQu releases capabilities in stages to keep booking and payment stable (ADR-0002). The server enforces each module's tier through the registry `config/modules.php` and the `module:<name>` middleware (ADR-0011): `hidden` answers 404, `locked` shows the lock page and refuses writes. `tests/Feature/Owner/OwnerSidebarNavigationTest.php` enforces the navigation.

| Tier | Meaning | Capabilities |
|---|---|---|
| **1 — Live** | Released, in navigation | Dashboard & notifications, calendar & schedules, services & categories, bookings (status, walk-in, reschedule, cancel), customers & notes, reviews, business profile & account, unified payment gateway, customer self-service portal, subscription & checkout, platform balance & withdrawals |
| **2 — Locked** | Visible with a "Fitur Sedang Dikembangkan" / "Coming Soon" notice | Analytics (`/owner/analytics`), schedule report (`/owner/schedule-report`), integrations (WhatsApp, Google Calendar) |
| **3 — Hidden** | Backend exists, no navigation entry, routes answer 404 | Vouchers, additional items, staff & resources, appearance, direct payment-gateway settings, media assets, landing-page builder |

**Target at the first production release (D-018):** production is released only after every capability of the owner portal redesign is built and tested. At that release every capability in §5 and §7 is Tier 1 for the plans that include it; direct payment-gateway settings are removed (D-016) and the integrations page is replaced by real integrations (D-019). How the module registry and plan gates represent this is decided in ADR-0021 (BQ-126 phase 6). Until then the table above describes the current code.

**Rules:** do not unhide Tier 3 or remove Tier 2 locks without a release plan approved by the Product Lead and Tech Lead. Promoting a module whose requirements already exist (e.g. vouchers, FR-VOUCHER-*) is a **release decision**; changing its behaviour beyond those requirements is a **product change**. Both start as a `proposed` work item. Promoting a module: finish its end-to-end flow, change its state in `config/modules.php`, update `OwnerSidebarNavigationTest`, then this table, `requirements.md` and the domain file.

## 9. Scope

**Out of scope** unless accepted as a product change: accounting / financial management, ERP, general-purpose CRM, inventory, social-media management, marketing automation, AI-made business decisions, unspecified integrations, multi-location management, advanced payment automation, tenant-owned payment gateways (D-016), customers choosing a staff member or unit.

**Deferred until after the first production release (D-021):** automatic slot opening from opening hours, automatic closing on national holidays, automatic suggestions in reports, down payments, WhatsApp reminders and the WhatsApp Business integration, custom roles.

**Possible future directions** (not requirements): additional payment providers, multi-location, advanced analytics and segmentation, AI-assisted insights.
