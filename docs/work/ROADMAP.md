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
| **2 — Architecture direction** | Decide target architecture (ADRs), re-sequence backlog | Lead + agent | **G2:** passed 2026-10-30 (ADR-0004…0015; lead delegated remaining choices) | done (BQ-028) |
| **3 — Guardrails** | CI on MySQL, gated deploy, quality and architecture checks | Agent | **G3:** approve dev dependencies; set branch protection | built (BQ-018, BQ-023, BQ-024, BQ-025, BQ-026 done); G3 open |
| **4 — Production readiness** | Close every launch blocker | Agent + Bagus | **G4:** review the launch-blocker list; provide production accounts, staging subdomain and cron (plans already approved through the ADRs) | BQ-067 first, then BQ-001 … BQ-015, BQ-017, BQ-019 … BQ-022, BQ-032, BQ-039 … BQ-066, BQ-068 … BQ-074 |
| **5 — Launch** | Release v1.0 and stabilize | Lead + Bagus + agent | **G5:** launch sign-off (`docs/operations.md` §8) | — |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Agent + Bagus | PR reviews | BQ-016, BQ-029, BQ-030, BQ-031, BQ-033, BQ-034, BQ-035, BQ-075 (BQ-036 rejected by ADR-0012) |
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
Done 2026-10-30. Accepted: ADR-0004 Payment lifecycle, ADR-0005 Slot bookability, ADR-0006 Management link, ADR-0007 Provider outcome intake, ADR-0008 Booking checkout draft, ADR-0009 Pending-payment lifetime, ADR-0010 Authorization and tenant scoping, ADR-0011 Server-side release gating, ADR-0012 Module boundaries and naming, ADR-0013 Background work on shared hosting, ADR-0014 Testing strategy, ADR-0015 Environments and release. `docs/architecture.md`, the domain files and the Phase 4/6 order below were updated. No code changes.

### 3 — Guardrails
Machines enforce the rules before big changes: MySQL integrity suite, deploy only after CI with a database backup, Pint/composer audit/doc checks, architecture tests with a shrinking allowlist, optional Larastan, branch protection on `main`.

Built on branch `phase-3-guardrails` (2026-10-30): BQ-023, BQ-025, BQ-024, BQ-018 and BQ-026 (Larastan approved with the plan). It also restored CI, which had failed on `agathan` since the Boost commit because the lock file needed PHP 8.4. CI now runs three jobs: `quality` (Pint on changed files, Larastan, composer audit, npm audit report-only), `tests-sqlite` and `tests-mysql` (whole suite on MySQL 8). Production deploys call those jobs first. All three jobs passed on GitHub Actions for PR #1 (run 41039402279), so the five items are `done`.

G3 (lead):
- done: `test-ssh.yml` confirmed `mysqldump` on HostData; the server is MariaDB 11.4.10, now tested in CI (BQ-073);
- done: BQ-072 approved and fixed in PR #2;
- open: protect `main` (PRs only, required checks `Quality checks`, `Tests (SQLite)`, `Tests (MySQL)`, `Tests (MariaDB)`, one approval). Needs a repository admin; agathan has push rights only on `BagusAth/bookqu`.

The first gated production deploy happens at the next release.

### 4 — Production readiness
Four streams, all `launch-blocker` items must be `done`:
- **Security:** BQ-006, BQ-008 + BQ-033 (ADR-0011), BQ-009 (ADR-0010), BQ-004, 005, 007, 010, 011 and the audit P0 items; BQ-001/002 land through the correctness and customer-access streams.
- **Booking and payment correctness** (in order): BQ-067 Payment lifecycle (ADR-0004) → BQ-032 Outcome intake (ADR-0007) with BQ-070, BQ-002 gateway cancel and BQ-003 → BQ-071 payment deadline (ADR-0009) with BQ-012 Slot bookability (ADR-0005) → BQ-069; alongside: BQ-068, BQ-014, BQ-015; BQ-013 after BQ-020.
- **Customer access:** BQ-001 Management link (ADR-0006) after BQ-012, with BQ-053 and BQ-052 acceptance.
- **Operations:** BQ-017, 019, 020, 021, 022.
- **Acceptance:** BQ-039 triage, core-flow P1 defects, end-to-end UAT with sandbox payments, MySQL concurrency suite green.

Progress (2026-10-30): BQ-073 (CI on MariaDB 11.4, the production engine; fixed the owner customer list) and BQ-067 Payment lifecycle done (also closed BQ-068 and BQ-014). BQ-032 Outcome intake done (also closed BQ-070 and BQ-003; gateway cancel for BQ-002). BQ-071 one payment deadline done. BQ-012 Slot bookability done (cache ownership split out as BQ-075, Phase 6). BQ-069 closed-date enforcement verified. BQ-001 Management link implemented (2026-10-31), with BQ-053 scope and BQ-052 sharing regressions closed. Recovery, owner resend and per-family throttling remain explicitly deferred in its Evidence. Next: verify and close BQ-002 payer-cancel acceptance, then BQ-004/BQ-005 tenant boundaries.

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
