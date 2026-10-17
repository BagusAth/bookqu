# BookQu Technical Audit — 2026-10-17

## Scope

- Repository: `BagusAth/bookqu`
- Working branch intended for this patch: `agathan`
- Audit areas: payment webhook correctness, provider isolation, sensitive logging, payout atomicity, asset upload policy, CI trigger coverage, and regression tests.
- This is a source-code review, not a penetration test or verification of live production configuration.

## Code fixes in this patch

### P0 — Fail-closed SingaPay webhook status handling
- Removed the fallback that treated a missing transaction status as `paid`.
- Root-level `status` is no longer treated as payment status; it can be an HTTP/envelope status.
- Missing or unmapped transaction status returns HTTP 422 without changing payment state.
- A successful payment status requires amount and currency, and the existing IDR/amount mismatch checks remain in force.
- Webhook amount/currency values are type-checked before use.

### P0 — Provider identity and callback isolation
- SingaPay callback resolution only matches `provider=singapay`, plus legacy rows with null `provider` and explicit `metode=singapay`.
- The Midtrans webhook rejects records explicitly marked as SingaPay, while retaining compatibility for old Midtrans payment rows whose `metode` becomes a payment-instrument value after settlement.
- New subscription and Snap booking payments record `provider=midtrans` explicitly.
- Regression tests cover reference collisions in both directions.

### P1 — Midtrans amount integrity and logging
- Midtrans webhook logs keep operational fields but no longer log the entire raw request payload and signature value.
- Midtrans callbacks with a valid signature are rejected if `gross_amount` differs from BookQu's stored payment amount.

### P1 — Payout consistency and upload safety
- Payout balance debit and payout record creation now occur in one transaction with a tenant row lock, and amounts are validated to two decimal places.
- Unsanitized SVG uploads are excluded until an explicit SVG sanitization and serving policy exists.

### P1 — Test and CI coverage
- The OAuth contract test now checks `X-CLIENT-ID`, `X-PARTNER-ID`, and the HMAC-SHA512 `X-Signature` expected by SingaPay v1.1 rather than expecting Basic Authorization.
- Regression tests cover missing SingaPay status, missing amount/currency on success, cross-provider callback collisions in both directions, Midtrans gross-amount mismatch, and payout balance/precision failures.
- Payout debit and payout-row creation now occur in one transaction using a per-tenant row lock; withdrawal amounts are bounded to valid positive values with at most two decimal places.
- Unsanitized SVG asset uploads are excluded until the application has an explicit SVG sanitization and serving policy.
- GitHub Actions test workflow now triggers for pushes and pull requests targeting `agathan`.

## Evidence

- The last recorded CI run on the equivalent `Refactor` implementation failed only in `SingaPayIntegrationTest::test_oauth_token_request_contract`: 347 tests passed, 1 failed, 1,660 assertions.
- Official SingaPay Payment Gateway v1.1 Access Token documentation: https://docs.singapay.id/api-reference/security/access-token-v11
- The tracked `.env.testing` inspected during the audit contains a testing APP_KEY and in-memory SQLite configuration only; no production payment secret was found there.

## Verification status

The patch must be applied and then tested in the repository environment. Release confidence is not established until the complete GitHub Actions run is green and the logs are reviewed. This source-only audit does not prove that production secrets, schedulers, queue workers, backups/restores, monitoring, or rollback work correctly in the live hosting environment.

## Remaining audit work

1. Review every remaining `withoutGlobalScope(s)` usage and prove that the call has a correct authorization/ownership check.
2. Verify concurrent booking and payout behavior against the production database engine, not only in-memory SQLite.
3. Run dependency vulnerability/static-analysis checks.
4. Verify production scheduler, queue worker, monitoring, backup restore, and deployment rollback procedures with authorized operational access.
5. Review all public uploads and ensure SVG/HTML-like active content cannot execute in the application origin.
6. Refresh the implementation tracker only after CI and required operational checks produce evidence.
