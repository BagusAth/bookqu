# ADR-0013: Background work on shared hosting

> **Status:** Accepted
> **Date:** 2026-10-30
> **Decider:** agathan (architecture lead) — delegated to the agent's recommendation on 2026-10-30
> **Work item:** BQ-020 (hosting setup), BQ-013 (queued mail), BQ-071, BQ-028 (Phase 2 package)

## Context

HostData shared hosting disables `proc_open` and long-running processes. Laravel's `Schedule::command()` runs each command in a child process (Symfony Process → `proc_open`), so `schedule:run` may start but its command events can fail. Whether cron exists is unknown (BQ-020). Mail is sent synchronously inside checkout requests and webhooks (BQ-013, BUG-T4-02). ADR-0009 needs a sweep every minute; ADR-0006 needs post-commit delivery.

## Options

1. **Keep everything synchronous.** Webhooks wait on SMTP; failures surface to customers and providers.
2. **Move to a VPS now.** Solves workers, but adds cost and operations before launch.
3. **Cron-driven, in-process scheduling and a short-lived database-queue worker.** Chosen, with explicit triggers to move.

## Decision

1. **Two cron entries, every minute:** `php artisan schedule:run` and `php artisan queue:work --stop-when-empty --max-time=50 --tries=3`. Cron starts PHP directly, so neither needs `proc_open`.
2. **Scheduled work runs in-process.** Schedule entries use `Schedule::call()` / `Schedule::job()` invoking Actions, not `Schedule::command()`, unless BQ-020 proves child processes work on the host. Overlap protection uses cache locks.
3. **Queued side effects after commit.** Mail and notifications implement `ShouldQueue` and dispatch after commit; jobs carry ids, never raw links, tokens or request objects (ADR-0006). Minute-level delay is accepted.
4. **Every job and sweep is idempotent and tenant-scoped**: one tenant per transaction (ADR-0005), tenant context through the runner (ADR-0010), retries safe by design. Failed jobs are kept in `failed_jobs`; retry only after confirming idempotency (operations §4).
5. **Health is observable.** The scheduler records a heartbeat; a missing heartbeat or a growing `failed_jobs` count raises an alert (BQ-021). No cron is a launch blocker, not a reason to fall back to synchronous mail.
6. **Move to a VPS when** a job needs sub-minute latency, the queue regularly exceeds what a 50-second run drains, the host kills processes, or a persistent worker/websocket becomes a product need (architecture §14).

## Consequences

- Webhooks and checkout respond without waiting for SMTP.
- BQ-020 must prove both cron entries and one queued job on HostData before BQ-013 ships.
- `routes/console.php` entries move from `Schedule::command()` to in-process calls in BQ-020.
