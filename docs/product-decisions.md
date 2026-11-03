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
- D-007 · Peta halaman owner · Disetujui 4 Nov 2026 (Revisi 1): 15 menu dalam lima kelompok dengan satu tugas per halaman, satu panel booking yang sama di semua halaman, Kebijakan Booking di Pengaturan, semua fitur lama tetap ada kecuali yang dihapus oleh D-011 dan D-016, alamat lama dialihkan, halaman baru ditulis dulu sebelum dibangun → item menyusul (BQ-126)
- D-008 · Daftar fitur paket hanya berisi fitur yang benar-benar ada · Ditutup 4 Nov 2026: digantikan D-017
- D-009 · Logo, sampul, dan warna halaman booking untuk semua paket · Disetujui 4 Nov 2026: semua paket mengatur logo, sampul, warna, dan deskripsi di menu Halaman Booking dengan pratinjau HP; semua gambar dari satu galeri media; landing page lengkap tetap khusus Pro → item menyusul (BQ-126)
- D-010 · Link, QR, dan materi promosi untuk semua paket · Disetujui 4 Nov 2026: salin, buka, dan bagikan link ke WhatsApp, QR, poster siap cetak, teks bio Instagram dan pesan WhatsApp siap pakai → item menyusul (BQ-126)
- D-011 · Staf adalah akun tim dengan peran Pemilik, Manajer, dan Kasir · Disetujui 4 Nov 2026: staf adalah anggota tim yang login, bukan penyedia layanan; Kasir hanya mengajukan pembatalan; undangan lewat email dan akses bisa dicabut; setiap aksi dicatat dengan nama pelakunya → item menyusul (BQ-126)
- D-012 · Unit yang dipakai beberapa layanan · Disetujui 4 Nov 2026: unit tidak bisa terpesan dua kali di jam yang sama walaupun layanannya berbeda; bentrok yang sudah ada ditampilkan; paket Pro → item menyusul (BQ-126)
- D-013 · Status booking "Tidak datang" · Disetujui 4 Nov 2026: status akhir baru setelah jadwal lewat; uang tidak dikembalikan otomatis; tampil di Laporan → item menyusul (BQ-126)
- D-014 · Lebih dari satu peserta per slot · Disetujui 4 Nov 2026: kapasitas per layanan, awalnya 1; satu booking boleh untuk beberapa peserta selama sisa kapasitas cukup, harga per peserta; semua paket → item menyusul (BQ-126)
- D-015 · Promo dan add-on masuk ke total pembayaran · Disetujui 4 Nov 2026: pelanggan memilih add-on dan memakai kode promo saat booking; nominal yang dibayar dicatat tetap; refund dari nominal yang dibayar; paket Medium ke atas → item menyusul (BQ-126)
- D-016 · Gateway pembayaran milik bisnis tidak ditawarkan · Disetujui 4 Nov 2026: pilihan dan kredensial gateway milik bisnis dihapus; semua pembayaran lewat BookQu → item menyusul (BQ-126)
- D-017 · Pembagian fitur per paket · Disetujui 4 Nov 2026: Small 5 layanan, 300 booking per bulan, tanpa akun tim; Medium layanan tanpa batas, 500 booking, Laporan dan ekspor, promo dan add-on, 2 akun tim; Pro booking tanpa batas, 10 akun tim, Unit, landing page dan domain sendiri, API dan webhook; harga tetap; menggantikan D-008 → item menyusul (BQ-126)
- D-018 · Produksi dirilis setelah semua fitur rancangan ulang selesai · Disetujui 4 Nov 2026: rilis setelah semua fitur dalam cakupan selesai dan teruji; perbaikan keamanan yang mendesak boleh dirilis lebih dulu → BQ-126
- D-019 · Integrasi yang bisa dihubungkan owner · Disetujui 4 Nov 2026: Google Calendar, Google Analytics, dan Meta Pixel untuk semua paket; API dan webhook untuk Pro; WhatsApp Business ditunda (D-021) → item menyusul (BQ-126)
- D-020 · Aturan pemesanan tambahan di Kebijakan Booking · Disetujui 4 Nov 2026: batas paling lambat memesan, paling jauh hari ke depan, dan jeda antar booking; booking langsung berlaku setelah dibayar, tanpa persetujuan manual → item menyusul (BQ-126)
- D-021 · Yang tidak termasuk rilis ini · Disetujui 4 Nov 2026: slot otomatis dari jam buka, tutup otomatis di libur nasional, saran otomatis di Laporan, uang muka, pengingat dan integrasi WhatsApp, serta peran buatan sendiri diajukan lagi setelah rilis
