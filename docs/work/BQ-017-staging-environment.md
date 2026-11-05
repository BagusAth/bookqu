---
id: BQ-017
title: Create a staging environment
type: ops
status: in-progress
priority: P0
phase: 5
domain: —
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-017: Create a staging environment

Environment model decided in [ADR-0015](../adr/ADR-0015-environments-and-release.md): staging on a HostData subdomain, deployed from `integration` after CI (ADR-0015 amendment), 2026-10-30. Tech Lead provisions the subdomain and secrets.

## Problem

There is no staging environment; every push to `main` deploys straight to `bookqu.my.id` (`docs/operations.md` §3).

## Plan

Tech Lead provisions a staging subdomain on HostData (database, `.env` with sandbox payment keys, mail sandbox) and GitHub secrets; the agent adds a staging deploy workflow (for example on push to `integration` or a `staging` branch).

## Evidence

2026-11-01: deferred until after launch; waiting for the Tech Lead to provide the staging subdomain, database and secrets. The launch-blocker flag is unchanged; the Tech Lead decides its release-gate disposition.


2026-11-04: agent scope implemented in deploy-staging.yml. Integration pushes run the reusable CI suite exactly once and deploy only after all checks succeed and the repository variable STAGING_ENABLED is true. Releases use staging-only shared configuration and storage; a failed smoke check restores the previous code link. Schema rollback is manual per ADR-0015.

Tech Lead setup:
- Create the GitHub environment staging and restrict deployment to integration.
- Add environment secrets STAGING_SSH_HOST, STAGING_SSH_USER, STAGING_SSH_PORT, STAGING_SSH_KEY, STAGING_SSH_KNOWN_HOSTS (verified host key) and STAGING_APP_ROOT (absolute path ending /bookqu-staging).
- Add environment variable STAGING_URL (HTTPS origin, without trailing slash). Set repository variable STAGING_ENABLED=true after provisioning.
- Provision HostData HTTPS subdomain, dedicated staging database/user, and a docroot pointing to STAGING_APP_ROOT/current/public. Place a private shared/.env with APP_ENV=staging, APP_DEBUG=false, the exact APP_URL, a staging APP_KEY, sandbox payment credentials, SMTP sandbox and database queue. Set up the staging scheduler and queue cron with the current symlink as the app root.
- Verify the first actual deployment and one sandbox booking through checkout. The workflow does not deploy from this PR.

Verification: parsed both workflow YAML files, checked the integration branch and reusable-test gates, and ran bash syntax checks on runner and remote scripts. SQLite 715 tests, Pint and PHPStan passed.
