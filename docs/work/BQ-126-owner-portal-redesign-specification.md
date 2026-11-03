---
id: BQ-126
title: Write the specification and reference documents for the owner portal redesign, then audit the backlog
type: docs
status: in-progress
priority: P1
phase: 8
domain: staged-modules
requirements: []
invariants: [INV-BOOK-01, INV-BOOK-02, INV-BOOK-03, INV-BOOK-04, INV-BOOK-05, INV-PAY-01, INV-SUB-01, INV-TEN-01]
lane: heavy
approval: tech-lead
decision: D-001, D-002, D-004, D-005, D-006, D-007
launch-blocker: true
depends-on: []
---

# BQ-126: Spesifikasi dan dokumen acuan rancangan ulang portal owner, lalu audit backlog

Work item ini adalah **rencana kerja dokumen**, bukan rencana kode. Hasilnya adalah seluruh acuan yang dibutuhkan untuk membangun rancangan ulang portal owner sampai selesai:

- keputusan produk;
- inventaris fitur saat ini;
- spesifikasi halaman;
- requirement, produk, dan glosarium;
- ADR, aturan agen, dan arsitektur;
- prototype;
- ROADMAP, work item per epic, dan audit backlog.

Agen yang mengerjakan item ini mengikuti fase di bawah secara berurutan, berhenti di setiap gerbang persetujuan, dan tidak mengubah kode aplikasi.

## Problem

1. **Rancangan ulang belum ada di repo.** Product Lead menyetujui dokumen rancangan ulang portal owner dan prototype-nya (4 Nov 2026). Keduanya saat ini hanya ada di luar repo:
   - `C:\Users\agath\BookQu-Prototype\Rancangan-Ulang-Portal-Owner-BookQu.md` (selanjutnya **Rancangan**);
   - `C:\Users\agath\BookQu-Prototype\portal-owner-bookqu-v3.html` (selanjutnya **Prototype**).
2. **Dokumen acuan masih memakai rencana lama:**
   - `docs/owner-portal.md` masih draf 2 Nov 2026 (11 menu);
   - `docs/requirements.md`, `docs/product.md`, `AGENTS.md`, dan ADR belum memuat Tim dan peran, Unit, no-show, kapasitas per slot, promo dan add-on di checkout, alur refund D-001, serta perilaku berakhirnya langganan D-002.
3. **Kode sudah berbeda dari requirement.** FR-PAYOUT-002 dan FR-PAYOUT-003 belum mengikuti BQ-123 sampai BQ-125, yang sudah ada di kode.
4. **Catatan keputusan belum sesuai format.** `docs/product-decisions.md` mencatat keputusan 4 Nov 2026 dengan entri lengkap dan status "Diputuskan", "Sedang direvisi", "Ditunda". Format di `docs/workflow.md` §5 adalah status `Menunggu`, `Disetujui`, atau `Ditolak`, dan entri yang sudah diputuskan ditulis satu baris.
5. **Kebijakan rilis berubah.** Produksi baru dirilis setelah semua fitur rancangan ulang selesai. Backlog (53 item terbuka) masih disusun untuk rilis lama. Banyak item tampilan yang akan terserap ke rancangan baru, dan sebagian lain harus tetap dikerjakan.

## Sumber yang dipakai

| Sumber | Dipakai untuk |
|---|---|
| Rancangan §6 (spesifikasi per halaman), §7 (peta perubahan), §8 (peran), §9 (paket), §10 (keputusan), §12 (risiko), §13 (yang tidak berubah) | Isi spesifikasi |
| Prototype (panel Demo, peran Pemilik/Manajer/Kasir) | Struktur, alur, dan isi contoh; **bukan** desain visual akhir |
| `docs/product-decisions.md` D-001 sampai D-008 | Keputusan yang sudah diambil |
| Kode saat ini (`routes/web.php`, `app/Http/Controllers/Owner/**`, `resources/views/owner/**`, `config/modules.php`, `config/plans.php`) | Inventaris fitur yang tidak boleh hilang |
| `docs/requirements.md`, `docs/product.md`, `docs/domains/*.md`, `docs/adr/*.md` | Dokumen yang direvisi |

**Kalau sumber bertentangan:** keputusan yang tercatat di `docs/product-decisions.md` mengalahkan Rancangan, dan Rancangan mengalahkan Prototype. Contoh: Rancangan §10 poin 2 menulis "owner memilih refund penuh, tanpa refund, atau sesuai kebijakan". D-001 yang berlaku.

## Gerbang persetujuan

| Gerbang | Siapa | Kapan | Isi |
|---|---|---|---|
| G0 | Tech Lead | Sebelum Fase 0 | Rencana ini (`status: approved`) dan jawaban atas "Pertanyaan terbuka" |
| G1 | Product Lead (lewat Tech Lead) | Akhir Fase 1 | Entri D-009 dan seterusnya |
| G2 | Product Lead (lewat Tech Lead) | Akhir Fase 3 | D-007 Revisi 1 (peta halaman dan isi) |
| G3 | Tech Lead | Akhir Fase 6 | ADR baru (`Accepted`), perubahan `AGENTS.md`, `.ai/rules`, `architecture.md` |
| G4 | Tech Lead | Akhir Fase 9 | ROADMAP baru, work item per epic, hasil audit backlog |

Agen berhenti di setiap gerbang. Fase berikutnya hanya boleh dimulai kalau tidak bergantung pada jawaban gerbang itu (lihat kolom "Bergantung pada" di setiap fase).

## Plan

### Fase 0 · Persiapan

*Bergantung pada:* G0.

1. **Rapikan format `docs/product-decisions.md`** sesuai `docs/workflow.md` §5:
   - D-001, D-002, D-004, D-005, D-006 ditulis sebagai satu baris di "Sudah diputuskan", berbentuk `- D-NNN · judul · Disetujui 4 Nov 2026: ringkasan satu kalimat → item`. Item diisi setelah Fase 9.
   - Isi lengkap keputusannya dipindah ke requirement di Fase 4. Sebelum Fase 4 selesai, isi lengkap tetap ada di Git (commit `451d089`).
   - D-007 kembali ke status `Menunggu`, dengan catatan "Revisi 1 sedang disusun".
   - D-008 tetap `Menunggu`, dengan catatan bahwa entri ini ditunda karena kebijakan rilis (D-018).
   - D-003 tetap `Menunggu`.
2. **Simpan sumber rancangan di repo** sebagai lampiran work item ini (lihat "Pertanyaan terbuka" 1):
   - `docs/work/BQ-126/rancangan-ulang-portal-owner.md`: salinan Rancangan, apa adanya, ditambah satu catatan di atas bahwa D-001 mengalahkan §10 poin 2;
   - `docs/work/BQ-126/prototype-portal-owner.html`: salinan Prototype.
3. **Buat item lanjutan dari audit PR #31** (`docs/work/BQ-127-payout-account-follow-ups.md`, light lane):
   - soft delete tetap terkena indeks unik, sehingga rekening yang pernah dihapus tidak bisa ditambahkan lagi dan muncul galat 500;
   - `throttle:3,10` berlaku untuk "Jadikan utama";
   - batas 3 rekening dan Rp 10.000 ditulis langsung di kode, tidak membaca `config/payouts.php`;
   - kolom `approved_by` tidak punya foreign key.

*Selesai kalau:* format sesuai §5, lampiran tersimpan, dan BQ-127 ada.

### Fase 1 · Keputusan produk baru

*Bergantung pada:* G0. Bisa berjalan bersamaan dengan Fase 2.

Tulis entri baru di `docs/product-decisions.md`. Bahasa Indonesia, untuk pembaca non-teknis, dengan format §5. Setiap entri dibuat dari Rancangan §10 dan halaman terkait di §6.

| Entri | Isi | Sumber Rancangan |
|---|---|---|
| D-009 | Logo, sampul, dan warna halaman booking untuk semua paket | §10 poin 4 |
| D-010 | Link, QR, poster, dan materi promosi untuk semua paket | §10 poin 5 |
| D-011 | Staf adalah akun tim dengan peran Pemilik, Manajer, Kasir; jejak aktivitas menyebut nama pelaku | §8, §10 poin 7 |
| D-012 | Unit dipakai bersama beberapa layanan, dicegah terpesan dua kali; paket Pro | §6 Unit, §10 poin 9 |
| D-013 | Status akhir booking "Tidak datang" | §10 poin 10 |
| D-014 | Kapasitas lebih dari 1 orang per slot | §10 poin 11 |
| D-015 | Promo dan add-on masuk total pembayaran; paket Medium ke atas | §10 poin 13 |
| D-016 | Gateway milik bisnis (BYOPG) tidak ditawarkan | §10 poin 14 |
| D-017 | Pembagian fitur per paket, termasuk jumlah akun tim (0/2/10); menggantikan D-008 bila disetujui | §8, §9 |
| D-018 | Kebijakan rilis: produksi setelah semua fitur rancangan ulang selesai | Keputusan 4 Nov 2026 |
| D-019 | Integrasi milik owner: WhatsApp Business, Google Calendar, Google Analytics dan Meta Pixel; API dan webhook untuk Pro | §6 Pengaturan → Integrasi |
| D-020 | Aturan pemesanan tambahan di Kebijakan Booking: minimal waktu pesan sebelum sesi, maksimal hari ke depan, jeda antar booking, konfirmasi otomatis | §6 Pengaturan → Kebijakan Booking |
| D-021 | Di luar cakupan rilis ini: slot otomatis dari jam buka, tutup otomatis di libur nasional, saran otomatis di Laporan, uang muka (DP), pengingat WhatsApp | §10 poin 16–20 |

Aturan menulis:
- Satu entri berisi satu keputusan. Tulis masalah dalam 2–4 kalimat, usulan bernomor, dan apa yang berlaku bila belum diputuskan.
- Usulannya mengikuti Rancangan. Kalau Rancangan menyebut "usulan" atau "akan diputuskan", tulis pilihannya dan beri rekomendasi.
- D-020 poin "konfirmasi otomatis" berarti ada booking yang menunggu konfirmasi owner, yaitu **status booking baru**. Tulis dua pilihan: (a) tanpa konfirmasi manual (rekomendasi), atau (b) status baru. Tandai bahwa pilihan (b) mengubah AGENTS aturan no. 4.
- D-011 harus menjelaskan bahwa Kasir hanya *mengajukan* pembatalan. Ini mengikuti Rancangan §8, dan tetap tunduk pada D-001 poin 8 (pembatalan oleh owner selalu refund penuh).

*Selesai kalau:* D-009 sampai D-021 tertulis, ringkasan untuk Product Lead siap dikirim (satu pesan per entri), lalu **berhenti di G1**. Setelah jawaban Product Lead datang, hanya Tech Lead yang mencatatnya.

### Fase 2 · Inventaris fitur saat ini

*Bergantung pada:* G0. Bisa berjalan bersamaan dengan Fase 1.

Tujuannya: tidak ada fitur yang hilang diam-diam saat spesifikasi baru ditulis. Ini juga menjawab temuan bahwa Prototype kehilangan sebagian fitur lama.

1. Untuk setiap halaman owner saat ini, termasuk modul yang disembunyikan atau dikunci di `config/modules.php`, catat:
   - route;
   - controller dan action;
   - view;
   - setiap field (nama, tipe, validasi dari Form Request);
   - setiap aksi (tombol, form, endpoint) beserta syarat status;
   - gerbang modul dan paket;
   - requirement ID yang terkait;
   - status: berfungsi, palsu atau tidak tersimpan, rusak.
2. Lakukan hal yang sama untuk bagian portal pelanggan dan admin yang tersentuh rancangan:
   - checkout (promo, add-on, kebijakan);
   - halaman kelola booking (refund, reschedule);
   - admin payout dan refund.
3. Untuk setiap baris, petakan ke halaman, tab, dan bagian baru di Rancangan §6. Kalau tidak ada tempatnya, tandai **YATIM**.
4. Cek data yang sudah ada di modul tersembunyi (Rancangan §12 poin 3): tabel apa saja yang berisi data owner (staf, voucher, add-on, aset, landing page), dan apa artinya untuk migrasi. Ini cukup lewat pembacaan skema dan model; jangan membaca data produksi.
5. Bandingkan dengan Prototype. Catat fitur lama yang tidak tampil di Prototype.

*Hasil:* `docs/work/BQ-126/inventaris-fitur.md`. Isinya tabel per halaman, daftar YATIM, daftar fitur yang tidak ada di Prototype, dan daftar data di modul tersembunyi.

*Selesai kalau:* setiap route owner di `php artisan route:list --path=owner` muncul tepat satu kali di inventaris, dan setiap YATIM punya usulan tempat atau usulan dihapus (dengan alasan) untuk dibahas di Fase 3.

### Fase 3 · Spesifikasi halaman (`docs/owner-portal.md`)

*Bergantung pada:* Fase 1 tertulis dan Fase 2 selesai. Isinya boleh ditulis sebelum G1, tetapi bagian yang bergantung pada D-009 sampai D-021 ditandai "menunggu D-NNN".

Tulis ulang `docs/owner-portal.md` dalam bahasa Inggris (dokumen otoritas). Draf 2 Nov 2026 diganti seluruhnya, tanpa riwayat; riwayat ada di Git.

1. **Struktur dokumen:**
   1. tujuan dokumen dan cara mengubahnya;
   2. pekerjaan owner (job);
   3. aturan penempatan (satu tugas per halaman, satu rumah per entitas);
   4. navigasi: 15 menu dalam 5 grup, bilah atas (lonceng, + Booking, pencarian), navigasi bawah di HP;
   5. rumah setiap entitas;
   6. kontrak setiap halaman;
   7. komponen bersama (panel detail booking, header halaman, tab, dialog, toast, pemilih gambar dari galeri, kosong/memuat/galat);
   8. matriks peran × halaman × aksi, yang menautkan ke FR-TEAM sebagai sumber aturannya;
   9. gerbang paket per fitur, yang menautkan ke `product.md` §7 sebagai sumber aturannya;
   10. peta dari halaman dan route lama ke yang baru (route lama tetap hidup, Rancangan §13);
   11. urutan pembangunan, yang menautkan ke ROADMAP.
2. **Kontrak setiap halaman memuat:**
   - pertanyaan yang dijawab halaman itu;
   - route;
   - akses per peran;
   - paket;
   - bagian dan field, termasuk baris inventaris yang menjadi asalnya;
   - aksi beserta syarat status (contoh: "Tandai tidak datang" hanya untuk booking `paid` yang jadwalnya sudah lewat);
   - keadaan kosong dan galat;
   - "tidak ada di sini";
   - requirement ID;
   - **kriteria terima** yang bisa diuji.
3. **Penyesuaian wajib terhadap Rancangan:**
   - **Booking:** aksi batalkan mengikuti D-001. Owner membatalkan selalu dengan refund penuh untuk booking berbayar. Pengajuan refund dari pelanggan diputuskan 100%, 50%, atau tolak.
   - **Pembayaran → Rekening:** sampai 3 rekening dengan 1 rekening utama (D-004).
   - **Halaman Booking:** memuat domain sendiri untuk Pro (D-005).
   - **Booking dan panel detail:** pindah jadwal menampilkan selisih harga (D-006).
   - **Paket & Tagihan, Beranda, dan semua halaman yang membuat sesuatu baru:** perilaku setelah langganan berakhir mengikuti D-002.
   - Setiap YATIM dari Fase 2 mendapat tempat, atau tercatat sebagai "dihapus" beserta alasan dan keputusan yang menghapusnya.
4. **Tulis D-007 Revisi 1** di `docs/product-decisions.md`: ringkasan peta 15 menu dan tugas setiap halaman, dalam bahasa Indonesia non-teknis. Tidak ada salinan aturan, cukup ringkasan yang menautkan ke `docs/owner-portal.md`.

*Selesai kalau:*
- setiap baris inventaris punya tempat atau alasan dihapus;
- setiap aksi punya peran, paket, dan syarat status;
- setiap halaman punya kriteria terima;
- lalu **berhenti di G2**.

### Fase 4 · Requirement (`docs/requirements.md`)

*Bergantung pada:* Fase 3. Bagian yang bergantung pada D-009 sampai D-021 menunggu G1.

ID yang sudah ada tidak diubah dan tidak dipakai ulang. Requirement yang tidak berlaku lagi ditandai `(Withdrawn YYYY-MM-DD, D-NNN)` dan menautkan penggantinya, tidak dihapus.

| Area | Perubahan |
|---|---|
| FR-AUTH-005 | Pengguna owner bisa berupa pemilik atau anggota tim |
| **FR-TEAM** (baru) | Undangan, peran Pemilik/Manajer/Kasir, matriks izin, batas akun per paket, jejak aktivitas, pencabutan akses |
| **FR-REFUND** (baru) | Pengajuan refund pelanggan, keputusan owner (100/50/tolak), refund penuh saat owner membatalkan, pemrosesan oleh admin, pemotongan saldo (D-001) |
| FR-MANAGE | Pembatalan dan reschedule pelanggan mengikuti D-001 dan D-006 |
| FR-SUB | Perilaku setelah trial atau langganan berakhir, perpanjangan, pengingat, satu kali trial (D-002) |
| FR-PAYOUT-002, FR-PAYOUT-003 | Disesuaikan dengan kode BQ-123 sampai BQ-125 (D-004). Ini perbaikan dokumen yang tertinggal dari kode, bukan perubahan perilaku |
| FR-PUBLIC, FR-APPEARANCE | Domain sendiri untuk Pro di Halaman Booking (D-005); tampilan untuk semua paket (D-009); link, QR, dan materi promosi (D-010) |
| FR-ASSET | Menjadi galeri media yang dipakai pemilih gambar |
| FR-BOOKING | No-show (D-013), kapasitas per slot (D-014), aturan pemesanan tambahan (D-020), nominal booking tetap walaupun harga berubah |
| FR-RESOURCE, FR-STAFFRESOURCE, FR-STAFF | Ditarik; diganti **FR-UNIT** (baru, D-012) dan FR-TEAM |
| FR-VOUCHER, FR-ADDON | Promo dan add-on dihitung di checkout dan di nominal pembayaran (D-015) |
| FR-PAYMENT | Pilihan gateway milik bisnis ditarik (D-016) |
| **FR-INTEGRATION**, **FR-API** (baru) | Integrasi milik owner dan API/webhook Pro (D-019) |
| FR-NOTIFICATION | Pengaturan per kejadian dan per saluran, disimpan di server |
| FR-DASH, FR-CALENDAR, FR-ANALYTICS, FR-REPORT | Disusun ulang sesuai Beranda, Kalender, dan Laporan baru |
| **FR-HELP**, **FR-SHELL** (baru) | Bantuan; bilah atas, pencarian global, + Booking, navigasi HP |
| Booking State Rules, Core Business Rules | Status `no_show`; occupancy dengan Unit dan kapasitas |
| Product Scope | Mengikuti D-018 dan D-021 |

*Selesai kalau:*
- setiap kontrak halaman di Fase 3 menautkan requirement yang ada;
- setiap requirement baru punya kriteria yang bisa diuji;
- `grep -c` per ID menunjukkan tidak ada ID ganda.

### Fase 5 · Produk dan glosarium

*Bergantung pada:* Fase 4.

1. **`docs/product.md`:**
   - §2 pengguna: tambah anggota tim;
   - §5 area produk: portal owner cukup menautkan `owner-portal.md`;
   - §6 model pembayaran: refund D-001, tanpa BYOPG;
   - §7 model langganan: matriks D-017 dan perilaku D-002;
   - §8 tahap rilis: daftar modul baru dan kebijakan rilis D-018;
   - §9 ruang lingkup: daftar D-021.
2. **`docs/glossary.md`:** Unit, add-on, promo, peran (Pemilik, Manajer, Kasir), anggota tim, no-show/Tidak datang, kapasitas, pengajuan refund, rekening utama, galeri media. Tambahkan juga nama menu lama ke nama menu baru.

*Selesai kalau:* tidak ada aturan yang tertulis di dua tempat (`.ai/rules/documentation.md`, "One rule, one place").

### Fase 6 · ADR, aturan agen, dan arsitektur

*Bergantung pada:* Fase 4.

1. **ADR baru** (`docs/adr/_TEMPLATE.md`, status `Proposed`):

   | ADR | Isi | Menyentuh |
   |---|---|---|
   | ADR-0016 | Akun tim dan otorisasi berbasis peran di dalam tenant | ADR-0010, `TenantMiddleware`/`TenantContext`, INV-TEN-01 |
   | ADR-0017 | Unit bersama dan kapasitas dalam aturan occupancy | ADR-0005, `BookingRules`, indeks `unique_active_booking_slot`, INV-BOOK-03/04/05 |
   | ADR-0018 | Status akhir `no_show` | `BookingState::canTransition`, INV-BOOK-01/02 |
   | ADR-0019 | Harga di checkout: promo, add-on, dan nominal yang dibekukan | ADR-0008, verifikasi nominal pembayaran INV-PAY-* |
   | ADR-0020 | Pengajuan refund dan pemrosesan oleh admin | ADR-0004, `TenantBalanceLedger`, D-001 |
   | ADR-0021 | Gerbang rilis setelah rancangan ulang: modul, paket, peran | Mengubah ADR-0002 dan ADR-0011 |
   | ADR-0022 | API publik dan webhook keluar | Pola integrasi baru, D-019 |

   Setiap ADR menulis keputusan, alternatif yang ditolak, akibatnya, dan invariant yang berubah. Bagian implementasi cukup arah; rinciannya di work item epic.
2. **`AGENTS.md`** (di luar blok Boost; setelah diubah, jalankan `php artisan boost:update` dan pastikan bagian BookQu tidak berubah):
   - aturan no. 4: tambah `no_show`, dan status baru D-020 bila pilihan (b) disetujui;
   - aturan no. 5 dan 6: occupancy memperhitungkan Unit dan kapasitas;
   - aturan no. 13: tahap rilis mengikuti ADR-0021;
   - tabel "Where things are": `owner-portal.md` tidak lagi draf.
3. **`.ai/rules`:**
   - `frontend.md`: view owner harus mengikuti kontrak halaman di `docs/owner-portal.md`;
   - `tenancy-security.md`: setiap aksi owner diperiksa peran di server;
   - `booking-availability.md`: Unit dan kapasitas hanya lewat `BookingRules`;
   - `payment.md`: refund dan nominal checkout.

   Setelah mengubah glob, regenerasi `.ai/rules/index.md` (perintahnya ada di `.ai/rules/documentation.md`).
4. **`docs/architecture.md`:** lapisan otorisasi peran, dan modul baru (Team, Unit, Promo/Checkout pricing, Refund, Integration) beserta batasnya.
5. **`docs/domains/*.md` tidak diubah** di fase ini. File domain menggambarkan kode saat ini, jadi baru diubah oleh work item yang membangun fiturnya.

*Selesai kalau:* invariant yang berubah tertulis di ADR; perintah `boost:update` dan regenerasi index sudah dijalankan; lalu **berhenti di G3**.

### Fase 7 · Prototype sebagai acuan tampilan

*Bergantung pada:* G2.

1. Sesuaikan `docs/work/BQ-126/prototype-portal-owner.html` dengan spesifikasi final:
   - alur refund D-001;
   - pilihan 3 rekening;
   - selisih harga saat reschedule;
   - layar langganan berakhir;
   - fitur dari daftar "tidak ada di Prototype" di Fase 2;
   - keputusan D-020.
2. Tulis catatan di bagian atas file: spesifikasi mengalahkan prototype. Prototype hanya menunjukkan struktur dan alur.
3. **Opsional dan terpisah:** perapian visual (misalnya lewat Google Stitch) tanpa mengubah struktur. Hasilnya menjadi acuan komponen di Fase 9, epic Fondasi.

*Selesai kalau:* setiap kriteria terima di Fase 3 yang bisa ditunjukkan di prototype memang tampil.

### Fase 8 · ROADMAP dan epic

*Bergantung pada:* G1, G2, G3.

1. Tulis ulang bagian "Current status" dan "Phases" di `docs/work/ROADMAP.md`. Fase 5 (Launch) menunggu semua epic di bawah, sesuai D-018.
2. **Epic:**

   | Epic | Isi | Lane |
   |---|---|---|
   | E0 · Keamanan dan integritas yang tetap berlaku | Item lama yang tetap dikerjakan, hasil Fase 9 | Sesuai item |
   | E1 · Fondasi | Komponen bersama, layout dan navigasi baru, bilah atas, navigasi HP, bahasa Indonesia, kerangka izin peran | Light, kecuali izin (heavy) |
   | E2 · Alur inti | Layanan, Jam Buka & Harga, Kalender, Booking dan panel detail, Beranda | Light, kecuali nominal tetap (heavy) |
   | E3 · Keputusan yang sudah disetujui | Refund D-001, langganan berakhir D-002, reschedule D-006 | Heavy |
   | E4 · Kepercayaan dan akuisisi | Pembayaran, Halaman Booking (tampilan, galeri, link/QR), Pelanggan, Ulasan, Pengaturan, Paket & Tagihan, Bantuan | Light, kecuali Kebijakan Booking D-020 (heavy) |
   | E5 · Nilai Medium | Laporan, Promo dan Add-on di checkout, Tim dan peran | Heavy |
   | E6 · Nilai Pro dan aturan inti | Unit, kapasitas, no-show, Landing Page dan domain sendiri, integrasi, API dan webhook | Heavy |
   | E7 · Rilis | Gerbang modul dan paket per ADR-0021, `config/plans.php`, `config/modules.php`, daftar paket yang jujur, ops BQ-020/021/022, perlindungan branch | Heavy |

3. **Pecah setiap epic menjadi work item** `docs/work/BQ-NNN-*.md` dari `_TEMPLATE.md`. Setiap item berisi:
   - `decision`, `requirements`, `invariants`, `depends-on`;
   - halaman dan kriteria terima dari `owner-portal.md` yang dipenuhi;
   - baris inventaris yang dipindah.

   Satu item kira-kira satu PR. Item heavy berstatus `proposed` sampai Tech Lead menyetujuinya.

*Selesai kalau:* setiap kriteria terima di `owner-portal.md` dan setiap requirement baru di Fase 4 dimiliki tepat satu work item.

### Fase 9 · Audit backlog

*Bergantung pada:* Fase 8.

1. Periksa semua item `docs/work/BQ-*.md` yang belum `done` (saat rencana ini ditulis: 30 proposed, 20 approved, 3 in-progress). Klasifikasikan setiap item:

   | Kelas | Arti | Tindakan |
   |---|---|---|
   | **Tetap** | Keamanan, integritas data, uang, operasional, atau perilaku server yang tetap ada walaupun tampilan diganti | Masuk E0 atau epic terkait; `depends-on` diperbarui |
   | **Dilebur** | Masalah tampilan atau isi halaman yang terselesaikan oleh kontrak halaman baru | `status: rejected` dengan alasan "Dilebur ke BQ-NNN (BQ-126)". Temuan teknisnya disalin ke kriteria terima item pengganti supaya tidak hilang |
   | **Diubah** | Masih perlu, tetapi lingkupnya berubah karena keputusan baru | Perbarui item, atau ganti dengan item baru dan tolak yang lama |
   | **Tidak relevan** | Fiturnya dihapus (BYOPG, menu Integrasi lama, dan sejenisnya) | `status: rejected` dengan alasan dan keputusannya |

2. Item yang `done` tetapi masih di `docs/work/` (BQ-103, BQ-122 sampai BQ-125) dipindah ke `docs/work/done/`.
3. Catat setiap klasifikasi di tabel "Backlog audit" di bagian Evidence item ini: ID, kelas, alasan, item pengganti.
4. Isi item pada baris "Sudah diputuskan" di `docs/product-decisions.md` (Fase 0 langkah 1).

*Selesai kalau:* setiap item terbuka punya kelas dan tindakan; tidak ada temuan keamanan atau integritas yang hilang; lalu **berhenti di G4**.

## Pembagian PR

| PR | Fase | Isi |
|---|---|---|
| 1 | 0, 1 | Format keputusan, lampiran sumber, BQ-127, D-009 sampai D-021 |
| 2 | 2 | Inventaris fitur |
| 3 | 3, 4, 5 | `owner-portal.md`, D-007 Revisi 1, `requirements.md`, `product.md`, `glossary.md` |
| 4 | 6 | ADR-0016 sampai ADR-0022, `AGENTS.md`, `.ai/rules`, `architecture.md` |
| 5 | 7 | Prototype |
| 6 | 8, 9 | ROADMAP, work item epic, audit backlog |

Semua PR menuju `integration`, dari branch milik developer yang menjalankan sesi. Tidak ada perubahan di `app/`, `config/`, `database/`, `resources/`, `routes/`, atau `tests/`.

## Pertanyaan terbuka (jawab di G0)

1. **Lokasi sumber rancangan di repo:** `docs/work/BQ-126/`, sebagai lampiran work item ini (rekomendasi), atau tidak disimpan di repo?
2. **Batas "semua fitur" untuk rilis:** Rancangan §6 ditambah D-001 sampai D-006, sedangkan Rancangan §10 poin 16–20 dan pengingat WhatsApp di luar cakupan (rekomendasi, dicatat di D-021)?

## Invariants at risk

Fase ini hanya mengubah dokumen, tetapi dokumen itu akan **mengubah invariant** saat dibangun:

- INV-BOOK-01/02: status `no_show`, dan mungkin status konfirmasi D-020;
- INV-BOOK-03/04/05: Unit dan kapasitas;
- INV-PAY-*: nominal dengan promo dan add-on, serta refund;
- INV-SUB-01: matriks paket dan jumlah akun tim;
- INV-TEN-01: anggota tim di dalam tenant.

Setiap perubahan invariant ditulis di ADR (Fase 6) dan disetujui di G3 sebelum ada kode. Aturan di `AGENTS.md` tetap berlaku untuk kode sampai ADR-nya diterima.

## Tests

Tidak ada tes kode di item ini. Pemeriksaan dokumen:

- tidak ada ID ganda (`FR-`, `BR-`, `INV-`, `ADR-`, `D-`, `BQ-`);
- setiap tautan relatif di dokumen yang diubah menunjuk file yang ada;
- tidak ada aturan yang ditulis di dua tempat;
- `php artisan boost:update` tidak mengubah bagian BookQu di `AGENTS.md`;
- `.ai/rules/index.md` sudah diregenerasi;
- keempat job CI hijau di setiap PR.

## Docs to update

- `docs/product-decisions.md`
- `docs/owner-portal.md`
- `docs/requirements.md`
- `docs/product.md`
- `docs/glossary.md`
- `docs/architecture.md`
- `docs/adr/ADR-0016` sampai `ADR-0022`
- `AGENTS.md`
- `.ai/rules/*.md`
- `docs/work/ROADMAP.md`
- `docs/work/BQ-*.md`
- lampiran di `docs/work/BQ-126/`

## Evidence (fill when done)

Per fase: PR, gerbang dan tanggal persetujuannya, perintah pemeriksaan yang dijalankan. Tabel "Backlog audit" dari Fase 9 ditulis di sini.

| Gerbang | Tanggal | Hasil |
|---|---|---|
| G0 | 4 Nov 2026 | Tech Lead menyetujui rencana ini. Pertanyaan terbuka 1: sumber disimpan di `docs/work/BQ-126/`. Pertanyaan terbuka 2: cakupan rilis adalah Rancangan §6 ditambah D-001 sampai D-006; Rancangan §10 poin 16–20 dan pengingat WhatsApp di luar cakupan (D-021). |

| Fase | Tanggal | Hasil |
|---|---|---|
| 0 | 4 Nov 2026 | `product-decisions.md` sudah sesuai format §5: D-001, D-002, D-004, D-005, D-006 menjadi satu baris; D-007 dan D-008 berstatus `Menunggu` dengan catatan. Lampiran `docs/work/BQ-126/rancangan-ulang-portal-owner.md` dan `prototype-portal-owner.html` sudah tersimpan. BQ-127 dibuat. |
| 1 | 4 Nov 2026 | D-009 sampai D-021 tertulis. Menunggu G1. |
| 2 | 4 Nov 2026 | `docs/work/BQ-126/inventaris-fitur.md`: 93 route owner dipetakan, masing-masing tepat satu kali (dicek otomatis terhadap `route:list`). Hasilnya 7 YATIM, 9 fitur lama yang tidak ada di Prototype, 7 kelompok data di modul tersembunyi (termasuk kredensial Midtrans milik owner yang harus dihapus bersama D-016), dan 5 temuan tambahan. |
| 3 | 4 Nov 2026 | `docs/owner-portal.md` ditulis ulang: 17 kontrak halaman (15 menu, panel booking, notifikasi) dengan 76 kriteria terima `AC-*`; matriks peran; gerbang paket; pemetaan semua bagian inventaris §2.1–§2.23; keputusan untuk YATIM-1 sampai YATIM-7; perubahan path dan pengalihan; perilaku saat langganan berakhir. D-007 Revisi 1 ditulis. Menunggu G2. |
