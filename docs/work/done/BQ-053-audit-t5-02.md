---
id: BQ-053
title: [BUG-T5-02] Fatal Token Scope Mismatch pada Aksi Reschedule & Batal di Portal Payment Group
type: bug-fix
status: done
priority: P0
phase: 4
domain: customer-access
requirements: [FR-MANAGE-005, FR-MANAGE-006, FR-MANAGE-007, FR-MANAGE-009, FR-MANAGE-011]
invariants: [INV-TOK-01]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-001]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-053: [BUG-T5-02] Fatal Token Scope Mismatch pada Aksi Reschedule & Batal di Portal Payment Group

Audit source retained below. Scope escalation is confirmed by source inspection; the accepted resolution is [ADR-0006](../../adr/ADR-0006-management-link-module.md), implemented in BQ-001. This item records the implemented scope acceptance; see Evidence below. The original recommendation below is historical, not permission to widen legacy tokens.

> Related to BQ-001 (manage_token acting as master token).

> Confirmed 2026-10-30 (architecture review): `show.blade.php:106` always includes the cancel modal, which embeds `cancellation_token` (`cancel-modal:87`); `action-panel:52` and `payment-show:295/434` embed tokens too, so any one management token yields every capability. Route-level scope checks (RF09 `test_p1_01_token_scope_separation`) pass regardless. Addressed together with BQ-001 (review candidate #3 "Management link module").

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/customer/manage/payment-show.blade.php:295,434`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/payment-show.blade.php#L295)
  - [`app/Http/Controllers/Customer/BookingManageController.php:176-187`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Customer/BookingManageController.php#L176-L187)
- **Deskripsi & Dampak**:
  Pada portal payment group, tautan reschedule dan cancel menggunakan `$token` (`Payment->manage_token`) sebagai fallback. Di `BookingManageController::resolveBooking`, scope `'cancel'` hanya menerima `cancellation_token` dan `'reschedule'` hanya menerima `reschedule_token`. Penggunaan `manage_token` ditolak keras dengan status `HTTP 403 Forbidden: Token tidak valid`. Pelanggan tidak dapat membatalkan atau mengubah jadwal mereka dari portal grup.
- **Rekomendasi Perbaikan**:
  Perbarui `resolveBooking` agar `manage_token` diizinkan untuk scope cancel dan reschedule jika mencakup transaksi tersebut.

## Evidence

Selesai bersama BQ-001, 2026-10-31. Management grant memberi operasi yang disebutkan secara eksplisit untuk anggota snapshot; Checkout tidak memperoleh kemampuan booking cancel/reschedule/review dari HTML atau navigasi. Semua hidden token, query token operasi lain dan fallback legacy dihapus. Bentuk route lama hanya adapter sesi grant baru.

Bukti: CustomerAccessTest menguji sembilan jalur management dengan identifier saja, Checkout, foreign grant dan Management yang benar; lima jalur storefront diuji dengan payment/tenant lain. Test HTML menolak seluruh bearer/legacy token. BookingManageTest, BookingReviewTest, PaymentGroupManagementTest serta ProductionLogicSpecificationTest mempertahankan perjalanan positif, refund, multi-slot refusal dan closed-date refusal dengan grant sebenarnya. Pencabutan di antara preflight dan mutasi diperiksa ulang di bawah kunci tenant. Hasil akhir tersedia di Evidence BQ-001 dan PR yang sama.


## Accepted plan

1. Use ADR-0006's explicit profile matrix. A new Management grant authorizes its named operations directly; Checkout/Invoice/narrow grants cannot acquire them from another page or callback. Do not add cancel/reschedule authority to the old `manage_token`.
2. Replace token-bearing models/forms/action links in individual and group pages with module-prepared permitted data and session-backed links. GETs must not issue credentials.
3. Test HTML and navigation chains as well as direct endpoints: opening detail/invoice must not expose another grant, and narrow profiles must still fail unrelated mutations/review. Verify correct same-group member access, foreign group/tenant rejection and current multi-slot refusals.
4. Retain the full Management journey regression and update legacy-link tests only for the explicitly accepted prelaunch cutover. Reuse the BQ-001 rule matrix; record the distinct HTTP regression evidence here before closure.
