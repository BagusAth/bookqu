/* Beranda: "Apa yang harus saya tahu dan kerjakan hari ini?" (§8.1, review 8.1) */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;

  function bisnisBaru() {
    return `<div class="view-in">
      ${ui.kepala({ judul: `Selamat datang, ${D.pengguna.pemilik.nama.split(' ')[0]}`, tujuan: 'Empat langkah sampai pelanggan bisa booking sendiri. Setiap langkah tercentang otomatis dari data Anda.' })}
      ${ui.panel({ judul: 'Siapkan bisnis Anda', rata: true, isi: ui.langkah([
        { st: 'selesai', judul: 'Lengkapi profil bisnis', ket: 'Nama, jenis, nomor HP dan alamat.' },
        { st: 'kini', judul: 'Tambah layanan pertama', ket: 'Yang dipesan pelanggan, dengan harga dan durasi.', aksi: ui.btn('Tambah layanan', { v: 'utama', kecil: true, href: '#/layanan' }) },
        { judul: 'Atur jam buka', ket: 'Slot dibuka otomatis dari jam buka.' },
        { judul: 'Bagikan link booking', ket: 'Lewat WhatsApp, Instagram atau Google Maps.' },
      ]) })}
      ${ui.panel({ isi: ui.kosong({ ikon: 'kalender', judul: 'Belum ada booking hari ini', teks: 'Jadwal hari ini muncul di sini setelah booking pertama masuk.' }) })}
    </div>`;
  }

  BQ.pages.beranda = {
    render({ S }) {
      if (S.state === 'baru') return bisnisBaru();
      const kasir = S.role === 'kasir';
      /* AC-BRD-02: jumlah pembayaran berhasil hari ini, dari transaksi */
      const lunas = D.transaksi.filter(t => t[1].startsWith('16 Nov') && t[6] === 'sukses').reduce((n, t) => n + t[4], 0);
      const banner = S.state === 'berakhir'
        ? ui.banner({ nada: 'bahaya', judul: 'Langganan berakhir 2 hari lalu', teks: 'Booking yang ada tetap bisa diurus. Booking baru tidak diterima.', aksi: S.role === 'pemilik' ? ui.btn('Perpanjang', { kecil: true, href: '#/paket' }) : '' })
        /* prioritas banner §8.1: layanan tanpa slot terbuka dalam 7 hari (Kasir tidak bisa membuka slot) */
        : kasir ? '' : ui.banner({ nada: 'perlu', judul: 'Creative Music Video (Half Day): slot hanya sampai 20 Nov', teks: 'Kurang dari 7 hari lagi. Pelanggan tidak bisa booking layanan ini setelah tanggal itu.', aksi: ui.btn('Buka slot', { kecil: true, href: '#/jam-buka/slot' }) }) + ui.cat('A', '§8.1 prioritas banner');

      const perlu = ui.panel({
        judul: 'Perlu Anda', cat: ui.cat('B', '8.1 no. 3'), rata: true,
        isi: D.perluAnda.filter(p => !kasir || !p.teks.startsWith('Refund')).map(p => ui.lr({ awal: `<span class="hitung">${p.jumlah}</span>`, judul: p.teks, akhir: ui.ikon('kanan', 18), aksi: 'ke', arg: p.ke })).join(''),
      });

      const angka = ui.panel({
        isi: ui.angka({
          utama: { nilai: String(D.hariIni.length), label: 'booking hari ini', ket: `Besok ${D.besok.jumlah} booking, pertama ${D.besok.pertama}` },
          lain: [
            { nilai: '1', label: 'menunggu bayar' },
            ...(kasir ? [] : [{ nilai: rp(lunas), label: 'masuk hari ini' }]),
            { nilai: '6 dari 8', label: 'slot terisi' },
          ],
        }) + `<div class="cat-baris">${ui.cat('B', '8.1 no. 4')}</div>`,
      });

      const jadwal = ui.panel({
        judul: 'Jadwal hari ini', ket: D.kini.tanggal, cat: ui.cat('A', '8.1 no. 5'),
        aksi: ui.btn('Buka kalender', { v: 'polos', kecil: true, href: '#/kalender/hari' }),
        isi: S.state === 'memuat' ? ui.kerangka() + ui.kerangka() : `<div class="hanya-desktop">${BQ.bersama.relHari()}</div><div class="hanya-hp">${BQ.bersama.relHari({ ringkas: true })}</div>`,
      });

      const samping = `<div class="tumpuk">
        <div class="hanya-desktop">${ui.panel({ judul: 'Besok', ket: D.besok.tanggal, rata: true, isi: D.minggu[1].b.map(([j, n, s]) => ui.lr({ awal: `<span class="jam">${j}</span>`, judul: esc(n), akhir: ui.status(s) })).join('')
          + `<div class="hal">Slot terbuka sampai <b class="dorong">${D.bisnis.slotSampai}</b></div>` })}</div>
        ${ui.panel({ judul: 'Link booking', isi: `<div class="kartu-kecil">${ui.salin(D.bisnis.link)}<div class="baris">${ui.btn('WhatsApp', { kecil: true, ikon: 'pesan', aksi: 'toast', arg: 'Membuka WhatsApp' })}${ui.btn('Kode QR', { kecil: true, href: '#/halaman-booking' })}</div></div>` })}
        <div class="hanya-desktop">${ui.panel({ judul: 'Aksi cepat', isi: `<div class="baris">${[ui.btn('Catat booking', { kecil: true, ikon: 'tambah', aksi: 'catat' }), kasir ? '' : ui.btn('Tambah layanan', { kecil: true, href: '#/layanan' }), !kasir && S.plan !== 'small' ? ui.btn('Buat promo', { kecil: true, href: '#/promo' }) : '', S.role === 'pemilik' && S.plan !== 'small' ? ui.btn('Undang anggota', { kecil: true, href: '#/pengaturan/tim' }) : ''].join('')}</div>` })}
        </div>
        <div class="hanya-desktop">${ui.panel({ judul: 'Panduan awal', ket: '3 dari 4 langkah selesai', cat: ui.cat('B', '8.1 no. 1'), aksi: ui.btn('Tutup panduan', { v: 'polos', kecil: true, ikon: 'tutup', hanyaIkon: true, aksi: 'toast', arg: 'Panduan disembunyikan' }), isi: `<div class="kartu-kecil">${ui.kuota({ label: 'Berikutnya: bagikan link booking', pakai: 3, batas: 4 })}${ui.btn('Bagikan link', { kecil: true, href: '#/halaman-booking' })}</div>` })}</div>
        <div class="hanya-hp">${ui.panel({ rata: true, isi: ui.lipat('Panduan awal · 3 dari 4 langkah selesai', 'Berikutnya: bagikan link booking ke pelanggan lewat WhatsApp atau kode QR.') })}</div>
      </div>`;

      return `<div class="view-in">
        ${ui.kepala({ judul: 'Beranda', tujuan: `${D.kini.tanggal} · pukul ${D.kini.jam}` })}
        ${banner}
        <div class="beranda">
          <div class="beranda-perlu">${perlu}</div>
          <div class="beranda-angka">${angka}</div>
          <div class="beranda-jadwal">${jadwal}</div>
          <div class="beranda-samping">${samping}</div>
        </div>
      </div>`;
    },
  };

  BQ.aksi = Object.assign(BQ.aksi || {}, { ke: el => { location.hash = el.dataset.arg; } });
})();
