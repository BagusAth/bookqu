# Roadmap

From the current state to a production-ready, maintainable BookQu. Each phase ends at a gate owned by a role (`AGENTS.md` → "Roles"); outside the gates developers and agents work through `docs/workflow.md`. History of finished work lives in `docs/work/done/` and Git.

## Current status (2026-11-04)

- **Not launched yet.** `main` still runs the 2026-10-05 code; everything since then is on `integration`. **Release policy (D-018):** production is released only after every capability of the owner portal redesign is built and tested; urgent security fixes may ship earlier through the hotfix path.
- **Specification is approved:**
  - [`owner-portal.md`](../owner-portal.md) (D-007), with 76 acceptance criteria;
  - requirements, product and glossary updated for D-001 to D-021;
  - ADR-0016 to ADR-0022 accepted.

  Plan and evidence: [BQ-126](BQ-126-owner-portal-redesign-specification.md). D-003 (admin scope beyond payouts and refunds) is deliberately left open; D-022 (formal acceptance of password reset, rate limiting and the platform landing page, which already exist) waits for the Product Lead.
- **CI:** four jobs (quality, SQLite, MySQL 8, MariaDB 11.4) on every PR and before every production deploy; the deploy backs up the database first.
- **Work is organised in eight epics.** Every acceptance criterion of `owner-portal.md` and every requirement accepted on 2026-11-04 belongs to exactly one item.

| Epic | Items | Content |
|---|---|---|
| **E0 · Security and integrity that stay** | BQ-049, BQ-074, BQ-077, BQ-086, BQ-087, BQ-088, BQ-089, BQ-098, BQ-107, BQ-108, BQ-111, BQ-112, BQ-113, BQ-114, BQ-118, BQ-120, BQ-121, BQ-127 | Server-side bugs that survive the redesign. Most are prerequisites of a page item |
| **E1 · Foundations** | BQ-128 shell and navigation, BQ-129 shared blocks, BQ-130 module/plan/role gates and plan list, BQ-131 membership and permissions | Everything the pages build on |
| **E2 · Core loop pages** | BQ-132 Layanan, BQ-133 Jam Buka & Harga, BQ-134 Kalender, BQ-135 Booking and panel, BQ-136 Beranda, BQ-137 Pelanggan | One PR per page |
| **E3 · Approved decisions** | BQ-138 refunds (D-001), BQ-139 ended subscription (D-002), BQ-140 reschedule prices (D-006) | Heavy lane |
| **E4 · Trust and acquisition** | BQ-141 Pembayaran, BQ-142 Halaman Booking, BQ-143 Ulasan, BQ-144 Pengaturan, BQ-145 Paket & Tagihan, Notifikasi, Bantuan | Mostly light lane |
| **E5 · Medium value** | BQ-146 Tim, BQ-147 Laporan, BQ-148 pricing rule, BQ-149 Promo, BQ-150 Add-on | Heavy lane |
| **E6 · Pro value and core rules** | BQ-151 seats and capacity, BQ-152 Unit, BQ-153 no-show, BQ-154 landing page and domain, BQ-155 integrations, BQ-156 API and webhooks | Heavy lane; MySQL concurrency tests |
| **E7 · Release** | BQ-157 remove withdrawn modules, BQ-158 first production release; ops BQ-017, BQ-019, BQ-020, BQ-021, BQ-022; branch protection | Product Lead go/no-go |

- **Backlog audit (BQ-126 phase 9):**
  - 24 items merged into the epic items (status `rejected` with a pointer; their findings are part of the new scope);
  - BQ-082 narrowed;
  - BQ-103 and BQ-122 to BQ-125 moved to `done/`;
  - the remaining items stay, listed in E0, E7 or phase 6.
- **Deferred until after the first release:**
  - D-021 items;
  - Phase 6 refactors that no page item needs: BQ-016, BQ-030, BQ-034, BQ-035, BQ-075;
  - BQ-082.

## Phases

| Phase | Goal | Gate owner | State |
|---|---|---|---|
| **0 — Baseline** | Clean, reversible starting point | — | done (tag `baseline-2026-10-30`) |
| **1 — Working foundation** | Docs and workflow for team and agents | Tech Lead | done (BQ-000) |
| **2 — Architecture direction** | Target architecture as ADRs, backlog re-sequenced | Tech Lead | done (BQ-028, ADR-0004…0015) |
| **3 — Guardrails** | CI on MySQL/MariaDB, gated deploy, quality and architecture checks | Tech Lead | built; branch protection still open |
| **4 — Build the redesign** | Every epic E0–E6 item done; every acceptance criterion of `owner-portal.md` passes | Tech Lead (PR audits) | next: E0 and E1 |
| **5 — Launch** | Release v1.0.0 and stabilize (E7, D-018) | Product Lead (go/no-go), Tech Lead (execution) | after phase 4 |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Tech Lead (PR audits) | BQ-029 with BQ-148; BQ-031 and BQ-033 merged into BQ-131 and BQ-130; after launch: BQ-016, BQ-030, BQ-034, BQ-035, BQ-075 |
| **7 — Cleanup** | Dead code, old branches | Tech Lead | BQ-037 done; old branch clean-up per BQ-085 |
| **8 — Continuous evolution** | New features and routine care | per decision table | BQ-082, D-003, D-021 items and future items |

```text
0 → 1 → 2 → 3 → 4 → 5 → 8
                └→ 6 (after launch) → 7
```

Small items may be pulled forward when they block other work.

## 5 — Launch

1. Every item of epics E0–E6 is done, and BQ-157 has removed the withdrawn modules. Tech Lead completes BQ-017, BQ-019, BQ-020, BQ-021 and BQ-022 with recorded evidence (`docs/operations.md` §2, §4, §5). The steps are tracked in BQ-158.
2. Product Lead gives the go.
3. Tech Lead opens the release PR `integration` → `main`, tags `v1.0.0` and finalizes `docs/release-notes/v1.0.0.md`.
4. Production smoke test (`docs/operations.md` §8–§9), then two weeks of close monitoring. Hotfixes: branch from `main` → PR into `main` → merge back into `integration`.

## 6 — Architecture alignment

Per item: characterization tests → refactor → architecture-test allowlist shrinks → domain file updated. Done when the allowlist is empty and core domains reach architecture status `Target`.

## 8 — Continuous evolution

- New capability: product change (Product Lead) → page contract in `owner-portal.md` → requirement → ADR if needed → BQ item → module gate `hidden` until done (ADR-0021).
- Routine: weekly backlog review (Tech Lead); monthly dependency update and `composer audit`; quarterly domain-file freshness check; periodic restore test.
- Scalability triggers (`docs/architecture.md` §14): VPS when a persistent worker is needed, Redis under load, object storage for multiple servers.
- Team trigger: more than three people → mirror the backlog in GitHub Issues/Projects.

## Effort guidance for agents

| Work | Suggested effort |
|---|---|
| Writing authority docs, ADRs, security and payment design | high |
| Mechanical migration, templates, reference updates | medium |
| Broad independent audits (scope bypasses, audit defects, characterization tests per module) | high, split into parallel tasks |
