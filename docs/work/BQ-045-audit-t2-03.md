---
id: BQ-045
title: [BUG-T2-03] Relasi Staff & Sumber Daya Tidak Pernah Terlepas saat Checkbox Dikosongkan (Relationship Desync)
type: bug-fix
status: proposed
priority: P0
phase: 4
domain: staged-modules
requirements: []
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-2-OWNER-CORE-OPERATIONS.md at a68ffe8
---

# BQ-045: [BUG-T2-03] Relasi Staff & Sumber Daya Tidak Pernah Terlepas saat Checkbox Dikosongkan (Relationship Desync)

Migrated verbatim from the 2026-10 audit (TAHAP-2-OWNER-CORE-OPERATIONS.md, stage 2: Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/components/owner/modal-edit-program.blade.php:180-210`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-edit-program.blade.php#L180-L210)
  - [`app/Actions/Program/UpdateService.php:85`](file:///c:/laragon/www/bookqu/app/Actions/Program/UpdateService.php#L85)
- **Deskripsi & Dampak**:
  Ketika owner mengedit layanan dan menghapus centang pada seluruh staf atau sumber daya, peramban tidak mengirimkan field checkbox yang kosong dalam payload HTTP POST. Di backend, `UpdateService` memeriksa `if (array_key_exists('staff_ids', $data))`. Karena key tidak ada, sinkronisasi `$service->staff()->sync([])` dilewati. **Owner tidak bisa melepaskan staf atau resource dari layanan**.
- **Akar Masalah**:
  ```php
  // UpdateService.php:
  if (array_key_exists('staff_ids', $data)) {
      $service->staff()->sync($data['staff_ids'] ?? []);
  }
  ```
- **Rekomendasi Perbaikan**:
  Tambahkan penggabungan nilai default array kosong di `UpdateServiceRequest`:
  ```php
  protected function prepareForValidation(): void
  {
      $this->merge([
          'staff_ids' => $this->input('staff_ids', []),
          'resource_ids' => $this->input('resource_ids', []),
      ]);
  }
  ```

## Evidence

_Fill in when done._
