---
id: BQ-013
title: Send mail and notifications through the queue after commit
type: improvement
status: proposed
priority: P1
phase: 4
domain: notifications
requirements: [NFR-PERF-002, NFR-REL-003]
invariants: []
launch-blocker: false
needs-lead-approval: true
depends-on: [BQ-020]
---

# BQ-013: Send mail and notifications through the queue after commit

## Problem

All mail and notifications are sent synchronously inside checkout requests and webhooks (only the unused `BookingPaidNotification` implements `ShouldQueue`). This slows webhooks (audit BUG-T4-02) and ties provider callbacks to SMTP latency.

## Plan

Make mail/notifications `ShouldQueue` with `afterCommit`; this depends on a working worker on HostData (BQ-020); tests with `Mail::fake()` / `Notification::fake()` asserting they are queued.

## Evidence

_Fill in when done._
