---
id: BQ-156
title: API keys and signed webhooks for Pro
type: feature
status: approved
priority: P2
phase: 4
domain: tenancy
requirements: [FR-API-001, FR-API-002]
invariants: [INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-019, D-017
launch-blocker: true
depends-on: [BQ-155]
---

# BQ-156: API keys and signed webhooks for Pro

Epic **E6 · Pro value and core rules** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Pro has no API or webhooks.

## Plan

1. Read-only API with hashed keys, rate limits and tenant scope; webhooks with HMAC signatures, SSRF checks, retries and a delivery log (ADR-0022 decisions 5–6).

## Scope

- **Acceptance criteria owned:** none (infrastructure for other items).
- **New requirements owned:** FR-API-001, FR-API-002.
- **ADRs:** ADR-0022.

## Invariants at risk

INV-TEN-01.

## Tests

- Key scope and revocation, SSRF refusal, signature verification, retries.

## Docs to update

- `docs/domains/integrations.md`

## Approval (Tech Lead, 2026-11-13)

Approved to start after BQ-155, following ADR-0022 decisions 5-7.

1. **Schema:** hashed API keys, webhook endpoints with their own secret, and a delivery log.
2. **Limits:** API rate limit of 60 requests per minute per key; webhook retries up to 5 times with backoff, then the owner is notified; the delivery log is kept 30 days.
3. **Rules:** SSRF refusal at save and at delivery, HMAC-SHA256 signatures with a timestamp and an event id, revocable keys, tenant scope on every read (INV-TEN-01), Pro through `EntitlementRules`, module gate until released.

## Evidence (fill when done)
