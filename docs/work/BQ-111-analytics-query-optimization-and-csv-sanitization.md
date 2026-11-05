---
id: BQ-111
title: Analytics query aggregation optimization and CSV formula injection protection
type: improvement
status: in-progress
priority: P2
phase: 4
domain: insights
requirements: [FR-ANALYTICS-001, FR-REPORT-003, NFR-PERF-004, NFR-SEC-004]
invariants: []
lane: light
approval: none
decision:
launch-blocker: true
depends-on: []
---

# BQ-111: Analytics query aggregation optimization and CSV formula injection protection

Found during deep audit of analytics reporting and export streams on 2026-11-02.

## Problem

### 1. Excessive Serial Queries in Analytics Controller (NFR-PERF-004)
In `app/Http/Controllers/Owner/OwnerAnalyticsController.php:67-80, 130-140`:
- Monthly revenue and booking counts iterate across a 12-month loop executing 24 individual queries:
  `Payment::whereBetween('created_at', ...)->sum('jumlah')` and `Booking::whereBetween('tanggalbooking', ...)->count()`.
- Daily appointments iterate across a 30-day loop executing 30 individual queries:
  `Booking::whereDate('tanggalbooking', ...)->count()`.
- Overall, accessing `/owner/analytics` executes over 60 database queries serially on every page view instead of executing single grouped SQL aggregations (`GROUP BY DATE_FORMAT(...)`).

### 2. Multi-Slot Booking Revenue Multiplication on CSV Export
In `OwnerAnalyticsController.php:197`:
```php
$harga = $booking->payment?->jumlah ?? $booking->layanan->harga ?? 0;
```
For a multi-slot booking where one payment covers multiple slots, the entire payment sum is output on each slot row. A 3-slot booking of Rp 300.000 results in three lines showing Rp 300.000 each (totaling Rp 900.000 on spreadsheet sums).

### 3. CSV Formula Injection Vulnerability (CWE-1236, NFR-SEC-004)
Both `OwnerAnalyticsController::export` and `app/Actions/Schedule/ExportScheduleReport.php:84-92` output customer names, emails, and notes directly into `fputcsv()`. If a customer name begins with `=`, `+`, `-`, or `@`, spreadsheet applications like Microsoft Excel execute it as a formula upon opening the exported CSV.

### 4. Export works while the module is locked (added by the Tech Lead review, 2026-11-02)
Analytics and schedule report are Tier 2 `locked` modules. `EnsureModuleReleased` lets every safe (GET) request through for a locked module (`app/Http/Middleware/EnsureModuleReleased.php:27-28`), so `GET /owner/analytics/export` and `GET /owner/schedule-report/export` still download full CSV data although the UI shows the module as locked. The lock should stop data exports too (export answers 403 while locked), or the routes must check the module state themselves. Release-tier behaviour is a Tech Lead decision (ADR-0011).

## Plan

1. Replace 12-month and 30-day query loops in `OwnerAnalyticsController` with single aggregated queries using `selectRaw('DATE_FORMAT(...) as period, sum(...) as total, count(...) as cnt')->groupBy('period')`.
2. Apportion multi-slot payment totals per slot row or output the slot's proportional charge rather than full group payment amount.
3. Sanitize CSV export fields: prefix any string beginning with `=`, `+`, `-`, or `@` with a single quote or tab to neutralize formula execution.

## Invariants at risk

None. Analytics and reports remain strictly read-only aggregations.

## Tests

- Performance test / query count: loading analytics executes fewer than 10 database queries total.
- Export test: customer names starting with `=`, `+`, `-`, or `@` are escaped in exported CSV output.
- Export test: multi-slot booking rows reflect per-slot price rather than multiplying group total.

## Docs to update

`docs/domains/insights.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
