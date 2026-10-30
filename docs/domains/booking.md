# Booking

The central domain: creating, changing and ending reservations, including multi-slot and walk-in bookings.

## Entry points

| Flow | Route → code |
|---|---|
| Storefront booking (6 steps: service, date, time, checkout, payment, invoice) | `customer.booking[.slug].*` → `Customer/BookingController` (session key `booking`) |
| Create booking | `process-checkout` → `Actions/Booking/CreateBooking` |
| Owner list / status / walk-in / reschedule | `owner.bookings`, `owner.bookings.status`, `owner.bookings.walkin`, `owner.bookings.reschedule` → `Owner/OwnerBookingController` |
| Customer cancel / reschedule | `booking.manage.cancel`, `booking.manage.reschedule.*` → `Customer/BookingManageController` (see `customer-access.md`) |

## Operations

| Action | Does |
|---|---|
| `CreateBooking` | One transaction: lock subscription/tenant row and check monthly quota (`EntitlementRules::canCreateBooking`), lock schedules, evict stale pending, validate tenant/service/date/status/past/occupancy, check contiguity, price slots (`BookingRules::calculateSlotPrices`), create one payment + N bookings (`pending`, or `paid` when free). After commit: initialize the gateway payment; on failure mark payment `gagal` and bookings `cancelled`; clear availability cache. |
| `CreateWalkInBooking` | Owner booking: lock schedule, evict stale pending, check occupancy, create `paid` booking with a `cash` payment `sukses`. |
| `UpdateBookingStatus` | Owner status change through `BookingState::canTransition`; `cancelled` delegates to `CancelBooking`. |
| `CancelBooking` | Actor `customer` or `owner`. Customers cannot cancel multi-slot bookings and must pass `BookingRules::canCancel` (paid, before `cancel_before_hours`). Owners cancel the whole payment group. Creates a `Refund` only for customer cancellation of a successful payment (one per payment). |
| `RescheduleBooking` | Moves a single-slot booking to another available schedule; multi-slot is rejected. |
| `EvictStalePendingBookings` | Inside the caller's transaction: request the stale pending booking's payment expiry from `PaymentLifecycle` and cancel the booking. |

## Rules (authoritative)

`App\Domain\Booking\BookingState` (states, transitions, labels) · `App\Domain\Booking\BookingRules` (pending hold from the payment deadline, occupancy condition, contiguity, pricing, cancel/reschedule policy).

## Data

`bookings`: `idtenant`, `idlayanan`, `idschedule`, `namapelanggan`, `nomorhp`, `email`, `tanggalbooking`, `jam`, `status`, `idpayment`, `catatan`, `booking_code` (unique), `cancellation_token`, `reschedule_token`, `rescheduled_from_*`. Virtual column `active_idschedule` with unique index `unique_active_booking_slot` (pending/paid/completed). `booking_logs` keeps an event trail.

## Invariants

- **INV-BOOK-01** — status ∈ `pending`, `paid`, `cancelled`, `completed`.
- **INV-BOOK-02** — transitions only via `BookingState::canTransition`: pending→paid / cancelled / completed; paid→completed / cancelled. `pending→completed` is used when the owner completes an unpaid online booking (e.g. paid on site); the online payment stays `pending` until it expires (BQ-014).
- **INV-BOOK-03** — occupying = `paid`, `completed`, or `pending` while its payment is `pending` and `now < payments.expired_at + settlement margin` (ADR-0009). The rule lives only in `BookingRules::applyOccupiesSlotCondition`.
- **INV-BOOK-04** — at most one occupying booking per schedule (row lock + `unique_active_booking_slot`).
- **INV-BOOK-05** — stale pending is evicted inside the booking transaction.
- **INV-BOOK-06** — multi-slot is contiguous, all-or-nothing, one payment; customers cannot cancel or reschedule it partially.

## Evidence

`tests/Unit/Domain/BookingRulesTest.php`, `BookingStateTest.php`, `tests/Feature/CoreFlowIntegrationTest.php`, `Customer/BookingFlowTest.php`, `Customer/ProductionLogicSpecificationTest.php`, `Owner/BookingManagementTest.php`. Concurrency is only exercised on SQLite, where `lockForUpdate` has no effect.

## Status

Functional: Implemented · Verification: Verified (SQLite only) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| `Customer/BookingController` is 1,034 lines and re-implements quota, blocked-date, occupancy and contiguity checks | BQ-029, BQ-012 |
| Concurrency not proven on MySQL | BQ-023 |
| Internal exception text returned to the customer (`CreateBooking.php:313`) | BQ-005 |
| `pending→completed` leaves the payment pending; no on-site payment record | BQ-014 |
| Notifications and mail are sent synchronously | BQ-013 |

Target decisions: [ADR-0005](../adr/ADR-0005-slot-bookability-module.md) (slot bookability, BQ-012), [ADR-0008](../adr/ADR-0008-booking-checkout-draft.md) (server-side checkout draft, quote = charge, BQ-029) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (pending hold derived from the payment deadline, BQ-071). Not implemented yet.
