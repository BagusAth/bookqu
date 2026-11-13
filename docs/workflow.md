# Workflow

How changes to BookQu are planned, approved, implemented, verified and documented — by people and by AI agents alike. Roles and the decision table live in `AGENTS.md` → "Roles" and "Who decides".

## 1. Roles

| Role | Responsibility |
|---|---|
| Product Lead | Decides what BookQu offers, priorities, pricing and when to release |
| Tech Lead | Owns architecture, technical rules, `AGENTS.md`, `docs/*.md`, `.ai/rules` and ADRs; assigns tasks; audits and merges heavy-lane PRs into `integration`; executes releases and production operations |
| Developer | Implements assigned tasks in their own branch; decides implementation details; audits and merges the other developer's light-lane PRs |
| Agent | Turns the developer's instructions into changes; asks the developer running the session; never decides what the table in `AGENTS.md` reserves for another role |

Who holds each role: `AGENTS.md` → "Roles". The agent never contacts other people; the developer coordinates with the Product Lead or Tech Lead outside the repository and confirms the outcome in the session.

## 2. Task types

| Type | Meaning | Needs a requirement change? |
|---|---|---|
| Bug fix | Behaviour violates an existing requirement | No |
| Feature | Adds a capability already accepted in `product.md` / `requirements.md` | No |
| Refactor | Changes structure, not behaviour | No |
| Technical improvement | Security, performance, tests, tooling, observability | No |
| Product change | Changes what BookQu does (new capability, new rule, new policy) | **Yes — Product Lead decision first**, through `docs/product-decisions.md` (§5) |
| Documentation | Changes docs only | No |
| Operational change | Deployment, scheduler, queue, environment, backups | No; update `operations.md` |

A refactor or bug fix that turns out to change behaviour becomes a product change. Never hide a product change inside another type.

## 3. Lanes

Every task runs in one of two lanes. When unsure, use the heavy lane.

| | Light lane | Heavy lane |
|---|---|---|
| **Covers** | UI and copy, tests, non-authority docs, behaviour-preserving refactors, small bug fixes outside the areas on the right | Domain rules (booking, availability, payment, subscription), DB schema, security and auth, tenancy, provider integration, authority docs (`AGENTS.md`, `docs/*.md`, ADRs, `.ai/rules`), product changes |
| **Work item** | Not needed; the PR description is the record | Required: `docs/work/BQ-NNN-slug.md` |
| **Decision** | Developer | Tech Lead before coding (Product Lead for product changes) |
| **Tests** | Related tests while working, full suite once before the PR | Narrow → related → full suite; MySQL suite for concurrency claims |
| **Docs** | Only what the change made wrong | Domain file, requirement or ADR where content changed; item `done` with evidence |

Findings outside the task are recorded as a new work item in either lane, never fixed in passing.

## 4. Lifecycle

```text
instruction ─► lane ─► (heavy: work item + decision) ─► own branch ─► implement + test ─► PR into integration ─► audit (light: other developer · heavy: Tech Lead) ─► merge
```

1. **Capture.** Heavy lane: create `docs/work/BQ-NNN-slug.md` from `docs/work/_TEMPLATE.md` with requirement IDs, domain, current code, target change, invariants at risk, tests and docs to update.
2. **Decide.** Check `AGENTS.md` → "Who decides". If the decision belongs to the Tech Lead, set `status: proposed`, tell the developer, and stop until the developer confirms the decision (`approved` or `rejected` with a reason). If it belongs to the Product Lead, do not create a work item: add a `D-NNN` entry to `docs/product-decisions.md` (§5), tell the developer, and stop. The work item is created only after the Product Lead approves, with `decision: D-NNN`.
3. **Implement.** Small, coherent commits on your own branch. Characterize poorly tested behaviour before changing it. A small related refactor is fine when it directly reduces risk or duplication for this task.
4. **Verify.** Per lane (§3). Record manual checks (UI, payment sandbox, custom domain) in the PR or item.
5. **Document.** Update only the sources whose content changed (§9).
6. **Review.** PR into `integration` with `.github/pull_request_template.md`. A light-lane PR is audited and merged by a developer other than its author. A heavy-lane PR is audited and merged by the Tech Lead; when the Tech Lead is its author, the other developer reviews it first. Nobody merges their own PR without a review by someone else, and every merge needs the four green CI jobs.
7. **Close.** Heavy lane: set `status: done`, add evidence, move the item to `docs/work/done/`. A `rejected` item (including one merged into another item, with a pointer to it) is moved there too, so `docs/work/` holds only open items.

Ask for clarification only when two materially different product behaviours are plausible and the repository cannot decide between them. Difficulty is not a reason to stop; record the decision you made.

## 5. Work items

- One file per item: `docs/work/BQ-NNN-short-slug.md`. IDs are never reused or renumbered.
- `status`: `proposed` → `approved` → `in-progress` → `done` (or `rejected`).
- `priority`: `P0` (security, data integrity, core flow broken) · `P1` (important) · `P2` (improvement).
- `phase`: the roadmap phase (`docs/work/ROADMAP.md`).
- `approval`: `none`, `tech-lead` or `product-lead`. Older items use `needs-lead-approval`; leave them as they are.
- `launch-blocker: true` items must be `done` before production launch.
- Items may be written in English or Indonesian; IDs, file paths and code names stay in English.
- `docs/work/` holds work that is decided or waiting only for the Tech Lead. A question for the Product Lead never becomes a work item before it is approved; `decision: D-NNN` links an item to the approved product decision it implements.

### Product decisions

`docs/product-decisions.md` is the single place for decisions the Product Lead must validate, and the record of product decisions already taken. The Tech Lead shares it outside the repository (for example by chat); the Product Lead does not need repository access.

- **Entry:** a `### D-NNN · title` heading, status, date proposed, whether it blocks the launch, the problem in two to four plain sentences, a numbered proposal, optional alternatives, what applies until it is decided, and the Product Lead's answer. IDs are never reused.
- **Writing:** Indonesian, for a non-technical reader: no file paths, code names or tables, short enough to send one entry per chat message.
- **Who writes:** the Tech Lead, or an agent at the request of the developer running the session. Only the Tech Lead records the Product Lead's answer, with its date.
- **Statuses:** `Menunggu` → `Disetujui` or `Ditolak`. A revision keeps `Menunggu`, updates the proposal and notes "Revisi N".
- **After the answer:** the Tech Lead creates the work items (`decision: D-NNN`) and moves the entry to "Sudah diputuskan" as one line, `- D-NNN · title · outcome and date → item IDs`. That work updates `product.md` and `requirements.md` before code (§8).
- **Untuk diketahui:** Tech Lead decisions that owners or customers will notice are listed for the Product Lead's information; they need no answer.

## 6. Branches, commits, releases

| Branch | Purpose | Who pushes | Changes arrive by |
|---|---|---|---|
| `main` | Production; every push deploys (`.github/workflows/deploy-production.yml`) | Nobody directly | Release PR from `integration` |
| `integration` | Where all finished work is combined and tested before release (ADR-0015) | Nobody directly | PR, four green CI jobs, audit and merge per lane (light: other developer · heavy: Tech Lead) |
| `agathan`, `bagus` | Each developer's own exploration branch | Its owner, freely | Direct push |
| `<name>/<topic>` | Optional short-lived branch for one task | Its owner | Direct push |

Rules for own branches, so they never drift far from `integration` (a branch 90 commits behind is a painful merge):

1. **Sync before you start** a task: merge or rebase the latest `integration` into your branch.
2. **Merge small and often:** one finished task per PR; do not batch many features.
3. **Sync again before the PR**, so conflicts are resolved in your branch, not in `integration`.

Releases: the Product Lead decides; the Tech Lead opens the release PR `integration` → `main`, tags `vMAJOR.MINOR.PATCH` and updates `docs/release-notes/`. Hotfixes branch from `main`, merge into `main` by PR, then merge back into `integration`.

Commits follow Conventional Commits: `type(scope): summary` with types `feat`, `fix`, `refactor`, `test`, `docs`, `chore`, `perf`, `ci`; mention the item when there is one: `fix(payment): reconcile late success (BQ-003)`.

### Parallel work with worktrees

Two agents in one checkout overwrite each other. For parallel work use `git worktree add <folder> -b <branch> origin/integration`, then:

- copy `vendor` into the worktree (`robocopy <main>\vendor <worktree>\vendor /E /MT:16` on Windows). Never link it: PHP resolves a junction, so tests would silently run the main checkout's code. Linking `node_modules` and `public/build` is fine;
- copy `.env`;
- run PHPStan as `php -d opcache.enable_cli=0 vendor/phpstan/phpstan/phpstan.phar analyse --memory-limit=1G` when CLI opcache is on;
- before `git worktree remove`, unlink junctions (`cmd /c rmdir <link>`) so their targets survive.

## 7. Testing

- Run the narrowest test first (`--filter`, one file), then related tests, then `php artisan test --compact`.
- Booking and payment changes cover: stale pending, concurrent booking, cross-tenant access, cross-booking access, duplicate webhook, driver switching, expiry.
- Name tests by behaviour: `test_owner_cannot_access_other_tenant_bookings`.
- A failing test is evidence. Decide whether the code, the requirement or the test is wrong before touching the test.
- "Verified" needs matching evidence. Concurrency claims need the MySQL suite (`vendor/bin/phpunit -c phpunit.mysql.xml`, `mysql` group), not SQLite.
- Add characterization tests before refactoring poorly tested critical code.

## 8. Definition of done

Every change:

- [ ] Lane chosen; decision obtained where the table requires it
- [ ] Implementation complete; validation and authorization handled; tenant isolation checked
- [ ] Tests added or updated; related and full suites pass
- [ ] `vendor/bin/pint --dirty --format agent` run on changed PHP
- [ ] Manual verification recorded where automated tests cannot reach
- [ ] Docs updated per §9
- [ ] Branch synced with `integration`; four CI jobs green

Heavy lane also: requirement identified (or product change decided), invariants in the item still hold, item `done` with evidence.

| Type | Also required |
|---|---|
| Bug fix | Root cause stated; regression test reproduces the bug |
| Feature | Product and requirement exist; UI verified |
| Refactor | Behaviour unchanged (tests before and after); no duplicated rules left behind |
| Product change | `product.md` and `requirements.md` updated before code; Product Lead decided (`D-NNN` approved in `docs/product-decisions.md`) |

## 9. Keeping documentation in sync

Update only what changed:

| When you change… | Update |
|---|---|
| A question or decision for the Product Lead | `docs/product-decisions.md` (§5) |
| What BookQu offers or a release tier | `docs/product.md` |
| Required behaviour or a business rule | `docs/requirements.md` |
| A structural rule or layer boundary | `docs/architecture.md` (+ ADR if significant) |
| How a domain works, its entry points, invariants, tests or status | `docs/domains/<domain>.md` |
| A term or legacy name | `docs/glossary.md` |
| Deployment, scheduler, queue, environment, backups | `docs/operations.md` |
| A rule for editing certain paths | `.ai/rules/<area>.md`, then regenerate `.ai/rules/index.md` (command in `.ai/rules/documentation.md`) |
| Work status | the `BQ-*` item only |
| Local setup steps | `README.md` |

Write an ADR (`docs/adr/_TEMPLATE.md`) when a decision affects several domains, changes an integration or tenancy/concurrency/payment strategy, introduces a new pattern, or would otherwise leave future contributors guessing.

## 10. When sources disagree

Classify the conflict — product, requirement, architecture, implementation drift, test drift or documentation drift — then fix the authoritative source. Do not rewrite docs to hide wrong code, and do not change correct code because an outdated doc says otherwise. Record the resolution in the PR or work item.

## 11. Writing docs for humans and agents

- **Language:** authority docs, rules, ADRs and work-item structure are English; `README.md`, `docs/product-decisions.md`, release notes and all user-facing UI copy are Indonesian.
- Put the answer first; one topic per section; short sentences.
- One rule lives in one place; everywhere else links to it.
- Use stable IDs (`FR-`, `BR-`, `NFR-`, `INV-`, `BQ-`, `ADR-`, `D-`) and real paths, class and route names.
- Name roles, not people, outside the roles table.
- Prefer tables and numbered steps over prose.
- Describe the current system in `docs/domains/`, the target in `docs/architecture.md`; never present the target as already implemented.
- No history in active docs — Git and `docs/work/done/` keep it. Exception: "Sudah diputuskan" in `docs/product-decisions.md` is the record of product decisions.
