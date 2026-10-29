---
id: BQ-028
title: Phase 2: architecture decision package
type: docs
status: in-progress
priority: P0
phase: 2
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-028: Phase 2: architecture decision package

## Problem

Several fixes need a design decision first (`docs/work/ROADMAP.md`, Phase 2).

## Plan

Lead runs `/improve-codebase-architecture`; the agent drafts ADR-0004 … ADR-0013: authorization model and scope bypasses; single availability authority; customer access and public identifiers; payment lifecycle and reconciliation; Tier gating; module boundaries; async notifications on shared hosting; testing strategy; legacy naming; environments and release. The lead decides in one session; the backlog for Phases 4 and 6 is re-sequenced.

## Progress

- 2026-10-30: `/improve-codebase-architecture` run on the booking-journey and payment hot spots; report with 6 candidates (Payment lifecycle, Slot bookability, Management link, Provider outcome intake, Booking checkout, Pending-payment lifetime) and 5 bugs (BQ-068, BQ-069, BQ-070, BQ-047, BQ-053).
- 2026-10-30: candidate #1 decided → ADR-0004 (Payment lifecycle module), implementation BQ-067.
- 2026-10-30: candidate #2 decided → [ADR-0005](../adr/ADR-0005-slot-bookability-module.md) (Slot bookability module), implementation BQ-012 and blocked-date acceptance BQ-069. Lead confirmed the complete contract after three grilling rounds: shared evaluation/claim, caller-owned outer transaction, tenant-first synchronization, lifecycle-owned group expiry, batch inputs/cache ownership, and preservation of existing reservations on date closure. Dependency order: BQ-023 → BQ-067 → BQ-012 → BQ-069.
- 2026-10-30: candidate #3 decided → [ADR-0006](../adr/ADR-0006-management-link-module.md) (Management link module). Lead accepted the module and separate Checkout profile, then explicitly delegated all remaining candidate #3 choices without further grilling. Selected explicit Management authority plus narrow profiles, hashed opaque grants, canonical fragment-to-session entry, finite expiry/recorded-recipient renewal, revocation generations for grants and delivery, and prelaunch invalidation of exposed legacy credentials. Requirements FR-MANAGE-008…011 accepted; implementation BQ-001, scope/share acceptance BQ-053/BQ-052, payer-cancel authorization BQ-002. Individual share-token leakage confirmed by source inspection; no runtime defect is marked fixed.
- Next candidate: Provider outcome intake (#4, → BQ-003, BQ-032). Booking checkout (#5) and Pending-payment lifetime (#6) follow the accepted payment, slot and customer-access contracts. The delegation above ends with candidate #3.
- G2 remains pending until the Phase 2 package is decided; no Phase 4 implementation starts from these ADR records alone.

## Evidence

_Fill in when done._
