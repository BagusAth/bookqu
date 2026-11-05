---
id: BQ-118
title: Keep Enter in storefront service search from submitting an empty booking form
type: bug-fix
status: in-progress
priority: P2
phase: 4
domain: booking
requirements: [FR-PUBLIC-004, FR-BOOKING-001, NFR-UX-003, NFR-UX-005]
invariants: []
lane: light
approval: none
decision:
launch-blocker: true
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: input pencarian layanan berada di dalam formulir pemilihan layanan (`resources/views/customer/booking/program-selection.blade.php:9-43`). Menekan Enter pada input tersebut memicu implicit form submission sebelum pelanggan memilih layanan.

### Reproduksi dan dampak

1. Buka storefront, ketik nama layanan di kolom "Cari layanan...".
2. Tekan Enter, sebagaimana pola pencarian umum pada keyboard desktop/mobile.
3. Browser mengirim POST ke `booking/select-program` dengan `service_id` kosong. Server menolak input, alih-alih mempertahankan pengguna pada hasil pencarian.

Edge/Playwright mengonfirmasi request POST tersebut. Ini berbeda dari empty state kategori dan karakter kutip dalam BQ-082.

## Plan

1. Pisahkan interaksi pencarian dari submit booking, atau cegah implicit submit pada input pencarian secara khusus. Jangan mematikan Enter untuk seluruh halaman.
2. Pertahankan auto-submit saat kartu layanan dipilih dan interaksi keyboard Enter/Space pada kartu. Tombol hapus pencarian tetap hanya mengubah hasil pencarian.
3. Bila menambah guard submit, pengguna tanpa pilihan tetap berada pada layar dengan umpan balik yang sesuai; server tetap memvalidasi service ID dan tenant.

## Invariants at risk

Tidak ada perubahan domain atau otorisasi. Pilihan booking harus tetap berasal dari layanan tenant yang tersedia; JavaScript tidak menggantikan validasi server.

## Tests

- Browser desktop/mobile: ketik pencarian lalu Enter → tidak ada POST booking atau navigasi; hasil pencarian tetap terlihat.
- Pilih kartu dengan mouse, Enter, dan Space → service ID yang benar dikirim satu kali.
- Cek input metode komposisi bila handler keyboard diubah; Enter untuk menyelesaikan komposisi teks tidak boleh membuat booking.
- Jalankan tes storefront terkait; tidak perlu tes PHP yang hanya mencocokkan teks atribut JavaScript.

## Docs to update

Item ini; tidak ada perubahan domain/requirement.

## Evidence (fill when done)

**Bukti audit:** Edge menjalankan Alpine pada HTML route storefront aktual dengan fixture sintetis; menekan Enter pada pencarian menangkap POST `booking/select-program` dan `service_id` kosong. Request ditahan oleh harness agar tidak membuat booking atau menghubungi provider. Belum ada perbaikan/PR.
