/* Booking: "Booking apa saja, dan mana yang harus saya urus?" (§8.3, review 8.3, usulan fitur 2) */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const TAB = [
    { k: 'perlu', label: 'Perlu tindakan', saring: b => !!b.perlu, kosong: 'Tidak ada yang perlu diurus. Sesi yang lewat dan permintaan refund muncul di sini.' },
    { k: 'mendatang', label: 'Mendatang', saring: b => b.status === 'paid' && b.fase !== 'lewat' && !b.refund, kosong: 'Belum ada booking mendatang.' },
    { k: 'menunggu', label: 'Menunggu bayar', saring: b => b.status === 'pending', kosong: 'Tidak ada booking yang menunggu pembayaran.' },
    { k: 'selesai', label: 'Selesai', saring: b => b.status === 'completed' || b.status === 'no_show', kosong: 'Booking yang ditandai selesai atau tidak datang muncul di sini.' },
    { k: 'batal', label: 'Dibatalkan', saring: b => b.status === 'cancelled', kosong: 'Tidak ada booking yang dibatalkan.' },
    { k: 'semua', label: 'Semua', saring: () => true },
  ];
  const urut = (a, b) => (parseInt(a.tgl) - parseInt(b.tgl)) || a.mulai.localeCompare(b.mulai);

  const hari = t => t === '16 Nov' ? 'Hari ini · ' : t === '17 Nov' ? 'Besok · ' : '';
  /* layanan grup menampilkan peserta dari kapasitas; layanan satu orang tidak (M6) */
  const lay = x => { const L = BQ.layanan(x.layanan); return esc(L.nama) + ((L.kapasitas || 1) > 1 ? ` <span class="redup">· ${x.peserta || 1}/${L.kapasitas} peserta</span>` : ''); };
  const tindakan = x => x.refund ? ui.btn('Putuskan refund', { kecil: true, aksi: 'refund', arg: x.kode })
    : x.status === 'pending' ? ui.btn('Ingatkan bayar', { kecil: true, aksi: 'toast', arg: 'Pengingat dikirim ke pelanggan' })
      : ui.btn('Tandai selesai', { kecil: true, aksi: 'toast', arg: 'Booking ditandai selesai' });
  const bisaTutup = x => x.status === 'paid' && x.fase === 'lewat' && !x.refund;

  BQ.pages.booking = {
    render({ tab, S }) {
      const t = TAB.find(x => x.k === tab) || TAB[0];
      const kasir = S.role === 'kasir';
      const lalu = ['selesai', 'batal', 'semua'].includes(t.k);
      const semua = D.semua.filter(t.saring).sort(urut);
      if (lalu) semua.reverse();
      /* T4: paling banyak 25 baris per halaman */
      const baris = semua.slice(0, 25);
      const lewat = semua.filter(bisaTutup).length;
      const perlu = t.k === 'perlu';
      const bisaMassal = perlu;
      const kolom = perlu ? [
        { k: 'sesi', label: 'Sesi', nowrap: true, l: 'auto', hp: 'awal' },
        { k: 'nama', label: 'Pelanggan', utama: true, l: 'minmax(160px, 1fr)', hp: 'utama' },
        { k: 'layanan', label: 'Layanan', l: 'minmax(0, 2fr)' },
        { k: 'alasan', label: 'Yang perlu dilakukan', l: 'auto', hp: 'sub' },
        { k: 'tindak', label: '', aksi: true },
      ] : [
        { k: 'sesi', label: 'Jam', nowrap: true, l: 'auto', hp: 'awal' },
        { k: 'nama', label: 'Pelanggan', utama: true, l: 'minmax(160px, 1fr)', hp: 'utama' },
        { k: 'layanan', label: 'Layanan', l: 'minmax(0, 2fr)', hp: 'sub' },
        { k: 'status', label: 'Status', l: 'auto', hp: 'sub' },
        ...(kasir ? [] : [{ k: 'bayar', label: 'Dibayar', kanan: true, l: 'auto', hp: 'akhir' }]),
      ];
      const sel = x => ({
        sesi: perlu ? `<span class="jam">${x.mulai}</span> <span class="redup hanya-desktop">${x.tgl}</span>` : `<span class="jam">${x.mulai}</span><span class="jam hanya-desktop">–${x.selesai}</span>`,
        nama: `<span class="potong" title="${esc(x.nama)}">${esc(x.nama)}</span>`,
        layanan: `<span class="potong" title="${esc(BQ.layanan(x.layanan).nama)}">${lay(x)}</span>`,
        alasan: ui.titikStatus(x.perlu, 'kunyit'),
        tindak: tindakan(x),
        status: BQ.bersama.statusBaris(x),
        bayar: x.bayar ? `<span class="num">${rp(x.bayar)}</span>` : x.tagihan ? `<span class="num redup">${rp(x.tagihan)}</span>` : '<span class="redup">—</span>',
      });
      /* kelompok per tanggal menggantikan tanggal yang diulang di tiap baris */
      const isi = [];
      baris.forEach((x, i) => {
        if (!perlu && (i === 0 || baris[i - 1].tgl !== x.tgl)) {
          const n = baris.filter(b => b.tgl === x.tgl).length;
          isi.push({ grup: `${hari(x.tgl)}${BQ.bersama.tglPanjang(x.tgl).replace(/ 2026$/, '')} <span class="redup">· ${n} booking</span>` });
        }
        isi.push({
          aksi: 'slip', arg: x.kode, sel: sel(x), bisaPilih: bisaTutup(x),
          redup: x.status === 'cancelled',
          hpSub: perlu ? ui.titikStatus(x.perlu, 'kunyit') + ` · ${x.tgl}` : `${BQ.bersama.statusBaris(x)} · ${esc(BQ.layanan(x.layanan).nama)}`,
        });
      });
      /* T4: tutup semua sesi lewat sekaligus; tidak datang tetap diubah satu per satu dari slip */
      const massalLewat = perlu && lewat > 5 ? ui.catatanTabel(`${lewat} sesi sudah lewat dan belum ditutup.`) .replace('</p>', `${ui.btn(`Tandai selesai semua (${lewat})`, { kecil: true, aksi: 'tutup-semua', arg: lewat })}</p>`) : '';
      const tabel = baris.length ? massalLewat + ui.tabel({ pilih: bisaMassal, kolom, baris: isi })
        + (bisaMassal ? `<div class="massal-bungkus" hidden>${ui.massal(0, ui.btn('Tandai selesai', { v: 'utama', kecil: true, aksi: 'massal-selesai' }) + ui.cat('C', 'fitur 2'))}</div>` : '')
        + ui.hal(`1–${baris.length} dari ${D.tabBooking[t.k]} booking`, baris.length < D.tabBooking[t.k]) : ui.kosong({ ikon: 'daftar', judul: 'Kosong', teks: t.kosong });

      return `<div class="view-in">
        ${ui.kepala({ judul: 'Booking', tujuan: 'Semua booking, dikelompokkan menurut yang perlu Anda kerjakan.' })}
        ${ui.tabs('booking', TAB.map(x => ({ k: x.k, label: x.label, jml: x.k === 'perlu' ? null : D.tabBooking[x.k], perlu: x.k === 'perlu' ? D.tabBooking.perlu : null })), t.k)}
        ${ui.cat('B', 'K3 · 8.3 no. 4 satu lencana; kolom bayar berisi jumlah')}${ui.cat('A', 'keterbacaan sesi 1: kelompok tanggal, 5 kolom, kode di slip')}${ui.cat('C', 'fitur 2 centang banyak')}
        ${ui.panel({ rata: true, isi:
          ui.filter({ cari: 'Cari nama, kode atau layanan', n: 0, isi: ui.pilih({ label: 'Tanggal', opsi: ['Semua tanggal', 'Hari ini', '7 hari ke depan', 'Bulan ini'] }) + ui.pilih({ label: 'Layanan', opsi: ['Semua layanan', ...D.layanan.map(l => l.nama)] }) + ui.pilih({ label: 'Urutkan', opsi: lalu ? ['Terbaru dulu', 'Terlama dulu', 'Jumlah terbesar'] : ['Sesi terdekat', 'Terbaru dibuat', 'Jumlah terbesar'] }), akhir: (bisaMassal ? `<span class="hanya-hp">${ui.btn('Pilih', { kecil: true, aksi: 'mode-pilih' })}</span>` : '') + (S.plan === 'small' ? '' : `<span class="hanya-desktop">${ui.btn('Ekspor CSV', { ikon: 'unduh', kecil: true, aksi: 'toast', arg: 'CSV diunduh' })}</span>`) })
          + (S.state === 'memuat' ? `<div class="panel-isi">${ui.kerangka()}${ui.kerangka()}</div>` : tabel) })}
      </div>`;
    },
    pasang(view) {
      /* tab aktif selalu terlihat di HP (review 8.3) */
      const a = view.querySelector('.tabs [aria-current]');
      if (a) a.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    },
  };

  const hitungPilih = () => {
    const n = document.querySelectorAll('#view .tbl-b[aria-selected="true"]').length;
    const m = document.querySelector('#view .massal-bungkus');
    if (!m) return;
    m.hidden = n === 0;
    m.querySelector('.massal > span').textContent = `${n} dipilih`;
  };
    BQ.aksi = Object.assign(BQ.aksi || {}, {
      'tutup-semua': el => BQ.konfirmasi({ judul: `Tandai ${el.dataset.arg} booking selesai?`, teks: 'Semua sesi yang sudah lewat dan belum ditutup ditandai selesai, kecuali yang punya permintaan refund. Pelanggan yang tidak datang ubah satu per satu dari slip setelahnya.', ya: `Tandai ${el.dataset.arg} selesai`, hasil: `${el.dataset.arg} booking ditandai selesai` }),
    'pilih-baris': el => { const b = el.closest('.tbl-b'); if (b) b.setAttribute('aria-selected', el.checked); hitungPilih(); },
    'pilih-semua': el => { document.querySelectorAll('#view .tbl-b input[data-aksi="pilih-baris"]').forEach(c => { c.checked = el.checked; c.closest('.tbl-b').setAttribute('aria-selected', el.checked); }); hitungPilih(); },
    'mode-pilih': el => { const t = document.querySelector('#view .tbl'); if (t) { const on = t.classList.toggle('mode-pilih'); el.textContent = on ? 'Selesai memilih' : 'Pilih'; } },
    'massal-selesai': () => { const n = document.querySelectorAll('#view .tbl-b[aria-selected="true"]').length; BQ.toast(`${n} booking ditandai selesai`); },
  });
  document.addEventListener('click', e => { if (e.target.closest('.massal .btn-polos')) { document.querySelectorAll('#view .tbl-b[aria-selected="true"]').forEach(b => { b.setAttribute('aria-selected', 'false'); b.querySelector('input').checked = false; }); hitungPilih(); } });
})();
