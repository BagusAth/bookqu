---
id: BQ-017
title: Create a staging environment
type: ops
status: proposed
priority: P0
phase: 4
domain: —
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: true
depends-on: []
---

# BQ-017: Create a staging environment

## Problem

There is no staging environment; every push to `main` deploys straight to `bookqu.my.id` (`docs/operations.md` §3).

## Plan

Lead provisions a staging subdomain on HostData (database, `.env` with sandbox payment keys, mail sandbox) and GitHub secrets; the agent adds a staging deploy workflow (for example on push to `agathan` or a `staging` branch).

## Evidence

_Fill in when done._
