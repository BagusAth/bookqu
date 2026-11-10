<!DOCTYPE html>
<html lang="id">
{{--
    Printable poster (BQ-142, FR-PUBLIC-006, AC-HBK-02): business name, the QR code of the current
    booking link, and the link itself. A standalone page so the browser prints only the poster.
    The QR SVG is generated on the server from the link by BuildBookingQr; it carries no user markup.
--}}
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Poster booking — {{ $tenant->namabisnis }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; color: #111827; background: #fff; }
        .poster { max-width: 170mm; margin: 0 auto; padding: 24px; text-align: center; }
        .poster h1 { margin: 0 0 6px; font-size: 32px; }
        .poster p { margin: 0; font-size: 18px; color: #374151; }
        .qr { width: 110mm; max-width: 100%; margin: 28px auto; }
        .qr svg { width: 100%; height: auto; display: block; }
        .link { margin-top: 8px; font-size: 14px; word-break: break-all; color: #4b5563; }
        .actions { margin-top: 24px; }
        .actions button { font: inherit; padding: 8px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; cursor: pointer; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <main class="poster">
        <h1>{{ $tenant->namabisnis }}</h1>
        <p>Pindai untuk booking online</p>
        <div class="qr" role="img" aria-label="Kode QR untuk {{ $link }}">{!! $qrSvg !!}</div>
        <p class="link">{{ $link }}</p>
        <div class="actions"><button type="button" onclick="window.print()">Cetak poster</button></div>
    </main>
</body>
</html>
