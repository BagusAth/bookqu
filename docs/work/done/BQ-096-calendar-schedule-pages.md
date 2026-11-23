---
id: BQ-096
title: Calendar and schedule pages - cramped grids, fake pricing panel, occupancy decided in Blade
type: bug-fix
status: rejected
priority: P2
phase: 8
domain: schedule
requirements: [FR-CALENDAR-001, FR-CALENDAR-003, FR-CALENDAR-004, FR-SCHEDULE-003, FR-SCHEDULE-009]
invariants: [INV-BOOK-03]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-092]
---

# BQ-096: Calendar and schedule pages - cramped grids, fake pricing panel, occupancy decided in Blade

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-133](BQ-133-jam-buka-dan-harga-page.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-133.


Found in the owner UI audit of 2026-11-02 on the demo tenant. Heavy lane because of item 1 (occupancy rule); the rest is light work inside the same pages.

## Problem

**1. Occupancy decided in Blade.** The schedule page marks a slot as booked when any non-cancelled booking exists (`resources/views/owner/schedule.blade.php:179`, `:281`), so an expired `pending` booking still blocks the slot on screen; the calendar month view counts only `paid` as confirmed and leaves `completed` out (`partials/calendar/month-view.blade.php:35-37`). The single occupancy rule lives in `BookingRules` (INV-BOOK-03); availability status is also recomputed per day inside the views (`week-view.blade.php:36-37`).

**2. Schedule page.**
- A "Bulk Price Adjustments" panel advertises "Surge Pricing (Weekend) +20%" and "Early Bird Discounts −15% … 30 days in advance". Early-bird pricing does not exist; both buttons just open the availability modal; the panel's settings button has no action (`schedule.blade.php:373-409`).
- "Status Live" metric with a permanent ping and a truncated "02 Nov • 0…" value; "Rasio Terbo…" and "Rp 10.…" truncated (`schedule.blade.php:43-107`).
- Header buttons wrap ("Add Bulk Slots" on three lines at 1024 px); English labels ("Schedule Management", "Set Default Pricing", "Configure", "Recent Activity", "New Booking … booked … at").
- Week grid cells show price, "Available/Booked" and time in 10–11 px text; seven columns do not fit next to the sidebar at 1024 px.
- HTML 507 KB for one week.

**3. Calendar page.**
- Week view at 1024 px: service names truncated to one letter ("S", "P"), Saturday and Sunday need horizontal scrolling, free slots render a vertical "+ Walk-in" button inside a tiny card, blocked slots show "[Diblokir]" in brackets (`partials/calendar/week-view.blade.php`).
- Uppercase status micro-labels ("CONFIRMED", "TERSEDIA") and per-status colours that differ from the rest of the app (BQ-092).
- Header "Calendar & Jadwal Operasional" with buttons "Kelola Schedule" / "Daftar Bookings" (mixed language, BQ-093); HTML 478 KB and the slowest owner page (1.4 s locally vs ~0.6 s for others).

**4. Schedule modals and reschedule edge cases (Added 2026-11-02 deep audit)**
- `components/owner/modal-add-bulk-slots.blade.php:117-130`: in "Date Range" mode, inputs `tanggalmulai` and `tanggalselesai` lack conditional `required` attributes, allowing empty range submission.
- `components/owner/modal-reschedule-booking.blade.php:15`: `minDate` uses `new Date().toISOString().split('T')[0]`, which converts local time to UTC. For Indonesian time zones (WIB / UTC+7) between 00:00 and 06:59, this sets `minDate` to yesterday.
- The schedule modals post to hand-written paths (`/owner/schedule/bulk-slots`, `/owner/schedule/availability`, `/owner/schedule/default-pricing`) instead of `route()` (`owner.schedule.bulk-store`, `owner.schedule.availability`, `owner.schedule.default-pricing`); tracked with the other hand-written URLs in BQ-105. The server also accepts an empty date range (`tanggalmulai`/`tanggalselesai` are `nullable` in `BulkCreateScheduleRequest`), see BQ-107. (Corrected by the Tech Lead review on 2026-11-02: the original quoted paths and route names did not exist.)

## Plan

1. Views receive a prepared state per slot and per day (`booked`, `held`, `free`, `blocked`, counts) from an Action that uses `BookingRules`/`AvailabilityRules`; Blade only renders it (Tech Lead review for the rule usage).
2. Remove the fake pricing panel. Weekend pricing stays reachable from the existing availability/pricing modal; any new pricing feature is a product decision (`docs/product-decisions.md`).
3. Replace "Status Live" with something real (next booking) or remove it; no truncated metrics.
4. Week grids: readable at 1024 px (fewer details per cell, details in the drawer; or a 3-day/day view by default under 1280 px); agenda list on phones.
5. Status colours and labels from BQ-092; copy from BQ-093.
6. Reduce payload: render only the active view's data, move per-slot JSON to the drawer request.

## Invariants at risk

INV-BOOK-03: the schedule and calendar must show the same occupancy the storefront uses.

## Tests

- Feature: a slot whose only booking is an expired pending one shows as free on the schedule page; `completed` counts as occupied in the month view.
- Manual at 1024 px and 375 px for week, day and month views.

## Docs to update

`docs/domains/schedule.md` and `docs/domains/insights.md` (BQ-012 gap about calendar counts, if still listed).

## Evidence (fill when done)

_Fill in when done._
