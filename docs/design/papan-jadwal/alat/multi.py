"""multi.py w1,w2 rute[@klik-selector] ...  -> ../shots/prototipe/m-<rute>-<w>.png (atau folder di env KELUAR) (dipotong tinggi 1400)"""
import sys, os
from playwright.sync_api import sync_playwright
from PIL import Image
import os
BASIS = os.environ.get('BASIS', 'http://localhost:8765')

out = os.environ.get("KELUAR") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "shots", "prototipe")
os.makedirs(out, exist_ok=True)
widths = [int(w) for w in sys.argv[1].split(",")]
with sync_playwright() as p:
    b = p.chromium.launch()
    for spec in sys.argv[2:]:
        route, _, klik = spec.partition("@")
        for w in widths:
            pg = b.new_page(viewport={"width": w, "height": 900 if w > 500 else 812})
            errs = []
            pg.on("console", lambda m: errs.append(m.text) if m.type == "error" else None)
            pg.on("pageerror", lambda e: errs.append(str(e)))
            pg.goto(f"{BASIS}/#/{route}")
            pg.wait_for_timeout(500)
            if klik:
                pg.locator(klik).first.click()
                pg.wait_for_timeout(400)
            else:
                h = pg.evaluate("document.getElementById('view').scrollHeight") + 56
                pg.set_viewport_size({"width": w, "height": min(max(900, h), 1400)})
                pg.wait_for_timeout(200)
            sw = pg.evaluate("document.getElementById('view').scrollWidth - document.getElementById('view').clientWidth")
            nama = (route + ("_" + klik if klik else "")).replace("/", "_").replace("?", "_").replace("=", "-")
            nama = "".join(c for c in nama if c.isalnum() or c in "_-")[:60]
            f = os.path.join(out, f"m-{nama}-{w}.png")
            pg.screenshot(path=f)
            print(os.path.basename(f), "err:", errs, "geserX:", sw)
            pg.close()
    b.close()
