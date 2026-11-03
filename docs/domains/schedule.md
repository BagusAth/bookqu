# Schedule & availability

Owns bookable time slots, blocked dates, slot pricing, and the definition of "available".

## Entry points

| Route | Controller |
|---|---|
| `owner.schedule`, `owner.schedule.bulk-store`, `owner.schedule.slots.destroy`, `owner.schedule.default-pricing`, `owner.schedule.availability`, `owner.schedule.blocked-dates.delete` | `Owner/OwnerScheduleController` |
| Storefront date/time steps | `Customer/BookingController::showDateSelection`, `selectDate`, `showTimeSelection`, `selectTime` |
| Owner reschedule slots | `Owner/OwnerBookingController::getAvailableSlots` |
| Customer reschedule slots | `Customer/BookingManageController::showReschedule`, `getTimeSlots` |

## Operations

`Actions/Schedule/BulkCreateSchedules`, `DeleteSchedule`, `UpdateScheduleAvailability`, `DeleteBlockedDate`, `GetAvailableSchedules` (`getDateAvailability`, `countAvailableSlotsOnDate`, `getTimeSlots`, `getSlotsForReschedule`, `getCustomerRescheduleSlots`).

## Rules (authoritative)

| Class | Owns |
|---|---|
| `App\Domain\Schedule\AvailabilityRules` | Slot status `AVAILABLE` / `BOOKED` / `BLOCKED` / `UNAVAILABLE`; blocked dates; past slots |
| `App\Domain\Schedule\ScheduleConflictRules` | Overlap detection, deletability, weekend price |
| `App\Domain\Schedule\SlotCompatibilityRules` | Contiguous / compatible multi-slot selection |
| `App\Domain\Booking\BookingRules::isSlotOccupied` | Occupancy (see `booking.md`, INV-BOOK-03) |

## Data

`schedules`: `idtenant`, `idlayanan`, `tanggal`, `jam_mulai`, `jam_selesai`, `harga_override`, `status` (`tersedia` / `tidak_tersedia`). `owner_blocked_dates`: `idtenant`, `tanggal`. Times are `Asia/Jakarta` (also `config('app.timezone')`).

## Cache

`tenant:{id}:service:{id}:availability:{min}:{max}`, `tenant:{id}:service:{id}:schedules:{date}`; cleared via `ClearsBookingCache::clearBookingAvailabilityCache` after booking mutations.

## Invariants

- **INV-SCH-01** — bookable only if `tersedia`, in the future, not blocked, service active with active fulfillment, and not occupied.

## Evidence

`tests/Unit/Domain/AvailabilityRulesTest.php`, `ScheduleConflictRulesTest.php`, `SlotCompatibilityRulesTest.php`, `tests/Feature/Schedule/ScheduleActionTest.php`, `Owner/ScheduleManagementTest.php`.

## Status

Functional: Implemented · Verification: Verified (whole suite on SQLite, MySQL 8 and MariaDB 11.4 in CI; lock tests in `ClaimSlotsLockingTest`) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Availability cache keys and invalidation are still built by callers (`ClearsBookingCache`, `ScheduleAvailabilityCache`), not owned by the Slot bookability module (ADR-0005 decision 9) | BQ-075 |
| `AvailabilityRules` checks status `diblokir`, which the DB enum cannot store | BQ-016 |
| Schedule page and calendar month view decide occupancy in Blade instead of `BookingRules` | BQ-133 |
| Bulk schedule date range capping, past slot protection, and occupancy check on delete | BQ-107 |
| Conflict detection and notification when blocking dates with existing active bookings | BQ-108 |

Implemented decisions: [ADR-0005](../adr/ADR-0005-slot-bookability-module.md) (slot claims, BQ-012/BQ-069; cache ownership still open, BQ-075) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (hold derived from the payment deadline, BQ-071).

BQ-120: reschedule slot selectability now delegates to `AvailabilityRules::isSlotAvailable`, including closed dates, service fulfillment, time and occupancy, with current-booking exclusion. `BookingManagementTest` verifies blocked GET/POST refusal, reopening and successful claim.
