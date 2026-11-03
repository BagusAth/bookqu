# Keputusan Produk BookQu

Dokumen ini berisi keputusan yang perlu divalidasi Product Lead, dan catatan keputusan produk yang sudah diambil. Setiap entri berisi masalah singkat dan usulan keputusan dari Tech Lead.

**Cara menjawab:** balas dengan nomor entri dan poinnya, misalnya "D-001 setuju semua" atau "D-001 setuju kecuali poin 3: …". Setelah disetujui, Tech Lead membuat pekerjaannya. Keputusan yang sudah diambil bisa dibuka lagi kapan saja dengan menyebut nomornya.

Terakhir diperbarui: 2 November 2026

---

## Menunggu keputusan

### D-001 · Refund booking dibuat opsional dan diputuskan owner

*Status:* Menunggu · *Diajukan:* 1 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Saat ini, kalau pelanggan membatalkan booking yang sudah dibayar, sistem langsung mencatat refund dan memotong saldo owner. Halaman pelanggan juga menjanjikan uang akan dikembalikan. Padahal tidak ada yang memproses refund itu: admin tidak punya halaman refund, rekening pelanggan tidak pernah diminta, dan pelanggan tidak diberi kabar. Sebaliknya, kalau owner yang membatalkan booking yang sudah dibayar, pelanggan tidak mendapat refund sama sekali.

*Usulan keputusan*
1. Refund adalah fitur opsional. Owner mengaktifkannya sendiri di pengaturan; awalnya nonaktif.
2. Kalau refund nonaktif, pelanggan tidak bisa membatalkan booking berbayar sendiri. Yang muncul hanya tombol "Hubungi pemilik".
3. Kalau refund aktif, pelanggan hanya bisa *mengajukan* refund dengan alasan dan nomor rekening, paling lambat sesuai batas jam yang diatur owner (awalnya 24 jam sebelum jadwal).
4. Selama pengajuan belum diputuskan, booking tetap berlaku dan slot tetap dipegang.
5. Owner memutuskan: terima penuh (100%), terima setengah (50%), atau tolak dengan alasan. Kalau ditolak, booking tetap berjalan.
6. Kalau owner belum memutuskan sampai jadwal tiba, sistem tidak memutuskan apa pun soal uang. Owner terus diingatkan.
7. Uang dikembalikan oleh admin platform, karena uang pelanggan memang masuk ke rekening platform. Saldo owner dipotong sebesar refund yang disetujui.
8. Kalau owner sendiri yang membatalkan booking yang sudah dibayar, pelanggan selalu mendapat refund penuh, dan owner wajib menulis alasannya.
9. Owner mendapat halaman "Kebijakan Booking" untuk mengatur: refund aktif atau tidak, batas jam pembatalan, dan batas jam reschedule.

*Alternatif poin 7:* owner yang mentransfer sendiri ke pelanggan. Lebih sederhana, tetapi platform tidak bisa memastikan uang benar-benar kembali.

*Kalau belum diputuskan:* sebelum rilis, poin 2 tetap diterapkan untuk semua bisnis, supaya tidak ada janji refund yang tidak bisa ditepati.

*Jawaban Product Lead:* —

---

### D-002 · Apa yang terjadi saat trial atau langganan berakhir

*Status:* Menunggu · *Diajukan:* 1 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Bisnis baru mendapat trial 7 hari dengan akses setara paket Pro. Belum pernah diputuskan apa yang terjadi setelah trial habis, dan BookQu tidak punya paket gratis. Saat ini trial yang sudah habis tetap mendapat semua manfaat Pro; ini sedang diperbaiki sebagai bug. Selain itu, memperpanjang paket yang masih aktif saat ini menghilangkan sisa hari yang sudah dibayar.

*Usulan keputusan*
1. Setelah trial atau langganan habis, owner tetap bisa masuk dashboard, mengelola booking yang sudah ada, melihat saldo, dan membeli paket. Owner tidak bisa menambah layanan, jadwal, staf, atau booking walk-in baru.
2. Halaman booking bisnis tetap tampil, tetapi tidak menerima booking baru. Pesannya netral: "Bisnis ini belum menerima booking online saat ini."
3. Booking yang sudah dibayar tetap dilayani seperti biasa.
4. Tidak ada masa tenggang setelah habis.
5. Membeli paket saat trial masih berjalan: masa berbayar mulai hari itu, sisa trial hangus.
6. Memperpanjang paket yang sama: ditambahkan setelah tanggal berakhir yang sekarang, jadi sisa hari tidak hilang. Ganti paket berlaku mulai hari pembayaran.
7. Pengingat dikirim 3 hari dan 1 hari sebelum habis, serta saat sudah habis.
8. Satu akun owner hanya mendapat satu kali trial.

*Kalau belum diputuskan:* sistem memakai poin 1–3, karena tanpa paket gratis itulah perilaku yang paling aman.

*Jawaban Product Lead:* —

---

### D-003 · Apa saja yang bisa dilakukan admin platform di v1

*Status:* Menunggu · *Diajukan:* 30 Okt 2026 · *Menahan rilis:* tidak

*Masalah*
Halaman admin saat ini hanya menampilkan ringkasan dan memproses permintaan pencairan saldo owner. Belum ditentukan apa lagi yang perlu dilakukan admin, misalnya mengurus refund atau membantu bisnis yang bermasalah.

*Usulan keputusan*
1. Admin v1 bisa: memproses pencairan saldo (sudah ada), memproses refund (kalau D-001 poin 7 disetujui), dan melihat daftar bisnis beserta status langganannya.
2. Menonaktifkan bisnis, masuk sebagai owner, dan mengubah langganan secara manual ditunda setelah rilis.

*Jawaban Product Lead:* —

---

### D-004 · Rekening bank penarikan saldo disimpan atau diisi setiap pengajuan

*Status:* Menunggu · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Setiap kali owner ingin mencairkan saldo hasil booking, owner harus mengetik ulang nama bank, nomor rekening, dan nama pemilik rekening dari awal. Jika terjadi salah ketik pada nomor rekening, ada risiko dana salah ditransfer oleh admin platform. Selain itu, owner merasa kerepotan karena tidak ada rekening tujuan yang tersimpan secara praktis.

*Usulan keputusan*
1. Owner dapat menyimpan satu rekening bank utama pada profil atau pengaturan bisnis.
2. Saat membuka formulir penarikan saldo, kolom rekening otomatis terisi dengan data rekening utama tersebut, namun owner tetap bisa menyesuaikannya jika diperlukan.
3. Nama pemilik rekening dianjurkan sesuai dengan nama pemilik akun untuk mempermudah verifikasi transfer oleh admin.
4. Setiap pembaruan data rekening bank utama mengirimkan notifikasi konfirmasi ke email owner demi keamanan akun.

*Kalau belum diputuskan:* sistem tetap menggunakan pengisian manual pada setiap pengajuan penarikan agar alur pencairan saldo tetap berjalan sederhana untuk rilis awal.

*Jawaban Product Lead:* Draf: Disetujui berdasarkan langkah 3 dokumen Revisian ("Revisian BookQu — Alur Withdraw Balance Owner Bisnis", 19/10/2026: rekening tersimpan maksimal 3 per tenant, 1 rekening utama, konfirmasi password saat simpan/ubah/hapus, notifikasi email perubahan rekening). [Menunggu konfirmasi final Developer / Product Lead]

---

### D-005 · Domain sendiri (custom domain) untuk halaman booking bisnis

*Status:* Menunggu · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Sistem sudah bisa menampilkan halaman booking lewat domain milik bisnis (misalnya booking.namabisnis.com), tetapi pengaturannya hanya ada di fitur landing page yang masih disembunyikan. Akibatnya owner di v1 tidak bisa memasang domain sendiri, padahal kebutuhan ini tercantum di spesifikasi.

*Usulan keputusan*
1. Domain sendiri tidak dirilis di v1; tetap disembunyikan bersama landing page.
2. Setelah rilis, domain sendiri menjadi fitur paket Pro, diatur di halaman Pengaturan Bisnis, lengkap dengan panduan pengaturan DNS dan pengecekan otomatis apakah domain sudah tersambung.
3. Selama belum dirilis, semua tautan halaman booking tetap memakai alamat BookQu (bookqu.my.id/nama-bisnis).

*Kalau belum diputuskan:* domain sendiri tetap tersembunyi seperti sekarang.

*Jawaban Product Lead:* —

---

### D-006 · Pindah jadwal ke slot dengan harga berbeda

*Status:* Menunggu · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Harga slot bisa berbeda, misalnya slot akhir pekan lebih mahal atau owner memasang harga khusus. Saat booking dipindah ke slot lain, sistem tidak memperhitungkan selisih harga sama sekali. Pelanggan bisa pindah dari slot murah ke slot mahal tanpa membayar kekurangannya, dan sebaliknya tidak ada catatan apa pun.

*Usulan keputusan*
1. Pelanggan hanya bisa memindahkan jadwal sendiri ke slot dengan harga yang sama atau lebih murah. Untuk slot yang lebih mahal, yang muncul hanya "Hubungi pemilik".
2. Pindah ke slot yang lebih murah tidak mengembalikan selisih secara otomatis.
3. Owner boleh memindahkan booking ke slot mana pun. Sistem menampilkan selisih harganya dan mencatatnya, tanpa menagih atau mengembalikan uang secara otomatis; owner menyelesaikannya langsung dengan pelanggan.

*Kalau belum diputuskan:* perilaku tetap seperti sekarang, tetapi owner diberi peringatan selisih harga saat memindahkan jadwal.

*Jawaban Product Lead:* —

---

### D-007 · Peta halaman owner v1: halaman apa saja dan isinya apa

*Status:* Menunggu · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* tidak

*Masalah*
Halaman owner dibangun dari daftar nama halaman tanpa penjelasan isinya, jadi isinya dikarang sendiri oleh yang membangun. Akibatnya detail booking ada di tiga tempat dengan tombol yang berbeda, grafik ada di dashboard dan di analitik sekaligus, ada panel dan pengaturan yang tidak berfungsi, dan pengaturan batas pembatalan malah ada di halaman jadwal. Rincian lengkap setiap halaman ada di `docs/owner-portal.md`.

*Usulan keputusan*
1. Menu owner menjadi 11 halaman (dari 15): Beranda, Kalender, Booking, Pelanggan, Ulasan; kelompok *Kelola*: Layanan, Jadwal; kelompok *Keuangan*: Saldo, Laporan; di bawah: Pengaturan, Langganan. Notifikasi cukup dari ikon lonceng.
2. Setiap halaman punya satu tugas:
   - *Beranda:* hanya hari ini dan hal yang perlu ditindaklanjuti, tanpa grafik.
   - *Kalender:* operasional harian dan booking walk-in.
   - *Booking:* mencari dan mengelola booking.
   - *Jadwal:* hanya pengaturan slot, harga, dan hari libur.
   - *Laporan:* grafik dan tren.
3. Kategori menjadi tab di dalam Layanan. Analitik dan laporan jadwal digabung menjadi satu halaman Laporan untuk paket Medium ke atas.
4. Logo, banner, dan warna halaman booking bisa diatur semua paket di *Pengaturan → Halaman Booking*. Saat ini fitur ini tersembunyi, jadi halaman booking bisnis tidak bisa diberi logo.
5. Batas waktu pembatalan dan pindah jadwal pindah ke *Pengaturan → Kebijakan Booking* (tempat yang sama dengan pengaturan refund di D-001).
6. Arti "staf" dipilih salah satu:
   - (a) *anggota tim yang bisa login* ke dashboard dengan hak akses terbatas (usulan, dikerjakan setelah rilis); atau
   - (b) *penyedia layanan yang dipilih pelanggan saat booking*, yang butuh perombakan besar pada jadwal.

   Sampai dipilih, modul staf tetap tersembunyi.
7. Dihapus, bukan disembunyikan:
   - halaman Integrasi: isinya hanya kartu "segera hadir" dan status sistem platform; integrasi nanti ditaruh di halaman yang sesuai tugasnya;
   - pengelola media (Assets);
   - pengaturan payment gateway milik owner (BYOPG): bertentangan dengan model semua pembayaran lewat rekening platform.
8. Promo/voucher dan item tambahan (add-on) tetap tersembunyi. Keduanya dirilis setelah perhitungan harga di checkout siap.
9. Mulai sekarang, halaman atau fitur owner baru harus ditulis dulu isinya di `docs/owner-portal.md` dan disetujui, baru dibangun.

*Kalau belum diputuskan:* halaman tetap seperti sekarang. Yang tetap dikerjakan sebelum rilis hanya menghapus hal yang palsu atau tidak berfungsi (panel harga massal, pengaturan notifikasi yang tidak tersimpan, menu Integrasi).

*Jawaban Product Lead:* —

---

### D-008 · Daftar fitur paket hanya berisi fitur yang benar-benar ada

*Status:* Menunggu · *Diajukan:* 2 Nov 2026 · *Menahan rilis:* ya

*Masalah*
Daftar fitur paket di halaman harga dan halaman langganan menjanjikan banyak hal yang belum ada, tersembunyi, atau memang di luar lingkup BookQu. Contohnya staf dan role management, pengingat WhatsApp, promo & voucher, BYOPG, multi-lokasi, white-label, dan automation workflow. Pelanggan yang membayar Medium atau Pro hampir tidak mendapat apa pun selain batas booking yang lebih besar. Ini berisiko keluhan dan permintaan uang kembali.

*Usulan keputusan*
1. Daftar fitur hanya memuat fitur yang sudah dirilis untuk paket itu. Fitur yang sedang dikerjakan boleh tampil dengan label "Segera hadir", paling banyak dua per paket.
2. *Small:* semua alur inti (halaman booking, kalender dan slot, booking, pelanggan, ulasan, saldo, notifikasi email, invoice), maksimal 5 layanan dan 300 booking per bulan.
3. *Medium:* layanan tanpa batas, 500 booking per bulan, ditambah Laporan dan ekspor CSV (label "Segera hadir" sampai dirilis).
4. *Pro:* booking tanpa batas. Halaman booking kustom dan domain sendiri tampil sebagai "Segera hadir" (D-005).
5. Dihapus dari daftar: staf, role management, advanced permission, "Customer CRM" (data pelanggan sudah ada di semua paket), pengingat WhatsApp, promo & voucher, BYOPG, multi-lokasi, white-label, automation workflow, advanced analytics. "Priority support" dihapus kecuali ada komitmen layanan yang jelas.
6. Harga tidak berubah. Karena nilai Pro saat rilis kecil, Product Lead memilih salah satu:
   - (a) Pro tetap dijual dengan harga sekarang (usulan);
   - (b) Pro diturunkan harganya sampai fitur Pro siap;
   - (c) Pro tidak dijual dulu.

*Kalau belum diputuskan:* poin 1 dan 5 tetap diterapkan sebelum rilis (dikerjakan di BQ-115), supaya tidak menjual fitur yang tidak ada.

*Jawaban Product Lead:* —

---

## Untuk diketahui (tidak perlu keputusan)

Perubahan teknis yang terlihat oleh owner atau pelanggan, diputuskan Tech Lead:

- *1 Nov 2026:* selama pelanggan masih dalam waktu pembayaran online (15 menit), owner tidak bisa membatalkan atau menandai lunas booking itu. Ini mencegah pelanggan membayar dua kali.
- *1 Nov 2026:* tombol "Tandai Lunas" untuk booking yang dibayar online dihapus. Kalau pelanggan membayar tunai di tempat, owner mencatatnya sebagai booking walk-in.
- *1 Nov 2026:* kalau pelanggan sampai membayar dua kali, sistem mencatat refund dan memberi tahu owner, tidak lagi diam saja.
- *1 Nov 2026:* trial atau langganan yang sudah habis tidak lagi mendapat manfaat Pro. Sampai D-002 diputuskan, berlaku poin 1–3 D-002.

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

Belum ada.
