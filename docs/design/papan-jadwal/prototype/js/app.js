/* Cangkang, router, lapisan melayang, sakelar prototipe. */
(function () {
  const BQ = window.BQ, ui = BQ.ui, esc = ui.esc;
  const $ = (s, el = document) => el.querySelector(s);
  const app = $('#app'), stage = $('#stage'), view = $('#view'), lapis = $('#lapis');

  /* ---------- Keadaan prototipe ---------- */
  const BAWAAN = { role: 'pemilik', plan: 'pro', state: 'berjalan', density: 'nyaman', sidebar: 'terang', notes: 'off', screen: 'otomatis' };
  let S = { ...BAWAAN };
  try { S = { ...BAWAAN, ...JSON.parse(localStorage.getItem('bq-proto') || '{}') }; } catch (e) { /* tanpa penyimpanan pun jalan */ }
  BQ.S = S;
  const simpan = () => { try { localStorage.setItem('bq-proto', JSON.stringify(S)); } catch (e) { /* abaikan */ } };

  /* ---------- Navigasi (15 menu, lima kelompok) ---------- */
  const NAV = [
    { item: [['beranda', 'Beranda', 'beranda'], ['kalender', 'Kalender', 'kalender'], ['booking', 'Booking', 'daftar', { hitung: BQ.data.tabBooking.perlu }], ['pelanggan', 'Pelanggan', 'orang']] },
    { grup: 'Bisnis saya', item: [['layanan', 'Layanan', 'layanan', { peran: 'mp' }], ['unit', 'Unit', 'unit', { peran: 'mp', paket: 'pro' }], ['jam-buka', 'Jam Buka & Harga', 'jam', { peran: 'mp' }]] },
    { grup: 'Pemasaran', item: [['halaman-booking', 'Halaman Booking', 'globe', { peran: 'mp' }], ['promo', 'Promo', 'promo', { peran: 'mp', paket: 'medium' }], ['ulasan', 'Ulasan', 'bintang', { peran: 'mp' }]] },
    { grup: 'Keuangan', item: [['pembayaran', 'Pembayaran', 'dompet', { peran: 'p' }], ['laporan', 'Laporan', 'grafik', { peran: 'mp', paket: 'medium' }]] },
    { grup: 'Lainnya', item: [['pengaturan', 'Pengaturan', 'atur'], ['paket', 'Paket & Tagihan', 'kartu', { peran: 'p' }], ['bantuan', 'Bantuan', 'bantuan']] },
  ];
  const TINGKAT = { small: 1, medium: 2, pro: 3 };
  const boleh = o => !o || !o.peran || o.peran.includes(S.role[0]);
  const terkunci = o => o && o.paket && TINGKAT[S.plan] < TINGKAT[o.paket];
  const JUDUL = { notifikasi: 'Notifikasi', sistem: 'Sistem desain' };
  NAV.forEach(g => g.item.forEach(([k, l]) => (JUDUL[k] = l)));

  function navHtml(aktif) {
    return NAV.map(g => {
      const it = g.item.filter(i => boleh(i[3]));
      if (!it.length) return '';
      return (g.grup ? `<div class="sb-grup">${g.grup}</div>` : '') + it.map(([k, l, ik, o]) =>
        `<a href="#/${k}" title="${l}"${k === aktif ? ' aria-current="page"' : ''}>${ui.ikon(ik)}<span>${l}</span>${o && o.hitung ? `<span class="hitung" aria-label="${o.hitung} perlu Anda">${o.hitung}</span>` : ''}${terkunci(o) ? `<span class="sb-kunci" title="Tersedia di paket ${o.paket}">${ui.ikon('kunci', 14)}</span>` : ''}</a>`).join('');
    }).join('');
  }

  /* K1: di halaman non-harian tombol bilah atas jadi tombol kedua, supaya satu tombol utama per layar */
  const HARIAN = ['beranda', 'kalender', 'booking', 'pelanggan'];
  function cangkang(aktif) {
    const u = BQ.data.pengguna[S.role];
    $('#sb').innerHTML = `<div class="sb-merek"><span class="logo">bq</span><div><b>BookQu</b><span>${esc(BQ.data.bisnis.nama)}</span></div></div>${navHtml(aktif)}`;
    $('#top').innerHTML = `<span class="logo">bq</span>
      <label class="cari">${ui.ikon('cari', 18)}<input type="search" placeholder="Cari booking atau pelanggan" aria-label="Cari booking atau pelanggan"></label>
      <div class="top-kanan">
        ${ui.btn('Catat booking', { v: HARIAN.includes(aktif) ? 'utama' : 'kedua', ikon: 'tambah', aksi: 'catat', kecil: true })}
        <a class="ikon-btn ke-halaman" href="#/halaman-booking" title="Buka halaman booking" aria-label="Buka halaman booking">${ui.ikon('luar')}</a>
        <a class="ikon-btn" href="#/notifikasi" aria-label="Notifikasi, 2 belum dibaca">${ui.ikon('lonceng')}<span class="titik"></span></a>
        <button class="akun" type="button" data-aksi="menu-akun">${ui.avatar(u.nama)}<span><b>${esc(u.nama)}</b><span>${u.peran}</span></span></button>
      </div>`;
    const bn = (k, l, ik) => `<a href="#/${k}"${k === aktif ? ' aria-current="page"' : ''}>${ui.ikon(ik, 22)}<span>${l}</span></a>`;
    $('#bnav').innerHTML = bn('beranda', 'Beranda', 'beranda') + bn('kalender', 'Kalender', 'kalender')
      + `<button type="button" class="tambah" data-aksi="catat"><span>${ui.ikon('tambah', 22)}</span><span>Catat</span></button>`
      + bn('booking', 'Booking', 'daftar') + `<button type="button" data-aksi="lainnya">${ui.ikon('lagi', 22)}<span>Lainnya</span></button>`;
  }

  /* ---------- Router ---------- */
  BQ.pages = BQ.pages || {};
  /* Keadaan "bisnis baru": halaman harian dan uang kosong, dengan langkah berikutnya */
  const KOSONG_BARU = {
    kalender: { ikon: 'kalender', judul: 'Belum ada slot di periode ini', teks: 'Atur jam buka, lalu slot terbuka otomatis.', aksi: ui.btn('Atur jam buka', { v: 'utama', href: '#/jam-buka' }) },
    booking: { ikon: 'daftar', judul: 'Belum ada booking', teks: 'Booking masuk dari halaman booking Anda. Bagikan linknya ke pelanggan.', aksi: ui.btn('Bagikan link booking', { v: 'utama', href: '#/halaman-booking' }) },
    pelanggan: { ikon: 'orang', judul: 'Belum ada pelanggan', teks: 'Pelanggan muncul otomatis setelah booking pertama.' },
    layanan: { ikon: 'layanan', judul: 'Tambah layanan pertama', teks: 'Yang dipesan pelanggan, dengan harga dan durasi. Setelah disimpan, lanjut ke jam buka.', aksi: ui.btn('Tambah layanan', { v: 'utama', ikon: 'tambah', aksi: 'ubah-layanan' }) },
    ulasan: { ikon: 'bintang', judul: 'Belum ada ulasan', teks: 'Pelanggan memberi ulasan lewat link booking mereka setelah sesi selesai.' },
    laporan: { ikon: 'grafik', judul: 'Laporan muncul setelah ada booking', teks: 'Pendapatan, jam ramai dan layanan terlaris dihitung dari booking Anda.' },
  };
  function rute() {
    const [jalur, q] = (location.hash.replace(/^#\/?/, '') || 'beranda').split('?');
    const [hal, tab] = jalur.split('/');
    const menu = NAV.flatMap(g => g.item).find(i => i[0] === hal);
    const p = menu && !boleh(menu[3]) ? { render: () => `<div class="view-in">${ui.kepala({ judul: JUDUL[hal] })}${ui.panel({ isi: ui.kosong({ ikon: 'kunci', judul: 'Halaman ini tidak tersedia untuk peran Anda', teks: `Peran ${BQ.data.pengguna[S.role].peran} tidak punya akses. Hubungi Pemilik bila Anda membutuhkannya.`, aksi: ui.btn('Kembali ke Beranda', { v: 'utama', href: '#/beranda' }) }) })}</div>` }
      : S.state === 'baru' && KOSONG_BARU[hal] ? { render: () => `<div class="view-in">${ui.kepala({ judul: JUDUL[hal] })}${ui.panel({ isi: ui.kosong(KOSONG_BARU[hal]) })}</div>` } : BQ.pages[hal];
    BQ.tutup();
    cangkang(hal);
    view.innerHTML = p
      ? p.render({ tab, q: new URLSearchParams(q || ''), S })
      : `<div class="view-in">${ui.kepala({ judul: JUDUL[hal] || 'Halaman' })}${ui.panel({ isi: ui.kosong({ judul: 'Halaman ini belum dibuat', teks: 'Langkah 1 baru memuat fondasi dan cangkang. Halaman dibuat setelah gaya di Sistem desain disetujui.', aksi: ui.btn('Buka Sistem desain', { v: 'utama', href: '#/sistem' }) }) })}</div>`;
    document.title = (JUDUL[hal] || 'BookQu') + ' · Prototipe BookQu';
    /* §12 langganan berakhir: satu banner di halaman yang membuat sesuatu baru */
    if (S.state === 'berakhir' && ['kalender', 'booking', 'pelanggan', 'layanan', 'unit', 'jam-buka', 'promo'].includes(hal)) {
      const ph = view.querySelector('.view-in > .ph');
      if (ph) ph.insertAdjacentHTML('afterend', ui.banner({ nada: 'bahaya', judul: 'Langganan berakhir 14 Nov', teks: `Booking yang ada tetap bisa diurus. Booking, slot, layanan dan promo baru tidak bisa dibuat sampai paket diperpanjang${S.role === 'pemilik' ? '' : ' oleh Pemilik'}.`, aksi: S.role === 'pemilik' ? ui.btn('Perpanjang', { kecil: true, href: '#/paket' }) : '' }));
    }
    view.scrollTop = 0;
    if (p && p.pasang) p.pasang(view);
    judulPenuh(view);
  }
  BQ.segarkan = rute;

  /* T6: setiap teks yang terpotong mendapat judul penuh, supaya isinya tetap bisa dibaca (sorot atau pembaca layar). */
  function judulPenuh(akar) {
    akar.querySelectorAll('*').forEach(e => {
      if (e.children.length || e.title || e.closest('[title]') || e.classList.contains('sr')) return;
      const t = e.textContent.trim();
      if (t && e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).overflow !== 'visible') e.title = t;
    });
  }
  BQ.judulPenuh = judulPenuh;
  let tundaUkuran = 0;
  window.addEventListener('resize', () => { clearTimeout(tundaUkuran); tundaUkuran = setTimeout(() => { judulPenuh(view); judulPenuh(lapis); }, 150); });

  /* ---------- Lapisan melayang ---------- */
  let fokusLama = null;
  function buka(jenis, o) {
    fokusLama = document.activeElement;
    lapis.innerHTML = `<div class="tirai" data-aksi="tutup"></div><div class="${jenis}${o.penuh ? ' penuh' : ''}" role="dialog" aria-modal="true" aria-label="${esc(o.judul)}">
      <div class="lap-kepala"><h2>${esc(o.judul)}</h2><button class="ikon-btn" type="button" data-aksi="tutup" aria-label="Tutup">${ui.ikon('tutup')}</button></div>
      <div class="lap-isi">${o.isi}</div>${o.kaki ? `<div class="lap-kaki">${o.kaki}</div>` : ''}</div>`;
    $('.lap-kepala .ikon-btn', lapis).focus();
    judulPenuh(lapis);
  }
  BQ.laci = o => buka('laci', o);
  BQ.dialog = o => buka('dialog', o);
  BQ.lembar = o => buka('lembar', o);
  BQ.tutup = () => { if (!lapis.innerHTML) return; lapis.innerHTML = ''; if (fokusLama && fokusLama.focus) fokusLama.focus(); };
  BQ.menu = (pemicu, item) => {
    fokusLama = pemicu;
    const a = app.getBoundingClientRect(), r = pemicu.getBoundingClientRect();
    lapis.innerHTML = `<div style="position:absolute;inset:0;pointer-events:auto" data-aksi="tutup"></div><div class="menu" role="menu">${item.map(i => i === '-' ? '<hr>' : `<button type="button" role="menuitem" class="${i.bahaya ? 'bahaya' : ''}" data-aksi="${i.aksi || 'tutup'}" data-arg="${esc(i.arg || '')}">${i.ikon ? ui.ikon(i.ikon, 18) : ''}${esc(i.label)}</button>`).join('')}</div>`;
    const m = $('.menu', lapis);
    const kiri = Math.max(8, Math.min(r.right - a.left - m.offsetWidth, a.width - m.offsetWidth - 8));
    const bawah = r.bottom - a.top + 4;
    m.style.left = kiri + 'px';
    m.style.top = (bawah + m.offsetHeight > a.height - 8 ? r.top - a.top - m.offsetHeight - 4 : bawah) + 'px';
    $('button', m).focus();
  };
  let tToast;
  BQ.toast = (teks, t, galat) => {
    const lama = $('.toast', app); if (lama) lama.remove();
    app.insertAdjacentHTML('beforeend', `<div class="toast${galat ? ' toast-galat' : ''}" role="${galat ? 'alert' : 'status'}">${ui.ikon(galat ? 'awas' : 'cek', 18)}${esc(teks)}${t ? `<button type="button" data-aksi="${t.aksi}" data-arg="${esc(t.arg)}">${esc(t.label)}</button>` : ''}</div>`);
    clearTimeout(tToast); tToast = setTimeout(() => { const t = $('.toast', app); if (t) t.remove(); }, 2600);
  };
  BQ.konfirmasi = o => BQ.dialog({ judul: o.judul, isi: `<p>${esc(o.teks)}</p>`, kaki: ui.btn('Batal', { aksi: 'tutup' }) + ui.btn(o.ya, { v: o.bahaya ? 'bahaya-isi' : 'utama', aksi: 'toast', arg: o.hasil }) });

  /* ---------- Tindakan ---------- */
  /* bawaan cangkang; tindakan halaman dengan nama sama menang */
  BQ.aksi = Object.assign({
    tutup: () => BQ.tutup(),
    toast: el => {
      /* Keadaan Galat: simpan gagal, lapisan tetap terbuka supaya isian tidak hilang */
      if (S.state === 'galat' && lapis.innerHTML) return BQ.toast('Belum tersimpan: koneksi terputus. Isian Anda masih ada.', { label: 'Coba lagi', aksi: 'tutup' }, true);
      BQ.tutup(); BQ.toast(el.dataset.arg || 'Selesai');
    },
    /* Sesi login berakhir: isian disimpan di perangkat, masuk lagi lalu lanjut */
    'sesi-habis': () => BQ.dialog({ judul: 'Sesi Anda berakhir', isi: '<p>Demi keamanan, Anda keluar otomatis setelah lama tidak aktif. Isian yang belum tersimpan ditahan di perangkat ini dan diisi kembali setelah Anda masuk.</p>', kaki: ui.btn('Masuk lagi', { v: 'utama', aksi: 'toast', arg: 'Masuk kembali; isian dipulihkan' }) }),
    'menu-akun': el => BQ.menu(el, [{ label: 'Akun saya', ikon: 'orang' }, { label: 'Notifikasi', ikon: 'lonceng' }, '-', { label: 'Keluar', ikon: 'luar' }]),
    /* Menu "Lainnya" HP: lembar berlatar solid, dikelompokkan seperti sidebar (review lintas 5) */
    lainnya: () => {
      const di = ['beranda', 'kalender', 'booking'];
      const isi = NAV.map(g => {
        const it = g.item.filter(i => boleh(i[3]) && !di.includes(i[0]));
        return it.length ? `<section class="lainnya-grup">${g.grup ? `<h3>${g.grup}</h3>` : ''}<div class="lainnya-kisi">${it.map(([k, l, ik, o]) => `<a href="#/${k}">${ui.ikon(ik, 22)}<span>${l}</span>${terkunci(o) ? ui.ikon('kunci', 14) : ''}</a>`).join('')}</div></section>` : '';
      }).join('');
      BQ.lembar({ penuh: true, judul: 'Lainnya', isi: isi + `<section class="lainnya-grup"><div class="lainnya-kisi"><a href="#/notifikasi">${ui.ikon('lonceng', 22)}<span>Notifikasi</span></a><a href="#/sistem">${ui.ikon('kotak', 22)}<span>Sistem desain</span></a></div></section>` });
    },
    'filter-hp': () => BQ.lembar({ judul: 'Filter', isi: ui.bidang({ id: 'f1', label: 'Layanan' }, ui.pilih({ id: 'f1', opsi: ['Semua layanan', ...BQ.data.layanan.map(l => l.nama)] })) + ui.bidang({ id: 'f2', label: 'Sumber' }, ui.pilih({ id: 'f2', opsi: ['Semua sumber', 'Online', 'Dicatat kasir'] })), kaki: ui.btn('Hapus filter', {}) + ui.btn('Terapkan', { v: 'utama', aksi: 'tutup' }) }),
    'pilih-baris': el => { const b = el.closest('.tbl-b'); if (b) b.setAttribute('aria-selected', el.checked); },
  }, BQ.aksi || {});

  document.addEventListener('click', e => {
    const seg = e.target.closest('[data-segmen] button');
    if (seg) seg.parentElement.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b === seg));
    const chip = e.target.closest('[data-chip] button');
    if (chip) chip.parentElement.querySelectorAll('button').forEach(b => b.setAttribute('aria-pressed', b === chip));
    const el = e.target.closest('[data-aksi]');
    if (!el) return;
    /* kontrol di dalam baris yang bisa diklik tidak ikut membuka baris */
    const dalam = e.target.closest('button, a, input, label, select');
    if (dalam && dalam !== el && el.contains(dalam) && !dalam.dataset.aksi) return;
    const f = BQ.aksi[el.dataset.aksi];
    if (f) f(el, e);
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') BQ.tutup();
    if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-aksi][tabindex="0"]')) { e.preventDefault(); e.target.click(); }

    /* Kunci fokus: Tab berputar di dalam dialog, laci, lembar atau menu yang terbuka */
    if (e.key === 'Tab' && lapis.innerHTML) {
      const kotak = lapis.querySelector('[role="dialog"], .menu');
      const f = kotak ? [...kotak.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea, [tabindex="0"]')].filter(x => x.getClientRects().length) : [];
      if (f.length) {
        const i = f.indexOf(document.activeElement);
        if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
        else if (!e.shiftKey && (i === -1 || i === f.length - 1)) { e.preventDefault(); f[0].focus(); }
      }
    }

    /* Menu "⋯": panah atas/bawah, Home, End */
    const menu = e.target.closest && e.target.closest('.menu');
    if (menu && ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(e.key)) {
      const it = [...menu.querySelectorAll('button')];
      const i = it.indexOf(e.target);
      const n = e.key === 'Home' ? 0 : e.key === 'End' ? it.length - 1 : (i + (e.key === 'ArrowDown' ? 1 : -1) + it.length) % it.length;
      it[n].focus(); e.preventDefault();
    }

    /* Tab halaman dan segmen: panah kiri/kanan berpindah pilihan */
    const grup = e.target.closest && e.target.closest('.tabs, .segmen');
    if (grup && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) {
      const it = [...grup.querySelectorAll('a, button')];
      const i = it.indexOf(e.target);
      if (i >= 0) { it[(i + (e.key === 'ArrowRight' ? 1 : -1) + it.length) % it.length].focus(); e.preventDefault(); }
    }
  });

  /* ---------- Sakelar prototipe ---------- */
  const SAKELAR = [
    ['screen', 'Layar', [['otomatis', 'Ikut jendela'], ['hp', 'HP 375']]],
    ['role', 'Peran', [['pemilik', 'Pemilik'], ['manajer', 'Manajer'], ['kasir', 'Kasir']]],
    ['plan', 'Paket', [['small', 'Small'], ['medium', 'Medium'], ['pro', 'Pro']]],
    ['data', 'Data', [['biasa', 'Biasa'], ['ramai', 'Ramai']]],
    ['state', 'Keadaan', [['berjalan', 'Berjalan'], ['baru', 'Baru'], ['berakhir', 'Berakhir'], ['memuat', 'Memuat'], ['galat', 'Galat']]],
    ['density', 'Kepadatan', [['nyaman', 'Nyaman'], ['padat', 'Padat']]],
    ['sidebar', 'Sidebar', [['terang', 'Terang'], ['terong', 'Terong']]],
    ['notes', 'Catatan perubahan', [['off', 'Mati'], ['on', 'Hidup']]],
  ];
  function terapkan() {
    ['role', 'plan', 'state', 'density', 'sidebar', 'notes'].forEach(k => (app.dataset[k] = S[k]));
    stage.dataset.screen = S.screen;
    $('#proto-panel').innerHTML = SAKELAR.map(([k, l, o]) => `<label>${l}<div class="segmen">${o.map(([v, t]) => `<button type="button" data-set="${k}" data-v="${v}" aria-pressed="${S[k] === v}">${t}</button>`).join('')}</div></label>`).join('')
      + `<a href="#/sistem">Buka Sistem desain</a>`;
  }
  $('#proto-tombol').addEventListener('click', () => { const p = $('#proto-panel'); p.hidden = !p.hidden; });
  $('#proto-panel').addEventListener('click', e => {
    const b = e.target.closest('[data-set]');
    if (!b) return;
    S[b.dataset.set] = b.dataset.v; simpan();
    if (b.dataset.set === 'data') return location.reload(); /* data dibentuk saat dimuat */
    terapkan(); rute();
  });

  window.addEventListener('hashchange', rute);
  terapkan();
  rute();
})();
