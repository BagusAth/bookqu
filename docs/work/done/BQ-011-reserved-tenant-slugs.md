---
id: BQ-011
title: Prevent tenant slugs that collide with application routes
type: bug-fix
status: done
priority: P2
phase: 4
domain: tenancy
requirements: [FR-TENANT-005]
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

# BQ-011: Prevent tenant slugs that collide with application routes

## Problem

Registration derives the slug from the business name (`RegisteredUserController::store`). A business called "Login", "Owner", "Admin", "Manage" or "API" gets a slug whose storefront `/{slug}` is shadowed by an application route.

## Plan

Keep a reserved-slug list (all first path segments in `routes/web.php` plus common words) and append a suffix on collision; test registration with a reserved name.

## Evidence

Done 2026-11-01. `App\Support\ReservedTenantSlugs` derives reserved words from every registered route's first segment plus a fixed list. Registration appends a suffix; profile creation and update refuse the name (replacing their old 4-word list). Test: `test_registration_never_takes_a_slug_shadowed_by_a_route`.
