/* Kalender: "Kapan ramai, kapan kosong, siapa datang?" (§8.2, review 8.2, usulan fitur 4) */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, esc = ui.esc;
  /* D-014: slot berkapasitas > 1 menampilkan peserta dari kapasitas */
  const kap = (x, isi) => { const k = BQ.layanan(x.layanan).kapasitas || 1; return k > 1 ? `${isi}/${k} peserta` : `${isi} peserta`; };
  const nadaBlok = x => x.fase === 'kini' ? 'kini' : x.fase === 'lewat' ? 'lewat' : x.status === 'pending' ? 'tunggu' : '';

  /* T4: kolom bawaan = layanan yang punya booking atau slot hari ini, paling banyak 6; sisanya lewat "Pilih layanan". */
  const KOLOM_MAKS = 6;
  const terpakai = () => [...new Set([...D.hariIni, ...D.slotKosongHariIni, ...D.slotLain, ...D.slotTutup].map(x => x.layanan))];
  const KOLOM_HARI = terpakai().slice(0, KOLOM_MAKS);
  BQ.aksi['pilih-kolom'] = () => BQ.lembar({ judul: 'Kolom layanan', isi: `<p class="redup">Paling banyak ${KOLOM_MAKS} kolom supaya nama tetap terbaca. Bawaan: layanan yang punya booking atau slot hari ini.</p><div class="tumpuk rapat">${D.layanan.map(l => ui.centang(l.nama, { on: KOLOM_HARI.includes(l.id) })).join('')}</div>`, kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Tampilkan', { v: 'utama', aksi: 'toast', arg: 'Kolom kalender diperbarui' }) });

  function kisiHari(perUnit) {
    const kolom = perUnit
      ? D.unit.map(u => ({ id: u.id, nama: u.nama, ket: u.jenis }))
      : KOLOM_HARI.map(id => ({ id, nama: BQ.layanan(id).nama, ket: ui.durasi(BQ.layanan(id).durasi) }));
    const kol = x => kolom.findIndex(k => k.id === (perUnit ? x.unit : x.layanan));
    const blok = [];
    D.hariIni.forEach(x => blok.push({
      kol: kol(x), mulai: x.mulai, selesai: x.selesai, nada: nadaBlok(x), aksi: 'slip', arg: x.kode,
      /* nama layanan hanya di judul kolom (review 8.2 no. 1); per unit, layanan jadi baris kedua */
      isi: `<span class="kh-atas"><span class="jam">${x.mulai}</span><b>${esc(x.nama)}</b></span><span>${x.status === 'pending' ? `bayar sebelum ${x.batas}` : perUnit ? esc(BQ.layanan(x.layanan).nama) : kap(x, x.peserta || 1)}</span>`,
    }));
    [...D.slotKosongHariIni, ...D.slotLain].forEach(x => blok.push({ kol: kol(x), mulai: x.mulai, selesai: x.selesai, nada: 'bebas', aksi: 'slot-bebas', arg: `${x.layanan}|${x.mulai}`, isi: `<span class="kh-atas"><span class="jam">${x.mulai}</span><b>Kosong</b></span><span>+ Catat · ${(BQ.layanan(x.layanan).kapasitas || 1) > 1 ? kap(x, 0) + ' · ' : ''}${BQ.rp(BQ.layanan(x.layanan).harga)}</span>` }));
    D.slotTutup.forEach(x => blok.push({ kol: kol(x), mulai: x.mulai, selesai: x.selesai, nada: 'tutup', aksi: 'slot-tutup', isi: `<span>Ditutup</span><span>${esc(x.alasan)}</span>` }));
    return ui.kisiHari({ dari: 8, sampai: 22, kolom, blok: blok.filter(b => b.kol >= 0), kini: D.kini.jam });
  }

  function bulan() {
    const hari = Array.from({ length: 30 }, (_, i) => {
      const n = i + 1;
      if (n === 5) return { n, tutup: 'Tutup: perawatan' };
      if (n === 22 || n === 29) return { n, tutup: 'Tutup: hari Minggu' };
      if (n > 24) return { n };
      const slot = [8, 5, 5, 5, 0, 8, 6][i % 7] || 6;
      return { n, slot, isi: n < 16 ? Math.min(slot, 3 + (n * 7) % 4) : Math.max(0, 5 - Math.floor((n - 15) / 2)), kini: n === 16 };
    });
    return ui.bulan({ geser: 6, hari });
  }

  BQ.pages.kalender = {
    render({ tab, q, S }) {
      tab = tab || 'hari';
      const pro = S.plan === 'pro';
      const perUnit = pro && q.get('kolom') === 'unit';
      const label = { hari: 'Senin, 16 Nov 2026', minggu: '16–22 Nov 2026', bulan: 'November 2026' }[tab];
      const tampilan = ui.segmen([{ label: 'Hari', href: '#/kalender/hari', on: tab === 'hari' }, { label: 'Minggu', href: '#/kalender/minggu', on: tab === 'minggu' }, { label: 'Bulan', href: '#/kalender/bulan', on: tab === 'bulan' }], { label: 'Tampilan' });
      const kolom = tab === 'hari' ? `<span class="hanya-desktop baris">${ui.segmen([{ label: 'Per layanan', href: '#/kalender/hari', on: !perUnit }, { label: pro ? 'Per unit' : 'Per unit (Pro)', href: pro ? '#/kalender/hari?kolom=unit' : '#/paket', on: perUnit }], { label: 'Kolom' })}${ui.cat('C', 'fitur 4')}</span>` : '';

      let isi;
      const tombolKolom = !perUnit ? `<span class="hanya-desktop">${ui.btn(`Layanan ${KOLOM_HARI.length} dari ${D.layanan.length}`, { kecil: true, ikon: 'filter', aksi: 'pilih-kolom' })}</span>` : '';
      if (tab === 'hari') {
        isi = ui.cat('A', '8.2 no. 1') + ui.cat('B', '8.2 no. 5') + (tombolKolom ? `<div class="baris">${tombolKolom}</div>` : '') + `<div class="hanya-desktop">${ui.panel({ rata: true, isi: kisiHari(perUnit) })}</div>`
          + `<div class="hanya-hp">${ui.panel({ isi: BQ.bersama.relHari() })}</div>`;
      } else if (tab === 'minggu') {
        const mg = D.minggu.map(h => ({ ...h, b: h.b.map(x => { const bk = D.semua.find(b => b.nama === x[1] && b.mulai === x[0]); const n = x[1].split(' '); const y = [x[0], n[0] + (n[1] ? ` ${n[1][0]}.` : ''), x[2]]; return bk ? [...y, 'slip', bk.kode] : y; }) }));
        isi = ui.minggu(mg) + `<p class="redup kecil">${ui.cat('A', '8.2 no. 2')} Satu baris per booking: jam, nama, titik status. Klik untuk membuka slip.</p>`;
      } else {
        isi = ui.panel({ ket: 'Batang menunjukkan slot terisi per tanggal. Klik tanggal untuk melihat harinya.', cat: ui.cat('A', '8.2 no. 3'), judul: 'November 2026', isi: bulan() });
      }

      return `<div class="view-in">
        ${ui.kepala({ judul: 'Kalender', tujuan: 'Kapan ramai, kapan kosong, dan siapa yang datang.' })}
        ${ui.alat(tampilan, ui.tglNav(label), `<span class="dorong baris">${kolom}<span class="hanya-desktop">${ui.btn('Atur jam buka', { v: 'polos', kecil: true, href: '#/jam-buka', ikon: 'jam' })}</span>${ui.btn('Filter', { kecil: true, ikon: 'filter', aksi: 'filter-kalender' })}${ui.cat('A', '8.2 no. 4')}</span>`)}
        ${isi}
      </div>`;
    },
  };

  BQ.aksi = Object.assign(BQ.aksi || {}, {
    'slot-bebas': el => {
      const [l, j] = el.dataset.arg.split('|');
      BQ.menu(el, [
        { label: 'Catat booking di slot ini', ikon: 'tambah', aksi: 'catat', arg: `slot|${l}|${j}` },
        { label: 'Ubah harga slot ini', ikon: 'promo', aksi: 'harga-slot' },
        '-',
        { label: 'Tutup slot ini', ikon: 'kunci', aksi: 'tutup-slot' },
      ]);
    },
    'slot-tutup': el => BQ.menu(el, [{ label: 'Buka lagi', ikon: 'kunci', aksi: 'toast', arg: 'Slot dibuka lagi' }]),
    'filter-kalender': () => BQ.lembar({
      judul: 'Filter kalender',
      isi: ui.bidang({ id: 'k1', label: 'Layanan' }, ui.pilih({ id: 'k1', opsi: ['Semua layanan', ...D.layanan.map(l => l.nama)] }))
        + (BQ.S.plan === 'pro' ? ui.bidang({ id: 'k2', label: 'Unit' }, ui.pilih({ id: 'k2', opsi: ['Semua unit', ...D.unit.map(u => u.nama)] })) : '')
        + ui.bidang({ id: 'k3', label: 'Status booking' }, ui.pilih({ id: 'k3', opsi: ['Semua status', 'Menunggu bayar', 'Terkonfirmasi', 'Selesai'] }))
        + `<p class="hanya-hp">${ui.btn('Atur jam buka', { v: 'polos', href: '#/jam-buka', ikon: 'jam' })}</p>`,
      kaki: ui.btn('Hapus filter', {}) + ui.btn('Terapkan', { v: 'utama', aksi: 'tutup' }),
    }),
  });
})();
