---
id: BQ-147
title: Laporan: summary, revenue, occupancy, services and customers
type: feature
status: in-progress
priority: P1
phase: 4
domain: insights
requirements: [FR-ANALYTICS-007]
invariants: [INV-SUB-01]
lane: light
approval: none
decision: D-007, D-013, D-017
launch-blocker: true
depends-on: [BQ-111, BQ-130]
---

# BQ-147: Laporan: summary, revenue, occupancy, services and customers

Epic **E5 · Medium value** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Analytics is a locked placeholder and the schedule report is a separate locked page.

## Plan

1. Build `owner-portal.md` §8.13 with one period filter and five tabs; `/owner/schedule-report` redirects.
2. Exports per tab, formula-safe, Medium and up.

## Scope

- **Acceptance criteria owned:** AC-LAP-01, AC-LAP-02, AC-LAP-03, AC-LAP-04.
- **New requirements owned:** FR-ANALYTICS-007.
- **ADRs:** none.

## Invariants at risk

Figures from payments and bookings; INV-SUB-01 for the plan gate.

## Tests

- AC-LAP-01 to AC-LAP-04.

## Docs to update

- `docs/domains/insights.md`

## Evidence (fill when done)

## Implementation notes

- **Wave:** built ahead under the ROADMAP rule. Its dependencies BQ-111 and BQ-130 are done, and it touches no file of Developer A's open items.
- **Code:** `Actions/Report/GetReport` holds every figure and its definition; `ExportReport` writes the CSV of one tab; `Support/ReportPeriod` is the one period filter. `OwnerAnalyticsController` is thin. `GetOwnerAnalyticsTrends`, `GenerateScheduleReport`, `ExportScheduleReport`, `OwnerScheduleReportController` and the schedule report view are removed, with their baseline entries.
- **Gates:** the page and its export sit behind the redesign module `reports`, which the Laporan menu already used (ADR-0021); `analytics` and `schedule-report` in `config/modules.php` are no longer used by any route and are left for the Tech Lead to remove. The page itself has no plan middleware, so Small sees the upgrade prompt over a sample without data; the export keeps `subscription:analytics` (AC-LAP-03). `reports.view` keeps Kasir out.
- **AC-LAP-01:** revenue is successful booking payments created in the period minus refunds recorded in the period on successful booking payments. A refund of a double payment (`cause = double-payment`) is left out because that money was never revenue; a refund on a failed payment never counts.
- **AC-LAP-02:** slots of one checkout share a payment; bookings are counted by that payment (a booking without payment counts by itself), and the Pendapatan export lists one row per payment with its slot count.
- **AC-LAP-04:** the summary is computed once per request from the same queries for every tab; the per-tab figures (net revenue, bookings per service, new customers) add up to it. Tested across all five tabs.
- **Waiting for other items:** no-show rate (shown once the `no-show` module is released, BQ-153), most used unit and utilisation per unit (BQ-152), promo usage (BQ-149).
- **Charts:** CSS bars and a heat-map table; no chart library.
- **Found on MySQL:** a negative group key on the unsigned `bookings.id` overflowed; the key is now a string (`b<id>` / `p<idpayment>`).
- **Tests moved to the new contract (same intent):** `AnalyticsExportAndAggregationTest` (series, formula safety, one row per payment, own tenant, hidden module), `AnalyticsAndSubscriptionTest`, `CoreFlowIntegrationTest` (schedule report → Okupansi), `FiveModulesFullIntegrationTest`, `OwnerPortalFunctionalIntegrationTest`, `SubscriptionActionTest`, `SubscriptionRulesAndMechanismsTest` (Small and an ended trial now see the preview; the export is the refused data route), `ReleaseGateAndHardeningTest` (module map).
