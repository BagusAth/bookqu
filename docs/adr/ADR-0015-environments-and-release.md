# ADR-0015: Environments, deployment and release

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-017 (staging), BQ-018 (gated deploy + backup), BQ-019, BQ-027 (branches), BQ-028 (Phase 2 package)

## Context

Every push to `main` deploys to `bookqu.my.id` without waiting for tests and runs `migrate --force` without a database backup; rollback restores code only. There is no staging environment. `agathan` is the working branch; 15 branches exist, several merged and inactive. Two developers work with AI agents.

## Decision

1. **Four environments.** Local (Laragon), CI (GitHub Actions, SQLite + MySQL), staging (HostData subdomain with sandbox payment keys and a mail sandbox — BQ-017), production. Secrets live only in each environment's `.env` / GitHub secrets.
2. **Branches.**
   - `main` = production. It is protected: PR only, green CI, lead approval.
   - `agathan` = integration branch, deployed to staging after CI passes.
   - Work happens on `bq-NNN-slug` branches with PRs into `agathan`.
   - Hotfixes branch from `main`, merge into `main` by PR, then back-merge into `agathan`.
   - Merged inactive branches get an `archive/<name>` tag and are then deleted. The lead runs that clean-up and decides on the unmerged ones (BQ-027).
3. **Gated deploy.** The production deploy runs only for a commit whose CI (SQLite and MySQL jobs) passed. It takes a gzipped, timestamped `mysqldump` (permission 600) before `migrate --force`, uses maintenance mode around migration, and has a documented restore (BQ-018, BQ-019).
4. **Backward-compatible migrations.** Because rollback restores code, not schema, each release's migrations must work with the previous release's code (expand → migrate data → contract in a later release). Destructive steps, such as dropping legacy token columns (ADR-0006), ship in a later release than the code that stops using them, after a verified backup.
5. **Releases.** A release PR from `agathan` to `main`, a semantic version tag `vX.Y.Z` on `main`, a `CHANGELOG.md` entry, a production smoke test (`docs/operations.md` §9). v1.0.0 is the launch (G5).
6. **Production configuration is checked**, not assumed: `APP_DEBUG=false`, HTTPS URL, real mail transport (never a logging transport with live credentials), production payment keys and webhook URLs, cron entries (ADR-0013) — the launch checklist in operations §8.

## Consequences

- Nothing reaches production untested or without a restorable backup.
- Staging becomes the place for sandbox payments and UAT (Phase 4 acceptance).
- Lead actions: create the staging subdomain and secrets, set branch protection, decide branch clean-up (G3/G4).
