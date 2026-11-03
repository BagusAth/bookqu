---
id: BQ-154
title: Landing page and custom domain for Pro
type: feature
status: proposed
priority: P2
phase: 8
domain: public-site
requirements: [FR-PUBLIC-007]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-005, D-009, D-017
launch-blocker: true
depends-on: [BQ-142, BQ-130]
---

# BQ-154: Landing page and custom domain for Pro

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The landing builder and custom domain are hidden; there is no DNS check.

## Plan

1. Landing page and Domain sendiri sections of `owner-portal.md` §8.9.
2. DNS check job; custom-domain resolution stays as in ADR-0006.

## Scope

- **Acceptance criteria owned:** AC-HBK-03, AC-HBK-04.
- **New requirements owned:** FR-PUBLIC-007.
- **ADRs:** ADR-0006.

## Invariants at risk

INV-TEN-01: a domain resolves to exactly one tenant.

## Tests

- AC-HBK-03, AC-HBK-04.

## Docs to update

- `docs/domains/public-site.md`

## Evidence (fill when done)
