---
id: BQ-004
title: Encrypt tenant gateway credentials and stop caching full tenant rows
type: improvement
status: done
priority: P0
phase: 4
domain: tenancy
requirements: [NFR-SEC-010]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-004: Encrypt tenant gateway credentials and stop caching full tenant rows

Plan approved by agathan, with explicit reversible-data rollback, hidden attributes and APP_KEY/APP_PREVIOUS_KEYS operational requirements (2026-10-31).

## Problem

`tenants.midtrans_*_server_key` and the client keys are stored in plain text (`app/Models/Tenant.php`, `app/Actions/Tenant/UpdatePaymentSettings.php:31`). `BookingController::resolveTenant` caches `getAttributes()` of the whole tenant row for one hour under `tenant:slug:{slug}` (database cache store), copying the secrets into the cache table.

## Plan

1. Add `encrypted` casts for the credential columns with a migration that encrypts existing values.
2. Cache only the public tenant fields the storefront needs.
3. Add hidden attributes for all four client/server key fields; prove JSON excludes plaintext and ciphertext.
4. Implement down() to restore plaintext before rolling back code. Keep TEXT expansion to avoid truncation. Update automatic deployment rollback for the first encryption release, and document the manual migration rollback order under maintenance mode (ADR-0015 exception approved by the lead).
5. Document that database restores require the matching APP_KEY and that key rotation requires APP_PREVIOUS_KEYS.
6. Tests: encryption at rest, legacy backfill (including null/empty/mixed data), down() restoring plaintext, key rotation, gateway reads, cache isolation/invalidations and preserved unrelated cache records.

## Evidence

Selesai 2026-11-01; rencana dan tambahan rollback/hidden attributes/APP_KEY disetujui lead di sesi ini.

- Empat client/server key sandbox/production memakai nullable TEXT dan encrypted casts; seluruhnya hidden dari array/JSON. Gateway tetap membaca plaintext melalui atribut model. Tidak ada logging atribut kredensial ditambahkan.
- Migrasi memakai query builder agar independen dari cast model. Backfill mempertahankan null/empty, melewati ciphertext yang sudah valid, dan dapat diulang. Transformasi data serta pembersihan cache berada dalam transaksi. down() mengembalikan plaintext, menolak key yang salah tanpa perubahan parsial, dan mempertahankan TEXT untuk menghindari truncation.
- Cache storefront berisi whitelist field publik berdasarkan TenantContext terverifikasi; semua penulis profil/appearance/landing/payment terkait menginvalidasi proyeksi. Tidak ada pemulihan konteks tenant dari cache atau hostname yang hanya menyerupai slug. Migrasi membersihkan namespace cache tenant database termasuk slug pensiun tanpa menghapus cache lain.
- docs/operations.md menjelaskan pengecualian ADR-0015 yang disetujui, urutan migrate:rollback sebelum kode lama, maintenance/worker, backup APP_KEY dan rotasi APP_PREVIOUS_KEYS. Automatic rollback rilis pertama menegakkan dekripsi sebelum copy; kegagalan dekripsi menghentikan rollback dalam maintenance. Rilis terenkripsi berikutnya tidak mendekripsi data saat code rollback biasa.
- TenantGatewayCredentialsTest: 8 kasus penyimpanan/JSON, masked updates, gateway sandbox/production, cache publik dan isolasi/invalidation, hostname tidak terdaftar, serta rotasi key. TenantCredentialMigrationTest: 3 kasus MySQL untuk backfill/up ulang/down, cache namespace dan wrong key. TenantCredentialRollbackTest: 4 skenario shell terisolasi menjalankan fungsi rollback aktual dengan command stub, tanpa akses produksi.
- Tes integrasi tambahan memakai route slug yang benar; sebelumnya slug keliru dimasukkan sebagai hostname custom domain dan diterima fallback yang kini dihapus. Assertion booking/checkout dipertahankan dan waktu dibekukan dengan travelTo.
- Verifikasi: SQLite 542 tes / 2.722 assertions; MySQL 553 tes / 2.765 assertions. Pint dirty lulus (max-processes=1 pada Windows), PHPStan tanpa error dan baseline tidak bertambah. Knowledge graph diperbarui. API provider live dan produksi tidak dipanggil.

Batas operasional: belum ada restore drill produksi (BQ-019). Jika cache selain database pernah menyimpan key slug lama yang sudah pensiun, namespace tersebut perlu dibersihkan dengan tooling backend sesuai operations §3. Audit redaksi pesan exception dilanjutkan terpisah di BQ-005.
