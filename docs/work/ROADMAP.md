# Roadmap

From the current state to a production-ready, maintainable BookQu. Each phase ends at a gate owned by a role (`AGENTS.md` → "Roles"); outside the gates developers and agents work through `docs/workflow.md`. History of finished work lives in `docs/work/done/` and Git.

## Current status (2026-11-04)

- **Not launched yet.** `main` still runs the 2026-10-05 code; everything since then is on `integration`.
- **Code for v1 is complete:** payment lifecycle and outcome intake, slot claims, one payment deadline, customer access links, server-side module gating, balance and withdrawals, queued mail with an in-process scheduler, three-step storefront booking (BQ-084) and storefront hardening (BQ-083).
- **CI:** four jobs (quality, SQLite, MySQL 8, MariaDB 11.4) on every PR and before every production deploy; the deploy backs up the database first.
- **Open before launch (Tech Lead, operations):** BQ-020 cron on HostData, BQ-021 SMTP and monitoring, BQ-022 production SingaPay credentials and webhook URL. Branch protection for `main` and `integration` needs a repository admin.
- **Open before launch (code, found 2026-11-01/02):** BQ-086 expired trials keep Pro entitlement; BQ-087 owner actions during an open online payment and ignored double payments; BQ-089 stored XSS in the dashboard refresh; BQ-098 account changes and deletion without the current password; BQ-115 plan and landing lists promise unreleased features (D-008 deferred, see release policy).
- **Owner UI rework (audit 2026-11-02):** target pages in [`owner-portal.md`](../owner-portal.md) §8 (draft until D-007 Revisi 1); the item list below predates the redesign and is re-planned in BQ-126 phases 8–9. BQ-092 foundations first, then BQ-090 shell performance, BQ-091 navigation, BQ-093 copy, BQ-094 dashboard, BQ-095 bookings, BQ-096 calendar and schedule, BQ-097 non-functional controls, BQ-099 services/categories, BQ-100 customers CRM, BQ-101 reviews, BQ-103 balance/payouts, BQ-104 subscription checkout, BQ-105 storefront link routing, BQ-106 dialog/drawer accessibility, BQ-107 bulk schedule capping, BQ-108 blocked date conflict, BQ-109 reschedule price drift, BQ-110 slug change & custom domain, BQ-111 analytics query & CSV, BQ-112 atomic registration & CSRF 419.
- **Customer/owner booking and public-site audit (2026-11-02, `f361cc2`):** seven new findings are proposed as [BQ-113](BQ-113-reschedule-stale-response-and-date-mismatch.md) reschedule response/date mismatch, [BQ-114](BQ-114-historical-booking-duration-drift.md) historical booking times, [BQ-115](BQ-115-public-feature-promises-release-alignment.md) unreleased feature promises, [BQ-116](BQ-116-checkout-policy-presentation-mismatch.md) checkout policy, [BQ-117](BQ-117-calendar-month-navigation-overflow.md) month navigation, [BQ-118](BQ-118-service-search-enter-submits-booking.md) Enter in search, and [BQ-119](BQ-119-public-plan-price-source.md) landing prices. Related gaps have dedicated follow-ups [BQ-120](BQ-120-owner-reschedule-closed-date-read-parity.md) closed-date read/write parity after BQ-012/BQ-069 and [BQ-121](BQ-121-customer-historical-invoice-amount.md) customer invoice amounts alongside BQ-095/BQ-109; WhatsApp scope extends BQ-082 / BUG-T6-10. Reproduction evidence: five isolated HTTP scenarios / 29 assertions plus Edge/Playwright; these confirm existing defects, not fixes. Tech Lead triage 2026-11-02: only BQ-115 blocks launch (through D-008); BQ-113 lowered to P2; heavy-lane plans still need approval before coding.
- **Product decisions (2026-11-04, [product decisions](../product-decisions.md)):** D-001 optional refunds, D-002 trial and subscription end, D-004 saved payout accounts (up to 3, one primary; built in BQ-123..125), D-005 custom domain (Pro, on the Halaman Booking page) and D-006 reschedule price difference are decided; their implementation items are still to be written. D-003 admin scope is deliberately left open. D-007 owner page map is being rewritten from the approved owner portal redesign (Revisi 1). D-008 plan feature lists is deferred in favour of D-017. D-009 to D-021 (redesign decisions, release policy and scope) wait for the Product Lead. The specification and backlog audit plan is [BQ-126](BQ-126-owner-portal-redesign-specification.md); payout follow-ups from the PR #31 audit are [BQ-127](BQ-127-payout-account-follow-ups.md).
- **Release policy (2026-11-04):** production is released only after every feature in the approved owner portal redesign is built. Until then the "open before launch" items above stay open but nothing ships; the phase plan and the owner UI rework list are being replanned from the new specification.
- **Deferred until after launch:** BQ-017 staging, BQ-019 scheduled backups and restore drill, Phase 6 refactors, BQ-082 polish.

## Phases

| Phase | Goal | Gate owner | State |
|---|---|---|---|
| **0 — Baseline** | Clean, reversible starting point | — | done (tag `baseline-2026-10-30`) |
| **1 — Working foundation** | Docs and workflow for team and agents | Tech Lead | done (BQ-000) |
| **2 — Architecture direction** | Target architecture as ADRs, backlog re-sequenced | Tech Lead | done (BQ-028, ADR-0004…0015) |
| **3 — Guardrails** | CI on MySQL/MariaDB, gated deploy, quality and architecture checks | Tech Lead | built; branch protection still open |
| **4 — Production readiness** | Close every launch blocker | Tech Lead | BQ-086, BQ-087, BQ-089, BQ-098, BQ-115, D-001, D-008 and BQ-020, BQ-021, BQ-022 open |
| **5 — Launch** | Release v1.0.0 and stabilize | Product Lead (go/no-go), Tech Lead (execution) | next |
| **6 — Architecture alignment** | Structural refactor toward the ADRs | Tech Lead (PR audits) | after launch: BQ-016, BQ-029, BQ-030, BQ-031, BQ-033, BQ-034, BQ-035, BQ-075, BQ-077 |
| **7 — Cleanup** | Dead code, old branches | Tech Lead | BQ-037 done; old branch clean-up per BQ-085 |
| **8 — Continuous evolution** | New features and routine care | per decision table | BQ-082, BQ-088, BQ-090 to BQ-097, BQ-099 to BQ-125, D-002 to D-007 and future items |

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
