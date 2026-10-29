---
id: BQ-046
title: [BUG-T2-04] Calendar Redirect Trap: Menghapus Slot Waktu Melemparkan Owner Keluar dari Kalender
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: insights
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-2-OWNER-CORE-OPERATIONS.md at a68ffe8
---

# BQ-046: [BUG-T2-04] Calendar Redirect Trap: Menghapus Slot Waktu Melemparkan Owner Keluar dari Kalender

Migrated verbatim from the 2026-10 audit (TAHAP-2-OWNER-CORE-OPERATIONS.md, stage 2: Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`app/Http/Controllers/Owner/OwnerScheduleController.php:182`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Owner/OwnerScheduleController.php#L182)
- **Deskripsi & Dampak**:
  Tombol hapus slot jadwal di dalam drawer Kalender Operasional (`/owner/calendar`) mengirim request DELETE ke `/owner/schedule/slots/{id}`. Controller meng-hardcode:
  `return redirect('/owner/schedule')->with('sukses', 'Slot berhasil dihapus.');`
  Owner terlempar keluar dari kalender ke halaman jadwal reguler, kehilangan tanggal dan minggu kalender yang sedang dipantau.
- **Rekomendasi Perbaikan**:
  Ubah redirect menjadi kembali ke halaman pemanggil:
  ```php
  return redirect()->back()->with('sukses', 'Slot berhasil dihapus.');
  ```

## Evidence

_Fill in when done._
