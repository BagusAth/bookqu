---
id: BQ-035
title: Clean up duplicate routes and main-domain detection
type: refactor
status: proposed
priority: P2
phase: 6
domain: tenancy
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-035: Clean up duplicate routes and main-domain detection

## Problem

Duplicate routes: `owner.programs*` vs `owner.services*`, `owner.settings.payment-setting` vs `owner.settings.payments`, `owner.settings` vs `owner.settings.business`. The main domain is defined twice (`app/Http/Middleware/TenantMiddleware.php:22` and the exclusion list in `routes/web.php`).

## Plan

Keep one route per action (with redirects where links exist) and one source for the main-domain list (config); tests for the storefront on both custom domain and slug.

## Evidence

_Fill in when done._
