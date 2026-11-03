---
id: BQ-114
title: Keep existing booking times stable when the service duration changes
type: bug-fix
status: approved
priority: P1
phase: 4
domain: booking
requirements: [FR-SERVICE-003, FR-SERVICE-005, FR-BOOKING-008, FR-MANAGE-003, FR-CALENDAR-003]
invariants: [INV-SCH-01, INV-BOOK-04, INV-BOOK-06]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: detail dan tiket booking lama menghitung ulang jam selesai memakai **durasi layanan sekarang**, bukan rentang yang telah dipesan. Mengedit katalog dapat mengubah informasi reservasi pelanggan tanpa reschedule.

| Sumber | Perilaku |
|---|---|
| `app/Actions/Service/UpdateService.php:57-60` | Mengizinkan perubahan `durasi`; slot dan booking yang sudah ada tidak ikut diperbarui |
| `resources/views/customer/manage/show.blade.php:20-28` | Jam akhir dan rentang Google Calendar dihitung dari `layanan->durasi` terbaru |
| `resources/views/customer/booking/invoice.blade.php:15-35` | Invoice storefront dan teks berbagi memakai durasi terbaru |
| `resources/views/customer/manage/payment-show.blade.php:50-59` | Rentang grup dan setiap sesi memakai durasi terbaru |
| `resources/views/customer/manage/payment-invoice.blade.php:25,110` | Invoice grup juga menghitung ulang durasi |

### Reproduksi dan dampak

Booking berbayar awalnya pukul 14.00, slot berakhir 15.00, durasi layanan 60 menit. Ubah layanan menjadi 90 menit, lalu buka ulang detail: tampil **15.30**, sedangkan `schedules.jam_selesai` masih **15:00:00**. Pelanggan dan owner memiliki informasi berbeda; ekspor Calendar berpotensi mengalokasikan waktu keliru. Ini bukan masalah timezone Google Calendar yang sudah ada di BQ-082.

## Plan

1. Tech Lead menentukan sumber rentang reservasi yang stabil bagi booking lama dan baru. Mulai dari data slot yang sudah tersimpan; evaluasi kebutuhan snapshot hanya bila data itu tidak cukup. Jangan menambah kolom atau migrasi sebelum persetujuan skema.
2. Periksa konvensi akhir slot sebelum memilih solusi: `BulkCreateSchedules.php:114` menyimpan akhir inklusif dengan `subMinute()`, sedangkan data lain dapat menyimpan akhir tepat. Jangan langsung mengganti semua tampilan dengan `jam_selesai` atau menambah satu menit untuk seluruh data tanpa aturan yang disepakati.
3. Siapkan satu proyeksi rentang untuk halaman kelola, invoice, teks WhatsApp/Calendar, dan tampilan owner terkait. Blade hanya merender rentang yang sudah disiapkan.
4. Tinjau pembacaan slot baru dan validasi multi-slot setelah durasi katalog diubah. Pilihan UI dan claim harus konsisten terhadap durasi/rentang yang disepakati, tanpa memindahkan atau memperpanjang booking lama diam-diam.
5. Tetapkan fallback eksplisit bagi booking legacy tanpa slot; jangan mengarang durasi historis yang tidak tersimpan. Penyesuaian harga historis ditangani BQ-121, bukan item ini.

## Invariants at risk

Perubahan katalog tidak boleh diam-diam mengubah reservasi yang sudah sah. Pertahankan occupancy tunggal, contiguity, tenant isolation, dan transaksi claim. Jika keputusan mengubah aturan produk durasi booking, ajukan product decision sebelum implementasi bagian tersebut.

## Tests

- Booking 14.00–15.00 tetap tampil dengan rentang yang sama setelah durasi katalog menjadi 90 menit; periksa detail individual/grup dan invoice.
- Decode rentang Google Calendar dan teks berbagi: keduanya sama dengan rentang reservasi yang ditampilkan.
- Cakup dua konvensi akhir slot, rentang multi-slot, pergantian tanggal bila didukung, dan booking legacy tanpa relasi slot.
- Periksa konsistensi pilihan slot/claim setelah durasi katalog berubah. Pakai `travelTo`, fakes eksternal, dan verifikasi sesuai heavy lane.

## Docs to update

`docs/domains/booking.md`, `docs/domains/schedule.md`, dan `docs/domains/catalog.md` hanya setelah sumber rentang diputuskan; requirement/ADR diperbarui hanya jika aturannya berubah.

## Evidence (fill when done)

**Bukti audit:** skenario HTTP pada SQLite terisolasi membuktikan detail menampilkan 15.30 setelah layanan diubah menjadi 90 menit, sementara akhir slot tetap 15:00:00. Bagian dari 5 skenario / 29 asersi audit pada BQ-113; bukan tes perbaikan. Belum ada implementasi/PR. Launch-blocker dan keputusan sumber waktu menunggu Tech Lead.
