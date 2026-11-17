# Prototipe BookQu "Papan Jadwal"

Prototipe independen portal owner: tampilan saja, data statis, tidak terhubung ke Laravel. Acuan visual untuk `../review.md` dan `../audit-ui.md`. Rencana awal: `../riwayat/rencana-awal-prototipe.md`; rencana lanjutan: `../riwayat/rencana-perbaikan-prototipe.md` dan `../riwayat/rencana-keterbacaan-prototipe.md` (keduanya selesai). Peta seluruh folder: `../README.md`.

Bila bagian "Panduan gaya" di bawah berbeda dengan bagian "Keterbacaan" di akhir berkas, yang berlaku adalah `css/tokens.css` dan aturan di `../riwayat/rencana-keterbacaan-prototipe.md`.

## Menjalankan

Dari folder repo: `php -S 127.0.0.1:8765 -t docs/design/papan-jadwal/prototype` (atau `preview_start` nama `bookqu-prototype`), lalu buka `http://127.0.0.1:8765/#/beranda`; katalog komponen di `#/sistem`. Tombol "Prototipe" (kanan bawah) berisi sakelar layar, peran, paket, keadaan, kepadatan, sidebar, catatan perubahan.

## Panduan gaya

**Konsep.** Waktu adalah tulang punggung tata letak. Satu elemen berani: rel hari dengan garis kunyit "sekarang". Selain itu tenang dan padat.

**Warna** (nilai hanya di `css/tokens.css`)
- Terong `#382186`: hanya yang bisa ditekan atau sedang dipilih.
- Tinta `#1D1638` teks; Redup `#625E75` teks kedua.
- Kunyit `#FFB84D`: hanya "sekarang" dan "perlu Anda"; tidak pernah jadi teks.
- Kabut `#F5F4F9` latar; panel putih; Lilak `#ECE8FA` terpilih; Garis `#E3E0EE`.
- Status tiga warna: Daun `#1F7A4D` (lunas), Bata `#B3261E` (batal/bahaya), Kunyit pucat `#FFF1D6` (menunggu).
- Sidebar terang; versi terong hanya sebagai pembanding lewat sakelar.

**Huruf**
- Plus Jakarta Sans untuk semua teks. Barlow Semi Condensed hanya untuk jam dan angka utama.
- Skala: angka utama 40/44; judul halaman 24/32; judul bagian 17/24; isi 14/20 (formulir 15/22, 16 px di HP); kecil 13/18.
- Tanpa huruf kapital semua; teks terkecil 13 px, kecuali lencana, angka hitung tab dan label nav bawah HP (12 px); angka memakai `tabular-nums`.

**Bentuk dan kepadatan**
- Radius 6 px kontrol, 10 px panel, 16 px dialog/lembar. Panel bergaris 1 px tanpa bayangan; hanya lapisan melayang yang berbayang.
- Baris tabel 52 px satu lapis / 60 px dua lapis (Nyaman, bawaan); Padat 44 / 52 px. Daftar (`ui.lr`) 56 px. Kontrol 40 px, 44 px di HP. Jarak pada kisi 4 px.
- Gerak hanya menjawab tindakan (200 ms); tidak ada animasi saat halaman dimuat.

**Aturan pemakaian**
- Satu tombol utama per layar. Tindakan bahaya bergaris merah; merah penuh hanya di dalam konfirmasi.
- Status biasa berupa titik + teks; lencana hanya untuk yang perlu perhatian (Menunggu bayar, Minta refund, Belum ditutup, Gagal, Nonaktif, Hampir habis). Paling banyak satu lencana per baris; label mengikuti status booking yang ada.
- Satu angka utama per halaman (Laporan → Ringkasan: dua), selalu dengan pembanding atau konteks.
- Tabel paling banyak 5 kolom data; nilai yang sama di semua baris ditulis sekali di atas tabel; kode, email dan telepon di slip/panel.
- Daftar bukan kartu: tabel di desktop, baris dua-baris di HP; filter di balik "Filter (n)" di HP.
- Paling banyak satu banner per halaman, selalu dengan tindakan.
- Formulir: label di atas, petunjuk di bawah, galat menggantikan petunjuk; angka 0 dijelaskan artinya; lebar maksimum 640 px.
- Satu nama per tindakan: "Catat booking". Bahasa Indonesia sehari-hari, tanpa istilah teknis.
- Isi rata kiri, lebar maksimum 1200 px. Di bawah 768 px: navigasi bawah, lapisan naik dari bawah.

**Aturan struktur.** Halaman (`js/pages/*.js`) tidak menulis gaya; hanya memanggil komponen di `js/ui.js`. Warna, ukuran huruf dan radius tidak boleh muncul di luar `tokens.css`. Titik henti memakai container query pada `.app`, bukan media query.

## Status

Semua langkah 1–6 selesai (17 Nov 2026). Semua 17 halaman, tab, panel dan dialog ada; rute bawaan `#/beranda`.

| Berkas | Isi |
|---|---|
| `js/pages/sistem.js` | Sistem desain: semua komponen |
| `js/pages/bersama.js` | Slip booking, Catat booking, pindah jadwal, batalkan, putuskan refund, panel pelanggan, sapa pelanggan, rel hari |
| `js/pages/beranda.js`, `kalender.js`, `booking.js`, `pelanggan.js` | Alur harian |
| `js/pages/penyiapan.js` | Layanan (3 tab), Unit, Jam Buka & Harga (4 tab) |
| `js/pages/uang.js` | Pembayaran (5 tab), Laporan (5 tab), Promo, Ulasan |
| `js/pages/lainnya.js` | Halaman Booking (5 tab), Pengaturan (6 tab), Paket & Tagihan, Notifikasi, Bantuan |

**Sakelar yang berpengaruh.** Peran: menu, akses halaman (penjaga di router), uang disembunyikan untuk Kasir, "Ajukan pembatalan" untuk Kasir, tab Pengaturan per peran. Paket: kunci Unit, Promo, Tambahan, Tim, Landing page, Domain; Laporan buram di Small; kuota di Paket & Tagihan. Keadaan: Baru = panduan di Beranda dan keadaan kosong di Kalender, Booking, Pelanggan, Layanan, Ulasan, Laporan, Pembayaran; Berakhir = banner di Beranda dan Paket & Tagihan. Kepadatan dan Sidebar: global. Catatan perubahan: label A/B/C/teknis dengan nomor temuan.

## Sesi perbaikan 1 (17 Nov 2026, lihat `../riwayat/rencana-perbaikan-prototipe.md`)

- Token: semua warna, ukuran huruf dan bentuk kini hanya di `tokens.css` (alat prototipe di blok "Alat prototipe"). Gradasi dekoratif dihapus; garis jam di kisi hari dan arsiran slot tutup tetap sebagai pola fungsional. Kunyit tidak lagi dipakai untuk warna kategori atau tema.
- Angka: periode 1–16 Nov = 32 booking, 87 slot (36,8%), kotor Rp14.050.000 − refund Rp450.000 = bersih Rp13.600.000; 16 hari terakhir grafik = Rp13.600.000; "masuk hari ini" Beranda = transaksi berhasil 16 Nov (Rp900.000); rating 4,6 dari sebaran 17/4/1/1/0; promo 9 kali dipakai di periode; waktu bayar di slip diambil dari transaksi.
- Keputusan: K1 tombol "Catat booking" di bilah atas jadi tombol kedua di luar Beranda, Kalender, Booking, Pelanggan. K2 nada lilak dipertahankan sebagai tanda "terpilih/sekarang", bukan status booking. K3 "Tambahan" dan satu lencana di Booking berlabel B. K4 "Halaman depan".
- Aturan: catatan di laci dan dialog memakai Kesimpulan, bukan banner; Laporan → Ringkasan dua angka utama + strip pendukung (`ui.angkaPendukung`); Paket & Tagihan angka utama = sisa hari; "Turun ke …" memeriksa pemakaian.
- Komponen: kisi hari kolom ≥ 152 px dengan judul penuh dua baris dan blok ≤ 60 menit satu baris; semua kartu Layanan bergambar (tempat gambar polos); Laporan untuk Small hanya cuplikan buram; menu Lainnya HP lembar penuh; baris Booking HP tiga baris (kode di slip).
- `#/sistem` menampilkan kisi hari, kisi minggu, bilah alat, kartu (bergambar, ringkas, besar, mati), kesimpulan, lipat, pratinjau HP, menu Lainnya, kunci paket.
- Pemeriksaan: skrip galat + gulir mendatar untuk 28 rute pada 1366 dan 375 px dalam tiga kombinasi sakelar, bersih; grep nilai gaya di luar token, bersih. Tanpa potret (sesuai permintaan); pemeriksaan visual penuh di sesi 3.

## Sesi perbaikan 2 (17 Nov 2026)

- Beranda: Aksi cepat; banner mengikuti prioritas §8.1 (berakhir → layanan tanpa slot 7 hari); Kasir tidak melihat banner slot dan item refund.
- Kalender: slot berkapasitas > 1 menampilkan "1/8 peserta" (D-014); menu slot kosong membuka dialog ubah harga dan tutup slot.
- Catat booking: bidang peserta (bila kapasitas > 1), email, saran pelanggan lama, galat "Isi nama pelanggan" saat nama kosong; ditolak dengan penjelasan saat langganan berakhir.
- Slip: contoh booking dua sesi (BKG-M2T5QX); setiap baris riwayat menyebut pelaku dan peran (atau "sistem").
- Pelanggan: kolom email; panel pelanggan dengan riwayat pembayaran (tanpa uang untuk Kasir).
- Pembayaran: pencairan tiga langkah dengan kunci permintaan; keadaan Baru = saldo Rp0, tombol cairkan mati, banner dengan "Tambah rekening".
- Formulir laci: unit (hapus ditolak bila ada booking mendatang), kategori (warna, urutan, hapus mati bila berisi), tambahan (stok terbatas atau tanpa batas), jam ramai, tanggal khusus, ubah harga satu slot, tutup slot.
- Notifikasi: menu per baris, hapus dengan konfirmasi.
- Keadaan: Berakhir = satu banner di halaman yang membuat sesuatu baru, Tambah layanan, Buka slot sekaligus dan Buat promo mati; Memuat (opsi sakelar baru) = kerangka di jadwal Beranda dan tabel Booking. Galat formulir: nama kosong di Catat booking, kode promo ganda atau tidak sah, tutup tanggal dengan booking aktif.
- Label catatan perubahan dilengkapi (Beranda, Kalender, Booking, slip, Pelanggan, Layanan, Unit, Jam Buka & Harga, Halaman Booking); saran Laporan bertanda "ditunda D-021".
- Pemeriksaan (teks saja): 23 uji interaksi lolos; galat + gulir mendatar bersih untuk 31 rute pada 1366 dan 375 px dalam lima kombinasi sakelar.

## Daftar periksa kontrak §8 → layar

| § | Butir wajib | Layar |
|---|---|---|
| 8.1 | Satu banner berprioritas; empat angka; Perlu tindakan; jadwal hari ini + besok; kartu link; aksi cepat; panduan; kosong | Beranda (angka dirangkum jadi satu utama + pendukung, B 8.1 no. 4). Penyegaran 60 detik tidak diperagakan |
| 8.2 | Hari/minggu/bulan; garis sekarang; Hari ini; filter layanan, unit, status; status, harga, kapasitas slot; tindakan slot kosong dan tutup; Atur jam buka; kosong | Kalender; kosong lewat Keadaan = Baru |
| 8.3 | Tab tugas berhitung; cari; filter tanggal, layanan, sumber; baris; Catat booking; ekspor (Medium+); kosong per tab | Booking (urutan "sort" tidak diperagakan) |
| 8.4 | Kode, status, sumber; sesi termasuk multi-slot; pelanggan + WhatsApp; pembayaran + rincian + batas bayar + refund; catatan; riwayat dengan pelaku; tindakan sesuai status dan peran | Slip booking (`bersama.js`) |
| 8.5 | Ringkasan; filter cepat; cari; daftar dengan email; panel: kontak, angka, catatan internal, riwayat booking dan pembayaran; WhatsApp; Buat booking; kosong | Pelanggan, panel pelanggan |
| 8.6 | Layanan (daftar, meter pemakaian, formulir lengkap, peringatan harga, hapus = nonaktifkan); Kategori; Tambahan | Layanan (3 tab, formulir laci) |
| 8.7 | Daftar unit dengan pemakaian; konflik; formulir; hapus hanya tanpa booking mendatang; kosong; Pro | Unit |
| 8.8 | Runway per layanan; Jam buka; Harga (akhir pekan, jam ramai, tanggal khusus, simulator, terapkan harga dasar); Libur & tutup; Slot (ubah, tutup, buka sekaligus dengan pratinjau jumlah) | Jam Buka & Harga |
| 8.9 | Bagikan (link, QR, poster, teks siap pakai); Tampilan + pratinjau; Galeri; Halaman depan (Pro); Domain (Pro) | Halaman Booking |
| 8.10 | Angka; tab Aktif/Terjadwal/Berakhir/Semua; kartu dengan kuota; formulir dengan aturan kode | Promo |
| 8.11 | Rata-rata dan sebaran; tab; balasan dengan saran; sembunyikan; tautan booking | Ulasan |
| 8.12 | Ringkasan; Transaksi; Saldo & pencairan (mutasi, riwayat, tiga langkah); Rekening (kata sandi); Cara kerja | Pembayaran (hanya Pemilik) |
| 8.13 | Satu filter periode; 5 tab; ekspor; buram di Small | Laporan |
| 8.14 | Profil, Kebijakan, Notifikasi, Integrasi, Akun, Tim (per peran) | Pengaturan |
| 8.15 | Paket, pemakaian, perbandingan, riwayat tagihan, perpanjang | Paket & Tagihan (checkout disederhanakan jadi satu dialog) |
| 8.16 | Daftar, filter, tandai dibaca, hapus dengan konfirmasi | Notifikasi |
| 8.17 | Tanya-jawab per halaman, kontak, panduan | Bantuan |

## Peta temuan `review.md` → layar

| Temuan | Layar |
|---|---|
| Lintas 1 satu nama "Catat booking" | Bilah atas, navigasi HP, Kalender, slip, panel pelanggan |
| Lintas 2 lebar halaman | Satu aturan `.view-in`; Notifikasi dan Bantuan sengaja sempit (daftar baca) |
| Lintas 3 ikon kosong | `ui.kosong` dengan ikon diam sesuai isi |
| Lintas 4 sisa bahasa Inggris | Paginasi "1–6 dari 271 booking", "Alamat link booking", "Tambahan" untuk add-on |
| Lintas 5 menu Lainnya HP | Lembar solid berkelompok |
| 8.1 no. 1–5 | Beranda (panduan jadi kartu, Perlu Anda di atas, satu angka + pembanding, urutan HP) |
| 8.2 no. 1–5, fitur 4 | Kalender hari (nama layanan di kolom, slot kosong = ajakan, per unit), minggu, bulan, filter terlipat di HP |
| 8.3 no. 1–5, fitur 2 | Booking (sesi kolom pertama, satu lencana, centang banyak), slip (batas bayar, riwayat), Pengaturan → Kebijakan (selesai otomatis) |
| 8.5 no. 1–5, fitur 5 | Pelanggan (WhatsApp, segmen, Lama tidak datang, Sapa). No. 5 "berikutnya" hanya menampilkan sesi mendatang |
| 8.6 no. 1–4 | Layanan (daftar bawaan, kisi pilihan, slot terbuka sampai, hapus → nonaktifkan, Tambahan "Habis") |
| 8.7 no. 1–2 | Unit (konflik mendatang, lampau dilipat; "x dari 14 jam") |
| 8.8 no. 1–5, fitur 1 | Jam Buka & Harga (slot otomatis, kisi mingguan, tanggal khusus ringkas, slot dengan menu "⋯", banner dengan tombol) |
| 8.9 no. 2–4, fitur 7 | Halaman Booking (warna terong pertama, pratinjau berisi layanan asli, tab Bagikan, link per tempat). No. 1 tidak diterapkan: tidak ada gambar rusak di prototipe |
| 8.10 no. 2, fitur 8 | Promo (kartu ringkas, kartu "jam sepi"), Laporan → Okupansi. No. 1 teknis, di luar prototipe |
| 8.11 no. 3 | Ulasan (label bintang satu baris) |
| 8.12 no. 2–4 | Pembayaran (Cairkan aktif, tab "Cara kerja", status bernilai kecil jadi satu baris). No. 1 teknis |
| 8.13 no. 1–5, fitur 3, 9, 10 | Laporan (pembanding, kesimpulan per tab, dua angka besar, dasar hitung di tiap angka, 30 batang tegak, hari pesan di muka, sumber booking, belum jadi membayar) |
| 8.14 no. 1–4, fitur 6 | Paket ("Semua di …, ditambah:"), Bantuan (kontak terisi), Integrasi ("Segera hadir"), Kebijakan (arti angka 0), undangan Tim (penjelasan peran), ringkasan mingguan |

## Verifikasi akhir (sesi perbaikan 3, 17 Nov 2026)

Semua pemeriksaan otomatis dan berbasis teks; sesuai permintaan, tanpa potret layar.

- **Papan ketik** (7 uji, lolos): fokus terkunci di dialog, laci, lembar dan menu (50 kali Tab/Shift+Tab); garis fokus terlihat, juga di sidebar terong; Esc menutup dan fokus kembali ke pemicu; menu "⋯" dengan panah, Home, End; tab dan segmen dengan panah kiri/kanan; baris tabel dibuka dengan Enter atau spasi.
- **Kontras** dihitung dari warna yang benar-benar tampil (teks terhadap latar terdekat) di 44 rute pada 1366 dan 375 px, dalam dua kombinasi sakelar (bawaan; sidebar terong + Kasir + Small), plus 14 dialog/laci/lembar: tidak ada pasangan di bawah AA (4,5; 3 untuk teks besar). Dikecualikan: kontrol nonaktif, label catatan perubahan, isi buram.
- **Aturan gaya dari DOM** pada rute dan lapisan yang sama: tidak ada teks di bawah 12 px, tidak ada lebih dari satu tombol utama aktif per layar (termasuk bilah atas), tidak ada lebih dari satu banner per halaman, setiap banner punya tindakan, paling banyak dua angka utama, dan tidak ada teks terpotong tanpa cara membaca isinya (judul penuh di `title`, atau terlipat dua baris di HP). `#/sistem` dikecualikan karena katalog.
- **Galat dan gulir:** tanpa galat konsol dan tanpa gulir mendatar untuk semua rute dalam lima kombinasi sakelar (sesi 2), diulang untuk rute yang diubah di sesi 3.
- **Token:** grep `components.css` dan `pages.css` hanya menemukan arsiran slot tutup (pola fungsional yang sengaja dipertahankan). Di `js/pages/` hanya lebar kolom tabel (data tata letak) dan contoh warna di `sistem.js`.
- **Interaksi** (sesi 2, 23 uji, lolos): galat formulir, pencairan tiga langkah, ketujuh formulir laci, keadaan Berakhir, Baru dan Memuat, tampilan Kasir.

**Batas pemeriksaan ini:** tidak ada mata manusia yang menilai keseimbangan visual, jarak dan kerapian setelah sesi perbaikan 1–3; pemeriksaan DOM menangkap pelanggaran aturan, bukan selera. Disarankan satu kali tinjauan visual oleh pengguna sebelum prototipe dipakai sebagai acuan tunggal. Pembaca layar belum diuji.

Handoff untuk proyek asli: `HANDOFF.md`.

## Keterbacaan sesi 1 (17 Nov 2026)

Rencana: `../riwayat/rencana-keterbacaan-prototipe.md`.

- Token: `--t-kecil` 13 px; baris tabel 52 px (satu lapis) / 60 px (dua lapis); kepadatan bawaan **Nyaman**, Padat 44/52.
- `ui.tabel` memakai subgrid (kolom ikut isi) dan kepala lengket. Opsi kolom: `utama`, `nowrap`, `bungkus`, `aksi`. Opsi baris: `grup`, `redup`, `bisaPilih`, `hpSub`. Kepala tabel punya "pilih semua". HP: dua lapis, utama dan akhir 15 px.
- Komponen baru: `titikStatus`, `catatanTabel`, `durasi`, `batangMini`, `aksiIkon`, `perluBlok`. `ui.status` memberi lencana hanya untuk Menunggu bayar; status lain titik + teks.
- Booking: 5 kolom, kelompok per tanggal, peserta hanya untuk layanan grup, kode di slip. Perlu tindakan punya tombol per baris dan centang hanya di baris yang bisa ditutup. Bilah massal menempel di bawah; di HP ada tombol "Pilih". Ada pilihan "Urutkan".
- Slip: tanggal di atas jam ("2 sesi · mulai …"), blok kunyit untuk refund atau sesi belum ditutup, "2 sesi × harga", panah di baris pelanggan, kaki HP sebaris.
- Catat booking: saran pelanggan muncul setelah 2 huruf; "Pilih tanggal" membuka kalender kecil.

## Keterbacaan sesi 2 (17 Nov 2026)

- Pelanggan: kartu ringkasan dihapus (K8, label B). Kolom: Pelanggan (avatar + nama) · Booking · Total dibayar · Terakhir datang · Berikutnya, plus ikon WhatsApp. Lencana segmen hanya untuk "Baru" dan "Lama tidak datang".
- Layanan: catatan "slot terbuka sampai 14 Des" di atas tabel. Pengecualian slot ditulis kunyit di lapis 2. Kapasitas grup ditulis "maks. 8 orang". Durasi "2 jam" (K10). Unit "A +1". Kolom Kapasitas dan Slot dihapus; Populer hanya di kisi (K9).
- Kategori tanpa lencana Aktif. Tambahan: status hanya untuk Nonaktif (baris redup). Unit: "12 orang", "2 layanan", batang mini pemakaian.
- Jam Buka: Slot dikelompokkan per tanggal; slot lewat redup; status titik (Terisi lilak, Ditutup bata). Alasan libur boleh 2 baris.
- Pembayaran: kolom Status dihapus; non-berhasil tampil sebagai lencana di sel Jumlah; kode ORD di detail. Pencairan: keterangan menggantikan lencana "Berhasil".
- Tim: kolom Status dihapus; "Diundang" sebagai lencana di samping nama. Riwayat tagihan: tanpa "Lunas", unduh jadi ikon.

## Keterbacaan sesi 3 (17 Nov 2026)

- Daftar (`ui.lr`): tinggi 56 px, tombol ⋯ redup sampai disorot. Notifikasi dikelompokkan Hari ini / Kemarin / Minggu ini, dengan waktu di kanan.
- Laci: label `kv` 13 px dan nilai 14 px; jarak antarbagian 24 px.
- Beranda HP: urutan Perlu Anda → Jadwal → angka. Jadwal ringkas (sesi lewat dilipat jadi satu kalimat, unit tidak ditulis). Aksi cepat hanya di desktop; panduan berupa lipatan tertutup.
- Kalender hari: nama layanan di kepala kolom boleh 2 baris, durasi di judul.
- Okupansi: sel peta tanpa angka (angka di judul sel), legenda Sepi→Ramai, kesimpulan paling ramai dan paling sepi.
- Promo: kartu berisi potongan, kode, masa berlaku dan batang pemakaian; tab Semua berupa tabel.
- HP: Galeri 2 kolom; Paket memakai segmen Small/Medium/Pro (satu paket sekaligus); Jam Buka satu baris per hari (`ui.jamBukaHp`).
- Baris redup memakai warna `--redup` (lolos kontras), bukan `--teks-mati`.

## Perbaikan akhir HP (17 Nov 2026)

- Tombol "Catat booking" di bilah atas disembunyikan di HP; Catat cukup di nav bawah. Kotak cari kini selebar 263 px di semua halaman.
- Banner di HP: tombol tindakan pindah ke bawah teks, sehingga teks memakai lebar penuh.

## Sesi 4: penutupan P1 audit (17 Nov 2026)

Lihat `../audit-prototipe.md`.
- **T1 tablet:** pada 768–1199 px sidebar menjadi rel ikon 72 px (label lewat `title`); di bawah 1024 px Beranda, kisi dua kolom dan angka menjadi satu kolom; simulator harga tidak lagi meluap.
- **T2 cakupan:** semua butir B dan C disetujui lewat D-025; daftarnya di `../KEPUTUSAN.md`.
- **T3 galat:** Keadaan = Galat. Catat booking dan Pindah jadwal menampilkan "jam baru saja terisi" dengan jam pengganti (isian tetap); tombol simpan di laci/dialog memberi toast galat dan lapisan tetap terbuka; Ajukan pencairan gagal tanpa saldo berkurang; paket Small → kuota habis; contoh sesi berakhir di `#/sistem`. Komponen baru `ui.galatBlok`.
- **T4 data ramai:** Data = Ramai (`js/data-ramai.js`): 25 layanan, 80 tugas, nama panjang. Booking paling banyak 25 baris per halaman dan "Tandai selesai semua (n)"; Kalender hari paling banyak 6 kolom layanan dengan "Layanan n dari m".
- **T5 sentuh:** pada `(hover: none)` tombol ⋯ baris selalu penuh dan minimal 44 px.
- **T6 terpotong:** setiap teks yang terpotong otomatis mendapat `title` berisi teks penuh.
- Pemeriksaan: `cek.py` 44 rute × 3 kombinasi sakelar, 0 masalah; `tepi.py` lebar 768/1024/1920 dan data Ramai pada 375/768/1024/1366, 0 masalah; `uji4.py` 9 uji galat lolos.
