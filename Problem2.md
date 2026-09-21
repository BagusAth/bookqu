# BookQu — Production Hardening & Multi-Slot Management Fix Specification

Dokumen ini merupakan tahap lanjutan dari `BookQu Production Logic Fix Specification`.

Branch target:

```text
agathan
```

Implementasi production logic utama sudah dilakukan, termasuk:

- multi-slot contiguous validation
- Payment → multiple Booking
- seluruh booking diproses ketika payment success/failure
- server-side Midtrans verification
- add-on disabled
- voucher disabled
- customer staff preference dihapus
- cancel/reschedule individual untuk multi-slot diblokir
- invoice mulai menggunakan seluruh booking

Dokumen ini TIDAK mengulang pekerjaan yang sudah selesai.

Fokus dokumen ini adalah menyelesaikan sisa masalah yang ditemukan setelah audit implementasi aktual.

Prioritas utama:

```text
1. Customer management menjadi 1 link untuk 1 payment group
2. Payment state machine diperketat
3. Email/invoice customer tidak duplikatif
4. Atomic quota diperkuat
5. Seluruh customer flow harus memahami payment group
6. Hapus sisa logic single-booking yang masih salah
7. Tambahkan regression test baru
```

---

# 1. CORE BUSINESS RULE BARU: PAYMENT GROUP

## Keputusan

Satu payment merupakan satu reservation group.

Contoh single-slot:

```text
Payment #100
    └── Booking A
         10:00
```

Contoh multi-slot:

```text
Payment #101
    ├── Booking A
    │    10:00
    ├── Booking B
    │    11:00
    └── Booking C
         12:00
```

Customer harus melihat payment tersebut sebagai SATU RESERVASI.

Customer TIDAK boleh diberikan:

```text
Booking A → manage link A
Booking B → manage link B
Booking C → manage link C
```

Customer harus diberikan:

```text
1 Payment Group
      ↓
1 Management Link
      ↓
seluruh booking di dalam group
```

Ini berlaku terutama untuk multi-slot.

---

# 2. MASALAH SAAT INI: SETIAP BOOKING MASIH MEMILIKI MANAGEMENT IDENTITY SENDIRI

Saat checkout multi-slot sekarang setiap Booking mendapatkan:

```text
booking_code
cancellation_token
reschedule_token
```

Contoh:

```text
Booking A
BKQ-001
token A

Booking B
BKQ-002
token B

Booking C
BKQ-003
token C
```

Akibatnya customer masih dapat menghasilkan beberapa management URL.

Ini bertentangan dengan konsep payment group.

## Solusi

Tambahkan credential management pada `payments`.

Migration baru:

```text
payments.manage_token
```

Karakteristik:

```text
string
64 characters
nullable untuk legacy data
unique
indexed
```

Generate menggunakan:

```php
Str::random(64)
```

atau generator cryptographically secure yang setara.

Jangan menggunakan `booking_code` sebagai secret.

---

# 3. PAYMENT MANAGEMENT URL

Buat URL customer-facing baru:

```text
/manage/payment/{order_id}?token={manage_token}
```

Contoh:

```text
/manage/payment/BKG-12-1726912345-532?token=xxxxxxxx
```

`order_id` berfungsi sebagai identifier.

`manage_token` berfungsi sebagai secret credential.

Jangan mengizinkan akses hanya berdasarkan order_id.

---

# 4. BUAT PAYMENT-LEVEL MANAGEMENT CONTROLLER

Buat endpoint/controller baru atau perluas `BookingManageController`.

Contoh:

```text
GET /manage/payment/{order_id}
```

Route:

```php
Route::get('/payment/{order_id}', ...)
    ->name('booking.manage.payment');
```

Controller harus melakukan:

```text
1. Cari Payment berdasarkan order_id
2. Jangan bergantung pada TenantContext saat route binding
3. Ambil payment secara tanpa tenant scope jika diperlukan
4. Validasi manage_token dengan hash_equals
5. Validasi payment memang tipe booking
6. Ambil seluruh Booking berdasarkan idpayment
7. Urutkan berdasarkan tanggal + jam
8. Validasi booking group masih valid
9. Render satu halaman management group
```

Jangan mengambil:

```php
Booking::where('booking_code', ...)
```

sebagai primary lookup untuk group management.

Source of truth:

```text
Payment.order_id
Payment.manage_token
Payment.idtenant
Booking.idpayment
```

---

# 5. CUSTOMER MANAGEMENT PAGE HARUS MENAMPILKAN GROUP

Untuk multi-slot:

```text
Payment
 ├── 10:00
 ├── 11:00
 └── 12:00
```

halaman `/manage/payment/...` harus menampilkan:

```text
Reservasi Anda

Layanan:
Studio Foto

Tanggal:
21 September 2026

Jadwal:
10:00 – 11:00
11:00 – 12:00
12:00 – 13:00

Total:
Rp600.000

Status:
Terkonfirmasi
```

Tidak perlu menampilkan:

```text
Kelola Slot 10:00
Kelola Slot 11:00
Kelola Slot 12:00
```

Tidak ada individual customer-management URL pada UI multi-slot.

---

# 6. SINGLE-SLOT VS MULTI-SLOT

Ada dua pendekatan yang diperbolehkan.

## Preferred approach

Semua payment booking BARU memiliki payment-level management token.

Maka:

```text
single-slot
→ /manage/payment/{order_id}?token=...

multi-slot
→ /manage/payment/{order_id}?token=...
```

Perbedaannya hanya jumlah booking.

Ini paling konsisten untuk arsitektur jangka panjang.

Booking-level URL lama:

```text
/manage/{booking_code}?token=...
```

tetap dipertahankan hanya untuk:

```text
legacy data
old emails
old links
existing production bookings
```

Jangan gunakan booking-level URL untuk booking baru jika payment-level management sudah tersedia.

Jika implementasi memilih pendekatan conditional, maka:

```text
single-slot → legacy/current booking URL
multi-slot  → payment-level URL
```

tetapi pendekatan ini kurang konsisten dibanding unified payment-level management.

---

# 7. JANGAN HAPUS BOOKING_CODE

`booking_code` masih diperlukan.

Jangan menghapus:

```text
booking_code
cancellation_token
reschedule_token
```

dari database secara langsung.

Masih digunakan oleh:

- legacy customer links
- owner portal
- audit
- existing emails
- review
- old production data

Tetapi untuk BOOKING BARU, customer-facing management authority harus berpindah ke payment-level.

Dengan kata lain:

```text
Booking code
= individual booking identifier

Payment manage token
= customer reservation-group credential
```

---

# 8. MANAGEMENT TOKEN HARUS DIGENERATE PADA PAYMENT, BUKAN SETIAP BOOKING SEBAGAI PRIMARY ACCESS

Pada `processCheckout()`:

Saat Payment dibuat:

```php
$payment = Payment::create([
    ...
    'manage_token' => Booking::generateSecureToken(),
]);
```

Kemudian buat N Booking.

Booking tetap boleh memiliki booking_code untuk internal/legacy purposes.

Tetapi jangan gunakan setiap booking token sebagai management link utama.

---

# 9. VALIDASI TOKEN PAYMENT

Jangan menggunakan:

```php
if ($payment->manage_token === $token)
```

Gunakan constant-time comparison:

```php
hash_equals(
    (string) $payment->manage_token,
    (string) $token
)
```

Jika invalid:

```text
403
```

Jangan bocorkan apakah:

- order_id tidak ada
- token salah
- payment tenant berbeda

secara detail kepada attacker.

---

# 10. PAYMENT MANAGEMENT HARUS TENANT-SAFE

Setelah payment ditemukan, validasi:

```text
payment.idtenant
```

harus konsisten dengan seluruh booking:

```text
booking.idtenant == payment.idtenant
```

Semua booking:

```text
booking.idpayment == payment.id
```

Jika ada data inconsistent:

```text
jangan tampilkan data sebagian
```

Log anomaly dan hentikan request.

---

# 11. MASALAH EMAIL SAAT INI: MULTI-SLOT DAPAT MENGHASILKAN BANYAK EMAIL

Saat payment sukses sekarang:

```text
payment
↓
foreach paidBookings
↓
send BookingInvoiceMail($bk)
```

Akibatnya:

```text
2 slot → 2 email
3 slot → 3 email
```

Ini salah untuk payment-group model.

## Solusi

Untuk satu payment:

```text
1 Payment
↓
1 Customer Confirmation Email
↓
1 Group Invoice
↓
1 Management Link
```

Buat Mailable baru, misalnya:

```text
BookingGroupInvoiceMail
```

Payload:

```php
Payment $payment
Collection $bookings
string $manageUrl
```

Jangan kirim N email untuk N slot.

---

# 12. EMAIL HARUS MENAMPILKAN SEMUA SLOT

Email:

```text
Booking Dikonfirmasi

Layanan:
Studio Foto

Tanggal:
21 September 2026

Jadwal:
10:00 – 11:00
11:00 – 12:00
12:00 – 13:00

Total:
Rp600.000

[Kelola Reservasi]
```

Management URL hanya SATU.

---

# 13. OWNER NOTIFICATION JUGA JANGAN DUPLIKAT UNTUK SATU PAYMENT GROUP

Saat ini owner notification diproses berdasarkan booking.

Untuk multi-slot:

```text
3 booking
→ 3 notification
```

lebih baik:

```text
1 payment group
→ 1 owner notification
```

Notification menampilkan seluruh slot:

```text
Booking baru

Customer:
Agathan

Layanan:
Studio Foto

Jadwal:
10:00
11:00
12:00

Total:
Rp600.000
```

Owner masih boleh melihat setiap booking individual di dashboard/calendar.

Yang digabung adalah notification/transaksi, bukan record booking.

---

# 14. INVOICE HARUS SEPENUHNYA PAYMENT-LEVEL

`customer.booking.invoice` sudah mulai menggunakan `$bookings`, tetapi pastikan seluruh logic invoice benar-benar menggunakan group.

Jangan menggunakan:

```php
$booking->booking_code
```

sebagai satu-satunya identity.

Gunakan:

```text
Payment order_id
+
Collection bookings
```

Invoice:

```text
Order ID
Booking Group
Customer
Service
Tanggal
Semua slot
Total payment
```

---

# 15. CUSTOMER MANAGE INVOICE SAAT INI MASIH SINGLE-BOOKING

Route:

```text
/manage/{booking_code}/invoice
```

masih mengambil:

```text
1 Booking
```

dan template:

```text
customer/manage/invoice.blade.php
```

masih single-booking oriented.

## Solusi

Tambahkan group invoice:

```text
/manage/payment/{order_id}/invoice?token=...
```

Controller:

```text
resolve Payment
→ verify manage_token
→ load bookings
→ render invoice group
```

Untuk multi-slot, invoice harus berasal dari payment group.

Single-slot juga boleh diarahkan ke invoice group yang sama.

---

# 16. CUSTOMER MANAGE SHOW HARUS MENAMPILKAN SEMUA SLOT

Saat ini `BookingManageController@show()` menerima satu booking.

Untuk payment group:

```text
Payment
↓
bookings[]
```

Controller harus mengirim:

```php
[
    'payment' => $payment,
    'bookings' => $bookings,
]
```

View jangan lagi hanya:

```text
$booking->jam
```

tetapi:

```text
foreach ($bookings as $booking)
```

dan tampilkan seluruh slot.

---

# 17. ACTION RULE MULTI-SLOT TETAP INDIVIDUAL DISABLED

Setelah management group diterapkan:

```text
multi-slot
→ cancel individual = disabled
→ reschedule individual = disabled
```

Customer manage page cukup menampilkan:

```text
Reservasi Multi-Slot

Untuk menjaga konsistensi reservasi, perubahan jadwal atau pembatalan per slot tidak tersedia.

Silakan hubungi pengelola bisnis.
```

Jangan membuat:

```text
Cancel Slot A
Cancel Slot B
Cancel Slot C
```

atau:

```text
Reschedule Slot A
```

---

# 18. LEGACY BOOKING MANAGEMENT HARUS TETAP BERFUNGSI

Jangan langsung menghapus:

```text
/manage/{booking_code}
```

karena booking production yang sudah pernah menerima link tersebut harus tetap dapat diakses.

Jadi ada dua mode:

```text
LEGACY
/manage/{booking_code}?token=...

NEW GROUP
/manage/payment/{order_id}?token=...
```

Untuk new booking group, gunakan payment-level.

---

# 19. PERKETAT PAYMENT SUCCESS STATE MACHINE

Implementasi sekarang sudah memeriksa:

```text
payment.status == sukses
payment.status == gagal
```

tetapi ada masalah pada mixed booking state.

Contoh:

```text
Payment = pending

Booking A = cancelled
Booking B = pending
```

kemudian Midtrans mengirim settlement.

Jangan melakukan:

```text
Payment → sukses
Booking B → paid
```

sementara Booking A cancelled.

## Solusi

Sebelum mengubah payment:

```text
BEGIN TRANSACTION

lock Payment
lock ALL Bookings

cek semua booking
```

Rule:

```text
Jika ada booking cancelled:
    jangan mark payment sukses
    jangan mark booking pending menjadi paid
    log late-settlement anomaly
    return

Jika semua booking masih pending:
    payment → sukses
    semua booking pending → paid

Jika semua booking sudah paid:
    idempotent success

Jika payment sudah gagal:
    jangan hidupkan kembali
```

Jangan melakukan:

```text
payment update → sukses
```

sebelum validasi group selesai.

Urutan sangat penting.

---

# 20. JANGAN HANYA LOG LATE SETTLEMENT

Current implementation telah mendeteksi booking cancelled, tetapi masih dapat mengubah payment menjadi sukses lebih dulu.

Ini harus diperbaiki.

Expected:

```text
Payment pending
Booking cancelled
Midtrans settlement
        ↓
Payment tetap terminal sesuai policy
Booking tetap cancelled
No resurrection
```

Tambahkan explicit test untuk mixed booking state.

---

# 21. PAYMENT FAILURE HARUS GROUP ATOMIC

Tetap pertahankan implementasi:

```text
Payment gagal
↓
lock semua bookings
↓
semua pending booking → cancelled
```

Tambahkan verifikasi bahwa tidak ada booking pada payment group yang tertinggal pending.

Test harus memverifikasi:

```text
N booking
→ N cancelled
```

---

# 22. PAYMENT EXPIRY HARUS GROUP ATOMIC

Tetap pertahankan:

```text
Payment pending + expired
↓
Payment gagal
↓
ALL bookings cancelled
↓
cache invalidated
```

Pastikan command:

```text
bookings:expire-payments
```

dan request:

```text
showPayment()
```

mengikuti behavior yang sama.

Jangan sampai command dan controller mempunyai dua business logic berbeda.

Jika memungkinkan, centralize expiry handling ke service:

```text
MidtransPaymentService::expirePayment()
```

agar:

```text
showPayment()
console command
scheduler
```

menggunakan implementation yang sama.

---

# 23. ATOMIC QUOTA CHECK PERLU DIPERKUAT

Current implementation memang berada di transaction, tetapi `lockForUpdate()` terhadap aggregate booking count bukan synchronization point yang ideal.

## Solusi

Lock row yang stabil.

Preferred:

```text
Subscription row
```

Flow:

```text
BEGIN

lock subscription

calculate current usage
calculate requested slot count

if exceeded:
    rollback

create N bookings

COMMIT
```

Jika subscription tidak tersedia:

gunakan tenant row sebagai synchronization point.

Tujuannya:

```text
Customer A
Customer B
```

tidak dapat melakukan quota check bersamaan pada nilai usage yang sama.

---

# 24. DEFINISI QUOTA HARUS JELAS

Untuk multi-slot:

```text
1 Payment
3 Booking slots
```

harus dihitung:

```text
3 booking usage
```

bukan:

```text
1 payment usage
```

Jadi:

```text
slotCount = 3
quota consumption = +3
```

---

# 25. `Payment::booking()` HANYA LEGACY

Tetap pertahankan untuk compatibility:

```php
public function booking(): BelongsTo
```

tetapi jangan gunakan untuk new multi-slot flow.

Official relationship:

```php
public function bookings(): HasMany
```

Semua code baru harus menggunakan:

```text
$payment->bookings
```

bukan:

```text
$payment->booking
```

Tambahkan komentar:

```php
/**
 * @deprecated Use bookings() for booking payments.
 */
```

---

# 26. `idbooking` PADA PAYMENT JANGAN DIJADIKAN SOURCE OF TRUTH LAGI

Current database masih mempunyai:

```text
payments.idbooking
```

Jangan dihapus pada phase ini karena backward compatibility.

Tetapi:

```text
idbooking
```

tidak boleh digunakan untuk menentukan seluruh booking dalam payment.

Source of truth:

```text
bookings.idpayment
```

Jadi:

```text
Payment.id
    ↓
Booking.idpayment
```

---

# 27. OWNER CUSTOMER MANAGEMENT MASIH MENGANDALKAN `payments.idbooking`

Current `OwnerCustomerController` masih menggunakan:

```text
payments.idbooking
```

untuk payment history dan spending association.

Untuk single-slot itu bekerja.

Untuk payment group, ini masih single-booking assumption.

## Solusi

Untuk payment history:

```text
Payment
→ load payment group
→ tampilkan order_id
→ tampilkan seluruh booking slots
```

Jangan membuat satu payment terlihat sebagai beberapa payment.

Untuk spending:

```text
SUM(payment.jumlah)
```

harus dihitung per payment.

Jangan melakukan:

```text
JOIN payment → N bookings
SUM(payment.jumlah)
```

karena payment bisa terduplikasi N kali.

Contoh:

```text
Payment = Rp600.000
3 bookings
```

hasil harus:

```text
Rp600.000
```

bukan:

```text
Rp1.800.000
```

Gunakan distinct payment ID atau aggregate payment terlebih dahulu.

---

# 28. CUSTOMER PAYMENT HISTORY JUGA HARUS PAYMENT-LEVEL

Customer melakukan:

```text
1 payment
3 slots
```

maka history:

```text
Booking
21 Sep
10:00 + 11:00 + 12:00
Rp600.000
```

bukan:

```text
Booking 10:00 Rp600.000
Booking 11:00 Rp600.000
Booking 12:00 Rp600.000
```

Jangan menggandakan nominal payment pada setiap booking.

---

# 29. `priceLabel` BOOKING PERLU DIPERHATIKAN

Current `Booking::getPriceLabelAttribute()` menggunakan:

```text
payment.jumlah
```

Artinya pada multi-slot:

```text
Booking A priceLabel = Rp600.000
Booking B priceLabel = Rp600.000
Booking C priceLabel = Rp600.000
```

Padahal payment total:

```text
Rp600.000
```

Ini tidak boleh dipakai sebagai harga per slot.

## Solusi

Bedakan:

```text
slot price
payment total
```

Booking individual harus menggunakan:

```text
schedule.harga_override ?? service.harga
```

Payment harus menggunakan:

```text
SUM(slot prices)
```

Dengan demikian:

```text
Slot A = Rp200.000
Slot B = Rp200.000
Slot C = Rp200.000

Payment = Rp600.000
```

Untuk invoice group:

```text
A = Rp200.000
B = Rp200.000
C = Rp200.000
Total = Rp600.000
```

Jangan menampilkan payment total sebagai price label setiap booking.

---

# 30. EMAIL CLASS EXISTING MASIH SINGLE-BOOKING

Class berikut masih menerima satu Booking:

```text
BookingConfirmedMail
BookingInvoiceMail
BookingRescheduledMail
BookingCancelledMail
```

Jangan paksa class tersebut menampung multi-slot dengan cara mengambil booking pertama.

Untuk confirmation/invoice multi-slot:

buat:

```text
BookingGroupInvoiceMail
```

atau mail group equivalent.

Untuk cancellation/reschedule multi-slot:

fitur customer masih disabled pada release pertama, sehingga group cancellation/reschedule email belum diperlukan.

---

# 31. `booking-success.blade.php` MASIH SINGLE-BOOKING

File:

```text
resources/views/customer/booking/booking-success.blade.php
```

masih menampilkan:

```text
$booking->booking_code
$booking->jam
```

Jika file ini masih digunakan oleh flow aktif, pastikan ia diubah menjadi payment-group aware.

Jika file sudah tidak lagi digunakan oleh customer booking production flow:

```text
jangan menghapus langsung
```

audit route/controller yang mereferensikannya terlebih dahulu.

Setelah dipastikan dead code:

boleh dihapus atau dipertahankan sebagai legacy.

---

# 32. CUSTOMER INVOICE GOOGLE CALENDAR HARUS MENANGANI MULTI-SLOT

Current invoice menghasilkan satu event dari:

```text
booking.jam
+
service.duration
```

Untuk multi-slot:

```text
10:00
11:00
12:00
```

harus menjadi satu reservation interval:

```text
10:00 → 13:00
```

Karena slot contiguous.

Google Calendar event:

```text
start = slot paling awal
end   = slot paling akhir + duration
```

Bukan tiga event terpisah.

---

# 33. WHATSAPP SHARE HARUS MENGGUNAKAN GROUP

Pesan share untuk multi-slot harus:

```text
Saya melakukan booking Studio Foto.

Tanggal:
21 September 2026

Jadwal:
10:00–11:00
11:00–12:00
12:00–13:00

Order ID:
BKG-...

Kelola Reservasi:
[1 management URL]
```

Jangan mencantumkan N management link.

---

# 34. PAYMENT MANAGEMENT TOKEN HARUS TESTED

Tambahkan tests:

```text
1. payment management URL valid
2. invalid token rejected
3. payment belonging to another tenant rejected
4. payment has all bookings loaded
5. multi-slot shows only one management identity
6. single-slot also works
7. legacy booking link continues to work
```

---

# 35. TEST MANAGEMENT GROUP

Tambahkan test khusus:

```text
test_multi_slot_payment_has_one_management_token()

test_multi_slot_management_page_shows_all_slots()

test_multi_slot_management_page_does_not_expose_individual_management_links()

test_multi_slot_invoice_uses_group_management_link()

test_multi_slot_email_contains_one_management_link()

test_multi_slot_confirmation_contains_one_management_link()
```

---

# 36. TEST EMAIL DUPLICATION

Untuk:

```text
Payment
 ├── Booking A
 ├── Booking B
 └── Booking C
```

assert:

```text
1 customer email
```

bukan:

```text
3 customer emails
```

Dan email tersebut harus menyebut seluruh slot.

---

# 37. TEST PAYMENT STATE MACHINE TAMBAHAN

Wajib tambahkan:

```text
1. pending payment + all pending bookings
   → settlement
   → all paid

2. pending payment + one cancelled booking
   → settlement
   → payment NOT resurrected
   → cancelled remains cancelled
   → pending booking NOT paid

3. failed payment + settlement
   → remains failed

4. sukses payment + duplicate settlement
   → idempotent

5. failed payment + duplicate failed webhook
   → idempotent
```

---

# 38. TEST QUOTA CONCURRENCY

Tambahkan skenario:

```text
quota tersisa 2

Request A → 2 slots
Request B → 2 slots
```

Expected:

```text
hanya satu request yang berhasil
request lain ditolak karena quota
```

Jangan hanya melakukan test sequential.

---

# 39. CLEANUP DEAD FRONTEND LOGIC

Checkout saat ini sudah tidak menampilkan add-on/voucher/staff secara normal, tetapi masih terdapat JavaScript seperti:

```text
voucher_code_input
btn_apply_voucher
addonCheckboxes
summary-voucher-code
```

hapus seluruh dead code tersebut.

Jangan menghapus backend feature model Voucher/Add-on owner management.

Yang dihapus hanya:

```text
customer booking integration
```

sampai feature resmi diaktifkan kembali.

---

# 40. JANGAN MENGEMBALIKAN STAFF CUSTOMER

Pastikan implementasi lanjutan tidak secara tidak sengaja menghidupkan:

```text
staff_id
availableStaff
staff preference
customer staff selection
```

Staff tetap:

```text
Owner-managed resource
```

Customer hanya:

```text
Service
Date
Consecutive Time Slots
Customer Information
Payment
```

---

# 41. CACHE HARUS GROUP-AWARE

Ketika payment group:

```text
3 slots
```

settled/cancelled/expired:

invalidate cache seluruh slot.

Jangan hanya:

```text
first booking
```

Gunakan:

```text
foreach booking group
```

dan invalidate setiap:

```text
tenant
service
date
```

---

# 42. MANAGEMENT GROUP HARUS MENANGANI DATA INCONSISTENCY

Jika ditemukan kondisi:

```text
Payment ada
Booking count = 0
```

atau:

```text
Payment tenant = 1
Booking tenant = 2
```

atau:

```text
Payment status sukses
Booking pending/cancelled mixed
```

jangan mencoba "memperbaiki" data secara diam-diam di request customer.

Lakukan:

```text
log anomaly
return safe error
```

Data repair dilakukan lewat reconciliation/administrative process.

---

# 43. PAYMENT MANAGEMENT TOKEN UNTUK LEGACY DATA

Data lama mungkin belum memiliki:

```text
payments.manage_token
```

Jangan memaksa migration untuk mengisi seluruh data lama tanpa review.

Untuk legacy:

```text
gunakan booking-level management URL
```

Untuk payment baru:

```text
gunakan payment-level management URL
```

Jika ingin migration seluruh data lama ke payment-level:

buat command terpisah:

```text
php artisan bookings:generate-payment-manage-tokens
```

dengan dry-run option.

Jangan generate token secara sembarangan di Blade.

---

# 44. JANGAN GENERATE TOKEN DI VIEW

Hindari logic:

```php
if (!$booking->booking_code) {
    $booking->assignManagementTokens();
}
```

di Blade.

View seharusnya tidak melakukan database mutation.

Token harus dibuat di:

```text
checkout transaction
payment confirmation
migration/reconciliation command
```

bukan ketika invoice dirender.

---

# 45. FINAL ARCHITECTURE

Target akhir:

```text
                    PAYMENT
                       │
             ┌─────────┴─────────┐
             │                   │
        manage_token          order_id
             │                   │
             └─────────┬─────────┘
                       ↓
              CUSTOMER MANAGEMENT
                       │
             ┌─────────┼─────────┐
             ↓         ↓         ↓
         Booking A  Booking B  Booking C
          10:00      11:00      12:00
```

Customer melihat:

```text
1 reservation
1 invoice
1 management link
1 payment
N booking slots
```

Database tetap:

```text
1 payment
N booking
```

---

# 46. FINAL PAYMENT STATE MACHINE

Gunakan:

```text
PENDING
   │
   ├── settlement/capture accepted
   │          ↓
   │       SUKSES
   │          ↓
   │      ALL bookings
   │          ↓
   │         PAID
   │
   ├── deny
   ├── cancel
   └── expire
              ↓
            GAGAL
              ↓
        ALL pending bookings
              ↓
          CANCELLED
```

Terminal states:

```text
SUKSES → tidak kembali GAGAL
GAGAL  → tidak kembali SUKSES
```

Kecuali reconciliation/manual recovery yang eksplisit.

Mixed booking state harus dianggap anomaly.

---

# 47. IMPLEMENTATION ORDER

Jangan mengerjakan semua perubahan sekaligus tanpa checkpoint.

Urutan implementasi:

```text
PHASE 1
Payment manage_token
+
payment management route
+
payment group resolver
```

↓

```text
PHASE 2
Customer manage page
+
group invoice
+
all slots rendering
```

↓

```text
PHASE 3
Single management link
+
remove N individual links
+
email consolidation
```

↓

```text
PHASE 4
Payment state-machine hardening
+
mixed booking guard
```

↓

```text
PHASE 5
Atomic quota locking
```

↓

```text
PHASE 6
Owner customer/payment history cleanup
+
priceLabel correction
+
calendar/WhatsApp group output
```

↓

```text
PHASE 7
Dead JS cleanup
```

↓

```text
PHASE 8
Full regression test
```

---

# 48. PRODUCTION ACCEPTANCE CRITERIA

Implementasi dianggap selesai hanya jika seluruh kondisi berikut terpenuhi.

## Booking

```text
single-slot checkout     ✅
multi-slot checkout      ✅
non-contiguous rejected  ✅
duplicate slot rejected  ✅
cross-tenant rejected    ✅
cross-service rejected   ✅
cross-date rejected      ✅
```

## Payment

```text
1 payment → N bookings             ✅
success → ALL paid                 ✅
failure → ALL cancelled            ✅
expiry → ALL cancelled             ✅
duplicate webhook = idempotent     ✅
late settlement tidak resurrect    ✅
```

## Management

```text
1 payment group
→ 1 management link

multi-slot
→ all slots visible

multi-slot
→ individual cancel disabled

multi-slot
→ individual reschedule disabled
```

## Customer Communication

```text
1 payment
→ 1 confirmation email

1 payment
→ 1 invoice

1 payment
→ 1 management URL
```

## Security

```text
client callback tidak dipercaya
payment token diverifikasi
tenant isolation tetap aktif
invalid token = 403
legacy links tetap berfungsi
```

## Quota

```text
slot count = quota consumption
concurrent requests tidak dapat melewati quota
```

## Data consistency

```text
Payment total ≠ repeated per booking
Booking slot price = actual slot price
Payment total = sum seluruh slot
```

---

# 49. FINAL INSTRUCTION TO AGENT

Jangan melakukan perubahan yang hanya memperbaiki tampilan.

Jangan menyelesaikan masalah management link dengan sekadar:

```text
"hapus foreach dan tampilkan booking pertama."
```

Itu bukan solusi.

Solusi yang benar adalah mengubah customer management identity menjadi:

```text
PAYMENT GROUP
```

dengan:

```text
Payment.manage_token
+
Payment.order_id
+
Payment.bookings()
```

dan satu secure management URL.

Jangan mengubah struktur database Booking agar satu record menyimpan banyak slot.

Tetap:

```text
1 Booking = 1 slot
```

dan:

```text
1 Payment = 1 reservation group
```

Jangan menghapus `booking_code` atau token legacy tanpa migration strategy.

Jangan melakukan network request Midtrans di dalam DB transaction.

Jangan mempercayai payload dari browser sebagai payment authority.

Jangan mengubah Payment menjadi sukses jika payment group memiliki booking yang sudah terminal/cancelled secara inconsistent.

Jangan membuat N email untuk N booking dalam satu payment.

Jangan menjadikan payment amount sebagai price masing-masing booking.

Jangan melakukan quota locking hanya pada aggregate count; lock stable synchronization row seperti Subscription/Tenant.

Jangan menambahkan kembali staff preference customer.

Jangan mengaktifkan kembali add-on atau voucher hanya karena logic lama masih tersedia.

Setelah implementasi selesai, jalankan seluruh regression suite dan test khusus payment-group management sebelum menyentuh UI polish lebih lanjut.