# Booking

The central domain: creating, changing and ending reservations, including multi-slot and walk-in bookings.

## Entry points

| Flow | Route → code |
|---|---|
| Storefront booking (3 selection steps: Layanan → Jadwal → Data & Pembayaran; followed by payment/invoice) | `customer.booking[.slug].*` → `Customer/BookingController` (session key `booking`) |
| Combined schedule page / AJAX / selection | GET `booking/schedule`, GET `booking/schedule/slots`, POST `booking/select-schedule` in both slug and custom-domain no-store groups; AJAX is throttled to 60/minute |
| Legacy navigation | GET `booking/date` and `booking/time` redirect to schedule and preserve flash feedback; legacy POST `select-date`/`select-time` remain supported |
| Create booking | `process-checkout` → `Actions/Booking/CreateBooking` |
| Owner list / status / walk-in / reschedule | `owner.bookings`, `owner.bookings.status`, `owner.bookings.walkin`, `owner.bookings.reschedule` → `Owner/OwnerBookingController` |
| Customer cancel / reschedule | `booking.manage.cancel`, `booking.manage.reschedule.*` → `Customer/BookingManageController` (see `customer-access.md`) |

## Operations

| Action | Does |
|---|---|
| `GetScheduleSelection` | Builds 31 Indonesian date entries and morning/afternoon/evening slots through `GetAvailableSchedules`; shared by page and AJAX. AJAX service comes only from the tenant-scoped session; closed dates expose no slots. |
| `ValidateBookingDate` | Shared POST date-window, monthly-quota, owner-closed and availability checks before session mutation. The controller's shared `storeSelectedTime` path validates both new and legacy slot submissions; legacy POST time ignores request-supplied dates. |
| `CreateBooking` | One transaction: lock subscription/tenant row and check monthly quota (`EntitlementRules::canCreateBooking`), lock schedules, evict stale pending, validate tenant/service/date/status/past/occupancy, check contiguity, price slots (`BookingRules::calculateSlotPrices`), create one payment + N bookings (`pending`, or `paid` when free). After commit: initialize the gateway payment; on failure mark payment `gagal` and bookings `cancelled`; clear availability cache. |
| `CreateWalkInBooking` | Owner booking: lock schedule, evict stale pending, check occupancy, create `paid` booking with a `cash` payment `sukses`. |
| `UpdateBookingStatus` | Owner status change through `BookingState::canTransition`; `cancelled` delegates to `CancelBooking`. |
| `CancelBooking` | Actor `customer` or `owner`. Customers cannot cancel multi-slot bookings and must pass `BookingRules::canCancel` (paid, before `cancel_before_hours`). Owners cancel the whole payment group. Creates a `Refund` only for customer cancellation of a successful payment (one per payment). |
| `RescheduleBooking` | Moves a single-slot booking to another available schedule; multi-slot is rejected. |
| `EvictStalePendingBookings` | Inside the caller's transaction: request the stale pending booking's payment expiry from `PaymentLifecycle` and cancel the booking. |

Unexpected checkout/reschedule failures return generic Indonesian messages to customers and owners. Provider initialization failures (Exception or Error) still transition through PaymentLifecycle and release the pending bookings. Explicit slot-refusal messages remain actionable. Related diagnostics log operation identifiers and exception type/location, without raw exception messages, objects or provider response bodies (BQ-005).

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

`tests/Feature/Customer/SafeOperationErrorsTest.php` verifies generic checkout/session and reschedule/JSON errors, Exception/Error cleanup, preserved slot-refusal messages and redacted diagnostics with fake providers.

BQ-084: `ThreeStepBookingFlowTest` covers the combined flow, AJAX validation/isolation, date refusals without session mutation, session-date authority, old GET redirects, unsafe characters and pinned Alpine. `BookingFlowHardeningTest` retains BQ-083 fingerprints, stale-CSRF feedback, pending banners and payment guards. Legacy date/time views and JS are removed after reference checks; routes keep their names for compatibility. Client JS lives in `public/js/booking-schedule.js`, rejects gaps inline and ignores stale/aborted AJAX responses.

## Status

Functional: Implemented · Verification: Verified (whole suite on SQLite, MySQL 8 and MariaDB 11.4 in CI; lock tests in `CreateBookingLockingTest`) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| `Customer/BookingController` (≈850 lines) still holds the storefront session handling and repeats contiguity checks before checkout (the claim itself goes through `ClaimSlots`; date checks live in `ValidateBookingDate`) | BQ-029 |

Implemented decisions: [ADR-0005](../adr/ADR-0005-slot-bookability-module.md) (slot claims, BQ-012) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (pending hold derived from the payment deadline, BQ-071). Not implemented yet: [ADR-0008](../adr/ADR-0008-booking-checkout-draft.md) (typed checkout draft, BQ-029); until then BQ-083's checkout fingerprint keeps the quote equal to the charge.
