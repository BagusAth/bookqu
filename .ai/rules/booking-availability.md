---
paths:
  - app/Actions/Booking/**
  - app/Actions/Schedule/**
  - app/Actions/Calendar/**
  - app/Domain/Booking/**
  - app/Domain/Schedule/**
  - app/Http/Controllers/Customer/**
  - app/Http/Controllers/Owner/OwnerBookingController.php
  - app/Http/Controllers/Owner/OwnerScheduleController.php
  - app/Http/Controllers/Owner/OwnerCalendarController.php
  - app/Services/Schedule/**
---

# Booking and availability

Context: `docs/domains/booking.md`, `docs/domains/schedule.md`.

## Use the single occupancy rule
Decide "slot occupied" only through `BookingRules::isSlotOccupied()` / `applyOccupiesSlotCondition()` or `Booking::scopeOccupiesSlot()`. Never write `subMinutes(15)` or status lists such as `['pending','paid','completed']` for occupancy (INV-BOOK-03).

## Use the single availability rule
Decide "bookable" through `AvailabilityRules` / `GetAvailableSchedules`, which cover status, past time, blocked dates, inactive service and occupancy (INV-SCH-01). Calendar, reports, owner and customer reschedule must not define their own.

## Booking writes follow the locked pattern
Inside one `DB::transaction`: lock the schedule rows (`lockForUpdate`), call `EvictStalePendingBookings`, re-check availability, then write. Never remove the `unique_active_booking_slot` index (INV-BOOK-04/05).

## Status changes go through BookingState
Use `BookingState::canTransition()`; cancellation goes through `CancelBooking`, rescheduling through `RescheduleBooking` (INV-BOOK-02).

## Multi-slot is all-or-nothing
Validate contiguity with `BookingRules::validateContiguousSlots()`; create every booking and the shared payment in the same transaction; reject partial cancel/reschedule (INV-BOOK-06).

## Side effects after commit
Send mail and notifications and clear availability cache (`ClearsBookingCache::clearBookingAvailabilityCache`) after the transaction commits; a failure there must not undo the booking.
