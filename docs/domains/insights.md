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

## Status

Functional: Implemented · Verification: Needs Verification (metric definitions not specified) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Calendar and schedule report count every `pending` booking as occupied, ignoring the 15-minute grace; analytics and schedule controller use their own "booked" status lists | BQ-012 |
| `OwnerAnalyticsController` holds aggregation queries directly | BQ-029 (pattern), BQ-012 |
| Dashboard view is 597 lines | BQ-034 |
| Audit P0/P1: deleting a slot from the calendar redirects out of it; week view hides free slots; empty AJAX error | BQ-046, BQ-062 |
