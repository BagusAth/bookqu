# Keputusan Produk BookQu

Dokumen ini berisi keputusan yang perlu divalidasi Product Lead, dan catatan keputusan produk yang sudah diambil. Setiap entri berisi masalah singkat dan usulan keputusan dari Tech Lead.

**Cara menjawab:** balas dengan nomor entri dan poinnya, misalnya "D-001 setuju semua" atau "D-001 setuju kecuali poin 3: …". Setelah disetujui, Tech Lead membuat pekerjaannya. Keputusan yang sudah diambil bisa dibuka lagi kapan saja dengan menyebut nomornya.

Terakhir diperbarui: 22 November 2026

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

### D-028 · Template tampilan siap pakai di menu Tampilan

*Status:* Disetujui · *Diajukan:* 21 Nov 2026 · *Diputuskan:* 21 Nov 2026 · *Menahan rilis:* tidak (hanya sesi 7 BQ-165)

*Masalah*
Owner sudah bisa memilih bentuk tombol (pil, membulat, kotak), gaya kartu (terangkat, garis tepi, lembut), dan huruf di Halaman Booking → Tampilan. Ada 9 kombinasi bentuk dan kartu, ditambah 6 huruf. Owner baru sering tidak tahu kombinasi mana yang cocok, padahal halaman pelanggan yang baru (BQ-165) berubah total mengikuti pilihan itu.

*Usulan keputusan*
1. Menu Tampilan menyediakan 3 template siap pakai: **Modern** (pil + terangkat + Plus Jakarta Sans), **Rapi** (membulat + garis tepi + Inter), dan **Tegas** (kotak + lembut + Outfit).
2. Memilih template hanya mengisi ketiga pengaturan itu di formulir. Warna merek, logo, banner, dan deskripsi tidak berubah. Owner tetap menekan Simpan.
3. Setelah memilih template, owner tetap bebas mengubah tiap pengaturan. Bila kombinasinya tidak sama dengan template mana pun, label menjadi "Kustom".
4. Berlaku untuk semua paket, sama seperti pengaturan Tampilan saat ini (D-009). Tidak ada data baru yang disimpan.

*Jawaban Product Lead:* Disetujui sesuai usulan (dikonfirmasi lewat Tech Lead, 21 Nov 2026).

---

## Untuk diketahui (tidak perlu keputusan)

Perubahan teknis yang terlihat oleh owner atau pelanggan, diputuskan Tech Lead:

- *1 Nov 2026:* selama pelanggan masih dalam waktu pembayaran online (15 menit), owner tidak bisa membatalkan atau menandai lunas booking itu. Ini mencegah pelanggan membayar dua kali.
- *1 Nov 2026:* tombol "Tandai Lunas" untuk booking yang dibayar online dihapus. Kalau pelanggan membayar tunai di tempat, owner mencatatnya sebagai booking walk-in.
- *1 Nov 2026:* kalau pelanggan sampai membayar dua kali, sistem mencatat refund dan memberi tahu owner, tidak lagi diam saja.
- *1 Nov 2026:* trial atau langganan yang sudah habis tidak lagi mendapat manfaat Pro. Perilaku setelah habis mengikuti D-002.
- *4 Nov 2026:* satu akun hanya untuk satu bisnis. Owner yang ingin membuka bisnis lain mendaftar dengan akun terpisah.
- *16 Nov 2026:* di Beranda, "Tandai selesai" muncul begitu sesi dimulai, sama seperti di panel booking. "Tidak datang" bisa ditandai langsung dari Beranda setelah sesi berakhir. (BQ-160)
- *16 Nov 2026:* tampilan portal owner memakai warna BookQu (ungu dan oranye) dan huruf Plus Jakarta Sans. Jadwal hari ini di Beranda tampil sebagai garis waktu dengan penanda jam sekarang. Tombol tengah di HP bernama "Walk-in". Fitur dan alurnya tidak berubah. (BQ-161)
- *21 Nov 2026:* semua halaman pelanggan (toko, jadwal, pembayaran, kelola reservasi, ubah jadwal, invoice, dan halaman error) mendapat tampilan baru yang sepenuhnya mengikuti warna merek, huruf, bentuk tombol, dan gaya kartu pilihan owner. Banner bisnis menjadi latar sampul toko. Judul toko menjadi "Reservasi di {nama bisnis}". Aturan harga, jadwal, pembayaran, dan refund tidak berubah. (BQ-165)

---

## Sudah diputuskan

- D-027 · Sesi lama saat fitur selesai otomatis pertama kali berjalan · Disetujui Product Lead dan Tech Lead pada 19 Nov 2026, dikonfirmasi melalui developer. Usulan utama diterapkan, mulai dari sesi yang berakhir sejak fitur diaktifkan, termasuk saat diaktifkan kembali. Sesi lama bisa ditutup manual → BQ-163
- D-029 · Masuk dan daftar dengan akun Google · Disetujui Product Lead dan Tech Lead pada 22 Nov 2026, dikonfirmasi melalui developer: tombol "Masuk dengan Google" di halaman masuk dan daftar; bisa dipakai pemilik dan anggota tim (Manajer, Kasir), admin platform tetap memakai email dan password; daftar baru lewat Google mengambil nama dan email dari Google, lalu pemilik tetap mengisi nomor HP, nama, jenis, dan alamat bisnis serta menyetujui ketentuan, mendapat trial 7 hari seperti biasa, dan emailnya langsung terverifikasi; akun lama dengan email yang sama otomatis terhubung saat pertama kali masuk dengan Google, hanya bila Google menyatakan email itu terverifikasi; akun yang dibuat lewat Google belum punya password, jadi untuk mengubah email, menghapus akun, dan mengubah rekening penarikan (D-004) pemilik membuat password dulu lewat link email; pelanggan tetap tidak perlu login → BQ-172

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

- D-001 · Refund booking opsional dan diputuskan owner · Disetujui 4 Nov 2026: owner menentukan apakah pelanggan boleh mengajukan refund; pengajuan diputuskan 100%, 50%, atau tolak; uang dikembalikan admin; pembatalan booking berbayar oleh bisnis selalu refund penuh; aturannya diatur di Kebijakan Booking → BQ-138
- D-002 · Saat trial atau langganan berakhir · Disetujui 4 Nov 2026: owner tetap bisa masuk dan mengurus booking yang ada tetapi tidak bisa membuat yang baru; halaman booking tidak menerima booking baru; tanpa masa tenggang; perpanjangan menyambung tanggal berakhir; pengingat 3 hari, 1 hari, dan saat habis; satu kali trial per akun → BQ-139
- D-004 · Rekening penarikan saldo disimpan · Disetujui 4 Nov 2026: sampai 3 rekening dengan 1 rekening utama, konfirmasi password saat menyimpan, mengubah, atau menghapus, dan email setiap ada perubahan → BQ-123, BQ-124, BQ-125
- D-005 · Domain sendiri untuk halaman booking · Disetujui 4 Nov 2026: fitur Pro, diatur di menu Halaman Booking dengan panduan DNS dan pengecekan otomatis → BQ-154
- D-006 · Pindah jadwal ke slot dengan harga berbeda · Disetujui 4 Nov 2026: pelanggan hanya bisa pindah ke slot yang sama atau lebih murah, tanpa pengembalian selisih; owner boleh ke slot mana pun, dan selisihnya ditampilkan serta dicatat → BQ-140
- D-007 · Peta halaman owner · Disetujui 4 Nov 2026 (Revisi 1): 15 menu dalam lima kelompok dengan satu tugas per halaman, satu panel booking yang sama di semua halaman, Kebijakan Booking di Pengaturan, semua fitur lama tetap ada kecuali yang dihapus oleh D-011 dan D-016, alamat lama dialihkan, halaman baru ditulis dulu sebelum dibangun → BQ-128 sampai BQ-145
- D-008 · Daftar fitur paket hanya berisi fitur yang benar-benar ada · Ditutup 4 Nov 2026: digantikan D-017
- D-009 · Logo, sampul, dan warna halaman booking untuk semua paket · Disetujui 4 Nov 2026: semua paket mengatur logo, sampul, warna, dan deskripsi di menu Halaman Booking dengan pratinjau HP; semua gambar dari satu galeri media; landing page lengkap tetap khusus Pro → BQ-142
- D-010 · Link, QR, dan materi promosi untuk semua paket · Disetujui 4 Nov 2026: salin, buka, dan bagikan link ke WhatsApp, QR, poster siap cetak, teks bio Instagram dan pesan WhatsApp siap pakai → BQ-142
- D-011 · Staf adalah akun tim dengan peran Pemilik, Manajer, dan Kasir · Disetujui 4 Nov 2026: staf adalah anggota tim yang login, bukan penyedia layanan; Kasir hanya mengajukan pembatalan; undangan lewat email dan akses bisa dicabut; setiap aksi dicatat dengan nama pelakunya → BQ-131, BQ-146
- D-012 · Unit yang dipakai beberapa layanan · Disetujui 4 Nov 2026: unit tidak bisa terpesan dua kali di jam yang sama walaupun layanannya berbeda; bentrok yang sudah ada ditampilkan; paket Pro → BQ-152
- D-013 · Status booking "Tidak datang" · Disetujui 4 Nov 2026: status akhir baru setelah jadwal lewat; uang tidak dikembalikan otomatis; tampil di Laporan → BQ-153
- D-014 · Lebih dari satu peserta per slot · Disetujui 4 Nov 2026: kapasitas per layanan, awalnya 1; satu booking boleh untuk beberapa peserta selama sisa kapasitas cukup, harga per peserta; semua paket → BQ-151
- D-015 · Promo dan add-on masuk ke total pembayaran · Disetujui 4 Nov 2026: pelanggan memilih add-on dan memakai kode promo saat booking; nominal yang dibayar dicatat tetap; refund dari nominal yang dibayar; paket Medium ke atas → BQ-148, BQ-149, BQ-150
- D-016 · Gateway pembayaran milik bisnis tidak ditawarkan · Disetujui 4 Nov 2026: pilihan dan kredensial gateway milik bisnis dihapus; semua pembayaran lewat BookQu → BQ-157
- D-017 · Pembagian fitur per paket · Disetujui 4 Nov 2026: Small 5 layanan, 300 booking per bulan, tanpa akun tim; Medium layanan tanpa batas, 500 booking, Laporan dan ekspor, promo dan add-on, 2 akun tim; Pro booking tanpa batas, 10 akun tim, Unit, landing page dan domain sendiri, API dan webhook; harga tetap; menggantikan D-008 → BQ-130
- D-018 · Produksi dirilis setelah semua fitur rancangan ulang selesai · Disetujui 4 Nov 2026: rilis setelah semua fitur dalam cakupan selesai dan teruji; perbaikan keamanan yang mendesak boleh dirilis lebih dulu → BQ-158
- D-019 · Integrasi yang bisa dihubungkan owner · Disetujui 4 Nov 2026: Google Calendar, Google Analytics, dan Meta Pixel untuk semua paket; API dan webhook untuk Pro; WhatsApp Business ditunda (D-021) → BQ-155, BQ-156
- D-020 · Aturan pemesanan tambahan di Kebijakan Booking · Disetujui 4 Nov 2026: batas paling lambat memesan, paling jauh hari ke depan, dan jeda antar booking; booking langsung berlaku setelah dibayar, tanpa persetujuan manual → BQ-144
- D-021 · Yang tidak termasuk rilis ini · Disetujui 4 Nov 2026: slot otomatis dari jam buka, tutup otomatis di libur nasional, saran otomatis di Laporan, uang muka, pengingat dan integrasi WhatsApp, serta peran buatan sendiri diajukan lagi setelah rilis
- D-022 · Tiga fitur yang sudah ada tetapi belum diterima resmi · Disetujui 4 Nov 2026: reset password lewat link email sekali pakai yang punya masa berlaku; pembatasan percobaan login, pendaftaran, reset password, dan formulir publik per akun dan per alamat internet; halaman utama BookQu dengan paket dan harga mengikuti D-017 → sudah ada di kode (FR-AUTH-007, FR-AUTH-008, FR-PUBLIC-005)
- D-023 · Durasi layanan dan interval slot kelipatan 5 menit · Disetujui 5 Nov 2026: owner hanya bisa menyimpan durasi layanan dan interval jadwal massal dalam kelipatan 5 menit; layanan lama yang tidak sesuai harus diubah saat diedit → BQ-114
- D-024 · Empat tombol bantu baru di portal owner · Disetujui 16 Nov 2026: tautan ke booking setelah walk-in; tombol "Isi dari jam buka" di form buat slot sekaligus (owner tetap menyimpan sendiri, berbeda dengan slot otomatis D-021); simulasi harga menampilkan aturan yang menentukan harga; uji dan kirim ulang webhook untuk paket Pro → BQ-160
- D-025 · Tampilan portal owner "Papan Jadwal" beserta perubahan isi dan fitur barunya · Disetujui 17 Nov 2026: semua perubahan isi halaman dan fitur baru di prototipe dibangun, termasuk slot otomatis dan kalimat kesimpulan di Laporan yang dibuka kembali dari D-021; dikerjakan dulu di branch tersendiri dan baru masuk ke versi utama setelah Tech Lead memutuskan; daftar lengkapnya di `docs/design/papan-jadwal/KEPUTUSAN.md` → BQ-162
- D-026 · Aturan rinci fitur baru portal owner (lanjutan D-025) · Disetujui 17 Nov 2026 kecuali sumber booking: slot otomatis dinyalakan per layanan sampai 30 hari ke depan di semua paket; selesai otomatis 6 jam setelah sesi, aktif dan bisa dimatikan, tidak berlaku untuk tidak datang atau minta refund; tutup banyak sekaligus untuk pemilik, manajer dan kasir; ringkasan mingguan email Senin pagi untuk Pemilik, aktif dan bisa dimatikan; promo jam sepi persen atau nominal di hari dan jam pilihan pemilik untuk Medium ke atas, dengan saran jam sepi di Laporan; tabel isi paket di `docs/product.md` diperbarui; rilis v1.0.0 menunggu BQ-162. Sumber booking per kanal disembunyikan dulu karena penanda link belum ada; sumber online dan walk-in tetap seperti sekarang → BQ-162
