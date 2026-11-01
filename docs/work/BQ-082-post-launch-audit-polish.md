---
id: BQ-082
title: Post-launch polish from the 2026-10 audit (P2/P3)
type: improvement
status: proposed
priority: P2
phase: 8
domain: booking
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: []
---

## Problem

The October 2026 audit stages (BQ-061–BQ-066) were triaged before launch: every P1 item was fixed, verified as already fixed, or moved to a launch item (walk-in: BQ-057/BQ-081). What remains is P2/P3 polish plus one deferred feature (BUG-T5-12, reviews on the payment-group portal). These were collected here so the closed audit files stay a historical record.

## Plan

After launch, pick items per area into small light-lane PRs. Verify each against the current code first; several may already be fixed.

## Open items

### From BQ-061 (full details in `done/BQ-061-audit-stage-1-p1-p3.md`)

- [ ] **BUG-T1-09 [P2]**: Seluruh link footer kebijakan dan panduan mengarah ke placeholder `href="#"` ([welcome.blade.php#L493](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L493)).
- [ ] **BUG-T1-10 [P2]**: Ketiadaan toggle mata intip (show/hide password) pada form login dan register ([login.blade.php#L60](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L60)).
- [ ] **BUG-T1-11 [P2]**: Tombol "Kirim Ulang Email Verifikasi" tidak memiliki timer throttle cooldown 60 detik ([verify-email.blade.php#L38](file:///c:/laragon/www/bookqu/resources/views/auth/verify-email.blade.php#L38)).
- [ ] **BUG-T1-12 [P2]**: Nilai checkbox "Ingat Saya" (Remember Me) tidak tersimpan saat login gagal.
- [ ] **BUG-T1-13 [P2]**: Normalisasi input nomor WhatsApp pada modal onboarding profil tidak menangani awalan `+62` atau spasi.
- [ ] **BUG-T1-14 [P2]**: Scroll snapping tersendat pada navigasi anchor section landing page.
- [ ] **BUG-T1-15 [P2]**: Ketiadaan indikator fokus keyboard (focus-visible ring) pada elemen interaktif header.
- [ ] **BUG-T1-16 [P2]**: Tombol submit form login/register tidak memiliki indikator loading spinner saat ditekan.
- [ ] **BUG-T1-17 [P3]**: Footer copyright tahun di-hardcode `2024` alih-alih `{{ date('Y') }}`.
- [ ] **BUG-T1-18 [P3]**: Kontras rasio teks subjudul landing page berada di bawah standar WCAG AA (4.5:1).
- [ ] **BUG-T1-19 [P3]**: Transisi warna tema pada mode gelap/terang mengalami flickering.
- [ ] **BUG-T1-20 [P3]**: Tombol "Kembali ke Beranda" pada halaman syarat ketentuan tidak memiliki ikon navigasi.
- [ ] **BUG-T1-21 [P3]**: Missing meta tag `theme-color` untuk bilah peramban mobile.

### From BQ-062 (full details in `done/BQ-062-audit-stage-2-p1-p3.md`)

- [ ] **BUG-T2-12 [P2]**: Tombol "Batal" pada modal edit layanan tidak me-reset data yang telah diubah pengguna.
- [ ] **BUG-T2-13 [P2]**: Pencarian layanan tidak menyaring berdasarkan nama staf yang bertugas.
- [ ] **BUG-T2-14 [P2]**: Format mata uang pada input harga layanan tidak otomatis menyisipkan pemisah ribuan.
- [ ] **BUG-T2-15 [P2]**: Filter kategori pada halaman program tidak mencerminkan URL query string.
- [ ] **BUG-T2-16 [P2]**: Drawer kalender mobile menutupi tombol navigasi minggu/bulan.
- [ ] **BUG-T2-17 [P2]**: Durasi layanan dalam menit tidak memiliki validasi kelipatan 5 atau 15 menit.
- [ ] **BUG-T2-18 [P2]**: Empty state jadwal kalender tidak menyertakan tombol pintas "Buat Jadwal Baru".
- [ ] **BUG-T2-19 [P2]**: Tab hari pada halaman jadwal tidak mengingat tab terakhir yang dibuka setelah refresh.
- [ ] **BUG-T2-20 [P2]**: Tanggal pada pemilih kalender tidak sinkron saat zona waktu klien bukan Asia/Jakarta.
- [ ] **BUG-T2-21 [P2]**: Tombol filter kalender "Semua Layanan" tidak me-reset pilihan staf.
- [ ] **BUG-T2-22 [P3]**: Ikon kategori bawaan selalu menggunakan SVG fallback generik.
- [ ] **BUG-T2-23 [P3]**: Indikator warna status layanan (hijau/merah) tidak ramah buta warna.
- [ ] **BUG-T2-24 [P3]**: Tooltip waktu slot kalender terpotong pada batas grid kanan layar.
- [ ] **BUG-T2-25 [P3]**: Ketiadaan animasi transisi halus saat drawer kalender dibuka.
- [ ] **BUG-T2-26 [P3]**: Badge jumlah slot tersisa pada sel kalender berkedip saat refetch AJAX.

### From BQ-063 (full details in `done/BQ-063-audit-stage-3-p1-p3.md`)

- [ ] **BUG-T3-08 [P2]**: Filter kategori berlayanan kosong menampilkan halaman putih tanpa empty state ([program-selection.blade.php#L239](file:///c:/laragon/www/bookqu/resources/views/customer/booking/program-selection.blade.php#L239)).
- [ ] **BUG-T3-09 [P2]**: Touch target tombol header mobile ("Kembali" & "Bantuan") hanya 28px, melanggar standar WCAG 2.1 (minimal 44px).
- [ ] **BUG-T3-10 [P2]**: Data nama dan nomor telepon pemesan terhapus saat terjadi tabrakan pemesanan slot jadwal (slot collision).
- [ ] **BUG-T3-11 [P2]**: Mode simulasi pengujian cepat (`?simulate=1`) gagal di Step 4 karena mencoba mencari jadwal dummy di database.
- [ ] **BUG-T3-12 [P2]**: Pemilihan tanggal kalender pada layar mobile 360px mengalami teks terpotong ("12..." alih-alih "12 slot").
- [ ] **BUG-T3-13 [P2]**: Tautan Google Calendar di halaman invoice tidak menyertakan parameter `&ctz=Asia/Jakarta` sehingga berisiko meleset 1-2 jam di luar WIB.
- [ ] **BUG-T3-14 [P2]**: Tombol kembali header di Step 5 membiarkan slot terkunci 15 menit tanpa membatalkan order.
- [ ] **BUG-T3-15 [P2]**: Pengecualian domain utama di `TenantMiddleware.php` melewatkan domain produksi `bookqu.my.id`.
- [ ] **BUG-T3-16 [P2]**: Ejeksi sesi checkout yang kedaluwarsa terjadi tanpa menampilkan flash error informatif ke pelanggan.
- [ ] **BUG-T3-17 [P2]**: Karakter kutip pada nama layanan di Step 1 memicu error parser HTML Alpine.js.
- [ ] **BUG-T3-18 [P3]**: Tombol "Pesan Sesi Baru" dan "Kembali ke Beranda" diletakkan berdampingan dengan URL target identik.
- [ ] **BUG-T3-19 [P3]**: Inkonsistensi validasi panjang nama pemesan: client 3 karakter, backend menerima 1 karakter.
- [ ] **BUG-T3-20 [P3]**: Ketiadaan fallback penanganan Clipboard API pada browser non-HTTPS.

### From BQ-064 (full details in `done/BQ-064-audit-stage-4-p1-p3.md`)

- [ ] **BUG-T4-10 [P2]**: Midtrans gateway keliru membaca konfigurasi SingaPay `config('payment.drivers.singapay.expiry_minutes')` ([MidtransPaymentGateway.php:315](file:///c:/laragon/www/bookqu/app/Infrastructure/Payments/Midtrans/MidtransPaymentGateway.php#L315)).
- [ ] **BUG-T4-11 [P2]**: Tombol "Periksa Status Pembayaran" gagal hening (silent fail) saat menerima respons HTTP 403 atau 500 tanpa memperbarui antarmuka pengguna.
- [ ] **BUG-T4-12 [P2]**: Variabel `$cacheCleared` pada artisan command `ExpireBookingPayments` tidak pernah diisi di dalam loop sehingga selalu melaporkan "0 slots cleared".
- [ ] **BUG-T4-13 [P2]**: Tombol navigasi "Buat Reservasi Baru" dan "Kembali ke Pemesanan" pada layar gagal mengarah ke target yang sama.
- [ ] **BUG-T4-14 [P2]**: Ketiadaan penanganan penutupan pop-up Snap Midtrans saat pengguna mengklik tanda silang tanpa menyelesaikan pembayaran.
- [ ] **BUG-T4-15 [P3]**: Format waktu kedaluwarsa tidak menyertakan penanda zona waktu WIB/WITA/WIT.
- [ ] **BUG-T4-16 [P3]**: Ikon salin nomor virtual account tidak memberikan transisi animasi centang hijau saat berhasil disalin.

### From BQ-065 (full details in `done/BQ-065-audit-stage-5-p1-p3.md`)

- [ ] BUG-T5-12: Ketiadaan Modul Ulasan (Review) pada Portal Payment Group
- [ ] **BUG-T5-13 [P2]**: Label tombol modal konfirmasi pembatalan booking berbunyi "Ya, Hapus" alih-alih "Ya, Batalkan Booking" ([confirm-modal.blade.php#L18](file:///c:/laragon/www/bookqu/resources/views/components/owner/confirm-modal.blade.php#L18)).
- [ ] **BUG-T5-14 [P2]**: Ketiadaan pencatatan `BookingLog` saat status diubah menjadi paid / completed di `UpdateBookingStatus.php`.
- [ ] **BUG-T5-15 [P2]**: Ketiadaan audit log dan email konfirmasi saat walk-in booking dibuat di `CreateWalkInBooking.php`.
- [ ] **BUG-T5-16 [P2]**: Inkonsistensi status multi-slot saat owner mengubah status salah satu slot tanpa menyinkronkan slot pasangannya.
- [ ] **BUG-T5-17 [P2]**: Hilangnya pilihan tanggal & slot customer reschedule saat validasi server gagal (`old()` tidak di-bind).
- [ ] **BUG-T5-18 [P2]**: Hero banner payment group tetap menampilkan badge hijau "Reservasi Dikonfirmasi" pada sesi yang sudah selesai/batal.
- [ ] **BUG-T5-19 [P2]**: Hilangnya flash message sukses/error pada kalender saat walk-in booking disimpan.
- [ ] **BUG-T5-20 [P2]**: Rating bintang form review direset ke 0 saat validasi server gagal karena melebihi batas karakter.
- [ ] **BUG-T5-21 [P2]**: Ketiadaan pesan error inline di dalam form ulasan `review-section.blade.php`.
- [ ] **BUG-T5-22 [P2]**: Teks statis "Pembayaran Berhasil & Terverifikasi" tetap terpampang pada booking pending dan batal di `ticket-card.blade.php`.
- [ ] **BUG-T5-23 [P3]**: Peringatan batas waktu reschedule/batal muncul membingungkan pada sesi yang sudah berstatus selesai atau batal.
- [ ] **BUG-T5-24 [P3]**: Ketiadaan kartu informasi refund dan timeline aktivitas pada `payment-show.blade.php`.
- [ ] **BUG-T5-25 [P3]**: Pesan error pembatalan tersembunyi di belakang backdrop gelap modal di `show.blade.php`.
- [ ] **BUG-T5-26 [P3]**: Filter tab "Today" pada owner bookings mengabaikan kombinasi filter status lainnya.
- [ ] **BUG-T5-27 [P3]**: Redirect pasca pembatalan melempar pelanggan keluar dari portal payment group ke legacy single view.
- [ ] **BUG-T5-28 [P3]**: Silent error pada fetch slot waktu reschedule customer saat terjadi HTTP 4xx.
- [ ] **BUG-T5-29 [P3]**: Form reschedule owner menggunakan full-page reload alih-alih AJAX, menutup modal saat terjadi error.
- [ ] **BUG-T5-30 [P3]**: Tombol cetak tiket individual tidak menyembunyikan header portal pada mode cetak.

### From BQ-066 (full details in `done/BQ-066-audit-stage-6-p1-p3.md`)

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

## Evidence (fill when done)
