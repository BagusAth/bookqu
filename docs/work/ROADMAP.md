# Roadmap

From the current state to a production-ready, maintainable BookQu. Each phase ends at a gate owned by a role (`AGENTS.md` → "Roles"); outside the gates developers and agents work through `docs/workflow.md`. History of finished work lives in `docs/work/done/` and Git.

## Current status (2026-11-16)

- **Build is complete.** Every item of epics E0–E6 of the owner portal redesign ([BQ-126](BQ-126-owner-portal-redesign-specification.md), [`owner-portal.md`](../owner-portal.md)) and BQ-157 are in `done/`. Developer A and Developer B have no open build items.
- **Every module is released on `integration`** (ADR-0024) so the Tech Lead and the Product Lead can test the whole portal. Plan limits (`EntitlementRules`) and roles (`TenantPermission`) still apply.
- **Not launched yet.** `main` still runs the 2026-10-05 code. **Release policy (D-018):** production is released through BQ-158 after the staging regression pass and the Product Lead's go; urgent security fixes may ship earlier through the hotfix path.
- **CI:** four jobs (quality, SQLite, MySQL 8, MariaDB 11.4) on every PR and before every production deploy; the deploy backs up the database first.

## What is left

Developer A's BQ-162 findings were implemented through BQ-163 on 19 Nov 2026. The [assignment and results](BQ-162/pembagian-perbaikan-dev-a-b.md) and [original test findings with follow-up status](BQ-162/temuan-pengujian-2026-11-18.md) track the 21 A parts and the B work awaiting verification. Browser, screen-reader and production checks remain in the release pass.

| Track | Items | Owner | When |
|---|---|---|---|
| **Operations** | BQ-017 staging, BQ-019 backup and restore, BQ-020 scheduler and queue, BQ-021 mail and monitoring, BQ-022 production payment, BQ-074 step 2; TLS and `CUSTOM_DOMAIN_*` for own domains (`operations.md` §8); branch protection | Tech Lead | before the release |
| **Owner portal look** | [BQ-161](BQ-161-owner-portal-visual-redesign.md): design system (brand aubergine and marigold, Plus Jakarta Sans, `x-ui` components) and a restyle of every owner page, presentation only, one bulk PR | Tech Lead (approval, PR audit), developer agent | before the regression pass |
| **Owner portal rebuild** | [BQ-162](BQ-162-papan-jadwal.md): rebuild every owner page on the "Papan Jadwal" prototype (`docs/design/papan-jadwal/`), with the content changes and new features approved in D-025; nine sessions on branch `agathan-bq-162-papan-jadwal` | Tech Lead (approval, merge decision), developer agent | before the regression pass |
| **Customer pages look** | [BQ-164](BQ-164-alur-pelanggan.md): bring the storefront, checkout, payment, access link, manage, reschedule, invoice, email and error pages to the owner portal standard, from the [customer audit](../design/booking-pelanggan/audit-ui-2026-11-20.md); seven sessions in five batches on branch `agathan-bq-164-alur-pelanggan` (from BQ-162) | Tech Lead (approval, merge decision), developer agent | before the regression pass |
| **Release** | BQ-158: regression pass on staging against every `AC-*`, then release v1.0.0; BQ-126 closes with it | Tech Lead (execution), Product Lead (go) | after the operations track and BQ-162 (D-026) |
| **After the release** | BQ-016, BQ-034, BQ-035, BQ-075 (phase 6), BQ-082 | Tech Lead | after launch |
| **Open product decisions** | D-003 (admin scope beyond payouts and refunds), D-021 items | Product Lead | when decided |

## Phases

| Phase | Goal | Gate owner | State |
|---|---|---|---|
| **0 — Baseline** | Clean, reversible starting point | — | done (tag `baseline-2026-10-30`) |
| **1 — Working foundation** | Docs and workflow for team and agents | Tech Lead | done (BQ-000) |
| **2 — Architecture direction** | Target architecture as ADRs, backlog re-sequenced | Tech Lead | done (BQ-028, ADR-0004…0015) |
| **3 — Guardrails** | CI on MySQL/MariaDB, gated deploy, quality and architecture checks | Tech Lead | built; branch protection still open |
| **4 — Build the redesign** | Every epic E0–E6 item done; every acceptance criterion of `owner-portal.md` passes | Tech Lead (PR audits) | done (2026-11-16, PR #39–#63) |
| **5 — Launch** | Release v1.0.0 and stabilize (E7, D-018) | Product Lead (go/no-go), Tech Lead (execution) | next |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Tech Lead (PR audits) | after launch: BQ-016, BQ-034, BQ-035, BQ-075 |
| **7 — Cleanup** | Dead code, old branches | Tech Lead | withdrawn modules removed (BQ-157), legacy paths removed (ADR-0024); old branches per BQ-085 |
| **8 — Continuous evolution** | New features and routine care | per decision table | BQ-082, D-003, D-021 items and future items |

```text
0 → 1 → 2 → 3 → 4 → 5 → 8
                    └→ 6 (after launch) → 7
```

## 5 — Launch

1. Tech Lead completes BQ-017, BQ-019, BQ-020, BQ-021 and BQ-022 with recorded evidence (`docs/operations.md` §2, §4, §5). The steps are tracked in BQ-158.
2. Regression pass on staging against every `AC-*` of `owner-portal.md`.
3. Product Lead gives the go.
4. Tech Lead opens the release PR `integration` → `main`, tags `v1.0.0` and finalizes `docs/release-notes/v1.0.0.md`.
5. Production smoke test (`docs/operations.md` §8–§9), then two weeks of close monitoring. Hotfixes: branch from `main` → PR into `main` → merge back into `integration`.

## 6 — Architecture alignment

Per item: characterization tests → refactor → architecture-test allowlist shrinks → domain file updated. Done when the allowlist is empty and core domains reach architecture status `Target`.

## 8 — Continuous evolution

- New capability: product change (Product Lead) → page contract in `owner-portal.md` → requirement → ADR if needed → BQ item → released when done; hiding a module needs Product Lead and Tech Lead approval (ADR-0024).
- Routine: weekly backlog review (Tech Lead); monthly dependency update and `composer audit`; quarterly domain-file freshness check; periodic restore test.
- Scalability triggers (`docs/architecture.md` §14): VPS when a persistent worker is needed, Redis under load, object storage for multiple servers.
- Team trigger: more than three people → mirror the backlog in GitHub Issues/Projects.

## Effort guidance for agents

| Work | Suggested effort |
|---|---|
| Writing authority docs, ADRs, security and payment design | high |
| Mechanical migration, templates, reference updates | medium |
| Broad independent audits (scope bypasses, audit defects, characterization tests per module) | high, split into parallel tasks |
