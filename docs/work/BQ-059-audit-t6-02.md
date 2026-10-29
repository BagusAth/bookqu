---
id: BQ-059
title: [BUG-T6-02] Kolom Isi Ulasan Pelanggan Blank / Kosong Melompong pada Seluruh Halaman Ulasan
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: customers-reviews
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md at a68ffe8
---

# BQ-059: [BUG-T6-02] Kolom Isi Ulasan Pelanggan Blank / Kosong Melompong pada Seluruh Halaman Ulasan

Migrated verbatim from the 2026-10 audit (TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md, stage 6: CRM Pelanggan, Moderasi Ulasan, Billing Langganan & Pengaturan Akun). Status in the current code is unverified — check it first (BQ-039).

> Spot check 2026-10-30: `resources/views/owner/reviews.blade.php:137` still prints `$rev->review` (column is `komentar`) — confirmed open.

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/owner/reviews.blade.php:137`](file:///c:/laragon/www/bookqu/resources/views/owner/reviews.blade.php#L137)
  - [`app/Models/Review.php:18`](file:///c:/laragon/www/bookqu/app/Models/Review.php#L18)
- **Deskripsi & Dampak (Data Presentation Error)**:
  Halaman ulasan memanggil `{{ $rev->review }}`. Namun kolom database pada tabel `reviews` dan atribut fillable model Eloquent bernama `komentar`. Pemanggilan `$rev->review` selalu mengembalikan `null`. **Seluruh isi teks testimoni pelanggan di halaman ulasan bisnis menjadi kosong melompong** (hanya bintang rating dan nama customer yang muncul).
- **Rekomendasi Perbaikan**:
  Ganti `{{ $rev->review }}` menjadi:
  ```blade
  <p class="text-xs text-bq-text leading-relaxed pl-12">{{ $rev->komentar }}</p>
  ```

## Evidence

_Fill in when done._
