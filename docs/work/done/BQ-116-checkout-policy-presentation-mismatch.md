---
id: BQ-116
title: Show the actual cancellation and reschedule policy before checkout
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: booking
requirements: [FR-BOOKING-012, FR-BOOKING-013, NFR-UX-005]
invariants: [INV-BOOK-06]
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: teks kebijakan sebelum pembayaran tidak mencerminkan aturan yang akan diberlakukan server.

- `resources/views/customer/partials/booking/checkout-form.blade.php:174` memanggil `<x-customer.booking-policy />` tanpa mengirim tenant atau nilai kebijakan.
- `resources/views/components/customer/booking-policy.blade.php:11` bergantung pada `isset($tenant)`, sehingga komponen anonim jatuh ke default **24 jam** pada reproduksi aktual.
- Bahkan bila tenant tersedia, satu nilai `cancel_before_hours` dipakai untuk pembatalan **dan** reschedule. `BookingRules::canCancel` dan `canReschedule` memakai field berbeda (`BookingRules.php:141,168`). Ekspresi truthy juga mengubah nilai 0 menjadi 24.
- Copy menjanjikan pembatalan/reschedule lewat link kelola tanpa menjelaskan bahwa pelanggan tidak dapat mengubah booking multi-slot sebagian secara mandiri.

### Reproduksi dan dampak

Siapkan tenant dengan `cancel_before_hours=6`, `reschedule_before_hours=48`; buka checkout. Browser tetap menampilkan kalimat bahwa kedua tindakan dapat dilakukan paling lambat **24 jam** sebelum sesi. Pelanggan dapat membayar dengan pemahaman kebijakan yang salah, lalu ditolak saat ingin reschedule 30 jam sebelum sesi.

## Plan

1. Kirim data kebijakan secara eksplisit ke komponen (atau proyeksi presentasi yang sudah disiapkan), tanpa membaca tenant secara implisit.
2. Tampilkan tenggat pembatalan dan reschedule secara terpisah, dengan default hanya untuk `null`. Nilai 0 harus disajikan sesuai aturan server yang berlaku.
3. Untuk pilihan multi-slot, jelaskan batas pengelolaan mandiri dan arahkan pengguna menghubungi bisnis. Jangan menjanjikan pembatalan seluruh grup bila operasi tersebut belum tersedia.
4. Pertahankan aturan `BookingRules`, jam batas yang tersimpan, dan proses pembayaran. Item ini memperbaiki presentasi kebijakan yang ada; perubahan eligibility adalah heavy lane terpisah.

## Invariants at risk

Tidak ada perubahan otorisasi atau eligibility. Batas multi-slot tetap utuh; copy tidak boleh memberi kesan bahwa link memberikan operasi di luar grant atau aturan booking.

## Tests

- Rendering checkout dengan batas 6/48 jam memuat dua nilai yang benar; tambahkan kasus `null` dan 0 tanpa menduplikasi tes aturan domain.
- Pilihan satu slot dan beberapa slot mendapat penjelasan yang sesuai; slug/custom-domain memakai data tenant yang benar.
- Browser 375 px: seluruh kebijakan terbaca, termasuk ketika memakai CTA bawah yang tetap terlihat. Tidak mengubah nilai default atau aturan server agar cocok dengan copy.

## Docs to update

Item ini; domain doc hanya jika dokumentasi presentasi yang ada menjadi tidak tepat. Tidak perlu mengubah aturan domain atau product decision.

## Evidence (fill when done)

**Bukti audit:** checkout dirender melalui route aktual memakai fixture tenant 6/48 jam; Edge/Playwright membaca teks 24 jam. Ini bukan sekadar dugaan dari scope Blade. Reproduksi menggunakan fixture dari audit bersama BQ-113; belum ada perbaikan atau PR.
