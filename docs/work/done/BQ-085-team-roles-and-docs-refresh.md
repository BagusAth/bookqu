---
id: BQ-085
title: Role-based decisions, integration branch and documentation refresh
type: docs
status: done
priority: P1
phase: 7
domain: —
requirements: [FR-MANAGE-002]
invariants: [INV-BOOK-03, INV-TOK-01]
lane: heavy
approval: tech-lead
launch-blocker: false
depends-on: [BQ-027]
---

# BQ-085: Role-based decisions, integration branch and documentation refresh

## Problem

The documentation named one person as "lead" for every decision, used the developer branch `agathan` as the integration branch, and had drifted after 25 PRs: the occupancy invariant in the index still described a 15-minute grace period, "token" wording survived after access grants replaced tokens, product tiers and domain statuses were outdated, `operations.md` mixed languages, the roadmap was a run-on log, and the light/heavy working lanes existed only in one agent's private memory. The README missed the local setup problems a new developer hits.

## Decisions (Tech Lead, 2026-11-01)

1. Roles, not people: Product Lead, Tech Lead, Developer, Agent; one roles table in `AGENTS.md`. The agent asks the developer running the session.
2. Branches `main`, `integration`, `agathan`, `bagus` (or `<name>/<topic>`), with three sync rules.
3. Changes reach `integration` only by PR, audited and merged by the Tech Lead.
4. Three decision levels: Developer, Tech Lead, Product Lead.
5. Release: Product Lead decides, Tech Lead executes.
6. Light and heavy lanes; work items only for the heavy lane.
7. Authority docs English; README, release notes and UI copy Indonesian.
8. ADR-0008 marked "Accepted — not implemented".
9. Historical records stay as written.
10. Repository rules win over personal agent memory, skills and plugins; the repository does not depend on personal tools.

## Changes

- `AGENTS.md` (BookQu section): roles, decision table, lanes, branch flow, precedence rule, optional graphify, access-grant wording, new commands.
- `docs/workflow.md` rewritten around roles, lanes, branches and worktrees; `docs/README.md`, `.ai/rules/documentation.md`, work/ADR/PR templates.
- Content drift: `product.md`, `architecture.md`, ADR-0008 status, ADR-0015 amendment, domain index (INV-BOOK-03, INV-TOK-01, statuses), `booking.md`, `schedule.md`, `balance-payout.md`, `customers-reviews.md`, requirements (FR-MANAGE-002 and access-grant rules), glossary, `operations.md` (English), `ROADMAP.md` (status snapshot), open work items (role wording), `README.md` (setup, branches).
- CI: `tests.yml` runs on push to `integration` instead of `agathan`.
- BQ-027 closed by these decisions.

## Evidence

- `DocumentationIntegrityTest` and the full suite pass locally; four CI jobs pass on the PR.
- `php artisan boost:update` leaves the BookQu section of `AGENTS.md` unchanged.
- After merge, `integration` is created from the merged `agathan` head.
- Outside the repository: the repository owner sets the default branch, branch protection and the Tech Lead's admin rights; old branch owners decide on archive tags.
