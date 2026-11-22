# Rencana perbaikan prototipe "Papan Jadwal"

Ditulis 17 Nov 2026, dari kritik objektif atas prototipe di `prototype/`. Tiga sesi, berurutan; tiap sesi bisa dikerjakan agent baru dengan membaca berkas ini, `prototype/README.md` dan rencana awal `C:\Users\agath\.claude\plans\bubbly-crafting-lemon.md`.

## Batasan yang berlaku di semua sesi

- Hanya mengubah ``. Tanpa commit, tanpa PR, tanpa subagent.
- Pratinjau lewat `preview_start` nama `bookqu-prototype` (port 8765).
- Pemeriksaan hemat: setelah tiap kelompok kerja, jalankan skrip galat + gulir mendatar untuk semua rute; potret hanya layar yang diubah tata letaknya. Pemeriksaan visual penuh dilakukan sekali, di sesi 3.
- Halaman tidak menulis gaya; komponen baru masuk `ui.js` + `components.css` dan tampil di `#/sistem`.

## Keputusan sebelum sesi 1 (bawaan yang saya usulkan)

| # | Pertanyaan | Bawaan |
|---|---|---|
| K1 | Tombol "Catat booking" di bilah atas pada halaman non-harian | Jadi tombol **kedua** di semua halaman kecuali Beranda, Kalender, Booking, Pelanggan. Aturan satu tombol utama per layar berlaku ketat. |
| K2 | Nada keempat lencana (lilak) untuk "Sedang berlangsung", "Baru", "Terisi" | **Dipertahankan** dan dicatat di README sebagai nada "terpilih/sekarang", bukan status. |
| K3 | Penggantian istilah "Add-on" → "Tambahan", penghapusan lencana pembayaran di Booking | Tetap, diberi label **B** di catatan perubahan. |
| K4 | Tab "Landing page" | Diganti **"Halaman depan"**, label A. |

---

## Sesi 1: fondasi, data dan desain sistemik — selesai 17 Nov 2026 (ringkasan di `prototype/README.md`)

**Tujuan:** prototipe menaati aturan gayanya sendiri, angkanya saling cocok, dan kelemahan desain yang berulang diperbaiki di tingkat komponen.

1. **Token.**
   - Pindahkan 19 kode hex di `components.css` ke `tokens.css` sebagai token bernama (misalnya `--lilak-garis`, `--kunyit-tua`, `--peta-1…4`). Alat bantu prototipe (`.proto`, `.cat`) boleh tetap di blok tersendiri dengan komentar "bukan bagian desain".
   - Tambahkan token huruf yang kurang (`--t-label` 600 13/18, `--t-tombol`, `--t-kecil-tebal`, `--t-angka-sedang` 22/26) dan ganti 40 deklarasi px langsung.
   - Hapus 7 gradasi: tempat gambar kartu dan sampul pratinjau jadi blok polos berwarna token dengan ikon gambar; QR jadi kotak bergaris dengan tulisan "QR".
   - Kunyit hanya untuk "sekarang" dan "perlu Anda": warna kategori dan warna tema tanpa kunyit; titik status menunggu memakai token `--kunyit-tua`.
2. **Data saling cocok** (`data.js` dan halaman).
   - Rating: sebaran diubah supaya rata-rata benar-benar 4,6 dari 23 (misalnya 17/4/1/1/0 = 4,61); `#/sistem` memakai angka yang sama.
   - Laporan: daftar per layanan lengkap 10 layanan, jumlahnya = Rp13.600.000 bersih + refund; jumlah booking per layanan = 57.
   - Promo: "dipakai" memakai angka periode, kumulatif hanya di batang kuota kartu.
   - Hapus baris "Promo −Rp0"; satu booking contoh benar-benar memakai promo dengan potongan nyata.
   - Paket: "Turun ke Small" menampilkan peringatan bila pemakaian melebihi batas.
3. **Aturan pemakaian.**
   - K1 diterapkan (bilah atas menerima penanda halaman harian).
   - Banner tanpa tindakan di laci dan dialog diganti teks penjelas biasa, atau diberi tindakan.
   - Laporan → Ringkasan: dua angka utama saja; baris tingkat jadi angka pendukung.
   - Paket & Tagihan: angka utama = sisa hari ("167 hari lagi"), nama paket jadi judul.
4. **Desain komponen.**
   - Kisi hari: judul kolom dua baris (nama penuh terbungkus, tanpa potong), lebar kolom minimum 152 px dengan gulir mendatar di dalam panel, blok ≤ 60 menit tampil satu baris "jam nama".
   - Kisi kartu: kartu tanpa gambar dan bergambar tidak dicampur dalam satu baris; tampilan kisi Layanan memakai tempat gambar polos untuk semua.
   - Laporan untuk Small: isi buram diganti cuplikan ringan (dua angka palsu + batang), bukan seluruh halaman.
   - Menu "Lainnya" HP setinggi penuh.
   - Baris Booking di HP tiga baris (kode hanya di slip).
5. **`#/sistem` diperbarui:** kisi hari, kisi minggu, kartu (bergambar, ringkas, besar, mati), lipat, kesimpulan, pratinjau HP, menu Lainnya, navigasi tanggal, bilah alat, kunci paket; contoh angka disamakan dengan data.

**Selesai bila:** `grep -E "#[0-9A-Fa-f]{6}|[0-9]+px/|gradient"` di `components.css` hanya menemukan blok alat prototipe; tidak ada angka yang bertentangan antar-halaman; potret `#/sistem`, Kalender hari, Layanan kisi dan Laporan (Small) pada 1366 dan 375 px.

---

## Sesi 2: isi kontrak yang kurang, formulir, keadaan — selesai 17 Nov 2026 (ringkasan dan daftar periksa kontrak di `prototype/README.md`)

**Tujuan:** setiap butir wajib `docs/owner-portal.md` §8 punya layar, dan tidak ada tindakan penting yang hanya berupa toast.

1. **Beranda:** Aksi cepat (Catat booking, Tambah layanan, Buat promo untuk Medium+, Undang anggota untuk Pemilik Medium+) di kolom samping, di bawah link; prioritas banner §8.1 diterapkan (Creative Music Video < 7 hari memunculkan banner "Buka slot" bila tidak ada banner berprioritas lebih tinggi). Kasir tidak melihat "Refund menunggu keputusan".
2. **Kalender:** slot berkapasitas > 1 menampilkan "3/8"; harga di slot kosong tetap ada.
3. **Catat booking:** bidang peserta (muncul bila kapasitas > 1, dengan sisa kursi), email opsional, pencarian pelanggan lama yang mengisi nama, HP dan email.
4. **Slip booking:** contoh booking multi-slot (dua sesi, satu kode); setiap baris riwayat menyebut pelaku dan peran.
5. **Pelanggan:** kolom email di desktop; panel pelanggan menampilkan riwayat pembayaran.
6. **Pembayaran:** dialog pencairan tiga langkah (jumlah → rekening → konfirmasi) dengan kunci permintaan; contoh "Cairkan saldo" mati dengan alasan dan tombol "Tambah rekening" (muncul saat sakelar Keadaan = Baru atau saat rekening kosong).
7. **Formulir yang masih toast:** tambah/ubah unit, kategori (dengan urutan), tambahan (stok atau tanpa batas), aturan jam ramai, tanggal khusus, ubah harga satu slot, tutup slot dari Kalender. Satu laci per formulir, memakai `formBagian`/`bidang` yang sudah ada.
8. **Notifikasi:** hapus satu dengan konfirmasi.
9. **Keadaan:**
   - Langganan berakhir (§12): banner di semua halaman yang membuat sesuatu baru; Catat booking, Buka slot sekaligus, Tambah layanan dan Buat promo mati dengan alasan; Pembayaran tetap penuh.
   - Galat formulir di halaman nyata: Catat booking tanpa nama, Tutup tanggal dengan booking aktif, kode promo ganda.
   - Kerangka memuat di Beranda (jadwal) dan Booking (tabel) lewat sakelar Keadaan = "Memuat" (opsi baru, alat prototipe).
10. **Catatan perubahan:** setiap elemen yang berubah menurut peta README diberi label A/B/C/teknis; yang berasal dari D-021 bertanda "ditunda D-021".

**Selesai bila:** daftar periksa kontrak (ditulis di README sebagai tabel butir §8 → layar) tidak punya baris kosong; skrip galat + gulir bersih untuk semua rute dalam empat kombinasi sakelar (bawaan; Kasir + Small; Baru; Berakhir); potret hanya formulir dan dialog baru, satu ukuran masing-masing.

---

## Sesi 3: verifikasi penuh dan aksesibilitas — selesai 17 Nov 2026 tanpa potret (diganti pemeriksaan DOM, atas permintaan pengguna); hasil di `prototype/README.md`, handoff di `prototype/HANDOFF.md`

**Tujuan:** memenuhi bagian Verifikasi rencana awal, lalu menyerahkan prototipe sebagai acuan tunggal.

1. **Papan ketik:**
   - Dialog, laci dan lembar mengunci fokus (Tab berputar di dalam), Esc menutup, fokus kembali ke pemicu.
   - Menu "⋯": panah atas/bawah, Home/End, Esc.
   - Tab halaman dan segmen bisa dipilih dengan panah; fokus selalu terlihat (termasuk di sidebar terong).
   - Uji manual per halaman: cangkang, slip, Catat booking, satu formulir laci, satu menu.
2. **Kontras:** hitung semua pasangan teks-latar termasuk token baru dari sesi 1, lencana, banner, peta jam, sidebar terong; perbaiki yang < 4,5 (atau < 3 untuk teks besar).
3. **Potret penuh:** setiap halaman dan tab pada 1366 dan 375 px, plus semua dialog/laci/lembar sekali; periksa terhadap aturan gaya (satu tombol utama, satu banner, satu angka utama, tanpa teks < 12 px, tanpa potongan yang menghilangkan informasi). Catat temuan, perbaiki, potret ulang hanya yang berubah.
4. **Grep konsistensi** di `components.css`, `pages.css`, `js/pages/`: warna, ukuran huruf, radius, gradasi.
5. **Penutup:**
   - README: status, peta temuan → layar, daftar periksa kontrak, hasil verifikasi yang akurat (tanpa klaim yang tidak diuji).
   - Potret akhir ke `shots/prototipe/` dengan nama per rute.
   - Handoff singkat untuk pelaksana proyek asli: komponen `x-ui` yang perlu dibangun (dari daftar `ui.js`), dan butir B/C yang menunggu keputusan Product Lead.

**Selesai bila:** semua butir Verifikasi di rencana awal terpenuhi dan tercatat; tidak ada temuan terbuka kecuali yang menunggu keputusan B/C.

---

## Perkiraan beban

| Sesi | Isi | Beban |
|---|---|---|
| 1 | Token, data, aturan, desain komponen, `#/sistem` | Sedang–besar (banyak sentuhan CSS, sedikit halaman) |
| 2 | Isi kontrak, 7 formulir, keadaan, label | Besar (paling banyak kode halaman) |
| 3 | Papan ketik, kontras, potret penuh, penutup | Sedang (paling banyak pemeriksaan) |
