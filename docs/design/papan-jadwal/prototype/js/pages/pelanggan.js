/* Pelanggan: "Siapa pelanggan saya, dan bagaimana menghubungi mereka?" (§8.5, review 8.5, usulan fitur 5) */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const nada = s => s === 'Baru' ? 'pilih' : s === 'Tetap' ? 'netral' : 'tunggu';
  const SEG = [
    { k: 'semua', label: 'Semua', jml: D.ringkasPelanggan.total },
    { k: 'baru', label: 'Baru', jml: D.ringkasPelanggan.baru, s: 'Baru' },
    { k: 'tetap', label: 'Tetap', jml: D.ringkasPelanggan.tetap, s: 'Tetap' },
    { k: 'lama', label: 'Lama tidak datang', jml: D.ringkasPelanggan.lama, s: 'Lama tidak datang' },
  ];

  BQ.pages.pelanggan = {
    render({ tab, S }) {
      const t = SEG.find(x => x.k === tab) || SEG[0];
      const kasir = S.role === 'kasir';
      const lama = t.k === 'lama';
      const daftar = D.pelanggan.filter(p => !t.s || p.segmen === t.s);
      const R = D.ringkasPelanggan;

      /* keterbacaan sesi 2: 5 kolom, kontak di panel, segmen hanya bila perlu perhatian, WhatsApp jadi ikon */
      const segmen = p => p.segmen === 'Tetap' ? '' : ` ${ui.lencana(p.segmen, nada(p.segmen), 1)}`;
      const tabel = ui.tabel({
        kolom: [
          { k: 'nama', label: 'Pelanggan', utama: true, l: 'minmax(220px, 2fr)', hp: 'utama' },
          { k: 'booking', label: 'Booking', l: 'auto', kanan: true },
          ...(kasir || lama ? [] : [{ k: 'total', label: 'Total dibayar', l: 'auto', kanan: true, hp: 'akhir' }]),
          { k: 'kapan', label: 'Terakhir datang', l: 'auto', nowrap: true },
          ...(lama ? [{ k: 'total', label: 'Total dibayar', l: 'auto', kanan: true }] : [{ k: 'berikut', label: 'Berikutnya', l: 'auto', nowrap: true }]),
          { k: 'wa', label: '', aksi: true, hp: lama ? 'akhir' : 'tidak' },
        ],
        baris: daftar.map(p => ({
          aksi: 'pelanggan', arg: p.nama,
          hpSub: `${p.booking} booking · terakhir ${esc(p.terakhir)}${p.segmen !== 'Tetap' ? ' · ' + esc(p.segmen) : ''}`,
          sel: {
            nama: `<span class="baris rapat">${ui.avatar(p.nama)}<span class="potong" title="${esc(p.nama)}">${esc(p.nama)}</span>${segmen(p)}</span>`,
            booking: `<span class="num">${p.booking}</span>`,
            total: `<span class="num">${rp(p.total)}</span>`,
            kapan: esc(p.terakhir),
            berikut: p.berikut ? esc(p.berikut.replace(', ', ' · ')) : '<span class="redup">—</span>',
            wa: lama ? ui.btn('Sapa', { kecil: true, ikon: 'pesan', aksi: 'sapa', arg: p.nama }) : ui.aksiIkon(`WhatsApp ${p.nama}`, 'pesan', 'toast', `Membuka WhatsApp ${p.hp}`),
          },
        })),
      });

      return `<div class="view-in">
        ${ui.kepala({ judul: 'Pelanggan', tujuan: 'Siapa pelanggan Anda, dan bagaimana menghubungi mereka.' })}
        ${ui.tabs('pelanggan', SEG.map(x => ({ k: x.k, label: x.label, jml: x.jml })), t.k)}${ui.cat('A', '8.5 no. 1, 2, 4')}${ui.cat('B', 'K8 kartu ringkasan dihapus; hitungan ada di tab')}
        ${lama ? `<p class="redup">${R.lama} pelanggan belum datang lagi lebih dari 60 hari. Sapa membuka WhatsApp dengan pesan siap kirim, satu per satu; tidak ada pesan massal. ${ui.cat('C', 'fitur 5')}</p>` : ''}
        ${ui.panel({ rata: true, isi: ui.filter({ cari: 'Cari nama, telepon atau email' }) + tabel + ui.hal(`1–${daftar.length} dari ${t.jml} pelanggan`, daftar.length < t.jml) })}
      </div>`;
    },
  };
})();
