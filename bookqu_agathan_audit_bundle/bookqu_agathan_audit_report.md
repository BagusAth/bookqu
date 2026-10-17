# BookQu CTO Audit — 17 October 2026

## Scope and access limitation

Repository reviewed: `BagusAth/bookqu`, working branch `agathan`, audited HEAD `5a504e33a4f612266e3a910b826f2eeca2bc3c43`.

The audit reviewed payment/webhook processing, provider identity, booking lifecycle/concurrency, tenant-scoped access patterns, owner payout balance changes, asset uploads, migrations, environment files, tests, and CI triggers.

This was a source-code audit, not a penetration test or live production verification. **No remote repository files were changed by this session.** Both GitHub write paths attempted by the connected integration returned HTTP 403 (`Resource not accessible by integration`). `apply_bookqu_agathan_audit.py` is a guarded local patch applier for a local checkout of `agathan`.

## Verified baseline findings

### P0 — SingaPay webhook status must fail closed

`ProcessSingaPayWebhook` previously used root-level `status` as a fallback and ultimately defaulted a missing transaction status to `paid`. The root-level value may be an envelope status rather than a transaction status. A validly signed but malformed/unexpected payload without a transaction status could therefore be treated as a success. The patch rejects a missing/unmapped transaction status and requires amount and currency before accepting a success state.

### P0 — Provider identity must be checked on callbacks

The SingaPay resolver searched by order reference or external reference after bypassing the tenant global scope, without restricting the result to SingaPay payments. A callback should not be able to resolve a payment belonging to the other provider if references collide. The patch scopes SingaPay lookup to provider `singapay`, with a narrow compatibility path for legacy rows where `provider` is null but `metode=singapay`. The Midtrans callback also explicitly rejects payments that are identified as SingaPay, preserving compatibility with legacy Midtrans records whose `metode` can change to a Midtrans payment instrument after settlement.

New subscription payments and Snap booking-payment records now record `provider=midtrans` explicitly, separately from the payment instrument/method.

### P1 — Midtrans amount must match the stored payment amount

The baseline checked the Midtrans webhook signature but did not compare `gross_amount` with the amount stored in BookQu before status synchronization. The patch adds that comparison after signature verification and rejects a mismatch without changing the payment state.

### P1 — Payout debit and payout creation must be atomic

`RequestOwnerPayout` previously subtracted the tenant balance and created an `OwnerPayout` record as separate database operations, with no transaction or lock around the balance check. Concurrent requests could act on a stale balance, and a failure while creating the payout record could leave a balance debit without a corresponding payout row. The patch puts the balance check, debit, and payout creation in a transaction with a row lock on the tenant record, plus validation for valid amounts and two-decimal precision.

### P1 — Raw Midtrans webhook payloads were logged

The baseline logged the full incoming Midtrans payload, which includes the signature and may include customer data. The patch logs selected operational fields instead.

### P1 — The active development branch was not running the test workflow

The GitHub Actions test workflow did not include `agathan` in the branch filters. The patch adds it to both push and pull-request filters.

The last recorded CI run on the equivalent `Refactor` implementation failed only in `SingaPayIntegrationTest::test_oauth_token_request_contract`: 347 tests passed, one failed, and 1,660 assertions were recorded. Composer/npm installation and the frontend build succeeded; the failure was the test's expectation of a Basic Authorization header, while the SingaPay v1.1 implementation uses `X-CLIENT-ID`, `X-PARTNER-ID`, and HMAC-SHA512 `X-Signature`.

- CI run: https://github.com/BagusAth/bookqu/actions/runs/38952345364
- Official SingaPay documentation: https://docs.singapay.id/api-reference/security/access-token-v11

### P1 — Unsanitized SVG upload policy

The owner asset upload validation listed SVG among accepted extensions. SVG is active document content and requires deliberate sanitization and safe serving. The patch excludes SVG upload unless a sanitizer and serving policy are introduced.

## Existing positive controls observed

- `TenantScope` fails closed when tenant context is missing.
- Tenant middleware clears context in a `finally` block.
- Owner booking/reschedule actions check tenant ownership and use transactions/row locks in important flows.
- Booking rules define the 15-minute pending grace period; a database active-slot unique constraint also exists.
- The SingaPay verifier checks HMAC and timestamp validity.
- The tracked `.env.testing` inspected during the audit contains a testing app key and in-memory SQLite configuration only; no production payment secret was found there.

These controls are valuable but do not prove the application is free of vulnerabilities.

## Changes packaged in the guarded patch script

1. Correct the SingaPay OAuth contract test.
2. Add webhook regression tests for missing status, missing amount/currency on success, cross-provider reference collisions in both directions, and Midtrans amount mismatch.
3. Fail closed on missing/unmapped SingaPay status and require amount/currency before payment success.
4. Scope SingaPay callback lookup to SingaPay records; reject known SingaPay records in the Midtrans webhook path while preserving legacy Midtrans callback compatibility.
5. Verify Midtrans `gross_amount` against the stored payment amount.
6. Reduce Midtrans webhook log data.
7. Make payout debit/record creation atomic with a tenant row lock and validate amount precision.
8. Set explicit Midtrans provider identity for newly created subscription / Snap booking payments.
9. Disallow SVG asset uploads until an explicit safe handling policy exists.
10. Include `agathan` in the test workflow branch filters.
11. Add a technical audit report inside the repository when the script is run.

## How to apply and verify

From a local checkout of the repository:

```bash
git checkout agathan
python apply_bookqu_agathan_audit.py
git diff --check
git diff --stat
git diff
php artisan test --filter=SingaPayIntegrationTest
php artisan test --filter=PaymentActionTest
php artisan test --filter=SettingsTest
php artisan test
npm run build
```

Review all changes before committing. Push the reviewed changes to `agathan` so GitHub Actions can run. Do not merge or deploy until the full workflow is green and the logs have been reviewed.

The script refuses to run unless the current branch is `agathan`, verifies the original code blocks before writing, and writes nothing if an expected block has changed. It does not commit or push. It has been syntax-checked as Python, but it has **not** been applied to a clone of this repository or tested against the real PHP suite in this environment.

## Remaining work before declaring production safe

1. Review every remaining `withoutGlobalScope(s)` usage and prove that the bypass has a correct authorization/ownership check.
2. Verify concurrent booking and payout behavior against the production database engine; the in-memory SQLite test suite is not enough to prove MySQL/MariaDB lock behavior.
3. Run dependency vulnerability and static-analysis checks.
4. Verify actual production secrets, `APP_DEBUG`, file permissions, scheduler/cron, queue workers, monitoring, log retention, backup restoration, and rollback procedures with authorized operational access.
5. Verify all public uploads and ensure SVG/HTML-like active content cannot execute in the application origin.
6. Update the tracker only after CI and operational checks produce evidence.

## Overall assessment

The repository has meaningful security controls and broad functional coverage, but the findings above should be fixed and tested before a safety claim or release approval. **This audit and its patch are not a certification that the live production system is safe.** The patch must be applied, the automated tests must pass, and remaining live-environment checks must be completed.
