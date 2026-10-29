---
id: BQ-061
title: Audit stage 1 P1-P3 defects (Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: auth
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-1-PUBLIC-AUTH-ONBOARDING.md at a68ffe8
---

# BQ-061: Audit stage 1 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-1-PUBLIC-AUTH-ONBOARDING.md (Guest Public Pages, Landing Page, Legal, Auth & Owner Onboarding). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

## P1 checklist

- [ ] BUG-T1-04: Ketiadaan Hamburger Menu & Navigasi Mobile pada Halaman Publik
- [ ] BUG-T1-05: Kehilangan Seluruh Data Input Modal Onboarding Profil saat Validasi Gagal
- [ ] BUG-T1-06: Kerentanan Reverse Tabnabbing pada Tautan Eksternal & WhatsApp
- [ ] BUG-T1-07: Tautan Syarat & Ketentuan pada Register Membuka di Tab Sama
- [ ] BUG-T1-08: Flash Status Session Message Hilang pada Tampilan Login

## P2 / P3 checklist

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

## P1 details

### BUG-T1-04: Ketiadaan Hamburger Menu & Navigasi Mobile pada Halaman Publik

- **Lokasi Berkas**: [`resources/views/welcome.blade.php:24-44`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L24-L44)
- **Deskripsi**: Navigasi header dibungkus dengan `hidden md:flex`. Pada viewport ponsel (< 768px), seluruh tautan fitur, solusi, harga, dan FAQ lenyap tanpa tombol hamburger atau drawer menu.
- **Rekomendasi Perbaikan**: Tambahkan Alpine.js mobile sheet drawer `<div x-data="{ mobileNavOpen: false }">` dengan tombol hamburger.

### BUG-T1-05: Kehilangan Seluruh Data Input Modal Onboarding Profil saat Validasi Gagal

- **Lokasi Berkas**: [`resources/views/components/owner/modal-complete-profile.blade.php:45-120`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-complete-profile.blade.php#L45-L120)
- **Deskripsi**: Tag `<input name="namabisnis">`, `<input name="nomorhp">`, dan `<textarea name="alamat">` tidak membaca `value="{{ old(...) }}"`. Jika nomor telepon invalid, modal tertutup dan seluruh ketikan owner hilang.
- **Rekomendasi Perbaikan**:
  Pasang helper `value="{{ old('namabisnis', auth()->user()->tenant?->namabisnis ?? '') }}"` dan inisialisasi state Alpine: `open: {{ $errors->any() ? 'true' : 'false' }}`.

### BUG-T1-06: Kerentanan Reverse Tabnabbing pada Tautan Eksternal & WhatsApp

- **Lokasi Berkas**: [`resources/views/welcome.blade.php:542`](file:///c:/laragon/www/bookqu/resources/views/welcome.blade.php#L542)
- **Deskripsi**: Tautan `target="_blank"` ke WhatsApp dan media sosial eksternal tidak menyertakan `rel="noopener noreferrer"`.
- **Rekomendasi Perbaikan**: Wajib sertakan `target="_blank" rel="noopener noreferrer"`.

### BUG-T1-07: Tautan Syarat & Ketentuan pada Register Membuka di Tab Sama

- **Lokasi Berkas**: [`resources/views/auth/register.blade.php:124`](file:///c:/laragon/www/bookqu/resources/views/auth/register.blade.php#L124)
- **Deskripsi**: Calon pengguna yang mengklik tautan Syarat & Ketentuan saat sedang mengisi formulir pendaftaran berpindah halaman di tab yang sama. Seluruh isian form registrasi hilang saat tombol back ditekan.
- **Rekomendasi Perbaikan**: Tambahkan atribut `target="_blank" rel="noopener noreferrer"`.

### BUG-T1-08: Flash Status Session Message Hilang pada Tampilan Login

- **Lokasi Berkas**: [`resources/views/auth/login.blade.php:20`](file:///c:/laragon/www/bookqu/resources/views/auth/login.blade.php#L20)
- **Deskripsi**: Pesan redirect dari verifikasi email sukses (`session('status')`) tidak memiliki banner penampil di halaman login, sehingga pengguna tidak tahu apakah akun mereka sudah aktif atau belum.

## Evidence

_Fill in when done._
