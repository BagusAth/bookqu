---
id: BQ-113
title: Prevent stale reschedule responses and reject a slot from another selected date
type: bug-fix
status: in-progress
priority: P2
phase: 4
domain: booking
requirements: [FR-BOOKING-011, FR-BOOKING-012, FR-SCHEDULE-009, NFR-UX-005]
invariants: [INV-SCH-01, INV-BOOK-04, INV-BOOK-06, INV-TEN-01, INV-TOK-01]
lane: heavy
approval: tech-lead
decision:
launch-blocker: true
depends-on: []
---

## Problem

Dikonfirmasi pada audit UI/UX 2026-11-02, snapshot `f361cc2`. Owner maupun pelanggan dapat melihat slot dari tanggal sebelumnya setelah cepat mengganti tanggal pada form reschedule. Respons AJAX diterapkan tanpa memeriksa apakah tanggal atau booking aktif masih sama dengan saat request dimulai.

- Owner: `resources/views/components/owner/modal-reschedule-booking.blade.php:34-65` langsung mengisi `slots` dari respons. Tidak ada pembatalan request atau nomor urut; flag loading juga dapat diakhiri oleh request lama.
- Pelanggan: `resources/views/customer/manage/reschedule.blade.php:467-497` memiliki masalah serupa pada `fetchTimeSlots`.
- Owner mengirim `tanggal`, tetapi `OwnerBookingController.php:252-256` hanya meneruskan ID slot ke Action. `RescheduleBooking.php` menerapkan target date pada claim hanya untuk actor customer. Karena itu slot dari tanggal lain dapat **berhasil disimpan**, bukan sekadar ditampilkan keliru.

### Reproduksi dan dampak

1. Buka reschedule, pilih 5 November lalu 6 November sebelum request pertama selesai.
2. Selesaikan respons 6 November lebih dahulu, kemudian respons 5 November.
3. Form masih menunjukkan 6 November, tetapi daftar slot berasal dari 5 November. Pilih slot tersebut.
4. Browser menghasilkan payload `tanggal=2026-11-06` dengan ID slot 5 November. Uji HTTP owner mengembalikan 200 / `success=true` dan booking berpindah ke **2026-11-05**.

Pesan sukses owner menyebut tanggal yang benar-benar tersimpan (5 November), dan kasus ini butuh dua request yang selesai terbalik; karena itu prioritasnya P2 (triase Tech Lead 2026-11-02), bukan launch blocker.

Pada pelanggan, target date sudah diperiksa oleh claim sehingga kombinasi tersebut ditolak; UI tetap menghabiskan waktu pengguna dengan pilihan yang pasti gagal. Ini berbeda dari tabrakan slot, perubahan harga BQ-109, dan hilangnya input setelah validasi dalam BQ-082.

## Plan

1. Ikat setiap pemuatan slot pada booking ID, tanggal, dan nomor request. Batalkan request sebelumnya bila memungkinkan dan abaikan respons, error, serta `finally` milik request yang sudah usang.
2. Kosongkan pilihan slot saat tanggal/booking berubah; tombol simpan tidak aktif sampai respons terbaru berhasil dimuat dan pilihan berasal dari respons itu. Menutup lalu membuka modal untuk booking lain juga menginvalidasi request lama.
3. Teruskan expected date dari controller owner ke operasi reschedule dan validasi kesesuaian tanggal terhadap slot di bawah kunci tenant. Jangan hanya menambahkan pengecekan di JavaScript. Tech Lead menyetujui kontrak parameter dan kompatibilitas caller lama sebelum coding.
4. Pertahankan otoritas `ClaimSlots`, validasi tenant/service, batas reschedule pelanggan, larangan reschedule parsial multi-slot, dan grant. Gunakan pesan Indonesia yang meminta pengguna memilih ulang bila pilihan usang.
5. Tidak mengubah harga/refund atau menyelesaikan BQ-087; perubahan itu tetap di item masing-masing.

## Invariants at risk

Claim tetap di dalam transaksi dengan tenant lock dan pemeriksaan slot aktual; respons yang lebih baru tidak memberi hak akses tambahan. Penolakan mismatch tidak mengubah booking, pembayaran, delivery, atau log sukses. Server harus menolak payload mismatch walaupun UI dilewati.

## Tests

- Fitur owner: tanggal 6 + slot tanggal 5 ditolak, tanggal/slot booking asli tetap; kombinasi yang cocok berhasil.
- Fitur pelanggan: mismatch tetap ditolak, grant dan tenant lain tetap ditolak; multi-slot tidak dapat dipindah sebagian.
- Browser dengan respons terkontrol: A lambat/B cepat, error A setelah B sukses, tutup modal lalu buka booking lain, serta flag loading milik request terbaru. Pastikan tanggal, label, slot, dan payload selalu cocok.
- Pakai `travelTo`; fake antrean/mail/provider. Jalankan tes terkait lalu verifikasi penuh sesuai workflow; SQLite tidak membuktikan locking lintas koneksi.

## Docs to update

`docs/domains/booking.md` bila kontrak expected date berubah. Tautkan bukti implementasi di item ini; riwayat BQ-054/BQ-055 tetap utuh.

## Evidence (fill when done)

**Bukti audit, belum bukti perbaikan:** pemeriksaan HTTP terisolasi dan Edge/Playwright berhasil mereproduksi payload tanggal 6 dengan ID slot tanggal 5. Audit bersama BQ-114/BQ-117/BQ-119/BQ-120/BQ-121 menjalankan 5 skenario HTTP / 29 asersi yang mengonfirmasi perilaku lama; alat reproduksi sementara bukan tes regresi yang sudah dikomit. Seluruh request eksternal browser diblokir/diganti; tidak ada perubahan produksi.

Implementasi, tes regresi permanen, hasil verifikasi, dan PR: belum ada. Triase Tech Lead 2026-11-02: P2, bukan launch blocker.

Disetujui Tech Lead 2026-11-04 untuk sesi agen.

Implementation evidence (2026-11-04): two owner HTTP regressions prove mismatched date refused without mutation (red: 200, green: 422) and matching date succeeds. Owner forwards nullable expected date; omitted-date direct callers retain compatibility. Both loaders bind responses/errors/finally to a monotonically increasing request and selected date; owner also binds booking ID and invalidates on close. Selections clear while loading. Edge/Playwright executed actual loader functions with deferred responses: old success after new success, old error/finally during newer load, modal close/reopen for another booking, all passed. No price/refund changes.
Verification: full SQLite 677 passed / 3562 assertions; MySQL group 11 passed / 43 assertions; Pint fixed changed PHP; PHPStan no errors, one obsolete unused-closure allowance removed.
