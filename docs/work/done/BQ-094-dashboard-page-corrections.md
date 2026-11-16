---
id: BQ-094
title: Owner dashboard shows truncated, unsigned and misleading figures
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: insights
requirements: [FR-DASH-001, FR-DASH-002, FR-DASH-003, FR-DASH-004, FR-DASH-006, FR-DASH-007]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: [BQ-089, BQ-092]
---

# BQ-094: Owner dashboard shows truncated, unsigned and misleading figures

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-136](BQ-136-beranda-page.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-136.


Found in the owner UI audit of 2026-11-02 on the demo tenant (271 bookings).

## Problem

| # | Issue | Evidence |
|---|---|---|
| 1 | Revenue is truncated ("Rp 106.…") and "TOTAL CUSTOME…" at 1024 px; values that matter most are cut | stat cards use `truncate` on value and label (`components/owner/stat-card.blade.php:58-60`) |
| 2 | A decrease renders without a minus sign on first load ("30%" in red); after the 30 s refresh it becomes "-30%" | `stat-card.blade.php:28-31` uses `abs()`; the refresh script adds the sign (`dashboard.blade.php:491, 500`) |
| 3 | "Total Revenue" is all-time but the percentage compares this month with last month; neither label says so (release audit F3 asked the card to say it covers all history) | `app/Actions/Dashboard/GetOwnerDashboardOverview.php:51-70` |
| 4 | The customers card shows a fake green "↑ CRM" trend; the services card shows "Steady" | `dashboard.blade.php:85-90`, `stat-card.blade.php:30` |
| 5 | "Daily Trends" shows "+100% vs yesterday" for every service with one booking when yesterday had none | `dashboard.blade.php:168-174` |
| 6 | "Upcoming Schedule" is subtitled "Next confirmed client sessions" but includes `pending` bookings | `GetOwnerDashboardOverview.php:174-176` |
| 7 | Status labels differ between cards ("CONFIRMED" uppercase in recent bookings, "Paid" in upcoming) | `dashboard.blade.php:226, 274, 317` |
| 8 | Three links to the calendar on one screen (header button, "Open Calendar →", sidebar) and a date badge with today's date | `dashboard.blade.php:17-31, 297` |
| 9 | "View Full Report 🔒 Terkunci" links to a locked page | `dashboard.blade.php:186-189` |
| 10 | Revenue chart: x labels are month names without year; weekly view subtitle "Last 7 days daily earnings"; colours are hard-coded indigo | `dashboard.blade.php:346-457` |
| 11 | Recent bookings: desktop table and mobile list are both rendered and both rebuilt by the refresh (two copies of the same data) | `dashboard.blade.php:209-287` |
| 12 | Welcome heading uses the account full name ("Welcome back, Brama Digital Owner") in English | `dashboard.blade.php:11-14` |

## Plan

1. Stat cards: no truncation of values (compact format such as "Rp 106,4 jt" with the exact value in a tooltip or `title`), signed percentages from one formatter, explicit period in the label ("Pendapatan bulan ini" with the comparison "vs bulan lalu"; all-time totals labelled as such). Decide the four cards with the Product Lead only if the meaning changes; relabelling is light.
2. Remove fake indicators ("↑ CRM", "Steady"); show a comparison only where one exists.
3. Daily trends: "baru" or absolute counts when yesterday is 0.
4. Upcoming: either only `paid`, or keep pending with its status and fix the subtitle.
5. Status badges and labels from BQ-092.
6. One calendar link; drop the date badge; drop the locked report link or show it as locked consistently.
7. Chart from tokens; labels with year when the range crosses a year.
8. Indonesian copy (BQ-093).

## Invariants at risk

FR-DASH-007: figures must stay tenant-scoped and match the domain definitions (`paid`/`completed`, payment `sukses`).

## Tests

- Unit or view tests for the percentage formatter (positive, negative, zero, previous 0).
- Feature test: dashboard view contains the signed value for a decrease.
- Manual at 375 px and 1024 px.

## Docs to update

`docs/domains/insights.md` if card definitions change.

## Evidence (fill when done)

_Fill in when done._
