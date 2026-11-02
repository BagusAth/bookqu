---
id: BQ-120
title: Stop offering owner reschedule slots that the availability claim will refuse
type: bug-fix
status: proposed
priority: P1
phase: 8
domain: schedule
requirements: [FR-SCHEDULE-005, FR-SCHEDULE-009, FR-BOOKING-011, NFR-UX-005]
invariants: [INV-SCH-01, INV-BOOK-03, INV-BOOK-04, INV-TEN-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: false
depends-on: [BQ-012, BQ-069]
---

## Problem

Audit 2026-11-02 pada `f361cc2` menemukan celah pembacaan setelah perbaikan BQ-012/BQ-069: endpoint reschedule owner masih menawarkan slot pada hari yang ditutup sebagai tersedia, walaupun operasi tulis sudah benar menolaknya.

- `OwnerBookingController::getAvailableSlots` memanggil `GetAvailableSchedules::getSlotsForReschedule`.
- `app/Actions/Schedule/GetAvailableSchedules.php:210-246` menyaring status slot, occupancy, dan waktu lampau, tetapi tidak memasukkan blocked date ke `is_available`.
- `ClaimSlots` tetap menolak hari tutup. Ini **bukan** temuan bahwa owner berhasil membuat booking di hari tutup dan bukan konflik booking aktif ketika menutup tanggal (BQ-108).

### Reproduksi dan dampak

1. Buat slot tersedia tanggal 5 November dan tutup tanggal tersebut melalui `owner_blocked_dates`.
2. Buka reschedule booking milik tenant itu pada tanggal tersebut.
3. GET `available-slots?tanggal=2026-11-05` mengembalikan slot dengan **`is_available=true`**.
4. Pilih slot dan simpan; POST reschedule ditolak **422 / `success=false`**.

Owner ditawari pilihan yang pasti gagal, mengisi alasan perubahan, lalu harus mengulang. Riwayat selesai BQ-012/BQ-069 tidak diubah; item ini melacak gap reader yang terkonfirmasi.

## Plan

1. Terapkan pembacaan bookability dari otoritas availability yang sudah ada; jangan membuat salinan aturan blocked/past/occupancy baru di controller atau Blade.
2. Slot hari tutup tidak dapat dipilih, atau respons menjelaskan tanggal tutup tanpa menawarkan slot. Pertahankan label slot saat ini tanpa menjadikannya pengecualian untuk memesan hari tutup.
3. Selaraskan status tersedia pada UI dengan klaim server untuk service/tenant terkait. Pemeriksaan server di bawah tenant lock tetap wajib walaupun GET sudah benar.
4. Batasi perubahan pada parity reader; refactor kepemilikan cache penuh tetap BQ-075, konflik saat menutup tanggal tetap BQ-108, dan stale response tetap BQ-113.

## Invariants at risk

Read/write agreement mengikuti INV-SCH-01; cache bukan sumber otoritas claim. Tenant isolation, current-booking exclusion, dan reclaim pending kedaluwarsa tidak boleh berubah.

## Tests

- Endpoint owner: hari tutup tidak memiliki slot selectable; POST tetap menolak dan booking lama tidak berubah.
- Hari dibuka kembali: reader menawarkan slot dan claim dapat berhasil bila syarat lain terpenuhi.
- Kasus slot lampau, sudah ditempati, pending kedaluwarsa, current slot, serta tenant/service lain tetap benar. Tambah hanya cabang yang belum dibuktikan oleh suite existing.
- Pakai `travelTo`; tes terkait lalu verifikasi heavy lane. Jangan mengklaim safety concurrency dari SQLite.

## Docs to update

`docs/domains/schedule.md` dan `docs/domains/booking.md` jika gap parity ditutup. Tautkan item ini sebagai tindak lanjut; jangan menulis ulang Evidence item selesai.

## Evidence (fill when done)

**Bukti audit:** HTTP terisolasi membuktikan GET `is_available=true` untuk slot pada blocked date, disusul POST 422. Termasuk 5 skenario / 29 asersi audit pada BQ-113. Tidak ada bukti bypass claim. Belum ada implementasi/PR.
