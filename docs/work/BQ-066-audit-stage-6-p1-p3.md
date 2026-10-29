---
id: BQ-066
title: Audit stage 6 P1-P3 defects (CRM Pelanggan, Moderasi Ulasan, Billing Langganan & Pengaturan Akun)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: customers-reviews
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md at a68ffe8
---

# BQ-066: Audit stage 6 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-6-CRM-REVIEWS-BILLING-SETTINGS.md (CRM Pelanggan, Moderasi Ulasan, Billing Langganan & Pengaturan Akun). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

> BUG-T6-05 overlaps BQ-033.

## P1 checklist

- [ ] BUG-T6-04: Tombol Utama "Change Plan" Tidak Memiliki Aksi Apapun (Dead Button)
- [ ] BUG-T6-05: Kartu Langganan di Sidebar Menampilkan Info Palsu pada Pelanggan Berbayar
- [ ] BUG-T6-06: Runtime Error `$dispatch is not defined` pada Simpan Catatan CRM
- [ ] BUG-T6-07: Input Upload File Logo Bisnis Hilang dari UI Halaman Pengaturan
- [ ] BUG-T6-08: Tampilan Tombol Ganda Redundan pada Halaman Checkout Payment
- [ ] BUG-T6-09: Unhandled Fetch Error Status & Silently Closed Drawer pada CRM Detail

## P2 / P3 checklist

- [ ] **BUG-T6-10 [P2]**: Format tautan WhatsApp CRM tidak memenuhi standar API (`08...` alih-alih `628...`) ([customers/table.blade.php#L39](file:///c:/laragon/www/bookqu/resources/views/owner/partials/customers/table.blade.php#L39)).
- [ ] **BUG-T6-11 [P2]**: Dropdown notifikasi topbar terdorong keluar layar pada viewport ponsel < 400px ([topbar.blade.php#L210](file:///c:/laragon/www/bookqu/resources/views/components/owner/topbar.blade.php#L210)).
- [ ] **BUG-T6-12 [P2]**: Tombol "Detail" pada halaman notifikasi tidak menandai notifikasi sebagai dibaca ([notifications.blade.php#L209](file:///c:/laragon/www/bookqu/resources/views/owner/notifications.blade.php#L209)).
- [ ] **BUG-T6-13 [P2]**: Form pencarian ulasan me-reset filter rating bintang yang sedang aktif saat disubmit ([reviews.blade.php#L68](file:///c:/laragon/www/bookqu/resources/views/owner/reviews.blade.php#L68)).
- [ ] **BUG-T6-14 [P2]**: Redirect pasca balas ulasan atau toggle visibility me-reset nomor halaman paginasi dan filter.
- [ ] **BUG-T6-15 [P2]**: Hardcoded subpath routing `/owner/reviews/...` pada action form modal balasan review.
- [ ] **BUG-T6-16 [P2]**: Breadcrumb topbar fallback ke `/ Dashboard` saat pengguna berada di halaman `owner/settings`.
- [ ] **BUG-T6-17 [P2]**: Peringatan tagihan pending yang belum kedaluwarsa diabaikan dan tidak ditampilkan di `checkout.blade.php`.
- [ ] **BUG-T6-18 [P2]**: Status `pending` pada riwayat penagihan langganan diwarnai badge merah menyerupai status gagal.
- [ ] **BUG-T6-19 [P2]**: Ketiadaan tautan aksi invoice atau bayar ulang pada riwayat penagihan langganan.
- [ ] **BUG-T6-20 [P2]**: Controller `checkPaymentStatus` langganan tidak memiliki guard status kedaluwarsa.
- [ ] **BUG-T6-21 [P2]**: Tidak ada state loading `:disabled="isSaving"` pada form catatan customer di CRM.
- [ ] **BUG-T6-22 [P3]**: Terminologi keliru "Kode Booking" pada invoice langganan platform owner ([checkout-invoice.blade.php#L62](file:///c:/laragon/www/bookqu/resources/views/owner/checkout-invoice.blade.php#L62)).
- [ ] **BUG-T6-23 [P3]**: Penggunaan `alert()` native browser saat menyalin link booking bisnis.
- [ ] **BUG-T6-24 [P3]**: Duplikasi tampilan flash message (`session('pesan')`) antara layout dan halaman pengaturan.
- [ ] **BUG-T6-25 [P3]**: Badge "🔒 Terkunci" pada widget analitik dashboard di-hardcode permanen bahkan untuk tenant berbayar.
- [ ] **BUG-T6-26 [P3]**: Overflow tab navigasi pada mobile drawer customer detail tanpa kemampuan horizontal scroll.
- [ ] **BUG-T6-27 [P3]**: Ketiadaan paginasi database pada ulasan owner, memuat seluruh data ke memory PHP Collection.

## P1 details

### BUG-T6-04: Tombol Utama "Change Plan" Tidak Memiliki Aksi Apapun (Dead Button)

- **Lokasi Berkas**: [`resources/views/owner/subscription.blade.php:39-41`](file:///c:/laragon/www/bookqu/resources/views/owner/subscription.blade.php#L39-L41)
- **Deskripsi**: Tombol utama `#btn-change-plan` pada kartu paket langganan saat ini tidak memiliki atribut `href`, event `@click`, maupun form submission. Mengkliknya tidak menghasilkan aksi apa pun, membuat owner mengira sistem upgrade/downgrade paket rusak.
- **Rekomendasi Perbaikan**: Tambahkan event smooth-scroll menuju bagian daftar paket:
  ```blade
  <button type="button" @click="document.getElementById('available-plans-section')?.scrollIntoView({ behavior: 'smooth' })" class="..." id="btn-change-plan">
      Change Plan
  </button>
  ```

### BUG-T6-05: Kartu Langganan di Sidebar Menampilkan Info Palsu pada Pelanggan Berbayar

- **Lokasi Berkas**: 
  - [`resources/views/components/owner/sidebar.blade.php:8-11`](file:///c:/laragon/www/bookqu/resources/views/components/owner/sidebar.blade.php#L8-L11)
  - [`resources/views/components/owner/sidebar.blade.php:509-513`](file:///c:/laragon/www/bookqu/resources/views/components/owner/sidebar.blade.php#L509-L513)
- **Deskripsi**: Status di-hardcode: jika bukan trial, selalu menampilkan label `Pro` dan sisa waktu `8 days left` (karena `$daysLeft` membaca `trial_berakhir` yang bernilai null pada langganan berbayar). Owner paket Small yang sudah membayar penuh selama 1 bulan tetap melihat status *"Pro - 8 days left"* disertai tombol *"Upgrade now"*.
- **Rekomendasi Perbaikan**: Ambil nama paket aktual `{{ ucfirst($subscription?->plan?->namapaket ?? 'Free') }}` dan hitung sisa hari dari `langganan_berakhir` jika status langganan `active`.

### BUG-T6-06: Runtime Error `$dispatch is not defined` pada Simpan Catatan CRM

- **Lokasi Berkas**: [`resources/views/owner/customers.blade.php:31-47`](file:///c:/laragon/www/bookqu/resources/views/owner/customers.blade.php#L31-L47)
- **Deskripsi**: Di dalam method `saveNote()`, `$dispatch` dipanggil secara telanjang di dalam promise `.then()`, memicu `Uncaught ReferenceError: $dispatch is not defined`. Catatan tersimpan di database server, namun toast notifikasi sukses tidak pernah muncul.
- **Rekomendasi Perbaikan**: Ganti dengan `window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Catatan berhasil disimpan!' } }));`.

### BUG-T6-07: Input Upload File Logo Bisnis Hilang dari UI Halaman Pengaturan

- **Lokasi Berkas**: [`resources/views/owner/settings.blade.php:67-107`](file:///c:/laragon/www/bookqu/resources/views/owner/settings.blade.php#L67-L107)
- **Deskripsi**: Formulir memiliki `enctype="multipart/form-data"` dan backend memvalidasi `'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240']`. Namun di dalam template Blade, tag `<input type="file" name="logo">` sama sekali tidak ada. Owner tidak memiliki cara untuk mengunggah atau mengganti logo bisnis.
- **Rekomendasi Perbaikan**: Tambahkan komponen preview gambar logo aktif dan input file upload avatar/logo.

### BUG-T6-08: Tampilan Tombol Ganda Redundan pada Halaman Checkout Payment

- **Lokasi Berkas**: [`resources/views/owner/checkout-payment.blade.php:65-93`](file:///c:/laragon/www/bookqu/resources/views/owner/checkout-payment.blade.php#L65-L93)
- **Deskripsi**: Tombol "Bayar Sekarang" dan tombol "Buka Ulang Halaman Pembayaran" dirender bersamaan secara bertumpuk sejak awal muat halaman tanpa pengkondisian `x-show`, membingungkan pengguna dengan dua tombol aksi yang sama.
- **Rekomendasi Perbaikan**: Sembunyikan tombol buka ulang sebelum pengguna pernah mengklik bayar sekarang.

### BUG-T6-09: Unhandled Fetch Error Status & Silently Closed Drawer pada CRM Detail

- **Lokasi Berkas**: [`resources/views/owner/customers.blade.php:18-29`](file:///c:/laragon/www/bookqu/resources/views/owner/customers.blade.php#L18-L29)
- **Deskripsi**: Fetch API tidak me-reject response dengan HTTP status 404/500. Saat customer tidak ditemukan, objek error `{"message": "Customer tidak ditemukan."}` diisi ke `activeCustomer`, menyebabkan drawer menampilkan nilai `undefined` di mana-mana.
- **Rekomendasi Perbaikan**: Periksa `if (!r.ok)` sebelum assign state dan tampilkan toast error jika gagal.

## Evidence

_Fill in when done._
