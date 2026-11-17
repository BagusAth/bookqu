# Insights (dashboard, calendar, analytics, reports)

Read-only views that aggregate booking, payment and schedule data for the owner. They must never mutate domain state or define their own business rules.

## Entry points

| Route | Code | Release tier |
|---|---|---|
| `owner.dashboard` (Beranda), `owner.dashboard.polling`, `owner.dashboard.link-shared` | `Owner/OwnerDashboardController` → `Actions/Dashboard/GetBeranda` | Tier 1 |
| `owner.calendar` | `Owner/OwnerCalendarController` → `Actions/Calendar/GetOwnerCalendarData` | Tier 1 |
| `owner.analytics` (Laporan), `owner.analytics.export` (`subscription:analytics`) | `Owner/OwnerAnalyticsController` → `Actions/Report/GetReport`, `ExportReport`, period `Support/ReportPeriod` | Redesign module `reports` (hidden until released, ADR-0021) |
| `owner.schedule-report`, `owner.schedule-report.export` | 301 to Laporan → Okupansi and its export | Module `reports` |

## Evidence

`tests/Feature/Owner/OwnerDashboardPollingTest.php`, `Owner/AnalyticsAndSubscriptionTest.php`, `Owner/FiveModulesFullIntegrationTest.php`, `Owner/OwnerSidebarNavigationTest.php` (locked banners), `Owner/LaporanPageTest.php`.

BQ-089: the dashboard live refresh clones `<template>` rows and fills them with `textContent`; the polling JSON stays raw data (`OwnerDashboardPollingTest`). `ArchitectureRulesTest::test_views_never_build_inner_html_from_data` (rule `inner-html-data`) fails on any view that assigns non-literal markup to `innerHTML`.

BQ-111: the analytics series come from grouped queries (period computed in a derived table for MariaDB's ONLY_FULL_GROUP_BY); since BQ-147 they are part of `GetReport`. The analytics CSV re-applies the tenant context inside the stream (`TenantMiddleware` clears it before a streamed body runs, which had left the export empty), writes a multi-session transaction's amount once with a `Keterangan Harga` note (no invented per-session amount, BQ-121), and both CSV exports pass every cell through `App\Support\CsvCell` (CWE-1236). Exports answer 404 while their module is hidden (ADR-0021). Evidence: `Owner/AnalyticsExportAndAggregationTest`.

BQ-136: Beranda (owner-portal.md §8.1, FR-DASH-008) replaces the old dashboard and its charts (AC-BRD-07); trends belong to Laporan (BQ-147). `GetBeranda` prepares:

- one banner by priority (AC-BRD-01): subscription ended, trial ending within 3 days, slot runway warning (`GetSlotRunway`), no payout account (`payouts.manage`), monthly bookings at 80% or more;
- today's figures. Waiting for payment uses `BookingRules::applyOccupiesSlotCondition`. Revenue is successful booking payments created today (AC-BRD-02), hidden without `revenue.view` (AC-BRD-03). Occupancy is today's slots holding an occupying booking;
- "Perlu tindakan" with counts and links (AC-BRD-04);
- today's schedule with "Sedang berlangsung" (`GetBookingTimeRanges`), tomorrow's summary and the next booking on an empty day;
- the booking link card and the role- and plan-aware quick actions;
- the new owner guide. Its steps come from data. `tenants.booking_link_shared_at` is recorded by `owner.dashboard.link-shared`, and `tenants.owner_guide_completed_at` is set once all steps are true, so the guide never returns (AC-BRD-05).

Sections 2–4 refresh every 60 seconds only while the tab is visible (`ownerBeranda` in `public/js/owner-ui.js`) and render with `x-text` (AC-BRD-06). Evidence: `Owner/BerandaPageTest`, `Owner/OwnerDashboardPollingTest`.

BQ-147: Laporan (owner-portal.md §8.13, FR-ANALYTICS-007) replaces the analytics page and the schedule report. Pemilik and Manajer (`reports.view`); Small, an ended plan or no plan sees the upgrade prompt over a sample, and no figure of the business is read (AC-LAP-03). One period filter (`ReportPeriod`: this month, last month, the last 3, 6 or 12 months, or a custom range of at most 366 days) applies to every tab and the export. `GetReport` holds the definitions, computed once per request so a period shows the same figures on every tab (AC-LAP-04):

- **Booking:** booking rows by service date; the slots of one checkout share a payment and count once (AC-LAP-02).
- **Revenue:** successful booking payments created in the period minus refunds recorded in the period on successful booking payments (AC-LAP-01). Refunds of a double payment are left out (that money was never revenue). Walk-in is `provider = manual`, everything else online. Per service through the payment's booking.
- **Customers:** `CustomerIdentity`; new when the first booking was made in the period.
- **Occupancy:** participants holding seats (BookingRules::seatsTakenSql) divided by total slot capacity, with a separate free-slot count and heat map of occupied slots. Pro unit utilisation compares occupied minutes with scheduled minutes across the services currently assigned to each active unit. Overlapping intervals count once.
- **Promo usage:** successful booking payments in the period contribute one use and their frozen discount to the customer and code. Pending reservations are reflected after payment succeeds.
- **Previous period (BQ-162):** the preceding calendar month or block of months for presets, or the immediately preceding range of the same length for custom dates. Conclusions come from the figures in `GetReport`.
- **Quietest day and hour (BQ-162, D-026):** on Okupansi, the day and the hour with the lowest occupancy among days that had slots, with a link that opens a new promo for them (FR-VOUCHER-006).
- **Days booked in advance:** the average calendar days from booking creation to the session date, counted once per checkout, using sessions in the selected period.
- **Belum jadi membayar (BQ-162, C-11):** Pelanggan shows system-closed booking payments (`gagal` and the “Pembayaran ditutup” log) for sessions in the period, newest bookings first. Owner/manager cancellations and customers with a later-created `paid` or `completed` booking in the same tenant are excluded. Identity follows `CustomerIdentity`. The WhatsApp link opens the owner's app, with no automated message.

Charts are CSS bars and a heat-map table, so no chart library loads. Each tab exports its own CSV (`ExportReport`): formula-safe cells, one row per payment in the Pendapatan transactions, tenant context restored inside the stream, refused below Medium by `subscription:analytics`. The no-show rate, unit metrics and promo usage follow their respective module release gates. Evidence: Owner/LaporanPageTest, Owner/AnalyticsExportAndAggregationTest.

## Status

Functional: Implemented · Verification: Needs Verification (metric definitions not specified) · Architecture: Needs Refactor

## Known gaps

| Gap | Item |
|---|---|
| Calendar counts every `pending` booking as occupied, ignoring the 15-minute grace (Laporan uses `BookingRules` since BQ-147) | BQ-012 |
| Audit P0/P1: deleting a slot from the calendar redirects out of it; week view hides free slots; empty AJAX error | BQ-046, BQ-062 |
| Owner dialog and drawer accessibility gaps (missing role="dialog", focus trapping, and keyboard escape handling) | BQ-129 |
