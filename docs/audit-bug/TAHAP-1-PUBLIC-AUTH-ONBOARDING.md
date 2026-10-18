# Dokumentasi Audit Cacat: Tahap 1
## Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding

- **Lingkup**: Halaman Publik (`welcome.blade.php`, `privacy-policy.blade.php`, `terms-conditions.blade.php`), Modul Autentikasi (`login.blade.php`, `register.blade.php`, `verify-email.blade.php`), dan Onboarding Owner Profil (`modal-complete-profile.blade.php`).
- **Total Cacat Terverifikasi**: 21 Cacat (3 P0, 5 P1, 8 P2, 5 P3).

---

### 1. Cacat Prioritas P0 (Kritis / Fatal)

#### BUG-T1-01: Tombol "Lupa Password?" pada Login Mengarah ke `#` (Dead Link)
- **Lokasi Berkas**: [`resources/views/auth/login.blade.php:73`](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L73)
- **Deskripsi & Dampak**:
  Tautan berbunyi *"Lupa kata sandi?"* menggunakan atribut statis `<a href="#" class="...">Lupa password?</a>`. Tidak ada route Laravel (`password.request`), controller, maupun form modal yang menanganinya. Pengguna yang lupa kata sandi terkunci permanen di luar akun mereka tanpa ada alur pemulihan kata sandi mandiri.
- **Akar Masalah**:
  ```blade
  <div class="text-right">
      <a href="#" class="text-xs font-semibold text-bq-primary hover:underline">
          Lupa password?
      </a>
  </div>
  ```
- **Rekomendasi Perbaikan**:
  Sediakan route pemulihan sandi (atau sembunyikan sementara jika fitur belum rilis sesuai ADR-0002) atau arahkan ke bantuan dukungan admin:
  ```blade
  <a href="{{ route('password.request') }}" class="text-xs font-semibold text-bq-primary hover:underline">
      Lupa password?
  </a>
  ```

---

#### BUG-T1-02: Jebakan Redirect Loop pada Tombol "Masuk ke Dashboard Bisnis"
- **Lokasi Berkas**: [`resources/views/welcome.blade.php:57`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L57)
- **Deskripsi & Dampak**:
  Ketika pengguna yang login belum memverifikasi email mengklik tombol *"Masuk ke Dashboard"*, tautan mengarahkan ke `route('owner.dashboard')`. Middleware `verified` mencegat dan me-redirect paksa pengguna ke `/verify-email`. Jika pengguna mengklik logo di halaman verifikasi email, mereka kembali ke beranda dan siklus berulang tanpa kejelasan.
- **Akar Masalah**:
  Kondisi `@auth` pada header landing page mengabaikan status verifikasi:
  ```blade
  @auth
      <a href="{{ route('owner.dashboard') }}" class="craft-btn ...">Dashboard</a>
  @endauth
  ```
- **Rekomendasi Perbaikan**:
  Arahkan secara kondisional:
  ```blade
  @auth
      @if(auth()->user()->hasVerifiedEmail())
          <a href="{{ route('owner.dashboard') }}" class="craft-btn ...">Buka Dashboard</a>
      @else
          <a href="{{ route('verification.notice') }}" class="craft-btn ...">Verifikasi Email Anda</a>
      @endif
  @endauth
  ```

---

#### BUG-T1-03: Ketiadaan Proteksi CSRF & Token Expired Handling pada Tombol Logout di Notice Verifikasi
- **Lokasi Berkas**: [`resources/views/auth/verify-email.blade.php:48`](file:///c:/laragon/www/bookqu/resources/views/auth/verify-email.blade.php#L48)
- **Deskripsi & Dampak**:
  Tombol Logout di halaman verifikasi email sering menghasilkan error `419 Page Expired` jika tab ditinggalkan terbuka lebih dari masa kedaluwarsa sesi CSRF. Tidak ada mekanisme regenerasi token otomatis.

---

### 2. Cacat Prioritas P1 (Tinggi / Broken Functionality)

#### BUG-T1-04: Ketiadaan Hamburger Menu & Navigasi Mobile pada Halaman Publik
- **Lokasi Berkas**: [`resources/views/welcome.blade.php:24-44`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L24-L44)
- **Deskripsi**: Navigasi header dibungkus dengan `hidden md:flex`. Pada viewport ponsel (< 768px), seluruh tautan fitur, solusi, harga, dan FAQ lenyap tanpa tombol hamburger atau drawer menu.
- **Rekomendasi Perbaikan**: Tambahkan Alpine.js mobile sheet drawer `<div x-data="{ mobileNavOpen: false }">` dengan tombol hamburger.

#### BUG-T1-05: Kehilangan Seluruh Data Input Modal Onboarding Profil saat Validasi Gagal
- **Lokasi Berkas**: [`resources/views/components/owner/modal-complete-profile.blade.php:45-120`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-complete-profile.blade.php#L45-L120)
- **Deskripsi**: Tag `<input name="namabisnis">`, `<input name="nomorhp">`, dan `<textarea name="alamat">` tidak membaca `value="{{ old(...) }}"`. Jika nomor telepon invalid, modal tertutup dan seluruh ketikan owner hilang.
- **Rekomendasi Perbaikan**:
  Pasang helper `value="{{ old('namabisnis', auth()->user()->tenant?->namabisnis ?? '') }}"` dan inisialisasi state Alpine: `open: {{ $errors->any() ? 'true' : 'false' }}`.

#### BUG-T1-06: Kerentanan Reverse Tabnabbing pada Tautan Eksternal & WhatsApp
- **Lokasi Berkas**: [`resources/views/welcome.blade.php:542`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L542)
- **Deskripsi**: Tautan `target="_blank"` ke WhatsApp dan media sosial eksternal tidak menyertakan `rel="noopener noreferrer"`.
- **Rekomendasi Perbaikan**: Wajib sertakan `target="_blank" rel="noopener noreferrer"`.

#### BUG-T1-07: Tautan Syarat & Ketentuan pada Register Membuka di Tab Sama
- **Lokasi Berkas**: [`resources/views/auth/register.blade.php:124`](file:///c:/laragon/www/bookqu/resources/views/auth/register.blade.php#L124)
- **Deskripsi**: Calon pengguna yang mengklik tautan Syarat & Ketentuan saat sedang mengisi formulir pendaftaran berpindah halaman di tab yang sama. Seluruh isian form registrasi hilang saat tombol back ditekan.
- **Rekomendasi Perbaikan**: Tambahkan atribut `target="_blank" rel="noopener noreferrer"`.

#### BUG-T1-08: Flash Status Session Message Hilang pada Tampilan Login
- **Lokasi Berkas**: [`resources/views/auth/login.blade.php:20`](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L20)
- **Deskripsi**: Pesan redirect dari verifikasi email sukses (`session('status')`) tidak memiliki banner penampil di halaman login, sehingga pengguna tidak tahu apakah akun mereka sudah aktif atau belum.

---

### 3. Cacat Prioritas P2 & P3 (Sedang & Rendah)

- **BUG-T1-09 [P2]**: Seluruh link footer kebijakan dan panduan mengarah ke placeholder `href="#"` ([welcome.blade.php#L493](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L493)).
- **BUG-T1-10 [P2]**: Ketiadaan toggle mata intip (show/hide password) pada form login dan register ([login.blade.php#L60](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L60)).
- **BUG-T1-11 [P2]**: Tombol "Kirim Ulang Email Verifikasi" tidak memiliki timer throttle cooldown 60 detik ([verify-email.blade.php#L38](file:///c:/laragon/www/bookqu/resources/views/auth/verify-email.blade.php#L38)).
- **BUG-T1-12 [P2]**: Nilai checkbox "Ingat Saya" (Remember Me) tidak tersimpan saat login gagal.
- **BUG-T1-13 [P2]**: Normalisasi input nomor WhatsApp pada modal onboarding profil tidak menangani awalan `+62` atau spasi.
- **BUG-T1-14 [P2]**: Scroll snapping tersendat pada navigasi anchor section landing page.
- **BUG-T1-15 [P2]**: Ketiadaan indikator fokus keyboard (focus-visible ring) pada elemen interaktif header.
- **BUG-T1-16 [P2]**: Tombol submit form login/register tidak memiliki indikator loading spinner saat ditekan.
- **BUG-T1-17 [P3]**: Footer copyright tahun di-hardcode `2024` alih-alih `{{ date('Y') }}`.
- **BUG-T1-18 [P3]**: Kontras rasio teks subjudul landing page berada di bawah standar WCAG AA (4.5:1).
- **BUG-T1-19 [P3]**: Transisi warna tema pada mode gelap/terang mengalami flickering.
- **BUG-T1-20 [P3]**: Tombol "Kembali ke Beranda" pada halaman syarat ketentuan tidak memiliki ikon navigasi.
- **BUG-T1-21 [P3]**: Missing meta tag `theme-color` untuk bilah peramban mobile.
