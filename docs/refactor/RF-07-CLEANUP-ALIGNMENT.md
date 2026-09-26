# RF-07 — CLEANUP, TERMINOLOGY ALIGNMENT & SHARED INFRASTRUCTURE

> **Work Order:** RF-07
> **Phase:** Cleanup & Alignment
> **Baseline Branch:** `Refactor`
> **Baseline Commit:** `3ef4ec2`
> **Status:** Completed
> **Depends On:** RF-00 Foundation → RF-01 Booking → RF-02 Payment → RF-03 Schedule → RF-04 Application Layer → RF-05 Presentation → RF-06 Subscription
> **Related Phase:** RF-08 Final Audit
> **Primary Areas:** Route architecture, Shared traits, Support services, Authentication controllers, Terminology alignment
> **Primary Goal:** Menghapus seluruh lingering inline business logic pada routes, menstandarkan shared traits/infrastructure, membersihkan dead code, dan menyelaraskan terminologi domain tanpa mengubah intended product behavior.

---

# 1. Purpose

RF-07 adalah fase konsolidasi dan pembersihan arsitektur BookQu sebelum dilakukannya audit final (RF-08).

Setelah ekstraksi besar pada RF-01 hingga RF-06, terdapat beberapa area yang membutuhkan alignment:
1. `routes/web.php` sebelumnya masih memiliki inline route closures untuk autentikasi, registrasi, verifikasi email, dan logout.
2. Shared traits (`ResolvesOwnerTenant`, `ClearsBookingCache`) dan support classes (`TenantContext`, `CustomerBookingRoutes`) perlu dipastikan tetap konsisten, minim ketergantungan, dan aman terhadap tenant isolation.
3. Import mati (dead imports) dan metode privat yang tidak lagi digunakan (seperti `generateOrderId` lama) dibersihkan.
4. Terminologi domain dinormalisasi antara database legacy (`idtenant`, `iduser`, `tanggalbooking`, `metode`, `jumlah`) dan representasi domain modern (`BookingState`, `PaymentState`, `SubscriptionState`, `PlanCapability`, `EntitlementRules`).

---

# 2. Scope & Changes

## 2.1 Route Architecture (`routes/web.php`)
* **Pemindahan Route Closures ke Dedicated Controllers:**
  * `AuthenticatedSessionController` (`create`, `store`, `destroy`): menangani login, regenerasi session, pengosongan context tenant session, dan logout.
  * `RegisteredUserController` (`create`, `store`): menangani form registrasi, pembuatan User, penentuan slug unik Tenant, inisialisasi trial via `CreateTrialSubscription`, dan pengiriman notifikasi verifikasi email.
  * `EmailVerificationController` (`notice`, `verify`, `send`): menangani prompt verifikasi email, eksekusi fulfill verifikasi, dan pengiriman ulang email verifikasi.
* **Form Requests untuk Autentikasi:**
  * `App\Http\Requests\Auth\LoginRequest`
  * `App\Http\Requests\Auth\RegisterRequest`
* **Pembersihan Import:**
  * Menghapus import tidak terpakai seperti `OwnerPortalController`, `Hash`, `User`, `EmailVerificationRequest`, dan `Password` dari `routes/web.php`.

## 2.2 Shared Infrastructure Audit
* **`TenantContext` (`app/Support/TenantContext.php`):**
  * Memastikan request-scoped context, reset otomatis pada lifecycle request, dan pencegahan kebocoran tenant (Fail-closed behavior).
* **`ResolvesOwnerTenant` (`app/Traits/ResolvesOwnerTenant.php`):**
  * Memvalidasi kepemilikan tenant terhadap `auth()->id()`, sinkronisasi ke `TenantContext`, dan mengeliminasi ketergantungan manual ke session.
* **`ClearsBookingCache` (`app/Traits/ClearsBookingCache.php`):**
  * Mendelegasikan seluruh invalidasi cache ke `ScheduleAvailabilityCache` yang terpusat.
* **`CustomerBookingRoutes` (`app/Support/CustomerBookingRoutes.php`):**
  * Menyediakan resolusi URL transparan baik untuk domain kustom maupun subpath slug usaha.

## 2.3 Terminology & Domain Alignment
* Menyelaraskan seluruh konstanta status di domain models:
  * `BookingState`: `STATUS_PENDING`, `STATUS_PAID`, `STATUS_CONFIRMED`, `STATUS_COMPLETED`, `STATUS_CANCELLED`.
  * `PaymentState`: `STATUS_PENDING`, `STATUS_SUKSES`, `STATUS_GAGAL`, `STATUS_EXPIRED`, `TIPE_BOOKING`, `TIPE_SUBSCRIPTION`.
  * `SubscriptionState`: `STATUS_TRIAL`, `STATUS_ACTIVE`, `STATUS_EXPIRED`, `STATUS_CANCELLED`.
  * `PlanCapability`: `PLAN_SMALL`, `PLAN_MEDIUM`, `PLAN_PRO`.
* Menjaga integritas kolom schema database Indonesia tanpa migrasi yang berisiko merusak data eksisting.

---

# 3. Acceptance Criteria

* [x] **AC-RF07-001 — Clean Routes:** Tidak ada business workflow closures yang tertinggal di `routes/web.php`.
* [x] **AC-RF07-002 — Auth Controllers:** Login, registrasi, verifikasi email, dan logout diproses oleh dedicated controller dengan FormRequests.
* [x] **AC-RF07-003 — Dead Imports & Unused Code:** Import usang dan dead code dari pemindahan RF-01 sampai RF-06 telah dibersihkan.
* [x] **AC-RF07-004 — Shared Traits Alignment:** Shared traits bekerja konsisten dengan TenantContext dan cache service.
* [x] **AC-RF07-005 — 100% Test Passing:** Seluruh test suite (301 tests, 1478 assertions) lulus 100% tanpa regresi.

---

# 4. Verification

* `php artisan test tests/Feature/Auth/AuthTest.php` -> PASS (6/6 tests)
* `php artisan test tests/Feature/CoreFlowIntegrationTest.php` -> PASS (20/20 tests)
* `php artisan test` -> PASS (301 tests, 1478 assertions, 0 failure)
