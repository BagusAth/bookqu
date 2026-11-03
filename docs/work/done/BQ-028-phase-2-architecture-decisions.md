---
id: BQ-028
title: Phase 2: architecture decision package
type: docs
status: done
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
- 2026-10-30: candidate #2 decided → [ADR-0005](../../adr/ADR-0005-slot-bookability-module.md) (Slot bookability module), implementation BQ-012 and blocked-date acceptance BQ-069. Lead confirmed the complete contract after three grilling rounds: shared evaluation/claim, caller-owned outer transaction, tenant-first synchronization, lifecycle-owned group expiry, batch inputs/cache ownership, and preservation of existing reservations on date closure. Dependency order: BQ-023 → BQ-067 → BQ-012 → BQ-069.
- 2026-10-30: candidate #3 decided → [ADR-0006](../../adr/ADR-0006-management-link-module.md) (Management link module). Lead accepted the module and separate Checkout profile, then explicitly delegated all remaining candidate #3 choices without further grilling. Selected explicit Management authority plus narrow profiles, hashed opaque grants, canonical fragment-to-session entry, finite expiry/recorded-recipient renewal, revocation generations for grants and delivery, and prelaunch invalidation of exposed legacy credentials. Requirements FR-MANAGE-008…011 accepted; implementation BQ-001, scope/share acceptance BQ-053/BQ-052, payer-cancel authorization BQ-002. Individual share-token leakage confirmed by source inspection; no runtime defect is marked fixed.
- 2026-10-30: audit of the ADR-0005/0006 commits (docs only, links/IDs clean, code claims confirmed). Fixed: BQ-067 now follows ADR-0005's tenant-first lock; BR-PAY-001 accepted and moved to the core business rules; architecture §5 points availability to the Slot bookability module.
- 2026-10-30: candidate #4 decided after one grilling round (lead accepted Q1–Q8) → [ADR-0007](../../adr/ADR-0007-provider-outcome-intake.md); implementation BQ-032 (rescoped, Phase 4, launch blocker).
- 2026-10-30: the lead then delegated every remaining Phase 2 decision ("saya akan menerima semua masukan dan rekomendasi dari anda … lanjutkan hingga tahapan 2 ini selesai"). Decided on that basis: [ADR-0008](../../adr/ADR-0008-booking-checkout-draft.md) checkout draft (BQ-029), [ADR-0009](../../adr/ADR-0009-pending-payment-lifetime.md) pending-payment lifetime (new BQ-071), [ADR-0010](../../adr/ADR-0010-authorization-and-tenant-scoping.md) authorization and tenant scoping, [ADR-0011](../../adr/ADR-0011-server-side-release-gating.md) release gating, [ADR-0012](../../adr/ADR-0012-module-boundaries-and-naming.md) module boundaries and naming (BQ-036 rejected), [ADR-0013](../../adr/ADR-0013-background-work-on-shared-hosting.md) background work, [ADR-0014](../../adr/ADR-0014-testing-strategy.md) testing strategy, [ADR-0015](../../adr/ADR-0015-environments-and-release.md) environments and release.
- G2 passed 2026-10-30 under that delegation. Phase 4/6 order re-sequenced in `ROADMAP.md`. No runtime behaviour changed; every target remains unimplemented until its item closes with evidence.

## Evidence

- ADR-0004 … ADR-0015 accepted (`docs/adr/README.md`); `docs/architecture.md`, `docs/glossary.md`, `docs/requirements.md` (FR-MANAGE-008…011, BR-PAY-001), domain files, `docs/operations.md`, `docs/workflow.md` and the affected BQ items updated.
- Documentation check script: links, paths and IDs clean except planned future paths; full test suite run unchanged (docs-only change).
