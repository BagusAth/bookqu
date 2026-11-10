---
id: BQ-142
title: Halaman Booking: link, QR, promotion materials, appearance and media gallery
type: feature
status: in-progress
priority: P1
phase: 4
domain: public-site
requirements: [FR-PUBLIC-006, FR-ASSET-005]
invariants: [INV-TEN-01]
lane: light
approval: none
decision: D-009, D-010
launch-blocker: true
depends-on: [BQ-129, BQ-130]
---

# BQ-142: Halaman Booking: link, QR, promotion materials, appearance and media gallery

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Appearance and assets are hidden modules, so storefronts cannot be branded; there is no QR, poster or ready text.

## Plan

1. Build `owner-portal.md` §8.9 sections Link & QR, Materi promosi, Tampilan, Galeri media for all plans.
2. Gallery picker used by Layanan, Unit and the landing page; logo moves here from the profile request.
3. Old appearance and assets paths redirect.

## Scope

- **Acceptance criteria owned:** AC-HBK-01, AC-HBK-02, AC-HBK-05.
- **New requirements owned:** FR-PUBLIC-006, FR-ASSET-005.
- **ADRs:** none.

## Invariants at risk

INV-TEN-01 for media files.

## Tests

- AC-HBK-01, AC-HBK-02, AC-HBK-05.

## Docs to update

- `docs/domains/public-site.md`

## Evidence (fill when done)

## Implementation notes

- **Decisions (developer, 2026-11-10, on behalf of the Tech Lead):** the new dependency `bacon/bacon-qr-code` (^3.1, server-side SVG) is approved. Gallery wiring covers Layanan and Tampilan in this item.
- **Wave:** built ahead of wave 4 under the ROADMAP rule. It depends on BQ-129 and BQ-130.
- **Page:** `owner.booking-page` has the sections Link & QR, Materi promosi, Tampilan and Galeri media (`Actions/Tenant/GetBookingPageData`).
  - It stays behind the redesign module `booking-page`, which is `hidden` until the Tech Lead releases it (ADR-0021). Tests release it explicitly.
  - Landing page and own domain (Pro) are other items, and show nothing here.
- **AC-HBK-01:** `Support/StorefrontBranding` defines the button styles, card styles, fonts and colour format. The request validates them exactly; the storefront shell and payment page normalise stored values, with older values mapped. The business description now shows on the storefront's service page.
- **AC-HBK-02:** `Actions/Tenant/BuildBookingQr` encodes `CustomerBookingRoutes::url('customer.booking.program', slug)`. The QR download and the printable poster use it, so a new slug changes the QR.
- **AC-HBK-05:**
  - files go under `tenants/{id}/assets`;
  - logo, cover and service image ids are validated against the tenant;
  - deleting another business's image answers 404;
  - SVG uploads are refused.
- **Deleting an image in use:** it asks for confirmation (shared confirm dialog). Then `DeleteMediaAsset` clears the logo, cover and service references and deletes the file.
- **Layanan:** the service form picks from the gallery (`image_asset_id`), and a new upload is stored in the gallery (category `service`). Replacing an image removes only pre-gallery files, never gallery files (`ResolveServiceImage`).
- **Old paths:** `owner.settings.appearance` and `owner.settings.assets` answer 301 into the page. The old controllers, views and write routes are removed.
- **Follow-ups:**
  - category images still upload directly, not through the gallery;
  - the logo field of the business-profile request belongs to BQ-144's file (`OwnerSettingController`);
  - Unit and the landing page will use the picker when they are built.
- **Tests changed by the spec:**
  - `OwnerModulesCrudTest` uses the new gallery and appearance routes (card style `outline`);
  - `ReleaseGateAndHardeningTest` maps the old paths to `booking-page`;
  - `EndedSubscriptionTest` posts to the new gallery upload, which is still refused when the subscription has ended.
