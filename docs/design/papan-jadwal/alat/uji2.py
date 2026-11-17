"""Uji interaksi sesi 2: hanya teks, tanpa potret."""
from playwright.sync_api import sync_playwright
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')

U = BASIS + '/#/'
with sync_playwright() as p:
    b = p.chromium.launch()
    pg = b.new_page(viewport={'width': 1366, 'height': 900})
    errs = []
    pg.on('pageerror', lambda e: errs.append(str(e)))

    def go(r, state=None):
        pg.goto(U + r)
        if state is not None:
            pg.evaluate("s => localStorage.setItem('bq-proto', JSON.stringify(s))", state)
            pg.reload()
        pg.wait_for_timeout(250)

    def ada(sel):
        return pg.locator(sel).count() > 0

    def klik(sel):
        pg.locator(sel).first.click(); pg.wait_for_timeout(200)

    hasil = []
    go('beranda', {})
    hasil.append(('beranda banner slot', 'slot hanya sampai 20 Nov' in pg.inner_text('#view')))
    hasil.append(('beranda aksi cepat', 'Aksi cepat' in pg.inner_text('#view')))
    go('kalender/hari')
    hasil.append(('kalender kapasitas 1/8', '1/8 peserta' in pg.inner_text('#view')))
    # catat booking: galat nama kosong
    klik('.top .btn[data-aksi=catat]')
    hasil.append(('catat peserta', ada('#c5')))
    hasil.append(('catat email', ada('#c6')))
    hasil.append(('catat saran pelanggan', ada('.saran')))
    klik('[data-aksi=catat-simpan]')
    hasil.append(('catat galat nama', 'Isi nama pelanggan' in pg.inner_text('#lapis')))
    # multi-slot
    go('booking/mendatang')
    klik('.tbl-b[data-arg="BKG-M2T5QX"]')
    hasil.append(('slip multi-sesi', '2 sesi dalam satu booking' in pg.inner_text('#lapis')))
    # pencairan tiga langkah
    go('pembayaran')
    klik('.ph .btn[data-aksi=cairkan]')
    klik('#lapis [data-aksi=cairkan][data-arg="2"]')
    klik('#lapis [data-aksi=cairkan][data-arg="3"]')
    hasil.append(('cairkan langkah 3', 'Langkah 3 dari 3' in pg.inner_text('#lapis')))
    # promo ganda
    go('promo')
    klik('.ph [data-aksi=form-promo]')
    pg.fill('#p1', 'BRAMA15'); klik('[data-aksi=simpan-promo]')
    hasil.append(('promo galat ganda', 'sudah dipakai' in pg.inner_text('#lapis')))
    # formulir penampung
    for r, sel, kata in [('unit', '.ph [data-aksi=form-unit]', 'Tambah unit'), ('layanan/kategori', '.tbl-b[data-aksi=form-kategori]', 'Ubah kategori'),
                         ('layanan/addon', '.tbl-b[data-aksi=form-tambahan]', 'Ubah tambahan'), ('jam-buka/harga', '.tbl-b[data-aksi=form-jamramai]', 'Ubah jam ramai'),
                         ('jam-buka/harga', '.tbl-b[data-aksi=form-tglkhusus]', 'Ubah tanggal khusus')]:
        go(r); klik(sel)
        hasil.append((f'form {kata}', kata in pg.inner_text('#lapis')))
    go('jam-buka/slot'); klik('[data-aksi=menu-slot][data-arg=kosong]'); klik('.menu [data-aksi=harga-slot]')
    hasil.append(('harga slot', 'Ubah harga slot ini' in pg.inner_text('#lapis')))
    go('notifikasi'); klik('[data-aksi=menu-notif]'); klik('.menu [data-aksi=hapus-notif]')
    hasil.append(('hapus notifikasi', 'Hapus notifikasi ini?' in pg.inner_text('#lapis')))
    # keadaan
    go('booking', {'state': 'berakhir'})
    hasil.append(('berakhir banner booking', 'Langganan berakhir 14 Nov' in pg.inner_text('#view')))
    klik('.top .btn[data-aksi=catat]')
    hasil.append(('berakhir catat ditolak', 'belum bisa dicatat' in pg.inner_text('#lapis')))
    go('layanan')
    hasil.append(('berakhir tambah layanan mati', pg.locator('.ph .btn[disabled]').count() > 0))
    go('pembayaran', {'state': 'baru'})
    hasil.append(('baru cairkan mati', pg.locator('.ph .btn[disabled]').count() > 0 and 'Saldo belum bisa dicairkan' in pg.inner_text('#view')))
    go('booking', {'state': 'memuat'})
    hasil.append(('memuat kerangka', ada('#view .kerangka')))
    go('beranda', {'role': 'kasir'})
    hasil.append(('kasir tanpa refund', 'Refund menunggu' not in pg.inner_text('#view')))
    pg.evaluate("localStorage.removeItem('bq-proto')")

    gagal = [n for n, ok in hasil if not ok]
    print(f'{len(hasil) - len(gagal)}/{len(hasil)} lolos; gagal: {gagal}; galat js: {errs[:3]}')
    b.close()
