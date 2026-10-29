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

Functional: Implemented · Verification: Verified (SQLite only) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| "Available" is re-implemented outside the rules: `BookingController::selectTime` (hard-coded 15 min), `BookingManageController::showReschedule` (own SQL, ignores blocked dates), `GetOwnerCalendarData` and `GenerateScheduleReport` (treat every pending as occupied) | BQ-012 |
| `AvailabilityRules` checks status `diblokir`, which the DB enum cannot store | BQ-016 |
