# Dokumentasi Audit Cacat: Tahap 2
## Owner Core Operations: Services, Kategori, Jadwal & Kalender Interaktif

- **Lingkup**: Halaman Manajemen Layanan (`programs.blade.php`, `modal-add-program.blade.php`, `modal-edit-program.blade.php`), Kategori (`categories.blade.php`), Jadwal Massal (`schedule.blade.php`), Kalender Interaktif Operasional (`calendar.blade.php`, partials).
- **Total Cacat Terverifikasi**: 26 Cacat (4 P0, 7 P1, 10 P2, 5 P3).

---

### 1. Cacat Prioritas P0 (Kritis / Fatal)

#### BUG-T2-01: Crash Global Alpine.js Akibat Karakter Kutip pada Flash Message Toast
- **Lokasi Berkas**: [`resources/views/components/owner/toast.blade.php:14-19`](file:///c:/laragon/www/bookqu/resources/views/components/owner/toast.blade.php#L14-L19)
- **Deskripsi & Dampak**:
  Pemanggilan `add('success', '{{ addslashes(session('sukses')) }}')` di dalam atribut HTML `x-init="..."` menghasilkan `\"`. Tanda kutip ganda ini memecah pembungkus atribut HTML `x-init="add('success', \"...\")"`. Peramban melempar `Uncaught SyntaxError` saat parsing DOM, yang menyebabkan **Alpine.js gagal terinisialisasi secara global di seluruh halaman dashboard owner**.
- **Akar Masalah**:
  ```blade
  x-init="
      @if(session('sukses'))
          add('success', '{{ addslashes(session('sukses')) }}');
      @endif
  "
  ```
- **Rekomendasi Perbaikan**:
  Ganti dengan directive bawaan Laravel Blade `@js(...)`:
  ```blade
  x-init="
      @if(session('sukses'))
          add('success', @js(session('sukses')));
      @endif
  "
  ```

---

#### BUG-T2-02: Tabrakan Delimiter Kutip `@click` pada Kartu Layanan, Kategori, dan Jadwal
- **Lokasi Berkas**: 
  - [`resources/views/owner/programs.blade.php:124`](file:///c:/laragon/www/bookqu/resources/views/owner/programs.blade.php#L124)
  - [`resources/views/owner/categories.blade.php:88`](file:///c:/laragon/www/bookqu/resources/views/owner/categories.blade.php#L88)
  - [`resources/views/owner/schedule.blade.php:165`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L165)
- **Deskripsi & Dampak**:
  Event klik menggunakan pola `@click='editProgram(@json($program))'`. Jika nama program atau kategori memiliki tanda petik tunggal (misal: *"Men's Cut"* atau *"D'Salon"*), string JSON dipotong sebelum waktunya, memicu JavaScript syntax error fatal saat tombol "Edit" diklik.
- **Rekomendasi Perbaikan**:
  Gunakan pemanggilan fungsi aman dengan encoding hex:
  ```blade
  x-on:click="editProgram({{ json_encode($program, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }})"
  ```

---

#### BUG-T2-03: Relasi Staff & Sumber Daya Tidak Pernah Terlepas saat Checkbox Dikosongkan (Relationship Desync)
- **Lokasi Berkas**: 
  - [`resources/views/components/owner/modal-edit-program.blade.php:180-210`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-edit-program.blade.php#L180-L210)
  - [`app/Actions/Program/UpdateService.php:85`](file:///c:/laragon/www/bookqu/app/Actions/Program/UpdateService.php#L85)
- **Deskripsi & Dampak**:
  Ketika owner mengedit layanan dan menghapus centang pada seluruh staf atau sumber daya, peramban tidak mengirimkan field checkbox yang kosong dalam payload HTTP POST. Di backend, `UpdateService` memeriksa `if (array_key_exists('staff_ids', $data))`. Karena key tidak ada, sinkronisasi `$service->staff()->sync([])` dilewati. **Owner tidak bisa melepaskan staf atau resource dari layanan**.
- **Akar Masalah**:
  ```php
  // UpdateService.php:
  if (array_key_exists('staff_ids', $data)) {
      $service->staff()->sync($data['staff_ids'] ?? []);
  }
  ```
- **Rekomendasi Perbaikan**:
  Tambahkan penggabungan nilai default array kosong di `UpdateServiceRequest`:
  ```php
  protected function prepareForValidation(): void
  {
      $this->merge([
          'staff_ids' => $this->input('staff_ids', []),
          'resource_ids' => $this->input('resource_ids', []),
      ]);
  }
  ```

---

#### BUG-T2-04: Calendar Redirect Trap: Menghapus Slot Waktu Melemparkan Owner Keluar dari Kalender
- **Lokasi Berkas**: [`app/Http/Controllers/Owner/OwnerScheduleController.php:182`](file:///c:/laragon/www/bookqu/app/Http/Controllers/Owner/OwnerScheduleController.php#L182)
- **Deskripsi & Dampak**:
  Tombol hapus slot jadwal di dalam drawer Kalender Operasional (`/owner/calendar`) mengirim request DELETE ke `/owner/schedule/slots/{id}`. Controller meng-hardcode:
  `return redirect('/owner/schedule')->with('sukses', 'Slot berhasil dihapus.');`
  Owner terlempar keluar dari kalender ke halaman jadwal reguler, kehilangan tanggal dan minggu kalender yang sedang dipantau.
- **Rekomendasi Perbaikan**:
  Ubah redirect menjadi kembali ke halaman pemanggil:
  ```php
  return redirect()->back()->with('sukses', 'Slot berhasil dihapus.');
  ```

---

### 2. Cacat Prioritas P1 (Tinggi / Broken Functionality)

#### BUG-T2-05: Slot Waktu Tersedia Tertutup Total oleh Booking pada Desktop Week View
- **Lokasi Berkas**: [`resources/views/owner/partials/calendar/week-view.blade.php:140-165`](file:///c:/laragon/www/bookqu/resources/views/owner/partials/calendar/week-view.blade.php#L140-L165)
- **Deskripsi**: Logika rendering menggunakan `@elseif` setelah iterasi slot booking. Jika suatu slot jam memiliki 1 booking aktif namun kapasitas slot lainnya masih tersedia, slot kosong tersebut disembunyikan dari kalender desktop.
- **Rekomendasi Perbaikan**: Pisahkan blok rendering kartu booking dan kartu slot kosong menjadi dua iterasi terpisah tanpa `@elseif`.

#### BUG-T2-06: Form Tambah Jadwal Massal Kehilangan Hari Terpilih saat Validasi Gagal
- **Lokasi Berkas**: [`resources/views/owner/schedule.blade.php:88-142`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L88-L142)
- **Deskripsi**: Checkbox hari (Senin–Minggu) tidak mengecek `in_array($hari, old('hari', []))`. Validasi error (misal jam mulai > jam selesai) mengosongkan seluruh hari yang telah dipilih owner.
- **Rekomendasi Perbaikan**: Pasang atribut `:checked="in_array($hari, old('hari', []))"`.

#### BUG-T2-07: Toggle Status Aktif Layanan Tidak Menampilkan Indikator Loading
- **Lokasi Berkas**: [`resources/views/owner/programs.blade.php:160-185`](file:///c:/laragon/www/bookqu/resources/views/owner/programs.blade.php#L160-L185)
- **Deskripsi**: Toggle saklar status aktif/nonaktif layanan dieksekusi via AJAX fetch, namun tidak mengunci input saklar selama request berlangsung. Klik berulang memicu *state desynchronization*.
- **Rekomendasi Perbaikan**: Tambahkan state `isToggling: false` dan `:disabled="isToggling"`.

#### BUG-T2-08: Ketiadaan Dialog Konfirmasi Dampak Hapus Kategori pada Layanan
- **Lokasi Berkas**: [`resources/views/owner/categories.blade.php:145`](file:///c:/laragon/www/bookqu/resources/views/owner/categories.blade.php#L145)
- **Deskripsi**: Menghapus kategori langsung mengeksekusi DELETE tanpa menginformasikan bahwa layanan yang terafiliasi akan menjadi *tanpa kategori*.

#### BUG-T2-09: Validasi Format 24-Jam Tidak Ditegakkan pada Time Picker Jadwal
- **Lokasi Berkas**: [`resources/views/owner/schedule.blade.php:120-135`](file:///c:/laragon/www/bookqu/resources/views/owner/schedule.blade.php#L120-L135)
- **Deskripsi**: Input jam tidak memiliki regex mask client-side, memungkinkan input invalid seperti `25:00` yang memicu 422 Unprocessable Entity dari server.

#### BUG-T2-10: Error Penanganan AJAX Kalender Hanya Menampilkan Objek Kosong
- **Lokasi Berkas**: [`resources/views/owner/calendar.blade.php:210-230`](file:///c:/laragon/www/bookqu/resources/views/owner/calendar.blade.php#L210-L230)
- **Deskripsi**: Ketika fetch data kalender gagal (500 atau 403), script hanya menutup loading tanpa memberikan alert banner toast error ke owner.

#### BUG-T2-11: Modal Tambah Layanan Tidak Memiliki Focus Trap untuk Aksesibilitas
- **Lokasi Berkas**: [`resources/views/components/owner/modal-add-program.blade.php:10-40`](file:///c:/laragon/www/bookqu/resources/views/components/owner/modal-add-program.blade.php#L10-L40)
- **Deskripsi**: Menekan tombol `Tab` pada modal dapat mengalirkan fokus ke elemen di latar belakang dashboard yang tertutup overlay.

---

### 3. Cacat Prioritas P2 & P3 (Sedang & Rendah)

- **BUG-T2-12 [P2]**: Tombol "Batal" pada modal edit layanan tidak me-reset data yang telah diubah pengguna.
- **BUG-T2-13 [P2]**: Pencarian layanan tidak menyaring berdasarkan nama staf yang bertugas.
- **BUG-T2-14 [P2]**: Format mata uang pada input harga layanan tidak otomatis menyisipkan pemisah ribuan.
- **BUG-T2-15 [P2]**: Filter kategori pada halaman program tidak mencerminkan URL query string.
- **BUG-T2-16 [P2]**: Drawer kalender mobile menutupi tombol navigasi minggu/bulan.
- **BUG-T2-17 [P2]**: Durasi layanan dalam menit tidak memiliki validasi kelipatan 5 atau 15 menit.
- **BUG-T2-18 [P2]**: Empty state jadwal kalender tidak menyertakan tombol pintas "Buat Jadwal Baru".
- **BUG-T2-19 [P2]**: Tab hari pada halaman jadwal tidak mengingat tab terakhir yang dibuka setelah refresh.
- **BUG-T2-20 [P2]**: Tanggal pada pemilih kalender tidak sinkron saat zona waktu klien bukan Asia/Jakarta.
- **BUG-T2-21 [P2]**: Tombol filter kalender "Semua Layanan" tidak me-reset pilihan staf.
- **BUG-T2-22 [P3]**: Ikon kategori bawaan selalu menggunakan SVG fallback generik.
- **BUG-T2-23 [P3]**: Indikator warna status layanan (hijau/merah) tidak ramah buta warna.
- **BUG-T2-24 [P3]**: Tooltip waktu slot kalender terpotong pada batas grid kanan layar.
- **BUG-T2-25 [P3]**: Ketiadaan animasi transisi halus saat drawer kalender dibuka.
- **BUG-T2-26 [P3]**: Badge jumlah slot tersisa pada sel kalender berkedip saat refetch AJAX.
