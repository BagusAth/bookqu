---
id: BQ-020
title: Verify scheduler and queue execution on HostData
type: ops
status: approved
priority: P0
phase: 4
domain: —
requirements: [NFR-REL-003]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-020: Verify scheduler and queue execution on HostData

Strategy decided in [ADR-0013](../adr/ADR-0013-background-work-on-shared-hosting.md): two cron entries every minute and in-process schedule entries (`Schedule::call()`/`job()`) because `proc_open` is disabled, 2026-10-30. Lead checks the hosting cron panel.

## Problem

Payment expiry and subscription expiry depend on `schedule:run` every minute; queued work needs a worker. HostData disables `proc_open` and long-running processes; whether cron entries exist is unknown.

## Plan

Lead checks the hosting cron panel; configure `schedule:run` every minute and a cron-driven `queue:work --stop-when-empty --max-time=50`; verify with `schedule:list` and a test job; record the evidence.

## Evidence

_Fill in when done._
