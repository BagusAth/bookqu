---
id: BQ-052
title: [BUG-T5-01] Kebocoran Token Rahasia pada Google Calendar & Teks Berbagi WhatsApp
type: bug-fix
status: approved
priority: P0
phase: 4
domain: customer-access
requirements: [FR-MANAGE-011, NFR-SEC-010]
invariants: [INV-TOK-01]
launch-blocker: true
needs-lead-approval: false
depends-on: [BQ-001]
source: docs/audit-bug/TAHAP-5-CUSTOMER-SELF-SERVICE-OWNER-OPS.md at a68ffe8
---

# BQ-052: [BUG-T5-01] Kebocoran Token Rahasia pada Google Calendar & Teks Berbagi WhatsApp

Audit source retained below. The remaining individual-page leak is confirmed by source inspection; [ADR-0006](../adr/ADR-0006-management-link-module.md) defines the accepted projection/link policy. This item remains unimplemented and records the public-share regression following BQ-001.

> Confirmed 2026-10-30: group `payment-show.blade.php:66-87` omits credentials, but individual `show.blade.php:25-42` still embeds the presented management URL/token in Calendar and WhatsApp text. The earlier group-only spot check did not establish a complete fix.

## Audit finding

- **Lokasi Berkas**: 
  - [`resources/views/customer/manage/show.blade.php:25-42`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/show.blade.php#L25-L42)
  - [`resources/views/customer/manage/invoice.blade.php:428-433`](file:///c:/laragon/www/bookqu/resources/views/customer/manage/invoice.blade.php#L428-L433)
- **Deskripsi & Dampak (Kerentanan Keamanan Serius)**:
  URL manajemen lengkap beserta query string token otentikasi rahasia `?token=...` dimasukkan ke dalam teks berbagi WhatsApp dan deskripsi Google Calendar publik. Siapapun yang menerima forward pesan WhatsApp atau melihat kalender bersama dapat membajak hak akses untuk membatalkan atau mengubah jadwal pelanggan tanpa izin.
- **Akar Masalah**:
  ```php
  $manageUrl = route('booking.manage', ['booking_code' => $booking->booking_code]) . ($token ? '?token=' . $token : '');
  $eventDetails = "...\nKelola Booking: " . $manageUrl;
  $waText = "...\nKelola Reservasi: " . $manageUrl;
  ```
- **Rekomendasi Perbaikan**:
  Hapus query string token dari tautan eksternal yang dibagikan secara publik.

## Evidence

_Fill in when done._

## Accepted plan

1. Use ADR-0006's audience projection for every individual/group calendar, WhatsApp, invoice and print path. Share reservation facts/public references only; remove all private management URLs and credential material, not just a query parameter.
2. Ensure invoice access never reveals a Management link and bootstrap/private pages follow the canonical origin, clean URL, no-store and no-referrer rules from BQ-001.
3. Decode and inspect generated external URLs/text in regression tests for both individual and group flows, including narrow/Management access. Verify credentials are absent from HTML/JSON/QR/print/share projections and provider return URLs while useful reservation facts remain.
4. Run affected tests and the full suite during implementation; record evidence before marking the leak fixed. Source inspection alone is not runtime verification.
