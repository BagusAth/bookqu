# Roadmap

From the current state to a production-ready, maintainable BookQu. Each phase ends at a gate owned by a role (`AGENTS.md` → "Roles"); outside the gates developers and agents work through `docs/workflow.md`. History of finished work lives in `docs/work/done/` and Git.

## Current status (2026-11-01)

- **Not launched yet.** `main` still runs the 2026-10-05 code; everything since then is on `integration`.
- **Code for v1 is complete:** payment lifecycle and outcome intake, slot claims, one payment deadline, customer access links, server-side module gating, balance and withdrawals, queued mail with an in-process scheduler, three-step storefront booking (BQ-084) and storefront hardening (BQ-083).
- **CI:** four jobs (quality, SQLite, MySQL 8, MariaDB 11.4) on every PR and before every production deploy; the deploy backs up the database first.
- **Open before launch (Tech Lead, operations):** BQ-020 cron on HostData, BQ-021 SMTP and monitoring, BQ-022 production SingaPay credentials and webhook URL. Branch protection for `main` and `integration` needs a repository admin.
- **Open before launch (code, found 2026-11-01):** BQ-086 expired trials keep Pro entitlement; BQ-087 owner actions during an open online payment and ignored double payments.
- **Waiting for a Product Lead decision** ([product decisions](../product-decisions.md)): D-001 optional refunds (blocks launch; its fallback is enough for launch), D-002 trial and subscription end, D-003 admin scope.
- **Deferred until after launch:** BQ-017 staging, BQ-019 scheduled backups and restore drill, Phase 6 refactors, BQ-082 polish.

## Phases

| Phase | Goal | Gate owner | State |
|---|---|---|---|
| **0 — Baseline** | Clean, reversible starting point | — | done (tag `baseline-2026-10-30`) |
| **1 — Working foundation** | Docs and workflow for team and agents | Tech Lead | done (BQ-000) |
| **2 — Architecture direction** | Target architecture as ADRs, backlog re-sequenced | Tech Lead | done (BQ-028, ADR-0004…0015) |
| **3 — Guardrails** | CI on MySQL/MariaDB, gated deploy, quality and architecture checks | Tech Lead | built; branch protection still open |
| **4 — Production readiness** | Close every launch blocker | Tech Lead | BQ-086, BQ-087, D-001 and BQ-020, BQ-021, BQ-022 open |
| **5 — Launch** | Release v1.0.0 and stabilize | Product Lead (go/no-go), Tech Lead (execution) | next |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Tech Lead (PR audits) | after launch: BQ-016, BQ-029, BQ-030, BQ-031, BQ-033, BQ-034, BQ-035, BQ-075, BQ-077 |
| **7 — Cleanup** | Dead code, old branches | Tech Lead | BQ-037 done; old branch clean-up per BQ-085 |
| **8 — Continuous evolution** | New features and routine care | per decision table | BQ-082, BQ-088, D-002, D-003 and future items |

```text
0 → 1 → 2 → 3 → 4 → 5 → 8
                └→ 6 (after launch) → 7
```

Small items may be pulled forward when they block other work.

## 5 — Launch

1. Tech Lead completes BQ-020, BQ-021 and BQ-022 with recorded evidence (`docs/operations.md` §2, §4, §5).
2. Product Lead gives the go.
3. Tech Lead opens the release PR `integration` → `main`, tags `v1.0.0` and finalizes `docs/release-notes/v1.0.0.md`.
4. Production smoke test (`docs/operations.md` §8–§9), then two weeks of close monitoring. Hotfixes: branch from `main` → PR into `main` → merge back into `integration`.

## 6 — Architecture alignment

Per item: characterization tests → refactor → architecture-test allowlist shrinks → domain file updated. Done when the allowlist is empty and core domains reach architecture status `Target`.

## 8 — Continuous evolution

- New capability: product change (Product Lead) → requirement → ADR if needed → BQ item → staged release Tier 3 → 2 → 1.
- Routine: weekly backlog review (Tech Lead); monthly dependency update and `composer audit`; quarterly domain-file freshness check; periodic restore test.
- Scalability triggers (`docs/architecture.md` §14): VPS when a persistent worker is needed, Redis under load, object storage for multiple servers.
- Team trigger: more than three people → mirror the backlog in GitHub Issues/Projects.

## Effort guidance for agents

| Work | Suggested effort |
|---|---|
| Writing authority docs, ADRs, security and payment design | high |
| Mechanical migration, templates, reference updates | medium |
| Broad independent audits (scope bypasses, audit defects, characterization tests per module) | high, split into parallel tasks |
