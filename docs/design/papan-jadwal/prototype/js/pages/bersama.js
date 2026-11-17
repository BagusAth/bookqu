/* Bagian bersama alur harian: slip booking, catat booking, panel pelanggan, rel hari.
   Satu komponen dibuka dari semua pintu (owner-portal §8.4). */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const kasir = () => BQ.S.role === 'kasir';
  const lay = x => BQ.layanan(x.layanan).nama;
  const tglPanjang = t => ({ '16 Nov': 'Senin, 16 Nov 2026', '17 Nov': 'Selasa, 17 Nov 2026', '18 Nov': 'Rabu, 18 Nov 2026', '19 Nov': 'Kamis, 19 Nov 2026', '15 Nov': 'Minggu, 15 Nov 2026', '14 Nov': 'Sabtu, 14 Nov 2026', '13 Nov': 'Jumat, 13 Nov 2026' }[t] || t + ' 2026');
  const wa = (hp, kecil = true) => ui.btn('WhatsApp', { kecil, ikon: 'pesan', aksi: 'toast', arg: `Membuka WhatsApp ${hp}` });
  BQ.bersama = { lay, tglPanjang, wa };

  /* Galat formulir: menggantikan petunjuk di bawah bidang (aturan formulir README) */
  BQ.galat = (id, teks) => {
    const inp = document.getElementById(id);
    if (!inp) return false;
    inp.setAttribute('aria-invalid', 'true');
    const bd = inp.closest('.bidang');
    bd.querySelectorAll('.galat').forEach(g => g.remove());
    const pt = bd.querySelector('.petunjuk');
    if (pt) pt.hidden = true;
    (inp.closest('.input-grup') || inp).insertAdjacentHTML('afterend', `<span class="galat" role="alert">${ui.ikon('awas', 14)}${esc(teks)}</span>`);
    inp.focus();
    return false;
  };

  /* Status yang tampil di baris: sesi berjalan dan sesi lewat yang belum ditutup punya label sendiri */
  BQ.bersama.statusBaris = x => x.fase === 'kini' && x.status === 'paid' ? ui.titikStatus('Sedang berlangsung', 'lilak')
    : x.perlu === 'Sesi lewat, belum ditutup' ? ui.lencana('Belum ditutup', 'tunggu')
      : x.refund ? ui.lencana('Minta refund', 'tunggu') : ui.status(x.status);

  /* ---------- Rel hari (Beranda dan Kalender HP) ---------- */
  /* ringkas (Beranda HP): sesi yang sudah lewat dilipat jadi satu baris, unit tidak ditulis */
  BQ.bersama.relHari = (o = {}) => {
    const b = D.hariIni, k = D.slotKosongHariIni;
    const item = x => ui.ri({
      waktu: `${x.mulai}–${x.selesai}`, nama: x.nama,
      ket: x.status === 'pending' ? `${lay(x)} · bayar sebelum ${x.batas} (sisa ${x.sisa})` : o.ringkas ? lay(x) : `${lay(x)} · ${BQ.unit(x.unit).nama}`,
      status: BQ.bersama.statusBaris(x),
      tindak: x.fase === 'lewat' && x.status === 'paid' ? ui.btn('Tandai selesai', { kecil: true, aksi: 'toast', arg: 'Booking ditandai selesai' }) : '',
      nada: x.fase === 'kini' ? 'kini' : x.fase === 'lewat' ? 'lewat' : x.status === 'pending' ? 'tunggu' : '',
      aksi: 'slip', arg: x.kode,
    });
    const bebas = x => ui.ri({ waktu: `${x.mulai}–${x.selesai}`, nama: 'Kosong', ket: `${BQ.layanan(x.layanan).nama} · ${BQ.unit(x.unit).nama}`, nada: 'bebas', tindak: ui.btn('Catat booking', { kecil: true, v: 'polos', ikon: 'tambah', aksi: 'catat', arg: `slot|${x.layanan}|${x.mulai}` }) });
    const lewat = b.filter(x => x.fase === 'lewat').length;
    if (o.ringkas) return `<p class="redup kecil" style="margin:0 0 var(--s2)">${lewat} sesi sebelumnya sudah lewat · <a href="#/kalender/hari">Lihat semua</a></p>` + ui.rel([
      ...[['09', b[1]], ['10', null], ['11', b[2]], ['14', b[3]], ['16', b[4]], ['18', b[5]]].filter(([, x]) => !x || x.fase !== 'lewat').map(([jam, x]) => x ? { jam, isi: [item(x)] } : { kini: D.kini.jam }),
    ]);
    return ui.rel([
      { jam: '08', isi: [item(b[0])] }, { jam: '09', isi: [item(b[1])] }, { kini: D.kini.jam },
      { jam: '11', isi: [item(b[2])] }, { jam: '13', isi: [bebas(k[0])] }, { jam: '14', isi: [item(b[3])] },
      { jam: '15', isi: [bebas(k[1])] }, { jam: '16', isi: [item(b[4])] }, { jam: '18', isi: [item(b[5])] },
    ]);
  };

  /* ---------- Slip booking (laci) ---------- */
  /* waktu bayar diambil dari transaksi yang sama supaya slip dan Pembayaran cocok */
  const waktuBayar = x => { const t = (D.transaksi || []).find(r => r[2] === x.nama && r[3] === x.layanan && r[6] === 'sukses'); return t ? t[1] : x.sumber === 'Dicatat kasir' ? '15 Nov, 17.40' : `${x.tgl === '16 Nov' ? '14 Nov' : x.tgl}, 10.23`; };
  function riwayat(x) {
    const r = [];
    if (x.sumber === 'Dicatat kasir') r.push(['15 Nov, 17.40', 'Dicatat oleh Dimas Prakoso (Kasir), bayar tunai']);
    else r.push(['14 Nov, 10.21', `Dibuat oleh ${x.nama} (Pelanggan)`]);
    if (x.bayar && x.sumber !== 'Dicatat kasir') r.push([waktuBayar(x), `Pembayaran berhasil lewat ${x.metode} (sistem)`]);
    if (x.status === 'pending') r.push(['16 Nov, 10.21', 'Menunggu pembayaran (sistem)']);
    if (x.refund) r.push([x.refund.diminta, `${x.nama} (Pelanggan) minta refund`]);
    if (x.status === 'completed') r.push([`${x.tgl}, ${x.selesai}`, 'Ditandai selesai oleh Brama Aditya (Pemilik)']);
    if (x.status === 'no_show') r.push([`${x.tgl}, ${x.selesai}`, 'Ditandai tidak datang oleh Sinta Maharani (Manajer)']);
    if (x.status === 'cancelled') r.push([`${x.tgl}`, x.batal || 'Dibatalkan']);
    return r;
  }

  function slipIsi(x) {
    const L = BQ.layanan(x.layanan), p = BQ.pel(x.nama);
    const tunggu = x.status === 'pending';
    const kaki = tunggu
      ? `<div class="baris"><span>Bayar sebelum <b class="jam">${x.batas}</b> <span class="redup">· sisa ${x.sisa}</span></span><span class="dorong tebal num">${rp(x.tagihan)}</span></div>`
      : x.bayar ? `<div class="baris"><span>${esc(x.metode)}</span><span class="dorong tebal num">${rp(x.bayar)}</span></div>`
        : `<div class="baris redup"><span>Tidak ada pembayaran</span></div>`;
    const harga = L.harga;
    return ui.cat('A', '8.3 no. 5') + ui.slip({ status: BQ.bersama.statusBaris(x), sumber: x.sumber, tanggal: x.sesi ? `${x.sesi.length} sesi · mulai ${tglPanjang(x.tgl).replace(/ 2026$/, '')}` : tglPanjang(x.tgl).replace(/ 2026$/, ''), jam: `${x.mulai}–${x.selesai}`, layanan: L.nama, ket: `${BQ.unit(x.unit).nama}${(L.kapasitas || 1) > 1 ? ` · ${x.peserta || 1}/${L.kapasitas} peserta` : ''}`, kaki })
      + (x.sesi ? `<div class="tumpuk rapat"><span class="label">${x.sesi.length} sesi dalam satu booking</span>${ui.kv(x.sesi.map(([t, j]) => [t, `<span class="jam">${j}</span>`]))}</div>` : '')
      + (tunggu ? `<p class="redup kecil">Tindakan dibuka setelah pembayaran selesai atau batas bayar lewat. Slot ditahan untuk pelanggan sampai ${x.batas}.</p>` : '')
      + (x.refund ? ui.perluBlok('Pelanggan minta refund', `"${x.refund.alasan}" Diminta ${x.refund.diminta}.`) : '')
      + (x.status === 'paid' && x.fase === 'lewat' && !x.refund ? ui.perluBlok('Sesi sudah lewat, belum ditutup', 'Tandai selesai atau tidak datang supaya laporan dan saldo tepat.') : '')
      + `<div class="tumpuk rapat"><span class="label">Pelanggan</span>${ui.lr({ awal: ui.avatar(x.nama), judul: esc(x.nama), ket: [x.hp || p.hp, p.email].filter(Boolean).join(' · '), akhir: wa(x.hp || p.hp) + ui.ikon('kanan', 18), aksi: 'pelanggan', arg: x.nama, datar: true })}</div>`
      + (x.bayar || tunggu ? `<div class="tumpuk rapat"><span class="label">Pembayaran</span>${ui.kv([
        ...(x.sesi ? [[`${x.sesi.length} sesi × ${rp(harga)}`, `<span class="num">${rp(harga * x.sesi.length)}</span>`]] : x.promo ? [['Harga layanan', `<span class="num">${rp(harga)}</span>`]] : []),
        ...(x.promo ? [[`Promo ${x.promo[0]}`, `<span class="num">−${rp(x.promo[1])}</span>`]] : []),
        ['Total', `<b class="num">${rp(x.bayar || x.tagihan)}</b>`],
        ...(x.bayar ? [['Dibayar', `${waktuBayar(x)} · ${esc(x.metode)}`]] : []),
        ...(x.status === 'cancelled' && x.bayar ? [['Refund', `<span class="num">${rp(x.bayar)}</span> · menunggu transfer admin`]] : []),
      ])}</div>` : '')
      + (x.kode === 'BKG-CFWK9P' ? `<div class="tumpuk rapat"><span class="label">Catatan pelanggan</span><p>Mohon disiapkan 3 mic dan headphone.</p></div>` : '')
      + `<div class="tumpuk rapat"><span class="label">Riwayat</span>${ui.kv(riwayat(x))}</div>`;
  }

  function slipKaki(x) {
    const lagi = items => ui.btn('Lainnya', { ikon: 'lagi', hanyaIkon: true, aksi: 'menu-slip', arg: x.kode });
    if (x.status === 'pending') return ui.btn('Kirim ulang link', { aksi: 'toast', arg: 'Link booking dikirim ulang ke email pelanggan' });
    if (x.refund) return lagi() + ui.btn('Putuskan refund', { v: 'utama', aksi: 'refund', arg: x.kode });
    if (x.status === 'paid' && x.fase === 'lewat') return lagi() + ui.btn('Tidak datang', { aksi: 'toast', arg: 'Ditandai tidak datang' }) + ui.btn('Tandai selesai', { v: 'utama', aksi: 'toast', arg: 'Booking ditandai selesai' });
    if (x.status === 'paid' && x.fase === 'kini') return lagi() + ui.btn('Tandai selesai', { v: 'utama', aksi: 'toast', arg: 'Booking ditandai selesai' });
    if (x.status === 'paid') return lagi() + ui.btn('Pindah jadwal', { v: 'utama', aksi: 'pindah', arg: x.kode });
    return ui.btn('Tutup', { aksi: 'tutup' });
  }

  /* ---------- Catat booking (satu formulir untuk semua pintu) ---------- */
  /* Sesi 4 (T3): slot keburu diambil (INV-BOOK-04). Isian tetap; tawarkan jam terdekat yang masih kosong. */
  function bentrok(jam, lain) {
    const isi = document.querySelector('.lap-isi'); if (!isi) return;
    const lama = isi.querySelector('.galat-blok'); if (lama) lama.remove();
    isi.insertAdjacentHTML('afterbegin', ui.galatBlok({ judul: `Jam ${jam} baru saja terisi booking lain`, teks: 'Booking ini belum tersimpan dan isian Anda tidak hilang. Pilih jam lain yang masih kosong:', aksi: lain.map(j => ui.btn(j, { kecil: true, aksi: 'ganti-jam', arg: j })).join('') }));
    isi.scrollTop = 0; isi.querySelector('.galat-blok button').focus();
  }
  function catat(arg) {
    if (BQ.S.state === 'galat' && BQ.S.plan === 'small') {
      return BQ.dialog({ judul: 'Kuota booking bulan ini habis', isi: '<p>100 dari 100 booking bulan November sudah terpakai di paket Small. Booking yang ada tetap bisa diurus. Kuota kembali 1 Des, atau naikkan paket sekarang.</p>', kaki: ui.btn('Tutup', { aksi: 'tutup' }) + ui.btn('Lihat paket', { v: 'utama', href: '#/paket' }) });
    }
    if (BQ.S.state === 'berakhir') {
      return BQ.dialog({ judul: 'Booking baru belum bisa dicatat', isi: '<p>Langganan berakhir 14 Nov. Booking yang ada tetap bisa diurus, tetapi booking baru tidak diterima sampai paket diperpanjang.</p>', kaki: ui.btn('Tutup', { aksi: 'tutup' }) + (BQ.S.role === 'pemilik' ? ui.btn('Perpanjang paket', { v: 'utama', href: '#/paket' }) : '') });
    }
    const [jenis, a, b] = String(arg || '').split('|');
    const L = BQ.layanan(jenis === 'slot' ? a : 'L4');
    const p = jenis === 'pel' ? BQ.pel(a) : null;
    const jam = ['09.00', '11.00', '13.00', '15.00', '17.00', '19.00'];
    BQ.dialog({
      judul: 'Catat booking',
      isi: ui.bidang({ id: 'c1', label: 'Layanan', wajib: true }, ui.pilih({ id: 'c1', nilai: L.nama, opsi: D.layanan.map(l => l.nama) }))
        + `<div class="tumpuk rapat"><span class="label">Tanggal</span>${ui.segmen([{ label: 'Hari ini, 16 Nov', on: true }, { label: 'Besok' }, { label: 'Pilih tanggal', aksi: 'pilih-tanggal' }])}<div class="tgl-mini" hidden>${ui.bulan({ geser: 6, hari: Array.from({ length: 30 }, (_, i) => ({ n: i + 1, slot: i + 1 >= 16 && ![22, 29].includes(i + 1) ? 1 : 0, isi: 0, kini: i + 1 === 16 })) })}</div></div>`
        + `<div class="tumpuk rapat"><span class="label">Jam mulai</span>${ui.chipJam(jam, jenis === 'slot' ? b : '13.00', ['09.00', '11.00'])}<span class="petunjuk">Jam yang dicoret sudah terisi atau sudah lewat. ${esc(L.durasi)} menit per sesi.</span></div>`
        + ((L.kapasitas || 1) > 1 ? ui.bidang({ id: 'c5', label: 'Peserta', petunjuk: `Sisa ${L.kapasitas - 1} dari ${L.kapasitas} kursi di jam ini.` }, ui.input({ id: 'c5', nilai: '1', akhiran: 'orang' })) : '')
        + ui.bidang({ id: 'c2', label: 'Nama pelanggan', wajib: true, petunjuk: p ? 'Pelanggan lama: data diisi dari booking sebelumnya.' : 'Ketik nama atau nomor HP untuk mencari pelanggan lama.' }, ui.input({ id: 'c2', nilai: p ? p.nama : '', ph: 'Nama atau nomor HP' }))
        + (p ? '' : `<div class="saran" hidden role="listbox" aria-label="Pelanggan lama"><span class="label">Pelanggan lama</span>${D.pelanggan.slice(0, 2).map(x => ui.lr({ datar: true, awal: ui.avatar(x.nama), judul: esc(x.nama), ket: `${x.hp} · ${x.booking} booking`, aksi: 'catat', arg: `pel|${x.nama}` })).join('')}</div>`)
        + `<div class="form-2">${ui.bidang({ id: 'c3', label: 'Nomor WhatsApp', petunjuk: 'Untuk kirim bukti booking.' }, ui.input({ id: 'c3', tipe: 'tel', nilai: p ? p.hp : '', ph: '08…' }))}${ui.bidang({ id: 'c6', label: 'Email (opsional)' }, ui.input({ id: 'c6', tipe: 'email', nilai: p ? p.email : '', ph: 'nama@contoh.com' }))}</div>`
        + `<div class="tumpuk rapat"><span class="label">Cara bayar</span>${ui.radioKartu('cb', [{ nilai: 'tunai', judul: 'Tunai' }, { nilai: 'qris', judul: 'QRIS' }, { nilai: 'transfer', judul: 'Transfer' }, { nilai: 'lain', judul: 'Lainnya' }], 'tunai')}<span class="petunjuk">Booking yang dicatat di sini langsung terkonfirmasi.</span></div>`
        + ui.bidang({ id: 'c4', label: 'Catatan (opsional)' }, ui.input({ id: 'c4', ph: 'Misalnya: bawa properti sendiri' })),
      kaki: `<span class="lap-total"><span>Total</span><b>${rp(L.harga)}</b></span>` + ui.btn('Catat booking', { v: 'utama', aksi: 'catat-simpan' }),
    });
  }

  /* ---------- Panel pelanggan ---------- */
  function pelanggan(nama) {
    const p = BQ.pel(nama);
    const daftar = D.semua.filter(b => b.nama === nama);
    BQ.laci({
      judul: p.nama,
      isi: `<div class="baris">${ui.avatar(p.nama, 1)}<div><b>${esc(p.nama)}</b><div class="redup kecil">${esc([p.hp, p.email].filter(Boolean).join(' · '))}</div></div><span class="dorong">${ui.lencana(p.segmen, p.segmen === 'Baru' ? 'pilih' : p.segmen === 'Tetap' ? 'netral' : 'tunggu', 1)}</span></div>`
        + ui.kv([['Booking', `<span class="num">${p.booking}</span>`], ...(kasir() ? [] : [['Total dibayar', `<span class="num">${rp(p.total)}</span>`]]), ['Dibatalkan', `<span class="num">${p.batal}</span>`], ['Tidak datang', `<span class="num">${p.absen}</span>`], ['Terakhir datang', esc(p.terakhir)], ['Berikutnya', p.berikut ? esc(p.berikut) : '<span class="redup">Belum ada</span>']])
        + ui.bidang({ id: 'pn', label: 'Catatan internal', petunjuk: 'Hanya terlihat oleh tim Anda. Paling banyak 2000 karakter.' }, ui.teksarea({ id: 'pn', nilai: p.catatan || '', ph: 'Misalnya: alergi, preferensi, kebiasaan' }))
        + `<div class="tumpuk rapat"><span class="label">Riwayat booking</span>${daftar.length ? daftar.map(b => ui.lr({ awal: `<span class="jam">${b.tgl}</span>`, judul: esc(lay(b)), ket: `${b.mulai}–${b.selesai}${kasir() || !b.bayar ? '' : ' · ' + rp(b.bayar)}`, akhir: ui.status(b.status), aksi: 'slip', arg: b.kode, datar: true })).join('') : `<p class="redup">${p.booking} booking sebelum November. Tampil lengkap di versi asli.</p>`}</div>`
        + (kasir() ? '' : `<div class="tumpuk rapat"><span class="label">Riwayat pembayaran</span>${(() => { const t = D.transaksi.filter(r => r[2] === p.nama); return t.length ? ui.kv(t.map(r => [r[1], `<span class="num">${rp(r[4])}</span> · ${esc(r[5])} · ${{ sukses: 'berhasil', pending: 'menunggu', gagal: 'gagal', refund: 'direfund' }[r[6]]}`])) : '<p class="redup">Belum ada pembayaran online bulan ini.</p>'; })()}</div>`),
      kaki: wa(p.hp, false) + ui.btn('Buat booking', { v: 'utama', ikon: 'tambah', aksi: 'catat', arg: `pel|${p.nama}` }),
    });
  }

  BQ.aksi = Object.assign(BQ.aksi || {}, {
    'pilih-tanggal': el => { const t = el.closest('.tumpuk').querySelector('.tgl-mini'); if (t) t.hidden = !t.hidden; },
    slip: el => { const x = BQ.booking(el.dataset.arg); if (x) BQ.laci({ judul: x.kode, isi: slipIsi(x), kaki: slipKaki(x) }); },
    'menu-slip': el => {
      const x = BQ.booking(el.dataset.arg);
      BQ.menu(el, [
        ...(x.status === 'paid' && x.fase === 'nanti' && !x.refund ? [] : [{ label: 'Pindah jadwal', ikon: 'kalender', aksi: 'pindah', arg: x.kode }]),
        { label: 'Kirim ulang link', ikon: 'pesan', aksi: 'toast', arg: 'Link booking dikirim ulang' },
        '-',
        kasir() ? { label: 'Ajukan pembatalan', ikon: 'tutup', bahaya: true, aksi: 'batal', arg: x.kode } : { label: 'Batalkan booking', ikon: 'tutup', bahaya: true, aksi: 'batal', arg: x.kode },
      ]);
    },
    pindah: el => {
      const x = BQ.booking(el.dataset.arg);
      BQ.dialog({
        judul: 'Pindah jadwal', isi: `<p>${esc(x.nama)} · ${esc(lay(x))}. Sekarang ${esc(x.tgl)}, ${x.mulai}–${x.selesai}.</p>`
          + `<div class="tumpuk rapat"><span class="label">Tanggal baru</span>${ui.segmen([{ label: '17 Nov' }, { label: '18 Nov', on: true }, { label: '19 Nov' }, { label: 'Lainnya' }])}</div>`
          + `<div class="tumpuk rapat"><span class="label">Jam mulai</span>${ui.chipJam(['09.00', '11.00', '13.00', '15.00', '17.00'], '15.00', ['11.00'])}</div>`
          + `<p class="redup">Harga slot baru sama: tidak ada selisih yang perlu dibayar atau dikembalikan. Perubahan dicatat di riwayat.</p>`,
        kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Pindahkan ke 18 Nov, 15.00', { v: 'utama', aksi: 'pindah-simpan', arg: 'Jadwal dipindah ke 18 Nov, 15.00' }),
      });
    },
    batal: el => {
      const x = BQ.booking(el.dataset.arg), k = kasir();
      BQ.dialog({
        judul: k ? 'Ajukan pembatalan' : 'Batalkan booking ini?',
        isi: `<p>${esc(x.nama)} · ${esc(x.tgl)}, ${x.mulai}–${x.selesai}. ${k ? 'Pemilik atau Manajer yang memutuskan.' : x.sumber === 'Online' ? `Pelanggan mendapat refund penuh ${rp(x.bayar)}. Admin BookQu mentransfernya.` : 'Slot terbuka lagi untuk pelanggan lain.'}</p>`
          + ui.bidang({ id: 'al', label: 'Alasan', wajib: true, petunjuk: 'Tercatat di riwayat booking.' }, ui.teksarea({ id: 'al', ph: 'Misalnya: studio ditutup karena perbaikan' })),
        kaki: ui.btn('Kembali', { aksi: 'tutup' }) + ui.btn(k ? 'Kirim pengajuan' : 'Batalkan booking', { v: k ? 'utama' : 'bahaya-isi', aksi: 'toast', arg: k ? 'Pengajuan dikirim ke Pemilik' : 'Booking dibatalkan' }),
      });
    },
    refund: el => {
      const x = BQ.booking(el.dataset.arg);
      BQ.dialog({
        judul: 'Putuskan refund', isi: `<p>${esc(x.nama)} membayar ${rp(x.bayar)} untuk ${esc(x.tgl)}, ${x.mulai}. Alasan: "${esc(x.refund.alasan)}"</p>`
          + ui.radioKartu('rf', [{ nilai: 'p', judul: 'Terima 100%', ket: `Refund ${rp(x.bayar)}` }, { nilai: 's', judul: 'Terima 50%', ket: `Refund ${rp(Math.floor(x.bayar / 2))}` }, { nilai: 't', judul: 'Tolak', ket: 'Booking tetap berlaku' }], 'p')
          + `<p class="redup kecil">Menerima refund membatalkan booking dan membuka slotnya lagi.</p>`,
        kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Kirim keputusan', { v: 'utama', aksi: 'toast', arg: 'Refund 100% diterima' }),
      });
    },
    catat: el => catat(el && el.dataset ? el.dataset.arg : ''),
    'ganti-jam': el => { const b = document.querySelector('.lap-isi .galat-blok'); if (b) b.remove(); BQ.toast(`Jam diganti ke ${el.dataset.arg}. Periksa lalu simpan lagi.`); },
    'pindah-simpan': el => BQ.S.state === 'galat' ? bentrok('15.00', ['17.00', '19.00']) : (BQ.tutup(), BQ.toast(el.dataset.arg)),
    'catat-simpan': () => {
      const n = document.getElementById('c2');
      if (n && !n.value.trim()) return BQ.galat('c2', 'Isi nama pelanggan.');
      if (BQ.S.state === 'galat') return bentrok('13.00', ['15.00', '17.00']);
      BQ.tutup(); BQ.toast('Booking BKG-8RT2QA dicatat', { label: 'Lihat', aksi: 'slip', arg: 'BKG-NADTHW' });
    },
    pelanggan: el => pelanggan(el.dataset.arg),
    sapa: el => {
      const p = BQ.pel(el.dataset.arg), depan = p.nama.split(' ')[0];
      BQ.dialog({
        judul: `Sapa ${p.nama}`, isi: `<p class="redup">Terakhir datang ${esc(p.terakhir)}. Pesan dibuka di WhatsApp Anda; tidak ada yang terkirim otomatis.</p>`
          + ui.bidang({ id: 'sp', label: 'Pesan', petunjuk: 'Ubah sesuai gaya Anda.' }, ui.teksarea({ id: 'sp', nilai: `Halo Kak ${depan}, sudah lama tidak mampir ke ${D.bisnis.nama}. Minggu ini masih ada jadwal kosong hari Rabu dan Jumat. Mau kami siapkan? Booking di ${D.bisnis.link}` })),
        kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Buka WhatsApp', { v: 'utama', ikon: 'pesan', aksi: 'toast', arg: `Membuka WhatsApp ${p.hp}` }),
      });
    },
  });
  /* saran pelanggan lama baru muncul setelah 2 huruf diketik */
  document.addEventListener('input', e => { if (e.target.id === 'c2') { const s = document.querySelector('#lapis .saran'); if (s) s.hidden = e.target.value.trim().length < 2; } });
})();
