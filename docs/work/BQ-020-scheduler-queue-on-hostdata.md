---
id: BQ-020
title: Verify scheduler and queue execution on HostData
type: ops
status: in-progress
priority: P0
phase: 5
domain: —
requirements: [NFR-REL-003]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-020: Verify scheduler and queue execution on HostData

Strategy decided in [ADR-0013](../adr/ADR-0013-background-work-on-shared-hosting.md): two cron entries every minute and in-process schedule entries (`Schedule::call()`/`job()`) because `proc_open` is disabled, 2026-10-30. Tech Lead checks the hosting cron panel.

## Problem

Payment expiry and subscription expiry depend on `schedule:run` every minute; queued work needs a worker. HostData disables `proc_open` and long-running processes; whether cron entries exist is unknown.

## Plan

Tech Lead checks the hosting cron panel; configure `schedule:run` every minute and a cron-driven `queue:work --stop-when-empty --max-time=50`; verify with `schedule:list` and a test job; record the evidence.

Approved by the Tech Lead (2026-11-01): use named in-process callbacks with overlap protection, publish a minute heartbeat, and fail `/up` only when an existing heartbeat is older than five minutes. Never-seen heartbeat remains healthy for first deployment; cron verification is still required before launch.

## Evidence

Code ready (2026-11-01): named in-process callbacks, overlap locks, minute heartbeat and `/up` stale-heartbeat check. `OperationsReadinessTest` passes with `proc_open` disabled; `schedule:list` shows the original expiry frequencies plus heartbeat.
Still in-progress: the Tech Lead must install both cron entries from operations §4, prove one worker email, and record server evidence before release. No hosting change was executed by the agent.
