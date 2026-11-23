---
id: BQ-005
title: Never show internal exception messages to customers
type: bug-fix
status: done
priority: P1
phase: 4
domain: booking
requirements: [NFR-SEC-004]
invariants: []
launch-blocker: true
needs-lead-approval: false
depends-on: []
---

# BQ-005: Never show internal exception messages to customers

## Problem

`CreateBooking` returns `'Gagal memproses pembayaran. Error: ' . $e->getMessage()` to the customer (`app/Actions/Booking/CreateBooking.php:313`).

## Plan

Return a generic Indonesian message and keep the technical detail in the log with booking/payment context. Search for other `getMessage()` values that reach views or JSON responses.

Scope verified 2026-11-01: customer booking checkout, owner subscription checkout and owner reschedule expose raw exceptions. Return generic Indonesian failures for unexpected errors, including Throwable, while retaining explicit slot-refusal messages. Log exception type/location and internal tenant/payment/booking identifiers instead of raw messages, exception objects or provider response bodies that can contain credentials/tokens. Cover actual HTTP/session/JSON boundaries, failure-state cleanup and safe diagnostics with fake gateways/slot claims. Related booking/payment notification and cancellation catches follow the same log redaction rule; business rules and authorization remain unchanged. This is the existing approved bug-fix work item, not a new product/schema decision.

## Evidence

Selesai 2026-11-01 dalam run BQ-002 → BQ-004 → BQ-005 yang diminta lead.

- CreateBooking dan checkout langganan owner memakai pesan pembayaran generik; RescheduleBooking memakai pesan generik untuk unexpected failure customer maupun owner. Tidak ada lagi getMessage() mentah yang mengalir ke view/JSON dari controller/Action terkait. SlotClaimRefused dan kode SLOT_TAKEN/SLOT_NOT_FOUND tetap menghasilkan pesan bisnis yang eksplisit.
- Payment initialization dan reschedule menangani Throwable, termasuk Error. Payment tetap gagal dan pending bookings cancelled melalui PaymentLifecycle pada initialization failure; reschedule gagal mempertahankan slot/status semula. Authorization dan penolakan HttpException tidak disamarkan menjadi error operasional.
- Log menyimpan exception type, lokasi sumber pada boundary utama dan ID operasi, bukan pesan mentah, exception object/trace berargumen, atau body respons provider. Midtrans initiation/status, SingaPay OAuth/account/payment-link, serta notification/cancel/refund catches terkait ikut diredaksi agar lapisan bawah tidak membocorkan rahasia yang sudah disembunyikan lapisan HTTP.
- SafeOperationErrorsTest: 13 kasus / 197 assertions. HTTP checkout customer, flash checkout owner, JSON reschedule owner, session errors reschedule customer, Exception/Error, slot refusal, gateway Midtrans dan empat jalur kegagalan SingaPay. Menggunakan fixture pesan berisi credential, SQL, path dan script; rekaman log aktual diperiksa. Semua HTTP provider/mail/notification difake; waktu dibekukan travelTo.
- Sebelum perbaikan, tes mereproduksi pesan internal pada flash checkout dan JSON owner serta Error yang lolos sebagai 500. Pesan bisnis yang valid tetap lulus setelah perbaikan.
- Verifikasi akhir: php artisan test --compact: 555 tes SQLite / 2.919 assertions. vendor/bin/phpunit -c phpunit.mysql.xml: 566 tes / 2.962 assertions. Pint dirty lulus (max-processes=1 di Windows); PHPStan tanpa error, baseline tidak bertambah; knowledge graph diperbarui. Main/produksi dan API provider live tidak disentuh.

Temuan di luar batas HTTP/payment ini dicatat sebagai BQ-077 (raw exception diagnostics pada command operasional dan CreateService). Tidak ada klaim audit menyeluruh terhadap seluruh log atau konfigurasi APP_DEBUG produksi.
