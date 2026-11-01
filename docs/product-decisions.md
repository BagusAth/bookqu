# Keputusan Produk BookQu

Dokumen ini berisi keputusan yang perlu divalidasi Product Lead, dan catatan keputusan produk yang sudah diambil. Setiap entri berisi masalah singkat dan usulan keputusan dari Tech Lead.

**Cara menjawab:** balas dengan nomor entri dan poinnya, misalnya "D-001 setuju semua" atau "D-001 setuju kecuali poin 3: …". Setelah disetujui, Tech Lead membuat pekerjaannya. Keputusan yang sudah diambil bisa dibuka lagi kapan saja dengan menyebut nomornya.

Terakhir diperbarui: 1 November 2026

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
