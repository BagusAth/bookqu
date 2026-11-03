---
id: BQ-115
title: Align public and subscription feature promises with the released product
type: bug-fix
status: rejected
priority: P1
phase: 8
domain: public-site
requirements: [FR-PUBLIC-005, FR-SUB-001, NFR-UX-005]
invariants: [INV-SUB-01]
lane: light
approval: none
decision:
launch-blocker: true
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: calon owner membaca janji fitur yang belum dirilis atau belum tersedia. Daftar fitur paket juga dipakai pada halaman langganan sehingga pemilik akun dapat mengira upgrade langsung membuka fitur tersebut.

- `config/plans.php:23-39` menawarkan WhatsApp reminders, promo/voucher, BYOPG, multi-location, custom domain, landing-page builder, white-label, dan automation workflow tanpa penanda keterbatasan rilis.
- `resources/views/components/landing/features.blade.php:62` menjanjikan kalender terhubung langsung dengan Google Calendar; baris 92 menjanjikan pembayaran DP.
- `app/View/Components/PlanFeatures.php` dan `resources/views/components/plan-features.blade.php` menampilkan daftar tersebut dengan tanda centang; tidak membedakan tersedia dan rencana.
- `docs/product.md` bagian Release tiers dan `config/modules.php` menetapkan integrasi/analytics terkunci, voucher/staff/builder/direct gateway tersembunyi, dan beberapa kemampuan sebagai arah masa depan. BQ-079 menetapkan pembayaran platform saja untuk v1.

### Reproduksi dan dampak

Buka landing sebagai guest, pilih Medium/Pro, lalu baca daftar fitur. Janji BYOPG dan Multi-location muncul pada HTML dan browser mobile. Owner yang memilih paket dapat membayar berdasarkan kemampuan yang tidak dapat digunakan. Ini perluasan audit ke **janji publik**, bukan pengulangan BQ-097 tentang kontrol owner dan sisa modul tersembunyi.

Daftar mana yang dijanjikan per paket diputuskan Product Lead di [D-008](../product-decisions.md); usulannya ada di `docs/owner-portal.md` §9. Fallback D-008 menjadikan item ini **launch blocker**: kalau D-008 belum dijawab, klaim yang belum tersedia tetap dihapus sebelum rilis (poin 1 dan 5).

## Plan

1. Cocokkan setiap klaim terhadap produk dan release gate yang berlaku; buat matriks tersedia / terkunci / tersembunyi / belum diimplementasikan dalam deskripsi PR.
2. Koreksi copy dan daftar fitur agar hanya menjanjikan manfaat yang benar-benar dapat dipakai pada rilis tersebut. Hapus klaim yang belum tersedia atau beri keterangan rencana yang tegas, sesuai keputusan produk yang sudah ada.
3. Gunakan daftar yang sama pada landing dan subscription sesuai BQ-080. Perbaiki pula klaim sinkronisasi Calendar dan DP; jangan mengganti masalah copy dengan membangun fitur baru.
4. Pertahankan harga dan entitlement. Jangan mengubah `config/modules.php`, membuka Tier 2/3, atau mengaktifkan BYOPG. Perluasan manfaat paket adalah product change dan memerlukan keputusan terpisah.

## Invariants at risk

Release gate dan `EntitlementRules` tetap menjadi otoritas. Sinkronisasi daftar pemasaran tidak boleh dianggap sebagai persetujuan fitur/paket baru. FR-PUBLIC-005 masih berstatus Proposed dalam requirements; item ini mengoreksi halaman yang sudah ada, tidak mengesahkan requirement itu atau manfaat baru.

## Tests

- Perbarui pengujian presentasi fitur bersama landing/subscription: klaim yang belum tersedia tidak muncul sebagai manfaat siap pakai.
- Pertahankan pengujian tautan guest, owner terverifikasi/belum terverifikasi, paket aktif, dan tenant aktif.
- Cek browser 375 px dan desktop: penanda ketersediaan terbaca sebelum CTA; tidak sekadar tooltip atau teks tersembunyi.
- Perubahan copy murni tidak perlu tes baru berlebihan; jalankan pengujian presentasi terkait yang sudah ada.

## Docs to update

Item ini dan release notes bila koreksi janji perlu dijelaskan. Jangan mengubah produk/requirements untuk membenarkan klaim yang keliru.

## Evidence (fill when done)

**Bukti audit:** HTML landing dari fixture SQLite dan Edge mobile menampilkan BYOPG, Multi-location, serta daftar Pro tanpa penanda belum tersedia. Referensi produk/gate diperiksa pada snapshot `f361cc2`. Bukti HTTP termasuk dalam 5 skenario / 29 asersi yang dirujuk BQ-113. Belum ada perubahan copy, implementasi, atau PR; light lane berlaku hanya selama lingkupnya koreksi janji terhadap produk yang telah diputuskan.
