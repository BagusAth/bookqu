# Insights (dashboard, calendar, analytics, reports)

Read-only views that aggregate booking, payment and schedule data for the owner. They must never mutate domain state or define their own business rules.

## Entry points

| Route | Code | Release tier |
|---|---|---|
| `owner.dashboard`, `owner.dashboard.polling` | `Owner/OwnerDashboardController` → `Actions/Dashboard/GetOwnerDashboardOverview` | Tier 1 |
| `owner.calendar` | `Owner/OwnerCalendarController` → `Actions/Calendar/GetOwnerCalendarData` | Tier 1 |
| `owner.analytics`, `owner.analytics.export` (`subscription:medium`) | `Owner/OwnerAnalyticsController` (queries inline in the controller) | Tier 2 (locked UI) |
| `owner.schedule-report`, `owner.schedule-report.export` | `Owner/OwnerScheduleReportController` → `Actions/Schedule/GenerateScheduleReport`, `ExportScheduleReport` | Tier 2 (locked UI) |

## Evidence

`tests/Feature/Owner/OwnerDashboardPollingTest.php`, `Owner/AnalyticsAndSubscriptionTest.php`, `Owner/FiveModulesFullIntegrationTest.php`, `Owner/OwnerSidebarNavigationTest.php` (locked banners).

BQ-089: the dashboard live refresh clones `<template>` rows and fills them with `textContent`; the polling JSON stays raw data (`OwnerDashboardPollingTest`). `ArchitectureRulesTest::test_views_never_build_inner_html_from_data` (rule `inner-html-data`) fails on any view that assigns non-literal markup to `innerHTML`.

BQ-111: `Actions/Dashboard/GetOwnerAnalyticsTrends` builds the 12-month revenue and booking series and the 30-day booking series from three grouped queries (period computed in a derived table for MariaDB's ONLY_FULL_GROUP_BY). The analytics CSV re-applies the tenant context inside the stream (`TenantMiddleware` clears it before a streamed body runs, which had left the export empty), writes a multi-session transaction's amount once with a `Keterangan Harga` note (no invented per-session amount, BQ-121), and both CSV exports pass every cell through `App\Support\CsvCell` (CWE-1236). Exports answer 404 while their module is hidden (ADR-0021). Evidence: `Owner/AnalyticsExportAndAggregationTest`.

## Status

Functional: Implemented · Verification: Needs Verification (metric definitions not specified) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Calendar and schedule report count every `pending` booking as occupied, ignoring the 15-minute grace; analytics and schedule controller use their own "booked" status lists | BQ-012 |
| `OwnerAnalyticsController` still holds the summary, top-service and status queries (the monthly and daily series moved to `GetOwnerAnalyticsTrends`, BQ-111) | BQ-029 (pattern), BQ-012 |
| Dashboard view is 597 lines | BQ-034 |
| Dashboard figures truncated, unsigned or mislabelled; fake trend indicators | BQ-136 |
| Audit P0/P1: deleting a slot from the calendar redirects out of it; week view hides free slots; empty AJAX error | BQ-046, BQ-062 |
| Owner dialog and drawer accessibility gaps (missing role="dialog", focus trapping, and keyboard escape handling) | BQ-129 |
