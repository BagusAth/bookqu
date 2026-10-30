# Workflow

How changes to BookQu are planned, approved, implemented, verified and documented — by people and by AI agents alike. The approval table lives in `AGENTS.md` → "Who decides".

## 1. Roles

| Role | Who | Responsibility |
|---|---|---|
| Lead | agathan | Product and architecture decisions, approves plans for critical domains, owns `AGENTS.md`, `docs/*.md` and ADRs, signs off releases |
| Peer | Bagus | Implements and reviews PRs, reads new guidance for clarity |
| Agent | Claude Code, Codex, Antigravity | Turns instructions into work items, implements approved work, keeps docs in sync, never self-approves what the approval table reserves for the lead |

## 2. Task types

| Type | Meaning | Needs a requirement change? |
|---|---|---|
| Bug fix | Behaviour violates an existing requirement | No |
| Feature | Adds a capability already accepted in `product.md` / `requirements.md` | No |
| Refactor | Changes structure, not behaviour | No |
| Technical improvement | Security, performance, tests, tooling, observability | No |
| Product change | Changes what BookQu does (new capability, new rule, new policy) | **Yes — lead decision first** |
| Documentation | Changes docs only | No |
| Operational change | Deployment, scheduler, queue, environment, backups | No; update `operations.md` |

A refactor or bug fix that turns out to change behaviour becomes a product change. Never hide a product change inside another type.

## 3. Lifecycle

```text
instruction ─► work item (proposed) ─► approval? ─► branch ─► implement + test ─► PR ─► review ─► merge ─► item done
```

1. **Capture.** For non-trivial work, create `docs/work/BQ-NNN-slug.md` from `docs/work/_TEMPLATE.md`. Non-trivial means any of: touches business logic, more than one file of behaviour, database schema, security, payment, or needs a decision. Typos, copy and single-line obvious fixes may skip the item.
2. **Plan.** Fill in the item: requirement IDs, domain, current code, target change, invariants at risk, tests, docs to update.
3. **Approve.** Check `AGENTS.md` → "Who decides". If the lead must approve, set `status: proposed` and stop. The lead sets `approved` (or `rejected` with a reason).
4. **Implement.** Branch `bq-NNN-slug`. Small, coherent commits. Characterize poorly tested behaviour before changing it. A small related refactor is fine when it directly reduces risk or duplication for this task; anything else becomes a new item.
5. **Verify.** Narrow tests → related tests → full suite. Record manual checks (UI, payment sandbox, custom domain) in the item.
6. **Document.** Update only the sources whose content changed (section 8).
7. **Review.** PR with `.github/pull_request_template.md`. The peer reviews; the lead also reviews items that required lead approval.
8. **Close.** After merge set `status: done`, add evidence, and move the file to `docs/work/done/`.

Ask for clarification only when two materially different product behaviours are plausible and the repository cannot decide between them. Difficulty is not a reason to stop; record the decision you made in the item instead.

## 4. Work items

- One file per item: `docs/work/BQ-NNN-short-slug.md`. IDs are never reused or renumbered.
- `status`: `proposed` → `approved` → `in-progress` → `done` (or `rejected`).
- `priority`: `P0` (security, data integrity, core flow broken) · `P1` (important) · `P2` (improvement).
- `phase`: the roadmap phase (`docs/work/ROADMAP.md`).
- `launch-blocker: true` items must be `done` before production launch.
- Items may be written in English or Indonesian; IDs, file paths and code names stay in English.
- Unrelated findings discovered during work become new items, not scope creep.

## 5. Branches, commits, releases

- `main` deploys to production automatically on every push (`.github/workflows/deploy-production.yml`). **Never push or merge to `main` directly**; it changes only through a release PR at a roadmap gate.
- `agathan` is the integration branch (ADR-0015); target: it deploys to staging after CI. Work branches start from it and return to it by PR. Hotfixes branch from `main` and are back-merged into `agathan`. Branch protection and clean-up of old branches are lead actions (BQ-027).
- Branch names: `bq-NNN-slug` (or `phase-N-topic` for roadmap phases).
- Commits follow Conventional Commits: `type(scope): summary` with types `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`, `ci`. Mention the item: `fix(payment): reconcile late success (BQ-003)`.
- Releases are tagged `vMAJOR.MINOR.PATCH` with a changelog entry.

## 6. Testing

- Run the narrowest test first (`--filter`, one file), then related tests, then `php artisan test --compact`.
- Booking and payment changes cover: stale pending, concurrent booking, cross-tenant access, cross-booking token use, duplicate webhook, driver switching, expiry.
- Name tests by behaviour: `test_owner_cannot_access_other_tenant_bookings`.
- A failing test is evidence. Decide whether the code, the requirement or the test is wrong before touching the test.
- "Verified" needs matching evidence. Concurrency claims need MySQL, not SQLite (see BQ-023).
- Add characterization tests before refactoring poorly tested critical code.

## 7. Definition of done

Every item:

- [ ] Requirement identified (or product change approved)
- [ ] Approval obtained where required
- [ ] Implementation complete; validation and authorization handled; tenant isolation checked
- [ ] Invariants listed in the item still hold
- [ ] Tests added or updated; focused and full suites pass
- [ ] `vendor/bin/pint --dirty --format agent` run on changed PHP
- [ ] Manual verification recorded where automated tests cannot reach
- [ ] Docs updated per section 8; item set to `done` with evidence

Extra per type:

| Type | Also required |
|---|---|
| Bug fix | Root cause stated; regression test reproduces the bug |
| Feature | Product and requirement exist; UI verified |
| Refactor | Behaviour unchanged (tests before and after); no duplicated rules left behind |
| Product change | `product.md` and `requirements.md` updated before code; lead approved |

## 8. Keeping documentation in sync

Update only what changed:

| When you change… | Update |
|---|---|
| What BookQu offers or a release tier | `docs/product.md` |
| Required behaviour or a business rule | `docs/requirements.md` |
| A structural rule or layer boundary | `docs/architecture.md` (+ ADR if significant) |
| How a domain works, its entry points, invariants, tests or status | `docs/domains/<domain>.md` |
| A term or legacy name | `docs/glossary.md` |
| Deployment, scheduler, queue, environment, backups | `docs/operations.md` |
| A rule for editing certain paths | `.ai/rules/<area>.md`, then regenerate `.ai/rules/index.md` (command in `.ai/rules/documentation.md`) |
| Work status | the `BQ-*` item only |

Write an ADR (`docs/adr/_TEMPLATE.md`) when a decision affects several domains, changes an integration or tenancy/concurrency/payment strategy, introduces a new pattern, or would otherwise leave future contributors guessing.

## 9. When sources disagree

Classify the conflict — product, requirement, architecture, implementation drift, test drift or documentation drift — then fix the authoritative source. Do not rewrite docs to hide wrong code, and do not change correct code because an outdated doc says otherwise. Record the resolution in the work item.

## 10. Writing docs for humans and agents

- Put the answer first; one topic per section; short sentences.
- One rule lives in one place; everywhere else links to it.
- Use stable IDs (`FR-`, `BR-`, `NFR-`, `INV-`, `BQ-`, `ADR-`) and real paths, class and route names.
- Prefer tables and numbered steps over prose and ASCII diagrams.
- Describe the current system in `docs/domains/`, the target in `docs/architecture.md`; never present the target as already implemented.
- No history in active docs — Git keeps it.
