---
id: BQ-142
title: Halaman Booking: link, QR, promotion materials, appearance and media gallery
type: feature
status: proposed
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
