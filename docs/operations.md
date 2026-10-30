# Operations

How BookQu is run, deployed, verified and recovered. Statements here are facts from the repository unless marked **Target** (planned in `docs/work/`) or **Unknown** (not established in the repository — do not invent it). An operational capability counts as working only with evidence: command output, logs, a CI run, a successful smoke or restore test — never because a configuration file exists.

## 1. Environments

| Environment | Where | Notes |
|---|---|---|
| Local | Laragon (Windows), `composer run dev` (server, queue listener, Pail logs, Vite) | `.env` from `.env.example`; SingaPay sandbox by default |
| Test / CI | GitHub Actions `tests.yml` — PHP 8.3, Node 22, in-memory SQLite | Runs on push/PR to `agathan`, `Refactor`, `main`, `payment` |
| Staging | **Target** (BQ-017) | Needed before launch: sandbox payments, same hosting type |
| Production | HostData shared hosting, `bookqu.my.id` (`~/domains/bookqu.my.id/laravel`, web root `public_html`) | Not launched to real customers yet; receives every push to `main` |

Never copy production secrets into local or CI environments.

## 2. Configuration

Production must have `APP_ENV=production`, `APP_DEBUG=false`, a stable `APP_KEY` (never rotated casually — it decrypts sessions and encrypted fields), and the real `APP_URL`. Drivers from `.env.example`: `DB_CONNECTION=mysql`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=local`, `MAIL_MAILER=log` (development only).

Secrets come from the server `.env`, never from Git, frontend code or docs. After changing configuration: `php artisan config:cache`, then restart anything long-running.

### Payment provider

```env
PAYMENT_GATEWAY_DRIVER=singapay        # or midtrans
SINGAPAY_BASE_URL=…  SINGAPAY_CLIENT_ID=…  SINGAPAY_CLIENT_SECRET=…  SINGAPAY_API_KEY=…
SINGAPAY_HMAC_VALIDATION_KEY=…  SINGAPAY_ACCOUNT_ID=  SINGAPAY_PAYMENT_EXPIRY_MINUTES=15
SINGAPAY_WEBHOOK_TOLERANCE_SECONDS=300  SINGAPAY_CUSTOMER_PAYS_FEE=false
MIDTRANS_MERCHANT_ID=…  MIDTRANS_CLIENT_KEY=…  MIDTRANS_SERVER_KEY=…
MIDTRANS_IS_PRODUCTION=false  MIDTRANS_IS_SANITIZED=true  MIDTRANS_IS_3DS=true
```

Webhook URLs to register with the providers: `https://bookqu.my.id/api/webhooks/singapay/transaction` and `https://bookqu.my.id/midtrans/webhook`.

Switching the active provider: set `PAYMENT_GATEWAY_DRIVER` → `php artisan config:cache` → verify a new booking checkout (SingaPay redirect or Midtrans Snap) and a subscription checkout. Existing payments keep their original provider for webhooks and status checks.

## 3. Deployment (current)

`.github/workflows/deploy-production.yml` runs on **every push to `main`**:

1. Build Vite assets on the runner and validate the manifest.
2. Archive the source (excluding `.git`, `.env*`, `node_modules`, `vendor`, `storage`, `public/build`) and the build; copy both to the server over SSH.
3. On the server: back up `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, `composer.*` and `public/build`; replace them; `composer install --no-dev --no-scripts --optimize-autoloader` (HostData disables `proc_open`, so Composer scripts are skipped); `php artisan migrate --force`; `optimize:clear`; `optimize`; `route:cache`.
4. Health check: `curl https://bookqu.my.id` must return 2xx/3xx. On failure the code and build are restored from the backup.

Target (ADR-0015): staging deployed from `agathan` after CI; production deploy only for a commit whose CI passed, with a `mysqldump` before `migrate --force`, maintenance mode and backward-compatible (expand/contract) migrations.

Known risks (all tracked):

| Risk | Item |
|---|---|
| Deploy does not wait for the test workflow | BQ-018 |
| No database backup before `migrate --force`; rollback restores code but not schema or data | BQ-018, BQ-019 |
| No staging environment | BQ-017 |

Other workflows: `validate-deploy.yml` (manual: build and transfer test), `test-ssh.yml` (manual: SSH check).

## 4. Scheduler and queue

| Command | Schedule (`routes/console.php`) | Purpose |
|---|---|---|
| `bookings:expire-payments` | every 15 minutes | Expire pending booking payments past `expired_at`, cancel their bookings, free slots (`--dry-run` available) |
| `app:check-expired-subscriptions` | daily | Expire trials and subscriptions; warn trials about to end |

Target (ADR-0013): two cron entries every minute — `php artisan schedule:run` and `php artisan queue:work --stop-when-empty --max-time=50 --tries=3`; scheduled work runs in-process (`Schedule::call()`/`job()`) because `proc_open` is disabled; the payment sweep runs every minute for booking and subscription payments (ADR-0009).

Production needs a cron entry running `php artisan schedule:run` every minute. Whether it exists on HostData is **Unknown** (BQ-020).

The database queue needs a worker. Shared hosting cannot keep a long-running worker; the expected pattern is a cron-driven `php artisan queue:work --stop-when-empty`. **Unknown** whether it is configured (BQ-020). Today almost all mail is sent synchronously, so a missing worker mainly affects anything queued in future (BQ-013).

Retry failed jobs (`php artisan queue:failed`, `queue:retry`) only after confirming the job is idempotent — payment and refund work especially.

## 5. Mail and monitoring

Production needs a real mail transport (credentials, sender, test delivery) and an error alerting channel; both are **Target** (BQ-021). Until then, `storage/logs/laravel.log` is the only diagnostic source. Never enable `APP_DEBUG` in production to debug.

## 6. Backups and recovery

**Target** (BQ-019): automated database backups before every migration and on a schedule, user uploads (`storage/app`) included, with a documented and tested restore. A backup counts only after a successful restore test.

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
