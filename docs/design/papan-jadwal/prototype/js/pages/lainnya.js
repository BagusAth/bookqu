/* Langkah 5: Halaman Booking (§8.9), Pengaturan (§8.14), Paket & Tagihan (§8.15), Notifikasi (§8.16), Bantuan (§8.17). */
(function () {
  const BQ = window.BQ, ui = BQ.ui, D = BQ.data, rp = BQ.rp, esc = ui.esc;
  const TINGKAT = { small: 1, medium: 2, pro: 3 };
  const minimal = (S, p) => TINGKAT[S.plan] >= TINGKAT[p];
  const NAMA_PAKET = { small: 'Small', medium: 'Medium', pro: 'Pro' };

  /* Data kecil khusus halaman ini */
  Object.assign(D, {
    galeri: [['Logo Brama', 'logo'], ['Sampul studio', 'cover'], ['Studio Foto A', 'service'], ['Podcast Suite', 'service'], ['Green screen', 'service'], ['Behind the scene', 'gallery'], ['Wisuda 2026', 'gallery'], ['Denah lantai', 'other']],
    tim: [
      { nama: 'Brama Aditya', email: 'brama@bramastudio.id', peran: 'Pemilik', st: 'Aktif', masuk: 'Sekarang' },
      { nama: 'Sinta Maharani', email: 'sinta@bramastudio.id', peran: 'Manajer', st: 'Aktif', masuk: 'Hari ini, 08.40' },
      { nama: 'Dimas Prakoso', email: 'dimas@bramastudio.id', peran: 'Kasir', st: 'Aktif', masuk: 'Kemarin, 19.12' },
      { nama: 'Ayu Lestari', email: 'ayu.l@gmail.com', peran: 'Kasir', st: 'Diundang', masuk: '-' },
    ],
    notif: [
      { baru: true, ikon: 'awas', judul: 'Raka Pradipta minta refund', ket: 'Studio Foto Tematik VIP, 18 Nov 10.00 · 2 jam lalu', aksi: 'slip', arg: 'BKG-R4KA7P', jenis: 'Booking' },
      { baru: true, ikon: 'kalender', judul: 'Booking baru: Maya Wulan', ket: 'Studio Podcast Multi-Mic, hari ini 11.00 · 3 mnt lalu', aksi: 'slip', arg: 'BKG-CFWK9P', jenis: 'Booking' },
      { ikon: 'dompet', judul: 'Pencairan Rp2.000.000 disetujui', ket: 'Menunggu transfer ke BCA •••• 4421 · kemarin', ke: '#/pembayaran/saldo', jenis: 'Pencairan', pemilik: true },
      { ikon: 'bintang', judul: 'Ulasan baru ★★★★★ dari Hendi Hermawan', ket: 'Live Streaming Broadcast Hub · kemarin', ke: '#/ulasan', jenis: 'Ulasan' },
      { ikon: 'cek', judul: 'Pembayaran berhasil: Nadia Safira', ket: 'Rp1.500.000 lewat QRIS · 15 Nov', aksi: 'slip', arg: 'BKG-LQZQG3', jenis: 'Booking' },
      { ikon: 'kartu', judul: 'Tagihan paket Pro terbayar', ket: 'Berlaku sampai 2 Mei 2027 · 2 Nov', ke: '#/paket', jenis: 'Paket', pemilik: true },
    ],
  });

  /* ---------- Halaman Booking ---------- */
  const pratinjau = () => ui.pratinjauHp(`<div class="hp-sampul"></div><div class="baris">${'<span class="logo">bq</span>'}<div><b>${esc(D.bisnis.nama)}</b><div class="redup kecil">Studio kreatif · ★ 4,6 (23)</div></div></div>`
    + D.layanan.filter(l => l.aktif).slice(0, 3).map(l => `<div class="hp-layanan"><div><b class="kecil">${esc(l.nama)}</b><div class="redup kecil">${l.durasi} menit</div></div><b class="kecil num">${rp(l.harga)}</b></div>`).join('')
    + `<span class="hp-tombol">Pilih jadwal</span>`);

  BQ.pages['halaman-booking'] = {
    render({ tab, S }) {
      tab = tab || 'bagikan';
      const tabs = ui.cat('A', 'K4 Landing page → Halaman depan') + ui.tabs('halaman-booking', [{ k: 'bagikan', label: 'Bagikan' }, { k: 'tampilan', label: 'Tampilan' }, { k: 'galeri', label: 'Galeri', jml: D.galeri.length }, { k: 'landing', label: 'Halaman depan' }, { k: 'domain', label: 'Domain sendiri' }], tab);
      let isi;
      if (tab === 'tampilan') {
        isi = `<div class="dua-kolom">${ui.panel({ rata: true, isi:
          ui.formBagian('Merek', 'Logo, sampul dan warna di halaman booking.', `<div class="baris"><span class="logo">bq</span>${ui.btn('Ganti logo', { kecil: true })}${ui.btn('Ganti sampul', { kecil: true })}</div>
            <div class="tumpuk rapat"><span class="label">Warna tema</span><div class="baris pilih-warna">${['terong', 'daun', 'bata', 'tinta', 'redup'].map((w, i) => `<button type="button" aria-pressed="${i === 0}" aria-label="Warna ${w}">${ui.titikWarna(w)}</button>`).join('')}</div><span class="petunjuk">Bawaan: terong BookQu. ${ui.cat('A', '8.9 no. 2')}</span></div>`)
          + ui.formBagian('Gaya', 'Bentuk tombol, kartu dan huruf.', `<div class="tumpuk rapat"><span class="label">Tombol</span>${ui.segmen([{ label: 'Kapsul', on: true }, { label: 'Membulat' }, { label: 'Kotak' }])}</div><div class="tumpuk rapat"><span class="label">Kartu</span>${ui.segmen([{ label: 'Timbul' }, { label: 'Bergaris', on: true }, { label: 'Lembut' }])}</div>${ui.bidang({ id: 'tp1', label: 'Huruf' }, ui.pilih({ id: 'tp1', opsi: ['Plus Jakarta Sans', 'Inter', 'Poppins'] }))}`)
          + ui.formBagian('Deskripsi', 'Tampil di bawah nama bisnis.', ui.teksarea({ nilai: 'Studio foto, video dan podcast di Bandung. Peralatan lengkap, operator berpengalaman.' }))
          + ui.simpanBilah('Simpan tampilan', '') })}
          <div class="tumpuk">${pratinjau()}<p class="redup kecil">Pratinjau memakai layanan Anda. ${ui.cat('B', '8.9 no. 3')}</p></div></div>`;
      } else if (tab === 'galeri') {
        isi = '<div class="galeri">' + ui.kisiKartu(D.galeri.map(([n, k]) => ui.kartu({ gambar: k === 'service' ? 'audio' : k === 'cover' ? 'video' : 'foto', judul: n, ket: { logo: 'Logo', cover: 'Sampul', service: 'Layanan', gallery: 'Galeri', other: 'Lainnya' }[k], kaki: `Dipakai di ${k === 'service' ? '1 layanan' : k === 'logo' ? 'halaman booking' : '—'}<span class="dorong">${ui.aksiIkon('Lainnya', 'lagi', 'toast', 'Ganti atau hapus gambar')}</span>` })).join('')) + '</div>'
          + `<p class="redup kecil">Gambar paling besar 10 MB. Galeri yang sama dipakai saat memilih gambar di Layanan, Unit dan Halaman depan.</p>`;
      } else if (tab === 'landing' || tab === 'domain') {
        if (!minimal(S, 'pro')) isi = ui.panel({ isi: ui.kunci({ judul: `${tab === 'landing' ? 'Halaman depan' : 'Domain sendiri'} tersedia di paket Pro`, teks: tab === 'landing' ? 'Halaman depan lengkap untuk bisnis Anda, dengan tombol booking.' : 'Pakai alamat sendiri, misalnya booking.bramastudio.id.', paket: 'Pro' }) });
        else if (tab === 'landing') isi = `<div class="dua-kolom">${ui.panel({ rata: true, isi: ui.formBagian('Templat', '', ui.radioKartu('tpl', [{ nilai: 'a', judul: 'Bisnis modern' }, { nilai: 'b', judul: 'Studio kreatif' }, { nilai: 'c', judul: 'Klinik minimalis' }], 'b'))
            + ui.formBagian('Isi', 'Bagian yang tampil, dari atas ke bawah.', `${ui.bidang({ id: 'ld1', label: 'Judul utama' }, ui.input({ id: 'ld1', nilai: 'Studio kreatif untuk karya terbaik Anda' }))}${['Banner', 'Layanan', 'Galeri', 'Ulasan', 'Lokasi'].map((x, i) => ui.centang(x, { on: i !== 2 })).join('')}`)
            + `<div class="simpan-bilah"><span class="redup kecil">Terbit 2 Nov</span>${ui.btn('Pratinjau', { ikon: 'luar' })}${ui.btn('Terbitkan', { v: 'utama', aksi: 'toast', arg: 'Halaman depan diterbitkan' })}</div>` })}${pratinjau()}</div>`;
        else isi = ui.panel({ rata: true, isi: ui.formBagian('Domain', 'Satu domain hanya bisa dipakai satu bisnis.', ui.bidang({ id: 'dm1', label: 'Alamat domain' }, ui.input({ id: 'dm1', nilai: 'booking.bramastudio.id' })) + `<div class="baris">${ui.lencana('Belum terhubung', 'tunggu')}<span class="redup kecil">Diperiksa 10.15</span>${ui.btn('Periksa lagi', { kecil: true, aksi: 'toast', arg: 'Masih belum terhubung' })}</div>`)
          + ui.formBagian('Atur DNS', 'Di tempat Anda membeli domain.', ui.kv([['Jenis', 'CNAME'], ['Nama', 'booking'], ['Nilai', ui.salin('tenant.bookqu.id')]]) + `<p class="redup kecil">Perubahan DNS bisa butuh sampai 24 jam.</p>`) });
      } else {
        isi = `<div class="kisi-sama">
          ${ui.panel({ judul: 'Link booking', isi: `<div class="tumpuk">${ui.salin(D.bisnis.link)}<div class="baris">${ui.btn('Buka', { kecil: true, ikon: 'luar' })}${ui.btn('Bagikan ke WhatsApp', { kecil: true, ikon: 'pesan', aksi: 'toast', arg: 'Membuka WhatsApp' })}</div></div>` })}
          ${ui.panel({ judul: 'Kode QR', isi: `<div class="baris"><div class="qr" role="img" aria-label="Kode QR link booking">QR</div><div class="tumpuk rapat">${ui.btn('Unduh QR', { kecil: true, ikon: 'unduh', aksi: 'toast', arg: 'QR diunduh' })}${ui.btn('Cetak poster', { kecil: true, ikon: 'unduh', aksi: 'toast', arg: 'Poster A5 diunduh' })}</div></div>` })}
        </div>
        <!-- D-026: sumber booking per kanal (C-08) disembunyikan; link booking tanpa penanda -->
        ${ui.panel({ judul: 'Teks siap pakai', isi: `<div class="kisi-sama">${ui.bidang({ id: 'tx1', label: 'Bio Instagram' }, ui.teksarea({ id: 'tx1', nilai: `📸 Studio foto, video & podcast di Bandung\nBooking online 👇\n${D.bisnis.link}` }))}${ui.bidang({ id: 'tx2', label: 'Pesan WhatsApp' }, ui.teksarea({ id: 'tx2', nilai: `Halo! Sekarang booking ${D.bisnis.nama} bisa langsung online, pilih jam dan bayar di sini: ${D.bisnis.link}` }))}</div><div class="baris jarak-atas">${ui.btn('Salin bio', { kecil: true, ikon: 'salin', aksi: 'toast', arg: 'Disalin' })}${ui.btn('Salin pesan', { kecil: true, ikon: 'salin', aksi: 'toast', arg: 'Disalin' })}</div>` })}
        ${ui.cat('A', '8.9 no. 4')}`;
      }
      return `<div class="view-in">${ui.kepala({ judul: 'Halaman Booking', tujuan: 'Bagaimana pelanggan menemukan Anda, dan apa yang mereka lihat.', aksi: tab === 'galeri' ? ui.btn('Unggah gambar', { v: 'utama', ikon: 'unduh', aksi: 'toast', arg: 'Memilih berkas' }) : '' })}${tabs}${isi}</div>`;
    },
  };

  /* ---------- Pengaturan ---------- */
  const TAB_ATUR = [
    { k: 'profil', label: 'Profil bisnis', peran: 'pm' }, { k: 'kebijakan', label: 'Kebijakan booking', peran: 'pm' }, { k: 'notifikasi', label: 'Notifikasi', peran: 'pmk' },
    { k: 'integrasi', label: 'Integrasi', peran: 'p' }, { k: 'akun', label: 'Akun', peran: 'pmk' }, { k: 'tim', label: 'Tim', peran: 'p' },
  ];
  BQ.pages.pengaturan = {
    render({ tab, S }) {
      const boleh = TAB_ATUR.filter(t => t.peran.includes(S.role[0]));
      const t = boleh.find(x => x.k === tab) || boleh[0];
      let isi;
      if (t.k === 'kebijakan') {
        isi = ui.formBagian('Pembatalan dan refund', 'Yang boleh dilakukan pelanggan sendiri lewat link booking.', `${ui.sakelar({ judul: 'Pelanggan boleh minta refund', ket: 'Anda tetap memutuskan setiap permintaan.', on: true })}
            <div class="form-2">${ui.bidang({ id: 'kb1', label: 'Batas minta refund', petunjuk: 'Sampai 24 jam sebelum sesi. 0 = sampai sesi dimulai.' }, ui.input({ id: 'kb1', nilai: '24', akhiran: 'jam' }))}${ui.bidang({ id: 'kb2', label: 'Batas pindah jadwal', petunjuk: 'Sampai 12 jam sebelum sesi. 0 = sampai sesi dimulai.' }, ui.input({ id: 'kb2', nilai: '12', akhiran: 'jam' }))}</div>`)
          + ui.formBagian('Waktu booking', 'Kapan pelanggan boleh memesan.', `<div class="form-2">${ui.bidang({ id: 'kb3', label: 'Booking paling lambat', petunjuk: '0 = pelanggan boleh booking sampai sesi dimulai.' }, ui.input({ id: 'kb3', nilai: '0', akhiran: 'jam sebelum' }))}${ui.bidang({ id: 'kb4', label: 'Jeda antar-booking', petunjuk: '0 = tanpa jeda.' }, ui.input({ id: 'kb4', nilai: '0', akhiran: 'menit' }))}</div>${ui.cat('A', '8.14 no. 3')}`)
          + ui.formBagian('Sesi lewat', 'Supaya Laporan tetap benar tanpa menandai satu per satu.', `${ui.sakelar({ judul: 'Tandai selesai otomatis', ket: 'Booking lunas ditandai selesai 6 jam setelah sesi berakhir, kecuali Anda menandainya tidak datang.', on: true })}${ui.cat('C', 'fitur 2')}`)
          + ui.simpanBilah('Simpan kebijakan', '');
      } else if (t.k === 'notifikasi') {
        const ev = ['Booking baru', 'Pembayaran berhasil', 'Pembatalan', 'Pindah jadwal', 'Permintaan refund', 'Ulasan baru', ...(S.role === 'pemilik' ? ['Status pencairan', 'Status paket'] : [])];
        isi = ui.formBagian('Per kejadian', 'Notifikasi di aplikasi selalu aktif. Email bisa dimatikan.', ui.tabel({ kolom: [{ k: 'e', label: 'Kejadian', utama: true, hp: 'utama' }, { k: 'a', label: 'Aplikasi', l: 'auto', nowrap: true }, { k: 'm', label: 'Email', l: 'auto', hp: 'akhir' }], baris: ev.map((e, i) => ({ sel: { e, a: '<span class="redup kecil">Selalu</span>', m: `<label class="sakelar"><input type="checkbox" role="switch"${i < 5 ? ' checked' : ''} aria-label="Email ${e}"><span></span></label>` } })) })
          + ui.bidang({ id: 'nt1', label: 'Kirim email ke' }, ui.input({ id: 'nt1', nilai: D.bisnis.email })))
          + (S.role === 'pemilik' ? ui.formBagian('Ringkasan mingguan', 'Setiap Senin pagi.', `${ui.sakelar({ judul: 'Kirim ringkasan mingguan', ket: 'Pendapatan, booking, slot terisi, slot terbuka sampai kapan, dan hal yang menunggu Anda.', on: true })}${ui.cat('C', 'fitur 6')}`) : '')
          + ui.simpanBilah('Simpan notifikasi', '');
      } else if (t.k === 'integrasi') {
        const kartu = (n, k, st, aksi) => ui.lr({ awal: ui.ikon('globe'), judul: n, ket: k, akhir: st + (aksi || '') });
        isi = ui.formBagian('Terhubung', 'Alat Anda yang tersambung ke BookQu.', `<div class="panel">${kartu('Google Calendar', 'Booking masuk ke kalender Google Anda', ui.lencana('Segera hadir', 'netral', 1))}${kartu('Google Analytics', 'Kunjungan halaman booking', ui.lencana('Terhubung', 'lunas', 1), ui.btn('Uji', { kecil: true, v: 'polos' }))}${kartu('Meta Pixel', 'Iklan Instagram dan Facebook', '', ui.btn('Hubungkan', { kecil: true }))}</div>`)
          + ui.formBagian('Dikelola BookQu', 'Tidak perlu diatur.', ui.kv([['Pembayaran', 'QRIS, virtual account, e-wallet'], ['Email', 'Bukti booking dan pengingat']]))
          + ui.formBagian('API dan webhook', 'Untuk developer. Paket Pro.', minimal(S, 'pro') ? `<div class="panel">${ui.lr({ judul: 'https://bramastudio.id/hook/bookqu', ket: 'Terakhir terkirim 10.21 · berhasil', akhir: ui.btn('Kirim tes', { kecil: true, aksi: 'toast', arg: 'Tes terkirim' }) })}</div>${ui.btn('Buat kunci API', { kecil: true, ikon: 'kunci' })}` : ui.lencana('Paket Pro', 'netral', 1));
      } else if (t.k === 'akun') {
        const u = D.pengguna[S.role];
        isi = ui.formBagian('Data diri', '', `<div class="form-2">${ui.bidang({ id: 'ak1', label: 'Nama' }, ui.input({ id: 'ak1', nilai: u.nama }))}${ui.bidang({ id: 'ak2', label: 'Nomor HP' }, ui.input({ id: 'ak2', nilai: '0812-9988-7766' }))}</div>${ui.bidang({ id: 'ak3', label: 'Email' }, ui.input({ id: 'ak3', nilai: u.nama.split(' ')[0].toLowerCase() + '@bramastudio.id' }))}`)
          + ui.formBagian('Kata sandi', 'Kosongkan bila tidak ingin mengganti.', `<div class="form-2">${ui.bidang({ id: 'ak4', label: 'Kata sandi baru' }, ui.input({ id: 'ak4', tipe: 'password' }))}${ui.bidang({ id: 'ak5', label: 'Ulangi kata sandi baru' }, ui.input({ id: 'ak5', tipe: 'password' }))}</div>${ui.bidang({ id: 'ak6', label: 'Kata sandi saat ini', wajib: true, petunjuk: 'Wajib untuk perubahan apa pun di halaman ini.' }, ui.input({ id: 'ak6', tipe: 'password' }))}`)
          + (S.role === 'pemilik' ? ui.formBagian('Hapus akun bisnis', 'Tidak bisa dibatalkan.', `<p class="redup">Menghapus bisnis, layanan, booking dan pelanggan. Ditolak selama masih ada saldo atau booking mendatang.</p><div>${ui.btn('Hapus akun bisnis', { v: 'bahaya', aksi: 'toast', arg: 'Masih ada saldo Rp18.500.000' })}</div>`) : '')
          + ui.simpanBilah('Simpan akun', '');
      } else if (t.k === 'tim') {
        isi = !minimal(S, 'medium') ? ui.kunci({ judul: 'Tim tersedia mulai paket Medium', teks: 'Undang Manajer dan Kasir dengan hak terbatas.', paket: 'Medium' })
          : `<div class="filter">${ui.kuota({ label: 'Akun tim', pakai: D.tim.length, batas: S.plan === 'pro' ? 10 : 5 })}<span class="dorong">${ui.btn('Undang anggota', { v: 'utama', kecil: true, ikon: 'tambah', aksi: 'undang' })}</span></div>`
            + ui.tabel({ kolom: [{ k: 'n', label: 'Anggota', utama: true, hp: 'utama' }, { k: 'p', label: 'Peran', l: 'auto', nowrap: true, hp: 'akhir' }, { k: 'm', label: 'Terakhir masuk', l: 'auto', nowrap: true, hp: 'sub' }, { k: 'x', label: '', aksi: true }], baris: D.tim.map(m => ({ sel: { n: ui.dua(`${esc(m.nama)}${m.st === 'Aktif' ? '' : ' ' + ui.lencana(m.st, 'tunggu', 1)}`, esc(m.email)), p: esc(m.peran), m: m.st === 'Aktif' ? esc(m.masuk) : '<span class="redup">Belum pernah</span>', x: m.peran === 'Pemilik' ? '' : ui.aksiIkon('Lainnya', 'lagi', 'menu-anggota') } })) })
            + ui.lipat('Log aktivitas tim', ui.kv([['10.21', 'Dimas Prakoso (Kasir) mencatat booking BKG-NADTHW'], ['08.45', 'Sinta Maharani (Manajer) menandai selesai BKG-P1GR5T'], ['Kemarin', 'Brama Aditya (Pemilik) mengundang Ayu Lestari sebagai Kasir']]));
      } else {
        isi = ui.formBagian('Identitas', 'Yang dilihat pelanggan di halaman booking.', `<div class="form-2">${ui.bidang({ id: 'pf1', label: 'Nama bisnis', wajib: true }, ui.input({ id: 'pf1', nilai: D.bisnis.nama }))}${ui.bidang({ id: 'pf2', label: 'Jenis bisnis' }, ui.pilih({ id: 'pf2', opsi: ['Studio kreatif', 'Lapangan olahraga', 'Klinik', 'Ruang sewa'] }))}</div>
            ${ui.bidang({ id: 'pf3', label: 'Alamat link booking', petunjuk: 'Jika diubah, link, kode QR dan poster lama berhenti berfungsi.' }, ui.input({ id: 'pf3', nilai: 'brama-studio', awalan: 'bookqu.id/' }))}`)
          + ui.formBagian('Kontak dan lokasi', '', `<div class="form-2">${ui.bidang({ id: 'pf4', label: 'WhatsApp' }, ui.input({ id: 'pf4', nilai: D.bisnis.hp }))}${ui.bidang({ id: 'pf5', label: 'Email' }, ui.input({ id: 'pf5', nilai: D.bisnis.email }))}</div>${ui.bidang({ id: 'pf6', label: 'Alamat' }, ui.teksarea({ id: 'pf6', nilai: 'Jl. Braga No. 21, Bandung' }))}${ui.bidang({ id: 'pf7', label: 'Link Google Maps' }, ui.input({ id: 'pf7', nilai: 'https://maps.app.goo.gl/brama' }))}${ui.bidang({ id: 'pf8', label: 'Zona waktu', petunjuk: 'Saat ini hanya WIB.' }, ui.pilih({ id: 'pf8', opsi: ['WIB (Asia/Jakarta)'], mati: true }))}`)
          + ui.simpanBilah('Simpan profil', '');
      }
      return `<div class="view-in">${ui.kepala({ judul: 'Pengaturan', tujuan: 'Setiap tab disimpan sendiri.' })}${ui.tabs('pengaturan', boleh, t.k)}${ui.panel({ rata: true, isi })}${ui.cat('A', 'lintas 2')}</div>`;
    },
  };

  /* ---------- Paket & Tagihan ---------- */
  const PAKET = [
    { k: 'small', nama: 'Small', harga: 'Rp149.000', fitur: ['5 layanan', '100 booking per bulan', 'Halaman booking, kalender, pembayaran online', 'Ulasan dan pelanggan'] },
    { k: 'medium', nama: 'Medium', harga: 'Rp299.000', fitur: ['20 layanan', '500 booking per bulan', 'Promo, laporan dan tambahan', 'Tim sampai 5 akun', 'Ekspor CSV'] },
    { k: 'pro', nama: 'Pro', harga: 'Rp499.000', fitur: ['Layanan tanpa batas', '2.000 booking per bulan', 'Unit dan kalender per unit', 'Halaman depan dan domain sendiri', 'Tim sampai 10 akun', 'API dan webhook'] },
  ];
  BQ.pages.paket = {
    render({ S }) {
      if (S.role !== 'pemilik') return `<div class="view-in">${ui.kepala({ judul: 'Paket & Tagihan' })}${ui.panel({ isi: ui.kosong({ ikon: 'kunci', judul: 'Hanya untuk Pemilik', teks: 'Paket dan tagihan diatur oleh Pemilik bisnis.' }) })}</div>`;
      const kini = S.plan, berakhir = S.state === 'berakhir';
      return `<div class="view-in">
        ${ui.kepala({ judul: 'Paket & Tagihan', tujuan: 'Paket Anda, apa yang didapat, dan riwayat pembayaran.' })}
        ${berakhir ? ui.banner({ nada: 'bahaya', judul: 'Langganan berakhir 14 Nov', teks: 'Booking yang ada tetap bisa diurus. Booking baru tidak diterima sampai diperpanjang.', aksi: ui.btn('Perpanjang', { kecil: true, v: 'utama', aksi: 'checkout' }) }) : ''}
        <div class="kisi-2">
          ${ui.panel({ judul: `Paket ${NAMA_PAKET[kini]}`, isi: ui.angka({ utama: { nilai: berakhir ? '0 hari' : '167 hari', label: berakhir ? 'berakhir 14 Nov 2026' : `tersisa, sampai ${D.bisnis.paketSampai}`, ket: 'Memperpanjang menambah dari tanggal berakhir, tidak memotong sisa hari.' } }) })}
          ${ui.panel({ judul: 'Pemakaian', isi: `<div class="tumpuk">${ui.kuota({ label: 'Layanan', pakai: 10, batas: { small: 5, medium: 20, pro: 999 }[kini] }).replace('10 dari 999', '10 · tanpa batas')}${ui.kuota({ label: 'Booking bulan ini', pakai: D.periode.booking, batas: { small: 100, medium: 500, pro: 2000 }[kini] })}${ui.kuota({ label: 'Akun tim', pakai: 4, batas: { small: 1, medium: 5, pro: 10 }[kini] })}</div>` })}
        </div>
        ${ui.panel({ judul: 'Bandingkan paket', ket: 'Harga per bulan. Ganti paket berlaku sejak tanggal bayar.', cat: ui.cat('A', '8.14 no. 1'), isi: `<div class="hanya-hp" style="margin-bottom: var(--s3)">${ui.segmen(PAKET.map(p => ({ label: p.nama, on: p.k === kini, aksi: 'paket-tampil', arg: p.k })), { label: 'Paket' })}</div><div class="kisi-3 paket-kisi" data-tampil="${kini}">${PAKET.map((p, i) => `<section data-k="${p.k}" class="${ui.esc(['paket', p.k === kini && 'kini'].filter(Boolean).join(' '))}"><div class="baris"><b>${p.nama}</b>${p.k === kini ? ui.lencana('Paket Anda', 'pilih', 1) : ''}</div><div class="paket-harga">${p.harga}<span>/bulan</span></div><p class="redup kecil">${i ? `Semua di ${PAKET[i - 1].nama}, ditambah:` : 'Termasuk:'}</p><ul>${p.fitur.map(f => `<li>${ui.ikon('cek', 16)}${esc(f)}</li>`).join('')}</ul>${p.k === kini ? ui.btn('Perpanjang', { lebar: true, aksi: 'checkout' }) : ui.btn(TINGKAT[p.k] > TINGKAT[kini] ? `Pindah ke ${p.nama}` : `Turun ke ${p.nama}`, { lebar: true, v: 'kedua', aksi: TINGKAT[p.k] > TINGKAT[kini] ? 'checkout' : 'turun', arg: p.k })}</section>`).join('')}</div><p class="redup kecil jarak-atas">Harga contoh untuk prototipe; harga resmi ditetapkan Product Lead.</p>` })}
        ${ui.panel({ judul: 'Riwayat tagihan', rata: true, isi: ui.tabel({ kolom: [{ k: 'n', label: 'Faktur', utama: true, hp: 'utama' }, { k: 'p', label: 'Paket', l: 'minmax(0, 1fr)', hp: 'sub' }, { k: 'j', label: 'Jumlah', l: 'auto', kanan: true, hp: 'akhir' }, { k: 'u', label: '', aksi: true }], baris: [['INV-2611-0042', 'Pro, 6 bulan', '2 Nov 2026', 2994000], ['INV-2605-0017', 'Medium, 6 bulan', '2 Mei 2026', 1794000]].map(([n, p, t, j]) => ({ hpSub: `${p} · ${t}`, sel: { n: ui.dua(n, t), p, j: `<span class="num">${rp(j)}</span>`, u: ui.aksiIkon('Unduh faktur', 'unduh', 'toast', 'Faktur diunduh') } })) }) })}
      </div>`;
    },
  };

  /* ---------- Notifikasi ---------- */
  BQ.pages.notifikasi = {
    render({ tab, S }) {
      const semua = D.notif.filter(n => !n.pemilik || S.role === 'pemilik');
      const daftar = tab === 'belum' ? semua.filter(n => n.baru) : semua;
      return `<div class="view-in sempit">
        ${ui.kepala({ judul: 'Notifikasi', tujuan: 'Yang terjadi dan perlu Anda ketahui.', aksi: ui.btn('Tandai semua dibaca', { v: 'polos', ikon: 'cek', aksi: 'toast', arg: 'Semua ditandai dibaca' }) })}
        ${ui.alat(ui.segmen([{ label: 'Semua', href: '#/notifikasi', on: tab !== 'belum' }, { label: 'Belum dibaca (2)', href: '#/notifikasi/belum', on: tab === 'belum' }]), `<span class="dorong">${ui.pilih({ label: 'Jenis', opsi: ['Semua jenis', 'Booking', 'Ulasan', ...(S.role === 'pemilik' ? ['Pencairan', 'Paket'] : [])] })}</span>`)}
        ${ui.panel({ rata: true, isi: (() => {
          /* keterbacaan sesi 3: dikelompokkan per waktu; waktu di kanan, keterangan satu kalimat */
          const kapan = n => n.ket.split(' · ').pop();
          const grup = n => /lalu$/.test(kapan(n)) ? 'Hari ini' : kapan(n) === 'kemarin' ? 'Kemarin' : 'Minggu ini';
          return ['Hari ini', 'Kemarin', 'Minggu ini'].map(g => { const isi = daftar.filter(n => grup(n) === g); return isi.length ? `<div class="lr-grup">${g}</div>` + isi.map(n => ui.lr({ baru: n.baru, awal: `<span class="ikon-bulat">${ui.ikon(n.ikon, 18)}</span>`, judul: esc(n.judul), ket: n.ket.split(' · ').slice(0, -1).join(' · '), akhir: `<span class="kecil">${esc(kapan(n))}</span>` + (n.baru ? '<i class="titik-baru" aria-label="Belum dibaca"></i>' : '') + ui.aksiIkon('Lainnya', 'lagi', 'menu-notif'), aksi: n.aksi || 'ke', arg: n.arg || n.ke })).join('') : ''; }).join('');
        })() })}
      </div>`;
    },
  };

  /* ---------- Bantuan ---------- */
  const TANYA = [
    ['Booking', [['Bagaimana menandai banyak booking selesai sekaligus?', 'Buka Booking → Perlu tindakan, centang barisnya, lalu Tandai selesai.'], ['Pelanggan minta refund, apa yang terjadi?', 'Permintaan muncul di Perlu Anda. Anda memilih terima 100%, 50% atau tolak; admin BookQu mentransfer refund.']]],
    ['Jam buka dan slot', [['Kenapa pelanggan tidak bisa booking?', 'Biasanya slot belum dibuka sampai tanggal itu. Lihat "Slot terbuka sampai" di Jam Buka & Harga.']]],
    ['Pembayaran', [['Kapan saldo bisa dicairkan?', 'Kapan saja, paling sedikit Rp50.000. Admin BookQu mentransfer dalam 1 hari kerja.'], ['Berapa biaya BookQu per transaksi?', '0%. Anda hanya membayar paket.']]],
  ];
  BQ.pages.bantuan = {
    render() {
      return `<div class="view-in sempit">
        ${ui.kepala({ judul: 'Bantuan', tujuan: 'Jawaban cepat, dan cara menghubungi kami.' })}
        ${ui.panel({ judul: 'Hubungi BookQu', ket: 'Senin–Sabtu, 08.00–20.00 WIB.', cat: ui.cat('teknis', '8.14 no. 2'), isi: `<div class="baris">${ui.btn('WhatsApp 0811-2233-4455', { v: 'utama', ikon: 'pesan', aksi: 'toast', arg: 'Membuka WhatsApp BookQu' })}${ui.btn('bantuan@bookqu.id', { ikon: 'luar', aksi: 'toast', arg: 'Membuka email' })}</div>` })}
        ${TANYA.map(([g, qs]) => ui.panel({ judul: g, rata: true, isi: qs.map(([q, a]) => ui.lipat(q, esc(a))).join('') })).join('')}
        ${ui.panel({ isi: ui.lr({ awal: ui.ikon('cek'), judul: 'Panduan awal', ket: 'Empat langkah menyiapkan bisnis, bisa dibuka lagi kapan saja.', akhir: ui.btn('Buka panduan', { kecil: true, href: '#/beranda' }), datar: true }) })}
      </div>`;
    },
  };

  BQ.aksi = Object.assign(BQ.aksi || {}, {
    'paket-tampil': el => { const k = document.querySelector('#view .paket-kisi'); if (k) k.dataset.tampil = el.dataset.arg; },
    undang: () => BQ.dialog({ judul: 'Undang anggota', isi: ui.bidang({ id: 'ud1', label: 'Email', wajib: true }, ui.input({ id: 'ud1', tipe: 'email', ph: 'nama@contoh.com' }))
      + `<div class="tumpuk rapat"><span class="label">Peran</span>${ui.radioKartu('pr', [{ nilai: 'm', judul: 'Manajer', ket: 'Semua kecuali uang, paket dan tim' }, { nilai: 'k', judul: 'Kasir', ket: 'Catat dan urus booking; tanpa angka uang' }], 'k')}<span class="petunjuk">Kasir tidak bisa membatalkan sendiri; ia mengajukan pembatalan ke Pemilik atau Manajer.</span></div>${ui.cat('A', '8.14 no. 4')}`,
      kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn('Kirim undangan', { v: 'utama', aksi: 'toast', arg: 'Undangan terkirim' }) }),
    turun: el => {
      const k = el.dataset.arg, batas = { small: [5, 100, 1], medium: [20, 500, 5] }[k], nama = NAMA_PAKET[k];
      const lebih = [[10, batas[0], 'Layanan aktif'], [4, batas[2], 'Akun tim']].filter(([pakai, b]) => pakai > b);
      BQ.dialog({ judul: `Turun ke ${nama}?`, isi: lebih.length
        ? `<p>Pemakaian Anda melebihi batas ${nama}:</p>${ui.kv(lebih.map(([pakai, b, l]) => [l, `<b class="num">${pakai}</b> dari batas ${b}`]))}<p class="redup">Nonaktifkan yang berlebih dulu. Fitur yang tidak ada di ${nama} berhenti saat paket berganti.</p>`
        : `<p>Fitur yang tidak ada di ${nama} berhenti saat paket berganti.</p>`,
        kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn(`Turun ke ${nama}`, { v: 'utama', mati: lebih.length > 0 }) });
    },
    'menu-notif': el => BQ.menu(el, [{ label: 'Tandai dibaca', ikon: 'cek', aksi: 'toast', arg: 'Ditandai dibaca' }, '-', { label: 'Hapus', ikon: 'tutup', bahaya: true, aksi: 'hapus-notif' }]),
    'hapus-notif': () => BQ.konfirmasi({ judul: 'Hapus notifikasi ini?', teks: 'Notifikasi hilang dari daftar. Booking, ulasan atau pencairan yang terkait tidak berubah.', ya: 'Hapus', bahaya: true, hasil: 'Notifikasi dihapus' }),
    'menu-anggota': el => BQ.menu(el, [{ label: 'Ubah peran', ikon: 'atur' }, '-', { label: 'Cabut akses', ikon: 'tutup', bahaya: true, aksi: 'toast', arg: 'Akses dicabut' }]),
    checkout: () => BQ.dialog({ judul: 'Perpanjang paket', isi: ui.radioKartu('lm', [{ nilai: '1', judul: '1 bulan', ket: 'Rp499.000' }, { nilai: '6', judul: '6 bulan', ket: 'Rp2.994.000' }, { nilai: '12', judul: '12 bulan', ket: 'Rp5.988.000' }], '6') + ui.kv([['Berlaku sampai', '2 Nov 2027'], ['Dibayar oleh', 'Brama Aditya · brama@bramastudio.id']]), kaki: `<span class="lap-total"><span>Total</span><b>Rp2.994.000</b></span>` + ui.btn('Bayar', { v: 'utama', aksi: 'toast', arg: 'Membuka halaman pembayaran' }) }),
  });
})();
