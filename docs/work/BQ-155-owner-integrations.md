---
id: BQ-155
title: Pengaturan → Integrasi: Google Calendar, Google Analytics, Meta Pixel
type: feature
status: proposed
priority: P2
phase: 4
domain: tenancy
requirements: [FR-INTEGRATION-001, FR-INTEGRATION-002]
invariants: []
lane: heavy
approval: tech-lead
decision: D-019
launch-blocker: true
depends-on: [BQ-130, BQ-144]
---

# BQ-155: Pengaturan → Integrasi: Google Calendar, Google Analytics, Meta Pixel

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

The integrations page only shows "coming soon" cards.

## Plan

1. Integration seam and Google Calendar adapter per ADR-0022; measurement ids for the storefront.

## Scope

- **Acceptance criteria owned:** AC-SET-07.
- **New requirements owned:** FR-INTEGRATION-001, FR-INTEGRATION-002.
- **ADRs:** ADR-0022.

## Invariants at risk

Secrets encrypted and never logged; external calls after commit.

## Tests

- AC-SET-07.
- Adapter fakes; booking succeeds when the integration fails.

## Docs to update

- new `docs/domains/integrations.md`

## Evidence (fill when done)
