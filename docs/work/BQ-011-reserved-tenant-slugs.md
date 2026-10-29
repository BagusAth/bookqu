---
id: BQ-011
title: Prevent tenant slugs that collide with application routes
type: bug-fix
status: proposed
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

_Fill in when done._
