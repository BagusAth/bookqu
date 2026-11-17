# Rencana peningkatan UI portal owner

Tanggal: 16 Nov 2026. Pembaca: Tech Lead. Dokumen ini berisi audit arsitektur UI dan urutan kerja. Tidak ada implementasi.

Sumber: `review.md` (temuan dari screenshot), kode di `resources/views/owner`, `resources/views/components`, `resources/css/app.css` pada `integration` `7f77ec6` (PR #67 sudah digabung), `docs/work/BQ-161-…md`, `docs/product-decisions.md`, `docs/workflow.md`, `docs/work/ROADMAP.md`, `NEXT.md`.

---

## 1. Kesimpulan

1. **Sistem desain BQ-161 baru setengah terpasang.** Token dan kelas `bq-*` dipakai luas, tetapi komponen `x-ui` hampir tidak dipakai. Selama itu belum beres, setiap perbaikan tampilan harus diulang di tiap halaman.
2. **Sebagian besar temuan A di `review.md` adalah gejala dari empat pola yang belum ada:** tabel/daftar responsif, tata letak formulir, susunan angka, dan kepadatan. Perbaiki polanya dulu.
3. **Dua usulan terbesar saya sudah pernah diputuskan ditunda** (D-021). Lihat bagian 2.
4. **Waktu paling penting:** rilis v1.0.0 (BQ-158) menunggu uji regresi terhadap semua `AC-*`. Setiap perubahan UI sebelum uji itu menambah bahan uji. Rekomendasi saya: sebelum rilis hanya perbaikan yang menghalangi rilis; sisanya setelah rilis. Ini keputusan Anda (bagian 4, K-1).

---

## 2. Koreksi terhadap `review.md`

| Di `review.md` | Koreksi | Sumber |
|---|---|---|
| Rekomendasi 1 dan fitur 1: slot otomatis dari jam buka | Sudah diputuskan **tidak masuk rilis ini** dan "diajukan lagi setelah rilis". Bukan usulan baru; tinggal diajukan ulang setelah rilis. D-024 sudah menambah tombol "Isi dari jam buka" sebagai jalan tengah. | D-021, D-024 |
| Rekomendasi 4 (kalimat kesimpulan di Laporan) | "Saran otomatis di Laporan" juga ditunda oleh D-021. Pembanding periode tidak disebut D-021, tetapi BQ-161 melewatinya karena `GetReport` tidak menyiapkan datanya. | D-021, BQ-161 §6 |
| Fitur 6 (ringkasan mingguan), pertanyaan 7 soal WhatsApp | Pengingat dan integrasi WhatsApp ditunda oleh D-021. Versi email belum pernah diputuskan. | D-021 |
| "Satu masalah diumumkan empat kali" berlabel A | Bukan A murni. Banner berasal dari `GetBeranda`; BQ-161 sengaja melewatinya. Perlu perubahan di Action, dan §8.1 mengatur urutan banner. Label yang benar: B. | BQ-161 Evidence §4 |
| Jam buka sebagai tabel mingguan, pratinjau HP, tabel simulator, chip slot walk-in | Sudah tercatat sebagai pekerjaan yang dilewati BQ-161. Bukan temuan baru; tinggal dijadwalkan. | BQ-161 kolom "Skipped" |
| Semua temuan | Belum dicek terhadap kode. `NEXT.md` langkah 2.1 mewajibkan itu sebelum ada work item. | `NEXT.md` |

Yang tetap berlaku: Beranda tertutup panduan, tumpukan "Perlu tindakan", kepadatan HP, halaman formulir mentah, dan empat fakta yang tampak seperti bug.

---

## 3. Audit arsitektur UI

Diukur di kode, 16 Nov 2026.

### 3.1 Yang sudah baik

- **Token:** satu palet `bq-*` di `resources/css/app.css` (227 baris); warna mentah dan hex tinggal di poster dan pratinjau landing.
- **Blok bersama §9 konsisten:** `x-owner.page-header`, `tabs`, `dialog`, `drawer`, `booking-panel`, `customer-panel`, `walkin-form`, `status-badge`, `empty-state`, `toast`, `confirm-modal`. Satu panel booking untuk semua halaman.
- **Aturan penyajian dijaga:** tidak ada kueri di Blade; menu disaring di server.
- **Kelas bersama dipakai luas:** `bq-btn` 230, `bq-input` 101, `bq-panel` 74, `bq-num` 73.

### 3.2 Celah

| # | Fakta | Akibat |
|---|---|---|
| G-1 | Komponen `x-ui.*` (12 buah) dipakai **17 kali di 3 berkas** (`bookings`, `customers`, `booking-panel`). 32 view halaman lain memakai 0. | Dua cara menulis UI. README menyebut "every action" memakai `x-ui.button`, kenyataannya tidak. |
| G-2 | Di view halaman: **188 kontrol formulir mentah** dan **135 `<button>` mentah**, diberi kelas lewat skrip sapuan BQ-161 §7. | Label, petunjuk, galat dan `aria-invalid` ditulis ulang per halaman, atau tidak ada. Tinggi dan susunan formulir tidak terkunci (contoh: kotak 160 px di "Harga tanggal khusus"). |
| G-3 | `x-ui.table` dan `x-ui.day-rail` **tidak dibuat**; tidak ada kelas `bq-table`. 6 tabel ditulis tangan; kartu HP ditulis per halaman. | Sumber langsung temuan kepadatan di Booking, Pelanggan, Transaksi, Laporan. |
| G-4 | **Skala huruf BQ-161 tidak dipasang** ("Type sizes stay on Tailwind's steps"). `text-sm` 457, `text-xs` 271, ukuran lain 113. | Hierarki masih lemah; ini penyebab "semua angka sama besar". |
| G-5 | 21 pemakaian ukuran `text-[8px]`–`text-[11px]`. | Melanggar aturan "minimal 12 px" di README sendiri. |
| G-6 | Tidak ada skala kepadatan. Jarak diisi per halaman (`p-4` 75, `p-5` 24, `p-3` 23, `py-3` 64, `py-2` 55). | Halaman kerja selonggar halaman pemasaran. |
| G-7 | Pola yang belum punya komponen: bagian formulir, strip angka, pasangan label–nilai, banner/peringatan, menu "⋯", kontrol segmen, lembar bawah (sheet). | Tiap halaman merakit sendiri (`<details>` 8, SVG sebaris 44). |
| G-8 | View besar: `services` 548 baris, `payments` 434, `booking-page` 405, `analytics` 328, `vouchers` 312. | Sulit dimigrasi dan diulas; satu berkas memuat beberapa tab dan dialog. |
| G-9 | "Restraint rules" di README masih berstatus **usulan**, belum masuk `.ai/rules/frontend.md`. Tidak ada uji yang menjaga pemakaian komponen. | Agent berikutnya tidak wajib mengikuti sistem; penyimpangan akan kembali. |
| G-10 | Tidak ada pembanding visual. Screenshot diambil skrip Playwright Python di luar repo. Uji komponen: 8. | Regresi tampilan hanya tertangkap mata. |
| G-11 | BQ-161 menutup dengan **uji HP (375 px) dan uji Kasir belum dilakukan**. | Dua pemakai utama (HP di meja depan, Kasir) belum pernah diverifikasi. |

### 3.3 Arsitektur sasaran

Empat lapis; lapis atas hanya boleh memakai lapis di bawahnya.

```text
4. Halaman        resources/views/owner/*            menyusun pola, tanpa kelas tampilan sendiri
3. Pola           x-owner.* (page-header, tabs, booking-panel, …) + pola baru
2. Primitif       x-ui.* (button, field, input, panel, stat, badge, list-row, …)
1. Token          app.css: warna, huruf, jarak/kepadatan, radius, bayangan
```

Yang perlu ditambah:

- **Token:** skala huruf BQ-161 (Display, Title, Heading, Body, Small) sebagai kelas bernama; dua kepadatan (`nyaman` untuk formulir, `padat` untuk daftar dan tabel).
- **Primitif:** `x-ui.table` (dengan bentuk daftar di HP), `x-ui.kv` (label–nilai), `x-ui.alert` (banner), `x-ui.menu` ("⋯"), `x-ui.segmented`, `x-ui.checkbox`, `x-ui.radio-card`.
- **Pola:** `x-ui.form-section` (judul, kolom, baris simpan), `x-ui.stat-strip` (satu angka utama + pendukung), `x-ui.day-rail` (diangkat dari Beranda dan Kalender), `x-ui.sheet` (lembar bawah HP).
- **Penjaga:** uji arsitektur dengan daftar pengecualian yang mengecil (pola yang sama dengan fase 6): `<button>` atau kontrol formulir tanpa komponen, ukuran huruf arbitrer, hex, `<table>` di luar `x-ui.table`.

---

## 4. Keputusan yang diperlukan sebelum ada pekerjaan

### Tech Lead

| # | Keputusan | Rekomendasi |
|---|---|---|
| K-1 | Kapan dikerjakan terhadap rilis (D-018, BQ-158)? | Sebelum rilis hanya Tahap 0 dan Tahap 1. Tahap 2 dan seterusnya setelah v1.0.0, supaya uji regresi `AC-*` tidak berulang. |
| K-2 | Standar penulisan UI: komponen atau kelas? | Komponen wajib untuk tombol, bidang formulir, panel, angka, tabel, daftar. Kelas `bq-*` hanya boleh di dalam templat Alpine yang tidak bisa memakai komponen. Catat di `.ai/rules/frontend.md`. |
| K-3 | Pasang skala huruf BQ-161 yang dulu dilewati? | Ya, sebagai kelas bernama khusus portal owner, sehingga CSS landing tidak tersentuh (alasan pelewatan dulu). |
| K-4 | Adopsi "restraint rules" ke `.ai/rules/frontend.md`? | Ya, bersama K-2. |
| K-5 | Pecah view besar per tab menjadi partial? | Ya, saat halaman itu dimigrasi (bukan pekerjaan terpisah), dengan uji halaman yang ada sebagai penjaga. |
| K-6 | Pembanding visual masuk repo? | Skrip screenshot masuk repo sebagai alat pengembang (bukan CI dulu); CI menyusul bila terbukti stabil. Menambah dependensi butuh persetujuan Anda. |

K-2, K-3, K-4 menyentuh dokumen otoritas (`.ai/rules`, `owner-portal.md` §9), jadi jalur berat.

### Product Lead (calon entri `D-NNN`, belum ditulis)

| Calon | Isi | Label | Kapan |
|---|---|---|---|
| P-1 | Beranda: panduan menjadi kartu kecil setelah booking pertama; banner slot digabung ke langkah panduan | B | Bisa sebelum rilis; mengubah §8.1 dan AC-BRD-05 |
| P-2 | Booking lewat: tandai selesai sekaligus banyak; opsi selesai otomatis | C (otomatis: aturan domain) | Setelah rilis |
| P-3 | Laporan: pembanding periode sebelumnya | C | Setelah rilis |
| P-4 | Kalender: kolom per unit | C | Setelah rilis |
| P-5 | Paket perubahan isi kecil: satu lencana status di daftar Booking, ganti nama tab "Pengaturan" di Pembayaran, kartu ketiga Pelanggan, konflik Unit hanya yang mendatang, gabung tab Link & Materi | B | Setelah rilis |
| P-6 | Ajukan ulang D-021: slot otomatis, saran di Laporan | C | Setelah rilis, sesuai D-021 |
| P-7 | Sapa pelanggan lama, sumber booking, jam sepi → promo, gagal bayar, ringkasan mingguan email | C | Setelah P-2 sampai P-4 terbukti |

---

## 5. Tahapan

Tiap tahap punya gerbang. Jangan mulai tahap berikutnya sebelum gerbangnya lolos.

### Tahap 0: Verifikasi dan bahan (sebelum rilis)

Tujuan: semua tahap berikut berdiri di atas fakta, bukan screenshot data contoh.

1. Cek tiap temuan `review.md` terhadap kode; tandai salah, sudah ada, atau benar (`NEXT.md` 2.1).
2. Jawab empat fakta yang tampak seperti bug: "Menunggu bayar" lewat sesi, saldo tanpa mutasi, angka promo tidak cocok, menu "Lainnya" tembus pandang. Yang nyata menjadi item bug tersendiri, bukan bagian pekerjaan UI.
3. Lengkapi paket tinjauan: seed dengan booking "hari ini" dan panduan selesai, akun Kasir dan Manajer, semua halaman pada 375 px, semua formulir dan dialog, keadaan kosong, galat validasi, paket Small, langganan berakhir.
4. Selesaikan sisa BQ-161: uji HP dan uji Kasir.

Gerbang: daftar temuan terverifikasi + daftar bug + paket screenshot lengkap. Jalur ringan, kecuali bug yang menyentuh pembayaran atau booking.

### Tahap 1: Penghalang rilis (sebelum rilis)

Hanya yang membuat pemilik buntu atau salah baca, tanpa mengubah §8:

- kontak Bantuan terisi; kartu Google Calendar yang belum siap tidak jadi jalan buntu;
- tombol "Tambah rekening" di alasan pencairan; tombol "Buka slot" di banner Jam Buka & Harga;
- kotak 160 px di "Harga tanggal khusus"; tab Booking di HP tergulir ke "Perlu tindakan"; menu "Lainnya" bila terbukti rusak;
- teks Inggris di paginasi; batas waktu bayar di panel (sudah diwajibkan §8.4).

Gerbang: lolos uji HP dan Kasir. Jalur ringan, satu PR. Hasilnya ikut uji regresi BQ-158.

### Tahap 2: Fondasi sistem (setelah rilis)

1. Keputusan K-2 sampai K-6 dicatat (aturan frontend, §9, README komponen).
2. Token huruf dan kepadatan.
3. Primitif dan pola baru dari 3.3, masing-masing dengan uji render per varian.
4. Halaman contoh komponen (hanya lokal) sebagai acuan visual tunggal.
5. Uji penjaga dengan daftar pengecualian berisi semua pelanggaran saat ini.

Gerbang: tidak ada halaman yang berubah tampilan; penjaga hijau; halaman contoh disetujui Anda. Jalur berat, satu work item. Halaman contoh inilah "gambar sasaran" yang tidak ada di `review.md`.

### Tahap 3: Migrasi per alur (setelah Tahap 2)

Satu PR per bundel, urut frekuensi pakai. Tiap bundel: pindah ke komponen, terapkan temuan A terverifikasi untuk halaman itu, pecah view besar, kurangi daftar pengecualian penjaga.

| Bundel | Halaman | Catatan |
|---|---|---|
| 3a Harian | Beranda, Kalender, Booking, panel booking, formulir catat booking, Pelanggan, panel pelanggan | HP dulu. Paling bernilai; kerjakan pertama. |
| 3b Penyiapan | Layanan (+ Kategori, Add-on), Unit, Jam Buka & Harga (4 tab) | Paling banyak kontrol mentah (`services` 40, `slots` 15). |
| 3c Uang | Pembayaran (5 tab), Laporan (5 tab), Promo, Ulasan | `payments` 20 kontrol mentah. |
| 3d Jarang | Halaman Booking (6 tab), Pengaturan (6 tab), Paket & Tagihan, checkout, Notifikasi, Bantuan | Terakhir. |

Gerbang tiap bundel: screenshot sebelum/sesudah pada 375 dan 1366 px sebagai Pemilik dan Kasir; semua `AC-*` halaman itu tetap lolos; tidak ada pengecualian penjaga tersisa untuk berkas bundel itu. Jalur ringan selama §8 tidak berubah; bila berubah, tunggu `D-NNN`.

### Tahap 4: Bukti dari pemilik

Tiga sampai lima pemilik, lima tugas (catat booking, buka slot minggu depan, cari pelanggan, cairkan saldo, baca laporan). Catat waktu dan titik macet. Hasilnya menentukan urutan P-2 sampai P-7.

### Tahap 5: Perubahan isi dan fitur

Tiap calon P menjadi `D-NNN`, lalu work item jalur berat setelah disetujui (`product.md` dan `requirements.md` diperbarui sebelum kode). P-2 otomatis dan P-6 menyentuh aturan booking dan jadwal.

### Ketergantungan

```text
Tahap 0 ─► Tahap 1 ─► [rilis v1.0.0, BQ-158] ─► Tahap 2 ─► 3a ─► 3b ─► 3c ─► 3d
   │                                                          │
   └─► bug nyata (item sendiri)                               └─► Tahap 4 ─► Tahap 5
P-1 boleh berjalan sejajar sejak Tahap 0; P-2…P-7 menunggu rilis.
```

---

## 6. Calon work item

Nomor dicek ulang saat dibuat (BQ-162 bebas pada 16 Nov 2026).

| Calon | Isi | Jalur | Bergantung |
|---|---|---|---|
| BQ-162 | Tahap 0 + Tahap 1: verifikasi temuan, sisa uji BQ-161, penghalang rilis | ringan (dicatat sebagai item karena `NEXT.md` memintanya) | — |
| BQ-163 | Tahap 2: fondasi sistem UI, aturan, penjaga | berat, Tech Lead | rilis |
| BQ-164 … 167 | Bundel 3a, 3b, 3c, 3d | ringan (PR sebagai catatan) | BQ-163 |
| per bug | Tiap fakta yang terbukti bug | menurut domain | Tahap 0 |
| setelah `D-NNN` | P-1 sampai P-7 | berat | keputusan Product Lead |

---

## 7. Risiko

| Risiko | Penangkal |
|---|---|
| Uji regresi rilis berulang karena UI terus berubah | K-1: bekukan UI di luar Tahap 1 sampai rilis |
| Restyle kedua mengulang pola BQ-161: kelas disapu skrip, komponen tidak dipakai | Gerbang Tahap 3: daftar pengecualian penjaga harus kosong per bundel |
| Uji fitur memeriksa markup atau label lama | Aturan BQ-161 tetap: ubah hanya pernyataan markup, pertahankan yang dibuktikan |
| Perubahan isi terselip dalam PR tampilan | Tiap item B/C wajib `D-NNN`; PR tampilan menolak perubahan §8 |
| Keputusan desain tanpa bukti pemakai | Tahap 4 sebelum Tahap 5 |
| Agent pelaksana menafsirkan kata-kata secara berbeda | Halaman contoh komponen (Tahap 2) sebagai acuan tunggal |

---

## 8. Yang saya butuhkan dari Anda

1. Jawaban K-1 (sebelum atau sesudah rilis). Ini menentukan seluruh urutan.
2. Jawaban K-2 (komponen wajib atau kelas cukup).
3. Izin memulai Tahap 0: pengecekan temuan terhadap kode hanya membaca; melengkapi seed dan screenshot mengubah data lokal.
