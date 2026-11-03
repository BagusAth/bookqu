# ADR-0017: Capacity and units in slot occupancy

> **Status:** Proposed
> **Date:** 2026-11-04
> **Decider:** Tech Lead — Product Lead for the product scope (D-012, D-014)
> **Work item:** BQ-126 (specification); implementation items follow in epic E6

## Context

**What changes:**
- **D-014** lets a service take several participants per slot (FR-BOOKING-021).
- **D-012** lets several services share a unit that must never be double-booked (FR-UNIT-003). A unit's bookings can come from schedules of different services, with different start and end times.

**Occupancy today:**
- A schedule is occupied by at most one active booking. `BookingRules` is the only home of the occupancy rule (INV-BOOK-03).
- Booking writes lock the tenant row first, then the schedules (ADR-0005 decision 4), through `ClaimSlots`.
- The database guard is the unique index `unique_active_booking_slot` on the virtual column `bookings.active_idschedule`. That column holds `idschedule` while the status is `pending`, `paid` or `completed` (INV-BOOK-04/05).

**Why the guard must change:**
- A unique index on the schedule cannot allow more than one booking per slot.
- A unique index cannot express time overlap across schedules.

## Options

1. **Drop the unique index and rely on locks.** It works because the tenant lock serialises every writer, but it loses the database-level guard that `AGENTS.md` rule 6 relies on.
2. **Counter column on `schedules` (`booked_count`).** Fast, but the counter can drift from the bookings and needs its own repair path.
3. **Seat rows with a unique index, plus unit overlap checked under the tenant lock.** Chosen.

## Decision

1. **Capacity and participants.**
   - `services.kapasitas` (default 1) is the capacity per slot.
   - `bookings.peserta` (default 1) is the number of participants of a booking.
   - The price is per participant (FR-BOOKING-021, ADR-0019).
2. **Seats are the database guard.**
   - A new table `booking_seats` holds one row per participant: `idbooking`, `idschedule`, `seat_no` (0 to capacity − 1), and a virtual `active_idschedule` that holds `idschedule` while the booking occupies the slot.
   - A unique index on (`active_idschedule`, `seat_no`) replaces `unique_active_booking_slot` as the final guard.
   - A claim picks the lowest free seat numbers under the lock.
   - For capacity 1 this is exactly today's guarantee: one seat, seat 0.
   - The old index is removed only in the same migration that creates and backfills the seats (one seat 0 per existing active booking).
3. **Occupied states.** A booking occupies its seats while it is `paid`, `completed` or `no_show` (ADR-0018), or `pending` within its payment deadline plus margin (ADR-0009).
   - `BookingRules` stays the only home of this rule, and of "slot full" (seats taken ≥ capacity).
   - The virtual columns use the same status list, and a test keeps them equal to `BookingRules`.
4. **Units.**
   - Units live in the Booking area: a new table `units` (from `resources`) and a link table `service_unit` (from `service_resource`).
   - A booking uses every active unit of its service at the time of its schedule.
   - `BookingRules::unitConflict()` is the only definition of a conflict: another occupying booking whose schedule overlaps in time and whose service uses the same unit.
5. **Unit checks run inside the existing claim.**
   - `ClaimSlots` already holds the tenant lock, and every writer that can create an occupying booking holds it too (ADR-0005 decision 4). So a unit conflict check inside the claim is serialised per tenant and needs no extra index.
   - The claim refuses with a typed reason (`unit_busy`).
   - Writers that change which units a service uses (Unit page, Layanan form) take the tenant lock and re-check future bookings. They refuse a change that would create a conflict, and list the bookings involved.
6. **Existing conflicts are reported, not repaired.** Data created before units existed may overlap. The Unit page lists those overlaps (FR-UNIT-004). Nothing is cancelled automatically.
7. **Availability.** `AvailabilityRules` / `GetAvailableSchedules` include remaining seats and unit conflicts in the same batch evaluation (ADR-0005 decision 8). Listings show remaining capacity; the claim stays the authority.

## Consequences

- **`AGENTS.md`:** rule 5 now names capacity and units as part of the one occupancy rule, and rule 6 names the seat index as the final guard.
- **`.ai/rules/booking-availability.md`:** updated to match.
- **Tests:**
  - MySQL concurrency tests (`--group mysql`) cover two claims for the last seat, two claims for the same unit from different services, and a unit change racing a booking;
  - SQLite tests cover the rules.
- **Reports:** occupancy uses seats taken over capacity.
- **Migration order:** seats with backfill first, then capacity above 1 can be enabled, then units. Each step is its own work item.
- **Domain files:** `docs/domains/booking.md` and `schedule.md` change with the implementation.
