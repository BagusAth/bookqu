"""uji4.py: alur keadaan Galat (sesi 4, T3). Butuh prototipe di BASIS."""
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')
import json
from playwright.sync_api import sync_playwright
U=BASIS + '/#/'
def baru(b, st):
    pg=b.new_page(viewport={'width':1366,'height':900}); e=[]
    pg.on('pageerror', lambda x: e.append(str(x))); pg.on('console', lambda m: e.append(m.text) if m.type=='error' else None)
    pg.add_init_script("localStorage.setItem('bq-proto', %r)" % json.dumps(st)); return pg, e
with sync_playwright() as p:
    b=p.chromium.launch(); hasil=[]
    pg,e=baru(b,{'state':'galat'})
    pg.goto(U+'beranda'); pg.wait_for_timeout(300); pg.click('.top [data-aksi=catat]'); pg.fill('#c2','Budi'); pg.click('.lap-kaki [data-aksi=catat-simpan]')
    hasil.append(('catat bentrok', pg.locator('.lap-isi .galat-blok').count()==1 and pg.input_value('#c2')=='Budi'))
    pg.click('.galat-blok [data-aksi=ganti-jam]'); hasil.append(('ganti jam', pg.locator('.galat-blok').count()==0))
    pg.goto(U+'booking/mendatang'); pg.wait_for_timeout(300); pg.locator('.tbl-b').first.click(); pg.wait_for_timeout(200)
    pg.locator('.lap-kaki [data-aksi=menu-slip], .lap-kaki .btn-ikon').first.click(); pg.wait_for_timeout(200)
    m=pg.locator('[role=menuitem][data-aksi=pindah]')
    if m.count(): m.first.click(); pg.wait_for_timeout(200); pg.click('[data-aksi=pindah-simpan]'); hasil.append(('pindah bentrok', pg.locator('.galat-blok').count()==1))
    else: hasil.append(('pindah: menu tidak ditemukan', False))
    pg.goto(U+'pembayaran'); pg.wait_for_timeout(300); pg.click('text=Cairkan saldo'); pg.click('.lap-kaki >> text=Lanjut'); pg.click('.lap-kaki >> text=Lanjut'); pg.click('[data-aksi=cair-ajukan]'); pg.wait_for_timeout(200)
    hasil.append(('cair gagal', pg.locator('.dialog .galat-blok').count()==1))
    pg.goto(U+'layanan'); pg.wait_for_timeout(300); pg.locator('.tbl-b').first.click(); pg.wait_for_timeout(200)
    pg.locator('.lap-kaki .btn-utama, .lap-kaki [class*=utama]').last.click(); pg.wait_for_timeout(200)
    hasil.append(('simpan gagal', pg.locator('.toast-galat').count()==1 and pg.locator('#lapis [role=dialog]').count()==1))
    pg.goto(U+'sistem'); pg.wait_for_timeout(300); pg.click('[data-aksi=sesi-habis]'); hasil.append(('sesi habis', pg.locator('text=Sesi Anda berakhir').count()>0))
    hasil.append(('galat konsol', not e)); print(e[:3])
    pg2,e2=baru(b,{'state':'galat','plan':'small'}); pg2.goto(U+'beranda'); pg2.wait_for_timeout(300); pg2.click('.top [data-aksi=catat]')
    hasil.append(('kuota', pg2.locator('text=Kuota booking bulan ini habis').count()>0))
    pg3,e3=baru(b,{}); pg3.goto(U+'beranda'); pg3.wait_for_timeout(300); pg3.click('.top [data-aksi=catat]'); pg3.fill('#c2','Budi'); pg3.click('.lap-kaki [data-aksi=catat-simpan]')
    hasil.append(('normal tetap sukses', pg3.locator('.toast').count()==1 and pg3.locator('.toast-galat').count()==0))
    for h in hasil: print(h)
    b.close()
