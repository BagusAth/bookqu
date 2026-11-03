# Keputusan Produk BookQu

Dokumen ini berisi keputusan yang perlu divalidasi Product Lead, dan catatan keputusan produk yang sudah diambil. Setiap entri berisi masalah singkat dan usulan keputusan dari Tech Lead.

**Cara menjawab:** balas dengan nomor entri dan poinnya, misalnya "D-001 setuju semua" atau "D-001 setuju kecuali poin 3: …". Setelah disetujui, Tech Lead membuat pekerjaannya. Keputusan yang sudah diambil bisa dibuka lagi kapan saja dengan menyebut nomornya.

Terakhir diperbarui: 4 November 2026

---

## Menunggu keputusan

### D-003 · Apa saja yang bisa dilakukan admin platform di v1

*Status:* Menunggu · *Diajukan:* 30 Okt 2026 · *Menahan rilis:* tidak

*Masalah*
Halaman admin saat ini hanya menampilkan ringkasan dan memproses permintaan pencairan saldo owner. Belum ditentukan apa lagi yang perlu dilakukan admin, misalnya mengurus refund atau membantu bisnis yang bermasalah.

*Usulan keputusan*
1. Admin v1 bisa: memproses pencairan saldo (sudah ada), memproses refund (kalau D-001 poin 7 disetujui), dan melihat daftar bisnis beserta status langganannya.
2. Menonaktifkan bisnis, masuk sebagai owner, dan mengubah langganan secara manual ditunda setelah rilis.

*Catatan 4 Nov 2026:* D-001 sudah disetujui, jadi pemrosesan refund oleh admin ikut dikerjakan sebagai bagian dari D-001. Yang masih menunggu di sini hanya fitur admin di luar pencairan saldo dan refund. Product Lead sengaja menunda entri ini.

*Jawaban Product Lead:* —

---

### D-007 · Peta halaman owner: halaman apa saja dan isinya apa

*Status:* Menunggu (Revisi 1) · *Diajukan:* 2 Nov 2026 · *Revisi 1:* 4 Nov 2026 · *Menahan rilis:* ya

*Revisi 1 (4 Nov 2026):* menggantikan usulan 2 Nov 2026. Disusun dari rancangan ulang portal owner yang sudah disetujui, ditambah pemeriksaan semua halaman dan fitur yang ada sekarang, supaya tidak ada fitur yang hilang. Rincian isi setiap halaman sudah ditulis untuk tim teknis. Entri ini ringkasannya.

*Masalah*
Halaman owner dibangun dari daftar nama halaman tanpa penjelasan isinya. Akibatnya isi halaman tumpang tindih, ada kontrol yang tidak berfungsi, dan beberapa pengaturan penting tidak punya tempat. Contohnya, batas jam pembatalan sudah dipakai sistem, tetapi owner tidak bisa mengubahnya.

*Usulan keputusan*
1. Menu owner menjadi 15 menu dalam lima kelompok:
   - *tanpa kelompok:* Beranda, Kalender, Booking, Pelanggan;
   - *Bisnis saya:* Layanan, Unit, Jam Buka & Harga;
   - *Pemasaran:* Halaman Booking, Promo, Ulasan;
   - *Keuangan:* Pembayaran, Laporan;
   - *Lainnya:* Pengaturan, Paket & Tagihan, Bantuan.

   Notifikasi dibuka dari lonceng di atas.
2. Di bagian atas setiap halaman ada lonceng, tombol "+ Booking", dan pencarian (kode booking, nama, atau nomor HP). Di HP, menu diganti navigasi bawah: Beranda, Kalender, + Booking, Booking, Lainnya.
3. Setiap halaman punya satu tugas:
   - *Beranda:* hari ini dan yang perlu ditindaklanjuti, tanpa grafik. Ada panduan 4 langkah untuk owner baru.
   - *Kalender:* siapa datang kapan, mencatat walk-in, menutup atau membuka slot.
   - *Booking:* semua booking, dikelompokkan menurut tugas (perlu tindakan, mendatang, menunggu bayar, selesai, dibatalkan).
   - *Pelanggan:* data, catatan, dan riwayat pelanggan.
   - *Layanan:* layanan, kategori, dan add-on.
   - *Unit:* lapangan, ruang, atau alat yang dipakai bersama (Pro).
   - *Jam Buka & Harga:* jam buka, aturan harga, hari libur, dan slot.
   - *Halaman Booking:* link, QR, materi promosi, tampilan, galeri gambar, landing page dan domain sendiri (Pro).
   - *Promo:* kode promo dan hasilnya.
   - *Ulasan:* membalas dan menyembunyikan ulasan.
   - *Pembayaran:* transaksi, saldo, pencairan, dan rekening (hanya Pemilik).
   - *Laporan:* grafik dan tren, ekspor.
   - *Pengaturan:* profil bisnis, kebijakan booking, notifikasi, integrasi, akun, tim.
   - *Paket & Tagihan:* paket, pemakaian, dan tagihan (hanya Pemilik).
   - *Bantuan:* pertanyaan umum dan kontak BookQu.
4. Detail booking selalu tampil di satu panel yang sama dari halaman mana pun. Tombolnya hanya yang sah untuk status booking itu: tandai selesai, tandai tidak datang, pindah jadwal (dengan selisih harga), batalkan (refund penuh untuk booking berbayar), dan putuskan pengajuan refund (100%, 50%, atau tolak).
5. Batas jam pembatalan, batas jam pindah jadwal, dan pengaturan refund ada di Pengaturan → Kebijakan Booking.
6. Semua fitur yang ada sekarang tetap ada, hanya pindah tempat. Contohnya: slot massal dengan jam dan interval, harga akhir pekan, gaya tampilan halaman booking, dan data pembayar saat membeli paket.

   Yang dihapus hanya:
   - pengaturan gateway pembayaran milik bisnis, termasuk kredensial yang tersimpan (D-016);
   - staf sebagai penyedia layanan, karena digantikan akun tim (D-011);
   - panel harga massal yang tidak berfungsi;
   - formulir penarikan saldo lama;
   - alamat halaman ganda.
7. Alamat halaman lama tetap bisa dibuka dan otomatis diarahkan ke halaman barunya.
8. Saat paket berakhir, owner tetap bisa mengurus booking dan saldo yang ada, tetapi tidak bisa membuat yang baru (D-002).
9. Mulai sekarang, halaman atau fitur owner baru harus ditulis dulu isinya dan disetujui, baru dibangun.

*Kalau belum diputuskan:* halaman tetap seperti sekarang, dan pembangunan portal baru belum dimulai.

*Jawaban Product Lead:* —

---

### D-008 · Daftar fitur paket hanya berisi fitur yang benar-benar ada

*Status:* Menunggu (ditunda) · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* tidak

*Catatan 4 Nov 2026:* Ditunda. Produksi baru dirilis setelah semua fitur rancangan ulang selesai (D-018), jadi tidak akan ada masa ketika paket dijual dengan fitur yang belum ada. Pembagian fitur per paket diajukan di D-017; kalau D-017 disetujui, entri ini ditutup.

*Masalah*
Daftar fitur paket di halaman harga dan halaman langganan menjanjikan banyak hal yang belum ada, tersembunyi, atau memang di luar lingkup BookQu. Pelanggan yang membayar Medium atau Pro hampir tidak mendapat apa pun selain batas booking yang lebih besar.

*Usulan keputusan (versi 2 Nov 2026)*
1. Daftar fitur hanya memuat fitur yang sudah dirilis untuk paket itu. Fitur yang sedang dikerjakan boleh tampil dengan label "Segera hadir", paling banyak dua per paket.
2. Fitur yang tidak ada dihapus dari daftar: staf, role management, pengingat WhatsApp, promo & voucher, gateway milik bisnis, multi-lokasi, white-label, automation workflow.
3. Harga tidak berubah.

*Jawaban Product Lead:* —

---

### D-009 · Logo, sampul, dan warna halaman booking untuk semua paket

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Pengaturan tampilan halaman booking saat ini disembunyikan, jadi halaman booking bisnis tidak bisa diberi logo sama sekali. Tampilan yang mencerminkan bisnis adalah kebutuhan dasar semua owner, bukan fitur tambahan.

*Usulan keputusan*
1. Semua paket bisa mengatur logo, gambar sampul, warna utama, dan deskripsi halaman booking.
2. Pengaturannya ada di menu Halaman Booking, dengan pratinjau tampilan di HP.
3. Semua gambar diambil dari satu galeri media milik bisnis, yang juga dipakai untuk foto layanan dan unit.
4. Landing page lengkap (template dan bagian halaman) tetap khusus paket Pro.

*Kalau belum diputuskan:* halaman booking tetap memakai tampilan standar BookQu tanpa logo.

*Jawaban Product Lead:* —

---

### D-010 · Link, QR, dan materi promosi untuk semua paket

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Owner baru kesulitan mendapat pelanggan pertama karena tidak ada cara mudah untuk membagikan halaman booking. Saat ini owner hanya bisa menyalin alamat halaman.

*Usulan keputusan*
1. Di menu Halaman Booking, owner bisa menyalin link, membukanya, membagikannya langsung ke WhatsApp, dan mengunduh QR.
2. Tersedia poster siap cetak untuk meja kasir, berisi QR dan nama bisnis.
3. Tersedia teks siap pakai untuk bio Instagram dan pesan WhatsApp.
4. Berlaku untuk semua paket.

*Kalau belum diputuskan:* owner tetap hanya bisa menyalin link.

*Jawaban Product Lead:* —

---

### D-011 · Staf adalah akun tim dengan peran Pemilik, Manajer, dan Kasir

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Spesifikasi lama menyebut "staf" tanpa menjelaskan artinya, dan modul staf saat ini disembunyikan. Pemilik bisnis perlu mengajak kasir atau manajer masuk ke dashboard tanpa memberi semua akses, termasuk akses ke saldo.

*Usulan keputusan*
1. Staf adalah anggota tim yang login ke dashboard bisnis, bukan penyedia layanan yang dipilih pelanggan saat booking.
2. Ada tiga peran:
   - *Pemilik:* semua akses.
   - *Manajer:* operasional, layanan, jadwal, pemasaran, laporan, dan pengaturan; tanpa pembayaran dan saldo, paket dan tagihan, pengelolaan tim, dan hapus akun.
   - *Kasir:* beranda, kalender, booking, dan pelanggan; mencatat walk-in, menandai selesai, dan memindah jadwal; tanpa angka pendapatan.
3. Kasir tidak bisa membatalkan booking. Kasir hanya mengajukan pembatalan, lalu Pemilik atau Manajer yang memutuskan. Pembatalan booking berbayar oleh bisnis tetap selalu refund penuh (D-001).
4. Menu yang tidak diizinkan disembunyikan, dan sistem tetap menolak aksesnya walaupun alamat halaman diketik langsung.
5. Pemilik mengundang anggota lewat email dan bisa mencabut aksesnya kapan saja.
6. Setiap aksi dicatat dengan nama pelakunya, misalnya "Walk-in dicatat oleh Siska (Kasir)". Pemilik bisa melihat catatan aktivitas tim.
7. Jumlah akun tim per paket mengikuti D-017.

*Kalau belum diputuskan:* modul staf tetap disembunyikan dan hanya pemilik yang bisa login.

*Jawaban Product Lead:* —

---

### D-012 · Unit: lapangan, ruang, atau alat yang dipakai beberapa layanan

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Beberapa layanan bisa memakai tempat yang sama. Misalnya, paket turnamen futsal dan sewa lapangan futsal A memakai lapangan yang sama. Saat ini keduanya bisa terpesan di jam yang sama, karena sistem hanya mencegah bentrok di dalam satu layanan.

*Usulan keputusan*
1. Owner bisa mencatat unit (lapangan, ruang, atau alat) dan menentukan layanan mana yang memakainya.
2. Sistem menolak dua booking yang memakai unit yang sama di jam yang sama, walaupun layanannya berbeda.
3. Halaman Unit menunjukkan unit yang terpakai hari ini, dan bentrok yang sudah terlanjur ada di data supaya owner bisa membereskannya.
4. Fitur ini untuk paket Pro.

*Kalau belum diputuskan:* bentrok antarlayanan tetap mungkin terjadi, dan owner harus mengaturnya sendiri.

*Jawaban Product Lead:* —

---

### D-013 · Status booking "Tidak datang"

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Saat ini booking yang sudah dibayar hanya bisa berakhir "Selesai" atau "Dibatalkan". Kalau pelanggan tidak datang, owner tidak punya cara yang jujur untuk mencatatnya, sehingga angka kehadiran di laporan tidak akurat.

*Usulan keputusan*
1. Ada status akhir baru "Tidak datang", selain "Selesai".
2. Pemilik, Manajer, atau Kasir bisa menandai "Tidak datang" setelah jadwal booking lewat.
3. Uang tidak dikembalikan otomatis. Booking yang tidak datang tetap dihitung sebagai pendapatan.
4. Laporan menampilkan tingkat tidak datang.

*Kalau belum diputuskan:* owner mencatat pelanggan yang tidak datang sebagai "Selesai".

*Jawaban Product Lead:* —

---

### D-014 · Lebih dari satu peserta per slot

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Saat ini setiap slot hanya bisa dipesan satu kali. Bisnis seperti kelas yoga atau workshop menerima banyak peserta di jam yang sama, sehingga owner harus membuat banyak layanan atau slot tiruan.

*Usulan keputusan*
1. Setiap layanan punya kapasitas per slot, awalnya 1.
2. Slot tetap bisa dipesan sampai kapasitasnya penuh. Setelah itu slot tampil penuh.
3. Satu booking boleh untuk beberapa peserta selama sisa kapasitas cukup, dan harga dihitung per peserta.
4. Berlaku untuk semua paket.

*Alternatif poin 3:* satu booking selalu untuk satu peserta. Lebih sederhana, tetapi pelanggan yang datang berdua harus memesan dua kali.

*Kalau belum diputuskan:* setiap slot tetap hanya untuk satu booking.

*Jawaban Product Lead:* —

---

### D-015 · Promo dan add-on masuk ke total pembayaran

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Menu voucher dan item tambahan sudah ada, tetapi disembunyikan karena keduanya tidak pernah masuk ke total yang dibayar pelanggan. Promo yang tidak bisa dipakai dan add-on yang tidak dibayar membingungkan owner maupun pelanggan.

*Usulan keputusan*
1. Saat booking, pelanggan bisa memilih add-on dan memasukkan kode promo. Total yang dibayar sudah termasuk keduanya.
2. Promo berisi: diskon persen atau potongan rupiah, minimal transaksi, maksimal diskon, kuota, periode, dan layanan yang berlaku.
3. Add-on berisi: harga, stok, dan layanan terkait. Stok hanya berkurang untuk booking yang dibayar.
4. Nominal yang dibayar dicatat tetap di booking. Perubahan harga, promo, atau add-on sesudahnya tidak mengubah booking yang sudah ada.
5. Refund dihitung dari nominal yang benar-benar dibayar.
6. Fitur ini untuk paket Medium ke atas.

*Kalau belum diputuskan:* promo dan add-on tetap disembunyikan.

*Jawaban Product Lead:* —

---

### D-016 · Gateway pembayaran milik bisnis tidak ditawarkan

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Pengaturan untuk memakai akun payment gateway milik bisnis sendiri masih ada di aplikasi dan tercantum di daftar fitur paket. Ini bertentangan dengan keputusan bahwa semua pembayaran lewat rekening BookQu, lalu menjadi saldo owner.

*Usulan keputusan*
1. Pilihan gateway milik bisnis dihapus dari aplikasi dan dari daftar fitur paket.
2. Semua pembayaran pelanggan tetap lewat BookQu, lalu masuk ke saldo owner tanpa potongan.
3. Halaman Pembayaran menjelaskan metode pembayaran yang tersedia, biaya platform, batas waktu bayar, dan aturan refund.

*Kalau belum diputuskan:* pengaturan itu tetap tersembunyi dan tidak berfungsi.

*Jawaban Product Lead:* —

---

### D-017 · Pembagian fitur per paket

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Daftar fitur paket saat ini menjanjikan fitur yang tidak ada, dan pembeda antarpaket hampir hanya batas booking. Setelah rancangan ulang, setiap paket perlu pembagian fitur yang jelas, termasuk jumlah akun tim.

*Usulan keputusan*
1. *Semua paket:* halaman booking, kalender dan slot, booking dan walk-in, pelanggan, ulasan, pembayaran, saldo dan pencairan, notifikasi email, invoice, logo dan tampilan, serta link, QR, dan materi promosi.
2. *Small:* maksimal 5 layanan dan 300 booking per bulan. Hanya pemilik yang login.
3. *Medium:* layanan tanpa batas, 500 booking per bulan, Laporan dan ekspor, promo dan add-on, serta 2 akun tim tambahan. Paket Small melihat Laporan sebagai pratinjau buram dengan ajakan naik paket.
4. *Pro:* semua fitur Medium, booking tanpa batas, 10 akun tim tambahan, Unit, landing page dan domain sendiri, serta API dan webhook.
5. Harga tidak berubah: Small Rp149.000, Medium Rp299.000, Pro Rp499.000 per bulan.
6. Daftar fitur yang tampil ke calon pelanggan hanya berisi fitur di atas. Keputusan ini menggantikan D-008.

*Kalau belum diputuskan:* daftar fitur paket tidak diubah, dan rilis produksi menunggu keputusan ini.

*Jawaban Product Lead:* —

---

### D-018 · Produksi dirilis setelah semua fitur rancangan ulang selesai

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Rencana sebelumnya adalah merilis dulu, lalu memperbaiki portal owner sedikit demi sedikit. Dengan rancangan ulang yang sudah disetujui, merilis versi lama berarti owner harus belajar portal dua kali, dan daftar paket menjanjikan fitur yang belum ada.

*Usulan keputusan*
1. Produksi baru dirilis setelah semua fitur dalam cakupan selesai dan teruji.
2. Cakupannya: portal owner baru, D-001 sampai D-006, dan D-009 sampai D-020 yang disetujui. Yang di luar cakupan dicatat di D-021.
3. Sampai saat itu, produksi tetap menjalankan versi yang sekarang.
4. Perbaikan keamanan yang mendesak tetap boleh dirilis lebih dulu.

*Kalau belum diputuskan:* berlaku seperti usulan ini, sesuai arahan 4 Nov 2026.

*Jawaban Product Lead:* —

---

### D-019 · Integrasi yang bisa dihubungkan owner

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Halaman integrasi saat ini hanya berisi kartu "segera hadir" dan status sistem BookQu, tanpa koneksi yang bisa dipakai. Owner butuh menghubungkan alat yang mereka pakai sendiri.

*Usulan keputusan*
1. Di Pengaturan → Integrasi, owner bisa menghubungkan Google Calendar (booking masuk ke kalender owner), serta Google Analytics dan Meta Pixel untuk halaman booking. Untuk semua paket.
2. Setiap integrasi punya tombol hubungkan, tes, dan putuskan.
3. API dan webhook, untuk sistem milik bisnis, hanya untuk paket Pro.
4. Pembayaran dan email ditandai "dikelola BookQu", dan tidak bisa diubah owner.
5. WhatsApp Business belum termasuk. Integrasi ini diputuskan bersama pengingat WhatsApp (D-021).

*Alternatif poin 5:* WhatsApp Business ikut dihubungkan sekarang, sesuai rancangan ulang. Pilihan ini membutuhkan akun WhatsApp Business API dan biaya pesan yang belum dihitung.

*Kalau belum diputuskan:* halaman integrasi disembunyikan.

*Jawaban Product Lead:* —

---

### D-020 · Aturan pemesanan tambahan di Kebijakan Booking

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Owner belum bisa mengatur seberapa cepat dan seberapa jauh pelanggan boleh memesan, atau memberi jeda antar booking. Rancangan ulang juga menyebut "konfirmasi otomatis", yang berarti ada booking yang harus disetujui owner lebih dulu.

*Usulan keputusan*
1. Owner bisa mengatur: paling lambat berapa jam sebelum sesi pelanggan boleh memesan, paling jauh berapa hari ke depan, dan jeda antar booking.
2. Booking langsung berlaku setelah dibayar. Tidak ada persetujuan manual dari owner.
3. Pelanggan melihat ringkasan aturan ini sebelum membayar.

*Alternatif poin 2:* owner bisa memilih menyetujui setiap booking secara manual. Ini menambah status booking baru "menunggu konfirmasi", membuat pelanggan menunggu setelah membayar, dan memerlukan aturan refund bila owner menolak.

*Kalau belum diputuskan:* aturan tetap seperti sekarang. Tidak ada batas tambahan, dan booking langsung berlaku.

*Jawaban Product Lead:* —

---

### D-021 · Yang tidak termasuk rilis ini

*Status:* Menunggu · *Diajukan:* 4 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Rancangan ulang juga mencatat beberapa ide jangka panjang. Supaya jelas kapan rilis dianggap selesai, perlu ditetapkan mana yang tidak termasuk.

*Usulan keputusan*
Hal berikut tidak termasuk rilis ini, dan diajukan lagi setelah rilis:
1. Slot dibuka otomatis dari jam buka mingguan.
2. Tutup otomatis di hari libur nasional.
3. Saran otomatis di Laporan.
4. Uang muka (DP).
5. Pengingat dan notifikasi lewat WhatsApp, termasuk integrasi WhatsApp Business dan paket mana yang mendapatkannya.
6. Peran buatan sendiri, ketika owner memilih hak akses satu per satu.

*Kalau belum diputuskan:* semuanya dianggap di luar rilis ini.

*Jawaban Product Lead:* —

---

## Untuk diketahui (tidak perlu keputusan)

Perubahan teknis yang terlihat oleh owner atau pelanggan, diputuskan Tech Lead:

- *1 Nov 2026:* selama pelanggan masih dalam waktu pembayaran online (15 menit), owner tidak bisa membatalkan atau menandai lunas booking itu. Ini mencegah pelanggan membayar dua kali.
- *1 Nov 2026:* tombol "Tandai Lunas" untuk booking yang dibayar online dihapus. Kalau pelanggan membayar tunai di tempat, owner mencatatnya sebagai booking walk-in.
- *1 Nov 2026:* kalau pelanggan sampai membayar dua kali, sistem mencatat refund dan memberi tahu owner, tidak lagi diam saja.
- *1 Nov 2026:* trial atau langganan yang sudah habis tidak lagi mendapat manfaat Pro. Perilaku setelah habis mengikuti D-002.

---

## Sudah diputuskan

### Keputusan sebelum dokumen ini dibuat

Diambil Tech Lead sebelum peran Product Lead ditetapkan. Product Lead bisa membuka lagi keputusan mana pun.

- *18 Okt 2026 · Rilis fitur bertahap:* analitik, laporan jadwal, dan integrasi tampil tetapi terkunci. Voucher, item tambahan, staf dan sumber daya, tampilan toko, pembayaran langsung, aset, dan landing page disembunyikan sampai siap. (ADR-0002, ADR-0011)
- *18 Okt 2026 · Pembayaran:* SingaPay menjadi gateway utama dan Midtrans sebagai alternatif. (ADR-0001)
- *30 Okt 2026 · Link kelola booking pelanggan:* pelanggan mengelola booking lewat link aman yang dikirim ke email dan punya masa berlaku. Link lama yang memakai token di alamatnya tidak berlaku lagi. (ADR-0006)
- *30 Okt 2026 · Batas waktu pembayaran:* pelanggan punya 15 menit untuk membayar booking; owner punya 60 menit untuk membayar langganan. Slot dipegang selama waktu itu. (ADR-0009)
- *1 Nov 2026 · Semua pembayaran lewat rekening platform:* mode owner memakai akun Midtrans sendiri dimatikan untuk v1. (BQ-079)
- *1 Nov 2026 · Saldo dan pencairan:* uang booking masuk sebagai saldo owner tanpa potongan (biaya 0% di v1). Owner meminta pencairan, admin memeriksa dan mentransfer manual. (BQ-015)
- *1 Nov 2026 · Saldo awal:* saldo owner mulai dari 0 saat rilis; pembayaran sebelum rilis tidak dihitung. (BQ-015)
- *1 Nov 2026 · Cek status ke SingaPay:* selain menunggu notifikasi, sistem bisa menanyakan status pembayaran langsung ke SingaPay. (BQ-074)
- *1 Nov 2026 · Booking 3 langkah:* alur booking pelanggan menjadi Layanan → Jadwal → Data & Pembayaran, dari desain baru. (BQ-084)

### Keputusan lewat dokumen ini

- D-001 · Refund booking opsional dan diputuskan owner · Disetujui 4 Nov 2026: owner menentukan apakah pelanggan boleh mengajukan refund; pengajuan diputuskan 100%, 50%, atau tolak; uang dikembalikan admin; pembatalan booking berbayar oleh bisnis selalu refund penuh; aturannya diatur di Kebijakan Booking → item menyusul (BQ-126)
- D-002 · Saat trial atau langganan berakhir · Disetujui 4 Nov 2026: owner tetap bisa masuk dan mengurus booking yang ada tetapi tidak bisa membuat yang baru; halaman booking tidak menerima booking baru; tanpa masa tenggang; perpanjangan menyambung tanggal berakhir; pengingat 3 hari, 1 hari, dan saat habis; satu kali trial per akun → item menyusul (BQ-126)
- D-004 · Rekening penarikan saldo disimpan · Disetujui 4 Nov 2026: sampai 3 rekening dengan 1 rekening utama, konfirmasi password saat menyimpan, mengubah, atau menghapus, dan email setiap ada perubahan → BQ-123, BQ-124, BQ-125
- D-005 · Domain sendiri untuk halaman booking · Disetujui 4 Nov 2026: fitur Pro, diatur di menu Halaman Booking dengan panduan DNS dan pengecekan otomatis → item menyusul (BQ-126)
- D-006 · Pindah jadwal ke slot dengan harga berbeda · Disetujui 4 Nov 2026: pelanggan hanya bisa pindah ke slot yang sama atau lebih murah, tanpa pengembalian selisih; owner boleh ke slot mana pun, dan selisihnya ditampilkan serta dicatat → item menyusul (BQ-126)
