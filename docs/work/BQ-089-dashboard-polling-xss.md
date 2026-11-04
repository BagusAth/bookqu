---
id: BQ-089
title: Dashboard live refresh renders customer names as HTML
type: bug-fix
status: in-progress
priority: P0
phase: 4
domain: insights
requirements: [NFR-SEC-004, FR-DASH-006]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

# BQ-089: Dashboard live refresh renders customer names as HTML

Found in the owner UI audit of 2026-11-02.

## Problem

Every 30 seconds the dashboard refreshes "Recent Bookings" from `owner.dashboard.polling` and rebuilds the table and the mobile list with template strings assigned to `innerHTML` (`resources/views/owner/dashboard.blade.php:520-580`). `item.customer_name` and `item.program_name` are inserted unescaped. The endpoint returns them raw (`app/Actions/Dashboard/GetOwnerDashboardOverview.php:274-290`).

`namapelanggan` is typed by any visitor on the public storefront and is only checked for length (`app/Http/Requests/Booking/CreateBookingRequest.php:23`). A booking named `<img src=x onerror=…>` runs script in the owner's session within 30 seconds of the owner opening the dashboard: stored XSS against an authenticated owner (NFR-SEC-004).

The first page render is safe (Blade escapes); only the refresh is affected.

## Plan

1. Build the refreshed rows with DOM nodes and `textContent` (or an Alpine `x-for` over the polled data), never `innerHTML` with data.
2. While there: render the same fields as the first render (the refreshed mobile card drops the time "WIB"; status labels come from BQ-092's status badge).
3. Search the owner views for any other `innerHTML` built from server data (the audit found only this file) and add a guardrail so it does not come back (for example an architecture test that fails on `innerHTML +=` / `innerHTML =` with template literals in `resources/views/owner`).

## Invariants at risk

None of the domain invariants; the change is output escaping only.

## Tests

- Feature test: a booking whose customer name contains HTML; the polling JSON stays raw data, and the dashboard view contains no `innerHTML` template built from it (guardrail test).
- Manual: create a storefront booking with `<b>x</b>` as the name, keep the dashboard open 30 s, the name shows as text.

## Docs to update

`docs/domains/insights.md` known gaps (remove the row when done).

## Evidence (fill when done)

_Fill in when done._
