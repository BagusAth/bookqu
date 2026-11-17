/* Data statis satu bisnis. "Hari ini" = Senin, 16 Nov 2026, pukul 10.24 WIB. */
window.BQ = window.BQ || {};
BQ.pages = {};
BQ.aksi = {};

BQ.data = {
  kini: { tanggal: 'Senin, 16 November 2026', pendek: '16 Nov 2026', jam: '10.24' },
  bisnis: { nama: 'Brama Studio Creative', jenis: 'Studio kreatif', link: 'bookqu.id/brama-studio', paketSampai: '2 Mei 2027', slotSampai: '14 Des' },
  pengguna: {
    pemilik: { nama: 'Brama Aditya', peran: 'Pemilik' },
    manajer: { nama: 'Sinta Maharani', peran: 'Manajer' },
    kasir: { nama: 'Dimas Prakoso', peran: 'Kasir' },
  },

  layanan: [
    { id: 'L1', nama: 'Studio Foto Basic', durasi: 60, harga: 150000, unit: ['U4'], kategori: 'Fotografi' },
    { id: 'L2', nama: 'Studio Foto Tematik VIP', durasi: 120, harga: 350000, unit: ['U4', 'U5', 'U6'], kategori: 'Fotografi' },
    { id: 'L3', nama: 'Graduation & Family Photoshoot', durasi: 90, harga: 500000, unit: ['U5', 'U6'], kategori: 'Fotografi' },
    { id: 'L4', nama: 'Studio Podcast Multi-Mic', durasi: 120, harga: 300000, unit: ['U3'], kategori: 'Audio' },
    { id: 'L5', nama: 'Voice Over & Dubbing', durasi: 60, harga: 200000, unit: ['U3'], kategori: 'Audio' },
    { id: 'L6', nama: 'Studio Video Green Screen 4K', durasi: 120, harga: 450000, unit: ['U1'], kategori: 'Videografi' },
    { id: 'L7', nama: 'Creative Music Video (Half Day)', durasi: 240, harga: 1500000, unit: ['U1', 'U6'], kategori: 'Videografi' },
    { id: 'L8', nama: 'Product & Catalog Shoot', durasi: 120, harga: 600000, unit: ['U4'], kategori: 'Komersial' },
    { id: 'L9', nama: 'Live Streaming Broadcast Hub', durasi: 180, harga: 850000, unit: ['U2'], kategori: 'Siaran' },
    { id: 'L10', nama: 'Webinar & Corporate Broadcast', durasi: 120, harga: 750000, unit: ['U2'], kategori: 'Siaran' },
  ],

  unit: [
    { id: 'U1', nama: 'Green Screen Stage 4K', jenis: 'Panggung video', kapasitas: 12 },
    { id: 'U2', nama: 'Live Streaming Control Room', jenis: 'Ruang siaran', kapasitas: 6 },
    { id: 'U3', nama: 'Podcast Suite 1', jenis: 'Ruang kedap suara', kapasitas: 4 },
    { id: 'U4', nama: 'Studio Foto A', jenis: 'Cyclorama putih', kapasitas: 10 },
    { id: 'U5', nama: 'Studio Foto B', jenis: 'Ruang tematik', kapasitas: 8 },
    { id: 'U6', nama: 'VIP Lounge', jenis: 'Ruang rias', kapasitas: 6 },
  ],

  /* status: pending | paid | completed | cancelled | no_show.  fase: lewat | kini | nanti */
  hariIni: [
    { kode: 'BKG-7KQ2MX', mulai: '08.00', selesai: '09.00', nama: 'Irma Suryani', hp: '0812-3456-7809', layanan: 'L1', unit: 'U4', status: 'paid', fase: 'lewat', bayar: 150000, metode: 'QRIS', sumber: 'Online' },
    { kode: 'BKG-2XL1WV', mulai: '09.00', selesai: '11.00', nama: 'Umar Wirahadi', hp: '0812-3456-7820', layanan: 'L10', unit: 'U2', status: 'paid', fase: 'kini', bayar: 750000, metode: 'Transfer bank', sumber: 'Online' },
    { kode: 'BKG-CFWK9P', mulai: '11.00', selesai: '13.00', nama: 'Maya Wulan', hp: '0812-3456-7813', layanan: 'L4', unit: 'U3', status: 'pending', fase: 'nanti', bayar: 0, tagihan: 300000, batas: '10.36', sisa: '12 mnt', sumber: 'Online' },
    { kode: 'BKG-TNK3EE', mulai: '14.00', selesai: '16.00', nama: 'Arif Hakim', hp: '0812-3456-7801', layanan: 'L6', unit: 'U1', status: 'paid', fase: 'nanti', bayar: 450000, metode: 'E-wallet', sumber: 'Online' },
    { kode: 'BKG-NADTHW', mulai: '16.00', selesai: '17.30', nama: 'Putra Permana', hp: '0812-3456-7816', layanan: 'L3', unit: 'U5', status: 'paid', fase: 'nanti', bayar: 425000, promo: ['BRAMA15', 75000], metode: 'Tunai', sumber: 'Dicatat kasir' },
    { kode: 'BKG-LQZQG3', mulai: '18.00', selesai: '22.00', nama: 'Nadia Safira', hp: '0812-3456-7814', layanan: 'L7', unit: 'U1', status: 'paid', fase: 'nanti', bayar: 1500000, metode: 'QRIS', sumber: 'Online' },
  ],
  slotKosongHariIni: [
    { mulai: '13.00', selesai: '15.00', layanan: 'L8', unit: 'U4' },
    { mulai: '15.00', selesai: '16.00', layanan: 'L5', unit: 'U3' },
  ],

  perluAnda: [
    { teks: 'Sesi lewat belum ditutup', jumlah: 2, ke: '#/booking/perlu' },
    { teks: 'Refund menunggu keputusan', jumlah: 1, ke: '#/booking/perlu' },
  ],

  pelanggan: [
    { nama: 'Putra Permana', hp: '0812-3456-7816', booking: 24, total: 11050000, terakhir: '16 Nov', segmen: 'Tetap' },
    { nama: 'Hendi Hermawan', hp: '0812-3456-7808', booking: 22, total: 12700000, terakhir: '28 Okt', segmen: 'Tetap' },
    { nama: 'Maya Wulan', hp: '0812-3456-7813', booking: 18, total: 7500000, terakhir: '15 Nov', segmen: 'Tetap' },
    { nama: 'Umar Wirahadi', hp: '0812-3456-7820', booking: 16, total: 4400000, terakhir: '16 Nov', segmen: 'Tetap' },
    { nama: 'Raka Pradipta', hp: '0813-9000-1122', booking: 1, total: 350000, terakhir: '12 Nov', segmen: 'Baru' },
    { nama: 'Oki Setiana', hp: '0812-3456-7815', booking: 11, total: 3500000, terakhir: '6 Sep', segmen: 'Lama tidak datang' },
  ],
};

/* ---------- Langkah 2: alur harian ---------- */
Object.assign(BQ.data.bisnis, { email: 'halo@bramastudio.id', hp: '0812-9988-7766' });

/* Hitungan tab Booking: 3 + 42 + 1 + 210 + 15 = 271 */
BQ.data.tabBooking = { perlu: 3, mendatang: 42, menunggu: 1, selesai: 210, batal: 15, semua: 271 };

/* Contoh baris di luar hari ini. tgl = tanggal sesi. perlu = alasan muncul di tab Perlu tindakan */
BQ.data.lain = [
  { kode: 'BKG-HQ81ZD', tgl: '15 Nov', mulai: '19.00', selesai: '21.00', nama: 'Hendi Hermawan', layanan: 'L9', unit: 'U2', status: 'paid', fase: 'lewat', bayar: 850000, metode: 'QRIS', sumber: 'Online', perlu: 'Sesi lewat, belum ditutup' },
  { kode: 'BKG-R4KA7P', tgl: '18 Nov', mulai: '10.00', selesai: '12.00', nama: 'Raka Pradipta', layanan: 'L2', unit: 'U5', status: 'paid', fase: 'nanti', bayar: 350000, metode: 'E-wallet', sumber: 'Online', perlu: 'Minta refund', refund: { alasan: 'Jadwal wisuda dimundurkan kampus.', diminta: '16 Nov, 08.12' } },
  { kode: 'BKG-9WDX2M', tgl: '17 Nov', mulai: '09.00', selesai: '11.00', nama: 'Dewi Lestari', layanan: 'L8', unit: 'U4', status: 'paid', fase: 'nanti', bayar: 600000, metode: 'Transfer bank', sumber: 'Online', peserta: 2 },
  { kode: 'BKG-K2P0LS', tgl: '17 Nov', mulai: '13.00', selesai: '15.00', nama: 'Hendi Hermawan', layanan: 'L4', unit: 'U3', status: 'paid', fase: 'nanti', bayar: 300000, metode: 'QRIS', sumber: 'Online' },
  { kode: 'BKG-V7TQ4N', tgl: '17 Nov', mulai: '15.00', selesai: '16.30', nama: 'Siti Rahma', layanan: 'L3', unit: 'U6', status: 'paid', fase: 'nanti', bayar: 500000, metode: 'Tunai', sumber: 'Dicatat kasir', peserta: 5 },
  { kode: 'BKG-B3MC8E', tgl: '17 Nov', mulai: '19.00', selesai: '21.00', nama: 'Bayu Saputra', layanan: 'L6', unit: 'U1', status: 'paid', fase: 'nanti', bayar: 450000, metode: 'QRIS', sumber: 'Online' },
  { kode: 'BKG-Z5YH1R', tgl: '19 Nov', mulai: '09.00', selesai: '12.00', nama: 'Putra Permana', layanan: 'L9', unit: 'U2', status: 'paid', fase: 'nanti', bayar: 850000, metode: 'Transfer bank', sumber: 'Online' },
  { kode: 'BKG-F6NL3Q', tgl: '15 Nov', mulai: '13.00', selesai: '14.00', nama: 'Maya Wulan', layanan: 'L5', unit: 'U3', status: 'completed', fase: 'lewat', bayar: 200000, metode: 'QRIS', sumber: 'Online' },
  { kode: 'BKG-J8UE6W', tgl: '15 Nov', mulai: '10.00', selesai: '12.00', nama: 'Oki Setiana', layanan: 'L2', unit: 'U4', status: 'no_show', fase: 'lewat', bayar: 350000, metode: 'E-wallet', sumber: 'Online' },
  { kode: 'BKG-P1GR5T', tgl: '14 Nov', mulai: '16.00', selesai: '18.00', nama: 'Umar Wirahadi', layanan: 'L10', unit: 'U2', status: 'completed', fase: 'lewat', bayar: 750000, metode: 'Transfer bank', sumber: 'Online' },
  { kode: 'BKG-X0CB9H', tgl: '14 Nov', mulai: '09.00', selesai: '10.00', nama: 'Lina Marlina', layanan: 'L1', unit: 'U4', status: 'cancelled', fase: 'lewat', bayar: 0, sumber: 'Online', batal: 'Dibatalkan otomatis: batas bayar lewat (sistem)' },
  { kode: 'BKG-E2SA4K', tgl: '13 Nov', mulai: '14.00', selesai: '16.00', nama: 'Fajar Nugroho', layanan: 'L6', unit: 'U1', status: 'cancelled', fase: 'lewat', bayar: 450000, metode: 'QRIS', sumber: 'Online', batal: 'Dibatalkan oleh Brama Aditya (Pemilik), refund penuh' },
  { kode: 'BKG-M2T5QX', tgl: '21 Nov', mulai: '10.00', selesai: '12.00', nama: 'Tiara Putri', layanan: 'L2', unit: 'U4', status: 'paid', fase: 'nanti', bayar: 700000, metode: 'QRIS', sumber: 'Online', sesi: [['Sabtu, 21 Nov 2026', '10.00–12.00'], ['Sabtu, 28 Nov 2026', '10.00–12.00']] },
];
BQ.data.hariIni.forEach(b => (b.tgl = '16 Nov'));
BQ.data.hariIni[0].perlu = 'Sesi lewat, belum ditutup';
BQ.data.semua = [...BQ.data.hariIni, ...BQ.data.lain];

BQ.data.besok = { tanggal: 'Selasa, 17 Nov', jumlah: 4, pertama: '09.00' };

/* Slot kosong hari ini untuk kisi hari (selain slotKosongHariIni yang sudah dipakai rel) */
BQ.data.slotLain = [
  { mulai: '17.00', selesai: '18.00', layanan: 'L1', unit: 'U4' },
  { mulai: '19.00', selesai: '21.00', layanan: 'L10', unit: 'U2' },
  { mulai: '13.30', selesai: '15.00', layanan: 'L3', unit: 'U6' },
  { mulai: '18.00', selesai: '20.00', layanan: 'L4', unit: 'U3' },
];
BQ.data.slotTutup = [{ mulai: '08.00', selesai: '12.00', layanan: 'L3', unit: 'U6', alasan: 'Perawatan lampu' }];

/* Minggu 16–22 Nov: [jam, nama, status] */
BQ.data.minggu = [
  { hari: 'Sen', tgl: 16, kini: true, b: BQ.data.hariIni.map(x => [x.mulai, x.nama, x.status]) },
  { hari: 'Sel', tgl: 17, b: [['09.00', 'Dewi Lestari', 'paid'], ['13.00', 'Hendi Hermawan', 'paid'], ['15.00', 'Siti Rahma', 'paid'], ['19.00', 'Bayu Saputra', 'paid']] },
  { hari: 'Rab', tgl: 18, b: [['10.00', 'Raka Pradipta', 'paid'], ['14.00', 'Citra Ayu', 'pending']] },
  { hari: 'Kam', tgl: 19, b: [['09.00', 'Putra Permana', 'paid'], ['13.00', 'Gilang Ramadhan', 'paid'], ['16.00', 'Nadia Safira', 'paid']] },
  { hari: 'Jum', tgl: 20, b: [['10.00', 'Wulan Sari', 'paid']] },
  { hari: 'Sab', tgl: 21, b: [['08.00', 'Hendi Hermawan', 'paid'], ['10.00', 'Tiara Putri', 'paid'], ['13.00', 'Yoga Pratama', 'paid'], ['15.00', 'Maya Wulan', 'paid'], ['18.00', 'Rizky Akbar', 'paid']] },
  { hari: 'Min', tgl: 22, tutup: true, b: [] },
];

/* Pelanggan: 20 total; tampil 12 */
BQ.data.pelanggan = [
  { nama: 'Putra Permana', hp: '0812-3456-7816', email: 'putra.permana@gmail.com', booking: 24, total: 11050000, terakhir: '16 Nov', berikut: '19 Nov, 09.00', segmen: 'Tetap', batal: 1, absen: 0, catatan: 'Suka backdrop krem. Biasanya datang 15 menit lebih awal.' },
  { nama: 'Hendi Hermawan', hp: '0812-3456-7808', email: 'hendi.h@yahoo.com', booking: 22, total: 12700000, terakhir: '15 Nov', berikut: '17 Nov, 13.00', segmen: 'Tetap', batal: 0, absen: 0 },
  { nama: 'Maya Wulan', hp: '0812-3456-7813', email: 'mayawulan@gmail.com', booking: 18, total: 7500000, terakhir: '15 Nov', berikut: '16 Nov, 11.00', segmen: 'Tetap', batal: 2, absen: 0 },
  { nama: 'Umar Wirahadi', hp: '0812-3456-7820', email: 'umar@wirahadi.co.id', booking: 16, total: 4400000, terakhir: '16 Nov', berikut: '', segmen: 'Tetap', batal: 0, absen: 0 },
  { nama: 'Nadia Safira', hp: '0812-3456-7814', email: 'nadia.safira@gmail.com', booking: 9, total: 6200000, terakhir: '2 Nov', berikut: '16 Nov, 18.00', segmen: 'Tetap', batal: 0, absen: 1 },
  { nama: 'Arif Hakim', hp: '0812-3456-7801', email: 'arifhakim@gmail.com', booking: 7, total: 2800000, terakhir: '9 Nov', berikut: '16 Nov, 14.00', segmen: 'Tetap', batal: 1, absen: 0 },
  { nama: 'Dewi Lestari', hp: '0857-2211-3344', email: 'dewi.lestari@gmail.com', booking: 3, total: 1650000, terakhir: '1 Nov', berikut: '17 Nov, 09.00', segmen: 'Tetap', batal: 0, absen: 0 },
  { nama: 'Raka Pradipta', hp: '0813-9000-1122', email: 'raka.p@gmail.com', booking: 1, total: 350000, terakhir: '12 Nov', berikut: '18 Nov, 10.00', segmen: 'Baru', batal: 0, absen: 0 },
  { nama: 'Siti Rahma', hp: '0821-7788-9900', email: '', booking: 2, total: 800000, terakhir: '20 Okt', berikut: '17 Nov, 15.00', segmen: 'Baru', batal: 0, absen: 0 },
  { nama: 'Oki Setiana', hp: '0812-3456-7815', email: 'oki.setiana@gmail.com', booking: 11, total: 3500000, terakhir: '6 Sep', berikut: '', segmen: 'Lama tidak datang', batal: 1, absen: 1 },
  { nama: 'Lina Marlina', hp: '0838-1122-4455', email: 'lina.marlina@gmail.com', booking: 5, total: 1250000, terakhir: '28 Agu', berikut: '', segmen: 'Lama tidak datang', batal: 1, absen: 0 },
  { nama: 'Fajar Nugroho', hp: '0856-4433-2211', email: 'fajar.n@gmail.com', booking: 4, total: 1800000, terakhir: '13 Sep', berikut: '', segmen: 'Lama tidak datang', batal: 1, absen: 0 },
];
BQ.data.ringkasPelanggan = { total: 20, baru: 2, tetap: 14, lama: 4 };

BQ.rp = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
BQ.booking = kode => BQ.data.semua.find(b => b.kode === kode);
BQ.pel = nama => BQ.data.pelanggan.find(p => p.nama === nama) || { nama, hp: '0812-0000-0000', email: '', booking: 1, total: 0, terakhir: '-', berikut: '', segmen: 'Baru', batal: 0, absen: 0 };
BQ.layanan = id => BQ.data.layanan.find(l => l.id === id) || {};
BQ.unit = id => BQ.data.unit.find(u => u.id === id) || {};

/* ---------- Langkah 3: penyiapan ---------- */
(function () {
  const x = {
    L1: { slotSampai: '14 Des', kapasitas: 1, populer: true, gambar: 'foto' },
    L2: { slotSampai: '14 Des', kapasitas: 1, gambar: 'foto' },
    L3: { slotSampai: '14 Des', kapasitas: 8, populer: true, gambar: 'foto' },
    L4: { slotSampai: '14 Des', kapasitas: 4, gambar: 'audio' },
    L5: { slotSampai: '14 Des', kapasitas: 1 },
    L6: { slotSampai: '14 Des', kapasitas: 1, gambar: 'video' },
    L7: { slotSampai: '20 Nov', kapasitas: 1, manual: true },
    L8: { slotSampai: '14 Des', kapasitas: 1 },
    L9: { slotSampai: '14 Des', kapasitas: 6, aktif: false },
    L10: { slotSampai: '14 Des', kapasitas: 6 },
  };
  BQ.data.layanan.forEach(l => Object.assign(l, { aktif: true, satuan: 'per sesi' }, x[l.id]));
})();

BQ.data.kategori = [
  { nama: 'Fotografi', ket: 'Sesi foto di studio', warna: 'terong', jumlah: 3, aktif: true },
  { nama: 'Audio', ket: 'Podcast dan rekaman suara', warna: 'daun', jumlah: 2, aktif: true },
  { nama: 'Videografi', ket: 'Produksi video', warna: 'bata', jumlah: 2, aktif: true },
  { nama: 'Komersial', ket: 'Foto produk dan katalog', warna: 'tinta', jumlah: 1, aktif: true },
  { nama: 'Siaran', ket: 'Live streaming dan webinar', warna: 'redup', jumlah: 2, aktif: true },
];

BQ.data.addon = [
  { nama: 'Makeup artist', harga: 250000, stok: null, layanan: 3, terjual: 18, aktif: true },
  { nama: 'Lensa Sony 85mm f/1.4', harga: 100000, stok: 0, layanan: 2, terjual: 7, aktif: true },
  { nama: 'Cetak foto 10R (5 lembar)', harga: 75000, stok: 40, layanan: 3, terjual: 22, aktif: true },
  { nama: 'Teleprompter', harga: 150000, stok: 2, layanan: 2, terjual: 5, aktif: true },
  { nama: 'Editing video dasar', harga: 300000, stok: null, layanan: 3, terjual: 9, aktif: false },
];

Object.assign(BQ.data.unit.find(u => u.id === 'U1'), { lokasi: 'Lantai 1', pakai: 6 });
Object.assign(BQ.data.unit.find(u => u.id === 'U2'), { lokasi: 'Lantai 2', pakai: 2 });
Object.assign(BQ.data.unit.find(u => u.id === 'U3'), { lokasi: 'Lantai 2', pakai: 2 });
Object.assign(BQ.data.unit.find(u => u.id === 'U4'), { lokasi: 'Lantai 1', pakai: 1 });
Object.assign(BQ.data.unit.find(u => u.id === 'U5'), { lokasi: 'Lantai 1', pakai: 1.5 });
Object.assign(BQ.data.unit.find(u => u.id === 'U6'), { lokasi: 'Lantai 3', pakai: 0, ditutup: 4 });
BQ.data.jamBukaHari = 14;
BQ.data.konflik = {
  mendatang: [{ tgl: '21 Nov', unit: 'U4', a: ['10.00–12.00', 'Tiara Putri', 'Studio Foto Tematik VIP'], b: ['11.00–12.00', 'Yoga Pratama', 'Studio Foto Basic'] }],
  lampau: 4,
};

BQ.data.jamBuka = [
  { hari: 'Senin', buka: true, rentang: [['08.00', '12.00'], ['13.00', '22.00']] },
  { hari: 'Selasa', buka: true, rentang: [['08.00', '22.00']] },
  { hari: 'Rabu', buka: true, rentang: [['08.00', '22.00']] },
  { hari: 'Kamis', buka: true, rentang: [['08.00', '22.00']] },
  { hari: 'Jumat', buka: true, rentang: [['08.00', '11.30'], ['13.00', '22.00']] },
  { hari: 'Sabtu', buka: true, rentang: [['07.00', '22.00']] },
  { hari: 'Minggu', buka: false },
];
BQ.data.jamRamai = [
  { nama: 'Malam kerja', hari: 'Sen–Jum', jam: '18.00–22.00', tambah: 50000, layanan: 'Semua layanan' },
  { nama: 'Pagi akhir pekan', hari: 'Sab', jam: '08.00–12.00', tambah: 75000, layanan: '3 layanan foto' },
];
BQ.data.tanggalKhusus = [
  { tgl: '24 Des 2026', harga: 'Harga dasar + 25%', layanan: 'Semua layanan' },
  { tgl: '31 Des 2026', harga: 'Rp1.000.000', layanan: 'Live Streaming Broadcast Hub' },
];
BQ.data.libur = [
  { tgl: 'Minggu, setiap minggu', alasan: 'Libur mingguan (dari jam buka)', tetap: true },
  { tgl: '25 Des 2026', alasan: 'Natal', booking: 0 },
  { tgl: '1 Jan 2027', alasan: 'Tahun Baru', booking: 0 },
  { tgl: '5 Des 2026', alasan: 'Perawatan lampu studio', booking: 0 },
];
/* Slot Studio Podcast Multi-Mic, 16–18 Nov. st: kosong | terisi | tutup | lewat */
BQ.data.slotMinggu = [
  ['16 Nov', '08.00–10.00', 300000, 'lewat'], ['16 Nov', '11.00–13.00', 300000, 'terisi', 'Maya Wulan'], ['16 Nov', '13.00–15.00', 300000, 'kosong'], ['16 Nov', '18.00–20.00', 350000, 'kosong', null, 'Malam kerja +Rp50.000'],
  ['17 Nov', '09.00–11.00', 300000, 'kosong'], ['17 Nov', '13.00–15.00', 300000, 'terisi', 'Hendi Hermawan'], ['17 Nov', '15.00–17.00', 300000, 'tutup'], ['17 Nov', '18.00–20.00', 350000, 'kosong', null, 'Malam kerja +Rp50.000'],
  ['18 Nov', '09.00–11.00', 300000, 'kosong'], ['18 Nov', '11.00–13.00', 250000, 'kosong', null, 'Harga diubah untuk slot ini'],
];

/* ---------- Langkah 4: uang ---------- */
BQ.data.saldo = { tersedia: 18500000, proses: 2000000, dicairkan: 41250000, min: 50000, maks: 25000000 };
BQ.data.statusBayar = { sukses: 197, menunggu: 1, gagal: 6, refund: 2 };
BQ.data.transaksi = [
  ['ORD-260116-4KQ2', '16 Nov, 10.23', 'Umar Wirahadi', 'L10', 750000, 'Transfer bank', 'sukses'],
  ['ORD-260116-9XL1', '16 Nov, 10.21', 'Maya Wulan', 'L4', 300000, 'QRIS', 'pending'],
  ['ORD-260116-2HT8', '16 Nov, 07.12', 'Irma Suryani', 'L1', 150000, 'QRIS', 'sukses'],
  ['ORD-260115-7MM3', '15 Nov, 21.40', 'Nadia Safira', 'L7', 1500000, 'QRIS', 'sukses'],
  ['ORD-260115-3PD9', '15 Nov, 18.02', 'Citra Ayu', 'L2', 350000, 'E-wallet', 'gagal'],
  ['ORD-260114-8RZ4', '14 Nov, 10.23', 'Raka Pradipta', 'L2', 350000, 'E-wallet', 'sukses'],
  ['ORD-260113-1QA6', '13 Nov, 09.15', 'Fajar Nugroho', 'L6', 450000, 'QRIS', 'refund'],
  ['ORD-260112-5WB2', '12 Nov, 16.44', 'Arif Hakim', 'L6', 450000, 'E-wallet', 'sukses'],
];
BQ.data.mutasi = [
  ['16 Nov', 'Pembayaran booking', 'BKG-2XL1WV', 750000, 18500000],
  ['16 Nov', 'Pembayaran booking', 'BKG-7KQ2MX', 150000, 17750000],
  ['15 Nov', 'Pembayaran booking', 'BKG-LQZQG3', 1500000, 17600000],
  ['14 Nov', 'Pencairan ke BCA •••• 4421', 'PCR-0031', -2000000, 16100000],
  ['13 Nov', 'Refund ke pelanggan', 'BKG-E2SA4K', -450000, 18100000],
  ['12 Nov', 'Pembayaran booking', 'BKG-W3EE71', 450000, 18550000],
];
BQ.data.pencairan = [
  ['PCR-0031', '14 Nov', 2000000, 'BCA •••• 4421', 'proses', 'Disetujui, menunggu transfer'],
  ['PCR-0030', '1 Nov', 10000000, 'BCA •••• 4421', 'berhasil', 'Ditransfer 2 Nov'],
  ['PCR-0029', '18 Okt', 7500000, 'Mandiri •••• 0087', 'gagal', 'Nama rekening tidak cocok'],
  ['PCR-0028', '3 Okt', 12000000, 'BCA •••• 4421', 'berhasil', 'Ditransfer 4 Okt'],
];
BQ.data.rekening = [
  { bank: 'BCA', no: '•••• 4421', nama: 'Brama Aditya', utama: true },
  { bank: 'Mandiri', no: '•••• 0087', nama: 'CV Brama Studio Kreatif' },
];
/* Pendapatan bersih per hari (ribu rupiah), 18 Okt–16 Nov. 16 hari terakhir = 13.600 = Laporan; hari ini = 900 = transaksi 16 Nov */
BQ.data.pendapatan30 = [0, 1350, 350, 600, 1350, 1400, 550, 900, 0, 400, 900, 1200, 300, 650, 400, 750, 600, 450, 1200, 950, 600, 0, 1100, 850, 700, 1500, 900, 1300, 1400, 900];
/* Pendapatan kotor per layanan 1–16 Nov: jumlah Rp14.050.000 (− refund Rp450.000 = bersih Rp13.600.000); booking 32 */
BQ.data.perLayanan = [['Webinar & Corporate Broadcast', 2250000, 3], ['Studio Podcast Multi-Mic', 1800000, 6], ['Live Streaming Broadcast Hub', 1700000, 2], ['Creative Music Video (Half Day)', 1500000, 1], ['Graduation & Family Photoshoot', 1500000, 3], ['Studio Foto Tematik VIP', 1400000, 4], ['Studio Video Green Screen 4K', 1350000, 3], ['Product & Catalog Shoot', 1200000, 2], ['Studio Foto Basic', 750000, 5], ['Voice Over & Dubbing', 600000, 3]];
BQ.data.periode = { booking: 32, slot: 87, terisi: '36,8%', bersih: 13600000, kotor: 14050000, refund: 450000 };
BQ.data.sumber = [['Instagram', 21], ['WhatsApp', 16], ['Dicatat kasir', 9], ['Google Maps', 7], ['Kode QR', 4]];
BQ.data.gagalBayar = [
  { nama: 'Citra Ayu', hp: '0812-7788-1100', layanan: 'Studio Foto Tematik VIP', kapan: '15 Nov, 18.02', alasan: 'Batas bayar lewat' },
  { nama: 'Yudha Prasetyo', hp: '0857-1234-9090', layanan: 'Studio Podcast Multi-Mic', kapan: '12 Nov, 20.15', alasan: 'Pembayaran ditolak bank' },
  { nama: 'Mira Kusuma', hp: '0813-4455-6677', layanan: 'Studio Foto Basic', kapan: '9 Nov, 11.30', alasan: 'Batas bayar lewat' },
];
BQ.data.promo = [
  { kode: 'BRAMA15', nilai: '15%', maks: 'maks Rp100.000', pakai: 38, kuota: 200, masa: '1 Nov – 31 Des 2026', untuk: 'Semua layanan', st: 'aktif' },
  { kode: 'WISUDA50K', nilai: 'Rp50.000', maks: 'min. belanja Rp400.000', pakai: 47, kuota: 50, masa: '1 Okt – 30 Nov 2026', untuk: 'Graduation & Family Photoshoot', st: 'aktif' },
  { kode: 'PODCASTPAGI', nilai: '20%', maks: 'maks Rp75.000', pakai: 6, kuota: 30, masa: '1 Nov – 31 Jan 2027', untuk: 'Studio Podcast Multi-Mic', st: 'aktif' },
  { kode: 'AKHIRTAHUN', nilai: '25%', maks: 'maks Rp200.000', pakai: 0, kuota: 100, masa: '20 Des – 31 Des 2026', untuk: 'Semua layanan', st: 'terjadwal' },
  { kode: 'MERDEKA17', nilai: '17%', maks: 'maks Rp170.000', pakai: 64, kuota: 100, masa: '1 – 31 Agu 2026', untuk: 'Semua layanan', st: 'berakhir' },
];
BQ.data.ulasan = [
  { nama: 'Hendi Hermawan', bintang: 5, layanan: 'Live Streaming Broadcast Hub', tgl: '15 Nov', teks: 'Operator sigap, koneksi stabil sampai akhir acara. Ruang kontrolnya nyaman.', balas: null, kode: 'BKG-HQ81ZD' },
  { nama: 'Oki Setiana', bintang: 3, layanan: 'Studio Foto Tematik VIP', tgl: '8 Sep', teks: 'Hasil foto bagus, tapi sesi mulai terlambat 20 menit karena studio masih dipakai.', balas: null, kode: 'BKG-J8UE6W' },
  { nama: 'Putra Permana', bintang: 5, layanan: 'Graduation & Family Photoshoot', tgl: '2 Nov', teks: 'Fotografer sabar menghadapi anak-anak. Backdrop banyak pilihan.', balas: 'Terima kasih, Kak Putra! Sampai jumpa di sesi berikutnya.' },
  { nama: 'Maya Wulan', bintang: 5, layanan: 'Studio Podcast Multi-Mic', tgl: '28 Okt', teks: 'Kedap suaranya juara. Mic lengkap.', balas: 'Terima kasih, Kak Maya. Senang podcastnya lancar.' },
  { nama: 'Lina Marlina', bintang: 2, layanan: 'Studio Foto Basic', tgl: '20 Agu', teks: 'Tempat parkir sempit dan AC kurang dingin.', balas: 'Mohon maaf, Kak Lina. AC sudah kami ganti bulan September.', sembunyi: true },
];
/* (17×5 + 4×4 + 1×3 + 1×2) / 23 = 4,61 */
BQ.data.rating = { rata: '4,6', jumlah: 23, sebaran: [17, 4, 1, 1, 0] };
