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
- Next candidates: Slot bookability (→ BQ-012, BQ-069), Management link (→ BQ-001, BQ-053), Provider outcome intake (→ BQ-003, BQ-032).

## Evidence

_Fill in when done._
