/* Langkah 4, uang: Pembayaran (§8.12), Laporan (§8.13), Promo (§8.10), Ulasan (§8.11). */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const TINGKAT = { small: 1, medium: 2, pro: 3 };
  const minimal = (S, p) => TINGKAT[S.plan] >= TINGKAT[p];
  const ditolak = (judul, teks) => `<div class="view-in">${ui.kepala({ judul })}${ui.panel({ isi: ui.kosong({ ikon: 'kunci', judul: 'Hanya untuk Pemilik', teks }) })}</div>`;
  const ST_BAYAR = { sukses: ['Berhasil', 'lunas'], pending: ['Menunggu', 'tunggu'], gagal: ['Gagal', 'bahaya'], refund: ['Direfund', 'batal'] };
  const ST_CAIR = { proses: ['Diproses', 'tunggu'], berhasil: ['Berhasil', 'lunas'], gagal: ['Gagal', 'bahaya'] };
  /* bintang kosong hanya hiasan; nilai dibaca dari angka di sebelahnya */
  const bintang = n => `<span class="bintang" role="img" aria-label="${n} dari 5 bintang">${'★'.repeat(n)}<span aria-hidden="true">${'★'.repeat(5 - n)}</span></span> <span class="kecil num">${n}/5</span>`;

  /* ---------- Pembayaran ---------- */
  const tabelTransaksi = baris => ui.tabel({
    /* keterbacaan sesi 2: status hanya bila bukan "berhasil", kode order di detail (K6, K7) */
    kolom: [
      { k: 'w', label: 'Waktu', l: 'auto', nowrap: true },
      { k: 'p', label: 'Pelanggan', utama: true, l: 'minmax(160px, 1fr)', hp: 'utama' },
      { k: 'l', label: 'Layanan', l: 'minmax(0, 1.5fr)' },
      { k: 'm', label: 'Metode', l: 'auto', nowrap: true },
      { k: 'j', label: 'Jumlah', l: 'auto', kanan: true, hp: 'akhir' },
    ],
    baris: baris.map(([id, w, nama, l, j, m, s]) => ({ aksi: 'toast', arg: `Membuka ${id}`, redup: s === 'gagal',
      hpSub: `${s === 'sukses' ? '' : ui.lencana(ST_BAYAR[s][0], ST_BAYAR[s][1]) + ' '}${esc(w.replace(', ', ' '))} · ${esc(m)}`, sel: {
      w: esc(w.replace(', ', ' · ')), p: `<span class="potong" title="${esc(nama)}">${esc(nama)}</span>`, l: `<span class="potong" title="${esc(BQ.layanan(l).nama)}">${esc(BQ.layanan(l).nama)}</span>`, m: esc(m),
      j: `<span class="baris rapat kanan-isi">${s === 'sukses' ? '' : ui.lencana(ST_BAYAR[s][0], ST_BAYAR[s][1])}<span class="num">${s === 'refund' ? '−' : ''}${rp(j)}</span></span>`,
    } })),
  });

  /* Pencairan tiga langkah: jumlah → rekening → konfirmasi (§8.12) */
  function cairkan(l) {
    const s = D.saldo, r = D.rekening[0];
    const kepala = `<p class="redup kecil">Langkah ${l} dari 3 · ${['Jumlah', 'Rekening', 'Konfirmasi'][l - 1]}</p>`;
    const isi = l === 1
      ? ui.bidang({ id: 'cr1', label: 'Jumlah', wajib: true, petunjuk: `Paling sedikit ${rp(s.min)}, paling banyak ${rp(s.tersedia)} (saldo tersedia). Rupiah bulat.` }, ui.input({ id: 'cr1', nilai: '18.500.000', awalan: 'Rp' }))
      : l === 2
        ? `<div class="tumpuk rapat"><span class="label">Ke rekening</span>${ui.radioKartu('rk', D.rekening.map(x => ({ nilai: x.no, judul: `${x.bank} ${x.no}`, ket: x.nama + (x.utama ? ' · utama' : '') })), r.no)}<a href="#/pembayaran/rekening">Kelola rekening</a></div>`
        : ui.kv([['Jumlah', `<b class="num">${rp(s.tersedia)}</b>`], ['Ke rekening', `${r.bank} ${r.no} · ${esc(r.nama)}`], ['Biaya', 'Rp0'], ['Diterima', `<b class="num">${rp(s.tersedia)}</b>`], ['Perkiraan', 'Ditinjau dan ditransfer admin BookQu, biasanya 1 hari kerja']])
          + '<p class="redup kecil">Kunci permintaan PCR-REQ-8F2A: menekan dua kali tidak membuat pencairan kedua.</p>';
    BQ.dialog({
      judul: 'Cairkan saldo', isi: kepala + isi,
      kaki: (l === 1 ? ui.btn('Batal', { aksi: 'tutup' }) : ui.btn('Kembali', { aksi: 'cairkan', arg: l - 1 }))
        + (l < 3 ? ui.btn('Lanjut', { v: 'utama', aksi: 'cairkan', arg: l + 1 }) : ui.btn('Ajukan pencairan', { v: 'utama', aksi: 'cair-ajukan', arg: `Pencairan ${rp(s.tersedia)} diajukan` })),
    });
  }

  BQ.pages.pembayaran = {
    render({ tab, S }) {
      if (S.role !== 'pemilik') return ditolak('Pembayaran', 'Saldo, pencairan dan rekening hanya bisa dibuka oleh Pemilik.');
      tab = tab || 'ringkasan';
      /* Bisnis baru: saldo 0 dan belum ada rekening; tombol cairkan mati dengan alasan (review 8.12 no. 2) */
      if (S.state === 'baru') {
        const tb = ui.tabs('pembayaran', [{ k: 'ringkasan', label: 'Ringkasan' }, { k: 'transaksi', label: 'Transaksi', jml: 0 }, { k: 'saldo', label: 'Saldo & pencairan' }, { k: 'rekening', label: 'Rekening', jml: 0 }, { k: 'cara', label: 'Cara kerja' }], tab);
        const isiBaru = tab === 'rekening'
          ? ui.panel({ isi: ui.kosong({ ikon: 'kartu', judul: 'Belum ada rekening', teks: 'Tambahkan rekening untuk mencairkan saldo. Paling banyak 3 rekening.', aksi: ui.btn('Tambah rekening', { v: 'utama', aksi: 'tambah-rekening' }) }) })
          : ui.banner({ nada: 'perlu', judul: 'Saldo belum bisa dicairkan', teks: `Belum ada rekening tujuan. Pencairan paling sedikit ${rp(D.saldo.min)}.`, aksi: ui.btn('Tambah rekening', { kecil: true, href: '#/pembayaran/rekening' }) }) + ui.cat('A', '8.12 no. 2')
            + ui.panel({ isi: ui.angka({ utama: { nilai: 'Rp0', label: 'saldo tersedia', ket: 'Pembayaran booking online masuk ke sini.' } }) });
        return `<div class="view-in">${ui.kepala({ judul: 'Pembayaran', tujuan: 'Uang yang masuk, saldo Anda, dan kapan bisa dicairkan.', aksi: tab === 'rekening' ? '' : ui.btn('Cairkan saldo', { v: 'utama', mati: true, attrs: 'title="Tambahkan rekening dulu"' }) })}${tb}${isiBaru}</div>`;
      }
      const s = D.saldo, b = D.statusBayar;
      const tabs = ui.tabs('pembayaran', [{ k: 'ringkasan', label: 'Ringkasan' }, { k: 'transaksi', label: 'Transaksi', jml: 206 }, { k: 'saldo', label: 'Saldo & pencairan' }, { k: 'rekening', label: 'Rekening', jml: D.rekening.length }, { k: 'cara', label: 'Cara kerja' }], tab);
      let isi;
      if (tab === 'transaksi') {
        isi = ui.panel({ rata: true, isi: ui.filter({ cari: 'Cari nama atau nomor order', isi: ui.pilih({ label: 'Status', opsi: ['Semua status', 'Berhasil', 'Menunggu', 'Gagal', 'Direfund'] }) + ui.pilih({ label: 'Bulan', opsi: ['November 2026', 'Oktober 2026'] }) }) + tabelTransaksi(D.transaksi) + ui.hal('1–8 dari 206 transaksi') });
      } else if (tab === 'saldo') {
        isi = ui.panel({ judul: 'Mutasi saldo', ket: 'Setiap uang masuk dan keluar. Saldo akhir sama dengan saldo tersedia.', rata: true, isi: ui.tabel({
          kolom: [{ k: 't', label: 'Tanggal', l: 'auto', nowrap: true, hp: 'awal' }, { k: 'k', label: 'Keterangan', utama: true, hp: 'utama' }, { k: 'c', label: 'Kode', l: 'auto', nowrap: true, hp: 'sub' }, { k: 'j', label: 'Jumlah', l: 'auto', kanan: true, hp: 'akhir' }, { k: 's', label: 'Saldo', l: 'auto', kanan: true }],
          baris: D.mutasi.map(([t, k, c, j, sa]) => ({ sel: { t, k: esc(k), c: `<span class="redup">${c}</span>`, j: `<span class="num delta ${j < 0 ? 'turun' : 'naik'}">${j < 0 ? '−' : '+'}${rp(Math.abs(j))}</span>`, s: `<span class="num">${rp(sa)}</span>` } })),
        }) + ui.hal('1–6 dari 214 mutasi') })
          + ui.panel({ judul: 'Riwayat pencairan', ket: 'Diajukan → disetujui → ditransfer.', rata: true, isi: ui.tabel({
            kolom: [{ k: 'id', label: 'Pencairan', utama: true, hp: 'utama' }, { k: 'r', label: 'Rekening', l: 'auto', nowrap: true }, { k: 's', label: 'Status', l: 'minmax(180px, 1fr)' }, { k: 'j', label: 'Jumlah', l: 'auto', kanan: true, hp: 'akhir' }, { k: 'b', label: '', aksi: true }],
            /* status "berhasil" tanpa lencana; keterangan jadi judul status (K6) */
            baris: D.pencairan.map(([id, t, j, r, st, k]) => ({ hpSub: `${esc(r)} · ${st === 'berhasil' ? esc(k) : ui.lencana(ST_CAIR[st][0], ST_CAIR[st][1])}`, sel: { id: ui.dua(id, `Diajukan ${t}`), r: esc(r), s: st === 'berhasil' ? `<span class="redup">${esc(k)}</span>` : `<span title="${esc(k)}">${ui.lencana(ST_CAIR[st][0], ST_CAIR[st][1])}</span>`, j: `<span class="num">${rp(j)}</span>`, b: st === 'berhasil' ? ui.aksiIkon('Unduh bukti transfer', 'unduh', 'toast', 'Bukti transfer diunduh') : '' } })),
          }) });
      } else if (tab === 'rekening') {
        isi = ui.panel({ rata: true, isi: D.rekening.map(r => ui.lr({ awal: ui.ikon('kartu'), judul: `${r.bank} ${r.no}`, ket: esc(r.nama), akhir: (r.utama ? ui.lencana('Utama', 'pilih', 1) : ui.btn('Jadikan utama', { kecil: true, v: 'polos' })) + ui.btn('Lainnya', { kecil: true, ikon: 'lagi', hanyaIkon: true, aksi: 'menu-rekening' }) })).join('')
          + ui.hal(`${D.rekening.length} dari 3 rekening. Setiap perubahan butuh kata sandi dan dikabarkan lewat email.`, false) });
      } else if (tab === 'cara') {
        isi = ui.panel({ judul: 'Cara pembayaran bekerja', ket: 'Tidak ada yang perlu diatur di sini.', cat: ui.cat('B', '8.12 no. 3'), isi: ui.kv([
          ['Diproses oleh', 'BookQu: QRIS, virtual account dan e-wallet'],
          ['Biaya platform', '0%'],
          ['Batas bayar', 'Pelanggan punya 15 menit untuk membayar; slot ditahan selama itu'],
          ['Uang masuk', 'Ke saldo BookQu Anda, lalu dicairkan ke rekening'],
          ['Refund', 'Mengikuti Kebijakan Booking: penuh sampai 24 jam sebelum sesi. <a href="#/pengaturan/kebijakan">Ubah kebijakan</a>'],
        ]) });
      } else {
        isi = ui.panel({ isi: ui.angka({ utama: { nilai: rp(s.tersedia), label: 'saldo tersedia', ket: 'Bisa dicairkan sekarang' }, lain: [{ nilai: rp(s.proses), label: 'sedang diproses' }, { nilai: rp(s.dicairkan), label: 'sudah dicairkan' }] })
          + `<p class="redup kecil">${b.sukses} pembayaran berhasil · ${b.menunggu} menunggu · ${b.gagal} gagal · ${b.refund} direfund bulan ini ${ui.cat('A', '8.12 no. 4')}</p>` })
          + ui.panel({ judul: 'Transaksi terbaru', aksi: ui.btn('Lihat semua', { v: 'polos', kecil: true, href: '#/pembayaran/transaksi' }), rata: true, isi: tabelTransaksi(D.transaksi.slice(0, 5)) });
      }
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Pembayaran', tujuan: 'Uang yang masuk, saldo Anda, dan kapan bisa dicairkan.', aksi: tab === 'rekening' ? ui.btn('Tambah rekening', { v: 'utama', ikon: 'tambah', aksi: 'tambah-rekening', mati: D.rekening.length >= 3 }) : ui.btn('Cairkan saldo', { v: 'utama', aksi: 'cairkan' }) })}
        ${tabs}${isi}
      </div>`;
    },
  };

  /* ---------- Laporan ---------- */
  BQ.pages.laporan = {
    render({ tab, S }) {
      tab = tab || 'ringkasan';
      const kunci = !minimal(S, 'medium');
      const periode = ui.alat(ui.segmen([{ label: 'Bulan ini', on: true }, { label: 'Bulan lalu' }, { label: '3 bulan' }, { label: '12 bulan' }, { label: 'Pilih' }], { label: 'Periode' }), `<span class="redup kecil">1–16 Nov 2026, dibanding 1–16 Okt</span>`, `<span class="dorong">${ui.btn('Ekspor CSV', { kecil: true, ikon: 'unduh', aksi: 'toast', arg: 'CSV diunduh' })}</span>`);
      const tabs = ui.tabs('laporan', [{ k: 'ringkasan', label: 'Ringkasan' }, { k: 'pendapatan', label: 'Pendapatan' }, { k: 'okupansi', label: 'Okupansi' }, { k: 'layanan', label: 'Layanan' }, { k: 'pelanggan', label: 'Pelanggan' }], tab);
      const maks = D.perLayanan[0][1];
      let isi;
      if (tab === 'pendapatan') {
        isi = ui.kesimpulan('Pendapatan naik 12% dari bulan lalu, terutama dari Product & Catalog Shoot.')
          + ui.panel({ isi: ui.angka({ utama: { nilai: 'Rp13.600.000', label: 'pendapatan bersih', delta: 12, ket: 'Dari pembayaran berhasil dikurangi refund, menurut tanggal bayar' }, lain: [{ nilai: 'Rp10.950.000', label: 'online', delta: 15 }, { nilai: 'Rp3.100.000', label: 'dicatat kasir', delta: 2 }, { nilai: '−Rp450.000', label: 'refund' }] }) + `<div class="jarak-atas">${ui.tiang(D.pendapatan30.map(v => v * 1000), 29, '18 Okt', 'Hari ini')}</div>` + ui.cat('A', '8.13 no. 5') });
      } else if (tab === 'okupansi') {
        isi = ui.kesimpulan('Paling ramai Sabtu pagi (09.00–11.00). Paling sepi Selasa–Kamis 13.00–17.00: rata-rata 1 dari 6 slot terisi.')
          + ui.panel({ isi: ui.angka({ utama: { nilai: D.periode.terisi, label: 'slot terisi', delta: -4, ket: `${D.periode.booking} dari ${D.periode.slot} slot, menurut tanggal sesi` }, lain: [{ nilai: '3 hari', label: 'rata-rata booking sebelum sesi' }] }) + ui.cat('C', 'fitur 9') })
          + `${ui.panel({ judul: 'Peta jam ramai', ket: 'Makin gelap, makin banyak booking. Arahkan kursor ke kotak untuk melihat angkanya.', isi: ui.peta({ jam: ['08', '09', '10', '11', '13', '14', '15', '16', '18', '19'], baris: [['Sen', [1, 3, 0, 0, 1, 0, 0, 0, 1, 0]], ['Sel', [0, 1, 0, 0, 0, 1, 0, 0, 0, 0]], ['Rab', [0, 1, 0, 0, 0, 1, 0, 0, 0, 1]], ['Kam', [0, 1, 0, 0, 1, 0, 0, 1, 1, 1]], ['Jum', [0, 2, 1, 0, 0, 0, 0, 0, 0, 1]], ['Sab', [0, 4, 2, 1, 0, 0, 0, 1, 1, 0]], ['Min', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]]].map(([hari, nilai]) => ({ hari, nilai })) })
            + `<div class="jarak-atas baris">${minimal(S, 'medium') ? ui.btn('Buat promo untuk jam sepi', { kecil: true, ikon: 'promo', href: '#/promo' }) : ''}${ui.cat('C', 'fitur 8')}</div>` })}
`
          + (minimal(S, 'pro') ? ui.panel({ judul: 'Pemakaian unit', isi: `<div class="tumpuk">${D.unit.map(u => ui.batang({ label: u.nama, nilai: u.pakai * 10 + 20, maks: 100, teks: `${u.pakai * 10 + 20}%` })).join('')}</div>` }) : '');
      } else if (tab === 'layanan') {
        isi = ui.kesimpulan('Webinar & Corporate Broadcast hanya 3 booking, tetapi pendapatannya terbesar. Voice Over paling kecil.')
          + ui.panel({ judul: 'Pendapatan kotor per layanan', ket: `Jumlah ${rp(D.periode.kotor)}; dikurangi refund ${rp(D.periode.refund)} menjadi pendapatan bersih ${rp(D.periode.bersih)}. Booking menurut tanggal sesi.`, isi: `<div class="tumpuk">${D.perLayanan.map(([n, v, j]) => ui.batang({ label: `${n} · ${j} booking`, nilai: v, maks, teks: rp(v) })).join('')}</div>` });
      } else if (tab === 'pelanggan') {
        isi = ui.kesimpulan('Instagram membawa booking terbanyak; 3 calon pelanggan belum jadi membayar.')
          + `<div class="kisi-sama">${ui.panel({ isi: ui.angka({ utama: { nilai: '2', label: 'pelanggan baru', delta: -33, ket: '18 pelanggan kembali lagi' }, lain: [{ nilai: '9 kali', label: 'promo dipakai' }] }) })}
            ${ui.panel({ judul: 'Dari mana booking datang', cat: ui.cat('C', 'fitur 7'), isi: `<div class="tumpuk">${D.sumber.map(([n, v]) => ui.batang({ label: n, nilai: v, maks: 21, teks: `${v}` })).join('')}</div>` })}</div>`
          + ui.panel({ judul: 'Belum jadi membayar', ket: 'Sudah memilih slot tetapi tidak membayar. Calon pelanggan paling dekat.', cat: ui.cat('C', 'fitur 10'), rata: true, isi: D.gagalBayar.map(g => ui.lr({ awal: ui.avatar(g.nama), judul: esc(g.nama), ket: `${esc(g.layanan)} · ${g.kapan} · ${g.alasan}`, akhir: ui.btn('WhatsApp', { kecil: true, ikon: 'pesan', aksi: 'toast', arg: `Membuka WhatsApp ${g.hp}` }) })).join('') });
      } else {
        isi = ui.kesimpulan('Bulan ini lebih baik: pendapatan naik 12%, booking naik 8%. Tingkat tidak datang turun.') + ui.cat('B', '8.13 no. 2 · saran ditunda D-021')
          + ui.panel({ isi: `<div class="kisi-sama">${ui.angka({ utama: { nilai: 'Rp13.600.000', label: 'pendapatan bersih', delta: 12, ket: 'Menurut tanggal bayar' } })}${ui.angka({ utama: { nilai: String(D.periode.booking), label: 'booking', delta: 8, ket: 'Menurut tanggal sesi' } })}</div>` + ui.cat('A', '8.13 no. 3 · B no. 4') })
          + ui.panel({ isi: ui.angkaPendukung([{ nilai: '94%', label: 'selesai', delta: 3 }, { nilai: '3,5%', label: 'dibatalkan', delta: -1 }, { nilai: '1,8%', label: 'tidak datang', delta: -2 }, { nilai: '4,6', label: 'rating rata-rata' }, { nilai: '2', label: 'pelanggan baru' }, ...(minimal(S, 'pro') ? [{ nilai: 'Green Screen', label: 'unit paling sibuk' }] : [])]) });
      }
      const badan = `${periode}${tabs}${isi}`;
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Laporan', tujuan: 'Bagaimana bisnis Anda berjalan, dan apa yang bisa diperbaiki.' })}
        ${kunci ? `<div class="buram-bungkus"><div class="buram" aria-hidden="true">${ui.panel({ isi: ui.angka({ utama: { nilai: 'Rp0.000.000', label: 'pendapatan bersih', delta: 0 }, lain: [{ nilai: '00', label: 'booking' }, { nilai: '00%', label: 'slot terisi' }] }) + `<div class="jarak-atas">${ui.tiang([2, 4, 3, 6, 5, 7, 4, 8, 6, 9, 7, 5], 11, '', '')}</div>` })}</div><div class="buram-ajak">${ui.panel({ isi: ui.kunci({ judul: 'Laporan tersedia mulai paket Medium', teks: 'Lihat pendapatan, jam ramai dan layanan terlaris, dibanding bulan lalu.', paket: 'Medium' }) })}</div></div>` : badan}
      </div>`;
    },
  };

  /* ---------- Promo ---------- */
  const formPromo = () => BQ.laci({
    judul: 'Buat promo',
    isi: ui.bidang({ id: 'p1', label: 'Kode promo', wajib: true, petunjuk: 'Huruf, angka, tanda hubung dan garis bawah. Paling banyak 50 karakter.' }, ui.input({ id: 'p1', nilai: 'SIANGSEPI' }))
      + `<div class="tumpuk rapat"><span class="label">Jenis potongan</span>${ui.segmen([{ label: 'Persen', on: true }, { label: 'Nominal' }])}</div>`
      + `<div class="form-2">${ui.bidang({ id: 'p2', label: 'Nilai', wajib: true }, ui.input({ id: 'p2', nilai: '20', akhiran: '%' }))}${ui.bidang({ id: 'p3', label: 'Potongan maksimum', petunjuk: '0 = tanpa batas.' }, ui.input({ id: 'p3', nilai: '75.000', awalan: 'Rp' }))}</div>`
      + `<div class="form-2">${ui.bidang({ id: 'p4', label: 'Belanja minimum', petunjuk: '0 = tanpa minimum.' }, ui.input({ id: 'p4', nilai: '0', awalan: 'Rp' }))}${ui.bidang({ id: 'p5', label: 'Kuota', petunjuk: 'Berapa kali promo bisa dipakai.' }, ui.input({ id: 'p5', nilai: '30', akhiran: 'kali' }))}</div>`
      + `<div class="form-2">${ui.bidang({ id: 'p6', label: 'Mulai' }, ui.input({ id: 'p6', nilai: '17 Nov 2026' }))}${ui.bidang({ id: 'p7', label: 'Berakhir' }, ui.input({ id: 'p7', nilai: '31 Des 2026' }))}</div>`
      + ui.bidang({ id: 'p8', label: 'Berlaku untuk' }, ui.pilih({ id: 'p8', opsi: ['Semua layanan', ...D.layanan.map(l => l.nama)] }))
      + ui.sakelar({ judul: 'Aktif', on: true }),
    kaki: ui.btn('Simpan promo', { v: 'utama', aksi: 'simpan-promo' }),
  });

  BQ.pages.promo = {
    render({ tab, S }) {
      const kepala = ui.kepala({ judul: 'Promo', tujuan: 'Beri potongan harga, dan lihat apakah berhasil.', aksi: minimal(S, 'medium') ? ui.btn('Buat promo', { v: 'utama', ikon: 'tambah', aksi: 'form-promo', mati: S.state === 'berakhir' }) : '' });
      if (!minimal(S, 'medium')) return `<div class="view-in">${kepala}${ui.panel({ isi: ui.kunci({ judul: 'Promo tersedia mulai paket Medium', teks: 'Buat kode potongan untuk pelanggan, dengan kuota dan masa berlaku.', paket: 'Medium' }) })}</div>`;
      tab = tab || 'aktif';
      const daftar = D.promo.filter(p => tab === 'semua' || p.st === tab);
      const kartu = p => ui.kartu({
        mati: p.st === 'berakhir', aksi: 'form-promo',
        lencana: p.st === 'terjadwal' ? ui.lencana('Terjadwal', 'netral', 1) : p.st === 'berakhir' ? ui.lencana('Berakhir', 'batal', 1) : p.pakai >= p.kuota * 0.9 ? ui.lencana('Hampir habis', 'tunggu', 1) : '',
        /* keterbacaan sesi 3: potongan besar, kode, satu baris masa berlaku, satu batang pemakaian; syarat di laci */
        besar: true, judul: p.nilai, ket: `Berlaku ${esc(p.masa)}`,
        isi: `<div class="tumpuk rapat jarak-atas">${ui.salin(p.kode)}${ui.batangMini(p.pakai, p.kuota, 'dipakai')}</div>`,
      });
      return `<div class="view-in">${kepala}
        ${ui.panel({ isi: ui.angka({ utama: { nilai: 'Rp2.950.000', label: 'pendapatan dari booking berpromo', ket: '1–16 Nov. Batang di kartu = pemakaian sejak promo dimulai.' }, lain: [{ nilai: '3', label: 'promo aktif' }, { nilai: '9 kali', label: 'dipakai 1–16 Nov' }, { nilai: 'Rp610.000', label: 'potongan diberikan' }] }) })}
        ${ui.tabs('promo', [{ k: 'aktif', label: 'Aktif', jml: 3 }, { k: 'terjadwal', label: 'Terjadwal', jml: 1 }, { k: 'berakhir', label: 'Berakhir', jml: 1 }, { k: 'semua', label: 'Semua', jml: 5 }], tab)}
        ${tab === 'semua' ? ui.panel({ rata: true, isi: ui.tabel({
          kolom: [{ k: 'k', label: 'Kode', utama: true, hp: 'utama' }, { k: 'n', label: 'Potongan', l: 'auto', nowrap: true, hp: 'akhir' }, { k: 'm', label: 'Berlaku', l: 'auto', nowrap: true, hp: 'sub' }, { k: 'p', label: 'Dipakai', l: 'auto' }, { k: 'x', label: '', aksi: true }],
          baris: daftar.map(p => ({ aksi: 'form-promo', redup: p.st === 'berakhir', hpSub: `${esc(p.masa)} · ${p.pakai}/${p.kuota} dipakai${p.st === 'berakhir' ? ' · Berakhir' : p.st === 'terjadwal' ? ' · Terjadwal' : ''}`, sel: {
            k: `<span class="baris"><span class="jam">${esc(p.kode)}</span>${p.st === 'terjadwal' ? ui.lencana('Terjadwal', 'netral', 1) : p.st === 'berakhir' ? '<span class="redup">· Berakhir</span>' : ''}</span>`,
            n: `<b>${esc(p.nilai)}</b>`, m: esc(p.masa), p: ui.batangMini(p.pakai, p.kuota), x: ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Menu promo'),
          } })),
        }) }) : ui.kisiKartu(daftar.map(kartu).join('') + (tab === 'aktif' ? ui.kartu({ judul: 'Promo untuk jam sepi', ket: 'Selasa–Kamis 13.00–17.00 rata-rata hanya 1 dari 6 slot terisi.', lencana: ui.cat('C', 'fitur 8'), isi: `<div class="jarak-atas">${ui.btn('Buat promo jam sepi', { kecil: true, ikon: 'promo', aksi: 'form-promo' })}</div>` }) : ''))}
        ${ui.cat('A', '8.10 no. 2')}
      </div>`;
    },
  };

  /* ---------- Ulasan ---------- */
  BQ.pages.ulasan = {
    render({ tab }) {
      tab = tab || 'belum';
      const R = D.rating;
      const daftar = D.ulasan.filter(u => tab === 'semua' ? true : tab === 'sembunyi' ? u.sembunyi : !u.balas);
      const isi = daftar.length ? daftar.map(u => `<article class="ulasan">
          <div class="baris">${ui.avatar(u.nama)}<div><b>${esc(u.nama)}</b><div class="redup kecil">${esc(u.layanan)} · ${u.tgl}</div></div><span class="dorong">${bintang(u.bintang)}</span></div>
          <p>${esc(u.teks)}</p>
          ${u.balas ? `<div class="ulasan-balas"><b class="kecil">Balasan Anda</b><p>${esc(u.balas)}</p></div>` : `<div class="tumpuk rapat"><div class="baris">${['Terima kasih atas ulasannya!', 'Mohon maaf atas ketidaknyamanannya.', 'Sampai jumpa di sesi berikutnya.'].map(t => ui.btn(t, { kecil: true, v: 'kedua' })).join('')}</div>${ui.teksarea({ ph: 'Tulis balasan, paling banyak 1000 karakter' })}</div>`}
          <div class="baris">${u.balas ? ui.btn('Ubah balasan', { kecil: true, v: 'polos' }) : ui.btn('Kirim balasan', { kecil: true, aksi: 'toast', arg: 'Balasan terkirim' })}${ui.btn(u.sembunyi ? 'Tampilkan lagi' : 'Sembunyikan', { kecil: true, v: 'polos', aksi: 'toast', arg: u.sembunyi ? 'Ulasan tampil lagi' : 'Ulasan disembunyikan dari halaman booking' })}${u.kode ? `<span class="dorong">${ui.btn('Lihat booking', { kecil: true, v: 'polos', aksi: 'slip', arg: u.kode })}</span>` : ''}</div>
        </article>`).join('')
        : ui.kosong({ ikon: 'bintang', judul: 'Semua ulasan sudah dibalas', teks: 'Pelanggan memberi ulasan lewat link booking mereka setelah sesi selesai.' });
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Ulasan', tujuan: 'Apa kata pelanggan, dan apakah sudah Anda balas.' })}
        ${ui.panel({ isi: `<div class="dua-kolom">${ui.angka({ utama: { nilai: R.rata, label: 'rating rata-rata', ket: `dari ${R.jumlah} ulasan` } })}<div class="tumpuk rapat sebaran">${R.sebaran.map((v, i) => ui.batang({ label: `${5 - i} bintang`, nilai: v, maks: R.sebaran[0], teks: String(v) })).join('')}</div></div>${ui.cat('A', '8.11 no. 3')}` })}
        ${ui.tabs('ulasan', [{ k: 'belum', label: 'Belum dibalas', perlu: D.ulasan.filter(u => !u.balas).length }, { k: 'semua', label: 'Semua', jml: R.jumlah }, { k: 'sembunyi', label: 'Disembunyikan', jml: 1 }], tab)}
        ${ui.panel({ rata: true, isi: `<div class="ulasan-daftar">${isi}</div>` })}
      </div>`;
    },
  };

  BQ.aksi = Object.assign(BQ.aksi || {}, {
    'cair-ajukan': el => {
      if (BQ.S.state !== 'galat') { BQ.tutup(); return BQ.toast(el.dataset.arg); }
      BQ.dialog({ judul: 'Pencairan belum diproses', isi: ui.galatBlok({ judul: 'Layanan pembayaran tidak menjawab', teks: 'Saldo Anda tidak berkurang dan tidak ada uang yang keluar. Kunci permintaan PCR-REQ-8F2A tetap sama, jadi mencoba lagi tidak membuat pencairan ganda.' }), kaki: ui.btn('Nanti saja', { aksi: 'tutup' }) + ui.btn('Coba lagi', { v: 'utama', aksi: 'cairkan', arg: 3 }) });
    },
    cairkan: el => cairkan(Number(el.dataset.arg) || 1),
    'form-promo': () => formPromo(),
    'simpan-promo': () => {
      const v = (document.getElementById('p1') || {}).value || '';
      const kode = v.trim().toUpperCase();
      if (!kode) return BQ.galat('p1', 'Isi kode promo.');
      if (!/^[A-Z0-9_-]{1,50}$/.test(kode)) return BQ.galat('p1', 'Hanya huruf, angka, tanda hubung dan garis bawah.');
      if (D.promo.some(p => p.kode === kode)) return BQ.galat('p1', `Kode ${kode} sudah dipakai promo lain. Pilih kode lain.`);
      BQ.tutup(); BQ.toast(`Promo ${kode} dibuat`);
    },
    'tambah-rekening': () => BQ.dialog({ judul: 'Tambah rekening', isi: ui.bidang({ id: 'r1', label: 'Bank', wajib: true }, ui.pilih({ id: 'r1', opsi: ['BCA', 'Mandiri', 'BRI', 'BNI', 'BSI'] })) + ui.bidang({ id: 'r2', label: 'Nomor rekening', wajib: true }, ui.input({ id: 'r2' })) + ui.bidang({ id: 'r3', label: 'Nama pemilik rekening', wajib: true, petunjuk: 'Harus sama persis dengan nama di buku tabungan.' }, ui.input({ id: 'r3' })) + ui.bidang({ id: 'r4', label: 'Kata sandi akun BookQu', wajib: true, petunjuk: 'Untuk memastikan ini Anda. Kami mengirim email setiap kali rekening berubah.' }, ui.input({ id: 'r4', tipe: 'password' })), kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Simpan rekening', { v: 'utama', aksi: 'toast', arg: 'Rekening tersimpan' }) }),
    'menu-rekening': el => BQ.menu(el, [{ label: 'Ubah', ikon: 'atur' }, '-', { label: 'Hapus rekening', ikon: 'tutup', bahaya: true, aksi: 'toast', arg: 'Butuh kata sandi untuk menghapus' }]),
  });
})();
