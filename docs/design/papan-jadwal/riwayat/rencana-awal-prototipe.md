# Rencana: prototipe independen portal owner BookQu ("Papan Jadwal")

## Context

Review portal owner (`review.md`) dan audit arsitektur (`audit-ui.md`) menghasilkan sekitar 45 temuan tampilan, 7 perubahan isi, dan 10 usulan fitur, tetapi semuanya masih kata-kata. Tidak ada gambar sasaran, sehingga dua pelaksana bisa menghasilkan dua tampilan berbeda.

Prototipe ini menjadi **acuan visual tunggal**: semua halaman portal owner, dengan semua usulan sudah diterapkan, satu gaya yang konsisten, data statis. Dasar pembandingnya adalah kondisi sekarang (screenshot di `shots/`).

Batasan yang Anda tetapkan:

- Independen: tidak terhubung ke Laravel, tidak ada backend, tidak mengikuti aturan frontend repo (Blade, Tailwind, token `bq-*`).
- Lokasi: `prototype/` (dikecualikan dari git). Lokal saja, tidak diterbitkan.
- Tidak ada berkas repo yang diubah, tidak ada commit.

## 1. Arah desain

### Subjek, pemakai, tugas

- **Subjek:** meja depan bisnis jasa berjadwal di Indonesia (lapangan, studio, ruang sewa).
- **Pemakai:** pemilik dan kasir, sering di HP, di sela melayani pelanggan.
- **Tugas utama:** "siapa datang jam berapa, sudah bayar atau belum, apa yang harus saya lakukan sekarang".

### Konsep: Papan Jadwal

Bisnis seperti ini hidup dari papan jadwal: jam di sisi kiri, nama di kanan, tanda lunas. Maka **waktu menjadi tulang punggung tata letak**. Semua yang punya jam disusun pada sumbu waktu; semua yang lain tenang dan padat.

Satu-satunya elemen berani: **rel hari** (sumbu jam dengan garis "sekarang" berwarna kunyit) di Beranda dan Kalender, dengan angka jam bergaya papan penunjuk. Selain itu tidak ada hiasan.

### Warna

| Nama | Hex | Peran |
|---|---|---|
| Terong | `#382186` | Hanya untuk yang bisa ditekan dan yang sedang dipilih: tombol utama, menu aktif, link |
| Tinta | `#1D1638` | Teks dan judul |
| Kunyit | `#FFB84D` | Hanya "sekarang" dan "perlu Anda". Tidak pernah jadi teks |
| Kabut | `#F5F4F9` | Latar aplikasi; panel tetap putih `#FFFFFF` |
| Lilak | `#ECE8FA` | Baris terpilih, slot terisi |
| Garis | `#E3E0EE` | Batas 1 px |

Teks redup `#625E75`. Status hanya tiga warna: hijau daun `#1F7A4D` (lunas, selesai), merah bata `#B3261E` (batal, bahaya), kunyit pucat `#FFF1D6` dengan teks Tinta (menunggu). Warna merek BookQu dipertahankan agar tetap dikenali.

**Perubahan terbesar dari kondisi sekarang: sidebar menjadi terang.** Sekarang Terong mengisi 19% layar, sehingga tombol utama (juga Terong) tidak menonjol. Dengan sidebar terang, Terong berarti "tekan di sini". Prototipe menyediakan sakelar "Sidebar: terang / terong" supaya Anda bisa membandingkan langsung dengan kondisi sekarang.

### Huruf

- **Plus Jakarta Sans** (dipertahankan): semua teks antarmuka.
- **Barlow Semi Condensed** (baru): hanya untuk jam di rel, dan angka utama. Memberi rasa papan penunjuk dan menghemat lebar di HP.

| Tingkat | Ukuran / tinggi baris | Pakai |
|---|---|---|
| Angka utama | 40 / 44, Barlow 600 | Satu angka per halaman (booking hari ini, saldo) |
| Judul halaman | 24 / 32, 700 | — |
| Judul bagian | 17 / 24, 600 | Judul panel |
| Isi | 14 / 20 (daftar), 15 / 22 (formulir; 16 di HP) | — |
| Kecil | 12.5 / 18, 500 | Baris kedua, lencana |

Tidak ada huruf kapital semua, tidak ada ukuran di bawah 12 px.

### Bentuk, kepadatan, gerak

- Radius: 6 px kontrol, 10 px panel, 16 px dialog dan lembar. Hanya lapisan melayang yang berbayang.
- Dua kepadatan: **padat** (baris 40 px) untuk daftar dan tabel, **nyaman** (48 px) untuk formulir. Sakelar di prototipe.
- Gerak hanya menjawab tindakan (panel bergeser 200 ms). Tidak ada animasi saat halaman dimuat.

### Tata letak

```text
Desktop                                              HP (375)
+----------+---------------------------------+      +--------------------+
| bq BookQu| Cari…            [Catat booking]|      | bq  Cari…      (o) |
| (terang) |---------------------------------|      |--------------------|
| Beranda  | Judul halaman        [1 utama]  |      | Perlu Anda (3)   > |
| Kalender | satu kalimat tujuan             |      | 09 | Umar · Lunas  |
| Booking 3| -- tab -------------------------|      |-10:24--- kunyit ---|
| Pelanggan| isi, rata kiri, maks 1200 px    |      | 11 | Maya · Tunggu |
| …        | formulir maks 640 px + ringkasan|      |--------------------|
+----------+---------------------------------+      | Ber Kal (+) Bkg Lai|
                                                     +--------------------+
```

```text
Beranda (harian)
Senin, 16 Nov                                  +-- Perlu Anda (3) ---------+
 6  booking hari ini                           | 2 sesi lewat belum ditutup |
    4 lunas · 2 menunggu · Rp2.150.000         | 1 refund menunggu          |
                                               +----------------------------+
 08 |
 09 | Umar W.   Webinar Room    Lunas      [Selesai]
 10 | ─────────── sekarang 10.24 ───────────────────
 11 | Maya W.   Podcast Suite   Menunggu bayar · sisa 12 mnt
 13 | kosong · Studio Foto A                [+ Catat booking]
Besok: 4 booking, pertama 09.00       Slot terbuka sampai 14 Des
```

Pola daftar tunggal untuk semua halaman: tabel padat di desktop, baris dua-baris 64 px di HP (bukan kartu), filter di balik tombol "Filter (n)".

### Yang saya ubah dari rancangan bawaan

- Rancangan pertama memakai empat kartu angka di Beranda. Diganti satu angka utama dengan satu baris konteks, karena kasir butuh "apa sekarang", bukan empat angka.
- Rancangan pertama mempertahankan sidebar Terong. Diganti terang dengan alasan di atas; versi Terong tetap tersedia lewat sakelar.
- Kartu grid hanya untuk hal yang memang sejajar (layanan bergambar, promo). Daftar tidak pernah berupa kartu.
- Tidak ada label kecil kapital di atas judul, tidak ada gradasi, tidak ada penomoran selain panduan awal (yang memang berurutan).

## 2. Bentuk teknis

HTML, CSS dan JavaScript murni. Tanpa kerangka, tanpa proses build; dibuka langsung dari `index.html`. Huruf dari Google Fonts.

```text
docs/design/papan-jadwal/prototype/
  index.html            cangkang + titik masuk
  css/tokens.css        warna, huruf, jarak, kepadatan, radius (variabel CSS)
  css/components.css    semua komponen
  css/pages.css         tata letak khusus halaman (sesedikit mungkin)
  js/data.js            data statis satu bisnis
  js/ui.js              fungsi render komponen (satu fungsi per komponen)
  js/pages/*.js         satu berkas per halaman
  js/app.js             router hash, sakelar, panel, dialog
  README.md             peta prototipe → temuan review → kontrak §8
```

Aturan struktur yang menjamin konsistensi: **halaman tidak boleh menulis gaya sendiri**; halaman hanya memanggil komponen dari `ui.js`. Ini sama dengan arsitektur empat lapis di `plan.md` 3.3, sehingga prototipe sekaligus menguji daftar komponen yang nanti dibangun di `x-ui`.

### Data statis

Bisnis "Brama Studio Creative" (sama dengan screenshot, supaya bisa dibandingkan), tetapi dengan angka yang saling cocok dan "hari ini" yang hidup: Senin 16 Nov 2026 pukul 10.24, 6 booking hari ini, 10 layanan, 6 unit, 20 pelanggan.

### Sakelar prototipe (bilah kecil di pojok, bukan bagian desain)

| Sakelar | Pilihan | Gunanya |
|---|---|---|
| Peran | Pemilik, Manajer, Kasir | Tampilan Kasir dan Manajer belum pernah terlihat |
| Paket | Small, Medium, Pro | Laporan buram di Small, kunci fitur |
| Keadaan | Berjalan, Bisnis baru, Langganan berakhir | Panduan awal, keadaan kosong, §12 |
| Kepadatan | Padat, Nyaman | — |
| Sidebar | Terang, Terong | Banding dengan kondisi sekarang |
| Catatan perubahan | Mati, Hidup | Menempelkan label A / B / C dan nomor temuan pada tiap elemen yang berubah |

Sakelar "Catatan perubahan" penting: elemen berlabel B atau C adalah yang butuh keputusan Brammy, dan yang berasal dari D-021 diberi tanda "ditunda D-021".

## 3. Komponen

Semua tampil di halaman `#/sistem` dengan setiap varian dan keadaan (biasa, fokus, mati, galat, memuat, kosong).

- **Dasar:** tombol (utama, kedua, polos, bahaya), bidang + input, pilihan, kotak centang, sakelar, kartu radio, kontrol segmen, lencana status (satu lencana per baris), avatar, chip salin.
- **Susunan:** kepala halaman, tab, panel, bagian formulir + bilah simpan lengket, strip angka (satu utama + pendukung), label–nilai, tabel↔daftar responsif, baris daftar, bilah filter + lembar filter HP, bilah tindakan massal, paginasi, banner, keadaan kosong, kerangka memuat.
- **Melayang:** dialog, panel samping (slip booking, pelanggan), lembar bawah HP, menu "⋯", toast, dialog konfirmasi.
- **Waktu dan data:** rel hari, kisi minggu, kalender bulan berisi batang isi, peta jam ramai, baris batang, penanda naik/turun, batang kuota, kisi jam buka mingguan, pratinjau HP, panduan langkah.

## 4. Halaman

Semua 17 halaman, semua tab, panel dan dialog; desktop dan HP. "Usulan" merujuk ke `review.md`.

| Halaman | Seperti apa di prototipe | Usulan yang diterapkan |
|---|---|---|
| Cangkang | Sidebar terang, satu nama tindakan "Catat booking", navigasi bawah HP, menu "Lainnya" sebagai lembar penuh | Lintas 1–5 |
| Beranda | "Perlu Anda" paling atas, satu angka utama, rel hari, besok, slot terbuka sampai kapan; panduan jadi kartu ringkas yang bisa ditutup | 8.1 no. 1–5 |
| Kalender | Hari: rel dengan kolom **per layanan atau per unit**; Minggu: satu baris per booking; Bulan: batang isi per tanggal; slot kosong mendatang jadi ajakan | 8.2 no. 1–5, fitur 4 |
| Booking | Tab tugas, kolom pertama sesi, satu lencana, centang banyak + "Tandai selesai", baris 64 px di HP | 8.3 no. 1–4, fitur 2 |
| Slip booking | Kepala seperti karcis (jam besar, status, batas bayar), satu tindakan utama sesuai keadaan, riwayat | 8.3 no. 5 |
| Catat booking | Pilih slot sebagai chip jam, cara bayar sebagai kartu radio, total lengket | Sisa BQ-161 |
| Pelanggan | Lencana segmen, tombol WhatsApp per baris, kartu "Lama tidak datang", pesan sapa siap kirim | 8.5 no. 1–5, fitur 5 |
| Layanan (3 tab) | Daftar ringkas sebagai bawaan, kisi bergambar sebagai pilihan; "slot terbuka sampai" per layanan; add-on dengan "Habis" | 8.6 no. 1–4 |
| Unit | Baris dengan "terpakai 2 dari 8 jam hari ini"; konflik hanya yang mendatang | 8.7 |
| Jam Buka & Harga (4 tab) | Jam buka sebagai kisi mingguan; **sakelar "Buka slot otomatis"**; harga sebagai tabel aturan + simulator; slot sebagai tabel dengan menu "⋯" | 8.8 no. 1–5, fitur 1 (D-021) |
| Halaman Booking | Tab "Bagikan" (link, QR, teks siap pakai, **link per kanal**); tampilan dengan pratinjau HP memakai data asli; galeri; landing; domain | 8.9, fitur 7 |
| Promo | Kartu: nilai diskon, batang kuota, masa berlaku; **"Buat promo untuk jam sepi"** | 8.10, fitur 8 |
| Ulasan | Ringkasan rating, baris ulasan dengan keadaan balasan | 8.11 |
| Pembayaran (5 tab) | Saldo sebagai angka utama dengan langkah berikutnya yang jelas; mutasi; tab "Cara kerja" | 8.12 no. 2–4 |
| Laporan (5 tab) | Dua angka utama dengan **naik/turun dibanding periode lalu**, satu kalimat kesimpulan per tab, batang 30 hari, **berapa hari pelanggan memesan di muka**, **sumber booking**, **gagal bayar** | 8.13 no. 1–5, fitur 3, 7, 9, 10 |
| Pengaturan (6 tab) | Bagian formulir seragam, teks bantu di tiap angka, penjelasan peran di Tim, **ringkasan mingguan** di Notifikasi, **selesai otomatis** di Kebijakan | 8.14, fitur 2, 6 |
| Paket & Tagihan | "Semua di Small, ditambah:" | 8.14 no. 1 |
| Notifikasi, Bantuan | Baris belum dibaca, kontak sebagai tindakan jelas | 8.14 no. 2 |

## 5. Urutan kerja

1. **Fondasi dan cangkang.** `tokens.css`, `components.css`, `ui.js`, halaman `#/sistem`, cangkang desktop dan HP, sakelar. **Berhenti di sini dan tunjukkan kepada Anda** (screenshot 1366 dan 375 px) sebelum membuat halaman, karena semua halaman mewarisi gaya ini.
2. **Alur harian:** Beranda, Kalender (3 tampilan), Booking, slip booking, catat booking, Pelanggan, panel pelanggan.
3. **Penyiapan:** Layanan, Unit, Jam Buka & Harga.
4. **Uang:** Pembayaran, Laporan, Promo, Ulasan.
5. **Sisanya:** Halaman Booking, Pengaturan, Paket & Tagihan, Notifikasi, Bantuan.
6. **Keadaan dan catatan:** Kasir dan Manajer, Small, bisnis baru, langganan berakhir, keadaan kosong, galat formulir; label catatan perubahan; `README.md`.

## 6. Batas

- Bukan kode produksi dan tidak untuk disalin ke Blade; ia acuan tampilan dan perilaku.
- Interaksi sebatas: pindah halaman dan tab, buka panel dan dialog, filter, centang banyak, sakelar. Tidak ada yang tersimpan.
- Fitur berlabel C tampil seolah sudah ada. Itu gambaran, bukan keputusan; keputusannya tetap lewat `D-NNN`.
- Halaman booking pelanggan dan panel admin tidak termasuk.

## 7. Verifikasi

- Buka `index.html` di browser bawaan; tiap halaman dan tab dipotret pada 1366 dan 375 px, lalu saya periksa sendiri terhadap aturan gaya di bagian 1.
- Konsol tanpa galat; tidak ada gulir mendatar pada 375 px.
- Uji papan ketik pada cangkang, slip booking dan dialog; fokus selalu terlihat.
- Kontras teks dihitung (AA) untuk semua pasangan warna di `tokens.css`.
- Pemeriksaan konsistensi: tidak ada warna, ukuran huruf, atau radius di luar `tokens.css` (dicari dengan grep di `pages.css` dan `js/pages`).
- Daftar periksa di `README.md`: tiap temuan dan fitur di `review.md` dipetakan ke layar yang menerapkannya, atau ditandai "tidak diterapkan" beserta alasannya.
