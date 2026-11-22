/* Langkah 3, penyiapan: Layanan (§8.6), Unit (§8.7), Jam Buka & Harga (§8.8). */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const TINGKAT = { small: 1, medium: 2, pro: 3 };
  const minimal = (S, p) => TINGKAT[S.plan] >= TINGKAT[p];
  const runway = l => !l.aktif ? '<span class="redup">Nonaktif</span>'
    : l.slotSampai === '20 Nov' ? `${ui.lencana('Sampai 20 Nov', 'tunggu')}` : `<span>Sampai ${l.slotSampai}</span>`;

  /* ---------- Layanan ---------- */
  function formLayanan(id) {
    const l = BQ.layanan(id) || {};
    const baru = !l.id;
    BQ.laci({
      judul: baru ? 'Tambah layanan' : 'Ubah layanan',
      isi: (baru ? '' : ui.kesimpulan('Harga dan durasi baru hanya berlaku untuk booking baru. Booking yang sudah ada tetap memakai yang lama.'))
        + ui.bidang({ id: 'l1', label: 'Nama layanan', wajib: true }, ui.input({ id: 'l1', nilai: l.nama || '' }))
        + ui.bidang({ id: 'l2', label: 'Kategori' }, ui.pilih({ id: 'l2', nilai: l.kategori, opsi: ['Tanpa kategori', ...D.kategori.map(k => k.nama)] }))
        + `<div class="form-2">${ui.bidang({ id: 'l3', label: 'Harga dasar', wajib: true }, ui.input({ id: 'l3', nilai: l.harga ? l.harga.toLocaleString('id-ID') : '', awalan: 'Rp' }))}${ui.bidang({ id: 'l4', label: 'Satuan harga', petunjuk: 'Tampil di halaman booking.' }, ui.pilih({ id: 'l4', opsi: ['per sesi', 'per jam', 'per orang'] }))}</div>`
        + `<div class="form-2">${ui.bidang({ id: 'l5', label: 'Durasi', wajib: true, petunjuk: '5 sampai 480 menit.' }, ui.input({ id: 'l5', nilai: l.durasi || 60, akhiran: 'menit' }))}${ui.bidang({ id: 'l6', label: 'Kapasitas per slot', petunjuk: '1 = satu pelanggan per slot.' }, ui.input({ id: 'l6', nilai: l.kapasitas || 1, akhiran: 'orang' }))}</div>`
        + (minimal(BQ.S, 'pro') ? `<div class="tumpuk rapat"><span class="label">Unit yang dipakai</span>${D.unit.map(u => ui.centang(u.nama, { on: (l.unit || []).includes(u.id) })).join('')}<span class="petunjuk">Dua booking tidak bisa memakai unit yang sama pada jam yang sama.</span></div>` : '')
        + ui.bidang({ id: 'l7', label: 'Deskripsi', petunjuk: 'Paling banyak 1000 karakter.' }, ui.teksarea({ id: 'l7', nilai: baru ? '' : 'Studio lengkap dengan peralatan profesional dan operator.' }))
        + `<div class="tumpuk rapat">${ui.sakelar({ judul: 'Populer', ket: 'Tampil dengan lencana di halaman booking.', on: l.populer })}${ui.sakelar({ judul: 'Aktif', ket: 'Layanan nonaktif tidak bisa dipesan.', on: baru || l.aktif })}</div>`,
      kaki: (baru ? '' : ui.btn('Hapus', { v: 'bahaya', aksi: 'hapus-layanan', arg: l.nama })) + ui.btn(baru ? 'Simpan layanan' : 'Simpan perubahan', { v: 'utama', aksi: 'toast', arg: 'Layanan tersimpan' }),
    });
  }

  BQ.pages.layanan = {
    render({ tab, q, S }) {
      tab = tab || 'layanan';
      const tabs = ui.tabs('layanan', [{ k: 'layanan', label: 'Layanan', jml: D.layanan.length }, { k: 'kategori', label: 'Kategori', jml: D.kategori.length }, { k: 'addon', label: 'Tambahan', jml: D.addon.length }], tab) + ui.cat('B', 'K3 Add-on → Tambahan');
      const batas = { small: 5, medium: 20 }[S.plan];
      let isi;
      if (tab === 'kategori') {
        isi = ui.panel({ rata: true, isi: ui.tabel({
          kolom: [{ k: 'nama', label: 'Kategori', utama: true, l: 'minmax(180px, 1fr)', hp: 'utama' }, { k: 'ket', label: 'Keterangan', l: 'minmax(0, 2fr)', bungkus: true, hp: 'sub' }, { k: 'jml', label: 'Layanan', l: 'auto', kanan: true, nowrap: true, hp: 'akhir' }],
          baris: D.kategori.map(k => ({ aksi: 'form-kategori', arg: k.nama, sel: { nama: `<span class="baris">${ui.titikWarna(k.warna)}<span>${esc(k.nama)}</span></span>`, ket: `<span class="potong redup">${esc(k.ket)}</span>`, jml: `<a href="#/layanan?kategori=${k.nama}">${k.jumlah} layanan</a>` } })),
        }) + ui.hal('Seret untuk mengubah urutan di halaman booking. Kategori hanya bisa dihapus bila kosong.', false) });
      } else if (tab === 'addon') {
        isi = !minimal(S, 'medium') ? ui.panel({ isi: ui.kunci({ judul: 'Tambahan tersedia mulai paket Medium', teks: 'Jual barang atau jasa tambahan saat pelanggan booking, misalnya makeup artist atau cetak foto.', paket: 'Medium' }) })
          : ui.panel({ rata: true, isi: ui.tabel({
            kolom: [{ k: 'nama', label: 'Tambahan', utama: true, hp: 'utama' }, { k: 'harga', label: 'Harga', l: 'auto', kanan: true, hp: 'akhir' }, { k: 'stok', label: 'Stok', l: 'auto', nowrap: true }, { k: 'lay', label: 'Dipakai di', l: 'auto', nowrap: true }, { k: 'jual', label: 'Terjual', l: 'auto', kanan: true }],
            baris: D.addon.map(a => ({ aksi: 'form-tambahan', arg: a.nama, redup: !a.aktif, hpSub: `${a.stok === 0 ? 'Habis · ' : ''}Dipakai di ${a.layanan} layanan · ${a.terjual} terjual${a.aktif ? '' : ' · Nonaktif'}`, sel: {
              nama: `${esc(a.nama)}${a.aktif ? '' : ' <span class="redup">· Nonaktif</span>'}`, harga: `<span class="num">${rp(a.harga)}</span>`,
              /* stok 0 diberi tanda (review 8.6 no. 4) */
              stok: a.stok === null ? '<span class="redup">Tanpa batas</span>' : a.stok === 0 ? ui.lencana('Habis', 'bahaya', 1) : `<span class="num">${a.stok} tersisa</span>`,
              lay: `${a.layanan} layanan`, jual: `<span class="num">${a.terjual}</span>`,
            } })),
          }) + ui.hal('Stok berkurang saat booking dibayar. Tambahan yang habis tidak bisa dipilih pelanggan.', false) });
      } else {
        const kisi = q.get('tampil') === 'kisi';
        const daftar = D.layanan;
        const alat = ui.alat(ui.segmen([{ label: 'Daftar', href: '#/layanan', on: !kisi }, { label: 'Kisi bergambar', href: '#/layanan?tampil=kisi', on: kisi }], { label: 'Tampilan' }), ui.cat('A', '8.6 no. 1, 2, 4 · K9 K10') + ui.cat('B', '8.6 no. 3'),
          `<span class="dorong">${batas ? ui.kuota({ label: 'Layanan', pakai: Math.min(daftar.length, batas), batas }) : '<span class="redup kecil">Tanpa batas jumlah layanan di paket Pro</span>'}</span>`);
        const lencana = l => [l.populer ? ui.lencana('Populer', 'pilih', 1) : '', !l.aktif ? ui.lencana('Nonaktif', 'batal', 1) : ''].join('');
        /* lapis 2 tabel: kategori, kapasitas grup, dan pengecualian slot/nonaktif (K9, K10, M6) */
        const ket = l => [esc(l.kategori), l.kapasitas > 1 ? `maks. ${l.kapasitas} orang` : '', !l.aktif ? 'Nonaktif' : l.slotSampai !== '14 Des' ? `<span class="teks-kunyit">Slot hanya sampai ${l.slotSampai}</span>` : ''].filter(Boolean).join(' · ');
        const unit = l => l.unit.length ? esc(BQ.unit(l.unit[0]).nama) + (l.unit.length > 1 ? ` <span class="redup">+${l.unit.length - 1}</span>` : '') : '<span class="redup">—</span>';
        isi = alat + (kisi
          ? ui.kisiKartu(daftar.map(l => ui.kartu({ aksi: 'ubah-layanan', arg: l.id, gambar: l.gambar || 'kosong', mati: !l.aktif, lencana: lencana(l), judul: l.nama, ket: `${esc(l.kategori)} · ${ui.durasi(l.durasi)} · ${l.kapasitas > 1 ? `${l.kapasitas} orang` : '1 orang'}`, isi: `<b class="num">${rp(l.harga)} <span class="redup kecil">${l.satuan}</span></b>`, kaki: `Slot terbuka<span class="dorong">${runway(l)}</span>` })).join(''))
          : ui.panel({ rata: true, isi: ui.tabel({
            catatan: ui.catatanTabel('Slot semua layanan terbuka sampai 14 Des, kecuali yang ditandai.', ['Atur slot', '#/jam-buka/slot']),
            kolom: [
              { k: 'nama', label: 'Layanan', utama: true, l: 'minmax(240px, 2fr)', hp: 'utama' },
              { k: 'harga', label: 'Harga', l: 'auto', kanan: true, hp: 'akhir' },
              { k: 'durasi', label: 'Durasi', l: 'auto', nowrap: true },
              ...(minimal(S, 'pro') ? [{ k: 'unit', label: 'Unit', l: 'minmax(0, 1fr)' }] : []),
              { k: 'm', label: '', aksi: true },
            ],
            baris: daftar.map(l => ({ aksi: 'ubah-layanan', arg: l.id, redup: !l.aktif, hpSub: `${ket(l)} · ${ui.durasi(l.durasi)}`, sel: {
              nama: ui.dua(esc(l.nama), ket(l)),
              harga: `<span class="num">${rp(l.harga)}</span>`, durasi: ui.durasi(l.durasi),
              unit: `<span class="potong" title="${esc(l.unit.map(u => BQ.unit(u).nama).join(', '))}">${unit(l)}</span>`,
              m: ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Menu layanan'),
            } })),
          }) }));
      }
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Layanan', tujuan: 'Yang Anda jual: harga, durasi, kapasitas dan tambahan.', aksi: tab === 'layanan' ? ui.btn('Tambah layanan', { v: 'utama', ikon: 'tambah', aksi: 'ubah-layanan', mati: S.state === 'berakhir' }) : tab === 'kategori' ? ui.btn('Tambah kategori', { v: 'utama', ikon: 'tambah', aksi: 'form-kategori' }) : minimal(S, 'medium') ? ui.btn('Tambah tambahan', { v: 'utama', ikon: 'tambah', aksi: 'form-tambahan' }) : '' })}
        ${tabs}${isi}
      </div>`;
    },
  };

  /* ---------- Unit ---------- */
  BQ.pages.unit = {
    render({ S }) {
      const kepala = ui.kepala({ judul: 'Unit', tujuan: 'Ruang, lapangan atau alat yang dipakai layanan Anda. Dua booking tidak bisa memakai unit yang sama pada jam yang sama.', aksi: minimal(S, 'pro') ? ui.btn('Tambah unit', { v: 'utama', ikon: 'tambah', aksi: 'form-unit' }) : '' });
      if (!minimal(S, 'pro')) return `<div class="view-in">${kepala}${ui.panel({ isi: ui.kunci({ judul: 'Unit tersedia di paket Pro', teks: 'Contoh: "Paket Turnamen" dan "Sewa Lapangan A" memakai lapangan yang sama. Dengan unit, keduanya tidak bisa dibooking pada jam yang sama.', paket: 'Pro' }) })}</div>`;
      const k = D.konflik.mendatang[0];
      return `<div class="view-in">${kepala}
        ${ui.panel({ judul: 'Konflik mendatang', cat: ui.cat('B', '8.7 no. 1') + ui.cat('A', '8.7 no. 2'), ket: 'Dua booking yang sudah memakai unit yang sama pada jam yang sama. Pindahkan salah satunya.', rata: true,
          isi: ui.lr({ awal: `<span class="hitung">1</span>`, judul: `${k.tgl} · ${esc(BQ.unit(k.unit).nama)}`, ket: `${k.a[0]} ${esc(k.a[1])} dan ${k.b[0]} ${esc(k.b[1])}`, akhir: ui.btn('Lihat booking', { kecil: true, href: '#/booking/mendatang' }) })
            + ui.lipat(`${D.konflik.lampau} konflik lampau`, 'Sesi sudah lewat, tidak bisa ditindak lagi. Disimpan untuk catatan.') })}
        ${ui.panel({ rata: true, isi: ui.tabel({
          kolom: [
            { k: 'nama', label: 'Unit', utama: true, hp: 'utama' },
            { k: 'kap', label: 'Kapasitas', l: 'auto', nowrap: true },
            { k: 'lok', label: 'Lokasi', l: 'auto', nowrap: true },
            { k: 'lay', label: 'Layanan', l: 'auto', nowrap: true },
            { k: 'pakai', label: 'Terpakai hari ini', l: 'auto', hp: 'akhir' },
          ],
          baris: D.unit.map(u => {
            const lay = D.layanan.filter(l => l.unit.includes(u.id));
            return { aksi: 'form-unit', arg: u.id, hpSub: `${esc(u.jenis)} · ${esc(u.lokasi)} · ${lay.length} layanan`, sel: {
              nama: ui.dua(esc(u.nama), esc(u.jenis)), kap: `${u.kapasitas} orang`, lok: esc(u.lokasi),
              lay: `<span title="${esc(lay.map(l => l.nama).join(', '))}">${lay.length} layanan</span>`,
              /* "0 dari 14 jam" dengan batang kosong tetap terlihat (review 8.7 no. 2) */
              pakai: u.ditutup ? `<span class="redup">Ditutup ${u.ditutup} jam</span>` : ui.batangMini(u.pakai, D.jamBukaHari, 'jam'),
            } };
          }),
        }) })}
      </div>`;
    },
  };

  /* ---------- Jam Buka & Harga ---------- */
  const stSlot = { kosong: ['Kosong', 'lunas'], terisi: ['Terisi', 'pilih'], tutup: ['Ditutup', 'batal'], lewat: ['Lewat', 'netral'] };
  function bukaSlotSekaligus() {
    BQ.dialog({
      judul: 'Buka slot sekaligus',
      isi: ui.bidang({ id: 'b1', label: 'Layanan', wajib: true }, ui.pilih({ id: 'b1', nilai: 'Creative Music Video (Half Day)', opsi: D.layanan.map(l => l.nama) }))
        + `<div class="tumpuk rapat"><span class="label">Tanggal</span>${ui.segmen([{ label: 'Satu hari' }, { label: 'Rentang', on: true }])}</div>`
        + `<div class="form-2">${ui.bidang({ id: 'b2', label: 'Dari' }, ui.input({ id: 'b2', nilai: '21 Nov 2026' }))}${ui.bidang({ id: 'b3', label: 'Sampai', petunjuk: 'Paling jauh 60 hari.' }, ui.input({ id: 'b3', nilai: '14 Des 2026' }))}</div>`
        + `<div class="form-2">${ui.bidang({ id: 'b4', label: 'Jam mulai' }, ui.input({ id: 'b4', nilai: '08.00' }))}${ui.bidang({ id: 'b5', label: 'Jam selesai' }, ui.input({ id: 'b5', nilai: '22.00' }))}</div>`
        + `<div class="baris">${ui.btn('Isi dari jam buka', { kecil: true, ikon: 'jam' })}<span class="redup kecil">Jeda istirahat tetap dilewati.</span></div>`
        + ui.bidang({ id: 'b6', label: 'Jarak antar-slot', petunjuk: 'Bawaan sama dengan durasi layanan (240 menit).' }, ui.input({ id: 'b6', nilai: '240', akhiran: 'menit' }))
        + ui.kesimpulan('42 slot baru akan dibuka. 6 slot pada rentang ini sudah ada dan tidak digandakan. Belum ada yang tersimpan.'),
      kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Buka 42 slot', { v: 'utama', aksi: 'toast', arg: '42 slot dibuka sampai 14 Des' }),
    });
  }

  BQ.pages['jam-buka'] = {
    render({ tab, S }) {
      tab = tab || 'jam';
      const L7 = BQ.layanan('L7');
      const banner = S.state === 'berakhir' ? '' : ui.banner({ nada: 'perlu', judul: `${L7.nama}: slot hanya sampai 20 Nov`, teks: 'Layanan ini tidak ikut slot otomatis. Pelanggan tidak bisa booking setelah tanggal itu.', aksi: ui.btn('Buka slot', { kecil: true, aksi: 'buka-slot' }) });
      const ringkas = ui.panel({ isi: ui.angka({ utama: { nilai: D.bisnis.slotSampai, label: 'slot terbuka sampai', ket: '9 dari 10 layanan, dibuka otomatis setiap hari' }, lain: [{ nilai: '28 hari', label: 'ke depan' }, { nilai: '1', label: 'layanan kurang dari 7 hari' }] }) + ui.cat('C', 'fitur 1 · ditunda D-021') });
      const tabs = ui.tabs('jam-buka', [{ k: 'jam', label: 'Jam buka' }, { k: 'harga', label: 'Harga' }, { k: 'libur', label: 'Libur & tutup' }, { k: 'slot', label: 'Slot' }], tab) + ui.cat('A', '8.8 no. 3–5');
      let isi;
      if (tab === 'harga') {
        isi = `<div class="kisi-2"><div class="tumpuk">
          ${ui.panel({ judul: 'Akhir pekan', ket: 'Berlaku Sabtu dan Minggu, di atas harga dasar dari Layanan.', isi: `<div class="tumpuk">${ui.segmen([{ label: 'Tanpa aturan' }, { label: 'Dikali', on: true }, { label: 'Harga tetap' }], { label: 'Jenis aturan' })}${ui.bidang({ id: 'h1', label: 'Pengali', petunjuk: '1,2 = harga dasar ditambah 20%. 1 = sama dengan hari biasa.' }, ui.input({ id: 'h1', nilai: '1,2', akhiran: '× harga dasar' }))}</div>` })}
          ${ui.panel({ judul: 'Jam ramai', ket: 'Tambahan harga pada jam tertentu.', aksi: ui.btn('Tambah', { kecil: true, ikon: 'tambah', aksi: 'form-jamramai' }), rata: true, isi: ui.tabel({ kolom: [{ k: 'n', label: 'Nama', l: 'minmax(0,1fr)', hp: 'utama' }, { k: 'w', label: 'Kapan', l: 'minmax(0,1.2fr)', hp: 'sub' }, { k: 't', label: 'Tambahan', l: '100px', kanan: true, hp: 'akhir' }, { k: 'l', label: 'Layanan', l: 'minmax(0,1fr)' }], baris: D.jamRamai.map(r => ({ aksi: 'form-jamramai', arg: r.nama, sel: { n: esc(r.nama), w: `${r.hari} · <span class="jam">${r.jam}</span>`, t: `<span class="num">+${rp(r.tambah)}</span>`, l: `<span class="redup">${esc(r.layanan)}</span>` } })) }) })}
          ${ui.panel({ judul: 'Tanggal khusus', ket: 'Menggantikan harga pada tanggal tertentu.', cat: ui.cat('A', '8.8 no. 2'), aksi: ui.btn('Tambah', { kecil: true, ikon: 'tambah', aksi: 'form-tglkhusus' }), rata: true, isi: ui.tabel({ kolom: [{ k: 't', label: 'Tanggal', l: '120px', hp: 'utama' }, { k: 'h', label: 'Harga', l: 'minmax(0,1fr)', hp: 'akhir' }, { k: 'l', label: 'Layanan', l: 'minmax(0,1.2fr)', hp: 'sub' }], baris: D.tanggalKhusus.map(r => ({ aksi: 'form-tglkhusus', arg: r.tgl, sel: { t: esc(r.tgl), h: esc(r.harga), l: `<span class="redup">${esc(r.layanan)}</span>` } })) }) })}
        </div>
        ${(() => { const sim = `<div class="tumpuk sim">
          ${ui.bidang({ id: 'sm1', label: 'Layanan' }, ui.pilih({ id: 'sm1', nilai: 'Studio Podcast Multi-Mic', opsi: D.layanan.map(l => l.nama) }))}
          ${ui.bidang({ id: 'sm2', label: 'Tanggal' }, ui.input({ id: 'sm2', nilai: 'Sabtu, 21 Nov 2026' }))}
          ${ui.kv([['08.00', `<b class="num">Rp435.000</b><br><span class="redup kecil">Akhir pekan ×1,2 · Pagi akhir pekan +Rp75.000</span>`], ['10.00', `<b class="num">Rp435.000</b><br><span class="redup kecil">Akhir pekan ×1,2 · Pagi akhir pekan +Rp75.000</span>`], ['13.00', `<b class="num">Rp360.000</b><br><span class="redup kecil">Akhir pekan ×1,2</span>`], ['18.00', `<b class="num">Rp410.000</b><br><span class="redup kecil">Akhir pekan ×1,2 · Malam kerja tidak berlaku Sabtu</span>`]])}
          ${ui.btn('Terapkan harga dasar ke slot terbuka', { ikon: 'cek', aksi: 'konfirmasi-harga' })}</div>`;
          /* HP: simulasi dilipat supaya page tidak terlalu panjang (keterbacaan) */
          return `<div class="hanya-desktop">${ui.panel({ judul: 'Simulasi harga', ket: 'Harga tiap jam dan aturan yang membentuknya.', isi: sim })}</div>`
            + `<div class="hanya-hp">${ui.panel({ rata: true, isi: ui.lipat('Simulasi harga per jam', sim.replace(/"sm(\d)"/g, '"smh$1"')) })}</div>`; })()}
        </div>`;
      } else if (tab === 'libur') {
        isi = ui.panel({ rata: true, isi: ui.tabel({
          kolom: [{ k: 't', label: 'Tanggal', utama: true, l: 'minmax(200px, 1fr)', hp: 'utama' }, { k: 'a', label: 'Alasan', l: 'minmax(0, 2fr)', bungkus: true, hp: 'sub' }, { k: 'm', label: '', aksi: true, hp: 'akhir' }],
          baris: D.libur.map(r => ({ sel: { t: esc(r.tgl), a: `<span class="potong">${esc(r.alasan)}</span>`, m: r.tetap ? '' : ui.aksiIkon('Lainnya', 'lagi', 'menu-libur') } })),
        }) + ui.hal('Libur mingguan diatur di tab Jam buka.', false) });
      } else if (tab === 'slot') {
        isi = ui.panel({ rata: true, isi: ui.filter({ cari: 'Cari jam', isi: ui.pilih({ label: 'Layanan', nilai: 'Studio Podcast Multi-Mic', opsi: D.layanan.map(l => l.nama) }) + ui.tglNav('16–22 Nov') })
          + ui.tabel({
            kolom: [{ k: 'jam', label: 'Jam', l: 'auto', nowrap: true, hp: 'utama' }, { k: 'st', label: 'Status', l: 'minmax(200px, 1fr)', hp: 'sub' }, { k: 'h', label: 'Harga', l: 'minmax(0, 1.4fr)', hp: 'akhir' }, { k: 'm', label: '', aksi: true }],
            baris: D.slotMinggu.flatMap(([tgl, jam, h, st, siapa, aturan], i, semua) => [
              ...(i === 0 || semua[i - 1][0] !== tgl ? [{ grup: `${tgl === '16 Nov' ? 'Hari ini · ' : tgl === '17 Nov' ? 'Besok · ' : ''}${BQ.bersama.tglPanjang(tgl).replace(/ 2026$/, '')}` }] : []),
              { redup: st === 'lewat', hpSub: `${stSlot[st][0]}${siapa ? ' · ' + esc(siapa) : ''}${aturan ? ' · ' + esc(aturan) : ''}`, sel: {
              jam: `<span class="jam">${jam}</span>`,
              h: `<span class="num">${rp(h)}</span>${aturan ? ` <span class="redup">· ${esc(aturan)}</span>` : ''}`,
              st: st === 'lewat' ? 'Lewat' : st === 'kosong' ? 'Kosong' : ui.titikStatus(stSlot[st][0] + (siapa ? ` · ${siapa}` : ''), st === 'terisi' ? 'lilak' : 'bata'),
              /* baris hanya berisi data + menu "⋯" (review 8.8 no. 4) */
              m: st === 'kosong' || st === 'tutup' ? ui.aksiIkon('Lainnya', 'lagi', 'menu-slot', st) : '',
            } }]),
          }) + ui.hal('10 dari 38 slot minggu ini') });
      } else {
        isi = ui.panel({ rata: true, isi:
          ui.formBagian('Slot otomatis', 'Sistem membuka slot dari jam buka setiap hari. Anda hanya mengatur pengecualian.',
            `${ui.sakelar({ judul: 'Buka slot otomatis', ket: 'Slot baru dibuka setiap malam sampai batas di bawah. Durasi diambil dari Layanan.', on: true })}
             ${ui.bidang({ id: 'j1', label: 'Buka slot sampai', petunjuk: 'Pelanggan bisa booking paling jauh 28 hari ke depan.' }, ui.input({ id: 'j1', nilai: '28', akhiran: 'hari ke depan' }))}
             <span class="redup kecil">9 dari 10 layanan ikut. ${ui.cat('C', 'fitur 1 · ditunda D-021')} <a href="#/layanan">Atur per layanan</a></span>`)
          + ui.formBagian('Jam buka mingguan', 'Satu baris per hari. Tambah jeda untuk istirahat.',
            `<div class="panel hanya-desktop">${ui.jamBuka(D.jamBuka)}</div><div class="panel hanya-hp">${ui.jamBukaHp(D.jamBuka)}</div><div>${ui.btn('Salin Senin ke semua hari', { kecil: true, ikon: 'salin', aksi: 'toast', arg: 'Jam Senin disalin ke Selasa–Sabtu' })}</div>`)
          + ui.simpanBilah('Simpan jam buka', '') });
      }
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Jam Buka & Harga', tujuan: 'Kapan Anda buka, berapa harganya, dan kapan tutup.', aksi: tab === 'slot' ? ui.btn('Buka slot sekaligus', { v: 'utama', ikon: 'tambah', aksi: 'buka-slot', mati: S.state === 'berakhir' }) : tab === 'libur' ? ui.btn('Tutup tanggal', { v: 'utama', ikon: 'tambah', aksi: 'tutup-tanggal' }) : '' })}
        ${banner}${ringkas}${tabs}${isi}
      </div>`;
    },
  };

  /* ---------- Formulir laci (sesi perbaikan 2) ---------- */
  const laci = (judul, isi, simpan, hapus) => BQ.laci({ judul, isi, kaki: (hapus || '') + ui.btn(simpan, { v: 'utama', aksi: 'toast', arg: 'Tersimpan' }) });
  const HARI = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
  const FORM = {
    'form-unit': el => {
      const u = BQ.unit(el.dataset.arg), baru = !u.id;
      laci(baru ? 'Tambah unit' : 'Ubah unit',
        ui.bidang({ id: 'u1', label: 'Nama unit', wajib: true, petunjuk: 'Paling banyak 100 karakter.' }, ui.input({ id: 'u1', nilai: u.nama || '', ph: 'Misalnya: Lapangan A' }))
        + `<div class="form-2">${ui.bidang({ id: 'u2', label: 'Jenis', wajib: true }, ui.input({ id: 'u2', nilai: u.jenis || '', ph: 'Ruang, lapangan atau alat' }))}${ui.bidang({ id: 'u3', label: 'Kapasitas', petunjuk: '1 sampai 9999 orang.' }, ui.input({ id: 'u3', nilai: u.kapasitas || 1, akhiran: 'orang' }))}</div>`
        + ui.bidang({ id: 'u4', label: 'Lokasi' }, ui.input({ id: 'u4', nilai: u.lokasi || '', ph: 'Misalnya: Lantai 2' }))
        + `<div class="tumpuk rapat"><span class="label">Dipakai oleh layanan</span>${D.layanan.map(l => ui.centang(l.nama, { on: !!u.id && l.unit.includes(u.id) })).join('')}</div>`
        + `<div class="tumpuk rapat"><span class="label">Gambar</span><div class="baris">${ui.btn('Pilih dari galeri', { kecil: true, ikon: 'gambar' })}<span class="redup kecil">Galeri yang sama dengan Halaman Booking.</span></div></div>`
        + ui.sakelar({ judul: 'Aktif', ket: 'Unit nonaktif tidak bisa dipakai booking baru.', on: true }),
        baru ? 'Simpan unit' : 'Simpan perubahan', baru ? '' : ui.btn('Hapus', { v: 'bahaya', aksi: 'hapus-unit', arg: u.nama }));
    },
    'hapus-unit': el => BQ.dialog({ judul: 'Unit ini belum bisa dihapus', isi: `<p>${esc(el.dataset.arg)} masih dipakai booking mendatang. Pindahkan booking itu ke unit lain, atau nonaktifkan unit ini.</p>`, kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Nonaktifkan', { v: 'utama', aksi: 'toast', arg: 'Unit dinonaktifkan' }) }),
    'form-kategori': el => {
      const k = D.kategori.find(x => x.nama === el.dataset.arg) || {}, baru = !k.nama;
      laci(baru ? 'Tambah kategori' : 'Ubah kategori',
        ui.bidang({ id: 'k1', label: 'Nama kategori', wajib: true }, ui.input({ id: 'k1', nilai: k.nama || '' }))
        + ui.bidang({ id: 'k2', label: 'Keterangan' }, ui.input({ id: 'k2', nilai: k.ket || '' }))
        + `<div class="tumpuk rapat"><span class="label">Warna label</span><div class="baris pilih-warna">${['terong', 'daun', 'bata', 'tinta', 'redup'].map(w => `<button type="button" aria-pressed="${w === (k.warna || 'terong')}" aria-label="Warna ${w}">${ui.titikWarna(w)}</button>`).join('')}</div></div>`
        + ui.bidang({ id: 'k3', label: 'Urutan di halaman booking', petunjuk: '1 = paling atas.' }, ui.input({ id: 'k3', nilai: baru ? D.kategori.length + 1 : D.kategori.indexOf(k) + 1 }))
        + ui.sakelar({ judul: 'Aktif', on: true })
        + (k.jumlah ? `<p class="redup kecil">Berisi ${k.jumlah} layanan. Pindahkan layanannya dulu untuk menghapus kategori ini. <a href="#/layanan">Lihat layanan</a></p>` : ''),
        baru ? 'Simpan kategori' : 'Simpan perubahan', baru ? '' : ui.btn('Hapus', { v: 'bahaya', mati: !!k.jumlah }));
    },
    'form-tambahan': el => {
      const a = D.addon.find(x => x.nama === el.dataset.arg) || {}, baru = !a.nama, tanpaBatas = a.stok === null;
      laci(baru ? 'Tambah tambahan' : 'Ubah tambahan',
        ui.bidang({ id: 'a1', label: 'Nama', wajib: true }, ui.input({ id: 'a1', nilai: a.nama || '', ph: 'Misalnya: Makeup artist' }))
        + ui.bidang({ id: 'a2', label: 'Harga', wajib: true }, ui.input({ id: 'a2', nilai: a.harga ? a.harga.toLocaleString('id-ID') : '', awalan: 'Rp' }))
        + `<div class="tumpuk rapat"><span class="label">Stok</span>${ui.segmen([{ label: 'Terbatas', on: !tanpaBatas }, { label: 'Tanpa batas', on: tanpaBatas }])}</div>`
        + ui.bidang({ id: 'a3', label: 'Jumlah stok', petunjuk: 'Berkurang saat booking dibayar. 0 = habis, tidak bisa dipilih pelanggan.' }, ui.input({ id: 'a3', nilai: tanpaBatas ? '' : (a.stok ?? 10), mati: tanpaBatas, akhiran: 'buah' }))
        + `<div class="tumpuk rapat"><span class="label">Bisa dipilih untuk</span>${D.layanan.slice(0, 5).map((l, i) => ui.centang(l.nama, { on: i < (a.layanan || 0) })).join('')}</div>`
        + ui.sakelar({ judul: 'Aktif', on: a.aktif !== false }),
        baru ? 'Simpan tambahan' : 'Simpan perubahan', baru ? '' : ui.btn('Hapus', { v: 'bahaya', aksi: 'toast', arg: 'Tambahan dihapus' }));
    },
    'form-jamramai': el => {
      const r = D.jamRamai.find(x => x.nama === el.dataset.arg) || {}, baru = !r.nama;
      const hari = r.hari === 'Sab' ? ['Sab'] : r.nama ? HARI.slice(0, 5) : [];
      const [dari, sampai] = (r.jam || '18.00–22.00').split('–');
      laci(baru ? 'Tambah jam ramai' : 'Ubah jam ramai',
        ui.bidang({ id: 'r1', label: 'Nama aturan', wajib: true }, ui.input({ id: 'r1', nilai: r.nama || '', ph: 'Misalnya: Malam kerja' }))
        + `<div class="tumpuk rapat"><span class="label">Hari</span><div class="baris">${HARI.map(h => ui.centang(h, { on: hari.includes(h) })).join('')}</div></div>`
        + `<div class="form-2">${ui.bidang({ id: 'r2', label: 'Dari' }, ui.input({ id: 'r2', nilai: dari }))}${ui.bidang({ id: 'r3', label: 'Sampai' }, ui.input({ id: 'r3', nilai: sampai }))}</div>`
        + ui.bidang({ id: 'r4', label: 'Tambahan harga', petunjuk: 'Ditambahkan ke harga slot pada jam itu.' }, ui.input({ id: 'r4', nilai: r.tambah ? r.tambah.toLocaleString('id-ID') : '', awalan: 'Rp' }))
        + ui.bidang({ id: 'r5', label: 'Berlaku untuk' }, ui.pilih({ id: 'r5', opsi: ['Semua layanan', ...D.layanan.map(l => l.nama)] })),
        baru ? 'Simpan aturan' : 'Simpan perubahan', baru ? '' : ui.btn('Hapus', { v: 'bahaya', aksi: 'toast', arg: 'Aturan dihapus' }));
    },
    'form-tglkhusus': el => {
      const t = D.tanggalKhusus.find(x => x.tgl === el.dataset.arg) || {}, baru = !t.tgl, persen = (t.harga || '').includes('%');
      laci(baru ? 'Tambah tanggal khusus' : 'Ubah tanggal khusus',
        ui.bidang({ id: 'd1', label: 'Tanggal', wajib: true }, ui.input({ id: 'd1', nilai: t.tgl || '' , ph: 'Misalnya: 24 Des 2026' }))
        + `<div class="tumpuk rapat"><span class="label">Harga</span>${ui.segmen([{ label: 'Harga tetap', on: !persen }, { label: 'Persen dari harga dasar', on: persen }])}</div>`
        + ui.bidang({ id: 'd2', label: persen ? 'Tambahan' : 'Harga', petunjuk: 'Menggantikan aturan akhir pekan dan jam ramai pada tanggal itu.' }, persen ? ui.input({ id: 'd2', nilai: '25', akhiran: '%' }) : ui.input({ id: 'd2', nilai: '1.000.000', awalan: 'Rp' }))
        + ui.bidang({ id: 'd3', label: 'Berlaku untuk' }, ui.pilih({ id: 'd3', nilai: t.layanan, opsi: ['Semua layanan', ...D.layanan.map(l => l.nama)] })),
        baru ? 'Simpan tanggal' : 'Simpan perubahan', baru ? '' : ui.btn('Hapus', { v: 'bahaya', aksi: 'toast', arg: 'Tanggal khusus dihapus' }));
    },
    'harga-slot': () => BQ.dialog({
      judul: 'Ubah harga slot ini',
      isi: '<p>Studio Podcast Multi-Mic · Selasa, 17 Nov, 09.00–11.00</p>'
        + ui.bidang({ id: 'hs1', label: 'Harga slot ini', petunjuk: 'Harga dasar Rp300.000. Hanya slot ini yang berubah; aturan lain tidak berlaku lagi untuk slot ini.' }, ui.input({ id: 'hs1', nilai: '250.000', awalan: 'Rp' })),
      kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Simpan harga', { v: 'utama', aksi: 'toast', arg: 'Harga slot diubah' }),
    }),
    'tutup-slot': () => BQ.dialog({
      judul: 'Tutup slot ini?',
      isi: '<p>Studio Podcast Multi-Mic · Selasa, 17 Nov, 09.00–11.00. Pelanggan tidak bisa memesan slot ini. Slot yang sudah dibooking tidak bisa ditutup.</p>'
        + ui.bidang({ id: 'ts1', label: 'Alasan (opsional)', petunjuk: 'Hanya terlihat oleh tim.' }, ui.input({ id: 'ts1', ph: 'Misalnya: perawatan alat' })),
      kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Tutup slot', { v: 'utama', aksi: 'toast', arg: 'Slot ditutup' }),
    }),
  };

  BQ.aksi = Object.assign(BQ.aksi || {}, FORM, {
    'ubah-layanan': el => formLayanan(el.dataset.arg),
    'hapus-layanan': el => BQ.dialog({ judul: 'Layanan ini tidak bisa dihapus', isi: `<p>${esc(el.dataset.arg)} sudah pernah dibooking, jadi hanya bisa dinonaktifkan. Layanan nonaktif tidak tampil di halaman booking; riwayatnya tetap ada.</p>`, kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Nonaktifkan', { v: 'utama', aksi: 'toast', arg: 'Layanan dinonaktifkan' }) }),
    'buka-slot': () => bukaSlotSekaligus(),
    'menu-slot': el => BQ.menu(el, el.dataset.arg === 'tutup' ? [{ label: 'Buka lagi', ikon: 'kunci', aksi: 'toast', arg: 'Slot dibuka lagi' }] : [{ label: 'Ubah harga slot ini', ikon: 'promo', aksi: 'harga-slot' }, { label: 'Tutup slot ini', ikon: 'kunci', aksi: 'tutup-slot' }, '-', { label: 'Hapus slot', ikon: 'tutup', bahaya: true, aksi: 'toast', arg: 'Slot dihapus' }]),
    'menu-libur': el => BQ.menu(el, [{ label: 'Ubah alasan', ikon: 'atur' }, '-', { label: 'Buka lagi tanggal ini', ikon: 'kunci', aksi: 'toast', arg: 'Tanggal dibuka lagi' }]),
    'tutup-tanggal': () => BQ.dialog({
      judul: 'Tutup tanggal',
      isi: ui.bidang({ id: 't1', label: 'Tanggal', wajib: true }, ui.input({ id: 't1', nilai: '21 Nov 2026' }))
        + ui.bidang({ id: 't2', label: 'Alasan', petunjuk: 'Pelanggan melihat alasan ini di halaman booking.' }, ui.input({ id: 't2', nilai: 'Acara keluarga' }))
        + ui.banner({ nada: 'bahaya', judul: '5 booking aktif pada 21 Nov', teks: 'Pindahkan atau batalkan semuanya dulu. Tanggal belum bisa ditutup.', aksi: ui.btn('Lihat booking', { kecil: true, href: '#/booking/mendatang' }) }),
      kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Tutup tanggal', { v: 'utama', mati: true }),
    }),
    'konfirmasi-harga': () => BQ.konfirmasi({ judul: 'Terapkan harga dasar?', teks: 'Harga 46 slot kosong mendatang Studio Podcast Multi-Mic menjadi Rp300.000. Slot yang sudah dibooking tidak berubah.', ya: 'Terapkan', hasil: 'Harga dasar diterapkan ke 46 slot' }),
  });
})();
