"""Sesi 3: papan ketik, kontras dari warna tampil, aturan gaya dari DOM. Hanya teks."""
import json, sys
from playwright.sync_api import sync_playwright
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')

U = BASIS + '/#/'
RUTE = ['beranda', 'kalender/hari', 'kalender/minggu', 'kalender/bulan', 'booking', 'booking/mendatang', 'pelanggan', 'pelanggan/lama',
        'layanan', 'layanan?tampil=kisi', 'layanan/kategori', 'layanan/addon', 'unit', 'jam-buka', 'jam-buka/harga', 'jam-buka/libur', 'jam-buka/slot',
        'halaman-booking', 'halaman-booking/tampilan', 'halaman-booking/galeri', 'halaman-booking/landing', 'halaman-booking/domain',
        'pembayaran', 'pembayaran/transaksi', 'pembayaran/saldo', 'pembayaran/rekening', 'pembayaran/cara',
        'laporan', 'laporan/pendapatan', 'laporan/okupansi', 'laporan/layanan', 'laporan/pelanggan', 'promo', 'promo/semua', 'ulasan',
        'pengaturan', 'pengaturan/kebijakan', 'pengaturan/notifikasi', 'pengaturan/integrasi', 'pengaturan/akun', 'pengaturan/tim',
        'paket', 'notifikasi', 'bantuan']

# Kontras dan aturan gaya, dijalankan di halaman. Mengembalikan daftar temuan.
PERIKSA = r"""(lingkup) => {
  const akar = document.querySelector(lingkup);
  if (!akar) return [];
  const rgb = s => { const m = s.match(/[\d.]+/g); return m ? m.map(Number) : null; };
  const lum = ([r, g, b]) => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4; }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
  const latar = el => { for (let e = el; e; e = e.parentElement) { const c = rgb(getComputedStyle(e).backgroundColor); if (c && (c.length < 4 || c[3] > 0.5)) return c; } return [255, 255, 255]; };
  const keluar = [];
  const lihat = e => e.getClientRects().length && getComputedStyle(e).visibility !== 'hidden';
  const alat = e => e.closest('.cat, .proto, [aria-hidden="true"], .buram, [disabled], .btn[disabled]');
  /* kontras + ukuran huruf untuk setiap elemen yang punya teks langsung */
  for (const el of akar.querySelectorAll('*')) {
    if (!lihat(el) || alat(el)) continue;
    const teks = [...el.childNodes].some(n => n.nodeType === 3 && n.textContent.trim());
    if (!teks) continue;
    const cs = getComputedStyle(el);
    const fz = parseFloat(cs.fontSize);
    if (fz < 12) keluar.push(['huruf<12', el.className || el.tagName, fz]);
    const fg = rgb(cs.color), bg = latar(el);
    const a = lum(fg), b = lum(bg), r = (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
    const besar = fz >= 18.66 || (fz >= 14 && +cs.fontWeight >= 700);
    if (r < (besar ? 3 : 4.5)) keluar.push(['kontras', (el.className || el.tagName) + ' "' + el.textContent.trim().slice(0, 24) + '"', +r.toFixed(2)]);
  }
  /* aturan pemakaian */
  const utama = [...akar.querySelectorAll('.btn-utama:not([disabled])')].filter(lihat);
  const atas = lingkup === '#view' ? [...document.querySelectorAll('#top .btn-utama')].filter(lihat) : [];
  if (utama.length + atas.length > 1) keluar.push(['utama>1', utama.concat(atas).map(b => b.textContent.trim()).join(' | '), utama.length + atas.length]);
  const banner = [...akar.querySelectorAll('.banner')].filter(lihat);
  if (lingkup === '#view' && banner.length > 1) keluar.push(['banner>1', banner.map(b => b.querySelector('b').textContent).join(' | '), banner.length]);
  banner.forEach(b => { if (!b.querySelector('.btn, a')) keluar.push(['banner tanpa tindakan', b.querySelector('b').textContent, 0]); });
  const angka = [...akar.querySelectorAll('.angka-utama')].filter(lihat).length;
  if (angka > 2) keluar.push(['angka utama>2', '', angka]);
  /* teks terpotong tanpa cara membaca isinya */
  const potong = [...akar.querySelectorAll('.potong, .kh-kepala b')].filter(e => lihat(e) && e.scrollWidth > e.clientWidth + 1 && !e.closest('[title]') && !e.title && !e.closest('[data-aksi]'));
  if (potong.length) keluar.push(['terpotong', potong.slice(0, 3).map(e => e.textContent.trim().slice(0, 30)).join(' | '), potong.length]);
  return keluar;
}"""

with sync_playwright() as p:
    b = p.chromium.launch()
    hasil = {}

    def catat(kunci, temuan):
        for t in temuan:
            hasil.setdefault((t[0], t[1]), []).append(kunci)

    for keadaan in [{}, {'sidebar': 'terong', 'role': 'kasir', 'plan': 'small'}]:
        for w in (1366, 375):
            pg = b.new_page(viewport={'width': w, 'height': 900})
            pg.goto(U + 'beranda'); pg.evaluate("s => localStorage.setItem('bq-proto', JSON.stringify(s))", keadaan)
            for r in RUTE:
                pg.goto(U + r); pg.wait_for_timeout(120)
                catat(f'{w} {r}{" (terong/kasir/small)" if keadaan else ""}', pg.evaluate(PERIKSA, '#view'))
                if r == 'beranda' and w == 1366:
                    catat(f'{w} cangkang', pg.evaluate(PERIKSA, '#sb'))
            pg.close()

    # Lapisan: buka tiap dialog/laci, periksa isinya
    pg = b.new_page(viewport={'width': 1366, 'height': 900})
    pg.goto(U + 'beranda'); pg.evaluate("localStorage.removeItem('bq-proto')")
    LAPIS = [('beranda', '.top [data-aksi=catat]'), ('booking', '.tbl-b[data-arg="BKG-R4KA7P"]'), ('beranda', '.ri[data-arg="BKG-CFWK9P"]'),
             ('booking/mendatang', '.tbl-b[data-arg="BKG-M2T5QX"]'), ('pelanggan', '.tbl-b[data-arg="Putra Permana"]'), ('pembayaran', '.ph [data-aksi=cairkan]'),
             ('unit', '.ph [data-aksi=form-unit]'), ('layanan', '.ph [data-aksi=ubah-layanan]'), ('jam-buka/slot', '.ph [data-aksi=buka-slot]'),
             ('jam-buka/libur', '.ph [data-aksi=tutup-tanggal]'), ('promo', '.ph [data-aksi=form-promo]'), ('pengaturan/tim', '[data-aksi=undang]'),
             ('paket', '[data-aksi=checkout]'), ('beranda', '.bnav [data-aksi=lainnya]')]
    for r, sel in LAPIS:
        pg.goto(U + r); pg.wait_for_timeout(150)
        loc = pg.locator(sel).first
        if not loc.is_visible():
            pg.set_viewport_size({'width': 375, 'height': 812}); pg.goto(U + r); pg.wait_for_timeout(150)
        pg.locator(sel).first.click(); pg.wait_for_timeout(200)
        catat(f'lapis {r} {sel}', pg.evaluate(PERIKSA, '#lapis'))
        pg.set_viewport_size({'width': 1366, 'height': 900})

    # Papan ketik
    kb = []
    pg.goto(U + 'booking'); pg.wait_for_timeout(200)
    pg.focus('.top [data-aksi=catat]'); pg.keyboard.press('Enter'); pg.wait_for_timeout(200)
    di_dalam = all(pg.evaluate("() => { const r = document.activeElement.closest('#lapis'); return !!r; }") or pg.keyboard.press('Tab') for _ in range(1))
    keluar_trap = 0
    for _ in range(40):
        pg.keyboard.press('Tab')
        if not pg.evaluate("() => !!document.activeElement.closest('#lapis [role=dialog]')"):
            keluar_trap += 1
    for _ in range(10):
        pg.keyboard.press('Shift+Tab')
        if not pg.evaluate("() => !!document.activeElement.closest('#lapis [role=dialog]')"):
            keluar_trap += 1
    kb.append(('fokus terkunci di dialog (50 Tab)', keluar_trap == 0))
    kb.append(('outline fokus terlihat', pg.evaluate("() => getComputedStyle(document.activeElement).outlineStyle !== 'none'")))
    pg.keyboard.press('Escape'); pg.wait_for_timeout(150)
    kb.append(('Esc menutup dan fokus kembali ke pemicu', pg.evaluate("() => !document.querySelector('#lapis').innerHTML && document.activeElement.matches('.top [data-aksi=catat]')")))
    pg.goto(U + 'jam-buka/slot'); pg.wait_for_timeout(200)
    pg.focus('[data-aksi=menu-slot][data-arg=kosong]'); pg.keyboard.press('Enter'); pg.wait_for_timeout(150)
    awal = pg.evaluate("() => document.activeElement.textContent.trim()")
    pg.keyboard.press('ArrowDown'); dua = pg.evaluate("() => document.activeElement.textContent.trim()")
    pg.keyboard.press('End'); akhir = pg.evaluate("() => document.activeElement.textContent.trim()")
    pg.keyboard.press('Home'); kembali = pg.evaluate("() => document.activeElement.textContent.trim()")
    kb.append(('menu panah/Home/End', awal != dua and akhir == 'Hapus slot' and kembali == awal))
    pg.keyboard.press('Escape')
    pg.goto(U + 'booking'); pg.wait_for_timeout(200)
    pg.focus('.tabs a[aria-current]'); pg.keyboard.press('ArrowRight')
    kb.append(('tab panah kanan', pg.evaluate("() => document.activeElement.textContent.startsWith('Mendatang')")))
    pg.focus('.tbl-b[data-arg="BKG-HQ81ZD"]'); pg.keyboard.press('Enter'); pg.wait_for_timeout(200)
    kb.append(('baris dibuka dengan Enter', 'BKG-HQ81ZD' in pg.inner_text('#lapis')))
    pg.keyboard.press('Escape')
    pg.goto(U + 'beranda'); pg.evaluate("localStorage.setItem('bq-proto', JSON.stringify({sidebar:'terong'}))"); pg.reload(); pg.wait_for_timeout(200)
    pg.focus('#sb a[href="#/kalender"]'); pg.keyboard.press('Tab'); pg.keyboard.press('Shift+Tab')
    kb.append(('fokus sidebar terong terlihat', pg.evaluate("() => { const c = getComputedStyle(document.activeElement); return c.outlineStyle !== 'none' && c.outlineColor !== 'rgb(56, 33, 134)'; }")))
    pg.evaluate("localStorage.removeItem('bq-proto')")
    b.close()

    print('PAPAN KETIK:', ', '.join(f"{n}: {'ya' if ok else 'TIDAK'}" for n, ok in kb))
    print('TEMUAN (jenis | contoh | di mana):')
    for (jenis, contoh), tempat in sorted(hasil.items()):
        print(f'- {jenis} | {contoh[:70]} | {len(tempat)}x, mis. {tempat[0]}')
