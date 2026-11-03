> **Lampiran BQ-126.** Salinan dokumen rancangan ulang yang disetujui Product Lead pada 4 Nov 2026, disimpan apa adanya sebagai sumber. Bila bertentangan, keputusan di `docs/product-decisions.md` yang berlaku. Contoh: §10 poin 2 (refund) digantikan D-001, dan pembagian fitur paket di §9 diajukan ulang sebagai D-017. Spesifikasi yang berlaku untuk pembangunan ada di `docs/owner-portal.md` setelah BQ-126 Fase 3.

# Rancangan Ulang Portal Owner BookQu

Spesifikasi lama, kondisi saat ini, dan spesifikasi baru

Disusun untuk: Product Lead (Brammy)
Disusun oleh: Tech Lead (agathan), dibantu Claude
Tanggal: 3 November 2026
Status: Usulan, menunggu keputusan Product Lead

Prototype: https://claude.ai/artifact/TPhchm7bsr2rBQKtpuFS3Q (tampilan belum final, lihat Lampiran A)

---

## 0. Ringkasan

### Masalahnya

1. Portal owner dibangun dari spesifikasi menu (21 menu) yang menjelaskan nama dan tujuan setiap menu, tetapi tidak menjelaskan bagaimana menu-menu itu saling tersambung dalam aplikasi.
2. Akibatnya semua halaman sudah dibuat, tetapi tujuh modul (Staff & Resources, Additional Items, Vouchers, Appearance, Payments, Assets, Landing Page) hanya berupa halaman isian yang tidak memengaruhi booking maupun pembayaran. Modul-modul itu disembunyikan, dan tiga lainnya dikunci.
3. Spesifikasi lama sendiri punya kelemahan: disusun menurut jenis data, bukan menurut pekerjaan owner. Beberapa hal diatur di dua atau tiga tempat sekaligus, dan ada bagian yang bertentangan dengan keputusan arsitektur yang sudah diambil.
4. Daftar fitur paket yang dijual saat ini menjanjikan fitur yang belum ada (staf, WhatsApp, voucher, multi-lokasi, white-label, dan lain-lain).
5. Owner bingung menu mana yang harus dibuka untuk satu pekerjaan, dan developer bingung di mana sebuah fitur harus ditempatkan.

### Usulannya

1. **Semua fitur dari spesifikasi lama tetap ada.** Tidak ada fitur yang dibuang; yang berubah adalah penempatan, nama, dan sambungannya.
2. **Menu disusun ulang menurut pekerjaan owner:** 21 menu menjadi 15 menu (14 halaman ditambah Bantuan), semuanya berbahasa Indonesia.
3. **Satu tempat untuk satu hal.** Jam operasional, gambar, saldo, dan aturan booking masing-masing hanya punya satu tempat.
4. **"Staf" diartikan sebagai akun tim dengan hak akses** (Pemilik, Manajer, Kasir). Lapangan, ruang, dan alat menjadi **Unit** yang dipakai bersama oleh beberapa layanan dan dicegah terpesan dua kali.
5. **Daftar fitur paket hanya berisi fitur yang benar-benar ada**, dengan nilai jual yang jelas untuk Medium dan Pro.

### Yang diminta dari Brammy

Daftar lengkap ada di Bagian 10. Yang paling menentukan:

| No | Keputusan | Usulan |
|---|---|---|
| 1 | Setuju susunan 15 menu dan nama barunya | Ya |
| 2 | Daftar fitur paket hanya berisi fitur nyata (menahan rilis) | Ya |
| 3 | Staf = akun tim dengan peran Pemilik, Manajer, Kasir; jumlah akun per paket | Small 0, Medium 2, Pro 10 akun tambahan |
| 4 | Lapangan/ruang/alat menjadi Unit yang dicegah terpesan dua kali | Ya, setelah rilis |
| 5 | Promo dan add-on dihitung ke total pembayaran | Ya, setelah rilis |
| 6 | Logo, sampul, dan warna halaman booking untuk semua paket | Ya |
| 7 | Status "Tidak datang" (no-show) dan kapasitas lebih dari 1 orang per slot | Ya, setelah rilis |
| 8 | Refund saat pembatalan, selisih harga saat pindah jadwal | Sesuai D-001 dan D-006 |

### Dampak ke rilis

Rilis tidak perlu menunggu rancangan ini selesai dibangun. Yang wajib sebelum rilis hanya: menghapus janji fitur yang tidak ada di daftar paket, menyembunyikan kontrol yang tidak berfungsi, dan menyelesaikan masalah rilis yang sudah tercatat. Rancangan baru dikerjakan bertahap sesudahnya, satu halaman per tahap.

---

## 1. Latar belakang

### Bagaimana portal owner dibangun

Portal owner adalah bagian BookQu yang dipakai pemilik bisnis untuk mengelola layanan, jadwal, booking, pelanggan, dan uang. Pedomannya adalah dokumen "Sidebar Functional Specification" yang mendaftar 21 menu beserta tujuan dan isinya (Bagian 2).

Spesifikasi itu menjelaskan setiap menu dengan cukup baik secara terpisah. Yang kurang adalah penjelasan bagaimana satu menu memengaruhi menu lain dalam aplikasi: misalnya bagaimana staf memengaruhi slot yang bisa dipesan, atau bagaimana voucher mengubah total pembayaran. Halaman-halaman dibangun satu per satu, sebagian besar oleh agent AI, dan setiap pembuat mengisi kekosongan itu dengan tafsirannya sendiri.

### Kenapa banyak halaman disembunyikan

Pada September 2026, saat menyiapkan rilis, tim menemukan bahwa banyak halaman terlihat lengkap tetapi tidak benar-benar bekerja. Contohnya, voucher bisa dibuat tetapi tidak bisa dipakai pelanggan saat membayar, dan staf bisa didaftarkan tetapi tidak memengaruhi jadwal. Daripada merilis fitur yang menyesatkan, halaman-halaman itu disembunyikan atau dikunci. Isi beberapa halaman (Analitik, sebagian Pengaturan) bahkan diganti dengan tulisan "Fitur Sedang Dikembangkan".

Audit menyeluruh pada akhir Oktober dan awal November 2026 menghasilkan lebih dari 30 catatan perbaikan untuk portal owner. Sebagian besar berakar pada satu hal: tidak ada gambaran utuh tentang isi setiap halaman dan batas tanggung jawabnya.

---

## 2. Spesifikasi lama (pedoman yang dipakai saat ini)

### Struktur menu

| Grup | Menu |
|---|---|
| — | Dashboard |
| Booking | Calendar, Schedule, Bookings |
| Services | Services, Categories, Staff & Resources, Additional Items |
| Customers | Customers |
| Marketing | Vouchers, Reviews |
| Analytics | Overview, Schedule Report |
| Settings | Business, Appearance, Payments, Assets, Integrations, Subscription |
| — | Landing Page (Pro) |

Total 21 menu.

### Tujuan setiap menu menurut spesifikasi lama

| Menu | Pertanyaan yang dijawab | Isi utama |
|---|---|---|
| Dashboard | Bagaimana kondisi bisnis saya? | Total booking, pendapatan, pelanggan, layanan aktif; booking hari ini dan mendatang; aktivitas; tren pendapatan dan booking; layanan populer; booking yang butuh perhatian; tombol cepat (+Booking, +Layanan, +Staf) |
| Calendar | Apa yang sedang terjadi? | Tampilan harian, mingguan, bulanan; filter layanan, staf, resource, status; lihat, buat, pindah, batalkan booking |
| Schedule | Kapan bisnis saya tersedia? | Jam operasional, jam kerja, istirahat, hari tutup, libur, jam khusus, waktu diblokir |
| Bookings | Booking apa saja yang terjadi? | Daftar booking dengan staf dan resource; status Pending, Confirmed, Completed, Cancelled, No-show; buat, ubah, konfirmasi, pindah, batal, selesai, tandai tidak datang |
| Services | Apa yang saya jual? | Nama, kategori, deskripsi, durasi, harga, kapasitas, gambar, status; terhubung ke staf, resource, jadwal, add-on; duplikat |
| Categories | Bagaimana layanan dikelompokkan? | Nama, deskripsi, gambar, status, urutan tampil |
| Staff & Resources | Siapa dan apa yang menjalankan layanan? | Staf (kontak, peran, layanan, jam kerja, ketersediaan) dan resource (tipe, kapasitas, layanan, ketersediaan) |
| Additional Items | Apa yang bisa dibeli sebagai tambahan? | Item, harga, stok, layanan terkait; ikut masuk total pembayaran |
| Customers | Siapa pelanggan saya? | Daftar pelanggan; detail berisi riwayat booking, riwayat pembayaran, layanan yang dipakai, catatan |
| Vouchers | Bagaimana saya memberi promo? | Kode, jenis dan nilai diskon, minimal transaksi, maksimal diskon, kuota, periode, layanan berlaku; statistik pemakaian |
| Reviews | Bagaimana pelanggan menilai bisnis saya? | Rata-rata rating, daftar ulasan, balas, sembunyikan |
| Analytics Overview | Bagaimana performa bisnis saya? | Pendapatan, booking, pertumbuhan pelanggan, layanan populer, tren, tingkat batal dan selesai |
| Schedule Report | Seberapa optimal kapasitas saya terpakai? | Slot tersedia dan terisi, utilisasi, jam dan hari ramai/sepi, utilisasi staf dan resource |
| Business | Bagaimana aturan operasional bisnis saya? | Info bisnis (logo, zona waktu, mata uang), jam operasional, aturan booking (minimal pemberitahuan, maksimal jauh hari, pembatalan, reschedule, jeda, konfirmasi otomatis) |
| Appearance | Bagaimana tampilan bisnis saya? | Logo, sampul, deskripsi, warna, gaya tombol, font, tata letak, gaya kartu layanan |
| Payments | Bagaimana saya menerima dan mengelola pembayaran? | Ringkasan, transaksi, saldo, penarikan, pengaturan; gateway platform atau gateway milik bisnis |
| Assets | File dan media bisnis saya apa saja? | Pusat media: unggah, pratinjau, ganti, hapus |
| Integrations | Layanan luar apa yang terhubung? | Midtrans, email, WhatsApp, Google Calendar, Google Analytics, API, webhook; hubungkan, putuskan, tes |
| Subscription | Paket saya apa dan apa yang bisa saya gunakan? | Paket aktif, manfaat, pemakaian, tagihan, riwayat, naik dan turun paket |
| Landing Page (Pro) | Bagaimana saya membuat website bisnis saya? | Pilih template, atur bagian halaman, isi konten, pratinjau, terbitkan |

### Hubungan antar-menu menurut spesifikasi lama

Spesifikasi lama menggambarkan alur berikut:

- Layanan memakai staf, resource, dan kategori.
- Staf dan resource menentukan jadwal dan ketersediaan slot.
- Ketersediaan tampil di kalender, lalu dipesan menjadi booking.
- Booking terhubung ke pelanggan, pembayaran, dan ulasan.
- Voucher dan add-on berada di tengah transaksi, sebelum pembayaran.
- Analitik dan dashboard membaca semua data itu.
- Landing page memakai data bisnis: layanan, ulasan, aset, dan branding.

Alur inilah yang belum dibangun untuk sebagian besar modul (Bagian 3).

---

## 3. Kondisi aplikasi saat ini

### Ringkasan

- **Nama dan susunan menu mengikuti spesifikasi lama hampir persis.** Semua 21 menu punya halamannya, ditambah halaman Notifikasi dan menu Saldo & Withdraw yang tidak ada di spesifikasi.
- **Halaman inti sudah tersambung dan berfungsi:** Dashboard, Kalender, Jadwal, Booking, Layanan, Kategori, Pelanggan, Ulasan, Pengaturan bisnis, Saldo & Withdraw, Langganan.
- **Tujuh modul hanya berupa halaman isian yang tidak tersambung**, sehingga disembunyikan. Tiga modul dikunci.

Gambaran sederhananya: spesifikasi lama adalah denah rumah, dan aplikasi sekarang adalah rumah yang semua ruangannya sudah berdiri, tetapi pipa dan kabel antar-ruangannya baru terpasang di ruangan inti.

### Status semua halaman

Keterangan status: **Rilis** berarti tampil dan bisa dipakai; **Terkunci** berarti tampil dengan label "Terkunci" dan hanya bisa dilihat; **Tersembunyi** berarti tidak ada di menu dan tidak bisa dibuka, walaupun halamannya sudah dibuat.

Keterangan kesesuaian: **Sesuai** berarti isi utama spesifikasi terpenuhi; **Sebagian** berarti ada tetapi kurang lengkap; **Hanya tampilan** berarti halaman dan isian ada, tetapi tidak memengaruhi booking atau pembayaran; **Bertentangan** berarti berlawanan dengan keputusan yang sudah diambil.

| Menu | Status | Kesesuaian dengan spesifikasi |
|---|---|---|
| Dashboard | Rilis | Sebagian |
| Notifications (di luar spesifikasi) | Rilis | — |
| Calendar | Rilis | Sebagian |
| Schedule | Rilis | Sebagian, dan mengerjakan tugas menu lain |
| Bookings | Rilis | Sebagian |
| Services | Rilis | Sebagian |
| Categories | Rilis | Sebagian |
| Staff & Resources | Tersembunyi | Hanya tampilan |
| Additional Items | Tersembunyi | Hanya tampilan |
| Customers | Rilis | Sesuai |
| Vouchers | Tersembunyi | Hanya tampilan |
| Reviews | Rilis | Sesuai |
| Analytics Overview | Terkunci | Isinya diganti tulisan "sedang dikembangkan" |
| Schedule Report | Terkunci | Sebagian |
| Business | Rilis | Sebagian kecil |
| Appearance | Tersembunyi | Sesuai, tetapi owner tidak bisa membukanya |
| Payments | Tersembunyi | Bertentangan, dan dobel dengan Saldo |
| Saldo & Withdraw (di luar spesifikasi) | Rilis | — |
| Assets | Tersembunyi | Hanya tampilan |
| Integrations | Terkunci | Hanya tampilan |
| Subscription (+ 3 halaman checkout) | Rilis | Sebagian |
| Landing Page (Pro) | Tersembunyi | Hanya tampilan |

### Rincian per menu

**Dashboard (Rilis, sebagian).** Berisi sapaan, ajakan melengkapi profil, banner masa trial, 4 kartu statistik, grafik pertumbuhan pendapatan, tren harian, booking terbaru, dan jadwal mendatang. Belum ada daftar "booking yang butuh perhatian" dan tombol cepat. Grafiknya sama dengan yang diminta untuk Analitik, sehingga keduanya tumpang tindih. Audit juga menemukan celah keamanan pada pembaruan otomatis dashboard yang harus ditutup sebelum rilis.

**Calendar (Rilis, sebagian).** Tampilan harian, mingguan, bulanan; filter layanan dan status; detail booking; walk-in; pindah jadwal. Tidak ada filter staf dan resource karena keduanya tidak tersambung ke booking. Detail booking di kalender memakai jendela sendiri yang berbeda dengan di halaman Booking. Navigasi bulan bisa melompati satu bulan bila dibuka dari tanggal 29–31.

**Schedule (Rilis, sebagian).** Cara kerjanya berbeda dari spesifikasi: bukan jam operasional mingguan, melainkan slot yang dibuat massal per layanan per tanggal, ditambah tanggal tutup dan harga akhir pekan. Halaman ini juga berisi hal-hal yang menurut spesifikasi bukan tugasnya: kisi booking mingguan, "Recent Activity", panel "Bulk Price Adjustments" yang tidak tersambung ke apa pun, dan pengaturan batas pembatalan serta pindah jadwal. Pembuatan slot massal belum punya batas aman, dan menutup tanggal tidak memeriksa booking yang sudah ada.

**Bookings (Rilis, sebagian).** Daftar, filter, detail, pindah jadwal, batal, tandai selesai. Status di sistem adalah Menunggu bayar, Lunas, Selesai, Dibatalkan; belum ada "Confirmed" dan "No-show" seperti di spesifikasi. Nominal yang ditampilkan dihitung ulang dari harga layanan terbaru, sehingga bisa berbeda dari yang benar-benar dibayar pelanggan. Ada juga beberapa masalah saat pindah jadwal (pilihan slot bisa tidak sesuai tanggal, hari tutup tampil tersedia).

**Services (Rilis, sebagian).** Nama, kategori, harga, durasi, kapasitas, gambar, status. Kapasitas disimpan tetapi tidak dipakai saat menghitung slot. Belum ada fitur duplikat. Mengubah durasi layanan mengubah jam booking lama yang ditampilkan.

**Categories (Rilis, sebagian).** Nama, deskripsi, warna, status. Belum ada gambar dan pengaturan urutan tampil.

**Staff & Resources (Tersembunyi, hanya tampilan).** Dua tab: staf (nama, email, peran, layanan, ketersediaan dalam bentuk teks bebas) dan resource (nama, tipe, kapasitas, lokasi). Tidak memengaruhi slot maupun booking sama sekali.

**Additional Items (Tersembunyi, hanya tampilan).** Daftar item tambahan dengan harga dan stok, bisa ditautkan ke layanan. Tidak muncul di halaman pembayaran pelanggan dan tidak masuk total.

**Customers (Rilis, sesuai).** Ringkasan, daftar, dan detail pelanggan dengan riwayat booking, riwayat pembayaran, layanan yang dipakai, dan catatan. Tautan WhatsApp masih memakai format nomor 08… sehingga gagal dibuka.

**Vouchers (Tersembunyi, hanya tampilan).** Semua isian spesifikasi ada dan bisa disimpan. Saat pelanggan memasukkan kode, sistem menjawab "Fitur voucher saat ini belum aktif". Belum ada statistik pemakaian.

**Reviews (Rilis, sesuai).** Rata-rata, sebaran bintang, daftar, balas, sembunyikan. Ada masalah kecil: pencarian menghapus filter bintang.

**Analytics Overview (Terkunci).** Isinya diganti tulisan "Fitur Sedang Dikembangkan". Perhitungan aslinya masih ada, tetapi audit menemukan pendapatan booking beberapa slot terhitung ganda, perhitungannya lambat, dan file ekspor tetap bisa diunduh walaupun halamannya dikunci.

**Schedule Report (Terkunci, sebagian).** Utilisasi, slot tersedia dan terisi, jam dan hari ramai/sepi, beban staf dan resource. Karena staf dan resource tidak tersambung ke booking, angka bebannya hanya perkiraan.

**Business (Rilis, sebagian kecil).** Profil bisnis, akun, preferensi notifikasi, hapus akun. Preferensi notifikasi hanya tersimpan di browser, jadi hilang bila ganti perangkat. Isian logo dihapus saat penyembunyian fitur. Tidak ada jam operasional dan aturan booking; batas pembatalan dan pindah jadwal berada di halaman Schedule. Mengubah nama bisnis mengubah link booking tanpa peringatan. Mengubah atau menghapus akun belum meminta password saat ini.

**Appearance (Tersembunyi, sesuai).** Warna, font, gaya tombol, gaya kartu, deskripsi, dengan pratinjau. Pengaturan ini benar-benar dipakai halaman booking pelanggan, tetapi owner tidak bisa mengubahnya karena halamannya tersembunyi.

**Payments (Tersembunyi, bertentangan).** Berisi pilihan "pembayaran lewat platform" atau "gateway milik bisnis sendiri" lengkap dengan isian kunci Midtrans, ditambah riwayat transaksi dan penarikan. Pilihan gateway milik bisnis bertentangan dengan keputusan arsitektur bahwa semua pembayaran lewat rekening BookQu. Saldo dan penarikan juga sudah ada di menu Saldo & Withdraw.

**Assets (Tersembunyi, hanya tampilan).** Unggah, pratinjau, hapus gambar. Tidak ada halaman lain yang memakainya; layanan, tampilan, dan landing page punya unggahan sendiri.

**Integrations (Terkunci, hanya tampilan).** Lima kartu (Midtrans, email, WhatsApp, Google Calendar, Google Analytics/Meta Pixel) yang hanya menampilkan status sistem BookQu, bukan koneksi milik owner. Tidak ada tombol hubungkan atau putuskan.

**Subscription (Rilis, sebagian).** Paket aktif, pemakaian, daftar paket, riwayat tagihan, pembayaran langganan. Daftar manfaat paket menjanjikan fitur yang tidak ada. Harga di halaman depan BookQu ditulis langsung, bukan diambil dari data paket.

**Landing Page (Tersembunyi, hanya tampilan).** Ada langkah memilih template, memilih bagian halaman, dan mengisi domain. Yang benar-benar tersimpan hanya domain, warna, dan banner. Tidak ada halaman publik yang bisa diterbitkan.

---

## 4. Audit spesifikasi lama

Pertanyaan yang diajukan: kalau spesifikasi lama dibangun 100% sesuai tulisannya, apakah hasilnya baik? Jawabannya: fiturnya benar, tetapi peta penempatannya bermasalah. Ada enam masalah.

### 4.1 Disusun menurut jenis data, bukan pekerjaan owner

Menu dikelompokkan seperti tabel di database (layanan, staf, voucher, aset), bukan seperti cara owner bekerja. Satu pekerjaan tersebar di banyak menu. Contoh "mengatur kapan saya buka":

- jam operasional ada di Business dan di Schedule;
- jam kerja staf ada di Staff & Resources;
- hari libur ada di Business dan di Schedule;
- aturan booking ada di Business.

### 4.2 Tumpang tindih yang tertulis di spesifikasi

| Bagian yang bertabrakan | Isi yang sama |
|---|---|
| Dashboard dan Analytics Overview | Tren pendapatan, tren booking, layanan populer |
| Business dan Schedule | Jam operasional, istirahat, hari tutup, libur, jam khusus |
| Appearance, Landing Page, dan Assets | Logo, banner, gambar, warna, tampilan publik |
| Payments dan Saldo | Saldo dan penarikan |
| Integrations dan notifikasi | Pengingat WhatsApp dan konfirmasi booking diletakkan di Integrations, padahal itu pengaturan notifikasi |
| Calendar dan Bookings | Keduanya membuat, memindah, dan membatalkan booking tanpa aturan bahwa keduanya memakai komponen yang sama |

### 4.3 Bertentangan dengan keputusan yang sudah diambil

- Status booking "Confirmed" dan "No-show", sementara sistem memakai Menunggu bayar, Lunas, Selesai, Dibatalkan.
- Gateway milik bisnis, sementara BookQu sudah memutuskan semua pembayaran lewat rekening platform.
- Pembayaran ditulis sebagai "Midtrans configuration", sementara gateway utama sekarang SingaPay.

### 4.4 Ambigu

- **Staf:** penyedia layanan yang dipilih pelanggan, atau orang yang login ke dashboard? Spesifikasi mencampur keduanya.
- **Jadwal di empat tingkat** (bisnis, layanan, staf, resource) tanpa aturan mana yang berlaku bila bertabrakan.
- **Kapasitas layanan:** satu slot bisa diisi berapa orang? Tidak dijelaskan.
- **Status pembayaran "Refunded":** ada, tetapi alur refund-nya tidak dijelaskan.

### 4.5 Kebutuhan owner yang tidak tercakup

- Link dan QR halaman booking serta cara membagikannya.
- Notifikasi.
- Panduan untuk owner baru.
- Bantuan.
- Kebijakan yang tampil ke pelanggan saat membayar.
- Akun tim dengan hak akses.
- Proses refund.
- Uang muka (DP), padahal halaman depan BookQu menjanjikannya.

### 4.6 Terlalu banyak menu

21 menu terlalu banyak untuk usaha kecil yang membuka aplikasi dari HP di meja kasir. Grup Settings sendiri berisi enam menu yang mencampur uang, tampilan, file, dan langganan.

---

## 5. Prinsip rancangan baru

1. **Satu halaman, satu tugas.** Setiap halaman menjawab satu pertanyaan owner.
2. **Satu tempat untuk satu hal.** Setiap data dibuat dan diubah di satu halaman saja; halaman lain hanya menampilkan dan membuka panel yang sama.
3. **Satu panel untuk satu jenis data.** Detail booking selalu memakai panel yang sama, dari mana pun dibuka. Begitu juga detail pelanggan.
4. **Hari ini dan riwayat dipisah.** Beranda hanya berisi hari ini dan yang perlu ditindaklanjuti. Grafik dan tren ada di Laporan.
5. **Pengaturan dan operasional dipisah.** Halaman yang mengatur aturan (Jam Buka & Harga, Pengaturan) tidak menampilkan operasional harian, dan sebaliknya.
6. **Integrasi diletakkan bersama tugasnya.** Pengingat WhatsApp diatur sebagai notifikasi; koneksi akun luar dikumpulkan di satu tab Integrasi.
7. **Hanya fitur yang berfungsi yang ditampilkan.** Tidak ada tombol, kartu, atau panel yang terlihat bekerja padahal tidak.
8. **Daftar fitur paket hanya menjanjikan fitur nyata.**
9. **Fitur di atas paket owner ditampilkan dengan cara yang sama di semua halaman:** satu ajakan naik paket yang seragam.
10. **Hak akses dicek di server.** Menyembunyikan menu saja tidak cukup; server tetap menolak akses peran yang tidak berhak.
11. **Bahasa Indonesia yang dimengerti owner.** Nama menu menyebut tujuan, bukan istilah teknis.
12. **Dirancang untuk HP.** Halaman harian harus nyaman dipakai di HP, karena owner dan kasir bekerja di meja kasir.

---

## 6. Spesifikasi baru

### 6.1 Struktur menu

| Grup | Menu | Isi utama |
|---|---|---|
| — | Beranda | Hari ini dan yang perlu ditindaklanjuti |
| — | Kalender | Siapa datang kapan; walk-in |
| — | Booking | Semua booking dan tindak lanjutnya |
| — | Pelanggan | Data dan riwayat pelanggan |
| Bisnis saya | Layanan | Tab: Layanan, Kategori, Add-on |
| Bisnis saya | Unit | Lapangan, ruang, alat yang dipakai layanan |
| Bisnis saya | Jam Buka & Harga | Tab: Jam Buka, Harga, Libur & Tutup, Slot |
| Pemasaran | Halaman Booking | Link & QR, materi promosi, tampilan, galeri media, landing page (Pro) |
| Pemasaran | Promo | Kode promo dan statistiknya |
| Pemasaran | Ulasan | Rating dan balasan |
| Keuangan | Pembayaran | Tab: Ringkasan, Transaksi, Saldo & Pencairan, Rekening, Pengaturan |
| Keuangan | Laporan | Tab: Ringkasan, Pendapatan, Okupansi, Layanan, Pelanggan |
| Lainnya | Pengaturan | Tab: Profil Bisnis, Kebijakan Booking, Notifikasi, Integrasi, Akun, Tim |
| Lainnya | Paket & Tagihan | Paket, pemakaian, tagihan |
| Lainnya | Bantuan | Pertanyaan umum dan kontak BookQu |

Di bagian atas setiap halaman ada lonceng **Notifikasi**, tombol **+ Booking**, dan **pencarian** (kode booking, nama, atau nomor HP). Di HP, menu diganti navigasi bawah: Beranda, Kalender, + Booking, Booking, Lainnya.

### 6.2 Rincian per halaman

Setiap halaman dijelaskan dengan: pertanyaan yang dijawab, siapa yang boleh membuka, paket, isi, aksi, yang sengaja tidak ada di halaman itu, dan asalnya dari menu lama.

**Beranda**
- **Pertanyaan:** Apa yang harus saya tahu dan lakukan hari ini?
- **Akses:** Pemilik, Manajer, Kasir (Kasir tanpa angka pendapatan). Semua paket.
- **Isi:**
  - satu banner status menurut prioritas: paket berakhir, trial hampir habis, slot hampir habis, rekening belum diisi, batas booking bulanan hampir tercapai;
  - empat angka hari ini: jumlah booking (online dan walk-in), menunggu pembayaran, pendapatan, persentase slot terisi;
  - "Perlu tindakan": permintaan pembatalan, booking yang belum ditandai selesai, ulasan buruk yang belum dibalas, status pencairan;
  - jadwal hari ini dengan penanda "sedang berlangsung", ringkasan besok, kartu link booking;
  - tombol cepat: + Booking, + Layanan, + Promo, + Anggota tim.
- **Owner baru:** panduan 4 langkah sampai siap menerima booking (lengkapi profil, tambah layanan, atur jam buka, bagikan link).
- **Tidak ada di sini:** grafik dan tren (ada di Laporan).
- **Asal:** Dashboard.

**Kalender**
- **Pertanyaan:** Kapan sibuk, kapan kosong, dan siapa yang datang?
- **Akses:** Semua peran. Semua paket.
- **Isi:** tampilan hari (kisi jam × layanan), minggu, bulan; filter layanan, unit, dan status; garis "sekarang"; di HP berupa daftar per jam.
- **Aksi:** klik slot kosong untuk mencatat walk-in atau menutup slot; klik booking untuk membuka panel detail booking.
- **Tidak ada di sini:** membuat slot massal dan mengatur harga (ada di Jam Buka & Harga).
- **Asal:** Calendar.

**Booking**
- **Pertanyaan:** Booking apa saja yang ada dan mana yang perlu saya urus?
- **Akses:** Semua peran (Kasir tidak bisa membatalkan, hanya mengajukan). Ekspor untuk Medium ke atas.
- **Isi:**
  - tab menurut tugas: Perlu tindakan, Mendatang, Menunggu bayar, Selesai, Dibatalkan, Semua;
  - pencarian dan filter (tanggal, layanan, sumber online/walk-in, urutan);
  - daftar dengan nominal yang sama persis dengan yang dibayar pelanggan.
- **Panel detail booking** (dipakai di semua halaman): sesi, pelanggan, pembayaran, add-on dan promo, permintaan pelanggan, riwayat lengkap dengan nama pelaku. Aksinya hanya yang sah menurut status: tandai selesai, tandai tidak datang, pindah jadwal, batalkan (dengan pilihan refund), kirim ulang link.
- **Asal:** Bookings.

**Pelanggan**
- **Pertanyaan:** Siapa pelanggan saya dan bagaimana menghubunginya?
- **Akses:** Semua peran. Semua paket.
- **Isi:** ringkasan (total, baru bulan ini, pelanggan kembali), filter cepat (baru, langganan, lama tidak datang), daftar, dan panel detail berisi kontak, statistik, catatan internal, riwayat booking dan pembayaran.
- **Aksi:** hubungi lewat WhatsApp atau email, tambah catatan, buat booking untuk pelanggan ini.
- **Asal:** Customers.

**Layanan**
- **Pertanyaan:** Apa yang saya jual?
- **Akses:** Pemilik, Manajer. Batas jumlah layanan menurut paket.
- **Tab Layanan:** nama, kategori, harga dasar, durasi, kapasitas per slot, deskripsi, foto (dari galeri), status, unit yang dipakai. Peringatan bahwa perubahan harga dan durasi hanya berlaku untuk booking baru. Layanan dengan riwayat booking tidak bisa dihapus, hanya dinonaktifkan.
- **Tab Kategori:** nama, deskripsi, gambar, status, urutan tampil.
- **Tab Add-on:** item tambahan dengan harga, stok, dan layanan terkait. Add-on dipilih pelanggan saat booking dan ikut masuk total pembayaran.
- **Asal:** Services, Categories, Additional Items.

**Unit**
- **Pertanyaan:** Lapangan, ruang, atau alat apa yang dipakai layanan saya?
- **Akses:** Pemilik, Manajer.
- **Isi:** daftar unit dengan tipe, kapasitas, layanan yang memakainya, persentase terpakai hari ini, status. Satu unit bisa dipakai beberapa layanan, dan sistem menolak dua booking di unit yang sama pada jam yang sama. Halaman ini juga menunjukkan bentrok yang terjadi di data saat ini.
- **Contoh nyata:** Paket Turnamen Futsal dan sewa Lapangan Futsal A memakai lapangan yang sama. Saat ini keduanya bisa terpesan di jam yang sama karena layanannya berbeda. Dengan Unit, bentrok ini dicegah.
- **Asal:** bagian "Resources" dari Staff & Resources.

**Jam Buka & Harga**
- **Pertanyaan:** Kapan saya buka, berapa harganya, dan kapan tutup?
- **Akses:** Pemilik, Manajer.
- **Isi:**
  - penanda sampai kapan slot terbuka;
  - tab **Jam Buka**: jam operasional mingguan, istirahat, berapa hari ke depan slot dibuka;
  - tab **Harga**: tambahan akhir pekan, jam ramai, harga tanggal khusus, simulasi harga per jam;
  - tab **Libur & Tutup**: tanggal tutup, dengan pemeriksaan booking yang sudah ada;
  - tab **Slot**: daftar slot, tutup/buka/ubah harga satu slot, buat slot massal dengan pratinjau dan batas aman.
- **Tidak ada di sini:** daftar booking, aktivitas, dan batas pembatalan.
- **Asal:** Schedule, ditambah jam operasional dari Business.

**Halaman Booking**
- **Pertanyaan:** Bagaimana pelanggan menemukan saya, dan seperti apa tampilannya?
- **Akses:** Pemilik, Manajer. Link, QR, tampilan, dan galeri untuk semua paket; landing page untuk Pro.
- **Isi:**
  - link dengan salin, buka, bagikan ke WhatsApp; QR dan poster untuk meja kasir;
  - materi promosi: teks bio Instagram, pesan WhatsApp siap kirim;
  - tampilan: logo, sampul, warna, gaya, deskripsi, dengan pratinjau HP langsung;
  - **galeri media**: satu tempat untuk semua gambar, dipakai oleh logo, sampul, layanan, unit, dan landing page;
  - **landing page (Pro)**: template, bagian halaman, judul, domain sendiri, pratinjau, terbitkan.
- **Asal:** Appearance, Assets, Landing Page.

**Promo**
- **Pertanyaan:** Bagaimana saya memberi promo, dan apakah promonya berhasil?
- **Akses:** Pemilik, Manajer. Medium ke atas.
- **Isi:** statistik (promo aktif, total dipakai, diskon diberikan, pendapatan dari booking berpromo); tab Aktif, Terjadwal, Berakhir, Semua; kartu promo dengan kuota terpakai.
- **Isian promo:** kode, jenis diskon (persen atau potongan), nilai, minimal transaksi, maksimal diskon, kuota, periode, layanan yang berlaku. Diskon dihitung sebelum pembayaran.
- **Asal:** Vouchers.

**Ulasan**
- **Pertanyaan:** Apa kata pelanggan, dan sudahkah saya menanggapinya?
- **Akses:** Pemilik, Manajer. Semua paket.
- **Isi:** rata-rata dan sebaran bintang, tab Belum dibalas, Semua, Disembunyikan; balas dengan contoh kalimat; sembunyikan dari halaman booking.
- **Asal:** Reviews.

**Pembayaran**
- **Pertanyaan:** Bagaimana saya menerima uang, berapa saldo saya, dan kapan bisa dicairkan?
- **Akses:** Pemilik saja.
- **Tab Ringkasan:** saldo tersedia, dalam proses, total dicairkan; jumlah pembayaran berhasil, menunggu, gagal, refund; transaksi terbaru.
- **Tab Transaksi:** semua pembayaran pelanggan beserta metode dan statusnya.
- **Tab Saldo & Pencairan:** mutasi saldo (dari booking mana) dan riwayat pencairan dengan tahapan diajukan, disetujui, ditransfer.
- **Tab Rekening:** rekening tujuan pencairan.
- **Tab Pengaturan:** penjelasan bahwa pembayaran diproses BookQu (QRIS, virtual account, e-wallet), biaya platform 0%, batas waktu bayar, aturan refund.
- **Asal:** Payments dan Saldo & Withdraw. Pilihan gateway milik bisnis tidak ditawarkan.

**Laporan**
- **Pertanyaan:** Bagaimana performa bisnis saya, dan apa yang bisa diperbaiki?
- **Akses:** Pemilik, Manajer. Medium ke atas; paket Small melihat pratinjau buram dengan ajakan naik paket.
- **Tab Ringkasan:** pendapatan, booking, pelanggan baru, tingkat pembatalan, tingkat selesai, rating, layanan terlaris, unit paling terpakai.
- **Tab Pendapatan, Okupansi, Layanan, Pelanggan:** tren pendapatan (online dan walk-in), peta jam ramai dan sepi, utilisasi per unit, peringkat layanan, pelanggan baru dan kembali. Ekspor CSV.
- **Asal:** Analytics Overview dan Schedule Report, ditambah grafik dari Dashboard.

**Pengaturan**
- **Akses:** Pemilik semua tab; Manajer tanpa tab Tim dan tanpa hapus akun.
- **Profil Bisnis:** nama, link (dengan peringatan bila diubah), kategori, alamat, peta, WhatsApp, email, zona waktu, mata uang, logo.
- **Kebijakan Booking:** batas pembatalan, batas pindah jadwal, aturan refund, minimal pemesanan sebelum sesi, maksimal hari ke depan, jeda antar booking, konfirmasi otomatis, dan pratinjau teks yang dilihat pelanggan saat membayar.
- **Notifikasi:** pilihan per kejadian (booking baru, pembayaran, pembatalan, pindah jadwal, ulasan, pencairan) per saluran (email, WhatsApp, aplikasi), tersimpan di server.
- **Integrasi:** WhatsApp Business, Google Calendar, Google Analytics dan Meta Pixel, API dan webhook (Pro), masing-masing dengan hubungkan, tes, putuskan. Pembayaran dan email ditandai "dikelola BookQu".
- **Akun:** nama, email, password (wajib password saat ini), hapus akun.
- **Tim:** lihat Bagian 8.
- **Asal:** Business, Integrations, dan bagian pengaturan dari Schedule.

**Paket & Tagihan**
- **Akses:** Pemilik saja.
- **Isi:** paket aktif dan masa berlaku, pemakaian (layanan, booking bulan ini, akun tim), perbandingan paket yang jujur, riwayat tagihan, naik atau turun paket. Satu-satunya tempat untuk naik paket.
- **Asal:** Subscription.

**Notifikasi dan Bantuan**
- Notifikasi dibuka dari lonceng; mengklik notifikasi menandainya terbaca dan membuka tujuannya.
- Bantuan berisi pertanyaan umum dan kontak WhatsApp BookQu.

---

## 7. Peta perubahan dan alasannya

| Menu lama | Menjadi | Jenis perubahan | Alasan |
|---|---|---|---|
| Dashboard | Beranda | Isi diubah | Dashboard dan Analitik tumpang tindih; Beranda fokus ke hari ini, tren pindah ke Laporan |
| Calendar | Kalender | Dipertahankan | Ditambah filter unit dan panel detail yang sama dengan Booking |
| Schedule | Jam Buka & Harga | Nama dan isi diubah | Menjadi satu-satunya tempat jam operasional, libur, dan harga; daftar booking dan aktivitas dikeluarkan |
| Bookings | Booking | Dipertahankan | Tab disusun menurut tugas; nominal sesuai yang dibayar |
| Services | Layanan (tab Layanan) | Dipertahankan | — |
| Categories | Layanan (tab Kategori) | Digabung | Kategori hanya mengelompokkan layanan, tidak perlu menu sendiri |
| Staff & Resources: Staff | Pengaturan → Tim | Diubah artinya | Staf adalah akun tim yang login dengan hak akses terbatas (keputusan Tech Lead dan developer) |
| Staff & Resources: Resources | Unit | Diubah dan disambungkan | Lapangan dan ruang dipakai beberapa layanan; tanpa Unit, bentrok tidak bisa dicegah |
| Additional Items | Layanan (tab Add-on) | Digabung dan disambungkan | Add-on selalu menempel pada layanan; harus masuk total pembayaran |
| Customers | Pelanggan | Dipertahankan | — |
| Vouchers | Promo | Nama diubah dan disambungkan | Nama yang dimengerti owner; harus bisa dipakai saat membayar |
| Reviews | Ulasan | Dipertahankan | — |
| Analytics Overview | Laporan (tab Ringkasan dan lainnya) | Digabung | Satu tempat untuk semua angka historis |
| Schedule Report | Laporan (tab Okupansi) | Digabung | Utilisasi tidak perlu diukur di dua tempat |
| Business | Pengaturan (Profil Bisnis, Kebijakan Booking) | Dipecah | Jam operasional pindah ke Jam Buka & Harga; aturan booking dikumpulkan di satu tab |
| Appearance | Halaman Booking (Tampilan) | Dipindah | Satu halaman untuk "wajah bisnis saya"; dibuka untuk semua paket |
| Payments | Pembayaran | Dipertahankan, gateway milik bisnis dihapus | Mengikuti struktur yang diusulkan spesifikasi lama sendiri; gateway milik bisnis bertentangan dengan keputusan arsitektur |
| Saldo & Withdraw | Pembayaran (tab Saldo & Pencairan) | Digabung | Menghilangkan dobel |
| Assets | Galeri media di Halaman Booking dan pemilih gambar | Dipindah | Media terpusat tetap ada, tetapi sebagai alat yang dipakai di setiap unggahan, bukan menu tersendiri |
| Integrations | Pengaturan (tab Integrasi) | Dipindah dan disambungkan | Hanya koneksi milik owner; layanan yang dikelola BookQu ditandai jelas |
| Subscription | Paket & Tagihan | Nama diubah | "Langganan" mudah tertukar dengan pelanggan berlangganan |
| Landing Page (Pro) | Halaman Booking (Landing Page) | Dipindah | Satu tempat untuk semua yang dilihat pelanggan |
| Notifications | Lonceng di atas | Keluar dari menu | Sudah bisa dibuka dari lonceng |
| (baru) | Halaman Booking: link, QR, materi promosi | Ditambah | Cara utama owner mendapatkan pelanggan, belum ada di spesifikasi |
| (baru) | Bantuan | Ditambah | Owner butuh jalur bantuan yang jelas |
| (baru) | + Booking, pencarian, navigasi HP | Ditambah | Kasir tidak boleh harus berpindah halaman untuk mencatat walk-in atau mencari booking |

**Semua fitur dari spesifikasi lama tetap ada.** Yang berubah adalah tempat, nama, dan sambungannya.

---

## 8. Peran dan hak akses (Tim)

### Arti "staf"

Staf adalah anggota tim pemilik bisnis yang login ke dashboard, misalnya kasir di meja depan. Staf **bukan** penyedia layanan yang dipilih pelanggan saat booking. Setiap anggota tim punya peran yang menentukan menu dan aksi yang boleh dipakai.

### Peran

| Halaman dan aksi | Pemilik | Manajer | Kasir |
|---|---|---|---|
| Beranda, Kalender, Booking, Pelanggan | Ya | Ya | Ya, tanpa angka pendapatan |
| Mencatat walk-in, tandai selesai, pindah jadwal | Ya | Ya | Ya |
| Membatalkan booking dan refund | Ya | Ya | Hanya mengajukan, disetujui Pemilik atau Manajer |
| Layanan, Unit, Jam Buka & Harga | Ya | Ya | Tidak |
| Halaman Booking, Promo, Ulasan | Ya | Ya | Tidak |
| Laporan | Ya | Ya | Tidak |
| Pembayaran (saldo, pencairan, rekening) | Ya | Tidak | Tidak |
| Pengaturan | Ya | Ya, tanpa Tim dan hapus akun | Tidak |
| Paket & Tagihan, Tim | Ya | Tidak | Tidak |

Menu yang tidak diizinkan disembunyikan, dan server tetap menolak aksesnya walaupun seseorang mengetik alamat halaman secara langsung.

### Jejak aktivitas

Setiap aksi dicatat dengan nama pelakunya, misalnya "Walk-in dicatat oleh Siska (Kasir)". Catatan ini tampil di riwayat booking dan di log aktivitas tim yang bisa dilihat Pemilik.

### Jumlah akun per paket (usulan)

| Paket | Akun tambahan |
|---|---|
| Small | 0 (pemilik saja) |
| Medium | 2 |
| Pro | 10 |

Peran buatan sendiri (owner mencentang hak akses sendiri) bisa ditambahkan kemudian bila ada permintaan.

---

## 9. Paket dan fitur

Daftar fitur paket hanya berisi fitur yang benar-benar ada. Fitur yang sedang dibangun boleh tampil dengan label "Segera hadir".

| Fitur | Small | Medium | Pro |
|---|---|---|---|
| Halaman booking, kalender, slot, booking, walk-in | Ya | Ya | Ya |
| Pelanggan, ulasan, pembayaran, saldo dan pencairan | Ya | Ya | Ya |
| Notifikasi email, invoice | Ya | Ya | Ya |
| Logo, sampul, warna halaman booking; link, QR, materi promosi | Ya | Ya | Ya |
| Jumlah layanan | 5 | Tanpa batas | Tanpa batas |
| Booking per bulan | 300 | 500 | Tanpa batas |
| Laporan dan ekspor | — | Ya | Ya |
| Promo dan add-on | — | Ya | Ya |
| Akun tim | — | 2 | 10 |
| Unit bersama | — | — | Ya |
| Landing page dan domain sendiri | — | — | Ya |
| API dan webhook | — | — | Ya |
| Pengingat WhatsApp | Akan diputuskan | Akan diputuskan | Akan diputuskan |

Harga tidak berubah (Small Rp149.000, Medium Rp299.000, Pro Rp499.000 per bulan). Pembagian fitur per paket di atas adalah usulan yang perlu disetujui (keputusan 3 dan 4 di Bagian 10).

---

## 10. Keputusan yang dibutuhkan

Setiap keputusan berisi pertanyaan, usulan, dan akibatnya bila belum diputuskan. Keputusan yang sudah tercatat sebelumnya (D-001 sampai D-008) disebut kodenya.

### Harus diputuskan sebelum rilis

**1. Daftar fitur paket hanya berisi fitur nyata (D-008).**
- Usulan: ya, sesuai Bagian 9.
- Bila belum diputuskan: janji fitur yang tidak ada tetap dihapus sebelum rilis, supaya BookQu tidak menjual fitur yang tidak ada.

**2. Refund saat pembatalan (D-001).**
- Usulan: owner memilih saat membatalkan (refund penuh, tanpa refund, atau sesuai kebijakan otomatis).
- Bila belum diputuskan: berlaku cara cadangan yang sudah tercatat di D-001.

### Struktur dan isi

**3. Susunan 15 menu dan nama barunya (menggantikan usulan D-007).**
- Usulan: ya.
- Bila belum diputuskan: menu tetap seperti sekarang; hanya hal yang tidak berfungsi yang disembunyikan.

**4. Logo, sampul, dan warna halaman booking untuk semua paket.**
- Usulan: ya. Saat ini halaman booking tidak bisa diberi logo sama sekali.

**5. Materi promosi (QR, poster, teks bio, pesan WhatsApp).**
- Usulan: ya, untuk semua paket. Fitur kecil dengan dampak besar untuk owner baru.

**6. Domain sendiri (D-005).**
- Usulan: hanya untuk Pro, di Halaman Booking.

### Tim dan hak akses

**7. Staf sebagai akun tim dengan peran Pemilik, Manajer, Kasir.**
- Usulan: ya, dengan hak akses di Bagian 8.

**8. Jumlah akun tim per paket.**
- Usulan: Small 0, Medium 2, Pro 10 akun tambahan.

### Perubahan aturan booking (dikerjakan setelah rilis)

**9. Unit.** Lapangan, ruang, dan alat menjadi unit yang dipakai bersama beberapa layanan dan dicegah terpesan dua kali.
- Usulan: ya, untuk Pro. Ini perubahan besar pada aturan slot.

**10. Status "Tidak datang" (no-show).**
- Usulan: ya, sebagai hasil akhir booking selain "Selesai".

**11. Kapasitas lebih dari 1 orang per slot** (misalnya kelas yoga 10 orang).
- Usulan: ya. Ini mengubah aturan anti-bentrok.

**12. Pindah jadwal ke slot dengan harga berbeda (D-006).**
- Usulan: sesuai pilihan di D-006.

### Pembayaran

**13. Promo dan add-on dihitung ke total pembayaran.**
- Usulan: ya, untuk Medium ke atas.

**14. Gateway milik bisnis (BYOPG).**
- Usulan: tidak ditawarkan. Semua pembayaran lewat rekening BookQu, sesuai keputusan arsitektur.

**15. Rekening pencairan disimpan (D-004).**
- Usulan: sesuai D-004.

### Arah jangka panjang (tidak mendesak)

**16. Slot dibuka otomatis dari jam buka mingguan**, supaya owner tidak perlu membuat slot massal berulang.
**17. Tutup otomatis di hari libur nasional.**
**18. Saran otomatis di Laporan**, misalnya "Selasa pagi hanya terisi 12%, pertimbangkan harga khusus".
**19. Uang muka (DP).**
**20. Pengingat WhatsApp:** paket mana yang mendapatkannya.

---

## 11. Tahapan pengerjaan

| Tahap | Isi | Ukuran |
|---|---|---|
| 0. Sebelum rilis | Hapus janji fitur yang tidak ada, sembunyikan kontrol yang tidak berfungsi, selesaikan masalah rilis yang sudah tercatat | Ringan |
| 1. Fondasi | Komponen bersama (panel detail, header, tab, dialog, notifikasi kecil), bahasa Indonesia, navigasi | Sedang |
| 2. Alur inti | Layanan, Jam Buka & Harga, Kalender, Booking, Beranda; satu halaman per tahap | Sedang, sebagian berat |
| 3. Kepercayaan dan akuisisi | Pembayaran, Halaman Booking, Pelanggan, Ulasan, Pengaturan, Paket & Tagihan | Ringan sampai sedang |
| 4. Nilai paket Medium | Laporan, Promo dan Add-on di pembayaran, Tim dan hak akses | Sedang sampai berat |
| 5. Nilai paket Pro | Unit, Landing Page, API dan webhook, integrasi lain | Berat |

**Ringan** berarti memindah atau merapikan yang sudah ada. **Sedang** berarti menyusun ulang tampilan dengan data yang sudah tersedia. **Berat** berarti mengubah aturan inti booking atau pembayaran, sehingga perlu persetujuan Tech Lead, pengujian lengkap, dan dikerjakan hati-hati.

Yang termasuk berat: Unit, kapasitas per slot, status Tidak datang, Promo dan Add-on di pembayaran, nominal booking yang tetap walaupun harga berubah, dan Tim dengan hak akses.

---

## 12. Risiko dan hal yang belum diketahui

1. **Perubahan aturan inti.** Unit, kapasitas, dan promo di pembayaran menyentuh bagian yang mencegah double booking dan memastikan pembayaran benar. Kesalahan di sini berdampak langsung ke pelanggan, jadi dikerjakan setelah rilis dengan pengujian lengkap.
2. **Kebiasaan owner yang sudah ada.** Owner yang sudah memakai BookQu akan menemukan nama menu yang berbeda. Alamat halaman tetap sama, tetapi perlu pengumuman singkat saat perubahan dirilis.
3. **Data lama.** Data staf, voucher, dan add-on yang sempat dibuat owner di halaman tersembunyi perlu diperiksa sebelum fitur-fitur itu diaktifkan.
4. **Tampilan prototype belum final.** Prototype menunjukkan struktur dan alur, bukan desain akhir. Desain visual akan dirapikan terpisah tanpa mengubah struktur.
5. **Angka paket.** Jumlah akun tim dan pembagian fitur per paket adalah usulan; dampaknya ke penjualan belum diukur.

---

## 13. Yang tidak berubah

Supaya jelas bahwa ini penataan ulang, bukan membangun dari awal:

- Aturan booking yang mencegah double booking, batas waktu pembayaran, dan status pembayaran.
- Semua pembayaran lewat rekening BookQu (SingaPay), lalu menjadi saldo owner.
- Data setiap bisnis terpisah dan tidak bisa dilihat bisnis lain.
- Pelanggan tetap tidak perlu membuat akun; mereka mengelola booking lewat link.
- Alamat halaman owner tetap sama, sehingga link lama tidak rusak.
- Pengujian otomatis dan proses rilis yang sudah ada.

---

## Lampiran A. Cara mencoba prototype

Link: https://claude.ai/artifact/TPhchm7bsr2rBQKtpuFS3Q

Prototype hanya tampilan dengan data contoh. Tidak ada data asli yang tersimpan, dan semuanya kembali ke awal saat halaman dimuat ulang. "Hari ini" di prototype adalah Kamis, 5 November 2026 pukul 10.20. **Tampilan visualnya belum final**; yang dinilai adalah susunan menu, isi setiap halaman, dan alurnya.

### Panel Demo

Tombol **Demo** di pojok kiri bawah berisi:
- **Bisnis:** Arena Sportiva (lapangan olahraga), Lensa Rina Studio (fotografer), atau Owner baru.
- **Paket:** Trial, Small, Medium, Pro, Berakhir. Mengubah fitur yang terkunci.
- **Masuk sebagai:** Pemilik, Manajer, Kasir. Mengubah menu dan tombol yang tersedia.
- **Anotasi "Kenapa?":** menampilkan penjelasan setiap halaman dan label keputusan (K-1 dan seterusnya).
- **Tampilan:** terang atau gelap.

### Urutan yang disarankan untuk dicoba

1. Buka **Beranda**, lihat "Perlu tindakan", lalu klik **Tandai selesai** pada booking kemarin.
2. Buka **Kalender**, klik slot kosong, lalu **Catat walk-in**. Pilih add-on dan masukkan kode promo **WELCOME20**; perhatikan total berubah.
3. Buka **Booking**, buka satu booking, lalu coba **Pindah jadwal** dan **Batalkan**.
4. Buka **Unit** dan lihat contoh bentrok Paket Turnamen dengan Lapangan Futsal A.
5. Buka **Promo** dan buat kode promo baru.
6. Buka **Halaman Booking**: ganti warna, pilih logo dari galeri, lalu lihat pratinjau HP. Ubah paket ke Pro di panel Demo untuk melihat Landing Page.
7. Buka **Pembayaran** dan coba **Cairkan dana**.
8. Di panel Demo, ganti **Masuk sebagai: Kasir**. Perhatikan menu yang tersisa, tombol "Ajukan pembatalan", dan coba buka halaman lain.
9. Ganti kembali ke Pemilik, buka **Pengaturan → Tim**, dan lihat log aktivitas yang mencatat aksi kasir tadi.
10. Ubah paket ke **Small** dan buka **Laporan** untuk melihat tampilan fitur terkunci.
11. Ganti bisnis ke **Owner baru** untuk melihat panduan 4 langkah.

---

## Lampiran B. Sehari bersama owner dan kasir

**07.45 – Siska (Kasir) membuka BookQu di HP.** Ia hanya melihat Beranda, Kalender, Booking, dan Pelanggan. Di Beranda terlihat 17 booking hari ini, 2 menunggu pembayaran, dan satu booking kemarin yang belum ditandai selesai. Ia menekan **Tandai selesai**.

**09.00 – Rombongan datang tanpa booking.** Siska menekan **+ Booking**, memilih Lapangan Badminton 1 pukul 09.00, menambahkan sewa raket, memasukkan kode promo yang mereka sebutkan, lalu mencatat pembayaran tunai. Booking tercatat atas nama Siska.

**10.30 – Pelanggan menelepon minta batal karena hujan.** Siska tidak bisa membatalkan dan refund sendiri. Ia menekan **Ajukan pembatalan** dan menuliskan alasannya.

**11.00 – Rina (Pemilik) membuka Beranda dari rumah.** Di "Perlu tindakan" ada pengajuan pembatalan dari Siska. Rina membukanya, memilih refund penuh, dan pelanggan otomatis diberi tahu.

**13.00 – Rina mengecek Unit.** Ia melihat Paket Turnamen hari Sabtu memakai lapangan yang sama dengan sewa biasa. Sistem sudah mencegah bentrok baru, dan Rina menghubungi pelanggan yang terlanjur memesan.

**20.00 – Rina membuka Laporan.** Selasa pagi hanya terisi 12%. Ia membuka **Promo** dan membuat kode "SELASAPAGI" diskon 20% untuk jam 08.00–12.00.

**Akhir minggu – Rina membuka Pembayaran.** Saldo dari booking yang selesai sudah masuk. Ia menekan **Cairkan dana**, dan uang ditransfer ke rekeningnya setelah disetujui admin BookQu.

---

## Lampiran C. Glosarium

| Istilah | Arti |
|---|---|
| Owner | Pemilik bisnis yang memakai BookQu |
| Slot | Satu jam (atau satu sesi) yang bisa dipesan pelanggan |
| Walk-in | Pelanggan yang datang langsung tanpa booking online; dicatat oleh owner atau kasir |
| Unit | Lapangan, ruang, atau alat yang dipakai layanan; tidak bisa dipakai dua booking di jam yang sama |
| Add-on | Barang atau layanan tambahan yang dibeli bersama booking, misalnya sewa raket |
| Promo | Kode diskon yang dimasukkan pelanggan saat membayar |
| No-show / Tidak datang | Pelanggan sudah membayar tetapi tidak hadir |
| Kapasitas per slot | Jumlah orang yang bisa memesan slot yang sama, misalnya kelas |
| Saldo | Uang hasil booking yang siap dicairkan ke rekening owner |
| Pencairan | Pemindahan saldo ke rekening owner, ditinjau admin BookQu |
| Refund | Pengembalian uang ke pelanggan |
| Peran / hak akses | Batas menu dan aksi yang boleh dipakai anggota tim |
| Landing page | Halaman profil bisnis yang lebih lengkap, dengan template dan domain sendiri (Pro) |
| Trial | Masa coba 7 hari dengan akses setara Pro |

---

## Lampiran D. Untuk tim teknis

Bagian ini tidak perlu dibaca Product Lead.

- Draft dokumen authority: `docs/owner-portal.md` (perlu diperbarui mengikuti spesifikasi baru bila disetujui).
- Keputusan yang sudah tercatat: `docs/product-decisions.md`, D-001 sampai D-008. Usulan D-007 akan diganti isinya oleh dokumen ini.
- Item kerja terkait kondisi saat ini: BQ-086 sampai BQ-121, terutama BQ-087, BQ-089, BQ-090 sampai BQ-112 (audit owner UI), BQ-113 sampai BQ-121 (audit booking dan halaman publik).
- Mekanisme penyembunyian modul: `config/modules.php` dengan middleware `module:`; menu dan isi beberapa halaman disembunyikan di commit `15f5be6`.
- Keputusan arsitektur yang membatasi: pembayaran hanya lewat platform (ADR-0001), tahapan rilis modul (ADR-0002, ADR-0011).
- Perubahan yang termasuk heavy lane (butuh work item dan persetujuan Tech Lead sebelum coding): Unit, kapasitas per slot, status no-show, promo dan add-on di checkout, nominal historis booking, Tim dan hak akses.
