# Requirements

What BookQu must do. Product meaning is in `docs/product.md`; how the current code meets each requirement is in `docs/domains/`; work to close gaps is in `docs/work/`.

## How to use this document

- IDs: `FR-{DOMAIN}-{NNN}` functional, `BR-{DOMAIN}-{NNN}` business rule, `NFR-{AREA}-{NNN}` non-functional, `AC-…` acceptance criteria. IDs are stable — never renumber or reuse them.
- Every behaviour change references an ID. No matching ID means a product change: the Product Lead decides, then this file is updated **before** code (`docs/workflow.md` §2).
- Requirements describe behaviour, not classes. Implementation status lives in the domain files, not here.
- Entries marked **Proposed** describe behaviour that exists in code or is needed but has not been formally accepted; the Product Lead accepts or rejects them.
- A requirement is never marked verified because a screen or code path exists; verification needs evidence (`docs/workflow.md` §7).
- Entries marked **Accepted 2026-11-04, D-NNN** come from the owner portal redesign (BQ-126). Which page offers each capability is in `docs/owner-portal.md`; the plan that unlocks it is in `docs/product.md` §7.
- A requirement that no longer applies is marked **Withdrawn**, with the date, the decision and its replacement. It is never deleted, and its ID is never reused.

## Actors

BookQu has four primary actors.

### Owner

The business owner who operates a tenant.

The owner can manage business operations and access the Owner Portal. In the Owner Portal the owner has the role **Pemilik**.

### Team Member

A person invited by the owner to sign in to the owner's business with a limited role (**Manajer** or **Kasir**, FR-TEAM-002). A team member acts only inside that one tenant and never holds money, plan or team management rights.

### Customer

A person making or managing a booking.

A customer does not require a normal BookQu owner account for the public booking flow.

### Platform Admin

A platform-level administrator responsible for permitted BookQu platform operations.

The admin is separate from a tenant owner.

Platform-level permissions must not be treated as an extension of normal owner permissions.

## Product Scope

The current requirement baseline covers:

```text
Core Operations
├── Authentication and Team Accounts
├── Tenant / Business Setup and Booking Policy
├── Public Business Page, Booking Link and Promotion Materials
├── Service Management (Categories, Add-ons)
├── Units
├── Opening Hours, Pricing and Slots
├── Booking (No-show, Capacity)
├── Refund Requests
├── Customer Management
├── Booking Payment, Balance and Payouts
├── Dashboard
└── Calendar

Supporting Operations
├── Promos
├── Reviews
├── Reports (Analytics, Occupancy, Export)
├── Appearance and Media Gallery
├── Landing Page and Custom Domain
├── Integrations and API
├── Notifications and Preferences
└── Help

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

## Authentication Requirements

### FR-AUTH-001 — Owner Registration

The system shall allow a prospective owner to create a BookQu account.

The registration process shall collect the information required by the current onboarding flow.

### FR-AUTH-002 — Owner Login

The system shall allow a registered owner to authenticate using supported credentials.

Authentication failure shall not grant access to protected owner functionality.

### FR-AUTH-003 — Owner Logout

The system shall allow an authenticated owner to terminate the current authenticated session.

### FR-AUTH-004 — Email Verification

The system shall support email verification where required by the authentication flow.

An account requiring verification shall not be treated as fully verified before the verification requirement is satisfied.

### FR-AUTH-005 — Role-Based Access

The system shall distinguish at least:

```text
owner        (Pemilik of one tenant)
team member  (Manajer or Kasir of one tenant, FR-TEAM)
admin        (platform)
```

A user must not access functionality outside the permissions associated with the user's role. Amended 2026-11-04 (D-011).

### FR-AUTH-006 — Password Security

Passwords shall never be stored as plaintext.

Passwords shall use a secure password hashing mechanism.

### FR-AUTH-007 — Password Reset (Accepted 2026-11-04, D-022)

An owner who forgot the password can request a reset link by e-mail and set a new password with a valid, expiring token. Implemented in commit `ab24193`.

### FR-AUTH-008 — Authentication Rate Limiting (Accepted 2026-11-04, D-022)

Login, registration, password-reset requests and other public state-changing endpoints are rate limited per account and per IP. Tracked in BQ-007.

## Tenant and Business Requirements

### FR-TENANT-001 — Tenant Association

The system shall associate an owner with the correct tenant/business.

### FR-TENANT-002 — Tenant Isolation

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

### FR-TENANT-003 — Tenant Context

Tenant-specific operations shall execute within the correct tenant context.

Client-provided tenant identifiers must not independently establish authorization.

### FR-TENANT-004 — Business Profile

The owner shall be able to manage business information used by the public booking page and owner portal.

Business information may include:

* business name;
* business type;
* phone number;
* address;
* description;
* contact information;
* operational information.

Amended 2026-11-04 (D-007 Revisi 1): business information also includes a map link, a WhatsApp number, a business email and the time zone (WIB only for now).

### FR-TENANT-005 — Business Slug

The system shall provide a unique public slug for each tenant where the slug is used as the default public identity.

### FR-TENANT-006 — Public Tenant Access

The system shall provide a public customer-facing page based on the tenant's public identity.

The default public access pattern is:

```text
/{tenant-slug}
```

### FR-TENANT-007 — Custom Domain

Where supported and configured, a tenant may expose its public page through a custom domain.

The custom domain must resolve to the correct tenant.

Amended 2026-11-04 (D-005): the custom domain is a Pro feature, set on the Halaman Booking page with DNS instructions and an automatic check that reports whether the domain is connected. A domain can belong to only one tenant. Until it is connected, every public link uses the BookQu address.

### FR-TENANT-008 — Booking Policy (Accepted 2026-11-04, D-001, D-020)

The tenant has one booking policy: refunds on or off (FR-REFUND-001), the cancellation and refund-request deadline, the reschedule deadline (both in hours before the session), and the booking window rules (FR-BOOKING-022). Customers see a summary of the policy before paying. The deadlines set here are the ones the booking rules enforce.

## Public Business Page Requirements

### FR-PUBLIC-001 — Public Business Page

The system shall provide a public customer-facing page for an eligible tenant.

The page shall provide sufficient information for customers to understand the available booking offering.

### FR-PUBLIC-002 — Public Services

The public page shall display eligible active services for the tenant.

Inactive services shall not normally appear as new bookable services.

### FR-PUBLIC-003 — Public Branding

Where configured, the public page shall use the tenant's supported branding information.

Branding may include:

* logo;
* brand color;
* banner;
* business imagery.

Amended 2026-11-04 (D-009): branding is available to every plan. It also covers button style, card style, font and description, with a live phone-sized preview. Images come from the tenant's media gallery (FR-ASSET-005).

### FR-PUBLIC-004 — Booking Entry Point

The public page shall provide a clear entry point into the customer booking flow.

### FR-PUBLIC-005 — Platform Landing Page and Pricing (Accepted 2026-11-04, D-022)

BookQu's own landing page presents the product and the subscription plans with their prices, and links to registration. Implemented in commit `fbe1539`.

### FR-PUBLIC-006 — Booking Link Sharing (Accepted 2026-11-04, D-010)

Every tenant can copy, open and share its booking link (including a WhatsApp share), download a QR code that encodes the current link, print a poster with the QR code and business name, and copy ready-made text for an Instagram bio and a WhatsApp message. All plans.

### FR-PUBLIC-007 — Landing Page (Accepted 2026-11-04, D-009, D-017)

A Pro tenant can publish a landing page built from a template (Modern Business, Creative Studio, Minimalist Clinic), with selected sections, a headline and a banner from the media gallery, after a preview. Below Pro the feature is refused on the server.

### FR-PUBLIC-008 — Ended Subscription Storefront (Accepted 2026-11-04, D-002)

When the tenant's trial or subscription has ended, the public page stays visible but accepts no new booking and shows: "Bisnis ini belum menerima booking online saat ini." Paid bookings keep their customer access.

## Service Requirements

### FR-SERVICE-001 — Create Service

The owner shall be able to create a service.

A service shall contain information sufficient to make it bookable.

### FR-SERVICE-002 — Service Information

A service may contain:

* name;
* description;
* price;
* duration;
* capacity;
* status;
* image;
* category association where applicable.

Amended 2026-11-04 (D-012, D-014, D-015): a service also has a price unit and a duration unit shown to customers, a capacity per slot (default 1, FR-BOOKING-021), a "popular" flag shown on the public page, the units it uses (Pro, FR-UNIT-002) and linked add-ons (FR-ADDON-004). Changing price or duration affects only new bookings (FR-BOOKING-023).

### FR-SERVICE-003 — Update Service

The owner shall be able to update an existing service.

### FR-SERVICE-004 — Service Activation

The owner shall be able to activate or deactivate a service.

### FR-SERVICE-005 — Service Deactivation

A deactivated service shall not become a new publicly bookable service.

Existing historical booking records must remain valid.

### FR-SERVICE-006 — Service Deletion Protection

The system shall prevent destructive service deletion when doing so would violate existing booking or data-integrity constraints.

### FR-SERVICE-007 — Service Ownership

A service must belong to exactly one tenant.

## Category Requirements

### FR-CATEGORY-001 — Create Category

The owner shall be able to create a service category.

### FR-CATEGORY-002 — Update Category

The owner shall be able to update a category.

### FR-CATEGORY-003 — Delete Category

The owner shall be able to delete a category where doing so does not violate relevant data constraints.

### FR-CATEGORY-004 — Category Status

The system shall support the relevant category active/inactive state where applicable.

### FR-CATEGORY-005 — Service Association

A service may be associated with an appropriate category.

Category management shall not change the fundamental identity of the service.

### FR-CATEGORY-006 — Category Presentation (Accepted 2026-11-04, D-007 Revisi 1)

A category has a colour label, an optional image and a display order that the public page respects.

## Schedule Requirements

### FR-SCHEDULE-001 — Create Schedule

The owner shall be able to create available schedules for a service.

A schedule shall identify at minimum:

* service;
* date;
* start time;
* end time;
* availability state.

### FR-SCHEDULE-002 — Bulk Schedule Creation

The owner shall be able to generate multiple schedules through a supported bulk operation.

### FR-SCHEDULE-003 — Schedule Pricing

The system shall support the accepted schedule pricing model.

Schedule-specific pricing may override a service's default price where configured.

### FR-SCHEDULE-004 — Availability Configuration

The owner shall be able to configure schedule availability.

### FR-SCHEDULE-005 — Blocked Dates

The owner shall be able to define dates that should not be available for normal booking.

### FR-SCHEDULE-006 — Delete Schedule

The owner shall be able to remove an eligible schedule where doing so does not violate existing reservation constraints.

### FR-SCHEDULE-007 — Schedule Conflict Prevention

The system shall prevent invalid overlapping or conflicting schedules where such conflicts would make the booking model ambiguous.

### FR-SCHEDULE-008 — Tenant Ownership

A schedule must belong to the correct tenant and service.

A tenant must not manipulate another tenant's schedules.

### FR-SCHEDULE-009 — Booking Availability

The system shall determine whether a schedule is currently eligible for booking.

Availability shall take relevant booking state and business rules into account.

### FR-SCHEDULE-010 — Past Schedule Protection

Schedules that are no longer valid for a new booking must not be presented as normally selectable customer booking slots.

### FR-SCHEDULE-011 — Opening Hours (Accepted 2026-11-04, D-007 Revisi 1)

The owner can set weekly opening hours per day with breaks, and how many days ahead slots may be opened. Opening hours are the default range for bulk slot creation and are shown on the public page. They do not open slots by themselves (automatic slot opening is out of scope, D-021).

### FR-SCHEDULE-012 — Price Rules (Accepted 2026-11-04, D-007 Revisi 1)

The price of a slot is the service's base price, changed by the tenant's price rules and an optional per-slot override:

* weekend rule: `none`, `multiplier` or `fixed`, with a value;
* peak-hour rules: days, start and end time, extra amount, services;
* special-date prices: date, price, services.

A price simulator shows the resulting price per hour for a service and date. "Apply base price" sets the base price on existing future free slots of a service only.

### FR-SCHEDULE-013 — Closed Date Conflicts (Accepted 2026-11-04, D-007 Revisi 1)

Adding a closed date that has active bookings lists those bookings and is refused until each is rescheduled or cancelled.

### FR-SCHEDULE-014 — Bulk Creation Preview (Accepted 2026-11-04, D-007 Revisi 1)

Bulk creation (one date or a capped date range, start and end time, interval of 5 to 480 minutes defaulting to the service duration) shows how many slots will be created and how many already exist before saving, and never creates a duplicate slot.

## Customer Booking Requirements

### FR-BOOKING-001 — Service Selection

A customer shall be able to select an eligible service from the tenant's public booking page.

### FR-BOOKING-002 — Date Selection

A customer shall be able to select an eligible booking date on the combined Jadwal page (step 2 of Layanan → Jadwal → Data & Pembayaran). Changing the date loads its time slots without reloading the page; full or owner-closed dates cannot be selected.

### FR-BOOKING-003 — Time Selection

A customer shall be able to select one or more available, contiguous time slots on the same Jadwal page. Times are displayed in 24-hour WIB format. Date and slot selections are validated server-side before continuing to customer details and payment; legacy date/time GET URLs redirect to Jadwal.

### FR-BOOKING-004 — Customer Information

The customer shall provide the information required to create the booking.

This may include:

* name;
* phone number;
* email;
* booking notes where applicable.

### FR-BOOKING-005 — Booking Creation

The system shall create a structured booking record after the applicable booking process is completed.

### FR-BOOKING-006 — Booking Code

Each booking shall have a unique booking identifier or booking code suitable for customer-facing management.

### FR-BOOKING-007 — Booking Status

The system shall maintain the booking lifecycle.

The current booking status model is:

```text
pending
paid
cancelled
completed
no_show
```

`no_show` is added on 2026-11-04 by D-013 (FR-BOOKING-020). Future status additions require an explicit requirement change. There is no manual confirmation status: a booking is confirmed when its payment succeeds (D-020).

### FR-BOOKING-008 — Booking Detail

The owner shall be able to view booking details.

Booking details shall provide relevant customer, service, schedule, payment, and operational information.

### FR-BOOKING-009 — Booking Status Management

The owner shall be able to perform supported booking status transitions.

Invalid state transitions shall be rejected.

### FR-BOOKING-010 — Walk-In Booking

The owner shall be able to create a booking on behalf of a customer who books directly through the business.

Walk-in booking remains part of the same booking domain as online customer booking.

Amended 2026-11-04 (D-011, D-014, D-015): any role of the tenant may record a walk-in. The walk-in records the customer's name, phone, optional email and note, the payment method (`cash`, `transfer`, `manual` or `qris`), the number of participants and, from Medium, add-ons.

### FR-BOOKING-011 — Owner Reschedule

The owner shall be able to reschedule an eligible booking to another available schedule.

Amended 2026-11-04 (D-006): the owner may move a booking to any available slot of the same service. The price difference between the old and the new slot is shown before confirming and recorded in the booking history; no money is charged or returned automatically.

### FR-BOOKING-012 — Customer Reschedule

The customer shall be able to reschedule an eligible booking through the secure booking management mechanism.

Eligibility is determined by the booking policy and current state.

Amended 2026-11-04 (D-006): a customer may move only to a slot with the same or a lower price, before the reschedule deadline (FR-TENANT-008). A slot with a higher price is not offered; the customer sees "Hubungi pemilik". Moving to a cheaper slot returns no difference.

### FR-BOOKING-013 — Customer Cancellation

The customer shall be able to cancel an eligible booking through the secure booking management mechanism.

Amended 2026-11-04 (D-001): a booking that has been paid cannot be cancelled directly by the customer. When the tenant has refunds turned on, the customer submits a refund request (FR-REFUND-002). When refunds are off, the customer sees only "Hubungi pemilik".

### FR-BOOKING-014 — Owner Cancellation

The owner shall be able to cancel an eligible booking through the supported operational flow.

Amended 2026-11-04 (D-001, D-011): a reason is required. Cancelling a booking paid online always creates a full refund (FR-REFUND-006). A Kasir cannot cancel; the Kasir submits a cancellation request that a Pemilik or Manajer decides (FR-TEAM-006).

### FR-BOOKING-015 — Booking Security

A customer shall not be able to manage another customer's booking merely by changing a public booking identifier.

### FR-BOOKING-016 — Booking Management Authorization

Customer booking management shall require an appropriate valid authorization mechanism.

### FR-BOOKING-017 — Booking Availability Protection

A booking shall not be created against a schedule that is no longer available.

### FR-BOOKING-018 — Double Booking Protection

The system shall prevent two incompatible booking operations from successfully occupying the same exclusive schedule.

### FR-BOOKING-019 — Tenant Validation

A booking must be associated with the correct tenant, service, and schedule.

Cross-tenant references must be rejected.

### FR-BOOKING-020 — No-show (Accepted 2026-11-04, D-013)

A Pemilik, Manajer or Kasir can mark a `paid` booking whose session has ended as `no_show`. Nothing is refunded automatically, and the booking still counts as revenue. Reports show the no-show rate.

### FR-BOOKING-021 — Capacity and Participants (Accepted 2026-11-04, D-014)

Each service has a capacity per slot (default 1). A booking states its number of participants (default 1). A slot stays bookable until the participants of its active bookings reach the capacity; a booking is refused when its participants exceed the remaining capacity. The price is per participant. All plans.

### FR-BOOKING-022 — Booking Window Rules (Accepted 2026-11-04, D-020)

The tenant can set a minimum number of hours before a session for a new booking, a maximum number of days ahead, and a buffer between bookings. Slots outside these rules are not offered and are refused on the server. The customer sees a summary of the rules before paying. A booking is confirmed when its payment succeeds; there is no manual approval by the owner.

### FR-BOOKING-023 — Frozen Booking Amount (Accepted 2026-11-04, D-015)

The amount of a booking is fixed when it is created: base price per participant, price rules, add-ons and promo discount. Later changes to prices, add-ons or promos never change an existing booking, and every owner list, panel, invoice and report shows the amount that was paid.

## Multi-Slot Booking Requirements

### FR-MULTIBOOK-001 — Multi-Slot Selection

The system shall support eligible reservations consisting of multiple compatible schedule slots.

### FR-MULTIBOOK-002 — Slot Compatibility

Selected slots within one multi-slot reservation must satisfy the applicable compatibility rules.

Where the booking policy requires contiguous slots, non-contiguous slots shall be rejected.

### FR-MULTIBOOK-003 — Unified Payment

Eligible multi-slot bookings shall use one customer payment transaction when the booking flow treats them as one reservation payment group.

### FR-MULTIBOOK-004 — Unified Invoice

The customer-facing invoice shall represent all schedules belonging to the same payment group.

### FR-MULTIBOOK-005 — Multi-Slot Cancellation

The system shall apply the applicable cancellation policy consistently to all schedules belonging to the reservation.

### FR-MULTIBOOK-006 — Multi-Slot Reschedule

The system shall apply the applicable rescheduling policy to a multi-slot reservation.

Unsupported operations must be rejected rather than partially applied.

## Customer Management Requirements

### FR-CUSTOMER-001 — Customer Record

The system shall maintain customer information associated with bookings.

### FR-CUSTOMER-002 — Customer Directory

The owner shall be able to view customers belonging to the owner's tenant.

### FR-CUSTOMER-003 — Booking History

The owner shall be able to access relevant customer booking history.

### FR-CUSTOMER-004 — Customer Notes

Where supported, the owner shall be able to maintain internal notes associated with a customer.

Customer notes must remain tenant-isolated.

### FR-CUSTOMER-005 — Customer Privacy

Customer information shall only be accessible to authorized users and permitted customer-facing flows.

## Payment Requirements

### FR-PAYMENT-001 — Booking Payment

The system shall support payment for eligible customer bookings.

### FR-PAYMENT-002 — Payment Provider

The system supports a Unified Payment Gateway architecture with pluggable drivers:
- **SingaPay**: Supported online payment gateway driver (active default via `PAYMENT_GATEWAY_DRIVER=singapay`) supporting payment links, QRIS, Virtual Accounts, and E-Wallets.
- **Midtrans**: Supported online payment gateway driver (switchable via `PAYMENT_GATEWAY_DRIVER=midtrans`) supporting Snap tokens/popups, QRIS, Virtual Accounts, and E-Wallets.

Switching between providers is governed centrally by configuration (`config/payment.php` and `PAYMENT_GATEWAY_DRIVER`) across both customer booking checkout and owner subscription checkout. Existing transactions maintain historical provider isolation.

All customer payments go through BookQu's platform credentials (platform escrow, ADR-0001). A tenant cannot connect its own payment gateway: the option and every stored tenant gateway credential are removed, not hidden (D-016, 2026-11-04).

### FR-PAYMENT-003 — Payment Status

The system shall maintain payment state independently from booking state.

The current payment status model is:

```text
pending
sukses
gagal
```

Payment expiration is a business condition and shall not be represented as an additional payment status unless explicitly introduced by a future requirement.

### FR-PAYMENT-004 — Payment Reference

Each external payment transaction shall maintain the relevant external reference required for reconciliation.

### FR-PAYMENT-005 — Payment Verification

The system shall not mark a payment as successful solely because a client-side request claims that payment succeeded.

Payment success shall rely on a trusted verification mechanism.

### FR-PAYMENT-006 — Payment Webhook

The system shall support payment-provider callback/webhook processing where required.

### FR-PAYMENT-007 — Webhook Idempotency

Repeated delivery of the same payment-provider event shall not create duplicate financial side effects.

### FR-PAYMENT-008 — Payment Expiration

An expired payment shall be handled as a failed payment outcome for payment state purposes.

Expiration may additionally affect the associated booking and schedule according to the booking expiration rules.

### FR-PAYMENT-009 — Booking Synchronization

Where payment determines booking eligibility, the system shall synchronize the booking state according to the defined booking and payment rules.

### FR-PAYMENT-010 — Failed Payment Protection

A failed or expired payment shall not leave a booking in an incorrectly confirmed payment state.

### FR-PAYMENT-011 — Refund Record

Where a customer cancellation or other supported operation requires a refund, the system shall maintain a refund record.

Supported refund states are:

```text
pending
processed
failed
```

Refund state is separate from both booking state and payment state.

Amended 2026-11-04 (D-001): a refund record holds the amount to return, which is the full paid amount or the share decided by the owner (FR-REFUND-004). It is created by an owner decision or an owner cancellation, and processed by the platform admin (FR-REFUND-007).

## Balance and Payout Requirements

### FR-PAYOUT-001 — Platform Balance (Accepted 2026-11-01)

Under the platform escrow model, each tenant has a platform balance. When a booking payment succeeds through the provider, the full amount is credited exactly once (fee 0% for v1). Owner-settled, free and walk-in payments credit nothing. A refund owed on a credited payment debits the balance (BQ-015).

### FR-PAYOUT-002 — Payout Request (Accepted 2026-11-01)

An owner can request a payout to one of the tenant's saved payout accounts. The amount is a whole number of rupiah, at least `payouts.min_amount` (Rp 10.000) and at most the available balance and any configured per-request cap. The request and the balance hold happen atomically, and a repeated request key creates no second payout.

Amended 2026-11-04 (D-004), matching BQ-123 to BQ-125:
* a tenant saves up to `payouts.max_accounts` (3) payout accounts, one of them primary; adding, changing and deleting an account require the current password and send an email to the owner; an account used by a payout in progress cannot be deleted;
* a payout is refused unless the owner's email is verified, the business profile is complete, the tenant uses the platform payment mode, the balance is positive, open payouts are below `payouts.max_open` and a payout account exists; an optional daily request cap applies;
* the payout keeps a snapshot of the destination account.

### FR-PAYOUT-003 — Payout Processing (Accepted 2026-11-01)

A platform admin reviews payouts: `pending → approved → success | failed`, or `pending → rejected`. Reject and fail require a reason and return the held amount to the balance. Success requires a transfer reference or a proof file; the proof file can be downloaded only by the owning tenant. The owner is notified of every change, by email for success, failure and rejection. The owner sees the states as Pending (pending, approved), Berhasil (success) and Gagal (failed, rejected). Platform admins are notified of every new request. Amended 2026-11-04 (D-004, BQ-125).

## Refund Requirements

### FR-REFUND-001 — Refund Policy (Accepted 2026-11-04, D-001)

Refunds are optional per tenant and off by default. The tenant sets in its booking policy whether customers may request refunds, the cancellation and refund-request deadline in hours before the session (default 24) and the reschedule deadline in hours (FR-TENANT-008).

### FR-REFUND-002 — Customer Refund Request (Accepted 2026-11-04, D-001)

When refunds are on, a customer can request a refund for a paid booking before the deadline, giving a reason and the bank account to receive the money. One open request per booking.

### FR-REFUND-003 — Slot Held During a Request (Accepted 2026-11-04, D-001)

While a request is open, the booking stays valid and keeps its slot.

### FR-REFUND-004 — Owner Decision (Accepted 2026-11-04, D-001)

A Pemilik or Manajer decides an open request: accept 100%, accept 50% (half of the paid amount, rounded down to whole rupiah), or reject with a reason. Accepting cancels the booking and frees the slot; rejecting keeps the booking. The customer is notified of the decision.

### FR-REFUND-005 — Undecided Requests (Accepted 2026-11-04, D-001)

If a request is still open when the session starts, the system decides nothing about the money and keeps reminding the owner.

### FR-REFUND-006 — Owner Cancellation Refund (Accepted 2026-11-04, D-001)

When the business cancels a booking that was paid online, the customer always receives a full refund, and the owner must give a reason.

### FR-REFUND-007 — Refund Processing (Accepted 2026-11-04, D-001)

The platform admin transfers the refund to the customer and records the result. The refunded amount is debited from the tenant's platform balance (FR-PAYOUT-001). Refund processing must be idempotent: one decision creates one refund.

## Booking Management Without Account

### FR-MANAGE-001 — Secure Booking Access

A customer shall be able to access eligible booking management functionality without requiring a normal BookQu account.

### FR-MANAGE-002 — Secure Access Grant

The system shall authorize customer booking management through secure access grants delivered as access links (ADR-0006). Legacy `?token=` links never authorize access.

### FR-MANAGE-003 — Booking Details

The customer shall be able to view eligible booking details through an authorized management link.

### FR-MANAGE-004 — Payment Information

The customer shall be able to access applicable payment and invoice information through valid authorization.

### FR-MANAGE-005 — Cancellation Authorization

Customer cancellation shall require the valid cancellation authorization associated with the booking.

### FR-MANAGE-006 — Reschedule Authorization

Customer rescheduling shall require the valid rescheduling authorization associated with the booking.

### FR-MANAGE-007 — Review Authorization

Review submission shall require valid authorization for the eligible booking.

### FR-MANAGE-008 — Public Identifiers Are Not Credentials

`order_id`, `booking_code` and similar public identifiers shall not authorize viewing or changing a booking or payment. Every private customer endpoint requires a valid access credential or a server-side session established from one. A checkout-session booking selection alone is not authorization. Accepted in ADR-0006; implementation tracked in BQ-001/BQ-002.

### FR-MANAGE-009 — Explicit Access Grants

Customer access shall be bound to an exact tenant, booking or payment group and an explicitly granted set of operations. Management access deliberately includes its named customer operations; Checkout, Invoice and operation-specific access shall not acquire wider rights through navigation, rendered content or callbacks. Authorization never bypasses current booking/payment eligibility or multi-slot restrictions. The profile matrix is defined in ADR-0006, not inferred from legacy token fields.

### FR-MANAGE-010 — Access Expiry, Revocation and Renewal

Customer credentials shall have finite, recorded expiry and revocation enforced on requests, sessions and business mutations. A recognized expired credential may request eligible same-profile renewal only to its recorded recipient, without granting data access or returning a new secret. Lost/revoked-link assistance requires the tenant-authenticated operation. Security replacement invalidates the reservation family's existing access and stale queued issuance. Durations and recovery limits are defined in ADR-0006.

### FR-MANAGE-011 — Safe Customer Links and Delivery

Private customer links shall use trusted destinations and expose only the authority intended for their audience. Invoice, public sharing, calendars, provider redirects, logs and routine serialized data shall not disclose management credentials. Delivery shall use the recorded recipient and trusted issuance context; requests cannot choose a replacement recipient. Canonical/private and custom-domain/storefront behavior is defined in ADR-0006.

## Additional Item Requirements

### FR-ADDON-001 — Create Additional Item

The owner shall be able to create an additional item.

### FR-ADDON-002 — Update Additional Item

The owner shall be able to update an additional item.

### FR-ADDON-003 — Activate or Deactivate Additional Item

The owner shall be able to control whether an additional item is currently offered.

### FR-ADDON-004 — Service Association

An additional item may be associated with eligible services.

### FR-ADDON-005 — Booking Integration

Where enabled, a customer shall be able to select eligible additional items during checkout.

Amended 2026-11-04 (D-015): add-ons are a Medium and Pro feature. The customer chooses add-ons in the Data & Pembayaran step; each chosen add-on is a line item of the payment, the invoice and the frozen booking amount (FR-BOOKING-023). An add-on has a price, a stock or "unlimited", and linked services. Stock decreases only when the booking is paid; an add-on with no stock left cannot be chosen.

## Voucher Requirements

### FR-VOUCHER-001 — Create Voucher

The owner shall be able to create promotional vouchers.

### FR-VOUCHER-002 — Voucher Rule Configuration

The owner shall be able to configure supported voucher rules such as:

* code;
* discount type;
* discount value;
* active period;
* minimum transaction;
* usage limitation.

### FR-VOUCHER-003 — Voucher Validation

The system shall validate voucher eligibility before applying the discount.

### FR-VOUCHER-004 — Voucher Tenant Isolation

A voucher belonging to one tenant shall not be usable by another tenant.

### FR-VOUCHER-005 — Voucher Calculation

The system shall calculate the resulting booking price according to the accepted voucher rules.

Amended 2026-11-04 (D-015): vouchers are offered to owners as **Promo**, for Medium and Pro. A promo has a code, a percentage or fixed discount, a minimum order, a maximum discount, a quota, a period and the services it applies to. The discount is computed on the server before payment and is part of the payment amount. The quota is never exceeded, also when customers pay at the same time. The owner sees how often each promo was used, the total discount and the revenue from bookings with a promo.

## Staff and Resource Requirements

### FR-STAFF-001 — Staff Management (Withdrawn 2026-11-04, D-011)

Replaced by FR-TEAM. "Staff" now means team members who sign in, not service providers.

### FR-RESOURCE-001 — Resource Management (Withdrawn 2026-11-04, D-012)

Replaced by FR-UNIT-001.

### FR-STAFFRESOURCE-001 — Association (Withdrawn 2026-11-04, D-011, D-012)

Replaced by FR-UNIT-002 for units. Services are no longer linked to staff.

### FR-STAFFRESOURCE-002 — Operational Constraint (Withdrawn 2026-11-04, D-012)

Replaced by FR-UNIT-003.

### FR-STAFFRESOURCE-003 — Customer Selection Boundary (Withdrawn 2026-11-04, D-011)

Customers never choose a staff member or a unit while booking.

## Team Requirements

### FR-TEAM-001 — Team Members (Accepted 2026-11-04, D-011)

The Pemilik can invite a person by email with a role (Manajer or Kasir), change the role, and revoke access at any time. A revoked member loses access at their next request. The number of team accounts is limited by plan (FR-SUB-010, `docs/product.md` §7).

### FR-TEAM-002 — Role Permissions (Accepted 2026-11-04, D-011)

| Capability | Pemilik | Manajer | Kasir |
|---|---|---|---|
| Today's bookings, calendar, bookings, customers | Yes | Yes | Yes, without revenue figures and spending totals |
| Walk-in, mark complete, mark no-show, reschedule | Yes | Yes | Yes |
| Cancel a booking, decide a refund request | Yes | Yes | Request cancellation only (FR-TEAM-006) |
| Services, units, opening hours and prices | Yes | Yes | No |
| Booking page, promos, reviews, reports | Yes | Yes | No |
| Business profile, booking policy, notifications, integrations | Yes | Yes | No |
| Payments, balance, payouts, payout accounts | Yes | No | No |
| Plan and billing, team management, delete the business account | Yes | No | No |
| Own account (name, email, phone, password) | Yes | Yes | Yes |

Every permission is enforced on the server; hiding a menu is not authorization.

### FR-TEAM-003 — Tenant Scope (Accepted 2026-11-04, D-011)

A team member belongs to exactly one tenant and can never reach another tenant's data (INV-TEN-01).

### FR-TEAM-004 — Activity Attribution (Accepted 2026-11-04, D-011)

Every create, change and state action records the acting user and role. The booking history shows it, and the Pemilik can read the activity log of all members.

### FR-TEAM-005 — Notifications by Role (Accepted 2026-11-04, D-011)

A team member receives only notifications their role may act on; payout and plan notifications go to the Pemilik only.

### FR-TEAM-006 — Cancellation Request (Accepted 2026-11-04, D-011)

A Kasir can request the cancellation of a booking with a reason. A Pemilik or Manajer approves (the booking is cancelled under FR-BOOKING-014) or rejects it.

## Unit Requirements

### FR-UNIT-001 — Unit Management (Accepted 2026-11-04, D-012)

A Pro tenant can create, edit, activate, deactivate and delete units (court, room or tool) with a name, type, capacity, location and image. A unit with future bookings cannot be deleted.

### FR-UNIT-002 — Shared Use (Accepted 2026-11-04, D-012)

A unit can be used by several services, and a service can use several units.

### FR-UNIT-003 — No Double Booking on a Unit (Accepted 2026-11-04, D-012)

Two active bookings that use the same unit cannot overlap in time, even for different services. The rule applies to customer checkout, walk-ins and reschedules, and is enforced in the same authoritative booking operation as slot occupancy (BR-CORE-007).

### FR-UNIT-004 — Existing Conflicts (Accepted 2026-11-04, D-012)

The Unit page lists existing bookings that already overlap on a unit, so the owner can resolve them.

## Review Requirements

### FR-REVIEW-001 — Review Eligibility

Only eligible bookings may generate a customer review.

### FR-REVIEW-002 — Review Submission

The customer shall be able to submit a review for an eligible booking.

### FR-REVIEW-003 — Review Rating

The system shall support a rating value within the accepted rating range.

### FR-REVIEW-004 — Review Comment

The customer may provide a textual comment where supported.

### FR-REVIEW-005 — Review Ownership

A review must remain associated with the correct tenant and booking.

### FR-REVIEW-006 — Review Management

The owner shall be able to manage supported review visibility or response behavior.

### FR-REVIEW-007 — One Review Per Eligible Booking

The system should prevent duplicate review creation for the same eligible booking unless a future requirement explicitly permits multiple reviews.

## Dashboard Requirements

### FR-DASH-001 — Business Overview

The owner shall be able to view a business overview dashboard.

### FR-DASH-002 — Booking Metrics

The dashboard shall present relevant booking metrics.

### FR-DASH-003 — Revenue Metrics

The dashboard shall present relevant revenue or payment-related metrics based on recorded data.

### FR-DASH-004 — Customer Metrics

The dashboard shall present relevant customer activity metrics.

### FR-DASH-005 — Service Metrics

The dashboard may present service-related performance information.

### FR-DASH-006 — Recent Activity

The dashboard shall provide access to recent or upcoming operational activity where applicable.

### FR-DASH-007 — Dashboard Integrity

Dashboard figures shall derive from authoritative operational data.

Dashboard aggregation must not become an independent source of truth.

### FR-DASH-008 — Today and Needs Action (Accepted 2026-11-04, D-007 Revisi 1)

The dashboard (Beranda) shows today only: one status banner by priority, today's figures, items needing action (refund and cancellation requests, ended bookings without an outcome, low-rated reviews without a reply, payout changes), today's schedule, a summary of tomorrow, and a new-owner guide until its steps are done. Trends and charts belong to reports (FR-ANALYTICS).

## Calendar Requirements

### FR-CALENDAR-001 — Calendar View

The owner shall be able to view booking and schedule information through a calendar interface.

### FR-CALENDAR-002 — Date Navigation

The calendar shall support navigation across relevant dates.

### FR-CALENDAR-003 — Booking Visibility

The calendar shall show relevant booking information.

### FR-CALENDAR-004 — Schedule Visibility

The calendar shall show relevant schedule availability.

### FR-CALENDAR-005 — Calendar Filtering

Where supported, the calendar shall allow filtering by relevant operational dimensions.

Amended 2026-11-04 (D-012, D-014): filters are service, unit (Pro) and booking status, and each slot shows participants against capacity when the capacity is above 1.

### FR-CALENDAR-006 — Walk-In Operation

The owner shall be able to initiate supported walk-in booking or operational actions from the calendar where provided by the current product flow.

## Analytics Requirements

### FR-ANALYTICS-001 — Operational Analytics

The system shall provide owner-facing operational analytics based on booking and business activity data.

### FR-ANALYTICS-002 — Booking Analysis

The system shall provide relevant booking metrics.

### FR-ANALYTICS-003 — Revenue Analysis

The system shall provide relevant revenue-related metrics where payment data is available.

### FR-ANALYTICS-004 — Service Analysis

The system may provide service performance metrics.

### FR-ANALYTICS-005 — Schedule Utilization

The system shall support schedule utilization analysis where sufficient data is available.

### FR-ANALYTICS-006 — Analytics Data Integrity

Analytics must derive from the same authoritative operational records used by the booking system.

### FR-ANALYTICS-007 — Reports (Accepted 2026-11-04, D-013, D-017)

Reports are a Medium and Pro feature; Small sees a blurred preview with the upgrade prompt. One period filter applies to every tab: summary, revenue (online and walk-in, per service, refunds, net), occupancy (rate, busy and quiet hours, per unit for Pro), services, customers (new and returning, promo use). Revenue comes from payments; a multi-slot booking counts once; the no-show rate is shown.

## Reporting Requirements

### FR-REPORT-001 — Schedule Report

The owner shall be able to access reports concerning schedule and booking performance.

### FR-REPORT-002 — Report Filtering

Where supported, the owner shall be able to filter reports by relevant time periods or operational dimensions.

### FR-REPORT-003 — Report Export

The system shall support export of supported report data.

Amended 2026-11-04 (D-007 Revisi 1, D-017): the schedule report becomes the occupancy tab of the reports (FR-ANALYTICS-007). Exports exist per report tab and for the filtered booking list, are refused below Medium on the server, and neutralise cells that start with `=`, `+`, `-` or `@`.

## Appearance Requirements

### FR-APPEARANCE-001 — Logo

The owner shall be able to configure the business logo where supported.

### FR-APPEARANCE-002 — Brand Color

The owner shall be able to configure supported brand colors.

### FR-APPEARANCE-003 — Banner / Cover

The owner shall be able to configure supported cover or banner imagery.

### FR-APPEARANCE-004 — Public Presentation

Appearance configuration shall affect public presentation without modifying core booking behavior.

Amended 2026-11-04 (D-009): appearance is available to every plan (FR-PUBLIC-003).

## Asset Requirements

### FR-ASSET-001 — Asset Storage

The system shall provide a mechanism for storing supported business media assets.

### FR-ASSET-002 — Asset Ownership

Assets shall belong to the correct tenant.

### FR-ASSET-003 — Asset Management

Authorized owners shall be able to add and remove supported assets.

### FR-ASSET-004 — Asset Usage

Eligible assets may be used by the tenant's public-facing pages.

### FR-ASSET-005 — Media Gallery (Accepted 2026-11-04, D-009)

Every image of a tenant (logo, cover, service, unit, landing page) lives in one media gallery with a title and a category (`logo`, `cover`, `service`, `gallery`, `other`). Every image field picks from or uploads into the gallery. Deleting an image that is in use asks for confirmation and clears the reference.

## Notification Requirements

### FR-NOTIFICATION-001 — Booking Notification

The system shall support owner notification for relevant booking events.

### FR-NOTIFICATION-002 — Booking Status Notification

The system shall support notifications for relevant booking state changes.

### FR-NOTIFICATION-003 — Payment Notification

The system may notify the relevant actor about important payment events.

### FR-NOTIFICATION-004 — Subscription Notification

The system shall support relevant subscription lifecycle notifications where applicable.

### FR-NOTIFICATION-005 — Notification Isolation

Owner notifications must only represent events belonging to the owner's tenant or authorized account.

### FR-NOTIFICATION-006 — Notification Preferences (Accepted 2026-11-04, D-007 Revisi 1, D-021)

Each user chooses per event (new booking, payment success, cancellation, reschedule, refund request, new review, payout status, plan status) whether to receive email; in-app notifications are always on. Preferences are stored on the server. WhatsApp is not a channel in this release (D-021).

### FR-NOTIFICATION-007 — Subscription Reminders (Accepted 2026-11-04, D-002)

The Pemilik is reminded 3 days and 1 day before the trial or subscription ends, and on the day it ends, in-app and by email.

## Subscription Requirements

### FR-SUB-001 — Subscription Plans

The platform shall support subscription plan definitions.

### FR-SUB-002 — Tenant Subscription

A tenant shall have a subscription state associated with a plan.

### FR-SUB-003 — Trial

The platform may provide a trial period for eligible new tenants according to the accepted subscription policy.

Amended 2026-11-04 (D-002): one owner account receives at most one trial.

### FR-SUB-004 — Subscription Status

The system shall maintain subscription status.

Current conceptual subscription states include:

```text
trial
active
expired
cancelled
```

### FR-SUB-005 — Subscription Access Control

The platform shall be able to restrict access to features based on subscription entitlement.

### FR-SUB-006 — Feature Entitlement

Feature access shall be determined using the tenant's current subscription state and plan configuration.

### FR-SUB-007 — Subscription Payment

The owner shall be able to make supported payments for the BookQu subscription.

### FR-SUB-008 — Payment Callback

The system shall process trusted subscription payment status updates.

### FR-SUB-009 — Subscription Lifecycle

The system shall handle supported subscription lifecycle transitions.

### FR-SUB-010 — Usage Limits

Where a plan includes usage limits, the system shall prevent the tenant from exceeding the accepted limit.

Amended 2026-11-04 (D-017): the limits are services, bookings per month and team accounts, with the values in `docs/product.md` §7.

### FR-SUB-011 — Usage Tracking

The system shall maintain the information necessary to evaluate supported usage limits.

### FR-SUB-012 — Ended Subscription (Accepted 2026-11-04, D-002)

When the trial or subscription has ended, the owner and team can still sign in, manage existing bookings (complete, reschedule, cancel, refund), see payments and balance, and buy a plan. Creating services, slots, units, promos, team members and walk-ins is refused on the server. There is no grace period. Paid bookings are served as usual. The public page follows FR-PUBLIC-008.

### FR-SUB-013 — Renewal and Plan Change (Accepted 2026-11-04, D-002)

Buying a plan during the trial starts the paid period that day and ends the trial. Renewing the same plan extends from the current end date, so no paid day is lost. Changing to another plan starts on the payment date.

### FR-SUB-014 — Plan Contents (Accepted 2026-11-04, D-017)

What each plan contains is defined in `docs/product.md` §7. The plan comparison shown to owners and on the platform landing page lists only features that exist for that plan.

## Platform Administration Requirements

### FR-ADMIN-001 — Admin Authentication

The platform admin shall authenticate using an authorized admin account.

### FR-ADMIN-002 — Admin Dashboard

The system shall provide a platform-level dashboard for supported administration metrics.

### FR-ADMIN-003 — Tenant Oversight

The admin may access permitted platform-level tenant information.

The admin view must respect the platform security model.

### FR-ADMIN-004 — Platform Boundary

Platform administration must remain separate from ordinary owner permissions.

### FR-ADMIN-005 — Refund Processing (Accepted 2026-11-04, D-001)

The platform admin sees refunds to transfer (amount, customer bank account, booking, tenant), records each as processed with a transfer reference or as failed with a reason, and the customer and the owner are notified. The admin dashboard shows how many refunds are waiting.

## Integration and API Requirements

### FR-INTEGRATION-001 — Owner Integrations (Accepted 2026-11-04, D-019)

Every plan can connect Google Calendar (bookings are written to the owner's calendar), Google Analytics and Meta Pixel (on the public booking page). Each integration can be connected, tested and disconnected. Payment and email are shown as managed by BookQu and cannot be changed.

### FR-INTEGRATION-002 — Integration Secrets (Accepted 2026-11-04, D-019)

Integration credentials are stored encrypted, are never shown again after saving, and are never logged.

### FR-API-001 — API Keys (Accepted 2026-11-04, D-019, D-017)

A Pro tenant can create and revoke API keys that give access only to its own tenant's data.

### FR-API-002 — Webhooks (Accepted 2026-11-04, D-019, D-017)

A Pro tenant can register webhook URLs that receive signed booking and payment events, with retries and a delivery log.

## Owner Portal Shell Requirements

### FR-SHELL-001 — Navigation (Accepted 2026-11-04, D-007 Revisi 1)

The owner portal shows only the menus the user's role may open, marks plan-gated menus with the upgrade prompt, and on phones replaces the sidebar with a bottom bar (Beranda, Kalender, + Booking, Booking, Lainnya).

### FR-SHELL-002 — Global Actions (Accepted 2026-11-04, D-007 Revisi 1)

Every owner page offers the notification bell, a "+ Booking" walk-in action and a search by booking code, customer name or phone that opens the booking or customer panel.

### FR-HELP-001 — Help (Accepted 2026-11-04, D-007 Revisi 1)

The owner portal has a help page with frequently asked questions and BookQu's contact details, taken from configuration.

## Data Integrity Requirements

### FR-DATA-001 — Referential Integrity

The system shall maintain valid relationships between related records.

### FR-DATA-002 — Tenant Integrity

Tenant-owned records must reference the correct tenant.

### FR-DATA-003 — Booking Integrity

A booking must reference a valid tenant, service, and schedule combination.

### FR-DATA-004 — Payment Integrity

Payment records must remain associated with their intended business purpose and relevant tenant.

### FR-DATA-005 — Atomic Critical Operations

Critical multi-record operations shall be performed atomically where partial completion would create invalid business state.

### FR-DATA-006 — Double Booking Prevention

The system shall provide application and/or database-level protection preventing conflicting reservations from occupying the same exclusive schedule.

## Security Requirements

### NFR-SEC-001 — HTTPS

Production communication shall use HTTPS/TLS.

### NFR-SEC-002 — Password Hashing

Passwords shall be securely hashed.

### NFR-SEC-003 — SQL Injection Protection

The system shall protect database operations against SQL injection.

### NFR-SEC-004 — XSS Protection

The system shall provide appropriate protection against cross-site scripting.

### NFR-SEC-005 — CSRF Protection

State-changing authenticated web operations shall use CSRF protection unless explicitly exempted for a trusted external callback mechanism.

### NFR-SEC-006 — Authorization

Every protected operation shall enforce authorization at the appropriate layer.

### NFR-SEC-007 — Tenant Isolation

Tenant isolation shall be treated as a security boundary.

### NFR-SEC-008 — IDOR Protection

User-controlled identifiers shall not be sufficient to access unauthorized tenant or booking data.

### NFR-SEC-009 — Payment Callback Security

External payment callbacks shall be verified before trusted financial state is updated.

### NFR-SEC-010 — Secret Protection

API keys, payment credentials, and application secrets must not be committed into source control.

## Performance Requirements

The following are performance targets rather than automatically verified claims.

### NFR-PERF-001 — General Response Time

Normal operations should target a response time of approximately 2 seconds or less under expected operating conditions.

This requirement requires formal performance measurement before it can be marked verified.

### NFR-PERF-002 — Heavy Operations

Heavy operations such as booking and payment processing should target approximately 5 seconds or less where practical.

External payment-provider latency may be outside direct application control.

### NFR-PERF-003 — Availability Data Freshness

Availability information should be sufficiently fresh to prevent stale availability from producing an invalid booking.

### NFR-PERF-004 — Query Efficiency

The system should avoid unnecessary repeated queries, unbounded retrieval, and redundant computation in frequently accessed flows.

### NFR-PERF-005 — Caching

Caching may be used where it improves performance without compromising booking correctness or required data freshness.

## Scalability Requirements

### NFR-SCALE-001 — Tenant Growth

The architecture shall support increasing numbers of tenants without requiring tenant-specific application code.

### NFR-SCALE-002 — User Growth

The system shall support increasing numbers of owner and customer interactions.

### NFR-SCALE-003 — Booking Growth

The system shall support increasing booking volume without requiring redesign of the core booking domain.

### NFR-SCALE-004 — Horizontal Scaling Readiness

The application architecture should remain compatible with future horizontal scaling.

This is an architectural target and requires infrastructure validation before being considered verified.

## Reliability Requirements

### NFR-REL-001 — Transaction Consistency

Critical booking and payment operations shall not leave the system in a partially updated state.

### NFR-REL-002 — External Callback Resilience

External payment callbacks may be delivered more than once and must be handled safely.

### NFR-REL-003 — Error Recovery

Expected external-service and application failures should produce predictable recovery behavior.

### NFR-REL-004 — Backup

Production data should be backed up using an appropriate operational backup mechanism.

### NFR-REL-005 — Recovery

The production environment should have a documented recovery procedure.

Backup and recovery require operational verification.

## Availability Requirement

### NFR-AVAIL-001 — Service Availability

The production service should target high availability.

A target of approximately 99% uptime may be used as an operational baseline.

This target must be validated through actual monitoring.

## Usability Requirements

### NFR-UX-001 — Responsive Interface

The customer and owner interfaces shall support:

* desktop;
* tablet;
* mobile.

### NFR-UX-002 — Consistent Navigation

Navigation within each portal should be consistent.

### NFR-UX-003 — Clear Booking Flow

The customer booking process should remain understandable and predictable.

### NFR-UX-004 — Concise Booking Flow

The primary booking operation should remain reasonably concise.

The approximate five-step historical target should be treated as a usability target rather than a rigid technical constraint.

### NFR-UX-005 — Clear State

Users should be able to understand:

* what they selected;
* what is available;
* what has been booked;
* whether payment succeeded;
* what action they can take next.

## Compatibility Requirements

### NFR-COMP-001 — Browser Support

The system shall support current mainstream browsers including:

* Chrome;
* Firefox;
* Edge;
* Safari.

### NFR-COMP-002 — Responsive Layout

Critical functionality shall remain usable across supported screen sizes.

## Maintainability Requirements

### NFR-MAINT-001 — Separation of Responsibilities

Controllers, views, models, services, domain/application logic, and infrastructure shall have clear responsibilities.

Business logic shall not be unnecessarily concentrated in controllers.

### NFR-MAINT-002 — Modular Design

The system shall be organized so that new features can be added without unnecessary modification of unrelated modules.

### NFR-MAINT-003 — Reusable Components

Repeated UI and application behavior should be extracted into reusable components where appropriate.

### NFR-MAINT-004 — Consistent Naming

New code must follow naming conventions defined by `docs/architecture.md` and `docs/glossary.md`.

Historical naming inconsistencies may remain temporarily but should not be propagated into new work.

### NFR-MAINT-005 — Documentation

Important domain, system, and architectural decisions shall be documented in their appropriate authority.

### NFR-MAINT-006 — Technical Debt Visibility

Known technical debt shall be recorded explicitly rather than silently treated as intended behavior.

## Logging and Monitoring Requirements

### NFR-OBS-001 — Authentication Events

Important authentication events should be logged appropriately.

### NFR-OBS-002 — Booking Events

Important booking lifecycle events should be traceable.

### NFR-OBS-003 — Payment Events

Important payment state transitions should be traceable.

### NFR-OBS-004 — Application Errors

Production application errors should be observable through the configured logging or monitoring mechanism.

### NFR-OBS-005 — Operational Monitoring

Production performance and availability should be observable.

## Extensibility Requirements

### NFR-EXT-001 — Payment Provider Integration

Payment integration shall be isolated sufficiently to allow future provider changes or additional providers.

### NFR-EXT-002 — Notification Integration

Notification delivery should be modular enough to allow future channels.

### NFR-EXT-003 — Feature Expansion

Future modules should be addable without unnecessarily rewriting the booking domain.

### NFR-EXT-004 — Multi-Tenant Expansion

New tenant-level modules must respect the established tenant isolation model.

## Core Business Rules

The following business rules are fundamental to BookQu.

### BR-CORE-001 — Tenant Ownership

Every tenant-owned operational record must belong to a valid tenant.

### BR-CORE-002 — Service Ownership

A service belongs to one tenant.

### BR-CORE-003 — Schedule Ownership

A schedule belongs to one service and the service's tenant.

### BR-CORE-004 — Booking Ownership

A booking remains associated with the correct tenant throughout its lifecycle.

### BR-CORE-005 — Booking References

A booking must reference a valid service and eligible schedule belonging to the correct tenant.

### BR-CORE-006 — Availability Protection

Unavailable schedules cannot be booked.

### BR-CORE-007 — Double Booking Prevention

Two incompatible reservations cannot successfully occupy the same exclusive schedule.

### BR-CORE-008 — Payment and Booking Separation

Payment state and booking state are separate concepts.

### BR-CORE-009 — Customer Authorization

A customer may only manage bookings for which valid management authorization exists.

### BR-CORE-010 — Owner Authorization

An owner may only operate on the tenant they are authorized to manage.

### BR-CORE-011 — Historical Data Preservation

Deactivation or normal lifecycle transitions must not unnecessarily destroy historical booking or payment information required for traceability.

### BR-PAY-001 — Late Payment Success Reconciliation

When a provider reports success for a payment already marked `gagal` (expired or cancelled), the system records a refund owed to the customer (`pending`, returned manually), alerts the owner and logs a critical event; the booking stays cancelled and the money is never silently ignored. Accepted in ADR-0004 (decision 5); provider evidence through ADR-0007. Tracked in BQ-003.

## Booking State Rules

The current booking lifecycle is:

```text
pending
paid
cancelled
completed
no_show
```

`no_show` is reached only from `paid` after the session has ended (FR-BOOKING-020). A booking state must not be changed arbitrarily through client-provided input.

Supported transitions must be enforced by business rules.

## Active Booking and Occupied-Slot Rules

A schedule is considered occupied according to the current booking rules.

The current occupied states are:

```text
paid
completed
no_show
```

Amended 2026-11-04 (D-012, D-014): a slot is full when the participants of its occupying bookings reach the service's capacity; a unit is occupied across services for the time of every occupying booking that uses it. Both are decided only by the authoritative occupancy rule (`AGENTS.md` rule 5).

A `pending` booking occupies a schedule only while its payment is `pending` and the payment deadline plus the settlement margin has not passed (ADR-0009). The deadline is `payments.expired_at`, set at creation from `config/payment.php`:

```text
booking payment window: 15 minutes (PAYMENT_BOOKING_DEADLINE_MINUTES)
settlement margin:       2 minutes (PAYMENT_SETTLEMENT_MARGIN_MINUTES)
```

Therefore:

```text
pending + now < expired_at + margin
        ↓
occupies schedule

pending + now ≥ expired_at + margin (or payment failed)
        ↓
does not remain an active occupied reservation

cancelled
        ↓
available
```

The application must not treat all historical `pending` records as permanently occupying a schedule.

## Stale Pending Booking Rules

When a pending booking's payment passes its deadline plus the settlement margin, the booking must be treated as expired for availability purposes.

Stale pending handling must not produce a false double-booking condition.

Where a new booking competes with a stale pending booking, the system must resolve the stale state within the authoritative booking operation before allowing the new reservation to proceed.

The implementation must preserve transactional safety and concurrency protection.

## Payment State Rules

The current payment status model is:

```text
pending
sukses
gagal
```

Payment expiration is not a separate persistent payment status.

An expired payment is handled as a failed payment outcome and must trigger the applicable booking expiration behavior.

Payment state must remain distinct from booking state.

## Payment Expiration Rules

When a payment expires:

```text
Payment
    ↓
gagal
```

and the associated pending booking must no longer remain an active confirmed reservation.

The affected schedule must become eligible according to the booking availability rules.

Payment expiration must not result in a permanently occupied schedule.

## Refund Rules

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

Amended 2026-11-04 (D-001): a customer never cancels a paid booking directly; refunds come from an owner decision on a refund request (FR-REFUND-004) or from an owner cancellation of a booking paid online, which always creates a full refund (FR-REFUND-006). A refund is debited from the tenant balance and transferred by the platform admin (FR-REFUND-007).

## Access Grant Authorization Rules

Customer booking management uses scoped access grants (ADR-0006).

The system must distinguish the purpose of each grant (its profile).

Examples include:

```text
show / management access
invoice access
review submission
cancellation
rescheduling
```

A valid grant for one purpose must not automatically authorize unrelated operations.

A grant belonging to one booking or payment group must not authorize operations on another.

Cross-booking grant use must be rejected.

## Multi-Slot Booking Rules

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

## Walk-In Booking Rules

Walk-in bookings are created by the owner or a team member on behalf of customers (FR-BOOKING-010).

They use the same core booking rules as online bookings.

They must respect:

* tenant isolation;
* schedule availability;
* booking constraints;
* applicable payment requirements;
* booking lifecycle rules.

## Subscription Entitlement Rules

A tenant's restricted feature access shall be derived from:

```text
Plan
+
Subscription state
+
Applicable entitlement rules
```

Feature gating should not be implemented through inconsistent independent checks across unrelated modules.

## Scheduler and Expiration Rules

The system has scheduled processing for payment expiration and related stale booking handling.

The payment-expiration command is expected to run periodically so that expired payment states do not remain indefinitely.

The operational scheduler must execute the registered expiration command according to the current runtime configuration.

Exact production scheduler configuration belongs in:

```text
docs/operations.md
```

The requirement is behavioral:

> Expired payment and stale pending booking states must eventually be reconciled without requiring manual customer intervention.

## Authorization Matrix

The baseline authorization model is:

| Capability               | Owner | Team member |              Customer |                           Admin |
| ------------------------ | ----: | ----------: | --------------------: | ------------------------------: |
| Manage own business      |   Yes | Per FR-TEAM-002 |                No | Platform-level where authorized |
| Manage services, units   |   Yes | Manajer |                    No | Platform-level where authorized |
| Manage schedules, prices |   Yes | Manajer |                    No | Platform-level where authorized |
| View own tenant bookings |   Yes | Yes | Own eligible bookings | Platform-level where authorized |
| Create walk-in booking   |   Yes | Yes |                    No | Not a normal customer operation |
| Create public booking    |    No | No |                   Yes |                              No |
| Manage own booking       |    No | No |      Yes, if eligible | Platform-level where authorized |
| Request a refund         |    No | No | Yes, if refunds are on |                           No |
| Decide a refund request  |   Yes | Manajer |                    No |                              No |
| Process a refund transfer|    No | No |                    No |                             Yes |
| Manage customers         |   Yes | Yes |                    No | Platform-level where authorized |
| Manage categories        |   Yes | Manajer |                    No | Platform-level where authorized |
| Manage promos            |   Yes | Manajer |                    No | Platform-level where authorized |
| Manage team              |   Yes | No |                    No | Platform-level where authorized |
| Balance and payouts      |   Yes | No |                    No | Process payouts                 |
| Submit review            |    No | No |      Yes, if eligible |                              No |
| Manage subscription      |   Yes | No |                    No | Platform-level where authorized |
| Access admin dashboard   |    No | No |                    No |                             Yes |

This matrix is intentionally high-level.

Detailed authorization policy belongs to the architecture and implementation.

## Global Acceptance Criteria

The following criteria apply to relevant tenant-scoped functionality.

### AC-GLOBAL-001 — Tenant Isolation

Given an owner of Tenant A, the owner must not retrieve or modify Tenant B data.

### AC-GLOBAL-002 — Authorization

A user without the required permission must receive an authorization failure instead of the protected operation or data.

### AC-GLOBAL-003 — Invalid Resource

A request for a nonexistent or inaccessible resource must not reveal another tenant's data.

### AC-GLOBAL-004 — Validation

Invalid input must be rejected before an invalid or destructive operation occurs.

### AC-GLOBAL-005 — Transaction Safety

Critical multi-record operations must either complete according to business rules or leave the system in a valid state.

## Non-Functional Verification Policy

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

## Deprecated or Superseded Concepts

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

## Out of Scope

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
Tenant-owned payment gateways (D-016)
Customers choosing a staff member or unit
```

Deferred until after the first production release (D-021): automatic slot opening from opening hours, automatic closing on national holidays, automatic suggestions in reports, down payments, WhatsApp reminders and the WhatsApp Business integration, and custom roles.

A new capability must not be treated as accepted simply because it is technically possible.
