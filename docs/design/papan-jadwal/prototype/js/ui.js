/* Komponen: satu fungsi per komponen, mengembalikan HTML. Halaman hanya memanggil fungsi ini. */
(function () {
  const BQ = window.BQ;
  const ui = (BQ.ui = {});
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  ui.esc = esc;
  const kelas = (...k) => k.filter(Boolean).join(' ');
  const klik = o => (o.aksi ? ` data-aksi="${o.aksi}"${o.arg != null ? ` data-arg="${esc(o.arg)}"` : ''} tabindex="0" role="button"` : '');

  /* ---------- Ikon (garis 1,75 px, 24 px) ---------- */
  const IK = {
    beranda: '<path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z"/>',
    kalender: '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
    daftar: '<path d="M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01"/>',
    orang: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.4 3.2-5.5 6.5-5.5s5.9 2.1 6.5 5.5M16 4.8a3.5 3.5 0 0 1 0 6.4M18.5 14.9c1.6.9 2.7 2.6 3 5.1"/>',
    layanan: '<path d="m12 3.5 8.5 4.5-8.5 4.5L3.5 8z"/><path d="m3.5 12.5 8.5 4.5 8.5-4.5"/>',
    unit: '<path d="M4 20V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v14M2.5 20h19M9.5 20v-6.5h5V20"/>',
    jam: '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    globe: '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.5 2.6 3.7 5.4 3.7 8.5s-1.2 5.9-3.7 8.5c-2.5-2.6-3.7-5.4-3.7-8.5S9.5 6.1 12 3.5z"/>',
    promo: '<path d="M3.5 12.2V5A1.5 1.5 0 0 1 5 3.5h7.2a2 2 0 0 1 1.4.6l6.6 6.6a2 2 0 0 1 0 2.8l-6.7 6.7a2 2 0 0 1-2.8 0l-6.6-6.6a2 2 0 0 1-.6-1.4z"/><path d="M8 8h.01"/>',
    bintang: '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.9-5.2-2.8-5.2 2.8 1-5.9-4.3-4.1 5.9-.8z"/>',
    dompet: '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3M4 7.5V17a2 2 0 0 0 2 2h13a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1H6.5A2.5 2.5 0 0 1 4 7.5zM16.5 13.5h.01"/>',
    grafik: '<path d="M5 20v-8M11 20V5M17 20v-5M21 20H3"/>',
    atur: '<path d="M4 7h9M19 7h1M4 17h1M11 17h9"/><circle cx="16" cy="7" r="2.5"/><circle cx="8" cy="17" r="2.5"/>',
    kartu: '<rect x="3" y="5.5" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h4"/>',
    bantuan: '<circle cx="12" cy="12" r="8.5"/><path d="M9.6 9.5a2.5 2.5 0 1 1 3.6 2.2c-.8.4-1.2 1-1.2 1.8M12 16.5h.01"/>',
    lonceng: '<path d="M6 16.5V11a6 6 0 1 1 12 0v5.5l1.5 1.5h-15zM10 20.5a2.2 2.2 0 0 0 4 0"/>',
    cari: '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
    tambah: '<path d="M12 5v14M5 12h14"/>',
    tutup: '<path d="M6 6l12 12M18 6 6 18"/>',
    kanan: '<path d="m9 6 6 6-6 6"/>',
    kiri: '<path d="m15 6-6 6 6 6"/>',
    bawah: '<path d="m6 9 6 6 6-6"/>',
    cek: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    lagi: '<circle cx="5" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.3" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.3" fill="currentColor" stroke="none"/>',
    luar: '<path d="M14 4h6v6M20 4l-9 9M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/>',
    filter: '<path d="M4 6h16M7 12h10M10 18h4"/>',
    pesan: '<path d="M20.5 11.5a8.5 8.5 0 0 1-12.6 7.4L3.5 20l1.2-4.2A8.5 8.5 0 1 1 20.5 11.5z"/>',
    salin: '<rect x="8.5" y="8.5" width="11" height="11" rx="2"/><path d="M5.5 15.5h-1a1 1 0 0 1-1-1v-10a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/>',
    unduh: '<path d="M12 4v11M7.5 11l4.5 4.5 4.5-4.5M5 20h14"/>',
    awas: '<path d="M12 4 2.8 19.5h18.4zM12 10v4.5M12 17h.01"/>',
    info: '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
    naik: '<path d="M12 19V5M6 11l6-6 6 6"/>',
    turun: '<path d="M12 5v14M6 13l6 6 6-6"/>',
    kunci: '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
    kotak: '<rect x="4" y="4" width="16" height="16" rx="2"/>',
    gambar: '<rect x="3.5" y="4.5" width="17" height="15" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m20.5 16-5-5-8 8.5"/>',
  };
  /* Sesi 4 (T3): galat di dalam laci/dialog. Isian tetap ada; selalu ada jalan keluar. */
  ui.galatBlok = o => `<div class="galat-blok" role="alert">${ui.ikon('awas', 20)}<div><b>${esc(o.judul)}</b>${o.teks ? `<p>${esc(o.teks)}</p>` : ''}${o.aksi ? `<div class="baris">${o.aksi}</div>` : ''}</div></div>`;
  ui.ikon = (n, s = 20) => `<svg width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${IK[n] || ''}</svg>`;

  /* ---------- Catatan perubahan (hanya tampil saat sakelar hidup) ---------- */
  ui.cat = (k, ref) => `<span class="cat" data-k="${k}" title="Label ${k}, lihat review.md ${esc(ref)}">${k} ${esc(ref)}</span>`;

  /* ---------- Dasar ---------- */
  ui.btn = (label, o = {}) => {
    const cls = kelas('btn', 'btn-' + (o.v || 'kedua'), o.kecil && 'btn-kecil', o.lebar && 'btn-lebar', o.hanyaIkon && 'btn-ikon');
    const isi = (o.ikon ? ui.ikon(o.ikon, o.kecil ? 16 : 18) : '') + (o.hanyaIkon ? `<span class="sr">${esc(label)}</span>` : esc(label));
    const a = (o.aksi ? ` data-aksi="${o.aksi}"` : '') + (o.arg != null ? ` data-arg="${esc(o.arg)}"` : '') + (o.attrs ? ' ' + o.attrs : '');
    return o.href ? `<a class="${cls}" href="${o.href}"${a}>${isi}</a>` : `<button type="button" class="${cls}"${o.mati ? ' disabled' : ''}${a}>${isi}</button>`;
  };
  ui.lencana = (label, nada = 'netral', polos) => `<span class="${kelas('lencana', 'l-' + nada, polos && 'polos')}">${esc(label)}</span>`;
  const ST = { pending: ['tunggu', 'Menunggu bayar'], paid: ['lunas', 'Terkonfirmasi'], completed: ['netral', 'Selesai'], cancelled: ['batal', 'Dibatalkan'], no_show: ['bahaya', 'Tidak datang'] };
  const TITIK = { paid: 'daun', completed: 'abu', cancelled: 'bata', no_show: 'bata' };
  ui.status = s => s === 'pending' ? ui.lencana(ST[s][1], ST[s][0]) : ui.titikStatus(ST[s][1], TITIK[s]);
  ui.avatar = (nama, besar) => `<span class="${kelas('avatar', besar && 'besar')}" aria-hidden="true">${esc(nama.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase())}</span>`;
  ui.salin = teks => `<span class="salin"><span class="potong" title="${esc(teks)}">${esc(teks)}</span><button type="button" data-aksi="toast" data-arg="Disalin" aria-label="Salin ${esc(teks)}">${ui.ikon('salin', 16)}</button></span>`;
  ui.delta = n => n === 0 ? `<span class="delta datar">sama</span>` : `<span class="delta ${n > 0 ? 'naik' : 'turun'}">${ui.ikon(n > 0 ? 'naik' : 'turun', 13)}${Math.abs(n)}%</span>`;

  /* ---------- Formulir ---------- */
  ui.bidang = (o, kontrol) => `<div class="bidang"><label for="${o.id}">${esc(o.label)}${o.wajib ? ' <span class="wajib" aria-hidden="true">*</span>' : ''}</label>${kontrol}${o.galat ? `<span class="galat">${ui.ikon('awas', 14)}${esc(o.galat)}</span>` : ''}${o.petunjuk ? `<span class="petunjuk">${esc(o.petunjuk)}</span>` : ''}</div>`;
  ui.input = (o = {}) => {
    const inp = `<input ${o.awalan || o.akhiran ? '' : 'class="input" '}id="${o.id || ''}" type="${o.tipe || 'text'}" value="${esc(o.nilai || '')}" placeholder="${esc(o.ph || '')}"${o.galat ? ' aria-invalid="true"' : ''}${o.mati ? ' disabled' : ''}>`;
    return o.awalan || o.akhiran ? `<div class="input-grup">${o.awalan ? `<span>${esc(o.awalan)}</span>` : ''}${inp}${o.akhiran ? `<span>${esc(o.akhiran)}</span>` : ''}</div>` : inp;
  };
  ui.pilih = (o = {}) => `<select class="pilih" id="${o.id || ''}"${o.mati ? ' disabled' : ''}${o.label ? ` aria-label="${esc(o.label)}"` : ''}>${(o.opsi || []).map(x => `<option${x === o.nilai ? ' selected' : ''}>${esc(x)}</option>`).join('')}</select>`;
  ui.teksarea = (o = {}) => `<textarea class="teksarea" id="${o.id || ''}" placeholder="${esc(o.ph || '')}">${esc(o.nilai || '')}</textarea>`;
  ui.centang = (label, o = {}) => `<label class="centang"><input type="checkbox"${o.on ? ' checked' : ''}${o.aksi ? ` data-aksi="${o.aksi}"` : ''}><span>${label ? esc(label) : `<span class="sr">${esc(o.sr || 'Pilih')}</span>`}</span></label>`;
  ui.sakelar = o => `<label class="sakelar"><input type="checkbox" role="switch"${o.on ? ' checked' : ''}><span><b>${esc(o.judul)}</b>${o.ket ? `<span>${esc(o.ket)}</span>` : ''}</span></label>`;
  ui.radioKartu = (nama, opsi, pilih) => `<div class="radio-kartu" role="radiogroup">${opsi.map(x => `<label><input type="radio" name="${nama}"${x.nilai === pilih ? ' checked' : ''}><b>${esc(x.judul)}</b>${x.ket ? `<span>${esc(x.ket)}</span>` : ''}</label>`).join('')}</div>`;
  ui.segmen = (opsi, o = {}) => `<div class="segmen" data-segmen role="group"${o.label ? ` aria-label="${esc(o.label)}"` : ''}>${opsi.map(x => x.href
    ? `<a href="${x.href}"${x.on ? ' aria-current="page"' : ''}>${esc(x.label)}</a>`
    : `<button type="button" aria-pressed="${x.on ? 'true' : 'false'}"${x.aksi ? ` data-aksi="${x.aksi}" data-arg="${esc(x.arg ?? '')}"` : ''}>${esc(x.label)}</button>`).join('')}</div>`;
  ui.chipJam = (jam, pilih, penuh = []) => `<div class="chip-jam" data-chip>${jam.map(j => `<button type="button" aria-pressed="${j === pilih}"${penuh.includes(j) ? ' disabled' : ''}>${j}</button>`).join('')}</div>`;
  ui.formBagian = (judul, ket, isi) => `<section class="form-bagian"><div><h3>${esc(judul)}</h3>${ket ? `<p>${esc(ket)}</p>` : ''}</div><div class="form-isi">${isi}</div></section>`;
  ui.simpanBilah = (label, ket) => `<div class="simpan-bilah"><span class="redup kecil">${esc(ket || '')}</span>${ui.btn('Batal', { v: 'polos' })}${ui.btn(label, { v: 'utama', aksi: 'toast', arg: 'Tersimpan' })}</div>`;

  /* ---------- Susunan ---------- */
  ui.kepala = o => `<header class="ph"><div><h1>${esc(o.judul)}</h1>${o.tujuan ? `<p>${esc(o.tujuan)}</p>` : ''}</div>${o.aksi ? `<div class="ph-aksi">${o.aksi}</div>` : ''}</header>`;
  ui.tabs = (dasar, item, aktif) => `<nav class="tabs" aria-label="Bagian halaman">${item.map(t => `<a href="#/${dasar}/${t.k}"${t.k === aktif ? ' aria-current="page"' : ''}>${esc(t.label)}${t.jml != null ? `<span class="jml">${t.jml}</span>` : ''}${t.perlu ? `<span class="hitung">${t.perlu}</span>` : ''}</a>`).join('')}</nav>`;
  ui.panel = o => `<section class="${kelas('panel', o.rata && 'rata')}">${o.judul ? `<div class="panel-kepala"><h2>${esc(o.judul)}</h2>${o.cat || ''}${o.aksi ? `<div class="ph-aksi">${o.aksi}</div>` : ''}${o.ket ? `<p>${esc(o.ket)}</p>` : ''}</div>` : ''}<div class="panel-isi">${o.isi}</div></section>`;
  ui.angka = o => `<div class="angka"><div class="angka-utama"><b>${esc(o.utama.nilai)}</b><span>${esc(o.utama.label)} ${o.utama.delta != null ? ui.delta(o.utama.delta) : ''}</span>${o.utama.ket ? `<small>${esc(o.utama.ket)}</small>` : ''}</div>${o.lain ? `<div class="angka-lain">${o.lain.map(x => `<div><b>${esc(x.nilai)}</b><span>${esc(x.label)} ${x.delta != null ? ui.delta(x.delta) : ''}</span></div>`).join('')}</div>` : ''}</div>`;
  /* Strip angka pendukung tanpa angka utama (dipakai di bawah angka utama yang sudah ada) */
  ui.angkaPendukung = lain => `<div class="angka-lain">${lain.map(x => `<div><b>${esc(x.nilai)}</b><span>${esc(x.label)} ${x.delta != null ? ui.delta(x.delta) : ''}</span></div>`).join('')}</div>`;
  ui.kv = pas => `<dl class="kv">${pas.map(([k, v]) => `<dt>${esc(k)}</dt><dd>${v}</dd>`).join('')}</dl>`;

  /* Tabel di desktop (subgrid: kolom mengikuti isi), baris dua lapis di HP.
     kolom: {k, label, l (lebar), utama, nowrap, bungkus, aksi, kanan, hp: awal|utama|sub|akhir|tidak}
     baris: {sel, aksi, arg, terpilih, redup, bisaPilih (false = tanpa centang), hpSub (lapis 2 HP)} atau {grup: 'judul'} */
  ui.tabel = o => {
    const kol = o.pilih ? [{ k: '_p', label: '', l: '24px', hp: 'tidak' }, ...o.kolom] : o.kolom;
    const lebar = c => c.l || (c.utama ? 'minmax(200px, 2fr)' : c.aksi ? 'auto' : 'minmax(0, 1fr)');
    const kls = c => kelas(c.kanan && 'kanan num', c.nowrap && 'nowrap', c.bungkus && 'bungkus', c.aksi && 'sel-aksi', c.utama && 'sel-utama');
    const sel = (c, r) => c.k === '_p' ? (r.bisaPilih === false ? '' : ui.centang('', { on: r.terpilih, sr: 'Pilih baris', aksi: 'pilih-baris' })) : (r.sel[c.k] ?? '');
    const hpSub = r => r.hpSub ?? kol.filter(c => c.hp === 'sub').map(c => r.sel[c.k]).filter(Boolean).join(' · ');
    const kepalaPilih = o.pilih ? ui.centang('', { sr: 'Pilih semua yang bisa dipilih', aksi: 'pilih-semua' }) : '';
    const baris = r => r.grup != null
      ? `<div class="tbl-grup" role="row"><span role="cell">${r.grup}</span></div>`
      : `<div class="${kelas('tbl-b', r.redup && 'redup-baris')}" role="row"${r.terpilih ? ' aria-selected="true"' : ''}${klik(r).replace(' role="button"', '')}>${kol.map(c => `<span role="cell" data-hp="${c.hp === 'sub' ? 'tidak' : c.hp || 'tidak'}" class="${kls(c)}">${sel(c, r)}</span>`).join('')}${hpSub(r) ? `<span class="sel-hp" data-hp="sub">${hpSub(r)}</span>` : ''}</div>`;
    return `${o.catatan || ''}<div class="tbl" style="--cols:${kol.map(lebar).join(' ')}" role="table"><div class="tbl-k" role="row">${kol.map(c => `<span role="columnheader" class="${kls(c)}">${c.k === '_p' ? kepalaPilih : esc(c.label)}</span>`).join('')}</div>${o.baris.map(baris).join('')}</div>`;
  };
  /* Status: titik + teks untuk keadaan biasa; lencana hanya untuk yang perlu perhatian (K6) */
  ui.titikStatus = (teks, nada = 'abu') => `<span class="st"><span class="st-titik" data-n="${nada}"></span>${esc(teks)}</span>`;
  ui.catatanTabel = (teks, tautan) => `<p class="catatan-tbl">${ui.ikon('info', 16)}<span>${teks}</span>${tautan ? ` <a href="${tautan[1]}">${esc(tautan[0])}</a>` : ''}</p>`;
  ui.durasi = m => m % 60 === 0 ? `${m / 60} jam` : m > 60 ? `${String(m / 60).replace('.', ',')} jam` : `${m} menit`;
  ui.batangMini = (isi, total, satuan = '') => `<span class="batang-mini" title="${isi} dari ${total}${satuan ? ' ' + satuan : ''}"><span><i style="width:${Math.round(isi / total * 100)}%"></i></span><small>${isi}/${total}${satuan ? ' ' + satuan : ''}</small></span>`;
  ui.aksiIkon = (label, ikon, aksi, arg) => ui.btn(label, { kecil: true, hanyaIkon: true, ikon, v: 'polos', aksi, arg, attrs: `title="${esc(label)}"` });
  ui.dua = (atas, bawah) => `<span class="dua"><span class="potong">${atas}</span><small class="potong">${bawah}</small></span>`;
  const polos = h => esc(String(h ?? '').replace(/<[^>]+>/g, '').replace(/&amp;/g, '&'));
  ui.lr = o => `<div class="${kelas('lr', o.datar && 'datar', o.baru && 'baru')}"${klik(o)}>${o.awal || '<span></span>'}<span class="lr-tengah"><b class="potong" title="${polos(o.judul)}">${o.judul}</b>${o.ket ? `<span class="potong" title="${polos(o.ket)}">${o.ket}</span>` : ''}</span><span class="lr-akhir">${o.akhir || ''}</span></div>`;
  ui.filter = o => `<div class="filter"><label class="cari">${ui.ikon('cari', 18)}<input type="search" placeholder="${esc(o.cari || 'Cari')}" aria-label="${esc(o.cari || 'Cari')}"></label>${o.isi || ''}${ui.btn(`Filter${o.n ? ` (${o.n})` : ''}`, { ikon: 'filter', attrs: 'style="height:36px"', aksi: 'filter-hp' }).replace('class="btn', 'class="filter-hp btn')}${o.akhir ? `<span class="dorong baris">${o.akhir}</span>` : ''}</div>`;
  ui.massal = (n, aksi) => `<div class="massal"><span>${n} dipilih</span>${aksi}<span class="dorong">${ui.btn('Batal pilih', { v: 'polos', kecil: true })}</span></div>`;
  ui.hal = (teks, lagi = true) => `<div class="hal"><span>${esc(teks)}</span>${lagi ? `<span class="dorong">${ui.btn('Sebelumnya', { kecil: true, ikon: 'kiri', hanyaIkon: true, mati: true })}${ui.btn('Berikutnya', { kecil: true, ikon: 'kanan', hanyaIkon: true })}</span>` : ''}</div>`;
  ui.banner = o => `<div class="${kelas('banner', o.nada)}" role="${o.nada === 'bahaya' ? 'alert' : 'status'}">${ui.ikon(o.ikon || (o.nada ? 'awas' : 'info'))}<div><b>${esc(o.judul)}</b>${o.teks ? `<p>${esc(o.teks)}</p>` : ''}</div>${o.aksi ? `<span class="dorong">${o.aksi}</span>` : ''}</div>`;
  ui.kosong = o => `<div class="kosong">${ui.ikon(o.ikon || 'kotak', 32)}<b>${esc(o.judul)}</b>${o.teks ? `<p>${esc(o.teks)}</p>` : ''}${o.aksi || ''}</div>`;
  ui.kerangka = () => `<div class="kerangka" aria-label="Memuat"><i></i><i></i><i></i></div>`;
  ui.kuota = o => { const p = Math.min(100, Math.round(o.pakai / o.batas * 100)); return `<div class="${kelas('kuota', p >= 80 && 'hampir')}"><div class="kuota-teks"><span>${esc(o.label)}</span><span class="num tebal">${o.pakai} dari ${o.batas}</span></div><div class="kuota-rel"><i style="width:${p}%"></i></div></div>`; };
  ui.batang = o => `<div class="batang"><span class="potong" title="${esc(o.label)}">${esc(o.label)}</span><span class="batang-rel"><i style="width:${Math.round(o.nilai / o.maks * 100)}%"></i></span><span class="num tebal">${esc(o.teks)}</span></div>`;
  ui.tiang = (nilai, kini, kiri, kanan) => { const m = Math.max(...nilai, 1); return `<div><div class="tiang" role="img" aria-label="Grafik batang ${nilai.length} hari">${nilai.map((v, i) => `<i class="${kelas(i === kini && 'kini', v === 0 && 'nol')}" style="height:${Math.round(v / m * 100)}%" title="${v}"></i>`).join('')}</div><div class="tiang-kaki"><span>${esc(kiri)}</span><span>${esc(kanan)}</span></div></div>`; };

  /* ---------- Waktu ---------- */
  ui.ri = o => `<div class="${kelas('ri', o.nada)}"${klik(o)}><span class="ri-waktu">${esc(o.waktu)}</span><span class="ri-siapa"><b class="potong">${esc(o.nama)}</b><span class="potong">${esc(o.ket || '')}</span></span><span>${o.status || ''}</span><span>${o.tindak || ''}</span></div>`;
  ui.rel = baris => `<div class="rel">${baris.map(b => b.kini ? `<div class="rel-kini" aria-label="Sekarang pukul ${b.kini}"><span>${b.kini}</span><i></i></div>` : `<div class="rel-jam"><b>${b.jam}</b><div class="rel-isi">${(b.isi || []).join('')}</div></div>`).join('')}</div>`;
  ui.bulan = o => `<div class="bulan">${['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'].map(h => `<span>${h}</span>`).join('')}${'<i class="tutup"></i>'.repeat(o.geser)}${o.hari.map(h => h.slot ? `<a href="#/kalender/hari" class="${kelas(h.kini && 'hari-ini')}"><b>${h.n}</b><span><span class="isi-rel"><i style="width:${Math.round(h.isi / h.slot * 100)}%"></i></span><small>${h.isi} dari ${h.slot}</small></span></a>` : `<i class="tutup"${h.tutup ? ` title="${h.tutup}"` : ''}><b>${h.n}</b><small>${h.tutup ? 'Tutup' : 'Belum ada slot'}</small></i>`).join('')}</div>`;
  ui.peta = o => `<div class="peta" style="--n:${o.jam.length}"><div class="peta-baris"><span></span>${o.jam.map(j => `<b>${j}</b>`).join('')}</div>${o.baris.map(b => `<div class="peta-baris"><span>${b.hari}</span>${b.nilai.map((v, i) => `<i data-l="${Math.min(4, v)}" title="${b.hari} ${o.jam[i]}.00: ${v} booking"></i>`).join('')}</div>`).join('')}<div class="peta-legenda"><span>Sepi</span>${[0, 1, 2, 3, 4].map(l => `<i data-l="${l}"></i>`).join('')}<span>Ramai</span></div></div>`;
  /* HP: satu baris per hari (keterbacaan sesi 3); desktop: formulir lengkap */
  ui.jamBukaHp = hari => `<div class="jam-buka-hp">${hari.map(h => ui.lr({ judul: h.hari, ket: h.buka ? h.rentang.map(r => r.join('–')).join(', ') : 'Tutup', akhir: ui.btn(`Ubah ${h.hari}`, { kecil: true, v: 'polos', ikon: 'kanan', hanyaIkon: true, aksi: 'toast', arg: `Mengubah jam ${h.hari}` }) })).join('')}</div>`;
  ui.jamBuka = hari => `<div class="jam-buka">${hari.map(h => `<div class="${kelas('jam-buka-baris', !h.buka && 'tutup')}"><b>${h.hari}</b><label class="sakelar"><input type="checkbox" role="switch"${h.buka ? ' checked' : ''} aria-label="${h.hari} buka"><span></span></label><div class="rentang">${h.buka ? h.rentang.map((r, i) => `<span class="rentang-satu"><input class="input" value="${r[0]}" aria-label="${i ? 'Buka lagi' : 'Buka'}"><span class="redup">–</span><input class="input" value="${r[1]}" aria-label="${i ? 'Tutup' : 'Mulai jeda'}"></span>`).join('') : '<span class="redup">Tutup</span>'}</div>${h.buka ? ui.btn('Tambah jeda', { v: 'polos', kecil: true }) : '<span></span>'}</div>`).join('')}</div>`;
  ui.langkah = item => `<ol class="langkah">${item.map((l, i) => `<li class="${l.st || ''}"><span>${l.st === 'selesai' ? ui.ikon('cek', 16) : i + 1}</span><div><b>${esc(l.judul)}</b><span>${esc(l.ket)}</span></div>${l.aksi || ''}</li>`).join('')}</ol>`;
  /* Navigasi tanggal: ‹ Hari ini › Senin, 16 Nov 2026 */
  ui.tglNav = label => `<div class="tgl-nav">${ui.btn('Sebelumnya', { kecil: true, ikon: 'kiri', hanyaIkon: true })}${ui.btn('Hari ini', { kecil: true })}${ui.btn('Berikutnya', { kecil: true, ikon: 'kanan', hanyaIkon: true })}<b>${esc(label)}</b></div>`;
  ui.alat = (...isi) => `<div class="alat">${isi.filter(Boolean).join('')}</div>`;

  /* Kisi hari: jam × kolom (layanan atau unit). blok: {kol, mulai:'09.00', selesai, isi, nada, aksi, arg} */
  const menit = j => { const [h, m] = j.split('.').map(Number); return h * 60 + m; };
  ui.kisiHari = o => {
    const awal = o.dari * 60, baris = (o.sampai - o.dari) * 2;
    const r = j => 2 + (menit(j) - awal) / 30;
    const jam = Array.from({ length: o.sampai - o.dari }, (_, i) => `<b style="grid-row:${2 + i * 2}">${String(o.dari + i).padStart(2, '0')}</b>`).join('');
    const kol = o.kolom.map((k, i) => `<div class="kh-kepala" style="grid-column:${i + 2}" title="${esc(k.nama)}"><b>${esc(k.nama)}</b>${k.ket ? `<small class="potong">${esc(k.ket)}</small>` : ''}</div><i class="kh-kol" style="grid-column:${i + 2}"></i>`).join('');
    const blok = o.blok.map(b => `<div class="${kelas('kh-blok', b.nada, menit(b.selesai) - menit(b.mulai) <= 60 && 'pendek')}" style="grid-column:${b.kol + 2};grid-row:${r(b.mulai)} / ${r(b.selesai)}"${klik(b)}>${b.isi}</div>`).join('');
    const k = o.kini ? (() => { const x = r(o.kini); return `<div class="kh-kini" style="grid-row:${Math.floor(x)};margin-top:${Math.round((x % 1) * 24)}px" aria-label="Sekarang pukul ${o.kini}"><span>${o.kini}</span><i></i></div>`; })() : '';
    return `<div class="kisi-hari-bungkus"><div class="kisi-hari" style="--kol:${o.kolom.length};--baris-jam:${baris}">${jam}${kol}${blok}${k}</div></div>`;
  };

  /* Kisi minggu: satu baris ringkas per booking. hari: {hari, tgl, kini, tutup, b:[[jam, nama, status, aksi?, arg?]]} */
  ui.minggu = hari => `<div class="minggu">${hari.map(h => `<section class="${kelas('mg-hari', h.kini && 'kini', h.tutup && 'tutup')}"><header><span>${esc(h.hari)}</span><b>${h.tgl}</b><small>${h.tutup ? 'Tutup' : h.b.length ? `${h.b.length} booking` : ''}</small></header>${h.b.length ? h.b.map(x => `<a class="mg-b" href="#/kalender/hari"${x[3] ? ` data-aksi="${x[3]}" data-arg="${esc(x[4] || '')}"` : ''}><span class="jam">${x[0]}</span><span class="potong">${esc(x[1])}</span><i class="titik-st" data-st="${x[2]}" title="${ST[x[2]][1]}"></i></a>`).join('') : `<p class="mg-kosong">${h.tutup ? 'Tutup sepanjang hari' : 'Tidak ada slot'}</p>`}</section>`).join('')}</div>`;

  /* ---------- Langkah 3 dst. ---------- */
  ui.titikWarna = w => `<i class="titik-warna" data-w="${w}" aria-hidden="true"></i>`;
  ui.lipat = (judul, isi) => `<details class="lipat"><summary>${ui.ikon('kanan', 16)}${esc(judul)}</summary><div>${isi}</div></details>`;
  /* Kartu grid: hanya untuk hal yang memang sejajar (layanan bergambar, promo). gambar: kunci pola, atau kosong = kartu ringkas */
  ui.kartu = o => `<article class="${kelas('kartu', o.mati && 'mati', o.besar && 'besar')}"${klik(o)}>${o.gambar ? `<div class="kartu-gambar" data-g="${o.gambar}">${ui.ikon('gambar', 32)}${o.lencana ? `<span class="kartu-gambar-lencana">${o.lencana}</span>` : ''}</div>` : ''}<div class="kartu-isi">${!o.gambar && o.lencana ? `<div>${o.lencana}</div>` : ''}<b>${esc(o.judul)}</b>${o.ket ? `<span>${o.ket}</span>` : ''}${o.isi || ''}</div>${o.kaki ? `<div class="kartu-kaki">${o.kaki}</div>` : ''}</article>`;
  ui.kisiKartu = isi => `<div class="kisi-kartu">${isi}</div>`;
  ui.kunci = o => ui.kosong({ ikon: 'kunci', judul: o.judul, teks: o.teks, aksi: ui.btn(`Lihat paket ${o.paket}`, { v: 'utama', href: '#/paket' }) });
  ui.pratinjauHp = isi => `<div class="hp-pratinjau" aria-label="Pratinjau di HP pelanggan"><div>${isi}</div></div>`;

  /* Butir yang perlu Anda di dalam laci: blok kunyit tanpa tombol (tombolnya di kaki laci) */
  ui.perluBlok = (judul, teks) => `<div class="perlu-blok">${ui.ikon('awas', 18)}<div><b>${esc(judul)}</b>${teks ? `<p>${esc(teks)}</p>` : ''}</div></div>`;
  ui.kesimpulan = teks => `<p class="kesimpulan">${ui.ikon('info', 18)}<span>${esc(teks)}</span></p>`;
  ui.slip = o => `<div class="slip"><div class="baris">${o.status}<span class="dorong redup kecil">${esc(o.sumber)}</span></div>${o.tanggal ? `<div class="slip-tgl">${esc(o.tanggal)}</div>` : ''}<div class="slip-jam">${esc(o.jam)}</div><div><b>${esc(o.layanan)}</b><div class="redup kecil">${esc(o.ket)}</div></div>${o.kaki ? `<div class="slip-sobek"></div>${o.kaki}` : ''}</div>`;
})();
