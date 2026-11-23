"""cek.py rute ... -> hanya baris bermasalah: galat konsol atau gulir mendatar pada 1366/375."""
import sys
from playwright.sync_api import sync_playwright
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')

with sync_playwright() as p:
    b = p.chromium.launch()
    masalah = 0
    for w in (1366, 375):
        pg = b.new_page(viewport={"width": w, "height": 812})
        if sys.argv[1].startswith("{"):
            pg.add_init_script("localStorage.setItem('bq-proto', %r)" % sys.argv[1])
        errs = []
        pg.on("pageerror", lambda e: errs.append(str(e)))
        pg.on("console", lambda m: errs.append(m.text) if m.type == "error" else None)
        for r in [a for a in sys.argv[1:] if not a.startswith("{")]:
            errs.clear()
            pg.goto(f"{BASIS}/#/{r}")
            pg.wait_for_timeout(250)
            gx = pg.evaluate("""() => { const v = document.getElementById('view'); const lebar = [...v.querySelectorAll('*')].filter(e => e.getBoundingClientRect().right > v.getBoundingClientRect().right + 1 && !e.closest('.kisi-hari-bungkus,.tabs,.segmen,.peta,.potong')).slice(0,3).map(e => e.className || e.tagName); return [v.scrollWidth - v.clientWidth, lebar]; }""")
            if errs or gx[0] > 0 or gx[1]:
                masalah += 1
                print(w, r, "err:", errs[:2], "geser:", gx)
        pg.close()
    print("selesai,", masalah, "masalah")
    b.close()
