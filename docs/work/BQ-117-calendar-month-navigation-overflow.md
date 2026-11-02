---
id: BQ-117
title: Prevent month navigation from skipping or repeating a calendar month
type: bug-fix
status: proposed
priority: P2
phase: 8
domain: schedule
requirements: [FR-CALENDAR-001, FR-CALENDAR-002, NFR-UX-002]
invariants: [INV-TEN-01]
lane: light
approval: none
decision:
launch-blocker: false
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2`: navigasi bulan owner memakai `subMonth()` / `addMonth()` sebelum `startOfMonth()` (`app/Actions/Calendar/GetOwnerCalendarData.php:43-45`). Tanggal 29–31 dapat meluap ke bulan lain sebelum dinormalkan.

### Reproduksi dan dampak

1. Buka `/owner/calendar?view=month&date=2026-01-31` (misalnya berpindah dari tampilan hari pada tanggal itu).
2. Target tombol berikutnya adalah **2026-03-01**, sehingga Februari terlewati.
3. Pada `view=month&date=2026-03-31`, target tombol sebelumnya adalah **2026-03-01**, sehingga kalender tetap berada pada Maret.

Owner sulit menemukan booking bulan berikut/sebelumnya. Ini berbeda dari layout grid BQ-096, timezone BQ-082, dan redirect setelah menghapus slot BQ-046.

## Plan

1. Hitung navigasi bulan dari awal bulan yang sedang ditampilkan atau gunakan operasi tanpa overflow. Jangan mengubah objek `currentDate` yang dipakai oleh view lain.
2. Pertahankan `view`, filter layanan/status, dan routing pada tautan navigasi yang sudah ada.
3. Jangan mengubah navigasi harian/mingguan, query booking, atau aturan availability.

## Invariants at risk

Query tetap tenant-scoped. Perubahan hanya target bulan; tidak mengubah jadwal atau booking yang tersimpan.

## Tests

- Tes fitur dengan `travelTo`: Januari 31 → Februari 1; Maret 31 → Februari 1; Desember → Januari tahun berikut.
- Sertakan Februari tahun kabisat dan tanggal 29/30; periksa URL tombol aktual, bukan hanya perhitungan terpisah.
- Filter layanan/status bertahan; navigasi hari/minggu tetap benar.
- Browser: berpindah dari day ke month pada tanggal 31 lalu tekan sebelumnya/berikutnya tanpa bulan terlewati.

## Docs to update

Item ini. Domain doc hanya bila kontrak navigasi yang didokumentasikan berubah.

## Evidence (fill when done)

**Bukti audit:** route owner dengan user terautentikasi pada SQLite terisolasi mengembalikan `nextDate=2026-03-01` untuk Januari 31 dan `prevDate=2026-03-01` untuk Maret 31. Jam dibekukan dengan `travelTo`. Bagian dari 5 skenario / 29 asersi audit yang dirujuk BQ-113; belum ada implementasi/PR.
