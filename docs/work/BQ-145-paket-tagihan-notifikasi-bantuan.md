---
id: BQ-145
title: Paket & Tagihan, notification centre and Bantuan
type: feature
status: proposed
priority: P2
phase: 8
domain: subscription
requirements: [FR-HELP-001]
invariants: []
lane: light
approval: none
decision: D-007, D-017
launch-blocker: true
depends-on: [BQ-129, BQ-130, BQ-131]
---

# BQ-145: Paket & Tagihan, notification centre and Bantuan

Epic **E4 · Trust and acquisition pages** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Subscription checkout has layout and terminology issues (BQ-104); mark-as-read redirects are not validated (BQ-102); there is no help page.

## Plan

1. Build `owner-portal.md` §8.15, §8.16, §8.17.
2. Carry every finding of the absorbed items.

## Scope

- **Acceptance criteria owned:** AC-PKT-03, AC-PKT-04, AC-NTF-01, AC-NTF-02, AC-BNT-01.
- **New requirements owned:** FR-HELP-001.
- **ADRs:** none.
- **Absorbs:** BQ-102, BQ-104 (closed as merged into this item; their findings are part of this scope).

## Invariants at risk

Notification redirects only to own-tenant targets.

## Tests

- AC-PKT-03, AC-PKT-04, AC-NTF-01, AC-NTF-02, AC-BNT-01.

## Docs to update

- `docs/domains/subscription.md`
- `docs/domains/notifications.md`

## Evidence (fill when done)
