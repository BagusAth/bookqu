---
id: BQ-119
title: Render landing plan prices from the same source as subscription checkout
type: bug-fix
status: proposed
priority: P2
phase: 8
domain: public-site
requirements: [FR-PUBLIC-005, FR-SUB-001, FR-SUB-007, NFR-UX-005]
invariants: []
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: tiga harga dalam `resources/views/components/landing/pricing.blade.php:175,229,282` ditulis sebagai Rp149.000/Rp299.000/Rp499.000, sementara sumber harga paket adalah `plans.hargabulanan`.

**Kondisi saat audit:** angka bawaan `PlanSeeder` masih sama dengan landing. Bug ini bersyarat saat harga paket diperbarui, bukan bukti bahwa seluruh pengunjung sekarang sudah melihat tagihan yang berbeda.

### Reproduksi dan dampak

Dalam database uji, ubah Small menjadi Rp159.000 lalu GET `/`. HTML masih memuat **Rp149.000**, tidak memuat Rp159.000. Harga yang menjadi dasar checkout dapat berbeda dari harga yang baru dibaca calon pelanggan.

## Plan

1. Gunakan harga dari record Plan yang sama dengan tujuan CTA/checkout. Query paket sudah ada pada komponen; jangan menambah query per kartu atau menggabungkan refactor besar BQ-034.
2. Gunakan format Rupiah Indonesia yang konsisten pada ketiga kartu; label periode tetap sesuai siklus yang saat ini didukung.
3. Tetapkan fallback tampilan bila record paket hilang: jangan menampilkan angka rekaan atau CTA yang seolah dapat membeli paket tersebut. Pertahankan perilaku aman dan jelaskan keterbatasannya.
4. Tidak mengubah angka harga paket, `PlanSeeder`, siklus tagihan, atau manfaat paket. Harga baru tetap keputusan Product Lead; pekerjaan ini hanya menyamakan presentasinya.

## Invariants at risk

Harga tidak boleh diputuskan oleh JavaScript atau teks hardcode. Ketiga kartu dan checkout memakai identitas paket yang sama; tampilan tidak mengubah entitlement atau akses tenant.

## Tests

- Ubah harga setiap paket dalam fixture lalu render landing: angka tampil sama dengan database dan tujuan checkout paket yang sesuai.
- Pertahankan pengujian guest/admin/owner, paket aktif, trial, dan pemilihan tenant dalam `LandingPagePricingTest`.
- Cakup record paket hilang sesuai fallback yang dipilih, serta format angka pada mobile.
- FR-PUBLIC-005 tetap Proposed; item ini memperbaiki halaman existing tanpa mengesahkan perubahan harga/produk.

## Docs to update

Item ini. `docs/domains/public-site.md` hanya bila sumber data presentasi yang dijelaskan berubah.

## Evidence (fill when done)

**Bukti audit:** fixture Small Rp159.000 masih merender Rp149.000 di landing. Pengujian terisolasi termasuk 5 skenario / 29 asersi audit BQ-113. Tidak mengubah database lokal/produksi yang dipakai pengguna. Belum ada perbaikan/PR.
