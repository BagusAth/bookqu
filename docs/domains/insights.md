# Insights (dashboard, calendar, analytics, reports)

Read-only views that aggregate booking, payment and schedule data for the owner. They must never mutate domain state or define their own business rules.

## Entry points

| Route | Code | Release tier |
|---|---|---|
| `owner.dashboard` (Beranda), `owner.dashboard.polling`, `owner.dashboard.link-shared` | `Owner/OwnerDashboardController` → `Actions/Dashboard/GetBeranda` | Tier 1 |
| `owner.calendar` | `Owner/OwnerCalendarController` → `Actions/Calendar/GetOwnerCalendarData` | Tier 1 |
| `owner.analytics`, `owner.analytics.export` (`subscription:medium`) | `Owner/OwnerAnalyticsController` (queries inline in the controller) | Tier 2 (locked UI) |
| `owner.schedule-report`, `owner.schedule-report.export` | `Owner/OwnerScheduleReportController` → `Actions/Schedule/GenerateScheduleReport`, `ExportScheduleReport` | Tier 2 (locked UI) |

## Evidence

`tests/Feature/Owner/OwnerDashboardPollingTest.php`, `Owner/AnalyticsAndSubscriptionTest.php`, `Owner/FiveModulesFullIntegrationTest.php`, `Owner/OwnerSidebarNavigationTest.php` (locked banners).

BQ-089: the dashboard live refresh clones `<template>` rows and fills them with `textContent`; the polling JSON stays raw data (`OwnerDashboardPollingTest`). `ArchitectureRulesTest::test_views_never_build_inner_html_from_data` (rule `inner-html-data`) fails on any view that assigns non-literal markup to `innerHTML`.

BQ-111: `Actions/Dashboard/GetOwnerAnalyticsTrends` builds the 12-month revenue and booking series and the 30-day booking series from three grouped queries (period computed in a derived table for MariaDB's ONLY_FULL_GROUP_BY). The analytics CSV re-applies the tenant context inside the stream (`TenantMiddleware` clears it before a streamed body runs, which had left the export empty), writes a multi-session transaction's amount once with a `Keterangan Harga` note (no invented per-session amount, BQ-121), and both CSV exports pass every cell through `App\Support\CsvCell` (CWE-1236). Exports answer 404 while their module is hidden (ADR-0021). Evidence: `Owner/AnalyticsExportAndAggregationTest`.

BQ-136: Beranda (owner-portal.md §8.1, FR-DASH-008) replaces the old dashboard and its charts (AC-BRD-07); trends belong to Laporan (BQ-147). `GetBeranda` prepares:

- one banner by priority (AC-BRD-01): subscription ended, trial ending within 3 days, slot runway warning (`GetSlotRunway`), no payout account (`payouts.manage`), monthly bookings at 80% or more;
- today's figures. Waiting for payment uses `BookingRules::applyOccupiesSlotCondition`. Revenue is successful booking payments created today (AC-BRD-02), hidden without `revenue.view` (AC-BRD-03). Occupancy is today's slots holding an occupying booking;
- "Perlu tindakan" with counts and links (AC-BRD-04);
- today's schedule with "Sedang berlangsung" (`GetBookingTimeRanges`), tomorrow's summary and the next booking on an empty day;
- the booking link card and the role- and plan-aware quick actions;
- the new owner guide. Its steps come from data. `tenants.booking_link_shared_at` is recorded by `owner.dashboard.link-shared`, and `tenants.owner_guide_completed_at` is set once all steps are true, so the guide never returns (AC-BRD-05).

Sections 2–4 refresh every 60 seconds only while the tab is visible (`ownerBeranda` in `public/js/owner-ui.js`) and render with `x-text` (AC-BRD-06). Evidence: `Owner/BerandaPageTest`, `Owner/OwnerDashboardPollingTest`.

## Status

Functional: Implemented · Verification: Needs Verification (metric definitions not specified) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Calendar and schedule report count every `pending` booking as occupied, ignoring the 15-minute grace; analytics and schedule controller use their own "booked" status lists | BQ-012 |
| `OwnerAnalyticsController` still holds the summary, top-service and status queries (the monthly and daily series moved to `GetOwnerAnalyticsTrends`, BQ-111) | BQ-029 (pattern), BQ-012 |
| Audit P0/P1: deleting a slot from the calendar redirects out of it; week view hides free slots; empty AJAX error | BQ-046, BQ-062 |
| Owner dialog and drawer accessibility gaps (missing role="dialog", focus trapping, and keyboard escape handling) | BQ-129 |
