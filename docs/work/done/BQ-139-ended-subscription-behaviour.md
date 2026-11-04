---
id: BQ-139
title: Ended trial or subscription: what keeps working, renewal rules and reminders
type: feature
status: done
priority: P1
phase: 4
domain: subscription
requirements: [FR-SUB-012, FR-SUB-013, FR-PUBLIC-008, FR-NOTIFICATION-007]
invariants: [INV-SUB-01]
lane: heavy
approval: tech-lead
decision: D-002
launch-blocker: true
depends-on: [BQ-086, BQ-130]
---

# BQ-139: Ended trial or subscription: what keeps working, renewal rules and reminders

Epic **E3 · Approved decisions D-001, D-002, D-006** of the owner portal redesign, planned in [BQ-126](../BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Nothing defines what an ended subscription may do; renewing an active plan loses paid days; reminders are not sent.

## Plan

1. Refuse every create route listed in `owner-portal.md` §12 when the subscription has ended; keep the listed pages working.
2. Storefront refuses checkout with the neutral message.
3. Renewal extends from the current end date; plan change starts on payment; one trial per owner account.
4. Reminders 3 days and 1 day before and on the end date.

## Scope

- **Acceptance criteria owned:** AC-END-01, AC-END-02, AC-PKT-02.
- **New requirements owned:** FR-SUB-012, FR-SUB-013, FR-PUBLIC-008, FR-NOTIFICATION-007.
- **ADRs:** ADR-0021.

## Invariants at risk

INV-SUB-01.

## Tests

- AC-END-01, AC-END-02, AC-PKT-02.
- Reminder scheduling with frozen time.

## Docs to update

- `docs/domains/subscription.md`

## Evidence (fill when done)

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

AC-END-01, AC-END-02 and AC-PKT-02 covered by EndedSubscriptionTest. SubscriptionActivationLockingTest verifies the tenant lock with a separate MySQL connection.
SQLite: 703 passed (4023 assertions). MySQL: 13 passed (48 assertions). Pint and PHPStan passed; three obsolete baseline entries removed.

Merged into `integration` through PR #39 (merge commit `0f92e68`, 2026-11-04); four CI jobs green on `f03cb61`.
