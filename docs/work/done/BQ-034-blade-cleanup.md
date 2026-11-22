---
id: BQ-034
title: Remove queries from Blade and decompose large views
type: refactor
status: done
priority: P2
phase: 6
domain: insights
requirements: [NFR-MAINT-003]
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-034: Remove queries from Blade and decompose large views

## Problem

Queries in `components/owner/topbar.blade.php`, `components/landing/pricing.blade.php` and `owner/landing-page.blade.php`. Large views: `customer/manage/invoice` (803 lines), `components/owner/sidebar` (632), `owner/dashboard` (597), `customer/manage/reschedule` (517).

## Plan

Move data loading to controllers or view composers; extract repeated UI into components; no visual redesign.

## Evidence

### Closed (22 Nov 2026)

- No Blade view queries a model: `ArchitectureRulesTest::test_blade_views_do_not_query_models` passes with an empty `blade-queries` allowlist. The three views named in the problem now receive prepared data (`GetOwnerShell` for the top bar, `GetPublicPlanCards` for pricing; `owner/landing-page` was replaced by `owner/booking-page`).
- The large views were decomposed by the rebuilds: `customer/manage/invoice` (803 lines) is gone (one `customer/booking/invoice` view, BQ-164/165), `components/owner/sidebar` 632 → 77, `owner/dashboard` 597 → 274, `customer/manage/reschedule` 517 → 137. The largest remaining views are the legal pages and `owner/booking-page` (493).
- No separate PR: the work landed through BQ-161, BQ-162, BQ-164 and BQ-165.
