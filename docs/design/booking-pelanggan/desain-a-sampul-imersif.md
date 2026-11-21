# Desain halaman pelanggan: arah A "Sampul Imersif" (revisi 3)

Spesifikasi tampilan untuk semua halaman yang dilihat pelanggan: toko (`/{slug}`), pilih jadwal, data & pembayaran, menunggu pembayaran, berhasil & invoice, kelola reservasi, ubah jadwal, status pembayaran, tautan akses, dan halaman error. Dipakai oleh [BQ-165](../../work/BQ-165-tampilan-pelanggan-dinamis.md).

- **Status:** disetujui Tech Lead pada 21 Nov 2026 sebagai acuan implementasi. Bagian template siap pakai menunggu [D-028](../../product-decisions.md).
- **Sumber:** audit [`audit-ui-2026-11-20.md`](audit-ui-2026-11-20.md), audit desain revisi 2 (21 Nov 2026), dan mockup kanvas pribadi Tech Lead. Dokumen ini berdiri sendiri; implementasi tidak perlu membuka kanvas.
- **Berlaku di atas** aturan desain pelanggan BQ-164. Bila berbeda, dokumen ini yang dipakai (lihat §10).

## 1. Prinsip

1. **Toko milik bisnis, bukan milik BookQu.** Semua warna diturunkan dari warna merek bisnis. Warna identitas BookQu (tinta `#1D1638`, marigold `#FFB84D`) tidak dipakai sebagai permukaan atau aksen di halaman pelanggan.
2. **Satu tampilan, mengikuti pengaturan owner.** Warna merek, huruf, bentuk tombol, dan gaya kartu dari menu Tampilan (owner-portal.md §8.9) berlaku di setiap layar.
3. **Tiap ukuran layar punya susunan sendiri.** Ponsel, tablet, dan desktop tidak sekadar direntangkan (§5).
4. **Satu aksi utama per layar.** Di ponsel dan tablet aksi utama ada di bilah bawah yang menempel.
5. **Kontras dulu, gaya kemudian.** Setiap kombinasi pengaturan owner harus lolos WCAG AA.

## 2. Warna

### 2.1 Turunan warna merek

Dihitung di server oleh `StorefrontBranding` dari `theme_color` bisnis.

| Token | Isi | Dipakai untuk |
|---|---|---|
| `--bq-primary` | warna merek | sampul, tombol utama, pilihan aktif |
| `--bq-on-primary` | `#17151F` bila luminans merek > 0,18, selain itu `#FFFFFF` | teks dan ikon di atas warna merek |
| `--bq-on-primary-soft` | teks di atas merek, 80–86% | deskripsi di sampul |
| `--bq-on-primary-faint` | lapisan 10–14% dari warna teks | chip dan tombol di atas sampul |
| `--bq-on-primary-line` | garis 18–24% | ornamen cincin di sampul |
| `--bq-primary-text` | merek; bila merek terang, merek dicampur 42% dengan hitam | harga, tautan "Ganti", angka hitung mundur, teks merek di atas putih |
| `--bq-primary-tint` | merek 9% di atas putih | kepala ringkasan, kotak bantuan |
| `--bq-primary-tint-strong` | merek 16% di atas putih | ikon layanan, tanggal hari ini |

Aturan: **teks putih tidak boleh ditulis langsung di atas warna merek**; selalu pakai `--bq-on-primary`. Contoh: kuning `#F59E0B` dengan teks putih hanya 2,15:1, sehingga harus memakai teks gelap.

### 2.2 Netral

| Token | Nilai | Catatan |
|---|---|---|
| latar halaman | `#F6F6F8` | abu netral, tidak keunguan |
| permukaan | `#FFFFFF` | |
| teks | `#17151F` | |
| teks redup | `#5E5A68` | 5,6:1 di atas latar |
| garis kontrol | `#D5D3DC` | |
| garis pemisah | `#E6E5EA` | |
| nonaktif | `#ECEBF0` latar, `#6B6776` teks | |

### 2.3 Status

Warna status tetap, tidak mengikuti merek: berhasil `#DDF0E5`/`#1A6B43`, menunggu `#FFF4DE`/`#8A5A00`, gagal `#FCEBEA`/`#8C1D18` (teks error `#B3261E`), info `#E8F0FB`/`#285E9F`.

## 3. Bentuk dan permukaan dinamis

### 3.1 Bentuk tombol (`button_style`)

Mengatur semua **kontrol**: tombol, chip kategori, kolom isian, kartu tanggal, tombol jam, tombol −/+, sel kalender.

| Pengaturan | Tombol & isian | Kartu tanggal & jam | Kolom multi-baris | Chip | Sudut kartu besar | Sudut kartu kecil |
|---|---|---|---|---|---|---|
| Pil (`pill`) | 999 px | 999 px | 20 px | 999 px | 24 px | 18 px |
| Membulat (`rounded`) | 12 px | 14 px | 12 px | 10 px | 18 px | 12 px |
| Kotak (`square`) | 4 px | 4 px | 4 px | 4 px | 6 px | 4 px |

Sudut kartu ikut bentuk tombol supaya senada. Owner tidak mengatur sudut kartu terpisah.

### 3.2 Gaya kartu (`card_style`)

Mengatur semua **permukaan**: kartu layanan, ringkasan, tiket, panel kalender dan jam, kartu info, bilah pembayaran tertunda.

| Pengaturan | Kartu biasa | Kartu menonjol (menumpuk di sampul, ringkasan samping) |
|---|---|---|
| Terangkat (`elevated`) | putih, bayangan `0 1px 2px` + `0 6px 18px` sangat tipis | putih, bayangan `0 16px 40px` 14% |
| Garis tepi (`outline`) | putih, garis 1,5 px `#D5D3DC`, tanpa bayangan | sama |
| Lembut (`soft`) | putih tanpa garis dan bayangan, mengandalkan latar `#F6F6F8` | putih dengan garis bawah 1 px samar |

### 3.3 Yang tidak ikut berubah

Ukuran sentuh minimal 44 px, warna status, aturan kontras, jarak antarelemen, dan ukuran huruf.

### 3.4 Template siap pakai (menunggu D-028)

Template mengisi tiga pengaturan sekaligus; warna merek tetap milik bisnis.

| Template | Bentuk tombol | Gaya kartu | Huruf |
|---|---|---|---|
| Modern | Pil | Terangkat | Plus Jakarta Sans |
| Rapi | Membulat | Garis tepi | Inter |
| Tegas | Kotak | Lembut | Outfit |

Setelah memilih template, owner tetap bebas mengubah tiap pengaturan. Bila kombinasi tidak sama dengan template mana pun, label menjadi "Kustom". Tidak perlu kolom database baru: template dikenali dari tiga nilai yang tersimpan.

## 4. Huruf, ikon, gerak

- **Huruf:** pilihan bisnis (`StorefrontBranding::FONTS`), ketebalan 400–700. Jangan memakai 800 karena Lora tidak punya. Hanya huruf pilihan bisnis yang dimuat.
- **Ukuran:** judul sampul 26 / 36 / 50 px (ponsel / tablet / desktop), judul layar 18 / 24 / 24 px, judul bagian 17–22 px, teks 14–15 px, teks kecil minimal 12 px.
- **Label kecil:** kalimat biasa, bukan huruf kapital semua (aturan BQ-164 tetap).
- **Ikon:** garis 1,5–2 px, `aria-hidden`; tombol ikon saja wajib punya `aria-label`.
- **Gerak:** transisi 150–200 ms saat memilih tanggal atau jam, membuka lembar, dan menyalin kode. Skeleton saat memuat jam. Semua mati di `prefers-reduced-motion`.

## 5. Ukuran layar

| Ukuran | Lebar | Pola |
|---|---|---|
| Ponsel | < 768 px | bilah atas ringkas berwarna merek, konten satu kolom, carousel geser untuk favorit, daftar untuk layanan, bilah aksi bawah menempel, lembar dari bawah untuk kalender |
| Tablet | 768–1023 px | sampul dua kolom, dua panel berdampingan (kalender + jam), ringkasan di kepala halaman, bilah aksi bawah menempel |
| Desktop | ≥ 1024 px | lebar konten maksimal 1240 px, navigasi kategori di samping, tiga kolom di jadwal (kalender, jam, ringkasan), ringkasan menempel di kanan |

Sampul semua layar: warna merek dengan ornamen tiga cincin tipis (`--bq-on-primary-line`) dan satu bulatan (`--bq-on-primary-faint`) di kanan atas. Bila bisnis punya banner (`banner_path`), banner menjadi latar sampul dengan lapisan gelap 55–70% dan teks putih; ornamen disembunyikan.

## 6. Komponen

| Komponen | Isi | Catatan |
|---|---|---|
| Sampul toko | logo, nama bisnis (`h1`), deskripsi 2 baris di ponsel, chip "Bayar online", lokasi | tablet/desktop: kartu lokasi dengan "Petunjuk arah" dan WhatsApp |
| Kepala langkah | tombol kembali, "Langkah n dari 3", judul, bilah kemajuan 3 bagian | tablet/desktop menampilkan layanan terpilih dan "Ganti" |
| Kartu layanan favorit | foto atau ikon di atas warna merek tipis, label Favorit (`is_popular`), kategori · durasi, nama, harga, tombol | ponsel: carousel geser; tablet: 3 kolom; desktop: 3 kolom dengan deskripsi |
| Baris layanan | foto/ikon, nama, durasi · kategori, harga | ponsel: daftar; tablet/desktop: grid 2 kolom dengan deskripsi 2 baris |
| Chip kategori | nama pendek + jumlah | ponsel: baris geser dengan pudar di tepi; tablet: membungkus; desktop: navigasi samping |
| Kartu tanggal | hari, tanggal, titik ketersediaan | ponsel: pita geser dengan snap + tombol "Kalender" |
| Kalender bulan | 7 kolom, titik ada sesi / penuh, hari ini bergaris | tablet/desktop: panel; ponsel: lembar bawah |
| Tombol jam | jam mulai, keterangan (Penuh, Sisa n tempat) | 3 kolom ponsel/tablet, 4 kolom desktop; `aria-pressed` |
| Bilah aksi bawah | label ringkas, total, tombol utama | ponsel dan tablet |
| Ringkasan samping | kepala `--bq-primary-tint`, baris rincian, total, tombol utama, aturan singkat | desktop |
| Tiket reservasi | blok tanggal, layanan, jam, lokasi, sobekan, kode + Salin | halaman berhasil |
| Kartu jadwal (Kelola) | blok tanggal, jam, layanan, aksi, catatan batas waktu | catatan berwarna sesuai status |
| Lembar bawah | pegangan, judul, tombol tutup, isi, tombol utama | `role="dialog"`, `aria-modal` |
| Kotak kosong / error | ikon, judul, penjelasan, 1–2 aksi | |

## 7. Layar dan keadaan

Teks yang ditulis di sini adalah teks final, kecuali data contoh.

### 7.1 Pilih layanan
- **Ponsel:** sampul ringkas → pencarian dan kategori menempel → Favorit (carousel) → Semua layanan (daftar) → footer.
- **Tablet:** sampul dua kolom (judul + kartu lokasi) → pencarian + chip membungkus → Favorit 3 kolom → grid 2 kolom.
- **Desktop:** sampul (judul 50 px + kartu lokasi menonjol) → kiri navigasi kategori menempel + "Ragu memilih? Tanya lewat WhatsApp"; kanan pencarian + jumlah hasil → Favorit 3 kolom → grid 2 kolom.
- **Judul:** "Reservasi di {nama bisnis}" (tablet/desktop). Di ponsel `h1` adalah nama bisnis dengan label kecil "Reservasi online".
- **Keadaan:** biasa; pembayaran belum selesai (bilah menempel "Pembayaran belum selesai · {layanan} · bayar sebelum {jam}" + "Lanjutkan"); pencarian tanpa hasil ("Tidak ada layanan “{kata}”" + "Lihat semua layanan" + "Tanya via WhatsApp"); reservasi online ditutup; dengan banner; dengan foto layanan.

### 7.2 Pilih jadwal
- **Ponsel:** kepala langkah → kartu layanan menumpuk di sampul → judul bulan + "Kalender" → pita tanggal geser → jam per Pagi/Siang/Sore (3 kolom) → bilah bawah.
- **Tablet:** kalender bulan (330 px) | panel jam; bilah bawah dengan catatan ubah jadwal.
- **Desktop:** kalender (320 px) | jam | ringkasan (340 px).
- **Info harga:** satu baris "Rp150.000 per sesi · akhir pekan +10%" dari aturan harga; harga tidak diulang di tiap jam.
- **Keadaan:** biasa; beberapa sesi berurutan (petunjuk "Bisa pilih beberapa sesi berurutan", total dan label ikut berubah); memuat (skeleton); tanggal tanpa sesi ("Tidak ada sesi di tanggal ini" + tombol tanggal terdekat yang masih ada sesi); layanan berkapasitas ("Sisa n tempat" + jumlah peserta); lembar kalender (ponsel).

### 7.3 Data & pembayaran
- **Ponsel:** ringkasan pesanan yang bisa dibuka-tutup di atas → Data pemesan (satu kolom) → Jumlah peserta (bila kapasitas > 1) → Tambahan (baris dengan −/+ 44 px) → Kode promo → catatan aturan → bilah bawah "Total · Bayar".
- **Tablet:** ringkasan di kepala halaman (layanan, jadwal, batas ubah jadwal) → form 2 kolom → tambahan 2 kolom → rincian harga → bilah bawah.
- **Desktop:** form | ringkasan menempel dengan tombol "Bayar {total}" dan tiga aturan.
- **Keadaan:** biasa; ringkasan terbuka (ponsel); error (kotak "Periksa n isian" di atas, kolom bergaris merah, pesan di bawah kolom, isian tetap); berkapasitas.

### 7.4 Menunggu pembayaran
- **Ponsel:** status, nomor pesanan, total, sisa waktu `mm:ss` + bilah "Sisa waktu" (progressbar), batas jam, "Periksa status", "Cara bayar" satu kotak, pemantauan otomatis, "Batalkan pesanan" (dengan dialog yang sudah ada), rincian reservasi bisa dibuka-tutup, bilah bawah "Bayar sekarang · {total}".
- **Tablet:** satu kartu di tengah (maks 680 px) + rincian di bawahnya.
- **Desktop:** kartu | rincian reservasi.
- **Keadaan:** menunggu; memeriksa (status biru, tombol "Memeriksa…" dengan `aria-busy`); rincian terbuka.

### 7.5 Berhasil & invoice
- **Mode berhasil:** sampul dengan lencana centang, "Pembayaran berhasil", email tujuan → tiket → 4 aksi (Google Calendar, WhatsApp, Petunjuk arah, Cetak/Unduh invoice) → "Kelola reservasi" → invoice (Lunas, pemesan, bisnis, rincian, total, metode).
- **Mode invoice** (dibuka dari Kelola): tanpa lencana; judul "Invoice pembayaran", tombol kembali ke Kelola, tombol Cetak.
- **Ponsel:** satu kolom; aksi 2×2. **Tablet:** tiket | aksi bertumpuk, invoice di bawah. **Desktop:** tiket + aksi (420 px) | invoice.
- Versi cetak memakai gaya yang sama tanpa sampul dan tombol.

### 7.6 Kelola reservasi
- Susunan memakai `grid-template-areas`: ponsel satu kolom; tablet jadwal dan ulasan selebar penuh, kartu info 2 kolom; desktop jadwal + ulasan + riwayat | pembayaran + pemesan + lokasi + bantuan.
- **Keadaan:**
  - terkonfirmasi: Ubah jadwal, Simpan ke Google Calendar, Bagikan; catatan "Batas ubah jadwal {tanggal jam}";
  - menunggu pembayaran: "Lanjutkan pembayaran", pembayaran "Belum dibayar";
  - batas ubah jadwal lewat: "Hubungi bisnis" menggantikan Ubah jadwal, catatan kuning;
  - selesai: "Pesan lagi" + form ulasan (bintang 44 px, komentar, Kirim ulasan); form ulasan hanya muncul di keadaan ini;
  - dibatalkan: jadwal dicoret, "Pesan lagi", catatan merah, bantuan pengembalian dana;
  - beberapa sesi / pembayaran grup: daftar sesi, catatan bahwa ubah jadwal per sesi tidak tersedia, "Hubungi bisnis".
- Bantuan pembatalan mengikuti `refunds_enabled` dan formulir refund yang sudah ada.

### 7.7 Ubah jadwal
- **Ponsel:** kepala → kartu Saat ini → Baru → aturan harga bisa dibuka-tutup → pita tanggal (Saat ini, Hubungi, Tutup) → baris kuning penjelasan tanggal Hubungi → jam → bilah bawah "Simpan".
- **Tablet:** perbandingan Dari → Ke **di kepala halaman** → kalender | jam → bilah bawah "Batal" + "Simpan jadwal baru". Ringkasan tidak boleh turun ke bawah konten.
- **Desktop:** kalender | jam | ringkasan Dari → Ke.
- **Keadaan:** biasa; menyimpan (`aria-busy`); ditolak server (kotak merah "Jadwal belum berubah…", jam terisi ditandai Penuh, jadwal lama tetap).

### 7.8 Status pembayaran
- Kedaluwarsa: "Waktu pembayaran habis", "Pilih jadwal lagi", catatan "Sudah terlanjur membayar? Simpan bukti pembayaran dan hubungi bisnis."
- Gagal: "Pembayaran gagal", "Coba bayar lagi".
- Ponsel: tombol di bilah bawah; tablet/desktop: kartu di tengah.
- Jangan menulis janji yang tidak dijamin sistem, misalnya "tidak ada dana yang ditarik".

### 7.9 Tautan akses dan error
- `/access`: logo BookQu (bisnis belum diketahui), indikator berputar, "Membuka reservasi Anda…"; mati di reduced-motion dan diganti teks.
- Tautan tidak berlaku, 404 (dengan merek bisnis bila diketahui), 500: ikon, kode error kecil, judul, penjelasan, dua aksi.

## 8. Aksesibilitas

- Kontras teks minimal 4,5:1 untuk setiap kombinasi warna merek; diuji dengan warna terang dan gelap.
- Semua tombol jam dan tanggal memakai `aria-pressed` atau `role="option"` + `aria-selected`.
- Lembar bawah menjebak fokus dan menutup dengan Esc.
- Satu wilayah `aria-live="polite"` per halaman untuk status.
- Bilah aksi bawah tidak menutupi konten (padding bawah halaman ≥ tinggi bilah).

## 9. Di luar cakupan

Landing page Pro, situs pemasaran BookQu, portal owner selain Tampilan (§3.4), dan semua aturan harga, ketersediaan, pembayaran, pembatalan, dan refund.

## 10. Perbedaan dari aturan desain pelanggan BQ-164

| Aturan BQ-164 | Sekarang |
|---|---|
| Warna `bq-*` owner dipakai di halaman pelanggan | Halaman pelanggan memakai turunan warna merek dan netral (§2). Tinta dan marigold BookQu tidak dipakai. |
| "Konten di panel, bukan kartu per baris" | Berlaku untuk daftar data. Kartu per layanan dipakai di toko karena tiap layanan adalah pilihan. |
| Sudut dan bayangan tetap | Mengikuti `button_style` dan `card_style` (§3). |
| Bilah bawah di ponsel | Juga di tablet. |

Aturan lain BQ-164 (format, salinan, label kalimat biasa, gerak, aksesibilitas) tetap berlaku.
