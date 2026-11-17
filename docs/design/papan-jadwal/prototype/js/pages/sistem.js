/* Halaman contoh: setiap komponen dengan varian dan keadaannya. Acuan tunggal gaya. */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp;
  const contoh = (judul, isi) => `<div class="contoh"><h3>${judul}</h3>${isi}</div>`;
  const warna = (nama, hex, peran, garis) => `<div><i style="background:${hex}${garis ? ';border-bottom:1px solid var(--garis)' : ''}"></i><b>${nama}</b><span>${hex} · ${peran}</span></div>`;

  function relHariIni() {
    const b = D.hariIni, k = D.slotKosongHariIni;
    const ket = x => `${BQ.layanan(x.layanan).nama} · ${BQ.unit(x.unit).nama}`;
    const item = x => ui.ri({
      waktu: `${x.mulai}–${x.selesai}`, nama: x.nama,
      ket: x.status === 'pending' ? `${BQ.layanan(x.layanan).nama} · bayar sebelum ${x.batas} (sisa ${x.sisa})` : ket(x),
      status: x.fase === 'kini' ? ui.lencana('Sedang berlangsung', 'pilih') : x.fase === 'lewat' ? ui.lencana('Belum ditutup', 'tunggu') : ui.status(x.status),
      tindak: x.fase === 'lewat' ? ui.btn('Tandai selesai', { kecil: true, aksi: 'toast', arg: 'Booking ditandai selesai' }) : '',
      nada: x.fase === 'kini' ? 'kini' : x.fase === 'lewat' ? 'lewat' : x.status === 'pending' ? 'tunggu' : '',
      aksi: 'contoh-laci', arg: x.kode,
    });
    const bebas = x => ui.ri({ waktu: `${x.mulai}–${x.selesai}`, nama: 'Kosong', ket: ket(x), nada: 'bebas', tindak: ui.btn('Catat booking', { kecil: true, v: 'polos', ikon: 'tambah', aksi: 'catat' }) });
    return ui.rel([
      { jam: '08', isi: [item(b[0])] }, { jam: '09', isi: [item(b[1])] }, { kini: D.kini.jam },
      { jam: '11', isi: [item(b[2])] }, { jam: '13', isi: [bebas(k[0])] }, { jam: '14', isi: [item(b[3])] },
      { jam: '15', isi: [bebas(k[1])] }, { jam: '16', isi: [item(b[4])] }, { jam: '18', isi: [item(b[5])] },
    ]);
  }

  const tabelBooking = () => ui.tabel({
    pilih: true,
    kolom: [
      { k: 'sesi', label: 'Sesi', l: '112px', hp: 'awal' },
      { k: 'nama', label: 'Pelanggan', l: 'minmax(0,1.1fr)', hp: 'utama' },
      { k: 'layanan', label: 'Layanan', l: 'minmax(0,1.6fr)', hp: 'sub' },
      { k: 'status', label: 'Status', l: '150px', hp: 'akhir' },
      { k: 'bayar', label: 'Dibayar', l: '110px', kanan: true },
    ],
    baris: D.hariIni.map((x, i) => ({
      aksi: 'contoh-laci', arg: x.kode, terpilih: i === 0,
      sel: {
        sesi: `<span class="jam">${x.mulai}–${x.selesai}</span>`,
        nama: ui.dua(ui.esc(x.nama), x.kode),
        layanan: `<span class="potong" style="display:block">${ui.esc(BQ.layanan(x.layanan).nama)}</span>`,
        status: ui.status(x.status),
        bayar: x.bayar ? rp(x.bayar) : '<span class="redup">Belum dibayar</span>',
      },
    })),
  });

  BQ.aksi = Object.assign(BQ.aksi || {}, {
    'contoh-laci': el => {
      const x = D.hariIni.find(b => b.kode === el.dataset.arg) || D.hariIni[2];
      const tunggu = x.status === 'pending';
      BQ.laci({
        judul: x.kode,
        isi: ui.slip({
          status: ui.status(x.status), sumber: x.sumber, jam: `${x.mulai}–${x.selesai}`,
          layanan: BQ.layanan(x.layanan).nama, ket: `${D.kini.tanggal} · ${BQ.unit(x.unit).nama} · 1 peserta`,
          kaki: tunggu ? `<div class="baris"><span>Bayar sebelum <b class="jam">${x.batas}</b></span><span class="dorong tebal num">${rp(x.tagihan)}</span></div>` : `<div class="baris"><span>${x.metode}</span><span class="dorong tebal num">${rp(x.bayar)}</span></div>`,
        })
          + `<div class="tumpuk"><span class="label">Pelanggan</span>${ui.lr({ awal: ui.avatar(x.nama), judul: ui.esc(x.nama), ket: x.hp, akhir: ui.btn('WhatsApp', { kecil: true, ikon: 'pesan' }) }).replace('class="lr"', 'class="lr" style="padding:0;border:0"')}</div>`
          + `<div class="tumpuk"><span class="label">Riwayat</span>${ui.kv([['10.21', 'Booking dibuat oleh pelanggan'], ['10.21', tunggu ? 'Menunggu pembayaran' : 'Pembayaran berhasil']])}</div>`,
        kaki: tunggu
          ? ui.btn('Lainnya', { ikon: 'lagi', aksi: 'contoh-menu' }) + ui.btn('Kirim ulang link bayar', { v: 'utama', aksi: 'toast', arg: 'Link bayar dikirim ulang' })
          : ui.btn('Lainnya', { ikon: 'lagi', aksi: 'contoh-menu' }) + ui.btn('Tandai selesai', { v: 'utama', aksi: 'toast', arg: 'Booking ditandai selesai' }),
      });
    },
    'contoh-dialog': () => BQ.dialog({ judul: 'Tutup tanggal', isi: ui.bidang({ id: 'd1', label: 'Tanggal', wajib: true }, ui.input({ id: 'd1', nilai: '25 Des 2026' })) + ui.bidang({ id: 'd2', label: 'Alasan', petunjuk: 'Pelanggan melihat alasan ini di halaman booking.' }, ui.input({ id: 'd2', nilai: 'Libur Natal' })), kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Tutup tanggal', { v: 'utama', aksi: 'toast', arg: 'Tanggal 25 Des ditutup' }) }),
    'contoh-konfirmasi': () => BQ.konfirmasi({ judul: 'Batalkan booking ini?', teks: 'Pelanggan mendapat refund penuh Rp450.000. Slot 14.00–16.00 terbuka lagi.', ya: 'Batalkan booking', bahaya: true, hasil: 'Booking dibatalkan' }),
    'contoh-lembar': () => BQ.aksi['filter-hp'](),
    'contoh-menu': el => BQ.menu(el, [{ label: 'Pindah jadwal', ikon: 'kalender' }, { label: 'Kirim ulang link', ikon: 'pesan' }, '-', { label: 'Batalkan booking', ikon: 'tutup', bahaya: true, aksi: 'contoh-konfirmasi' }]),
  });

  BQ.pages.sistem = {
    render() {
      const bulan = Array.from({ length: 30 }, (_, i) => {
        const n = i + 1;
        if (n === 5) return { n, tutup: 'Tutup: perawatan' };
        if (n > 24) return { n };
        const slot = [8, 5, 5, 5, 0, 8, 6][i % 7] || 6;
        return { n, slot, isi: n < 16 ? Math.min(slot, 3 + (n * 7) % 4) : Math.max(0, 5 - Math.floor((n - 15) / 2)), kini: n === 16 };
      });
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Sistem desain', tujuan: 'Acuan gaya prototipe. Setiap halaman hanya memakai komponen di bawah ini; tidak ada gaya yang ditulis per halaman.', aksi: ui.btn('Catat booking', { v: 'utama', ikon: 'tambah', aksi: 'catat' }) })}

        ${ui.panel({ judul: 'Warna', ket: 'Terong hanya untuk yang bisa ditekan atau sedang dipilih. Kunyit hanya untuk "sekarang" dan "perlu Anda".', isi: `<div class="warna">
          ${warna('Terong', '#382186', 'tindakan')}${warna('Tinta', '#1D1638', 'teks')}${warna('Kunyit', '#FFB84D', 'sekarang')}
          ${warna('Kabut', '#F5F4F9', 'latar', 1)}${warna('Lilak', '#ECE8FA', 'terpilih')}${warna('Garis', '#E3E0EE', 'batas')}
          ${warna('Daun', '#1F7A4D', 'lunas')}${warna('Bata', '#B3261E', 'bahaya')}${warna('Redup', '#625E75', 'teks kedua')}</div>` })}

        ${ui.panel({ judul: 'Huruf', ket: 'Plus Jakarta Sans untuk teks. Barlow Semi Condensed hanya untuk jam dan angka utama.', isi: `<div class="tumpuk">
          <div class="baris" style="align-items:baseline;gap:var(--s6)"><span style="font:var(--t-angka)">09.00–11.00</span><span style="font:var(--t-angka)">Rp3.350.000</span><span class="redup kecil">Angka utama 40/44</span></div>
          <div style="font:var(--t-judul)">Judul halaman 24/32</div>
          <div style="font:var(--t-bagian)">Judul bagian 17/24</div>
          <div>Isi 14/20. Booking dihitung menurut tanggal sesi, bukan tanggal pemesanan.</div>
          <div class="redup kecil">Kecil 12,5/18 untuk baris kedua dan lencana. Tidak ada teks di bawah 12 px.</div></div>` })}

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Tombol', ket: 'Satu tombol utama per layar.', isi: `<div class="tumpuk">
            <div class="baris">${ui.btn('Simpan', { v: 'utama' })}${ui.btn('Batal')}${ui.btn('Lihat semua', { v: 'polos' })}${ui.btn('Batalkan booking', { v: 'bahaya' })}</div>
            <div class="baris">${ui.btn('Catat booking', { v: 'utama', ikon: 'tambah' })}${ui.btn('Ekspor CSV', { ikon: 'unduh' })}${ui.btn('Lainnya', { ikon: 'lagi', hanyaIkon: true, aksi: 'contoh-menu' })}${ui.btn('Cairkan saldo', { v: 'utama', mati: true })}</div>
            <div class="baris">${ui.btn('Kecil', { v: 'utama', kecil: true })}${ui.btn('Kecil', { kecil: true })}${ui.btn('WhatsApp', { kecil: true, ikon: 'pesan' })}</div></div>` })}
          ${ui.panel({ judul: 'Lencana status', ket: 'Satu lencana per baris. Label mengikuti status booking yang sudah ada.', isi: `<div class="tumpuk">
            <div class="baris">${['pending', 'paid', 'completed', 'cancelled', 'no_show'].map(ui.status).join('')}</div>
            <div class="baris">${ui.lencana('Sedang berlangsung', 'pilih')}${ui.lencana('Baru', 'pilih', 1)}${ui.lencana('Tetap', 'netral', 1)}${ui.lencana('Lama tidak datang', 'tunggu', 1)}${ui.lencana('Habis', 'bahaya', 1)}</div>
            <div class="baris"><span class="hitung">3</span><span class="redup kecil">Hitungan "perlu Anda"</span>${ui.salin('BRAMA15')}${ui.delta(12)}${ui.delta(-4)}${ui.delta(0)}</div></div>` })}
        </div>

        ${ui.panel({ judul: 'Formulir', ket: 'Label di atas, petunjuk di bawah, galat menggantikan petunjuk. Tinggi kontrol 40 px (44 px di HP).', rata: true, isi:
          ui.formBagian('Identitas', 'Yang dilihat pelanggan di halaman booking.',
            `<div class="form-2">${ui.bidang({ id: 's1', label: 'Nama bisnis', wajib: true }, ui.input({ id: 's1', nilai: D.bisnis.nama }))}${ui.bidang({ id: 's2', label: 'Jenis bisnis' }, ui.pilih({ id: 's2', opsi: ['Studio kreatif', 'Lapangan olahraga', 'Klinik', 'Ruang sewa'] }))}</div>
             ${ui.bidang({ id: 's3', label: 'Alamat link booking', petunjuk: 'Jika diubah, link, kode QR dan poster lama berhenti berfungsi.' }, ui.input({ id: 's3', nilai: 'brama-studio', awalan: 'bookqu.id/' }))}
             ${ui.bidang({ id: 's4', label: 'Nomor WhatsApp', galat: 'Nomor harus diawali 08 atau +62.' }, ui.input({ id: 's4', nilai: '8129988', galat: true }))}`)
          + ui.formBagian('Kebijakan', 'Angka 0 selalu dijelaskan artinya.',
            `<div class="form-2">${ui.bidang({ id: 's5', label: 'Batas pindah jadwal', petunjuk: 'Pelanggan boleh pindah sampai 12 jam sebelum sesi.' }, ui.input({ id: 's5', nilai: '12', akhiran: 'jam' }))}${ui.bidang({ id: 's6', label: 'Jeda antar-booking', petunjuk: '0 = tanpa jeda.' }, ui.input({ id: 's6', nilai: '0', akhiran: 'menit' }))}</div>
             ${ui.sakelar({ judul: 'Buka slot otomatis', ket: 'Slot dibuka dari jam buka sampai 30 hari ke depan, setiap hari.', on: true })}
             ${ui.centang('Kirim email saat ada booking baru', { on: true })}
             <div class="tumpuk" style="gap:6px"><span class="label">Cara bayar</span>${ui.radioKartu('bayar', [{ nilai: 'tunai', judul: 'Tunai', ket: 'Diterima di tempat' }, { nilai: 'qris', judul: 'QRIS', ket: 'Lewat BookQu' }, { nilai: 'transfer', judul: 'Transfer', ket: 'Dicek manual' }], 'tunai')}</div>
             <div class="tumpuk" style="gap:6px"><span class="label">Jam mulai</span>${ui.chipJam(['09.00', '11.00', '13.00', '14.00', '16.00', '18.00'], '13.00', ['09.00', '14.00'])}</div>`)
          + ui.simpanBilah('Simpan perubahan', '2 perubahan belum disimpan') })}

        ${ui.panel({ judul: 'Rel hari', ket: 'Elemen khas: jam di kiri, garis kunyit menandai sekarang. Dipakai di Beranda dan Kalender. Klik baris untuk membuka slip booking.', isi: relHariIni() })}

        ${ui.panel({ judul: 'Tabel dan daftar', ket: 'Satu komponen: tabel padat di desktop, baris dua-baris di HP. Filter terlipat di HP.', rata: true, isi:
          ui.filter({ cari: 'Kode, nama, telepon atau email', n: 1, isi: ui.pilih({ label: 'Layanan', opsi: ['Semua layanan', 'Studio Foto Basic'] }) + ui.pilih({ label: 'Sumber', opsi: ['Semua sumber', 'Online', 'Dicatat kasir'] }), akhir: ui.btn('Ekspor CSV', { ikon: 'unduh', kecil: true }) })
          + ui.massal(1, ui.btn('Tandai selesai', { v: 'utama', kecil: true, aksi: 'toast', arg: '1 booking ditandai selesai' }))
          + tabelBooking() + ui.hal('1–6 dari 271 booking') })}

        ${ui.panel({ judul: 'Keterbacaan tabel', ket: 'Baris 52/60 px, maks. 5 kolom data, status biasa sebagai titik, lencana hanya untuk yang perlu perhatian, nilai seragam ditulis sekali di atas tabel.', rata: true, isi:
          ui.tabel({ catatan: ui.catatanTabel('Slot semua layanan terbuka sampai 14 Des.', ['Atur', '#/jam-buka/slot']), kolom: [
            { k: 'nama', label: 'Layanan', utama: true, hp: 'utama' }, { k: 'dur', label: 'Durasi', l: 'auto', nowrap: true, hp: 'sub' },
            { k: 'st', label: 'Status', l: 'auto', hp: 'sub' }, { k: 'pakai', label: 'Terpakai hari ini', l: 'auto' }, { k: 'harga', label: 'Harga', kanan: true, l: 'auto', hp: 'akhir' }, { k: 'a', label: '', aksi: true } ],
          baris: [
            { grup: 'Hari ini · Senin, 16 Nov <span class="redup">· 3 baris</span>' },
            { sel: { nama: 'Studio Foto Basic', dur: ui.durasi(60), st: ui.titikStatus('Terkonfirmasi', 'daun'), pakai: ui.batangMini(6, 14, 'jam'), harga: '<span class="num">Rp150.000</span>', a: ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Menu') } },
            { sel: { nama: 'Graduation & Family Photoshoot', dur: ui.durasi(90), st: ui.lencana('Menunggu bayar', 'tunggu'), pakai: ui.batangMini(3, 14, 'jam'), harga: '<span class="num">Rp500.000</span>', a: ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Menu') } },
            { redup: true, sel: { nama: 'Live Streaming Broadcast Hub', dur: ui.durasi(180), st: ui.titikStatus('Nonaktif'), pakai: ui.batangMini(0, 14, 'jam'), harga: '<span class="num">Rp850.000</span>', a: ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Menu') } },
          ] })
          + `<div class="panel-isi">${ui.perluBlok('Pelanggan minta refund', 'Blok kunyit untuk butir "perlu Anda" di dalam laci; tombolnya di kaki laci.')}</div>` })}

        <div class="kisi-2">
          ${ui.panel({ judul: 'Angka', ket: 'Satu angka utama, sisanya pendukung. Pembanding selalu ada.', isi:
            ui.angka({ utama: { nilai: rp(D.periode.bersih), label: 'Pendapatan bersih', delta: 12, ket: '1–16 Nov, dibanding 1–16 Okt' }, lain: [{ nilai: String(D.periode.booking), label: 'Booking', delta: 8 }, { nilai: D.periode.terisi, label: 'Slot terisi', delta: -4 }, { nilai: D.rating.rata, label: `Rating dari ${D.rating.jumlah} ulasan` }] })
            + `<div style="margin-top:var(--s5)">${ui.tiang(D.pendapatan30.slice(-16), 15, '1 Nov', 'Hari ini')}</div>` })}
          ${ui.panel({ judul: 'Baris daftar', rata: true, isi:
            D.perluAnda.map(p => ui.lr({ awal: `<span class="hitung">${p.jumlah}</span>`, judul: p.teks, akhir: ui.ikon('kanan', 18), aksi: 'toast', arg: 'Membuka tab Perlu tindakan' })).join('')
            + D.pelanggan.slice(0, 3).map(p => ui.lr({ awal: ui.avatar(p.nama), judul: ui.esc(p.nama), ket: `${p.booking} booking · ${rp(p.total)} · terakhir ${p.terakhir}`, akhir: ui.lencana(p.segmen, p.segmen === 'Baru' ? 'pilih' : 'netral', 1) })).join('') })}
        </div>

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Banner', ket: 'Paling banyak satu banner per halaman, selalu dengan tindakan. Catatan di dalam laci atau dialog memakai Kesimpulan, bukan banner.', isi: `<div class="tumpuk">
            ${ui.banner({ nada: 'perlu', judul: 'Slot terbuka hanya sampai 20 Nov', teks: '3 layanan belum punya slot minggu depan.', aksi: ui.btn('Buka slot', { kecil: true }) })}
            ${ui.banner({ judul: 'Pencairan Rp2.000.000 sedang diproses', teks: 'Ditinjau dan ditransfer admin BookQu, biasanya 1 hari kerja.', aksi: ui.btn('Lihat status', { kecil: true, href: '#/pembayaran/saldo' }) })}
            ${ui.banner({ nada: 'bahaya', judul: 'Langganan berakhir 2 hari lalu', teks: 'Booking yang ada tetap bisa diurus. Booking baru tidak diterima.', aksi: ui.btn('Perpanjang', { kecil: true, v: 'utama' }) })}</div>` })}
          ${ui.panel({ judul: 'Galat', ket: 'Sakelar Keadaan = Galat memperagakan semuanya di alurnya: Catat booking dan Pindah jadwal (jam keburu terisi), semua tombol simpan di laci dan dialog (koneksi putus, isian tetap), Ajukan pencairan (layanan pembayaran tidak menjawab), Catat booking di paket Small (kuota habis).', isi: `<div class="tumpuk">${ui.galatBlok({ judul: 'Jam 13.00 baru saja terisi booking lain', teks: 'Booking ini belum tersimpan dan isian Anda tidak hilang. Pilih jam lain yang masih kosong:', aksi: ui.btn('15.00', { kecil: true }) + ui.btn('17.00', { kecil: true }) })}<div class="baris">${ui.btn('Contoh sesi berakhir', { aksi: 'sesi-habis' })}</div></div>` })}
          ${ui.panel({ judul: 'Kosong dan memuat', isi: `<div class="tumpuk">${ui.kosong({ ikon: 'bintang', judul: 'Semua ulasan sudah dibalas', teks: 'Ulasan baru yang belum dibalas muncul di sini.' })}${ui.kerangka()}${ui.kunci({ judul: 'Unit tersedia di paket Pro', teks: 'Kunci paket: penjelasan singkat dan satu ajakan.', paket: 'Pro' })}</div>` })}
        </div>

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Kalender bulan', ket: 'Batang isi per tanggal; tanggal tanpa slot diredupkan.', isi: ui.bulan({ geser: 6, hari: bulan }) })}
          <div class="tumpuk">
            ${ui.panel({ judul: 'Peta jam ramai', isi: ui.peta({ jam: ['08', '09', '10', '11', '13', '14', '15', '16', '18', '19'], baris: [['Sen', [1, 3, 0, 0, 1, 0, 0, 0, 1, 0]], ['Sel', [0, 1, 0, 0, 0, 1, 0, 0, 0, 0]], ['Rab', [0, 1, 0, 0, 0, 1, 0, 0, 0, 1]], ['Kam', [0, 1, 0, 0, 1, 0, 0, 1, 1, 1]], ['Jum', [0, 2, 1, 0, 0, 0, 0, 0, 0, 1]], ['Sab', [0, 4, 2, 1, 0, 0, 0, 1, 1, 0]], ['Min', [0, 2, 1, 0, 1, 0, 1, 0, 0, 1]]].map(([hari, nilai]) => ({ hari, nilai })) }) })}
            ${ui.panel({ judul: 'Kuota dan batang', isi: `<div class="tumpuk">${ui.kuota({ label: 'Booking bulan ini', pakai: D.periode.booking, batas: 2000 })}${ui.kuota({ label: 'Akun tim', pakai: 9, batas: 10 })}${D.perLayanan.slice(0, 3).map(([n, v]) => ui.batang({ label: n, nilai: v, maks: D.perLayanan[0][1], teks: rp(v) })).join('')}</div>` })}
          </div>
        </div>

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Jam buka mingguan', rata: true, isi: ui.jamBuka([{ hari: 'Senin', buka: true, rentang: [['09.00', '12.00'], ['13.00', '21.00']] }, { hari: 'Selasa', buka: true, rentang: [['09.00', '21.00']] }, { hari: 'Minggu', buka: false }]) })}
          ${ui.panel({ judul: 'Panduan langkah', ket: 'Satu-satunya penomoran di portal, karena memang berurutan.', rata: true, isi: ui.langkah([{ st: 'selesai', judul: 'Lengkapi profil bisnis', ket: 'Nama, jenis, nomor HP dan alamat.' }, { st: 'selesai', judul: 'Tambah layanan pertama', ket: 'Yang dipesan pelanggan, dengan harga dan durasi.' }, { st: 'kini', judul: 'Atur jam buka', ket: 'Slot dibuka otomatis dari jam buka.', aksi: ui.btn('Mulai', { v: 'utama', kecil: true }) }, { judul: 'Bagikan link booking', ket: 'Lewat WhatsApp, Instagram atau Google Maps.' }]) })}
        </div>


        ${ui.panel({ judul: 'Kisi hari', ket: 'Jam × layanan atau unit. Judul kolom penuh (dua baris); blok satu jam tampil satu baris. Di HP diganti rel hari.', rata: true, isi: ui.kisiHari({ dari: 8, sampai: 14, kini: D.kini.jam,
          kolom: [{ nama: 'Studio Foto Basic', ket: '60 menit' }, { nama: 'Webinar & Corporate Broadcast', ket: '120 menit' }, { nama: 'Studio Podcast Multi-Mic', ket: '120 menit' }],
          blok: [
            { kol: 0, mulai: '08.00', selesai: '09.00', nada: 'lewat', aksi: 'slip', arg: 'BKG-7KQ2MX', isi: '<span class="kh-atas"><span class="jam">08.00</span><b>Irma Suryani</b></span><span>1 peserta</span>' },
            { kol: 1, mulai: '09.00', selesai: '11.00', nada: 'kini', aksi: 'slip', arg: 'BKG-2XL1WV', isi: '<span class="kh-atas"><span class="jam">09.00</span><b>Umar Wirahadi</b></span><span>1 peserta</span>' },
            { kol: 2, mulai: '11.00', selesai: '13.00', nada: 'tunggu', aksi: 'slip', arg: 'BKG-CFWK9P', isi: '<span class="kh-atas"><span class="jam">11.00</span><b>Maya Wulan</b></span><span>bayar sebelum 10.36</span>' },
            { kol: 0, mulai: '12.00', selesai: '13.00', nada: 'bebas', isi: '<span class="kh-atas"><span class="jam">12.00</span><b>Kosong</b></span><span>+ Catat · Rp150.000</span>' },
            { kol: 1, mulai: '11.30', selesai: '14.00', nada: 'tutup', isi: '<span>Ditutup</span><span>Perawatan</span>' },
          ] }) })}

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Kisi minggu', ket: 'Satu baris per booking: jam, nama singkat, titik status. Di HP bertumpuk per hari.', rata: true, isi: ui.minggu([{ hari: 'Sen', tgl: 16, kini: true, b: [['08.00', 'Irma S.', 'completed'], ['11.00', 'Maya W.', 'pending']] }, { hari: 'Sel', tgl: 17, b: [['09.00', 'Dewi L.', 'paid']] }, { hari: 'Min', tgl: 22, tutup: true, b: [] }]) })}
          ${ui.panel({ judul: 'Navigasi dan bilah alat', ket: 'Bilah alat membungkus segmen, navigasi tanggal dan tombol kecil; di HP navigasi tanggal turun ke baris sendiri.', isi: ui.alat(ui.segmen([{ label: 'Hari', on: true }, { label: 'Minggu' }, { label: 'Bulan' }]), ui.tglNav('Senin, 16 Nov 2026'), `<span class="dorong">${ui.btn('Filter', { kecil: true, ikon: 'filter' })}</span>`) })}
        </div>

        ${ui.panel({ judul: 'Kartu', ket: 'Hanya untuk hal yang memang sejajar. Dalam satu kisi, semua kartu bergambar atau semua ringkas; tidak dicampur. Tempat gambar polos, tanpa gradasi.', isi: ui.kisiKartu(
          ui.kartu({ gambar: 'foto', lencana: ui.lencana('Populer', 'pilih', 1), judul: 'Studio Foto Basic', ket: 'Fotografi · 60 menit', isi: '<b class="num">Rp150.000</b>', kaki: 'Slot terbuka<span class="dorong">Sampai 14 Des</span>' })
          + ui.kartu({ gambar: 'kosong', judul: 'Voice Over & Dubbing', ket: 'Belum ada gambar', isi: '<b class="num">Rp200.000</b>', kaki: 'Slot terbuka<span class="dorong">Sampai 14 Des</span>' })
          + ui.kartu({ besar: true, judul: '15%', ket: 'maks Rp100.000 · Semua layanan', isi: `<div class="tumpuk rapat jarak-atas">${ui.salin('BRAMA15')}${ui.kuota({ label: 'Dipakai', pakai: 38, batas: 200 })}</div>`, kaki: 'Berlaku 1 Nov – 31 Des 2026' })
          + ui.kartu({ besar: true, mati: true, lencana: ui.lencana('Berakhir', 'batal', 1), judul: '17%', ket: 'maks Rp170.000', kaki: 'Berlaku 1 – 31 Agu 2026' })) })}

        <div class="kisi-sama">
          ${ui.panel({ judul: 'Kesimpulan dan lipat', ket: 'Kesimpulan: satu kalimat arti angka, juga catatan di dalam laci dan dialog. Lipat: isi sekunder yang jarang dibuka.', rata: true, isi: `<div class="panel-isi">${ui.kesimpulan('Webinar & Corporate Broadcast hanya 3 booking, tetapi pendapatannya terbesar.')}</div>` + ui.lipat('4 konflik lampau', 'Sesi sudah lewat, tidak bisa ditindak lagi.') + ui.lipat('Kenapa pelanggan tidak bisa booking?', 'Biasanya slot belum dibuka sampai tanggal itu.') })}
          ${ui.panel({ judul: 'Pratinjau HP dan menu Lainnya', ket: 'Pratinjau memakai data asli bisnis. Menu Lainnya di HP adalah lembar penuh berkelompok.', isi: `<div class="baris">${ui.pratinjauHp(`<div class="hp-sampul"></div><b>${ui.esc(D.bisnis.nama)}</b><div class="hp-layanan"><b class="kecil">Studio Foto Basic</b><b class="kecil num">Rp150.000</b></div><span class="hp-tombol">Pilih jadwal</span>`)}${ui.btn('Buka menu Lainnya', { aksi: 'lainnya' })}</div>` })}
        </div>

        ${ui.panel({ judul: 'Lapisan melayang', ket: 'Hanya lapisan ini yang berbayang. Di HP semuanya naik dari bawah. Esc atau klik di luar untuk menutup.', isi: `<div class="baris">
          ${ui.btn('Slip booking (laci)', { aksi: 'contoh-laci', arg: 'BKG-CFWK9P' })}${ui.btn('Dialog formulir', { aksi: 'contoh-dialog' })}${ui.btn('Konfirmasi bahaya', { aksi: 'contoh-konfirmasi' })}${ui.btn('Lembar filter', { aksi: 'contoh-lembar' })}${ui.btn('Menu', { ikon: 'lagi', aksi: 'contoh-menu' })}${ui.btn('Toast', { aksi: 'toast', arg: 'Booking ditandai selesai' })}</div>` })}
      </div>`;
    },
  };
})();
