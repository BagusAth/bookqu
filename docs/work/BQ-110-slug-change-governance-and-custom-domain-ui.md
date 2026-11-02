---
id: BQ-110
title: Business slug change redirect governance and custom domain settings UI
type: improvement
status: proposed
priority: P1
phase: 8
domain: tenancy
requirements: [FR-TENANT-004, FR-TENANT-005, FR-TENANT-007, NFR-UX-002]
invariants: [INV-TEN-01, INV-TEN-02]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

# BQ-110: Business slug change redirect governance and custom domain settings UI

Found during deep audit of business profile updates and custom domain routing on 2026-11-02.

## Problem

### 1. Business Name Update Silently Breaks Existing Public Links
In `app/Actions/Tenant/UpdateBusinessProfile.php:30-51`:
- When an owner updates their business name (`namabisnis`), the action automatically recalculates the tenant slug: `$slug = Str::slug($namabisnis)`.
- If the name changes (e.g. from "Yoga Haven" to "Yoga Haven Studio"), the slug changes from `/yoga-haven` to `/yoga-haven-studio`.
- **There is no redirect or history preservation for the previous slug.**
- Any customer bookmark, shared social media link, or printed QR code using the old slug immediately returns HTTP 404.
- In addition, `Cache::forget("tenant:slug:{$tenant->slug}")` at line 85 runs after updating the model, leaving the old slug cached in memory.

### 2. Missing Custom Domain Configuration Interface in Settings (FR-TENANT-007)
Requirement FR-TENANT-007 mandates custom domain support. However:
- The only form handling `custom_domain` input is inside `OwnerLandingPageController` under the Tier 3 `module:landing-page` route, which answers 404 by default.
- The standard settings page (`/owner/settings`) and its request `UpdateBusinessProfileRequest` contain no input for `custom_domain`. Owners in v1 have no way to configure their custom domain.

## Plan

1. Provide an explicit warning or confirmation in `/owner/settings` informing the owner that changing their business name alters their public storefront URL.
2. Store former slugs in a `tenant_slug_histories` table (or similar redirect mechanism) so that incoming customer requests to former slugs issue a 301 permanent redirect to the current slug.
3. In `UpdateBusinessProfile`, clear cache for the old slug (`$oldSlug`) prior to model update.
4. Add a `custom_domain` input field to the Business Profile section in `/owner/settings` with validation rules in `UpdateBusinessProfileRequest`.

## Invariants at risk

`INV-TEN-01` and `INV-TEN-02`: Tenant isolation and context resolution must remain fail-closed on both current and historical slug routes.

## Tests

- Feature test: renaming a business flushes the old slug cache and leaves the storefront accessible.
- Feature test: setting a valid custom domain via `/owner/settings` saves correctly and binds to the tenant.
- Routing test: requests to former slugs redirect cleanly to the new slug.

## Docs to update

`docs/domains/tenancy.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
