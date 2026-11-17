"""tepi.py lebar|manajer|kasir|panjang -> rute bermasalah: galat, luapan ke kanan, teks terpotong tanpa title (abaikan kelas sr)."""
import re, json, sys, os
from playwright.sync_api import sync_playwright
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')
OUT = os.environ.get('KELUAR') or os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'shots', 'tepi'); os.makedirs(OUT, exist_ok=True)
src = open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'uji3.py'), encoding='utf-8').read()
RUTE = re.findall(r"'([^']+)'", re.search(r'RUTE = \[(.*?)\]', src, re.S).group(1))
GX = """() => { const v=document.getElementById('view'); const vr=v.getBoundingClientRect();
 const lebar=[...v.querySelectorAll('*')].filter(e=>e.getBoundingClientRect().right>vr.right+1 && !e.closest('.kisi-hari-bungkus,.tabs,.segmen,.peta,.potong')).slice(0,2).map(e=>(e.className||e.tagName).toString().slice(0,40));
 const potong=[...v.querySelectorAll('*')].filter(e=>e.children.length===0 && e.textContent.trim() && e.scrollWidth>e.clientWidth+1 && getComputedStyle(e).overflow!=='visible' && !e.title && !e.closest('[title]') && !e.classList.contains('sr')).length;
 return [document.documentElement.scrollWidth-innerWidth, lebar, potong]; }"""
def run(label, widths, store=None, longnames=False, shots=()):
    with sync_playwright() as p:
        b = p.chromium.launch(); n = 0
        for w in widths:
            pg = b.new_page(viewport={'width': w, 'height': 900})
            if store: pg.add_init_script("localStorage.setItem('bq-proto', %r)" % json.dumps(store))
            if longnames:
                def h(route):
                    r = route.fetch(); t = r.text()
                    t = t.replace("'Umar Wirahadi'", "'Muhammad Fadhil Rizky Pratama Wirahadikusuma'").replace("'Maya Wulan'", "'Maya Wulandari Kusumaningrum Hadiwijaya'")
                    route.fulfill(response=r, body=t)
                pg.route('**/js/data.js', h)
            errs = []; pg.on('pageerror', lambda e: errs.append(str(e)[:80]))
            for r in RUTE:
                errs.clear(); pg.goto(f'{BASIS}/#/{r}'); pg.wait_for_timeout(200)
                gx = pg.evaluate(GX)
                if errs or gx[0] > 0 or gx[1] or gx[2]:
                    n += 1; print(label, w, r, 'err', errs[:1], 'docX', gx[0], 'lebar', gx[1], 'terpotong', gx[2])
                if r in shots: pg.screenshot(path=f'{OUT}/{label}-{r.replace("/","_")}-{w}.png')
            pg.close()
        print(label, 'selesai', n, 'masalah'); b.close()
mode = sys.argv[1]
if mode == 'lebar': run('lebar', [768, 1024, 1920], shots=('beranda', 'kalender/hari', 'booking'))
if mode == 'manajer': run('manajer', [1366], {'role': 'manajer'})
if mode == 'kasir': run('kasir', [1366, 375], {'role': 'kasir'})
if mode == 'panjang': run('panjang', [1366, 375], longnames=True, shots=('beranda', 'booking', 'kalender/hari'))
