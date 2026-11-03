---
id: BQ-121
title: Keep customer invoices tied to the recorded charge after catalog price changes
type: bug-fix
status: in-progress
priority: P1
phase: 4
domain: booking
requirements: [FR-BOOKING-008, FR-MANAGE-004, FR-PAYMENT-001, NFR-UX-005]
invariants: [INV-PAY-01, INV-BOOK-06, INV-TOK-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

## Problem

Audit 2026-11-02 pada `f361cc2` memperluas temuan harga BQ-095 ke bukti transaksi pelanggan: invoice yang sudah dibayar dapat berubah nominalnya hanya karena owner mengedit harga layanan.

- `app/Models/Booking.php:171-176`: `priceLabel` membaca `schedule.harga_override`, lalu harga katalog terbaru; tidak membaca jumlah yang ditagihkan pada transaksi.
- `resources/views/customer/manage/invoice.blade.php:429,650,662,674` memakai label itu untuk rincian, subtotal/total, dan teks berbagi.
- `resources/views/customer/manage/payment-invoice.blade.php:118-145` memakai `priceLabel` untuk rincian sesi tetapi `payment.jumlah` untuk total grup. Rincian dapat tidak cocok dengan total.

### Reproduksi dan dampak

Booking satu slot dibayar **Rp150.000**, tanpa override. Ubah harga layanan menjadi Rp250.000, buka invoice dengan grant sah: HTML invoice memuat **Rp250.000**, sementara `payments.jumlah` tetap **150000.00**. Tidak terjadi reschedule atau pembayaran tambahan.

Pelanggan memperoleh bukti transaksi yang salah dan owner dapat menerima komplain/perbedaan pembukuan. BQ-095 tetap menangani presentasi owner yang telah disetujui; BQ-109 menangani kebijakan selisih harga saat reschedule. Item ini khusus **harga historis setelah perubahan katalog**, tanpa menganggap kalkulasi harga terbaru sebagai pemulihan fakta historis.

## Plan

1. Tech Lead menyetujui proyeksi nominal historis. Untuk transaksi satu slot, gunakan jumlah transaksi yang tersimpan sebagai sumber total; bedakan harga katalog sekarang dari jumlah dibayar bila keduanya perlu ditampilkan.
2. Untuk grup, total harus tetap berasal dari payment yang sama. Tentukan apakah rincian per slot historis tersedia; jangan menghitung ulang dari katalog/override yang dapat berubah, atau membagi rata bila harga tiap slot berbeda.
3. Jika diperlukan snapshot per slot, ajukan skema/backfill terpisah sebelum coding. Data legacy yang tidak menyimpan rincian harus disajikan sebagai total transaksi dengan keterbatasan yang jelas, bukan angka historis rekaan.
4. Gunakan proyeksi yang sama pada invoice individual/grup dan teks berbagi; koordinasikan sumber nominal dengan BQ-095 tanpa memperluas persetujuan light lane-nya secara diam-diam.
5. Tidak mengubah payment sukses, saldo, refund, harga katalog, atau kebijakan selisih saat reschedule. D-006/BQ-109 tetap terpisah.

## Invariants at risk

Perbaikan tampilan tidak boleh merekonsiliasi pembayaran dengan menulis ulang transaksi. Multi-slot tetap satu payment; grant, invoice authorization, dan larangan ekspor credential tetap utuh.

## Tests

- Bayar Rp150.000, ubah katalog menjadi Rp250.000: invoice dan teks berbagi tetap menunjukkan nominal transaksi yang benar.
- Grup dengan harga slot berbeda: total dan rincian tidak saling bertentangan setelah edit katalog/override; legacy tanpa rincian mengikuti fallback yang disepakati.
- Pemeriksaan nominal tidak mengubah payment, saldo, booking, atau refund. Grant tenant/payment lain tetap ditolak.
- Pakai `travelTo`, fakes provider/mail, pengujian HTTP rendering, dan verifikasi heavy lane. Migrasi hanya dites jika skema memang disetujui.

## Docs to update

`docs/domains/booking.md`, serta domain payment bila sumber nominal historis dijelaskan di sana. Tautkan BQ-095/BQ-109 agar tidak dikerjakan dua kali; requirement/ADR hanya jika keputusan baru mengubah aturannya.

## Evidence (fill when done)

**Bukti audit:** invoice pelanggan merender Rp250.000 setelah edit katalog, dengan payment tetap Rp150.000, melalui GET terotorisasi di SQLite terisolasi. Termasuk 5 skenario / 29 asersi audit BQ-113. Tidak ada perubahan transaksi lokal/produksi. Belum ada implementasi/PR; keputusan proyeksi atau skema menunggu Tech Lead.

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): shared `GetInvoiceAmounts` reads the stored payment total for invoice HTML and WhatsApp text. Legacy groups show an explicit whole-transaction total and unavailable per-session detail; no equal allocation, catalog recalculation, schema, balance or refund writes. Two HTTP regressions added to `BookingManageTest` (catalog/override mutation and unequal legacy group); existing grant isolation tests retained.
Verification: full SQLite suite 675 passed / 3556 assertions; related suites 25 passed / 99 assertions; Pint fixed changed PHP; PHPStan no errors, two obsolete baseline entries removed. MySQL not rerun: this item changes read-only projections, no locks or booking/payment/tenant transactions. Status stays in-progress until merge.
