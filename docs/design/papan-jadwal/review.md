# Review portal owner BookQu

Tanggal: 16 Nov 2026. Bahan: `INDEX.md`, `data-inventory.md`, `docs/owner-portal.md` §8, dan 44 dari 49 screenshot (tidak dibuka: 15 Kategori, 27 Rekening, 38 Galeri, 40 Domain, 45 Akun). Tidak ada kode atau browser yang dibuka.

> **Koreksi 16 Nov 2026 (setelah audit kode, lihat `plan.md` bagian 2).** Slot otomatis dari jam buka, saran otomatis di Laporan, dan pengingat WhatsApp sudah diputuskan ditunda sampai setelah rilis (D-021); di dokumen ini ketiganya adalah pengajuan ulang, bukan usulan baru. Temuan "satu masalah diumumkan empat kali" berlabel B, bukan A. Semua temuan belum dicek terhadap kode.

**Cara membaca label**

- **A** = tampilan saja. **B** = mengubah isi halaman §8 (perlu Product Lead). **C** = fitur atau data baru (perlu Product Lead; skema atau aturan domain juga Tech Lead).
- **D / K / U** = dampak / keyakinan / besar kerja. Nilai: T (tinggi), S (sedang), R (rendah). Besar kerja: K (kecil, ≤ 1 hari), S (sedang, beberapa hari), B (besar, lebih dari seminggu).
- **Fakta** = terlihat di bahan. **Asumsi** = dugaan saya, ditandai.

---

## 1. Ringkasan untuk Brammy

Portal owner sudah lengkap dan rapi. Semua halaman ada, warnanya konsisten, dan tampilan HP untuk halaman harian sudah berfungsi. Masalahnya bukan di warna. Masalahnya: portal masih terasa seperti **kumpulan formulir**, belum seperti **asisten yang memberi tahu pemilik apa yang terjadi dan apa yang harus dilakukan**. Lima hal ini paling layak dikerjakan:

**1. Slot terbuka otomatis dari jam buka.**
Sekarang pemilik harus membuat slot sendiri berulang-ulang. Kalau lupa, pelanggan tidak bisa booking dan pemilik tidak sadar. Di bisnis demo, 10 dari 10 layanan tidak punya slot untuk 7 hari ke depan, dan peringatannya muncul di empat tempat sekaligus. Peringatan sebanyak itu menandakan pekerjaan ini seharusnya dikerjakan sistem. Ini risiko terbesar bagi pendapatan pemilik, dan juga bagi langganan BookQu.

**2. Beranda harian jangan pernah tertutup panduan.**
Bisnis demo punya 271 booking dan 3 booking hari ini, tetapi Beranda hanya menampilkan "Siapkan bisnis Anda". Angka hari ini, "Perlu tindakan" dan jadwal hari ini tersembunyi. Panduan sebaiknya menjadi kartu kecil, bukan pengganti seluruh halaman, begitu bisnis punya booking pertama.

**3. Booking yang sudah lewat ditutup otomatis (atau sekaligus banyak).**
Tab "Perlu tindakan" berisi 80 booking, sebagian besar tampaknya sesi yang sudah lewat tetapi belum ditandai selesai. Akibatnya Laporan menulis "Tingkat selesai 1,8%", padahal bisnisnya berjalan normal. Kalau pemilik harus menandai satu per satu, ia tidak akan melakukannya, lalu angka di Laporan tidak bisa dipercaya.

**4. Laporan yang memberi arti, bukan hanya angka.**
"Rp13.600.000" tidak berarti apa-apa tanpa pembanding. Tambahkan "naik/turun dibanding bulan lalu" dan satu kalimat kesimpulan per tab (contoh: "Jam 13.00–17.00 paling sepi"). Datanya sudah ada.

**5. Rapikan halaman kerja harian dan selesaikan halaman yang masih mentah.**
Kalender, Booking dan Pelanggan terlalu boros ruang, terutama di HP: satu booking memakan satu layar. Halaman Jam Buka & Harga masih terlihat seperti formulir setengah jadi (kotak isian raksasa, daftar teks tanpa susunan). Ini murni pekerjaan tampilan dan bisa langsung dikerjakan.

Nomor 1, 3 dan 4 butuh keputusan Anda (bagian 4 dan 5). Nomor 5 tidak. Nomor 2 butuh persetujuan kecil karena mengubah isi Beranda.

---

## 2. Posisi brand dan kesesuaian tampilan

**Posisi yang saya sarankan:** BookQu adalah "buku jadwal yang tidak pernah salah" untuk pemilik lapangan, studio dan konsultan. Pemilik ini sibuk, sering memakai HP, dan bukan orang IT. Sifat yang harus terasa: tenang, cepat dibaca, berbahasa Indonesia sehari-hari.

**Penilaian BQ-161:**

- **Sesuai, pertahankan.** Aubergine + Paper + Plus Jakarta Sans terlihat serius dan bisa dipercaya untuk urusan uang. Marigold hanya untuk "sekarang" dan "perlu Anda" dipakai dengan disiplin (garis "Sekarang", lencana "Menunggu bayar", tanda di menu). Navigasi bawah di HP dengan tombol Walk-in di tengah sudah tepat.
- **Belum sesuai.**
  1. **Kepadatan.** Halaman operasional memakai jarak selonggar halaman pemasaran. Pemilik perlu melihat banyak baris sekaligus.
  2. **Bahasa campur.** "Walk-in", "slug", "Add-on", "Landing page", "Online", "Showing 1 to 15 of 271 results" berdampingan dengan bahasa Indonesia.
  3. **Tidak merata.** Beranda, Booking dan Pembayaran terasa selesai; Jam Buka & Harga, Slot dan sebagian Pengaturan masih formulir polos.
  4. **Angka tanpa hierarki.** Hampir semua kartu angka berukuran sama. Tidak ada yang menonjol sebagai "ini yang penting hari ini".

**Kesimpulan:** arah visual BQ-161 tidak perlu diulang. Yang perlu: satu putaran kepadatan dan penyelesaian (item A di bagian 3), lalu isi yang lebih bermakna (bagian 4).

---

## 3. Temuan per halaman

### Lintas halaman (shell)

1. **[A] Tiga nama untuk satu tindakan.** Fakta: "Walk-in" (atas), "+ Booking" (halaman Booking), "Booking" (Aksi cepat). Ubah semuanya menjadi "Catat booking". *Selesai bila:* satu label dipakai di topbar, halaman Booking, Aksi cepat dan navigasi HP. D S / K S / U K. Lihat pertanyaan 8.
2. **[A] Lebar halaman tidak konsisten.** Fakta: Pengaturan, Notifikasi dan Bantuan sempit di tengah; halaman lain rata kiri dan lebar. *Selesai bila:* semua halaman memakai satu aturan lebar dan posisi judul yang sama. D R / K T / U K.
3. **[A] Ikon keadaan kosong terlihat seperti tanda "memuat".** Fakta: Ulasan dan Notifikasi. *Selesai bila:* keadaan kosong memakai ikon diam yang sesuai isinya. D R / K T / U K.
4. **[A] Sisa bahasa Inggris.** "Showing … results" di Booking dan Transaksi; "(slug)" di Profil Bisnis. *Selesai bila:* tidak ada teks Inggris di paginasi; "slug" menjadi "Alamat link booking". D R / K T / U K.
5. **[A] Menu "Lainnya" di HP (shot 03).** Fakta di screenshot: isi halaman di belakang masih terbaca dan menimpa daftar menu. Asumsi: tertangkap saat animasi. *Selesai bila:* lembar menu berlatar solid dan terbaca pada 375 px. Periksa dulu. D T bila nyata / K R / U K.

### 8.1 Beranda (shot 01–02; tampilan harian dinilai dari deskripsi)

1. **[B] Panduan menutupi tampilan harian padahal bisnis sudah berjalan.** Fakta: 271 booking, panduan masih tampil karena langkah 3 belum tercentang. Usul: begitu ada satu booking, bagian 2–4 selalu tampil dan panduan menjadi kartu ringkas yang bisa ditutup. Mengubah §8.1 butir 7 dan AC-BRD-05. D T / K T / U S.
2. **[A] Satu masalah diumumkan empat kali.** Fakta: banner Beranda, langkah 3 panduan, tanda "!" di menu, banner di Jam Buka & Harga. *Selesai bila:* saat panduan tampil dan langkah aktifnya sama dengan isi banner, banner tidak ditampilkan. D S / K T / U K.
3. **[B] "Perlu tindakan" sebaiknya paling atas, di atas empat angka.** Pertanyaan halaman ini "apa yang harus saya lakukan hari ini", dan pemilik di HP hanya melihat layar pertama. Asumsi: urutan sekarang mengikuti §8.1 (angka dulu). D S / K S / U K.
4. **[B] Empat angka butuh pembanding kecil.** Contoh: "Booking hari ini 6 · besok 4" dan "Slot terisi 6 dari 14". Angka persen saja sulit dirasakan. Bukan grafik, jadi tidak melanggar AC-BRD-07. D S / K S / U K.
5. **[A] Aksi cepat dan kartu link kalah penting dari jadwal.** *Selesai bila:* pada 375 px urutan menjadi: banner, Perlu tindakan, angka, jadwal hari ini, besok, lalu link dan aksi cepat. D S / K S / U K.

### 8.2 Kalender (shot 04–07)

1. **[A] Nama layanan ditulis tiga kali per slot.** Fakta: di judul kolom, judul slot dan kartu booking. *Selesai bila:* kartu slot di tampilan hari hanya berisi jam, nama pelanggan, peserta dan status; nama layanan hanya di judul kolom. D T / K T / U K.
2. **[A] Tampilan minggu tidak terbaca.** Fakta: kolom ±140 px, teks terpotong per kata, lencana status keluar dari kartu, hari tanpa slot hanya judul. *Selesai bila:* tiap booking satu baris ringkas (jam · nama · titik status), tidak ada elemen keluar dari kolom, hari kosong bertuliskan "Tidak ada slot". D T / K T / U S.
3. **[A] Tampilan bulan hanya teks "5 slot · 3 booking".** Legenda warna ada tetapi tidak dipakai. *Selesai bila:* tiap tanggal memakai batang isi atau gradasi warna (kosong, sebagian, penuh), tanggal tanpa slot diredupkan, segitiga kecil dihilangkan. D S / K T / U S.
4. **[A] Di HP, jadwal baru muncul setelah ±570 px.** Fakta: judul, tombol, tab, navigasi tanggal, tiga filter dan legenda dulu. *Selesai bila:* pada 375 px filter dan legenda terlipat di satu tombol "Filter", dan slot pertama terlihat tanpa menggulir. D T / K T / U K.
5. **[B] Slot kosong tidak terlihat sebagai peluang.** Fakta: sel tanpa slot dan slot kosong yang sudah lewat sama-sama sepi. §8.2 meminta status "free" terlihat. Usul: slot kosong mendatang tampil jelas dengan "+ Catat booking". Lihat juga usulan fitur 4 (tampilan per unit). D S / K S / U S.

### 8.3 Booking dan 8.4 panel (shot 08–11)

1. **[C] Tidak ada cara menyelesaikan banyak booking sekaligus.** Fakta: Perlu tindakan (80), sedangkan tindakan hanya lewat panel satu per satu. Lihat usulan fitur 2. D T / K T / U S.
2. **[A] Kode booking jadi kolom pertama dan patah dua baris.** Pemilik mencari berdasarkan nama dan jam, bukan kode. *Selesai bila:* kolom pertama adalah sesi (tanggal dan jam), lalu pelanggan; kode tampil kecil di bawah nama dan tidak patah. D S / K T / U K.
3. **[A] Kartu booking di HP terlalu tinggi.** Fakta: ±175 px per kartu, hanya 4 kartu per layar; daftar unit memakan 1–3 baris. *Selesai bila:* kartu ≤ 110 px: baris 1 jam + nama + status, baris 2 layanan, baris 3 pembayaran + jumlah; unit hanya di panel. D T / K T / U K.
4. **[A] Dua lencana status membingungkan.** Fakta: "Menunggu bayar" + "Belum tercatat", "Terkonfirmasi" + "Berhasil". *Selesai bila:* daftar hanya menampilkan satu lencana status booking, dan kolom pembayaran berisi jumlah atau "Belum dibayar". Catatan: §8.3 menyebut dua status di baris, jadi bila lencana kedua dihapus ini menjadi B. D S / K S / U K.
5. **[A] Panel: tombol utama untuk booking "Menunggu bayar" adalah "Kirim ulang link booking", batas waktu bayar tidak terlihat, Riwayat kosong, dan "Dibayar —" tampil sebagai judul besar.** §8.4 mewajibkan batas waktu saat pending. *Selesai bila:* panel pending menampilkan "Bayar sebelum [jam]" atau "Batas bayar sudah lewat"; riwayat kosong bertuliskan "Belum ada riwayat"; baris "Dibayar" disembunyikan saat belum ada pembayaran. Format tanggal panel (16/11/2026) disamakan dengan daftar (16 Nov 2026). D S / K T / U K.

Di HP, deretan tab terbuka tergulir ke ujung kanan sehingga "Perlu tindakan" tidak terlihat (shot 10). **[A]** *Selesai bila:* tab aktif selalu digulir ke dalam layar dan "Perlu tindakan" adalah tab pertama yang terlihat saat halaman dibuka.

### 8.5 Pelanggan (shot 12–13)

1. **[A] Tidak ada tombol WhatsApp di baris,** padahal pertanyaan halaman ini "bagaimana saya menghubungi mereka". *Selesai bila:* tiap baris dan kartu punya ikon WhatsApp yang membuka chat ke nomor itu. D T / K T / U K.
2. **[A] Kartu HP ±180 px, empat baris label-nilai.** *Selesai bila:* kartu ≤ 96 px: nama, nomor, lalu satu baris "11 booking · Rp2.700.000 · terakhir 16 Nov". D S / K T / U K.
3. **[B] Angka ringkasan tidak membantu.** Fakta: "Total 20 · Baru 0 · Kembali lagi 20". Usul: ganti kartu ketiga dengan "Lama tidak datang" (jumlah), karena itu daftar yang bisa ditindaklanjuti. D S / K S / U K.
4. **[A] Segmen tidak terlihat di daftar.** *Selesai bila:* baris menampilkan lencana "Baru", "Tetap" atau "Lama tidak datang" sesuai filter cepat yang sudah ada. D S / K S / U K.
5. **[B] "Berikutnya" menampilkan booking yang sudah lewat dan belum dibayar.** Fakta: Arif Hakim "16 Nov 2026 14:00", status Menunggu bayar. Lihat pertanyaan teknis 1. D S / K S / U K.

### 8.6 Layanan (shot 14, 16)

1. **[A] Area gambar kosong setinggi ±190 px berisi nama layanan lagi.** *Selesai bila:* layanan tanpa gambar memakai kartu ringkas tanpa area gambar, atau ada tampilan daftar (nama, kategori, harga, durasi, status) sebagai bawaan di atas 6 layanan. D S / K T / U S.
2. **[A] Kalimat "Sudah pernah dibooking, jadi hanya bisa dinonaktifkan" diulang di 10 kartu.** *Selesai bila:* kalimat itu hanya muncul saat pemilik mencoba menghapus. D R / K T / U K.
3. **[B] Kartu layanan tidak memberi tahu slot terbuka sampai kapan.** Ini informasi paling penting per layanan dan sudah dihitung untuk Jam Buka & Harga. Usul: baris "Slot terbuka sampai 14 Des" atau "Belum ada slot" dengan link. D T / K S / U K.
4. **[A] Add-on: "Stok 0" tidak diberi tanda** (fakta: Lensa Sony). *Selesai bila:* stok 0 menampilkan lencana "Habis". D R / K T / U K.

### 8.7 Unit (shot 17)

1. **[B] "Konflik booking" berisi tanggal lampau** (fakta: Agustus dan September 2026) **dan pasangan yang sama diulang per unit.** Konflik lama tidak bisa ditindak. Usul: hanya tampilkan konflik mendatang; yang lampau dilipat. D S / K S / U K.
2. **[A] Batang "0% hari ini" tanpa konteks.** *Selesai bila:* tertulis "0 dari 4 jam terpakai hari ini" dan batang kosong tetap terlihat sebagai batang. D R / K S / U K.

### 8.8 Jam Buka & Harga (shot 18–21)

1. **[C] Akar masalah: jam buka tidak membuat slot.** Fakta: halaman menulis sendiri "Jam buka tidak otomatis membuat slot"; semua hari tercentang "Tutup" padahal slot ada. Lihat usulan fitur 1. D T / K T / U B.
2. **[A] Tab Harga: "Harga tanggal khusus" rusak tata letaknya.** Fakta: kotak tanggal dan harga setinggi ±160 px. *Selesai bila:* semua kotak isian setinggi standar (±40 px) dan daftar layanan memakai pilihan ganda yang ringkas. D S / K T / U K.
3. **[A] Tab Jam Buka masih formulir polos.** Tujuh blok berulang, "Salin ke semua hari" di setiap hari, kolom "Batas buka slot" kosong tanpa penjelasan, daftar durasi berupa satu paragraf panjang. *Selesai bila:* tujuh hari tampil sebagai tabel satu baris per hari (hari, buka/tutup, jam, tombol jeda), satu tombol salin, dan paragraf durasi dihapus atau jadi link. D S / K T / U S.
4. **[A] Tab Slot: tiap baris punya kotak harga dan tombol "Simpan harga".** *Selesai bila:* baris hanya berisi layanan, jam, harga, status berlencana dan menu "⋯" (Tutup, Ubah harga); "Buat slot sekaligus" terbuka sebagai tombol utama, bukan baris terlipat. D S / K T / U S.
5. **[A] Banner runway tidak punya tombol.** Fakta: di halaman ini banner hanya punya panah. *Selesai bila:* banner berisi tombol "Buka slot" yang membuka pembuatan slot sekaligus. D S / K T / U K.

### 8.9 Halaman Booking (shot 35–37, 39)

1. **[A] Gambar tampil sebagai teks pengganti** di Tampilan dan Landing page (fakta). Asumsi: berkas contoh tidak ada di lingkungan lokal. *Selesai bila:* gambar yang gagal dimuat diganti kotak abu-abu dengan nama berkas terpotong, tidak merusak tata letak. D S / K S / U K.
2. **[A] Warna tema bawaan `#4F46E5`, bukan aubergine BookQu.** *Selesai bila:* warna pertama di daftar adalah warna brand, atau ada alasan tertulis mengapa berbeda. D R / K S / U K. Lihat pertanyaan 9.
3. **[B] Pratinjau HP terlalu miskin** (satu layanan contoh). Usul: pratinjau memakai layanan dan logo asli pemilik. D S / K S / U S.
4. **[A] Link & QR dan Materi promosi masing-masing setengah layar kosong.** *Selesai bila:* keduanya digabung dalam satu tab "Bagikan". Catatan: §8.9 menyebut keduanya sebagai bagian terpisah, jadi bila tab digabung ini menjadi B. D R / K S / U K.

### 8.10 Promo dan 8.11 Ulasan (shot 22–23)

1. **[A/teknis] Angka tidak cocok.** Fakta: ringkasan "Pemakaian 0", kartu "38 / 200", "Diskon diberikan Rp 0". Lihat pertanyaan teknis 3. D T / K T / U K–S.
2. **[A] Kartu promo: enam baris label-nilai dan lingkaran hias di sudut.** *Selesai bila:* kartu berisi kode, nilai, batang kuota, masa berlaku dan "Berlaku untuk"; hiasan dihapus. D R / K T / U K.
3. **[A] Ulasan: label "5 bintang" patah dua baris.** *Selesai bila:* label satu baris pada 1366 dan 375 px. D R / K T / U K.

### 8.12 Pembayaran (shot 24–26, 28)

1. **[teknis] Saldo Rp18.500.000 tetapi "Belum ada mutasi" dan "Saldo bersih dari riwayat mutasi Rp 0".** Bertentangan dengan AC-PAY-02 bila bukan data contoh. Lihat pertanyaan teknis 2. D T / K S.
2. **[A] "Cairkan saldo" mati, alasannya di kotak terpisah tanpa tombol.** *Selesai bila:* kotak alasan berisi tombol "Tambah rekening" yang membuka tab Rekening. D T / K T / U K.
3. **[B] Tab "Pengaturan" tidak punya pengaturan.** Usul: ganti nama menjadi "Cara kerja". D R / K T / U K.
4. **[A] Ringkasan: empat kartu status berisi 197, 0, 0, 0.** *Selesai bila:* kartu bernilai 0 digabung menjadi satu baris teks kecil. D R / K S / U K.

### 8.13 Laporan (shot 29–34)

1. **[C] Tidak ada pembanding periode.** Lihat usulan fitur 3. D T / K T / U S.
2. **[B] Angka tidak punya kesimpulan.** Okupansi sudah punya satu kalimat ("Hari paling ramai: Sen"). Usul: tiap tab punya satu kalimat seperti itu. Contoh dari data demo: "Product Shoot hanya 7 booking tetapi pendapatannya terbesar." D T / K S / U S.
3. **[A] Ringkasan: sembilan kartu sama besar dan satu kotak abu-abu kosong.** *Selesai bila:* pendapatan dan booking tampil besar di baris pertama, tingkat-tingkat di baris kedua yang lebih kecil, tidak ada sel kosong. D S / K T / U K.
4. **[B] Dua dasar hitung dalam satu layar.** Fakta: catatan kaki menyebut booking dihitung menurut tanggal layanan, pendapatan menurut pembayaran. Pemilik akan membagi Rp13,6 juta dengan 57 booking dan mendapat angka yang salah. Usul: tulis dasar hitung di bawah tiap angka, bukan di catatan kaki. D S / K S / U K.
5. **[A] "Pendapatan bersih per hari" terpotong dalam kotak bergulir.** *Selesai bila:* 30 hari tampil sebagai batang tegak dalam satu layar, atau daftar tampil penuh tanpa gulir di dalam kotak. D S / K T / U S.

### 8.14–8.17 Pengaturan, Paket & Tagihan, Notifikasi, Bantuan (shot 41–49)

1. **[A] Paket: tiga kolom mengulang 16 baris yang sama.** *Selesai bila:* Medium menulis "Semua di Small, ditambah:" lalu hanya selisihnya; begitu juga Pro. Tetap dihasilkan dari daftar paket (AC-PKT-01). D S / K T / U S.
2. **[teknis] Bantuan menulis "Kontak bantuan belum diatur"; Integrasi menulis "Google Calendar belum dikonfigurasi" tanpa tindakan.** Keduanya jalan buntu bagi pemilik. *Selesai bila:* kontak terisi sebelum rilis; kartu integrasi yang belum siap disembunyikan atau bertuliskan "Segera hadir". D T / K T / U K.
3. **[A] Kebijakan Booking: "0 jam" dan "0 menit" tanpa arti.** *Selesai bila:* teks bantu menulis "0 = pelanggan boleh booking sampai sesi dimulai" dan "0 = tanpa jeda". D S / K T / U K.
4. **[A] Tim: undangan tanpa penjelasan peran.** *Selesai bila:* di bawah pilihan peran ada satu kalimat tentang apa yang boleh dilakukan Kasir atau Manajer. D S / K T / U K.

---

## 4. Usulan fitur dan informasi baru

Semua berlabel **C**. Urutan menurut nilai (dampak × keyakinan ÷ kerja).

| # | Usulan | Masalah pemilik | Data | D / K / U |
|---|---|---|---|---|
| 1 | **Slot otomatis bergulir.** Sistem membuka slot dari jam buka sampai N hari ke depan, setiap hari. Pemilik hanya mengatur pengecualian. | Lupa membuka slot berarti tidak bisa dibooking tanpa sadar. Empat peringatan tidak menyelesaikannya. | Sudah ada: `opening_hours`, `tenants.slot_open_days_ahead`, `services.durasi`, `owner_blocked_dates`, `schedules`. Baru: tanda "otomatis" per layanan. **Aturan domain + skema: Tech Lead.** | T / T / B |
| 2 | **Selesai otomatis atau massal.** Booking `paid` yang sesinya lewat X jam menjadi "Selesai" sendiri, kecuali ditandai tidak datang. Minimal: centang banyak lalu "Tandai selesai". | 80 booking menumpuk; "Tingkat selesai 1,8%" membuat Laporan tidak dipercaya. | Sudah ada: `bookings.status`, `tanggalbooking`, `jam`, `booking_logs`. Versi otomatis: **aturan domain, Tech Lead.** | T / T / K (massal), S (otomatis) |
| 3 | **Pembanding periode di Laporan.** Tiap angka utama diberi "▲ 12% dari bulan lalu". | Angka tanpa pembanding tidak bisa dinilai baik atau buruk. | Sudah ada: `payments`, `refunds`, `bookings`. `GetReport` perlu menghitung periode sebelumnya. Tanpa skema. | T / T / S |
| 4 | **Kalender per unit.** Pilihan kolom: per layanan atau per unit (Lapangan A, Studio B). | Pemilik lapangan dan studio berpikir "ruang mana yang kosong", bukan "layanan mana". Fakta: demo punya 10 layanan tetapi 6 unit. | Sudah ada: `units`, kaitan unit–layanan, `UnitUtilisation`. Paket Pro. | T / S / S |
| 5 | **Sapa pelanggan lama.** Di filter "Lama tidak datang", tombol WhatsApp membuka pesan siap kirim, satu per satu (bukan pesan massal, sesuai §8.5). | Pemilik tahu siapa yang hilang tetapi tidak punya langkah berikutnya. | Sudah ada: `bookings` per `customer_identifier`, `nomorhp`. Tanpa skema. | S / T / K |
| 6 | **Ringkasan mingguan lewat email.** Senin pagi: pendapatan, booking, okupansi, slot terbuka sampai kapan, hal yang menunggu. | Asumsi: banyak pemilik tidak membuka portal tiap hari. | Sudah ada: semua angka `GetReport` dan `GetBeranda`. Baru: satu jenis notifikasi dan preferensinya. | S / S / S |
| 7 | **Sumber booking.** Link berbeda untuk Instagram, WhatsApp, Maps dan QR; booking mencatat asalnya. | Halaman Booking bertanya "bagaimana pelanggan menemukan saya" tetapi tidak bisa menjawab. | **Belum ada** (inventaris §2: tidak ada kanal akuisisi). Baru: kolom sumber di `bookings`. **Skema: Tech Lead.** | S / S / S |
| 8 | **Jam sepi → promo.** Dari peta jam ramai, tombol "Buat promo untuk jam sepi". | Kursi kosong adalah pendapatan yang hilang; peta jam sudah menunjukkannya tetapi berhenti di situ. | Sudah ada: `schedules`, `booking_seats`, `vouchers`. **Belum jelas:** apakah promo bisa dibatasi jam atau hari (pertanyaan 6). Bila tidak: aturan domain baru. | S / R / S–B |
| 9 | **Seberapa jauh pelanggan memesan.** Satu angka di Laporan → Okupansi: "Rata-rata booking 3 hari sebelum sesi". | Pemilik menebak berapa hari ke depan slot perlu dibuka dan batas minimum booking. | Sudah ada: `bookings.created_at` dikurangi `tanggalbooking`. Tanpa skema. | R / T / K |
| 10 | **Daftar "gagal bayar" untuk ditindaklanjuti.** Pelanggan yang sudah memilih slot tetapi tidak membayar, dengan tombol WhatsApp. | Ini calon pelanggan paling dekat dengan membeli. | Sebagian ada: `payments.status = gagal`, `booking_logs`. **Belum jelas:** apakah batal karena waktu habis bisa dibedakan dari batal lain (pertanyaan teknis 4). | S / R / S |

**Tidak saya usulkan**, karena di luar batas produk (`INDEX.md`): laba per layanan (tidak ada data biaya), target pendapatan, kinerja per staf, konversi kunjungan (tidak ada data kunjungan), pesan massal.

---

## 5. Pertanyaan untuk Brammy

1. **Slot otomatis (fitur 1).** Setuju sistem membuka slot sendiri dari jam buka? Apakah bawaan untuk semua bisnis, atau pilihan per layanan?
2. **Selesai otomatis (fitur 2).** Bolehkah booking yang sudah dibayar dianggap "Selesai" sendiri setelah sesinya lewat? Berapa jam setelah sesi? Atau cukup tindakan massal dulu?
3. **Panduan di Beranda.** Setuju panduan menjadi kartu kecil begitu ada booking pertama, sehingga tampilan harian tidak pernah tertutup?
4. **Pembanding periode (fitur 3).** Dibanding periode sebelumnya, atau periode yang sama tahun lalu? Apakah ini ikut batas paket Laporan (Medium ke atas)?
5. **Kalender per unit (fitur 4).** Jenis bisnis mana yang paling Anda bidik? Kalau lapangan dan studio, tampilan per unit seharusnya menjadi bawaan untuk Pro.
6. **Promo.** Apakah promo sekarang bisa dibatasi hari atau jam? Kalau tidak, apakah Anda mau itu?
7. **Ringkasan mingguan (fitur 6).** Email cukup, atau ini hanya bernilai lewat WhatsApp (yang saat ini tidak ditawarkan, D-021)?
8. **Istilah.** Apakah "Walk-in" dan "+ Booking" memang satu formulir yang sama? Kalau ya, setuju satu nama "Catat booking"?
9. **Warna tema halaman booking.** Sengaja berbeda dari warna BookQu?
10. **Halaman tanpa screenshot.** Tampilan Kasir, Manajer dan halaman booking pelanggan belum dinilai. Mau diulas di putaran berikutnya?

**Pertanyaan teknis untuk Tech Lead** (fakta di screenshot yang tidak bisa saya jelaskan dari bahan):

1. Booking "Menunggu bayar" dengan sesi yang sudah lewat masih tampil (27 buah), sedangkan Pembayaran menulis booking batal otomatis setelah 15 menit dan slotnya tampil "Terbuka". Data contoh, atau perilaku nyata?
2. Saldo Rp18.500.000 dengan mutasi kosong: data contoh, atau buku mutasi tidak membaca pembayaran lama?
3. Promo: "Pemakaian 0" berbanding "38 / 200" di kartu. Dua sumber angka berbeda?
4. Apakah pembatalan karena waktu bayar habis tercatat berbeda dari pembatalan lain di `booking_logs`?
5. Menu "Lainnya" di HP (shot 03): tembus pandang sungguhan, atau tertangkap saat animasi?
