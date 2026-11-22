"""Audit kepadatan: per tabel/daftar per rute, hanya teks."""
from playwright.sync_api import sync_playwright
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')

U = BASIS + '/#/'
RUTE = ['beranda', 'kalender/hari', 'booking', 'booking/mendatang', 'booking/selesai', 'pelanggan', 'pelanggan/lama',
        'layanan', 'layanan/kategori', 'layanan/addon', 'unit', 'jam-buka', 'jam-buka/harga', 'jam-buka/libur', 'jam-buka/slot',
        'halaman-booking', 'halaman-booking/galeri',
        'pembayaran', 'pembayaran/transaksi', 'pembayaran/saldo', 'pembayaran/rekening',
        'laporan', 'laporan/pendapatan', 'laporan/okupansi', 'laporan/layanan', 'laporan/pelanggan', 'promo', 'promo/semua', 'ulasan',
        'pengaturan', 'pengaturan/kebijakan', 'pengaturan/notifikasi', 'pengaturan/tim', 'paket', 'notifikasi', 'bantuan']

UKUR = r"""() => {
  const lihat = e => e.getClientRects().length && getComputedStyle(e).visibility !== 'hidden';
  const daun = row => [...row.querySelectorAll('*')].filter(e => lihat(e) && !e.closest('.cat') && [...e.childNodes].some(n => n.nodeType === 3 && n.textContent.trim()));
  const out = [];
  const view = document.querySelector('#view');
  // tabel
  view.querySelectorAll('.tbl').forEach((t, i) => {
    const rows = [...t.querySelectorAll('.tbl-b')].filter(lihat);
    if (!rows.length) return;
    const kepala = t.querySelector('.tbl-k');
    const kol = kepala && lihat(kepala) ? [...kepala.children].filter(lihat).length : getComputedStyle(rows[0]).gridTemplateColumns.split(' ').length;
    let h = 0, teks = 0, kecil = 0, lenc = 0, potong = 0, kar = 0, dua = 0;
    rows.forEach(r => {
      h += r.getBoundingClientRect().height;
      const d = daun(r); teks += d.length;
      kecil += d.filter(e => parseFloat(getComputedStyle(e).fontSize) < 14).length;
      lenc += r.querySelectorAll('.lencana').length;
      potong += [...r.querySelectorAll('*')].filter(e => lihat(e) && e.scrollWidth > e.clientWidth + 1 && getComputedStyle(e).overflow !== 'visible').length;
      kar += r.innerText.replace(/\s+/g, ' ').trim().length;
      dua += r.querySelector('.dua small, small') ? 1 : 0;
    });
    const n = rows.length;
    out.push(`tbl${i} kol=${kol} baris=${n} tinggi=${(h/n).toFixed(0)} teks/baris=${(teks/n).toFixed(1)} kecil%=${teks ? Math.round(kecil*100/teks) : 0} lencana/baris=${(lenc/n).toFixed(1)} dua-lapis=${Math.round(dua*100/n)}% kar/baris=${Math.round(kar/n)} potong=${potong} judul="${(t.closest('.panel')?.querySelector('h2,h3')?.textContent||'').trim().slice(0,24)}"`);
  });
  // daftar lr
  const lr = [...view.querySelectorAll('.lr')].filter(lihat);
  if (lr.length) {
    let h = 0, teks = 0, kecil = 0;
    lr.forEach(r => { h += r.getBoundingClientRect().height; const d = daun(r); teks += d.length; kecil += d.filter(e => parseFloat(getComputedStyle(e).fontSize) < 14).length; });
    out.push(`lr n=${lr.length} tinggi=${(h/lr.length).toFixed(0)} teks/baris=${(teks/lr.length).toFixed(1)} kecil%=${teks ? Math.round(kecil*100/teks) : 0}`);
  }
  // halaman
  const semua = [...view.querySelectorAll('*')].filter(e => lihat(e) && !e.closest('.cat') && [...e.childNodes].some(n => n.nodeType === 3 && n.textContent.trim()));
  const k = semua.filter(e => parseFloat(getComputedStyle(e).fontSize) < 14).length;
  out.push(`HAL panel=${[...view.querySelectorAll('.panel')].filter(lihat).length} teks=${semua.length} kecil%=${Math.round(k*100/semua.length)} tinggi=${view.scrollHeight}`);
  return out;
}"""

with sync_playwright() as p:
    b = p.chromium.launch()
    for w in (1366, 375):
        pg = b.new_page(viewport={'width': w, 'height': 900})
        pg.goto(U + 'beranda'); pg.evaluate("localStorage.setItem('bq-proto', JSON.stringify({notes:false}))")
        print(f'===== {w}px')
        for r in RUTE:
            pg.goto(U + r); pg.wait_for_timeout(120)
            print(f'# {r}: ' + ' | '.join(pg.evaluate(UKUR)))
        pg.evaluate("localStorage.removeItem('bq-proto')")
        pg.close()
    b.close()
