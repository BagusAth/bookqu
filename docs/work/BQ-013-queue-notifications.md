---
id: BQ-013
title: Send mail and notifications through the queue after commit
type: improvement
status: approved
priority: P1
phase: 4
domain: notifications
requirements: [NFR-PERF-002, NFR-REL-003]
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-020, BQ-067]
---

# BQ-013: Send mail and notifications through the queue after commit

Plan approved via [ADR-0013](../adr/ADR-0013-background-work-on-shared-hosting.md): queued after-commit mail, id-only jobs, cron-driven short-lived worker, 2026-10-30.

## Problem

All mail and notifications are sent synchronously inside checkout requests and webhooks (only the unused `BookingPaidNotification` implements `ShouldQueue`). This slows webhooks (audit BUG-T4-02) and ties provider callbacks to SMTP latency.

## Plan

Make mail/notifications `ShouldQueue` with `afterCommit`; this depends on a working worker on HostData (BQ-020); tests with `Mail::fake()` / `Notification::fake()` asserting they are queued.

## Evidence

_Fill in when done._
