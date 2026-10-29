# Domains

One file per domain. Each file says **what the domain owns, where its code lives, which invariants it protects, what proves them, and how healthy it is**. Read only the domain you are working on.

This is the descriptive map of the current system. The normative rules live in `docs/architecture.md` and `.ai/rules/`; required behaviour lives in `docs/requirements.md`.

## Index

| Domain | File | Functional | Verification | Architecture |
|---|---|---|---|---|
| Tenancy & business profile | [tenancy.md](tenancy.md) | Implemented | Needs Verification | Target (gaps noted) |
| Authentication & accounts | [auth.md](auth.md) | Implemented | Verified (partial) | Needs Refactor |
| Catalog (services, categories) | [catalog.md](catalog.md) | Implemented | Verified | Target |
| Schedule & availability | [schedule.md](schedule.md) | Implemented | Verified (SQLite only) | Needs Refactor |
| Booking | [booking.md](booking.md) | Implemented | Verified (SQLite only) | Needs Refactor |
| Customer access (management links) | [customer-access.md](customer-access.md) | Implemented | Needs Verification | Needs Refactor |
| Payment & refund | [payment.md](payment.md) | Implemented | Verified (partial) | Needs Refactor |
| Subscription & billing | [subscription.md](subscription.md) | Implemented | Verified (partial) | Target (gaps noted) |
| Platform balance & payouts | [balance-payout.md](balance-payout.md) | Partial | Needs Verification | Target |
| Customers CRM & reviews | [customers-reviews.md](customers-reviews.md) | Implemented | Verified | Target |
| Notifications & mail | [notifications.md](notifications.md) | Implemented | Needs Verification | Needs Refactor |
| Insights (dashboard, calendar, analytics, reports) | [insights.md](insights.md) | Implemented | Needs Verification | Needs Refactor |
| Staged modules (Tier 2 / Tier 3) | [staged-modules.md](staged-modules.md) | Implemented, gated | Needs Verification | Needs Refactor |
| Platform admin | [admin.md](admin.md) | Partial | Verified (partial) | Not Evaluated |
| Public site (landing, pricing, legal, sitemap) | [public-site.md](public-site.md) | Implemented | Verified | Target |

## Status vocabulary

| Column | Values |
|---|---|
| Functional | `Implemented` · `Partial` · `Planned` · `Deprecated` |
| Verification | `Verified` (automated evidence covers the critical behaviour) · `Verified (partial)` · `Verified (SQLite only)` (concurrency not yet proven on MySQL) · `Needs Verification` |
| Architecture | `Target` (follows `architecture.md`) · `Needs Refactor` · `Not Evaluated` |

Status changes only with evidence (test, command output, recorded manual check). Work to change a status is a `BQ-*` item in `docs/work/`.

## Invariant index

Invariants are the rules that must hold at all times. Each one has a home domain.

| ID | Invariant | Home |
|---|---|---|
| INV-TEN-01 | A tenant never reads or changes another tenant's data. Tenant-owned models apply `TenantScope`; an empty tenant context returns no rows. | tenancy |
| INV-TEN-02 | Tenant context comes from `TenantMiddleware` (route slug, custom domain, or the authenticated owner), never from client input. | tenancy |
| INV-SCH-01 | A schedule is bookable only if it is `tersedia`, in the future, not on a blocked date, its service is active with active fulfillment, and it is not occupied. | schedule |
| INV-BOOK-01 | Booking status is one of `pending`, `paid`, `cancelled`, `completed`. | booking |
| INV-BOOK-02 | Booking status changes only through `BookingState::canTransition`: pending→paid/cancelled/completed, paid→completed/cancelled; `cancelled` and `completed` are terminal. | booking |
| INV-BOOK-03 | A booking occupies its schedule when `paid`, `completed`, or `pending` created within the last 15 minutes (`BookingRules::PENDING_GRACE_MINUTES`). | booking |
| INV-BOOK-04 | At most one occupying booking per schedule: row lock in the booking transaction plus DB unique index `unique_active_booking_slot`. | booking |
| INV-BOOK-05 | Stale pending bookings are evicted inside the booking transaction before a new booking claims the slot. | booking |
| INV-BOOK-06 | A multi-slot booking is contiguous, all-or-nothing, and shares one payment. | booking |
| INV-PAY-01 | Payment state is separate from booking state. | payment |
| INV-PAY-02 | Payment status is `pending`, `sukses` or `gagal`; `sukses` and `gagal` are terminal; expiry sets `gagal`. | payment |
| INV-PAY-03 | Repeated provider events produce no duplicate state change, refund, notification or booking side effect. | payment |
| INV-PAY-04 | Payment success comes only from trusted provider evidence (verified webhook or server-side status query). | payment |
| INV-PAY-05 | An existing payment always resolves its own provider (`PaymentGatewayManager::forPayment`). | payment |
| INV-PAY-06 | Refunds have their own lifecycle; at most one refund per payment. | payment |
| INV-TOK-01 | A management token authorizes only its own booking/payment group and only its own capability. | customer-access |
| INV-SUB-01 | Feature access and limits are decided by `EntitlementRules`. | subscription |
