---
id: BQ-132
title: Layanan page with tabs Layanan and Kategori
type: feature
status: in-progress
priority: P1
phase: 4
domain: catalog
requirements: [FR-CATEGORY-006]
invariants: [INV-SUB-01]
lane: light
approval: none
decision: D-007
launch-blocker: true
depends-on: [BQ-129, BQ-114]
---

# BQ-132: Layanan page with tabs Layanan and Kategori

Epic **E2 · Core loop pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Services and categories live on two pages with broken links and native alerts (BQ-099); price unit, duration unit, popular flag and category colour, image and order have no form (inventory YATIM-2 to YATIM-4).

## Plan

1. Build `owner-portal.md` §8.6 tabs Layanan and Kategori; `owner.programs.*` redirects to `owner.services.*`.
2. Service fields of §8.6 except units, capacity and add-ons (BQ-151, BQ-152, BQ-150); category colour, image and display order respected by the storefront.
3. Deactivate instead of delete when history exists; usage meter against the plan limit.
4. Carry every finding of BQ-099.

## Scope

- **Acceptance criteria owned:** AC-LYN-01, AC-LYN-02, AC-LYN-03.
- **New requirements owned:** FR-CATEGORY-006.
- **ADRs:** none.
- **Absorbs:** BQ-099 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

INV-SUB-01 for the service limit; existing bookings keep their times and amounts (BQ-114).

## Tests

- AC-LYN-01 to AC-LYN-03.
- Category order on the storefront.

## Docs to update

- `docs/domains/catalog.md`

## Evidence (fill when done)

## Implementation notes

- **Schema (approved by the Tech Lead through the developer, 2026-11-09):** expand-only migration adding `categories.image_url` (nullable string) and `categories.sort_order` (unsigned int, default 0) with index `(idtenant, sort_order)` for FR-CATEGORY-006.
- **Routes:** `GET /owner/programs` answers 301 to `owner.services` with the query kept. The `owner.programs.*` write aliases keep working, and their redirect then lands on Layanan. `GET /owner/categories` renders the same page on its Kategori tab.
- **AC-LYN-02:** `DeleteService` now refuses any booking history, cancelled bookings included (it used to check only pending, paid and completed). The `slot-occupancy` allowlist entry for `DeleteService` is removed.
- **Category delete:** refused while a service uses the category (§8.6 "delete when empty"). Before, it unlinked the services. `ServicesDomainEndToEndTest` step 5 now asserts the refusal, and the category and the service link both stay.
- **Units:** the form has no unit field (BQ-152). It sends the service's current `resource_ids` back unchanged, because BQ-045 requires that a save without them detaches every unit.
- **Images:** uploaded directly. The gallery picker waits until a gallery page exists.
- **BQ-099 findings carried:**
  - card actions are always visible;
  - no native alerts (the shared confirm dialog and toast);
  - routes go through `@js` templates;
  - "Lihat layanan" uses the `category` filter.
