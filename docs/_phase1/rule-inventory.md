# Fase 1 · Langkah 1.1 — Inventaris Aturan

> File kerja sementara untuk review architecture lead. Dihapus di langkah 1.7 setelah semua aturan pindah ke rumah barunya.
> Disusun 2026-10-30 dari isi repo pada commit `a68ffe8`.

## Cara membaca

Setiap baris adalah **satu aturan kanonik**. Aturan yang sama yang tertulis di beberapa dokumen digabung menjadi satu baris; kolom **Sumber** mencatat semua tempat asalnya.

Teks aturan ditulis dalam bahasa Inggris (keputusan D2) karena akan disalin ke dokumen baru.

**Singkatan sumber**

| Kode | Dokumen | Kode | Dokumen |
|---|---|---|---|
| AG | `AGENT.md` | SD | `docs/7-SYSTEM-DESIGN.md` |
| RD | `docs/1-README.md` | OP | `docs/8-OPERATIONS.md` |
| PR | `docs/2-PRODUCT.md` | A1 / A2 | ADR-0001 / ADR-0002 |
| RQ | `docs/3-REQUIREMENT.md` | TA | `docs/TECHNICAL-AUDIT-2026-10-17.md` |
| AR | `docs/4-ARCHITECTURE.md` | AB | `docs/audit-bug/*` |
| DV | `docs/5-DEVELOPMENT.md` | TR | `docs/6-TRACKER.md` |

Angka setelah kode = nomor bagian, contoh `AG§20` = `AGENT.md` bagian 20.

**Rumah baru**

| Kode | File baru | Isi |
|---|---|---|
| `CORE` | `AGENTS.md` (bagian BookQu) | Selalu dimuat. Hanya aturan yang tidak boleh dilanggar + peta. |
| `WF` | `docs/workflow.md` | Alur kerja, kewenangan, definition of done, template. |
| `ARCH` | `docs/architecture.md` | Aturan struktur normatif. |
| `MAP` | `docs/domain-map.md` | Pemetaan konkret domain → kode, invariant `INV-*`, status. |
| `GLOS` | `docs/glossary.md` | Istilah kanonik ↔ nama di kode. |
| `PROD` | `docs/product.md` | Definisi produk, Tier rilis. |
| `REQ` | `docs/requirements.md` | FR / BR / NFR (ID dipertahankan). |
| `OPS` | `docs/operations.md` | Runbook dan checklist. |
| `RULE:x` | `.ai/rules/x.md` | Aturan per path, dimuat hanya saat menyentuh path terkait. |
| `WORK` | `docs/work/` | Item backlog `BQ-*`. |
| `ADR` | `docs/adr/` | Alasan keputusan arsitektur. |
| `DROP` | — | Dibuang, alasannya ditulis. |

**Status**: `KEEP` dipertahankan · `MERGE` digabung dari beberapa sumber · `REWORD` disederhanakan · `FIX` dokumen lama salah · `NEW` aturan belum ada, perlu persetujuan lead · `DROP` dibuang

**Cek kode**: ✓ kode sesuai · ✗ kode melanggar · ◐ sebagian · ? belum dicek · — tidak berlaku

---

## Ringkasan

| Kelompok | Aturan | ✗ melanggar | ◐ sebagian | NEW |
|---|---:|---:|---:|---:|
| A. Sistem dokumentasi | 13 | 0 | 0 | 2 |
| B. Alur kerja dan kewenangan | 19 | 0 | 0 | 3 |
| C. Produk dan terminologi | 9 | 0 | 1 | 0 |
| D. Tenant dan otorisasi | 10 | 2 | 3 | 1 |
| E. Booking dan ketersediaan | 15 | 2 | 0 | 0 |
| F. Pembayaran dan refund | 12 | 2 | 2 | 1 |
| G. Langganan | 4 | 1 | 1 | 0 |
| H. Struktur kode | 14 | 1 | 4 | 0 |
| I. Data, transaksi, cache | 8 | 0 | 1 | 0 |
| J. Frontend | 5 | 1 | 0 | 0 |
| K. Pengujian | 8 | 0 | 1 | 0 |
| L. Keamanan, log, error | 8 | 3 | 1 | 2 |
| M. Operasional | 14 | 0 | 1 | 0 |
| N. Rilis bertahap (Tier) | 3 | 0 | 1 | 1 |
| **Total** | **142** | **12** | **16** | **10** |

> Revisi audit 2026-10-30: +7 aturan yang terlewat (R-DOC-12/13, R-WF-18/19, R-ARCH-13/14, R-TEST-08); R-SEC-01 dan R-ARCH-10 dilunakkan menjadi ◐; R-BOOK-03 diputuskan (dokumen mengikuti kode); R-BOOK-14 dan R-SEC-04 terverifikasi ✓.

Dari ~18.000 baris dokumen, isinya terkumpul menjadi **142 aturan unik**. Sisanya adalah pengulangan, diagram panah, dan bagian meta (status dokumen, "dokumen ini bukan ...").

---

## A. Sistem dokumentasi

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-DOC-01 | Each question has exactly one authoritative document; the doc map lists which. | RD§3, RD§17, AG§3, RQ§2, DV§2 | `docs/README.md` | MERGE | — |
| R-DOC-02 | When sources disagree, classify the conflict (product / requirement / architecture / implementation drift / test drift / doc drift) and fix the authoritative source; never pick silently. | AG§3, RD§18, RQ§64, DV§13, DV§79 | WF | MERGE | — |
| R-DOC-03 | Do not rewrite docs to hide wrong code, and do not change code just because an outdated doc says so. | RD§18 | WF | KEEP | — |
| R-DOC-04 | Git history is the only record of past work; active docs describe the current system and the target only. | RD§13, RD§22, AG§71, DV§82, TR§38 | `docs/README.md` | MERGE | — |
| R-DOC-05 | Update only the documents whose responsibility actually changed; no documentation noise. | AG§69, RD§20, DV§67–69 | WF | MERGE | — |
| R-DOC-06 | Add a new document only when the topic has a long-lived responsibility no existing document can hold. | RD§19 | `docs/README.md` | KEEP | — |
| R-DOC-07 | Target architecture must not be described as already implemented. | RD§13.2, AR§51 | ARCH | KEEP | — |
| R-DOC-08 | Durable docs avoid temporary class names; concrete code mapping lives only in `domain-map.md`. | RD§21, PR§5 | `docs/README.md` | REWORD | — |
| R-DOC-09 | Work status (todo / in progress / verified) lives only in `docs/work/`, never in reference docs. | TR§2, TR§42, OP§51 | WF | NEW | — |
| R-DOC-10 | Every requirement, invariant, work item and decision has a stable ID (`FR-`, `BR-`, `NFR-`, `INV-`, `BQ-`, `ADR-`); renumbering is forbidden. | RQ§4 | `docs/README.md` | MERGE | — |
| R-DOC-11 | Path-scoped rules live in `.ai/rules/`; agents read `.ai/rules/index.md` before editing a matching path. | (Boost guideline) | CORE | NEW | — |
| R-DOC-12 | One status vocabulary: capability status (Implemented / Verified / Needs Verification / Planned / Deprecated) in domain docs; work status (proposed / approved / in-progress / done / rejected) in work items. | RQ§3, TR§3–5 | `docs/README.md` | MERGE | — |
| R-DOC-13 | Functional status and architecture status are tracked separately; known technical debt stays visible. | AG§74, TR§4, RQ NFR-MAINT-006 | MAP | MERGE | — |

## B. Alur kerja dan kewenangan

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-WF-01 | Classify every task: feature, bug fix, refactor, technical improvement, documentation, product change, operational change. | DV§5 | WF | KEEP | — |
| R-WF-02 | Every behaviour change maps to a requirement ID; if none exists, the task is a product change. | AG§6, AG§7, DV§7, DV§11 | CORE | MERGE | — |
| R-WF-03 | Product changes go: decision → product.md → requirements.md → architecture (if needed) → implement → test → work item done. | AG§49, AG§80, PR§26, RQ§63, DV§12 | WF | MERGE | — |
| R-WF-04 | Inspect routes, controllers, requests, models, actions, middleware, views, migrations and tests before editing; search before creating anything new. | AG§9, AG§56, DV§8, DV§9 | CORE | MERGE | — |
| R-WF-05 | Make the smallest coherent change — but never keep a security or data-integrity flaw just to keep the diff small. | AG§8, AG§53, DV§66 | CORE | MERGE | — |
| R-WF-06 | Do not expand scope; record unrelated findings as a new work item. | AG§51, DV§64 | CORE | MERGE | — |
| R-WF-07 | Small refactors are allowed only when they directly reduce risk or duplication for the current task. | AG§52, DV§65 | WF | MERGE | — |
| R-WF-08 | No rewrites from scratch: understand → characterize → extract → test → replace → verify. | AG§76, DV§81 | ARCH | MERGE | — |
| R-WF-09 | Ask for clarification only when two materially different product behaviours are plausible and the repo cannot decide; difficulty alone is not a reason. | AG§64, AG§65, DV§78 | CORE | MERGE | — |
| R-WF-10 | Non-trivial work starts with a written plan: requirement, domain, current files, target change, tests, docs affected. | AG§66, DV§14 | WF (template) | MERGE | — |
| R-WF-11 | Non-trivial work ends with a completion report: changes, requirement IDs, files, tests run, verification, docs updated, follow-ups. | AG§67, DV§80 | WF (PR template) | MERGE | — |
| R-WF-12 | One Definition of Done with per-type checklists (bug fix, feature, refactor, product change). | AG§77–81, DV§72–76, RQ§67 | WF | MERGE | — |
| R-WF-13 | Trade-off priority: Security → Data integrity → Tenant isolation → Business correctness → Maintainability → Testability → Performance → Convenience. | AG§54, DV§83 | CORE | MERGE | — |
| R-WF-14 | Refactor priority order: tenant/security → booking → payment → schedule → controller decomposition → Blade decomposition → subscription → terminology → routes → cleanup. | AG§75, TR§43 | ARCH | MERGE | — |
| R-WF-15 | **Approval matrix**: small fixes/UI/tests → agent + peer PR review; domain rules, schema, security → lead approves the plan first; architecture, ADR, core docs → lead only; new product scope → lead as product change. | (keputusan D6) | CORE | NEW | — |
| R-WF-16 | **Abstract instructions** are turned by the agent into a `BQ-*` work item (status `proposed`) before any non-trivial code change. | (keputusan sesi ini) | WF | NEW | — |
| R-WF-17 | **Work item lifecycle**: proposed → approved → in-progress → done (or rejected); one file per item in `docs/work/`. | (keputusan D4) | WF | NEW | — |
| R-WF-18 | Golden rule: understand first, change second, verify third, document fourth. | AG§83, DV§86 | CORE | MERGE | — |
| R-WF-19 | Existing code is evidence of current behaviour, not automatically the pattern to copy; follow the target architecture and do not reproduce known anti-patterns. | AG§5, DV§4, DV§77 | CORE | MERGE | — |

## C. Produk dan terminologi

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-PROD-01 | Code implements the product; code must never silently redefine it. | AG§4, PR§31, RQ§73 | CORE | MERGE | — |
| R-PROD-02 | A route, screen, field or half-built feature does not make something part of the product. | AG§73, PR§25 | PROD | MERGE | — |
| R-PROD-03 | Absence from an older spec does not invalidate a currently accepted capability. | PR§25, RQ§65 | PROD | KEEP | — |
| R-PROD-04 | Canonical terms: Tenant, Owner, Customer, Service, Schedule, Availability, Booking, Payment, Plan, Subscription, Staff, Resource, Additional Item, Voucher, Review. | AG§10, PR§7, PR§22 | GLOS | MERGE | — |
| R-PROD-05 | Do not introduce competing concepts: Program, Business entity, Order, Coupon, Membership, Customer-as-User. | PR§22, RQ§61 | GLOS | MERGE | — |
| R-PROD-06 | Legacy names (`namalayanan`, `idlayanan`, `namabisnis`, `idtenant`, Program) stay until a dedicated migration task; new code uses canonical English names. | AG§11, AG§42, PR§23 | GLOS + `RULE:php-naming` | MERGE | ◐ kode baru masih memakai nama lama, mis. `$adalahpro`, `$langganan` (`AppServiceProvider`) |
| R-PROD-07 | Service ≠ Schedule, Booking ≠ Payment, Customer ≠ User. | PR§21 | GLOS | KEEP | — |
| R-PROD-08 | Out of scope unless accepted: accounting, ERP, full CRM, inventory, social media, marketing automation, AI decisions. | PR§20, RQ§62 | PROD | MERGE | — |
| R-PROD-09 | Expansion ideas (Google Calendar, WhatsApp, multi-location, etc.) are not requirements until accepted. | PR§19.4, AR§58 | PROD | MERGE | — |

## D. Tenant dan otorisasi

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-TEN-01 | Tenant isolation is a security boundary: no tenant may read or modify another tenant's data. | AG§12, PR§16, RQ FR-TENANT-002, AR§3.8, SD§8, DV§36 | CORE + `INV-TEN-01` | MERGE | ✓ `TenantScope` |
| R-TEN-02 | Tenant context comes from `TenantMiddleware` / `TenantContext`; client-supplied tenant IDs never establish authorization. | AG§13, AG§14, RQ FR-TENANT-003, AR§14 | `RULE:tenant` | MERGE | ✓ |
| R-TEN-03 | Tenant-owned models use `BelongsToTenant`; an empty tenant context returns no rows (fail closed). | AG§14 | `RULE:tenant` + MAP | REWORD | ✓ 15 model memakai trait; `TenantScope` fail-closed |
| R-TEN-04 | Every `withoutGlobalScope(s)` needs a written reason, an explicit ownership filter, and a regression test when security-relevant. | AG§15, TA "remaining 1" | `RULE:tenant` | KEEP | ◐ 55 pemakaian di 29 file, belum diaudit; yang dicek (UpdateService, DeleteSchedule) sudah memfilter tenant |
| R-TEN-05 | User-supplied IDs (tenant, booking, service, schedule, payment, order, …) are untrusted: check existence, ownership, tenant and eligibility. | AG§17, RQ NFR-SEC-008, AR§13 | CORE | MERGE | ✗ `order_id` cukup untuk membuka invoice + token kelola dan membatalkan pembayaran (`BookingController.php:803`, `:950`) |
| R-TEN-06 | UI visibility (hidden button/menu, disabled input, Alpine state) is never authorization; enforce server-side. | AG§16, AR§57, DV§37 | CORE | MERGE | ◐ lihat R-REL-03 |
| R-TEN-07 | Authorization answers: who acts, which resource, permission, and tenant ownership. | AR§13 | ARCH | KEEP | ◐ tidak ada Policies; otorisasi = TenantScope + cek manual |
| R-TEN-08 | Every new tenant-scoped feature answers the six tenant-boundary questions and has an A-can/B-cannot test. | AR§15, DV§36 | `RULE:tenant` | MERGE | — |
| R-TEN-09 | Platform admin permissions are separate from owner permissions. | RQ FR-ADMIN-004, RQ§5.3 | REQ | KEEP | ✓ middleware `role:admin` |
| R-TEN-10 | **Middleware that gates by subscription or tenant must fail closed when tenant context is missing.** | (dari temuan analisa) | `RULE:tenant` | NEW | ✗ `CheckSubscription.php:30` meloloskan request |

## E. Booking dan ketersediaan

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-BOOK-01 | Booking is a critical domain: any change considers service, schedule, availability, tenant, booking state, payment state, concurrency, cancel, reschedule, multi-slot. | AG§18, DV§23, AR§16 | `RULE:booking` | MERGE | — |
| R-BOOK-02 | Booking states are exactly `pending`, `paid`, `cancelled`, `completed`; new states need a requirement change. | AG§21, RQ FR-BOOKING-007, RQ§43, DV§24 | CORE + `INV-BOOK-01` | MERGE | ✓ `BookingState` |
| R-BOOK-03 | Allowed transitions: pending→paid, pending→cancelled, pending→completed (owner marks an unpaid online booking as completed, e.g. paid on site), paid→completed, paid→cancelled. | AR§18, SD§19, keputusan lead 2026-10-30 | MAP + `INV-BOOK-02` | FIX | ✓ dokumen disesuaikan dengan `BookingState.php:23`. Walk-in dibuat langsung `paid`; pending→completed meninggalkan payment online `pending` sampai kedaluwarsa → item backlog untuk mencatat pembayaran tunai |
| R-BOOK-04 | Occupied slot = `paid`, `completed`, or `pending` created within the grace period; `cancelled` and stale `pending` never occupy. | RQ§44, AR§19, SD§14, TR§15 | CORE + `INV-BOOK-03` | MERGE | ✓ definisi di `BookingRules::applyOccupiesSlotCondition` |
| R-BOOK-05 | The occupancy rule and the 15-minute grace exist in exactly one place (`BookingRules`); every other layer calls it. | AG§57, AR§3.6, AR§19, SD§15, DV§25 | `RULE:booking` | MERGE | ✗ grace 15 menit ditulis ulang di `BookingController.php:495` dan `BookingManageController.php:302`; grace diabaikan (semua pending dianggap terisi) di `GetOwnerCalendarData.php:84` dan `GenerateScheduleReport.php:57`; definisi "terpesan" sendiri di laporan/analitik |
| R-BOOK-06 | One definition of "available" for customer booking, owner booking, rescheduling, calendar, schedule management and background expiry. | AR§17 | `RULE:booking` | KEEP | ✗ kalender menganggap semua pending terisi; ketersediaan reschedule pelanggan memakai query sendiri |
| R-BOOK-07 | Client-side or cached availability is never final; re-validate server-side inside the booking transaction. | AG§19, RQ NFR-PERF-003, DV§27 | `RULE:booking` | MERGE | ✓ `CreateBooking` mengecek ulang |
| R-BOOK-08 | Double-booking prevention needs all three: rule check + transaction/row lock + database unique constraint. | AG§20, AG§36, RQ FR-DATA-006, AR§21, SD§46, SD§48, DV§27 | CORE + `INV-BOOK-04` | MERGE | ✓ `lockForUpdate` + `unique_active_booking_slot` |
| R-BOOK-09 | A stale pending booking is evicted inside the authoritative booking transaction; correctness never depends on the scheduler alone. | RQ§45, AR§20, SD§16, DV§26 | `RULE:booking` | MERGE | ✓ `EvictStalePendingBookings` |
| R-BOOK-10 | Multi-slot = one reservation intent: compatible + contiguous slots, all-or-nothing persistence, one payment group. | AR§22, RQ FR-MULTIBOOK-*, RQ§50, SD§23, DV§28 | `RULE:booking` | MERGE | ✓ |
| R-BOOK-11 | Unsupported multi-slot operations are rejected, never partially applied. | RQ FR-MULTIBOOK-006 | REQ | KEEP | ✓ pelanggan tidak bisa membatalkan/reschedule multi-slot |
| R-BOOK-12 | Cancellation and rescheduling are business operations (authorize, lock, transition, release, notify), not raw status updates. | AR§23, AR§24, SD§20–22 | `RULE:booking` | MERGE | ✓ `CancelBooking`, `RescheduleBooking` |
| R-BOOK-13 | Owner cancellation does not automatically create a refund; customer cancellation of a paid booking does. | RQ§48, SD§21 | REQ (BR) | MERGE | ✓ `CancelBooking` |
| R-BOOK-14 | Walk-in bookings follow the same booking rules as online bookings. | RQ§51, PR§12, DV§29 | REQ (BR) | MERGE | ✓ `CreateWalkInBooking`: lock + evict + `isSlotOccupied`; dibuat `paid` dengan payment `cash` |
| R-BOOK-15 | Public booking URLs and customer management links must not break casually. | AG§58, DV§51 | `RULE:booking` | MERGE | — |

## F. Pembayaran dan refund

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-PAY-01 | Payment is a separate domain and lifecycle from booking. | AG§22, PR§21.3, RQ BR-CORE-008, AR§25, SD§27 | CORE + `INV-PAY-01` | MERGE | ✓ |
| R-PAY-02 | Payment states are exactly `pending`, `sukses`, `gagal`; expiry is a business condition, not a state. | RQ FR-PAYMENT-003, RQ§46, AR§25, SD§28, DV§30 | MAP + `INV-PAY-02` | MERGE | ✓ `PaymentState` |
| R-PAY-03 | Provider-specific code stays behind `PaymentGatewayInterface` / `PaymentGatewayManager`. | AG§23, A1, AR§26, AR§49 | `RULE:payment` | MERGE | ✗ `BookingController` memasang `MidtransConfig` di constructor; `MidtransPaymentService` dipakai untuk semua provider |
| R-PAY-04 | New payments use the configured driver; existing payments resolve their own provider (historical isolation). | A1, AR§26 | `RULE:payment` | KEEP | ✓ `forPayment()` |
| R-PAY-05 | Success requires trusted provider evidence (verified webhook or server status query), never a browser or client claim. | AG§24, RQ FR-PAYMENT-005, AR§27, SD§30, DV§31 | CORE | MERGE | ✓ |
| R-PAY-06 | Webhooks verify authenticity first (signature, timestamp, amount, currency) and fail closed on missing data. | AG§24, RQ NFR-SEC-009, TA P0 | `RULE:payment` | MERGE | ✓ |
| R-PAY-07 | Repeated provider events cause no duplicate state change, refund, booking side effect or notification. | AG§24, RQ FR-PAYMENT-007, AR§28, DV§32 | `RULE:payment` + `INV-PAY-03` | MERGE | ✓ |
| R-PAY-08 | Expired or failed payment never leaves a booking confirmed, and never leaves a slot permanently occupied. | RQ FR-PAYMENT-008/010, RQ§47 | REQ (BR) | MERGE | ✓ `ExpirePayment` |
| R-PAY-09 | Before expiring a payment, check the provider's state (for every provider, not only Midtrans). | RQ§53, OP§18 | `RULE:payment` | REWORD | ◐ `ExpireBookingPayments.php:65` hanya cek Midtrans |
| R-PAY-10 | Refund is its own lifecycle (`pending`, `processed`, `failed`), idempotent, never encoded in booking or payment status. | RQ FR-PAYMENT-011, RQ§48, AR§29, SD§33, DV§33 | `RULE:payment` + `INV-PAY-04` | MERGE | ✓ cek duplikat per `payment_id` |
| R-PAY-11 | **A success event that arrives after the payment was expired/cancelled must be reconciled (refund record + owner alert), never silently ignored.** | (dari temuan analisa, AB T4-01) | REQ (BR baru) | NEW | ✗ `ProcessSingaPayWebhook.php:201` hanya mencatat ke log |
| R-PAY-12 | Payment integration keeps reconciliation references (`order_id`, `external_id`, `provider`). | RQ FR-PAYMENT-004 | REQ | KEEP | ◐ `order_id` bisa ditebak (lihat R-SEC-07) |

## G. Langganan

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-SUB-01 | Plan ≠ Subscription; entitlement = plan + subscription state + rules. | PR§7.11, RQ§52, AR§31 | GLOS + REQ | MERGE | ✓ |
| R-SUB-02 | Feature and limit checks go through `EntitlementRules` only. | AG§25, RQ§52, AR§31, SD§35, DV§35 | `RULE:subscription` | MERGE | ✗ sidebar memeriksa Pro lewat `str_contains(namapaket, 'pro')` (`AppServiceProvider.php:68`) |
| R-SUB-03 | Subscription states: `trial`, `active`, `expired`, `cancelled`. | RQ FR-SUB-004 | MAP | KEEP | ✓ `SubscriptionState` |
| R-SUB-04 | Monthly booking quota is enforced atomically inside the booking transaction. | (implisit RQ FR-SUB-010) | `RULE:subscription` | REWORD | ◐ dicek di `CreateBooking` (atomik) dan diulang di `BookingController::selectDate` |

## H. Struktur kode

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-ARCH-01 | Layers: Presentation → Application (Actions) → Domain (rules) → Infrastructure/Persistence; dependencies point inward. | AR§5, AR§6, AR§46 | ARCH | MERGE | ◐ |
| R-ARCH-02 | Controllers only: validate/authorize → call an Action → respond. | AG§26, AR§3.4, AR§8, DV§16, RQ NFR-MAINT-001 | CORE + `RULE:controllers` | MERGE | ✗ `Customer/BookingController.php` 1.034 baris berisi logika domain |
| R-ARCH-03 | When touching a large controller, extract instead of adding more logic. | AG§27 | `RULE:controllers` | KEEP | — |
| R-ARCH-04 | Actions = one meaningful operation with defined input, output, errors and transaction boundary; no generic actions. | AG§28, AR§10, DV§18 | `RULE:actions` | MERGE | ✓ |
| R-ARCH-05 | Domain rules (`app/Domain`) are pure business rules, independent of HTTP. | AR§6.3, AR§11, DV§19 | `RULE:domain` | MERGE | ✓ |
| R-ARCH-06 | Services only for coherent reusable responsibilities; no `GeneralService` / `UtilityService`. | AG§29, DV§21, AR§57 | ARCH | MERGE | ◐ `MidtransPaymentService` adalah facade yang namanya menyesatkan |
| R-ARCH-07 | Repositories only when they isolate real persistence complexity. | AG§30, DV§22 | ARCH | MERGE | ✓ tidak ada |
| R-ARCH-08 | Models hold relationships, casts, scopes and small helpers — not workflows. | AG§31, AR§12, DV§20 | `RULE:models` | MERGE | ✓ |
| R-ARCH-09 | Form Requests validate input; business rules (e.g. "slot occupied") do not belong there. | AR§9, DV§17 | `RULE:controllers` | MERGE | ✓ |
| R-ARCH-10 | Routes are declarative; no logic in route closures; names follow `domain.resource.action`. | AG§43, DV§49, AG§41 | `RULE:routes` | MERGE | ◐ rute duplikat `programs`/`services`, `settings/payment-setting`/`payments` |
| R-ARCH-11 | New abstractions/folders need a named responsibility and an invariant they protect; no "architecture by folder". | AR§48, AR§57, DV§15 | ARCH | MERGE | — |
| R-ARCH-12 | Significant decisions (multi-domain, integration boundary, tenant/concurrency/payment strategy, new pattern) get an ADR. | AG§50, AG§70, AR§52, DV§70 | WF | MERGE | ✓ ADR-0001, ADR-0002 |
| R-ARCH-13 | Notification and mail failures must not roll back or corrupt the primary booking/payment transaction; send them after commit. | AR§32, SD§34 | `RULE:actions` | MERGE | ✓ `CreateBooking` mengirim setelah transaksi dengan try/catch |
| R-ARCH-14 | Scheduled commands call application actions/domain rules; they never re-implement business rules. | AR§34, DV§43, SD§44 | `RULE:actions` | MERGE | ◐ `ExpireBookingPayments` → `ExpirePayment` ✓; `CheckExpiredSubscriptions` mengubah status dengan query mentah |

## I. Data, transaksi, cache

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-DATA-01 | Every schema change is a new migration; never edit an applied migration. | AG§35, DV§39, OP§9 | `RULE:migrations` | MERGE | ✓ |
| R-DATA-02 | Before changing a column, search models, queries, views, seeders, tests, commands, reports. | AG§35, DV§40 | `RULE:migrations` | MERGE | — |
| R-DATA-03 | Critical invariants also live in the database (FK, unique, not-null, indexes). | AG§36, AR§35, SD§45 | ARCH | MERGE | ✓ |
| R-DATA-04 | Multi-record critical operations run in one transaction; no external HTTP calls inside it. | AG§37, AR§36, DV§41, SD§47, RQ FR-DATA-005 | `RULE:actions` | MERGE | ✓ `CreateBooking` memanggil gateway setelah commit |
| R-DATA-05 | Cache is derived, never the source of truth for booking, payment, authorization or tenant identity. | AG§38, AR§33, SD§43, DV§42, OP§14 | CORE | MERGE | ✓ |
| R-DATA-06 | Cache keys include every isolating dimension (tenant, service, date); availability mutations invalidate affected keys. | AR§33, SD§43, DV§42, OP§15 | `RULE:cache` | MERGE | ◐ cache tenant (`tenant:slug:*`) menyimpan seluruh atribut termasuk kunci gateway |
| R-DATA-07 | Preserve historical booking/payment data on deactivation. | RQ BR-CORE-011, RQ FR-SERVICE-005 | REQ | KEEP | ✓ |
| R-DATA-08 | Queued jobs must be idempotent and must not move critical state changes out of the request transaction. | AG§63, DV§44, OP§13 | `RULE:actions` | MERGE | — |

## J. Frontend

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-FE-01 | Blade is presentation: no queries, mutations, authorization or business calculations. | AG§32, AR§38, DV§47 | `RULE:blade` | MERGE | ✗ query di `components/owner/topbar`, `components/landing/pricing`, `owner/landing-page` |
| R-FE-02 | Alpine/JS handles interaction only; never the authority for availability, payment, authorization, tenant, entitlement. | AG§33, AR§38, DV§48 | `RULE:blade` | MERGE | — |
| R-FE-03 | Search and reuse components before creating new ones. | AG§39, AR§39, RQ NFR-MAINT-003 | `RULE:blade` | MERGE | — |
| R-FE-04 | When editing a large Blade file, extract repeated UI into components; no unrequested visual rewrites. | AG§40 | `RULE:blade` | KEEP | — |
| R-FE-05 | Validation is layered: client (UX) → request → business rule → DB constraint. | AG§34, AR§40, DV§38 | ARCH | MERGE | — |

## K. Pengujian

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-TEST-01 | Behaviour changes ship with tests covering happy path, validation, authorization, tenant isolation, state transitions and concurrency where relevant. | AG§44, DV§57, AR§44 | `RULE:tests` | MERGE | — |
| R-TEST-02 | Booking and payment changes cover the critical matrix (stale pending, concurrency, cross-tenant, cross-token, duplicate webhook, driver switch). | DV§58, DV§59 | `RULE:tests` | MERGE | — |
| R-TEST-03 | A failing test is evidence; never edit a test just to go green. | AG§45, DV§60 | CORE | MERGE | — |
| R-TEST-04 | Test names describe behaviour (`test_owner_cannot_access_other_tenant_bookings`). | AG§46 | `RULE:tests` | KEEP | — |
| R-TEST-05 | Add characterization tests before refactoring poorly tested critical code. | AG§48, DV§53 | WF | MERGE | — |
| R-TEST-06 | Run narrow → related → full suite; never declare done without running tests. | DV§55, DV§56 | WF | MERGE | — |
| R-TEST-07 | "Verified" needs evidence appropriate to the claim; concurrency claims need the production database engine. | RQ§3, RQ§60, TA "remaining 2" | WF | MERGE | ◐ tes berjalan di SQLite in-memory, `lockForUpdate` tidak berefek |
| R-TEST-08 | Manual verification (UI, payment sandbox, custom domain) is recorded in the work item when it is the evidence for done. | DV§61, OP§45 | WF | MERGE | — |

## L. Keamanan, log, error

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-SEC-01 | Customer tokens are scoped per capability (show, invoice, review, cancel, reschedule) and per booking; cross-scope or cross-booking use fails. | RQ§49, AR§30, SD§25–26, DV§34 | CORE + `INV-TOK-01` | MERGE | ◐ cek token di controller sudah per cakupan; tetapi halaman payment group (manage_token) menampilkan token pembatalan (`payment-show.blade.php:434`), sehingga manage_token efektif menjadi token induk — diputuskan di ADR Fase 2 |
| R-SEC-02 | Never expose internal errors or stack details to users. | AG§60, AR§41, SD§50 | CORE | MERGE | ✗ `CreateBooking.php:313` menampilkan `$e->getMessage()` |
| R-SEC-03 | Log important booking/payment/auth events with tenant, booking and payment context; never log passwords, secrets, payment credentials or tokens. | AG§61, AR§42, OP§25, TA P1 | `RULE:logging` | MERGE | ✓ tidak ditemukan token di log |
| R-SEC-04 | Uploads: validate type and size, store via configured disk, associate with tenant, no active content (SVG/HTML) without sanitization. | AG§62, SD§42, TA P1 | `RULE:uploads` | MERGE | ✓ aset, logo, dan cover memakai `image|mimes:jpg,jpeg,png,webp,gif|max:10240` |
| R-SEC-05 | Secrets never in source control; supplied via environment. | RQ NFR-SEC-010, OP§5 | CORE | MERGE | ✓ |
| R-SEC-06 | Treat external systems as unreliable: handle timeout, retry, duplicate, partial data. | AG§59, AR§37, DV§45 | `RULE:payment` | MERGE | — |
| R-SEC-07 | **Public identifiers (order_id, booking_code) are never sufficient on their own; every public endpoint that reveals data or changes state requires a scoped token.** | (dari temuan analisa) | CORE | NEW | ✗ lihat R-TEN-05 |
| R-SEC-08 | **Third-party credentials stored in the database are encrypted at rest; login and public state-changing endpoints are rate limited.** | (dari temuan analisa) | `RULE:security` | NEW | ✗ kunci Midtrans tenant disimpan polos; login tanpa throttle |

## M. Operasional

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-OPS-01 | Production: `APP_ENV=production`, `APP_DEBUG=false`; never enable debug to investigate. | OP§4, OP§41 | OPS | MERGE | — |
| R-OPS-02 | After config changes run `config:cache`; restart long-running workers. | OP§6 | OPS | KEEP | — |
| R-OPS-03 | Never rotate `APP_KEY` casually. | OP§30 | OPS | KEEP | — |
| R-OPS-04 | Cron runs `schedule:run` every minute; `bookings:expire-payments` every 15 min, `app:check-expired-subscriptions` daily. | OP§16–20, RQ§53 | OPS | MERGE | ✓ `routes/console.php` |
| R-OPS-05 | Database queue requires a running worker; retry failed jobs only after checking idempotency. | OP§11–13, OP§37 | OPS | MERGE | ◐ hampir semua email dikirim sinkron, bukan lewat queue |
| R-OPS-06 | Switching payment driver follows the switching checklist. | OP§23, A1 | OPS | KEEP | — |
| R-OPS-07 | Deployment sequence and post-deploy smoke checks. | OP§27, OP§28, OP§43 | OPS | MERGE | — |
| R-OPS-08 | Backups must be proven restorable, not just created. | OP§32–33, RQ NFR-REL-004/005 | OPS | MERGE | — |
| R-OPS-09 | Incident response playbooks (booking, payment, queue, cache, database, security). | OP§34–40 | OPS | MERGE | — |
| R-OPS-10 | Data integrity first, then restore service; never "fix" by creating duplicates or wrong states. | OP§54 | OPS | KEEP | — |
| R-OPS-11 | Do not invent infrastructure the repo does not establish; mark unknowns explicitly. | OP§49 | OPS | KEEP | — |
| R-OPS-12 | Operational claims need evidence (command output, logs, CI, restore test). | OP§50 | OPS | KEEP | — |
| R-OPS-13 | A release does not bypass a failing CI check without a documented reason. | OP§44 | WF | KEEP | — |
| R-OPS-14 | Environments are separated (local, CI, staging, production); no production secrets locally. | OP§46 | OPS | KEEP | — |

## N. Rilis bertahap (Tier)

| ID | Aturan | Sumber | Rumah | Status | Kode |
|---|---|---|---|---|---|
| R-REL-01 | Features are released in three tiers: Tier 1 live, Tier 2 visible but locked, Tier 3 hidden. | PR§29, A2 | PROD | MERGE | ✓ |
| R-REL-02 | Do not unhide Tier 3 or remove Tier 2 locks without lead approval and a release plan; `OwnerSidebarNavigationTest` guards this. | PR§29, A2§4 | CORE | MERGE | ✓ |
| R-REL-03 | Hidden Tier 3 modules must also be disabled server-side, not only removed from navigation. | (bertentangan dengan R-TEN-06) | ADR (keputusan Fase 2) | NEW | ◐ route Tier 3 masih aktif, mis. `POST /owner/settings/payment` |

---

## Blok requirement (dipindah utuh)

Semua ID di `3-REQUIREMENT.md` dipertahankan apa adanya di `requirements.md`: 165 FR, 11 BR-CORE, 47 NFR, aturan status §43–§53, matriks otorisasi §54, dan AC-GLOBAL-001…005. Yang dibuang hanya bagian meta (§1–§2, §55, §57, §65–§74).

**Requirement yang belum ada, ditambahkan dengan tanda `Proposed`** (butuh persetujuan lead):

| Usulan ID | Isi | Dasar |
|---|---|---|
| FR-AUTH-007 | Password reset via emailed token | Sudah dibangun (`ab24193`) |
| FR-AUTH-008 | Login and registration are rate limited | R-SEC-08 |
| FR-PUBLIC-005 | Platform landing page and plan pricing | Sudah dibangun (`fbe1539`) |
| FR-PAYOUT-001…003 | Platform balance, payout request, payout atomicity | Sudah dibangun; disebut di PR§29.2 dan TA |
| BR-PAY-001 | Late success after expiry → refund record + owner alert | R-PAY-11 |
| FR-MANAGE-008 | Public order/booking identifiers never grant access without a scoped token | R-SEC-07 |

---

## Pertentangan antar dokumen (diselesaikan saat penulisan ulang)

| # | Pertentangan | Penyelesaian usulan |
|---|---|---|
| K1 | Urutan prioritas keamanan berbeda (AG§54 vs DV§83) | Satu urutan di R-WF-13 |
| K2 | Urutan baca dokumen berbeda (AG§2, DV§3, RD§16) | Diganti peta di `AGENTS.md` |
| K3 | `AGENT.md` menyebut 7, 8, dan `adr/` sebagai "planned"; RD dan TR menyebutnya aktif | Hilang dengan sendirinya (`AGENT.md` dihapus) |
| K4 | Provider pembayaran "Midtrans" saja (AG§23, AR§4, OP§2, OP§55) vs dua provider (A1, RQ, AR§26) | Dua provider |
| K5 | Path webhook di A1 (`/webhook/singapay`) ≠ route asli (`/api/webhooks/singapay/transaction`, `/midtrans/webhook`) | Ikuti route asli |
| K6 | CI menurut OP§44: Refactor, main · menurut workflow: agathan, Refactor, main, payment | Ikuti workflow |
| K7 | Struktur di AR§7 menyebut `app/Policies` (tidak ada), tidak menyebut `Contracts`, `Domain/Subscription` | Dipindah ke `domain-map.md` dan dibetulkan |
| K8 | Tracker: "Group rescheduling Verified", padahal reschedule multi-slot ditolak oleh kode | Status ditulis ulang di `domain-map.md` |
| K9 | Tracker: baseline 368 tes, kenyataan 413 | Status pindah ke `domain-map.md`, angka terkini |
| K10 | ADR-0002 menyembunyikan menu Tier 3, sedangkan R-TEN-06 menyatakan visibilitas UI bukan otorisasi | Diputuskan lewat ADR di Fase 2 (R-REL-03) |

---

## Yang dibuang (DROP)

| Isi | Sumber | Alasan |
|---|---|---|
| Bagian "Document Status", "Purpose", "This document is not …" di tiap file | semua | Meta; satu peta di `docs/README.md` cukup |
| Diagram panah untuk alur linear dan "Final … Model" | AG§84, RD§23, PR§28, RQ§72, AR§60, SD§63, DV§84, OP§55 | Pengulangan; diganti daftar bernomor |
| "Recommended reading by task" dan "Which document should I read" | RD§15–16 | Diganti peta di `AGENTS.md` |
| Lima varian Definition of Done | AG§77–81, DV§72–76 | Digabung jadi R-WF-12 |
| Aturan penamaan umum PHP (PascalCase, camelCase) | AG§41, DV§50 | Sudah dicakup pedoman Boost; yang dipertahankan hanya penamaan domain (R-PROD-06) |
| Nasihat performa umum ("measure first") | AG§55, DV§63 | Diringkas jadi satu kalimat di ARCH |
| Sebutan RF-00…RF-09 | RD§22, TR§38, AG§2 | Riwayat ada di git (R-DOC-04) |
| Tabel status per fitur di tracker | TR§9–29 | Pindah ke `domain-map.md` dengan bukti terkini |
| Antrean verifikasi tracker | TR§30–36 | Menjadi item `BQ-*` |
| Seluruh `TECHNICAL-AUDIT` dan `audit-bug/` | TA, AB | Isinya menjadi item `BQ-*` (D4); file dihapus (D5) |

---

## Pelanggaran kode → calon item backlog (dibuat di langkah 1.6)

| Calon | Aturan | Prioritas | Launch blocker |
|---|---|---|---|
| BQ: akses invoice dan token kelola lewat `order_id` | R-TEN-05, R-SEC-01, R-SEC-07 | P0 | ya |
| BQ: `cancelPayment` tanpa token + tidak membatalkan transaksi gateway | R-TEN-05, R-PAY-11 | P0 | ya |
| BQ: rekonsiliasi pembayaran sukses yang telat + refund | R-PAY-11, R-PAY-09 | P0 | ya |
| BQ: enkripsi kredensial gateway tenant + rate limit login | R-SEC-08 | P0 | ya |
| BQ: pesan error internal tampil ke pelanggan | R-SEC-02 | P1 | ya |
| BQ: satukan aturan slot terisi (~8 tempat) | R-BOOK-05, R-BOOK-06 | P1 | — |
| BQ: catat pembayaran tunai saat owner menyelesaikan booking pending | R-BOOK-03 | P2 | — |
| BQ: `CheckExpiredSubscriptions` lewat aturan domain | R-ARCH-14 | P2 | — |
| BQ: `CheckSubscription` fail-open | R-TEN-10 | P1 | — |
| BQ: cek Pro di sidebar lewat `EntitlementRules` | R-SUB-02 | P2 | — |
| BQ: konfigurasi Midtrans keluar dari `BookingController`, ganti nama `MidtransPaymentService` | R-PAY-03, R-ARCH-06 | P2 | — |
| BQ: query di Blade (3 view) | R-FE-01 | P2 | — |
| BQ: rute duplikat dan penamaan route | R-ARCH-10 | P2 | — |
| BQ: audit 55 bypass scope | R-TEN-04 | P1 | — |
| BQ: tes konkurensi di MySQL (CI) | R-TEST-07 | P1 | — |
| BQ: Tier 3 dinonaktifkan di server | R-REL-03 | P1 | — |
| BQ: pecah `Customer/BookingController` | R-ARCH-02 | P2 (Fase 2) | — |

---

## Pertanyaan untuk lead

> **Dijawab 2026-10-30:** lead menyetujui semua (aturan NEW, requirement Proposed, daftar DROP). R-BOOK-03 diputuskan: dokumen mengikuti kode.

1. **R-BOOK-03**: apakah pending→completed memang diizinkan (misalnya untuk walk-in tunai)? Kalau ya, dokumen yang dibetulkan; kalau tidak, kode yang dibetulkan.
2. **Aturan NEW** (R-DOC-09, R-DOC-11, R-WF-15, R-WF-16, R-WF-17, R-TEN-10, R-PAY-11, R-SEC-07, R-SEC-08, R-REL-03): disetujui masuk sebagai aturan?
3. **Requirement `Proposed`** di tabel blok requirement: disetujui masuk dengan status `Proposed` dulu?
4. Ada aturan di daftar DROP yang ingin tetap dipertahankan?
