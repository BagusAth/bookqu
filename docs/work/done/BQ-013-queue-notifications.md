---
id: BQ-013
title: Send mail and notifications through the queue after commit
type: improvement
status: done
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

Plan approved via [ADR-0013](../../adr/ADR-0013-background-work-on-shared-hosting.md): queued after-commit mail, id-only jobs, cron-driven short-lived worker, 2026-10-30.

## Problem

All mail and notifications are sent synchronously inside checkout requests and webhooks. This slows webhooks (audit BUG-T4-02) and ties provider callbacks to SMTP latency.

## Plan

Make mail/notifications `ShouldQueue` with `afterCommit`; this depends on a working worker on HostData (BQ-020); tests with `Mail::fake()` / `Notification::fake()` asserting they are queued.

S7 approved by lead (2026-11-01): queue the four owner/trial mail notifications and cancellation mail after commit; dispatch the id-only management job asynchronously. Credential-bearing mailables remain synchronous inside that job. Production cron and SMTP verification remain lead actions under BQ-020/021.

## Evidence

S7 (2026-11-01), lead-approved: four owner/trial mail notifications and cancellation mail queue after commit; `RequestManagementDelivery` dispatches the ID-only job asynchronously. Credential-bearing mailables remain synchronous inside the job.
Evidence: paid-webhook regression queues both owner notification and customer delivery without SMTP; existing recipient/stale-family worker regression retained. Production cron/SMTP proof remains BQ-020/021; code completion does not waive that launch gate.
Verification: full SQLite suite 596 tests / 3,123 assertions; Pint and PHPStan pass with unchanged baseline. PR to `agathan` must pass all four CI jobs before guarded merge.
