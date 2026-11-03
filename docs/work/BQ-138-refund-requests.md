---
id: BQ-138
title: Refund requests, owner decisions, business cancellation refunds and admin processing
type: feature
status: proposed
priority: P1
phase: 8
domain: payment
requirements: [FR-REFUND-001, FR-REFUND-002, FR-REFUND-003, FR-REFUND-004, FR-REFUND-005, FR-REFUND-006, FR-REFUND-007, FR-ADMIN-005]
invariants: [INV-PAY-03, INV-PAY-06, INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-001
launch-blocker: true
depends-on: [BQ-131, BQ-135, BQ-049]
---

# BQ-138: Refund requests, owner decisions, business cancellation refunds and admin processing

Epic **E3 · Approved decisions D-001, D-002, D-006** of the owner portal redesign, planned in [BQ-126](BQ-126-owner-portal-redesign-specification.md). Specification: [`owner-portal.md`](../owner-portal.md). Release policy D-018 makes every redesign item a launch blocker.

## Problem

Customer cancellation creates a refund nobody processes; owner cancellation refunds nothing; there is no request, decision or admin screen.

## Plan

1. Implement ADR-0020: `refund_requests`, customer request through the access link, owner decision 100%/50%/reject, full refund on business cancellation, Kasir cancellation request routed to FR-TEAM-006 once BQ-146 lands.
2. Payment lifecycle creates the refund with amount and cause; balance debit through `TenantBalanceLedger`.
3. Admin refund list and transitions; reminders for open requests.

## Scope

- **Acceptance criteria owned:** AC-PNL-02, AC-PNL-03.
- **New requirements owned:** FR-REFUND-001, FR-REFUND-002, FR-REFUND-003, FR-REFUND-004, FR-REFUND-005, FR-REFUND-006, FR-REFUND-007, FR-ADMIN-005.
- **ADRs:** ADR-0020, ADR-0004.

## Invariants at risk

INV-PAY-03 (idempotent), INV-PAY-06 (one refund per payment), INV-TEN-01.

## Tests

- Every case listed in ADR-0020 Consequences.
- AC-PNL-02, AC-PNL-03.

## Docs to update

- `docs/domains/payment.md`
- `docs/domains/booking.md`
- `docs/domains/balance-payout.md`
- `docs/domains/admin.md`

## Evidence (fill when done)
