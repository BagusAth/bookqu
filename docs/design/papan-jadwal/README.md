# Papan Jadwal: acuan tampilan portal owner

Acuan untuk BQ-162 (`docs/work/BQ-162-papan-jadwal.md`), disetujui lewat D-025. Prototipe di sini adalah HTML/CSS/JS statis, **bukan kode untuk disalin**: halaman asli dibangun ulang dengan Blade, Alpine dan token Tailwind.

## Menjalankan

```bash
php -S 127.0.0.1:8770 -t docs/design/papan-jadwal/prototype
```

Buka `http://127.0.0.1:8770/#/beranda`. Tombol "Prototipe" (kanan bawah) berisi sakelar peran, paket, keadaan (termasuk Galat), data (Biasa/Ramai), kepadatan, sidebar dan label perubahan. Katalog komponen di `#/sistem`.

## Isi folder

| Berkas | Isi |
|---|---|
| `KEPUTUSAN.md` | Semua butir B (isi berubah) dan C (fitur baru): layar, data, jalur, sesi BQ-162 |
| `prototype/README.md` | Panduan gaya, status, verifikasi tiap sesi |
| `prototype/HANDOFF.md` | Peta komponen prototipe → `x-ui`, aturan keterbacaan |
| `audit-prototipe.md` | Audit sebelum prototipe dijadikan acuan (P1 sudah ditutup di sesi 4) |
| `audit-ui.md` | Audit kode UI portal asli (celah G-1–G-11) dan tahapan |
| `review.md`, `data-inventory.md` | Review desainer produk dan daftar data yang ada di sistem |
| `riwayat/` | Rencana sesi prototipe; "Aturan desain baru" ada di `rencana-keterbacaan-prototipe.md` |
| `alat/` | Skrip Python Playwright (opsional, butuh `pip install playwright`): `cek.py`, `tepi.py`, `padat.py`, `uji2.py`, `uji3.py`, `uji4.py`, `multi.py`. Atur alamat lewat `BASIS=http://127.0.0.1:8770`. Potret masuk `shots/` (diabaikan git). |
