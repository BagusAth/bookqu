# ADR-0003: Layered documentation and in-repo work system

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead)
> **Work item:** BQ-000

## Context

By October 2026 BookQu had about 18,000 lines of guidance: `AGENT.md` (1,876 lines, never loaded automatically because agents read `AGENTS.md`/`CLAUDE.md`), eight numbered documents in `docs/`, a technical audit and a 140-defect audit. The same rules were repeated in up to six places and had started to contradict each other (security priority order, payment providers, webhook paths, CI branches). The system-design document named almost no code. Status was spread over a tracker (stale: 368 tests recorded, 413 real), the audit files and the technical audit. Two developers and three AI agents (Claude Code, Codex, Antigravity) need one shared way of working, and instructions are usually given abstractly.

## Options

1. **Keep the numbered documents and fix drift.** Cheapest now; keeps duplication, length and the unread `AGENT.md`.
2. **GitHub Issues/Projects for work, docs unchanged.** Good notifications; agents need `gh` and logins; plans live apart from code.
3. **Layered docs + in-repo work items (chosen).**

## Decision

1. **Layers.** `AGENTS.md` (BookQu section, always loaded by all three agents; Laravel Boost's block below it stays generated) → `.ai/rules/*.md` (path-scoped rules, indexed by Boost's `RuleRepository`) → `docs/` references read on demand.
2. **Document set.** `docs/README.md` (map), `product.md`, `requirements.md`, `architecture.md`, `domains/<domain>.md`, `glossary.md`, `workflow.md`, `operations.md`, `adr/`, `work/`. File names carry no numbers; documentation is written in English; user-facing copy stays Indonesian.
3. **Current vs target.** `domains/` describes the current system with invariants (`INV-*`), evidence and status; `architecture.md` is normative; status and plans live only in `work/`.
4. **Work items.** One Markdown file per item (`docs/work/BQ-NNN-slug.md`, front matter + problem/plan/evidence), roadmap in `docs/work/ROADMAP.md`, finished items in `docs/work/done/`. Abstract instructions become `proposed` items; the approval table in `AGENTS.md` decides when the lead must approve before coding.
5. **Migration.** All 228 requirement IDs kept; 142 canonical rules from the old documents were inventoried and re-homed; the 140 audit defects became BQ-040 … BQ-066; the old documents were deleted (available at tag `baseline-2026-10-30`).

## Consequences

- Agents load ~85 lines of BookQu guidance plus only the rules and domain files they need, instead of ignoring 1,876 lines.
- Plans and code change in the same PR; both developers and every agent read the same files.
- Discipline required: update the domain file, requirement or ADR when content changes (`workflow.md` §8). CI checks for links and IDs are planned (BQ-024).
- If the team grows beyond three people, mirror work items in GitHub Issues/Projects (roadmap phase 8).
