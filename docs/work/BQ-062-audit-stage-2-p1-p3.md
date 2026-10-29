---
id: BQ-062
title: Audit stage 2 P1-P3 defects (Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif)
type: bug-fix
status: proposed
priority: P1
phase: 4
domain: catalog
requirements: []
invariants: []
launch-blocker: false
needs-lead-approval: false
depends-on: [BQ-039]
source: docs/audit-bug/TAHAP-2-OWNER-CORE-OPERATIONS.md at a68ffe8
---

# BQ-062: Audit stage 2 — P1, P2 and P3 defects

Migrated verbatim from TAHAP-2-OWNER-CORE-OPERATIONS.md (Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif). Status in the current code is unverified (BQ-039). P1 defects that touch a core flow are launch-relevant; promote any of them to its own item when work starts.

## P1 checklist

- [ ] BUG-T2-05: Slot Waktu Tersedia Tertutup Total oleh Booking pada Desktop Week View
- [ ] BUG-T2-06: Form Tambah Jadwal Massal Kehilangan Hari Terpilih saat Validasi Gagal
- [ ] BUG-T2-07: Toggle Status Aktif Layanan Tidak Menampilkan Indikator Loading
- [ ] BUG-T2-08: Ketiadaan Dialog Konfirmasi Dampak Hapus Kategori pada Layanan
- [ ] BUG-T2-09: Validasi Format 24-Jam Tidak Ditegakkan pada Time Picker Jadwal
- [ ] BUG-T2-10: Error Penanganan AJAX Kalender Hanya Menampilkan Objek Kosong
- [ ] BUG-T2-11: Modal Tambah Layanan Tidak Memiliki Focus Trap untuk Aksesibilitas

## P2 / P3 checklist

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

## P1 details

### BUG-T2-05: Slot Waktu Tersedia Tertutup Total oleh Booking pada Desktop Week View

- **Lokasi Berkas**: [`resources/views/owner/partials/calendar/week-view.blade.php:140-165`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/calendar/week-view.blade.php#L140-L165)
- **Deskripsi**: Logika rendering menggunakan `@elseif` setelah iterasi slot booking. Jika suatu slot jam memiliki 1 booking aktif namun kapasitas slot lainnya masih tersedia, slot kosong tersebut disembunyikan dari kalender desktop.
- **Rekomendasi Perbaikan**: Pisahkan blok rendering kartu booking dan kartu slot kosong menjadi dua iterasi terpisah tanpa `@elseif`.

### BUG-T2-06: Form Tambah Jadwal Massal Kehilangan Hari Terpilih saat Validasi Gagal

- **Lokasi Berkas**: [`resources/views/owner/schedule.blade.php:88-142`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L88-L142)
- **Deskripsi**: Checkbox hari (Senin–Minggu) tidak mengecek `in_array($hari, old('hari', []))`. Validasi error (misal jam mulai > jam selesai) mengosongkan seluruh hari yang telah dipilih owner.
- **Rekomendasi Perbaikan**: Pasang atribut `:checked="in_array($hari, old('hari', []))"`.

### BUG-T2-07: Toggle Status Aktif Layanan Tidak Menampilkan Indikator Loading

- **Lokasi Berkas**: [`resources/views/owner/programs.blade.php:160-185`](file:///c:/laragon/www/bookqu/resources/views/owner/programs.blade.php#L160-L185)
- **Deskripsi**: Toggle saklar status aktif/nonaktif layanan dieksekusi via AJAX fetch, namun tidak mengunci input saklar selama request berlangsung. Klik berulang memicu *state desynchronization*.
- **Rekomendasi Perbaikan**: Tambahkan state `isToggling: false` dan `:disabled="isToggling"`.

### BUG-T2-08: Ketiadaan Dialog Konfirmasi Dampak Hapus Kategori pada Layanan

- **Lokasi Berkas**: [`resources/views/owner/categories.blade.php:145`](file:///c:/laragon/www/bookqu/resources/views/owner/categories.blade.php#L145)
- **Deskripsi**: Menghapus kategori langsung mengeksekusi DELETE tanpa menginformasikan bahwa layanan yang terafiliasi akan menjadi *tanpa kategori*.

### BUG-T2-09: Validasi Format 24-Jam Tidak Ditegakkan pada Time Picker Jadwal

- **Lokasi Berkas**: [`resources/views/owner/schedule.blade.php:120-135`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L120-L135)
- **Deskripsi**: Input jam tidak memiliki regex mask client-side, memungkinkan input invalid seperti `25:00` yang memicu 422 Unprocessable Entity dari server.

### BUG-T2-10: Error Penanganan AJAX Kalender Hanya Menampilkan Objek Kosong

- **Lokasi Berkas**: [`resources/views/owner/calendar.blade.php:210-230`](file:///c:/laragon/www/bookqu/resources/views/owner/calendar.blade.php#L210-L230)
- **Deskripsi**: Ketika fetch data kalender gagal (500 atau 403), script hanya menutup loading tanpa memberikan alert banner toast error ke owner.

### BUG-T2-11: Modal Tambah Layanan Tidak Memiliki Focus Trap untuk Aksesibilitas

- **Lokasi Berkas**: [`resources/views/components/owner/modal-add-program.blade.php:10-40`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-add-program.blade.php#L10-L40)
- **Deskripsi**: Menekan tombol `Tab` pada modal dapat mengalirkan fokus ke elemen di latar belakang dashboard yang tertutup overlay.

## Evidence

_Fill in when done._
