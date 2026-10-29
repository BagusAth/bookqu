# Roadmap

From the current state to a production-ready, maintainable BookQu. Approved by the lead on 2026-10-30. Each phase ends at a gate; outside the gates agents work autonomously and record decisions in work items.

## Context (2026-10-30)

- Not launched to real customers. Every push to `main` deploys to `bookqu.my.id` without waiting for tests and without a database backup (`docs/operations.md` §3).
- Shared hosting (HostData) disables `proc_open` — constrains queue workers and scheduling.
- Two developers (lead: agathan, peer: Bagus) working with AI agents (Claude Code, Codex, Antigravity).
- 413 tests pass on SQLite; known security, correctness and structure gaps are listed in `docs/domains/*` → "Known gaps".

## Phases

| Phase | Goal | Done by | Gate (lead) | Items |
|---|---|---|---|---|
| **0 — Baseline** | Clean, reversible starting point | Agent | — | done (tag `baseline-2026-10-30`, commit `0c3837a`) |
| **1 — Working foundation** | Docs and workflow for team and agents | Agent | **G1:** passed 2026-10-30 | done (BQ-000) |
| **2 — Architecture direction** | Decide target architecture (ADRs), re-sequence backlog | Lead + agent | **G2:** one decision session on the ADR package | BQ-028 (in progress: ADR-0004…0006 accepted) |
| **3 — Guardrails** | CI on MySQL, gated deploy, quality and architecture checks | Agent | **G3:** approve dev dependencies; set branch protection | BQ-018, BQ-023, BQ-024, BQ-025, BQ-026 |
| **4 — Production readiness** | Close every launch blocker | Agent + Bagus | **G4:** approve security/payment plans as one package; provide production accounts | BQ-067 first, then BQ-001 … BQ-015, BQ-017, BQ-019 … BQ-022, BQ-039 … BQ-066, BQ-068 … BQ-070 |
| **5 — Launch** | Release v1.0 and stabilize | Lead + Bagus + agent | **G5:** launch sign-off (`docs/operations.md` §8) | — |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Agent + Bagus | PR reviews | BQ-016, BQ-029 … BQ-036 |
| **7 — Cleanup** | Dead code, old branches | Agent | branch decision | BQ-027, BQ-037 |
| **8 — Continuous evolution** | New features and routine care | Everyone | per approval table | BQ-038 and future items |

```text
0 → 1 → 2 → 3 → 4 → 5 → 8
                └→ 6 (starts after 3, runs alongside 4–5) → 7
```

Small items may be pulled forward when they block other work.

## Phase details

### 1 — Working foundation
Glossary, per-domain system map with invariants, `AGENTS.md` BookQu section, workflow, templates, path-scoped rules (`.ai/rules`), condensed product / requirements / architecture / operations, this backlog, ADR-0003. Old documents removed (history in Git). Verified by rule-inventory coverage, link checks, a fresh-agent quiz and the full test suite.

### 2 — Architecture direction
Lead runs `/improve-codebase-architecture`; agent drafts ADR-0004 … ADR-0013 (authorization, availability authority, customer access and identifiers, payment reconciliation, Tier gating, module boundaries, async on shared hosting, testing strategy, legacy naming, environments and release). After the decision session, update `docs/architecture.md`, the affected domain files and the order of Phase 4/6 items. No code changes.

### 3 — Guardrails
Machines enforce the rules before big changes: MySQL integrity suite, deploy only after CI with a database backup, Pint/composer audit/doc checks, architecture tests with a shrinking allowlist, optional Larastan, branch protection on `main`.

### 4 — Production readiness
Four streams, all `launch-blocker` items must be `done`:
- **Security:** BQ-001, 002, 004, 005, 006, 007, 008, 009, 010 and the audit P0 items.
- **Booking and payment correctness:** BQ-067 (Payment lifecycle module, ADR-0004) first; then BQ-068, 003, 014, 013, 015, 070, and BQ-012 + 069 for availability.
- **Operations:** BQ-017, 019, 020, 021, 022.
- **Acceptance:** BQ-039 triage, core-flow P1 defects, end-to-end UAT with sandbox payments, MySQL concurrency suite green.

### 5 — Launch
Release PR `agathan` → `main`, tag `v1.0.0`, changelog, production smoke test, two weeks of close monitoring, hotfix procedure (hotfix branch → `main` → back-merge), post-launch review.

### 6 — Architecture alignment
Per item: characterization tests → refactor → architecture-test allowlist shrinks → domain file updated. Done when the allowlist is empty and core domains reach architecture status `Target`.

### 7 — Cleanup
Dead code, branch clean-up after the lead's branch decision, leftover configuration duplicates.

### 8 — Continuous evolution
- New capability: product change → requirement → ADR if needed → BQ item → staged release Tier 3 → 2 → 1.
- Routine: weekly backlog review (lead); monthly dependency update and `composer audit`; quarterly domain-file freshness check (agent); periodic restore test.
- Scalability triggers (`docs/architecture.md` §14): VPS when a persistent worker is needed, Redis under load, object storage for multiple servers.
- Team trigger: more than three people → mirror the backlog in GitHub Issues/Projects.

## Effort guidance for agents

| Work | Suggested effort |
|---|---|
| Writing authority docs, ADRs, security and payment design | high / xhigh |
| Mechanical migration, templates, reference updates | medium |
| Broad independent audits (55 scope bypasses, 140 audit defects, characterization tests per module) | high + parallel workflows (ultracode) |
