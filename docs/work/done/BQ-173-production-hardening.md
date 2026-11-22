---
id: BQ-173
title: Harden mailed links, scheduler locks, alerts and upload backups before the first release
type: bug-fix
status: done
priority: P0
phase: 5
domain: auth
requirements: [FR-AUTH-007]
invariants: []
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

## Problem

The production-readiness audit of `integration` on 22 Nov 2026 found:

1. **Host header poisoning of mailed links.** In a web request Laravel builds URLs from the request's Host header, and `AppServiceProvider` even forced that root (`URL::forceRootUrl(request()->getSchemeAndHttpHost())`). A `POST /forgot-password` with `Host: evil.example` mailed `http://evil.example/reset-password/<token>`. A victim who clicks it hands the reset token to the attacker (account takeover). The verification mail and the admin payout-request notification used the same root. This is exploitable wherever the server answers unknown hosts, which custom domains make likely.
2. **Scheduler locks held for a day.** Every job used `withoutOverlapping()`, whose lock lasts 1440 minutes. A run that the host kills leaves the lock behind, so `bookings:expire-payments` would stop for 24 hours.
3. **No error alert.** `Log::critical` (refund owed, rejected provider outcome) and unhandled exceptions only reached `storage/logs/laravel.log` (BQ-021).
4. **Uploads not backed up.** The deploy dumped the database but not `storage/app` (gallery, logos, payout proofs) (BQ-019).

## Plan

1. Build the password reset and verification links from `APP_URL` (`ResetPassword::createUrlUsing`, `VerifyEmail::createUrlUsing`, signed on the APP_URL origin) and drop the forced request root. Build the stored links of notifications sent inside a web request (admin payout request, the owner booking fallbacks) from `APP_URL` too.
2. Give every overlap lock an expiry shorter than the job's interval: 10 minutes for every-minute jobs, 50 for hourly, 120 for daily and weekly.
3. Add the `alert` log channel (`AlertMailHandler`): records at `OPS_ALERT_LEVEL` (default `error`) and above are mailed synchronously to `OPS_ALERT_EMAIL`, once per message per 15 minutes and at most 20 per hour. Production enables it with `LOG_STACK=single,alert`.
4. The production deploy also archives `storage/app` next to the database dump, with the same 14-day retention.

## Invariants at risk

None of the booking, payment or tenancy invariants change. Customer access links already used `CustomerAccessLinks::canonical()` and are untouched.

## Tests

- `Auth/EmailLinkHostTest`: a forged Host does not reach the reset link, the verification link or the admin payout link, and the canonical verification link still verifies.
- `OperationsReadinessTest::test_overlap_locks_expire_before_the_next_run_is_due`.
- `Operations/OperationsAlertTest`: one mail per message per quiet period, exceptions carry their location, warnings and a missing recipient send nothing, the hourly cap holds.
- Deploy script: YAML parsed, the remote script passes `bash -n`, the uploads archive step was run against a sample tree.

## Docs to update

- `docs/operations.md` §2, §3, §4, §5, §6
- `docs/domains/auth.md`

## Evidence (fill when done)

Implemented on branch `agathan-bq-173-production-hardening` (22 Nov 2026). Verification in the PR. Left for the Tech Lead on the server: `OPS_ALERT_EMAIL`, `LOG_STACK=single,alert`, a scheduled backup cron (operations §6) and the Host check in operations §8.
