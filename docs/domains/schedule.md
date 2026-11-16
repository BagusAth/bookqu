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

`Actions/Schedule/BulkCreateSchedules` (`execute`, `preview`), `SaveOpeningHours`, `SavePriceRules`, `SimulateSlotPrices`, `ApplyBasePriceToOpenSlots`, `GetSlotRunway`, `PriceSlots`, `DeleteSchedule`, `UpdateScheduleAvailability`, `DeleteBlockedDate`, `GetAvailableSchedules` (`getDateAvailability`, `countAvailableSlotsOnDate`, `getTimeSlots`, `getSlotsForReschedule`, `getCustomerRescheduleSlots`).

## Rules (authoritative)

| Class | Owns |
|---|---|
| `App\Domain\Schedule\AvailabilityRules` | Slot status `AVAILABLE` / `BOOKED` / `BLOCKED` / `UNAVAILABLE`; blocked dates; past slots |
| `App\Domain\Schedule\ScheduleConflictRules` | Overlap detection, deletability |
| `App\Domain\Schedule\SlotPricing` (with `PriceRules`) | A slot's price: override, else special-date price, else base with the weekend rule; plus matching peak-hour extras; whole rupiah (FR-SCHEDULE-012) |
| `App\Domain\Schedule\SlotCompatibilityRules` | Contiguous / compatible multi-slot selection |
| `App\Domain\Booking\BookingRules::isSlotOccupied` | Occupancy (see `booking.md`, INV-BOOK-03) |
| `App\Domain\Booking\BookingRules::hasRoomFor` / `hasRoomSql` / `unitBusyRanges` | Room for the participants against `services.kapasitas`, and unit conflicts; listings show `seats_left` (ADR-0017 decision 7) |

## Data

`schedules`: `idtenant`, `idlayanan`, `tanggal`, `jam_mulai`, `jam_selesai`, `harga_override`, `status` (`tersedia` / `tidak_tersedia`). `owner_blocked_dates`: `idtenant`, `tanggal`, `alasan`. `opening_hours`: `idtenant`, `day_of_week` (ISO), `opens_at`, `closes_at` (several rows per day = breaks). `peak_hour_rules` (+ `peak_hour_rule_service`; no services = all), `special_date_prices` (unique per tenant, service and date). `tenants.slot_open_days_ahead` caps bulk creation; `tenants.weekend_price_*` holds the weekend rule. `harga_override` is only an explicit per-slot price: bulk creation no longer writes the weekend price into it. Times are `Asia/Jakarta` (also `config('app.timezone')`).

## Cache

`tenant:{id}:service:{id}:availability:{min}:{max}`, `tenant:{id}:service:{id}:schedules:{date}`; cleared via `ClearsBookingCache::clearBookingAvailabilityCache` after booking mutations.

## Invariants

- **INV-SCH-01** — bookable only if `tersedia`, in the future, not blocked, service active with active fulfillment, with room for the participants, and no shared unit busy at that time.

## Evidence

`tests/Unit/Domain/AvailabilityRulesTest.php`, `ScheduleConflictRulesTest.php`, `SlotCompatibilityRulesTest.php`, `tests/Feature/Schedule/ScheduleActionTest.php`, `BulkScheduleLimitsAndDeleteOccupancyTest.php`, `BlockedDateActiveBookingConflictTest.php`, `Owner/ScheduleManagementTest.php`.

## Status

Functional: Implemented · Verification: Verified (whole suite on SQLite, MySQL 8 and MariaDB 11.4 in CI; lock tests in `ClaimSlotsLockingTest`) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Availability cache keys and invalidation are still built by callers (`ClearsBookingCache`, `ScheduleAvailabilityCache`), not owned by the Slot bookability module (ADR-0005 decision 9) | BQ-075 |
| `AvailabilityRules` checks status `diblokir`, which the DB enum cannot store | BQ-016 |
| Schedule page and calendar month view decide occupancy in Blade instead of `BookingRules` | BQ-133 (page, after BQ-129) |
| Slots created before BQ-133 on weekends carry the weekend price as `harga_override`, so they ignore later rule changes until "Terapkan harga dasar" clears them | BQ-133 |

Implemented decisions: [ADR-0005](../adr/ADR-0005-slot-bookability-module.md) (slot claims, BQ-012/BQ-069; cache ownership still open, BQ-075) and [ADR-0009](../adr/ADR-0009-pending-payment-lifetime.md) (hold derived from the payment deadline, BQ-071).

BQ-107: bulk creation refuses past dates and caps a `rentang` at `BulkCreateSchedules::MAX_RANGE_DAYS` (90) after the start date; slots are inserted in one batch per day after one read of that day's existing slots. Deleting a slot is refused only while `BookingRules::isSlotOccupied` holds; stale pending bookings on it expire through the Payment lifecycle (`EvictStalePendingBookings`) before the slot is deleted.

BQ-108 (AC-JBH-03): `UpdateScheduleAvailability` refuses to close a date, and saves nothing, while `ScheduleConflictRules::activeBookingsOnDate` (the occupancy rule applied to the tenant's bookings on that date) returns any booking; the owner sees the count and the affected bookings until each is rescheduled or cancelled.

BQ-114: the storefront slot picker (`GetAvailableSchedules::getTimeSlots`) and the checkout summary show each slot's own end and length (`BookingTimeRange::forSlot`), not the service's current duration; inclusive ends (`subMinute()` in `BulkCreateSchedules`) display one minute later.

BQ-120: reschedule slot selectability now delegates to `AvailabilityRules::isSlotAvailable`, including closed dates, service fulfillment, time and occupancy, with current-booking exclusion. `BookingManagementTest` verifies blocked GET/POST refusal, reopening and successful claim.

BQ-133 backend (AC-JBH-01, 02, 04, 05): `SlotPricing` is the one price rule; the simulator, storefront slot lists, owner calendar and schedule page, the export, the checkout quote and walk-ins read it. Bulk interval is 5–480 minutes in steps of 5 and may differ from the service duration (FR-SCHEDULE-014; the page defaults it to the duration). `BulkCreateSchedules::preview` counts new and existing slots without saving; writes re-read the day's slots inside the tenant lock. Price-rule writes take the tenant lock. `GetSlotRunway` reads `GetAvailableSchedules::getDateAvailability`. Tests: `SlotPricingTest`, `PriceRulesAndOpeningHoursTest`.
