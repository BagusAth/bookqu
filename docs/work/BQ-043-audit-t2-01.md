---
id: BQ-043
title: [BUG-T2-01] Crash Global Alpine.js Akibat Karakter Kutip pada Flash Message Toast
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

# BQ-043: [BUG-T2-01] Crash Global Alpine.js Akibat Karakter Kutip pada Flash Message Toast

Migrated verbatim from the 2026-10 audit (TAHAP-2-OWNER-CORE-OPERATIONS.md, stage 2: Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif). Status in the current code is unverified — check it first (BQ-039).

## Audit finding

- **Lokasi Berkas**: [`resources/views/components/owner/toast.blade.php:14-19`](file:///c:/laragon/www/bookqu/resources/views/components/owner/toast.blade.php#L14-L19)
- **Deskripsi & Dampak**:
  Pemanggilan `add('success', '{{ addslashes(session('sukses')) }}')` di dalam atribut HTML `x-init="..."` menghasilkan `\"`. Tanda kutip ganda ini memecah pembungkus atribut HTML `x-init="add('success', \"...\")"`. Peramban melempar `Uncaught SyntaxError` saat parsing DOM, yang menyebabkan **Alpine.js gagal terinisialisasi secara global di seluruh halaman dashboard owner**.
- **Akar Masalah**:
  ```blade
  x-init="
      @if(session('sukses'))
          add('success', '{{ addslashes(session('sukses')) }}');
      @endif
  "
  ```
- **Rekomendasi Perbaikan**:
  Ganti dengan directive bawaan Laravel Blade `@js(...)`:
  ```blade
  x-init="
      @if(session('sukses'))
          add('success', @js(session('sukses')));
      @endif
  "
  ```

## Evidence

_Fill in when done._
