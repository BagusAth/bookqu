# Rencana perbaikan keterbacaan prototipe "Papan Jadwal"

Ditulis 17 Nov 2026 dari audit tampilan semua page (ukur DOM di 1366 px dan 375 px). Tujuan: setiap page **mudah dibaca dan lega**, terutama tabel. Hanya tampilan; logika dan data di belakang tidak menjadi soal di prototipe ini.

Tiga sesi, berurutan. Sesi 1 membangun sistemnya dan membuktikannya di Booking; sesi 2 dan 3 menerapkannya ke page lain.

## Batasan semua sesi

- Hanya mengubah ``. Tanpa commit, PR, atau subagent.
- Halaman tidak menulis gaya: perubahan masuk `tokens.css`, `components.css`, `ui.js`, lalu halaman memakai komponennya.
- Pemeriksaan hemat: skrip teks (`padat.py`, `uji3.py` (sekarang di `alat/`), galat + gulir mendatar). Potret paling banyak 2 per sesi, JPEG kecil, hanya page yang tata letaknya berubah besar.

## Masalah yang diselesaikan (dari audit)

| # | Masalah | Angka sekarang |
|---|---|---|
| M1 | Baris 40 px berisi dua lapis teks | 100% tabel utama |
| M2 | Teks kecil (12,5 px) terlalu banyak | 40–55% desktop, 56–75% HP |
| M3 | Kolom terlalu banyak | Pelanggan 8, Booking 7, Layanan/Transaksi/Tambahan 6 |
| M4 | Teks penting terpotong | Pelanggan 11 sel, Slot 7, Transaksi 7, Unit 6 |
| M5 | Lencana di setiap baris | ±1,0 per baris di hampir semua tabel |
| M6 | Nilai sama diulang | "Sampai 14 Des" 9/10, Kapasitas/Peserta "1" |
| M7 | Kode/ID dan kontak sebagai baris kedua | Booking, Transaksi, Pelanggan |
| M8 | Tombol yang sama di setiap baris | 12× "WhatsApp" |
| M9 | Baris HP 64–91 px dengan 4–6 potong teks | Booking, Transaksi, Unit |
| M10 | Page HP terlalu panjang | Beranda 2624 px, Galeri 2294, Paket 2011, Jam Buka 1877 |

## Keputusan bawaan (dipakai kecuali Anda ubah)

| # | Keputusan | Label |
|---|---|---|
| K5 | Kepadatan bawaan jadi **Nyaman**; "Padat" tetap ada sebagai pilihan | A |
| K6 | Keadaan normal tidak diberi lencana (Aktif, Berhasil, Lunas, Tetap, Terkonfirmasi, Lewat) | A |
| K7 | Kode booking, kode order, email dan telepon tidak tampil di daftar; tetap bisa dicari, tampil di slip/panel | A |
| K8 | Kartu ringkasan Pelanggan dihapus; angkanya sudah ada di tab | B |
| K9 | Lencana "Populer" tidak tampil di tabel Layanan (tetap di tampilan kisi) | A |
| K10 | Durasi ditulis "1 jam", "1,5 jam", "2 jam", bukan "120 mnt" | A |

---

## Aturan desain baru (acuan semua sesi)

### Tipografi
- `--t-kecil` naik dari 12,5 px ke **13 px/18 px**. Teks terkecil di UI 13 px; pengecualian hanya lencana, angka hitung tab dan label nav bawah HP (12 px).
- Isi sel desktop 14 px; kolom utama 14 px tebal 600. Isi utama baris HP 15 px.
- Kepala tabel 13 px tebal 600 warna redup, tinggi 40 px, **lengket** di atas saat tabel digulir.
- Angka, tanggal dan jam tidak pernah patah baris (`nowrap`, angka sejajar).

### Tabel desktop
- **Tinggi baris:** Nyaman 52 px (satu lapis) / 60 px (dua lapis); Padat 44 / 52 px. Padding vertikal minimal 10 px.
- **Paling banyak 5 kolom data**, ditambah kolom pilih dan kolom tindakan bila perlu.
- **Dua lapis hanya di kolom utama.** Kolom lain satu lapis. Subteks kolom utama berisi satu keterangan yang berguna, bukan kode.
- **Lebar kolom ikut isi.** `ui.tabel` pindah ke CSS `subgrid`: kolom utama `minmax(220px, 1fr)`, kolom lain `auto`. Nama tidak boleh terpotong; teks panjang di kolom teks (alasan, keterangan) boleh turun ke 2 baris.
- **Status:** dua bentuk saja.
  - *Titik + teks* (13 px) untuk status biasa yang memang beda antarbaris (mis. Terkonfirmasi, Sedang berlangsung).
  - *Lencana* hanya untuk yang perlu perhatian (Menunggu bayar, Minta refund, Belum ditutup, Gagal, Nonaktif, Hampir habis).
  - Keadaan normal yang sama di semua baris tidak ditampilkan sama sekali.
- **Nilai seragam:** tulis sekali sebagai catatan di atas tabel ("Semua slot terbuka sampai 14 Des"), di baris hanya pengecualian.
- **Tindakan baris:** satu tombol ikon 32 px (WhatsApp, ⋯, unduh) yang redup dan menjadi penuh saat baris disorot atau difokus. Tombol berteks hanya di tab yang memang daftar tugas (Perlu tindakan, Lama tidak datang).
- **Kelompok per tanggal:** baris kepala kelompok "Hari ini · Senin 16 Nov · 4 booking" (13 px tebal, latar kabut), menggantikan tanggal yang diulang di tiap baris.
- **Baris lewat/nonaktif:** diredupkan (`--teks-mati`), tanpa lencana.

### Tabel di HP (≤ 767 px)
- **Paling banyak dua lapis, tinggi ±64 px.**
  - Lapis 1: utama (15 px, 600, boleh 1 baris terpotong dengan judul) dan akhir (jumlah/angka, 15 px, rata kanan).
  - Lapis 2: satu kalimat redup 13 px, beberapa nilai digabung dengan " · ", dan status sebagai titik di depannya.
- Kolom "awal" hanya untuk jam (Booking, Slot) atau avatar (Pelanggan, Tim).
- Unit, kode, email dan telepon tidak tampil.
- Strip tab bisa digulir mendatar dengan pudar di tepi, dan tab aktif digulir ke tampilan.

### Daftar (`ui.lr`) dan kartu
- Judul 14 px tebal, satu keterangan 13 px, tinggi 56–60 px; waktu di kanan.
- Kartu: judul, satu angka atau nilai utama, satu baris keterangan, kaki opsional. Rincian lain pindah ke laci detail.

### Komponen yang ditambah atau diubah (`ui.js`)
- `ui.tabel`: opsi kolom `utama`, `bungkus` (boleh 2 baris), `nowrap`, `aksi` (tombol ikon); opsi baris `redup`, `grup` (baris kepala kelompok), `bisaPilih` (centang hanya di baris yang layak) dan "pilih semua" di kepala. Di HP, sel `sub` digabung jadi satu kalimat.
- `ui.titikStatus(s)`: titik + teks. `ui.status(s)` mengembalikan lencana hanya untuk status perhatian, selain itu `titikStatus` atau kosong.
- `ui.catatanTabel(teks, tautan)`: satu baris catatan redup di atas tabel.
- `ui.durasi(menit)`: "1 jam", "1,5 jam".
- `ui.batangMini(isi, total)`: batang 80 px + "6/14 jam".
- Semua varian tampil di `#/sistem`.

### Sasaran terukur (dicek dengan `padat.py`)

| Ukuran | Sasaran |
|---|---|
| Tinggi baris desktop (Nyaman) | ≥ 52 px |
| Teks < 14 px per page, desktop / HP | ≤ 30% / ≤ 40% |
| Teks < 13 px (di luar lencana, hitung, nav bawah) | 0 |
| Kolom data per tabel | ≤ 5 |
| Sel terpotong di kolom utama | 0 |
| Lencana per baris | ≤ 0,3 (kecuali tab tugas) |
| Tinggi baris HP | 60–68 px |
| Tinggi page HP terpanjang | ≤ 1800 px |

---

## Sesi 1: sistem tabel, tipografi dan Booking — selesai 17 Nov 2026

> Hasil: Booking desktop 5 kolom, baris 52 px, 0 sel terpotong, 4 kelompok tanggal di Mendatang; HP 64 px dua lapis. Tanpa galat JS atau gulir mendatar di 16 rute. Catatan: komponen sudah memakai aturan baru (huruf 13 px, baris 52/60 px, `ui.status` bertitik), sehingga page lain ikut berubah sebagian sebelum sesi 2.

**Tujuan:** aturan baru hidup di token dan komponen, lalu dibuktikan di page paling padat.

1. **Token dan tipografi** (`tokens.css`).
   - `--t-kecil` → 13/18; `--t-kecil-tebal` → 13/18; kepala tabel memakai `--t-label`.
   - `--baris` 52, tambah `--baris-dua` 60; padat 44/52.
   - `BAWAAN.density` jadi `nyaman` (K5); label sakelar "Nyaman (bawaan)".
2. **Komponen** (`ui.js`, `components.css`): semua butir "Komponen yang ditambah" di atas, termasuk subgrid, kepala lengket, kelompok, tindakan ikon dan susunan HP dua lapis.
3. **Booking: daftar desktop.**
   - Semua tab: *Sesi · Pelanggan · Layanan · Status · Dibayar*.
     - Sesi berisi jam saja; tanggal ada di kepala kelompok.
     - Pelanggan berisi nama saja (K7).
     - Layanan satu lapis, ditambah "· 5/8 peserta" hanya untuk layanan grup; kolom Peserta dihapus.
     - Status memakai titik + teks; lencana hanya untuk Menunggu bayar, Minta refund dan Belum ditutup.
   - **Perlu tindakan:** *Sesi (tanggal + jam) · Pelanggan · Layanan · Yang perlu dilakukan · Tindakan*.
     - Alasan ditulis sekali, dengan titik kunyit.
     - Tombol kedua kecil per baris: "Tandai selesai", "Putuskan refund" atau "Ingatkan bayar".
     - Centang hanya di baris yang bisa ditutup; "pilih semua yang bisa ditutup" di kepala.
   - **Mendatang:** dikelompokkan per tanggal naik. **Selesai, Batal, Semua:** dikelompokkan per tanggal turun.
   - **Alat:** kotak cari page tetap ("Cari nama, kode atau layanan"); tambah pilihan "Urutkan" kecil (Sesi terdekat, Terbaru dibuat, Jumlah). Kotak cari bilah atas diberi teks "Cari di semua page".
4. **Booking: HP.**
   - Baris: awal jam mulai (papan 15 px); utama nama; akhir jumlah dibayar; lapis 2 "Layanan · ● Status".
   - Perlu tindakan: lapis 2 berisi alasan; tombol "Pilih" di alat membuka mode pilih banyak dengan bilah tindakan di bawah.
5. **Slip booking (tampilan).**
   - Kepala karcis: tanggal setara jam ("Sabtu 21 Nov · 10.00–12.00"); untuk dua sesi "2 sesi · mulai Sab 21 Nov".
   - Butir yang perlu Anda (refund, belum ditutup) memakai blok kunyit, bukan kalimat info netral.
   - Pembayaran: "Total" saja bila tanpa tambahan/promo; bila dua sesi "2 sesi × Rp350.000".
   - Baris pelanggan diberi panah kanan; kaki HP: tombol utama + tombol ikon "⋯" di sebelahnya.
6. **Catat booking.** Saran pelanggan lama baru muncul setelah mengetik 2 huruf; "Pilih tanggal" membuka kalender bulan kecil.
7. **Periksa:** `padat.py` untuk Booking memenuhi sasaran; `uji3.py` (batas huruf dinaikkan ke 13 px); 1 potret Booking desktop + 1 HP.

## Sesi 2: semua tabel lain — selesai 17 Nov 2026

> Hasil: semua tabel ≤ 5 kolom data, baris desktop 52/60 px, HP 64 px (Pelanggan 72 px karena avatar), 0 teks terpotong, lencana hanya untuk pengecualian. Tanpa galat JS atau gulir mendatar.

**Tujuan:** setiap tabel memakai sistem dari sesi 1; kolom dipangkas sesuai spesifikasi ini.

| Page | Kolom desktop baru | HP (utama / akhir / lapis 2) | Catatan |
|---|---|---|---|
| **Pelanggan › Semua, Baru, Tetap** | Pelanggan (avatar + nama) · Booking · Total dibayar · Terakhir datang · Berikutnya · [ikon WhatsApp] | nama / total / "24 booking · terakhir 16 Nov" | Kartu ringkasan dihapus (K8). Segmen hanya tampil sebagai lencana untuk "Baru" dan "Lama tidak datang". "Berikutnya" = "19 Nov · 09.00" atau "—". |
| **Pelanggan › Lama tidak datang** | Pelanggan · Terakhir datang ("62 hari lalu") · Total dibayar · [Sapa] | nama / tombol Sapa ikon / "62 hari lalu · Rp4,4 jt" | Tombol berteks boleh: ini tab tugas. |
| **Layanan** | Layanan (nama; lapis 2: kategori · "maks 8 orang" bila grup) · Harga · Durasi · Unit ("Studio Foto A +1") · [⋯] | nama / harga / "Fotografi · 1 jam" | Catatan tabel: "Slot semua layanan terbuka sampai 14 Des · Atur". Pengecualian di lapis 2 dengan teks kunyit "Slot hanya sampai 20 Nov". Nonaktif = baris redup + "Nonaktif". Kolom Kapasitas dan Slot terbuka dihapus (K9, K10). |
| **Layanan › Kategori** | Kategori (titik warna + nama) · Layanan · Terjual · [⋯] | nama / jumlah layanan | Lencana diganti titik warna. |
| **Layanan › Tambahan** | Tambahan · Harga · Stok · Dipakai di · Terjual · [⋯] | nama / harga / "Dipakai di 3 layanan · 18 terjual" | Status hanya untuk Nonaktif (baris redup). |
| **Unit** | Unit (nama; jenis) · Kapasitas ("12 orang") · Lokasi · Layanan ("2 layanan", judul berisi daftar) · Terpakai hari ini (batang mini) | nama / batang mini / "Lantai 1 · 2 layanan" | |
| **Jam Buka › Slot** | Jam · Layanan · Harga · Status · [⋯], dikelompokkan per tanggal | jam / harga / "Layanan · ● Status" | Slot lewat diredupkan tanpa lencana. Status: Kosong (teks biasa), Terisi (titik lilak), Ditutup (titik bata). |
| **Jam Buka › Libur** | Tanggal · Alasan (boleh 2 baris) · [⋯] | tanggal / — / alasan | |
| **Jam Buka › Harga** | tidak berubah; ikut tinggi baris dan huruf baru | | |
| **Pembayaran (ringkasan) dan › Transaksi** | Waktu ("16 Nov · 10.23") · Pelanggan · Layanan · Metode · Jumlah | nama / jumlah / "16 Nov 10.23 · Transfer bank" | Kolom Status dihapus; non-berhasil tampil sebagai lencana di sel Jumlah. Kode ORD pindah ke laci detail (K7). |
| **Pembayaran › Saldo** | Mutasi: tetap. Pencairan: Pencairan (kode; tanggal) · Rekening · Status · Jumlah | kode / jumlah / "BCA ••4421 · ● Diproses" | Kolom Keterangan dipindah ke laci; status Selesai tanpa lencana. |
| **Pengaturan › Tim** | Anggota (nama; email) · Peran · Terakhir masuk · [⋯] | nama / peran / "Terakhir masuk sekarang" | Kolom Status dihapus; "Undangan terkirim" sebagai lencana di samping nama. |
| **Pengaturan › Notifikasi** | Kejadian (nama; satu keterangan) · WhatsApp · Email | nama / sakelar | Sakelar sejajar tengah; tinggi baris 52. |
| **Paket › Riwayat tagihan** | Faktur (nomor; tanggal) · Paket · Jumlah · [ikon unduh] | nomor / jumlah / "Pro 6 bulan · 2 Nov" | Lunas tanpa lencana; hanya "Belum dibayar". |

**Periksa:** `padat.py` semua rute di atas memenuhi sasaran; `uji3.py`; potret Pelanggan desktop + Transaksi HP.

## Sesi 3: daftar, kartu, page panjang, dan sapuan akhir — selesai 17 Nov 2026

> Hasil sapuan (36 rute × 2 lebar): semua baris tabel 52–62 px desktop, 64–73 px HP; maks. 5 kolom data; 0 teks terpotong; `uji3` bersih (kontras, huruf, tombol utama, banner, papan ketik 7/7).
> Belum mencapai sasaran: (1) porsi teks < 14 px masih 21–56% desktop karena subteks 13 px ikut dihitung; teks < 13 px di luar lencana/hitung = 0. (2) Page HP terpanjang sudah dirapikan: Beranda 1641 px (dari 2624; panel Besok hanya di desktop karena angkanya sudah ada di kartu angka), Jam Buka › Harga 1424 px (simulasi harga dilipat di HP). Potret akhir dilewati untuk menghemat token.

**Tujuan:** page selain tabel ikut lega; page HP tidak lebih dari ±2 layar.

1. **`ui.lr`** sesuai aturan daftar. Terapkan ke:
   - **Notifikasi:** kelompok "Hari ini / Kemarin / Minggu ini", titik belum dibaca di kiri, waktu di kanan.
   - **Rekening, Laporan › Pelanggan, Halaman Booking.**
2. **Beranda.**
   - Desktop: rel hari dan panel samping ikut huruf baru; panel "Perlu Anda" paling banyak 3 butir + "Lihat semua (n)".
   - HP, urutan: Perlu Anda → Jadwal hari ini → angka utama. "Aksi cepat" dihapus di HP (Catat ada di nav bawah). Panduan memakai `lipat` tertutup. "Masuk hari ini" digabung ke angka. Sasaran tinggi ≤ 1800 px.
3. **Kalender › Hari.**
   - Blok ≤ 60 menit: satu baris "09.00 Umar W." (13 px tebal).
   - Blok > 60 menit: tambah lapis kedua.
   - Kepala kolom: nama layanan boleh 2 baris; durasi pindah ke judul (hover).
4. **Laporan › Okupansi.**
   - Sel peta panas tanpa angka (angka di judul sel), legenda 4 tingkat.
   - Satu kalimat kesimpulan di atas: "Paling ramai Sabtu 13.00–17.00; paling sepi Selasa pagi".
5. **Promo.**
   - Kartu Aktif: kode (papan 17 px), potongan besar, satu baris "Sampai 30 Nov · 12 dari 50 dipakai" + batang kuota. Syarat lain di laci.
   - Tab Semua jadi tabel: *Kode · Potongan · Berlaku · Dipakai · [⋯]*; Berakhir/Nonaktif diredupkan.
6. **Halaman Booking › Galeri:** HP kisi 2 kolom, keterangan foto 13 px, tombol ubah lewat ⋯ (sasaran ≤ 1800 px).
7. **Paket (HP):** perbandingan paket jadi segmen Small / Medium / Pro yang menampilkan satu paket sekaligus, bukan tiga kartu bertumpuk.
8. **Jam Buka (HP):** satu baris per hari "Sen 08.00–22.00 · Ubah"; hari tutup redup.
9. **Laci dan dialog:** `kv` dan riwayat di slip memakai 14 px untuk nilai, 13 px untuk label; jarak antar-bagian 24 px.
10. **Sapuan akhir.**
    - `padat.py` untuk semua 36 rute di dua lebar; semua sasaran terpenuhi atau pengecualiannya dicatat.
    - `uji3.py` (kontras, huruf, aturan utama/banner).
    - Potret: 1 desktop dan 1 HP dari page yang paling berubah.
11. **Dokumen.**
    - `prototype/README.md`: bagian "Aturan tabel dan keterbacaan" berisi aturan di atas, plus ringkasan sesi.
    - `prototype/HANDOFF.md`: komponen baru (`titikStatus`, `catatanTabel`, `durasi`, `batangMini`, opsi `ui.tabel`) dan keputusan K5–K10 (K8 masuk daftar B).
    - Tandai sesi selesai di berkas ini.

## Hasil akhir yang diharapkan

- Setiap baris tabel bisa dipindai dalam satu lirikan: siapa, kapan, berapa, dan apakah perlu tindakan.
- Warna dan lencana hanya muncul ketika ada yang perlu dikerjakan, sehingga perhatian langsung tertuju ke sana.
- Tidak ada teks di bawah 13 px untuk isi, tidak ada nama terpotong, dan tidak ada nilai yang diulang tanpa makna.
- Di HP setiap baris berisi dua lapis, dan page utama bisa dibaca dalam ±2 layar.
