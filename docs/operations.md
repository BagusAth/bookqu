# Operations

How BookQu is run, deployed, verified and recovered. Statements here are facts from the repository unless marked **Target** (planned in `docs/work/`) or **Unknown** (not established in the repository — do not invent it). An operational capability counts as working only with evidence: command output, logs, a CI run, a successful smoke or restore test — never because a configuration file exists.

## 1. Environments

| Environment | Where | Notes |
|---|---|---|
| Local | Laragon (Windows), `composer run dev` (server, queue listener, Pail logs, Vite) | `.env` from `.env.example`; SingaPay sandbox by default |
| Test / CI | GitHub Actions `tests.yml` — PHP 8.3, Node 22; quality checks, then the suite on in-memory SQLite, MySQL 8 and MariaDB 11.4 | Runs on push to `integration`, on every PR, and before each production deploy |
| Staging | **Target** (BQ-017) | Needed before launch: sandbox payments, same hosting type |
| Production | HostData shared hosting, `bookqu.my.id` (`~/domains/bookqu.my.id/laravel`, web root `public_html`); PHP 8.4, MariaDB 11.4 (checked 2026-10-30) | Not launched to real customers yet; receives every push to `main` |

Never copy production secrets into local or CI environments.

### SingaPay payments outside production

SingaPay sends a webhook only to the one URL registered in its dashboard, which a local or dev instance cannot receive (BQ-076). BookQu also asks SingaPay for the status itself (BQ-074): while the payment page is open, or when "Periksa Status" is clicked, a paid sandbox payment becomes `sukses` without a webhook. Point the instance at the sandbox merchant (`SINGAPAY_BASE_URL=https://sandbox-payment-b2b.singapay.id` with the sandbox credentials) and register the machine's public IP there. Other ways to test:

- **Simulate the webhook (no SingaPay needed):** create the booking or subscription checkout, then run `php artisan singapay:simulate-webhook <order_id>`. Use `--status=failed` or `--status=expired` for the other outcomes. The command signs a SingaPay-shaped notification with `SINGAPAY_CLIENT_SECRET` and runs it through the real webhook flow (`ProcessSingaPayWebhook` → Outcome intake → Payment lifecycle). It refuses to run when `APP_ENV=production`.
- **Receive real sandbox webhooks:**
  1. Expose the app with a tunnel; Laragon includes `ngrok`: `ngrok http 80`.
  2. Register `https://<tunnel-host>/api/webhooks/singapay/transaction` as the webhook URL of the SingaPay **sandbox** merchant. Never change the production merchant's URL.
  3. Register the dev machine's public IP with SingaPay. API calls from unregistered IPs fail with "IP address not registered".

## 2. Configuration

Production must have `APP_ENV=production`, `APP_DEBUG=false`, a stable `APP_KEY` (never rotated casually — it decrypts sessions and encrypted fields), and the real `APP_URL`. Drivers from `.env.example`: `DB_CONNECTION=mysql`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`, `MAIL_MAILER=log` (development only).

Secrets come from the server `.env`, never from Git, frontend code or docs. After changing configuration: `php artisan config:cache`, then restart anything long-running.

### Production `.env` checklist (Tech Lead)

The values below describe what to fill in; they are not secrets. Verify on the server without copying values into PRs or logs.

| Name | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://bookqu.my.id` (exact, no path or trailing slash; customer access links require the request host to equal it) |
| `APP_KEY` | Keep the key that matches the database; back it up separately from the database (§6) |
| `LOG_LEVEL` | `warning` |
| `DB_CONNECTION`, `SESSION_DRIVER`, `CACHE_STORE` | `mysql`, `database`, `database` |
| `QUEUE_CONNECTION` | `database` |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME` | Host, port and TLS scheme of the SMTP provider |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | Production SMTP credentials from secret storage |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | A verified sender address and `BookQu` |
| `PAYMENT_GATEWAY_DRIVER` | `singapay` (bookings and subscriptions in v1) |
| `PAYMENT_BOOKING_DEADLINE_MINUTES` | `15` |
| `PAYMENT_SUBSCRIPTION_DEADLINE_MINUTES` | `60` |
| `PAYMENT_SETTLEMENT_MARGIN_MINUTES` | `2` |
| `SINGAPAY_BASE_URL` | `https://payment-b2b.singapay.id` (the sandbox is `https://sandbox-payment-b2b.singapay.id`) |
| `SINGAPAY_CLIENT_ID`, `SINGAPAY_CLIENT_SECRET`, `SINGAPAY_API_KEY`, `SINGAPAY_ACCOUNT_ID` | The platform's live credentials |
| `TRUSTED_PROXIES` | Empty by default; set comma-separated verified IP/CIDR of the hosting proxy only if customer links answer 404 because HTTPS ends at a proxy |

Remove the obsolete `SINGAPAY_PAYMENT_EXPIRY_MINUTES`. Never set `TRUSTED_PROXIES=*` unless the origin is reachable only through trusted proxies; the setting lets Laravel read HTTPS from forwarded headers, and an empty value keeps ignoring headers from untrusted senders.

After filling it in, run `php artisan config:cache`. The Tech Lead also registers the server IP in the SingaPay whitelist and the Transaction Notif URL `https://bookqu.my.id/api/webhooks/singapay/transaction`, then sends one test e-mail through the queue to prove SMTP and the worker.

### Payment provider

```env
PAYMENT_GATEWAY_DRIVER=singapay        # or midtrans
SINGAPAY_BASE_URL=…  SINGAPAY_CLIENT_ID=…  SINGAPAY_CLIENT_SECRET=…  SINGAPAY_API_KEY=…
SINGAPAY_HMAC_VALIDATION_KEY=…  SINGAPAY_ACCOUNT_ID=
SINGAPAY_WEBHOOK_TOLERANCE_SECONDS=300  SINGAPAY_CUSTOMER_PAYS_FEE=false
MIDTRANS_MERCHANT_ID=…  MIDTRANS_CLIENT_KEY=…  MIDTRANS_SERVER_KEY=…
MIDTRANS_IS_PRODUCTION=false  MIDTRANS_IS_SANITIZED=true  MIDTRANS_IS_3DS=true
PAYMENT_BOOKING_DEADLINE_MINUTES=15  PAYMENT_SUBSCRIPTION_DEADLINE_MINUTES=60  PAYMENT_SETTLEMENT_MARGIN_MINUTES=2
```

One deadline per payment (ADR-0009): `payments.expired_at` is set from these values. The provider link, the slot hold, the sweep and the countdowns all follow it. The obsolete `SINGAPAY_PAYMENT_EXPIRY_MINUTES` setting must be removed; the booking window uses `PAYMENT_BOOKING_DEADLINE_MINUTES`.

Webhook URLs to register with the providers: `https://bookqu.my.id/api/webhooks/singapay/transaction` and `https://bookqu.my.id/midtrans/webhook`.

Switching the active provider: set `PAYMENT_GATEWAY_DRIVER` → `php artisan config:cache` → verify a new booking checkout (SingaPay redirect or Midtrans Snap) and a subscription checkout. Existing payments keep their original provider for webhooks and status checks.

## 3. Deployment

`.github/workflows/deploy-production.yml` runs on every push to `main` (ADR-0015, BQ-018):

1. **Tests first.** The `tests` job calls `tests.yml` (quality checks, SQLite suite, MySQL suite). The `deploy` job needs it, so a red CI never deploys.
2. Build Vite assets on the runner and validate the manifest.
3. Archive the source (excluding `.git`, `.env*`, `node_modules`, `vendor`, `storage`, `public/build`) and the build; copy both to the server over SSH.
4. On the server, before anything changes:
   - back up `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `composer.*` and `public/build` to `deploy-backups/<timestamp>/`;
   - dump the database with `mysqldump --single-transaction --no-tablespaces` to `db-backups/bookqu-<timestamp>.sql.gz` (directory 700, file 600). Credentials are read from the live Laravel config into a temporary 600 client file, never the command line or the log. The dump must pass `gzip -t` and end with `Dump completed`, or the deploy stops before touching anything. Backups older than 14 days are deleted.
5. `php artisan down` on the old code first, so no request reaches new code before its migrations; replace the code; `composer install --no-dev --no-scripts --optimize-autoloader` (HostData disables `proc_open`, so Composer scripts are skipped); `migrate --force`; `optimize:clear`; `optimize`; `php artisan up`; `route:cache`. A newer push waits for a running deploy (`cancel-in-progress: false`); a failed deploy restores the old code and reinstalls its dependencies.
6. Health check: `curl https://bookqu.my.id` must return 2xx/3xx. On any failure after the swap, code and build are restored and the site is brought back up; the schema is **not** rolled back, except for the approved BQ-004 credential-data reversal below (see §6).

Prerequisite: `mysqldump` on the server. Run the manual `test-ssh.yml` workflow once; it reports the PHP and `mysqldump` versions. Without `mysqldump` every deploy fails at the backup step, by design. Checked 2026-10-30: present as the MariaDB 11.4 client (PHP 8.4.27); the database server is MariaDB 11.4.10, which CI also tests (BQ-073).

Migrations must stay backward compatible with the previous release's code (expand → migrate data → contract in a later release), because a rollback restores code, not schema.

### BQ-004: tenant credential encryption release

Tech Lead-approved exception to ADR-0015: migration `2026_11_01_001033_encrypt_tenant_gateway_credentials` encrypts four tenant Midtrans client/server keys in place. Previous code expects plaintext and cannot run against these encrypted rows. Before releasing, verify the database dump, preserve the matching `APP_KEY`, pause queue/scheduler entry points and let running workers finish. Keep requests in maintenance mode during the migration and any reversal. Never print tenant keys or dump their model attributes into logs.

The migration expands the four columns to nullable TEXT, backfills plaintext without encrypting already valid ciphertext again, and purges the legacy tenant and public-projection cache namespaces. Database-cache purging includes retired slugs and preserves unrelated records. Other cache drivers remove current tenant slug/domain and public keys; if such a driver contains retired legacy slug keys, remove that namespace using its backend tooling before bringing requests back. Storefront caching now stores only an explicit public-field projection.

**Rollback to code from before BQ-004 must first run this migration's `down()` using the encryption-aware code and its matching key.** Record its batch from `php artisan migrate:status` and, while requests and workers remain stopped, run:

```bash
php artisan down
php artisan migrate:rollback --batch=<BQ004_BATCH> --path=database/migrations/2026_11_01_001033_encrypt_tenant_gateway_credentials.php --force --no-interaction
```

Only after successful decryption restore the old code, clear/rebuild its caches, restart workers with that code, and bring requests up. `down()` restores plaintext and removes tenant caches; columns stay TEXT to avoid truncating values written since deployment. Do not run the new encrypted-cast code against the restored plaintext rows. To return to new code later, rerun its migration before reopening traffic.

Automatic deployment rollback enforces this order for the first encryption release: when migrations started and the backup lacks this migration, enter maintenance and reverse the migration before copying old code. If reversal fails, keep maintenance mode and stop without copying old code; recover the correct key and complete reversal manually. Backups from later encrypted releases already contain the migration, so ordinary code rollback keeps ciphertext. No production rollback was executed as part of BQ-004; the rollback function is exercised with isolated shell fixtures and the data reversal with MySQL tests.

Remaining gaps:

| Gap | Item |
|---|---|
| No staging environment | BQ-017 |
| No scheduled backups and no restore drill on the server; `storage/app` uploads not backed up | BQ-019 |

Other workflows: `validate-deploy.yml` (manual: build and transfer test), `test-ssh.yml` (manual: SSH and deploy prerequisites).

## 4. Scheduler and queue

| Command | Schedule (`routes/console.php`) | Purpose |
|---|---|---|
| `bookings:expire-payments` | every minute | Reclaim booking and subscription payments past `expired_at` + settlement margin. It asks the payment's provider first: a success is applied, anything else expires the payment and cancels its bookings (`--dry-run` available) |
| `app:check-expired-subscriptions` | daily | Expire trials and subscriptions; warn trials about to end |
| `ops:scheduler-heartbeat` | every minute | Record a timestamp in shared cache for the `/up` health check (retained for one day) |

Implemented (ADR-0013, BQ-013/020): scheduled commands use named `Schedule::call()` callbacks calling `Artisan::call()` in-process, with cache-based `withoutOverlapping()` locks. No child process or `proc_open` is needed. The payment sweep remains every minute; subscription expiry remains daily.

The Tech Lead installs **two cron entries, each every minute**, in the HostData panel (not yet done):

```bash
cd ~/domains/bookqu.my.id/laravel && php artisan schedule:run >> /dev/null 2>&1
cd ~/domains/bookqu.my.id/laravel && php artisan queue:work --stop-when-empty --max-time=50 --tries=3 >> /dev/null 2>&1
```

Without the queue entry **no e-mail is sent**, including customer access links. The four owner/trial notifications and the cancellation e-mail are queued after commit. Customer confirmation, invoice and reschedule mail goes through `SendManagementAccess`, which carries only the delivery id; the access link is minted when the job sends. Mailables that contain credentials are never `ShouldQueue`, so raw links never sit in the queue payload. The worker uses the database connection and stops when the queue is empty or its time limit is reached; target mail delay ≤1 minute when cron, queue capacity and SMTP are healthy.

Verification (BQ-020 is **in-progress**): run `php artisan schedule:list` to see the three callbacks and their schedules, wait for cron, open `/up`, then confirm one queued e-mail actually arrives. `/up` answers 500 when an existing heartbeat is **older than five minutes**; a fresh heartbeat or exactly five minutes answers 200. A heartbeat that never existed, expired after one day or was removed by a cache clear does not fail the check: a 200 alone does not prove cron is installed. The cache must be persistent and shared by CLI and web (`CACHE_STORE=database`).

If the worker stops, check `jobs` / `failed_jobs`, cron and logs; `/up` monitors the scheduler, not SMTP. The Tech Lead records cron and e-mail evidence before release. Never use `schedule:work` or a persistent worker on this shared hosting.

Retry failed jobs (`php artisan queue:failed`, `queue:retry`) only after confirming the job is idempotent — payment and refund work especially.

## 5. Mail and monitoring

Production needs a real mail transport (credentials, sender, test delivery) and an error alerting channel; both are **Target** (BQ-021). Until then, `storage/logs/laravel.log` is the only diagnostic source. Never enable `APP_DEBUG` in production to debug.

Configuration and the health check are ready; BQ-021 stays **in-progress** until the Tech Lead fills in SMTP, proves one test e-mail and enables a free UptimeRobot monitor on `https://bookqu.my.id/up` every five minutes. Application exception alerts and queue failures still need verification; a healthy uptime does not prove that e-mail works.

## 6. Backups and recovery

Every production deploy writes `~/domains/bookqu.my.id/db-backups/bookqu-<timestamp>.sql.gz` before migrating (§3) and keeps 14 days. **Target** (BQ-019): scheduled backups as well, user uploads (`storage/app`) included, and a restore drill on the server. A backup counts only after a successful restore test.

Since BQ-004, `APP_KEY` also decrypts tenant gateway credentials. An encrypted database backup is usable only with its matching `APP_KEY` (or that key retained in `APP_PREVIOUS_KEYS`). Back up the key separately in protected secret storage; do not commit it or include it in diagnostic output. Rotation requires a new `APP_KEY` plus the previous key in `APP_PREVIOUS_KEYS`, followed by config-cache rebuild and worker restart. Keep previous keys until all dependent database values and relevant backups can be decrypted without them; `key:generate` alone would make existing tenant credentials unreadable.

Restore a deploy backup (verified locally on MySQL 8.4 on 2026-10-30; take a fresh dump of the current state first):

```bash
cd ~/domains/bookqu.my.id/laravel && php artisan down
umask 077 && printf '[client]\nuser="%s"\npassword="%s"\nhost="%s"\n' '<DB_USERNAME>' '<DB_PASSWORD>' '<DB_HOST>' > ~/restore.cnf
gunzip -c ~/domains/bookqu.my.id/db-backups/bookqu-<timestamp>.sql.gz | mysql --defaults-extra-file=~/restore.cnf <DB_DATABASE>
rm ~/restore.cnf
```

Then restore the matching code from `deploy-backups/<timestamp>/` (or redeploy that commit), run `php artisan optimize`, `php artisan up`, and check §9. Restoring a dump overwrites everything written since it was taken: bookings and payment webhooks received after the dump must be reconciled from the provider dashboards.


When data looks inconsistent:

1. Stop further destructive actions.
2. Identify the affected domain and records; preserve logs.
3. Decide which side is authoritative (application vs payment provider).
4. Correct state through a controlled, recorded operation — not ad-hoc SQL as a first response.
5. Verify related records; record the root cause as a work item.

Protect data integrity first, then restore service. Never "fix" an incident by creating duplicate bookings, wrong payment states or cross-tenant access.

## 7. Incident playbooks

| Incident | Check, in order |
|---|---|
| Double booking, slot stuck, booking cancelled unexpectedly | booking → schedule → payment → tenant → logs → concurrent requests → availability cache |
| Payment says paid but booking is not (or the reverse) | payment record → `provider` + `order_id`/`external_id` → provider dashboard → webhook log → booking states → refund |
| Queue not processing | `QUEUE_CONNECTION` → `jobs` / `failed_jobs` tables → worker cron → logs |
| Stale availability | cache keys `tenant:{id}:service:{id}:…` → missing invalidation → source query; clearing cache is diagnosis, not the fix |
| Suspected unauthorized access | endpoint and tenant → preserve logs → contain the path → rotate exposed tokens/credentials → fix → regression test → review related endpoints |

## 8. Launch checklist

All must be checked before the first real customer (gate G5 in `docs/work/ROADMAP.md`):

- [ ] Every `launch-blocker: true` work item is `done`
- [ ] CI green, including the MySQL integrity suite (BQ-023)
- [ ] Deploy waits for CI and backs up the database first (BQ-018)
- [ ] Backup restore tested (BQ-019)
- [ ] Scheduler and queue running on the server (BQ-020)
- [ ] Production mail and error alerting working (BQ-021)
- [ ] Production payment credentials set, webhooks registered and tested end-to-end (BQ-022)
- [ ] `APP_DEBUG=false`, HTTPS enforced, `storage:link` present
- [ ] Smoke test: public page, booking with payment, management link (view/cancel/reschedule), owner portal, subscription checkout
- [ ] Legal pages reviewed

## 9. After every deployment

Application responds · owner login works · a public storefront loads with availability · a booking can start payment · scheduler and queue are alive · `laravel.log` shows no new critical errors.
