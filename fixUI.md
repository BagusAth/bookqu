# BOOKQU — CUSTOMER FRONTEND PRESCRIPTIVE IMPLEMENTATION SPECIFICATION

Target branch:

```text
agathan
```

Repository:

```text
BagusAth/bookqu
```

Dokumen ini adalah implementation specification, bukan sekadar UX recommendation.

Agent WAJIB mengikuti solusi yang ditentukan di dokumen ini dan TIDAK BOLEH mengarang architecture, route, business rule, wording, atau UI behavior baru tanpa dasar dari implementation yang sudah ada.

---

# 1. GLOBAL IMPLEMENTATION CONTRACT

## 1.1 Tujuan

Perbaiki customer-facing booking flow BookQu agar:

```text
customer dapat menyelesaikan booking
↓
tanpa memahami architecture aplikasi
↓
tanpa salah memahami status pembayaran
↓
tanpa salah memilih multi-slot
↓
tanpa melihat technical terminology
↓
tanpa menerima credential management melalui sharing
```

Flow customer yang menjadi target:

```text
Pilih Layanan
    ↓
Pilih Tanggal
    ↓
Pilih Waktu
    ↓
Data Pemesan
    ↓
Pembayaran
    ↓
Reservasi Berhasil / Pembayaran Gagal
    ↓
Kelola Reservasi
    ↓
Invoice
```

---

# 2. RULE YANG TIDAK BOLEH DILANGGAR AGENT

## RULE-001 — Jangan redesign dari nol

Pertahankan:

```text
booking shell
layout structure
tenant identity
responsive layout
desktop progress indicator
mobile sticky action
calendar visual system
existing card system
existing Tailwind approach
existing Blade architecture
```

Jangan mengganti frontend framework.

Jangan memperkenalkan React/Vue/Livewire hanya untuk perubahan UX ini.

Jangan memasukkan UI library baru.

---

## RULE-002 — Backend tetap source of truth

Frontend tidak boleh menentukan sendiri:

```text
harga final
availability final
payment result
cancellation eligibility
reschedule eligibility
booking status
```

Frontend hanya:

```text
menampilkan
memvalidasi UX
memberikan feedback
```

Backend tetap melakukan validation final.

---

## RULE-003 — Jangan membuat route baru jika route existing dapat digunakan

Sebelum menambahkan route:

```text
inspect routes/web.php
inspect controller
inspect existing legacy/customer manage flow
```

Jika functionality existing masih valid:

```text
reuse
```

Jangan membuat:

```text
cancel-v2
reschedule-v2
manage-new
invoice-new
```

tanpa kebutuhan architectural yang benar-benar terbukti.

---

## RULE-004 — Jangan menghidupkan kembali feature yang sengaja disabled

Feature berikut TIDAK BOLEH diaktifkan:

```text
voucher
add-on
customer staff preference
```

Selama backend masih disabled.

---

## RULE-005 — Jangan mempertahankan dua UX architecture

Canonical customer management architecture adalah:

```text
Payment
    ↓
Booking[]
    ↓
Payment-level management
    ↓
Group invoice
```

Legacy individual booking flow hanya boleh dipertahankan untuk compatibility yang memang masih diperlukan.

---

## RULE-006 — Jangan memasukkan manage token ke external share

Manage token adalah credential.

Tidak boleh muncul di:

```text
WhatsApp share
Google Calendar description
public page
visible customer metadata
```

---

# 3. TARGET FILES

Audit dan ubah hanya file yang relevan:

```text
resources/views/customer/layouts/booking-shell.blade.php

resources/views/customer/booking/program-selection.blade.php
resources/views/customer/booking/date-selection.blade.php
resources/views/customer/booking/time-selection.blade.php
resources/views/customer/booking/checkout.blade.php
resources/views/customer/booking/payment.blade.php
resources/views/customer/booking/invoice.blade.php

resources/views/customer/manage/payment-show.blade.php
resources/views/customer/manage/payment-invoice.blade.php

public/js/booking-program.js
public/js/booking-date.js
public/js/booking-time.js
public/js/booking-checkout.js

public/css/booking-program.css
public/css/booking-manage.css

app/Http/Controllers/BookingController.php
app/Http/Controllers/BookingManageController.php
routes/web.php
```

File legacy harus diverifikasi sebelum dihapus:

```text
resources/views/customer/booking/booking-success.blade.php
resources/views/customer/booking/booking-failed.blade.php

resources/views/customer/manage/show.blade.php
resources/views/customer/manage/invoice.blade.php
resources/views/customer/manage/reschedule.blade.php
```

---

# 4. P0 — PAYMENT STATE MACHINE

## Problem

Current customer payment page memiliki state:

```text
pending
success
failed
expired
```

Tetapi UI belum memisahkan state tersebut dengan benar.

Khususnya:

```text
failed
↓
reload
↓
payment page
↓
masih terlihat pending
```

Ini BUG.

---

# 5. REQUIRED PAYMENT STATE

Gunakan state berikut:

```text
pending
success
failed
expired
```

Mapping wajib:

```text
SUCCESS
→ redirect invoice

FAILED
→ render failed state

EXPIRED
→ render expired state / redirect sesuai existing expiration flow

PENDING
→ payment page
```

Tidak boleh ada:

```text
FAILED → reload → pending UI
```

---

# 6. BOOKINGCONTROLLER — SHOW PAYMENT

Target:

```text
BookingController@showPayment
```

Implementasikan logic konseptual berikut:

```php
if ($payment->status === 'sukses') {
    return redirect()->route(/* existing invoice route */);
}

if ($payment->status === 'gagal') {
    return view(/* payment failed state */);
}

if ($payment->status === 'pending' && $payment->isExpired()) {
    // gunakan existing expiration handling
}

return view(/* payment page */);
```

CATATAN:

Jangan mengarang method `isExpired()` jika model tidak memilikinya.

Gunakan logic expiration yang sudah ada di repository.

Tujuannya adalah behavior di atas, bukan nama method tertentu.

---

# 7. PAYMENT BLADE — HAPUS AUTO-OPEN

File:

```text
resources/views/customer/booking/payment.blade.php
```

Current behavior memiliki:

```javascript
setTimeout(() => {
    payButton.click();
}, 500);
```

HAPUS seluruh auto-launch tersebut.

Jangan menggantinya dengan timeout lain.

Customer harus melakukan explicit action.

---

# 8. PAYMENT CTA

Gunakan:

```text
Bayar Sekarang
```

bukan:

```text
Buka Pilihan Pembayaran
```

Alasannya: customer tidak sedang “membuka pilihan”. Customer sedang menyelesaikan pembayaran.

Button tetap menggunakan Midtrans Snap yang existing.

Behavior:

```text
customer klik
↓
snap.pay(existing_snap_token)
```

Tidak boleh ada perubahan pada payment provider architecture.

---

# 9. PAYMENT PAGE HIERARCHY

Susunan wajib:

```text
Selesaikan Pembayaran

Rp xxx.xxx

Bayar sebelum 14.32

[ Bayar Sekarang ]

Detail Reservasi
    Layanan
    Tanggal
    Waktu

Order ID
Status
```

Primary information:

```text
jumlah
deadline
payment action
```

Secondary information:

```text
order ID
technical transaction metadata
```

---

# 10. PAYMENT COPY

Gunakan wording final berikut.

Title:

```text
Selesaikan Pembayaran
```

Status:

```text
Menunggu Pembayaran
```

Description:

```text
Selesaikan pembayaran sebelum batas waktu agar reservasi Anda tetap aktif.
```

CTA:

```text
Bayar Sekarang
```

Manual verification:

```text
Periksa Status Pembayaran
```

Monitoring:

```text
Kami sedang memantau status pembayaran Anda.
```

Verification:

```text
Memverifikasi pembayaran...
```

Jangan gunakan:

```text
Checking payment
Processing
Payment Group
Payment Entity
```

---

# 11. PAYMENT CALLBACK STATE

Existing callbacks:

```text
onSuccess
onPending
onError
onClose
```

Tetap gunakan.

Jangan membuat payment provider flow baru.

Behavior:

```text
onSuccess
→ existing callback
→ backend verification
→ success
→ invoice redirect

onPending
→ tetap pending
→ jangan redirect failed

onError
→ jangan langsung assume business failure jika backend belum mengonfirmasi
→ lakukan existing callback verification

onClose
→ check status existing
```

Backend tetap menentukan final status.

---

# 12. FAILED PAYMENT UI

Jangan:

```javascript
window.location.reload();
```

ketika polling memperoleh:

```text
gagal
```

Gunakan dedicated failed state.

Required content:

```text
Pembayaran Tidak Berhasil

Pembayaran belum berhasil diproses.
Reservasi Anda belum dikonfirmasi.

[ Coba Bayar Lagi ]
[ Kembali ke Pemesanan ]
```

Jika transaction status benar-benar final dan tidak dapat dilanjutkan, gunakan:

```text
Pembayaran Tidak Berhasil

Pembayaran ini tidak dapat dilanjutkan.
Silakan buat reservasi baru.
```

---

# 13. IMPORTANT — RETRY BEHAVIOR

Jangan membuat retry melakukan:

```text
reload current page
```

jika payment sudah final failed.

Retry harus mengikuti architecture payment yang sudah ada.

Jika current payment token tidak lagi valid:

```text
redirect → booking start / program selection
```

Gunakan existing flow.

Jangan membuat token payment baru di frontend.

---

# 14. ALERT() CLEANUP

Current payment JavaScript menggunakan:

```javascript
alert(...)
```

Ganti untuk error/status normal dengan inline state.

Contoh:

```text
Pembayaran belum terdeteksi.
Tunggu beberapa saat lalu coba periksa kembali.
```

Tempatkan message di bawah/sekitar payment CTA.

`alert()` hanya boleh dipertahankan jika benar-benar digunakan untuk browser-level failure yang tidak memiliki UI state alternatif.

---

# 15. PAYMENT EXPIRATION

Saat countdown habis:

JANGAN hanya:

```javascript
window.location.reload()
```

dan berharap controller memperbaiki UX.

UI harus berubah menjadi:

```text
Waktu Pembayaran Habis

Batas waktu pembayaran telah berakhir
sehingga reservasi ini tidak dapat dilanjutkan.

[ Buat Reservasi Baru ]
```

Jika backend membutuhkan request/check tambahan untuk finalizing expired state, lakukan melalui existing endpoint/controller behavior.

---

# 16. P0 — PAYMENT MANAGEMENT INVOICE VISIBILITY

File:

```text
resources/views/customer/manage/payment-show.blade.php
```

Current invoice link tidak boleh selalu tampil.

Rule:

```text
payment sukses
→ tampilkan "Lihat Invoice"

payment pending
→ jangan tampilkan invoice

payment gagal
→ jangan tampilkan invoice
```

Controller:

```text
BookingManageController@invoicePaymentGroup
```

tetap mempertahankan server-side validation:

```text
status === sukses
```

Jangan melemahkan controller hanya supaya button selalu bisa dibuka.

---

# 17. P0 — PAYMENT MANAGEMENT STATE UI

Payment management harus menampilkan state sesuai backend.

## Pending

```text
Menunggu Pembayaran

Selesaikan pembayaran untuk mengonfirmasi reservasi Anda.

[ Bayar Sekarang ]
```

## Success

```text
Reservasi Dikonfirmasi

[ Lihat Invoice ]
```

## Failed

```text
Pembayaran Tidak Berhasil

Reservasi ini belum dikonfirmasi.
```

## Expired

```text
Waktu Pembayaran Habis
```

---

# 18. P0 — SINGLE-SLOT MANAGEMENT

Canonical page:

```text
/manage/payment/{order_id}?token=...
```

Untuk payment yang hanya mempunyai satu booking:

dan booking tersebut eligible untuk action:

```text
cancel
reschedule
```

tampilkan action yang sesuai.

Target UI:

```text
[ Ubah Jadwal ]
[ Batalkan Reservasi ]
```

JANGAN mengarang route baru sebelum mengecek existing:

```text
routes/web.php
BookingManageController
legacy manage flow
```

Gunakan existing implementation jika compatible.

---

# 19. MULTI-SLOT MANAGEMENT

Untuk:

```text
payment->bookings()->count() > 1
```

jangan tampilkan individual:

```text
Ubah Jadwal
Batalkan Sesi #1
Batalkan Sesi #2
```

Gunakan:

```text
Reservasi ini terdiri dari beberapa sesi yang berurutan.

Perubahan atau pembatalan per sesi tidak tersedia.
```

Jangan membuat frontend menawarkan feature yang backend memang melarang.

---

# 20. MULTI-SLOT — TIME RANGE

Pada semua tempat customer melihat jadwal:

gunakan:

```text
10:00 – 11:00
```

bukan hanya:

```text
10:00
```

Target berlaku di:

```text
time selection
checkout
payment
invoice
manage reservation
calendar share
WhatsApp share
```

---

# 21. MULTI-SLOT — CONTIGUOUS SELECTION

Backend adalah authority.

Frontend harus melakukan early validation.

Rule:

```text
10:00
11:00
12:00
```

valid.

Sedangkan:

```text
10:00
12:00
```

invalid.

Frontend harus mengetahui urutan slot berdasarkan waktu/schedule interval.

Jangan sekadar memeriksa array index jika index tidak menjamin interval contiguous.

---

# 22. MULTI-SLOT SELECTION UX

Helper text:

```text
Pilih satu waktu, atau beberapa waktu yang berurutan untuk memesan sesi lebih lama.
```

Saat selection sudah dimulai:

```text
Pilih sesi yang berdekatan untuk melanjutkan.
```

Jika customer mencoba memilih slot non-contiguous:

```text
Slot harus berurutan. Pilih sesi yang berdekatan terlebih dahulu.
```

Jangan:

```text
alert()
```

Gunakan inline feedback.

---

# 23. SLOT PRICE

Jika payload sudah memiliki:

```text
price
price_label
```

gunakan data tersebut.

Card:

```text
10:00 – 11:00
Rp250.000
Tersedia
```

Jangan menghitung ulang harga di browser berdasarkan:

```text
service.harga
```

jika backend sudah mengirim calculated per-slot price.

Server tetap authority.

---

# 24. CALENDAR — FULL VS BLOCKED

Backend harus mengirim explicit semantic state.

Recommended:

```json
{
    "available_slots": 0,
    "is_blocked": true
}
```

Frontend:

```text
is_blocked === true
→ Tidak tersedia
```

Sedangkan:

```text
available_slots === 0
&& is_blocked !== true
→ Penuh
```

Jangan menggunakan:

```text
available_slots === 0
→ otomatis "Penuh"
```

---

# 25. CALENDAR COPY

Page:

```text
Pilih Tanggal
```

Helper:

```text
Pilih tanggal yang tersedia untuk reservasi Anda.
```

Calendar month:

```text
September 2026
```

Available:

```text
Tersedia
```

Limited:

```text
Terbatas
```

Full:

```text
Penuh
```

Blocked:

```text
Tidak tersedia
```

---

# 26. DATE PERSISTENCE

`booking-date.js` tidak boleh selalu mereset selected date tanpa membaca booking state.

Expected behavior:

```text
customer memilih 20 September
↓
masuk Time Selection
↓
back
↓
20 September tetap selected
```

Gunakan existing booking/session state sebagai source.

Jangan membuat duplicate client-side persistence system.

---

# 27. TIME SELECTION SUMMARY

Desktop:

tampilkan seluruh selected range.

Mobile:

Jika <= 3 session:

```text
10:00 – 11:00
11:00 – 12:00
12:00 – 13:00
```

Jika > 3:

```text
4 sesi dipilih
10:00 – 14:00
```

Jangan menghasilkan string panjang:

```text
10:00, 11:00, 12:00, 13:00, 14:00
```

---

# 28. TIME SUBMISSION LOCK

Jangan mengandalkan:

```javascript
setTimeout(() => isSubmitting = false, 5000)
```

untuk membuka button kembali.

Gunakan:

```text
request lifecycle
```

Expected:

```text
submit
↓
disable
↓
request success
→ navigation

request failure
→ re-enable
```

Tidak boleh:

```text
request masih berjalan
↓
5 detik
↓
button kembali aktif
```

---

# 29. CHECKOUT PAGE

Title:

```text
Data Pemesan
```

Helper:

```text
Pastikan nama, email, dan nomor WhatsApp Anda sudah benar.
```

Fields:

```text
Nama lengkap
Alamat email
Nomor WhatsApp
Catatan (opsional)
```

Remember:

```text
Simpan data kontak di perangkat ini untuk pemesanan berikutnya.
```

CTA:

```text
Lanjutkan ke Pembayaran
```

---

# 30. MOBILE CHECKOUT SUMMARY

Di mobile, sebelum CTA final, tampilkan:

```text
Detail Reservasi

Layanan
[Nama layanan]

Tanggal
[20 September 2026]

Waktu
10:00 – 11:00
11:00 – 12:00

2 sesi

Total
Rp500.000
```

Bottom sticky CTA tetap dipertahankan.

Tujuan:

customer dapat melakukan final verification tanpa kembali ke previous page.

---

# 31. OBSOLETE CHECKOUT FEATURES

Hapus dari current checkout implementation:

```text
voucher UI
add-on UI
staff preference UI
voucher validation JS
addon checkbox handling
discount summary yang tidak digunakan
```

Jangan sekadar hide dengan CSS.

Jika DOM element tidak lagi ada, JS legacy yang bergantung padanya juga harus dibuang.

---

# 32. `booking-checkout.js`

Periksa apakah:

```text
public/js/booking-checkout.js
```

masih direferensikan oleh current checkout.

Jika tidak direferensikan:

```text
DELETE FILE
```

Jika masih direferensikan:

```text
REMOVE REFERENCE
THEN DELETE
```

Jangan mempertahankan dead JavaScript hanya karena “mungkin berguna”.

---

# 33. LEGACY SUCCESS/FAILED VIEW

Periksa:

```text
booking-success.blade.php
booking-failed.blade.php
```

Cari seluruh route/reference.

Jika tidak digunakan oleh current flow:

```text
DELETE
```

Current success UX harus:

```text
payment success
↓
group invoice
```

Bukan legacy success page.

---

# 34. TERMINOLOGY CONTRACT

Primary customer vocabulary:

```text
Layanan
Tanggal
Waktu
Reservasi
Pembayaran
Data Pemesan
Invoice
Order ID
```

Gunakan "sesi" jika menjelaskan multiple time units.

Gunakan "slot" hanya ketika menjelaskan availability mechanism.

Jangan gunakan "Program" sebagai primary customer-facing term jika yang dipilih customer sebenarnya adalah layanan.

---

# 35. SERVICE SEARCH

Current placeholder harus cocok dengan actual search implementation.

Gunakan:

```text
Cari layanan...
```

Bukan:

```text
Cari nama layanan, paket, atau durasi...
```

kecuali code benar-benar melakukan search pada ketiga atribut tersebut.

---

# 36. SERVICE CARD

Keep automatic navigation behavior jika memang sudah menjadi existing UX.

Tetapi tambahkan affordance visual:

```text
Pilih layanan ini
```

atau CTA setara.

Jangan mengubah card menjadi confirmation modal.

---

# 37. PAYMENT → MANAGEMENT → INVOICE DESIGN SYSTEM

Semua halaman harus menggunakan visual language yang sama.

Source of truth:

```text
booking-shell
```

Reuse:

```text
tenant logo
tenant theme color
tenant font
button style
radius
card language
spacing
```

Jangan mempertahankan visual system terpisah seperti:

```text
hardcoded indigo
generic logo initial
different typography
admin-style layout
```

pada manage/invoice apabila booking shell sudah menyediakan tenant theme.

---

# 38. MANAGEMENT PAGE INFORMATION HIERARCHY

Primary:

```text
Reservasi Anda
Jadwal
Status
Action
```

Secondary:

```text
Customer information
Payment information
Order ID
```

Tertiary:

```text
individual booking codes
internal metadata
```

Jangan membuat:

```text
Slot #1
Booking Code XXXXX
Slot #2
Booking Code XXXXX
```

menjadi primary hierarchy.

---

# 39. SHARE PRIVACY

## Google Calendar

Description:

```text
Reservasi di [Nama Tenant]

Layanan:
[Nama layanan]

Tanggal:
[Tanggal]

Waktu:
[Waktu]

Order ID:
[Order ID]
```

Jangan memasukkan:

```text
manage URL
token
credential
```

---

## WhatsApp

Gunakan:

```text
Reservasi saya di [Nama Tenant]

Layanan: [Nama layanan]
Tanggal: [Tanggal]
Waktu: [Waktu]
Order ID: [Order ID]

Reservasi telah dikonfirmasi.
```

Jangan memasukkan management URL.

---

# 40. PAYMENT MANAGEMENT — EXISTING CANCEL FLOW

Current payment page sudah memiliki action:

```text
Batalkan & Ganti Jadwal
```

Jangan mempertahankan behavior tersebut secara otomatis untuk semua booking.

Rule:

```text
single slot
+
eligible according to backend
→ boleh

multi slot
→ jangan gunakan individual cancellation
```

Jika existing backend route menerima payment-level parameter:

```text
reuse
```

Jika route hanya dirancang untuk individual booking:

```text
do not pass payment group as if it were booking entity
```

Cari existing controller contract terlebih dahulu.

---

# 41. POLICY COPY

Jangan hardcode:

```text
24 jam
```

jika backend memiliki konfigurasi tenant.

Render actual configured value.

Contoh:

```text
Pembatalan tersedia hingga 24 jam sebelum sesi.
```

atau:

```text
Pembatalan tersedia hingga 6 jam sebelum sesi.
```

Nilai harus berasal dari backend.

---

# 42. ERROR COPY STANDARD

Semua customer error harus menjawab:

```text
apa yang terjadi?
apa yang harus dilakukan?
```

Contoh:

Buruk:

```text
Terjadi kesalahan.
```

Baik:

```text
Jadwal baru saja berubah.
Silakan pilih waktu lain yang masih tersedia.
```

Buruk:

```text
Invalid schedule.
```

Baik:

```text
Pilih waktu yang berurutan untuk memesan beberapa sesi.
```

Buruk:

```text
Payment failed.
```

Baik:

```text
Pembayaran belum berhasil diproses.
Reservasi Anda belum dikonfirmasi.
```

---

# 43. LOADING COPY STANDARD

Gunakan:

```text
Memuat layanan...
Memuat ketersediaan...
Memuat jadwal...
Memproses reservasi...
Memverifikasi pembayaran...
Memuat invoice...
```

Jangan campur:

```text
Loading
Processing
Checking
Please wait
```

dalam flow utama berbahasa Indonesia.

---

# 44. ACCESSIBILITY

Pertahankan existing:

```text
role
aria-selected
aria-label
keyboard selection
```

Tambahkan:

```text
focus-visible
aria-describedby pada error
aria-live pada payment state
```

Payment state wajib mempunyai live announcement.

Contoh:

```html
<div aria-live="polite">
    Memverifikasi pembayaran...
</div>
```

Kemudian:

```text
Pembayaran berhasil dikonfirmasi.
```

---

# 45. EXACT WORK ORDER FOR AGENT

Agent WAJIB mengerjakan dalam urutan:

```text
PHASE 1
Inspect current files and routes.

PHASE 2
Fix payment status state machine.

PHASE 3
Remove payment auto-open.

PHASE 4
Fix payment status UI.

PHASE 5
Fix conditional invoice visibility.

PHASE 6
Fix single-slot / multi-slot management behavior.

PHASE 7
Fix contiguous time selection.

PHASE 8
Add time ranges and per-slot prices.

PHASE 9
Fix blocked/full calendar semantics.

PHASE 10
Fix checkout mobile summary.

PHASE 11
Remove obsolete voucher/add-on JS.

PHASE 12
Unify tenant branding.

PHASE 13
Replace customer-facing copy.

PHASE 14
Fix accessibility/loading state.

PHASE 15
Remove confirmed dead files.

PHASE 16
Run regression tests.
```

Jangan mengerjakan Phase 12 sebelum Phase 1–10 stabil.

---

# 46. AGENT RESPONSE CONTRACT

Setelah setiap phase selesai, agent harus melaporkan:

```text
1. Files changed
2. Exact behavior changed
3. Existing behavior preserved
4. Tests performed
5. Any unresolved dependency
```

Agent tidak boleh mengatakan:

```text
"Frontend improved"
```

tanpa menjelaskan perubahan konkret.

---

# 47. REGRESSION TEST — PAYMENT

WAJIB test:

```text
pending
→ payment page

success
→ invoice

failed
→ failed state

expired
→ expired state

manual check pending
→ inline pending feedback

manual check success
→ invoice

manual check failed
→ failed state
```

Tidak boleh ada:

```text
failed → reload → pending
```

---

# 48. REGRESSION TEST — MULTI SLOT

Test:

```text
10:00
```

valid.

```text
10:00 + 11:00
```

valid.

```text
10:00 + 12:00
```

invalid.

```text
11:00 + 12:00 + 13:00
```

valid.

Past slot:

```text
disabled
```

Booked:

```text
disabled
```

Blocked:

```text
not selectable
```

---

# 49. REGRESSION TEST — MANAGEMENT

Single slot:

```text
status success
→ invoice visible

eligible
→ reschedule available

eligible
→ cancel available
```

Multi slot:

```text
invoice available only when success
individual cancel unavailable
individual reschedule unavailable
restriction explanation visible
```

---

# 50. REGRESSION TEST — SHARE

Google Calendar:

```text
[PASS] booking information exists
[PASS] order ID exists
[PASS] token absent
[PASS] manage URL absent
```

WhatsApp:

```text
[PASS] booking information exists
[PASS] order ID exists
[PASS] token absent
[PASS] manage URL absent
```

---

# 51. REGRESSION TEST — MOBILE

Check at minimum:

```text
360px
390px
```

Verify:

```text
no horizontal overflow
no clipped CTA
sticky CTA does not cover content
time labels fit
price labels fit
payment state fits
error state fits
modal fits
```

---

# 52. FINAL CUSTOMER COPY TABLE

Gunakan copy berikut sebagai source of truth.

| Context | Final Copy |
|---|---|
| Service title | Pilih Layanan |
| Service search | Cari layanan... |
| Date title | Pilih Tanggal |
| Date helper | Pilih tanggal yang tersedia untuk reservasi Anda. |
| Time title | Pilih Waktu |
| Time helper | Pilih satu waktu, atau beberapa waktu yang berurutan untuk memesan sesi lebih lama. |
| Non-contiguous | Slot harus berurutan. Pilih sesi yang berdekatan terlebih dahulu. |
| Checkout title | Data Pemesan |
| Checkout helper | Pastikan nama, email, dan nomor WhatsApp Anda sudah benar. |
| Remember data | Simpan data kontak di perangkat ini untuk pemesanan berikutnya. |
| Payment title | Selesaikan Pembayaran |
| Payment pending | Menunggu Pembayaran |
| Payment CTA | Bayar Sekarang |
| Check status | Periksa Status Pembayaran |
| Verification | Memverifikasi pembayaran... |
| Payment failed | Pembayaran Tidak Berhasil |
| Payment failed description | Pembayaran belum berhasil diproses. Reservasi Anda belum dikonfirmasi. |
| Retry | Coba Bayar Lagi |
| Expired | Waktu Pembayaran Habis |
| Expired description | Batas waktu pembayaran telah berakhir sehingga reservasi ini tidak dapat dilanjutkan. |
| New booking | Buat Reservasi Baru |
| Invoice success | Reservasi Berhasil |
| Invoice success helper | Pembayaran Anda telah berhasil dan reservasi telah dikonfirmasi. |
| Manage title | Kelola Reservasi |
| Manage schedule | Jadwal Anda |
| Cancel | Batalkan Reservasi |
| Reschedule | Ubah Jadwal |
| Invoice | Lihat Invoice |
| Multi-slot restriction | Reservasi ini terdiri dari beberapa sesi yang berurutan. Perubahan atau pembatalan per sesi tidak tersedia. |
| Full | Penuh |
| Blocked | Tidak tersedia |
| Past time | Waktu telah lewat |

---

# 53. DEFINITION OF DONE

Task hanya dianggap selesai jika:

```text
[ ] Payment failed tidak kembali ke pending page
[ ] Payment tidak auto-open
[ ] Payment state jelas
[ ] Invoice hanya dapat diakses saat success
[ ] Single-slot management memiliki action yang sesuai
[ ] Multi-slot menjelaskan restriction
[ ] Non-contiguous selection dicegah di frontend
[ ] Backend tetap melakukan final validation
[ ] Time range tampil
[ ] Per-slot price tampil bila tersedia
[ ] Full dan blocked dibedakan
[ ] Mobile checkout menampilkan complete review
[ ] Voucher/add-on dead code dibersihkan
[ ] Legacy JS dibuang jika unused
[ ] Legacy success/failure views dibuang jika unused
[ ] Tenant branding konsisten
[ ] Terminology konsisten
[ ] Policy text berasal dari configuration
[ ] Token tidak masuk Calendar
[ ] Token tidak masuk WhatsApp
[ ] Accessibility state diperbaiki
[ ] Mobile regression lolos
[ ] Payment regression lolos
[ ] Multi-slot regression lolos
```

---

# 54. NON-GOALS

Agent TIDAK BOLEH melakukan hal-hal berikut sebagai bagian task ini:

```text
- redesign total
- migrasi Blade ke SPA
- migrasi Alpine ke framework lain
- membuat payment provider baru
- mengubah database architecture
- mengubah Payment → Booking[] architecture
- mengaktifkan voucher
- mengaktifkan add-on
- mengubah business rule multi-slot
- mengubah server-side validation
- membuat customer authentication system baru
- membuat management token baru
- membuat route duplicate
- menambah dependency hanya untuk UX kecil
```

---

# 55. PRINCIPLE PALING PENTING

Jangan membuat customer beradaptasi dengan sistem.

Sistem harus beradaptasi dengan customer.

Customer tidak perlu tahu:

```text
Payment
mempunyai banyak Booking
backend menggunakan payment group
Midtrans callback
manage token
schedule entity
```

Customer hanya perlu memahami:

```text
Saya memilih layanan.
Saya memilih tanggal.
Saya memilih waktu.
Saya memasukkan data.
Saya membayar.
Reservasi saya berhasil.
Saya dapat melihat dan mengelolanya.
```

Architecture internal boleh kompleks.

Customer-facing experience harus sederhana, konsisten, dan predictable.