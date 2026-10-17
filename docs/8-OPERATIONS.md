# BookQu Operations Guide

> **Document Status:** Active
> **Version:** 1.1
> **Authority:** Runtime, deployment, and operational procedures
> **Product Definition:** `docs/2-PRODUCT.md`
> **Requirements:** `docs/3-REQUIREMENT.md`
> **Architecture:** `docs/4-ARCHITECTURE.md`
> **Development:** `docs/5-DEVELOPMENT.md`
> **Tracker:** `docs/6-TRACKER.md`
> **System Design:** `docs/7-SYSTEM-DESIGN.md`
> **ADR:** `docs/adr/`
> **Last Updated:** 2026-09-30
>
> This document defines how BookQu is configured, deployed, scheduled, verified, monitored, and operated.
>
> It describes operational procedures rather than product requirements or development workflow.

---

# 1. Purpose

This document answers:

> **How should BookQu be run and verified as an operational system?**

It covers:

```text id="fud0er"
Environment configuration
Application startup
Database
Cache
Queue
Scheduler
Payment expiration
Subscription expiration
Deployment
Production verification
Logging
Failure handling
Backup / recovery
Maintenance
Operational troubleshooting
```

Product behavior remains defined by:

```text id="gpx8to"
docs/2-PRODUCT.md
docs/3-REQUIREMENT.md
```

Development workflow remains defined by:

```text id="s0m7xb"
docs/5-DEVELOPMENT.md
```

---

# 2. Operational Model

BookQu is a Laravel application with the following runtime dependencies:

```text id="wdbs4h"
Web Application
    ↓
PHP / Laravel
    ↓
Relational Database
    ↓
Cache
    ↓
Queue
    ↓
External Services
```

Current application configuration indicates:

```text id="qp6vcl"
Database:
MySQL

Session:
database

Queue:
database

Cache:
database

Frontend build:
Vite / Node.js

Payment provider:
Midtrans integration

Mail:
Laravel mail system
```

The exact production infrastructure may differ from the local environment.

Operational configuration must therefore be verified against the actual deployment environment.

---

# 3. Runtime Environment

The application currently expects a PHP runtime compatible with the project requirements.

The repository CI currently verifies against:

```text id="g7wrsy"
PHP 8.3
Node.js 22
```

The production environment should use a supported PHP version compatible with the application and dependencies.

Do not deploy with a different major PHP version without verifying compatibility.

---

# 4. Application Environment

Important Laravel environment values include:

```text id="yqj9f0"
APP_ENV
APP_KEY
APP_DEBUG
APP_URL
APP_LOCALE
APP_FALLBACK_LOCALE
```

The current example configuration specifies:

```text id="g0v2fk"
APP_LOCALE=id
APP_FALLBACK_LOCALE=id
```

Production configuration should not use development-oriented debug settings.

At minimum, production must ensure:

```text id="1d36xy"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<secure application key>
APP_URL=<actual public URL>
```

The exact values must be supplied through the deployment environment.

---

# 5. Secret Management

Secrets must not be committed to Git.

Sensitive values include:

```text id="zv3q0e"
APP_KEY
Database credentials
Payment credentials
Webhook secrets
Mail credentials
Storage credentials
Cloud credentials
```

Production secrets should be supplied through the server or deployment environment.

Never place production secrets into:

```text id="p1y1w3"
Source code
Git commits
Public documentation
Frontend JavaScript
Public configuration
```

---

# 6. Application Configuration Cache

After changing production environment configuration, Laravel configuration cache should be rebuilt.

Typical verification:

```bash
php artisan config:clear
php artisan config:cache
```

After configuration changes, verify that the application is reading the expected values.

Do not assume an old worker or long-running process has automatically reloaded changed configuration.

---

# 7. Route Cache

When deploying production builds, route caching may be used where compatible with the current application.

Verification command:

```bash
php artisan route:cache
```

If route definitions have changed and a cached route configuration exists, regenerate the cache during deployment.

---

# 8. Database

The application currently uses:

```text id="xgq0z2"
DB_CONNECTION=mysql
```

Production database configuration must provide the correct:

```text id="m30kth"
Host
Port
Database
Username
Password
```

The production database must be treated as authoritative persistent state for:

```text id="el02l7"
Tenant data
Services
Schedules
Bookings
Customers
Payments
Refunds
Subscriptions
Reviews
Configuration
```

---

# 9. Database Migration

Database schema changes must be applied through Laravel migrations.

Before deployment:

```bash
php artisan migrate:status
```

Then, when the release is approved:

```bash
php artisan migrate --force
```

Never modify already-applied historical migrations simply to change production state.

Create a new migration instead.

---

# 10. Database Safety

Before production schema changes:

```text id="8p5n0w"
[ ] Backup available
[ ] Migration reviewed
[ ] Affected tables identified
[ ] Expected data impact understood
[ ] Rollback / recovery strategy understood
[ ] Application version compatible with schema
```

Database migrations must be treated as production changes, not merely code changes.

---

# 11. Queue Configuration

The example environment uses:

```text id="60p1xv"
QUEUE_CONNECTION=database
```

This means queued jobs are persisted through the database queue system.

Production environments using this configuration require an active queue worker.

Conceptually:

```text id="nhw0hk"
Application
    ↓
Dispatch Job
    ↓
Database Queue
    ↓
Queue Worker
    ↓
Job Execution
```

If the queue worker is not running, queued work can remain pending even though the HTTP request succeeded.

---

# 12. Queue Worker Operations

The exact process manager is deployment-specific.

Common operational models include:

```text id="xb80ry"
Supervisor
systemd
Container process
Managed process platform
```

The important operational requirement is:

```text id="l3h2yf"
Queue worker must remain running.
```

After deployment or worker restart, verify that workers can process jobs.

Example manual verification:

```bash
php artisan queue:work --once
```

Use the appropriate long-running worker configuration for production rather than relying on manual execution.

---

# 13. Queue Failure Handling

Operational checks should consider:

```text id="fcmqil"
Pending jobs
Failed jobs
Repeatedly failing jobs
External-service failures
Worker crashes
Database connection failures
```

Useful commands include:

```bash
php artisan queue:failed
php artisan queue:retry all
```

Only retry failed jobs after confirming that the operation is safe to repeat.

Payment and refund-related jobs must be treated as potentially idempotent-sensitive operations.

---

# 14. Cache Configuration

The example environment uses:

```text id="t7tq9q"
CACHE_STORE=database
```

Cache is derived state.

It must not become the authoritative source for:

```text id="fknbzh"
Booking ownership
Payment truth
Authorization
Tenant identity
```

For booking availability, cache must always remain consistent with the underlying booking state.

---

# 15. Cache Invalidation

Availability-related mutations may require cache invalidation.

Important events include:

```text id="v6sl0p"
Booking creation
Booking cancellation
Booking rescheduling
Payment expiration
Schedule changes
Availability changes
```

When investigating incorrect availability:

```bash
php artisan cache:clear
```

may be useful as a diagnostic or recovery step, but it must not substitute for fixing incorrect cache invalidation logic.

---

# 16. Scheduler

BookQu uses the Laravel scheduler.

Current scheduled operations include:

```text id="xjba2z"
app:check-expired-subscriptions
→ Daily

bookings:expire-payments
→ Every 15 minutes
```

The scheduler source is:

```text
routes/console.php
```

---

# 17. Production Scheduler Requirement

Production must invoke Laravel's scheduler regularly.

The current deployment guidance uses:

```cron
* * * * * cd /path/to/bookqu && php artisan schedule:run >> /dev/null 2>&1
```

The cron process should run at least once per minute so Laravel can determine which scheduled commands are due.

---

# 18. Scheduled Payment Expiration

The command:

```bash
php artisan bookings:expire-payments
```

handles payment expiration processing.

Its purpose includes:

```text id="hry2yr"
Find expired payment windows
Update payment state
Cancel affected booking
Release affected availability
Invalidate relevant cache
```

The scheduler invokes this process every 15 minutes.

---

# 19. Payment Expiration Verification

Available operational commands include:

```bash
php artisan schedule:list
```

and:

```bash
php artisan bookings:expire-payments --dry-run
```

Manual execution:

```bash
php artisan bookings:expire-payments
```

Manual execution should be used carefully in production because it mutates application state.

---

# 20. Scheduled Subscription Expiration

The application also registers:

```bash
php artisan app:check-expired-subscriptions
```

This scheduled operation runs daily.

It evaluates subscription expiration and updates applicable subscription state.

Verify scheduling with:

```bash
php artisan schedule:list
```

---

# 21. Scheduler Troubleshooting

If scheduled behavior does not occur:

```text id="aj1v9f"
1. Check system cron.
2. Check `php artisan schedule:list`.
3. Check application logs.
4. Run the scheduled command manually.
5. Verify database connectivity.
6. Verify the server PHP binary.
7. Verify the application path used by cron.
```

A common operational failure is a cron process using a different PHP binary or application directory than the interactive shell.

---

# 22. Mail Operations

The example environment currently specifies:

```text id="aqr2o8"
MAIL_MAILER=log
```

This is suitable for development-oriented logging but must not automatically be treated as a production mail configuration.

Production mail delivery requires a configured mail transport.

After changing mail configuration:

```text id="v5s40d"
[ ] Credentials configured
[ ] Sender configured
[ ] Configuration cache refreshed
[ ] Test email sent
[ ] Delivery verified
```

---

# 23. Payment Provider Operations

BookQu integrates with Midtrans.

Payment-provider configuration must be present in the production environment where payment functionality is enabled.

Operational verification should include:

```text id="zgn9s3"
Provider credentials
Payment creation
Provider response
Webhook endpoint
Webhook verification
Payment status synchronization
Refund flow where applicable
```

Never expose provider secrets to the frontend.

---

# 24. Webhook Operations

Payment webhooks are operationally critical.

Verify:

```text id="q0a8t7"
Webhook URL reachable
HTTPS enabled
Provider verification works
Application can reach database
Webhook processing is idempotent
Webhook failures are logged
```

When investigating payment inconsistency, inspect:

```text id="ts2d7c"
Payment record
Booking record
Webhook logs
Application logs
Provider-side transaction state
```

Do not resolve a payment discrepancy solely from the browser redirect state.

---

# 25. Application Logs

Laravel application logs should be treated as a primary troubleshooting source.

Operationally useful information may include:

```text id="6h1j92"
Timestamp
Exception
Tenant
Booking
Payment
Job
External provider
Failure reason
```

Logs must not contain:

```text id="u0x3qp"
Passwords
API secrets
Private tokens
Payment credentials
Sensitive authentication material
```

---

# 26. Production Logging

Production logging should balance:

```text id="5g7f2a"
Troubleshooting value
+
Security
+
Storage cost
```

Do not enable verbose debugging simply because an issue is difficult to diagnose.

Prefer targeted logging and controlled diagnostics.

---

# 27. Application Health Verification

After deployment, verify at minimum:

```text id="2zhnb4"
[ ] Application loads
[ ] Authentication works
[ ] Public tenant page works
[ ] Service listing works
[ ] Availability loads
[ ] Booking can be created
[ ] Payment flow can be initiated
[ ] Owner portal loads
[ ] Database queries succeed
[ ] Queue worker is running
[ ] Scheduler is running
[ ] Logs are writable
```

Payment production verification should use an approved test or sandbox procedure where applicable.

---

# 28. Deployment Verification

Every deployment should verify:

```text id="k72e9f"
Code version
Environment configuration
Dependencies
Database schema
Configuration cache
Route cache where used
Frontend assets
Queue worker
Scheduler
External integrations
```

Recommended sequence:

```text id="wj3rnr"
Deploy Code
    ↓
Install Dependencies
    ↓
Build Frontend
    ↓
Run Migrations
    ↓
Refresh Configuration
    ↓
Refresh Routes where applicable
    ↓
Restart / Reload Long-Running Processes
    ↓
Verify Scheduler
    ↓
Verify Queue
    ↓
Run Smoke Tests
```

The exact zero-downtime mechanism depends on the hosting environment.

---

# 29. Dependency Installation

The repository CI installs PHP dependencies using:

```bash
composer install --prefer-dist --no-interaction --no-progress
```

Frontend dependencies are installed using:

```bash
npm ci
```

Frontend assets are built using:

```bash
npm run build
```

Production deployment should use a reproducible dependency installation process appropriate to the deployment environment.

---

# 30. Application Key

`APP_KEY` is required for Laravel application encryption and related framework functionality.

Never regenerate the production application key casually.

Changing an existing production key can invalidate encrypted application data and active sessions depending on the application configuration.

Only rotate it through a deliberate operational procedure.

---

# 31. Maintenance Mode

Laravel maintenance mode may be used for operations that cannot safely be performed while users are active.

Example:

```bash
php artisan down
```

After the operation:

```bash
php artisan up
```

Use maintenance mode selectively.

Prefer deployment strategies that minimize downtime where the hosting environment supports them.

---

# 32. Backup Strategy

The repository documents the application but does not by itself guarantee production backup infrastructure.

Production must maintain a backup strategy for at least:

```text id="z2o7he"
Database
User/business assets
Critical configuration/secrets through an appropriate secure mechanism
```

Backup policy should define:

```text id="y1y3a6"
Frequency
Retention
Storage location
Encryption
Access control
Restore procedure
Restore verification
```

The exact provider and implementation must be documented once established.

---

# 33. Backup Verification

A backup is not considered operationally trustworthy merely because a backup job reports success.

Periodically verify that:

```text id="y3rj4b"
[ ] Backup exists
[ ] Backup is readable
[ ] Backup is complete
[ ] Restore can be performed
[ ] Restored database is usable
[ ] Critical assets can be restored
```

Restore testing should occur separately from the normal application environment where practical.

---

# 34. Recovery Principles

When production data becomes inconsistent:

```text id="3t4yo3"
1. Stop further destructive actions.
2. Identify affected domain.
3. Preserve logs/evidence.
4. Determine whether application or external provider is authoritative.
5. Correct state through a controlled operation.
6. Verify related records.
7. Document root cause.
```

Do not directly edit database rows in production as the first response unless a controlled emergency procedure requires it.

---

# 35. Booking Incident Response

For a suspected booking incident:

```text id="a6n6nm"
Check booking record
      ↓
Check schedule
      ↓
Check related payment
      ↓
Check tenant ownership
      ↓
Check relevant application logs
      ↓
Check concurrent/duplicate operations
      ↓
Check cache
```

Particular incidents include:

```text id="w66yul"
Double booking
Booking unexpectedly cancelled
Expired booking still blocking slot
Paid booking not recognized
Rescheduled booking inconsistency
Cross-tenant booking access
```

---

# 36. Payment Incident Response

For a payment discrepancy:

```text id="n4yw1x"
1. Identify booking.
2. Identify payment record.
3. Identify external provider reference.
4. Check provider state.
5. Check webhook / verification logs.
6. Compare booking and payment states.
7. Determine whether refund state is involved.
```

Never assume:

```text id="9uypdy"
Customer says payment succeeded
```

is sufficient evidence that the application should mark the payment as successful.

---

# 37. Queue Incident Response

When queued work is not executing:

```text id="l1mx2t"
Check:
    ↓
Queue connection
    ↓
Database queue records
    ↓
Worker process
    ↓
Worker logs
    ↓
Failed jobs
    ↓
Database connectivity
```

Relevant commands may include:

```bash
php artisan queue:failed
php artisan queue:work --once
```

Do not blindly retry large numbers of jobs without checking idempotency and business impact.

---

# 38. Cache Incident Response

Symptoms of cache problems may include:

```text id="3w47rf"
Availability appears stale
Old service information remains visible
Recently cancelled slot remains unavailable
Tenant-specific data appears incorrect
```

First determine whether the root problem is:

```text id="0dz0lp"
Incorrect cache key
Missing invalidation
Incorrect source data
Incorrect query
Unexpected cache persistence
```

Clearing the cache can be a diagnostic step, but it does not replace correcting the underlying issue.

---

# 39. Database Incident Response

For database errors:

```text id="z1uw4j"
Check database connectivity
      ↓
Check Laravel logs
      ↓
Check migration state
      ↓
Check recent deployment
      ↓
Check database server health
      ↓
Check affected queries / constraints
```

Do not immediately disable database constraints to force an operation to succeed.

---

# 40. Security Incident Response

For suspected unauthorized access:

```text id="c33j20"
1. Identify affected endpoint.
2. Identify affected tenant/resource.
3. Preserve relevant logs.
4. Disable or contain the affected path if required.
5. Revoke compromised credentials/tokens where appropriate.
6. Correct authorization logic.
7. Add regression coverage.
8. Review related endpoints.
```

Security incidents should be treated as P0 operational events.

---

# 41. Production Debugging Rule

Do not enable:

```text id="lfgdqb"
APP_DEBUG=true
```

in production merely to obtain more information.

Prefer:

```text id="tq1s3p"
Logs
Controlled reproduction
Targeted instrumentation
Staging reproduction
```

Sensitive production information should not be exposed through browser error pages.

---

# 42. Scheduled Maintenance Checklist

For planned maintenance:

```text id="5g3fzb"
[ ] Identify affected services
[ ] Confirm maintenance scope
[ ] Confirm backup
[ ] Confirm rollback/recovery plan
[ ] Check active bookings/payments if relevant
[ ] Apply maintenance
[ ] Run verification
[ ] Check queue
[ ] Check scheduler
[ ] Check logs
[ ] Restore normal service
[ ] Record result
```

---

# 43. Production Release Checklist

Before release:

```text id="6xg6yl"
[ ] Correct commit/version identified
[ ] CI passed
[ ] Environment values verified
[ ] Database migration reviewed
[ ] Backup available
[ ] Deployment procedure ready
[ ] Queue worker plan ready
[ ] Scheduler verified
```

After release:

```text id="5kfn2j"
[ ] Application responds
[ ] Database works
[ ] Public booking works
[ ] Owner portal works
[ ] Payment flow works
[ ] Queue works
[ ] Scheduler works
[ ] Logs show no unexpected critical errors
[ ] Critical user flow smoke-tested
```

---

# 44. CI Verification

The current GitHub Actions workflow verifies:

```text id="0n9r5a"
PHP 8.3
Node.js 22
Composer dependencies
Node dependencies
Frontend build
Laravel test suite
```

The workflow runs for pushes and pull requests targeting:

```text id="i4h3f6"
Refactor
main
```

The CI sequence includes:

```bash
composer install --prefer-dist --no-interaction --no-progress
npm ci
npm run build
php artisan test
```

A production release should not intentionally bypass a failing required CI verification without a documented reason.

---

# 45. Manual Production Verification

Automated tests cannot replace every production check.

Manual verification may be required for:

```text id="a5dw4h"
External payment provider
Production mail
Domain routing
Custom domain
SSL/TLS
File storage
Queue workers
Scheduler
Browser-specific behavior
```

Manual results should be recorded when they represent an important release gate.

---

# 46. Environment Separation

At minimum, distinguish:

```text id="m1v01j"
Local
CI
Staging / Test
Production
```

Do not copy production secrets into local or CI environments unnecessarily.

Likewise, do not use local infrastructure assumptions as evidence that production configuration is correct.

---

# 47. Operational Change Rule

Changes to:

```text id="5j4v4q"
Cron
Queue workers
Environment variables
Payment credentials
Mail
Storage
Database infrastructure
Cache
Deployment
Monitoring
```

are operational changes.

They should be reflected in this document when they become part of the permanent operating model.

---

# 48. Operational Documentation Rule

Do not put:

```text id="l7cz7n"
Production cron instructions
Queue worker configuration
Backup procedures
Incident response
Deployment runbooks
```

into:

```text
docs/5-DEVELOPMENT.md
```

Likewise, do not put implementation-specific business rules here when they belong in:

```text
docs/7-SYSTEM-DESIGN.md
```

---

# 49. Unknown Operational Infrastructure

Where BookQu's exact hosting infrastructure is not established in the repository, this document must not invent it.

For example, the repository does not itself establish a universal production provider, process manager, or backup vendor.

Therefore these remain deployment-specific until explicitly standardized:

```text id="k6gj1f"
Hosting provider
Web server
Process supervisor
Container strategy
Backup provider
Monitoring provider
CDN
Object storage provider
```

Once standardized, the operational details should be added here.

---

# 50. Operational Evidence

Operational claims should be based on evidence such as:

```text id="tv0sq4"
Command output
Application logs
CI result
Scheduler status
Queue status
Database status
Provider dashboard
Successful smoke test
Successful restore test
```

Do not mark a production capability verified merely because its configuration file exists.

---

# 51. Operational Status

Current operational status should be summarized in:

```text id="dk0e4c"
docs/6-TRACKER.md
```

This document contains the procedures.

The tracker contains whether those procedures have been verified.

---

# 52. Operations and System Design Boundary

Use:

```text id="0e5b2g"
docs/7-SYSTEM-DESIGN.md
```

for:

```text
How payment expiration changes booking state
How availability is calculated
How tenant resolution works
How booking state transitions work
```

Use:

```text id="yvv4mz"
docs/8-OPERATIONS.md
```

for:

```text
How the expiration command is scheduled
How the queue worker is run
How production configuration is applied
How an incident is investigated
```

This distinction prevents operational procedures from becoming business-rule documentation.

---

# 53. Change Verification Matrix

| Change               | Required Operational Verification                |
| -------------------- | ------------------------------------------------ |
| Application code     | Smoke test + logs                                |
| Database migration   | Migration status + affected flow                 |
| Environment variable | Config refresh + targeted verification           |
| Scheduler            | `schedule:list` + scheduled command verification |
| Queue                | Worker status + test job                         |
| Payment integration  | Provider + webhook verification                  |
| Mail                 | Delivery test                                    |
| Cache                | Targeted data verification                       |
| Asset storage        | Upload/read verification                         |
| Deployment           | Full smoke test                                  |
| Backup               | Backup and restore verification                  |

---

# 54. Emergency Recovery Principle

When service availability and data correctness conflict:

> **Protect data integrity first, then restore service safely.**

Do not restore service by knowingly creating:

```text id="qqv2b5"
Duplicate bookings
Incorrect payment state
Cross-tenant access
Corrupted subscription state
Invalid refund state
```

A controlled degraded state is preferable to creating unrecoverable data corruption.

---

# 55. Final Operational Model

BookQu operations can be summarized as:

```text id="2j5t2a"
                PRODUCTION
                    │
        ┌───────────┼───────────┐
        │           │           │
     Web App      Queue      Scheduler
        │           │           │
        └───────────┼───────────┘
                    │
                 Database
                    │
          ┌─────────┴─────────┐
          │                   │
        Cache             External Services
                              │
                       ┌──────┴──────┐
                       │             │
                    Midtrans        Mail
```

Critical operational chain:

```text id="f9txqn"
Deploy
  ↓
Application
  ↓
Database
  ↓
Queue
  ↓
Scheduler
  ↓
External Integrations
  ↓
Verification
  ↓
Monitoring
```

---

# 56. Final Principles

BookQu operations follow these principles:

```text id="88rw09"
1. Production configuration must be explicit.
2. Secrets must remain outside source control.
3. Database is authoritative persistent state.
4. Cache is derived state.
5. Queue workers must be operational when queued work is used.
6. Scheduler must run reliably.
7. Payment operations require external-state verification.
8. Production debugging must not expose sensitive information.
9. Backups must be restorable, not merely created.
10. Operational claims require evidence.
11. Data integrity has priority over convenience.
12. Operational procedures belong here, not in development documentation.
```

The final operational rule is:

> **Do not consider BookQu operationally healthy merely because the application loads.**

A healthy deployment must also have working persistence, queue processing, scheduler execution, external integrations, and the verification needed for critical customer and owner flows.
