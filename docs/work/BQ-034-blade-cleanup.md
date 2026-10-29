---
id: BQ-034
title: Remove queries from Blade and decompose large views
type: refactor
status: proposed
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

_Fill in when done._
