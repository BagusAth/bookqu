# BookQu — Production Logic Fix Specification

Dokumen ini berisi daftar masalah logic pada customer booking flow branch `agathan`, keputusan arsitektur yang harus digunakan, serta solusi implementasi yang harus diterapkan sebelum production.

Fokus dokumen ini adalah business logic, data consistency, transaction integrity, payment lifecycle, concurrency, dan security. Perbaikan UI dapat dilakukan setelah logic production-critical selesai.

---

## 1. Aturan Utama Multi-Slot Booking

### Keputusan

Fitur multi-slot tetap dipertahankan.

Customer dapat memilih lebih dari satu slot dalam satu tanggal dan satu layanan, tetapi slot yang dipilih HARUS berurutan berdasarkan interval schedule/service.

Contoh service berdurasi 60 menit:

```text
10:00 + 11:00          VALID
10:00 + 11:00 + 12:00  VALID
```

Sedangkan:

```text
10:00 + 12:00           INVALID
10:00 + 11:00 + 13:00  INVALID
10:00 + 14:00           INVALID
```

Multi-slot bukan berarti customer boleh melakukan beberapa booking terpisah dalam satu checkout.

### Solusi

Validasi contiguous slot harus dilakukan di backend.

Jangan hanya mengandalkan JavaScript.

Validasi harus dilakukan pada dua titik:

```text
selectTime()
processCheckout()
```

`processCheckout()` adalah final authority.

Algoritmanya:

1. Ambil semua schedule yang dipilih.
2. Pastikan seluruh schedule:
   - tenant sama
   - service sama
   - tanggal sama
   - status tersedia
3. Sort berdasarkan `jam_mulai`.
4. Pastikan jumlah slot sesuai.
5. Untuk setiap slot berikutnya, pastikan:

```text
next.jam_mulai == previous.jam_mulai + service.durasi
```

6. Tolak request jika tidak contiguous.
7. Pastikan `schedule_ids` tidak duplicate.

Contoh:

```text
selected:
10:00
11:00
13:00

service duration:
60 menit

result:
REJECT
"Slot harus berurutan tanpa jeda."
```

---

# 2. Payment → Booking Harus One-to-Many

## Masalah

Arsitektur baru sudah membuat:

```text
1 Payment
 ├── Booking A
 ├── Booking B
 └── Booking C
```

Tetapi beberapa bagian aplikasi masih menganggap:

```text
1 Payment
 └── 1 Booking
```

Contoh:

```php
Booking::where('idpayment', $payment->id)->first();
```

Ini menyebabkan hanya booking pertama yang diproses.

## Solusi

Relationship utama harus:

```text
Payment
  hasMany Bookings

Booking
  belongsTo Payment
```

Pada `Payment`:

```php
public function bookings(): HasMany
{
    return $this->hasMany(Booking::class, 'idpayment');
}
```

Pada `Booking` tetap:

```php
public function payment(): BelongsTo
{
    return $this->belongsTo(Payment::class, 'idpayment');
}
```

`Payment::booking()` yang lama jangan digunakan sebagai source of truth lagi.

Kolom `idbooking` pada payment dapat dipertahankan sementara untuk compatibility dengan data lama, tetapi customer booking flow baru harus menggunakan:

```text
booking.idpayment
```

sebagai relasi utama.

---

# 3. Midtrans Success Hanya Mengubah Booking Pertama

## Masalah

`MidtransPaymentService::processSuccess()` menggunakan:

```php
->where('idpayment', $payment->id)->first();
```

Akibatnya:

```text
Payment = sukses

Booking A = paid
Booking B = pending
Booking C = pending
```

## Solusi

Ambil seluruh booking:

```php
$bookings = Booking::where('idpayment', $payment->id)
    ->lockForUpdate()
    ->get();
```

Kemudian seluruh booking pending diubah:

```text
pending → paid
```

Token management dibuat untuk setiap booking jika belum ada.

Usage log dan cache invalidation dilakukan berdasarkan seluruh booking tersebut.

Notifikasi dan invoice harus mengikuti group payment, bukan hanya booking pertama.

---

# 4. Midtrans Failed / Expired / Cancel Hanya Mengubah Booking Pertama

## Masalah

`processFailed()` juga hanya mengambil satu booking.

Ini dapat menghasilkan:

```text
Payment = gagal

Booking A = cancelled
Booking B = pending
Booking C = pending
```

## Solusi

Ketika payment menjadi gagal:

```text
Payment → gagal

SEMUA Booking yang memiliki idpayment tersebut:
pending → cancelled
```

Gunakan transaction + `lockForUpdate()`.

Hal ini juga harus berlaku untuk:

```text
deny
expire
cancel
payment timeout
customer cancel payment
```

---

# 5. Payment Expiry Harus Atomic

## Masalah

`showPayment()` saat payment expired saat ini melakukan update payment dan booking secara terpisah.

Selain itu implementasinya sebelumnya masih mengambil booking pertama untuk cache handling.

## Solusi

Buat satu transaction:

```text
BEGIN

lock Payment

cek status masih pending

lock seluruh Booking payment tersebut

Payment → gagal

seluruh Booking → cancelled

clear availability cache

COMMIT
```

Jangan sampai state akhirnya:

```text
payment = gagal
booking = pending
```

atau:

```text
payment = pending
booking = cancelled
```

---

# 6. Cancel Payment Harus Memproses Seluruh Booking

## Masalah

`cancelPayment()` sekarang mencari:

```php
Booking::where('idpayment', $payment->id)->first();
```

Ini tidak sesuai dengan multi-slot.

## Solusi

Ubah menjadi:

```text
Payment pending
        ↓
transaction
        ↓
Payment → gagal
        ↓
semua booking → cancelled
        ↓
clear availability cache
```

Semua operasi dilakukan dalam transaction.

---

# 7. Security Problem: Jangan Percaya Payload Pembayaran dari Browser

## Masalah Paling Krusial

`handleCallback()` memiliki fallback yang menggunakan:

```php
$request->input('result')
```

ketika verification ke Midtrans gagal.

Artinya browser dapat mengirim payload yang terlihat seperti:

```json
{
    "transaction_status": "settlement"
}
```

dan aplikasi berpotensi memprosesnya sebagai payment sukses.

Payload browser bukan authoritative source.

## Solusi

Hapus fallback tersebut.

Flow harus menjadi:

```text
Browser callback
      ↓
Server verify ke Midtrans
      ↓
Jika Midtrans menyatakan sukses
      ↓
proses payment
```

Jika server tidak dapat melakukan verification:

```text
Payment tetap pending
```

Jangan mengubah payment menjadi sukses hanya karena payload dari browser.

Sumber kebenaran payment:

```text
Midtrans server response
atau
Midtrans webhook yang signature-nya valid
```

Client-side callback hanya berfungsi sebagai trigger untuk melakukan verification.

---

# 8. Payment State Machine Harus Ketat

## Masalah

Saat ini payment dapat berada dalam kondisi yang tidak konsisten, misalnya:

```text
Payment = sukses
Booking = cancelled
```

atau payment yang sudah dianggap gagal berpotensi diproses lagi menjadi sukses dari late webhook.

## Solusi

Gunakan state transition yang jelas:

```text
pending → sukses
pending → gagal

sukses → sukses      allowed/idempotent
gagal  → gagal       allowed/idempotent

sukses → gagal       forbidden
gagal  → sukses      forbidden secara normal
```

Late settlement setelah transaksi lokal sudah terminal tidak boleh otomatis menghidupkan booking yang sudah cancelled.

Kasus seperti ini harus:

```text
log + reconciliation/manual handling
```

bukan automatic state reversal.

---

# 9. Invoice Harus Payment-Level

## Masalah

`showInvoice()` masih:

```php
Booking::where('idpayment', $payment->id)->first();
```

Ini hanya cocok untuk single-slot.

## Solusi

Gunakan:

```php
$bookings = Booking::where('idpayment', $payment->id)
    ->orderBy('tanggalbooking')
    ->orderBy('jam')
    ->get();
```

Invoice harus menampilkan seluruh slot.

Contoh:

```text
Layanan:
Konsultasi

Tanggal:
21 September 2026

Sesi:
10:00 - 11:00
11:00 - 12:00
12:00 - 13:00

Total:
Rp 600.000
```

Invoice tidak boleh hanya menampilkan slot pertama.

---

# 10. Invoice Hanya Boleh Diakses Jika Payment Sukses

## Masalah

Invoice sekarang cukup mencari booking berdasarkan payment.

Secara business logic, itu belum cukup.

## Solusi

Sebelum menampilkan invoice:

```text
payment harus sukses
```

dan:

```text
payment memiliki minimal satu booking
```

Untuk multi-slot:

```text
seluruh booking associated payment
harus berada pada state yang sesuai
```

Minimal customer tidak boleh mendapatkan halaman:

```text
"Booking Berhasil Dikonfirmasi"
```

ketika payment masih pending atau gagal.

---

# 11. Payment Page Harus Menggunakan Semua Booking

## Masalah

Payment page menggunakan:

```php
$payment->booking
```

yang masih single-booking oriented.

Akibatnya preview pembayaran pada multi-slot hanya memperlihatkan satu jadwal.

## Solusi

Payment page gunakan:

```php
$payment->bookings
```

dan tampilkan seluruh slot yang masuk dalam transaksi.

Contoh:

```text
Layanan: Konsultasi

Jadwal:
10:00
11:00
12:00

Total:
Rp 600.000
```

---

# 12. Cancel / Reschedule Individual Booking pada Multi-Slot

## Masalah

Misalnya:

```text
Payment
 ├── Booking A = 10:00
 └── Booking B = 11:00
```

Jika customer membatalkan Booking A saja, logic refund saat ini menggunakan:

```php
$booking->payment->jumlah
```

yang dapat merupakan total seluruh payment.

Hasilnya berpotensi:

```text
Refund slot 10:00
→ refund Rp 400.000

Padahal:
10:00 = Rp 200.000
11:00 = Rp 200.000
```

Masalah yang sama muncul pada reschedule.

Customer dapat mengubah hanya satu slot sehingga group awal tidak lagi contiguous.

## Keputusan Production

Untuk release pertama:

```text
Multi-slot booking:
individual cancel = disabled
individual reschedule = disabled
```

Single-slot:

```text
cancel/reschedule = tetap berjalan
```

Identifikasi multi-slot:

```php
$bookingCount = Booking::where('idpayment', $booking->idpayment)->count();
```

Jika:

```text
bookingCount > 1
```

maka customer tidak diperbolehkan melakukan individual cancel/reschedule.

Tampilkan pesan bahwa multi-slot booking harus dikelola sebagai satu transaksi/group.

Implementasi full group cancellation/reschedule dapat dibuat pada phase berikutnya.

---

# 13. Owner Juga Harus Menghormati Group Multi-Slot

## Masalah

Owner saat ini dapat memanipulasi booking individual.

Untuk multi-slot, ini bisa menghasilkan group yang tidak konsisten.

## Solusi Production

Sementara multi-slot aktif:

```text
Owner boleh melihat masing-masing booking
tetapi perubahan destructive harus memahami payment group.
```

Untuk cancellation/reschedule individual, idealnya:

```text
jika payment memiliki >1 booking
→ gunakan group operation
```

Jika belum ada group operation:

```text
disable individual mutation
```

Jangan membiarkan owner secara tidak sengaja membuat:

```text
Payment:
Rp 600.000

Booking A = cancelled
Booking B = paid
Booking C = paid
```

tanpa aturan business logic yang jelas.

---

# 14. Multi-Slot Final Validation Harus Terjadi Dalam Transaction

## Masalah

Availability di UI tidak dapat dijadikan jaminan karena slot dapat diambil customer lain setelah halaman dibuka.

## Solusi

`processCheckout()` harus menjadi final authority.

Dalam satu transaction:

```text
BEGIN

lock seluruh schedule yang dipilih

validasi:
- tenant
- service
- tanggal
- jam
- status
- past time
- duplicate schedule
- active booking
- contiguous sequence

buat Payment

buat seluruh Booking

COMMIT
```

Tidak boleh membuat booking sebelum seluruh slot lolos validation.

---

# 15. Schedule ID Harus Unique

## Masalah

Request manual berpotensi mengirim:

```text
schedule_ids:
[10, 10]
```

dan:

```text
jam:
[10:00, 10:00]
```

## Solusi

Validasi:

```php
'schedule_ids.*' => ['required', 'integer', 'distinct']
```

dan tetap lakukan validation duplicate di backend sebelum transaction.

Jangan hanya percaya data session atau JavaScript.

---

# 16. Semua Schedule Harus Memiliki Tanggal yang Sama

## Masalah

Customer flow memilih satu tanggal.

Tetapi final backend tetap harus memastikan setiap schedule yang diterima benar-benar berada pada tanggal session tersebut.

## Solusi

Untuk setiap schedule:

```text
schedule.idtenant == session tenant
schedule.idlayanan == session service
schedule.tanggal == selectedDate
```

Jika salah satu tidak cocok:

```text
reject checkout
```

---

# 17. Staff Preference Customer Harus Dihapus Total

## Keputusan

Customer TIDAK memilih staff.

Staff adalah resource yang dikelola oleh owner.

Arsitektur yang benar:

```text
Owner
 └── manage staff/resource

Customer
 └── choose service
 └── choose date
 └── choose time
```

Bukan:

```text
Customer
 └── choose staff
```

## Solusi

Hapus dari customer booking flow:

```text
availableStaff
staff_id
staffPref
service->staff loading
staff validation dari checkout
staff-related checkout JS
staff-related hidden input
```

Jangan hanya hide dari UI.

Feature tersebut harus benar-benar dihapus dari customer backend flow.

Model dan owner staff management tetap dipertahankan karena masih diperlukan untuk fulfillment/business management.

---

# 18. Add-on Saat Ini Belum Aktif

## Keputusan

Add-on boleh tetap dikembangkan kemudian.

Untuk production sekarang:

```text
Add-on = OFF
```

UI boleh disembunyikan, tetapi backend juga harus memblokir penggunaan.

## Solusi

`processCheckout()` jangan menerima add-on selama feature belum aktif.

Contoh:

```text
selected_addons dikirim manual
        ↓
REJECT
```

atau abaikan secara eksplisit dengan feature flag.

Jangan mengandalkan:

```text
"element UI sudah di-hide"
```

karena user dapat membuat POST request manual.

---

# 19. Add-on Stock Logic Belum Aman

Ketika add-on nanti diaktifkan, logic berikut masih harus diperbaiki:

```php
if ($addon->stock !== null && $addon->stock > 0) {
    $addon->decrement('stock');
}
```

Masalah:

```text
stock = 0
```

masih dapat diproses.

## Solusi future

Aturan:

```text
stock = null → unlimited
stock > 0    → allowed
stock = 0    → reject
```

Stock reservation harus diperlakukan sebagai transaction resource.

Jika payment gagal/expired/cancelled:

```text
reserved stock → restored
```

Tetapi ini tidak menjadi blocker release selama add-on benar-benar disabled.

---

# 20. Voucher Saat Ini Belum Aktif

Jika voucher belum menjadi bagian dari production release:

```text
Voucher = OFF
```

Backend checkout juga harus menonaktifkannya, bukan hanya UI.

Jika voucher ingin diaktifkan, maka lifecycle usage harus diubah.

## Masalah

Saat ini:

```text
checkout dibuat
↓
voucher used_count++
↓
payment gagal
```

Voucher tetap terhitung terpakai.

## Solusi future

Usage voucher harus dianggap finalized ketika payment berhasil.

Contoh:

```text
pending payment
→ voucher reservation

payment sukses
→ used_count finalized

payment gagal
→ reservation released
```

Untuk release pertama, lebih aman menonaktifkan voucher sampai lifecycle ini selesai.

---

# 21. Harga Multi-Slot Harus Mengikuti Schedule Price Override

## Masalah

Backend menghitung:

```text
schedule.harga_override
atau
service.harga
```

Sedangkan JS time selection menggunakan:

```text
service.price × jumlah slot
```

Akibatnya harga UI dapat berbeda dari harga backend.

## Solusi

Backend adalah source of truth.

Frontend harus menerima:

```text
harga setiap selected schedule
```

dari server.

Jangan hitung harga multi-slot berdasarkan:

```text
service.price × count
```

jika setiap schedule dapat memiliki `harga_override`.

Final amount selalu dihitung server-side.

---

# 22. Monthly Booking Quota Harus Dicek Secara Atomic

## Masalah

Quota sekarang diperiksa sebelum final transaction.

Race condition masih mungkin terjadi:

```text
Customer A cek quota → masih tersedia
Customer B cek quota → masih tersedia

A membuat booking
B membuat booking

quota terlewati
```

## Solusi

Quota final harus diverifikasi di transaction atau menggunakan mekanisme locking/counter yang menjamin atomicity.

Untuk multi-slot:

```text
quota consumption = jumlah booking slot
```

bukan jumlah payment.

Contoh:

```text
1 payment
3 slots
→ quota usage = 3 booking
```

---

# 23. Free Booking Harus Konsisten Dengan Paid Booking

## Masalah

Free booking saat ini:

```text
booking dibuat
↓
redirect kembali ke program
```

sedangkan paid booking:

```text
payment
↓
invoice
```

Perbedaan flow ini tidak ideal.

## Solusi

Free booking tetap boleh bypass Midtrans, tetapi hasil akhirnya harus memiliki:

```text
booking confirmed
management token
invoice/confirmation
```

dan jika multi-slot:

```text
semua slot tetap dianggap satu booking group
```

Untuk production minimum, free booking harus setidaknya menghasilkan confirmation yang jelas, bukan sekadar redirect ke katalog.

---

# 24. Cache Tidak Boleh Menjadi Source of Truth

Cache availability sudah digunakan dengan benar sebagai optimization, tetapi backend tidak boleh mempercayainya untuk final booking.

Flow yang benar:

```text
Cache
 ↓
UI availability
 ↓
customer memilih
 ↓
Final checkout
 ↓
DB lock + validation
 ↓
Booking creation
```

Jangan menganggap:

```text
"slot terlihat tersedia di cache"
```

berarti slot pasti tersedia.

Transaction + database lock adalah final authority.

---

# 25. Timezone Booking Harus Konsisten

Customer flow menggunakan:

```text
Asia/Jakarta
```

Untuk production, semua validasi:

```text
today
slot time
past time
expiry
booking datetime
```

harus konsisten timezone.

Jangan mencampur:

```text
Carbon::now()
```

dengan timezone default server jika server timezone berbeda.

Gunakan timezone aplikasi secara eksplisit untuk business logic booking.

---

# 26. Payment Order ID Harus Unique di Database

Current format:

```text
BKG-{tenant}-{time}-{random}
```

cukup kecil kemungkinan collision, tetapi production tetap membutuhkan database-level protection.

## Solusi

Tambahkan unique constraint:

```text
payments.order_id UNIQUE
```

Application-generated uniqueness saja tidak cukup sebagai guarantee.

---

# 27. Booking Code Juga Harus Unique di Database

`generateBookingCode()` sudah melakukan retry jika collision.

Tetapi production sebaiknya tetap memiliki:

```text
UNIQUE booking_code
```

di database.

Application check + DB constraint adalah kombinasi yang benar.

---

# 28. Test Suite Harus Mengikuti Business Rule Baru

Test saat ini belum cukup untuk memastikan multi-slot + payment lifecycle.

Tambahkan minimal test berikut:

```text
1. single slot checkout succeeds

2. two contiguous slots succeed

3. three contiguous slots succeed

4. non-contiguous slots rejected

5. duplicate schedule rejected

6. schedule from another tenant rejected

7. schedule from another service rejected

8. schedule from another date rejected

9. concurrent booking cannot double-book slot

10. one payment creates multiple bookings

11. successful payment marks ALL bookings paid

12. failed payment cancels ALL bookings

13. expired payment cancels ALL bookings

14. cancel payment cancels ALL bookings

15. invoice displays ALL booking slots

16. invoice unavailable for pending payment

17. invoice unavailable for failed payment

18. customer cannot select staff

19. selected_addons rejected while feature disabled

20. voucher rejected while feature disabled

21. late payment webhook cannot revive cancelled booking

22. malicious client callback cannot mark payment successful

23. multi-slot customer cancel is rejected

24. multi-slot customer reschedule is rejected
```

Test nomor 22 sangat penting karena menyangkut payment security.

---

# 29. Prioritas Implementasi Production

## P0 — wajib selesai sebelum production

```text
1. Hapus trust terhadap client payment callback.
2. Ubah Payment → Booking menjadi one-to-many.
3. Midtrans success → process ALL bookings.
4. Midtrans failure → process ALL bookings.
5. Payment expiry → process ALL bookings.
6. Cancel payment → process ALL bookings.
7. Implement contiguous multi-slot validation.
8. Reject duplicate schedule IDs.
9. Final schedule validation + lock di transaction.
10. Invoice menjadi payment-level.
11. Invoice hanya untuk payment sukses.
12. Disable individual cancel/reschedule untuk multi-slot.
13. Hapus staff preference customer dari backend.
14. Disable add-on backend.
15. Disable voucher backend jika belum siap.
16. Perketat payment state transition.
17. Tambahkan DB unique constraint untuk order_id dan booking_code.
```

## P1 — segera setelah P0

```text
1. Atomic monthly quota.
2. Free booking flow diseragamkan.
3. Payment page menjadi multi-booking aware.
4. Owner booking mutation dibuat group-aware.
5. Perbaikan cache invalidation untuk semua slot.
```

## P2 — polish / feature completion

```text
1. UI multi-slot.
2. UI add-on.
3. UI voucher.
4. Price display berdasarkan schedule override.
5. Customer group cancellation.
6. Customer group reschedule.
7. Voucher lifecycle.
8. Add-on stock reservation.
```

---

# 30. Target Arsitektur Final

Customer booking flow yang diinginkan:

```text
Select Service
      ↓
Select Date
      ↓
Select Consecutive Time Slots
      ↓
Customer Data
      ↓
Create Payment Group
      ↓
Create N Booking Records
      ↓
Midtrans
      ↓
Webhook / Server Verification
      ↓
Payment State Update
      ↓
Update ALL Booking Records
      ↓
Confirmation / Invoice
```

Database relationship:

```text
Payment
  │
  ├── Booking 10:00
  ├── Booking 11:00
  └── Booking 12:00
```

Business rule:

```text
1 Payment = 1 Booking Group
1 Booking Group = 1..N consecutive slots
```

Untuk release awal:

```text
single-slot:
cancel/reschedule allowed

multi-slot:
cancel/reschedule individual = disabled
```

Customer tidak memilih staff.

Add-on dan voucher belum aktif.

Server/database adalah source of truth.

Midtrans adalah source of truth untuk payment status.

Browser hanya trigger interaction dan tidak boleh menjadi authority untuk mengonfirmasi pembayaran.

---

# Final Production Decision

Sistem tidak perlu di-rewrite.

Struktur existing dapat dipertahankan dan diperbaiki dengan prinsip:

```text
Payment = transaction group
Booking = individual schedule slot
```

Masalah utama branch saat ini bukan konsep multi-slotnya, melainkan beberapa bagian sistem masih memiliki asumsi lama:

```text
1 Payment = 1 Booking
```

Asumsi tersebut harus dihilangkan dari seluruh customer payment lifecycle.

Sebelum production, prioritas utama adalah:

```text
PAYMENT SECURITY
        ↓
PAYMENT/BOOKING CONSISTENCY
        ↓
MULTI-SLOT VALIDATION
        ↓
TRANSACTION & CONCURRENCY
        ↓
FEATURE CLEANUP
        ↓
UI POLISH
```

Jangan menyelesaikan UI terlebih dahulu sebelum P0 selesai, karena UI yang terlihat benar tidak dapat memperbaiki state transaction yang salah di backend.