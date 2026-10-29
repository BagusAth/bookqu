---
id: BQ-044
title: [BUG-T2-02] Tabrakan Delimiter Kutip `@click` pada Kartu Layanan, Kategori, dan Jadwal
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: catalog
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-2-OWNER-CORE-OPERATIONS.md at a68ffe8
---

# BQ-044: [BUG-T2-02] Tabrakan Delimiter Kutip `@click` pada Kartu Layanan, Kategori, dan Jadwal

Migrated verbatim from the 2026-10 audit (TAHAP-2-OWNER-CORE-OPERATIONS.md, stage 2: Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/owner/programs.blade.php:124`](file:///c:/laragon/www/bookqu/resources/views/owner/programs.blade.php#L124)
  - [`resources/views/owner/categories.blade.php:88`](file:///c:/laragon/www/bookqu/resources/views/owner/categories.blade.php#L88)
  - [`resources/views/owner/schedule.blade.php:165`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L165)
- **Deskripsi & Dampak**:
  Event klik menggunakan pola `@click='editProgram(@json($program))'`. Jika nama program atau kategori memiliki tanda petik tunggal (misal: *"Men's Cut"* atau *"D'Salon"*), string JSON dipotong sebelum waktunya, memicu JavaScript syntax error fatal saat tombol "Edit" diklik.
- **Rekomendasi Perbaikan**:
  Gunakan pemanggilan fungsi aman dengan encoding hex:
  ```blade
  x-on:click="editProgram({{ json_encode($program, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }})"
  ```

## Evidence

_Fill in when done._
