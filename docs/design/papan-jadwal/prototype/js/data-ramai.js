/* Sesi 4 (T4): sakelar Data = Ramai. Skala tenant nyata: 25 layanan, 80 tugas, nama panjang.
   Dimuat setelah data.js; hanya mengubah BQ.data bila sakelar memilih "ramai". */
(function () {
  let S = {};
  try { S = JSON.parse(localStorage.getItem('bq-proto') || '{}'); } catch (e) { /* tanpa penyimpanan pun jalan */ }
  if (S.data !== 'ramai') return;
  const D = BQ.data;

  /* Nama panjang: dua pelanggan diganti di semua tempat */
  const GANTI = { 'Umar Wirahadi': 'Muhammad Fadhil Rizky Pratama Wirahadikusuma', 'Maya Wulan': 'Maya Wulandari Kusumaningrum Hadiwijaya' };
  const jalan = v => typeof v === 'string' ? (GANTI[v] || v) : Array.isArray(v) ? v.map(jalan) : v && typeof v === 'object' ? Object.fromEntries(Object.entries(v).map(([k, x]) => [k, jalan(x)])) : v;
  Object.keys(D).forEach(k => { D[k] = jalan(D[k]); });

  /* 25 layanan (paket Medium sampai 20, Pro tanpa batas) */
  const tambah = [
    ['Prewedding Outdoor Package (Full Day, 2 Lokasi)', 480, 3500000, 'Fotografi'], ['Pas Foto & Foto Ijazah', 30, 75000, 'Fotografi'],
    ['Maternity & Newborn Session', 120, 650000, 'Fotografi'], ['Foto Produk Flat Lay (10 Item)', 90, 400000, 'Komersial'],
    ['Company Profile Video Shoot', 240, 2500000, 'Videografi'], ['Mixing & Mastering Lagu', 180, 900000, 'Audio'],
    ['Rekaman Vokal 1 Jam', 60, 250000, 'Audio'], ['Podcast Video 3 Kamera', 120, 700000, 'Audio'],
    ['Kelas Fotografi Dasar (Grup)', 120, 150000, 'Kelas'], ['Kelas Editing Lightroom (Grup)', 120, 175000, 'Kelas'],
    ['Sewa Studio Kosong per Jam', 60, 200000, 'Sewa'], ['Live Shopping Studio Set', 180, 800000, 'Siaran'],
    ['Wisuda Kelompok (sampai 10 orang)', 60, 600000, 'Fotografi'], ['Konten Reels & TikTok (5 Video)', 120, 950000, 'Videografi'],
    ['Audio Book Narration Session', 180, 750000, 'Audio'],
  ];
  tambah.forEach(([nama, durasi, harga, kategori], i) => D.layanan.push({ id: `L${11 + i}`, nama, durasi, harga, unit: ['U4'], kategori, ...(kategori === 'Kelas' ? { kapasitas: 12 } : {}) }));

  /* 77 sesi lewat yang belum ditutup, ditambah 3 tugas yang ada = 80 */
  const NAMA = ['Ahmad Fauzi', 'Siti Nurhaliza Rahmawati', 'Bagas Prasetyo', 'Dewi Anggraini Puspitasari', 'Rizky Ramadhan', 'Yohana Christina Simanjuntak', 'Agus Salim', 'Fitri Handayani', 'Kevin Wijaya', 'Ni Luh Putu Ayu Sukmawati'];
  for (let i = 0; i < 77; i++) {
    const tgl = `${1 + (i % 15)} Nov`, jam = ['08.00', '10.00', '13.00', '15.00', '19.00'][i % 5];
    const L = D.layanan[i % D.layanan.length];
    D.semua.push({ kode: `BKG-R${String(100 + i)}X`, tgl, mulai: jam, selesai: jam, nama: NAMA[i % NAMA.length], layanan: L.id, unit: L.unit[0], status: 'paid', fase: 'lewat', bayar: L.harga, metode: 'QRIS', sumber: 'Online', perlu: 'Sesi lewat, belum ditutup' });
  }
  D.tabBooking = { perlu: 80, mendatang: 156, menunggu: 9, selesai: 940, batal: 61, semua: 1246 };
  D.perluAnda = D.perluAnda.map(p => p.teks.startsWith('Sesi lewat') ? { ...p, jumlah: 79 } : p);
  D.ramai = true;
})();
