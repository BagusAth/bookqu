---
id: BQ-110
title: Renaming the business silently changes the slug and breaks old storefront links
type: improvement
status: rejected
priority: P1
phase: 8
domain: tenancy
requirements: [FR-TENANT-004, FR-TENANT-005, NFR-UX-002]
invariants: [INV-TEN-01, INV-TEN-02]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: []
---

# BQ-110: Renaming the business silently changes the slug and breaks old storefront links

> **Ditutup 2026-11-04 (BQ-126 Fase 9): dilebur ke [BQ-144](../BQ-144-pengaturan-profile-policy-notifications-account.md).** Semua temuan di item ini menjadi bagian dari lingkup BQ-144.


Found during deep audit of business profile updates and custom domain routing on 2026-11-02. Corrected by the Tech Lead review on 2026-11-02: the missing custom-domain settings UI is a product question (custom domains live in the hidden landing-page module) and moved to D-005 in `docs/product-decisions.md`.

## Problem

### 1. Business Name Update Silently Breaks Existing Public Links
In `app/Actions/Tenant/UpdateBusinessProfile.php:30-51`:
- When an owner updates their business name (`namabisnis`), the action automatically recalculates the tenant slug: `$slug = Str::slug($namabisnis)`.
- If the name changes (e.g. from "Yoga Haven" to "Yoga Haven Studio"), the slug changes from `/yoga-haven` to `/yoga-haven-studio`.
- **There is no redirect or history preservation for the previous slug.**
- Any customer bookmark, shared social media link, or printed QR code using the old slug immediately returns HTTP 404.
- In addition, `Cache::forget("tenant:slug:{$tenant->slug}")` (line 84) runs after the update, so it forgets the **new** slug and leaves the old slug's cache entry until it expires.

## Plan

1. Provide an explicit warning or confirmation in `/owner/settings` informing the owner that changing their business name alters their public storefront URL.
2. Store former slugs in a `tenant_slug_histories` table (or similar redirect mechanism) so that incoming customer requests to former slugs issue a 301 permanent redirect to the current slug.
3. In `UpdateBusinessProfile`, clear cache for the old slug (`$oldSlug`) prior to model update.

## Invariants at risk

`INV-TEN-01` and `INV-TEN-02`: Tenant isolation and context resolution must remain fail-closed on both current and historical slug routes.

## Tests

- Feature test: renaming a business flushes the old slug cache and leaves the storefront accessible.
- Routing test: requests to former slugs redirect cleanly to the new slug.

## Docs to update

`docs/domains/tenancy.md` (Known gaps).

## Evidence (fill when done)

_Fill in when done._
