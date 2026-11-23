---
id: BQ-000
title: Phase 1 — working foundation (documentation and workflow) and its decision log
type: docs
status: done
priority: P0
phase: 1
domain: —
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: []
---

# BQ-000: Phase 1 — working foundation

Replace ~18,000 lines of overlapping documentation (`AGENT.md`, `docs/1-…8-*.md`, the technical audit and the defect audit) with a short, layered, single-source documentation system that people and agents share. No application code changes in this phase.

## Scope (approved 2026-10-30)

Phase 0 baseline and hygiene; step 1.1 rule inventory; 1.2 glossary and domain files; 1.3 `AGENTS.md` section, workflow, templates; 1.4 `.ai/rules`; 1.5 product / requirements / architecture / operations; 1.6 backlog and roadmap; 1.7 cutover and ADR-0003; 1.8 verification. Lead decisions D1–D6: file names without numbers; English docs; capability status in domain files; audit migrated as 21 P0 items + one group per stage; old docs deleted (Git keeps history); approval table as proposed.

## Decision log

Decisions the agent took on the lead's behalf under the autonomy granted on 2026-10-30. Each can be reverted.

| # | Decision | Reason |
|---|---|---|
| 1 | Phase work on branch `phase-1-docs` from `agathan`; never touch `main` | `main` auto-deploys to production |
| 2 | Phase 0 removed `graphify-out/` from Git and 15 legacy skills in `.agents/skills` (lead-approved earlier as category A / point 6) | They polluted every diff and the Codex/Antigravity context |
| 3 | Split the planned single `domain-map.md` into `docs/domains/<domain>.md` (15 files + index) | Agents load only the relevant domain; parallel editing; one file per new domain |
| 4 | Invariant IDs `INV-<AREA>-NN` defined once in `docs/domains/README.md` | Stable references for rules, tests and work items |
| 5 | Booking transition pending→completed documented as allowed (code unchanged); the missing on-site payment record became BQ-014 | Walk-ins are created `paid`; pending→completed is the owner completing an unpaid online booking |
| 6 | `requirements.md` keeps all 228 IDs; 8 new entries marked **Proposed** (FR-AUTH-007/008, FR-PUBLIC-005, FR-PAYOUT-001…003, FR-MANAGE-008, BR-PAY-001) | Behaviour exists or is needed but was never accepted |
| 7 | Work items may be English or Indonesian; migrated audit text stays in its original Indonesian | Translating 140 findings adds risk and no value |
| 8 | `.ai/rules` index generated with Boost's own `RuleRepository::writeIndex()` | Keeps the format `boost:update` expects |
| 9 | Approval table and non-negotiable rules live only in `AGENTS.md`; `docs/workflow.md` links to them | One rule, one place |
| 10 | New backlog findings while writing docs: platform balance never credited (BQ-015), reserved tenant slugs (BQ-011), SingaPay status check reads only the local DB (in BQ-003), schedule status `diblokir` not in enum (BQ-016), deploy has no CI gate or DB backup (BQ-018) | Found by reading code and workflows |
| 11 | The BookQu section of `AGENTS.md` must never contain Boost's literal tag; verified that `php artisan boost:update` leaves it byte-identical | The first version mentioned the tag and `boost:update` overwrote the section — caught in step 1.7 |
| 12 | The root `README.md` stays in Indonesian (human onboarding); it now points to the new docs and warns that `main` auto-deploys | Audience is the team; reference docs are English |
| 13 | Items BQ-010 and BQ-037 start as `approved` | The lead approved them earlier in the session (test-token command local-only; dead-code removal) |

## Progress

| Step | Commit |
|---|---|
| 0 Baseline and hygiene | `0c3837a` |
| 1.1 Rule inventory, 1.2 glossary and domains | `05c615a` |
| 1.3 `AGENTS.md`, workflow, templates | `827451f` |
| 1.4 `.ai/rules` | `3a229f1` |
| 1.5 Product, requirements, architecture, operations | `78e15ad` |
| 1.6 Backlog and roadmap | `d30188f` |
| 1.7 Cutover, ADR-0003, README | `36002a5` |
| 1.8 Verification | this commit |

The temporary rule inventory (`docs/_phase1/rule-inventory.md`, 142 rules with sources and new homes) is preserved in Git at commit `d30188f`.

## Evidence (step 1.8, 2026-10-30)

| Check | Result |
|---|---|
| Rule inventory coverage | 142 rules: 131 matched automatically in their new home; 11 reviewed by hand — 8 already covered in other words, 3 added (related refactors in `workflow.md`, unreliable providers in `.ai/rules/payment.md`, evidence for operational claims in `operations.md`) |
| Requirement IDs | 228 original IDs preserved + 8 Proposed = 236; every `FR-/BR-/NFR-` reference in the docs resolves |
| Links, paths, IDs | Script check over 111 files: no broken links, no unknown `BQ-`, `INV-` or ADR references; remaining hits are placeholders (`BQ-NNN-slug`), Boost's own text, Git-only history and the planned `app/Policies` |
| `AGENTS.md` | BookQu section 85 lines (target ≤150); `php artisan boost:update` leaves the file byte-identical |
| Fresh-agent quiz | An agent with no prior context answered 10/10 questions correctly from the docs alone without opening application code; its four confusion points were resolved (PHP version wording, `done/` folder note, voucher promotion vs product change) or are tracked (`diblokir`, BQ-016) |
| Test suite | `php artisan test --compact`: 413 passed (1,955 assertions) — unchanged from the baseline |
| Size | Guidance read on demand: ~3,400 lines (`AGENTS.md`, `docs/*.md`, `docs/domains/`, `.ai/rules/`) instead of ~18,000 |

Gate G1 passed 2026-10-30: the lead reviewed the branch and approved the merge into `agathan` (fast-forward to `8365de9`). Peer readability check by Bagus is still open.
