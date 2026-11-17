# Handoff: dari prototipe ke portal owner asli

Untuk pelaksana proyek asli (BQ-161 dan sesudahnya). Prototipe ini acuan tampilan dan perilaku, **bukan kode untuk disalin**: tidak memakai Blade, Tailwind atau token `bq-*`, dan semua datanya statis. Aturan repo (`AGENTS.md`, `.ai/rules/frontend.md`, `docs/owner-portal.md`) tetap berlaku penuh.

## Cara membaca prototipe

- Jalankan `preview_start` nama `bookqu-prototype`, buka `http://127.0.0.1:8765/#/beranda`.
- Tombol "Prototipe" (kanan bawah): peran, paket, keadaan (Berjalan, Baru, Berakhir, Memuat), kepadatan, sidebar, catatan perubahan.
- Sakelar **Catatan perubahan = Hidup** menempelkan label pada setiap elemen yang berubah: **A** tampilan saja, **B** mengubah isi §8 (perlu Product Lead), **C** fitur atau data baru (perlu Product Lead; skema atau aturan domain juga Tech Lead), **teknis** temuan teknis.
- `#/sistem` adalah katalog semua komponen; aturan "satu per layar" tidak berlaku di halaman itu.

## Komponen yang perlu ada di `x-ui`

Satu fungsi di `js/ui.js` = satu komponen. Nama di kiri adalah nama prototipe.

| Kelompok | Komponen |
|---|---|
| Dasar | `btn` (utama, kedua, polos, bahaya, bahaya-isi, kecil, ikon), `lencana` + `status` (lencana hanya untuk Menunggu bayar) + `titikStatus`, `durasi`, `batangMini`, `avatar`, `salin`, `delta`, `titikWarna` |
| Formulir | `bidang` (label, petunjuk, galat), `input` (awalan/akhiran), `pilih`, `teksarea`, `centang`, `sakelar`, `radioKartu`, `segmen`, `chipJam`, `formBagian`, `simpanBilah` |
| Susunan | `kepala`, `tabs`, `alat`, `panel`, `angka`, `angkaPendukung`, `kesimpulan`, `perluBlok`, `kv`, `tabel` (subgrid; opsi `utama`, `nowrap`, `bungkus`, `aksi`, `grup`, `redup`, `bisaPilih`, `hpSub`; dua lapis di HP), `catatanTabel`, `aksiIkon`, `dua`, `lr`, `filter` (+ lembar filter HP), `massal`, `hal`, `banner`, `kosong`, `kerangka`, `kunci`, `lipat`, `kartu`, `kisiKartu` |
| Waktu dan data | `rel` + `ri` (rel hari), `kisiHari`, `minggu`, `bulan`, `tglNav`, `peta`, `kuota`, `batang`, `tiang`, `jamBuka` + `jamBukaHp`, `langkah`, `pratinjauHp` |
| Lapisan (di `app.js`) | laci, dialog, lembar (termasuk lembar penuh), menu "⋯", toast (dengan tautan), konfirmasi; kunci fokus, Esc, fokus kembali ke pemicu, panah di menu, tab dan segmen |
| Bagian bersama (`bersama.js`) | slip booking, Catat booking (satu formulir untuk semua pintu), pindah jadwal, batalkan / ajukan pembatalan, putuskan refund, panel pelanggan, sapa pelanggan |

Token di `css/tokens.css` (warna, huruf, jarak, radius, kepadatan) adalah sumber nilai untuk token `bq-*`; nama dan nilainya bisa dipetakan satu-satu.

## Aturan keterbacaan (wajib di portal asli)

Tambahan sesi 4: tablet 768–1199 px memakai rel ikon; di bawah 1024 px isi satu kolom; teks terpotong selalu punya `title`; tindakan baris terlihat tanpa sorot di layar sentuh; galat simpan tidak menutup lapisan dan tidak menghapus isian.


Lengkapnya di `../riwayat/rencana-keterbacaan-prototipe.md` ("Aturan desain baru").
- Isi teks minimal 13 px.
- Baris tabel 52 px (satu lapis) / 60 px (dua lapis); kepadatan bawaan Nyaman.
- Maksimal 5 kolom data; dua lapis hanya di kolom utama.
- Status biasa berupa titik + teks; lencana hanya untuk yang perlu tindakan.
- Nilai seragam ditulis sekali di atas tabel.
- Kode, email dan telepon pindah ke slip/panel.
- Di HP setiap baris paling banyak dua lapis.

## Keputusan isi dan fitur

**Semua butir di bawah disetujui lewat D-025 (17 Nov 2026)** dan dibangun di branch BQ-162. Daftar resmi dengan ID, data dan sesinya: `../KEPUTUSAN.md`. Ringkasan asal (sebelum disetujui):

**Product Lead (lewat `D-NNN`):**
- B: urutan dan isi Beranda (Perlu Anda di atas, satu angka utama + pembanding, panduan jadi kartu), slot kosong mendatang sebagai ajakan di Kalender, satu lencana di Booking, ringkasan Pelanggan "Lama tidak datang", "slot terbuka sampai" per layanan, konflik unit hanya mendatang, pratinjau Halaman Booking berisi data asli, tab Pembayaran "Cara kerja", kesimpulan per tab Laporan, istilah "Tambahan" (K3).
- C: slot otomatis (ditunda D-021), selesai otomatis dan centang banyak, pembanding periode di Laporan, kalender per unit, sapa pelanggan lama, ringkasan mingguan email, sumber booking per kanal, promo jam sepi, hari pesan di muka, daftar belum jadi membayar.
- B (keterbacaan): kartu ringkasan Pelanggan dihapus, hitungan cukup di tab (K8).
- Harga paket di Paket & Tagihan mengikuti `docs/product.md` (Small Rp149.000, Medium Rp299.000, Pro Rp499.000); 6 dan 12 bulan dihitung tanpa potongan. Isi fitur per paket masih contoh.

**Tech Lead:** butir C yang menyentuh skema atau aturan domain (slot otomatis, selesai otomatis, sumber booking, promo per jam).

## Yang sengaja tidak diperagakan

Penyegaran Beranda tiap 60 detik, pilihan urutan di Booking, checkout paket lengkap (disederhanakan jadi satu dialog), unggah berkas sungguhan, QR sungguhan, simulator harga yang menghitung.
