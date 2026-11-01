---
id: BQ-017
title: Create a staging environment
type: ops
status: approved
priority: P0
phase: 4
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
