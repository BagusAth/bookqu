---
id: BQ-086
title: An expired trial or subscription keeps Pro limits and the Pro badge
type: bug-fix
status: in-progress
priority: P1
phase: 4
domain: subscription
requirements: [FR-SUB-003, FR-SUB-004, FR-SUB-005, FR-SUB-006, FR-BOOKING-005]
invariants: [INV-SUB-01]
lane: heavy
approval: tech-lead
launch-blocker: true
depends-on: []
---

# BQ-086: An expired trial or subscription keeps Pro limits and the Pro badge

## Problem

Reported 2026-11-01: an owner account whose 7-day trial ended more than a week ago still has every Pro benefit. Local evidence: tenant 6, trial created 2026-10-18, `trial_berakhir` 2026-10-25, status still `trial` on 2026-11-01.

"Trial ended" has no effect apart from two feature-gated routes:

| Area | What happens after the trial ends | Evidence |
|---|---|---|
| Feature routes (analytics, landing page) | Blocked by date: correct, but these modules are locked or hidden anyway, so nobody notices | `app/Http/Middleware/CheckSubscription.php:41`, `app/Domain/Subscription/EntitlementRules.php:26` |
| Service, staff and monthly booking limits | Computed from the plan on the expired row (`pro`), so they stay **unlimited** | `EntitlementRules.php:85-89`, `:124-125`, `:162-163`; `app/Domain/Subscription/PlanCapability.php:40-88` |
| Storefront booking | `canCreateBooking` allows unlimited bookings; nothing checks that the subscription is active | `app/Actions/Booking/CreateBooking.php:90`, `app/Actions/Booking/ValidateBookingDate.php:34` |
| Walk-in | Correctly refused when expired (the only write path that checks) | `app/Actions/Booking/CreateWalkInBooking.php:41` |
| Sidebar "Pro" badge | Uses `status != 'expired'` and `str_contains(namapaket, 'pro')`; the status changes only when the cron runs | `app/Providers/AppServiceProvider.php:75-85` |
| Subscription page and dashboard | Trial flag is date-based, but limits shown fall back to the expired plan's (Pro) limits | `app/Actions/Subscription/GetSubscriptionOverview.php:79-93` |
| `status` column | Changed to `expired` only by `subscriptions:check` (cron); the cron has never run locally and is not yet set up in production (BQ-020) | `app/Console/Commands/CheckExpiredSubscriptions.php:46-58` |

A second fail-open path: a tenant with **no** subscription row (or a row without a plan) is treated as unlimited in every `can*` method (`EntitlementRules.php:76`, `:115`, `:152`, `:198`, `:208`, `:221`). ADR-0011 decision 4 says entitlement fails closed.

Root cause: `EntitlementRules` asks "is this a live trial?" and otherwise reads the plan on the latest row, without asking whether that row is active at all.

## Plan

1. `EntitlementRules` resolves one effective entitlement per tenant: an active subscription (by date, `SubscriptionState::isActive`) gives its plan's features and limits, an active trial gives Pro-level access, and **no active subscription gives nothing** (no new services, staff or bookings; no gated features). This fail-closed default matches ADR-0011; product decision D-002 (`docs/product-decisions.md`) may later relax what an inactive tenant keeps, by changing only this mapping.
2. Every `can*` / `isUnlimited*` method and `CheckSubscription` use that one resolution; missing subscription or plan fails closed.
3. Storefront: when the tenant cannot take bookings, `ValidateBookingDate` and `CreateBooking` refuse with a neutral customer message ("Bisnis ini belum menerima booking online saat ini.") that never mentions subscriptions. Existing paid bookings and payments already in progress are unaffected.
4. Sidebar badge, subscription page and dashboard read the same resolution (`isPro` from the effective plan, not `str_contains` and not the `status` column). This closes the `str_contains` part of BQ-033.
5. Everything is decided by dates; `subscriptions:check` only sends reminders and tidies the `status` column.
6. Owner dashboard shows an "expired" state (banner with a link to the subscription page) instead of trial or Pro labels; final copy follows D-002.

## Invariants at risk

- INV-SUB-01: no plan check outside `EntitlementRules` after this change (sidebar included).
- Existing paid bookings, customer access, payments in progress and payouts must keep working for an expired tenant.
- Tenants with a live trial or an active paid plan must see no change.

## Tests

- Expired trial (status still `trial`, date in the past): service, staff and booking creation refused; storefront date step and checkout refused with the neutral message; sidebar shows no Pro badge.
- Expired paid plan: same as above.
- Live trial: unlimited, Pro badge. Active `small`/`medium`: their own limits.
- No subscription row / no plan: refused (fail closed).
- Expired tenant can still open the dashboard, manage an existing booking and buy a plan.
- Fixed time with `travelTo`; no reliance on the cron.

## Docs to update

`docs/domains/subscription.md` (current behaviour, known gaps), `docs/domains/booking.md` (storefront refusal), BQ-033 (str_contains part done).

## Evidence (fill when done)

_Fill in when done._

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): effective date/state/plan resolution now gates creation, unlimited limits, feature middleware and the Pro badge; inactive storefront writes return neutral copy, owner dashboard offers renewal. Four new tests in `ExpiredEntitlementTest`; original happy-path assertions retained with explicit live-trial prerequisites. `Subscription::plan` now has its real generic relationship type; nine obsolete PHPStan baseline entries removed, and the sidebar architecture allowance reduced.

Verification: `php artisan test --compact`: 673 passed / 3541 assertions; Pint fixed changed PHP; PHPStan: no errors (baseline only reduced); MySQL group: 11 passed / 43 assertions, including the schedule-row wait assertion. No scheduler required, no migration or dependency. Status stays in-progress until merge.
